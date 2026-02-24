<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Gem &amp; Mineral Collection</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
    <nav class="admin-nav">
        <div class="admin-nav-inner">
            <a href="/admin" class="admin-logo">💎 Gem Tracker</a>
            <div class="admin-nav-links">
                <a href="/admin" class="nav-link">Dashboard</a>
                <a href="/admin/specimens" class="nav-link">Specimens</a>
                <a href="/admin/fields" class="nav-link">Fields</a>
                <a href="/" class="nav-link" target="_blank">View Site ↗</a>
                <a href="/admin/logout" class="nav-link nav-logout">Logout</a>
            </div>
        </div>
    </nav>

    <main class="admin-main">
        <?php $flash = flash(); ?>
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <script src="/assets/js/admin.js"></script>
</body>
</html>
