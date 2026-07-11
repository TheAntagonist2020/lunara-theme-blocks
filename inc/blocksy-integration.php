<?php
/**
 * Lunara Film - Blocksy Parent Theme Integrations
 * Surgically injects bespoke elements without breaking the parent theme.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// The former "aggressive font preloading" block injected a PLACEHOLDER
// Typekit stylesheet (your-project-id) plus Google Fonts preconnects on
// every page — a dead request and two unused connections. The house
// faces are self-hosted woff2s declared in style.css; nothing external
// to preload.

add_action( 'wp_body_open', 'lunara_inject_film_grain' );
function lunara_inject_film_grain() {
    echo '<div id="lunara-grain" class="is-live" aria-hidden="true"></div><div id="lunara-vignette" aria-hidden="true"></div>';
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
