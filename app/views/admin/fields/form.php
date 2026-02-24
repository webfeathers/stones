<?php
$isEdit = !empty($field);
$pageTitle = $isEdit ? 'Edit Field: ' . e($field['label']) : 'Add Custom Field';
$options = $isEdit && !empty($field['options_json']) ? json_decode($field['options_json'], true) : [];
?>

<div class="section-header">
    <h2><?= $pageTitle ?></h2>
</div>

<form method="POST" action="<?= $isEdit ? "/admin/fields/{$field['id']}/edit" : '/admin/fields/create' ?>" class="field-form">
    <?= Auth::csrfField() ?>

    <div class="form-group">
        <label for="label">Label *</label>
        <input type="text" id="label" name="label" required
               value="<?= e($isEdit ? $field['label'] : '') ?>"
               class="form-input" placeholder="e.g. Mohs Hardness">
        <small class="form-hint">The display name shown on forms and detail pages.</small>
    </div>

    <div class="form-group">
        <label for="field_type">Field Type *</label>
        <select id="field_type" name="field_type" class="form-input" required>
            <?php
            $types = [
                'text'         => 'Text (single line)',
                'textarea'     => 'Text Area (multi-line)',
                'number'       => 'Number',
                'select'       => 'Dropdown (single select)',
                'multi_select' => 'Checkboxes (multi-select)',
                'date'         => 'Date',
                'url'          => 'URL',
                'color'        => 'Color Picker',
            ];
            $currentType = $isEdit ? $field['field_type'] : 'text';
            foreach ($types as $val => $label):
            ?>
                <option value="<?= $val ?>" <?= $currentType === $val ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group" id="options-group" style="<?= in_array($currentType, ['select', 'multi_select']) ? '' : 'display:none' ?>">
        <label for="options">Options (one per line)</label>
        <textarea id="options" name="options" rows="6" class="form-input"
                  placeholder="Option 1&#10;Option 2&#10;Option 3"><?= e(is_array($options) ? implode("\n", $options) : '') ?></textarea>
        <small class="form-hint">Enter each option on a separate line.</small>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_required" value="1"
                       <?= ($isEdit && $field['is_required']) ? 'checked' : '' ?>>
                Required field
            </label>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_filterable" value="1"
                       <?= ($isEdit && $field['is_filterable']) ? 'checked' : '' ?>>
                Show in gallery filters
            </label>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_visible_public" value="1"
                       <?= (!$isEdit || $field['is_visible_public']) ? 'checked' : '' ?>>
                Show on public detail page
            </label>
        </div>
    </div>

    <div class="form-group">
        <label for="sort_order">Sort Order</label>
        <input type="number" id="sort_order" name="sort_order"
               value="<?= e($isEdit ? $field['sort_order'] : '0') ?>"
               class="form-input form-input-sm">
        <small class="form-hint">Lower numbers appear first.</small>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Field</button>
        <a href="/admin/fields" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
// Show/hide options field based on type selection
document.getElementById('field_type').addEventListener('change', function() {
    const optionsGroup = document.getElementById('options-group');
    const needsOptions = ['select', 'multi_select'].includes(this.value);
    optionsGroup.style.display = needsOptions ? '' : 'none';
});
</script>
