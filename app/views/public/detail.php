<?php $pageTitle = $specimen['name']; ?>

<div class="specimen-detail">
    <a href="/" class="back-link">← Back to Gallery</a>

    <div class="detail-layout">
        <!-- Photos -->
        <div class="detail-photos">
            <?php if (!empty($specimen['photos'])): ?>
                <!-- Main image -->
                <div class="detail-main-photo" id="main-photo">
                    <?php
                    $primaryPhoto = null;
                    foreach ($specimen['photos'] as $p) {
                        if ($p['is_primary']) { $primaryPhoto = $p; break; }
                    }
                    if (!$primaryPhoto) $primaryPhoto = $specimen['photos'][0];
                    ?>
                    <img src="/uploads/originals/<?= e($primaryPhoto['filename']) ?>"
                         alt="<?= e($primaryPhoto['caption'] ?? $specimen['name']) ?>"
                         id="main-photo-img">
                    <?php if ($primaryPhoto['caption']): ?>
                        <p class="photo-caption" id="main-photo-caption"><?= e($primaryPhoto['caption']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Thumbnail strip -->
                <?php if (count($specimen['photos']) > 1): ?>
                    <div class="detail-thumbs">
                        <?php foreach ($specimen['photos'] as $photo): ?>
                            <button class="thumb-btn <?= $photo['id'] === $primaryPhoto['id'] ? 'active' : '' ?>"
                                    data-original="/uploads/originals/<?= e($photo['filename']) ?>"
                                    data-caption="<?= e($photo['caption'] ?? '') ?>"
                                    onclick="switchPhoto(this)">
                                <img src="/uploads/thumbs/<?= e($photo['filename']) ?>"
                                     alt="<?= e($photo['caption'] ?? '') ?>">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="detail-no-photo">
                    <span>💎</span>
                    <p>No photos available</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="detail-info">
            <h1><?= e($specimen['name']) ?></h1>

            <?php if (!empty($specimen['description'])): ?>
                <div class="detail-description">
                    <?= nl2br(e($specimen['description'])) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($visibleFields)): ?>
                <table class="detail-fields">
                    <tbody>
                        <?php foreach ($visibleFields as $field): ?>
                            <tr>
                                <th><?= e($field['label']) ?></th>
                                <td>
                                    <?php
                                    $val = $field['value'];
                                    if ($field['field_type'] === 'multi_select') {
                                        $decoded = json_decode($val, true);
                                        if (is_array($decoded)) {
                                            echo e(implode(', ', $decoded));
                                        } else {
                                            echo e($val);
                                        }
                                    } elseif ($field['field_type'] === 'url' && !empty($val)) {
                                        echo '<a href="' . e($val) . '" target="_blank" rel="noopener">' . e($val) . '</a>';
                                    } elseif ($field['field_type'] === 'color' && !empty($val)) {
                                        echo '<span class="color-swatch" style="background:' . e($val) . '"></span> ' . e($val);
                                    } else {
                                        echo e($val);
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function switchPhoto(btn) {
    document.getElementById('main-photo-img').src = btn.dataset.original;
    const caption = document.getElementById('main-photo-caption');
    if (caption) {
        caption.textContent = btn.dataset.caption || '';
        caption.style.display = btn.dataset.caption ? '' : 'none';
    }
    document.querySelectorAll('.thumb-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
</script>
