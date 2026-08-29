<?php
/**
 * Isolated behavioral contract for the Oscars Portal Studio route family.
 *
 * Run: php tests/oscars-portal-studio-runtime.php
 *
 * The default run executes the accessors-mode contract and then re-executes
 * itself in two child processes to prove the reader-degradation contract:
 *   LUNARA_OSCARS_PORTAL_MODE=degraded  — plugin class present, pre-2.7.82
 *                                         (no read accessors)
 *   LUNARA_OSCARS_PORTAL_MODE=noplugin  — no plugin class at all
 * Both degraded lanes must resolve every migrated data helper to
 * hidden/empty output — except linked-reviews, which degrades to its
 * SQL-free IMDb meta_query fallback — with zero SQL anywhere on the path.
 *
 * Fast path for the static contract's byte measurements (no assertions):
 *   php tests/oscars-portal-studio-runtime.php --emit-portal-critical
 * prints the saved-provenance variable CSS on line one and the structural
 * seed on line two.
 */

define( 'ABSPATH', __DIR__ . '/' );

$lunara_test_mode = getenv( 'LUNARA_OSCARS_PORTAL_MODE' );
$lunara_test_mode = in_array( $lunara_test_mode, array( 'degraded', 'noplugin' ), true ) ? $lunara_test_mode : 'accessors';

$lunara_test_theme_mods    = array();
$lunara_test_options       = array();
$lunara_test_transients    = array();
$lunara_test_actions_fired = array();
$lunara_test_rocket_urls   = null;
$lunara_test_revision_write_mode = 'normal';
$lunara_test_user_id       = 7;
$lunara_test_can_edit      = true;
$lunara_test_now           = 2000000000;
$lunara_test_is_portal     = true;
$lunara_test_nocache_calls = 0;
$lunara_test_status_headers = array();
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
function is_wp_error( $value ) { return $value instanceof WP_Error; }

class Lunara_Test_WP_Die extends RuntimeException {}

class WP_Query {
	public $query_args     = array();
	public $posts          = array();
	public $is_main        = true;
	public $page_slug      = '';
	public $query_vars     = array();
	public $queried_object = null;
	public function __construct( $args = array() ) { $this->query_args = is_array( $args ) ? $args : array(); }
	public function have_posts() { return ! empty( $this->posts ); }
	public function is_main_query() { return $this->is_main; }
	public function get( $key, $default = '' ) { return array_key_exists( $key, $this->query_vars ) ? $this->query_vars[ $key ] : $default; }
	// Faithful to real WordPress: WP_Query::is_page( 'slug' ) compares against
	// the QUERIED OBJECT, which is only determined after posts are fetched —
	// so at pre_get_posts (queried_object unset) it is ALWAYS false. The old
	// stub resolved the slug pre-query and hid a dead preflight layer.
	public function is_page( $page = '' ) {
		if ( null === $this->queried_object ) {
			return false;
		}
		return '' !== $this->page_slug && $this->page_slug === $page;
	}
}

function add_action() {}
function add_filter() {}
function do_action( $hook, $a = null, $b = null ) { global $lunara_test_actions_fired; $lunara_test_actions_fired[] = array( $hook, $a, $b ); }
function apply_filters( $hook, $value ) { return $value; }
function __( $value, $domain = '' ) { return $value; }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_html__( $value, $domain = '' ) { return esc_html( $value ); }
function esc_html_e( $value, $domain = '' ) { echo esc_html( $value ); }
function esc_attr_e( $value, $domain = '' ) { echo esc_attr( $value ); }
function esc_js( $value ) { return addslashes( (string) $value ); }
function esc_url( $value ) { return esc_url_raw( $value ); }
function esc_url_raw( $value ) { if ( ! is_scalar( $value ) ) { throw new TypeError( 'esc_url_raw expects a scalar test value' ); } return filter_var( (string) $value, FILTER_SANITIZE_URL ); }
function sanitize_key( $value ) { if ( ! is_scalar( $value ) ) { throw new TypeError( 'sanitize_key expects a scalar test value' ); } return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function wp_unslash( $value ) { return $value; }
function absint( $value ) { if ( ! is_scalar( $value ) ) { throw new TypeError( 'absint expects a scalar test value' ); } return abs( (int) $value ); }
// Faithful to real WordPress: wp_http_validate_url accepts BOTH http and
// https, so any future https-only discipline in the Oscars modules must come
// from module-owned guards, never from an optimistic harness stub.
function wp_http_validate_url( $url ) { return in_array( parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) && (bool) parse_url( $url, PHP_URL_HOST ) ? $url : false; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function get_theme_mod( $key, $default = '' ) { global $lunara_test_theme_mods; return array_key_exists( $key, $lunara_test_theme_mods ) ? $lunara_test_theme_mods[ $key ] : $default; }
function set_theme_mod( $key, $value ) { global $lunara_test_theme_mods; $lunara_test_theme_mods[ $key ] = $value; }
function remove_theme_mod( $key ) { global $lunara_test_theme_mods; unset( $lunara_test_theme_mods[ $key ] ); }
function get_option( $key, $default = false ) { global $lunara_test_options; return array_key_exists( $key, $lunara_test_options ) ? $lunara_test_options[ $key ] : $default; }
function update_option( $key, $value ) {
	global $lunara_test_options, $lunara_test_revision_write_mode;
	if ( 'lunara_oscars_portal_studio_revisions' === $key && 'fail' === $lunara_test_revision_write_mode ) { return false; }
	$lunara_test_options[ $key ] = 'lunara_oscars_portal_studio_revisions' === $key && 'mismatch' === $lunara_test_revision_write_mode && is_array( $value ) ? array_slice( $value, 1 ) : $value;
	return true;
}
function delete_option( $key ) { global $lunara_test_options; unset( $lunara_test_options[ $key ] ); return true; }
// The public config resolver is deliberately uncached: no wp_cache_get or
// wp_cache_set stub exists, so any reintroduced object-cache read/write in
// the module fatals this runtime immediately. Only the hygiene delete stays.
function wp_cache_delete( $key, $group ) { return true; }
function get_current_user_id() { global $lunara_test_user_id; return $lunara_test_user_id; }
function current_user_can( $capability ) { global $lunara_test_can_edit; return $lunara_test_can_edit && 'edit_theme_options' === $capability; }
function current_time( $type = 'timestamp', $gmt = 0 ) { global $lunara_test_now; return 'mysql' === $type ? '2026-08-17 12:00:00' : $lunara_test_now; }
function wp_generate_uuid4() { static $n = 0; return 'uuid-4-test-' . ( ++$n ); }
function wp_hash( $value ) { return hash( 'sha256', 'test-salt|' . $value ); }
function set_transient( $key, $value, $ttl ) { global $lunara_test_transients, $lunara_test_now; $lunara_test_transients[ $key ] = array( 'value' => $value, 'expires' => $lunara_test_now + $ttl ); return true; }
function get_transient( $key ) {
	global $lunara_test_transients, $lunara_test_now, $lunara_test_actions_fired;
	// Preview-token transient reads land in the same fired-actions array the
	// no-store sender uses, so tests can assert the no-store transition
	// happened strictly BEFORE any token work — not merely that it happened.
	if ( 0 === strpos( (string) $key, 'lunara_oscars_portal_preview_' ) ) {
		$lunara_test_actions_fired[] = array( 'oscars_portal_preview_transient_read', $key, null );
	}
	return isset( $lunara_test_transients[ $key ] ) && $lunara_test_transients[ $key ]['expires'] > $lunara_test_now ? $lunara_test_transients[ $key ]['value'] : false;
}
function delete_transient( $key ) { global $lunara_test_transients; unset( $lunara_test_transients[ $key ] ); return true; }
function wp_parse_args( $args, $defaults = array() ) { return array_replace( (array) $defaults, (array) $args ); }
function get_page_by_path( $path ) { return 'oscars' === $path ? (object) array( 'ID' => 700, 'post_type' => 'page', 'post_status' => 'publish' ) : null; }
function get_permalink( $post ) { return is_object( $post ) && 700 === $post->ID ? 'https://example.test/oscars/' : 'https://example.test/permalink/'; }
function rocket_clean_files( $urls ) { global $lunara_test_rocket_urls; $lunara_test_rocket_urls = $urls; }
function is_admin() { return false; }
function is_page( $page = '' ) { global $lunara_test_is_portal; return $lunara_test_is_portal; }
function is_page_template( $template = '' ) { return false; }
function get_query_var( $key ) { return ''; }
function nocache_headers() { global $lunara_test_nocache_calls; $lunara_test_nocache_calls++; }
function status_header( $code ) { global $lunara_test_status_headers; $lunara_test_status_headers[] = $code; }
function wp_die( $message = '', $title = '', $args = array() ) { throw new Lunara_Test_WP_Die( is_scalar( $message ) ? (string) $message : 'wp_die' ); }
function lunara_get_design_tokens() { return $GLOBALS['lunara_test_design_tokens']; }
function lunara_design_token_font_choices() {
	return array(
		'tiempos-text' => array( 'stack' => '"Tiempos Text", Georgia, serif' ),
		'lora'         => array( 'stack' => '"Lora", Georgia, serif' ),
		'georgia'      => array( 'stack' => 'Georgia, serif' ),
	);
}
function lunara_test_no_store_precedes_transient_read( $actions ) {
	$no_store_index  = null;
	$transient_index = null;
	foreach ( array_values( $actions ) as $index => $fired ) {
		if ( null === $no_store_index && 'lunara_oscars_portal_preview_no_store_sent' === $fired[0] ) {
			$no_store_index = $index;
		}
		if ( null === $transient_index && 'oscars_portal_preview_transient_read' === $fired[0] ) {
			$transient_index = $index;
		}
	}
	return null !== $no_store_index && null !== $transient_index && $no_store_index < $transient_index;
}

// ---------------------------------------------------------------------------
// Academy Awards fixture, shaped per mode.
// ---------------------------------------------------------------------------
if ( 'accessors' === $lunara_test_mode ) {
	class Academy_Awards_Table {
		public static function get_instance() { static $i = null; return $i ?: $i = new self(); }
		public function get_title_visual_package( $imdb_id, $size = 'large' ) { return array( 'poster_url' => 'https://img.test/' . $imdb_id . '.jpg' ); }
		public function build_entity_url_from_id( $imdb_id ) { return 'https://example.test/oscars/title/' . $imdb_id . '/'; }
		public function get_reviewed_award_title_post_ids( $limit = 12, $offset = 0 ) {
			if ( ! empty( $GLOBALS['lunara_test_empty_pool'] ) ) {
				return array();
			}
			$pool = range( 901, 940 ); // Deep enough for every daily rotation offset (0-19).
			return array_slice( $pool, $offset, $limit );
		}
		public function get_title_award_context( $imdb_id, $args = array() ) {
			if ( 'tt0000404' === $imdb_id ) {
				return array(); // Unknown title: accessor's empty contract.
			}
			return array(
				'imdb_id'            => $imdb_id,
				'title'              => 'Context Fixture',
				'year'               => '1998',
				'rows'               => array( array( 'ceremony' => 70 ), array( 'ceremony' => 70 ), array( 'ceremony' => 70 ) ),
				'wins'               => 2,
				'nominations'        => 3,
				'winner_categories'  => array( 'BEST PICTURE', 'DIRECTING' ),
				'display_categories' => array( 'Best Picture', 'Directing' ),
			);
		}
		public function get_category_first_ceremony( $category ) { return 'CASTING' === $category ? 97 : 1; }
		public function get_route_context() { return array( 'kind' => 'ceremony', 'id' => 97 ); }
	}
} elseif ( 'degraded' === $lunara_test_mode ) {
	// The plugin class exists but predates the 2.7.82 read accessors: only the
	// visual-package surface is present, so every migrated helper must hit its
	// method_exists guard and degrade to hidden/empty.
	class Academy_Awards_Table {
		public static function get_instance() { static $i = null; return $i ?: $i = new self(); }
		public function get_title_visual_package( $imdb_id, $size = 'large' ) { return array( 'poster_url' => 'https://img.test/' . $imdb_id . '.jpg' ); }
	}
}
// 'noplugin' mode defines no class at all: the reader itself is null.

// ---------------------------------------------------------------------------
// Modules under test.
// ---------------------------------------------------------------------------
require dirname( __DIR__ ) . '/inc/oscars-family.php';
require dirname( __DIR__ ) . '/inc/oscars-portal-studio.php';
require dirname( __DIR__ ) . '/inc/site-studio-registry.php';
require dirname( __DIR__ ) . '/inc/site-studio-adapters.php';

$oscars_projection_schema = lunara_site_studio_oscars_portal_state_schema();
lunara_test_assert( array( 'schema_version', 'identity', 'section_order', 'section_visibility', 'presentation' ) === array_keys( $oscars_projection_schema ), 'Oscars projection schema must inventory every authoritative top-level provider key.' );
lunara_test_assert( array( 'kicker', 'title', 'explore_kicker', 'explore_heading', 'spotlights_heading', 'titles_kicker', 'titles_heading', 'research_kicker', 'research_heading', 'reviews_heading', 'deep_cuts_heading' ) === array_keys( $oscars_projection_schema['identity'] ), 'Oscars projection schema must inventory every authoritative identity key.' );
lunara_test_assert( array( 'hero', 'navigator', 'board', 'doors', 'spotlights', 'titles', 'research', 'linked-reviews', 'winners', 'deep-cuts', 'rotating-winners' ) === array_keys( $oscars_projection_schema['section_visibility'] ), 'Oscars projection schema must inventory every authoritative visibility key.' );
lunara_test_assert( array( 'section_gap', 'hero_min_height', 'card_min_height' ) === array_keys( $oscars_projection_schema['presentation'] ), 'Oscars projection schema must inventory every authoritative geometry key.' );
$oscars_projection_accepted = false;
$oscars_projection_roundtrip = lunara_site_studio_project_state_value( lunara_oscars_portal_studio_defaults(), $oscars_projection_schema, $oscars_projection_accepted );
lunara_test_assert( $oscars_projection_accepted && lunara_oscars_portal_studio_defaults() === $oscars_projection_roundtrip, 'Oscars projection must preserve the complete authoritative default shape.' );
require dirname( __DIR__ ) . '/inc/oscars-portal-critical.php';
require dirname( __DIR__ ) . '/inc/queries.php';
require dirname( __DIR__ ) . '/inc/home-sections.php';

// ---------------------------------------------------------------------------
// Fast path for the static contract's byte measurements.
// ---------------------------------------------------------------------------
if ( in_array( '--emit-portal-critical', (array) ( $argv ?? array() ), true ) ) {
	update_option(
		LUNARA_OSCARS_PORTAL_STUDIO_OPTION,
		array(
			'schema_version' => 1,
			'section_order'  => lunara_oscars_portal_studio_slots(),
			'presentation'   => array( 'section_gap' => 60, 'hero_min_height' => 420, 'card_min_height' => 300 ),
		)
	);
	echo lunara_oscars_portal_variable_css( lunara_oscars_portal_studio_get_public_config( false ) ) . "\n";
	echo lunara_oscars_portal_critical_css() . "\n";
	exit( 0 );
}

$lunara_template_order = array( 'hero', 'navigator', 'board', 'doors', 'spotlights', 'titles', 'research', 'linked-reviews', 'winners', 'deep-cuts', 'rotating-winners' );

// ---------------------------------------------------------------------------
// Route family + reader boundary.
// ---------------------------------------------------------------------------
if ( 'noplugin' === $lunara_test_mode ) {
	lunara_test_assert( null === lunara_oscars_reader(), 'Without the plugin class the reader must be null.' );
	lunara_test_assert( false === lunara_is_oscars_ledger_route(), 'Without the plugin and without aat_* query vars there is no ledger route.' );
} else {
	lunara_test_assert( is_object( lunara_oscars_reader() ), 'With the plugin class present the reader must return the singleton.' );
}
lunara_test_assert( true === lunara_is_oscars_portal_route(), 'The portal page must classify as the portal route.' );
lunara_test_assert( true === lunara_is_oscars_route_family(), 'The portal route must be inside the route family.' );
lunara_test_assert( in_array( 'lunara-oscars-family', lunara_oscars_family_body_classes( array( 'aat-shell-page' ) ), true ), 'The family body class must be appended on family routes.' );
lunara_test_assert( in_array( 'aat-shell-page', lunara_oscars_family_body_classes( array( 'aat-shell-page' ) ), true ), 'Existing body classes (aat-shell-page contract) must never be removed.' );
if ( 'accessors' === $lunara_test_mode ) {
	lunara_test_assert( true === lunara_is_oscars_ledger_route(), 'A plugin route context of kind=ceremony must classify as a ledger route.' );
}

// ---------------------------------------------------------------------------
// Defaults validate and reproduce the template's emission order + literals.
// ---------------------------------------------------------------------------
$defaults  = lunara_oscars_portal_studio_defaults();
$validated = lunara_oscars_portal_studio_validate_config( $defaults );
lunara_test_assert( ! is_wp_error( $validated ), 'The shipped defaults must pass strict validation.' );
lunara_test_assert( $validated['section_order'] === $lunara_template_order, 'Default section order must be the template emission order (hero, navigator, board, doors, spotlights, titles, research, linked-reviews, winners, deep-cuts, rotating-winners).' );
lunara_test_assert( lunara_oscars_portal_studio_slots() === $lunara_template_order, 'The canonical slot list must be exactly the eleven template slots in emission order.' );
lunara_test_assert( false === $validated['section_visibility']['linked-reviews'], 'Linked Reviews must default hidden, matching the shipped theme-mod default.' );
lunara_test_assert( true === $validated['section_visibility']['board'] && true === $validated['section_visibility']['navigator'], 'Board and navigator lanes must resolve visible by default.' );
lunara_test_assert( array( 40, 360, 360 ) === array( $validated['presentation']['section_gap'], $validated['presentation']['hero_min_height'], $validated['presentation']['card_min_height'] ), 'Default geometry must be 40/360/360.' );
lunara_test_assert( 'The Lunara Oscar Ledger' === $validated['identity']['kicker'] && 'Academy Awards history, treated like a living editorial system.' === $validated['identity']['title'], 'Default identity must reproduce the shipped portal literals byte-for-byte.' );

// The identity defaults are byte-locked against the actual template source:
// every spec's canonical setting and shipped literal must appear as the exact
// `'setting', 'default'` fallback pair page-oscars.php passes to its resolver.
$lunara_template_source = file_get_contents( dirname( __DIR__ ) . '/page-oscars.php' );
lunara_test_assert( false !== $lunara_template_source && '' !== $lunara_template_source, 'page-oscars.php must be readable for the byte-lock checks.' );
foreach ( lunara_oscars_portal_studio_identity_specs() as $identity_field => $identity_spec ) {
	lunara_test_assert(
		false !== strpos( $lunara_template_source, "'" . $identity_spec['setting'] . "', '" . $identity_spec['default'] . "'" ),
		'Identity spec ' . $identity_field . ' must match the template fallback literal byte-for-byte.'
	);
}
lunara_test_assert(
	false !== strpos( $lunara_template_source, "array( 'hero', 'navigator', 'board', 'doors', 'spotlights', 'titles', 'research', 'linked-reviews', 'winners', 'deep-cuts', 'rotating-winners' )" ),
	'The template fallback order array must remain byte-identical to the canonical slot list.'
);

// Visibility maps to the canonical lunara_oscars_show_* / rotating owners.
$owners = lunara_oscars_portal_studio_visibility_owners();
$expected_owner_settings = array(
	'hero'             => 'lunara_oscars_show_hero',
	'navigator'        => '',
	'board'            => '',
	'doors'            => 'lunara_oscars_show_portal_links',
	'spotlights'       => 'lunara_oscars_show_spotlights',
	'titles'           => 'lunara_oscars_show_title_cards',
	'research'         => 'lunara_oscars_show_research',
	'linked-reviews'   => 'lunara_oscars_show_linked_reviews',
	'winners'          => 'lunara_oscars_show_latest_winners',
	'deep-cuts'        => 'lunara_oscars_show_deep_cuts',
	'rotating-winners' => 'lunara_oscars_rotating_winners_enabled',
);
lunara_test_assert( array_keys( $owners ) === $lunara_template_order, 'The visibility owner map must enumerate exactly the eleven slots in template order.' );
foreach ( $expected_owner_settings as $owner_slot => $owner_setting ) {
	lunara_test_assert( $owners[ $owner_slot ]['setting'] === $owner_setting, 'Slot ' . $owner_slot . ' must keep its canonical visibility owner (' . ( '' !== $owner_setting ? $owner_setting : 'derived' ) . ').' );
}
lunara_test_assert( 'doors' === $owners['navigator']['bound'] && 'content' === $owners['board']['bound'], 'Navigator must stay bound to the doors toggle and the board must stay content-driven.' );
lunara_test_assert( false === $owners['linked-reviews']['default'], 'Linked Reviews must keep its shipped hidden default in the owner map.' );

// An unsaved site resolves to the defaults exactly (no option, no mods).
$resolved = lunara_oscars_portal_studio_get_public_config( false );
lunara_test_assert( $resolved['section_order'] === $validated['section_order'] && $resolved['identity'] === $validated['identity'] && $resolved['section_visibility'] === $validated['section_visibility'] && $resolved['presentation'] === $validated['presentation'], 'An unsaved site must resolve to the validated defaults.' );
lunara_test_assert( empty( $resolved['_preview'] ), 'A non-preview resolution must not be flagged as preview.' );
lunara_test_assert( false === lunara_oscars_portal_studio_has_saved_presentation(), 'The provenance gate must stay closed before any explicit save.' );

// Mods stay canonical: flipping an existing owner flows into the resolved map.
$lunara_test_theme_mods['lunara_oscars_show_spotlights'] = false;
$lunara_test_theme_mods['lunara_oscars_portal_kicker']   = '  Custom Kicker  ';
$resolved = lunara_oscars_portal_studio_get_public_config( false );
lunara_test_assert( false === $resolved['section_visibility']['spotlights'], 'A false lunara_oscars_show_* mod must resolve as hidden.' );
lunara_test_assert( 'Custom Kicker' === $resolved['identity']['kicker'], 'A non-empty portal text mod must win, trimmed, over the shipped literal.' );
$lunara_test_theme_mods['lunara_oscars_show_portal_links'] = false;
$resolved = lunara_oscars_portal_studio_get_public_config( false );
lunara_test_assert( false === $resolved['section_visibility']['doors'] && false === $resolved['section_visibility']['navigator'] && true === $resolved['section_visibility']['board'], 'Hiding the doors owner must hide the bound navigator while the board stays content-driven true.' );
$lunara_test_theme_mods['lunara_oscars_portal_kicker'] = array( 'corrupt-mod' );
$resolved = lunara_oscars_portal_studio_get_public_config( false );
lunara_test_assert( 'The Lunara Oscar Ledger' === $resolved['identity']['kicker'], 'A non-scalar copy mod must fall back to the shipped literal without a type error.' );
$lunara_test_theme_mods = array();

// ---------------------------------------------------------------------------
// Every validator error code is reachable; set-equality with the allowlist.
// ---------------------------------------------------------------------------
$code_producers = array(
	'oscars_portal_config_invalid'        => static function () {
		return lunara_oscars_portal_studio_validate_config( 'not-an-array' );
	},
	'oscars_portal_identity_invalid'      => static function () use ( $defaults ) {
		$c = $defaults;
		$c['identity']['kicker'] = array( 'nested' );
		return lunara_oscars_portal_studio_validate_config( $c );
	},
	'oscars_portal_section_order_invalid' => static function () use ( $defaults ) {
		$c = $defaults;
		$c['section_order'] = array( 'hero', 'hero', 'board', 'doors', 'spotlights', 'titles', 'research', 'linked-reviews', 'winners', 'deep-cuts', 'rotating-winners' );
		return lunara_oscars_portal_studio_validate_config( $c );
	},
	'oscars_portal_visibility_invalid'    => static function () use ( $defaults ) {
		$c = $defaults;
		$c['section_visibility'] = array( 'hero' => array( 'nested' ) );
		return lunara_oscars_portal_studio_validate_config( $c );
	},
	'oscars_portal_geometry_invalid'      => static function () use ( $defaults ) {
		$c = $defaults;
		$c['presentation'] = array( 'section_gap' => 5, 'hero_min_height' => 360, 'card_min_height' => 360 );
		return lunara_oscars_portal_studio_validate_config( $c );
	},
	'oscars_portal_revision_not_found'    => static function () {
		return lunara_oscars_portal_studio_restore_revision( 'no-such-revision' );
	},
	'oscars_portal_preview_forbidden'     => static function () use ( $defaults ) {
		global $lunara_test_can_edit;
		$lunara_test_can_edit = false;
		$result = lunara_oscars_portal_studio_store_preview( $defaults );
		$lunara_test_can_edit = true;
		return $result;
	},
	'oscars_portal_config_repair_failed'  => static function () use ( $defaults ) {
		// An unknown identity field with a non-scalar value defeats the
		// identity repair (which only fills the canonical fields), exhausting
		// the bounded repair loop fail-closed.
		$c = $defaults;
		$c['identity']['rogue_field'] = array( 'unrepairable' );
		return lunara_oscars_portal_studio_repair_public_config( $c, $defaults );
	},
);
$allowlisted_messages = lunara_oscars_portal_studio_validation_messages();
$generic_feedback     = lunara_oscars_portal_studio_validation_message( 'not-a-real-code' );
$produced_codes       = array();
foreach ( $code_producers as $expected_code => $producer ) {
	$result = $producer();
	lunara_test_assert( is_wp_error( $result ), 'Validator code ' . $expected_code . ' must be reachable as a WP_Error.' );
	lunara_test_assert( $expected_code === $result->get_error_code(), 'Producer for ' . $expected_code . ' emitted ' . $result->get_error_code() . ' instead.' );
	lunara_test_assert( array_key_exists( $expected_code, $allowlisted_messages ), 'Validator code ' . $expected_code . ' must resolve in the bounded message allowlist.' );
	$message = lunara_oscars_portal_studio_validation_message( $expected_code );
	lunara_test_assert( is_string( $message ) && '' !== $message && $message !== $generic_feedback, 'Validator code ' . $expected_code . ' must resolve a dedicated human message.' );
	$produced_codes[] = $expected_code;
}
lunara_test_assert( array() === array_diff( array_keys( $allowlisted_messages ), $produced_codes ), 'Every allowlisted validator code must be reachable: missing ' . implode( ', ', array_diff( array_keys( $allowlisted_messages ), $produced_codes ) ) . '.' );
lunara_test_assert( array() === array_diff( $produced_codes, array_keys( $allowlisted_messages ) ), 'No emitted validator code may fall outside the allowlist.' );
lunara_test_assert( '' === lunara_oscars_portal_studio_bound_feedback_code( 'not-a-real-code' ) && 'oscars_portal_geometry_invalid' === lunara_oscars_portal_studio_bound_feedback_code( 'oscars_portal_geometry_invalid' ), 'Feedback codes must bind to the exact allowlist without normalization.' );

// The public resolver can never surface repair_failed: normalization drops
// unknown identity leaves before the repair loop ever sees them.
$lunara_test_theme_mods['lunara_oscars_portal_kicker'] = 'Survivor Kicker';
$lunara_test_options[ LUNARA_OSCARS_PORTAL_STUDIO_OPTION ] = array(
	'schema_version' => 1,
	'identity'       => array( 'rogue_field' => array( 'unrepairable' ) ),
	'section_order'  => lunara_oscars_portal_studio_slots(),
	'presentation'   => array( 'section_gap' => 44, 'hero_min_height' => 400, 'card_min_height' => 320 ),
);
$rogue_public = lunara_oscars_portal_studio_get_public_config( false );
lunara_test_assert( ! is_wp_error( $rogue_public ) && 'Survivor Kicker' === $rogue_public['identity']['kicker'] && 44 === $rogue_public['presentation']['section_gap'], 'Rogue stored identity leaves must be normalized away without erasing valid siblings.' );
$lunara_test_options    = array();
$lunara_test_theme_mods = array();

// Strict non-array candidate and non-scalar schema_version reject outright.
lunara_test_assert( is_wp_error( lunara_oscars_portal_studio_validate_config( null ) ), 'A null candidate must reject.' );
$bad_schema = $defaults;
$bad_schema['schema_version'] = array( 'v' );
$bad_schema_result = lunara_oscars_portal_studio_validate_config( $bad_schema );
lunara_test_assert( is_wp_error( $bad_schema_result ) && 'oscars_portal_config_invalid' === $bad_schema_result->get_error_code(), 'A non-scalar schema_version must reject as config-invalid.' );

// Identity text is bounded to each spec's max characters.
$long_identity = $defaults;
$long_identity['identity']['kicker'] = str_repeat( 'K', 200 );
$long_identity_valid = lunara_oscars_portal_studio_validate_config( $long_identity );
lunara_test_assert( ! is_wp_error( $long_identity_valid ) && 140 === strlen( $long_identity_valid['identity']['kicker'] ), 'Identity copy must clamp to the spec maximum.' );
$empty_identity = $defaults;
$empty_identity['identity']['kicker'] = '   ';
$empty_identity_valid = lunara_oscars_portal_studio_validate_config( $empty_identity );
lunara_test_assert( ! is_wp_error( $empty_identity_valid ) && 'The Lunara Oscar Ledger' === $empty_identity_valid['identity']['kicker'], 'An explicitly empty identity field must fold to its shipped literal.' );

// The derived lanes are canonicalized from their owners, not trusted raw.
$forced_lanes = $defaults;
$forced_lanes['section_visibility']['board']     = false;
$forced_lanes['section_visibility']['navigator'] = true;
$forced_lanes['section_visibility']['doors']     = false;
$canonical = lunara_oscars_portal_studio_validate_config( $forced_lanes );
lunara_test_assert( ! is_wp_error( $canonical ) && true === $canonical['section_visibility']['board'] && false === $canonical['section_visibility']['navigator'], 'Board must validate to always-true and navigator must follow the doors toggle.' );

// Any complete permutation round-trips; unknown slugs fail the permutation.
$permuted = $defaults;
$permuted['section_order'] = array( 'rotating-winners', 'deep-cuts', 'winners', 'linked-reviews', 'research', 'titles', 'spotlights', 'doors', 'board', 'navigator', 'hero' );
lunara_test_assert( $permuted['section_order'] === lunara_oscars_portal_studio_validate_config( $permuted )['section_order'], 'Any complete eleven-slot permutation must round-trip exactly.' );
$unknown_slot = $defaults;
$unknown_slot['section_order'] = array( 'hero', 'navigator', 'board', 'doors', 'spotlights', 'titles', 'research', 'linked-reviews', 'winners', 'deep-cuts', 'bogus-slot' );
lunara_test_assert( is_wp_error( lunara_oscars_portal_studio_validate_config( $unknown_slot ) ), 'An unknown slot slug must fail the permutation contract.' );

// ---------------------------------------------------------------------------
// Fail-closed repair of corrupt stored enums/orders/geometry.
// ---------------------------------------------------------------------------
$lunara_test_options[ LUNARA_OSCARS_PORTAL_STUDIO_OPTION ] = array(
	'schema_version' => 'x',
	'section_order'  => array( 'hero', 'hero', 'unknown-slug', 42, array( 'nested' ) ),
	'presentation'   => array( 'section_gap' => 9999, 'hero_min_height' => 'soup', 'card_min_height' => array( 'bad' ) ),
);
$repaired = lunara_oscars_portal_studio_get_public_config( false );
lunara_test_assert( ! is_wp_error( $repaired ), 'A corrupt stored option must never surface a WP_Error publicly.' );
$sorted_slots    = lunara_oscars_portal_studio_slots();
$sorted_repaired = $repaired['section_order'];
sort( $sorted_slots );
sort( $sorted_repaired );
lunara_test_assert( $sorted_slots === $sorted_repaired && 11 === count( $repaired['section_order'] ), 'Repair must land on exactly the eleven canonical slots.' );
lunara_test_assert( 'hero' === $repaired['section_order'][0], 'The recognized stored slug must keep its relative position through repair.' );
lunara_test_assert( array( 40, 360, 360 ) === array( $repaired['presentation']['section_gap'], $repaired['presentation']['hero_min_height'], $repaired['presentation']['card_min_height'] ), 'Out-of-bounds or non-numeric geometry must repair to the defaults.' );
$lunara_test_options = array();

// The comma-string repair path collapses duplicates and appends missing slots.
lunara_test_assert(
	array( 'board', 'hero', 'navigator', 'doors', 'spotlights', 'titles', 'research', 'linked-reviews', 'winners', 'deep-cuts', 'rotating-winners' ) === lunara_oscars_portal_studio_expand_section_order( 'board,BOARD,bogus,hero' ),
	'Corrupt order tokens must repair to a complete canonical eleven-slot permutation.'
);

// A partially-reordered valid stored order survives resolution as-is.
$lunara_test_options[ LUNARA_OSCARS_PORTAL_STUDIO_OPTION ] = array(
	'schema_version' => 1,
	'section_order'  => array( 'deep-cuts', 'hero', 'navigator', 'board', 'doors', 'spotlights', 'titles', 'research', 'linked-reviews', 'winners', 'rotating-winners' ),
	'presentation'   => array( 'section_gap' => 60, 'hero_min_height' => 420, 'card_min_height' => 300 ),
);
$saved = lunara_oscars_portal_studio_get_public_config( false );
lunara_test_assert( 'deep-cuts' === $saved['section_order'][0] && 60 === $saved['presentation']['section_gap'], 'A valid stored order and geometry must resolve unchanged.' );
lunara_test_assert( true === lunara_oscars_portal_studio_has_saved_presentation(), 'The provenance gate must open once a presentation has been explicitly stored.' );

// ---------------------------------------------------------------------------
// EXECUTED builder coverage: variable CSS provenance gating + structural seed.
// ---------------------------------------------------------------------------
$saved_vars = lunara_oscars_portal_variable_css( $saved );
lunara_test_assert(
	'#primary.lunara-oscars-portal{--lunara-oscars-portal-section-gap:60px;--lunara-oscars-portal-hero-min-height:420px;--lunara-oscars-portal-card-min-height:300px}' === $saved_vars,
	'A saved presentation must emit the exact geometry custom-property block.'
);
$lunara_test_options = array();
$unsaved_config      = lunara_oscars_portal_studio_get_public_config( false );
lunara_test_assert( '' === lunara_oscars_portal_variable_css( $unsaved_config ), 'An unsaved site must emit NO geometry variables even though the resolved config carries bounded defaults.' );
$preview_config             = $unsaved_config;
$preview_config['_preview'] = true;
lunara_test_assert(
	'#primary.lunara-oscars-portal{--lunara-oscars-portal-section-gap:40px;--lunara-oscars-portal-hero-min-height:360px;--lunara-oscars-portal-card-min-height:360px}' === lunara_oscars_portal_variable_css( $preview_config ),
	'An authorized private preview must emit geometry variables without any saved option.'
);
lunara_test_assert( '' === lunara_oscars_portal_variable_css( 'garbage' ), 'A non-array config must fail the variable emitter closed.' );
lunara_test_assert( '' === lunara_oscars_portal_variable_css( array( '_preview' => true, 'presentation' => array( 'section_gap' => 40 ) ) ), 'A presentation missing geometry keys must fail the variable emitter closed.' );
lunara_test_assert( '' === lunara_oscars_portal_variable_css( array( '_preview' => true, 'presentation' => array( 'section_gap' => array( 40 ), 'hero_min_height' => 360, 'card_min_height' => 360 ) ) ), 'A non-scalar geometry leaf must fail the variable emitter closed.' );

$portal_seed = lunara_oscars_portal_critical_css();
lunara_test_assert( is_string( $portal_seed ) && '' !== $portal_seed, 'The portal structural seed must be nonempty.' );
lunara_test_assert( false !== strpos( $portal_seed, 'order:initial!important' ), 'The seed must neutralize historic numeric order declarations so CSS can never become a second order owner.' );
lunara_test_assert( false !== strpos( $portal_seed, '#primary.lunara-oscars-portal>:is([class*="lunara-oscars-portal-slot-"],.lunara-oscars-navigator)' ), 'The order neutralization must target the slot chambers and the navigator.' );
lunara_test_assert( false !== strpos( $portal_seed, 'var(--lunara-oscars-portal-section-gap,clamp(34px,4.8vw,58px))' ), 'The seed must fall back to the shipped clamp geometry when no Studio variables are stamped.' );
lunara_test_assert( strlen( $portal_seed ) <= 12288, 'The portal structural seed must stay at or below 12 KB.' );
lunara_test_assert( strlen( $portal_seed ) + strlen( $saved_vars ) <= 12288, 'The rendered portal vars plus seed must stay inside the 12 KB inline budget.' );

// ---------------------------------------------------------------------------
// Resolver token discipline for the licensed Tiempos label face.
// ---------------------------------------------------------------------------
$lunara_test_design_tokens = array();
lunara_test_assert( 'tiempos-text' === lunara_oscars_portal_resolved_label_font_slug(), 'An absent label token must resolve to the approved licensed Tiempos default.' );
lunara_test_assert( true === lunara_oscars_portal_uses_tiempos_label_face(), 'The approved default label token must activate the licensed Tiempos face.' );
$lunara_test_design_tokens = array( 'fonts' => array( 'label' => 'lora' ) );
lunara_test_assert( 'lora' === lunara_oscars_portal_resolved_label_font_slug(), 'A valid non-default Studio label token must round-trip without Tiempos substitution.' );
lunara_test_assert( false === lunara_oscars_portal_uses_tiempos_label_face(), 'A valid non-default Studio label token must bypass the route-owned Tiempos face.' );
$lunara_test_design_tokens = array( 'fonts' => array( 'label' => array( 'bad-shape' ) ) );
lunara_test_assert( 'tiempos-text' === lunara_oscars_portal_resolved_label_font_slug(), 'A corrupt label token must repair field-locally to the default without a type error.' );
$lunara_test_design_tokens = array( 'fonts' => array( 'label' => 'not-a-choice' ) );
lunara_test_assert( 'tiempos-text' === lunara_oscars_portal_resolved_label_font_slug(), 'An unknown label token must repair field-locally to the default.' );
foreach ( array( ' tiempos-text', 'tiempos-text ', 'tiempos_text!', 'Tiempos-Text', 'TIEMPOS-TEXT' ) as $corrupt_token ) {
	$lunara_test_design_tokens = array( 'fonts' => array( 'label' => $corrupt_token ) );
	lunara_test_assert( 'tiempos-text' === lunara_oscars_portal_resolved_label_font_slug(), 'Corrupt near-match "' . $corrupt_token . '" must follow the exact-key validator back to the default.' );
	lunara_test_assert( true === lunara_oscars_portal_uses_tiempos_label_face(), 'Corrupt near-match "' . $corrupt_token . '" must retain the canonical default Tiempos behavior.' );
}
$lunara_test_design_tokens = array( 'fonts' => array( 'label' => ' lora ' ) );
lunara_test_assert( 'tiempos-text' === lunara_oscars_portal_resolved_label_font_slug(), 'A whitespace-corrupt custom near-match must not become a valid custom label token.' );
$lunara_test_design_tokens = array();

// ---------------------------------------------------------------------------
// Promotion writes through canonical owners; invalid changes nothing public.
// ---------------------------------------------------------------------------
$lunara_test_options    = array();
$lunara_test_theme_mods = array();
$dup_order              = $defaults;
$dup_order['section_order'] = array( 'hero', 'hero', 'board', 'doors', 'spotlights', 'titles', 'research', 'linked-reviews', 'winners', 'deep-cuts', 'rotating-winners' );
$options_before = $lunara_test_options;
$mods_before    = $lunara_test_theme_mods;
$result         = lunara_oscars_portal_studio_promote_config( $dup_order, 'save' );
lunara_test_assert( is_wp_error( $result ), 'Promoting an invalid candidate must return the validator error.' );
lunara_test_assert( $options_before === $lunara_test_options && $mods_before === $lunara_test_theme_mods, 'A rejected promotion must write no option and no theme mod.' );

$candidate = $defaults;
$candidate['section_order']                    = array( 'hero', 'navigator', 'board', 'doors', 'titles', 'spotlights', 'research', 'linked-reviews', 'winners', 'deep-cuts', 'rotating-winners' );
$candidate['section_visibility']['spotlights'] = false;
$candidate['identity']['kicker']               = 'Promoted Kicker';
$candidate['presentation']['section_gap']      = 52;
$result = lunara_oscars_portal_studio_promote_config( $candidate, 'save' );
lunara_test_assert( ! is_wp_error( $result ), 'A valid candidate must promote.' );
lunara_test_assert( 'Promoted Kicker' === $lunara_test_theme_mods['lunara_oscars_portal_kicker'], 'Identity copy must save through its canonical lunara_oscars_portal_* mod.' );
lunara_test_assert( false === $lunara_test_theme_mods['lunara_oscars_show_spotlights'], 'Visibility must save through its canonical lunara_oscars_show_* mod.' );
lunara_test_assert( true === $lunara_test_theme_mods['lunara_oscars_rotating_winners_enabled'], 'The rotating-winners visibility must save through its canonical enable mod.' );
$stored_option = $lunara_test_options[ LUNARA_OSCARS_PORTAL_STUDIO_OPTION ];
lunara_test_assert( array( 'schema_version', 'section_order', 'presentation' ) === array_keys( $stored_option ), 'The Studio option must store ONLY the order and geometry — never a second owner for copy or visibility.' );
lunara_test_assert( 'titles' === $stored_option['section_order'][4] && 52 === $stored_option['presentation']['section_gap'], 'The saved order and geometry must land in the Studio option.' );
$revisions = lunara_oscars_portal_studio_get_revisions();
lunara_test_assert( 1 === count( $revisions ) && 'save' === $revisions[0]['action'] && ! empty( $revisions[0]['prior_public'] ), 'Promotion must push exactly one prior-public revision.' );
lunara_test_assert( isset( $revisions[0]['id'], $revisions[0]['saved_at'], $revisions[0]['saved_by'], $revisions[0]['validator_result'] ) && 7 === $revisions[0]['saved_by'] && 'passed' === $revisions[0]['validator_result'], 'The revision must record who, when, the action, and the validator result.' );

// Targeted route-cache invalidation stays bounded to the portal route.
$lunara_test_rocket_urls = null;
lunara_oscars_portal_studio_flush_route_cache();
$invalidation = end( $lunara_test_actions_fired );
lunara_test_assert( 'lunara_oscars_portal_studio_invalidate_routes' === $invalidation[0], 'Targeted route invalidation must be explicitly dispatched.' );
lunara_test_assert( array( '/oscars/' ) === $invalidation[1], 'Only the portal route family may be invalidated; ledger aat_* URLs are contractually exempt.' );
lunara_test_assert( array( 'https://example.test/oscars/' ) === $invalidation[2], 'The bounded cleaner must name exactly the portal URL.' );
lunara_test_assert( array( 'https://example.test/oscars/' ) === $lunara_test_rocket_urls, 'The bounded per-URL cache cleaner must receive only the portal URL.' );

// ---------------------------------------------------------------------------
// Revision restore: tampered-ID and tampered-snapshot fail closed; limit 12.
// ---------------------------------------------------------------------------
$tampered_restore = lunara_oscars_portal_studio_restore_revision( 'uuid-4-test-tampered' );
lunara_test_assert( is_wp_error( $tampered_restore ) && 'oscars_portal_revision_not_found' === $tampered_restore->get_error_code(), 'Restoring a tampered revision ID must fail closed.' );
lunara_test_assert( 'titles' === lunara_oscars_portal_studio_get_public_config( false )['section_order'][4], 'A failed restore must leave the current public state unchanged.' );

$tampered_revisions = lunara_oscars_portal_studio_get_revisions();
$revision_id        = $tampered_revisions[0]['id'];
$tampered_revisions[0]['config']['presentation']['section_gap'] = 9999;
update_option( LUNARA_OSCARS_PORTAL_STUDIO_REVISIONS_OPTION, $tampered_revisions );
$tampered_config_restore = lunara_oscars_portal_studio_restore_revision( $revision_id );
lunara_test_assert( is_wp_error( $tampered_config_restore ) && 'oscars_portal_geometry_invalid' === $tampered_config_restore->get_error_code(), 'Restoring a revision whose snapshot was tampered invalid must revalidate and fail closed.' );
lunara_test_assert( 'titles' === lunara_oscars_portal_studio_get_public_config( false )['section_order'][4], 'A tampered snapshot must never reach public state.' );
$tampered_revisions[0]['config']['presentation']['section_gap'] = 40;
update_option( LUNARA_OSCARS_PORTAL_STUDIO_REVISIONS_OPTION, $tampered_revisions );
$restored = lunara_oscars_portal_studio_restore_revision( $revision_id );
lunara_test_assert( ! is_wp_error( $restored ) && 'spotlights' === $restored['section_order'][4], 'Restoring the repaired prior snapshot must return the default order.' );
lunara_test_assert( 'spotlights' === lunara_oscars_portal_studio_get_public_config( false )['section_order'][4], 'Restore must reinstate the prior public configuration.' );
$restore_revisions = lunara_oscars_portal_studio_get_revisions();
lunara_test_assert( 'restore' === $restore_revisions[0]['action'] && ! empty( $restore_revisions[0]['prior_public'] ), 'Restore must itself push a prior-public audit row.' );
for ( $i = 0; $i < 20; $i++ ) {
	lunara_oscars_portal_studio_push_revision( $defaults, 'save', 'passed', true );
}
lunara_test_assert( LUNARA_OSCARS_PORTAL_STUDIO_REVISION_LIMIT === count( lunara_oscars_portal_studio_get_revisions() ), 'Revision history must stay bounded at twelve snapshots.' );
$verified_oscars_revision = lunara_oscars_portal_studio_push_revision( $defaults, 'save' );
lunara_test_assert( is_string( $verified_oscars_revision ) && $verified_oscars_revision === lunara_oscars_portal_studio_get_revisions()[0]['id'], 'Revision writes must return the exact UUID verified on readback.' );
$oscars_public_before_failure = lunara_oscars_portal_studio_get_public_config( false );
$oscars_cache_actions_before_failure = count( $lunara_test_actions_fired );
$oscars_candidate_after_failure = $oscars_public_before_failure;
$oscars_candidate_after_failure['identity']['title'] = 'Must never publish without durable history';
$lunara_test_revision_write_mode = 'fail';
$oscars_write_failure = lunara_oscars_portal_studio_promote_config( $oscars_candidate_after_failure, 'save' );
lunara_test_assert( is_wp_error( $oscars_write_failure ) && $oscars_public_before_failure === lunara_oscars_portal_studio_get_public_config( false ) && $oscars_cache_actions_before_failure === count( $lunara_test_actions_fired ), 'Oscars save must abort before live mutation/invalidation when revision update_option fails.' );
$lunara_test_revision_write_mode = 'mismatch';
$oscars_readback_failure = lunara_oscars_portal_studio_promote_config( $oscars_candidate_after_failure, 'save' );
lunara_test_assert( is_wp_error( $oscars_readback_failure ) && $oscars_public_before_failure === lunara_oscars_portal_studio_get_public_config( false ) && $oscars_cache_actions_before_failure === count( $lunara_test_actions_fired ), 'Oscars save must abort before live mutation/invalidation when revision UUID readback fails.' );
$oscars_restore_target = lunara_oscars_portal_studio_get_revisions()[0]['id'];
$oscars_restore_failure = lunara_oscars_portal_studio_restore_revision( $oscars_restore_target );
lunara_test_assert( is_wp_error( $oscars_restore_failure ) && $oscars_public_before_failure === lunara_oscars_portal_studio_get_public_config( false ) && $oscars_cache_actions_before_failure === count( $lunara_test_actions_fired ), 'Oscars restore must abort before live mutation/invalidation when its safety revision readback fails.' );
$lunara_test_revision_write_mode = 'normal';
$lunara_test_options    = array();
$lunara_test_theme_mods = array();

// ---------------------------------------------------------------------------
// The composer respects order + visibility and drops hidden/empty/unknown.
// ---------------------------------------------------------------------------
$markup = array(
	'hero'      => '<section class="hero">H</section>',
	'navigator' => '<nav>N</nav>',
	'board'     => '<section id="oscars-board">B</section>',
	'doors'     => '<section id="oscars-doors">D</section>',
	'empty'     => '',
);
$order      = array( 'doors', 'board', 'hero', 'navigator', 'empty', 'missing' );
$visibility = array( 'hero' => true, 'navigator' => false, 'board' => true, 'doors' => true, 'empty' => true, 'missing' => true );
$composed   = lunara_oscars_portal_render_sections( $markup, $order, $visibility );
lunara_test_assert( '<section id="oscars-doors">D</section><section id="oscars-board">B</section><section class="hero">H</section>' === $composed, 'The composer must honor the saved order, drop hidden slots, and skip empty or unknown markup.' );
lunara_test_assert( '' === lunara_oscars_portal_render_sections( $markup, $order, array() ), 'An all-hidden visibility map must compose to an empty string.' );
lunara_test_assert( '' === lunara_oscars_portal_render_sections( array(), array( 'hero' ), array( 'hero' => true ) ), 'A visible slot with no markup must contribute nothing.' );
lunara_test_assert( '' === lunara_oscars_portal_render_sections( $markup, 'not-an-array', $visibility ), 'A malformed order must compose to the empty string, never fatal.' );
$full_visible = lunara_oscars_portal_render_sections(
	array( 'hero' => 'A', 'navigator' => 'B', 'board' => 'C', 'doors' => 'D' ),
	array( 'hero', 'navigator', 'board', 'doors' ),
	array( 'hero' => true, 'navigator' => true, 'board' => true, 'doors' => true )
);
lunara_test_assert( 'ABCD' === $full_visible, 'The default order with full visibility must reproduce simple concatenation byte-for-byte.' );

// ---------------------------------------------------------------------------
// The three migrated data helpers: reader-gated, never SQL.
// ---------------------------------------------------------------------------
if ( 'accessors' === $lunara_test_mode ) {
	$query = lunara_oscars_linked_reviews_query( 2 );
	lunara_test_assert( $query instanceof WP_Query && isset( $query->query_args['post__in'] ), 'With the accessor present the linked-reviews query must be driven by accessor post IDs.' );
	$expected_ids = array_map( 'intval', array_slice( Academy_Awards_Table::get_instance()->get_reviewed_award_title_post_ids( 6, intval( date( 'z' ) ) % 20 ), 0, 2 ) );
	lunara_test_assert( $query->query_args['post__in'] === $expected_ids, 'The linked-reviews query must slice the accessor pool exactly like the retired direct join.' );

	$story = lunara_build_home_title_story( ' TT0000031 ', array( 'max_categories' => 2 ) );
	lunara_test_assert( 'Context Fixture' === $story['title'] && 2 === $story['wins'] && 3 === $story['nominations'], 'The title story must map the accessor context fields.' );
	lunara_test_assert( 'Best Picture / Directing' === $story['categories_line'], 'The title story must build its categories line from the accessor display categories.' );
	lunara_test_assert( 'https://example.test/oscars/title/tt0000031/' === $story['url'], 'The title story must keep building its URL through the plugin URL helper.' );
	lunara_test_assert( array() === lunara_build_home_title_story( 'tt0000404' ), 'An unknown title context must degrade the story card to empty.' );

	// Pipe-joined co-production film_id values (the awards table's multi-value
	// column shape) normalize to their FIRST tt token instead of being
	// silently rejected by the accessor's ^tt\d+$ gate.
	$pipe_story = lunara_build_home_title_story( 'tt0000031|tt0000032', array( 'max_categories' => 2 ) );
	lunara_test_assert( is_array( $pipe_story ) && ! empty( $pipe_story ) && 'tt0000031' === $pipe_story['imdb_id'], 'A pipe-joined film_id must normalize to its first tt token and keep its card.' );
	lunara_test_assert( 'https://example.test/oscars/title/tt0000031/' === $pipe_story['url'], 'The pipe-joined card must build its URL from the normalized single token.' );
	lunara_test_assert( array() === lunara_build_home_title_story( 'no-token-here' ), 'A film_id with no tt token must still degrade the story card to empty.' );

	lunara_test_assert( true === lunara_home_category_debuts_in_ceremony( 'CASTING', 97 ), 'A category whose accessor debut matches the ceremony must report a debut.' );
	lunara_test_assert( false === lunara_home_category_debuts_in_ceremony( 'CASTING', 96 ), 'A non-matching ceremony must not report a debut.' );
	lunara_test_assert( false === lunara_home_category_debuts_in_ceremony( '', 97 ), 'An empty category must never report a debut.' );

	// Accessor present but pool empty: today's generic-reviews fallback query
	// (meta_query on the IMDb key) must survive the migration untouched.
	$GLOBALS['lunara_test_empty_pool'] = true;
	$fallback = lunara_oscars_linked_reviews_query( 4 );
	lunara_test_assert( $fallback instanceof WP_Query && ! isset( $fallback->query_args['post__in'] ) && isset( $fallback->query_args['meta_query'] ), 'An empty accessor pool must keep falling back to the IMDb meta_query, exactly as an empty join did before.' );
	$GLOBALS['lunara_test_empty_pool'] = false;
} else {
	// Degraded (accessor-less plugin) and noplugin modes behave identically:
	// the ledger-joined priority is skipped and linked-reviews degrades to
	// the SQL-FREE IMDb meta_query fallback (a plain WP_Query on
	// _lunara_imdb_title_id) — never to an empty section, and never to SQL.
	$query = lunara_oscars_linked_reviews_query( 4 );
	lunara_test_assert( $query instanceof WP_Query && ! isset( $query->query_args['post__in'] ) && isset( $query->query_args['meta_query'] ), 'Without the accessor the linked-reviews helper must degrade to the SQL-free IMDb meta_query fallback.' );
	lunara_test_assert( array() === lunara_build_home_title_story( 'tt0000031' ), 'Without the accessor the title story must degrade to empty.' );
	lunara_test_assert( false === lunara_home_category_debuts_in_ceremony( 'CASTING', 97 ), 'Without the accessor the debut check must degrade to false.' );
}

// ---------------------------------------------------------------------------
// Preview tokens: owner-bound, capability-gated, expiring, tamper-evident,
// and no-store ALWAYS strictly precedes any token transient read.
// ---------------------------------------------------------------------------
$preview_candidate = $defaults;
$preview_candidate['section_visibility']['spotlights'] = false;
$token = lunara_oscars_portal_studio_store_preview( $preview_candidate );
lunara_test_assert( is_string( $token ) && '' !== $token, 'A valid candidate must produce a preview token.' );
$preview = lunara_oscars_portal_studio_get_preview_config( $token );
lunara_test_assert( is_array( $preview ) && false === $preview['section_visibility']['spotlights'], 'The token owner must read back the staged preview.' );

$_GET['lunara_oscars_preview'] = $token;
$via_config = lunara_oscars_portal_studio_get_public_config( true );
lunara_test_assert( ! empty( $via_config['_preview'] ) && false === $via_config['section_visibility']['spotlights'], 'The resolved config must adopt a valid preview token and flag it.' );
unset( $_GET['lunara_oscars_preview'] );

// A normal request stays public/cacheable: no handling, no no-store headers.
$nocache_before  = $lunara_test_nocache_calls;
$normal_response = lunara_oscars_portal_studio_prepare_preview_response();
lunara_test_assert( false === $normal_response['handled'] && true === $normal_response['authorized'] && 200 === $normal_response['status'] && $lunara_test_nocache_calls === $nocache_before, 'A normal portal request must remain public/cacheable and never receive preview denial headers.' );

// Authorized owner: 200 + no-store strictly before the token transient read.
$_GET['lunara_oscars_preview'] = $token;
$nocache_before = $lunara_test_nocache_calls;
$actions_before = count( $lunara_test_actions_fired );
$response       = lunara_oscars_portal_studio_prepare_preview_response( true );
lunara_test_assert( ! empty( $response['handled'] ) && ! empty( $response['authorized'] ) && 200 === $response['status'], 'A valid token on the portal family must authorize.' );
lunara_test_assert( $lunara_test_nocache_calls === $nocache_before + 1, 'Every preview-query response must become no-store before access validation.' );
lunara_test_assert( lunara_test_no_store_precedes_transient_read( array_slice( $lunara_test_actions_fired, $actions_before ) ), 'The no-store marker must fire at a strictly earlier index than the preview transient read.' );

// Foreign user: owner binding denies, still no-store-before-token-work.
$lunara_test_user_id = 9;
lunara_test_assert( false === lunara_oscars_portal_studio_get_preview_config( $token ), 'A preview token must be bound to its creating user.' );
$nocache_before = $lunara_test_nocache_calls;
$actions_before = count( $lunara_test_actions_fired );
$foreign        = lunara_oscars_portal_studio_prepare_preview_response( true );
lunara_test_assert( ! empty( $foreign['handled'] ) && empty( $foreign['authorized'] ) && 403 === $foreign['status'] && $lunara_test_nocache_calls === $nocache_before + 1, 'A foreign-user token URL must be denied as a non-cacheable 403 response.' );
lunara_test_assert( lunara_test_no_store_precedes_transient_read( array_slice( $lunara_test_actions_fired, $actions_before ) ), 'A foreign-user denial must also send no-store strictly before its token transient read.' );
$lunara_test_user_id = 7;

// No capability: storing and reading both fail; the URL is denied no-store.
$lunara_test_can_edit = false;
lunara_test_assert( false === lunara_oscars_portal_studio_get_preview_config( $token ), 'A preview token must require theme-editing capability.' );
lunara_test_assert( is_wp_error( lunara_oscars_portal_studio_store_preview( $defaults ) ), 'Storing a preview must require theme-editing capability.' );
$nocache_before = $lunara_test_nocache_calls;
$anonymous      = lunara_oscars_portal_studio_prepare_preview_response( true );
lunara_test_assert( ! empty( $anonymous['handled'] ) && empty( $anonymous['authorized'] ) && 403 === $anonymous['status'] && $lunara_test_nocache_calls === $nocache_before + 1, 'An unauthorized preview request must be denied no-store rather than falling back to public output.' );
$lunara_test_can_edit = true;

// Guessed/forged token: denied 403, no-store precedes its transient read.
$_GET['lunara_oscars_preview'] = 'forged-token';
$actions_before = count( $lunara_test_actions_fired );
$forged         = lunara_oscars_portal_studio_prepare_preview_response( true );
lunara_test_assert( ! empty( $forged['handled'] ) && empty( $forged['authorized'] ) && 403 === $forged['status'], 'A forged token must be handled and denied with 403.' );
lunara_test_assert( lunara_test_no_store_precedes_transient_read( array_slice( $lunara_test_actions_fired, $actions_before ) ), 'A forged-token denial must also send no-store strictly before its token transient read.' );
$_GET['lunara_oscars_preview'] = $token;

// Tampered owner-binding hash: hash_equals rejects the preview.
$preview_transient_key = 'lunara_oscars_portal_preview_' . hash( 'sha256', $token );
$genuine_hash          = $lunara_test_transients[ $preview_transient_key ]['value']['token_hash'];
$lunara_test_transients[ $preview_transient_key ]['value']['token_hash'] = 'tampered-hash';
lunara_test_assert( false === lunara_oscars_portal_studio_get_preview_config( $token ), 'A tampered owner-binding hash must reject the preview.' );
$tampered_hash_response = lunara_oscars_portal_studio_prepare_preview_response( true );
lunara_test_assert( empty( $tampered_hash_response['authorized'] ) && 403 === $tampered_hash_response['status'], 'A tampered-hash token URL must receive a no-store 403 response.' );
$lunara_test_transients[ $preview_transient_key ]['value']['token_hash'] = $genuine_hash;

// Tampered internal expiry, then true clock expiry past the 30-minute TTL.
$genuine_expires = $lunara_test_transients[ $preview_transient_key ]['value']['expires'];
$lunara_test_transients[ $preview_transient_key ]['value']['expires'] = 1;
lunara_test_assert( false === lunara_oscars_portal_studio_get_preview_config( $token ), 'A tampered internal expiry must reject the preview.' );
$lunara_test_transients[ $preview_transient_key ]['value']['expires'] = $genuine_expires;
$lunara_test_now += 1900;
lunara_test_assert( false === lunara_oscars_portal_studio_get_preview_config( $token ), 'The preview must expire after its bounded thirty-minute lifetime.' );
$expired_response = lunara_oscars_portal_studio_prepare_preview_response( true );
lunara_test_assert( empty( $expired_response['authorized'] ) && 403 === $expired_response['status'], 'An expired token URL must receive a no-store 403 response.' );
unset( $_GET['lunara_oscars_preview'] );

// A preview token outside the portal family is ignored entirely: no preview,
// no denial, no cache poisoning. A preview token can never restyle a ledger.
$fresh_token = lunara_oscars_portal_studio_store_preview( $preview_candidate );
$_GET['lunara_oscars_preview'] = $fresh_token;
$nocache_before = $lunara_test_nocache_calls;
$off_family     = lunara_oscars_portal_studio_prepare_preview_response( false );
lunara_test_assert( empty( $off_family['handled'] ) && $lunara_test_nocache_calls === $nocache_before, 'A preview token outside the portal family must be ignored: no preview, no denial, no cache poisoning.' );

// Pre-query preflight: bad tokens die 403 on the portal main query only.
// The candidate queries model pre_get_posts truthfully: no queried object
// exists yet, so is_page() is false and the preflight must fire from the
// pagename/page_id query vars — the only signals real WP has at that hook.
$_GET['lunara_oscars_preview'] = 'guessed-token';
$lunara_test_status_headers    = array();
$portal_query                  = new WP_Query();
$portal_query->query_vars['pagename'] = 'oscars';
lunara_test_assert( false === $portal_query->is_page( 'oscars' ), 'Truthful WP model: is_page() must be false while the queried object is unset (pre-query).' );
try {
	lunara_oscars_portal_studio_preflight_preview_query( $portal_query );
	lunara_test_assert( false, 'A bad preview token on the portal main query must die before Studio config (via the pagename path).' );
} catch ( Lunara_Test_WP_Die $died ) {
	lunara_test_assert( in_array( 403, $lunara_test_status_headers, true ), 'The pre-query denial must be an explicit 403.' );
}
// The page_id spelling of the portal query is detected too (the stubbed
// get_page_by_path('oscars') resolves to ID 700).
$page_id_query = new WP_Query();
$page_id_query->query_vars['page_id'] = 700;
try {
	lunara_oscars_portal_studio_preflight_preview_query( $page_id_query );
	lunara_test_assert( false, 'A bad preview token on the page_id portal query must die before Studio config.' );
} catch ( Lunara_Test_WP_Die $died ) {
	lunara_test_assert( true, 'page_id path denied.' );
}
$other_query = new WP_Query();
$other_query->query_vars['pagename'] = 'about';
lunara_oscars_portal_studio_preflight_preview_query( $other_query );
$_GET['lunara_oscars_preview'] = $fresh_token;
$authorized_query = new WP_Query();
$authorized_query->query_vars['pagename'] = 'oscars';
lunara_oscars_portal_studio_preflight_preview_query( $authorized_query );
unset( $_GET['lunara_oscars_preview'] );

// ---------------------------------------------------------------------------
// Rejected saves become a bounded private draft with allowlisted feedback.
// ---------------------------------------------------------------------------
$rejected_request = array(
	'lunara_oscars_portal_identity'          => array( 'kicker' => 'Draft kicker to keep' ),
	'lunara_oscars_portal_section_positions' => array_fill_keys( $lunara_template_order, 2 ),
);
$rejected_candidate = lunara_oscars_portal_studio_config_from_request( $rejected_request );
lunara_test_assert( array( 'invalid-position' ) === $rejected_candidate['section_order'], 'Duplicate composer positions must surface as an invalid order candidate.' );
$rejected_result = lunara_oscars_portal_studio_validate_config( $rejected_candidate );
lunara_test_assert( is_wp_error( $rejected_result ) && 'oscars_portal_section_order_invalid' === $rejected_result->get_error_code(), 'Duplicate positions must fail before any public mutation.' );
lunara_oscars_portal_studio_store_invalid_stage( $rejected_candidate, $rejected_request, $rejected_result->get_error_code() );
$private_stage = lunara_oscars_portal_studio_get_invalid_stage();
lunara_test_assert( is_array( $private_stage ) && 'Draft kicker to keep' === $private_stage['identity']['kicker'], 'Rejected edits must return as a bounded private per-user draft instead of disappearing.' );
lunara_test_assert( 2 === $private_stage['_staged_positions']['hero'] && 2 === $private_stage['_staged_positions']['board'], 'Rejected duplicate section positions must remain visible for correction.' );
lunara_test_assert( 'Give every portal section one unique position.' === lunara_oscars_portal_studio_validation_message(), 'Invalid save feedback must resolve through the bounded validator-message allowlist.' );
lunara_oscars_portal_studio_clear_invalid_stage();
lunara_test_assert( false === lunara_oscars_portal_studio_get_invalid_stage(), 'A successful transition must be able to clear the rejected private draft.' );

// The focused form adapter round-trips a complete reordering request.
$reorder_positions = array_combine( $lunara_template_order, range( 1, 11 ) );
$reorder_positions['deep-cuts'] = 1;
$reorder_positions['hero']      = 10;
$reorder_request = array(
	'lunara_oscars_portal_identity'           => array( 'kicker' => '', 'title' => '' ),
	'lunara_oscars_portal_section_visibility' => array_fill_keys( $lunara_template_order, 1 ),
	'lunara_oscars_portal_section_positions'  => $reorder_positions,
	'lunara_oscars_portal_number'             => array( 'section_gap' => 48, 'hero_min_height' => 420, 'card_min_height' => 320 ),
);
$reorder_candidate = lunara_oscars_portal_studio_config_from_request( $reorder_request );
$reorder_validated = lunara_oscars_portal_studio_validate_config( $reorder_candidate );
lunara_test_assert( ! is_wp_error( $reorder_validated ), 'A complete unique-position request must validate.' );
lunara_test_assert( 'deep-cuts' === $reorder_validated['section_order'][0] && 'hero' === $reorder_validated['section_order'][9], 'Position values must sort into the saved slot order.' );
lunara_test_assert( array( 48, 420, 320 ) === array( $reorder_validated['presentation']['section_gap'], $reorder_validated['presentation']['hero_min_height'], $reorder_validated['presentation']['card_min_height'] ), 'Bounded geometry numbers must round-trip from the focused form.' );

// ---------------------------------------------------------------------------
// Reader-degradation child processes (accessors mode only).
// ---------------------------------------------------------------------------
if ( 'accessors' === $lunara_test_mode ) {
	foreach ( array( 'degraded', 'noplugin' ) as $child_mode ) {
		$child_environment = getenv();
		$child_environment = is_array( $child_environment ) ? $child_environment : array();
		$child_environment['LUNARA_OSCARS_PORTAL_MODE'] = $child_mode;
		$child_process = proc_open(
			array( PHP_BINARY, __FILE__ ),
			array(
				1 => array( 'pipe', 'w' ),
				2 => array( 'pipe', 'w' ),
			),
			$child_pipes,
			null,
			$child_environment
		);
		lunara_test_assert( is_resource( $child_process ), 'The ' . $child_mode . ' reader-degradation lane must start.' );
		$child_output = stream_get_contents( $child_pipes[1] ) . stream_get_contents( $child_pipes[2] );
		fclose( $child_pipes[1] );
		fclose( $child_pipes[2] );
		$code   = proc_close( $child_process );
		$output = preg_split( '/\R/', trim( $child_output ) );
		lunara_test_assert( 0 === $code, 'The ' . $child_mode . ' reader-degradation lane must pass: ' . implode( "\n", $output ) );
		lunara_test_assert( false !== strpos( implode( "\n", $output ), 'oscars-portal-studio-runtime: all assertions passed (' . $child_mode . ' mode)' ), 'The ' . $child_mode . ' lane must report success.' );
	}
}

fwrite( STDOUT, 'oscars-portal-studio-runtime: all assertions passed (' . $lunara_test_mode . " mode).\n" );
