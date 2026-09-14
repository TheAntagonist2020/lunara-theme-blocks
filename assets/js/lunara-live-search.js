/**
 * Lunara Live Search — REST-backed overlay (Design Spec §6 / §9).
 *
 * Opens from ordinary header and editorial search controls. Queries
 * lunara/v1/search with a debounce and
 * an AbortController, renders grouped results (Reviews / Journal / Films /
 * Talent / Stories), and supports full arrow-key navigation. All result
 * text is inserted via textContent — never markup. With JS unavailable, forms
 * still land on the theme-owned search route.
 */
(function () {
	'use strict';

	var cfg = window.LUNARA_LIVE_SEARCH || {};
	var overlay, input, results;
	var lastQuery = '';
	var debounceTimer = null;
	var controller = null;
	var activeIndex = -1;
	var isOpen = false;
	var invokingElement = null;
	var openingFrame = null;
	var closingTimer = null;

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	function openOverlay(trigger) {
		if (!overlay || isOpen) {
			return;
		}
		window.clearTimeout(closingTimer);
		invokingElement = trigger || document.activeElement;
		isOpen = true;
		overlay.inert = false;
		overlay.hidden = false;
		document.body.classList.add('lunara-search-open');
		openingFrame = window.requestAnimationFrame(function () {
			openingFrame = null;
			if (!isOpen) {
				return;
			}
			overlay.classList.add('is-open');
			if (input) {
				input.focus();
				input.select();
				if (input.value.trim().length < 2) {
					renderSuggestions();
				}
			}
		});
	}

	function closeOverlay() {
		if (!overlay || !isOpen) {
			return;
		}
		isOpen = false;
		window.cancelAnimationFrame(openingFrame);
		openingFrame = null;
		// The closing animation must not leave invisible controls in the Tab order.
		overlay.inert = true;
		overlay.classList.remove('is-open');
		document.body.classList.remove('lunara-search-open');
		if (invokingElement && invokingElement.isConnected && typeof invokingElement.focus === 'function') {
			invokingElement.focus({ preventScroll: true });
		}
		invokingElement = null;
		closingTimer = window.setTimeout(function () {
			closingTimer = null;
			if (!isOpen) {
				overlay.hidden = true;
			}
		}, 180);
	}

	function containTab(event) {
		// Results and filter chips change while open; take a fresh census per key.
		var focusables = Array.prototype.filter.call(overlay.querySelectorAll('a[href], button, input, select, textarea, [tabindex], [contenteditable="true"]'), function (el) {
			var visibility = window.getComputedStyle(el).visibility;
			return el.tabIndex >= 0 && !el.matches(':disabled') && !el.closest('[hidden], [inert], [aria-hidden="true"]') && el.getClientRects().length > 0 && visibility !== 'hidden' && visibility !== 'collapse';
		});
		var index = focusables.indexOf(document.activeElement);
		if (!focusables.length) {
			event.preventDefault();
			overlay.tabIndex = -1;
			overlay.focus();
		} else if (event.shiftKey ? index <= 0 : index < 0 || index === focusables.length - 1) {
			event.preventDefault();
			focusables[event.shiftKey ? focusables.length - 1 : 0].focus();
		}
	}

	function optionEls() {
		if (!results) {
			return [];
		}
		// Skip hits inside chip-hidden groups so arrow keys never land on
		// something the reader cannot see.
		return Array.prototype.filter.call(results.querySelectorAll('a.lunara-search-hit'), function (el) {
			var section = el.closest && el.closest('section');
			return !section || !section.hidden;
		});
	}

	function setActive(index) {
		var options = optionEls();
		if (!options.length) {
			activeIndex = -1;
			return;
		}
		activeIndex = Math.max(0, Math.min(index, options.length - 1));
		Array.prototype.forEach.call(options, function (el, i) {
			el.classList.toggle('is-active', i === activeIndex);
		});
		options[activeIndex].scrollIntoView({ block: 'nearest' });
	}

	function renderMessage(text) {
		results.textContent = '';
		var p = document.createElement('p');
		p.className = 'lunara-search-overlay-empty';
		p.textContent = text;
		results.appendChild(p);
	}

	// Suggested commands for the empty state: the palette should read as a
	// discovery instrument before the first keystroke, not a blank form.
	function renderSuggestions() {
		var list = (cfg.suggestions || []).filter(Boolean);
		results.textContent = '';
		activeIndex = -1;
		if (!list.length) {
			return;
		}
		var wrap = document.createElement('div');
		wrap.className = 'lunara-search-suggestions';
		var label = document.createElement('p');
		label.className = 'lunara-search-group-label';
		label.textContent = cfg.tryLabel || 'Try';
		wrap.appendChild(label);
		list.forEach(function (text) {
			var chip = document.createElement('button');
			chip.type = 'button';
			chip.className = 'lunara-search-suggestion';
			chip.textContent = text;
			chip.addEventListener('click', function () {
				window.clearTimeout(debounceTimer);
				input.value = text;
				lastQuery = text;
				input.focus();
				runQuery(text);
			});
			wrap.appendChild(chip);
		});
		results.appendChild(wrap);
	}

	// Group chips: All / per-desk filters over the rendered result set.
	function renderChips(groups) {
		if (groups.length < 2) {
			return;
		}
		var bar = document.createElement('div');
		bar.className = 'lunara-search-chips';
		var names = [cfg.all || 'All'].concat(groups.map(function (g) { return g.label || ''; }));
		names.forEach(function (name, i) {
			var chip = document.createElement('button');
			chip.type = 'button';
			chip.className = 'lunara-search-chip' + (i === 0 ? ' is-active' : '');
			chip.textContent = name;
			chip.addEventListener('click', function () {
				Array.prototype.forEach.call(bar.querySelectorAll('.lunara-search-chip'), function (c) {
					c.classList.remove('is-active');
				});
				chip.classList.add('is-active');
				Array.prototype.forEach.call(results.querySelectorAll('.lunara-search-group'), function (section) {
					var sectionLabel = section.getAttribute('data-lunara-group') || '';
					section.hidden = i !== 0 && sectionLabel !== name;
				});
				Array.prototype.forEach.call(results.querySelectorAll('a.lunara-search-hit.is-active'), function (hit) {
					hit.classList.remove('is-active');
				});
				activeIndex = -1;
			});
			bar.appendChild(chip);
		});
		results.appendChild(bar);
	}

	function renderGroups(payload) {
		results.textContent = '';
		activeIndex = -1;

		var groups = (payload && payload.groups) || [];
		if (!groups.length) {
			renderMessage(cfg.empty || 'No matches.');
			return;
		}

		renderChips(groups);

		groups.forEach(function (group) {
			var section = document.createElement('section');
			section.className = 'lunara-search-group';
			section.setAttribute('data-lunara-group', group.label || '');

			var heading = document.createElement('h3');
			heading.className = 'lunara-search-group-label';
			heading.textContent = group.label || '';
			section.appendChild(heading);

			(group.items || []).forEach(function (item) {
				var link = document.createElement('a');
				link.className = 'lunara-search-hit';
				link.href = item.url;

				var title = document.createElement('span');
				title.className = 'lunara-search-hit-title';
				title.textContent = item.title || '';
				link.appendChild(title);

				if (item.meta) {
					var meta = document.createElement('span');
					meta.className = 'lunara-search-hit-meta';
					meta.textContent = item.meta;
					link.appendChild(meta);
				}

				section.appendChild(link);
			});

			results.appendChild(section);
		});

		if (payload.more_url) {
			var more = document.createElement('a');
			more.className = 'lunara-search-hit lunara-search-hit--more';
			more.href = payload.more_url;
			more.textContent = (cfg.more || 'See every result') + ' →';
			results.appendChild(more);
		}
	}

	function runQuery(q) {
		if (controller) {
			controller.abort();
		}
		if (q.length < 2) {
			renderSuggestions();
			return;
		}

		controller = ('AbortController' in window) ? new AbortController() : null;
		var url = cfg.endpoint + (cfg.endpoint.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(q);

		window.fetch(url, { signal: controller ? controller.signal : undefined, credentials: 'same-origin' })
			.then(function (response) { return response.json(); })
			.then(function (payload) {
				if (q !== lastQuery) {
					return; // A newer keystroke already superseded this response.
				}
				renderGroups(payload);
			})
			.catch(function (err) {
				if (err && err.name === 'AbortError') {
					return;
				}
				renderMessage(cfg.empty || 'No matches.');
			});
	}

	ready(function () {
		overlay = document.getElementById('lunara-search-overlay');
		input   = document.getElementById('lunara-search-overlay-input');
		results = document.getElementById('lunara-search-overlay-results');
		if (!overlay || !input || !results || !cfg.endpoint || !window.fetch) {
			return;
		}

		// Keep keyboard navigation inside the current dialog, including new results.
		document.addEventListener('keydown', function (event) {
			if (!isOpen) {
				return;
			}
			if (event.key === 'Escape') {
				event.preventDefault();
				closeOverlay();
			} else if (event.key === 'Tab') {
				containTab(event);
			}
		});

		// Take over the header search trigger (capture phase beats the
		// parent theme's own modal handler).
		document.addEventListener('click', function (event) {
			var trigger = event.target.closest && event.target.closest(
				'.ct-header-search, .ct-search-trigger, [data-id="search"], a[href*="?s="], [data-lunara-search-open]'
			);
			if (!trigger || overlay.contains(trigger)) {
				return;
			}
			event.preventDefault();
			event.stopPropagation();
			openOverlay(trigger);
		}, true);

		Array.prototype.forEach.call(overlay.querySelectorAll('[data-lunara-search-close]'), function (el) {
			el.addEventListener('click', closeOverlay);
		});

		input.addEventListener('input', function () {
			var q = input.value.trim();
			lastQuery = q;
			window.clearTimeout(debounceTimer);
			debounceTimer = window.setTimeout(function () { runQuery(q); }, 220);
		});

		input.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowDown') {
				event.preventDefault();
				setActive(activeIndex + 1);
			} else if (event.key === 'ArrowUp') {
				event.preventDefault();
				setActive(activeIndex - 1);
			} else if (event.key === 'Enter' && activeIndex >= 0) {
				var options = optionEls();
				if (options[activeIndex]) {
					event.preventDefault();
					window.location.href = options[activeIndex].href;
				}
			}
		});
	});
})();
