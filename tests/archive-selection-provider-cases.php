<?php
/** Runs against each real provider and its existing isolated WordPress harness. */
$prefix = 'lunara_' . $archive_selection_kind . '_archive_studio_';
$option = constant( 'LUNARA_' . strtoupper( $archive_selection_kind ) . '_ARCHIVE_STUDIO_OPTION' );
$revision_option = constant( 'LUNARA_' . strtoupper( $archive_selection_kind ) . '_ARCHIVE_STUDIO_REVISIONS_OPTION' );
$selection_checks = 0;
if ( ! isset( $lunara_test_posts[14] ) ) { $lunara_test_posts[14] = clone $lunara_test_posts[12]; $lunara_test_posts[14]->ID = 14; $lunara_test_posts[14]->post_title = 'Older manual lead'; $lunara_test_posts[14]->post_date = '2026-07-01 00:00:00'; }
$check = static function ( $condition, $message ) use ( &$selection_checks ) { ++$selection_checks; lunara_test_assert( $condition, $message ); };
$base = call_user_func( $prefix . 'defaults' );
$v1 = $base;
$v1['selection_version'] = 1; $v1['lead_mode'] = 'manual'; $v1['lead_id'] = 12; $v1['lane_mode'] = 'curated'; $v1['curated_ids'] = array( 12, 13, 999999, 11 );
$check( ! is_wp_error( call_user_func( $prefix . 'validate_config', $v1 ) ), 'V1 stores eligible manual lead and skips unavailable priority IDs at render time.' );
$saved = call_user_func( $prefix . 'promote_config_transaction', $v1 );
$check( ! is_wp_error( $saved ), 'Explicit selection adoption uses existing promotion transaction.' );
$read = call_user_func( $prefix . 'get_public_config', false );
$check( 1 === $read['selection_version'] && 12 === $read['lead_id'] && $v1['curated_ids'] === $read['curated_ids'], 'Canonical provider retains exact adopted selection state.' );
$check( in_array( 'curated_selection_unavailable', $read['_warnings'], true ), 'Unavailable saved priority rows remain visible as warnings.' );
$v1['lead_mode'] = 'automatic'; $v1['lane_mode'] = 'query';
$saved = call_user_func( $prefix . 'promote_config_transaction', $v1 );
$read = call_user_func( $prefix . 'get_public_config', false );
$check( ! is_wp_error( $saved ) && 12 === $read['lead_id'] && $v1['curated_ids'] === $read['curated_ids'], 'Automatic modes retain both independent manual choices after public save/read.' );
if ( 'reviews' === $archive_selection_kind ) { $check( 0 === $lunara_test_pinned_id, 'Automatic clears only active pin, not remembered selection.' ); }
$v1['lead_mode'] = 'manual'; $v1['lane_mode'] = 'curated';
$check( ! is_wp_error( call_user_func( $prefix . 'promote_config_transaction', $v1 ) ), 'Returning to manual restores retained lead and curation.' );
if ( 'reviews' === $archive_selection_kind ) {
	$check( 12 === $lunara_test_pinned_id, 'Returning to manual writes the canonical review pin.' );
	lunara_set_pinned_review_id( 11 );
	$external = call_user_func( $prefix . 'get_public_config', false );
	$check( 11 === $external['lead_id'] && 'manual' === $external['lead_mode'], 'External canonical pin change wins over remembered option.' );
	lunara_set_pinned_review_id( 0 );
	$external = call_user_func( $prefix . 'get_public_config', false );
	$check( 'automatic' === $external['lead_mode'] && 12 === $external['lead_id'], 'External unpin leaves Automatic and remembered ID independently represented.' );
}
foreach ( array( 0, 13, 999999 ) as $invalid_lead ) { $bad = $v1; $bad['lead_id'] = $invalid_lead; $check( is_wp_error( call_user_func( $prefix . 'validate_config', $bad ) ), 'Active Manual requires an eligible published lead.' ); }
foreach ( array( -1, true, 12.5, '12tail' ) as $invalid_id ) { $bad = $v1; $bad['lead_mode'] = 'automatic'; $bad['lead_id'] = $invalid_id; $check( is_wp_error( call_user_func( $prefix . 'validate_config', $bad ) ), 'Inactive remembered IDs still reject malformed values.' ); }
foreach ( array( array( 11, 11 ), array( -11 ), array( true ), array( 11.5 ), range( 1000, 1024 ) ) as $invalid_ids ) { $bad = $v1; $bad['curated_ids'] = $invalid_ids; $check( is_wp_error( call_user_func( $prefix . 'validate_config', $bad ) ), 'Malformed, duplicate and over-limit priority lists are rejected.' ); }
$empty = $v1; $empty['curated_ids'] = array();
$check( ! is_wp_error( call_user_func( $prefix . 'validate_config', $empty ) ) && in_array( 'curated_selection_empty', lunara_archive_selection_warnings( $empty, $archive_selection_kind ), true ), 'Empty curated list is explicit no-priority with warning, preserving full archive.' );
$inactive = $v1; $inactive['lead_mode'] = 'automatic'; $inactive['lead_id'] = 13;
$check( ! is_wp_error( call_user_func( $prefix . 'validate_config', $inactive ) ), 'Unavailable inactive manual choice remains recoverable.' );
$classic_option = lunara_archive_selection_unavailable_lead_option( $inactive, $archive_selection_kind );
$classic_rows = lunara_archive_selection_classic_rows( $inactive, $archive_selection_kind );
$check( count( $inactive['curated_ids'] ) === substr_count( $classic_rows, 'type="hidden" name="lunara_' . $archive_selection_kind . '_archive_curated_ids[]"' ) && false === strpos( $classic_rows, 'type="text"' ) && false === strpos( $classic_rows, 'type="number"' ), 'Actual Classic rows emit exactly one hidden canonical field per selected ID and no raw-ID entry control.' );
$check( false !== strpos( $classic_option, 'value="13" selected=' ) && false !== strpos( $classic_option, 'Unavailable selection #13' ), 'Classic lead select retains unavailable remembered choice.' );
$check( false !== strpos( $classic_rows, 'value="999999"' ) && false !== strpos( $classic_rows, 'Unavailable selection #13' ) && false === strpos( $classic_rows, 'Hidden draft' ), 'Classic curated list retains deleted/private IDs without exposing draft metadata.' );
call_user_func( $prefix . 'promote_config_transaction', $inactive );
$form_prefix = 'lunara_' . $archive_selection_kind . '_archive_';
$request = array( $form_prefix . 'lead_mode' => 'automatic', $form_prefix . 'lead_id' => '13', $form_prefix . 'lane_mode' => 'curated', $form_prefix . 'curated_ids' => array( '12', '13', '999999', '11' ) );
$form = call_user_func( $prefix . 'config_from_request', $request );
$check( 1 === $form['selection_version'] && '13' === $form['lead_id'] && array( '12', '13', '999999', '11' ) === $form['curated_ids'], 'Classic request retains marker and unavailable choices from its rendered controls.' );
// Validate selection family independently: unrelated Classic form fields were omitted intentionally.
$check( ! is_wp_error( lunara_archive_selection_validate( $form, $archive_selection_kind ) ), 'Classic unavailable-choice round trip remains valid in Automatic.' );

// Real provider previews read candidate selection without promoting public options or pins.
call_user_func( $prefix . 'promote_config_transaction', $v1 );
$public_options = serialize( $lunara_test_options );
$public_pin = 'reviews' === $archive_selection_kind ? $lunara_test_pinned_id : null;
foreach ( array( 'manual', 'automatic' ) as $mode ) {
	$expected_lead = 'manual' === $mode ? 14 : 11;
	$candidate = $v1; $candidate['lead_mode'] = $mode; $candidate['lead_id'] = $expected_lead; $candidate['curated_ids'] = array( 12, 13, 999999 );
	$token = call_user_func( $prefix . 'store_preview', $candidate );
	$check( is_string( $token ), 'Valid candidate receives a private owner-bound preview.' );
	$_GET[ 'lunara_' . $archive_selection_kind . '_preview' ] = $token;
	$preview = call_user_func( $prefix . 'get_public_config' );
	$check( $mode === $preview['lead_mode'] && $expected_lead === call_user_func( $prefix . 'get_lead_id', $preview ), 'Private preview resolves its selected lead rather than public pin.' );
	if ( 'reviews' === $archive_selection_kind ) {
		$args = lunara_get_review_archive_query_args( array(), 'release_desc', '' );
		$check( $expected_lead === $args['lunara_reviews_archive_pinned_orderby'] && array( 12 ) === $args['lunara_reviews_archive_priority_ids'], 'Actual Reviews query gives candidate lead first, eligible curated priorities second.' );
		$query = new WP_Query(); foreach ( $args as $key => $value ) { $query->set( $key, $value ); }
		$sql = lunara_reviews_archive_pinned_orderby( lunara_reviews_archive_studio_priority_orderby( 'wp_posts.post_date DESC', $query ), $query );
		$check( 0 === strpos( $sql, 'CASE WHEN wp_posts.ID = ' . $expected_lead . ' THEN 0' ) && false === strpos( $sql, '999999' ), 'Executed SQL composition prevents old curation or public pin from owning preview lead.' );
		$filtered = lunara_get_review_archive_query_args( array(), 'release_desc', '2026' );
		$check( ! isset( $filtered['lunara_reviews_archive_pinned_orderby'], $filtered['lunara_reviews_archive_priority_ids'] ), 'Year filter remains authoritative after adoption.' );
	} else {
		$query = new WP_Query(); lunara_journal_archive_studio_configure_query( $query );
		$check( array( $expected_lead, 12 ) === $query->get( 'lunara_journal_archive_priority_ids' ), 'Actual Journal query prioritizes candidate lead and skips unavailable choices.' );
	}
	unset( $_GET[ 'lunara_' . $archive_selection_kind . '_preview' ] );
	$check( $public_options === serialize( $lunara_test_options ) && ( null === $public_pin || $public_pin === $lunara_test_pinned_id ), 'Private previews never write public selection, pin or history.' );
}

// Existing revision owner restores both v1 retained choices and pre-adoption semantics.
$revision_id = call_user_func( $prefix . 'push_revision', $v1 );
$changed = $v1; $changed['lead_mode'] = 'automatic'; $changed['lead_id'] = 11; $changed['curated_ids'] = array();
call_user_func( $prefix . 'promote_config_transaction', $changed );
$restored = call_user_func( $prefix . 'restore_revision_transaction', $revision_id );
$check( ! is_wp_error( $restored ) && $v1['curated_ids'] === $restored['state']['curated_ids'] && 12 === $restored['state']['lead_id'], 'V1 history restores remembered lead and unavailable priority IDs.' );
$legacy = $base; unset( $legacy['selection_version'] );
$legacy_revision = call_user_func( $prefix . 'push_revision', $legacy );
$legacy_restored = call_user_func( $prefix . 'restore_revision_transaction', $legacy_revision );
$check( ! is_wp_error( $legacy_restored ) && 0 === $legacy_restored['state']['selection_version'], 'Old revisions without adoption marker restore exact legacy semantics.' );
if ( 'journal' === $archive_selection_kind ) { $check( 'shared' === $legacy_restored['state']['lead_mode'], 'Old Journal Shared lead remains explicit legacy behavior.' ); }
require __DIR__ . '/archive-selection-metadata-cases.php';
fwrite( STDOUT, 'archive-selection-' . $archive_selection_kind . ': ' . $selection_checks . " assertions passed.\n" );
