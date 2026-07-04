/**
 * The "Method" Footer Reveal — curtain effect (Design Spec 2.0 §7).
 *
 * The main content layer slides up on scroll to unveil the brand-matrix
 * footer pinned beneath it. This module only ARMS the effect — the actual
 * positioning is pure CSS keyed to body.lunara-curtain-armed — and it only
 * arms when the layout can support it:
 *
 * - Desktop widths only (≥901px).
 * - The content stage must be meaningfully taller than the viewport, so a
 *   short page never shows a floating footer behind nothing.
 * - Footer height is mirrored into --lunara-footer-h and kept fresh via
 *   ResizeObserver, so the reveal gap always matches the real footer.
 *
 * Anything failing the checks leaves the footer in normal document flow.
 */
(function () {
	'use strict';

	if (!window.matchMedia) { return; }
	var mq = matchMedia('(min-width: 901px)');

	function boot() {
		var footer = document.querySelector('footer.lunara-site-footer');
		if (!footer || !footer.previousElementSibling) { return; }
		var stage = footer.previousElementSibling;

		function measure() {
			var h = footer.offsetHeight || 0;
			// Stage height excludes margins, so this stays stable across
			// arm/disarm — no feedback loop with our own margin-bottom.
			var enough = stage.offsetHeight > (window.innerHeight - h) + 240;
			if (mq.matches && h > 0 && enough) {
				document.documentElement.style.setProperty('--lunara-footer-h', h + 'px');
				document.body.classList.add('lunara-curtain-armed');
				stage.classList.add('lunara-curtain-stage');
			} else {
				document.body.classList.remove('lunara-curtain-armed');
				stage.classList.remove('lunara-curtain-stage');
			}
		}

		measure();
		if (window.ResizeObserver) {
			var ro = new ResizeObserver(measure);
			ro.observe(footer);
			ro.observe(stage);
		}
		window.addEventListener('resize', measure);
		if (mq.addEventListener) { mq.addEventListener('change', measure); }
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
