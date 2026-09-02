<?php
/** Behavioral contract for private front-page preview consumption. */
define( 'LUNARA_SITE_STUDIO_RUNTIME_BOOTSTRAP_ONLY', true );
$lunara_preview_user_id = 7;
$lunara_preview_transients = array();
$lunara_preview_options = array( 'show_on_front' => 'page', 'page_on_front' => 42, 'lunara_design_tokens' => array( 'live' => true ) );
$lunara_preview_mods = array();
$lunara_preview_posts = array( 42 => '<!-- wp:lunara/cinematic-hero /--><!-- wp:core/paragraph --><p>Keep me.</p><!-- /wp:core/paragraph --><!-- wp:lunara/pairing-desk /-->' );
$lunara_preview_status = 200;
$lunara_preview_events = array();
$lunara_preview_is_front = true;
$lunara_preview_front_id = 42;
$lunara_preview_now = 2000000000;
$lunara_preview_callback_events = array();
$lunara_preview_collision = '';
$lunara_preview_consumer_mutation = '';
$lunara_preview_warnings = array();
$lunara_preview_admin_bar_calls = array();
$lunara_preview_admin_bar_enabled = true;
$lunara_preview_core_admin_bar_events = array();
$lunara_preview_surface_mutation_target = 'global-design';

function lunara_test_extract_named_function( $source, $function_name ) { $tokens = token_get_all( $source ); for ( $index = 0; $index < count( $tokens ); $index++ ) { if ( ! is_array( $tokens[ $index ] ) || T_FUNCTION !== $tokens[ $index ][0] ) { continue; } $name_index = $index + 1; while ( isset( $tokens[ $name_index ] ) && ( ! is_array( $tokens[ $name_index ] ) || T_STRING !== $tokens[ $name_index ][0] ) ) { ++$name_index; } if ( ! isset( $tokens[ $name_index ][1] ) || $function_name !== $tokens[ $name_index ][1] ) { continue; } $code = ''; $depth = 0; $opened = false; for ( $cursor = $index; $cursor < count( $tokens ); $cursor++ ) { $text = is_array( $tokens[ $cursor ] ) ? $tokens[ $cursor ][1] : $tokens[ $cursor ]; $code .= $text; if ( '{' === $text ) { ++$depth; $opened = true; } elseif ( '}' === $text ) { --$depth; if ( $opened && 0 === $depth ) { return $code; } } } } return ''; }
$lunara_round2_registry_source = file_get_contents( dirname( __DIR__ ) . '/inc/site-studio-registry.php' );
$lunara_round2_get_surface_source = lunara_test_extract_named_function( $lunara_round2_registry_source, 'lunara_site_studio_get_surface' );
$lunara_round2_availability_source = lunara_test_extract_named_function( $lunara_round2_registry_source, 'lunara_site_studio_surface_availability' );
if ( '' === $lunara_round2_get_surface_source || '' === $lunara_round2_availability_source ) { throw new RuntimeException( 'Round-2 fixture could not extract the real registry consumer boundaries.' ); }
eval( preg_replace( '/function\s+lunara_site_studio_get_surface\b/', 'function lunara_round2_real_get_surface', $lunara_round2_get_surface_source, 1 ) );
eval( preg_replace( '/function\s+lunara_site_studio_surface_availability\b/', 'function lunara_round2_real_surface_availability', $lunara_round2_availability_source, 1 ) );

function lunara_round2_throwing_dependency() { global $lunara_test_trace_callbacks, $lunara_preview_events; if ( $lunara_test_trace_callbacks ) { $lunara_preview_events[] = 'dependency'; } throw new RuntimeException( 'fixture dependency secret' ); }
function lunara_round2_absent_adapter_factory() { global $lunara_test_trace_callbacks, $lunara_preview_events; if ( $lunara_test_trace_callbacks ) { $lunara_preview_events[] = 'factory'; } return null; }
function lunara_round2_malformed_adapter_factory() { global $lunara_test_trace_callbacks, $lunara_preview_events; if ( $lunara_test_trace_callbacks ) { $lunara_preview_events[] = 'factory'; } return new stdClass(); }
function lunara_round2_reentrant_adapter_factory( $surface ) { global $lunara_test_trace_callbacks, $lunara_preview_events; if ( $lunara_test_trace_callbacks ) { $lunara_preview_events[] = 'factory'; } return lunara_site_studio_get_adapter( $surface['id'] ); }
function lunara_site_studio_get_surface( $surface ) {
	global $lunara_preview_consumer_mutation;
	$surface = sanitize_key( (string) $surface ); $result = lunara_round2_real_get_surface( $surface );
	if ( ! is_array( $result ) || 'global-design' !== $surface ) { return $result; }
	if ( 'supports-missing' === $lunara_preview_consumer_mutation ) { unset( $result['supports_preview'] ); }
	if ( 'supports-string' === $lunara_preview_consumer_mutation ) { $result['supports_preview'] = 'true'; }
	if ( 'supports-integer' === $lunara_preview_consumer_mutation ) { $result['supports_preview'] = 1; }
	if ( 'dependency-throw' === $lunara_preview_consumer_mutation ) { $result['dependency_callback'] = 'lunara_round2_throwing_dependency'; }
	if ( 'adapter-absent' === $lunara_preview_consumer_mutation ) { $result['adapter_factory'] = 'lunara_round2_absent_adapter_factory'; }
	if ( 'adapter-malformed' === $lunara_preview_consumer_mutation ) { $result['adapter_factory'] = 'lunara_round2_malformed_adapter_factory'; }
	if ( 'adapter-reentrant' === $lunara_preview_consumer_mutation ) { $result['adapter_factory'] = 'lunara_round2_reentrant_adapter_factory'; }
	return $result;
}
function lunara_site_studio_surface_availability( $surface ) {
	global $lunara_preview_consumer_mutation;
	if ( 'availability-missing' === $lunara_preview_consumer_mutation ) { return array( 'reason' => 'fixture', 'message' => 'Fixture unavailable.' ); }
	if ( 'availability-string' === $lunara_preview_consumer_mutation ) { return array( 'available' => 'true', 'reason' => '', 'message' => 'Available.' ); }
	if ( 'availability-integer' === $lunara_preview_consumer_mutation ) { return array( 'available' => 1, 'reason' => '', 'message' => 'Available.' ); }
	if ( 'availability-malformed' === $lunara_preview_consumer_mutation ) { return 'available'; }
	return lunara_round2_real_surface_availability( $surface );
}

function get_current_user_id() { global $lunara_preview_user_id; return $lunara_preview_user_id; }
function current_time( $type, $gmt = false ) { global $lunara_preview_now; return 'timestamp' === $type ? $lunara_preview_now : '2033-05-18 03:33:20'; }
function wp_hash( $value ) { return hash( 'sha256', 'fixture|' . $value ); }
function get_transient( $key ) { global $lunara_preview_transients, $lunara_preview_events; $lunara_preview_events[] = 'transient'; return isset( $lunara_preview_transients[ $key ] ) ? $lunara_preview_transients[ $key ] : false; }
function nocache_headers() { global $lunara_preview_events; $lunara_preview_events[] = 'headers'; }
function status_header( $status ) { global $lunara_preview_status; $lunara_preview_status = (int) $status; }
function show_admin_bar( $show ) { global $lunara_preview_admin_bar_calls, $lunara_preview_admin_bar_enabled; $lunara_preview_admin_bar_calls[] = $show; $lunara_preview_admin_bar_enabled = (bool) $show; }
function is_user_logged_in() { return 0 < get_current_user_id(); }
function is_front_page() { global $lunara_preview_is_front; return $lunara_preview_is_front; }
function get_queried_object_id() { global $lunara_preview_front_id; return $lunara_preview_front_id; }
function get_option( $key, $default = false ) { global $lunara_preview_options; $pre = apply_filters( 'pre_option_' . $key, false, $key, $default ); if ( false !== $pre ) { return $pre; } return array_key_exists( $key, $lunara_preview_options ) ? $lunara_preview_options[ $key ] : $default; }
function get_theme_mod( $key, $default = false ) { global $lunara_preview_mods; $value = array_key_exists( $key, $lunara_preview_mods ) ? $lunara_preview_mods[ $key ] : $default; return apply_filters( 'theme_mod_' . $key, $value ); }
function get_post_field( $field, $post_id ) { global $lunara_preview_posts; return 'post_content' === $field && isset( $lunara_preview_posts[ $post_id ] ) ? $lunara_preview_posts[ $post_id ] : ''; }
function has_block( $name, $content ) { return false !== strpos( (string) $content, '<!-- wp:' . $name ); }
function parse_blocks( $content ) { $parts = preg_split( '/(?=<!-- wp:)/', (string) $content, -1, PREG_SPLIT_NO_EMPTY ); $blocks = array(); foreach ( $parts as $part ) { preg_match( '/^<!-- wp:([^\s\/>]+)/', $part, $match ); $blocks[] = array( 'blockName' => isset( $match[1] ) ? $match[1] : '', '__fixture' => $part ); } return $blocks; }
function serialize_block( $block ) { return isset( $block['__fixture'] ) ? $block['__fixture'] : ''; }
function do_blocks( $content ) { return (string) $content; }
function set_transient() { throw new RuntimeException( 'Preview production attempted set_transient.' ); }
function delete_transient() { throw new RuntimeException( 'Preview production attempted delete_transient.' ); }
function update_option() { throw new RuntimeException( 'Preview production attempted update_option.' ); }
function add_option() { throw new RuntimeException( 'Preview production attempted add_option.' ); }
function delete_option() { throw new RuntimeException( 'Preview production attempted delete_option.' ); }
function set_theme_mod() { throw new RuntimeException( 'Preview production attempted set_theme_mod.' ); }
function remove_theme_mod() { throw new RuntimeException( 'Preview production attempted remove_theme_mod.' ); }
function wp_update_post() { throw new RuntimeException( 'Preview production attempted wp_update_post.' ); }
function wp_cache_delete() { throw new RuntimeException( 'Preview production attempted wp_cache_delete.' ); }
function wp_cache_flush() { throw new RuntimeException( 'Preview production attempted wp_cache_flush.' ); }
function wp_cache_add() { throw new RuntimeException( 'Preview production attempted wp_cache_add.' ); }
function wp_cache_set() { throw new RuntimeException( 'Preview production attempted wp_cache_set.' ); }
function wp_cache_replace() { throw new RuntimeException( 'Preview production attempted wp_cache_replace.' ); }
function clean_post_cache() { throw new RuntimeException( 'Preview production attempted clean_post_cache.' ); }
function clean_term_cache() { throw new RuntimeException( 'Preview production attempted clean_term_cache.' ); }
function clean_object_term_cache() { throw new RuntimeException( 'Preview production attempted clean_object_term_cache.' ); }
function wp_insert_post() { throw new RuntimeException( 'Preview production attempted wp_insert_post.' ); }
function wp_delete_post() { throw new RuntimeException( 'Preview production attempted wp_delete_post.' ); }
function update_post_meta() { throw new RuntimeException( 'Preview production attempted update_post_meta.' ); }
function delete_post_meta() { throw new RuntimeException( 'Preview production attempted delete_post_meta.' ); }

function lunara_preview_core_admin_bar_bump() { global $lunara_preview_core_admin_bar_events; $lunara_preview_core_admin_bar_events[] = 'core-admin-bump'; }
function lunara_preview_core_admin_bar_init() { global $lunara_preview_admin_bar_enabled, $lunara_preview_core_admin_bar_events; if ( ! $lunara_preview_admin_bar_enabled ) { return; } $lunara_preview_core_admin_bar_events[] = 'core-admin-init'; add_action( 'wp_enqueue_scripts', 'lunara_preview_core_admin_bar_bump', 0 ); }

require __DIR__ . '/site-studio-runtime.php';
require dirname( __DIR__ ) . '/inc/home-blocks.php';

/**
 * Literal preview contracts. These values intentionally do not derive from
 * the production allowlist so route, owner, query, parameter, or marker drift
 * causes this runtime to fail.
 *
 * @return array<string,array<string,mixed>>
 */
function lunara_preview_surface_specs() {
	return array(
		'global-design'      => array( 'owner' => 'theme:global-design', 'route' => '/', 'query' => 'lunara_global_design_preview', 'params' => array(), 'storage' => 'site-studio', 'markers' => array() ),
		'homepage-structure' => array( 'owner' => 'theme:homepage-structure', 'route' => '/', 'query' => 'lunara_homepage_preview', 'params' => array(), 'storage' => 'site-studio', 'markers' => array( 'hero', 'latest-reviews', 'pairing-desk', 'dispatch', 'oscar-picks', 'oscar-facts' ) ),
		'lunara-method'      => array( 'owner' => 'theme:lunara-method', 'route' => '/', 'query' => 'lunara_method_preview', 'params' => array(), 'storage' => 'site-studio', 'markers' => array( 'pairing-desk' ) ),
		'reviews-archive'    => array( 'owner' => 'theme:reviews-archive', 'route' => '/reviews/', 'query' => 'lunara_reviews_preview', 'params' => array(), 'storage' => 'provider', 'markers' => array( 'hero', 'grid', 'pagination', 'pairing-desk' ) ),
		'journal-archive'    => array( 'owner' => 'theme:journal-archive', 'route' => '/journal/', 'query' => 'lunara_journal_preview', 'params' => array(), 'storage' => 'provider', 'markers' => array( 'hero', 'deskbar', 'filters', 'toolbar', 'grid', 'retention', 'pagination' ) ),
		'review-single'      => array( 'owner' => 'theme:review-single', 'route' => '/reviews/sinners-2025/', 'query' => 'lunara_review_single_preview', 'params' => array(), 'storage' => 'site-studio', 'markers' => array( 'hero', 'criticism', 'debrief', 'pair-it-with' ) ),
		'utility-search'     => array( 'owner' => 'theme:utility-search', 'route' => '/search/', 'query' => 'lunara_utility_search_preview', 'params' => array( 'q' => 'Lunara' ), 'storage' => 'site-studio', 'markers' => array( 'search-command', 'direct-matches', 'result-run', 'recovery' ) ),
		'site-footer'        => array( 'owner' => 'theme:site-footer', 'route' => '/', 'query' => 'lunara_footer_preview', 'params' => array(), 'storage' => 'site-studio', 'markers' => array( 'footer' ) ),
	);
}

/** Return a recognizable candidate whose preview-only values can be observed. */
function lunara_preview_candidate( $surface ) {
	$state = lunara_preview_state( $surface );
	if ( 'reviews-archive' === $surface ) { $state['kicker'] = 'Private Reviews Preview'; }
	if ( 'journal-archive' === $surface ) { $state['kicker'] = 'Private Journal Preview'; }
	if ( 'review-single' === $surface ) { $state['review']['density'] = 'compact'; $state['pairing']['columns'] = 2; }
	if ( 'utility-search' === $surface ) { $state['presentation']['density'] = 'compact'; $state['focus']['lead'] = 'journal'; $state['geometry']['section_gap'] = 34; }
	if ( 'site-footer' === $surface ) { $state['brand']['show_logo'] = false; $state['brand']['tagline'] = 'Private footer preview'; }
	return $state;
}

/** Provider-owned preview storage seam, shaped like the real archive stores. */
function lunara_preview_provider_config( $surface, $token ) {
	$prefixes = array( 'reviews-archive' => 'lunara_reviews_archive_preview_', 'journal-archive' => 'lunara_journal_archive_preview_' );
	if ( ! isset( $prefixes[ $surface ] ) ) { return false; }
	$record = get_transient( $prefixes[ $surface ] . hash( 'sha256', $token ) );
	$user = get_current_user_id();
	if ( ! is_array( $record ) || ! isset( $record['user_id'], $record['token_hash'], $record['expires'], $record['config'] ) || absint( $record['user_id'] ) !== absint( $user ) || ! hash_equals( wp_hash( $token . '|' . absint( $user ) ), (string) $record['token_hash'] ) || absint( $record['expires'] ) <= current_time( 'timestamp', true ) || ! is_array( $record['config'] ) ) { return false; }
	return $record['config'];
}
function lunara_reviews_archive_studio_get_preview_config( $token ) { return lunara_preview_provider_config( 'reviews-archive', $token ); }
function lunara_journal_archive_studio_get_preview_config( $token ) { return lunara_preview_provider_config( 'journal-archive', $token ); }

/** Trace every real projection callback, including the five 3.2.57 surfaces. */
function lunara_preview_trace_schema( $surface ) {
	global $lunara_test_trace_callbacks, $lunara_preview_events;
	if ( $lunara_test_trace_callbacks ) { $lunara_preview_events[] = 'projection'; }
	$callbacks = array(
		'global-design'      => 'lunara_site_studio_global_design_state_schema',
		'homepage-structure' => 'lunara_site_studio_homepage_structure_state_schema',
		'lunara-method'      => 'lunara_site_studio_lunara_method_state_schema',
		'reviews-archive'    => 'lunara_site_studio_reviews_archive_state_schema',
		'journal-archive'    => 'lunara_site_studio_journal_archive_state_schema',
		'review-single'      => 'lunara_site_studio_review_single_state_schema',
		'utility-search'     => 'lunara_site_studio_utility_search_state_schema',
		'site-footer'        => 'lunara_site_studio_footer_state_schema',
	);
	return isset( $callbacks[ $surface['id'] ] ) ? call_user_func( $callbacks[ $surface['id'] ] ) : array();
}
add_filter( 'lunara_site_studio_surfaces', static function ( $surfaces ) { foreach ( array( 'reviews-archive', 'journal-archive', 'review-single', 'utility-search', 'site-footer' ) as $surface_id ) { if ( isset( $surfaces[ $surface_id ] ) ) { $surfaces[ $surface_id ]['state_schema_callback'] = 'lunara_preview_trace_schema'; } } return $surfaces; }, 30 );

function lunara_preview_callback_event( $name ) { global $lunara_preview_callback_events, $lunara_preview_collision; $lunara_preview_callback_events[] = $name; if ( $name === $lunara_preview_collision ) { throw new RuntimeException( 'Earlier callback escaped private guard: ' . $name ); } }
function lunara_handle_festival_qr_redirect() { lunara_preview_callback_event( 'festival-qr' ); }
function lunara_journal_archive_studio_guard_preview_request() { lunara_preview_callback_event( 'journal-guard' ); }
function lunara_reviews_archive_studio_guard_preview_request() { lunara_preview_callback_event( 'reviews-guard' ); }
function lunara_oscars_portal_studio_guard_preview_request() { lunara_preview_callback_event( 'oscars-guard' ); }
function lunara_send_home_cinematic_hero_preload_header() { lunara_preview_callback_event( 'hero-preload' ); }
function lunara_search_command_template_redirect() { lunara_preview_callback_event( 'search-command' ); }
add_action( 'template_redirect', 'lunara_preview_core_admin_bar_init', 0 );
add_action( 'template_redirect', 'lunara_handle_festival_qr_redirect', 0 );
add_action( 'template_redirect', 'lunara_journal_archive_studio_guard_preview_request', 0 );
add_action( 'template_redirect', 'lunara_reviews_archive_studio_guard_preview_request', 0 );
add_action( 'template_redirect', 'lunara_oscars_portal_studio_guard_preview_request', 0 );
add_action( 'template_redirect', 'lunara_send_home_cinematic_hero_preload_header', 0 );
add_action( 'template_redirect', 'lunara_search_command_template_redirect', 0 );
require dirname( __DIR__ ) . '/inc/site-studio-preview.php';

$lunara_preview_surface_mutation = '';
add_filter( 'lunara_site_studio_surfaces', static function ( $surfaces ) { global $lunara_preview_surface_mutation, $lunara_preview_surface_mutation_target; $target = $lunara_preview_surface_mutation_target; if ( $lunara_preview_surface_mutation && isset( $surfaces[ $target ] ) ) { if ( 'owner' === $lunara_preview_surface_mutation ) { $surfaces[ $target ]['owner'] = 'theme:foreign'; } if ( 'query' === $lunara_preview_surface_mutation ) { $surfaces[ $target ]['preview_query_arg'] = 'other_preview'; } if ( 'route' === $lunara_preview_surface_mutation ) { $surfaces[ $target ]['preview_route'] = '/wrong-preview-route/'; } if ( 'params' === $lunara_preview_surface_mutation ) { $surfaces[ $target ]['preview_params'] = array( 'q' => 'Changed' ); } } return $surfaces; }, 40 );
$lunara_preview_base_filters = $lunara_test_filters;
$lunara_preview_base_actions = $lunara_test_actions;
add_action( 'lunara_site_studio_private_no_store_sent', static function () { global $lunara_preview_events; $lunara_preview_events[] = 'private'; } );
$lunara_preview_base_actions = $lunara_test_actions;

function lunara_preview_state( $surface ) {
	$adapter = lunara_site_studio_get_adapter( $surface );
	return lunara_site_studio_call_adapter( $adapter, 'read_state' );
}
function lunara_preview_record( $surface, $token, $state = null ) {
	$specs = lunara_preview_surface_specs(); $spec = $specs[ $surface ];
	$user = get_current_user_id(); $owner = $spec['owner']; $route = $spec['route'];
	return array( 'user_id' => $user, 'surface' => $surface, 'owner' => $owner, 'route' => $route, 'token_hash' => wp_hash( $token . '|' . $user . '|' . $surface . '|' . $owner . '|' . $route ), 'expires' => current_time( 'timestamp', true ) + 600, 'state' => null === $state ? lunara_preview_state( $surface ) : $state );
}
function lunara_preview_reset( $surface, $token, $instance, $query = null, $get = null, $state = null ) {
	global $lunara_test_filters, $lunara_test_actions, $lunara_preview_base_filters, $lunara_preview_base_actions, $lunara_preview_transients, $lunara_preview_events, $lunara_preview_status, $lunara_preview_user_id, $lunara_test_can_edit, $lunara_test_dependency_ready, $lunara_test_adapter_failure, $lunara_test_enqueued_scripts, $lunara_test_localized, $lunara_preview_is_front, $lunara_preview_front_id, $lunara_preview_surface_mutation, $lunara_preview_surface_mutation_target, $lunara_test_trace_callbacks, $lunara_test_wp_die_args, $lunara_preview_callback_events, $lunara_preview_collision, $lunara_preview_consumer_mutation, $lunara_preview_warnings, $lunara_preview_admin_bar_calls, $lunara_preview_admin_bar_enabled, $lunara_preview_core_admin_bar_events;
	$specs = lunara_preview_surface_specs(); $spec = $specs[ $surface ];
	$lunara_test_filters = $lunara_preview_base_filters; $lunara_test_actions = $lunara_preview_base_actions;
	$lunara_preview_events = array(); $lunara_preview_status = 200; $lunara_preview_user_id = 7; $lunara_test_can_edit = true; $lunara_test_dependency_ready = true; $lunara_test_adapter_failure = ''; $lunara_test_enqueued_scripts = array(); $lunara_test_localized = array(); $lunara_preview_is_front = '/' === $spec['route']; $lunara_preview_front_id = 42; $lunara_preview_surface_mutation = ''; $lunara_preview_surface_mutation_target = 'global-design'; $lunara_test_trace_callbacks = false; $lunara_test_wp_die_args = array(); $lunara_preview_callback_events = array(); $lunara_preview_collision = ''; $lunara_preview_consumer_mutation = ''; $lunara_preview_warnings = array(); $lunara_preview_admin_bar_calls = array(); $lunara_preview_admin_bar_enabled = true; $lunara_preview_core_admin_bar_events = array();
	$state = null === $state ? lunara_preview_state( $surface ) : $state;
	$values = $spec['params']; $values[ $spec['query'] ] = $token; $values['lunara_site_studio_instance'] = $instance;
	if ( null === $query ) { $segments = array(); foreach ( $values as $key => $value ) { $segments[] = rawurlencode( $key ) . '=' . rawurlencode( $value ); } $query = implode( '&', $segments ); }
	$_GET = null === $get ? $values : $get;
	$home_path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ); if ( '' === $home_path ) { $home_path = '/'; } $request_path = '/' === $spec['route'] ? $home_path : rtrim( $home_path, '/' ) . '/' . ltrim( $spec['route'], '/' );
	$_SERVER['QUERY_STRING'] = $query; $_SERVER['REQUEST_METHOD'] = 'GET'; $_SERVER['HTTP_HOST'] = 'example.test'; $_SERVER['REQUEST_URI'] = $request_path . '?' . $query; $_SERVER['HTTPS'] = 'on'; $_SERVER['SERVER_PORT'] = '443';
	if ( 'provider' === $spec['storage'] ) {
		$prefix = 'reviews-archive' === $surface ? 'lunara_reviews_archive_preview_' : 'lunara_journal_archive_preview_';
		$lunara_preview_transients = array( $prefix . hash( 'sha256', $token ) => array( 'user_id' => get_current_user_id(), 'token_hash' => wp_hash( $token . '|' . get_current_user_id() ), 'expires' => current_time( 'timestamp', true ) + 600, 'config' => $state ) );
	} else {
		$lunara_preview_transients = array( 'lunara_site_studio_preview_' . hash( 'sha256', $token ) => lunara_preview_record( $surface, $token, $state ) );
	}
}
function lunara_preview_run() {
	global $lunara_preview_status, $lunara_test_wp_die_args;
	try { lunara_site_studio_handle_private_preview(); return array( 'ok' => true, 'status' => $lunara_preview_status, 'message' => '', 'die_args' => $lunara_test_wp_die_args ); }
	catch ( Throwable $error ) { return array( 'ok' => false, 'status' => $lunara_preview_status, 'message' => $error->getMessage(), 'die_args' => $lunara_test_wp_die_args ); }
}
function lunara_preview_dispatch() { global $lunara_preview_status, $lunara_test_wp_die_args; try { do_action( 'template_redirect' ); return array( 'ok' => true, 'status' => $lunara_preview_status, 'message' => '', 'die_args' => $lunara_test_wp_die_args ); } catch ( Throwable $error ) { return array( 'ok' => false, 'status' => $lunara_preview_status, 'message' => $error->getMessage(), 'die_args' => $lunara_test_wp_die_args ); } }

lunara_test_assert( function_exists( 'lunara_site_studio_preview_instance_query_arg' ) && 'lunara_site_studio_instance' === lunara_site_studio_preview_instance_query_arg(), 'The public preview module must own the exact reserved instance helper.' );
lunara_test_assert( function_exists( 'lunara_site_studio_handle_private_preview' ), 'The public template_redirect handler must exist.' );
$preview_actions = array_values( array_filter( $lunara_test_actions, static function ( $item ) { return 'template_redirect' === $item['hook']; } ) );
$preview_callbacks = array_column( $preview_actions, 'callback' );
lunara_test_assert( in_array( 'lunara_site_studio_handle_private_preview', $preview_callbacks, true ) && in_array( 'lunara_preview_core_admin_bar_init', $preview_callbacks, true ), 'The public guard and the Core-like admin-bar initializer must both remain registered on template_redirect.' );
$preview_priorities = array(); foreach ( $preview_actions as $preview_action ) { $preview_priorities[ $preview_action['callback'] ] = $preview_action['priority']; }
lunara_test_assert( isset( $preview_priorities['lunara_site_studio_handle_private_preview'], $preview_priorities['lunara_preview_core_admin_bar_init'] ) && -1 === $preview_priorities['lunara_site_studio_handle_private_preview'] && 0 === $preview_priorities['lunara_preview_core_admin_bar_init'] && $preview_priorities['lunara_site_studio_handle_private_preview'] < $preview_priorities['lunara_preview_core_admin_bar_init'], 'Site Studio must register at exact priority -1 ahead of modeled Core admin-bar initialization at exact priority 0.' );

$token = '123e4567-e89b-42d3-a456-426614174111'; $instance = '123e4567-e89b-42d3-a456-426614174000:1';
$_GET = array(); $_SERVER['QUERY_STRING'] = ''; $_SERVER['REQUEST_URI'] = '/'; $before_filters = $lunara_test_filters; $before_scripts = $lunara_test_enqueued_scripts; $before_events = $lunara_preview_events; $normal = lunara_preview_run();
lunara_test_assert( $normal['ok'] && $before_filters === $lunara_test_filters && $before_scripts === $lunara_test_enqueued_scripts && $before_events === $lunara_preview_events && array() === $lunara_preview_admin_bar_calls, 'A normal Homepage request must be a total no-op, including WordPress admin-bar state.' );
$lunara_preview_callback_events = array(); $normal_dispatch = lunara_preview_dispatch(); do_action( 'wp_enqueue_scripts' ); lunara_test_assert( $normal_dispatch['ok'] && array( 'festival-qr', 'journal-guard', 'reviews-guard', 'oscars-guard', 'hero-preload', 'search-command' ) === $lunara_preview_callback_events && $before_events === $lunara_preview_events && array( 'core-admin-init', 'core-admin-bump' ) === $lunara_preview_core_admin_bar_events && array() === $lunara_preview_admin_bar_calls, 'A normal request must retain Core admin-bar initialization/bump and every pre-existing priority-zero callback without private-preview effects.' );

$generic = null;
$malformed = array(
	array( 'lunara+global+design+preview=' . $token, array( 'lunara_global_design_preview' => $token ) ),
	array( 'lunara_!global_design_preview=' . $token, array( 'lunara_global_design_preview' => $token ) ),
	array( 'lunara_%E2%98%83global_design_preview=' . $token, array( 'lunara_global_design_preview' => $token ) ),
	array( 'lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_site_studio_instance' => $instance ) ),
	array( 'lunara_global_design_preview=' . $token, array( 'lunara_global_design_preview' => $token ) ),
	array( 'lunara_global_design_preview=' . $token . '&lunara_method_preview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_global_design_preview' => $token, 'lunara_method_preview' => $token, 'lunara_site_studio_instance' => $instance ) ),
	array( 'lunara_global_design_preview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ) . '&extra=1', array( 'lunara_global_design_preview' => $token, 'lunara_site_studio_instance' => $instance, 'extra' => '1' ) ),
	array( 'lunara_global_design_preview=' . $token . '&lunara_global_design_preview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_global_design_preview' => $token, 'lunara_site_studio_instance' => $instance ) ),
	array( 'lunara_global_design_preview%5B%5D=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_global_design_preview' => array( $token ), 'lunara_site_studio_instance' => $instance ) ),
	array( 'lunara%5Fglobal%5Fdesign%5Fpreview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_global_design_preview' => $token, 'lunara_site_studio_instance' => $instance ) ),
	array( 'LUNARA_GLOBAL_DESIGN_PREVIEW=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'LUNARA_GLOBAL_DESIGN_PREVIEW' => $token, 'lunara_site_studio_instance' => $instance ) ),
	array( 'lunara.global.design.preview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_global_design_preview' => $token, 'lunara_site_studio_instance' => $instance ) ),
	array( 'lunara global design preview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_global_design_preview' => $token, 'lunara_site_studio_instance' => $instance ) ),
	array( 'lunara+global+design+preview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_global_design_preview' => $token, 'lunara_site_studio_instance' => $instance ) ),
	array( 'lunara_!global_design_preview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_global_design_preview' => $token, 'lunara_site_studio_instance' => $instance ) ),
	array( 'lunara_%E2%98%83global_design_preview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_global_design_preview' => $token, 'lunara_site_studio_instance' => $instance ) ),
	array( 'lunara_global_design_preview%ZZ=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_global_design_preview_ZZ' => $token, 'lunara_site_studio_instance' => $instance ) ),
	array( 'lunara_global_design_preview=' . $token . '&&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_global_design_preview' => $token, 'lunara_site_studio_instance' => $instance ) ),
	array( 'lunara_global_design_preview=' . rawurlencode( ' ' . $token ) . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_global_design_preview' => ' ' . $token, 'lunara_site_studio_instance' => $instance ) ),
	array( 'lunara_global_design_preview=' . strtoupper( $token ) . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_global_design_preview' => strtoupper( $token ), 'lunara_site_studio_instance' => $instance ) ),
	array( 'lunara_global_design_preview=123e4567-e89b-12d3-a456-426614174111&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_global_design_preview' => '123e4567-e89b-12d3-a456-426614174111', 'lunara_site_studio_instance' => $instance ) ),
	array( 'lunara_global_design_preview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( 'bad:1' ), array( 'lunara_global_design_preview' => $token, 'lunara_site_studio_instance' => 'bad:1' ) ),
	array( 'lunara_global_design_preview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( '123e4567-e89b-42d3-a456-426614174000:' . str_repeat( '9', 50 ) ), array( 'lunara_global_design_preview' => $token, 'lunara_site_studio_instance' => '123e4567-e89b-42d3-a456-426614174000:' . str_repeat( '9', 50 ) ) ),
	array( 'lunara_global_design_preview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_global_design_preview' => $token, 'lunara_site_studio_instance' => $instance, 'injected_mirror' => '1' ) ),
);
foreach ( $malformed as $case ) { lunara_preview_reset( 'global-design', $token, $instance, $case[0], $case[1] ); $lunara_test_trace_callbacks = true; $result = lunara_preview_run(); $generic = null === $generic ? $result['message'] : $generic; lunara_test_assert( ! $result['ok'] && 403 === $result['status'] && array( 'response' => 403 ) === $result['die_args'] && $generic === $result['message'] && array_slice( $lunara_preview_events, 0, 2 ) === array( 'headers', 'private' ) && array() === array_values( array_intersect( $lunara_preview_events, array( 'dependency', 'factory', 'transient', 'validate', 'projection' ) ) ) && array() === $lunara_preview_admin_bar_calls, 'Every malformed preview shape must receive the same explicit Core 403 after private headers and before dependency/adapter/state work, without changing admin-bar state.' ); }

foreach ( array( 'festival-qr', 'reviews-guard', 'search-command' ) as $collision ) { lunara_preview_reset( 'global-design', $token, $instance, 'lunara_global_design_preview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ) . '&lunara_qr=festival', array( 'lunara_global_design_preview' => $token, 'lunara_site_studio_instance' => $instance, 'lunara_qr' => 'festival' ) ); $lunara_preview_collision = $collision; $collision_result = lunara_preview_dispatch(); lunara_test_assert( ! $collision_result['ok'] && 403 === $collision_result['status'] && $generic === $collision_result['message'] && array() === $lunara_preview_callback_events && array() === $lunara_preview_admin_bar_calls, 'A preview-shaped collision must be denied by Site Studio before ' . $collision . ' can redirect, render, exit, or mutate admin-bar state.' ); }

lunara_preview_reset( 'global-design', $token, $instance, 'lunara_site_studio_instance=' . rawurlencode( $instance ) . '&lunara_global_design_preview=' . $token, array( 'lunara_site_studio_instance' => $instance, 'lunara_global_design_preview' => $token ) ); lunara_test_assert( lunara_preview_run()['ok'], 'The exact two canonical private-preview keys must remain valid in reversed order.' );

$consumer_denials = array( 'supports-missing', 'supports-string', 'supports-integer', 'availability-missing', 'availability-string', 'availability-integer', 'availability-malformed' );
set_error_handler( static function ( $severity, $message ) { global $lunara_preview_warnings; $lunara_preview_warnings[] = $message; return true; } );
foreach ( $consumer_denials as $kind ) {
	lunara_preview_reset( 'global-design', $token, $instance ); $lunara_preview_consumer_mutation = $kind; $lunara_test_trace_callbacks = true; $result = lunara_preview_dispatch(); do_action( 'wp_enqueue_scripts' );
	$private_index = array_search( 'private', $lunara_preview_events, true ); $denied_order = false !== $private_index; foreach ( array( 'dependency', 'factory', 'transient', 'validate', 'projection' ) as $event_name ) { foreach ( array_keys( $lunara_preview_events, $event_name, true ) as $event_index ) { $denied_order = $denied_order && $event_index > $private_index; } }
	lunara_test_assert( ! $result['ok'] && 403 === $result['status'] && array( 'response' => 403 ) === $result['die_args'] && $generic === $result['message'] && array_slice( $lunara_preview_events, 0, 2 ) === array( 'headers', 'private' ) && $denied_order && array() === $lunara_preview_callback_events && array() === $lunara_test_enqueued_scripts && array() === $lunara_test_localized && array() === $lunara_preview_warnings && array() === $lunara_preview_admin_bar_calls, $kind . ' surface/availability shape must receive the identical warning-free private 403 with no live fallthrough, child enqueue, or admin-bar mutation.' );
}
restore_error_handler();

foreach ( array( 'dependency-throw', 'factory-throw', 'adapter-absent', 'adapter-malformed', 'adapter-reentrant' ) as $kind ) {
	lunara_preview_reset( 'global-design', $token, $instance ); if ( 'factory-throw' === $kind ) { $lunara_test_adapter_failure = 'factory-throw'; } else { $lunara_preview_consumer_mutation = $kind; } $lunara_test_trace_callbacks = true; $result = lunara_preview_dispatch(); do_action( 'wp_enqueue_scripts' );
	$private_index = array_search( 'private', $lunara_preview_events, true ); $denied_order = false !== $private_index; foreach ( array( 'dependency', 'factory', 'transient', 'validate', 'projection' ) as $event_name ) { foreach ( array_keys( $lunara_preview_events, $event_name, true ) as $event_index ) { $denied_order = $denied_order && $event_index > $private_index; } }
	lunara_test_assert( ! $result['ok'] && 403 === $result['status'] && array( 'response' => 403 ) === $result['die_args'] && $generic === $result['message'] && array_slice( $lunara_preview_events, 0, 2 ) === array( 'headers', 'private' ) && $denied_order && array() === $lunara_preview_callback_events && array() === $lunara_test_enqueued_scripts && array() === $lunara_test_localized && array() === $lunara_preview_admin_bar_calls, $kind . ' dependency/adapter denial must be indistinguishable, private-first, live-inert, child-inert, and admin-bar-inert.' );
}

foreach ( array( 'global-design', 'homepage-structure', 'lunara-method', 'reviews-archive', 'journal-archive', 'review-single', 'utility-search', 'site-footer' ) as $surface ) {
	$surface_specs = lunara_preview_surface_specs(); $surface_spec = $surface_specs[ $surface ]; lunara_preview_reset( $surface, $token, $instance ); $candidate = lunara_preview_candidate( $surface );
	lunara_preview_reset( $surface, $token, $instance, null, null, $candidate ); $canonical_before = serialize( array( $lunara_preview_options, $lunara_preview_mods, $lunara_preview_posts, $lunara_preview_transients ) ); $lunara_test_trace_callbacks = true; $accepted = lunara_preview_dispatch();
	lunara_test_assert( array( false ) === $lunara_preview_admin_bar_calls && array() === $lunara_preview_core_admin_bar_events, $surface . ' must suppress the admin bar before Core initialization can install or emit its bump.' );
	$private_index = array_search( 'private', $lunara_preview_events, true ); $work_events = array( 'dependency', 'factory', 'transient', 'validate', 'projection' ); $work_after_private = true; foreach ( $work_events as $work_event ) { $work_index = array_search( $work_event, $lunara_preview_events, true ); $work_after_private = $work_after_private && false !== $work_index && $work_index > $private_index; }
	lunara_test_assert( $accepted['ok'] && 200 === $accepted['status'] && array_slice( $lunara_preview_events, 0, 2 ) === array( 'headers', 'private' ) && $work_after_private, $surface . ' must authorize only after private headers and before every dependency/factory/transient/validation/projection callback.' );
	do_action( 'wp_enqueue_scripts' ); lunara_test_assert( array() === $lunara_preview_core_admin_bar_events, $surface . ' authorized preview must leave the Core admin-bar bump inert.' );
	lunara_test_assert( $canonical_before === serialize( array( $lunara_preview_options, $lunara_preview_mods, $lunara_preview_posts, $lunara_preview_transients ) ), $surface . ' must leave byte-identical canonical options/mods/posts/transient state.' );
	lunara_test_assert( false === apply_filters( 'redirect_canonical', 'https://example.test/canonical' ), $surface . ' authorized preview must suppress redirect_canonical only for this request.' );
	lunara_test_assert( isset( $lunara_test_enqueued_scripts['lunara-site-studio-preview'] ) && isset( $lunara_test_localized['LunaraSiteStudioPreviewConfig'] ), $surface . ' must enqueue the dedicated child bridge only after authorization.' );
	$child = $lunara_test_localized['LunaraSiteStudioPreviewConfig']; $keys = array_keys( $child ); sort( $keys );
	lunara_test_assert( $keys === array( 'instance', 'markers', 'protocol', 'surface', 'type', 'version' ) && 'lunara-site-studio/v1' === $child['protocol'] && 1 === $child['version'] && 'select-section' === $child['type'] && $surface === $child['surface'] && $instance === $child['instance'] && $surface_spec['markers'] === $child['markers'] && false === strpos( wp_json_encode( $child ), $token ) && false === strpos( wp_json_encode( $child ), 'state' ), $surface . ' child config must expose only the exact six safe keys and literal marker contract.' );
	if ( 'global-design' === $surface ) { $tokens = get_option( 'lunara_design_tokens', array() ); lunara_test_assert( isset( $tokens['colors']['gold_light'] ) && ! isset( $tokens['colors']['gold'] ) && isset( $tokens['fonts']) && false === strpos( wp_json_encode( $tokens ), 'effective' ), 'Global preview must substitute only non-null canonical overrides in memory.' ); }
	if ( 'homepage-structure' === $surface ) { lunara_test_assert( '' === get_theme_mod( 'lunara_home_section_order_preset', 'missing' ), 'Homepage preview must supply its exact request-local theme mod, including the canonical empty preset.' ); $content = lunara_home_front_page_content( 42 ); lunara_test_assert( false !== strpos( $content, 'wp:lunara/cinematic-hero' ) && false !== strpos( $content, 'Keep me') && false !== strpos( $content, 'wp:lunara/pairing-desk' ), 'Block-mode Homepage preview must compose request-only content while preserving unknown content.' ); lunara_test_assert( is_string( $content ) && $lunara_preview_posts[42] !== $content && '<!-- wp:lunara/cinematic-hero /--><!-- wp:core/paragraph --><p>Keep me.</p><!-- /wp:core/paragraph --><!-- wp:lunara/pairing-desk /-->' === $lunara_preview_posts[42], 'Homepage preview seam must return a distinct request-only composition while preserving exact canonical post bytes.' ); }
	if ( 'lunara-method' === $surface ) { lunara_test_assert( 0 === get_theme_mod( 'lunara_home_pairing_desk_review_id', 99 ) && 44 === get_theme_mod( 'lunara_home_pairing_desk_backdrop_id', 0 ) && '' === get_theme_mod( 'lunara_home_pairing_desk_kicker', 'fallback' ), 'Method preview must preserve exact zero/empty candidate values in memory.' ); }
	if ( 'reviews-archive' === $surface ) { lunara_test_assert( $candidate === lunara_reviews_archive_studio_get_preview_config( $token ) && 'Private Reviews Preview' === lunara_reviews_archive_studio_get_preview_config( $token )['kicker'], 'Reviews preview must remain available through its provider-owned request-local read seam.' ); }
	if ( 'journal-archive' === $surface ) { lunara_test_assert( $candidate === lunara_journal_archive_studio_get_preview_config( $token ) && 'Private Journal Preview' === lunara_journal_archive_studio_get_preview_config( $token )['kicker'], 'Journal preview must remain available through its provider-owned request-local read seam.' ); }
	if ( 'review-single' === $surface ) { lunara_test_assert( 'compact' === get_theme_mod( 'lunara_review_single_density', 'editorial' ) && 2 === get_theme_mod( 'lunara_review_pair_with_columns', 1 ), 'Review Single must install its candidate mods only for the authorized request.' ); }
	if ( 'utility-search' === $surface ) { lunara_test_assert( 'compact' === get_theme_mod( 'lunara_utility_search_density', 'editorial' ) && 'journal' === get_theme_mod( 'lunara_utility_search_lead_focus', 'balanced' ) && 34 === get_theme_mod( 'lunara_utility_section_gap', 42 ), 'Utility Search must install its candidate mods only for the authorized exact Search request.' ); }
	if ( 'site-footer' === $surface ) { lunara_test_assert( false === get_theme_mod( 'lunara_footer_show_logo', true ) && 'Private footer preview' === get_theme_mod( 'lunara_footer_tagline', '' ), 'Footer must install its candidate mods only for the authorized front-page request.' ); }
}

// Every hardcoded preview owner must agree with the normalized registry owner.
foreach ( array_keys( lunara_preview_surface_specs() ) as $surface ) {
	lunara_preview_reset( $surface, $token, $instance ); $canonical_before = serialize( array( $lunara_preview_options, $lunara_preview_mods, $lunara_preview_posts, $lunara_preview_transients ) ); $lunara_preview_surface_mutation_target = $surface; $lunara_preview_surface_mutation = 'owner'; $owner_denial = lunara_preview_run();
	lunara_test_assert( ! $owner_denial['ok'] && 403 === $owner_denial['status'] && $generic === $owner_denial['message'] && array() === $lunara_preview_admin_bar_calls && array() === $lunara_test_enqueued_scripts && array() === $lunara_test_localized && $canonical_before === serialize( array( $lunara_preview_options, $lunara_preview_mods, $lunara_preview_posts, $lunara_preview_transients ) ), $surface . ' must deny registry-owner drift before exposing state, bridge config, or canonical mutation.' );
}

// Site-Studio-owned records must also remain bound to their stored owner.
foreach ( array( 'global-design', 'homepage-structure', 'lunara-method', 'review-single', 'utility-search', 'site-footer' ) as $surface ) {
	lunara_preview_reset( $surface, $token, $instance ); $specs = lunara_preview_surface_specs(); $spec = $specs[ $surface ]; $key = 'lunara_site_studio_preview_' . hash( 'sha256', $token ); $lunara_preview_transients[ $key ]['owner'] = 'theme:foreign'; $lunara_preview_transients[ $key ]['token_hash'] = wp_hash( $token . '|' . get_current_user_id() . '|' . $surface . '|theme:foreign|' . $spec['route'] ); $stored_owner_denial = lunara_preview_run();
	lunara_test_assert( ! $stored_owner_denial['ok'] && 403 === $stored_owner_denial['status'] && $generic === $stored_owner_denial['message'] && array() === $lunara_preview_admin_bar_calls && array() === $lunara_test_localized, $surface . ' must deny a self-consistent token record owned by another canonical owner.' );
}

// Utility Search has the only fixed preview parameter; every byte and registry
// contract must remain exact before dependency, adapter, or transient work.
$utility_query = 'q=Lunara&lunara_utility_search_preview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance );
$utility_get = array( 'q' => 'Lunara', 'lunara_utility_search_preview' => $token, 'lunara_site_studio_instance' => $instance );
$utility_query_denials = array(
	'changed fixed query value' => array( 'q=lunara&lunara_utility_search_preview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'q' => 'lunara', 'lunara_utility_search_preview' => $token, 'lunara_site_studio_instance' => $instance ) ),
	'missing fixed query value' => array( 'lunara_utility_search_preview=' . $token . '&lunara_site_studio_instance=' . rawurlencode( $instance ), array( 'lunara_utility_search_preview' => $token, 'lunara_site_studio_instance' => $instance ) ),
	'extra query value' => array( $utility_query . '&extra=1', $utility_get + array( 'extra' => '1' ) ),
);
foreach ( $utility_query_denials as $label => $case ) {
	lunara_preview_reset( 'utility-search', $token, $instance, $case[0], $case[1] ); $utility_denial = lunara_preview_run();
	lunara_test_assert( ! $utility_denial['ok'] && 403 === $utility_denial['status'] && $generic === $utility_denial['message'] && array() === array_values( array_intersect( $lunara_preview_events, array( 'dependency', 'factory', 'transient', 'validate', 'projection' ) ) ) && array() === $lunara_preview_admin_bar_calls && array() === $lunara_test_localized, 'Utility Search must deny ' . $label . ' before any owner state or bridge work.' );
}
lunara_preview_reset( 'utility-search', $token, $instance ); $_SERVER['REQUEST_URI'] = '/journal/?' . $_SERVER['QUERY_STRING']; $utility_wrong_route = lunara_preview_run();
lunara_test_assert( ! $utility_wrong_route['ok'] && 403 === $utility_wrong_route['status'] && $generic === $utility_wrong_route['message'] && array() === array_values( array_intersect( $lunara_preview_events, array( 'dependency', 'factory', 'transient', 'validate', 'projection' ) ) ) && array() === $lunara_preview_admin_bar_calls && array() === $lunara_test_localized, 'Utility Search must deny the exact candidate on any route other than /search/.' );
foreach ( array( 'query', 'route', 'params' ) as $drift ) {
	lunara_preview_reset( 'utility-search', $token, $instance ); $lunara_preview_surface_mutation_target = 'utility-search'; $lunara_preview_surface_mutation = $drift; $utility_registry_denial = lunara_preview_run();
	lunara_test_assert( ! $utility_registry_denial['ok'] && 403 === $utility_registry_denial['status'] && $generic === $utility_registry_denial['message'] && array() === array_values( array_intersect( $lunara_preview_events, array( 'dependency', 'factory', 'transient', 'validate', 'projection' ) ) ) && array() === $lunara_preview_admin_bar_calls && array() === $lunara_test_localized, 'Utility Search must deny normalized registry ' . $drift . ' drift before any owner state or bridge work.' );
}

$strict_states = array(); foreach ( array( 'global-design', 'homepage-structure', 'lunara-method' ) as $surface ) { $state = lunara_preview_state( $surface ); $extra = $state; $extra['extra'] = true; $missing = $state; unset( $missing[ array_keys( $missing )[0] ] ); $wrong = $state; $first = array_keys( $wrong )[0]; $wrong[ $first ] = new stdClass(); $strict_states[] = array( $surface, $extra ); $strict_states[] = array( $surface, $missing ); $strict_states[] = array( $surface, $wrong ); } $provenance = lunara_preview_state( 'global-design' ); $provenance['colors']['gold_light']['source'] = 'shipped-default'; $strict_states[] = array( 'global-design', $provenance );
foreach ( $strict_states as $case_index => $case ) { lunara_preview_reset( $case[0], $token, $instance ); $key = 'lunara_site_studio_preview_' . hash( 'sha256', $token ); $lunara_preview_transients[ $key ]['state'] = $case[1]; $result = lunara_preview_run(); lunara_test_assert( ! $result['ok'] && 403 === $result['status'] && $generic === $result['message'] && array() === $lunara_preview_admin_bar_calls, $case[0] . ' recovered state case ' . $case_index . ' must match its exact canonical validator result before projection without premature admin-bar suppression.' ); }

lunara_preview_reset( 'global-design', $token, $instance ); $empty = lunara_preview_state( 'global-design' ); foreach ( $empty['colors'] as &$item ) { $item['override'] = null; $item['source'] = 'shipped-default'; } unset( $item ); foreach ( $empty['fonts'] as &$item ) { $item['override'] = null; } unset( $item ); $key = 'lunara_site_studio_preview_' . hash( 'sha256', $token ); $lunara_preview_transients[ $key ]['state'] = $empty; lunara_test_assert( lunara_preview_run()['ok'], 'An all-inherited Global candidate must remain valid.' ); lunara_test_assert( array( 'colors' => array(), 'fonts' => array() ) === get_option( 'lunara_design_tokens' ), 'All-inherited Global preview must expose the exact empty canonical option shape without writing.' );
lunara_preview_reset( 'homepage-structure', $token, $instance ); $registry = lunara_preview_state( 'homepage-structure' ); $registry['mode'] = 'registry'; $key = 'lunara_site_studio_preview_' . hash( 'sha256', $token ); $lunara_preview_transients[ $key ]['state'] = $registry; lunara_test_assert( lunara_preview_run()['ok'] && $lunara_preview_posts[42] === lunara_home_front_page_content( 42 ) && '' === lunara_home_front_page_content( 99 ), 'Registry-mode and wrong-page content reads must never receive block composition substitution.' );

$denials = array( 'logged-out', 'capability', 'dependency', 'factory-error', 'validate-error', 'validate-throw', 'projection-error', 'foreign-user', 'tampered-hash', 'expired', 'invalid-state', 'not-front', 'wrong-page', 'wrong-method', 'lowercase-method', 'missing-method', 'head-method', 'wrong-host', 'wrong-port', 'wrong-scheme', 'wrong-route', 'owner-filter', 'query-filter', 'route-filter', 'request-query-mismatch' );
foreach ( $denials as $kind ) {
	lunara_preview_reset( 'global-design', $token, $instance );
	if ( 'logged-out' === $kind ) { $lunara_preview_user_id = 0; }
	if ( 'capability' === $kind ) { $lunara_test_can_edit = false; }
	if ( 'dependency' === $kind ) { $lunara_test_dependency_ready = false; }
	if ( in_array( $kind, array( 'factory-error', 'validate-error', 'validate-throw', 'projection-error' ), true ) ) { $lunara_test_adapter_failure = $kind; }
	$key = 'lunara_site_studio_preview_' . hash( 'sha256', $token );
	if ( 'foreign-user' === $kind ) { $lunara_preview_transients[ $key ]['user_id'] = 9; }
	if ( 'tampered-hash' === $kind ) { $lunara_preview_transients[ $key ]['token_hash'] = 'tampered'; }
	if ( 'expired' === $kind ) { $lunara_preview_transients[ $key ]['expires'] = current_time( 'timestamp', true ); }
	if ( 'invalid-state' === $kind ) { $lunara_preview_transients[ $key ]['state'] = array( 'secret' => true ); }
	if ( 'not-front' === $kind ) { $lunara_preview_is_front = false; }
	if ( 'wrong-page' === $kind ) { $lunara_preview_front_id = 99; }
	if ( 'wrong-method' === $kind ) { $_SERVER['REQUEST_METHOD'] = 'POST'; }
	if ( 'lowercase-method' === $kind ) { $_SERVER['REQUEST_METHOD'] = 'get'; }
	if ( 'missing-method' === $kind ) { unset( $_SERVER['REQUEST_METHOD'] ); }
	if ( 'head-method' === $kind ) { $_SERVER['REQUEST_METHOD'] = 'HEAD'; }
	if ( 'wrong-host' === $kind ) { $_SERVER['HTTP_HOST'] = 'evil.test'; }
	if ( 'wrong-port' === $kind ) { $_SERVER['HTTP_HOST'] = 'example.test:444'; }
	if ( 'wrong-scheme' === $kind ) { $_SERVER['HTTPS'] = 'off'; $_SERVER['SERVER_PORT'] = '80'; }
	if ( 'wrong-route' === $kind ) { $_SERVER['REQUEST_URI'] = '/journal/?' . $_SERVER['QUERY_STRING']; }
	if ( 'owner-filter' === $kind ) { $lunara_preview_surface_mutation = 'owner'; }
	if ( 'query-filter' === $kind ) { $lunara_preview_surface_mutation = 'query'; }
	if ( 'route-filter' === $kind ) { $lunara_preview_surface_mutation = 'route'; }
	if ( 'request-query-mismatch' === $kind ) { $_SERVER['REQUEST_URI'] .= '&other=1'; }
	$lunara_test_trace_callbacks = true; $result = lunara_preview_run(); $private_index = array_search( 'private', $lunara_preview_events, true ); $denied_order = false !== $private_index; foreach ( array( 'dependency', 'factory', 'transient', 'validate', 'projection' ) as $event_name ) { foreach ( array_keys( $lunara_preview_events, $event_name, true ) as $event_index ) { $denied_order = $denied_order && $event_index > $private_index; } }
	lunara_test_assert( ! $result['ok'] && 403 === $result['status'] && $generic === $result['message'] && $denied_order && array() === $lunara_preview_admin_bar_calls, $kind . ' must be indistinguishable, admin-bar-inert, and keep every reached dependency/adapter/state callback after the private boundary.' );
}

$lunara_home_before_subdir = $lunara_test_home_base; $lunara_test_home_base = 'https://example.test/subsite/'; lunara_preview_reset( 'global-design', $token, $instance ); $_SERVER['REQUEST_URI'] = '/subsite/?' . $_SERVER['QUERY_STRING']; lunara_test_assert( lunara_preview_run()['ok'], 'A canonical subdirectory Homepage preview route must authorize.' );
foreach ( array( '/subsite?', '/subsite//?', '/subsite/./?', '/subsite/%2e/?' ) as $bad_subdir_path ) { lunara_preview_reset( 'global-design', $token, $instance ); $_SERVER['REQUEST_URI'] = $bad_subdir_path . $_SERVER['QUERY_STRING']; $subdir_denial = lunara_preview_run(); lunara_test_assert( ! $subdir_denial['ok'] && 403 === $subdir_denial['status'] && $generic === $subdir_denial['message'] && array() === $lunara_preview_admin_bar_calls, 'Noncanonical subdirectory route must receive the generic private denial without premature admin-bar suppression: ' . $bad_subdir_path ); } $lunara_test_home_base = $lunara_home_before_subdir;

lunara_test_assert( '<!-- wp:lunara/cinematic-hero /--><!-- wp:core/paragraph --><p>Keep me.</p><!-- /wp:core/paragraph --><!-- wp:lunara/pairing-desk /-->' === $lunara_preview_posts[42], 'Private preview behavior must preserve canonical post bytes.' );

$lunara_hero_fixture_slides = array();
function lunara_get_home_cinematic_hero_slides() { global $lunara_hero_fixture_slides; return $lunara_hero_fixture_slides; }
function lunara_hero_command_slides() { return array(); }
function lunara_render_cinematic_hero() { return '<section class="fixture-static-hero"><p>Fallback</p></section>'; }
function lunara_render_cinematic_hero_slide( $slide, $index, $lcp ) { return '<li class="splide__slide">' . esc_html( $slide['title'] ) . '</li>'; }
$hero_source = file_get_contents( dirname( __DIR__ ) . '/functions.php' ); $hero_function = lunara_test_extract_named_function( $hero_source, 'lunara_render_cinematic_hero_carousel' ); lunara_test_assert( '' !== $hero_function, 'Hero runtime must extract the real production carousel function.' ); eval( $hero_function );
$fallback_hero = lunara_render_cinematic_hero_carousel(); $lunara_hero_fixture_slides = array( array( 'title' => 'Only' ) ); $singleton_fallback_hero = lunara_render_cinematic_hero_carousel(); $lunara_hero_fixture_slides = array( array( 'title' => 'One' ), array( 'title' => 'Two' ) ); $carousel_hero = lunara_render_cinematic_hero_carousel();
foreach ( array( 'empty fallback' => $fallback_hero, 'singleton no-command fallback' => $singleton_fallback_hero, 'multi-slide' => $carousel_hero ) as $hero_kind => $hero_html ) { lunara_test_assert( 1 === substr_count( $hero_html, 'data-lunara-site-studio-section="hero"' ) && 1 === preg_match( '/^\s*<section\s+data-lunara-site-studio-section="hero"(?=\s|>)/', $hero_html ) && 1 === substr_count( $hero_html, '<section' ), 'Real ' . $hero_kind . ' Hero output must expose exactly one root marker with no wrapper or nested duplicate.' ); }
lunara_test_assert( false !== strpos( $singleton_fallback_hero, 'fixture-static-hero' ) && false !== strpos( $singleton_fallback_hero, 'Fallback' ) && false === strpos( $singleton_fallback_hero, 'splide__slide' ), 'A singleton without a Hero Command must execute the required fewer-than-two static fallback branch; changing its <2 boundary to <1 must fail this assertion.' );
fwrite( STDOUT, "site-studio-private-preview-runtime: all assertions passed.\n" );
