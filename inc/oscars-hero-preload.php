<?php
/**
 * Oscars portal images (3.2.102): the hero backdrop is discoverable from the
 * HTML, and the portal's posters say how wide they render.
 *
 * Lighthouse on /oscars/ (phone, 2026-10-08): the LCP element was the hero
 * band, whose image is a CSS background set through a custom property on the
 * section's inline style. The browser only finds it after CSS and layout;
 * "prioritize LCP image: 1,240 ms" was the audit's estimate. A `<link
 * rel="preload" as="image">` in <head> starts that download with the HTML.
 *
 * The posters the portal prints come from the Ledger plugin and carried its
 * default `sizes`. The portal knows its own layout (measured 2026-10-10:
 * hero card 96px on phones, 124px to 820px, 300px on desktop; the spotlight,
 * title and winner grids 96px on phones, ~335px on tablets, ~250-290px on
 * desktop), so it says so, and drops the inline background-image it put
 * behind an <img> that fully covers it (a second full-size download of the
 * same poster, never visible).
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The hero backdrop URL the portal template paints, resolved the same way the
 * template resolves it (Best Picture visual, TMDB backdrop at w780).
 *
 * @return string Empty when the portal has no backdrop.
 */
function lunara_oscars_hero_backdrop_url() {
	static $resolved = null;
	if ( null !== $resolved ) {
		return $resolved;
	}
	$resolved = '';
	if ( ! function_exists( 'lunara_get_home_oscars_snapshot' ) ) {
		return $resolved;
	}
	$snapshot = lunara_get_home_oscars_snapshot();
	$visual   = is_array( $snapshot['best_picture']['visual'] ?? null ) ? $snapshot['best_picture']['visual'] : array();
	if ( ! empty( $visual ) && function_exists( 'lunara_normalize_visual_package_image_sizes' ) ) {
		$visual = lunara_normalize_visual_package_image_sizes( $visual, 'w500', 'w780' );
	}
	$resolved = trim( (string) ( $visual['backdrop_url'] ?? '' ) );
	return $resolved;
}

/**
 * Preload the hero backdrop on the portal route only.
 */
function lunara_oscars_hero_backdrop_preload() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	$is_portal = function_exists( 'lunara_is_oscars_portal_route' )
		? lunara_is_oscars_portal_route()
		: ( is_page( 'oscars' ) || is_page_template( 'page-oscars.php' ) );
	if ( ! $is_portal ) {
		return;
	}
	$url = lunara_oscars_hero_backdrop_url();
	if ( '' === $url ) {
		return;
	}
	printf( '<link rel="preload" as="image" href="%s" fetchpriority="high" />' . "\n", esc_url( $url ) );
}
add_action( 'wp_head', 'lunara_oscars_hero_backdrop_preload', 4 );

/**
 * Tell a poster <img> how wide the portal renders it.
 *
 * Replaces the `sizes` attribute on the first <img> in $html, keeping a
 * leading `auto, ` (WordPress adds it for lazy images; Chromium uses the
 * laid-out width) in front of the new value. An <img> with no srcset is
 * returned unchanged: `sizes` means nothing without candidates.
 *
 * @param string $html  Poster markup from the Ledger.
 * @param string $sizes The sizes value, e.g. "(max-width: 480px) 96px, 300px".
 * @return string
 */
function lunara_oscars_poster_sizes( $html, $sizes ) {
	$html  = (string) $html;
	$sizes = trim( (string) $sizes );
	if ( '' === $html || '' === $sizes || ! preg_match( '/<img\b[^>]*\bsrcset="/i', $html ) ) {
		return $html;
	}
	if ( preg_match( '/\ssizes="([^"]*)"/i', $html, $m ) ) {
		$auto = preg_match( '/^\s*auto\s*,/i', $m[1] ) ? 'auto, ' : '';
		return preg_replace( '/\ssizes="[^"]*"/i', ' sizes="' . esc_attr( $auto . $sizes ) . '"', $html, 1 );
	}
	$auto = preg_match( '/\sloading="lazy"/i', $html ) ? 'auto, ' : '';
	return preg_replace( '/<img\b/i', '<img sizes="' . esc_attr( $auto . $sizes ) . '"', $html, 1 );
}

/**
 * The portal's measured poster widths, by slot.
 *
 * @param string $slot hero|grid.
 * @return string
 */
function lunara_oscars_poster_sizes_for( $slot ) {
	switch ( $slot ) {
		case 'hero':
			return '(max-width: 480px) 96px, (max-width: 900px) 124px, 300px';
		default:
			return '(max-width: 640px) 96px, (max-width: 900px) 340px, 300px';
	}
}
