<?php
/** Method presentation helpers and read-only editor metadata. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function lunara_site_studio_method_backdrop_defaults() {
	return array( 'hidden' => false, 'focal_x' => 50, 'focal_y' => 26, 'fit' => 'cover', 'zoom' => 100 );
}

function lunara_site_studio_method_backdrop_valid( $value ) {
	if ( ! is_array( $value ) || array_keys( lunara_site_studio_method_backdrop_defaults() ) !== array_keys( $value ) || ! is_bool( $value['hidden'] ) || ! in_array( $value['fit'], array( 'cover', 'full' ), true ) ) { return false; }
	foreach ( array( 'focal_x' => array( 0, 100 ), 'focal_y' => array( 0, 100 ), 'zoom' => array( 100, 112 ) ) as $key => $range ) {
		if ( ! is_int( $value[ $key ] ) || $value[ $key ] < $range[0] || $value[ $key ] > $range[1] ) { return false; }
	}
	return true;
}

function lunara_method_backdrop_settings() {
	$value = get_theme_mod( 'lunara_home_pairing_desk_backdrop', null );
	return lunara_site_studio_method_backdrop_valid( $value ) ? $value : lunara_site_studio_method_backdrop_defaults();
}

function lunara_method_review_available( $id ) {
	$post = $id ? get_post( $id ) : null;
	return $post && 'review' === $post->post_type && 'publish' === $post->post_status && empty( $post->post_password );
}

/** Keep the historical bounded pairing eligibility rule, explicitly date ordered. */
function lunara_method_automatic_review_id() {
	$ids = get_posts( array( 'post_type' => 'review', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => 20, 'fields' => 'ids', 'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ), 'no_found_rows' => true, 'update_post_term_cache' => false ) );
	if ( ! $ids ) { return 0; }
	update_meta_cache( 'post', array_map( 'intval', $ids ) );
	foreach ( $ids as $id ) {
		if ( ! lunara_method_review_available( $id ) ) { continue; }
		foreach ( array( '_lunara_theme_echo', '_lunara_counter_program', '_lunara_career_context', '_lunara_craft_mirror' ) as $key ) {
			if ( '' !== trim( (string) get_post_meta( $id, $key, true ) ) ) { return (int) $id; }
		}
	}
	return 0;
}

/** Use the same canonical Review hero art as the public Method renderer. */
function lunara_method_source_art( $review_id ) {
	$url = function_exists( 'lunara_get_review_hero_image_url' ) ? trim( (string) lunara_get_review_hero_image_url( $review_id ) ) : '';
	if ( $url && function_exists( 'lunara_rightsize_backdrop_url' ) ) { $url = lunara_rightsize_backdrop_url( $url ); }
	return array( 'url' => $url, 'label' => 'Review hero artwork' );
}

function lunara_method_backdrop_url( $review_id, $image_id, $settings, $legacy = false ) {
	if ( $settings['hidden'] ) { return ''; }
	if ( $image_id ) {
		$url = wp_attachment_is_image( $image_id ) ? (string) wp_get_attachment_image_url( $image_id, 'full' ) : '';
		if ( $url || ! $legacy ) { return $url; }
	}
	return lunara_method_source_art( $review_id )['url'];
}

function lunara_method_backdrop_style( $settings ) {
	return '--lunara-method-x:' . $settings['focal_x'] . '%;--lunara-method-y:' . $settings['focal_y'] . '%;--lunara-method-fit:' . ( 'full' === $settings['fit'] ? 'contain' : 'cover' ) . ';--lunara-method-zoom:' . ( 'full' === $settings['fit'] ? '1' : (string) ( $settings['zoom'] / 100 ) ) . ';';
}

/** Unpublished/unavailable titles and their art never enter editor responses. */
function lunara_site_studio_method_item( $id ) {
	$available = lunara_method_review_available( $id );
	$art = $available ? lunara_method_source_art( $id ) : array( 'url' => '', 'label' => '' );
	return array( 'id' => (int) $id, 'available' => (bool) $available, 'title' => $available ? (string) get_the_title( $id ) : 'Unavailable Review', 'date_label' => $available ? (string) get_the_date( 'M j, Y', $id ) : '', 'image_url' => $art['url'], 'image_source' => $art['label'] );
}

function lunara_site_studio_method_search( $request ) {
	$posts = get_posts( array( 'post_type' => 'review', 'post_status' => 'publish', 'has_password' => false, 's' => substr( sanitize_text_field( (string) $request->get_param( 'search' ) ), 0, 160 ), 'posts_per_page' => 20, 'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ), 'no_found_rows' => true ) );
	$items = array();
	foreach ( $posts as $post ) { if ( lunara_method_review_available( $post->ID ) ) { $items[] = lunara_site_studio_method_item( $post->ID ); } }
	return new WP_REST_Response( array( 'items' => $items ), 200 );
}

function lunara_site_studio_method_metadata( $request ) {
	$mode = $request->get_param( 'mode' );
	if ( ! in_array( $mode, array( 'automatic', 'manual' ), true ) ) { return new WP_REST_Response( array( 'message' => 'Choose a valid mode.' ), 400 ); }
	$ids = array();
	foreach ( array( 'review_id', 'image_id' ) as $key ) {
		$value = $request->get_param( $key );
		if ( ! is_scalar( $value ) || ! preg_match( '/^(0|[1-9][0-9]*)$/D', (string) $value ) || (float) $value > PHP_INT_MAX ) { return new WP_REST_Response( array( 'message' => 'Choose a valid image or Review ID.' ), 400 ); }
		$ids[ $key ] = (int) $value;
	}
	// Automatic deliberately never inspects the retained manual selection.
	$id = 'automatic' === $mode ? lunara_method_automatic_review_id() : $ids['review_id'];
	$image = $ids['image_id'] && wp_attachment_is_image( $ids['image_id'] ) ? wp_get_attachment_image_url( $ids['image_id'], 'full' ) : '';
	return new WP_REST_Response( array( 'item' => $id ? lunara_site_studio_method_item( $id ) : null, 'image_url' => $image ? (string) $image : '' ), 200 );
}

function lunara_site_studio_register_method_routes() {
	foreach ( array( 'search', 'metadata' ) as $endpoint ) {
		register_rest_route( 'lunara-site-studio/v1', '/surfaces/(?P<surface>lunara-method)/' . $endpoint, array( 'methods' => 'GET', 'callback' => 'lunara_site_studio_method_' . $endpoint, 'permission_callback' => 'lunara_site_studio_rest_route_permission' ) );
	}
}
add_action( 'rest_api_init', 'lunara_site_studio_register_method_routes' );
