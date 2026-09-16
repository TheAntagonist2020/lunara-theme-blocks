<?php
/** Real shared transactions and exact live winner readers/markup, with cached ledger fixture data. */
function lunara_test_portal_winner_markup( $source, $ceremony_label = '98th Academy Awards' ) {
    $start = strpos( $source, '$oscars_studio_config     =' ); $end = strpos( $source, '$best_picture', $start );
    lunara_test_assert( false !== $start && false !== $end, 'Live winner preamble including the real showcase call is available.' );
    eval( substr( $source, $start, $end - $start ) );
    $snapshot = array( 'ceremony_label' => $ceremony_label, 'ceremony_url' => 'https://example.test/oscars/ceremony/98/' );
    foreach ( array( 'ceremony_label_str', 'ceremony_url_str' ) as $variable ) {
        preg_match( '/^\$' . $variable . '\s*=.*;\r?$/m', $source, $assignment );
        lunara_test_assert( ! empty( $assignment[0] ), 'Live latest ceremony label/URL reader is available.' ); eval( $assignment[0] );
    }
    $start = strpos( $source, '<div class="lunara-home-section-header">', strpos( $source, '<section id="oscars-winners"' ) );
    $end = strpos( $source, '<div class="lunara-ceremony-winners-grid">', $start );
    ob_start(); eval( '?>' . substr( $source, $start, $end - $start ) ); $latest_html = ob_get_clean();
    $start = strpos( $source, '<?php if ( ! empty( $rotating_cards ) ) : ?>' );
    $capture = strpos( $source, "\$oscars_slot_markup['rotating-winners']", $start );
    $end = strrpos( substr( $source, 0, $capture ), '<?php' );
    lunara_test_assert( false !== $start && false !== $capture && $end > $start, 'Live rotating winner markup is available.' );
    $database_url = 'https://example.test/oscars/ledger/';
    ob_start(); eval( '?>' . substr( $source, $start, $end - $start ) ); $rotating_html = ob_get_clean();
    return compact( 'latest_html', 'rotating_html', 'rotating_cards', 'rotating_count', 'rotating_autoplay', 'show_latest_winners', 'rotating_enabled' );
}
$winner_shared_snapshot = array( $lunara_test_options, $lunara_test_theme_mods, $lunara_test_transients, $lunara_test_actions_fired );
$winner_day = function_exists( 'wp_date' ) ? intval( wp_date( 'z' ) ) : intval( date( 'z' ) );
foreach ( array( 4, 10, 16 ) as $count ) {
    $cards = array();
    for ( $i = 1; $i <= $count; $i++ ) { $cards[] = array( 'primary_label' => 'Cached winner ' . $count . '-' . $i, 'category_label' => 'Category ' . $i, 'film' => 'Film ' . $i, '_visual' => array() ); }
    set_transient( 'lunara_oscars_rotating_showcase_v4_' . $winner_day . '_' . $count, array( 'winner_cards' => $cards, 'ceremony_label' => 'Cached ceremony ' . $count, 'ceremony_url' => 'https://example.test/oscars/ceremony/' . $count . '/' ), 86400 );
}
$winner_initial = $adapter->read_state();
$winner_draft = $winner_initial;
$winner_draft['winners'] = array( 'fallback_heading' => 'Private latest fallback', 'link_label' => 'Private latest link' );
$winner_draft['rotating_winners'] = array( 'kicker' => 'Private rotating kicker', 'heading' => 'Private rotating heading', 'link_label' => 'Private rotating link', 'count' => 16, 'autoplay_ms' => 7251 );
$winner_before = serialize( array( $lunara_test_options, $lunara_test_theme_mods ) );
$winner_preview = $adapter->create_preview( $winner_draft );
lunara_test_assert( ! is_wp_error( $winner_preview ), 'The shared adapter must accept every winner field for private Preview.' );
$_GET['lunara_oscars_preview'] = $winner_preview['token'];
$winner_token_before = serialize( $lunara_test_transients );
$winner_markup = lunara_test_portal_winner_markup( $source );
lunara_test_assert( 16 === count( $winner_markup['rotating_cards'] ) && 16 === substr_count( $winner_markup['rotating_html'], '<article ' ) && false !== strpos( $winner_markup['rotating_html'], 'Cached winner 16-16' ), 'Actual private showcase call must use the selected count and its count-scoped cache payload.' );
lunara_test_assert( false !== strpos( $winner_markup['rotating_html'], 'data-lunara-carousel-autoplay="7251"' ) && false !== strpos( $winner_markup['rotating_html'], 'Private rotating kicker' ) && false !== strpos( $winner_markup['rotating_html'], 'Private rotating heading' ) && false !== strpos( $winner_markup['rotating_html'], '>Private rotating link</a>' ), 'Actual rotating winner markup must use the private timer and all three copy fields.' );
lunara_test_assert( false !== strpos( $winner_markup['latest_html'], '>98th Academy Awards</h2>' ) && false === strpos( $winner_markup['latest_html'], 'Private latest fallback' ) && false !== strpos( $winner_markup['latest_html'], '>Private latest link</a>' ), 'A real ceremony label takes precedence over the fallback while the private latest link remains editable.' );
$winner_no_label = lunara_test_portal_winner_markup( $source, '' );
lunara_test_assert( false !== strpos( $winner_no_label['latest_html'], '>Private latest fallback</h2>' ), 'The private latest fallback heading appears only when the actual snapshot label is absent.' );
lunara_test_assert( $winner_token_before === serialize( $lunara_test_transients ) && $winner_before === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'Rendering a private winner candidate reuses canonical data caches and changes no public settings or cached payload shape.' );
$winner_paused = $winner_draft; $winner_paused['rotating_winners']['count'] = 4; $winner_paused['rotating_winners']['autoplay_ms'] = 0;
$winner_paused_preview = $adapter->create_preview( $winner_paused ); $_GET['lunara_oscars_preview'] = $winner_paused_preview['token'];
$winner_paused_markup = lunara_test_portal_winner_markup( $source );
lunara_test_assert( 4 === count( $winner_paused_markup['rotating_cards'] ) && false === strpos( $winner_paused_markup['rotating_html'], 'data-lunara-carousel-autoplay=' ) && false !== strpos( $winner_paused_markup['rotating_html'], 'data-lunara-carousel-track' ), 'Zero autoplay removes the timer attribute while retaining four readable cards and manual carousel markup.' );
$winner_hidden = $winner_draft; $winner_hidden['section_visibility']['winners'] = false; $winner_hidden['section_visibility']['rotating-winners'] = false;
$winner_hidden_preview = $adapter->create_preview( $winner_hidden ); $_GET['lunara_oscars_preview'] = $winner_hidden_preview['token'];
$winner_hidden_markup = lunara_test_portal_winner_markup( $source );
lunara_test_assert( false === $winner_hidden_markup['show_latest_winners'] && false === $winner_hidden_markup['rotating_enabled'] && array() === $winner_hidden_markup['rotating_cards'] && '' === trim( $winner_hidden_markup['rotating_html'] ), 'Existing visibility owners still hide the winner lanes and suppress the rotating showcase.' );
unset( $_GET['lunara_oscars_preview'] );
$winner_public_markup = lunara_test_portal_winner_markup( $source );
lunara_test_assert( 10 === count( $winner_public_markup['rotating_cards'] ) && false !== strpos( $winner_public_markup['rotating_html'], 'data-lunara-carousel-autoplay="7200"' ) && false === strpos( $winner_public_markup['latest_html'] . $winner_public_markup['rotating_html'], 'Private' ), 'Anonymous/public winner output keeps its original copy, count and 7200ms timer after private previews.' );
foreach ( array( 'winners.fallback_heading' => array(), 'rotating_winners.count' => 3, 'rotating_winners.autoplay_ms' => 12001 ) as $path => $value ) {
    $invalid = $winner_draft; list( $family, $field ) = explode( '.', $path ); $invalid[$family][$field] = $value;
    foreach ( array( 'lunara_site_studio_rest_preview', 'lunara_site_studio_rest_save' ) as $operation ) {
        $response = $operation( new WP_REST_Request( array( 'surface' => 'oscars-portal', 'state' => $invalid ) ) );
        lunara_test_assert( 422 === $response->get_status() && array( $path ) === array_keys( $response->get_data()['fields'] ), 'Shared winner Preview/Apply errors retain the exact safe control anchor: ' . $path );
    }
}
lunara_test_assert( $winner_before === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'Invalid shared winner requests leave public options/mods and revisions unchanged.' );
$winner_failed_before = serialize( array( $lunara_test_options, $lunara_test_theme_mods ) );
$lunara_test_revision_write_mode = 'fail'; $winner_failed = $adapter->save_state( $winner_draft ); $lunara_test_revision_write_mode = 'normal';
lunara_test_assert( is_wp_error( $winner_failed ) && $winner_failed_before === serialize( array( $lunara_test_options, $lunara_test_theme_mods ) ), 'Winner Apply must abort before all seven canonical writes when the safety revision cannot be saved.' );
$winner_applied = $adapter->save_state( $winner_draft );
lunara_test_assert( ! is_wp_error( $winner_applied ) && $winner_draft === $adapter->read_state(), 'Shared Apply/reload must preserve the complete winner candidate.' );
$winner_restored = $adapter->restore_revision( $winner_applied['revision_id'] );
lunara_test_assert( ! is_wp_error( $winner_restored ) && $winner_initial === $adapter->read_state(), 'Shared History must restore the exact preceding winner configuration.' );
list( $lunara_test_options, $lunara_test_theme_mods, $lunara_test_transients, $lunara_test_actions_fired ) = $winner_shared_snapshot;
fwrite( STDOUT, "Shared Portal winners: exact live headings/count/timer, private/public isolation, zero-off, visibility, errors and Apply/History passed.\n" );
