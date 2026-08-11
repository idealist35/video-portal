<?php
$firstEpisode = $episodes[0] ?? null;
$seasons = $seasons ?? getOasisSeasons();
$openingEpisodes = array_slice($episodes, 0, 4);
$latestEpisodes = array_slice($episodes, 9, 6);
$totalEpisodes = count($episodes);
$totalSeasons = count($seasons);

// The continue banner reads watch progress in the browser and prints the next
// episode's name. Without a manifest here, streaming.js would fall back to the
// English titles compiled into it, so the home page ships its own.
$storyManifestJson = htmlspecialchars(
    json_encode(
        array_map(
            static fn(array $episode): array => [
                'number' => (int) $episode['number'],
                'season' => (int) $episode['season'],
                'episode' => (int) $episode['season_episode'],
                'slug' => (string) $episode['slug'],
                'title' => (string) $episode['short_title'],
            ],
            $episodes
        ),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ),
    ENT_QUOTES,
    'UTF-8'
);
?>

<?php if (!empty($error)): ?>
    <div class="page-notice page-notice--error section-shell">
        <strong><?= te('common.playback_notice') ?></strong>
        <p><?= htmlspecialchars($error) ?></p>
    </div>
<?php endif; ?>

<?php if ($firstEpisode): ?>
<section class="stream-hero section-shell" aria-labelledby="hero-title">
    <img class="stream-hero__art"
         src="/assets/images/episodes/mysterious-gift.webp"
         alt=""
         fetchpriority="high"
         decoding="async">
    <div class="stream-hero__shade" aria-hidden="true"></div>

    <div class="stream-hero__topline">
        <span class="original-label">
            <img src="/assets/images/brand/hikaya-watch-mark.svg" alt="" width="24" height="24">
            <?= te('brand.original') ?>
        </span>
        <span class="quality-label"><?= te('common.hd') ?></span>
    </div>

    <div class="stream-hero__content">
        <p class="live-kicker"><span></span> <?= te('home.now_streaming') ?></p>
        <h1 id="hero-title"><?= te('home.hero_title_line_1') ?><br><?= te('home.hero_title_line_2') ?></h1>
        <div class="title-meta" aria-label="<?= te('home.series_details_aria') ?>">
            <span class="match-label"><?= te('common.new') ?></span>
            <span><?= htmlspecialchars(tn('plural.seasons', $totalSeasons)) ?></span>
            <span><?= htmlspecialchars(tn('plural.episodes', $totalEpisodes)) ?></span>
            <span class="age-label"><?= te('common.age_all') ?></span>
            <span><?= te('common.arabic_audio') ?></span>
        </div>
        <p class="stream-hero__description"><?= te('home.hero_description') ?></p>
        <div class="stream-hero__actions">
            <a class="button button--primary button--large" href="<?= htmlspecialchars($firstEpisode['watch_url']) ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg>
                <?= te('home.watch_episode_one') ?>
            </a>
            <a class="button button--glass button--large" href="/world/oasis#episodes">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 7h12M6 12h12M6 17h8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <?= te('home.browse_episodes') ?>
            </a>
        </div>
    </div>

    <a class="hero-play" href="<?= htmlspecialchars($firstEpisode['watch_url']) ?>" aria-label="<?= htmlspecialchars(t('home.play_aria', ['title' => $firstEpisode['title']])) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg>
    </a>

</section>
<?php endif; ?>

<section class="streaming-proof" aria-label="<?= te('home.proof_aria') ?>">
    <div class="section-shell streaming-proof__inner">
        <span><b class="streaming-proof__dot"></b> <?= te('common.on_demand') ?></span>
        <span><strong><?= $totalEpisodes ?></strong> <?= htmlspecialchars(tn('plural.full_episodes_label', $totalEpisodes)) ?></span>
        <span><strong><?= $totalSeasons ?></strong> <?= htmlspecialchars(tn('plural.complete_seasons_label', $totalSeasons)) ?></span>
        <span><strong><?= te('home.proof_hd') ?></strong> <?= te('home.proof_hd_label') ?></span>
        <span><strong><?= te('home.proof_audio_code') ?></strong> <?= te('home.proof_audio_label') ?></span>
    </div>
</section>

<?php if ($openingEpisodes): ?>
<section class="content-section section-shell" aria-labelledby="start-watching-title">
    <div class="section-heading">
        <div>
            <p class="section-kicker"><?= te('home.start_here') ?></p>
            <h2 id="start-watching-title"><?= te('home.watch_from_one') ?></h2>
        </div>
        <a class="section-link" href="/world/oasis#episodes"><?= te('home.view_all_episodes', ['count' => $totalEpisodes]) ?>
            <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </div>
    <div class="episode-grid episode-grid--four">
        <?php foreach ($openingEpisodes as $index => $episode): ?>
            <?php $episodeCardLoading = $index < 2 ? 'eager' : 'lazy'; ?>
            <?php require TEMPLATES_PATH . '/partials/stream_episode_card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php foreach (($featuredWorlds ?? []) as $featured): ?>
    <?php
    $featuredWorld = $featured['world'] ?? null;
    $featuredWorldEpisodes = $featured['episodes'] ?? [];
    if (empty($featuredWorld) || empty($featuredWorldEpisodes)) {
        continue;
    }
    $featuredTitleId = 'new-series-title-' . $featuredWorld['slug'];
    ?>
<section class="watch-cta watch-cta--new-series section-shell" aria-labelledby="<?= htmlspecialchars($featuredTitleId) ?>">
    <img src="<?= htmlspecialchars((string) $featuredWorld['image']) ?>" alt="" loading="lazy" decoding="async">
    <div class="watch-cta__shade" aria-hidden="true"></div>
    <div class="watch-cta__content">
        <span><?= te('home.new_series') ?> · <?= htmlspecialchars((string) ($featuredWorld['audio_label'] ?? t('common.arabic_audio'))) ?></span>
        <h2 id="<?= htmlspecialchars($featuredTitleId) ?>"><?= htmlspecialchars((string) $featuredWorld['title']) ?></h2>
        <p><?= htmlspecialchars((string) $featuredWorld['description']) ?></p>
        <a class="button button--primary button--large" href="/world/<?= htmlspecialchars((string) $featuredWorld['slug']) ?>">
            <?= te('home.explore_series') ?>
            <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        </a>
    </div>
    <div class="watch-cta__status">
        <span></span>
        <?= te('home.episodes_ready', [
            'ready' => count($featuredWorldEpisodes),
            'total' => (int) ($featuredWorld['planned_episodes'] ?? count($featuredWorldEpisodes)),
        ]) ?>
    </div>
</section>
<?php endforeach; ?>

<section class="continue-banner section-shell"
         aria-labelledby="continue-title"
         data-story-world="oasis"
         data-story-manifest="<?= $storyManifestJson ?>">
    <div class="continue-banner__visual">
        <img src="/assets/images/episodes/camel-laughter-scene-03-2.webp" alt="" loading="lazy" decoding="async">
        <span class="continue-banner__play" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg>
        </span>
    </div>
    <div class="continue-banner__content">
        <p class="section-kicker"><?= te('home.continue_kicker') ?></p>
        <h2 id="continue-title"><?= te('home.continue_title') ?></h2>
        <p><?= te('home.continue_copy') ?></p>
        <div class="watch-progress">
            <div><span><?= te('home.your_progress') ?></span><strong data-progress-label><?= te('common.progress', ['watched' => 0, 'total' => $totalEpisodes]) ?></strong></div>
            <span class="watch-progress__track"><i data-progress-bar style="width:0%"></i></span>
        </div>
        <a class="button button--primary" data-continue-link href="<?= htmlspecialchars($firstEpisode['watch_url']) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg>
            <span data-continue-copy><?= te('home.start_watching') ?></span>
        </a>
    </div>
</section>

<section class="content-section section-shell" aria-labelledby="season-title">
    <div class="section-heading">
        <div>
            <p class="section-kicker"><?= te('home.collections_kicker') ?></p>
            <h2 id="season-title"><?= te('home.choose_season') ?></h2>
        </div>
        <p class="section-heading__note"><?= te('home.seasons_note') ?></p>
    </div>
    <div class="season-stream-grid">
        <?php foreach ($seasons as $season): ?>
        <article class="season-stream season-stream--<?= htmlspecialchars($season['theme']) ?>">
            <a class="season-stream__screen" href="<?= htmlspecialchars($season['start_url']) ?>">
                <img src="<?= htmlspecialchars($season['poster']) ?>" alt="" loading="lazy" decoding="async">
                <span class="season-stream__wash" aria-hidden="true"></span>
                <span class="season-stream__number"><?= te('common.season_n', ['number' => (int) $season['number']]) ?></span>
                <span class="season-stream__play" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg>
                </span>
            </a>
            <div class="season-stream__body">
                <div>
                    <p><?= htmlspecialchars($season['eyebrow']) ?></p>
                    <h3><?= htmlspecialchars($season['title']) ?></h3>
                </div>
                <span><?= htmlspecialchars($season['description']) ?></span>
                <a href="/world/oasis#season-<?= (int) $season['number'] ?>"><?= te('home.view_season') ?>
                    <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                </a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($latestEpisodes): ?>
<section class="content-section section-shell content-section--latest" aria-labelledby="latest-title">
    <div class="section-heading">
        <div>
            <p class="section-kicker section-kicker--aqua"><?= te('home.coral_kicker') ?></p>
            <h2 id="latest-title"><?= te('home.more_episodes') ?></h2>
        </div>
        <button class="text-button" type="button" data-random-story>
            <?= te('home.surprise_me') ?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 3h5v5M4 20 21 3M21 16v5h-5M15 15l6 6M4 4l5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </div>
    <div class="episode-grid episode-grid--three">
        <?php foreach ($latestEpisodes as $episode): ?>
            <?php $episodeCardLoading = 'lazy'; ?>
            <?php require TEMPLATES_PATH . '/partials/stream_episode_card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="watch-cta section-shell">
    <img src="/assets/images/episodes/season-2-full-cast.webp" alt="" loading="lazy" decoding="async">
    <div class="watch-cta__shade" aria-hidden="true"></div>
    <div class="watch-cta__content">
        <span><?= te('home.all_available') ?></span>
        <h2><?= te('home.cta_line_1', ['count' => $totalEpisodes]) ?><br><?= te('home.cta_line_2') ?></h2>
        <a class="button button--primary button--large" href="/world/oasis">
            <?= te('home.open_series') ?>
            <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </a>
    </div>
    <div class="watch-cta__status"><span></span> <?= te('home.ready_to_stream') ?></div>
</section>
