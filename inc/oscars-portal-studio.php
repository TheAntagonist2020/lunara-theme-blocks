<?php
/**
 * Focused Oscars Portal Studio public-state, preview, revision, and composer layer.
 *
 * Existing owners remain canonical: portal copy stays in the
 * lunara_oscars_portal_* text mods, section visibility stays in the
 * lunara_oscars_show_* mods (plus lunara_oscars_rotating_winners_enabled),
 * and the Prediction Board stays content-driven. This module adds only the
 * bounded composition data that previously had no WordPress owner: the slot
 * order and the presentation geometry, stored in one option with strict
 * last-valid promotion, a bounded revision history, and private previews.
 *
 * Ported from inc/reviews-archive-studio.php (the review-hardened Studio
 * shape): raw-scalar allowlist validation, fail-closed repair, no object
 * caching of the public config, no-store before any preview-token work,
 * owner-bound sha256-keyed preview transients, and provenance-gated saved
 * values (geometry only stamps the route after an explicit save or preview).
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LUNARA_OSCARS_PORTAL_STUDIO_OPTION' ) ) {
	define( 'LUNARA_OSCARS_PORTAL_STUDIO_OPTION', 'lunara_oscars_portal_studio' );
}
if ( ! defined( 'LUNARA_OSCARS_PORTAL_STUDIO_REVISIONS_OPTION' ) ) {
	define( 'LUNARA_OSCARS_PORTAL_STUDIO_REVISIONS_OPTION', 'lunara_oscars_portal_studio_revisions' );
}
if ( ! defined( 'LUNARA_OSCARS_PORTAL_STUDIO_REVISION_LIMIT' ) ) {
	define( 'LUNARA_OSCARS_PORTAL_STUDIO_REVISION_LIMIT', 12 );
}

/**
 * The eleven portal slots in the template's emission order.
 *
 * This is the order page-oscars.php has always emitted: the board HTML is
 * computed before the navigator (the navigator needs board emptiness for its
 * anchor link) but echoed after it, so `navigator` precedes `board` here.
 *
 * @return array<int,string>
 */
function lunara_oscars_portal_studio_slots() {
	return array( 'hero', 'navigator', 'board', 'doors', 'spotlights', 'titles', 'research', 'linked-reviews', 'winners', 'deep-cuts', 'rotating-winners' );
}

/**
 * Slot-to-visibility-owner map. The canonical storage is the existing
 * Customizer mods; this Studio aggregates them (the reviews-studio pattern)
 * and never invents a second owner.
 *
 * `setting` names are sourced from the shared portal section registry when
 * it is loaded, with identical local literals as the fail-closed fallback
 * for isolated runtime harnesses. Two slots have no toggle of their own:
 * the navigator is bound to the Quick Start doors toggle (it has always
 * rendered under the portal-links condition), and the board is
 * content-driven (an empty prediction board renders nothing).
 *
 * @return array<string,array{setting:string,default:bool,bound:string}>
 */
function lunara_oscars_portal_studio_visibility_owners() {
	$local = array(
		'hero'             => array( 'registry' => 'hero', 'setting' => 'lunara_oscars_show_hero', 'default' => true, 'bound' => '' ),
		'navigator'        => array( 'registry' => '', 'setting' => '', 'default' => true, 'bound' => 'doors' ),
		'board'            => array( 'registry' => '', 'setting' => '', 'default' => true, 'bound' => 'content' ),
		'doors'            => array( 'registry' => 'portal-links', 'setting' => 'lunara_oscars_show_portal_links', 'default' => true, 'bound' => '' ),
		'spotlights'       => array( 'registry' => 'spotlights', 'setting' => 'lunara_oscars_show_spotlights', 'default' => true, 'bound' => '' ),
		'titles'           => array( 'registry' => 'titles', 'setting' => 'lunara_oscars_show_title_cards', 'default' => true, 'bound' => '' ),
		'research'         => array( 'registry' => 'research', 'setting' => 'lunara_oscars_show_research', 'default' => true, 'bound' => '' ),
		'linked-reviews'   => array( 'registry' => 'linked-reviews', 'setting' => 'lunara_oscars_show_linked_reviews', 'default' => false, 'bound' => '' ),
		'winners'          => array( 'registry' => 'latest-winners', 'setting' => 'lunara_oscars_show_latest_winners', 'default' => true, 'bound' => '' ),
		'deep-cuts'        => array( 'registry' => 'deep-cuts', 'setting' => 'lunara_oscars_show_deep_cuts', 'default' => true, 'bound' => '' ),
		'rotating-winners' => array( 'registry' => 'rotating-winners', 'setting' => 'lunara_oscars_rotating_winners_enabled', 'default' => true, 'bound' => '' ),
	);

	if ( function_exists( 'lunara_get_oscars_portal_section_registry' ) ) {
		$registry = lunara_get_oscars_portal_section_registry();
		foreach ( $local as $slot => $owner ) {
			if ( '' !== $owner['registry'] && isset( $registry[ $owner['registry'] ]['setting'] ) && is_scalar( $registry[ $owner['registry'] ]['setting'] ) ) {
				$local[ $slot ]['setting'] = (string) $registry[ $owner['registry'] ]['setting'];
			}
		}
	}

	return $local;
}

/**
 * Identity copy fields whose canonical owners are the existing
 * lunara_oscars_portal_* text mods. Default literals reproduce the
 * page-oscars.php fallbacks byte-for-byte; the template ships them
 * untranslated, so the Studio deliberately keeps them untranslated too.
 *
 * @return array<string,array{setting:string,default:string,label:string,max:int}>
 */
function lunara_oscars_portal_studio_identity_specs() {
	return array(
		'kicker'             => array( 'setting' => 'lunara_oscars_portal_kicker', 'default' => 'The Lunara Oscar Ledger', 'label' => __( 'Hero kicker', 'lunara-film' ), 'max' => 140 ),
		'title'              => array( 'setting' => 'lunara_oscars_portal_title', 'default' => 'Academy Awards history, treated like a living editorial system.', 'label' => __( 'Hero headline', 'lunara-film' ), 'max' => 220 ),
		'explore_kicker'     => array( 'setting' => 'lunara_oscars_portal_explore_kicker', 'default' => 'Explore the Portal', 'label' => __( 'Quick Start kicker', 'lunara-film' ), 'max' => 140 ),
		'explore_heading'    => array( 'setting' => 'lunara_oscars_portal_explore_heading', 'default' => 'Start anywhere in the ledger.', 'label' => __( 'Quick Start heading', 'lunara-film' ), 'max' => 220 ),
		'spotlights_heading' => array( 'setting' => 'lunara_oscars_portal_spotlights_heading', 'default' => 'Latest Ceremony, category by category.', 'label' => __( 'Spotlights heading', 'lunara-film' ), 'max' => 220 ),
		'titles_kicker'      => array( 'setting' => 'lunara_oscars_portal_titles_kicker', 'default' => 'Poster-Led Entry Points', 'label' => __( 'Titles kicker', 'lunara-film' ), 'max' => 140 ),
		'titles_heading'     => array( 'setting' => 'lunara_oscars_portal_titles_heading', 'default' => 'Open the ledger through the films themselves.', 'label' => __( 'Titles heading', 'lunara-film' ), 'max' => 220 ),
		'research_kicker'    => array( 'setting' => 'lunara_oscars_portal_research_kicker', 'default' => 'Research Mode', 'label' => __( 'Research kicker', 'lunara-film' ), 'max' => 140 ),
		'research_heading'   => array( 'setting' => 'lunara_oscars_portal_research_heading', 'default' => 'Open the ledger without leaving the portal.', 'label' => __( 'Research heading', 'lunara-film' ), 'max' => 220 ),
		'reviews_heading'    => array( 'setting' => 'lunara_oscars_portal_reviews_heading', 'default' => 'Reviews Inside the Ledger', 'label' => __( 'Linked Reviews heading', 'lunara-film' ), 'max' => 220 ),
		'deep_cuts_heading'  => array( 'setting' => 'lunara_oscars_portal_deep_cuts_heading', 'default' => 'Oscar Deep Cuts', 'label' => __( 'Deep Cuts heading', 'lunara-film' ), 'max' => 220 ),
	);
}

/**
 * Presentation geometry bounds. Defaults describe the shipped stylesheet:
 * the portal hero floor is the 360px clamp minimum in lunara-shell.css, and
 * gap/card mirror the proven Reviews Studio envelope.
 *
 * @return array<string,array{default:int,min:int,max:int,label:string}>
 */
function lunara_oscars_portal_studio_geometry_specs() {
	return array(
		'section_gap'     => array( 'default' => 40, 'min' => 20, 'max' => 90, 'label' => __( 'Section gap (px)', 'lunara-film' ) ),
		'hero_min_height' => array( 'default' => 360, 'min' => 300, 'max' => 640, 'label' => __( 'Hero minimum height (px)', 'lunara-film' ) ),
		'card_min_height' => array( 'default' => 360, 'min' => 260, 'max' => 540, 'label' => __( 'Card minimum height (px)', 'lunara-film' ) ),
	);
}

/**
 * Complete fallback state. Defaults intentionally reproduce the current
 * public portal output byte-for-byte: template emission order, the shipped
 * theme-mod visibility defaults (Linked Reviews ships hidden), the
 * untranslated copy fallbacks, and the shipped geometry.
 *
 * @return array<string,mixed>
 */
function lunara_oscars_portal_studio_defaults() {
	$identity = array();
	foreach ( lunara_oscars_portal_studio_identity_specs() as $field => $spec ) {
		$identity[ $field ] = $spec['default'];
	}

	$visibility = array();
	foreach ( lunara_oscars_portal_studio_visibility_owners() as $slot => $owner ) {
		$visibility[ $slot ] = (bool) $owner['default'];
	}

	$presentation = array();
	foreach ( lunara_oscars_portal_studio_geometry_specs() as $key => $spec ) {
		$presentation[ $key ] = $spec['default'];
	}

	return array(
		'schema_version'     => 1,
		'identity'           => $identity,
		'section_order'      => lunara_oscars_portal_studio_slots(),
		'section_visibility' => $visibility,
		'presentation'       => $presentation,
	);
}

/**
 * Return a scalar owner value or its safe field-local fallback.
 *
 * @param mixed $value Candidate value.
 * @param mixed $fallback Scalar fallback.
 * @return mixed
 */
function lunara_oscars_portal_studio_scalar_or( $value, $fallback ) {
	return is_scalar( $value ) ? $value : $fallback;
}

/**
 * Bounded plain text.
 *
 * @param mixed $value Input.
 * @param int   $max Maximum characters.
 * @return string
 */
function lunara_oscars_portal_studio_text( $value, $max = 180 ) {
	$value = sanitize_text_field( is_scalar( $value ) ? $value : '' );
	return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
}

/**
 * Repair a raw order candidate (array or comma string) to the canonical
 * eleven-slot set: recognized slugs keep their relative order, duplicates
 * collapse, and missing slots append in template order. Fail-closed — the
 * result always contains every slot exactly once.
 *
 * @param mixed $raw Raw order candidate.
 * @return array<int,string>
 */
function lunara_oscars_portal_studio_expand_section_order( $raw ) {
	$required = lunara_oscars_portal_studio_slots();

	if ( is_array( $raw ) ) {
		$parts = array_values( $raw );
	} else {
		$parts = explode( ',', strtolower( is_scalar( $raw ) ? (string) $raw : '' ) );
	}

	$allowed = array_fill_keys( $required, true );
	$ordered = array();
	foreach ( $parts as $slug ) {
		$slug = trim( is_scalar( $slug ) ? (string) $slug : '' );
		if ( isset( $allowed[ $slug ] ) && ! in_array( $slug, $ordered, true ) ) {
			$ordered[] = $slug;
		}
	}

	foreach ( $required as $slug ) {
		if ( ! in_array( $slug, $ordered, true ) ) {
			$ordered[] = $slug;
		}
	}

	return $ordered;
}

/**
 * Read only the option-owned fields (order + geometry) raw.
 *
 * @return array<string,mixed>
 */
function lunara_oscars_portal_studio_get_new_fields() {
	$stored = get_option( LUNARA_OSCARS_PORTAL_STUDIO_OPTION, array() );
	return is_array( $stored ) ? $stored : array();
}

/**
 * Whether the stored option carries an explicitly saved presentation.
 *
 * Provenance gate for the public route: the resolved config always exposes
 * bounded geometry, but the template only stamps geometry custom properties
 * after an editor's explicit save (or inside a private preview). A site that
 * never saved the Studio keeps today's byte-identical markup.
 *
 * @return bool
 */
function lunara_oscars_portal_studio_has_saved_presentation() {
	$stored = get_option( LUNARA_OSCARS_PORTAL_STUDIO_OPTION, array() );
	return is_array( $stored ) && isset( $stored['presentation'] );
}

/**
 * Normalize corrupt nested public families without discarding valid siblings.
 *
 * Strict save/preview/restore validation still rejects malformed input. This
 * defensive pass is only for previously stored or legacy public owners, where
 * a scalar in place of an array must never fatal the portal route.
 *
 * @param mixed               $config Composite public owner data.
 * @param array<string,mixed> $defaults Optional validated defaults.
 * @return array<string,mixed>
 */
function lunara_oscars_portal_studio_normalize_public_shape( $config, $defaults = array() ) {
	$defaults = is_array( $defaults ) && ! empty( $defaults ) ? $defaults : lunara_oscars_portal_studio_defaults();
	$config   = is_array( $config ) ? array_replace( $defaults, $config ) : $defaults;

	$config['schema_version'] = lunara_oscars_portal_studio_scalar_or( $config['schema_version'], $defaults['schema_version'] );

	foreach ( array( 'identity', 'section_visibility', 'presentation' ) as $family ) {
		if ( ! isset( $config[ $family ] ) || ! is_array( $config[ $family ] ) ) {
			$config[ $family ] = $defaults[ $family ];
			continue;
		}
		$raw_family        = $config[ $family ];
		$config[ $family ] = $defaults[ $family ];
		foreach ( $defaults[ $family ] as $field => $fallback ) {
			if ( array_key_exists( $field, $raw_family ) ) {
				$config[ $family ][ $field ] = lunara_oscars_portal_studio_scalar_or( $raw_family[ $field ], $fallback );
			}
		}
	}

	$section_order = isset( $config['section_order'] ) && is_array( $config['section_order'] ) ? array_values( $config['section_order'] ) : array();
	$config['section_order'] = count( $section_order ) === count( array_filter( $section_order, 'is_scalar' ) )
		? $section_order
		: $defaults['section_order'];

	return $config;
}

/**
 * Repair only the malformed owner family in legacy/corrupt public data.
 *
 * Promotion and restore remain strict. Public resolution is defensive because
 * a stored Customizer or option value can become invalid after bounds evolve;
 * one bad geometry number must never erase an unrelated saved order.
 *
 * @param array<string,mixed> $config Composite owner data.
 * @param array<string,mixed> $defaults Valid defaults.
 * @return array<string,mixed>|WP_Error
 */
function lunara_oscars_portal_studio_repair_public_config( $config, $defaults ) {
	for ( $attempt = 0; $attempt < 12; $attempt++ ) {
		$validated = lunara_oscars_portal_studio_validate_config( $config );
		if ( ! is_wp_error( $validated ) ) {
			return $validated;
		}

		switch ( $validated->get_error_code() ) {
			case 'oscars_portal_identity_invalid':
				$config['identity'] = is_array( $config['identity'] ) ? $config['identity'] : array();
				foreach ( $defaults['identity'] as $field => $fallback ) {
					if ( ! isset( $config['identity'][ $field ] ) || ! is_scalar( $config['identity'][ $field ] ) ) {
						$config['identity'][ $field ] = $fallback;
					}
				}
				break;

			case 'oscars_portal_section_order_invalid':
				$config['section_order'] = lunara_oscars_portal_studio_expand_section_order(
					is_array( $config['section_order'] ) ? array_filter( $config['section_order'], 'is_scalar' ) : array()
				);
				break;

			case 'oscars_portal_visibility_invalid':
				$config['section_visibility'] = is_array( $config['section_visibility'] ) ? $config['section_visibility'] : array();
				foreach ( $defaults['section_visibility'] as $slot => $fallback ) {
					$candidate = isset( $config['section_visibility'][ $slot ] ) ? $config['section_visibility'][ $slot ] : $fallback;
					$config['section_visibility'][ $slot ] = is_scalar( $candidate ) ? (bool) $candidate : (bool) $fallback;
				}
				break;

			case 'oscars_portal_geometry_invalid':
				$config['presentation'] = is_array( $config['presentation'] ) ? $config['presentation'] : array();
				foreach ( lunara_oscars_portal_studio_geometry_specs() as $key => $spec ) {
					$value = isset( $config['presentation'][ $key ] ) && is_scalar( $config['presentation'][ $key ] ) ? absint( $config['presentation'][ $key ] ) : 0;
					if ( $value < $spec['min'] || $value > $spec['max'] ) {
						$config['presentation'][ $key ] = $defaults['presentation'][ $key ];
					}
				}
				break;

			default:
				return $validated;
		}
	}

	return new WP_Error( 'oscars_portal_config_repair_failed' );
}

/**
 * Resolve the current, last-valid public configuration.
 *
 * Deliberately uncached and recomputed on every call, exactly like the
 * journal and reviews references: get_theme_mod reads are
 * Customizer-preview-filtered and every visibility/copy owner has
 * independent writers, so a persistent object-cache entry could pin
 * poisoned preview state or go stale against owners this module never
 * writes. The wp_cache_delete calls in apply_config and flush_route_cache
 * remain as harmless hygiene for any legacy cached entry.
 *
 * @param bool $allow_preview Whether a private token may override the request.
 * @return array<string,mixed>
 */
function lunara_oscars_portal_studio_get_public_config( $allow_preview = true ) {
	if ( $allow_preview && isset( $_GET['lunara_oscars_preview'] ) ) {
		$preview = lunara_oscars_portal_studio_get_preview_config( sanitize_text_field( wp_unslash( $_GET['lunara_oscars_preview'] ) ) );
		if ( is_array( $preview ) ) {
			$preview['_preview'] = true;
			return $preview;
		}
	}

	$defaults = lunara_oscars_portal_studio_defaults();
	$stored   = lunara_oscars_portal_studio_get_new_fields();
	$config   = $defaults;

	// The lunara_oscars_portal_* text mods remain the copy owner. Resolution
	// mirrors lunara_theme_mod_text: a trimmed non-empty mod wins, anything
	// else keeps the shipped literal, so an unsaved site renders today's copy
	// byte-for-byte (with a scalar guard instead of a raw string cast).
	// Mod-sourced identity is VERBATIM (the reviews-archive-studio shape): the
	// sanitize/cap discipline in lunara_oscars_portal_studio_text() is a
	// save-time gate for request input (config_from_request → validate_config
	// before promotion), never a rewrite of copy an existing owner already
	// stores. Escaping stays at output — page-oscars.php esc_html()s every
	// identity field.
	$verbatim_identity = array();
	foreach ( lunara_oscars_portal_studio_identity_specs() as $field => $spec ) {
		$stored_copy = lunara_oscars_portal_studio_scalar_or( get_theme_mod( $spec['setting'], '' ), '' );
		$stored_copy = trim( (string) $stored_copy );
		$verbatim_identity[ $field ] = '' !== $stored_copy ? $stored_copy : $spec['default'];
	}
	$config['identity'] = $verbatim_identity;

	// The lunara_oscars_show_* mods (and the rotating-winners enable) remain
	// the visibility owners; bound slots derive after the owned reads.
	$owners = lunara_oscars_portal_studio_visibility_owners();
	foreach ( $owners as $slot => $owner ) {
		if ( '' === $owner['setting'] ) {
			continue;
		}
		$mod = get_theme_mod( $owner['setting'], (bool) $owner['default'] );
		$config['section_visibility'][ $slot ] = is_scalar( $mod ) ? (bool) $mod : (bool) $owner['default'];
	}
	$config['section_visibility']['board']     = true;
	$config['section_visibility']['navigator'] = ! empty( $config['section_visibility']['doors'] );

	if ( array_key_exists( 'section_order', $stored ) ) {
		$config['section_order'] = $stored['section_order'];
	}
	if ( array_key_exists( 'presentation', $stored ) && is_array( $stored['presentation'] ) ) {
		$config['presentation'] = array_replace( $defaults['presentation'], $stored['presentation'] );
	}

	$config    = lunara_oscars_portal_studio_normalize_public_shape( $config, $defaults );
	$validated = lunara_oscars_portal_studio_repair_public_config( $config, $defaults );
	if ( is_wp_error( $validated ) ) {
		// Every validator family is repaired above. Keep this impossible-state
		// fallback valid without pretending the malformed candidate was public.
		$validated = lunara_oscars_portal_studio_validate_config( $defaults );
	}

	// Re-assert the mod-sourced identity after the repair/validate pass. The
	// validator's identity sanitize/cap exists for SAVE candidates; on a pure
	// public read it must not rewrite what the theme-mod owner stores, or an
	// unsaved site loses byte-parity with today's trim-then-esc_html render.
	if ( is_array( $validated ) ) {
		$validated['identity'] = $verbatim_identity;
	}

	return $validated;
}

/**
 * Validate a complete staged configuration without mutating public state.
 *
 * Enum and slot candidates are validated raw against explicit allowlists;
 * nothing is key-normalized before its allowlist decision. The two derived
 * lanes are the only canonicalized values, and only after their owners
 * validate: the board is always true (content-driven) and the navigator
 * always equals the doors toggle — that binding is the ownership contract,
 * not a repair of corrupt input.
 *
 * @param mixed $raw Candidate configuration.
 * @return array<string,mixed>|WP_Error
 */
function lunara_oscars_portal_studio_validate_config( $raw ) {
	if ( ! is_array( $raw ) ) {
		return new WP_Error( 'oscars_portal_config_invalid' );
	}

	$defaults     = lunara_oscars_portal_studio_defaults();
	$shape_errors = array(
		'identity'           => 'oscars_portal_identity_invalid',
		'section_order'      => 'oscars_portal_section_order_invalid',
		'section_visibility' => 'oscars_portal_visibility_invalid',
		'presentation'       => 'oscars_portal_geometry_invalid',
	);
	foreach ( $shape_errors as $family => $error_code ) {
		if ( array_key_exists( $family, $raw ) && ! is_array( $raw[ $family ] ) ) {
			return new WP_Error( $error_code );
		}
	}
	if ( array_key_exists( 'schema_version', $raw ) && ! is_scalar( $raw['schema_version'] ) ) {
		return new WP_Error( 'oscars_portal_config_invalid' );
	}
	foreach ( $shape_errors as $family => $error_code ) {
		if ( ! isset( $raw[ $family ] ) || ! is_array( $raw[ $family ] ) ) {
			continue;
		}
		foreach ( $raw[ $family ] as $value ) {
			if ( ! is_scalar( $value ) ) {
				return new WP_Error( $error_code );
			}
		}
	}

	$config                   = array_replace_recursive( $defaults, $raw );
	$config['schema_version'] = 1;

	// Identity: bounded text; an explicitly empty field folds to its shipped
	// literal — the same "empty inherits the default" semantic the
	// lunara_theme_mod_text owner has always applied at render time.
	foreach ( lunara_oscars_portal_studio_identity_specs() as $field => $spec ) {
		$value = lunara_oscars_portal_studio_text( isset( $config['identity'][ $field ] ) ? $config['identity'][ $field ] : '', $spec['max'] );
		$config['identity'][ $field ] = '' !== $value ? $value : $spec['default'];
	}
	$config['identity'] = array_intersect_key( $config['identity'], $defaults['identity'] );

	$required_order = lunara_oscars_portal_studio_slots();
	$order          = array();
	foreach ( is_array( $config['section_order'] ) ? array_values( $config['section_order'] ) : array() as $slug ) {
		$order[] = is_scalar( $slug ) ? (string) $slug : '';
	}
	if ( count( $order ) !== count( array_unique( $order ) ) ) {
		return new WP_Error( 'oscars_portal_section_order_invalid' );
	}
	$sorted_order = $order;
	sort( $sorted_order );
	$sorted_required = $required_order;
	sort( $sorted_required );
	if ( $sorted_required !== $sorted_order ) {
		return new WP_Error( 'oscars_portal_section_order_invalid' );
	}
	$config['section_order'] = array_values( $order );

	$visibility = is_array( $config['section_visibility'] ) ? $config['section_visibility'] : array();
	$config['section_visibility'] = array();
	foreach ( $required_order as $slot ) {
		$config['section_visibility'][ $slot ] = ! empty( $visibility[ $slot ] );
	}
	// Derived lanes: their owners are the doors toggle and board content.
	$config['section_visibility']['board']     = true;
	$config['section_visibility']['navigator'] = $config['section_visibility']['doors'];

	foreach ( lunara_oscars_portal_studio_geometry_specs() as $key => $spec ) {
		$config['presentation'][ $key ] = absint( $config['presentation'][ $key ] );
		if ( $config['presentation'][ $key ] < $spec['min'] || $config['presentation'][ $key ] > $spec['max'] ) {
			return new WP_Error( 'oscars_portal_geometry_invalid' );
		}
	}
	$config['presentation'] = array_intersect_key( $config['presentation'], $defaults['presentation'] );

	return array(
		'schema_version'     => 1,
		'identity'           => $config['identity'],
		'section_order'      => $config['section_order'],
		'section_visibility' => $config['section_visibility'],
		'presentation'       => $config['presentation'],
	);
}

/**
 * Build a complete candidate from the focused form.
 *
 * @param array<string,mixed> $request Request data.
 * @return array<string,mixed>
 */
function lunara_oscars_portal_studio_config_from_request( $request ) {
	$current = lunara_oscars_portal_studio_get_public_config( false );
	$request = is_array( $request ) ? wp_unslash( $request ) : array();

	$identity = isset( $request['lunara_oscars_portal_identity'] ) && is_array( $request['lunara_oscars_portal_identity'] ) ? $request['lunara_oscars_portal_identity'] : array();
	foreach ( array_keys( lunara_oscars_portal_studio_identity_specs() ) as $field ) {
		$current['identity'][ $field ] = isset( $identity[ $field ] ) ? $identity[ $field ] : '';
	}

	$owners     = lunara_oscars_portal_studio_visibility_owners();
	$visibility = isset( $request['lunara_oscars_portal_section_visibility'] ) && is_array( $request['lunara_oscars_portal_section_visibility'] ) ? $request['lunara_oscars_portal_section_visibility'] : array();
	foreach ( $owners as $slot => $owner ) {
		if ( '' !== $owner['bound'] ) {
			continue; // Derived lanes are recomputed by the validator.
		}
		$current['section_visibility'][ $slot ] = array_key_exists( $slot, $visibility ) ? $visibility[ $slot ] : false;
	}

	$positions       = isset( $request['lunara_oscars_portal_section_positions'] ) && is_array( $request['lunara_oscars_portal_section_positions'] ) ? $request['lunara_oscars_portal_section_positions'] : array();
	$positioned      = array();
	$position_values = array();
	$positions_valid = true;
	foreach ( $current['section_order'] as $slug ) {
		$position = isset( $positions[ $slug ] ) && is_scalar( $positions[ $slug ] ) ? absint( $positions[ $slug ] ) : 0;
		if ( $position < 1 || $position > count( $current['section_order'] ) || in_array( $position, $position_values, true ) ) {
			$positions_valid = false;
		}
		$positioned[ $slug ] = $position;
		$position_values[]   = $position;
	}
	if ( $positions_valid ) {
		asort( $positioned, SORT_NUMERIC );
		$current['section_order'] = array_keys( $positioned );
	} else {
		$current['section_order'] = array( 'invalid-position' );
	}

	$numbers = isset( $request['lunara_oscars_portal_number'] ) && is_array( $request['lunara_oscars_portal_number'] ) ? $request['lunara_oscars_portal_number'] : array();
	foreach ( array_keys( lunara_oscars_portal_studio_geometry_specs() ) as $key ) {
		if ( isset( $numbers[ $key ] ) ) {
			$current['presentation'][ $key ] = $numbers[ $key ];
		}
	}

	unset( $current['_preview'] );

	return $current;
}

/**
 * Apply a prevalidated configuration to its canonical WordPress owners.
 *
 * Copy writes to the lunara_oscars_portal_* mods, visibility writes to the
 * lunara_oscars_show_* mods (and the rotating enable); the option stores
 * only what has no prior owner: the slot order and the geometry.
 *
 * @param array<string,mixed> $config Valid configuration.
 * @return void
 */
function lunara_oscars_portal_studio_apply_config( $config ) {
	foreach ( lunara_oscars_portal_studio_identity_specs() as $field => $spec ) {
		set_theme_mod( $spec['setting'], (string) $config['identity'][ $field ] );
	}

	foreach ( lunara_oscars_portal_studio_visibility_owners() as $slot => $owner ) {
		if ( '' === $owner['setting'] ) {
			continue;
		}
		set_theme_mod( $owner['setting'], ! empty( $config['section_visibility'][ $slot ] ) );
	}

	update_option(
		LUNARA_OSCARS_PORTAL_STUDIO_OPTION,
		array(
			'schema_version' => 1,
			'section_order'  => $config['section_order'],
			'presentation'   => $config['presentation'],
		),
		false
	);
	wp_cache_delete( 'oscars_portal_studio_public', 'lunara' );
}

/**
 * Return newest-first bounded audit history.
 *
 * @return array<int,array<string,mixed>>
 */
function lunara_oscars_portal_studio_get_revisions() {
	$revisions = get_option( LUNARA_OSCARS_PORTAL_STUDIO_REVISIONS_OPTION, array() );
	return is_array( $revisions ) ? array_slice( $revisions, 0, LUNARA_OSCARS_PORTAL_STUDIO_REVISION_LIMIT ) : array();
}

/**
 * Save a prior-public snapshot with audit metadata.
 *
 * @param array<string,mixed> $config Public snapshot.
 * @param string              $action Audit action.
 * @param string              $validator_result Validation result for replacement.
 * @param bool                $prior_public Whether snapshot was public.
 * @return string|WP_Error Verified revision ID or persistence failure.
 */
function lunara_oscars_portal_studio_push_revision( $config, $action = 'save', $validator_result = 'passed', $prior_public = true ) {
	$revisions = lunara_oscars_portal_studio_get_revisions();
	$id        = wp_generate_uuid4();
	array_unshift(
		$revisions,
		array(
			'id'               => $id,
			'saved_at'         => current_time( 'mysql' ),
			'saved_by'         => absint( get_current_user_id() ),
			'action'           => sanitize_key( $action ),
			'validator_result' => sanitize_key( $validator_result ),
			'prior_public'     => (bool) $prior_public,
			'config'           => $config,
		)
	);
	if ( ! update_option( LUNARA_OSCARS_PORTAL_STUDIO_REVISIONS_OPTION, array_slice( $revisions, 0, LUNARA_OSCARS_PORTAL_STUDIO_REVISION_LIMIT ), false ) ) {
		return new WP_Error( 'oscars_portal_revision_write_failed', __( 'The Oscars safety revision could not be stored.', 'lunara-film' ) );
	}
	$stored = lunara_oscars_portal_studio_get_revisions();
	$verified = false;
	foreach ( $stored as $revision ) {
		if ( is_array( $revision ) && ! empty( $revision['id'] ) && hash_equals( $id, (string) $revision['id'] ) ) {
			$verified = true;
			break;
		}
	}
	if ( ! $verified ) {
		return new WP_Error( 'oscars_portal_revision_readback_failed', __( 'The Oscars safety revision could not be verified.', 'lunara-film' ) );
	}
	return $id;
}

/**
 * Validate, durably snapshot, and promote while returning transaction metadata.
 *
 * @param mixed  $raw Candidate configuration.
 * @param string $action Audit action.
 * @return array{state:array<string,mixed>,revision_id:string}|WP_Error
 */
function lunara_oscars_portal_studio_promote_config_transaction( $raw, $action = 'save' ) {
	$validated = lunara_oscars_portal_studio_validate_config( $raw );
	if ( is_wp_error( $validated ) ) {
		return $validated;
	}
	$prior = lunara_oscars_portal_studio_get_public_config( false );
	$revision_id = lunara_oscars_portal_studio_push_revision( $prior, $action, 'passed', true );
	if ( is_wp_error( $revision_id ) ) {
		return $revision_id;
	}
	lunara_oscars_portal_studio_apply_config( $validated );
	lunara_oscars_portal_studio_flush_route_cache();
	return array( 'state' => $validated, 'revision_id' => $revision_id );
}

/** Preserve the public state-shaped promotion contract. */
function lunara_oscars_portal_studio_promote_config( $raw, $action = 'save' ) {
	$transaction = lunara_oscars_portal_studio_promote_config_transaction( $raw, $action );
	return is_wp_error( $transaction ) ? $transaction : $transaction['state'];
}

/**
 * Restore a prior valid public snapshot and return transaction metadata.
 *
 * @param string $revision_id Revision UUID.
 * @return array{state:array<string,mixed>,safety_revision_id:string}|WP_Error
 */
function lunara_oscars_portal_studio_restore_revision_transaction( $revision_id ) {
	$revision_id = sanitize_text_field( $revision_id );
	foreach ( lunara_oscars_portal_studio_get_revisions() as $revision ) {
		if ( empty( $revision['id'] ) || ! hash_equals( (string) $revision['id'], $revision_id ) || empty( $revision['prior_public'] ) ) {
			continue;
		}
		$validated = lunara_oscars_portal_studio_validate_config( isset( $revision['config'] ) ? $revision['config'] : array() );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}
		$current = lunara_oscars_portal_studio_get_public_config( false );
		$safety_id = lunara_oscars_portal_studio_push_revision( $current, 'restore', 'passed', true );
		if ( is_wp_error( $safety_id ) ) {
			return $safety_id;
		}
		lunara_oscars_portal_studio_apply_config( $validated );
		lunara_oscars_portal_studio_flush_route_cache();
		return array( 'state' => $validated, 'safety_revision_id' => $safety_id );
	}
	return new WP_Error( 'oscars_portal_revision_not_found' );
}

/** Preserve the public state-shaped restore contract. */
function lunara_oscars_portal_studio_restore_revision( $revision_id ) {
	$transaction = lunara_oscars_portal_studio_restore_revision_transaction( $revision_id );
	return is_wp_error( $transaction ) ? $transaction : $transaction['state'];
}

/**
 * The bounded portal-route URL set: exactly the resolved /oscars/ page.
 *
 * Ledger routes are contractually exempt — this Studio composes only the
 * portal, so no aat_* URL is ever collected here.
 *
 * @return array<int,string>
 */
function lunara_oscars_portal_studio_cache_urls() {
	$urls = array( home_url( '/oscars/' ) );

	$portal_page = function_exists( 'get_page_by_path' ) ? get_page_by_path( 'oscars' ) : null;
	if ( is_object( $portal_page ) && function_exists( 'get_permalink' ) ) {
		$permalink = get_permalink( $portal_page );
		if ( $permalink ) {
			$urls[] = $permalink;
		}
	}

	return array_values( array_unique( array_filter( array_map( 'esc_url_raw', $urls ) ) ) );
}

function lunara_oscars_portal_studio_flush_route_cache() {
	$routes = array( '/oscars/' );
	$urls   = lunara_oscars_portal_studio_cache_urls();
	wp_cache_delete( 'oscars_portal_studio_public', 'lunara' );

	// WP Rocket's bounded URL cleaner is used only when already available.
	// Never invoke a domain-wide purge, purge a CDN, or toggle a plugin here.
	if ( function_exists( 'rocket_clean_files' ) && $urls ) {
		rocket_clean_files( $urls );
	}
	do_action( 'lunara_oscars_portal_studio_invalidate_routes', $routes, $urls );
}

/**
 * Time helper kept testable without changing system clocks.
 *
 * @return int
 */
function lunara_oscars_portal_studio_timestamp() {
	return (int) current_time( 'timestamp', true );
}

/**
 * Store a private unsaved preview for thirty minutes.
 *
 * @param mixed $raw Candidate configuration.
 * @return string|WP_Error
 */
function lunara_oscars_portal_studio_store_preview( $raw ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return new WP_Error( 'oscars_portal_preview_forbidden' );
	}
	$validated = lunara_oscars_portal_studio_validate_config( $raw );
	if ( is_wp_error( $validated ) ) {
		return $validated;
	}
	$token   = wp_generate_uuid4();
	$user_id = absint( get_current_user_id() );
	$key     = 'lunara_oscars_portal_preview_' . hash( 'sha256', $token );
	set_transient(
		$key,
		array(
			'user_id'    => $user_id,
			'token_hash' => wp_hash( $token . '|' . $user_id ),
			'expires'    => lunara_oscars_portal_studio_timestamp() + 1800,
			'config'     => $validated,
		),
		1800
	);
	return $token;
}

/**
 * Retrieve a preview only for its authorized owner.
 *
 * @param string $token Preview token.
 * @return array<string,mixed>|false
 */
function lunara_oscars_portal_studio_get_preview_config( $token ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return false;
	}
	$token  = sanitize_text_field( $token );
	$record = get_transient( 'lunara_oscars_portal_preview_' . hash( 'sha256', $token ) );
	if ( ! is_array( $record ) || empty( $record['user_id'] ) || absint( $record['user_id'] ) !== absint( get_current_user_id() ) ) {
		return false;
	}
	$expected = wp_hash( $token . '|' . absint( $record['user_id'] ) );
	if ( empty( $record['token_hash'] ) || ! hash_equals( (string) $record['token_hash'], $expected ) || empty( $record['expires'] ) || absint( $record['expires'] ) <= lunara_oscars_portal_studio_timestamp() ) {
		return false;
	}
	$validated = lunara_oscars_portal_studio_validate_config( isset( $record['config'] ) ? $record['config'] : array() );
	return is_wp_error( $validated ) ? false : $validated;
}

/**
 * Per-user transient key for a rejected, non-public form draft.
 *
 * @return string
 */
function lunara_oscars_portal_studio_invalid_stage_key() {
	return 'lunara_oscars_portal_invalid_' . absint( get_current_user_id() );
}

/**
 * Per-user transient key for bounded validator feedback.
 *
 * @return string
 */
function lunara_oscars_portal_studio_feedback_key() {
	return 'lunara_oscars_portal_feedback_' . absint( get_current_user_id() );
}

/**
 * Convert a rejected candidate into a bounded private form draft.
 *
 * This deliberately does not make the candidate valid or public. It only
 * keeps the editor's escaped field values available on the return screen so
 * the reported validation problem can be corrected instead of retyped.
 *
 * @param array<string,mixed> $candidate Candidate from the request adapter.
 * @param array<string,mixed> $request Raw request, used only for positions.
 * @return array<string,mixed>
 */
function lunara_oscars_portal_studio_bound_invalid_stage( $candidate, $request = array() ) {
	$defaults = lunara_oscars_portal_studio_defaults();
	$stage    = lunara_oscars_portal_studio_normalize_public_shape( array_replace_recursive( $defaults, is_array( $candidate ) ? $candidate : array() ), $defaults );
	$request  = is_array( $request ) ? wp_unslash( $request ) : array();

	foreach ( lunara_oscars_portal_studio_identity_specs() as $field => $spec ) {
		$stage['identity'][ $field ] = lunara_oscars_portal_studio_text( isset( $stage['identity'][ $field ] ) ? $stage['identity'][ $field ] : '', $spec['max'] );
	}

	$required    = lunara_oscars_portal_studio_slots();
	$valid_order = array();
	foreach ( is_array( $stage['section_order'] ) ? array_values( $stage['section_order'] ) : array() as $slug ) {
		$valid_order[] = is_scalar( $slug ) ? (string) $slug : '';
	}
	$sorted          = $valid_order;
	$sorted_required = $required;
	sort( $sorted );
	sort( $sorted_required );
	if ( count( $valid_order ) !== count( array_unique( $valid_order ) ) || $sorted !== $sorted_required ) {
		$stage['section_order'] = lunara_oscars_portal_studio_get_public_config( false )['section_order'];
	} else {
		$stage['section_order'] = $valid_order;
	}

	$raw_positions = isset( $request['lunara_oscars_portal_section_positions'] ) && is_array( $request['lunara_oscars_portal_section_positions'] )
		? $request['lunara_oscars_portal_section_positions']
		: array();
	$stage['_staged_positions'] = array();
	foreach ( $required as $slug ) {
		$position = isset( $raw_positions[ $slug ] ) && is_scalar( $raw_positions[ $slug ] ) ? absint( $raw_positions[ $slug ] ) : 0;
		if ( $position >= 1 && $position <= count( $required ) ) {
			$stage['_staged_positions'][ $slug ] = $position;
		}
		$stage['section_visibility'][ $slug ] = ! empty( $stage['section_visibility'][ $slug ] );
	}

	foreach ( lunara_oscars_portal_studio_geometry_specs() as $key => $spec ) {
		$stage['presentation'][ $key ] = min( 9999, absint( is_scalar( $stage['presentation'][ $key ] ) ? $stage['presentation'][ $key ] : 0 ) );
	}
	unset( $stage['_preview'] );

	return $stage;
}

/**
 * Persist a rejected private draft and its allowlisted reason for 30 minutes.
 *
 * @param array<string,mixed> $candidate Rejected candidate.
 * @param array<string,mixed> $request Request values.
 * @param string              $error_code Validator code.
 * @return void
 */
function lunara_oscars_portal_studio_store_invalid_stage( $candidate, $request, $error_code ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	set_transient( lunara_oscars_portal_studio_invalid_stage_key(), lunara_oscars_portal_studio_bound_invalid_stage( $candidate, $request ), 30 * 60 );
	set_transient( lunara_oscars_portal_studio_feedback_key(), lunara_oscars_portal_studio_bound_feedback_code( $error_code ), 30 * 60 );
}

/**
 * Return the current editor's rejected private form draft.
 *
 * @return array<string,mixed>|false
 */
function lunara_oscars_portal_studio_get_invalid_stage() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return false;
	}
	$stage = get_transient( lunara_oscars_portal_studio_invalid_stage_key() );
	return is_array( $stage ) ? $stage : false;
}

/**
 * Clear rejected draft/feedback after a successful state transition.
 */
function lunara_oscars_portal_studio_clear_invalid_stage() {
	delete_transient( lunara_oscars_portal_studio_invalid_stage_key() );
	delete_transient( lunara_oscars_portal_studio_feedback_key() );
}

/**
 * Store restore feedback without replacing a rejected edit draft.
 *
 * @param string $error_code Validator code.
 */
function lunara_oscars_portal_studio_store_feedback( $error_code ) {
	if ( current_user_can( 'edit_theme_options' ) ) {
		set_transient( lunara_oscars_portal_studio_feedback_key(), lunara_oscars_portal_studio_bound_feedback_code( $error_code ), 30 * 60 );
	}
}

/**
 * Bound a candidate feedback code to the exact validator allowlist.
 *
 * The raw scalar is checked against the message allowlist directly; an
 * unknown code stores as empty and later resolves to the generic message.
 *
 * @param mixed $error_code Candidate validator code.
 * @return string
 */
function lunara_oscars_portal_studio_bound_feedback_code( $error_code ) {
	$code = is_scalar( $error_code ) ? (string) $error_code : '';
	return array_key_exists( $code, lunara_oscars_portal_studio_validation_messages() ) ? $code : '';
}

/**
 * The complete validator message allowlist; no raw error text ever leaks.
 *
 * @return array<string,string>
 */
function lunara_oscars_portal_studio_validation_messages() {
	return array(
		'oscars_portal_config_invalid'        => __( 'The staged portal configuration was malformed and changed nothing public.', 'lunara-film' ),
		'oscars_portal_config_repair_failed'  => __( 'The stored portal configuration could not be repaired; the defaults remain live.', 'lunara-film' ),
		'oscars_portal_identity_invalid'      => __( 'One portal copy field was malformed; leave a field empty to keep its shipped copy.', 'lunara-film' ),
		'oscars_portal_section_order_invalid' => __( 'Give every portal section one unique position.', 'lunara-film' ),
		'oscars_portal_visibility_invalid'    => __( 'One section visibility value was malformed.', 'lunara-film' ),
		'oscars_portal_geometry_invalid'      => __( 'One geometry value is outside its displayed bounds.', 'lunara-film' ),
		'oscars_portal_revision_not_found'    => __( 'That revision is no longer available to restore.', 'lunara-film' ),
		'oscars_portal_preview_forbidden'     => __( 'Theme editing permission is required to preview.', 'lunara-film' ),
	);
}

/**
 * Human-readable allowlist lookup for validator feedback.
 *
 * The raw code is checked against the allowlist with isset(); it is never
 * key-normalized into a different code before that decision.
 *
 * @param string|null $error_code Optional explicit code.
 * @return string
 */
function lunara_oscars_portal_studio_validation_message( $error_code = null ) {
	$code     = null === $error_code ? get_transient( lunara_oscars_portal_studio_feedback_key() ) : $error_code;
	$code     = is_scalar( $code ) ? (string) $code : '';
	$messages = lunara_oscars_portal_studio_validation_messages();
	return isset( $messages[ $code ] ) ? $messages[ $code ] : __( 'Review the highlighted Oscars Portal fields and try again.', 'lunara-film' );
}

/**
 * Whether the current public request is in the Oscars portal route family.
 *
 * The family is exactly the theme-owned portal: the /oscars/ page and the
 * page-oscars.php template. Plugin-owned ledger routes (aat_* query vars)
 * are deliberately excluded — previews included — so a preview token can
 * never restyle a ledger page.
 *
 * @return bool
 */
function lunara_oscars_portal_studio_is_portal_family_request() {
	if ( function_exists( 'lunara_is_oscars_portal_route' ) ) {
		return lunara_is_oscars_portal_route();
	}
	return is_page( 'oscars' ) || is_page_template( 'page-oscars.php' );
}

/**
 * Mark a response private before touching or validating a preview token.
 */
function lunara_oscars_portal_studio_send_private_no_store() {
	if ( function_exists( 'lunara_site_studio_send_private_no_store' ) ) {
		lunara_site_studio_send_private_no_store();
	} else {
		nocache_headers();
		if ( ! headers_sent() ) {
			header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
			header( 'X-Robots-Tag: noindex, nofollow', true );
		}
	}
	do_action( 'lunara_oscars_portal_preview_no_store_sent' );
}

/**
 * Prepare a testable response decision for a portal preview-query URL.
 *
 * @param bool|null $is_portal_family Optional pre-query route decision.
 * @return array{handled:bool,authorized:bool,status:int,config:array<string,mixed>|false}
 */
function lunara_oscars_portal_studio_prepare_preview_response( $is_portal_family = null ) {
	$is_portal_family = null === $is_portal_family ? lunara_oscars_portal_studio_is_portal_family_request() : (bool) $is_portal_family;
	if ( ! isset( $_GET['lunara_oscars_preview'] ) || ! $is_portal_family ) {
		return array( 'handled' => false, 'authorized' => true, 'status' => 200, 'config' => false );
	}

	// This must happen before capability, token, ownership, hash, or expiry work.
	lunara_oscars_portal_studio_send_private_no_store();
	$token  = sanitize_text_field( wp_unslash( $_GET['lunara_oscars_preview'] ) );
	$config = '' !== $token ? lunara_oscars_portal_studio_get_preview_config( $token ) : false;
	if ( false === $config ) {
		return array( 'handled' => true, 'authorized' => false, 'status' => 403, 'config' => false );
	}
	return array( 'handled' => true, 'authorized' => true, 'status' => 200, 'config' => $config );
}

/**
 * Stop invalid portal preview URLs before any public template can fall back.
 */
function lunara_oscars_portal_studio_guard_preview_request() {
	$response = lunara_oscars_portal_studio_prepare_preview_response();
	if ( empty( $response['handled'] ) || ! empty( $response['authorized'] ) ) {
		return;
	}
	status_header( 403 );
	wp_die(
		esc_html__( 'This private Oscars Portal preview is unavailable or has expired.', 'lunara-film' ),
		esc_html__( 'Oscars Portal Preview', 'lunara-film' ),
		array( 'response' => 403 )
	);
}
add_action( 'template_redirect', 'lunara_oscars_portal_studio_guard_preview_request', 0 );

/**
 * Deny invalid preview tokens before the portal query reaches Studio config.
 *
 * @param WP_Query $query Candidate main query.
 */
function lunara_oscars_portal_studio_preflight_preview_query( $query ) {
	if ( is_admin() || ! isset( $_GET['lunara_oscars_preview'] ) || ! $query->is_main_query() ) {
		return;
	}
	// Pre-query, the family resolves as the /oscars/ page; the page-template
	// variant is finished by the template_redirect guard. Ledger routes are
	// deliberately never part of the family.
	//
	// WP_Query::is_page( 'oscars' ) CANNOT resolve at pre_get_posts — the
	// queried object is only determined once posts are fetched — so the
	// pre-query detection reads the query vars directly: the pagename var,
	// or a page_id var matching the resolved /oscars/ page (get_page_by_path
	// is core-cached). is_page() stays only as a late fallback for a query
	// whose object has already resolved.
	$is_portal_pre_query = 'oscars' === (string) $query->get( 'pagename' );
	if ( ! $is_portal_pre_query ) {
		$page_id = absint( $query->get( 'page_id' ) );
		if ( $page_id > 0 ) {
			$oscars_page         = get_page_by_path( 'oscars' );
			$is_portal_pre_query = $oscars_page && isset( $oscars_page->ID ) && $page_id === (int) $oscars_page->ID;
		}
	}
	if ( ! $is_portal_pre_query && ! $query->is_page( 'oscars' ) ) {
		return;
	}
	$response = lunara_oscars_portal_studio_prepare_preview_response( true );
	if ( ! empty( $response['authorized'] ) ) {
		return;
	}
	status_header( 403 );
	wp_die(
		esc_html__( 'This private Oscars Portal preview is unavailable or has expired.', 'lunara-film' ),
		esc_html__( 'Oscars Portal Preview', 'lunara-film' ),
		array( 'response' => 403 )
	);
}
add_action( 'pre_get_posts', 'lunara_oscars_portal_studio_preflight_preview_query', 1 );

/**
 * Behavior-tested, server-rendered slot composition.
 *
 * Same contract as lunara_journal_archive_studio_render_sections: iterate
 * the saved order, skip slots that are hidden or empty, concatenate the
 * rest. The journal's screen-reader fallback-h1 lane is deliberately not
 * ported — hiding the portal hero has always removed the page h1 with no
 * replacement, and this migration step changes no rendered byte.
 *
 * @param array<string,string> $markup Slot markup.
 * @param array<int,string>    $order Saved order.
 * @param array<string,bool>   $visibility Saved visibility.
 * @return string
 */
function lunara_oscars_portal_render_sections( $markup, $order, $visibility ) {
	$output = '';
	foreach ( is_array( $order ) ? $order : array() as $slug ) {
		if ( empty( $visibility[ $slug ] ) || empty( $markup[ $slug ] ) ) {
			continue;
		}
		$output .= $markup[ $slug ];
	}
	return $output;
}

/**
 * Focused Oscars Portal Studio surface for the Control Desk / Site Studio.
 *
 * @param string $context Rendering context: 'control-desk' or 'site-studio'.
 * @return void
 */
function lunara_control_desk_render_oscars_portal_studio( $context = 'control-desk' ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$config    = lunara_oscars_portal_studio_get_public_config( false );
	$staged    = lunara_oscars_portal_studio_get_invalid_stage();
	$has_stage = is_array( $staged );
	$form      = $has_stage ? array_replace( $config, array_intersect_key( $staged, $config ) ) : $config;
	$revisions = lunara_oscars_portal_studio_get_revisions();
	$notice    = isset( $_GET['lunara_notice'] ) ? sanitize_key( wp_unslash( $_GET['lunara_notice'] ) ) : '';
	$owners    = lunara_oscars_portal_studio_visibility_owners();
	$geometry  = lunara_oscars_portal_studio_geometry_specs();
	$identity  = lunara_oscars_portal_studio_identity_specs();
	$portal_url = home_url( '/oscars/' );

	$slot_labels = array(
		'hero'             => __( 'Hero', 'lunara-film' ),
		'navigator'        => __( 'Navigator', 'lunara-film' ),
		'board'            => __( 'Prediction Board', 'lunara-film' ),
		'doors'            => __( 'Quick Start Doors', 'lunara-film' ),
		'spotlights'       => __( 'Ceremony Spotlights', 'lunara-film' ),
		'titles'           => __( 'Title Cards', 'lunara-film' ),
		'research'         => __( 'Research Layer', 'lunara-film' ),
		'linked-reviews'   => __( 'Linked Reviews', 'lunara-film' ),
		'winners'          => __( 'Latest Winners', 'lunara-film' ),
		'deep-cuts'        => __( 'Deep Cuts', 'lunara-film' ),
		'rotating-winners' => __( 'Rotating Winners', 'lunara-film' ),
	);

	$positions = array();
	foreach ( array_values( $form['section_order'] ) as $index => $slug ) {
		$positions[ $slug ] = $index + 1;
	}
	if ( $has_stage && isset( $staged['_staged_positions'] ) && is_array( $staged['_staged_positions'] ) ) {
		foreach ( $staged['_staged_positions'] as $slug => $position ) {
			if ( isset( $positions[ $slug ] ) ) {
				$positions[ $slug ] = absint( $position );
			}
		}
	}
	?>
	<section class="lunara-control-desk-homepage lunara-oscars-portal-studio" id="lunara-oscars-portal-studio" data-lunara-oscars-portal-studio-context="<?php echo esc_attr( 'site-studio' === $context ? 'site-studio' : 'control-desk' ); ?>">
		<div class="lunara-control-desk-card-head">
			<div>
				<p class="lunara-control-desk-kicker"><?php esc_html_e( 'Oscars Portal Studio', 'lunara-film' ); ?></p>
				<h3><?php esc_html_e( 'Compose the public /oscars/ portal', 'lunara-film' ); ?></h3>
				<p class="lunara-control-desk-subtle"><?php esc_html_e( 'Copy and visibility save through their existing Customizer owners; the slot order and geometry live in the Studio option. Invalid input changes nothing public.', 'lunara-film' ); ?></p>
			</div>
		</div>

		<?php if ( 'oscars_portal_studio_saved' === $notice ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'The Oscars Portal configuration validated and is now public.', 'lunara-film' ); ?></p></div>
		<?php elseif ( 'oscars_portal_studio_restored' === $notice ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'The selected prior public configuration was restored.', 'lunara-film' ); ?></p></div>
		<?php elseif ( 'oscars_portal_studio_invalid' === $notice || 'oscars_portal_studio_restore_invalid' === $notice ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( lunara_oscars_portal_studio_validation_message() ); ?></p></div>
		<?php elseif ( 'oscars_portal_studio_forbidden' === $notice ) : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'Theme editing permission is required to change the Oscars Portal.', 'lunara-film' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="lunara_save_oscars_portal_studio" />
			<?php wp_nonce_field( 'lunara_save_oscars_portal_studio', 'lunara_oscars_portal_nonce' ); ?>
			<?php wp_nonce_field( 'lunara_preview_oscars_portal_studio', 'lunara_oscars_portal_preview_nonce' ); ?>
			<?php if ( 'site-studio' === $context ) : ?>
				<input type="hidden" name="lunara_oscars_portal_return" value="site-studio" />
			<?php endif; ?>

			<div class="lunara-control-desk-homepage-card">
				<div class="lunara-control-desk-card-head"><div><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Portal Language', 'lunara-film' ); ?></p><h3><?php esc_html_e( 'Identity copy', 'lunara-film' ); ?></h3><p class="lunara-control-desk-subtle"><?php esc_html_e( 'Stored in the existing lunara_oscars_portal_* Customizer settings. Leave a field empty to keep its shipped copy.', 'lunara-film' ); ?></p></div></div>
				<div class="lunara-control-desk-field-grid">
					<?php foreach ( $identity as $field => $spec ) : ?>
						<label>
							<span><?php echo esc_html( $spec['label'] ); ?></span>
							<input type="text" name="lunara_oscars_portal_identity[<?php echo esc_attr( $field ); ?>]" maxlength="<?php echo esc_attr( $spec['max'] ); ?>" value="<?php echo esc_attr( isset( $form['identity'][ $field ] ) && is_scalar( $form['identity'][ $field ] ) ? (string) $form['identity'][ $field ] : '' ); ?>" placeholder="<?php echo esc_attr( $spec['default'] ); ?>" />
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="lunara-control-desk-homepage-card">
				<div class="lunara-control-desk-card-head"><div><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Composition', 'lunara-film' ); ?></p><h3><?php esc_html_e( 'Eleven-slot order and visibility', 'lunara-film' ); ?></h3><p class="lunara-control-desk-subtle"><?php esc_html_e( 'Visibility toggles write to the existing lunara_oscars_show_* settings. The navigator follows the Quick Start doors toggle, and the Prediction Board shows whenever published picks exist.', 'lunara-film' ); ?></p></div></div>
				<div class="lunara-control-desk-section-lanes">
					<?php foreach ( lunara_oscars_portal_studio_slots() as $slot ) : $owner = $owners[ $slot ]; ?>
						<div class="lunara-control-desk-section-lane">
							<label>
								<span><?php echo esc_html( sprintf( __( '%s position', 'lunara-film' ), $slot_labels[ $slot ] ) ); ?></span>
								<input type="number" name="lunara_oscars_portal_section_positions[<?php echo esc_attr( $slot ); ?>]" min="1" max="<?php echo esc_attr( count( $slot_labels ) ); ?>" step="1" value="<?php echo esc_attr( isset( $positions[ $slot ] ) ? $positions[ $slot ] : '' ); ?>" required />
							</label>
							<?php if ( '' === $owner['bound'] ) : ?>
								<label class="lunara-control-desk-inline-toggle">
									<input type="checkbox" name="lunara_oscars_portal_section_visibility[<?php echo esc_attr( $slot ); ?>]" value="1" <?php checked( ! empty( $form['section_visibility'][ $slot ] ) ); ?> />
									<span><?php esc_html_e( 'Visible', 'lunara-film' ); ?></span>
								</label>
							<?php elseif ( 'doors' === $owner['bound'] ) : ?>
								<span class="lunara-control-desk-subtle"><?php esc_html_e( 'Follows the Quick Start doors toggle.', 'lunara-film' ); ?></span>
							<?php else : ?>
								<span class="lunara-control-desk-subtle"><?php esc_html_e( 'Content-driven: hides itself when no picks are published.', 'lunara-film' ); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="lunara-control-desk-homepage-card">
				<div class="lunara-control-desk-card-head"><div><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Geometry', 'lunara-film' ); ?></p><h3><?php esc_html_e( 'Bounded spacing and chambers', 'lunara-film' ); ?></h3><p class="lunara-control-desk-subtle"><?php esc_html_e( 'Stamped onto the portal root as custom properties only after an explicit save; an unsaved site keeps its shipped markup untouched.', 'lunara-film' ); ?></p></div></div>
				<div class="lunara-control-desk-homepage-number-grid">
					<?php foreach ( $geometry as $key => $spec ) : ?>
						<label>
							<span><?php echo esc_html( $spec['label'] ); ?></span>
							<input type="number" name="lunara_oscars_portal_number[<?php echo esc_attr( $key ); ?>]" min="<?php echo esc_attr( $spec['min'] ); ?>" max="<?php echo esc_attr( $spec['max'] ); ?>" step="1" value="<?php echo esc_attr( isset( $form['presentation'][ $key ] ) && is_scalar( $form['presentation'][ $key ] ) ? absint( $form['presentation'][ $key ] ) : $spec['default'] ); ?>" required />
							<small><?php echo esc_html( sprintf( __( '%1$d–%2$d, default %3$d', 'lunara-film' ), $spec['min'], $spec['max'], $spec['default'] ) ); ?></small>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="lunara-control-desk-homepage-footer">
				<div><strong><?php esc_html_e( 'Last-valid promotion', 'lunara-film' ); ?></strong><span><?php esc_html_e( 'Invalid input changes nothing public. Preview is private and expires after 30 minutes.', 'lunara-film' ); ?></span></div>
				<div class="lunara-control-desk-actions">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Validate and Save Public Configuration', 'lunara-film' ); ?></button>
					<button type="submit" class="button" name="action" value="lunara_preview_oscars_portal_studio" formtarget="_blank"><?php esc_html_e( 'Preview unsaved desktop + mobile', 'lunara-film' ); ?></button>
					<a class="button" href="<?php echo esc_url( $portal_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open current Portal', 'lunara-film' ); ?></a>
				</div>
			</div>
		</form>

		<div class="lunara-control-desk-homepage-card lunara-oscars-portal-history">
			<div class="lunara-control-desk-card-head"><div><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Configuration History', 'lunara-film' ); ?></p><h3><?php esc_html_e( 'Restore a prior valid public state', 'lunara-film' ); ?></h3><p class="lunara-control-desk-subtle"><?php esc_html_e( 'Up to twelve prior-public snapshots retain who changed the Studio, when, why, and the validator result.', 'lunara-film' ); ?></p></div></div>
			<?php if ( empty( $revisions ) ) : ?><p><?php esc_html_e( 'No Oscars Portal Studio revisions exist yet.', 'lunara-film' ); ?></p><?php else : ?><div class="lunara-oscars-portal-revision-list"><?php foreach ( $revisions as $revision ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="lunara_restore_oscars_portal_studio" /><input type="hidden" name="lunara_oscars_portal_revision_id" value="<?php echo esc_attr( $revision['id'] ); ?>" /><?php wp_nonce_field( 'lunara_restore_oscars_portal_studio', 'lunara_oscars_portal_restore_nonce' ); ?><span><strong><?php echo esc_html( $revision['saved_at'] ); ?></strong><small><?php echo esc_html( sprintf( __( 'User %1$d / %2$s / validator %3$s', 'lunara-film' ), absint( $revision['saved_by'] ), $revision['action'], $revision['validator_result'] ) ); ?></small></span><button type="submit" class="button" onclick="return confirm('<?php echo esc_js( __( 'Restore this prior public Oscars Portal configuration?', 'lunara-film' ) ); ?>');"><?php esc_html_e( 'Restore this revision', 'lunara-film' ); ?></button></form><?php endforeach; ?></div><?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Focused save flow. Validation happens before any public owner is touched.
 */
function lunara_control_desk_save_oscars_portal_studio() {
	$legacy_redirect = admin_url( 'admin.php?page=lunara-site-studio&surface=oscars-portal' );
	$redirect        = function_exists( 'lunara_control_desk_bounded_return_url' )
		? lunara_control_desk_bounded_return_url( 'lunara_oscars_portal_return', 'oscars-portal', $legacy_redirect )
		: $legacy_redirect;
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_safe_redirect( add_query_arg( 'lunara_notice', 'oscars_portal_studio_forbidden', $redirect ) );
		exit;
	}
	check_admin_referer( 'lunara_save_oscars_portal_studio', 'lunara_oscars_portal_nonce' );
	$candidate = lunara_oscars_portal_studio_config_from_request( $_POST );
	$result    = lunara_oscars_portal_studio_promote_config( $candidate, 'save' );
	if ( is_wp_error( $result ) ) {
		lunara_oscars_portal_studio_store_invalid_stage( $candidate, $_POST, $result->get_error_code() );
		$notice = 'oscars_portal_studio_invalid';
	} else {
		lunara_oscars_portal_studio_clear_invalid_stage();
		$notice = 'oscars_portal_studio_saved';
	}
	wp_safe_redirect( add_query_arg( 'lunara_notice', $notice, $redirect ) );
	exit;
}
add_action( 'admin_post_lunara_save_oscars_portal_studio', 'lunara_control_desk_save_oscars_portal_studio' );

/**
 * Restore handler for a selected prior-public snapshot.
 */
function lunara_control_desk_restore_oscars_portal_studio() {
	$redirect = admin_url( 'admin.php?page=lunara-site-studio&surface=oscars-portal' );
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_safe_redirect( add_query_arg( 'lunara_notice', 'oscars_portal_studio_forbidden', $redirect ) );
		exit;
	}
	check_admin_referer( 'lunara_restore_oscars_portal_studio', 'lunara_oscars_portal_restore_nonce' );
	$revision_id = isset( $_POST['lunara_oscars_portal_revision_id'] ) ? sanitize_text_field( wp_unslash( $_POST['lunara_oscars_portal_revision_id'] ) ) : '';
	$result      = lunara_oscars_portal_studio_restore_revision( $revision_id );
	if ( is_wp_error( $result ) ) {
		lunara_oscars_portal_studio_store_feedback( $result->get_error_code() );
		$notice = 'oscars_portal_studio_restore_invalid';
	} else {
		lunara_oscars_portal_studio_clear_invalid_stage();
		$notice = 'oscars_portal_studio_restored';
	}
	wp_safe_redirect( add_query_arg( 'lunara_notice', $notice, $redirect ) );
	exit;
}
add_action( 'admin_post_lunara_restore_oscars_portal_studio', 'lunara_control_desk_restore_oscars_portal_studio' );

/**
 * Private side-by-side preview of an unsaved candidate.
 */
function lunara_control_desk_preview_oscars_portal_studio() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to preview Oscars Portal changes.', 'lunara-film' ) );
	}
	check_admin_referer( 'lunara_preview_oscars_portal_studio', 'lunara_oscars_portal_preview_nonce' );
	$token = lunara_oscars_portal_studio_store_preview( lunara_oscars_portal_studio_config_from_request( $_POST ) );
	if ( is_wp_error( $token ) ) {
		wp_die( esc_html( sprintf( __( 'The preview was not created: %s', 'lunara-film' ), lunara_oscars_portal_studio_validation_message( $token->get_error_code() ) ) ) );
	}
	nocache_headers();
	header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
	$url = add_query_arg( 'lunara_oscars_preview', rawurlencode( $token ), home_url( '/oscars/' ) );
	?><!doctype html>
	<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php esc_html_e( 'Oscars Portal Preview', 'lunara-film' ); ?></title></head>
	<body style="margin:0;background:#151515;color:#fff;font-family:system-ui,sans-serif">
		<header style="padding:18px 24px"><h1 style="margin:0;font-size:20px"><?php esc_html_e( 'Unsaved Oscars Portal preview', 'lunara-film' ); ?></h1><p><?php esc_html_e( 'Private, read-only, and expires in 30 minutes. Nothing below is public yet.', 'lunara-film' ); ?></p></header>
		<div style="display:flex;gap:24px;align-items:flex-start;overflow:auto;padding:0 24px 24px">
			<iframe title="<?php esc_attr_e( 'Desktop Portal preview', 'lunara-film' ); ?>" data-lunara-oscars-preview-frame="desktop" style="width:1440px;height:900px;flex:0 0 1440px;border:1px solid #555;background:#fff" src="<?php echo esc_url( $url ); ?>"></iframe>
			<iframe title="<?php esc_attr_e( 'Mobile Portal preview', 'lunara-film' ); ?>" data-lunara-oscars-preview-frame="mobile" style="width:390px;height:844px;flex:0 0 390px;border:1px solid #555;background:#fff" src="<?php echo esc_url( $url ); ?>"></iframe>
		</div>
	</body></html><?php
	exit;
}
add_action( 'admin_post_lunara_preview_oscars_portal_studio', 'lunara_control_desk_preview_oscars_portal_studio' );
