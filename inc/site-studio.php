<?php
/** Dedicated, progressively enhanced Lunara Site Studio workspace. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'lunara_register_site_studio_page' ) ) {
	function lunara_register_site_studio_page() {
		add_submenu_page( 'lunara-control-desk', __( 'Lunara Site Studio', 'lunara-film' ), __( 'Site Studio', 'lunara-film' ), 'edit_theme_options', 'lunara-site-studio', 'lunara_render_site_studio_page', 1 );
	}
	add_action( 'admin_menu', 'lunara_register_site_studio_page', 20 );
}

if ( ! function_exists( 'lunara_site_studio_authorized_surfaces' ) ) {
	function lunara_site_studio_authorized_surfaces() {
		$authorized = array();
		foreach ( lunara_site_studio_surfaces() as $id => $surface ) { if ( current_user_can( $surface['capability'] ) ) { $authorized[ $id ] = $surface; } }
		return $authorized;
	}
}

if ( ! function_exists( 'lunara_site_studio_workspace_surface' ) ) {
	function lunara_site_studio_workspace_surface( $surfaces = null ) {
		$surfaces = is_array( $surfaces ) ? $surfaces : lunara_site_studio_authorized_surfaces();
		$present = array_key_exists( 'surface', $_GET ); if ( $present && ! is_string( $_GET['surface'] ) ) { return ''; }
		$raw_requested = $present ? wp_unslash( $_GET['surface'] ) : '';
		$requested = $present ? sanitize_key( $raw_requested ) : '';
		if ( $present && $raw_requested !== $requested ) { return ''; }
		if ( $present ) { return '' !== $requested && isset( $surfaces[ $requested ] ) ? $requested : ''; }
		$ids = array_keys( $surfaces );
		return isset( $ids[0] ) ? $ids[0] : '';
	}
}

if ( ! function_exists( 'lunara_site_studio_workspace_editable_surfaces' ) ) {
	/** @return array<int,string> */
	function lunara_site_studio_workspace_editable_surfaces() {
		return array( 'global-design', 'homepage-structure', 'lunara-method', 'reviews-archive', 'review-single', 'journal-archive', 'utility-search', 'site-footer' );
	}
}

if ( ! function_exists( 'lunara_site_studio_workspace_state_schema' ) ) {
	/** Return a surface's private, storage-key-free client shape. */
	function lunara_site_studio_workspace_state_schema( $surface ) {
		$callback = is_array( $surface ) && isset( $surface['state_schema_callback'] ) ? $surface['state_schema_callback'] : '';
		if ( ! is_callable( $callback ) ) { return array(); }
		try { $schema = call_user_func( $callback, $surface ); } catch ( Throwable $error ) { return array(); }
		return is_array( $schema ) ? $schema : array();
	}
	function lunara_site_studio_workspace_schema_is_safe( $schema, $depth = 0, &$nodes = null ) {
		if ( null === $nodes ) { $nodes = 0; }
		$nodes++;
		if ( $depth > 32 || $nodes > 4096 ) { return false; }
		if ( true === $schema ) { return true; }
		if ( ! is_array( $schema ) ) { return false; }
		foreach ( $schema as $key => $child ) {
			if ( ! is_string( $key ) || '' === $key || ( '*' === $key && 1 !== count( $schema ) ) || ( '*' !== $key && sanitize_key( $key ) !== $key ) || ! lunara_site_studio_workspace_schema_is_safe( $child, $depth + 1, $nodes ) ) { return false; }
		}
		return true;
	}
}

if ( ! function_exists( 'lunara_site_studio_workspace_config' ) ) {
	function lunara_site_studio_workspace_config( $surface_id, $surface ) {
		static $page_uuid = null; if ( null === $page_uuid ) { $page_uuid = strtolower( wp_generate_uuid4() ); }
		$urls = lunara_site_studio_workspace_urls( $surface );
		if ( ! $urls ) { return array(); }
		$base = 'lunara-site-studio/v1/surfaces/' . rawurlencode( $surface_id );
		$home = $urls['home'];
		$origin_scheme = strtolower( (string) wp_parse_url( $home, PHP_URL_SCHEME ) ); $origin_host = strtolower( (string) wp_parse_url( $home, PHP_URL_HOST ) );
		$origin = $origin_scheme . '://' . $origin_host;
		$port = wp_parse_url( $home, PHP_URL_PORT ); if ( $port && ! ( 'https' === $origin_scheme && 443 === absint( $port ) ) && ! ( 'http' === $origin_scheme && 80 === absint( $port ) ) ) { $origin .= ':' . absint( $port ); }
		$preview_path = (string) wp_parse_url( $urls['preview'], PHP_URL_PATH ); if ( '' === $preview_path ) { $preview_path = '/'; }
		$preview_surfaces = lunara_site_studio_preview_pilots();
		$preview_meta = isset( $preview_surfaces[ $surface_id ] ) ? $preview_surfaces[ $surface_id ] : array();
		$schema = lunara_site_studio_workspace_state_schema( $surface ); $schema_nodes = 0;
		if ( ! $preview_meta || ! lunara_site_studio_workspace_schema_is_safe( $schema, 0, $schema_nodes ) ) { return array(); }
		return array(
			'protocol' => 'lunara-site-studio/v1', 'clientVersion' => 1, 'surface' => $surface_id,
			'pageUuid' => $page_uuid, 'previewInstanceArg' => lunara_site_studio_preview_instance_query_arg(),
			'endpoints' => array( 'state' => esc_url_raw( rest_url( $base . '/state' ) ), 'preview' => esc_url_raw( rest_url( $base . '/preview' ) ), 'save' => esc_url_raw( rest_url( $base . '/save' ) ), 'revisions' => esc_url_raw( rest_url( $base . '/revisions' ) ), 'restore' => esc_url_raw( rest_url( $base . '/restore' ) ) ),
			'nonce' => wp_create_nonce( 'wp_rest' ), 'previewOrigin' => $origin, 'previewRoute' => $preview_path,
			'previewQueryArg' => $surface['preview_query_arg'], 'previewParams' => (object) $surface['preview_params'], 'stateSchema' => $schema,
			'widths' => array( 'desktop' => 1440, 'tablet' => 768, 'mobile' => 390 ), 'markers' => $preview_meta['markers'],
			'strings' => array(
				'live' => __( 'Live settings loaded.', 'lunara-film' ), 'dirty' => __( 'Unsaved changes.', 'lunara-film' ), 'previewCurrent' => __( 'Preview is current.', 'lunara-film' ), 'previewStale' => __( 'Preview is out of date.', 'lunara-film' ), 'saving' => __( 'Saving live settings…', 'lunara-film' ), 'saved' => __( 'Live settings saved.', 'lunara-film' ), 'restored' => __( 'Revision restored.', 'lunara-film' ), 'failed' => __( 'The request could not be completed. Your changes are still here.', 'lunara-film' ),
				'discardConfirm' => __( 'Discard your unsaved changes?', 'lunara-film' ), 'navigateConfirm' => __( 'Discard unsaved changes and open another surface?', 'lunara-film' ), 'hideConfirm' => __( 'Hide this Homepage section?', 'lunara-film' ), 'removeConfirm' => __( 'Hide this item from the public site?', 'lunara-film' ), 'clearOverrideConfirm' => __( 'Clear this override and use the current inherited fallback?', 'lunara-film' ), 'resetOverridesConfirm' => __( 'Reset all Global overrides?', 'lunara-film' ), 'resetConfirm' => __( 'Reset this candidate?', 'lunara-film' ), 'reviewAutomaticConfirm' => __( 'Switch this Review selection back to Automatic?', 'lunara-film' ), 'clearMediaConfirm' => __( 'Clear this backdrop?', 'lunara-film' ), 'restoreConfirm' => __( 'Restore this revision to the live site?', 'lunara-film' ),
				'revisionEmpty' => __( 'No revisions yet.', 'lunara-film' ), 'revisionRestore' => __( 'Restore', 'lunara-film' ), 'revisionSaved' => __( 'Saved live', 'lunara-film' ), 'revisionSafety' => __( 'Safety snapshot', 'lunara-film' ), 'revisionRestored' => __( 'Restored revision', 'lunara-film' ), 'revisionOther' => __( 'Revision', 'lunara-film' ),
				'desktop' => __( 'Desktop', 'lunara-film' ), 'tablet' => __( 'Tablet', 'lunara-film' ), 'mobile' => __( 'Mobile', 'lunara-film' ), 'searchCount' => __( '%d destinations', 'lunara-film' ), 'moved' => __( 'Section order updated.', 'lunara-film' ), 'chooseBackdrop' => __( 'Choose backdrop', 'lunara-film' ),
			),
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_origin_key' ) ) {
	function lunara_site_studio_origin_key( $url ) { $scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ); $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ); $port = wp_parse_url( $url, PHP_URL_PORT ); if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || '' === $host || null !== wp_parse_url( $url, PHP_URL_USER ) || null !== wp_parse_url( $url, PHP_URL_PASS ) || null !== wp_parse_url( $url, PHP_URL_FRAGMENT ) ) { return ''; } if ( ! $port ) { $port = 'https' === $scheme ? 443 : 80; } return $scheme . '://' . $host . ':' . absint( $port ); }
	function lunara_site_studio_same_origin( $left, $right ) { $left_key = lunara_site_studio_origin_key( $left ); return '' !== $left_key && $left_key === lunara_site_studio_origin_key( $right ); }
	function lunara_site_studio_workspace_urls( $surface ) {
		if ( ! is_array( $surface ) || empty( $surface['preview_route'] ) || ! is_string( $surface['preview_route'] ) ) { return array(); }
		$admin = admin_url( '/' ); $home = home_url( '/' ); $preview_base = home_url( $surface['preview_route'] );
		foreach ( array( $admin, $home, $preview_base ) as $url ) { if ( ! is_string( $url ) || '' === lunara_site_studio_origin_key( $url ) || null !== wp_parse_url( $url, PHP_URL_QUERY ) ) { return array(); } }
		$home_path = (string) wp_parse_url( $home, PHP_URL_PATH ); if ( '' === $home_path ) { $home_path = '/'; }
		$expected_path = '/' === $surface['preview_route'] ? $home_path : rtrim( $home_path, '/' ) . '/' . ltrim( $surface['preview_route'], '/' );
		$preview_path = (string) wp_parse_url( $preview_base, PHP_URL_PATH ); if ( '' === $preview_path ) { $preview_path = '/'; }
		if ( ! lunara_site_studio_same_origin( $admin, $home ) || ! lunara_site_studio_same_origin( $home, $preview_base ) || $preview_path !== $expected_path ) { return array(); }
		$params = isset( $surface['preview_params'] ) && is_array( $surface['preview_params'] ) ? $surface['preview_params'] : array();
		$preview = $params ? add_query_arg( $params, $preview_base ) : $preview_base;
		if ( ! is_string( $preview ) || ! lunara_site_studio_same_origin( $home, $preview ) || null !== wp_parse_url( $preview, PHP_URL_FRAGMENT ) ) { return array(); }
		return array( 'admin' => $admin, 'home' => $home, 'preview' => $preview );
	}
	function lunara_site_studio_safe_admin_destination( $url ) {
		$admin = admin_url( '/' );
		if ( ! is_string( $url ) || preg_match( '/[\r\n]/', $url ) ) { return ''; }
		$fragment_at = strpos( $url, '#' ); $origin_url = false === $fragment_at ? $url : substr( $url, 0, $fragment_at );
		if ( ! lunara_site_studio_same_origin( $origin_url, $admin ) ) { return ''; }
		$base_path = (string) wp_parse_url( $admin, PHP_URL_PATH ); $path = (string) wp_parse_url( $url, PHP_URL_PATH );
		return '' !== $base_path && 0 === strpos( $path, $base_path ) ? $url : '';
	}
	function lunara_site_studio_safe_local_thumbnail( $url ) { if ( ! is_string( $url ) || ! lunara_site_studio_same_origin( $url, home_url( '/' ) ) || wp_parse_url( $url, PHP_URL_USER ) || wp_parse_url( $url, PHP_URL_PASS ) ) { return ''; } return $url; }
	function lunara_site_studio_workspace_config_is_safe( $config ) {
		$top_keys = array( 'protocol', 'clientVersion', 'surface', 'pageUuid', 'previewInstanceArg', 'endpoints', 'nonce', 'previewOrigin', 'previewRoute', 'previewQueryArg', 'previewParams', 'stateSchema', 'widths', 'markers', 'strings' );
		if ( ! is_array( $config ) ) { return false; } $actual_top_keys = array_keys( $config ); sort( $actual_top_keys ); $expected_top_keys = $top_keys; sort( $expected_top_keys );
		if ( $actual_top_keys !== $expected_top_keys || 'lunara-site-studio/v1' !== $config['protocol'] || 1 !== $config['clientVersion'] || empty( $config['surface'] ) || ! is_string( $config['surface'] ) || ! is_string( $config['pageUuid'] ) || 1 !== preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $config['pageUuid'] ) || lunara_site_studio_preview_instance_query_arg() !== $config['previewInstanceArg'] || ! is_array( $config['endpoints'] ) || ! is_string( $config['nonce'] ) || '' === $config['nonce'] || ! is_string( $config['previewOrigin'] ) || ! is_string( $config['previewRoute'] ) || ! is_string( $config['previewQueryArg'] ) || ! ( $config['previewParams'] instanceof stdClass ) || ! is_array( $config['stateSchema'] ) || ! is_array( $config['widths'] ) || array( 'desktop' => 1440, 'tablet' => 768, 'mobile' => 390 ) !== $config['widths'] || ! is_array( $config['markers'] ) || ! is_array( $config['strings'] ) ) { return false; }
		$surface = lunara_site_studio_get_surface( $config['surface'] ); $urls = lunara_site_studio_workspace_urls( $surface ); if ( ! is_array( $surface ) || ! $urls ) { return false; }
		$home = $urls['home']; $scheme = strtolower( (string) wp_parse_url( $home, PHP_URL_SCHEME ) ); $host = strtolower( (string) wp_parse_url( $home, PHP_URL_HOST ) ); $expected_origin = $scheme . '://' . $host; $port = wp_parse_url( $home, PHP_URL_PORT ); if ( $port && ! ( 'https' === $scheme && 443 === absint( $port ) ) && ! ( 'http' === $scheme && 80 === absint( $port ) ) ) { $expected_origin .= ':' . absint( $port ); }
		$expected_route = (string) wp_parse_url( $urls['preview'], PHP_URL_PATH ); if ( '' === $expected_route ) { $expected_route = '/'; }
		$preview_surfaces = lunara_site_studio_preview_pilots(); $preview_meta = isset( $preview_surfaces[ $config['surface'] ] ) ? $preview_surfaces[ $config['surface'] ] : array();
		$expected_schema = lunara_site_studio_workspace_state_schema( $surface ); $schema_nodes = 0;
		if ( $expected_origin !== $config['previewOrigin'] || $expected_route !== $config['previewRoute'] || ! $preview_meta || $preview_meta['owner'] !== $surface['owner'] || $preview_meta['query'] !== $surface['preview_query_arg'] || $preview_meta['route'] !== $surface['preview_route'] || $preview_meta['params'] !== $surface['preview_params'] || $preview_meta['markers'] !== $config['markers'] || $surface['preview_query_arg'] !== $config['previewQueryArg'] || $surface['preview_params'] !== (array) $config['previewParams'] || $expected_schema !== $config['stateSchema'] || ! lunara_site_studio_workspace_schema_is_safe( $config['stateSchema'], 0, $schema_nodes ) || ! lunara_site_studio_same_origin( $config['previewOrigin'] . $config['previewRoute'], admin_url( '/' ) ) ) { return false; }
		$string_keys = array( 'live', 'dirty', 'previewCurrent', 'previewStale', 'saving', 'saved', 'restored', 'failed', 'discardConfirm', 'navigateConfirm', 'hideConfirm', 'removeConfirm', 'clearOverrideConfirm', 'resetOverridesConfirm', 'resetConfirm', 'reviewAutomaticConfirm', 'clearMediaConfirm', 'restoreConfirm', 'revisionEmpty', 'revisionRestore', 'revisionSaved', 'revisionSafety', 'revisionRestored', 'revisionOther', 'desktop', 'tablet', 'mobile', 'searchCount', 'moved', 'chooseBackdrop' ); $actual_string_keys = array_keys( $config['strings'] ); sort( $actual_string_keys ); $expected_string_keys = $string_keys; sort( $expected_string_keys ); if ( $actual_string_keys !== $expected_string_keys ) { return false; } foreach ( $config['strings'] as $message ) { if ( ! is_string( $message ) || '' === $message ) { return false; } }
		$base = 'lunara-site-studio/v1/surfaces/' . rawurlencode( $config['surface'] );
		foreach ( array( 'state', 'preview', 'save', 'revisions', 'restore' ) as $key ) {
			$expected = esc_url_raw( rest_url( $base . '/' . $key ) );
			if ( empty( $config['endpoints'][ $key ] ) || ! is_string( $config['endpoints'][ $key ] ) || $expected !== $config['endpoints'][ $key ] || ! lunara_site_studio_same_origin( $config['endpoints'][ $key ], admin_url( '/' ) ) ) { return false; }
		}
		return 5 === count( $config['endpoints'] );
	}
}

if ( ! function_exists( 'lunara_enqueue_site_studio_assets' ) ) {
	function lunara_enqueue_site_studio_assets( $hook ) {
		if ( 'lunara_page_lunara-site-studio' !== $hook || ! function_exists( 'lunara_resolve_theme_asset' ) ) { return; }
		$surfaces = lunara_site_studio_authorized_surfaces(); $surface_id = lunara_site_studio_workspace_surface( $surfaces );
		if ( '' === $surface_id || ! isset( $surfaces[ $surface_id ] ) ) { return; }
		wp_enqueue_style( 'dashicons' );
		foreach ( array( 'style' => 'assets/css/lunara-site-studio.css', 'script' => 'assets/js/lunara-site-studio.js' ) as $kind => $relative ) {
			$asset = lunara_resolve_theme_asset( $relative ); if ( empty( $asset['uri'] ) ) { continue; }
			$version = function_exists( 'lunara_theme_asset_version' ) ? lunara_theme_asset_version( $asset['path'] ) : false;
			if ( 'style' === $kind ) { wp_enqueue_style( 'lunara-site-studio', $asset['uri'], array( 'dashicons' ), $version ); }
			else { wp_enqueue_script( 'lunara-site-studio', $asset['uri'], array(), $version, true ); $config = lunara_site_studio_workspace_config( $surface_id, $surfaces[ $surface_id ] ); wp_localize_script( 'lunara-site-studio', 'LunaraSiteStudioWorkspaceConfig', lunara_site_studio_workspace_config_is_safe( $config ) ? $config : array() ); }
		}
		if ( 'lunara-method' === $surface_id ) { wp_enqueue_media(); }
	}
	add_action( 'admin_enqueue_scripts', 'lunara_enqueue_site_studio_assets' );
}

if ( ! function_exists( 'lunara_site_studio_safe_revisions' ) ) {
	function lunara_site_studio_safe_revisions( $revisions ) {
		$safe = array(); foreach ( is_array( $revisions ) ? array_slice( $revisions, 0, 12 ) : array() as $row ) { if ( ! is_array( $row ) || empty( $row['id'] ) || ! is_scalar( $row['id'] ) ) { continue; } $timestamp = isset( $row['timestamp'] ) ? $row['timestamp'] : ( isset( $row['saved_at'] ) ? $row['saved_at'] : '' ); $action = isset( $row['action'] ) ? $row['action'] : ''; if ( ! is_scalar( $timestamp ) || ! is_scalar( $action ) ) { continue; } $id = sanitize_text_field( $row['id'] ); if ( '' === $id ) { continue; } $safe[] = array( 'id' => $id, 'timestamp' => sanitize_text_field( $timestamp ), 'action' => sanitize_key( $action ) ); }
		return $safe;
	}
}

if ( ! function_exists( 'lunara_site_studio_render_details_open' ) ) {
	function lunara_site_studio_render_details_open( $section, $label, $open = false, $preview_sections = array() ) { echo '<details' . ( $open ? ' open' : '' ) . ' class="lunara-site-studio-details" data-section="' . esc_attr( $section ) . '"' . ( $preview_sections ? ' data-preview-controls="' . esc_attr( implode( ' ', $preview_sections ) ) . '"' : '' ) . '><summary>' . esc_html( $label ) . '</summary><div class="lunara-site-studio-details-body">'; }
	function lunara_site_studio_render_details_close() { echo '</div></details>'; }
}

if ( ! function_exists( 'lunara_site_studio_render_field' ) ) {
	function lunara_site_studio_render_field( $path, $label, $value, $type = 'text', $error_key = '', $effective_id = '' ) {
		$id = 'lunara-site-studio-' . str_replace( array( '.', '_' ), '-', $path ); $error_key = $error_key ? $error_key : str_replace( '.', '_', $path );
		$describedby = $id . '-help' . ( $effective_id ? ' ' . $effective_id : '' ) . ' ' . $id . '-error';
		echo '<div class="lunara-site-studio-field"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
		if ( 'textarea' === $type ) { echo '<textarea id="' . esc_attr( $id ) . '" data-field-path="' . esc_attr( $path ) . '" data-error-key="' . esc_attr( $error_key ) . '" aria-describedby="' . esc_attr( $describedby ) . '">' . esc_html( $value ) . '</textarea>'; }
		else { echo '<input id="' . esc_attr( $id ) . '" type="' . esc_attr( $type ) . '" value="' . esc_attr( $value ) . '" data-field-path="' . esc_attr( $path ) . '" data-error-key="' . esc_attr( $error_key ) . '" aria-describedby="' . esc_attr( $describedby ) . '" />'; }
		echo '<span class="description" id="' . esc_attr( $id ) . '-help">' . esc_html__( 'Changes stay local until Preview or Save Live.', 'lunara-film' ) . '</span><span class="lunara-site-studio-error" id="' . esc_attr( $id ) . '-error" hidden></span></div>';
	}
}

if ( ! function_exists( 'lunara_site_studio_control_label' ) ) {
	function lunara_site_studio_control_label( $field ) {
		$labels = array(
			'density' => __( 'Visual density', 'lunara-film' ), 'hero_scale' => __( 'Hero scale', 'lunara-film' ), 'rail_mode' => __( 'Companion rail', 'lunara-film' ), 'debrief_prominence' => __( 'Debrief prominence', 'lunara-film' ), 'pairing_density' => __( 'Pairing density', 'lunara-film' ), 'spoiler_treatment' => __( 'Spoiler treatment', 'lunara-film' ), 'trailer_prominence' => __( 'Trailer prominence', 'lunara-film' ), 'section_gap' => __( 'Section spacing', 'lunara-film' ), 'debrief_poster_width' => __( 'Debrief poster width', 'lunara-film' ), 'related_count' => __( 'Related Reviews', 'lunara-film' ),
			'layout' => __( 'Layout', 'lunara-film' ), 'text_depth' => __( 'Text depth', 'lunara-film' ), 'mobile_stack' => __( 'Mobile stack', 'lunara-film' ), 'image_focus' => __( 'Image focus', 'lunara-film' ), 'columns' => __( 'Columns', 'lunara-film' ), 'thumb_width' => __( 'Thumbnail width', 'lunara-film' ),
			'result_treatment' => __( 'Result treatment', 'lunara-film' ), 'result_media' => __( 'Result imagery', 'lunara-film' ), 'recovery_prominence' => __( 'Recovery prominence', 'lunara-film' ), 'lead' => __( 'Lead focus', 'lunara-film' ), 'spotlight' => __( 'Spotlight type', 'lunara-film' ), 'result_min_height' => __( 'Result height', 'lunara-film' ), 'card_grid_min' => __( 'Card width', 'lunara-film' ),
			'show_logo' => __( 'Show the Lunara logo', 'lunara-film' ), 'tagline' => __( 'Closing line', 'lunara-film' ), 'editorial' => __( 'Editorial column', 'lunara-film' ), 'oscars' => __( 'Oscars column', 'lunara-film' ), 'utility' => __( 'Utility column', 'lunara-film' ), 'name' => __( 'Copyright name', 'lunara-film' ),
			'lead_prominence' => __( 'Lead prominence', 'lunara-film' ), 'rail_density' => __( 'Companion rail density', 'lunara-film' ), 'desk_rhythm' => __( 'Desk rhythm', 'lunara-film' ), 'lead_min_height' => __( 'Lead height', 'lunara-film' ), 'hero_min_height' => __( 'Hero height', 'lunara-film' ), 'card_min_height' => __( 'Card height', 'lunara-film' ), 'compact_media_width' => __( 'Compact image width', 'lunara-film' ), 'media_min_height' => __( 'Image height', 'lunara-film' ), 'item_count' => __( 'Items per page', 'lunara-film' ),
		);
		return isset( $labels[ $field ] ) ? $labels[ $field ] : ucwords( str_replace( '_', ' ', $field ) );
	}
	function lunara_site_studio_choice_label( $choice ) { return ucwords( str_replace( '-', ' ', $choice ) ); }
}

if ( ! function_exists( 'lunara_site_studio_render_control' ) ) {
	/** Render one familiar control without exposing its canonical storage key. */
	function lunara_site_studio_render_control( $path, $value, $definition, $label = '' ) {
		$parts = explode( '.', $path ); $field = end( $parts ); $label = $label ? $label : lunara_site_studio_control_label( $field );
		$id = 'lunara-site-studio-' . sanitize_html_class( str_replace( array( '.', '_' ), '-', $path ) ); $help_id = $id . '-help'; $error_id = $id . '-error';
		echo '<div class="lunara-site-studio-field"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
		if ( 'select' === $definition['type'] ) {
			echo '<select id="' . esc_attr( $id ) . '" data-field-path="' . esc_attr( $path ) . '" data-value-type="string" data-error-key="' . esc_attr( $path ) . '" aria-describedby="' . esc_attr( $help_id . ' ' . $error_id ) . '">'; foreach ( $definition['allowed'] as $choice ) { echo '<option value="' . esc_attr( $choice ) . '"' . ( $choice === $value ? ' selected' : '' ) . '>' . esc_html( lunara_site_studio_choice_label( $choice ) ) . '</option>'; } echo '</select>';
		} elseif ( 'int' === $definition['type'] ) {
			echo '<input id="' . esc_attr( $id ) . '" type="number" min="' . esc_attr( $definition['min'] ) . '" max="' . esc_attr( $definition['max'] ) . '" step="1" value="' . esc_attr( $value ) . '" data-field-path="' . esc_attr( $path ) . '" data-value-type="integer" data-error-key="' . esc_attr( $path ) . '" aria-describedby="' . esc_attr( $help_id . ' ' . $error_id ) . '" />';
		} elseif ( 'bool' === $definition['type'] ) {
			echo '<input id="' . esc_attr( $id ) . '" type="checkbox"' . ( $value ? ' checked' : '' ) . ' data-field-path="' . esc_attr( $path ) . '" data-value-type="boolean" data-error-key="' . esc_attr( $path ) . '" aria-describedby="' . esc_attr( $help_id . ' ' . $error_id ) . '" />';
		} else {
			echo '<input id="' . esc_attr( $id ) . '" type="text" maxlength="' . esc_attr( $definition['max_length'] ) . '" value="' . esc_attr( $value ) . '" data-field-path="' . esc_attr( $path ) . '" data-value-type="string" data-error-key="' . esc_attr( $path ) . '" aria-describedby="' . esc_attr( $help_id . ' ' . $error_id ) . '" />';
		}
		echo '<span class="description" id="' . esc_attr( $help_id ) . '">' . esc_html__( 'Changes stay local until Preview Changes or Save Live.', 'lunara-film' ) . '</span><span class="lunara-site-studio-error" id="' . esc_attr( $error_id ) . '" hidden></span></div>';
	}
	function lunara_site_studio_render_spec_paths( $state, $spec, $paths ) {
		foreach ( $paths as $path ) { $parts = explode( '.', $path ); if ( 2 !== count( $parts ) || ! isset( $spec[ $parts[0] ][ $parts[1] ], $state[ $parts[0] ] ) || ! is_array( $state[ $parts[0] ] ) || ! array_key_exists( $parts[1], $state[ $parts[0] ] ) ) { continue; } lunara_site_studio_render_control( $path, $state[ $parts[0] ][ $parts[1] ], $spec[ $parts[0] ][ $parts[1] ] ); }
	}
}

if ( ! function_exists( 'lunara_site_studio_render_mod_surface_inspector' ) ) {
	function lunara_site_studio_render_mod_surface_inspector( $surface_id, $state, $revisions, $classic_url ) {
		if ( 'review-single' === $surface_id ) {
			$spec = lunara_site_studio_review_single_spec();
			lunara_site_studio_render_details_open( 'essentials', __( 'Essentials', 'lunara-film' ), true, array( 'hero', 'criticism', 'debrief' ) ); lunara_site_studio_render_spec_paths( $state, $spec, array( 'review.density', 'review.hero_scale', 'review.debrief_prominence', 'review.related_count' ) ); lunara_site_studio_render_details_close();
			lunara_site_studio_render_details_open( 'fine-tune', __( 'Fine Tune', 'lunara-film' ), false, array( 'criticism', 'debrief', 'pair-it-with' ) ); lunara_site_studio_render_spec_paths( $state, $spec, array( 'review.rail_mode', 'review.pairing_density', 'review.spoiler_treatment', 'review.trailer_prominence', 'review.section_gap', 'review.debrief_poster_width', 'pairing.layout', 'pairing.text_depth' ) ); lunara_site_studio_render_details_close();
			lunara_site_studio_render_details_open( 'mobile', __( 'Mobile', 'lunara-film' ), false, array( 'pair-it-with' ) ); lunara_site_studio_render_spec_paths( $state, $spec, array( 'pairing.mobile_stack', 'pairing.image_focus', 'pairing.columns', 'pairing.thumb_width' ) ); lunara_site_studio_render_details_close();
		} elseif ( 'utility-search' === $surface_id ) {
			$spec = lunara_site_studio_utility_search_spec();
			lunara_site_studio_render_details_open( 'essentials', __( 'Essentials', 'lunara-film' ), true, array( 'search-command', 'direct-matches', 'result-run', 'recovery' ) ); lunara_site_studio_render_spec_paths( $state, $spec, array( 'presentation.density', 'presentation.result_treatment', 'presentation.result_media', 'presentation.recovery_prominence', 'focus.lead', 'focus.spotlight' ) ); lunara_site_studio_render_details_close();
			lunara_site_studio_render_details_open( 'fine-tune', __( 'Fine Tune', 'lunara-film' ), false, array( 'result-run', 'recovery' ) ); lunara_site_studio_render_spec_paths( $state, $spec, array( 'geometry.section_gap', 'geometry.result_min_height', 'geometry.card_grid_min' ) ); lunara_site_studio_render_details_close();
			lunara_site_studio_render_details_open( 'mobile', __( 'Mobile', 'lunara-film' ), false, array( 'search-command', 'result-run', 'recovery' ) ); echo '<p>' . esc_html__( 'The same bounded geometry is previewed at the real 390px mobile width.', 'lunara-film' ) . '</p>'; lunara_site_studio_render_details_close();
		} else {
			$spec = lunara_site_studio_footer_spec();
			lunara_site_studio_render_details_open( 'essentials', __( 'Essentials', 'lunara-film' ), true, array( 'footer' ) ); lunara_site_studio_render_spec_paths( $state, $spec, array( 'brand.show_logo', 'brand.tagline', 'copyright.name' ) ); lunara_site_studio_render_details_close();
			lunara_site_studio_render_details_open( 'fine-tune', __( 'Fine Tune', 'lunara-film' ), false, array( 'footer' ) ); lunara_site_studio_render_spec_paths( $state, $spec, array( 'columns.editorial', 'columns.oscars', 'columns.utility' ) ); lunara_site_studio_render_details_close();
			lunara_site_studio_render_details_open( 'mobile', __( 'Mobile', 'lunara-film' ), false, array( 'footer' ) ); echo '<p>' . esc_html__( 'Footer columns collapse automatically in the mobile preview.', 'lunara-film' ) . '</p>'; lunara_site_studio_render_details_close();
		}
		lunara_site_studio_render_details_open( 'advanced', __( 'Advanced', 'lunara-film' ) ); echo '<button type="button" data-action="reset-candidate" disabled>' . esc_html__( 'Reset candidate', 'lunara-film' ) . '</button>';
		if ( 'review-single' === $surface_id ) { $review_url = function_exists( 'lunara_core_review_studio_admin_url' ) ? lunara_core_review_studio_admin_url() : admin_url( 'edit.php?post_type=review' ); $review_url = lunara_site_studio_safe_admin_destination( $review_url ); if ( $review_url ) { echo '<a class="button" data-workspace-navigation href="' . esc_url( $review_url ) . '">' . esc_html__( 'Open Review Studio', 'lunara-film' ) . '</a>'; } }
		if ( 'utility-search' === $surface_id ) { echo '<p>' . esc_html__( 'The 404 recovery destination remains in Classic controls because this Search preview cannot show that route.', 'lunara-film' ) . '</p>'; }
		$classic = lunara_site_studio_safe_admin_destination( admin_url( $classic_url ) ); if ( $classic ) { echo '<a class="button" data-workspace-navigation href="' . esc_url( $classic ) . '">' . esc_html__( 'Open Classic controls', 'lunara-film' ) . '</a>'; } lunara_site_studio_render_details_close(); lunara_site_studio_render_revisions( $revisions );
	}
}

if ( ! function_exists( 'lunara_site_studio_archive_control_specs' ) ) {
	function lunara_site_studio_archive_control_specs( $surface_id ) {
		if ( 'reviews-archive' === $surface_id ) {
			return array(
				'presentation.density' => array( 'type' => 'select', 'allowed' => array( 'compact', 'editorial', 'showcase' ) ), 'presentation.lead_prominence' => array( 'type' => 'select', 'allowed' => array( 'restrained', 'standard', 'feature' ) ), 'presentation.rail_density' => array( 'type' => 'select', 'allowed' => array( 'compact', 'editorial', 'showcase' ) ),
				'item_count' => array( 'type' => 'int', 'min' => 4, 'max' => 24 ), 'presentation.section_gap' => array( 'type' => 'int', 'min' => 20, 'max' => 90 ), 'presentation.lead_min_height' => array( 'type' => 'int', 'min' => 340, 'max' => 640 ), 'presentation.card_min_height' => array( 'type' => 'int', 'min' => 260, 'max' => 540 ), 'presentation.compact_media_width' => array( 'type' => 'int', 'min' => 92, 'max' => 150 ),
			);
		}
		return array(
			'presentation.density' => array( 'type' => 'select', 'allowed' => array( 'compact', 'editorial', 'showcase' ) ), 'presentation.lead_prominence' => array( 'type' => 'select', 'allowed' => array( 'restrained', 'standard', 'feature' ) ), 'presentation.desk_rhythm' => array( 'type' => 'select', 'allowed' => array( 'quick', 'balanced', 'immersive' ) ),
			'item_count' => array( 'type' => 'int', 'min' => 4, 'max' => 24 ), 'presentation.section_gap' => array( 'type' => 'int', 'min' => 18, 'max' => 86 ), 'presentation.hero_min_height' => array( 'type' => 'int', 'min' => 160, 'max' => 420 ), 'presentation.card_min_height' => array( 'type' => 'int', 'min' => 280, 'max' => 560 ), 'presentation.media_min_height' => array( 'type' => 'int', 'min' => 160, 'max' => 360 ),
		);
	}
	function lunara_site_studio_state_path_value( $state, $path ) { foreach ( explode( '.', $path ) as $part ) { if ( ! is_array( $state ) || ! array_key_exists( $part, $state ) ) { return null; } $state = $state[ $part ]; } return $state; }
}

if ( ! function_exists( 'lunara_site_studio_render_archive_order' ) ) {
	function lunara_site_studio_render_archive_order( $state ) {
		$order_help = 'lunara-site-studio-section-order-help'; $order_error = 'lunara-site-studio-section-order-error'; $visibility_help = 'lunara-site-studio-section-visibility-help'; $visibility_error = 'lunara-site-studio-section-visibility-error';
		echo '<div class="lunara-site-studio-visibility-group" data-section-order-list data-error-key="section_order" tabindex="-1" aria-describedby="' . esc_attr( $order_help . ' ' . $order_error ) . '"><span class="description" id="' . esc_attr( $order_help ) . '">' . esc_html__( 'Move sections into the order visitors should see.', 'lunara-film' ) . '</span><span class="lunara-site-studio-error" id="' . esc_attr( $order_error ) . '" hidden></span><div data-section-visibility-group data-error-key="section_visibility" tabindex="-1" aria-describedby="' . esc_attr( $visibility_help . ' ' . $visibility_error ) . '"><span class="description" id="' . esc_attr( $visibility_help ) . '">' . esc_html__( 'Choose which sections appear on the public archive.', 'lunara-film' ) . '</span><span class="lunara-site-studio-error" id="' . esc_attr( $visibility_error ) . '" hidden></span><div data-section-order>';
		foreach ( $state['section_order'] as $index => $slug ) { $label = ucwords( str_replace( '-', ' ', $slug ) ); echo '<div class="lunara-site-studio-order-row" data-section-row data-section-control="' . esc_attr( $slug ) . '" data-slug="' . esc_attr( $slug ) . '"><strong>' . esc_html( $label ) . '</strong><label for="lunara-section-visible-' . esc_attr( $slug ) . '"><input id="lunara-section-visible-' . esc_attr( $slug ) . '" type="checkbox" data-field-path="section_visibility.' . esc_attr( $slug ) . '" data-section-visible="' . esc_attr( $slug ) . '" data-value-type="boolean" aria-describedby="' . esc_attr( $visibility_help . ' ' . $visibility_error ) . '"' . ( ! empty( $state['section_visibility'][ $slug ] ) ? ' checked' : '' ) . ' /> ' . esc_html__( 'Visible', 'lunara-film' ) . '</label><button type="button" data-section-move="earlier" aria-label="' . esc_attr( sprintf( __( 'Move %s earlier', 'lunara-film' ), $label ) ) . '"' . ( 0 === $index ? ' disabled' : '' ) . '>↑</button><button type="button" data-section-move="later" aria-label="' . esc_attr( sprintf( __( 'Move %s later', 'lunara-film' ), $label ) ) . '"' . ( count( $state['section_order'] ) - 1 === $index ? ' disabled' : '' ) . '>↓</button></div>'; }
		echo '</div></div></div>';
	}
}

if ( ! function_exists( 'lunara_site_studio_render_archive_inspector' ) ) {
	function lunara_site_studio_render_archive_inspector( $surface_id, $state, $revisions, $classic_url ) {
		lunara_site_studio_render_details_open( 'essentials', __( 'Essentials', 'lunara-film' ), true, 'reviews-archive' === $surface_id ? array( 'hero' ) : array( 'hero', 'deskbar' ) );
		lunara_site_studio_render_field( 'kicker', __( 'Kicker', 'lunara-film' ), $state['kicker'] ); lunara_site_studio_render_field( 'title', __( 'Title', 'lunara-film' ), $state['title'] ); lunara_site_studio_render_field( 'deck', __( 'Introduction', 'lunara-film' ), $state['deck'], 'textarea' ); lunara_site_studio_render_field( 'supporting_copy', __( 'Supporting copy', 'lunara-film' ), $state['supporting_copy'], 'textarea' ); lunara_site_studio_render_details_close();
		$specs = lunara_site_studio_archive_control_specs( $surface_id ); $paths = array_keys( $specs ); $split = 4;
		lunara_site_studio_render_details_open( 'fine-tune', __( 'Fine Tune', 'lunara-film' ), false, 'reviews-archive' === $surface_id ? array( 'grid', 'pagination', 'pairing-desk' ) : array( 'filters', 'toolbar', 'grid' ) ); foreach ( array_slice( $paths, 0, $split ) as $path ) { lunara_site_studio_render_control( $path, lunara_site_studio_state_path_value( $state, $path ), $specs[ $path ] ); } lunara_site_studio_render_details_close();
		lunara_site_studio_render_details_open( 'mobile', __( 'Mobile', 'lunara-film' ), false, array( 'grid' ) ); foreach ( array_slice( $paths, $split ) as $path ) { lunara_site_studio_render_control( $path, lunara_site_studio_state_path_value( $state, $path ), $specs[ $path ] ); } lunara_site_studio_render_details_close();
		lunara_site_studio_render_details_open( 'advanced', __( 'Advanced', 'lunara-film' ), false, 'reviews-archive' === $surface_id ? array( 'pairing-desk' ) : array( 'retention', 'pagination' ) ); echo '<button type="button" data-action="reset-candidate" disabled>' . esc_html__( 'Reset candidate', 'lunara-film' ) . '</button>'; $classic = lunara_site_studio_safe_admin_destination( admin_url( $classic_url ) ); if ( $classic ) { echo '<a class="button" data-workspace-navigation href="' . esc_url( $classic ) . '">' . esc_html__( 'Open full archive controls', 'lunara-film' ) . '</a>'; } lunara_site_studio_render_details_close(); lunara_site_studio_render_revisions( $revisions );
	}
}

if ( ! function_exists( 'lunara_site_studio_render_global_inspector' ) ) {
	function lunara_site_studio_render_global_inspector( $state, $revisions, $classic_url ) {
		$color_specs = function_exists( 'lunara_design_token_color_specs' ) ? lunara_design_token_color_specs() : array(); $color_labels = array(); foreach ( array( 'gold', 'gold_light', 'bg_primary', 'bg_secondary', 'text', 'text_muted' ) as $key ) { $color_labels[ $key ] = isset( $color_specs[ $key ]['label'] ) ? $color_specs[ $key ]['label'] : ucwords( str_replace( '_', ' ', $key ) ); }
		$font_specs = function_exists( 'lunara_design_token_font_role_specs' ) ? lunara_design_token_font_role_specs() : array(); $font_labels = array(); foreach ( array( 'body', 'display', 'signature', 'glamour', 'label' ) as $key ) { $font_labels[ $key ] = isset( $font_specs[ $key ]['label'] ) ? $font_specs[ $key ]['label'] : ucwords( $key ); }
		lunara_site_studio_render_details_open( 'essentials', __( 'Essentials', 'lunara-film' ), true ); echo '<div tabindex="-1" data-error-key="colors" aria-describedby="lunara-global-colors-error"><span id="lunara-global-colors-error" class="lunara-site-studio-error" hidden></span></div>'; foreach ( $color_labels as $key => $label ) { $role = $state['colors'][ $key ]; $effective_id = 'lunara-effective-colors-' . sanitize_html_class( $key ); lunara_site_studio_render_field( 'colors.' . $key, $label, null === $role['override'] ? '' : $role['override'], 'text', 'color_' . $key, $effective_id ); echo '<p id="' . esc_attr( $effective_id ) . '" class="lunara-site-studio-effective" data-effective-for="colors.' . esc_attr( $key ) . '"' . ' data-source-design-tokens="' . esc_attr__( 'Site Studio override', 'lunara-film' ) . '" data-source-customizer="' . esc_attr__( 'Customizer', 'lunara-film' ) . '" data-source-shipped-default="' . esc_attr__( 'Shipped default', 'lunara-film' ) . '"' . '>' . esc_html( $role['effective'] ) . ' · ' . esc_html( 'design-tokens' === $role['source'] ? __( 'Site Studio override', 'lunara-film' ) : ( 'customizer' === $role['source'] ? __( 'Customizer', 'lunara-film' ) : __( 'Shipped default', 'lunara-film' ) ) ) . '</p>'; } lunara_site_studio_render_details_close();
		lunara_site_studio_render_details_open( 'fine-tune', __( 'Fine Tune', 'lunara-film' ) ); echo '<div tabindex="-1" data-error-key="fonts" aria-describedby="lunara-global-fonts-error"><span id="lunara-global-fonts-error" class="lunara-site-studio-error" hidden></span></div>'; $font_choices = function_exists( 'lunara_design_token_font_choices' ) ? lunara_design_token_font_choices() : array(); foreach ( $font_labels as $key => $label ) { $role = $state['fonts'][ $key ]; $field_id = 'lunara-field-' . sanitize_html_class( str_replace( '.', '-', 'fonts.' . $key ) ); $error_id = $field_id . '-error'; $effective_id = 'lunara-effective-fonts-' . sanitize_html_class( $key ); echo '<div class="lunara-site-studio-field"><label for="' . esc_attr( $field_id ) . '">' . esc_html( $label ) . '</label><select id="' . esc_attr( $field_id ) . '" data-field-path="fonts.' . esc_attr( $key ) . '" data-error-key="font_' . esc_attr( $key ) . '" aria-describedby="' . esc_attr( $field_id . '-help ' . $effective_id . ' ' . $error_id ) . '"><option value="">' . esc_html__( 'Inherit', 'lunara-film' ) . '</option>'; foreach ( $font_choices as $choice_id => $choice ) { echo '<option value="' . esc_attr( $choice_id ) . '"' . ( $choice_id === $role['override'] ? ' selected' : '' ) . '>' . esc_html( $choice['label'] ) . '</option>'; } echo '</select><span id="' . esc_attr( $field_id . '-help' ) . '" class="description">' . esc_html__( 'Choose a supported face or inherit the shipped role.', 'lunara-film' ) . '</span><span id="' . esc_attr( $error_id ) . '" class="lunara-site-studio-error" hidden></span></div><p id="' . esc_attr( $effective_id ) . '" class="lunara-site-studio-effective" data-effective-for="fonts.' . esc_attr( $key ) . '"' . ' data-source-design-tokens="' . esc_attr__( 'Site Studio override', 'lunara-film' ) . '" data-source-customizer="' . esc_attr__( 'Customizer', 'lunara-film' ) . '" data-source-shipped-default="' . esc_attr__( 'Shipped default', 'lunara-film' ) . '"' . '>' . esc_html( isset( $font_choices[ $role['effective'] ] ) ? $font_choices[ $role['effective'] ]['label'] : $role['effective'] ) . ' · ' . esc_html( 'design-tokens' === $role['source'] ? __( 'Site Studio override', 'lunara-film' ) : ( 'customizer' === $role['source'] ? __( 'Customizer', 'lunara-film' ) : __( 'Shipped default', 'lunara-film' ) ) ) . '</p>'; } lunara_site_studio_render_details_close();
		lunara_site_studio_render_details_open( 'mobile', __( 'Mobile', 'lunara-film' ) ); echo '<p>' . esc_html__( 'These sitewide roles remain consistent on mobile.', 'lunara-film' ) . '</p>'; lunara_site_studio_render_details_close();
		lunara_site_studio_render_details_open( 'advanced', __( 'Advanced', 'lunara-film' ) ); echo '<button type="button" class="button" data-action="reset-overrides" disabled>' . esc_html__( 'Reset all Global overrides', 'lunara-film' ) . '</button><a class="button" data-workspace-navigation href="' . esc_url( lunara_site_studio_safe_admin_destination( admin_url( $classic_url ) ) ) . '">' . esc_html__( 'Open Classic controls', 'lunara-film' ) . '</a>'; lunara_site_studio_render_details_close();
		lunara_site_studio_render_revisions( $revisions );
	}
}

if ( ! function_exists( 'lunara_site_studio_homepage_labels' ) ) {
	function lunara_site_studio_homepage_specs() { $registry = function_exists( 'lunara_get_home_section_registry' ) ? lunara_get_home_section_registry() : array(); $safe = array(); foreach ( array( 'hero', 'latest-reviews', 'pairing-desk', 'dispatch', 'oscar-picks', 'oscar-facts' ) as $slug ) { $row = isset( $registry[ $slug ] ) && is_array( $registry[ $slug ] ) ? $registry[ $slug ] : array(); $safe[ $slug ] = array( 'label' => isset( $row['label'] ) ? $row['label'] : ucwords( str_replace( '-', ' ', $slug ) ), 'toggle_label' => isset( $row['toggle_label'] ) ? $row['toggle_label'] : __( 'Visible', 'lunara-film' ), 'description' => isset( $row['description'] ) ? $row['description'] : '' ); } return $safe; }
	function lunara_site_studio_homepage_labels() { $labels = array(); foreach ( lunara_site_studio_homepage_specs() as $slug => $spec ) { $labels[ $slug ] = $spec['label']; } return $labels; }
	function lunara_site_studio_render_home_order( $state ) { echo '<div class="lunara-site-studio-visibility-group" data-home-visibility-group data-error-key="visibility" tabindex="-1" aria-describedby="lunara-home-visibility-help lunara-home-visibility-error"><span id="lunara-home-visibility-help" class="description">' . esc_html__( 'Choose which canonical Homepage sections are visible.', 'lunara-film' ) . '</span><span id="lunara-home-visibility-error" class="lunara-site-studio-error" hidden></span><div data-home-order-list data-order-kind="desktop" data-order="' . esc_attr( implode( ',', $state['desktop_order'] ) ) . '">'; foreach ( $state['desktop_order'] as $slug ) { $spec = lunara_site_studio_homepage_specs()[ $slug ]; echo '<div class="lunara-site-studio-order-row" data-home-row data-slug="' . esc_attr( $slug ) . '"><span><strong>' . esc_html( $spec['label'] ) . '</strong><small>' . esc_html( $spec['description'] ) . '</small></span><label for="lunara-home-visible-' . esc_attr( $slug ) . '"><input id="lunara-home-visible-' . esc_attr( $slug ) . '" type="checkbox" data-field-path="visibility.' . esc_attr( $slug ) . '" data-home-visible="' . esc_attr( $slug ) . '" aria-describedby="lunara-home-visibility-help"' . ( ! empty( $state['visibility'][ $slug ] ) ? ' checked' : '' ) . ' /> ' . esc_html( $spec['toggle_label'] ) . '</label><button id="lunara-home-move-' . esc_attr( $slug ) . '-earlier" type="button" data-move="earlier" aria-label="' . esc_attr( sprintf( __( 'Move %s earlier', 'lunara-film' ), $spec['label'] ) ) . '" disabled>↑</button><button id="lunara-home-move-' . esc_attr( $slug ) . '-later" type="button" data-move="later" aria-label="' . esc_attr( sprintf( __( 'Move %s later', 'lunara-film' ), $spec['label'] ) ) . '" disabled>↓</button></div>'; } echo '</div></div><span id="lunara-home-desktop-order-error" class="lunara-site-studio-error" hidden></span><span id="lunara-home-mobile-order-error" class="lunara-site-studio-error" hidden></span>'; }
	function lunara_site_studio_render_home_inspector( $state, $revisions, $classic_url ) {
		echo '<input id="lunara-home-mode" type="hidden" data-home-mode data-field-path="mode" aria-label="' . esc_attr__( 'Homepage composition mode', 'lunara-film' ) . '" value="' . esc_attr( $state['mode'] ) . '" /><input id="lunara-home-front-page-id" type="hidden" data-home-front-page-id data-field-path="front_page_id" aria-label="' . esc_attr__( 'Homepage front page ID', 'lunara-film' ) . '" value="' . esc_attr( $state['front_page_id'] ) . '" />';
		lunara_site_studio_render_details_open( 'essentials', __( 'Essentials', 'lunara-film' ), true ); echo '<fieldset class="lunara-site-studio-presets" data-error-key="preset" tabindex="-1" aria-describedby="lunara-home-preset-help lunara-home-preset-error"><legend>' . esc_html__( 'Structure preset', 'lunara-film' ) . '</legend>'; $presets = function_exists( 'lunara_control_desk_homepage_order_preset_specs' ) ? lunara_control_desk_homepage_order_preset_specs() : array(); echo '<label for="lunara-home-preset-custom"><input id="lunara-home-preset-custom" type="radio" name="lunara_home_preset" data-field-path="preset" value=""' . ( '' === $state['preset'] ? ' checked' : '' ) . ' /> ' . esc_html__( 'Custom', 'lunara-film' ) . '</label>'; $managed = array_keys( lunara_site_studio_homepage_labels() ); foreach ( $presets as $key => $preset ) { $desktop = array_values( array_intersect( isset( $preset['desktop_order'] ) ? $preset['desktop_order'] : array(), $managed ) ); $mobile = array_values( array_intersect( isset( $preset['mobile_order'] ) ? $preset['mobile_order'] : array(), $managed ) ); if ( 6 !== count( array_unique( $desktop ) ) || 6 !== count( array_unique( $mobile ) ) ) { continue; } echo '<label for="lunara-home-preset-' . esc_attr( $key ) . '"><input id="lunara-home-preset-' . esc_attr( $key ) . '" type="radio" name="lunara_home_preset" data-field-path="preset" data-desktop-order="' . esc_attr( implode( ',', $desktop ) ) . '" data-mobile-order="' . esc_attr( implode( ',', $mobile ) ) . '" value="' . esc_attr( $key ) . '"' . ( $key === $state['preset'] ? ' checked' : '' ) . ' /> ' . esc_html( $preset['label'] ) . '</label>'; } echo '<span id="lunara-home-preset-help" class="description">' . esc_html__( 'Preset changes stay local until Preview or Save Live.', 'lunara-film' ) . '</span><span id="lunara-home-preset-error" class="lunara-site-studio-error" hidden></span></fieldset>'; lunara_site_studio_render_details_close();
		lunara_site_studio_render_details_open( 'fine-tune', __( 'Fine Tune', 'lunara-film' ) ); echo '<p>' . esc_html__( 'Desktop and tablet share the desktop order.', 'lunara-film' ) . '</p>'; lunara_site_studio_render_details_close();
		lunara_site_studio_render_details_open( 'mobile', __( 'Mobile', 'lunara-film' ) ); echo '<p>' . esc_html__( 'Choose Mobile width to edit the mobile order.', 'lunara-film' ) . '</p>'; lunara_site_studio_render_details_close();
		lunara_site_studio_render_details_open( 'advanced', __( 'Advanced', 'lunara-film' ) ); echo '<p tabindex="-1" data-error-key="front_page" aria-describedby="lunara-home-front-page-error">Mode: <strong>' . esc_html( $state['mode'] ) . '</strong> · Front page: <strong>' . esc_html( $state['front_page_id'] ) . '</strong><span id="lunara-home-front-page-error" class="lunara-site-studio-error" hidden></span></p><button type="button" data-action="reset-candidate" disabled>' . esc_html__( 'Reset candidate', 'lunara-film' ) . '</button><a class="button" data-workspace-navigation href="' . esc_url( lunara_site_studio_safe_admin_destination( admin_url( $classic_url ) ) ) . '">' . esc_html__( 'Open Classic controls', 'lunara-film' ) . '</a>'; lunara_site_studio_render_details_close(); lunara_site_studio_render_revisions( $revisions );
	}
}

if ( ! function_exists( 'lunara_site_studio_render_method_inspector' ) ) {
	function lunara_site_studio_render_method_inspector( $state, $revisions, $classic_url ) {
		lunara_site_studio_render_details_open( 'essentials', __( 'Essentials', 'lunara-film' ), true ); lunara_site_studio_render_field( 'kicker', __( 'Kicker', 'lunara-film' ), $state['kicker'] ); lunara_site_studio_render_field( 'title', __( 'Title', 'lunara-film' ), $state['title'] ); lunara_site_studio_render_field( 'copy', __( 'Supporting copy', 'lunara-film' ), $state['copy'], 'textarea' ); lunara_site_studio_render_details_close();
		lunara_site_studio_render_details_open( 'fine-tune', __( 'Fine Tune', 'lunara-film' ) ); echo '<label for="lunara-method-review">' . esc_html__( 'Featured Review', 'lunara-film' ) . '</label><select id="lunara-method-review" data-field-path="review_id" data-error-key="review_id" aria-describedby="lunara-method-review-help lunara-method-review-error"><option value="0">' . esc_html__( 'Automatic', 'lunara-film' ) . '</option>'; foreach ( get_posts( array( 'post_type' => 'review', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) ) as $review ) { echo '<option value="' . esc_attr( $review->ID ) . '"' . ( absint( $state['review_id'] ) === absint( $review->ID ) ? ' selected' : '' ) . '>' . esc_html( get_the_title( $review ) . ' — ' . get_the_date( '', $review ) ) . '</option>'; } echo '</select><span id="lunara-method-review-help" class="description">' . esc_html__( 'Choose a published Review or Automatic.', 'lunara-film' ) . '</span><span id="lunara-method-review-error" class="lunara-site-studio-error" hidden></span>';
		echo '<div class="lunara-site-studio-media"><label for="lunara-method-backdrop">' . esc_html__( 'Backdrop', 'lunara-film' ) . '</label><input id="lunara-method-backdrop" type="hidden" data-field-path="backdrop_id" value="' . esc_attr( absint( $state['backdrop_id'] ) ) . '" /><button id="lunara-method-choose-backdrop" type="button" data-action="choose-media" aria-controls="lunara-method-backdrop" data-error-key="backdrop_id" aria-describedby="lunara-method-backdrop-help lunara-method-backdrop-error" disabled>' . esc_html__( 'Choose backdrop', 'lunara-film' ) . '</button><button type="button" data-action="clear-media" disabled>' . esc_html__( 'Clear backdrop', 'lunara-film' ) . '</button><span id="lunara-method-backdrop-help" class="description">' . esc_html__( 'Choose a local media-library attachment.', 'lunara-film' ) . '</span><span id="lunara-method-backdrop-error" class="lunara-site-studio-error" hidden></span>'; $thumb = lunara_site_studio_safe_local_thumbnail( wp_get_attachment_image_url( absint( $state['backdrop_id'] ), 'thumbnail' ) ); if ( $thumb ) { echo '<img data-attachment-id="' . esc_attr( absint( $state['backdrop_id'] ) ) . '" src="' . esc_url( $thumb ) . '" alt="" loading="lazy" decoding="async" />'; } echo '</div>'; lunara_site_studio_render_details_close();
		lunara_site_studio_render_details_open( 'mobile', __( 'Mobile', 'lunara-film' ) ); echo '<p>' . esc_html__( 'Copy and imagery use the same canonical values on mobile.', 'lunara-film' ) . '</p>'; lunara_site_studio_render_details_close();
		lunara_site_studio_render_details_open( 'advanced', __( 'Advanced', 'lunara-film' ) ); echo '<button type="button" data-action="reset-candidate" disabled>' . esc_html__( 'Reset candidate', 'lunara-film' ) . '</button><a class="button" data-workspace-navigation href="' . esc_url( lunara_site_studio_safe_admin_destination( admin_url( $classic_url ) ) ) . '">' . esc_html__( 'Open Classic controls', 'lunara-film' ) . '</a>'; lunara_site_studio_render_details_close(); lunara_site_studio_render_revisions( $revisions );
	}
}

if ( ! function_exists( 'lunara_site_studio_render_revisions' ) ) {
	function lunara_site_studio_revision_action_label( $action ) {
		if ( 'save' === $action ) { return __( 'Saved live', 'lunara-film' ); }
		if ( 'restore-safety' === $action ) { return __( 'Safety snapshot', 'lunara-film' ); }
		if ( 'restore' === $action ) { return __( 'Restored revision', 'lunara-film' ); }
		return __( 'Revision', 'lunara-film' );
	}
	function lunara_site_studio_render_revisions( $revisions ) {
		echo '<details class="lunara-site-studio-details" data-section="revision-history" data-revision-history><summary data-revision-summary>' . esc_html__( 'Revision History', 'lunara-film' ) . '</summary><div class="lunara-site-studio-details-body"><div data-revision-list>';
		if ( ! $revisions ) { echo '<p data-revision-empty>' . esc_html__( 'No revisions yet.', 'lunara-film' ) . '</p>'; }
		foreach ( $revisions as $revision ) { echo '<div class="lunara-site-studio-revision" data-revision-row><span data-revision-label>' . esc_html( $revision['timestamp'] . ' · ' . lunara_site_studio_revision_action_label( $revision['action'] ) ) . '</span><button type="button" data-action="restore" data-revision-id="' . esc_attr( $revision['id'] ) . '" disabled>' . esc_html__( 'Restore', 'lunara-film' ) . '</button></div>'; }
		echo '</div></div></details>';
	}
}

if ( ! function_exists( 'lunara_render_site_studio_page' ) ) {
	function lunara_render_site_studio_page() {
		if ( ! current_user_can( 'edit_theme_options' ) ) { wp_die( esc_html__( 'You do not have permission to access Lunara Site Studio.', 'lunara-film' ) ); }
		$surfaces = lunara_site_studio_authorized_surfaces(); $active_id = lunara_site_studio_workspace_surface( $surfaces ); if ( '' === $active_id ) { wp_die( esc_html__( 'This Site Studio destination is unavailable.', 'lunara-film' ) ); }
		$active = $surfaces[ $active_id ]; $editable = lunara_site_studio_workspace_editable_surfaces(); $availability = lunara_site_studio_surface_availability( $active ); $adapter = null; $state = null; $revisions = array(); $workspace_error = false;
		$workspace_eligible = ! empty( $availability['available'] ) && in_array( $active_id, $editable, true );
		$workspace_urls = $workspace_eligible ? lunara_site_studio_workspace_urls( $active ) : array();
		$workspace_config = ! empty( $workspace_urls ) && function_exists( 'lunara_site_studio_preview_pilots' ) && function_exists( 'lunara_site_studio_preview_instance_query_arg' ) ? lunara_site_studio_workspace_config( $active_id, $active ) : array();
		$transport_safe = ! empty( $workspace_urls ) && lunara_site_studio_workspace_config_is_safe( $workspace_config ); $usable = $workspace_eligible && $transport_safe;
		if ( $usable ) { $adapter = lunara_site_studio_get_adapter( $active_id ); $state = lunara_site_studio_call_adapter( $adapter, 'read_state' ); $listed = lunara_site_studio_call_adapter( $adapter, 'list_revisions' ); if ( ! is_wp_error( $state ) ) { $state = lunara_site_studio_project_state( $active_id, $state ); } if ( is_wp_error( $adapter ) || is_wp_error( $state ) || is_wp_error( $listed ) || ! is_array( $state ) || ! is_array( $listed ) ) { $usable = false; $workspace_error = true; } else { $revisions = lunara_site_studio_safe_revisions( $listed ); } } if ( ! $transport_safe && $workspace_eligible ) { $workspace_error = true; }
		?>
		<div class="wrap lunara-site-studio" data-lunara-site-studio data-surface="<?php echo esc_attr( $active_id ); ?>" data-workspace-state="recovery" data-dirty="false">
			<header class="lunara-site-studio-header"><div><p class="lunara-site-studio-kicker"><?php esc_html_e( 'Lunara · Visual Site Map', 'lunara-film' ); ?></p><h1><?php esc_html_e( 'Site Studio', 'lunara-film' ); ?></h1><p><?php esc_html_e( 'Choose a surface, inspect its live state, and make an explicit preview or save.', 'lunara-film' ); ?></p></div></header>
			<section class="lunara-site-studio-map" aria-labelledby="lunara-site-map-heading"><h2 id="lunara-site-map-heading"><?php esc_html_e( 'Visual Site Map', 'lunara-film' ); ?></h2><label for="lunara-site-studio-search"><?php esc_html_e( 'Search destinations', 'lunara-film' ); ?></label><input id="lunara-site-studio-search" type="search" data-lunara-surface-search /><p data-lunara-search-count aria-live="polite"><?php echo esc_html( sprintf( __( '%d destinations', 'lunara-film' ), count( $surfaces ) ) ); ?></p><div class="lunara-site-studio-map-grid">
			<?php foreach ( $surfaces as $id => $surface ) : $status = lunara_site_studio_surface_availability( $surface ); $section_terms = array(); foreach ( $surface['sections'] as $section ) { $section_terms[] = $section; $section_terms[] = ucwords( str_replace( '-', ' ', $section ) ); } $search = implode( ' ', array_merge( array( $surface['label'], $surface['group'], $surface['description'] ), $surface['aliases'], $section_terms ) ); ?>
				<?php $icons = array( 'global-design' => 'admin-customizer', 'site-footer' => 'align-center', 'homepage-structure' => 'admin-home', 'lunara-method' => 'heart', 'reviews-archive' => 'star-filled', 'review-single' => 'media-document', 'journal-archive' => 'edit-page', 'utility-search' => 'search', 'oscars-portal' => 'awards', 'core-review-studio' => 'welcome-write-blog', 'journal-workflow' => 'randomize', 'dispatch-automation' => 'controls-repeat' ); $method_thumb = 'lunara-method' === $id && 'lunara-method' === $active_id && $usable ? lunara_site_studio_safe_local_thumbnail( wp_get_attachment_image_url( absint( $state['backdrop_id'] ), 'thumbnail' ) ) : ''; $card_url = lunara_site_studio_safe_admin_destination( lunara_site_studio_admin_url( $id ) ); ?><a class="lunara-site-studio-card lunara-site-studio-card--<?php echo esc_attr( $id ); ?><?php echo $id === $active_id ? ' is-active' : ''; ?>" data-lunara-surface-card data-surface="<?php echo esc_attr( $id ); ?>" data-search-index="<?php echo esc_attr( strtolower( $search ) ); ?>" href="<?php echo esc_url( $card_url ); ?>"<?php echo $id === $active_id ? ' aria-current="page"' : ''; ?>><?php if ( $method_thumb ) : ?><img class="lunara-site-studio-card-thumb" src="<?php echo esc_url( $method_thumb ); ?>" alt="" loading="lazy" decoding="async" /><?php else : ?><span class="dashicons dashicons-<?php echo esc_attr( isset( $icons[ $id ] ) ? $icons[ $id ] : 'admin-generic' ); ?>" aria-hidden="true"></span><?php endif; ?><small><?php echo esc_html( $surface['group'] ); ?></small><strong><?php echo esc_html( $surface['label'] ); ?></strong><span><?php echo esc_html( $surface['description'] ); ?></span><em><?php echo esc_html( ! empty( $status['available'] ) ? __( 'Available', 'lunara-film' ) : __( 'Unavailable', 'lunara-film' ) ); ?></em></a>
			<?php endforeach; ?></div></section>
			<?php if ( ! $usable ) : ?><section class="lunara-site-studio-handoff"><h2><?php echo esc_html( $active['label'] ); ?></h2><?php if ( $workspace_error ) : ?><p><?php esc_html_e( 'This focused workspace could not be loaded safely. Use its Classic controls and try again later.', 'lunara-film' ); ?></p><?php elseif ( empty( $availability['available'] ) ) : ?><p><?php echo esc_html( $availability['message'] ); ?></p><?php else : ?><p><?php esc_html_e( 'This available destination remains in its purpose-built Classic controls while its focused workspace is prepared.', 'lunara-film' ); ?></p><?php endif; ?><?php $handoff_url = ! empty( $active['classic_url'] ) ? lunara_site_studio_safe_admin_destination( admin_url( $active['classic_url'] ) ) : ''; if ( $handoff_url ) : ?><a class="button" href="<?php echo esc_url( $handoff_url ); ?>"><?php esc_html_e( 'Open Classic controls', 'lunara-film' ); ?></a><?php endif; ?></section>
			<?php else : $route = $workspace_urls['preview']; ?>
			<script type="application/json" id="lunara-site-studio-state"><?php echo wp_json_encode( $state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>
			<div class="lunara-site-studio-workspace">
				<aside class="lunara-site-studio-section-rail" aria-label="<?php echo esc_attr__( 'Site sections', 'lunara-film' ); ?>"><h2><?php esc_html_e( 'Sections', 'lunara-film' ); ?></h2><?php if ( 'homepage-structure' === $active_id ) { lunara_site_studio_render_home_order( $state ); } elseif ( in_array( $active_id, array( 'reviews-archive', 'journal-archive' ), true ) ) { lunara_site_studio_render_archive_order( $state ); } else { ?><ol><?php foreach ( $active['sections'] as $section ) : ?><li data-section-control="<?php echo esc_attr( $section ); ?>" tabindex="-1"><?php echo esc_html( ucwords( str_replace( '-', ' ', $section ) ) ); ?></li><?php endforeach; ?></ol><?php } ?></aside>
				<section class="lunara-site-studio-preview" aria-label="<?php echo esc_attr__( 'Live preview', 'lunara-film' ); ?>"><div class="lunara-site-studio-widths" aria-label="<?php echo esc_attr__( 'Preview width', 'lunara-film' ); ?>"><button id="lunara-preview-desktop" type="button" data-preview-width="desktop"<?php echo 'homepage-structure' === $active_id ? ' data-error-key="desktop_order" aria-describedby="lunara-home-desktop-order-error"' : ''; ?> aria-pressed="true"><?php esc_html_e( 'Desktop', 'lunara-film' ); ?></button><button type="button" data-preview-width="tablet" aria-pressed="false"><?php esc_html_e( 'Tablet', 'lunara-film' ); ?></button><button id="lunara-preview-mobile" type="button" data-preview-width="mobile"<?php echo 'homepage-structure' === $active_id ? ' data-error-key="mobile_order" aria-describedby="lunara-home-mobile-order-error"' : ''; ?> aria-pressed="false"><?php esc_html_e( 'Mobile', 'lunara-film' ); ?></button></div><div class="lunara-site-studio-preview-viewport"><div class="lunara-site-studio-preview-flow"><div class="lunara-site-studio-preview-canvas"><iframe title="<?php echo esc_attr( sprintf( __( '%s live preview', 'lunara-film' ), $active['label'] ) ); ?>" src="<?php echo esc_url( $route ); ?>" width="1440" height="900" referrerpolicy="no-referrer" sandbox="allow-scripts allow-same-origin"></iframe></div></div></div></section>
				<aside class="lunara-site-studio-inspector" aria-label="<?php echo esc_attr__( 'Settings inspector', 'lunara-film' ); ?>" aria-busy="false"><h2><?php echo esc_html( $active['label'] ); ?></h2><?php if ( 'global-design' === $active_id ) { lunara_site_studio_render_global_inspector( $state, $revisions, $active['classic_url'] ); } elseif ( 'homepage-structure' === $active_id ) { lunara_site_studio_render_home_inspector( $state, $revisions, $active['classic_url'] ); } elseif ( 'lunara-method' === $active_id ) { lunara_site_studio_render_method_inspector( $state, $revisions, $active['classic_url'] ); } elseif ( in_array( $active_id, array( 'reviews-archive', 'journal-archive' ), true ) ) { lunara_site_studio_render_archive_inspector( $active_id, $state, $revisions, $active['classic_url'] ); } else { lunara_site_studio_render_mod_surface_inspector( $active_id, $state, $revisions, $active['classic_url'] ); } ?><div class="lunara-site-studio-actions"><button type="button" class="button" data-action="preview" disabled><?php esc_html_e( 'Preview Changes', 'lunara-film' ); ?></button><button type="button" class="button button-primary" data-action="save" disabled><?php esc_html_e( 'Save Live', 'lunara-film' ); ?></button><button type="button" class="button" data-action="discard" disabled><?php esc_html_e( 'Discard', 'lunara-film' ); ?></button></div><p class="lunara-site-studio-status" data-workspace-status aria-live="polite"><?php esc_html_e( 'This focused workspace could not be loaded safely. Use its Classic controls and try again later.', 'lunara-film' ); ?></p></aside>
			</div><?php endif; ?>
		</div><?php
	}
}
