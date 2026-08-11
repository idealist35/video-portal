<?php
/** Renders a list of video cards. Used by catalog.php and by /catalog-more (Load more). */
foreach ($videos as $video):
    $watchUrl = $video['watch_url'] ?? ('/watch/' . urlencode((string) $video['id']));
    $previewUrl = (string) ($video['preview_url'] ?? '');
    $duration = (int) ($video['duration'] ?? 0);
    $durationLabel = $duration >= 3600 ? gmdate('H:i:s', $duration) : gmdate('i:s', $duration);
    $isPortrait = (($video['aspect_ratio'] ?? '') === '9:16');
?>
<div class="video-card <?= $video['is_free'] ? 'video-card--free' : 'video-card--premium' ?><?= $isPortrait ? ' video-card--preview-portrait' : '' ?>">
    <div class="video-card__thumbnail">
        <?php if ($previewUrl !== ''): ?>
            <?php if ($video['thumbnail']): ?>
                <img class="video-card__poster" src="<?= htmlspecialchars($video['thumbnail']) ?>" alt="" aria-hidden="true">
            <?php else: ?>
                <div class="video-card__preview-fallback" aria-hidden="true">▶</div>
            <?php endif; ?>
            <video
                class="video-card__preview"
                muted
                loop
                playsinline
                preload="none"
                data-preview-src="<?= htmlspecialchars($previewUrl) ?>"
                aria-hidden="true"
            ></video>
            <div class="video-card__preview-overlay"></div>
            <div class="video-card__preview-play">▶</div>
            <div class="video-card__preview-loading" aria-hidden="true">
                <span class="video-card__preview-loading-bar"></span>
            </div>
        <?php elseif ($video['thumbnail']): ?>
            <img src="<?= htmlspecialchars($video['thumbnail']) ?>" alt="<?= htmlspecialchars($video['title']) ?>">
        <?php else: ?>
            <div class="video-card__placeholder">▶</div>
        <?php endif; ?>

        <button class="video-card__mute-btn" aria-label="<?= te('video.toggle_mute') ?>" style="display: none;">
            <svg class="icon-muted" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                <line x1="23" y1="9" x2="17" y2="15"></line>
                <line x1="17" y1="9" x2="23" y2="15"></line>
            </svg>
            <svg class="icon-unmuted" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                <path d="M19.07 4.93a10 10 0 0 1 0 14.14"></path>
            </svg>
        </button>
        <div class="video-card__progress-container" style="display: none;">
            <div class="video-card__progress-bar"></div>
        </div>

        <?php if (!$video['is_free']): ?>
            <span class="badge badge-premium"><?= te('video.premium') ?></span>
        <?php endif; ?>
    </div>
    <div class="video-card__body">
        <h3 class="video-card__title"><?= htmlspecialchars($video['title']) ?></h3>
        <?php if ($video['description']): ?>
            <p class="video-card__desc"><?= htmlspecialchars(mb_strimwidth($video['description'], 0, 100, '...')) ?></p>
        <?php endif; ?>
        <?php if ($video['category']): ?>
            <span class="video-card__category"><?= htmlspecialchars($video['category']) ?></span>
        <?php endif; ?>
    </div>
    <a href="<?= htmlspecialchars($watchUrl) ?>" class="video-card__link"></a>
</div>
<?php endforeach; ?>
