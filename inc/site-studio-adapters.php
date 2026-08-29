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
if ( ! defined( 'LUNARA_SITE_STUDIO_PROJECTION_MAX_DEPTH' ) ) {
	define( 'LUNARA_SITE_STUDIO_PROJECTION_MAX_DEPTH', 32 );
}
if ( ! defined( 'LUNARA_SITE_STUDIO_PROJECTION_MAX_NODES' ) ) {
	define( 'LUNARA_SITE_STUDIO_PROJECTION_MAX_NODES', 4096 );
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

if ( ! class_exists( 'Lunara_Site_Studio_Theme_Adapter' ) ) {
	/** Small callback adapter for canonical theme-owned state. */
	class Lunara_Site_Studio_Theme_Adapter implements Lunara_Site_Studio_Surface_Adapter {
		private $surface;
		private $owner;
		private $read_callback;
		private $validate_callback;
		private $save_callback;
		private $restore_callback;

		public function __construct( $surface, $owner, $callbacks ) {
			$this->surface = sanitize_key( $surface );
			$this->owner = sanitize_text_field( $owner );
			$this->read_callback = isset( $callbacks['read'] ) ? $callbacks['read'] : '';
			$this->validate_callback = isset( $callbacks['validate'] ) ? $callbacks['validate'] : '';
			$this->save_callback = isset( $callbacks['save'] ) ? $callbacks['save'] : '';
			$this->restore_callback = isset( $callbacks['restore'] ) ? $callbacks['restore'] : '';
		}

		private function invoke( $callback, $arguments = array() ) {
			if ( ! is_callable( $callback ) ) {
				return new WP_Error( 'site_studio_adapter_unavailable', __( 'The destination adapter is unavailable.', 'lunara-film' ) );
			}
			$callback_key = is_string( $callback ) ? $callback : 'theme-callback';
			$guard_key = $this->surface . '-' . $callback_key;
			if ( ! lunara_site_studio_boundary_guard( 'theme_callback', $guard_key ) ) {
				return new WP_Error( 'site_studio_adapter_unavailable', __( 'The destination adapter is unavailable.', 'lunara-film' ) );
			}
			try {
				return call_user_func_array( $callback, $arguments );
			} catch ( Throwable $error ) {
				return new WP_Error( 'site_studio_adapter_unavailable', __( 'The destination adapter is unavailable.', 'lunara-film' ) );
			} finally {
				lunara_site_studio_boundary_guard( 'theme_callback', $guard_key, false );
			}
		}

		public function read_state() { return $this->invoke( $this->read_callback ); }
		public function validate_state( $candidate ) { return $this->invoke( $this->validate_callback, array( $candidate ) ); }
		public function save_state( $candidate ) { return $this->invoke( $this->save_callback, array( $candidate ) ); }
		public function create_preview( $candidate ) {
			$validated = $this->validate_state( $candidate );
			if ( is_wp_error( $validated ) ) { return $validated; }
			$registered = lunara_site_studio_get_surface( $this->surface );
			$route = is_array( $registered ) && ! empty( $registered['preview_route'] ) ? $registered['preview_route'] : '';
			if ( '' === $route ) {
				return new WP_Error( 'site_studio_preview_unavailable', __( 'This destination does not have a private preview route.', 'lunara-film' ) );
			}
			$token = lunara_site_studio_store_private_preview( $this->surface, $this->owner, $route, $validated );
			return is_wp_error( $token ) ? $token : array( 'token' => $token, 'expires_at' => lunara_site_studio_timestamp() + LUNARA_SITE_STUDIO_PREVIEW_TTL );
		}
		public function list_revisions() { return lunara_site_studio_list_revisions( $this->surface ); }
		public function restore_revision( $revision_id ) { return $this->invoke( $this->restore_callback, array( sanitize_text_field( $revision_id ) ) ); }
	}
}

if ( ! function_exists( 'lunara_site_studio_raw_option_snapshot' ) ) {
	function lunara_site_studio_raw_option_snapshot( $option, $include_autoload = false ) {
		$missing = new stdClass();
		$value = get_option( $option, $missing );
		$snapshot = array( 'present' => $missing !== $value, 'value' => $missing !== $value ? $value : null );
		if ( $include_autoload ) { $snapshot['autoload'] = $missing !== $value ? lunara_site_studio_option_autoload_state( $option ) : null; }
		return $snapshot;
	}
}
if ( ! function_exists( 'lunara_site_studio_option_autoload_state' ) ) {
	/** Read persisted option autoload state; null means absent or unverifiable. */
	function lunara_site_studio_option_autoload_state( $option ) {
		global $wpdb;
		if ( ! is_object( $wpdb ) || empty( $wpdb->options ) || ! method_exists( $wpdb, 'prepare' ) || ! method_exists( $wpdb, 'get_var' ) ) { return null; }
		$stored = $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", $option ) );
		if ( null === $stored ) { return null; }
		$autoload_values = function_exists( 'wp_autoload_values_to_autoload' ) ? wp_autoload_values_to_autoload() : array( 'yes', 'on', 'auto-on', 'auto' );
		return in_array( $stored, $autoload_values, true );
	}
}
if ( ! function_exists( 'lunara_site_studio_apply_option_snapshot' ) ) {
	function lunara_site_studio_apply_option_snapshot( $option, $snapshot ) {
		$present = is_array( $snapshot ) && ! empty( $snapshot['present'] );
		$include_autoload = is_array( $snapshot ) && array_key_exists( 'autoload', $snapshot );
		try {
			$before = lunara_site_studio_raw_option_snapshot( $option, $include_autoload );
			if ( $present ) {
				$value = array_key_exists( 'value', $snapshot ) ? $snapshot['value'] : null;
				$autoload = $include_autoload ? $snapshot['autoload'] : false;
				if ( $include_autoload && ! is_bool( $autoload ) ) { return false; }
				update_option( $option, $value, $autoload );
				if ( $include_autoload ) {
					$current = lunara_site_studio_raw_option_snapshot( $option, true );
					if ( $current['autoload'] !== $autoload ) {
						if ( function_exists( 'wp_set_option_autoload_values' ) ) { wp_set_option_autoload_values( array( $option => $autoload ) ); }
						elseif ( ! $current['present'] || $current['value'] !== $value || ! lunara_site_studio_legacy_replace_option( $option, $value, $autoload, $before ) ) { return false; }
					}
				}
			} else { delete_option( $option ); }
			$expected = array( 'present' => $present, 'value' => $present && array_key_exists( 'value', $snapshot ) ? $snapshot['value'] : null );
			if ( $include_autoload ) { $expected['autoload'] = $present ? $snapshot['autoload'] : null; }
			return lunara_site_studio_raw_option_snapshot( $option, $include_autoload ) === $expected;
		} catch ( Throwable $error ) { return false; }
	}
}
if ( ! function_exists( 'lunara_site_studio_legacy_replace_option' ) ) {
	/** WordPress 6.0-compatible same-value autoload transition with exact local rollback. */
	function lunara_site_studio_legacy_replace_option( $option, $value, $autoload, $rollback ) {
		$verify = static function ( $expected_present, $expected_value, $expected_autoload ) use ( $option ) {
			$missing = new stdClass();
			$stored = get_option( $option, $missing );
			$alloptions = function_exists( 'wp_load_alloptions' ) ? wp_load_alloptions() : array();
			return ( $missing !== $stored ) === $expected_present && ( ! $expected_present || $stored === $expected_value ) && (bool) array_key_exists( $option, $alloptions ) === ( $expected_present && $expected_autoload );
		};
		$restore = static function () use ( $option, $rollback, $verify ) {
			try {
				delete_option( $option );
				if ( ! empty( $rollback['present'] ) && ! add_option( $option, $rollback['value'], '', ! empty( $rollback['autoload'] ) ? 'yes' : 'no' ) ) { return false; }
				return $verify( ! empty( $rollback['present'] ), ! empty( $rollback['present'] ) ? $rollback['value'] : null, ! empty( $rollback['autoload'] ) );
			} catch ( Throwable $error ) { return false; }
		};
		try {
			if ( ! delete_option( $option ) || ! add_option( $option, $value, '', $autoload ? 'yes' : 'no' ) || ! $verify( true, $value, $autoload ) ) { $restore(); return false; }
			return true;
		} catch ( Throwable $error ) { $restore(); return false; }
	}
}
if ( ! function_exists( 'lunara_site_studio_raw_mod_snapshot' ) ) {
	function lunara_site_studio_raw_mod_snapshot( $keys ) {
		$snapshot = array();
		foreach ( $keys as $key ) {
			$missing = new stdClass();
			$value = get_theme_mod( $key, $missing );
			$snapshot[ $key ] = array( 'present' => $missing !== $value, 'value' => $missing !== $value ? $value : null );
		}
		return $snapshot;
	}
}
if ( ! function_exists( 'lunara_site_studio_apply_mod_snapshot' ) ) {
	function lunara_site_studio_apply_mod_snapshot( $snapshot, $allowed_keys ) {
		if ( ! lunara_site_studio_valid_mod_snapshot( $snapshot, $allowed_keys ) ) { return false; }
		try {
			foreach ( $allowed_keys as $key ) {
				$entry = $snapshot[ $key ];
				if ( $entry['present'] ) { set_theme_mod( $key, $entry['value'] ); } else { remove_theme_mod( $key ); }
				if ( array( $key => $entry ) !== lunara_site_studio_raw_mod_snapshot( array( $key ) ) ) { return false; }
			}
		} catch ( Throwable $error ) { return false; }
		return true;
	}
}
if ( ! function_exists( 'lunara_site_studio_valid_mod_snapshot' ) ) {
	function lunara_site_studio_valid_mod_snapshot( $snapshot, $allowed_keys ) {
		if ( ! is_array( $snapshot ) || ! is_array( $allowed_keys ) || array_values( $allowed_keys ) !== array_keys( $snapshot ) ) { return false; }
		foreach ( $allowed_keys as $key ) {
			$entry = $snapshot[ $key ];
			if ( ! is_array( $entry ) || array( 'present', 'value' ) !== array_keys( $entry ) || ! is_bool( $entry['present'] ) || ( ! $entry['present'] && null !== $entry['value'] ) || ( $entry['present'] && ! is_scalar( $entry['value'] ) && null !== $entry['value'] ) ) { return false; }
		}
		return true;
	}
}
if ( ! function_exists( 'lunara_site_studio_private_revision' ) ) {
	/** Store one private snapshot and restore the revision option if creation is not durable. */
	function lunara_site_studio_private_revision( $surface, $snapshot, $action ) {
		$option = lunara_site_studio_revision_option_name( $surface );
		try {
			$before = lunara_site_studio_raw_option_snapshot( $option );
			$id = lunara_site_studio_push_revision( $surface, $snapshot, $action );
		} catch ( Throwable $error ) { return new WP_Error( 'site_studio_revision_write_failed', __( 'The safety revision could not be stored.', 'lunara-film' ) ); }
		if ( is_wp_error( $id ) ) {
			if ( ! lunara_site_studio_apply_option_snapshot( $option, $before ) ) {
				return new WP_Error( 'site_studio_revision_rollback_failed', __( 'Revision history could not be restored safely.', 'lunara-film' ) );
			}
			return $id;
		}
		return $id;
	}
}
if ( ! function_exists( 'lunara_site_studio_private_revision_target' ) ) {
	function lunara_site_studio_private_revision_target( $surface, $revision_id ) {
		foreach ( lunara_site_studio_list_revisions( $surface, true ) as $revision ) {
			if ( is_array( $revision ) && ! empty( $revision['id'] ) && hash_equals( (string) $revision['id'], (string) $revision_id ) && isset( $revision['config'] ) && is_array( $revision['config'] ) ) { return $revision['config']; }
		}
		return new WP_Error( 'site_studio_revision_not_found', __( 'The selected revision was not found.', 'lunara-film' ) );
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
		private $managed_paths;
		private $validation_fields;

		/** @param array<string,mixed> $callbacks Provider callback map and managed state paths. */
		public function __construct( $surface, $callbacks ) {
			$this->surface            = sanitize_key( $surface );
			$this->read_callback      = isset( $callbacks['read'] ) ? $callbacks['read'] : '';
			$this->validate_callback  = isset( $callbacks['validate'] ) ? $callbacks['validate'] : '';
			$this->save_callback      = isset( $callbacks['save'] ) ? $callbacks['save'] : '';
			$this->preview_callback   = isset( $callbacks['preview'] ) ? $callbacks['preview'] : '';
			$this->revisions_callback = isset( $callbacks['revisions'] ) ? $callbacks['revisions'] : '';
			$this->restore_callback   = isset( $callbacks['restore'] ) ? $callbacks['restore'] : '';
			$this->managed_paths      = array();
			foreach ( isset( $callbacks['managed_paths'] ) && is_array( $callbacks['managed_paths'] ) ? $callbacks['managed_paths'] : array() as $path ) {
				if ( is_string( $path ) && 1 === preg_match( '/^[a-z0-9_-]+(?:\.[a-z0-9_-]+)*$/D', $path ) ) {
					$this->managed_paths[] = $path;
				}
			}
			$this->validation_fields = array();
			foreach ( isset( $callbacks['validation_fields'] ) && is_array( $callbacks['validation_fields'] ) ? $callbacks['validation_fields'] : array() as $code => $paths ) {
				$code = sanitize_key( $code );
				if ( ! $code || ! is_array( $paths ) ) { continue; }
				foreach ( $paths as $path ) {
					if ( is_string( $path ) && 1 === preg_match( '/^[a-z0-9_-]+(?:\.[a-z0-9_-]+)*$/D', $path ) ) { $this->validation_fields[ $code ][] = $path; }
				}
				if ( isset( $this->validation_fields[ $code ] ) ) { $this->validation_fields[ $code ] = array_values( array_unique( $this->validation_fields[ $code ] ) ); }
			}
		}

		/** Add bounded inspector metadata when a mature provider returns only an error code. */
		private function annotate_validation_error( $error ) {
			if ( ! is_wp_error( $error ) ) { return $error; }
			$data = $error->get_error_data();
			if ( is_array( $data ) && isset( $data['fields'] ) && is_array( $data['fields'] ) && $data['fields'] ) { return $error; }
			$code = sanitize_key( $error->get_error_code() );
			if ( empty( $this->validation_fields[ $code ] ) ) { return $error; }
			$fields = array();
			foreach ( $this->validation_fields[ $code ] as $path ) { $fields[ $path ] = __( 'Review this control and try again.', 'lunara-film' ); }
			$data = is_array( $data ) ? $data : array();
			$data['fields'] = $fields;
			return new WP_Error( $code, $error->get_error_message(), $data );
		}

		/** Merge only inspector-owned leaves into a fresh provider read without reordering canonical keys. */
		private function prepare_candidate( $candidate, $current ) {
			if ( empty( $this->managed_paths ) ) {
				return $candidate;
			}
			if ( ! is_array( $candidate ) || ! is_array( $current ) ) {
				return new WP_Error( 'site_studio_managed_state_invalid', __( 'The destination state is incomplete.', 'lunara-film' ) );
			}
			$merged = $current;
			foreach ( $this->managed_paths as $path ) {
				$parts = explode( '.', $path );
				$source = $candidate;
				foreach ( $parts as $part ) {
					if ( ! is_array( $source ) || ! array_key_exists( $part, $source ) ) {
						$field = 0 === strpos( $path, 'section_visibility.' ) ? 'section_visibility' : $path;
						return new WP_Error( 'site_studio_managed_state_invalid', __( 'The destination state is incomplete.', 'lunara-film' ), array( 'fields' => array( $field => __( 'Reload this destination and try again.', 'lunara-film' ) ) ) );
					}
					$source = $source[ $part ];
				}

				$target =& $merged;
				$last = count( $parts ) - 1;
				foreach ( $parts as $index => $part ) {
					if ( ! is_array( $target ) || ! array_key_exists( $part, $target ) ) {
						unset( $target );
						return new WP_Error( 'site_studio_provider_state_invalid', __( 'The canonical destination state is incomplete.', 'lunara-film' ) );
					}
					if ( $last === $index ) {
						$target[ $part ] = $source;
					} else {
						$target =& $target[ $part ];
					}
				}
				unset( $target );
			}
			return $this->validate_state( $merged );
		}

		/** @return WP_Error|null */
		private function callback_error( $callback ) {
			return is_callable( $callback ) ? null : new WP_Error( 'site_studio_adapter_unavailable', __( 'The canonical surface provider is unavailable.', 'lunara-film' ) );
		}

		/** @return mixed|WP_Error */
		private function invoke( $callback, $arguments = array() ) {
			$error = $this->callback_error( $callback );
			if ( $error ) {
				return $error;
			}
			$callback_key = is_string( $callback ) ? $callback : 'provider-callback';
			$guard_key = $this->surface . '-' . $callback_key;
			if ( ! lunara_site_studio_boundary_guard( 'provider_callback', $guard_key ) ) {
				return new WP_Error( 'site_studio_adapter_unavailable', __( 'The canonical surface provider is unavailable.', 'lunara-film' ) );
			}
			try {
				return call_user_func_array( $callback, $arguments );
			} catch ( Throwable $error ) {
				return new WP_Error( 'site_studio_adapter_unavailable', __( 'The canonical surface provider is unavailable.', 'lunara-film' ) );
			} finally {
				lunara_site_studio_boundary_guard( 'provider_callback', $guard_key, false );
			}
		}

		public function read_state() {
			return $this->invoke( $this->read_callback, array( false ) );
		}

		public function validate_state( $candidate ) {
			return $this->annotate_validation_error( $this->invoke( $this->validate_callback, array( $candidate ) ) );
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
			$prepared = $this->prepare_candidate( $candidate, $before );
			if ( is_wp_error( $prepared ) ) {
				return $prepared;
			}
			$result = $this->invoke( $this->save_callback, array( $prepared, 'site-studio-save' ) );
			if ( is_wp_error( $result ) ) {
				return $this->annotate_validation_error( $result );
			}
			if ( ! is_array( $result ) || ! isset( $result['state'], $result['revision_id'] ) || ! is_array( $result['state'] ) ) {
				return new WP_Error( 'site_studio_revision_unverified', __( 'The provider revision could not be verified.', 'lunara-film' ) );
			}
			$revisions = $this->list_revisions();
			$revision_id = lunara_site_studio_verify_operation_revision( $result['revision_id'], $revisions );
			if ( is_wp_error( $revision_id ) ) {
				return $revision_id;
			}
			return array(
				'state'            => $result['state'],
				'changed_sections' => lunara_site_studio_changed_sections( $this->surface, $before, $result['state'] ),
				'revision_id'      => $revision_id,
				'timestamp'        => current_time( 'mysql' ),
			);
		}

		public function create_preview( $candidate ) {
			$error = $this->callback_error( $this->preview_callback );
			if ( $error ) {
				return $error;
			}
			$prepared = $candidate;
			if ( ! empty( $this->managed_paths ) ) {
				$current = $this->read_state();
				if ( is_wp_error( $current ) ) {
					return $current;
				}
				$prepared = $this->prepare_candidate( $candidate, $current );
				if ( is_wp_error( $prepared ) ) {
					return $prepared;
				}
			}
			$token = $this->invoke( $this->preview_callback, array( $prepared ) );
			if ( is_wp_error( $token ) ) {
				return $this->annotate_validation_error( $token );
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
			$revisions = $this->invoke( $this->revisions_callback );
			if ( is_wp_error( $revisions ) ) {
				return $revisions;
			}
			$revisions = is_array( $revisions ) ? array_slice( $revisions, 0, LUNARA_SITE_STUDIO_REVISION_LIMIT ) : array();
			foreach ( $revisions as &$revision ) {
				if ( is_array( $revision ) && ! empty( $revision['prior_public'] ) && isset( $revision['action'] ) && 'restore' === $revision['action'] ) {
					$revision['action'] = 'restore-safety';
				}
			}
			unset( $revision );
			return $revisions;
		}

		public function restore_revision( $revision_id ) {
			$error = $this->callback_error( $this->restore_callback );
			if ( $error ) {
				return $error;
			}
			$result = $this->invoke( $this->restore_callback, array( sanitize_text_field( $revision_id ) ) );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			if ( ! is_array( $result ) || ! isset( $result['state'], $result['safety_revision_id'] ) || ! is_array( $result['state'] ) ) {
				return new WP_Error( 'site_studio_revision_unverified', __( 'The provider revision could not be verified.', 'lunara-film' ) );
			}
			$revisions = $this->list_revisions();
			$safety_id = lunara_site_studio_verify_operation_revision( $result['safety_revision_id'], $revisions );
			if ( is_wp_error( $safety_id ) ) {
				return $safety_id;
			}
			return array(
				'state'              => $result['state'],
				'safety_revision_id' => $safety_id,
				'timestamp'          => current_time( 'mysql' ),
			);
		}
	}
}

if ( ! function_exists( 'lunara_site_studio_verify_operation_revision' ) ) {
	/** Verify the exact in-band UUID returned by a provider transaction. */
	function lunara_site_studio_verify_operation_revision( $revision_id, $revisions ) {
		$revision_id = sanitize_text_field( $revision_id );
		if ( '' === $revision_id || ! is_array( $revisions ) ) {
			return new WP_Error( 'site_studio_revision_unverified', __( 'The provider revision could not be verified.', 'lunara-film' ) );
		}
		foreach ( $revisions as $revision ) {
			if ( is_array( $revision ) && ! empty( $revision['id'] ) && hash_equals( $revision_id, (string) $revision['id'] ) ) {
				return $revision_id;
			}
		}
		return new WP_Error( 'site_studio_revision_unverified', __( 'The provider revision could not be verified.', 'lunara-film' ) );
	}
}

if ( ! function_exists( 'lunara_site_studio_reviews_archive_dependency' ) ) {
	function lunara_site_studio_reviews_archive_dependency() {
		return function_exists( 'lunara_reviews_archive_studio_get_public_config' ) && function_exists( 'lunara_reviews_archive_studio_validate_config' ) && function_exists( 'lunara_reviews_archive_studio_promote_config_transaction' ) && function_exists( 'lunara_reviews_archive_studio_store_preview' ) && function_exists( 'lunara_reviews_archive_studio_get_revisions' ) && function_exists( 'lunara_reviews_archive_studio_restore_revision_transaction' );
	}
}
if ( ! function_exists( 'lunara_site_studio_journal_archive_dependency' ) ) {
	function lunara_site_studio_journal_archive_dependency() {
		return function_exists( 'lunara_journal_archive_studio_get_public_config' ) && function_exists( 'lunara_journal_archive_studio_validate_config' ) && function_exists( 'lunara_journal_archive_studio_promote_config_transaction' ) && function_exists( 'lunara_journal_archive_studio_store_preview' ) && function_exists( 'lunara_journal_archive_studio_get_revisions' ) && function_exists( 'lunara_journal_archive_studio_restore_revision_transaction' );
	}
}
if ( ! function_exists( 'lunara_site_studio_oscars_portal_dependency' ) ) {
	function lunara_site_studio_oscars_portal_dependency() {
		return function_exists( 'lunara_oscars_portal_studio_get_public_config' ) && function_exists( 'lunara_oscars_portal_studio_validate_config' ) && function_exists( 'lunara_oscars_portal_studio_promote_config_transaction' ) && function_exists( 'lunara_oscars_portal_studio_store_preview' ) && function_exists( 'lunara_oscars_portal_studio_get_revisions' ) && function_exists( 'lunara_oscars_portal_studio_restore_revision_transaction' );
	}
}
if ( ! function_exists( 'lunara_site_studio_oscars_ledger_dependency' ) ) {
	function lunara_site_studio_oscars_ledger_dependency() {
		return function_exists( 'lunara_oscars_plugin_instance' ) && is_object( lunara_oscars_plugin_instance() );
	}
}

if ( ! function_exists( 'lunara_site_studio_schema_from_shape' ) ) {
	/** Build a private scalar/list/object allowlist from a theme-owned shape. */
	function lunara_site_studio_schema_from_shape( $shape ) {
		if ( ! is_array( $shape ) ) {
			return true;
		}
		if ( array() === $shape ) {
			return array();
		}
		$is_list = array_keys( $shape ) === range( 0, count( $shape ) - 1 );
		if ( $is_list ) {
			return array( '*' => lunara_site_studio_schema_from_shape( reset( $shape ) ) );
		}
		$schema = array();
		foreach ( $shape as $key => $value ) {
			$schema[ sanitize_key( $key ) ] = lunara_site_studio_schema_from_shape( $value );
		}
		return $schema;
	}
}

if ( ! function_exists( 'lunara_site_studio_archive_gallery_item_schema' ) ) {
	/** @return array<string,bool> */
	function lunara_site_studio_archive_gallery_item_schema() {
		return array_fill_keys( array( 'order', 'attachment_id', 'alt', 'caption', 'link_url', 'credit', 'source', 'source_url', 'focal_x', 'focal_y' ), true );
	}
}

if ( ! function_exists( 'lunara_site_studio_reviews_archive_state_schema' ) ) {
	function lunara_site_studio_reviews_archive_state_schema() {
		$defaults = function_exists( 'lunara_reviews_archive_studio_defaults' ) ? lunara_reviews_archive_studio_defaults() : array();
		$schema   = lunara_site_studio_schema_from_shape( $defaults );
		$schema['curated_ids'] = array( '*' => true );
		$schema['gallery']['items'] = array( '*' => lunara_site_studio_archive_gallery_item_schema() );
		return $schema;
	}
}

if ( ! function_exists( 'lunara_site_studio_journal_archive_state_schema' ) ) {
	function lunara_site_studio_journal_archive_state_schema() {
		$defaults = function_exists( 'lunara_journal_archive_studio_defaults' ) ? lunara_journal_archive_studio_defaults() : array();
		$schema   = lunara_site_studio_schema_from_shape( $defaults );
		$schema['curated_ids'] = array( '*' => true );
		$schema['gallery']['items'] = array( '*' => lunara_site_studio_archive_gallery_item_schema() );
		return $schema;
	}
}

if ( ! function_exists( 'lunara_site_studio_oscars_portal_state_schema' ) ) {
	function lunara_site_studio_oscars_portal_state_schema() {
		$defaults = function_exists( 'lunara_oscars_portal_studio_defaults' ) ? lunara_oscars_portal_studio_defaults() : array();
		return lunara_site_studio_schema_from_shape( $defaults );
	}
}

if ( ! function_exists( 'lunara_site_studio_global_design_state_schema' ) ) {
	function lunara_site_studio_global_design_state_schema() {
		$item = array( 'override' => true, 'effective' => true, 'source' => true );
		return array(
			'colors' => array_fill_keys( array_keys( lunara_design_token_color_specs() ), $item ),
			'fonts'  => array_fill_keys( array_keys( lunara_design_token_font_role_specs() ), $item ),
		);
	}
}
if ( ! function_exists( 'lunara_site_studio_global_design_build_state' ) ) {
	function lunara_site_studio_global_design_build_state( $colors, $fonts ) {
		$colors = is_array( $colors ) ? $colors : array();
		$fonts = is_array( $fonts ) ? $fonts : array();
		$state = array( 'colors' => array(), 'fonts' => array() );
		foreach ( lunara_design_token_color_specs() as $key => $spec ) {
			$effective = lunara_design_token_effective_color( $key, $colors );
			$state['colors'][ $key ] = array(
				'override' => array_key_exists( $key, $colors ) && is_scalar( $colors[ $key ] ) ? (string) $colors[ $key ] : null,
				'effective' => (string) $effective['value'],
				'source' => (string) $effective['source'],
			);
		}
		$choices = lunara_design_token_font_choices();
		foreach ( lunara_design_token_font_role_specs() as $role => $spec ) {
			$present = array_key_exists( $role, $fonts );
			$override = $present && is_scalar( $fonts[ $role ] ) ? sanitize_key( $fonts[ $role ] ) : null;
			$effective = $override && isset( $choices[ $override ] ) ? $override : $spec['default'];
			$state['fonts'][ $role ] = array( 'override' => $override, 'effective' => $effective, 'source' => $present ? 'design-tokens' : 'shipped-default' );
		}
		return $state;
	}
}
if ( ! function_exists( 'lunara_site_studio_global_design_read_state' ) ) {
	function lunara_site_studio_global_design_read_state() {
		$tokens = get_option( 'lunara_design_tokens', array() );
		$tokens = is_array( $tokens ) ? $tokens : array();
		return lunara_site_studio_global_design_build_state(
			isset( $tokens['colors'] ) && is_array( $tokens['colors'] ) ? $tokens['colors'] : array(),
			isset( $tokens['fonts'] ) && is_array( $tokens['fonts'] ) ? $tokens['fonts'] : array()
		);
	}
}
if ( ! function_exists( 'lunara_site_studio_global_design_validate_state' ) ) {
	function lunara_site_studio_global_design_validate_state( $candidate ) {
		$fields = array();
		if ( ! is_array( $candidate ) || ! isset( $candidate['colors'], $candidate['fonts'] ) || ! is_array( $candidate['colors'] ) || ! is_array( $candidate['fonts'] ) ) {
			return new WP_Error( 'site_studio_global_invalid', __( 'Global Design state is incomplete.', 'lunara-film' ), array( 'fields' => array( 'colors' => __( 'Complete every color role.', 'lunara-film' ), 'fonts' => __( 'Complete every typography role.', 'lunara-film' ) ) ) );
		}
		$colors = array();
		foreach ( lunara_design_token_color_specs() as $key => $spec ) {
			if ( ! isset( $candidate['colors'][ $key ] ) || ! is_array( $candidate['colors'][ $key ] ) || ! array_key_exists( 'override', $candidate['colors'][ $key ] ) ) { $fields[ 'color_' . $key ] = __( 'Choose a valid color override or clear it.', 'lunara-film' ); continue; }
			$value = $candidate['colors'][ $key ]['override'];
			if ( null === $value ) { continue; }
			$value = is_scalar( $value ) ? sanitize_hex_color( (string) $value ) : false;
			if ( ! $value ) { $fields[ 'color_' . $key ] = __( 'Choose a valid six-digit color.', 'lunara-film' ); } else { $colors[ $key ] = $value; }
		}
		$fonts = array();
		$choices = lunara_design_token_font_choices();
		foreach ( lunara_design_token_font_role_specs() as $role => $spec ) {
			if ( ! isset( $candidate['fonts'][ $role ] ) || ! is_array( $candidate['fonts'][ $role ] ) || ! array_key_exists( 'override', $candidate['fonts'][ $role ] ) ) { $fields[ 'font_' . $role ] = __( 'Choose a valid typography override or clear it.', 'lunara-film' ); continue; }
			$value = $candidate['fonts'][ $role ]['override'];
			if ( null === $value ) { continue; }
			$value = is_scalar( $value ) ? sanitize_key( $value ) : '';
			if ( ! isset( $choices[ $value ] ) ) { $fields[ 'font_' . $role ] = __( 'Choose a supported typeface.', 'lunara-film' ); } else { $fonts[ $role ] = $value; }
		}
		if ( $fields ) { return new WP_Error( 'site_studio_global_invalid', __( 'Review the invalid Global Design fields.', 'lunara-film' ), array( 'fields' => $fields ) ); }
		return lunara_site_studio_global_design_build_state( $colors, $fonts );
	}
}
if ( ! function_exists( 'lunara_site_studio_global_design_save_state' ) ) {
	function lunara_site_studio_global_design_save_state( $candidate ) {
		$validated = lunara_site_studio_global_design_validate_state( $candidate );
		if ( is_wp_error( $validated ) ) { return $validated; }
		$before = lunara_site_studio_raw_option_snapshot( 'lunara_design_tokens', true );
		$colors = array(); $fonts = array();
		foreach ( $validated['colors'] as $key => $item ) { if ( null !== $item['override'] ) { $colors[ $key ] = $item['override']; } }
		foreach ( $validated['fonts'] as $key => $item ) { if ( null !== $item['override'] ) { $fonts[ $key ] = $item['override']; } }
		$value = array( 'colors' => $colors, 'fonts' => $fonts );
		$desired = empty( $colors ) && empty( $fonts ) ? array( 'present' => false, 'value' => null, 'autoload' => null ) : array( 'present' => true, 'value' => $value, 'autoload' => false );
		if ( ! lunara_site_studio_apply_option_snapshot( 'lunara_design_tokens', $desired ) ) {
			if ( ! lunara_site_studio_apply_option_snapshot( 'lunara_design_tokens', $before ) ) { return new WP_Error( 'site_studio_global_rollback_failed', __( 'Global Design could not be restored safely.', 'lunara-film' ) ); }
			return new WP_Error( 'site_studio_global_write_failed', __( 'Global Design could not be saved.', 'lunara-film' ) );
		}
		$revision_id = lunara_site_studio_private_revision( 'global-design', array( 'option' => $before ), 'save' );
		if ( is_wp_error( $revision_id ) ) {
			if ( ! lunara_site_studio_apply_option_snapshot( 'lunara_design_tokens', $before ) ) { return new WP_Error( 'site_studio_global_rollback_failed', __( 'Global Design could not be restored safely.', 'lunara-film' ) ); }
			return $revision_id;
		}
		return array( 'state' => lunara_site_studio_global_design_read_state(), 'changed_sections' => array( 'colors', 'typography' ), 'revision_id' => $revision_id, 'timestamp' => current_time( 'mysql' ) );
	}
}
if ( ! function_exists( 'lunara_site_studio_valid_global_revision_config' ) ) {
	function lunara_site_studio_valid_global_revision_config( $config ) {
		if ( ! is_array( $config ) || array( 'option' ) !== array_keys( $config ) || ! is_array( $config['option'] ) || array( 'present', 'value', 'autoload' ) !== array_keys( $config['option'] ) || ! is_bool( $config['option']['present'] ) ) { return false; }
		$option = $config['option'];
		if ( ! $option['present'] ) { return null === $option['value'] && null === $option['autoload']; }
		if ( ! is_bool( $option['autoload'] ) || ! is_array( $option['value'] ) || array_diff( array_keys( $option['value'] ), array( 'colors', 'fonts' ) ) ) { return false; }
		$colors = isset( $option['value']['colors'] ) ? $option['value']['colors'] : array();
		$fonts = isset( $option['value']['fonts'] ) ? $option['value']['fonts'] : array();
		if ( ! is_array( $colors ) || ! is_array( $fonts ) || array_diff( array_keys( $colors ), array_keys( lunara_design_token_color_specs() ) ) || array_diff( array_keys( $fonts ), array_keys( lunara_design_token_font_role_specs() ) ) ) { return false; }
		foreach ( array_merge( $colors, $fonts ) as $value ) { if ( ! is_scalar( $value ) ) { return false; } }
		return true;
	}
}
if ( ! function_exists( 'lunara_site_studio_global_design_restore_revision' ) ) {
	function lunara_site_studio_global_design_restore_revision( $revision_id ) {
		$target = lunara_site_studio_private_revision_target( 'global-design', $revision_id );
		if ( is_wp_error( $target ) || ! lunara_site_studio_valid_global_revision_config( $target ) ) { return is_wp_error( $target ) ? $target : new WP_Error( 'site_studio_revision_invalid', __( 'The selected Global Design revision is invalid.', 'lunara-film' ) ); }
		$current = lunara_site_studio_raw_option_snapshot( 'lunara_design_tokens', true );
		$safety_id = lunara_site_studio_private_revision( 'global-design', array( 'option' => $current ), 'restore-safety' );
		if ( is_wp_error( $safety_id ) ) { return $safety_id; }
		if ( ! lunara_site_studio_apply_option_snapshot( 'lunara_design_tokens', $target['option'] ) ) {
			if ( ! lunara_site_studio_apply_option_snapshot( 'lunara_design_tokens', $current ) ) { return new WP_Error( 'site_studio_global_rollback_failed', __( 'Global Design could not be restored safely.', 'lunara-film' ) ); }
			return new WP_Error( 'site_studio_global_restore_failed', __( 'Global Design could not be restored.', 'lunara-film' ) );
		}
		return array( 'state' => lunara_site_studio_global_design_read_state(), 'safety_revision_id' => $safety_id, 'timestamp' => current_time( 'mysql' ) );
	}
}
if ( ! function_exists( 'lunara_site_studio_global_design_adapter' ) ) {
	function lunara_site_studio_global_design_adapter() { return new Lunara_Site_Studio_Theme_Adapter( 'global-design', 'theme:global-design', array( 'read' => 'lunara_site_studio_global_design_read_state', 'validate' => 'lunara_site_studio_global_design_validate_state', 'save' => 'lunara_site_studio_global_design_save_state', 'restore' => 'lunara_site_studio_global_design_restore_revision' ) ); }
}

if ( ! function_exists( 'lunara_site_studio_lunara_method_keys' ) ) {
	function lunara_site_studio_lunara_method_keys() { return array( 'lunara_home_pairing_desk_kicker', 'lunara_home_pairing_desk_title', 'lunara_home_pairing_desk_copy', 'lunara_home_pairing_desk_review_id', 'lunara_home_pairing_desk_backdrop_id' ); }
}
if ( ! function_exists( 'lunara_site_studio_lunara_method_state_schema' ) ) {
	function lunara_site_studio_lunara_method_state_schema() { return array_fill_keys( array( 'kicker', 'title', 'copy', 'review_id', 'backdrop_id' ), true ); }
}
if ( ! function_exists( 'lunara_site_studio_lunara_method_read_state' ) ) {
	function lunara_site_studio_lunara_method_read_state() {
		return array(
			'kicker' => (string) get_theme_mod( 'lunara_home_pairing_desk_kicker', '' ),
			'title' => (string) get_theme_mod( 'lunara_home_pairing_desk_title', '' ),
			'copy' => (string) get_theme_mod( 'lunara_home_pairing_desk_copy', '' ),
			'review_id' => absint( get_theme_mod( 'lunara_home_pairing_desk_review_id', 0 ) ),
			'backdrop_id' => absint( get_theme_mod( 'lunara_home_pairing_desk_backdrop_id', 0 ) ),
		);
	}
}
if ( ! function_exists( 'lunara_site_studio_lunara_method_validate_state' ) ) {
	function lunara_site_studio_lunara_method_validate_state( $candidate ) {
		if ( ! is_array( $candidate ) ) { return new WP_Error( 'site_studio_method_invalid', __( 'The Lunara Method state is incomplete.', 'lunara-film' ) ); }
		$state = array(
			'kicker' => isset( $candidate['kicker'] ) ? trim( sanitize_text_field( $candidate['kicker'] ) ) : '',
			'title' => isset( $candidate['title'] ) ? trim( sanitize_text_field( $candidate['title'] ) ) : '',
			'copy' => isset( $candidate['copy'] ) ? trim( sanitize_textarea_field( $candidate['copy'] ) ) : '',
			'review_id' => isset( $candidate['review_id'] ) ? absint( $candidate['review_id'] ) : 0,
			'backdrop_id' => isset( $candidate['backdrop_id'] ) ? absint( $candidate['backdrop_id'] ) : 0,
		);
		$fields = array();
		if ( $state['review_id'] ) { $post = get_post( $state['review_id'] ); if ( ! $post || 'review' !== $post->post_type || 'publish' !== $post->post_status ) { $fields['review_id'] = __( 'Choose a published Review.', 'lunara-film' ); } }
		if ( $state['backdrop_id'] && ( ! function_exists( 'lunara_control_desk_brand_image_is_valid' ) || ! lunara_control_desk_brand_image_is_valid( $state['backdrop_id'] ) ) ) { $fields['backdrop_id'] = __( 'Choose a valid image.', 'lunara-film' ); }
		return $fields ? new WP_Error( 'site_studio_method_invalid', __( 'Review the invalid Lunara Method fields.', 'lunara-film' ), array( 'fields' => $fields ) ) : $state;
	}
}
if ( ! function_exists( 'lunara_site_studio_lunara_method_desired_mods' ) ) {
	function lunara_site_studio_lunara_method_desired_mods( $state ) {
		$map = array( 'kicker' => 'lunara_home_pairing_desk_kicker', 'title' => 'lunara_home_pairing_desk_title', 'copy' => 'lunara_home_pairing_desk_copy', 'review_id' => 'lunara_home_pairing_desk_review_id', 'backdrop_id' => 'lunara_home_pairing_desk_backdrop_id' );
		$desired = array();
		foreach ( $map as $field => $key ) { $present = in_array( $field, array( 'review_id', 'backdrop_id' ), true ) ? $state[ $field ] > 0 : '' !== $state[ $field ]; $desired[ $key ] = array( 'present' => $present, 'value' => $present ? $state[ $field ] : null ); }
		return $desired;
	}
}
if ( ! function_exists( 'lunara_site_studio_lunara_method_save_state' ) ) {
	function lunara_site_studio_lunara_method_save_state( $candidate ) {
		$validated = lunara_site_studio_lunara_method_validate_state( $candidate ); if ( is_wp_error( $validated ) ) { return $validated; }
		$before = lunara_site_studio_raw_mod_snapshot( lunara_site_studio_lunara_method_keys() );
		$keys = lunara_site_studio_lunara_method_keys();
		if ( ! lunara_site_studio_apply_mod_snapshot( lunara_site_studio_lunara_method_desired_mods( $validated ), $keys ) ) { if ( ! lunara_site_studio_apply_mod_snapshot( $before, $keys ) ) { return new WP_Error( 'site_studio_method_rollback_failed', __( 'The Lunara Method could not be restored safely.', 'lunara-film' ) ); } return new WP_Error( 'site_studio_method_write_failed', __( 'The Lunara Method could not be saved.', 'lunara-film' ) ); }
		$revision_id = lunara_site_studio_private_revision( 'lunara-method', array( 'mods' => $before ), 'save' );
		if ( is_wp_error( $revision_id ) ) { if ( ! lunara_site_studio_apply_mod_snapshot( $before, $keys ) ) { return new WP_Error( 'site_studio_method_rollback_failed' ); } return $revision_id; }
		return array( 'state' => lunara_site_studio_lunara_method_read_state(), 'changed_sections' => array( 'language', 'featured-review', 'backdrop' ), 'revision_id' => $revision_id, 'timestamp' => current_time( 'mysql' ) );
	}
}
if ( ! function_exists( 'lunara_site_studio_lunara_method_restore_revision' ) ) {
	function lunara_site_studio_lunara_method_restore_revision( $revision_id ) {
		$target = lunara_site_studio_private_revision_target( 'lunara-method', $revision_id ); if ( is_wp_error( $target ) || ! lunara_site_studio_valid_method_revision_config( $target ) ) { return is_wp_error( $target ) ? $target : new WP_Error( 'site_studio_revision_invalid', __( 'The selected Lunara Method revision is invalid.', 'lunara-film' ) ); }
		$current = lunara_site_studio_raw_mod_snapshot( lunara_site_studio_lunara_method_keys() );
		$safety_id = lunara_site_studio_private_revision( 'lunara-method', array( 'mods' => $current ), 'restore-safety' ); if ( is_wp_error( $safety_id ) ) { return $safety_id; }
		$keys = lunara_site_studio_lunara_method_keys();
		if ( ! lunara_site_studio_apply_mod_snapshot( $target['mods'], $keys ) ) { if ( ! lunara_site_studio_apply_mod_snapshot( $current, $keys ) ) { return new WP_Error( 'site_studio_method_rollback_failed', __( 'The Lunara Method could not be restored safely.', 'lunara-film' ) ); } return new WP_Error( 'site_studio_method_restore_failed', __( 'The selected Lunara Method revision could not be restored.', 'lunara-film' ) ); }
		return array( 'state' => lunara_site_studio_lunara_method_read_state(), 'safety_revision_id' => $safety_id, 'timestamp' => current_time( 'mysql' ) );
	}
}
if ( ! function_exists( 'lunara_site_studio_valid_method_revision_config' ) ) {
	function lunara_site_studio_valid_method_revision_config( $config ) { return is_array( $config ) && array( 'mods' ) === array_keys( $config ) && lunara_site_studio_valid_mod_snapshot( $config['mods'], lunara_site_studio_lunara_method_keys() ); }
}
if ( ! function_exists( 'lunara_site_studio_lunara_method_adapter' ) ) {
	function lunara_site_studio_lunara_method_adapter() { return new Lunara_Site_Studio_Theme_Adapter( 'lunara-method', 'theme:lunara-method', array( 'read' => 'lunara_site_studio_lunara_method_read_state', 'validate' => 'lunara_site_studio_lunara_method_validate_state', 'save' => 'lunara_site_studio_lunara_method_save_state', 'restore' => 'lunara_site_studio_lunara_method_restore_revision' ) ); }
}

if ( ! function_exists( 'lunara_site_studio_homepage_slugs' ) ) {
	function lunara_site_studio_homepage_slugs() { return array_keys( lunara_home_section_block_map() ); }
}
if ( ! function_exists( 'lunara_site_studio_homepage_mod_keys' ) ) {
	function lunara_site_studio_homepage_mod_keys() {
		return array( 'lunara_home_section_order_preset', 'lunara_home_section_order', 'lunara_home_section_mobile_order', 'lunara_home_show_hero', 'lunara_home_show_latest_reviews', 'lunara_home_show_pairing_desk', 'lunara_home_show_dispatch', 'lunara_home_show_oscar_picks', 'lunara_home_show_oscar_facts' );
	}
}
if ( ! function_exists( 'lunara_site_studio_homepage_filtered_order' ) ) {
	function lunara_site_studio_homepage_filtered_order( $value ) {
		$allowed = lunara_site_studio_homepage_slugs();
		$items = is_array( $value ) ? $value : explode( ',', is_scalar( $value ) ? (string) $value : '' );
		$order = array();
		foreach ( $items as $item ) { $item = sanitize_key( $item ); if ( in_array( $item, $allowed, true ) && ! in_array( $item, $order, true ) ) { $order[] = $item; } }
		foreach ( $allowed as $item ) { if ( ! in_array( $item, $order, true ) ) { $order[] = $item; } }
		return $order;
	}
}
if ( ! function_exists( 'lunara_site_studio_homepage_public_state_from_snapshot' ) ) {
	function lunara_site_studio_homepage_public_state_from_snapshot( $snapshot ) {
		$specs = function_exists( 'lunara_control_desk_homepage_order_preset_specs' ) ? lunara_control_desk_homepage_order_preset_specs() : array();
		$preset = sanitize_key( get_theme_mod( 'lunara_home_section_order_preset', '' ) );
		if ( ! isset( $specs[ $preset ] ) ) { $preset = ''; }
		$default_desktop = $preset ? $specs[ $preset ]['desktop_order'] : lunara_site_studio_homepage_slugs();
		$default_mobile = $preset ? $specs[ $preset ]['mobile_order'] : lunara_site_studio_homepage_slugs();
		$desktop = lunara_site_studio_homepage_filtered_order( get_theme_mod( 'lunara_home_section_order', implode( ',', $default_desktop ) ) );
		$mobile = lunara_site_studio_homepage_filtered_order( get_theme_mod( 'lunara_home_section_mobile_order', implode( ',', $default_mobile ) ) );
		$visibility = array();
		foreach ( lunara_site_studio_homepage_slugs() as $slug ) { $visibility[ $slug ] = '0' !== (string) get_theme_mod( 'lunara_home_show_' . str_replace( '-', '_', $slug ), '1' ); }
		return array( 'mode' => $snapshot['composition_mode'], 'front_page_id' => absint( $snapshot['front_page_id'] ), 'preset' => $preset, 'desktop_order' => $desktop, 'mobile_order' => $mobile, 'visibility' => $visibility );
	}
}
if ( ! function_exists( 'lunara_site_studio_homepage_snapshot' ) ) {
	function lunara_site_studio_homepage_snapshot() {
		$show = get_option( 'show_on_front' );
		$page = get_option( 'page_on_front' );
		$front_page_id = 'page' === $show ? absint( $page ) : 0;
		$content = $front_page_id ? (string) get_post_field( 'post_content', $front_page_id ) : '';
		return array( 'show_on_front' => $show, 'page_on_front' => $page, 'front_page_id' => $front_page_id, 'composition_mode' => lunara_site_studio_homepage_composition_mode( $content ), 'post_content' => $content, 'mods' => lunara_site_studio_raw_mod_snapshot( lunara_site_studio_homepage_mod_keys() ) );
	}
}
if ( ! function_exists( 'lunara_site_studio_homepage_composition_mode' ) ) {
	function lunara_site_studio_homepage_composition_mode( $content ) {
		foreach ( lunara_home_section_block_map() as $block_name ) { if ( has_block( $block_name, (string) $content ) ) { return 'blocks'; } }
		return 'registry';
	}
}
if ( ! function_exists( 'lunara_site_studio_homepage_structure_state_schema' ) ) {
	function lunara_site_studio_homepage_structure_state_schema() { return array( 'mode' => true, 'front_page_id' => true, 'preset' => true, 'desktop_order' => array( '*' => true ), 'mobile_order' => array( '*' => true ), 'visibility' => array_fill_keys( lunara_site_studio_homepage_slugs(), true ) ); }
}
if ( ! function_exists( 'lunara_site_studio_homepage_structure_read_state' ) ) {
	function lunara_site_studio_homepage_structure_read_state() { $snapshot = lunara_site_studio_homepage_snapshot(); return lunara_site_studio_homepage_public_state_from_snapshot( $snapshot ); }
}
if ( ! function_exists( 'lunara_site_studio_homepage_valid_order' ) ) {
	function lunara_site_studio_homepage_valid_order( $value ) {
		if ( ! is_array( $value ) || array_keys( $value ) !== range( 0, count( $value ) - 1 ) || count( $value ) !== count( lunara_site_studio_homepage_slugs() ) ) { return false; }
		$actual = array_values( array_unique( array_map( 'sanitize_key', $value ) ) ); $expected = lunara_site_studio_homepage_slugs(); sort( $actual ); sort( $expected );
		return $actual === $expected;
	}
}
if ( ! function_exists( 'lunara_site_studio_homepage_structure_validate_state' ) ) {
	function lunara_site_studio_homepage_structure_validate_state( $candidate ) {
		$current = lunara_site_studio_homepage_structure_read_state();
		$fields = array();
		if ( ! is_array( $candidate ) ) { return new WP_Error( 'site_studio_homepage_invalid', __( 'Homepage Structure state is incomplete.', 'lunara-film' ), array( 'fields' => array( 'front_page' => __( 'Reload the front page and try again.', 'lunara-film' ) ) ) ); }
		$mode = isset( $candidate['mode'] ) && is_scalar( $candidate['mode'] ) ? sanitize_key( $candidate['mode'] ) : '';
		$front_page_id = isset( $candidate['front_page_id'] ) ? absint( $candidate['front_page_id'] ) : 0;
		if ( $mode !== $current['mode'] || $front_page_id !== $current['front_page_id'] ) { $fields['front_page'] = __( 'The front page changed. Reload before saving.', 'lunara-film' ); }
		$preset = isset( $candidate['preset'] ) && is_scalar( $candidate['preset'] ) ? sanitize_key( $candidate['preset'] ) : '';
		$specs = function_exists( 'lunara_control_desk_homepage_order_preset_specs' ) ? lunara_control_desk_homepage_order_preset_specs() : array();
		if ( '' !== $preset && ! isset( $specs[ $preset ] ) ) { $fields['preset'] = __( 'Choose a valid Homepage preset.', 'lunara-film' ); }
		$desktop = isset( $candidate['desktop_order'] ) ? array_map( 'sanitize_key', (array) $candidate['desktop_order'] ) : array();
		$mobile = isset( $candidate['mobile_order'] ) ? array_map( 'sanitize_key', (array) $candidate['mobile_order'] ) : array();
		if ( ! lunara_site_studio_homepage_valid_order( $desktop ) ) { $fields['desktop_order'] = __( 'Include every Homepage lane once.', 'lunara-film' ); }
		if ( ! lunara_site_studio_homepage_valid_order( $mobile ) ) { $fields['mobile_order'] = __( 'Include every mobile Homepage lane once.', 'lunara-film' ); }
		$visibility = array();
		if ( ! isset( $candidate['visibility'] ) || ! is_array( $candidate['visibility'] ) || lunara_site_studio_homepage_slugs() !== array_keys( $candidate['visibility'] ) ) { $fields['visibility'] = __( 'Choose visibility for every Homepage lane.', 'lunara-film' ); }
		else { foreach ( $candidate['visibility'] as $slug => $visible ) { if ( ! is_bool( $visible ) && ! in_array( $visible, array( 0, 1, '0', '1' ), true ) ) { $fields['visibility'] = __( 'Choose visibility for every Homepage lane.', 'lunara-film' ); break; } $visibility[ $slug ] = (bool) $visible; } }
		if ( $fields ) { return new WP_Error( 'site_studio_homepage_invalid', __( 'Review the invalid Homepage Structure fields.', 'lunara-film' ), array( 'fields' => $fields ) ); }
		return array( 'mode' => $mode, 'front_page_id' => $front_page_id, 'preset' => $preset, 'desktop_order' => $desktop, 'mobile_order' => $mobile, 'visibility' => $visibility );
	}
}
if ( ! function_exists( 'lunara_site_studio_homepage_desired_mods' ) ) {
	function lunara_site_studio_homepage_desired_mods( $state ) {
		$desired = array(
			'lunara_home_section_order_preset' => array( 'present' => '' !== $state['preset'], 'value' => '' !== $state['preset'] ? $state['preset'] : null ),
			'lunara_home_section_order' => array( 'present' => true, 'value' => implode( ',', $state['desktop_order'] ) ),
			'lunara_home_section_mobile_order' => array( 'present' => true, 'value' => implode( ',', $state['mobile_order'] ) ),
		);
		foreach ( $state['visibility'] as $slug => $visible ) { $desired[ 'lunara_home_show_' . str_replace( '-', '_', $slug ) ] = array( 'present' => true, 'value' => $visible ? '1' : '0' ); }
		return $desired;
	}
}
if ( ! function_exists( 'lunara_site_studio_homepage_identity_matches' ) ) {
	function lunara_site_studio_homepage_identity_matches( $snapshot ) {
		$show = get_option( 'show_on_front' ); $page = get_option( 'page_on_front' ); $front = 'page' === $show ? absint( $page ) : 0;
		return $show === $snapshot['show_on_front'] && $page === $snapshot['page_on_front'] && $front === absint( $snapshot['front_page_id'] );
	}
}
if ( ! function_exists( 'lunara_site_studio_homepage_write_content' ) ) {
	function lunara_site_studio_homepage_write_content( $page_id, $content ) {
		try {
			if ( (string) get_post_field( 'post_content', $page_id ) === (string) $content ) { return true; }
			$result = wp_update_post( array( 'ID' => absint( $page_id ), 'post_content' => wp_slash( (string) $content ) ), true );
			if ( is_wp_error( $result ) ) { return $result; }
			return (string) get_post_field( 'post_content', $page_id ) === (string) $content ? true : new WP_Error( 'site_studio_homepage_content_readback_failed', __( 'Homepage content could not be verified.', 'lunara-film' ) );
		} catch ( Throwable $error ) { return new WP_Error( 'site_studio_homepage_content_readback_failed', __( 'Homepage content could not be verified.', 'lunara-film' ) ); }
	}
}
if ( ! function_exists( 'lunara_site_studio_valid_homepage_revision_config' ) ) {
	function lunara_site_studio_valid_homepage_revision_config( $snapshot ) {
		$keys = array( 'show_on_front', 'page_on_front', 'front_page_id', 'composition_mode', 'post_content', 'mods' );
		if ( ! is_array( $snapshot ) || $keys !== array_keys( $snapshot ) || ! is_scalar( $snapshot['show_on_front'] ) || ! is_scalar( $snapshot['page_on_front'] ) || ! is_int( $snapshot['front_page_id'] ) || ! in_array( $snapshot['composition_mode'], array( 'registry', 'blocks' ), true ) || ! is_string( $snapshot['post_content'] ) || ! lunara_site_studio_valid_mod_snapshot( $snapshot['mods'], lunara_site_studio_homepage_mod_keys() ) ) { return false; }
		return lunara_site_studio_homepage_composition_mode( $snapshot['post_content'] ) === $snapshot['composition_mode'];
	}
}
if ( ! function_exists( 'lunara_site_studio_homepage_restore_snapshot' ) ) {
	function lunara_site_studio_homepage_restore_snapshot( $snapshot ) {
		if ( ! lunara_site_studio_valid_homepage_revision_config( $snapshot ) ) { return false; }
		try {
			$keys = lunara_site_studio_homepage_mod_keys();
			$mods_ok = lunara_site_studio_apply_mod_snapshot( $snapshot['mods'], $keys );
			$content_ok = true;
			if ( 'blocks' === $snapshot['composition_mode'] ) { $content_ok = lunara_site_studio_homepage_write_content( $snapshot['front_page_id'], $snapshot['post_content'] ); }
			return $mods_ok && ! is_wp_error( $content_ok ) && lunara_site_studio_homepage_identity_matches( $snapshot ) && $snapshot['mods'] === lunara_site_studio_raw_mod_snapshot( lunara_site_studio_homepage_mod_keys() ) && ( 'blocks' !== $snapshot['composition_mode'] || $snapshot['post_content'] === (string) get_post_field( 'post_content', $snapshot['front_page_id'] ) );
		} catch ( Throwable $error ) { return false; }
	}
}
if ( ! function_exists( 'lunara_site_studio_homepage_apply_transaction' ) ) {
	function lunara_site_studio_homepage_apply_transaction( $target, $rollback ) {
		if ( ! lunara_site_studio_valid_homepage_revision_config( $target ) || ! lunara_site_studio_valid_homepage_revision_config( $rollback ) || ( 'registry' === $rollback['composition_mode'] && 'blocks' === $target['composition_mode'] ) ) { return new WP_Error( 'site_studio_revision_invalid', __( 'The Homepage snapshot is invalid.', 'lunara-film' ) ); }
		try {
			if ( ! lunara_site_studio_homepage_identity_matches( $target ) ) { return new WP_Error( 'site_studio_homepage_identity_changed', __( 'The front page changed. Reload before saving.', 'lunara-film' ), array( 'fields' => array( 'front_page' => __( 'Reload the front page and try again.', 'lunara-film' ) ) ) ); }
			$failure = null;
			if ( ! lunara_site_studio_apply_mod_snapshot( $target['mods'], lunara_site_studio_homepage_mod_keys() ) ) { $failure = new WP_Error( 'site_studio_homepage_transaction_failed', __( 'Homepage settings could not be verified.', 'lunara-film' ) ); }
			if ( ! $failure && $target['post_content'] !== (string) get_post_field( 'post_content', $target['front_page_id'] ) ) { $content_result = lunara_site_studio_homepage_write_content( $target['front_page_id'], $target['post_content'] ); if ( is_wp_error( $content_result ) ) { $failure = $content_result; } }
			if ( ! $failure && ( ! lunara_site_studio_homepage_identity_matches( $target ) || $target['mods'] !== lunara_site_studio_raw_mod_snapshot( lunara_site_studio_homepage_mod_keys() ) || ( 'blocks' === $target['composition_mode'] && $target['post_content'] !== (string) get_post_field( 'post_content', $target['front_page_id'] ) ) ) ) { $failure = new WP_Error( 'site_studio_homepage_transaction_failed', __( 'Homepage state could not be verified.', 'lunara-film' ) ); }
		} catch ( Throwable $error ) { $failure = new WP_Error( 'site_studio_homepage_transaction_failed', __( 'Homepage state could not be verified.', 'lunara-film' ) ); }
		if ( $failure ) { return lunara_site_studio_homepage_restore_snapshot( $rollback ) ? $failure : new WP_Error( 'site_studio_homepage_rollback_failed', __( 'Homepage state could not be restored safely.', 'lunara-film' ) ); }
		return true;
	}
}
if ( ! function_exists( 'lunara_site_studio_homepage_structure_save_state' ) ) {
	function lunara_site_studio_homepage_structure_save_state( $candidate ) {
		$validated = lunara_site_studio_homepage_structure_validate_state( $candidate ); if ( is_wp_error( $validated ) ) { return $validated; }
		$before = lunara_site_studio_homepage_snapshot();
		$target = $before; $target['mods'] = lunara_site_studio_homepage_desired_mods( $validated );
		if ( 'blocks' === $before['composition_mode'] ) { $enabled = array(); foreach ( $validated['desktop_order'] as $slug ) { if ( $validated['visibility'][ $slug ] ) { $enabled[] = $slug; } } $content = lunara_compose_home_section_blocks( $before['post_content'], $enabled ); if ( is_wp_error( $content ) ) { return $content; } $target['post_content'] = $content; $target['composition_mode'] = lunara_site_studio_homepage_composition_mode( $content ); }
		$transaction = lunara_site_studio_homepage_apply_transaction( $target, $before ); if ( is_wp_error( $transaction ) ) { return $transaction; }
		$revision_id = lunara_site_studio_private_revision( 'homepage-structure', $before, 'save' );
		if ( is_wp_error( $revision_id ) ) { if ( ! lunara_site_studio_homepage_restore_snapshot( $before ) ) { return new WP_Error( 'site_studio_homepage_rollback_failed', __( 'Homepage state could not be restored safely.', 'lunara-film' ) ); } return $revision_id; }
		return array( 'state' => lunara_site_studio_homepage_structure_read_state(), 'changed_sections' => lunara_site_studio_homepage_slugs(), 'revision_id' => $revision_id, 'timestamp' => current_time( 'mysql' ) );
	}
}
if ( ! function_exists( 'lunara_site_studio_homepage_structure_restore_revision' ) ) {
	function lunara_site_studio_homepage_structure_restore_revision( $revision_id ) {
		$target = lunara_site_studio_private_revision_target( 'homepage-structure', $revision_id ); if ( is_wp_error( $target ) ) { return $target; }
		$current = lunara_site_studio_homepage_snapshot();
		if ( ! lunara_site_studio_valid_homepage_revision_config( $target ) ) { return new WP_Error( 'site_studio_revision_invalid', __( 'The selected Homepage revision is invalid.', 'lunara-film' ) ); }
		if ( ! lunara_site_studio_homepage_identity_matches( $target ) ) { return new WP_Error( 'site_studio_homepage_identity_changed', __( 'The front page changed. Reload before restoring.', 'lunara-film' ), array( 'fields' => array( 'front_page' => __( 'Reload the front page and try again.', 'lunara-film' ) ) ) ); }
		if ( ! lunara_site_studio_valid_homepage_revision_config( $current ) || $target['composition_mode'] !== $current['composition_mode'] ) { return new WP_Error( 'site_studio_revision_invalid', __( 'The selected Homepage revision is invalid.', 'lunara-film' ) ); }
		$safety_id = lunara_site_studio_private_revision( 'homepage-structure', $current, 'restore-safety' ); if ( is_wp_error( $safety_id ) ) { return $safety_id; }
		$result = lunara_site_studio_homepage_apply_transaction( $target, $current ); if ( is_wp_error( $result ) ) { return $result; }
		return array( 'state' => lunara_site_studio_homepage_structure_read_state(), 'safety_revision_id' => $safety_id, 'timestamp' => current_time( 'mysql' ) );
	}
}
if ( ! function_exists( 'lunara_site_studio_homepage_structure_adapter' ) ) {
	function lunara_site_studio_homepage_structure_adapter() { return new Lunara_Site_Studio_Theme_Adapter( 'homepage-structure', 'theme:homepage-structure', array( 'read' => 'lunara_site_studio_homepage_structure_read_state', 'validate' => 'lunara_site_studio_homepage_structure_validate_state', 'save' => 'lunara_site_studio_homepage_structure_save_state', 'restore' => 'lunara_site_studio_homepage_structure_restore_revision' ) ); }
}

if ( ! function_exists( 'lunara_site_studio_review_single_spec' ) ) {
	/** @return array<string,array<string,array<string,mixed>>> */
	function lunara_site_studio_review_single_spec() {
		return array(
			'review' => array(
				'density' => array( 'mod' => 'lunara_review_single_density', 'type' => 'select', 'default' => 'editorial', 'allowed' => array( 'compact', 'editorial', 'feature' ) ),
				'hero_scale' => array( 'mod' => 'lunara_review_single_hero_scale', 'type' => 'select', 'default' => 'standard', 'allowed' => array( 'standard', 'poster-forward', 'wide-forward' ) ),
				'rail_mode' => array( 'mod' => 'lunara_review_single_rail_mode', 'type' => 'select', 'default' => 'balanced', 'allowed' => array( 'balanced', 'minimal', 'metadata-forward' ) ),
				'debrief_prominence' => array( 'mod' => 'lunara_review_single_debrief_prominence', 'type' => 'select', 'default' => 'standard', 'allowed' => array( 'standard', 'poster-forward', 'signature-forward' ) ),
				'pairing_density' => array( 'mod' => 'lunara_review_single_pairing_density', 'type' => 'select', 'default' => 'editorial', 'allowed' => array( 'compact', 'editorial', 'showcase' ) ),
				'spoiler_treatment' => array( 'mod' => 'lunara_review_single_spoiler_treatment', 'type' => 'select', 'default' => 'standard', 'allowed' => array( 'standard', 'shield-forward', 'high-contrast' ) ),
				'trailer_prominence' => array( 'mod' => 'lunara_review_single_trailer_prominence', 'type' => 'select', 'default' => 'standard', 'allowed' => array( 'standard', 'centered', 'feature' ) ),
				'section_gap' => array( 'mod' => 'lunara_review_single_section_gap', 'type' => 'int', 'default' => 48, 'min' => 24, 'max' => 96 ),
				'debrief_poster_width' => array( 'mod' => 'lunara_review_single_debrief_poster_width', 'type' => 'int', 'default' => 300, 'min' => 220, 'max' => 360 ),
				'related_count' => array( 'mod' => 'lunara_review_related_count', 'type' => 'int', 'default' => 4, 'min' => 2, 'max' => 6 ),
			),
			'pairing' => array(
				'layout' => array( 'mod' => 'lunara_review_pair_with_layout', 'type' => 'select', 'default' => 'wide', 'allowed' => array( 'contained', 'wide', 'feature' ) ),
				'text_depth' => array( 'mod' => 'lunara_review_pair_with_text_depth', 'type' => 'select', 'default' => 'balanced', 'allowed' => array( 'tight', 'balanced', 'full' ) ),
				'mobile_stack' => array( 'mod' => 'lunara_review_pair_with_mobile_stack', 'type' => 'select', 'default' => 'editorial', 'allowed' => array( 'compact', 'editorial', 'poster-led' ) ),
				'image_focus' => array( 'mod' => 'lunara_review_pair_with_image_focus', 'type' => 'select', 'default' => 'center-center', 'allowed' => array( 'center-center', 'center-top', 'center-bottom', 'left-center', 'right-center' ) ),
				'columns' => array( 'mod' => 'lunara_review_pair_with_columns', 'type' => 'int', 'default' => 1, 'min' => 1, 'max' => 3 ),
				'thumb_width' => array( 'mod' => 'lunara_review_pair_with_thumb_width', 'type' => 'int', 'default' => 96, 'min' => 64, 'max' => 140 ),
			),
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_utility_search_spec' ) ) {
	/** @return array<string,array<string,array<string,mixed>>> */
	function lunara_site_studio_utility_search_spec() {
		return array(
			'presentation' => array(
				'density' => array( 'mod' => 'lunara_utility_search_density', 'type' => 'select', 'default' => 'editorial', 'allowed' => array( 'compact', 'editorial', 'showcase' ) ),
				'result_treatment' => array( 'mod' => 'lunara_utility_result_treatment', 'type' => 'select', 'default' => 'cards', 'allowed' => array( 'list', 'cards', 'spotlight' ) ),
				'result_media' => array( 'mod' => 'lunara_utility_result_media', 'type' => 'select', 'default' => 'guarded', 'allowed' => array( 'guarded', 'poster-led', 'text-led' ) ),
				'recovery_prominence' => array( 'mod' => 'lunara_utility_recovery_prominence', 'type' => 'select', 'default' => 'standard', 'allowed' => array( 'quiet', 'standard', 'strong' ) ),
			),
			'focus' => array(
				'lead' => array( 'mod' => 'lunara_utility_search_lead_focus', 'type' => 'select', 'default' => 'balanced', 'allowed' => array( 'balanced', 'ledger', 'reviews', 'journal' ) ),
				'spotlight' => array( 'mod' => 'lunara_utility_search_spotlight_type', 'type' => 'select', 'default' => 'automatic', 'allowed' => array( 'automatic', 'review', 'journal', 'page' ) ),
			),
			'geometry' => array(
				'section_gap' => array( 'mod' => 'lunara_utility_section_gap', 'type' => 'int', 'default' => 42, 'min' => 20, 'max' => 84 ),
				'result_min_height' => array( 'mod' => 'lunara_utility_result_min_height', 'type' => 'int', 'default' => 158, 'min' => 118, 'max' => 260 ),
				'card_grid_min' => array( 'mod' => 'lunara_utility_card_grid_min', 'type' => 'int', 'default' => 280, 'min' => 220, 'max' => 360 ),
			),
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_footer_spec' ) ) {
	/** @return array<string,array<string,array<string,mixed>>> */
	function lunara_site_studio_footer_spec() {
		return array(
			'brand' => array(
				'show_logo' => array( 'mod' => 'lunara_footer_show_logo', 'type' => 'bool', 'default' => true ),
				'tagline' => array( 'mod' => 'lunara_footer_tagline', 'type' => 'text', 'default' => 'Film criticism and a living Oscar ledger.', 'max_length' => 180 ),
			),
			'columns' => array(
				'editorial' => array( 'mod' => 'lunara_footer_col1_heading', 'type' => 'text', 'default' => 'Editorial', 'max_length' => 60 ),
				'oscars' => array( 'mod' => 'lunara_footer_col2_heading', 'type' => 'text', 'default' => 'Oscar Ledger', 'max_length' => 60 ),
				'utility' => array( 'mod' => 'lunara_footer_col3_heading', 'type' => 'text', 'default' => 'Utility', 'max_length' => 60 ),
			),
			'copyright' => array(
				'name' => array( 'mod' => 'lunara_footer_copyright', 'type' => 'text', 'default' => 'Lunara Film', 'max_length' => 100 ),
			),
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_mod_surface_keys' ) ) {
	/** @return array<int,string> */
	function lunara_site_studio_mod_surface_keys( $spec ) {
		$keys = array();
		foreach ( $spec as $fields ) {
			foreach ( $fields as $definition ) { $keys[] = $definition['mod']; }
		}
		return $keys;
	}
}

if ( ! function_exists( 'lunara_site_studio_review_single_keys' ) ) {
	function lunara_site_studio_review_single_keys() { return lunara_site_studio_mod_surface_keys( lunara_site_studio_review_single_spec() ); }
}
if ( ! function_exists( 'lunara_site_studio_utility_search_keys' ) ) {
	function lunara_site_studio_utility_search_keys() { return lunara_site_studio_mod_surface_keys( lunara_site_studio_utility_search_spec() ); }
}
if ( ! function_exists( 'lunara_site_studio_footer_keys' ) ) {
	function lunara_site_studio_footer_keys() { return lunara_site_studio_mod_surface_keys( lunara_site_studio_footer_spec() ); }
}

if ( ! function_exists( 'lunara_site_studio_mod_surface_schema' ) ) {
	/** @return array<string,array<string,bool>> */
	function lunara_site_studio_mod_surface_schema( $spec ) {
		$schema = array();
		foreach ( $spec as $group => $fields ) { $schema[ $group ] = array_fill_keys( array_keys( $fields ), true ); }
		return $schema;
	}
}
if ( ! function_exists( 'lunara_site_studio_review_single_state_schema' ) ) {
	function lunara_site_studio_review_single_state_schema() { return lunara_site_studio_mod_surface_schema( lunara_site_studio_review_single_spec() ); }
}
if ( ! function_exists( 'lunara_site_studio_utility_search_state_schema' ) ) {
	function lunara_site_studio_utility_search_state_schema() { return lunara_site_studio_mod_surface_schema( lunara_site_studio_utility_search_spec() ); }
}
if ( ! function_exists( 'lunara_site_studio_footer_state_schema' ) ) {
	function lunara_site_studio_footer_state_schema() { return lunara_site_studio_mod_surface_schema( lunara_site_studio_footer_spec() ); }
}

if ( ! function_exists( 'lunara_site_studio_mod_surface_read_state' ) ) {
	/** @return array<string,array<string,mixed>> */
	function lunara_site_studio_mod_surface_read_state( $spec ) {
		$state = array();
		foreach ( $spec as $group => $fields ) {
			$state[ $group ] = array();
			foreach ( $fields as $field => $definition ) {
				$value = get_theme_mod( $definition['mod'], $definition['default'] );
				if ( 'select' === $definition['type'] ) {
					$value = is_scalar( $value ) ? (string) $value : '';
					if ( ! in_array( $value, $definition['allowed'], true ) ) { $value = $definition['default']; }
				} elseif ( 'int' === $definition['type'] ) {
					$valid_integer = is_int( $value ) || ( is_string( $value ) && 1 === preg_match( '/^-?\d+$/D', $value ) );
					$value = $valid_integer ? (int) $value : (int) $definition['default'];
					if ( $value < $definition['min'] || $value > $definition['max'] ) { $value = (int) $definition['default']; }
				} elseif ( 'bool' === $definition['type'] ) {
					if ( is_bool( $value ) ) { /* Already canonical. */ }
					elseif ( in_array( $value, array( 0, 1, '0', '1' ), true ) ) { $value = (bool) $value; }
					else { $value = (bool) $definition['default']; }
				} elseif ( 'text' === $definition['type'] ) {
					$value = is_scalar( $value ) ? trim( sanitize_text_field( (string) $value ) ) : (string) $definition['default'];
					if ( strlen( $value ) > $definition['max_length'] ) { $value = (string) $definition['default']; }
				} else {
					$value = $definition['default'];
				}
				$state[ $group ][ $field ] = $value;
			}
		}
		return $state;
	}
}

if ( ! function_exists( 'lunara_site_studio_mod_surface_validate_state' ) ) {
	/** @return array<string,array<string,mixed>>|WP_Error */
	function lunara_site_studio_mod_surface_validate_state( $candidate, $spec, $code ) {
		$fields = array();
		if ( ! is_array( $candidate ) || array_keys( $spec ) !== array_keys( $candidate ) ) {
			return new WP_Error( $code . '_invalid', __( 'The destination state is incomplete.', 'lunara-film' ), array( 'fields' => array( 'state' => __( 'Reload this destination and try again.', 'lunara-film' ) ) ) );
		}
		$normalized = array();
		foreach ( $spec as $group => $definitions ) {
			if ( ! is_array( $candidate[ $group ] ) || array_keys( $definitions ) !== array_keys( $candidate[ $group ] ) ) {
				$fields[ $group ] = __( 'Complete every control in this section.', 'lunara-film' );
				continue;
			}
			$normalized[ $group ] = array();
			foreach ( $definitions as $field => $definition ) {
				$value = $candidate[ $group ][ $field ];
				$field_key = $group . '.' . $field;
				if ( 'select' === $definition['type'] ) {
					if ( ! is_string( $value ) || ! in_array( $value, $definition['allowed'], true ) ) { $fields[ $field_key ] = __( 'Choose one of the available options.', 'lunara-film' ); continue; }
					$normalized[ $group ][ $field ] = $value;
				} elseif ( 'int' === $definition['type'] ) {
					if ( ! is_int( $value ) || $value < $definition['min'] || $value > $definition['max'] ) { $fields[ $field_key ] = __( 'Choose a number inside the available range.', 'lunara-film' ); continue; }
					$normalized[ $group ][ $field ] = $value;
				} elseif ( 'bool' === $definition['type'] ) {
					if ( ! is_bool( $value ) ) { $fields[ $field_key ] = __( 'Choose on or off.', 'lunara-film' ); continue; }
					$normalized[ $group ][ $field ] = $value;
				} elseif ( 'text' === $definition['type'] ) {
					if ( ! is_string( $value ) ) { $fields[ $field_key ] = __( 'Enter plain text.', 'lunara-film' ); continue; }
					$value = trim( sanitize_text_field( $value ) );
					if ( strlen( $value ) > $definition['max_length'] ) { $fields[ $field_key ] = __( 'Shorten this text before saving.', 'lunara-film' ); continue; }
					$normalized[ $group ][ $field ] = $value;
				}
			}
		}
		return $fields ? new WP_Error( $code . '_invalid', __( 'Review the highlighted controls.', 'lunara-film' ), array( 'fields' => $fields ) ) : $normalized;
	}
}

if ( ! function_exists( 'lunara_site_studio_mod_surface_desired_snapshot' ) ) {
	/** @return array<string,array{present:bool,value:mixed}> */
	function lunara_site_studio_mod_surface_desired_snapshot( $state, $spec ) {
		$desired = array();
		foreach ( $spec as $group => $fields ) {
			foreach ( $fields as $field => $definition ) { $desired[ $definition['mod'] ] = array( 'present' => true, 'value' => $state[ $group ][ $field ] ); }
		}
		return $desired;
	}
}

if ( ! function_exists( 'lunara_site_studio_mod_surface_save_state' ) ) {
	/** @return array<string,mixed>|WP_Error */
	function lunara_site_studio_mod_surface_save_state( $candidate, $surface, $spec, $sections, $code ) {
		$validated = lunara_site_studio_mod_surface_validate_state( $candidate, $spec, $code );
		if ( is_wp_error( $validated ) ) { return $validated; }
		$keys = lunara_site_studio_mod_surface_keys( $spec );
		$before = lunara_site_studio_raw_mod_snapshot( $keys );
		if ( ! lunara_site_studio_apply_mod_snapshot( lunara_site_studio_mod_surface_desired_snapshot( $validated, $spec ), $keys ) ) {
			if ( ! lunara_site_studio_apply_mod_snapshot( $before, $keys ) ) { return new WP_Error( $code . '_rollback_failed', __( 'The destination could not be restored safely.', 'lunara-film' ) ); }
			return new WP_Error( $code . '_write_failed', __( 'The destination could not be saved.', 'lunara-film' ) );
		}
		$revision_id = lunara_site_studio_private_revision( $surface, array( 'mods' => $before ), 'save' );
		if ( is_wp_error( $revision_id ) ) {
			if ( ! lunara_site_studio_apply_mod_snapshot( $before, $keys ) ) { return new WP_Error( $code . '_rollback_failed', __( 'The destination could not be restored safely.', 'lunara-film' ) ); }
			return $revision_id;
		}
		return array( 'state' => lunara_site_studio_mod_surface_read_state( $spec ), 'changed_sections' => $sections, 'revision_id' => $revision_id, 'timestamp' => current_time( 'mysql' ) );
	}
}

if ( ! function_exists( 'lunara_site_studio_mod_surface_restore_revision' ) ) {
	/** @return array<string,mixed>|WP_Error */
	function lunara_site_studio_mod_surface_restore_revision( $revision_id, $surface, $spec, $code ) {
		$target = lunara_site_studio_private_revision_target( $surface, $revision_id );
		$keys = lunara_site_studio_mod_surface_keys( $spec );
		if ( is_wp_error( $target ) || ! is_array( $target ) || array( 'mods' ) !== array_keys( $target ) || ! lunara_site_studio_valid_mod_snapshot( $target['mods'], $keys ) ) {
			return is_wp_error( $target ) ? $target : new WP_Error( 'site_studio_revision_invalid', __( 'The selected revision is invalid.', 'lunara-film' ) );
		}
		$current = lunara_site_studio_raw_mod_snapshot( $keys );
		$safety_id = lunara_site_studio_private_revision( $surface, array( 'mods' => $current ), 'restore-safety' );
		if ( is_wp_error( $safety_id ) ) { return $safety_id; }
		if ( ! lunara_site_studio_apply_mod_snapshot( $target['mods'], $keys ) ) {
			if ( ! lunara_site_studio_apply_mod_snapshot( $current, $keys ) ) { return new WP_Error( $code . '_rollback_failed', __( 'The destination could not be restored safely.', 'lunara-film' ) ); }
			return new WP_Error( $code . '_restore_failed', __( 'The selected revision could not be restored.', 'lunara-film' ) );
		}
		return array( 'state' => lunara_site_studio_mod_surface_read_state( $spec ), 'safety_revision_id' => $safety_id, 'timestamp' => current_time( 'mysql' ) );
	}
}

if ( ! function_exists( 'lunara_site_studio_review_single_dependency' ) ) {
	function lunara_site_studio_review_single_dependency() { return function_exists( 'post_type_exists' ) && post_type_exists( 'review' ); }
}
if ( ! function_exists( 'lunara_site_studio_review_single_read_state' ) ) {
	function lunara_site_studio_review_single_read_state() { return lunara_site_studio_mod_surface_read_state( lunara_site_studio_review_single_spec() ); }
}
if ( ! function_exists( 'lunara_site_studio_review_single_validate_state' ) ) {
	function lunara_site_studio_review_single_validate_state( $candidate ) { return lunara_site_studio_mod_surface_validate_state( $candidate, lunara_site_studio_review_single_spec(), 'site_studio_review_single' ); }
}
if ( ! function_exists( 'lunara_site_studio_review_single_save_state' ) ) {
	function lunara_site_studio_review_single_save_state( $candidate ) { return lunara_site_studio_mod_surface_save_state( $candidate, 'review-single', lunara_site_studio_review_single_spec(), array( 'hero', 'criticism', 'debrief', 'pair-it-with' ), 'site_studio_review_single' ); }
}
if ( ! function_exists( 'lunara_site_studio_review_single_restore_revision' ) ) {
	function lunara_site_studio_review_single_restore_revision( $revision_id ) { return lunara_site_studio_mod_surface_restore_revision( $revision_id, 'review-single', lunara_site_studio_review_single_spec(), 'site_studio_review_single' ); }
}
if ( ! function_exists( 'lunara_site_studio_review_single_adapter' ) ) {
	function lunara_site_studio_review_single_adapter() { return new Lunara_Site_Studio_Theme_Adapter( 'review-single', 'theme:review-single', array( 'read' => 'lunara_site_studio_review_single_read_state', 'validate' => 'lunara_site_studio_review_single_validate_state', 'save' => 'lunara_site_studio_review_single_save_state', 'restore' => 'lunara_site_studio_review_single_restore_revision' ) ); }
}

if ( ! function_exists( 'lunara_site_studio_utility_search_read_state' ) ) {
	function lunara_site_studio_utility_search_read_state() { return lunara_site_studio_mod_surface_read_state( lunara_site_studio_utility_search_spec() ); }
}
if ( ! function_exists( 'lunara_site_studio_utility_search_validate_state' ) ) {
	function lunara_site_studio_utility_search_validate_state( $candidate ) { return lunara_site_studio_mod_surface_validate_state( $candidate, lunara_site_studio_utility_search_spec(), 'site_studio_utility_search' ); }
}
if ( ! function_exists( 'lunara_site_studio_utility_search_save_state' ) ) {
	function lunara_site_studio_utility_search_save_state( $candidate ) { return lunara_site_studio_mod_surface_save_state( $candidate, 'utility-search', lunara_site_studio_utility_search_spec(), array( 'search-command', 'direct-matches', 'result-run', 'recovery' ), 'site_studio_utility_search' ); }
}
if ( ! function_exists( 'lunara_site_studio_utility_search_restore_revision' ) ) {
	function lunara_site_studio_utility_search_restore_revision( $revision_id ) { return lunara_site_studio_mod_surface_restore_revision( $revision_id, 'utility-search', lunara_site_studio_utility_search_spec(), 'site_studio_utility_search' ); }
}
if ( ! function_exists( 'lunara_site_studio_utility_search_adapter' ) ) {
	function lunara_site_studio_utility_search_adapter() { return new Lunara_Site_Studio_Theme_Adapter( 'utility-search', 'theme:utility-search', array( 'read' => 'lunara_site_studio_utility_search_read_state', 'validate' => 'lunara_site_studio_utility_search_validate_state', 'save' => 'lunara_site_studio_utility_search_save_state', 'restore' => 'lunara_site_studio_utility_search_restore_revision' ) ); }
}

if ( ! function_exists( 'lunara_site_studio_footer_read_state' ) ) {
	function lunara_site_studio_footer_read_state() { return lunara_site_studio_mod_surface_read_state( lunara_site_studio_footer_spec() ); }
}
if ( ! function_exists( 'lunara_site_studio_footer_validate_state' ) ) {
	function lunara_site_studio_footer_validate_state( $candidate ) { return lunara_site_studio_mod_surface_validate_state( $candidate, lunara_site_studio_footer_spec(), 'site_studio_footer' ); }
}
if ( ! function_exists( 'lunara_site_studio_footer_save_state' ) ) {
	function lunara_site_studio_footer_save_state( $candidate ) { return lunara_site_studio_mod_surface_save_state( $candidate, 'site-footer', lunara_site_studio_footer_spec(), array( 'footer' ), 'site_studio_footer' ); }
}
if ( ! function_exists( 'lunara_site_studio_footer_restore_revision' ) ) {
	function lunara_site_studio_footer_restore_revision( $revision_id ) { return lunara_site_studio_mod_surface_restore_revision( $revision_id, 'site-footer', lunara_site_studio_footer_spec(), 'site_studio_footer' ); }
}
if ( ! function_exists( 'lunara_site_studio_footer_adapter' ) ) {
	function lunara_site_studio_footer_adapter() { return new Lunara_Site_Studio_Theme_Adapter( 'site-footer', 'theme:site-footer', array( 'read' => 'lunara_site_studio_footer_read_state', 'validate' => 'lunara_site_studio_footer_validate_state', 'save' => 'lunara_site_studio_footer_save_state', 'restore' => 'lunara_site_studio_footer_restore_revision' ) ); }
}

if ( ! function_exists( 'lunara_site_studio_project_state_value' ) ) {
	/** Project one value through a private schema; $accepted distinguishes omission from null. */
	function lunara_site_studio_project_state_value( $value, $schema, &$accepted, $depth = 0, &$budget = null ) {
		$accepted = false;
		if ( null === $budget ) {
			$budget = array( 'nodes' => 0 );
		}
		$budget['nodes']++;
		if ( $depth > LUNARA_SITE_STUDIO_PROJECTION_MAX_DEPTH || $budget['nodes'] > LUNARA_SITE_STUDIO_PROJECTION_MAX_NODES ) {
			return new WP_Error( 'site_studio_state_too_complex', __( 'The destination state is too complex to project safely.', 'lunara-film' ) );
		}
		if ( true === $schema ) {
			if ( is_scalar( $value ) || null === $value ) {
				$accepted = true;
				return $value;
			}
			return null;
		}
		if ( ! is_array( $schema ) || ! is_array( $value ) ) {
			return null;
		}
		$projected = array();
		if ( array_key_exists( '*', $schema ) ) {
			foreach ( $value as $key => $item ) {
				if ( ! is_int( $key ) ) {
					return null;
				}
				$item_accepted = false;
				$safe_item = lunara_site_studio_project_state_value( $item, $schema['*'], $item_accepted, $depth + 1, $budget );
				if ( is_wp_error( $safe_item ) ) {
					return $safe_item;
				}
				if ( $item_accepted ) {
					$projected[] = $safe_item;
				}
			}
			$accepted = true;
			return $projected;
		}
		foreach ( $schema as $key => $child_schema ) {
			if ( ! is_string( $key ) || ! array_key_exists( $key, $value ) ) {
				continue;
			}
			$child_accepted = false;
			$safe_child = lunara_site_studio_project_state_value( $value[ $key ], $child_schema, $child_accepted, $depth + 1, $budget );
			if ( is_wp_error( $safe_child ) ) {
				return $safe_child;
			}
			if ( $child_accepted ) {
				$projected[ $key ] = $safe_child;
			}
		}
		$accepted = true;
		return $projected;
	}
}

if ( ! function_exists( 'lunara_site_studio_project_state' ) ) {
	/** Strictly project adapter state through its private per-surface schema. */
	function lunara_site_studio_project_state( $surface_id, $state ) {
		$surface = lunara_site_studio_get_surface( $surface_id );
		if ( ! is_array( $surface ) || ! is_array( $state ) ) {
			return new WP_Error( 'site_studio_state_unavailable', __( 'The destination state is unavailable.', 'lunara-film' ) );
		}
		$callback = isset( $surface['state_schema_callback'] ) ? $surface['state_schema_callback'] : '';
		if ( ! is_callable( $callback ) ) {
			return new WP_Error( 'site_studio_state_unavailable', __( 'The destination state is unavailable.', 'lunara-film' ) );
		}
		if ( ! lunara_site_studio_boundary_guard( 'schema', $surface_id ) ) {
			return new WP_Error( 'site_studio_state_unavailable', __( 'The destination state is unavailable.', 'lunara-film' ) );
		}
		try {
			$schema = call_user_func( $callback, $surface );
		} catch ( Throwable $error ) {
			return new WP_Error( 'site_studio_state_unavailable', __( 'The destination state is unavailable.', 'lunara-film' ) );
		} finally {
			lunara_site_studio_boundary_guard( 'schema', $surface_id, false );
		}
		if ( ! is_array( $schema ) ) {
			return new WP_Error( 'site_studio_state_unavailable', __( 'The destination state is unavailable.', 'lunara-film' ) );
		}
		$accepted = false;
		if ( ! lunara_site_studio_boundary_guard( 'projector', $surface_id ) ) {
			return new WP_Error( 'site_studio_state_unavailable', __( 'The destination state is unavailable.', 'lunara-film' ) );
		}
		try {
			$projected = lunara_site_studio_project_state_value( $state, $schema, $accepted );
		} finally {
			lunara_site_studio_boundary_guard( 'projector', $surface_id, false );
		}
		if ( is_wp_error( $projected ) ) {
			return $projected;
		}
		return $accepted && is_array( $projected ) ? $projected : new WP_Error( 'site_studio_state_unavailable', __( 'The destination state is unavailable.', 'lunara-film' ) );
	}
}

if ( ! function_exists( 'lunara_site_studio_reviews_archive_managed_paths' ) ) {
	/** @return array<int,string> */
	function lunara_site_studio_reviews_archive_managed_paths() {
		return array(
			'kicker', 'title', 'deck', 'supporting_copy', 'item_count', 'section_order',
			'section_visibility.hero', 'section_visibility.grid', 'section_visibility.pagination', 'section_visibility.pairing-desk',
			'presentation.density', 'presentation.lead_prominence', 'presentation.rail_density', 'presentation.section_gap',
			'presentation.lead_min_height', 'presentation.card_min_height', 'presentation.compact_media_width',
		);
	}
}
if ( ! function_exists( 'lunara_site_studio_journal_archive_managed_paths' ) ) {
	/** @return array<int,string> */
	function lunara_site_studio_journal_archive_managed_paths() {
		return array(
			'kicker', 'title', 'deck', 'supporting_copy', 'item_count', 'section_order',
			'section_visibility.hero', 'section_visibility.deskbar', 'section_visibility.filters', 'section_visibility.toolbar',
			'section_visibility.grid', 'section_visibility.retention', 'section_visibility.pagination',
			'presentation.density', 'presentation.lead_prominence', 'presentation.desk_rhythm', 'presentation.section_gap',
			'presentation.hero_min_height', 'presentation.card_min_height', 'presentation.media_min_height',
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_reviews_archive_validation_fields' ) ) {
	/** Map mature provider codes to the exact safe controls Site Studio owns. */
	function lunara_site_studio_reviews_archive_validation_fields() {
		return array(
			'reviews_archive_config_invalid' => array( 'deck', 'supporting_copy', 'section_visibility' ),
			'reviews_archive_identity_required' => array( 'kicker', 'title' ),
			'reviews_archive_item_count_invalid' => array( 'item_count' ),
			'reviews_archive_section_order_invalid' => array( 'section_order' ),
			'reviews_archive_primary_sections_hidden' => array( 'section_visibility' ),
			'reviews_archive_presentation_invalid' => array( 'presentation.density', 'presentation.lead_prominence', 'presentation.rail_density' ),
			'reviews_archive_geometry_invalid' => array( 'presentation.section_gap', 'presentation.lead_min_height', 'presentation.card_min_height', 'presentation.compact_media_width' ),
		);
	}
}
if ( ! function_exists( 'lunara_site_studio_journal_archive_validation_fields' ) ) {
	/** Map mature provider codes to the exact safe controls Site Studio owns. */
	function lunara_site_studio_journal_archive_validation_fields() {
		return array(
			'journal_archive_config_invalid' => array( 'deck', 'supporting_copy', 'section_visibility' ),
			'journal_archive_identity_required' => array( 'kicker', 'title' ),
			'journal_archive_item_count_invalid' => array( 'item_count' ),
			'journal_archive_section_order_invalid' => array( 'section_order' ),
			'journal_archive_primary_sections_hidden' => array( 'section_visibility' ),
			'journal_archive_presentation_invalid' => array( 'presentation.density', 'presentation.lead_prominence', 'presentation.desk_rhythm' ),
			'journal_archive_geometry_invalid' => array( 'presentation.section_gap', 'presentation.hero_min_height', 'presentation.card_min_height', 'presentation.media_min_height' ),
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_reviews_archive_adapter' ) ) {
	function lunara_site_studio_reviews_archive_adapter() {
		return new Lunara_Site_Studio_Provider_Adapter(
			'reviews-archive',
			array(
				'read' => 'lunara_reviews_archive_studio_get_public_config', 'validate' => 'lunara_reviews_archive_studio_validate_config',
				'save' => 'lunara_reviews_archive_studio_promote_config_transaction', 'preview' => 'lunara_reviews_archive_studio_store_preview',
				'revisions' => 'lunara_reviews_archive_studio_get_revisions', 'restore' => 'lunara_reviews_archive_studio_restore_revision_transaction',
				'managed_paths' => lunara_site_studio_reviews_archive_managed_paths(),
				'validation_fields' => lunara_site_studio_reviews_archive_validation_fields(),
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
				'save' => 'lunara_journal_archive_studio_promote_config_transaction', 'preview' => 'lunara_journal_archive_studio_store_preview',
				'revisions' => 'lunara_journal_archive_studio_get_revisions', 'restore' => 'lunara_journal_archive_studio_restore_revision_transaction',
				'managed_paths' => lunara_site_studio_journal_archive_managed_paths(),
				'validation_fields' => lunara_site_studio_journal_archive_validation_fields(),
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
				'save' => 'lunara_oscars_portal_studio_promote_config_transaction', 'preview' => 'lunara_oscars_portal_studio_store_preview',
				'revisions' => 'lunara_oscars_portal_studio_get_revisions', 'restore' => 'lunara_oscars_portal_studio_restore_revision_transaction',
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
		if ( ! lunara_site_studio_boundary_guard( 'adapter_factory', $surface_id ) ) {
			return new WP_Error( 'site_studio_adapter_unavailable', __( 'The destination adapter is unavailable.', 'lunara-film' ) );
		}
		try {
			$adapter = call_user_func( $factory, $surface );
		} catch ( Throwable $error ) {
			return new WP_Error( 'site_studio_adapter_unavailable', __( 'The destination adapter is unavailable.', 'lunara-film' ) );
		} finally {
			lunara_site_studio_boundary_guard( 'adapter_factory', $surface_id, false );
		}
		return $adapter instanceof Lunara_Site_Studio_Surface_Adapter ? $adapter : new WP_Error( 'site_studio_adapter_invalid', __( 'The destination adapter is invalid.', 'lunara-film' ) );
	}
}

if ( ! function_exists( 'lunara_site_studio_call_adapter' ) ) {
	/** Safely invoke a contributed adapter method without exposing exceptions. */
	function lunara_site_studio_call_adapter( $adapter, $method, $arguments = array() ) {
		if ( ! ( $adapter instanceof Lunara_Site_Studio_Surface_Adapter ) || ! is_callable( array( $adapter, $method ) ) ) {
			return new WP_Error( 'site_studio_adapter_unavailable', __( 'The destination adapter is unavailable.', 'lunara-film' ) );
		}
		$guard_key = spl_object_hash( $adapter ) . '-' . sanitize_key( $method );
		if ( ! lunara_site_studio_boundary_guard( 'adapter_method', $guard_key ) ) {
			return new WP_Error( 'site_studio_adapter_unavailable', __( 'The destination adapter is unavailable.', 'lunara-film' ) );
		}
		try {
			return call_user_func_array( array( $adapter, $method ), is_array( $arguments ) ? $arguments : array() );
		} catch ( Throwable $error ) {
			return new WP_Error( 'site_studio_adapter_unavailable', __( 'The destination adapter is unavailable.', 'lunara-film' ) );
		} finally {
			lunara_site_studio_boundary_guard( 'adapter_method', $guard_key, false );
		}
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
		$registered = lunara_site_studio_get_surface( $surface );
		if ( ! is_array( $registered ) || ! current_user_can( $registered['capability'] ) ) {
			return new WP_Error( 'site_studio_preview_forbidden', __( 'You do not have permission to create this private preview.', 'lunara-film' ) );
		}
		if ( empty( $registered['supports_preview'] ) || ! hash_equals( (string) $registered['owner'], $owner ) || ! hash_equals( (string) $registered['preview_route'], $route ) ) {
			return new WP_Error( 'site_studio_preview_invalid', __( 'The private preview could not be created.', 'lunara-film' ) );
		}
		$token   = wp_generate_uuid4();
		$user_id = absint( get_current_user_id() );
		$key     = 'lunara_site_studio_preview_' . hash( 'sha256', $token );
		$record = array(
			'user_id'    => $user_id,
			'surface'    => $surface,
			'owner'      => $owner,
			'route'      => $route,
			'token_hash' => wp_hash( $token . '|' . $user_id . '|' . $surface . '|' . $owner . '|' . $route ),
			'expires'    => lunara_site_studio_timestamp() + LUNARA_SITE_STUDIO_PREVIEW_TTL,
			'state'      => $state,
		);
		try {
			if ( ! set_transient( $key, $record, LUNARA_SITE_STUDIO_PREVIEW_TTL ) ) { return new WP_Error( 'site_studio_preview_write_failed', __( 'The private preview could not be stored.', 'lunara-film' ) ); }
		} catch ( Throwable $error ) {
			try { delete_transient( $key ); } catch ( Throwable $cleanup_error ) { /* A failed cleanup cannot produce a preview token. */ }
			return new WP_Error( 'site_studio_preview_write_failed', __( 'The private preview could not be stored.', 'lunara-film' ) );
		}
		try { $stored = get_transient( $key ); } catch ( Throwable $error ) { $stored = false; }
		if ( $record !== $stored ) {
			try { delete_transient( $key ); } catch ( Throwable $cleanup_error ) { /* A failed cleanup cannot produce a preview token. */ }
			return new WP_Error( 'site_studio_preview_readback_failed', __( 'The private preview could not be verified.', 'lunara-film' ) );
		}
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
		$registered = lunara_site_studio_get_surface( $surface );
		if ( ! is_array( $registered ) || ! current_user_can( $registered['capability'] ) || empty( $registered['supports_preview'] ) ) {
			return false;
		}
		if ( ! hash_equals( (string) $registered['owner'], $owner ) || ! hash_equals( (string) $registered['preview_route'], $route ) ) {
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
		if ( lunara_site_studio_boundary_guard( 'preview_notification' ) ) {
			try {
				do_action( 'lunara_site_studio_private_no_store_sent' );
			} catch ( Throwable $error ) {
				// No extension callback may turn a private no-store response into a fatal.
			} finally {
				lunara_site_studio_boundary_guard( 'preview_notification', '', false );
			}
		}
	}
}

if ( ! function_exists( 'lunara_site_studio_prepare_private_preview_response' ) ) {
	/** @return mixed */
	function lunara_site_studio_prepare_private_preview_response( $lookup_callback ) {
		lunara_site_studio_send_private_no_store();
		if ( ! is_callable( $lookup_callback ) ) {
			return false;
		}
		$lookup_key = is_string( $lookup_callback ) ? $lookup_callback : ( is_object( $lookup_callback ) ? spl_object_hash( $lookup_callback ) : 'lookup' );
		if ( ! lunara_site_studio_boundary_guard( 'preview_lookup', $lookup_key ) ) {
			return false;
		}
		try {
			return call_user_func( $lookup_callback );
		} catch ( Throwable $error ) {
			return false;
		} finally {
			lunara_site_studio_boundary_guard( 'preview_lookup', $lookup_key, false );
		}
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
		$before    = lunara_site_studio_raw_option_snapshot( $option );
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
		$bounded = array_slice( $revisions, 0, LUNARA_SITE_STUDIO_REVISION_LIMIT );
		$error = null;
		try {
			if ( ! update_option( $option, $bounded, false ) ) { $error = new WP_Error( 'site_studio_revision_write_failed', __( 'The safety revision could not be stored.', 'lunara-film' ) ); }
			elseif ( $bounded !== get_option( $option, array() ) ) { $error = new WP_Error( 'site_studio_revision_readback_failed', __( 'The safety revision could not be verified.', 'lunara-film' ) ); }
		} catch ( Throwable $throwable ) { $error = new WP_Error( 'site_studio_revision_write_failed', __( 'The safety revision could not be stored.', 'lunara-film' ) ); }
		if ( $error ) {
			if ( ! lunara_site_studio_apply_option_snapshot( $option, $before ) ) { return new WP_Error( 'site_studio_revision_rollback_failed', __( 'Revision history could not be restored safely.', 'lunara-film' ) ); }
			return $error;
		}
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
		$surface = sanitize_key( $surface );
		if ( ! lunara_site_studio_boundary_guard( 'restore', $surface ) ) {
			return new WP_Error( 'site_studio_restore_unavailable', __( 'The restore service is unavailable.', 'lunara-film' ) );
		}
		try {
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
			try {
				$validated = call_user_func( $validate_callback, $target );
			} catch ( Throwable $error ) {
				return new WP_Error( 'site_studio_restore_unavailable', __( 'The restore service is unavailable.', 'lunara-film' ) );
			}
			if ( is_wp_error( $validated ) ) {
				return $validated;
			}
			try {
				$current = call_user_func( $read_callback );
			} catch ( Throwable $error ) {
				return new WP_Error( 'site_studio_restore_unavailable', __( 'The restore service is unavailable.', 'lunara-film' ) );
			}
			if ( is_wp_error( $current ) || ! is_array( $current ) ) {
				return is_wp_error( $current ) ? $current : new WP_Error( 'site_studio_restore_snapshot_failed' );
			}
			$safety_id = lunara_site_studio_push_revision( $surface, $current, 'restore-safety' );
			if ( is_wp_error( $safety_id ) ) {
				return $safety_id;
			}
			try {
				$saved = call_user_func( $save_callback, $validated );
			} catch ( Throwable $error ) {
				return new WP_Error( 'site_studio_restore_unavailable', __( 'The restore service is unavailable.', 'lunara-film' ) );
			}
			if ( is_wp_error( $saved ) ) {
				return $saved;
			}
			return array( 'state' => $saved, 'safety_revision_id' => $safety_id, 'timestamp' => current_time( 'mysql' ) );
		} finally {
			lunara_site_studio_boundary_guard( 'restore', $surface, false );
		}
	}
}
