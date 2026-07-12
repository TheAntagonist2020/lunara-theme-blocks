/**
 * Lunara Lights Down — immersive screening mode for single reviews/journal.
 *
 * Three quiet moves once the reader is past the hero and into the text:
 * 1. The chrome (header/footer) dims to 35% so the page reads like a theater
 *    going dark — hovering the header brings it back instantly.
 * 2. A 2px reading-progress hairline tracks the article along the top edge.
 * 3. The hero art is sampled (24x24 canvas average) into --lunara-ambient,
 *    which tints the progress hairline and blockquote rules to the film's
 *    own palette. Palette is kept under reduced motion (it's color, not
 *    motion); the dim transition is dropped there by CSS.
 *
 * Enqueued only on is_singular(review|journal). Everything bails safely:
 * no IntersectionObserver, no article, cross-origin canvas — the page just
 * stays exactly as it was.
 *
 * @package Lunara_Film
 */
(function () {
	'use strict';

	if (!('IntersectionObserver' in window)) {
		return;
	}

	var body = document.body;
	var article = document.querySelector('article') || document.querySelector('.entry-content');
	if (!article) {
		return;
	}

	// --- Reading progress hairline -------------------------------------
	var bar = document.createElement('div');
	bar.id = 'lunara-read-progress';
	bar.setAttribute('aria-hidden', 'true');
	body.appendChild(bar);

	var ticking = false;
	var updateProgress = function () {
		ticking = false;
		var rect = article.getBoundingClientRect();
		var total = rect.height - window.innerHeight;
		var p = total > 0 ? Math.min(1, Math.max(0, -rect.top / total)) : 0;
		bar.style.transform = 'scaleX(' + p.toFixed(4) + ')';
	};
	window.addEventListener('scroll', function () {
		if (!ticking) {
			ticking = true;
			window.requestAnimationFrame(updateProgress);
		}
	}, { passive: true });
	window.addEventListener('resize', updateProgress, { passive: true });
	updateProgress();

	// --- Lights down: dim the chrome while deep in the read -------------
	var hero = document.querySelector('.lunara-review-single-cinematic-hero')
		|| document.querySelector('.lunara-review-visual--hero')
		|| document.querySelector('.entry-header');
	if (hero) {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				// Lights go down only once the hero has fully scrolled above
				// the viewport; scrolling back up brings them right back.
				var above = !entry.isIntersecting && entry.boundingClientRect.bottom < 0;
				body.classList.toggle('is-lights-down', above);
			});
		}, { threshold: 0 });
		io.observe(hero);
	}

	// --- Ambient accent sampled from the hero art ------------------------
	var img = document.querySelector('.lunara-review-single-cinematic-hero img')
		|| document.querySelector('.lunara-review-visual--hero img')
		|| article.querySelector('img');
	if (img) {
		var ambientSampleUrl = function (image) {
			var source = image.currentSrc || image.src;
			if (!source) {
				return '';
			}

			try {
				var url = new URL(source, document.baseURI);

				if (url.hostname.toLowerCase() === 'image.tmdb.org') {
					var photon = new URL('https://i0.wp.com/' + url.hostname + url.pathname);
					photon.searchParams.set('resize', '48,48');
					photon.searchParams.set('quality', '60');
					photon.searchParams.set('ssl', '1');
					return photon.toString();
				}

				if (url.origin === window.location.origin || /(^|\.)wp\.com$/i.test(url.hostname)) {
					return url.toString();
				}
			} catch (e) {
				return '';
			}

			return '';
		};
		var sampleUrl = ambientSampleUrl(img);
		if (!sampleUrl) {
			return;
		}

		// Re-fetch through crossOrigin=anonymous so the canvas isn't tainted —
		// Photon/i0.wp.com sends ACAO:*; unsupported external hosts are skipped.
		var sample = new Image();
		sample.crossOrigin = 'anonymous';
		var applyAmbient = function () {
			try {
				var c = document.createElement('canvas');
				c.width = 24;
				c.height = 24;
				var g = c.getContext('2d', { willReadFrequently: true });
				g.drawImage(sample, 0, 0, 24, 24);
				var d = g.getImageData(0, 0, 24, 24).data;
				var r = 0, gr = 0, b = 0, n = 0;
				for (var i = 0; i < d.length; i += 4) {
					if (d[i + 3] < 200) {
						continue;
					}
					r += d[i];
					gr += d[i + 1];
					b += d[i + 2];
					n++;
				}
				if (!n) {
					return;
				}
				// Lift toward legible warmth so a murky frame can't produce mud.
				var lift = function (v) {
					return Math.min(255, Math.round((v / n) * 0.55 + 110));
				};
				document.documentElement.style.setProperty(
					'--lunara-ambient',
					'rgb(' + lift(r) + ',' + lift(gr) + ',' + lift(b) + ')'
				);
			} catch (e) {
				/* Cross-origin canvas taint — keep the gold default. */
			}
		};
		sample.addEventListener('load', applyAmbient, { once: true });
		var armSample = function () {
			sample.src = sampleUrl;
		};
		if (img.complete && img.naturalWidth) {
			armSample();
		} else {
			img.addEventListener('load', armSample, { once: true });
		}
	}
})();
