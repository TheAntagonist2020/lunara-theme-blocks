(function () {
	'use strict';

	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var compactViewport = window.matchMedia && window.matchMedia('(max-width: 820px)').matches;
	var selector = [
		'.lunara-journal-home-card',
		'.lunara-review-grid-card',
		'.lunara-oscar-pick-card',
		'.lunara-oscar-fact-card'
	].join(',');
	var cards = Array.prototype.slice.call(document.querySelectorAll(selector));

	if (!cards.length || reduceMotion || compactViewport || !('IntersectionObserver' in window)) {
		cards.forEach(function (card) {
			card.classList.remove('is-cinematic-pending');
			card.classList.add('is-cinematic-visible');
		});
		return;
	}

	cards.forEach(function (card) {
		card.classList.add('is-cinematic-pending');
	});

	var observer = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (!entry.isIntersecting) {
				return;
			}

			entry.target.classList.remove('is-cinematic-pending');
			entry.target.classList.add('is-cinematic-visible');
			observer.unobserve(entry.target);
		});
	}, {
		rootMargin: '240px 0px',
		threshold: 0.01
	});

	cards.forEach(function (card) {
		observer.observe(card);
	});
}());
