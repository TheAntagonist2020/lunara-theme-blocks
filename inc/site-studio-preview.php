<?php
/** Private, request-local Site Studio preview consumption and child bridge bootstrap. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'lunara_site_studio_preview_instance_query_arg' ) ) {
	function lunara_site_studio_preview_instance_query_arg() { return 'lunara_site_studio_instance'; }
}

if ( ! function_exists( 'lunara_site_studio_preview_pilots' ) ) {
	function lunara_site_studio_preview_pilots() {
		return array(
			'global-design' => array( 'owner' => 'theme:global-design', 'query' => 'lunara_global_design_preview', 'route' => '/', 'params' => array(), 'storage' => 'site-studio', 'markers' => array() ),
			'homepage-structure' => array( 'owner' => 'theme:homepage-structure', 'query' => 'lunara_homepage_preview', 'route' => '/', 'params' => array(), 'storage' => 'site-studio', 'markers' => array( 'hero', 'latest-reviews', 'pairing-desk', 'dispatch', 'oscar-picks', 'oscar-facts' ) ),
			'lunara-method' => array( 'owner' => 'theme:lunara-method', 'query' => 'lunara_method_preview', 'route' => '/', 'params' => array(), 'storage' => 'site-studio', 'markers' => array( 'pairing-desk' ) ),
			'reviews-archive' => array( 'owner' => 'theme:reviews-archive', 'query' => 'lunara_reviews_preview', 'route' => '/reviews/', 'params' => array(), 'storage' => 'provider', 'preview_callback' => 'lunara_reviews_archive_studio_get_preview_config', 'markers' => array( 'hero', 'grid', 'pagination', 'pairing-desk' ) ),
			'journal-archive' => array( 'owner' => 'theme:journal-archive', 'query' => 'lunara_journal_preview', 'route' => '/journal/', 'params' => array(), 'storage' => 'provider', 'preview_callback' => 'lunara_journal_archive_studio_get_preview_config', 'markers' => array( 'hero', 'deskbar', 'filters', 'toolbar', 'grid', 'retention', 'pagination' ) ),
			'review-single' => array( 'owner' => 'theme:review-single', 'query' => 'lunara_review_single_preview', 'route' => '/reviews/sinners-2025/', 'params' => array(), 'storage' => 'site-studio', 'markers' => array( 'hero', 'criticism', 'debrief', 'pair-it-with' ) ),
			'utility-search' => array( 'owner' => 'theme:utility-search', 'query' => 'lunara_utility_search_preview', 'route' => '/search/', 'params' => array( 'q' => 'Lunara' ), 'storage' => 'site-studio', 'markers' => array( 'search-command', 'direct-matches', 'result-run', 'recovery' ) ),
			'site-footer' => array( 'owner' => 'theme:site-footer', 'query' => 'lunara_footer_preview', 'route' => '/', 'params' => array(), 'storage' => 'site-studio', 'markers' => array( 'footer' ) ),
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_preview_uuid' ) ) {
	function lunara_site_studio_preview_uuid( $value ) { return is_string( $value ) && 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $value ); }
	function lunara_site_studio_preview_instance( $value ) { return is_string( $value ) && strlen( $value ) <= 80 && 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}:[1-9][0-9]*$/D', $value ); }
}

if ( ! function_exists( 'lunara_site_studio_preview_key_shape' ) ) {
	function lunara_site_studio_preview_key_shape( $raw_query ) {
		$keys = array( lunara_site_studio_preview_instance_query_arg() );
		foreach ( lunara_site_studio_preview_pilots() as $pilot ) { $keys[] = $pilot['query']; }
		foreach ( explode( '&', (string) $raw_query ) as $segment ) {
			$raw_key = explode( '=', $segment, 2 )[0]; $decoded = urldecode( $raw_key );
			$form_key = str_replace( array( '.', ' ' ), '_', strtolower( $decoded ) );
			$sanitized = preg_replace( '/[^a-z0-9_\-]/', '', $form_key );
			foreach ( $keys as $key ) { if ( $form_key === $key || $sanitized === $key || 0 === strpos( $form_key, $key . '[' ) || 0 === strpos( $sanitized, $key . '[' ) ) { return true; } }
		}
		return false;
	}
}

if ( ! function_exists( 'lunara_site_studio_preview_exact_query' ) ) {
	function lunara_site_studio_preview_exact_query( $raw_query, $expected_count = 2 ) {
		$segments = explode( '&', (string) $raw_query );
		$count = count( $segments );
		if ( ! is_array( $_GET ) || in_array( '', $segments, true ) || ( null !== $expected_count && ( (int) $expected_count !== count( $_GET ) || (int) $expected_count !== $count ) ) || ( null === $expected_count && ( $count < 2 || $count > 3 || count( $_GET ) !== $count ) ) ) { return false; }
		$values = array();
		foreach ( $segments as $segment ) {
			if ( 1 !== substr_count( $segment, '=' ) ) { return false; }
			list( $raw_key, $raw_value ) = explode( '=', $segment, 2 );
			if ( '' === $raw_key || '' === $raw_value || preg_match( '/%(?![0-9A-Fa-f]{2})/', $raw_key . $raw_value ) ) { return false; }
			$key = rawurldecode( $raw_key ); $value = rawurldecode( $raw_value );
			if ( $raw_key !== $key || isset( $values[ $key ] ) || ! array_key_exists( $key, $_GET ) || ! is_string( $_GET[ $key ] ) || $value !== wp_unslash( $_GET[ $key ] ) ) { return false; }
			$values[ $key ] = $value;
		}
		return $values;
	}
}

if ( ! function_exists( 'lunara_site_studio_preview_request_origin' ) ) {
	function lunara_site_studio_preview_request_origin() {
		$home = home_url( '/' ); $home_scheme = strtolower( (string) wp_parse_url( $home, PHP_URL_SCHEME ) ); $home_host = strtolower( (string) wp_parse_url( $home, PHP_URL_HOST ) ); $home_port = wp_parse_url( $home, PHP_URL_PORT ); if ( ! $home_port ) { $home_port = 'https' === $home_scheme ? 443 : 80; }
		$scheme = ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( (string) $_SERVER['HTTPS'] ) ? 'https' : 'http'; $host_value = isset( $_SERVER['HTTP_HOST'] ) ? (string) $_SERVER['HTTP_HOST'] : ''; $request_url = $scheme . '://' . $host_value . ( isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '' );
		$request_host = strtolower( (string) wp_parse_url( $request_url, PHP_URL_HOST ) ); $request_port = wp_parse_url( $request_url, PHP_URL_PORT ); if ( ! $request_port ) { $request_port = 'https' === $scheme ? 443 : 80; }
		return in_array( $home_scheme, array( 'http', 'https' ), true ) && '' !== $home_host && null === wp_parse_url( $home, PHP_URL_USER ) && null === wp_parse_url( $home, PHP_URL_PASS ) && null === wp_parse_url( $home, PHP_URL_FRAGMENT ) && null === wp_parse_url( $home, PHP_URL_QUERY ) && null === wp_parse_url( $request_url, PHP_URL_USER ) && null === wp_parse_url( $request_url, PHP_URL_PASS ) && null === wp_parse_url( $request_url, PHP_URL_FRAGMENT ) && $scheme === $home_scheme && $request_host === $home_host && (int) $request_port === (int) $home_port;
	}
	function lunara_site_studio_preview_request_path( $route = '/' ) {
		$home = home_url( '/' ); $scheme = ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( (string) $_SERVER['HTTPS'] ) ? 'https' : 'http'; $host_value = isset( $_SERVER['HTTP_HOST'] ) ? (string) $_SERVER['HTTP_HOST'] : ''; $request_url = $scheme . '://' . $host_value . ( isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '' );
		$home_path = (string) wp_parse_url( $home, PHP_URL_PATH ); if ( '' === $home_path ) { $home_path = '/'; } $request_path = (string) wp_parse_url( $request_url, PHP_URL_PATH );
		$request_query = (string) wp_parse_url( $request_url, PHP_URL_QUERY ); $raw_query = isset( $_SERVER['QUERY_STRING'] ) ? (string) $_SERVER['QUERY_STRING'] : '';
		$route = lunara_site_studio_normalize_preview_route( $route );
		$expected_path = '/' === $route ? $home_path : rtrim( $home_path, '/' ) . '/' . ltrim( $route, '/' );
		return '' !== $route && $request_query === $raw_query && $request_path === $expected_path;
	}
}

if ( ! function_exists( 'lunara_site_studio_preview_install_state' ) ) {
	function lunara_site_studio_preview_state_safe( $surface_id, $state ) {
		if ( ! is_array( $state ) ) { return false; }
		if ( 'global-design' === $surface_id ) {
			if ( array( 'colors', 'fonts' ) !== array_keys( $state ) || ! is_array( $state['colors'] ) || ! is_array( $state['fonts'] ) || array( 'gold', 'gold_light', 'bg_primary', 'bg_secondary', 'text', 'text_muted' ) !== array_keys( $state['colors'] ) || array( 'body', 'display', 'signature', 'glamour', 'label' ) !== array_keys( $state['fonts'] ) ) { return false; }
			foreach ( $state['colors'] as $item ) { if ( ! is_array( $item ) || array( 'override', 'effective', 'source' ) !== array_keys( $item ) || ( null !== $item['override'] && ( ! is_string( $item['override'] ) || 1 !== preg_match( '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/D', $item['override'] ) ) ) || ! is_string( $item['effective'] ) || 1 !== preg_match( '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/D', $item['effective'] ) || ! in_array( $item['source'], array( 'design-tokens', 'customizer', 'shipped-default' ), true ) || ( null !== $item['override'] && ( 'design-tokens' !== $item['source'] || strtolower( $item['effective'] ) !== strtolower( $item['override'] ) ) ) ) { return false; } }
			foreach ( $state['fonts'] as $item ) { if ( ! is_array( $item ) || array( 'override', 'effective', 'source' ) !== array_keys( $item ) || ( null !== $item['override'] && ! is_string( $item['override'] ) ) || ! is_string( $item['effective'] ) || ! in_array( $item['source'], array( 'design-tokens', 'shipped-default' ), true ) || ( null !== $item['override'] && ( 'design-tokens' !== $item['source'] || $item['effective'] !== $item['override'] ) ) ) { return false; } } return true;
		}
		if ( 'homepage-structure' === $surface_id ) { $slugs = array( 'hero', 'latest-reviews', 'pairing-desk', 'dispatch', 'oscar-picks', 'oscar-facts' ); if ( array( 'mode', 'front_page_id', 'preset', 'desktop_order', 'mobile_order', 'visibility' ) !== array_keys( $state ) || ! in_array( $state['mode'], array( 'registry', 'blocks' ), true ) || ! is_int( $state['front_page_id'] ) || ! is_string( $state['preset'] ) || ! is_array( $state['desktop_order'] ) || ! is_array( $state['mobile_order'] ) || ! is_array( $state['visibility'] ) || array_values( $state['desktop_order'] ) !== $state['desktop_order'] || array_values( $state['mobile_order'] ) !== $state['mobile_order'] || 6 !== count( array_unique( $state['desktop_order'] ) ) || 6 !== count( array_unique( $state['mobile_order'] ) ) || array() !== array_diff( $slugs, $state['desktop_order'] ) || array() !== array_diff( $state['desktop_order'], $slugs ) || array() !== array_diff( $slugs, $state['mobile_order'] ) || array() !== array_diff( $state['mobile_order'], $slugs ) || $slugs !== array_keys( $state['visibility'] ) ) { return false; } foreach ( $state['visibility'] as $visible ) { if ( ! is_bool( $visible ) ) { return false; } } return true; }
		if ( 'lunara-method' === $surface_id ) { return array( 'kicker', 'title', 'copy', 'review_id', 'backdrop_id' ) === array_keys( $state ) && is_string( $state['kicker'] ) && is_string( $state['title'] ) && is_string( $state['copy'] ) && is_int( $state['review_id'] ) && 0 <= $state['review_id'] && is_int( $state['backdrop_id'] ) && 0 <= $state['backdrop_id']; }
		$validators = array(
			'review-single' => 'lunara_site_studio_review_single_validate_state',
			'utility-search' => 'lunara_site_studio_utility_search_validate_state',
			'site-footer' => 'lunara_site_studio_footer_validate_state',
		);
		if ( isset( $validators[ $surface_id ] ) && is_callable( $validators[ $surface_id ] ) ) {
			$validated = call_user_func( $validators[ $surface_id ], $state );
			return is_array( $validated ) && $validated === $state;
		}
		return false;
	}
	function lunara_site_studio_preview_install_state( $surface_id, $state, $front_page_id ) {
		if ( ! lunara_site_studio_preview_state_safe( $surface_id, $state ) ) { return false; }
		if ( 'global-design' === $surface_id ) {
			$tokens = array( 'colors' => array(), 'fonts' => array() ); foreach ( array( 'colors', 'fonts' ) as $group ) { foreach ( $state[ $group ] as $key => $item ) { if ( null !== $item['override'] ) { $tokens[ $group ][ $key ] = $item['override']; } } }
			add_filter( 'pre_option_lunara_design_tokens', static function () use ( $tokens ) { return $tokens; }, PHP_INT_MAX ); return true;
		}
		if ( 'lunara-method' === $surface_id ) {
			$map = array( 'kicker' => 'lunara_home_pairing_desk_kicker', 'title' => 'lunara_home_pairing_desk_title', 'copy' => 'lunara_home_pairing_desk_copy', 'review_id' => 'lunara_home_pairing_desk_review_id', 'backdrop_id' => 'lunara_home_pairing_desk_backdrop_id' );
			foreach ( $map as $field => $mod ) { $value = $state[ $field ]; add_filter( 'theme_mod_' . $mod, static function () use ( $value ) { return $value; }, PHP_INT_MAX ); } return true;
		}
		$spec_callbacks = array(
			'review-single' => 'lunara_site_studio_review_single_spec',
			'utility-search' => 'lunara_site_studio_utility_search_spec',
			'site-footer' => 'lunara_site_studio_footer_spec',
		);
		if ( isset( $spec_callbacks[ $surface_id ] ) && is_callable( $spec_callbacks[ $surface_id ] ) ) {
			$spec = call_user_func( $spec_callbacks[ $surface_id ] );
			$mods = lunara_site_studio_mod_surface_desired_snapshot( $state, $spec );
			foreach ( $mods as $mod => $entry ) { $value = $entry['value']; add_filter( 'theme_mod_' . $mod, static function () use ( $value ) { return $value; }, PHP_INT_MAX ); }
			return true;
		}
		if ( 'homepage-structure' !== $surface_id || absint( $state['front_page_id'] ) !== absint( $front_page_id ) ) { return false; }
		$mods = array( 'lunara_home_section_order_preset' => $state['preset'], 'lunara_home_section_order' => implode( ',', $state['desktop_order'] ), 'lunara_home_section_mobile_order' => implode( ',', $state['mobile_order'] ) ); foreach ( $state['visibility'] as $slug => $visible ) { $mods[ 'lunara_home_show_' . str_replace( '-', '_', $slug ) ] = $visible ? '1' : '0'; }
		foreach ( $mods as $mod => $value ) { add_filter( 'theme_mod_' . $mod, static function () use ( $value ) { return $value; }, PHP_INT_MAX ); }
		if ( 'blocks' === $state['mode'] ) {
			$current = (string) get_post_field( 'post_content', $front_page_id ); $order = array_values( array_filter( $state['desktop_order'], static function ( $slug ) use ( $state ) { return ! empty( $state['visibility'][ $slug ] ); } ) ); $composed = lunara_compose_home_section_blocks( $current, $order ); if ( is_wp_error( $composed ) || absint( get_option( 'page_on_front' ) ) !== absint( $front_page_id ) ) { return false; }
			add_filter( 'lunara_home_front_page_content', static function ( $content, $page_id ) use ( $composed, $front_page_id ) { return absint( $page_id ) === absint( $front_page_id ) ? $composed : $content; }, PHP_INT_MAX, 2 );
		}
		return true;
	}
}

if ( ! function_exists( 'lunara_site_studio_resolve_private_preview' ) ) {
	function lunara_site_studio_resolve_private_preview() {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== $_SERVER['REQUEST_METHOD'] || ! lunara_site_studio_preview_request_origin() ) { return false; }
		$values = lunara_site_studio_preview_exact_query( isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '', null ); if ( ! is_array( $values ) ) { return false; }
		$instance_arg = lunara_site_studio_preview_instance_query_arg(); if ( ! isset( $values[ $instance_arg ] ) || ! lunara_site_studio_preview_instance( $values[ $instance_arg ] ) ) { return false; }
		$selected = ''; $pilot = null; foreach ( lunara_site_studio_preview_pilots() as $surface_id => $candidate ) { if ( isset( $values[ $candidate['query'] ] ) ) { if ( '' !== $selected ) { return false; } $selected = $surface_id; $pilot = $candidate; } }
		if ( '' === $selected || ! lunara_site_studio_preview_uuid( $values[ $pilot['query'] ] ) ) { return false; }
		$surface = lunara_site_studio_get_surface( $selected ); $surface_keys = array( 'id', 'owner', 'supports_preview', 'preview_route', 'preview_query_arg', 'preview_params', 'capability' );
		if ( ! is_array( $surface ) || array() !== array_diff( $surface_keys, array_keys( $surface ) ) || ! is_string( $surface['id'] ) || ! is_string( $surface['owner'] ) || ! is_bool( $surface['supports_preview'] ) || ! is_string( $surface['preview_route'] ) || ! is_string( $surface['preview_query_arg'] ) || ! is_array( $surface['preview_params'] ) || ! is_string( $surface['capability'] ) || '' === $surface['capability'] || $selected !== $surface['id'] || $pilot['owner'] !== $surface['owner'] || true !== $surface['supports_preview'] || $pilot['route'] !== $surface['preview_route'] || $pilot['params'] !== $surface['preview_params'] || $pilot['query'] !== $surface['preview_query_arg'] ) { return false; }
		$expected_keys = array_merge( array( $pilot['query'], $instance_arg ), array_keys( $pilot['params'] ) ); $actual_keys = array_keys( $values ); sort( $expected_keys ); sort( $actual_keys ); if ( $expected_keys !== $actual_keys ) { return false; }
		foreach ( $pilot['params'] as $key => $expected ) { if ( ! isset( $values[ $key ] ) || ! is_string( $values[ $key ] ) || ! hash_equals( $expected, $values[ $key ] ) ) { return false; } }
		if ( ! lunara_site_studio_preview_request_path( $pilot['route'] ) ) { return false; }
		$front_page_id = 0; if ( '/' === $pilot['route'] ) { if ( ! is_front_page() ) { return false; } $front_page_id = 'page' === get_option( 'show_on_front' ) ? absint( get_option( 'page_on_front' ) ) : 0; if ( ! $front_page_id || absint( get_queried_object_id() ) !== $front_page_id ) { return false; } }
		if ( ! is_user_logged_in() || ! get_current_user_id() || ! current_user_can( $surface['capability'] ) ) { return false; }
		$availability = lunara_site_studio_surface_availability( $surface ); if ( ! is_array( $availability ) || ! array_key_exists( 'available', $availability ) || ! is_bool( $availability['available'] ) || true !== $availability['available'] ) { return false; } $adapter = lunara_site_studio_get_adapter( $selected ); if ( is_wp_error( $adapter ) ) { return false; }
		if ( 'provider' === $pilot['storage'] ) { if ( empty( $pilot['preview_callback'] ) || ! is_callable( $pilot['preview_callback'] ) ) { return false; } try { $state = call_user_func( $pilot['preview_callback'], $values[ $pilot['query'] ] ); } catch ( Throwable $error ) { return false; } } else { $state = lunara_site_studio_get_private_preview( $selected, $pilot['owner'], $pilot['route'], $values[ $pilot['query'] ] ); }
		if ( ! is_array( $state ) ) { return false; } $validated = lunara_site_studio_call_adapter( $adapter, 'validate_state', array( $state ) ); if ( is_wp_error( $validated ) || ! is_array( $validated ) || $validated !== $state ) { return false; } $projected = lunara_site_studio_project_state( $selected, $validated ); if ( is_wp_error( $projected ) || ! is_array( $projected ) || $projected !== $validated || ( 'provider' !== $pilot['storage'] && ! lunara_site_studio_preview_install_state( $selected, $projected, $front_page_id ) ) ) { return false; }
		return array( 'surface' => $selected, 'instance' => $values[ $instance_arg ], 'markers' => $pilot['markers'] );
	}
}

if ( ! function_exists( 'lunara_site_studio_enqueue_preview_bridge' ) ) {
	function lunara_site_studio_enqueue_preview_bridge() {
		global $lunara_site_studio_preview_context; if ( ! is_array( $lunara_site_studio_preview_context ) ) { return; }
		$asset = lunara_resolve_theme_asset( 'assets/js/lunara-site-studio-preview.js' ); wp_enqueue_script( 'lunara-site-studio-preview', $asset['uri'], array(), lunara_theme_asset_version( $asset['path'] ), true );
		wp_localize_script( 'lunara-site-studio-preview', 'LunaraSiteStudioPreviewConfig', array( 'protocol' => 'lunara-site-studio/v1', 'version' => 1, 'type' => 'select-section', 'surface' => $lunara_site_studio_preview_context['surface'], 'instance' => $lunara_site_studio_preview_context['instance'], 'markers' => $lunara_site_studio_preview_context['markers'] ) );
	}
}

if ( ! function_exists( 'lunara_site_studio_handle_private_preview' ) ) {
	function lunara_site_studio_handle_private_preview() {
		global $lunara_site_studio_preview_context;
		$raw_query = isset( $_SERVER['QUERY_STRING'] ) ? (string) $_SERVER['QUERY_STRING'] : ''; if ( ! lunara_site_studio_preview_key_shape( $raw_query ) ) { return; }
		$context = lunara_site_studio_prepare_private_preview_response( 'lunara_site_studio_resolve_private_preview' ); if ( ! is_array( $context ) ) { status_header( 403 ); wp_die( __( 'This private preview is unavailable.', 'lunara-film' ), '', array( 'response' => 403 ) ); }
		$lunara_site_studio_preview_context = $context; show_admin_bar( false ); add_filter( 'redirect_canonical', static function () { return false; }, PHP_INT_MAX ); add_action( 'wp_enqueue_scripts', 'lunara_site_studio_enqueue_preview_bridge', 0 );
	}
	$deferred = array();
	foreach ( array( 'lunara_handle_festival_qr_redirect', 'lunara_journal_archive_studio_guard_preview_request', 'lunara_reviews_archive_studio_guard_preview_request', 'lunara_oscars_portal_studio_guard_preview_request', 'lunara_send_home_cinematic_hero_preload_header', 'lunara_search_command_template_redirect' ) as $callback ) { if ( 0 === has_action( 'template_redirect', $callback ) && remove_action( 'template_redirect', $callback, 0 ) ) { $deferred[] = $callback; } }
	add_action( 'template_redirect', 'lunara_site_studio_handle_private_preview', -1 );
	foreach ( $deferred as $callback ) { add_action( 'template_redirect', $callback, 0 ); }
}
