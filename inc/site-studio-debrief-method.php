<?php
/**
 * Debrief Method page presentation in Site Studio.
 *
 * The page's words, section visibility and counts are canonical theme mods
 * described by lunara_debrief_method_settings_spec() (inc/debrief-method.php).
 * This module is only the editor: it validates, saves and restores them through
 * the shared Preview/Apply/History transaction. The pairings, the canon and the
 * recent Debriefs stay owned by the reviews; the page's own block content stays
 * owned by the page editor.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** The public route the preview renders. The template auto-applies to this slug. */
function lunara_site_studio_debrief_method_preview_route() { return '/debrief/'; }

function lunara_site_studio_debrief_method_spec() { return lunara_debrief_method_settings_spec(); }

/** Sections the preview can select, in page order. */
function lunara_site_studio_debrief_method_sections() { return array( 'hero', 'moves', 'why', 'specimen', 'desk', 'canon', 'recent', 'next' ); }

/**
 * Available only while a published page sits at the preview route, so the
 * editor never previews a page readers cannot reach.
 */
function lunara_site_studio_debrief_method_dependency() {
	// Read the page afresh: the public URL helper caches for the request, and the editor must see a just-published page.
	$page = function_exists( 'get_page_by_path' ) ? get_page_by_path( 'debrief', OBJECT, 'page' ) : null;
	$url = $page instanceof WP_Post && 'publish' === $page->post_status && empty( $page->post_password ) ? (string) get_permalink( $page ) : '';
	$expected = home_url( lunara_site_studio_debrief_method_preview_route() );
	$available = '' !== $url && rtrim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' ) === rtrim( (string) wp_parse_url( $expected, PHP_URL_PATH ), '/' );
	return array(
		'available' => $available,
		'reason'    => $available ? '' : 'debrief_page_unavailable',
		'message'   => $available ? '' : __( 'Publish a Page with the slug "debrief" at the top level of the site to preview and edit the Debrief page.', 'lunara-film' ),
	);
}

// Text fields use the Search/404 helpers: they validate multibyte length exactly as the editor counts it.
function lunara_site_studio_debrief_method_state_schema() { return lunara_site_studio_mod_surface_schema( lunara_site_studio_debrief_method_spec() ); }
function lunara_site_studio_debrief_method_read_state() { return lunara_site_studio_utility_recovery_read_state( lunara_site_studio_debrief_method_spec() ); }
function lunara_site_studio_debrief_method_validate_state( $candidate ) { return lunara_site_studio_utility_recovery_validate_state( $candidate, lunara_site_studio_debrief_method_spec(), 'site_studio_debrief_method' ); }
function lunara_site_studio_debrief_method_save_state( $candidate ) { return lunara_site_studio_utility_recovery_save_state( $candidate, 'debrief-method', lunara_site_studio_debrief_method_spec(), lunara_site_studio_debrief_method_sections() ); }
function lunara_site_studio_debrief_method_restore_revision( $id ) { return lunara_site_studio_utility_recovery_restore_revision( $id, 'debrief-method', lunara_site_studio_debrief_method_spec() ); }
function lunara_site_studio_debrief_method_adapter() { return new Lunara_Site_Studio_Theme_Adapter( 'debrief-method', 'theme:debrief-method', array( 'read' => 'lunara_site_studio_debrief_method_read_state', 'validate' => 'lunara_site_studio_debrief_method_validate_state', 'save' => 'lunara_site_studio_debrief_method_save_state', 'restore' => 'lunara_site_studio_debrief_method_restore_revision' ) ); }

/** Validation paths the REST layer may echo back, derived from the spec so they never drift. */
function lunara_site_studio_debrief_method_paths() {
	$paths = array();
	foreach ( lunara_site_studio_debrief_method_spec() as $group => $fields ) {
		$paths[] = $group;
		foreach ( array_keys( $fields ) as $field ) { $paths[] = $group . '.' . $field; }
	}
	return $paths;
}

/** Inspector groups: group => label, open by default, preview sections it controls. */
function lunara_site_studio_debrief_method_groups() {
	return array(
		'hero'     => array( __( 'Opening', 'lunara-film' ), true, array( 'hero' ) ),
		'moves'    => array( __( 'The three moves', 'lunara-film' ), false, array( 'moves' ) ),
		'why'      => array( __( 'Why three', 'lunara-film' ), false, array( 'why' ) ),
		'specimen' => array( __( 'Featured Debrief', 'lunara-film' ), false, array( 'specimen' ) ),
		'desk'     => array( __( 'From the Desk', 'lunara-film' ), false, array( 'desk' ) ),
		'canon'    => array( __( 'The Debrief Canon', 'lunara-film' ), false, array( 'canon' ) ),
		'recent'   => array( __( 'Recent Debriefs', 'lunara-film' ), false, array( 'recent' ) ),
		'next'     => array( __( 'Closing links', 'lunara-film' ), false, array( 'next' ) ),
	);
}

function lunara_site_studio_render_debrief_method_inspector( $state, $revisions ) {
	$spec = lunara_site_studio_debrief_method_spec();
	echo '<p>' . esc_html__( 'Edit the words, sections and counts of the Debrief page. The featured Debrief, the canon and the recent Debriefs update themselves from the reviews.', 'lunara-film' ) . '</p>';
	foreach ( lunara_site_studio_debrief_method_groups() as $group => $meta ) {
		if ( ! isset( $spec[ $group ], $state[ $group ] ) ) { continue; }
		lunara_site_studio_render_details_open( $group, $meta[0], $meta[1], $meta[2] );
		if ( 'desk' === $group ) {
			$page = get_page_by_path( 'debrief', OBJECT, 'page' );
			echo '<p>' . esc_html__( 'The manifesto in this section is the Debrief page’s own content. Write it in the page editor; it appears only when the page has content.', 'lunara-film' ) . '</p>';
			if ( $page instanceof WP_Post && current_user_can( 'edit_post', $page->ID ) ) { echo '<a class="button" data-workspace-navigation href="' . esc_url( admin_url( 'post.php?post=' . $page->ID . '&action=edit' ) ) . '">' . esc_html__( 'Edit the Debrief page content', 'lunara-film' ) . '</a>'; }
		}
		foreach ( $spec[ $group ] as $field => $definition ) { lunara_site_studio_render_control( $group . '.' . $field, $state[ $group ][ $field ], $definition ); }
		lunara_site_studio_render_details_close();
	}
	lunara_site_studio_render_details_open( 'advanced', __( 'Advanced', 'lunara-film' ) );
	echo '<button type="button" class="button" data-action="reset-candidate" disabled>' . esc_html__( 'Reset candidate', 'lunara-film' ) . '</button>';
	echo '<a class="button" data-workspace-navigation href="' . esc_url( admin_url( 'admin.php?page=lunara-site-studio&surface=review-single' ) ) . '">' . esc_html__( 'Review article layout', 'lunara-film' ) . '</a>';
	lunara_site_studio_render_details_close();
	lunara_site_studio_render_revisions( $revisions );
}
