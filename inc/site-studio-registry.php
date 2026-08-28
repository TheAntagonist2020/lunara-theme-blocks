<?php
/**
 * Lunara Site Studio surface registry.
 *
 * The registry is intentionally available on public, REST, and admin requests.
 * It contains routing metadata only; canonical settings remain with the theme
 * mods, post content, and plugin providers that already own them.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_site_studio_boundary_guard' ) ) {
	/**
	 * Enter or leave one internal extension boundary without exposing guard state.
	 *
	 * @param string $boundary Boundary family.
	 * @param string $surface  Surface/object key.
	 * @param bool   $enter    True to enter, false to leave.
	 * @return bool False only when the same boundary/key is already active.
	 */
	function lunara_site_studio_boundary_guard( $boundary, $surface = '', $enter = true ) {
		static $active = array();
		$boundary = sanitize_key( is_scalar( $boundary ) ? (string) $boundary : '' );
		$surface  = sanitize_key( is_scalar( $surface ) ? (string) $surface : '' );
		$key      = $boundary . '|' . $surface;
		if ( ! $enter ) {
			unset( $active[ $key ] );
			return true;
		}
		if ( isset( $active[ $key ] ) ) {
			return false;
		}
		$active[ $key ] = true;
		return true;
	}
}

if ( ! function_exists( 'lunara_site_studio_dependency_available' ) ) {
	/** @return bool */
	function lunara_site_studio_dependency_available() {
		return true;
	}
}

if ( ! function_exists( 'lunara_site_studio_status_ready' ) ) {
	/** @return array<string,string> */
	function lunara_site_studio_status_ready() {
		return array(
			'state'   => 'ready',
			'label'   => __( 'Available', 'lunara-film' ),
			'message' => __( 'The canonical controls are available.', 'lunara-film' ),
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_registry_text' ) ) {
	/** @return string */
	function lunara_site_studio_registry_text( $value ) {
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}
}

if ( ! function_exists( 'lunara_site_studio_registry_key' ) ) {
	/** @return string */
	function lunara_site_studio_registry_key( $value ) {
		return is_scalar( $value ) ? sanitize_key( (string) $value ) : '';
	}
}

if ( ! function_exists( 'lunara_site_studio_default_surfaces' ) ) {
	/**
	 * Canonical theme destinations before plugin contributions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function lunara_site_studio_default_surfaces() {
		return array(
			'lunara-method' => array(
				'id'                  => 'lunara-method',
				'group'               => __( 'Homepage', 'lunara-film' ),
				'label'               => __( 'Lunara Method', 'lunara-film' ),
				'description'         => __( 'Edit the words, featured Review, and backdrop for the signature three-film showcase.', 'lunara-film' ),
				'aliases'             => array( 'pairing desk', 'pair it with', 'method', 'three films' ),
				'owner'               => 'theme:lunara-method',
				'kind'                => 'presentation',
				'capability'          => 'edit_theme_options',
				'supports_preview'    => false,
				'preview_route'       => '/',
				'preview_query_arg'   => '',
				'adapter_factory'     => '',
				'admin_url'           => 'admin.php?page=lunara-site-studio&surface=lunara-method',
				'dependency_callback' => 'lunara_site_studio_dependency_available',
				'status_callback'     => 'lunara_site_studio_status_ready',
				'danger_level'        => 'none',
				'sections'            => array( 'language', 'featured-review', 'backdrop' ),
				'classic_url'         => 'admin.php?page=lunara-control-desk&tab=homepage',
				'renderer'            => 'lunara_control_desk_render_pairing_desk_form',
			),
			'homepage-structure' => array(
				'id'                  => 'homepage-structure',
				'group'               => __( 'Homepage', 'lunara-film' ),
				'label'               => __( 'Homepage Structure', 'lunara-film' ),
				'description'         => __( 'See the six live lanes, choose their presence, and set desktop and mobile order.', 'lunara-film' ),
				'aliases'             => array( 'home', 'front page', 'sections', 'mobile order', 'lanes' ),
				'owner'               => 'theme:homepage-structure',
				'kind'                => 'presentation',
				'capability'          => 'edit_theme_options',
				'supports_preview'    => false,
				'preview_route'       => '/',
				'preview_query_arg'   => '',
				'adapter_factory'     => '',
				'admin_url'           => 'admin.php?page=lunara-site-studio&surface=homepage-structure',
				'dependency_callback' => 'lunara_site_studio_dependency_available',
				'status_callback'     => 'lunara_site_studio_status_ready',
				'danger_level'        => 'none',
				'sections'            => array( 'hero', 'latest-reviews', 'pairing-desk', 'journal', 'oscar-picks', 'oscar-facts' ),
				'classic_url'         => 'customize.php?autofocus[panel]=lunara_homepage_panel',
				'renderer'            => 'lunara_control_desk_render_homepage_studio',
			),
			'reviews-archive' => array(
				'id'                  => 'reviews-archive',
				'group'               => __( 'Archives', 'lunara-film' ),
				'label'               => __( 'Reviews Archive', 'lunara-film' ),
				'description'         => __( 'Control the Reviews desk density, lead chamber, cards, and companion rail.', 'lunara-film' ),
				'aliases'             => array( 'reviews', 'review desk', 'lead review', 'review density' ),
				'owner'               => 'theme:reviews-archive',
				'kind'                => 'presentation',
				'capability'          => 'edit_theme_options',
				'supports_preview'    => true,
				'preview_route'       => '/reviews/',
				'preview_query_arg'   => 'lunara_reviews_preview',
				'adapter_factory'     => 'lunara_site_studio_reviews_archive_adapter',
				'state_schema_callback' => 'lunara_site_studio_reviews_archive_state_schema',
				'admin_url'           => 'admin.php?page=lunara-site-studio&surface=reviews-archive',
				'dependency_callback' => 'lunara_site_studio_reviews_archive_dependency',
				'status_callback'     => 'lunara_site_studio_status_ready',
				'danger_level'        => 'none',
				'sections'            => array( 'hero', 'grid', 'pagination', 'pairing-desk' ),
				'classic_url'         => 'customize.php?autofocus[panel]=lunara_reviews_panel',
				'renderer'            => 'lunara_control_desk_render_reviews_archive_studio',
			),
			'journal-archive' => array(
				'id'                  => 'journal-archive',
				'group'               => __( 'Archives', 'lunara-film' ),
				'label'               => __( 'Journal Archive', 'lunara-film' ),
				'description'         => __( 'Control the live-desk rhythm, lead file, filters, cards, and retention lane.', 'lunara-film' ),
				'aliases'             => array( 'journal', 'dispatch', 'live desk', 'journal density', 'retention' ),
				'owner'               => 'theme:journal-archive',
				'kind'                => 'presentation',
				'capability'          => 'edit_theme_options',
				'supports_preview'    => true,
				'preview_route'       => '/journal/',
				'preview_query_arg'   => 'lunara_journal_preview',
				'adapter_factory'     => 'lunara_site_studio_journal_archive_adapter',
				'state_schema_callback' => 'lunara_site_studio_journal_archive_state_schema',
				'admin_url'           => 'admin.php?page=lunara-site-studio&surface=journal-archive',
				'dependency_callback' => 'lunara_site_studio_journal_archive_dependency',
				'status_callback'     => 'lunara_site_studio_status_ready',
				'danger_level'        => 'none',
				'sections'            => array( 'hero', 'filters', 'stream', 'gallery', 'retention' ),
				'classic_url'         => 'customize.php?autofocus[panel]=lunara_editorial_panel',
				'renderer'            => 'lunara_control_desk_render_journal_archive_studio',
			),
			'oscars-portal' => array(
				'id'                  => 'oscars-portal',
				'group'               => __( 'Oscars', 'lunara-film' ),
				'label'               => __( 'Oscars Portal', 'lunara-film' ),
				'description'         => __( 'Compose the /oscars/ portal copy, eleven-slot order and visibility, and bounded geometry.', 'lunara-film' ),
				'aliases'             => array( 'oscars', 'academy awards', 'winners', 'portal' ),
				'owner'               => 'theme:oscars-portal',
				'kind'                => 'presentation',
				'capability'          => 'edit_theme_options',
				'supports_preview'    => true,
				'preview_route'       => '/oscars/',
				'preview_query_arg'   => 'lunara_oscars_preview',
				'adapter_factory'     => 'lunara_site_studio_oscars_portal_adapter',
				'state_schema_callback' => 'lunara_site_studio_oscars_portal_state_schema',
				'admin_url'           => 'admin.php?page=lunara-site-studio&surface=oscars-portal',
				'dependency_callback' => 'lunara_site_studio_oscars_portal_dependency',
				'status_callback'     => 'lunara_site_studio_status_ready',
				'danger_level'        => 'none',
				'sections'            => array( 'board', 'hero', 'navigator', 'doors', 'spotlights', 'titles', 'research', 'linked-reviews', 'winners', 'deep-cuts', 'rotating-winners' ),
				'classic_url'         => 'customize.php?autofocus[panel]=lunara_oscars_panel',
				'renderer'            => 'lunara_control_desk_render_oscars_portal_studio',
			),
			'oscars-ledger' => array(
				'id'                  => 'oscars-ledger',
				'group'               => __( 'Oscars', 'lunara-film' ),
				'label'               => __( 'Oscars Ledger Routes', 'lunara-film' ),
				'description'         => __( 'Tune ceremony, category, title, and person dossiers while the Academy plugin owns the data.', 'lunara-film' ),
				'aliases'             => array( 'dossiers', 'ceremony', 'category', 'title', 'person', 'ledger' ),
				'owner'               => 'theme:oscars-ledger-presentation',
				'kind'                => 'presentation',
				'capability'          => 'edit_theme_options',
				'supports_preview'    => false,
				'preview_route'       => '',
				'preview_query_arg'   => '',
				'adapter_factory'     => '',
				'admin_url'           => 'admin.php?page=lunara-site-studio&surface=oscars-ledger',
				'dependency_callback' => 'lunara_site_studio_oscars_ledger_dependency',
				'status_callback'     => 'lunara_site_studio_status_ready',
				'danger_level'        => 'none',
				'sections'            => array( 'ceremony', 'category', 'title', 'person' ),
				'classic_url'         => 'admin.php?page=lunara-control-desk&tab=oscars',
				'renderer'            => 'lunara_control_desk_render_oscars_dossier_studio',
			),
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_sanitize_admin_path' ) ) {
	/** @return string */
	function lunara_site_studio_sanitize_admin_path( $value ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( '' === $value || false !== strpos( $value, '://' ) || 0 === strpos( $value, '//' ) || preg_match( '/[\r\n]/', $value ) ) {
			return '';
		}
		$path = (string) wp_parse_url( $value, PHP_URL_PATH );
		if ( ! preg_match( '#^(?:admin\.php|customize\.php|edit\.php|edit-tags\.php|post-new\.php|tools\.php|options-general\.php)$#', ltrim( $path, '/' ) ) ) {
			return '';
		}
		return $value;
	}
}

if ( ! function_exists( 'lunara_site_studio_normalize_preview_route' ) ) {
	/** @return string */
	function lunara_site_studio_normalize_preview_route( $value ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( '' === $value ) {
			return '';
		}
		if ( '/' !== substr( $value, 0, 1 ) || 0 === strpos( $value, '//' ) || false !== strpos( $value, '?' ) || false !== strpos( $value, '#' ) || false !== strpos( $value, "\\" ) || preg_match( '/[\r\n]/', $value ) ) {
			return '';
		}
		$path = (string) wp_parse_url( $value, PHP_URL_PATH );
		return $path === $value ? $path : '';
	}
}

if ( ! function_exists( 'lunara_site_studio_normalize_surface' ) ) {
	/**
	 * Normalize without executing callbacks or factories.
	 *
	 * @param string               $id Surface ID.
	 * @param array<string,mixed>  $raw Raw metadata.
	 * @return array<string,mixed>
	 */
	function lunara_site_studio_normalize_surface( $id, $raw ) {
		$id        = lunara_site_studio_registry_key( $id );
		$raw       = is_array( $raw ) ? $raw : array();
		$kinds     = array( 'presentation', 'content', 'workflow', 'integration', 'operations' );
		$dangers   = array( 'none', 'caution', 'destructive' );
		$kind      = isset( $raw['kind'] ) && in_array( $raw['kind'], $kinds, true ) ? $raw['kind'] : '';
		$danger    = isset( $raw['danger_level'] ) && in_array( $raw['danger_level'], $dangers, true ) ? $raw['danger_level'] : '';
		$capability = isset( $raw['capability'] ) ? lunara_site_studio_registry_key( $raw['capability'] ) : '';
		$owner     = isset( $raw['owner'] ) && is_scalar( $raw['owner'] ) ? sanitize_text_field( $raw['owner'] ) : '';
		$aliases   = isset( $raw['aliases'] ) && is_array( $raw['aliases'] ) ? $raw['aliases'] : array();
		$sections  = isset( $raw['sections'] ) && is_array( $raw['sections'] ) ? $raw['sections'] : array();
		$aliases   = array_values( array_unique( array_filter( array_map( 'lunara_site_studio_registry_text', $aliases ) ) ) );
		$sections  = array_values( array_unique( array_filter( array_map( 'lunara_site_studio_registry_key', $sections ) ) ) );
		$dependency = isset( $raw['dependency_callback'] ) ? $raw['dependency_callback'] : '';
		$status     = isset( $raw['status_callback'] ) ? $raw['status_callback'] : '';
		$adapter    = isset( $raw['adapter_factory'] ) ? $raw['adapter_factory'] : '';
		$state_schema = isset( $raw['state_schema_callback'] ) ? $raw['state_schema_callback'] : '';
		$renderer   = isset( $raw['renderer'] ) ? $raw['renderer'] : '';
		$admin_path = lunara_site_studio_sanitize_admin_path( isset( $raw['admin_url'] ) ? $raw['admin_url'] : '' );
		$classic    = lunara_site_studio_sanitize_admin_path( isset( $raw['classic_url'] ) ? $raw['classic_url'] : '' );
		$preview    = lunara_site_studio_normalize_preview_route( isset( $raw['preview_route'] ) ? $raw['preview_route'] : '' );
		$query_arg  = isset( $raw['preview_query_arg'] ) ? lunara_site_studio_registry_key( $raw['preview_query_arg'] ) : '';

		$normalized = array(
			'id'                  => $id,
			'group'               => isset( $raw['group'] ) ? lunara_site_studio_registry_text( $raw['group'] ) : '',
			'label'               => isset( $raw['label'] ) ? lunara_site_studio_registry_text( $raw['label'] ) : '',
			'description'         => isset( $raw['description'] ) ? lunara_site_studio_registry_text( $raw['description'] ) : '',
			'aliases'             => $aliases,
			'owner'               => $owner,
			'kind'                => $kind,
			'capability'          => $capability,
			'supports_preview'    => ! empty( $raw['supports_preview'] ),
			'preview_route'       => $preview,
			'preview_query_arg'   => $query_arg,
			'adapter_factory'     => $adapter,
			'state_schema_callback' => $state_schema,
			'admin_url'           => $admin_path,
			'dependency_callback' => $dependency,
			'status_callback'     => $status,
			'danger_level'        => $danger,
			'sections'            => $sections,
			'classic_url'         => $classic,
			'renderer'            => $renderer,
			'available'           => true,
			'unavailable_reason'  => '',
		);

		$required_strings = array( 'id', 'group', 'label', 'description', 'owner', 'kind', 'capability', 'dependency_callback', 'status_callback', 'danger_level', 'classic_url' );
		foreach ( $required_strings as $field ) {
			if ( '' === $normalized[ $field ] ) {
				$normalized['available']          = false;
				$normalized['unavailable_reason'] = 'invalid_schema';
			}
		}
		foreach ( array( 'dependency_callback', 'status_callback' ) as $callback_field ) {
			if ( '' !== $normalized[ $callback_field ] && ! is_callable( $normalized[ $callback_field ] ) ) {
				$normalized['available']          = false;
				$normalized['unavailable_reason'] = 'invalid_callback';
			}
		}
		foreach ( array( 'adapter_factory', 'state_schema_callback', 'renderer' ) as $optional_callback ) {
			if ( '' !== $normalized[ $optional_callback ] && ! is_callable( $normalized[ $optional_callback ] ) ) {
				$normalized['available']          = false;
				$normalized['unavailable_reason'] = 'invalid_callback';
			}
		}
		if ( $normalized['supports_preview'] && ( '' === $preview || '' === $query_arg || '' === $adapter ) ) {
			$normalized['available']          = false;
			$normalized['unavailable_reason'] = 'invalid_preview';
		}
		if ( '' === $adapter && '' === $admin_path ) {
			$normalized['available']          = false;
			$normalized['unavailable_reason'] = 'missing_destination';
		}
		if ( '' !== $adapter && '' === $state_schema ) {
			$normalized['available']          = false;
			$normalized['unavailable_reason'] = 'missing_state_schema';
		}

		return $normalized;
	}
}

if ( ! function_exists( 'lunara_site_studio_surfaces' ) ) {
	/**
	 * Return normalized canonical and contributed destinations.
	 *
	 * The default six are seeded before the filter result is considered, so a
	 * plugin cannot silently remove or take ownership of a theme bookmark.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function lunara_site_studio_surfaces() {
		$defaults = lunara_site_studio_default_surfaces();
		$filtered = $defaults;
		$history  = array();
		if ( lunara_site_studio_boundary_guard( 'registry_filter' ) ) {
			$trackers = array();
			try {
				global $wp_filter;
				if ( isset( $wp_filter['lunara_site_studio_surfaces'] ) && is_object( $wp_filter['lunara_site_studio_surfaces'] ) && isset( $wp_filter['lunara_site_studio_surfaces']->callbacks ) && is_array( $wp_filter['lunara_site_studio_surfaces']->callbacks ) ) {
					foreach ( array_keys( $wp_filter['lunara_site_studio_surfaces']->callbacks ) as $priority ) {
						$tracker = static function ( $items ) use ( &$history ) {
							$history[] = is_array( $items ) ? $items : array();
							return $items;
						};
						$trackers[] = array( 'callback' => $tracker, 'priority' => (int) $priority );
						add_filter( 'lunara_site_studio_surfaces', $tracker, (int) $priority, 1 );
					}
				}
				$filtered = apply_filters( 'lunara_site_studio_surfaces', $defaults );
				if ( empty( $history ) && is_array( $filtered ) ) {
					$history[] = $filtered;
				}
			} catch ( Throwable $error ) {
				$filtered = $defaults;
				$history  = array();
			} finally {
				foreach ( $trackers as $tracker ) {
					remove_filter( 'lunara_site_studio_surfaces', $tracker['callback'], $tracker['priority'] );
				}
				lunara_site_studio_boundary_guard( 'registry_filter', '', false );
			}
		}
		$filtered = is_array( $filtered ) ? $filtered : array();
		$result   = array();
		$owners   = array();
		$first_surfaces = array();

		foreach ( $defaults as $id => $raw ) {
			$result[ $id ] = lunara_site_studio_normalize_surface( $id, $raw );
			$first_surfaces[ $id ] = $result[ $id ];
			$owners[ $id ] = array( (string) $result[ $id ]['owner'] => true );
		}
		foreach ( $history as $snapshot ) {
			foreach ( is_array( $snapshot ) ? $snapshot : array() as $key => $raw ) {
				if ( ! is_array( $raw ) ) {
					continue;
				}
				$id = is_int( $key ) ? ( isset( $raw['id'] ) ? lunara_site_studio_registry_key( $raw['id'] ) : '' ) : lunara_site_studio_registry_key( isset( $raw['id'] ) ? $raw['id'] : $key );
				if ( '' === $id ) {
					continue;
				}
				$candidate = lunara_site_studio_normalize_surface( $id, $raw );
				if ( ! isset( $first_surfaces[ $id ] ) ) {
					$first_surfaces[ $id ] = $candidate;
				}
				if ( ! isset( $owners[ $id ] ) ) {
					$owners[ $id ] = array();
				}
				$owners[ $id ][ (string) $candidate['owner'] ] = true;
			}
		}

		foreach ( $filtered as $key => $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}
			$id = is_int( $key ) ? ( isset( $raw['id'] ) ? lunara_site_studio_registry_key( $raw['id'] ) : '' ) : lunara_site_studio_registry_key( isset( $raw['id'] ) ? $raw['id'] : $key );
			if ( '' === $id ) {
				continue;
			}
			$candidate = lunara_site_studio_normalize_surface( $id, $raw );
			$result[ $id ] = $candidate;
			if ( ! isset( $owners[ $id ] ) ) {
				$owners[ $id ] = array();
			}
			$owners[ $id ][ (string) $candidate['owner'] ] = true;
		}
		foreach ( $owners as $id => $claims ) {
			if ( isset( $result[ $id ] ) && count( $claims ) > 1 ) {
				$result[ $id ] = $first_surfaces[ $id ];
				$result[ $id ]['available']          = false;
				$result[ $id ]['unavailable_reason'] = 'ownership_conflict';
			}
		}

		return $result;
	}
}

if ( ! function_exists( 'lunara_site_studio_get_surface' ) ) {
	/** @return array<string,mixed>|null */
	function lunara_site_studio_get_surface( $surface ) {
		$surface  = sanitize_key( (string) $surface );
		$surfaces = lunara_site_studio_surfaces();
		return isset( $surfaces[ $surface ] ) ? $surfaces[ $surface ] : null;
	}
}

if ( ! function_exists( 'lunara_site_studio_surface_availability' ) ) {
	/**
	 * Resolve dependency availability only after the caller checks capability.
	 *
	 * @param array<string,mixed> $surface Normalized surface.
	 * @return array<string,mixed>
	 */
	function lunara_site_studio_surface_availability( $surface ) {
		if ( empty( $surface['available'] ) ) {
			return array( 'available' => false, 'reason' => $surface['unavailable_reason'], 'message' => __( 'This destination is unavailable because its registration is incomplete or conflicts with another owner.', 'lunara-film' ) );
		}
		$callback = isset( $surface['dependency_callback'] ) ? $surface['dependency_callback'] : '';
		if ( ! is_callable( $callback ) ) {
			return array( 'available' => false, 'reason' => 'invalid_callback', 'message' => __( 'This destination is unavailable because its dependency check is invalid.', 'lunara-film' ) );
		}
		$surface_id = isset( $surface['id'] ) ? $surface['id'] : '';
		if ( ! lunara_site_studio_boundary_guard( 'dependency', $surface_id ) ) {
			return array( 'available' => false, 'reason' => 'callback_reentry', 'message' => __( 'The required owner is not currently available.', 'lunara-film' ) );
		}
		try {
			$result = call_user_func( $callback, $surface );
		} catch ( Throwable $error ) {
			return array( 'available' => false, 'reason' => 'dependency_unavailable', 'message' => __( 'The required owner is not currently available.', 'lunara-film' ) );
		} finally {
			lunara_site_studio_boundary_guard( 'dependency', $surface_id, false );
		}
		if ( is_wp_error( $result ) ) {
			return array( 'available' => false, 'reason' => sanitize_key( $result->get_error_code() ), 'message' => __( 'The required owner is not currently available.', 'lunara-film' ) );
		}
		if ( is_array( $result ) ) {
			$available = ! empty( $result['available'] );
			return array(
				'available' => $available,
				'reason'    => $available ? '' : sanitize_key( isset( $result['reason'] ) ? $result['reason'] : 'missing_dependency' ),
				'message'   => sanitize_text_field( isset( $result['message'] ) ? $result['message'] : ( $available ? __( 'Available.', 'lunara-film' ) : __( 'The required owner is not currently available.', 'lunara-film' ) ) ),
			);
		}
		return array(
			'available' => true === $result,
			'reason'    => true === $result ? '' : 'missing_dependency',
			'message'   => true === $result ? __( 'Available.', 'lunara-film' ) : __( 'The required owner is not currently available.', 'lunara-film' ),
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_safe_status' ) ) {
	/** @return array<string,mixed> */
	function lunara_site_studio_safe_status( $status ) {
		if ( ! is_array( $status ) ) {
			return array();
		}
		$safe = array();
		foreach ( array( 'state', 'label', 'message', 'updated_at', 'action_label' ) as $field ) {
			if ( isset( $status[ $field ] ) && is_scalar( $status[ $field ] ) ) {
				$safe[ $field ] = sanitize_text_field( $status[ $field ] );
			}
		}
		if ( isset( $status['count'] ) && is_numeric( $status['count'] ) ) {
			$safe['count'] = absint( $status['count'] );
		}
		if ( isset( $status['url'] ) && is_scalar( $status['url'] ) ) {
			$url       = (string) $status['url'];
			$site_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
			$url_host  = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
			if ( '' !== $url_host && hash_equals( $site_host, $url_host ) ) {
				$safe['url'] = esc_url_raw( $url );
			}
		}
		return $safe;
	}
}

if ( ! function_exists( 'lunara_site_studio_public_surface' ) ) {
	/** @return array<string,mixed> */
	function lunara_site_studio_public_surface( $surface, $availability, $status = array() ) {
		$public_fields = array( 'id', 'group', 'label', 'description', 'aliases', 'owner', 'kind', 'capability', 'supports_preview', 'preview_route', 'danger_level', 'sections' );
		$public = array();
		foreach ( $public_fields as $field ) {
			$public[ $field ] = $surface[ $field ];
		}
		$public['available']          = ! empty( $availability['available'] );
		$public['unavailable_reason'] = $public['available'] ? '' : sanitize_key( isset( $availability['reason'] ) ? $availability['reason'] : 'unavailable' );
		$public['unavailable_message'] = sanitize_text_field( isset( $availability['message'] ) ? $availability['message'] : '' );
		$public['admin_url']          = '' === $surface['admin_url'] ? '' : admin_url( $surface['admin_url'] );
		$public['classic_url']        = admin_url( $surface['classic_url'] );
		$public['status']             = lunara_site_studio_safe_status( $status );
		return $public;
	}
}

if ( ! function_exists( 'lunara_site_studio_public_surfaces' ) ) {
	/**
	 * Capability-filtered, redacted registry for admin and REST consumers.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function lunara_site_studio_public_surfaces() {
		$public = array();
		foreach ( lunara_site_studio_surfaces() as $id => $surface ) {
			if ( ! current_user_can( $surface['capability'] ) ) {
				continue;
			}
			$availability = lunara_site_studio_surface_availability( $surface );
			$status       = array();
			if ( ! empty( $availability['available'] ) && is_callable( $surface['status_callback'] ) ) {
				if ( ! lunara_site_studio_boundary_guard( 'status', $id ) ) {
					$status = array( 'state' => 'unavailable', 'label' => __( 'Status unavailable', 'lunara-film' ), 'message' => __( 'Status could not be loaded from the canonical owner.', 'lunara-film' ) );
				} else {
					try {
						$status = call_user_func( $surface['status_callback'], $surface );
					} catch ( Throwable $error ) {
						$status = array(
							'state'   => 'unavailable',
							'label'   => __( 'Status unavailable', 'lunara-film' ),
							'message' => __( 'Status could not be loaded from the canonical owner.', 'lunara-film' ),
						);
					} finally {
						lunara_site_studio_boundary_guard( 'status', $id, false );
					}
				}
				if ( is_wp_error( $status ) ) {
					$status = array();
				}
			}
			$public[ $id ] = lunara_site_studio_public_surface( $surface, $availability, $status );
		}
		return $public;
	}
}

if ( ! function_exists( 'lunara_site_studio_admin_url' ) ) {
	/** @return string */
	function lunara_site_studio_admin_url( $surface = 'lunara-method' ) {
		$surface  = sanitize_key( (string) $surface );
		$surfaces = lunara_site_studio_surfaces();
		if ( ! isset( $surfaces[ $surface ] ) ) {
			$surface = 'lunara-method';
		}
		return add_query_arg(
			array( 'page' => 'lunara-site-studio', 'surface' => $surface ),
			admin_url( 'admin.php' )
		);
	}
}

if ( ! function_exists( 'lunara_site_studio_current_surface' ) ) {
	/** @return string */
	function lunara_site_studio_current_surface() {
		$surface  = isset( $_GET['surface'] ) ? sanitize_key( wp_unslash( $_GET['surface'] ) ) : 'lunara-method';
		$surfaces = lunara_site_studio_surfaces();
		return isset( $surfaces[ $surface ] ) ? $surface : 'lunara-method';
	}
}
