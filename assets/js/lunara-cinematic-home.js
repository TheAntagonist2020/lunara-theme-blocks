(function () {
	'use strict';

	var selector = [
		'.lunara-journal-home-card',
		'.lunara-review-grid-card',
		'.lunara-oscar-pick-card',
		'.lunara-oscar-fact-card'
	].join(',');
	var cards = Array.prototype.slice.call(document.querySelectorAll(selector));

	cards.forEach(function (card) {
		card.classList.remove('is-cinematic-pending');
		card.classList.add('is-cinematic-visible');
	});
}());
