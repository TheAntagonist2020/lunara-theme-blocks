<?php
/** Journal article presentation. Article text, featured art and galleries remain post-owned. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function lunara_site_studio_journal_single_spec() {
	return array(
		'hero' => array(
			'title_size' => array( 'mod' => 'lunara_journal_single_hero_title_size', 'type' => 'int', 'value_scale' => 1, 'default' => 84, 'min' => 48, 'max' => 120, 'label' => __( 'Headline size', 'lunara-film' ), 'help' => __( '84 keeps the standard size. Headlines adapt to the screen width.', 'lunara-film' ) ),
			'image_fit' => array( 'mod' => 'lunara_journal_single_image_fit', 'type' => 'select', 'default' => 'cover', 'allowed' => array( 'cover', 'contain' ), 'label' => __( 'Image fit', 'lunara-film' ) ),
			'image_position_x' => array( 'mod' => 'lunara_journal_single_image_position_x', 'type' => 'int', 'value_scale' => 1, 'default' => 50, 'min' => 0, 'max' => 100, 'label' => __( 'Horizontal focal point', 'lunara-film' ) ),
			'image_position_y' => array( 'mod' => 'lunara_journal_single_image_position_y', 'type' => 'int', 'value_scale' => 1, 'default' => 50, 'min' => 0, 'max' => 100, 'label' => __( 'Vertical focal point', 'lunara-film' ) ),
		),
		'metadata' => array(
			'show_byline' => array( 'mod' => 'lunara_journal_show_byline', 'type' => 'bool', 'default' => true, 'label' => __( 'Show author', 'lunara-film' ) ),
			'show_date' => array( 'mod' => 'lunara_journal_show_date', 'type' => 'bool', 'default' => true, 'label' => __( 'Show publication date', 'lunara-film' ) ),
			'show_reading_time' => array( 'mod' => 'lunara_journal_show_reading_time', 'type' => 'bool', 'default' => false, 'label' => __( 'Show reading time', 'lunara-film' ) ),
		),
	);
}

/** Resolve the newest published article, independent of featured or archive priority. */
function lunara_site_studio_journal_single_preview_article() {
	if ( ! function_exists( 'get_posts' ) || ! function_exists( 'get_permalink' ) ) { return array(); }
	$posts = get_posts( array( 'post_type' => 'journal', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => 1, 'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ), 'ignore_sticky_posts' => true, 'no_found_rows' => true, 'suppress_filters' => true ) );
	if ( ! $posts || ! is_object( $posts[0] ) || empty( $posts[0]->ID ) || 'journal' !== $posts[0]->post_type || 'publish' !== $posts[0]->post_status || ! empty( $posts[0]->post_password ) ) { return array(); }
	$post = $posts[0]; $url = get_permalink( $post->ID ); $home = home_url( '/' );
	if ( ! is_string( $url ) || wp_parse_url( $url, PHP_URL_SCHEME ) !== wp_parse_url( $home, PHP_URL_SCHEME ) || wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( $home, PHP_URL_HOST ) || wp_parse_url( $url, PHP_URL_PORT ) !== wp_parse_url( $home, PHP_URL_PORT ) || wp_parse_url( $url, PHP_URL_USER ) || wp_parse_url( $url, PHP_URL_PASS ) || wp_parse_url( $url, PHP_URL_QUERY ) || wp_parse_url( $url, PHP_URL_FRAGMENT ) ) { return array(); }
	$base = rtrim( (string) wp_parse_url( $home, PHP_URL_PATH ), '/' ); $path = (string) wp_parse_url( $url, PHP_URL_PATH );
	if ( 0 !== strpos( $path, $base . '/journal/' ) ) { return array(); }
	$route = substr( $path, strlen( $base ) );
	if ( ! preg_match( '~^/journal/[a-zA-Z0-9_\-]+/$~D', $route ) ) { return array(); }
	return array( 'id' => (int) $post->ID, 'route' => $route );
}
function lunara_site_studio_journal_single_preview_route() { $article = lunara_site_studio_journal_single_preview_article(); return $article ? $article['route'] : '/journal/'; }
function lunara_site_studio_journal_single_dependency() {
	$available = function_exists( 'post_type_exists' ) && post_type_exists( 'journal' ) && (bool) lunara_site_studio_journal_single_preview_article();
	return array( 'available' => $available, 'reason' => $available ? '' : 'journal_article_unavailable', 'message' => $available ? '' : __( 'Publish a Journal article to preview and edit the shared article layout.', 'lunara-film' ) );
}
function lunara_site_studio_journal_single_keys() { return lunara_site_studio_mod_surface_keys( lunara_site_studio_journal_single_spec() ); }
function lunara_site_studio_journal_single_state_schema() { return lunara_site_studio_mod_surface_schema( lunara_site_studio_journal_single_spec() ); }
function lunara_site_studio_journal_single_read_state() { return lunara_site_studio_mod_surface_read_state( lunara_site_studio_journal_single_spec() ); }
function lunara_site_studio_journal_single_validate_state( $candidate ) { return lunara_site_studio_mod_surface_validate_state( $candidate, lunara_site_studio_journal_single_spec(), 'site_studio_journal_single' ); }
function lunara_site_studio_journal_single_save_state( $candidate ) { return lunara_site_studio_mod_surface_save_state( $candidate, 'journal-single', lunara_site_studio_journal_single_spec(), array( 'hero', 'article', 'gallery' ), 'site_studio_journal_single' ); }
function lunara_site_studio_journal_single_restore_revision( $revision_id ) { return lunara_site_studio_mod_surface_restore_revision( $revision_id, 'journal-single', lunara_site_studio_journal_single_spec(), 'site_studio_journal_single' ); }
function lunara_site_studio_journal_single_adapter() { return new Lunara_Site_Studio_Theme_Adapter( 'journal-single', 'theme:journal-single', array( 'read' => 'lunara_site_studio_journal_single_read_state', 'validate' => 'lunara_site_studio_journal_single_validate_state', 'save' => 'lunara_site_studio_journal_single_save_state', 'restore' => 'lunara_site_studio_journal_single_restore_revision' ) ); }

/** Remove the old writer registration without deleting any saved setting. */
function lunara_site_studio_journal_single_retire_customizer( $customizer ) {
	$customizer->remove_control( 'lunara_journal_single_hero_title_size' );
	$customizer->remove_setting( 'lunara_journal_single_hero_title_size' );
}
add_action( 'customize_register', 'lunara_site_studio_journal_single_retire_customizer', 100 );
