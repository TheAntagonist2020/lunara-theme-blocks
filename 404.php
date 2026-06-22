<?php
/**
 * 404 template.
 *
 * @package Lunara_Film
 */

get_header();

if ( ! function_exists( 'lunara_404_select_theme_value' ) ) {
    function lunara_404_select_theme_value( $key, $default, $allowed ) {
        $value = sanitize_key( (string) get_theme_mod( $key, $default ) );

        return in_array( $value, $allowed, true ) ? $value : $default;
    }
}

if ( ! function_exists( 'lunara_404_order_reentry_actions' ) ) {
    function lunara_404_order_reentry_actions( $actions, $primary ) {
        if ( empty( $actions ) || ! is_array( $actions ) ) {
            return array();
        }

        $indexed = array();
        foreach ( $actions as $index => $action ) {
            $indexed[] = array(
                'action' => $action,
                'index'  => $index,
            );
        }

        usort(
            $indexed,
            static function ( $left, $right ) use ( $primary ) {
                $left_key  = isset( $left['action']['key'] ) ? (string) $left['action']['key'] : '';
                $right_key = isset( $right['action']['key'] ) ? (string) $right['action']['key'] : '';
                $left_rank = $left_key === $primary ? 0 : 1;
                $right_rank = $right_key === $primary ? 0 : 1;

                if ( $left_rank === $right_rank ) {
                    return $left['index'] <=> $right['index'];
                }

                return $left_rank <=> $right_rank;
            }
        );

        return array_values(
            array_map(
                static function ( $item ) {
                    return $item['action'];
                },
                $indexed
            )
        );
    }
}

$reentry_primary = lunara_404_select_theme_value( 'lunara_utility_reentry_primary', 'home', array( 'home', 'reviews', 'journal', 'oscars', 'search' ) );
$reentry_actions = lunara_404_order_reentry_actions(
    array(
        array(
            'key'   => 'home',
            'label' => __( 'Go Home', 'lunara-film' ),
            'url'   => home_url( '/' ),
        ),
        array(
            'key'   => 'reviews',
            'label' => __( 'Open Reviews', 'lunara-film' ),
            'url'   => home_url( '/reviews/' ),
        ),
        array(
            'key'   => 'journal',
            'label' => __( 'Open Journal', 'lunara-film' ),
            'url'   => home_url( '/journal/' ),
        ),
        array(
            'key'   => 'oscars',
            'label' => __( 'Open The Ledger', 'lunara-film' ),
            'url'   => home_url( '/oscars/' ),
        ),
        array(
            'key'   => 'search',
            'label' => __( 'Search', 'lunara-film' ),
            'url'   => home_url( '/?s=' ),
        ),
    ),
    $reentry_primary
);
?>
<main id="primary" class="site-main lunara-archive-page lunara-404-page lunara-404-page--primary-<?php echo esc_attr( $reentry_primary ); ?>">
    <section class="lunara-home-section lunara-archive-hero">
        <div class="lunara-editorial-archive-hero-shell">
            <div class="lunara-editorial-archive-hero-copy-wrap">
                <p class="lunara-archive-hero-kicker"><?php echo esc_html( get_theme_mod( 'lunara_404_kicker', __( 'Lost Signal', 'lunara-film' ) ) ); ?></p>
                <h1 class="lunara-archive-hero-title"><?php echo esc_html( get_theme_mod( 'lunara_404_title', __( 'This page is not on the record.', 'lunara-film' ) ) ); ?></h1>
                <p class="lunara-archive-hero-copy"><?php echo esc_html( get_theme_mod( 'lunara_404_explanation', __( 'The route you followed does not currently resolve inside Lunara Film. The publication shell is still intact, and the quickest way back is through the front door, the reviews, or the Oscars ledger.', 'lunara-film' ) ) ); ?></p>
            </div>
            <aside class="lunara-editorial-archive-debrief" aria-label="<?php esc_attr_e( 'Recovery options', 'lunara-film' ); ?>">
                <p class="lunara-editorial-archive-debrief-kicker"><?php esc_html_e( 'Recovery Route', 'lunara-film' ); ?></p>
                <ul class="lunara-editorial-archive-debrief-list">
                    <li>
                        <strong><?php echo esc_html( get_theme_mod( 'lunara_404_reset_label', __( 'Best Reset', 'lunara-film' ) ) ); ?></strong>
                        <span><?php echo esc_html( get_theme_mod( 'lunara_404_reset_desc', __( 'Return to the homepage and start fresh.', 'lunara-film' ) ) ); ?></span>
                    </li>
                    <li>
                        <strong><?php echo esc_html( get_theme_mod( 'lunara_404_fastest_label', __( 'Fastest Route', 'lunara-film' ) ) ); ?></strong>
                        <span><?php echo esc_html( get_theme_mod( 'lunara_404_fastest_desc', __( 'Search a title, name, or keyword.', 'lunara-film' ) ) ); ?></span>
                    </li>
                    <li>
                        <strong><?php echo esc_html( get_theme_mod( 'lunara_404_hubs_label', __( 'Stable Hubs', 'lunara-film' ) ) ); ?></strong>
                        <span><?php echo esc_html( get_theme_mod( 'lunara_404_hubs_desc', __( 'Reviews / Journal / Oscar Ledger', 'lunara-film' ) ) ); ?></span>
                    </li>
                </ul>
            </aside>
        </div>
    </section>

    <section class="lunara-home-section lunara-404-shell">
        <div class="lunara-404-panel">
            <div class="lunara-home-section-head">
                <div>
                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Re-enter The Site', 'lunara-film' ); ?></p>
                    <h2 class="lunara-section-title"><?php echo esc_html( get_theme_mod( 'lunara_404_reentry_title', __( 'Choose the cleanest way back in.', 'lunara-film' ) ) ); ?></h2>
                    <p class="lunara-editorial-archive-run-copy"><?php esc_html_e( 'The goal here is recovery without friction: get back to criticism, the Journal, or the Oscar ledger in one move.', 'lunara-film' ); ?></p>
                </div>
            </div>

            <div class="lunara-404-actions">
                <?php foreach ( $reentry_actions as $action ) : ?>
                    <?php
                    $is_primary = isset( $action['key'] ) && $reentry_primary === $action['key'];
                    $classes    = 'lunara-btn lunara-404-action lunara-404-action--' . sanitize_html_class( $action['key'] ?? 'route' );
                    $classes   .= $is_primary ? ' lunara-btn-primary lunara-404-action--primary' : ' lunara-btn-secondary';
                    ?>
                    <a class="<?php echo esc_attr( $classes ); ?>" href="<?php echo esc_url( $action['url'] ); ?>"><?php echo esc_html( $action['label'] ); ?></a>
                <?php endforeach; ?>
            </div>

            <form role="search" method="get" class="lunara-search-form lunara-search-form-shell" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <label class="screen-reader-text" for="lunara-404-search-input"><?php esc_html_e( 'Search for:', 'lunara-film' ); ?></label>
                <input id="lunara-404-search-input" type="search" class="lunara-search-input" placeholder="<?php esc_attr_e( 'Search Lunara Film', 'lunara-film' ); ?>" value="" name="s" />
                <button type="submit" class="lunara-btn lunara-btn-primary"><?php esc_html_e( 'Search', 'lunara-film' ); ?></button>
            </form>
        </div>
    </section>
</main>
<?php
get_footer();
