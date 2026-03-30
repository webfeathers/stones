<?php
    $paginationParams = $search !== '' ? ['q' => $search] : [];
    $paginationHtml = pagination($total, $perPage, $page, '/admin/specimens', $paginationParams);
?>

<div class="section-header">
    <h2>Specimens (<?= $total ?>)</h2>
    <div class="header-actions">
        <form method="GET" action="/admin/specimens" class="admin-search-form">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search specimens..." class="form-input form-input-search">
            <button type="submit" class="btn btn-sm">Search</button>
            <?php if ($search !== ''): ?>
                <a href="/admin/specimens" class="btn btn-sm">Clear</a>
            <?php endif; ?>
        </form>
        <a href="/admin/specimens/create" class="btn btn-primary">+ Add Specimen</a>
    </div>
</div>

<?php if ($search !== ''): ?>
    <p class="search-result-info"><?= $total ?> result<?= $total !== 1 ? 's' : '' ?> for "<?= e($search) ?>"</p>
<?php endif; ?>

<?php if (empty($specimens)): ?>
    <p class="empty-state">
        <?php if ($search !== ''): ?>
            No specimens match your search.
        <?php else: ?>
            No specimens yet. <a href="/admin/specimens/create">Add your first one!</a>
        <?php endif; ?>
    </p>
<?php else: ?>
    <?= $paginationHtml ?>

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

    <?= $paginationHtml ?>
<?php endif; ?>
