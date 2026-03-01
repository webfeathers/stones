<?php
$isEdit = !empty($specimen);
$pageTitle = $isEdit ? 'Edit: ' . e($specimen['name']) : 'Add Specimen';

// Build a lookup of current values
$currentValues = [];
if ($isEdit) {
    foreach ($specimen['fields'] as $f) {
        $currentValues[$f['field_id']] = $f['value'];
    }
}

// Group fields into sections by field_name
$sections = [
    'basics' => [
        'label' => '🪨 Basics',
        'open' => true,
        'fields' => ['type', 'display_options', 'specimen_form', 'location', 'identifier_key'],
    ],
    'physical' => [
        'label' => '📐 Size & Weight',
        'open' => false,
        'fields' => ['height', 'width', 'depth', 'dimension_unit', 'weight', 'weight_unit'],
    ],
    'properties' => [
        'label' => '🔬 Properties',
        'open' => false,
        'fields' => ['minerals_in_specimen', 'safe_to_handle', 'radioactive', 'pseudomorph', 'epimorph', 'partial', 'irradiated', 'heated', 'repaired'],
    ],
    'fluorescence' => [
        'label' => '💡 Fluorescence',
        'open' => false,
        'fields' => ['fluoresces', 'fluorescent_long_wave', 'fluorescent_mid_wave', 'fluorescent_short_wave'],
    ],
    'meteorite' => [
        'label' => '☄️ Meteorite Info',
        'open' => false,
        'fields' => ['meteorite_type', 'meteorite_name'],
    ],
    'reference' => [
        'label' => '🔗 Reference',
        'open' => false,
        'fields' => ['mindat_mineral_id', 'mindat_mineral_link', 'description_field'],
    ],
    'provenance' => [
        'label' => '📦 Provenance & Value',
        'open' => false,
        'fields' => ['collected_by', 'date_collected', 'acquisition_notes', 'added_to_collection_date', 'quality', 'quality_notes', 'amount_paid', 'perceived_value'],
    ],
];

// Build a lookup of fields by field_name
$fieldsByName = [];
foreach ($fields as $field) {
    $fieldsByName[$field['field_name']] = $field;
}
?>

<div class="section-header">
    <h2><?= $pageTitle ?></h2>
    <div class="header-actions">
        <?php if ($isEdit): ?>
            <a href="/admin/specimens/<?= $specimen['id'] ?>/photos" class="btn btn-primary btn-sm">📷 Manage Photos (<?= count($specimen['photos']) ?>)</a>
            <a href="/specimen/<?= e($specimen['slug']) ?>" class="btn btn-sm" target="_blank">View ↗</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$isEdit): ?>
    <p class="section-description">
        Only the <strong>Name</strong> is required. Fill in as much or as little as you want — you can always come back and add more later.
    </p>
<?php endif; ?>

<form method="POST" action="<?= $isEdit ? "/admin/specimens/{$specimen['id']}/edit" : '/admin/specimens/create' ?>" class="specimen-form" enctype="multipart/form-data">
    <?= Auth::csrfField() ?>

    <!-- Core fields — always visible -->
    <div class="form-card">
        <div class="form-card-body">
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" required
                       value="<?= e($isEdit ? $specimen['name'] : '') ?>"
                       class="form-input" placeholder="e.g. Amethyst Cluster from Brazil">
            </div>

            <?php
            // Render AKA field right under Name
            if (isset($fieldsByName['aka'])) {
                $akaField = $fieldsByName['aka'];
                $akaValue = $currentValues[$akaField['id']] ?? '';
            ?>
            <div class="form-group">
                <label for="field_<?= $akaField['id'] ?>">AKA</label>
                <input type="text" id="field_<?= $akaField['id'] ?>" name="fields[<?= $akaField['id'] ?>]"
                       value="<?= e($akaValue) ?>" class="form-input"
                       placeholder="Other names for this specimen">
            </div>
            <?php } ?>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"
                          class="form-input" placeholder="A short description of this piece..."><?= e($isEdit ? $specimen['description'] : '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_published" value="1"
                               <?= (!$isEdit || $specimen['is_published']) ? 'checked' : '' ?>>
                        Published (visible to public)
                    </label>
                </div>
                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order"
                           value="<?= e($isEdit ? $specimen['sort_order'] : '0') ?>"
                           class="form-input form-input-sm">
                </div>
            </div>
        </div>
    </div>

    <!-- Collapsible field sections -->
    <?php foreach ($sections as $sectionKey => $section): ?>
        <?php
        // Check if any fields in this section have values (to auto-open)
        $hasValues = false;
        foreach ($section['fields'] as $fname) {
            if (isset($fieldsByName[$fname])) {
                $fid = $fieldsByName[$fname]['id'];
                if (!empty($currentValues[$fid] ?? '')) {
                    $hasValues = true;
                    break;
                }
            }
        }
        $isOpen = $section['open'] || $hasValues;
        ?>
        <div class="form-card collapsible <?= $isOpen ? 'open' : '' ?>">
            <div class="form-card-header" onclick="this.parentElement.classList.toggle('open')">
                <h3><?= $section['label'] ?></h3>
                <span class="collapse-icon">›</span>
            </div>
            <div class="form-card-body">
                <div class="field-grid">
                    <?php foreach ($section['fields'] as $fieldName): ?>
                        <?php
                        if (!isset($fieldsByName[$fieldName])) continue;
                        $field = $fieldsByName[$fieldName];
                        $fieldId = $field['id'];
                        $value = $currentValues[$fieldId] ?? '';
                        $required = $field['is_required'] ? 'required' : '';
                        $options = !empty($field['options_json']) ? json_decode($field['options_json'], true) : [];

                        // Apply defaults for new specimens
                        if (!$isEdit && $value === '') {
                            $value = match($fieldName) {
                                'type' => 'Mineral',
                                'display_options' => 'Collection Case',
                                default => '',
                            };
                        }

                        // Determine if this is a compact field
                        $isCompact = in_array($field['field_type'], ['number', 'date', 'select', 'color']);
                        ?>

                        <div class="form-group <?= $isCompact ? 'form-group-compact' : 'form-group-full' ?>">
                            <label for="field_<?= $fieldId ?>">
                                <?= e($field['label']) ?>
                                <?= $field['is_required'] ? ' *' : '' ?>
                            </label>

                            <?php if ($field['field_type'] === 'text'): ?>
                                <input type="text" id="field_<?= $fieldId ?>" name="fields[<?= $fieldId ?>]"
                                       value="<?= e($value) ?>" class="form-input" <?= $required ?>
                                       placeholder="<?= e(placeholder($fieldName)) ?>">

                            <?php elseif ($field['field_type'] === 'textarea'): ?>
                                <textarea id="field_<?= $fieldId ?>" name="fields[<?= $fieldId ?>]"
                                          rows="2" class="form-input" <?= $required ?>><?= e($value) ?></textarea>

                            <?php elseif ($field['field_type'] === 'number'): ?>
                                <input type="number" step="any" id="field_<?= $fieldId ?>" name="fields[<?= $fieldId ?>]"
                                       value="<?= e($value) ?>" class="form-input form-input-sm" <?= $required ?>>

                            <?php elseif ($field['field_type'] === 'date'): ?>
                                <input type="date" id="field_<?= $fieldId ?>" name="fields[<?= $fieldId ?>]"
                                       value="<?= e($value) ?>" class="form-input form-input-sm" <?= $required ?>>

                            <?php elseif ($field['field_type'] === 'url'): ?>
                                <input type="url" id="field_<?= $fieldId ?>" name="fields[<?= $fieldId ?>]"
                                       value="<?= e($value) ?>" class="form-input" <?= $required ?>
                                       placeholder="https://...">

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
        </div>
    <?php endforeach; ?>

    <!-- Photos -->
    <?php if (!$isEdit): ?>
        <div class="form-card collapsible open">
            <div class="form-card-header" onclick="this.parentElement.classList.toggle('open')">
                <h3>📷 Photos</h3>
                <span class="collapse-icon">›</span>
            </div>
            <div class="form-card-body">
                <div class="upload-area upload-area-inline">
                    <label for="photo-upload" class="upload-label">
                        <span class="upload-icon">📷</span>
                        <span>Click to choose photos or drag & drop</span>
                        <small>JPG, PNG, WebP, GIF — max 10MB each</small>
                    </label>
                    <input type="file" id="photo-upload" name="photos[]" multiple
                           accept="image/jpeg,image/png,image/webp,image/gif"
                           class="upload-input">
                    <div id="photo-preview" class="photo-preview-grid"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Specimen</button>
        <?php if (!$isEdit): ?>
            <button type="submit" name="add_another" value="1" class="btn btn-secondary">Save & Add Another</button>
        <?php endif; ?>
        <a href="/admin/specimens" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<?php if ($isEdit): ?>
    <form method="POST" action="/admin/specimens/<?= $specimen['id'] ?>/delete"
          class="inline-form delete-form" onsubmit="return confirm('Delete this specimen and all its photos? This cannot be undone.')">
        <?= Auth::csrfField() ?>
        <div class="form-actions form-actions-danger">
            <button type="submit" class="btn btn-danger">Delete Specimen</button>
        </div>
    </form>
<?php endif; ?>

<?php
// Helper for placeholder text
function placeholder(string $fieldName): string {
    return match($fieldName) {
        'identifier_key' => 'Your ID or catalog number',
        'location' => 'e.g. Tucson, AZ or Minas Gerais, Brazil',
        'aka' => 'Other names for this specimen',
        'minerals_in_specimen' => 'e.g. Quartz, Feldspar, Mica',
        'meteorite_type' => 'e.g. Iron, Stony-iron, Chondrite',
        'meteorite_name' => 'e.g. Campo del Cielo',
        'mindat_mineral_id' => 'Mindat.org mineral ID number',
        'collected_by' => 'Who found/collected it',
        'acquisition_notes' => 'Where you got it, dealer, show, etc.',
        'quality' => 'e.g. Museum, A+, Display, Study',
        'quality_notes' => 'Notes about condition or quality',
        'amount_paid' => 'e.g. $25',
        'perceived_value' => 'e.g. $100',
        default => '',
    };
}
?>
