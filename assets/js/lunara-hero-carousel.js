/**
 * Lunara Cinematic Hero — rotating carousel.
 *
 * Mounts a premium cross-fade Splide carousel on the homepage hero, cycling
 * through the latest reviews + journal entries. Independent of the Oscar-Facts
 * "splide pilot" (which is hardcoded to its own markup). The PHP outputs native
 * Splide DOM (.splide / .splide__track / .splide__list / .splide__slide) with
 * REAL image srcs on every slide (slide 1 eager, the rest native-lazy), so
 * this just configures and mounts — no image swapping.
 *
 * Resilience contract: the pre-mount guard in style.css hides slides 2+ until
 * .is-hero-mounted exists. That class is added BEFORE mount() so a failure
 * anywhere inside Splide's mount (which fires our handlers synchronously) can
 * never leave the deck permanently hidden. Every custom handler is isolated
 * in try/catch — decoration must never break navigation — and any caught
 * error is announced with a [lunara-hero] console.warn beacon.
 *
 * @package Lunara_Film
 */
(function () {
	'use strict';

	function warn(where, err) {
		if (window.console && console.warn) {
			console.warn('[lunara-hero] ' + where + ':', err);
		}
	}

	function mountHeroCarousels() {
		if (!window.Splide) {
			return;
		}

		var reduceMotion = window.matchMedia
			? window.matchMedia('(prefers-reduced-motion: reduce)').matches
			: false;

		var roots = document.querySelectorAll('.lunara-cinematic-hero-carousel.splide');

		Array.prototype.forEach.call(roots, function (root) {
			if (root.classList.contains('is-hero-mounted')) {
				return;
			}

			var slideCount = root.querySelectorAll('.splide__slide').length;
			if (slideCount < 2) {
				// Nothing to rotate — leave the single slide as static markup. Splide
				// keeps .splide roots hidden until is-initialized/is-rendered, so
				// release that guard explicitly when no mount is needed.
				root.classList.add('is-hero-mounted', 'is-hero-static', 'is-rendered');
				return;
			}

			var interval = parseInt(root.getAttribute('data-lunara-hero-autoplay'), 10);
			if (!interval || interval < 1500) {
				interval = 6500;
			}

			var splide = new window.Splide(root, {
				type: 'fade',
				rewind: true,
				perPage: 1,
				perMove: 1,
				arrows: true,
				pagination: true,
				drag: true,
				keyboard: 'focused',
				autoplay: !reduceMotion,
				interval: interval,
				pauseOnHover: true,
				pauseOnFocus: true,
				speed: reduceMotion ? 0 : 850,
				rewindSpeed: reduceMotion ? 0 : 850,
				classes: {
					arrows: 'splide__arrows lunara-hero-arrows',
					arrow: 'splide__arrow lunara-hero-arrow',
					prev: 'splide__arrow--prev lunara-hero-arrow-prev',
					next: 'splide__arrow--next lunara-hero-arrow-next',
					pagination: 'splide__pagination lunara-hero-pagination',
					page: 'splide__pagination__page lunara-hero-page'
				}
			});

			// Title Card: replay the kicker/title/CTA rise on every slide
			// change. Purely decorative, so it is isolated — a failure here
			// must never break paging or the mount sequence.
			splide.on('active', function (slide) {
				try {
					if (reduceMotion || !slide || !slide.slide) {
						return;
					}
					var content = slide.slide.querySelector('.lunara-cinematic-hero-content');
					if (content) {
						content.classList.remove('is-title-live');
						void content.offsetWidth;
						content.classList.add('is-title-live');
					}
				} catch (err) {
					warn('title-card', err);
				}
			});

			// Release the pre-mount guard BEFORE mounting: Splide fires events
			// synchronously inside mount(), and if anything in that chain ever
			// throws, the deck must already be visible.
			root.classList.add('is-hero-mounted');

			try {
				splide.mount();
				// The first slide is server-marked is-active, which means
				// Splide's initial 'active' event can be skipped — arm its
				// title card explicitly.
				if (!reduceMotion) {
					try {
						var initial = root.querySelector('.splide__slide.is-active .lunara-cinematic-hero-content');
						if (initial && !initial.classList.contains('is-title-live')) {
							initial.classList.add('is-title-live');
						}
					} catch (err) {
						warn('initial title-card', err);
					}
				}
			} catch (err) {
				warn('mount failed', err);
				if (!root.classList.contains('splide--fade')) {
					// Failed before Splide set up the fade layout: re-arm the
					// guard so the hero degrades to the static slide-1 view
					// instead of six vertically stacked slides.
					root.classList.remove('is-hero-mounted');
				} else if (!root.querySelector('.splide__slide.is-active')) {
					// Failed after layout setup: keep the deck usable and make
					// sure something is showing.
					root.querySelector('.splide__slide').classList.add('is-active');
				}
				root.classList.add('is-hero-static');
			}
		});
	}

	if (document.readyState !== 'loading') {
		mountHeroCarousels();
	} else {
		document.addEventListener('DOMContentLoaded', mountHeroCarousels);
	}
})();
