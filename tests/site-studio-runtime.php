<?php
/**
 * Behavioral contract for the focused Lunara Site Studio router.
 *
 * @package Lunara_Film
 */

define( 'ABSPATH', __DIR__ . '/' );

$lunara_test_actions  = array();
$lunara_test_submenus = array();
$lunara_test_can_edit = true;
$lunara_test_filters  = array();

class WP_Error {
	private $code;
	public function __construct( $code = '' ) { $this->code = $code; }
	public function get_error_code() { return $this->code; }
}

function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

function lunara_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function add_action( $hook, $callback, $priority = 10 ) {
	global $lunara_test_actions;
	$lunara_test_actions[] = compact( 'hook', 'callback', 'priority' );
}

function add_filter( $hook, $callback, $priority = 10 ) {
	global $lunara_test_filters;
	$lunara_test_filters[ $hook ][] = compact( 'callback', 'priority' );
}

function apply_filters( $hook, $value ) {
	global $lunara_test_filters;
	foreach ( isset( $lunara_test_filters[ $hook ] ) ? $lunara_test_filters[ $hook ] : array() as $filter ) {
		$value = call_user_func( $filter['callback'], $value );
	}
	return $value;
}

function add_submenu_page( $parent, $page_title, $menu_title, $capability, $slug, $callback, $position = null ) {
	global $lunara_test_submenus;
	$lunara_test_submenus[] = compact( 'parent', 'page_title', 'menu_title', 'capability', 'slug', 'callback', 'position' );
	return 'lunara_page_' . $slug;
}

function current_user_can( $capability ) {
	global $lunara_test_can_edit;
	return $lunara_test_can_edit && 'edit_theme_options' === $capability;
}

function wp_die( $message ) {
	throw new RuntimeException( (string) $message );
}

function __( $text ) {
	return $text;
}

function esc_html__( $text ) {
	return $text;
}

function esc_html_e( $text ) {
	echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_url( $url ) {
	return (string) $url;
}

function admin_url( $path = '' ) {
	return 'https://example.test/wp-admin/' . ltrim( (string) $path, '/' );
}

function home_url( $path = '' ) {
	return 'https://example.test/' . ltrim( (string) $path, '/' );
}

function add_query_arg( $args, $url ) {
	return $url . '?' . http_build_query( $args );
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( is_scalar( $value ) ? (string) $value : '' ) );
}

function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}

function __return_true() {
	return true;
}

function wp_unslash( $value ) {
	return $value;
}

function lunara_control_desk_render_notice() {
	echo '<div data-test="notice"></div>';
}

function lunara_control_desk_render_pairing_desk_form( $context = 'control-desk' ) {
	echo '<div data-test="lunara-method" data-context="' . esc_attr( $context ) . '"></div>';
}

function lunara_control_desk_render_homepage_studio( $context = 'control-desk' ) {
	echo '<div data-test="homepage-structure" data-context="' . esc_attr( $context ) . '"></div>';
}

function lunara_control_desk_render_reviews_archive_studio( $context = 'control-desk' ) {
	echo '<div data-test="reviews-archive" data-context="' . esc_attr( $context ) . '"></div>';
}

function lunara_control_desk_render_journal_archive_studio( $context = 'control-desk' ) {
	echo '<div data-test="journal-archive" data-context="' . esc_attr( $context ) . '"></div>';
}

require dirname( __DIR__ ) . '/inc/site-studio-registry.php';
require dirname( __DIR__ ) . '/inc/site-studio-adapters.php';
add_filter(
	'lunara_site_studio_surfaces',
	static function ( $surfaces ) {
		foreach ( $surfaces as &$surface ) {
			$surface['dependency_callback'] = '__return_true';
		}
		unset( $surface );
		return $surfaces;
	}
);
require dirname( __DIR__ ) . '/inc/site-studio.php';

lunara_test_assert(
	array_filter(
		$lunara_test_actions,
		static function ( $action ) {
			return 'admin_menu' === $action['hook'] && 'lunara_register_site_studio_page' === $action['callback'];
		}
	),
	'Site Studio must register itself on admin_menu.'
);

lunara_register_site_studio_page();
lunara_test_assert( 1 === count( $lunara_test_submenus ), 'Site Studio must register exactly one submenu.' );
$submenu = $lunara_test_submenus[0];
lunara_test_assert( 'lunara-control-desk' === $submenu['parent'], 'Site Studio must live under the Lunara menu.' );
lunara_test_assert( 'edit_theme_options' === $submenu['capability'], 'Site Studio must require theme-editing permission.' );
lunara_test_assert( 'lunara-site-studio' === $submenu['slug'], 'Site Studio must have a stable direct URL.' );

$expected = array(
	'lunara-method'      => 'lunara-method',
	'homepage-structure' => 'homepage-structure',
	'reviews-archive'    => 'reviews-archive',
	'journal-archive'    => 'journal-archive',
);

foreach ( $expected as $surface => $marker ) {
	$_GET['surface'] = $surface;
	ob_start();
	lunara_render_site_studio_page();
	$html = ob_get_clean();

	lunara_test_assert( false !== strpos( $html, 'data-test="' . $marker . '"' ), "{$surface} must render its selected control surface." );
	lunara_test_assert( false !== strpos( $html, 'data-context="site-studio"' ), "{$surface} must preserve the focused return context." );
	foreach ( $expected as $other_surface => $other_marker ) {
		if ( $other_surface === $surface ) {
			continue;
		}
		lunara_test_assert( false === strpos( $html, 'data-test="' . $other_marker . '"' ), "{$surface} must not render {$other_surface}." );
	}
}

unset( $_GET['surface'] );
ob_start();
lunara_render_site_studio_page();
$default_html = ob_get_clean();
lunara_test_assert( false !== strpos( $default_html, 'data-test="lunara-method"' ), 'Lunara Method must be the default first surface.' );
lunara_test_assert( false !== strpos( $default_html, 'data-context="site-studio"' ), 'The direct Method editor must reuse the shared form in Site Studio context.' );
lunara_test_assert( false !== strpos( $default_html, 'Homepage' ) && false !== strpos( $default_html, 'Archives' ), 'Site Studio must group Homepage and Archive navigation.' );

$lunara_test_can_edit = false;
try {
	lunara_render_site_studio_page();
	lunara_test_assert( false, 'Unauthorized users must not render Site Studio.' );
} catch ( RuntimeException $error ) {
	lunara_test_assert( false !== strpos( $error->getMessage(), 'permission' ), 'Unauthorized access must fail with a clear permission message.' );
}

echo "site-studio runtime: all assertions passed.\n";
