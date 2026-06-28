<?php
/**
 * Lunara Film — Rewrite-rule maintenance.
 *
 * The GitHub deploy pipeline does not run the rewrite-rules flush that the old
 * manual deploy ritual performed, so custom-post-type permalinks (Journal,
 * etc.) can 404 after a deploy until permalinks are re-saved by hand. This
 * flushes rewrite rules after each deploy — keyed off the theme version, so it
 * runs once per version bump (best-effort; a brief post-deploy race could flush
 * a couple of extra times) — keeping CPT routes healthy with no manual step.
 *
 * For the flush to fire, bump the theme version (style.css "Version:") on each
 * deploy. Cost is one autoloaded-option read per request and a single write
 * per deploy.
 *
 * @package Lunara_Film
 * @version 3.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_flush_rewrite_rules_on_deploy' ) ) {
	/**
	 * Flush rewrite rules once when the theme version changes.
	 *
	 * Hooked late on init so every custom post type and taxonomy has already
	 * registered its rewrite rules before the flush rebuilds them.
	 *
	 * @return void
	 */
	function lunara_flush_rewrite_rules_on_deploy() {
		$version = (string) wp_get_theme()->get( 'Version' );

		if ( '' === $version ) {
			return;
		}

		if ( get_option( 'lunara_rewrite_flush_version' ) === $version ) {
			return;
		}

		// Record the version FIRST so any concurrent requests immediately skip
		// this block — prevents a flush stampede where several in-flight
		// requests each run the expensive flush before the option is updated.
		update_option( 'lunara_rewrite_flush_version', $version, true );

		// Soft flush: rebuilds the rewrite-rules option without touching server
		// config (the correct mode for WordPress.com Atomic).
		flush_rewrite_rules( false );
	}

	add_action( 'init', 'lunara_flush_rewrite_rules_on_deploy', 99 );
}
