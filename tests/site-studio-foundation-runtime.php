<?php
/**
 * Behavioral contract for the Site Studio 3.2.55 foundation.
 *
 * This deliberately boots the production registry, adapter/service, REST, and
 * Design Token modules against a small WordPress stub. It exercises behavior;
 * source assertions in the PowerShell launcher cover only load boundaries and
 * the removal of the six competing Customizer declarations.
 *
 * @package Lunara_Film
 */

define( 'ABSPATH', __DIR__ . '/' );

set_error_handler(
	static function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);

$lunara_test_actions       = array();
$lunara_test_filters       = array();
$lunara_test_routes        = array();
$lunara_test_options       = array();
$lunara_test_option_writes = array();
$lunara_test_transients    = array();
$lunara_test_events        = array();
$lunara_test_theme_mods    = array();
$lunara_test_logged_in     = true;
$lunara_test_can_edit      = true;
$lunara_test_can_manage    = true;
$lunara_test_nonce_valid   = true;
$lunara_test_user_id       = 41;
$lunara_test_now           = 1900000000;
$lunara_test_uuid          = 0;
$lunara_test_dependency_calls = 0;
$lunara_test_status_calls     = 0;
$lunara_test_factory_calls    = 0;
$lunara_test_preview_delegate_calls = 0;
$lunara_test_update_mode      = 'normal';
$lunara_test_renderer_calls   = array();
$lunara_test_provider_calls   = array();
$lunara_test_provider_defaults = array(
	'reviews' => array(
		'schema_version' => 1, 'kicker' => 'Criticism Desk', 'title' => 'Reviews', 'deck' => 'Review deck', 'supporting_copy' => 'Support',
		'lead_mode' => 'automatic', 'lead_id' => 0, 'lane_mode' => 'query', 'curated_ids' => array( 10, 11 ), 'item_count' => 9,
		'section_order' => array( 'hero', 'grid', 'pagination', 'pairing-desk' ),
		'section_visibility' => array( 'hero' => true, 'grid' => true, 'pagination' => true, 'pairing-desk' => true ),
		'labels' => array( 'toolbar_title' => 'Release timeline', 'sort_label' => 'Sort by', 'pagination_next' => 'Next' ),
		'gallery' => array(
			'kicker' => 'Visual File', 'title' => 'Review images', 'copy' => 'Gallery copy',
			'items' => array( array( 'order' => 1, 'attachment_id' => 51, 'alt' => 'Alt', 'caption' => 'Caption', 'link_url' => '/review/', 'credit' => 'Credit', 'source' => 'Studio', 'source_url' => 'https://example.test/source', 'focal_x' => 50, 'focal_y' => 45 ) ),
		),
		'retention' => array( array( 'visible' => true, 'order' => 1, 'label' => 'Latest', 'destination' => 'latest', 'url' => '', 'image_id' => 52, 'image_alt' => 'Alt', 'image_credit' => 'Credit', 'image_source' => 'Studio', 'image_source_url' => 'https://example.test/source', 'focal_x' => 50, 'focal_y' => 50 ) ),
		'presentation' => array( 'density' => 'editorial', 'lead_prominence' => 'standard', 'rail_density' => 'editorial', 'section_gap' => 40, 'lead_min_height' => 460, 'card_min_height' => 360, 'compact_media_width' => 116 ),
	),
	'journal' => array(
		'schema_version' => 1, 'kicker' => 'Journal', 'title' => 'Journal', 'deck' => 'Journal deck', 'supporting_copy' => 'Support',
		'lead_mode' => 'shared', 'lead_id' => 0, 'lane_mode' => 'query', 'curated_ids' => array( 20, 21 ), 'item_count' => 8,
		'filter_caps' => array( 'journal_section' => 8, 'journal_topic' => 10, 'journal_type' => 8 ),
		'section_order' => array( 'hero', 'deskbar', 'filters', 'toolbar', 'grid', 'retention', 'pagination' ),
		'section_visibility' => array( 'hero' => true, 'deskbar' => true, 'filters' => true, 'toolbar' => true, 'grid' => true, 'retention' => true, 'pagination' => true ),
		'labels' => array( 'toolbar_title' => 'Latest files', 'card_cta' => 'Read file', 'pagination_next' => 'Older' ),
		'gallery' => array(
			'kicker' => 'Visual File', 'title' => 'Journal images', 'copy' => 'Gallery copy',
			'items' => array( array( 'order' => 1, 'attachment_id' => 61, 'alt' => 'Alt', 'caption' => 'Caption', 'link_url' => '/journal/file/', 'credit' => 'Credit', 'source' => 'Studio', 'source_url' => 'https://example.test/source', 'focal_x' => 50, 'focal_y' => 45 ) ),
		),
		'retention' => array( array( 'visible' => true, 'order' => 1, 'label' => 'Latest', 'title' => 'Newest file', 'copy' => 'Keep moving', 'destination' => 'latest', 'url' => '', 'image_id' => 62, 'image_alt' => 'Alt', 'image_credit' => 'Credit', 'image_source' => 'Studio', 'image_source_url' => 'https://example.test/source', 'focal_x' => 50, 'focal_y' => 50 ) ),
		'presentation' => array( 'density' => 'editorial', 'lead_prominence' => 'standard', 'desk_rhythm' => 'balanced', 'section_gap' => 38, 'hero_min_height' => 240, 'card_min_height' => 390, 'media_min_height' => 220 ),
	),
	'oscars' => array(
		'schema_version' => 1,
		'identity' => array( 'kicker' => 'Oscar Ledger', 'title' => 'Academy Awards', 'explore_kicker' => 'Explore', 'explore_heading' => 'Start anywhere', 'spotlights_heading' => 'Latest Ceremony', 'titles_kicker' => 'Poster-Led', 'titles_heading' => 'Open through films', 'research_kicker' => 'Research', 'research_heading' => 'Open the ledger', 'reviews_heading' => 'Reviews', 'deep_cuts_heading' => 'Deep Cuts' ),
		'section_order' => array( 'hero', 'navigator', 'board', 'doors', 'spotlights', 'titles', 'research', 'linked-reviews', 'winners', 'deep-cuts', 'rotating-winners' ),
		'section_visibility' => array( 'hero' => true, 'navigator' => true, 'board' => true, 'doors' => true, 'spotlights' => true, 'titles' => true, 'research' => true, 'linked-reviews' => false, 'winners' => true, 'deep-cuts' => true, 'rotating-winners' => true ),
		'presentation' => array( 'section_gap' => 40, 'hero_min_height' => 360, 'card_min_height' => 360 ),
	),
);
$lunara_test_provider_state = $lunara_test_provider_defaults;
$lunara_test_contributed_state = array(
	'public_name'   => 'Visible',
	'access_token'  => 'top-secret-token',
	'authorization' => 'Bearer secret',
	'preview_hash'  => 'preview-secret',
	'raw_option_key' => 'private_option_name',
	'nested' => array(
		'headline' => 'Safe headline',
		'access_token' => 'nested-secret-token',
		'deeper' => array( 'visible' => 'Safe nested value', 'authorization' => 'nested bearer', 'preview_hash' => 'nested hash' ),
	),
	'items' => array( array( 'label' => 'Safe row', 'raw_option_key' => 'private_row_option', 'access_token' => 'row-secret' ) ),
);
$lunara_test_provider_revisions = array(
	'reviews' => array(),
	'journal' => array(),
	'oscars'  => array(),
);

function lunara_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

class WP_Error {
	private $code;
	private $message;
	private $data;
	public function __construct( $code = '', $message = '', $data = null ) {
		$this->code = (string) $code;
		$this->message = (string) $message;
		$this->data = $data;
	}
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
	public function get_error_data() { return $this->data; }
}

function is_wp_error( $value ) { return $value instanceof WP_Error; }

class WP_REST_Request {
	private $params;
	private $headers;
	public function __construct( $params = array(), $headers = array() ) {
		$this->params = $params;
		$this->headers = array_change_key_case( $headers, CASE_LOWER );
	}
	public function get_param( $key ) { return array_key_exists( $key, $this->params ) ? $this->params[ $key ] : null; }
	public function get_json_params() { return $this->params; }
	public function get_header( $key ) {
		$key = strtolower( str_replace( '_', '-', (string) $key ) );
		return isset( $this->headers[ $key ] ) ? $this->headers[ $key ] : '';
	}
}

class WP_REST_Response {
	private $data;
	private $status;
	public function __construct( $data = null, $status = 200 ) { $this->data = $data; $this->status = $status; }
	public function get_data() { return $this->data; }
	public function get_status() { return $this->status; }
}

function add_action( $hook, $callback, $priority = 10 ) {
	global $lunara_test_actions;
	$lunara_test_actions[] = compact( 'hook', 'callback', 'priority' );
}
function add_filter( $hook, $callback, $priority = 10 ) {
	global $lunara_test_filters;
	if ( ! isset( $lunara_test_filters[ $hook ] ) ) { $lunara_test_filters[ $hook ] = array(); }
	$lunara_test_filters[ $hook ][] = compact( 'callback', 'priority' );
}
function apply_filters( $hook, $value ) {
	global $lunara_test_filters;
	if ( empty( $lunara_test_filters[ $hook ] ) ) { return $value; }
	$filters = $lunara_test_filters[ $hook ];
	usort( $filters, static function ( $a, $b ) { return $a['priority'] - $b['priority']; } );
	foreach ( $filters as $filter ) { $value = call_user_func( $filter['callback'], $value ); }
	return $value;
}
function do_action( $hook ) {
	global $lunara_test_actions, $lunara_test_events;
	$lunara_test_events[] = 'action:' . $hook;
	foreach ( $lunara_test_actions as $action ) {
		if ( $hook === $action['hook'] ) { call_user_func( $action['callback'] ); }
	}
}
function register_rest_route( $namespace, $route, $args ) {
	global $lunara_test_routes;
	$lunara_test_routes[ $namespace . $route ] = $args;
}
function rest_ensure_response( $value ) { return $value instanceof WP_REST_Response ? $value : new WP_REST_Response( $value ); }

function __( $text ) { return $text; }
function esc_html__( $text ) { return $text; }
function esc_html_e( $text ) { echo htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr_e( $text ) { echo htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $text ) { return (string) $text; }
function esc_url_raw( $text ) { return filter_var( (string) $text, FILTER_SANITIZE_URL ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( is_scalar( $value ) ? (string) $value : '' ) ); }
function sanitize_hex_color( $value ) { return is_string( $value ) && preg_match( '/^#[0-9a-fA-F]{6}$/', $value ) ? $value : null; }
function absint( $value ) { return abs( (int) $value ); }
function wp_unslash( $value ) { return $value; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_hash( $value ) { return hash_hmac( 'sha256', (string) $value, 'test-auth-salt' ); }
function wp_generate_uuid4() { global $lunara_test_uuid; $lunara_test_uuid++; return sprintf( '00000000-0000-4000-8000-%012d', $lunara_test_uuid ); }
function current_time( $type, $gmt = 0 ) {
	global $lunara_test_now;
	if ( 'timestamp' === $type ) { return $lunara_test_now; }
	return gmdate( 'Y-m-d H:i:s', $lunara_test_now );
}
function get_current_user_id() { global $lunara_test_user_id; return $lunara_test_user_id; }
function is_user_logged_in() { global $lunara_test_logged_in; return $lunara_test_logged_in; }
function current_user_can( $capability ) {
	global $lunara_test_can_edit, $lunara_test_can_manage;
	if ( 'edit_theme_options' === $capability ) { return $lunara_test_can_edit; }
	if ( 'manage_options' === $capability ) { return $lunara_test_can_manage; }
	return false;
}
function is_admin() { return false; }
function wp_verify_nonce( $nonce, $action ) {
	global $lunara_test_nonce_valid;
	return $lunara_test_nonce_valid && 'wp_rest' === $action && 'good-rest-nonce' === $nonce;
}
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . ltrim( (string) $path, '/' ); }
function home_url( $path = '' ) { return 'https://example.test/' . ltrim( (string) $path, '/' ); }
function add_query_arg( $args, $url = '' ) {
	if ( ! is_array( $args ) ) { $args = array( $args => func_get_arg( 1 ) ); $url = func_get_arg( 2 ); }
	$separator = false === strpos( $url, '?' ) ? '?' : '&';
	return $url . $separator . http_build_query( $args );
}
function get_option( $key, $default = false ) { global $lunara_test_options; return array_key_exists( $key, $lunara_test_options ) ? $lunara_test_options[ $key ] : $default; }
function update_option( $key, $value, $autoload = null ) {
	global $lunara_test_options, $lunara_test_option_writes, $lunara_test_update_mode;
	$lunara_test_option_writes[] = array( 'key' => $key, 'autoload' => $autoload, 'value' => $value );
	if ( 'fail' === $lunara_test_update_mode ) { return false; }
	$lunara_test_options[ $key ] = 'mismatch' === $lunara_test_update_mode && is_array( $value ) ? array_slice( $value, 1 ) : $value;
	return true;
}
function delete_option( $key ) { global $lunara_test_options; unset( $lunara_test_options[ $key ] ); return true; }
function set_transient( $key, $value, $expiration ) { global $lunara_test_transients, $lunara_test_events; $lunara_test_events[] = 'transient-write:' . $key; $lunara_test_transients[ $key ] = $value; return true; }
function get_transient( $key ) {
	global $lunara_test_transients, $lunara_test_events;
	$lunara_test_events[] = 'transient-read:' . $key;
	return array_key_exists( $key, $lunara_test_transients ) ? $lunara_test_transients[ $key ] : false;
}
function delete_transient( $key ) { global $lunara_test_transients; unset( $lunara_test_transients[ $key ] ); return true; }
function get_theme_mod( $key, $default = false ) { global $lunara_test_theme_mods; return array_key_exists( $key, $lunara_test_theme_mods ) ? $lunara_test_theme_mods[ $key ] : $default; }
function nocache_headers() { global $lunara_test_events; $lunara_test_events[] = 'no-store'; }
function wp_die( $message ) { throw new RuntimeException( (string) $message ); }
function add_submenu_page() { return 'lunara_page_lunara-site-studio'; }
function wp_nonce_field() { echo '<input type="hidden" name="_wpnonce" value="test" />'; }
function selected( $selected, $current = true, $echo = true ) { $result = $selected == $current ? ' selected="selected"' : ''; if ( $echo ) { echo $result; } return $result; }
function __return_true() { return true; }
function __return_false() { return false; }

function lunara_test_available_dependency() { global $lunara_test_dependency_calls; $lunara_test_dependency_calls++; return true; }
function lunara_test_missing_dependency() { global $lunara_test_dependency_calls; $lunara_test_dependency_calls++; return new WP_Error( 'missing_dependency', 'Unavailable' ); }
function lunara_test_status() {
	global $lunara_test_status_calls;
	$lunara_test_status_calls++;
	return array(
		'state'      => 'ready',
		'label'      => 'Ready',
		'message'    => 'Canonical owner is available.',
		'count'      => 7,
		'updated_at' => '2026-08-28 12:00:00',
		'url'        => 'https://example.test/wp-admin/admin.php?page=owner',
		'api_key'    => 'must-never-leave',
		'raw_option' => array( 'secret' => 'nope' ),
	);
}
function lunara_test_renderer( $context = '' ) { global $lunara_test_renderer_calls; $lunara_test_renderer_calls[] = 'test'; echo '<div data-test="test-renderer">' . esc_html( $context ) . '</div>'; }
function lunara_control_desk_render_pairing_desk_form( $context = '' ) { global $lunara_test_renderer_calls; $lunara_test_renderer_calls[] = 'lunara-method'; echo '<div data-test="lunara-method">' . esc_html( $context ) . '</div>'; }
function lunara_control_desk_render_homepage_studio( $context = '' ) { global $lunara_test_renderer_calls; $lunara_test_renderer_calls[] = 'homepage-structure'; echo '<div data-test="homepage-structure">' . esc_html( $context ) . '</div>'; }
function lunara_control_desk_render_reviews_archive_studio( $context = '' ) { global $lunara_test_renderer_calls; $lunara_test_renderer_calls[] = 'reviews-archive'; echo '<div data-test="reviews-archive">' . esc_html( $context ) . '</div>'; }
function lunara_control_desk_render_journal_archive_studio( $context = '' ) { global $lunara_test_renderer_calls; $lunara_test_renderer_calls[] = 'journal-archive'; echo '<div data-test="journal-archive">' . esc_html( $context ) . '</div>'; }
function lunara_control_desk_render_oscars_portal_studio( $context = '' ) { global $lunara_test_renderer_calls; $lunara_test_renderer_calls[] = 'oscars-portal'; echo '<div data-test="oscars-portal">' . esc_html( $context ) . '</div>'; }
function lunara_control_desk_render_oscars_dossier_studio( $context = '' ) { global $lunara_test_renderer_calls; $lunara_test_renderer_calls[] = 'oscars-ledger'; echo '<div data-test="oscars-ledger">' . esc_html( $context ) . '</div>'; }

function lunara_test_provider_get( $provider ) { global $lunara_test_provider_calls, $lunara_test_provider_state; $lunara_test_provider_calls[] = $provider . ':read'; return $lunara_test_provider_state[ $provider ]; }
function lunara_test_provider_validate( $provider, $state ) {
	global $lunara_test_provider_calls;
	$lunara_test_provider_calls[] = $provider . ':validate';
	if ( ! is_array( $state ) || isset( $state['bad'] ) ) { return new WP_Error( $provider . '_invalid', 'Invalid', array( 'fields' => array( 'title' => 'Title is invalid.', 'secret_field' => 'No.' ) ) ); }
	return $state;
}
function lunara_test_provider_promote( $provider, $state ) {
	global $lunara_test_provider_calls, $lunara_test_provider_state, $lunara_test_provider_revisions;
	$validated = lunara_test_provider_validate( $provider, $state );
	if ( is_wp_error( $validated ) ) { return $validated; }
	$lunara_test_provider_calls[] = $provider . ':save';
	array_unshift( $lunara_test_provider_revisions[ $provider ], array( 'id' => wp_generate_uuid4(), 'saved_at' => current_time( 'mysql' ), 'saved_by' => get_current_user_id(), 'action' => 'save', 'validator_result' => 'passed', 'prior_public' => true, 'config' => $lunara_test_provider_state[ $provider ] ) );
	$lunara_test_provider_state[ $provider ] = $validated;
	return $validated;
}
function lunara_test_provider_preview( $provider, $state ) {
	global $lunara_test_provider_calls;
	$validated = lunara_test_provider_validate( $provider, $state );
	if ( is_wp_error( $validated ) ) { return $validated; }
	$lunara_test_provider_calls[] = $provider . ':preview';
	return $provider . '-private-token';
}
function lunara_test_provider_revisions( $provider ) { global $lunara_test_provider_calls, $lunara_test_provider_revisions; $lunara_test_provider_calls[] = $provider . ':revisions'; return $lunara_test_provider_revisions[ $provider ]; }
function lunara_test_provider_restore( $provider, $revision_id ) {
	global $lunara_test_provider_calls, $lunara_test_provider_revisions, $lunara_test_provider_state;
	$lunara_test_provider_calls[] = $provider . ':restore';
	foreach ( $lunara_test_provider_revisions[ $provider ] as $revision ) {
		if ( $revision['id'] === $revision_id ) {
			array_unshift( $lunara_test_provider_revisions[ $provider ], array( 'id' => wp_generate_uuid4(), 'saved_at' => current_time( 'mysql' ), 'saved_by' => get_current_user_id(), 'action' => 'restore', 'validator_result' => 'passed', 'prior_public' => true, 'config' => $lunara_test_provider_state[ $provider ] ) );
			$lunara_test_provider_state[ $provider ] = $revision['config'];
			return $lunara_test_provider_state[ $provider ];
		}
	}
	return new WP_Error( $provider . '_revision_not_found', 'Not found' );
}

function lunara_reviews_archive_studio_get_public_config( $allow_preview = true ) { return lunara_test_provider_get( 'reviews' ); }
function lunara_reviews_archive_studio_validate_config( $state ) { return lunara_test_provider_validate( 'reviews', $state ); }
function lunara_reviews_archive_studio_promote_config( $state, $action = 'save' ) { return lunara_test_provider_promote( 'reviews', $state ); }
function lunara_reviews_archive_studio_store_preview( $state ) { return lunara_test_provider_preview( 'reviews', $state ); }
function lunara_reviews_archive_studio_get_revisions() { return lunara_test_provider_revisions( 'reviews' ); }
function lunara_reviews_archive_studio_restore_revision( $id ) { return lunara_test_provider_restore( 'reviews', $id ); }
function lunara_journal_archive_studio_get_public_config( $allow_preview = true ) { return lunara_test_provider_get( 'journal' ); }
function lunara_journal_archive_studio_validate_config( $state ) { return lunara_test_provider_validate( 'journal', $state ); }
function lunara_journal_archive_studio_promote_config( $state, $action = 'save' ) { return lunara_test_provider_promote( 'journal', $state ); }
function lunara_journal_archive_studio_store_preview( $state ) { return lunara_test_provider_preview( 'journal', $state ); }
function lunara_journal_archive_studio_get_revisions() { return lunara_test_provider_revisions( 'journal' ); }
function lunara_journal_archive_studio_restore_revision( $id ) { return lunara_test_provider_restore( 'journal', $id ); }
function lunara_oscars_portal_studio_get_public_config( $allow_preview = true ) { return lunara_test_provider_get( 'oscars' ); }
function lunara_oscars_portal_studio_validate_config( $state ) { return lunara_test_provider_validate( 'oscars', $state ); }
function lunara_oscars_portal_studio_promote_config( $state, $action = 'save' ) { return lunara_test_provider_promote( 'oscars', $state ); }
function lunara_oscars_portal_studio_store_preview( $state ) { return lunara_test_provider_preview( 'oscars', $state ); }
function lunara_oscars_portal_studio_get_revisions() { return lunara_test_provider_revisions( 'oscars' ); }
function lunara_oscars_portal_studio_restore_revision( $id ) { return lunara_test_provider_restore( 'oscars', $id ); }

function lunara_reviews_archive_studio_defaults() { global $lunara_test_provider_defaults; return $lunara_test_provider_defaults['reviews']; }
function lunara_journal_archive_studio_defaults() { global $lunara_test_provider_defaults; return $lunara_test_provider_defaults['journal']; }
function lunara_oscars_portal_studio_defaults() { global $lunara_test_provider_defaults; return $lunara_test_provider_defaults['oscars']; }

function lunara_test_throwing_filter() { throw new RuntimeException( 'secret filter exception' ); }
function lunara_test_throwing_dependency() { throw new RuntimeException( 'secret dependency exception' ); }
function lunara_test_throwing_status() { throw new RuntimeException( 'secret status exception' ); }
function lunara_test_throwing_factory() { throw new RuntimeException( 'secret factory exception' ); }
function lunara_test_throwing_renderer() { throw new RuntimeException( 'secret renderer exception' ); }
function lunara_test_throwing_state_schema() { throw new RuntimeException( 'secret state schema exception' ); }
function lunara_test_throwing_no_store_action() { throw new RuntimeException( 'secret no-store action exception' ); }
function lunara_test_counting_factory() { global $lunara_test_factory_calls; $lunara_test_factory_calls++; return new Lunara_Test_Contributed_Adapter(); }
function lunara_test_contributed_state_schema() {
	return array(
		'public_name' => true,
		'nested' => array( 'headline' => true, 'deeper' => array( 'visible' => true ) ),
		'items' => array( '*' => array( 'label' => true ) ),
	);
}

$theme_root = dirname( __DIR__ );
if ( file_exists( $theme_root . '/inc/site-studio-registry.php' ) ) { require $theme_root . '/inc/site-studio-registry.php'; }
if ( file_exists( $theme_root . '/inc/site-studio-adapters.php' ) ) { require $theme_root . '/inc/site-studio-adapters.php'; }
if ( file_exists( $theme_root . '/inc/site-studio-rest.php' ) ) { require $theme_root . '/inc/site-studio-rest.php'; }
require $theme_root . '/inc/site-studio.php';
require $theme_root . '/inc/design-tokens.php';

class Lunara_Test_Contributed_Adapter implements Lunara_Site_Studio_Surface_Adapter {
	public function read_state() { global $lunara_test_contributed_state; return $lunara_test_contributed_state; }
	public function validate_state( $candidate ) { return is_array( $candidate ) ? $candidate : new WP_Error( 'invalid_state' ); }
	public function save_state( $candidate ) {
		$validated = $this->validate_state( $candidate );
		if ( is_wp_error( $validated ) ) { return $validated; }
		return array( 'state' => $validated, 'changed_sections' => array( 'panel', 'private-section' ), 'revision_id' => 'safe-revision', 'timestamp' => '2026-08-28 15:00:00' );
	}
	public function create_preview( $candidate ) {
		global $lunara_test_preview_delegate_calls;
		$lunara_test_preview_delegate_calls++;
		set_transient( 'contributed-preview-storage', $candidate, 1800 );
		return array( 'token' => 'contributed-token', 'expires_at' => 1900001800 );
	}
	public function list_revisions() { return array(); }
	public function restore_revision( $revision_id ) { return array( 'state' => $this->read_state(), 'safety_revision_id' => 'safety', 'timestamp' => '2026-08-28 15:00:00' ); }
}

class Lunara_Test_Throwing_Adapter extends Lunara_Test_Contributed_Adapter {
	public function read_state() { throw new RuntimeException( 'secret adapter read exception' ); }
}

function lunara_test_throwing_adapter_factory() { return new Lunara_Test_Throwing_Adapter(); }

// Registry schema, stable bookmarks, and no eager callback execution.
$lunara_test_dependency_calls = 0;
$lunara_test_status_calls = 0;
$surfaces = lunara_site_studio_surfaces();
$expected_ids = array( 'lunara-method', 'homepage-structure', 'reviews-archive', 'journal-archive', 'oscars-portal', 'oscars-ledger' );
lunara_test_assert( $expected_ids === array_keys( $surfaces ), 'The six stable Site Studio IDs must remain in canonical order.' );
$required_fields = array( 'id', 'group', 'label', 'description', 'aliases', 'owner', 'kind', 'capability', 'supports_preview', 'preview_route', 'admin_url', 'dependency_callback', 'status_callback', 'danger_level', 'sections', 'classic_url', 'available', 'unavailable_reason' );
foreach ( $surfaces as $id => $surface ) {
	foreach ( $required_fields as $field ) { lunara_test_assert( array_key_exists( $field, $surface ), "{$id} must normalize {$field}." ); }
	lunara_test_assert( $id === $surface['id'], "{$id} must preserve its key as id." );
	lunara_test_assert( is_array( $surface['aliases'] ) && is_array( $surface['sections'] ), "{$id} must expose array aliases and sections." );
	lunara_test_assert( in_array( $surface['kind'], array( 'presentation', 'content', 'workflow', 'integration', 'operations' ), true ), "{$id} must use an approved kind." );
	lunara_test_assert( in_array( $surface['danger_level'], array( 'none', 'caution', 'destructive' ), true ), "{$id} must use an approved danger level." );
}
lunara_test_assert( 0 === $lunara_test_dependency_calls && 0 === $lunara_test_status_calls, 'Registry normalization must never execute dependency or status callbacks.' );
lunara_test_assert( 'lunara_site_studio_oscars_ledger_dependency' === $surfaces['oscars-ledger']['dependency_callback'], 'Oscars Ledger must use its plugin availability dependency instead of appearing available unconditionally.' );
lunara_test_assert( false === call_user_func( $surfaces['oscars-ledger']['dependency_callback'] ), 'Oscars Ledger must be unavailable when its owning plugin is absent.' );
foreach ( $expected_ids as $id ) {
	$url = lunara_site_studio_admin_url( $id );
	lunara_test_assert( false !== strpos( $url, 'surface=' . rawurlencode( $id ) ), "{$id} bookmark must remain stable." );
}
lunara_test_assert( false !== strpos( lunara_site_studio_admin_url( 'not-real' ), 'surface=lunara-method' ), 'Unknown bookmarks must fall back to Lunara Method.' );

// Associative and list-style contributions; invalid callbacks fail closed.
add_filter( 'lunara_site_studio_surfaces', static function ( $items ) {
	$items[] = array(
		'id' => 'plugin-tool', 'group' => 'Tools', 'label' => 'Plugin Tool', 'description' => 'A contributed tool.',
		'aliases' => array( 'helper' ), 'owner' => 'plugin:test', 'kind' => 'operations', 'capability' => 'edit_theme_options',
		'supports_preview' => false, 'preview_route' => '', 'admin_url' => 'admin.php?page=plugin-tool',
		'dependency_callback' => 'lunara_test_available_dependency', 'status_callback' => 'lunara_test_status',
		'danger_level' => 'caution', 'sections' => array(), 'classic_url' => 'admin.php?page=plugin-tool', 'renderer' => 'lunara_test_renderer',
	);
	$items['broken-tool'] = array(
		'group' => 'Tools', 'label' => 'Broken Tool', 'description' => 'Unavailable.', 'owner' => 'plugin:broken',
		'kind' => 'integration', 'capability' => 'edit_theme_options', 'admin_url' => 'admin.php?page=broken',
		'dependency_callback' => 'not_a_real_callback', 'status_callback' => 'also_not_real', 'danger_level' => 'none',
	);
	$items['malformed-tool'] = array(
		'group' => array( 'not scalar' ), 'label' => 'Malformed Tool', 'description' => 'Unavailable.', 'aliases' => array( array( 'bad' ), 'safe alias' ),
		'owner' => 'plugin:malformed', 'kind' => 'integration', 'capability' => array( 'not scalar' ), 'admin_url' => 'admin.php?page=malformed',
		'dependency_callback' => array( 'not', 'callable' ), 'status_callback' => 'lunara_test_status', 'danger_level' => 'none',
	);
	return $items;
}, 20 );
$surfaces = lunara_site_studio_surfaces();
lunara_test_assert( isset( $surfaces['plugin-tool'] ) && 'plugin-tool' === $surfaces['plugin-tool']['id'], 'List-style plugin contributions must normalize by their id.' );
lunara_test_assert( isset( $surfaces['broken-tool'] ) && false === $surfaces['broken-tool']['available'] && 'invalid_callback' === $surfaces['broken-tool']['unavailable_reason'], 'Invalid callbacks must fail closed without a fatal.' );
lunara_test_assert( isset( $surfaces['malformed-tool'] ) && false === $surfaces['malformed-tool']['available'], 'Malformed non-scalar plugin metadata must fail closed without a fatal.' );

// A different owner claiming a stable ID becomes an unavailable conflict card.
add_filter( 'lunara_site_studio_surfaces', static function ( $items ) {
	$items[] = array(
		'id' => 'reviews-archive', 'group' => 'Collision', 'label' => 'Hijack', 'description' => 'Must not win.',
		'owner' => 'plugin:hijack', 'kind' => 'presentation', 'capability' => 'edit_theme_options', 'admin_url' => 'admin.php?page=hijack',
		'dependency_callback' => '__return_true', 'status_callback' => 'lunara_test_status', 'danger_level' => 'none',
	);
	return $items;
}, 30 );
$surfaces = lunara_site_studio_surfaces();
lunara_test_assert( false === $surfaces['reviews-archive']['available'] && 'ownership_conflict' === $surfaces['reviews-archive']['unavailable_reason'], 'A second owner claiming one ID must produce an unavailable conflict card.' );
lunara_test_assert( 'theme:reviews-archive' === $surfaces['reviews-archive']['owner'], 'An ownership conflict must preserve the canonical owner rather than accept the claimant.' );

// Remove test filters so the canonical adapter and REST lanes remain available.
$lunara_test_filters['lunara_site_studio_surfaces'] = array();

// Capability must be checked before dependency/status callbacks.
$lunara_test_can_edit = false;
$lunara_test_dependency_calls = 0;
$lunara_test_status_calls = 0;
$public = lunara_site_studio_public_surfaces();
lunara_test_assert( array() === $public, 'An unauthorized registry listing must expose no surfaces.' );
lunara_test_assert( 0 === $lunara_test_dependency_calls && 0 === $lunara_test_status_calls, 'Unauthorized listing must not execute dependency or status callbacks.' );
$lunara_test_can_edit = true;

// Status is strict-allowlist serialized.
add_filter( 'lunara_site_studio_surfaces', static function ( $items ) {
	$items['lunara-method']['dependency_callback'] = 'lunara_test_available_dependency';
	$items['lunara-method']['status_callback'] = 'lunara_test_status';
	return $items;
} );
$public = lunara_site_studio_public_surfaces();
$method_status = $public['lunara-method']['status'];
lunara_test_assert( 'ready' === $method_status['state'] && 7 === $method_status['count'], 'Allowlisted status fields must survive serialization.' );
lunara_test_assert( ! isset( $method_status['api_key'] ) && ! isset( $method_status['raw_option'] ), 'Arbitrary status and secret-shaped fields must be removed.' );
foreach ( array( 'dependency_callback', 'status_callback', 'adapter_factory', 'renderer', 'preview_query_arg' ) as $internal_field ) {
	lunara_test_assert( ! array_key_exists( $internal_field, $public['lunara-method'] ), "Public registry must omit internal {$internal_field}." );
}
$lunara_test_filters['lunara_site_studio_surfaces'] = array();

// Unknown selection falls back, while a known unavailable destination stays selected.
$_GET['surface'] = 'not-real';
lunara_test_assert( 'lunara-method' === lunara_site_studio_current_surface(), 'Unknown selected surfaces must fall back to Lunara Method.' );
add_filter( 'lunara_site_studio_surfaces', static function ( $items ) {
	$items['journal-archive']['dependency_callback'] = 'lunara_test_missing_dependency';
	return $items;
} );
$_GET['surface'] = 'journal-archive';
lunara_test_assert( 'journal-archive' === lunara_site_studio_current_surface(), 'Known unavailable surfaces must remain selected.' );
$lunara_test_renderer_calls = array();
ob_start();
lunara_render_site_studio_page();
$unavailable_html = ob_get_clean();
lunara_test_assert( false !== strpos( $unavailable_html, 'unavailable' ) && array() === $lunara_test_renderer_calls, 'Unavailable selection must render a clear card and execute no renderer.' );
$lunara_test_filters['lunara_site_studio_surfaces'] = array();

// Selected-surface isolation.
$_GET['surface'] = 'reviews-archive';
$lunara_test_renderer_calls = array();
ob_start();
lunara_render_site_studio_page();
ob_end_clean();
lunara_test_assert( array( 'reviews-archive' ) === $lunara_test_renderer_calls, 'Only the selected renderer may execute.' );

// Direct admin selection must enforce the declared capability before touching dependencies.
add_filter( 'lunara_site_studio_surfaces', static function ( $items ) {
	$items['restricted-tool'] = array(
		'id' => 'restricted-tool', 'group' => 'Tools', 'label' => 'Restricted Tool', 'description' => 'A stricter destination.',
		'aliases' => array(), 'owner' => 'plugin:restricted', 'kind' => 'operations', 'capability' => 'manage_options',
		'supports_preview' => false, 'preview_route' => '', 'admin_url' => 'admin.php?page=restricted-tool',
		'dependency_callback' => 'lunara_test_available_dependency', 'status_callback' => 'lunara_test_status',
		'danger_level' => 'none', 'sections' => array(), 'classic_url' => 'admin.php?page=restricted-tool', 'renderer' => 'lunara_test_renderer',
	);
	return $items;
} );
$_GET['surface'] = 'restricted-tool';
$lunara_test_dependency_calls = 0;
$lunara_test_can_manage = false;
$restricted_denied = false;
ob_start();
try {
	lunara_render_site_studio_page();
} catch ( RuntimeException $error ) {
	$restricted_denied = true;
}
ob_end_clean();
lunara_test_assert( $restricted_denied, 'A direct admin request must deny a surface whose declared capability is unavailable.' );
lunara_test_assert( 0 === $lunara_test_dependency_calls, 'Admin rendering must check the declared capability before executing the dependency callback.' );
$lunara_test_can_manage = true;
$lunara_test_filters['lunara_site_studio_surfaces'] = array();

// Adapter contract and mature-provider delegation.
lunara_test_assert( interface_exists( 'Lunara_Site_Studio_Surface_Adapter' ), 'The PHP 7.4 adapter interface must exist.' );
$adapter = lunara_site_studio_get_adapter( 'reviews-archive' );
lunara_test_assert( $adapter instanceof Lunara_Site_Studio_Surface_Adapter, 'Reviews Archive must resolve a callable adapter.' );
lunara_test_assert( lunara_site_studio_get_adapter( 'journal-archive' ) instanceof Lunara_Site_Studio_Surface_Adapter, 'Journal Archive must resolve a callable adapter.' );
lunara_test_assert( lunara_site_studio_get_adapter( 'oscars-portal' ) instanceof Lunara_Site_Studio_Surface_Adapter, 'Oscars Portal must resolve a callable adapter.' );
$lunara_test_provider_calls = array();
lunara_test_assert( 'Reviews' === $adapter->read_state()['title'], 'Adapter read must delegate to the canonical provider.' );
lunara_test_assert( is_wp_error( $adapter->validate_state( array( 'bad' => true ) ) ), 'Adapter validation must preserve provider WP_Error failures.' );
$save = $adapter->save_state( array( 'title' => 'New Reviews', 'section_order' => array( 'hero', 'grid' ) ) );
lunara_test_assert( 'New Reviews' === $save['state']['title'] && ! empty( $save['revision_id'] ), 'Adapter save must return canonical normalized state and provider revision ID.' );
$preview = $adapter->create_preview( array( 'title' => 'Preview Reviews' ) );
lunara_test_assert( 'reviews-private-token' === $preview['token'], 'Adapter preview must reuse the provider token contract.' );
lunara_test_assert( in_array( 'reviews:read', $lunara_test_provider_calls, true ) && in_array( 'reviews:save', $lunara_test_provider_calls, true ) && in_array( 'reviews:preview', $lunara_test_provider_calls, true ), 'Adapter must delegate read/save/preview to the hardened provider.' );

// Generic route, token, revision, and restore services for later surfaces.
add_filter( 'lunara_site_studio_surfaces', static function ( $items ) {
	$items['future-surface'] = array(
		'id' => 'future-surface', 'group' => 'Future', 'label' => 'Future Surface', 'description' => 'A future adapter-owned surface.',
		'aliases' => array( 'future' ), 'owner' => 'theme:future', 'kind' => 'presentation', 'capability' => 'edit_theme_options',
		'supports_preview' => true, 'preview_route' => '/future/', 'preview_query_arg' => 'future_preview',
		'adapter_factory' => 'lunara_test_counting_factory', 'state_schema_callback' => 'lunara_test_contributed_state_schema', 'admin_url' => '',
		'dependency_callback' => 'lunara_test_available_dependency', 'status_callback' => 'lunara_test_status',
		'danger_level' => 'none', 'sections' => array( 'panel' ), 'classic_url' => 'admin.php?page=future-surface', 'renderer' => '',
	);
	return $items;
} );
lunara_test_assert( '/journal/' === lunara_site_studio_allow_relative_route( '/journal/' ), 'A relative same-origin preview route must pass.' );
foreach ( array( 'https://evil.test/', '//evil.test/', 'javascript:alert(1)', '/journal/?redirect=https://evil.test' ) as $bad_route ) {
	lunara_test_assert( '' === lunara_site_studio_allow_relative_route( $bad_route ), "Unsafe preview route must fail closed: {$bad_route}" );
}
$generic_token = lunara_site_studio_store_private_preview( 'future-surface', 'theme:future', '/future/', array( 'title' => 'Private' ) );
lunara_test_assert( is_string( $generic_token ) && '' !== $generic_token, 'Generic preview service must issue a token.' );
$generic_state = lunara_site_studio_get_private_preview( 'future-surface', 'theme:future', '/future/', $generic_token );
lunara_test_assert( 'Private' === $generic_state['title'], 'Generic preview must retrieve the bound owner/surface/route record.' );
$generic_key = 'lunara_site_studio_preview_' . hash( 'sha256', $generic_token );
$original_generic_record = $lunara_test_transients[ $generic_key ];
$lunara_test_transients[ $generic_key ]['token_hash'] = str_repeat( '0', 64 );
lunara_test_assert( false === lunara_site_studio_get_private_preview( 'future-surface', 'theme:future', '/future/', $generic_token ), 'A tampered preview token hash must fail closed.' );
$lunara_test_transients[ $generic_key ] = $original_generic_record;
lunara_test_assert( false === lunara_site_studio_get_private_preview( 'other-surface', 'theme:future', '/future/', $generic_token ), 'Preview token must be surface-bound.' );
lunara_test_assert( false === lunara_site_studio_get_private_preview( 'future-surface', 'plugin:other', '/future/', $generic_token ), 'Preview token must be owner-bound.' );
lunara_test_assert( false === lunara_site_studio_get_private_preview( 'future-surface', 'theme:future', '/other/', $generic_token ), 'Preview token must be route-bound.' );
$lunara_test_user_id = 99;
lunara_test_assert( false === lunara_site_studio_get_private_preview( 'future-surface', 'theme:future', '/future/', $generic_token ), 'Preview token must be user-bound.' );
$lunara_test_user_id = 41;
$lunara_test_now += 1801;
lunara_test_assert( false === lunara_site_studio_get_private_preview( 'future-surface', 'theme:future', '/future/', $generic_token ), 'Expired preview tokens must fail closed.' );
$lunara_test_now -= 1801;

$lunara_test_events = array();
lunara_site_studio_prepare_private_preview_response(
	static function () use ( $generic_token ) {
		return lunara_site_studio_get_private_preview( 'future-surface', 'theme:future', '/future/', $generic_token );
	}
);
$no_store_index = array_search( 'no-store', $lunara_test_events, true );
$read_index = null;
foreach ( $lunara_test_events as $index => $event ) { if ( 0 === strpos( $event, 'transient-read:' ) ) { $read_index = $index; break; } }
lunara_test_assert( false !== $no_store_index && ( null === $read_index || $no_store_index < $read_index ), 'Private no-store/noindex headers must be sent before preview lookup or denial.' );
$private_headers = lunara_site_studio_private_preview_headers();
lunara_test_assert( in_array( 'X-Robots-Tag: noindex, nofollow', $private_headers, true ), 'Private previews must emit an X-Robots-Tag noindex header.' );

for ( $i = 1; $i <= 14; $i++ ) { lunara_site_studio_push_revision( 'future-surface', array( 'value' => $i ), 'save' ); }
$generic_revisions = lunara_site_studio_list_revisions( 'future-surface', false );
lunara_test_assert( 12 === count( $generic_revisions ), 'Generic revision history must retain exactly twelve newest snapshots.' );
lunara_test_assert( ! isset( $generic_revisions[0]['config'] ) && ! isset( $generic_revisions[0]['saved_by'] ), 'Public revision rows must omit stored config and user IDs.' );
$nonautoload_write = end( $lunara_test_option_writes );
lunara_test_assert( false === $nonautoload_write['autoload'], 'Generic revision options must be written non-autoloaded.' );
$restore_id = $generic_revisions[5]['id'];
$current_state = array( 'value' => 99 );
$restore = lunara_site_studio_restore_revision(
	'future-surface',
	$restore_id,
	static function () use ( &$current_state ) { return $current_state; },
	static function ( $state ) { return is_array( $state ) ? $state : new WP_Error( 'invalid' ); },
	static function ( $state ) use ( &$current_state ) { $current_state = $state; return $state; }
);
lunara_test_assert( is_array( $restore ) && 99 !== $restore['state']['value'] && ! empty( $restore['safety_revision_id'] ), 'Restore must validate, save, and report a pre-restore safety snapshot.' );
$lunara_test_filters['lunara_site_studio_surfaces'] = array();

// REST registration and authorization ordering.
lunara_site_studio_register_rest_routes();
foreach ( array( '/surfaces', '/surfaces/(?P<surface>[a-z0-9\-]+)/state', '/surfaces/(?P<surface>[a-z0-9\-]+)/preview', '/surfaces/(?P<surface>[a-z0-9\-]+)/save', '/surfaces/(?P<surface>[a-z0-9\-]+)/revisions', '/surfaces/(?P<surface>[a-z0-9\-]+)/restore' ) as $route ) {
	lunara_test_assert( isset( $lunara_test_routes['lunara-site-studio/v1' . $route] ), "REST route must register: {$route}" );
}
$good_request = new WP_REST_Request( array( 'surface' => 'reviews-archive' ), array( 'x-wp-nonce' => 'good-rest-nonce' ) );
lunara_test_assert( true === lunara_site_studio_rest_permission( $good_request, true ), 'Authorized cookie + nonce + capability request must pass.' );
$lunara_test_dependency_calls = 0;
$lunara_test_logged_in = false;
$denied = lunara_site_studio_rest_permission( $good_request, true );
lunara_test_assert( is_wp_error( $denied ) && 0 === $lunara_test_dependency_calls, 'Anonymous REST requests must fail before any dependency callback.' );
$lunara_test_logged_in = true;
$lunara_test_nonce_valid = false;
$denied = lunara_site_studio_rest_permission( $good_request, true );
lunara_test_assert( is_wp_error( $denied ), 'Invalid REST nonce must fail closed.' );
$lunara_test_nonce_valid = true;
$lunara_test_can_edit = false;
$lunara_test_provider_calls = array();
$denied = lunara_site_studio_rest_permission( $good_request, true );
lunara_test_assert( is_wp_error( $denied ) && array() === $lunara_test_provider_calls, 'Capability denial must occur before adapter/provider callbacks.' );
$lunara_test_can_edit = true;

$unknown = lunara_site_studio_rest_permission( new WP_REST_Request( array( 'surface' => 'unknown-surface' ), array( 'x-wp-nonce' => 'good-rest-nonce' ) ), true );
lunara_test_assert( is_wp_error( $unknown ) && 'site_studio_surface_not_found' === $unknown->get_error_code(), 'Unknown REST surfaces must fail closed.' );
add_filter( 'lunara_site_studio_surfaces', static function ( $items ) {
	$items['reviews-archive']['dependency_callback'] = 'lunara_test_missing_dependency';
	return $items;
} );
$missing = lunara_site_studio_rest_permission( $good_request, true );
lunara_test_assert( is_wp_error( $missing ) && 'site_studio_unavailable' === $missing->get_error_code(), 'A missing dependency must return an unavailable REST error.' );
$lunara_test_filters['lunara_site_studio_surfaces'] = array();

$lunara_test_provider_state['reviews']['api_key'] = 'must-not-leave';
$state_response = lunara_site_studio_rest_get_state( $good_request );
lunara_test_assert( 200 === $state_response->get_status() && 'New Reviews' === $state_response->get_data()['state']['title'], 'State endpoint must return canonical adapter state.' );
lunara_test_assert( ! isset( $state_response->get_data()['state']['api_key'] ), 'State responses must redact secret-shaped canonical fields.' );
unset( $lunara_test_provider_state['reviews']['api_key'] );
$preview_response = lunara_site_studio_rest_preview( new WP_REST_Request( array( 'surface' => 'reviews-archive', 'state' => array( 'title' => 'REST Preview' ) ), array( 'x-wp-nonce' => 'good-rest-nonce' ) ) );
$preview_data = $preview_response->get_data();
lunara_test_assert( 200 === $preview_response->get_status() && false !== strpos( $preview_data['url'], 'https://example.test/reviews/' ) && false !== strpos( $preview_data['url'], 'lunara_reviews_preview=' ), 'Preview endpoint must construct the URL from the allowlisted registry route and provider query key.' );
lunara_test_assert( $lunara_test_now + 1800 === $preview_data['expires_at'], 'Preview response must expose the bounded 30-minute expiry.' );

$bad_save = lunara_site_studio_rest_save( new WP_REST_Request( array( 'surface' => 'reviews-archive', 'state' => array( 'bad' => true ) ), array( 'x-wp-nonce' => 'good-rest-nonce' ) ) );
$bad_save_data = $bad_save->get_data();
lunara_test_assert( 422 === $bad_save->get_status() && isset( $bad_save_data['fields']['title'] ) && ! isset( $bad_save_data['fields']['secret_field'] ), 'Validation errors must preserve allowlisted fields without exposing arbitrary internals.' );
$save_response = lunara_site_studio_rest_save( new WP_REST_Request( array( 'surface' => 'reviews-archive', 'state' => array( 'title' => 'REST Save', 'section_order' => array( 'hero', 'grid' ) ) ), array( 'x-wp-nonce' => 'good-rest-nonce' ) ) );
$save_data = $save_response->get_data();
lunara_test_assert( 200 === $save_response->get_status() && 'REST Save' === $save_data['state']['title'] && ! empty( $save_data['revision_id'] ), 'Save endpoint must return normalized state and revision metadata.' );

$revisions_response = lunara_site_studio_rest_get_revisions( $good_request );
foreach ( $revisions_response->get_data()['revisions'] as $revision ) {
	lunara_test_assert( ! isset( $revision['config'] ) && ! isset( $revision['saved_by'] ), 'REST revision output must redact config and user identity.' );
}
$revision_id = $revisions_response->get_data()['revisions'][0]['id'];
$unconfirmed = lunara_site_studio_rest_restore( new WP_REST_Request( array( 'surface' => 'reviews-archive', 'revision_id' => $revision_id ), array( 'x-wp-nonce' => 'good-rest-nonce' ) ) );
lunara_test_assert( 400 === $unconfirmed->get_status(), 'REST restore must require explicit confirm=true.' );
$confirmed = lunara_site_studio_rest_restore( new WP_REST_Request( array( 'surface' => 'reviews-archive', 'revision_id' => $revision_id, 'confirm' => true ), array( 'x-wp-nonce' => 'good-rest-nonce' ) ) );
lunara_test_assert( 200 === $confirmed->get_status() && ! empty( $confirmed->get_data()['safety_revision_id'] ), 'Confirmed restore must return the safety revision created first.' );

// Design Tokens are the sole emitter for the six shared colors. Saved key
// presence wins even when it equals the shipped default; missing keys fall
// back to compatible Customizer theme mods.
$lunara_test_options['lunara_design_tokens'] = array(
	'colors' => array(
		'gold'       => '#c9a961',
		'bg_primary' => '#112233',
	),
	'fonts' => array(),
);
$lunara_test_theme_mods = array(
	'lunara_accent_color'      => '#abcdef',
	'lunara_accent_soft_color' => '#123456',
	'lunara_bg_primary'        => '#654321',
	'lunara_bg_secondary'      => '#234567',
	'lunara_text_color'        => '#345678',
	'lunara_muted_text_color'  => '#456789',
);
ob_start();
lunara_design_tokens_output_css();
$token_css = ob_get_clean();
foreach ( array( '--lunara-bg-primary', '--lunara-bg-secondary', '--lunara-gold', '--lunara-gold-light', '--lunara-text', '--lunara-text-muted' ) as $variable ) {
	lunara_test_assert( 1 === substr_count( $token_css, $variable . ':' ), "Design Tokens must emit {$variable} exactly once." );
}
lunara_test_assert( false !== strpos( $token_css, '--lunara-gold:#c9a961;' ) && false === strpos( $token_css, '#abcdef' ), 'An explicitly stored shipped-default token must beat the Customizer value.' );
lunara_test_assert( false !== strpos( $token_css, '--lunara-bg-primary:#112233;' ) && false === strpos( $token_css, '#654321' ), 'A stored non-default token must beat the Customizer value.' );
lunara_test_assert( false !== strpos( $token_css, '--lunara-bg-secondary:#234567;' ) && false !== strpos( $token_css, '--lunara-gold-light:#123456;' ), 'Missing token keys must fall back to compatible Customizer mods.' );

function lunara_review_surface( $id, $owner, $overrides = array() ) {
	return array_replace(
		array(
			'id' => $id, 'group' => 'Review Fixtures', 'label' => 'Review Fixture', 'description' => 'Behavior fixture.',
			'aliases' => array(), 'owner' => $owner, 'kind' => 'presentation', 'capability' => 'edit_theme_options',
			'supports_preview' => false, 'preview_route' => '', 'preview_query_arg' => '',
			'adapter_factory' => 'lunara_test_counting_factory', 'state_schema_callback' => 'lunara_test_contributed_state_schema',
			'admin_url' => 'admin.php?page=' . $id, 'dependency_callback' => 'lunara_test_available_dependency',
			'status_callback' => 'lunara_test_status', 'danger_level' => 'none', 'sections' => array( 'panel' ),
			'classic_url' => 'admin.php?page=' . $id, 'renderer' => '',
		),
		$overrides
	);
}

function lunara_review_finish( $case, $failures ) {
	if ( ! empty( $failures ) ) {
		foreach ( $failures as $failure ) { fwrite( STDERR, "FAIL [{$case}]: {$failure}\n" ); }
		exit( 1 );
	}
	echo "site-studio review case {$case}: all assertions passed.\n";
	exit( 0 );
}

function lunara_review_case_sticky_ownership() {
	global $lunara_test_filters;
	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	add_filter( 'lunara_site_studio_surfaces', static function ( $items ) {
		$items[] = lunara_review_surface( 'sticky-tool', 'plugin:alpha', array( 'label' => 'Alpha first' ) );
		$items[] = lunara_review_surface( 'sticky-tool', 'plugin:beta', array( 'label' => 'Beta conflict' ) );
		$items[] = lunara_review_surface( 'sticky-tool', 'plugin:alpha', array( 'label' => 'Alpha last' ) );
		$items[] = lunara_review_surface( 'long-sticky-tool', 'plugin:alpha' );
		$items[] = lunara_review_surface( 'long-sticky-tool', 'plugin:beta' );
		$items[] = lunara_review_surface( 'long-sticky-tool', 'plugin:gamma' );
		$items[] = lunara_review_surface( 'long-sticky-tool', 'plugin:alpha' );
		$items[] = lunara_review_surface( 'same-owner-tool', 'plugin:alpha', array( 'label' => 'First label' ) );
		$items[] = lunara_review_surface( 'same-owner-tool', 'plugin:alpha', array( 'label' => 'Replacement label' ) );
		return $items;
	} );
	$surfaces = lunara_site_studio_surfaces();
	$failures = array();
	if ( ! isset( $surfaces['sticky-tool'] ) || ! empty( $surfaces['sticky-tool']['available'] ) || 'ownership_conflict' !== $surfaces['sticky-tool']['unavailable_reason'] ) { $failures[] = 'A→B→A ownership conflict must remain unavailable.'; }
	if ( ! isset( $surfaces['long-sticky-tool'] ) || ! empty( $surfaces['long-sticky-tool']['available'] ) || 'ownership_conflict' !== $surfaces['long-sticky-tool']['unavailable_reason'] ) { $failures[] = 'Longer distinct-owner sequences must remain unavailable.'; }
	if ( empty( $surfaces['same-owner-tool']['available'] ) || 'Replacement label' !== $surfaces['same-owner-tool']['label'] ) { $failures[] = 'Same-owner replacement alone must remain valid.'; }
	lunara_review_finish( 'sticky-ownership', $failures );
}

function lunara_review_case_adapter_only() {
	global $lunara_test_filters;
	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	add_filter( 'lunara_site_studio_surfaces', static function ( $items ) {
		$items['adapter-only-tool'] = lunara_review_surface( 'adapter-only-tool', 'plugin:adapter-only', array( 'admin_url' => '' ) );
		return $items;
	} );
	$surfaces = lunara_site_studio_surfaces();
	$public   = lunara_site_studio_public_surfaces();
	$failures = array();
	if ( empty( $surfaces['adapter-only-tool']['available'] ) ) { $failures[] = 'A callable adapter with a private schema must be valid without admin_url.'; }
	if ( ! isset( $public['adapter-only-tool'] ) || '' !== $public['adapter-only-tool']['admin_url'] ) { $failures[] = 'An absent canonical admin URL must serialize as an empty string.'; }
	lunara_review_finish( 'adapter-only', $failures );
}

function lunara_review_case_throwing_callbacks() {
	global $lunara_test_filters;
	$failures = array();
	$secret_pattern = '/secret (?:filter|dependency|status|factory|renderer|adapter)/i';

	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	add_filter( 'lunara_site_studio_surfaces', 'lunara_test_throwing_filter' );
	$thrown = null;
	try { $filtered = lunara_site_studio_surfaces(); } catch ( Throwable $error ) { $thrown = $error; $filtered = array(); }
	if ( null !== $thrown || ! isset( $filtered['lunara-method'] ) ) { $failures[] = 'A throwing registry filter must fall back without escaping.'; }

	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	add_filter( 'lunara_site_studio_surfaces', static function ( $items ) { $items['lunara-method']['dependency_callback'] = 'lunara_test_throwing_dependency'; return $items; } );
	$thrown = null;
	try { $availability = lunara_site_studio_surface_availability( lunara_site_studio_get_surface( 'lunara-method' ) ); } catch ( Throwable $error ) { $thrown = $error; $availability = array(); }
	if ( null !== $thrown || ! empty( $availability['available'] ) || preg_match( $secret_pattern, isset( $availability['message'] ) ? $availability['message'] : '' ) ) { $failures[] = 'A throwing dependency callback must return a generic unavailable result.'; }

	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	add_filter( 'lunara_site_studio_surfaces', static function ( $items ) { $items['lunara-method']['status_callback'] = 'lunara_test_throwing_status'; return $items; } );
	$thrown = null;
	try { $public = lunara_site_studio_public_surfaces(); } catch ( Throwable $error ) { $thrown = $error; $public = array(); }
	$status_text = isset( $public['lunara-method']['status'] ) ? wp_json_encode( $public['lunara-method']['status'] ) : '';
	if ( null !== $thrown || preg_match( $secret_pattern, $status_text ) ) { $failures[] = 'A throwing status callback must degrade without exposing exception text.'; }

	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	add_filter( 'lunara_site_studio_surfaces', static function ( $items ) { $items['reviews-archive']['adapter_factory'] = 'lunara_test_throwing_factory'; return $items; } );
	$thrown = null;
	try { $adapter_result = lunara_site_studio_get_adapter( 'reviews-archive' ); } catch ( Throwable $error ) { $thrown = $error; $adapter_result = null; }
	if ( null !== $thrown || ! is_wp_error( $adapter_result ) || preg_match( $secret_pattern, is_wp_error( $adapter_result ) ? $adapter_result->get_error_message() : '' ) ) { $failures[] = 'A throwing adapter factory must return a generic WP_Error.'; }

	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	add_filter( 'lunara_site_studio_surfaces', static function ( $items ) {
		$items['throwing-schema'] = lunara_review_surface( 'throwing-schema', 'plugin:throwing-schema', array( 'state_schema_callback' => 'lunara_test_throwing_state_schema' ) );
		return $items;
	} );
	$thrown = null;
	try { $schema_response = lunara_site_studio_rest_get_state( new WP_REST_Request( array( 'surface' => 'throwing-schema' ), array( 'x-wp-nonce' => 'good-rest-nonce' ) ) ); } catch ( Throwable $error ) { $thrown = $error; $schema_response = null; }
	$schema_text = $schema_response instanceof WP_REST_Response ? wp_json_encode( $schema_response->get_data() ) : '';
	if ( null !== $thrown || ! ( $schema_response instanceof WP_REST_Response ) || $schema_response->get_status() < 400 || preg_match( '/secret state schema/i', $schema_text ) ) { $failures[] = 'A throwing private state schema must return a generic non-secret REST error.'; }

	add_action( 'lunara_site_studio_private_no_store_sent', 'lunara_test_throwing_no_store_action' );
	$thrown = null;
	try { lunara_site_studio_send_private_no_store(); } catch ( Throwable $error ) { $thrown = $error; }
	if ( null !== $thrown ) { $failures[] = 'A throwing private-preview notification action must not escape after no-store headers are sent.'; }

	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	add_filter( 'lunara_site_studio_surfaces', static function ( $items ) { $items['lunara-method']['renderer'] = 'lunara_test_throwing_renderer'; return $items; } );
	$_GET['surface'] = 'lunara-method';
	$thrown = null;
	ob_start();
	try { lunara_render_site_studio_page(); } catch ( Throwable $error ) { $thrown = $error; }
	$admin_html = ob_get_clean();
	if ( null !== $thrown || false === stripos( $admin_html, 'unavailable' ) || preg_match( $secret_pattern, $admin_html ) ) { $failures[] = 'A throwing selected renderer must produce a non-secret unavailable admin response.'; }

	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	add_filter( 'lunara_site_studio_surfaces', static function ( $items ) {
		$items['throwing-adapter'] = lunara_review_surface( 'throwing-adapter', 'plugin:throwing', array( 'adapter_factory' => 'lunara_test_throwing_adapter_factory' ) );
		return $items;
	} );
	$request = new WP_REST_Request( array( 'surface' => 'throwing-adapter' ), array( 'x-wp-nonce' => 'good-rest-nonce' ) );
	$thrown = null;
	try { $response = lunara_site_studio_rest_get_state( $request ); } catch ( Throwable $error ) { $thrown = $error; $response = null; }
	$response_text = $response instanceof WP_REST_Response ? wp_json_encode( $response->get_data() ) : '';
	if ( null !== $thrown || ! ( $response instanceof WP_REST_Response ) || $response->get_status() < 400 || preg_match( $secret_pattern, $response_text ) ) { $failures[] = 'A throwing REST adapter method must return a generic non-secret error response.'; }

	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	lunara_review_finish( 'throwing-callbacks', $failures );
}

function lunara_review_case_preview_capability() {
	global $lunara_test_filters, $lunara_test_can_edit, $lunara_test_events, $lunara_test_transients;
	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	add_filter( 'lunara_site_studio_surfaces', static function ( $items ) {
		$items['capability-preview'] = lunara_review_surface(
			'capability-preview',
			'plugin:preview-owner',
			array( 'supports_preview' => true, 'preview_route' => '/capability-preview/', 'preview_query_arg' => 'capability_preview' )
		);
		return $items;
	} );
	$lunara_test_can_edit = true;
	$token = lunara_site_studio_store_private_preview( 'capability-preview', 'plugin:preview-owner', '/capability-preview/', array( 'title' => 'Private' ) );
	$lunara_test_can_edit = false;
	$lunara_test_events = array();
	$revoked = lunara_site_studio_get_private_preview( 'capability-preview', 'plugin:preview-owner', '/capability-preview/', is_string( $token ) ? $token : '' );
	$events_after_read = $lunara_test_events;
	$transient_count = count( $lunara_test_transients );
	$lunara_test_events = array();
	$denied_create = lunara_site_studio_store_private_preview( 'capability-preview', 'plugin:preview-owner', '/capability-preview/', array( 'title' => 'Denied' ) );
	$failures = array();
	if ( false !== $revoked ) { $failures[] = 'A preview token must fail after its declared capability is revoked.'; }
	if ( ! empty( array_filter( $events_after_read, static function ( $event ) { return 0 === strpos( $event, 'transient-read:' ); } ) ) ) { $failures[] = 'Revoked preview retrieval must deny before transient lookup.'; }
	if ( ! is_wp_error( $denied_create ) || count( $lunara_test_transients ) !== $transient_count || ! empty( array_filter( $lunara_test_events, static function ( $event ) { return 0 === strpos( $event, 'transient-write:' ); } ) ) ) { $failures[] = 'Revoked preview creation must deny before transient storage.'; }
	$lunara_test_can_edit = true;
	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	lunara_review_finish( 'preview-capability', $failures );
}

function lunara_review_case_preview_disabled() {
	global $lunara_test_filters, $lunara_test_provider_calls, $lunara_test_events, $lunara_test_preview_delegate_calls;
	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	add_filter( 'lunara_site_studio_surfaces', static function ( $items ) { $items['reviews-archive']['supports_preview'] = false; return $items; } );
	$lunara_test_provider_calls = array();
	$lunara_test_preview_delegate_calls = 0;
	$lunara_test_events = array();
	$response = lunara_site_studio_rest_preview( new WP_REST_Request( array( 'surface' => 'reviews-archive', 'state' => array( 'title' => 'Must not delegate' ) ), array( 'x-wp-nonce' => 'good-rest-nonce' ) ) );
	$data = $response->get_data();
	$failures = array();
	if ( $response->get_status() < 400 || 'site_studio_preview_unavailable' !== ( isset( $data['code'] ) ? $data['code'] : '' ) ) { $failures[] = 'A preview-disabled surface must return preview_unavailable.'; }
	if ( ! empty( $lunara_test_provider_calls ) || 0 !== $lunara_test_preview_delegate_calls ) { $failures[] = 'A preview-disabled surface must not validate, create, or store a preview.'; }
	if ( ! empty( array_filter( $lunara_test_events, static function ( $event ) { return 0 === strpos( $event, 'transient-write:' ); } ) ) ) { $failures[] = 'A preview-disabled surface must not write preview storage.'; }
	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	lunara_review_finish( 'preview-disabled', $failures );
}

function lunara_review_case_state_projection() {
	global $lunara_test_filters, $lunara_test_provider_defaults, $lunara_test_provider_state, $lunara_test_contributed_state;
	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	$failures = array();
	foreach ( array( 'reviews-archive' => 'reviews', 'journal-archive' => 'journal', 'oscars-portal' => 'oscars' ) as $surface => $provider ) {
		$lunara_test_provider_state[ $provider ] = $lunara_test_provider_defaults[ $provider ];
		$request = new WP_REST_Request( array( 'surface' => $surface ), array( 'x-wp-nonce' => 'good-rest-nonce' ) );
		$response = lunara_site_studio_rest_get_state( $request );
		if ( 200 !== $response->get_status() || $lunara_test_provider_defaults[ $provider ] !== $response->get_data()['state'] ) { $failures[] = "{$surface} state projection must retain every canonical editable field."; }
		$candidate = $lunara_test_provider_defaults[ $provider ];
		if ( isset( $candidate['title'] ) ) { $candidate['title'] .= ' Saved'; } elseif ( isset( $candidate['identity']['title'] ) ) { $candidate['identity']['title'] .= ' Saved'; }
		$save = lunara_site_studio_rest_save( new WP_REST_Request( array( 'surface' => $surface, 'state' => $candidate ), array( 'x-wp-nonce' => 'good-rest-nonce' ) ) );
		if ( 200 !== $save->get_status() || $candidate !== $save->get_data()['state'] ) { $failures[] = "{$surface} save projection must retain every canonical editable field."; }
	}
	add_filter( 'lunara_site_studio_surfaces', static function ( $items ) {
		$items['projected-adapter'] = lunara_review_surface( 'projected-adapter', 'plugin:projected' );
		return $items;
	} );
	$request = new WP_REST_Request( array( 'surface' => 'projected-adapter' ), array( 'x-wp-nonce' => 'good-rest-nonce' ) );
	$get = lunara_site_studio_rest_get_state( $request );
	$expected = array( 'public_name' => 'Visible', 'nested' => array( 'headline' => 'Safe headline', 'deeper' => array( 'visible' => 'Safe nested value' ) ), 'items' => array( array( 'label' => 'Safe row' ) ) );
	if ( 200 !== $get->get_status() || $expected !== $get->get_data()['state'] ) { $failures[] = 'Contributed state must be projected by its private allowlist, including nested/list fields.'; }
	$save = lunara_site_studio_rest_save( new WP_REST_Request( array( 'surface' => 'projected-adapter', 'state' => $lunara_test_contributed_state ), array( 'x-wp-nonce' => 'good-rest-nonce' ) ) );
	if ( 200 !== $save->get_status() || $expected !== $save->get_data()['state'] ) { $failures[] = 'Contributed save output must remove access_token, authorization, preview_hash, raw_option_key, and nested variants.'; }
	$public = lunara_site_studio_public_surfaces();
	if ( isset( $public['projected-adapter']['state_schema_callback'] ) ) { $failures[] = 'The private state schema callback must never appear in public registry output.'; }
	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	lunara_review_finish( 'state-projection', $failures );
}

function lunara_review_case_revision_durability() {
	global $lunara_test_update_mode;
	$failures = array();
	$lunara_test_update_mode = 'fail';
	$failed_write = lunara_site_studio_push_revision( 'durability-write-fail', array( 'value' => 1 ), 'save' );
	if ( ! is_wp_error( $failed_write ) ) { $failures[] = 'update_option failure must make revision creation fail.'; }
	$lunara_test_update_mode = 'mismatch';
	$mismatch = lunara_site_studio_push_revision( 'durability-readback-fail', array( 'value' => 1 ), 'save' );
	if ( ! is_wp_error( $mismatch ) ) { $failures[] = 'Missing UUID on readback must make revision creation fail.'; }

	$lunara_test_update_mode = 'normal';
	$target_id = lunara_site_studio_push_revision( 'durability-restore', array( 'value' => 1 ), 'save' );
	$current = array( 'value' => 99 );
	$save_calls = 0;
	$lunara_test_update_mode = 'fail';
	$restore_failed = lunara_site_studio_restore_revision(
		'durability-restore', $target_id,
		static function () use ( &$current ) { return $current; },
		static function ( $state ) { return $state; },
		static function ( $state ) use ( &$current, &$save_calls ) { $save_calls++; $current = $state; return $state; }
	);
	if ( ! is_wp_error( $restore_failed ) || 0 !== $save_calls || 99 !== $current['value'] ) { $failures[] = 'Restore must abort before live save when the safety snapshot write fails.'; }

	$current = array( 'value' => 99 );
	$save_calls = 0;
	$lunara_test_update_mode = 'mismatch';
	$restore_mismatch = lunara_site_studio_restore_revision(
		'durability-restore', $target_id,
		static function () use ( &$current ) { return $current; },
		static function ( $state ) { return $state; },
		static function ( $state ) use ( &$current, &$save_calls ) { $save_calls++; $current = $state; return $state; }
	);
	if ( ! is_wp_error( $restore_mismatch ) || 0 !== $save_calls || 99 !== $current['value'] ) { $failures[] = 'Restore must abort before live save when the safety snapshot readback mismatches.'; }
	$lunara_test_update_mode = 'normal';
	lunara_review_finish( 'revision-durability', $failures );
}

function lunara_review_case_authorization_order() {
	global $lunara_test_filters, $lunara_test_can_manage, $lunara_test_dependency_calls, $lunara_test_factory_calls;
	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	add_filter( 'lunara_site_studio_surfaces', static function ( $items ) {
		$items['restricted-callbacks'] = lunara_review_surface( 'restricted-callbacks', 'plugin:restricted', array( 'capability' => 'manage_options' ) );
		return $items;
	} );
	$lunara_test_can_manage = false;
	$lunara_test_dependency_calls = 0;
	$lunara_test_factory_calls = 0;
	$request = new WP_REST_Request( array( 'surface' => 'restricted-callbacks' ), array( 'x-wp-nonce' => 'good-rest-nonce' ) );
	$result = lunara_site_studio_rest_permission( $request, true );
	$failures = array();
	if ( ! is_wp_error( $result ) || 'site_studio_forbidden' !== $result->get_error_code() ) { $failures[] = 'Unauthorized contributed surfaces must return forbidden.'; }
	if ( 0 !== $lunara_test_dependency_calls || 0 !== $lunara_test_factory_calls ) { $failures[] = 'Authorization must run before contributed dependency and adapter factory callbacks.'; }
	$lunara_test_can_manage = true;
	$lunara_test_filters['lunara_site_studio_surfaces'] = array();
	lunara_review_finish( 'authorization-order', $failures );
}

function lunara_review_case_design_token_inheritance() {
	global $lunara_test_options, $lunara_test_theme_mods;
	$lunara_test_options['lunara_design_tokens'] = array( 'colors' => array(), 'fonts' => array() );
	$lunara_test_theme_mods = array( 'lunara_bg_secondary' => '#234567' );
	ob_start();
	lunara_design_tokens_render_panel();
	$panel = ob_get_clean();
	$failures = array();
	if ( false === strpos( $panel, 'name="token_color_bg_secondary" value="#234567"' ) ) { $failures[] = 'A missing token dial must display its effective inherited Customizer value.'; }
	if ( false === strpos( $panel, 'Inherited from Classic controls' ) ) { $failures[] = 'The admin panel must show inherited color provenance.'; }
	if ( false === strpos( $panel, 'Clear Design Token overrides' ) || false !== strpos( $panel, 'Reset to shipped defaults' ) ) { $failures[] = 'Reset wording must describe clearing overrides rather than claiming shipped defaults.'; }

	$posted = array(
		'token_color_gold' => '#112233', 'token_color_gold_light' => '#e0c481', 'token_color_bg_primary' => '#0a1520',
		'token_color_bg_secondary' => '#234567', 'token_color_text' => '#fafbfc', 'token_color_text_muted' => '#a8a8b8',
	);
	if ( ! function_exists( 'lunara_design_tokens_prepare_save' ) ) {
		$failures[] = 'A provenance-aware Design Token save projector must exist.';
	} else {
		$prepared = lunara_design_tokens_prepare_save( $posted, $lunara_test_options['lunara_design_tokens'] );
		if ( '#112233' !== ( isset( $prepared['colors']['gold'] ) ? $prepared['colors']['gold'] : '' ) || isset( $prepared['colors']['bg_secondary'] ) ) { $failures[] = 'Saving another dial must retain inherited color provenance instead of persisting it as an override.'; }
		$lunara_test_options['lunara_design_tokens'] = $prepared;
		ob_start();
		lunara_design_tokens_output_css();
		$css = ob_get_clean();
		if ( false === strpos( $css, '--lunara-bg-secondary:#234567;' ) || false !== strpos( $css, '--lunara-bg-secondary:#0f1d2e;' ) ) { $failures[] = 'Saving another dial must not visually replace an inherited color with the shipped default.'; }
	}
	lunara_review_finish( 'design-token-inheritance', $failures );
}

$lunara_review_cases = array(
	'sticky-ownership' => 'lunara_review_case_sticky_ownership',
	'adapter-only' => 'lunara_review_case_adapter_only',
	'throwing-callbacks' => 'lunara_review_case_throwing_callbacks',
	'preview-capability' => 'lunara_review_case_preview_capability',
	'preview-disabled' => 'lunara_review_case_preview_disabled',
	'state-projection' => 'lunara_review_case_state_projection',
	'revision-durability' => 'lunara_review_case_revision_durability',
	'authorization-order' => 'lunara_review_case_authorization_order',
	'design-token-inheritance' => 'lunara_review_case_design_token_inheritance',
);
$lunara_review_case = isset( $argv[1] ) ? sanitize_key( $argv[1] ) : '';
if ( '' !== $lunara_review_case ) {
	if ( ! isset( $lunara_review_cases[ $lunara_review_case ] ) ) { fwrite( STDERR, "Unknown review case: {$lunara_review_case}\n" ); exit( 2 ); }
	call_user_func( $lunara_review_cases[ $lunara_review_case ] );
}

echo "site-studio foundation runtime: all assertions passed.\n";
