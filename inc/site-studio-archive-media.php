<?php
/** Private archive artwork metadata. Existing archive providers own all saved state. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Denials and successful metadata reads receive the same private cache policy. */
function lunara_site_studio_archive_media_permission( $request ) {
	lunara_site_studio_send_private_no_store();
	if ( ! is_object( $request ) || ! method_exists( $request, 'get_param' ) || ! method_exists( $request, 'get_header' ) ) {
		return lunara_site_studio_rest_error( 'site_studio_media_invalid', 'Use a valid artwork request.', 400 );
	}
	return lunara_site_studio_rest_route_permission( $request );
}

/** Return only requested, readable image derivatives; unavailable references stay redacted. */
function lunara_site_studio_archive_media( $request ) {
	$permission = lunara_site_studio_archive_media_permission( $request );
	if ( is_wp_error( $permission ) ) { return $permission; }
	$surface = $request->get_param( 'surface' );
	if ( ! in_array( $surface, array( 'reviews-archive', 'journal-archive' ), true ) ) {
		return lunara_site_studio_rest_error( 'site_studio_surface_not_found', 'Unknown archive.', 404 );
	}
	$ids = lunara_site_studio_archive_selection_csv( $request->get_param( 'image_ids' ), 15 );
	if ( false === $ids || ( $ids && max( $ids ) > 9999999999 ) ) {
		return lunara_site_studio_rest_error( 'site_studio_media_invalid', 'Choose up to fifteen distinct image IDs.', 400 );
	}
	$prefix = 'reviews-archive' === $surface ? 'lunara_reviews_archive_studio_' : 'lunara_journal_archive_studio_';
	$images = array();
	foreach ( $ids as $id ) {
		$image = array( 'id' => $id, 'available' => false, 'url' => '', 'width' => 0, 'height' => 0, 'default_alt' => '' );
		$attachment = get_post( $id );
		if ( is_object( $attachment ) && 'attachment' === $attachment->post_type
			&& in_array( $attachment->post_status, array( 'inherit', 'publish' ), true )
			&& current_user_can( 'read_post', $id ) && call_user_func( $prefix . 'validate_attachment_id', $id ) ) {
			$source = wp_get_attachment_image_src( $id, 'lunara-hero-spotlight' );
			if ( is_array( $source ) && isset( $source[0], $source[1], $source[2] ) && is_string( $source[0] ) && is_numeric( $source[1] ) && is_numeric( $source[2] ) ) {
				$url = call_user_func( $prefix . 'safe_https_url', $source[0], false );
				$width = absint( $source[1] ); $height = absint( $source[2] );
				if ( $url && $width > 0 && $height > 0 ) {
					$image = array( 'id' => $id, 'available' => true, 'url' => $url, 'width' => $width, 'height' => $height,
						'default_alt' => call_user_func( $prefix . 'text', get_post_meta( $id, '_wp_attachment_image_alt', true ), 180 ) );
				}
			}
		}
		$images[] = $image;
	}
	return new WP_REST_Response( array( 'images' => $images ), 200, array(
		'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
		'X-Robots-Tag' => 'noindex, nofollow', 'Referrer-Policy' => 'no-referrer',
	) );
}

add_action( 'rest_api_init', static function () {
	register_rest_route( 'lunara-site-studio/v1', '/surfaces/(?P<surface>reviews-archive|journal-archive)/media', array(
		'methods' => 'GET', 'callback' => 'lunara_site_studio_archive_media', 'permission_callback' => 'lunara_site_studio_archive_media_permission',
	) );
} );
