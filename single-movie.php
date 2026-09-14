<?php
/**
 * Film Dossier — single template for movie entities (Phase 2B).
 */

if ( 'movie' !== get_post_type() ) {
    $fallback = locate_template( array( 'singular.php', 'index.php' ), false, false );
    if ( $fallback ) {
        include $fallback;
        return;
    }
}

get_header();

while ( have_posts() ) :
    the_post();
    $movie_id  = get_the_ID();
    $year      = get_post_meta( $movie_id, 'release_year', true );
    $runtime   = get_post_meta( $movie_id, 'runtime', true );
    $backdrop  = trim( (string) get_post_meta( $movie_id, 'tmdb_backdrop_url', true ) );
    $awards    = function_exists( 'lunara_entity_movie_awards' ) ? lunara_entity_movie_awards( $movie_id ) : array();
    $review_id = function_exists( 'lunara_entity_review_for_movie' ) ? lunara_entity_review_for_movie( $movie_id ) : null;
    $ledgerurl = function_exists( 'lunara_entity_ledger_url' ) ? lunara_entity_ledger_url( $movie_id ) : '';
    $studios   = get_the_terms( $movie_id, 'lunara_studio' );

    $directors = get_post_meta( $movie_id, 'directors', true );
    $directors = array_filter( array_map( 'intval', is_array( $directors ) ? $directors : (array) maybe_unserialize( $directors ) ) );
    $cast      = get_post_meta( $movie_id, 'principal_cast', true );
    $cast      = array_filter( array_map( 'intval', is_array( $cast ) ? $cast : (array) maybe_unserialize( $cast ) ) );
    ?>
    <main id="primary" class="site-main lunara-entity-page lunara-film-dossier">
        <header class="lunara-entity-hero<?php echo '' !== $backdrop ? ' has-backdrop' : ''; ?>">
            <?php if ( '' !== $backdrop ) : ?>
                <div class="lunara-entity-hero-backdrop" style="background-image:url('<?php echo esc_url( $backdrop ); ?>');" aria-hidden="true"></div>
            <?php endif; ?>
            <div class="lunara-entity-hero-overlay" aria-hidden="true"></div>
            <div class="lunara-entity-hero-inner">
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="lunara-entity-hero-poster"><?php the_post_thumbnail( 'large', array( 'fetchpriority' => 'high' ) ); ?></div>
                <?php endif; ?>
                <div class="lunara-entity-hero-copy">
                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Film Dossier', 'lunara-film' ); ?></p>
                    <h1 class="lunara-entity-title"><?php the_title(); ?></h1>
                    <p class="lunara-entity-meta">
                        <?php if ( $year ) : ?><span><?php echo esc_html( $year ); ?></span><?php endif; ?>
                        <?php if ( $runtime ) : ?><span><?php echo esc_html( $runtime ); ?></span><?php endif; ?>
                        <?php if ( $studios && ! is_wp_error( $studios ) ) : ?><span><?php echo esc_html( implode( ' · ', wp_list_pluck( $studios, 'name' ) ) ); ?></span><?php endif; ?>
                    </p>
                    <?php if ( ! empty( $directors ) ) : ?>
                        <p class="lunara-entity-directors">
                            <span class="lunara-entity-label"><?php echo esc_html( _n( 'Directed by', 'Directed by', count( $directors ), 'lunara-film' ) ); ?></span>
                            <?php
                            $links = array();
                            foreach ( $directors as $pid ) {
                                if ( 'publish' === get_post_status( $pid ) ) {
                                    $links[] = '<a href="' . esc_url( get_permalink( $pid ) ) . '">' . esc_html( get_the_title( $pid ) ) . '</a>';
                                }
                            }
                            echo wp_kses_post( implode( ', ', $links ) );
                            ?>
                        </p>
                    <?php endif; ?>
                    <?php if ( $review_id ) : ?>
                        <a class="lunara-entity-review-cta" href="<?php echo esc_url( get_permalink( $review_id ) ); ?>"><?php esc_html_e( 'Read the Lunara Review', 'lunara-film' ); ?> <span aria-hidden="true">&rarr;</span></a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="lunara-entity-body">
            <?php
            if ( '' !== trim( (string) get_the_content() ) ) {
                echo '<div class="lunara-entity-notes">';
                the_content();
                echo '</div>';
            }

            if ( function_exists( 'lunara_entity_render_award_history' ) ) {
                echo lunara_entity_render_award_history( $awards, 'movie' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            ?>

            <?php if ( ! empty( $cast ) ) : ?>
                <section class="lunara-entity-section" aria-label="<?php esc_attr_e( 'Principal cast', 'lunara-film' ); ?>">
                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Principal Cast', 'lunara-film' ); ?></p>
                    <div class="lunara-entity-grid is-people">
                        <?php
                        foreach ( array_slice( $cast, 0, 12 ) as $pid ) {
                            if ( 'publish' === get_post_status( $pid ) && function_exists( 'lunara_entity_render_person_card' ) ) {
                                echo lunara_entity_render_person_card( $pid ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            }
                        }
                        ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ( '' !== $ledgerurl && ! empty( $awards ) ) : ?>
                <p class="lunara-entity-ledger-link"><a href="<?php echo esc_url( $ledgerurl ); ?>"><?php esc_html_e( 'Open the full record in the Oscars Ledger', 'lunara-film' ); ?> <span aria-hidden="true">&rarr;</span></a></p>
            <?php endif; ?>

            <?php
            $wtw = function_exists( 'get_field' ) ? get_field( 'where_to_watch', $movie_id ) : null;
            if ( is_array( $wtw ) && ! empty( $wtw ) ) :
                ?>
                <section class="lunara-entity-section" aria-label="<?php esc_attr_e( 'Where to watch', 'lunara-film' ); ?>">
                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Where to Watch', 'lunara-film' ); ?></p>
                    <div class="lunara-entity-wtw">
                        <?php foreach ( $wtw as $row ) :
                            $provider = isset( $row['provider_name'] ) ? trim( (string) $row['provider_name'] ) : '';
                            $access   = isset( $row['access_type'] ) ? trim( (string) $row['access_type'] ) : '';
                            $url      = isset( $row['url'] ) ? trim( (string) $row['url'] ) : '';
                            if ( '' === $provider ) {
                                continue;
                            }
                            $chip = esc_html( $provider ) . ( $access ? ' <em>' . esc_html( ucfirst( $access ) ) . '</em>' : '' );
                            ?>
                            <?php if ( $url ) : ?>
                                <a class="lunara-entity-wtw-chip" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow"><?php echo wp_kses_post( $chip ); ?></a>
                            <?php else : ?>
                                <span class="lunara-entity-wtw-chip"><?php echo wp_kses_post( $chip ); ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>
    <?php
endwhile;

get_footer();
