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
    const previews = Array.from(document.querySelectorAll('.video-card__preview'));
    if (!previews.length) return;

    const supportsHover = window.matchMedia('(hover: hover)').matches;
    const stopPreviewHandlers = [];
    const hydrated = new WeakSet();

    const hydratePreview = (video) => {
        if (hydrated.has(video)) return;
        const src = String(video.dataset.previewSrc || '').trim();
        if (!src) return;
        video.src = src;
        video.load();
        hydrated.add(video);
    };

    const observer = 'IntersectionObserver' in window
        ? new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const video = entry.target;
                if (!(video instanceof HTMLVideoElement)) return;
                hydratePreview(video);
                observer.unobserve(video);
            });
        }, {
            rootMargin: '240px 0px 240px 0px',
            threshold: 0.01,
        })
        : null;

    previews.forEach((video, index) => {
        const card = video.closest('.video-card');
        if (!card) return;

        video.muted = true;
        video.loop = true;
        video.playsInline = true;
        video.preload = 'none';

        const markOrientation = () => {
            if (!video.videoWidth || !video.videoHeight) return;
            card.classList.toggle('video-card--preview-portrait', video.videoHeight > video.videoWidth);
        };

        const markReady = () => {
            card.classList.add('video-card--preview-ready');
        };

        video.addEventListener('loadedmetadata', markOrientation);
        video.addEventListener('loadeddata', markReady, { once: true });

        // Keep first two cards snappy, lazy-load everything else near viewport.
        if (index < 2) {
            hydratePreview(video);
        } else if (observer) {
            observer.observe(video);
        } else {
            hydratePreview(video);
        }

        const playPreview = () => {
            hydratePreview(video);
            if (video.dataset.previewPlaying === '1') return;
            video.dataset.previewPlaying = '1';

            card.classList.add('video-card--preview-playing');
            const playPromise = video.play();
            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(() => {
                    video.dataset.previewPlaying = '0';
                    card.classList.remove('video-card--preview-playing');
                });
            }
        };

        const stopPreview = () => {
            video.dataset.previewPlaying = '0';
            video.pause();
            card.classList.remove('video-card--preview-playing');
        };

        if (supportsHover) {
            card.addEventListener('mouseenter', playPreview);
            card.addEventListener('mouseleave', stopPreview);
        }

        card.addEventListener('focusin', playPreview);
        card.addEventListener('focusout', stopPreview);
        stopPreviewHandlers.push(stopPreview);

        if (video.readyState >= 1) {
            markOrientation();
        }
        if (video.readyState >= 2) {
            markReady();
        }
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
