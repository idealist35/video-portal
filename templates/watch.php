<?php
$forcePortrait = (($video['aspect_ratio'] ?? '') === '9:16');
$posterUrl = $posterUrl ?? null;
$isStoryEpisode = !empty($storyEpisode);
$episodeNumber = (int) ($video['number'] ?? 0);
$seasonNumber = (int) ($video['season'] ?? 0);
$seasonEpisode = (int) ($video['season_episode'] ?? 0);
$seasonTotal = (int) ($video['season_total'] ?? 0);
$storyWorldSlug = $isStoryEpisode ? (string) ($video['world_slug'] ?? 'oasis') : '';
$storyWorld = $isStoryEpisode ? (getStoryWorlds()[$storyWorldSlug] ?? getStoryWorlds()['oasis']) : [];
$storyWorldUrl = $isStoryEpisode ? '/world/' . $storyWorldSlug : '/';
$storyAudioLabel = (string) ($storyWorld['audio_label'] ?? t('common.arabic_audio'));
$allStoryEpisodes = $isStoryEpisode ? getStoryWorldEpisodes($storyWorldSlug) : [];
$plannedStoryEpisodes = $isStoryEpisode
    ? max(count($allStoryEpisodes), (int) ($storyWorld['planned_episodes'] ?? count($allStoryEpisodes)))
    : 0;
$storyManifest = $isStoryEpisode
    ? array_map(
        static fn(array $episode): array => [
            'number' => (int) $episode['number'],
            'season' => (int) $episode['season'],
            'episode' => (int) $episode['season_episode'],
            'slug' => (string) $episode['slug'],
            'title' => (string) $episode['short_title'],
        ],
        $allStoryEpisodes
    )
    : [];
$storyManifestJson = htmlspecialchars(
    json_encode($storyManifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ENT_QUOTES,
    'UTF-8'
);
$seasonEpisodes = $isStoryEpisode
    ? array_values(array_filter($allStoryEpisodes, static fn(array $episode): bool => (int) $episode['season'] === $seasonNumber))
    : [];
$relatedEpisodes = $isStoryEpisode
    ? array_slice(array_values(array_filter(
        $allStoryEpisodes,
        static fn(array $episode): bool => (int) $episode['number'] !== $episodeNumber
    )), max(0, $episodeNumber - 2), 4)
    : [];
?>

<div class="watch-page<?= $forcePortrait ? ' watch-page--portrait' : '' ?>"
     data-force-portrait="<?= $forcePortrait ? '1' : '0' ?>"
     <?= $isStoryEpisode
         ? 'data-story-world="' . htmlspecialchars($storyWorldSlug) . '" data-story-manifest="' . $storyManifestJson
             . '" data-story-episode="' . $episodeNumber . '" data-story-season="' . $seasonNumber
             . '" data-story-slug="' . htmlspecialchars($video['slug']) . '"'
         : '' ?>>

    <div class="watch-breadcrumb section-shell">
        <a href="<?= $isStoryEpisode ? htmlspecialchars($storyWorldUrl . '#season-' . $seasonNumber) : '/' ?>">
            <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 6-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <?= $isStoryEpisode ? te('common.all_episodes') : te('watch.back_to_home') ?>
        </a>
        <?php if ($isStoryEpisode): ?>
            <span><?= te('common.season_n', ['number' => $seasonNumber]) ?></span>
            <span><?= te('common.episode_n', ['number' => $seasonEpisode]) ?></span>
        <?php endif; ?>
    </div>

    <section class="watch-stage section-shell" aria-label="<?= te('watch.player_aria') ?>">
        <div class="watch-player">
            <div class="watch-player__topbar" aria-hidden="true">
                <span class="watch-player__topmeta">
                    <?php if ($isStoryEpisode): ?><small>S<?= $seasonNumber ?> E<?= $seasonEpisode ?></small><?php endif; ?>
                    <b><?= te('common.hd') ?></b>
                </span>
            </div>

            <?php if ($posterUrl): ?>
                <img class="watch-player__poster" src="<?= htmlspecialchars($posterUrl) ?>" alt="" aria-hidden="true">
            <?php endif; ?>

            <video id="videoPlayer"
                   class="video-player"
                   controls
                   preload="metadata"
                   playsinline
                   controlsList="nodownload"
                   <?= $posterUrl ? 'poster="' . htmlspecialchars($posterUrl) . '"' : '' ?>>
                <source src="<?= htmlspecialchars($videoUrl) ?>" type="video/mp4">
                <?= te('watch.no_support') ?>
            </video>

            <button class="watch-player__center-play" type="button" data-player-toggle aria-label="<?= te('watch.play_video') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg>
            </button>

            <div class="watch-player__loading" id="playerLoading" aria-hidden="true" hidden>
                <span></span>
                <small><?= te('watch.loading_stream') ?></small>
            </div>

            <div class="watch-player__signal" aria-hidden="true"><i></i> <?= te('common.on_demand') ?></div>
        </div>
    </section>

    <section class="watch-details section-shell" aria-labelledby="watch-title">
        <div class="watch-details__main">
            <?php if ($isStoryEpisode): ?>
                <p class="section-kicker"><?= te('watch.episode_kicker', ['season' => $seasonNumber, 'episode' => $seasonEpisode, 'total' => $seasonTotal]) ?></p>
            <?php elseif (!empty($video['category'])): ?>
                <p class="section-kicker"><?= htmlspecialchars($video['category']) ?></p>
            <?php endif; ?>
            <h1 id="watch-title"><?= htmlspecialchars($video['title']) ?></h1>
            <div class="title-meta">
                <?php if ($isStoryEpisode): ?>
                    <span class="match-label"><?= te('watch.now_playing') ?></span>
                    <span><?= htmlspecialchars($storyAudioLabel) ?></span>
                    <span><?= te('common.hd') ?></span>
                    <span class="age-label"><?= te('common.age_all') ?></span>
                    <span><?= htmlspecialchars($video['place']) ?></span>
                <?php elseif (!empty($video['category'])): ?>
                    <span><?= htmlspecialchars($video['category']) ?></span>
                    <span><?= te('common.hd') ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($video['description'])): ?>
                <p class="watch-details__description"><?= nl2br(htmlspecialchars($video['description'])) ?></p>
            <?php endif; ?>
            <?php if ($isStoryEpisode): ?>
                <p class="watch-details__cast"><span><?= te('watch.featuring') ?></span><?= htmlspecialchars($video['character']) ?></p>
            <?php endif; ?>
        </div>

        <?php if ($isStoryEpisode): ?>
        <aside class="up-next">
            <p class="up-next__label"><?= te('watch.up_next') ?></p>
            <?php if ($nextEpisode): ?>
                <a class="up-next__screen" data-next-story href="<?= htmlspecialchars($nextEpisode['watch_url']) ?>">
                    <img src="<?= htmlspecialchars($nextEpisode['poster']) ?>" alt="" loading="lazy" decoding="async">
                    <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg></span>
                    <small>S<?= (int) $nextEpisode['season'] ?> E<?= (int) $nextEpisode['season_episode'] ?></small>
                    <b><?= te('common.hd') ?></b>
                </a>
                <div class="up-next__copy">
                    <span><?= te('watch.next_episode') ?></span>
                    <strong><?= htmlspecialchars($nextEpisode['short_title']) ?></strong>
                    <a data-next-story href="<?= htmlspecialchars($nextEpisode['watch_url']) ?>"><?= te('watch.watch_next') ?>
                        <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                    </a>
                </div>
            <?php else: ?>
                <div class="up-next__complete">
                    <img src="/assets/images/brand/hikaya-watch-mark.svg" alt="" width="46" height="46">
                    <?php if ($plannedStoryEpisodes > count($allStoryEpisodes)): ?>
                        <span><strong><?= te('watch.more_coming_title') ?></strong><small><?= te('watch.more_coming_note') ?></small></span>
                    <?php else: ?>
                        <span><strong><?= te('watch.complete_title') ?></strong><small><?= te('watch.complete_note') ?></small></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </aside>
        <?php endif; ?>
    </section>

    <?php if ($isStoryEpisode): ?>
    <section class="watch-season-progress section-shell">
        <div>
            <span><?= te('watch.season_progress', ['number' => $seasonNumber]) ?></span>
            <strong data-season-progress="<?= $seasonNumber ?>"><?= te('common.progress', ['watched' => 0, 'total' => $seasonTotal]) ?></strong>
        </div>
        <div class="watch-season-progress__dots">
            <?php foreach ($seasonEpisodes as $seasonStory): ?>
                <a href="<?= htmlspecialchars($seasonStory['watch_url']) ?>"
                   data-progress-dot="<?= (int) $seasonStory['number'] ?>"
                   <?= (int) $seasonStory['number'] === $episodeNumber ? 'aria-current="page"' : '' ?>
                   aria-label="<?= htmlspecialchars(t('watch.episode_aria', ['episode' => (int) $seasonStory['season_episode'], 'title' => $seasonStory['short_title']])) ?>">
                    <?= (int) $seasonStory['season_episode'] ?>
                </a>
            <?php endforeach; ?>
        </div>
        <p data-complete-note><?= te('watch.order_note') ?></p>
    </section>

    <nav class="episode-navigation section-shell" aria-label="<?= te('watch.nav_aria') ?>">
        <?php if ($previousEpisode): ?>
            <a class="episode-navigation__item" href="<?= htmlspecialchars($previousEpisode['watch_url']) ?>">
                <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 6-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                <span><small><?= te('watch.previous_episode') ?></small><strong><?= htmlspecialchars($previousEpisode['short_title']) ?></strong></span>
            </a>
        <?php else: ?>
            <a class="episode-navigation__item" href="<?= htmlspecialchars($storyWorldUrl) ?>">
                <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 6-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                <span><small><?= te('watch.back_to') ?></small><strong><?= te('common.all_episodes') ?></strong></span>
            </a>
        <?php endif; ?>

        <?php if ($nextEpisode): ?>
            <a class="episode-navigation__item episode-navigation__item--next" data-next-story href="<?= htmlspecialchars($nextEpisode['watch_url']) ?>">
                <span><small><?= (int) $nextEpisode['season'] !== $seasonNumber ? te('watch.begin_next_season') : te('watch.next_episode') ?></small><strong><?= htmlspecialchars($nextEpisode['short_title']) ?></strong></span>
                <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
            </a>
        <?php else: ?>
            <a class="episode-navigation__item episode-navigation__item--next" href="<?= htmlspecialchars($storyWorldUrl) ?>">
                <span>
                    <small><?= $plannedStoryEpisodes > count($allStoryEpisodes) ? te('watch.more_coming') : te('watch.complete_title') ?></small>
                    <strong><?= te('watch.return_to_library') ?></strong>
                </span>
                <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
            </a>
        <?php endif; ?>
    </nav>

    <?php if ($relatedEpisodes): ?>
    <section class="content-section section-shell watch-more" aria-labelledby="watch-more-title">
        <div class="section-heading">
            <div>
                <p class="section-kicker section-kicker--aqua"><?= te('watch.keep_watching') ?></p>
                <h2 id="watch-more-title"><?= te('watch.more_from_series') ?></h2>
            </div>
            <a class="section-link" href="<?= htmlspecialchars($storyWorldUrl) ?>#episodes"><?= te('common.all_episodes') ?>
                <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
            </a>
        </div>
        <div class="episode-grid episode-grid--four">
            <?php foreach ($relatedEpisodes as $episode): ?>
                <?php $episodeCardLoading = 'lazy'; ?>
                <?php require TEMPLATES_PATH . '/partials/stream_episode_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    <?php endif; ?>
</div>
