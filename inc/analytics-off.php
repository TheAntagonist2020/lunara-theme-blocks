<?php
/**
 * Google Analytics off (3.2.97).
 *
 * Two dormant analytics outputs cost every page view:
 * - Blocksy Companion's "Google Analytics v4" field held 380392203 (a property
 *   number, not a G- measurement ID), so ~90KB gzip of gtag.js loaded and
 *   most likely recorded nothing.
 * - Jetpack's Google Analytics module was on with no ID and printed a
 *   "missing the tracking ID" comment.
 * Clearing both settings is the real fix; this keeps either from coming back.
 *
 * @package Lunara
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'theme_mod_analytics_v4_id', '__return_empty_string', 99 );

add_action(
	'wp',
	static function () {
		$manager = '\\Automattic\\Jetpack\\Google_Analytics\\GA_Manager';
		if ( class_exists( $manager ) && ! empty( $manager::$analytics ) ) {
			remove_action( 'wp_head', array( $manager::$analytics, 'insert_code' ), 999 );
		}
	}
);
