<?php $pageTitle = 'Gallery'; ?>

<div class="gallery-layout">
    <!-- Filter Sidebar -->
    <?php if (!empty($filterableFields)): ?>
    <aside class="filter-sidebar">
        <h3>Filter</h3>
        <form method="GET" action="/" id="filter-form">
            <?php foreach ($filterableFields as $field): ?>
                <?php
                $paramKey = 'filter_' . $field['id'];
                $currentVal = $_GET[$paramKey] ?? '';
                $options = !empty($field['options_json']) ? json_decode($field['options_json'], true) : [];
                ?>

                <div class="filter-group">
                    <label><?= e($field['label']) ?></label>

                    <?php if ($field['field_type'] === 'select' && !empty($options)): ?>
                        <select name="<?= $paramKey ?>" class="filter-select" onchange="this.form.submit()">
                            <option value="">All</option>
                            <?php foreach ($options as $opt): ?>
                                <option value="<?= e($opt) ?>" <?= $currentVal === $opt ? 'selected' : '' ?>>
                                    <?= e($opt) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($field['field_type'] === 'multi_select' && !empty($options)): ?>
                        <div class="filter-checkboxes">
                            <?php
                            $selectedArr = !empty($currentVal) ? explode(',', $currentVal) : [];
                            foreach ($options as $opt):
                            ?>
                                <label class="filter-checkbox-label">
                                    <input type="checkbox" value="<?= e($opt) ?>"
                                           data-param="<?= $paramKey ?>"
                                           class="multi-filter-cb"
                                           <?= in_array($opt, $selectedArr) ? 'checked' : '' ?>>
                                    <?= e($opt) ?>
                                </label>
                            <?php endforeach; ?>
                            <input type="hidden" name="<?= $paramKey ?>" value="<?= e($currentVal) ?>" class="multi-filter-hidden">
                        </div>

                    <?php else: ?>
                        <input type="text" name="<?= $paramKey ?>" value="<?= e($currentVal) ?>"
                               class="filter-input" placeholder="Filter...">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-sm btn-primary filter-btn">Apply Filters</button>
            <a href="/" class="btn btn-sm filter-btn">Clear</a>
        </form>
    </aside>
    <?php endif; ?>

    <!-- Gallery Grid -->
    <div class="gallery-content">
        <?php if ($total > 0): ?>
            <p class="results-count"><?= $total ?> specimen<?= $total !== 1 ? 's' : '' ?></p>
        <?php endif; ?>

        <?php if (empty($specimens)): ?>
            <p class="empty-state">No specimens found.</p>
        <?php else: ?>
            <div class="gallery-grid">
                <?php foreach ($specimens as $s): ?>
                    <a href="/specimen/<?= e($s['slug']) ?>" class="gallery-card">
                        <div class="gallery-card-image">
                            <?php if ($s['photo_filename']): ?>
                                <img src="/uploads/thumbs/<?= e($s['photo_filename']) ?>"
                                     alt="<?= e($s['name']) ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="gallery-placeholder">💎</div>
                            <?php endif; ?>
                        </div>
                        <div class="gallery-card-info">
                            <h3><?= e($s['name']) ?></h3>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <?= pagination($total, $perPage, $page, '/') ?>
        <?php endif; ?>
    </div>
</div>
