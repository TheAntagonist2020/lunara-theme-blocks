<?php
/** Real Portal provider integration plus the active page's exact config/copy preamble. */
require __DIR__ . '/oscars-portal-studio-runtime.php';
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . $path; }
function has_action( $hook, $callback = false ) { return false; }
require dirname( __DIR__ ) . '/inc/site-studio-preview.php';
$pilot = lunara_site_studio_preview_pilots()['oscars-portal'];
lunara_test_assert( 'provider' === $pilot['storage'] && 'lunara_oscars_portal_studio_get_preview_config' === $pilot['preview_callback'] && 'lunara_oscars_preview' === $pilot['query'] && '/oscars/' === $pilot['route'], 'Portal private previews must use their canonical owner-bound provider consumer.' );
$initial = lunara_oscars_portal_studio_defaults();
lunara_oscars_portal_studio_promote_config_transaction( $initial );
$adapter = lunara_site_studio_oscars_portal_adapter();
$draft = $adapter->read_state(); $draft['identity']['title'] = 'Private Portal headline'; $draft['section_order'] = array_reverse( $draft['section_order'] ); $draft['section_visibility']['doors'] = false; $draft['section_visibility']['navigator'] = false; $draft['presentation']['hero_min_height'] = 411;
$before = serialize( array( $lunara_test_options, $lunara_test_theme_mods ) );
$preview = $adapter->create_preview( $draft );
lunara_test_assert( ! is_wp_error( $preview ) && isset( $preview['token'] ), 'The shared adapter stages a real Portal private preview.' );
lunara_test_assert( $before === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'Creating and reading private previews must never persist Portal options or mods.' );
$token = $preview['token']; $_GET['lunara_oscars_preview'] = $token;
$accepted = call_user_func( $pilot['preview_callback'], $token );
lunara_test_assert( $accepted['identity']['title'] === $draft['identity']['title'], 'Pilot invokes the real provider token consumer.' );
$source = file_get_contents( dirname( __DIR__ ) . '/page-oscars.php' );
$start = strpos( $source, '$oscars_studio_config     =' ); // Resolve the real public renderer's identity/order/geometry through its own code.
$end = strpos( $source, '$rotating_', $start );
lunara_test_assert( false !== $start && false !== $end, 'Active Portal preamble is available.' );
eval( substr( $source, $start, $end - $start ) );
lunara_test_assert( $hero_title === $draft['identity']['title'] && $oscars_portal_order === $draft['section_order'] && false === $oscars_portal_visibility['navigator'] && false !== strpos( $oscars_portal_geometry_style, '411px' ), 'Private values reach active page-oscars copy, order, visibility and presentation.' );
$lunara_test_user_id = 91;
lunara_test_assert( false === call_user_func( $pilot['preview_callback'], $token ), 'A different user cannot consume the Portal pilot.' );
$lunara_test_user_id = 0; $lunara_test_can_edit = false;
lunara_test_assert( false === call_user_func( $pilot['preview_callback'], $token ), 'Anonymous requests cannot consume the Portal pilot.' );
unset( $_GET['lunara_oscars_preview'] ); $lunara_test_user_id = 7; $lunara_test_can_edit = true;
lunara_test_assert( $before === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'Public state remains byte-exact after all preview reads.' );
$invalid = $draft; $invalid['presentation']['hero_min_height'] = 999;
$error = $adapter->validate_state( $invalid );
lunara_test_assert( is_wp_error( $error ) && isset( $error->get_error_data()['fields']['presentation.hero_min_height'] ), 'Provider errors map to the native geometry control.' );
$lunara_test_revision_write_mode = 'fail'; $failed = $adapter->save_state( $draft ); $lunara_test_revision_write_mode = 'normal';
lunara_test_assert( is_wp_error( $failed ) && $before === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'Failed safety revision leaves all public state unchanged.' );
$saved = $adapter->save_state( $draft );
lunara_test_assert( ! is_wp_error( $saved ) && $adapter->read_state() === $draft, 'Apply and reload preserve the exact Portal candidate.' );
$restored = $adapter->restore_revision( $saved['revision_id'] );
lunara_test_assert( ! is_wp_error( $restored ) && $adapter->read_state() === $initial, 'Restore returns the exact prior public Portal snapshot.' );
foreach ( $pilot['markers'] as $marker ) { lunara_test_assert( false !== strpos( $source, 'data-lunara-site-studio-section="' . $marker . '"' ), 'Active Portal exposes marker ' . $marker ); }
ob_start(); lunara_control_desk_render_oscars_portal_studio(); $legacy = ob_get_clean();
lunara_test_assert( false === strpos( $legacy, '<form' ) && false !== strpos( $legacy, 'surface=oscars-portal' ), 'Covered legacy Portal form redirects to the shared editor.' );
$customizer = file_get_contents( dirname( __DIR__ ) . '/inc/customizer.php' );
preg_match( '/^function lunara_customize_retire_portal_studio_controls\(.*?^\}/ms', $customizer, $callback ); eval( $callback[0] );
$manager = new class { public $removed = array(); public function remove_control( $key ) { $this->removed[] = $key; } public function remove_setting( $key ) { $this->removed[] = $key; } public function get_section( $key ) { return false; } };
lunara_customize_retire_portal_studio_controls( $manager );
foreach ( lunara_oscars_portal_studio_identity_specs() as $spec ) { lunara_test_assert( in_array( $spec['setting'], $manager->removed, true ), 'Covered copy writer is retired.' ); }
foreach ( lunara_oscars_portal_studio_visibility_owners() as $owner ) { if ( $owner['setting'] ) { lunara_test_assert( in_array( $owner['setting'], $manager->removed, true ), 'Covered visibility writer is retired.' ); } }
lunara_test_assert( in_array( 'lunara_oscars_portal_section_order', $manager->removed, true ) && ! in_array( 'lunara_oscars_rotating_winners_count', $manager->removed, true ) && ! in_array( 'lunara_oscars_portal_hero_primary_label', $manager->removed, true ), 'No-op legacy order is retired while supplemental Classic controls remain.' );
fwrite( STDOUT, "site-studio Oscars runtime: provider isolation, active renderer, errors, failed save, exact Apply/reload/Restore and legacy redirect passed.\n" );
