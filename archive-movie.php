<?php
/**
 * Film index — archive template for movie entities (Phase 2B).
 */

get_header();

$sort    = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'year';
$base    = get_post_type_archive_link( 'movie' );
$total   = wp_count_posts( 'movie' );
$total   = isset( $total->publish ) ? (int) $total->publish : 0;
?>
<main id="primary" class="site-main lunara-entity-page lunara-entity-archive">
    <header class="lunara-entity-archive-head">
        <p class="lunara-home-section-kicker"><?php esc_html_e( 'The Film Index', 'lunara-film' ); ?></p>
        <h1 class="lunara-entity-title"><?php esc_html_e( 'Every Film in the Record', 'lunara-film' ); ?></h1>
        <p class="lunara-entity-archive-copy"><?php echo esc_html( sprintf( __( '%s films, every one wired to its Academy Award history.', 'lunara-film' ), number_format_i18n( $total ) ) ); ?></p>
        <nav class="lunara-entity-sort" aria-label="<?php esc_attr_e( 'Sort films', 'lunara-film' ); ?>">
            <a class="<?php echo 'year' === $sort ? 'is-current' : ''; ?>" href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'Newest', 'lunara-film' ); ?></a>
            <a class="<?php echo 'az' === $sort ? 'is-current' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'sort', 'az', $base ) ); ?>"><?php esc_html_e( 'A–Z', 'lunara-film' ); ?></a>
        </nav>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="lunara-entity-grid is-archive">
            <?php
            while ( have_posts() ) :
                the_post();
                if ( function_exists( 'lunara_entity_render_film_card' ) ) {
                    echo lunara_entity_render_film_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
            endwhile;
            ?>
        </div>
        <div class="lunara-archive-pagination">
            <?php echo wp_kses_post( paginate_links( array( 'add_args' => 'year' === $sort ? false : array( 'sort' => $sort ) ) ) ); ?>
        </div>
    <?php else : ?>
        <p class="lunara-entity-archive-copy"><?php esc_html_e( 'The film record is being built — check back shortly.', 'lunara-film' ); ?></p>
    <?php endif; ?>
</main>
<?php
get_footer();
