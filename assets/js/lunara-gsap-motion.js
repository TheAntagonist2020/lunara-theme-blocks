/**
 * Lunara GSAP Motion Layer (Design Spec 2.0 §5, §19B).
 *
 * The two effects that genuinely need GSAP — everything else on the site
 * stays on native CSS scroll timelines:
 *
 * 1. Character-Level Stagger: section headings split into characters via
 *    SplitText (accessible by default in 3.13: aria attributes handled) and
 *    rise along the Y-axis as their viewport trigger clears.
 * 2. The Trinity Fan-Out: the Pairing Desk's three cards fan out from a
 *    slight stacked rotation as the desk scrolls into view. When this layer
 *    arms, the html element gets .lunara-gsap-motion and the CSS fallback
 *    rise (kept for no-GSAP contexts) stands down.
 *
 * Failure posture is identical to every Lunara decoration: if GSAP is
 *  missing or anything throws, nothing here runs and the site renders
 * exactly as it does today. Reduced motion disables the whole layer.
 */
(function () {
	'use strict';

	if (typeof window.gsap === 'undefined') { return; }
	if (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }

	var gsap = window.gsap;
	try {
		if (window.ScrollTrigger) { gsap.registerPlugin(window.ScrollTrigger); }
		if (window.SplitText) { gsap.registerPlugin(window.SplitText); }
	} catch (err) { return; }
	if (!window.ScrollTrigger) { return; }

	document.documentElement.classList.add('lunara-gsap-motion');

	// Headings that get the character rise. The hero carousel is excluded —
	// its Title Card replay owns that surface.
	var HEADING_SELECTOR = '.lunara-home-section-title, .lunara-archive-hero-title';

	function armHeadings() {
		document.querySelectorAll(HEADING_SELECTOR).forEach(function (el) {
			if (el.closest('.lunara-cinematic-hero-carousel')) { return; }
			try {
				var targets = [el];
				var stagger = 0;
				if (window.SplitText) {
					var split = new window.SplitText(el, { type: 'words,chars', charsClass: 'lunara-char' });
					if (split.chars && split.chars.length) {
						targets = split.chars;
						stagger = 0.018;
					}
				}
				gsap.from(targets, {
					yPercent: 60,
					opacity: 0,
					duration: 0.7,
					ease: 'power3.out',
					stagger: stagger,
					scrollTrigger: { trigger: el, start: 'top 88%', once: true }
				});
			} catch (err) { /* decorative — never break the page */ }
		});
	}

	function armPairFanOut() {
		document.querySelectorAll('.lunara-pairing-desk-section').forEach(function (desk) {
			var cards = desk.querySelectorAll('.lunara-pair-card');
			if (!cards.length) { return; }
			try {
				var mid = (cards.length - 1) / 2;
				gsap.from(cards, {
					y: 46,
					opacity: 0,
					rotation: function (i) { return (i - mid) * 2.4; },
					transformOrigin: '50% 100%',
					duration: 0.85,
					ease: 'power3.out',
					stagger: 0.14,
					clearProps: 'transform,opacity',
					scrollTrigger: { trigger: desk, start: 'top 78%', once: true }
				});
			} catch (err) { /* decorative */ }
		});
	}

	function boot() {
		armHeadings();
		armPairFanOut();
	}

	// Split after webfonts settle so character metrics are final (no reflow
	// of already-split text when Bebas/Georgia finish loading).
	if (document.fonts && document.fonts.ready && document.fonts.ready.then) {
		document.fonts.ready.then(boot).catch(boot);
	} else {
		boot();
	}
})();
