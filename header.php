<?php
/**
 * Blocksy-powered header shell for the Lunara child theme.
 *
 * This keeps the child theme in the template hierarchy while handing header
 * structure and builder controls back to the Blocksy parent theme.
 *
 * @package Lunara_Film
 */

?><!doctype html>
<html <?php language_attributes(); ?><?php echo function_exists( 'blocksy_html_attr' ) ? blocksy_html_attr() : ''; ?>>
<head>
    <?php do_action( 'blocksy:head:start' ); ?>

    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <link rel="profile" href="https://gmpg.org/xfn/11">

    <?php wp_head(); ?>
    <style id="lunara-critical-shell-repair">
        .ct-drawer-canvas .ct-panel[inert],
        .ct-drawer-canvas [role="dialog"][inert],
        #search-modal[inert],
        #offcanvas[inert] {
            display: none !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        #main-container > #header {
            position: relative;
            z-index: 20;
            min-height: 96px;
            background: linear-gradient(90deg, rgba(5, 12, 21, 0.96), rgba(10, 23, 38, 0.94));
            border-bottom: 1px solid rgba(201, 169, 97, 0.18);
        }

        #header [data-row] > div,
        #header [data-column] {
            min-height: 0 !important;
        }

        #header [data-row] .ct-container {
            width: min(1180px, calc(100% - 32px));
            margin-inline: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: clamp(16px, 3vw, 32px);
            padding-block: 12px;
        }

        #header [data-items],
        #header [data-column="end"] {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 16px;
            min-width: 0;
        }

        #header .site-branding,
        #header .site-logo-container {
            display: inline-flex !important;
            align-items: center;
            min-width: 0;
        }

        #header .site-logo-container {
            width: auto !important;
            max-width: min(360px, 48vw) !important;
            height: clamp(44px, 6vw, 76px) !important;
            overflow: hidden;
            line-height: 0;
        }

        #header .site-logo-container img {
            width: 100% !important;
            max-width: 100% !important;
            height: 100% !important;
            object-fit: contain !important;
        }

        #header .site-logo-container img.dark-mode-logo {
            display: none !important;
        }

        #header .site-logo-container img.default-logo {
            display: block !important;
        }

        #header .header-menu-1 .menu {
            display: flex !important;
            align-items: center;
            justify-content: flex-end;
            gap: 18px;
            flex-wrap: wrap;
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        #header .header-menu-1 .menu > li {
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        #header .header-menu-1 .ct-menu-link {
            display: inline-flex;
            align-items: center;
            color: #FAFBFC;
            font-size: 0.84rem;
            letter-spacing: 0.08em;
            text-decoration: none;
            text-transform: uppercase;
        }

        #header .header-menu-1 .ct-menu-link:hover,
        #header .header-menu-1 .current-menu-item > .ct-menu-link {
            color: #e0c481;
        }

        #header .lunara-inline-header-search {
            display: flex;
            align-items: center;
            gap: 8px;
            max-width: 360px;
        }

        #header .lunara-inline-header-search input[type="search"] {
            min-width: 180px;
        }

        #header [data-device="desktop"] [data-id="search"] {
            display: none !important;
        }

        @media (max-width: 999.98px) {
            #main-container > #header {
                height: 98px;
                min-height: 98px;
                overflow: hidden;
            }

            body.home #main-container > main#main {
                margin-top: 13px !important;
            }

            #header [data-device="desktop"] {
                display: none !important;
            }

            #header [data-device="mobile"] {
                display: block !important;
            }

            #header [data-row] .ct-container {
                width: min(100% - 24px, 720px);
                padding-block: 10px;
            }

            #header .site-logo-container {
                max-width: min(260px, 68vw) !important;
                height: clamp(42px, 12vw, 58px) !important;
            }

            #header .site-logo-container img.dark-mode-logo {
                display: none !important;
            }

            #header .site-logo-container img.default-logo {
                display: block !important;
            }
        }

        body.home .lunara-front-page,
        body.home main.lunara-front-page {
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            max-width: none !important;
            padding-inline: clamp(16px, 4vw, 48px) !important;
            gap: clamp(48px, 6vw, 86px) !important;
        }

        <?php
        $lunara_home_sections     = function_exists( 'lunara_get_home_section_slugs' ) ? lunara_get_home_section_slugs() : array();
        $lunara_home_order_map    = function_exists( 'lunara_get_home_section_order_map' ) ? lunara_get_home_section_order_map() : array();
        $lunara_home_mobile_order = function_exists( 'lunara_get_home_section_mobile_order_map' ) ? lunara_get_home_section_mobile_order_map() : $lunara_home_order_map;
        ?>
        <?php foreach ( $lunara_home_sections as $lunara_home_section_slug ) : ?>
            body.home .lunara-home-slot-<?php echo esc_html( sanitize_html_class( $lunara_home_section_slug ) ); ?> { order: <?php echo esc_html( isset( $lunara_home_order_map[ $lunara_home_section_slug ] ) ? absint( $lunara_home_order_map[ $lunara_home_section_slug ] ) : 99 ); ?> !important; }
        <?php endforeach; ?>

        @media (max-width: 820px) {
            <?php foreach ( $lunara_home_sections as $lunara_home_section_slug ) : ?>
                body.home .lunara-home-slot-<?php echo esc_html( sanitize_html_class( $lunara_home_section_slug ) ); ?> { order: <?php echo esc_html( isset( $lunara_home_mobile_order[ $lunara_home_section_slug ] ) ? absint( $lunara_home_mobile_order[ $lunara_home_section_slug ] ) : 99 ); ?> !important; }
            <?php endforeach; ?>
        }
    </style>
    <?php do_action( 'blocksy:head:end' ); ?>
</head>

<?php
// Each header owner renders its saved navigation unchanged across routes.
// Search for the Lunara header is owned by Header Command.
ob_start();
if ( function_exists( 'blocksy_output_header' ) ) {
    blocksy_output_header();
}
$global_header = ob_get_clean();

if (
    function_exists( 'lunara_header_takeover_enabled' )
    && ! lunara_header_takeover_enabled()
    && '' === trim( $global_header )
) {
    // Fail open: a public page should never render with no header at all.
    add_filter( 'lunara_header_takeover_enabled', '__return_true', 99 );
}
?>

<body <?php body_class(); ?> <?php echo function_exists( 'blocksy_body_attr' ) ? blocksy_body_attr() : ''; ?>>

<?php
if ( function_exists( 'wp_body_open' ) ) {
    wp_body_open();
}
?>

<div id="main-container">
    <?php
    do_action( 'blocksy:header:before' );

    /*
     * Under the Lunara header takeover the Blocksy header markup is not
     * printed at all — hiding it with CSS proved fragile once WP Rocket's
     * unused-CSS pass stripped the hide rule (the "floating header" seen
     * on Edge). Standalone builds have no Blocksy header to print anyway.
     */
    if ( ! function_exists( 'lunara_header_takeover_enabled' ) || ! lunara_header_takeover_enabled() ) {
        echo $global_header;
    }

    do_action( 'blocksy:header:after' );
    do_action( 'blocksy:content:before' );
    ?>

    <main <?php echo function_exists( 'blocksy_main_attr' ) ? blocksy_main_attr() : 'id="main" class="site-main"'; ?>>
        <?php
        do_action( 'blocksy:content:top' );
        if ( function_exists( 'blocksy_before_current_template' ) ) {
            blocksy_before_current_template();
        }
        ?>
