<?php
/**
 * Cinematic homepage front-door bridge.
 *
 * This keeps plugin-backed hero experiments behind explicit gates while the
 * homepage migrates away from shortcode-controlled structural composition.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current homepage front door should be replaced by the cinematic
 * wrapper. Defaults off so the branch can ship without surprising production.
 */
function lunara_home_cinematic_front_door_is_enabled() {
	$enabled = (bool) get_theme_mod( 'lunara_home_cinematic_front_door_enabled', false );

	return (bool) apply_filters( 'lunara_home_cinematic_front_door_enabled', $enabled );
}

/**
 * Whether a third-party slider shortcode is allowed to render the front door.
 */
function lunara_home_plugin_hero_is_allowed() {
	$enabled = (bool) get_theme_mod( 'lunara_home_plugin_hero_enabled', false );

	return (bool) apply_filters( 'lunara_home_plugin_hero_enabled', $enabled );
}

/**
 * Read the configured Responsive Zoom In/Out slider shortcode.
 */
function lunara_home_plugin_hero_shortcode() {
	$shortcode = trim( (string) get_theme_mod( 'lunara_home_plugin_hero_shortcode', '' ) );

	return trim( (string) apply_filters( 'lunara_home_plugin_hero_shortcode', $shortcode ) );
}

/**
 * Extract the shortcode tag from a shortcode string.
 *
 * @param string $shortcode Shortcode markup.
 * @return string
 */
function lunara_home_extract_shortcode_tag( $shortcode ) {
	if ( ! is_string( $shortcode ) || '' === trim( $shortcode ) ) {
		return '';
	}

	if ( preg_match( '/^\s*\[([A-Za-z0-9_-]+)/', $shortcode, $matches ) ) {
		return sanitize_key( $matches[1] );
	}

	return '';
}

/**
 * Render the native theme hero fallback inside the front-door wrapper.
 */
function lunara_render_native_home_hero_fallback() {
	if ( ! function_exists( 'lunara_render_cinematic_hero_carousel' ) ) {
		return '';
	}

	$fallback = trim(
		(string) lunara_render_cinematic_hero_carousel(
			array(
				'context' => 'home-front-door',
			)
		)
	);

	if ( '' === $fallback ) {
		return '';
	}

	return '<div class="lunara-home-cinematic-front-door is-native-fallback" data-lunara-home-hero-source="native">' . $fallback . '</div>';
}

/**
 * Render the guarded plugin-backed hero wrapper.
 */
function lunara_render_plugin_backed_home_hero() {
	if ( ! lunara_home_cinematic_front_door_is_enabled() ) {
		return '';
	}

	$shortcode     = lunara_home_plugin_hero_shortcode();
	$shortcode_tag = lunara_home_extract_shortcode_tag( $shortcode );

	if (
		lunara_home_plugin_hero_is_allowed()
		&& '' !== $shortcode
		&& '' !== $shortcode_tag
		&& shortcode_exists( $shortcode_tag )
	) {
		ob_start();
		?>
		<section class="lunara-home-cinematic-front-door lunara-home-plugin-hero is-plugin-backed" data-lunara-home-hero-source="plugin" aria-label="<?php esc_attr_e( 'Featured Lunara Film stories', 'lunara-film' ); ?>">
			<div class="lunara-home-plugin-hero-frame">
				<?php echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted theme setting gated by filter/theme mod. ?>
			</div>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	return lunara_render_native_home_hero_fallback();
}

/**
 * Add homepage state classes for visual QA and targeted CSS.
 *
 * @param array $classes Body classes.
 * @return array
 */
function lunara_cinematic_home_body_classes( $classes ) {
	if ( is_front_page() && lunara_home_cinematic_front_door_is_enabled() ) {
		$classes[] = 'lunara-home-cinematic-front-door-enabled';

		if ( lunara_home_plugin_hero_is_allowed() ) {
			$classes[] = 'lunara-home-plugin-hero-enabled';
		}
	}

	return $classes;
}
add_filter( 'body_class', 'lunara_cinematic_home_body_classes' );

/**
 * Front-page cinematic polish shared by the guarded hero and lower homepage.
 */
function lunara_enqueue_cinematic_home_assets() {
	if ( is_admin() || is_feed() || ! is_front_page() ) {
		return;
	}

	$css = lunara_resolve_theme_asset(
		'assets/css/lunara-cinematic-home.css',
		array( 'assets/css/lunara-cinematic-home.css' )
	);
	if ( $css['uri'] ) {
		wp_enqueue_style(
			'lunara-cinematic-home',
			$css['uri'],
			array(),
			lunara_theme_asset_version( $css['path'] )
		);
	}

	$js = lunara_resolve_theme_asset(
		'assets/js/lunara-cinematic-home.js',
		array( 'assets/js/lunara-cinematic-home.js' )
	);
	if ( $js['uri'] ) {
		wp_enqueue_script(
			'lunara-cinematic-home',
			$js['uri'],
			array(),
			lunara_theme_asset_version( $js['path'] ),
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'lunara_enqueue_cinematic_home_assets', 25 );
