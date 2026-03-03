/**
 * Portal Frontend JS
 * 
 * Handles: animated particles, context menu protection on video.
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

// ── Disable right-click on video (basic download protection) ─

document.addEventListener('contextmenu', function(e) {
    if (e.target.closest('.video-player')) {
        e.preventDefault();
    }
});
