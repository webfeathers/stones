<?php
/**
 * Photo Model
 */

class Photo
{
    /**
     * Find by ID
     */
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM photos WHERE id = ?', [$id]);
    }

    /**
     * Add a photo to a specimen
     */
    public static function create(int $specimenId, array $data): int
    {
        // Get next sort order
        $maxSort = Database::fetch(
            'SELECT COALESCE(MAX(sort_order), -1) + 1 as next_sort FROM photos WHERE specimen_id = ?',
            [$specimenId]
        );

        // If this is the first photo, make it primary
        $existingCount = Database::fetch(
            'SELECT COUNT(*) as cnt FROM photos WHERE specimen_id = ?',
            [$specimenId]
        )['cnt'];

        $isPrimary = ($existingCount == 0) ? 1 : 0;

        Database::query(
            'INSERT INTO photos (specimen_id, filename, original_name, caption, sort_order, is_primary, file_size, width, height)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $specimenId,
                $data['filename'],
                $data['original_name'] ?? null,
                $data['caption'] ?? null,
                $maxSort['next_sort'],
                $isPrimary,
                $data['file_size'] ?? null,
                $data['width'] ?? null,
                $data['height'] ?? null,
            ]
        );

        return (int)Database::lastInsertId();
    }

    /**
     * Set a photo as the primary (and unset others for that specimen)
     */
    public static function setPrimary(int $photoId, int $specimenId): void
    {
        Database::query('UPDATE photos SET is_primary = 0 WHERE specimen_id = ?', [$specimenId]);
        Database::query('UPDATE photos SET is_primary = 1 WHERE id = ?', [$photoId]);
    }

    /**
     * Update caption
     */
    public static function updateCaption(int $id, string $caption): void
    {
        Database::query('UPDATE photos SET caption = ? WHERE id = ?', [$caption, $id]);
    }

    /**
     * Update sort order for multiple photos
     */
    public static function updateSortOrder(array $order): void
    {
        $stmt = Database::getConnection()->prepare('UPDATE photos SET sort_order = ? WHERE id = ?');
        foreach ($order as $position => $id) {
            $stmt->execute([$position, $id]);
        }
    }

    /**
     * Delete a photo (file + DB record)
     */
    public static function delete(int $id): void
    {
        $photo = self::find($id);
        if (!$photo) return;

        deletePhotoFiles($photo['filename']);
        Database::query('DELETE FROM photos WHERE id = ?', [$id]);

        // If this was the primary photo, set the first remaining as primary
        if ($photo['is_primary']) {
            $next = Database::fetch(
                'SELECT id FROM photos WHERE specimen_id = ? ORDER BY sort_order ASC LIMIT 1',
                [$photo['specimen_id']]
            );
            if ($next) {
                Database::query('UPDATE photos SET is_primary = 1 WHERE id = ?', [$next['id']]);
            }
        }
    }
}
