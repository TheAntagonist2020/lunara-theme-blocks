<?php
/** Page-focused navigation for the shared Site Studio workspace. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Canonical surface IDs remain unchanged; these groups only organize navigation. */
function lunara_site_studio_page_groups() {
	return array(
		'home' => array( 'label' => __( 'Home', 'lunara-film' ), 'surface' => 'homepage-structure', 'editors' => array(
			'homepage-structure' => __( 'Page layout', 'lunara-film' ),
			'hero-carousel' => __( 'Hero stories', 'lunara-film' ),
			'journal-carousel' => __( 'Journal stories', 'lunara-film' ),
			'lunara-method' => __( 'Lunara Method', 'lunara-film' ),
			'home-oscar-picks' => __( 'Oscar Picks', 'lunara-film' ),
			'home-oscar-facts' => __( 'Oscar Facts', 'lunara-film' ),
		) ),
		'reviews' => array( 'label' => __( 'Reviews', 'lunara-film' ), 'surface' => 'reviews-archive', 'editors' => array(
			'reviews-archive' => __( 'Reviews page', 'lunara-film' ),
			'review-single' => __( 'Review article layout', 'lunara-film' ),
		) ),
		'journal' => array( 'label' => __( 'Journal', 'lunara-film' ), 'surface' => 'journal-archive', 'editors' => array(
			'journal-archive' => __( 'Journal page', 'lunara-film' ),
		) ),
		'oscars' => array( 'label' => __( 'Oscars', 'lunara-film' ), 'surface' => 'oscars-portal', 'editors' => array(
			'oscars-portal' => __( 'Oscars page', 'lunara-film' ),
			'oscars-ledger' => __( 'Ledger layouts', 'lunara-film' ),
		) ),
	);
}

/** Return a page family only for a known surface; unrelated tools remain independent. */
function lunara_site_studio_page_for_surface( $surface_id ) {
	foreach ( lunara_site_studio_page_groups() as $id => $group ) {
		if ( isset( $group['editors'][ $surface_id ] ) ) { return $id; }
	}
	return '';
}

/** Resolve only registered, capability-authorized local destinations. */
function lunara_site_studio_navigation_destination( $surface_id, $surfaces ) {
	if ( ! isset( $surfaces[ $surface_id ] ) || ! current_user_can( $surfaces[ $surface_id ]['capability'] ) ) { return array(); }
	$url = lunara_site_studio_safe_admin_destination( lunara_site_studio_admin_url( $surface_id ) );
	if ( ! $url ) { return array(); }
	$availability = lunara_site_studio_surface_availability( $surfaces[ $surface_id ] );
	return array( 'url' => $url, 'available' => ! empty( $availability['available'] ) );
}

function lunara_site_studio_render_page_navigation( $surfaces, $active_id ) {
	$groups = lunara_site_studio_page_groups();
	$active_page = lunara_site_studio_page_for_surface( $active_id );
	echo '<nav class="lunara-site-studio-page-nav" data-studio-page-navigation aria-label="' . esc_attr__( 'Site pages', 'lunara-film' ) . '">';
	foreach ( $groups as $id => $group ) {
		$destination = lunara_site_studio_navigation_destination( $group['surface'], $surfaces );
		if ( ! $destination ) { continue; }
		echo '<a data-workspace-navigation data-studio-page="' . esc_attr( $id ) . '" href="' . esc_url( $destination['url'] ) . '"' . ( $id === $active_page ? ' aria-current="page"' : '' ) . '>' . esc_html( $group['label'] );
		if ( ! $destination['available'] ) { echo '<small>' . esc_html__( 'Unavailable', 'lunara-film' ) . '</small>'; }
		echo '</a>';
	}
	echo '</nav>';
	if ( ! isset( $groups[ $active_page ] ) ) { return; }
	echo '<nav class="lunara-site-studio-context-nav" data-studio-context-navigation aria-label="' . esc_attr__( 'Page editors', 'lunara-film' ) . '">';
	foreach ( $groups[ $active_page ]['editors'] as $id => $label ) {
		$destination = lunara_site_studio_navigation_destination( $id, $surfaces );
		if ( ! $destination ) { continue; }
		echo '<a data-workspace-navigation data-studio-editor="' . esc_attr( $id ) . '" href="' . esc_url( $destination['url'] ) . '"' . ( $id === $active_id ? ' aria-current="page"' : '' ) . '>' . esc_html( $label );
		if ( ! $destination['available'] ) { echo '<small>' . esc_html__( 'Unavailable', 'lunara-film' ) . '</small>'; }
		echo '</a>';
	}
	echo '</nav>';
}

/** Section composition and section content share a direct, guarded path. */
function lunara_site_studio_render_home_section_editor( $section ) {
	$editors = array( 'hero' => 'hero-carousel', 'dispatch' => 'journal-carousel', 'pairing-desk' => 'lunara-method', 'oscar-picks' => 'home-oscar-picks', 'oscar-facts' => 'home-oscar-facts' );
	if ( ! isset( $editors[ $section ] ) ) { return; }
	$destination = lunara_site_studio_navigation_destination( $editors[ $section ], lunara_site_studio_authorized_surfaces() );
	if ( ! $destination ) { return; }
	$groups = lunara_site_studio_page_groups();
	$label = $groups['home']['editors'][ $editors[ $section ] ];
	echo '<a class="lunara-site-studio-section-editor" data-workspace-navigation data-home-section-editor="' . esc_attr( $section ) . '" href="' . esc_url( $destination['url'] ) . '">' . esc_html( sprintf( __( 'Edit %s', 'lunara-film' ), $label ) );
	if ( ! $destination['available'] ) { echo '<small>' . esc_html__( 'Unavailable', 'lunara-film' ) . '</small>'; }
	echo '</a>';
}
