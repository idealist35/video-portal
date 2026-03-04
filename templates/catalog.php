<?php if (!empty($error)): ?>
    <div class="error-page">
        <h1><?= htmlspecialchars($error) ?></h1>
        <a href="/" class="btn btn-primary">Back to Catalog</a>
    </div>
<?php else: ?>

<div class="catalog-header">
    <h1 class="catalog-title">Video Showcase</h1>
    <p class="catalog-subtitle">Latest projects and selected uploads</p>
</div>

<?php if (empty($videos)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">🎬</div>
        <h2>No videos yet</h2>
        <p>Content is coming soon. Stay tuned!</p>
    </div>
<?php else: ?>
    <div class="video-grid">
        <?php foreach ($videos as $video): ?>
            <?php
                $watchUrl = $video['watch_url'] ?? ('/watch/' . urlencode((string) $video['id']));
                $previewUrl = (string) ($video['preview_url'] ?? '');
                $duration = (int) ($video['duration'] ?? 0);
                $durationLabel = $duration >= 3600 ? gmdate('H:i:s', $duration) : gmdate('i:s', $duration);
            ?>
            <div class="video-card <?= $video['is_free'] ? 'video-card--free' : 'video-card--premium' ?>">
                <div class="video-card__thumbnail">
                    <?php if ($video['thumbnail']): ?>
                        <img src="<?= htmlspecialchars($video['thumbnail']) ?>" alt="<?= htmlspecialchars($video['title']) ?>">
                    <?php elseif ($previewUrl !== ''): ?>
                        <video
                            class="video-card__preview"
                            muted
                            loop
                            playsinline
                            preload="metadata"
                            src="<?= htmlspecialchars($previewUrl) ?>"
                            aria-hidden="true"
                        ></video>
                        <div class="video-card__preview-overlay"></div>
                        <div class="video-card__preview-play">▶</div>
                    <?php else: ?>
                        <div class="video-card__placeholder">▶</div>
                    <?php endif; ?>
                    <?php if ($video['is_free']): ?>
                        <span class="badge badge-free">Free</span>
                    <?php else: ?>
                        <span class="badge badge-premium">Premium</span>
                    <?php endif; ?>
                    <?php if ($duration > 0): ?>
                        <span class="video-card__duration"><?= $durationLabel ?></span>
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
    </div>
<?php endif; ?>

<?php endif; ?>
