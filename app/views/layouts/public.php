<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?>Gem &amp; Mineral Collection</title>
    <meta name="description" content="<?= e($pageDescription ?? 'A curated collection of gems, minerals, and geological specimens.') ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <a href="/" class="site-logo">
                <h1>💎 Gem &amp; Mineral Collection</h1>
            </a>
            <nav class="site-nav">
                <a href="/" class="nav-link">Gallery</a>
                <form action="/search" method="GET" class="search-form">
                    <input type="search" name="q" placeholder="Search specimens..."
                           value="<?= e($_GET['q'] ?? '') ?>" class="search-input">
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="site-main">
        <div class="container">
            <?= $content ?>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Gem &amp; Mineral Collection &nbsp;·&nbsp; <a href="/admin">Admin</a></p>
        </div>
    </footer>

    <script src="/assets/js/public.js"></script>
</body>
</html>
