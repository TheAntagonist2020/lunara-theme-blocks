<?php
/**
 * Focused Journal Archive Studio public-state, preview, revision, and query layer.
 *
 * Existing identity, section, presentation, taxonomy, featured-image, and
 * Journal-post owners remain canonical. This module adds only the bounded
 * editorial data that did not previously have a WordPress owner.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION' ) ) {
	define( 'LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION', 'lunara_journal_archive_studio_public' );
}
if ( ! defined( 'LUNARA_JOURNAL_ARCHIVE_STUDIO_REVISIONS_OPTION' ) ) {
	define( 'LUNARA_JOURNAL_ARCHIVE_STUDIO_REVISIONS_OPTION', 'lunara_journal_archive_studio_revisions' );
}
if ( ! defined( 'LUNARA_JOURNAL_ARCHIVE_STUDIO_REVISION_LIMIT' ) ) {
	define( 'LUNARA_JOURNAL_ARCHIVE_STUDIO_REVISION_LIMIT', 12 );
}

/**
 * Complete fallback state. Defaults intentionally reproduce Theme 3.2.43.
 *
 * @return array<string,mixed>
 */
function lunara_journal_archive_studio_defaults() {
	return array(
		'schema_version'  => 1,
		'kicker'         => __( 'Journal', 'lunara-film' ),
		'title'          => __( 'Lunara Journal', 'lunara-film' ),
		'deck'           => '',
		'supporting_copy' => '',
		'lead_mode'      => 'shared',
		'lead_id'        => 0,
		'lane_mode'      => 'query',
		'curated_ids'    => array(),
		'item_count'     => 8,
		'filter_caps'    => array(
			'journal_section' => 8,
			'journal_topic'   => 10,
			'journal_type'    => 8,
		),
		'section_order'  => array( 'hero', 'deskbar', 'filters', 'toolbar', 'grid', 'retention', 'pagination' ),
		'section_visibility' => array(
			'hero'       => true,
			'deskbar'    => true,
			'filters'    => true,
			'toolbar'    => true,
			'grid'       => true,
			'retention'  => true,
			'pagination' => true,
		),
		'labels' => array(
			'desk_count'       => __( 'On the desk:', 'lunara-film' ),
			'desk_latest'      => __( 'Latest file:', 'lunara-film' ),
			'desk_mix'         => __( 'Desk mix:', 'lunara-film' ),
			'file_singular'     => __( 'file', 'lunara-film' ),
			'file_plural'       => __( 'files', 'lunara-film' ),
			'lane_singular'     => __( 'lane', 'lunara-film' ),
			'lane_plural'       => __( 'lanes', 'lunara-film' ),
			'filter_sections'   => __( 'Sections', 'lunara-film' ),
			'filter_types'      => __( 'Types', 'lunara-film' ),
			'filter_topics'     => __( 'Topics', 'lunara-film' ),
			'filter_archive_types' => __( 'Archive Types', 'lunara-film' ),
			'taxonomy_section_kicker' => __( 'Journal Section', 'lunara-film' ),
			'taxonomy_topic_kicker'   => __( 'Journal Topic', 'lunara-film' ),
			'taxonomy_type_kicker'    => __( 'Journal Type', 'lunara-film' ),
			'filter_all'       => __( 'All', 'lunara-film' ),
			'toolbar_kicker'   => __( 'Desk Order', 'lunara-film' ),
			'toolbar_title'    => __( 'Latest files from the desk', 'lunara-film' ),
			'sort_newest'      => __( 'Newest Filed', 'lunara-film' ),
			'sort_oldest'      => __( 'Oldest Filed', 'lunara-film' ),
			'sort_updated'     => __( 'Recently Updated', 'lunara-film' ),
			'lead_kicker'      => __( 'Lead file', 'lunara-film' ),
			'card_kicker'      => __( 'From the desk', 'lunara-film' ),
			'card_cta'         => __( 'Read file', 'lunara-film' ),
			'retention_kicker' => __( 'Desk Channels', 'lunara-film' ),
			'retention_title'  => __( 'Keep the file moving', 'lunara-film' ),
			'pagination_prev'  => __( '← Newer', 'lunara-film' ),
			'pagination_next'  => __( 'Older →', 'lunara-film' ),
			'empty_copy'       => __( 'No journal entries yet. Check back soon.', 'lunara-film' ),
		),
		'gallery' => array(
			'kicker' => __( 'Visual File', 'lunara-film' ),
			'title'  => __( 'From the Journal desk', 'lunara-film' ),
			'copy'   => '',
			'items'  => array(),
		),
		'retention' => array(
			array(
				'visible'          => true,
				'order'            => 1,
				'label'            => __( 'Latest File', 'lunara-film' ),
				'title'            => __( 'Open the newest desk entry', 'lunara-film' ),
				'copy'             => __( 'Stay with the freshest reported movement before it settles into the wider conversation.', 'lunara-film' ),
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
				'label'            => __( 'Trailer Lane', 'lunara-film' ),
				'title'            => __( 'Watch what just moved', 'lunara-film' ),
				'copy'             => __( 'Jump to trailer-backed files where the image, hook, and industry signal belong together.', 'lunara-film' ),
				'destination'      => 'trailer',
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
				'label'            => __( 'Review Desk', 'lunara-film' ),
				'title'            => __( 'Move into the criticism', 'lunara-film' ),
				'copy'             => __( 'Follow the conversation from quick dispatches into full Lunara reviews and context.', 'lunara-film' ),
				'destination'      => 'reviews',
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
			'density'         => 'editorial',
			'lead_prominence' => 'standard',
			'desk_rhythm'     => 'balanced',
			'section_gap'     => 38,
			'hero_min_height' => 240,
			'card_min_height' => 390,
			'media_min_height' => 220,
		),
	);
}

/**
 * Expand the legacy four-lane order without changing its relative choices.
 *
 * @param string $raw Raw comma-separated theme mod.
 * @return array<int,string>
 */
function lunara_journal_archive_studio_expand_section_order( $raw ) {
	$defaults = lunara_journal_archive_studio_defaults()['section_order'];
	$allowed  = array_fill_keys( $defaults, true );
	$parts    = array_filter( array_map( 'sanitize_key', explode( ',', (string) $raw ) ) );
	$ordered  = array();

	foreach ( $parts as $slug ) {
		if ( ! isset( $allowed[ $slug ] ) || in_array( $slug, $ordered, true ) ) {
			continue;
		}
		$ordered[] = $slug;
		if ( 'hero' === $slug && ! in_array( 'deskbar', $parts, true ) ) {
			$ordered[] = 'deskbar';
		}
		if ( 'filters' === $slug && ! in_array( 'toolbar', $parts, true ) ) {
			$ordered[] = 'toolbar';
		}
		if ( 'grid' === $slug && ! in_array( 'retention', $parts, true ) ) {
			$ordered[] = 'retention';
		}
	}

	foreach ( $defaults as $slug ) {
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
function lunara_journal_archive_studio_get_new_fields() {
	$stored = get_option( LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION, array() );
	return is_array( $stored ) ? $stored : array();
}

/**
 * Return a scalar owner value or its safe field-local fallback.
 *
 * @param mixed $value Candidate value.
 * @param mixed $fallback Scalar fallback.
 * @return mixed
 */
function lunara_journal_archive_studio_scalar_or( $value, $fallback ) {
	return is_scalar( $value ) ? $value : $fallback;
}

/**
 * Normalize corrupt nested public families without discarding valid siblings.
 *
 * Strict save/preview/restore validation still rejects malformed input. This
 * defensive pass is only for previously stored or legacy public owners, where
 * a scalar in place of an array must never fatal the Journal route.
 *
 * @param mixed               $config Composite public owner data.
 * @param array<string,mixed> $defaults Optional validated defaults.
 * @return array<string,mixed>
 */
function lunara_journal_archive_studio_normalize_public_shape( $config, $defaults = array() ) {
	$defaults = is_array( $defaults ) && ! empty( $defaults ) ? $defaults : lunara_journal_archive_studio_defaults();
	$config   = is_array( $config ) ? array_replace( $defaults, $config ) : $defaults;

	foreach ( array( 'schema_version', 'kicker', 'title', 'deck', 'supporting_copy', 'lead_mode', 'lead_id', 'lane_mode', 'item_count' ) as $field ) {
		$config[ $field ] = lunara_journal_archive_studio_scalar_or( $config[ $field ], $defaults[ $field ] );
	}

	foreach ( array( 'filter_caps', 'labels', 'section_visibility', 'presentation' ) as $family ) {
		if ( ! isset( $config[ $family ] ) || ! is_array( $config[ $family ] ) ) {
			$config[ $family ] = $defaults[ $family ];
			continue;
		}
		$raw_family = $config[ $family ];
		$config[ $family ] = $defaults[ $family ];
		foreach ( $defaults[ $family ] as $field => $fallback ) {
			if ( array_key_exists( $field, $raw_family ) ) {
				$config[ $family ][ $field ] = lunara_journal_archive_studio_scalar_or( $raw_family[ $field ], $fallback );
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
				$config['gallery'][ $field ] = lunara_journal_archive_studio_scalar_or( $raw_gallery[ $field ], $defaults['gallery'][ $field ] );
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
				$item[ $field ] = lunara_journal_archive_studio_scalar_or( $item[ $field ], $fallback );
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
					$retention[ $index ][ $field ] = lunara_journal_archive_studio_scalar_or( $raw_card[ $field ], $fallback );
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
function lunara_journal_archive_studio_repair_public_config( $config, $defaults ) {
	for ( $attempt = 0; $attempt < 12; $attempt++ ) {
		$validated = lunara_journal_archive_studio_validate_config( $config );
		if ( ! is_wp_error( $validated ) ) {
			return $validated;
		}

		$code = $validated->get_error_code();
		switch ( $code ) {
			case 'journal_archive_identity_required':
				if ( '' === trim( (string) $config['kicker'] ) ) {
					$config['kicker'] = $defaults['kicker'];
				}
				if ( '' === trim( (string) $config['title'] ) ) {
					$config['title'] = $defaults['title'];
				}
				break;

			case 'journal_archive_item_count_invalid':
				$config['item_count'] = $defaults['item_count'];
				break;

			case 'journal_archive_filter_cap_invalid':
				$config['filter_caps'] = is_array( $config['filter_caps'] ) ? $config['filter_caps'] : array();
				foreach ( $defaults['filter_caps'] as $taxonomy => $fallback ) {
					$value = isset( $config['filter_caps'][ $taxonomy ] ) ? absint( $config['filter_caps'][ $taxonomy ] ) : 0;
					if ( $value < 1 || $value > 20 ) {
						$config['filter_caps'][ $taxonomy ] = $fallback;
					}
				}
				break;

			case 'journal_archive_lead_mode_invalid':
			case 'journal_archive_lead_invalid':
				$config['lead_mode'] = 'shared';
				$config['lead_id']   = 0;
				break;

			case 'journal_archive_lane_mode_invalid':
			case 'journal_archive_curated_post_invalid':
			case 'journal_archive_curated_duplicate':
			case 'journal_archive_curated_count_invalid':
				$config['lane_mode']   = 'query';
				$config['curated_ids'] = array();
				break;

			case 'journal_archive_section_order_invalid':
				$config['section_order'] = lunara_journal_archive_studio_expand_section_order( implode( ',', (array) $config['section_order'] ) );
				break;

			case 'journal_archive_primary_sections_hidden':
				$config['section_visibility']['hero'] = true;
				break;

			case 'journal_archive_label_required':
				$config['labels'] = is_array( $config['labels'] ) ? $config['labels'] : array();
				foreach ( $defaults['labels'] as $key => $fallback ) {
					if ( '' === trim( (string) ( isset( $config['labels'][ $key ] ) ? $config['labels'][ $key ] : '' ) ) ) {
						$config['labels'][ $key ] = $fallback;
					}
				}
				break;

			case 'journal_archive_gallery_copy_required':
			case 'journal_archive_gallery_count_invalid':
			case 'journal_archive_gallery_order_invalid':
			case 'journal_archive_gallery_image_invalid':
			case 'journal_archive_gallery_duplicate':
			case 'journal_archive_gallery_provenance_required':
			case 'journal_archive_gallery_source_invalid':
			case 'journal_archive_gallery_link_invalid':
				$config['gallery'] = $defaults['gallery'];
				break;

			case 'journal_archive_retention_order_invalid':
			case 'journal_archive_retention_copy_required':
			case 'journal_archive_retention_destination_invalid':
			case 'journal_archive_retention_url_invalid':
			case 'journal_archive_retention_image_invalid':
			case 'journal_archive_retention_image_provenance_required':
			case 'journal_archive_retention_image_source_invalid':
				$config['retention'] = $defaults['retention'];
				break;

			case 'journal_archive_presentation_invalid':
				$config['presentation'] = is_array( $config['presentation'] ) ? $config['presentation'] : array();
				$allowed = array(
					'density'         => array( 'compact', 'editorial', 'showcase' ),
					'lead_prominence' => array( 'restrained', 'standard', 'feature' ),
					'desk_rhythm'     => array( 'quick', 'balanced', 'immersive' ),
				);
				foreach ( $allowed as $key => $values ) {
					$value = isset( $config['presentation'][ $key ] ) ? sanitize_key( $config['presentation'][ $key ] ) : '';
					if ( ! in_array( $value, $values, true ) ) {
						$config['presentation'][ $key ] = $defaults['presentation'][ $key ];
					}
				}
				break;

			case 'journal_archive_geometry_invalid':
				$config['presentation'] = is_array( $config['presentation'] ) ? $config['presentation'] : array();
				$bounds = array(
					'section_gap'      => array( 18, 86 ),
					'hero_min_height'  => array( 160, 420 ),
					'card_min_height'  => array( 280, 560 ),
					'media_min_height' => array( 160, 360 ),
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

	return new WP_Error( 'journal_archive_config_repair_failed' );
}

/**
 * Resolve the current, last-valid public configuration.
 *
 * @param bool $allow_preview Whether a private token may override the request.
 * @return array<string,mixed>
 */
function lunara_journal_archive_studio_get_public_config( $allow_preview = true ) {
	if ( $allow_preview && isset( $_GET['lunara_journal_preview'] ) ) {
		$preview = lunara_journal_archive_studio_get_preview_config( sanitize_text_field( wp_unslash( $_GET['lunara_journal_preview'] ) ) );
		if ( is_array( $preview ) ) {
			return $preview;
		}
	}

	$defaults = lunara_journal_archive_studio_defaults();
	$new      = lunara_journal_archive_studio_get_new_fields();
	$config   = $defaults;

	foreach ( array( 'schema_version', 'supporting_copy', 'lead_mode', 'lead_id', 'lane_mode', 'curated_ids', 'item_count', 'filter_caps', 'labels', 'gallery', 'retention' ) as $key ) {
		if ( array_key_exists( $key, $new ) ) {
			$config[ $key ] = $new[ $key ];
		}
	}

	$legacy_kicker = lunara_journal_archive_studio_scalar_or( get_theme_mod( 'lunara_journal_archive_kicker', null ), null );
	$legacy_title  = lunara_journal_archive_studio_scalar_or( get_theme_mod( 'lunara_journal_archive_title', null ), null );
	$has_studio_schema = isset( $new['schema_version'] ) && is_scalar( $new['schema_version'] ) && 1 === absint( $new['schema_version'] );
	$legacy_sentinel   = ! $has_studio_schema && 'The Lunara Journal' === $legacy_kicker && 'Journal' === $legacy_title;
	// Preserve the historic pre-Studio rendered fallback only for the exact
	// legacy sentinel pair. Once the existing schema marker is present, every
	// phrase entered in the focused Studio round-trips verbatim.
	$config['kicker'] = null === $legacy_kicker || false === $legacy_kicker || $legacy_sentinel ? __( 'Journal', 'lunara-film' ) : (string) $legacy_kicker;
	$config['title']  = null === $legacy_title || false === $legacy_title || $legacy_sentinel ? __( 'Lunara Journal', 'lunara-film' ) : (string) $legacy_title;
	$config['deck']   = (string) lunara_journal_archive_studio_scalar_or( get_theme_mod( 'lunara_journal_archive_copy', '' ), '' );

	$raw_order = (string) lunara_journal_archive_studio_scalar_or( get_theme_mod( 'lunara_journal_archive_section_order', 'hero,filters,grid,pagination' ), 'hero,filters,grid,pagination' );
	$config['section_order'] = lunara_journal_archive_studio_expand_section_order( $raw_order );

	if ( function_exists( 'lunara_get_journal_archive_section_registry' ) ) {
		foreach ( lunara_get_journal_archive_section_registry() as $slug => $spec ) {
			$visibility = get_theme_mod( $spec['setting'], true );
			$config['section_visibility'][ $slug ] = is_scalar( $visibility ) ? (bool) $visibility : true;
		}
	}

	$config['presentation'] = array(
		'density'          => sanitize_key( (string) lunara_journal_archive_studio_scalar_or( get_theme_mod( 'lunara_journal_archive_density', 'editorial' ), 'editorial' ) ),
		'lead_prominence'  => sanitize_key( (string) lunara_journal_archive_studio_scalar_or( get_theme_mod( 'lunara_journal_archive_lead_prominence', 'standard' ), 'standard' ) ),
		'desk_rhythm'      => sanitize_key( (string) lunara_journal_archive_studio_scalar_or( get_theme_mod( 'lunara_journal_archive_desk_rhythm', 'balanced' ), 'balanced' ) ),
		'section_gap'      => absint( lunara_journal_archive_studio_scalar_or( get_theme_mod( 'lunara_journal_archive_section_gap', 38 ), 38 ) ),
		'hero_min_height'  => absint( lunara_journal_archive_studio_scalar_or( get_theme_mod( 'lunara_journal_archive_hero_min_height', 240 ), 240 ) ),
		'card_min_height'  => absint( lunara_journal_archive_studio_scalar_or( get_theme_mod( 'lunara_journal_archive_card_min_height', 390 ), 390 ) ),
		'media_min_height' => absint( lunara_journal_archive_studio_scalar_or( get_theme_mod( 'lunara_journal_archive_media_min_height', 220 ), 220 ) ),
	);

	$config    = lunara_journal_archive_studio_normalize_public_shape( $config, $defaults );
	$config    = lunara_journal_archive_studio_degrade_invalid_references( $config );
	$warnings  = isset( $config['_warnings'] ) && is_array( $config['_warnings'] ) ? $config['_warnings'] : array();
	$validated = lunara_journal_archive_studio_repair_public_config( $config, $defaults );
	if ( is_wp_error( $validated ) ) {
		// Every validator family is repaired above. Keep this impossible-state
		// fallback valid without pretending the malformed candidate was public.
		$validated = lunara_journal_archive_studio_validate_config( $defaults );
	}
	$validated['_warnings'] = $warnings;

	return $validated;
}

/**
 * Degrade only references whose external WordPress owner later disappeared.
 *
 * @param array<string,mixed> $config Composite config.
 * @return array<string,mixed>
 */
function lunara_journal_archive_studio_degrade_invalid_references( $config ) {
	$warnings = array();
	if ( 'manual' === $config['lead_mode'] && ! lunara_journal_archive_studio_validate_post_id( $config['lead_id'] ) ) {
		$config['lead_mode'] = 'shared';
		$config['lead_id']   = 0;
		$warnings[] = 'manual_lead_unavailable';
	}
	$curated = array();
	foreach ( (array) $config['curated_ids'] as $post_id ) {
		$post_id = lunara_journal_archive_studio_validate_post_id( $post_id );
		if ( $post_id && ! in_array( $post_id, $curated, true ) ) {
			$curated[] = $post_id;
		} else {
			$warnings[] = 'curated_story_unavailable';
		}
	}
	$config['curated_ids'] = $curated;
	if ( 'curated' === $config['lane_mode'] && empty( $curated ) ) {
		$config['lane_mode'] = 'query';
		$warnings[] = 'curated_lane_fell_back_to_query';
	}
	foreach ( $config['retention'] as $index => $card ) {
		$image_id = absint( isset( $card['image_id'] ) ? $card['image_id'] : 0 );
		if ( $image_id && ! lunara_journal_archive_studio_validate_attachment_id( $image_id ) ) {
			$config['retention'][ $index ]['image_id'] = 0;
			$warnings[] = 'retention_image_unavailable';
		} elseif ( $image_id && lunara_journal_archive_studio_attachment_below_wide_target( $image_id ) ) {
			$warnings[] = 'retention_image_wide_quality';
		}
	}
	$gallery_items = array();
	foreach ( (array) $config['gallery']['items'] as $item ) {
		$image_id = absint( isset( $item['attachment_id'] ) ? $item['attachment_id'] : 0 );
		if ( ! $image_id || ! lunara_journal_archive_studio_validate_attachment_id( $image_id ) ) {
			$warnings[] = 'gallery_image_unavailable';
			continue;
		}
		if ( lunara_journal_archive_studio_attachment_below_wide_target( $image_id ) ) {
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
 * Validate a published Journal ID.
 *
 * @param mixed $post_id Candidate ID.
 * @return int
 */
function lunara_journal_archive_studio_validate_post_id( $post_id ) {
	if ( ! is_scalar( $post_id ) ) {
		return 0;
	}
	$post_id = absint( $post_id );
	$post    = $post_id ? get_post( $post_id ) : null;
	return $post && 'journal' === $post->post_type && 'publish' === $post->post_status ? $post_id : 0;
}

/**
 * Validate an optional Media Library image.
 *
 * @param mixed $attachment_id Candidate ID.
 * @return int
 */
function lunara_journal_archive_studio_validate_attachment_id( $attachment_id ) {
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
function lunara_journal_archive_studio_attachment_below_wide_target( $attachment_id ) {
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
function lunara_journal_archive_studio_safe_https_url( $value, $allow_empty = true ) {
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
 * Resolve optional retention media before the template opens a media chamber.
 *
 * A valid attachment record can still point at a missing derivative or stale
 * physical file. Returning an empty string lets the renderer degrade to the
 * text card without emitting `has-media` or a fixed-ratio empty wrapper.
 *
 * @param array<string,mixed> $card Retention card config.
 * @return string
 */
function lunara_journal_archive_studio_retention_media_markup( $card ) {
	$image_id = lunara_journal_archive_studio_validate_attachment_id( isset( $card['image_id'] ) ? $card['image_id'] : 0 );
	if ( ! $image_id || ! function_exists( 'wp_get_attachment_image' ) ) {
		return '';
	}

	$alt = isset( $card['image_alt'] ) ? trim( (string) $card['image_alt'] ) : '';
	if ( '' === $alt && function_exists( 'get_post_meta' ) ) {
		$alt = trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) );
	}
	if ( '' === $alt && function_exists( 'get_the_title' ) ) {
		$alt = trim( (string) get_the_title( $image_id ) );
	}

	$markup = wp_get_attachment_image(
		$image_id,
		'lunara-hero-spotlight',
		false,
		array(
			'loading'  => 'lazy',
			'decoding' => 'async',
			'sizes'    => '(max-width: 700px) 92vw, 31vw',
			'alt'      => $alt,
		)
	);

	return is_string( $markup ) ? trim( $markup ) : '';
}

/**
 * Resolve one archive-gallery attachment into native responsive markup.
 *
 * @param array<string,mixed> $item Valid gallery item.
 * @return string
 */
function lunara_journal_archive_studio_gallery_media_markup( $item ) {
	$image_id = lunara_journal_archive_studio_validate_attachment_id( isset( $item['attachment_id'] ) ? $item['attachment_id'] : 0 );
	if ( ! $image_id || ! function_exists( 'wp_get_attachment_image' ) ) {
		return '';
	}
	$markup = wp_get_attachment_image(
		$image_id,
		'lunara-hero-spotlight',
		false,
		array(
			'class'    => 'lunara-journal-archive-gallery-image',
			'loading'  => 'lazy',
			'decoding' => 'async',
			'sizes'    => '(max-width: 700px) 92vw, (max-width: 1100px) 46vw, 31vw',
			'alt'      => isset( $item['alt'] ) ? trim( (string) $item['alt'] ) : '',
		)
	);
	return is_string( $markup ) ? trim( $markup ) : '';
}

/**
 * Render the optional archive-only gallery as responsive, public SSR.
 *
 * Missing derivatives are skipped before any fixed-ratio chamber is opened.
 * If no selected attachment renders, this returns an exact empty string so
 * the default/cleared state adds no heading, wrapper, geometry, or script.
 *
 * @param array<string,mixed> $gallery Last-valid gallery configuration.
 * @return string
 */
function lunara_journal_archive_studio_render_gallery( $gallery ) {
	if ( ! is_array( $gallery ) || empty( $gallery['items'] ) || ! is_array( $gallery['items'] ) ) {
		return '';
	}
	$items = $gallery['items'];
	usort(
		$items,
		static function ( $left, $right ) {
			return absint( isset( $left['order'] ) ? $left['order'] : 0 ) <=> absint( isset( $right['order'] ) ? $right['order'] : 0 );
		}
	);
	$figures = array();
	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$media = lunara_journal_archive_studio_gallery_media_markup( $item );
		if ( '' === $media ) {
			continue;
		}
		$link_url   = isset( $item['link_url'] ) ? (string) $item['link_url'] : '';
		$source_url = isset( $item['source_url'] ) ? (string) $item['source_url'] : '';
		$media_html = '' !== $link_url
			? '<a class="lunara-journal-archive-gallery-destination" href="' . esc_url( $link_url ) . '">' . $media . '</a>'
			: $media;
		$caption = '';
		if ( ! empty( $item['caption'] ) ) {
			$caption .= '<p>' . esc_html( $item['caption'] ) . '</p>';
		}
		$credit = '<span class="lunara-journal-archive-gallery-credit">' . esc_html( $item['credit'] ) . '</span>';
		$source = '<a class="lunara-journal-archive-gallery-source" href="' . esc_url( $source_url ) . '" rel="noopener noreferrer">' . esc_html( $item['source'] ) . '</a>';
		$caption .= '<small>' . $credit . '<span aria-hidden="true"> · </span>' . $source . '</small>';
		$figures[] = '<figure class="lunara-journal-archive-gallery-item"><div class="lunara-journal-archive-gallery-media" style="--lunara-gallery-focus-x:' . esc_attr( absint( $item['focal_x'] ) ) . '%;--lunara-gallery-focus-y:' . esc_attr( absint( $item['focal_y'] ) ) . '%">' . $media_html . '</div><figcaption>' . $caption . '</figcaption></figure>';
	}
	if ( empty( $figures ) ) {
		return '';
	}
	$copy = ! empty( $gallery['copy'] ) ? '<p class="lunara-journal-archive-gallery-copy">' . esc_html( $gallery['copy'] ) . '</p>' : '';
	return '<section class="lunara-journal-archive-gallery" aria-labelledby="lunara-journal-archive-gallery-title"><header class="lunara-journal-archive-gallery-head"><p class="lunara-home-section-kicker">' . esc_html( $gallery['kicker'] ) . '</p><h3 id="lunara-journal-archive-gallery-title" class="lunara-section-title">' . esc_html( $gallery['title'] ) . '</h3>' . $copy . '</header><div class="lunara-journal-archive-gallery-grid">' . implode( '', $figures ) . '</div></section>';
}

/**
 * Keep the archive-only gallery on canonical /journal/ and off term archives.
 *
 * @return bool
 */
function lunara_journal_archive_studio_is_gallery_request() {
	return is_post_type_archive( 'journal' ) && ! is_paged() && ! is_tax( array( 'journal_section', 'journal_topic', 'journal_type' ) );
}

/**
 * Compose the retention lane with the root-only gallery after card markup.
 *
 * @param string $cards_markup Retention heading and card grid, or empty.
 * @param string $gallery_markup Root archive gallery SSR, or empty.
 * @param bool   $has_posts Whether the archive has eligible Journal posts.
 * @return string
 */
function lunara_journal_archive_studio_compose_retention_lane( $cards_markup, $gallery_markup, $has_posts ) {
	$cards_markup   = is_string( $cards_markup ) ? trim( $cards_markup ) : '';
	$gallery_markup = is_string( $gallery_markup ) ? trim( $gallery_markup ) : '';
	if ( ! $has_posts || ( '' === $cards_markup && '' === $gallery_markup ) ) {
		return '';
	}
	return '<section class="lunara-journal-archive-retention lunara-journal-archive-slot-retention" aria-label="' . esc_attr( __( 'Continue reading the Journal', 'lunara-film' ) ) . '">' . $cards_markup . $gallery_markup . '</section>';
}

/**
 * Bounded plain text.
 *
 * @param mixed $value Input.
 * @param int   $max Maximum characters.
 * @return string
 */
function lunara_journal_archive_studio_text( $value, $max = 180 ) {
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
function lunara_journal_archive_studio_textarea( $value, $max = 700 ) {
	$value = sanitize_textarea_field( is_scalar( $value ) ? $value : '' );
	return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
}

/**
 * Validate a complete staged configuration without mutating public state.
 *
 * @param mixed $raw Candidate configuration.
 * @return array<string,mixed>|WP_Error
 */
function lunara_journal_archive_studio_validate_config( $raw ) {
	if ( ! is_array( $raw ) ) {
		return new WP_Error( 'journal_archive_config_invalid' );
	}

	$defaults = lunara_journal_archive_studio_defaults();
	$shape_errors = array(
		'filter_caps'       => 'journal_archive_filter_cap_invalid',
		'section_order'     => 'journal_archive_section_order_invalid',
		'section_visibility' => 'journal_archive_config_invalid',
		'labels'            => 'journal_archive_label_required',
		'gallery'           => 'journal_archive_gallery_count_invalid',
		'retention'         => 'journal_archive_retention_order_invalid',
		'presentation'      => 'journal_archive_presentation_invalid',
	);
	foreach ( $shape_errors as $family => $error_code ) {
		if ( array_key_exists( $family, $raw ) && ! is_array( $raw[ $family ] ) ) {
			return new WP_Error( $error_code );
		}
	}
	if ( isset( $raw['gallery'] ) && is_array( $raw['gallery'] ) && array_key_exists( 'items', $raw['gallery'] ) && ! is_array( $raw['gallery']['items'] ) ) {
		return new WP_Error( 'journal_archive_gallery_count_invalid' );
	}
	if ( isset( $raw['retention'] ) && is_array( $raw['retention'] ) ) {
		if ( 3 !== count( $raw['retention'] ) ) {
			return new WP_Error( 'journal_archive_retention_order_invalid' );
		}
		for ( $retention_index = 0; $retention_index < 3; $retention_index++ ) {
			if ( ! isset( $raw['retention'][ $retention_index ] ) || ! is_array( $raw['retention'][ $retention_index ] ) ) {
				return new WP_Error( 'journal_archive_retention_order_invalid' );
			}
		}
	}

	$top_scalar_errors = array(
		'schema_version'  => 'journal_archive_config_invalid',
		'kicker'          => 'journal_archive_identity_required',
		'title'           => 'journal_archive_identity_required',
		'deck'            => 'journal_archive_config_invalid',
		'supporting_copy' => 'journal_archive_config_invalid',
		'lead_mode'       => 'journal_archive_lead_mode_invalid',
		'lead_id'         => 'journal_archive_lead_invalid',
		'lane_mode'       => 'journal_archive_lane_mode_invalid',
		'item_count'      => 'journal_archive_item_count_invalid',
	);
	foreach ( $top_scalar_errors as $field => $error_code ) {
		if ( array_key_exists( $field, $raw ) && ! is_scalar( $raw[ $field ] ) ) {
			return new WP_Error( $error_code );
		}
	}
	if ( isset( $raw['curated_ids'] ) ) {
		if ( ! is_array( $raw['curated_ids'] ) ) {
			return new WP_Error( 'journal_archive_curated_count_invalid' );
		}
		foreach ( $raw['curated_ids'] as $post_id ) {
			if ( ! is_scalar( $post_id ) ) {
				return new WP_Error( 'journal_archive_curated_post_invalid' );
			}
		}
	}
	foreach ( array(
		'filter_caps'       => 'journal_archive_filter_cap_invalid',
		'section_visibility' => 'journal_archive_config_invalid',
		'labels'            => 'journal_archive_label_required',
		'presentation'      => 'journal_archive_presentation_invalid',
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
				return new WP_Error( 'journal_archive_section_order_invalid' );
			}
		}
	}
	if ( isset( $raw['gallery'] ) && is_array( $raw['gallery'] ) ) {
		foreach ( array( 'kicker', 'title', 'copy' ) as $field ) {
			if ( array_key_exists( $field, $raw['gallery'] ) && ! is_scalar( $raw['gallery'][ $field ] ) ) {
				return new WP_Error( 'journal_archive_gallery_copy_required' );
			}
		}
		if ( isset( $raw['gallery']['items'] ) && is_array( $raw['gallery']['items'] ) ) {
			foreach ( $raw['gallery']['items'] as $raw_item ) {
				if ( ! is_array( $raw_item ) ) {
					return new WP_Error( 'journal_archive_gallery_order_invalid' );
				}
				foreach ( array( 'order', 'attachment_id', 'alt', 'caption', 'link_url', 'credit', 'source', 'source_url', 'focal_x', 'focal_y' ) as $field ) {
					if ( array_key_exists( $field, $raw_item ) && ! is_scalar( $raw_item[ $field ] ) ) {
						return new WP_Error( in_array( $field, array( 'order', 'focal_x', 'focal_y' ), true ) ? 'journal_archive_gallery_order_invalid' : 'journal_archive_gallery_provenance_required' );
					}
				}
			}
		}
	}
	if ( isset( $raw['retention'] ) && is_array( $raw['retention'] ) ) {
		foreach ( $raw['retention'] as $raw_card ) {
			foreach ( array( 'visible', 'order', 'label', 'title', 'copy', 'destination', 'url', 'image_id', 'image_alt', 'image_credit', 'image_source', 'image_source_url', 'focal_x', 'focal_y' ) as $field ) {
				if ( array_key_exists( $field, $raw_card ) && ! is_scalar( $raw_card[ $field ] ) ) {
					return new WP_Error( 'destination' === $field ? 'journal_archive_retention_destination_invalid' : 'journal_archive_retention_copy_required' );
				}
			}
		}
	}
	$config   = array_replace_recursive( $defaults, $raw );
	$config['schema_version']  = 1;
	$config['kicker']          = lunara_journal_archive_studio_text( $config['kicker'], 80 );
	$config['title']           = lunara_journal_archive_studio_text( $config['title'], 140 );
	$config['deck']            = lunara_journal_archive_studio_textarea( $config['deck'], 500 );
	$config['supporting_copy'] = lunara_journal_archive_studio_textarea( $config['supporting_copy'], 700 );

	if ( '' === $config['kicker'] || '' === $config['title'] ) {
		return new WP_Error( 'journal_archive_identity_required' );
	}

	$config['item_count'] = absint( $config['item_count'] );
	if ( $config['item_count'] < 4 || $config['item_count'] > 24 ) {
		return new WP_Error( 'journal_archive_item_count_invalid' );
	}
	foreach ( $defaults['filter_caps'] as $taxonomy => $fallback_cap ) {
		$config['filter_caps'][ $taxonomy ] = absint( isset( $config['filter_caps'][ $taxonomy ] ) ? $config['filter_caps'][ $taxonomy ] : $fallback_cap );
		if ( $config['filter_caps'][ $taxonomy ] < 1 || $config['filter_caps'][ $taxonomy ] > 20 ) {
			return new WP_Error( 'journal_archive_filter_cap_invalid' );
		}
	}

	$config['lead_mode'] = sanitize_key( $config['lead_mode'] );
	if ( ! in_array( $config['lead_mode'], array( 'shared', 'automatic', 'manual' ), true ) ) {
		return new WP_Error( 'journal_archive_lead_mode_invalid' );
	}
	$config['lead_id'] = absint( $config['lead_id'] );
	if ( 'manual' === $config['lead_mode'] ) {
		$config['lead_id'] = lunara_journal_archive_studio_validate_post_id( $config['lead_id'] );
		if ( ! $config['lead_id'] ) {
			return new WP_Error( 'journal_archive_lead_invalid' );
		}
	} else {
		// Clearing the manual mode must clear its hidden pin state as well.
		$config['lead_id'] = 0;
	}

	$config['lane_mode'] = sanitize_key( $config['lane_mode'] );
	if ( ! in_array( $config['lane_mode'], array( 'query', 'curated' ), true ) ) {
		return new WP_Error( 'journal_archive_lane_mode_invalid' );
	}
	$raw_curated = 'curated' === $config['lane_mode'] && is_array( $config['curated_ids'] ) ? $config['curated_ids'] : array();
	$curated     = array();
	foreach ( $raw_curated as $raw_id ) {
		$id = lunara_journal_archive_studio_validate_post_id( $raw_id );
		if ( ! $id ) {
			return new WP_Error( 'journal_archive_curated_post_invalid' );
		}
		if ( in_array( $id, $curated, true ) ) {
			return new WP_Error( 'journal_archive_curated_duplicate' );
		}
		$curated[] = $id;
	}
	if ( count( $curated ) > 24 || ( 'curated' === $config['lane_mode'] && empty( $curated ) ) ) {
		return new WP_Error( 'journal_archive_curated_count_invalid' );
	}
	$config['curated_ids'] = $curated;

	$required_order = $defaults['section_order'];
	$order          = is_array( $config['section_order'] ) ? array_map( 'sanitize_key', $config['section_order'] ) : array();
	if ( count( $order ) !== count( array_unique( $order ) ) ) {
		return new WP_Error( 'journal_archive_section_order_invalid' );
	}
	$sorted_order = $order;
	sort( $sorted_order );
	$sorted_required = $required_order;
	sort( $sorted_required );
	if ( $sorted_required !== $sorted_order ) {
		return new WP_Error( 'journal_archive_section_order_invalid' );
	}
	$config['section_order'] = array_values( $order );

	$visibility = is_array( $config['section_visibility'] ) ? $config['section_visibility'] : array();
	foreach ( $required_order as $slug ) {
		$config['section_visibility'][ $slug ] = ! empty( $visibility[ $slug ] );
	}
	if ( ! $config['section_visibility']['hero'] && ! $config['section_visibility']['grid'] ) {
		return new WP_Error( 'journal_archive_primary_sections_hidden' );
	}

	foreach ( $defaults['labels'] as $key => $fallback ) {
		$config['labels'][ $key ] = lunara_journal_archive_studio_text( isset( $config['labels'][ $key ] ) ? $config['labels'][ $key ] : $fallback, 120 );
		if ( '' === $config['labels'][ $key ] ) {
			return new WP_Error( 'journal_archive_label_required' );
		}
	}

	$raw_gallery = isset( $config['gallery'] ) && is_array( $config['gallery'] ) ? $config['gallery'] : array();
	$gallery     = array_replace( $defaults['gallery'], $raw_gallery );
	$gallery['kicker'] = lunara_journal_archive_studio_text( $gallery['kicker'], 80 );
	$gallery['title']  = lunara_journal_archive_studio_text( $gallery['title'], 140 );
	$gallery['copy']   = lunara_journal_archive_studio_textarea( $gallery['copy'], 500 );
	if ( '' === $gallery['kicker'] || '' === $gallery['title'] ) {
		return new WP_Error( 'journal_archive_gallery_copy_required' );
	}
	$raw_items = isset( $raw_gallery['items'] ) && is_array( $raw_gallery['items'] ) ? array_values( $raw_gallery['items'] ) : array();
	if ( count( $raw_items ) > 12 ) {
		return new WP_Error( 'journal_archive_gallery_count_invalid' );
	}
	$gallery_items  = array();
	$gallery_ids    = array();
	$gallery_orders = array();
	$item_defaults  = array( 'order' => 0, 'attachment_id' => 0, 'alt' => '', 'caption' => '', 'link_url' => '', 'credit' => '', 'source' => '', 'source_url' => '', 'focal_x' => 50, 'focal_y' => 50 );
	foreach ( $raw_items as $raw_item ) {
		$item = is_array( $raw_item ) ? array_replace( $item_defaults, $raw_item ) : $item_defaults;
		$item['order'] = absint( $item['order'] );
		if ( $item['order'] < 1 || $item['order'] > count( $raw_items ) || in_array( $item['order'], $gallery_orders, true ) ) {
			return new WP_Error( 'journal_archive_gallery_order_invalid' );
		}
		$gallery_orders[] = $item['order'];
		$raw_attachment_id     = absint( $item['attachment_id'] );
		$item['attachment_id'] = lunara_journal_archive_studio_validate_attachment_id( $raw_attachment_id );
		if ( ! $raw_attachment_id || ! $item['attachment_id'] ) {
			return new WP_Error( 'journal_archive_gallery_image_invalid' );
		}
		if ( in_array( $item['attachment_id'], $gallery_ids, true ) ) {
			return new WP_Error( 'journal_archive_gallery_duplicate' );
		}
		$gallery_ids[]   = $item['attachment_id'];
		$item['alt']     = lunara_journal_archive_studio_text( $item['alt'], 180 );
		$item['caption'] = lunara_journal_archive_studio_textarea( $item['caption'], 360 );
		$item['credit']  = lunara_journal_archive_studio_text( $item['credit'], 180 );
		$item['source']  = lunara_journal_archive_studio_text( $item['source'], 180 );
		if ( '' === $item['alt'] || '' === $item['credit'] || '' === $item['source'] || '' === trim( (string) $item['source_url'] ) ) {
			return new WP_Error( 'journal_archive_gallery_provenance_required' );
		}
		$item['source_url'] = lunara_journal_archive_studio_safe_https_url( $item['source_url'], false );
		if ( false === $item['source_url'] ) {
			return new WP_Error( 'journal_archive_gallery_source_invalid' );
		}
		$item['link_url'] = lunara_journal_archive_studio_safe_https_url( $item['link_url'], true );
		if ( false === $item['link_url'] ) {
			return new WP_Error( 'journal_archive_gallery_link_invalid' );
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
	for ( $index = 0; $index < 3; $index++ ) {
		$card = isset( $config['retention'][ $index ] ) && is_array( $config['retention'][ $index ] )
			? array_replace( $defaults['retention'][ $index ], $config['retention'][ $index ] )
			: $defaults['retention'][ $index ];
		$card['visible'] = ! empty( $card['visible'] );
		$card['order']   = absint( $card['order'] );
		if ( $card['order'] < 1 || $card['order'] > 3 || in_array( $card['order'], $retention_orders, true ) ) {
			return new WP_Error( 'journal_archive_retention_order_invalid' );
		}
		$retention_orders[] = $card['order'];
		$card['label'] = lunara_journal_archive_studio_text( $card['label'], 80 );
		$card['title'] = lunara_journal_archive_studio_text( $card['title'], 140 );
		$card['copy']  = lunara_journal_archive_studio_textarea( $card['copy'], 360 );
		if ( '' === $card['label'] || '' === $card['title'] || '' === $card['copy'] ) {
			return new WP_Error( 'journal_archive_retention_copy_required' );
		}
		$card['destination'] = sanitize_key( $card['destination'] );
		if ( ! in_array( $card['destination'], array( 'latest', 'trailer', 'reviews', 'journal', 'custom' ), true ) ) {
			return new WP_Error( 'journal_archive_retention_destination_invalid' );
		}
		$card['url'] = lunara_journal_archive_studio_safe_https_url( $card['url'], 'custom' !== $card['destination'] );
		if ( false === $card['url'] ) {
			return new WP_Error( 'journal_archive_retention_url_invalid' );
		}
		$raw_image_id    = absint( $card['image_id'] );
		$card['image_id'] = lunara_journal_archive_studio_validate_attachment_id( $raw_image_id );
		if ( $raw_image_id && ! $card['image_id'] ) {
			return new WP_Error( 'journal_archive_retention_image_invalid' );
		}
		$card['image_alt']        = lunara_journal_archive_studio_text( $card['image_alt'], 180 );
		$card['image_credit']     = lunara_journal_archive_studio_text( $card['image_credit'], 180 );
		$card['image_source']     = lunara_journal_archive_studio_text( $card['image_source'], 180 );
		if ( $card['image_id'] && ( '' === $card['image_credit'] || '' === $card['image_source'] || '' === trim( (string) $card['image_source_url'] ) ) ) {
			return new WP_Error( 'journal_archive_retention_image_provenance_required' );
		}
		$card['image_source_url'] = lunara_journal_archive_studio_safe_https_url( $card['image_source_url'], ! $card['image_id'] );
		if ( false === $card['image_source_url'] ) {
			return new WP_Error( 'journal_archive_retention_image_source_invalid' );
		}
		$card['focal_x']          = max( 0, min( 100, absint( $card['focal_x'] ) ) );
		$card['focal_y']          = max( 0, min( 100, absint( $card['focal_y'] ) ) );
		$retention[] = $card;
	}
	$config['retention'] = $retention;

	$selects = array(
		'density'         => array( 'compact', 'editorial', 'showcase' ),
		'lead_prominence' => array( 'restrained', 'standard', 'feature' ),
		'desk_rhythm'     => array( 'quick', 'balanced', 'immersive' ),
	);
	foreach ( $selects as $key => $allowed ) {
		$config['presentation'][ $key ] = sanitize_key( $config['presentation'][ $key ] );
		if ( ! in_array( $config['presentation'][ $key ], $allowed, true ) ) {
			return new WP_Error( 'journal_archive_presentation_invalid' );
		}
	}
	$numbers = array(
		'section_gap'      => array( 18, 86 ),
		'hero_min_height'  => array( 160, 420 ),
		'card_min_height'  => array( 280, 560 ),
		'media_min_height' => array( 160, 360 ),
	);
	foreach ( $numbers as $key => $bounds ) {
		$config['presentation'][ $key ] = absint( $config['presentation'][ $key ] );
		if ( $config['presentation'][ $key ] < $bounds[0] || $config['presentation'][ $key ] > $bounds[1] ) {
			return new WP_Error( 'journal_archive_geometry_invalid' );
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
function lunara_journal_archive_studio_config_from_request( $request ) {
	$current = lunara_journal_archive_studio_get_public_config( false );
	$request = is_array( $request ) ? wp_unslash( $request ) : array();
	$identity = isset( $request['lunara_journal_archive_identity'] ) && is_array( $request['lunara_journal_archive_identity'] ) ? $request['lunara_journal_archive_identity'] : array();
	$labels   = isset( $request['lunara_journal_archive_labels'] ) && is_array( $request['lunara_journal_archive_labels'] ) ? $request['lunara_journal_archive_labels'] : array();
	$current['kicker']          = isset( $identity['kicker'] ) ? $identity['kicker'] : '';
	$current['title']           = isset( $identity['title'] ) ? $identity['title'] : '';
	$current['deck']            = isset( $identity['deck'] ) ? $identity['deck'] : '';
	$current['supporting_copy'] = isset( $identity['supporting_copy'] ) ? $identity['supporting_copy'] : '';
	$current['lead_mode']       = isset( $request['lunara_journal_archive_lead_mode'] ) ? $request['lunara_journal_archive_lead_mode'] : 'shared';
	$current['lead_id']         = isset( $request['lunara_journal_archive_lead_id'] ) ? $request['lunara_journal_archive_lead_id'] : 0;
	$current['lane_mode']       = isset( $request['lunara_journal_archive_lane_mode'] ) ? $request['lunara_journal_archive_lane_mode'] : 'query';
	$current['curated_ids']     = isset( $request['lunara_journal_archive_curated_ids'] ) && is_array( $request['lunara_journal_archive_curated_ids'] ) ? $request['lunara_journal_archive_curated_ids'] : array();
	$current['item_count']      = isset( $request['lunara_journal_archive_item_count'] ) ? $request['lunara_journal_archive_item_count'] : 8;
	$current['filter_caps']     = isset( $request['lunara_journal_archive_filter_caps'] ) && is_array( $request['lunara_journal_archive_filter_caps'] ) ? array_replace( $current['filter_caps'], $request['lunara_journal_archive_filter_caps'] ) : $current['filter_caps'];
	$current['labels']          = array_replace( $current['labels'], $labels );
	$current['retention']       = isset( $request['lunara_journal_archive_retention'] ) && is_array( $request['lunara_journal_archive_retention'] ) ? $request['lunara_journal_archive_retention'] : $current['retention'];

	$gallery = isset( $request['lunara_journal_archive_gallery'] ) && is_array( $request['lunara_journal_archive_gallery'] ) ? $request['lunara_journal_archive_gallery'] : array();
	$current['gallery']['kicker'] = isset( $gallery['kicker'] ) ? $gallery['kicker'] : $current['gallery']['kicker'];
	$current['gallery']['title']  = isset( $gallery['title'] ) ? $gallery['title'] : $current['gallery']['title'];
	$current['gallery']['copy']   = isset( $gallery['copy'] ) ? $gallery['copy'] : $current['gallery']['copy'];
	$gallery_ids_valid = ! isset( $request['lunara_journal_archive_gallery_ids'] ) || is_scalar( $request['lunara_journal_archive_gallery_ids'] );
	$gallery_ids = isset( $request['lunara_journal_archive_gallery_ids'] ) && $gallery_ids_valid
		? array_values( array_filter( array_map( 'trim', explode( ',', (string) $request['lunara_journal_archive_gallery_ids'] ) ), 'strlen' ) )
		: array();
	$gallery_maps = array();
	foreach ( array( 'alt', 'caption', 'link_url', 'credit', 'source', 'source_url', 'focal_x', 'focal_y' ) as $field ) {
		$key = 'lunara_journal_archive_gallery_' . $field;
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

	$visibility = isset( $request['lunara_journal_archive_section_visibility'] ) && is_array( $request['lunara_journal_archive_section_visibility'] ) ? $request['lunara_journal_archive_section_visibility'] : array();
	foreach ( $current['section_visibility'] as $slug => $enabled ) {
		$current['section_visibility'][ $slug ] = array_key_exists( $slug, $visibility ) ? $visibility[ $slug ] : false;
	}

	$positions = isset( $request['lunara_journal_archive_section_positions'] ) && is_array( $request['lunara_journal_archive_section_positions'] ) ? $request['lunara_journal_archive_section_positions'] : array();
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

	$raw_selects = isset( $request['lunara_journal_archive_select'] ) && is_array( $request['lunara_journal_archive_select'] ) ? $request['lunara_journal_archive_select'] : array();
	$raw_numbers = isset( $request['lunara_journal_archive_number'] ) && is_array( $request['lunara_journal_archive_number'] ) ? $request['lunara_journal_archive_number'] : array();
	$select_map  = array(
		'lunara_journal_archive_density'         => 'density',
		'lunara_journal_archive_lead_prominence' => 'lead_prominence',
		'lunara_journal_archive_desk_rhythm'     => 'desk_rhythm',
	);
	$number_map = array(
		'lunara_journal_archive_section_gap'      => 'section_gap',
		'lunara_journal_archive_hero_min_height'  => 'hero_min_height',
		'lunara_journal_archive_card_min_height'  => 'card_min_height',
		'lunara_journal_archive_media_min_height' => 'media_min_height',
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
function lunara_journal_archive_studio_apply_config( $config ) {
	set_theme_mod( 'lunara_journal_archive_kicker', $config['kicker'] );
	set_theme_mod( 'lunara_journal_archive_title', $config['title'] );
	set_theme_mod( 'lunara_journal_archive_copy', $config['deck'] );
	set_theme_mod( 'lunara_journal_archive_section_order', implode( ',', $config['section_order'] ) );

	if ( function_exists( 'lunara_get_journal_archive_section_registry' ) ) {
		foreach ( lunara_get_journal_archive_section_registry() as $slug => $spec ) {
			set_theme_mod( $spec['setting'], ! empty( $config['section_visibility'][ $slug ] ) );
		}
	}

	set_theme_mod( 'lunara_journal_archive_density', $config['presentation']['density'] );
	set_theme_mod( 'lunara_journal_archive_lead_prominence', $config['presentation']['lead_prominence'] );
	set_theme_mod( 'lunara_journal_archive_desk_rhythm', $config['presentation']['desk_rhythm'] );
	set_theme_mod( 'lunara_journal_archive_section_gap', (string) $config['presentation']['section_gap'] );
	set_theme_mod( 'lunara_journal_archive_hero_min_height', (string) $config['presentation']['hero_min_height'] );
	set_theme_mod( 'lunara_journal_archive_card_min_height', (string) $config['presentation']['card_min_height'] );
	set_theme_mod( 'lunara_journal_archive_media_min_height', (string) $config['presentation']['media_min_height'] );

	update_option(
		LUNARA_JOURNAL_ARCHIVE_STUDIO_OPTION,
		array(
			'schema_version'  => 1,
			'supporting_copy' => $config['supporting_copy'],
			'lead_mode'       => $config['lead_mode'],
			'lead_id'         => $config['lead_id'],
			'lane_mode'       => $config['lane_mode'],
			'curated_ids'     => $config['curated_ids'],
			'item_count'      => $config['item_count'],
			'filter_caps'     => $config['filter_caps'],
			'labels'          => $config['labels'],
			'gallery'         => $config['gallery'],
			'retention'       => $config['retention'],
		),
		false
	);
	wp_cache_delete( 'journal_archive_studio_public', 'lunara' );
}

/**
 * Return newest-first bounded audit history.
 *
 * @return array<int,array<string,mixed>>
 */
function lunara_journal_archive_studio_get_revisions() {
	$revisions = get_option( LUNARA_JOURNAL_ARCHIVE_STUDIO_REVISIONS_OPTION, array() );
	return is_array( $revisions ) ? array_slice( $revisions, 0, LUNARA_JOURNAL_ARCHIVE_STUDIO_REVISION_LIMIT ) : array();
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
function lunara_journal_archive_studio_push_revision( $config, $action = 'save', $validator_result = 'passed', $prior_public = true ) {
	$revisions = lunara_journal_archive_studio_get_revisions();
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
	update_option( LUNARA_JOURNAL_ARCHIVE_STUDIO_REVISIONS_OPTION, array_slice( $revisions, 0, LUNARA_JOURNAL_ARCHIVE_STUDIO_REVISION_LIMIT ), false );
	return $id;
}

/**
 * Validate first, then promote without any post/content mutation.
 *
 * @param mixed  $raw Candidate configuration.
 * @param string $action Audit action.
 * @return array<string,mixed>|WP_Error
 */
function lunara_journal_archive_studio_promote_config( $raw, $action = 'save' ) {
	$validated = lunara_journal_archive_studio_validate_config( $raw );
	if ( is_wp_error( $validated ) ) {
		return $validated;
	}
	$prior = lunara_journal_archive_studio_get_public_config( false );
	lunara_journal_archive_studio_push_revision( $prior, $action, 'passed', true );
	lunara_journal_archive_studio_apply_config( $validated );
	lunara_journal_archive_studio_flush_route_cache();
	return $validated;
}

/**
 * Restore a prior valid public snapshot.
 *
 * @param string $revision_id Revision UUID.
 * @return array<string,mixed>|WP_Error
 */
function lunara_journal_archive_studio_restore_revision( $revision_id ) {
	$revision_id = sanitize_text_field( $revision_id );
	foreach ( lunara_journal_archive_studio_get_revisions() as $revision ) {
		if ( empty( $revision['id'] ) || ! hash_equals( (string) $revision['id'], $revision_id ) || empty( $revision['prior_public'] ) ) {
			continue;
		}
		$validated = lunara_journal_archive_studio_validate_config( isset( $revision['config'] ) ? $revision['config'] : array() );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}
		$current = lunara_journal_archive_studio_get_public_config( false );
		lunara_journal_archive_studio_push_revision( $current, 'restore', 'passed', true );
		lunara_journal_archive_studio_apply_config( $validated );
		lunara_journal_archive_studio_flush_route_cache();
		return $validated;
	}
	return new WP_Error( 'journal_archive_revision_not_found' );
}

/**
 * Invalidate only Journal archive/data consumers.
 *
 * @return void
 */
function lunara_journal_archive_studio_cache_urls() {
	$urls        = array( get_post_type_archive_link( 'journal' ) );
	$taxonomies  = array( 'journal_section', 'journal_topic', 'journal_type' );
	foreach ( $taxonomies as $taxonomy ) {
		if ( ! function_exists( 'taxonomy_exists' ) || ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 200,
			)
		);
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			continue;
		}
		foreach ( $terms as $term ) {
			$url = get_term_link( $term );
			if ( ! is_wp_error( $url ) ) {
				$urls[] = $url;
			}
		}
	}
	return array_values( array_unique( array_filter( array_map( 'esc_url_raw', $urls ) ) ) );
}

function lunara_journal_archive_studio_flush_route_cache() {
	$routes = array( '/journal/', '/journal_section/', '/journal_topic/', '/journal_type/' );
	$urls   = lunara_journal_archive_studio_cache_urls();
	wp_cache_delete( 'journal_archive_studio_public', 'lunara' );

	// WP Rocket's bounded URL cleaner is used only when already available.
	// Never invoke a domain-wide purge, purge a CDN, or toggle a plugin here.
	if ( function_exists( 'rocket_clean_files' ) && $urls ) {
		rocket_clean_files( $urls );
	}
	do_action( 'lunara_journal_archive_studio_invalidate_routes', $routes, $urls );
}

/**
 * Time helper kept testable without changing system clocks.
 *
 * @return int
 */
function lunara_journal_archive_studio_timestamp() {
	return (int) current_time( 'timestamp', true );
}

/**
 * Store a private unsaved preview for thirty minutes.
 *
 * @param mixed $raw Candidate configuration.
 * @return string|WP_Error
 */
function lunara_journal_archive_studio_store_preview( $raw ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return new WP_Error( 'journal_archive_preview_forbidden' );
	}
	$validated = lunara_journal_archive_studio_validate_config( $raw );
	if ( is_wp_error( $validated ) ) {
		return $validated;
	}
	$token   = wp_generate_uuid4();
	$user_id = absint( get_current_user_id() );
	$key     = 'lunara_journal_archive_preview_' . hash( 'sha256', $token );
	set_transient(
		$key,
		array(
			'user_id'    => $user_id,
			'token_hash' => wp_hash( $token . '|' . $user_id ),
			'expires'    => lunara_journal_archive_studio_timestamp() + 1800,
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
function lunara_journal_archive_studio_get_preview_config( $token ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return false;
	}
	$token  = sanitize_text_field( $token );
	$record = get_transient( 'lunara_journal_archive_preview_' . hash( 'sha256', $token ) );
	if ( ! is_array( $record ) || empty( $record['user_id'] ) || absint( $record['user_id'] ) !== absint( get_current_user_id() ) ) {
		return false;
	}
	$expected = wp_hash( $token . '|' . absint( $record['user_id'] ) );
	if ( empty( $record['token_hash'] ) || ! hash_equals( (string) $record['token_hash'], $expected ) || empty( $record['expires'] ) || absint( $record['expires'] ) <= lunara_journal_archive_studio_timestamp() ) {
		return false;
	}
	$validated = lunara_journal_archive_studio_validate_config( isset( $record['config'] ) ? $record['config'] : array() );
	return is_wp_error( $validated ) ? false : $validated;
}

/**
 * Per-user transient key for a rejected, non-public form draft.
 *
 * @return string
 */
function lunara_journal_archive_studio_invalid_stage_key() {
	return 'lunara_journal_archive_invalid_' . absint( get_current_user_id() );
}

/**
 * Per-user transient key for bounded validator feedback.
 *
 * @return string
 */
function lunara_journal_archive_studio_feedback_key() {
	return 'lunara_journal_archive_feedback_' . absint( get_current_user_id() );
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
function lunara_journal_archive_studio_bound_invalid_stage( $candidate, $request = array() ) {
	$defaults = lunara_journal_archive_studio_defaults();
	$stage    = lunara_journal_archive_studio_normalize_public_shape( array_replace_recursive( $defaults, is_array( $candidate ) ? $candidate : array() ), $defaults );
	$request  = is_array( $request ) ? wp_unslash( $request ) : array();

	$stage['kicker']          = lunara_journal_archive_studio_text( $stage['kicker'], 80 );
	$stage['title']           = lunara_journal_archive_studio_text( $stage['title'], 140 );
	$stage['deck']            = lunara_journal_archive_studio_textarea( $stage['deck'], 500 );
	$stage['supporting_copy'] = lunara_journal_archive_studio_textarea( $stage['supporting_copy'], 700 );
	$stage['lead_mode']       = sanitize_key( $stage['lead_mode'] );
	$stage['lead_id']         = absint( $stage['lead_id'] );
	$stage['lane_mode']       = sanitize_key( $stage['lane_mode'] );
	$stage['curated_ids']     = array_slice( array_map( 'absint', is_array( $stage['curated_ids'] ) ? $stage['curated_ids'] : array() ), 0, 24 );
	$stage['item_count']      = min( 999, absint( $stage['item_count'] ) );

	foreach ( $defaults['filter_caps'] as $taxonomy => $fallback ) {
		$stage['filter_caps'][ $taxonomy ] = min( 999, absint( isset( $stage['filter_caps'][ $taxonomy ] ) ? $stage['filter_caps'][ $taxonomy ] : $fallback ) );
	}
	foreach ( $defaults['labels'] as $key => $fallback ) {
		$stage['labels'][ $key ] = lunara_journal_archive_studio_text( isset( $stage['labels'][ $key ] ) ? $stage['labels'][ $key ] : $fallback, 120 );
	}

	$valid_order = is_array( $stage['section_order'] ) ? array_values( array_map( 'sanitize_key', $stage['section_order'] ) ) : array();
	$required    = $defaults['section_order'];
	$sorted      = $valid_order;
	$sorted_required = $required;
	sort( $sorted );
	sort( $sorted_required );
	if ( count( $valid_order ) !== count( array_unique( $valid_order ) ) || $sorted !== $sorted_required ) {
		$stage['section_order'] = lunara_journal_archive_studio_get_public_config( false )['section_order'];
	}
	$raw_positions = isset( $request['lunara_journal_archive_section_positions'] ) && is_array( $request['lunara_journal_archive_section_positions'] )
		? $request['lunara_journal_archive_section_positions']
		: array();
	$stage['_staged_positions'] = array();
	foreach ( $required as $slug ) {
		$position = isset( $raw_positions[ $slug ] ) && is_scalar( $raw_positions[ $slug ] ) ? absint( $raw_positions[ $slug ] ) : 0;
		if ( $position >= 1 && $position <= count( $required ) ) {
			$stage['_staged_positions'][ $slug ] = $position;
		}
		$stage['section_visibility'][ $slug ] = ! empty( $stage['section_visibility'][ $slug ] );
	}

	foreach ( array( 'density', 'lead_prominence', 'desk_rhythm' ) as $key ) {
		$stage['presentation'][ $key ] = sanitize_key( $stage['presentation'][ $key ] );
	}
	foreach ( array( 'section_gap', 'hero_min_height', 'card_min_height', 'media_min_height' ) as $key ) {
		$stage['presentation'][ $key ] = min( 9999, absint( $stage['presentation'][ $key ] ) );
	}

	$stage['gallery'] = is_array( $stage['gallery'] ) ? array_replace( $defaults['gallery'], $stage['gallery'] ) : $defaults['gallery'];
	$stage['gallery']['kicker'] = lunara_journal_archive_studio_text( $stage['gallery']['kicker'], 80 );
	$stage['gallery']['title']  = lunara_journal_archive_studio_text( $stage['gallery']['title'], 140 );
	$stage['gallery']['copy']   = lunara_journal_archive_studio_textarea( $stage['gallery']['copy'], 500 );
	$stage['gallery']['items']  = is_array( $stage['gallery']['items'] ) ? array_slice( array_values( $stage['gallery']['items'] ), 0, 13 ) : array();
	$gallery_item_defaults = array( 'order' => 0, 'attachment_id' => 0, 'alt' => '', 'caption' => '', 'link_url' => '', 'credit' => '', 'source' => '', 'source_url' => '', 'focal_x' => 50, 'focal_y' => 50 );
	foreach ( $stage['gallery']['items'] as $index => $raw_item ) {
		$item = is_array( $raw_item ) ? array_replace( $gallery_item_defaults, $raw_item ) : $gallery_item_defaults;
		$item['order']         = $index + 1;
		$item['attachment_id'] = absint( $item['attachment_id'] );
		$item['alt']           = lunara_journal_archive_studio_text( $item['alt'], 180 );
		$item['caption']       = lunara_journal_archive_studio_textarea( $item['caption'], 360 );
		$item['link_url']      = esc_url_raw( $item['link_url'] );
		$item['credit']        = lunara_journal_archive_studio_text( $item['credit'], 180 );
		$item['source']        = lunara_journal_archive_studio_text( $item['source'], 180 );
		$item['source_url']    = esc_url_raw( $item['source_url'] );
		$item['focal_x']       = min( 100, absint( $item['focal_x'] ) );
		$item['focal_y']       = min( 100, absint( $item['focal_y'] ) );
		$stage['gallery']['items'][ $index ] = $item;
	}

	$stage['retention'] = is_array( $stage['retention'] ) ? array_slice( $stage['retention'], 0, 3 ) : $defaults['retention'];
	for ( $index = 0; $index < 3; $index++ ) {
		$card = isset( $stage['retention'][ $index ] ) && is_array( $stage['retention'][ $index ] )
			? array_replace( $defaults['retention'][ $index ], $stage['retention'][ $index ] )
			: $defaults['retention'][ $index ];
		$card['visible']          = ! empty( $card['visible'] );
		$card['order']            = min( 99, absint( $card['order'] ) );
		$card['label']            = lunara_journal_archive_studio_text( $card['label'], 80 );
		$card['title']            = lunara_journal_archive_studio_text( $card['title'], 140 );
		$card['copy']             = lunara_journal_archive_studio_textarea( $card['copy'], 360 );
		$card['destination']      = sanitize_key( $card['destination'] );
		$card['url']              = esc_url_raw( $card['url'] );
		$card['image_id']         = absint( $card['image_id'] );
		$card['image_alt']        = lunara_journal_archive_studio_text( $card['image_alt'], 180 );
		$card['image_credit']     = lunara_journal_archive_studio_text( $card['image_credit'], 180 );
		$card['image_source']     = lunara_journal_archive_studio_text( $card['image_source'], 180 );
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
function lunara_journal_archive_studio_store_invalid_stage( $candidate, $request, $error_code ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	set_transient( lunara_journal_archive_studio_invalid_stage_key(), lunara_journal_archive_studio_bound_invalid_stage( $candidate, $request ), 30 * 60 );
	set_transient( lunara_journal_archive_studio_feedback_key(), sanitize_key( $error_code ), 30 * 60 );
}

/**
 * Return the current editor's rejected private form draft.
 *
 * @return array<string,mixed>|false
 */
function lunara_journal_archive_studio_get_invalid_stage() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return false;
	}
	$stage = get_transient( lunara_journal_archive_studio_invalid_stage_key() );
	return is_array( $stage ) ? $stage : false;
}

/**
 * Clear rejected draft/feedback after a successful state transition.
 */
function lunara_journal_archive_studio_clear_invalid_stage() {
	delete_transient( lunara_journal_archive_studio_invalid_stage_key() );
	delete_transient( lunara_journal_archive_studio_feedback_key() );
}

/**
 * Store restore feedback without replacing a rejected edit draft.
 *
 * @param string $error_code Validator code.
 */
function lunara_journal_archive_studio_store_feedback( $error_code ) {
	if ( current_user_can( 'edit_theme_options' ) ) {
		set_transient( lunara_journal_archive_studio_feedback_key(), sanitize_key( $error_code ), 30 * 60 );
	}
}

/**
 * Human-readable allowlist for validator feedback; no raw error text leaks.
 *
 * @param string|null $error_code Optional explicit code.
 * @return string
 */
function lunara_journal_archive_studio_validation_message( $error_code = null ) {
	$code = null === $error_code ? get_transient( lunara_journal_archive_studio_feedback_key() ) : $error_code;
	$messages = array(
		'journal_archive_identity_required'              => __( 'Add both the archive kicker and headline.', 'lunara-film' ),
		'journal_archive_item_count_invalid'             => __( 'Items per page must be between 4 and 24.', 'lunara-film' ),
		'journal_archive_filter_cap_invalid'              => __( 'Each filter cap must be between 1 and 20.', 'lunara-film' ),
		'journal_archive_lead_mode_invalid'               => __( 'Choose a supported lead mode.', 'lunara-film' ),
		'journal_archive_lead_invalid'                    => __( 'The manual lead must be a published Journal file.', 'lunara-film' ),
		'journal_archive_lane_mode_invalid'               => __( 'Choose automatic query or curated selection.', 'lunara-film' ),
		'journal_archive_curated_post_invalid'            => __( 'Every curated file must still be published.', 'lunara-film' ),
		'journal_archive_curated_duplicate'               => __( 'Remove the duplicate file from the curated order.', 'lunara-film' ),
		'journal_archive_curated_count_invalid'           => __( 'A curated run needs 1 to 24 published files.', 'lunara-film' ),
		'journal_archive_section_order_invalid'           => __( 'Give every section one unique position.', 'lunara-film' ),
		'journal_archive_primary_sections_hidden'        => __( 'Keep either the Hero or Journal Grid visible.', 'lunara-film' ),
		'journal_archive_label_required'                  => __( 'Every public-language field needs a value.', 'lunara-film' ),
		'journal_archive_gallery_copy_required'           => __( 'The archive gallery needs both its kicker and heading.', 'lunara-film' ),
		'journal_archive_gallery_count_invalid'           => __( 'The archive gallery accepts up to twelve images.', 'lunara-film' ),
		'journal_archive_gallery_order_invalid'           => __( 'Give every archive gallery image one unique position.', 'lunara-film' ),
		'journal_archive_gallery_image_invalid'           => __( 'Every gallery choice must be an existing Media Library image.', 'lunara-film' ),
		'journal_archive_gallery_duplicate'               => __( 'Remove the duplicate image from the archive gallery.', 'lunara-film' ),
		'journal_archive_gallery_provenance_required'     => __( 'Every gallery image needs useful alt text, credit, source, and source URL.', 'lunara-film' ),
		'journal_archive_gallery_source_invalid'          => __( 'Every gallery source needs a safe HTTPS URL.', 'lunara-film' ),
		'journal_archive_gallery_link_invalid'            => __( 'Gallery image destinations must be safe HTTPS URLs.', 'lunara-film' ),
		'journal_archive_retention_order_invalid'        => __( 'Give the three retention cards unique positions 1 through 3.', 'lunara-film' ),
		'journal_archive_retention_copy_required'        => __( 'Each retention card needs its label, title, and copy.', 'lunara-film' ),
		'journal_archive_retention_destination_invalid' => __( 'Choose a supported retention destination.', 'lunara-film' ),
		'journal_archive_retention_url_invalid'         => __( 'A custom retention destination needs a complete URL.', 'lunara-film' ),
		'journal_archive_retention_image_invalid'       => __( 'Choose an image attachment from the Media Library.', 'lunara-film' ),
		'journal_archive_retention_image_provenance_required' => __( 'A retention image needs its credit, source, and source URL.', 'lunara-film' ),
		'journal_archive_retention_image_source_invalid' => __( 'Retention image sources must use a safe HTTPS URL.', 'lunara-film' ),
		'journal_archive_presentation_invalid'          => __( 'Choose one of the supported presentation presets.', 'lunara-film' ),
		'journal_archive_geometry_invalid'              => __( 'One geometry value is outside its displayed bounds.', 'lunara-film' ),
		'journal_archive_revision_not_found'            => __( 'That revision is no longer available to restore.', 'lunara-film' ),
	);
	$code = sanitize_key( (string) $code );
	return isset( $messages[ $code ] ) ? $messages[ $code ] : __( 'Review the highlighted Journal Archive fields and try again.', 'lunara-film' );
}

/**
 * Whether the current public request is in the Journal archive route family.
 *
 * @return bool
 */
function lunara_journal_archive_studio_is_journal_family_request() {
	return is_post_type_archive( 'journal' ) || is_tax( array( 'journal_section', 'journal_topic', 'journal_type' ) );
}

/**
 * Mark a response private before touching or validating a preview token.
 */
function lunara_journal_archive_studio_send_private_no_store() {
	nocache_headers();
	if ( ! headers_sent() ) {
		header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
	}
	do_action( 'lunara_journal_archive_preview_no_store_sent' );
}

/**
 * Prepare a testable response decision for a Journal preview-query URL.
 *
 * @param bool|null $is_journal_family Optional pre-query route decision.
 * @return array{handled:bool,authorized:bool,status:int,config:array<string,mixed>|false}
 */
function lunara_journal_archive_studio_prepare_preview_response( $is_journal_family = null ) {
	$is_journal_family = null === $is_journal_family ? lunara_journal_archive_studio_is_journal_family_request() : (bool) $is_journal_family;
	if ( ! isset( $_GET['lunara_journal_preview'] ) || ! $is_journal_family ) {
		return array( 'handled' => false, 'authorized' => true, 'status' => 200, 'config' => false );
	}

	// This must happen before capability, token, ownership, hash, or expiry work.
	lunara_journal_archive_studio_send_private_no_store();
	$token  = sanitize_text_field( wp_unslash( $_GET['lunara_journal_preview'] ) );
	$config = '' !== $token ? lunara_journal_archive_studio_get_preview_config( $token ) : false;
	if ( false === $config ) {
		return array( 'handled' => true, 'authorized' => false, 'status' => 403, 'config' => false );
	}
	return array( 'handled' => true, 'authorized' => true, 'status' => 200, 'config' => $config );
}

/**
 * Stop invalid Journal preview URLs before any public template can fall back.
 */
function lunara_journal_archive_studio_guard_preview_request() {
	$response = lunara_journal_archive_studio_prepare_preview_response();
	if ( empty( $response['handled'] ) || ! empty( $response['authorized'] ) ) {
		return;
	}
	status_header( 403 );
	wp_die(
		esc_html__( 'This private Journal Archive preview is unavailable or has expired.', 'lunara-film' ),
		esc_html__( 'Journal Archive Preview', 'lunara-film' ),
		array( 'response' => 403 )
	);
}
add_action( 'template_redirect', 'lunara_journal_archive_studio_guard_preview_request', 0 );

/**
 * Deny invalid preview tokens before the Journal query reaches Studio config.
 *
 * @param WP_Query $query Candidate main query.
 */
function lunara_journal_archive_studio_preflight_preview_query( $query ) {
	if ( is_admin() || ! isset( $_GET['lunara_journal_preview'] ) || ! $query->is_main_query() ) {
		return;
	}
	$is_family = $query->is_post_type_archive( 'journal' ) || $query->is_tax( array( 'journal_section', 'journal_topic', 'journal_type' ) );
	if ( ! $is_family ) {
		return;
	}
	$response = lunara_journal_archive_studio_prepare_preview_response( true );
	if ( ! empty( $response['authorized'] ) ) {
		return;
	}
	status_header( 403 );
	wp_die(
		esc_html__( 'This private Journal Archive preview is unavailable or has expired.', 'lunara-film' ),
		esc_html__( 'Journal Archive Preview', 'lunara-film' ),
		array( 'response' => 403 )
	);
}
add_action( 'pre_get_posts', 'lunara_journal_archive_studio_preflight_preview_query', 1 );

/**
 * Resolve the newest eligible Journal ID with one bounded, request-local query.
 *
 * The lookup deliberately suppresses query filters so the archive priority
 * hook cannot recurse into itself. A request-local cache is enough: publication
 * changes become visible on the next public request without a persistent-key
 * invalidation dependency.
 *
 * @return int
 */
function lunara_journal_archive_studio_get_newest_id() {
	static $newest_id = null;
	if ( null !== $newest_id ) {
		return $newest_id;
	}

	$ids = get_posts(
		array(
			'post_type'              => 'journal',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'orderby'                => array(
				'date' => 'DESC',
				'ID'   => 'DESC',
			),
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'cache_results'          => false,
			'suppress_filters'       => true,
		)
	);
	$newest_id = ! empty( $ids[0] ) ? lunara_journal_archive_studio_validate_post_id( $ids[0] ) : 0;
	return $newest_id;
}

/**
 * Resolve the active archive-only lead without changing the homepage owner.
 *
 * @param array<string,mixed>|null $config Configuration.
 * @return int
 */
function lunara_journal_archive_studio_get_lead_id( $config = null ) {
	$config = is_array( $config ) ? $config : lunara_journal_archive_studio_get_public_config();
	if ( 'automatic' === $config['lead_mode'] ) {
		return lunara_journal_archive_studio_get_newest_id();
	}
	if ( 'manual' === $config['lead_mode'] ) {
		return lunara_journal_archive_studio_validate_post_id( $config['lead_id'] );
	}
	$shared = function_exists( 'lunara_get_curated_journal_lead_id' ) ? absint( lunara_get_curated_journal_lead_id() ) : 0;
	$shared = lunara_journal_archive_studio_validate_post_id( $shared );
	return $shared ? $shared : lunara_journal_archive_studio_get_newest_id();
}

/**
 * Scope item count and deterministic curation to Journal main queries.
 *
 * @param WP_Query $query Query.
 * @return void
 */
function lunara_journal_archive_studio_configure_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$is_archive = $query->is_post_type_archive( 'journal' );
	$is_tax     = $query->is_tax( array( 'journal_section', 'journal_topic', 'journal_type' ) );
	if ( ! $is_archive && ! $is_tax ) {
		return;
	}
	$config = lunara_journal_archive_studio_get_public_config();
	$query->set( 'posts_per_page', absint( $config['item_count'] ) );
	// Taxonomy routes retain their native term query. On the canonical Journal
	// archive, lead and curated priority remains true for every supported sort;
	// the selected sort stays the SQL CASE expression's secondary order.
	if ( ! $is_archive ) {
		return;
	}
	$priority = array();
	$lead_id  = lunara_journal_archive_studio_get_lead_id( $config );
	if ( $lead_id ) {
		$priority[] = $lead_id;
	}
	if ( 'curated' === $config['lane_mode'] ) {
		foreach ( $config['curated_ids'] as $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id && ! in_array( $post_id, $priority, true ) ) {
				$priority[] = $post_id;
			}
		}
	}
	if ( $priority ) {
		$query->set( 'lunara_journal_archive_priority_ids', $priority );
	}
}
add_action( 'pre_get_posts', 'lunara_journal_archive_studio_configure_query', 13 );

/**
 * Prepend one stable CASE expression so pins remain unique across pagination.
 *
 * @param string   $orderby Existing SQL.
 * @param WP_Query $query Query.
 * @return string
 */
function lunara_journal_archive_studio_priority_orderby( $orderby, $query ) {
	$ids = $query->get( 'lunara_journal_archive_priority_ids' );
	if ( ! is_array( $ids ) || empty( $ids ) ) {
		return $orderby;
	}
	global $wpdb;
	$cases = array();
	foreach ( array_values( array_unique( array_map( 'absint', $ids ) ) ) as $position => $post_id ) {
		if ( $post_id ) {
			$cases[] = 'WHEN ' . $wpdb->posts . '.ID = ' . $post_id . ' THEN ' . $position;
		}
	}
	if ( empty( $cases ) ) {
		return $orderby;
	}
	$tie_direction = preg_match( '/\bASC\b/i', (string) $orderby ) ? 'ASC' : 'DESC';
	$stable_order  = trim( (string) $orderby );
	$id_pattern    = '/\b' . preg_quote( $wpdb->posts, '/' ) . '\.ID\s+(?:ASC|DESC)\b/i';
	if ( ! preg_match( $id_pattern, $stable_order ) ) {
		$stable_order .= ( '' !== $stable_order ? ', ' : '' ) . $wpdb->posts . '.ID ' . $tie_direction;
	}
	return 'CASE ' . implode( ' ', $cases ) . ' ELSE ' . count( $cases ) . ' END ASC, ' . $stable_order;
}
add_filter( 'posts_orderby', 'lunara_journal_archive_studio_priority_orderby', 20, 2 );

/**
 * Behavior-tested, server-rendered lane composition.
 *
 * @param array<string,string> $markup Lane markup.
 * @param array<int,string>    $order Saved order.
 * @param array<string,bool>   $visibility Saved visibility.
 * @return string
 */
function lunara_journal_archive_studio_render_sections( $markup, $order, $visibility ) {
	$output = empty( $visibility['hero'] ) && ! empty( $markup['fallback-h1'] ) ? $markup['fallback-h1'] : '';
	foreach ( $order as $slug ) {
		if ( empty( $visibility[ $slug ] ) || empty( $markup[ $slug ] ) ) {
			continue;
		}
		$output .= $markup[ $slug ];
	}
	return $output;
}

/**
 * Search a bounded published-Journal window for the private Studio pickers.
 *
 * Empty search returns the twenty newest eligible files. Numeric search is an
 * exact post-ID lookup; text search is title/content search handled by core.
 * IDs are hydrated only after the bounded query so no archive-wide post, meta,
 * or taxonomy cache is created.
 *
 * @param mixed $raw_search Search text or exact ID.
 * @param mixed $raw_limit  Requested result count (clamped to twenty).
 * @return array<int,WP_Post>
 */
function lunara_journal_archive_studio_search_posts( $raw_search = '', $raw_limit = 20 ) {
	$search = is_scalar( $raw_search ) ? trim( sanitize_text_field( (string) $raw_search ) ) : '';
	$limit  = is_scalar( $raw_limit ) ? absint( $raw_limit ) : 20;
	$limit  = max( 1, min( 20, $limit ) );
	$search = function_exists( 'mb_substr' ) ? mb_substr( $search, 0, 100 ) : substr( $search, 0, 100 );

	$args = array(
		'post_type'              => 'journal',
		'post_status'            => 'publish',
		'posts_per_page'         => $limit,
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'suppress_filters'       => true,
		'cache_results'          => false,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	);
	if ( '' !== $search ) {
		if ( ctype_digit( $search ) ) {
			$args['post__in'] = array( absint( $search ) );
			$args['orderby']  = 'post__in';
		} else {
			$args['s'] = $search;
		}
	}

	$posts = array();
	foreach ( (array) get_posts( $args ) as $post ) {
		if ( $post instanceof WP_Post && 'journal' === $post->post_type && 'publish' === $post->post_status ) {
			$posts[] = $post;
		}
	}
	return $posts;
}

/**
 * Return a bounded private Journal title/ID search result.
 *
 * @return void
 */
function lunara_journal_archive_studio_ajax_search_posts() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Theme editing permission is required.', 'lunara-film' ) ), 403 );
		return;
	}
	check_ajax_referer( 'lunara_journal_archive_studio_search', 'nonce' );

	$raw_search = isset( $_GET['q'] ) && is_scalar( $_GET['q'] ) ? wp_unslash( $_GET['q'] ) : '';
	$search     = trim( sanitize_text_field( (string) $raw_search ) );
	$search     = function_exists( 'mb_substr' ) ? mb_substr( $search, 0, 100 ) : substr( $search, 0, 100 );
	if ( '' === $search || ( ! ctype_digit( $search ) && strlen( $search ) < 2 ) ) {
		wp_send_json_success( array( 'items' => array() ) );
		return;
	}

	$items = array();
	foreach ( lunara_journal_archive_studio_search_posts( $search, 20 ) as $post ) {
		$items[] = array(
			'id'   => absint( $post->ID ),
			'text' => sprintf( '#%1$d — %2$s', $post->ID, get_the_title( $post ) ),
		);
	}
	wp_send_json_success( array( 'items' => $items ) );
}
add_action( 'wp_ajax_lunara_journal_archive_studio_search', 'lunara_journal_archive_studio_ajax_search_posts' );

/**
 * Recent published Journal choices plus configured older selections.
 *
 * @param array<string,mixed> $config Current config.
 * @return array<int,WP_Post>
 */
function lunara_journal_archive_studio_editor_posts( $config ) {
	$posts    = lunara_journal_archive_studio_search_posts( '', 20 );
	$required = array_slice( array_values( array_unique( array_filter( array_merge( array( absint( $config['lead_id'] ) ), array_map( 'absint', (array) $config['curated_ids'] ) ) ) ) ), 0, 25 );
	$by_id    = array();
	foreach ( $posts as $post ) {
		if ( $post instanceof WP_Post ) {
			$by_id[ $post->ID ] = $post;
		}
	}
	$missing = array_values( array_diff( $required, array_keys( $by_id ) ) );
	if ( $missing ) {
		$configured_posts = get_posts(
			array(
				'post_type'              => 'journal',
				'post_status'            => 'publish',
				'post__in'               => $missing,
				'posts_per_page'         => count( $missing ),
				'orderby'                => 'post__in',
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'suppress_filters'       => true,
				'cache_results'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		foreach ( (array) $configured_posts as $post ) {
			if ( $post instanceof WP_Post && 'journal' === $post->post_type && 'publish' === $post->post_status ) {
				$by_id[ $post->ID ] = $post;
			}
		}
	}
	return array_values( $by_id );
}

/**
 * Render the complete focused admin surface.
 *
 * @param string $context Return context.
 * @return void
 */
function lunara_journal_archive_studio_render_control_surface( $context = 'site-studio' ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		?>
		<section id="lunara-theme-studio-journal-archive-studio" class="lunara-control-desk-homepage-studio">
			<div class="lunara-control-desk-panel-header"><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Journal Archive Studio', 'lunara-film' ); ?></p><h3><?php esc_html_e( 'Theme editing permission is required', 'lunara-film' ); ?></h3></div>
		</section>
		<?php
		return;
	}

	$public_config = lunara_journal_archive_studio_get_public_config( false );
	$invalid_stage = lunara_journal_archive_studio_get_invalid_stage();
	$config        = is_array( $invalid_stage ) ? $invalid_stage : $public_config;
	$posts        = lunara_journal_archive_studio_editor_posts( $config );
	$active_id    = lunara_journal_archive_studio_get_lead_id( $config );
	$active_post  = $active_id ? get_post( $active_id ) : ( ! empty( $posts[0] ) ? $posts[0] : null );
	$shared_id    = function_exists( 'lunara_get_curated_journal_lead_id' ) ? absint( lunara_get_curated_journal_lead_id() ) : 0;
	$registry     = function_exists( 'lunara_get_journal_archive_section_registry' ) ? lunara_get_journal_archive_section_registry() : array();
	$positions    = array_flip( $config['section_order'] );
	if ( ! empty( $config['_staged_positions'] ) && is_array( $config['_staged_positions'] ) ) {
		foreach ( $config['_staged_positions'] as $slug => $position ) {
			$positions[ $slug ] = max( 0, absint( $position ) - 1 );
		}
	}
	$revisions    = lunara_journal_archive_studio_get_revisions();
	$context      = 'site-studio' === sanitize_key( (string) $context ) ? 'site-studio' : 'control-desk';
	$visual_url   = lunara_control_desk_admin_url(
		array(
			'tab'                     => 'journal-growth',
			'lcd_journal_visual_post' => $active_post instanceof WP_Post ? absint( $active_post->ID ) : 0,
		)
	) . '#lunara-journal-visual-file-manager';
	$checks       = $active_post instanceof WP_Post && function_exists( 'lunara_control_desk_get_journal_growth_checks' ) ? lunara_control_desk_get_journal_growth_checks( $active_post->ID ) : array();
	$needs_count  = 0;
	foreach ( $checks as $check ) {
		if ( ! empty( $check['state'] ) && 'ready' !== $check['state'] ) {
			$needs_count++;
		}
	}
	$featured_id  = $active_post instanceof WP_Post ? get_post_thumbnail_id( $active_post->ID ) : 0;
	$featured_dim = $featured_id && function_exists( 'lunara_control_desk_get_attachment_dimensions_label' ) ? lunara_control_desk_get_attachment_dimensions_label( $featured_id ) : '';
	$carousel_ids = $active_post instanceof WP_Post && function_exists( 'lunara_control_desk_get_journal_carousel_ids' ) ? lunara_control_desk_get_journal_carousel_ids( $active_post->ID ) : array();
	?>
	<section id="lunara-theme-studio-journal-archive-studio" class="lunara-control-desk-homepage-studio lunara-journal-archive-studio-admin">
		<div class="lunara-control-desk-panel-header">
			<p class="lunara-control-desk-kicker"><?php esc_html_e( 'Journal Archive Studio', 'lunara-film' ); ?></p>
			<h3><?php esc_html_e( 'Run the Journal without a code release', 'lunara-film' ); ?></h3>
			<p class="lunara-control-desk-subtle"><?php esc_html_e( 'This is the focused owner for archive identity, lead behavior, published curation, labels, lane order, retention, and responsive preview. It never publishes or schedules a Journal post.', 'lunara-film' ); ?></p>
		</div>

		<?php
		$quality_warnings   = array_intersect( (array) ( isset( $config['_warnings'] ) ? $config['_warnings'] : array() ), array( 'retention_image_wide_quality', 'gallery_image_wide_quality' ) );
		$reference_warnings = array_diff( (array) ( isset( $config['_warnings'] ) ? $config['_warnings'] : array() ), array( 'retention_image_wide_quality', 'gallery_image_wide_quality' ) );
		?>
		<?php if ( ! empty( $reference_warnings ) ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'The last valid archive remains live, but one referenced post or image is no longer eligible. The affected field has fallen back safely; review the highlighted selection before saving again.', 'lunara-film' ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! empty( $quality_warnings ) ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'One selected wide image is below the 1920×1080 target or is not close to 16:9. It remains live without stretching inside the intentional crop chamber; replace it with a stronger wide source when practical.', 'lunara-film' ); ?></p></div>
		<?php endif; ?>
		<?php if ( is_array( $invalid_stage ) ) : ?>
			<div class="notice notice-error inline"><p><strong><?php esc_html_e( 'Your rejected edits are restored below and remain private.', 'lunara-film' ); ?></strong> <?php echo esc_html( lunara_journal_archive_studio_validation_message() ); ?> <?php esc_html_e( 'The last valid public Journal is unchanged.', 'lunara-film' ); ?></p></div>
		<?php endif; ?>

		<form class="lunara-control-desk-homepage-form lunara-journal-archive-studio-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="lunara_save_journal_archive_studio" />
			<input type="hidden" name="lunara_journal_archive_return" value="<?php echo esc_attr( $context ); ?>" />
			<?php wp_nonce_field( 'lunara_save_journal_archive_studio', 'lunara_journal_archive_nonce' ); ?>
			<?php wp_nonce_field( 'lunara_preview_journal_archive_studio', 'lunara_journal_archive_preview_nonce' ); ?>

			<div class="lunara-control-desk-homepage-card">
				<div class="lunara-control-desk-card-head"><div><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Archive Identity', 'lunara-film' ); ?></p><h3><?php esc_html_e( 'Kicker, headline, deck, and supporting copy', 'lunara-film' ); ?></h3><p class="lunara-control-desk-subtle"><?php esc_html_e( 'These write through to the existing Editorial Archives owner; exact words are preserved.', 'lunara-film' ); ?></p></div></div>
				<div class="lunara-control-desk-homepage-number-grid">
					<label><span><strong><?php esc_html_e( 'Kicker', 'lunara-film' ); ?></strong></span><input type="text" maxlength="80" name="lunara_journal_archive_identity[kicker]" value="<?php echo esc_attr( $config['kicker'] ); ?>" required /></label>
					<label><span><strong><?php esc_html_e( 'Headline', 'lunara-film' ); ?></strong></span><input type="text" maxlength="140" name="lunara_journal_archive_identity[title]" value="<?php echo esc_attr( $config['title'] ); ?>" required /></label>
				</div>
				<label class="lunara-journal-archive-wide-field"><span><strong><?php esc_html_e( 'Deck', 'lunara-film' ); ?></strong></span><textarea maxlength="500" rows="3" name="lunara_journal_archive_identity[deck]"><?php echo esc_textarea( $config['deck'] ); ?></textarea></label>
				<label class="lunara-journal-archive-wide-field"><span><strong><?php esc_html_e( 'Supporting copy', 'lunara-film' ); ?></strong><small><?php esc_html_e( 'Appears with the archive command framing when supplied.', 'lunara-film' ); ?></small></span><textarea maxlength="700" rows="3" name="lunara_journal_archive_identity[supporting_copy]"><?php echo esc_textarea( $config['supporting_copy'] ); ?></textarea></label>
			</div>

			<div class="lunara-control-desk-homepage-grid">
				<div class="lunara-control-desk-homepage-card">
					<div class="lunara-control-desk-card-head"><div><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Lead Curator', 'lunara-film' ); ?></p><h3><?php esc_html_e( 'Choose who owns the first file', 'lunara-film' ); ?></h3></div></div>
					<fieldset class="lunara-control-desk-homepage-choice"><legend><strong><?php esc_html_e( 'Lead behavior', 'lunara-film' ); ?></strong></legend><div class="lunara-control-desk-homepage-choice-options">
						<?php
						$lead_modes = array(
							'shared'    => array( __( 'Shared homepage lead', 'lunara-film' ), $shared_id ? sprintf( __( 'Currently Journal #%d.', 'lunara-film' ), $shared_id ) : __( 'Homepage owner is automatic.', 'lunara-film' ) ),
							'automatic' => array( __( 'Automatic newest', 'lunara-film' ), __( 'The newest eligible published file leads this archive only.', 'lunara-film' ) ),
							'manual'    => array( __( 'Journal-only manual lead', 'lunara-film' ), __( 'Pin one published file without changing the homepage.', 'lunara-film' ) ),
						);
						foreach ( $lead_modes as $mode => $mode_copy ) : ?>
							<label class="<?php echo $config['lead_mode'] === $mode ? 'is-selected' : ''; ?>"><input type="radio" name="lunara_journal_archive_lead_mode" value="<?php echo esc_attr( $mode ); ?>" <?php checked( $config['lead_mode'], $mode ); ?> /><span><strong><?php echo esc_html( $mode_copy[0] ); ?></strong><small><?php echo esc_html( $mode_copy[1] ); ?></small></span></label>
						<?php endforeach; ?>
					</div></fieldset>
					<label><span><strong><?php esc_html_e( 'Find a published lead', 'lunara-film' ); ?></strong><small><?php esc_html_e( 'Twenty recent choices load first; type at least two title characters or an exact ID to search every published Journal file.', 'lunara-film' ); ?></small></span><input type="search" data-lunara-journal-post-filter="#lunara-journal-archive-lead-id" placeholder="<?php esc_attr_e( 'Search title or ID', 'lunara-film' ); ?>" /><small data-lunara-journal-post-search-status aria-live="polite"></small></label>
					<label><span><strong><?php esc_html_e( 'Manual lead file', 'lunara-film' ); ?></strong></span><select id="lunara-journal-archive-lead-id" name="lunara_journal_archive_lead_id"><option value="0"><?php esc_html_e( 'Choose a published Journal file', 'lunara-film' ); ?></option><?php foreach ( $posts as $journal_post ) : ?><option value="<?php echo esc_attr( $journal_post->ID ); ?>" <?php selected( $config['lead_id'], $journal_post->ID ); ?>><?php echo esc_html( sprintf( '#%1$d — %2$s', $journal_post->ID, get_the_title( $journal_post ) ) ); ?></option><?php endforeach; ?></select></label>
				</div>

				<div class="lunara-control-desk-homepage-card">
					<div class="lunara-control-desk-card-head"><div><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Archive Run', 'lunara-film' ); ?></p><h3><?php esc_html_e( 'Automatic query or curated selection', 'lunara-film' ); ?></h3><p class="lunara-control-desk-subtle"><?php esc_html_e( 'Both modes remain published-only. Curated files lead in the selected list order, then the normal query fills the page without duplicates.', 'lunara-film' ); ?></p></div></div>
					<div class="lunara-control-desk-homepage-choice-options">
						<label class="<?php echo 'query' === $config['lane_mode'] ? 'is-selected' : ''; ?>"><input type="radio" name="lunara_journal_archive_lane_mode" value="query" <?php checked( 'query', $config['lane_mode'] ); ?> /><span><strong><?php esc_html_e( 'Automatic query', 'lunara-film' ); ?></strong><small><?php esc_html_e( 'Newest published Journal files, following the active sort.', 'lunara-film' ); ?></small></span></label>
						<label class="<?php echo 'curated' === $config['lane_mode'] ? 'is-selected' : ''; ?>"><input type="radio" name="lunara_journal_archive_lane_mode" value="curated" <?php checked( 'curated', $config['lane_mode'] ); ?> /><span><strong><?php esc_html_e( 'Curated selection', 'lunara-film' ); ?></strong><small><?php esc_html_e( 'Selected published files first; automatic query fills the rest.', 'lunara-film' ); ?></small></span></label>
					</div>
					<label><span><strong><?php esc_html_e( 'Items per Journal page', 'lunara-film' ); ?></strong><small><?php esc_html_e( 'Journal only; does not change WordPress global reading settings.', 'lunara-film' ); ?></small></span><input type="number" name="lunara_journal_archive_item_count" min="4" max="24" step="1" value="<?php echo esc_attr( $config['item_count'] ); ?>" /></label>
					<div class="lunara-journal-curation-builder" data-lunara-journal-curation>
						<label><span><strong><?php esc_html_e( 'Find any published Journal file', 'lunara-film' ); ?></strong><small><?php esc_html_e( 'Twenty recent choices load first; type at least two title characters or an exact ID to search every eligible published file.', 'lunara-film' ); ?></small></span><input type="search" data-lunara-journal-post-filter="#lunara-journal-curated-picker" placeholder="<?php esc_attr_e( 'Search title or ID', 'lunara-film' ); ?>" /><small data-lunara-journal-post-search-status aria-live="polite"></small></label>
						<div class="lunara-control-desk-actions"><select id="lunara-journal-curated-picker" data-lunara-journal-curated-picker><option value="0"><?php esc_html_e( 'Choose a published file', 'lunara-film' ); ?></option><?php foreach ( $posts as $journal_post ) : ?><option value="<?php echo esc_attr( $journal_post->ID ); ?>"><?php echo esc_html( sprintf( '#%1$d — %2$s', $journal_post->ID, get_the_title( $journal_post ) ) ); ?></option><?php endforeach; ?></select><button type="button" class="button" data-lunara-journal-curated-add><?php esc_html_e( 'Add to curated run', 'lunara-film' ); ?></button></div>
						<ol class="lunara-journal-curated-list" data-lunara-journal-curated-list aria-label="<?php esc_attr_e( 'Curated Journal order', 'lunara-film' ); ?>">
							<?php foreach ( $config['curated_ids'] as $curated_id ) : $curated_post = get_post( $curated_id ); if ( ! $curated_post instanceof WP_Post ) { continue; } ?><li data-lunara-journal-curated-item data-post-id="<?php echo esc_attr( $curated_id ); ?>"><span><?php echo esc_html( sprintf( '#%1$d — %2$s', $curated_id, get_the_title( $curated_post ) ) ); ?></span><input type="hidden" name="lunara_journal_archive_curated_ids[]" value="<?php echo esc_attr( $curated_id ); ?>" /><span class="lunara-control-desk-actions"><button type="button" class="button button-small" data-lunara-journal-curated-move="up"><?php esc_html_e( 'Up', 'lunara-film' ); ?></button><button type="button" class="button button-small" data-lunara-journal-curated-move="down"><?php esc_html_e( 'Down', 'lunara-film' ); ?></button><button type="button" class="button button-small" data-lunara-journal-curated-remove><?php esc_html_e( 'Remove', 'lunara-film' ); ?></button></span></li><?php endforeach; ?>
						</ol>
						<p class="lunara-control-desk-subtle"><?php esc_html_e( 'Up and Down define the exact server-rendered priority order. Buttons are keyboard accessible; duplicates are refused.', 'lunara-film' ); ?></p>
					</div>
				</div>
			</div>

			<div class="lunara-control-desk-homepage-card">
				<div class="lunara-control-desk-card-head"><div><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Current Public File', 'lunara-film' ); ?></p><h3><?php echo $active_post instanceof WP_Post ? esc_html( get_the_title( $active_post ) ) : esc_html__( 'No eligible published Journal file', 'lunara-film' ); ?></h3><p class="lunara-control-desk-subtle"><?php esc_html_e( 'Status and links are read from the existing Journal post, featured-image, carousel, and provenance owners. This Studio does not duplicate them.', 'lunara-film' ); ?></p></div></div>
				<div class="lunara-control-desk-journal-checks">
					<article class="lunara-control-desk-journal-check is-<?php echo $needs_count ? 'weak' : 'ready'; ?>"><div><strong><?php esc_html_e( 'Validator', 'lunara-film' ); ?></strong><span><?php echo esc_html( $active_post instanceof WP_Post ? ( $needs_count ? sprintf( _n( '%d focused check needs review', '%d focused checks need review', $needs_count, 'lunara-film' ), $needs_count ) : __( 'Focused checks passed', 'lunara-film' ) ) : __( 'No eligible file', 'lunara-film' ) ); ?></span></div><p><?php esc_html_e( 'Only published Journal posts can be selected; automation receives no publication authority here.', 'lunara-film' ); ?></p></article>
					<article class="lunara-control-desk-journal-check is-<?php echo $featured_id ? 'ready' : 'needs'; ?>"><div><strong><?php esc_html_e( 'Featured image', 'lunara-film' ); ?></strong><span><?php echo $featured_id ? esc_html( sprintf( '#%1$d%2$s', $featured_id, $featured_dim ? ' / ' . $featured_dim : '' ) ) : esc_html__( 'Missing', 'lunara-film' ); ?></span></div><p><?php esc_html_e( 'Archive cards continue to use the post featured image and Media Library alt/provenance owners.', 'lunara-film' ); ?></p></article>
					<article class="lunara-control-desk-journal-check is-ready"><div><strong><?php esc_html_e( 'Visual File Manager', 'lunara-film' ); ?></strong><span><?php echo esc_html( sprintf( _n( '%d carousel image', '%d carousel images', count( $carousel_ids ), 'lunara-film' ), count( $carousel_ids ) ) ); ?></span></div><p><?php esc_html_e( 'The single-entry gallery remains post-owned and editable in its existing manager.', 'lunara-film' ); ?></p><a href="<?php echo esc_url( $visual_url ); ?>"><?php esc_html_e( 'Open Visual File Manager', 'lunara-film' ); ?></a></article>
				</div>
				<?php if ( $active_post instanceof WP_Post ) : ?><div class="lunara-control-desk-actions"><a class="button" href="<?php echo esc_url( get_edit_post_link( $active_post->ID, 'raw' ) ); ?>"><?php esc_html_e( 'Edit file, featured image, and post metadata', 'lunara-film' ); ?></a><a class="button" href="<?php echo esc_url( get_permalink( $active_post ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View public file', 'lunara-film' ); ?></a></div><?php endif; ?>
			</div>

			<div class="lunara-control-desk-homepage-card">
				<div class="lunara-control-desk-card-head"><div><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Section Composer', 'lunara-film' ); ?></p><h3><?php esc_html_e( 'Visibility and true server-rendered order', 'lunara-film' ); ?></h3><p class="lunara-control-desk-subtle"><?php esc_html_e( 'All seven lanes move in the HTML itself. Hiding the visual Hero retains one accessible page heading.', 'lunara-film' ); ?></p></div></div>
				<div class="lunara-journal-archive-section-grid">
				<?php foreach ( $registry as $slug => $spec ) : ?>
					<article class="lunara-journal-archive-section-control"><input type="hidden" name="lunara_journal_archive_section_visibility[<?php echo esc_attr( $slug ); ?>]" value="0" /><label><input type="checkbox" name="lunara_journal_archive_section_visibility[<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( ! empty( $config['section_visibility'][ $slug ] ) ); ?> /> <strong><?php echo esc_html( $spec['label'] ); ?></strong></label><small><?php echo esc_html( $spec['description'] ); ?></small><label><span><?php esc_html_e( 'Position', 'lunara-film' ); ?></span><select name="lunara_journal_archive_section_positions[<?php echo esc_attr( $slug ); ?>]"><?php for ( $position = 1; $position <= count( $registry ); $position++ ) : ?><option value="<?php echo esc_attr( $position ); ?>" <?php selected( isset( $positions[ $slug ] ) ? $positions[ $slug ] + 1 : 99, $position ); ?>><?php echo esc_html( $position ); ?></option><?php endfor; ?></select></label></article>
				<?php endforeach; ?>
				</div>
				<div class="lunara-control-desk-homepage-number-grid">
					<label><span><strong><?php esc_html_e( 'Section terms shown', 'lunara-film' ); ?></strong></span><input type="number" min="1" max="20" name="lunara_journal_archive_filter_caps[journal_section]" value="<?php echo esc_attr( $config['filter_caps']['journal_section'] ); ?>" /></label>
					<label><span><strong><?php esc_html_e( 'Topic terms shown', 'lunara-film' ); ?></strong></span><input type="number" min="1" max="20" name="lunara_journal_archive_filter_caps[journal_topic]" value="<?php echo esc_attr( $config['filter_caps']['journal_topic'] ); ?>" /></label>
					<label><span><strong><?php esc_html_e( 'Legacy archive types shown', 'lunara-film' ); ?></strong></span><input type="number" min="1" max="20" name="lunara_journal_archive_filter_caps[journal_type]" value="<?php echo esc_attr( $config['filter_caps']['journal_type'] ); ?>" /></label>
				</div>
				<p class="lunara-control-desk-subtle"><?php esc_html_e( 'Term names, descriptions, and Journal assignments remain owned by the standard WordPress taxonomies. This Studio changes only the visible term caps and archive framing labels.', 'lunara-film' ); ?></p>
			</div>

			<div class="lunara-control-desk-homepage-card">
				<div class="lunara-control-desk-card-head"><div><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Public Language', 'lunara-film' ); ?></p><h3><?php esc_html_e( 'Desk, card, retention, and pagination labels', 'lunara-film' ); ?></h3></div></div>
				<div class="lunara-journal-archive-label-grid"><?php foreach ( $config['labels'] as $label_key => $label_value ) : ?><label><span><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $label_key ) ) ); ?></strong></span><input type="text" maxlength="120" name="lunara_journal_archive_labels[<?php echo esc_attr( $label_key ); ?>]" value="<?php echo esc_attr( $label_value ); ?>" required /></label><?php endforeach; ?></div>
			</div>

			<div class="lunara-control-desk-homepage-card">
				<div class="lunara-control-desk-card-head"><div><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Retention Cards', 'lunara-film' ); ?></p><h3><?php esc_html_e( 'Three independently visible, ordered continuation routes', 'lunara-film' ); ?></h3><p class="lunara-control-desk-subtle"><?php esc_html_e( 'Images are optional. Empty image fields preserve the current text-only public design.', 'lunara-film' ); ?></p></div></div>
				<div class="lunara-journal-archive-retention-editor">
				<?php foreach ( $config['retention'] as $index => $card ) : ?>
					<article class="lunara-control-desk-homepage-card lunara-journal-retention-editor-card">
						<input type="hidden" name="lunara_journal_archive_retention[<?php echo esc_attr( $index ); ?>][visible]" value="0" />
						<label><input type="checkbox" name="lunara_journal_archive_retention[<?php echo esc_attr( $index ); ?>][visible]" value="1" <?php checked( ! empty( $card['visible'] ) ); ?> /> <strong><?php echo esc_html( sprintf( __( 'Show retention card %d', 'lunara-film' ), $index + 1 ) ); ?></strong></label>
						<label><span><?php esc_html_e( 'Order', 'lunara-film' ); ?></span><select name="lunara_journal_archive_retention[<?php echo esc_attr( $index ); ?>][order]"><?php for ( $card_order = 1; $card_order <= 3; $card_order++ ) : ?><option value="<?php echo esc_attr( $card_order ); ?>" <?php selected( $card['order'], $card_order ); ?>><?php echo esc_html( $card_order ); ?></option><?php endfor; ?></select></label>
						<label><span><?php esc_html_e( 'Label', 'lunara-film' ); ?></span><input type="text" maxlength="80" name="lunara_journal_archive_retention[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $card['label'] ); ?>" required /></label>
						<label><span><?php esc_html_e( 'Title', 'lunara-film' ); ?></span><input type="text" maxlength="140" name="lunara_journal_archive_retention[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $card['title'] ); ?>" required /></label>
						<label><span><?php esc_html_e( 'Copy', 'lunara-film' ); ?></span><textarea maxlength="360" rows="3" name="lunara_journal_archive_retention[<?php echo esc_attr( $index ); ?>][copy]" required><?php echo esc_textarea( $card['copy'] ); ?></textarea></label>
						<label><span><?php esc_html_e( 'Destination', 'lunara-film' ); ?></span><select name="lunara_journal_archive_retention[<?php echo esc_attr( $index ); ?>][destination]"><?php foreach ( array( 'latest' => __( 'Latest Journal file', 'lunara-film' ), 'trailer' => __( 'Trailer lane', 'lunara-film' ), 'reviews' => __( 'Reviews archive', 'lunara-film' ), 'journal' => __( 'Journal archive', 'lunara-film' ), 'custom' => __( 'Custom secure URL', 'lunara-film' ) ) as $destination => $destination_label ) : ?><option value="<?php echo esc_attr( $destination ); ?>" <?php selected( $card['destination'], $destination ); ?>><?php echo esc_html( $destination_label ); ?></option><?php endforeach; ?></select></label>
						<label><span><?php esc_html_e( 'Custom destination URL', 'lunara-film' ); ?></span><input type="url" name="lunara_journal_archive_retention[<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_attr( $card['url'] ); ?>" placeholder="https://" /></label>
						<?php if ( function_exists( 'lunara_control_desk_render_brand_media_control' ) ) { lunara_control_desk_render_brand_media_control( array( 'value' => $card['image_id'], 'field' => sprintf( 'lunara_journal_archive_retention[%d][image_id]', $index ), 'eyebrow' => __( 'Optional Image', 'lunara-film' ), 'label' => __( 'Retention card image', 'lunara-film' ), 'note' => __( 'Choose, replace, or clear a Media Library image.', 'lunara-film' ), 'affects' => __( 'This retention card only.', 'lunara-film' ), 'picker_title' => __( 'Choose a retention image', 'lunara-film' ), 'picker_button' => __( 'Use this image', 'lunara-film' ) ) ); } ?>
						<label><span><?php esc_html_e( 'Image alt', 'lunara-film' ); ?></span><input type="text" maxlength="180" name="lunara_journal_archive_retention[<?php echo esc_attr( $index ); ?>][image_alt]" value="<?php echo esc_attr( $card['image_alt'] ); ?>" /></label>
						<label><span><?php esc_html_e( 'Image credit', 'lunara-film' ); ?></span><input type="text" maxlength="180" name="lunara_journal_archive_retention[<?php echo esc_attr( $index ); ?>][image_credit]" value="<?php echo esc_attr( $card['image_credit'] ); ?>" /></label>
						<label><span><?php esc_html_e( 'Image source', 'lunara-film' ); ?></span><input type="text" maxlength="180" name="lunara_journal_archive_retention[<?php echo esc_attr( $index ); ?>][image_source]" value="<?php echo esc_attr( $card['image_source'] ); ?>" /></label>
						<label><span><?php esc_html_e( 'Image source URL', 'lunara-film' ); ?></span><input type="url" name="lunara_journal_archive_retention[<?php echo esc_attr( $index ); ?>][image_source_url]" value="<?php echo esc_attr( $card['image_source_url'] ); ?>" /></label>
						<div class="lunara-control-desk-homepage-number-grid"><label><span><?php esc_html_e( 'Focal X', 'lunara-film' ); ?></span><input type="number" min="0" max="100" name="lunara_journal_archive_retention[<?php echo esc_attr( $index ); ?>][focal_x]" value="<?php echo esc_attr( $card['focal_x'] ); ?>" /></label><label><span><?php esc_html_e( 'Focal Y', 'lunara-film' ); ?></span><input type="number" min="0" max="100" name="lunara_journal_archive_retention[<?php echo esc_attr( $index ); ?>][focal_y]" value="<?php echo esc_attr( $card['focal_y'] ); ?>" /></label></div>
					</article>
				<?php endforeach; ?>
				</div>
			</div>

			<div class="lunara-control-desk-homepage-card" data-lunara-journal-archive-gallery-form>
				<div class="lunara-control-desk-card-head"><div><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Archive Gallery', 'lunara-film' ); ?></p><h3><?php esc_html_e( 'A bounded visual sequence after the retention cards', 'lunara-film' ); ?></h3><p class="lunara-control-desk-subtle"><?php esc_html_e( 'Choose up to twelve Media Library images, then move, replace, remove, or clear them here. An empty gallery adds nothing to the public page. Wide media should target 1920×1080; smaller legacy sources stay proportional and surface a warning.', 'lunara-film' ); ?></p></div></div>
				<div class="lunara-control-desk-homepage-number-grid">
					<label><span><strong><?php esc_html_e( 'Gallery kicker', 'lunara-film' ); ?></strong></span><input type="text" maxlength="80" name="lunara_journal_archive_gallery[kicker]" value="<?php echo esc_attr( $config['gallery']['kicker'] ); ?>" required /></label>
					<label><span><strong><?php esc_html_e( 'Gallery heading', 'lunara-film' ); ?></strong></span><input type="text" maxlength="140" name="lunara_journal_archive_gallery[title]" value="<?php echo esc_attr( $config['gallery']['title'] ); ?>" required /></label>
				</div>
				<label class="lunara-journal-archive-wide-field"><span><strong><?php esc_html_e( 'Gallery introduction', 'lunara-film' ); ?></strong></span><textarea maxlength="500" rows="3" name="lunara_journal_archive_gallery[copy]"><?php echo esc_textarea( $config['gallery']['copy'] ); ?></textarea></label>
				<input type="hidden" name="lunara_journal_archive_gallery_ids" value="<?php echo esc_attr( implode( ',', array_map( 'absint', array_column( $config['gallery']['items'], 'attachment_id' ) ) ) ); ?>" data-lunara-journal-archive-gallery-ids />
				<div class="lunara-control-desk-actions"><button type="button" class="button button-secondary" data-lunara-journal-archive-gallery-picker><?php esc_html_e( 'Add Images', 'lunara-film' ); ?></button><button type="button" class="button" data-lunara-journal-archive-gallery-clear><?php esc_html_e( 'Clear Gallery', 'lunara-film' ); ?></button></div>
				<div class="lunara-control-desk-carousel-list lunara-journal-archive-gallery-editor" data-lunara-journal-archive-gallery-list>
					<?php if ( empty( $config['gallery']['items'] ) ) : ?><div class="lunara-control-desk-empty" data-lunara-journal-archive-gallery-empty><p><?php esc_html_e( 'No archive gallery images selected. The public Journal has no gallery wrapper or reserved space.', 'lunara-film' ); ?></p></div><?php endif; ?>
					<?php foreach ( $config['gallery']['items'] as $gallery_item ) : $gallery_id = absint( $gallery_item['attachment_id'] ); ?>
						<article class="lunara-control-desk-carousel-item lunara-journal-archive-gallery-editor-item" data-lunara-journal-archive-gallery-item data-attachment-id="<?php echo esc_attr( $gallery_id ); ?>">
							<div class="lunara-control-desk-carousel-thumb"><?php echo wp_get_attachment_image( $gallery_id, 'thumbnail', false, array( 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							<div class="lunara-control-desk-carousel-copy"><div class="lunara-control-desk-carousel-title-row"><div><strong data-lunara-journal-archive-gallery-item-title><?php echo esc_html( get_the_title( $gallery_id ) ); ?></strong><span><?php echo esc_html( sprintf( __( 'Attachment #%d', 'lunara-film' ), $gallery_id ) ); ?></span></div><div class="lunara-control-desk-carousel-controls"><button type="button" class="button button-small" data-lunara-journal-archive-gallery-move="up"><?php esc_html_e( 'Up', 'lunara-film' ); ?></button><button type="button" class="button button-small" data-lunara-journal-archive-gallery-move="down"><?php esc_html_e( 'Down', 'lunara-film' ); ?></button><button type="button" class="button button-small" data-lunara-journal-archive-gallery-replace><?php esc_html_e( 'Replace', 'lunara-film' ); ?></button><button type="button" class="button button-small" data-lunara-journal-archive-gallery-remove><?php esc_html_e( 'Remove', 'lunara-film' ); ?></button></div></div>
								<div class="lunara-control-desk-carousel-fields">
									<label><span><?php esc_html_e( 'Alt text', 'lunara-film' ); ?></span><input data-lunara-journal-archive-gallery-field="alt" type="text" maxlength="180" name="lunara_journal_archive_gallery_alt[<?php echo esc_attr( $gallery_id ); ?>]" value="<?php echo esc_attr( $gallery_item['alt'] ); ?>" required /></label>
									<label><span><?php esc_html_e( 'Caption', 'lunara-film' ); ?></span><textarea data-lunara-journal-archive-gallery-field="caption" maxlength="360" rows="2" name="lunara_journal_archive_gallery_caption[<?php echo esc_attr( $gallery_id ); ?>]"><?php echo esc_textarea( $gallery_item['caption'] ); ?></textarea></label>
									<label><span><?php esc_html_e( 'Optional image link', 'lunara-film' ); ?></span><input data-lunara-journal-archive-gallery-field="link_url" type="url" maxlength="2048" name="lunara_journal_archive_gallery_link_url[<?php echo esc_attr( $gallery_id ); ?>]" value="<?php echo esc_attr( $gallery_item['link_url'] ); ?>" placeholder="https://" /></label>
									<label><span><?php esc_html_e( 'Credit', 'lunara-film' ); ?></span><input data-lunara-journal-archive-gallery-field="credit" type="text" maxlength="180" name="lunara_journal_archive_gallery_credit[<?php echo esc_attr( $gallery_id ); ?>]" value="<?php echo esc_attr( $gallery_item['credit'] ); ?>" required /></label>
									<label><span><?php esc_html_e( 'Source name', 'lunara-film' ); ?></span><input data-lunara-journal-archive-gallery-field="source" type="text" maxlength="180" name="lunara_journal_archive_gallery_source[<?php echo esc_attr( $gallery_id ); ?>]" value="<?php echo esc_attr( $gallery_item['source'] ); ?>" required /></label>
									<label><span><?php esc_html_e( 'Source URL', 'lunara-film' ); ?></span><input data-lunara-journal-archive-gallery-field="source_url" type="url" maxlength="2048" name="lunara_journal_archive_gallery_source_url[<?php echo esc_attr( $gallery_id ); ?>]" value="<?php echo esc_attr( $gallery_item['source_url'] ); ?>" placeholder="https://" required /></label>
									<label><span><?php esc_html_e( 'Focal X', 'lunara-film' ); ?></span><input data-lunara-journal-archive-gallery-field="focal_x" type="number" min="0" max="100" name="lunara_journal_archive_gallery_focal_x[<?php echo esc_attr( $gallery_id ); ?>]" value="<?php echo esc_attr( $gallery_item['focal_x'] ); ?>" /></label>
									<label><span><?php esc_html_e( 'Focal Y', 'lunara-film' ); ?></span><input data-lunara-journal-archive-gallery-field="focal_y" type="number" min="0" max="100" name="lunara_journal_archive_gallery_focal_y[<?php echo esc_attr( $gallery_id ); ?>]" value="<?php echo esc_attr( $gallery_item['focal_y'] ); ?>" /></label>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="lunara-control-desk-homepage-grid">
				<div class="lunara-control-desk-homepage-card"><div class="lunara-control-desk-card-head"><div><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Editorial Rhythm', 'lunara-film' ); ?></p><h3><?php esc_html_e( 'Density, lead, and desk emphasis', 'lunara-film' ); ?></h3></div></div><div class="lunara-control-desk-homepage-choice-grid"><?php foreach ( lunara_control_desk_journal_archive_select_specs() as $key => $spec ) : ?><?php lunara_control_desk_render_journal_archive_select_control( $key, $spec ); ?><?php endforeach; ?></div></div>
				<div class="lunara-control-desk-homepage-card"><div class="lunara-control-desk-card-head"><div><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Geometry', 'lunara-film' ); ?></p><h3><?php esc_html_e( 'Bounded spacing and media chambers', 'lunara-film' ); ?></h3></div></div><div class="lunara-control-desk-homepage-number-grid"><?php foreach ( lunara_control_desk_journal_archive_number_specs() as $key => $spec ) : ?><?php lunara_control_desk_render_journal_archive_number_control( $key, $spec ); ?><?php endforeach; ?></div></div>
			</div>

			<div class="lunara-control-desk-homepage-footer"><div><strong><?php esc_html_e( 'Last-valid promotion', 'lunara-film' ); ?></strong><span><?php esc_html_e( 'Invalid input changes nothing public. Preview is private and expires after 30 minutes.', 'lunara-film' ); ?></span></div><div class="lunara-control-desk-actions"><button type="submit" class="button button-primary"><?php esc_html_e( 'Validate and Save Public Configuration', 'lunara-film' ); ?></button><button type="submit" class="button" name="action" value="lunara_preview_journal_archive_studio" formtarget="_blank"><?php esc_html_e( 'Preview unsaved desktop + mobile', 'lunara-film' ); ?></button><a class="button" href="<?php echo esc_url( home_url( '/journal/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open current Journal', 'lunara-film' ); ?></a></div></div>
		</form>

		<div class="lunara-control-desk-homepage-card lunara-journal-archive-history">
			<div class="lunara-control-desk-card-head"><div><p class="lunara-control-desk-kicker"><?php esc_html_e( 'Configuration History', 'lunara-film' ); ?></p><h3><?php esc_html_e( 'Restore a prior valid public state', 'lunara-film' ); ?></h3><p class="lunara-control-desk-subtle"><?php esc_html_e( 'Up to twelve prior-public snapshots retain who changed the Studio, when, why, and the validator result.', 'lunara-film' ); ?></p></div></div>
			<?php if ( empty( $revisions ) ) : ?><p><?php esc_html_e( 'No Journal Archive Studio revisions exist yet.', 'lunara-film' ); ?></p><?php else : ?><div class="lunara-journal-archive-revision-list"><?php foreach ( $revisions as $revision ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="lunara_restore_journal_archive_studio" /><input type="hidden" name="lunara_journal_archive_revision_id" value="<?php echo esc_attr( $revision['id'] ); ?>" /><?php wp_nonce_field( 'lunara_restore_journal_archive_studio', 'lunara_journal_archive_restore_nonce' ); ?><span><strong><?php echo esc_html( $revision['saved_at'] ); ?></strong><small><?php echo esc_html( sprintf( __( 'User %1$d / %2$s / validator %3$s', 'lunara-film' ), absint( $revision['saved_by'] ), $revision['action'], $revision['validator_result'] ) ); ?></small></span><button type="submit" class="button" onclick="return confirm('<?php echo esc_js( __( 'Restore this prior public Journal configuration?', 'lunara-film' ) ); ?>');"><?php esc_html_e( 'Restore this revision', 'lunara-film' ); ?></button></form><?php endforeach; ?></div><?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Focused save handler. Validation happens before any public owner is touched.
 */
function lunara_control_desk_save_journal_archive_studio() {
	$redirect = function_exists( 'lunara_control_desk_bounded_return_url' )
		? lunara_control_desk_bounded_return_url( 'lunara_journal_archive_return', 'journal-archive', admin_url( 'admin.php?page=lunara-site-studio&surface=journal-archive' ) )
		: admin_url( 'admin.php?page=lunara-site-studio&surface=journal-archive' );
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_safe_redirect( add_query_arg( 'lunara_notice', 'journal_archive_studio_forbidden', $redirect ) );
		exit;
	}
	check_admin_referer( 'lunara_save_journal_archive_studio', 'lunara_journal_archive_nonce' );
	$candidate = lunara_journal_archive_studio_config_from_request( $_POST );
	$result    = lunara_journal_archive_studio_promote_config( $candidate, 'save' );
	if ( is_wp_error( $result ) ) {
		lunara_journal_archive_studio_store_invalid_stage( $candidate, $_POST, $result->get_error_code() );
		$notice = 'journal_archive_studio_invalid';
	} else {
		lunara_journal_archive_studio_clear_invalid_stage();
		$notice = 'journal_archive_studio_saved';
	}
	wp_safe_redirect( add_query_arg( 'lunara_notice', $notice, $redirect ) );
	exit;
}
add_action( 'admin_post_lunara_save_journal_archive_studio', 'lunara_control_desk_save_journal_archive_studio' );

/**
 * Restore handler for a selected prior-public snapshot.
 */
function lunara_control_desk_restore_journal_archive_studio() {
	$redirect = admin_url( 'admin.php?page=lunara-site-studio&surface=journal-archive' );
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_safe_redirect( add_query_arg( 'lunara_notice', 'journal_archive_studio_forbidden', $redirect ) );
		exit;
	}
	check_admin_referer( 'lunara_restore_journal_archive_studio', 'lunara_journal_archive_restore_nonce' );
	$revision_id = isset( $_POST['lunara_journal_archive_revision_id'] ) ? sanitize_text_field( wp_unslash( $_POST['lunara_journal_archive_revision_id'] ) ) : '';
	$result      = lunara_journal_archive_studio_restore_revision( $revision_id );
	if ( is_wp_error( $result ) ) {
		lunara_journal_archive_studio_store_feedback( $result->get_error_code() );
		$notice = 'journal_archive_studio_restore_invalid';
	} else {
		lunara_journal_archive_studio_clear_invalid_stage();
		$notice = 'journal_archive_studio_restored';
	}
	wp_safe_redirect( add_query_arg( 'lunara_notice', $notice, $redirect ) );
	exit;
}
add_action( 'admin_post_lunara_restore_journal_archive_studio', 'lunara_control_desk_restore_journal_archive_studio' );

/**
 * Private side-by-side preview of an unsaved candidate.
 */
function lunara_control_desk_preview_journal_archive_studio() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to preview Journal Archive changes.', 'lunara-film' ) );
	}
	check_admin_referer( 'lunara_preview_journal_archive_studio', 'lunara_journal_archive_preview_nonce' );
	$token = lunara_journal_archive_studio_store_preview( lunara_journal_archive_studio_config_from_request( $_POST ) );
	if ( is_wp_error( $token ) ) {
		wp_die( esc_html( sprintf( __( 'The preview was not created: %s', 'lunara-film' ), lunara_journal_archive_studio_validation_message( $token->get_error_code() ) ) ) );
	}
	nocache_headers();
	header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
	$url = add_query_arg( 'lunara_journal_preview', rawurlencode( $token ), home_url( '/journal/' ) );
	?><!doctype html>
	<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php esc_html_e( 'Journal Archive Preview', 'lunara-film' ); ?></title></head>
	<body style="margin:0;background:#151515;color:#fff;font-family:system-ui,sans-serif">
		<header style="padding:18px 24px"><h1 style="margin:0;font-size:20px"><?php esc_html_e( 'Unsaved Journal Archive preview', 'lunara-film' ); ?></h1><p><?php esc_html_e( 'Private, read-only, and expires in 30 minutes. Nothing below is public yet.', 'lunara-film' ); ?></p></header>
		<div style="display:flex;gap:24px;align-items:flex-start;overflow:auto;padding:0 24px 24px">
			<iframe title="<?php esc_attr_e( 'Desktop Journal preview', 'lunara-film' ); ?>" data-lunara-journal-preview-frame="desktop" style="width:1440px;height:900px;flex:0 0 1440px;border:1px solid #555;background:#fff" src="<?php echo esc_url( $url ); ?>"></iframe>
			<iframe title="<?php esc_attr_e( 'Mobile Journal preview', 'lunara-film' ); ?>" data-lunara-journal-preview-frame="mobile" style="width:390px;height:844px;flex:0 0 390px;border:1px solid #555;background:#fff" src="<?php echo esc_url( $url ); ?>"></iframe>
		</div>
	</body></html><?php
	exit;
}
add_action( 'admin_post_lunara_preview_journal_archive_studio', 'lunara_control_desk_preview_journal_archive_studio' );
