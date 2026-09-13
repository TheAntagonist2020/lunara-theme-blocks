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

function untrailingslashit( $value ) { return rtrim( $value, '/\\' ); }
function remove_query_arg( $key, $url ) {
    $parts = parse_url( $url ); $query = array(); parse_str( $parts['query'] ?? '', $query ); unset( $query[$key] );
    return ( isset( $parts['host'] ) ? ( $parts['scheme'] ?? 'https' ) . '://' . $parts['host'] : '' ) . ( $parts['path'] ?? '' ) . ( $query ? '?' . http_build_query( $query ) : '' ) . ( isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '' );
}
/** Evaluate actual live readers and markup, with only their route data supplied by the harness. */
function lunara_test_portal_navigation_markup( $source ) {
    $start = strpos( $source, '$oscars_studio_config     =' ); $end = strpos( $source, '$rotating_', $start );
    eval( substr( $source, $start, $end - $start ) );
    $ceremony_url = 'https://example.test/oscars/ceremony/98/'; $ceremonies_url = 'https://example.test/oscars/ceremonies/';
    $categories_url = 'https://example.test/oscars/categories/'; $database_url = 'https://example.test/oscars/ledger/';
    $database_table_url = $database_url . '?view=table'; $about_url = 'https://example.test/about/';
    $portal_backdrops = array( 'Ceremonies' => 'https://example.test/ceremony.jpg', 'Categories' => 'https://example.test/category.jpg', 'Ledger' => 'https://example.test/ledger.jpg', 'About' => 'https://example.test/method.jpg' );
    $start = strpos( $source, '$portal_link_defaults =' ); $end = strpos( $source, '$research_cards =', $start );
    lunara_test_assert( false !== $start && false !== $end, 'Actual Quick Start reader block must be available.' );
    eval( substr( $source, $start, $end - $start ) );
    preg_match( '/<div class="lunara-oscars-portal-actions">.*?<\/div>/s', $source, $actions );
    lunara_test_assert( ! empty( $actions[0] ), 'Actual Hero button markup must be available.' );
    ob_start(); eval( '?>' . $actions[0] ); $buttons_html = ob_get_clean();
    $start = strpos( $source, '<?php if ( $show_portal_links', strpos( $source, "\$oscars_slot_markup['board']" ) );
    $end = strpos( $source, "<?php \$oscars_slot_markup['doors']", $start );
    lunara_test_assert( false !== $start && false !== $end, 'Actual Quick Start card markup must be available.' );
    $show_portal_links = $oscars_portal_show( 'doors', 'lunara_oscars_show_portal_links', true );
    ob_start(); eval( '?>' . substr( $source, $start, $end - $start ) ); $cards_html = ob_get_clean();
    return compact( 'buttons_html', 'cards_html', 'portal_links' );
}

$pilot = lunara_site_studio_preview_pilots()['oscars-portal'];
lunara_test_assert( 'provider' === $pilot['storage'] && 'lunara_oscars_portal_studio_get_preview_config' === $pilot['preview_callback'] && 'lunara_oscars_preview' === $pilot['query'] && '/oscars/' === $pilot['route'], 'Portal private previews must use their canonical owner-bound provider consumer.' );
$initial = lunara_oscars_portal_studio_defaults();
lunara_oscars_portal_studio_promote_config_transaction( $initial );
$adapter = lunara_site_studio_oscars_portal_adapter();
$draft = $adapter->read_state(); $draft['identity']['title'] = 'Private Portal headline'; $draft['section_order'] = array_reverse( $draft['section_order'] ); $draft['section_visibility']['doors'] = false; $draft['section_visibility']['navigator'] = false; $draft['presentation']['hero_min_height'] = 411;
foreach ( lunara_oscars_portal_studio_button_specs() as $key => $spec ) { $draft['buttons'][$key] = 'Private ' . $key; }
foreach ( lunara_oscars_portal_studio_quick_start_specs() as $key => $spec ) {
    $draft['quick_start'][$key] = array( 'enabled' => 'categories' !== $key, 'kicker' => 'Private kicker ' . $key, 'title' => 'Private title ' . $key, 'copy' => "First line\nSecond " . $key, 'url' => '/private/' . $key . '/' );
}
$draft['quick_start']['ceremonies']['url'] = '';
$draft['quick_start']['ledger']['url'] = 'https://example.test/oscars/ledger/?view=cards';
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
$navigation_output = lunara_test_portal_navigation_markup( $source );
lunara_test_assert( false !== strpos( $navigation_output['buttons_html'], '>Private ceremony</a>' ) && false !== strpos( $navigation_output['buttons_html'], '>Private ledger</a>' ) && false !== strpos( $navigation_output['buttons_html'], '>Private categories</a>' ), 'Actual live Hero markup must use all three private button labels.' );
lunara_test_assert( '' === trim( $navigation_output['cards_html'] ), 'The real Quick Start section remains absent when its parent section is hidden.' );
$navigation_draft = $draft; $navigation_draft['section_visibility']['doors'] = true;
$navigation_preview = $adapter->create_preview( $navigation_draft ); $_GET['lunara_oscars_preview'] = $navigation_preview['token'];
$navigation_output = lunara_test_portal_navigation_markup( $source );
lunara_test_assert( 3 === count( $navigation_output['portal_links'] ) && false !== strpos( $navigation_output['cards_html'], 'Private title method' ) && false !== strpos( $navigation_output['cards_html'], 'First line' ) && false === strpos( $navigation_output['cards_html'], 'Private title categories' ), 'Actual Quick Start markup must use private card titles/copy and skip only the disabled card.' );
lunara_test_assert( array( 'https://example.test/oscars/ceremonies/', 'https://example.test/oscars/ledger/?view=table', '/private/method/' ) === array_column( $navigation_output['portal_links'], 'url' ), 'Actual Quick Start reader retains dynamic empty destinations, ledger table normalization and root-relative custom links.' );
lunara_test_assert( array( 'https://example.test/ceremony.jpg', 'https://example.test/ledger.jpg', 'https://example.test/method.jpg' ) === array_column( $navigation_output['portal_links'], 'backdrop' ), 'Private card editing preserves canonical slot artwork and fixed order.' );
foreach ( $navigation_draft['quick_start'] as &$card ) { $card['enabled'] = false; } unset( $card );
$empty_preview = $adapter->create_preview( $navigation_draft ); $_GET['lunara_oscars_preview'] = $empty_preview['token'];
$empty_output = lunara_test_portal_navigation_markup( $source );
lunara_test_assert( array() === $empty_output['portal_links'] && '' === trim( $empty_output['cards_html'] ), 'All four cards disabled must leave no empty Quick Start shell.' );
$_GET['lunara_oscars_preview'] = $token;
$lunara_test_user_id = 91;
lunara_test_assert( false === call_user_func( $pilot['preview_callback'], $token ), 'A different user cannot consume the Portal pilot.' );
$lunara_test_user_id = 0; $lunara_test_can_edit = false;
lunara_test_assert( false === call_user_func( $pilot['preview_callback'], $token ), 'Anonymous requests cannot consume the Portal pilot.' );
unset( $_GET['lunara_oscars_preview'] ); $lunara_test_user_id = 7; $lunara_test_can_edit = true;
$public_navigation_output = lunara_test_portal_navigation_markup( $source );
lunara_test_assert( false === strpos( $public_navigation_output['buttons_html'] . $public_navigation_output['cards_html'], 'Private' ) && 4 === count( $public_navigation_output['portal_links'] ), 'Public navigation retains its current labels and all default cards after private previews.' );
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
foreach ( array( 'buttons.ceremony', 'quick_start.method.enabled', 'quick_start.method.url' ) as $path ) {
    $invalid = $draft; $node =& $invalid;
    foreach ( explode( '.', $path ) as $part ) { $node =& $node[$part]; }
    $node = 'quick_start.method.url' === $path ? 'javascript:alert(1)' : array( 'invalid' ); unset( $node );
    foreach ( array( 'lunara_site_studio_rest_save', 'lunara_site_studio_rest_preview' ) as $operation ) {
        $response = $operation( new WP_REST_Request( array( 'surface' => 'oscars-portal', 'state' => $invalid ) ) );
        lunara_test_assert( 422 === $response->get_status() && array( $path ) === array_keys( $response->get_data()['fields'] ), 'Shared Preview/Apply must preserve exact navigation error anchors: ' . $path );
    }
}
lunara_test_assert( $rest_before === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'Rejected shared navigation edits write no canonical settings or revisions.' );
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
$navigation_settings = array_column( lunara_oscars_portal_studio_button_specs(), 'setting' );
foreach ( lunara_oscars_portal_studio_quick_start_specs() as $spec ) { foreach ( array( 'enabled', 'kicker', 'title', 'copy', 'url' ) as $field ) { $navigation_settings[] = 'lunara_oscars_portal_card_' . $spec['slot'] . '_' . $field; } }
$manager = new class {
    public $removed = array(); public $removed_settings = array(); public $registered = array();
    public function remove_control( $key ) { $this->removed[] = $key; }
    public function remove_setting( $key ) { $this->removed_settings[] = $key; unset( $this->registered[$key] ); }
    public function get_section( $key ) { return false; }
    public function stale_save( $values ) { foreach ( $values as $key => $value ) { if ( isset( $this->registered[$key] ) ) { set_theme_mod( $key, $value ); } } }
};
$manager->registered = array_fill_keys( array_merge( $navigation_settings, array( 'lunara_oscars_rotating_winners_count' ) ), true );
$before_retirement = $lunara_test_theme_mods;
lunara_customize_retire_portal_studio_controls( $manager );
lunara_test_assert( 23 === count( $navigation_settings ) && ! array_diff( $navigation_settings, $manager->removed ) && ! array_diff( $navigation_settings, $manager->removed_settings ) && $before_retirement === $lunara_test_theme_mods, 'All 23 migrated Customizer controls and registrations retire without touching their saved canonical mods.' );
$manager->stale_save( array_fill_keys( $navigation_settings, 'Stale Customizer value' ) );
lunara_test_assert( $before_retirement === $lunara_test_theme_mods && isset( $manager->registered['lunara_oscars_rotating_winners_count'] ), 'A stale Customizer submission cannot overwrite migrated navigation, while supplemental settings stay registered.' );
foreach ( lunara_oscars_portal_studio_identity_specs() as $spec ) { lunara_test_assert( in_array( $spec['setting'], $manager->removed, true ), 'Covered copy writer is retired.' ); }
foreach ( lunara_oscars_portal_studio_visibility_owners() as $owner ) { if ( $owner['setting'] ) { lunara_test_assert( in_array( $owner['setting'], $manager->removed, true ), 'Covered visibility writer is retired.' ); } }
lunara_test_assert( in_array( 'lunara_oscars_portal_section_order', $manager->removed, true ) && ! in_array( 'lunara_oscars_rotating_winners_count', $manager->removed, true ) && ! in_array( 'lunara_oscars_portal_hero_primary_label', $manager->removed, true ), 'No-op legacy order is retired while supplemental Classic controls remain.' );
fwrite( STDOUT, "site-studio Oscars runtime: provider isolation, active renderer, errors, failed save, exact Apply/reload/Restore and legacy redirect passed.\n" );
