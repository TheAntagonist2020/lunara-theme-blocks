<?php
/**
 * Authenticated, redacted Site Studio admin REST API.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_site_studio_rest_error' ) ) {
	/** @return WP_Error */
	function lunara_site_studio_rest_error( $code, $message, $status ) {
		return new WP_Error( sanitize_key( $code ), sanitize_text_field( $message ), array( 'status' => absint( $status ) ) );
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_base_permission' ) ) {
	/** @return true|WP_Error */
	function lunara_site_studio_rest_base_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return lunara_site_studio_rest_error( 'site_studio_auth_required', __( 'Authentication is required.', 'lunara-film' ), 401 );
		}
		$nonce = is_object( $request ) && method_exists( $request, 'get_header' ) ? $request->get_header( 'X-WP-Nonce' ) : '';
		if ( ! is_scalar( $nonce ) || ! wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return lunara_site_studio_rest_error( 'site_studio_invalid_nonce', __( 'The REST nonce is invalid or expired.', 'lunara-film' ), 403 );
		}
		return true;
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_request_surface' ) ) {
	/** @return string */
	function lunara_site_studio_rest_request_surface( $request ) {
		$value = is_object( $request ) && method_exists( $request, 'get_param' ) ? $request->get_param( 'surface' ) : '';
		return sanitize_key( is_scalar( $value ) ? $value : '' );
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_permission' ) ) {
	/**
	 * Enforce cookie auth, nonce, exact surface capability, dependencies, and
	 * optional adapter availability in that order.
	 *
	 * @return true|WP_Error
	 */
	function lunara_site_studio_rest_permission( $request, $require_adapter = true ) {
		$base = lunara_site_studio_rest_base_permission( $request );
		if ( is_wp_error( $base ) ) {
			return $base;
		}
		$surface_id = lunara_site_studio_rest_request_surface( $request );
		$surface    = lunara_site_studio_get_surface( $surface_id );
		if ( ! is_array( $surface ) ) {
			return lunara_site_studio_rest_error( 'site_studio_surface_not_found', __( 'Unknown Site Studio destination.', 'lunara-film' ), 404 );
		}
		if ( ! current_user_can( $surface['capability'] ) ) {
			return lunara_site_studio_rest_error( 'site_studio_forbidden', __( 'You do not have permission to use this destination.', 'lunara-film' ), 403 );
		}
		$availability = lunara_site_studio_surface_availability( $surface );
		if ( empty( $availability['available'] ) ) {
			return lunara_site_studio_rest_error( 'site_studio_unavailable', $availability['message'], 503 );
		}
		if ( $require_adapter ) {
			$adapter = lunara_site_studio_get_adapter( $surface_id, false );
			if ( is_wp_error( $adapter ) ) {
				return lunara_site_studio_rest_error( $adapter->get_error_code(), $adapter->get_error_message(), 503 );
			}
		}
		return true;
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_route_permission' ) ) {
	/** @return true|WP_Error */
	function lunara_site_studio_rest_route_permission( $request ) {
		return lunara_site_studio_rest_permission( $request, true );
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_preview_permission' ) ) {
	/** Preview capability/dependency check before any adapter factory runs. */
	function lunara_site_studio_rest_preview_permission( $request ) {
		return lunara_site_studio_rest_permission( $request, false );
	}
}

if ( ! function_exists( 'lunara_site_studio_safe_validation_fields' ) ) {
	/** @return array<string,string> */
	function lunara_site_studio_safe_validation_fields( $error ) {
		$allowed = array(
			'title', 'kicker', 'colors', 'color_gold', 'color_gold_light', 'color_bg_primary', 'color_bg_secondary', 'color_text', 'color_text_muted', 'fonts', 'font_body', 'font_display', 'font_signature', 'font_glamour', 'font_label', 'copy', 'review_id', 'backdrop_id', 'preset', 'desktop_order', 'mobile_order', 'visibility', 'front_page', 'deck', 'supporting_copy', 'section_order', 'section_visibility', 'presentation', 'identity', 'geometry', 'labels', 'gallery', 'retention', 'lead_mode', 'lead_id', 'lane_mode', 'curated_ids', 'item_count',
			'presentation.density', 'presentation.lead_prominence', 'presentation.rail_density', 'presentation.desk_rhythm', 'presentation.section_gap', 'presentation.lead_min_height', 'presentation.hero_min_height', 'presentation.card_min_height', 'presentation.compact_media_width', 'presentation.media_min_height', 'presentation.result_treatment', 'presentation.result_media', 'presentation.recovery_prominence',
			'review.density', 'review.hero_scale', 'review.rail_mode', 'review.debrief_prominence', 'review.pairing_density', 'review.spoiler_treatment', 'review.trailer_prominence', 'review.section_gap', 'review.debrief_poster_width', 'review.related_count',
			'pairing.layout', 'pairing.text_depth', 'pairing.mobile_stack', 'pairing.image_focus', 'pairing.columns', 'pairing.thumb_width',
			'focus.lead', 'focus.spotlight', 'geometry.section_gap', 'geometry.result_min_height', 'geometry.card_grid_min',
			'brand.show_logo', 'brand.tagline', 'columns.editorial', 'columns.oscars', 'columns.utility', 'copyright.name',
		);
		$data    = is_wp_error( $error ) ? $error->get_error_data() : array();
		$fields  = is_array( $data ) && isset( $data['fields'] ) && is_array( $data['fields'] ) ? $data['fields'] : array();
		$safe    = array();
		foreach ( $allowed as $field ) {
			if ( isset( $fields[ $field ] ) && is_scalar( $fields[ $field ] ) ) {
				$safe[ $field ] = sanitize_text_field( $fields[ $field ] );
			}
		}
		return $safe;
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_adapter_error_message' ) ) {
	/** Return only fixed, user-actionable messages for allowlisted operational failures. */
	function lunara_site_studio_rest_adapter_error_message( $code ) {
		$messages = array(
			'reviews_archive_preview_write_failed'    => __( 'The private preview could not be stored. Try Preview Changes again.', 'lunara-film' ),
			'journal_archive_preview_write_failed'    => __( 'The private preview could not be stored. Try Preview Changes again.', 'lunara-film' ),
			'reviews_archive_preview_readback_failed' => __( 'The private preview could not be verified. Try Preview Changes again.', 'lunara-film' ),
			'journal_archive_preview_readback_failed' => __( 'The private preview could not be verified. Try Preview Changes again.', 'lunara-film' ),
		);
		$code = sanitize_key( $code );
		return isset( $messages[ $code ] ) ? $messages[ $code ] : __( 'The requested state was not accepted. Review the highlighted fields and try again.', 'lunara-film' );
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_adapter_error_response' ) ) {
	/** @return WP_REST_Response */
	function lunara_site_studio_rest_adapter_error_response( $error, $status = 422 ) {
		$code = is_wp_error( $error ) ? sanitize_key( $error->get_error_code() ) : 'site_studio_request_failed';
		return new WP_REST_Response(
			array(
				'code'    => $code,
				'message' => lunara_site_studio_rest_adapter_error_message( $code ),
				'fields'  => lunara_site_studio_safe_validation_fields( $error ),
			),
			absint( $status )
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_guard_response' ) ) {
	/** @return WP_REST_Response|null */
	function lunara_site_studio_rest_guard_response( $request, $require_adapter = true ) {
		$result = lunara_site_studio_rest_permission( $request, $require_adapter );
		if ( ! is_wp_error( $result ) ) {
			return null;
		}
		$data   = $result->get_error_data();
		$status = is_array( $data ) && ! empty( $data['status'] ) ? absint( $data['status'] ) : 403;
		return new WP_REST_Response( array( 'code' => sanitize_key( $result->get_error_code() ), 'message' => sanitize_text_field( $result->get_error_message() ) ), $status );
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_get_surfaces' ) ) {
	/** @return WP_REST_Response */
	function lunara_site_studio_rest_get_surfaces( $request ) {
		$base = lunara_site_studio_rest_base_permission( $request );
		if ( is_wp_error( $base ) ) {
			$data = $base->get_error_data();
			return new WP_REST_Response( array( 'code' => $base->get_error_code(), 'message' => $base->get_error_message() ), isset( $data['status'] ) ? absint( $data['status'] ) : 403 );
		}
		return new WP_REST_Response( array( 'surfaces' => array_values( lunara_site_studio_public_surfaces() ) ), 200 );
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_get_state' ) ) {
	/** @return WP_REST_Response */
	function lunara_site_studio_rest_get_state( $request ) {
		$denied = lunara_site_studio_rest_guard_response( $request, true );
		if ( $denied ) { return $denied; }
		$surface_id = lunara_site_studio_rest_request_surface( $request );
		$adapter = lunara_site_studio_get_adapter( $surface_id );
		$state   = lunara_site_studio_call_adapter( $adapter, 'read_state' );
		if ( is_wp_error( $state ) ) {
			return lunara_site_studio_rest_adapter_error_response( $state, 503 );
		}
		$projected = lunara_site_studio_project_state( $surface_id, $state );
		return is_wp_error( $projected ) ? lunara_site_studio_rest_adapter_error_response( $projected, 503 ) : new WP_REST_Response( array( 'state' => $projected ), 200 );
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_candidate' ) ) {
	/** @return mixed */
	function lunara_site_studio_rest_candidate( $request ) {
		return is_object( $request ) && method_exists( $request, 'get_param' ) ? $request->get_param( 'state' ) : null;
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_nonempty_scalar' ) ) {
	/** @return bool */
	function lunara_site_studio_rest_nonempty_scalar( $value ) {
		return is_scalar( $value ) && '' !== sanitize_text_field( (string) $value );
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_valid_save_envelope' ) ) {
	/** @return bool */
	function lunara_site_studio_rest_valid_save_envelope( $result ) {
		if ( ! is_array( $result ) || ! array_key_exists( 'state', $result ) || ! is_array( $result['state'] ) || ! array_key_exists( 'changed_sections', $result ) || ! is_array( $result['changed_sections'] ) || ! array_key_exists( 'revision_id', $result ) || ! lunara_site_studio_rest_nonempty_scalar( $result['revision_id'] ) || ! array_key_exists( 'timestamp', $result ) || ! lunara_site_studio_rest_nonempty_scalar( $result['timestamp'] ) ) {
			return false;
		}
		foreach ( $result['changed_sections'] as $section ) {
			if ( ! is_string( $section ) ) {
				return false;
			}
		}
		return true;
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_valid_restore_envelope' ) ) {
	/** @return bool */
	function lunara_site_studio_rest_valid_restore_envelope( $result ) {
		return is_array( $result ) && array_key_exists( 'state', $result ) && is_array( $result['state'] ) && array_key_exists( 'safety_revision_id', $result ) && lunara_site_studio_rest_nonempty_scalar( $result['safety_revision_id'] ) && array_key_exists( 'timestamp', $result ) && lunara_site_studio_rest_nonempty_scalar( $result['timestamp'] );
	}
}

if ( ! function_exists( 'lunara_site_studio_preview_url' ) ) {
	/** @return string|WP_Error */
	function lunara_site_studio_preview_url( $surface, $token ) {
		$route = lunara_site_studio_allow_relative_route( $surface['preview_route'] );
		$arg   = sanitize_key( $surface['preview_query_arg'] );
		if ( '' === $route || '' === $arg || empty( $surface['supports_preview'] ) ) {
			return new WP_Error( 'site_studio_preview_unavailable' );
		}
		$params    = isset( $surface['preview_params'] ) && is_array( $surface['preview_params'] ) ? $surface['preview_params'] : array();
		$params[ $arg ] = sanitize_text_field( $token );
		$url       = add_query_arg( $params, home_url( $route ) );
		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$url_host  = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( '' === $url_host || ! hash_equals( $home_host, $url_host ) ) {
			return new WP_Error( 'site_studio_preview_route_invalid' );
		}
		return $url;
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_preview' ) ) {
	/** @return WP_REST_Response */
	function lunara_site_studio_rest_preview( $request ) {
		$denied = lunara_site_studio_rest_guard_response( $request, false );
		if ( $denied ) { return $denied; }
		$surface_id = lunara_site_studio_rest_request_surface( $request );
		$surface    = lunara_site_studio_get_surface( $surface_id );
		if ( ! current_user_can( $surface['capability'] ) ) {
			return new WP_REST_Response( array( 'code' => 'site_studio_forbidden' ), 403 );
		}
		if ( empty( $surface['supports_preview'] ) ) {
			return new WP_REST_Response( array( 'code' => 'site_studio_preview_unavailable', 'message' => __( 'Private preview is not available for this destination.', 'lunara-film' ) ), 409 );
		}
		$adapter = lunara_site_studio_get_adapter( $surface_id );
		$result  = lunara_site_studio_call_adapter( $adapter, 'create_preview', array( lunara_site_studio_rest_candidate( $request ) ) );
		if ( is_wp_error( $result ) ) {
			return lunara_site_studio_rest_adapter_error_response( $result );
		}
		if ( ! is_array( $result ) || empty( $result['token'] ) || empty( $result['expires_at'] ) ) {
			return lunara_site_studio_rest_adapter_error_response( new WP_Error( 'site_studio_adapter_invalid' ), 503 );
		}
		$url = lunara_site_studio_preview_url( $surface, $result['token'] );
		if ( is_wp_error( $url ) ) {
			return lunara_site_studio_rest_adapter_error_response( $url, 503 );
		}
		return new WP_REST_Response( array( 'url' => $url, 'expires_at' => absint( $result['expires_at'] ) ), 200 );
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_save' ) ) {
	/** @return WP_REST_Response */
	function lunara_site_studio_rest_save( $request ) {
		$denied = lunara_site_studio_rest_guard_response( $request, true );
		if ( $denied ) { return $denied; }
		$surface_id = lunara_site_studio_rest_request_surface( $request );
		$surface    = lunara_site_studio_get_surface( $surface_id );
		if ( ! current_user_can( $surface['capability'] ) ) {
			return new WP_REST_Response( array( 'code' => 'site_studio_forbidden' ), 403 );
		}
		$adapter = lunara_site_studio_get_adapter( $surface_id );
		$result  = lunara_site_studio_call_adapter( $adapter, 'save_state', array( lunara_site_studio_rest_candidate( $request ) ) );
		if ( is_wp_error( $result ) ) {
			return lunara_site_studio_rest_adapter_error_response( $result );
		}
		if ( ! lunara_site_studio_rest_valid_save_envelope( $result ) ) {
			return lunara_site_studio_rest_adapter_error_response( new WP_Error( 'site_studio_adapter_invalid' ), 503 );
		}
		$projected = lunara_site_studio_project_state( $surface_id, $result['state'] );
		if ( is_wp_error( $projected ) ) {
			return lunara_site_studio_rest_adapter_error_response( $projected, 503 );
		}
		$allowed_sections = array_flip( $surface['sections'] );
		$changed = array();
		foreach ( isset( $result['changed_sections'] ) && is_array( $result['changed_sections'] ) ? $result['changed_sections'] : array() as $section ) {
			$section = sanitize_key( $section );
			if ( isset( $allowed_sections[ $section ] ) ) { $changed[] = $section; }
		}
		return new WP_REST_Response(
			array(
				'state'            => $projected,
				'changed_sections' => array_values( array_unique( $changed ) ),
				'revision_id'      => sanitize_text_field( isset( $result['revision_id'] ) ? $result['revision_id'] : '' ),
				'timestamp'        => sanitize_text_field( isset( $result['timestamp'] ) ? $result['timestamp'] : '' ),
			),
			200
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_redact_revisions' ) ) {
	/** @return array<int,array<string,mixed>> */
	function lunara_site_studio_redact_revisions( $revisions ) {
		$safe = array();
		foreach ( is_array( $revisions ) ? array_slice( $revisions, 0, LUNARA_SITE_STUDIO_REVISION_LIMIT ) : array() as $revision ) {
			if ( ! is_array( $revision ) || empty( $revision['id'] ) ) { continue; }
			$row = array(
				'id'        => sanitize_text_field( $revision['id'] ),
				'timestamp' => sanitize_text_field( isset( $revision['timestamp'] ) ? $revision['timestamp'] : ( isset( $revision['saved_at'] ) ? $revision['saved_at'] : '' ) ),
				'action'    => sanitize_key( isset( $revision['action'] ) ? $revision['action'] : '' ),
			);
			if ( isset( $revision['validator_result'] ) && is_scalar( $revision['validator_result'] ) ) { $row['validator_result'] = sanitize_key( $revision['validator_result'] ); }
			if ( isset( $revision['prior_public'] ) ) { $row['prior_public'] = (bool) $revision['prior_public']; }
			$safe[] = $row;
		}
		return $safe;
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_get_revisions' ) ) {
	/** @return WP_REST_Response */
	function lunara_site_studio_rest_get_revisions( $request ) {
		$denied = lunara_site_studio_rest_guard_response( $request, true );
		if ( $denied ) { return $denied; }
		$adapter   = lunara_site_studio_get_adapter( lunara_site_studio_rest_request_surface( $request ) );
		$revisions = lunara_site_studio_call_adapter( $adapter, 'list_revisions' );
		return is_wp_error( $revisions ) ? lunara_site_studio_rest_adapter_error_response( $revisions, 503 ) : new WP_REST_Response( array( 'revisions' => lunara_site_studio_redact_revisions( $revisions ) ), 200 );
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_restore' ) ) {
	/** @return WP_REST_Response */
	function lunara_site_studio_rest_restore( $request ) {
		$denied = lunara_site_studio_rest_guard_response( $request, true );
		if ( $denied ) { return $denied; }
		$confirm = is_object( $request ) && method_exists( $request, 'get_param' ) ? $request->get_param( 'confirm' ) : false;
		if ( true !== $confirm && 'true' !== $confirm ) {
			return new WP_REST_Response( array( 'code' => 'site_studio_restore_confirmation_required', 'message' => __( 'Confirm this restoration before continuing.', 'lunara-film' ) ), 400 );
		}
		$surface_id = lunara_site_studio_rest_request_surface( $request );
		$surface    = lunara_site_studio_get_surface( $surface_id );
		if ( ! current_user_can( $surface['capability'] ) ) {
			return new WP_REST_Response( array( 'code' => 'site_studio_forbidden' ), 403 );
		}
		$revision_id = is_object( $request ) && method_exists( $request, 'get_param' ) ? sanitize_text_field( $request->get_param( 'revision_id' ) ) : '';
		$adapter     = lunara_site_studio_get_adapter( $surface_id );
		$result      = lunara_site_studio_call_adapter( $adapter, 'restore_revision', array( $revision_id ) );
		if ( is_wp_error( $result ) ) {
			return lunara_site_studio_rest_adapter_error_response( $result );
		}
		if ( ! lunara_site_studio_rest_valid_restore_envelope( $result ) ) {
			return lunara_site_studio_rest_adapter_error_response( new WP_Error( 'site_studio_adapter_invalid' ), 503 );
		}
		$projected = lunara_site_studio_project_state( $surface_id, $result['state'] );
		if ( is_wp_error( $projected ) ) {
			return lunara_site_studio_rest_adapter_error_response( $projected, 503 );
		}
		return new WP_REST_Response(
			array(
				'state'              => $projected,
				'safety_revision_id' => sanitize_text_field( isset( $result['safety_revision_id'] ) ? $result['safety_revision_id'] : '' ),
				'timestamp'          => sanitize_text_field( isset( $result['timestamp'] ) ? $result['timestamp'] : '' ),
			),
			200
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_register_rest_routes' ) ) {
	/** @return void */
	function lunara_site_studio_register_rest_routes() {
		$namespace = 'lunara-site-studio/v1';
		$surface_route = '/surfaces/(?P<surface>[a-z0-9\-]+)';
		register_rest_route( $namespace, '/surfaces', array( 'methods' => 'GET', 'callback' => 'lunara_site_studio_rest_get_surfaces', 'permission_callback' => 'lunara_site_studio_rest_base_permission' ) );
		register_rest_route( $namespace, $surface_route . '/state', array( 'methods' => 'GET', 'callback' => 'lunara_site_studio_rest_get_state', 'permission_callback' => 'lunara_site_studio_rest_route_permission' ) );
		register_rest_route( $namespace, $surface_route . '/preview', array( 'methods' => 'POST', 'callback' => 'lunara_site_studio_rest_preview', 'permission_callback' => 'lunara_site_studio_rest_preview_permission' ) );
		register_rest_route( $namespace, $surface_route . '/save', array( 'methods' => 'POST', 'callback' => 'lunara_site_studio_rest_save', 'permission_callback' => 'lunara_site_studio_rest_route_permission' ) );
		register_rest_route( $namespace, $surface_route . '/revisions', array( 'methods' => 'GET', 'callback' => 'lunara_site_studio_rest_get_revisions', 'permission_callback' => 'lunara_site_studio_rest_route_permission' ) );
		register_rest_route( $namespace, $surface_route . '/restore', array( 'methods' => 'POST', 'callback' => 'lunara_site_studio_rest_restore', 'permission_callback' => 'lunara_site_studio_rest_route_permission' ) );
	}
	add_action( 'rest_api_init', 'lunara_site_studio_register_rest_routes' );
}
