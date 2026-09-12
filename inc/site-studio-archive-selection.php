<?php
/** Shared archive selection rules; the existing archive providers own persistence. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function lunara_archive_selection_enabled( $config ) {
	return is_array( $config ) && isset( $config['selection_version'] ) && 1 === $config['selection_version'];
}

/** Accept canonical nonnegative IDs without coercing floats, booleans or negative input. */
function lunara_archive_selection_id( $value, $allow_zero = true ) {
	if ( ! is_int( $value ) && ! ( is_string( $value ) && preg_match( '/^(?:0|[1-9][0-9]*)$/D', $value ) ) ) { return false; }
	$id = filter_var( $value, FILTER_VALIDATE_INT );
	return false !== $id && $id >= ( $allow_zero ? 0 : 1 ) ? $id : false;
}

/** Validate the retained choices, not their transient publication status. */
function lunara_archive_selection_validate( $config, $kind ) {
	$prefix = 'lunara_' . $kind . '_archive_studio_';
	$error = $kind . '_archive_';
	$modes = 'journal' === $kind ? array( 'shared', 'automatic', 'manual' ) : array( 'automatic', 'manual' );
	if ( ! in_array( $config['lead_mode'], $modes, true ) ) { return new WP_Error( $error . 'lead_mode_invalid' ); }
	$id = lunara_archive_selection_id( $config['lead_id'] );
	if ( false === $id || ( 'manual' === $config['lead_mode'] && ! call_user_func( $prefix . 'validate_post_id', $id ) ) ) { return new WP_Error( $error . 'lead_invalid' ); }
	$config['lead_id'] = $id;
	if ( ! in_array( $config['lane_mode'], array( 'query', 'curated' ), true ) ) { return new WP_Error( $error . 'lane_mode_invalid' ); }
	if ( ! is_array( $config['curated_ids'] ) || count( $config['curated_ids'] ) > 24 || ( $config['curated_ids'] && array_keys( $config['curated_ids'] ) !== range( 0, count( $config['curated_ids'] ) - 1 ) ) ) { return new WP_Error( $error . 'curated_count_invalid' ); }
	$ids = array();
	foreach ( $config['curated_ids'] as $value ) {
		$id = lunara_archive_selection_id( $value, false );
		if ( false === $id ) { return new WP_Error( $error . 'curated_post_invalid' ); }
		if ( in_array( $id, $ids, true ) ) { return new WP_Error( $error . 'curated_duplicate' ); }
		$ids[] = $id;
	}
	$config['curated_ids'] = $ids;
	return $config;
}

/** Fixed messages are redacted; missing/private selections never disclose their titles. */
function lunara_archive_selection_warnings( $config, $kind ) {
	$validate = 'lunara_' . $kind . '_archive_studio_validate_post_id';
	$warnings = array();
	if ( 'shared' === $config['lead_mode'] ) { $warnings[] = 'legacy_shared_lead'; }
	if ( 'manual' === $config['lead_mode'] && ! call_user_func( $validate, $config['lead_id'] ) ) { $warnings[] = 'manual_lead_unavailable'; }
	elseif ( ! empty( $config['lead_id'] ) && ! call_user_func( $validate, $config['lead_id'] ) ) { $warnings[] = 'retained_lead_unavailable'; }
	$eligible = array_filter( (array) $config['curated_ids'], $validate );
	if ( count( $eligible ) !== count( (array) $config['curated_ids'] ) ) { $warnings[] = 'curated_selection_unavailable'; }
	if ( 'curated' === $config['lane_mode'] && ! $eligible ) { $warnings[] = 'curated_selection_empty'; }
	return $warnings;
}

/** Missing active leads recover to Automatic while keeping the remembered selection. */
function lunara_archive_selection_recover( $config, $kind ) {
	if ( 'manual' === $config['lead_mode'] && ! call_user_func( 'lunara_' . $kind . '_archive_studio_validate_post_id', $config['lead_id'] ) ) { $config['lead_mode'] = 'automatic'; }
	return $config;
}

/** Keep a remembered missing choice present when the older form submits. */
function lunara_archive_selection_unavailable_lead_option( $config, $kind ) {
	$id = absint( $config['lead_id'] );
	return $id && ! call_user_func( 'lunara_' . $kind . '_archive_studio_validate_post_id', $id )
		? '<option value="' . esc_attr( $id ) . '" selected="selected">' . esc_html( 'Unavailable selection #' . $id ) . '</option>' : '';
}

/** The Classic list shares retained IDs without revealing unpublished metadata. */
function lunara_archive_selection_classic_rows( $config, $kind ) {
	$html = '';
	foreach ( $config['curated_ids'] as $id ) {
		$available = call_user_func( 'lunara_' . $kind . '_archive_studio_validate_post_id', $id );
		$label = $available ? '#' . $id . ' — ' . get_the_title( get_post( $id ) ) : 'Unavailable selection #' . $id;
		$html .= '<li data-lunara-journal-curated-item data-post-id="' . esc_attr( $id ) . '"><span>' . esc_html( $label ) . '</span><input type="hidden" name="lunara_' . esc_attr( $kind ) . '_archive_curated_ids[]" value="' . esc_attr( $id ) . '" /><span class="lunara-control-desk-actions"><button type="button" class="button button-small" data-lunara-journal-curated-move="up">Up</button><button type="button" class="button button-small" data-lunara-journal-curated-move="down">Down</button><button type="button" class="button button-small" data-lunara-journal-curated-remove>Remove</button></span></li>';
	}
	return $html;
}

function lunara_site_studio_archive_selection_csv( $value, $limit ) {
	if ( null === $value || '' === $value ) { return array(); }
	if ( ! is_string( $value ) || strlen( $value ) > 600 ) { return false; }
	$values = explode( ',', $value );
	if ( count( $values ) > $limit ) { return false; }
	$ids = array();
	foreach ( $values as $value ) { $id = lunara_archive_selection_id( $value, false ); if ( false === $id || in_array( $id, $ids, true ) ) { return false; } $ids[] = $id; }
	return $ids;
}

/** Private metadata uses the same canonical search and lead helpers as the public provider. */
function lunara_site_studio_archive_selection_items( $request ) {
	$permission = lunara_site_studio_rest_route_permission( $request );
	if ( is_wp_error( $permission ) ) { return $permission; }
	$surface = $request->get_param( 'surface' );
	if ( ! in_array( $surface, array( 'reviews-archive', 'journal-archive' ), true ) ) { return lunara_site_studio_rest_error( 'site_studio_surface_not_found', 'Unknown archive.', 404 ); }
	$kind = 'reviews-archive' === $surface ? 'reviews' : 'journal';
	$prefix = 'lunara_' . $kind . '_archive_studio_';
	$config = call_user_func( $prefix . 'get_public_config', false );
	$ids = lunara_site_studio_archive_selection_csv( $request->get_param( 'ids' ), 25 );
	$curated = $request->get_param( 'curated_ids' );
	$curated = null === $curated ? $config['curated_ids'] : lunara_site_studio_archive_selection_csv( $curated, 24 );
	$q = $request->get_param( 'q' );
	if ( false === $ids || false === $curated || ( null !== $q && ( ! is_string( $q ) || strlen( $q ) > 400 ) ) ) { return lunara_site_studio_rest_error( 'site_studio_items_invalid', 'Use bounded story IDs and search text.', 400 ); }
	$q = null === $q ? '' : sanitize_text_field( $q );
	$q = function_exists( 'mb_substr' ) ? mb_substr( $q, 0, 100 ) : substr( $q, 0, 100 );
	foreach ( array( 'selection_version', 'lead_mode', 'lead_id', 'lane_mode' ) as $key ) { $value = $request->get_param( $key ); if ( null !== $value ) { $config[ $key ] = $value; } }
	$version = lunara_archive_selection_id( $config['selection_version'] );
	$lead_id = lunara_archive_selection_id( $config['lead_id'] );
	$modes = 'journal' === $kind ? array( 'shared', 'automatic', 'manual' ) : array( 'automatic', 'manual' );
	if ( false === $version || $version > 1 || false === $lead_id || ! in_array( $config['lead_mode'], $modes, true ) || ! in_array( $config['lane_mode'], array( 'query', 'curated' ), true ) ) { return lunara_site_studio_rest_error( 'site_studio_items_invalid', 'Choose supported story controls.', 400 ); }
	$config['selection_version'] = $version; $config['lead_id'] = $lead_id; $config['curated_ids'] = $curated;
	$effective = 'manual' === $config['lead_mode'] ? call_user_func( $prefix . 'validate_post_id', $lead_id ) : call_user_func( $prefix . 'get_lead_id', $config );
	// Legacy Reviews is query-first: an older curated story may currently lead.
	if ( ! $version && 'reviews' === $kind && 'automatic' === $config['lead_mode'] && 'curated' === $config['lane_mode'] ) { $eligible = array_values( array_filter( $curated, $prefix . 'validate_post_id' ) ); if ( $eligible ) { $effective = (int) $eligible[0]; } }
	$priority = $effective ? array( $effective ) : array();
	if ( 'curated' === $config['lane_mode'] ) { foreach ( $curated as $id ) { if ( call_user_func( $prefix . 'validate_post_id', $id ) && ! in_array( $id, $priority, true ) ) { $priority[] = $id; } } }
	$results = array();
	foreach ( call_user_func( $prefix . 'search_posts', $q, 20 ) as $post ) { if ( call_user_func( $prefix . 'validate_post_id', $post->ID ) ) { $results[] = (int) $post->ID; } }
	$items = array();
	foreach ( array_unique( array_merge( $ids, $results, $curated, $effective ? array( $effective ) : array(), $lead_id ? array( $lead_id ) : array() ) ) as $id ) {
		$available = (bool) call_user_func( $prefix . 'validate_post_id', $id );
		$image = '';
		if ( $available ) {
			if ( 'reviews' === $kind && function_exists( 'lunara_get_review_card_image_data' ) ) {
				$art = lunara_get_review_card_image_data( $id );
				$image = isset( $art['url'] ) ? $art['url'] : '';
			} elseif ( function_exists( 'get_the_post_thumbnail_url' ) ) {
				$image = get_the_post_thumbnail_url( $id, 'full' );
			}
		}
		$items[] = array( 'id' => (int) $id, 'title' => $available ? (string) get_the_title( $id ) : 'Unavailable selection #' . $id, 'available' => $available, 'image_url' => $image ? esc_url_raw( $image ) : '', 'published_date' => $available ? (string) get_the_date( 'M j, Y', $id ) : '' );
	}
	return rest_ensure_response( array( 'items' => $items, 'results' => $results, 'lead_id' => (int) $effective, 'priority_ids' => $priority, 'selection_version' => $version, 'warnings' => lunara_archive_selection_warnings( $config, $kind ) ) );
}

add_action( 'rest_api_init', static function () {
	register_rest_route( 'lunara-site-studio/v1', '/surfaces/(?P<surface>reviews-archive|journal-archive)/items', array( 'methods' => 'GET', 'callback' => 'lunara_site_studio_archive_selection_items', 'permission_callback' => 'lunara_site_studio_rest_route_permission' ) );
} );
