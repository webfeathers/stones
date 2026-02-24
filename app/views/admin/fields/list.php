<div class="section-header">
    <h2>Custom Fields</h2>
    <a href="/admin/fields/create" class="btn btn-primary">+ Add Field</a>
</div>

<p class="section-description">
    Define the properties you want to track for each specimen. Fields can be reordered, hidden from the public,
    or deactivated without losing data.
</p>

<?php if (empty($fields)): ?>
    <p class="empty-state">No fields defined. <a href="/admin/fields/create">Create your first field.</a></p>
<?php else: ?>
    <table class="data-table sortable-table" id="fields-table" data-reorder-url="/admin/fields/reorder">
        <thead>
            <tr>
                <th class="cell-drag"></th>
                <th>Label</th>
                <th>Type</th>
                <th>Filterable</th>
                <th>Public</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fields as $field): ?>
            <tr data-id="<?= $field['id'] ?>" class="<?= $field['is_active'] ? '' : 'row-inactive' ?>">
                <td class="cell-drag"><span class="drag-handle">⠿</span></td>
                <td>
                    <strong><?= e($field['label']) ?></strong>
                    <br><small class="text-muted"><?= e($field['field_name']) ?></small>
                </td>
                <td><span class="badge badge-blue"><?= e($field['field_type']) ?></span></td>
                <td><?= $field['is_filterable'] ? '✓' : '' ?></td>
                <td><?= $field['is_visible_public'] ? '✓' : '' ?></td>
                <td>
                    <span class="badge <?= $field['is_active'] ? 'badge-green' : 'badge-gray' ?>">
                        <?= $field['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </td>
                <td class="cell-actions">
                    <a href="/admin/fields/<?= $field['id'] ?>/edit" class="btn btn-xs">Edit</a>
                    <form method="POST" action="/admin/fields/<?= $field['id'] ?>/toggle" class="inline-form">
                        <button type="submit" class="btn btn-xs <?= $field['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                            <?= $field['is_active'] ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
