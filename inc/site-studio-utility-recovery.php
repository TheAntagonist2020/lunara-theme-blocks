<?php
/** Search and genuine 404 presentation, retaining canonical theme-mod ownership. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** The exact nine-field state emitted before Search copy controls were adopted. */
function lunara_site_studio_utility_search_legacy_spec() {
	return array(
		'presentation' => array(
			'density' => array( 'mod' => 'lunara_utility_search_density', 'type' => 'select', 'default' => 'editorial', 'allowed' => array( 'compact', 'editorial', 'showcase' ) ),
			'result_treatment' => array( 'mod' => 'lunara_utility_result_treatment', 'type' => 'select', 'default' => 'cards', 'allowed' => array( 'list', 'cards', 'spotlight' ) ),
			'result_media' => array( 'mod' => 'lunara_utility_result_media', 'type' => 'select', 'default' => 'guarded', 'allowed' => array( 'guarded', 'poster-led', 'text-led' ) ),
			'recovery_prominence' => array( 'mod' => 'lunara_utility_recovery_prominence', 'type' => 'select', 'default' => 'standard', 'allowed' => array( 'quiet', 'standard', 'strong' ) ),
		),
		'focus' => array(
			'lead' => array( 'mod' => 'lunara_utility_search_lead_focus', 'type' => 'select', 'default' => 'balanced', 'allowed' => array( 'balanced', 'ledger', 'reviews', 'journal' ) ),
			'spotlight' => array( 'mod' => 'lunara_utility_search_spotlight_type', 'type' => 'select', 'default' => 'automatic', 'allowed' => array( 'automatic', 'review', 'journal', 'page' ) ),
		),
		'geometry' => array(
			'section_gap' => array( 'mod' => 'lunara_utility_section_gap', 'type' => 'int', 'value_scale' => 1, 'default' => 42, 'min' => 20, 'max' => 84 ),
			'result_min_height' => array( 'mod' => 'lunara_utility_result_min_height', 'type' => 'int', 'value_scale' => 1, 'default' => 158, 'min' => 118, 'max' => 260 ),
			'card_grid_min' => array( 'mod' => 'lunara_utility_card_grid_min', 'type' => 'int', 'value_scale' => 1, 'default' => 280, 'min' => 220, 'max' => 360 ),
		),
	);
}

function lunara_site_studio_utility_search_extended_spec() {
	$spec = lunara_site_studio_utility_search_legacy_spec();
	$spec['content'] = array(
		'kicker' => array( 'mod' => 'lunara_search_kicker', 'type' => 'text', 'default' => 'Search Desk', 'max_length' => 120, 'preserve_legacy_read' => true, 'label' => __( 'Kicker', 'lunara-film' ) ),
		'no_query_title' => array( 'mod' => 'lunara_search_no_query_title', 'type' => 'text', 'default' => 'Search Lunara Film', 'max_length' => 220, 'preserve_legacy_read' => true, 'label' => __( 'Empty search heading', 'lunara-film' ) ),
		'excerpt_words' => array( 'mod' => 'lunara_search_excerpt_words', 'type' => 'int', 'default' => 22, 'min' => 10, 'max' => 50, 'value_scale' => 1, 'preserve_legacy_read' => true, 'label' => __( 'Excerpt words', 'lunara-film' ) ),
		'use_empty_title' => array( 'mod' => 'lunara_search_no_query_title_enabled', 'type' => 'bool', 'default' => false, 'label' => __( 'Use this heading for an empty search', 'lunara-film' ), 'help' => __( 'Enable and Apply to use the saved heading. Turning this off keeps the text and restores the standard heading.', 'lunara-film' ) ),
	);
	return $spec;
}

function lunara_site_studio_utility_404_spec() {
	$text = static function ( $mod, $default, $limit, $label, $type = 'text' ) {
		return array( 'mod' => $mod, 'type' => $type, 'default' => $default, 'max_length' => $limit, 'preserve_legacy_read' => true, 'label' => __( $label, 'lunara-film' ) );
	};
	return array(
		'hero' => array(
			'kicker' => $text( 'lunara_404_kicker', 'Lost Signal', 120, 'Kicker' ),
			'title' => $text( 'lunara_404_title', 'This page is not on the record.', 220, 'Heading' ),
			'explanation' => $text( 'lunara_404_explanation', 'The route you followed does not currently resolve inside Lunara Film. The publication shell is still intact, and the quickest way back is through the front door, the reviews, or the Oscars ledger.', 1000, 'Explanation', 'textarea' ),
		),
		'guidance' => array(
			'reset_label' => $text( 'lunara_404_reset_label', 'Best Reset', 120, 'Reset label' ),
			'reset_desc' => $text( 'lunara_404_reset_desc', 'Return to the homepage and start fresh.', 360, 'Reset description' ),
			'fastest_label' => $text( 'lunara_404_fastest_label', 'Fastest Route', 120, 'Fastest route label' ),
			'fastest_desc' => $text( 'lunara_404_fastest_desc', 'Search a title, name, or keyword.', 360, 'Fastest route description' ),
			'hubs_label' => $text( 'lunara_404_hubs_label', 'Stable Hubs', 120, 'Hubs label' ),
			'hubs_desc' => $text( 'lunara_404_hubs_desc', 'Reviews / Journal / Oscar Ledger', 360, 'Hubs description' ),
		),
		'recovery' => array(
			'title' => $text( 'lunara_404_reentry_title', 'Choose the cleanest way back in.', 220, 'Recovery heading' ),
			'primary' => array( 'mod' => 'lunara_utility_reentry_primary', 'type' => 'select', 'default' => 'home', 'allowed' => array( 'home', 'reviews', 'journal', 'oscars', 'search' ), 'label' => __( 'Primary destination', 'lunara-film' ), 'help' => __( 'Put this destination first. All five recovery links remain available.', 'lunara-film' ) ),
		),
	);
}

/** Preserve what the active renderer reads; validation occurs only for a candidate. */
function lunara_site_studio_utility_recovery_read_state( $spec ) {
	$state = lunara_site_studio_mod_surface_read_state( $spec );
	foreach ( $spec as $group => $fields ) {
		foreach ( $fields as $field => $definition ) {
			if ( empty( $definition['preserve_legacy_read'] ) ) { continue; }
			$value = get_theme_mod( $definition['mod'], $definition['default'] );
			$state[ $group ][ $field ] = 'int' === $definition['type'] ? absint( $value ) : ( is_scalar( $value ) || null === $value ? (string) $value : (string) $definition['default'] );
		}
	}
	return $state;
}

/** Match native HTML maxlength / JavaScript string.length, including emoji. */
function lunara_site_studio_utility_recovery_text_length( $value ) {
	if ( 1 !== preg_match( '//u', $value ) ) { return PHP_INT_MAX; }
	$count = preg_match_all( '/./us', $value, $matches );
	if ( false === $count ) { return PHP_INT_MAX; }
	foreach ( $matches[0] as $character ) { if ( 4 === strlen( $character ) ) { ++$count; } }
	return $count;
}

/** Strict full candidates; legacy preview/history admission is deliberately separate. */
function lunara_site_studio_utility_recovery_validate_state( $candidate, $spec, $code ) {
	if ( ! is_array( $candidate ) || array_keys( $candidate ) !== array_keys( $spec ) ) {
		return new WP_Error( $code . '_invalid', __( 'Reload this editor to get every setting.', 'lunara-film' ), array( 'fields' => array( 'state' => __( 'The complete editor state is required.', 'lunara-film' ) ) ) );
	}
	$normalized = array(); $errors = array();
	foreach ( $spec as $group => $definitions ) {
		if ( ! is_array( $candidate[ $group ] ) || array_keys( $candidate[ $group ] ) !== array_keys( $definitions ) ) { $errors[ $group ] = __( 'Complete every control in this section.', 'lunara-film' ); continue; }
		$normalized[ $group ] = array();
		foreach ( $definitions as $field => $definition ) {
			$value = $candidate[ $group ][ $field ]; $path = $group . '.' . $field;
			if ( in_array( $definition['type'], array( 'text', 'textarea' ), true ) ) {
				if ( ! is_string( $value ) ) { $errors[ $path ] = __( 'Enter plain text.', 'lunara-film' ); continue; }
				if ( lunara_site_studio_utility_recovery_text_length( $value ) > $definition['max_length'] ) { $errors[ $path ] = __( 'Shorten this text before saving.', 'lunara-film' ); continue; }
				$value = 'textarea' === $definition['type'] ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
			} elseif ( 'int' === $definition['type'] ) {
				if ( ! is_int( $value ) || $value < $definition['min'] || $value > $definition['max'] ) { $errors[ $path ] = __( 'Choose a whole number inside the available range.', 'lunara-film' ); continue; }
			} elseif ( 'select' === $definition['type'] ) {
				if ( ! is_string( $value ) || ! in_array( $value, $definition['allowed'], true ) ) { $errors[ $path ] = __( 'Choose one of the available destinations or settings.', 'lunara-film' ); continue; }
			} elseif ( 'bool' === $definition['type'] ) {
				if ( ! is_bool( $value ) ) { $errors[ $path ] = __( 'Choose on or off.', 'lunara-film' ); continue; }
			} else { $errors[ $path ] = __( 'This setting is unavailable.', 'lunara-film' ); continue; }
			$normalized[ $group ][ $field ] = $value;
		}
	}
	return $errors ? new WP_Error( $code . '_invalid', __( 'Review the highlighted controls.', 'lunara-film' ), array( 'fields' => $errors ) ) : $normalized;
}

function lunara_site_studio_utility_recovery_save_state( $candidate, $surface, $spec, $sections ) {
	$code = str_replace( '-', '_', 'site_studio_' . $surface );
	$validated = lunara_site_studio_utility_recovery_validate_state( $candidate, $spec, $code );
	if ( is_wp_error( $validated ) ) { return $validated; }
	$keys = lunara_site_studio_mod_surface_keys( $spec ); $before = lunara_site_studio_raw_mod_snapshot( $keys );
	if ( ! lunara_site_studio_apply_mod_snapshot( lunara_site_studio_mod_surface_desired_snapshot( $validated, $spec ), $keys ) ) {
		$restored = lunara_site_studio_apply_mod_snapshot( $before, $keys );
		return new WP_Error( $code . ( $restored ? '_write_failed' : '_rollback_failed' ), __( 'The settings could not be saved safely.', 'lunara-film' ) );
	}
	$revision = lunara_site_studio_private_revision( $surface, array( 'mods' => $before ), 'save' );
	if ( is_wp_error( $revision ) ) {
		if ( ! lunara_site_studio_apply_mod_snapshot( $before, $keys ) ) { return new WP_Error( $code . '_rollback_failed', __( 'The settings could not be restored safely.', 'lunara-film' ) ); }
		return $revision;
	}
	return array( 'state' => lunara_site_studio_utility_recovery_read_state( $spec ), 'changed_sections' => $sections, 'revision_id' => $revision, 'timestamp' => current_time( 'mysql' ) );
}

function lunara_site_studio_utility_recovery_restore_revision( $id, $surface, $spec, $legacy_keys = array() ) {
	$target = lunara_site_studio_private_revision_target( $surface, $id );
	if ( is_wp_error( $target ) ) { return $target; }
	$keys = lunara_site_studio_mod_surface_keys( $spec ); $current = lunara_site_studio_raw_mod_snapshot( $keys );
	if ( ! is_array( $target ) || array( 'mods' ) !== array_keys( $target ) ) { return new WP_Error( 'site_studio_revision_invalid', __( 'The selected revision is invalid.', 'lunara-film' ) ); }
	$desired = $legacy_keys ? lunara_site_studio_merge_legacy_mod_snapshot( $target['mods'], $current, $keys, $legacy_keys ) : ( lunara_site_studio_valid_mod_snapshot( $target['mods'], $keys ) ? $target['mods'] : false );
	if ( false === $desired ) { return new WP_Error( 'site_studio_revision_invalid', __( 'The selected revision is invalid.', 'lunara-film' ) ); }
	$option = lunara_site_studio_revision_option_name( $surface ); $revision_before = lunara_site_studio_raw_option_snapshot( $option );
	$safety = lunara_site_studio_private_revision( $surface, array( 'mods' => $current ), 'restore-safety' );
	if ( is_wp_error( $safety ) ) { return $safety; }
	if ( ! lunara_site_studio_apply_mod_snapshot( $desired, $keys ) ) {
		$mods_restored = lunara_site_studio_apply_mod_snapshot( $current, $keys );
		$history_restored = lunara_site_studio_apply_option_snapshot( $option, $revision_before );
		return new WP_Error( $mods_restored && $history_restored ? 'site_studio_restore_failed' : 'site_studio_restore_rollback_failed', __( 'The revision could not be restored safely.', 'lunara-film' ) );
	}
	return array( 'state' => lunara_site_studio_utility_recovery_read_state( $spec ), 'safety_revision_id' => $safety, 'timestamp' => current_time( 'mysql' ) );
}

function lunara_site_studio_utility_search_extended_read_state() { return lunara_site_studio_utility_recovery_read_state( lunara_site_studio_utility_search_extended_spec() ); }
function lunara_site_studio_utility_search_extended_validate_state( $state ) { return lunara_site_studio_utility_recovery_validate_state( $state, lunara_site_studio_utility_search_extended_spec(), 'site_studio_utility_search' ); }
function lunara_site_studio_utility_search_extended_save_state( $state ) { return lunara_site_studio_utility_recovery_save_state( $state, 'utility-search', lunara_site_studio_utility_search_extended_spec(), array( 'search-command', 'direct-matches', 'result-run', 'recovery' ) ); }
function lunara_site_studio_utility_search_extended_restore_revision( $id ) { return lunara_site_studio_utility_recovery_restore_revision( $id, 'utility-search', lunara_site_studio_utility_search_extended_spec(), lunara_site_studio_mod_surface_keys( lunara_site_studio_utility_search_legacy_spec() ) ); }

function lunara_site_studio_utility_404_state_schema() { return lunara_site_studio_mod_surface_schema( lunara_site_studio_utility_404_spec() ); }
function lunara_site_studio_utility_404_read_state() { return lunara_site_studio_utility_recovery_read_state( lunara_site_studio_utility_404_spec() ); }
function lunara_site_studio_utility_404_validate_state( $state ) { return lunara_site_studio_utility_recovery_validate_state( $state, lunara_site_studio_utility_404_spec(), 'site_studio_utility_404' ); }
function lunara_site_studio_utility_404_save_state( $state ) { return lunara_site_studio_utility_recovery_save_state( $state, 'utility-404', lunara_site_studio_utility_404_spec(), array( 'search-command', 'recovery' ) ); }
function lunara_site_studio_utility_404_restore_revision( $id ) { return lunara_site_studio_utility_recovery_restore_revision( $id, 'utility-404', lunara_site_studio_utility_404_spec() ); }
function lunara_site_studio_utility_404_adapter() { return new Lunara_Site_Studio_Theme_Adapter( 'utility-404', 'theme:utility-404', array( 'read' => 'lunara_site_studio_utility_404_read_state', 'validate' => 'lunara_site_studio_utility_404_validate_state', 'save' => 'lunara_site_studio_utility_404_save_state', 'restore' => 'lunara_site_studio_utility_404_restore_revision' ) ); }

function lunara_site_studio_utility_search_preview_cases() { return array( 'results' => array( 'q' => 'Lunara' ), 'start' => array() ); }
function lunara_site_studio_utility_404_preview_route() { return '/definitely-not-a-real-lunara-route/'; }
function lunara_site_studio_utility_404_preview_request_valid() { return is_404() && lunara_site_studio_preview_request_path( lunara_site_studio_utility_404_preview_route() ); }

/** Invoke only after authenticating the old stored token; never from a save endpoint. */
function lunara_site_studio_utility_search_legacy_preview_state_valid( $state ) {
	$validated = lunara_site_studio_mod_surface_validate_state( $state, lunara_site_studio_utility_search_legacy_spec(), 'site_studio_utility_search_legacy' );
	return is_array( $state ) && is_array( $validated ) && $state === $validated;
}
function lunara_site_studio_utility_search_install_legacy_preview_state( $state ) {
	if ( ! lunara_site_studio_utility_search_legacy_preview_state_valid( $state ) ) { return false; }
	$mods = lunara_site_studio_mod_surface_desired_snapshot( $state, lunara_site_studio_utility_search_legacy_spec() );
	foreach ( $mods as $mod => $entry ) { $value = $entry['value']; add_filter( 'theme_mod_' . $mod, static function () use ( $value ) { return $value; }, PHP_INT_MAX ); }
	return true;
}
