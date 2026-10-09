<?php
/**
 * Lunara Film — Fallback Template
 *
 * @package Lunara_Film
 * @version 3.0.0
 */

get_header();
?>

<main id="primary" class="site-main lunara-archive-page">
    <?php if ( have_posts() ) : ?>
        <section class="lunara-home-section lunara-archive-hero">
            <div class="lunara-container">
                <p class="lunara-archive-hero-kicker"><?php esc_html_e( 'Archive', 'lunara-film' ); ?></p>
                <h1 class="lunara-archive-hero-title"><?php esc_html_e( 'Archive', 'lunara-film' ); ?></h1>
            </div>
        </section>

        <section class="lunara-home-section">
            <div class="lunara-dispatch-archive-grid">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php
                    if ( function_exists( 'lunara_render_dispatch_archive_card' ) ) {
                        echo lunara_render_dispatch_archive_card( get_the_ID() );
                    } else {
                        the_title( '<h2><a href="' . esc_url( get_permalink() ) . '">', '</a></h2>' );
                    }
                    ?>
                <?php endwhile; ?>
            </div>

            <?php
            $pagination = paginate_links();
            if ( $pagination ) :
            ?>
                <div class="lunara-archive-pagination">
                    <?php echo wp_kses_post( $pagination ); ?>
                </div>
            <?php endif; ?>
        </section>
    <?php else : ?>
        <section class="lunara-home-section">
            <div class="lunara-archive-empty">
                <h2><?php esc_html_e( 'Nothing found.', 'lunara-film' ); ?></h2>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
get_footer();
