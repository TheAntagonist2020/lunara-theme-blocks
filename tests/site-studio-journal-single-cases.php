<?php
/** Real Journal article provider transactions, ownership and dynamic preview contract. */
$journal_checks = 0;
function lunara_journal_single_assert( $condition, $message ) { global $journal_checks; lunara_editorial_assert( $condition, $message ); $journal_checks++; }

lunara_pilot_reset();
$journal = lunara_site_studio_journal_single_adapter();
$expected_keys = array( 'lunara_journal_single_hero_title_size', 'lunara_journal_single_image_fit', 'lunara_journal_single_image_position_x', 'lunara_journal_single_image_position_y', 'lunara_journal_show_byline', 'lunara_journal_show_date', 'lunara_journal_show_reading_time' );
lunara_journal_single_assert( $expected_keys === lunara_site_studio_journal_single_keys(), 'Journal article owns exactly its seven presentation mods, never post artwork or galleries.' );
$before = serialize( array( $lunara_pilot_theme_mods, $lunara_pilot_options, $lunara_pilot_posts ) );
$default = $journal->read_state();
lunara_journal_single_assert( array( 'hero' => array( 'title_size' => 84, 'image_fit' => 'cover', 'image_position_x' => 50, 'image_position_y' => 50 ), 'metadata' => array( 'show_byline' => true, 'show_date' => true, 'show_reading_time' => false ) ) === $default, 'Journal defaults preserve its standard headline, full byline/date and reading-time-off behavior.' );
lunara_journal_single_assert( $before === serialize( array( $lunara_pilot_theme_mods, $lunara_pilot_options, $lunara_pilot_posts ) ), 'Reading Journal defaults never writes missing mods or article content.' );

$article = lunara_site_studio_journal_single_preview_article();
lunara_journal_single_assert( array( 'id' => 301, 'route' => '/journal/angel-finally-gets-a-face-that-can-fly/' ) === $article, 'Dynamic Journal preview resolves a real published article permalink.' );
$query = $GLOBALS['lunara_pilot_journal_query'];
lunara_journal_single_assert( 'journal' === $query['post_type'] && 'publish' === $query['post_status'] && false === $query['has_password'] && 1 === $query['posts_per_page'] && array( 'date' => 'DESC', 'ID' => 'DESC' ) === $query['orderby'] && true === $query['ignore_sticky_posts'] && ! isset( $query['meta_key'], $query['post__in'] ), 'Journal preview chooses the newest eligible publication without featured or curated priority.' );
$GLOBALS['lunara_pilot_journal_url'] = home_url( '/journal/a-newer-published-file/' );
lunara_journal_single_assert( '/journal/a-newer-published-file/' === lunara_site_studio_journal_single_preview_route(), 'A new latest article changes the preview route without a hardcoded slug.' );
foreach ( array( 'https://foreign.test/journal/private/', home_url( '/journal/a/?secret=yes' ), home_url( '/journal/a/#fragment' ), home_url( '/journal/a/../b/' ), home_url( '/reviews/a/' ) ) as $url ) {
	$GLOBALS['lunara_pilot_journal_url'] = $url;
	lunara_journal_single_assert( array() === lunara_site_studio_journal_single_preview_article(), 'Invalid or foreign article destinations never become a private preview route.' );
}
unset( $GLOBALS['lunara_pilot_journal_url'] );
foreach ( array( array(), array( (object) array( 'ID' => 301, 'post_type' => 'journal', 'post_status' => 'draft' ) ), array( (object) array( 'ID' => 301, 'post_type' => 'journal', 'post_status' => 'publish', 'post_password' => 'hidden' ) ), array( (object) array( 'ID' => 301, 'post_type' => 'review', 'post_status' => 'publish' ) ) ) as $posts ) {
	$GLOBALS['lunara_pilot_journal_posts'] = $posts;
	lunara_journal_single_assert( array() === lunara_site_studio_journal_single_preview_article() && false === lunara_site_studio_journal_single_dependency()['available'], 'No eligible Journal article produces an explicit unavailable editor without exposing unpublished content.' );
}
unset( $GLOBALS['lunara_pilot_journal_posts'] );

$invalids = array();
foreach ( array( 47, 121, 84.5, '84' ) as $value ) { $bad = $default; $bad['hero']['title_size'] = $value; $invalids[] = $bad; }
foreach ( array( -1, 101, '50', 50.5 ) as $value ) { $bad = $default; $bad['hero']['image_position_x'] = $value; $invalids[] = $bad; }
$bad = $default; $bad['hero']['image_fit'] = 'stretch'; $invalids[] = $bad;
$bad = $default; $bad['metadata']['show_byline'] = 'false'; $invalids[] = $bad;
$bad = $default; $bad['featured_image_id'] = 999; $invalids[] = $bad;
foreach ( $invalids as $bad ) {
	$result = $journal->save_state( $bad );
	lunara_journal_single_assert( is_wp_error( $result ) && $before === serialize( array( $lunara_pilot_theme_mods, $lunara_pilot_options, $lunara_pilot_posts ) ), 'Invalid Journal article input produces zero writes, revisions or post mutations.' );
}
$bad = $default; $bad['metadata']['show_date'] = 'false';
$error = $journal->validate_state( $bad );
lunara_journal_single_assert( array( 'metadata.show_date' ) === array_keys( lunara_site_studio_safe_validation_fields( $error ) ), 'Journal validation points to the exact shared field without exposing internal mod names.' );

$lunara_pilot_theme_mods = array( 'lunara_journal_single_hero_title_size' => '100', 'lunara_journal_show_date' => '0', 'unrelated_setting' => array( 'keep' => true ) );
$legacy = $lunara_pilot_theme_mods;
$candidate = $journal->read_state();
lunara_journal_single_assert( 100 === $candidate['hero']['title_size'] && false === $candidate['metadata']['show_date'] && $legacy === $lunara_pilot_theme_mods, 'Legacy string mods are displayed correctly without normalizing saved storage on read.' );
$candidate['hero'] = array( 'title_size' => 48, 'image_fit' => 'contain', 'image_position_x' => 0, 'image_position_y' => 100 );
$candidate['metadata'] = array( 'show_byline' => false, 'show_date' => true, 'show_reading_time' => true );
$preview = $journal->create_preview( $candidate );
lunara_journal_single_assert( ! is_wp_error( $preview ) && $legacy === $lunara_pilot_theme_mods, 'Journal Preview stores only a private candidate and leaves public settings untouched.' );
$record = get_transient( 'lunara_site_studio_preview_' . hash( 'sha256', $preview['token'] ) );
lunara_journal_single_assert( 'theme:journal-single' === $record['owner'] && '/journal/angel-finally-gets-a-face-that-can-fly/' === $record['route'], 'Private Journal token binds to its canonical owner and current article route.' );
$posts_before = serialize( $lunara_pilot_posts );
$saved = $journal->save_state( $candidate );
lunara_journal_single_assert( ! is_wp_error( $saved ) && $candidate === $saved['state'] && 'contain' === get_theme_mod( 'lunara_journal_single_image_fit' ) && 0 === get_theme_mod( 'lunara_journal_single_image_position_x' ) && 100 === get_theme_mod( 'lunara_journal_single_image_position_y' ), 'Apply saves exact Journal image fit and focal endpoints to renderer-owned mods.' );
lunara_journal_single_assert( false === get_theme_mod( 'lunara_journal_show_byline' ) && true === get_theme_mod( 'lunara_journal_show_reading_time' ) && $posts_before === serialize( $lunara_pilot_posts ) && array( 'keep' => true ) === $lunara_pilot_theme_mods['unrelated_setting'], 'Apply preserves article content/art ownership and unrelated theme settings.' );
$restored = $journal->restore_revision( $saved['revision_id'] );
lunara_journal_single_assert( ! is_wp_error( $restored ) && $legacy === $lunara_pilot_theme_mods, 'History restores exact legacy mod types and original absence of new framing mods.' );
lunara_journal_single_assert( ! empty( $restored['safety_revision_id'] ) && 'restore-safety' === $journal->list_revisions()[0]['action'], 'Journal history takes a safety snapshot before restoring old presentation.' );
$redone = $journal->restore_revision( $restored['safety_revision_id'] );
lunara_journal_single_assert( ! is_wp_error( $redone ) && $candidate === $redone['state'], 'The safety revision recovers all seven applied Journal controls.' );

$rollback_before = $lunara_pilot_theme_mods;
$failure_candidate = $candidate; $failure_candidate['hero']['image_fit'] = 'cover'; $failure_candidate['hero']['image_position_x'] = 67;
$lunara_pilot_mod_fault = array( 'key' => 'lunara_journal_single_image_position_x', 'remaining' => 1, 'mode' => 'fail' );
$failed = $journal->save_state( $failure_candidate );
lunara_journal_single_assert( is_wp_error( $failed ) && $rollback_before === $lunara_pilot_theme_mods, 'A failed Journal framing write rolls back every touched setting to its prior exact value.' );
$lunara_pilot_mod_fault = array();

$customizer = new class {
	public $controls = array( 'lunara_journal_single_hero_title_size' => true, 'unrelated' => true );
	public $settings = array( 'lunara_journal_single_hero_title_size' => true, 'unrelated' => true );
	public function remove_control( $key ) { unset( $this->controls[$key] ); }
	public function remove_setting( $key ) { unset( $this->settings[$key] ); }
};
lunara_site_studio_journal_single_retire_customizer( $customizer );
lunara_journal_single_assert( array( 'unrelated' => true ) === $customizer->controls && array( 'unrelated' => true ) === $customizer->settings && $rollback_before === $lunara_pilot_theme_mods, 'Retiring the Classic title-size writer prevents stale saves and preserves stored values and unrelated controls.' );
echo 'Journal article provider: ' . $journal_checks . " assertions passed.\n";
