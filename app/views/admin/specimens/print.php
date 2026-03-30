<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Specimen Collection</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 1rem;
            color: #1a1d26;
        }
        h1 {
            font-size: 1.2rem;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #333;
        }
        .print-bar {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .print-bar button, .print-bar a {
            padding: 0.4rem 1rem;
            font-size: 0.85rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: #f0f0f2;
            color: #333;
            cursor: pointer;
            text-decoration: none;
            font-family: inherit;
        }
        .print-bar button:hover, .print-bar a:hover { background: #e0e0e4; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            gap: 0.5rem;
        }
        .item {
            text-align: center;
        }
        .item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
            display: block;
            margin: 0 auto;
        }
        .no-img {
            width: 80px;
            height: 80px;
            border-radius: 4px;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            background: #f5f5f5;
            font-size: 1.5rem;
        }
        .item-name {
            font-size: 0.6rem;
            margin-top: 0.2rem;
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        @media print {
            .print-bar { display: none; }
            body { padding: 0; }
            .grid { gap: 0.3rem; }
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <button onclick="window.print()">Print</button>
        <a href="/admin/specimens">Back to List</a>
    </div>
    <h1>Specimen Collection (<?= count($result['items']) ?>)</h1>
    <div class="grid">
        <?php foreach ($result['items'] as $s): ?>
            <div class="item">
                <?php if ($s['photo_filename']): ?>
                    <img src="/uploads/thumbs/<?= e($s['photo_filename']) ?>" alt="">
                <?php else: ?>
                    <div class="no-img">💎</div>
                <?php endif; ?>
                <div class="item-name"><?= e($s['name']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
