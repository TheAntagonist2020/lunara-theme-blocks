<?php
/** Homepage carousel Site Studio adapters, metadata and inspector. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function lunara_site_studio_carousel_schema() {
	$schema = array_fill_keys( array_keys( lunara_home_carousel_defaults() ), true );
	$schema['slides'] = array( '*' => array_fill_keys( array( 'post_id', 'image_id', 'headline', 'excerpt', 'kicker', 'cta', 'overlay', 'focal_x', 'focal_y', 'zoom', 'fit' ), true ) );
	return $schema;
}

function lunara_site_studio_carousel_surfaces( $surfaces ) {
	foreach ( array( 'hero' => 'Hero Carousel', 'journal' => 'Journal Carousel' ) as $kind => $label ) {
		$id = $kind . '-carousel';
		$surfaces[ $id ] = array(
			'id' => $id, 'group' => __( 'Homepage', 'lunara-film' ), 'label' => $label,
			'description' => 'Choose automatic latest content or arrange a manual carousel with independent presentation overrides.',
			'aliases' => array( $kind, 'carousel', 'slides' ), 'owner' => 'theme:' . $id, 'kind' => 'presentation',
			'capability' => 'edit_theme_options', 'supports_preview' => true, 'preview_route' => '/',
			'preview_query_arg' => 'lunara_' . $kind . '_carousel_preview',
			'adapter_factory' => 'lunara_site_studio_' . $kind . '_carousel_adapter',
			'state_schema_callback' => 'lunara_site_studio_carousel_schema',
			'admin_url' => 'admin.php?page=lunara-site-studio&surface=' . $id,
			'dependency_callback' => 'lunara_site_studio_dependency_available', 'status_callback' => 'lunara_site_studio_status_ready',
			'danger_level' => 'none', 'sections' => array( 'hero' === $kind ? 'hero' : 'dispatch' ),
			'classic_url' => 'admin.php?page=lunara-site-studio&surface=' . $id,
		);
	}
	return $surfaces;
}
add_filter( 'lunara_site_studio_surfaces', 'lunara_site_studio_carousel_surfaces' );

function lunara_site_studio_carousel_validate( $candidate, $kind ) {
	if ( ! is_array( $candidate ) || ! isset( $candidate['mode'], $candidate['slides'] ) || ! in_array( $candidate['mode'], array( 'auto', 'manual' ), true ) || ! is_array( $candidate['slides'] ) || count( $candidate['slides'] ) > 48 ) {
		return new WP_Error( 'site_studio_carousel_invalid', 'Check the carousel settings and select no more than 48 items.' );
	}
	return lunara_home_carousel_sanitize( $candidate, $kind );
}

function lunara_site_studio_carousel_write( $kind, $target, $action = 'save' ) {
	$surface  = $kind . '-carousel';
	$option   = lunara_home_carousel_option( $kind );
	$before   = lunara_site_studio_raw_option_snapshot( $option );
	$revision = lunara_site_studio_private_revision( $surface, $before, $action );
	if ( is_wp_error( $revision ) ) { return $revision; }
	if ( ! lunara_site_studio_apply_option_snapshot( $option, $target ) ) {
		if ( ! lunara_site_studio_apply_option_snapshot( $option, $before ) ) {
			return new WP_Error( 'site_studio_carousel_rollback_failed', 'The prior carousel settings could not be restored.' );
		}
		return new WP_Error( 'site_studio_carousel_write_failed', 'The carousel settings could not be saved.' );
	}
	$result = array( 'state' => lunara_home_carousel_settings( $kind ) );
	if ( 'restore-safety' === $action ) {
		$result['safety_revision_id'] = $revision;
	} else {
		$result['changed_sections'] = array( 'hero' === $kind ? 'hero' : 'dispatch' );
		$result['revision_id']      = $revision;
	}
	$result['timestamp'] = current_time( 'mysql' );
	return $result;
}

function lunara_site_studio_carousel_save( $candidate, $kind ) {
	$state = lunara_site_studio_carousel_validate( $candidate, $kind );
	if ( is_wp_error( $state ) ) { return $state; }
	$state['adopted'] = true;
	$value            = $state;
	if ( 'hero' === $kind ) {
		$value = get_option( 'lunara_hero_command', array() );
		$value = is_array( $value ) ? $value : array();
		$value['carousel'] = $state;
	}
	return lunara_site_studio_carousel_write( $kind, array( 'present' => true, 'value' => $value ) );
}

function lunara_site_studio_carousel_restore( $id, $kind ) {
	$target = lunara_site_studio_private_revision_target( $kind . '-carousel', $id );
	if ( is_wp_error( $target ) ) { return $target; }
	if ( array( 'present', 'value' ) !== array_keys( $target ) || ! is_bool( $target['present'] ) || ( $target['present'] && ! is_array( $target['value'] ) ) || ( ! $target['present'] && null !== $target['value'] ) ) {
		return new WP_Error( 'site_studio_revision_invalid', 'The selected revision is invalid.' );
	}
	return lunara_site_studio_carousel_write( $kind, $target, 'restore-safety' );
}

function lunara_site_studio_carousel_adapter( $kind ) {
	return new Lunara_Site_Studio_Theme_Adapter(
		$kind . '-carousel',
		'theme:' . $kind . '-carousel',
		array(
			'read' => static function () use ( $kind ) { return lunara_home_carousel_settings( $kind ); },
			'validate' => static function ( $state ) use ( $kind ) { return lunara_site_studio_carousel_validate( $state, $kind ); },
			'save' => static function ( $state ) use ( $kind ) { return lunara_site_studio_carousel_save( $state, $kind ); },
			'restore' => static function ( $id ) use ( $kind ) { return lunara_site_studio_carousel_restore( $id, $kind ); },
		)
	);
}
function lunara_site_studio_hero_carousel_adapter() { return lunara_site_studio_carousel_adapter( 'hero' ); }
function lunara_site_studio_journal_carousel_adapter() { return lunara_site_studio_carousel_adapter( 'journal' ); }

function lunara_site_studio_carousel_kind_from_request( $request ) {
	return 'journal-carousel' === $request->get_param( 'surface' ) ? 'journal' : 'hero';
}

/** Return one exact editor record, including unavailable retained selections. */
function lunara_site_studio_carousel_item_metadata( $post_id, $kind ) {
	$post_id   = absint( $post_id );
	$post      = $post_id ? get_post( $post_id ) : null;
	$types     = lunara_home_carousel_source_types( $kind );
	$available = $post instanceof WP_Post && 'publish' === $post->post_status && empty( $post->post_password ) && in_array( $post->post_type, $types, true );
	$readable  = $post instanceof WP_Post && ( $available || current_user_can( 'read_post', $post_id ) );
	$slide     = $available ? lunara_home_carousel_source_slide( $post, $kind ) : array();
	return array(
		'id'           => $post_id,
		'title'        => $readable ? (string) get_the_title( $post ) : sprintf( __( 'Unavailable item #%d', 'lunara-film' ), $post_id ),
		'type'         => $post instanceof WP_Post ? sanitize_key( $post->post_type ) : '',
		'available'    => $available,
		'date'         => $available ? (string) get_the_date( 'Y-m-d', $post_id ) : '',
		'date_label'   => $available ? (string) get_the_date( 'M j, Y', $post_id ) : '',
		'image_url'    => $available && isset( $slide['image'] ) ? (string) $slide['image'] : '',
		'image_id'     => $available && isset( $slide['attachment_id'] ) ? absint( $slide['attachment_id'] ) : 0,
		'image_source' => $available && isset( $slide['image_source'] ) ? (string) $slide['image_source'] : '',
		'excerpt'      => $available && isset( $slide['excerpt'] ) ? (string) $slide['excerpt'] : '',
		'kicker'       => $available && isset( $slide['kicker'] ) ? (string) $slide['kicker'] : '',
		'cta'          => $available && isset( $slide['cta'] ) ? (string) $slide['cta'] : '',
	);
}

/** Build the exact metadata envelope shared by initial markup and refreshes. */
function lunara_site_studio_carousel_metadata( $kind, $post_ids = array(), $image_ids = array() ) {
	$kind      = 'journal' === $kind ? 'journal' : 'hero';
	$automatic = lunara_home_carousel_automatic_posts( $kind );
	$all_ids   = array();
	foreach ( array_merge( is_array( $post_ids ) ? $post_ids : array(), wp_list_pluck( $automatic, 'ID' ) ) as $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id ) { $all_ids[ $post_id ] = $post_id; }
	}
	$items  = array();
	$images = array();
	foreach ( $all_ids as $post_id ) {
		$item = lunara_site_studio_carousel_item_metadata( $post_id, $kind );
		$items[ (string) $post_id ] = $item;
		if ( $item['image_id'] && '' !== $item['image_url'] ) { $images[ (string) $item['image_id'] ] = $item['image_url']; }
	}
	foreach ( is_array( $image_ids ) ? $image_ids : array() as $image_id ) {
		$image_id = absint( $image_id );
		if ( ! $image_id ) { continue; }
		$url = wp_attachment_is_image( $image_id ) ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
		$images[ (string) $image_id ] = $url ? (string) $url : '';
	}
	return array(
		'items'     => (object) $items,
		'images'    => (object) $images,
		'automatic' => array_values( array_map( static function ( $post ) { return (int) $post->ID; }, $automatic ) ),
	);
}

/** Accept the canonical comma form and array params without coercing bad IDs. */
function lunara_site_studio_carousel_request_ids( $request, $name, $limit = 48 ) {
	$raw = $request->get_param( $name );
	if ( null === $raw || '' === $raw ) { return array(); }
	$values = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
	if ( count( $values ) > $limit ) { return new WP_Error( 'site_studio_carousel_metadata_invalid', __( 'Too many metadata IDs were requested.', 'lunara-film' ) ); }
	$result = array();
	foreach ( $values as $value ) {
		if ( ! is_scalar( $value ) || 1 !== preg_match( '/^[1-9][0-9]*$/D', (string) $value ) ) {
			return new WP_Error( 'site_studio_carousel_metadata_invalid', __( 'Metadata IDs must be positive integers.', 'lunara-film' ) );
		}
		$id = absint( $value );
		if ( ! isset( $result[ $id ] ) ) { $result[ $id ] = $id; }
	}
	return array_values( $result );
}

function lunara_site_studio_carousel_search( $request ) {
	$kind  = lunara_site_studio_carousel_kind_from_request( $request );
	$query = new WP_Query( array(
		'post_type' => lunara_home_carousel_source_types( $kind ), 'post_status' => 'publish', 'has_password' => false,
		's' => sanitize_text_field( (string) $request->get_param( 'search' ) ), 'posts_per_page' => 20,
		'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ), 'ignore_sticky_posts' => true, 'no_found_rows' => true,
	) );
	$items = array();
	foreach ( $query->posts as $post ) { $items[] = lunara_site_studio_carousel_item_metadata( $post->ID, $kind ); }
	return new WP_REST_Response( array( 'items' => $items ), 200 );
}

function lunara_site_studio_carousel_metadata_response( $request ) {
	$post_ids  = lunara_site_studio_carousel_request_ids( $request, 'ids' );
	$image_ids = lunara_site_studio_carousel_request_ids( $request, 'image_ids' );
	if ( is_wp_error( $post_ids ) || is_wp_error( $image_ids ) ) {
		$error = is_wp_error( $post_ids ) ? $post_ids : $image_ids;
		return new WP_REST_Response( array( 'code' => $error->get_error_code(), 'message' => $error->get_error_message() ), 400 );
	}
	return new WP_REST_Response( lunara_site_studio_carousel_metadata( lunara_site_studio_carousel_kind_from_request( $request ), $post_ids, $image_ids ), 200 );
}

function lunara_site_studio_register_carousel_routes() {
	$route = '/surfaces/(?P<surface>hero-carousel|journal-carousel)';
	register_rest_route( 'lunara-site-studio/v1', $route . '/search', array( 'methods' => 'GET', 'callback' => 'lunara_site_studio_carousel_search', 'permission_callback' => 'lunara_site_studio_rest_route_permission' ) );
	register_rest_route( 'lunara-site-studio/v1', $route . '/metadata', array( 'methods' => 'GET', 'callback' => 'lunara_site_studio_carousel_metadata_response', 'permission_callback' => 'lunara_site_studio_rest_route_permission' ) );
}
add_action( 'rest_api_init', 'lunara_site_studio_register_carousel_routes' );

function lunara_site_studio_render_carousel_inspector( $surface, $state, $revisions ) {
	$kind      = 'journal-carousel' === $surface ? 'journal' : 'hero';
	$post_ids  = wp_list_pluck( $state['slides'], 'post_id' );
	$image_ids = wp_list_pluck( $state['slides'], 'image_id' );
	$metadata  = lunara_site_studio_carousel_metadata( $kind, $post_ids, $image_ids );
	?>
	<p>Automatic uses the six newest published items by publication date. Manual keeps your selection when switching modes. Unavailable items stay listed but are skipped on the homepage.</p>
	<?php if ( ! $state['adopted'] ) : ?><p data-carousel-adoption-notice><strong>Your existing homepage presentation stays active until you Apply this carousel.</strong></p><?php endif; ?>
	<div data-carousel-editor>
		<label>Mode <select data-carousel-field="mode"><option value="auto">Automatic — six newest</option><option value="manual">Manual selection</option></select></label>
		<label>Section heading <input data-carousel-field="heading" maxlength="160" type="text"></label>
		<label><input data-carousel-field="autoplay" type="checkbox"> Autoplay</label>
		<label>Interval (seconds) <input data-carousel-field="interval" type="number" min="3" max="30"></label>
		<?php if ( 'hero' === $kind ) : ?><label>Overlay strength <input data-carousel-field="overlay" type="range" min="20" max="100"></label><?php endif; ?>
		<div data-carousel-automatic></div>
		<div data-carousel-manual>
			<label>Search published <?php echo 'journal' === $kind ? 'Journal entries' : 'Reviews and Journal entries'; ?> <input type="search" data-carousel-search></label>
			<button type="button" data-carousel-search-button>Search</button>
			<div data-carousel-results aria-live="polite"></div>
			<p data-carousel-empty hidden>An empty manual carousel is hidden on the homepage. Add an item or choose Automatic.</p>
			<ol data-carousel-items></ol>
		</div>
	</div>
	<script type="application/json" id="lunara-carousel-metadata"><?php echo wp_json_encode( $metadata, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>
	<?php
	lunara_site_studio_render_revisions( $revisions );
}

function lunara_home_carousel_retire_customizer_controls( $manager ) {
	if ( ! is_object( $manager ) || ! method_exists( $manager, 'remove_control' ) ) { return; }
	$hero = lunara_home_carousel_settings( 'hero' ); $journal = lunara_home_carousel_settings( 'journal' );
	if ( $hero['adopted'] ) { foreach ( array( 'lunara_spotlight_enabled', 'lunara_spotlight_post_id', 'lunara_spotlight_label', 'lunara_home_hero_review_ids' ) as $id ) { $manager->remove_control( $id ); } }
	if ( $journal['adopted'] ) { $manager->remove_control( 'lunara_home_journal_lead_post_id' ); }
	if ( $journal['adopted'] && method_exists( $manager, 'controls' ) ) { foreach ( $manager->controls() as $id => $control ) { if ( 0 === strpos( $id, 'lunara_home_dispatch_' ) ) { $manager->remove_control( $id ); } } }
}
add_action( 'customize_register', 'lunara_home_carousel_retire_customizer_controls', 100 );
