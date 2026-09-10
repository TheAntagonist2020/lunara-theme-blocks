<?php
/** Real Portal provider integration plus the active page's exact config/copy preamble. */
require __DIR__ . '/oscars-portal-studio-runtime.php';
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . $path; }
function has_action( $hook, $callback = false ) { return false; }
require dirname( __DIR__ ) . '/inc/site-studio-preview.php';
// WordPress transport doubles; the registry, provider adapter and REST controller below are real.
class WP_REST_Request {
    private $params;
    public function __construct( $params ) { $this->params = $params; }
    public function get_param( $key ) { return $this->params[ $key ] ?? null; }
    public function get_header( $key ) { return 'x-wp-nonce' === strtolower( $key ) ? 'portal-rest-nonce' : ''; }
}
class WP_REST_Response {
    private $data; private $status;
    public function __construct( $data, $status = 200 ) { $this->data = $data; $this->status = $status; }
    public function get_data() { return $this->data; }
    public function get_status() { return $this->status; }
}
function is_user_logged_in() { return get_current_user_id() > 0; }
function wp_verify_nonce( $nonce, $action ) { return 'portal-rest-nonce' === $nonce && 'wp_rest' === $action; }
require dirname( __DIR__ ) . '/inc/site-studio-rest.php';

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
// A real REST save must retain every native anchor from the canonical provider's error.
$rest_before = serialize( array( $lunara_test_options, $lunara_test_theme_mods ) );
$rest_cases = array(
    'identity' => array( 'group' => 'identity', 'field' => 'title', 'value' => array( 'invalid' ), 'code' => 'oscars_portal_identity_invalid', 'spec' => lunara_oscars_portal_studio_identity_specs() ),
    'winner-width' => array( 'group' => 'presentation', 'field' => 'winners_min_width', 'value' => 999, 'code' => 'oscars_portal_geometry_invalid', 'spec' => array_merge( lunara_oscars_portal_studio_geometry_specs(), lunara_oscars_portal_studio_rhythm_specs() ) ),
    'board-rhythm' => array( 'group' => 'presentation', 'field' => 'board_rhythm', 'value' => 'invalid', 'code' => 'oscars_portal_geometry_invalid', 'spec' => array_merge( lunara_oscars_portal_studio_geometry_specs(), lunara_oscars_portal_studio_rhythm_specs() ) ),
);
foreach ( $rest_cases as $case => $spec ) {
    $invalid = $draft; $invalid[ $spec['group'] ][ $spec['field'] ] = $spec['value'];
    $response = lunara_site_studio_rest_save( new WP_REST_Request( array( 'surface' => 'oscars-portal', 'state' => $invalid ) ) );
    $payload = $response->get_data();
    lunara_test_assert( 422 === $response->get_status() && $spec['code'] === $payload['code'], 'REST must expose the actual Portal provider validation failure: ' . $case );
    $expected = array_map( static function ( $field ) use ( $spec ) { return $spec['group'] . '.' . $field; }, array_keys( $spec['spec'] ) );
    $actual = array_keys( $payload['fields'] ); sort( $expected ); sort( $actual );
    lunara_test_assert( $expected === $actual, 'Real REST Portal ' . $case . ' error must retain every canonical native field anchor.' );
}
lunara_test_assert( $rest_before === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'Rejected REST candidates never persist Portal settings or revisions.' );
$invalid = $draft; $invalid['identity']['title'] = array( 'invalid' );
$provider_error = $adapter->validate_state( $invalid );
$fields = $provider_error->get_error_data()['fields'];
$fields['identity.title'] = '<b>Review this title.</b>';
$fields['identity.kicker'] = array( 'secret-nonscalar' );
foreach ( array( 'identity.access_token', 'identity.title.private', 'identity.unknown', 'presentation.private', 'presentation.unknown', 'raw_option_key', 'token_hash' ) as $private_path ) { $fields[ $private_path ] = 'secret-field-value'; }
$privacy_response = lunara_site_studio_rest_adapter_error_response( new WP_Error( $provider_error->get_error_code(), 'secret-provider-message', array( 'fields' => $fields, 'access_token' => 'secret-top-level' ) ) );
$privacy_payload = $privacy_response->get_data();
lunara_test_assert( 'Review this title.' === ( $privacy_payload['fields']['identity.title'] ?? '' ) && ! isset( $privacy_payload['fields']['identity.kicker'] ), 'REST preserves sanitized native messages and excludes nonscalar values.' );
lunara_test_assert( false === strpos( json_encode( $privacy_payload ), 'secret-' ) && ! array_diff( array_keys( $privacy_payload['fields'] ), array_map( static function ( $field ) { return 'identity.' . $field; }, array_keys( lunara_oscars_portal_studio_identity_specs() ) ) ), 'REST must exclude unknown and private paths, raw provider messages and unrelated metadata.' );
fwrite( STDOUT, "Portal REST validation: actual provider identity/geometry errors retain canonical fields; unknown/private paths filtered; rejected saves unchanged.\n" );
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
