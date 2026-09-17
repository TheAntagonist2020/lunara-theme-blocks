<?php
/**
 * Single post template for Lunara editorial dispatches.
 */

if ( ! function_exists( 'lunara_theme_mod_text' ) ) {
    function lunara_theme_mod_text( $key, $default = '' ) {
        $value = get_theme_mod( $key, $default );
        return is_string( $value ) ? trim( $value ) : $default;
    }
}

if ( ! function_exists( 'lunara_card_excerpt' ) ) {
    function lunara_card_excerpt( $post_id, $words = 22 ) {
        if ( has_excerpt( $post_id ) ) {
            return wp_trim_words( get_the_excerpt( $post_id ), $words );
        }

        return wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ), $words );
    }
}

if ( ! function_exists( 'lunara_get_dispatch_type_label' ) ) {
    function lunara_get_dispatch_type_label( $post_id ) {
        $priority_labels = array(
            'podcast'      => __( 'Podcast', 'lunara-film' ),
            'audio'        => __( 'Podcast', 'lunara-film' ),
            'news'         => __( 'News', 'lunara-film' ),
            'reaction'     => __( 'Reaction', 'lunara-film' ),
            'reactions'    => __( 'Reaction', 'lunara-film' ),
            'think-piece'  => __( 'Think Piece', 'lunara-film' ),
            'think-pieces' => __( 'Think Piece', 'lunara-film' ),
            'essay'        => __( 'Essay', 'lunara-film' ),
            'essays'       => __( 'Essay', 'lunara-film' ),
            'ink'          => __( 'Ink', 'lunara-film' ),
            'interview'    => __( 'Interview', 'lunara-film' ),
        );

        $terms = get_the_terms( $post_id, 'category' );
        if ( ! is_array( $terms ) ) {
            return __( 'Dispatch', 'lunara-film' );
        }

        foreach ( $priority_labels as $slug => $label ) {
            foreach ( $terms as $term ) {
                if ( $term instanceof WP_Term && $term->slug === $slug ) {
                    return $label;
                }
            }
        }

        foreach ( $terms as $term ) {
            if ( $term instanceof WP_Term && 'uncategorized' !== $term->slug && '' !== trim( (string) $term->name ) ) {
                return (string) $term->name;
            }
        }

        return __( 'Dispatch', 'lunara-film' );
    }
}

if ( ! function_exists( 'lunara_get_dispatch_category_slugs' ) ) {
    function lunara_get_dispatch_category_slugs() {
        $raw_slugs = lunara_theme_mod_text( 'lunara_home_dispatch_category_slugs', 'news,think-pieces,reactions,podcast' );
        return array_values( array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', $raw_slugs ) ) ) ) );
    }
}

if ( ! function_exists( 'lunara_get_dispatch_category_line' ) ) {
    function lunara_get_dispatch_category_line( $post_id ) {
        $terms = get_the_terms( $post_id, 'category' );
        if ( ! is_array( $terms ) ) {
            return '';
        }

        $labels = array();
        foreach ( $terms as $term ) {
            if ( ! ( $term instanceof WP_Term ) || 'uncategorized' === $term->slug ) {
                continue;
            }

            $labels[] = trim( (string) $term->name );
        }

        return implode( ' / ', array_slice( array_values( array_filter( array_unique( $labels ) ) ), 0, 2 ) );
    }
}

if ( ! function_exists( 'lunara_get_post_reading_time' ) ) {
    function lunara_get_post_reading_time( $post_id ) {
        $content = wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) );
        $words   = str_word_count( $content );

        if ( $words <= 0 ) {
            return '';
        }

        $wpm     = absint( get_theme_mod( 'lunara_post_reading_wpm', 225 ) );
        $wpm     = $wpm > 0 ? $wpm : 225;
        $minutes = max( 1, (int) ceil( $words / $wpm ) );

        return sprintf(
            _n( '%d min read', '%d mins read', $minutes, 'lunara-film' ),
            $minutes
        );
    }
}

if ( ! function_exists( 'lunara_get_post_tag_line' ) ) {
    function lunara_get_post_tag_line( $post_id, $limit = 4 ) {
        $terms = get_the_terms( $post_id, 'post_tag' );
        if ( ! is_array( $terms ) ) {
            return '';
        }

        $labels = array();
        foreach ( $terms as $term ) {
            if ( $term instanceof WP_Term ) {
                $labels[] = trim( (string) $term->name );
            }
        }

        return implode( ' / ', array_slice( array_values( array_filter( array_unique( $labels ) ) ), 0, max( 1, intval( $limit ) ) ) );
    }
}

if ( ! function_exists( 'lunara_home_dispatch_archive_url' ) ) {
    function lunara_home_dispatch_archive_url() {
        $custom_url = trim( (string) get_theme_mod( 'lunara_home_dispatch_button_url', '' ) );
        if ( '' !== $custom_url ) {
            return esc_url_raw( $custom_url );
        }

        foreach ( lunara_get_dispatch_category_slugs() as $slug ) {
            $term = get_category_by_slug( $slug );
            if ( $term instanceof WP_Term && intval( $term->count ) > 0 ) {
                $term_link = get_term_link( $term );
                if ( ! is_wp_error( $term_link ) ) {
                    return $term_link;
                }
            }
        }

        foreach ( array( 'news', 'journal', 'blog' ) as $path ) {
            $page = get_page_by_path( $path );
            if ( $page instanceof WP_Post ) {
                $page_url = get_permalink( $page );
                if ( is_string( $page_url ) && '' !== $page_url ) {
                    return $page_url;
                }
            }
        }

        return home_url( '/news/' );
    }
}

if ( ! function_exists( 'lunara_get_related_dispatch_posts' ) ) {
    function lunara_get_related_dispatch_posts( $post_id, $count = 3 ) {
        $post_id = intval( $post_id );
        $count   = max( 1, intval( $count ) );
        $cat_ids = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );

        $query_args = array(
            'post_type'              => 'post',
            'post_status'            => 'publish',
            'posts_per_page'         => $count,
            'post__not_in'           => array( $post_id ),
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
            'orderby'                => 'date',
            'order'                  => 'DESC',
        );

        if ( ! empty( $cat_ids ) ) {
            $query_args['category__in'] = array_map( 'intval', $cat_ids );
        } else {
            $dispatch_slugs = lunara_get_dispatch_category_slugs();
            if ( ! empty( $dispatch_slugs ) ) {
                $query_args['tax_query'] = array(
                    array(
                        'taxonomy' => 'category',
                        'field'    => 'slug',
                        'terms'    => $dispatch_slugs,
                        'operator' => 'IN',
                    ),
                );
            }
        }

        return new WP_Query( $query_args );
    }
}

if ( ! function_exists( 'lunara_render_dispatch_archive_card' ) ) {
    function lunara_render_dispatch_archive_card( $post_id, $featured = false ) {
        $post_id        = intval( $post_id );
        $featured       = (bool) $featured;
        $type_label     = lunara_get_dispatch_type_label( $post_id );
        $category_line  = lunara_get_dispatch_category_line( $post_id );
        $excerpt_length = $featured ? 40 : 22;
        $excerpt        = lunara_card_excerpt( $post_id, $excerpt_length );

        ob_start();

        if ( $featured ) :
            ?>
            <article class="lunara-dispatch-lead lunara-archive-lead-card">
                <a class="lunara-dispatch-lead-link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                    <div class="lunara-dispatch-lead-media">
                        <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                            <?php echo get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'lunara-dispatch-lead-image', 'loading' => 'lazy' ) ); ?>
                        <?php else : ?>
                            <div class="lunara-dispatch-lead-placeholder"><?php echo esc_html( $type_label ); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="lunara-dispatch-lead-copy">
                        <p class="lunara-dispatch-type"><?php echo esc_html( $type_label ); ?></p>
                        <h2 class="lunara-dispatch-lead-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h2>
                        <?php if ( '' !== $excerpt ) : ?>
                            <p class="lunara-dispatch-lead-excerpt"><?php echo esc_html( $excerpt ); ?></p>
                        <?php endif; ?>
                        <div class="lunara-dispatch-lead-meta">
                            <span><?php echo esc_html( get_the_date( 'F j, Y', $post_id ) ); ?></span>
                            <?php if ( '' !== $category_line ) : ?>
                                <span><?php echo esc_html( $category_line ); ?></span>
                            <?php endif; ?>
                            <span class="lunara-dispatch-meta-link"><?php esc_html_e( 'Read or listen', 'lunara-film' ); ?></span>
                        </div>
                    </div>
                </a>
            </article>
            <?php
        else :
            ?>
            <article class="lunara-dispatch-archive-card">
                <a class="lunara-dispatch-archive-link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                    <div class="lunara-dispatch-archive-thumb-wrap">
                        <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                            <?php echo get_the_post_thumbnail( $post_id, 'medium_large', array( 'class' => 'lunara-dispatch-archive-thumb', 'loading' => 'lazy' ) ); ?>
                        <?php else : ?>
                            <div class="lunara-dispatch-rail-thumb-placeholder"><?php echo esc_html( $type_label ); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="lunara-dispatch-archive-copy">
                        <p class="lunara-dispatch-type"><?php echo esc_html( $type_label ); ?></p>
                        <h3 class="lunara-dispatch-archive-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
                        <?php if ( '' !== $excerpt ) : ?>
                            <p class="lunara-dispatch-archive-excerpt"><?php echo esc_html( $excerpt ); ?></p>
                        <?php endif; ?>
                        <p class="lunara-dispatch-archive-meta">
                            <span><?php echo esc_html( get_the_date( 'F j, Y', $post_id ) ); ?></span>
                            <?php if ( '' !== $category_line ) : ?>
                                <span><?php echo esc_html( $category_line ); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </a>
            </article>
            <?php
        endif;

        return ob_get_clean();
    }
}

$post_type = get_post_type();

if ( 'review' === $post_type ) {
    if ( function_exists( 'lunara_prepend_review_metadata' ) ) {
        remove_filter( 'the_content', 'lunara_prepend_review_metadata', 5 );
    }

    get_header();

    if ( have_posts() ) :
        while ( have_posts() ) :
            the_post();

            $post_id       = get_the_ID();
            $score         = trim( (string) get_post_meta( $post_id, '_lunara_score', true ) );
            $year          = trim( (string) get_post_meta( $post_id, '_lunara_year', true ) );
            $director      = trim( (string) get_post_meta( $post_id, '_lunara_director', true ) );
            $runtime       = trim( (string) get_post_meta( $post_id, '_lunara_runtime', true ) );
            $studio        = trim( (string) get_post_meta( $post_id, '_lunara_studio', true ) );
            $where         = trim( (string) get_post_meta( $post_id, '_lunara_where', true ) );
            $excerpt       = has_excerpt( $post_id ) ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_the_content( null, false, $post_id ) ), 42 );
            $archive_url   = get_post_type_archive_link( 'review' );
            $archive_url   = is_string( $archive_url ) && '' !== $archive_url ? $archive_url : home_url( '/reviews/' );
            $review_meta   = implode( ' / ', array_filter( array( $year, $director ) ) );
            $detail_items  = array_filter(
                array(
                    'Year'      => $year,
                    'Director'  => $director,
                    'Runtime'   => $runtime,
                    'Studio'    => $studio,
                    'Published' => get_the_date( 'F j, Y', $post_id ),
                ),
                static function( $value ) {
                    return '' !== trim( (string) $value );
                }
            );
            $stars            = '';
            $ledger_pill      = '';
            $related_query    = null;
            $review_tt        = '';
            // Strip the [lunara_debrief] shortcode from content BEFORE rendering,
            // so it only renders once via the template's dedicated debrief section at the end.
            $raw_content = (string) get_post_field( 'post_content', $post_id );
            $raw_content = preg_replace( '/\[lunara_debrief\]/', '', $raw_content );
            $rendered_content = (string) apply_filters( 'the_content', $raw_content );
            $body_html       = $rendered_content;
            $debrief_block   = '';
            $postscript_html = '';
            $clean_postscript = static function( $html ) {
                $html = trim( (string) $html );
                if ( '' === $html ) {
                    return '';
                }

                $html = preg_replace( '~<section class="lunara-debrief-block".*?</section>~is', '', $html );
                $html = preg_replace( '~<div class="lunara-debrief-kicker">\s*A\s+LUNARA\s+FILM\s+SIGNATURE\s*</div>~i', '', $html );
                $html = preg_replace( '~\s*\|\s*IMDB:\s*(?=(?:</span>|</p>|<))~i', '', $html );

                $plain = strtolower( trim( wp_strip_all_tags( $html ) ) );
                $plain = str_replace(
                    array(
                        'lunara debrief',
                        'a lunara film signature',
                    ),
                    '',
                    $plain
                );

                return '' === trim( $plain ) ? '' : trim( $html );
            };

            if ( function_exists( 'lunara_extract_review_content_sections' ) ) {
                $sections        = lunara_extract_review_content_sections( $rendered_content );
                $body_html       = '' !== trim( (string) $sections['body'] ) ? (string) $sections['body'] : $rendered_content;
                $debrief_block   = trim( (string) $sections['debrief'] );
                $postscript_html = $clean_postscript( $sections['postscript'] );
            }

            // If no debrief was found in the rendered content (because we stripped the shortcode),
            // render it fresh here for the dedicated closing section.
            if ( '' === $debrief_block && function_exists( 'lunara_debrief_shortcode' ) ) {
                $debrief_block = trim( (string) lunara_debrief_shortcode( array() ) );
            }

            if ( '' !== $body_html && function_exists( 'lunara_insert_review_visuals_into_body_html' ) ) {
                $body_html = lunara_insert_review_visuals_into_body_html( $body_html, $post_id );
            }

            if ( '' !== $score && function_exists( 'lunara_render_stars' ) ) {
                $stars = lunara_render_stars( $score );
            } elseif ( '' !== $score ) {
                $stars = sprintf( '%s/5', $score );
            }

            if ( function_exists( 'lunara_get_review_imdb_title_id' ) && function_exists( 'lunara_get_oscar_ledger_counts' ) && function_exists( 'lunara_render_oscar_ledger_pill' ) ) {
                $review_tt = lunara_get_review_imdb_title_id( $post_id );
                if ( '' !== $review_tt ) {
                    $ledger_pill = lunara_render_oscar_ledger_pill( $review_tt, lunara_get_oscar_ledger_counts( $review_tt ) );
                }
            }

            if ( function_exists( 'lunara_get_related_review_posts' ) ) {
                $related_query = lunara_get_related_review_posts( $post_id, 4 );
            }

            $debrief_media = function_exists( 'lunara_get_review_debrief_signature_media_html' )
                ? lunara_get_review_debrief_signature_media_html( $post_id )
                : '';
            $hero_banner_html = function_exists( 'lunara_render_review_visual_slot' )
                ? lunara_render_review_visual_slot(
                    $post_id,
                    'hero_banner',
                    array(
                        'loading' => 'eager',
                        'context' => 'hero',
                    )
                )
                : '';
            ?>
            <main id="primary" class="site-main lunara-archive-page lunara-review-single-page">
                <article <?php post_class( 'lunara-journal-single lunara-review-single' ); ?>>
                    <section class="lunara-review-single-hero">
                        <div class="lunara-review-single-hero-inner">
                            <p class="lunara-archive-hero-kicker"><?php esc_html_e( 'Lunara Review', 'lunara-film' ); ?></p>
                            <h1 class="lunara-review-single-title"><?php the_title(); ?></h1>

                            <?php if ( '' !== trim( $excerpt ) ) : ?>
                                <p class="lunara-review-single-excerpt"><?php echo esc_html( $excerpt ); ?></p>
                            <?php endif; ?>

                            <div class="lunara-review-single-meta">
                                <?php if ( '' !== $review_meta ) : ?><span><?php echo esc_html( $review_meta ); ?></span><?php endif; ?>
                                <span><?php echo esc_html( get_the_date( 'F j, Y', $post_id ) ); ?></span>
                                <?php if ( '' !== $stars ) : ?><span><?php echo wp_kses_post( $stars ); ?></span><?php endif; ?>
                            </div>

                            <?php if ( '' !== $ledger_pill ) : ?>
                                <div class="lunara-review-single-hero-tools">
                                    <?php echo wp_kses_post( $ledger_pill ); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ( '' !== $hero_banner_html ) : ?>
                            <div class="lunara-review-single-banner-shell">
                                <?php echo $hero_banner_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="lunara-review-single-body">
                        <div class="lunara-review-single-body-grid">
                            <div class="lunara-review-single-content">
                                <?php
                                if ( '' !== $body_html ) {
                                    echo wp_kses_post( $body_html );
                                } else {
                                    the_content();
                                }
                                ?>
                            </div>

                            <aside class="lunara-review-single-rail" aria-label="<?php esc_attr_e( 'Review details', 'lunara-film' ); ?>">
                                <div class="lunara-review-single-rail-sticky">
                                    <?php if ( '' !== $where ) : ?>
                                        <?php
                                        $watch_url = '';
                                        if ( '' !== $review_tt && function_exists( 'lunara_where_to_watch_shortcode' ) ) {
                                            $watch_providers = (string) lunara_where_to_watch_shortcode( array( 'imdb' => $review_tt ) );
                                            if ( '' !== trim( $watch_providers ) && preg_match( '~https://www\.themoviedb\.org/[^"\']+/watch\?locale=[A-Z]{2}~', $watch_providers, $watch_match ) ) {
                                                $watch_url = $watch_match[0];
                                            }
                                        }
                                        ?>
                                        <div class="lunara-journal-rail-card lunara-review-single-where-card">
                                            <p class="lunara-home-section-kicker"><?php esc_html_e( 'Where to Watch', 'lunara-film' ); ?></p>
                                            <div class="lunara-review-single-where-value">
                                                <?php echo wp_kses_post( lunara_render_review_where_links( $where, get_the_title( $post_id ), $watch_url ) ); ?>
                                            </div>
                                            <?php if ( '' !== $review_tt && function_exists( 'lunara_where_to_watch_shortcode' ) ) : ?>
                                                <div class="lunara-review-single-where-providers">
                                                    <?php echo lunara_where_to_watch_shortcode( array( 'imdb' => $review_tt ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes internally, same output as the former shortcode. ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ( ! empty( $detail_items ) ) : ?>
                                        <div class="lunara-journal-rail-card lunara-review-single-details">
                                            <p class="lunara-home-section-kicker"><?php esc_html_e( 'Review Details', 'lunara-film' ); ?></p>
                                            <ul class="lunara-review-single-detail-list">
                                                <?php foreach ( $detail_items as $label => $value ) : ?>
                                                    <li>
                                                        <strong><?php echo esc_html( $label ); ?></strong>
                                                        <span><?php echo esc_html( $value ); ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <div class="lunara-review-single-rail-actions">
                                        <a class="lunara-btn lunara-btn-primary" href="<?php echo esc_url( $archive_url ); ?>">
                                            <?php esc_html_e( 'Browse Reviews', 'lunara-film' ); ?>
                                        </a>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </section>

                    <?php if ( '' !== trim( wp_strip_all_tags( $debrief_block ) ) || '' !== trim( wp_strip_all_tags( $postscript_html ) ) ) : ?>
                        <section class="lunara-home-section lunara-review-single-debrief-shell">
                            <div class="lunara-home-section-head lunara-review-single-debrief-head">
                                <div>
                                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Lunara Debrief', 'lunara-film' ); ?></p>
                                    <h2 class="lunara-section-title"><?php esc_html_e( 'The Closing Ledger', 'lunara-film' ); ?></h2>
                                </div>
                                <?php if ( '' !== $ledger_pill ) : ?>
                                    <div class="lunara-review-single-debrief-ledger"><?php echo wp_kses_post( $ledger_pill ); ?></div>
                                <?php endif; ?>
                            </div>

                            <?php if ( '' !== trim( wp_strip_all_tags( $debrief_block ) ) ) : ?>
                                <div class="lunara-review-single-debrief-wrap<?php echo '' !== $debrief_media ? ' has-signature-media' : ''; ?>">
                                    <?php if ( '' !== $debrief_media ) : ?>
                                        <?php echo $debrief_media; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <?php endif; ?>
                                    <div class="lunara-review-single-debrief">
                                        <?php echo $debrief_block; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ( '' !== trim( wp_strip_all_tags( $postscript_html ) ) ) : ?>
                                <div class="lunara-review-single-postscript">
                                    <?php echo wp_kses_post( $postscript_html ); ?>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <?php if ( $related_query instanceof WP_Query && $related_query->have_posts() && function_exists( 'lunara_render_review_grid_card' ) ) : ?>
                        <section class="lunara-home-section lunara-review-related">
                            <div class="lunara-home-section-head">
                                <div>
                                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Continue Watching', 'lunara-film' ); ?></p>
                                    <h2 class="lunara-section-title"><?php esc_html_e( 'More Lunara Criticism', 'lunara-film' ); ?></h2>
                                </div>
                                <a class="lunara-section-link" href="<?php echo esc_url( $archive_url ); ?>">
                                    <?php esc_html_e( 'Open Reviews', 'lunara-film' ); ?>
                                </a>
                            </div>

                            <div class="lunara-review-grid lunara-review-related-grid">
                                <?php
                                while ( $related_query->have_posts() ) :
                                    $related_query->the_post();
                                    echo lunara_render_review_grid_card( get_the_ID() );
                                endwhile;
                                wp_reset_postdata();
                                ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </article>
            </main>
            <?php
        endwhile;
    endif;

    get_footer();
    return;
}

if ( 'post' !== get_post_type() ) {
    $fallback = locate_template(
        array(
            'single-' . get_post_type() . '.php',
            'singular.php',
            'index.php',
        ),
        false,
        false
    );

    if ( $fallback ) {
        include $fallback;
        return;
    }
}

get_header();

if ( have_posts() ) :
    while ( have_posts() ) :
        the_post();

        $post_id        = get_the_ID();
        $type_label     = lunara_get_dispatch_type_label( $post_id );
        $type_slug      = function_exists( 'lunara_get_dispatch_type_slug' ) ? lunara_get_dispatch_type_slug( $post_id ) : 'dispatch';
        $category_line  = lunara_get_dispatch_category_line( $post_id );
        $tag_line       = lunara_get_post_tag_line( $post_id );
        $reading_time   = lunara_get_post_reading_time( $post_id );
        $type_label_override = trim( (string) get_post_meta( $post_id, '_lunara_post_type_label_override', true ) );
        $hero_image_override = trim( (string) get_post_meta( $post_id, '_lunara_post_hero_image_url', true ) );
        $hero_secondary_override = trim( (string) get_post_meta( $post_id, '_lunara_post_hero_secondary_image_url', true ) );
        $hero_media_layout       = trim( (string) get_post_meta( $post_id, '_lunara_post_hero_media_layout', true ) );
        $standfirst_override = trim( (string) get_post_meta( $post_id, '_lunara_post_standfirst', true ) );
        $hide_standfirst     = '1' === (string) get_post_meta( $post_id, '_lunara_post_hide_standfirst', true );
        $hide_hero_media     = '1' === (string) get_post_meta( $post_id, '_lunara_post_hide_hero_media', true );
        $signal_note_override= trim( (string) get_post_meta( $post_id, '_lunara_post_signal_note', true ) );
        $archive_cta_label   = trim( (string) get_post_meta( $post_id, '_lunara_post_archive_cta_label', true ) );
        $archive_url_override= trim( (string) get_post_meta( $post_id, '_lunara_post_archive_url_override', true ) );
        $details_kicker      = trim( (string) get_post_meta( $post_id, '_lunara_post_details_kicker', true ) );
        $signal_kicker       = trim( (string) get_post_meta( $post_id, '_lunara_post_signal_kicker', true ) );
        $category_override   = trim( (string) get_post_meta( $post_id, '_lunara_post_category_line_override', true ) );
        $tag_override        = trim( (string) get_post_meta( $post_id, '_lunara_post_tag_line_override', true ) );
        $related_kicker      = trim( (string) get_post_meta( $post_id, '_lunara_post_related_kicker', true ) );
        $related_heading     = trim( (string) get_post_meta( $post_id, '_lunara_post_related_heading', true ) );
        $related_copy        = trim( (string) get_post_meta( $post_id, '_lunara_post_related_copy', true ) );
        $related_button      = trim( (string) get_post_meta( $post_id, '_lunara_post_related_button_label', true ) );
        $related_url_override= trim( (string) get_post_meta( $post_id, '_lunara_post_related_url_override', true ) );
        $hide_details        = '1' === (string) get_post_meta( $post_id, '_lunara_post_hide_details_card', true );
        $hide_signal         = '1' === (string) get_post_meta( $post_id, '_lunara_post_hide_signal_card', true );
        $hide_related        = '1' === (string) get_post_meta( $post_id, '_lunara_post_hide_related', true );
        $excerpt             = '';
        $hero_image_markup   = '';
        $hero_secondary_markup = '';

        if ( '' !== $standfirst_override ) {
            $excerpt = $standfirst_override;
        } elseif ( ! $hide_standfirst ) {
            $excerpt = has_excerpt( $post_id ) ? get_the_excerpt() : lunara_card_excerpt( $post_id, 40 );
        }

        $related_query  = lunara_get_related_dispatch_posts( $post_id, absint( get_theme_mod( 'lunara_post_related_count', 3 ) ) );
        $archive_url    = lunara_home_dispatch_archive_url();
        $signal_notes   = array(
            'news'      => get_theme_mod( 'lunara_post_signal_news', __( 'Industry movement, festival motion, and awards pressure filed with urgency instead of noise.', 'lunara-film' ) ),
            'reaction'  => get_theme_mod( 'lunara_post_signal_reaction', __( 'A sharper immediate response filed while the conversation is still moving.', 'lunara-film' ) ),
            'essay'     => get_theme_mod( 'lunara_post_signal_essay', __( 'A longer editorial line built for interpretation, connection, and aftershocks.', 'lunara-film' ) ),
            'podcast'   => __( 'An audio-led dispatch translated into a cleaner editorial trail on the page.', 'lunara-film' ),
            'ink'       => __( 'A writerly dispatch lane where the filing itself carries the signature.', 'lunara-film' ),
            'interview' => __( 'A conversation-driven filing shaped to foreground the voice and what matters in it.', 'lunara-film' ),
            'dispatch'  => get_theme_mod( 'lunara_post_signal_dispatch', __( 'A Lunara filing built to move quickly while still keeping the argument intact.', 'lunara-film' ) ),
        );
        $signal_note    = '' !== $signal_note_override
            ? $signal_note_override
            : ( isset( $signal_notes[ $type_slug ] ) ? $signal_notes[ $type_slug ] : $signal_notes['dispatch'] );
        if ( '' !== $type_label_override ) {
            $type_label = $type_label_override;
        }
        if ( '' === $archive_cta_label ) {
            $archive_cta_label = __( 'Open the Journal', 'lunara-film' );
        }
        if ( '' !== $archive_url_override ) {
            $archive_url = $archive_url_override;
        }
        if ( '' !== $category_override ) {
            $category_line = $category_override;
        }
        if ( '' !== $tag_override ) {
            $tag_line = $tag_override;
        }
        if ( '' === $details_kicker ) {
            $details_kicker = get_theme_mod( 'lunara_post_details_kicker_default', __( 'Article Details', 'lunara-film' ) );
        }
        if ( '' === $signal_kicker ) {
            $signal_kicker = get_theme_mod( 'lunara_post_signal_kicker_default', __( 'Signal Context', 'lunara-film' ) );
        }
        if ( '' === $related_kicker ) {
            $related_kicker = get_theme_mod( 'lunara_post_related_kicker_default', __( 'Continue Reading', 'lunara-film' ) );
        }
        if ( '' === $related_heading ) {
            $related_heading = get_theme_mod( 'lunara_post_related_heading_default', __( 'More from the Journal', 'lunara-film' ) );
        }
        if ( '' === $related_copy ) {
            $related_copy = __( 'Stay inside the editorial current and move into the next filing with the same pressure still intact.', 'lunara-film' );
        }
        if ( '' === $related_button ) {
            $related_button = get_theme_mod( 'lunara_post_related_button_default', __( 'Open Archive', 'lunara-film' ) );
        }
        $related_url = '' !== $related_url_override ? $related_url_override : $archive_url;
        if ( ! $hide_hero_media ) {
            if ( '' !== $hero_image_override ) {
                $hero_image_markup = sprintf(
                    '<img class="lunara-journal-single-image" src="%1$s" alt="%2$s" loading="eager" />',
                    esc_url( $hero_image_override ),
                    esc_attr( get_the_title( $post_id ) )
                );
            } elseif ( has_post_thumbnail( $post_id ) ) {
                $hero_image_markup = get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'lunara-journal-single-image', 'loading' => 'eager' ) );
            }
            if ( '' !== $hero_secondary_override ) {
                $hero_secondary_markup = sprintf(
                    '<img class="lunara-journal-single-image" src="%1$s" alt="%2$s" loading="lazy" />',
                    esc_url( $hero_secondary_override ),
                    esc_attr( get_the_title( $post_id ) )
                );
            }
        }
        $use_stacked_hero_media = ! $hide_hero_media && '' !== $hero_secondary_markup && 'single' !== $hero_media_layout;
        $article_classes = array(
            'lunara-journal-single',
            'lunara-dispatch-single',
            'is-' . sanitize_html_class( $type_slug ),
        );
        if ( $hide_hero_media ) {
            $article_classes[] = 'has-no-hero-media';
        }
        if ( $use_stacked_hero_media ) {
            $article_classes[] = 'has-stacked-hero-media';
        }
        if ( $hide_details && $hide_signal ) {
            $article_classes[] = 'has-no-rail-cards';
        }
        ?>
        <main id="primary" class="site-main lunara-archive-page lunara-editorial-single-page">
            <article <?php post_class( implode( ' ', $article_classes ) ); ?>>
                <section class="lunara-home-section lunara-journal-single-hero">
                    <div class="lunara-journal-single-grid">
                        <div class="lunara-journal-single-copy lunara-dispatch-single-copy-shell">
                            <p class="lunara-archive-hero-kicker"><?php echo esc_html( $type_label ); ?></p>
                            <h1 class="lunara-journal-single-title"><?php the_title(); ?></h1>
                            <p class="lunara-dispatch-single-note"><?php echo esc_html( $signal_note ); ?></p>
                            <?php if ( '' !== trim( $excerpt ) ) : ?>
                                <p class="lunara-journal-single-excerpt"><?php echo esc_html( $excerpt ); ?></p>
                            <?php endif; ?>

                            <div class="lunara-journal-single-meta">
                                <span><?php echo esc_html( get_the_date( 'F j, Y', $post_id ) ); ?></span>
                                <?php if ( '' !== $reading_time ) : ?>
                                    <span><?php echo esc_html( $reading_time ); ?></span>
                                <?php endif; ?>
                                <?php if ( '' !== $category_line ) : ?>
                                    <span><?php echo esc_html( $category_line ); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="lunara-journal-taxonomy">
                                <?php if ( '' !== $tag_line ) : ?>
                                    <p class="lunara-journal-taxonomy-line">
                                        <strong><?php esc_html_e( 'Filed under', 'lunara-film' ); ?></strong>
                                        <span><?php echo esc_html( $tag_line ); ?></span>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ( ! $hide_hero_media ) : ?>
                            <div class="lunara-journal-single-media">
                                <?php if ( $use_stacked_hero_media ) : ?>
                                    <div class="lunara-journal-single-media-stack">
                                        <div class="lunara-journal-single-media-pane is-primary">
                                            <?php echo $hero_image_markup; ?>
                                        </div>
                                        <div class="lunara-journal-single-media-pane is-secondary">
                                            <?php echo $hero_secondary_markup; ?>
                                        </div>
                                    </div>
                                <?php elseif ( '' !== $hero_image_markup ) : ?>
                                    <?php echo $hero_image_markup; ?>
                                <?php else : ?>
                                    <div class="lunara-journal-single-placeholder"><?php echo esc_html( $type_label ); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="lunara-home-section lunara-journal-single-body">
                    <div class="lunara-journal-single-body-grid">
                        <div class="lunara-journal-single-content">
                            <?php the_content(); ?>
                        </div>

                        <?php if ( ! $hide_details || ! $hide_signal ) : ?>
                            <aside class="lunara-journal-single-rail" aria-label="<?php esc_attr_e( 'Article details', 'lunara-film' ); ?>">
                                <div class="lunara-dispatch-single-rail-stack">
                                    <?php if ( ! $hide_details ) : ?>
                                        <div class="lunara-journal-rail-card">
                                            <p class="lunara-home-section-kicker"><?php echo esc_html( $details_kicker ); ?></p>
                                            <ul class="lunara-journal-rail-meta">
                                                <li><?php echo esc_html( get_the_date( 'F j, Y', $post_id ) ); ?></li>
                                                <?php if ( '' !== $reading_time ) : ?>
                                                    <li><?php echo esc_html( $reading_time ); ?></li>
                                                <?php endif; ?>
                                                <?php if ( '' !== $category_line ) : ?>
                                                    <li><?php echo esc_html( $category_line ); ?></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ( ! $hide_signal ) : ?>
                                        <div class="lunara-journal-rail-card lunara-dispatch-signal-card">
                                            <p class="lunara-home-section-kicker"><?php echo esc_html( $signal_kicker ); ?></p>
                                            <div class="lunara-dispatch-context-grid">
                                                <p class="lunara-dispatch-context-line">
                                                    <strong><?php esc_html_e( 'Lane', 'lunara-film' ); ?></strong>
                                                    <span><?php echo esc_html( $type_label ); ?></span>
                                                </p>
                                                <?php if ( '' !== $category_line ) : ?>
                                                    <p class="lunara-dispatch-context-line">
                                                        <strong><?php esc_html_e( 'Filed under', 'lunara-film' ); ?></strong>
                                                        <span><?php echo esc_html( $category_line ); ?></span>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ( '' !== $tag_line ) : ?>
                                                    <p class="lunara-dispatch-context-line">
                                                        <strong><?php esc_html_e( 'Threaded through', 'lunara-film' ); ?></strong>
                                                        <span><?php echo esc_html( $tag_line ); ?></span>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <p class="lunara-dispatch-context-copy"><?php echo esc_html( $signal_note ); ?></p>
                                            <a class="lunara-section-link" href="<?php echo esc_url( $archive_url ); ?>">
                                                <?php echo esc_html( $archive_cta_label ); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </aside>
                        <?php endif; ?>
                    </div>
                </section>

                <?php if ( ! $hide_related && $related_query instanceof WP_Query && $related_query->have_posts() ) : ?>
                    <section class="lunara-home-section lunara-editorial-related">
                        <div class="lunara-home-section-head">
                            <div>
                                <p class="lunara-home-section-kicker"><?php echo esc_html( $related_kicker ); ?></p>
                                <h2 class="lunara-section-title"><?php echo esc_html( $related_heading ); ?></h2>
                                <p class="lunara-review-related-copy"><?php echo esc_html( $related_copy ); ?></p>
                            </div>
                            <a class="lunara-section-link" href="<?php echo esc_url( $related_url ); ?>">
                                <?php echo esc_html( $related_button ); ?>
                            </a>
                        </div>

                        <div class="lunara-dispatch-archive-grid">
                            <?php
                            while ( $related_query->have_posts() ) :
                                $related_query->the_post();
                                echo lunara_render_dispatch_archive_card( get_the_ID() );
                            endwhile;
                            wp_reset_postdata();
                            ?>
                        </div>
                    </section>
                <?php endif; ?>
            </article>
        </main>
        <?php
    endwhile;
endif;

get_footer();
