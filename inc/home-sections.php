<?php
/**
 * Homepage dynamic section builders (Oscars snapshot, spotlight, deep cuts, etc.).
 *
 * Extracted from functions.php for maintainability.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clear short-lived front-page query caches whenever review content changes.
 */
function lunara_get_oscars_plugin() {
    if ( ! class_exists( 'Academy_Awards_Table' ) || ! method_exists( 'Academy_Awards_Table', 'get_instance' ) ) {
        return null;
    }

    return Academy_Awards_Table::get_instance();
}

/**
 * Resolve the most readable homepage label for an Oscar winner entry.
 */
function lunara_home_winner_primary_label( $entry ) {
    $category = trim( (string) ( $entry['canonical_category'] ?? '' ) );
    $film     = trim( (string) ( $entry['film'] ?? '' ) );
    $name     = trim( (string) ( $entry['name'] ?? '' ) );
    $detail   = trim( (string) ( $entry['detail'] ?? '' ) );
    $nominees = trim( (string) ( $entry['nominees'] ?? '' ) );

    $title_forward_categories = array(
        'BEST PICTURE',
        'ANIMATED FEATURE FILM',
        'DOCUMENTARY (Feature)',
        'DOCUMENTARY (Short Subject)',
        'INTERNATIONAL FEATURE FILM',
        'SHORT FILM (Animated)',
        'SHORT FILM (Live Action)',
    );

    if ( in_array( $category, $title_forward_categories, true ) && $film !== '' ) {
        return $film;
    }

    if ( $name !== '' ) {
        return $name;
    }

    if ( $film !== '' ) {
        return $film;
    }

    if ( $detail !== '' ) {
        return $detail;
    }

    return $nominees;
}

/**
 * Build a compact secondary line for homepage winner cards.
 */
function lunara_home_winner_secondary_label( $entry ) {
    $primary  = lunara_home_winner_primary_label( $entry );
    $bits     = array();
    $category = trim( (string) ( $entry['canonical_category'] ?? '' ) );

    $field_order = array( 'film', 'name', 'detail', 'note' );
    if ( lunara_oscars_category_prefers_title_visual( $category ) ) {
        $field_order = array( 'detail', 'note', 'name', 'film' );
    }

    foreach ( $field_order as $key ) {
        $value = trim( (string) ( $entry[ $key ] ?? '' ) );
        if ( $value === '' ) {
            continue;
        }

        if ( strcasecmp( $value, $primary ) === 0 ) {
            continue;
        }

        $skip_value = false;

        foreach ( $bits as $existing_idx => $existing_value ) {
            if ( 0 === strcasecmp( $existing_value, $value ) ) {
                $skip_value = true;
                break;
            }

            $existing_lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $existing_value ) : strtolower( $existing_value );
            $value_lower    = function_exists( 'mb_strtolower' ) ? mb_strtolower( $value ) : strtolower( $value );

            if ( false !== strpos( $existing_lower, $value_lower ) ) {
                $skip_value = true;
                break;
            }

            if ( false !== strpos( $value_lower, $existing_lower ) ) {
                $bits[ $existing_idx ] = $value;
                $skip_value = true;
                break;
            }
        }

        if ( ! $skip_value ) {
            $bits[] = $value;
        }
    }

    return implode( ' | ', array_slice( $bits, 0, 2 ) );
}

/**
 * Determine whether an Oscars category should lead with the winner's person image.
 */
function lunara_oscars_category_prefers_person_visual( $canonical_category ) {
    $canonical_category = strtoupper( trim( (string) $canonical_category ) );

    return in_array(
        $canonical_category,
        array(
            'DIRECTING',
            'ACTOR IN A LEADING ROLE',
            'ACTRESS IN A LEADING ROLE',
            'ACTOR IN A SUPPORTING ROLE',
            'ACTRESS IN A SUPPORTING ROLE',
        ),
        true
    );
}

/**
 * Determine whether an Oscars category should lead with the film/title visual.
 */
function lunara_oscars_category_prefers_title_visual( $canonical_category ) {
    $canonical_category = strtoupper( trim( (string) $canonical_category ) );

    return in_array(
        $canonical_category,
        array(
            'BEST PICTURE',
            'ANIMATED FEATURE FILM',
            'DOCUMENTARY (FEATURE)',
            'DOCUMENTARY (SHORT SUBJECT)',
            'INTERNATIONAL FEATURE FILM',
            'SHORT FILM (ANIMATED)',
            'SHORT FILM (LIVE ACTION)',
        ),
        true
    );
}

/**
 * Resolve a nominee person id for the winner entry when the data maps cleanly.
 */
function lunara_resolve_oscars_winner_person_id( $entry ) {
    $winner_name   = trim( (string) ( $entry['name'] ?? '' ) );
    $nominee_names = array_values( array_filter( array_map( 'trim', explode( '|', (string) ( $entry['nominees'] ?? '' ) ) ), 'strlen' ) );
    $nominee_ids   = array_values( array_filter( array_map( 'trim', explode( '|', (string) ( $entry['nominee_ids'] ?? '' ) ) ), 'strlen' ) );

    if ( 1 === count( $nominee_ids ) && preg_match( '/^nm\\d+$/i', $nominee_ids[0] ) ) {
        if ( empty( $nominee_names ) || '' === $winner_name || ! isset( $nominee_names[0] ) || 0 === strcasecmp( $nominee_names[0], $winner_name ) ) {
            return strtolower( (string) $nominee_ids[0] );
        }
    }

    if ( '' !== $winner_name && count( $nominee_ids ) === count( $nominee_names ) ) {
        foreach ( $nominee_names as $idx => $nominee_name ) {
            if ( 0 === strcasecmp( $nominee_name, $winner_name ) && ! empty( $nominee_ids[ $idx ] ) && preg_match( '/^nm\\d+$/i', $nominee_ids[ $idx ] ) ) {
                return strtolower( (string) $nominee_ids[ $idx ] );
            }
        }
    }

    return '';
}

/**
 * Resolve the best person URL for an Oscars winner entry.
 */
function lunara_get_oscars_entry_person_url( $entry, $aat_instance = null ) {
    $person_id = lunara_resolve_oscars_winner_person_id( $entry );
    if ( '' === $person_id ) {
        return '';
    }

    if ( ! $aat_instance ) {
        $aat_instance = lunara_get_oscars_plugin();
    }

    if ( $aat_instance && method_exists( $aat_instance, 'build_entity_url_from_id' ) ) {
        return trim( (string) $aat_instance->build_entity_url_from_id( $person_id ) );
    }

    return home_url( '/oscars/name/' . rawurlencode( strtolower( $person_id ) ) . '/' );
}

/**
 * Attach category, person, and title links to an Oscars entry so templates can
 * expose richer navigation than a single card-level anchor.
 */
function lunara_enrich_oscars_entry_links( $entry, $aat_instance = null ) {
    if ( empty( $entry ) || ! is_array( $entry ) ) {
        return array();
    }

    if ( ! $aat_instance ) {
        $aat_instance = lunara_get_oscars_plugin();
    }

    $category     = trim( (string) ( $entry['canonical_category'] ?? '' ) );
    $name         = trim( (string) ( $entry['name'] ?? '' ) );
    $film         = trim( (string) ( $entry['film'] ?? '' ) );
    $primary      = trim( (string) ( $entry['primary_label'] ?? '' ) );
    $secondary    = trim( (string) ( $entry['secondary_label'] ?? '' ) );
    $category_url = trim( (string) ( $entry['category_url'] ?? '' ) );
    $film_url     = trim( (string) ( $entry['film_url'] ?? '' ) );
    $person_url   = trim( (string) ( $entry['person_url'] ?? '' ) );

    if ( '' === $category_url && '' !== $category ) {
        $category_url = ( $aat_instance && method_exists( $aat_instance, 'get_category_url' ) )
            ? trim( (string) $aat_instance->get_category_url( $category ) )
            : home_url( '/oscars/category/' . sanitize_title( $category ) . '/' );
    }

    if ( '' === $person_url ) {
        $person_url = lunara_get_oscars_entry_person_url( $entry, $aat_instance );
    }

    $label_mentions = static function ( $label, $value ) {
        $label = trim( (string) $label );
        $value = trim( (string) $value );

        if ( '' === $label || '' === $value ) {
            return false;
        }

        if ( 0 === strcasecmp( $label, $value ) ) {
            return true;
        }

        $label_lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $label ) : strtolower( $label );
        $value_lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $value ) : strtolower( $value );

        return false !== strpos( $label_lower, $value_lower );
    };

    $primary_url = '';
    if ( $label_mentions( $primary, $name ) && '' !== $person_url ) {
        $primary_url = $person_url;
    } elseif ( $label_mentions( $primary, $film ) && '' !== $film_url ) {
        $primary_url = $film_url;
    } elseif ( lunara_oscars_category_prefers_person_visual( $category ) && '' !== $person_url ) {
        $primary_url = $person_url;
    } elseif ( lunara_oscars_category_prefers_title_visual( $category ) && '' !== $film_url ) {
        $primary_url = $film_url;
    } elseif ( '' !== $film_url ) {
        $primary_url = $film_url;
    } elseif ( '' !== $person_url ) {
        $primary_url = $person_url;
    } else {
        $primary_url = $category_url;
    }

    $secondary_url = '';
    if ( $label_mentions( $secondary, $film ) && '' !== $film_url && $primary_url !== $film_url ) {
        $secondary_url = $film_url;
    } elseif ( $label_mentions( $secondary, $name ) && '' !== $person_url && $primary_url !== $person_url ) {
        $secondary_url = $person_url;
    }

    $entry['category_url']  = $category_url;
    $entry['film_url']      = $film_url;
    $entry['person_url']    = $person_url;
    $entry['primary_url']   = $primary_url;
    $entry['secondary_url'] = $secondary_url;
    $entry['url']           = '' !== $primary_url ? $primary_url : ( '' !== $film_url ? $film_url : $category_url );

    return $entry;
}

/**
 * Build a curated media-library visual override for an Oscars winner card.
 */
function lunara_get_oscars_curated_visual( $entry, $photo_map ) {
    $entry_category = trim( (string) ( $entry['canonical_category'] ?? '' ) );
    $photo_id       = isset( $photo_map[ $entry_category ] ) ? absint( $photo_map[ $entry_category ] ) : 0;

    if ( $photo_id <= 0 ) {
        return array();
    }

    $photo_url = wp_get_attachment_image_url( $photo_id, 'medium_large' );
    if ( ! $photo_url ) {
        return array();
    }

    $entry_category = strtoupper( trim( (string) $entry_category ) );
    $photo_alt      = '';

    if ( 'BEST PICTURE' === $entry_category ) {
        $photo_alt = trim( (string) ( $entry['film'] ?? '' ) );
    }

    if ( '' === $photo_alt ) {
        $photo_alt = ! empty( $entry['name'] ) ? trim( (string) $entry['name'] ) : '';
    }

    if ( '' === $photo_alt ) {
        $photo_alt = trim( (string) ( $entry['film'] ?? '' ) );
    }

    return array(
        'poster_url'  => $photo_url,
        'poster_html' => sprintf(
            '<img src="%s" alt="%s" loading="lazy" decoding="async" class="lunara-winner-photo" />',
            esc_url( $photo_url ),
            esc_attr( $photo_alt )
        ),
    );
}

/**
 * Resolve the best visual package for an Oscars winner entry.
 */
function lunara_get_oscars_entry_visual( $entry, $aat_instance = null, $args = array() ) {
    $args = wp_parse_args(
        $args,
        array(
            'use_curated_photos'    => true,
            'prefer_backdrop'       => false,
            'prefer_person_visuals' => false,
            'title_visual_size'     => 'medium',
            'person_visual_size'    => 'large',
        )
    );

    $entry_category         = trim( (string) ( $entry['canonical_category'] ?? '' ) );
    $prefer_person_category = ! empty( $args['prefer_person_visuals'] ) && lunara_oscars_category_prefers_person_visual( $entry_category );
    $prefer_title_category  = lunara_oscars_category_prefers_title_visual( $entry_category );
    $visual                 = array();
    $visual_source          = '';
    $person_tried           = false;

    $try_person_visual = static function () use ( $entry, $aat_instance, $args ) {
        if ( ! $aat_instance || ! method_exists( $aat_instance, 'get_person_visual_package' ) ) {
            return array();
        }

        $person_id = lunara_resolve_oscars_winner_person_id( $entry );
        if ( '' === $person_id ) {
            return array();
        }

        $person_visual = $aat_instance->get_person_visual_package( $person_id, $args['person_visual_size'] );
        if ( ! is_array( $person_visual ) || empty( $person_visual ) ) {
            return array();
        }

        if ( empty( $person_visual['poster_url'] ) && ! empty( $person_visual['portrait_url'] ) ) {
            $person_visual['poster_url'] = $person_visual['portrait_url'];
        }

        if ( ! empty( $person_visual['poster_url'] ) && empty( $person_visual['poster_html'] ) ) {
            $person_alt = ! empty( $entry['name'] ) ? $entry['name'] : ( $person_visual['name'] ?? '' );
            if ( '' !== trim( (string) $person_alt ) ) {
                $person_visual['poster_html'] = sprintf(
                    '<img src="%s" alt="%s" loading="lazy" decoding="async" class="lunara-winner-photo lunara-winner-photo--person" />',
                    esc_url( $person_visual['poster_url'] ),
                    esc_attr( $person_alt )
                );
            }
        }

        return $person_visual;
    };

    if ( $prefer_person_category ) {
        $person_tried = true;
        $visual       = $try_person_visual();
        if ( ! empty( $visual ) ) {
            $visual_source = 'person';
        }
    }

    if ( empty( $visual['poster_url'] ) && empty( $visual['backdrop_url'] ) && $prefer_title_category && $aat_instance && method_exists( $aat_instance, 'get_title_visual_package' ) && ! empty( $entry['film_id'] ) ) {
        $visual = $aat_instance->get_title_visual_package( $entry['film_id'], $args['title_visual_size'] );
        if ( ! empty( $visual ) ) {
            $visual_source = 'title';
        }
    }

    if ( empty( $visual['poster_url'] ) && empty( $visual['backdrop_url'] ) && ! empty( $args['use_curated_photos'] ) ) {
        $visual = lunara_get_oscars_curated_visual( $entry, lunara_get_oscars_winner_photo_map() );
        if ( ! empty( $visual ) ) {
            $visual_source = 'curated';
        }
    }

    if ( empty( $visual['poster_url'] ) && empty( $visual['backdrop_url'] ) && $aat_instance && method_exists( $aat_instance, 'get_title_visual_package' ) && ! empty( $entry['film_id'] ) ) {
        $visual = $aat_instance->get_title_visual_package( $entry['film_id'], $args['title_visual_size'] );
        if ( ! empty( $visual ) ) {
            $visual_source = 'title';
        }
    }

    if ( empty( $visual['poster_url'] ) && empty( $visual['backdrop_url'] ) && ! $person_tried && ! empty( $args['prefer_person_visuals'] ) ) {
        $visual = $try_person_visual();
        if ( ! empty( $visual ) ) {
            $visual_source = 'person';
        }
    }

    if ( ! empty( $args['prefer_backdrop'] ) && ! empty( $visual['backdrop_url'] ) && 'person' !== $visual_source ) {
        $backdrop_url = trim( (string) $visual['backdrop_url'] );
        if ( '' !== $backdrop_url ) {
            $photo_alt             = ! empty( $entry['film'] ) ? $entry['film'] : ( $entry['name'] ?? '' );
            $visual['poster_url']  = $backdrop_url;
            $visual['poster_html'] = sprintf(
                '<img src="%s" alt="%s" loading="lazy" decoding="async" class="lunara-winner-photo" />',
                esc_url( $backdrop_url ),
                esc_attr( $photo_alt )
            );
        }
    }

    return is_array( $visual ) ? $visual : array();
}

/**
 * Build a dynamic Oscars snapshot for the homepage from the active database plugin.
 */
function lunara_get_home_oscars_snapshot() {
    $cache_key = 'lunara_home_oscars_snapshot_v6'; // v6: enrich spotlight entries with category, person, and film links
    $cached    = get_transient( $cache_key );

    if ( is_array( $cached ) && ! empty( $cached ) ) {
        return $cached;
    }

    $aat = lunara_get_oscars_plugin();
    if ( ! $aat || ! method_exists( $aat, 'get_max_ceremony' ) || ! method_exists( $aat, 'get_ceremony_rollup' ) ) {
        return array();
    }

    $ceremony = intval( $aat->get_max_ceremony() );
    if ( $ceremony <= 0 ) {
        return array();
    }

    $rollup = $aat->get_ceremony_rollup( $ceremony );
    if ( empty( $rollup ) || ! is_array( $rollup ) ) {
        return array();
    }

    $winner_rows = ! empty( $rollup['winner_rows'] ) && is_array( $rollup['winner_rows'] ) ? $rollup['winner_rows'] : array();
    $winner_map  = array();
    foreach ( $winner_rows as $winner_entry ) {
        $canonical = trim( (string) ( $winner_entry['canonical_category'] ?? '' ) );
        if ( $canonical !== '' && ! isset( $winner_map[ $canonical ] ) ) {
            $winner_map[ $canonical ] = $winner_entry;
        }
    }

    $spotlight_categories = array(
        'BEST PICTURE',
        'DIRECTING',
        'ACTOR IN A LEADING ROLE',
        'ACTRESS IN A LEADING ROLE',
        'ACTOR IN A SUPPORTING ROLE',
        'ACTRESS IN A SUPPORTING ROLE',
        'CINEMATOGRAPHY',
        'WRITING (Original Screenplay)',
    );

    $spotlights = array();
    foreach ( $spotlight_categories as $canonical ) {
        if ( empty( $winner_map[ $canonical ] ) ) {
            continue;
        }

        $entry = $winner_map[ $canonical ];
        $entry['category_label'] = method_exists( $aat, 'format_category_display' ) ? $aat->format_category_display( $canonical ) : $canonical;
        $entry['category_url']   = method_exists( $aat, 'get_category_url' ) ? $aat->get_category_url( $canonical ) : home_url( '/oscars/category/' . sanitize_title( $canonical ) . '/' );
        $entry['primary_label']  = lunara_home_winner_primary_label( $entry );
        $entry['secondary_label'] = lunara_home_winner_secondary_label( $entry );
        $entry                   = lunara_enrich_oscars_entry_links( $entry, $aat );
        $entry['visual']         = lunara_get_oscars_entry_visual(
            $entry,
            $aat,
            array(
                'use_curated_photos'    => true,
                'prefer_person_visuals' => true,
                'title_visual_size'     => 'medium_large',
                'person_visual_size'    => 'large',
            )
        );
        $spotlights[]            = $entry;
    }

    $top_titles = array();
    foreach ( array_slice( (array) ( $rollup['top_titles'] ?? array() ), 0, 4 ) as $title_entry ) {
        $film_id = trim( (string) ( $title_entry['film_id'] ?? '' ) );
        $visual  = ( $film_id !== '' && method_exists( $aat, 'get_title_visual_package' ) ) ? $aat->get_title_visual_package( $film_id, 'large' ) : array();

        $title_entry['visual'] = $visual;
        $title_entry['url']    = ! empty( $title_entry['film_url'] )
            ? $title_entry['film_url']
            : ( ( $film_id !== '' && method_exists( $aat, 'build_entity_url_from_id' ) ) ? $aat->build_entity_url_from_id( $film_id ) : home_url( '/oscars/title/' . $film_id . '/' ) );
        $title_entry['winning_categories_line'] = ! empty( $title_entry['winning_categories'] ) && is_array( $title_entry['winning_categories'] )
            ? implode( ' · ', array_slice( array_values( $title_entry['winning_categories'] ), 0, 2 ) )
            : '';

        $top_titles[] = $title_entry;
    }

    $best_picture = ! empty( $rollup['best_picture'] ) && is_array( $rollup['best_picture'] ) ? $rollup['best_picture'] : array();
    if ( ! empty( $best_picture['film_id'] ) && method_exists( $aat, 'get_title_visual_package' ) ) {
        $best_picture['visual'] = $aat->get_title_visual_package( $best_picture['film_id'], 'large' );
    } else {
        $best_picture['visual'] = array();
    }

    $winner_rows_total = count( $winner_rows );
    $categories_total  = intval( $rollup['categories_total'] ?? 0 );
    $summary_bits      = array();

    if ( ! empty( $best_picture['film'] ) ) {
        $summary_bits[] = sprintf( 'Best Picture went to %s', $best_picture['film'] );
    }

    if ( ! empty( $rollup['most_wins']['film'] ) && ! empty( $rollup['most_wins']['wins'] ) ) {
        $summary_bits[] = sprintf( '%1$s led the ceremony with %2$s win%3$s', $rollup['most_wins']['film'], number_format_i18n( intval( $rollup['most_wins']['wins'] ) ), intval( $rollup['most_wins']['wins'] ) === 1 ? '' : 's' );
    }

    $snapshot = array(
        'ceremony'          => $ceremony,
        'ceremony_label'    => method_exists( $aat, 'ordinal' ) ? $aat->ordinal( $ceremony ) . ' Academy Awards' : sprintf( 'Ceremony %d', $ceremony ),
        'year_label'        => method_exists( $aat, 'get_ceremony_year' ) ? $aat->get_ceremony_year( $ceremony ) : (string) ( $rollup['year'] ?? '' ),
        'ceremony_url'      => method_exists( $aat, 'get_ceremony_url' ) ? $aat->get_ceremony_url( $ceremony ) : home_url( '/oscars/ceremony/' . $ceremony . '/' ),
        'database_url'      => method_exists( $aat, 'get_database_url' ) ? $aat->get_database_url() : home_url( '/oscars/' ),
        'categories_url'    => method_exists( $aat, 'get_categories_index_url' ) ? $aat->get_categories_index_url() : home_url( '/oscars/categories/' ),
        'rollup'            => $rollup,
        'best_picture'      => $best_picture,
        'spotlights'        => $spotlights,
        'top_titles'        => $top_titles,
        'winner_rows_total' => $winner_rows_total,
        'categories_total'  => $categories_total,
        'summary'           => ! empty( $summary_bits ) ? implode( '. ', $summary_bits ) . '.' : '',
        'winner_record'     => sprintf(
            '%1$s winner row%2$s across %3$s categor%4$s%5$s',
            number_format_i18n( $winner_rows_total ),
            $winner_rows_total === 1 ? '' : 's',
            number_format_i18n( $categories_total ),
            $categories_total === 1 ? 'y' : 'ies',
            $winner_rows_total > $categories_total ? ', including ties' : ''
        ),
    );

    set_transient( $cache_key, $snapshot, 5 * MINUTE_IN_SECONDS );

    return $snapshot;
}

/**
 * Curated winner-photo overrides shared by Oscars winner lanes.
 */
function lunara_get_oscars_winner_photo_map() {
    return array(
        'BEST PICTURE'                  => 30253,
        'DIRECTING'                     => 30249,
        'ACTOR IN A LEADING ROLE'       => 30247,
        'ACTRESS IN A LEADING ROLE'     => 30251,
        'ACTOR IN A SUPPORTING ROLE'    => 30255,
        'ACTRESS IN A SUPPORTING ROLE'  => 30256,
        'WRITING (Original Screenplay)' => 30252,
        'WRITING (Adapted Screenplay)'  => 28520,
        'CINEMATOGRAPHY'                => 30248,
        'FILM EDITING'                  => 30250,
        'MUSIC (Original Score)'        => 30254,
    );
}

/**
 * Build a curated set of Oscars winner cards for a ceremony rollup.
 */
function lunara_build_oscars_ceremony_winner_cards( $winner_map, $aat_instance = null, $limit = 12, $args = array() ) {
    if ( empty( $winner_map ) || ! is_array( $winner_map ) ) {
        return array();
    }

    $limit = max( 1, min( 24, absint( $limit ) ) );
    $args  = wp_parse_args(
        $args,
        array(
            'use_curated_photos' => true,
            'prefer_backdrop'    => false,
            'prefer_person_visuals' => false,
            'title_visual_size'  => 'medium',
            'person_visual_size' => 'large',
        )
    );

    $preferred_categories = array(
        'BEST PICTURE',
        'DIRECTING',
        'ACTOR IN A LEADING ROLE',
        'ACTRESS IN A LEADING ROLE',
        'ACTOR IN A SUPPORTING ROLE',
        'ACTRESS IN A SUPPORTING ROLE',
        'WRITING (Original Screenplay)',
        'WRITING (Adapted Screenplay)',
        'VISUAL EFFECTS',
        'CINEMATOGRAPHY',
        'FILM EDITING',
        'MUSIC (Original Score)',
    );

    $winner_cards   = array();
    $used_keys      = array();
    $normalized_map = array();

    foreach ( $winner_map as $key => $entry ) {
        $normalized_map[ strtoupper( trim( (string) $key ) ) ] = $entry;
    }

    $append_card = static function ( $entry, $canonical_key ) use ( &$winner_cards, &$used_keys, $limit, $aat_instance, $args ) {
        if ( count( $winner_cards ) >= $limit ) {
            return;
        }

        $canonical_key = strtoupper( trim( (string) $canonical_key ) );
        if ( isset( $used_keys[ $canonical_key ] ) ) {
            return;
        }

        $used_keys[ $canonical_key ] = true;
        $entry_category              = trim( (string) ( $entry['canonical_category'] ?? $canonical_key ) );

        if ( empty( $entry['category_label'] ) ) {
            $entry['category_label'] = ( $aat_instance && method_exists( $aat_instance, 'format_category_display' ) )
                ? $aat_instance->format_category_display( $entry_category )
                : $entry_category;
        }

        $entry['primary_label']   = lunara_home_winner_primary_label( $entry );
        $entry['secondary_label'] = lunara_home_winner_secondary_label( $entry );
        $entry                    = lunara_enrich_oscars_entry_links( $entry, $aat_instance );
        $entry['_visual'] = lunara_get_oscars_entry_visual( $entry, $aat_instance, $args );
        $winner_cards[]   = $entry;
    };

    foreach ( $preferred_categories as $canonical ) {
        $canonical_key = strtoupper( $canonical );
        if ( empty( $normalized_map[ $canonical_key ] ) ) {
            continue;
        }

        $append_card( $normalized_map[ $canonical_key ], $canonical_key );
    }

    foreach ( $winner_map as $key => $entry ) {
        if ( count( $winner_cards ) >= $limit ) {
            break;
        }

        $canonical_key = strtoupper( trim( (string) $key ) );
        $append_card( $entry, $canonical_key );
    }

    return $winner_cards;
}

/**
 * Daily rotating Oscars ceremony showcase for the /oscars/ deep-dive lane.
 */
function lunara_get_rotating_oscars_ceremony_showcase( $card_limit = 10 ) {
    $card_limit = max( 4, min( 16, absint( $card_limit ) ) );
    $day_index  = function_exists( 'wp_date' ) ? intval( wp_date( 'z' ) ) : intval( date( 'z' ) );
    $cache_key  = 'lunara_oscars_rotating_showcase_v3_' . $day_index . '_' . $card_limit;
    $cached     = get_transient( $cache_key );

    if ( is_array( $cached ) && ! empty( $cached ) ) {
        return $cached;
    }

    $aat = lunara_get_oscars_plugin();
    if ( ! $aat || ! method_exists( $aat, 'get_max_ceremony' ) || ! method_exists( $aat, 'get_ceremony_rollup' ) ) {
        return array();
    }

    $max_ceremony = intval( $aat->get_max_ceremony() );
    if ( $max_ceremony <= 0 ) {
        return array();
    }

    $rotation_total = max( 1, $max_ceremony );
    $offset         = $day_index % $rotation_total;
    $ceremony       = $max_ceremony - $offset;

    if ( $ceremony < 1 ) {
        $ceremony += $rotation_total;
    }

    $rollup = $aat->get_ceremony_rollup( $ceremony );
    if ( empty( $rollup ) || ! is_array( $rollup ) ) {
        return array();
    }

    $winner_rows = ! empty( $rollup['winner_rows'] ) && is_array( $rollup['winner_rows'] ) ? $rollup['winner_rows'] : array();
    $winner_map  = array();

    foreach ( $winner_rows as $winner_entry ) {
        $canonical = trim( (string) ( $winner_entry['canonical_category'] ?? '' ) );
        if ( $canonical !== '' && ! isset( $winner_map[ $canonical ] ) ) {
            $winner_map[ $canonical ] = $winner_entry;
        }
    }

    $winner_cards = lunara_build_oscars_ceremony_winner_cards(
        $winner_map,
        $aat,
        $card_limit,
        array(
            'use_curated_photos' => false,
            'prefer_backdrop'    => true,
            'prefer_person_visuals' => true,
            'title_visual_size'  => 'large',
            'person_visual_size' => 'large',
        )
    );
    if ( empty( $winner_cards ) ) {
        return array();
    }

    $best_picture = ! empty( $rollup['best_picture'] ) && is_array( $rollup['best_picture'] ) ? $rollup['best_picture'] : array();
    $summary_bits = array();

    if ( ! empty( $best_picture['film'] ) ) {
        $summary_bits[] = sprintf( 'Best Picture went to %s', $best_picture['film'] );
    }

    if ( ! empty( $rollup['most_wins']['film'] ) && ! empty( $rollup['most_wins']['wins'] ) ) {
        $summary_bits[] = sprintf(
            '%1$s led with %2$s win%3$s',
            $rollup['most_wins']['film'],
            number_format_i18n( intval( $rollup['most_wins']['wins'] ) ),
            intval( $rollup['most_wins']['wins'] ) === 1 ? '' : 's'
        );
    }

    $result = array(
        'day_index'        => $day_index,
        'ceremony'         => $ceremony,
        'ceremony_label'   => method_exists( $aat, 'ordinal' ) ? $aat->ordinal( $ceremony ) . ' Academy Awards' : sprintf( 'Ceremony %d', $ceremony ),
        'year_label'       => method_exists( $aat, 'get_ceremony_year' ) ? $aat->get_ceremony_year( $ceremony ) : (string) ( $rollup['year'] ?? '' ),
        'ceremony_url'     => method_exists( $aat, 'get_ceremony_url' ) ? $aat->get_ceremony_url( $ceremony ) : home_url( '/oscars/ceremony/' . $ceremony . '/' ),
        'winner_cards'     => $winner_cards,
        'winner_total'     => count( $winner_cards ),
        'categories_total' => intval( $rollup['categories_total'] ?? 0 ),
        'summary'          => ! empty( $summary_bits ) ? implode( '. ', $summary_bits ) . '.' : '',
    );

    set_transient( $cache_key, $result, DAY_IN_SECONDS );

    return $result;
}

/**
 * Find a spotlight winner entry in the homepage snapshot by canonical category.
 */
function lunara_get_home_snapshot_spotlight( $snapshot, $canonical_category ) {
    foreach ( (array) ( $snapshot['spotlights'] ?? array() ) as $spotlight ) {
        if ( strtoupper( trim( (string) ( $spotlight['canonical_category'] ?? '' ) ) ) === strtoupper( trim( (string) $canonical_category ) ) ) {
            return $spotlight;
        }
    }

    return array();
}

/**
 * Determine whether a category debuts in the supplied ceremony.
 */
function lunara_home_category_debuts_in_ceremony( $canonical_category, $ceremony ) {
    global $wpdb;

    $canonical_category = trim( (string) $canonical_category );
    $ceremony           = intval( $ceremony );

    if ( $canonical_category === '' || $ceremony <= 0 ) {
        return false;
    }

    $table_name = $wpdb->prefix . 'academy_awards';
    $first_seen = intval(
        $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MIN(ceremony) FROM $table_name WHERE canonical_category = %s",
                $canonical_category
            )
        )
    );

    return $first_seen > 0 && $first_seen === $ceremony;
}

/**
 * Build editable homepage Oscar pulse notes.
 */
function lunara_get_home_pulse_editorial_cards( $snapshot = array() ) {
    if ( empty( $snapshot ) ) {
        $snapshot = lunara_get_home_oscars_snapshot();
    }

    $ceremony          = intval( $snapshot['ceremony'] ?? 0 );
    $ceremony_label    = trim( (string) ( $snapshot['ceremony_label'] ?? 'Latest Oscar Pulse' ) );
    $database_url      = trim( (string) ( $snapshot['database_url'] ?? home_url( '/oscars/' ) ) );
    $ceremony_url      = trim( (string) ( $snapshot['ceremony_url'] ?? $database_url ) );
    $best_picture      = (array) ( $snapshot['best_picture'] ?? array() );
    $most_wins         = (array) ( $snapshot['rollup']['most_wins'] ?? array() );
    $casting_spotlight = lunara_get_home_snapshot_spotlight( $snapshot, 'CASTING' );
    $cinema_spotlight  = lunara_get_home_snapshot_spotlight( $snapshot, 'CINEMATOGRAPHY' );

    $cards = array(
        array(
            'kicker'     => 'Latest Best Picture',
            'title'      => ! empty( $best_picture['film'] ) ? sprintf( '%s is now the front door into the latest ceremony', $best_picture['film'] ) : 'The latest Best Picture winner is now live',
            'copy'       => ! empty( $most_wins['film'] ) && ! empty( $most_wins['wins'] )
                ? sprintf( '%1$s now sits inside the latest Academy Awards hub alongside %2$s, which led the night with %3$s win%4$s.', $ceremony_label, $most_wins['film'], number_format_i18n( intval( $most_wins['wins'] ) ), intval( $most_wins['wins'] ) === 1 ? '' : 's' )
                : 'The newest winners, ceremony rollup, and film pages are already stitched into the Lunara Oscar Ledger.',
            'link_label' => 'Open Ceremony Hub',
            'url'        => $ceremony_url,
        ),
        array(
            'kicker'     => lunara_home_category_debuts_in_ceremony( 'CASTING', $ceremony ) ? 'New Oscar Category' : 'Category Spotlight',
            'title'      => ! empty( $casting_spotlight['primary_label'] )
                ? sprintf( '%s keeps the newest category from feeling abstract', $casting_spotlight['primary_label'] )
                : 'Every category in the Ledger leads somewhere real',
            'copy'       => ! empty( $casting_spotlight['film'] )
                ? sprintf( '%s gives you a live example of how the Ledger moves from a category win into a film page, people pages, and broader ceremony context.', $casting_spotlight['film'] )
                : 'Browse any category to see the full lineage of winners and nominees, then follow each name into the wider Lunara record.',
            'link_label' => ! empty( $casting_spotlight['category_url'] ) ? 'See the Category' : 'Explore the Ledger',
            'url'        => ! empty( $casting_spotlight['category_url'] ) ? $casting_spotlight['category_url'] : $database_url,
        ),
        array(
            'kicker'     => ! empty( $cinema_spotlight['category_label'] ) ? $cinema_spotlight['category_label'] : 'Editorial Spotlight',
            'title'      => ! empty( $cinema_spotlight['primary_label'] )
                ? sprintf( '%s won in a category that rewards the image itself', $cinema_spotlight['primary_label'] )
                : 'The craft categories reveal the ceremony from a different angle',
            'copy'       => ! empty( $cinema_spotlight['film'] )
                ? sprintf( 'Follow %s through the Ledger to see how the craft side of this ceremony connects to the broader competition.', $cinema_spotlight['film'] )
                : 'Cinematography, editing, sound, and design winners sit alongside every other category inside the same Lunara record.',
            'link_label' => ! empty( $cinema_spotlight['url'] ) ? 'Open the Winner Page' : 'Browse Categories',
            'url'        => ! empty( $cinema_spotlight['url'] ) ? $cinema_spotlight['url'] : $database_url,
        ),
    );

    foreach ( $cards as $index => $card ) {
        $card_number = $index + 1;
        foreach ( array( 'kicker', 'title', 'copy', 'link_label', 'url' ) as $field ) {
            $theme_mod_key = sprintf( 'lunara_home_pulse_card_%d_%s', $card_number, $field === 'url' ? 'link_url' : $field );
            $override      = 'url' === $field
                ? lunara_theme_mod_url( $theme_mod_key, '' )
                : lunara_theme_mod_text( $theme_mod_key, '' );

            if ( '' !== $override ) {
                $cards[ $index ][ $field ] = $override;
            }
        }
    }

    return $cards;
}

/**
 * Build a poster-first story card for a specific Oscar-recognized title.
 */
function lunara_build_home_title_story( $imdb_id, $args = array() ) {
    $aat = lunara_get_oscars_plugin();
    if ( ! $aat || ! method_exists( $aat, 'get_title_visual_package' ) ) {
        return array();
    }

    $defaults = array(
        'preferred_categories' => array(),
        'max_categories'       => 4,
    );
    $args = wp_parse_args( $args, $defaults );

    global $wpdb;
    $table_name = $wpdb->prefix . 'academy_awards';
    $imdb_id    = strtolower( trim( (string) $imdb_id ) );

    if ( $imdb_id === '' ) {
        return array();
    }

    $like = '%' . $wpdb->esc_like( $imdb_id ) . '%';
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ceremony, year, canonical_category, film, winner FROM $table_name WHERE (film_id = %s OR film_id LIKE %s) AND canonical_category != '' ORDER BY ceremony DESC, winner DESC, canonical_category ASC",
            $imdb_id,
            $like
        ),
        ARRAY_A
    );

    if ( ! is_array( $rows ) || empty( $rows ) ) {
        return array();
    }

    $winner_rows = array_values(
        array_filter(
            $rows,
            static function( $row ) {
                return ! empty( $row['winner'] );
            }
        )
    );

    $raw_categories = array();
    foreach ( $winner_rows as $row ) {
        $canonical = trim( (string) ( $row['canonical_category'] ?? '' ) );
        if ( $canonical !== '' && ! in_array( $canonical, $raw_categories, true ) ) {
            $raw_categories[] = $canonical;
        }
    }

    $ordered_categories = array();
    foreach ( (array) $args['preferred_categories'] as $preferred ) {
        if ( in_array( $preferred, $raw_categories, true ) ) {
            $ordered_categories[] = $preferred;
        }
    }
    foreach ( $raw_categories as $canonical ) {
        if ( ! in_array( $canonical, $ordered_categories, true ) ) {
            $ordered_categories[] = $canonical;
        }
    }

    $display_categories = array();
    foreach ( array_slice( $ordered_categories, 0, intval( $args['max_categories'] ) ) as $canonical ) {
        $display_categories[] = method_exists( $aat, 'format_category_display' ) ? $aat->format_category_display( $canonical ) : $canonical;
    }

    $first_row = $rows[0];
    $title     = trim( (string) ( $first_row['film'] ?? '' ) );
    if ( $title === '' && method_exists( $aat, 'lookup_title_label' ) ) {
        $title = $aat->lookup_title_label( $imdb_id );
    }

    return array(
        'imdb_id'         => $imdb_id,
        'title'           => $title,
        'year'            => trim( (string) ( $first_row['year'] ?? '' ) ),
        'url'             => method_exists( $aat, 'build_entity_url_from_id' ) ? $aat->build_entity_url_from_id( $imdb_id ) : home_url( '/oscars/title/' . $imdb_id . '/' ),
        'visual'          => $aat->get_title_visual_package( $imdb_id, 'medium_large' ),
        'wins'            => count( $winner_rows ),
        'nominations'     => count( $rows ),
        'categories'      => $display_categories,
        'categories_line' => implode( ' / ', $display_categories ),
    );
}

/**
 * Build the homepage Oscar Database spotlight section.
 */
function lunara_get_home_database_spotlight() {
    $cache_key = 'lunara_home_database_spotlight_v1';
    $cached    = get_transient( $cache_key );

    if ( is_array( $cached ) && ! empty( $cached ) ) {
        return $cached;
    }

    $aat = lunara_get_oscars_plugin();
    if ( ! $aat ) {
        return array();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'academy_awards';
    $snapshot   = lunara_get_home_oscars_snapshot();

    $cards = array();
    foreach ( array_slice( (array) ( $snapshot['top_titles'] ?? array() ), 0, 5 ) as $title_entry ) {
        $card = array(
            'title'           => trim( (string) ( $title_entry['film'] ?? '' ) ),
            'year'            => trim( (string) ( $title_entry['year'] ?? '' ) ),
            'url'             => trim( (string) ( $title_entry['url'] ?? '' ) ),
            'visual'          => is_array( $title_entry['visual'] ?? null ) ? $title_entry['visual'] : array(),
            'categories_line' => trim( (string) ( $title_entry['winning_categories_line'] ?? '' ) ),
        );

        if ( '' !== $card['title'] && '' !== $card['url'] ) {
            $cards[] = $card;
        }
    }

    if ( count( $cards ) < 5 ) {
        // Fill remaining slots from the latest ceremony's most-nominated films
        $max_ceremony = intval( $wpdb->get_var( "SELECT MAX(ceremony) FROM $table_name" ) );
        if ( $max_ceremony > 0 ) {
            $existing_urls = array();
            foreach ( $cards as $c ) {
                $existing_urls[ strtolower( trim( (string) ( $c['url'] ?? '' ) ) ) ] = true;
            }

            $fill_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT film_id, film, COUNT(*) as nom_count FROM $table_name WHERE ceremony = %d AND film_id != '' AND canonical_category != '' GROUP BY film_id, film ORDER BY nom_count DESC LIMIT 10",
                    $max_ceremony
                ),
                ARRAY_A
            );

            foreach ( $fill_rows as $fill_row ) {
                if ( count( $cards ) >= 5 ) {
                    break;
                }

                $fill_card = lunara_build_home_title_story( $fill_row['film_id'], array( 'max_categories' => 3 ) );
                if ( ! empty( $fill_card ) && ! empty( $fill_card['url'] ) ) {
                    $fill_url = strtolower( trim( (string) $fill_card['url'] ) );
                    if ( ! isset( $existing_urls[ $fill_url ] ) ) {
                        $cards[]                    = $fill_card;
                        $existing_urls[ $fill_url ] = true;
                    }
                }
            }
        }
    }

    $spotlight = array(
        'database_url'      => method_exists( $aat, 'get_database_url' ) ? $aat->get_database_url() : home_url( '/oscars/' ),
        'categories_url'    => method_exists( $aat, 'get_categories_index_url' ) ? $aat->get_categories_index_url() : home_url( '/oscars/categories/' ),
        'records_total'     => intval( $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ) ),
        'winners_total'     => intval( $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE winner = 1" ) ),
        'categories_total'  => intval( $wpdb->get_var( "SELECT COUNT(DISTINCT canonical_category) FROM $table_name WHERE canonical_category != ''" ) ),
        'ceremonies_total'  => intval( $wpdb->get_var( "SELECT COUNT(DISTINCT ceremony) FROM $table_name" ) ),
        'cards'             => $cards,
    );

    set_transient( $cache_key, $spotlight, 15 * MINUTE_IN_SECONDS );

    return $spotlight;
}

/**
 * Build the homepage "From the Ledger" story cards.
 *
 * Feature 3: Dynamic + admin-controllable.
 * - If the Customizer override IDs are set, use those.
 * - Otherwise, query the database for a rotating mix of Best Picture winners
 *   and high-nomination films, refreshed every 6 hours.
 */
function lunara_get_home_ledger_story_cards() {
    $cache_key = 'lunara_home_ledger_story_cards_v2';
    $cached    = get_transient( $cache_key );

    if ( is_array( $cached ) && ! empty( $cached ) ) {
        return $cached;
    }

    // Check for Customizer overrides first.
    $override_ids = array();
    for ( $i = 1; $i <= 4; $i++ ) {
        $id = trim( (string) get_theme_mod( 'lunara_home_ledger_card_' . $i . '_imdb_id', '' ) );
        if ( preg_match( '/^tt\d{7,8}$/i', $id ) ) {
            $override_ids[] = strtolower( $id );
        }
    }

    if ( count( $override_ids ) >= 4 ) {
        // Use the admin-selected IDs.
        $selected_ids = array_slice( $override_ids, 0, 4 );
    } else {
        // Dynamic rotation: query the database.
        $table = lunara_awards_table_name();
        if ( $table === '' ) {
            // Fallback to curated classics.
            $selected_ids = array( 'tt0070047', 'tt0074958', 'tt0477348', 'tt6751668' );
        } else {
            global $wpdb;
            $day_of_year = intval( date( 'z' ) );

            $bp_categories = "'BEST PICTURE','BEST MOTION PICTURE','OUTSTANDING PICTURE','OUTSTANDING PRODUCTION','OUTSTANDING MOTION PICTURE','UNIQUE AND ARTISTIC PICTURE'";

            // Pool 1: Random Best Picture winners (seeded by day).
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $bp_winners = $wpdb->get_col(
                "SELECT DISTINCT film_id FROM {$table} WHERE category IN ({$bp_categories}) AND winner = 1 AND film_id != '' ORDER BY RAND({$day_of_year}) LIMIT 8"
            );

            // Pool 2: High-nomination films (10+ noms).
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $high_nom = $wpdb->get_col(
                "SELECT film_id FROM {$table} WHERE film_id != '' GROUP BY film_id HAVING COUNT(*) >= 10 ORDER BY RAND({$day_of_year}) LIMIT 8"
            );

            // Pool 3: Recent ceremony highlights (latest 3 ceremonies).
            $aat = lunara_get_oscars_plugin();
            $max_ceremony = ( $aat && method_exists( $aat, 'get_max_ceremony' ) ) ? intval( $aat->get_max_ceremony() ) : 0;
            $recent_ids = array();
            if ( $max_ceremony > 0 ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $recent_ids = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT DISTINCT film_id FROM {$table} WHERE ceremony >= %d AND winner = 1 AND film_id != '' ORDER BY RAND({$day_of_year}) LIMIT 6",
                        max( 1, $max_ceremony - 2 )
                    )
                );
            }

            // Merge pools, deduplicate, pick 4.
            $all_pools = array_merge(
                array_slice( (array) $bp_winners, 0, 3 ),
                array_slice( (array) $high_nom, 0, 3 ),
                array_slice( (array) $recent_ids, 0, 3 )
            );
            $all_pools = array_values( array_unique( array_filter( $all_pools ) ) );

            // Fill in any Customizer overrides at the front.
            $selected_ids = array();
            foreach ( $override_ids as $oid ) {
                $selected_ids[] = $oid;
            }
            foreach ( $all_pools as $pool_id ) {
                if ( ! in_array( $pool_id, $selected_ids, true ) ) {
                    $selected_ids[] = $pool_id;
                }
                if ( count( $selected_ids ) >= 4 ) {
                    break;
                }
            }

            // Final fallback if database is sparse.
            $fallback_ids = array( 'tt0070047', 'tt0074958', 'tt0477348', 'tt6751668' );
            foreach ( $fallback_ids as $fid ) {
                if ( count( $selected_ids ) >= 4 ) {
                    break;
                }
                if ( ! in_array( $fid, $selected_ids, true ) ) {
                    $selected_ids[] = $fid;
                }
            }
        }
    }

    $cards = array();
    foreach ( array_slice( $selected_ids, 0, 4 ) as $imdb_id ) {
        $card = lunara_build_home_title_story(
            $imdb_id,
            array(
                'preferred_categories' => array( 'BEST PICTURE', 'DIRECTING', 'ACTOR IN A LEADING ROLE', 'ACTRESS IN A LEADING ROLE' ),
                'max_categories'       => 4,
            )
        );

        if ( ! empty( $card ) ) {
            $cards[] = $card;
        }
    }

    // Cache for 6 hours.
    set_transient( $cache_key, $cards, 6 * HOUR_IN_SECONDS );

    return $cards;
}

/* ========================================
   FEATURE 1: OSCAR SPOTLIGHT (date-based daily rotation)
   ======================================== */

/**
 * Build the homepage Oscar Spotlight section.
 * Uses date('z') (day of year 0-365) to rotate through different spotlight types.
 * Cached as a 12-hour transient.
 */
function lunara_get_home_oscar_spotlight() {
    $cache_key = 'lunara_home_oscar_spotlight_v1';
    $cached    = get_transient( $cache_key );

    if ( is_array( $cached ) && ! empty( $cached ) ) {
        return $cached;
    }

    $table = lunara_awards_table_name();
    if ( $table === '' ) {
        return array();
    }

    global $wpdb;
    $aat       = lunara_get_oscars_plugin();
    $day       = intval( date( 'z' ) );
    $month     = intval( date( 'n' ) );
    $result    = array();

    $bp_categories = "'BEST PICTURE','BEST MOTION PICTURE','OUTSTANDING PICTURE','OUTSTANDING PRODUCTION','OUTSTANDING MOTION PICTURE','UNIQUE AND ARTISTIC PICTURE'";

    if ( $day <= 72 ) {
        // --- "On This Day" — ceremonies/wins from the current month across all years ---
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $ceremony_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ceremony, year, film, film_id FROM {$table} WHERE category IN ({$bp_categories}) AND winner = 1 ORDER BY RAND(%d) LIMIT 1",
                $day + $month * 100
            ),
            ARRAY_A
        );

        if ( empty( $ceremony_row ) ) {
            return array();
        }

        $ceremony_num  = intval( $ceremony_row['ceremony'] );
        $film          = trim( (string) ( $ceremony_row['film'] ?? '' ) );
        $film_id       = trim( (string) ( $ceremony_row['film_id'] ?? '' ) );

        // Get stats for this ceremony.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $ceremony_stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) AS total_noms, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS total_wins, COUNT(DISTINCT canonical_category) AS categories FROM {$table} WHERE ceremony = %d",
                $ceremony_num
            ),
            ARRAY_A
        );

        $ceremony_label = ( $aat && method_exists( $aat, 'ordinal' ) ) ? $aat->ordinal( $ceremony_num ) . ' Academy Awards' : sprintf( 'Ceremony %d', $ceremony_num );
        $ceremony_url   = ( $aat && method_exists( $aat, 'get_ceremony_url' ) ) ? $aat->get_ceremony_url( $ceremony_num ) : home_url( '/oscars/ceremony/' . $ceremony_num . '/' );

        $visual = array();
        if ( $film_id !== '' && $aat && method_exists( $aat, 'get_title_visual_package' ) ) {
            $visual = $aat->get_title_visual_package( $film_id, 'medium_large' );
        }

        $result = array(
            'kicker'        => 'On This Day in Oscar History',
            'title'         => sprintf( '%s won Best Picture at the %s', $film, $ceremony_label ),
            'copy'          => sprintf( 'The %s featured %s categories, %s nominees, and %s winners. Explore the full ceremony breakdown.',
                $ceremony_label,
                number_format_i18n( intval( $ceremony_stats['categories'] ?? 0 ) ),
                number_format_i18n( intval( $ceremony_stats['total_noms'] ?? 0 ) ),
                number_format_i18n( intval( $ceremony_stats['total_wins'] ?? 0 ) )
            ),
            'stats'         => array(
                array( 'label' => 'Ceremony', 'value' => $ceremony_label ),
                array( 'label' => 'Year', 'value' => trim( (string) ( $ceremony_row['year'] ?? '' ) ) ),
                array( 'label' => 'Categories', 'value' => number_format_i18n( intval( $ceremony_stats['categories'] ?? 0 ) ) ),
                array( 'label' => 'Winners', 'value' => number_format_i18n( intval( $ceremony_stats['total_wins'] ?? 0 ) ) ),
            ),
            'featured_film' => array(
                'title'   => $film,
                'film_id' => $film_id,
                'visual'  => $visual,
                'url'     => $film_id !== '' ? home_url( '/oscars/title/' . $film_id . '/' ) : $ceremony_url,
            ),
            'url'           => $ceremony_url,
        );

    } elseif ( $day <= 145 ) {
        // --- "Category Deep Dive" — spotlight a random category with its most decorated nominee ---
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $category_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT canonical_category, COUNT(*) AS total, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS wins FROM {$table} WHERE canonical_category != '' GROUP BY canonical_category HAVING total >= 20 ORDER BY RAND(%d) LIMIT 1",
                $day
            ),
            ARRAY_A
        );

        if ( empty( $category_row ) ) {
            return array();
        }

        $canonical = trim( (string) $category_row['canonical_category'] );
        $category_label = ( $aat && method_exists( $aat, 'format_category_display' ) ) ? $aat->format_category_display( $canonical ) : $canonical;
        $category_url   = ( $aat && method_exists( $aat, 'get_category_url' ) ) ? $aat->get_category_url( $canonical ) : home_url( '/oscars/category/' . sanitize_title( $canonical ) . '/' );

        // Most decorated person/film in this category.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $top_nominee = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT name, film, film_id, COUNT(*) AS noms, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS wins FROM {$table} WHERE canonical_category = %s AND name != '' GROUP BY name ORDER BY noms DESC, wins DESC LIMIT 1",
                $canonical
            ),
            ARRAY_A
        );

        $film_id = trim( (string) ( $top_nominee['film_id'] ?? '' ) );
        $visual  = array();
        if ( $film_id !== '' && $aat && method_exists( $aat, 'get_title_visual_package' ) ) {
            $visual = $aat->get_title_visual_package( $film_id, 'medium_large' );
        }

        $result = array(
            'kicker'        => 'Category Deep Dive',
            'title'         => $category_label,
            'copy'          => sprintf( '%s has %s total nomination rows and %s winners in the ledger. %s leads this category with %s nominations and %s wins.',
                $category_label,
                number_format_i18n( intval( $category_row['total'] ) ),
                number_format_i18n( intval( $category_row['wins'] ) ),
                ! empty( $top_nominee['name'] ) ? $top_nominee['name'] : 'The most decorated nominee',
                number_format_i18n( intval( $top_nominee['noms'] ?? 0 ) ),
                number_format_i18n( intval( $top_nominee['wins'] ?? 0 ) )
            ),
            'stats'         => array(
                array( 'label' => 'Nominations', 'value' => number_format_i18n( intval( $category_row['total'] ) ) ),
                array( 'label' => 'Winners', 'value' => number_format_i18n( intval( $category_row['wins'] ) ) ),
                array( 'label' => 'Top Nominee', 'value' => trim( (string) ( $top_nominee['name'] ?? 'N/A' ) ) ),
                array( 'label' => 'Their Noms', 'value' => number_format_i18n( intval( $top_nominee['noms'] ?? 0 ) ) ),
            ),
            'featured_film' => array(
                'title'   => trim( (string) ( $top_nominee['film'] ?? $category_label ) ),
                'film_id' => $film_id,
                'visual'  => $visual,
                'url'     => $category_url,
            ),
            'url'           => $category_url,
        );

    } elseif ( $day <= 218 ) {
        // --- "The Record Holders" — most wins/nominations for a rotating category ---
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $record_row = $wpdb->get_row(
            "SELECT film, film_id, COUNT(*) AS noms, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS wins FROM {$table} WHERE film_id != '' AND film != '' GROUP BY film_id ORDER BY wins DESC, noms DESC LIMIT 1",
            ARRAY_A
        );

        if ( empty( $record_row ) ) {
            return array();
        }

        $film    = trim( (string) $record_row['film'] );
        $film_id = trim( (string) $record_row['film_id'] );
        $visual  = array();
        if ( $film_id !== '' && $aat && method_exists( $aat, 'get_title_visual_package' ) ) {
            $visual = $aat->get_title_visual_package( $film_id, 'medium_large' );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total_unique_films = intval( $wpdb->get_var( "SELECT COUNT(DISTINCT film_id) FROM {$table} WHERE film_id != ''" ) );

        $result = array(
            'kicker'        => 'The Record Holders',
            'title'         => sprintf( '%s holds the most Oscar wins', $film ),
            'copy'          => sprintf( 'With %s wins from %s nominations, %s stands at the top of the all-time Oscar leaderboard across %s unique films in the database.',
                number_format_i18n( intval( $record_row['wins'] ) ),
                number_format_i18n( intval( $record_row['noms'] ) ),
                $film,
                number_format_i18n( $total_unique_films )
            ),
            'stats'         => array(
                array( 'label' => 'Wins', 'value' => number_format_i18n( intval( $record_row['wins'] ) ) ),
                array( 'label' => 'Nominations', 'value' => number_format_i18n( intval( $record_row['noms'] ) ) ),
                array( 'label' => 'Unique Films', 'value' => number_format_i18n( $total_unique_films ) ),
            ),
            'featured_film' => array(
                'title'   => $film,
                'film_id' => $film_id,
                'visual'  => $visual,
                'url'     => $film_id !== '' ? home_url( '/oscars/title/' . $film_id . '/' ) : home_url( '/oscars/' ),
            ),
            'url'           => $film_id !== '' ? home_url( '/oscars/title/' . $film_id . '/' ) : home_url( '/oscars/' ),
        );

    } elseif ( $day <= 291 ) {
        // --- "Oscar Rivalries" — ceremonies where multiple films had 10+ nominations ---
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rivalry_ceremony = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ceremony, year FROM (
                    SELECT ceremony, year, film_id, COUNT(*) AS noms
                    FROM {$table}
                    WHERE film_id != ''
                    GROUP BY ceremony, film_id
                    HAVING noms >= 10
                ) AS high_nom_films
                GROUP BY ceremony
                HAVING COUNT(*) >= 2
                ORDER BY RAND(%d) LIMIT 1",
                $day
            ),
            ARRAY_A
        );

        if ( empty( $rivalry_ceremony ) ) {
            // Fallback: just pick the ceremony with the most total nominations.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $rivalry_ceremony = $wpdb->get_row(
                "SELECT ceremony, year FROM {$table} GROUP BY ceremony ORDER BY COUNT(*) DESC LIMIT 1",
                ARRAY_A
            );
        }

        if ( empty( $rivalry_ceremony ) ) {
            return array();
        }

        $ceremony_num = intval( $rivalry_ceremony['ceremony'] );
        $ceremony_label = ( $aat && method_exists( $aat, 'ordinal' ) ) ? $aat->ordinal( $ceremony_num ) . ' Academy Awards' : sprintf( 'Ceremony %d', $ceremony_num );
        $ceremony_url   = ( $aat && method_exists( $aat, 'get_ceremony_url' ) ) ? $aat->get_ceremony_url( $ceremony_num ) : home_url( '/oscars/ceremony/' . $ceremony_num . '/' );

        // Top two films at this ceremony by nomination count.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rivals = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT film, film_id, COUNT(*) AS noms, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS wins FROM {$table} WHERE ceremony = %d AND film_id != '' GROUP BY film_id ORDER BY noms DESC LIMIT 2",
                $ceremony_num
            ),
            ARRAY_A
        );

        $first  = ! empty( $rivals[0] ) ? $rivals[0] : array();
        $second = ! empty( $rivals[1] ) ? $rivals[1] : array();
        $film_id = trim( (string) ( $first['film_id'] ?? '' ) );

        $visual = array();
        if ( $film_id !== '' && $aat && method_exists( $aat, 'get_title_visual_package' ) ) {
            $visual = $aat->get_title_visual_package( $film_id, 'medium_large' );
        }

        $result = array(
            'kicker'        => 'Oscar Rivalries',
            'title'         => sprintf( '%s vs. %s at the %s',
                trim( (string) ( $first['film'] ?? 'Film A' ) ),
                trim( (string) ( $second['film'] ?? 'Film B' ) ),
                $ceremony_label
            ),
            'copy'          => sprintf( '%s led with %s nominations (%s wins) while %s earned %s nominations (%s wins). A ceremony worth revisiting.',
                trim( (string) ( $first['film'] ?? 'The front-runner' ) ),
                number_format_i18n( intval( $first['noms'] ?? 0 ) ),
                number_format_i18n( intval( $first['wins'] ?? 0 ) ),
                trim( (string) ( $second['film'] ?? 'the challenger' ) ),
                number_format_i18n( intval( $second['noms'] ?? 0 ) ),
                number_format_i18n( intval( $second['wins'] ?? 0 ) )
            ),
            'stats'         => array(
                array( 'label' => trim( (string) ( $first['film'] ?? 'Film A' ) ), 'value' => sprintf( '%s noms / %s wins', number_format_i18n( intval( $first['noms'] ?? 0 ) ), number_format_i18n( intval( $first['wins'] ?? 0 ) ) ) ),
                array( 'label' => trim( (string) ( $second['film'] ?? 'Film B' ) ), 'value' => sprintf( '%s noms / %s wins', number_format_i18n( intval( $second['noms'] ?? 0 ) ), number_format_i18n( intval( $second['wins'] ?? 0 ) ) ) ),
                array( 'label' => 'Ceremony', 'value' => $ceremony_label ),
            ),
            'featured_film' => array(
                'title'   => trim( (string) ( $first['film'] ?? '' ) ),
                'film_id' => $film_id,
                'visual'  => $visual,
                'url'     => $ceremony_url,
            ),
            'url'           => $ceremony_url,
        );

    } else {
        // --- "Ceremony Spotlight" — random historical ceremony + BP winner + stats ---
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $random_ceremony = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT DISTINCT ceremony, year FROM {$table} ORDER BY RAND(%d) LIMIT 1",
                $day
            ),
            ARRAY_A
        );

        if ( empty( $random_ceremony ) ) {
            return array();
        }

        $ceremony_num   = intval( $random_ceremony['ceremony'] );
        $ceremony_label = ( $aat && method_exists( $aat, 'ordinal' ) ) ? $aat->ordinal( $ceremony_num ) . ' Academy Awards' : sprintf( 'Ceremony %d', $ceremony_num );
        $ceremony_url   = ( $aat && method_exists( $aat, 'get_ceremony_url' ) ) ? $aat->get_ceremony_url( $ceremony_num ) : home_url( '/oscars/ceremony/' . $ceremony_num . '/' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $bp_winner = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT film, film_id FROM {$table} WHERE ceremony = %d AND category IN ({$bp_categories}) AND winner = 1 LIMIT 1",
                $ceremony_num
            ),
            ARRAY_A
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $ceremony_stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) AS total_noms, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS total_wins, COUNT(DISTINCT canonical_category) AS categories FROM {$table} WHERE ceremony = %d",
                $ceremony_num
            ),
            ARRAY_A
        );

        $film    = trim( (string) ( $bp_winner['film'] ?? 'Unknown' ) );
        $film_id = trim( (string) ( $bp_winner['film_id'] ?? '' ) );
        $visual  = array();
        if ( $film_id !== '' && $aat && method_exists( $aat, 'get_title_visual_package' ) ) {
            $visual = $aat->get_title_visual_package( $film_id, 'medium_large' );
        }

        $result = array(
            'kicker'        => 'Ceremony Spotlight',
            'title'         => $ceremony_label,
            'copy'          => sprintf( '%s won Best Picture at the %s, a ceremony with %s categories, %s nominees, and %s winners.',
                $film,
                $ceremony_label,
                number_format_i18n( intval( $ceremony_stats['categories'] ?? 0 ) ),
                number_format_i18n( intval( $ceremony_stats['total_noms'] ?? 0 ) ),
                number_format_i18n( intval( $ceremony_stats['total_wins'] ?? 0 ) )
            ),
            'stats'         => array(
                array( 'label' => 'Best Picture', 'value' => $film ),
                array( 'label' => 'Year', 'value' => trim( (string) ( $random_ceremony['year'] ?? '' ) ) ),
                array( 'label' => 'Categories', 'value' => number_format_i18n( intval( $ceremony_stats['categories'] ?? 0 ) ) ),
                array( 'label' => 'Winners', 'value' => number_format_i18n( intval( $ceremony_stats['total_wins'] ?? 0 ) ) ),
            ),
            'featured_film' => array(
                'title'   => $film,
                'film_id' => $film_id,
                'visual'  => $visual,
                'url'     => $film_id !== '' ? home_url( '/oscars/title/' . $film_id . '/' ) : $ceremony_url,
            ),
            'url'           => $ceremony_url,
        );
    }

    if ( ! empty( $result ) ) {
        set_transient( $cache_key, $result, 12 * HOUR_IN_SECONDS );
    }

    return $result;
}

/* ========================================
   FEATURE 2: DEEP CUT STATS
   ======================================== */

/**
 * Build the homepage Deep Cut Stats section.
 * Queries the database for 4 interesting stats, rotating daily using date('z').
 * Cached as a 24-hour transient.
 */
function lunara_get_home_deep_cuts() {
    $cache_key = 'lunara_home_deep_cuts_v1';
    $cached    = get_transient( $cache_key );

    if ( is_array( $cached ) && ! empty( $cached ) ) {
        return $cached;
    }

    $table = lunara_awards_table_name();
    if ( $table === '' ) {
        return array();
    }

    global $wpdb;
    $aat = lunara_get_oscars_plugin();
    $day = intval( date( 'z' ) );

    // Build a pool of stats, then pick 4 based on the day.
    $pool = array();

    // 1. Most wins by a single film.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $most_wins_film = $wpdb->get_row(
        "SELECT film, film_id, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS wins FROM {$table} WHERE film_id != '' GROUP BY film_id ORDER BY wins DESC LIMIT 1",
        ARRAY_A
    );
    if ( ! empty( $most_wins_film ) ) {
        $pool[] = array(
            'label'   => 'Most Wins by a Single Film',
            'value'   => number_format_i18n( intval( $most_wins_film['wins'] ) ),
            'context' => trim( (string) $most_wins_film['film'] ),
            'url'     => ! empty( $most_wins_film['film_id'] ) ? home_url( '/oscars/title/' . $most_wins_film['film_id'] . '/' ) : home_url( '/oscars/' ),
        );
    }

    // 2. Total unique films in database.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $unique_films = intval( $wpdb->get_var( "SELECT COUNT(DISTINCT film_id) FROM {$table} WHERE film_id != ''" ) );
    if ( $unique_films > 0 ) {
        $pool[] = array(
            'label'   => 'Unique Films in the Ledger',
            'value'   => number_format_i18n( $unique_films ),
            'context' => 'Every nominated and winning film, tracked',
            'url'     => home_url( '/oscars/' ),
        );
    }

    // 3. Total unique people in database.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $unique_people = intval( $wpdb->get_var( "SELECT COUNT(DISTINCT name) FROM {$table} WHERE name != ''" ) );
    if ( $unique_people > 0 ) {
        $pool[] = array(
            'label'   => 'Unique People Nominated',
            'value'   => number_format_i18n( $unique_people ),
            'context' => 'Actors, directors, writers, and craftspeople',
            'url'     => home_url( '/oscars/' ),
        );
    }

    // 4. Most nominated film without winning Best Picture.
    $bp_categories = "'BEST PICTURE','BEST MOTION PICTURE','OUTSTANDING PICTURE','OUTSTANDING PRODUCTION','OUTSTANDING MOTION PICTURE','UNIQUE AND ARTISTIC PICTURE'";
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $most_nom_no_bp = $wpdb->get_row(
        "SELECT film, film_id, COUNT(*) AS noms FROM {$table}
         WHERE film_id != '' AND film_id NOT IN (
             SELECT film_id FROM {$table} WHERE category IN ({$bp_categories}) AND winner = 1 AND film_id != ''
         )
         GROUP BY film_id ORDER BY noms DESC LIMIT 1",
        ARRAY_A
    );
    if ( ! empty( $most_nom_no_bp ) ) {
        $pool[] = array(
            'label'   => 'Most Nominated Without Best Picture',
            'value'   => number_format_i18n( intval( $most_nom_no_bp['noms'] ) ) . ' noms',
            'context' => trim( (string) $most_nom_no_bp['film'] ),
            'url'     => ! empty( $most_nom_no_bp['film_id'] ) ? home_url( '/oscars/title/' . $most_nom_no_bp['film_id'] . '/' ) : home_url( '/oscars/' ),
        );
    }

    // 5. Most nominated person without a win.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $most_nom_no_win = $wpdb->get_row(
        "SELECT name, COUNT(*) AS noms FROM {$table} WHERE name != '' GROUP BY name HAVING SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) = 0 ORDER BY noms DESC LIMIT 1",
        ARRAY_A
    );
    if ( ! empty( $most_nom_no_win ) ) {
        $pool[] = array(
            'label'   => 'Most Nominated Without a Win',
            'value'   => number_format_i18n( intval( $most_nom_no_win['noms'] ) ) . ' noms',
            'context' => trim( (string) $most_nom_no_win['name'] ),
            'url'     => home_url( '/oscars/' ),
        );
    }

    // 6. Director with most Best Picture wins.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $top_director = $wpdb->get_row(
        "SELECT name, COUNT(*) AS wins FROM {$table} WHERE canonical_category = 'DIRECTING' AND winner = 1 AND name != '' GROUP BY name ORDER BY wins DESC LIMIT 1",
        ARRAY_A
    );
    if ( ! empty( $top_director ) ) {
        $pool[] = array(
            'label'   => 'Most Best Director Wins',
            'value'   => number_format_i18n( intval( $top_director['wins'] ) ),
            'context' => trim( (string) $top_director['name'] ),
            'url'     => home_url( '/oscars/' ),
        );
    }

    // 7. Total ceremonies.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $total_ceremonies = intval( $wpdb->get_var( "SELECT COUNT(DISTINCT ceremony) FROM {$table}" ) );
    if ( $total_ceremonies > 0 ) {
        $pool[] = array(
            'label'   => 'Ceremonies in the Ledger',
            'value'   => number_format_i18n( $total_ceremonies ),
            'context' => 'From the 1st Academy Awards to the latest',
            'url'     => home_url( '/oscars/' ),
        );
    }

    // 8. Most competitive ceremony (highest ratio of nominees to winners).
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $competitive = $wpdb->get_row(
        "SELECT ceremony, COUNT(*) AS total, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS wins FROM {$table} GROUP BY ceremony HAVING wins > 0 ORDER BY (total / wins) DESC LIMIT 1",
        ARRAY_A
    );
    if ( ! empty( $competitive ) ) {
        $comp_ceremony = intval( $competitive['ceremony'] );
        $comp_label    = ( $aat && method_exists( $aat, 'ordinal' ) ) ? $aat->ordinal( $comp_ceremony ) : (string) $comp_ceremony;
        $pool[] = array(
            'label'   => 'Most Competitive Ceremony',
            'value'   => sprintf( '%s:%s', number_format_i18n( intval( $competitive['total'] ) ), number_format_i18n( intval( $competitive['wins'] ) ) ),
            'context' => sprintf( '%s ceremony (nominees to winners)', $comp_label ),
            'url'     => ( $aat && method_exists( $aat, 'get_ceremony_url' ) ) ? $aat->get_ceremony_url( $comp_ceremony ) : home_url( '/oscars/ceremony/' . $comp_ceremony . '/' ),
        );
    }

    // 9. Person with longest span between first and last nomination.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $longest_span = $wpdb->get_row(
        "SELECT name, MIN(ceremony) AS first_nom, MAX(ceremony) AS last_nom, (MAX(ceremony) - MIN(ceremony)) AS span FROM {$table} WHERE name != '' GROUP BY name HAVING COUNT(*) >= 2 ORDER BY span DESC LIMIT 1",
        ARRAY_A
    );
    if ( ! empty( $longest_span ) && intval( $longest_span['span'] ) > 0 ) {
        $pool[] = array(
            'label'   => 'Longest Career Span',
            'value'   => number_format_i18n( intval( $longest_span['span'] ) ) . ' ceremonies',
            'context' => trim( (string) $longest_span['name'] ),
            'url'     => home_url( '/oscars/' ),
        );
    }

    // 10. Category with most nominees per year (on average).
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $busiest_cat = $wpdb->get_row(
        "SELECT canonical_category, COUNT(*) AS total, COUNT(DISTINCT ceremony) AS ceremonies FROM {$table} WHERE canonical_category != '' GROUP BY canonical_category HAVING ceremonies >= 5 ORDER BY (total / ceremonies) DESC LIMIT 1",
        ARRAY_A
    );
    if ( ! empty( $busiest_cat ) ) {
        $cat_label = ( $aat && method_exists( $aat, 'format_category_display' ) ) ? $aat->format_category_display( $busiest_cat['canonical_category'] ) : $busiest_cat['canonical_category'];
        $avg = round( intval( $busiest_cat['total'] ) / max( 1, intval( $busiest_cat['ceremonies'] ) ), 1 );
        $pool[] = array(
            'label'   => 'Most Nominees Per Year (Avg)',
            'value'   => number_format( $avg, 1 ),
            'context' => $cat_label,
            'url'     => ( $aat && method_exists( $aat, 'get_category_url' ) ) ? $aat->get_category_url( $busiest_cat['canonical_category'] ) : home_url( '/oscars/' ),
        );
    }

    if ( count( $pool ) < 4 ) {
        return array();
    }

    // Deterministic daily rotation: pick 4 stats based on day of year.
    $pool_count = count( $pool );
    $start      = $day % $pool_count;
    $selected   = array();
    for ( $i = 0; $i < 4; $i++ ) {
        $selected[] = $pool[ ( $start + $i ) % $pool_count ];
    }

    set_transient( $cache_key, $selected, DAY_IN_SECONDS );

    return $selected;
}

/**
 * Render the homepage latest/current-release review block.
 *
 * This definition loads before the monolithic fallback in functions.php, so it
 * becomes the active renderer for the lunara/latest-reviews block.
 */
if ( ! function_exists( 'lunara_render_homepage_latest_reviews' ) ) {
    function lunara_render_homepage_latest_reviews( $attrs = array() ) {
        $attrs = is_array( $attrs ) ? $attrs : array();

        $count     = isset( $attrs['count'] ) ? max( 1, min( 24, (int) $attrs['count'] ) ) : 8;
        $source    = isset( $attrs['source'] ) ? sanitize_key( (string) $attrs['source'] ) : 'curated';
        $heading   = isset( $attrs['heading'] ) ? trim( (string) $attrs['heading'] ) : '';
        $kicker    = isset( $attrs['kicker'] ) ? trim( (string) $attrs['kicker'] ) : '';
        $cta_label = isset( $attrs['ctaLabel'] ) ? trim( (string) $attrs['ctaLabel'] ) : '';
        $cta_url   = isset( $attrs['ctaUrl'] ) ? trim( (string) $attrs['ctaUrl'] ) : '';
        $source    = in_array( $source, array( 'curated', 'latest', 'hero' ), true ) ? $source : 'curated';

        $current_release_ids = function_exists( 'lunara_home_latest_review_ids' ) ? lunara_home_latest_review_ids() : array();
        $has_current_release = ! empty( $current_release_ids );

        if ( '' === $heading ) {
            $heading = __( 'Latest Reviews', 'lunara-film' );
        }
        if ( '' === $kicker ) {
            $kicker = $has_current_release ? __( 'Current Release Spotlight', 'lunara-film' ) : __( 'Lunara Reviews', 'lunara-film' );
        }
        if ( '' === $cta_label ) {
            $cta_label = __( 'All Reviews', 'lunara-film' );
        }
        if ( '' === $cta_url ) {
            $cta_url = home_url( '/reviews/' );
        }

        if ( function_exists( 'lunara_repair_mojibake_text' ) ) {
            $heading   = lunara_repair_mojibake_text( $heading );
            $kicker    = lunara_repair_mojibake_text( $kicker );
            $cta_label = lunara_repair_mojibake_text( $cta_label );
        }

        if ( $has_current_release && function_exists( 'lunara_home_latest_reviews_query' ) ) {
            $latest = lunara_home_latest_reviews_query( $count );
            $source = 'current-release';
        } elseif ( 'latest' === $source && function_exists( 'lunara_home_latest_reviews_query' ) ) {
            $latest = lunara_home_latest_reviews_query( $count );
        } elseif ( 'latest' === $source && function_exists( 'lunara_latest_reviews_query' ) ) {
            $latest = lunara_latest_reviews_query( $count );
        } elseif ( 'hero' === $source && function_exists( 'lunara_home_hero_reviews_query' ) ) {
            $latest = lunara_home_hero_reviews_query( $count );
        } elseif ( function_exists( 'lunara_home_featured_reviews_query' ) ) {
            $latest = lunara_home_featured_reviews_query( $count );
        } elseif ( function_exists( 'lunara_latest_reviews_query' ) ) {
            $latest = lunara_latest_reviews_query( $count );
        } else {
            $latest = new WP_Query(
                array(
                    'post_type'      => 'review',
                    'posts_per_page' => $count,
                    'post_status'    => 'publish',
                    'no_found_rows'  => true,
                )
            );
        }

        if ( ! ( $latest instanceof WP_Query ) || ! $latest->have_posts() ) {
            return '';
        }

        ob_start();
        ?>
        <section class="lunara-home-section lunara-home-slot-latest-reviews lunara-latest-reviews-section" data-review-source="<?php echo esc_attr( $source ); ?>" aria-label="<?php esc_attr_e( 'Latest Reviews', 'lunara-film' ); ?>">
            <div class="lunara-home-section-head">
                <div>
                    <p class="lunara-home-section-kicker"><?php echo esc_html( $kicker ); ?></p>
                    <h2 class="lunara-home-section-title"><?php echo esc_html( $heading ); ?></h2>
                </div>
                <a class="lunara-section-link" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
            </div>
            <div class="lunara-review-grid lunara-review-archive-uniform">
                <?php
                while ( $latest->have_posts() ) :
                    $latest->the_post();
                    $review_index       = max( 0, (int) $latest->current_post );
                    $rid                = get_the_ID();
                    $is_current_release = in_array( $rid, $current_release_ids, true );
                    $score              = get_post_meta( $rid, '_lunara_score', true );
                    // Always give a card a blurb: hand-set pull-quote first, otherwise
                    // fall back to the review's excerpt / opening lines so no card is empty.
                    $quote              = function_exists( 'lunara_get_review_card_pull_quote' )
                        ? lunara_get_review_card_pull_quote( $rid, 22, true )
                        : wp_trim_words( wp_strip_all_tags( get_the_excerpt( $rid ) ), 22, '...' );
                    $thumb_attrs        = array(
                        'class'    => 'lunara-review-grid-poster',
                        'loading'  => 'eager',
                        'decoding' => 'async',
                        'sizes'    => '(max-width: 520px) 46vw, (max-width: 900px) 42vw, (max-width: 1180px) 30vw, 340px',
                    );

                    if ( 0 === $review_index ) {
                        $thumb_attrs['fetchpriority'] = 'high';
                    }

                    $image_data = function_exists( 'lunara_get_review_card_image_data' )
                        ? lunara_get_review_card_image_data( $rid, 'lunara-review-card', $thumb_attrs )
                        : array(
                            'url'  => has_post_thumbnail( $rid ) ? get_the_post_thumbnail_url( $rid, 'medium_large' ) : '',
                            'html' => has_post_thumbnail( $rid ) ? get_the_post_thumbnail( $rid, 'medium_large', $thumb_attrs ) : '',
                        );
                    $thumb_url       = isset( $image_data['url'] ) ? (string) $image_data['url'] : '';
                    $has_thumb_html  = ! empty( $image_data['html'] );
                    $use_fallback_bg = '' !== $thumb_url && ! $has_thumb_html;
                    $has_card_media  = $has_thumb_html || $use_fallback_bg;
                    ?>
                    <article class="lunara-review-grid-card<?php echo $is_current_release ? ' is-current-release-spotlight' : ''; ?> <?php echo $has_card_media ? 'has-visual' : 'has-no-visual'; ?>">
                        <a class="lunara-review-grid-link" href="<?php the_permalink(); ?>">
                            <?php if ( $has_card_media ) : ?>
                                <div class="lunara-review-grid-poster-wrap<?php echo $use_fallback_bg ? ' has-poster-bg has-fallback-bg' : ''; ?>"<?php if ( $use_fallback_bg ) : ?> style="background-image: url('<?php echo esc_url( $thumb_url ); ?>');"<?php endif; ?>>
                                    <?php if ( $has_thumb_html ) : ?>
                                        <?php echo $image_data['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <?php endif; ?>
                                    <?php if ( $score && function_exists( 'lunara_render_stars' ) ) : ?>
                                        <span class="lunara-score-badge"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="lunara-review-grid-copy">
                                <p class="lunara-review-grid-kicker"><?php esc_html_e( 'Lunara Review', 'lunara-film' ); ?></p>
                                <?php if ( ! $has_card_media && $score && function_exists( 'lunara_render_stars' ) ) : ?>
                                    <span class="lunara-score-badge is-inline-score"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span>
                                <?php endif; ?>
                                <?php if ( function_exists( 'lunara_render_trailer_card_badge' ) ) : ?>
                                    <?php echo lunara_render_trailer_card_badge( $rid, 'review-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php endif; ?>
                                <h3 class="lunara-review-grid-title"><?php the_title(); ?></h3>
                                <?php if ( '' !== trim( $quote ) ) : ?>
                                    <p class="lunara-review-grid-excerpt lunara-review-grid-quote"><?php echo esc_html( $quote ); ?></p>
                                <?php endif; ?>
                                <?php if ( $is_current_release ) : ?>
                                    <p class="lunara-review-grid-updated"><?php esc_html_e( 'Current Release Spotlight', 'lunara-film' ); ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                    </article>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </section>
        <?php

        return (string) ob_get_clean();
    }
}
