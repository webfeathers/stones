<?php
$isEdit = !empty($specimen);
$pageTitle = $isEdit ? 'Edit: ' . e($specimen['name']) : 'Add Specimen';
?>

<div class="section-header">
    <h2><?= $pageTitle ?></h2>
    <div class="header-actions">
        <?php if ($isEdit): ?>
            <a href="/admin/specimens/<?= $specimen['id'] ?>/photos" class="btn btn-sm">Manage Photos (<?= count($specimen['photos']) ?>)</a>
            <a href="/specimen/<?= e($specimen['slug']) ?>" class="btn btn-sm" target="_blank">View ↗</a>
        <?php endif; ?>
    </div>
</div>

<form method="POST" action="<?= $isEdit ? "/admin/specimens/{$specimen['id']}/edit" : '/admin/specimens/create' ?>" class="specimen-form">
    <?= Auth::csrfField() ?>

    <div class="form-grid">
        <!-- Left Column: Core fields -->
        <div class="form-column">
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" required
                       value="<?= e($isEdit ? $specimen['name'] : '') ?>"
                       class="form-input" placeholder="e.g. Amethyst Cluster from Brazil">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"
                          class="form-input" placeholder="Describe this specimen..."><?= e($isEdit ? $specimen['description'] : '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order"
                           value="<?= e($isEdit ? $specimen['sort_order'] : '0') ?>"
                           class="form-input form-input-sm">
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_published" value="1"
                               <?= ($isEdit && $specimen['is_published']) ? 'checked' : '' ?>>
                        Published (visible to public)
                    </label>
                </div>
            </div>
        </div>

        <!-- Right Column: Custom fields -->
        <div class="form-column">
            <h3 class="form-section-title">Properties</h3>

            <?php
            // Build a lookup of current values
            $currentValues = [];
            if ($isEdit) {
                foreach ($specimen['fields'] as $f) {
                    $currentValues[$f['field_id']] = $f['value'];
                }
            }
            ?>

            <?php foreach ($fields as $field): ?>
                <?php
                $fieldId = $field['id'];
                $value = $currentValues[$fieldId] ?? '';
                $required = $field['is_required'] ? 'required' : '';
                $options = !empty($field['options_json']) ? json_decode($field['options_json'], true) : [];
                ?>

                <div class="form-group">
                    <label for="field_<?= $fieldId ?>">
                        <?= e($field['label']) ?>
                        <?= $field['is_required'] ? '*' : '' ?>
                    </label>

                    <?php if ($field['field_type'] === 'text'): ?>
                        <input type="text" id="field_<?= $fieldId ?>" name="fields[<?= $fieldId ?>]"
                               value="<?= e($value) ?>" class="form-input" <?= $required ?>>

                    <?php elseif ($field['field_type'] === 'textarea'): ?>
                        <textarea id="field_<?= $fieldId ?>" name="fields[<?= $fieldId ?>]"
                                  rows="3" class="form-input" <?= $required ?>><?= e($value) ?></textarea>

                    <?php elseif ($field['field_type'] === 'number'): ?>
                        <input type="number" step="any" id="field_<?= $fieldId ?>" name="fields[<?= $fieldId ?>]"
                               value="<?= e($value) ?>" class="form-input form-input-sm" <?= $required ?>>

                    <?php elseif ($field['field_type'] === 'date'): ?>
                        <input type="date" id="field_<?= $fieldId ?>" name="fields[<?= $fieldId ?>]"
                               value="<?= e($value) ?>" class="form-input form-input-sm" <?= $required ?>>

                    <?php elseif ($field['field_type'] === 'url'): ?>
                        <input type="url" id="field_<?= $fieldId ?>" name="fields[<?= $fieldId ?>]"
                               value="<?= e($value) ?>" class="form-input" <?= $required ?>>

                    <?php elseif ($field['field_type'] === 'color'): ?>
                        <input type="color" id="field_<?= $fieldId ?>" name="fields[<?= $fieldId ?>]"
                               value="<?= e($value ?: '#000000') ?>" class="form-input-color" <?= $required ?>>

                    <?php elseif ($field['field_type'] === 'select' && !empty($options)): ?>
                        <select id="field_<?= $fieldId ?>" name="fields[<?= $fieldId ?>]"
                                class="form-input" <?= $required ?>>
                            <option value="">— Select —</option>
                            <?php foreach ($options as $opt): ?>
                                <option value="<?= e($opt) ?>" <?= $value === $opt ? 'selected' : '' ?>>
                                    <?= e($opt) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($field['field_type'] === 'multi_select' && !empty($options)): ?>
                        <?php
                        $selectedValues = !empty($value) ? json_decode($value, true) : [];
                        if (!is_array($selectedValues)) $selectedValues = [$value];
                        ?>
                        <div class="checkbox-group">
                            <?php foreach ($options as $opt): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="fields[<?= $fieldId ?>][]"
                                           value="<?= e($opt) ?>"
                                           <?= in_array($opt, $selectedValues) ? 'checked' : '' ?>>
                                    <?= e($opt) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>
                        <input type="text" id="field_<?= $fieldId ?>" name="fields[<?= $fieldId ?>]"
                               value="<?= e($value) ?>" class="form-input" <?= $required ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Specimen</button>
        <a href="/admin/specimens" class="btn btn-secondary">Cancel</a>

        <?php if ($isEdit): ?>
            <form method="POST" action="/admin/specimens/<?= $specimen['id'] ?>/delete"
                  class="inline-form" onsubmit="return confirm('Delete this specimen and all its photos? This cannot be undone.')">
                <?= Auth::csrfField() ?>
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        <?php endif; ?>
    </div>
</form>
