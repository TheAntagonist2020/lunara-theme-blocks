<?php
/** Real winner settings coverage, run by all three existing provider modes. */
$winner_snapshot = array( $lunara_test_options, $lunara_test_theme_mods, $lunara_test_transients, $lunara_test_actions_fired );
$lunara_test_options = array(); $lunara_test_theme_mods = array();
$winner_defaults = lunara_oscars_portal_studio_defaults();
lunara_test_assert( $winner_defaults === lunara_oscars_portal_studio_validate_config( $winner_defaults ), 'Validation must retain both complete winner families.' );
lunara_test_assert( array( 'fallback_heading', 'link_label' ) === array_keys( $oscars_projection_schema['winners'] ) && array( 'kicker', 'heading', 'link_label', 'count', 'autoplay_ms' ) === array_keys( $oscars_projection_schema['rotating_winners'] ), 'Winner schema must inventory exactly the seven canonical fields.' );
lunara_test_assert( 7200 === lunara_oscars_portal_studio_get_public_config( false )['rotating_winners']['autoplay_ms'] && array() === $lunara_test_theme_mods && array() === $lunara_test_options, 'An absent autoplay mod must retain the actual live 7200ms default without saving it.' );
$winner_long_heading = str_repeat( 'Legacy winner title ', 20 );
$lunara_test_theme_mods = array(
    'lunara_oscars_latest_winners_heading' => '  ', 'lunara_oscars_latest_winners_link_label' => '  Saved ceremony link  ',
    'lunara_oscars_rotating_winners_heading' => $winner_long_heading,
    'lunara_oscars_rotating_winners_count' => '-14', 'lunara_oscars_rotating_winners_autoplay' => '7251',
);
$winner_raw_before = serialize( array( $lunara_test_options, $lunara_test_theme_mods ) );
$winner_legacy = lunara_oscars_portal_studio_get_public_config( false );
lunara_test_assert( 'Latest Ceremony Winners' === $winner_legacy['winners']['fallback_heading'] && 'Saved ceremony link' === $winner_legacy['winners']['link_label'] && trim( $winner_long_heading ) === $winner_legacy['rotating_winners']['heading'] && 14 === $winner_legacy['rotating_winners']['count'] && 7251 === $winner_legacy['rotating_winners']['autoplay_ms'], 'Pure winner reads preserve long saved copy and existing trim/default/absint numeric semantics.' );
$winner_classic = lunara_oscars_portal_studio_config_from_request( array( 'lunara_oscars_portal_identity' => array( 'title' => 'Classic edit' ) ) );
lunara_test_assert( $winner_legacy['winners'] === $winner_classic['winners'] && $winner_legacy['rotating_winners'] === $winner_classic['rotating_winners'] && $winner_raw_before === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'Unrelated legacy requests retain fresh canonical winner families without hidden form copies or writes.' );
$winner_candidate = $winner_defaults;
$winner_candidate['winners'] = array( 'fallback_heading' => 'Chosen fallback', 'link_label' => 'Chosen latest link' );
$winner_candidate['rotating_winners'] = array( 'kicker' => 'Chosen kicker', 'heading' => 'Chosen rotation', 'link_label' => 'Chosen rotation link', 'count' => 16, 'autoplay_ms' => 7251 );
$winner_token = lunara_oscars_portal_studio_store_preview( $winner_candidate );
lunara_test_assert( $winner_candidate === lunara_oscars_portal_studio_get_preview_config( $winner_token ) && $winner_raw_before === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'Private winner Preview preserves all seven choices without public writes.' );
$winner_old_token_key = 'lunara_oscars_portal_preview_' . hash( 'sha256', $winner_token );
unset( $lunara_test_transients[$winner_old_token_key]['value']['config']['winners'], $lunara_test_transients[$winner_old_token_key]['value']['config']['rotating_winners'] );
$winner_old_preview = lunara_oscars_portal_studio_get_preview_config( $winner_token );
lunara_test_assert( $winner_legacy['winners'] === $winner_old_preview['winners'] && $winner_legacy['rotating_winners'] === $winner_old_preview['rotating_winners'] && $winner_raw_before === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'Pre-extension tokens inherit current winner families and never write their defaults.' );
foreach ( array(
    array( 'winners', 'bad' ), array( 'winners', array( 'link_label' => 'Missing heading' ) ), array( 'winners.fallback_heading', array() ),
    array( 'rotating_winners', array( 'unknown' => 'bad' ) ), array( 'rotating_winners.heading', false ),
    array( 'rotating_winners.count', 3 ), array( 'rotating_winners.count', 17 ), array( 'rotating_winners.count', '10' ), array( 'rotating_winners.count', 10.5 ),
    array( 'rotating_winners.autoplay_ms', -1 ), array( 'rotating_winners.autoplay_ms', 12001 ), array( 'rotating_winners.autoplay_ms', '7200' ), array( 'rotating_winners.autoplay_ms', false ),
) as $case ) {
    $invalid_winner = $winner_candidate; $node =& $invalid_winner;
    foreach ( explode( '.', $case[0] ) as $part ) { $node =& $node[$part]; }
    $node = $case[1]; unset( $node );
    $before = serialize( array( $lunara_test_options, $lunara_test_theme_mods, $lunara_test_transients ) );
    foreach ( array( 'lunara_oscars_portal_studio_store_preview', 'lunara_oscars_portal_studio_promote_config_transaction' ) as $operation ) {
        $error = $operation( $invalid_winner );
        lunara_test_assert( is_wp_error( $error ) && 'oscars_portal_winners_invalid' === $error->get_error_code() && isset( $error->get_error_data()['fields'][$case[0]] ), 'Invalid winner input must fail at its exact field: ' . $case[0] );
    }
    lunara_test_assert( $before === serialize( array( $lunara_test_options, $lunara_test_theme_mods, $lunara_test_transients ) ), 'Invalid winner Preview/Apply must write no settings, revisions or private tokens.' );
}
foreach ( array( array( 4, 0 ), array( 16, 12000 ), array( 10, 7200 ), array( 8, 7251 ) ) as $bounds ) {
    $bounded_winner = $winner_candidate; $bounded_winner['rotating_winners']['count'] = $bounds[0]; $bounded_winner['rotating_winners']['autoplay_ms'] = $bounds[1];
    lunara_test_assert( $bounded_winner === lunara_oscars_portal_studio_validate_config( $bounded_winner ), 'Winner counts and milliseconds accept both bounds, zero-off, the 7200ms default and values between 500ms steps.' );
}
foreach ( lunara_oscars_portal_studio_winner_specs() as $family => $fields ) {
    foreach ( $fields as $field => $spec ) {
        if ( 'text' !== $spec['type'] ) { continue; }
        $bounded_winner = $winner_candidate; $bounded_winner[$family][$field] = '  ';
        lunara_test_assert( $spec['default'] === lunara_oscars_portal_studio_validate_config( $bounded_winner )[$family][$field], 'Blank winner text inherits its shipped fallback: ' . $family . '.' . $field );
        $bounded_winner[$family][$field] = '<b>' . str_repeat( 'x', $spec['max'] + 50 ) . '</b>';
        lunara_test_assert( str_repeat( 'x', $spec['max'] ) === lunara_oscars_portal_studio_validate_config( $bounded_winner )[$family][$field], 'Explicit winner saves sanitize and bound text: ' . $family . '.' . $field );
    }
}
$winner_saved = lunara_oscars_portal_studio_promote_config_transaction( $winner_candidate );
lunara_test_assert( ! is_wp_error( $winner_saved ) && $winner_candidate === lunara_oscars_portal_studio_get_public_config( false ), 'Apply/reload must retain all seven winner choices exactly.' );
$winner_settings = array();
foreach ( lunara_oscars_portal_studio_winner_specs() as $family => $fields ) {
    foreach ( $fields as $field => $spec ) { $winner_settings[$spec['setting']] = true; lunara_test_assert( $winner_candidate[$family][$field] === $lunara_test_theme_mods[$spec['setting']], 'Each winner field writes its original canonical mod: ' . $family . '.' . $field ); }
}
lunara_test_assert( 7 === count( $winner_settings ) && array( 'schema_version', 'section_order', 'presentation' ) === array_keys( $lunara_test_options[LUNARA_OSCARS_PORTAL_STUDIO_OPTION] ), 'Winner editing must keep exactly seven existing mod owners and the unchanged composition-only option.' );
$winner_next = $winner_candidate; $winner_next['winners']['fallback_heading'] = 'Next fallback'; $winner_next['rotating_winners']['count'] = 4; $winner_next['rotating_winners']['autoplay_ms'] = 0;
$winner_next_saved = lunara_oscars_portal_studio_promote_config_transaction( $winner_next );
$winner_restored = lunara_oscars_portal_studio_restore_revision_transaction( $winner_next_saved['revision_id'] );
lunara_test_assert( ! is_wp_error( $winner_restored ) && $winner_candidate === lunara_oscars_portal_studio_get_public_config( false ), 'New revisions restore winner text, count and autoplay through existing owners.' );
$winner_old_state = $winner_candidate; unset( $winner_old_state['winners'], $winner_old_state['rotating_winners'] );
$winner_old_revision = lunara_oscars_portal_studio_push_revision( $winner_old_state, 'save' );
$lunara_test_theme_mods['lunara_oscars_latest_winners_heading'] = ' ';
$lunara_test_theme_mods['lunara_oscars_rotating_winners_heading'] = $winner_long_heading;
$lunara_test_theme_mods['lunara_oscars_rotating_winners_count'] = '-14';
unset( $lunara_test_theme_mods['lunara_oscars_rotating_winners_autoplay'], $lunara_test_theme_mods['lunara_oscars_rotating_winners_link_label'] );
$winner_raw_mods = array_intersect_key( $lunara_test_theme_mods, $winner_settings );
$winner_old_restore = lunara_oscars_portal_studio_restore_revision_transaction( $winner_old_revision );
lunara_test_assert( ! is_wp_error( $winner_old_restore ) && $winner_raw_mods === array_intersect_key( $lunara_test_theme_mods, $winner_settings ) && 7200 === $winner_old_restore['state']['rotating_winners']['autoplay_ms'], 'Pre-extension history preserves raw winner text/types and missing mods while resolving the correct live 7200ms fallback.' );
list( $lunara_test_options, $lunara_test_theme_mods, $lunara_test_transients, $lunara_test_actions_fired ) = $winner_snapshot;
fwrite( STDOUT, "Portal winners: seven canonical controls, strict input, 7200ms default, pure legacy reads, private previews and old/new history passed.\n" );
