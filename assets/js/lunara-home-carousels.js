/* Independently mounted Journal carousel; the cinematic hero keeps its own runtime. */
(function () {
	'use strict';
	function start() {
		var motion = window.matchMedia('(prefers-reduced-motion: reduce)');
		// Failed remote artwork receives the same treatment as an absent image.
		document.querySelectorAll('.lunara-home-curated-journal img, .lunara-home-curated-hero img').forEach(function (img) {
			function fallback() {
				var placeholder = document.createElement('span');
				placeholder.className = 'lunara-home-carousel-placeholder';
				placeholder.setAttribute('aria-hidden', 'true');
				var label = document.createElement('span'); label.textContent = 'LUNARA FILM'; placeholder.appendChild(label);
				img.replaceWith(placeholder);
			}
			img.addEventListener('error', fallback, { once: true });
			if (img.complete && img.naturalWidth === 0) { fallback(); }
		});
		if (!window.Splide) { return; }
		document.querySelectorAll('[data-lunara-journal-carousel]').forEach(function (root) {
			if (root.classList.contains('is-initialized')) { return; }
			var count = root.querySelectorAll('.splide__slide').length;
			if (count < 2) { root.classList.add('is-journal-static'); return; }
			var enabled = root.getAttribute('data-lunara-autoplay-enabled') === '1';
			var userPaused = !enabled;
			var toggle = root.querySelector('.splide__toggle');
			var interval = parseInt(root.getAttribute('data-lunara-carousel-interval'), 10) || 7000;
			var carousel = new window.Splide(root, {
				type: 'slide', rewind: true, perPage: 3, perMove: 1, gap: '24px',
				breakpoints: { 980: { perPage: 2, gap: '18px' }, 640: { perPage: 1, gap: '14px' } },
				arrows: true, pagination: true, drag: true, keyboard: 'focused',
				autoplay: enabled && !motion.matches ? true : 'pause', interval: interval,
				pauseOnHover: true, pauseOnFocus: true, speed: 500,
				reducedMotion: { speed: 0, rewindSpeed: 0, autoplay: 'pause' }
			});
			function playback() {
				var fits = count <= carousel.options.perPage;
				root.classList.toggle('is-journal-static', fits);
				if (toggle) { toggle.hidden = fits || motion.matches; }
				if ((fits || motion.matches) && carousel.Components.Autoplay) { carousel.Components.Autoplay.pause(); }
				else if (!userPaused && carousel.Components.Autoplay && !root.matches(':hover') && !root.contains(document.activeElement)) { carousel.Components.Autoplay.play(); }
			}
			if (toggle) { toggle.addEventListener('click', function () { userPaused = !carousel.Components.Autoplay.isPaused(); }); }
			carousel.on('mounted resized updated', playback);
			try { carousel.mount(); } catch (error) {
				// Restore the ordinary readable card grid if the slider cannot initialize.
				try { carousel.destroy(true); } catch (ignored) { /* The CSS fallback does not depend on teardown. */ }
				root.classList.add('has-carousel-error');
			}
			if (motion.addEventListener) { motion.addEventListener('change', playback); }
		});
	}
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
