<?php
/** Behavioral contract for Theme 3.2.59 editorial and utility adapters. */

require __DIR__ . '/site-studio-pilot-runtime.php';

function lunara_editorial_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function lunara_editorial_state_has_technical_keys( $value ) {
	if ( ! is_array( $value ) ) {
		return false;
	}
	foreach ( $value as $key => $child ) {
		if ( is_string( $key ) && preg_match( '/(?:lunara_|option|theme_mod|callback|revision|token|secret)/i', $key ) ) {
			return true;
		}
		if ( is_array( $child ) && lunara_editorial_state_has_technical_keys( $child ) ) {
			return true;
		}
	}
	return false;
}

function lunara_editorial_expected_review_keys() {
	return array(
		'lunara_review_single_density',
		'lunara_review_single_hero_scale',
		'lunara_review_single_rail_mode',
		'lunara_review_single_debrief_prominence',
		'lunara_review_single_pairing_density',
		'lunara_review_single_spoiler_treatment',
		'lunara_review_single_trailer_prominence',
		'lunara_review_single_section_gap',
		'lunara_review_single_debrief_poster_width',
		'lunara_review_related_count',
		'lunara_review_pair_with_layout',
		'lunara_review_pair_with_text_depth',
		'lunara_review_pair_with_mobile_stack',
		'lunara_review_pair_with_image_focus',
		'lunara_review_pair_with_columns',
		'lunara_review_pair_with_thumb_width',
	);
}

function lunara_editorial_expected_utility_keys() {
	return array(
		'lunara_utility_search_density',
		'lunara_utility_result_treatment',
		'lunara_utility_result_media',
		'lunara_utility_recovery_prominence',
		'lunara_utility_search_lead_focus',
		'lunara_utility_search_spotlight_type',
		'lunara_utility_section_gap',
		'lunara_utility_result_min_height',
		'lunara_utility_card_grid_min',
	);
}

function lunara_editorial_expected_footer_keys() {
	return array(
		'lunara_footer_show_logo',
		'lunara_footer_tagline',
		'lunara_footer_col1_heading',
		'lunara_footer_col2_heading',
		'lunara_footer_col3_heading',
		'lunara_footer_copyright',
	);
}

// Exact, plain-language state and allowlisted canonical ownership.
lunara_pilot_reset();
$review_adapter = lunara_site_studio_review_single_adapter();
$review_state   = $review_adapter->read_state();
lunara_editorial_assert( array( 'review', 'pairing' ) === array_keys( $review_state ), 'Review Single state must use the exact two plain-language groups.' );
lunara_editorial_assert( ! lunara_editorial_state_has_technical_keys( $review_state ), 'Review Single public state must not expose theme-mod or storage keys.' );
lunara_editorial_assert( lunara_editorial_expected_review_keys() === lunara_site_studio_review_single_keys(), 'Review Single must own exactly the existing sixteen presentation mods.' );

$invalid_review = $review_state;
$invalid_review['review']['density'] = 'injected';
$review_before = $lunara_pilot_theme_mods;
$review_invalid_result = $review_adapter->save_state( $invalid_review );
lunara_editorial_assert( is_wp_error( $review_invalid_result ) && $review_before === $lunara_pilot_theme_mods, 'Invalid Review Single state must produce zero canonical mutation.' );

$review_state['review']['density'] = 'compact';
$review_state['pairing']['columns'] = 2;
$review_save = $review_adapter->save_state( $review_state );
lunara_editorial_assert( ! is_wp_error( $review_save ) && 'compact' === get_theme_mod( 'lunara_review_single_density' ) && 2 === get_theme_mod( 'lunara_review_pair_with_columns' ), 'Review Single save must write only normalized canonical values.' );
lunara_editorial_assert( array() === array_diff( array_keys( $lunara_pilot_theme_mods ), lunara_editorial_expected_review_keys() ), 'Review Single save must not write outside its exact allowlist.' );
lunara_editorial_assert( array( 'hero', 'criticism', 'debrief', 'pair-it-with' ) === $review_save['changed_sections'], 'Review Single save must report its exact recognizable sections.' );

// Utility Search owns nine previewable mods; route-specific 404 re-entry remains in Classic controls.
lunara_pilot_reset();
$utility_adapter = lunara_site_studio_utility_search_adapter();
$utility_state   = $utility_adapter->read_state();
lunara_editorial_assert( array( 'presentation', 'focus', 'geometry' ) === array_keys( $utility_state ), 'Utility Search state must use the exact three recognizable groups.' );
lunara_editorial_assert( lunara_editorial_expected_utility_keys() === lunara_site_studio_utility_search_keys(), 'Utility Search must own exactly nine previewable theme mods.' );
lunara_editorial_assert( ! isset( $utility_state['focus']['reentry'] ) && ! in_array( 'lunara_utility_reentry_primary', lunara_site_studio_utility_search_keys(), true ), 'The Search preview must not claim control of the 404-only re-entry destination.' );
lunara_editorial_assert( ! in_array( 'lunara_utility_search_preset', lunara_site_studio_utility_search_keys(), true ) && ! lunara_editorial_state_has_technical_keys( $utility_state ), 'Utility Search must exclude the legacy preset marker and all technical keys.' );

$bad_utility = $utility_state;
$bad_utility['geometry']['section_gap'] = 9999;
$utility_before = $lunara_pilot_theme_mods;
$bad_utility_result = $utility_adapter->save_state( $bad_utility );
lunara_editorial_assert( is_wp_error( $bad_utility_result ) && $utility_before === $lunara_pilot_theme_mods, 'Out-of-range Utility geometry must produce zero mutation.' );

$utility_state['focus']['lead'] = 'journal';
$utility_state['geometry']['section_gap'] = 34;
$utility_save = $utility_adapter->save_state( $utility_state );
lunara_editorial_assert( ! is_wp_error( $utility_save ) && 'journal' === get_theme_mod( 'lunara_utility_search_lead_focus' ) && 34 === get_theme_mod( 'lunara_utility_section_gap' ), 'Utility Search must persist normalized values to its real canonical mods.' );
lunara_editorial_assert( ! array_key_exists( 'lunara_utility_search_preset', $lunara_pilot_theme_mods ) && array() === array_diff( array_keys( $lunara_pilot_theme_mods ), lunara_editorial_expected_utility_keys() ), 'Utility Search save must never persist the compatibility-only preset marker or foreign mods.' );

// Invalid legacy storage must be normalized before it reaches the generic controller.
lunara_pilot_reset();
set_theme_mod( 'lunara_utility_search_density', 'not-an-option' );
set_theme_mod( 'lunara_utility_section_gap', 999 );
set_theme_mod( 'lunara_footer_show_logo', array( 'not', 'a', 'boolean' ) );
set_theme_mod( 'lunara_footer_tagline', array( 'not', 'plain', 'text' ) );
$legacy_utility = lunara_site_studio_utility_search_adapter()->read_state();
$legacy_footer  = lunara_site_studio_footer_adapter()->read_state();
lunara_editorial_assert( 'editorial' === $legacy_utility['presentation']['density'] && 42 === $legacy_utility['geometry']['section_gap'], 'Invalid legacy select and number values must fall back to their safe defaults.' );
lunara_editorial_assert( true === $legacy_footer['brand']['show_logo'] && 'Film criticism and a living Oscar ledger.' === $legacy_footer['brand']['tagline'], 'Invalid legacy boolean and text values must fall back to their safe defaults.' );

// Dotted UI paths are safe only when explicitly allowlisted; technical names remain redacted.
$field_error = new WP_Error(
	'site_studio_editorial_invalid',
	'Invalid editorial state.',
	array(
		'fields' => array(
			'review.density'       => 'Choose a valid density.',
			'geometry.section_gap' => 'Choose a valid gap.',
			'section_visibility'   => 'Choose visible sections.',
			'lunara_secret'        => 'Do not expose me.',
			'secret_field'         => 'Do not expose me either.',
		),
	)
);
$safe_fields = lunara_site_studio_safe_validation_fields( $field_error );
$safe_field_keys = array_keys( $safe_fields );
sort( $safe_field_keys );
lunara_editorial_assert( array( 'geometry.section_gap', 'review.density', 'section_visibility' ) === $safe_field_keys, 'Exact dotted UI errors must survive redaction while technical and secret-looking fields remain hidden.' );

// Known preview-storage failures need operational guidance rather than field-error copy.
$preview_write_response = lunara_site_studio_rest_adapter_error_response( new WP_Error( 'reviews_archive_preview_write_failed', 'Untrusted provider copy.' ) );
$preview_write_data = $preview_write_response->get_data();
lunara_editorial_assert( 'The private preview could not be stored. Try Preview Changes again.' === $preview_write_data['message'] && array() === $preview_write_data['fields'], 'Allowlisted preview write failures must explain the safe retry action without inventing highlighted fields.' );
$preview_readback_response = lunara_site_studio_rest_adapter_error_response( new WP_Error( 'journal_archive_preview_readback_failed', 'Untrusted provider copy.' ) );
$preview_readback_data = $preview_readback_response->get_data();
lunara_editorial_assert( 'The private preview could not be verified. Try Preview Changes again.' === $preview_readback_data['message'] && array() === $preview_readback_data['fields'], 'Allowlisted preview readback failures must explain the safe retry action without exposing provider copy.' );
$unknown_adapter_response = lunara_site_studio_rest_adapter_error_response( new WP_Error( 'unknown_provider_failure', 'Secret internal detail.' ) );
lunara_editorial_assert( 'The requested state was not accepted. Review the highlighted fields and try again.' === $unknown_adapter_response->get_data()['message'], 'Unknown adapter failures must retain the generic redacted message.' );

// Footer exposes six real controls and no phantom social state.
lunara_pilot_reset();
$footer_adapter = lunara_site_studio_footer_adapter();
$footer_state   = $footer_adapter->read_state();
lunara_editorial_assert( array( 'brand', 'columns', 'copyright' ) === array_keys( $footer_state ), 'Footer state must use recognizable brand, columns, and copyright groups.' );
lunara_editorial_assert( lunara_editorial_expected_footer_keys() === lunara_site_studio_footer_keys(), 'Footer must own exactly the six live theme mods.' );
lunara_editorial_assert( false === strpos( wp_json_encode( $footer_state ), 'social' ) && ! lunara_editorial_state_has_technical_keys( $footer_state ), 'Footer state must not expose phantom social or technical keys.' );
$footer_state['brand']['show_logo'] = false;
$footer_state['columns']['editorial'] = 'Criticism';
$footer_save = $footer_adapter->save_state( $footer_state );
lunara_editorial_assert( ! is_wp_error( $footer_save ) && false === get_theme_mod( 'lunara_footer_show_logo' ) && 'Criticism' === get_theme_mod( 'lunara_footer_col1_heading' ), 'Footer save must update its real live renderer values.' );
lunara_editorial_assert( array() === array_diff( array_keys( $lunara_pilot_theme_mods ), lunara_editorial_expected_footer_keys() ), 'Footer save must remain inside the exact real-six allowlist.' );

// Theme preview tokens must bind to each registered route instead of the old root-only path.
lunara_pilot_reset();
$utility_preview = $utility_adapter->create_preview( $utility_adapter->read_state() );
lunara_editorial_assert( ! is_wp_error( $utility_preview ), 'Utility Search must create a private preview.' );
$utility_record = get_transient( 'lunara_site_studio_preview_' . hash( 'sha256', $utility_preview['token'] ) );
lunara_editorial_assert( '/search/' === $utility_record['route'] && 'theme:utility-search' === $utility_record['owner'], 'Utility preview token must be owner-bound to the exact Search route.' );

lunara_pilot_reset();
$review_preview = $review_adapter->create_preview( $review_adapter->read_state() );
$review_record  = get_transient( 'lunara_site_studio_preview_' . hash( 'sha256', $review_preview['token'] ) );
lunara_editorial_assert( ! is_wp_error( $review_preview ) && '/reviews/sinners-2025/' === $review_record['route'], 'Review Single preview token must be bound to the exact representative Review route.' );

// Revisions stay capped at twelve and restore takes a safety snapshot first.
lunara_pilot_reset();
$footer_adapter = lunara_site_studio_footer_adapter();
for ( $index = 0; $index < 13; $index++ ) {
	$state = $footer_adapter->read_state();
	$state['brand']['show_logo'] = 0 === $index % 2;
	$result = $footer_adapter->save_state( $state );
	lunara_editorial_assert( ! is_wp_error( $result ), 'Each Footer revision save must succeed.' );
}
$footer_revisions = $footer_adapter->list_revisions();
lunara_editorial_assert( 12 === count( $footer_revisions ), 'Footer history must retain exactly twelve snapshots.' );
$restore_id = $footer_revisions[11]['id'];
$restored = $footer_adapter->restore_revision( $restore_id );
lunara_editorial_assert( ! is_wp_error( $restored ) && ! empty( $restored['safety_revision_id'] ) && 'restore-safety' === $footer_adapter->list_revisions()[0]['action'], 'Footer restore must create and retain a safety revision before applying the target.' );

echo "site-studio editorial runtime: all assertions passed.\n";
