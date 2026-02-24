<?php
/**
 * Utility / Helper Functions
 */

/**
 * Escape output for HTML
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a URL-safe slug from a string
 */
function slugify(string $text): string
{
    $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Ensure a slug is unique in the specimens table
 */
function uniqueSlug(string $slug, ?int $excludeId = null): string
{
    $original = $slug;
    $counter = 1;

    while (true) {
        $sql = 'SELECT id FROM specimens WHERE slug = ?';
        $params = [$slug];

        if ($excludeId) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $existing = Database::fetch($sql, $params);
        if (!$existing) break;

        $slug = $original . '-' . $counter;
        $counter++;
    }

    return $slug;
}

/**
 * Redirect to a URL
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Flash message (set or get)
 */
function flash(?string $type = null, ?string $message = null): ?array
{
    if ($type && $message) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        return null;
    }

    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    return null;
}

/**
 * Render a view with data
 */
function view(string $viewPath, array $data = [], ?string $layout = null): void
{
    extract($data);
    $viewFile = __DIR__ . '/views/' . $viewPath . '.php';

    if ($layout) {
        ob_start();
        require $viewFile;
        $content = ob_get_clean();
        require __DIR__ . '/views/layouts/' . $layout . '.php';
    } else {
        require $viewFile;
    }
}

/**
 * Get the current page number from query string
 */
function currentPage(): int
{
    return max(1, (int)($_GET['page'] ?? 1));
}

/**
 * Generate pagination HTML
 */
function pagination(int $total, int $perPage, int $currentPage, string $baseUrl): string
{
    $totalPages = (int)ceil($total / $perPage);
    if ($totalPages <= 1) return '';

    $html = '<nav class="pagination">';

    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" class="page-link">&laquo; Prev</a>';
    }

    $start = max(1, $currentPage - 3);
    $end = min($totalPages, $currentPage + 3);

    if ($start > 1) {
        $html .= '<a href="' . $baseUrl . '?page=1" class="page-link">1</a>';
        if ($start > 2) $html .= '<span class="page-ellipsis">&hellip;</span>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="page-link' . $active . '">' . $i . '</a>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<span class="page-ellipsis">&hellip;</span>';
        $html .= '<a href="' . $baseUrl . '?page=' . $totalPages . '" class="page-link">' . $totalPages . '</a>';
    }

    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" class="page-link">Next &raquo;</a>';
    }

    $html .= '</nav>';
    return $html;
}

/**
 * Process and save an uploaded image with thumbnail
 */
function processUpload(array $file, int $specimenId): ?array
{
    $config = require __DIR__ . '/config.php';
    $upload = $config['uploads'];

    // Validate
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > $upload['max_file_size']) return null;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $upload['allowed_types'])) return null;

    // Generate unique filename
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => 'jpg',
    };
    $filename = $specimenId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

    // Get image dimensions
    $imageInfo = getimagesize($file['tmp_name']);
    $origWidth = $imageInfo[0];
    $origHeight = $imageInfo[1];

    // Resize original if too large
    $source = imageFromFile($file['tmp_name'], $mime);
    if (!$source) return null;

    if ($origWidth > $upload['max_width']) {
        $ratio = $upload['max_width'] / $origWidth;
        $newWidth = $upload['max_width'];
        $newHeight = (int)($origHeight * $ratio);
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        preserveTransparency($resized, $mime);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        saveImage($resized, $upload['originals_dir'] . '/' . $filename, $mime);
        imagedestroy($resized);
        $origWidth = $newWidth;
        $origHeight = $newHeight;
    } else {
        move_uploaded_file($file['tmp_name'], $upload['originals_dir'] . '/' . $filename);
    }

    // Create thumbnail (square crop from center)
    $thumbSize = $upload['thumbnail_width'];
    $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
    preserveTransparency($thumb, $mime);

    $sourceForThumb = imageFromFile($upload['originals_dir'] . '/' . $filename, $mime);
    $srcSize = min($origWidth, $origHeight);
    $srcX = (int)(($origWidth - $srcSize) / 2);
    $srcY = (int)(($origHeight - $srcSize) / 2);

    imagecopyresampled($thumb, $sourceForThumb, 0, 0, $srcX, $srcY, $thumbSize, $thumbSize, $srcSize, $srcSize);
    saveImage($thumb, $upload['thumbs_dir'] . '/' . $filename, $mime);

    imagedestroy($source);
    imagedestroy($sourceForThumb);
    imagedestroy($thumb);

    return [
        'filename'      => $filename,
        'original_name' => $file['name'],
        'file_size'     => filesize($upload['originals_dir'] . '/' . $filename),
        'width'         => $origWidth,
        'height'        => $origHeight,
    ];
}

/**
 * Create GD image resource from file
 */
function imageFromFile(string $path, string $mime): GdImage|false
{
    return match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($path),
        'image/png'  => imagecreatefrompng($path),
        'image/webp' => imagecreatefromwebp($path),
        'image/gif'  => imagecreatefromgif($path),
        default      => false,
    };
}

/**
 * Save GD image to file
 */
function saveImage(GdImage $image, string $path, string $mime): void
{
    match ($mime) {
        'image/jpeg' => imagejpeg($image, $path, 85),
        'image/png'  => imagepng($image, $path, 8),
        'image/webp' => imagewebp($image, $path, 85),
        'image/gif'  => imagegif($image, $path),
        default      => imagejpeg($image, $path, 85),
    };
}

/**
 * Preserve transparency for PNG/WebP/GIF
 */
function preserveTransparency(GdImage $image, string $mime): void
{
    if (in_array($mime, ['image/png', 'image/webp', 'image/gif'])) {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
    }
}

/**
 * Delete a photo's files from disk
 */
function deletePhotoFiles(string $filename): void
{
    $config = require __DIR__ . '/config.php';
    $origPath = $config['uploads']['originals_dir'] . '/' . $filename;
    $thumbPath = $config['uploads']['thumbs_dir'] . '/' . $filename;

    if (file_exists($origPath)) unlink($origPath);
    if (file_exists($thumbPath)) unlink($thumbPath);
}

/**
 * Format file size for display
 */
function formatFileSize(int $bytes): string
{
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
