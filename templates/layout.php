<?php
$cssVersion = (int) (@filemtime(BASE_PATH . '/public/assets/css/style.css') ?: time());
$jsVersion = (int) (@filemtime(BASE_PATH . '/public/assets/js/app.js') ?: time());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Home') ?> — <?= htmlspecialchars($siteTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= $cssVersion ?>">
</head>
<body>

<!-- Animated background particles -->
<div class="particles" id="particles"></div>

<!-- Navigation -->
<nav class="navbar">
    <a href="/" class="navbar-brand"><?= htmlspecialchars($siteTitle) ?></a>
    <div class="navbar-links">
        <?php if ($user): ?>
            <span class="navbar-user"><?= htmlspecialchars($user['email']) ?></span>
            <?php if ($hasSubscription): ?>
                <span class="badge badge-premium">Premium</span>
            <?php endif; ?>
            <a href="/logout" class="btn btn-sm btn-outline">Logout</a>
        <?php else: ?>
            <a href="/login" class="btn btn-sm btn-outline">Login</a>
            <a href="/register" class="btn btn-sm btn-primary">Sign Up</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Flash Messages -->
<?php $flash = getFlash(); ?>
<?php if ($flash): ?>
    <div class="flash flash-<?= $flash['type'] ?>">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Main Content -->
<main class="container">
    <?php require $contentTemplate; ?>
</main>

<!-- Footer -->
<footer class="footer">
    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteTitle) ?>. All rights reserved.</p>
</footer>

<script src="/assets/js/app.js?v=<?= $jsVersion ?>"></script>
</body>
</html>
