/**
 * Hikaya Watch interactions.
 * Watch progress remains local to the browser and only controls presentation.
 */

// Strings come from the server so this file holds no language of its own; the
// fallbacks only matter if the payload fails to render.
const I18N = window.HikayaI18n || {};
function s(key, fallback, values) {
    let text = I18N[key] || fallback;
    if (values) {
        for (const name of Object.keys(values)) {
            text = text.split(`{${name}}`).join(values[name]);
        }
    }
    return text;
}

const STORY_PROGRESS_KEY = 'barikStoryProgressV2';
const OASIS_STORIES = [
    { number: 1, season: 1, episode: 1, slug: 'camel-who-collected-laughter', title: 'Collected Laughter' },
    { number: 2, season: 1, episode: 2, slug: 'mysterious-gift', title: 'The Mysterious Gift' },
    { number: 3, season: 1, episode: 3, slug: 'operation-tickle', title: 'Operation Tickle' },
    { number: 4, season: 1, episode: 4, slug: 'bitterest-date', title: 'The Bitterest Date' },
    { number: 5, season: 1, episode: 5, slug: 'everyone-swapped-places', title: 'Everyone Swapped Places' },
    { number: 6, season: 1, episode: 6, slug: 'cafe-for-lizards', title: 'The Cafe for Lizards' },
    { number: 7, season: 1, episode: 7, slug: 'great-wind-hunt', title: 'The Great Wind Hunt' },
    { number: 8, season: 1, episode: 8, slug: 'sea-went-for-a-walk', title: 'The Sea Went for a Walk' },
    { number: 9, season: 1, episode: 9, slug: 'night-of-a-thousand-stars', title: 'A Thousand Stars' },
    { number: 10, season: 1, episode: 10, slug: 'the-giants-footprints', title: 'The Giant\'s Footprints' },
    { number: 11, season: 2, episode: 1, slug: 'coral-bay', title: 'Coral Bay' },
    { number: 12, season: 2, episode: 2, slug: 'why-corals-must-not-be-hugged', title: 'Do Not Hug the Corals' },
    { number: 13, season: 2, episode: 3, slug: 'riddle-of-the-eight-shells', title: 'The Eight Shells' },
    { number: 14, season: 2, episode: 4, slug: 'everyone-must-get-home', title: 'Everyone Must Get Home' },
    { number: 15, season: 2, episode: 5, slug: 'when-the-sun-comes-to-visit', title: 'When the Sun Visits' },
    { number: 16, season: 2, episode: 6, slug: 'big-moving-day', title: 'The Big Moving Day' },
    { number: 17, season: 2, episode: 7, slug: 'following-the-sea', title: 'Following the Sea' },
    { number: 18, season: 2, episode: 8, slug: 'when-the-wind-hid-the-sun', title: 'The Wind Hid the Sun' },
    { number: 19, season: 2, episode: 9, slug: 'big-crab-races', title: 'The Big Crab Races' },
    { number: 20, season: 2, episode: 10, slug: 'when-i-knew-you-were-my-friend', title: 'I Knew You Were My Friend' },
];

function getStoryContext() {
    const root = document.querySelector('[data-story-world]');
    const worldSlug = root?.dataset.storyWorld || 'oasis';
    let stories = OASIS_STORIES;

    if (root?.dataset.storyManifest) {
        try {
            const parsed = JSON.parse(root.dataset.storyManifest);
            if (Array.isArray(parsed) && parsed.length) {
                stories = parsed
                    .map((story) => ({
                        number: Number(story.number),
                        season: Number(story.season),
                        episode: Number(story.episode),
                        slug: String(story.slug || ''),
                        title: String(story.title || ''),
                    }))
                    .filter((story) =>
                        Number.isInteger(story.number)
                        && Number.isInteger(story.season)
                        && Number.isInteger(story.episode)
                        && story.slug
                    );
            }
        } catch (error) {
            stories = OASIS_STORIES;
        }
    }

    return {
        worldSlug,
        stories,
        progressKey: worldSlug === 'oasis' ? STORY_PROGRESS_KEY : `hikayaStoryProgressV1:${worldSlug}`,
        worldUrl: `/world/${worldSlug}#episodes`,
    };
}

function getCompletedStories() {
    const context = getStoryContext();
    const validNumbers = new Set(context.stories.map((story) => story.number));
    try {
        const parsed = JSON.parse(localStorage.getItem(context.progressKey) || '[]');
        if (!Array.isArray(parsed)) return [];
        return Array.from(new Set(
            parsed
                .map(Number)
                .filter((value) => Number.isInteger(value) && validNumbers.has(value))
        )).sort((a, b) => a - b);
    } catch (error) {
        return [];
    }
}

function saveCompletedStory(number) {
    const context = getStoryContext();
    const completed = getCompletedStories();
    if (!completed.includes(number)) completed.push(number);
    completed.sort((a, b) => a - b);
    try {
        localStorage.setItem(context.progressKey, JSON.stringify(completed));
    } catch (error) {
        // The current page still receives the visual completion state.
    }
    return completed;
}

let toastTimer = null;
function showToast(message) {
    const toast = document.querySelector('[data-toast]');
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('is-visible');
    if (toastTimer) window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toast.classList.remove('is-visible'), 2800);
}

function refreshWatchProgress(explicitCompleted) {
    const context = getStoryContext();
    const stories = context.stories;
    const completed = explicitCompleted || getCompletedStories();
    const watchedCount = completed.length;
    const percent = stories.length ? (watchedCount / stories.length) * 100 : 0;

    document.querySelectorAll('[data-progress-label]').forEach((label) => {
        label.textContent = s('progress', '{watched} / {total} watched', { watched: watchedCount, total: stories.length });
    });

    document.querySelectorAll('[data-progress-bar]').forEach((bar) => {
        bar.style.width = `${percent}%`;
    });

    document.querySelectorAll('[data-season-progress]').forEach((label) => {
        const season = Number(label.dataset.seasonProgress);
        const seasonStories = stories.filter((story) => story.season === season);
        const seasonWatched = seasonStories.filter((story) => completed.includes(story.number)).length;
        label.textContent = s('progress', '{watched} / {total} watched', { watched: seasonWatched, total: seasonStories.length });
    });

    document.querySelectorAll('[data-episode-card]').forEach((card) => {
        const number = Number(card.dataset.episode);
        card.classList.toggle('is-watched', completed.includes(number));
    });

    document.querySelectorAll('[data-progress-dot]').forEach((dot) => {
        const number = Number(dot.dataset.progressDot);
        dot.classList.toggle('is-complete', completed.includes(number));
        dot.classList.remove('is-current');
    });

    const watchPage = document.querySelector('[data-story-episode]');
    if (watchPage) {
        const currentNumber = Number(watchPage.dataset.storyEpisode);
        const currentDot = document.querySelector(`[data-progress-dot="${currentNumber}"]`);
        if (currentDot) currentDot.classList.add('is-current');
    }

    const nextStory = stories.find((story) => !completed.includes(story.number));
    document.querySelectorAll('[data-continue-link]').forEach((continueLink) => {
        const continueCopy = continueLink.querySelector('[data-continue-copy]');
        if (nextStory) {
            continueLink.href = `/story/${nextStory.slug}`;
            if (continueCopy) {
                continueCopy.textContent = watchedCount
                    ? s('continueWith', 'Continue with {title}', { title: nextStory.title })
                    : s('startWatching', 'Start watching');
            }
        } else {
            continueLink.href = context.worldUrl;
            if (continueCopy) continueCopy.textContent = s('replayAny', 'Replay any episode');
        }
    });

    return completed;
}

(function initNavigation() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const panel = document.querySelector('[data-nav-panel]');
    const header = document.querySelector('[data-site-header]');
    if (toggle && panel) {
        const close = () => {
            panel.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            const label = toggle.querySelector('.sr-only');
            if (label) label.textContent = s('openNavigation', 'Open navigation');
        };

        toggle.addEventListener('click', () => {
            const isOpen = !panel.classList.contains('is-open');
            panel.classList.toggle('is-open', isOpen);
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            const label = toggle.querySelector('.sr-only');
            if (label) label.textContent = isOpen ? s('closeNavigation', 'Close navigation') : s('openNavigation', 'Open navigation');
        });

        panel.querySelectorAll('a').forEach((link) => link.addEventListener('click', close));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') close();
        });
    }

    if (header) {
        const syncHeader = () => header.classList.toggle('is-scrolled', window.scrollY > 18);
        window.addEventListener('scroll', syncHeader, { passive: true });
        syncHeader();
    }
})();

(function initRandomStories() {
    const buttons = document.querySelectorAll('[data-random-story]');
    if (!buttons.length) return;
    const context = getStoryContext();
    const currentSlug = document.querySelector('[data-story-slug]')?.dataset.storySlug || '';
    const choices = context.stories.filter((story) => story.slug !== currentSlug);
    if (!choices.length) return;

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const randomValues = new Uint32Array(1);
            const index = window.crypto?.getRandomValues
                ? (window.crypto.getRandomValues(randomValues), randomValues[0] % choices.length)
                : Math.floor(Math.random() * choices.length);
            window.location.assign(`/story/${choices[index].slug}`);
        });
    });
})();

(function initSeasonFilters() {
    const buttons = Array.from(document.querySelectorAll('[data-season-filter]'));
    const panels = Array.from(document.querySelectorAll('[data-season-panel]'));
    if (!buttons.length || !panels.length) return;

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const selected = button.dataset.seasonFilter || 'all';
            buttons.forEach((item) => {
                const active = item === button;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            panels.forEach((panel) => {
                panel.hidden = selected !== 'all' && panel.dataset.seasonPanel !== selected;
            });
        });
    });
})();

(function initCastProfiles() {
    document.querySelectorAll('[data-cast-card]').forEach((card) => {
        const toggle = card.querySelector('[data-cast-toggle]');
        const profile = card.querySelector('[data-cast-profile]');
        if (!toggle || !profile) return;

        toggle.addEventListener('click', () => {
            const opening = profile.hidden;
            profile.hidden = !opening;
            card.classList.toggle('is-open', opening);
            toggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
            toggle.firstChild.textContent = `${opening ? s('closeProfile', 'Close profile') : s('viewProfile', 'View profile')} `;
        });
    });
})();

(function initWatchPlayer() {
    const player = document.getElementById('videoPlayer');
    if (!player) return;
    const page = player.closest('.watch-page');
    const wrapper = player.closest('.watch-player');
    const loading = document.getElementById('playerLoading');
    const toggle = wrapper?.querySelector('[data-player-toggle]');
    if (!page || !wrapper) return;

    // iOS Safari ignores preload="metadata" and drops to preload="none" on
    // cellular or in Low Data Mode: it fires loadstart and then stops, so
    // loadedmetadata and canplay never arrive. Showing the spinner on
    // loadstart therefore left it up forever. Only show it once playback has
    // actually been asked for, and clear it on every event that means the
    // browser has stopped fetching.
    let playbackRequested = false;

    const showLoading = () => {
        if (!playbackRequested) return;
        if (loading) loading.hidden = false;
        wrapper.classList.add('is-loading');
    };
    const hideLoading = () => {
        if (loading) loading.hidden = true;
        wrapper.classList.remove('is-loading');
    };
    const syncPlayback = () => {
        const playing = !player.paused && !player.ended;
        wrapper.classList.toggle('is-playing', playing);
        if (toggle) toggle.setAttribute('aria-label', playing ? s('pauseVideo', 'Pause video') : s('playVideo', 'Play video'));
    };

    player.addEventListener('loadstart', showLoading);
    player.addEventListener('waiting', showLoading);
    player.addEventListener('stalled', showLoading);
    ['loadedmetadata', 'loadeddata', 'canplay', 'suspend', 'abort', 'emptied', 'error']
        .forEach((name) => player.addEventListener(name, hideLoading));
    player.addEventListener('play', () => {
        playbackRequested = true;
    });
    player.addEventListener('playing', () => {
        hideLoading();
        syncPlayback();
    });
    player.addEventListener('pause', () => {
        hideLoading();
        syncPlayback();
    });

    const requestPlayback = () => {
        playbackRequested = true;
        // With preload suppressed, iOS has fetched nothing yet; nudge it first.
        if (player.networkState === HTMLMediaElement.NETWORK_EMPTY) player.load();
        const playPromise = player.play();
        if (playPromise && typeof playPromise.catch === 'function') {
            playPromise.catch(() => {
                hideLoading();
                showToast(s('pressPlay', 'Press play in the video controls to start.'));
            });
        }
    };

    if (toggle) {
        toggle.addEventListener('click', () => {
            if (player.paused || player.ended) {
                requestPlayback();
            } else {
                player.pause();
            }
        });
    }

    const applyOrientation = () => {
        if (page.dataset.forcePortrait === '1') {
            page.classList.add('watch-page--portrait');
            return;
        }
        if (!player.videoWidth || !player.videoHeight) return;
        page.classList.toggle('watch-page--portrait', player.videoHeight > player.videoWidth);
    };
    player.addEventListener('loadedmetadata', applyOrientation);

    const episodeNumber = Number(page.dataset.storyEpisode || 0);
    player.addEventListener('ended', () => {
        syncPlayback();
        if (!episodeNumber) return;
        const completed = saveCompletedStory(episodeNumber);
        refreshWatchProgress(completed);
        const storyContext = getStoryContext();
        const story = storyContext.stories.find((item) => item.number === episodeNumber);
        const lastAvailableStory = storyContext.stories[storyContext.stories.length - 1];
        const note = document.querySelector('[data-complete-note]');
        if (note) {
            note.textContent = episodeNumber === lastAvailableStory?.number
                ? s('allReadyReplay', 'Every available episode is ready to replay.')
                : s('episodeWatched', 'Episode watched. Your next stream is ready.');
            note.classList.add('is-complete');
        }
        showToast(story
            ? s('markedWatched', '{title} marked as watched', { title: story.title })
            : s('markedGeneric', 'Episode marked as watched'));
    });

    if (player.readyState >= 1) {
        hideLoading();
        applyOrientation();
    }
    syncPlayback();
})();

(function initBackToTop() {
    const button = document.querySelector('[data-back-to-top]');
    if (!button) return;
    const refresh = () => {
        const visible = window.scrollY > 700;
        button.classList.toggle('is-visible', visible);
        button.setAttribute('aria-hidden', visible ? 'false' : 'true');
        button.tabIndex = visible ? 0 : -1;
    };
    window.addEventListener('scroll', refresh, { passive: true });
    button.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    refresh();
})();

refreshWatchProgress();

document.addEventListener('contextmenu', (event) => {
    if (event.target.closest('.video-player')) event.preventDefault();
});
