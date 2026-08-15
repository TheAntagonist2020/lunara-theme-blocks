<?php
/**
 * Behavioral regression for Journal archive responsive card media.
 *
 * Run: php tests/journal-archive-responsive-runtime.php
 */

define( 'ABSPATH', __DIR__ . '/' );

$lunara_test_is_archive = true;
$lunara_test_is_paged   = false;
$lunara_test_is_tax     = false;
$lunara_test_image_calls = array();
$lunara_test_image_outputs = array();
$lunara_test_photon_calls = array();
$lunara_test_photon_available = true;
$lunara_test_photon_url_mode = 'https';
$lunara_test_attachments = array(
	101 => array(
		'post_type' => 'attachment',
		'image'     => true,
		'url'       => 'https://example.test/wp-content/uploads/native-wide.jpg',
		'width'     => 1800,
		'height'    => 1013,
		'html_route' => '<img class="lunara-review-grid-poster" width="1800" height="1013" src="native-wide.jpg" srcset="https://example.test/wp-content/uploads/native-768.jpg 768w, https://example.test/wp-content/uploads/native-wide.jpg 1800w" sizes="380px" alt="Native wide still">',
		'html_full'  => '<img class="lunara-review-grid-poster" width="1800" height="1013" src="native-wide.jpg" srcset="https://example.test/wp-content/uploads/native-768.jpg 768w, https://example.test/wp-content/uploads/native-wide.jpg 1800w" sizes="380px" alt="Native wide still">',
	),
	102 => array(
		'post_type' => 'attachment',
		'image'     => true,
		'url'       => 'https://example.test/wp-content/uploads/full-only-landscape.jpg',
		'width'     => 1800,
		'height'    => 1200,
		'html_route' => '<img class="lunara-review-grid-poster" width="1800" height="1080" src="full-only-landscape-1800x1080.jpg" sizes="380px" alt="Landscape still">',
		'html_full'  => '<img class="lunara-review-grid-poster" width="1800" height="1200" src="full-only-landscape.jpg" srcset="https://example.test/wp-content/uploads/landscape-768.jpg 768w, https://example.test/wp-content/uploads/full-only-landscape.jpg 1800w" sizes="380px" alt="Landscape still">',
	),
	103 => array(
		'post_type' => 'attachment',
		'image'     => true,
		'url'       => 'https://example.test/wp-content/uploads/portrait.webp',
		'width'     => 768,
		'height'    => 960,
		'html_route' => '<img class="lunara-review-grid-poster" width="768" height="960" src="portrait.webp" sizes="380px" alt="Portrait key art">',
		'html_full'  => '<img class="lunara-review-grid-poster" width="768" height="960" src="portrait.webp" sizes="380px" alt="Portrait key art">',
	),
	104 => array(
		'post_type' => 'attachment',
		'image'     => true,
		'url'       => 'https://example.test/wp-content/uploads/no-registered-sizes.jpg',
		'width'     => 3006,
		'height'    => 2160,
		'html_route' => '<img class="lunara-review-grid-poster" width="1920" height="1080" src="no-registered-sizes-1920x1080.jpg" sizes="380px" alt="Large editorial still">',
		'html_full'  => '<img class="lunara-review-grid-poster" width="3006" height="2160" src="no-registered-sizes.jpg" sizes="380px" alt="Large editorial still">',
	),
	105 => array(
		'post_type' => 'post',
		'image'     => true,
		'url'       => 'https://example.test/wp-content/uploads/not-attachment.jpg',
		'width'     => 1200,
		'height'    => 800,
		'html_route' => '<img src="not-attachment.jpg">',
		'html_full'  => '<img src="not-attachment.jpg">',
	),
	106 => array(
		'post_type' => 'attachment',
		'image'     => false,
		'url'       => 'https://example.test/wp-content/uploads/document.pdf',
		'width'     => 0,
		'height'    => 0,
		'html_route' => '',
		'html_full'  => '',
	),
	107 => array(
		'post_type' => 'attachment',
		'image'     => true,
		'url'       => 'https://image.tmdb.org/t/p/original/remote.jpg',
		'width'     => 1200,
		'height'    => 800,
		'html_route' => '<img class="lunara-review-grid-poster" width="1200" height="800" src="https://image.tmdb.org/t/p/original/remote.jpg" sizes="380px" alt="Remote still">',
		'html_full'  => '<img class="lunara-review-grid-poster" width="1200" height="800" src="https://image.tmdb.org/t/p/original/remote.jpg" sizes="380px" alt="Remote still">',
	),
	108 => array(
		'post_type' => 'attachment',
		'image'     => true,
		'url'       => 'https://example.test/wp-content/uploads/full-without-native-set.jpg',
		'width'     => 1800,
		'height'    => 1200,
		'html_route' => '<img class="lunara-review-grid-poster" width="1800" height="1080" src="full-without-native-set-1800x1080.jpg" sizes="380px" alt="Full-only still">',
		'html_full'  => '<img class="lunara-review-grid-poster" width="1800" height="1200" src="full-without-native-set.jpg" sizes="380px" alt="Full-only still">',
	),
	109 => array(
		'post_type' => 'attachment',
		'image'     => true,
		'url'       => 'https://example.test/wp-content/uploads/one-pixel.png',
		'width'     => 1,
		'height'    => 1,
		'html_route' => '<img class="lunara-review-grid-poster" width="1" height="1" src="one-pixel.png" sizes="380px" alt="Tiny source">',
		'html_full'  => '<img class="lunara-review-grid-poster" width="1" height="1" src="one-pixel.png" sizes="380px" alt="Tiny source">',
	),
	110 => array(
		'post_type' => 'attachment',
		'image'     => true,
		'url'       => 'https://i0.wp.com/example.test/wp-content/uploads/proxied-local.jpg?quality=86&ssl=1',
		'width'     => 1200,
		'height'    => 800,
		'html_route' => '<img class="lunara-review-grid-poster" width="1200" height="675" src="proxied-local-1200x675.jpg" sizes="380px" alt="Proxied local still">',
		'html_full'  => '<img class="lunara-review-grid-poster" width="1200" height="800" src="proxied-local.jpg" sizes="380px" alt="Proxied local still">',
	),
	111 => array(
		'post_type' => 'attachment',
		'image'     => true,
		'url'       => 'https://example.test/wp-content/uploads/undersized-native.jpg',
		'width'     => 767,
		'height'    => 431,
		'html_route' => '<img class="lunara-review-grid-poster" width="767" height="431" src="undersized-native.jpg" srcset="https://example.test/wp-content/uploads/undersized-480.jpg 480w, https://example.test/wp-content/uploads/undersized-native.jpg 767w" sizes="380px" alt="Undersized still">',
		'html_full'  => '<img class="lunara-review-grid-poster" width="767" height="431" src="undersized-native.jpg" srcset="https://example.test/wp-content/uploads/undersized-480.jpg 480w, https://example.test/wp-content/uploads/undersized-native.jpg 767w" sizes="380px" alt="Undersized still">',
	),
);

function lunara_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function absint( $value ) { return abs( (int) $value ); }
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $value ) { return (string) $value; }
function esc_url_raw( $value ) { return filter_var( (string) $value, FILTER_VALIDATE_URL ) ? (string) $value : ''; }
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( (string) $url, $component ); }
function get_post_type( $id ) { global $lunara_test_attachments; return isset( $lunara_test_attachments[ $id ] ) ? $lunara_test_attachments[ $id ]['post_type'] : false; }
function wp_attachment_is_image( $id ) { global $lunara_test_attachments; return ! empty( $lunara_test_attachments[ $id ]['image'] ); }
function wp_get_attachment_url( $id ) { global $lunara_test_attachments; return isset( $lunara_test_attachments[ $id ] ) ? $lunara_test_attachments[ $id ]['url'] : false; }
function wp_get_attachment_metadata( $id ) {
	global $lunara_test_attachments;
	return isset( $lunara_test_attachments[ $id ] )
		? array( 'width' => $lunara_test_attachments[ $id ]['width'], 'height' => $lunara_test_attachments[ $id ]['height'], 'sizes' => array() )
		: false;
}
function wp_get_attachment_image( $id, $size, $icon, $attrs ) {
	global $lunara_test_attachments, $lunara_test_image_calls, $lunara_test_image_outputs;
	$lunara_test_image_calls[] = array( 'id' => $id, 'size' => $size, 'attrs' => $attrs );
	if ( ! isset( $lunara_test_attachments[ $id ] ) ) {
		return '';
	}
	$html = 'full' === $size ? $lunara_test_attachments[ $id ]['html_full'] : $lunara_test_attachments[ $id ]['html_route'];
	if ( 101 === $id || '' === $html ) {
		$lunara_test_image_outputs[] = $html;
		return $html;
	}
	foreach ( array( 'class', 'loading', 'fetchpriority', 'decoding', 'sizes', 'alt' ) as $name ) {
		if ( ! isset( $attrs[ $name ] ) ) {
			continue;
		}
		$value = esc_attr( $attrs[ $name ] );
		if ( preg_match( '/\s' . preg_quote( $name, '/' ) . '=("|\').*?\1/is', $html ) ) {
			$html = preg_replace( '/\s' . preg_quote( $name, '/' ) . '=("|\').*?\1/is', ' ' . $name . '="' . $value . '"', $html, 1 );
		} else {
			$html = preg_replace( '/<img\b/i', '<img ' . $name . '="' . $value . '"', $html, 1 );
		}
	}
	$lunara_test_image_outputs[] = $html;
	return $html;
}
function jetpack_photon_url( $url, $args = array(), $scheme = null ) {
	global $lunara_test_photon_available, $lunara_test_photon_calls, $lunara_test_photon_url_mode;
	$lunara_test_photon_calls[] = array( 'url' => $url, 'args' => $args, 'scheme' => $scheme );
	if ( ! $lunara_test_photon_available || empty( $args['w'] ) || ! is_numeric( $args['w'] ) ) {
		return '';
	}
	$parts = parse_url( (string) $url );
	if ( empty( $parts['host'] ) || empty( $parts['path'] ) ) {
		return '';
	}
	$origin = 'http' === $lunara_test_photon_url_mode
		? 'http://i0.wp.com'
		: ( 'port' === $lunara_test_photon_url_mode ? 'https://i0.wp.com:444' : 'https://i0.wp.com' );
	return $origin . '/' . $parts['host'] . $parts['path'] . '?w=' . absint( $args['w'] ) . '&quality=86&ssl=1';
}
function is_post_type_archive( $type = '' ) { global $lunara_test_is_archive; return 'journal' === $type && $lunara_test_is_archive; }
function is_paged() { global $lunara_test_is_paged; return $lunara_test_is_paged; }
function is_tax( $taxonomies = '' ) { global $lunara_test_is_tax; return $lunara_test_is_tax; }

$module = dirname( __DIR__ ) . '/inc/journal-archive-media.php';
lunara_test_assert( is_file( $module ), 'The Journal archive media module must exist.' );
require_once $module;

function lunara_test_img_attr( $html, $name ) {
	if ( preg_match( '/\s' . preg_quote( $name, '/' ) . '=("|\')(.*?)\1/is', (string) $html, $match ) ) {
		return html_entity_decode( (string) $match[2], ENT_QUOTES, 'UTF-8' );
	}
	return '';
}

function lunara_test_srcset_candidates( $html ) {
	$srcset = lunara_test_img_attr( $html, 'srcset' );
	$result = array();
	foreach ( preg_split( '/,\s*/', $srcset ) as $candidate ) {
		if ( preg_match( '/^(\S+)\s+(\d+)w$/', trim( (string) $candidate ), $match ) ) {
			$result[] = array( 'url' => $match[1], 'width' => (int) $match[2] );
		}
	}
	return $result;
}

$base_attrs = array(
	'class'         => 'lunara-review-grid-poster',
	'loading'       => 'lazy',
	'fetchpriority' => 'auto',
	'decoding'      => 'async',
	'sizes'         => '(max-width: 640px) 92vw, (max-width: 980px) 46vw, (max-width: 1280px) 31vw, 380px',
	'alt'           => 'A useful editorial description',
);

$native = lunara_journal_archive_card_image_markup( 101, $base_attrs );
lunara_test_assert( $native === $lunara_test_attachments[101]['html_route'], 'A working route-size native responsive image must round-trip byte-for-byte.' );
lunara_test_assert( 1 === count( $lunara_test_image_calls ) && 'lunara-hero-spotlight' === $lunara_test_image_calls[0]['size'], 'A working route-size srcset must not trigger a full-size probe.' );

$photon_before = count( $lunara_test_photon_calls );
$recovered_native = lunara_journal_archive_card_image_markup( 102, $base_attrs );
lunara_test_assert( $recovered_native === end( $lunara_test_image_outputs ), 'A missing route srcset must prefer a native uncropped full srcset before CDN synthesis.' );
lunara_test_assert( count( $lunara_test_photon_calls ) === $photon_before, 'A recovered native full srcset must not be rewritten through Photon.' );

foreach ( array( 108, 103, 104, 110 ) as $id ) {
	$markup     = lunara_journal_archive_card_image_markup( $id, $base_attrs );
	$candidates = lunara_test_srcset_candidates( $markup );
	$source     = $lunara_test_attachments[ $id ];
	$widths     = array_column( $candidates, 'width' );

	lunara_test_assert( count( $candidates ) >= 2 && count( $candidates ) <= 6, "Attachment {$id} must receive a compact, bounded responsive fallback." );
	lunara_test_assert( count( $widths ) === count( array_unique( $widths ) ), "Attachment {$id} fallback widths must be unique." );
	for ( $candidate_index = 1; $candidate_index < count( $widths ); $candidate_index++ ) {
		lunara_test_assert( $widths[ $candidate_index ] > $widths[ $candidate_index - 1 ], "Attachment {$id} fallback widths must be strictly ascending." );
	}
	lunara_test_assert( max( $widths ) <= min( 1920, $source['width'] ), "Attachment {$id} must never upscale or exceed the 1920px route ceiling." );
	lunara_test_assert( (string) $source['width'] === lunara_test_img_attr( $markup, 'width' ) && (string) $source['height'] === lunara_test_img_attr( $markup, 'height' ), "Attachment {$id} must preserve honest intrinsic dimensions." );
	lunara_test_assert( '(max-width: 640px) 92vw, (max-width: 980px) 46vw, (max-width: 1280px) 31vw, 380px' === lunara_test_img_attr( $markup, 'sizes' ), "Attachment {$id} must preserve the route sizes contract." );
	lunara_test_assert( 'A useful editorial description' === lunara_test_img_attr( $markup, 'alt' ), "Attachment {$id} must preserve meaningful alt text." );
	lunara_test_assert( strlen( $markup ) < 4096, "Attachment {$id} responsive markup must stay compact." );

	foreach ( $candidates as $candidate ) {
		lunara_test_assert( 'i0.wp.com' === parse_url( $candidate['url'], PHP_URL_HOST ), "Attachment {$id} fallback must use the WordPress.com Image CDN only." );
		parse_str( (string) parse_url( $candidate['url'], PHP_URL_QUERY ), $query );
		lunara_test_assert( isset( $query['w'] ) && $candidate['width'] === (int) $query['w'], "Attachment {$id} candidate must use an honest width-only CDN request." );
		lunara_test_assert( ! isset( $query['resize'] ) && ! isset( $query['fit'] ) && ! isset( $query['h'] ), "Attachment {$id} fallback must preserve source aspect instead of forcing a crop." );
	}
}

lunara_test_assert( '' === lunara_journal_archive_card_image_markup( 105, $base_attrs ), 'A non-attachment ID must degrade to a text-led card.' );
lunara_test_assert( '' === lunara_journal_archive_card_image_markup( 106, $base_attrs ), 'A non-image attachment must degrade to a text-led card.' );
lunara_test_assert( '' === lunara_journal_archive_card_image_markup( 999, $base_attrs ), 'A missing attachment must degrade to a text-led card.' );
lunara_test_assert( '' === lunara_journal_archive_card_image_markup( 107, $base_attrs ), 'A remote attachment without native responsive markup must not receive guessed CDN derivatives.' );
lunara_test_assert( false === strpos( lunara_journal_archive_card_image_markup( 110, $base_attrs ), 'i0.wp.com/i0.wp.com' ), 'An already proxied local attachment must not be double-proxied.' );
lunara_test_assert( '' === lunara_journal_archive_card_image_markup( 109, $base_attrs ), 'A source too small for two honest candidates must degrade to text-led instead of upscaling.' );
lunara_test_assert( '' === lunara_journal_archive_card_image_markup( 111, $base_attrs ), 'A 767px source must degrade to text-led even when it has a native srcset.' );
lunara_test_assert( count( lunara_test_srcset_candidates( lunara_journal_archive_card_image_markup( 103, $base_attrs ) ) ) >= 2, 'A 768px portrait source must remain eligible for honest responsive delivery.' );

$lunara_test_photon_available = false;
lunara_test_assert( '' === lunara_journal_archive_card_image_markup( 108, $base_attrs ), 'An unavailable WordPress.com Image CDN must fail closed to a text-led card.' );
$lunara_test_photon_available = true;

$lunara_test_photon_url_mode = 'http';
lunara_test_assert( '' === lunara_journal_archive_card_image_markup( 108, $base_attrs ), 'An HTTP Image CDN result must fail closed.' );
$lunara_test_photon_url_mode = 'port';
lunara_test_assert( '' === lunara_journal_archive_card_image_markup( 108, $base_attrs ), 'An Image CDN result with a non-default port must fail closed.' );
$lunara_test_photon_url_mode = 'https';

$lunara_test_is_archive = true;
$lunara_test_is_paged   = false;
$lunara_test_is_tax     = false;
lunara_test_assert( lunara_journal_archive_card_is_visual_lead( 1 ), 'The first unpaged main Journal card must remain the visual lead.' );
lunara_test_assert( ! lunara_journal_archive_card_is_visual_lead( 2 ), 'Only one card may be the visual lead.' );
$lead_attrs = lunara_journal_archive_card_image_attributes( true, 'Lead still' );
lunara_test_assert( 'eager' === $lead_attrs['loading'] && 'high' === $lead_attrs['fetchpriority'], 'The page-one visual lead alone must be eager/high.' );

$lunara_test_is_paged = true;
lunara_test_assert( ! lunara_journal_archive_card_is_visual_lead( 1 ), 'Page two and later must not emit a visual lead.' );
$regular_attrs = lunara_journal_archive_card_image_attributes( false, 'Regular still' );
lunara_test_assert( 'lazy' === $regular_attrs['loading'] && 'high' !== $regular_attrs['fetchpriority'], 'Paged cards must remain lazy and non-high.' );

$lunara_test_is_paged   = false;
$lunara_test_is_archive = false;
$lunara_test_is_tax     = true;
lunara_test_assert( ! lunara_journal_archive_card_is_visual_lead( 1 ), 'Journal taxonomy archives must remain uniform without a visual lead.' );

fwrite( STDOUT, "Journal archive responsive media runtime passed.\n" );
