<?php
/**
 * Custom Field Model
 */

class CustomField
{
    /**
     * Get all active fields, ordered
     */
    public static function all(bool $activeOnly = true): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return Database::fetchAll("SELECT * FROM custom_fields $where ORDER BY sort_order ASC");
    }

    /**
     * Get only filterable fields (for public sidebar)
     */
    public static function filterable(): array
    {
        return Database::fetchAll(
            'SELECT * FROM custom_fields WHERE is_active = 1 AND is_filterable = 1 ORDER BY sort_order ASC'
        );
    }

    /**
     * Get publicly visible fields
     */
    public static function publicVisible(): array
    {
        return Database::fetchAll(
            'SELECT * FROM custom_fields WHERE is_active = 1 AND is_visible_public = 1 ORDER BY sort_order ASC'
        );
    }

    /**
     * Find by ID
     */
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM custom_fields WHERE id = ?', [$id]);
    }

    /**
     * Create a new custom field
     */
    public static function create(array $data): int
    {
        // Auto-generate field_name from label if not provided
        $fieldName = $data['field_name'] ?? self::generateFieldName($data['label']);

        // Get next sort_order
        $maxSort = Database::fetch('SELECT COALESCE(MAX(sort_order), 0) + 1 as next_sort FROM custom_fields');
        $sortOrder = $data['sort_order'] ?? $maxSort['next_sort'];

        Database::query(
            'INSERT INTO custom_fields (field_name, label, field_type, options_json, is_required, is_filterable, is_visible_public, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $fieldName,
                $data['label'],
                $data['field_type'] ?? 'text',
                isset($data['options']) ? json_encode($data['options']) : null,
                $data['is_required'] ?? 0,
                $data['is_filterable'] ?? 0,
                $data['is_visible_public'] ?? 1,
                $sortOrder,
            ]
        );

        return (int)Database::lastInsertId();
    }

    /**
     * Update a custom field
     */
    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE custom_fields SET label = ?, field_type = ?, options_json = ?,
             is_required = ?, is_filterable = ?, is_visible_public = ?, sort_order = ?
             WHERE id = ?',
            [
                $data['label'],
                $data['field_type'] ?? 'text',
                isset($data['options']) ? json_encode($data['options']) : null,
                $data['is_required'] ?? 0,
                $data['is_filterable'] ?? 0,
                $data['is_visible_public'] ?? 1,
                $data['sort_order'] ?? 0,
                $id,
            ]
        );
    }

    /**
     * Soft-delete (deactivate) a field — data is preserved
     */
    public static function deactivate(int $id): void
    {
        Database::query('UPDATE custom_fields SET is_active = 0 WHERE id = ?', [$id]);
    }

    /**
     * Reactivate a field
     */
    public static function activate(int $id): void
    {
        Database::query('UPDATE custom_fields SET is_active = 1 WHERE id = ?', [$id]);
    }

    /**
     * Update sort order for multiple fields
     */
    public static function updateSortOrder(array $order): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE custom_fields SET sort_order = ? WHERE id = ?');

        foreach ($order as $position => $id) {
            $stmt->execute([$position, $id]);
        }
    }

    /**
     * Get options as PHP array from JSON
     */
    public static function getOptions(array $field): array
    {
        if (empty($field['options_json'])) return [];
        $options = json_decode($field['options_json'], true);
        return is_array($options) ? $options : [];
    }

    /**
     * Generate a safe field_name from a label
     */
    private static function generateFieldName(string $label): string
    {
        $name = strtolower(trim($label));
        $name = preg_replace('/[^a-z0-9]+/', '_', $name);
        $name = trim($name, '_');

        // Ensure uniqueness
        $base = $name;
        $counter = 1;
        while (Database::fetch('SELECT id FROM custom_fields WHERE field_name = ?', [$name])) {
            $name = $base . '_' . $counter;
            $counter++;
        }

        return $name;
    }

    /**
     * Get distinct values for a field (for filter dropdowns)
     */
    public static function distinctValues(int $fieldId): array
    {
        $rows = Database::fetchAll(
            'SELECT DISTINCT sfv.value FROM specimen_field_values sfv
             INNER JOIN specimens s ON s.id = sfv.specimen_id AND s.is_published = 1
             WHERE sfv.field_id = ? AND sfv.value IS NOT NULL AND sfv.value != ""
             ORDER BY sfv.value ASC',
            [$fieldId]
        );

        return array_column($rows, 'value');
    }
}
