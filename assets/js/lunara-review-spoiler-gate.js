/**
 * Full-spoiler Review click-through gate.
 * Restored from the verified Theme 3.1.94 inline runtime as a cacheable asset.
 */

(function () {
    function keyFor(postId) {
        return 'lunaraFullSpoilerAcknowledged:' + postId;
    }

    function getStored(postId) {
        try {
            return window.sessionStorage && window.sessionStorage.getItem(keyFor(postId)) === '1';
        } catch (error) {
            return false;
        }
    }

    function setStored(postId) {
        try {
            if (window.sessionStorage) {
                window.sessionStorage.setItem(keyFor(postId), '1');
            }
        } catch (error) {
            return false;
        }

        return true;
    }

    function getProtected(postId) {
        var nodes = document.querySelectorAll('[data-lunara-spoiler-protected]');
        return Array.prototype.filter.call(nodes, function (node) {
            return node.getAttribute('data-lunara-spoiler-post') === String(postId);
        });
    }

    function applyState(shield, isRevealed) {
        var postId = shield.getAttribute('data-lunara-spoiler-post');
        var protectedNodes = getProtected(postId);

        shield.classList.toggle('is-acknowledged', isRevealed);
        shield.setAttribute('aria-hidden', isRevealed ? 'true' : 'false');

        protectedNodes.forEach(function (node) {
            node.classList.toggle('is-revealed', isRevealed);
            node.setAttribute('aria-hidden', isRevealed ? 'false' : 'true');
        });
    }

    function initSpoilerShields() {
        var shields = document.querySelectorAll('[data-lunara-spoiler-shield]');
        if (!shields.length || !document.body) {
            return;
        }

        document.body.classList.add('has-lunara-spoiler-gate');

        shields.forEach(function (shield) {
            var postId = shield.getAttribute('data-lunara-spoiler-post');
            var button = shield.querySelector('[data-lunara-spoiler-reveal]');

            applyState(shield, getStored(postId));

            if (!button) {
                return;
            }

            button.addEventListener('click', function () {
                setStored(postId);
                applyState(shield, true);

                var firstProtected = getProtected(postId)[0];
                if (firstProtected) {
                    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    firstProtected.setAttribute('tabindex', '-1');
                    firstProtected.focus({ preventScroll: true });
                    firstProtected.scrollIntoView({ block: 'start', behavior: reducedMotion ? 'auto' : 'smooth' });
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSpoilerShields);
    } else {
        initSpoilerShields();
    }
}());
