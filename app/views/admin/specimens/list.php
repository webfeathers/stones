<div class="section-header">
    <h2>Specimens (<?= $total ?>)</h2>
    <a href="/admin/specimens/create" class="btn btn-primary">+ Add Specimen</a>
</div>

<?php if (empty($specimens)): ?>
    <p class="empty-state">No specimens yet. <a href="/admin/specimens/create">Add your first one!</a></p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Photo</th>
                <th>Name</th>
                <th>Status</th>
                <th>Updated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($specimens as $s): ?>
            <tr>
                <td class="cell-photo">
                    <?php if ($s['photo_filename']): ?>
                        <img src="/uploads/thumbs/<?= e($s['photo_filename']) ?>" alt="" class="table-thumb">
                    <?php else: ?>
                        <span class="no-photo">📷</span>
                    <?php endif; ?>
                </td>
                <td><a href="/admin/specimens/<?= $s['id'] ?>/edit"><?= e($s['name']) ?></a></td>
                <td>
                    <span class="badge <?= $s['is_published'] ? 'badge-green' : 'badge-gray' ?>">
                        <?= $s['is_published'] ? 'Published' : 'Draft' ?>
                    </span>
                </td>
                <td class="cell-date"><?= date('M j, Y', strtotime($s['updated_at'])) ?></td>
                <td class="cell-actions">
                    <a href="/admin/specimens/<?= $s['id'] ?>/edit" class="btn btn-sm">Edit</a>
                    <a href="/admin/specimens/<?= $s['id'] ?>/photos" class="btn btn-sm">Photos</a>
                    <a href="/specimen/<?= e($s['slug']) ?>" class="btn btn-sm" target="_blank">View ↗</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?= pagination($total, $perPage, $page, '/admin/specimens') ?>
<?php endif; ?>
