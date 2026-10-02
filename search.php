<?php
/**
 * Search results template.
 *
 * @package Lunara_Film
 */

get_header();

if ( ! function_exists( 'lunara_search_select_theme_value' ) ) {
    function lunara_search_select_theme_value( $key, $default, $allowed ) {
        $preview_values = function_exists( 'lunara_get_utility_search_preview_preset_values' ) ? lunara_get_utility_search_preview_preset_values() : array();

        if ( isset( $preview_values[ $key ] ) ) {
            $preview_value = sanitize_key( (string) $preview_values[ $key ] );
            if ( in_array( $preview_value, $allowed, true ) ) {
                return $preview_value;
            }
        }

        $value = sanitize_key( (string) get_theme_mod( $key, $default ) );

        return in_array( $value, $allowed, true ) ? $value : $default;
    }
}

if ( ! function_exists( 'lunara_search_focus_post_type_priority' ) ) {
    function lunara_search_focus_post_type_priority( $post_type, $focus_type ) {
        $post_type  = (string) $post_type;
        $focus_type = (string) $focus_type;

        if ( 'review' === $focus_type ) {
            return 'review' === $post_type ? 0 : 1;
        }

        if ( 'journal' === $focus_type ) {
            return in_array( $post_type, array( 'post', 'journal' ), true ) ? 0 : 1;
        }

        if ( 'page' === $focus_type ) {
            return 'page' === $post_type ? 0 : 1;
        }

        return 1;
    }
}

if ( ! function_exists( 'lunara_search_focus_order_posts' ) ) {
    function lunara_search_focus_order_posts( $posts, $lead_focus, $spotlight_type ) {
        if ( empty( $posts ) || ! is_array( $posts ) ) {
            return array();
        }

        $focus_type = '';

        if ( in_array( $spotlight_type, array( 'review', 'journal', 'page' ), true ) ) {
            $focus_type = $spotlight_type;
        } elseif ( 'reviews' === $lead_focus ) {
            $focus_type = 'review';
        } elseif ( 'journal' === $lead_focus ) {
            $focus_type = 'journal';
        }

        if ( '' === $focus_type ) {
            return $posts;
        }

        $indexed = array();
        foreach ( $posts as $index => $post_item ) {
            $indexed[] = array(
                'index' => $index,
                'post'  => $post_item,
            );
        }

        usort(
            $indexed,
            static function ( $left, $right ) use ( $focus_type ) {
                $left_post  = $left['post'];
                $right_post = $right['post'];
                $left_type  = $left_post instanceof WP_Post ? $left_post->post_type : '';
                $right_type = $right_post instanceof WP_Post ? $right_post->post_type : '';
                $left_rank  = lunara_search_focus_post_type_priority( $left_type, $focus_type );
                $right_rank = lunara_search_focus_post_type_priority( $right_type, $focus_type );

                if ( $left_rank === $right_rank ) {
                    return $left['index'] <=> $right['index'];
                }

                return $left_rank <=> $right_rank;
            }
        );

        return array_values(
            array_map(
                static function ( $item ) {
                    return $item['post'];
                },
                $indexed
            )
        );
    }
}

if ( ! function_exists( 'lunara_search_query_text' ) ) {
    function lunara_search_query_text() {
        $query_text = trim( (string) get_search_query( false ) );

        if ( '' === $query_text && isset( $_GET['q'] ) ) {
            $query_text = trim( sanitize_text_field( wp_unslash( $_GET['q'] ) ) );
        }

        if ( '' === $query_text && isset( $_GET['s'] ) ) {
            $query_text = trim( sanitize_text_field( wp_unslash( $_GET['s'] ) ) );
        }

        return $query_text;
    }
}

if ( ! function_exists( 'lunara_search_result_post_types' ) ) {
    function lunara_search_result_post_types() {
        $post_types = array( 'review', 'journal', 'post', 'movie', 'person', 'page' );

        return array_values(
            array_filter(
                $post_types,
                static function ( $post_type ) {
                    return post_type_exists( $post_type );
                }
            )
        );
    }
}

if ( ! function_exists( 'lunara_search_command_results_query' ) ) {
    function lunara_search_command_results_query( $query_text ) {
        $query_text = trim( (string) $query_text );

        if ( '' === $query_text ) {
            return null;
        }

        return new WP_Query(
            array(
                's'                      => $query_text,
                'post_type'              => lunara_search_result_post_types(),
                'post_status'            => 'publish',
                'posts_per_page'         => 24,
                'ignore_sticky_posts'    => true,
                'no_found_rows'          => false,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => false,
            )
        );
    }
}

if ( ! function_exists( 'lunara_search_group_label' ) ) {
    function lunara_search_group_label( $post_type ) {
        $labels = array(
            'review'  => __( 'Reviews', 'lunara-film' ),
            'journal' => __( 'Journal', 'lunara-film' ),
            'post'    => __( 'Essays & Stories', 'lunara-film' ),
            'movie'   => __( 'Film Dossiers', 'lunara-film' ),
            'person'  => __( 'Talent', 'lunara-film' ),
            'page'    => __( 'Site Pages', 'lunara-film' ),
        );

        if ( isset( $labels[ $post_type ] ) ) {
            return $labels[ $post_type ];
        }

        $object = get_post_type_object( $post_type );
        return $object && ! empty( $object->labels->name ) ? $object->labels->name : __( 'Entries', 'lunara-film' );
    }
}

if ( ! function_exists( 'lunara_search_group_posts' ) ) {
    function lunara_search_group_posts( $posts ) {
        $order  = array( 'review', 'journal', 'post', 'movie', 'person', 'page' );
        $groups = array();

        foreach ( (array) $posts as $post_item ) {
            if ( ! ( $post_item instanceof WP_Post ) ) {
                continue;
            }

            $type = (string) $post_item->post_type;
            if ( ! isset( $groups[ $type ] ) ) {
                $groups[ $type ] = array(
                    'label' => lunara_search_group_label( $type ),
                    'posts' => array(),
                );
            }

            $groups[ $type ]['posts'][] = $post_item;
        }

        uksort(
            $groups,
            static function ( $left, $right ) use ( $order ) {
                $left_rank  = array_search( $left, $order, true );
                $right_rank = array_search( $right, $order, true );

                $left_rank  = false === $left_rank ? 99 : $left_rank;
                $right_rank = false === $right_rank ? 99 : $right_rank;

                return $left_rank <=> $right_rank;
            }
        );

        return $groups;
    }
}

if ( ! function_exists( 'lunara_search_render_post_result_card' ) ) {
    function lunara_search_render_post_result_card( $post_item ) {
        if ( ! ( $post_item instanceof WP_Post ) ) {
            return;
        }

        $type_label = lunara_search_group_label( $post_item->post_type );
        ?>
        <article class="lunara-search-result-card is-type-<?php echo esc_attr( sanitize_html_class( $post_item->post_type ) ); ?>">
            <a class="lunara-search-result-link" href="<?php echo esc_url( get_permalink( $post_item ) ); ?>">
                <p class="lunara-search-result-kicker"><?php echo esc_html( $type_label ); ?></p>
                <h3 class="lunara-search-result-title"><?php echo esc_html( get_the_title( $post_item ) ); ?></h3>
                <p class="lunara-search-result-copy"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post_item ) ), absint( get_theme_mod( 'lunara_search_excerpt_words', 22 ) ) ) ); ?></p>
            </a>
        </article>
        <?php
    }
}

if ( ! function_exists( 'lunara_search_render_oscar_matches' ) ) {
    function lunara_search_render_oscar_matches( $oscar_matches ) {
        if ( empty( $oscar_matches ) || ! is_array( $oscar_matches ) ) {
            return;
        }
        ?>
        <section class="lunara-home-section lunara-search-oscar-shell">
            <div class="lunara-home-section-head lunara-search-results-head">
                <div>
                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Oscar Signal', 'lunara-film' ); ?></p>
                    <h2 class="lunara-section-title"><?php esc_html_e( 'Direct ledger matches', 'lunara-film' ); ?></h2>
                </div>
            </div>

            <div class="lunara-search-oscar-grid">
                <?php foreach ( $oscar_matches as $match ) : ?>
                    <article class="lunara-search-oscar-card">
                        <a class="lunara-search-oscar-link" href="<?php echo esc_url( $match['url'] ); ?>">
                            <p class="lunara-search-oscar-kicker"><?php echo esc_html( $match['kicker'] ); ?></p>
                            <h3 class="lunara-search-oscar-title"><?php echo esc_html( $match['title'] ); ?></h3>
                            <?php if ( ! empty( $match['meta'] ) ) : ?>
                                <p class="lunara-search-oscar-meta"><?php echo esc_html( $match['meta'] ); ?></p>
                            <?php endif; ?>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}

$query_text    = lunara_search_query_text();
$command_query = function_exists( 'lunara_is_search_command_request' ) && lunara_is_search_command_request();
$custom_query  = $command_query ? lunara_search_command_results_query( $query_text ) : null;
$result_posts  = $custom_query instanceof WP_Query ? lunara_get_loop_posts( $custom_query ) : lunara_get_loop_posts();
$oscar_matches = function_exists( 'lunara_get_oscars_search_matches' ) ? lunara_get_oscars_search_matches( $query_text, 6 ) : array();
$recovery_hits = function_exists( 'lunara_get_search_recovery_routes' ) ? lunara_get_search_recovery_routes( $query_text, 6 ) : array();
$result_count  = 0;
$lead_focus    = lunara_search_select_theme_value( 'lunara_utility_search_lead_focus', 'balanced', array( 'balanced', 'ledger', 'reviews', 'journal' ) );
$spotlight_type = lunara_search_select_theme_value( 'lunara_utility_search_spotlight_type', 'automatic', array( 'automatic', 'review', 'journal', 'page' ) );
$result_posts  = lunara_search_focus_order_posts( $result_posts, $lead_focus, $spotlight_type );
$result_groups = lunara_search_group_posts( $result_posts );
$oscar_after_results = in_array( $lead_focus, array( 'reviews', 'journal' ), true );
$search_page_classes = array(
    'site-main',
    'lunara-archive-page',
    'lunara-search-page',
    'lunara-search-page--focus-' . $lead_focus,
    'lunara-search-page--spotlight-' . $spotlight_type,
);

global $wp_query;

if ( $custom_query instanceof WP_Query ) {
    $result_count = max( 0, intval( $custom_query->found_posts ) );
} elseif ( isset( $wp_query ) && $wp_query instanceof WP_Query ) {
    $result_count = max( 0, intval( $wp_query->found_posts ) );
}

if ( $result_count <= 0 ) {
    $result_count = count( $result_posts );
}

$archive_title = '' !== $query_text
    ? __( 'Search Results', 'lunara-film' )
    : __( 'Search Lunara Film', 'lunara-film' );

$archive_copy = __( 'A command surface for criticism, Journal entries, film dossiers, talent files, and the Oscar Ledger.', 'lunara-film' );

$overview_lines = array(
    array(
        'label' => __( 'Matches', 'lunara-film' ),
        'value' => number_format_i18n( $result_count ),
    ),
    array(
        'label' => __( 'Query', 'lunara-film' ),
        'value' => '' !== $query_text ? $query_text : __( 'None entered', 'lunara-film' ),
    ),
    array(
        'label' => __( 'Search Scope', 'lunara-film' ),
        'value' => __( 'Reviews / Editorial / Direct Oscar matches', 'lunara-film' ),
    ),
);

if ( ! empty( $oscar_matches ) ) {
    $overview_lines[] = array(
        'label' => __( 'Ledger Hits', 'lunara-film' ),
        'value' => number_format_i18n( count( $oscar_matches ) ),
    );
}

if ( empty( $result_posts ) && empty( $oscar_matches ) && ! empty( $recovery_hits ) ) {
    $overview_lines[] = array(
        'label' => __( 'Closest Routes', 'lunara-film' ),
        'value' => number_format_i18n( count( $recovery_hits ) ),
    );
}
?>
<main id="primary" class="<?php echo esc_attr( implode( ' ', $search_page_classes ) ); ?>">
    <section class="lunara-home-section lunara-archive-hero">
        <div class="lunara-editorial-archive-hero-shell">
            <div class="lunara-editorial-archive-hero-copy-wrap">
                <p class="lunara-archive-hero-kicker"><?php echo esc_html( get_theme_mod( 'lunara_search_kicker', __( 'Search Desk', 'lunara-film' ) ) ); ?></p>
                <h1 class="lunara-archive-hero-title"><?php echo esc_html( $archive_title ); ?></h1>
                <?php if ( '' !== $query_text ) : ?>
                    <p class="lunara-search-query-line">
                        <span><?php esc_html_e( 'Query', 'lunara-film' ); ?></span>
                        <strong><?php echo esc_html( $query_text ); ?></strong>
                    </p>
                <?php endif; ?>
                <?php if ( '' !== $archive_copy ) : ?>
                    <p class="lunara-archive-hero-copy"><?php echo esc_html( $archive_copy ); ?></p>
                <?php endif; ?>
            </div>
            <aside class="lunara-editorial-archive-debrief" aria-label="<?php esc_attr_e( 'Search summary', 'lunara-film' ); ?>">
                <p class="lunara-editorial-archive-debrief-kicker"><?php esc_html_e( 'At A Glance', 'lunara-film' ); ?></p>
                <ul class="lunara-editorial-archive-debrief-list">
                    <?php foreach ( $overview_lines as $line ) : ?>
                        <li>
                            <strong><?php echo esc_html( $line['label'] ); ?></strong>
                            <span><?php echo esc_html( $line['value'] ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>
        </div>
        <form role="search" method="get" class="lunara-search-command-form" action="<?php echo esc_url( function_exists( 'lunara_search_command_url' ) ? lunara_search_command_url() : home_url( '/' ) ); ?>">
            <label class="screen-reader-text" for="lunara-search-command-input"><?php esc_html_e( 'Search Lunara Film', 'lunara-film' ); ?></label>
            <input id="lunara-search-command-input" type="search" class="lunara-search-command-input" placeholder="<?php esc_attr_e( 'Search a film, critic file, Oscar category, or person', 'lunara-film' ); ?>" value="<?php echo esc_attr( $query_text ); ?>" name="<?php echo esc_attr( function_exists( 'lunara_search_command_url' ) ? 'q' : 's' ); ?>" />
            <button type="submit" class="lunara-search-command-submit"><?php esc_html_e( 'Run Search', 'lunara-film' ); ?></button>
        </form>
    </section>

    <?php if ( ! $oscar_after_results ) : ?>
        <?php lunara_search_render_oscar_matches( $oscar_matches ); ?>
    <?php endif; ?>

    <?php if ( ! empty( $result_groups ) ) : ?>
        <section class="lunara-home-section lunara-search-results-shell">
            <div class="lunara-home-section-head lunara-search-results-head">
                <div>
                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Search Run', 'lunara-film' ); ?></p>
                    <h2 class="lunara-section-title"><?php esc_html_e( 'Results by desk', 'lunara-film' ); ?></h2>
                </div>
            </div>

            <div class="lunara-search-command-lanes">
                <?php foreach ( $result_groups as $group_key => $group ) : ?>
                    <section class="lunara-search-command-lane is-lane-<?php echo esc_attr( sanitize_html_class( $group_key ) ); ?>">
                        <div class="lunara-search-command-lane-head">
                            <p class="lunara-search-command-lane-kicker"><?php echo esc_html( $group['label'] ); ?></p>
                            <span><?php echo esc_html( number_format_i18n( count( $group['posts'] ) ) ); ?></span>
                        </div>
                        <div class="lunara-search-results-grid">
                            <?php foreach ( $group['posts'] as $post_item ) : ?>
                                <?php lunara_search_render_post_result_card( $post_item ); ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>

            <?php if ( ! ( $custom_query instanceof WP_Query ) ) : ?>
                <?php the_posts_pagination(); ?>
            <?php endif; ?>
        </section>
    <?php elseif ( ! empty( $recovery_hits ) ) : ?>
        <section class="lunara-home-section lunara-search-recovery-shell">
            <div class="lunara-home-section-head lunara-search-results-head">
                <div>
                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Closest Routes', 'lunara-film' ); ?></p>
                    <h2 class="lunara-section-title"><?php esc_html_e( 'You were close. These are the strongest nearby routes.', 'lunara-film' ); ?></h2>
                </div>
            </div>

            <div class="lunara-search-oscar-grid lunara-search-recovery-grid">
                <?php foreach ( $recovery_hits as $match ) : ?>
                    <article class="lunara-search-oscar-card lunara-search-recovery-card">
                        <a class="lunara-search-oscar-link" href="<?php echo esc_url( $match['url'] ); ?>">
                            <p class="lunara-search-oscar-kicker"><?php echo esc_html( $match['kicker'] ); ?></p>
                            <h3 class="lunara-search-oscar-title"><?php echo esc_html( $match['title'] ); ?></h3>
                            <?php if ( ! empty( $match['meta'] ) ) : ?>
                                <p class="lunara-search-oscar-meta"><?php echo esc_html( $match['meta'] ); ?></p>
                            <?php endif; ?>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php elseif ( empty( $oscar_matches ) ) : ?>
        <section class="lunara-home-section lunara-search-empty-shell">
            <div class="lunara-editorial-archive-empty-shell">
                <div class="lunara-archive-empty lunara-editorial-archive-empty">
                    <h2><?php esc_html_e( 'Nothing matched that search yet.', 'lunara-film' ); ?></h2>
                    <p><?php esc_html_e( 'Try a film title, a filmmaker, an Oscar category, or a broader keyword.', 'lunara-film' ); ?></p>
                </div>
                <div class="lunara-editorial-archive-empty-note">
                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Try Again', 'lunara-film' ); ?></p>
                    <h2 class="lunara-section-title"><?php esc_html_e( 'Search for a title, person, or argument worth reopening.', 'lunara-film' ); ?></h2>
                    <form role="search" method="get" class="lunara-search-form lunara-search-form-shell" action="<?php echo esc_url( function_exists( 'lunara_search_command_url' ) ? lunara_search_command_url() : home_url( '/' ) ); ?>">
                        <label class="screen-reader-text" for="lunara-search-input"><?php esc_html_e( 'Search for:', 'lunara-film' ); ?></label>
                        <input id="lunara-search-input" type="search" class="lunara-search-input" placeholder="<?php esc_attr_e( 'Search Lunara Film', 'lunara-film' ); ?>" value="<?php echo esc_attr( $query_text ); ?>" name="<?php echo esc_attr( function_exists( 'lunara_search_command_url' ) ? 'q' : 's' ); ?>" />
                        <button type="submit" class="lunara-btn lunara-btn-primary"><?php esc_html_e( 'Search', 'lunara-film' ); ?></button>
                    </form>
                </div>
            </div>
            <div class="lunara-search-empty-routes-shell">
                <div class="lunara-home-section-head lunara-search-results-head">
                    <div>
                        <p class="lunara-home-section-kicker"><?php esc_html_e( 'Stay In The Record', 'lunara-film' ); ?></p>
                        <h2 class="lunara-section-title"><?php esc_html_e( 'Elsewhere in Lunara', 'lunara-film' ); ?></h2>
                    </div>
                </div>
                <div class="lunara-search-empty-routes-grid">
                    <a class="lunara-search-empty-route-card" href="<?php echo esc_url( get_post_type_archive_link( 'review' ) ?: home_url( '/reviews/' ) ); ?>">
                        <p class="lunara-home-section-kicker"><?php esc_html_e( 'Criticism', 'lunara-film' ); ?></p>
                        <h3><?php esc_html_e( 'Browse The Review Archive', 'lunara-film' ); ?></h3>
                        <span class="lunara-section-link"><?php esc_html_e( 'Enter The Reviews', 'lunara-film' ); ?></span>
                    </a>
                    <a class="lunara-search-empty-route-card" href="<?php echo esc_url( home_url( '/oscars/' ) ); ?>">
                        <p class="lunara-home-section-kicker"><?php esc_html_e( 'Ledger', 'lunara-film' ); ?></p>
                        <h3><?php esc_html_e( 'Open The Oscar Ledger', 'lunara-film' ); ?></h3>
                        <span class="lunara-section-link"><?php esc_html_e( 'Open The Ledger', 'lunara-film' ); ?></span>
                    </a>
                    <a class="lunara-search-empty-route-card" href="<?php echo esc_url( home_url( '/news/' ) ); ?>">
                        <p class="lunara-home-section-kicker"><?php esc_html_e( 'Editorial', 'lunara-film' ); ?></p>
                        <h3><?php esc_html_e( 'Return To The Journal', 'lunara-film' ); ?></h3>
                        <span class="lunara-section-link"><?php esc_html_e( 'Open The Journal', 'lunara-film' ); ?></span>
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ( $oscar_after_results ) : ?>
        <?php lunara_search_render_oscar_matches( $oscar_matches ); ?>
    <?php endif; ?>
</main>
<?php
get_footer();
