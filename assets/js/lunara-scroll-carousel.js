/**
 * Generated from the verified inline runtime during Phase 1C.
 * Keep behavior route-gated and free of render-blocking dependencies.
 */

document.addEventListener('DOMContentLoaded', function () {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('[data-lunara-carousel]').forEach(function(section) {
            const track = section.querySelector('[data-lunara-carousel-track]');
            const prev = section.querySelector('[data-lunara-carousel-prev]');
            const next = section.querySelector('[data-lunara-carousel-next]');
            const dots = Array.from(section.querySelectorAll('[data-lunara-carousel-dot]'));
            const toggle = section.querySelector('[data-lunara-carousel-toggle]');
            if (!track) return;
            if (toggle && reduceMotion) {
                toggle.disabled = true;
                toggle.setAttribute('aria-disabled', 'true');
                toggle.setAttribute('aria-label', 'Autoplay disabled for reduced motion');
            }
            function amount() {
                const card = track.children[0];
                const styles = window.getComputedStyle(track);
                const gap = parseInt(styles.columnGap || styles.gap || 24, 10);
                return card ? card.offsetWidth + gap : 360;
            }
            function getIndexFromOffset(scrollLeft) {
                const step = amount();
                if (!step) {
                    return 0;
                }
                const total = track.children.length;
                return Math.min(total - 1, Math.max(0, Math.round(scrollLeft / step)));
            }
            function syncDots() {
                if (!dots.length) {
                    return;
                }
                const activeIndex = getIndexFromOffset(track.scrollLeft);
                dots.forEach(function(dot, index) {
                    const active = index === activeIndex;
                    dot.classList.toggle('active', active);
                    dot.setAttribute('aria-selected', active ? 'true' : 'false');
                });
            }
            function scrollToIndex(index) {
                const target = Number.isInteger(index) ? index : 0;
                const cards = track.children;
                if (!cards.length) {
                    return;
                }
                const clampedIndex = Math.min(cards.length - 1, Math.max(0, target));
                const card = cards[clampedIndex];
                if (!card || !card.scrollIntoView) {
                    return;
                }
                const trackRect = track.getBoundingClientRect();
                const cardRect = card.getBoundingClientRect();
                const targetLeft = track.scrollLeft + cardRect.left - trackRect.left;
                track.scrollTo({
                    left: Math.max(0, targetLeft),
                    behavior: reduceMotion ? 'auto' : 'smooth'
                });
            }
            function step(direction) {
                const distance = amount() * direction;
                const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
                const behavior = reduceMotion ? 'auto' : 'smooth';
                // Reach the final card before wrapping on the following advance.
                if (direction > 0 && track.scrollLeft >= maxScroll - 6) {
                    track.scrollTo({ left: 0, behavior: behavior });
                    return;
                }
                if (direction < 0 && track.scrollLeft <= 6) {
                    track.scrollTo({ left: maxScroll, behavior: behavior });
                    return;
                }
                track.scrollBy({ left: distance, behavior: behavior });
            }
            if (prev) {
                prev.addEventListener('click', function () {
                    step(-1);
                });
            }
            if (next) {
                next.addEventListener('click', function () {
                    step(1);
                });
            }
            if (dots.length) {
                dots.forEach(function(dot, index) {
                    dot.addEventListener('click', function() {
                        scrollToIndex(index);
                    });
                });
            }

            section.addEventListener('keydown', function(event) {
                if ('ArrowLeft' === event.key) {
                    event.preventDefault();
                    step(-1);
                } else if ('ArrowRight' === event.key) {
                    event.preventDefault();
                    step(1);
                }
            });

            let syncRaf = null;
            track.addEventListener('scroll', function () {
                if (!dots.length) {
                    return;
                }
                if (syncRaf) {
                    window.cancelAnimationFrame(syncRaf);
                }
                syncRaf = window.requestAnimationFrame(function () {
                    syncDots();
                    syncRaf = null;
                });
            }, { passive: true });

            const autoplay = parseInt(section.getAttribute('data-lunara-carousel-autoplay') || '0', 10);
            const allowMobileAutoplay = !!toggle;
            let timer = null;
            let userPaused = false;
            let pointerHover = false;
            let focusWithin = false;

            function syncToggle() {
                if (!toggle) {
                    return;
                }
                const paused = userPaused || reduceMotion || autoplay <= 0;
                toggle.textContent = paused ? 'Play' : 'Pause';
                toggle.setAttribute('aria-label', reduceMotion ? 'Autoplay disabled for reduced motion' : (paused ? 'Play Oscar Picks rotation' : 'Pause Oscar Picks rotation'));
                toggle.setAttribute('aria-pressed', userPaused ? 'true' : 'false');
                toggle.classList.toggle('is-paused', paused);
            }

            function stop() {
                if (timer) {
                    window.clearInterval(timer);
                    timer = null;
                }
            }

            function start() {
                stop();
                if (userPaused || reduceMotion || autoplay <= 0 || track.children.length < 2 || (!allowMobileAutoplay && window.innerWidth <= 900) || pointerHover || focusWithin) {
                    syncToggle();
                    return;
                }
                timer = window.setInterval(function () {
                    step(1);
                }, autoplay);
                syncToggle();
            }

            function resumeWhenAvailable() {
                if (pointerHover || focusWithin || userPaused) {
                    stop();
                    syncToggle();
                    return;
                }
                start();
            }

            if (toggle) {
                toggle.addEventListener('click', function () {
                    userPaused = !userPaused;
                    if (userPaused) {
                        stop();
                    } else {
                        start();
                    }
                    syncToggle();
                });
            }

            // Pause on real mouse hover and while keyboard focus is inside the
            // section. Touch users can still swipe and autoplay resumes after
            // the gesture ends. A user pause always wins over these temporary
            // interaction pauses.
            section.addEventListener('pointerenter', function (event) {
                if (event.pointerType === 'mouse') {
                    pointerHover = true;
                    stop();
                }
            });
            section.addEventListener('pointerleave', function (event) {
                if (event.pointerType === 'mouse') {
                    pointerHover = false;
                    resumeWhenAvailable();
                }
            });
            section.addEventListener('focusin', function () {
                focusWithin = true;
                stop();
            });
            section.addEventListener('focusout', function () {
                window.setTimeout(function () {
                    if (!section.contains(document.activeElement)) {
                        focusWithin = false;
                        resumeWhenAvailable();
                    }
                }, 0);
            });
            track.addEventListener('touchstart', stop, { passive: true });
            track.addEventListener('touchend', resumeWhenAvailable, { passive: true });
            track.addEventListener('touchcancel', resumeWhenAvailable, { passive: true });
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) {
                    stop();
                } else {
                    resumeWhenAvailable();
                }
            });
            syncToggle();
            start();

            // The first dot is already marked active in server-rendered HTML.
            // Defer geometry reads until the rail actually scrolls or a reader
            // uses a control; this keeps below-fold carousels out of first paint.
        });
    });
