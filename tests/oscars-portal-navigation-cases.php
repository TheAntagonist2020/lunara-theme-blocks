<?php
/** Runs inside the real Portal provider harness, including both degraded plugin modes. */
$navigation_snapshot = array( $lunara_test_options, $lunara_test_theme_mods, $lunara_test_transients, $lunara_test_actions_fired );
$lunara_test_options = array(); $lunara_test_theme_mods = array();
$navigation_defaults = lunara_oscars_portal_studio_defaults();
lunara_test_assert( $navigation_defaults === lunara_oscars_portal_studio_validate_config( $navigation_defaults ), 'Validation must retain both complete navigation families.' );
lunara_test_assert( array( 'ceremony', 'ledger', 'categories' ) === array_keys( $oscars_projection_schema['buttons'] ) && array( 'ceremonies', 'categories', 'ledger', 'method' ) === array_keys( $oscars_projection_schema['quick_start'] ), 'Navigation schema must use three buttons and four stable named cards.' );
foreach ( $oscars_projection_schema['quick_start'] as $card_schema ) {
    lunara_test_assert( array( 'enabled', 'kicker', 'title', 'copy', 'url' ) === array_keys( $card_schema ), 'Each Quick Start card exposes exactly its five canonical controls.' );
}
$legacy_title = str_repeat( 'Legacy title ', 30 );
$lunara_test_theme_mods = array(
    'lunara_oscars_ceremony_btn' => '', 'lunara_oscars_ledger_btn' => '  Untrimmed legacy button  ',
    'lunara_oscars_portal_card_1_title' => $legacy_title,
    'lunara_oscars_portal_card_2_kicker' => ' ', 'lunara_oscars_portal_card_2_copy' => "  Legacy\ncopy  ",
    'lunara_oscars_portal_card_3_enabled' => '0', 'lunara_oscars_portal_card_4_url' => '/about/#ledger',
);
$before_navigation_reads = serialize( array( $lunara_test_options, $lunara_test_theme_mods ) );
$legacy_navigation = lunara_oscars_portal_studio_get_public_config( false );
lunara_test_assert( '' === $legacy_navigation['buttons']['ceremony'] && '  Untrimmed legacy button  ' === $legacy_navigation['buttons']['ledger'], 'Reading must preserve existing explicitly empty and untrimmed button labels.' );
lunara_test_assert( trim( $legacy_title ) === $legacy_navigation['quick_start']['ceremonies']['title'] && 'Categories' === $legacy_navigation['quick_start']['categories']['kicker'] && "Legacy\ncopy" === $legacy_navigation['quick_start']['categories']['copy'] && false === $legacy_navigation['quick_start']['ledger']['enabled'], 'Legacy Quick Start reads preserve long copy, trim text, inherit empty titles and retain the existing boolean semantics.' );
lunara_test_assert( $before_navigation_reads === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'Opening migrated navigation must perform zero public writes.' );
$classic_candidate = lunara_oscars_portal_studio_config_from_request( array( 'lunara_oscars_portal_identity' => array( 'title' => 'Classic copy edit' ) ) );
lunara_test_assert( $legacy_navigation['buttons'] === $classic_candidate['buttons'] && $legacy_navigation['quick_start'] === $classic_candidate['quick_start'] && $before_navigation_reads === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'An unrelated Classic form request preserves fresh canonical navigation without requiring hidden copies.' );
$navigation_candidate = $navigation_defaults;
foreach ( lunara_oscars_portal_studio_button_specs() as $key => $spec ) { $navigation_candidate['buttons'][$key] = 'Selected ' . $key; }
foreach ( lunara_oscars_portal_studio_quick_start_specs() as $key => $spec ) {
    $navigation_candidate['quick_start'][$key] = array( 'enabled' => 'categories' !== $key, 'kicker' => 'Kicker ' . $key, 'title' => 'Title ' . $key, 'copy' => "First line\nSecond " . $key, 'url' => '/chosen/' . $key . '/?view=table#open' );
}
$navigation_candidate['quick_start']['ledger']['url'] = 'http://example.test/ledger/';
$navigation_candidate['quick_start']['method']['url'] = 'https://example.test/method/';
$validated_navigation = lunara_oscars_portal_studio_validate_config( $navigation_candidate );
lunara_test_assert( $navigation_candidate === $validated_navigation, 'Validation preserves all 23 legitimate navigation choices, including multiline copy and relative/HTTP(S) destinations.' );
$navigation_token = lunara_oscars_portal_studio_store_preview( $navigation_candidate );
lunara_test_assert( $navigation_candidate === lunara_oscars_portal_studio_get_preview_config( $navigation_token ) && $before_navigation_reads === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'Private navigation Preview returns the complete candidate and never modifies canonical settings.' );
// A token minted by the previous release never owned the newly exposed controls.
$old_navigation_token_key = 'lunara_oscars_portal_preview_' . hash( 'sha256', $navigation_token );
unset( $lunara_test_transients[$old_navigation_token_key]['value']['config']['buttons'], $lunara_test_transients[$old_navigation_token_key]['value']['config']['quick_start'] );
$old_navigation_preview = lunara_oscars_portal_studio_get_preview_config( $navigation_token );
lunara_test_assert( $legacy_navigation['buttons'] === $old_navigation_preview['buttons'] && $legacy_navigation['quick_start'] === $old_navigation_preview['quick_start'] && $before_navigation_reads === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'A pre-extension preview retains current navigation instead of substituting new defaults or writing settings.' );
$invalid_navigation_cases = array(
    array( 'buttons', 'broken' ), array( 'buttons.ceremony', array( 'nested' ) ),
    array( 'quick_start', array() ), array( 'quick_start.method', array( 'extra' => true ) ),
    array( 'quick_start.method.enabled', 'false' ), array( 'quick_start.method.title', 5 ),
    array( 'quick_start.method.url', 'javascript:alert(1)' ), array( 'quick_start.method.url', '//elsewhere.test/' ),
    array( 'quick_start.method.url', '/\\elsewhere.test/' ), array( 'quick_start.method.url', "https://example.test/\nprivate" ),
    array( 'quick_start.method.url', 'https://name:password@example.test/' ), array( 'quick_start.method.url', str_repeat( 'x', 2049 ) ),
);
foreach ( $invalid_navigation_cases as $case ) {
    $invalid_navigation = $navigation_candidate; $node =& $invalid_navigation;
    foreach ( explode( '.', $case[0] ) as $part ) { $node =& $node[$part]; }
    $node = $case[1]; unset( $node );
    $before = serialize( array( $lunara_test_options, $lunara_test_theme_mods, $lunara_test_transients ) );
    foreach ( array( 'lunara_oscars_portal_studio_store_preview', 'lunara_oscars_portal_studio_promote_config_transaction' ) as $operation ) {
        $error = $operation( $invalid_navigation );
        lunara_test_assert( is_wp_error( $error ) && isset( $error->get_error_data()['fields'][$case[0]] ), 'Malformed navigation must fail with its exact editable field: ' . $case[0] );
    }
    lunara_test_assert( $before === serialize( array( $lunara_test_options, $lunara_test_theme_mods, $lunara_test_transients ) ), 'Rejected navigation must write no preview, revision or public setting: ' . $case[0] );
}
$bounded_navigation = $navigation_candidate;
$bounded_navigation['buttons']['ceremony'] = str_repeat( 'a', 150 );
$bounded_navigation['buttons']['ledger'] = '';
$bounded_navigation['quick_start']['method'] = array( 'enabled' => false, 'kicker' => '', 'title' => '', 'copy' => '<b>' . str_repeat( 'c', 700 ) . '</b>', 'url' => '' );
$bounded_navigation = lunara_oscars_portal_studio_validate_config( $bounded_navigation );
lunara_test_assert( 120 === strlen( $bounded_navigation['buttons']['ceremony'] ) && 'Open Full Ledger' === $bounded_navigation['buttons']['ledger'] && 'About' === $bounded_navigation['quick_start']['method']['kicker'] && 'Ledger Method' === $bounded_navigation['quick_start']['method']['title'] && 600 === strlen( $bounded_navigation['quick_start']['method']['copy'] ) && '' === $bounded_navigation['quick_start']['method']['url'], 'New navigation input uses bounded sanitized copy and inherits safe visible button/card labels and card destinations.' );
$lunara_test_theme_mods['lunara_oscars_rotating_winners_count'] = 13;
$saved_navigation = lunara_oscars_portal_studio_promote_config_transaction( $navigation_candidate );
lunara_test_assert( ! is_wp_error( $saved_navigation ) && $navigation_candidate === lunara_oscars_portal_studio_get_public_config( false ), 'Apply must reload every navigation choice exactly, including hidden card copy.' );
foreach ( lunara_oscars_portal_studio_button_specs() as $key => $spec ) { lunara_test_assert( $navigation_candidate['buttons'][$key] === $lunara_test_theme_mods[$spec['setting']], 'Button applies to its existing canonical theme mod: ' . $key ); }
foreach ( lunara_oscars_portal_studio_quick_start_specs() as $key => $spec ) {
    foreach ( $navigation_candidate['quick_start'][$key] as $field => $value ) { lunara_test_assert( $value === $lunara_test_theme_mods['lunara_oscars_portal_card_' . $spec['slot'] . '_' . $field], 'Quick Start applies to its existing canonical theme mod: ' . $key . '.' . $field ); }
}
lunara_test_assert( array( 'schema_version', 'section_order', 'presentation' ) === array_keys( $lunara_test_options[LUNARA_OSCARS_PORTAL_STUDIO_OPTION] ) && 13 === $lunara_test_theme_mods['lunara_oscars_rotating_winners_count'], 'Navigation never creates a parallel option owner or changes supplemental winner controls.' );
$next_navigation = $navigation_candidate; $next_navigation['buttons']['ceremony'] = 'Next button'; $next_navigation['quick_start']['method']['title'] = 'Next method';
$next_navigation_result = lunara_oscars_portal_studio_promote_config_transaction( $next_navigation );
$restored_navigation = lunara_oscars_portal_studio_restore_revision_transaction( $next_navigation_result['revision_id'] );
lunara_test_assert( ! is_wp_error( $restored_navigation ) && $navigation_candidate === lunara_oscars_portal_studio_get_public_config( false ), 'New revisions restore both navigation families through their canonical owners.' );
// Old revisions have no navigation keys: preserve current mod values AND absence.
$old_revision = $navigation_defaults; unset( $old_revision['buttons'], $old_revision['quick_start'] );
$old_revision_id = lunara_oscars_portal_studio_push_revision( $old_revision, 'save' );
unset( $lunara_test_theme_mods['lunara_oscars_portal_card_4_copy'] );
$lunara_test_theme_mods['lunara_oscars_portal_card_1_title'] = $legacy_title;
$lunara_test_theme_mods['lunara_oscars_portal_card_3_enabled'] = '0';
$before_old_restore_mods = $lunara_test_theme_mods;
$old_restore = lunara_oscars_portal_studio_restore_revision_transaction( $old_revision_id );
lunara_test_assert( ! is_wp_error( $old_restore ) && $before_old_restore_mods === $lunara_test_theme_mods, 'Restoring a pre-extension revision must preserve current navigation raw values, types and missing mods.' );
// Structurally corrupt history fails closed rather than causing a PHP type error.
$lunara_test_options[LUNARA_OSCARS_PORTAL_STUDIO_REVISIONS_OPTION][0]['config'] = 'broken';
$malformed_id = $lunara_test_options[LUNARA_OSCARS_PORTAL_STUDIO_REVISIONS_OPTION][0]['id'];
$before = serialize( array( $lunara_test_options, $lunara_test_theme_mods ) );
lunara_test_assert( is_wp_error( lunara_oscars_portal_studio_restore_revision_transaction( $malformed_id ) ) && $before === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'Malformed historical configuration must fail without changing current navigation or revisions.' );
list( $lunara_test_options, $lunara_test_theme_mods, $lunara_test_transients, $lunara_test_actions_fired ) = $navigation_snapshot;
fwrite( STDOUT, "Portal navigation: all 23 controls, strict input, pure reads, private tokens, canonical Apply and old/new history passed.\n" );
