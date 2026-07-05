<?php
/**
 * Entity Surfaces — Phase 2B of the Lunara knowledge graph.
 *
 * The public faces of the movie / person entities built by the Entity
 * Graph Builder: data helpers, card renderers, archive query shaping, and
 * the Movie / Person JSON-LD emitters. The templates themselves live at
 * single-movie.php, single-person.php, archive-movie.php, archive-person.php.
 *
 * Query discipline: one batched ledger query per page, relationship lookups
 * via the standard serialized-ID LIKE pattern, imagery local-only.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ---------------------------------------------------------------------------
 * Data helpers
 * ------------------------------------------------------------------------ */

/**
 * All ledger entries for a movie, oldest ceremony first.
 * One query; rows come back as light arrays, never WP_Post loops per entry.
 */
function lunara_entity_movie_awards( $movie_id ) {
    global $wpdb;
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT p.ID AS entry_id,
                    MAX(CASE WHEN pm.meta_key = 'category' THEN pm.meta_value END)        AS category,
                    MAX(CASE WHEN pm.meta_key = 'ceremony_number' THEN pm.meta_value END) AS ceremony,
                    MAX(CASE WHEN pm.meta_key = 'ceremony_year' THEN pm.meta_value END)   AS year,
                    MAX(CASE WHEN pm.meta_key = 'won' THEN pm.meta_value END)             AS won,
                    MAX(CASE WHEN pm.meta_key = 'person' THEN pm.meta_value END)          AS person_id
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} link ON link.post_id = p.ID AND link.meta_key = 'movie' AND link.meta_value = %s
             LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key IN ('category','ceremony_number','ceremony_year','won','person')
             WHERE p.post_type = 'ledger_entry' AND p.post_status != 'trash'
             GROUP BY p.ID
             ORDER BY CAST(MAX(CASE WHEN pm.meta_key = 'ceremony_number' THEN pm.meta_value END) AS UNSIGNED) ASC,
                      CAST(MAX(CASE WHEN pm.meta_key = 'won' THEN pm.meta_value END) AS UNSIGNED) ASC,
                      MAX(CASE WHEN pm.meta_key = 'category' THEN pm.meta_value END) ASC",
            (string) $movie_id
        ),
        ARRAY_A
    );
    return is_array( $rows ) ? $rows : array();
}

/**
 * All ledger entries for a person, oldest first.
 */
function lunara_entity_person_awards( $person_id ) {
    global $wpdb;
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT p.ID AS entry_id,
                    MAX(CASE WHEN pm.meta_key = 'category' THEN pm.meta_value END)        AS category,
                    MAX(CASE WHEN pm.meta_key = 'ceremony_number' THEN pm.meta_value END) AS ceremony,
                    MAX(CASE WHEN pm.meta_key = 'ceremony_year' THEN pm.meta_value END)   AS year,
                    MAX(CASE WHEN pm.meta_key = 'won' THEN pm.meta_value END)             AS won,
                    MAX(CASE WHEN pm.meta_key = 'movie' THEN pm.meta_value END)           AS movie_id
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} link ON link.post_id = p.ID AND link.meta_key = 'person' AND link.meta_value = %s
             LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key IN ('category','ceremony_number','ceremony_year','won','movie')
             WHERE p.post_type = 'ledger_entry' AND p.post_status != 'trash'
             GROUP BY p.ID
             ORDER BY CAST(MAX(CASE WHEN pm.meta_key = 'ceremony_number' THEN pm.meta_value END) AS UNSIGNED) ASC,
                      CAST(MAX(CASE WHEN pm.meta_key = 'won' THEN pm.meta_value END) AS UNSIGNED) ASC,
                      MAX(CASE WHEN pm.meta_key = 'category' THEN pm.meta_value END) ASC",
            (string) $person_id
        ),
        ARRAY_A
    );
    return is_array( $rows ) ? $rows : array();
}

/**
 * Wins / nominations tally out of a ledger row set.
 */
function lunara_entity_award_tally( $rows ) {
    $wins = 0;
    foreach ( (array) $rows as $row ) {
        if ( ! empty( $row['won'] ) ) {
            $wins++;
        }
    }
    return array(
        'wins'        => $wins,
        'nominations' => count( (array) $rows ),
    );
}

/**
 * The Lunara review covering this movie, matched through the IMDb bridge id.
 */
function lunara_entity_review_for_movie( $movie_id ) {
    $tt = strtolower( trim( (string) get_post_meta( $movie_id, 'imdb_title_id', true ) ) );
    if ( '' === $tt ) {
        $tt = strtolower( trim( (string) get_post_meta( $movie_id, '_lunara_entity_id', true ) ) );
    }
    if ( ! preg_match( '/^tt\d{6,9}$/', $tt ) ) {
        return null;
    }
    global $wpdb;
    $review_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_lunara_imdb_title_id'
             WHERE p.post_type = 'review' AND p.post_status = 'publish' AND pm.meta_value LIKE %s
             ORDER BY p.post_date DESC LIMIT 1",
            '%' . $wpdb->esc_like( $tt ) . '%'
        )
    );
    return $review_id ? (int) $review_id : null;
}

/**
 * Filmography lanes for a person from the relationship graph.
 * ACF relationship fields store serialized ID arrays — the LIKE '"id"'
 * pattern is the standard containment test.
 */
function lunara_entity_filmography( $person_id ) {
    $lanes = array( 'directed' => array(), 'performed' => array() );
    foreach ( array( 'directed' => 'directors', 'performed' => 'principal_cast' ) as $lane => $field ) {
        $lanes[ $lane ] = get_posts(
            array(
                'post_type'      => 'movie',
                'post_status'    => 'publish',
                'posts_per_page' => 60,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_key'       => 'release_year',
                'orderby'        => 'meta_value_num',
                'order'          => 'DESC',
                'meta_query'     => array(
                    array(
                        'key'     => $field,
                        'value'   => '"' . (int) $person_id . '"',
                        'compare' => 'LIKE',
                    ),
                ),
            )
        );
    }
    return $lanes;
}

/**
 * Oscars Ledger deep link for an entity (the plugin's own routes).
 */
function lunara_entity_ledger_url( $post_id ) {
    $id = strtolower( trim( (string) get_post_meta( $post_id, '_lunara_entity_id', true ) ) );
    if ( preg_match( '/^tt\d+$/', $id ) ) {
        return home_url( '/oscars/title/' . $id . '/' );
    }
    if ( preg_match( '/^nm\d+$/', $id ) ) {
        return home_url( '/oscars/name/' . $id . '/' );
    }
    return '';
}

/* ---------------------------------------------------------------------------
 * Renderers
 * ------------------------------------------------------------------------ */

/**
 * Award history block: ledger pills grouped by ceremony, winners lit gold.
 */
function lunara_entity_render_award_history( $rows, $context = 'movie' ) {
    if ( empty( $rows ) ) {
        return '';
    }
    $tally = lunara_entity_award_tally( $rows );
    ob_start();
    ?>
    <section class="lunara-entity-awards" aria-label="<?php esc_attr_e( 'Academy Award record', 'lunara-film' ); ?>">
        <div class="lunara-entity-awards-head">
            <p class="lunara-home-section-kicker"><?php esc_html_e( 'The Oscar Record', 'lunara-film' ); ?></p>
            <p class="lunara-entity-awards-tally">
                <span><?php echo esc_html( sprintf( _n( '%s nomination', '%s nominations', $tally['nominations'], 'lunara-film' ), number_format_i18n( $tally['nominations'] ) ) ); ?></span>
                <span aria-hidden="true">·</span>
                <span class="lunara-entity-tally-wins"><?php echo esc_html( sprintf( _n( '%s win', '%s wins', $tally['wins'], 'lunara-film' ), number_format_i18n( $tally['wins'] ) ) ); ?></span>
            </p>
        </div>
        <ul class="lunara-entity-award-list">
            <?php foreach ( $rows as $row ) :
                $won   = ! empty( $row['won'] );
                $year  = ! empty( $row['year'] ) ? (int) $row['year'] : 0;
                $other = 0;
                if ( 'movie' === $context && ! empty( $row['person_id'] ) ) {
                    $other = (int) $row['person_id'];
                } elseif ( 'person' === $context && ! empty( $row['movie_id'] ) ) {
                    $other = (int) $row['movie_id'];
                }
                ?>
                <li class="lunara-entity-award<?php echo $won ? ' is-win' : ''; ?>">
                    <span class="lunara-entity-award-year"><?php echo $year ? esc_html( $year ) : '&mdash;'; ?></span>
                    <span class="lunara-entity-award-cat"><?php echo esc_html( (string) $row['category'] ); ?></span>
                    <?php if ( $other && 'publish' === get_post_status( $other ) ) : ?>
                        <a class="lunara-entity-award-link" href="<?php echo esc_url( get_permalink( $other ) ); ?>"><?php echo esc_html( get_the_title( $other ) ); ?></a>
                    <?php endif; ?>
                    <span class="lunara-entity-award-state"><?php echo $won ? esc_html__( 'WINNER', 'lunara-film' ) : esc_html__( 'Nominee', 'lunara-film' ); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Poster card for archive grids and filmography lanes.
 */
function lunara_entity_render_film_card( $movie_id ) {
    $year = get_post_meta( $movie_id, 'release_year', true );
    ob_start();
    ?>
    <article class="lunara-entity-card lunara-entity-card--film">
        <a class="lunara-entity-card-link" href="<?php echo esc_url( get_permalink( $movie_id ) ); ?>">
            <div class="lunara-entity-card-poster">
                <?php if ( has_post_thumbnail( $movie_id ) ) : ?>
                    <?php echo get_the_post_thumbnail( $movie_id, 'medium_large', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
                <?php else : ?>
                    <span class="lunara-entity-card-plate"><?php echo esc_html( get_the_title( $movie_id ) ); ?></span>
                <?php endif; ?>
            </div>
            <h3 class="lunara-entity-card-title"><?php echo esc_html( get_the_title( $movie_id ) ); ?></h3>
            <?php if ( $year ) : ?><p class="lunara-entity-card-meta"><?php echo esc_html( $year ); ?></p><?php endif; ?>
        </a>
    </article>
    <?php
    return ob_get_clean();
}

/**
 * Portrait card for the talent archive.
 */
function lunara_entity_render_person_card( $person_id ) {
    $roles = get_post_meta( $person_id, 'roles', true );
    $roles = is_array( $roles ) ? $roles : ( is_string( $roles ) ? (array) maybe_unserialize( $roles ) : array() );
    ob_start();
    ?>
    <article class="lunara-entity-card lunara-entity-card--person">
        <a class="lunara-entity-card-link" href="<?php echo esc_url( get_permalink( $person_id ) ); ?>">
            <div class="lunara-entity-card-poster is-portrait">
                <?php if ( has_post_thumbnail( $person_id ) ) : ?>
                    <?php echo get_the_post_thumbnail( $person_id, 'medium_large', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
                <?php else : ?>
                    <span class="lunara-entity-card-plate"><?php echo esc_html( get_the_title( $person_id ) ); ?></span>
                <?php endif; ?>
            </div>
            <h3 class="lunara-entity-card-title"><?php echo esc_html( get_the_title( $person_id ) ); ?></h3>
            <?php if ( ! empty( $roles ) ) : ?><p class="lunara-entity-card-meta"><?php echo esc_html( implode( ' · ', array_map( 'ucfirst', array_filter( array_map( 'strval', $roles ) ) ) ) ); ?></p><?php endif; ?>
        </a>
    </article>
    <?php
    return ob_get_clean();
}

/* ---------------------------------------------------------------------------
 * Archive query shaping
 * ------------------------------------------------------------------------ */

function lunara_entity_shape_archive_queries( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }
    if ( $query->is_post_type_archive( 'movie' ) ) {
        $query->set( 'posts_per_page', 24 );
        $query->set( 'posts_per_archive_page', 24 );
        $sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'year';
        if ( 'az' === $sort ) {
            $query->set( 'orderby', 'title' );
            $query->set( 'order', 'ASC' );
        } else {
            $query->set( 'meta_key', 'release_year' );
            $query->set( 'orderby', 'meta_value_num' );
            $query->set( 'order', 'DESC' );
        }
    }
    if ( $query->is_post_type_archive( 'person' ) ) {
        $query->set( 'posts_per_page', 36 );
        $query->set( 'posts_per_archive_page', 36 );
        $query->set( 'orderby', 'title' );
        $query->set( 'order', 'ASC' );
        $role = isset( $_GET['role'] ) ? sanitize_key( wp_unslash( $_GET['role'] ) ) : '';
        if ( in_array( $role, array( 'director', 'actor', 'writer', 'craft' ), true ) ) {
            $query->set(
                'meta_query',
                array(
                    array(
                        'key'     => 'roles',
                        'value'   => '"' . $role . '"',
                        'compare' => 'LIKE',
                    ),
                )
            );
        }
    }
}
// PHP_INT_MAX: live testing showed the ordering applied at priority 99 but
// posts_per_page still came back 10 — something in the stack (Jetpack's
// archive modules are the usual suspect) re-caps it after us. The entity
// indexes' page size is not negotiable, so this runs dead last.
add_action( 'pre_get_posts', 'lunara_entity_shape_archive_queries', PHP_INT_MAX );

/* ---------------------------------------------------------------------------
 * JSON-LD (Design Spec 2.0 §11 / §15)
 * ------------------------------------------------------------------------ */

function lunara_entity_output_schema() {
    if ( is_singular( 'movie' ) ) {
        $post_id = get_the_ID();
        $tt      = strtolower( trim( (string) get_post_meta( $post_id, '_lunara_entity_id', true ) ) );
        $rows    = lunara_entity_movie_awards( $post_id );
        $tally   = lunara_entity_award_tally( $rows );
        $schema  = array(
            '@context' => 'https://schema.org',
            '@type'    => 'Movie',
            'name'     => get_the_title( $post_id ),
            'url'      => get_permalink( $post_id ),
        );
        $year = get_post_meta( $post_id, 'release_year', true );
        if ( $year ) {
            $schema['datePublished'] = (string) (int) $year;
        }
        if ( has_post_thumbnail( $post_id ) ) {
            $schema['image'] = get_the_post_thumbnail_url( $post_id, 'large' );
        }
        if ( preg_match( '/^tt\d+$/', $tt ) ) {
            $schema['sameAs'] = 'https://www.imdb.com/title/' . $tt . '/';
        }
        if ( $tally['nominations'] > 0 ) {
            $schema['award'] = sprintf( '%d Academy Award wins, %d nominations', $tally['wins'], $tally['nominations'] );
        }
        $directors = get_post_meta( $post_id, 'directors', true );
        $directors = is_array( $directors ) ? $directors : (array) maybe_unserialize( $directors );
        $director_nodes = array();
        foreach ( array_slice( array_filter( array_map( 'intval', $directors ) ), 0, 6 ) as $pid ) {
            if ( 'publish' === get_post_status( $pid ) ) {
                $director_nodes[] = array(
                    '@type' => 'Person',
                    'name'  => get_the_title( $pid ),
                    'url'   => get_permalink( $pid ),
                );
            }
        }
        if ( $director_nodes ) {
            $schema['director'] = $director_nodes;
        }
        echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
    }

    if ( is_singular( 'person' ) ) {
        $post_id = get_the_ID();
        $nm      = strtolower( trim( (string) get_post_meta( $post_id, '_lunara_entity_id', true ) ) );
        $schema  = array(
            '@context' => 'https://schema.org',
            '@type'    => 'Person',
            'name'     => get_the_title( $post_id ),
            'url'      => get_permalink( $post_id ),
        );
        if ( has_post_thumbnail( $post_id ) ) {
            $schema['image'] = get_the_post_thumbnail_url( $post_id, 'large' );
        }
        if ( preg_match( '/^nm\d+$/', $nm ) ) {
            $schema['sameAs'] = 'https://www.imdb.com/name/' . $nm . '/';
        }
        $roles = get_post_meta( $post_id, 'roles', true );
        $roles = is_array( $roles ) ? $roles : (array) maybe_unserialize( $roles );
        if ( ! empty( $roles ) ) {
            $schema['jobTitle'] = implode( ', ', array_map( 'ucfirst', array_filter( array_map( 'strval', $roles ) ) ) );
        }
        echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
    }
}
add_action( 'wp_head', 'lunara_entity_output_schema', 60 );
