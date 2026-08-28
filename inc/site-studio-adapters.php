<?php
/**
 * Site Studio canonical adapters plus shared private-preview/revision services.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LUNARA_SITE_STUDIO_PREVIEW_TTL' ) ) {
	define( 'LUNARA_SITE_STUDIO_PREVIEW_TTL', 1800 );
}
if ( ! defined( 'LUNARA_SITE_STUDIO_REVISION_LIMIT' ) ) {
	define( 'LUNARA_SITE_STUDIO_REVISION_LIMIT', 12 );
}

if ( ! interface_exists( 'Lunara_Site_Studio_Surface_Adapter' ) ) {
	/**
	 * Contract between Site Studio and a surface's canonical state owner.
	 *
	 * Method signatures intentionally avoid PHP 8-only syntax because the theme
	 * still supports PHP 7.4.
	 */
	interface Lunara_Site_Studio_Surface_Adapter {
		/** @return array<string,mixed>|WP_Error */
		public function read_state();
		/** @param mixed $candidate @return array<string,mixed>|WP_Error */
		public function validate_state( $candidate );
		/** @param mixed $candidate @return array<string,mixed>|WP_Error */
		public function save_state( $candidate );
		/** @param mixed $candidate @return array<string,mixed>|WP_Error */
		public function create_preview( $candidate );
		/** @return array<int,array<string,mixed>>|WP_Error */
		public function list_revisions();
		/** @param string $revision_id @return array<string,mixed>|WP_Error */
		public function restore_revision( $revision_id );
	}
}

if ( ! class_exists( 'Lunara_Site_Studio_Provider_Adapter' ) ) {
	/** Adapter around a mature provider without copying its storage. */
	class Lunara_Site_Studio_Provider_Adapter implements Lunara_Site_Studio_Surface_Adapter {
		private $surface;
		private $read_callback;
		private $validate_callback;
		private $save_callback;
		private $preview_callback;
		private $revisions_callback;
		private $restore_callback;

		/** @param array<string,string> $callbacks Provider callback map. */
		public function __construct( $surface, $callbacks ) {
			$this->surface            = sanitize_key( $surface );
			$this->read_callback      = isset( $callbacks['read'] ) ? $callbacks['read'] : '';
			$this->validate_callback  = isset( $callbacks['validate'] ) ? $callbacks['validate'] : '';
			$this->save_callback      = isset( $callbacks['save'] ) ? $callbacks['save'] : '';
			$this->preview_callback   = isset( $callbacks['preview'] ) ? $callbacks['preview'] : '';
			$this->revisions_callback = isset( $callbacks['revisions'] ) ? $callbacks['revisions'] : '';
			$this->restore_callback   = isset( $callbacks['restore'] ) ? $callbacks['restore'] : '';
		}

		/** @return WP_Error|null */
		private function callback_error( $callback ) {
			return is_callable( $callback ) ? null : new WP_Error( 'site_studio_adapter_unavailable', __( 'The canonical surface provider is unavailable.', 'lunara-film' ) );
		}

		public function read_state() {
			$error = $this->callback_error( $this->read_callback );
			return $error ? $error : call_user_func( $this->read_callback, false );
		}

		public function validate_state( $candidate ) {
			$error = $this->callback_error( $this->validate_callback );
			return $error ? $error : call_user_func( $this->validate_callback, $candidate );
		}

		public function save_state( $candidate ) {
			$error = $this->callback_error( $this->save_callback );
			if ( $error ) {
				return $error;
			}
			$before = $this->read_state();
			if ( is_wp_error( $before ) ) {
				return $before;
			}
			$result = call_user_func( $this->save_callback, $candidate, 'site-studio-save' );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$revisions = $this->list_revisions();
			return array(
				'state'            => $result,
				'changed_sections' => lunara_site_studio_changed_sections( $this->surface, $before, $result ),
				'revision_id'      => is_array( $revisions ) && ! empty( $revisions[0]['id'] ) ? sanitize_text_field( $revisions[0]['id'] ) : '',
				'timestamp'        => current_time( 'mysql' ),
			);
		}

		public function create_preview( $candidate ) {
			$error = $this->callback_error( $this->preview_callback );
			if ( $error ) {
				return $error;
			}
			$token = call_user_func( $this->preview_callback, $candidate );
			if ( is_wp_error( $token ) ) {
				return $token;
			}
			return array(
				'token'      => sanitize_text_field( $token ),
				'expires_at' => lunara_site_studio_timestamp() + LUNARA_SITE_STUDIO_PREVIEW_TTL,
			);
		}

		public function list_revisions() {
			$error = $this->callback_error( $this->revisions_callback );
			if ( $error ) {
				return $error;
			}
			$revisions = call_user_func( $this->revisions_callback );
			return is_array( $revisions ) ? array_slice( $revisions, 0, LUNARA_SITE_STUDIO_REVISION_LIMIT ) : array();
		}

		public function restore_revision( $revision_id ) {
			$error = $this->callback_error( $this->restore_callback );
			if ( $error ) {
				return $error;
			}
			$result = call_user_func( $this->restore_callback, sanitize_text_field( $revision_id ) );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$revisions = $this->list_revisions();
			return array(
				'state'              => $result,
				'safety_revision_id' => is_array( $revisions ) && ! empty( $revisions[0]['id'] ) ? sanitize_text_field( $revisions[0]['id'] ) : '',
				'timestamp'          => current_time( 'mysql' ),
			);
		}
	}
}

if ( ! function_exists( 'lunara_site_studio_reviews_archive_dependency' ) ) {
	function lunara_site_studio_reviews_archive_dependency() {
		return function_exists( 'lunara_reviews_archive_studio_get_public_config' ) && function_exists( 'lunara_reviews_archive_studio_validate_config' ) && function_exists( 'lunara_reviews_archive_studio_promote_config' ) && function_exists( 'lunara_reviews_archive_studio_store_preview' ) && function_exists( 'lunara_reviews_archive_studio_get_revisions' ) && function_exists( 'lunara_reviews_archive_studio_restore_revision' );
	}
}
if ( ! function_exists( 'lunara_site_studio_journal_archive_dependency' ) ) {
	function lunara_site_studio_journal_archive_dependency() {
		return function_exists( 'lunara_journal_archive_studio_get_public_config' ) && function_exists( 'lunara_journal_archive_studio_validate_config' ) && function_exists( 'lunara_journal_archive_studio_promote_config' ) && function_exists( 'lunara_journal_archive_studio_store_preview' ) && function_exists( 'lunara_journal_archive_studio_get_revisions' ) && function_exists( 'lunara_journal_archive_studio_restore_revision' );
	}
}
if ( ! function_exists( 'lunara_site_studio_oscars_portal_dependency' ) ) {
	function lunara_site_studio_oscars_portal_dependency() {
		return function_exists( 'lunara_oscars_portal_studio_get_public_config' ) && function_exists( 'lunara_oscars_portal_studio_validate_config' ) && function_exists( 'lunara_oscars_portal_studio_promote_config' ) && function_exists( 'lunara_oscars_portal_studio_store_preview' ) && function_exists( 'lunara_oscars_portal_studio_get_revisions' ) && function_exists( 'lunara_oscars_portal_studio_restore_revision' );
	}
}
if ( ! function_exists( 'lunara_site_studio_oscars_ledger_dependency' ) ) {
	function lunara_site_studio_oscars_ledger_dependency() {
		return function_exists( 'lunara_oscars_plugin_instance' ) && is_object( lunara_oscars_plugin_instance() );
	}
}

if ( ! function_exists( 'lunara_site_studio_reviews_archive_adapter' ) ) {
	function lunara_site_studio_reviews_archive_adapter() {
		return new Lunara_Site_Studio_Provider_Adapter(
			'reviews-archive',
			array(
				'read' => 'lunara_reviews_archive_studio_get_public_config', 'validate' => 'lunara_reviews_archive_studio_validate_config',
				'save' => 'lunara_reviews_archive_studio_promote_config', 'preview' => 'lunara_reviews_archive_studio_store_preview',
				'revisions' => 'lunara_reviews_archive_studio_get_revisions', 'restore' => 'lunara_reviews_archive_studio_restore_revision',
			)
		);
	}
}
if ( ! function_exists( 'lunara_site_studio_journal_archive_adapter' ) ) {
	function lunara_site_studio_journal_archive_adapter() {
		return new Lunara_Site_Studio_Provider_Adapter(
			'journal-archive',
			array(
				'read' => 'lunara_journal_archive_studio_get_public_config', 'validate' => 'lunara_journal_archive_studio_validate_config',
				'save' => 'lunara_journal_archive_studio_promote_config', 'preview' => 'lunara_journal_archive_studio_store_preview',
				'revisions' => 'lunara_journal_archive_studio_get_revisions', 'restore' => 'lunara_journal_archive_studio_restore_revision',
			)
		);
	}
}
if ( ! function_exists( 'lunara_site_studio_oscars_portal_adapter' ) ) {
	function lunara_site_studio_oscars_portal_adapter() {
		return new Lunara_Site_Studio_Provider_Adapter(
			'oscars-portal',
			array(
				'read' => 'lunara_oscars_portal_studio_get_public_config', 'validate' => 'lunara_oscars_portal_studio_validate_config',
				'save' => 'lunara_oscars_portal_studio_promote_config', 'preview' => 'lunara_oscars_portal_studio_store_preview',
				'revisions' => 'lunara_oscars_portal_studio_get_revisions', 'restore' => 'lunara_oscars_portal_studio_restore_revision',
			)
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_get_adapter' ) ) {
	/** @return Lunara_Site_Studio_Surface_Adapter|WP_Error */
	function lunara_site_studio_get_adapter( $surface_id, $enforce_capability = true ) {
		$surface = lunara_site_studio_get_surface( $surface_id );
		if ( ! is_array( $surface ) ) {
			return new WP_Error( 'site_studio_surface_not_found', __( 'Unknown Site Studio destination.', 'lunara-film' ) );
		}
		if ( $enforce_capability && ! current_user_can( $surface['capability'] ) ) {
			return new WP_Error( 'site_studio_forbidden', __( 'You do not have permission to use this destination.', 'lunara-film' ) );
		}
		$availability = lunara_site_studio_surface_availability( $surface );
		if ( empty( $availability['available'] ) ) {
			return new WP_Error( 'site_studio_unavailable', $availability['message'] );
		}
		$factory = $surface['adapter_factory'];
		if ( ! is_callable( $factory ) ) {
			return new WP_Error( 'site_studio_adapter_unavailable', __( 'This destination does not expose editable state yet.', 'lunara-film' ) );
		}
		$adapter = call_user_func( $factory, $surface );
		return $adapter instanceof Lunara_Site_Studio_Surface_Adapter ? $adapter : new WP_Error( 'site_studio_adapter_invalid', __( 'The destination adapter is invalid.', 'lunara-film' ) );
	}
}

if ( ! function_exists( 'lunara_site_studio_changed_sections' ) ) {
	/** @return array<int,string> */
	function lunara_site_studio_changed_sections( $surface_id, $before, $after ) {
		$surface = lunara_site_studio_get_surface( $surface_id );
		if ( ! is_array( $surface ) || $before === $after ) {
			return array();
		}
		return array_values( array_filter( array_map( 'sanitize_key', $surface['sections'] ) ) );
	}
}

if ( ! function_exists( 'lunara_site_studio_timestamp' ) ) {
	/** @return int */
	function lunara_site_studio_timestamp() {
		return (int) current_time( 'timestamp', true );
	}
}

if ( ! function_exists( 'lunara_site_studio_allow_relative_route' ) ) {
	/** @return string */
	function lunara_site_studio_allow_relative_route( $route ) {
		return lunara_site_studio_normalize_preview_route( $route );
	}
}

if ( ! function_exists( 'lunara_site_studio_store_private_preview' ) ) {
	/** @return string|WP_Error */
	function lunara_site_studio_store_private_preview( $surface, $owner, $route, $state ) {
		$surface = sanitize_key( $surface );
		$owner   = sanitize_text_field( $owner );
		$route   = lunara_site_studio_allow_relative_route( $route );
		if ( '' === $surface || '' === $owner || '' === $route || ! is_array( $state ) || ! get_current_user_id() ) {
			return new WP_Error( 'site_studio_preview_invalid', __( 'The private preview could not be created.', 'lunara-film' ) );
		}
		$token   = wp_generate_uuid4();
		$user_id = absint( get_current_user_id() );
		$key     = 'lunara_site_studio_preview_' . hash( 'sha256', $token );
		set_transient(
			$key,
			array(
				'user_id'    => $user_id,
				'surface'    => $surface,
				'owner'      => $owner,
				'route'      => $route,
				'token_hash' => wp_hash( $token . '|' . $user_id . '|' . $surface . '|' . $owner . '|' . $route ),
				'expires'    => lunara_site_studio_timestamp() + LUNARA_SITE_STUDIO_PREVIEW_TTL,
				'state'      => $state,
			),
			LUNARA_SITE_STUDIO_PREVIEW_TTL
		);
		return $token;
	}
}

if ( ! function_exists( 'lunara_site_studio_get_private_preview' ) ) {
	/** @return array<string,mixed>|false */
	function lunara_site_studio_get_private_preview( $surface, $owner, $route, $token ) {
		$surface = sanitize_key( $surface );
		$owner   = sanitize_text_field( $owner );
		$route   = lunara_site_studio_allow_relative_route( $route );
		$token   = sanitize_text_field( $token );
		if ( '' === $surface || '' === $owner || '' === $route || '' === $token || ! get_current_user_id() ) {
			return false;
		}
		$record = get_transient( 'lunara_site_studio_preview_' . hash( 'sha256', $token ) );
		$user_id = absint( get_current_user_id() );
		if ( ! is_array( $record ) || empty( $record['user_id'] ) || $user_id !== absint( $record['user_id'] ) ) {
			return false;
		}
		foreach ( array( 'surface' => $surface, 'owner' => $owner, 'route' => $route ) as $field => $expected ) {
			if ( empty( $record[ $field ] ) || ! hash_equals( (string) $record[ $field ], (string) $expected ) ) {
				return false;
			}
		}
		$expected_hash = wp_hash( $token . '|' . $user_id . '|' . $surface . '|' . $owner . '|' . $route );
		if ( empty( $record['token_hash'] ) || ! hash_equals( (string) $record['token_hash'], $expected_hash ) || empty( $record['expires'] ) || absint( $record['expires'] ) <= lunara_site_studio_timestamp() ) {
			return false;
		}
		return isset( $record['state'] ) && is_array( $record['state'] ) ? $record['state'] : false;
	}
}

if ( ! function_exists( 'lunara_site_studio_private_preview_headers' ) ) {
	/** @return array<int,string> */
	function lunara_site_studio_private_preview_headers() {
		return array(
			'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0',
			'X-Robots-Tag: noindex, nofollow',
			'Referrer-Policy: no-referrer',
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_send_private_no_store' ) ) {
	/** @return void */
	function lunara_site_studio_send_private_no_store() {
		nocache_headers();
		if ( ! headers_sent() ) {
			foreach ( lunara_site_studio_private_preview_headers() as $header_value ) {
				header( $header_value, true );
			}
		}
		do_action( 'lunara_site_studio_private_no_store_sent' );
	}
}

if ( ! function_exists( 'lunara_site_studio_prepare_private_preview_response' ) ) {
	/** @return mixed */
	function lunara_site_studio_prepare_private_preview_response( $lookup_callback ) {
		lunara_site_studio_send_private_no_store();
		return is_callable( $lookup_callback ) ? call_user_func( $lookup_callback ) : false;
	}
}

if ( ! function_exists( 'lunara_site_studio_revision_option_name' ) ) {
	/** @return string */
	function lunara_site_studio_revision_option_name( $surface ) {
		return 'lunara_site_studio_revisions_' . substr( hash( 'sha256', sanitize_key( $surface ) ), 0, 24 );
	}
}

if ( ! function_exists( 'lunara_site_studio_push_revision' ) ) {
	/** @return string|WP_Error */
	function lunara_site_studio_push_revision( $surface, $config, $action = 'save' ) {
		$surface = sanitize_key( $surface );
		if ( '' === $surface || ! is_array( $config ) ) {
			return new WP_Error( 'site_studio_revision_invalid', __( 'The revision could not be created.', 'lunara-film' ) );
		}
		$option    = lunara_site_studio_revision_option_name( $surface );
		$revisions = get_option( $option, array() );
		$revisions = is_array( $revisions ) ? $revisions : array();
		$id        = wp_generate_uuid4();
		array_unshift(
			$revisions,
			array(
				'id'       => $id,
				'saved_at' => current_time( 'mysql' ),
				'saved_by' => absint( get_current_user_id() ),
				'action'   => sanitize_key( $action ),
				'config'   => $config,
			)
		);
		update_option( $option, array_slice( $revisions, 0, LUNARA_SITE_STUDIO_REVISION_LIMIT ), false );
		return $id;
	}
}

if ( ! function_exists( 'lunara_site_studio_list_revisions' ) ) {
	/** @return array<int,array<string,mixed>> */
	function lunara_site_studio_list_revisions( $surface, $include_private = false ) {
		$revisions = get_option( lunara_site_studio_revision_option_name( $surface ), array() );
		$revisions = is_array( $revisions ) ? array_slice( $revisions, 0, LUNARA_SITE_STUDIO_REVISION_LIMIT ) : array();
		if ( $include_private ) {
			return $revisions;
		}
		$safe = array();
		foreach ( $revisions as $revision ) {
			if ( empty( $revision['id'] ) ) {
				continue;
			}
			$safe[] = array(
				'id'        => sanitize_text_field( $revision['id'] ),
				'timestamp' => sanitize_text_field( isset( $revision['saved_at'] ) ? $revision['saved_at'] : '' ),
				'action'    => sanitize_key( isset( $revision['action'] ) ? $revision['action'] : '' ),
			);
		}
		return $safe;
	}
}

if ( ! function_exists( 'lunara_site_studio_restore_revision' ) ) {
	/** @return array<string,mixed>|WP_Error */
	function lunara_site_studio_restore_revision( $surface, $revision_id, $read_callback, $validate_callback, $save_callback ) {
		if ( ! is_callable( $read_callback ) || ! is_callable( $validate_callback ) || ! is_callable( $save_callback ) ) {
			return new WP_Error( 'site_studio_restore_unavailable', __( 'The restore service is unavailable.', 'lunara-film' ) );
		}
		$target = null;
		foreach ( lunara_site_studio_list_revisions( $surface, true ) as $revision ) {
			if ( ! empty( $revision['id'] ) && hash_equals( (string) $revision['id'], sanitize_text_field( $revision_id ) ) && isset( $revision['config'] ) && is_array( $revision['config'] ) ) {
				$target = $revision['config'];
				break;
			}
		}
		if ( null === $target ) {
			return new WP_Error( 'site_studio_revision_not_found', __( 'The selected revision was not found.', 'lunara-film' ) );
		}
		$validated = call_user_func( $validate_callback, $target );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}
		$current = call_user_func( $read_callback );
		if ( is_wp_error( $current ) || ! is_array( $current ) ) {
			return is_wp_error( $current ) ? $current : new WP_Error( 'site_studio_restore_snapshot_failed' );
		}
		$safety_id = lunara_site_studio_push_revision( $surface, $current, 'restore-safety' );
		if ( is_wp_error( $safety_id ) ) {
			return $safety_id;
		}
		$saved = call_user_func( $save_callback, $validated );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		return array( 'state' => $saved, 'safety_revision_id' => $safety_id, 'timestamp' => current_time( 'mysql' ) );
	}
}
