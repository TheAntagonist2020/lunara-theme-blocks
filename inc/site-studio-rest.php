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

if ( ! function_exists( 'lunara_site_studio_safe_state' ) ) {
	/**
	 * Recursively redact private implementation keys from canonical state.
	 *
	 * @return mixed
	 */
	function lunara_site_studio_safe_state( $value, $depth = 0 ) {
		if ( $depth > 8 ) {
			return null;
		}
		if ( is_scalar( $value ) || null === $value ) {
			return $value;
		}
		if ( ! is_array( $value ) ) {
			return null;
		}
		$safe = array();
		foreach ( $value as $key => $item ) {
			if ( is_string( $key ) ) {
				$normalized = strtolower( $key );
				if ( 0 === strpos( $normalized, '_' ) || preg_match( '/(?:secret|credential|password|api[_-]?key|token_hash|transient|option_name|saved_by)/', $normalized ) ) {
					continue;
				}
			}
			$safe[ $key ] = lunara_site_studio_safe_state( $item, $depth + 1 );
		}
		return $safe;
	}
}

if ( ! function_exists( 'lunara_site_studio_safe_validation_fields' ) ) {
	/** @return array<string,string> */
	function lunara_site_studio_safe_validation_fields( $error ) {
		$allowed = array( 'title', 'kicker', 'deck', 'supporting_copy', 'section_order', 'section_visibility', 'presentation', 'identity', 'geometry', 'labels', 'gallery', 'retention', 'lead_mode', 'lead_id', 'lane_mode', 'curated_ids', 'item_count' );
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

if ( ! function_exists( 'lunara_site_studio_rest_adapter_error_response' ) ) {
	/** @return WP_REST_Response */
	function lunara_site_studio_rest_adapter_error_response( $error, $status = 422 ) {
		$code = is_wp_error( $error ) ? sanitize_key( $error->get_error_code() ) : 'site_studio_request_failed';
		return new WP_REST_Response(
			array(
				'code'    => $code,
				'message' => __( 'The requested state was not accepted. Review the highlighted fields and try again.', 'lunara-film' ),
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
		$adapter = lunara_site_studio_get_adapter( lunara_site_studio_rest_request_surface( $request ) );
		$state   = $adapter->read_state();
		return is_wp_error( $state ) ? lunara_site_studio_rest_adapter_error_response( $state, 503 ) : new WP_REST_Response( array( 'state' => lunara_site_studio_safe_state( $state ) ), 200 );
	}
}

if ( ! function_exists( 'lunara_site_studio_rest_candidate' ) ) {
	/** @return mixed */
	function lunara_site_studio_rest_candidate( $request ) {
		return is_object( $request ) && method_exists( $request, 'get_param' ) ? $request->get_param( 'state' ) : null;
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
		$url       = add_query_arg( array( $arg => sanitize_text_field( $token ) ), home_url( $route ) );
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
		$denied = lunara_site_studio_rest_guard_response( $request, true );
		if ( $denied ) { return $denied; }
		$surface_id = lunara_site_studio_rest_request_surface( $request );
		$surface    = lunara_site_studio_get_surface( $surface_id );
		if ( ! current_user_can( $surface['capability'] ) ) {
			return new WP_REST_Response( array( 'code' => 'site_studio_forbidden' ), 403 );
		}
		$adapter = lunara_site_studio_get_adapter( $surface_id );
		$result  = $adapter->create_preview( lunara_site_studio_rest_candidate( $request ) );
		if ( is_wp_error( $result ) ) {
			return lunara_site_studio_rest_adapter_error_response( $result );
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
		$result  = $adapter->save_state( lunara_site_studio_rest_candidate( $request ) );
		if ( is_wp_error( $result ) ) {
			return lunara_site_studio_rest_adapter_error_response( $result );
		}
		$allowed_sections = array_flip( $surface['sections'] );
		$changed = array();
		foreach ( isset( $result['changed_sections'] ) && is_array( $result['changed_sections'] ) ? $result['changed_sections'] : array() as $section ) {
			$section = sanitize_key( $section );
			if ( isset( $allowed_sections[ $section ] ) ) { $changed[] = $section; }
		}
		return new WP_REST_Response(
			array(
				'state'            => lunara_site_studio_safe_state( $result['state'] ),
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
		$revisions = $adapter->list_revisions();
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
		$result      = $adapter->restore_revision( $revision_id );
		if ( is_wp_error( $result ) ) {
			return lunara_site_studio_rest_adapter_error_response( $result );
		}
		return new WP_REST_Response(
			array(
				'state'              => lunara_site_studio_safe_state( $result['state'] ),
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
		register_rest_route( $namespace, $surface_route . '/preview', array( 'methods' => 'POST', 'callback' => 'lunara_site_studio_rest_preview', 'permission_callback' => 'lunara_site_studio_rest_route_permission' ) );
		register_rest_route( $namespace, $surface_route . '/save', array( 'methods' => 'POST', 'callback' => 'lunara_site_studio_rest_save', 'permission_callback' => 'lunara_site_studio_rest_route_permission' ) );
		register_rest_route( $namespace, $surface_route . '/revisions', array( 'methods' => 'GET', 'callback' => 'lunara_site_studio_rest_get_revisions', 'permission_callback' => 'lunara_site_studio_rest_route_permission' ) );
		register_rest_route( $namespace, $surface_route . '/restore', array( 'methods' => 'POST', 'callback' => 'lunara_site_studio_rest_restore', 'permission_callback' => 'lunara_site_studio_rest_route_permission' ) );
	}
	add_action( 'rest_api_init', 'lunara_site_studio_register_rest_routes' );
}
