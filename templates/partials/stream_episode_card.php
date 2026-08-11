<?php
/**
 * Streaming-style episode tile.
 *
 * Expected variables:
 * - $episode: curated episode metadata.
 * - $episodeCardLoading: optional image loading strategy ("lazy" by default).
 */
$episodeCardLoading = $episodeCardLoading ?? 'lazy';
$episodeNumber = (int) ($episode['number'] ?? 0);
$seasonNumber = (int) ($episode['season'] ?? 0);
$seasonEpisode = (int) ($episode['season_episode'] ?? 0);
$watchUrl = (string) ($episode['watch_url'] ?? '#');
$poster = (string) ($episode['poster'] ?? '');
$durationLabel = !empty($episode['duration'])
    ? gmdate(((int) $episode['duration']) >= 3600 ? 'H:i:s' : 'i:s', (int) $episode['duration'])
    : t('common.hd');
?>
<article class="stream-card"
         data-episode-card
         data-episode="<?= $episodeNumber ?>"
         data-season="<?= $seasonNumber ?>">
    <a class="stream-card__screen"
       href="<?= htmlspecialchars($watchUrl) ?>"
       aria-label="<?= htmlspecialchars(t('card.watch_aria', [
           'season' => $seasonNumber,
           'episode' => $seasonEpisode,
           'title' => (string) $episode['title'],
       ])) ?>">
        <?php if ($poster !== ''): ?>
            <img src="<?= htmlspecialchars($poster) ?>"
                 alt=""
                 loading="<?= htmlspecialchars($episodeCardLoading) ?>"
                 decoding="async">
        <?php else: ?>
            <span class="stream-card__fallback" aria-hidden="true"></span>
        <?php endif; ?>
        <span class="stream-card__wash" aria-hidden="true"></span>
        <span class="stream-card__flag">S<?= $seasonNumber ?> · E<?= $seasonEpisode ?></span>
        <span class="stream-card__quality"><?= htmlspecialchars($durationLabel) ?></span>
        <span class="stream-card__play" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="m9 7 8 5-8 5V7Z" fill="currentColor"/></svg>
        </span>
        <span class="stream-card__watched" data-watched-indicator>
            <svg viewBox="0 0 24 24"><path d="m6 12.5 3.4 3.4L18 7.8" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <?= te('card.watched') ?>
        </span>
    </a>
    <div class="stream-card__body">
        <p class="stream-card__kicker"><?= htmlspecialchars(t('card.kicker', [
            'episode' => $seasonEpisode,
            'place' => (string) ($episode['place'] ?? t('card.now_streaming')),
        ])) ?></p>
        <h3><a href="<?= htmlspecialchars($watchUrl) ?>"><?= htmlspecialchars($episode['short_title'] ?? $episode['title']) ?></a></h3>
        <?php if (!empty($episode['description'])): ?>
            <p class="stream-card__description"><?= htmlspecialchars($episode['description']) ?></p>
        <?php endif; ?>
        <div class="stream-card__meta">
            <span><?= te('common.arabic_audio') ?></span>
            <span><?= htmlspecialchars($episode['character'] ?? t('common.all_ages')) ?></span>
        </div>
    </div>
</article>
