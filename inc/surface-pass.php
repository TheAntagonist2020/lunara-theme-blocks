<?php
/**
 * Late Home / Oscars / Reviews surface pass.
 *
 * Owns only the cacheable 3.2.88 overlay. Route sheets, Studio variables,
 * and public guardrails stay the owners of their geometry; this file attaches
 * the overlay through the public enqueue queue so WordPress.com concatenation
 * cannot drop a wp_head-only print, then reprints it after guardrails.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve the overlay asset.
 *
 * @return array{uri:string,path:string}
 */
function lunara_surface_pass_asset() {
	if ( ! function_exists( 'lunara_resolve_theme_asset' ) ) {
		return array(
			'uri'  => '',
			'path' => '',
		);
	}

	$asset = lunara_resolve_theme_asset( 'assets/css/lunara-surface-pass.css' );

	return is_array( $asset ) ? $asset : array(
		'uri'  => '',
		'path' => '',
	);
}

/**
 * Put the overlay on the public style queue so Jetpack concat can see it.
 */
function lunara_enqueue_surface_pass_styles() {
	if ( is_admin() || is_feed() ) {
		return;
	}

	$asset = lunara_surface_pass_asset();
	if ( empty( $asset['uri'] ) ) {
		return;
	}

	$version = function_exists( 'lunara_theme_asset_version' )
		? lunara_theme_asset_version( $asset['path'] )
		: '3.2.88';

	wp_enqueue_style(
		'lunara-surface-pass',
		$asset['uri'],
		array( 'lunara-style' ),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'lunara_enqueue_surface_pass_styles', 120 );

/**
 * Reprint after public guardrails (priority 1005) so mid-width card density wins.
 */
function lunara_print_surface_pass_styles() {
	if ( is_admin() || is_feed() ) {
		return;
	}

	if ( ! function_exists( 'lunara_print_cacheable_stylesheet' ) ) {
		return;
	}

	if ( function_exists( 'wp_style_is' ) && wp_style_is( 'lunara-surface-pass', 'done' ) ) {
		return;
	}

	lunara_print_cacheable_stylesheet( 'lunara-surface-pass', 'assets/css/lunara-surface-pass.css' );
}
add_action( 'wp_head', 'lunara_print_surface_pass_styles', 1006 );

/**
 * Live canary: body class proves 3.2.88 PHP loaded even if CSS is concatenated.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function lunara_surface_pass_body_class( $classes ) {
	$classes[] = 'lunara-surface-pass-3288';

	return $classes;
}
add_filter( 'body_class', 'lunara_surface_pass_body_class' );
