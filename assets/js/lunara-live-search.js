/**
 * Lunara Live Search — REST-backed overlay (Design Spec §6 / §9).
 *
 * Opens with Cmd/Ctrl+K or "/" (outside form fields), or by intercepting
 * the header search trigger. Queries lunara/v1/search with a debounce and
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

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	function isTypingContext(el) {
		if (!el) {
			return false;
		}
		var tag = (el.tagName || '').toLowerCase();
		return tag === 'input' || tag === 'textarea' || tag === 'select' || el.isContentEditable;
	}

	function openOverlay() {
		if (!overlay) {
			return;
		}
		overlay.hidden = false;
		document.body.classList.add('lunara-search-open');
		window.requestAnimationFrame(function () {
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
		if (!overlay || overlay.hidden) {
			return;
		}
		overlay.classList.remove('is-open');
		document.body.classList.remove('lunara-search-open');
		window.setTimeout(function () {
			overlay.hidden = true;
		}, 180);
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

		// Global shortcuts.
		document.addEventListener('keydown', function (event) {
			if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
				event.preventDefault();
				openOverlay();
				return;
			}
			if (event.key === '/' && !isTypingContext(event.target) && overlay.hidden) {
				event.preventDefault();
				openOverlay();
				return;
			}
			if (event.key === 'Escape' && !overlay.hidden) {
				closeOverlay();
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
			openOverlay();
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
