/**
 * Lunara Carousel — dependency-free slider with mobile-correct behavior.
 *
 * Fixes vs the previous version:
 *   1. Pause-on-hover uses pointer events with pointerType check, so a mobile
 *      tap no longer kills autoplay forever.
 *   2. Swipe-left / swipe-right gestures navigate slides on touch devices.
 *   3. Initialization waits for DOMContentLoaded (or runs immediately if the
 *      DOM is already parsed) — no more race conditions.
 *   4. Respects prefers-reduced-motion: stops autoplay for users who ask for
 *      reduced motion.
 *   5. Keyboard arrow-key navigation when the carousel has focus.
 *
 * HTML/CSS contract is unchanged:
 *   .lunara-carousel              — outer container; data-autoplay="ms" optional
 *   .lunara-carousel-slide        — slide; .active class drives visibility via CSS
 *   .lunara-carousel-dot          — pagination dot; .active class for current dot
 */

(function () {
	'use strict';

	var SWIPE_THRESHOLD_PX = 40;     // minimum horizontal travel for a swipe
	var SWIPE_VERT_LIMIT_PX = 60;    // ignore if vertical travel exceeds this
	var prefersReducedMotion = window.matchMedia &&
		window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function init(root) {
		var slides = Array.prototype.slice.call(
			root.querySelectorAll('.lunara-carousel-slide')
		);
		if (!slides.length) return;

		var dots = Array.prototype.slice.call(
			root.querySelectorAll('.lunara-carousel-dot')
		);

		var current = 0;
		var autoplayMs = parseInt(root.dataset.autoplay || '5000', 10);
		var timer = null;

		function show(n) {
			current = (n + slides.length) % slides.length;
			slides.forEach(function (el, k) {
				el.classList.toggle('active', k === current);
			});
			dots.forEach(function (el, k) {
				el.classList.toggle('active', k === current);
			});
		}

		function startAutoplay() {
			if (autoplayMs > 0 && slides.length > 1 && !timer && !prefersReducedMotion) {
				timer = setInterval(function () { show(current + 1); }, autoplayMs);
			}
		}

		function stopAutoplay() {
			if (timer) { clearInterval(timer); timer = null; }
		}

		// Make the root focusable for keyboard nav, but not in normal tab flow
		// unless an a11y review elevates this later.
		if (!root.hasAttribute('tabindex')) {
			root.setAttribute('tabindex', '-1');
		}
		if (!root.hasAttribute('aria-roledescription')) {
			root.setAttribute('aria-roledescription', 'carousel');
		}

		// Dot navigation
		dots.forEach(function (el, k) {
			el.addEventListener('click', function () {
				show(k);
				stopAutoplay(); startAutoplay(); // reset cadence after manual nav
			});
		});

		// Pause-on-hover — but ONLY for actual mouse pointers.
		// pointerenter/leave with pointerType === 'mouse' avoids the mobile-tap trap.
		root.addEventListener('pointerenter', function (e) {
			if (e.pointerType === 'mouse') stopAutoplay();
		});
		root.addEventListener('pointerleave', function (e) {
			if (e.pointerType === 'mouse') startAutoplay();
		});

		// Touch swipe gestures
		var touchStartX = 0, touchStartY = 0, touchActive = false;
		root.addEventListener('touchstart', function (e) {
			if (!e.touches || !e.touches[0]) return;
			touchStartX = e.touches[0].clientX;
			touchStartY = e.touches[0].clientY;
			touchActive = true;
		}, { passive: true });

		root.addEventListener('touchend', function (e) {
			if (!touchActive) return;
			touchActive = false;
			var t = e.changedTouches && e.changedTouches[0];
			if (!t) return;
			var dx = t.clientX - touchStartX;
			var dy = t.clientY - touchStartY;
			if (Math.abs(dy) > SWIPE_VERT_LIMIT_PX) return;     // vertical scroll, not a swipe
			if (Math.abs(dx) < SWIPE_THRESHOLD_PX) return;      // too short to be a swipe
			show(current + (dx < 0 ? 1 : -1));
			stopAutoplay(); startAutoplay();
		});

		// Keyboard arrows (when carousel has focus)
		root.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowRight') { show(current + 1); stopAutoplay(); startAutoplay(); }
			else if (e.key === 'ArrowLeft') { show(current - 1); stopAutoplay(); startAutoplay(); }
		});

		// Pause autoplay when the tab is hidden — saves battery and
		// prevents a 30-slide jump-back when the user returns.
		document.addEventListener('visibilitychange', function () {
			if (document.hidden) stopAutoplay();
			else startAutoplay();
		});

		startAutoplay();
	}

	function bootAll() {
		document.querySelectorAll('.lunara-carousel').forEach(init);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bootAll);
	} else {
		bootAll();
	}

	// Hide any "X / Y" fraction counter overlay (preserved from original behavior).
	function hideCounters() {
		document.querySelectorAll('.lunara-carousel').forEach(function (root) {
			root.querySelectorAll(
				'.swiper-pagination-fraction,.splide__pagination__counter,' +
				'.slick-counter,.lunara-carousel-count,.lunara-slide-count,' +
				'[data-slide-count]'
			).forEach(function (el) { el.style.display = 'none'; });

			root.querySelectorAll('*').forEach(function (el) {
				if (el.children.length === 0 &&
					/^\d+\s*\/\s*\d+$/.test((el.textContent || '').trim())) {
					el.style.display = 'none';
				}
			});
		});
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', hideCounters);
	} else {
		hideCounters();
	}
})();
