<?php
/**
 * 2026-09-16 surface pass loader.
 *
 * Prints assets/css/lunara-surface-pass.css after the shared public guardrails
 * without touching the 3.2.80 identity contracts in setup.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
