<div class="section-header">
    <h2>Photos: <?= e($specimen['name']) ?></h2>
    <a href="/admin/specimens/<?= $specimen['id'] ?>/edit" class="btn btn-sm">← Back to Edit</a>
</div>

<!-- Upload form -->
<div class="upload-area">
    <form method="POST" action="/admin/specimens/<?= $specimen['id'] ?>/photos/upload"
          enctype="multipart/form-data" class="upload-form">
        <label for="photo-upload" class="upload-label">
            <span class="upload-icon">📷</span>
            <span>Click to choose photos or drag & drop</span>
            <small>JPG, PNG, WebP, GIF — max 10MB each</small>
        </label>
        <input type="file" id="photo-upload" name="photos[]" multiple
               accept="image/jpeg,image/png,image/webp,image/gif"
               class="upload-input">
        <button type="submit" class="btn btn-primary upload-btn" style="display:none">Upload Photos</button>
    </form>
</div>

<!-- Existing photos -->
<?php if (empty($specimen['photos'])): ?>
    <p class="empty-state">No photos yet. Upload some above!</p>
<?php else: ?>
    <div class="photo-grid sortable" id="photo-grid" data-reorder-url="/admin/photos/reorder">
        <?php foreach ($specimen['photos'] as $photo): ?>
            <div class="photo-card" data-id="<?= $photo['id'] ?>">
                <div class="photo-card-image">
                    <img src="/uploads/thumbs/<?= e($photo['filename']) ?>"
                         alt="<?= e($photo['caption'] ?? '') ?>">
                    <?php if ($photo['is_primary']): ?>
                        <span class="photo-badge">Primary</span>
                    <?php endif; ?>
                    <span class="drag-handle">⠿</span>
                </div>

                <div class="photo-card-actions">
                    <?php if (!$photo['is_primary']): ?>
                        <form method="POST" action="/admin/photos/<?= $photo['id'] ?>/primary" class="inline-form">
                            <button type="submit" class="btn btn-xs">Set Primary</button>
                        </form>
                    <?php endif; ?>

                    <form method="POST" action="/admin/photos/<?= $photo['id'] ?>/caption" class="inline-form caption-form">
                        <input type="text" name="caption" value="<?= e($photo['caption'] ?? '') ?>"
                               placeholder="Caption..." class="form-input form-input-xs">
                        <button type="submit" class="btn btn-xs">Save</button>
                    </form>

                    <form method="POST" action="/admin/photos/<?= $photo['id'] ?>/delete" class="inline-form"
                          onsubmit="return confirm('Delete this photo?')">
                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                    </form>
                </div>

                <small class="photo-meta">
                    <?= e($photo['original_name'] ?? $photo['filename']) ?>
                    <?php if ($photo['file_size']): ?>
                        · <?= formatFileSize($photo['file_size']) ?>
                    <?php endif; ?>
                </small>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
