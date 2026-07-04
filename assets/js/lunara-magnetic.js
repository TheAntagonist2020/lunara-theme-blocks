/**
 * Lunara Magnetic Interaction System (Design Spec 2.0 §10).
 *
 * High-priority interactive surfaces gain a fine magnetic pull toward the
 * cursor. Implementation notes that matter:
 *
 * - Uses the individual `translate` CSS property, NOT `transform`, so the
 *   pull composes cleanly with existing transforms (the hero arrows'
 *   translateY(-50%) centering, the CTA's hover lift) instead of clobbering
 *   them. Browsers without the property simply ignore it — pure enhancement.
 * - The spring-back runs through the Web Animations API rather than a CSS
 *   transition, so no element's carefully tuned transition stack is touched.
 * - Desktop fine-pointer only, and fully disabled under reduced motion.
 */
(function () {
	'use strict';

	if (!window.matchMedia) { return; }
	if (!matchMedia('(hover: hover) and (pointer: fine)').matches) { return; }
	if (matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }

	var SELECTOR = [
		'.lunara-cinematic-hero-cta',
		'.lunara-hero-arrow',
		'.lunara-section-link',
		'.lunara-archive-hero-kicker',
		'.lunara-pairing-desk-claim'
	].join(',');

	var PULL = 0.22;  // fraction of cursor offset transferred to the element
	var MAX = 8;      // px clamp so large targets never lurch

	function arm(el) {
		var raf = 0;

		el.addEventListener('pointermove', function (e) {
			if (raf) { return; }
			raf = requestAnimationFrame(function () {
				raf = 0;
				var r = el.getBoundingClientRect();
				var dx = (e.clientX - (r.left + r.width / 2)) * PULL;
				var dy = (e.clientY - (r.top + r.height / 2)) * PULL;
				dx = Math.max(-MAX, Math.min(MAX, dx));
				dy = Math.max(-MAX, Math.min(MAX, dy));
				el.style.translate = dx.toFixed(1) + 'px ' + dy.toFixed(1) + 'px';
			});
		});

		el.addEventListener('pointerleave', function () {
			if (raf) { cancelAnimationFrame(raf); raf = 0; }
			var current = el.style.translate;
			el.style.translate = '';
			if (current && current !== '0px 0px' && el.animate) {
				try {
					el.animate(
						[ { translate: current }, { translate: '0px 0px' } ],
						{ duration: 380, easing: 'cubic-bezier(.2, 0, .2, 1)' }
					);
				} catch (err) { /* decorative only — never let this throw */ }
			}
		});
	}

	function boot() {
		document.querySelectorAll(SELECTOR).forEach(arm);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
