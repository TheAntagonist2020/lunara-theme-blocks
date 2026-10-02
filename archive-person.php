<?php
/**
 * Talent index — archive template for person entities (Phase 2B).
 */

get_header();

$role  = isset( $_GET['role'] ) ? sanitize_key( wp_unslash( $_GET['role'] ) ) : '';
$base  = get_post_type_archive_link( 'person' );
$total = wp_count_posts( 'person' );
$total = isset( $total->publish ) ? (int) $total->publish : 0;
$roles = array(
    ''         => __( 'Everyone', 'lunara-film' ),
    'director' => __( 'Directors', 'lunara-film' ),
    'actor'    => __( 'Actors', 'lunara-film' ),
    'writer'   => __( 'Writers', 'lunara-film' ),
    'craft'    => __( 'Craft', 'lunara-film' ),
);
?>
<main id="primary" class="site-main lunara-entity-page lunara-entity-archive">
    <header class="lunara-entity-archive-head">
        <p class="lunara-home-section-kicker"><?php esc_html_e( 'The Talent Index', 'lunara-film' ); ?></p>
        <h1 class="lunara-entity-title"><?php esc_html_e( 'The People of the Record', 'lunara-film' ); ?></h1>
        <p class="lunara-entity-archive-copy"><?php echo esc_html( sprintf( __( '%s artists, each with an auto-built filmography and award history.', 'lunara-film' ), number_format_i18n( $total ) ) ); ?></p>
        <nav class="lunara-entity-sort" aria-label="<?php esc_attr_e( 'Filter talent', 'lunara-film' ); ?>">
            <?php foreach ( $roles as $slug => $label ) : ?>
                <a class="<?php echo $role === $slug ? 'is-current' : ''; ?>" href="<?php echo esc_url( '' === $slug ? $base : add_query_arg( 'role', $slug, $base ) ); ?>"><?php echo esc_html( $label ); ?></a>
            <?php endforeach; ?>
        </nav>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="lunara-entity-grid is-people is-archive">
            <?php
            while ( have_posts() ) :
                the_post();
                if ( function_exists( 'lunara_entity_render_person_card' ) ) {
                    echo lunara_entity_render_person_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
            endwhile;
            ?>
        </div>
        <div class="lunara-archive-pagination">
            <?php echo wp_kses_post( paginate_links( array( 'add_args' => '' === $role ? false : array( 'role' => $role ) ) ) ); ?>
        </div>
    <?php else : ?>
        <p class="lunara-entity-archive-copy"><?php esc_html_e( 'The talent record is being built — check back shortly.', 'lunara-film' ); ?></p>
    <?php endif; ?>
</main>
<?php
get_footer();
