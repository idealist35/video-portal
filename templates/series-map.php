<?php
$audioLabel = (string) ($world['audio_label'] ?? t('common.arabic_audio'));
$plannedEpisodes = max(count($episodes), (int) ($world['planned_episodes'] ?? count($episodes)));
$episodeCountLabel = $plannedEpisodes > count($episodes)
    ? tn('plural.episodes_partial_available', count($episodes), ['total' => $plannedEpisodes])
    : tn('plural.episodes_available', count($episodes));
$heroImage = (string) ($world['hero_image'] ?? $world['image'] ?? $episodes[0]['poster']);
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
?>

<div class="detailed-map-page"
     data-story-world="<?= htmlspecialchars((string) $world['slug']) ?>"
     data-story-manifest="<?= $storyManifestJson ?>">
    <div class="series-breadcrumb section-shell">
        <a href="/"><?= te('common.home') ?></a>
        <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        <a href="/world/<?= htmlspecialchars((string) $world['slug']) ?>"><?= htmlspecialchars($world['short_title']) ?></a>
        <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        <strong><?= te('map.breadcrumb') ?></strong>
    </div>

    <section class="map-hero section-shell" aria-labelledby="map-title">
        <img class="map-hero__art" src="<?= htmlspecialchars($heroImage) ?>" alt="" fetchpriority="high" decoding="async">
        <div class="map-hero__shade" aria-hidden="true"></div>
        <div class="map-hero__copy">
            <p class="live-kicker"><span></span> <?= te('map.route_kicker') ?></p>
            <h1 id="map-title"><?= htmlspecialchars($world['title']) ?><br><em><?= te('map.title_suffix') ?></em></h1>
            <p><?= te('map.intro') ?></p>
            <div class="title-meta">
                <span class="match-label"><?= htmlspecialchars(tn('plural.seasons', count($seasons))) ?></span>
                <span><?= htmlspecialchars($episodeCountLabel) ?></span>
                <span class="age-label"><?= te('common.age_all') ?></span>
                <span><?= htmlspecialchars($audioLabel) ?></span>
            </div>
            <div class="map-hero__actions">
                <a class="button button--primary button--large" data-continue-link href="<?= htmlspecialchars($episodes[0]['watch_url']) ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg>
                    <span data-continue-copy><?= te('home.start_watching') ?></span>
                </a>
                <a class="button button--glass button--large" href="/world/<?= htmlspecialchars((string) $world['slug']) ?>#episodes">
                    <?= te('map.back_to_episodes') ?>
                </a>
            </div>
        </div>
    </section>

    <section class="map-overview section-shell" aria-labelledby="map-overview-title">
        <div class="map-overview__head">
            <div>
                <p class="section-kicker section-kicker--aqua"><?= te('map.overview_kicker') ?></p>
                <h2 id="map-overview-title"><?= te('map.overview_title') ?></h2>
            </div>
            <div class="map-overview__progress">
                <div><span><?= te('map.series_progress') ?></span><strong data-progress-label><?= te('common.progress', ['watched' => 0, 'total' => count($episodes)]) ?></strong></div>
                <span class="series-progress__track"><i data-progress-bar style="width:0%"></i></span>
            </div>
        </div>

        <nav class="map-season-nav" aria-label="<?= te('map.jump_aria') ?>">
            <?php foreach ($seasons as $season): ?>
                <a href="#map-season-<?= (int) $season['number'] ?>">
                    <?= te('common.season_n', ['number' => (int) $season['number']]) ?>
                    <span><?= (int) $season['episode_count'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <?php foreach ($seasons as $season): ?>
    <section class="detailed-map-season section-shell"
             id="map-season-<?= (int) $season['number'] ?>"
             aria-labelledby="map-season-<?= (int) $season['number'] ?>-title">
        <div class="detailed-map-season__heading">
            <div>
                <p class="section-kicker<?= (int) $season['number'] % 2 === 0 ? ' section-kicker--aqua' : '' ?>"><?= te('common.season_n', ['number' => (int) $season['number']]) ?></p>
                <h2 id="map-season-<?= (int) $season['number'] ?>-title"><?= htmlspecialchars($season['title']) ?></h2>
                <p><?= htmlspecialchars($season['description']) ?></p>
            </div>
            <div class="detailed-map-season__status">
                <span><i></i> <?= htmlspecialchars(tn('plural.episodes_ready', (int) $season['episode_count'])) ?></span>
                <strong data-season-progress="<?= (int) $season['number'] ?>"><?= te('common.progress', ['watched' => 0, 'total' => (int) $season['episode_count']]) ?></strong>
            </div>
        </div>

        <div class="detailed-map-route">
            <?php foreach ($season['episodes'] as $episodeIndex => $episode): ?>
            <article class="detailed-map-stop"
                     data-episode-card
                     data-episode="<?= (int) $episode['number'] ?>"
                     data-season="<?= (int) $episode['season'] ?>">
                <a class="detailed-map-stop__node"
                   data-progress-dot="<?= (int) $episode['number'] ?>"
                   href="<?= htmlspecialchars($episode['watch_url']) ?>"
                   aria-label="<?= htmlspecialchars(t('map.watch_aria', ['season' => (int) $episode['season'], 'episode' => (int) $episode['season_episode']])) ?>">
                    <span><?= (int) $episode['season_episode'] ?></span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 12.5 3.4 3.4L18 7.8" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>

                <a class="detailed-map-card"
                   href="<?= htmlspecialchars($episode['watch_url']) ?>"
                   aria-label="<?= htmlspecialchars(t('common.watch_episode') . ': ' . $episode['title']) ?>">
                    <span class="detailed-map-card__visual">
                        <img src="<?= htmlspecialchars($episode['poster']) ?>"
                             alt=""
                             loading="<?= $episodeIndex < 2 ? 'eager' : 'lazy' ?>"
                             decoding="async">
                        <span class="detailed-map-card__play" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg>
                        </span>
                    </span>
                    <span class="detailed-map-card__body">
                        <span class="detailed-map-card__kicker"><?= htmlspecialchars(t('map.card_kicker', [
                            'season' => (int) $episode['season'],
                            'episode' => (int) $episode['season_episode'],
                            'place' => (string) $episode['place'],
                        ])) ?></span>
                        <strong><?= htmlspecialchars($episode['title']) ?></strong>
                        <span class="detailed-map-card__description"><?= htmlspecialchars($episode['description']) ?></span>
                        <span class="detailed-map-card__facts">
                            <span><?= htmlspecialchars($audioLabel) ?></span>
                            <span><?= htmlspecialchars($episode['character'] ?? t('common.all_ages')) ?></span>
                        </span>
                        <span class="detailed-map-card__action"><?= te('common.watch_episode') ?>
                            <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                        </span>
                    </span>
                </a>
            </article>
            <?php endforeach; ?>

            <div class="detailed-map-finish">
                <img src="/assets/images/brand/hikaya-watch-mark.svg" alt="" width="42" height="42" loading="lazy">
                <span><strong><?= te('map.season_complete', ['number' => (int) $season['number']]) ?></strong><small><?= te('map.replays_note') ?></small></span>
            </div>
        </div>
    </section>
    <?php endforeach; ?>

    <section class="map-endcap section-shell">
        <div>
            <p class="section-kicker"><?= te('map.keep_exploring') ?></p>
            <h2><?= te('map.next_story') ?></h2>
            <p><?= te('map.endcap_note') ?></p>
        </div>
        <div class="map-endcap__actions">
            <a class="button button--primary" data-continue-link href="<?= htmlspecialchars($episodes[0]['watch_url']) ?>">
                <span data-continue-copy><?= te('home.start_watching') ?></span>
            </a>
            <a class="button button--quiet" href="/world/<?= htmlspecialchars((string) $world['slug']) ?>#episodes"><?= te('common.all_episodes') ?></a>
        </div>
    </section>
</div>
