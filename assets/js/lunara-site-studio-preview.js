(function () {
	'use strict';
	var config = window.LunaraSiteStudioPreviewConfig;
	var markerMap = {
		'global-design': [],
		'homepage-structure': ['hero', 'latest-reviews', 'pairing-desk', 'dispatch', 'oscar-picks', 'oscar-facts'],
		'lunara-method': ['pairing-desk']
	};
	function ownDataObject(value, keys) {
		var names, symbols, index, descriptor;
		if (!value || typeof value !== 'object' || Object.getPrototypeOf(value) !== Object.prototype) { return false; }
		try { names = Object.getOwnPropertyNames(value).sort(); symbols = Object.getOwnPropertySymbols ? Object.getOwnPropertySymbols(value) : []; } catch (error) { return false; }
		keys = keys.slice().sort();
		if (names.length !== keys.length || symbols.length || JSON.stringify(names) !== JSON.stringify(keys)) { return false; }
		for (index = 0; index < names.length; index += 1) { descriptor = Object.getOwnPropertyDescriptor(value, names[index]); if (!descriptor || !Object.prototype.hasOwnProperty.call(descriptor, 'value') || descriptor.get || descriptor.set) { return false; } }
		return true;
	}
	function sameList(left, right) { return Array.isArray(left) && JSON.stringify(left) === JSON.stringify(right); }
	function valid() {
		var expected;
		if (window.parent === window || !ownDataObject(config, ['protocol', 'version', 'type', 'surface', 'instance', 'markers'])) { return false; }
		expected = markerMap[config.surface];
		return config.protocol === 'lunara-site-studio/v1' && config.version === 1 && config.type === 'select-section' && typeof config.surface === 'string' && expected && typeof config.instance === 'string' && config.instance.length <= 80 && /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}:[1-9][0-9]*$/.test(config.instance) && sameList(config.markers, expected) && /^https?:\/\//.test(window.location.origin);
	}
	if (!valid()) { return; }
	document.addEventListener('click', function (event) {
		var target, section, payload;
		if (event.defaultPrevented || event.button !== 0 || event.altKey || event.ctrlKey || event.metaKey || event.shiftKey || !event.target || typeof event.target.closest !== 'function') { return; }
		target = event.target.closest('[data-lunara-site-studio-section]');
		if (!target) { return; }
		section = target.getAttribute('data-lunara-site-studio-section');
		if (typeof section !== 'string' || config.markers.indexOf(section) === -1) { return; }
		event.preventDefault();
		payload = { protocol: config.protocol, version: config.version, type: config.type, surface: config.surface, section: section, instance: config.instance };
		window.parent.postMessage(payload, window.location.origin);
	}, false);
}());
