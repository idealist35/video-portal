<?php
$cssVersion = (int) (@filemtime(BASE_PATH . '/public/assets/css/streaming.css') ?: time());
$rtlVersion = (int) (@filemtime(BASE_PATH . '/public/assets/css/rtl.css') ?: time());
$jsVersion = (int) (@filemtime(BASE_PATH . '/public/assets/js/streaming.js') ?: time());
$brandVersion = (int) (@filemtime(BASE_PATH . '/public/assets/images/brand/hikaya-watch-mark.svg') ?: time());
$bodyClass = trim((string) ($bodyClass ?? ''));
$currentPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$isHomePage = $currentPath === '/';
$isSeriesPage = str_starts_with($currentPath, '/world/') || str_starts_with($currentPath, '/story/') || str_starts_with($currentPath, '/watch/');
$activeWorldSlug = (string) ($world['slug'] ?? $video['world_slug'] ?? '');
$isBarikPage = $activeWorldSlug === 'oasis';
$isMiraPage = $activeWorldSlug === 'star-workshop';
$isPushPage = $activeWorldSlug === 'push-questions';
$isWheelsPage = $activeWorldSlug === 'city-on-wheels';
$isRobotsPage = $activeWorldSlug === 'robot-family';
$isCastPage = $currentPath === '/characters';
$brandName = t('brand.name');
$locale = currentLocale();
$locales = availableLocales();
$textDirection = localeDirection();
$bodyClass = trim($bodyClass . ' locale-' . $locale);

// Strings the browser has to render after load; kept in one payload so
// streaming.js never carries English of its own.
$scriptStrings = [
    'progress'         => t('js.progress', ['watched' => '{watched}', 'total' => '{total}']),
    'continueWith'     => t('js.continue_with', ['title' => '{title}']),
    'startWatching'    => t('js.start_watching'),
    'replayAny'        => t('js.replay_any'),
    'openNavigation'   => t('js.open_navigation'),
    'closeNavigation'  => t('js.close_navigation'),
    'viewProfile'      => t('js.view_profile'),
    'closeProfile'     => t('js.close_profile'),
    'playVideo'        => t('js.play_video'),
    'pauseVideo'       => t('js.pause_video'),
    'pressPlay'        => t('js.press_play'),
    'allReadyReplay'   => t('js.all_ready_replay'),
    'episodeWatched'   => t('js.episode_watched'),
    'markedWatched'    => t('js.marked_watched', ['title' => '{title}']),
    'markedGeneric'    => t('js.marked_generic'),
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locales[$locale]['html']) ?>" dir="<?= htmlspecialchars($textDirection) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#07090d">
    <meta name="color-scheme" content="dark">
    <meta name="description" content="<?= te('meta.description') ?>">
    <title><?= htmlspecialchars($pageTitle ?? t('common.home')) ?> — <?= htmlspecialchars($brandName) ?></title>
    <?php foreach ($locales as $code => $localeMeta): ?>
        <link rel="alternate" hreflang="<?= htmlspecialchars($localeMeta['html']) ?>" href="<?= htmlspecialchars(rtrim(SITE_URL, '/') . localeSwitchUrl($code)) ?>">
    <?php endforeach; ?>
    <link rel="icon" type="image/svg+xml" href="/assets/images/brand/hikaya-watch-mark.svg?v=<?= $brandVersion ?>">
    <link rel="apple-touch-icon" href="/assets/images/brand/hikaya-watch-mark.svg?v=<?= $brandVersion ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if (isRtl()): ?>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php else: ?>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/streaming.css?v=<?= $cssVersion ?>">
    <?php if (isRtl()): ?>
        <link rel="stylesheet" href="/assets/css/rtl.css?v=<?= $rtlVersion ?>">
    <?php endif; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">
<a class="skip-link" href="#main-content"><?= te('common.skip_to_content') ?></a>

<div class="app-backdrop" aria-hidden="true">
    <span class="app-backdrop__glow app-backdrop__glow--one"></span>
    <span class="app-backdrop__glow app-backdrop__glow--two"></span>
    <span class="app-backdrop__grain"></span>
</div>

<header class="site-header" data-site-header>
    <div class="site-header__inner">
        <a href="/" class="brand" aria-label="<?= te('brand.home_aria') ?>">
            <img src="/assets/images/brand/hikaya-watch-mark.svg?v=<?= $brandVersion ?>" alt="" width="44" height="44">
            <span class="brand__wordmark"><strong><?= te('brand.wordmark_primary') ?></strong><em><?= te('brand.wordmark_secondary') ?></em></span>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav" data-nav-toggle>
            <span></span><span></span><span></span><span class="sr-only"><?= te('nav.open') ?></span>
        </button>

        <div class="header-panel" id="main-nav" data-nav-panel>
            <nav class="site-nav" aria-label="<?= te('nav.aria') ?>">
                <a href="/"<?= $isHomePage ? ' class="is-active" aria-current="page"' : '' ?>><?= te('nav.home') ?></a>
                <a href="/world/oasis"<?= $isBarikPage ? ' class="is-active" aria-current="page"' : '' ?>><?= te('nav.barik') ?></a>
                <a href="/world/star-workshop"<?= $isMiraPage ? ' class="is-active" aria-current="page"' : '' ?>><?= te('nav.mira') ?></a>
                <a href="/world/push-questions"<?= $isPushPage ? ' class="is-active" aria-current="page"' : '' ?>><?= te('nav.push') ?></a>
                <a href="/world/city-on-wheels"<?= $isWheelsPage ? ' class="is-active" aria-current="page"' : '' ?>><?= te('nav.wheels') ?></a>
                <a href="/world/robot-family"<?= $isRobotsPage ? ' class="is-active" aria-current="page"' : '' ?>><?= te('nav.robots') ?></a>
                <a href="/characters"<?= $isCastPage ? ' class="is-active" aria-current="page"' : '' ?>><?= te('nav.cast') ?></a>
            </nav>

            <div class="header-actions">
                <div class="language-switch" role="group" aria-label="<?= te('header.language_aria') ?>">
                    <svg class="language-switch__globe" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.7"/>
                        <path d="M3 12h18M12 3c2.5 2.6 2.5 15.4 0 18-2.5-2.6-2.5-15.4 0-18Z" fill="none" stroke="currentColor" stroke-width="1.7"/>
                    </svg>
                    <?php foreach ($locales as $code => $localeMeta): ?>
                        <a class="language-switch__option<?= $code === $locale ? ' is-active' : '' ?>"
                           href="<?= htmlspecialchars(localeSwitchUrl($code)) ?>"
                           lang="<?= htmlspecialchars($localeMeta['html']) ?>"
                           hreflang="<?= htmlspecialchars($localeMeta['html']) ?>"
                           <?= $code === $locale ? 'aria-current="true"' : '' ?>>
                            <?= htmlspecialchars($localeMeta['short']) ?>
                            <span class="sr-only"><?= htmlspecialchars($localeMeta['native']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <a class="language-chip" href="/world/oasis#episodes" aria-label="<?= te('header.audio_chip_aria') ?>">
                    <span class="language-chip__pulse"></span>
                    <?= te('header.audio_chip') ?>
                </a>
                <?php if ($user): ?>
                    <span class="account-name" title="<?= htmlspecialchars($user['email']) ?>"><?= htmlspecialchars($user['email']) ?></span>
                    <?php if ($hasSubscription): ?><span class="access-chip"><?= te('header.premium') ?></span><?php endif; ?>
                    <a href="/logout" class="button button--small button--quiet"><?= te('header.log_out') ?></a>
                <?php else: ?>
                    <a href="/login" class="button button--small button--quiet"><?= te('header.log_in') ?></a>
                    <a href="/story/camel-who-collected-laughter" class="button button--small button--primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg>
                        <?= te('header.watch_now') ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<?php $flash = getFlash(); ?>
<?php if ($flash): ?>
    <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>" role="status">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<main class="site-main" id="main-content">
    <?php require $contentTemplate; ?>
</main>

<footer class="site-footer">
    <div class="site-footer__inner section-shell">
        <div class="site-footer__brand">
            <img src="/assets/images/brand/hikaya-watch-mark.svg?v=<?= $brandVersion ?>" alt="" width="42" height="42" loading="lazy">
            <span><strong><?= htmlspecialchars($brandName) ?></strong><small><?= te('brand.tagline') ?></small></span>
        </div>
        <nav aria-label="<?= te('footer.aria') ?>">
            <a href="/"><?= te('footer.home') ?></a>
            <a href="/world/oasis"><?= te('footer.barik') ?></a>
            <a href="/world/star-workshop"><?= te('footer.mira') ?></a>
            <a href="/world/push-questions"><?= te('footer.push') ?></a>
            <a href="/world/city-on-wheels"><?= te('footer.city') ?></a>
            <a href="/world/robot-family"><?= te('footer.robots') ?></a>
            <a href="/characters"><?= te('footer.cast') ?></a>
        </nav>
        <p><?= te('footer.copyright', ['year' => date('Y'), 'brand' => $brandName]) ?></p>
    </div>
</footer>

<button class="back-to-top" type="button" data-back-to-top aria-label="<?= te('common.back_to_top') ?>" aria-hidden="true" tabindex="-1">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 14 6-6 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>
<div class="toast" data-toast role="status" aria-live="polite"></div>
<script>window.HikayaI18n = <?= json_encode($scriptStrings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="/assets/js/streaming.js?v=<?= $jsVersion ?>"></script>
</body>
</html>
