#!/usr/bin/env php
<?php
/**
 * Regenerate Thumbnails
 *
 * Recreates all thumbnails from originals using fit-within logic
 * (preserves aspect ratio, no cropping).
 *
 * Usage: php regenerate_thumbs.php
 */

$config = require __DIR__ . '/app/config.php';
$originalsDir = $config['uploads']['originals_dir'];
$thumbsDir    = $config['uploads']['thumbs_dir'];
$maxThumb     = $config['uploads']['thumbnail_width'];

require __DIR__ . '/app/helpers.php';

if (!is_dir($originalsDir)) {
    echo "Originals directory not found: {$originalsDir}\n";
    exit(1);
}

if (!is_dir($thumbsDir)) {
    mkdir($thumbsDir, 0755, true);
}

$files = array_diff(scandir($originalsDir), ['.', '..', '.gitkeep']);
$total = count($files);
$done  = 0;
$errors = 0;

echo "Regenerating {$total} thumbnails...\n";

foreach ($files as $filename) {
    $origPath  = $originalsDir . '/' . $filename;
    $thumbPath = $thumbsDir . '/' . $filename;

    // Detect MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $origPath);
    finfo_close($finfo);

    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
        echo "  SKIP  {$filename} (unsupported type: {$mime})\n";
        continue;
    }

    $source = imageFromFile($origPath, $mime);
    if (!$source) {
        echo "  ERROR {$filename} (could not load image)\n";
        $errors++;
        continue;
    }

    $origWidth  = imagesx($source);
    $origHeight = imagesy($source);

    // Fit within maxThumb, preserving aspect ratio
    if ($origWidth > $origHeight) {
        $thumbW = $maxThumb;
        $thumbH = (int)($origHeight * ($maxThumb / $origWidth));
    } else {
        $thumbH = $maxThumb;
        $thumbW = (int)($origWidth * ($maxThumb / $origHeight));
    }

    $thumb = imagecreatetruecolor($thumbW, $thumbH);
    preserveTransparency($thumb, $mime);
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbW, $thumbH, $origWidth, $origHeight);
    saveImage($thumb, $thumbPath, $mime);

    imagedestroy($source);
    imagedestroy($thumb);

    $done++;
    echo "  OK    {$filename} ({$origWidth}x{$origHeight} → {$thumbW}x{$thumbH})\n";
}

echo "\nDone: {$done} regenerated, {$errors} errors, " . ($total - $done - $errors) . " skipped.\n";
