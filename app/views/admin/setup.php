<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup — Gem Tracker</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h1>💎 Gem Tracker Setup</h1>
        <p class="login-subtitle">Create your admin account</p>

        <?php $flash = flash(); ?>
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin/setup">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus class="form-input">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8" class="form-input">
                <small class="form-hint">At least 8 characters</small>
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" required class="form-input">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create Admin Account</button>
        </form>
    </div>
</body>
</html>
