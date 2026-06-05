<?php
/**
 * Lunara Film — Generic Page Template
 *
 * @package Lunara_Film
 * @version 3.0.0
 */

get_header();
?>

<main id="primary" class="site-main lunara-archive-page">
    <?php while ( have_posts() ) : the_post(); ?>
        <article <?php post_class( 'lunara-page-single' ); ?>>
            <section class="lunara-home-section lunara-archive-hero">
                <div class="lunara-container">
                    <h1 class="lunara-archive-hero-title"><?php the_title(); ?></h1>
                </div>
            </section>
            <section class="lunara-home-section lunara-page-content">
                <div class="lunara-container">
                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>
                </div>
            </section>
        </article>
    <?php endwhile; ?>
</main>

<?php
get_footer();
