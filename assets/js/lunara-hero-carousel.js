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

			// Eager-load the first slide's image; lazy-load the rest only once
			// they're about to become visible (keeps the LCP fast).
			splide.on('active', function (slide) {
				var img = slide.slide.querySelector('img[data-lunara-lazy]');
				if (img) {
					img.src = img.getAttribute('data-lunara-lazy');
					img.removeAttribute('data-lunara-lazy');
				}
			});

			splide.mount();
			root.classList.add('is-hero-mounted');
		});
	}

	if (document.readyState !== 'loading') {
		mountHeroCarousels();
	} else {
		document.addEventListener('DOMContentLoaded', mountHeroCarousels);
	}
})();
