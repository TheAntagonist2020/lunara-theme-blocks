<?php
/**
 * Lunara Film - Blocksy Parent Theme Integrations
 * Surgically injects bespoke elements without breaking the parent theme.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_head', 'lunara_inject_premium_preloads', 1 );
function lunara_inject_premium_preloads() {
    ?>
    <!-- LUNARA STUDIO: Aggressive Font Preloading -->
    <link rel="preload" href="https://use.typekit.net/your-project-id.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://use.typekit.net/your-project-id.css"></noscript>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php
}

add_action( 'wp_body_open', 'lunara_inject_film_grain' );
function lunara_inject_film_grain() {
    // This adds the microscopic SVG noise overlay immediately after the body tag opens
    echo '<div class="lunara-film-grain" aria-hidden="true"></div>';
}

// We hook BEFORE the main content to start the Barba wrapper.
// This allows the Blocksy Header to remain persistent across page loads!
add_action( 'blocksy:main:before', 'lunara_start_barba_wrapper', 5 );
function lunara_start_barba_wrapper() {
    $namespace = is_front_page() ? 'home' : 'internal';
    ?>
    <!-- LUNARA STUDIO: Seamless Transition Wrapper -->
    <div id="lunara-app-wrapper" data-barba="wrapper">
        <div class="lunara-dynamic-viewport" data-barba="container" data-barba-namespace="<?php echo esc_attr( $namespace ); ?>">
    <?php
}

// We hook AFTER the main content to close the Barba wrapper.
add_action( 'blocksy:main:after', 'lunara_end_barba_wrapper', 99 );
function lunara_end_barba_wrapper() {
    ?>
        </div><!-- /.lunara-dynamic-viewport -->
    </div><!-- /#lunara-app-wrapper -->
    <?php
}
