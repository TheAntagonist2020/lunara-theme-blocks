/**
 * Lunara Header Command — off-canvas navigation (Design Spec §9).
 *
 * Opens from the header burger, closes on Esc, veil click, or the close
 * button. Focus is trapped inside the panel while open and restored to
 * the opener on close. No dependencies; does nothing unless the takeover
 * markup is present.
 */
(function () {
	'use strict';

	var root, panel, opener, lastFocus = null;

	function focusables() {
		return panel.querySelectorAll('a[href], button:not([disabled])');
	}

	function openNav() {
		if (!root) { return; }
		lastFocus = document.activeElement;
		root.hidden = false;
		document.body.classList.add('lunara-nav-open');
		if (opener) { opener.setAttribute('aria-expanded', 'true'); }
		window.requestAnimationFrame(function () {
			root.classList.add('is-open');
			var f = focusables();
			if (f.length) { f[0].focus(); }
		});
	}

	function closeNav() {
		if (!root || root.hidden) { return; }
		root.classList.remove('is-open');
		document.body.classList.remove('lunara-nav-open');
		if (opener) { opener.setAttribute('aria-expanded', 'false'); }
		window.setTimeout(function () { root.hidden = true; }, 300);
		if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
	}

	function trapTab(event) {
		if (event.key !== 'Tab' || root.hidden) { return; }
		var f = focusables();
		if (!f.length) { return; }
		var first = f[0];
		var last = f[f.length - 1];
		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	}

	function ready(fn) {
		if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); }
	}

	ready(function () {
		root   = document.getElementById('lunara-offcanvas');
		opener = document.querySelector('[data-lunara-nav-open]');
		if (!root || !opener) { return; }
		panel = root.querySelector('.lunara-offcanvas-panel') || root;

		opener.addEventListener('click', openNav);

		Array.prototype.forEach.call(root.querySelectorAll('[data-lunara-nav-close]'), function (el) {
			el.addEventListener('click', closeNav);
		});

		// Following a link closes the panel (same-page anchors included).
		root.addEventListener('click', function (event) {
			if (event.target.closest && event.target.closest('a[href]')) { closeNav(); }
		});

		document.addEventListener('keydown', function (event) {
			if (root.hidden) { return; }
			if (event.key === 'Escape') { closeNav(); return; }
			trapTab(event);
		});
	});
})();
