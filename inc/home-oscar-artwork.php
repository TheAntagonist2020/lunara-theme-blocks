<?php
/** Homepage-only Oscar artwork. Source posts and their verification are never changed. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Decode the bounded, canonical scalar stored by the existing theme-mod transactions. */
function lunara_home_oscar_artwork_decode( $json ) {
	if ( ! is_string( $json ) || strlen( $json ) > 12000 ) { return false; }
	$object = json_decode( $json );
	if ( ! $object instanceof stdClass ) { return false; }
	$entries = (array) $object;
	if ( count( $entries ) > 48 ) { return false; }
	$normalized = array();
	foreach ( $entries as $id => $entry ) {
		if ( ! preg_match( '/^[1-9][0-9]{0,9}$/D', (string) $id ) || ! $entry instanceof stdClass ) { return false; }
		$value = (array) $entry;
		if ( array( 'image_id', 'fit', 'focal_x', 'focal_y', 'zoom' ) !== array_keys( $value ) || ! is_int( $value['image_id'] ) || $value['image_id'] < 0 || $value['image_id'] > 9999999999 || ! in_array( $value['fit'], array( 'cover', 'full' ), true ) ) { return false; }
		foreach ( array( 'focal_x' => array( 0, 100 ), 'focal_y' => array( 0, 100 ), 'zoom' => array( 100, 112 ) ) as $key => $range ) {
			if ( ! is_int( $value[$key] ) || $value[$key] < $range[0] || $value[$key] > $range[1] ) { return false; }
		}
		$normalized[(int) $id] = $value;
	}
	ksort( $normalized, SORT_NUMERIC );
	return json_encode( (object) $normalized ) === $json ? $normalized : false;
}

function lunara_home_oscar_artwork_overrides( $kind ) {
	$value = lunara_home_oscar_artwork_decode( get_theme_mod( 'lunara_home_oscar_' . $kind . '_artwork_overrides', '{}' ) );
	return false === $value ? array() : $value;
}

/** Full attachment sources are shared by editor metadata and public presentation. */
function lunara_home_oscar_artwork( $kind, $post_id, $use_overrides = true ) {
	$post_id = (int) $post_id;
	$source_id = (int) get_post_thumbnail_id( $post_id );
	$source_url = $source_id && wp_attachment_is_image( $source_id ) ? wp_get_attachment_image_url( $source_id, 'full' ) : '';
	$allowed = true;
	$settings = array( 'image_id' => 0, 'fit' => 'cover', 'focal_x' => 50, 'focal_y' => 50, 'zoom' => 100 );
	if ( 'facts' === $kind ) {
		$held = function_exists( 'lunara_oscar_fact_visual_hold_ids' ) ? array_map( 'intval', lunara_oscar_fact_visual_hold_ids() ) : array();
		$allowed = '1' === (string) get_post_meta( $post_id, '_lunara_fact_visual_verified', true ) && ! in_array( $post_id, $held, true );
		$settings['fit'] = 'archival' === get_post_meta( $post_id, '_lunara_fact_visual_treatment', true ) ? 'full' : 'cover';
		$focus = (string) get_post_meta( $post_id, '_lunara_fact_visual_focus', true );
		$positions = array( 'center' => array( 50, 50 ), 'center-high' => array( 50, 38 ), 'center-low' => array( 50, 58 ), 'left' => array( 38, 50 ), 'right' => array( 62, 50 ), 'left-high' => array( 38, 38 ), 'right-high' => array( 62, 38 ), 'left-low' => array( 38, 58 ), 'right-low' => array( 62, 58 ) );
		if ( isset( $positions[$focus] ) ) { $settings['focal_x'] = $positions[$focus][0]; $settings['focal_y'] = $positions[$focus][1]; }
	}
	$overrides = $use_overrides ? lunara_home_oscar_artwork_overrides( $kind ) : array();
	$override = isset( $overrides[$post_id] );
	if ( $override ) { $settings = $overrides[$post_id]; }
	$image_id = $settings['image_id'] ?: $source_id;
	$url = $settings['image_id'] ? ( wp_attachment_is_image( $image_id ) ? wp_get_attachment_image_url( $image_id, 'full' ) : '' ) : $source_url;
	return array_merge( $settings, array( 'image_id' => $image_id, 'url' => $url ? (string) $url : '', 'allowed' => $allowed, 'has_image' => $allowed && (bool) $url, 'override' => $override, 'source_id' => $source_id, 'source_url' => $source_url ? (string) $source_url : '', 'image_source' => 'facts' === $kind ? ( $allowed ? 'Verified Fact featured image' : 'Fact featured image — public visual not approved' ) : 'Pick featured image' ) );
}

function lunara_home_oscar_artwork_style( $artwork ) {
	return '--lunara-oscar-image-fit:' . ( 'full' === $artwork['fit'] ? 'contain' : 'cover' ) . ';--lunara-oscar-image-position:' . $artwork['focal_x'] . '% ' . $artwork['focal_y'] . '%;--lunara-oscar-image-zoom:' . ( 'full' === $artwork['fit'] ? '1' : (string) ( $artwork['zoom'] / 100 ) ) . ';';
}
