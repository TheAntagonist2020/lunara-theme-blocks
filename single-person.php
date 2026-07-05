<?php
/**
 * Talent Page — single template for person entities (Phase 2B).
 */

if ( 'person' !== get_post_type() ) {
    $fallback = locate_template( array( 'singular.php', 'index.php' ), false, false );
    if ( $fallback ) {
        include $fallback;
        return;
    }
}

get_header();

while ( have_posts() ) :
    the_post();
    $person_id = get_the_ID();
    $awards    = function_exists( 'lunara_entity_person_awards' ) ? lunara_entity_person_awards( $person_id ) : array();
    $lanes     = function_exists( 'lunara_entity_filmography' ) ? lunara_entity_filmography( $person_id ) : array( 'directed' => array(), 'performed' => array() );
    $ledgerurl = function_exists( 'lunara_entity_ledger_url' ) ? lunara_entity_ledger_url( $person_id ) : '';
    $roles     = get_post_meta( $person_id, 'roles', true );
    $roles     = array_filter( array_map( 'strval', is_array( $roles ) ? $roles : (array) maybe_unserialize( $roles ) ) );
    ?>
    <main id="primary" class="site-main lunara-entity-page lunara-talent-page">
        <header class="lunara-entity-hero is-talent">
            <div class="lunara-entity-hero-overlay" aria-hidden="true"></div>
            <div class="lunara-entity-hero-inner">
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="lunara-entity-hero-poster is-portrait"><?php the_post_thumbnail( 'large', array( 'fetchpriority' => 'high' ) ); ?></div>
                <?php endif; ?>
                <div class="lunara-entity-hero-copy">
                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Talent File', 'lunara-film' ); ?></p>
                    <h1 class="lunara-entity-title"><?php the_title(); ?></h1>
                    <?php if ( ! empty( $roles ) ) : ?>
                        <p class="lunara-entity-meta"><span><?php echo esc_html( implode( ' · ', array_map( 'ucfirst', $roles ) ) ); ?></span></p>
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
                echo lunara_entity_render_award_history( $awards, 'person' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }

            foreach ( array(
                'directed'  => __( 'Directed', 'lunara-film' ),
                'performed' => __( 'Performances', 'lunara-film' ),
            ) as $lane => $label ) :
                if ( empty( $lanes[ $lane ] ) ) {
                    continue;
                }
                ?>
                <section class="lunara-entity-section" aria-label="<?php echo esc_attr( $label ); ?>">
                    <p class="lunara-home-section-kicker"><?php echo esc_html( $label ); ?></p>
                    <div class="lunara-entity-grid">
                        <?php
                        foreach ( $lanes[ $lane ] as $mid ) {
                            if ( function_exists( 'lunara_entity_render_film_card' ) ) {
                                echo lunara_entity_render_film_card( $mid ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            }
                        }
                        ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <?php if ( '' !== $ledgerurl && ! empty( $awards ) ) : ?>
                <p class="lunara-entity-ledger-link"><a href="<?php echo esc_url( $ledgerurl ); ?>"><?php esc_html_e( 'Open the full record in the Oscars Ledger', 'lunara-film' ); ?> <span aria-hidden="true">&rarr;</span></a></p>
            <?php endif; ?>
        </div>
    </main>
    <?php
endwhile;

get_footer();
