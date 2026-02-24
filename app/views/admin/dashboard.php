<h2>Dashboard</h2>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-number"><?= $stats['total_specimens'] ?></span>
        <span class="stat-label">Total Specimens</span>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?= $stats['published_specimens'] ?></span>
        <span class="stat-label">Published</span>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?= $stats['total_photos'] ?></span>
        <span class="stat-label">Photos</span>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?= $stats['total_fields'] ?></span>
        <span class="stat-label">Active Fields</span>
    </div>
</div>

<div class="section">
    <div class="section-header">
        <h3>Recently Updated</h3>
        <a href="/admin/specimens/create" class="btn btn-primary btn-sm">+ Add Specimen</a>
    </div>

    <?php if (empty($recent)): ?>
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
                <?php foreach ($recent as $s): ?>
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
                    <td>
                        <a href="/admin/specimens/<?= $s['id'] ?>/edit" class="btn btn-sm">Edit</a>
                        <a href="/admin/specimens/<?= $s['id'] ?>/photos" class="btn btn-sm">Photos</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
