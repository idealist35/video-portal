<?php $forcePortrait = (($video['aspect_ratio'] ?? '') === '9:16'); ?>
<div class="watch-page<?= $forcePortrait ? ' watch-page--portrait' : '' ?>" data-force-portrait="<?= $forcePortrait ? '1' : '0' ?>">
    <div class="player-container">
        <video id="videoPlayer" class="video-player" controls preload="metadata"
               controlsList="nodownload">
            <source src="<?= htmlspecialchars($videoUrl) ?>" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>

    <div class="video-info">
        <h1 class="video-info__title"><?= htmlspecialchars($video['title']) ?></h1>
        <?php if ($video['category']): ?>
            <span class="video-card__category"><?= htmlspecialchars($video['category']) ?></span>
        <?php endif; ?>
        <?php if ($video['description']): ?>
            <p class="video-info__desc"><?= nl2br(htmlspecialchars($video['description'])) ?></p>
        <?php endif; ?>
    </div>

    <a href="/" class="btn btn-outline btn-back">← Back to Catalog</a>
</div>
