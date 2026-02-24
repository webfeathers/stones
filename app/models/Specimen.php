<?php
/**
 * Specimen Model
 */

class Specimen
{
    /**
     * Get paginated list of specimens (with primary photo)
     */
    public static function paginate(int $page = 1, int $perPage = 24, bool $publishedOnly = true): array
    {
        $offset = ($page - 1) * $perPage;

        $where = $publishedOnly ? 'WHERE s.is_published = 1' : '';

        $sql = "SELECT s.*, p.filename AS photo_filename
                FROM specimens s
                LEFT JOIN photos p ON p.specimen_id = s.id AND p.is_primary = 1
                $where
                ORDER BY s.sort_order ASC, s.name ASC
                LIMIT ? OFFSET ?";

        $items = Database::fetchAll($sql, [$perPage, $offset]);

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM specimens s $where";
        $total = Database::fetch($countSql)['total'];

        return ['items' => $items, 'total' => (int)$total];
    }

    /**
     * Find by ID with all field values and photos
     */
    public static function find(int $id): ?array
    {
        $specimen = Database::fetch('SELECT * FROM specimens WHERE id = ?', [$id]);
        if (!$specimen) return null;

        $specimen['fields'] = self::getFieldValues($id);
        $specimen['photos'] = self::getPhotos($id);

        return $specimen;
    }

    /**
     * Find by slug with all field values and photos
     */
    public static function findBySlug(string $slug, bool $publishedOnly = true): ?array
    {
        $sql = 'SELECT * FROM specimens WHERE slug = ?';
        $params = [$slug];

        if ($publishedOnly) {
            $sql .= ' AND is_published = 1';
        }

        $specimen = Database::fetch($sql, $params);
        if (!$specimen) return null;

        $specimen['fields'] = self::getFieldValues($specimen['id']);
        $specimen['photos'] = self::getPhotos($specimen['id']);

        return $specimen;
    }

    /**
     * Get all custom field values for a specimen
     */
    public static function getFieldValues(int $specimenId): array
    {
        return Database::fetchAll(
            'SELECT cf.id as field_id, cf.field_name, cf.label, cf.field_type,
                    cf.options_json, cf.is_visible_public, cf.is_filterable,
                    sfv.value
             FROM custom_fields cf
             LEFT JOIN specimen_field_values sfv ON sfv.field_id = cf.id AND sfv.specimen_id = ?
             WHERE cf.is_active = 1
             ORDER BY cf.sort_order ASC',
            [$specimenId]
        );
    }

    /**
     * Get all photos for a specimen
     */
    public static function getPhotos(int $specimenId): array
    {
        return Database::fetchAll(
            'SELECT * FROM photos WHERE specimen_id = ? ORDER BY sort_order ASC, id ASC',
            [$specimenId]
        );
    }

    /**
     * Create a new specimen
     */
    public static function create(array $data): int
    {
        $slug = uniqueSlug(slugify($data['name']));

        Database::query(
            'INSERT INTO specimens (name, slug, description, is_published, sort_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $data['name'],
                $slug,
                $data['description'] ?? '',
                $data['is_published'] ?? 0,
                $data['sort_order'] ?? 0,
            ]
        );

        return (int)Database::lastInsertId();
    }

    /**
     * Update a specimen
     */
    public static function update(int $id, array $data): void
    {
        $slug = uniqueSlug(slugify($data['name']), $id);

        Database::query(
            'UPDATE specimens SET name = ?, slug = ?, description = ?, is_published = ?, sort_order = ?, updated_at = NOW()
             WHERE id = ?',
            [
                $data['name'],
                $slug,
                $data['description'] ?? '',
                $data['is_published'] ?? 0,
                $data['sort_order'] ?? 0,
                $id,
            ]
        );
    }

    /**
     * Save custom field values for a specimen
     */
    public static function saveFieldValues(int $specimenId, array $fieldValues): void
    {
        foreach ($fieldValues as $fieldId => $value) {
            // Upsert: insert or update
            Database::query(
                'INSERT INTO specimen_field_values (specimen_id, field_id, value)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE value = VALUES(value)',
                [$specimenId, $fieldId, $value]
            );
        }
    }

    /**
     * Delete a specimen and all related data
     */
    public static function delete(int $id): void
    {
        // Get photos to delete files
        $photos = self::getPhotos($id);
        foreach ($photos as $photo) {
            deletePhotoFiles($photo['filename']);
        }

        // CASCADE will handle field_values and photos rows
        Database::query('DELETE FROM specimens WHERE id = ?', [$id]);
    }

    /**
     * Count total specimens
     */
    public static function count(bool $publishedOnly = false): int
    {
        $where = $publishedOnly ? 'WHERE is_published = 1' : '';
        return (int)Database::fetch("SELECT COUNT(*) as total FROM specimens $where")['total'];
    }

    /**
     * Search specimens by name and text field values
     */
    public static function search(string $query, int $page = 1, int $perPage = 24): array
    {
        $offset = ($page - 1) * $perPage;
        $like = '%' . $query . '%';

        $sql = "SELECT DISTINCT s.*, p.filename AS photo_filename
                FROM specimens s
                LEFT JOIN photos p ON p.specimen_id = s.id AND p.is_primary = 1
                LEFT JOIN specimen_field_values sfv ON sfv.specimen_id = s.id
                WHERE s.is_published = 1
                  AND (s.name LIKE ? OR s.description LIKE ? OR sfv.value LIKE ?)
                ORDER BY s.name ASC
                LIMIT ? OFFSET ?";

        $items = Database::fetchAll($sql, [$like, $like, $like, $perPage, $offset]);

        $countSql = "SELECT COUNT(DISTINCT s.id) as total
                     FROM specimens s
                     LEFT JOIN specimen_field_values sfv ON sfv.specimen_id = s.id
                     WHERE s.is_published = 1
                       AND (s.name LIKE ? OR s.description LIKE ? OR sfv.value LIKE ?)";

        $total = Database::fetch($countSql, [$like, $like, $like])['total'];

        return ['items' => $items, 'total' => (int)$total];
    }

    /**
     * Filter specimens by custom field values
     */
    public static function filter(array $filters, int $page = 1, int $perPage = 24): array
    {
        $offset = ($page - 1) * $perPage;
        $joins = [];
        $conditions = ['s.is_published = 1'];
        $params = [];

        $i = 0;
        foreach ($filters as $fieldId => $value) {
            if (empty($value)) continue;
            $alias = "fv{$i}";
            $joins[] = "INNER JOIN specimen_field_values {$alias} ON {$alias}.specimen_id = s.id AND {$alias}.field_id = ?";
            $params[] = $fieldId;

            if (is_array($value)) {
                // Multi-select: match any
                $placeholders = implode(',', array_fill(0, count($value), '?'));
                $conditions[] = "{$alias}.value IN ($placeholders)";
                $params = array_merge($params, $value);
            } else {
                $conditions[] = "{$alias}.value = ?";
                $params[] = $value;
            }
            $i++;
        }

        $joinSql = implode(' ', $joins);
        $whereSql = implode(' AND ', $conditions);

        $sql = "SELECT DISTINCT s.*, p.filename AS photo_filename
                FROM specimens s
                LEFT JOIN photos p ON p.specimen_id = s.id AND p.is_primary = 1
                $joinSql
                WHERE $whereSql
                ORDER BY s.name ASC
                LIMIT ? OFFSET ?";

        $params[] = $perPage;
        $params[] = $offset;

        $items = Database::fetchAll($sql, $params);

        // Rebuild params for count (without limit/offset)
        array_pop($params);
        array_pop($params);

        $countSql = "SELECT COUNT(DISTINCT s.id) as total FROM specimens s $joinSql WHERE $whereSql";
        $total = Database::fetch($countSql, $params)['total'];

        return ['items' => $items, 'total' => (int)$total];
    }
}
