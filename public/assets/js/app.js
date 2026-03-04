/**
 * Portal Frontend JS
 * 
 * Handles: animated particles, catalog previews, context menu protection on video.
 */

// ── Animated Background Particles ────────────────────────────

(function initParticles() {
    const container = document.getElementById('particles');
    if (!container) return;

    const PARTICLE_COUNT = 25;
    const colors = ['#a855f7', '#e040a0', '#6c2bd9', '#38bdf8', '#fbbf24'];

    for (let i = 0; i < PARTICLE_COUNT; i++) {
        const p = document.createElement('div');
        p.className = 'particle';

        const size = Math.random() * 4 + 2;
        const left = Math.random() * 100;
        const duration = Math.random() * 15 + 10;
        const delay = Math.random() * 15;
        const color = colors[Math.floor(Math.random() * colors.length)];

        p.style.cssText = `
            width: ${size}px;
            height: ${size}px;
            left: ${left}%;
            background: ${color};
            animation-duration: ${duration}s;
            animation-delay: ${delay}s;
            box-shadow: 0 0 ${size * 2}px ${color};
        `;

        container.appendChild(p);
    }
})();

// ── Local video card previews ────────────────────────────────

(function initCatalogPreviews() {
    const previews = document.querySelectorAll('.video-card__preview');
    if (!previews.length) return;

    const supportsHover = window.matchMedia('(hover: hover)').matches;
    const previewFrameSeconds = 0.35;
    const stopPreviewHandlers = [];

    function setPreviewFrame(video) {
        if (!Number.isFinite(video.duration) || video.duration <= 0) return;
        const target = Math.min(previewFrameSeconds, Math.max(video.duration - 0.1, 0));
        try {
            video.currentTime = target;
        } catch (error) {
            // Some browsers throw if seek happens too early.
        }
    }

    previews.forEach((video) => {
        const card = video.closest('.video-card');
        if (!card) return;

        video.muted = true;
        video.loop = true;
        video.playsInline = true;

        const onLoadedMetadata = () => setPreviewFrame(video);
        const onSeeked = () => {
            video.pause();
            video.dataset.previewReady = '1';
        };
        const markOrientation = () => {
            if (!video.videoWidth || !video.videoHeight) return;
            card.classList.toggle('video-card--preview-portrait', video.videoHeight > video.videoWidth);
        };

        video.addEventListener('loadedmetadata', onLoadedMetadata, { once: true });
        video.addEventListener('loadedmetadata', markOrientation, { once: true });
        video.addEventListener('seeked', onSeeked, { once: true });
        if (video.readyState >= 1) {
            setPreviewFrame(video);
            markOrientation();
        }

        const playPreview = () => {
            card.classList.add('video-card--preview-playing');
            try {
                video.currentTime = 0;
            } catch (error) {
                // Ignore seek errors and let browser continue from current frame.
            }
            const playPromise = video.play();
            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(() => {
                    card.classList.remove('video-card--preview-playing');
                });
            }
        };

        const stopPreview = () => {
            video.pause();
            card.classList.remove('video-card--preview-playing');
            if (video.dataset.previewReady === '1') {
                setPreviewFrame(video);
            }
        };

        if (supportsHover) {
            card.addEventListener('mouseenter', playPreview);
            card.addEventListener('mouseleave', stopPreview);
        }

        card.addEventListener('focusin', playPreview);
        card.addEventListener('focusout', stopPreview);
        stopPreviewHandlers.push(stopPreview);
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) return;
        stopPreviewHandlers.forEach((stop) => stop());
    });
})();

// ── Watch page sizing for portrait videos ────────────────────

(function tuneWatchPlayerSize() {
    const player = document.getElementById('videoPlayer');
    if (!player) return;

    const watchPage = player.closest('.watch-page');
    if (!watchPage) return;
    const forcedPortrait = watchPage.dataset.forcePortrait === '1';

    const applyLayout = () => {
        if (forcedPortrait) {
            watchPage.classList.add('watch-page--portrait');
            return;
        }

        if (!player.videoWidth || !player.videoHeight) return;
        const isPortrait = player.videoHeight > player.videoWidth;
        watchPage.classList.toggle('watch-page--portrait', isPortrait);
    };

    player.addEventListener('loadedmetadata', applyLayout);
    if (player.readyState >= 1) {
        applyLayout();
    }
})();

// ── Disable right-click on video (basic download protection) ─

document.addEventListener('contextmenu', function(e) {
    if (e.target.closest('.video-player')) {
        e.preventDefault();
    }
});
