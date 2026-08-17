<?php
/**
 * Execute the Oscars ledger overlay delivery from the shipped sources.
 *
 * Clones the oscars-late-css-route-scope-harness approach: the real frontend
 * emitters are token-extracted from inc/frontend.php and executed (alongside
 * the required inc/oscars-family.php and inc/oscars-ledger-critical.php
 * modules) against the same eleven route cases the late-CSS guard proved, so
 * the wp_head-6 variables, the wp_head-7 structural seed, and the enqueue-112
 * overlay print on exactly the entity/hub ledger routes — never on the
 * portal, admin, feeds, or the query-less home shape.
 *
 * It also proves, value for value, that the priority-6 saved-only variable
 * block and the preserved priority-1002 Dossier Studio emitter are identical
 * for the same saved mods on anonymous requests, and that an admin preset
 * preview shifts ONLY the 1002 emitter — the cacheable priority-6 block
 * never absorbs preview state.
 */

define( 'ABSPATH', __DIR__ . '/' ); // The required inc modules exit without it.

$root = dirname( __DIR__, 2 );

// ---------------------------------------------------------------------------
// Minimal real filter registry so the identity seam is fed through
// apply_filters exactly like the plugin composer does.
// ---------------------------------------------------------------------------
$GLOBALS['lunara_test_filters'] = array();

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['lunara_test_filters'][ $hook ][] = array( 'callback' => $callback, 'priority' => $priority, 'accepted_args' => $accepted_args );
	usort(
		$GLOBALS['lunara_test_filters'][ $hook ],
		static function ( $a, $b ) {
			return $a['priority'] <=> $b['priority'];
		}
	);
	return true;
}
function add_action( $hook, $callback = null, $priority = 10, $accepted_args = 1 ) { return true; }
function apply_filters( $hook, $value, ...$args ) {
	foreach ( $GLOBALS['lunara_test_filters'][ $hook ] ?? array() as $registered ) {
		$pass  = array_slice( $args, 0, max( 0, $registered['accepted_args'] - 1 ) );
		$value = call_user_func( $registered['callback'], $value, ...$pass );
	}
	return $value;
}

// ---------------------------------------------------------------------------
// Route + option state and faithful stubs.
// ---------------------------------------------------------------------------
$GLOBALS['lunara_ledger_test_state'] = array(
	'admin'          => false,
	'feed'           => false,
	'query_vars'     => array(),
	'is_page_oscars' => false,
	'theme_mods'     => array(),
	'can_edit'       => false,
	'aat_registered' => false,
	'enqueued'       => array(),
);

function is_admin() { return $GLOBALS['lunara_ledger_test_state']['admin']; }
function is_feed() { return $GLOBALS['lunara_ledger_test_state']['feed']; }
function get_query_var( $key ) { return $GLOBALS['lunara_ledger_test_state']['query_vars'][ $key ] ?? ''; }
function is_page( $page = '' ) { return 'oscars' === $page && $GLOBALS['lunara_ledger_test_state']['is_page_oscars']; }
function is_page_template( $template = '' ) { return false; }
function get_theme_mod( $key, $default = '' ) {
	$mods = $GLOBALS['lunara_ledger_test_state']['theme_mods'];
	return array_key_exists( $key, $mods ) ? $mods[ $key ] : $default;
}
function current_user_can( $capability ) { return $GLOBALS['lunara_ledger_test_state']['can_edit'] && 'edit_theme_options' === $capability; }
function sanitize_key( $value ) { if ( ! is_scalar( $value ) ) { throw new TypeError( 'sanitize_key expects a scalar test value' ); } return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function absint( $value ) { if ( ! is_scalar( $value ) ) { throw new TypeError( 'absint expects a scalar test value' ); } return abs( (int) $value ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function wp_unslash( $value ) { return $value; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( (string) $url, $component ); }
function wp_style_is( $handle, $status = 'enqueued' ) { return 'aat-styles' === $handle && 'registered' === $status && $GLOBALS['lunara_ledger_test_state']['aat_registered']; }
function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
	$GLOBALS['lunara_ledger_test_state']['enqueued'][] = array( 'handle' => $handle, 'deps' => $deps );
}
function lunara_resolve_theme_asset( $relative, $fallbacks = array() ) {
	return array( 'uri' => 'https://example.test/wp-content/themes/lunara/' . ltrim( $relative, '/' ), 'path' => '/theme/' . ltrim( $relative, '/' ) );
}
function lunara_theme_asset_version( $path ) { return 'v-test'; }

// The admin preset-preview surface, present so the 1002 emitter's
// preview-awareness can be exercised against the saved-only priority-6 vars.
function lunara_control_desk_oscars_dossier_preset_specs() {
	return array(
		'showcase-test' => array(
			'values' => array(
				'lunara_oscars_dossier_density'     => 'showcase',
				'lunara_oscars_dossier_section_gap' => 96,
			),
		),
	);
}

function lunara_extract_named_function( $path, $function_name, $replacement_name ) {
	$source = file_get_contents( $path );
	if ( false === $source ) {
		throw new RuntimeException( 'Unable to read ' . $path );
	}

	$tokens = token_get_all( $source );
	$count  = count( $tokens );

	for ( $i = 0; $i < $count; $i++ ) {
		if ( ! is_array( $tokens[ $i ] ) || T_FUNCTION !== $tokens[ $i ][0] ) {
			continue;
		}

		$name_index = $i + 1;
		while ( $name_index < $count && ( ! is_array( $tokens[ $name_index ] ) || T_STRING !== $tokens[ $name_index ][0] ) ) {
			$name_index++;
		}

		if ( $name_index >= $count || $function_name !== $tokens[ $name_index ][1] ) {
			continue;
		}

		$function_source = '';
		$brace_depth     = 0;
		$body_started    = false;

		for ( $j = $i; $j < $count; $j++ ) {
			$token = $tokens[ $j ];
			$text  = is_array( $token ) ? $token[1] : $token;

			if ( $j === $name_index ) {
				$text = $replacement_name;
			}

			$function_source .= $text;

			if ( '{' === $text ) {
				$body_started = true;
				$brace_depth++;
			} elseif ( '}' === $text && $body_started ) {
				$brace_depth--;
				if ( 0 === $brace_depth ) {
					return $function_source;
				}
			}
		}
	}

	throw new RuntimeException( sprintf( 'Function %s was not found in %s', $function_name, $path ) );
}

// The route-family boundary and the ledger critical module are loaded whole;
// no Academy_Awards_Table class is defined, so the family detector exercises
// its aat_* query-var fallback — the same classification the late-CSS guard
// proved across these cases.
require $root . '/inc/oscars-family.php';
require $root . '/inc/oscars-ledger-critical.php';

// The frontend emitters execute as shipped (extracted under their real names
// because inc/frontend.php itself cannot load standalone).
foreach ( array(
	'lunara_home_brand_number_setting',
	'lunara_home_select_setting',
	'lunara_frontend_is_oscars_ledger_route',
	'lunara_get_oscars_dossier_studio_select_value',
	'lunara_get_oscars_dossier_studio_number_value',
	'lunara_get_oscars_dossier_preview_preset_values',
	'lunara_get_oscars_dossier_saved_control_values',
	'lunara_output_oscars_ledger_vars_css',
	'lunara_output_oscars_ledger_critical_css',
	'lunara_enqueue_oscars_ledger_styles',
	'lunara_is_oscars_dossier_surface',
	'lunara_output_oscars_dossier_studio_css',
) as $frontend_function ) {
	eval( lunara_extract_named_function( $root . '/inc/frontend.php', $frontend_function, $frontend_function ) );
}

$report = array( 'cases' => array(), 'checks' => array() );

$record_check = static function ( $name, $passed, $detail = null ) use ( &$report ) {
	$report['checks'][ $name ] = array( 'passed' => (bool) $passed, 'detail' => $detail );
};

// ---------------------------------------------------------------------------
// 1. Route gating: the same eleven cases the late-CSS route-scope guard pins.
// ---------------------------------------------------------------------------
$cases = array(
	'empty-vars'           => array( 'admin' => false, 'feed' => false, 'is_page_oscars' => false, 'query_vars' => array(), 'ledger' => false ),
	'portal'               => array( 'admin' => false, 'feed' => false, 'is_page_oscars' => true, 'query_vars' => array(), 'ledger' => false ),
	'entity-with-id'       => array( 'admin' => false, 'feed' => false, 'is_page_oscars' => false, 'query_vars' => array( 'aat_entity' => 'title', 'aat_entity_id' => 'tt0110912' ), 'ledger' => true ),
	'entity-without-id'    => array( 'admin' => false, 'feed' => false, 'is_page_oscars' => false, 'query_vars' => array( 'aat_entity' => 'title' ), 'ledger' => false ),
	'hub-ceremony'         => array( 'admin' => false, 'feed' => false, 'is_page_oscars' => false, 'query_vars' => array( 'aat_hub' => 'ceremony', 'aat_hub_id' => '96' ), 'ledger' => true ),
	'hub-category'         => array( 'admin' => false, 'feed' => false, 'is_page_oscars' => false, 'query_vars' => array( 'aat_hub' => 'category', 'aat_hub_id' => 'best-picture' ), 'ledger' => true ),
	'hub-ceremonies-index' => array( 'admin' => false, 'feed' => false, 'is_page_oscars' => false, 'query_vars' => array( 'aat_hub' => 'ceremonies' ), 'ledger' => true ),
	'hub-categories-index' => array( 'admin' => false, 'feed' => false, 'is_page_oscars' => false, 'query_vars' => array( 'aat_hub' => 'categories' ), 'ledger' => true ),
	'hub-about'            => array( 'admin' => false, 'feed' => false, 'is_page_oscars' => false, 'query_vars' => array( 'aat_hub' => 'about' ), 'ledger' => true ),
	'admin-entity'         => array( 'admin' => true, 'feed' => false, 'is_page_oscars' => false, 'query_vars' => array( 'aat_entity' => 'person', 'aat_entity_id' => '123' ), 'ledger' => false ),
	'feed-hub'             => array( 'admin' => false, 'feed' => true, 'is_page_oscars' => false, 'query_vars' => array( 'aat_hub' => 'about' ), 'ledger' => false ),
);

foreach ( $cases as $case_name => $case ) {
	$GLOBALS['lunara_ledger_test_state']['admin']          = $case['admin'];
	$GLOBALS['lunara_ledger_test_state']['feed']           = $case['feed'];
	$GLOBALS['lunara_ledger_test_state']['is_page_oscars'] = $case['is_page_oscars'];
	$GLOBALS['lunara_ledger_test_state']['query_vars']     = $case['query_vars'];
	$GLOBALS['lunara_ledger_test_state']['enqueued']       = array();

	ob_start();
	lunara_output_oscars_ledger_vars_css();
	lunara_output_oscars_ledger_critical_css();
	$head_output = ob_get_clean();
	lunara_enqueue_oscars_ledger_styles();

	$vars_printed    = false !== strpos( $head_output, 'id="lunara-oscars-ledger-vars"' );
	$seed_printed    = false !== strpos( $head_output, 'id="lunara-oscars-ledger-critical-css"' );
	$overlay_handles = array_column( $GLOBALS['lunara_ledger_test_state']['enqueued'], 'handle' );

	$passed = $case['ledger']
		? ( $vars_printed && $seed_printed && array( 'lunara-oscars-ledger' ) === $overlay_handles )
		: ( '' === $head_output && array() === $overlay_handles );

	$report['cases'][ $case_name ] = array(
		'passed'          => $passed,
		'expected_ledger' => $case['ledger'],
		'vars_printed'    => $vars_printed,
		'seed_printed'    => $seed_printed,
		'enqueued'        => $overlay_handles,
	);
}

// The overlay's dependency chain: behind the plugin chain when aat-styles is
// registered, behind the theme shell otherwise.
$GLOBALS['lunara_ledger_test_state']['admin']          = false;
$GLOBALS['lunara_ledger_test_state']['feed']           = false;
$GLOBALS['lunara_ledger_test_state']['is_page_oscars'] = false;
$GLOBALS['lunara_ledger_test_state']['query_vars']     = array( 'aat_hub' => 'ceremony', 'aat_hub_id' => '96' );
$GLOBALS['lunara_ledger_test_state']['enqueued']       = array();
$GLOBALS['lunara_ledger_test_state']['aat_registered'] = false;
lunara_enqueue_oscars_ledger_styles();
$record_check( 'overlay-deps-fall-back-to-shell', array( 'lunara-shell' ) === ( $GLOBALS['lunara_ledger_test_state']['enqueued'][0]['deps'] ?? null ) );
$GLOBALS['lunara_ledger_test_state']['enqueued']       = array();
$GLOBALS['lunara_ledger_test_state']['aat_registered'] = true;
lunara_enqueue_oscars_ledger_styles();
$record_check( 'overlay-deps-chain-behind-plugin', array( 'aat-styles' ) === ( $GLOBALS['lunara_ledger_test_state']['enqueued'][0]['deps'] ?? null ) );

// ---------------------------------------------------------------------------
// 2. Value identity: the priority-6 saved-only block and the preserved
// priority-1002 emitter carry identical custom-property values for the same
// saved mods on an anonymous request.
// ---------------------------------------------------------------------------
function lunara_test_extract_shell_declarations( $css ) {
	if ( ! preg_match( '/body\.aat-shell-page\s*\{([^}]*)\}/', (string) $css, $block ) ) {
		return array();
	}
	preg_match_all( '/(--[a-z0-9-]+)\s*:\s*([^;]+?)\s*(?:;|$)/', $block[1], $pairs, PREG_SET_ORDER );
	$declarations = array();
	foreach ( $pairs as $pair ) {
		$declarations[ trim( $pair[1] ) ] = preg_replace( '/\s+/', ' ', trim( $pair[2] ) );
	}
	ksort( $declarations );
	return $declarations;
}

function lunara_test_serialize_declarations( $declarations ) {
	$out = array();
	foreach ( $declarations as $property => $value ) {
		$out[] = $property . ':' . $value;
	}
	return implode( ';', $out );
}

$saved_mods = array(
	'lunara_oscars_dossier_density'           => 'dense',
	'lunara_oscars_major_race_prominence'     => 'feature',
	'lunara_oscars_profile_scale'             => 'cinematic',
	'lunara_oscars_profile_media_treatment'   => 'archival-fit',
	'lunara_oscars_writeup_prominence'        => 'compact',
	'lunara_oscars_related_reviews_treatment' => 'compact-rail',
	'lunara_oscars_title_image_focus'         => 'center-top',
	'lunara_oscars_dossier_section_gap'       => 64,
	'lunara_oscars_dossier_card_min'          => 300,
	'lunara_oscars_profile_media_width'       => 400,
	'lunara_oscars_profile_media_height'      => 600,
	'lunara_oscars_related_reviews_count'     => 4,
);

$GLOBALS['lunara_ledger_test_state']['theme_mods'] = $saved_mods;
$GLOBALS['lunara_ledger_test_state']['can_edit']   = false;
$_SERVER['REQUEST_URI']                            = '/oscars/ceremony/96/';
unset( $_GET['lunara-oscars-preset'] );

$priority6_css = lunara_oscars_ledger_variable_css( lunara_get_oscars_dossier_saved_control_values() );
ob_start();
lunara_output_oscars_dossier_studio_css();
$priority1002_css = ob_get_clean();

$vars_map = lunara_test_extract_shell_declarations( $priority6_css );
$em_map   = lunara_test_extract_shell_declarations( $priority1002_css );
$vars_serialized = lunara_test_serialize_declarations( $vars_map );
$em_serialized   = lunara_test_serialize_declarations( $em_map );

$record_check( 'anonymous-vars-and-1002-value-identical', '' !== $vars_serialized && $vars_serialized === $em_serialized, array( 'priority6' => $vars_serialized, 'priority1002' => $em_serialized ) );
$record_check( 'sixteen-shared-custom-properties', 16 === count( $vars_map ) && 16 === count( $em_map ) );
$record_check( 'saved-values-reach-both-blocks', '64px' === ( $vars_map['--lunara-oscars-dossier-section-gap'] ?? null ) && '.88' === ( $em_map['--lunara-oscars-dossier-density-scale'] ?? null ) );

// An admin preset preview shifts ONLY the preview-aware 1002 emitter; the
// cacheable priority-6 block stays saved-only for every visitor.
$GLOBALS['lunara_ledger_test_state']['can_edit'] = true;
$_GET['lunara-oscars-preset']                    = 'showcase-test';
$priority6_previewed = lunara_oscars_ledger_variable_css( lunara_get_oscars_dossier_saved_control_values() );
ob_start();
lunara_output_oscars_dossier_studio_css();
$priority1002_previewed = ob_get_clean();
$em_preview_map = lunara_test_extract_shell_declarations( $priority1002_previewed );
$record_check( 'priority6-stays-saved-only-under-preview', $priority6_previewed === $priority6_css );
$record_check( 'priority1002-adopts-admin-preview', '96px' === ( $em_preview_map['--lunara-oscars-dossier-section-gap'] ?? null ) && '1.12' === ( $em_preview_map['--lunara-oscars-dossier-density-scale'] ?? null ) );
unset( $_GET['lunara-oscars-preset'] );
$GLOBALS['lunara_ledger_test_state']['can_edit'] = false;

// ---------------------------------------------------------------------------
// 3. Budgets, executed from the real builders.
// ---------------------------------------------------------------------------
$seed_css   = lunara_oscars_ledger_critical_css();
$seed_bytes = strlen( $seed_css );
$vars_bytes = strlen( $priority6_css );
$record_check( 'seed-nonempty-within-6144', $seed_bytes > 0 && $seed_bytes <= 6144, $seed_bytes );
$record_check( 'seed-consumes-priority6-variables', false !== strpos( $seed_css, 'var(--lunara-oscars-dossier-density-scale,1)' ) && false !== strpos( $seed_css, 'var(--lunara-oscars-dossier-card-min,240px)' ) );
$record_check( 'seed-carries-no-important', false === strpos( $seed_css, '!important' ) );
$report['seed_bytes'] = $seed_bytes;
$report['vars_bytes'] = $vars_bytes;

// ---------------------------------------------------------------------------
// 4. Identity seam: registered on the plugin composer hooks and pure.
// ---------------------------------------------------------------------------
$hub_registered    = array_column( $GLOBALS['lunara_test_filters']['aat_hub_route_sections'] ?? array(), 'callback' );
$entity_registered = array_column( $GLOBALS['lunara_test_filters']['aat_entity_route_sections'] ?? array(), 'callback' );
$record_check( 'hub-seam-registered', in_array( 'lunara_oscars_compose_hub_route_sections', $hub_registered, true ) );
$record_check( 'entity-seam-registered', in_array( 'lunara_oscars_compose_entity_route_sections', $entity_registered, true ) );

$sections = array(
	'dossier-hero'           => '<div>H</div>',
	'future-unknown-section' => '<div>F</div>',
	'table-shell'            => '<div>T</div>',
);
$ctx = array( 'kind' => 'ceremony', 'id' => 96 );
ob_start();
$hub_result    = apply_filters( 'aat_hub_route_sections', $sections, $ctx );
$entity_result = apply_filters( 'aat_entity_route_sections', $sections, array( 'kind' => 'title', 'id' => 'tt0110912' ) );
$seam_output   = ob_get_clean();
$record_check( 'hub-seam-identity', $hub_result === $sections );
$record_check( 'entity-seam-identity', $entity_result === $sections );
$record_check( 'seam-emits-no-output', '' === $seam_output );
$record_check( 'hub-seam-normalizes-malformed', array() === apply_filters( 'aat_hub_route_sections', 'not-an-array', $ctx ) );
$record_check( 'entity-seam-normalizes-malformed', array() === apply_filters( 'aat_entity_route_sections', null, $ctx ) );

echo json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

foreach ( $report['cases'] as $case ) {
	if ( ! $case['passed'] ) {
		exit( 1 );
	}
}
foreach ( $report['checks'] as $check ) {
	if ( ! $check['passed'] ) {
		exit( 1 );
	}
}
