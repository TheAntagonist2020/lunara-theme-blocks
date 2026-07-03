/**
 * Lunara Cinematic Hero — rotating carousel.
 *
 * Mounts a premium cross-fade Splide carousel on the homepage hero, cycling
 * through the latest reviews + journal entries. Independent of the Oscar-Facts
 * "splide pilot" (which is hardcoded to its own markup). The PHP outputs native
 * Splide DOM (.splide / .splide__track / .splide__list / .splide__slide), so
 * this just configures and mounts.
 *
 * @package Lunara_Film
 */
(function () {
	'use strict';

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
				// Nothing to rotate — leave the single slide as static markup.
				root.classList.add('is-hero-mounted', 'is-hero-static');
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

			// First slide loads eagerly (LCP). Every other slide's real image is
			// swapped in as early as possible — at transition START (move), at
			// activation, and via a full background preload shortly after the
			// window load event — so paging through the deck never shows a blank
			// slide waiting on a download.
			var loadLazyImage = function (slideEl) {
				if (!slideEl) {
					return;
				}
				var lazyImg = slideEl.querySelector('img[data-lunara-lazy]');
				if (lazyImg) {
					lazyImg.loading = 'eager';
					lazyImg.src = lazyImg.getAttribute('data-lunara-lazy');
					lazyImg.removeAttribute('data-lunara-lazy');
				}
			};

			splide.on('move', function (newIndex) {
				var allSlides = root.querySelectorAll('.splide__slide');
				if (allSlides.length) {
					loadLazyImage(allSlides[newIndex % allSlides.length]);
					loadLazyImage(allSlides[(newIndex + 1) % allSlides.length]);
				}
			});

			splide.on('active', function (slide) {
				loadLazyImage(slide.slide);

				var allSlides = root.querySelectorAll('.splide__slide');
				if (allSlides.length) {
					loadLazyImage(allSlides[(slide.index + 1) % allSlides.length]);
				}

				// Title Card: replay the kicker/title/CTA rise on every slide
				// change. The hidden from-state lives only inside the animation
				// keyframes (fill backwards), so text is never hidden for JS-off,
				// reduced-motion, or pre-mount readers.
				if (!reduceMotion) {
					var content = slide.slide.querySelector('.lunara-cinematic-hero-content');
					if (content) {
						content.classList.remove('is-title-live');
						void content.offsetWidth;
						content.classList.add('is-title-live');
					}
				}
			});

			splide.mount();
			root.classList.add('is-hero-mounted');

			// Background-load every remaining slide image once the page itself
			// has finished loading, gently staggered so the whole deck is warm
			// within a few seconds without competing with the first paint.
			var preloadRemaining = function () {
				var pending = root.querySelectorAll('img[data-lunara-lazy]');
				Array.prototype.forEach.call(pending, function (img, i) {
					window.setTimeout(function () {
						if (img.hasAttribute('data-lunara-lazy')) {
							img.loading = 'eager';
							img.src = img.getAttribute('data-lunara-lazy');
							img.removeAttribute('data-lunara-lazy');
						}
					}, 400 + i * 350);
				});
			};
			if (document.readyState === 'complete') {
				preloadRemaining();
			} else {
				window.addEventListener('load', preloadRemaining, { once: true });
			}
		});
	}

	if (document.readyState !== 'loading') {
		mountHeroCarousels();
	} else {
		document.addEventListener('DOMContentLoaded', mountHeroCarousels);
	}
})();
