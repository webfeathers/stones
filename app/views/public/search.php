<?php $pageTitle = 'Search' . (!empty($query) ? ': ' . $query : ''); ?>

<h2>Search Results</h2>

<?php if (!empty($query)): ?>
    <p class="results-count">
        <?= $total ?> result<?= $total !== 1 ? 's' : '' ?> for "<?= e($query) ?>"
    </p>

    <?php if (empty($specimens)): ?>
        <p class="empty-state">No specimens found matching your search. Try different keywords.</p>
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

        <?= pagination($total, $perPage, $page, '/search?q=' . urlencode($query)) ?>
    <?php endif; ?>
<?php else: ?>
    <p class="empty-state">Enter a search term to find specimens.</p>
<?php endif; ?>
