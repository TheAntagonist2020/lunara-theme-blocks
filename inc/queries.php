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
    $journal_exclude = $lead_id ? array( $lead_id ) : array();
    if ( $lead_id ) {
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

        return lunara_posts_query_from_ids(
            array_slice(
                array_values( array_unique( array_merge( array( $lead_id ), array_map( 'absint', (array) $fill_ids ) ) ) ),
                0,
                $count
            )
        );
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
 */
function lunara_latest_reviews_query( $count = 9 ) {
    $post_ids = lunara_cached_review_ids( 'latest_reviews', $count, array() );

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
