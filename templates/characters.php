<?php
$featuredCast = array_slice($characters, 0, 3);

// Grouped by show. A flat grid was fine while every character came from the
// oasis; with five series in it, an ungrouped wall of twenty-five gives the
// viewer no way to find the one they were watching. Order follows the array so
// the series stay in a deliberate order rather than alphabetical.
$castBySeries = [];
foreach ($characters as $character) {
    $castBySeries[$character['series'] ?? 'Other'][] = $character;
}
$castIndex = 0;
?>

<div class="cast-page">
    <section class="cast-hero section-shell" aria-labelledby="cast-title">
        <div class="cast-hero__copy">
            <p class="live-kicker"><span></span> <?= te('cast.kicker') ?></p>
            <h1 id="cast-title"><?= te('cast.title_line_1') ?><br><?= te('cast.title_line_2') ?></h1>
            <p><?= te('cast.intro') ?></p>
            <div class="cast-hero__actions">
                <a class="button button--primary button--large" href="/story/camel-who-collected-laughter">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg>
                    <?= te('cast.watch_series') ?>
                </a>
                <span><strong><?= count($characters) ?></strong><small><?= te('cast.featured_label') ?></small></span>
            </div>
        </div>

        <div class="cast-hero__screen" aria-label="<?= te('cast.featured_aria') ?>">
            <img class="cast-hero__backdrop" src="/assets/images/episodes/season-2-full-cast.webp" alt="" fetchpriority="high" decoding="async">
            <span class="cast-hero__shade" aria-hidden="true"></span>
            <div class="cast-hero__portraits" aria-hidden="true">
                <?php foreach ($featuredCast as $character): ?>
                    <span><img src="<?= htmlspecialchars($character['image']) ?>" alt="" decoding="async"></span>
                <?php endforeach; ?>
            </div>
            <span class="cast-hero__label"><?= te('cast.featured_cast') ?></span>
            <span class="cast-hero__play" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg>
            </span>
            <span class="cast-hero__chrome" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg>
                <i></i><small><?= te('cast.meet_the_crew') ?></small><b><?= te('common.hd') ?></b>
                <svg viewBox="0 0 24 24"><path d="M8 4H4v4M16 4h4v4M20 16v4h-4M4 16v4h4" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
            </span>
        </div>
    </section>

    <section class="cast-library section-shell" aria-labelledby="cast-library-title">
        <div class="section-heading">
            <div>
                <p class="section-kicker section-kicker--aqua"><?= te('cast.profiles_kicker') ?></p>
                <h2 id="cast-library-title"><?= te('cast.full_crew') ?></h2>
            </div>
            <p class="section-heading__note"><?= te('cast.profiles_note') ?></p>
        </div>

        <?php foreach ($castBySeries as $seriesName => $seriesCast): ?>
        <div class="cast-series">
            <h3 class="cast-series__title"><?= htmlspecialchars($seriesName) ?>
                <span><?= htmlspecialchars(tn('plural.characters', count($seriesCast))) ?></span>
            </h3>
        </div>
        <div class="cast-grid">
            <?php foreach ($seriesCast as $character): $index = $castIndex++; ?>
            <article class="cast-card" data-cast-card>
                <div class="cast-card__visual">
                    <span class="cast-card__index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <img src="<?= htmlspecialchars($character['image']) ?>"
                         alt="<?= htmlspecialchars($character['name']) ?>"
                         loading="<?= $index < 3 ? 'eager' : 'lazy' ?>"
                         decoding="async">
                    <span class="cast-card__gradient" aria-hidden="true"></span>
                    <span class="cast-card__status"><i></i> <?= te('cast.series_regular') ?></span>
                </div>
                <div class="cast-card__body">
                    <span><?= htmlspecialchars($character['species']) ?></span>
                    <h3><?= htmlspecialchars($character['name']) ?></h3>
                    <p><?= htmlspecialchars($character['likes']) ?> · <?= htmlspecialchars($character['food']) ?></p>
                    <button type="button" data-cast-toggle aria-expanded="false">
                        <?= te('cast.view_profile') ?>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div class="cast-card__profile" data-cast-profile hidden>
                        <dl>
                            <div><dt><?= te('cast.likes') ?></dt><dd><?= htmlspecialchars($character['likes']) ?></dd></div>
                            <div><dt><?= te('cast.worries') ?></dt><dd><?= htmlspecialchars($character['fears']) ?></dd></div>
                            <div><dt><?= te('cast.favorite_bite') ?></dt><dd><?= htmlspecialchars($character['food']) ?></dd></div>
                            <div><dt><?= te('cast.did_you_know') ?></dt><dd><?= htmlspecialchars($character['fact']) ?></dd></div>
                        </dl>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </section>

    <section class="cast-watch-strip section-shell">
        <div class="cast-watch-strip__screen">
            <img src="/assets/images/episodes/coral-bay-dolphin-leap.jpg" alt="" loading="lazy" decoding="async">
            <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg></span>
        </div>
        <div class="cast-watch-strip__content">
            <p class="section-kicker"><?= te('cast.in_action_kicker') ?></p>
            <h2><?= te('cast.in_action_title') ?></h2>
            <a class="button button--primary" href="/world/oasis#episodes"><?= te('cast.browse_all') ?>
                <svg class="icon-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
            </a>
        </div>
        <div class="cast-watch-strip__chrome" aria-hidden="true"><i></i><small>00:00</small><b><?= te('common.hd') ?></b></div>
    </section>
</div>
