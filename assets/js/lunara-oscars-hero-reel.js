/**
 * Oscars portal hero film loop (3.2.98): Wings and Sunrise, both 1927 Oscar
 * winners in the public domain.
 *
 * The <video> ships with no src, so it costs nothing on first paint. After the
 * page has loaded, and only for visitors who have not asked for reduced motion
 * or reduced data, the loop is attached and fades in behind the hero. It
 * pauses whenever the hero is off-screen or the tab is hidden.
 */
(function () {
    'use strict';

    var video = document.querySelector('[data-lunara-hero-reel]');
    if (!video || !video.getAttribute('data-src')) {
        return;
    }

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var saveData = navigator.connection && navigator.connection.saveData;
    // 3.2.100: phones. The stacked hero hides the reel below 820px (see the
    // matching rule in lunara-oscars-portal.css); do not download it either.
    var phone = window.matchMedia && window.matchMedia('(max-width: 820px)').matches;
    if (reduceMotion || saveData || phone) {
        return;
    }

    var visible = true;

    function play() {
        if (visible && !document.hidden) {
            var p = video.play();
            if (p && p.catch) {
                p.catch(function () {});
            }
        }
    }

    function start() {
        video.muted = true;
        video.src = video.getAttribute('data-src');
        video.addEventListener('playing', function () {
            video.parentNode.classList.add('is-playing');
        }, { once: true });
        play();

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                visible = entries[0].isIntersecting;
                if (visible) { play(); } else { video.pause(); }
            }).observe(video.parentNode);
        }
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) { video.pause(); } else { play(); }
        });
    }

    if (document.readyState === 'complete') {
        setTimeout(start, 400);
    } else {
        window.addEventListener('load', function () { setTimeout(start, 400); }, { once: true });
    }
})();
