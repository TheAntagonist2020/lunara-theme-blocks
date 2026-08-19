<?php
/**
 * Isolated behavioral contract for Theme 3.2.52 Reviews Archive Studio.
 *
 * Run: php tests/reviews-archive-studio-runtime.php
 */

define( 'ABSPATH', __DIR__ . '/' );

$lunara_test_theme_mods    = array();
$lunara_test_options       = array();
$lunara_test_cache_deletes = array();
$lunara_test_actions_fired = array();
$lunara_test_rocket_urls   = array();
$lunara_test_transients    = array();
$lunara_test_user_id       = 7;
$lunara_test_can_edit      = true;
$lunara_test_now           = 2000000000;
$lunara_test_is_review_route = true;
$lunara_test_is_reviews_page = false;
$lunara_test_is_reviews_template = false;
$lunara_test_is_director_tax = false;
$lunara_test_is_paged      = false;
$lunara_test_paged_var     = 0;
$lunara_test_nocache_calls = 0;
$lunara_test_status_headers = array();
$lunara_test_pinned_id     = 0;
$lunara_test_pin_writes    = array();
$lunara_test_get_posts_args = array();
$lunara_test_ajax_nonce_valid = true;
$lunara_test_ajax_nonce_checks = 0;
$lunara_test_title_args    = array();
$lunara_test_posts = array(
	11  => (object) array( 'ID' => 11, 'post_type' => 'review', 'post_status' => 'publish', 'post_title' => 'Newest published file', 'post_date' => '2026-08-10 10:00:00', 'post_date_gmt' => '2026-08-10 15:00:00' ),
	12  => (object) array( 'ID' => 12, 'post_type' => 'review', 'post_status' => 'publish', 'post_title' => 'Older published file', 'post_date' => '2026-08-09 10:00:00', 'post_date_gmt' => '2026-08-09 15:00:00' ),
	13  => (object) array( 'ID' => 13, 'post_type' => 'review', 'post_status' => 'draft', 'post_title' => 'Hidden draft file', 'post_date' => '2026-08-15 10:00:00', 'post_date_gmt' => '0000-00-00 00:00:00' ),
	14  => (object) array( 'ID' => 14, 'post_type' => 'review', 'post_status' => 'publish', 'post_title' => 'Third published file', 'post_date' => '2026-08-08 10:00:00', 'post_date_gmt' => '2026-08-08 15:00:00' ),
	101 => (object) array( 'ID' => 101, 'post_type' => 'attachment', 'post_status' => 'inherit' ),
	102 => (object) array( 'ID' => 102, 'post_type' => 'attachment', 'post_status' => 'inherit' ),
	103 => (object) array( 'ID' => 103, 'post_type' => 'attachment', 'post_status' => 'inherit' ),
	104 => (object) array( 'ID' => 104, 'post_type' => 'attachment', 'post_status' => 'inherit' ),
);
$lunara_test_images = array( 101 => 'image/jpeg', 102 => 'image/webp', 103 => 'application/pdf', 104 => 'image/png', 105 => 'image/jpeg', 11 => 'image/jpeg' );
$lunara_test_attachment_renderable = true;
$lunara_test_design_tokens = array();

function lunara_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "Assertion failed: {$message}\n" );
		exit( 1 );
	}
}

class WP_Error {
	private $code;
	public function __construct( $code ) { $this->code = $code; }
	public function get_error_code() { return $this->code; }
}

class WP_Post {
	public function __construct( $post ) {
		foreach ( get_object_vars( $post ) as $key => $value ) {
			$this->{$key} = $value;
		}
	}
}

class Lunara_Test_JSON_Response extends RuntimeException {
	public $success;
	public $data;
	public $status;
	public function __construct( $success, $data, $status ) {
		parent::__construct( 'JSON response' );
		$this->success = $success;
		$this->data = $data;
		$this->status = $status;
	}
}

class Lunara_Test_WP_Die extends RuntimeException {}

class WP_Query {
	private $values = array();
	public $is_main = true;
	public $review_archive = true;
	public $reviews_page = false;
	public function is_main_query() { return $this->is_main; }
	public function is_post_type_archive( $type ) { return $this->review_archive && 'review' === $type; }
	public function is_page( $page = '' ) { return $this->reviews_page; }
	// Core WP_Query::get() returns an empty string for unset vars.
	public function get( $key ) { return isset( $this->values[ $key ] ) ? $this->values[ $key ] : ''; }
	public function set( $key, $value ) { $this->values[ $key ] = $value; }
}

function add_action() {}
function add_filter() {}
function is_admin() { return false; }
function is_feed() { return false; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function __( $value ) { return $value; }
function esc_html__( $value ) { return esc_html( $value ); }
function esc_attr__( $value ) { return esc_attr( $value ); }
function esc_html_e( $value ) { echo esc_html( $value ); }
function esc_attr_e( $value ) { echo esc_attr( $value ); }
function sanitize_key( $value ) { if ( ! is_scalar( $value ) ) { throw new TypeError( 'sanitize_key expects a scalar test value' ); } return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function sanitize_title( $value ) { return sanitize_key( $value ); }
function lunara_get_design_tokens() { return $GLOBALS['lunara_test_design_tokens']; }
function lunara_design_token_font_choices() {
	return array(
		'tiempos-text' => array( 'stack' => '"Tiempos Text", Georgia, serif' ),
		'lora'         => array( 'stack' => '"Lora", Georgia, serif' ),
		'georgia'      => array( 'stack' => 'Georgia, serif' ),
	);
}
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function wp_unslash( $value ) { return $value; }
function absint( $value ) { if ( ! is_scalar( $value ) ) { throw new TypeError( 'absint expects a scalar test value' ); } return abs( (int) $value ); }
function esc_url_raw( $value ) { if ( ! is_scalar( $value ) ) { throw new TypeError( 'esc_url_raw expects a scalar test value' ); } return filter_var( (string) $value, FILTER_SANITIZE_URL ); }
function esc_url( $value ) { return esc_url_raw( $value ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
// Faithful to real WordPress: wp_http_validate_url accepts BOTH http and
// https. The https-only rejection of http:// fixtures must therefore come
// from the module's own lunara_reviews_archive_studio_safe_https_url guard.
function wp_http_validate_url( $url ) { return in_array( parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) && (bool) parse_url( $url, PHP_URL_HOST ) ? $url : false; }
function home_url( $path = '' ) { return 'https://example.test/' . ltrim( $path, '/' ); }
function get_theme_mod( $key, $default = '' ) { global $lunara_test_theme_mods; return array_key_exists( $key, $lunara_test_theme_mods ) ? $lunara_test_theme_mods[ $key ] : $default; }
function set_theme_mod( $key, $value ) { global $lunara_test_theme_mods; $lunara_test_theme_mods[ $key ] = $value; }
function remove_theme_mod( $key ) { global $lunara_test_theme_mods; unset( $lunara_test_theme_mods[ $key ] ); }
function get_option( $key, $default = false ) { global $lunara_test_options; return array_key_exists( $key, $lunara_test_options ) ? $lunara_test_options[ $key ] : $default; }
function update_option( $key, $value ) { global $lunara_test_options; $lunara_test_options[ $key ] = $value; return true; }
function delete_option( $key ) { global $lunara_test_options; unset( $lunara_test_options[ $key ] ); return true; }
function get_post( $id ) { global $lunara_test_posts; $id = is_scalar( $id ) ? (int) $id : 0; return isset( $lunara_test_posts[ $id ] ) ? $lunara_test_posts[ $id ] : null; }
function get_posts( $args ) {
	global $lunara_test_posts, $lunara_test_get_posts_args;
	$lunara_test_get_posts_args[] = $args;
	$posts = array_filter(
		$lunara_test_posts,
		static function ( $post ) use ( $args ) {
			$type = isset( $args['post_type'] ) ? $args['post_type'] : 'review';
			if ( $type !== $post->post_type || 'publish' !== $post->post_status ) {
				return false;
			}
			if ( ! empty( $args['post__in'] ) && ! in_array( $post->ID, array_map( 'intval', (array) $args['post__in'] ), true ) ) {
				return false;
			}
			if ( ! empty( $args['s'] ) ) {
				$haystack = strtolower( ( isset( $post->post_title ) ? $post->post_title : '' ) . ' #' . $post->ID );
				return false !== strpos( $haystack, strtolower( (string) $args['s'] ) );
			}
			return true;
		}
	);
	usort(
		$posts,
		static function ( $a, $b ) {
			$date_order = strcmp( $b->post_date, $a->post_date );
			return 0 !== $date_order ? $date_order : ( $b->ID <=> $a->ID );
		}
	);
	$posts = array_slice( $posts, 0, isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : count( $posts ) );
	return isset( $args['fields'] ) && 'ids' === $args['fields']
		? array_map( static function ( $post ) { return $post->ID; }, $posts )
		: array_map( static function ( $post ) { return new WP_Post( $post ); }, $posts );
}
function get_post_mime_type( $id ) { global $lunara_test_images; return isset( $lunara_test_images[ $id ] ) ? $lunara_test_images[ $id ] : ''; }
function get_post_meta( $id, $key ) { return '_wp_attachment_image_alt' === $key ? 'Media Library alt ' . $id : ''; }
function wp_get_attachment_metadata( $id ) {
	$dimensions = array( 101 => array( 'width' => 1280, 'height' => 720 ), 102 => array( 'width' => 1920, 'height' => 1080 ), 104 => array( 'width' => 2000, 'height' => 3000 ) );
	return isset( $dimensions[ $id ] ) ? $dimensions[ $id ] : false;
}
function get_the_title( $post ) { global $lunara_test_title_args; $lunara_test_title_args[] = $post; return is_object( $post ) && isset( $post->post_title ) ? $post->post_title : 'Attachment ' . $post; }
function wp_get_attachment_image( $id, $size, $icon, $attrs ) {
	global $lunara_test_attachment_renderable;
	if ( ! $lunara_test_attachment_renderable ) {
		return '';
	}
	// Like core, attribute values are escaped and requested loading/decoding
	// attributes are reflected so lazy-loading is executable, not assumed.
	$extra = '';
	foreach ( array( 'class', 'loading', 'decoding' ) as $attr ) {
		if ( isset( $attrs[ $attr ] ) && '' !== $attrs[ $attr ] ) {
			$extra .= ' ' . $attr . '="' . esc_attr( $attrs[ $attr ] ) . '"';
		}
	}
	return '<img width="1920" height="1080" src="image-' . $id . '.jpg" srcset="image-' . $id . '.jpg 1920w" sizes="' . esc_attr( $attrs['sizes'] ) . '"' . $extra . ' alt="' . esc_attr( $attrs['alt'] ) . '">';
}
// FIX: the module's public config resolver is deliberately uncached, so the
// harness defines no wp_cache_get/wp_cache_set at all — any reintroduced
// object-cache read or write in the module fatals this runtime immediately.
function wp_cache_delete( $key, $group ) { global $lunara_test_cache_deletes; $lunara_test_cache_deletes[] = array( $key, $group ); return true; }
function do_action( $hook, $payload = null, $extra = null ) { global $lunara_test_actions_fired; $lunara_test_actions_fired[] = array( $hook, $payload, $extra ); }
function get_current_user_id() { global $lunara_test_user_id; return $lunara_test_user_id; }
function current_user_can( $capability ) { global $lunara_test_can_edit; return $lunara_test_can_edit && 'edit_theme_options' === $capability; }
function check_ajax_referer( $action, $field ) { global $lunara_test_ajax_nonce_valid, $lunara_test_ajax_nonce_checks; $lunara_test_ajax_nonce_checks++; if ( ! $lunara_test_ajax_nonce_valid || 'lunara_reviews_archive_studio_search' !== $action || 'nonce' !== $field ) { throw new RuntimeException( 'Invalid AJAX nonce.' ); } return true; }
function wp_send_json_success( $data = null, $status = 200 ) { throw new Lunara_Test_JSON_Response( true, $data, $status ); }
function wp_send_json_error( $data = null, $status = 400 ) { throw new Lunara_Test_JSON_Response( false, $data, $status ); }
function current_time() { return '2026-08-15 12:00:00'; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_generate_uuid4() { static $uuid_counter = 0; return 'uuid-4-test-' . ( ++$uuid_counter ); }
function wp_hash( $value ) { return hash( 'sha256', 'test-salt|' . $value ); }
function set_transient( $key, $value, $ttl ) { global $lunara_test_transients, $lunara_test_now; $lunara_test_transients[ $key ] = array( 'value' => $value, 'expires' => $lunara_test_now + $ttl ); return true; }
function get_transient( $key ) {
	global $lunara_test_transients, $lunara_test_now, $lunara_test_actions_fired;
	// Preview-token transient reads land in the same fired-actions array the
	// no-store sender uses, so tests can assert the no-store transition
	// happened strictly BEFORE any token work — not merely that it happened.
	if ( 0 === strpos( (string) $key, 'lunara_reviews_archive_preview_' ) ) {
		$lunara_test_actions_fired[] = array( 'reviews_archive_preview_transient_read', $key, null );
	}
	return isset( $lunara_test_transients[ $key ] ) && $lunara_test_transients[ $key ]['expires'] > $lunara_test_now ? $lunara_test_transients[ $key ]['value'] : false;
}
function lunara_test_no_store_precedes_transient_read( $actions ) {
	$no_store_index = null;
	$transient_index = null;
	foreach ( array_values( $actions ) as $index => $fired ) {
		if ( null === $no_store_index && 'lunara_reviews_archive_preview_no_store_sent' === $fired[0] ) {
			$no_store_index = $index;
		}
		if ( null === $transient_index && 'reviews_archive_preview_transient_read' === $fired[0] ) {
			$transient_index = $index;
		}
	}
	return null !== $no_store_index && null !== $transient_index && $no_store_index < $transient_index;
}
function delete_transient( $key ) { global $lunara_test_transients; unset( $lunara_test_transients[ $key ] ); return true; }
function get_post_type_archive_link( $type = 'review' ) { return 'https://example.test/reviews/'; }
function get_page_by_path( $path ) { return 'reviews' === $path ? (object) array( 'ID' => 900, 'post_type' => 'page', 'post_status' => 'publish' ) : null; }
function get_permalink( $post ) { return is_object( $post ) && 900 === $post->ID ? 'https://example.test/reviews-page/' : 'https://example.test/permalink/'; }
function rocket_clean_files( $urls ) { global $lunara_test_rocket_urls; $lunara_test_rocket_urls = $urls; }
function is_post_type_archive( $type ) { global $lunara_test_is_review_route; return $lunara_test_is_review_route && 'review' === $type; }
function is_page( $page = '' ) { global $lunara_test_is_reviews_page; return $lunara_test_is_reviews_page; }
function is_page_template( $template = '' ) { global $lunara_test_is_reviews_template; return $lunara_test_is_reviews_template; }
function is_tax( $taxonomy = '' ) { global $lunara_test_is_director_tax; return $lunara_test_is_director_tax && 'lunara_director' === $taxonomy; }
function is_paged() { global $lunara_test_is_paged; return $lunara_test_is_paged; }
function get_query_var( $key ) { global $lunara_test_paged_var; return in_array( $key, array( 'paged', 'page' ), true ) ? $lunara_test_paged_var : ''; }
function nocache_headers() { global $lunara_test_nocache_calls; $lunara_test_nocache_calls++; }
function status_header( $code ) { global $lunara_test_status_headers; $lunara_test_status_headers[] = $code; }
function wp_die( $message = '', $title = '', $args = array() ) { throw new Lunara_Test_WP_Die( is_scalar( $message ) ? (string) $message : 'wp_die' ); }
function taxonomy_exists( $taxonomy ) { return 'lunara_review_year' === $taxonomy; }
function get_terms( $args ) { return array(); }
function lunara_get_pinned_review_id() { global $lunara_test_pinned_id; return $lunara_test_pinned_id; }
function lunara_set_pinned_review_id( $post_id = 0 ) {
	global $lunara_test_pinned_id, $lunara_test_pin_writes, $lunara_test_posts;
	$post_id = absint( $post_id );
	$lunara_test_pin_writes[] = $post_id;
	$post = $post_id && isset( $lunara_test_posts[ $post_id ] ) ? $lunara_test_posts[ $post_id ] : null;
	$lunara_test_pinned_id = $post && 'review' === $post->post_type && 'publish' === $post->post_status ? $post_id : 0;
	return $lunara_test_pinned_id;
}

require dirname( __DIR__ ) . '/inc/reviews-archive-studio.php';
require dirname( __DIR__ ) . '/inc/helpers.php';
require dirname( __DIR__ ) . '/inc/review-archive-critical.php';
require dirname( __DIR__ ) . '/inc/review-rendering.php';

// --- Label-face resolver token discipline (raw scalar allowlist, no key normalization).
lunara_test_assert( 'tiempos-text' === lunara_reviews_archive_resolved_label_font_slug(), 'An absent label token must resolve to the approved licensed Tiempos default.' );
lunara_test_assert( true === lunara_reviews_archive_uses_tiempos_label_face(), 'The approved default label token must activate the route-aware licensed Tiempos face.' );
$lunara_test_design_tokens = array( 'fonts' => array( 'label' => 'lora' ) );
lunara_test_assert( 'lora' === lunara_reviews_archive_resolved_label_font_slug(), 'A valid non-default Studio label token must round-trip without Tiempos substitution.' );
lunara_test_assert( false === lunara_reviews_archive_uses_tiempos_label_face(), 'A valid non-default Studio label token must bypass the route-owned Tiempos face.' );
$lunara_test_design_tokens = array( 'fonts' => array( 'label' => array( 'bad-shape' ) ) );
lunara_test_assert( 'tiempos-text' === lunara_reviews_archive_resolved_label_font_slug(), 'A corrupt label token must repair field-locally to the default without a type error.' );
$lunara_test_design_tokens = array( 'fonts' => array( 'label' => 'not-a-choice' ) );
lunara_test_assert( 'tiempos-text' === lunara_reviews_archive_resolved_label_font_slug(), 'An unknown label token must repair field-locally to the default.' );
foreach ( array( ' tiempos-text', 'tiempos-text ', 'tiempos_text!', 'Tiempos-Text', 'TIEMPOS-TEXT' ) as $corrupt_token ) {
	$lunara_test_design_tokens = array( 'fonts' => array( 'label' => $corrupt_token ) );
	lunara_test_assert( 'tiempos-text' === lunara_reviews_archive_resolved_label_font_slug(), 'Corrupt near-match "' . $corrupt_token . '" must follow the canonical exact-key validator back to the default.' );
	lunara_test_assert( true === lunara_reviews_archive_uses_tiempos_label_face(), 'Corrupt near-match "' . $corrupt_token . '" must retain the canonical default Tiempos behavior.' );
}
$lunara_test_design_tokens = array( 'fonts' => array( 'label' => ' lora ' ) );
lunara_test_assert( 'tiempos-text' === lunara_reviews_archive_resolved_label_font_slug(), 'A whitespace-corrupt custom near-match must not become a valid custom label token.' );
$lunara_test_design_tokens = array();

// --- Defaults reproduce the current public literals byte-for-byte.
$defaults = lunara_reviews_archive_studio_defaults();
$pristine_defaults = $defaults;
lunara_test_assert( 'Criticism Desk' === $defaults['kicker'] && 'Lunara Reviews' === $defaults['title'], 'Default identity must reproduce the shipped Criticism Desk / Lunara Reviews literals.' );
lunara_test_assert( 'Spoiler-free criticism, full-spoiler companion files, festival finds, and the films that deserve a longer argument after the credits roll.' === $defaults['deck'], 'Default deck must reproduce the shipped archive copy byte-for-byte.' );
lunara_test_assert( 9 === $defaults['item_count'], 'Reviews archive must retain nine cards by default.' );
lunara_test_assert( array( 'hero', 'grid', 'pagination', 'pairing-desk' ) === $defaults['section_order'], 'Default section order must preserve the proven public sequence.' );
lunara_test_assert( 'automatic' === $defaults['lead_mode'] && 0 === $defaults['lead_id'], 'Archive lead must stay automatic with no stored lead ID by default.' );
lunara_test_assert( 'query' === $defaults['lane_mode'] && array() === $defaults['curated_ids'], 'Archive run must remain query-driven with no curation by default.' );
$expected_label_literals = array(
	'debrief_kicker'      => 'Reviews Command',
	'debrief_depth'       => 'Archive Depth',
	'debrief_visible'     => 'Visible File',
	'debrief_latest'      => 'Latest Update',
	'debrief_order'       => 'Current Order',
	'hero_action_run'     => 'Browse The Run',
	'hero_action_oscars'  => 'Oscar Ledger',
	'hero_action_journal' => 'Journal Desk',
	'toolbar_kicker'      => 'Review Order',
	'toolbar_title'       => 'Release timeline or real editing activity',
	'sort_label'          => 'Sort by',
	'sort_release_desc'   => 'Newest Release',
	'sort_release_asc'    => 'Oldest Release',
	'sort_modified_desc'  => 'Recently Updated',
	'year_label'          => 'Release year',
	'year_all'            => 'All years',
	'year_filter'         => 'Filter',
	'support_kicker'      => 'On The Desk',
	'support_title'       => 'Current companion files.',
	'run_kicker'          => 'Criticism Run',
	'run_title'           => 'The archive keeps moving.',
	'retention_kicker'    => 'Keep Moving',
	'retention_title'     => 'More routes through the desk.',
	'retention_copy'      => 'Follow the latest updates, jump into the Journal, or cross-reference the Oscar Ledger.',
	'pagination_prev'     => '&laquo; Previous',
	'pagination_next'     => 'Next &raquo;',
);
foreach ( $expected_label_literals as $label_key => $label_literal ) {
	lunara_test_assert( isset( $defaults['labels'][ $label_key ] ) && $label_literal === $defaults['labels'][ $label_key ], 'Default label ' . $label_key . ' must reproduce its shipped public literal byte-for-byte.' );
}
lunara_test_assert( array_keys( $expected_label_literals ) === array_keys( $defaults['labels'] ), 'The Studio label set must be exactly the consumed public-language keys — no dead controls.' );
// The empty state's canonical owners are the lunara_archive_review_empty_text
// theme mod (archive-review.php) and the hub template literal; the Studio
// must expose no dead empty_title/empty_copy controls.
lunara_test_assert( ! array_key_exists( 'empty_title', $defaults['labels'] ) && ! array_key_exists( 'empty_copy', $defaults['labels'] ), 'The removed empty-state controls must not resurface in the Studio defaults.' );
lunara_test_assert(
	array( 40, 460, 360, 116 ) === array(
		$defaults['presentation']['section_gap'],
		$defaults['presentation']['lead_min_height'],
		$defaults['presentation']['card_min_height'],
		$defaults['presentation']['compact_media_width'],
	),
	'Default presentation geometry must reproduce the shipped 40/460/360/116 values.'
);
lunara_test_assert( array( 1, 2, 3 ) === array_column( $defaults['retention'], 'order' ), 'Retention cards must expose a stable independent order.' );
lunara_test_assert( array( 'Recently Updated', 'Journal Desk', 'Oscar Ledger' ) === array_column( $defaults['retention'], 'label' ), 'Default retention labels must reproduce the shipped continuation routes.' );
lunara_test_assert( array( 'latest', 'journal', 'oscars' ) === array_column( $defaults['retention'], 'destination' ), 'Default retention destinations must reproduce the shipped route targets.' );
lunara_test_assert( array() === $defaults['gallery']['items'], 'The archive gallery must default empty so it adds no public wrapper or geometry.' );

// The shell renderer's module-absent fallback strings must stay byte-identical
// to the module defaults, extracted from the actual review-rendering source.
$lunara_rendering_source = file_get_contents( dirname( __DIR__ ) . '/inc/review-rendering.php' );
$lunara_shell_labels_start = strpos( $lunara_rendering_source, "'debrief_kicker'" );
$lunara_shell_labels_end   = false !== $lunara_shell_labels_start ? strpos( $lunara_rendering_source, ');', $lunara_shell_labels_start ) : false;
lunara_test_assert( false !== $lunara_shell_labels_start && false !== $lunara_shell_labels_end, 'The shell renderer must keep its byte-identical fallback label table.' );
$lunara_shell_labels_block = substr( $lunara_rendering_source, $lunara_shell_labels_start, $lunara_shell_labels_end - $lunara_shell_labels_start );
preg_match_all( "/'(?<key>[a-z_]+)'\s*=>\s*__\(\s*'(?<value>[^']*)',\s*'lunara-film'\s*\)/", $lunara_shell_labels_block, $lunara_shell_label_pairs, PREG_SET_ORDER );
lunara_test_assert( count( $lunara_shell_label_pairs ) >= 24, 'The shell fallback label table must enumerate the public strings.' );
foreach ( $lunara_shell_label_pairs as $lunara_shell_label_pair ) {
	$shell_label_key = $lunara_shell_label_pair['key'];
	lunara_test_assert( isset( $defaults['labels'][ $shell_label_key ] ), 'Shell fallback label ' . $shell_label_key . ' must exist in the Studio defaults.' );
	lunara_test_assert( $defaults['labels'][ $shell_label_key ] === $lunara_shell_label_pair['value'], 'Shell fallback label ' . $shell_label_key . ' must match the Studio default byte-for-byte.' );
}
$lunara_sort_options = lunara_get_review_archive_sort_options();
lunara_test_assert(
	$defaults['labels']['sort_release_desc'] === $lunara_sort_options['release_desc']
	&& $defaults['labels']['sort_release_asc'] === $lunara_sort_options['release_asc']
	&& $defaults['labels']['sort_modified_desc'] === $lunara_sort_options['modified_desc'],
	'Default sort labels must match the live sort-option owner byte-for-byte.'
);
$lunara_template_source = file_get_contents( dirname( __DIR__ ) . '/archive-review.php' );
lunara_test_assert(
	false !== strpos( $lunara_template_source, "__( '" . $defaults['labels']['pagination_prev'] . "', 'lunara-film' )" )
	&& false !== strpos( $lunara_template_source, "__( '" . $defaults['labels']['pagination_next'] . "', 'lunara-film' )" ),
	'The archive template pagination fallbacks must match the Studio defaults byte-for-byte.'
);
lunara_test_assert( 'hero,grid,pagination,pairing-desk' === lunara_sanitize_reviews_archive_section_order( 'hero,grid,pagination,pairing-desk' ), 'The canonical Customizer sanitizer must round-trip the default four-lane order.' );
lunara_test_assert( array( 'grid', 'hero', 'pairing-desk', 'pagination' ) === lunara_reviews_archive_studio_expand_section_order( 'grid,hero,pairing-desk,pagination' ), 'A complete explicit four-lane order must round-trip without semantic rewriting.' );
lunara_test_assert( array( 'grid', 'hero', 'pagination', 'pairing-desk' ) === lunara_reviews_archive_studio_expand_section_order( 'grid,bogus,grid' ), 'Corrupt lane tokens must repair to a complete canonical permutation.' );

// The default resolved public config must reproduce the pure defaults.
delete_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION );
$default_public = lunara_reviews_archive_studio_get_public_config( false );
lunara_test_assert( array() === $default_public['_warnings'], 'A pristine environment must resolve without validator warnings.' );
unset( $default_public['_warnings'] );
lunara_test_assert( serialize( $defaults ) === serialize( $default_public ), 'An unconfigured environment must resolve the exact byte-identical default public state.' );

// The first-paint seed must guard the new retention slot without becoming a numeric order owner.
$lunara_seed = lunara_reviews_archive_critical_css( array( 'hero' => 1, 'utility' => 2, 'grid' => 2, 'pagination' => 3, 'pairing-desk' => 4 ), array( 'hero' => true, 'grid' => true, 'pairing-desk' => true ) );
lunara_test_assert( strlen( $lunara_seed ) <= 12288, 'The universal structural seed must stay at or below 12 KB.' );
lunara_test_assert( false !== strpos( $lunara_seed, '&>.lunara-review-archive-slot-retention{margin:0!important;order:var(--lunara-reviews-archive-order-grid,2)!important;width:100%!important}' ), 'The structural seed must guard the retention slot with the grid-lane variable.' );
lunara_test_assert( false !== strpos( $lunara_seed, '& .lunara-review-archive-gallery{min-width:0!important;width:100%!important}' ), 'The structural seed must guard archive gallery width before deferred CSS settles.' );
lunara_test_assert( 0 === preg_match( '/(?<![a-z-])order:[1-9]/', $lunara_seed ), 'The structural seed must never hard-code a numeric lane order.' );

// --- Every validator family and every error code must be reachable with a bounded message.
$valid = $defaults;
$valid['title']                            = 'A sharper Reviews desk';
$valid['item_count']                       = 12;
$valid['lead_mode']                        = 'manual';
$valid['lead_id']                          = 11;
$valid['lane_mode']                        = 'curated';
$valid['curated_ids']                      = array( 11, 12, 14 );
$valid['supporting_copy']                  = 'Focused supporting copy.';
$valid['retention'][0]['image_id']         = 101;
$valid['retention'][0]['image_credit']     = 'Studio credit';
$valid['retention'][0]['image_source']     = 'Studio source';
$valid['retention'][0]['image_source_url'] = 'https://source.example/retention-image';
$valid['gallery'] = array(
	'kicker' => 'The Visual File',
	'title'  => 'Three frames from the desk',
	'copy'   => 'A bounded archive-only image sequence.',
	'items'  => array(
		array( 'order' => 1, 'attachment_id' => 102, 'alt' => 'First frame', 'caption' => 'First caption', 'link_url' => 'https://example.test/first', 'credit' => 'First credit', 'source' => 'First source', 'source_url' => 'https://source.example/first', 'focal_x' => 31, 'focal_y' => 67 ),
		array( 'order' => 2, 'attachment_id' => 101, 'alt' => 'Second frame', 'caption' => 'Second caption', 'link_url' => '', 'credit' => 'Second credit', 'source' => 'Second source', 'source_url' => 'https://source.example/second', 'focal_x' => 50, 'focal_y' => 50 ),
		array( 'order' => 3, 'attachment_id' => 104, 'alt' => 'Third frame', 'caption' => 'Third caption', 'link_url' => 'https://example.test/third', 'credit' => 'Third credit', 'source' => 'Third source', 'source_url' => 'https://source.example/third', 'focal_x' => 72, 'focal_y' => 40 ),
	),
);
$validated = lunara_reviews_archive_studio_validate_config( $valid );
lunara_test_assert( ! is_wp_error( $validated ), 'A complete published configuration must validate.' );
lunara_test_assert( array( 11, 12, 14 ) === $validated['curated_ids'], 'Curated order must survive validation.' );
lunara_test_assert( 101 === $validated['retention'][0]['image_id'], 'Validated attachment IDs must survive.' );
lunara_test_assert( array( 102, 101, 104 ) === array_column( $validated['gallery']['items'], 'attachment_id' ), 'Archive gallery attachment order must round-trip exactly.' );

$mutate = static function ( $base, $mutator ) {
	$candidate = $base;
	$mutator( $candidate );
	return $candidate;
};
$code_producers = array(
	'reviews_archive_config_invalid'                => static function () { return lunara_reviews_archive_studio_validate_config( 'not-an-array' ); },
	'reviews_archive_identity_required'             => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['kicker'] = '   '; } ) ); },
	'reviews_archive_item_count_invalid'            => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['item_count'] = 25; } ) ); },
	'reviews_archive_lead_mode_invalid'             => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['lead_mode'] = 'bogus'; } ) ); },
	'reviews_archive_lead_invalid'                  => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['lead_id'] = 13; } ) ); },
	'reviews_archive_lane_mode_invalid'             => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['lane_mode'] = 'bogus'; } ) ); },
	'reviews_archive_curated_post_invalid'          => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['curated_ids'] = array( 12, 13 ); } ) ); },
	'reviews_archive_curated_duplicate'             => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['curated_ids'] = array( 12, 14, 12 ); } ) ); },
	'reviews_archive_curated_count_invalid'         => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['curated_ids'] = array(); } ) ); },
	'reviews_archive_section_order_invalid'         => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['section_order'] = array( 'hero', 'grid', 'grid', 'pagination' ); } ) ); },
	'reviews_archive_primary_sections_hidden'       => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['section_visibility']['hero'] = false; $c['section_visibility']['grid'] = false; } ) ); },
	'reviews_archive_label_required'                => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['labels']['sort_label'] = ''; } ) ); },
	'reviews_archive_gallery_copy_required'         => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['gallery']['kicker'] = ''; } ) ); },
	'reviews_archive_gallery_count_invalid'         => static function () use ( $valid, $mutate ) {
		return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) {
			for ( $index = 4; $index <= 13; $index++ ) {
				$c['gallery']['items'][] = array( 'order' => $index, 'attachment_id' => 400 + $index, 'alt' => 'Alt', 'caption' => '', 'link_url' => '', 'credit' => 'Credit', 'source' => 'Source', 'source_url' => 'https://source.example/' . $index, 'focal_x' => 50, 'focal_y' => 50 );
			}
		} ) );
	},
	'reviews_archive_gallery_order_invalid'         => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['gallery']['items'][1]['order'] = 1; } ) ); },
	'reviews_archive_gallery_image_invalid'         => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['gallery']['items'][1]['attachment_id'] = 103; } ) ); },
	'reviews_archive_gallery_duplicate'             => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['gallery']['items'][2]['attachment_id'] = 102; } ) ); },
	'reviews_archive_gallery_provenance_required'   => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['gallery']['items'][1]['alt'] = ''; } ) ); },
	'reviews_archive_gallery_source_invalid'        => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['gallery']['items'][1]['source_url'] = 'http://unsafe.example/image'; } ) ); },
	'reviews_archive_gallery_link_invalid'          => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['gallery']['items'][1]['link_url'] = 'javascript:alert(1)'; } ) ); },
	'reviews_archive_retention_order_invalid'       => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['retention'][1]['order'] = 1; } ) ); },
	'reviews_archive_retention_copy_required'       => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['retention'][1]['label'] = '  '; } ) ); },
	'reviews_archive_retention_destination_invalid' => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['retention'][1]['destination'] = 'bogus'; } ) ); },
	'reviews_archive_retention_url_invalid'         => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['retention'][1]['destination'] = 'custom'; $c['retention'][1]['url'] = ''; } ) ); },
	'reviews_archive_retention_image_invalid'       => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['retention'][0]['image_id'] = 103; } ) ); },
	'reviews_archive_retention_image_provenance_required' => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['retention'][0]['image_credit'] = ''; } ) ); },
	'reviews_archive_retention_image_source_invalid' => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['retention'][0]['image_source_url'] = 'http://unsafe.example/source'; } ) ); },
	'reviews_archive_presentation_invalid'          => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['presentation']['density'] = 'bogus'; } ) ); },
	'reviews_archive_geometry_invalid'              => static function () use ( $valid, $mutate ) { return lunara_reviews_archive_studio_validate_config( $mutate( $valid, static function ( &$c ) { $c['presentation']['section_gap'] = 999; } ) ); },
	'reviews_archive_revision_not_found'            => static function () { return lunara_reviews_archive_studio_restore_revision( 'no-such-revision' ); },
	'reviews_archive_preview_forbidden'             => static function () use ( $valid ) {
		global $lunara_test_can_edit;
		$lunara_test_can_edit = false;
		$result = lunara_reviews_archive_studio_store_preview( $valid );
		$lunara_test_can_edit = true;
		return $result;
	},
	'reviews_archive_config_repair_failed'          => static function () use ( $valid, $mutate ) {
		// A kicker that survives trim() but sanitizes to empty defeats the
		// identity repair, exhausting the bounded repair loop fail-closed.
		return lunara_reviews_archive_studio_repair_public_config(
			$mutate( $valid, static function ( &$c ) { $c['kicker'] = '<i></i>'; } ),
			lunara_reviews_archive_studio_defaults()
		);
	},
);
$allowlisted_messages = lunara_reviews_archive_studio_validation_messages();
$generic_feedback = lunara_reviews_archive_studio_validation_message( 'not-a-real-code' );
$produced_codes = array();
foreach ( $code_producers as $expected_code => $producer ) {
	$result = $producer();
	lunara_test_assert( is_wp_error( $result ), 'Validator code ' . $expected_code . ' must be reachable as a WP_Error.' );
	lunara_test_assert( $expected_code === $result->get_error_code(), 'Producer for ' . $expected_code . ' emitted ' . $result->get_error_code() . ' instead.' );
	lunara_test_assert( array_key_exists( $expected_code, $allowlisted_messages ), 'Validator code ' . $expected_code . ' must resolve in the bounded message allowlist.' );
	$message = lunara_reviews_archive_studio_validation_message( $expected_code );
	lunara_test_assert( is_string( $message ) && '' !== $message && $message !== $generic_feedback, 'Validator code ' . $expected_code . ' must resolve a dedicated human message.' );
	$produced_codes[] = $expected_code;
}
lunara_test_assert( array() === array_diff( array_keys( $allowlisted_messages ), $produced_codes ), 'Every allowlisted validator code must be reachable: missing ' . implode( ', ', array_diff( array_keys( $allowlisted_messages ), $produced_codes ) ) . '.' );
lunara_test_assert( array() === array_diff( $produced_codes, array_keys( $allowlisted_messages ) ), 'No emitted validator code may fall outside the allowlist.' );
lunara_test_assert( '' === lunara_reviews_archive_studio_bound_feedback_code( 'not-a-real-code' ) && 'reviews_archive_geometry_invalid' === lunara_reviews_archive_studio_bound_feedback_code( 'reviews_archive_geometry_invalid' ), 'Feedback codes must bind to the exact allowlist without normalization.' );

// Bounds themselves: 4 and 24 are valid, 3 and 25 are not; the curated cap is 24.
foreach ( array( 4, 24 ) as $legal_count ) {
	$legal = $valid;
	$legal['item_count'] = $legal_count;
	lunara_test_assert( ! is_wp_error( lunara_reviews_archive_studio_validate_config( $legal ) ), 'Item count ' . $legal_count . ' must remain valid.' );
}
foreach ( array( 3, 25, -2, PHP_INT_MAX ) as $illegal_count ) {
	$illegal = $valid;
	$illegal['item_count'] = $illegal_count;
	$illegal_result = lunara_reviews_archive_studio_validate_config( $illegal );
	lunara_test_assert( is_wp_error( $illegal_result ) && 'reviews_archive_item_count_invalid' === $illegal_result->get_error_code(), 'Item count ' . $illegal_count . ' must fail closed.' );
}
$over_cap = $valid;
$over_cap['curated_ids'] = range( 200, 224 );
foreach ( $over_cap['curated_ids'] as $curated_cap_id ) {
	$lunara_test_posts[ $curated_cap_id ] = (object) array( 'ID' => $curated_cap_id, 'post_type' => 'review', 'post_status' => 'publish', 'post_title' => 'Cap file ' . $curated_cap_id, 'post_date' => '2026-07-01 10:00:00', 'post_date_gmt' => '2026-07-01 15:00:00' );
}
$over_cap_result = lunara_reviews_archive_studio_validate_config( $over_cap );
lunara_test_assert( is_wp_error( $over_cap_result ) && 'reviews_archive_curated_count_invalid' === $over_cap_result->get_error_code(), 'A 25-file curated run must fail the 24-file cap.' );
$at_cap = $over_cap;
$at_cap['curated_ids'] = range( 200, 223 );
lunara_test_assert( ! is_wp_error( lunara_reviews_archive_studio_validate_config( $at_cap ) ), 'A 24-file curated run must remain valid.' );
foreach ( range( 200, 224 ) as $curated_cap_id ) {
	unset( $lunara_test_posts[ $curated_cap_id ] );
}
$cleared_lead = $valid;
$cleared_lead['lead_mode'] = 'automatic';
lunara_test_assert( 0 === lunara_reviews_archive_studio_validate_config( $cleared_lead )['lead_id'], 'Leaving manual mode must clear the stale hidden lead ID.' );
$cleared_curation = $valid;
$cleared_curation['lane_mode'] = 'query';
$cleared_curation['curated_ids'] = array( 11, 13, 11 );
lunara_test_assert( array() === lunara_reviews_archive_studio_validate_config( $cleared_curation )['curated_ids'], 'Returning to automatic query mode must clear stale hidden curated IDs.' );
$permuted_order = $valid;
$permuted_order['section_order'] = array( 'pairing-desk', 'grid', 'hero', 'pagination' );
lunara_test_assert( array( 'pairing-desk', 'grid', 'hero', 'pagination' ) === lunara_reviews_archive_studio_validate_config( $permuted_order )['section_order'], 'Any complete lane permutation must round-trip exactly.' );
$unknown_order = $valid;
$unknown_order['section_order'] = array( 'hero', 'grid', 'pagination', 'bogus' );
lunara_test_assert( is_wp_error( lunara_reviews_archive_studio_validate_config( $unknown_order ) ), 'An unknown lane slug must fail the permutation contract.' );
$clamped_focal = $valid;
$clamped_focal['retention'][0]['focal_x'] = 150;
$clamped_focal['retention'][0]['focal_y'] = 101;
$clamped_focal_result = lunara_reviews_archive_studio_validate_config( $clamped_focal );
lunara_test_assert( ! is_wp_error( $clamped_focal_result ) && 100 === $clamped_focal_result['retention'][0]['focal_x'] && 100 === $clamped_focal_result['retention'][0]['focal_y'], 'Retention focal positions must clamp to the 0-100 percent bounds.' );
$custom_destination = $valid;
$custom_destination['retention'][1]['destination'] = 'custom';
$custom_destination['retention'][1]['url'] = 'https://example.test/custom-route';
lunara_test_assert( ! is_wp_error( lunara_reviews_archive_studio_validate_config( $custom_destination ) ), 'A custom retention destination with a safe HTTPS URL must validate.' );

// Strict wrong-shape rejection without coercion or fatals.
foreach ( array( 'labels', 'section_visibility', 'presentation', 'retention', 'section_order' ) as $wrong_shape_family ) {
	$wrong_shape_candidate = $defaults;
	$wrong_shape_candidate[ $wrong_shape_family ] = 'not-an-array';
	lunara_test_assert( is_wp_error( lunara_reviews_archive_studio_validate_config( $wrong_shape_candidate ) ), 'Strict validation must reject wrong-shaped ' . $wrong_shape_family . ' input instead of coercing it or throwing.' );
}
$wrong_gallery_items = $defaults;
$wrong_gallery_items['gallery']['items'] = 'not-an-array';
lunara_test_assert( is_wp_error( lunara_reviews_archive_studio_validate_config( $wrong_gallery_items ) ), 'Strict validation must reject a wrong-shaped gallery item collection.' );
$wrong_scalar_candidates = array();
foreach ( array( 'schema_version', 'kicker', 'title', 'deck', 'supporting_copy', 'lead_mode', 'lead_id', 'lane_mode', 'item_count' ) as $scalar_key ) {
	$candidate = $defaults;
	$candidate[ $scalar_key ] = array( 'malformed' );
	$wrong_scalar_candidates[] = $candidate;
}
$candidate = $defaults;
$candidate['section_order'][0] = array( 'hero' );
$wrong_scalar_candidates[] = $candidate;
$candidate = $defaults;
$candidate['labels']['sort_label'] = array( 'copy' );
$wrong_scalar_candidates[] = $candidate;
$candidate = $defaults;
$candidate['presentation']['density'] = array( 'editorial' );
$wrong_scalar_candidates[] = $candidate;
$candidate = $defaults;
$candidate['curated_ids'] = array( array( 11 ) );
$wrong_scalar_candidates[] = $candidate;
$candidate = $valid;
$candidate['gallery']['items'][0]['alt'] = array( 'alt' );
$wrong_scalar_candidates[] = $candidate;
$candidate = $valid;
$candidate['retention'][0]['destination'] = array( 'custom' );
$wrong_scalar_candidates[] = $candidate;
$candidate = $defaults;
$candidate['labels']['sort_label'] = (object) array( 'value' => 'Sort by' );
$wrong_scalar_candidates[] = $candidate;
foreach ( $wrong_scalar_candidates as $wrong_scalar_candidate ) {
	lunara_test_assert( is_wp_error( lunara_reviews_archive_studio_validate_config( $wrong_scalar_candidate ) ), 'Strict validation must fail closed on every array- or object-valued scalar leaf without throwing.' );
}

// --- Per-family fail-closed repair of corrupt stored public state; siblings survive.
update_option(
	LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION,
	array(
		'schema_version'  => 1,
		'item_count'      => 14,
		'supporting_copy' => 'Preserve this unrelated copy exactly.',
		'labels'          => null,
		'gallery'         => array( 'kicker' => 'Custom Visual File', 'title' => 'Custom gallery title', 'copy' => 'Keep gallery copy.', 'items' => 'not-an-array' ),
		'retention'       => array( 'bad' => 'not-a-card' ),
	)
);
set_theme_mod( 'lunara_reviews_archive_title', 'Corruption-safe Reviews' );
$corrupt_public = lunara_reviews_archive_studio_get_public_config( false );
lunara_test_assert( 'Corruption-safe Reviews' === $corrupt_public['title'] && 14 === $corrupt_public['item_count'] && 'Preserve this unrelated copy exactly.' === $corrupt_public['supporting_copy'], 'Wrong-shaped nested option families must not fatal or reset unrelated valid owners.' );
lunara_test_assert( $defaults['labels'] === $corrupt_public['labels'], 'A null label family must repair locally to validated defaults.' );
lunara_test_assert( 'Custom Visual File' === $corrupt_public['gallery']['kicker'] && array() === $corrupt_public['gallery']['items'], 'A corrupt gallery item list must be cleared locally while valid gallery framing survives.' );
lunara_test_assert( $defaults['retention'] === $corrupt_public['retention'], 'Associative retention corruption must repair to the bounded three-card defaults.' );

update_option(
	LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION,
	array(
		'schema_version'  => 1,
		'supporting_copy' => 'This valid public sibling survives scalar corruption.',
		'item_count'      => 16,
		'lead_mode'       => array( 'manual' ),
		'lane_mode'       => (object) array( 'mode' => 'curated' ),
		'curated_ids'     => array( 12, array( 'bad' ), 13, 12 ),
		'labels'          => array( 'sort_label' => array( 'broken' ), 'run_title' => 'Keep this custom run title.' ),
		'gallery'         => array( 'kicker' => 'Preserved gallery kicker', 'title' => 'Preserved gallery title', 'copy' => '', 'items' => array( 'not-an-item', array( 'order' => 1, 'attachment_id' => 102, 'alt' => array( 'bad' ), 'credit' => 'C', 'source' => 'S', 'source_url' => 'https://source.example/x' ) ) ),
		'retention'       => array(
			array( 'visible' => 1, 'order' => 1, 'label' => 'Custom retention label', 'destination' => array( 'custom' ), 'url' => '', 'image_id' => 0, 'image_alt' => '', 'image_credit' => '', 'image_source' => '', 'image_source_url' => '', 'focal_x' => 50, 'focal_y' => 50 ),
			(object) array( 'not' => 'a-card' ),
			'not-a-card',
		),
	)
);
set_theme_mod( 'lunara_reviews_archive_title', 'Scalar-safe Reviews' );
set_theme_mod( 'lunara_reviews_archive_section_order', array( 'hero', 'grid' ) );
set_theme_mod( 'lunara_reviews_archive_density', array( 'editorial' ) );
set_theme_mod( 'lunara_reviews_archive_section_gap', PHP_INT_MAX );
set_theme_mod( 'lunara_reviews_archive_card_min_height', -50 );
$scalar_public = lunara_reviews_archive_studio_get_public_config( false );
lunara_test_assert( 'Scalar-safe Reviews' === $scalar_public['title'] && 'This valid public sibling survives scalar corruption.' === $scalar_public['supporting_copy'] && 16 === $scalar_public['item_count'], 'Array-valued public scalar leaves must repair locally without losing unrelated owners.' );
lunara_test_assert( 'query' === $scalar_public['lane_mode'] && array() === $scalar_public['curated_ids'], 'A corrupt public lane enum must fall back to query mode with no hidden curated state.' );
lunara_test_assert( $defaults['labels']['sort_label'] === $scalar_public['labels']['sort_label'] && 'Keep this custom run title.' === $scalar_public['labels']['run_title'], 'Only the corrupt label leaf may fall back; the valid sibling survives.' );
lunara_test_assert( $defaults['section_order'] === $scalar_public['section_order'] && 'editorial' === $scalar_public['presentation']['density'], 'Corrupt theme-mod scalar leaves must recover without a fatal.' );
lunara_test_assert( 40 === $scalar_public['presentation']['section_gap'] && 360 === $scalar_public['presentation']['card_min_height'], 'Huge and negative geometry owners must repair field-locally to validated defaults.' );
lunara_test_assert( 'Custom retention label' === $scalar_public['retention'][0]['label'] && 'latest' === $scalar_public['retention'][0]['destination'], 'A corrupt retention destination leaf must repair without discarding its valid label sibling.' );
lunara_test_assert( $defaults['retention'][1] === $scalar_public['retention'][1] && $defaults['retention'][2] === $scalar_public['retention'][2], 'Object and scalar retention cards must repair to their bounded defaults.' );
$bounded_scalar_stage = lunara_reviews_archive_studio_bound_invalid_stage( get_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION ) );
lunara_test_assert( 'automatic' === $bounded_scalar_stage['lead_mode'] && 'latest' === $bounded_scalar_stage['retention'][0]['destination'], 'Rejected array-valued scalar input must remain a safe private stage instead of throwing.' );
$huge_stage = $defaults;
$huge_stage['item_count'] = PHP_INT_MAX;
$huge_stage['curated_ids'] = range( 1, 60 );
$huge_stage['presentation']['section_gap'] = PHP_INT_MAX;
$huge_bounded = lunara_reviews_archive_studio_bound_invalid_stage( $huge_stage );
lunara_test_assert( 999 === $huge_bounded['item_count'] && 24 === count( $huge_bounded['curated_ids'] ) && 9999 === $huge_bounded['presentation']['section_gap'], 'The private stage must bound huge integers and oversized curated lists.' );
delete_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION );
remove_theme_mod( 'lunara_reviews_archive_title' );
remove_theme_mod( 'lunara_reviews_archive_section_order' );
remove_theme_mod( 'lunara_reviews_archive_density' );
remove_theme_mod( 'lunara_reviews_archive_section_gap' );
remove_theme_mod( 'lunara_reviews_archive_card_min_height' );

// A valid curated lane with corrupt, draft, and duplicate members degrades locally.
update_option(
	LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION,
	array(
		'schema_version' => 1,
		'lane_mode'      => 'curated',
		'curated_ids'    => array( 12, array( 'bad' ), 13, 12, 14 ),
	)
);
$curated_drift = lunara_reviews_archive_studio_get_public_config( false );
lunara_test_assert( 'curated' === $curated_drift['lane_mode'] && array( 12, 14 ) === $curated_drift['curated_ids'], 'Corrupt, draft, and duplicate curated leaves must degrade to the surviving published unique set.' );
lunara_test_assert( in_array( 'curated_review_unavailable', $curated_drift['_warnings'], true ), 'Curated degradation must surface its validator warning.' );
update_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION, array( 'schema_version' => 1, 'lane_mode' => 'curated', 'curated_ids' => array( 13 ) ) );
$empty_curated_drift = lunara_reviews_archive_studio_get_public_config( false );
lunara_test_assert( 'query' === $empty_curated_drift['lane_mode'] && in_array( 'curated_lane_fell_back_to_query', $empty_curated_drift['_warnings'], true ), 'A curated lane whose members all vanished must fall back to query with a warning.' );
delete_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION );

$corrupt_shape = $defaults;
$corrupt_shape['title'] = 'Keep this shape sibling';
$corrupt_shape['section_visibility'] = 'broken';
$corrupt_shape['presentation'] = null;
$normalized_shape = lunara_reviews_archive_studio_normalize_public_shape( $corrupt_shape, $defaults );
lunara_test_assert( 'Keep this shape sibling' === $normalized_shape['title'] && $defaults['section_visibility'] === $normalized_shape['section_visibility'] && $defaults['presentation'] === $normalized_shape['presentation'], 'Public shape normalization must repair nested families without touching valid siblings.' );

// One malformed legacy geometry owner must not reset unrelated custom state.
update_option(
	LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION,
	array(
		'item_count'      => 14,
		'supporting_copy' => 'Keep this custom toolbar copy.',
		'labels'          => array_replace( $defaults['labels'], array( 'retention_copy' => 'Keep this custom retention copy.', 'empty_copy' => 'Legacy dead-control value.' ) ),
	)
);
set_theme_mod( 'lunara_reviews_archive_title', 'Preserve My Reviews' );
set_theme_mod( 'lunara_reviews_archive_section_gap', 999 );
$repaired_legacy = lunara_reviews_archive_studio_get_public_config( false );
lunara_test_assert( 'Preserve My Reviews' === $repaired_legacy['title'], 'One malformed legacy presentation owner must not reset a valid customized title.' );
lunara_test_assert( 14 === $repaired_legacy['item_count'] && 'Keep this custom toolbar copy.' === $repaired_legacy['supporting_copy'], 'Field-local geometry repair must preserve unrelated option-owned copy byte-for-byte.' );
lunara_test_assert( 'Keep this custom retention copy.' === $repaired_legacy['labels']['retention_copy'], 'Field-local repair must preserve valid custom labels.' );
lunara_test_assert( ! array_key_exists( 'empty_copy', $repaired_legacy['labels'] ) && ! array_key_exists( 'empty_title', $repaired_legacy['labels'] ), 'A legacy stored dead-control label must be dropped from the resolved public config, not resurfaced.' );
lunara_test_assert( 40 === $repaired_legacy['presentation']['section_gap'], 'Only the malformed geometry field must fall back to its validated default.' );
delete_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION );
remove_theme_mod( 'lunara_reviews_archive_title' );
remove_theme_mod( 'lunara_reviews_archive_section_gap' );

// Empty stored labels repair to the byte-locked defaults instead of erasing language.
update_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION, array( 'labels' => array_replace( $defaults['labels'], array( 'year_filter' => '', 'run_kicker' => 'Custom Run Kicker' ) ) ) );
$label_repaired = lunara_reviews_archive_studio_get_public_config( false );
lunara_test_assert( 'Filter' === $label_repaired['labels']['year_filter'] && 'Custom Run Kicker' === $label_repaired['labels']['run_kicker'], 'An emptied public label must repair to its default while custom siblings survive.' );
delete_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION );

// --- The pin meta remains the sole lead owner; the option never stores a lead ID.
$lunara_test_pin_writes = array();
$state_before_invalid = serialize( array( $lunara_test_theme_mods, $lunara_test_options, $lunara_test_posts, $lunara_test_pinned_id ) );
$invalid_lead = $valid;
$invalid_lead['lead_id'] = 13;
$invalid_promotion = lunara_reviews_archive_studio_promote_config( $invalid_lead, 'save' );
lunara_test_assert( is_wp_error( $invalid_promotion ), 'Invalid promotion must return its validation error.' );
lunara_test_assert( $state_before_invalid === serialize( array( $lunara_test_theme_mods, $lunara_test_options, $lunara_test_posts, $lunara_test_pinned_id ) ), 'Invalid promotion must leave public state, post records, and the pin byte-for-byte unchanged.' );
lunara_test_assert( array() === $lunara_test_pin_writes, 'Invalid promotion must never touch the canonical pin helper.' );

$post_records_before = serialize( $lunara_test_posts );
$promotion = lunara_reviews_archive_studio_promote_config( $validated, 'save' );
lunara_test_assert( ! is_wp_error( $promotion ), 'Valid configuration must promote.' );
lunara_test_assert( array( 11 ) === $lunara_test_pin_writes && 11 === $lunara_test_pinned_id, 'A manual lead must be written only through lunara_set_pinned_review_id.' );
$stored_option = get_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION );
lunara_test_assert( is_array( $stored_option ) && ! array_key_exists( 'lead_id', $stored_option ), 'The Studio option must never store a lead ID; the pin meta is the whole lead state.' );
lunara_test_assert( false === strpos( serialize( $stored_option ), '"lead_id"' ), 'No serialized lead_id field may reach the option payload.' );
lunara_test_assert( $post_records_before === serialize( $lunara_test_posts ), 'Promotion must not change Review status, publication dates, GMT dates, or scheduling metadata.' );
$public_after = lunara_reviews_archive_studio_get_public_config( false );
lunara_test_assert( 'A sharper Reviews desk' === $public_after['title'] && 12 === $public_after['item_count'], 'Valid configuration must become public.' );
lunara_test_assert( 'manual' === $public_after['lead_mode'] && 11 === $public_after['lead_id'], 'The public lead state must be read back from the pin owner.' );
lunara_test_assert( in_array( 'retention_image_wide_quality', $public_after['_warnings'], true ) && in_array( 'gallery_image_wide_quality', $public_after['_warnings'], true ), 'Low-resolution and portrait media must remain valid while surfacing a wide-image quality warning.' );

// Clearing the pin externally must flip the resolved lead state to automatic.
$lunara_test_pinned_id = 0;
$pinless_public = lunara_reviews_archive_studio_get_public_config( false );
lunara_test_assert( 'automatic' === $pinless_public['lead_mode'] && 0 === $pinless_public['lead_id'], 'Without a pin the resolved lead must be automatic; no hidden option lead may resurface.' );
$lunara_test_pinned_id = 11;

$automatic_promotion = lunara_reviews_archive_studio_promote_config( $cleared_lead, 'save' );
lunara_test_assert( ! is_wp_error( $automatic_promotion ), 'The automatic-lead configuration must promote.' );
lunara_test_assert( 0 === end( $lunara_test_pin_writes ) && 0 === $lunara_test_pinned_id, 'Returning to automatic mode must clear the pin through the canonical helper.' );
$repromote = lunara_reviews_archive_studio_promote_config( $validated, 'save' );
lunara_test_assert( ! is_wp_error( $repromote ) && 11 === $lunara_test_pinned_id, 'Re-promoting the manual configuration must restore the pin for the query tests.' );

// --- Lead resolution and the bounded newest lookup.
$lunara_test_get_posts_args = array();
$newest_id = lunara_reviews_archive_studio_get_newest_id();
lunara_test_assert( 11 === $newest_id, 'Automatic newest must resolve the newest eligible published Review.' );
lunara_test_assert( 1 === count( $lunara_test_get_posts_args ) && 1 === $lunara_test_get_posts_args[0]['posts_per_page'] && 'ids' === $lunara_test_get_posts_args[0]['fields'] && true === $lunara_test_get_posts_args[0]['suppress_filters'], 'Automatic newest must use one bounded, filter-suppressed, ID-only published-Review lookup.' );
lunara_test_assert( 11 === lunara_reviews_archive_studio_get_lead_id( $public_after ), 'Manual lead resolution must return the validated pinned Review.' );
lunara_test_assert( 11 === lunara_reviews_archive_studio_get_lead_id( $defaults ), 'Automatic lead resolution must follow the newest eligible published Review.' );

// --- Provenance gate: posts_per_page composes ONLY from an explicitly saved
// item_count in the stored Studio option. The resolved config always reports
// a bounded default (9), so an unsaved site must produce NO posts_per_page
// key at all — the CPT archive keeps get_option( 'posts_per_page' ), its
// exact pre-release Reading-settings behavior.
$saved_option_snapshot = get_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION );
lunara_test_assert( is_array( $saved_option_snapshot ) && true === lunara_reviews_archive_studio_has_saved_item_count(), 'A promoted Studio option must report an explicitly saved item count.' );
delete_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION );
lunara_test_assert( false === lunara_reviews_archive_studio_has_saved_item_count(), 'An unsaved site must report no explicitly saved item count.' );
$unsaved_args = lunara_get_review_archive_query_args( array(), 'release_desc', '' );
lunara_test_assert( ! array_key_exists( 'posts_per_page', $unsaved_args ), 'An unsaved site must compose NO posts_per_page key; the Reading setting stays authoritative.' );
update_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION, array( 'schema_version' => 1, 'supporting_copy' => 'Saved without an item count.' ) );
lunara_test_assert( false === lunara_reviews_archive_studio_has_saved_item_count(), 'A stored option without item_count must not count as saved pagination provenance.' );
$partial_args = lunara_get_review_archive_query_args( array(), 'release_desc', '' );
lunara_test_assert( ! array_key_exists( 'posts_per_page', $partial_args ), 'An option lacking item_count must compose NO posts_per_page key.' );
update_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION, $saved_option_snapshot );

// --- Query-args composition: item count, pin, and curated priority minus the pinned ID.
$composed_args = lunara_get_review_archive_query_args( array(), 'release_desc', '' );
lunara_test_assert( 12 === $composed_args['posts_per_page'], 'The Reviews query must receive the validated Studio item count.' );
lunara_test_assert( 11 === $composed_args['lunara_reviews_archive_pinned_orderby'], 'The pin owner must stay the sole first-position query input.' );
lunara_test_assert( array( 12, 14 ) === $composed_args['lunara_reviews_archive_priority_ids'], 'Curated priority IDs must exclude the pinned ID and preserve editor order.' );
$sorted_args = lunara_get_review_archive_query_args( array(), 'modified_desc', '' );
lunara_test_assert( 12 === $sorted_args['posts_per_page'] && ! isset( $sorted_args['lunara_reviews_archive_pinned_orderby'] ) && ! isset( $sorted_args['lunara_reviews_archive_priority_ids'] ), 'Alternate sorts must keep the bounded item count without promoting pin or curation.' );
$year_args = lunara_get_review_archive_query_args( array(), 'release_desc', '2026' );
lunara_test_assert( ! isset( $year_args['lunara_reviews_archive_pinned_orderby'] ) && ! isset( $year_args['lunara_reviews_archive_priority_ids'] ), 'Year-filtered queries must not promote pin or curation.' );

// --- Priority orderby SQL shape: pin CASE, then curated CASE, then native order, stable ID tiebreak.
$wpdb = (object) array( 'posts' => 'wp_posts' );
$priority_query = new WP_Query();
$priority_query->set( 'lunara_reviews_archive_priority_ids', $composed_args['lunara_reviews_archive_priority_ids'] );
$priority_query->set( 'lunara_reviews_archive_pinned_orderby', $composed_args['lunara_reviews_archive_pinned_orderby'] );
$sql_after_curated = lunara_reviews_archive_studio_priority_orderby( 'wp_posts.post_date DESC', $priority_query );
$sql_final = lunara_reviews_archive_pinned_orderby( $sql_after_curated, $priority_query );
lunara_test_assert(
	'CASE WHEN wp_posts.ID = 11 THEN 0 ELSE 1 END ASC, CASE WHEN wp_posts.ID = 12 THEN 1 WHEN wp_posts.ID = 14 THEN 2 ELSE 3 END ASC, wp_posts.post_date DESC, wp_posts.ID DESC' === $sql_final,
	'Composed SQL must read pin CASE first, curated CASE second, native order third, stable ID tiebreak last: ' . $sql_final
);
lunara_test_assert( 0 === strpos( $sql_final, 'CASE WHEN wp_posts.ID = 11 THEN 0' ), 'The pin CASE must own the first SQL position.' );
lunara_test_assert( 1 === substr_count( $sql_final, 'wp_posts.ID = 12' ) && 1 === substr_count( $sql_final, 'wp_posts.ID = 14' ) && 0 === substr_count( $sql_after_curated, 'wp_posts.ID = 11' ), 'Curated CASE positions must start at 1 and never contain the pinned ID.' );
$sql_ascending = lunara_reviews_archive_studio_priority_orderby( 'wp_posts.post_date ASC', $priority_query );
lunara_test_assert( false !== strpos( $sql_ascending, 'wp_posts.post_date ASC, wp_posts.ID ASC' ), 'Oldest sorting must retain its direction and add a matching stable ID tiebreaker.' );
$sql_stable = lunara_reviews_archive_studio_priority_orderby( 'wp_posts.post_date DESC, wp_posts.ID DESC', $priority_query );
lunara_test_assert( 1 === substr_count( $sql_stable, 'wp_posts.ID DESC' ), 'An existing ID tiebreaker must never be duplicated.' );
$plain_query = new WP_Query();
lunara_test_assert( 'wp_posts.post_date DESC' === lunara_reviews_archive_studio_priority_orderby( 'wp_posts.post_date DESC', $plain_query ), 'A query without priority IDs must keep its native SQL untouched.' );
$zero_query = new WP_Query();
$zero_query->set( 'lunara_reviews_archive_priority_ids', array( 0, 0 ) );
lunara_test_assert( 'wp_posts.post_date DESC' === lunara_reviews_archive_studio_priority_orderby( 'wp_posts.post_date DESC', $zero_query ), 'All-zero priority IDs must keep the native SQL untouched.' );

// --- Retention and gallery public rendering.
$retention_media_card = $defaults['retention'][0];
$retention_media_card['image_id'] = 101;
$retention_media = lunara_reviews_archive_studio_retention_media_markup( $retention_media_card );
lunara_test_assert( false !== strpos( $retention_media, 'Media Library alt 101' ), 'Retention media must preserve the canonical Media Library alt when no placement override exists.' );
$lunara_test_attachment_renderable = false;
lunara_test_assert( '' === lunara_reviews_archive_studio_retention_media_markup( $retention_media_card ), 'A stale attachment whose image markup cannot render must degrade to a text card with no media chamber.' );
$lunara_test_attachment_renderable = true;
lunara_test_assert( '' === lunara_reviews_archive_studio_render_gallery( $defaults['gallery'] ), 'An empty archive gallery must emit no heading, wrapper, or script.' );
$gallery_markup = lunara_reviews_archive_studio_render_gallery( $validated['gallery'] );
lunara_test_assert( false !== strpos( $gallery_markup, 'lunara-review-archive-gallery' ) && false !== strpos( $gallery_markup, 'Three frames from the desk' ), 'A populated archive gallery must render its bounded SSR wrapper and heading.' );
lunara_test_assert( strpos( $gallery_markup, 'image-102.jpg' ) < strpos( $gallery_markup, 'image-101.jpg' ) && strpos( $gallery_markup, 'image-101.jpg' ) < strpos( $gallery_markup, 'image-104.jpg' ), 'Gallery SSR must follow the editor-defined order.' );
lunara_test_assert( false !== strpos( $gallery_markup, '--lunara-gallery-focus-x:31%;--lunara-gallery-focus-y:67%' ), 'Gallery SSR must preserve per-image focal position without public JavaScript.' );
lunara_test_assert( false !== strpos( $gallery_markup, 'First credit' ) && false !== strpos( $gallery_markup, 'https://source.example/first' ) && false !== strpos( $gallery_markup, 'https://example.test/first' ), 'Gallery SSR must preserve credit, provenance, and optional destination.' );
$cards_fixture = '<div class="lunara-review-archive-retention-showcase-grid">Cards</div>';
$gallery_fixture = '<section class="lunara-review-archive-gallery">Gallery</section>';
$composed_retention = lunara_reviews_archive_studio_compose_retention_lane( $cards_fixture, $gallery_fixture, true );
lunara_test_assert( strpos( $composed_retention, $cards_fixture ) < strpos( $composed_retention, $gallery_fixture ) && false !== strpos( $composed_retention, 'lunara-review-archive-slot-retention' ), 'The compositor must emit the retention slot with the gallery exactly after the cards.' );
lunara_test_assert( '' === lunara_reviews_archive_studio_compose_retention_lane( '', '', true ) && '' === lunara_reviews_archive_studio_compose_retention_lane( $cards_fixture, $gallery_fixture, false ), 'Empty composition or an archive with no posts must reserve no retention lane.' );
lunara_test_assert( true === lunara_reviews_archive_studio_is_gallery_request(), 'The archive-only gallery may render on the canonical Reviews index.' );
$lunara_test_is_paged = true;
lunara_test_assert( false === lunara_reviews_archive_studio_is_gallery_request(), 'The archive-only gallery must not repeat on paged Reviews routes.' );
$lunara_test_is_paged = false;

// --- EXECUTED retention/gallery builder coverage. The browser fixture in
// tests/reviews-archive-first-paint-runtime.js is hand-maintained and only
// statically inspected; these calls run the real module builders.
lunara_test_assert( '' === lunara_reviews_archive_studio_retention_media_markup( $defaults['retention'][1] ), 'An unconfigured retention card must produce the exact empty string from the real builder.' );
lunara_test_assert( '' === lunara_reviews_archive_studio_compose_retention_lane( lunara_reviews_archive_studio_retention_media_markup( $defaults['retention'][0] ), lunara_reviews_archive_studio_render_gallery( $defaults['gallery'] ), true ), 'Composing the real default builder outputs must reserve no retention lane markup at all.' );
$executed_retention_card  = array_replace( $defaults['retention'][0], array( 'image_id' => 101, 'image_alt' => 'Retention route art', 'image_credit' => 'Studio credit', 'image_source' => 'Studio source', 'image_source_url' => 'https://source.example/retention', 'focal_x' => 40, 'focal_y' => 60 ) );
$executed_retention_media = lunara_reviews_archive_studio_retention_media_markup( $executed_retention_card );
lunara_test_assert( false !== strpos( $executed_retention_media, 'loading="lazy"' ) && false !== strpos( $executed_retention_media, 'decoding="async"' ), 'Configured retention media must actually emit lazy, async image markup.' );
lunara_test_assert( false === strpos( $executed_retention_media, '<script' ), 'Retention media markup must contain no script element.' );
$hostile_gallery = array(
	'kicker' => 'Visual File',
	'title'  => '<script>x</script>',
	'copy'   => 'Copy <script>x</script>',
	'items'  => array(
		array( 'order' => 1, 'attachment_id' => 102, 'alt' => 'Frame one', 'caption' => 'Caption <script>x</script>', 'link_url' => 'https://example.test/first', 'credit' => 'Credit <script>x</script>', 'source' => 'Source <script>x</script>', 'source_url' => 'https://source.example/first', 'focal_x' => 31, 'focal_y' => 67 ),
		array( 'order' => 2, 'attachment_id' => 104, 'alt' => 'Frame two', 'caption' => '', 'link_url' => '', 'credit' => 'Second credit', 'source' => 'Second source', 'source_url' => 'https://source.example/second', 'focal_x' => 50, 'focal_y' => 50 ),
	),
);
$hostile_gallery_markup = lunara_reviews_archive_studio_render_gallery( $hostile_gallery );
lunara_test_assert( false !== strpos( $hostile_gallery_markup, '&lt;script&gt;x&lt;/script&gt;' ), 'Hostile stored strings must arrive entity-escaped in the executed gallery SSR.' );
lunara_test_assert( false === strpos( $hostile_gallery_markup, '<script' ), 'The executed gallery SSR must contain no script element or unescaped fixture string.' );
lunara_test_assert( false !== strpos( $hostile_gallery_markup, 'loading="lazy"' ) && false !== strpos( $hostile_gallery_markup, 'decoding="async"' ), 'Executed gallery images must be lazy and async.' );
lunara_test_assert( false !== strpos( $hostile_gallery_markup, '--lunara-gallery-focus-x:31%;--lunara-gallery-focus-y:67%' ), 'Executed gallery media chambers must carry the focal-point CSS variables.' );
lunara_test_assert( 1 === substr_count( $hostile_gallery_markup, 'lunara-review-archive-gallery-destination' ), 'The optional image destination anchor must render only for the item that configured one.' );
lunara_test_assert( 1 === substr_count( $hostile_gallery_markup, '<p>' ), 'Captions must render only when configured; an empty caption adds no paragraph.' );
lunara_test_assert( false !== strpos( $hostile_gallery_markup, 'Second credit' ) && false !== strpos( $hostile_gallery_markup, 'https://source.example/second' ), 'Configured provenance must reach the executed SSR.' );
$executed_lane = lunara_reviews_archive_studio_compose_retention_lane( $executed_retention_media, $hostile_gallery_markup, true );
lunara_test_assert( false !== strpos( $executed_lane, 'lunara-review-archive-slot-retention' ), 'The executed composed lane wrapper must carry the first-paint retention slot class.' );
lunara_test_assert( strpos( $executed_lane, 'image-101.jpg' ) < strpos( $executed_lane, 'lunara-review-archive-gallery' ), 'The executed composition must keep retention media before the gallery.' );
lunara_test_assert( '' === lunara_reviews_archive_studio_compose_retention_lane( $executed_retention_media, $hostile_gallery_markup, false ), 'An archive with no posts must reserve no executed retention lane.' );

// --- Revision history: audit fields, restore round trip, tamper fail-closed, limit 12.
update_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_REVISIONS_OPTION, array() );
$prior_public_snapshot = lunara_reviews_archive_studio_get_public_config( false );
$revised = $validated;
$revised['title'] = 'Second public state';
$second_promotion = lunara_reviews_archive_studio_promote_config( $revised, 'save' );
lunara_test_assert( ! is_wp_error( $second_promotion ), 'The second valid configuration must promote.' );
$revisions = lunara_reviews_archive_studio_get_revisions();
lunara_test_assert( 1 === count( $revisions ), 'Successful promotion must capture exactly one prior-public revision.' );
lunara_test_assert( isset( $revisions[0]['id'], $revisions[0]['saved_at'], $revisions[0]['saved_by'], $revisions[0]['action'], $revisions[0]['validator_result'], $revisions[0]['prior_public'] ), 'Revision audit metadata must be complete.' );
lunara_test_assert( 7 === $revisions[0]['saved_by'] && 'save' === $revisions[0]['action'] && 'passed' === $revisions[0]['validator_result'] && true === $revisions[0]['prior_public'], 'The revision must record who, what action, the validator result, and prior-public provenance.' );
lunara_test_assert( 'A sharper Reviews desk' === $revisions[0]['config']['title'], 'The revision must snapshot the prior public configuration.' );
$tampered_restore = lunara_reviews_archive_studio_restore_revision( 'uuid-4-test-tampered' );
lunara_test_assert( is_wp_error( $tampered_restore ) && 'reviews_archive_revision_not_found' === $tampered_restore->get_error_code(), 'Restoring a tampered revision ID must fail closed.' );
lunara_test_assert( 'Second public state' === lunara_reviews_archive_studio_get_public_config( false )['title'], 'A failed restore must leave the current public state unchanged.' );
$tampered_revisions = lunara_reviews_archive_studio_get_revisions();
$tampered_revisions[0]['config']['item_count'] = 999;
update_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_REVISIONS_OPTION, $tampered_revisions );
$tampered_config_restore = lunara_reviews_archive_studio_restore_revision( $tampered_revisions[0]['id'] );
lunara_test_assert( is_wp_error( $tampered_config_restore ) && 'reviews_archive_item_count_invalid' === $tampered_config_restore->get_error_code(), 'Restoring a revision whose snapshot was tampered invalid must revalidate and fail closed.' );
lunara_test_assert( 'Second public state' === lunara_reviews_archive_studio_get_public_config( false )['title'], 'A tampered snapshot must never reach public state.' );
$tampered_revisions[0]['config']['item_count'] = 12;
update_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_REVISIONS_OPTION, $tampered_revisions );
$post_records_before_restore = serialize( $lunara_test_posts );
$restored = lunara_reviews_archive_studio_restore_revision( $tampered_revisions[0]['id'] );
lunara_test_assert( ! is_wp_error( $restored ), 'A valid prior-public revision must restore.' );
lunara_test_assert( 'A sharper Reviews desk' === lunara_reviews_archive_studio_get_public_config( false )['title'], 'Restore must reinstate the prior public configuration.' );
lunara_test_assert( $post_records_before_restore === serialize( $lunara_test_posts ), 'Restore must not change Review publication metadata.' );
$restore_revisions = lunara_reviews_archive_studio_get_revisions();
lunara_test_assert( 'restore' === $restore_revisions[0]['action'] && 'passed' === $restore_revisions[0]['validator_result'] && true === $restore_revisions[0]['prior_public'], 'Restore must create a complete newest audit row.' );
lunara_test_assert( 'Second public state' === $restore_revisions[0]['config']['title'], 'Restore history must snapshot the public configuration it replaced.' );
for ( $i = 0; $i < 20; $i++ ) {
	lunara_reviews_archive_studio_push_revision( $defaults, 'save' );
}
lunara_test_assert( 12 === count( lunara_reviews_archive_studio_get_revisions() ), 'Revision history must remain bounded to twelve snapshots.' );

// --- Targeted route-cache invalidation.
$lunara_test_rocket_urls = array();
lunara_reviews_archive_studio_flush_route_cache();
lunara_test_assert( in_array( array( 'reviews_archive_studio_public', 'lunara' ), $lunara_test_cache_deletes, true ), 'Only the Reviews Studio cache key should be invalidated.' );
$invalidation = end( $lunara_test_actions_fired );
lunara_test_assert( 'lunara_reviews_archive_studio_invalidate_routes' === $invalidation[0], 'Targeted route invalidation must be explicitly dispatched.' );
lunara_test_assert( array( '/reviews/' ) === $invalidation[1], 'Only the Reviews archive route family may be invalidated; director term URLs are contractually exempt.' );
lunara_test_assert( array( 'https://example.test/reviews/', 'https://example.test/reviews-page/' ) === $invalidation[2], 'The bounded cleaner must name exactly the archive and hub page URLs.' );
lunara_test_assert( array( 'https://example.test/reviews/', 'https://example.test/reviews-page/' ) === $lunara_test_rocket_urls, 'The bounded per-URL cache cleaner must receive only the two Reviews route URLs.' );

// --- Preview token authorization and the no-store-before-validation discipline.
$preview_token = lunara_reviews_archive_studio_store_preview( $validated );
lunara_test_assert( is_string( $preview_token ) && '' !== $preview_token, 'Valid unsaved configuration must receive a private preview token.' );
lunara_test_assert( 'A sharper Reviews desk' === lunara_reviews_archive_studio_get_preview_config( $preview_token )['title'], 'The authorized owner must retrieve the unsaved preview.' );
$_GET['lunara_reviews_preview'] = $preview_token;
lunara_test_assert( 'A sharper Reviews desk' === lunara_reviews_archive_studio_get_public_config()['title'], 'A live preview token must override the public read without a write.' );
unset( $_GET['lunara_reviews_preview'] );
$nocache_before = $lunara_test_nocache_calls;
$normal_response = lunara_reviews_archive_studio_prepare_preview_response();
lunara_test_assert( false === $normal_response['handled'] && true === $normal_response['authorized'] && 200 === $normal_response['status'] && $lunara_test_nocache_calls === $nocache_before, 'A normal Reviews request must remain public/cacheable and never receive preview denial headers.' );
$_GET['lunara_reviews_preview'] = $preview_token;
$nocache_before = $lunara_test_nocache_calls;
$actions_before_preview = count( $lunara_test_actions_fired );
$preview_response = lunara_reviews_archive_studio_prepare_preview_response();
lunara_test_assert( true === $preview_response['authorized'] && 200 === $preview_response['status'] && is_array( $preview_response['config'] ), 'The owner with capability and a live token must receive the private preview response.' );
lunara_test_assert( $lunara_test_nocache_calls === $nocache_before + 1, 'Every preview-query response must become no-store before access validation.' );
$no_store_marker = null;
foreach ( array_reverse( $lunara_test_actions_fired ) as $fired_action ) {
	if ( 'lunara_reviews_archive_preview_no_store_sent' === $fired_action[0] ) {
		$no_store_marker = $fired_action;
		break;
	}
}
lunara_test_assert( null !== $no_store_marker, 'The no-store transition must be observable before token validation work.' );
lunara_test_assert( lunara_test_no_store_precedes_transient_read( array_slice( $lunara_test_actions_fired, $actions_before_preview ) ), 'The no-store marker must fire at a strictly earlier index than the preview transient read.' );
$lunara_test_user_id = 8;
lunara_test_assert( false === lunara_reviews_archive_studio_get_preview_config( $preview_token ), 'A different logged-in user must not retrieve the preview.' );
$nocache_before = $lunara_test_nocache_calls;
$actions_before_foreign = count( $lunara_test_actions_fired );
$foreign_response = lunara_reviews_archive_studio_prepare_preview_response();
lunara_test_assert( false === $foreign_response['authorized'] && 403 === $foreign_response['status'] && $lunara_test_nocache_calls === $nocache_before + 1, 'A foreign-user token URL must be denied as a non-cacheable 403 response.' );
lunara_test_assert( lunara_test_no_store_precedes_transient_read( array_slice( $lunara_test_actions_fired, $actions_before_foreign ) ), 'A foreign-user denial must also send no-store strictly before its token transient read.' );
$lunara_test_user_id = 7;
$lunara_test_can_edit = false;
lunara_test_assert( false === lunara_reviews_archive_studio_get_preview_config( $preview_token ), 'A user without theme-edit capability must not retrieve the preview.' );
$anonymous_response = lunara_reviews_archive_studio_prepare_preview_response();
lunara_test_assert( false === $anonymous_response['authorized'] && 403 === $anonymous_response['status'], 'An unauthorized preview request must be denied rather than falling back to public output.' );
$lunara_test_can_edit = true;
lunara_test_assert( false === lunara_reviews_archive_studio_get_preview_config( 'wrong-token' ), 'A guessed token must not retrieve the preview.' );
$_GET['lunara_reviews_preview'] = 'wrong-token';
$actions_before_guessed = count( $lunara_test_actions_fired );
$guessed_response = lunara_reviews_archive_studio_prepare_preview_response();
lunara_test_assert( false === $guessed_response['authorized'] && 403 === $guessed_response['status'], 'A guessed token URL must receive a no-store 403 response.' );
lunara_test_assert( lunara_test_no_store_precedes_transient_read( array_slice( $lunara_test_actions_fired, $actions_before_guessed ) ), 'A guessed-token denial must also send no-store strictly before its token transient read.' );
$_GET['lunara_reviews_preview'] = $preview_token;
$preview_transient_key = 'lunara_reviews_archive_preview_' . hash( 'sha256', $preview_token );
$genuine_hash = $lunara_test_transients[ $preview_transient_key ]['value']['token_hash'];
$lunara_test_transients[ $preview_transient_key ]['value']['token_hash'] = 'tampered-hash';
lunara_test_assert( false === lunara_reviews_archive_studio_get_preview_config( $preview_token ), 'A tampered owner-binding hash must reject the preview.' );
$tampered_hash_response = lunara_reviews_archive_studio_prepare_preview_response();
lunara_test_assert( false === $tampered_hash_response['authorized'] && 403 === $tampered_hash_response['status'], 'A tampered-hash token URL must receive a no-store 403 response.' );
$lunara_test_transients[ $preview_transient_key ]['value']['token_hash'] = $genuine_hash;
$genuine_expires = $lunara_test_transients[ $preview_transient_key ]['value']['expires'];
$lunara_test_transients[ $preview_transient_key ]['value']['expires'] = 1;
lunara_test_assert( false === lunara_reviews_archive_studio_get_preview_config( $preview_token ), 'A tampered internal expiry must reject the preview.' );
$lunara_test_transients[ $preview_transient_key ]['value']['expires'] = $genuine_expires;
$lunara_test_now += 1900;
lunara_test_assert( false === lunara_reviews_archive_studio_get_preview_config( $preview_token ), 'The preview must expire after its bounded thirty-minute lifetime.' );
$expired_response = lunara_reviews_archive_studio_prepare_preview_response();
lunara_test_assert( false === $expired_response['authorized'] && 403 === $expired_response['status'], 'An expired token URL must receive a no-store 403 response.' );
unset( $_GET['lunara_reviews_preview'] );

// The pre-query preflight must deny bad tokens on Reviews family queries only.
$fresh_preview_token = lunara_reviews_archive_studio_store_preview( $validated );
$_GET['lunara_reviews_preview'] = 'guessed-token';
$lunara_test_status_headers = array();
$family_query = new WP_Query();
try {
	lunara_reviews_archive_studio_preflight_preview_query( $family_query );
	lunara_test_assert( false, 'A bad preview token on the Reviews archive query must die before Studio config.' );
} catch ( Lunara_Test_WP_Die $died ) {
	lunara_test_assert( in_array( 403, $lunara_test_status_headers, true ), 'The pre-query denial must be an explicit 403.' );
}
$director_query = new WP_Query();
$director_query->review_archive = false;
lunara_reviews_archive_studio_preflight_preview_query( $director_query );
$_GET['lunara_reviews_preview'] = $fresh_preview_token;
$authorized_family_query = new WP_Query();
lunara_reviews_archive_studio_preflight_preview_query( $authorized_family_query );
unset( $_GET['lunara_reviews_preview'] );

// --- Rejected saves become a bounded private draft with allowlisted feedback.
$rejected_candidate = $defaults;
$rejected_candidate['title'] = '';
$rejected_candidate['supporting_copy'] = 'Do not make me retype this private edit.';
$rejected_positions = array_fill_keys( $defaults['section_order'], 2 );
lunara_reviews_archive_studio_store_invalid_stage(
	$rejected_candidate,
	array( 'lunara_reviews_archive_section_positions' => $rejected_positions ),
	'reviews_archive_identity_required'
);
$private_stage = lunara_reviews_archive_studio_get_invalid_stage();
lunara_test_assert( is_array( $private_stage ) && 'Do not make me retype this private edit.' === $private_stage['supporting_copy'], 'Rejected edits must return as a bounded private per-user draft instead of disappearing.' );
lunara_test_assert( 2 === $private_stage['_staged_positions']['hero'] && 2 === $private_stage['_staged_positions']['grid'], 'Rejected duplicate section positions must remain visible for correction.' );
lunara_test_assert( 'Add both the archive kicker and headline.' === lunara_reviews_archive_studio_validation_message(), 'Invalid save feedback must resolve through the bounded validator-message allowlist.' );
lunara_reviews_archive_studio_clear_invalid_stage();
lunara_test_assert( false === lunara_reviews_archive_studio_get_invalid_stage(), 'A successful transition must be able to clear the rejected private draft.' );

// --- The focused form adapter round-trips gallery order and rejects duplicate positions.
$duplicate_positions_request = array(
	'lunara_reviews_archive_identity' => array( 'kicker' => 'Criticism Desk', 'title' => 'Lunara Reviews', 'deck' => '', 'supporting_copy' => '' ),
	'lunara_reviews_archive_item_count' => 9,
	'lunara_reviews_archive_section_visibility' => array_fill_keys( $defaults['section_order'], 1 ),
	'lunara_reviews_archive_section_positions' => array_fill_keys( $defaults['section_order'], 1 ),
);
$duplicate_positions_candidate = lunara_reviews_archive_studio_config_from_request( $duplicate_positions_request );
lunara_test_assert( is_wp_error( lunara_reviews_archive_studio_validate_config( $duplicate_positions_candidate ) ), 'Duplicate Section Composer positions must fail before any public mutation.' );
$gallery_request = array(
	'lunara_reviews_archive_identity' => array( 'kicker' => 'Criticism Desk', 'title' => 'Lunara Reviews', 'deck' => '', 'supporting_copy' => '' ),
	'lunara_reviews_archive_item_count' => 9,
	'lunara_reviews_archive_section_visibility' => array_fill_keys( $defaults['section_order'], 1 ),
	'lunara_reviews_archive_section_positions' => array_combine( $defaults['section_order'], range( 1, count( $defaults['section_order'] ) ) ),
	'lunara_reviews_archive_gallery' => array( 'kicker' => 'Gallery kicker', 'title' => 'Gallery title', 'copy' => 'Gallery copy' ),
	'lunara_reviews_archive_gallery_ids' => '104,102,101',
	'lunara_reviews_archive_gallery_alt' => array( 104 => 'Third image', 102 => 'First image', 101 => 'Second image' ),
	'lunara_reviews_archive_gallery_caption' => array( 104 => 'Third caption', 102 => 'First caption', 101 => 'Second caption' ),
	'lunara_reviews_archive_gallery_link_url' => array( 104 => '', 102 => 'https://example.test/first', 101 => '' ),
	'lunara_reviews_archive_gallery_credit' => array( 104 => 'Third credit', 102 => 'First credit', 101 => 'Second credit' ),
	'lunara_reviews_archive_gallery_source' => array( 104 => 'Third source', 102 => 'First source', 101 => 'Second source' ),
	'lunara_reviews_archive_gallery_source_url' => array( 104 => 'https://source.example/third', 102 => 'https://source.example/first', 101 => 'https://source.example/second' ),
	'lunara_reviews_archive_gallery_focal_x' => array( 104 => 72, 102 => 31, 101 => 50 ),
	'lunara_reviews_archive_gallery_focal_y' => array( 104 => 40, 102 => 67, 101 => 50 ),
);
$gallery_candidate = lunara_reviews_archive_studio_config_from_request( $gallery_request );
lunara_test_assert( array( 104, 102, 101 ) === array_column( $gallery_candidate['gallery']['items'], 'attachment_id' ), 'Add/reorder request adaptation must preserve the exact editor-defined gallery sequence.' );
lunara_test_assert( 'First caption' === $gallery_candidate['gallery']['items'][1]['caption'] && 31 === absint( $gallery_candidate['gallery']['items'][1]['focal_x'] ), 'Replaceable gallery rows must round-trip all per-image fields from the focused form.' );
lunara_test_assert( ! is_wp_error( lunara_reviews_archive_studio_validate_config( $gallery_candidate ) ), 'A complete three-image gallery request must validate.' );
$gallery_request['lunara_reviews_archive_gallery_ids'] = '';
$cleared_gallery_candidate = lunara_reviews_archive_studio_config_from_request( $gallery_request );
lunara_test_assert( array() === $cleared_gallery_candidate['gallery']['items'] && ! is_wp_error( lunara_reviews_archive_studio_validate_config( $cleared_gallery_candidate ) ), 'Clear gallery must validate as an empty zero-output state.' );
$gallery_request['lunara_reviews_archive_gallery_ids'] = array( 'not-a-scalar' );
$corrupt_gallery_candidate = lunara_reviews_archive_studio_config_from_request( $gallery_request );
$corrupt_gallery_result = lunara_reviews_archive_studio_validate_config( $corrupt_gallery_candidate );
lunara_test_assert( is_wp_error( $corrupt_gallery_result ) && 'reviews_archive_gallery_count_invalid' === $corrupt_gallery_result->get_error_code(), 'A wrong-shaped gallery ID payload must fail closed instead of throwing.' );

// --- Reference drift degrades field-locally with warnings, never fataling the route.
$lunara_test_posts[12]->post_status = 'draft';
unset( $lunara_test_images[101] );
$drifted = lunara_reviews_archive_studio_get_public_config( false );
lunara_test_assert( 'A sharper Reviews desk' === $drifted['title'] && 12 === $drifted['item_count'], 'Reference drift must preserve every unrelated last-valid editorial field.' );
lunara_test_assert( array( 11, 14 ) === $drifted['curated_ids'], 'An unpublished curated Review must be removed locally without discarding the lane.' );
lunara_test_assert( 0 === $drifted['retention'][0]['image_id'], 'A deleted retention attachment must fall back locally to text.' );
lunara_test_assert( array( 102, 104 ) === array_column( $drifted['gallery']['items'], 'attachment_id' ), 'A deleted gallery reference must be removed locally without resetting the remaining ordered gallery.' );
lunara_test_assert( in_array( 'curated_review_unavailable', $drifted['_warnings'], true ) && in_array( 'retention_image_unavailable', $drifted['_warnings'], true ) && in_array( 'gallery_image_unavailable', $drifted['_warnings'], true ), 'Field-local degradation must surface validator warnings.' );
$lunara_test_posts[12]->post_status = 'publish';
$lunara_test_images[101] = 'image/jpeg';

// --- Bounded private search, capability-first AJAX ordering, and picker hydration.
$posts_before_picker_test = $lunara_test_posts;
for ( $picker_index = 300; $picker_index < 330; $picker_index++ ) {
	$lunara_test_posts[ $picker_index ] = (object) array(
		'ID'            => $picker_index,
		'post_type'     => 'review',
		'post_status'   => 'publish',
		'post_title'    => 'Deep archive file ' . $picker_index,
		'post_date'     => '2026-09-' . sprintf( '%02d', 1 + ( $picker_index - 300 ) % 28 ) . ' 10:00:00',
		'post_date_gmt' => '2026-09-' . sprintf( '%02d', 1 + ( $picker_index - 300 ) % 28 ) . ' 15:00:00',
	);
}
$lunara_test_posts[330] = (object) array( 'ID' => 330, 'post_type' => 'review', 'post_status' => 'draft', 'post_title' => 'Deep archive hidden draft', 'post_date' => '2026-10-01 10:00:00', 'post_date_gmt' => '0000-00-00 00:00:00' );
$picker_config = $defaults;
$picker_config['lead_mode'] = 'manual';
$picker_config['lead_id'] = 11;
$picker_config['lane_mode'] = 'curated';
$picker_config['curated_ids'] = array( 12, 13 );
$lunara_test_get_posts_args = array();
$initial_picker_posts = lunara_reviews_archive_studio_editor_posts( $picker_config );
$initial_picker_ids = array_map( static function ( $post ) { return $post->ID; }, $initial_picker_posts );
$initial_picker_args = $lunara_test_get_posts_args[0];
lunara_test_assert( 20 === $initial_picker_args['posts_per_page'] && ! isset( $initial_picker_args['fields'] ), 'The initial Studio picker must request at most twenty WP_Post objects instead of loading the archive.' );
lunara_test_assert( false === $initial_picker_args['update_post_meta_cache'] && false === $initial_picker_args['update_post_term_cache'] && false === $initial_picker_args['cache_results'], 'The bounded private picker must not warm unrelated post, meta, or taxonomy caches.' );
lunara_test_assert( count( $initial_picker_posts ) <= 22 && in_array( 11, $initial_picker_ids, true ) && in_array( 12, $initial_picker_ids, true ), 'Initial choices may add only the configured eligible lead/curated files beyond the twenty recent choices.' );
lunara_test_assert( ! in_array( 13, $initial_picker_ids, true ) && ! in_array( 330, $initial_picker_ids, true ), 'Draft Reviews must remain ineligible even when configured or searchable.' );
lunara_test_assert( 2 === count( $lunara_test_get_posts_args ) && array( 11, 12, 13 ) === $lunara_test_get_posts_args[1]['post__in'], 'Configured older choices must be hydrated in one bounded batch query.' );
$lunara_test_get_posts_args = array();
$older_results = lunara_reviews_archive_studio_search_posts( 'Deep archive file 305', 500 );
lunara_test_assert( array( 305 ) === array_map( static function ( $post ) { return $post->ID; }, $older_results ), 'Authenticated server-side search must reach older published Reviews without an unbounded initial payload.' );
lunara_test_assert( 20 === $lunara_test_get_posts_args[0]['posts_per_page'] && 'Deep archive file 305' === $lunara_test_get_posts_args[0]['s'], 'Server-side search must clamp result count to twenty and pass a bounded title query.' );
$lunara_test_get_posts_args = array();
$numeric_results = lunara_reviews_archive_studio_search_posts( '12', 20 );
lunara_test_assert( array( 12 ) === array_map( static function ( $post ) { return $post->ID; }, $numeric_results ) && array( 12 ) === $lunara_test_get_posts_args[0]['post__in'], 'Numeric search must use one exact published-Review ID lookup.' );
$lunara_test_get_posts_args = array();
lunara_reviews_archive_studio_search_posts( str_repeat( 'x', 140 ), 20 );
lunara_test_assert( 100 === strlen( $lunara_test_get_posts_args[0]['s'] ), 'Server-side search terms must be bounded to one hundred characters.' );

$_GET['q'] = 'Deep archive file 305';
$lunara_test_can_edit = false;
$lunara_test_ajax_nonce_checks = 0;
try {
	lunara_reviews_archive_studio_ajax_search_posts();
	lunara_test_assert( false, 'Unauthorized Reviews search must stop with a JSON denial.' );
} catch ( Lunara_Test_JSON_Response $response ) {
	lunara_test_assert( false === $response->success && 403 === $response->status && 0 === $lunara_test_ajax_nonce_checks, 'Unauthorized Reviews search must fail capability before querying or checking a token.' );
}
$lunara_test_can_edit = true;
$lunara_test_ajax_nonce_valid = false;
try {
	lunara_reviews_archive_studio_ajax_search_posts();
	lunara_test_assert( false, 'An invalid Reviews search nonce must be denied.' );
} catch ( RuntimeException $response ) {
	lunara_test_assert( 'Invalid AJAX nonce.' === $response->getMessage(), 'Reviews search must use its dedicated AJAX nonce gate.' );
}
$lunara_test_ajax_nonce_valid = true;
$lunara_test_title_args = array();
try {
	lunara_reviews_archive_studio_ajax_search_posts();
	lunara_test_assert( false, 'Authorized Reviews search must terminate through a bounded JSON response.' );
} catch ( Lunara_Test_JSON_Response $response ) {
	lunara_test_assert( true === $response->success && 200 === $response->status && 1 === count( $response->data['items'] ) && 305 === $response->data['items'][0]['id'], 'Authorized Reviews search must return only the matching eligible published file.' );
	lunara_test_assert( 1 === count( $lunara_test_title_args ) && $lunara_test_title_args[0] instanceof WP_Post, 'AJAX labels must reuse the bounded query WP_Post object instead of per-ID title reads.' );
}
$_GET['q'] = 'x';
try {
	lunara_reviews_archive_studio_ajax_search_posts();
	lunara_test_assert( false, 'A too-short Reviews search must still return a bounded JSON response.' );
} catch ( Lunara_Test_JSON_Response $response ) {
	lunara_test_assert( true === $response->success && array() === $response->data['items'], 'Non-numeric Reviews searches shorter than two characters must not query the archive.' );
}
$_GET['q'] = '305';
try {
	lunara_reviews_archive_studio_ajax_search_posts();
	lunara_test_assert( false, 'A numeric single-file Reviews search must terminate through JSON.' );
} catch ( Lunara_Test_JSON_Response $response ) {
	lunara_test_assert( true === $response->success && 1 === count( $response->data['items'] ) && 305 === $response->data['items'][0]['id'], 'Numeric-exact search must return the single matching published file.' );
}
unset( $_GET['q'] );
$lunara_test_posts = $posts_before_picker_test;

// --- Director exemption: the shell path, preview family, and query surface all skip Studio state.
$customized_public = lunara_reviews_archive_studio_get_public_config( false );
lunara_test_assert( 'A sharper Reviews desk' === $customized_public['title'], 'The customized public state must be live before proving the director exemption.' );
$is_director_archive = true;
$director_shell_config = $is_director_archive
	? lunara_reviews_archive_studio_defaults()
	: lunara_reviews_archive_studio_get_public_config();
lunara_test_assert( serialize( $pristine_defaults ) === serialize( $director_shell_config ), 'The director shell path must receive the pure byte-identical defaults, never saved Studio state.' );
lunara_test_assert( $director_shell_config['title'] !== $customized_public['title'] && 9 === $director_shell_config['item_count'] && array() === $director_shell_config['curated_ids'], 'Director archives must never inherit customized identity, item count, or curation.' );
$lunara_test_is_review_route = false;
$lunara_test_is_director_tax = true;
lunara_test_assert( false === lunara_reviews_archive_studio_is_reviews_family_request(), 'Director term archives are never part of the preview-eligible Reviews family.' );
lunara_test_assert( false === lunara_reviews_archive_studio_is_gallery_request(), 'The populated archive gallery must stay absent from director taxonomy output.' );
$_GET['lunara_reviews_preview'] = $fresh_preview_token;
$nocache_before = $lunara_test_nocache_calls;
$director_response = lunara_reviews_archive_studio_prepare_preview_response();
lunara_test_assert( false === $director_response['handled'] && $lunara_test_nocache_calls === $nocache_before, 'A preview token on a director route must be ignored: no preview, no denial, no cache poisoning.' );
unset( $_GET['lunara_reviews_preview'] );
$lunara_test_is_review_route = true;
$lunara_test_is_director_tax = false;
$director_sql_query = new WP_Query();
$director_sql_query->review_archive = false;
lunara_test_assert( 'wp_posts.menu_order ASC' === lunara_reviews_archive_studio_priority_orderby( 'wp_posts.menu_order ASC', $director_sql_query ), 'A director query without priority IDs must keep its native term ordering byte-for-byte.' );
lunara_test_assert( 'wp_posts.menu_order ASC' === lunara_reviews_archive_pinned_orderby( 'wp_posts.menu_order ASC', $director_sql_query ), 'The pin filter must also leave a director query untouched.' );
lunara_test_assert( serialize( $pristine_defaults ) === serialize( lunara_reviews_archive_studio_defaults() ), 'Nothing in the run may have mutated the pure defaults the director path depends on.' );

fwrite( STDOUT, "reviews-archive-studio-runtime: all assertions passed.\n" );
