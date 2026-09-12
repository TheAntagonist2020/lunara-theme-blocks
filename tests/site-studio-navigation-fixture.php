<?php
/** Render the real page navigation with all four homepage editor registrations. */
define( 'LUNARA_SITE_STUDIO_RUNTIME_BOOTSTRAP_ONLY', true );
require __DIR__ . '/site-studio-runtime.php';
require dirname( __DIR__ ) . '/inc/site-studio-preview.php';
require dirname( __DIR__ ) . '/inc/site-studio-carousels.php';
require dirname( __DIR__ ) . '/inc/site-studio-home-oscars.php';

$navigation_args = array();
foreach ( $argv as $argument ) {
	if ( preg_match( '/^--(surface|deny|unavailable|unsafe)=(.*)$/D', $argument, $match ) ) {
		$navigation_args[ $match[1] ] = sanitize_key( $match[2] );
	}
}
function lunara_navigation_fixture_unavailable() { return false; }
add_filter( 'lunara_site_studio_surfaces', static function ( $surfaces ) use ( $navigation_args ) {
	foreach ( $surfaces as $id => &$surface ) {
		if ( isset( $navigation_args['deny'] ) && $navigation_args['deny'] === $id ) { $surface['capability'] = 'manage_options'; }
		if ( isset( $navigation_args['unavailable'] ) && $navigation_args['unavailable'] === $id ) { $surface['dependency_callback'] = 'lunara_navigation_fixture_unavailable'; }
		if ( isset( $navigation_args['unsafe'] ) && $navigation_args['unsafe'] === $id ) { $surface['admin_url'] = 'https://unsafe.test/wp-admin/admin.php'; }
	}
	unset( $surface );
	return $surfaces;
}, 99 );
$_GET['surface'] = isset( $navigation_args['surface'] ) ? $navigation_args['surface'] : 'homepage-structure';
lunara_enqueue_site_studio_assets( 'lunara_page_lunara-site-studio' );
ob_start();
lunara_render_site_studio_page();
$workspace = ob_get_clean();
$config = isset( $lunara_test_localized['LunaraSiteStudioWorkspaceConfig'] ) ? $lunara_test_localized['LunaraSiteStudioWorkspaceConfig'] : array();
echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><script>window.LunaraSiteStudioWorkspaceConfig=' . wp_json_encode( $config ) . ';</script></head><body class="wp-admin">' . $workspace . '</body></html>';
