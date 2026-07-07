/**
 * Lunara GSAP Motion Layer (Design Spec 2.0 §5, §19B).
 *
 * The GSAP-driven effects — everything else on the site stays on native CSS
 * scroll timelines:
 *
 * 1. Character-Level Stagger: section, archive, and entity-dossier headings
 *    split into characters via SplitText (accessible by default in 3.13:
 *    aria attributes handled) and rise along the Y-axis as their viewport
 *    trigger clears.
 * 2. The Trinity Fan-Out: the Pairing Desk's three cards fan out from a
 *    slight stacked rotation as the desk scrolls into view.
 * 3. Index Grid Rise: film/talent index cards lift in as they enter,
 *    batched so long grids animate row by row, never all at once.
 * 4. Award Record Rise: dossier award rows stagger in down the record.
 * 5. Essay Module Rise: essay-builder modules lift in one by one; the
 *    full-bleed cinematic banner adds a slow scale settle (1.04 → 1).
 *
 * When this layer arms, the html element gets .lunara-gsap-motion and the
 * CSS fallback rises (kept for no-GSAP contexts) stand down. Failure
 * posture is identical to every Lunara decoration: if GSAP is missing or
 * anything throws, nothing here runs and the site renders exactly as it
 * does today. Reduced motion disables the whole layer.
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
	var HEADING_SELECTOR = '.lunara-home-section-title, .lunara-archive-hero-title, .lunara-entity-title';

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
					clearProps: 'transform,opacity',
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

	function armEntityGrids() {
		document.querySelectorAll('.lunara-entity-grid').forEach(function (grid) {
			var cards = grid.children;
			if (!cards.length) { return; }
			try {
				if (window.ScrollTrigger.batch) {
					window.ScrollTrigger.batch(cards, {
						start: 'top 92%',
						once: true,
						onEnter: function (batch) {
							gsap.from(batch, {
								y: 32,
								opacity: 0,
								duration: 0.65,
								ease: 'power3.out',
								stagger: 0.07,
								clearProps: 'transform,opacity'
							});
						}
					});
				}
			} catch (err) { /* decorative */ }
		});
	}

	function armAwardRecords() {
		document.querySelectorAll('.lunara-entity-awards').forEach(function (record) {
			var rows = record.querySelectorAll('.lunara-entity-award');
			if (!rows.length) { return; }
			try {
				gsap.from(rows, {
					x: -18,
					opacity: 0,
					duration: 0.55,
					ease: 'power2.out',
					stagger: 0.05,
					clearProps: 'transform,opacity',
					scrollTrigger: { trigger: record, start: 'top 82%', once: true }
				});
			} catch (err) { /* decorative */ }
		});
	}

	function armEssayModules() {
		document.querySelectorAll('.lunara-essay-module').forEach(function (module) {
			try {
				gsap.from(module, {
					y: 30,
					opacity: 0,
					duration: 0.75,
					ease: 'power3.out',
					clearProps: 'transform,opacity',
					scrollTrigger: { trigger: module, start: 'top 88%', once: true }
				});

				var bannerMedia = module.classList.contains('lunara-essay-banner')
					? module.querySelector('.lunara-essay-banner-media img')
					: null;
				if (bannerMedia) {
					gsap.from(bannerMedia, {
						scale: 1.04,
						duration: 1.4,
						ease: 'power2.out',
						clearProps: 'transform',
						scrollTrigger: { trigger: module, start: 'top 88%', once: true }
					});
				}
			} catch (err) { /* decorative */ }
		});
	}

	// Atmosphere V3: Tracking Shot — horizontal shelves ease in from a
	// lateral offset as the page scrolls down to them, so the eye reads a
	// camera pan rather than a fade. Scrubbed to scroll (reversible on the
	// way back up); after the entry range the shelf rests at exactly x:0.
	function armTrackingShot() {
		var shelves = document.querySelectorAll(
			'.lunara-pair-cards-grid, .lunara-poster-carousel-track, ' +
			'.lunara-review-archive-rail-track, .lunara-ledger-carousel-track, ' +
			'.lunara-oscars-winner-carousel-track'
		);
		shelves.forEach(function (shelf) {
			try {
				gsap.fromTo(shelf, { x: 32 }, {
					x: 0,
					ease: 'none',
					scrollTrigger: { trigger: shelf, start: 'top 98%', end: 'top 58%', scrub: 0.6 }
				});
			} catch (err) { /* decorative — never break the page */ }
		});
	}

	function boot() {
		armHeadings();
		armPairFanOut();
		armEntityGrids();
		armAwardRecords();
		armEssayModules();
		armTrackingShot();
	}

	// Split after webfonts settle so character metrics are final (no reflow
	// of already-split text when Bebas/Georgia finish loading).
	if (document.fonts && document.fonts.ready && document.fonts.ready.then) {
		document.fonts.ready.then(boot).catch(boot);
	} else {
		boot();
	}
})();
