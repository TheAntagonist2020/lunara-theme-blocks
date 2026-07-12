<?php
/**
 * Local-only Debrief film resolver.
 *
 * Resolves Core-owned movie entities into a stable data snapshot. This module
 * is hook-free and does not replace the existing public Debrief renderer in
 * Release A. It never performs remote HTTP or returns presentation markup.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Lunara_Debrief_Film_Resolver {

    /**
     * Per-request resolver cache.
     *
     * @var array<string,array<string,mixed>>
     */
    private static $cache = array();

    /**
     * Resolve one canonical movie entity using local WordPress data only.
     *
     * @param int                 $movie_id Movie post ID.
     * @param array<string,mixed> $args Resolver options.
     * @return array<string,mixed>
     */
    public static function resolve( $movie_id, $args = array() ) {
        $movie_id = absint( $movie_id );
        $args     = array_merge(
            array(
                'require_published'      => true,
                'resolve_poster'         => true,
                'allow_aat_local_poster' => true,
                'resolve_awards'         => false,
            ),
            is_array( $args ) ? $args : array()
        );

        $args['require_published']      = (bool) $args['require_published'];
        $args['resolve_poster']         = (bool) $args['resolve_poster'];
        $args['allow_aat_local_poster'] = (bool) $args['allow_aat_local_poster'];
        $args['resolve_awards']         = (bool) $args['resolve_awards'];

        $cache_key = md5( serialize( array( $movie_id, $args ) ) );
        if ( isset( self::$cache[ $cache_key ] ) ) {
            return self::$cache[ $cache_key ];
        }

        $snapshot = self::empty_snapshot( $movie_id );

        if ( ! $movie_id || 'movie' !== get_post_type( $movie_id ) ) {
            $snapshot['warnings'][] = 'invalid_movie';
            return self::remember( $cache_key, $snapshot, $args );
        }

        $post_status = (string) get_post_status( $movie_id );
        if ( $args['require_published'] && 'publish' !== $post_status ) {
            $snapshot['post_status'] = $post_status;
            $snapshot['warnings'][]  = 'movie_not_published';
            return self::remember( $cache_key, $snapshot, $args );
        }

        $title     = trim( (string) get_the_title( $movie_id ) );
        $year      = self::normalize_year( get_post_meta( $movie_id, 'release_year', true ) );
        $imdb_id   = self::normalize_imdb_title_id( get_post_meta( $movie_id, 'imdb_title_id', true ) );
        $permalink = trim( (string) get_permalink( $movie_id ) );

        if ( '' === $imdb_id ) {
            $imdb_id = self::normalize_imdb_title_id( get_post_meta( $movie_id, '_lunara_entity_id', true ) );
        }

        $snapshot['post_status']    = $post_status;
        $snapshot['title']          = $title;
        $snapshot['year']           = $year;
        $snapshot['imdb_title_id']  = $imdb_id;
        $snapshot['permalink']      = $permalink;
        $snapshot['imdb_url']       = '' !== $imdb_id ? 'https://www.imdb.com/title/' . $imdb_id . '/' : '';
        $snapshot['directors']      = self::normalize_id_list( get_post_meta( $movie_id, 'directors', true ) );
        $snapshot['principal_cast'] = self::normalize_id_list( get_post_meta( $movie_id, 'principal_cast', true ) );

        if ( $args['resolve_poster'] ) {
            $snapshot['poster_checked'] = true;
            $poster_id                  = absint( get_post_thumbnail_id( $movie_id ) );

            if ( $poster_id ) {
                $snapshot['poster_attachment_id'] = $poster_id;
                $snapshot['poster_source']        = 'movie_featured';
            } elseif ( $args['allow_aat_local_poster'] && '' !== $imdb_id && class_exists( 'Academy_Awards_Table' ) ) {
                $aat = Academy_Awards_Table::get_instance();
                if ( $aat && method_exists( $aat, 'get_poster_attachment_id_for_title' ) ) {
                    $poster_id = absint( $aat->get_poster_attachment_id_for_title( $imdb_id ) );
                    if ( $poster_id ) {
                        $snapshot['poster_attachment_id'] = $poster_id;
                        $snapshot['poster_source']        = 'aat_local';
                    }
                }
            }
        }

        if ( $args['resolve_awards'] && '' !== $imdb_id && function_exists( 'lunara_get_oscar_ledger_counts' ) ) {
            $counts = lunara_get_oscar_ledger_counts( $imdb_id );
            if ( is_array( $counts ) ) {
                $snapshot['oscar_counts'] = array(
                    'noms' => absint( $counts['noms'] ?? 0 ),
                    'wins' => absint( $counts['wins'] ?? 0 ),
                );
                $snapshot['awards_resolved'] = true;
            }
        }

        if ( '' === $title ) {
            $snapshot['warnings'][] = 'missing_title';
        }
        if ( '' === $year ) {
            $snapshot['warnings'][] = 'missing_year';
        }
        if ( '' === $imdb_id ) {
            $snapshot['warnings'][] = 'missing_imdb_title_id';
        }
        if ( '' === $permalink ) {
            $snapshot['warnings'][] = 'missing_permalink';
        }
        if ( $snapshot['poster_checked'] && ! $snapshot['poster_attachment_id'] ) {
            $snapshot['warnings'][] = 'missing_poster';
        }

        $snapshot['valid'] = '' !== $title && '' !== $imdb_id && '' !== $permalink;

        return self::remember( $cache_key, $snapshot, $args );
    }

    /**
     * Resolve the local movie entity owned by a Review.
     *
     * @param int                 $review_id Review post ID.
     * @param array<string,mixed> $args Movie resolver options.
     * @return array<string,mixed>
     */
    public static function resolve_reviewed_movie( $review_id, $args = array() ) {
        $review_id = absint( $review_id );
        if ( ! $review_id ) {
            return self::empty_snapshot();
        }

        $movie_id = 0;
        if ( function_exists( 'lunara_entity_movie_for_review' ) ) {
            $movie_id = absint( lunara_entity_movie_for_review( $review_id ) );
        }

        if ( ! $movie_id && function_exists( 'lunara_debrief_get_review_record' ) ) {
            $record   = lunara_debrief_get_review_record( $review_id );
            $movie_id = absint( $record['reviewed_film']['movie_id'] ?? 0 );
        }

        if ( ! $movie_id ) {
            $imdb_id = self::normalize_imdb_title_id( get_post_meta( $review_id, '_lunara_imdb_title_id', true ) );
            if ( '' !== $imdb_id ) {
                $movie_ids = get_posts(
                    array(
                        'post_type'              => 'movie',
                        'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
                        'posts_per_page'         => 1,
                        'fields'                 => 'ids',
                        'no_found_rows'          => true,
                        'update_post_meta_cache' => false,
                        'update_post_term_cache' => false,
                        'meta_query'             => array(
                            'relation' => 'OR',
                            array(
                                'key'   => 'imdb_title_id',
                                'value' => $imdb_id,
                            ),
                            array(
                                'key'   => '_lunara_entity_id',
                                'value' => $imdb_id,
                            ),
                        ),
                    )
                );
                if ( ! empty( $movie_ids ) ) {
                    $movie    = reset( $movie_ids );
                    $movie_id = is_object( $movie ) && isset( $movie->ID ) ? absint( $movie->ID ) : absint( $movie );
                }
            }
        }

        if ( ! $movie_id ) {
            $snapshot                = self::empty_snapshot();
            $snapshot['warnings'][] = 'review_movie_not_found';
            return $snapshot;
        }

        $args['require_published'] = $args['require_published'] ?? false;
        return self::resolve( $movie_id, $args );
    }

    /**
     * Empty stable resolver result.
     *
     * @param int $movie_id Optional attempted movie ID.
     * @return array<string,mixed>
     */
    private static function empty_snapshot( $movie_id = 0 ) {
        return array(
            'valid'                => false,
            'movie_id'             => absint( $movie_id ),
            'post_status'          => '',
            'title'                => '',
            'year'                 => '',
            'imdb_title_id'        => '',
            'permalink'            => '',
            'imdb_url'             => '',
            'poster_attachment_id' => 0,
            'poster_source'        => 'none',
            'poster_checked'       => false,
            'oscar_counts'         => array( 'noms' => 0, 'wins' => 0 ),
            'awards_resolved'      => false,
            'directors'            => array(),
            'principal_cast'       => array(),
            'warnings'             => array(),
        );
    }

    /**
     * Normalize a local ACF relationship value into unique positive IDs.
     *
     * @param mixed $value Raw relationship value.
     * @return array<int,int>
     */
    private static function normalize_id_list( $value ) {
        if ( function_exists( 'maybe_unserialize' ) ) {
            $value = maybe_unserialize( $value );
        }

        $ids = array_filter( array_map( 'absint', is_array( $value ) ? $value : (array) $value ) );
        return array_values( array_unique( $ids ) );
    }

    /**
     * Normalize an IMDb ID through Core when available.
     *
     * @param mixed $value Raw IMDb value.
     * @return string
     */
    private static function normalize_imdb_title_id( $value ) {
        if ( class_exists( 'Lunara_Debrief_Contract' ) ) {
            return Lunara_Debrief_Contract::normalize_imdb_title_id( $value );
        }

        $value = strtolower( trim( (string) $value ) );
        return preg_match( '/\b(tt\d{6,9})\b/', $value, $matches ) ? $matches[1] : '';
    }

    /**
     * Normalize a release year.
     *
     * @param mixed $year Raw year.
     * @return string
     */
    private static function normalize_year( $year ) {
        return preg_match( '/\b(18|19|20|21)\d{2}\b/', (string) $year, $matches ) ? $matches[0] : '';
    }

    /**
     * Apply the data-only extension filter and cache the final snapshot.
     *
     * @param string              $cache_key Cache key.
     * @param array<string,mixed> $snapshot Resolver result.
     * @param array<string,mixed> $args Resolver options.
     * @return array<string,mixed>
     */
    private static function remember( $cache_key, $snapshot, $args ) {
        if ( function_exists( 'apply_filters' ) ) {
            $snapshot = apply_filters( 'lunara_debrief_resolved_movie', $snapshot, $snapshot['movie_id'], $args );
        }

        self::$cache[ $cache_key ] = $snapshot;
        return $snapshot;
    }
}

if ( ! function_exists( 'lunara_debrief_resolve_movie' ) ) {
    function lunara_debrief_resolve_movie( $movie_id, $args = array() ) {
        return Lunara_Debrief_Film_Resolver::resolve( $movie_id, $args );
    }
}

if ( ! function_exists( 'lunara_debrief_resolve_reviewed_movie' ) ) {
    function lunara_debrief_resolve_reviewed_movie( $review_id, $args = array() ) {
        return Lunara_Debrief_Film_Resolver::resolve_reviewed_movie( $review_id, $args );
    }
}
