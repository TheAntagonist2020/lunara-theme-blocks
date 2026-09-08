<?php
/** Delivery tests execute the live entrypoints with a small WordPress data fixture. */
define( 'ABSPATH', __DIR__ );
if ( ! function_exists( 'mb_substr' ) ) { function mb_substr( $value, $start, $length ) { return substr( $value, $start, $length ); } }
class WP_Post {
	public $ID, $post_type, $post_status, $post_date, $post_title, $post_password = '';
	function __construct( $id, $type = 'journal', $status = 'publish' ) { $this->ID = $id; $this->post_type = $type; $this->post_status = $status; $this->post_date = '2026-09-' . str_pad( $id, 2, '0', STR_PAD_LEFT ); $this->post_title = 'Story ' . $id; }
}
class WP_Query {
	public $posts;
	function __construct( $args ) {
		$GLOBALS['queries'][] = $args;
		$this->posts = array_values( array_filter( $GLOBALS['posts'], static function ( $p ) use ( $args ) { return in_array( $p->post_type, $args['post_type'], true ) && $p->post_status === $args['post_status'] && empty( $p->post_password ); } ) );
		usort( $this->posts, static function ( $a, $b ) { return strcmp( $b->post_date, $a->post_date ); } );
		$this->posts = array_slice( $this->posts, 0, $args['posts_per_page'] );
	}
}
$GLOBALS['options'] = array(); $GLOBALS['posts'] = array(); $GLOBALS['queries'] = array(); $GLOBALS['hooks'] = array();
$GLOBALS['review_sources'] = array(); $GLOBALS['review_legacy_urls'] = array(); $GLOBALS['journal_urls'] = array(); $GLOBALS['featured_images'] = array();
class Lunara_Review_Image_Studio { public static function resolve_slot( $id, $slot ) { return $GLOBALS['review_sources'][ $id ][ $slot ] ?? array(); } }
function add_action( $name, $callback, $priority = 10 ) { $GLOBALS['hooks'][ $name ][] = $callback; }
function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function get_post( $id ) { return $GLOBALS['posts'][ $id ] ?? null; }
function absint( $v ) { return abs( (int) $v ); }
function sanitize_text_field( $v ) { return trim( strip_tags( $v ) ); }
function wp_strip_all_tags( $v ) { return strip_tags( $v ); }
function __( $v, $domain = '' ) { return $v; }
function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); }
function esc_html( $v ) { return esc_attr( $v ); }
function esc_url( $v ) { return esc_attr( $v ); }
function esc_url_raw( $v ) { return $v; }
function esc_html__( $v, $domain = '' ) { return esc_html( $v ); }
function esc_html_e( $v, $domain = '' ) { echo esc_html( $v ); }
function esc_attr_e( $v, $domain = '' ) { echo esc_attr( $v ); }
function get_the_title( $id ) { return get_post( $id )->post_title; }
function get_permalink( $id ) { return '/stories/' . $id . '/'; }
function get_the_date( $format, $id ) { return date( $format, strtotime( get_post( $id )->post_date ) ); }
function get_the_excerpt( $id ) { return 'An original article excerpt with enough detail to identify the source.'; }
function wp_trim_words( $text, $limit, $suffix = '' ) { return $text; }
function get_post_thumbnail_id( $id ) { return $GLOBALS['featured_images'][ $id ] ?? 0; }
function get_the_post_thumbnail_url( $id, $size ) { $image_id = get_post_thumbnail_id( $id ); return $image_id ? wp_get_attachment_image_url( $image_id, $size ) : ''; }
function wp_attachment_is_image( $id ) { return in_array( $id, array( 82, 84, 86, 90, 93 ), true ); }
function wp_get_attachment_image_url( $id, $size ) { return wp_attachment_is_image( $id ) ? ( 90 === $id ? '/tests/fixtures/home-carousel-art.svg' : '/uploads/art-' . $id . '.jpg' ) : ''; }
function attachment_url_to_postid( $url ) { return preg_match( '~art-(\d+)\.jpg$~', $url, $match ) ? (int) $match[1] : 0; }
function wp_get_attachment_image( $id, $size, $icon, $attrs ) { $html = '<img src="' . wp_get_attachment_image_url( $id, $size ) . '" width="1200" height="750"'; foreach ( $attrs as $key => $value ) { $html .= ' ' . $key . '="' . esc_attr( $value ) . '"'; } return $html . ' />'; }
function home_url( $path ) { return $path; }
function wp_cache_get_last_changed( $group ) { return 'fixture'; }
function lunara_hero_command_slides() { return array( array( 'title' => 'Old featured item', 'image' => '/old.jpg' ) ); }
function lunara_get_cinematic_hero_data() { return array( 'title' => 'Old fallback', 'image' => '/old.jpg' ); }
function lunara_get_review_card_image_data( $id, $size, $attrs ) { $source = Lunara_Review_Image_Studio::resolve_slot( $id, 'card' ); if ( isset( $source['mode'] ) && 'off' === $source['mode'] ) { return array( 'url' => '', 'html' => '', 'has_image' => false ); } $url = $source['url'] ?? ( $GLOBALS['review_legacy_urls'][ $id ] ?? '' ); if ( isset( $source['mode'] ) && 'custom' === $source['mode'] && '' === $url ) { return array( 'url' => '', 'html' => '', 'has_image' => false ); } if ( '' === $url ) { $url = get_the_post_thumbnail_url( $id, $size ); } return array( 'url' => $url, 'html' => '', 'has_image' => '' !== $url ); }
function lunara_get_journal_card_image_url( $id, $size ) { return $GLOBALS['journal_urls'][ $id ] ?? get_the_post_thumbnail_url( $id, $size ); }
function lunara_build_hero_slide_for_post( $id ) { return null; }
require dirname( __DIR__ ) . '/inc/home-carousel-settings.php';
require dirname( __DIR__ ) . '/inc/home-carousels.php';
require dirname( __DIR__ ) . '/inc/hero-delivery.php';
function load_delivery_function( $name ) {
	$source = file_get_contents( dirname( __DIR__ ) . '/functions.php' );
	$start = strpos( $source, 'function ' . $name . '(' );
	if ( false === $start ) { throw new RuntimeException( 'Missing live renderer ' . $name ); }
	$tokens = token_get_all( '<?php ' . substr( $source, $start ) );
	$code = ''; $depth = 0; $started = false;
	foreach ( array_slice( $tokens, 1 ) as $token ) {
		$text = is_array( $token ) ? $token[1] : $token; $code .= $text;
		if ( '{' === $token ) { $depth++; $started = true; } elseif ( '}' === $token ) { $depth--; if ( $started && 0 === $depth ) { break; } }
	}
	eval( $code );
}
foreach ( array( 'lunara_get_cinematic_hero_slides', 'lunara_get_home_cinematic_hero_slides', 'lunara_render_cinematic_hero_slide', 'lunara_render_cinematic_hero_carousel', 'lunara_render_homepage_journal_lane' ) as $name ) { load_delivery_function( $name ); }
$checks = 0;
function check_delivery( $value, $message ) { global $checks; $checks++; if ( ! $value ) { throw new RuntimeException( $message ); } }
function configure( $kind, $changes ) {
	$config = array_replace( lunara_home_carousel_defaults( $kind ), array( 'adopted' => true ), $changes );
	$GLOBALS['options'][ lunara_home_carousel_option( $kind ) ] = 'hero' === $kind ? array( 'carousel' => $config ) : $config;
}
for ( $id = 1; $id <= 12; $id++ ) { $GLOBALS['posts'][ $id ] = new WP_Post( $id, $id % 2 ? 'journal' : 'review' ); }
$GLOBALS['posts'][13] = new WP_Post( 13, 'journal', 'draft' );
$GLOBALS['posts'][14] = new WP_Post( 14, 'page' );
$GLOBALS['posts'][15] = new WP_Post( 15, 'journal' ); $GLOBALS['posts'][15]->post_password = 'protected';
$GLOBALS['review_legacy_urls'][2] = '/uploads/art-82.jpg';
$GLOBALS['review_sources'][2]['hero_banner'] = array( 'mode' => 'custom', 'url' => '/uploads/art-84.jpg', 'attachment_id' => 84 );
$GLOBALS['review_sources'][4]['hero_banner'] = array( 'mode' => 'off', 'url' => '', 'attachment_id' => 0 ); $GLOBALS['review_sources'][4]['card'] = array( 'mode' => 'auto', 'url' => '/uploads/art-82.jpg', 'attachment_id' => 82 ); $GLOBALS['featured_images'][4] = 84;
$GLOBALS['review_sources'][6]['hero_banner'] = array( 'mode' => 'custom', 'url' => '', 'attachment_id' => 0 ); $GLOBALS['review_sources'][6]['card'] = array( 'mode' => 'auto', 'url' => '/uploads/art-82.jpg', 'attachment_id' => 82 ); $GLOBALS['featured_images'][6] = 86;
$GLOBALS['journal_urls'][3] = '/uploads/art-93.jpg';
check_delivery( ! lunara_home_carousel_is_adopted(), 'Defaults must leave existing presentation alone.' );
check_delivery( lunara_home_carousel_source_artwork( 2 ) === array( 'url' => '/uploads/art-84.jpg', 'attachment_id' => 84, 'source' => 'Review artwork' ), 'Hero carousel artwork must preserve the canonical hero banner ahead of distinct card artwork.' );
check_delivery( lunara_home_carousel_source_artwork( 2, 'full', 'card' ) === array( 'url' => '/uploads/art-82.jpg', 'attachment_id' => 82, 'source' => 'Review artwork' ), 'Card placements must continue to use canonical Review card artwork.' );
check_delivery( '' === lunara_home_carousel_source_artwork( 4 )['url'] && '' === lunara_home_carousel_source_artwork( 6 )['url'], 'Explicit Review Image Studio off and empty-custom choices must not fall through to featured artwork.' );
check_delivery( lunara_home_carousel_source_artwork( 3 ) === array( 'url' => '/uploads/art-93.jpg', 'attachment_id' => 93, 'source' => 'Journal artwork' ), 'Journal carousel artwork must use the canonical Journal card resolver.' );
configure( 'hero', array() ); configure( 'journal', array() );
check_delivery( array_column( lunara_get_home_cinematic_hero_slides(), 'post_id' ) === array( 12,11,10,9,8,7 ), 'Automatic hero must mix newest eligible reviews and Journal sources.' );
check_delivery( '/uploads/art-84.jpg' === lunara_home_carousel_source_slide( $GLOBALS['posts'][2], 'hero' )['image'], 'Public Hero delivery must use the same resolved hero artwork exposed to editor metadata.' );
check_delivery( array_column( lunara_home_carousel_slides( 'journal' ), 'post_id' ) === array( 11,9,7,5,3,1 ), 'Journal must exclude reviews, drafts, pages and protected posts.' );
check_delivery( $GLOBALS['queries'][0]['orderby'] === array( 'date' => 'DESC', 'ID' => 'DESC' ) && ! isset( $GLOBALS['queries'][0]['meta_key'] ), 'Auto query must sort by publication date without featured priority.' );
check_delivery( count( lunara_get_cinematic_hero_slides( 1 ) ) === 6, 'All legacy hero consumers must use the same adopted six-story deck.' );
$manual = array( array( 'post_id' => 3, 'headline' => 'Display <title>', 'excerpt' => 'Display excerpt', 'kicker' => 'Selected', 'cta' => 'Explore', 'image_id' => 90, 'focal_x' => 70, 'fit' => 'full' ), array( 'post_id' => 2 ), array( 'post_id' => 13 ), array( 'post_id' => 999 ) );
configure( 'hero', array( 'mode' => 'manual', 'slides' => $manual ) );
$slides = lunara_get_home_cinematic_hero_slides();
check_delivery( array_column( $slides, 'post_id' ) === array( 3,2 ), 'Manual must preserve exact order and skip unavailable items.' );
check_delivery( $slides[0]['title'] === 'Display' && $slides[0]['cta'] === 'Explore' && $slides[0]['attachment_id'] === 90 && $slides[0]['focal_x'] === 70, 'Display overrides and image framing must reach the renderer.' );
check_delivery( get_the_title( 3 ) === 'Story 3', 'Presentation must not modify source content.' );
configure( 'hero', array( 'mode' => 'manual', 'slides' => array( array( 'post_id' => 4, 'image_id' => 90 ) ) ) );
$off_override = lunara_home_carousel_slides( 'hero' );
check_delivery( 90 === $off_override[0]['attachment_id'] && '/tests/fixtures/home-carousel-art.svg' === $off_override[0]['image'], 'A carousel-specific manual image override must remain usable when inherited Review artwork is explicitly off.' );
configure( 'journal', array( 'mode' => 'manual', 'slides' => $manual ) );
check_delivery( array_column( lunara_home_carousel_slides( 'journal' ), 'post_id' ) === array( 3 ), 'Manual Journal must reject review sources too.' );
$html = lunara_render_homepage_journal_lane();
check_delivery( str_contains( $html, 'data-lunara-journal-carousel' ) && str_contains( $html, '<time datetime=' ) && str_contains( $html, 'is-full-frame' ), 'Live Journal entrypoint must emit cards, dates and framing.' );
configure( 'hero', array( 'mode' => 'manual', 'slides' => array() ) );
check_delivery( lunara_render_cinematic_hero_carousel() === '' && lunara_resolve_home_cinematic_hero_lcp_data() === array(), 'Empty manual hero must hide and suppress stale preload/fallback.' );
configure( 'hero', array( 'mode' => 'manual', 'slides' => array( array( 'post_id' => 1 ) ), 'autoplay' => 0 ) );
$html = lunara_render_cinematic_hero_carousel();
check_delivery( str_contains( $html, 'is-hero-static' ) && str_contains( $html, 'lunara-home-carousel-placeholder' ) && ! str_contains( $html, 'splide__toggle' ), 'One imageless item must remain a readable static hero.' );
check_delivery( lunara_resolve_home_cinematic_hero_lcp_data()['post_id'] === 1, 'Single-item preload resolver must select the actual manual item.' );
$GLOBALS['posts'][1]->post_status = 'draft'; lunara_home_carousel_reset_delivery();
check_delivery( lunara_get_home_cinematic_hero_slides() === array(), 'Delivery invalidation must remove unpublished stories within the request.' );
foreach ( array( 'save_post', 'deleted_post', 'updated_post_meta', 'updated_option' ) as $hook ) { check_delivery( in_array( 'lunara_home_carousel_reset_delivery', $GLOBALS['hooks'][ $hook ], true ), 'Missing delivery invalidation hook ' . $hook ); }

if ( in_array( '--fixture', $argv ?? array(), true ) ) {
	$GLOBALS['posts'][1]->post_status = 'publish';
	$GLOBALS['posts'][3]->post_title = 'A long headline about the current film scene that should stay readable on a small screen';
	$fixture = array(); foreach ( array( 3,5,7,9,11,1 ) as $id ) { $fixture[] = array( 'post_id' => $id, 'image_id' => 1 === $id ? 0 : 90 ); }
	configure( 'hero', array( 'mode' => 'manual', 'slides' => $fixture, 'heading' => 'Featured stories' ) );
	configure( 'journal', array( 'mode' => 'manual', 'slides' => $fixture, 'heading' => 'Fresh movement from the Lunara Journal' ) );
	echo '<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/style.css"><link rel="stylesheet" href="/assets/vendor/splide/splide-core.min.css"><link rel="stylesheet" href="/assets/css/lunara-home-modules.css"><link rel="stylesheet" href="/assets/css/lunara-cinematic-home.css"><link rel="stylesheet" href="/assets/css/lunara-home-carousels.css"><style>body{margin:0;background:#07111b;color:#fafbfc;font-family:Georgia,serif}main{max-width:1440px;margin:auto}.lunara-home-curated-journal{margin:60px 20px;padding:30px}.lunara-home-curated-hero{height:auto;min-height:420px}</style></head><body class="home"><main>';
	echo lunara_render_cinematic_hero_carousel() . lunara_render_homepage_journal_lane();
	echo '</main><script src="/assets/vendor/splide/splide.min.js"></script><script src="/assets/js/lunara-hero-carousel.js"></script><script src="/assets/js/lunara-home-carousels.js"></script></body></html>';
} else { echo "Homepage carousel delivery: {$checks} checks passed.\n"; }
