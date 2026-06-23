/**
 * Lunara Splide Pilot.
 *
 * Converts the homepage Oscar Facts lane to a Splide carousel while keeping the
 * original Lunara markup usable as a static fallback.
 */
(function () {
	'use strict';

	var reduceMotion = window.matchMedia &&
		window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function numberAttr(root, attr, fallback) {
		var value = parseInt(root.getAttribute(attr) || '', 10);
		return Number.isFinite(value) && value >= 0 ? value : fallback;
	}

	function activeIndex(index, length) {
		if (!length) return 0;
		return ((index % length) + length) % length;
	}

	function padNumber(value) {
		return value < 10 ? '0' + String(value) : String(value);
	}

	function initPilot(root) {
		if (!window.Splide || root.classList.contains('lunara-splide-ready')) {
			return;
		}

		var track = root.querySelector('.lunara-oscar-facts-track');
		if (!track) return;

		var slides = Array.prototype.slice.call(
			track.querySelectorAll('.lunara-oscar-fact-card')
		);
		if (slides.length < 2) return;

		root.classList.add('splide', 'lunara-splide-pending');
		root.setAttribute('data-lunara-splide-pilot-active', 'pending');
		root.setAttribute('aria-roledescription', 'carousel');
		track.classList.add('splide__track');

		var list = document.createElement('div');
		list.className = 'splide__list lunara-oscar-facts-splide-list';
		while (track.firstChild) {
			list.appendChild(track.firstChild);
		}
		track.appendChild(list);

		slides = Array.prototype.slice.call(
			list.querySelectorAll('.lunara-oscar-fact-card')
		);
		slides.forEach(function (slide, index) {
			slide.classList.add('splide__slide');
			slide.setAttribute('aria-label', String(index + 1) + ' of ' + String(slides.length));
		});

		var dots = Array.prototype.slice.call(
			root.querySelectorAll('.lunara-carousel-dot')
		);
		var currentCounter = root.querySelector('.lunara-oscar-facts-current');
		var totalCounter = root.querySelector('.lunara-oscar-facts-total');
		var progressBar = root.querySelector('.lunara-oscar-facts-progress-bar');
		var interval = numberAttr(root, 'data-lunara-splide-autoplay', 6500);
		var heightFrame = 0;

		var splide = new window.Splide(root, {
			type: 'loop',
			perPage: 1,
			perMove: 1,
			gap: 0,
			arrows: true,
			pagination: false,
			autoplay: !reduceMotion && interval > 0,
			interval: interval,
			pauseOnHover: true,
			pauseOnFocus: true,
			keyboard: 'focused',
			drag: true,
			speed: reduceMotion ? 0 : 520,
			classes: {
				arrows: 'splide__arrows lunara-splide-arrows',
				arrow: 'splide__arrow lunara-splide-arrow',
				prev: 'splide__arrow--prev lunara-splide-arrow-prev',
				next: 'splide__arrow--next lunara-splide-arrow-next'
			}
		});

		function syncHeight() {
			if (heightFrame) {
				window.cancelAnimationFrame(heightFrame);
			}

			heightFrame = window.requestAnimationFrame(function () {
				var current = activeIndex(splide.index, slides.length);
				var activeSlide = slides[current];
				var height = activeSlide ? Math.ceil(activeSlide.getBoundingClientRect().height) : 0;

				if (height > 0) {
					track.style.height = String(height) + 'px';
					list.style.height = String(height) + 'px';
				}
			});
		}

		function syncState() {
			var current = activeIndex(splide.index, slides.length);
			var previous = activeIndex(current - 1, slides.length);
			var next = activeIndex(current + 1, slides.length);
			slides.forEach(function (slide, index) {
				var isActive = index === current;
				slide.classList.toggle('active', isActive);
				slide.classList.toggle('is-lunara-active', isActive);
				slide.classList.toggle('is-lunara-prev', index === previous && slides.length > 2);
				slide.classList.toggle('is-lunara-next', index === next && slides.length > 2);
				if (isActive) {
					slide.setAttribute('aria-current', 'true');
				} else {
					slide.removeAttribute('aria-current');
				}
			});
			dots.forEach(function (dot, index) {
				var active = index === current;
				dot.classList.toggle('active', active);
				if (active) {
					dot.setAttribute('aria-current', 'true');
				} else {
					dot.removeAttribute('aria-current');
				}
			});
			syncSignatureConsole(current);
			syncHeight();
		}

		function syncSignatureConsole(current) {
			var total = slides.length;
			var displayCurrent = total ? current + 1 : 0;
			var progress = total ? Math.max(0, Math.min(100, (displayCurrent / total) * 100)) : 0;
			var progressValue = progress.toFixed(4) + '%';

			root.setAttribute('data-lunara-current-slide', String(displayCurrent));
			root.style.setProperty('--lunara-oscar-facts-progress', progressValue);

			if (currentCounter) {
				currentCounter.textContent = padNumber(displayCurrent);
			}
			if (totalCounter) {
				totalCounter.textContent = padNumber(total);
			}
			if (progressBar) {
				progressBar.style.width = progressValue;
			}
		}

		slides.forEach(function (slide) {
			Array.prototype.slice.call(slide.querySelectorAll('img')).forEach(function (image) {
				if (!image.complete) {
					image.addEventListener('load', syncHeight, { once: true });
					image.addEventListener('error', syncHeight, { once: true });
				}
			});
		});

		window.addEventListener('resize', syncHeight);

		dots.forEach(function (dot, index) {
			dot.addEventListener('click', function (event) {
				event.preventDefault();
				splide.go(index);
			});
		});

		splide.on('mounted move moved active resized updated refreshed', syncState);
		splide.on('mounted', function () {
			root.classList.remove('lunara-splide-pending');
			root.classList.add('lunara-splide-ready');
			root.setAttribute('data-lunara-splide-pilot-active', 'ready');
			syncState();
		});
		splide.on('destroy', function () {
			window.removeEventListener('resize', syncHeight);
			if (heightFrame) {
				window.cancelAnimationFrame(heightFrame);
			}
			track.style.height = '';
			list.style.height = '';
			root.classList.remove('lunara-splide-ready');
			root.removeAttribute('data-lunara-splide-pilot-active');
		});

		try {
			splide.mount();
			root.lunaraSplidePilot = splide;
		} catch (error) {
			root.classList.remove('lunara-splide-pending');
			root.setAttribute('data-lunara-splide-pilot-active', 'failed');
			if (window.console && window.console.warn) {
				window.console.warn('Lunara Splide pilot failed to initialize.', error);
			}
		}
	}

	function boot() {
		document.querySelectorAll('[data-lunara-splide-pilot]').forEach(initPilot);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
