<?php
/**
 * Homepage and archive review query functions.
 *
 * Extracted from functions.php for maintainability.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Curation v1 — order a homepage feed featured-first, then fill with newest.
 *
 * Returns a list of post IDs for the given post type(s) where any posts pinned
 * with `_lunara_home_featured` = 1 come first (ordered by
 * `_lunara_home_feature_order` ASC, then publish date DESC), followed by the
 * newest non-featured posts to fill out the same total count.
 *
 * If no post is featured, the result is byte-for-byte the same list a pure
 * newest-first query would return — so feeds stay dynamic-by-default and the
 * homepage renders exactly as before when nothing is pinned.
 *
 * @param array $args {
 *     @type string|array $post_type Post type slug or list. Default 'review'.
 *     @type int          $count     Total IDs to return. Default 9.
 *     @type array        $exclude   Post IDs to exclude (e.g. a curated lead).
 * }
 * @return int[] Ordered list of post IDs.
 */
if ( ! function_exists( 'lunara_order_featured_first' ) ) {
    function lunara_order_featured_first( $args = array() ) {
        $args = wp_parse_args(
            is_array( $args ) ? $args : array(),
            array(
                'post_type' => 'review',
                'count'     => 9,
                'exclude'   => array(),
            )
        );

        $post_type = $args['post_type'];
        $count     = max( 0, (int) $args['count'] );
        $exclude   = array_values( array_filter( array_map( 'intval', (array) $args['exclude'] ) ) );

        if ( $count < 1 ) {
            return array();
        }

        $base_query = array(
            'post_type'              => $post_type,
            'post_status'            => 'publish',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'fields'                 => 'ids',
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );

        if ( ! empty( $exclude ) ) {
            $base_query['post__not_in'] = $exclude;
        }

        // 1. Featured pins. Filter only on the flag so a pin is never dropped
        //    for lacking an order value, then sort in PHP by feature order
        //    (ASC, missing => 0) and break ties by newest publish date (DESC).
        //    Fetch the full pinned set (capped) so the ordering is stable
        //    regardless of how WordPress would have joined the order meta.
        $featured_rows = get_posts(
            array_merge(
                $base_query,
                array(
                    'posts_per_page' => max( $count, 50 ),
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'meta_query'     => array(
                        array(
                            'key'   => '_lunara_home_featured',
                            'value' => '1',
                        ),
                    ),
                )
            )
        );
        $featured_rows = array_values( array_map( 'intval', is_array( $featured_rows ) ? $featured_rows : array() ) );

        if ( ! empty( $featured_rows ) ) {
            // Stable sort: feature order ASC, then preserve the date-DESC input order on ties.
            $ordered = array();
            foreach ( $featured_rows as $index => $fid ) {
                $ordered[] = array(
                    'id'    => $fid,
                    'order' => (int) get_post_meta( $fid, '_lunara_home_feature_order', true ),
                    'seq'   => $index,
                );
            }
            usort(
                $ordered,
                static function ( $a, $b ) {
                    if ( $a['order'] === $b['order'] ) {
                        return $a['seq'] <=> $b['seq'];
                    }
                    return $a['order'] <=> $b['order'];
                }
            );
            $featured_ids = array_values( wp_list_pluck( $ordered, 'id' ) );
        } else {
            $featured_ids = array();
        }

        // Fast path: nothing pinned → identical to a pure newest-first query.
        if ( empty( $featured_ids ) ) {
            $newest = get_posts(
                array_merge(
                    $base_query,
                    array(
                        'posts_per_page' => $count,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    )
                )
            );

            return array_values( array_map( 'intval', is_array( $newest ) ? $newest : array() ) );
        }

        $featured_ids = array_slice( $featured_ids, 0, $count );
        $fill_count   = $count - count( $featured_ids );
        $post_ids     = $featured_ids;

        // 2. Fill the remainder with the newest posts that are not already pinned.
        if ( $fill_count > 0 ) {
            $fill_exclude = array_values( array_unique( array_merge( $exclude, $featured_ids ) ) );
            $fill_ids     = get_posts(
                array_merge(
                    $base_query,
                    array(
                        'posts_per_page' => $fill_count,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                        'post__not_in'   => $fill_exclude,
                    )
                )
            );

            $post_ids = array_merge(
                $post_ids,
                array_values( array_map( 'intval', is_array( $fill_ids ) ? $fill_ids : array() ) )
            );
        }

        return array_slice( array_values( array_unique( $post_ids ) ), 0, $count );
    }
}

/**
 * Homepage featured reviews query with manual curation override.
 */
function lunara_home_featured_reviews_query( $count = 8 ) {
    $manual_ids = lunara_parse_manual_post_ids(
        lunara_theme_mod_text( 'lunara_home_featured_review_ids', '' ),
        'review'
    );

    if ( ! empty( $manual_ids ) ) {
        return lunara_reviews_query_from_ids( array_slice( $manual_ids, 0, max( 1, intval( $count ) ) ) );
    }

    return lunara_featured_reviews_query( $count );
}

/**
 * Reviews pinned to the homepage "latest/current release" lane.
 *
 * This lets a festival review re-enter homepage circulation when the film
 * opens theatrically or lands on home video, without changing the review date.
 */
function lunara_home_latest_review_ids() {
    return lunara_parse_manual_post_ids(
        lunara_theme_mod_text( 'lunara_home_latest_review_ids', '' ),
        'review'
    );
}

/**
 * Homepage latest reviews query with a current-release curation override.
 */
function lunara_home_latest_reviews_query( $count = 9 ) {
    $count      = max( 1, intval( $count ) );
    $manual_ids = array_slice( lunara_home_latest_review_ids(), 0, $count );

    if ( empty( $manual_ids ) ) {
        return lunara_latest_reviews_query( $count );
    }

    $fill_count = max( 0, $count - count( $manual_ids ) );
    $post_ids   = $manual_ids;

    if ( $fill_count > 0 ) {
        $fill_ids = get_posts(
            array(
                'post_type'              => 'review',
                'post_status'            => 'publish',
                'posts_per_page'         => $fill_count,
                'post__not_in'           => $manual_ids,
                'ignore_sticky_posts'    => true,
                'no_found_rows'          => true,
                'fields'                 => 'ids',
                'orderby'                => 'date',
                'order'                  => 'DESC',
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            )
        );

        $post_ids = array_merge( $post_ids, array_map( 'intval', is_array( $fill_ids ) ? $fill_ids : array() ) );
    }

    return lunara_reviews_query_from_ids( array_slice( array_values( array_unique( $post_ids ) ), 0, $count ) );
}

/**
 * Resolve the manually selected Journal lead.
 *
 * This is intentionally a single published Journal entry. The homepage and
 * Journal archive can share one editorial lead without turning the whole lane
 * into a hand-managed list.
 */
function lunara_get_curated_journal_lead_id() {
    $lead_ids = lunara_parse_manual_post_ids(
        lunara_theme_mod_text( 'lunara_home_journal_lead_post_id', '' ),
        'journal'
    );

    return ! empty( $lead_ids[0] ) ? absint( $lead_ids[0] ) : 0;
}

/**
 * Homepage dispatches query.
 *
 * Priority order:
 *   1. Manual curation — customizer post IDs (accepts both `post` and `dispatch`).
 *   2. `dispatch` CPT — the new dedicated post type.
 *   3. Legacy fallback — standard `post` filtered by the category slugs
 *      defined in the customizer (news, reactions, think-pieces, podcast).
 *      This keeps existing category-tagged posts visible during migration.
 *
 * @param  int        $count  Number of items to return.
 * @return WP_Query
 */
function lunara_home_dispatches_query( $count = 4 ) {
    $count   = max( 1, intval( $count ) );
    $lead_id = lunara_get_curated_journal_lead_id();

    // --- 1. Manual curation ------------------------------------------------
    // Pass empty string for $allowed_post_type so both `post` and `dispatch`
    // IDs are accepted without a strict type filter.
    $manual_ids = lunara_parse_manual_post_ids(
        lunara_theme_mod_text( 'lunara_home_dispatch_post_ids', '' ),
        ''
    );

    if ( $lead_id && ! in_array( $lead_id, $manual_ids, true ) ) {
        array_unshift( $manual_ids, $lead_id );
    }

    if ( ! empty( $manual_ids ) ) {
        return lunara_posts_query_from_ids( array_slice( $manual_ids, 0, $count ) );
    }

    // --- 2. journal CPT query ----------------------------------------------
    // Curation v1: featured pins (`_lunara_home_featured`) float to the top,
    // then the lane fills with the newest journal entries. A manually curated
    // lead still wins the first slot; remaining slots are featured-first.
    $journal_exclude = $lead_id ? array( $lead_id ) : array();
    if ( $lead_id ) {
        if ( function_exists( 'lunara_order_featured_first' ) ) {
            $fill_ids = lunara_order_featured_first(
                array(
                    'post_type' => 'journal',
                    'count'     => max( 0, $count - 1 ),
                    'exclude'   => $journal_exclude,
                )
            );
        } else {
            $fill_ids = get_posts(
                array(
                    'post_type'              => 'journal',
                    'posts_per_page'         => max( 0, $count - 1 ),
                    'post_status'            => 'publish',
                    'post__not_in'           => $journal_exclude,
                    'ignore_sticky_posts'    => true,
                    'no_found_rows'          => true,
                    'fields'                 => 'ids',
                    'update_post_meta_cache' => true,
                    'update_post_term_cache' => true,
                    'orderby'                => 'date',
                    'order'                  => 'DESC',
                )
            );
        }

        return lunara_posts_query_from_ids(
            array_slice(
                array_values( array_unique( array_merge( array( $lead_id ), array_map( 'absint', (array) $fill_ids ) ) ) ),
                0,
                $count
            )
        );
    }

    if ( function_exists( 'lunara_order_featured_first' ) ) {
        $journal_ids = lunara_order_featured_first(
            array(
                'post_type' => 'journal',
                'count'     => $count,
            )
        );

        if ( ! empty( $journal_ids ) ) {
            return lunara_posts_query_from_ids( $journal_ids );
        }
    }

    $journal_query = new WP_Query( array(
        'post_type'              => 'journal',
        'posts_per_page'         => $count,
        'post_status'            => 'publish',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
        'orderby'                => 'date',
        'order'                  => 'DESC',
    ) );

    if ( $journal_query->have_posts() ) {
        return $journal_query;
    }

    // --- 3. Legacy fallback — standard posts filtered by category slugs ----
    $slugs      = lunara_get_dispatch_category_slugs();
    $query_args = array(
        'post_type'              => 'post',
        'posts_per_page'         => $count,
        'post_status'            => 'publish',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
    );

    if ( ! empty( $slugs ) ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => $slugs,
                'operator' => 'IN',
            ),
        );
    }

    $legacy_query = new WP_Query( $query_args );
    if ( $legacy_query->have_posts() ) {
        return $legacy_query;
    }

    // Empty result set.
    return new WP_Query( array(
        'post_type'      => 'journal',
        'post__in'       => array( 0 ),
        'posts_per_page' => 0,
        'no_found_rows'  => true,
    ) );
}

function lunara_invalidate_review_query_caches( $post_id = 0 ) {
    if ( $post_id && get_post_type( $post_id ) !== 'review' ) {
        return;
    }

    delete_transient( 'lunara_home_oscars_snapshot_v1' );
    delete_transient( 'lunara_home_oscars_snapshot_v2' );
    delete_transient( 'lunara_home_database_spotlight_v1' );
    delete_transient( 'lunara_home_ledger_story_cards_v1' );
    delete_transient( 'lunara_home_ledger_story_cards_v2' );
    delete_transient( 'lunara_home_oscar_spotlight_v1' );
    delete_transient( 'lunara_home_deep_cuts_v1' );

    $counts = array( 6, 8, 9 );
    $groups = array( 'featured_reviews', 'ledger_highlights', 'latest_reviews' );

    foreach ( $groups as $group ) {
        foreach ( $counts as $count ) {
            delete_transient( sprintf( 'lunara_%s_%d_v1', $group, $count ) );
        }
    }

    // Curation v1 — featured-first latest-reviews cache (multiple counts in use
    // across the latest-reviews lane and the cinematic hero pool).
    foreach ( array( 6, 8, 9, 12, 14, 15 ) as $count ) {
        delete_transient( sprintf( 'lunara_latest_reviews_featured_%d_v1', $count ) );
    }
}
add_action( 'save_post_review', 'lunara_invalidate_review_query_caches', 50 );
add_action( 'deleted_post', 'lunara_invalidate_review_query_caches' );

/**
 * Flush all theme caches that derive from the Academy Awards plugin data.
 * Fired by the plugin's aat_after_data_import hook after any import or clear.
 *
 * @param string $type   Import type: 'full', 'delta', 'bundled', 'clear'.
 * @param int    $count  Number of rows imported (0 on clear).
 */
function lunara_invalidate_oscars_data_caches( $type = '', $count = 0 ) {
    // Oscar snapshot & homepage section caches.
    delete_transient( 'lunara_home_oscars_snapshot_v1' );
    delete_transient( 'lunara_home_oscars_snapshot_v2' );
    delete_transient( 'lunara_home_database_spotlight_v1' );
    delete_transient( 'lunara_home_ledger_story_cards_v1' );
    delete_transient( 'lunara_home_ledger_story_cards_v2' );
    delete_transient( 'lunara_home_oscar_spotlight_v1' );
    delete_transient( 'lunara_home_deep_cuts_v1' );
    delete_transient( 'lunara_home_lore_cards_v2' );

    // Table name cache (in case table was recreated).
    delete_transient( 'lunara_awards_table_name_v1' );

    // Review query caches (ledger pills reference Oscar data).
    $counts = array( 6, 8, 9 );
    $groups = array( 'featured_reviews', 'ledger_highlights', 'latest_reviews' );
    foreach ( $groups as $group ) {
        foreach ( $counts as $c ) {
            delete_transient( sprintf( 'lunara_%s_%d_v1', $group, $c ) );
        }
    }
}
add_action( 'aat_after_data_import', 'lunara_invalidate_oscars_data_caches', 10, 2 );

/**
 * Featured review query.
 */
function lunara_featured_reviews_query( $count = 8 ) {
    $post_ids = lunara_cached_review_ids(
        'featured_reviews',
        $count,
        array(
            'tag' => 'featured',
        )
    );

    return lunara_reviews_query_from_ids( $post_ids );
}

/**
 * Ledger highlights query.
 */
function lunara_ledger_highlights_query( $count = 6 ) {
    $post_ids = lunara_cached_review_ids(
        'ledger_highlights',
        $count,
        array(
            'tag' => 'oscar-ledger',
        )
    );

    return lunara_reviews_query_from_ids( $post_ids );
}

/**
 * Latest review query.
 *
 * Curation v1: featured pins (`_lunara_home_featured`) float to the top, then
 * the lane fills with the newest reviews. With nothing pinned this returns the
 * same newest-first set as before. Cached under a versioned key that is busted
 * by lunara_invalidate_review_query_caches() on review save/delete.
 */
function lunara_latest_reviews_query( $count = 9 ) {
    $count = max( 1, (int) $count );

    if ( function_exists( 'lunara_order_featured_first' ) ) {
        $cache_key = sprintf( 'lunara_latest_reviews_featured_%d_v1', $count );
        $post_ids  = get_transient( $cache_key );

        if ( ! is_array( $post_ids ) ) {
            $post_ids = lunara_order_featured_first(
                array(
                    'post_type' => 'review',
                    'count'     => $count,
                )
            );
            set_transient( $cache_key, $post_ids, 15 * MINUTE_IN_SECONDS );
        }
    } else {
        $post_ids = lunara_cached_review_ids( 'latest_reviews', $count, array() );
    }

    return lunara_reviews_query_from_ids( $post_ids );
}

/**
 * Latest reviews that are explicitly connected to an Oscars title page.
 */
function lunara_oscars_linked_reviews_query( $count = 4 ) {
    $count = max( 1, intval( $count ) );

    // First priority: published reviews whose IMDb ID appears in the Academy Awards table.
    global $wpdb;
    $awards_table = $wpdb->prefix . 'academy_awards';
    $oscar_ids    = array();

    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$awards_table}'" ) === $awards_table ) {
        // Get IMDb IDs of reviewed Oscar-nominated films (rotate daily via OFFSET).
        $day_offset = intval( date( 'z' ) ) % 20; // rotate through the pool
        $oscar_imdb = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT pm.post_id
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_type = 'review' AND p.post_status = 'publish'
             INNER JOIN {$awards_table} aa ON aa.film_id = pm.meta_value AND aa.film_id != ''
             WHERE pm.meta_key = '_lunara_imdb_title_id' AND pm.meta_value != ''
             GROUP BY pm.post_id
             ORDER BY p.post_date DESC
             LIMIT %d OFFSET %d",
            $count * 3, // fetch extra so rotation works
            $day_offset
        ) );

        if ( ! empty( $oscar_imdb ) ) {
            $oscar_ids = array_map( 'intval', array_slice( $oscar_imdb, 0, $count ) );
        }
    }

    if ( ! empty( $oscar_ids ) ) {
        $query = new WP_Query(
            array(
                'post_type'              => 'review',
                'post_status'            => 'publish',
                'post__in'               => $oscar_ids,
                'posts_per_page'         => $count,
                'orderby'                => 'post__in',
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => true,
            )
        );
    } else {
        // Fallback: any published review with an IMDb ID.
        $query = new WP_Query(
            array(
                'post_type'              => 'review',
                'post_status'            => 'publish',
                'posts_per_page'         => $count,
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => true,
                'meta_query'             => array(
                    'relation' => 'AND',
                    array(
                        'key'     => '_lunara_imdb_title_id',
                        'compare' => 'EXISTS',
                    ),
                    array(
                        'key'     => '_lunara_imdb_title_id',
                        'value'   => '',
                        'compare' => '!=',
                    ),
                ),
            )
        );
    }

    if ( function_exists( 'lunara_prime_review_card_caches' ) && ! empty( $query->posts ) ) {
        lunara_prime_review_card_caches( wp_list_pluck( $query->posts, 'ID' ) );
    }

    return $query;
}

/* =========================================================================
   CURATION v1 — SPOTLIGHT CAMPAIGN
   Global, optional override: promote one published post to the lead slide of
   the cinematic hero and expose a label for a future visual strip. Off by
   default; with the toggle off the homepage renders exactly as before.
   ========================================================================= */

/**
 * Is the Spotlight Campaign turned on?
 *
 * @return bool
 */
if ( ! function_exists( 'lunara_is_spotlight_enabled' ) ) {
    function lunara_is_spotlight_enabled() {
        $enabled = (bool) get_theme_mod( 'lunara_spotlight_enabled', false );

        /**
         * Filter whether the Spotlight Campaign is active.
         *
         * @param bool $enabled
         */
        return (bool) apply_filters( 'lunara_spotlight_enabled', $enabled );
    }
}

/**
 * Resolve the Spotlight Campaign post ID, but only when the campaign is enabled
 * and the configured post is a real, published post. Returns 0 otherwise.
 *
 * @return int
 */
if ( ! function_exists( 'lunara_get_spotlight_post_id' ) ) {
    function lunara_get_spotlight_post_id() {
        if ( ! lunara_is_spotlight_enabled() ) {
            return 0;
        }

        $post_id = absint( get_theme_mod( 'lunara_spotlight_post_id', 0 ) );
        if ( $post_id < 1 ) {
            return 0;
        }

        $post = get_post( $post_id );
        if ( ! ( $post instanceof WP_Post ) || 'publish' !== $post->post_status ) {
            return 0;
        }

        /**
         * Filter the resolved Spotlight Campaign post ID.
         *
         * @param int $post_id Validated, published post ID (or 0).
         */
        return (int) apply_filters( 'lunara_spotlight_post_id', $post_id );
    }
}

/**
 * Get the Spotlight Campaign label.
 *
 * Filterable so a future visual strip (or any other surface) can read it
 * without re-reading the theme mod. Returns '' when nothing is set.
 *
 * @return string
 */
if ( ! function_exists( 'lunara_get_spotlight_label' ) ) {
    function lunara_get_spotlight_label() {
        $label = trim( (string) get_theme_mod( 'lunara_spotlight_label', '' ) );

        /**
         * Filter the Spotlight Campaign label.
         *
         * @param string $label   The configured label (may be empty).
         * @param int    $post_id The resolved spotlight post ID (0 if inactive).
         */
        return (string) apply_filters( 'lunara_get_spotlight_label', $label, lunara_get_spotlight_post_id() );
    }
}

/**
 * Add a body class on the front page when the Spotlight Campaign is active with
 * a valid published post, so a future visual strip can hook styling off it.
 *
 * @param  array $classes
 * @return array
 */
if ( ! function_exists( 'lunara_spotlight_body_class' ) ) {
    function lunara_spotlight_body_class( $classes ) {
        if ( ( is_front_page() || is_home() ) && lunara_get_spotlight_post_id() ) {
            $classes[] = 'has-lunara-spotlight';
        }

        return $classes;
    }
    add_filter( 'body_class', 'lunara_spotlight_body_class' );
}
