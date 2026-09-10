<?php
/** Homepage Oscar lineups in the shared, private Preview / Apply workspace. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function lunara_site_studio_home_oscars_owns_mod( $key ) {
	return 1 === preg_match( '/^lunara_home_oscar_(picks|facts)_/', (string) $key );
}

function lunara_site_studio_home_oscars_spec( $kind ) {
	$picks = 'picks' === $kind;
	$prefix = 'lunara_home_oscar_' . $kind . '_';
	$year = function_exists( 'lunara_home_oscar_picks_ceremony_year' ) ? lunara_home_oscar_picks_ceremony_year() : (int) gmdate( 'Y' ) + 1;
	$ordinal = function_exists( 'lunara_oscar_ceremony_ordinal_from_year' ) ? lunara_oscar_ceremony_ordinal_from_year( $year ) : (string) ( $year - 1928 );
	$spec = array(
		'copy' => array(
			'kicker' => array( 'mod' => $prefix . 'kicker', 'type' => 'text', 'default' => $picks ? 'Lunara Forecast' : 'Did you know?', 'max_length' => 160 ),
			'heading' => array( 'mod' => $prefix . 'heading', 'type' => 'text', 'default' => $picks ? 'Road to the ' . $ordinal . ' Academy Awards' : 'Records, firsts, and Oscar arguments that still live on', 'max_length' => 300 ),
			'summary' => array( 'mod' => $prefix . 'summary', 'type' => 'text', 'default' => $picks ? 'The board before the race hardens. Early forecasts move as films screen, campaigns take shape, and the season reveals itself.' : '', 'max_length' => 1200 ),
			'cta_text' => array( 'mod' => $prefix . 'cta_text', 'type' => 'text', 'default' => $picks ? 'Open the awards-season board' : 'Explore the full Lunara Oscar Ledger', 'max_length' => 160 ),
		),
		'selection' => array(
			'mode' => array( 'mod' => $prefix . 'selection_mode', 'type' => 'select', 'default' => 'legacy', 'allowed' => array( 'legacy', 'automatic', 'manual' ) ),
			'ids' => array( 'mod' => $prefix . 'selection_ids', 'type' => 'text', 'default' => $picks ? implode( ',', lunara_site_studio_home_oscars_ids( get_theme_mod( $prefix . 'manual_order', '' ) ) ) : '', 'max_length' => 1200 ),
		),
		'presentation' => array(
			'count' => array( 'mod' => $prefix . 'count', 'type' => 'int', 'default' => $picks ? 12 : 8, 'min' => 1, 'max' => 16 ),
			'autoplay_interval' => array( 'mod' => $prefix . 'autoplay_interval', 'type' => 'int', 'default' => 6500, 'min' => 0, 'max' => 12000 ),
			'density' => array( 'mod' => $prefix . 'density', 'type' => 'select', 'default' => 'editorial', 'allowed' => array( 'compact', 'editorial', 'showcase' ) ),
			'card_min_height' => array( 'mod' => $prefix . 'card_min_height', 'type' => 'int', 'default' => $picks ? 520 : 390, 'min' => $picks ? 380 : 300, 'max' => $picks ? 720 : 540 ),
		),
	);
	if ( $picks ) { $spec['selection']['ceremony_year'] = array( 'mod' => $prefix . 'ceremony_year', 'type' => 'int', 'default' => $year, 'min' => 1929, 'max' => 2100 ); }
	return $spec;
}

function lunara_site_studio_home_oscars_ids( $value ) {
	if ( ! is_string( $value ) || '' === $value ) { return array(); }
	$ids = array();
	foreach ( preg_split( '/[\s,]+/', $value ) as $id ) {
		if ( preg_match( '/^[1-9][0-9]{0,9}$/D', $id ) ) { $ids[] = (int) $id; }
	}
	return array_slice( array_values( array_unique( $ids ) ), 0, 48 );
}

function lunara_site_studio_home_oscars_validate( $state, $kind ) {
	$state = lunara_site_studio_mod_surface_validate_state( $state, lunara_site_studio_home_oscars_spec( $kind ), 'site_studio_home_oscars' );
	if ( is_wp_error( $state ) ) { return $state; }
	if ( implode( ',', lunara_site_studio_home_oscars_ids( $state['selection']['ids'] ) ) !== $state['selection']['ids'] ) {
		return new WP_Error( 'site_studio_home_oscars_ids', 'Use the lineup controls to select up to 48 unique items.', array( 'fields' => array( 'selection.ids' => 'The saved lineup is invalid.' ) ) );
	}
	return $state;
}

function lunara_site_studio_home_oscars_adapter( $kind ) {
	$surface = 'home-oscar-' . $kind;
	return new Lunara_Site_Studio_Theme_Adapter( $surface, 'theme:' . $surface, array(
		'read' => static function () use ( $kind ) { return lunara_site_studio_mod_surface_read_state( lunara_site_studio_home_oscars_spec( $kind ) ); },
		'validate' => static function ( $state ) use ( $kind ) { return lunara_site_studio_home_oscars_validate( $state, $kind ); },
		'save' => static function ( $state ) use ( $kind, $surface ) {
			$state = lunara_site_studio_home_oscars_validate( $state, $kind );
			return is_wp_error( $state ) ? $state : lunara_site_studio_mod_surface_save_state( $state, $surface, lunara_site_studio_home_oscars_spec( $kind ), array( 'oscar-' . $kind ), 'site_studio_home_oscars' );
		},
		'restore' => static function ( $id ) use ( $kind, $surface ) { return lunara_site_studio_mod_surface_restore_revision( $id, $surface, lunara_site_studio_home_oscars_spec( $kind ), 'site_studio_home_oscars' ); },
	) );
}
function lunara_site_studio_home_oscar_picks_adapter() { return lunara_site_studio_home_oscars_adapter( 'picks' ); }
function lunara_site_studio_home_oscar_facts_adapter() { return lunara_site_studio_home_oscars_adapter( 'facts' ); }
function lunara_site_studio_home_oscar_picks_spec() { return lunara_site_studio_home_oscars_spec( 'picks' ); }
function lunara_site_studio_home_oscar_facts_spec() { return lunara_site_studio_home_oscars_spec( 'facts' ); }
function lunara_site_studio_home_oscar_picks_schema() { return lunara_site_studio_mod_surface_schema( lunara_site_studio_home_oscar_picks_spec() ); }
function lunara_site_studio_home_oscar_facts_schema() { return lunara_site_studio_mod_surface_schema( lunara_site_studio_home_oscar_facts_spec() ); }
function lunara_site_studio_home_oscar_picks_validate( $state ) { return lunara_site_studio_home_oscars_validate( $state, 'picks' ); }
function lunara_site_studio_home_oscar_facts_validate( $state ) { return lunara_site_studio_home_oscars_validate( $state, 'facts' ); }

add_filter( 'lunara_site_studio_surfaces', static function ( $surfaces ) {
	foreach ( array( 'picks' => 'Homepage Oscar Picks', 'facts' => 'Homepage Oscar Facts' ) as $kind => $label ) {
		$id = 'home-oscar-' . $kind;
		$surfaces[ $id ] = array(
			'id' => $id, 'group' => 'Homepage', 'label' => $label, 'description' => 'Arrange the lineup, edit presentation, and preview desktop or mobile before Apply.',
			'aliases' => array( 'oscars', $kind, 'carousel', 'lineup' ), 'owner' => 'theme:' . $id, 'kind' => 'presentation', 'capability' => 'edit_theme_options',
			'supports_preview' => true, 'preview_route' => '/', 'preview_query_arg' => 'lunara_home_oscar_' . $kind . '_preview',
			'adapter_factory' => 'lunara_site_studio_home_oscar_' . $kind . '_adapter', 'state_schema_callback' => 'lunara_site_studio_home_oscar_' . $kind . '_schema',
			'admin_url' => 'admin.php?page=lunara-site-studio&surface=' . $id, 'classic_url' => 'edit.php?post_type=' . ( 'picks' === $kind ? 'lunara_oscar_pick' : 'oscar_fact' ),
			'dependency_callback' => 'lunara_site_studio_dependency_available', 'status_callback' => 'lunara_site_studio_status_ready', 'danger_level' => 'none', 'sections' => array( 'oscar-' . $kind ),
		);
	}
	return $surfaces;
} );

/** Published lineups and editor availability use the same eligibility rule. */
function lunara_home_oscars_item_available( $post, $kind, $year = 0 ) {
	return $post instanceof WP_Post && 'publish' === $post->post_status && empty( $post->post_password )
		&& ( 'picks' === $kind ? 'lunara_oscar_pick' : 'oscar_fact' ) === $post->post_type
		&& ( 'picks' !== $kind || ! $year || $year === (int) get_post_meta( $post->ID, '_lunara_pick_ceremony_year', true ) );
}

/** Only the presentation renderer adopts selection; stored content is untouched. */
function lunara_home_oscars_selection( $kind, $year = 0 ) {
	$mode = get_theme_mod( 'lunara_home_oscar_' . $kind . '_selection_mode', 'legacy' );
	$ids = array();
	if ( 'manual' === $mode ) {
		foreach ( lunara_site_studio_home_oscars_ids( get_theme_mod( 'lunara_home_oscar_' . $kind . '_selection_ids', '' ) ) as $id ) {
			if ( lunara_home_oscars_item_available( get_post( $id ), $kind, $year ) ) { $ids[] = $id; }
		}
	}
	return array( 'mode' => $mode, 'ids' => $ids );
}

function lunara_site_studio_home_oscars_items( $request ) {
	$kind = 'home-oscar-picks' === $request->get_param( 'surface' ) ? 'picks' : 'facts';
	$ids = lunara_site_studio_carousel_request_ids( $request, 'ids' );
	if ( is_wp_error( $ids ) ) { return $ids; }
	$search = $request->get_param( 'search' );
	$year = $request->get_param( 'year' );
	if ( null !== $search && ( ! is_string( $search ) || strlen( $search ) > 200 ) || null !== $year && ( ! is_scalar( $year ) || ! ctype_digit( (string) $year ) || (int) $year < 1929 || (int) $year > 2100 ) ) {
		return new WP_Error( 'site_studio_home_oscars_search', 'The search is invalid.', array( 'status' => 400 ) );
	}
	$year = $year ? (int) $year : 0;
	$query = array( 'post_type' => 'picks' === $kind ? 'lunara_oscar_pick' : 'oscar_fact', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => 20, 'orderby' => 'date', 'order' => 'DESC', 'ignore_sticky_posts' => true, 's' => sanitize_text_field( (string) $search ) );
	if ( 'picks' === $kind && $year ) { $query['meta_query'] = array( array( 'key' => '_lunara_pick_ceremony_year', 'value' => $year ) ); }
	$posts = get_posts( $query );
	$search_ids = array_map( 'intval', wp_list_pluck( $posts, 'ID' ) );
	$items = array();
	foreach ( array_unique( array_merge( $search_ids, $ids ) ) as $id ) {
		$post = get_post( $id );
		$available = lunara_home_oscars_item_available( $post, $kind, $year );
		$items[] = array( 'id' => (int) $id, 'title' => $available ? (string) get_the_title( $post ) : 'Unavailable selection #' . $id, 'available' => $available );
	}
	return rest_ensure_response( array( 'items' => $items, 'results' => $search_ids ) );
}
add_action( 'rest_api_init', static function () {
	register_rest_route( 'lunara-site-studio/v1', '/surfaces/(?P<surface>home-oscar-picks|home-oscar-facts)/items', array( 'methods' => 'GET', 'callback' => 'lunara_site_studio_home_oscars_items', 'permission_callback' => 'lunara_site_studio_rest_route_permission' ) );
} );

function lunara_site_studio_render_home_oscars_inspector( $surface, $state, $revisions ) {
	$kind = 'home-oscar-picks' === $surface ? 'picks' : 'facts';
	$spec = lunara_site_studio_home_oscars_spec( $kind );
	$labels = array( 'kicker' => 'Label', 'heading' => 'Section heading', 'summary' => 'Supporting text', 'cta_text' => 'Button text', 'mode' => 'Selection mode', 'ceremony_year' => 'Ceremony year', 'count' => 'Maximum cards', 'autoplay_interval' => 'Rotation interval (milliseconds; 0 pauses)', 'density' => 'Card spacing and text density', 'card_min_height' => 'Minimum card height (pixels)' );
	lunara_site_studio_render_details_open( 'essentials', 'Essentials', true, array( 'oscar-' . $kind ) );
	foreach ( $spec['copy'] as $key => $def ) { lunara_site_studio_render_control( 'copy.' . $key, $state['copy'][$key], $def, $labels[$key] ); }
	lunara_site_studio_render_details_close();
	lunara_site_studio_render_details_open( 'lineup', 'Lineup', true, array( 'oscar-' . $kind ) );
	foreach ( $spec['selection'] as $key => $def ) { if ( 'ids' !== $key ) { lunara_site_studio_render_control( 'selection.' . $key, $state['selection'][$key], $def, $labels[$key] ); } }
	echo '<p>Legacy keeps your existing lineup. Automatic uses the newest published items. Manual retains your list when switching modes; unavailable selections are skipped.</p><div data-home-oscars-editor><div data-home-oscars-manual><label>Search published items <input type="search" maxlength="200" data-oscars-search></label><button type="button" data-oscars-search-button>Search</button><div data-oscars-results></div><ol data-oscars-lineup></ol></div><p data-oscars-status role="status" aria-live="polite"></p><p data-error-key="selection.ids" tabindex="-1" aria-describedby="lunara-oscars-ids-error"><span id="lunara-oscars-ids-error" class="lunara-site-studio-error" hidden></span></p></div>';
	lunara_site_studio_render_details_close();
	lunara_site_studio_render_details_open( 'fine-tune', 'Fine Tune', false, array( 'oscar-' . $kind ) );
	foreach ( $spec['presentation'] as $key => $def ) {
		if ( 'autoplay_interval' === $key ) {
			echo '<label class="lunara-site-studio-field">Time between advances (seconds; 0 pauses)<input type="number" min="0" max="12" step="0.5" data-oscars-interval data-error-key="presentation.autoplay_interval" aria-describedby="lunara-oscars-interval-error" value="' . esc_attr( $state['presentation'][$key] / 1000 ) . '"><span id="lunara-oscars-interval-error" class="lunara-site-studio-error" hidden></span></label>';
		} else { lunara_site_studio_render_control( 'presentation.' . $key, $state['presentation'][$key], $def, $labels[$key] ); }
	}
	lunara_site_studio_render_details_close();
	lunara_site_studio_render_details_open( 'mobile', 'Mobile', false, array( 'oscar-' . $kind ) );
	echo '<p>Use Mobile above the preview to inspect the real 390px layout. Density and card height apply to this section only. Artwork framing remains on each Pick or Fact.</p>';
	lunara_site_studio_render_details_close();
	lunara_site_studio_render_details_open( 'advanced', 'Advanced' );
	echo '<button type="button" data-action="reset-candidate" disabled>Reset candidate</button><a class="button" data-workspace-navigation href="' . esc_url( admin_url( 'edit.php?post_type=' . ( 'picks' === $kind ? 'lunara_oscar_pick' : 'oscar_fact' ) ) ) . '">Edit ' . esc_html( ucfirst( $kind ) ) . '</a>';
	lunara_site_studio_render_details_close();
	lunara_site_studio_render_revisions( $revisions );
}
