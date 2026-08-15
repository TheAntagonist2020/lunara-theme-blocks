<?php
/**
 * Isolated behavioral contract for Theme 3.2.44 Journal Archive Studio.
 *
 * Run: php tests/journal-archive-studio-runtime.php
 */

define( 'ABSPATH', __DIR__ . '/' );

$lunara_test_theme_mods   = array();
$lunara_test_options      = array();
$lunara_test_cache_deletes = array();
$lunara_test_actions_fired = array();
$lunara_test_rocket_urls   = array();
$lunara_test_transients    = array();
$lunara_test_user_id       = 7;
$lunara_test_can_edit      = true;
$lunara_test_now           = 2000000000;
$lunara_test_sort          = 'date_desc';
$lunara_test_is_journal_route = true;
$lunara_test_is_journal_tax = false;
$lunara_test_is_paged      = false;
$lunara_test_nocache_calls = 0;
$lunara_test_shared_lead_id = 11;
$lunara_test_get_posts_args = array();
$lunara_test_ajax_nonce_valid = true;
$lunara_test_ajax_nonce_checks = 0;
$lunara_test_title_args = array();
$lunara_test_posts = array(
	11 => (object) array( 'ID' => 11, 'post_type' => 'journal', 'post_status' => 'publish', 'post_title' => 'Newest published file', 'post_date' => '2026-08-10 10:00:00', 'post_date_gmt' => '2026-08-10 15:00:00' ),
	12 => (object) array( 'ID' => 12, 'post_type' => 'journal', 'post_status' => 'publish', 'post_title' => 'Older published file', 'post_date' => '2026-08-09 10:00:00', 'post_date_gmt' => '2026-08-09 15:00:00' ),
	13 => (object) array( 'ID' => 13, 'post_type' => 'journal', 'post_status' => 'draft', 'post_title' => 'Hidden draft file', 'post_date' => '2026-08-15 10:00:00', 'post_date_gmt' => '0000-00-00 00:00:00' ),
	101 => (object) array( 'ID' => 101, 'post_type' => 'attachment', 'post_status' => 'inherit' ),
	102 => (object) array( 'ID' => 102, 'post_type' => 'attachment', 'post_status' => 'inherit' ),
	103 => (object) array( 'ID' => 103, 'post_type' => 'attachment', 'post_status' => 'inherit' ),
	104 => (object) array( 'ID' => 104, 'post_type' => 'attachment', 'post_status' => 'inherit' ),
);
$lunara_test_images = array( 101 => 'image/jpeg', 102 => 'image/webp', 103 => 'application/pdf', 104 => 'image/png', 105 => 'image/jpeg', 11 => 'image/jpeg' );
$lunara_test_attachment_renderable = true;

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

class WP_Query {
	private $values = array();
	public $is_main = true;
	public $journal_archive = true;
	public $journal_tax = false;
	public $paged = 1;
	public function is_main_query() { return $this->is_main; }
	public function is_post_type_archive( $type ) { return $this->journal_archive && 'journal' === $type; }
	public function is_tax() { return $this->journal_tax; }
	public function get( $key ) { return isset( $this->values[ $key ] ) ? $this->values[ $key ] : null; }
	public function set( $key, $value ) { $this->values[ $key ] = $value; }
}

function add_action() {}
function add_filter() {}
function is_admin() { return false; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function __( $value ) { return $value; }
function sanitize_key( $value ) { if ( ! is_scalar( $value ) ) { throw new TypeError( 'sanitize_key expects a scalar test value' ); } return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function sanitize_title( $value ) { return sanitize_key( $value ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function wp_unslash( $value ) { return $value; }
function absint( $value ) { if ( ! is_scalar( $value ) ) { throw new TypeError( 'absint expects a scalar test value' ); } return abs( (int) $value ); }
function esc_url_raw( $value ) { if ( ! is_scalar( $value ) ) { throw new TypeError( 'esc_url_raw expects a scalar test value' ); } return filter_var( (string) $value, FILTER_SANITIZE_URL ); }
function esc_url( $value ) { return esc_url_raw( $value ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function wp_http_validate_url( $url ) { return 'https' === parse_url( $url, PHP_URL_SCHEME ) && (bool) parse_url( $url, PHP_URL_HOST ) ? $url : false; }
function home_url( $path = '' ) { return 'https://example.test/' . ltrim( $path, '/' ); }
function get_theme_mod( $key, $default = '' ) { global $lunara_test_theme_mods; return array_key_exists( $key, $lunara_test_theme_mods ) ? $lunara_test_theme_mods[ $key ] : $default; }
function set_theme_mod( $key, $value ) { global $lunara_test_theme_mods; $lunara_test_theme_mods[ $key ] = $value; }
function remove_theme_mod( $key ) { global $lunara_test_theme_mods; unset( $lunara_test_theme_mods[ $key ] ); }
function get_option( $key, $default = false ) { global $lunara_test_options; return array_key_exists( $key, $lunara_test_options ) ? $lunara_test_options[ $key ] : $default; }
function update_option( $key, $value ) { global $lunara_test_options; $lunara_test_options[ $key ] = $value; return true; }
function delete_option( $key ) { global $lunara_test_options; unset( $lunara_test_options[ $key ] ); return true; }
function get_post( $id ) { global $lunara_test_posts; return isset( $lunara_test_posts[ $id ] ) ? $lunara_test_posts[ $id ] : null; }
function get_posts( $args ) {
	global $lunara_test_posts, $lunara_test_get_posts_args;
	$lunara_test_get_posts_args[] = $args;
	$posts = array_filter(
		$lunara_test_posts,
		static function ( $post ) use ( $args ) {
			if ( 'journal' !== $post->post_type || 'publish' !== $post->post_status ) {
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
function wp_get_attachment_image( $id, $size, $icon, $attrs ) { global $lunara_test_attachment_renderable; return $lunara_test_attachment_renderable ? '<img width="1920" height="1080" src="image-' . $id . '.jpg" srcset="image-' . $id . '.jpg 1920w" sizes="' . $attrs['sizes'] . '" alt="' . $attrs['alt'] . '">' : ''; }
function wp_cache_get() { return false; }
function wp_cache_set() { return true; }
function wp_cache_delete( $key, $group ) { global $lunara_test_cache_deletes; $lunara_test_cache_deletes[] = array( $key, $group ); return true; }
function do_action( $hook, $payload = null ) { global $lunara_test_actions_fired; $lunara_test_actions_fired[] = array( $hook, $payload ); }
function get_current_user_id() { global $lunara_test_user_id; return $lunara_test_user_id; }
function current_user_can( $capability ) { global $lunara_test_can_edit; return $lunara_test_can_edit && 'edit_theme_options' === $capability; }
function check_ajax_referer( $action, $field ) { global $lunara_test_ajax_nonce_valid, $lunara_test_ajax_nonce_checks; $lunara_test_ajax_nonce_checks++; if ( ! $lunara_test_ajax_nonce_valid || 'lunara_journal_archive_studio_search' !== $action || 'nonce' !== $field ) { throw new RuntimeException( 'Invalid AJAX nonce.' ); } return true; }
function wp_send_json_success( $data = null, $status = 200 ) { throw new Lunara_Test_JSON_Response( true, $data, $status ); }
function wp_send_json_error( $data = null, $status = 400 ) { throw new Lunara_Test_JSON_Response( false, $data, $status ); }
function current_time() { return '2026-08-15 12:00:00'; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_generate_uuid4() { return '11111111-2222-4333-8444-555555555555'; }
function wp_hash( $value ) { return hash( 'sha256', 'test-salt|' . $value ); }
function set_transient( $key, $value, $ttl ) { global $lunara_test_transients, $lunara_test_now; $lunara_test_transients[ $key ] = array( 'value' => $value, 'expires' => $lunara_test_now + $ttl ); return true; }
function get_transient( $key ) { global $lunara_test_transients, $lunara_test_now; return isset( $lunara_test_transients[ $key ] ) && $lunara_test_transients[ $key ]['expires'] > $lunara_test_now ? $lunara_test_transients[ $key ]['value'] : false; }
function delete_transient( $key ) { global $lunara_test_transients; unset( $lunara_test_transients[ $key ] ); return true; }
function lunara_get_curated_journal_lead_id() { global $lunara_test_shared_lead_id; return $lunara_test_shared_lead_id; }
function lunara_get_editorial_archive_sort() { global $lunara_test_sort; return $lunara_test_sort; }
function get_post_type_archive_link() { return 'https://example.test/journal/'; }
function taxonomy_exists( $taxonomy ) { return in_array( $taxonomy, array( 'journal_section', 'journal_topic', 'journal_type' ), true ); }
function get_terms( $args ) { return array( (object) array( 'taxonomy' => $args['taxonomy'], 'slug' => 'sample' ) ); }
function get_term_link( $term ) { return 'https://example.test/' . $term->taxonomy . '/' . $term->slug . '/'; }
function rocket_clean_files( $urls ) { global $lunara_test_rocket_urls; $lunara_test_rocket_urls = $urls; }
function is_post_type_archive( $type ) { global $lunara_test_is_journal_route; return $lunara_test_is_journal_route && 'journal' === $type; }
function is_tax() { global $lunara_test_is_journal_tax; return $lunara_test_is_journal_tax; }
function is_paged() { global $lunara_test_is_paged; return $lunara_test_is_paged; }
function nocache_headers() { global $lunara_test_nocache_calls; $lunara_test_nocache_calls++; }

require dirname( __DIR__ ) . '/inc/journal-archive-studio.php';
require dirname( __DIR__ ) . '/inc/helpers.php';
require dirname( __DIR__ ) . '/inc/journal-archive-critical.php';

$defaults = lunara_journal_archive_studio_defaults();
lunara_test_assert(
	array( 'hero', 'deskbar', 'filters', 'toolbar', 'grid', 'retention', 'pagination' ) === $defaults['section_order'],
	'Default section order must preserve the proven public sequence.'
);
lunara_test_assert( 8 === $defaults['item_count'], 'Journal Archive must retain eight cards by default.' );
lunara_test_assert( 'shared' === $defaults['lead_mode'], 'Archive lead must preserve the shared homepage owner by default.' );
lunara_test_assert( 'query' === $defaults['lane_mode'], 'Archive run must remain query-driven by default.' );
lunara_test_assert( array() === $defaults['curated_ids'], 'Default curation must not alter public output.' );
lunara_test_assert( array( 1, 2, 3 ) === array_column( $defaults['retention'], 'order' ), 'Retention cards must expose a stable independent order.' );
lunara_test_assert( array() === $defaults['gallery']['items'], 'The archive gallery must default empty so it adds no public wrapper or geometry.' );
lunara_test_assert( 'hero,deskbar,filters,toolbar,grid,retention,pagination' === lunara_sanitize_journal_archive_section_order( 'hero,filters,grid,pagination' ), 'The actual Customizer sanitizer must insert all three new lanes into their canonical legacy positions.' );
lunara_test_assert( 'grid,hero,pagination,deskbar,filters,toolbar,retention' === lunara_sanitize_journal_archive_section_order( 'grid,hero,pagination,deskbar,filters,toolbar,retention' ), 'A complete explicit seven-lane Customizer order must round-trip without semantic rewriting.' );
lunara_test_assert( '' === lunara_journal_archive_studio_render_gallery( $defaults['gallery'] ), 'An empty archive gallery must emit no heading, wrapper, or script.' );
lunara_test_assert( true === lunara_journal_archive_studio_is_gallery_request(), 'The archive-only gallery may render on canonical root /journal/.' );
$lunara_test_is_paged = true;
lunara_test_assert( false === lunara_journal_archive_studio_is_gallery_request(), 'The archive-only gallery must not repeat on paged /journal/ routes.' );
$lunara_test_is_paged = false;
foreach ( array( 'journal_section', 'journal_topic', 'journal_type' ) as $taxonomy_route ) {
	$lunara_test_is_journal_tax = true;
	lunara_test_assert( false === lunara_journal_archive_studio_is_gallery_request(), 'The populated archive gallery must stay absent from ' . $taxonomy_route . ' taxonomy output.' );
}
$lunara_test_is_journal_tax = false;
$cards_fixture = '<div class="lunara-journal-archive-retention-grid">Cards</div>';
$gallery_fixture = '<section class="lunara-journal-archive-gallery">Gallery</section>';
$composed_retention = lunara_journal_archive_studio_compose_retention_lane( $cards_fixture, $gallery_fixture, true );
lunara_test_assert( strpos( $composed_retention, $cards_fixture ) < strpos( $composed_retention, $gallery_fixture ) && 1 === substr_count( $composed_retention, 'lunara-journal-archive-gallery' ), 'The compositor must emit the root gallery exactly once after retention cards.' );
$gallery_only_retention = lunara_journal_archive_studio_compose_retention_lane( '', $gallery_fixture, true );
lunara_test_assert( false !== strpos( $gallery_only_retention, $gallery_fixture ) && false === strpos( $gallery_only_retention, 'retention-grid' ), 'A populated gallery must still render when all three retention cards are hidden, without an empty card grid.' );
lunara_test_assert( false === strpos( lunara_journal_archive_studio_compose_retention_lane( $cards_fixture, '', true ), 'archive-gallery' ), 'Taxonomy-native composition with an empty gallery input must emit no archive gallery.' );
lunara_test_assert( '' === lunara_journal_archive_studio_compose_retention_lane( '', $gallery_fixture, false ), 'An archive with no eligible posts must not reserve a retention/gallery lane.' );

delete_option( LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION );
set_theme_mod( 'lunara_journal_archive_kicker', 'The Lunara Journal' );
set_theme_mod( 'lunara_journal_archive_title', 'Journal' );
$pre_studio_identity = lunara_journal_archive_studio_get_public_config( false );
lunara_test_assert( 'Journal' === $pre_studio_identity['kicker'] && 'Lunara Journal' === $pre_studio_identity['title'], 'An exact pre-Studio legacy sentinel pair must preserve the 3.2.43 effective public identity without a write.' );
update_option( LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION, array( 'schema_version' => 1 ) );
$post_studio_identity = lunara_journal_archive_studio_get_public_config( false );
lunara_test_assert( 'The Lunara Journal' === $post_studio_identity['kicker'] && 'Journal' === $post_studio_identity['title'], 'Once the existing Studio schema marker is present, those same explicit phrases must round-trip verbatim.' );
delete_option( LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION );
remove_theme_mod( 'lunara_journal_archive_kicker' );
remove_theme_mod( 'lunara_journal_archive_title' );

$corrupt_public_labels = $defaults['labels'];
$corrupt_public_labels['empty_copy'] = 'This valid sibling should survive.';
update_option(
	LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION,
	array(
		'schema_version'   => 1,
		'item_count'       => 14,
		'supporting_copy'  => 'Preserve this unrelated copy exactly.',
		'filter_caps'      => 'not-an-array',
		'labels'           => null,
		'gallery'          => array( 'kicker' => 'Custom Visual File', 'title' => 'Custom gallery title', 'copy' => 'Keep gallery copy.', 'items' => 'not-an-array' ),
		'retention'        => array( 'bad' => 'not-a-card' ),
	)
);
set_theme_mod( 'lunara_journal_archive_title', 'Corruption-safe Journal' );
$corrupt_public = lunara_journal_archive_studio_get_public_config( false );
lunara_test_assert( 'Corruption-safe Journal' === $corrupt_public['title'] && 14 === $corrupt_public['item_count'] && 'Preserve this unrelated copy exactly.' === $corrupt_public['supporting_copy'], 'Wrong-shaped nested option families must not fatal or reset unrelated valid owners.' );
lunara_test_assert( $defaults['filter_caps'] === $corrupt_public['filter_caps'] && $defaults['labels'] === $corrupt_public['labels'], 'Scalar and null bounded families must repair locally to validated defaults.' );
lunara_test_assert( 'Custom Visual File' === $corrupt_public['gallery']['kicker'] && array() === $corrupt_public['gallery']['items'], 'A corrupt gallery item list must be cleared locally while valid gallery framing survives.' );
lunara_test_assert( $defaults['retention'] === $corrupt_public['retention'], 'Associative retention corruption must repair to the bounded three-card defaults.' );

$corrupt_shape = $defaults;
$corrupt_shape['title'] = 'Keep this shape sibling';
$corrupt_shape['section_visibility'] = 'broken';
$corrupt_shape['presentation'] = null;
$normalized_shape = lunara_journal_archive_studio_normalize_public_shape( $corrupt_shape, $defaults );
lunara_test_assert( 'Keep this shape sibling' === $normalized_shape['title'] && $defaults['section_visibility'] === $normalized_shape['section_visibility'] && $defaults['presentation'] === $normalized_shape['presentation'], 'Public shape normalization must repair theme-owned nested families without touching valid siblings.' );

foreach ( array( 'filter_caps', 'labels', 'section_visibility', 'presentation', 'retention' ) as $wrong_shape_family ) {
	$wrong_shape_candidate = $defaults;
	$wrong_shape_candidate[ $wrong_shape_family ] = 'not-an-array';
	lunara_test_assert( is_wp_error( lunara_journal_archive_studio_validate_config( $wrong_shape_candidate ) ), 'Strict validation must reject wrong-shaped ' . $wrong_shape_family . ' input instead of coercing it or throwing.' );
}
$wrong_gallery_items = $defaults;
$wrong_gallery_items['gallery']['items'] = 'not-an-array';
lunara_test_assert( is_wp_error( lunara_journal_archive_studio_validate_config( $wrong_gallery_items ) ), 'Strict validation must reject a wrong-shaped gallery item collection.' );

$wrong_scalar_candidates = array();
foreach ( array( 'lead_mode', 'lead_id', 'lane_mode', 'item_count' ) as $scalar_key ) {
	$candidate = $defaults;
	$candidate[ $scalar_key ] = array( 'malformed' );
	$wrong_scalar_candidates[] = $candidate;
}
$candidate = $defaults;
$candidate['section_order'][0] = array( 'hero' );
$wrong_scalar_candidates[] = $candidate;
$candidate = $defaults;
$candidate['filter_caps']['journal_topic'] = array( 10 );
$wrong_scalar_candidates[] = $candidate;
$candidate = $defaults;
$candidate['labels']['empty_copy'] = array( 'copy' );
$wrong_scalar_candidates[] = $candidate;
$candidate = $defaults;
$candidate['presentation']['density'] = array( 'editorial' );
$wrong_scalar_candidates[] = $candidate;
$candidate = $defaults;
$candidate['gallery']['items'] = array( array( 'order' => 1, 'attachment_id' => 101, 'alt' => array( 'alt' ), 'caption' => '', 'link_url' => '', 'credit' => 'Credit', 'source' => 'Source', 'source_url' => 'https://source.example/scalar', 'focal_x' => 50, 'focal_y' => 50 ) );
$wrong_scalar_candidates[] = $candidate;
$candidate = $defaults;
$candidate['retention'][0]['destination'] = array( 'custom' );
$wrong_scalar_candidates[] = $candidate;
foreach ( $wrong_scalar_candidates as $wrong_scalar_candidate ) {
	lunara_test_assert( is_wp_error( lunara_journal_archive_studio_validate_config( $wrong_scalar_candidate ) ), 'Strict validation must fail closed on every array-valued scalar leaf without throwing.' );
}

update_option(
	LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION,
	array(
		'schema_version'  => 1,
		'supporting_copy' => 'This valid public sibling survives scalar corruption.',
		'item_count'      => 16,
		'lead_mode'       => array( 'manual' ),
		'lead_id'         => array( 11 ),
		'lane_mode'       => array( 'curated' ),
		'filter_caps'     => array( 'journal_section' => array( 8 ), 'journal_topic' => 9, 'journal_type' => 7 ),
		'labels'          => array( 'empty_copy' => array( 'broken' ) ),
		'gallery'         => array( 'kicker' => 'Preserved gallery kicker', 'title' => 'Preserved gallery title', 'copy' => '', 'items' => array() ),
		'retention'       => $defaults['retention'],
	)
);
$corrupt_retention = $defaults['retention'];
$corrupt_retention[0]['destination'] = array( 'custom' );
$corrupt_option = get_option( LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION );
$corrupt_option['retention'] = $corrupt_retention;
update_option( LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION, $corrupt_option );
set_theme_mod( 'lunara_journal_archive_title', 'Scalar-safe Journal' );
set_theme_mod( 'lunara_journal_archive_section_order', array( 'hero', 'filters', 'grid', 'pagination' ) );
set_theme_mod( 'lunara_journal_archive_density', array( 'editorial' ) );
$scalar_public = lunara_journal_archive_studio_get_public_config( false );
lunara_test_assert( 'Scalar-safe Journal' === $scalar_public['title'] && 'This valid public sibling survives scalar corruption.' === $scalar_public['supporting_copy'] && 16 === $scalar_public['item_count'], 'Array-valued public scalar leaves must repair locally without losing unrelated owners.' );
lunara_test_assert( 'shared' === $scalar_public['lead_mode'] && 0 === $scalar_public['lead_id'] && 'query' === $scalar_public['lane_mode'], 'Corrupt public lead and lane enums must fall back to their field defaults.' );
lunara_test_assert( $defaults['section_order'] === $scalar_public['section_order'] && 'editorial' === $scalar_public['presentation']['density'], 'Corrupt theme-mod scalar leaves must recover without warnings or a fatal.' );
lunara_test_assert( 8 === $scalar_public['filter_caps']['journal_section'] && 9 === $scalar_public['filter_caps']['journal_topic'], 'Only the corrupt filter-cap leaf must fall back.' );
lunara_test_assert( $defaults['labels']['empty_copy'] === $scalar_public['labels']['empty_copy'] && 'latest' === $scalar_public['retention'][0]['destination'], 'Corrupt label and retention leaves must repair only their own fields.' );
$bounded_scalar_stage = lunara_journal_archive_studio_bound_invalid_stage( $corrupt_option );
lunara_test_assert( 'shared' === $bounded_scalar_stage['lead_mode'] && 'latest' === $bounded_scalar_stage['retention'][0]['destination'], 'Rejected array-valued scalar input must remain a safe private stage instead of throwing.' );
delete_option( LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION );
remove_theme_mod( 'lunara_journal_archive_title' );
remove_theme_mod( 'lunara_journal_archive_section_order' );
remove_theme_mod( 'lunara_journal_archive_density' );

$preserved_labels = $defaults['labels'];
$preserved_labels['empty_copy'] = 'Keep this custom empty-state copy.';
update_option(
	LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION,
	array(
		'item_count'     => 14,
		'supporting_copy' => 'Keep this custom toolbar copy.',
		'lead_mode'      => 'automatic',
		'labels'         => $preserved_labels,
	)
);
set_theme_mod( 'lunara_journal_archive_title', 'Preserve My Journal' );
set_theme_mod( 'lunara_journal_archive_section_gap', 999 );
$repaired_legacy = lunara_journal_archive_studio_get_public_config( false );
lunara_test_assert( 'Preserve My Journal' === $repaired_legacy['title'], 'One malformed legacy presentation owner must not reset a valid customized title.' );
lunara_test_assert( 14 === $repaired_legacy['item_count'] && 'Keep this custom toolbar copy.' === $repaired_legacy['supporting_copy'], 'Field-local geometry repair must preserve unrelated option-owned curation and copy byte-for-byte.' );
lunara_test_assert( 'Keep this custom empty-state copy.' === $repaired_legacy['labels']['empty_copy'] && 'automatic' === $repaired_legacy['lead_mode'], 'Field-local repair must preserve valid labels and lead state.' );
lunara_test_assert( 38 === $repaired_legacy['presentation']['section_gap'], 'Only the malformed geometry field must fall back to its validated default.' );
update_option( LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION, array() );
remove_theme_mod( 'lunara_journal_archive_title' );
remove_theme_mod( 'lunara_journal_archive_section_gap' );

remove_theme_mod( 'lunara_journal_archive_section_order' );
$absent_order = lunara_journal_archive_studio_get_public_config( false )['section_order'];
lunara_test_assert( $defaults['section_order'] === $absent_order, 'An absent legacy owner must expand to the canonical seven-lane first-paint sequence.' );
set_theme_mod( 'lunara_journal_archive_section_order', 'hero,filters,grid,pagination' );
$legacy_order = lunara_journal_archive_studio_get_public_config( false )['section_order'];
lunara_test_assert( $defaults['section_order'] === $legacy_order, 'The legacy four-lane owner must migrate to the canonical visual and DOM sequence without a write.' );

$critical_css = lunara_journal_archive_critical_css();
lunara_test_assert( false !== strpos( $critical_css, 'order:initial!important' ), 'The first-paint seed must neutralize stale numeric order rules so DOM order stays canonical.' );
lunara_test_assert( 0 === preg_match( '/(?<![a-z-])order:[1-9]/', $critical_css ), 'The first-paint seed must never become a second numeric section-order owner.' );
lunara_test_assert( false !== strpos( $critical_css, '.lunara-journal-archive-retention-media' ) && false !== strpos( $critical_css, 'object-position:var(--lunara-retention-focus-x' ), 'Retention media geometry and focal controls must exist before deferred CSS settles.' );
lunara_test_assert( false !== strpos( $critical_css, '@media(max-width:620px)' ), 'The first-paint seed must reserve the true mobile composition.' );

$retention_media_card = $defaults['retention'][0];
$retention_media_card['image_id'] = 101;
$retention_media = lunara_journal_archive_studio_retention_media_markup( $retention_media_card );
lunara_test_assert( false !== strpos( $retention_media, 'Media Library alt 101' ), 'Retention media must preserve the canonical Media Library alt when no placement override exists.' );
$lunara_test_attachment_renderable = false;
lunara_test_assert( '' === lunara_journal_archive_studio_retention_media_markup( $retention_media_card ), 'A stale attachment whose image markup cannot render must degrade to a text card with no media chamber.' );
$missing_derivative_gallery = $defaults['gallery'];
$missing_derivative_gallery['items'] = array(
	array( 'order' => 1, 'attachment_id' => 101, 'alt' => 'Useful alt', 'caption' => '', 'link_url' => '', 'credit' => 'Credit', 'source' => 'Source', 'source_url' => 'https://source.example/missing', 'focal_x' => 50, 'focal_y' => 50 ),
);
lunara_test_assert( '' === lunara_journal_archive_studio_render_gallery( $missing_derivative_gallery ), 'Missing gallery derivatives must emit no blank fixed-ratio chamber or wrapper.' );
lunara_test_assert( '' === lunara_journal_archive_studio_compose_retention_lane( '', lunara_journal_archive_studio_render_gallery( $missing_derivative_gallery ), true ), 'A gallery-only lane whose final image cannot render must emit no outer padded retention shell.' );
$lunara_test_attachment_renderable = true;

$preview_geometry = $defaults;
$preview_geometry['presentation'] = array(
	'density'          => 'showcase',
	'lead_prominence'  => 'feature',
	'desk_rhythm'      => 'immersive',
	'section_gap'      => 72,
	'hero_min_height'  => 360,
	'card_min_height'  => 500,
	'media_min_height' => 300,
);
$geometry_token = lunara_journal_archive_studio_store_preview( $preview_geometry );
$_GET['lunara_journal_preview'] = $geometry_token;
$preview_resolved = lunara_journal_archive_studio_get_public_config();
$preview_variables = lunara_journal_archive_variable_css( $preview_resolved );
lunara_test_assert( false !== strpos( $preview_variables, '--lunara-journal-archive-grid-gap:30px' ), 'Unsaved preview density must drive its first-paint grid geometry.' );
lunara_test_assert( false !== strpos( $preview_variables, '--lunara-journal-archive-hero-min:389px' ), 'Unsaved preview rhythm and hero height must drive its first-paint variables.' );
lunara_test_assert( false !== strpos( $preview_variables, '--lunara-journal-archive-card-min:500px' ) && false !== strpos( $preview_variables, '--lunara-journal-archive-lead-media-min:310px' ), 'Unsaved preview card and lead presentation must differ from saved owners without a write.' );
unset( $_GET['lunara_journal_preview'] );

$valid = $defaults;
$valid['title']                     = 'A sharper Journal';
$valid['item_count']                = 12;
$valid['lead_mode']                 = 'manual';
$valid['lead_id']                   = 11;
$valid['lane_mode']                 = 'curated';
$valid['curated_ids']               = array( 12, 11 );
$valid['retention'][0]['image_id']  = 101;
$valid['retention'][0]['image_credit'] = 'Studio credit';
$valid['retention'][0]['image_source'] = 'Studio source';
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
$validated = lunara_journal_archive_studio_validate_config( $valid );
lunara_test_assert( ! is_wp_error( $validated ), 'A complete published configuration must validate.' );
lunara_test_assert( array( 12, 11 ) === $validated['curated_ids'], 'Curated order must survive validation.' );
lunara_test_assert( 101 === $validated['retention'][0]['image_id'], 'Validated attachment IDs must survive.' );
lunara_test_assert( array( 102, 101, 104 ) === array_column( $validated['gallery']['items'], 'attachment_id' ), 'Archive gallery attachment order must round-trip exactly.' );
$gallery_markup = lunara_journal_archive_studio_render_gallery( $validated['gallery'] );
lunara_test_assert( false !== strpos( $gallery_markup, 'lunara-journal-archive-gallery' ) && false !== strpos( $gallery_markup, 'Three frames from the desk' ), 'A populated archive gallery must render its bounded SSR wrapper and heading.' );
lunara_test_assert( strpos( $gallery_markup, 'image-102.jpg' ) < strpos( $gallery_markup, 'image-101.jpg' ) && strpos( $gallery_markup, 'image-101.jpg' ) < strpos( $gallery_markup, 'image-104.jpg' ), 'Gallery SSR must follow the editor-defined order.' );
lunara_test_assert( false !== strpos( $gallery_markup, 'First caption' ) && false !== strpos( $gallery_markup, 'First credit' ) && false !== strpos( $gallery_markup, 'https://source.example/first' ) && false !== strpos( $gallery_markup, 'https://example.test/first' ), 'Gallery SSR must preserve caption, credit, provenance, and optional destination.' );
lunara_test_assert( false !== strpos( $gallery_markup, '--lunara-gallery-focus-x:31%;--lunara-gallery-focus-y:67%' ), 'Gallery SSR must preserve per-image focal position without public JavaScript.' );
lunara_test_assert( false !== strpos( $gallery_markup, 'width="1920" height="1080"' ) && false !== strpos( $gallery_markup, 'srcset=') && false !== strpos( $gallery_markup, 'sizes=' ), 'Gallery SSR must use native responsive attachment markup with intrinsic dimensions.' );
lunara_test_assert( 0 === preg_match( '/<a[^>]*>[^<]*(?:<(?!\/a)[^>]+>[^<]*)*<a\b/s', $gallery_markup ), 'Gallery destination and source links must never be nested.' );

$invalid_count = $valid;
$invalid_count['item_count'] = 25;
lunara_test_assert( is_wp_error( lunara_journal_archive_studio_validate_config( $invalid_count ) ), 'Out-of-range item count must fail without clamping public state.' );

$invalid_lead = $valid;
$invalid_lead['lead_id'] = 13;
lunara_test_assert( is_wp_error( lunara_journal_archive_studio_validate_config( $invalid_lead ) ), 'A draft Journal cannot become the public lead.' );
$cleared_lead = $valid;
$cleared_lead['lead_mode'] = 'automatic';
lunara_test_assert( 0 === lunara_journal_archive_studio_validate_config( $cleared_lead )['lead_id'], 'Leaving manual mode must clear the stale hidden lead ID and restore automatic behavior.' );

$duplicate_curated = $valid;
$duplicate_curated['curated_ids'] = array( 11, 12, 11 );
lunara_test_assert( is_wp_error( lunara_journal_archive_studio_validate_config( $duplicate_curated ) ), 'Duplicate curated IDs must fail rather than duplicate cards.' );
$cleared_curation = $valid;
$cleared_curation['lane_mode'] = 'query';
$cleared_curation['curated_ids'] = array( 11, 13, 11 );
lunara_test_assert( array() === lunara_journal_archive_studio_validate_config( $cleared_curation )['curated_ids'], 'Returning to automatic query mode must clear stale hidden curated IDs.' );

$invalid_image = $valid;
$invalid_image['retention'][0]['image_id'] = 103;
lunara_test_assert( is_wp_error( lunara_journal_archive_studio_validate_config( $invalid_image ) ), 'Non-image attachments must fail validation.' );

$missing_retention_provenance = $valid;
$missing_retention_provenance['retention'][0]['image_source'] = '';
lunara_test_assert( 'journal_archive_retention_image_provenance_required' === lunara_journal_archive_studio_validate_config( $missing_retention_provenance )->get_error_code(), 'A retention image without required provenance must fail before public replacement.' );
$unsafe_retention_source = $valid;
$unsafe_retention_source['retention'][0]['image_source_url'] = 'javascript:alert(1)';
lunara_test_assert( 'journal_archive_retention_image_source_invalid' === lunara_journal_archive_studio_validate_config( $unsafe_retention_source )->get_error_code(), 'Unsafe retention source URLs must fail instead of being silently sanitized.' );

$duplicate_gallery = $valid;
$duplicate_gallery['gallery']['items'][2]['attachment_id'] = 102;
lunara_test_assert( 'journal_archive_gallery_duplicate' === lunara_journal_archive_studio_validate_config( $duplicate_gallery )->get_error_code(), 'Duplicate archive gallery attachments must fail validation.' );
$non_image_gallery = $valid;
$non_image_gallery['gallery']['items'][1]['attachment_id'] = 103;
lunara_test_assert( 'journal_archive_gallery_image_invalid' === lunara_journal_archive_studio_validate_config( $non_image_gallery )->get_error_code(), 'Non-image archive gallery attachments must fail validation.' );
$orphaned_gallery_image = $valid;
$orphaned_gallery_image['gallery']['items'][1]['attachment_id'] = 105;
lunara_test_assert( 'journal_archive_gallery_image_invalid' === lunara_journal_archive_studio_validate_config( $orphaned_gallery_image )->get_error_code(), 'An image MIME record without an attachment post must fail validation.' );
$wrong_type_gallery_image = $valid;
$wrong_type_gallery_image['gallery']['items'][1]['attachment_id'] = 11;
lunara_test_assert( 'journal_archive_gallery_image_invalid' === lunara_journal_archive_studio_validate_config( $wrong_type_gallery_image )->get_error_code(), 'A non-attachment post with an image MIME record must fail validation.' );
$missing_gallery_provenance = $valid;
$missing_gallery_provenance['gallery']['items'][1]['source'] = '';
lunara_test_assert( 'journal_archive_gallery_provenance_required' === lunara_journal_archive_studio_validate_config( $missing_gallery_provenance )->get_error_code(), 'Every gallery image must keep a source name and secure source URL.' );
$missing_gallery_alt = $valid;
$missing_gallery_alt['gallery']['items'][1]['alt'] = '';
lunara_test_assert( 'journal_archive_gallery_provenance_required' === lunara_journal_archive_studio_validate_config( $missing_gallery_alt )->get_error_code(), 'Every archive gallery image must have useful placement alt text.' );
$missing_gallery_credit = $valid;
$missing_gallery_credit['gallery']['items'][1]['credit'] = '';
lunara_test_assert( 'journal_archive_gallery_provenance_required' === lunara_journal_archive_studio_validate_config( $missing_gallery_credit )->get_error_code(), 'Every archive gallery image must retain its credit.' );
$unsafe_gallery_source = $valid;
$unsafe_gallery_source['gallery']['items'][1]['source_url'] = 'http://unsafe.example/image';
lunara_test_assert( 'journal_archive_gallery_source_invalid' === lunara_journal_archive_studio_validate_config( $unsafe_gallery_source )->get_error_code(), 'Unsafe gallery provenance URLs must fail without replacing public state.' );
$unsafe_gallery_link = $valid;
$unsafe_gallery_link['gallery']['items'][1]['link_url'] = 'javascript:alert(1)';
lunara_test_assert( 'journal_archive_gallery_link_invalid' === lunara_journal_archive_studio_validate_config( $unsafe_gallery_link )->get_error_code(), 'Unsafe optional gallery destinations must fail validation.' );
$oversized_gallery = $valid;
for ( $gallery_index = 4; $gallery_index <= 13; $gallery_index++ ) {
	$oversized_gallery['gallery']['items'][] = array( 'order' => $gallery_index, 'attachment_id' => 100 + $gallery_index, 'alt' => '', 'caption' => '', 'link_url' => '', 'credit' => '', 'source' => 'Source', 'source_url' => 'https://source.example/' . $gallery_index, 'focal_x' => 50, 'focal_y' => 50 );
}
lunara_test_assert( 'journal_archive_gallery_count_invalid' === lunara_journal_archive_studio_validate_config( $oversized_gallery )->get_error_code(), 'Archive gallery capacity must remain bounded to twelve images.' );

$invalid_order = $valid;
$invalid_order['section_order'] = array( 'hero', 'grid', 'grid' );
lunara_test_assert( is_wp_error( lunara_journal_archive_studio_validate_config( $invalid_order ) ), 'Incomplete or duplicate lane order must fail validation.' );

$duplicate_positions_request = array(
	'lunara_journal_archive_identity' => array( 'kicker' => 'Journal', 'title' => 'Lunara Journal', 'deck' => '', 'supporting_copy' => '' ),
	'lunara_journal_archive_item_count' => 8,
	'lunara_journal_archive_section_visibility' => array_fill_keys( $defaults['section_order'], 1 ),
	'lunara_journal_archive_section_positions' => array_fill_keys( $defaults['section_order'], 1 ),
);
$duplicate_positions_candidate = lunara_journal_archive_studio_config_from_request( $duplicate_positions_request );
lunara_test_assert( is_wp_error( lunara_journal_archive_studio_validate_config( $duplicate_positions_candidate ) ), 'Duplicate Section Composer positions must fail before any public mutation.' );

$gallery_request = array(
	'lunara_journal_archive_identity' => array( 'kicker' => 'Journal', 'title' => 'Lunara Journal', 'deck' => '', 'supporting_copy' => '' ),
	'lunara_journal_archive_item_count' => 8,
	'lunara_journal_archive_section_visibility' => array_fill_keys( $defaults['section_order'], 1 ),
	'lunara_journal_archive_section_positions' => array_combine( $defaults['section_order'], range( 1, count( $defaults['section_order'] ) ) ),
	'lunara_journal_archive_gallery' => array( 'kicker' => 'Gallery kicker', 'title' => 'Gallery title', 'copy' => 'Gallery copy' ),
	'lunara_journal_archive_gallery_ids' => '104,102,101',
	'lunara_journal_archive_gallery_alt' => array( 104 => 'Third image', 102 => 'First image', 101 => 'Second image' ),
	'lunara_journal_archive_gallery_caption' => array( 104 => 'Third caption', 102 => 'First caption', 101 => 'Second caption' ),
	'lunara_journal_archive_gallery_link_url' => array( 104 => '', 102 => 'https://example.test/first', 101 => '' ),
	'lunara_journal_archive_gallery_credit' => array( 104 => 'Third credit', 102 => 'First credit', 101 => 'Second credit' ),
	'lunara_journal_archive_gallery_source' => array( 104 => 'Third source', 102 => 'First source', 101 => 'Second source' ),
	'lunara_journal_archive_gallery_source_url' => array( 104 => 'https://source.example/third', 102 => 'https://source.example/first', 101 => 'https://source.example/second' ),
	'lunara_journal_archive_gallery_focal_x' => array( 104 => 72, 102 => 31, 101 => 50 ),
	'lunara_journal_archive_gallery_focal_y' => array( 104 => 40, 102 => 67, 101 => 50 ),
);
$gallery_candidate = lunara_journal_archive_studio_config_from_request( $gallery_request );
lunara_test_assert( array( 104, 102, 101 ) === array_column( $gallery_candidate['gallery']['items'], 'attachment_id' ), 'Add/reorder request adaptation must preserve the exact editor-defined gallery sequence.' );
lunara_test_assert( 'First caption' === $gallery_candidate['gallery']['items'][1]['caption'] && 31 === absint( $gallery_candidate['gallery']['items'][1]['focal_x'] ), 'Replaceable gallery rows must round-trip all per-image fields from the focused form.' );
lunara_test_assert( ! is_wp_error( lunara_journal_archive_studio_validate_config( $gallery_candidate ) ), 'A complete three-image gallery request must validate.' );
$gallery_request['lunara_journal_archive_gallery_ids'] = '104,101';
$removed_gallery_candidate = lunara_journal_archive_studio_config_from_request( $gallery_request );
lunara_test_assert( array( 104, 101 ) === array_column( $removed_gallery_candidate['gallery']['items'], 'attachment_id' ), 'Removing one image must produce the exact remaining public order.' );
$gallery_request['lunara_journal_archive_gallery_ids'] = '';
$cleared_gallery_candidate = lunara_journal_archive_studio_config_from_request( $gallery_request );
lunara_test_assert( array() === $cleared_gallery_candidate['gallery']['items'] && ! is_wp_error( lunara_journal_archive_studio_validate_config( $cleared_gallery_candidate ) ), 'Clear gallery must validate as an empty zero-output state.' );

$public_before = lunara_journal_archive_studio_get_public_config( false );
lunara_test_assert( 'Lunara Journal' === $public_before['title'], 'Built-in fallback must represent current public identity.' );
$state_before_invalid = serialize( array( $lunara_test_theme_mods, $lunara_test_options, $lunara_test_posts ) );
$invalid_promotion = lunara_journal_archive_studio_promote_config( $invalid_lead, 'save' );
lunara_test_assert( is_wp_error( $invalid_promotion ), 'Invalid promotion must return its validation error.' );
lunara_test_assert( $state_before_invalid === serialize( array( $lunara_test_theme_mods, $lunara_test_options, $lunara_test_posts ) ), 'Invalid promotion must leave public state and post records byte-for-byte unchanged.' );
$state_before_invalid_gallery = serialize( array( $lunara_test_theme_mods, $lunara_test_options, $lunara_test_posts ) );
$invalid_gallery_promotion = lunara_journal_archive_studio_promote_config( $unsafe_gallery_source, 'save' );
lunara_test_assert( is_wp_error( $invalid_gallery_promotion ) && $state_before_invalid_gallery === serialize( array( $lunara_test_theme_mods, $lunara_test_options, $lunara_test_posts ) ), 'Invalid gallery provenance must leave the last-valid public configuration and all post metadata byte-for-byte unchanged.' );

$rejected_candidate = $defaults;
$rejected_candidate['title'] = '';
$rejected_candidate['supporting_copy'] = 'Do not make me retype this private edit.';
$rejected_positions = array_fill_keys( $defaults['section_order'], 2 );
lunara_journal_archive_studio_store_invalid_stage(
	$rejected_candidate,
	array( 'lunara_journal_archive_section_positions' => $rejected_positions ),
	'journal_archive_identity_required'
);
$private_stage = lunara_journal_archive_studio_get_invalid_stage();
lunara_test_assert( is_array( $private_stage ) && 'Do not make me retype this private edit.' === $private_stage['supporting_copy'], 'Rejected edits must return as a bounded private per-user draft instead of disappearing.' );
lunara_test_assert( 2 === $private_stage['_staged_positions']['hero'] && 2 === $private_stage['_staged_positions']['grid'], 'Rejected duplicate section positions must remain visible for correction.' );
lunara_test_assert( 'Add both the archive kicker and headline.' === lunara_journal_archive_studio_validation_message(), 'Invalid save feedback must resolve through the bounded validator-message allowlist.' );
lunara_journal_archive_studio_clear_invalid_stage();
lunara_test_assert( false === lunara_journal_archive_studio_get_invalid_stage(), 'A successful transition must be able to clear the rejected private draft.' );

$post_records_before = serialize( $lunara_test_posts );
$promotion = lunara_journal_archive_studio_promote_config( $validated, 'save' );
lunara_test_assert( ! is_wp_error( $promotion ), 'Valid configuration must promote.' );
$public_after = lunara_journal_archive_studio_get_public_config( false );
lunara_test_assert( 'A sharper Journal' === $public_after['title'], 'Valid configuration must become public.' );
lunara_test_assert( 'manual' === $public_after['lead_mode'] && 11 === $public_after['lead_id'], 'Manual archive-only lead must round-trip.' );
lunara_test_assert( in_array( 'retention_image_wide_quality', $public_after['_warnings'], true ) && in_array( 'gallery_image_wide_quality', $public_after['_warnings'], true ), 'Low-resolution and portrait media must remain valid while surfacing an accurate wide-image quality warning.' );
lunara_test_assert( $post_records_before === serialize( $lunara_test_posts ), 'Promotion must not change Journal status, publication dates, GMT dates, or scheduling metadata.' );

set_theme_mod( 'lunara_journal_archive_kicker', 'The Lunara Journal' );
set_theme_mod( 'lunara_journal_archive_title', 'Journal' );
$verbatim_owner_config = lunara_journal_archive_studio_get_public_config( false );
lunara_test_assert( 'The Lunara Journal' === $verbatim_owner_config['kicker'] && 'Journal' === $verbatim_owner_config['title'], 'Explicit Customizer-owned identity must round-trip verbatim even when it matches old fallback words.' );
set_theme_mod( 'lunara_journal_archive_kicker', $public_after['kicker'] );
set_theme_mod( 'lunara_journal_archive_title', $public_after['title'] );

$lunara_test_posts[11]->post_status = 'draft';
unset( $lunara_test_images[101] );
$drifted_references = lunara_journal_archive_studio_get_public_config( false );
lunara_test_assert( 'A sharper Journal' === $drifted_references['title'] && 12 === $drifted_references['item_count'], 'Reference drift must preserve every unrelated last-valid editorial field.' );
lunara_test_assert( 'shared' === $drifted_references['lead_mode'] && 0 === $drifted_references['lead_id'], 'An unpublished manual lead must fall back locally to the shared homepage lead before newest-only fallback.' );
lunara_test_assert( array( 12 ) === $drifted_references['curated_ids'], 'An unpublished curated story must be removed locally without discarding the lane.' );
lunara_test_assert( 0 === $drifted_references['retention'][0]['image_id'], 'A deleted retention attachment must fall back locally to text.' );
lunara_test_assert( array( 102, 104 ) === array_column( $drifted_references['gallery']['items'], 'attachment_id' ), 'A deleted gallery reference must be removed locally without resetting the remaining ordered gallery.' );
lunara_test_assert( in_array( 'manual_lead_unavailable', $drifted_references['_warnings'], true ) && in_array( 'retention_image_unavailable', $drifted_references['_warnings'], true ) && in_array( 'gallery_image_unavailable', $drifted_references['_warnings'], true ), 'Field-local degradation must surface validator warnings.' );
$lunara_test_posts[11]->post_status = 'publish';
$lunara_test_images[101] = 'image/jpeg';

$page_one = new WP_Query();
$page_one->paged = 1;
$page_one->set( 'paged', 1 );
lunara_journal_archive_studio_configure_query( $page_one );
lunara_test_assert( 12 === $page_one->get( 'posts_per_page' ), 'Journal query must receive the Studio item count only.' );
lunara_test_assert( array( 11, 12 ) === $page_one->get( 'lunara_journal_archive_priority_ids' ), 'Lead and curated IDs must be unique and deterministically prioritized.' );

$page_two = new WP_Query();
$page_two->paged = 2;
$page_two->set( 'paged', 2 );
lunara_journal_archive_studio_configure_query( $page_two );
lunara_test_assert( array( 11, 12 ) === $page_two->get( 'lunara_journal_archive_priority_ids' ), 'Every archive page must use the same SQL ordering so a pin cannot reappear across page boundaries.' );

foreach ( array( 'date_asc', 'modified_desc' ) as $alternate_sort ) {
	$lunara_test_sort = $alternate_sort;
	$alternate_query = new WP_Query();
	lunara_journal_archive_studio_configure_query( $alternate_query );
	lunara_test_assert( array( 11, 12 ) === $alternate_query->get( 'lunara_journal_archive_priority_ids' ), 'Manual lead and curated priority must remain true for ' . $alternate_sort . ' while its selected order stays secondary.' );
}
$lunara_test_sort = 'date_desc';

$saved_query_option = get_option( LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION );
$automatic_option = $saved_query_option;
$automatic_option['lead_mode'] = 'automatic';
$automatic_option['lead_id'] = 0;
$automatic_option['lane_mode'] = 'curated';
$automatic_option['curated_ids'] = array( 12, 11 );
update_option( LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION, $automatic_option );
foreach ( array( 'date_asc', 'modified_desc' ) as $alternate_sort ) {
	$lunara_test_sort = $alternate_sort;
	$automatic_query = new WP_Query();
	lunara_journal_archive_studio_configure_query( $automatic_query );
	lunara_test_assert( array( 11, 12 ) === $automatic_query->get( 'lunara_journal_archive_priority_ids' ), 'Automatic newest must stay first and dedupe curated IDs for ' . $alternate_sort . '.' );
}
lunara_test_assert( ! empty( $lunara_test_get_posts_args ) && 1 === $lunara_test_get_posts_args[0]['posts_per_page'] && 'ids' === $lunara_test_get_posts_args[0]['fields'], 'Automatic newest must use one bounded ID-only published-Journal lookup.' );

$shared_option = $automatic_option;
$shared_option['lead_mode'] = 'shared';
$shared_option['lane_mode'] = 'query';
$shared_option['curated_ids'] = array();
update_option( LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION, $shared_option );
$lunara_test_shared_lead_id = 13;
$shared_fallback_query = new WP_Query();
lunara_journal_archive_studio_configure_query( $shared_fallback_query );
lunara_test_assert( array( 11 ) === $shared_fallback_query->get( 'lunara_journal_archive_priority_ids' ), 'An unavailable shared lead must fall through to the newest eligible published Journal file.' );
$lunara_test_shared_lead_id = 11;
update_option( LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION, $saved_query_option );
$lunara_test_sort = 'date_desc';

$taxonomy = new WP_Query();
$taxonomy->journal_archive = false;
$taxonomy->journal_tax = true;
lunara_journal_archive_studio_configure_query( $taxonomy );
lunara_test_assert( 12 === $taxonomy->get( 'posts_per_page' ), 'Journal taxonomy routes must share the bounded item count.' );
lunara_test_assert( null === $taxonomy->get( 'lunara_journal_archive_priority_ids' ), 'Taxonomy routes must preserve term ordering and never inject archive curation.' );

$unrelated = new WP_Query();
$unrelated->journal_archive = false;
lunara_journal_archive_studio_configure_query( $unrelated );
lunara_test_assert( null === $unrelated->get( 'posts_per_page' ), 'Unrelated queries must remain untouched.' );

$posts_before_picker_test = $lunara_test_posts;
for ( $picker_index = 200; $picker_index < 230; $picker_index++ ) {
	$lunara_test_posts[ $picker_index ] = (object) array(
		'ID'            => $picker_index,
		'post_type'     => 'journal',
		'post_status'   => 'publish',
		'post_title'    => 'Deep archive file ' . $picker_index,
		'post_date'     => '2026-09-' . sprintf( '%02d', 1 + ( $picker_index - 200 ) ) . ' 10:00:00',
		'post_date_gmt' => '2026-09-' . sprintf( '%02d', 1 + ( $picker_index - 200 ) ) . ' 15:00:00',
	);
}
$lunara_test_posts[230] = (object) array( 'ID' => 230, 'post_type' => 'journal', 'post_status' => 'draft', 'post_title' => 'Deep archive hidden draft', 'post_date' => '2026-10-01 10:00:00', 'post_date_gmt' => '0000-00-00 00:00:00' );
$picker_config = $defaults;
$picker_config['lead_mode'] = 'manual';
$picker_config['lead_id'] = 11;
$picker_config['lane_mode'] = 'curated';
$picker_config['curated_ids'] = array( 12, 13 );
$lunara_test_get_posts_args = array();
$initial_picker_posts = lunara_journal_archive_studio_editor_posts( $picker_config );
$initial_picker_ids = array_map( static function ( $post ) { return $post->ID; }, $initial_picker_posts );
$initial_picker_args = $lunara_test_get_posts_args[0];
lunara_test_assert( 20 === $initial_picker_args['posts_per_page'] && ! isset( $initial_picker_args['fields'] ), 'The initial Studio picker must request at most twenty WP_Post objects instead of loading the archive.' );
lunara_test_assert( false === $initial_picker_args['update_post_meta_cache'] && false === $initial_picker_args['update_post_term_cache'] && false === $initial_picker_args['cache_results'], 'The bounded private picker must not warm unrelated post, meta, or taxonomy caches.' );
lunara_test_assert( count( $initial_picker_posts ) <= 22 && in_array( 11, $initial_picker_ids, true ) && in_array( 12, $initial_picker_ids, true ), 'Initial choices may add only the configured eligible lead/curated files beyond the twenty recent choices.' );
lunara_test_assert( ! in_array( 13, $initial_picker_ids, true ) && ! in_array( 230, $initial_picker_ids, true ), 'Draft Journal files must remain ineligible even when configured or searchable.' );
lunara_test_assert( 2 === count( $lunara_test_get_posts_args ) && array( 11, 12, 13 ) === $lunara_test_get_posts_args[1]['post__in'] && 3 === $lunara_test_get_posts_args[1]['posts_per_page'], 'Configured older choices must be hydrated in one bounded batch query.' );

$lunara_test_get_posts_args = array();
$older_results = lunara_journal_archive_studio_search_posts( 'Deep archive file 205', 500 );
$older_ids = array_map( static function ( $post ) { return $post->ID; }, $older_results );
lunara_test_assert( array( 205 ) === $older_ids, 'Authenticated server-side search must make an older published Journal file reachable without an unbounded initial payload.' );
lunara_test_assert( 20 === $lunara_test_get_posts_args[0]['posts_per_page'] && 'Deep archive file 205' === $lunara_test_get_posts_args[0]['s'], 'Server-side search must clamp result count to twenty and pass a bounded title query.' );
$lunara_test_get_posts_args = array();
$numeric_results = lunara_journal_archive_studio_search_posts( '12', 20 );
lunara_test_assert( array( 12 ) === array_map( static function ( $post ) { return $post->ID; }, $numeric_results ) && array( 12 ) === $lunara_test_get_posts_args[0]['post__in'], 'Numeric search must use one exact published-Journal ID lookup.' );
$lunara_test_get_posts_args = array();
lunara_journal_archive_studio_search_posts( str_repeat( 'x', 140 ), 20 );
lunara_test_assert( 100 === strlen( $lunara_test_get_posts_args[0]['s'] ), 'Server-side search terms must be bounded to one hundred characters.' );

$_GET['q'] = 'Deep archive file 205';
$lunara_test_can_edit = false;
$lunara_test_ajax_nonce_checks = 0;
try {
	lunara_journal_archive_studio_ajax_search_posts();
	lunara_test_assert( false, 'Unauthorized Journal search must stop with a JSON denial.' );
} catch ( Lunara_Test_JSON_Response $response ) {
	lunara_test_assert( false === $response->success && 403 === $response->status && 0 === $lunara_test_ajax_nonce_checks, 'Unauthorized Journal search must fail capability before querying or checking a token.' );
}
$lunara_test_can_edit = true;
$lunara_test_ajax_nonce_valid = false;
try {
	lunara_journal_archive_studio_ajax_search_posts();
	lunara_test_assert( false, 'Invalid Journal search nonce must be denied.' );
} catch ( RuntimeException $response ) {
	lunara_test_assert( 'Invalid AJAX nonce.' === $response->getMessage(), 'Journal search must use its dedicated AJAX nonce gate.' );
}
$lunara_test_ajax_nonce_valid = true;
$lunara_test_title_args = array();
try {
	lunara_journal_archive_studio_ajax_search_posts();
	lunara_test_assert( false, 'Authorized Journal search must terminate through a bounded JSON response.' );
} catch ( Lunara_Test_JSON_Response $response ) {
	lunara_test_assert( true === $response->success && 200 === $response->status && 1 === count( $response->data['items'] ) && 205 === $response->data['items'][0]['id'], 'Authorized Journal search must return only the matching eligible published file.' );
	lunara_test_assert( 1 === count( $lunara_test_title_args ) && $lunara_test_title_args[0] instanceof WP_Post, 'AJAX labels must reuse the bounded query WP_Post object instead of triggering per-ID title reads.' );
}
$_GET['q'] = 'x';
try {
	lunara_journal_archive_studio_ajax_search_posts();
	lunara_test_assert( false, 'A too-short Journal search must still return a bounded JSON response.' );
} catch ( Lunara_Test_JSON_Response $response ) {
	lunara_test_assert( true === $response->success && array() === $response->data['items'], 'Non-numeric Journal searches shorter than two characters must not query the archive.' );
}
unset( $_GET['q'] );
$lunara_test_posts = $posts_before_picker_test;

$wpdb = (object) array( 'posts' => 'wp_posts' );
$orderby = lunara_journal_archive_studio_priority_orderby( 'wp_posts.post_date DESC', $page_one );
lunara_test_assert( 1 === substr_count( $orderby, 'wp_posts.ID = 11' ), 'Lead ID must occur once in priority SQL.' );
lunara_test_assert( 1 === substr_count( $orderby, 'wp_posts.ID = 12' ), 'Curated ID must occur once in priority SQL.' );
lunara_test_assert( false !== strpos( $orderby, 'wp_posts.post_date DESC' ), 'Priority SQL must retain the requested secondary order.' );
lunara_test_assert( 1 === substr_count( $orderby, 'wp_posts.ID DESC' ), 'Newest sorting must add one deterministic descending ID tie-breaker for equal timestamps.' );
$oldest_orderby = lunara_journal_archive_studio_priority_orderby( 'wp_posts.post_date ASC', $page_one );
lunara_test_assert( false !== strpos( $oldest_orderby, 'wp_posts.post_date ASC, wp_posts.ID ASC' ), 'Oldest sorting must retain its direction and add a matching stable ID tie-breaker.' );
$updated_orderby = lunara_journal_archive_studio_priority_orderby( 'wp_posts.post_modified DESC', $page_one );
lunara_test_assert( false !== strpos( $updated_orderby, 'wp_posts.post_modified DESC, wp_posts.ID DESC' ), 'Recently Updated must remain priority-first and deterministic for tied modified dates.' );
$already_stable_orderby = lunara_journal_archive_studio_priority_orderby( 'wp_posts.post_date DESC, wp_posts.ID DESC', $page_one );
lunara_test_assert( 1 === substr_count( $already_stable_orderby, 'wp_posts.ID DESC' ), 'An existing ID tie-breaker must never be duplicated.' );

$revisions = lunara_journal_archive_studio_get_revisions();
lunara_test_assert( 1 === count( $revisions ), 'Successful promotion must capture exactly one prior-public revision.' );
lunara_test_assert( isset( $revisions[0]['id'], $revisions[0]['saved_at'], $revisions[0]['saved_by'], $revisions[0]['action'], $revisions[0]['validator_result'], $revisions[0]['prior_public'] ), 'Revision audit metadata must be complete.' );
lunara_test_assert( 'passed' === $revisions[0]['validator_result'] && true === $revisions[0]['prior_public'], 'Revision must record that the promoted replacement passed and snapshot was previously public.' );
$restore_id = $revisions[0]['id'];
$post_records_before_restore = serialize( $lunara_test_posts );
$restored = lunara_journal_archive_studio_restore_revision( $restore_id );
lunara_test_assert( ! is_wp_error( $restored ), 'A valid prior-public revision must restore.' );
lunara_test_assert( 'Lunara Journal' === lunara_journal_archive_studio_get_public_config( false )['title'], 'Restore must reinstate the prior public configuration.' );
lunara_test_assert( $post_records_before_restore === serialize( $lunara_test_posts ), 'Restore must not change Journal publication metadata.' );
$restore_revisions = lunara_journal_archive_studio_get_revisions();
lunara_test_assert( 'restore' === $restore_revisions[0]['action'] && 'passed' === $restore_revisions[0]['validator_result'] && true === $restore_revisions[0]['prior_public'], 'Restore must create a complete newest audit row.' );
lunara_test_assert( 'A sharper Journal' === $restore_revisions[0]['config']['title'], 'Restore history must snapshot the public configuration it replaced.' );

for ( $i = 0; $i < 20; $i++ ) {
	lunara_journal_archive_studio_push_revision( $defaults, 'save' );
}
lunara_test_assert( 12 === count( lunara_journal_archive_studio_get_revisions() ), 'Revision history must remain bounded to twelve snapshots.' );

lunara_journal_archive_studio_flush_route_cache();
lunara_test_assert( array( 'journal_archive_studio_public', 'lunara' ) === $lunara_test_cache_deletes[0], 'Only the Journal Studio cache key should be invalidated.' );
$invalidation = end( $lunara_test_actions_fired );
lunara_test_assert( 'lunara_journal_archive_studio_invalidate_routes' === $invalidation[0], 'Targeted route invalidation must be explicitly dispatched.' );
lunara_test_assert( array( '/journal/', '/journal_section/', '/journal_topic/', '/journal_type/' ) === $invalidation[1], 'Only Journal archive and taxonomy route families may be invalidated.' );
lunara_test_assert( 4 === count( $lunara_test_rocket_urls ), 'Bounded cache cleaner must receive the archive plus three actual taxonomy URLs.' );
lunara_test_assert( 'https://example.test/journal/' === $lunara_test_rocket_urls[0], 'Bounded cache cleaner must include the canonical archive URL.' );

$preview_token = lunara_journal_archive_studio_store_preview( $validated );
lunara_test_assert( is_string( $preview_token ) && '' !== $preview_token, 'Valid unsaved configuration must receive a private preview token.' );
lunara_test_assert( 'A sharper Journal' === lunara_journal_archive_studio_get_preview_config( $preview_token )['title'], 'Authorized owner must retrieve the unsaved preview.' );
$nocache_before = $lunara_test_nocache_calls;
$normal_response = lunara_journal_archive_studio_prepare_preview_response();
lunara_test_assert( false === $normal_response['handled'] && true === $normal_response['authorized'] && 200 === $normal_response['status'] && $lunara_test_nocache_calls === $nocache_before, 'A normal /journal/ request must remain public/cacheable and never receive preview denial headers.' );
$_GET['lunara_journal_preview'] = $preview_token;
$nocache_before = $lunara_test_nocache_calls;
$preview_response = lunara_journal_archive_studio_prepare_preview_response();
lunara_test_assert( true === $preview_response['authorized'] && 200 === $preview_response['status'], 'The owner with capability and a live token must receive the private Journal preview response.' );
lunara_test_assert( $lunara_test_nocache_calls === $nocache_before + 1, 'Every preview-query response must become no-store before access validation.' );
$lunara_test_user_id = 8;
lunara_test_assert( false === lunara_journal_archive_studio_get_preview_config( $preview_token ), 'A different logged-in user must not retrieve the preview.' );
$nocache_before = $lunara_test_nocache_calls;
$foreign_response = lunara_journal_archive_studio_prepare_preview_response();
lunara_test_assert( false === $foreign_response['authorized'] && 403 === $foreign_response['status'] && $lunara_test_nocache_calls === $nocache_before + 1, 'A foreign-user token URL must be denied as a non-cacheable 403 response.' );
$lunara_test_user_id = 7;
$lunara_test_can_edit = false;
lunara_test_assert( false === lunara_journal_archive_studio_get_preview_config( $preview_token ), 'A user without theme-edit capability must not retrieve the preview.' );
$anonymous_response = lunara_journal_archive_studio_prepare_preview_response();
lunara_test_assert( false === $anonymous_response['authorized'] && 403 === $anonymous_response['status'], 'An anonymous or unauthorized preview request must be denied rather than falling back to public output.' );
$lunara_test_can_edit = true;
lunara_test_assert( false === lunara_journal_archive_studio_get_preview_config( 'wrong-token' ), 'A guessed token must not retrieve the preview.' );
$_GET['lunara_journal_preview'] = 'wrong-token';
$guessed_response = lunara_journal_archive_studio_prepare_preview_response();
lunara_test_assert( false === $guessed_response['authorized'] && 403 === $guessed_response['status'], 'A guessed token URL must receive a no-store 403 response.' );
$_GET['lunara_journal_preview'] = $preview_token;
$lunara_test_now += 1900;
lunara_test_assert( false === lunara_journal_archive_studio_get_preview_config( $preview_token ), 'Preview must expire after its bounded lifetime.' );
$expired_response = lunara_journal_archive_studio_prepare_preview_response();
lunara_test_assert( false === $expired_response['authorized'] && 403 === $expired_response['status'], 'An expired token URL must receive a no-store 403 response.' );
unset( $_GET['lunara_journal_preview'] );

$markup = array(
	'hero'       => '<header><h1>Lunara Journal</h1></header>',
	'deskbar'    => '<div id="deskbar">Desk</div>',
	'filters'    => '<nav id="filters">Filters</nav>',
	'toolbar'    => '<div id="toolbar">Toolbar</div>',
	'grid'       => '<section id="grid">Grid</section>',
	'retention'  => '<section id="retention">Retention</section>',
	'pagination' => '<nav id="pagination">Pages</nav>',
);
$custom_order = array( 'hero', 'toolbar', 'grid', 'deskbar', 'retention', 'filters', 'pagination' );
$visibility = array_fill_keys( $custom_order, true );
$visibility['deskbar'] = false;
$markup['fallback-h1'] = '<h1 class="screen-reader-text">Lunara Journal</h1>';
$rendered = lunara_journal_archive_studio_render_sections( $markup, $custom_order, $visibility );
lunara_test_assert( false === strpos( $rendered, 'id="deskbar"' ), 'Disabled lanes must not render.' );
lunara_test_assert( strpos( $rendered, 'id="toolbar"' ) < strpos( $rendered, 'id="grid"' ) && strpos( $rendered, 'id="grid"' ) < strpos( $rendered, 'id="retention"' ), 'Rendered DOM order must exactly follow the saved order.' );
lunara_test_assert( 1 === substr_count( $rendered, '<h1>' ), 'Server-rendered Journal composition must retain exactly one H1.' );
$visibility['hero'] = false;
$rendered_without_hero = lunara_journal_archive_studio_render_sections( $markup, $custom_order, $visibility );
lunara_test_assert( 1 === substr_count( $rendered_without_hero, '<h1' ), 'Hiding the visual Hero must emit exactly one accessible fallback H1.' );

fwrite( STDOUT, "journal-archive-studio-runtime: all assertions passed.\n" );
