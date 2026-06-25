<?php
/**
 * ACF Pro integration bridge.
 *
 * This module prepares Lunara's Review, Journal, and Debrief systems for ACF Pro
 * without breaking legacy `_lunara_*` meta reads used by existing templates.
 *
 * IMPORTANT: ACF field names intentionally use the `acf_lunara_*` prefix. Do not
 * rename them to `lunara_*`: ACF stores field-key references in underscored meta
 * keys, and names such as `lunara_score` would collide with existing legacy
 * value keys such as `_lunara_score`.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the theme's ACF Local JSON directory.
 *
 * @return string
 */
function lunara_acf_json_root() {
    return trailingslashit( get_stylesheet_directory() ) . 'acf-json';
}

/**
 * Save ACF Local JSON into the child theme so field changes can be versioned.
 *
 * @param string $path Incoming save path.
 * @return string
 */
function lunara_acf_json_save_path( $path ) {
    $json_root = lunara_acf_json_root();

    if ( ! is_dir( $json_root ) && function_exists( 'wp_mkdir_p' ) ) {
        wp_mkdir_p( $json_root );
    }

    return $json_root;
}
add_filter( 'acf/settings/save_json', 'lunara_acf_json_save_path' );

/**
 * Load ACF Local JSON from the theme and optional organizational subfolders.
 *
 * @param array $paths Existing load paths.
 * @return array
 */
function lunara_acf_json_load_paths( $paths ) {
    $json_root = lunara_acf_json_root();
    $paths     = is_array( $paths ) ? $paths : array();

    foreach ( array( $json_root, $json_root . '/field-groups', $json_root . '/post-types', $json_root . '/taxonomies', $json_root . '/options-pages' ) as $path ) {
        if ( is_dir( $path ) ) {
            $paths[] = $path;
        }
    }

    return array_values( array_unique( $paths ) );
}
add_filter( 'acf/settings/load_json', 'lunara_acf_json_load_paths' );

/**
 * Map legacy meta keys to safe ACF field names.
 *
 * The bridge lets existing frontend code keep reading `_lunara_*` keys while new
 * ACF fields can be filled in the editor. ACF values win only when the mapped
 * ACF field has been saved on the post.
 *
 * Structured fallbacks let the current frontend render richer ACF data through
 * the existing legacy Debrief/Journal renderers until those renderers are
 * refactored directly.
 *
 * @return array<string,array<string,mixed>>
 */
function lunara_acf_legacy_meta_map() {
    return array(
        // Review — core metadata.
        '_lunara_score'                         => array( 'field' => 'acf_lunara_review_score', 'post_types' => array( 'review' ) ),
        '_lunara_year'                          => array( 'field' => 'acf_lunara_review_year', 'post_types' => array( 'review' ) ),
        '_lunara_imdb_title_id'                 => array( 'field' => 'acf_lunara_review_imdb_title_id', 'post_types' => array( 'review' ) ),
        '_lunara_director'                      => array( 'field' => 'acf_lunara_review_director', 'post_types' => array( 'review' ) ),
        '_lunara_runtime'                       => array( 'field' => 'acf_lunara_review_runtime', 'post_types' => array( 'review' ) ),
        '_lunara_studio'                        => array( 'field' => 'acf_lunara_review_studio', 'post_types' => array( 'review' ) ),

        // Review — Debrief legacy-compatible fields.
        '_lunara_where'                         => array( 'field' => 'acf_lunara_review_where', 'structured_field' => 'acf_lunara_review_where_structured', 'structured_type' => 'where_to_watch', 'post_types' => array( 'review' ) ),
        '_lunara_theme_echo'                    => array( 'field' => 'acf_lunara_debrief_theme_echo', 'structured_field' => 'acf_lunara_debrief_theme_echo_structured', 'structured_type' => 'pairing_line', 'post_types' => array( 'review' ) ),
        '_lunara_counter_program'               => array( 'field' => 'acf_lunara_debrief_counter_program', 'structured_field' => 'acf_lunara_debrief_counter_structured', 'structured_type' => 'pairing_line', 'post_types' => array( 'review' ) ),
        '_lunara_career_context'                => array( 'field' => 'acf_lunara_debrief_career_context', 'structured_field' => 'acf_lunara_debrief_career_structured', 'structured_type' => 'pairing_line', 'post_types' => array( 'review' ) ),
        '_lunara_craft_mirror'                  => array( 'field' => 'acf_lunara_debrief_career_context', 'structured_field' => 'acf_lunara_debrief_career_structured', 'structured_type' => 'pairing_line', 'post_types' => array( 'review' ) ),

        // Review — editorial controls.
        '_lunara_review_lane_label_override'    => array( 'field' => 'acf_lunara_review_lane_label_override', 'post_types' => array( 'review' ) ),
        '_lunara_review_standfirst'             => array( 'field' => 'acf_lunara_review_standfirst', 'post_types' => array( 'review' ) ),
        '_lunara_pull_quote'                    => array( 'field' => 'acf_lunara_review_pull_quote', 'post_types' => array( 'review' ) ),
        '_lunara_review_pull_quote'             => array( 'field' => 'acf_lunara_review_pull_quote', 'post_types' => array( 'review' ) ),
        '_lunara_review_archive_cta_label'      => array( 'field' => 'acf_lunara_review_archive_cta_label', 'post_types' => array( 'review' ) ),
        '_lunara_review_archive_url_override'   => array( 'field' => 'acf_lunara_review_archive_url_override', 'post_types' => array( 'review' ) ),
        '_lunara_review_hide_standfirst'        => array( 'field' => 'acf_lunara_review_hide_standfirst', 'post_types' => array( 'review' ), 'type' => 'boolean', 'allow_empty' => true ),
        '_lunara_review_hide_where_card'        => array( 'field' => 'acf_lunara_review_hide_where_card', 'post_types' => array( 'review' ), 'type' => 'boolean', 'allow_empty' => true ),
        '_lunara_review_hide_details_card'      => array( 'field' => 'acf_lunara_review_hide_details_card', 'post_types' => array( 'review' ), 'type' => 'boolean', 'allow_empty' => true ),

        // Review — image and cinematic slots. Legacy code expects URLs.
        '_lunara_review_card_image'             => array( 'field' => 'acf_lunara_review_card_image', 'post_types' => array( 'review' ), 'type' => 'image_url' ),
        '_lunara_review_hero_banner'            => array( 'field' => 'acf_lunara_review_hero_banner', 'post_types' => array( 'review' ), 'type' => 'image_url' ),
        '_lunara_review_hero_banner_caption'    => array( 'field' => 'acf_lunara_review_hero_banner_caption', 'post_types' => array( 'review' ) ),
        '_lunara_review_context_shot'           => array( 'field' => 'acf_lunara_review_context_shot', 'post_types' => array( 'review' ), 'type' => 'image_url' ),
        '_lunara_review_context_shot_caption'   => array( 'field' => 'acf_lunara_review_context_shot_caption', 'post_types' => array( 'review' ) ),
        '_lunara_review_visual_evidence'        => array( 'field' => 'acf_lunara_review_visual_evidence', 'post_types' => array( 'review' ), 'type' => 'image_url' ),
        '_lunara_review_visual_evidence_caption'=> array( 'field' => 'acf_lunara_review_visual_evidence_caption', 'post_types' => array( 'review' ) ),
        '_lunara_review_thematic_echo'          => array( 'field' => 'acf_lunara_review_thematic_echo', 'post_types' => array( 'review' ), 'type' => 'image_url' ),
        '_lunara_review_thematic_echo_caption'  => array( 'field' => 'acf_lunara_review_thematic_echo_caption', 'post_types' => array( 'review' ) ),

        // Review — homepage placement controls.
        '_lunara_review_home_hero_featured'     => array( 'field' => 'acf_lunara_review_home_hero_featured', 'post_types' => array( 'review' ), 'type' => 'boolean', 'allow_empty' => true ),
        '_lunara_review_home_hero_priority'     => array( 'field' => 'acf_lunara_review_home_hero_priority', 'post_types' => array( 'review' ) ),
        '_lunara_review_home_featured_shelf'    => array( 'field' => 'acf_lunara_review_home_featured_shelf', 'post_types' => array( 'review' ), 'type' => 'boolean', 'allow_empty' => true ),
        '_lunara_review_home_featured_priority' => array( 'field' => 'acf_lunara_review_home_featured_priority', 'post_types' => array( 'review' ) ),

        // Journal — canonical journal controls.
        '_lunara_journal_kicker'                => array( 'field' => 'acf_lunara_journal_kicker', 'post_types' => array( 'journal' ) ),
        '_lunara_journal_signal_note'           => array( 'field' => 'acf_lunara_journal_signal_note', 'post_types' => array( 'journal' ) ),
        '_lunara_journal_featured'              => array( 'field' => 'acf_lunara_journal_featured', 'post_types' => array( 'journal' ), 'type' => 'boolean', 'allow_empty' => true ),
        '_lunara_journal_carousel_ids'          => array( 'field' => 'acf_lunara_journal_gallery', 'post_types' => array( 'journal' ), 'type' => 'gallery_ids' ),
        '_lunara_journal_gallery_ids'           => array( 'field' => 'acf_lunara_journal_gallery', 'post_types' => array( 'journal' ), 'type' => 'gallery_ids' ),
        '_lunara_featured_image_credit'         => array( 'field' => 'acf_lunara_journal_featured_image_credit', 'post_types' => array( 'journal' ) ),
        '_lunara_featured_image_source_name'    => array( 'field' => 'acf_lunara_journal_featured_image_source_name', 'post_types' => array( 'journal' ) ),
        '_lunara_featured_image_source_url'     => array( 'field' => 'acf_lunara_journal_featured_image_source_url', 'post_types' => array( 'journal' ) ),

        // Journal / editorial post controls shared by old post UI and Journal.
        '_lunara_post_type_label_override'      => array( 'field' => 'acf_lunara_journal_label_override', 'post_types' => array( 'post', 'journal' ) ),
        '_lunara_post_hero_image_url'           => array( 'field' => 'acf_lunara_journal_hero_image', 'post_types' => array( 'post', 'journal' ), 'type' => 'image_url' ),
        '_lunara_post_hero_secondary_image_url' => array( 'field' => 'acf_lunara_journal_hero_secondary_image', 'post_types' => array( 'post', 'journal' ), 'type' => 'image_url' ),
        '_lunara_post_hero_media_layout'        => array( 'field' => 'acf_lunara_journal_hero_media_layout', 'post_types' => array( 'post', 'journal' ) ),
        '_lunara_post_standfirst'               => array( 'field' => 'acf_lunara_journal_standfirst', 'post_types' => array( 'post', 'journal' ) ),
        '_lunara_post_signal_note'              => array( 'field' => 'acf_lunara_journal_signal_note', 'post_types' => array( 'post', 'journal' ) ),
        '_lunara_post_archive_cta_label'        => array( 'field' => 'acf_lunara_journal_archive_cta_label', 'post_types' => array( 'post', 'journal' ) ),
        '_lunara_post_archive_url_override'     => array( 'field' => 'acf_lunara_journal_archive_url_override', 'post_types' => array( 'post', 'journal' ) ),
        '_lunara_post_hide_standfirst'          => array( 'field' => 'acf_lunara_journal_hide_standfirst', 'post_types' => array( 'post', 'journal' ), 'type' => 'boolean', 'allow_empty' => true ),
        '_lunara_post_hide_hero_media'          => array( 'field' => 'acf_lunara_journal_hide_hero_media', 'post_types' => array( 'post', 'journal' ), 'type' => 'boolean', 'allow_empty' => true ),
        '_lunara_post_hide_details_card'        => array( 'field' => 'acf_lunara_journal_hide_details_card', 'post_types' => array( 'post', 'journal' ), 'type' => 'boolean', 'allow_empty' => true ),
        '_lunara_post_hide_signal_card'         => array( 'field' => 'acf_lunara_journal_hide_signal_card', 'post_types' => array( 'post', 'journal' ), 'type' => 'boolean', 'allow_empty' => true ),
        '_lunara_post_hide_related'             => array( 'field' => 'acf_lunara_journal_hide_related', 'post_types' => array( 'post', 'journal' ), 'type' => 'boolean', 'allow_empty' => true ),
    );
}

/**
 * Read a raw ACF value when ACF is available, with a post-meta fallback.
 *
 * @param int    $post_id    Post ID.
 * @param string $field_name ACF field name.
 * @return mixed
 */
function lunara_acf_get_raw_field_value( $post_id, $field_name ) {
    $post_id    = absint( $post_id );
    $field_name = sanitize_key( (string) $field_name );

    if ( $post_id <= 0 || '' === $field_name ) {
        return null;
    }

    if ( function_exists( 'get_field' ) ) {
        $acf_value = get_field( $field_name, $post_id, false );
        if ( null !== $acf_value ) {
            return $acf_value;
        }
    }

    if ( metadata_exists( 'post', $post_id, $field_name ) ) {
        return get_post_meta( $post_id, $field_name, true );
    }

    return null;
}

/**
 * Build the current legacy Pair It With line from a structured ACF group.
 *
 * @param int    $post_id    Post ID.
 * @param string $field_name Structured group field name.
 * @return string
 */
function lunara_acf_build_pairing_line_from_structured_field( $post_id, $field_name ) {
    $group = lunara_acf_get_raw_field_value( $post_id, $field_name );
    if ( ! is_array( $group ) ) {
        return '';
    }

    $title = trim( (string) ( $group['title'] ?? '' ) );
    $year  = trim( (string) ( $group['year'] ?? '' ) );
    $tt    = strtolower( trim( (string) ( $group['imdb_title_id'] ?? '' ) ) );
    $note  = trim( (string) ( $group['note'] ?? '' ) );

    if ( '' === $title ) {
        return '';
    }

    $line = $title;

    if ( '' !== $year && preg_match( '/^\d{4}$/', $year ) && false === strpos( $line, '(' . $year . ')' ) ) {
        $line .= ' (' . $year . ')';
    }

    if ( '' !== $note ) {
        $line .= ' — ' . $note;
    }

    if ( '' !== $tt && preg_match( '/^tt\d{7,8}$/', $tt ) ) {
        $line .= ' | ' . $tt;
    }

    return sanitize_text_field( $line );
}

/**
 * Build the current legacy Where to Watch text from a structured ACF repeater.
 *
 * @param int    $post_id    Post ID.
 * @param string $field_name Structured repeater field name.
 * @return string
 */
function lunara_acf_build_where_text_from_structured_field( $post_id, $field_name ) {
    $rows = lunara_acf_get_raw_field_value( $post_id, $field_name );
    if ( ! is_array( $rows ) || empty( $rows ) ) {
        return '';
    }

    $lines = array();

    foreach ( $rows as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $label     = trim( (string) ( $row['provider_label'] ?? '' ) );
        $type      = trim( (string) ( $row['provider_type'] ?? '' ) );
        $url       = trim( (string) ( $row['provider_url'] ?? '' ) );
        $note      = trim( (string) ( $row['availability_note'] ?? '' ) );
        $affiliate = ! empty( $row['affiliate_disclosure'] );

        if ( '' === $label && '' !== $type ) {
            $label = ucwords( str_replace( array( '-', '_' ), ' ', $type ) );
        }

        if ( '' === $label && '' === $url && '' === $note ) {
            continue;
        }

        $line = '' !== $label ? $label : $url;

        if ( '' !== $url && '' !== $label ) {
            $line .= ' | ' . esc_url_raw( $url );
        }

        if ( $affiliate ) {
            $line .= ' | affiliate';
        }

        if ( '' !== $note ) {
            $line .= ' — ' . $note;
        }

        $lines[] = sanitize_text_field( $line );
    }

    return implode( "\n", array_values( array_filter( $lines ) ) );
}

/**
 * Build a structured fallback value for a mapped legacy field.
 *
 * @param int   $post_id Post ID.
 * @param array $config  Bridge config.
 * @return string
 */
function lunara_acf_build_structured_legacy_fallback( $post_id, $config ) {
    if ( empty( $config['structured_field'] ) || empty( $config['structured_type'] ) ) {
        return '';
    }

    $structured_field = (string) $config['structured_field'];
    $structured_type  = (string) $config['structured_type'];

    if ( 'pairing_line' === $structured_type ) {
        return lunara_acf_build_pairing_line_from_structured_field( $post_id, $structured_field );
    }

    if ( 'where_to_watch' === $structured_type ) {
        return lunara_acf_build_where_text_from_structured_field( $post_id, $structured_field );
    }

    return '';
}

/**
 * Format a raw ACF meta value into the shape legacy theme code expects.
 *
 * @param mixed $value Raw ACF value from post meta.
 * @param array $config Bridge config.
 * @return mixed
 */
function lunara_acf_format_legacy_value( $value, $config ) {
    $type = isset( $config['type'] ) ? (string) $config['type'] : 'text';

    if ( 'boolean' === $type ) {
        return ( true === $value || '1' === (string) $value || 1 === $value ) ? '1' : '0';
    }

    if ( 'image_url' === $type ) {
        if ( is_array( $value ) && ! empty( $value['url'] ) ) {
            return esc_url_raw( (string) $value['url'] );
        }

        $attachment_id = is_array( $value ) && ! empty( $value['ID'] ) ? absint( $value['ID'] ) : absint( $value );
        if ( $attachment_id > 0 ) {
            $url = wp_get_attachment_image_url( $attachment_id, 'full' );
            return is_string( $url ) ? esc_url_raw( $url ) : '';
        }

        return is_string( $value ) ? esc_url_raw( $value ) : '';
    }

    if ( 'gallery_ids' === $type ) {
        if ( is_string( $value ) ) {
            return sanitize_text_field( $value );
        }

        $ids = array();
        foreach ( (array) $value as $item ) {
            if ( is_array( $item ) && ! empty( $item['ID'] ) ) {
                $ids[] = absint( $item['ID'] );
            } else {
                $ids[] = absint( $item );
            }
        }

        $ids = array_values( array_filter( array_unique( $ids ) ) );
        return implode( ',', $ids );
    }

    if ( is_scalar( $value ) ) {
        return sanitize_text_field( (string) $value );
    }

    return '';
}

/**
 * Let new ACF fields satisfy legacy `_lunara_*` meta reads.
 *
 * @param mixed  $value     Short-circuit value.
 * @param int    $object_id Post ID.
 * @param string $meta_key  Requested legacy meta key.
 * @param bool   $single    Whether a single value is requested.
 * @return mixed
 */
function lunara_acf_bridge_legacy_post_meta( $value, $object_id, $meta_key, $single ) {
    static $bridging = false;

    if ( null !== $value || $bridging || ! is_string( $meta_key ) || '' === $meta_key ) {
        return $value;
    }

    $map = lunara_acf_legacy_meta_map();
    if ( ! isset( $map[ $meta_key ] ) ) {
        return $value;
    }

    $object_id = absint( $object_id );
    if ( $object_id <= 0 ) {
        return $value;
    }

    $config     = $map[ $meta_key ];
    $post_types = isset( $config['post_types'] ) ? (array) $config['post_types'] : array();
    $post_type  = get_post_type( $object_id );

    if ( ! empty( $post_types ) && ! in_array( $post_type, $post_types, true ) ) {
        return $value;
    }

    $acf_field   = isset( $config['field'] ) ? (string) $config['field'] : '';
    $has_acf_raw = '' !== $acf_field && metadata_exists( 'post', $object_id, $acf_field );
    $raw         = null;

    if ( $has_acf_raw ) {
        $bridging = true;
        $raw      = get_post_meta( $object_id, $acf_field, true );
        $bridging = false;
    }

    $formatted   = $has_acf_raw ? lunara_acf_format_legacy_value( $raw, $config ) : '';
    $allow_empty = ! empty( $config['allow_empty'] );

    if ( ! $allow_empty && '' === trim( (string) $formatted ) ) {
        $bridging  = true;
        $structured = lunara_acf_build_structured_legacy_fallback( $object_id, $config );
        $bridging  = false;

        if ( '' !== trim( (string) $structured ) ) {
            $formatted = $structured;
        }
    }

    if ( ! $allow_empty && '' === trim( (string) $formatted ) ) {
        return $value;
    }

    return $single ? $formatted : array( $formatted );
}
add_filter( 'get_post_metadata', 'lunara_acf_bridge_legacy_post_meta', 10, 4 );
