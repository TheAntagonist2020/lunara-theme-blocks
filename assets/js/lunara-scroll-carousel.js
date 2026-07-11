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
            if (!track) return;
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
                if (direction > 0 && track.scrollLeft + distance >= maxScroll - 6) {
                    track.scrollTo({ left: 0, behavior: 'smooth' });
                    return;
                }
                if (direction < 0 && track.scrollLeft <= 6) {
                    track.scrollTo({ left: maxScroll, behavior: 'smooth' });
                    return;
                }
                track.scrollBy({ left: distance, behavior: 'smooth' });
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
            if (!reduceMotion && autoplay > 0 && track.children.length > 1 && window.innerWidth > 900) {
                let timer = null;
                const stop = function () {
                    if (timer) {
                        window.clearInterval(timer);
                        timer = null;
                    }
                };
                const start = function () {
                    stop();
                    timer = window.setInterval(function () {
                        step(1);
                    }, autoplay);
                };
                section.addEventListener('mouseenter', stop);
                section.addEventListener('mouseleave', start);
                section.addEventListener('focusin', stop);
                section.addEventListener('focusout', start);
                document.addEventListener('visibilitychange', function () {
                    if (document.hidden) {
                        stop();
                    } else {
                        start();
                    }
                });
                start();
            }
            syncDots();
        });
    });
