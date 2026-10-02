<?php
/**
 * Atomic public Debrief renderer.
 *
 * The canonical renderer is staging-only and opt-in during the compatibility
 * window. It resolves the complete Review-owned record before emitting markup;
 * any missing or invalid canonical value falls back to the complete legacy
 * module without mixing sources.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'lunara_is_debrief_renderer_staging_environment' ) ) {
    /**
     * Whether this request is running on the WordPress.com staging site.
     *
     * @return bool
     */
    function lunara_is_debrief_renderer_staging_environment() {
        $environment = function_exists( 'wp_get_environment_type' )
            ? (string) wp_get_environment_type()
            : '';

        if ( 'staging' === $environment ) {
            return true;
        }

        $home_url = function_exists( 'home_url' ) ? (string) home_url( '/' ) : '';
        $host     = strtolower( (string) parse_url( $home_url, PHP_URL_HOST ) );
        $suffix   = '.wpcomstaging.com';

        return strlen( $host ) > strlen( $suffix )
            && $suffix === substr( $host, -strlen( $suffix ) );
    }
}

if ( ! function_exists( 'lunara_debrief_public_renderer_enabled' ) ) {
    /**
     * Staging kill switch for the canonical renderer.
     *
     * The filter intentionally runs after the environment guard so no filter
     * can turn this release on in production.
     *
     * @return bool
     */
    function lunara_debrief_public_renderer_enabled() {
        if ( ! lunara_is_debrief_renderer_staging_environment() ) {
            return false;
        }

        $stored  = function_exists( 'get_theme_mod' )
            ? get_theme_mod( 'lunara_debrief_public_renderer_enabled', false )
            : false;
        $enabled = true === $stored || '1' === (string) $stored || 'true' === strtolower( (string) $stored );

        return (bool) apply_filters( 'lunara_debrief_public_renderer_enabled', $enabled );
    }
}

if ( ! function_exists( 'lunara_debrief_empty_render_parts' ) ) {
    /**
     * Stable empty public-render result.
     *
     * @return array<string,mixed>
     */
    function lunara_debrief_empty_render_parts() {
        return array(
            'source'        => 'none',
            'has_content'   => false,
            'signature_html' => '',
            'media_html'    => '',
            'pairings_html' => '',
        );
    }
}

if ( ! function_exists( 'lunara_get_review_debrief_render_parts' ) ) {
    /**
     * Return one atomic, request-cached Debrief render decision.
     *
     * @param int $review_id Review post ID.
     * @return array<string,mixed>
     */
    function lunara_get_review_debrief_render_parts( $review_id = 0 ) {
        static $cache = array();

        $review_id = $review_id ? absint( $review_id ) : absint( get_the_ID() );
        if ( ! $review_id ) {
            return lunara_debrief_empty_render_parts();
        }

        $canonical_enabled = lunara_debrief_public_renderer_enabled();
        $cache_key         = $review_id . ':' . ( $canonical_enabled ? 'canonical' : 'legacy' );

        if ( isset( $cache[ $cache_key ] ) ) {
            return $cache[ $cache_key ];
        }

        if ( $canonical_enabled ) {
            $canonical = lunara_debrief_build_canonical_render_parts( $review_id );
            if ( is_array( $canonical ) ) {
                $cache[ $cache_key ] = $canonical;
                return $canonical;
            }
        }

        $cache[ $cache_key ] = lunara_debrief_build_legacy_render_parts( $review_id );
        return $cache[ $cache_key ];
    }
}

if ( ! function_exists( 'lunara_render_review_debrief' ) ) {
    /**
     * Render the complete server-side Debrief content for native block callers.
     *
     * @param int $review_id Review post ID.
     * @return string
     */
    function lunara_render_review_debrief( $review_id = 0 ) {
        $parts = lunara_get_review_debrief_render_parts( $review_id );
        if ( empty( $parts['has_content'] ) ) {
            return '';
        }

        return (string) $parts['signature_html'] . (string) $parts['pairings_html'];
    }
}

if ( ! function_exists( 'lunara_debrief_build_canonical_render_parts' ) ) {
    /**
     * Build canonical markup only after the complete record resolves locally.
     *
     * @param int $review_id Review post ID.
     * @return array<string,mixed>|null
     */
    function lunara_debrief_build_canonical_render_parts( $review_id ) {
        $data = lunara_debrief_get_canonical_public_data( $review_id );
        if ( ! is_array( $data ) ) {
            return null;
        }

        $signature = lunara_debrief_render_canonical_signature( $review_id, $data['reviewed_film'] );
        $media     = lunara_debrief_render_canonical_media( $review_id, $data['reviewed_film'] );
        $cards     = lunara_debrief_render_canonical_pairing_cards( $review_id, $data['pairings'] );
        if ( '' === trim( $cards ) ) {
            return null;
        }

        return array(
            'source'         => 'canonical',
            'has_content'    => true,
            'signature_html' => $signature,
            'media_html'     => $media,
            'pairings_html'  => '<div class="lunara-review-single-debrief-pairings-modern">' . $cards . '</div>',
        );
    }
}

if ( ! function_exists( 'lunara_debrief_get_canonical_public_data' ) ) {
    /**
     * Validate and resolve the entire canonical Debrief without presentation.
     *
     * @param int $review_id Review post ID.
     * @return array<string,mixed>|null
     */
    function lunara_debrief_get_canonical_public_data( $review_id ) {
        if (
            ! function_exists( 'lunara_debrief_get_review_record' )
            || ! function_exists( 'lunara_debrief_validate_record' )
            || ! function_exists( 'lunara_debrief_resolve_movie' )
        ) {
            return null;
        }

        $record = lunara_debrief_get_review_record( $review_id );
        if ( ! is_array( $record ) || 'published' !== (string) ( $record['status'] ?? '' ) ) {
            return null;
        }

        $validation = lunara_debrief_validate_record( $record, true );
        if (
            ! is_array( $validation )
            || empty( $validation['valid'] )
            || empty( $validation['complete'] )
            || ! isset( $validation['record'] )
            || ! is_array( $validation['record'] )
        ) {
            return null;
        }

        $record = $validation['record'];
        if ( 'published' !== (string) ( $record['status'] ?? '' ) ) {
            return null;
        }

        $role_order = array( 'theme_echo', 'counter_program', 'career_context' );
        $pairings   = isset( $record['pairings'] ) && is_array( $record['pairings'] )
            ? $record['pairings']
            : array();

        if ( 3 !== count( $pairings ) ) {
            return null;
        }

        $pairings_by_role = array();
        foreach ( $pairings as $pairing ) {
            if ( ! is_array( $pairing ) ) {
                return null;
            }

            $role = (string) ( $pairing['role'] ?? '' );
            if ( ! in_array( $role, $role_order, true ) || isset( $pairings_by_role[ $role ] ) ) {
                return null;
            }
            $pairings_by_role[ $role ] = $pairing;
        }

        $resolver_args = array(
            'require_published'      => true,
            'resolve_poster'         => true,
            'allow_aat_local_poster' => false,
            'resolve_awards'         => false,
        );
        $source_id = absint( $record['reviewed_film']['movie_id'] ?? 0 );
        $source    = $source_id ? lunara_debrief_resolve_movie( $source_id, $resolver_args ) : array();

        if ( ! lunara_debrief_public_snapshot_is_valid( $source ) ) {
            return null;
        }

        $resolved_pairings = array();
        foreach ( $role_order as $role ) {
            if ( ! isset( $pairings_by_role[ $role ] ) ) {
                return null;
            }

            $pairing  = $pairings_by_role[ $role ];
            $movie_id = absint( $pairing['film']['movie_id'] ?? 0 );
            $reason   = trim( (string) ( $pairing['editorial_reason'] ?? '' ) );
            $film     = $movie_id ? lunara_debrief_resolve_movie( $movie_id, $resolver_args ) : array();

            if ( '' === $reason || ! lunara_debrief_public_snapshot_is_valid( $film ) ) {
                return null;
            }

            $resolved_pairings[] = array(
                'role'             => $role,
                'editorial_reason' => $reason,
                'film'             => $film,
            );
        }

        return array(
            'reviewed_film' => $source,
            'pairings'      => $resolved_pairings,
        );
    }
}

if ( ! function_exists( 'lunara_debrief_public_snapshot_is_valid' ) ) {
    /**
     * Defensive public resolver check at the rendering boundary.
     *
     * @param mixed $snapshot Resolver snapshot.
     * @return bool
     */
    function lunara_debrief_public_snapshot_is_valid( $snapshot ) {
        return is_array( $snapshot )
            && ! empty( $snapshot['valid'] )
            && 'publish' === (string) ( $snapshot['post_status'] ?? '' )
            && absint( $snapshot['movie_id'] ?? 0 ) > 0
            && '' !== trim( (string) ( $snapshot['title'] ?? '' ) )
            && '' !== trim( (string) ( $snapshot['imdb_title_id'] ?? '' ) )
            && '' !== trim( (string) ( $snapshot['permalink'] ?? '' ) );
    }
}

if ( ! function_exists( 'lunara_debrief_render_canonical_signature' ) ) {
    /**
     * Render the native signature panel from Review metadata and local film data.
     *
     * @param int                 $review_id Review post ID.
     * @param array<string,mixed> $film Reviewed-film snapshot.
     * @return string
     */
    function lunara_debrief_render_canonical_signature( $review_id, $film ) {
        $score  = trim( (string) get_post_meta( $review_id, '_lunara_score', true ) );
        $year   = trim( (string) get_post_meta( $review_id, '_lunara_year', true ) );
        $where  = trim( (string) get_post_meta( $review_id, '_lunara_where', true ) );
        $kicker = trim( (string) get_theme_mod( 'lunara_debrief_kicker_text', 'A LUNARA FILM SIGNATURE' ) );

        if ( '' === $year ) {
            $year = trim( (string) ( $film['year'] ?? '' ) );
        }

        $html  = '<section class="lunara-debrief-block lunara-debrief-block--signature">';
        $html .= '<h3 class="lunara-debrief-heading">' . esc_html__( 'LUNARA DEBRIEF', 'lunara-film' ) . '</h3>';
        if ( '' !== $kicker ) {
            $html .= '<div class="lunara-debrief-kicker">' . esc_html( $kicker ) . '</div>';
        }
        $html .= '<ul class="lunara-debrief-list lunara-debrief-list--signature">';

        if ( '' !== $score ) {
            $html .= '<li><strong>' . esc_html__( 'Score:', 'lunara-film' ) . '</strong><span class="lunara-debrief-value">' . lunara_render_stars( $score ) . '</span></li>';
        }

        if ( '' !== $year ) {
            $html .= '<li><strong>' . esc_html__( 'Year:', 'lunara-film' ) . '</strong><span class="lunara-debrief-value">' . esc_html( $year ) . '</span></li>';
        }

        if ( '' !== $where ) {
            $where_markup = function_exists( 'lunara_render_review_where_links' )
                ? (string) lunara_render_review_where_links( $where, get_the_title( $review_id ) )
                : '';
            $where_value  = '' !== trim( $where_markup ) ? wp_kses_post( $where_markup ) : esc_html( $where );
            $html        .= '<li class="lunara-debrief-where-row"><strong>' . esc_html__( 'Where to Watch:', 'lunara-film' ) . '</strong><span class="lunara-debrief-value">' . $where_value . '</span></li>';
        }

        $html .= '</ul></section>';
        return $html;
    }
}

if ( ! function_exists( 'lunara_debrief_render_canonical_media' ) ) {
    /**
     * Render the reviewed-film poster from a local attachment only.
     *
     * @param int                 $review_id Review post ID.
     * @param array<string,mixed> $film Reviewed-film snapshot.
     * @return string
     */
    function lunara_debrief_render_canonical_media( $review_id, $film ) {
        $poster_id = absint( $film['poster_attachment_id'] ?? 0 );
        if ( ! $poster_id ) {
            return '';
        }

        $title       = trim( (string) ( $film['title'] ?? get_the_title( $review_id ) ) );
        $poster_html = wp_get_attachment_image(
            $poster_id,
            'lunara-poster-library',
            false,
            array(
                'class'          => 'lunara-review-single-debrief-poster',
                'loading'        => 'lazy',
                'decoding'       => 'async',
                'sizes'          => '(max-width: 900px) 42vw, 320px',
                'alt'            => '' !== $title ? $title . ' poster' : __( 'Reviewed film poster', 'lunara-film' ),
                'data-image-title' => $title,
            )
        );

        if ( '' === trim( (string) $poster_html ) ) {
            return '';
        }

        $meta = trim( (string) ( $film['year'] ?? '' ) );
        if ( '' === $meta ) {
            $meta = __( 'Review anchor', 'lunara-film' );
        }

        return sprintf(
            '<aside class="lunara-review-single-debrief-media" aria-label="%1$s"><p class="lunara-home-section-kicker">%2$s</p><div class="lunara-review-single-debrief-poster-shell">%3$s</div><div class="lunara-review-single-debrief-media-copy"><p class="lunara-review-single-debrief-media-title">%4$s</p><p class="lunara-review-single-debrief-media-meta">%5$s</p></div></aside>',
            esc_attr__( 'Reviewed film poster', 'lunara-film' ),
            esc_html__( 'Reviewed Film', 'lunara-film' ),
            $poster_html,
            esc_html( $title ),
            esc_html( $meta )
        );
    }
}

if ( ! function_exists( 'lunara_debrief_render_canonical_pairing_cards' ) ) {
    /**
     * Shape resolved canonical films for the shared card markup renderer.
     *
     * @param int                       $review_id Review post ID.
     * @param array<int,array<string,mixed>> $pairings Resolved pairings.
     * @return string
     */
    function lunara_debrief_render_canonical_pairing_cards( $review_id, $pairings ) {
        $roles = array(
            'theme_echo' => array( 'slug' => 'theme', 'label' => __( 'Theme Echo', 'lunara-film' ) ),
            'counter_program' => array( 'slug' => 'counter', 'label' => __( 'Counter-Program', 'lunara-film' ) ),
            'career_context' => array( 'slug' => 'career', 'label' => __( 'Career Context', 'lunara-film' ) ),
        );
        $rows  = array();

        foreach ( $pairings as $pairing ) {
            $role = (string) ( $pairing['role'] ?? '' );
            $film = isset( $pairing['film'] ) && is_array( $pairing['film'] ) ? $pairing['film'] : array();
            if ( ! isset( $roles[ $role ] ) ) {
                return '';
            }

            $title     = trim( (string) ( $film['title'] ?? '' ) );
            $poster_id = absint( $film['poster_attachment_id'] ?? 0 );
            $poster    = '';
            if ( $poster_id ) {
                $poster = (string) wp_get_attachment_image(
                    $poster_id,
                    'lunara-poster-library',
                    false,
                    array(
                        'class'    => 'lunara-pair-preview-thumb',
                        'loading'  => 'lazy',
                        'decoding' => 'async',
                        'sizes'    => '(max-width: 680px) 32vw, 180px',
                        'alt'      => '' !== $title ? $title . ' poster' : __( 'Companion film poster', 'lunara-film' ),
                    )
                );
            }

            $rows[] = array(
                'slug'  => $roles[ $role ]['slug'],
                'label' => $roles[ $role ]['label'],
                'data'  => array(
                    'tt'              => (string) ( $film['imdb_title_id'] ?? '' ),
                    'title'           => $title,
                    'title_base'      => $title,
                    'year'            => (string) ( $film['year'] ?? '' ),
                    'note'            => (string) ( $pairing['editorial_reason'] ?? '' ),
                    'title_href'      => (string) ( $film['permalink'] ?? '' ),
                    'title_href_type' => 'entity',
                    'imdb_href'       => (string) ( $film['imdb_url'] ?? '' ),
                    'show_oscar_ledger' => false,
                    'poster_html'     => $poster,
                    'watch_html'      => '',
                ),
            );
        }

        return 3 === count( $rows ) ? lunara_debrief_render_pair_card_group( $review_id, $rows ) : '';
    }
}

if ( ! function_exists( 'lunara_debrief_build_legacy_render_parts' ) ) {
    /**
     * Preserve the complete verified legacy module without relational mixing.
     *
     * @param int $review_id Review post ID.
     * @return array<string,mixed>
     */
    function lunara_debrief_build_legacy_render_parts( $review_id ) {
        $block = function_exists( 'lunara_debrief_shortcode' )
            ? (string) lunara_debrief_shortcode( array() )
            : '';
        $split = function_exists( 'lunara_split_review_debrief_block' )
            ? lunara_split_review_debrief_block( $block )
            : array( 'signature' => $block, 'pairings' => '' );
        $media = function_exists( 'lunara_get_review_debrief_signature_media_html' )
            ? (string) lunara_get_review_debrief_signature_media_html( $review_id )
            : '';

        $has_content = '' !== trim( wp_strip_all_tags( $block ) );
        $pairings    = '';

        if ( $has_content ) {
            $cards = lunara_debrief_render_legacy_pairing_cards( $review_id );
            if ( '' !== trim( $cards ) ) {
                $pairings = '<div class="lunara-review-single-debrief-pairings-modern">' . $cards . '</div>';
            } elseif ( ! empty( $split['pairings'] ) ) {
                $pairings = '<div class="lunara-review-single-debrief lunara-review-single-debrief--pairings">' . $split['pairings'] . '</div>';
            }
        }

        return array(
            'source'         => $has_content ? 'legacy' : 'none',
            'has_content'    => $has_content,
            'signature_html' => (string) ( $split['signature'] ?? $block ),
            'media_html'     => $media,
            'pairings_html'  => $pairings,
        );
    }
}

if ( ! function_exists( 'lunara_debrief_render_legacy_pairing_cards' ) ) {
    /**
     * Render cards from legacy text fields only.
     *
     * @param int $review_id Review post ID.
     * @return string
     */
    function lunara_debrief_render_legacy_pairing_cards( $review_id ) {
        if ( ! function_exists( 'lunara_parse_pair_it_with_value' ) ) {
            return '';
        }

        $career = trim( (string) get_post_meta( $review_id, '_lunara_career_context', true ) );
        if ( '' === $career ) {
            $career = trim( (string) get_post_meta( $review_id, '_lunara_craft_mirror', true ) );
        }

        $legacy_rows = array(
            array( 'slug' => 'theme', 'label' => __( 'Theme Echo', 'lunara-film' ), 'value' => get_post_meta( $review_id, '_lunara_theme_echo', true ) ),
            array( 'slug' => 'counter', 'label' => __( 'Counter-Program', 'lunara-film' ), 'value' => get_post_meta( $review_id, '_lunara_counter_program', true ) ),
            array( 'slug' => 'career', 'label' => __( 'Career Context', 'lunara-film' ), 'value' => $career ),
        );
        $rows = array();

        foreach ( $legacy_rows as $row ) {
            $value = trim( (string) $row['value'] );
            if ( '' === $value ) {
                continue;
            }

            $data       = lunara_parse_pair_it_with_value( $value, $review_id );
            $title_base = trim( (string) ( $data['title_base'] ?? '' ) );
            if ( '' === $title_base ) {
                $title_base = trim( (string) ( $data['title'] ?? '' ) );
            }
            if ( '' === $title_base ) {
                continue;
            }

            $data['watch_html'] = function_exists( 'lunara_pair_render_where_to_watch' )
                ? (string) lunara_pair_render_where_to_watch( (string) ( $data['tt'] ?? '' ), $review_id )
                : '';
            $rows[] = array(
                'slug'  => $row['slug'],
                'label' => $row['label'],
                'data'  => $data,
            );
        }

        return lunara_debrief_render_pair_card_group( $review_id, $rows );
    }
}

if ( ! function_exists( 'lunara_debrief_render_pair_card_group' ) ) {
    /**
     * Render normalized Pair It With rows using the approved existing geometry.
     *
     * @param int                             $review_id Review post ID.
     * @param array<int,array<string,mixed>> $rows Pairing rows.
     * @return string
     */
    function lunara_debrief_render_pair_card_group( $review_id, $rows ) {
        $cards = array();

        foreach ( $rows as $row ) {
            $data       = isset( $row['data'] ) && is_array( $row['data'] ) ? $row['data'] : array();
            $title_base = trim( (string) ( $data['title_base'] ?? '' ) );
            if ( '' === $title_base ) {
                $title_base = trim( (string) ( $data['title'] ?? '' ) );
            }
            if ( '' === $title_base ) {
                continue;
            }

            $tt          = (string) ( $data['tt'] ?? '' );
            $year        = (string) ( $data['year'] ?? '' );
            $note        = (string) ( $data['note'] ?? '' );
            $poster_html = trim( (string) ( $data['poster_html'] ?? '' ) );

            if ( '' !== $poster_html ) {
                $media = '<div class="lunara-pair-card-poster">' . $poster_html . '</div>';
            } else {
                $media = '<div class="lunara-pair-card-poster lunara-pair-card-poster--plate"><div class="lunara-pair-card-plate">'
                    . '<span class="lunara-pair-card-mark" aria-hidden="true"></span>'
                    . '<span class="lunara-pair-card-plate-title">' . esc_html( $title_base ) . '</span>'
                    . '<span class="lunara-pair-card-plate-rule"></span></div></div>';
            }

            $title_inner = '<span class="lunara-pair-card-title-text">' . esc_html( $title_base ) . '</span>';
            if ( '' !== $year ) {
                $title_inner .= ' <span class="lunara-pair-card-year">(' . esc_html( $year ) . ')</span>';
            }

            $title_href = (string) ( $data['title_href'] ?? '' );
            $href_type  = (string) ( $data['title_href_type'] ?? 'none' );
            if ( '' !== $title_href && in_array( $href_type, array( 'review', 'oscar', 'entity' ), true ) ) {
                $title_html = '<a class="lunara-pair-card-title-link" href="' . esc_url( $title_href ) . '">' . $title_inner . '</a>';
            } elseif ( '' !== $title_href ) {
                $title_html = '<a class="lunara-pair-card-title-link" href="' . esc_url( $title_href ) . '" target="_blank" rel="noopener noreferrer nofollow">' . $title_inner . '</a>';
            } else {
                $title_html = '<span class="lunara-pair-card-title-link">' . $title_inner . '</span>';
            }

            $chips     = '';
            $imdb_href = (string) ( $data['imdb_href'] ?? '' );
            if ( '' !== $imdb_href ) {
                $chips .= '<a class="lunara-pair-card-chip lunara-pair-card-chip--imdb" href="' . esc_url( $imdb_href ) . '" target="_blank" rel="noopener noreferrer nofollow">IMDb</a>';
            }
            $show_oscar_ledger = ! array_key_exists( 'show_oscar_ledger', $data )
                || ! empty( $data['show_oscar_ledger'] );
            if ( $show_oscar_ledger && function_exists( 'lunara_render_oscar_ledger_pill' ) ) {
                $counts = is_array( $data['counts'] ?? null ) ? $data['counts'] : array( 'noms' => 0, 'wins' => 0 );
                $chips .= lunara_render_oscar_ledger_pill( $tt, $counts );
            }

            $card  = '<article class="lunara-pair-card lunara-pair-card--' . esc_attr( (string) $row['slug'] ) . '"';
            $card .= '' !== $tt ? ' data-pair-tt="' . esc_attr( $tt ) . '"' : '';
            $card .= '>' . $media . '<div class="lunara-pair-card-body">';
            $card .= '<p class="lunara-pair-card-role">' . esc_html( (string) $row['label'] ) . '</p>';
            $card .= '<h4 class="lunara-pair-card-title">' . $title_html . '</h4>';
            if ( '' !== trim( $note ) ) {
                $card .= '<p class="lunara-pair-card-note">' . esc_html( $note ) . '</p>';
            }
            if ( '' !== trim( $chips ) ) {
                $card .= '<div class="lunara-pair-card-chips">' . $chips . '</div>';
            }
            if ( '' !== trim( (string) ( $data['watch_html'] ?? '' ) ) ) {
                $card .= '<div class="lunara-pair-card-watch">' . (string) $data['watch_html'] . '</div>';
            }
            $card .= '</div></article>';
            $cards[] = $card;
        }

        if ( empty( $cards ) ) {
            return '';
        }

        $subtitle = (string) apply_filters(
            'lunara_pair_cards_subtitle',
            __( 'Three films in conversation with this one.', 'lunara-film' ),
            $review_id
        );
        $html  = '<section class="lunara-pair-cards" aria-label="' . esc_attr__( 'Pair It With', 'lunara-film' ) . '">';
        $html .= '<div class="lunara-pair-cards-head"><h3 class="lunara-pair-cards-title">' . esc_html__( 'Pair It With', 'lunara-film' ) . '</h3>';
        if ( '' !== trim( $subtitle ) ) {
            $html .= '<p class="lunara-pair-cards-sub">' . esc_html( $subtitle ) . '</p>';
        }
        $html .= '</div><div class="lunara-pair-cards-grid" data-count="' . count( $cards ) . '">' . implode( '', $cards ) . '</div></section>';

        return $html;
    }
}
