<?php
/**
 * Focused Reviews Archive Studio public-state, preview, revision, and query layer.
 *
 * Existing identity, section, presentation, lead-pin, featured-image, and
 * Review-post owners remain canonical. This module adds only the bounded
 * editorial data that did not previously have a WordPress owner.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION' ) ) {
	define( 'LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION', 'lunara_reviews_archive_studio_public' );
}
if ( ! defined( 'LUNARA_REVIEWS_ARCHIVE_STUDIO_REVISIONS_OPTION' ) ) {
	define( 'LUNARA_REVIEWS_ARCHIVE_STUDIO_REVISIONS_OPTION', 'lunara_reviews_archive_studio_revisions' );
}
if ( ! defined( 'LUNARA_REVIEWS_ARCHIVE_STUDIO_REVISION_LIMIT' ) ) {
	define( 'LUNARA_REVIEWS_ARCHIVE_STUDIO_REVISION_LIMIT', 12 );
}

/**
 * Complete fallback state. Defaults intentionally reproduce the current
 * hard-coded public Reviews archive output byte-for-byte.
 *
 * @return array<string,mixed>
 */
function lunara_reviews_archive_studio_defaults() {
	return array(
		'schema_version'  => 1,
		'kicker'          => __( 'Criticism Desk', 'lunara-film' ),
		'title'           => __( 'Lunara Reviews', 'lunara-film' ),
		'deck'            => __( 'Spoiler-free criticism, full-spoiler companion files, festival finds, and the films that deserve a longer argument after the credits roll.', 'lunara-film' ),
		'supporting_copy' => '',
		'lead_mode'       => 'automatic',
		'lead_id'         => 0,
		'lane_mode'       => 'query',
		'curated_ids'     => array(),
		'item_count'      => 9,
		'section_order'   => array( 'hero', 'grid', 'pagination', 'pairing-desk' ),
		'section_visibility' => array(
			'hero'         => true,
			'grid'         => true,
			'pagination'   => true,
			'pairing-desk' => true,
		),
		'labels' => array(
			'debrief_kicker'      => __( 'Reviews Command', 'lunara-film' ),
			'debrief_depth'       => __( 'Archive Depth', 'lunara-film' ),
			'debrief_visible'     => __( 'Visible File', 'lunara-film' ),
			'debrief_latest'      => __( 'Latest Update', 'lunara-film' ),
			'debrief_order'       => __( 'Current Order', 'lunara-film' ),
			'hero_action_run'     => __( 'Browse The Run', 'lunara-film' ),
			'hero_action_oscars'  => __( 'Oscar Ledger', 'lunara-film' ),
			'hero_action_journal' => __( 'Journal Desk', 'lunara-film' ),
			'toolbar_kicker'      => __( 'Review Order', 'lunara-film' ),
			'toolbar_title'       => __( 'Release timeline or real editing activity', 'lunara-film' ),
			'sort_label'          => __( 'Sort by', 'lunara-film' ),
			'sort_release_desc'   => __( 'Newest Release', 'lunara-film' ),
			'sort_release_asc'    => __( 'Oldest Release', 'lunara-film' ),
			'sort_modified_desc'  => __( 'Recently Updated', 'lunara-film' ),
			'year_label'          => __( 'Release year', 'lunara-film' ),
			'year_all'            => __( 'All years', 'lunara-film' ),
			'year_filter'         => __( 'Filter', 'lunara-film' ),
			'support_kicker'      => __( 'On The Desk', 'lunara-film' ),
			'support_title'       => __( 'Current companion files.', 'lunara-film' ),
			'run_kicker'          => __( 'Criticism Run', 'lunara-film' ),
			'run_title'           => __( 'The archive keeps moving.', 'lunara-film' ),
			'retention_kicker'    => __( 'Keep Moving', 'lunara-film' ),
			'retention_title'     => __( 'More routes through the desk.', 'lunara-film' ),
			'retention_copy'      => __( 'Follow the latest updates, jump into the Journal, or cross-reference the Oscar Ledger.', 'lunara-film' ),
			'empty_title'         => __( 'No reviews yet.', 'lunara-film' ),
			'empty_copy'          => __( 'When new criticism is published, it will appear here automatically.', 'lunara-film' ),
			'pagination_prev'     => __( '&laquo; Previous', 'lunara-film' ),
			'pagination_next'     => __( 'Next &raquo;', 'lunara-film' ),
		),
		'gallery' => array(
			'kicker' => __( 'Visual File', 'lunara-film' ),
			'title'  => __( 'From the Reviews desk', 'lunara-film' ),
			'copy'   => '',
			'items'  => array(),
		),
		'retention' => array(
			array(
				'visible'          => true,
				'order'            => 1,
				'label'            => __( 'Recently Updated', 'lunara-film' ),
				'destination'      => 'latest',
				'url'              => '',
				'image_id'         => 0,
				'image_alt'        => '',
				'image_credit'     => '',
				'image_source'     => '',
				'image_source_url' => '',
				'focal_x'          => 50,
				'focal_y'          => 50,
			),
			array(
				'visible'          => true,
				'order'            => 2,
				'label'            => __( 'Journal Desk', 'lunara-film' ),
				'destination'      => 'journal',
				'url'              => '',
				'image_id'         => 0,
				'image_alt'        => '',
				'image_credit'     => '',
				'image_source'     => '',
				'image_source_url' => '',
				'focal_x'          => 50,
				'focal_y'          => 50,
			),
			array(
				'visible'          => true,
				'order'            => 3,
				'label'            => __( 'Oscar Ledger', 'lunara-film' ),
				'destination'      => 'oscars',
				'url'              => '',
				'image_id'         => 0,
				'image_alt'        => '',
				'image_credit'     => '',
				'image_source'     => '',
				'image_source_url' => '',
				'focal_x'          => 50,
				'focal_y'          => 50,
			),
		),
		'presentation' => array(
			'density'             => 'editorial',
			'lead_prominence'     => 'standard',
			'rail_density'        => 'editorial',
			'section_gap'         => 40,
			'lead_min_height'     => 460,
			'card_min_height'     => 360,
			'compact_media_width' => 116,
		),
	);
}

/**
 * Repair a raw comma-separated lane order to the canonical four-lane set.
 *
 * The existing registry sanitizer stays the canonical order owner; this
 * wrapper only adapts its comma string into the Studio's array shape and
 * keeps a fail-closed local fallback for isolated runtime harnesses.
 *
 * @param string $raw Raw comma-separated theme mod.
 * @return array<int,string>
 */
function lunara_reviews_archive_studio_expand_section_order( $raw ) {
	$required = lunara_reviews_archive_studio_defaults()['section_order'];

	if ( function_exists( 'lunara_sanitize_reviews_archive_section_order' ) ) {
		$parts = explode( ',', (string) lunara_sanitize_reviews_archive_section_order( (string) $raw ) );
	} else {
		$parts = explode( ',', strtolower( (string) $raw ) );
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
 * Read only the new option-owned fields over the stable fallbacks.
 *
 * @return array<string,mixed>
 */
function lunara_reviews_archive_studio_get_new_fields() {
	$stored = get_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION, array() );
	return is_array( $stored ) ? $stored : array();
}

/**
 * Return a scalar owner value or its safe field-local fallback.
 *
 * @param mixed $value Candidate value.
 * @param mixed $fallback Scalar fallback.
 * @return mixed
 */
function lunara_reviews_archive_studio_scalar_or( $value, $fallback ) {
	return is_scalar( $value ) ? $value : $fallback;
}

/**
 * Normalize corrupt nested public families without discarding valid siblings.
 *
 * Strict save/preview/restore validation still rejects malformed input. This
 * defensive pass is only for previously stored or legacy public owners, where
 * a scalar in place of an array must never fatal the Reviews route.
 *
 * @param mixed               $config Composite public owner data.
 * @param array<string,mixed> $defaults Optional validated defaults.
 * @return array<string,mixed>
 */
function lunara_reviews_archive_studio_normalize_public_shape( $config, $defaults = array() ) {
	$defaults = is_array( $defaults ) && ! empty( $defaults ) ? $defaults : lunara_reviews_archive_studio_defaults();
	$config   = is_array( $config ) ? array_replace( $defaults, $config ) : $defaults;

	foreach ( array( 'schema_version', 'kicker', 'title', 'deck', 'supporting_copy', 'lead_mode', 'lead_id', 'lane_mode', 'item_count' ) as $field ) {
		$config[ $field ] = lunara_reviews_archive_studio_scalar_or( $config[ $field ], $defaults[ $field ] );
	}

	foreach ( array( 'labels', 'section_visibility', 'presentation' ) as $family ) {
		if ( ! isset( $config[ $family ] ) || ! is_array( $config[ $family ] ) ) {
			$config[ $family ] = $defaults[ $family ];
			continue;
		}
		$raw_family = $config[ $family ];
		$config[ $family ] = $defaults[ $family ];
		foreach ( $defaults[ $family ] as $field => $fallback ) {
			if ( array_key_exists( $field, $raw_family ) ) {
				$config[ $family ][ $field ] = lunara_reviews_archive_studio_scalar_or( $raw_family[ $field ], $fallback );
			}
		}
	}

	$section_order = isset( $config['section_order'] ) && is_array( $config['section_order'] ) ? array_values( $config['section_order'] ) : array();
	$config['section_order'] = count( $section_order ) === count( array_filter( $section_order, 'is_scalar' ) )
		? $section_order
		: $defaults['section_order'];
	$config['curated_ids'] = isset( $config['curated_ids'] ) && is_array( $config['curated_ids'] )
		? array_values( array_filter( $config['curated_ids'], 'is_scalar' ) )
		: array();

	if ( ! isset( $config['gallery'] ) || ! is_array( $config['gallery'] ) ) {
		$config['gallery'] = $defaults['gallery'];
	} else {
		$raw_gallery = $config['gallery'];
		$config['gallery'] = $defaults['gallery'];
		foreach ( array( 'kicker', 'title', 'copy' ) as $field ) {
			if ( array_key_exists( $field, $raw_gallery ) ) {
				$config['gallery'][ $field ] = lunara_reviews_archive_studio_scalar_or( $raw_gallery[ $field ], $defaults['gallery'][ $field ] );
			}
		}
		$item_defaults = array( 'order' => 0, 'attachment_id' => 0, 'alt' => '', 'caption' => '', 'link_url' => '', 'credit' => '', 'source' => '', 'source_url' => '', 'focal_x' => 50, 'focal_y' => 50 );
		$items = array();
		$raw_items = isset( $raw_gallery['items'] ) && is_array( $raw_gallery['items'] ) ? $raw_gallery['items'] : array();
		foreach ( $raw_items as $raw_item ) {
			if ( ! is_array( $raw_item ) ) {
				continue;
			}
			$item = array_replace( $item_defaults, $raw_item );
			if ( ! is_scalar( $item['order'] ) || ! is_scalar( $item['attachment_id'] ) ) {
				continue;
			}
			$required_scalar = true;
			foreach ( array( 'alt', 'credit', 'source', 'source_url' ) as $field ) {
				if ( ! is_scalar( $item[ $field ] ) ) {
					$required_scalar = false;
					break;
				}
			}
			if ( ! $required_scalar ) {
				continue;
			}
			foreach ( $item_defaults as $field => $fallback ) {
				$item[ $field ] = lunara_reviews_archive_studio_scalar_or( $item[ $field ], $fallback );
			}
			$items[] = $item;
		}
		$config['gallery']['items'] = $items;
	}

	if ( ! isset( $config['retention'] ) || ! is_array( $config['retention'] ) ) {
		$config['retention'] = $defaults['retention'];
	} else {
		$retention = array();
		for ( $index = 0; $index < 3; $index++ ) {
			$raw_card = isset( $config['retention'][ $index ] ) && is_array( $config['retention'][ $index ] ) ? $config['retention'][ $index ] : array();
			$retention[ $index ] = $defaults['retention'][ $index ];
			foreach ( $defaults['retention'][ $index ] as $field => $fallback ) {
				if ( array_key_exists( $field, $raw_card ) ) {
					$retention[ $index ][ $field ] = lunara_reviews_archive_studio_scalar_or( $raw_card[ $field ], $fallback );
				}
			}
		}
		$config['retention'] = $retention;
	}

	return $config;
}

/**
 * Repair only the malformed owner family in legacy/corrupt public data.
 *
 * Promotion and restore remain strict. Public resolution is defensive because
 * an old Customizer value can become invalid after bounds or registries evolve;
 * one bad geometry value must never erase unrelated editorial copy or curation.
 *
 * @param array<string,mixed> $config Composite owner data.
 * @param array<string,mixed> $defaults Valid defaults.
 * @return array<string,mixed>|WP_Error
 */
function lunara_reviews_archive_studio_repair_public_config( $config, $defaults ) {
	for ( $attempt = 0; $attempt < 12; $attempt++ ) {
		$validated = lunara_reviews_archive_studio_validate_config( $config );
		if ( ! is_wp_error( $validated ) ) {
			return $validated;
		}

		$code = $validated->get_error_code();
		switch ( $code ) {
			case 'reviews_archive_identity_required':
				if ( '' === trim( (string) $config['kicker'] ) ) {
					$config['kicker'] = $defaults['kicker'];
				}
				if ( '' === trim( (string) $config['title'] ) ) {
					$config['title'] = $defaults['title'];
				}
				break;

			case 'reviews_archive_item_count_invalid':
				$config['item_count'] = $defaults['item_count'];
				break;

			case 'reviews_archive_lead_mode_invalid':
			case 'reviews_archive_lead_invalid':
				$config['lead_mode'] = 'automatic';
				$config['lead_id']   = 0;
				break;

			case 'reviews_archive_lane_mode_invalid':
			case 'reviews_archive_curated_post_invalid':
			case 'reviews_archive_curated_duplicate':
			case 'reviews_archive_curated_count_invalid':
				$config['lane_mode']   = 'query';
				$config['curated_ids'] = array();
				break;

			case 'reviews_archive_section_order_invalid':
				$config['section_order'] = lunara_reviews_archive_studio_expand_section_order( implode( ',', array_filter( (array) $config['section_order'], 'is_scalar' ) ) );
				break;

			case 'reviews_archive_primary_sections_hidden':
				$config['section_visibility']['hero'] = true;
				break;

			case 'reviews_archive_label_required':
				$config['labels'] = is_array( $config['labels'] ) ? $config['labels'] : array();
				foreach ( $defaults['labels'] as $key => $fallback ) {
					if ( '' === trim( (string) ( isset( $config['labels'][ $key ] ) ? $config['labels'][ $key ] : '' ) ) ) {
						$config['labels'][ $key ] = $fallback;
					}
				}
				break;

			case 'reviews_archive_gallery_copy_required':
			case 'reviews_archive_gallery_count_invalid':
			case 'reviews_archive_gallery_order_invalid':
			case 'reviews_archive_gallery_image_invalid':
			case 'reviews_archive_gallery_duplicate':
			case 'reviews_archive_gallery_provenance_required':
			case 'reviews_archive_gallery_source_invalid':
			case 'reviews_archive_gallery_link_invalid':
				$config['gallery'] = $defaults['gallery'];
				break;

			case 'reviews_archive_retention_order_invalid':
			case 'reviews_archive_retention_copy_required':
			case 'reviews_archive_retention_destination_invalid':
			case 'reviews_archive_retention_url_invalid':
			case 'reviews_archive_retention_image_invalid':
			case 'reviews_archive_retention_image_provenance_required':
			case 'reviews_archive_retention_image_source_invalid':
				$config['retention'] = $defaults['retention'];
				break;

			case 'reviews_archive_presentation_invalid':
				$config['presentation'] = is_array( $config['presentation'] ) ? $config['presentation'] : array();
				// Enum candidates stay raw here: each is checked against the
				// allowlist with isset() and repaired to its default on any
				// mismatch, never key-normalized into a passing value.
				$allowed = array(
					'density'         => array( 'compact' => true, 'editorial' => true, 'showcase' => true ),
					'lead_prominence' => array( 'restrained' => true, 'standard' => true, 'feature' => true ),
					'rail_density'    => array( 'compact' => true, 'editorial' => true, 'showcase' => true ),
				);
				foreach ( $allowed as $key => $values ) {
					$value = isset( $config['presentation'][ $key ] ) && is_scalar( $config['presentation'][ $key ] ) ? (string) $config['presentation'][ $key ] : '';
					if ( ! isset( $values[ $value ] ) ) {
						$config['presentation'][ $key ] = $defaults['presentation'][ $key ];
					}
				}
				break;

			case 'reviews_archive_geometry_invalid':
				$config['presentation'] = is_array( $config['presentation'] ) ? $config['presentation'] : array();
				$bounds = array(
					'section_gap'         => array( 20, 90 ),
					'lead_min_height'     => array( 340, 640 ),
					'card_min_height'     => array( 260, 540 ),
					'compact_media_width' => array( 92, 150 ),
				);
				foreach ( $bounds as $key => $range ) {
					$value = isset( $config['presentation'][ $key ] ) ? absint( $config['presentation'][ $key ] ) : 0;
					if ( $value < $range[0] || $value > $range[1] ) {
						$config['presentation'][ $key ] = $defaults['presentation'][ $key ];
					}
				}
				break;

			default:
				return $validated;
		}
	}

	return new WP_Error( 'reviews_archive_config_repair_failed' );
}

/**
 * Resolve the current, last-valid public configuration.
 *
 * @param bool $allow_preview Whether a private token may override the request.
 * @return array<string,mixed>
 */
function lunara_reviews_archive_studio_get_public_config( $allow_preview = true ) {
	if ( $allow_preview && isset( $_GET['lunara_reviews_preview'] ) ) {
		$preview = lunara_reviews_archive_studio_get_preview_config( sanitize_text_field( wp_unslash( $_GET['lunara_reviews_preview'] ) ) );
		if ( is_array( $preview ) ) {
			return $preview;
		}
	}

	$cached = wp_cache_get( 'reviews_archive_studio_public', 'lunara' );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$defaults = lunara_reviews_archive_studio_defaults();
	$new      = lunara_reviews_archive_studio_get_new_fields();
	$config   = $defaults;

	foreach ( array( 'schema_version', 'supporting_copy', 'lead_mode', 'lane_mode', 'curated_ids', 'item_count', 'labels', 'gallery', 'retention' ) as $key ) {
		if ( array_key_exists( $key, $new ) ) {
			$config[ $key ] = $new[ $key ];
		}
	}

	// The Editorial Archives theme mods remain the identity owner. An unset or
	// blank mod keeps today's rendered fallback exactly, matching the
	// non-empty-or-default read the public templates already perform.
	$stored_kicker = lunara_reviews_archive_studio_scalar_or( get_theme_mod( 'lunara_reviews_archive_kicker', null ), null );
	$stored_title  = lunara_reviews_archive_studio_scalar_or( get_theme_mod( 'lunara_reviews_archive_title', null ), null );
	$stored_deck   = lunara_reviews_archive_studio_scalar_or( get_theme_mod( 'lunara_reviews_archive_copy', null ), null );
	$config['kicker'] = null === $stored_kicker || false === $stored_kicker || '' === trim( (string) $stored_kicker ) ? $defaults['kicker'] : (string) $stored_kicker;
	$config['title']  = null === $stored_title || false === $stored_title || '' === trim( (string) $stored_title ) ? $defaults['title'] : (string) $stored_title;
	$config['deck']   = null === $stored_deck || false === $stored_deck || '' === trim( (string) $stored_deck ) ? $defaults['deck'] : (string) $stored_deck;

	$raw_order = (string) lunara_reviews_archive_studio_scalar_or( get_theme_mod( 'lunara_reviews_archive_section_order', 'hero,grid,pagination,pairing-desk' ), 'hero,grid,pagination,pairing-desk' );
	$config['section_order'] = lunara_reviews_archive_studio_expand_section_order( $raw_order );

	if ( function_exists( 'lunara_get_reviews_archive_section_registry' ) ) {
		foreach ( lunara_get_reviews_archive_section_registry() as $slug => $spec ) {
			$visibility = get_theme_mod( $spec['setting'], true );
			$config['section_visibility'][ $slug ] = is_scalar( $visibility ) ? (bool) $visibility : true;
		}
	}

	// Presentation candidates stay raw strings here; the repair pass validates
	// them against the Control Desk allowlists and bounds without any
	// key-normalization that could turn corrupt input into a passing value.
	$config['presentation'] = array(
		'density'             => (string) lunara_reviews_archive_studio_scalar_or( get_theme_mod( 'lunara_reviews_archive_density', 'editorial' ), 'editorial' ),
		'lead_prominence'     => (string) lunara_reviews_archive_studio_scalar_or( get_theme_mod( 'lunara_reviews_archive_lead_prominence', 'standard' ), 'standard' ),
		'rail_density'        => (string) lunara_reviews_archive_studio_scalar_or( get_theme_mod( 'lunara_reviews_archive_rail_density', 'editorial' ), 'editorial' ),
		'section_gap'         => absint( lunara_reviews_archive_studio_scalar_or( get_theme_mod( 'lunara_reviews_archive_section_gap', 40 ), 40 ) ),
		'lead_min_height'     => absint( lunara_reviews_archive_studio_scalar_or( get_theme_mod( 'lunara_reviews_archive_lead_min_height', 460 ), 460 ) ),
		'card_min_height'     => absint( lunara_reviews_archive_studio_scalar_or( get_theme_mod( 'lunara_reviews_archive_card_min_height', 360 ), 360 ) ),
		'compact_media_width' => absint( lunara_reviews_archive_studio_scalar_or( get_theme_mod( 'lunara_reviews_archive_compact_media_width', 116 ), 116 ) ),
	);

	// The `_lunara_review_pinned` post meta remains the only lead owner. The
	// option stores no lead ID; the pin's presence is the whole lead state.
	$pinned_id = function_exists( 'lunara_get_pinned_review_id' )
		? lunara_reviews_archive_studio_validate_post_id( lunara_get_pinned_review_id() )
		: 0;
	$config['lead_mode'] = $pinned_id ? 'manual' : 'automatic';
	$config['lead_id']   = $pinned_id;

	$config    = lunara_reviews_archive_studio_normalize_public_shape( $config, $defaults );
	$config    = lunara_reviews_archive_studio_degrade_invalid_references( $config );
	$warnings  = isset( $config['_warnings'] ) && is_array( $config['_warnings'] ) ? $config['_warnings'] : array();
	$validated = lunara_reviews_archive_studio_repair_public_config( $config, $defaults );
	if ( is_wp_error( $validated ) ) {
		// Every validator family is repaired above. Keep this impossible-state
		// fallback valid without pretending the malformed candidate was public.
		$validated = lunara_reviews_archive_studio_validate_config( $defaults );
	}
	$validated['_warnings'] = $warnings;

	wp_cache_set( 'reviews_archive_studio_public', $validated, 'lunara' );

	return $validated;
}

/**
 * Degrade only references whose external WordPress owner later disappeared.
 *
 * @param array<string,mixed> $config Composite config.
 * @return array<string,mixed>
 */
function lunara_reviews_archive_studio_degrade_invalid_references( $config ) {
	$warnings = array();
	if ( 'manual' === $config['lead_mode'] && ! lunara_reviews_archive_studio_validate_post_id( $config['lead_id'] ) ) {
		$config['lead_mode'] = 'automatic';
		$config['lead_id']   = 0;
		$warnings[] = 'manual_lead_unavailable';
	}
	$curated = array();
	foreach ( (array) $config['curated_ids'] as $post_id ) {
		$post_id = lunara_reviews_archive_studio_validate_post_id( $post_id );
		if ( $post_id && ! in_array( $post_id, $curated, true ) ) {
			$curated[] = $post_id;
		} else {
			$warnings[] = 'curated_review_unavailable';
		}
	}
	$config['curated_ids'] = $curated;
	if ( 'curated' === $config['lane_mode'] && empty( $curated ) ) {
		$config['lane_mode'] = 'query';
		$warnings[] = 'curated_lane_fell_back_to_query';
	}
	foreach ( $config['retention'] as $index => $card ) {
		$image_id = absint( isset( $card['image_id'] ) ? $card['image_id'] : 0 );
		if ( $image_id && ! lunara_reviews_archive_studio_validate_attachment_id( $image_id ) ) {
			$config['retention'][ $index ]['image_id'] = 0;
			$warnings[] = 'retention_image_unavailable';
		} elseif ( $image_id && lunara_reviews_archive_studio_attachment_below_wide_target( $image_id ) ) {
			$warnings[] = 'retention_image_wide_quality';
		}
	}
	$gallery_items = array();
	foreach ( (array) $config['gallery']['items'] as $item ) {
		$image_id = absint( isset( $item['attachment_id'] ) ? $item['attachment_id'] : 0 );
		if ( ! $image_id || ! lunara_reviews_archive_studio_validate_attachment_id( $image_id ) ) {
			$warnings[] = 'gallery_image_unavailable';
			continue;
		}
		if ( lunara_reviews_archive_studio_attachment_below_wide_target( $image_id ) ) {
			$warnings[] = 'gallery_image_wide_quality';
		}
		$gallery_items[] = $item;
	}
	foreach ( $gallery_items as $index => $item ) {
		$gallery_items[ $index ]['order'] = $index + 1;
	}
	$config['gallery']['items'] = $gallery_items;
	$config['_warnings'] = array_values( array_unique( $warnings ) );
	return $config;
}

/**
 * Validate a published Review ID.
 *
 * @param mixed $post_id Candidate ID.
 * @return int
 */
function lunara_reviews_archive_studio_validate_post_id( $post_id ) {
	if ( ! is_scalar( $post_id ) ) {
		return 0;
	}
	$post_id = absint( $post_id );
	$post    = $post_id ? get_post( $post_id ) : null;
	return $post && 'review' === $post->post_type && 'publish' === $post->post_status ? $post_id : 0;
}

/**
 * Validate an optional Media Library image.
 *
 * @param mixed $attachment_id Candidate ID.
 * @return int
 */
function lunara_reviews_archive_studio_validate_attachment_id( $attachment_id ) {
	if ( ! is_scalar( $attachment_id ) ) {
		return 0;
	}
	$attachment_id = absint( $attachment_id );
	if ( 0 === $attachment_id ) {
		return 0;
	}
	$attachment = get_post( $attachment_id );
	if ( ! is_object( $attachment ) || 'attachment' !== $attachment->post_type ) {
		return 0;
	}
	return 0 === strpos( (string) get_post_mime_type( $attachment_id ), 'image/' ) ? $attachment_id : 0;
}

/**
 * Flag wide archive media below the 1920x1080 quality target without blocking
 * a valid legacy image from promotion.
 *
 * @param int $attachment_id Image attachment ID.
 * @return bool
 */
function lunara_reviews_archive_studio_attachment_below_wide_target( $attachment_id ) {
	if ( ! is_scalar( $attachment_id ) || ! function_exists( 'wp_get_attachment_metadata' ) ) {
		return false;
	}
	$metadata = wp_get_attachment_metadata( absint( $attachment_id ) );
	if ( ! is_array( $metadata ) || empty( $metadata['width'] ) || empty( $metadata['height'] ) ) {
		return false;
	}
	$width  = is_scalar( $metadata['width'] ) ? absint( $metadata['width'] ) : 0;
	$height = is_scalar( $metadata['height'] ) ? absint( $metadata['height'] ) : 0;
	$ratio  = $height ? $width / $height : 0;
	return $width < 1920 || $height < 1080 || abs( $ratio - ( 16 / 9 ) ) > 0.18;
}

/**
 * Validate a bounded public destination or provenance URL.
 *
 * Public media controls intentionally accept HTTPS URLs only. This prevents a
 * value that merely survives sanitization from becoming a javascript, data,
 * local-network, or otherwise unsafe public link.
 *
 * @param mixed $value Candidate URL.
 * @param bool  $allow_empty Whether an empty value is valid.
 * @return string|false
 */
function lunara_reviews_archive_studio_safe_https_url( $value, $allow_empty = true ) {
	$raw = trim( is_scalar( $value ) ? (string) $value : '' );
	if ( '' === $raw ) {
		return $allow_empty ? '' : false;
	}
	if ( strlen( $raw ) > 2048 ) {
		return false;
	}
	$url = esc_url_raw( $raw );
	if ( '' === $url || 'https' !== strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) || ! wp_parse_url( $url, PHP_URL_HOST ) ) {
		return false;
	}
	if ( function_exists( 'wp_http_validate_url' ) && false === wp_http_validate_url( $url ) ) {
		return false;
	}
	return $url;
}

/**
 * Bounded plain text.
 *
 * @param mixed $value Input.
 * @param int   $max Maximum characters.
 * @return string
 */
function lunara_reviews_archive_studio_text( $value, $max = 180 ) {
	$value = sanitize_text_field( is_scalar( $value ) ? $value : '' );
	return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
}

/**
 * Bounded textarea text.
 *
 * @param mixed $value Input.
 * @param int   $max Maximum characters.
 * @return string
 */
function lunara_reviews_archive_studio_textarea( $value, $max = 700 ) {
	$value = sanitize_textarea_field( is_scalar( $value ) ? $value : '' );
	return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
}

/**
 * Validate a complete staged configuration without mutating public state.
 *
 * Enum and token candidates are validated raw against explicit allowlists;
 * nothing is key-normalized before its allowlist decision.
 *
 * @param mixed $raw Candidate configuration.
 * @return array<string,mixed>|WP_Error
 */
function lunara_reviews_archive_studio_validate_config( $raw ) {
	if ( ! is_array( $raw ) ) {
		return new WP_Error( 'reviews_archive_config_invalid' );
	}

	$defaults = lunara_reviews_archive_studio_defaults();
	$shape_errors = array(
		'section_order'      => 'reviews_archive_section_order_invalid',
		'section_visibility' => 'reviews_archive_config_invalid',
		'labels'             => 'reviews_archive_label_required',
		'gallery'            => 'reviews_archive_gallery_count_invalid',
		'retention'          => 'reviews_archive_retention_order_invalid',
		'presentation'       => 'reviews_archive_presentation_invalid',
	);
	foreach ( $shape_errors as $family => $error_code ) {
		if ( array_key_exists( $family, $raw ) && ! is_array( $raw[ $family ] ) ) {
			return new WP_Error( $error_code );
		}
	}
	if ( isset( $raw['gallery'] ) && is_array( $raw['gallery'] ) && array_key_exists( 'items', $raw['gallery'] ) && ! is_array( $raw['gallery']['items'] ) ) {
		return new WP_Error( 'reviews_archive_gallery_count_invalid' );
	}
	if ( isset( $raw['retention'] ) && is_array( $raw['retention'] ) ) {
		if ( 3 !== count( $raw['retention'] ) ) {
			return new WP_Error( 'reviews_archive_retention_order_invalid' );
		}
		for ( $retention_index = 0; $retention_index < 3; $retention_index++ ) {
			if ( ! isset( $raw['retention'][ $retention_index ] ) || ! is_array( $raw['retention'][ $retention_index ] ) ) {
				return new WP_Error( 'reviews_archive_retention_order_invalid' );
			}
		}
	}

	$top_scalar_errors = array(
		'schema_version'  => 'reviews_archive_config_invalid',
		'kicker'          => 'reviews_archive_identity_required',
		'title'           => 'reviews_archive_identity_required',
		'deck'            => 'reviews_archive_config_invalid',
		'supporting_copy' => 'reviews_archive_config_invalid',
		'lead_mode'       => 'reviews_archive_lead_mode_invalid',
		'lead_id'         => 'reviews_archive_lead_invalid',
		'lane_mode'       => 'reviews_archive_lane_mode_invalid',
		'item_count'      => 'reviews_archive_item_count_invalid',
	);
	foreach ( $top_scalar_errors as $field => $error_code ) {
		if ( array_key_exists( $field, $raw ) && ! is_scalar( $raw[ $field ] ) ) {
			return new WP_Error( $error_code );
		}
	}
	if ( isset( $raw['curated_ids'] ) ) {
		if ( ! is_array( $raw['curated_ids'] ) ) {
			return new WP_Error( 'reviews_archive_curated_count_invalid' );
		}
		foreach ( $raw['curated_ids'] as $post_id ) {
			if ( ! is_scalar( $post_id ) ) {
				return new WP_Error( 'reviews_archive_curated_post_invalid' );
			}
		}
	}
	foreach ( array(
		'section_visibility' => 'reviews_archive_config_invalid',
		'labels'             => 'reviews_archive_label_required',
		'presentation'       => 'reviews_archive_presentation_invalid',
	) as $family => $error_code ) {
		if ( ! isset( $raw[ $family ] ) || ! is_array( $raw[ $family ] ) ) {
			continue;
		}
		foreach ( $raw[ $family ] as $value ) {
			if ( ! is_scalar( $value ) ) {
				return new WP_Error( $error_code );
			}
		}
	}
	if ( isset( $raw['section_order'] ) && is_array( $raw['section_order'] ) ) {
		foreach ( $raw['section_order'] as $slug ) {
			if ( ! is_scalar( $slug ) ) {
				return new WP_Error( 'reviews_archive_section_order_invalid' );
			}
		}
	}
	if ( isset( $raw['gallery'] ) && is_array( $raw['gallery'] ) ) {
		foreach ( array( 'kicker', 'title', 'copy' ) as $field ) {
			if ( array_key_exists( $field, $raw['gallery'] ) && ! is_scalar( $raw['gallery'][ $field ] ) ) {
				return new WP_Error( 'reviews_archive_gallery_copy_required' );
			}
		}
		if ( isset( $raw['gallery']['items'] ) && is_array( $raw['gallery']['items'] ) ) {
			foreach ( $raw['gallery']['items'] as $raw_item ) {
				if ( ! is_array( $raw_item ) ) {
					return new WP_Error( 'reviews_archive_gallery_order_invalid' );
				}
				foreach ( array( 'order', 'attachment_id', 'alt', 'caption', 'link_url', 'credit', 'source', 'source_url', 'focal_x', 'focal_y' ) as $field ) {
					if ( array_key_exists( $field, $raw_item ) && ! is_scalar( $raw_item[ $field ] ) ) {
						return new WP_Error( in_array( $field, array( 'order', 'focal_x', 'focal_y' ), true ) ? 'reviews_archive_gallery_order_invalid' : 'reviews_archive_gallery_provenance_required' );
					}
				}
			}
		}
	}
	if ( isset( $raw['retention'] ) && is_array( $raw['retention'] ) ) {
		foreach ( $raw['retention'] as $raw_card ) {
			foreach ( array( 'visible', 'order', 'label', 'destination', 'url', 'image_id', 'image_alt', 'image_credit', 'image_source', 'image_source_url', 'focal_x', 'focal_y' ) as $field ) {
				if ( array_key_exists( $field, $raw_card ) && ! is_scalar( $raw_card[ $field ] ) ) {
					return new WP_Error( 'destination' === $field ? 'reviews_archive_retention_destination_invalid' : 'reviews_archive_retention_copy_required' );
				}
			}
		}
	}
	$config   = array_replace_recursive( $defaults, $raw );
	$config['schema_version']  = 1;
	$config['kicker']          = lunara_reviews_archive_studio_text( $config['kicker'], 80 );
	$config['title']           = lunara_reviews_archive_studio_text( $config['title'], 140 );
	$config['deck']            = lunara_reviews_archive_studio_textarea( $config['deck'], 600 );
	$config['supporting_copy'] = lunara_reviews_archive_studio_textarea( $config['supporting_copy'], 700 );

	if ( '' === $config['kicker'] || '' === $config['title'] ) {
		return new WP_Error( 'reviews_archive_identity_required' );
	}

	$config['item_count'] = absint( $config['item_count'] );
	if ( $config['item_count'] < 4 || $config['item_count'] > 24 ) {
		return new WP_Error( 'reviews_archive_item_count_invalid' );
	}

	$lead_modes = array( 'automatic' => true, 'manual' => true );
	$lead_mode  = is_scalar( $config['lead_mode'] ) ? (string) $config['lead_mode'] : '';
	if ( ! isset( $lead_modes[ $lead_mode ] ) ) {
		return new WP_Error( 'reviews_archive_lead_mode_invalid' );
	}
	$config['lead_mode'] = $lead_mode;
	$config['lead_id']   = absint( $config['lead_id'] );
	if ( 'manual' === $config['lead_mode'] ) {
		$config['lead_id'] = lunara_reviews_archive_studio_validate_post_id( $config['lead_id'] );
		if ( ! $config['lead_id'] ) {
			return new WP_Error( 'reviews_archive_lead_invalid' );
		}
	} else {
		// Clearing the manual mode must clear its hidden pin state as well.
		$config['lead_id'] = 0;
	}

	$lane_modes = array( 'query' => true, 'curated' => true );
	$lane_mode  = is_scalar( $config['lane_mode'] ) ? (string) $config['lane_mode'] : '';
	if ( ! isset( $lane_modes[ $lane_mode ] ) ) {
		return new WP_Error( 'reviews_archive_lane_mode_invalid' );
	}
	$config['lane_mode'] = $lane_mode;
	$raw_curated = 'curated' === $config['lane_mode'] && is_array( $config['curated_ids'] ) ? $config['curated_ids'] : array();
	$curated     = array();
	foreach ( $raw_curated as $raw_id ) {
		$id = lunara_reviews_archive_studio_validate_post_id( $raw_id );
		if ( ! $id ) {
			return new WP_Error( 'reviews_archive_curated_post_invalid' );
		}
		if ( in_array( $id, $curated, true ) ) {
			return new WP_Error( 'reviews_archive_curated_duplicate' );
		}
		$curated[] = $id;
	}
	if ( count( $curated ) > 24 || ( 'curated' === $config['lane_mode'] && empty( $curated ) ) ) {
		return new WP_Error( 'reviews_archive_curated_count_invalid' );
	}
	$config['curated_ids'] = $curated;

	$required_order = $defaults['section_order'];
	$order          = array();
	foreach ( is_array( $config['section_order'] ) ? array_values( $config['section_order'] ) : array() as $slug ) {
		$order[] = is_scalar( $slug ) ? (string) $slug : '';
	}
	if ( count( $order ) !== count( array_unique( $order ) ) ) {
		return new WP_Error( 'reviews_archive_section_order_invalid' );
	}
	$sorted_order = $order;
	sort( $sorted_order );
	$sorted_required = $required_order;
	sort( $sorted_required );
	if ( $sorted_required !== $sorted_order ) {
		return new WP_Error( 'reviews_archive_section_order_invalid' );
	}
	$config['section_order'] = array_values( $order );

	$visibility = is_array( $config['section_visibility'] ) ? $config['section_visibility'] : array();
	foreach ( $required_order as $slug ) {
		$config['section_visibility'][ $slug ] = ! empty( $visibility[ $slug ] );
	}
	if ( ! $config['section_visibility']['hero'] && ! $config['section_visibility']['grid'] ) {
		return new WP_Error( 'reviews_archive_primary_sections_hidden' );
	}

	foreach ( $defaults['labels'] as $key => $fallback ) {
		$config['labels'][ $key ] = lunara_reviews_archive_studio_text( isset( $config['labels'][ $key ] ) ? $config['labels'][ $key ] : $fallback, 120 );
		if ( '' === $config['labels'][ $key ] ) {
			return new WP_Error( 'reviews_archive_label_required' );
		}
	}

	$raw_gallery = isset( $config['gallery'] ) && is_array( $config['gallery'] ) ? $config['gallery'] : array();
	$gallery     = array_replace( $defaults['gallery'], $raw_gallery );
	$gallery['kicker'] = lunara_reviews_archive_studio_text( $gallery['kicker'], 80 );
	$gallery['title']  = lunara_reviews_archive_studio_text( $gallery['title'], 140 );
	$gallery['copy']   = lunara_reviews_archive_studio_textarea( $gallery['copy'], 500 );
	if ( '' === $gallery['kicker'] || '' === $gallery['title'] ) {
		return new WP_Error( 'reviews_archive_gallery_copy_required' );
	}
	$raw_items = isset( $raw_gallery['items'] ) && is_array( $raw_gallery['items'] ) ? array_values( $raw_gallery['items'] ) : array();
	if ( count( $raw_items ) > 12 ) {
		return new WP_Error( 'reviews_archive_gallery_count_invalid' );
	}
	$gallery_items  = array();
	$gallery_ids    = array();
	$gallery_orders = array();
	$item_defaults  = array( 'order' => 0, 'attachment_id' => 0, 'alt' => '', 'caption' => '', 'link_url' => '', 'credit' => '', 'source' => '', 'source_url' => '', 'focal_x' => 50, 'focal_y' => 50 );
	foreach ( $raw_items as $raw_item ) {
		$item = is_array( $raw_item ) ? array_replace( $item_defaults, $raw_item ) : $item_defaults;
		$item['order'] = absint( $item['order'] );
		if ( $item['order'] < 1 || $item['order'] > count( $raw_items ) || in_array( $item['order'], $gallery_orders, true ) ) {
			return new WP_Error( 'reviews_archive_gallery_order_invalid' );
		}
		$gallery_orders[] = $item['order'];
		$raw_attachment_id     = absint( $item['attachment_id'] );
		$item['attachment_id'] = lunara_reviews_archive_studio_validate_attachment_id( $raw_attachment_id );
		if ( ! $raw_attachment_id || ! $item['attachment_id'] ) {
			return new WP_Error( 'reviews_archive_gallery_image_invalid' );
		}
		if ( in_array( $item['attachment_id'], $gallery_ids, true ) ) {
			return new WP_Error( 'reviews_archive_gallery_duplicate' );
		}
		$gallery_ids[]   = $item['attachment_id'];
		$item['alt']     = lunara_reviews_archive_studio_text( $item['alt'], 180 );
		$item['caption'] = lunara_reviews_archive_studio_textarea( $item['caption'], 360 );
		$item['credit']  = lunara_reviews_archive_studio_text( $item['credit'], 180 );
		$item['source']  = lunara_reviews_archive_studio_text( $item['source'], 180 );
		if ( '' === $item['alt'] || '' === $item['credit'] || '' === $item['source'] || '' === trim( (string) $item['source_url'] ) ) {
			return new WP_Error( 'reviews_archive_gallery_provenance_required' );
		}
		$item['source_url'] = lunara_reviews_archive_studio_safe_https_url( $item['source_url'], false );
		if ( false === $item['source_url'] ) {
			return new WP_Error( 'reviews_archive_gallery_source_invalid' );
		}
		$item['link_url'] = lunara_reviews_archive_studio_safe_https_url( $item['link_url'], true );
		if ( false === $item['link_url'] ) {
			return new WP_Error( 'reviews_archive_gallery_link_invalid' );
		}
		$item['focal_x'] = max( 0, min( 100, absint( $item['focal_x'] ) ) );
		$item['focal_y'] = max( 0, min( 100, absint( $item['focal_y'] ) ) );
		$gallery_items[] = $item;
	}
	usort(
		$gallery_items,
		static function ( $left, $right ) {
			return $left['order'] <=> $right['order'];
		}
	);
	$gallery['items'] = $gallery_items;
	$config['gallery'] = $gallery;

	$retention = array();
	$retention_orders = array();
	$destinations = array( 'latest' => true, 'journal' => true, 'oscars' => true, 'reviews' => true, 'custom' => true );
	for ( $index = 0; $index < 3; $index++ ) {
		$card = isset( $config['retention'][ $index ] ) && is_array( $config['retention'][ $index ] )
			? array_replace( $defaults['retention'][ $index ], $config['retention'][ $index ] )
			: $defaults['retention'][ $index ];
		$card['visible'] = ! empty( $card['visible'] );
		$card['order']   = absint( $card['order'] );
		if ( $card['order'] < 1 || $card['order'] > 3 || in_array( $card['order'], $retention_orders, true ) ) {
			return new WP_Error( 'reviews_archive_retention_order_invalid' );
		}
		$retention_orders[] = $card['order'];
		$card['label'] = lunara_reviews_archive_studio_text( $card['label'], 80 );
		if ( '' === $card['label'] ) {
			return new WP_Error( 'reviews_archive_retention_copy_required' );
		}
		$destination = is_scalar( $card['destination'] ) ? (string) $card['destination'] : '';
		if ( ! isset( $destinations[ $destination ] ) ) {
			return new WP_Error( 'reviews_archive_retention_destination_invalid' );
		}
		$card['destination'] = $destination;
		$card['url'] = lunara_reviews_archive_studio_safe_https_url( $card['url'], 'custom' !== $card['destination'] );
		if ( false === $card['url'] ) {
			return new WP_Error( 'reviews_archive_retention_url_invalid' );
		}
		$raw_image_id     = absint( $card['image_id'] );
		$card['image_id'] = lunara_reviews_archive_studio_validate_attachment_id( $raw_image_id );
		if ( $raw_image_id && ! $card['image_id'] ) {
			return new WP_Error( 'reviews_archive_retention_image_invalid' );
		}
		$card['image_alt']    = lunara_reviews_archive_studio_text( $card['image_alt'], 180 );
		$card['image_credit'] = lunara_reviews_archive_studio_text( $card['image_credit'], 180 );
		$card['image_source'] = lunara_reviews_archive_studio_text( $card['image_source'], 180 );
		if ( $card['image_id'] && ( '' === $card['image_credit'] || '' === $card['image_source'] || '' === trim( (string) $card['image_source_url'] ) ) ) {
			return new WP_Error( 'reviews_archive_retention_image_provenance_required' );
		}
		$card['image_source_url'] = lunara_reviews_archive_studio_safe_https_url( $card['image_source_url'], ! $card['image_id'] );
		if ( false === $card['image_source_url'] ) {
			return new WP_Error( 'reviews_archive_retention_image_source_invalid' );
		}
		$card['focal_x'] = max( 0, min( 100, absint( $card['focal_x'] ) ) );
		$card['focal_y'] = max( 0, min( 100, absint( $card['focal_y'] ) ) );
		$retention[] = $card;
	}
	$config['retention'] = $retention;

	$selects = array(
		'density'         => array( 'compact' => true, 'editorial' => true, 'showcase' => true ),
		'lead_prominence' => array( 'restrained' => true, 'standard' => true, 'feature' => true ),
		'rail_density'    => array( 'compact' => true, 'editorial' => true, 'showcase' => true ),
	);
	foreach ( $selects as $key => $allowed ) {
		$value = is_scalar( $config['presentation'][ $key ] ) ? (string) $config['presentation'][ $key ] : '';
		if ( ! isset( $allowed[ $value ] ) ) {
			return new WP_Error( 'reviews_archive_presentation_invalid' );
		}
		$config['presentation'][ $key ] = $value;
	}
	$numbers = array(
		'section_gap'         => array( 20, 90 ),
		'lead_min_height'     => array( 340, 640 ),
		'card_min_height'     => array( 260, 540 ),
		'compact_media_width' => array( 92, 150 ),
	);
	foreach ( $numbers as $key => $bounds ) {
		$config['presentation'][ $key ] = absint( $config['presentation'][ $key ] );
		if ( $config['presentation'][ $key ] < $bounds[0] || $config['presentation'][ $key ] > $bounds[1] ) {
			return new WP_Error( 'reviews_archive_geometry_invalid' );
		}
	}

	return $config;
}

/**
 * Build a complete candidate from the focused form.
 *
 * @param array<string,mixed> $request Request data.
 * @return array<string,mixed>
 */
function lunara_reviews_archive_studio_config_from_request( $request ) {
	$current = lunara_reviews_archive_studio_get_public_config( false );
	$request = is_array( $request ) ? wp_unslash( $request ) : array();
	$identity = isset( $request['lunara_reviews_archive_identity'] ) && is_array( $request['lunara_reviews_archive_identity'] ) ? $request['lunara_reviews_archive_identity'] : array();
	$labels   = isset( $request['lunara_reviews_archive_labels'] ) && is_array( $request['lunara_reviews_archive_labels'] ) ? $request['lunara_reviews_archive_labels'] : array();
	$current['kicker']          = isset( $identity['kicker'] ) ? $identity['kicker'] : '';
	$current['title']           = isset( $identity['title'] ) ? $identity['title'] : '';
	$current['deck']            = isset( $identity['deck'] ) ? $identity['deck'] : '';
	$current['supporting_copy'] = isset( $identity['supporting_copy'] ) ? $identity['supporting_copy'] : '';
	$current['lead_mode']       = isset( $request['lunara_reviews_archive_lead_mode'] ) ? $request['lunara_reviews_archive_lead_mode'] : 'automatic';
	$current['lead_id']         = isset( $request['lunara_reviews_archive_lead_id'] ) ? $request['lunara_reviews_archive_lead_id'] : 0;
	$current['lane_mode']       = isset( $request['lunara_reviews_archive_lane_mode'] ) ? $request['lunara_reviews_archive_lane_mode'] : 'query';
	$current['curated_ids']     = isset( $request['lunara_reviews_archive_curated_ids'] ) && is_array( $request['lunara_reviews_archive_curated_ids'] ) ? $request['lunara_reviews_archive_curated_ids'] : array();
	$current['item_count']      = isset( $request['lunara_reviews_archive_item_count'] ) ? $request['lunara_reviews_archive_item_count'] : 9;
	$current['labels']          = array_replace( $current['labels'], $labels );
	$current['retention']       = isset( $request['lunara_reviews_archive_retention'] ) && is_array( $request['lunara_reviews_archive_retention'] ) ? $request['lunara_reviews_archive_retention'] : $current['retention'];

	$gallery = isset( $request['lunara_reviews_archive_gallery'] ) && is_array( $request['lunara_reviews_archive_gallery'] ) ? $request['lunara_reviews_archive_gallery'] : array();
	$current['gallery']['kicker'] = isset( $gallery['kicker'] ) ? $gallery['kicker'] : $current['gallery']['kicker'];
	$current['gallery']['title']  = isset( $gallery['title'] ) ? $gallery['title'] : $current['gallery']['title'];
	$current['gallery']['copy']   = isset( $gallery['copy'] ) ? $gallery['copy'] : $current['gallery']['copy'];
	$gallery_ids_valid = ! isset( $request['lunara_reviews_archive_gallery_ids'] ) || is_scalar( $request['lunara_reviews_archive_gallery_ids'] );
	$gallery_ids = isset( $request['lunara_reviews_archive_gallery_ids'] ) && $gallery_ids_valid
		? array_values( array_filter( array_map( 'trim', explode( ',', (string) $request['lunara_reviews_archive_gallery_ids'] ) ), 'strlen' ) )
		: array();
	$gallery_maps = array();
	foreach ( array( 'alt', 'caption', 'link_url', 'credit', 'source', 'source_url', 'focal_x', 'focal_y' ) as $field ) {
		$key = 'lunara_reviews_archive_gallery_' . $field;
		$gallery_maps[ $field ] = isset( $request[ $key ] ) && is_array( $request[ $key ] ) ? $request[ $key ] : array();
	}
	$current['gallery']['items'] = array();
	foreach ( $gallery_ids as $position => $raw_gallery_id ) {
		$gallery_id = is_scalar( $raw_gallery_id ) ? absint( $raw_gallery_id ) : 0;
		$current['gallery']['items'][] = array(
			'order'         => $position + 1,
			'attachment_id' => $gallery_id,
			'alt'           => isset( $gallery_maps['alt'][ $gallery_id ] ) ? $gallery_maps['alt'][ $gallery_id ] : '',
			'caption'       => isset( $gallery_maps['caption'][ $gallery_id ] ) ? $gallery_maps['caption'][ $gallery_id ] : '',
			'link_url'      => isset( $gallery_maps['link_url'][ $gallery_id ] ) ? $gallery_maps['link_url'][ $gallery_id ] : '',
			'credit'        => isset( $gallery_maps['credit'][ $gallery_id ] ) ? $gallery_maps['credit'][ $gallery_id ] : '',
			'source'        => isset( $gallery_maps['source'][ $gallery_id ] ) ? $gallery_maps['source'][ $gallery_id ] : '',
			'source_url'    => isset( $gallery_maps['source_url'][ $gallery_id ] ) ? $gallery_maps['source_url'][ $gallery_id ] : '',
			'focal_x'       => isset( $gallery_maps['focal_x'][ $gallery_id ] ) ? $gallery_maps['focal_x'][ $gallery_id ] : 50,
			'focal_y'       => isset( $gallery_maps['focal_y'][ $gallery_id ] ) ? $gallery_maps['focal_y'][ $gallery_id ] : 50,
		);
	}
	if ( ! $gallery_ids_valid ) {
		$current['gallery']['items'] = 'invalid-gallery-ids';
	}

	$visibility = isset( $request['lunara_reviews_archive_section_visibility'] ) && is_array( $request['lunara_reviews_archive_section_visibility'] ) ? $request['lunara_reviews_archive_section_visibility'] : array();
	foreach ( $current['section_visibility'] as $slug => $enabled ) {
		$current['section_visibility'][ $slug ] = array_key_exists( $slug, $visibility ) ? $visibility[ $slug ] : false;
	}

	$positions = isset( $request['lunara_reviews_archive_section_positions'] ) && is_array( $request['lunara_reviews_archive_section_positions'] ) ? $request['lunara_reviews_archive_section_positions'] : array();
	$positioned = array();
	$position_values = array();
	$positions_valid = true;
	foreach ( $current['section_order'] as $fallback_position => $slug ) {
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

	$raw_selects = isset( $request['lunara_reviews_archive_select'] ) && is_array( $request['lunara_reviews_archive_select'] ) ? $request['lunara_reviews_archive_select'] : array();
	$raw_numbers = isset( $request['lunara_reviews_archive_number'] ) && is_array( $request['lunara_reviews_archive_number'] ) ? $request['lunara_reviews_archive_number'] : array();
	$select_map  = array(
		'lunara_reviews_archive_density'         => 'density',
		'lunara_reviews_archive_lead_prominence' => 'lead_prominence',
		'lunara_reviews_archive_rail_density'    => 'rail_density',
	);
	$number_map = array(
		'lunara_reviews_archive_section_gap'         => 'section_gap',
		'lunara_reviews_archive_lead_min_height'     => 'lead_min_height',
		'lunara_reviews_archive_card_min_height'     => 'card_min_height',
		'lunara_reviews_archive_compact_media_width' => 'compact_media_width',
	);
	foreach ( $select_map as $posted => $key ) {
		if ( isset( $raw_selects[ $posted ] ) ) {
			$current['presentation'][ $key ] = $raw_selects[ $posted ];
		}
	}
	foreach ( $number_map as $posted => $key ) {
		if ( isset( $raw_numbers[ $posted ] ) ) {
			$current['presentation'][ $key ] = $raw_numbers[ $posted ];
		}
	}

	return $current;
}

/**
 * Apply a prevalidated configuration to its canonical WordPress owners.
 *
 * @param array<string,mixed> $config Valid configuration.
 * @return void
 */
function lunara_reviews_archive_studio_apply_config( $config ) {
	set_theme_mod( 'lunara_reviews_archive_kicker', $config['kicker'] );
	set_theme_mod( 'lunara_reviews_archive_title', $config['title'] );
	set_theme_mod( 'lunara_reviews_archive_copy', $config['deck'] );

	$section_order = implode( ',', $config['section_order'] );
	if ( function_exists( 'lunara_sanitize_reviews_archive_section_order' ) ) {
		$section_order = lunara_sanitize_reviews_archive_section_order( $section_order );
	}
	set_theme_mod( 'lunara_reviews_archive_section_order', $section_order );

	if ( function_exists( 'lunara_get_reviews_archive_section_registry' ) ) {
		foreach ( lunara_get_reviews_archive_section_registry() as $slug => $spec ) {
			set_theme_mod( $spec['setting'], ! empty( $config['section_visibility'][ $slug ] ) );
		}
	}

	set_theme_mod( 'lunara_reviews_archive_density', $config['presentation']['density'] );
	set_theme_mod( 'lunara_reviews_archive_lead_prominence', $config['presentation']['lead_prominence'] );
	set_theme_mod( 'lunara_reviews_archive_rail_density', $config['presentation']['rail_density'] );
	set_theme_mod( 'lunara_reviews_archive_section_gap', (string) $config['presentation']['section_gap'] );
	set_theme_mod( 'lunara_reviews_archive_lead_min_height', (string) $config['presentation']['lead_min_height'] );
	set_theme_mod( 'lunara_reviews_archive_card_min_height', (string) $config['presentation']['card_min_height'] );
	set_theme_mod( 'lunara_reviews_archive_compact_media_width', (string) $config['presentation']['compact_media_width'] );

	// The pin meta remains the sole lead owner; zero restores automatic
	// selection through the same canonical helper the editor meta box uses.
	if ( function_exists( 'lunara_set_pinned_review_id' ) ) {
		lunara_set_pinned_review_id( 'manual' === $config['lead_mode'] ? absint( $config['lead_id'] ) : 0 );
	}

	update_option(
		LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION,
		array(
			'schema_version'  => 1,
			'supporting_copy' => $config['supporting_copy'],
			'lead_mode'       => $config['lead_mode'],
			'lane_mode'       => $config['lane_mode'],
			'curated_ids'     => $config['curated_ids'],
			'item_count'      => $config['item_count'],
			'labels'          => $config['labels'],
			'gallery'         => $config['gallery'],
			'retention'       => $config['retention'],
		),
		false
	);
	wp_cache_delete( 'reviews_archive_studio_public', 'lunara' );
}

/**
 * Return newest-first bounded audit history.
 *
 * @return array<int,array<string,mixed>>
 */
function lunara_reviews_archive_studio_get_revisions() {
	$revisions = get_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_REVISIONS_OPTION, array() );
	return is_array( $revisions ) ? array_slice( $revisions, 0, LUNARA_REVIEWS_ARCHIVE_STUDIO_REVISION_LIMIT ) : array();
}

/**
 * Save a prior-public snapshot with audit metadata.
 *
 * @param array<string,mixed> $config Public snapshot.
 * @param string              $action Audit action.
 * @param string              $validator_result Validation result for replacement.
 * @param bool                $prior_public Whether snapshot was public.
 * @return string Revision ID.
 */
function lunara_reviews_archive_studio_push_revision( $config, $action = 'save', $validator_result = 'passed', $prior_public = true ) {
	$revisions = lunara_reviews_archive_studio_get_revisions();
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
	update_option( LUNARA_REVIEWS_ARCHIVE_STUDIO_REVISIONS_OPTION, array_slice( $revisions, 0, LUNARA_REVIEWS_ARCHIVE_STUDIO_REVISION_LIMIT ), false );
	return $id;
}

/**
 * Validate first, then promote without any post/content mutation.
 *
 * @param mixed  $raw Candidate configuration.
 * @param string $action Audit action.
 * @return array<string,mixed>|WP_Error
 */
function lunara_reviews_archive_studio_promote_config( $raw, $action = 'save' ) {
	$validated = lunara_reviews_archive_studio_validate_config( $raw );
	if ( is_wp_error( $validated ) ) {
		return $validated;
	}
	$prior = lunara_reviews_archive_studio_get_public_config( false );
	lunara_reviews_archive_studio_push_revision( $prior, $action, 'passed', true );
	lunara_reviews_archive_studio_apply_config( $validated );
	lunara_reviews_archive_studio_flush_route_cache();
	return $validated;
}

/**
 * Restore a prior valid public snapshot.
 *
 * @param string $revision_id Revision UUID.
 * @return array<string,mixed>|WP_Error
 */
function lunara_reviews_archive_studio_restore_revision( $revision_id ) {
	$revision_id = sanitize_text_field( $revision_id );
	foreach ( lunara_reviews_archive_studio_get_revisions() as $revision ) {
		if ( empty( $revision['id'] ) || ! hash_equals( (string) $revision['id'], $revision_id ) || empty( $revision['prior_public'] ) ) {
			continue;
		}
		$validated = lunara_reviews_archive_studio_validate_config( isset( $revision['config'] ) ? $revision['config'] : array() );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}
		$current = lunara_reviews_archive_studio_get_public_config( false );
		lunara_reviews_archive_studio_push_revision( $current, 'restore', 'passed', true );
		lunara_reviews_archive_studio_apply_config( $validated );
		lunara_reviews_archive_studio_flush_route_cache();
		return $validated;
	}
	return new WP_Error( 'reviews_archive_revision_not_found' );
}

/**
 * Invalidate only the two Reviews archive route consumers.
 *
 * The bounded set is exactly the Review post type archive and the dedicated
 * /reviews/ hub page. `lunara_director` term archives are contractually
 * exempt from all Studio state, so no term URL is ever collected here.
 *
 * @return array<int,string>
 */
function lunara_reviews_archive_studio_cache_urls() {
	$urls = array( get_post_type_archive_link( 'review' ) );

	$reviews_page = function_exists( 'get_page_by_path' ) ? get_page_by_path( 'reviews' ) : null;
	if ( is_object( $reviews_page ) && function_exists( 'get_permalink' ) ) {
		$permalink = get_permalink( $reviews_page );
		if ( $permalink ) {
			$urls[] = $permalink;
		}
	}

	return array_values( array_unique( array_filter( array_map( 'esc_url_raw', $urls ) ) ) );
}

function lunara_reviews_archive_studio_flush_route_cache() {
	$routes = array( '/reviews/' );
	$urls   = lunara_reviews_archive_studio_cache_urls();
	wp_cache_delete( 'reviews_archive_studio_public', 'lunara' );

	// WP Rocket's bounded URL cleaner is used only when already available.
	// Never invoke a domain-wide purge, purge a CDN, or toggle a plugin here.
	if ( function_exists( 'rocket_clean_files' ) && $urls ) {
		rocket_clean_files( $urls );
	}
	do_action( 'lunara_reviews_archive_studio_invalidate_routes', $routes, $urls );
}

/**
 * Time helper kept testable without changing system clocks.
 *
 * @return int
 */
function lunara_reviews_archive_studio_timestamp() {
	return (int) current_time( 'timestamp', true );
}

/**
 * Store a private unsaved preview for thirty minutes.
 *
 * @param mixed $raw Candidate configuration.
 * @return string|WP_Error
 */
function lunara_reviews_archive_studio_store_preview( $raw ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return new WP_Error( 'reviews_archive_preview_forbidden' );
	}
	$validated = lunara_reviews_archive_studio_validate_config( $raw );
	if ( is_wp_error( $validated ) ) {
		return $validated;
	}
	$token   = wp_generate_uuid4();
	$user_id = absint( get_current_user_id() );
	$key     = 'lunara_reviews_archive_preview_' . hash( 'sha256', $token );
	set_transient(
		$key,
		array(
			'user_id'    => $user_id,
			'token_hash' => wp_hash( $token . '|' . $user_id ),
			'expires'    => lunara_reviews_archive_studio_timestamp() + 1800,
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
function lunara_reviews_archive_studio_get_preview_config( $token ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return false;
	}
	$token  = sanitize_text_field( $token );
	$record = get_transient( 'lunara_reviews_archive_preview_' . hash( 'sha256', $token ) );
	if ( ! is_array( $record ) || empty( $record['user_id'] ) || absint( $record['user_id'] ) !== absint( get_current_user_id() ) ) {
		return false;
	}
	$expected = wp_hash( $token . '|' . absint( $record['user_id'] ) );
	if ( empty( $record['token_hash'] ) || ! hash_equals( (string) $record['token_hash'], $expected ) || empty( $record['expires'] ) || absint( $record['expires'] ) <= lunara_reviews_archive_studio_timestamp() ) {
		return false;
	}
	$validated = lunara_reviews_archive_studio_validate_config( isset( $record['config'] ) ? $record['config'] : array() );
	return is_wp_error( $validated ) ? false : $validated;
}

/**
 * Per-user transient key for a rejected, non-public form draft.
 *
 * @return string
 */
function lunara_reviews_archive_studio_invalid_stage_key() {
	return 'lunara_reviews_archive_invalid_' . absint( get_current_user_id() );
}

/**
 * Per-user transient key for bounded validator feedback.
 *
 * @return string
 */
function lunara_reviews_archive_studio_feedback_key() {
	return 'lunara_reviews_archive_feedback_' . absint( get_current_user_id() );
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
function lunara_reviews_archive_studio_bound_invalid_stage( $candidate, $request = array() ) {
	$defaults = lunara_reviews_archive_studio_defaults();
	$stage    = lunara_reviews_archive_studio_normalize_public_shape( array_replace_recursive( $defaults, is_array( $candidate ) ? $candidate : array() ), $defaults );
	$request  = is_array( $request ) ? wp_unslash( $request ) : array();

	$stage['kicker']          = lunara_reviews_archive_studio_text( $stage['kicker'], 80 );
	$stage['title']           = lunara_reviews_archive_studio_text( $stage['title'], 140 );
	$stage['deck']            = lunara_reviews_archive_studio_textarea( $stage['deck'], 600 );
	$stage['supporting_copy'] = lunara_reviews_archive_studio_textarea( $stage['supporting_copy'], 700 );

	// Enum stage fields fold to their defaults on any allowlist mismatch;
	// nothing is key-normalized into a value the validator could then accept.
	$lead_modes = array( 'automatic' => true, 'manual' => true );
	$lead_mode  = is_scalar( $stage['lead_mode'] ) ? (string) $stage['lead_mode'] : '';
	$stage['lead_mode'] = isset( $lead_modes[ $lead_mode ] ) ? $lead_mode : $defaults['lead_mode'];
	$stage['lead_id']   = absint( $stage['lead_id'] );
	$lane_modes = array( 'query' => true, 'curated' => true );
	$lane_mode  = is_scalar( $stage['lane_mode'] ) ? (string) $stage['lane_mode'] : '';
	$stage['lane_mode']   = isset( $lane_modes[ $lane_mode ] ) ? $lane_mode : $defaults['lane_mode'];
	$stage['curated_ids'] = array_slice( array_map( 'absint', is_array( $stage['curated_ids'] ) ? $stage['curated_ids'] : array() ), 0, 24 );
	$stage['item_count']  = min( 999, absint( $stage['item_count'] ) );

	foreach ( $defaults['labels'] as $key => $fallback ) {
		$stage['labels'][ $key ] = lunara_reviews_archive_studio_text( isset( $stage['labels'][ $key ] ) ? $stage['labels'][ $key ] : $fallback, 120 );
	}

	$valid_order = array();
	foreach ( is_array( $stage['section_order'] ) ? array_values( $stage['section_order'] ) : array() as $slug ) {
		$valid_order[] = is_scalar( $slug ) ? (string) $slug : '';
	}
	$required        = $defaults['section_order'];
	$sorted          = $valid_order;
	$sorted_required = $required;
	sort( $sorted );
	sort( $sorted_required );
	if ( count( $valid_order ) !== count( array_unique( $valid_order ) ) || $sorted !== $sorted_required ) {
		$stage['section_order'] = lunara_reviews_archive_studio_get_public_config( false )['section_order'];
	} else {
		$stage['section_order'] = $valid_order;
	}
	$raw_positions = isset( $request['lunara_reviews_archive_section_positions'] ) && is_array( $request['lunara_reviews_archive_section_positions'] )
		? $request['lunara_reviews_archive_section_positions']
		: array();
	$stage['_staged_positions'] = array();
	foreach ( $required as $slug ) {
		$position = isset( $raw_positions[ $slug ] ) && is_scalar( $raw_positions[ $slug ] ) ? absint( $raw_positions[ $slug ] ) : 0;
		if ( $position >= 1 && $position <= count( $required ) ) {
			$stage['_staged_positions'][ $slug ] = $position;
		}
		$stage['section_visibility'][ $slug ] = ! empty( $stage['section_visibility'][ $slug ] );
	}

	$select_allowlists = array(
		'density'         => array( 'compact' => true, 'editorial' => true, 'showcase' => true ),
		'lead_prominence' => array( 'restrained' => true, 'standard' => true, 'feature' => true ),
		'rail_density'    => array( 'compact' => true, 'editorial' => true, 'showcase' => true ),
	);
	foreach ( $select_allowlists as $key => $allowed ) {
		$value = is_scalar( $stage['presentation'][ $key ] ) ? (string) $stage['presentation'][ $key ] : '';
		$stage['presentation'][ $key ] = isset( $allowed[ $value ] ) ? $value : $defaults['presentation'][ $key ];
	}
	foreach ( array( 'section_gap', 'lead_min_height', 'card_min_height', 'compact_media_width' ) as $key ) {
		$stage['presentation'][ $key ] = min( 9999, absint( $stage['presentation'][ $key ] ) );
	}

	$stage['gallery'] = is_array( $stage['gallery'] ) ? array_replace( $defaults['gallery'], $stage['gallery'] ) : $defaults['gallery'];
	$stage['gallery']['kicker'] = lunara_reviews_archive_studio_text( $stage['gallery']['kicker'], 80 );
	$stage['gallery']['title']  = lunara_reviews_archive_studio_text( $stage['gallery']['title'], 140 );
	$stage['gallery']['copy']   = lunara_reviews_archive_studio_textarea( $stage['gallery']['copy'], 500 );
	$stage['gallery']['items']  = is_array( $stage['gallery']['items'] ) ? array_slice( array_values( $stage['gallery']['items'] ), 0, 13 ) : array();
	$gallery_item_defaults = array( 'order' => 0, 'attachment_id' => 0, 'alt' => '', 'caption' => '', 'link_url' => '', 'credit' => '', 'source' => '', 'source_url' => '', 'focal_x' => 50, 'focal_y' => 50 );
	foreach ( $stage['gallery']['items'] as $index => $raw_item ) {
		$item = is_array( $raw_item ) ? array_replace( $gallery_item_defaults, $raw_item ) : $gallery_item_defaults;
		$item['order']         = $index + 1;
		$item['attachment_id'] = absint( $item['attachment_id'] );
		$item['alt']           = lunara_reviews_archive_studio_text( $item['alt'], 180 );
		$item['caption']       = lunara_reviews_archive_studio_textarea( $item['caption'], 360 );
		$item['link_url']      = esc_url_raw( $item['link_url'] );
		$item['credit']        = lunara_reviews_archive_studio_text( $item['credit'], 180 );
		$item['source']        = lunara_reviews_archive_studio_text( $item['source'], 180 );
		$item['source_url']    = esc_url_raw( $item['source_url'] );
		$item['focal_x']       = min( 100, absint( $item['focal_x'] ) );
		$item['focal_y']       = min( 100, absint( $item['focal_y'] ) );
		$stage['gallery']['items'][ $index ] = $item;
	}

	$destinations = array( 'latest' => true, 'journal' => true, 'oscars' => true, 'reviews' => true, 'custom' => true );
	$stage['retention'] = is_array( $stage['retention'] ) ? array_slice( $stage['retention'], 0, 3 ) : $defaults['retention'];
	for ( $index = 0; $index < 3; $index++ ) {
		$card = isset( $stage['retention'][ $index ] ) && is_array( $stage['retention'][ $index ] )
			? array_replace( $defaults['retention'][ $index ], $stage['retention'][ $index ] )
			: $defaults['retention'][ $index ];
		$card['visible'] = ! empty( $card['visible'] );
		$card['order']   = min( 99, absint( $card['order'] ) );
		$card['label']   = lunara_reviews_archive_studio_text( $card['label'], 80 );
		$destination = is_scalar( $card['destination'] ) ? (string) $card['destination'] : '';
		$card['destination']      = isset( $destinations[ $destination ] ) ? $destination : $defaults['retention'][ $index ]['destination'];
		$card['url']              = esc_url_raw( $card['url'] );
		$card['image_id']         = absint( $card['image_id'] );
		$card['image_alt']        = lunara_reviews_archive_studio_text( $card['image_alt'], 180 );
		$card['image_credit']     = lunara_reviews_archive_studio_text( $card['image_credit'], 180 );
		$card['image_source']     = lunara_reviews_archive_studio_text( $card['image_source'], 180 );
		$card['image_source_url'] = esc_url_raw( $card['image_source_url'] );
		$card['focal_x']          = min( 100, absint( $card['focal_x'] ) );
		$card['focal_y']          = min( 100, absint( $card['focal_y'] ) );
		$stage['retention'][ $index ] = $card;
	}
	unset( $stage['_warnings'] );

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
function lunara_reviews_archive_studio_store_invalid_stage( $candidate, $request, $error_code ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	set_transient( lunara_reviews_archive_studio_invalid_stage_key(), lunara_reviews_archive_studio_bound_invalid_stage( $candidate, $request ), 30 * 60 );
	set_transient( lunara_reviews_archive_studio_feedback_key(), lunara_reviews_archive_studio_bound_feedback_code( $error_code ), 30 * 60 );
}

/**
 * Return the current editor's rejected private form draft.
 *
 * @return array<string,mixed>|false
 */
function lunara_reviews_archive_studio_get_invalid_stage() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return false;
	}
	$stage = get_transient( lunara_reviews_archive_studio_invalid_stage_key() );
	return is_array( $stage ) ? $stage : false;
}

/**
 * Clear rejected draft/feedback after a successful state transition.
 */
function lunara_reviews_archive_studio_clear_invalid_stage() {
	delete_transient( lunara_reviews_archive_studio_invalid_stage_key() );
	delete_transient( lunara_reviews_archive_studio_feedback_key() );
}

/**
 * Store restore feedback without replacing a rejected edit draft.
 *
 * @param string $error_code Validator code.
 */
function lunara_reviews_archive_studio_store_feedback( $error_code ) {
	if ( current_user_can( 'edit_theme_options' ) ) {
		set_transient( lunara_reviews_archive_studio_feedback_key(), lunara_reviews_archive_studio_bound_feedback_code( $error_code ), 30 * 60 );
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
function lunara_reviews_archive_studio_bound_feedback_code( $error_code ) {
	$code = is_scalar( $error_code ) ? (string) $error_code : '';
	return array_key_exists( $code, lunara_reviews_archive_studio_validation_messages() ) ? $code : '';
}

/**
 * The complete validator message allowlist; no raw error text ever leaks.
 *
 * Every code the validator, repair pass, restore flow, and preview gate can
 * emit resolves here.
 *
 * @return array<string,string>
 */
function lunara_reviews_archive_studio_validation_messages() {
	return array(
		'reviews_archive_config_invalid'                => __( 'The staged configuration was malformed and changed nothing public.', 'lunara-film' ),
		'reviews_archive_config_repair_failed'          => __( 'The stored public configuration could not be repaired; the defaults remain live.', 'lunara-film' ),
		'reviews_archive_identity_required'             => __( 'Add both the archive kicker and headline.', 'lunara-film' ),
		'reviews_archive_item_count_invalid'            => __( 'Items per page must be between 4 and 24.', 'lunara-film' ),
		'reviews_archive_lead_mode_invalid'             => __( 'Choose a supported lead mode.', 'lunara-film' ),
		'reviews_archive_lead_invalid'                  => __( 'The manual lead must be a published Review.', 'lunara-film' ),
		'reviews_archive_lane_mode_invalid'             => __( 'Choose automatic query or curated selection.', 'lunara-film' ),
		'reviews_archive_curated_post_invalid'          => __( 'Every curated review must still be published.', 'lunara-film' ),
		'reviews_archive_curated_duplicate'             => __( 'Remove the duplicate review from the curated order.', 'lunara-film' ),
		'reviews_archive_curated_count_invalid'         => __( 'A curated run needs 1 to 24 published reviews.', 'lunara-film' ),
		'reviews_archive_section_order_invalid'         => __( 'Give every section one unique position.', 'lunara-film' ),
		'reviews_archive_primary_sections_hidden'       => __( 'Keep either the Hero or Review Grid visible.', 'lunara-film' ),
		'reviews_archive_label_required'                => __( 'Every public-language field needs a value.', 'lunara-film' ),
		'reviews_archive_gallery_copy_required'         => __( 'The archive gallery needs both its kicker and heading.', 'lunara-film' ),
		'reviews_archive_gallery_count_invalid'         => __( 'The archive gallery accepts up to twelve images.', 'lunara-film' ),
		'reviews_archive_gallery_order_invalid'         => __( 'Give every archive gallery image one unique position.', 'lunara-film' ),
		'reviews_archive_gallery_image_invalid'         => __( 'Every gallery choice must be an existing Media Library image.', 'lunara-film' ),
		'reviews_archive_gallery_duplicate'             => __( 'Remove the duplicate image from the archive gallery.', 'lunara-film' ),
		'reviews_archive_gallery_provenance_required'   => __( 'Every gallery image needs useful alt text, credit, source, and source URL.', 'lunara-film' ),
		'reviews_archive_gallery_source_invalid'        => __( 'Every gallery source needs a safe HTTPS URL.', 'lunara-film' ),
		'reviews_archive_gallery_link_invalid'          => __( 'Gallery image destinations must be safe HTTPS URLs.', 'lunara-film' ),
		'reviews_archive_retention_order_invalid'       => __( 'Give the three retention cards unique positions 1 through 3.', 'lunara-film' ),
		'reviews_archive_retention_copy_required'       => __( 'Each retention card needs its label.', 'lunara-film' ),
		'reviews_archive_retention_destination_invalid' => __( 'Choose a supported retention destination.', 'lunara-film' ),
		'reviews_archive_retention_url_invalid'         => __( 'A custom retention destination needs a complete URL.', 'lunara-film' ),
		'reviews_archive_retention_image_invalid'       => __( 'Choose an image attachment from the Media Library.', 'lunara-film' ),
		'reviews_archive_retention_image_provenance_required' => __( 'A retention image needs its credit, source, and source URL.', 'lunara-film' ),
		'reviews_archive_retention_image_source_invalid' => __( 'Retention image sources must use a safe HTTPS URL.', 'lunara-film' ),
		'reviews_archive_presentation_invalid'          => __( 'Choose one of the supported presentation presets.', 'lunara-film' ),
		'reviews_archive_geometry_invalid'              => __( 'One geometry value is outside its displayed bounds.', 'lunara-film' ),
		'reviews_archive_revision_not_found'            => __( 'That revision is no longer available to restore.', 'lunara-film' ),
		'reviews_archive_preview_forbidden'             => __( 'Theme editing permission is required to preview.', 'lunara-film' ),
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
function lunara_reviews_archive_studio_validation_message( $error_code = null ) {
	$code     = null === $error_code ? get_transient( lunara_reviews_archive_studio_feedback_key() ) : $error_code;
	$code     = is_scalar( $code ) ? (string) $code : '';
	$messages = lunara_reviews_archive_studio_validation_messages();
	return isset( $messages[ $code ] ) ? $messages[ $code ] : __( 'Review the highlighted Reviews Archive fields and try again.', 'lunara-film' );
}

/**
 * Whether the current public request is in the Reviews archive route family.
 *
 * The family is the Review post type archive, the dedicated /reviews/ page,
 * and the page-reviews.php template. `lunara_director` term archives are
 * deliberately excluded: director archives are contractually exempt from all
 * Studio state, previews included.
 *
 * @return bool
 */
function lunara_reviews_archive_studio_is_reviews_family_request() {
	return is_post_type_archive( 'review' ) || is_page( 'reviews' ) || is_page_template( 'page-reviews.php' );
}

/**
 * Mark a response private before touching or validating a preview token.
 */
function lunara_reviews_archive_studio_send_private_no_store() {
	nocache_headers();
	if ( ! headers_sent() ) {
		header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
	}
	do_action( 'lunara_reviews_archive_preview_no_store_sent' );
}

/**
 * Prepare a testable response decision for a Reviews preview-query URL.
 *
 * @param bool|null $is_reviews_family Optional pre-query route decision.
 * @return array{handled:bool,authorized:bool,status:int,config:array<string,mixed>|false}
 */
function lunara_reviews_archive_studio_prepare_preview_response( $is_reviews_family = null ) {
	$is_reviews_family = null === $is_reviews_family ? lunara_reviews_archive_studio_is_reviews_family_request() : (bool) $is_reviews_family;
	if ( ! isset( $_GET['lunara_reviews_preview'] ) || ! $is_reviews_family ) {
		return array( 'handled' => false, 'authorized' => true, 'status' => 200, 'config' => false );
	}

	// This must happen before capability, token, ownership, hash, or expiry work.
	lunara_reviews_archive_studio_send_private_no_store();
	$token  = sanitize_text_field( wp_unslash( $_GET['lunara_reviews_preview'] ) );
	$config = '' !== $token ? lunara_reviews_archive_studio_get_preview_config( $token ) : false;
	if ( false === $config ) {
		return array( 'handled' => true, 'authorized' => false, 'status' => 403, 'config' => false );
	}
	return array( 'handled' => true, 'authorized' => true, 'status' => 200, 'config' => $config );
}

/**
 * Stop invalid Reviews preview URLs before any public template can fall back.
 */
function lunara_reviews_archive_studio_guard_preview_request() {
	$response = lunara_reviews_archive_studio_prepare_preview_response();
	if ( empty( $response['handled'] ) || ! empty( $response['authorized'] ) ) {
		return;
	}
	status_header( 403 );
	wp_die(
		esc_html__( 'This private Reviews Archive preview is unavailable or has expired.', 'lunara-film' ),
		esc_html__( 'Reviews Archive Preview', 'lunara-film' ),
		array( 'response' => 403 )
	);
}
add_action( 'template_redirect', 'lunara_reviews_archive_studio_guard_preview_request', 0 );

/**
 * Deny invalid preview tokens before the Reviews query reaches Studio config.
 *
 * @param WP_Query $query Candidate main query.
 */
function lunara_reviews_archive_studio_preflight_preview_query( $query ) {
	if ( is_admin() || ! isset( $_GET['lunara_reviews_preview'] ) || ! $query->is_main_query() ) {
		return;
	}
	// Pre-query, the family resolves as the Review archive or the /reviews/
	// page; the page-template variant is finished by the template_redirect
	// guard. Director term queries are deliberately never part of the family.
	$is_family = $query->is_post_type_archive( 'review' ) || $query->is_page( 'reviews' );
	if ( ! $is_family ) {
		return;
	}
	$response = lunara_reviews_archive_studio_prepare_preview_response( true );
	if ( ! empty( $response['authorized'] ) ) {
		return;
	}
	status_header( 403 );
	wp_die(
		esc_html__( 'This private Reviews Archive preview is unavailable or has expired.', 'lunara-film' ),
		esc_html__( 'Reviews Archive Preview', 'lunara-film' ),
		array( 'response' => 403 )
	);
}
add_action( 'pre_get_posts', 'lunara_reviews_archive_studio_preflight_preview_query', 1 );

/**
 * Private side-by-side preview of an unsaved candidate.
 */
function lunara_control_desk_preview_reviews_archive_studio() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to preview Reviews Archive changes.', 'lunara-film' ) );
	}
	check_admin_referer( 'lunara_preview_reviews_archive_studio', 'lunara_reviews_archive_preview_nonce' );
	$token = lunara_reviews_archive_studio_store_preview( lunara_reviews_archive_studio_config_from_request( $_POST ) );
	if ( is_wp_error( $token ) ) {
		wp_die( esc_html( sprintf( __( 'The preview was not created: %s', 'lunara-film' ), lunara_reviews_archive_studio_validation_message( $token->get_error_code() ) ) ) );
	}
	nocache_headers();
	header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
	$url = add_query_arg( 'lunara_reviews_preview', rawurlencode( $token ), get_post_type_archive_link( 'review' ) );
	?><!doctype html>
	<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php esc_html_e( 'Reviews Archive Preview', 'lunara-film' ); ?></title></head>
	<body style="margin:0;background:#151515;color:#fff;font-family:system-ui,sans-serif">
		<header style="padding:18px 24px"><h1 style="margin:0;font-size:20px"><?php esc_html_e( 'Unsaved Reviews Archive preview', 'lunara-film' ); ?></h1><p><?php esc_html_e( 'Private, read-only, and expires in 30 minutes. Nothing below is public yet.', 'lunara-film' ); ?></p></header>
		<div style="display:flex;gap:24px;align-items:flex-start;overflow:auto;padding:0 24px 24px">
			<iframe title="<?php esc_attr_e( 'Desktop Reviews preview', 'lunara-film' ); ?>" data-lunara-reviews-preview-frame="desktop" style="width:1440px;height:900px;flex:0 0 1440px;border:1px solid #555;background:#fff" src="<?php echo esc_url( $url ); ?>"></iframe>
			<iframe title="<?php esc_attr_e( 'Mobile Reviews preview', 'lunara-film' ); ?>" data-lunara-reviews-preview-frame="mobile" style="width:390px;height:844px;flex:0 0 390px;border:1px solid #555;background:#fff" src="<?php echo esc_url( $url ); ?>"></iframe>
		</div>
	</body></html><?php
	exit;
}
add_action( 'admin_post_lunara_preview_reviews_archive_studio', 'lunara_control_desk_preview_reviews_archive_studio' );
