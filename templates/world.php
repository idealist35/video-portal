<?php
$seasons = $seasons ?? ($world['status'] === 'available' ? getOasisSeasons() : []);
$isAvailable = $world['status'] === 'available' && !empty($episodes);
$audioLabel = (string) ($world['audio_label'] ?? t('common.arabic_audio'));
$plannedEpisodes = max(count($episodes), (int) ($world['planned_episodes'] ?? count($episodes)));
$episodeCountLabel = $plannedEpisodes > count($episodes)
    ? tn('plural.episodes_partial', count($episodes), ['total' => $plannedEpisodes])
    : tn('plural.episodes', count($episodes));
$seasonStatusLabel = $plannedEpisodes > count($episodes)
    ? tn('plural.episodes_available', count($episodes))
    : t('world.complete_season');
$endcapUrl = $world['slug'] === 'oasis' ? '/characters' : '/';
$endcapLabel = $world['slug'] === 'oasis' ? t('world.meet_the_cast') : t('world.browse_all_series');
$availabilityCopy = (string) ($world['availability_copy'] ?? t('world.availability_full'));
$storyManifest = array_map(
    static fn(array $episode): array => [
        'number' => (int) $episode['number'],
        'season' => (int) $episode['season'],
        'episode' => (int) $episode['season_episode'],
        'slug' => (string) $episode['slug'],
        'title' => (string) $episode['short_title'],
    ],
    $episodes
);
$storyManifestJson = htmlspecialchars(
    json_encode($storyManifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ENT_QUOTES,
    'UTF-8'
);
$heroImage = $isAvailable
    ? (string) ($world['hero_image'] ?? '/assets/images/episodes/mysterious-gift.webp')
    : (string) ($world['image'] ?? '/assets/images/worlds/sea.webp');
?>

<?php if (!empty($error)): ?>
    <div class="page-notice page-notice--error section-shell">
        <strong><?= te('common.playback_notice') ?></strong>
        <p><?= htmlspecialchars($error) ?></p>
    </div>
<?php endif; ?>

<div class="series-page"
     data-story-world="<?= htmlspecialchars((string) $world['slug']) ?>"
     data-story-manifest="<?= $storyManifestJson ?>">
    <div class="series-breadcrumb section-shell">
        <a href="/"><?= te('common.home') ?></a>
        <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        <span><?= te('common.series') ?></span>
        <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        <strong><?= htmlspecialchars($world['short_title']) ?></strong>
    </div>

    <section class="series-hero section-shell" aria-labelledby="series-title">
        <img class="series-hero__art" src="<?= htmlspecialchars($heroImage) ?>" alt="" fetchpriority="high" decoding="async">
        <div class="series-hero__shade" aria-hidden="true"></div>
        <div class="series-hero__topbar">
            <span><img src="/assets/images/brand/hikaya-watch-mark.svg" alt="" width="22" height="22"> <?= te('brand.original') ?></span>
            <b><?= $isAvailable ? te('common.hd') : te('common.preview') ?></b>
        </div>
        <div class="series-hero__copy">
            <p class="live-kicker"><span></span> <?= $isAvailable ? htmlspecialchars($availabilityCopy) : te('world.coming_soon') ?></p>
            <h1 id="series-title"><?= htmlspecialchars($world['title']) ?></h1>
            <div class="title-meta">
                <?php if ($isAvailable): ?>
                    <span class="match-label"><?= te('common.new') ?></span>
                    <span><?= htmlspecialchars(tn('plural.seasons', count($seasons))) ?></span>
                    <span><?= htmlspecialchars($episodeCountLabel) ?></span>
                    <span class="age-label"><?= te('common.age_all') ?></span>
                    <span><?= htmlspecialchars($audioLabel) ?></span>
                <?php else: ?>
                    <span class="age-label"><?= te('common.age_all') ?></span>
                    <span><?= te('world.animated_series') ?></span>
                    <span><?= te('world.in_production') ?></span>
                <?php endif; ?>
            </div>
            <p><?= htmlspecialchars($world['description']) ?></p>
            <div class="series-hero__actions">
                <?php if ($isAvailable): ?>
                    <a class="button button--primary button--large" href="<?= htmlspecialchars($episodes[0]['watch_url']) ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg>
                        <?= te('world.play_first') ?>
                    </a>
                    <button class="button button--glass button--large" type="button" data-random-story>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 3h5v5M4 20 21 3M21 16v5h-5M15 15l6 6M4 4l5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <?= te('world.play_something') ?>
                    </button>
                    <a class="button button--glass button--large" href="/world/<?= htmlspecialchars((string) $world['slug']) ?>/map">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 6 5-2 6 2 5-2v14l-5 2-6-2-5 2V6Zm5-2v14m6-12v14" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <?= te('world.series_map') ?>
                    </a>
                <?php else: ?>
                    <a class="button button--glass button--large" href="/"><?= te('world.back_to_streaming') ?></a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($isAvailable): ?>
    <section class="series-catalog section-shell" id="episodes" aria-labelledby="episodes-title">
        <div class="series-catalog__head">
            <div>
                <p class="section-kicker"><?= te('world.now_playing') ?></p>
                <h2 id="episodes-title"><?= te('common.all_episodes') ?></h2>
            </div>
            <div class="series-progress">
                <div><span><?= te('world.watch_progress') ?></span><strong data-progress-label><?= te('common.progress', ['watched' => 0, 'total' => count($episodes)]) ?></strong></div>
                <span class="series-progress__track"><i data-progress-bar style="width:0%"></i></span>
            </div>
        </div>

        <div class="season-filters" aria-label="<?= te('world.filter_aria') ?>">
            <button class="is-active" type="button" data-season-filter="all" aria-pressed="true"><?= te('world.filter_all') ?> <span><?= count($episodes) ?></span></button>
            <?php foreach ($seasons as $season): ?>
                <button type="button" data-season-filter="<?= (int) $season['number'] ?>" aria-pressed="false">
                    <?= te('common.season_n', ['number' => (int) $season['number']]) ?> <span><?= (int) $season['episode_count'] ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($seasons as $season): ?>
        <section class="series-season"
                 id="season-<?= (int) $season['number'] ?>"
                 data-season-panel="<?= (int) $season['number'] ?>"
                 aria-labelledby="season-<?= (int) $season['number'] ?>-title">
            <div class="series-season__heading">
                <div>
                    <span><?= te('common.season_n', ['number' => (int) $season['number']]) ?></span>
                    <h3 id="season-<?= (int) $season['number'] ?>-title"><?= htmlspecialchars($season['title']) ?></h3>
                    <p><?= htmlspecialchars($season['description']) ?></p>
                </div>
                <div class="season-status">
                    <span><i></i> <?= htmlspecialchars($seasonStatusLabel) ?></span>
                    <strong data-season-progress="<?= (int) $season['number'] ?>"><?= te('common.progress', ['watched' => 0, 'total' => (int) $season['episode_count']]) ?></strong>
                </div>
            </div>

            <div class="episode-grid episode-grid--three">
                <?php foreach ($season['episodes'] as $episodeIndex => $episode): ?>
                    <?php $episodeCardLoading = $episodeIndex < 3 && (int) $season['number'] === 1 ? 'eager' : 'lazy'; ?>
                    <?php require TEMPLATES_PATH . '/partials/stream_episode_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </section>

    <section class="series-endcap section-shell">
        <div>
            <img src="/assets/images/brand/hikaya-watch-mark.svg" alt="" width="48" height="48">
            <span><strong><?= te('world.endcap_title') ?></strong><small><?= te('world.endcap_note', ['audio' => $audioLabel]) ?></small></span>
        </div>
        <a class="button button--quiet" href="<?= htmlspecialchars($endcapUrl) ?>"><?= htmlspecialchars($endcapLabel) ?>
            <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        </a>
    </section>
    <?php else: ?>
    <section class="coming-player section-shell" aria-labelledby="coming-title">
        <div class="section-heading">
            <div>
                <p class="section-kicker section-kicker--aqua"><?= te('world.next_on') ?></p>
                <h2 id="coming-title"><?= te('world.loading_title') ?></h2>
            </div>
            <p class="section-heading__note"><?= te('world.loading_note') ?></p>
        </div>
        <div class="coming-player__screen">
            <img src="<?= htmlspecialchars($heroImage) ?>" alt="" loading="lazy">
            <span class="coming-player__shade" aria-hidden="true"></span>
            <span class="coming-player__badge"><?= te('world.trailer_soon') ?></span>
            <span class="coming-player__play" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg>
            </span>
        </div>
    </section>
    <?php endif; ?>
</div>
