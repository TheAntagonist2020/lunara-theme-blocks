/**
 * Lunara Portrait Organizer — admin UI.
 *
 * Drives the Scan / Organize / Undo buttons against the wp_ajax endpoints,
 * processing portraits in batches so thousands of rows never time out a single
 * request. Progress is reported as it goes.
 *
 * @package Lunara_Film
 */
(function ($) {
	'use strict';

	if (typeof window.LUNARA_PORTRAITS === 'undefined') {
		return;
	}

	var cfg = window.LUNARA_PORTRAITS;
	var $scan = $('#lunara-portraits-scan');
	var $organize = $('#lunara-portraits-organize');
	var $unfile = $('#lunara-portraits-unfile');
	var $stats = $('#lunara-portraits-stats');
	var $status = $('#lunara-portraits-status');
	var $progressWrap = $('#lunara-portraits-progress-wrap');
	var $progress = $('#lunara-portraits-progress');

	var total = 0; // total portraits, set on scan

	function post(action) {
		return $.post(cfg.ajaxUrl, { action: action, nonce: cfg.nonce });
	}

	function setProgress(done) {
		if (!total) {
			return;
		}
		var pct = Math.min(100, Math.round((done / total) * 100));
		$progressWrap.show();
		$progress.css('width', pct + '%');
	}

	function renderStats(d) {
		$stats.html(
			'<strong>' + d.total.toLocaleString() + '</strong> portraits found · ' +
			'<strong>' + d.filed.toLocaleString() + '</strong> already in “' + d.folderName + '” · ' +
			'<strong>' + d.unfiled.toLocaleString() + '</strong> to file.'
		);
	}

	function lock(on) {
		$scan.prop('disabled', on);
		$organize.prop('disabled', on || total === 0);
		$unfile.prop('disabled', on);
	}

	function doScan() {
		lock(true);
		$status.text(cfg.i18n.working);
		post('lunara_portraits_scan')
			.done(function (res) {
				if (!res || !res.success) {
					$status.text((res && res.data && res.data.message) || cfg.i18n.error);
					lock(false);
					$organize.prop('disabled', true);
					return;
				}
				var d = res.data;
				total = d.total;
				renderStats(d);
				setProgress(d.filed);
				$status.text('');
				lock(false);
				$organize.prop('disabled', d.unfiled === 0);
			})
			.fail(function () {
				$status.text(cfg.i18n.error);
				lock(false);
			});
	}

	function runBatches(action, onProgress, onDone) {
		lock(true);
		$status.text(cfg.i18n.working);
		var filedSoFar = 0;

		function step() {
			post(action)
				.done(function (res) {
					if (!res || !res.success) {
						$status.text((res && res.data && res.data.message) || cfg.i18n.error);
						lock(false);
						return;
					}
					filedSoFar += res.data.processed;
					onProgress(res.data, filedSoFar);
					if (res.data.done) {
						onDone(res.data, filedSoFar);
						lock(false);
					} else {
						step();
					}
				})
				.fail(function () {
					$status.text(cfg.i18n.error);
					lock(false);
				});
		}

		step();
	}

	function doOrganize() {
		runBatches(
			'lunara_portraits_organize',
			function (data) {
				setProgress(total - data.remaining);
				$status.text(cfg.i18n.working + ' (' + (total - data.remaining).toLocaleString() + ' / ' + total.toLocaleString() + ')');
			},
			function () {
				setProgress(total);
				$status.text(cfg.i18n.doneOrganize);
				doScan();
			}
		);
	}

	function doUnfile() {
		if (!window.confirm(cfg.i18n.confirmUnfile)) {
			return;
		}
		runBatches(
			'lunara_portraits_unfile',
			function () {
				$status.text(cfg.i18n.working);
			},
			function () {
				$progress.css('width', '0%');
				$status.text(cfg.i18n.doneUnfile);
				doScan();
			}
		);
	}

	$scan.on('click', doScan);
	$organize.on('click', doOrganize);
	$unfile.on('click', doUnfile);

	// Auto-scan on load so the operator sees the numbers immediately.
	doScan();
})(jQuery);
