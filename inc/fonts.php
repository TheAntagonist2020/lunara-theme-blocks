<?php
/**
 * Fonts (3.2.101): the theme is the only source of @font-face on the front end.
 *
 * WordPress core's Font Library prints a `<style class="wp-fonts-local">` block
 * on every page with an @font-face for each family uploaded through the Site
 * Editor. On lunarafilm.com that block declared Tiempos Text, Tiempos Headline,
 * GT Sectra and Canela a second time, from /wp-content/uploads/fonts/*.ttf,
 * on top of the woff2 faces style.css already declares from
 * /wp-content/uploads/lunara-fonts/v1/. Browsers downloaded both. Measured on
 * 2026-10-08: 214-357 KB of fonts per page, 2-4 of them TTFs.
 *
 * The editor keeps the Font Library (it is how those families are picked in
 * the block editor); only the front-end output is unhooked.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Unhook the Font Library's front-end @font-face output.
 *
 * Core adds both printers to wp_head at priority 50 (default-filters.php):
 * wp_print_font_faces for the Font Library, and, since 6.7,
 * wp_print_font_faces_from_style_variations for style variations.
 */
function lunara_fonts_unhook_font_library() {
	if ( is_admin() ) {
		return;
	}
	remove_action( 'wp_head', 'wp_print_font_faces', 50 );
	remove_action( 'wp_head', 'wp_print_font_faces_from_style_variations', 50 );
}
add_action( 'init', 'lunara_fonts_unhook_font_library', 20 );
