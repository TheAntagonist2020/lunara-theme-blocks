<?php
/**
 * Late Home / Oscars / Reviews surface pass.
 *
 * Owns only the cacheable 3.2.88 overlay. Route sheets, Studio variables,
 * and public guardrails stay the owners of their geometry; this file prints
 * after those sheets so mid-width card density and a few frame repairs can
 * win without growing the Oscars 56 KB route budget.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print the surface-pass stylesheet after public guardrails (priority 1005).
 */
function lunara_print_surface_pass_styles() {
	if ( is_admin() || is_feed() ) {
		return;
	}

	if ( ! function_exists( 'lunara_print_cacheable_stylesheet' ) ) {
		return;
	}

	lunara_print_cacheable_stylesheet( 'lunara-surface-pass', 'assets/css/lunara-surface-pass.css' );
}
add_action( 'wp_head', 'lunara_print_surface_pass_styles', 1006 );
