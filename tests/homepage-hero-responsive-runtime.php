<?php
/**
 * Runtime contract for the attachment-backed homepage hero delivery pipeline.
 *
 * This deliberately returns already-filtered attachment markup from
 * wp_get_attachment_image(). The delivery layer must parse and reuse that exact
 * markup; rebuilding srcset separately would make the preload drift from the
 * image after WordPress.com/Jetpack filters run.
 */

$module = dirname( __DIR__ ) . '/inc/hero-delivery.php';
if ( ! is_file( $module ) ) {
	fwrite( STDERR, "Missing inc/hero-delivery.php\n" );
	exit( 1 );
}

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['lunara_actions']          = array();
$GLOBALS['lunara_attachment_lookups'] = array();
$GLOBALS['lunara_wp_image_calls']   = 0;
$GLOBALS['lunara_wp_image_sizes']   = array();
$GLOBALS['lunara_slides']           = array();
$GLOBALS['lunara_command_slides']   = array();
$GLOBALS['lunara_static_data']      = null;
$GLOBALS['lunara_is_front_page']    = true;
$GLOBALS['lunara_front_door']       = true;
$GLOBALS['lunara_plugin_shortcode'] = '';
$GLOBALS['lunara_plugin_allowed']   = false;
$GLOBALS['lunara_shortcode_exists'] = false;

$GLOBALS['lunara_slide_reads']      = 0;

function lunara_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function add_action( $hook, $callback, $priority = 10 ) {
	$GLOBALS['lunara_actions'][] = array( $hook, $callback, $priority );
}

function is_admin() {
	return false;
}

function is_front_page() {
	return (bool) $GLOBALS['lunara_is_front_page'];
}

function lunara_home_cinematic_front_door_is_enabled() {
	return (bool) $GLOBALS['lunara_front_door'];
}

function lunara_home_plugin_hero_shortcode() {
	return (string) $GLOBALS['lunara_plugin_shortcode'];
}

function lunara_home_extract_shortcode_tag( $shortcode ) {
	return '' !== trim( (string) $shortcode ) ? 'plugin_hero' : '';
}

function lunara_home_plugin_hero_is_allowed() {
	return (bool) $GLOBALS['lunara_plugin_allowed'];
}

function shortcode_exists( $tag ) {
	return (bool) $GLOBALS['lunara_shortcode_exists'];
}

function lunara_get_home_cinematic_hero_slides() {
	++$GLOBALS['lunara_slide_reads'];
	return $GLOBALS['lunara_slides'];
}

function lunara_hero_command_slides() {
	return $GLOBALS['lunara_command_slides'];
}

function lunara_get_cinematic_hero_data( $attrs = array() ) {
	return $GLOBALS['lunara_static_data'];
}

function home_url( $path = '/' ) {
	return 'https://lunarafilm.com' . $path;
}

function attachment_url_to_postid( $url ) {
	$GLOBALS['lunara_attachment_lookups'][] = $url;
	return 0 === strpos( $url, 'https://lunarafilm.com/wp-content/uploads/2026/08/Spider-Man' ) ? 42 : 0;
}

function wp_get_attachment_image( $attachment_id, $size, $icon = false, $attrs = array() ) {
	++$GLOBALS['lunara_wp_image_calls'];
	$GLOBALS['lunara_wp_image_sizes'][] = $size;
	lunara_test_assert( 42 === (int) $attachment_id, 'Resolved attachment ID must reach WordPress image rendering.' );
	lunara_test_assert( 'full' === $size, 'Attachment-backed heroes must preserve the uncropped source composition.' );

	$class    = isset( $attrs['class'] ) ? $attrs['class'] : '';
	$style    = isset( $attrs['style'] ) ? $attrs['style'] : '';
	$loading  = isset( $attrs['loading'] ) ? $attrs['loading'] : '';
	$priority = isset( $attrs['fetchpriority'] ) ? $attrs['fetchpriority'] : '';
	$sizes    = isset( $attrs['sizes'] ) ? $attrs['sizes'] : '';

	return sprintf(
		'<img data-jp-lcp-optimized="true" width="3038" height="1713" src="https://i0.wp.com/lunarafilm.com/wp-content/uploads/2026/08/Spider-Man-Brand-New-Day.jpg?ssl=1" class="%s" style="%s" alt="" loading="%s" decoding="async" fetchpriority="%s" sizes="%s" srcset="https://i0.wp.com/lunarafilm.com/wp-content/uploads/2026/08/Spider-Man-768x433.jpg?ssl=1 768w, https://i0.wp.com/lunarafilm.com/wp-content/uploads/2026/08/Spider-Man-1080x609.jpg?ssl=1 1080w, https://i0.wp.com/lunarafilm.com/wp-content/uploads/2026/08/Spider-Man-1920x1080.jpg?ssl=1 1920w, https://i0.wp.com/lunarafilm.com/wp-content/uploads/2026/08/Spider-Man-Brand-New-Day.jpg?ssl=1 3038w" />',
		htmlspecialchars( $class, ENT_QUOTES, 'UTF-8' ),
		htmlspecialchars( $style, ENT_QUOTES, 'UTF-8' ),
		htmlspecialchars( $loading, ENT_QUOTES, 'UTF-8' ),
		htmlspecialchars( $priority, ENT_QUOTES, 'UTF-8' ),
		htmlspecialchars( $sizes, ENT_QUOTES, 'UTF-8' )
	);
}

function esc_url_raw( $url ) {
	return (string) $url;
}

function esc_url( $url ) {
	return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function absint( $value ) {
	return abs( (int) $value );
}

require $module;

// template_redirect runs on every route. Ownership checks must stop before
// touching homepage data whenever the native front door is not responsible.
$GLOBALS['lunara_is_front_page'] = false;
lunara_test_assert( '' === lunara_get_home_cinematic_hero_http_link_value(), 'Non-home routes must never emit the homepage hero hint.' );
lunara_test_assert( 0 === $GLOBALS['lunara_slide_reads'] && 0 === $GLOBALS['lunara_wp_image_calls'], 'Non-home routes must not resolve the homepage deck or image markup.' );

$GLOBALS['lunara_is_front_page'] = true;
$GLOBALS['lunara_front_door']    = false;
lunara_test_assert( '' === lunara_get_home_cinematic_hero_http_link_value(), 'Disabled native front door must never emit its hero hint.' );
lunara_test_assert( 0 === $GLOBALS['lunara_slide_reads'] && 0 === $GLOBALS['lunara_wp_image_calls'], 'Disabled front door must not resolve hero data.' );

$GLOBALS['lunara_front_door']       = true;
$GLOBALS['lunara_plugin_shortcode'] = '[plugin_hero]';
$GLOBALS['lunara_plugin_allowed']   = true;
$GLOBALS['lunara_shortcode_exists'] = true;
lunara_test_assert( '' === lunara_get_home_cinematic_hero_http_link_value(), 'Plugin-owned front door must own its own resource hints.' );
lunara_test_assert( 0 === $GLOBALS['lunara_slide_reads'] && 0 === $GLOBALS['lunara_wp_image_calls'], 'Plugin-owned front door must not resolve native hero data.' );

$GLOBALS['lunara_plugin_shortcode'] = '';
$GLOBALS['lunara_plugin_allowed']   = false;
$GLOBALS['lunara_shortcode_exists'] = false;

// Remote provider art must never incur an attachment-table lookup. Repeated
// local resolution must reuse the request cache shared by qualification/build.
$lookups_before = count( $GLOBALS['lunara_attachment_lookups'] );
lunara_test_assert( 0 === lunara_hero_attachment_id_from_url( 'https://image.tmdb.org/t/p/original/remote.jpg' ), 'Remote TMDB art is not a local attachment.' );
lunara_test_assert( $lookups_before === count( $GLOBALS['lunara_attachment_lookups'] ), 'Remote hero URLs must not query attachment storage.' );
$local_lookup = 'https://lunarafilm.com/wp-content/uploads/2026/08/Spider-Man-local.jpg';
lunara_test_assert( 42 === lunara_hero_attachment_id_from_url( $local_lookup ), 'Local upload must resolve to media.' );
$lookups_after_local = count( $GLOBALS['lunara_attachment_lookups'] );
lunara_test_assert( 42 === lunara_hero_attachment_id_from_url( $local_lookup ), 'Repeated local upload resolution must remain stable.' );
lunara_test_assert( $lookups_after_local === count( $GLOBALS['lunara_attachment_lookups'] ), 'Repeated local upload resolution must use the request cache.' );

$original = 'https://i0.wp.com/lunarafilm.com/wp-content/uploads/2026/08/Spider-Man-Brand-New-Day.jpg?ssl=1';
$slide    = array(
	'image'         => $original,
	'attachment_id' => 42,
	'focal_x'       => 50,
	'focal_y'       => 30,
	'zoom'          => 100,
	'fit'           => 'full',
);

$descriptor = lunara_build_cinematic_hero_image_descriptor( $slide, true );
$expected_srcset = 'https://i0.wp.com/lunarafilm.com/wp-content/uploads/2026/08/Spider-Man-768x433.jpg?ssl=1 768w, https://i0.wp.com/lunarafilm.com/wp-content/uploads/2026/08/Spider-Man-1080x609.jpg?ssl=1 1080w, https://i0.wp.com/lunarafilm.com/wp-content/uploads/2026/08/Spider-Man-1920x1080.jpg?ssl=1 1920w, https://i0.wp.com/lunarafilm.com/wp-content/uploads/2026/08/Spider-Man-Brand-New-Day.jpg?ssl=1 3038w';

lunara_test_assert( 42 === $descriptor['attachment_id'], 'Descriptor must preserve attachment identity.' );
lunara_test_assert( 'https://i0.wp.com/lunarafilm.com/wp-content/uploads/2026/08/Spider-Man-Brand-New-Day.jpg?ssl=1' === $descriptor['src'], 'Descriptor must preserve the realistic final full-image fallback src.' );
lunara_test_assert( $expected_srcset === $descriptor['srcset'], 'Descriptor must parse the final filtered srcset byte-for-byte after entity decoding.' );
lunara_test_assert( '100vw' === $descriptor['sizes'], 'Full-bleed hero sizes must match its viewport slot.' );
lunara_test_assert( 3038 === $descriptor['width'] && 1713 === $descriptor['height'], 'Attachment hero must carry explicit intrinsic dimensions.' );
lunara_test_assert( false !== strpos( $descriptor['html'], 'loading="eager"' ) && false !== strpos( $descriptor['html'], 'fetchpriority="high"' ), 'Priority hero must stay eager/high.' );
lunara_test_assert( false !== strpos( $descriptor['html'], 'is-full-frame' ), 'Fit mode class must remain on final image markup.' );
lunara_test_assert( 'full' === $GLOBALS['lunara_wp_image_sizes'][0], 'Full Frame must preserve the attachment composition instead of requesting a hard crop.' );
lunara_test_assert( 1 === $GLOBALS['lunara_wp_image_calls'], 'Descriptor must build final attachment markup once.' );
lunara_test_assert( $descriptor['html'] === lunara_render_cinematic_hero_image( $slide, true ), 'Renderer must reuse the cached final image markup.' );
lunara_test_assert( 1 === $GLOBALS['lunara_wp_image_calls'], 'Renderer/preload parity must not build a second filtered image.' );
$second_descriptor = lunara_build_cinematic_hero_image_descriptor( $slide, false );
lunara_test_assert( false !== strpos( $second_descriptor['html'], 'loading="lazy"' ) && false !== strpos( $second_descriptor['html'], 'fetchpriority="low"' ), 'The same attachment must be lazy/low outside the first LCP slot.' );
$cover_descriptor = lunara_build_cinematic_hero_image_descriptor( array_merge( $slide, array( 'fit' => 'cover' ) ), false );
lunara_test_assert( 'full' === end( $GLOBALS['lunara_wp_image_sizes'] ), 'Cover mode must retain all source pixels for the existing focal-position controls.' );

// Responsive preload: no fixed href, exact imagesrcset/imagesizes parity.
$GLOBALS['lunara_slides']         = array( $slide, array( 'image' => 'https://example.com/second.jpg' ) );
$GLOBALS['lunara_command_slides'] = array( $slide, array( 'image' => 'https://example.com/second.jpg' ) );
$calls_before_http = $GLOBALS['lunara_wp_image_calls'];
lunara_test_assert( '' === lunara_get_home_cinematic_hero_http_link_value(), 'Attachment-backed responsive hero must omit the fixed HTTP Link header.' );
lunara_test_assert( $calls_before_http === $GLOBALS['lunara_wp_image_calls'], 'HTTP-header routing must not build attachment markup before later image filters initialize.' );
$preload = lunara_get_home_cinematic_hero_preload_descriptor();
lunara_test_assert( $expected_srcset === $preload['srcset'], 'Preload resolver must consume the same final descriptor as the rendered lead.' );

ob_start();
lunara_preload_home_cinematic_hero_image();
$link = trim( (string) ob_get_clean() );
lunara_test_assert( false !== strpos( $link, 'imagesrcset=' ), 'Responsive preload must advertise imagesrcset.' );
lunara_test_assert( false !== strpos( $link, 'imagesizes="100vw"' ), 'Responsive preload must advertise the exact image sizes.' );
lunara_test_assert( ! preg_match( '/\shref=/', $link ), 'Responsive preload must not pin a mismatched fixed href candidate.' );
preg_match( '/imagesrcset="([^"]+)"/', $link, $srcset_match );
lunara_test_assert( isset( $srcset_match[1] ) && $expected_srcset === html_entity_decode( $srcset_match[1], ENT_QUOTES, 'UTF-8' ), 'Preload imagesrcset must equal final IMG srcset after entity decode.' );
lunara_test_assert( false !== strpos( $link, 'Spider-Man-768x433.jpg' ), 'Responsive preload must offer a mobile-sized candidate instead of pinning the full original.' );
lunara_test_assert( ! preg_match( '/\shref=/', $link ), 'The full original may remain a srcset candidate but must never be pinned as responsive preload href.' );

// URL-only fallback keeps exact request parity and derives dimensions only
// when the URL explicitly names a crop.
$raw = array(
	'image' => 'https://image.tmdb.org/example.jpg?resize=1600,900&quality=86',
	'fit'   => 'cover',
);
$raw_descriptor = lunara_build_cinematic_hero_image_descriptor( $raw, false );
lunara_test_assert( '' === $raw_descriptor['srcset'], 'URL-only fallback must not invent responsive candidates.' );
lunara_test_assert( 1600 === $raw_descriptor['width'] && 900 === $raw_descriptor['height'], 'Explicit URL crop may provide honest dimensions.' );
lunara_test_assert( false !== strpos( $raw_descriptor['html'], 'loading="lazy"' ) && false !== strpos( $raw_descriptor['html'], 'fetchpriority="low"' ), 'Non-priority slides must stay lazy/low.' );

$unknown = lunara_build_cinematic_hero_image_descriptor( array( 'image' => 'https://example.com/unknown.jpg' ), true );
lunara_test_assert( 0 === $unknown['width'] && 0 === $unknown['height'], 'Unknown external images must not claim synthetic dimensions.' );
lunara_test_assert( 'https://example.com/unknown.jpg' === $unknown['src'], 'URL-only source must remain exact.' );

$GLOBALS['lunara_slides'] = array(
	array( 'image' => 'https://example.com/unknown.jpg' ),
	array( 'image' => 'https://example.com/second.jpg' ),
);
$GLOBALS['lunara_command_slides'] = $GLOBALS['lunara_slides'];
ob_start();
lunara_preload_home_cinematic_hero_image();
$raw_link = trim( (string) ob_get_clean() );
lunara_test_assert( false !== strpos( $raw_link, 'href="https://example.com/unknown.jpg"' ), 'URL-only HTML preload must use the exact rendered src.' );
lunara_test_assert( false === strpos( $raw_link, 'imagesrcset=' ), 'URL-only HTML preload must not invent responsive candidates.' );
lunara_test_assert( '<https://example.com/unknown.jpg>; rel=preload; as=image; fetchpriority=high' === lunara_get_home_cinematic_hero_http_link_value(), 'URL-only HTTP Link value must match the rendered src exactly.' );

// Jetpack/i0 upload URLs must resolve back to the local attachment URL.
lunara_build_cinematic_hero_image_descriptor(
	array( 'image' => 'https://i0.wp.com/lunarafilm.com/wp-content/uploads/2026/08/Spider-Man.jpg?ssl=1' ),
	true
);
lunara_test_assert(
	in_array( 'https://lunarafilm.com/wp-content/uploads/2026/08/Spider-Man.jpg', $GLOBALS['lunara_attachment_lookups'], true ),
	'Jetpack upload URLs must normalize to a local attachment lookup.'
);

// The carousel falls back to the static renderer for one automatic slide.
// Preload resolution must follow that exact branch rather than preload the
// otherwise-unused automatic slide image.
$GLOBALS['lunara_slides']         = array( array( 'image' => 'https://example.com/unused-auto.jpg' ) );
$GLOBALS['lunara_command_slides'] = array();
$GLOBALS['lunara_static_data']    = array(
	'image_url'     => $original,
	'attachment_id' => 42,
	'fit'           => 'full',
);
$static_preload = lunara_resolve_home_cinematic_hero_lcp_data();
lunara_test_assert( 42 === (int) $static_preload['attachment_id'], 'One automatic slide must preload the static fallback data actually rendered.' );
lunara_test_assert( 'unused-auto.jpg' !== basename( (string) $static_preload['image'] ), 'One-slide static fallback must not preload the unused carousel slide.' );

fwrite( STDOUT, "homepage-hero-responsive-runtime: all assertions passed.\n" );
