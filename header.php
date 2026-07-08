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

        body.home .lunara-front-page > .lunara-home-section,
        body.home main.lunara-front-page > .lunara-home-section {
            width: min(1180px, 100%) !important;
            max-width: 1180px !important;
            margin-inline: auto !important;
        }

        body.home .lunara-front-page > .lunara-home-section.lunara-reveal,
        body.home .lunara-front-page .lunara-review-grid-card.lunara-reveal,
        body.home .lunara-front-page .lunara-journal-home-card.lunara-reveal,
        body.home .lunara-front-page .lunara-poster-card.lunara-reveal,
        body.home .lunara-front-page .lunara-ledger-card.lunara-reveal {
            opacity: 1 !important;
            transform: none !important;
            transition: none !important;
        }

        body.post-type-archive-review #main-container > main#main,
        body.post-type-archive-journal #main-container > main#main {
            width: 100% !important;
            max-width: none !important;
        }

        body.post-type-archive-review .lunara-archive-page,
        body.post-type-archive-journal .lunara-archive-page {
            display: flex !important;
            flex-direction: column !important;
            width: min(1180px, calc(100vw - 40px)) !important;
            max-width: 1180px !important;
            margin-inline: auto !important;
            padding: 0 clamp(16px, 4vw, 40px) 80px !important;
            box-sizing: border-box !important;
        }

        body.post-type-archive-review .lunara-archive-hero,
        body.post-type-archive-journal .lunara-archive-hero {
            order: 1 !important;
        }

        body.post-type-archive-review .lunara-archive-page > .lunara-home-section,
        body.post-type-archive-journal .lunara-archive-page > .lunara-home-section {
            margin: 0 0 62px !important;
        }

        body.post-type-archive-review .lunara-review-archive-hero {
            display: grid !important;
            gap: 14px !important;
            padding-top: 76px !important;
            position: relative !important;
            isolation: isolate !important;
            box-sizing: border-box !important;
        }

        body.post-type-archive-review .lunara-review-archive-hero-shell {
            display: grid !important;
            grid-template-columns: minmax(0, 1.3fr) minmax(280px, 0.7fr) !important;
            gap: 28px !important;
            align-items: stretch !important;
            padding: 34px 36px !important;
            border-radius: 28px !important;
            position: relative !important;
            overflow: hidden !important;
            box-sizing: border-box !important;
            min-width: 0 !important;
        }

        body.post-type-archive-review .lunara-review-archive-hero-copy-wrap,
        body.post-type-archive-review .lunara-review-archive-debrief {
            position: relative !important;
            z-index: 1 !important;
            min-width: 0 !important;
        }

        body.post-type-archive-review .lunara-archive-hero-kicker {
            margin: 0 !important;
            color: #e0c481 !important;
            font-size: 0.78rem !important;
            letter-spacing: 0.18em !important;
            text-transform: uppercase !important;
        }

        body.post-type-archive-review .lunara-archive-hero-title {
            margin: 0 !important;
            color: #c9a961 !important;
            font-size: clamp(2.4rem, 4.8vw, 4.6rem) !important;
            line-height: 0.98 !important;
            letter-spacing: -0.03em !important;
        }

        body.post-type-archive-review .lunara-archive-hero-copy {
            max-width: 68ch !important;
            margin: 0 !important;
            color: #FAFBFC !important;
            font-size: 1.05rem !important;
            line-height: 1.78 !important;
        }

        body.post-type-archive-review .lunara-review-archive-debrief {
            display: block !important;
            padding: 22px 24px !important;
            border-radius: 20px !important;
            border: 1px solid rgba(201, 169, 97, 0.18) !important;
            box-sizing: border-box !important;
        }

        body.post-type-archive-review .lunara-review-archive-debrief-kicker {
            margin: 0 0 14px !important;
            color: rgba(224, 196, 129, 0.94) !important;
            letter-spacing: 0.16em !important;
            text-transform: uppercase !important;
            font-size: 0.72rem !important;
        }

        body.post-type-archive-review .lunara-review-archive-debrief-list {
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
            display: grid !important;
        }

        body.post-type-archive-review .lunara-review-archive-debrief-list li {
            display: grid !important;
            grid-template-columns: minmax(116px, 1fr) auto !important;
            gap: 12px !important;
            align-items: start !important;
            padding: 12px 0 !important;
        }

        body.post-type-archive-review .lunara-review-archive-debrief-list strong {
            color: rgba(224, 196, 129, 0.96) !important;
            letter-spacing: 0.14em !important;
            text-transform: uppercase !important;
            font-size: 0.7rem !important;
            font-weight: 700 !important;
        }

        body.post-type-archive-review .lunara-review-archive-debrief-list span {
            color: #FAFBFC !important;
            font-size: 1rem !important;
            text-align: right !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-deskbar {
            order: 2 !important;
        }

        body.post-type-archive-review .lunara-review-archive-toolbar,
        body.post-type-archive-journal .lunara-journal-archive-filters {
            order: 3 !important;
        }

        body.post-type-archive-journal .lunara-editorial-archive-toolbar {
            order: 4 !important;
        }

        body.post-type-archive-review .lunara-review-archive-slot-grid {
            order: 4 !important;
        }

        body.post-type-archive-review .lunara-review-archive-shell {
            display: grid !important;
            gap: 28px !important;
        }

        body.post-type-archive-review .lunara-review-archive-slot-pagination {
            order: 6 !important;
            width: 100% !important;
            margin: 34px auto 0 !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-slot-grid {
            order: 5 !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-slot-pagination {
            order: 6 !important;
            width: 100% !important;
            margin: 34px auto 0 !important;
        }

        body.post-type-archive-journal .lunara-editorial-archive-shell {
            order: 5 !important;
        }

        body.post-type-archive-review .lunara-archive-page .lunara-reveal,
        body.post-type-archive-journal .lunara-archive-page .lunara-reveal {
            opacity: 1 !important;
            transform: none !important;
            transition: none !important;
        }

        @media (max-width: 768px) {
            body.post-type-archive-review .lunara-archive-page,
            body.post-type-archive-journal .lunara-archive-page {
                width: min(100%, calc(100vw - 28px)) !important;
                max-width: calc(100vw - 28px) !important;
                padding-inline: 0 !important;
                overflow-x: hidden !important;
            }

            body.post-type-archive-review .lunara-review-archive-hero-shell {
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 20px !important;
                padding: 26px 20px !important;
                border-radius: 22px !important;
            }

            body.post-type-archive-review .lunara-review-archive-debrief,
            body.post-type-archive-review .lunara-review-archive-rail-shell,
            body.post-type-archive-review .lunara-review-archive-run-head {
                padding: 18px 18px 16px !important;
                border-radius: 18px !important;
            }

            body.post-type-archive-review .lunara-review-archive-debrief-list li {
                grid-template-columns: 1fr !important;
                gap: 8px !important;
            }

            body.post-type-archive-review .lunara-review-archive-debrief-list span {
                text-align: left !important;
            }
        }

        body.home .lunara-latest-reviews-section .lunara-review-grid {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            grid-auto-flow: row !important;
            grid-auto-columns: auto !important;
            gap: clamp(16px, 2vw, 24px) !important;
            justify-content: stretch !important;
            overflow: visible !important;
            padding: 0 !important;
            scroll-snap-type: none !important;
        }

        body.post-type-archive-review .lunara-review-archive-grid,
        body.post-type-archive-review .lunara-review-archive-uniform,
        body.post-type-archive-journal .lunara-journal-archive-grid,
        body.post-type-archive-journal .lunara-review-archive-uniform {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: clamp(20px, 2.4vw, 30px) !important;
        }

        body.home .lunara-journal-home-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: clamp(18px, 2.2vw, 26px) !important;
            align-items: stretch !important;
        }

        body.home .lunara-latest-reviews-section .lunara-review-grid-card,
        body.post-type-archive-review .lunara-review-grid-card,
        body.post-type-archive-journal .lunara-review-grid-card,
        body.home .lunara-journal-home-card,
        body.post-type-archive-journal .lunara-journal-archive-card {
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
            height: 100% !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            border-radius: 18px !important;
            background: rgba(16, 28, 43, 0.88) !important;
        }

        body.home .lunara-latest-reviews-section .lunara-review-grid-link,
        body.post-type-archive-review .lunara-review-grid-link,
        body.post-type-archive-journal .lunara-review-grid-link,
        body.home .lunara-journal-home-card-link,
        body.post-type-archive-journal .lunara-dispatch-archive-link {
            display: grid !important;
            grid-template-rows: auto 1fr !important;
            height: 100% !important;
            color: #FAFBFC !important;
            text-decoration: none !important;
        }

        body.home .lunara-latest-reviews-section .lunara-review-grid-card,
        body.post-type-archive-review .lunara-review-grid-card,
        body.post-type-archive-journal .lunara-review-grid-card {
            contain: layout paint !important;
            min-width: 0 !important;
        }

        body.home .lunara-latest-reviews-section .lunara-review-grid-poster-wrap,
        body.post-type-archive-review .lunara-review-grid-poster-wrap {
            aspect-ratio: 3 / 4 !important;
            width: 100% !important;
            min-height: 0 !important;
            border-radius: 18px 18px 0 0 !important;
            overflow: hidden !important;
            background-color: rgba(255, 255, 255, 0.04) !important;
        }

        body.home .lunara-journal-home-card-media,
        body.post-type-archive-journal .lunara-review-grid-poster-wrap,
        body.post-type-archive-journal .lunara-dispatch-archive-thumb-wrap {
            aspect-ratio: 16 / 10 !important;
            width: 100% !important;
            max-height: none !important;
            border-radius: 18px 18px 0 0 !important;
            overflow: hidden !important;
        }

        body.home .lunara-latest-reviews-section .lunara-review-grid-poster,
        body.home .lunara-latest-reviews-section .lunara-review-grid-poster-wrap img,
        body.post-type-archive-review .lunara-review-grid-poster,
        body.post-type-archive-review .lunara-review-grid-poster-wrap img,
        body.home .lunara-journal-home-card-image,
        body.post-type-archive-journal .lunara-review-grid-poster,
        body.post-type-archive-journal .lunara-review-grid-poster-wrap img,
        body.post-type-archive-journal .lunara-dispatch-archive-thumb,
        body.post-type-archive-journal .lunara-dispatch-archive-thumb-wrap img {
            display: block !important;
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }

        body.home .lunara-latest-reviews-section .lunara-review-grid-copy,
        body.post-type-archive-review .lunara-review-grid-copy,
        body.post-type-archive-journal .lunara-review-grid-copy,
        body.home .lunara-journal-home-card-copy {
            display: grid !important;
            align-content: start !important;
            gap: 10px !important;
            padding: 16px 18px 20px !important;
            box-sizing: border-box !important;
            min-height: 0 !important;
        }

        body.home .lunara-latest-reviews-section .lunara-review-grid-copy,
        body.post-type-archive-review .lunara-review-grid-copy {
            grid-template-rows: auto auto minmax(calc(1.58em * 7), auto) auto !important;
            min-height: clamp(232px, 18vw, 284px) !important;
        }

        body.home .lunara-section-link,
        body.post-type-archive-review .lunara-section-link,
        body.post-type-archive-journal .lunara-section-link,
        body.home .lunara-review-grid-kicker,
        body.post-type-archive-review .lunara-review-grid-kicker,
        body.post-type-archive-journal .lunara-review-grid-kicker,
        body.home .lunara-journal-home-card-kicker {
            color: #e0c481 !important;
            text-decoration: none !important;
        }

        body.home .lunara-review-grid-title,
        body.post-type-archive-review .lunara-review-grid-title,
        body.post-type-archive-journal .lunara-review-grid-title,
        body.home .lunara-journal-home-card-title {
            color: #c9a961 !important;
            font-size: clamp(1rem, 1.35vw, 1.18rem) !important;
            line-height: 1.18 !important;
            min-height: calc(1.18em * 2) !important;
            display: -webkit-box !important;
            -webkit-line-clamp: 2 !important;
            -webkit-box-orient: vertical !important;
            overflow: hidden !important;
            overflow-wrap: anywhere !important;
            text-decoration: none !important;
        }

        body.post-type-archive-journal .lunara-review-grid-title {
            min-height: calc(1.18em * 3) !important;
            -webkit-line-clamp: 3 !important;
        }

        body.home .lunara-journal-home-card-title {
            font-size: clamp(1.12rem, 1.8vw, 1.42rem) !important;
        }

        body.home .lunara-journal-home-card-kicker,
        body.home .lunara-dispatch-type {
            margin: 0 !important;
            color: rgba(224, 196, 129, 0.9) !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.08em !important;
            line-height: 1.2 !important;
            text-transform: uppercase !important;
        }

        body.home .lunara-journal-home-card:not(.is-lead) .lunara-journal-home-card-kicker {
            display: none !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card:not(.is-lead) .lunara-review-grid-kicker {
            display: none !important;
        }

        body.home .lunara-journal-home-card.is-lead {
            border: 1px solid rgba(224, 196, 129, 0.28) !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card.is-lead {
            border: 1px solid rgba(224, 196, 129, 0.28) !important;
        }

        body.home .lunara-journal-home-card.is-lead .lunara-journal-home-card-kicker {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: max-content !important;
            max-width: 100% !important;
            padding: 6px 9px !important;
            border: 1px solid rgba(224, 196, 129, 0.32) !important;
            border-radius: 999px !important;
            background: rgba(224, 196, 129, 0.10) !important;
            color: #f0d795 !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card .lunara-review-grid-kicker,
        body.post-type-archive-journal .lunara-journal-archive-card .lunara-dispatch-type {
            margin: 0 !important;
            color: rgba(224, 196, 129, 0.9) !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.08em !important;
            line-height: 1.2 !important;
            text-transform: uppercase !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card.is-lead .lunara-review-grid-kicker {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: max-content !important;
            max-width: 100% !important;
            padding: 6px 9px !important;
            border: 1px solid rgba(224, 196, 129, 0.32) !important;
            border-radius: 999px !important;
            background: rgba(224, 196, 129, 0.10) !important;
            color: #f0d795 !important;
        }

        body.home .lunara-journal-home-card-excerpt {
            color: rgba(239, 232, 214, 0.78) !important;
            font-size: clamp(0.88rem, 1.1vw, 0.98rem) !important;
            line-height: 1.62 !important;
            margin: 2px 0 0 !important;
            display: -webkit-box !important;
            -webkit-box-orient: vertical !important;
            -webkit-line-clamp: 3 !important;
            overflow: hidden !important;
        }

        body.home .lunara-journal-home-card:not(.is-lead) .lunara-journal-home-card-excerpt {
            -webkit-line-clamp: 2 !important;
        }

        body.home .lunara-journal-home-card-meta {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 10px !important;
            margin-top: auto !important;
            padding-top: 12px !important;
            border-top: 1px solid rgba(201, 169, 97, 0.16) !important;
            color: rgba(239, 232, 214, 0.68) !important;
            font-size: 0.82rem !important;
            line-height: 1.25 !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card-footer {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 10px !important;
            margin-top: auto !important;
            padding-top: 12px !important;
            border-top: 1px solid rgba(201, 169, 97, 0.16) !important;
            color: rgba(239, 232, 214, 0.68) !important;
            font-size: 0.82rem !important;
            line-height: 1.25 !important;
        }

        body.home .lunara-journal-home-card-cta {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 30px !important;
            padding: 6px 9px !important;
            border: 1px solid rgba(224, 196, 129, 0.30) !important;
            border-radius: 999px !important;
            color: #f0d795 !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.04em !important;
            text-transform: uppercase !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card-cta {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 30px !important;
            padding: 6px 9px !important;
            border: 1px solid rgba(224, 196, 129, 0.30) !important;
            border-radius: 999px !important;
            color: #f0d795 !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.04em !important;
            text-transform: uppercase !important;
        }

        @media (min-width: 641px) and (max-width: 820px) {
            body.home .lunara-journal-home-card.is-lead {
                grid-column: 1 / -1 !important;
                width: 100% !important;
                max-width: none !important;
            }

            body.home .lunara-journal-home-card.is-lead .lunara-journal-home-card-link {
                grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.05fr) !important;
                grid-template-rows: minmax(0, 1fr) !important;
            }

            body.home .lunara-journal-home-card.is-lead .lunara-journal-home-card-media {
                height: 100% !important;
                min-height: 280px !important;
                border-radius: 18px 0 0 18px !important;
            }

            body.home .lunara-journal-home-card.is-lead .lunara-journal-home-card-copy {
                padding: 22px 24px !important;
            }
        }

        body.home .lunara-review-grid-excerpt,
        body.post-type-archive-review .lunara-review-grid-excerpt {
            color: #FAFBFC !important;
            font-size: clamp(.86rem, 1.05vw, .96rem) !important;
            line-height: 1.58 !important;
            min-height: calc(1.58em * 7) !important;
            margin: 2px 0 0 !important;
            display: -webkit-box !important;
            -webkit-line-clamp: 7 !important;
            -webkit-box-orient: vertical !important;
            overflow: hidden !important;
        }

        body.post-type-archive-journal .lunara-review-grid-excerpt {
            color: #FAFBFC !important;
            font-size: clamp(.86rem, 1.05vw, .96rem) !important;
            line-height: 1.62 !important;
            min-height: calc(1.62em * 3) !important;
            margin: 2px 0 0 !important;
            display: -webkit-box !important;
            -webkit-line-clamp: 3 !important;
            -webkit-box-orient: vertical !important;
            overflow: hidden !important;
        }

        body.post-type-archive-review .lunara-review-grid-quote {
            color: #e0c481 !important;
            -webkit-line-clamp: 7 !important;
        }

        body.home .lunara-review-grid-quote {
            color: #e0c481 !important;
        }

        body.home .lunara-review-grid-meta,
        body.post-type-archive-review .lunara-review-grid-meta,
        body.post-type-archive-journal .lunara-review-grid-meta,
        body.home .lunara-journal-home-card-excerpt,
        body.home .lunara-journal-home-card-meta {
            color: #A8A8B8 !important;
        }

        body.home .lunara-journal-home-card .lunara-journal-home-card-excerpt {
            color: rgba(239, 232, 214, 0.78) !important;
        }

        body.home .lunara-journal-home-card .lunara-journal-home-card-meta {
            color: rgba(239, 232, 214, 0.68) !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card-footer .lunara-review-grid-meta,
        body.post-type-archive-journal .lunara-journal-archive-card-footer .lunara-review-grid-updated {
            color: rgba(239, 232, 214, 0.68) !important;
        }

        body.home .lunara-journal-home-card .lunara-journal-home-card-cta {
            color: #f0d795 !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card .lunara-journal-archive-card-cta {
            color: #f0d795 !important;
        }

        body.post-type-archive-review .lunara-review-archive-toolbar {
            display: grid !important;
            gap: 16px !important;
            margin: 0 0 clamp(18px, 3vw, 28px) !important;
        }

        body.post-type-archive-review .lunara-review-archive-toolbar-head {
            display: flex !important;
            justify-content: space-between !important;
            align-items: flex-end !important;
            gap: 18px !important;
            margin-bottom: 0 !important;
        }

        body.post-type-archive-review .lunara-review-archive-toolbar .lunara-home-section-kicker {
            margin: 0 0 8px !important;
            color: #e0c481 !important;
            font-size: 0.74rem !important;
            letter-spacing: 0.16em !important;
            text-transform: uppercase !important;
        }

        body.post-type-archive-review .lunara-review-archive-toolbar .lunara-section-title {
            margin: 0 !important;
            color: #c9a961 !important;
            font-size: 2rem !important;
            letter-spacing: 0.1em !important;
            line-height: 1.15 !important;
            text-transform: uppercase !important;
        }

        body.post-type-archive-review .lunara-review-archive-sort {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 12px !important;
            align-items: center !important;
        }

        body.post-type-archive-review .lunara-review-archive-sort-link {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 44px !important;
            padding: 10px 16px !important;
            border: 1px solid rgba(201, 169, 97, 0.32) !important;
            border-radius: 10px !important;
            background: rgba(11, 25, 40, 0.88) !important;
            color: #FAFBFC !important;
            font-size: 0.84rem !important;
            font-weight: 600 !important;
            letter-spacing: 0.08em !important;
            line-height: 1.1 !important;
            text-transform: uppercase !important;
            text-decoration: none !important;
            transition: border-color 0.18s ease, background-color 0.18s ease, color 0.18s ease !important;
        }

        body.post-type-archive-review .lunara-review-archive-sort-link:hover,
        body.post-type-archive-review .lunara-review-archive-sort-link:focus-visible,
        body.post-type-archive-review .lunara-review-archive-sort-link.is-active {
            border-color: rgba(237, 210, 150, 0.76) !important;
            background: rgba(34, 29, 18, 0.92) !important;
            color: #e0c481 !important;
        }

        .lunara-archive-pagination,
        .lunara-archive-pagination .nav-links {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 10px !important;
            align-items: center !important;
        }

        .lunara-archive-pagination .page-numbers {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 42px !important;
            min-height: 42px !important;
            padding: 8px 12px !important;
            border: 1px solid rgba(201, 169, 97, 0.24) !important;
            border-radius: 8px !important;
            background: rgba(15, 29, 46, 0.84) !important;
            color: #FAFBFC !important;
            text-decoration: none !important;
        }

        .lunara-archive-pagination .page-numbers.current {
            border-color: rgba(224, 196, 129, 0.72) !important;
            background: rgba(201, 169, 97, 0.15) !important;
            color: #e0c481 !important;
        }

        body.single-review .lunara-review-single-content,
        body.single-journal .lunara-review-single-content {
            max-width: min(100%, 74ch) !important;
            color: #FAFBFC !important;
            font-family: Georgia, "Times New Roman", serif !important;
            line-height: 1.84 !important;
        }

        body.single-review .lunara-review-single-hero-inner {
            max-width: min(100%, 78ch) !important;
        }

        body.single-review .lunara-review-single-excerpt {
            max-width: 74ch !important;
            color: rgba(250, 251, 252, 0.94) !important;
            font-family: Georgia, "Times New Roman", serif !important;
            line-height: 1.78 !important;
            letter-spacing: 0 !important;
        }

        body.single-review .lunara-review-single-meta {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px 12px !important;
            align-items: center !important;
            color: #A8A8B8 !important;
        }

        body.single-review .lunara-review-single-meta span + span::before {
            content: "/" !important;
            margin-right: 12px !important;
            color: rgba(201, 169, 97, 0.52) !important;
        }

        body.single-review .lunara-review-single-content h2,
        body.single-review .lunara-review-single-content h3,
        body.single-journal .lunara-review-single-content h2,
        body.single-journal .lunara-review-single-content h3 {
            color: #e0c481 !important;
            letter-spacing: 0 !important;
            scroll-margin-top: 132px !important;
        }

        .lunara-reader-toc {
            display: grid !important;
            gap: 12px !important;
            margin: 0 0 30px !important;
            padding: 18px !important;
            border: 1px solid rgba(201, 169, 97, 0.28) !important;
            border-radius: 8px !important;
            background: linear-gradient(180deg, rgba(15, 29, 46, 0.92), rgba(10, 21, 32, 0.96)) !important;
        }

        .lunara-reader-toc-kicker {
            margin: 0 !important;
            color: #c9a961 !important;
            font-size: 0.76rem !important;
            font-weight: 700 !important;
            letter-spacing: 0 !important;
            text-transform: uppercase !important;
        }

        .lunara-reader-toc-links {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 10px !important;
        }

        .lunara-reader-toc-link {
            display: inline-flex !important;
            align-items: center !important;
            min-height: 40px !important;
            padding: 8px 12px !important;
            border: 1px solid rgba(224, 196, 129, 0.28) !important;
            border-radius: 8px !important;
            background: rgba(250, 251, 252, 0.04) !important;
            color: #FAFBFC !important;
            text-decoration: none !important;
        }

        body.single-review .lunara-review-single-rail-actions .lunara-btn,
        body.single-journal .lunara-review-single-rail-actions .lunara-btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            min-height: 44px !important;
            padding: 10px 16px !important;
            border: 1px solid rgba(201, 169, 97, 0.34) !important;
            border-radius: 8px !important;
            background: rgba(201, 169, 97, 0.1) !important;
            color: #e0c481 !important;
            text-align: center !important;
            text-decoration: none !important;
        }

        @media (max-width: 1100px) {
            body.home .lunara-latest-reviews-section .lunara-review-grid,
            body.post-type-archive-review .lunara-review-archive-grid,
            body.post-type-archive-review .lunara-review-archive-uniform,
            body.post-type-archive-journal .lunara-journal-archive-grid,
            body.post-type-archive-journal .lunara-review-archive-uniform {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 760px) {
            body.home,
            body.post-type-archive-journal,
            body.post-type-archive-review,
            body.home #main-container,
            body.post-type-archive-journal #main-container,
            body.post-type-archive-review #main-container {
                width: 100vw !important;
                max-width: 100vw !important;
                overflow-x: hidden !important;
            }

            body.home .lunara-front-page,
            body.post-type-archive-journal .lunara-archive-page,
            body.post-type-archive-review .lunara-archive-page {
                width: 100vw !important;
                max-width: 100vw !important;
                margin-left: calc(50% - 50vw) !important;
                margin-right: calc(50% - 50vw) !important;
                padding-inline: 18px !important;
                box-sizing: border-box !important;
                overflow-x: hidden !important;
            }

            body.post-type-archive-journal .lunara-journal-archive-hero,
            body.post-type-archive-journal .lunara-editorial-archive-toolbar,
            body.post-type-archive-journal .lunara-editorial-archive-toolbar-head {
                width: calc(100vw - 36px) !important;
                max-width: calc(100vw - 36px) !important;
                margin-inline: auto !important;
                overflow: hidden !important;
                text-align: center !important;
                justify-content: center !important;
            }

            body.post-type-archive-journal .lunara-archive-hero-title,
            body.post-type-archive-journal .lunara-section-title {
                width: min(100%, 300px) !important;
                max-width: 300px !important;
                margin-inline: auto !important;
                font-size: clamp(1.72rem, 7.8vw, 2.08rem) !important;
                line-height: 1.08 !important;
                white-space: normal !important;
                overflow-wrap: anywhere !important;
                text-wrap: balance !important;
            }

            body.post-type-archive-journal .lunara-archive-hero-copy {
                width: 100% !important;
                max-width: 300px !important;
                margin-inline: auto !important;
                white-space: normal !important;
                overflow-wrap: anywhere !important;
            }

            body.post-type-archive-journal .lunara-journal-archive-deskbar,
            body.post-type-archive-journal .lunara-journal-archive-filters,
            body.post-type-archive-journal .lunara-archive-sort {
                display: flex !important;
                flex-wrap: wrap !important;
                justify-content: center !important;
                text-align: center !important;
                gap: 10px !important;
            }

            body.post-type-archive-journal .lunara-journal-archive-deskbar {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) !important;
                width: min(100%, 300px) !important;
                max-width: 300px !important;
                margin-inline: auto !important;
                padding: 14px 16px !important;
                box-sizing: border-box !important;
                border-radius: 12px !important;
                text-align: center !important;
                justify-items: center !important;
            }

            body.post-type-archive-journal .lunara-journal-archive-deskbar span {
                display: grid !important;
                gap: 4px !important;
                width: 100% !important;
                max-width: 100% !important;
                white-space: normal !important;
                overflow-wrap: anywhere !important;
            }

            body.post-type-archive-journal .lunara-journal-archive-deskbar strong {
                display: block !important;
                width: 100% !important;
                text-align: center !important;
            }

            body.post-type-archive-journal .lunara-archive-sort {
                width: min(100%, 300px) !important;
                max-width: 300px !important;
                margin-inline: auto !important;
                box-sizing: border-box !important;
            }

            body.post-type-archive-journal .lunara-journal-filter-pill,
            body.post-type-archive-journal .lunara-archive-sort-link {
                flex: 1 1 100% !important;
                width: 100% !important;
                max-width: 300px !important;
                min-height: 52px !important;
                box-sizing: border-box !important;
            }

            body.home .lunara-latest-reviews-section .lunara-review-grid,
            body.home .lunara-journal-home-grid,
            body.post-type-archive-review .lunara-review-archive-grid,
            body.post-type-archive-review .lunara-review-archive-uniform,
            body.post-type-archive-journal .lunara-journal-archive-grid,
            body.post-type-archive-journal .lunara-review-archive-uniform {
                grid-template-columns: minmax(0, 1fr) !important;
                gap: 18px !important;
                justify-items: center !important;
                width: calc(100vw - 36px) !important;
                max-width: calc(100vw - 36px) !important;
                margin-inline: auto !important;
                overflow: hidden !important;
            }

            body.home .lunara-latest-reviews-section .lunara-review-grid-copy,
            body.post-type-archive-review .lunara-review-grid-copy,
            body.post-type-archive-journal .lunara-review-grid-copy,
            body.home .lunara-journal-home-card-copy {
                padding: 18px !important;
            }

            body.home .lunara-latest-reviews-section .lunara-review-grid-card,
            body.home .lunara-journal-home-card,
            body.post-type-archive-review .lunara-review-grid-card,
            body.post-type-archive-journal .lunara-review-grid-card,
            body.post-type-archive-journal .lunara-journal-archive-card {
                width: min(100%, 300px) !important;
                max-width: 300px !important;
                margin-inline: auto !important;
                box-sizing: border-box !important;
            }

            body.home .lunara-latest-reviews-section .lunara-review-grid-title,
            body.home .lunara-journal-home-card-title,
            body.post-type-archive-journal .lunara-review-grid-title {
                font-size: clamp(1.08rem, 5.2vw, 1.34rem) !important;
                line-height: 1.18 !important;
                overflow-wrap: anywhere !important;
                text-wrap: balance !important;
            }

            body.home .lunara-latest-reviews-section .lunara-review-grid-excerpt,
            body.home .lunara-latest-reviews-section .lunara-review-grid-copy,
            body.home .lunara-latest-reviews-section .lunara-review-grid-copy *,
            body.home .lunara-journal-home-card-excerpt,
            body.home .lunara-journal-home-card-copy,
            body.home .lunara-journal-home-card-copy *,
            body.post-type-archive-journal .lunara-review-grid-excerpt,
            body.post-type-archive-journal .lunara-review-grid-copy,
            body.post-type-archive-journal .lunara-review-grid-copy * {
                width: 100% !important;
                max-width: 100% !important;
                margin-left: auto !important;
                margin-right: auto !important;
                box-sizing: border-box !important;
                white-space: normal !important;
                overflow-wrap: anywhere !important;
                word-break: break-word !important;
            }

            body.home .lunara-journal-home-card .lunara-journal-home-card-kicker,
            body.home .lunara-journal-home-card .lunara-dispatch-type,
            body.home .lunara-journal-home-card .lunara-journal-home-card-meta,
            body.home .lunara-journal-home-card .lunara-journal-home-card-meta span,
            body.home .lunara-journal-home-card .lunara-journal-home-card-cta,
            body.post-type-archive-journal .lunara-journal-archive-card .lunara-review-grid-kicker,
            body.post-type-archive-journal .lunara-journal-archive-card .lunara-dispatch-type,
            body.post-type-archive-journal .lunara-journal-archive-card .lunara-journal-archive-card-footer span,
            body.post-type-archive-journal .lunara-journal-archive-card .lunara-journal-archive-card-cta {
                width: auto !important;
                max-width: 100% !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }

            body.home .lunara-journal-home-card .lunara-journal-home-card-meta,
            body.post-type-archive-journal .lunara-journal-archive-card .lunara-journal-archive-card-footer {
                width: 100% !important;
            }
        }

        @media (max-width: 420px) {
            body.home .lunara-latest-reviews-section .lunara-review-grid,
            body.home .lunara-journal-home-grid,
            body.post-type-archive-review .lunara-review-archive-grid,
            body.post-type-archive-review .lunara-review-archive-uniform,
            body.post-type-archive-journal .lunara-journal-archive-grid,
            body.post-type-archive-journal .lunara-review-archive-uniform {
                grid-template-columns: 1fr !important;
            }
        }

        body.home .lunara-oscar-pick-card,
        body.home .lunara-oscar-fact-card {
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
            height: 100% !important;
            overflow: hidden !important;
            border-radius: 18px !important;
            background: rgba(16, 28, 43, 0.88) !important;
            border: 1px solid rgba(201, 169, 97, 0.12) !important;
        }

        body.home .lunara-oscar-fact-card:not(.has-poster) {
            height: auto !important;
            align-self: start !important;
        }

        body.home .lunara-oscar-pick-card-link,
        body.home .lunara-oscar-fact-card-link {
            display: grid !important;
            grid-template-rows: auto 1fr !important;
            height: 100% !important;
            color: #FAFBFC !important;
            text-decoration: none !important;
        }

        body.home .lunara-oscar-fact-card:not(.has-poster) .lunara-oscar-fact-card-link {
            grid-template-rows: 1fr !important;
            min-height: 260px !important;
        }

        body.home .lunara-oscar-pick-card-media,
        body.home .lunara-oscar-fact-card-poster {
            position: relative !important;
            aspect-ratio: 16 / 10 !important;
            width: 100% !important;
            overflow: hidden !important;
            border-radius: 18px 18px 0 0 !important;
            background-color: rgba(255, 255, 255, 0.04) !important;
            background-position: center !important;
            background-size: cover !important;
        }

        body.home .lunara-oscar-pick-card-image,
        body.home .lunara-oscar-fact-card-poster-image,
        body.home .lunara-oscar-pick-card-media img,
        body.home .lunara-oscar-fact-card-poster img {
            display: block !important;
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }

        body.home .lunara-oscar-pick-card-copy,
        body.home .lunara-oscar-fact-card-text {
            display: grid !important;
            align-content: start !important;
            gap: 10px !important;
            padding: 18px !important;
        }

        body.home .lunara-oscar-pick-card-kicker,
        body.home .lunara-oscar-fact-card-kicker,
        body.home .lunara-oscar-pick-card-status {
            color: #e0c481 !important;
            text-decoration: none !important;
        }

        body.home .lunara-oscar-pick-card-title,
        body.home .lunara-oscar-fact-card-title {
            margin: 0 !important;
            color: #c9a961 !important;
            font-size: clamp(1.02rem, 1.45vw, 1.22rem) !important;
            line-height: 1.18 !important;
            overflow-wrap: anywhere !important;
            text-decoration: none !important;
        }

        body.home .lunara-oscar-pick-card-meta,
        body.home .lunara-oscar-fact-card-body,
        body.home .lunara-oscar-fact-card-foot,
        body.home .lunara-oscar-fact-card-attribution {
            color: #A8A8B8 !important;
            text-decoration: none !important;
        }

        body.home .lunara-oscar-pick-card-status {
            position: absolute !important;
            top: 12px !important;
            right: 12px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 30px !important;
            padding: 6px 10px !important;
            border-radius: 999px !important;
            background: rgba(7, 15, 26, 0.78) !important;
            border: 1px solid rgba(237, 210, 150, 0.4) !important;
            font-size: 0.72rem !important;
            letter-spacing: 0.12em !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal {
            display: grid !important;
            gap: clamp(34px, 4.8vw, 58px) !important;
            grid-template-columns: minmax(0, 1fr) !important;
            justify-items: stretch !important;
            width: min(1180px, calc(100vw - 48px)) !important;
            max-width: 1180px !important;
            margin: 0 auto !important;
            padding: clamp(16px, 2.4vw, 26px) 0 clamp(48px, 6vw, 76px) !important;
            min-width: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal a {
            color: #e0c481 !important;
            text-decoration: none !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal a:hover {
            color: #fff2c2 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal > .lunara-home-section {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding-inline: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-hero,
        body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-copy,
        body.lunara-oscars-portal-page .lunara-oscars-portal-actions,
        body.lunara-oscars-portal-page .lunara-oscars-portal-stat-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid,
        body.lunara-oscars-portal-page .lunara-oscars-research-card-grid,
        body.lunara-oscars-portal-page .lunara-ledger-carousel-track {
            max-width: 100% !important;
            min-width: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-container,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-database-landing-shell,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-header,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-hub-section,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-grid,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-card,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-poster-wrap,
        body.lunara-oscars-portal-page .lunara-oscars-portal-reviews,
        body.lunara-oscars-portal-page .lunara-oscars-portal-reviews .lunara-review-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-reviews .lunara-review-grid-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-reviews .lunara-review-grid-link,
        body.lunara-oscars-portal-page .lunara-oscars-portal-reviews .lunara-review-grid-poster-wrap {
            box-sizing: border-box !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-hero {
            min-height: clamp(360px, 42vh, 540px) !important;
            padding: clamp(18px, 2.8vw, 32px) !important;
            border: 1px solid rgba(201, 169, 97, 0.2) !important;
            border-radius: 22px !important;
            background-size: cover !important;
            background-position: center !important;
            box-shadow: 0 28px 70px rgba(0, 0, 0, 0.28) !important;
            overflow: hidden !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 1.18fr) minmax(220px, 330px) !important;
            gap: clamp(20px, 3vw, 34px) !important;
            align-items: center !important;
            min-height: inherit !important;
            padding: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy {
            display: grid !important;
            gap: clamp(13px, 1.7vw, 19px) !important;
            align-content: center !important;
            padding: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
            max-width: 16.4ch !important;
            margin: 0 !important;
            color: #FAFBFC !important;
            font-size: clamp(2.15rem, 4vw, 4.05rem) !important;
            line-height: 1.01 !important;
            letter-spacing: 0 !important;
            text-transform: none !important;
            text-wrap: balance !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-copy {
            max-width: 780px !important;
            margin: 0 !important;
            color: #FAFBFC !important;
            font-size: clamp(1rem, 1.2vw, 1.14rem) !important;
            line-height: 1.72 !important;
        }

        body.lunara-oscars-portal-page .lunara-home-section-kicker,
        body.lunara-oscars-portal-page .lunara-home-section-summary,
        body.lunara-oscars-portal-page .lunara-home-section-title {
            margin-top: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-home-section-title {
            color: #FAFBFC !important;
            font-size: clamp(1.7rem, 2.4vw, 2.5rem) !important;
            line-height: 1.08 !important;
        }

        body.lunara-oscars-portal-page .lunara-home-section-summary,
        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-body,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-secondary,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-line,
        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-context {
            color: rgba(244, 239, 227, 0.78) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-actions,
        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-actions {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 12px !important;
            align-items: center !important;
        }

        body.lunara-oscars-portal-page .lunara-button,
        body.lunara-oscars-portal-page .lunara-button-ghost,
        body.lunara-oscars-portal-page .lunara-btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 42px !important;
            padding: 10px 16px !important;
            border: 1px solid rgba(237, 210, 150, 0.34) !important;
            border-radius: 999px !important;
            background: rgba(7, 15, 26, 0.62) !important;
            color: #e0c481 !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.12em !important;
            line-height: 1.1 !important;
            text-transform: uppercase !important;
            text-decoration: none !important;
        }

        body.lunara-oscars-portal-page .lunara-button:hover,
        body.lunara-oscars-portal-page .lunara-button-ghost:hover,
        body.lunara-oscars-portal-page .lunara-btn:hover {
            border-color: rgba(237, 210, 150, 0.58) !important;
            background: rgba(15, 29, 46, 0.9) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-stat-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid,
        body.lunara-oscars-portal-page .lunara-oscars-research-card-grid,
        body.lunara-oscars-portal-page .lunara-ledger-carousel-track {
            display: grid !important;
            gap: clamp(16px, 2vw, 24px) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-stat-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid,
        body.lunara-oscars-portal-page .lunara-oscars-research-card-grid,
        body.lunara-oscars-portal-page .lunara-ledger-carousel-track {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-stat,
        body.lunara-oscars-portal-page .lunara-oscars-portal-link-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card,
        body.lunara-oscars-portal-page .lunara-oscars-research-card,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
            min-width: 0 !important;
            border: 1px solid rgba(201, 169, 97, 0.16) !important;
            border-radius: 18px !important;
            background:
                radial-gradient(circle at top right, rgba(201, 169, 97, 0.1), transparent 38%),
                linear-gradient(180deg, rgba(16, 28, 43, 0.94), rgba(7, 15, 26, 0.96)) !important;
            box-shadow: 0 20px 46px rgba(0, 0, 0, 0.18) !important;
            overflow: hidden !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card,
        body.lunara-oscars-portal-page .lunara-oscars-research-card,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-copy,
        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy {
            padding: clamp(18px, 2vw, 24px) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
            display: grid !important;
            grid-template-rows: auto 1fr !important;
            gap: 0 !important;
            padding: 0 !important;
            text-decoration: none !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-media,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-poster,
        body.lunara-oscars-portal-page .lunara-oscars-spotlight-poster,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-poster,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-media-link {
            display: block !important;
            width: 100% !important;
            aspect-ratio: 2 / 3 !important;
            background: rgba(255, 255, 255, 0.04) !important;
            background-size: cover !important;
            background-position: center !important;
            overflow: hidden !important;
        }

        body.lunara-oscars-portal-page .lunara-ceremony-winner-media-link,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-poster {
            aspect-ratio: 16 / 10 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster img,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-media img,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-poster img,
        body.lunara-oscars-portal-page .lunara-oscars-spotlight-poster img,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-poster img,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-poster,
        body.lunara-oscars-portal-page .aat-entity-poster {
            display: block !important;
            width: 100% !important;
            height: 100% !important;
            max-width: none !important;
            object-fit: cover !important;
            border: 0 !important;
            border-radius: 0 !important;
            opacity: 1 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy h2,
        body.lunara-oscars-portal-page .lunara-oscars-portal-link-card h3,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card h3,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-card h3,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-film,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-name,
        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-value {
            margin: 0 !important;
            color: #FAFBFC !important;
            font-size: clamp(1.05rem, 1.35vw, 1.28rem) !important;
            line-height: 1.18 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-kicker,
        body.lunara-oscars-portal-page .lunara-oscars-portal-link-kicker,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-category,
        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-label,
        body.lunara-oscars-portal-page .lunara-oscars-portal-stat-label,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-category {
            display: block !important;
            color: #e0c481 !important;
            font-size: 0.72rem !important;
            letter-spacing: 0.12em !important;
            line-height: 1.35 !important;
            text-transform: uppercase !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-stat {
            display: grid !important;
            gap: 5px !important;
            padding: 12px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-stat-value {
            color: #FAFBFC !important;
            font-size: clamp(.88rem, 1.12vw, 1.05rem) !important;
        }

        <?php if ( function_exists( 'lunara_is_oscars_portal_page' ) && lunara_is_oscars_portal_page() ) : ?>
        @media (min-width: 981px) {
            body.lunara-oscars-portal-page .lunara-oscars-portal {
                gap: clamp(30px, 3.7vw, 48px) !important;
                width: min(1120px, calc(100vw - 80px)) !important;
                max-width: 1120px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-hero {
                min-height: clamp(330px, 38vh, 460px) !important;
                padding: clamp(20px, 2.2vw, 30px) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid {
                grid-template-columns: minmax(0, 1fr) minmax(250px, 276px) !important;
                gap: clamp(18px, 2.2vw, 28px) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
                max-width: 15.6ch !important;
                font-size: clamp(2.15rem, 3.45vw, 3.6rem) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-copy {
                max-width: 62ch !important;
                font-size: clamp(.98rem, 1vw, 1.06rem) !important;
                line-height: 1.62 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
                align-items: center !important;
                display: grid !important;
                gap: 12px !important;
                grid-template-columns: 104px minmax(0, 1fr) !important;
                grid-template-rows: none !important;
                justify-self: end !important;
                max-width: 276px !important;
                padding: 12px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
                align-self: start !important;
                aspect-ratio: 2 / 3 !important;
                border-radius: 12px !important;
                max-height: 156px !important;
                width: 104px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy {
                display: grid !important;
                gap: 7px !important;
                min-width: 0 !important;
                padding: 0 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy h2 {
                font-size: clamp(1rem, 1.05vw, 1.12rem) !important;
                line-height: 1.14 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-body {
                font-size: .82rem !important;
                line-height: 1.42 !important;
            }

            body.lunara-oscars-portal-page .lunara-home-section-header {
                align-items: end !important;
                display: grid !important;
                grid-template-columns: minmax(0, 0.76fr) minmax(280px, 0.92fr) !important;
                gap: 18px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-links-section .lunara-home-section-header {
                align-items: end !important;
                grid-template-columns: minmax(280px, 0.72fr) minmax(320px, 1fr) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-links-section .lunara-home-section-summary {
                justify-self: end !important;
                max-width: 46ch !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
                gap: 14px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-link-card {
                align-content: start !important;
                display: grid !important;
                gap: 10px !important;
                min-height: 0 !important;
                max-width: none !important;
                padding: 16px !important;
                width: 100% !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-link-card h3 {
                font-size: clamp(.98rem, 1vw, 1.08rem) !important;
                line-height: 1.15 !important;
                max-width: 14rem !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-link-card p {
                font-size: .84rem !important;
                line-height: 1.42 !important;
            }
        }
        <?php endif; ?>

        @media (max-width: 980px) {
            body.lunara-oscars-portal-page .lunara-oscars-portal {
                width: min(100%, calc(100vw - 28px)) !important;
                grid-template-columns: minmax(0, 1fr) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal > .lunara-home-section {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid {
                grid-template-columns: 1fr !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
                max-width: 20ch !important;
                font-size: clamp(2rem, 4.8vw, 2.75rem) !important;
                line-height: 1.04 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid,
            body.lunara-oscars-portal-page .lunara-oscars-research-card-grid,
            body.lunara-oscars-portal-page .lunara-ledger-carousel-track {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
                display: grid !important;
                grid-template-columns: minmax(150px, 210px) minmax(0, 1fr) !important;
                grid-template-rows: none !important;
                max-width: none !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
                min-height: 220px !important;
                height: 100% !important;
            }
        }

        @media (max-width: 560px) {
            body.lunara-oscars-portal-page .lunara-oscars-portal {
                width: min(100%, calc(100vw - 22px)) !important;
                max-width: calc(100vw - 22px) !important;
                grid-template-columns: minmax(0, 1fr) !important;
                overflow-x: hidden !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal > .lunara-home-section {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-hero {
                min-height: auto !important;
                padding: 22px !important;
                overflow-x: hidden !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-copy {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                overflow-x: hidden !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
                max-width: none !important;
                font-size: clamp(1.56rem, 7.2vw, 2rem) !important;
                line-height: 1.06 !important;
                white-space: normal !important;
                overflow-wrap: anywhere !important;
                word-break: normal !important;
                text-wrap: pretty !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-copy {
                max-width: 100% !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid,
            body.lunara-oscars-portal-page .lunara-oscars-research-card-grid,
            body.lunara-oscars-portal-page .lunara-ledger-carousel-track {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
                grid-template-columns: minmax(96px, 36vw) minmax(0, 1fr) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
                height: auto !important;
                min-height: 0 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-research-shell,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-container,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-database-landing-shell,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-header,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-hub-section,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-grid,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-card,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-poster-wrap,
            body.lunara-oscars-portal-page .lunara-oscars-portal-reviews,
            body.lunara-oscars-portal-page .lunara-oscars-portal-reviews .lunara-review-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-reviews .lunara-review-grid-card,
            body.lunara-oscars-portal-page .lunara-oscars-portal-reviews .lunara-review-grid-link,
            body.lunara-oscars-portal-page .lunara-oscars-portal-reviews .lunara-review-grid-poster-wrap {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                overflow-x: hidden !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-reviews .lunara-review-grid {
                grid-template-columns: 1fr !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-oscar-icon {
                width: min(160px, 48vw) !important;
                max-width: 100% !important;
                height: auto !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-poster,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-poster-wrap img,
            body.lunara-oscars-portal-page .lunara-oscars-portal-reviews .lunara-review-grid-poster,
            body.lunara-oscars-portal-page .lunara-oscars-portal-reviews .lunara-review-grid-poster-wrap img {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
                grid-template-columns: minmax(104px, 36vw) minmax(0, 1fr) !important;
                max-width: 100% !important;
                margin-inline: 0 !important;
                min-width: 0 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
                height: auto !important;
                min-height: 0 !important;
                max-height: none !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-actions {
                display: grid !important;
                grid-template-columns: 1fr !important;
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
            }

            body.lunara-oscars-portal-page .lunara-button,
            body.lunara-oscars-portal-page .lunara-button-ghost,
            body.lunara-oscars-portal-page .lunara-btn {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                box-sizing: border-box !important;
                overflow-wrap: anywhere !important;
            }
        }

        /*
         * Oscars portal poster scale ceiling.
         * Keep /oscars/ as a fast ledger front door, not a poster wall.
         */
        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
            display: grid !important;
            grid-template-columns: minmax(118px, 150px) minmax(0, 1fr) !important;
            gap: 0 !important;
            max-width: min(100%, 520px) !important;
            padding: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
            align-self: stretch !important;
            aspect-ratio: 2 / 3 !important;
            max-height: 230px !important;
            min-height: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy {
            align-content: center !important;
            padding: 16px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-body {
            font-size: .82rem !important;
            line-height: 1.38 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid,
        body.lunara-oscars-portal-page .lunara-ceremony-winners-grid {
            grid-template-columns: repeat(auto-fill, minmax(118px, 148px)) !important;
            justify-content: start !important;
            gap: 14px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-card,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-card {
            max-width: 148px !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-spotlight-poster,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-media {
            max-height: 214px !important;
        }

        body.lunara-oscars-portal-page .lunara-ceremony-winner-media-link,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-poster {
            max-height: 92px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-spotlight-card-copy,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-copy,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-copy {
            padding: 10px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card h3,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-card h3,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-film,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-name {
            font-size: .82rem !important;
            line-height: 1.18 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-secondary,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-line,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-meta {
            font-size: .72rem !important;
            line-height: 1.28 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-grid,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-grid,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-grid.is-hero-latest,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-grid.is-marquee-latest {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(118px, 148px)) !important;
            justify-content: start !important;
            gap: 14px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-card,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-card {
            display: grid !important;
            align-content: start !important;
            max-width: 148px !important;
            min-height: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-link {
            display: grid !important;
            gap: 0 !important;
            min-width: 0 !important;
            text-decoration: none !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-poster-wrap {
            aspect-ratio: 2 / 3 !important;
            border-radius: 12px 12px 0 0 !important;
            max-height: 214px !important;
            min-height: 0 !important;
            overflow: hidden !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-media {
            aspect-ratio: 16 / 10 !important;
            border-radius: 12px 12px 0 0 !important;
            max-height: 92px !important;
            min-height: 0 !important;
            overflow: hidden !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-poster,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-poster-wrap img,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-media img,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-photo {
            display: block !important;
            height: 100% !important;
            max-width: none !important;
            object-fit: cover !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-title,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-title {
            font-size: .82rem !important;
            line-height: 1.18 !important;
            margin: 0 !important;
            padding: 10px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-meta,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-meta {
            display: none !important;
        }

        @media (max-width: 980px) {
            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
                grid-template-columns: minmax(112px, 150px) minmax(0, 1fr) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
                max-height: 216px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid,
            body.lunara-oscars-portal-page .lunara-ceremony-winners-grid {
                grid-template-columns: repeat(auto-fill, minmax(108px, 136px)) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card,
            body.lunara-oscars-portal-page .lunara-oscars-portal-title-card,
            body.lunara-oscars-portal-page .lunara-ceremony-winner-card,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-card,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-card {
                max-width: 136px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-grid,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-grid,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-grid.is-hero-latest,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-grid.is-marquee-latest {
                grid-template-columns: repeat(auto-fill, minmax(108px, 136px)) !important;
            }
        }

        @media (max-width: 560px) {
            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
                grid-template-columns: 92px minmax(0, 1fr) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
                max-height: 138px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy {
                padding: 12px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-body {
                display: none !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid,
            body.lunara-oscars-portal-page .lunara-ceremony-winners-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 9px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card,
            body.lunara-oscars-portal-page .lunara-oscars-portal-title-card,
            body.lunara-oscars-portal-page .lunara-ceremony-winner-card,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-card,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-card {
                max-width: none !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-grid,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-grid,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-grid.is-hero-latest,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-grid.is-marquee-latest {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 9px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-spotlight-poster,
            body.lunara-oscars-portal-page .lunara-oscars-portal-title-media,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-poster-wrap {
                max-height: 154px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-media {
                max-height: 70px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-spotlight-card-copy,
            body.lunara-oscars-portal-page .lunara-oscars-portal-title-copy,
            body.lunara-oscars-portal-page .lunara-ceremony-winner-copy {
                padding: 8px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-secondary,
            body.lunara-oscars-portal-page .lunara-oscars-portal-title-line,
            body.lunara-oscars-portal-page .lunara-ceremony-winner-meta {
                display: none !important;
            }
        }

        body.aat-shell-page {
            background: #08131f !important;
            color: #FAFBFC !important;
        }

        body.aat-shell-page .site-main {
            padding-block: clamp(22px, 4vw, 54px) !important;
        }

        body.aat-shell-page .aat-container {
            width: min(1320px, calc(100vw - 36px)) !important;
            max-width: 1320px !important;
            margin: 0 auto !important;
            padding: clamp(22px, 3vw, 42px) !important;
            border: 1px solid rgba(201, 169, 97, 0.18) !important;
            border-radius: 22px !important;
            background:
                radial-gradient(circle at 14% 0%, rgba(201, 169, 97, 0.12), transparent 28%),
                linear-gradient(145deg, rgba(12, 25, 40, 0.96), rgba(7, 15, 26, 0.98)) !important;
            box-shadow: 0 28px 70px rgba(0, 0, 0, 0.32) !important;
            overflow: hidden !important;
        }

        body.aat-shell-page .aat-hub-breadcrumbs,
        body.aat-shell-page .aat-entity-breadcrumbs {
            display: flex !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
            margin: 0 0 24px !important;
            color: rgba(244, 239, 227, 0.72) !important;
            font-size: 0.78rem !important;
            letter-spacing: 0.08em !important;
            text-transform: uppercase !important;
        }

        body.aat-shell-page .aat-hub-page a,
        body.aat-shell-page .aat-entity-page a {
            color: #e0c481 !important;
            text-decoration: none !important;
        }

        body.aat-shell-page .aat-hub-page a:hover,
        body.aat-shell-page .aat-entity-page a:hover {
            color: #fff2c2 !important;
        }

        body.aat-shell-page .aat-hub-header,
        body.aat-shell-page .aat-entity-header {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            align-items: end !important;
            gap: clamp(18px, 3vw, 36px) !important;
            margin-bottom: clamp(28px, 4vw, 48px) !important;
            padding-bottom: clamp(18px, 2vw, 28px) !important;
            border-bottom: 1px solid rgba(201, 169, 97, 0.2) !important;
        }

        body.aat-shell-page .aat-hub-title,
        body.aat-shell-page .aat-entity-title,
        body.aat-shell-page .aat-hub-section h2,
        body.aat-shell-page .aat-section-title {
            margin: 0 !important;
            color: #FAFBFC !important;
            line-height: 1.08 !important;
            letter-spacing: 0 !important;
        }

        body.aat-shell-page .aat-hub-title,
        body.aat-shell-page .aat-entity-title {
            font-size: clamp(2.15rem, 5vw, 4.5rem) !important;
        }

        body.aat-shell-page .aat-hub-subtitle,
        body.aat-shell-page .aat-entity-subtitle,
        body.aat-shell-page .aat-hub-copy,
        body.aat-shell-page .aat-winner-circle-meta,
        body.aat-shell-page .aat-hub-spotlight-meta {
            color: rgba(244, 239, 227, 0.78) !important;
        }

        body.aat-shell-page .aat-hub-actions,
        body.aat-shell-page .aat-entity-actions,
        body.aat-shell-page .aat-winner-circle-actions {
            display: flex !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            gap: 10px !important;
        }

        body.aat-shell-page .aat-btn,
        body.aat-shell-page .aat-hub-card-action,
        body.aat-shell-page .aat-winner-circle-action {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 40px !important;
            padding: 10px 16px !important;
            border: 1px solid rgba(201, 169, 97, 0.28) !important;
            border-radius: 999px !important;
            background: rgba(11, 21, 35, 0.88) !important;
            color: #e0c481 !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.1em !important;
            line-height: 1 !important;
            text-transform: uppercase !important;
            text-decoration: none !important;
        }

        body.aat-shell-page .aat-btn:hover,
        body.aat-shell-page .aat-hub-card-action:hover,
        body.aat-shell-page .aat-winner-circle-action:hover {
            border-color: rgba(237, 210, 150, 0.55) !important;
            background: rgba(201, 169, 97, 0.14) !important;
        }

        body.aat-shell-page .aat-hub-section,
        body.aat-shell-page .aat-entity-section {
            margin-top: clamp(28px, 5vw, 60px) !important;
        }

        body.aat-shell-page .aat-ceremony-marquee {
            display: grid !important;
            grid-template-columns: minmax(0, 0.9fr) minmax(420px, 1.1fr) !important;
            gap: clamp(18px, 3vw, 34px) !important;
            align-items: stretch !important;
            padding: clamp(18px, 2.6vw, 34px) !important;
            border: 1px solid rgba(201, 169, 97, 0.16) !important;
            border-radius: 20px !important;
            background: rgba(4, 12, 22, 0.5) !important;
        }

        body.aat-shell-page .aat-ceremony-marquee-stack,
        body.aat-shell-page .aat-hub-chip-stack {
            display: grid !important;
            gap: 16px !important;
            min-width: 0 !important;
        }

        body.aat-shell-page .aat-hub-grid,
        body.aat-shell-page .aat-winner-circle-grid,
        body.aat-shell-page .aat-filmography-grid,
        body.aat-shell-page .aat-related-reviews-grid,
        body.aat-shell-page .aat-best-picture-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)) !important;
            gap: clamp(16px, 2vw, 26px) !important;
            align-items: stretch !important;
        }

        body.aat-shell-page .aat-winner-circle-grid.is-hero-latest,
        body.aat-shell-page .aat-winner-circle-grid.is-marquee-latest {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)) !important;
        }

        body.aat-shell-page .aat-hub-card,
        body.aat-shell-page .aat-hub-spotlight-card,
        body.aat-shell-page .aat-hub-chip-rich,
        body.aat-shell-page .aat-winner-circle-card,
        body.aat-shell-page .aat-filmography-card,
        body.aat-shell-page .aat-related-review-card,
        body.aat-shell-page .aat-entity-status-banner,
        body.aat-shell-page .aat-history-item,
        body.aat-shell-page .aat-timeline-card {
            min-width: 0 !important;
            border: 1px solid rgba(201, 169, 97, 0.14) !important;
            border-radius: 18px !important;
            background: rgba(12, 25, 40, 0.78) !important;
            box-shadow: 0 18px 38px rgba(0, 0, 0, 0.2) !important;
            overflow: hidden !important;
        }

        body.aat-shell-page .aat-hub-spotlight-card {
            display: grid !important;
            grid-template-columns: minmax(132px, 190px) minmax(0, 1fr) !important;
            gap: 18px !important;
            padding: 16px !important;
            align-items: stretch !important;
        }

        body.aat-shell-page .aat-hub-chip-rich {
            display: grid !important;
            gap: 6px !important;
            padding: 14px 16px !important;
        }

        body.aat-shell-page .aat-winner-circle-card {
            display: grid !important;
            grid-template-rows: auto auto auto 1fr !important;
            gap: 14px !important;
            padding: 18px !important;
        }

        body.aat-shell-page .aat-winner-circle-top {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 12px !important;
        }

        body.aat-shell-page .aat-hub-spotlight-media,
        body.aat-shell-page .aat-winner-circle-media,
        body.aat-shell-page .aat-filmography-poster-wrap,
        body.aat-shell-page .aat-related-review-media,
        body.aat-shell-page .aat-entity-poster-wrap {
            position: relative !important;
            width: 100% !important;
            min-height: 0 !important;
            overflow: hidden !important;
            border-radius: 14px !important;
            background: rgba(255, 255, 255, 0.04) !important;
        }

        body.aat-shell-page .aat-hub-spotlight-media,
        body.aat-shell-page .aat-entity-poster-wrap,
        body.aat-shell-page .aat-filmography-poster-wrap {
            aspect-ratio: 2 / 3 !important;
        }

        body.aat-shell-page .aat-winner-circle-media,
        body.aat-shell-page .aat-related-review-media {
            aspect-ratio: 16 / 10 !important;
        }

        body.aat-shell-page .aat-hub-spotlight-media img,
        body.aat-shell-page .aat-winner-circle-media img,
        body.aat-shell-page .aat-winner-circle-photo,
        body.aat-shell-page .aat-filmography-poster,
        body.aat-shell-page .aat-filmography-poster-wrap img,
        body.aat-shell-page .aat-related-review-image,
        body.aat-shell-page .aat-related-review-media img,
        body.aat-shell-page .aat-entity-poster-wrap img,
        body.aat-shell-page .aat-entity-poster {
            display: block !important;
            width: 100% !important;
            max-width: none !important;
            height: 100% !important;
            object-fit: cover !important;
            opacity: 1 !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }

        body.aat-shell-page .aat-entity-hero {
            display: grid !important;
            grid-template-columns: minmax(180px, 260px) minmax(0, 1fr) !important;
            gap: clamp(20px, 3vw, 38px) !important;
            align-items: start !important;
        }

        body.aat-shell-page .aat-entity-poster-wrap {
            max-width: 260px !important;
        }

        body.aat-shell-page .aat-winner-circle-grid,
        body.aat-shell-page .aat-filmography-grid,
        body.aat-shell-page .aat-related-reviews-grid,
        body.aat-shell-page .aat-best-picture-grid {
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)) !important;
            gap: 14px !important;
        }

        body.aat-shell-page .aat-winner-circle-grid.is-hero-latest,
        body.aat-shell-page .aat-winner-circle-grid.is-marquee-latest {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) !important;
        }

        body.aat-shell-page .aat-winner-circle-card,
        body.aat-shell-page .aat-winner-circle-card.is-hero-latest,
        body.aat-shell-page .aat-winner-circle-card.is-marquee-latest {
            gap: 10px !important;
            min-height: 0 !important;
            padding: 16px !important;
        }

        body.aat-shell-page .aat-winner-circle-media,
        body.aat-shell-page .aat-winner-circle-card.is-hero-latest .aat-winner-circle-media,
        body.aat-shell-page .aat-winner-circle-card.is-marquee-latest .aat-winner-circle-media {
            aspect-ratio: 2 / 3 !important;
            margin: 0 auto 4px !important;
            max-width: 168px !important;
            min-height: 0 !important;
            width: min(100%, 168px) !important;
        }

        body.aat-shell-page .aat-entity-hero {
            grid-template-columns: minmax(150px, 210px) minmax(0, 1fr) !important;
            gap: clamp(16px, 2vw, 24px) !important;
        }

        body.aat-shell-page .aat-entity-poster-wrap {
            max-width: 210px !important;
        }

        body.aat-shell-page .aat-entity-poster-wrap img,
        body.aat-shell-page .aat-entity-poster {
            max-height: 320px !important;
        }

        body.aat-shell-page .aat-stats-bar.aat-entity-stats {
            display: grid !important;
            gap: 10px !important;
            grid-template-columns: repeat(auto-fit, minmax(118px, 1fr)) !important;
            margin: 16px 0 30px !important;
        }

        body.aat-shell-page .aat-stats-bar.aat-entity-stats .aat-stat {
            align-content: start !important;
            background: rgba(255, 255, 255, 0.035) !important;
            border: 1px solid rgba(201, 169, 97, 0.14) !important;
            border-radius: 12px !important;
            display: grid !important;
            gap: 4px !important;
            min-width: 0 !important;
            padding: 11px 12px !important;
            text-align: left !important;
        }

        body.aat-shell-page .aat-stats-bar.aat-entity-stats .aat-stat-number,
        body.aat-shell-page .aat-stats-bar.aat-entity-stats .aat-stat-label {
            display: block !important;
        }

        body.aat-shell-page .aat-stats-bar.aat-entity-stats .aat-stat-number {
            font-size: 1.18rem !important;
            line-height: 1.1 !important;
        }

        body.aat-shell-page .aat-stats-bar.aat-entity-stats .aat-stat-label {
            font-size: .68rem !important;
            line-height: 1.2 !important;
        }

        body.aat-shell-page .aat-winner-badge,
        body.aat-shell-page .aat-hub-kicker,
        body.aat-shell-page .aat-hub-chip,
        body.aat-shell-page .aat-winner-circle-category {
            color: #e0c481 !important;
            letter-spacing: 0.08em !important;
            text-transform: uppercase !important;
            text-decoration: none !important;
        }

        body.aat-shell-page .aat-hub-chip,
        body.aat-shell-page .aat-winner-badge,
        body.aat-shell-page .aat-winner-circle-category {
            display: inline-flex !important;
            width: fit-content !important;
            align-items: center !important;
            min-height: 30px !important;
            padding: 6px 10px !important;
            border: 1px solid rgba(201, 169, 97, 0.24) !important;
            border-radius: 999px !important;
            background: rgba(5, 13, 24, 0.62) !important;
            font-size: 0.72rem !important;
            line-height: 1 !important;
        }

        body.aat-shell-page .aat-hub-spotlight-title,
        body.aat-shell-page .aat-winner-circle-title,
        body.aat-shell-page .aat-filmography-title,
        body.aat-shell-page .aat-related-review-title {
            margin: 0 !important;
            color: #FAFBFC !important;
            line-height: 1.16 !important;
            overflow-wrap: anywhere !important;
        }

        body.aat-shell-page .aat-hub-spotlight-body,
        body.aat-shell-page .aat-filmography-body,
        body.aat-shell-page .aat-related-review-body {
            display: grid !important;
            align-content: start !important;
            gap: 10px !important;
            padding: 16px !important;
        }

        @media (max-width: 900px) {
            body.aat-shell-page .aat-hub-header,
            body.aat-shell-page .aat-entity-header,
            body.aat-shell-page .aat-ceremony-marquee,
            body.aat-shell-page .aat-entity-hero {
                grid-template-columns: 1fr !important;
            }

            body.aat-shell-page .aat-hub-actions,
            body.aat-shell-page .aat-entity-actions {
                justify-content: flex-start !important;
            }

            body.aat-shell-page .aat-entity-poster-wrap {
                justify-self: start !important;
                max-width: 146px !important;
            }

            body.aat-shell-page .aat-entity-poster-wrap img,
            body.aat-shell-page .aat-entity-poster {
                max-height: 220px !important;
            }
        }

        @media (max-width: 560px) {
            body.aat-shell-page .site-main {
                padding-block: 16px !important;
            }

            body.aat-shell-page .aat-container {
                width: calc(100vw - 22px) !important;
                padding: 16px !important;
                border-radius: 16px !important;
            }

            body.aat-shell-page .aat-hub-grid,
            body.aat-shell-page .aat-winner-circle-grid,
            body.aat-shell-page .aat-filmography-grid,
            body.aat-shell-page .aat-related-reviews-grid,
            body.aat-shell-page .aat-best-picture-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            body.aat-shell-page .aat-hub-spotlight-card {
                grid-template-columns: 110px minmax(0, 1fr) !important;
                padding: 12px !important;
            }

            body.aat-shell-page .aat-winner-circle-grid,
            body.aat-shell-page .aat-winner-circle-grid.is-hero-latest,
            body.aat-shell-page .aat-winner-circle-grid.is-marquee-latest,
            body.aat-shell-page .aat-related-reviews-grid {
                grid-template-columns: 1fr !important;
            }

            body.aat-shell-page .aat-winner-circle-media,
            body.aat-shell-page .aat-winner-circle-card.is-hero-latest .aat-winner-circle-media,
            body.aat-shell-page .aat-winner-circle-card.is-marquee-latest .aat-winner-circle-media {
                max-width: 132px !important;
                width: 132px !important;
            }

            body.aat-shell-page .aat-entity-poster-wrap {
                max-width: 132px !important;
            }
        }

        /*
         * Lunara Clean Power design pass.
         * Public surfaces use the canonical Lunara type tokens; the fallbacks
         * stay literary and compact when the licensed faces are unavailable.
         */
        body:not(.wp-admin) {
            font-family: var(--lunara-font-body, Georgia, "Times New Roman", serif) !important;
            letter-spacing: 0 !important;
            text-rendering: optimizeLegibility !important;
        }

        body:not(.wp-admin) button,
        body:not(.wp-admin) input,
        body:not(.wp-admin) select,
        body:not(.wp-admin) textarea,
        body:not(.wp-admin) .ct-header,
        body:not(.wp-admin) .site-main,
        body:not(.wp-admin) .entry-content {
            font-family: var(--lunara-font-body, Georgia, "Times New Roman", serif) !important;
            letter-spacing: 0 !important;
        }

        body:not(.wp-admin) h1,
        body:not(.wp-admin) h2,
        body:not(.wp-admin) h3,
        body:not(.wp-admin) h4,
        body:not(.wp-admin) .entry-title,
        body:not(.wp-admin) .page-title,
        body:not(.wp-admin) .lunara-home-hero-title,
        body:not(.wp-admin) .lunara-home-section-title,
        body:not(.wp-admin) .lunara-archive-hero-title,
        body:not(.wp-admin) .lunara-review-single-title,
        body:not(.wp-admin) .aat-entity-title,
        body:not(.wp-admin) .aat-section-title,
        body:not(.wp-admin) .aat-category-history-title {
            font-family: var(--lunara-font-display, Georgia, "Times New Roman", serif) !important;
            font-weight: 700 !important;
            letter-spacing: 0 !important;
            text-wrap: balance !important;
        }

        body:not(.wp-admin) .lunara-home-front-desk-title,
        body:not(.wp-admin) .lunara-home-masthead-logo-fallback {
            font-family: var(--lunara-font-signature, var(--lunara-font-display, Georgia, "Times New Roman", serif)) !important;
            font-weight: 500 !important;
        }

        body:not(.wp-admin) .lunara-home-front-desk-signal strong,
        body:not(.wp-admin) .lunara-home-masthead-route strong {
            font-family: var(--lunara-font-display, Georgia, "Times New Roman", serif) !important;
        }

        body:not(.wp-admin) a:not(.button):not(.wp-block-button__link):not(.lunara-button):not(.lunara-button-ghost):not(.lunara-btn):not(.aat-btn) {
            color: #e0c481 !important;
            text-decoration-color: rgba(224, 196, 129, 0.54) !important;
            text-decoration-thickness: 1px !important;
            text-underline-offset: 0.18em !important;
        }

        body:not(.wp-admin) a:not(.button):not(.wp-block-button__link):not(.lunara-button):not(.lunara-button-ghost):not(.lunara-btn):not(.aat-btn):hover {
            color: #f4e1a3 !important;
            text-decoration-color: rgba(244, 225, 163, 0.82) !important;
        }

        body:not(.wp-admin) .lunara-button,
        body:not(.wp-admin) .lunara-button-ghost,
        body:not(.wp-admin) .lunara-btn,
        body:not(.wp-admin) .aat-btn,
        body:not(.wp-admin) .lunara-journal-filter-pill,
        body:not(.wp-admin) .lunara-archive-sort a,
        body:not(.wp-admin) .aat-decade-pill,
        body:not(.wp-admin) .aat-crossroad-pill,
        body:not(.wp-admin) .aat-winner-circle-action {
            font-family: var(--lunara-font-label, "Tiempos Text", "Segoe UI", Arial, sans-serif) !important;
            letter-spacing: 0.09em !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal {
            gap: clamp(26px, 4vw, 44px) !important;
            width: min(1120px, calc(100vw - 42px)) !important;
            max-width: 1120px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-hero {
            min-height: clamp(300px, 36vh, 430px) !important;
            padding: clamp(16px, 2.2vw, 26px) !important;
            border-radius: 18px !important;
            box-shadow: 0 22px 54px rgba(0, 0, 0, 0.24) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid {
            grid-template-columns: minmax(0, 1.24fr) minmax(180px, 270px) !important;
            gap: clamp(16px, 2.4vw, 28px) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
            max-width: 14.4ch !important;
            font-size: clamp(1.9rem, 3.35vw, 3.15rem) !important;
            line-height: 1.04 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-copy {
            max-width: 60ch !important;
            font-size: clamp(.94rem, 1.05vw, 1.02rem) !important;
            line-height: 1.52 !important;
        }

        body.lunara-oscars-portal-page .lunara-home-section-title {
            max-width: 18ch !important;
            font-size: clamp(1.38rem, 2vw, 1.95rem) !important;
            line-height: 1.08 !important;
        }

        body.lunara-oscars-portal-page .lunara-home-section-summary {
            max-width: 56ch !important;
            font-size: .92rem !important;
            line-height: 1.48 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-stat-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid,
        body.lunara-oscars-portal-page .lunara-oscars-research-card-grid {
            gap: 12px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
        body.lunara-oscars-portal-page .lunara-ceremony-winners-grid {
            grid-template-columns: repeat(auto-fill, minmax(116px, 142px)) !important;
            justify-content: start !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card,
        body.lunara-oscars-portal-page .lunara-oscars-research-card,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-card {
            border-radius: 14px !important;
            max-width: 142px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card,
        body.lunara-oscars-portal-page .lunara-oscars-research-card,
        body.lunara-oscars-portal-page .lunara-oscars-spotlight-card-copy,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-copy {
            padding: 12px !important;
        }

        @media (min-width: 981px) {
            body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
                justify-content: stretch !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                justify-content: stretch !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-link-card {
                box-sizing: border-box !important;
                max-width: none !important;
                width: 100% !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card {
                align-items: center !important;
                box-sizing: border-box !important;
                display: grid !important;
                gap: 10px !important;
                grid-template-columns: 72px minmax(0, 1fr) !important;
                grid-template-rows: none !important;
                min-height: 104px !important;
                max-width: none !important;
                padding: 10px !important;
                width: 100% !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-spotlight-poster {
                align-self: center !important;
                aspect-ratio: auto !important;
                border-radius: 10px !important;
                height: 88px !important;
                max-height: 88px !important;
                max-width: 72px !important;
                min-height: 0 !important;
                width: 72px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-spotlight-card-copy {
                align-content: center !important;
                display: grid !important;
                gap: 4px !important;
                min-width: 0 !important;
                padding: 0 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card h3 {
                font-size: clamp(.88rem, .95vw, 1rem) !important;
                line-height: 1.12 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-secondary,
            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-meta {
                font-size: .78rem !important;
                line-height: 1.28 !important;
                margin: 0 !important;
            }
        }

        @media (max-width: 980px) {
            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                justify-content: stretch !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card {
                align-items: center !important;
                box-sizing: border-box !important;
                display: grid !important;
                gap: 10px !important;
                grid-template-columns: 70px minmax(0, 1fr) !important;
                grid-template-rows: none !important;
                min-height: 102px !important;
                max-width: none !important;
                padding: 10px !important;
                width: 100% !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-spotlight-poster {
                align-self: center !important;
                aspect-ratio: auto !important;
                border-radius: 10px !important;
                height: 88px !important;
                max-height: 88px !important;
                max-width: 70px !important;
                min-height: 0 !important;
                width: 70px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-spotlight-card-copy {
                align-content: center !important;
                display: grid !important;
                gap: 4px !important;
                min-width: 0 !important;
                padding: 0 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card h3 {
                font-size: .94rem !important;
                line-height: 1.12 !important;
            }
        }

        @media (max-width: 560px) {
            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
            grid-template-columns: minmax(106px, 136px) minmax(0, 1fr) !important;
            max-width: min(100%, 460px) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
            max-height: 210px !important;
        }

        /*
         * Oscars portal compact ledger follow-through.
         * The lower poster-led title row should read as navigation, not a wall
         * of oversized poster slabs.
         */
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)) !important;
            justify-content: stretch !important;
            gap: 12px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-title-card {
            align-items: stretch !important;
            box-sizing: border-box !important;
            display: grid !important;
            grid-template-columns: 74px minmax(0, 1fr) !important;
            grid-template-rows: none !important;
            max-width: none !important;
            min-height: 108px !important;
            padding: 0 !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-title-media {
            align-self: stretch !important;
            aspect-ratio: auto !important;
            border-radius: 14px 0 0 14px !important;
            height: 100% !important;
            max-height: none !important;
            min-height: 108px !important;
            min-width: 0 !important;
            width: 74px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-title-media img,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-media .aat-poster-img,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-media .aat-entity-poster,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-media .aat-filmography-poster-placeholder {
            display: block !important;
            height: 100% !important;
            max-height: none !important;
            object-fit: cover !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-title-copy {
            align-content: center !important;
            display: grid !important;
            gap: 4px !important;
            min-width: 0 !important;
            padding: 10px 12px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-title-card h3 {
            font-size: clamp(.88rem, 1vw, 1.02rem) !important;
            line-height: 1.13 !important;
            margin: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-title-year,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-line {
            font-size: .76rem !important;
            line-height: 1.25 !important;
            margin: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-grid,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-grid,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-grid.is-hero-latest,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-grid.is-marquee-latest {
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) !important;
            justify-content: stretch !important;
            gap: 12px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-card,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-card {
            display: grid !important;
            grid-template-columns: 72px minmax(0, 1fr) !important;
            grid-template-rows: none !important;
            max-width: none !important;
            min-height: 106px !important;
            padding: 0 !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-link {
            display: grid !important;
            grid-template-columns: 72px minmax(0, 1fr) !important;
            min-height: 106px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-poster-wrap,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-media {
            align-self: stretch !important;
            aspect-ratio: auto !important;
            border-radius: 14px 0 0 14px !important;
            height: 100% !important;
            max-height: none !important;
            min-height: 106px !important;
            width: 72px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-title,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-title {
            align-self: center !important;
            font-size: .86rem !important;
            line-height: 1.16 !important;
            margin: 0 !important;
            padding: 10px 12px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-top,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-actions,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-meta,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-meta {
            display: none !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-card-grid {
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)) !important;
            justify-content: stretch !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-card {
            align-content: start !important;
            display: grid !important;
            gap: 10px !important;
            max-width: none !important;
            min-height: 156px !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-card h3,
        body.lunara-oscars-portal-page .lunara-oscars-research-card p {
            max-width: none !important;
            min-width: 0 !important;
            overflow-wrap: anywhere !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-card h3 {
            font-size: .98rem !important;
            line-height: 1.15 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-card p {
            font-size: .82rem !important;
            line-height: 1.42 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-database-landing-shell {
            gap: clamp(18px, 2.2vw, 28px) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-header {
            align-items: center !important;
            background:
                radial-gradient(circle at top left, rgba(201, 169, 97, .12), transparent 42%),
                linear-gradient(135deg, rgba(15, 28, 43, .9), rgba(7, 15, 26, .96)) !important;
            border: 1px solid rgba(201, 169, 97, .16) !important;
            border-radius: 18px !important;
            display: grid !important;
            gap: 4px 18px !important;
            grid-template-columns: 104px minmax(0, 1fr) !important;
            margin: 4px 0 18px !important;
            padding: 18px 20px !important;
            text-align: left !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-oscar-icon {
            align-self: center !important;
            grid-column: 1 !important;
            grid-row: 1 / span 2 !important;
            height: auto !important;
            justify-self: center !important;
            margin: 0 !important;
            max-width: 100% !important;
            width: clamp(74px, 7vw, 104px) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-header h2,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-header .aat-subtitle {
            grid-column: 2 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            max-width: 760px !important;
            min-width: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-header h2 {
            font-size: clamp(1.35rem, 2.05vw, 2rem) !important;
            line-height: 1.04 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-header .aat-subtitle {
            font-size: .92rem !important;
            line-height: 1.48 !important;
        }

        @media (max-width: 760px) {
            body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-grid,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-grid,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-grid.is-hero-latest,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-grid.is-marquee-latest {
                grid-template-columns: minmax(0, 1fr) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-title-card,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-card,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-card,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-link {
                grid-template-columns: 66px minmax(0, 1fr) !important;
                min-height: 96px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-title-media,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-filmography-poster-wrap,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-media {
                min-height: 96px !important;
                width: 66px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-header {
                gap: 4px 12px !important;
                grid-template-columns: 62px minmax(0, 1fr) !important;
                padding: 14px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-oscar-icon {
                width: 58px !important;
            }
        }

        body.aat-shell-page .aat-container {
            font-family: Georgia, "Times New Roman", serif !important;
            max-width: min(1200px, calc(100vw - 36px)) !important;
        }

        body.aat-shell-page .aat-hub-header,
        body.aat-shell-page .aat-entity-header,
        body.aat-shell-page .aat-category-latest-winner,
        body.aat-shell-page .aat-ceremony-marquee {
            border-radius: 18px !important;
            padding: clamp(20px, 2.8vw, 32px) !important;
        }

        body.aat-shell-page .aat-category-latest-winner,
        body.aat-shell-page .aat-ceremony-marquee {
            gap: clamp(16px, 2vw, 24px) !important;
        }

        body.aat-shell-page .aat-category-latest-winner .aat-hub-spotlight-card,
        body.aat-shell-page .aat-ceremony-marquee .aat-hub-spotlight-card {
            grid-template-columns: minmax(104px, 148px) minmax(0, 1fr) !important;
            gap: 14px !important;
            max-width: 760px !important;
            padding: 12px !important;
        }

        body.aat-shell-page .aat-category-latest-winner .aat-hub-spotlight-media,
        body.aat-shell-page .aat-category-latest-winner .aat-hub-spotlight-media-link,
        body.aat-shell-page .aat-ceremony-marquee .aat-hub-spotlight-media,
        body.aat-shell-page .aat-ceremony-marquee .aat-hub-spotlight-media-link {
            max-width: 148px !important;
            min-height: 0 !important;
            width: 148px !important;
        }

        body.aat-shell-page .aat-category-latest-winner h2,
        body.aat-shell-page .aat-ceremony-marquee h2,
        body.aat-shell-page .aat-section-title {
            font-size: clamp(1.42rem, 2.2vw, 2.08rem) !important;
            line-height: 1.08 !important;
        }

        body.aat-shell-page .aat-hub-spotlight-title,
        body.aat-shell-page .aat-category-history-title,
        body.aat-shell-page .aat-winner-circle-title {
            font-size: clamp(.98rem, 1.35vw, 1.18rem) !important;
            line-height: 1.15 !important;
        }

        body.aat-shell-page .aat-hub-metric-grid,
        body.aat-shell-page .aat-stats-bar,
        body.aat-shell-page .aat-records-grid {
            gap: 10px !important;
        }

        body.aat-shell-page .aat-hub-metric-card,
        body.aat-shell-page .aat-stat,
        body.aat-shell-page .aat-category-history-winner,
        body.aat-shell-page .aat-category-ceremony-row,
        body.aat-shell-page .aat-nominee-row,
        body.aat-shell-page .aat-history-item,
        body.aat-shell-page .aat-timeline-card {
            border-radius: 14px !important;
            gap: 8px !important;
            padding: 12px 14px !important;
        }

        body.aat-shell-page .aat-hub-metric-copy,
        body.aat-shell-page .aat-section-description,
        body.aat-shell-page .aat-history-line,
        body.aat-shell-page .aat-category-history-detail,
        body.aat-shell-page .aat-nominee-secondary,
        body.aat-shell-page .aat-nominee-detail {
            font-size: .9rem !important;
            line-height: 1.45 !important;
        }

        body.aat-shell-page .aat-decade-nav,
        body.aat-shell-page .aat-hub-chip-stack,
        body.aat-shell-page .aat-crossroads-list {
            gap: 8px !important;
        }

        body.aat-shell-page .aat-decade-pill,
        body.aat-shell-page .aat-crossroad-pill,
        body.aat-shell-page .aat-winner-circle-action,
        body.aat-shell-page .aat-hub-inline-link.aat-hub-inline-link-title {
            line-height: 1.18 !important;
        }

        body.post-type-archive-journal .lunara-archive-page,
        body.single-journal .lunara-journal-single-page {
            font-family: Georgia, "Times New Roman", serif !important;
        }

        body.post-type-archive-journal .lunara-archive-page {
            width: min(1180px, calc(100vw - 36px)) !important;
            margin-inline: auto !important;
        }

        body.post-type-archive-journal .lunara-archive-hero,
        body.post-type-archive-journal .lunara-journal-archive-deskbar,
        body.post-type-archive-journal .lunara-editorial-archive-toolbar {
            border-radius: 18px !important;
            margin-inline: auto !important;
            max-width: 1120px !important;
        }

        body.post-type-archive-journal .lunara-archive-hero {
            padding: clamp(24px, 4vw, 44px) !important;
        }

        body.post-type-archive-journal .lunara-archive-hero-title {
            font-size: clamp(2rem, 4.4vw, 4.1rem) !important;
            line-height: 1.02 !important;
        }

        body.post-type-archive-journal .lunara-archive-hero-copy {
            max-width: 62ch !important;
            font-size: clamp(.98rem, 1.25vw, 1.12rem) !important;
            line-height: 1.56 !important;
        }

        body.home .lunara-journal-home-card,
        body.post-type-archive-journal .lunara-journal-archive-card,
        body.single-journal .lunara-review-single-body,
        body.single-journal .lunara-journal-image-carousel {
            border-radius: 18px !important;
            box-shadow: 0 20px 48px rgba(0, 0, 0, 0.22) !important;
        }

        body.home .lunara-journal-home-card-title,
        body.post-type-archive-journal .lunara-review-grid-title,
        body.single-journal .lunara-review-single-title {
            line-height: 1.12 !important;
        }

        body.home .lunara-journal-home-card-excerpt,
        body.post-type-archive-journal .lunara-review-grid-excerpt,
        body.single-journal .lunara-review-single-content p {
            line-height: 1.62 !important;
        }

        body.single-journal .lunara-review-single-body {
            max-width: 900px !important;
        }

        body.home .lunara-journal-home-card.is-lead,
        body.post-type-archive-journal .lunara-journal-archive-card.is-lead {
            background:
                linear-gradient(180deg, rgba(22, 39, 58, 0.94), rgba(10, 22, 34, 0.98)) !important;
            box-shadow: 0 24px 56px rgba(0, 0, 0, 0.28) !important;
        }

        body.home .lunara-journal-home-card-cta,
        body.post-type-archive-journal .lunara-journal-archive-card-cta {
            white-space: nowrap !important;
        }

        body.single-journal .lunara-journal-cinematic-hero-credit {
            border-color: rgba(224, 196, 129, 0.28) !important;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.28) !important;
            color: rgba(244, 239, 227, 0.92) !important;
        }

        body.single-journal .lunara-journal-image-carousel {
            border: 1px solid rgba(201, 169, 97, 0.18) !important;
            background: rgba(8, 19, 31, 0.72) !important;
            padding: clamp(14px, 2vw, 20px) !important;
        }

        body.single-journal .lunara-journal-image-carousel-head h2 {
            margin: 0 !important;
            color: #FAFBFC !important;
            font-size: clamp(1.24rem, 2vw, 1.7rem) !important;
            line-height: 1.1 !important;
        }

        body.single-journal .lunara-journal-image-carousel-head p {
            margin: 6px 0 0 !important;
            color: rgba(244, 239, 227, 0.72) !important;
            font-size: .9rem !important;
            line-height: 1.45 !important;
        }

        body.single-journal .lunara-journal-image-carousel-slide {
            overflow: hidden !important;
            border: 1px solid rgba(201, 169, 97, 0.16) !important;
            border-radius: 14px !important;
            background: rgba(255, 255, 255, 0.035) !important;
        }

        body.single-journal .lunara-journal-image-carousel-slide figcaption {
            display: grid !important;
            gap: 4px !important;
            padding: 9px 11px !important;
            color: rgba(244, 239, 227, 0.78) !important;
            font-size: .78rem !important;
            line-height: 1.35 !important;
        }

        body.single-journal .lunara-review-single-content {
            max-width: min(100%, 68ch) !important;
            font-size: clamp(1rem, 1.08vw, 1.08rem) !important;
        }

        @media (max-width: 820px) {
            body.lunara-oscars-portal-page .lunara-oscars-portal {
                width: min(100%, calc(100vw - 24px)) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
                max-width: 12ch !important;
                font-size: clamp(1.62rem, 6.8vw, 2.25rem) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
                grid-template-columns: minmax(92px, 31vw) minmax(0, 1fr) !important;
            }

            body.aat-shell-page .aat-category-latest-winner .aat-hub-spotlight-card,
            body.aat-shell-page .aat-ceremony-marquee .aat-hub-spotlight-card {
                max-width: 100% !important;
            }
        }

        @media (max-width: 560px) {
            body.post-type-archive-journal .lunara-archive-page {
                width: 100vw !important;
                max-width: 100vw !important;
                margin-left: calc(50% - 50vw) !important;
                margin-right: calc(50% - 50vw) !important;
                padding-inline: 0 !important;
                align-items: center !important;
            }

            body.post-type-archive-journal .lunara-journal-archive-hero,
            body.post-type-archive-journal .lunara-journal-archive-deskbar,
            body.post-type-archive-journal .lunara-journal-archive-filters,
            body.post-type-archive-journal .lunara-editorial-archive-toolbar,
            body.post-type-archive-journal .lunara-editorial-archive-toolbar-head,
            body.post-type-archive-journal .lunara-archive-sort,
            body.post-type-archive-journal .lunara-journal-archive-grid,
            body.post-type-archive-journal .lunara-review-archive-uniform {
                width: min(100%, 312px) !important;
                max-width: min(100%, 312px) !important;
                margin-left: auto !important;
                margin-right: auto !important;
                box-sizing: border-box !important;
            }

            body.post-type-archive-journal .lunara-archive-hero {
                padding: 22px 18px !important;
                overflow: visible !important;
            }

            body.post-type-archive-journal .lunara-archive-hero-title {
                font-size: clamp(1.8rem, 9vw, 2.42rem) !important;
            }

            body.post-type-archive-journal .lunara-archive-hero-copy {
                width: 100% !important;
                max-width: 31ch !important;
                margin-left: auto !important;
                margin-right: auto !important;
                overflow-wrap: normal !important;
                text-wrap: pretty !important;
                white-space: normal !important;
                word-break: normal !important;
            }

            body.post-type-archive-journal .lunara-journal-filter-pill,
            body.post-type-archive-journal .lunara-archive-sort-link {
                max-width: 100% !important;
            }

            body.post-type-archive-journal .lunara-journal-archive-deskbar,
            body.post-type-archive-journal .lunara-journal-archive-filters,
            body.post-type-archive-journal .lunara-editorial-archive-toolbar,
            body.post-type-archive-journal .lunara-editorial-archive-toolbar-head,
            body.post-type-archive-journal .lunara-archive-sort,
            body.post-type-archive-journal .lunara-journal-archive-grid {
                transform: translateX(-32px) !important;
            }

            body.post-type-archive-journal .lunara-review-grid-card,
            body.post-type-archive-journal .lunara-journal-archive-card {
                width: 100% !important;
                max-width: 100% !important;
            }

            body.aat-shell-page .aat-category-latest-winner,
            body.aat-shell-page .aat-ceremony-marquee {
                padding: 18px !important;
            }

            body.aat-shell-page .aat-category-latest-winner .aat-hub-spotlight-card,
            body.aat-shell-page .aat-ceremony-marquee .aat-hub-spotlight-card {
                grid-template-columns: minmax(88px, 108px) minmax(0, 1fr) !important;
                max-width: 100% !important;
                padding: 10px !important;
            }

            body.aat-shell-page .aat-category-latest-winner .aat-hub-spotlight-media,
            body.aat-shell-page .aat-category-latest-winner .aat-hub-spotlight-media-link,
            body.aat-shell-page .aat-ceremony-marquee .aat-hub-spotlight-media,
            body.aat-shell-page .aat-ceremony-marquee .aat-hub-spotlight-media-link {
                max-width: 108px !important;
                width: 108px !important;
            }
        }

        @media (max-width: 900px) {
            body.lunara-oscars-portal-page .lunara-oscars-portal-hero {
                min-height: auto !important;
                padding: clamp(16px, 4vw, 24px) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid {
                display: grid !important;
                gap: 16px !important;
                grid-template-columns: minmax(0, 1fr) !important;
                min-height: 0 !important;
                padding: 0 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-copy,
            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
                max-width: 100% !important;
                min-width: 0 !important;
                width: 100% !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
                font-size: clamp(1.76rem, 7vw, 2.5rem) !important;
                line-height: 1.06 !important;
                max-width: 100% !important;
                overflow-wrap: normal !important;
                text-wrap: balance !important;
                word-break: normal !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-copy {
                max-width: 34rem !important;
                overflow-wrap: normal !important;
                word-break: normal !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
                align-items: center !important;
                display: grid !important;
                gap: 10px !important;
                grid-template-columns: minmax(96px, 124px) minmax(0, 1fr) !important;
                justify-self: stretch !important;
                padding: 10px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
                align-self: center !important;
                max-height: 186px !important;
                width: 100% !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy {
                display: grid !important;
                gap: 5px !important;
                min-width: 0 !important;
                padding: 0 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-kicker,
            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-meta {
                overflow-wrap: normal !important;
                word-break: normal !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-kicker {
                font-size: .58rem !important;
                letter-spacing: .08em !important;
                line-height: 1.18 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy h2 {
                font-size: clamp(.96rem, 3vw, 1.2rem) !important;
                line-height: 1.13 !important;
                overflow-wrap: normal !important;
                word-break: normal !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-body {
                display: none !important;
            }
        }

        @media (max-width: 520px) {
            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
                grid-template-columns: minmax(84px, 102px) minmax(0, 1fr) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
                max-height: 154px !important;
            }
        }
        /*
         * Oscars portal dynamic recovery pass.
         * Restore force, legibility, and image rhythm after the compact pass
         * made the Ledger front door too flat.
         */
        body.lunara-oscars-portal-page .lunara-oscars-portal {
            gap: clamp(44px, 5vw, 78px) !important;
        }

        body.lunara-oscars-portal-page .lunara-home-section-header {
            align-items: end !important;
            gap: clamp(20px, 3vw, 42px) !important;
        }

        body.lunara-oscars-portal-page .lunara-home-section-title,
        body.lunara-oscars-portal-page .aat-hub-section h2,
        body.lunara-oscars-portal-page .aat-ceremony-marquee h2 {
            color: #f7f4ec !important;
            font-family: Georgia, "Times New Roman", serif !important;
            font-size: clamp(2rem, 3.4vw, 4.15rem) !important;
            font-weight: 800 !important;
            letter-spacing: 0 !important;
            line-height: 1.03 !important;
            text-wrap: balance !important;
        }

        body.lunara-oscars-portal-page .lunara-home-section-summary,
        body.lunara-oscars-portal-page .aat-hub-copy,
        body.lunara-oscars-portal-page .aat-subtitle,
        body.lunara-oscars-portal-page .aat-database-landing-note {
            color: rgba(249, 250, 251, .91) !important;
            font-size: clamp(1.04rem, 1.18vw, 1.2rem) !important;
            font-weight: 600 !important;
            line-height: 1.62 !important;
            max-width: 64ch !important;
            text-wrap: pretty !important;
        }

        body.lunara-oscars-portal-page .lunara-home-section-kicker,
        body.lunara-oscars-portal-page .aat-hub-kicker,
        body.lunara-oscars-portal-page .aat-hub-metric-label,
        body.lunara-oscars-portal-page .aat-stat-label {
            color: #f1cc6e !important;
            font-family: Georgia, "Times New Roman", serif !important;
            font-size: .82rem !important;
            font-weight: 800 !important;
            letter-spacing: 0 !important;
            text-transform: uppercase !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-container.aat-database-landing-shell,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-container.aat-embedded {
            max-width: min(100%, 1280px) !important;
            margin-inline: auto !important;
        }

        body.lunara-oscars-portal-page .aat-header.aat-ledger-command,
        body.lunara-oscars-portal-page .aat-header.aat-database-landing-header {
            display: grid !important;
            grid-template-columns: minmax(82px, 120px) minmax(0, 1fr) minmax(300px, 430px) !important;
            align-items: center !important;
            gap: clamp(20px, 3vw, 42px) !important;
            min-height: 300px !important;
            padding: clamp(26px, 4vw, 58px) !important;
            border: 1px solid rgba(241, 204, 110, .26) !important;
            border-radius: 8px !important;
            background:
                linear-gradient(135deg, rgba(2, 9, 19, .96), rgba(8, 24, 38, .92) 52%, rgba(16, 22, 32, .98)),
                linear-gradient(90deg, rgba(241, 204, 110, .12), transparent 58%) !important;
            box-shadow: 0 32px 90px rgba(0, 0, 0, .36), inset 0 1px 0 rgba(255, 255, 255, .06) !important;
            overflow: hidden !important;
            position: relative !important;
            text-align: left !important;
        }

        body.lunara-oscars-portal-page .aat-header.aat-ledger-command::after,
        body.lunara-oscars-portal-page .aat-header.aat-database-landing-header::after {
            content: "" !important;
            position: absolute !important;
            inset: auto 28px 28px auto !important;
            width: min(34vw, 440px) !important;
            height: 1px !important;
            background: linear-gradient(90deg, transparent, rgba(241, 204, 110, .66)) !important;
        }

        body.lunara-oscars-portal-page .aat-ledger-command-symbol,
        body.lunara-oscars-portal-page .aat-database-landing-header > .aat-oscar-icon {
            align-self: stretch !important;
            display: grid !important;
            place-items: center !important;
        }

        body.lunara-oscars-portal-page .aat-oscar-icon {
            width: clamp(74px, 8vw, 112px) !important;
            max-width: 112px !important;
            filter: drop-shadow(0 16px 22px rgba(0, 0, 0, .46)) !important;
        }

        body.lunara-oscars-portal-page .aat-ledger-command-copy h2,
        body.lunara-oscars-portal-page .aat-database-landing-header h2 {
            color: #ffffff !important;
            font-family: Georgia, "Times New Roman", serif !important;
            font-size: clamp(2.25rem, 4.8vw, 5.65rem) !important;
            font-weight: 800 !important;
            letter-spacing: 0 !important;
            line-height: .98 !important;
            margin: 0 0 18px !important;
            max-width: 10.5ch !important;
            text-wrap: balance !important;
        }

        body.lunara-oscars-portal-page .aat-ledger-header-actions {
            display: grid !important;
            gap: 12px !important;
            position: relative !important;
            z-index: 1 !important;
        }

        body.lunara-oscars-portal-page .aat-ledger-header-action,
        body.lunara-oscars-portal-page .aat-database-landing-actions .aat-btn {
            display: grid !important;
            gap: 7px !important;
            min-height: 76px !important;
            padding: 16px 18px !important;
            border: 1px solid rgba(241, 204, 110, .32) !important;
            border-radius: 8px !important;
            background: rgba(255, 255, 255, .055) !important;
            color: #fff7df !important;
            font-family: Georgia, "Times New Roman", serif !important;
            text-decoration: none !important;
            transition: background .18s ease, border-color .18s ease, transform .18s ease !important;
        }

        body.lunara-oscars-portal-page .aat-ledger-header-action:hover,
        body.lunara-oscars-portal-page .aat-database-landing-actions .aat-btn:hover {
            background: rgba(241, 204, 110, .14) !important;
            border-color: rgba(241, 204, 110, .72) !important;
            transform: translateY(-2px) !important;
        }

        body.lunara-oscars-portal-page .aat-ledger-header-action strong,
        body.lunara-oscars-portal-page .aat-database-landing-actions .aat-btn {
            color: #ffe28b !important;
            font-size: clamp(1.04rem, 1.4vw, 1.35rem) !important;
            font-weight: 800 !important;
            line-height: 1.1 !important;
        }

        body.lunara-oscars-portal-page .aat-ledger-header-action span {
            color: rgba(255, 255, 255, .76) !important;
            font-size: .9rem !important;
            font-weight: 600 !important;
            line-height: 1.35 !important;
        }

        body.lunara-oscars-portal-page .aat-database-landing-metrics,
        body.lunara-oscars-portal-page .aat-explorer-signal-grid {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 14px !important;
            margin-top: clamp(20px, 3vw, 32px) !important;
        }

        body.lunara-oscars-portal-page .aat-hub-metric-card,
        body.lunara-oscars-portal-page .aat-stat {
            display: grid !important;
            gap: 12px !important;
            min-height: 150px !important;
            padding: 18px !important;
            border: 1px solid rgba(241, 204, 110, .22) !important;
            border-radius: 8px !important;
            background: linear-gradient(145deg, rgba(255, 255, 255, .07), rgba(255, 255, 255, .025)) !important;
            color: #f7f4ec !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05) !important;
        }

        body.lunara-oscars-portal-page .aat-hub-metric-value,
        body.lunara-oscars-portal-page .aat-stat-number {
            color: #fff !important;
            font-family: Georgia, "Times New Roman", serif !important;
            font-size: clamp(1.65rem, 2.8vw, 3.1rem) !important;
            font-weight: 800 !important;
            letter-spacing: 0 !important;
            line-height: 1 !important;
        }

        body.lunara-oscars-portal-page .aat-hub-metric-copy,
        body.lunara-oscars-portal-page .aat-stat-label {
            color: rgba(255, 255, 255, .82) !important;
            font-size: .96rem !important;
            font-weight: 600 !important;
            line-height: 1.46 !important;
        }

        body.lunara-oscars-portal-page .aat-ceremony-marquee {
            display: grid !important;
            grid-template-columns: minmax(0, 1.2fr) minmax(280px, .8fr) !important;
            gap: clamp(18px, 3vw, 38px) !important;
            padding: clamp(24px, 4vw, 46px) !important;
            border: 1px solid rgba(241, 204, 110, .22) !important;
            border-radius: 8px !important;
            background: linear-gradient(135deg, rgba(5, 14, 25, .92), rgba(8, 19, 29, .72)) !important;
        }

        body.lunara-oscars-portal-page .aat-ceremony-marquee-stack {
            display: grid !important;
            gap: 12px !important;
            align-content: center !important;
        }

        body.lunara-oscars-portal-page .aat-hub-chip-rich {
            display: grid !important;
            gap: 7px !important;
            min-height: 86px !important;
            padding: 17px 18px !important;
            border-radius: 8px !important;
            border: 1px solid rgba(241, 204, 110, .36) !important;
            background-color: rgba(255, 255, 255, .065) !important;
            color: #fff7df !important;
            text-decoration: none !important;
        }

        body.lunara-oscars-portal-page .aat-hub-chip-rich strong {
            color: #ffe28b !important;
            font-size: clamp(1.05rem, 1.35vw, 1.3rem) !important;
            font-weight: 800 !important;
        }

        body.lunara-oscars-portal-page .aat-hub-chip-rich span {
            color: rgba(255, 255, 255, .78) !important;
            font-size: .94rem !important;
            font-weight: 600 !important;
        }

        body.lunara-oscars-portal-page .aat-ceremony-gallery-section .aat-hub-film-grid,
        body.lunara-oscars-portal-page .aat-filmography-grid.aat-hub-film-grid {
            display: grid !important;
            grid-template-columns: repeat(6, minmax(0, 1fr)) !important;
            gap: 16px !important;
            align-items: stretch !important;
        }

        body.lunara-oscars-portal-page .aat-ceremony-gallery-section .aat-filmography-card {
            grid-column: span 2 !important;
            min-height: clamp(168px, 14vw, 220px) !important;
            border-radius: 8px !important;
            overflow: hidden !important;
            border: 1px solid rgba(241, 204, 110, .22) !important;
            background-color: rgba(4, 13, 23, .9) !important;
            box-shadow: 0 18px 42px rgba(0, 0, 0, .25) !important;
        }

        body.lunara-oscars-portal-page .aat-ceremony-gallery-section .aat-filmography-card:nth-child(1),
        body.lunara-oscars-portal-page .aat-ceremony-gallery-section .aat-filmography-card:nth-child(2) {
            grid-column: span 3 !important;
            min-height: clamp(210px, 18vw, 280px) !important;
        }

        body.lunara-oscars-portal-page .aat-ceremony-gallery-section .aat-filmography-link {
            display: grid !important;
            grid-template-columns: minmax(96px, 34%) minmax(0, 1fr) !important;
            align-items: stretch !important;
            gap: 0 !important;
            height: 100% !important;
            min-height: inherit !important;
            color: #fff7df !important;
            text-decoration: none !important;
        }

        body.lunara-oscars-portal-page .aat-filmography-poster-wrap {
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
            height: 100% !important;
            aspect-ratio: auto !important;
            border-radius: 0 !important;
            overflow: hidden !important;
        }

        body.lunara-oscars-portal-page .aat-filmography-poster,
        body.lunara-oscars-portal-page .aat-filmography-poster-wrap img {
            width: 100% !important;
            height: 100% !important;
            max-height: none !important;
            object-fit: cover !important;
            object-position: center !important;
        }

        body.lunara-oscars-portal-page .aat-filmography-card-content,
        body.lunara-oscars-portal-page .aat-filmography-copy,
        body.lunara-oscars-portal-page .aat-filmography-title,
        body.lunara-oscars-portal-page .aat-filmography-meta,
        body.lunara-oscars-portal-page .aat-filmography-trail {
            min-width: 0 !important;
        }

        body.lunara-oscars-portal-page .aat-filmography-title {
            color: #ffe28b !important;
            font-family: Georgia, "Times New Roman", serif !important;
            font-size: clamp(1.08rem, 1.65vw, 1.55rem) !important;
            font-weight: 800 !important;
            letter-spacing: 0 !important;
            line-height: 1.12 !important;
            overflow-wrap: anywhere !important;
            text-wrap: balance !important;
        }

        body.lunara-oscars-portal-page .aat-filmography-meta,
        body.lunara-oscars-portal-page .aat-filmography-trail {
            color: rgba(255, 255, 255, .82) !important;
            font-size: .94rem !important;
            font-weight: 600 !important;
            line-height: 1.4 !important;
        }

        body.lunara-oscars-portal-page .aat-winner-circle-grid {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 16px !important;
        }

        body.lunara-oscars-portal-page .aat-winner-circle-card {
            display: grid !important;
            grid-template-columns: minmax(104px, 140px) minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 18px !important;
            min-height: 156px !important;
            padding: 14px !important;
            border: 1px solid rgba(241, 204, 110, .24) !important;
            border-radius: 8px !important;
            background: linear-gradient(145deg, rgba(255, 255, 255, .07), rgba(255, 255, 255, .025)) !important;
            color: #f7f4ec !important;
        }

        body.lunara-oscars-portal-page .aat-winner-circle-media {
            width: 100% !important;
            max-width: 140px !important;
            min-height: 126px !important;
            aspect-ratio: 1 / 1.18 !important;
            border-radius: 8px !important;
            overflow: hidden !important;
        }

        body.lunara-oscars-portal-page .aat-winner-circle-media img,
        body.lunara-oscars-portal-page .aat-winner-circle-photo {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            object-position: center !important;
        }

        body.lunara-oscars-portal-page .aat-winner-circle-title,
        body.lunara-oscars-portal-page .aat-winner-circle-title a {
            color: #ffe28b !important;
            font-family: Georgia, "Times New Roman", serif !important;
            font-size: clamp(1.04rem, 1.35vw, 1.32rem) !important;
            font-weight: 800 !important;
            letter-spacing: 0 !important;
            line-height: 1.15 !important;
            text-decoration: none !important;
            text-wrap: balance !important;
        }

        body.lunara-oscars-portal-page .aat-winner-circle-meta,
        body.lunara-oscars-portal-page .aat-winner-circle-meta a {
            color: rgba(255, 255, 255, .82) !important;
            font-size: .92rem !important;
            font-weight: 600 !important;
            line-height: 1.35 !important;
            text-decoration: none !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 16px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card {
            min-height: 178px !important;
            padding: 20px !important;
            border-radius: 8px !important;
            border-color: rgba(241, 204, 110, .24) !important;
            background: linear-gradient(145deg, rgba(255, 255, 255, .075), rgba(255, 255, 255, .022)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-value {
            color: #fff !important;
            font-size: clamp(2.2rem, 4vw, 4.25rem) !important;
            line-height: .92 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-context {
            color: rgba(255, 255, 255, .82) !important;
            font-size: .98rem !important;
            font-weight: 600 !important;
            line-height: 1.45 !important;
        }

        body.lunara-oscars-portal-page .lunara-ceremony-winners-grid {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 16px !important;
        }

        body.lunara-oscars-portal-page .lunara-ceremony-winner-card {
            border-radius: 8px !important;
            border: 1px solid rgba(241, 204, 110, .24) !important;
            background: rgba(255, 255, 255, .045) !important;
            overflow: hidden !important;
        }

        body.lunara-oscars-portal-page .lunara-ceremony-winner-card img,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-card .aat-winner-circle-media {
            max-height: 180px !important;
            object-fit: cover !important;
        }

        body.lunara-oscars-portal-page a:not(.ct-menu-link):not(.lunara-button):not(.lunara-btn) {
            text-decoration-color: rgba(241, 204, 110, .45) !important;
            text-underline-offset: 3px !important;
        }

        @media (max-width: 1120px) {
            body.lunara-oscars-portal-page .aat-header.aat-ledger-command,
            body.lunara-oscars-portal-page .aat-header.aat-database-landing-header {
                grid-template-columns: minmax(72px, 98px) minmax(0, 1fr) !important;
            }

            body.lunara-oscars-portal-page .aat-ledger-header-actions {
                grid-column: 1 / -1 !important;
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }

            body.lunara-oscars-portal-page .aat-database-landing-metrics,
            body.lunara-oscars-portal-page .aat-explorer-signal-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            body.lunara-oscars-portal-page .aat-winner-circle-grid,
            body.lunara-oscars-portal-page .lunara-ceremony-winners-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 780px) {
            body.lunara-oscars-portal-page .lunara-home-section-header,
            body.lunara-oscars-portal-page .aat-ceremony-marquee {
                grid-template-columns: 1fr !important;
            }

            body.lunara-oscars-portal-page .aat-header.aat-ledger-command,
            body.lunara-oscars-portal-page .aat-header.aat-database-landing-header {
                grid-template-columns: 1fr !important;
                min-height: 0 !important;
                padding: 24px !important;
            }

            body.lunara-oscars-portal-page .aat-ledger-command-symbol,
            body.lunara-oscars-portal-page .aat-database-landing-header > .aat-oscar-icon {
                justify-self: start !important;
            }

            body.lunara-oscars-portal-page .aat-ledger-header-actions,
            body.lunara-oscars-portal-page .aat-database-landing-metrics,
            body.lunara-oscars-portal-page .aat-explorer-signal-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid,
            body.lunara-oscars-portal-page .aat-winner-circle-grid,
            body.lunara-oscars-portal-page .lunara-ceremony-winners-grid {
                grid-template-columns: 1fr !important;
            }

            body.lunara-oscars-portal-page .aat-ceremony-gallery-section .aat-hub-film-grid,
            body.lunara-oscars-portal-page .aat-filmography-grid.aat-hub-film-grid {
                grid-template-columns: 1fr !important;
            }

            body.lunara-oscars-portal-page .aat-ceremony-gallery-section .aat-filmography-card,
            body.lunara-oscars-portal-page .aat-ceremony-gallery-section .aat-filmography-card:nth-child(1),
            body.lunara-oscars-portal-page .aat-ceremony-gallery-section .aat-filmography-card:nth-child(2) {
                grid-column: auto !important;
                min-height: 156px !important;
            }

            body.lunara-oscars-portal-page .aat-ceremony-gallery-section .aat-filmography-link {
                grid-template-columns: minmax(96px, 126px) minmax(0, 1fr) !important;
            }

            body.lunara-oscars-portal-page .aat-winner-circle-card {
                grid-template-columns: minmax(92px, 116px) minmax(0, 1fr) !important;
                min-height: 136px !important;
            }
        }

        @media (max-width: 430px) {
            body.lunara-oscars-portal-page .aat-header.aat-ledger-command,
            body.lunara-oscars-portal-page .aat-header.aat-database-landing-header {
                padding: 20px !important;
            }

            body.lunara-oscars-portal-page .aat-ledger-command-copy h2,
            body.lunara-oscars-portal-page .aat-database-landing-header h2 {
                font-size: clamp(2.15rem, 12vw, 3.4rem) !important;
                max-width: 9.5ch !important;
            }

            body.lunara-oscars-portal-page .aat-ceremony-gallery-section .aat-filmography-link,
            body.lunara-oscars-portal-page .aat-winner-circle-card {
                grid-template-columns: minmax(84px, 104px) minmax(0, 1fr) !important;
                gap: 14px !important;
            }

            body.lunara-oscars-portal-page .aat-winner-circle-media {
                min-height: 108px !important;
            }

            body.lunara-oscars-portal-page .aat-filmography-title,
            body.lunara-oscars-portal-page .aat-winner-circle-title,
            body.lunara-oscars-portal-page .aat-winner-circle-title a {
                font-size: 1.03rem !important;
            }
        }
        /*
         * Correct older compact rules that were forcing poster cards into
         * a 72px column after the dynamic recovery pass.
         */
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-ceremony-gallery-section .aat-filmography-card,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-ceremony-gallery-section .aat-filmography-card:nth-child(1),
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-ceremony-gallery-section .aat-filmography-card:nth-child(2) {
            display: block !important;
            padding: 0 !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-ceremony-gallery-section .aat-filmography-link {
            display: grid !important;
            grid-template-columns: minmax(104px, 32%) minmax(0, 1fr) !important;
            grid-template-rows: minmax(0, 1fr) auto !important;
            width: 100% !important;
            min-height: inherit !important;
            height: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-ceremony-gallery-section .aat-filmography-poster-wrap {
            grid-column: 1 !important;
            grid-row: 1 / 3 !important;
            width: 100% !important;
            min-height: inherit !important;
            border-radius: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-ceremony-gallery-section .aat-filmography-title {
            grid-column: 2 !important;
            grid-row: 1 !important;
            align-self: end !important;
            justify-self: stretch !important;
            width: 100% !important;
            max-width: none !important;
            padding: 20px 22px 6px !important;
            overflow-wrap: normal !important;
            word-break: normal !important;
            text-wrap: pretty !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-ceremony-gallery-section .aat-filmography-meta {
            display: block !important;
            grid-column: 2 !important;
            grid-row: 2 !important;
            width: 100% !important;
            padding: 0 22px 20px !important;
            overflow-wrap: normal !important;
            word-break: normal !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-grid,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-grid.is-hero-latest,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-grid.is-marquee-latest {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 16px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-card,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-card.is-hero-latest,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-card.is-marquee-latest {
            display: grid !important;
            grid-template-columns: minmax(104px, 136px) minmax(0, 1fr) !important;
            grid-template-rows: auto 1fr auto !important;
            align-items: center !important;
            gap: 12px 18px !important;
            min-height: 154px !important;
            padding: 14px !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-media,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-card.is-hero-latest .aat-winner-circle-media,
        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-card.is-marquee-latest .aat-winner-circle-media {
            display: block !important;
            grid-column: 1 !important;
            grid-row: 1 / 4 !important;
            width: 100% !important;
            max-width: 136px !important;
            min-height: 126px !important;
            aspect-ratio: 1 / 1.18 !important;
            border-radius: 8px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-title {
            grid-column: 2 !important;
            grid-row: 2 !important;
            align-self: end !important;
            width: 100% !important;
            padding: 0 !important;
            overflow-wrap: normal !important;
            word-break: normal !important;
            text-wrap: pretty !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-meta {
            display: block !important;
            grid-column: 2 !important;
            grid-row: 3 !important;
            margin: 0 !important;
            overflow-wrap: normal !important;
            word-break: normal !important;
        }

        @media (max-width: 780px) {
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-grid,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-grid.is-hero-latest,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-grid.is-marquee-latest {
                grid-template-columns: 1fr !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-ceremony-gallery-section .aat-filmography-link,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-card,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-card.is-hero-latest,
            body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-winner-circle-section .aat-winner-circle-card.is-marquee-latest {
                grid-template-columns: minmax(92px, 118px) minmax(0, 1fr) !important;
            }
        }
        /*
         * Final Oscars portal legibility guardrails for stat and rotating
         * ceremony lanes.
         */
        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card {
            align-content: start !important;
            overflow: hidden !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-value {
            display: block !important;
            max-width: 100% !important;
            color: #fff !important;
            font-size: clamp(1.75rem, 2.25vw, 2.45rem) !important;
            line-height: 1.04 !important;
            overflow-wrap: normal !important;
            word-break: normal !important;
            text-wrap: balance !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ledger-carousel-track {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 16px !important;
            transform: none !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ceremony-winner-card:not(.has-poster) {
            display: grid !important;
            align-content: end !important;
            min-height: 178px !important;
            padding: 18px !important;
            background: linear-gradient(145deg, rgba(255, 255, 255, .07), rgba(255, 255, 255, .025)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ceremony-winner-card:not(.has-poster) .lunara-ceremony-winner-media-link:empty {
            display: none !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ceremony-winner-card:not(.has-poster) .lunara-ceremony-winner-copy {
            display: grid !important;
            gap: 8px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ceremony-winner-name,
        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ceremony-winner-name a {
            color: #ffe28b !important;
            font-size: clamp(1.02rem, 1.3vw, 1.26rem) !important;
            line-height: 1.12 !important;
            overflow-wrap: normal !important;
            word-break: normal !important;
            text-wrap: pretty !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ceremony-winner-category,
        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ceremony-winner-film {
            color: rgba(255, 255, 255, .82) !important;
            font-size: .86rem !important;
            line-height: 1.25 !important;
            overflow-wrap: normal !important;
            word-break: normal !important;
        }

        @media (max-width: 980px) {
            body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ledger-carousel-track {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 520px) {
            body.lunara-oscars-portal-page .lunara-oscars-portal-fact-value {
                font-size: clamp(1.55rem, 9vw, 2.2rem) !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ledger-carousel-track {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 10px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ceremony-winner-card:not(.has-poster) {
                min-height: 148px !important;
                padding: 12px !important;
            }
        }
        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-value {
            font-size: clamp(1.35rem, 1.8vw, 1.78rem) !important;
            line-height: 1.14 !important;
            white-space: normal !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ceremony-winner-card:not(.has-poster) .lunara-ceremony-winner-media-link {
            display: none !important;
        }

        /*
         * Oscars rotating winners carousel.
         * Earlier compactness passes intentionally reduced bulk; this restores
         * motion and symmetry without returning to oversized poster slabs.
         */
        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section {
            position: relative !important;
            overflow: hidden !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-home-section-header {
            align-items: end !important;
            gap: clamp(16px, 2vw, 24px) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-oscars-rotating-winners-actions {
            justify-content: flex-end !important;
            margin-left: auto !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-oscars-rotating-winners-note {
            margin: 0 !important;
            color: rgba(244, 239, 227, .74) !important;
            font-size: .82rem !important;
            font-weight: 700 !important;
            letter-spacing: .05em !important;
            line-height: 1.25 !important;
            text-transform: uppercase !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-poster-carousel-controls {
            display: inline-flex !important;
            gap: 8px !important;
            align-items: center !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-poster-carousel-btn {
            display: inline-grid !important;
            place-items: center !important;
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            padding: 0 !important;
            border: 1px solid rgba(237, 210, 150, .44) !important;
            border-radius: 999px !important;
            background: rgba(7, 15, 26, .72) !important;
            color: #ffe28b !important;
            font-family: Georgia, serif !important;
            font-size: 1rem !important;
            line-height: 1 !important;
            cursor: pointer !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-poster-carousel-btn:hover,
        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-poster-carousel-btn:focus-visible {
            border-color: rgba(255, 226, 139, .76) !important;
            background: rgba(20, 35, 52, .96) !important;
            outline: none !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ledger-carousel-wrap {
            position: relative !important;
            width: 100% !important;
            max-width: 100% !important;
            overflow: hidden !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ledger-carousel-track {
            display: flex !important;
            grid-template-columns: none !important;
            gap: 18px !important;
            align-items: stretch !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            padding: 2px 3px 12px !important;
            overflow-x: auto !important;
            overflow-y: hidden !important;
            scroll-behavior: smooth !important;
            scroll-padding-inline: 3px !important;
            scroll-snap-type: x mandatory !important;
            scrollbar-width: none !important;
            transform: none !important;
            -webkit-overflow-scrolling: touch !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ledger-carousel-track::-webkit-scrollbar {
            display: none !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-oscars-winner-carousel-card {
            display: grid !important;
            flex: 0 0 calc((100% - 36px) / 3) !important;
            grid-template-rows: auto minmax(0, 1fr) !important;
            max-width: calc((100% - 36px) / 3) !important;
            min-width: 0 !important;
            min-height: clamp(260px, 26vw, 332px) !important;
            border-radius: 8px !important;
            scroll-snap-align: start !important;
            scroll-snap-stop: always !important;
            transition: border-color .2s ease, transform .2s ease, background .2s ease !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-oscars-winner-carousel-card:hover,
        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-oscars-winner-carousel-card:focus-within {
            border-color: rgba(255, 226, 139, .5) !important;
            background:
                radial-gradient(circle at top right, rgba(241, 204, 110, .14), transparent 42%),
                linear-gradient(180deg, rgba(18, 32, 49, .98), rgba(7, 15, 26, .98)) !important;
            transform: translateY(-2px) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-oscars-winner-carousel-card .lunara-ceremony-winner-media-link,
        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-oscars-winner-carousel-card .lunara-ceremony-winner-poster {
            aspect-ratio: 16 / 9 !important;
            max-height: none !important;
            min-height: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-oscars-winner-carousel-card .lunara-ceremony-winner-copy {
            display: grid !important;
            align-content: end !important;
            gap: 8px !important;
            padding: clamp(14px, 1.5vw, 18px) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-oscars-winner-carousel-card:not(.has-poster) {
            grid-template-rows: minmax(0, 1fr) !important;
            min-height: clamp(224px, 22vw, 286px) !important;
            padding: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-oscars-winner-carousel-card:not(.has-poster) .lunara-ceremony-winner-copy {
            align-content: center !important;
            min-height: 100% !important;
            padding: clamp(18px, 2.1vw, 26px) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ceremony-winner-category,
        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ceremony-winner-film {
            color: rgba(244, 239, 227, .78) !important;
            font-size: .82rem !important;
            font-weight: 600 !important;
            letter-spacing: .02em !important;
            line-height: 1.3 !important;
            margin: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ceremony-winner-name,
        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ceremony-winner-name a {
            color: #ffe28b !important;
            font-size: clamp(1.08rem, 1.55vw, 1.42rem) !important;
            font-weight: 700 !important;
            line-height: 1.08 !important;
            margin: 0 !important;
            overflow-wrap: anywhere !important;
            text-wrap: balance !important;
        }

        @media (max-width: 980px) {
            body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-oscars-winner-carousel-card {
                flex-basis: calc((100% - 16px) / 2) !important;
                max-width: calc((100% - 16px) / 2) !important;
                min-height: 248px !important;
            }
        }

        @media (max-width: 620px) {
            body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-home-section-header,
            body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-oscars-rotating-winners-actions {
                align-items: flex-start !important;
                justify-content: flex-start !important;
                margin-left: 0 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ledger-carousel-track {
                gap: 12px !important;
                padding-bottom: 10px !important;
                scroll-padding-inline: 0 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-oscars-winner-carousel-card {
                flex-basis: min(86vw, 328px) !important;
                max-width: min(86vw, 328px) !important;
                min-height: 236px !important;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-ledger-carousel-track {
                scroll-behavior: auto !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-oscars-winner-carousel-card {
                transition: none !important;
            }
        }

        @media (max-width: 520px) {
            body.home .lunara-home-section-head,
            body.home .lunara-home-section-head.is-with-summary,
            body.post-type-archive-review .lunara-archive-hero,
            body.post-type-archive-journal .lunara-archive-hero {
                width: min(100%, 324px) !important;
                max-width: 324px !important;
                margin-inline: auto !important;
                box-sizing: border-box !important;
                overflow: hidden !important;
            }

            body.home .lunara-home-section-title {
                width: 100% !important;
                max-width: 280px !important;
                font-size: clamp(1.88rem, 8.6vw, 2.22rem) !important;
                line-height: 1.07 !important;
                letter-spacing: 0 !important;
                overflow-wrap: anywhere !important;
                text-wrap: balance !important;
            }

            body.home .lunara-home-section-summary,
            body.post-type-archive-review .lunara-archive-hero-copy,
            body.post-type-archive-journal .lunara-archive-hero-copy {
                width: 100% !important;
                max-width: 324px !important;
                overflow-wrap: break-word !important;
                text-wrap: pretty !important;
            }

            body.post-type-archive-review .lunara-archive-hero-title,
            body.post-type-archive-journal .lunara-archive-hero-title {
                width: 100% !important;
                max-width: 324px !important;
                font-size: clamp(2.2rem, 9.4vw, 2.72rem) !important;
                line-height: 1.03 !important;
                letter-spacing: 0 !important;
                overflow-wrap: anywhere !important;
                text-wrap: balance !important;
            }
        }

        @media (max-width: 520px) {
            body.home {
                height: auto !important;
                min-height: 100% !important;
                overflow-x: hidden !important;
                overflow-y: auto !important;
                overscroll-behavior-y: auto !important;
                touch-action: pan-y pinch-zoom !important;
            }

            body.home #main-container,
            body.home main,
            body.home .ct-main,
            body.home .ct-content,
            body.home .lunara-front-page,
            body.home main.lunara-front-page {
                height: auto !important;
                min-height: 0 !important;
                max-height: none !important;
                overflow-x: clip !important;
                overflow-y: visible !important;
                touch-action: pan-y pinch-zoom !important;
            }

            body.home .lunara-home-section,
            body.home .lunara-latest-reviews-section,
            body.home .lunara-dispatches-section,
            body.home .lunara-home-slot-oscar-picks,
            body.home .lunara-home-slot-oscar-facts {
                overflow: visible !important;
                touch-action: pan-y pinch-zoom !important;
            }

            body.home,
            body.home #main-container,
            body.home main,
            body.home .ct-main,
            body.home .ct-content,
            body.home .lunara-front-page,
            body.home main.lunara-front-page {
                background-color: #07111d !important;
            }

            body.home .lunara-home-slot-dispatch,
            body.home .lunara-dispatches-section {
                background:
                    radial-gradient(circle at 14% 2%, rgba(224, 196, 129, 0.055), transparent 30%),
                    linear-gradient(180deg, #081421 0%, #07111d 54%, #050d18 100%) !important;
                background-color: #0a1520 !important;
                background-blend-mode: normal !important;
                color: #FAFBFC !important;
            }

            body.home .lunara-home-slot-dispatch > .lunara-home-section-head,
            body.home .lunara-dispatches-section > .lunara-home-section-head {
                background:
                    radial-gradient(circle at 0% 0%, rgba(224, 196, 129, 0.045), transparent 42%),
                    linear-gradient(180deg, #081421 0%, #07111d 100%) !important;
                background-color: #07111d !important;
            }

            body.home .lunara-home-slot-dispatch::before,
            body.home .lunara-home-slot-dispatch::after,
            body.home .lunara-dispatches-section::before,
            body.home .lunara-dispatches-section::after {
                opacity: 0.34 !important;
            }

            body.home .lunara-journal-home-deskbar {
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 8px !important;
                width: calc(100vw - 32px) !important;
                max-width: calc(100vw - 32px) !important;
                margin-inline: auto !important;
                overflow: visible !important;
                box-sizing: border-box !important;
            }

            body.home .lunara-journal-home-deskbar span {
                display: grid !important;
                grid-template-columns: minmax(0, auto) minmax(0, 1fr) !important;
                gap: 10px !important;
                align-items: baseline !important;
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                overflow: visible !important;
                text-align: left !important;
            }

            body.home .lunara-journal-home-deskbar span > :last-child,
            body.home .lunara-journal-home-deskbar span {
                overflow-wrap: anywhere !important;
                white-space: normal !important;
            }

            body.home .lunara-latest-reviews-section .lunara-review-grid,
            body.home .lunara-journal-home-grid,
            body.home .lunara-oscar-facts-track {
                width: calc(100vw - 32px) !important;
                max-width: calc(100vw - 32px) !important;
                margin-inline: auto !important;
                justify-items: stretch !important;
                overflow: visible !important;
                overflow-x: visible !important;
                overflow-y: visible !important;
                overscroll-behavior: auto !important;
                scroll-snap-type: none !important;
                touch-action: pan-y pinch-zoom !important;
            }

            body.home .lunara-oscar-picks-track {
                width: calc(100vw - 32px) !important;
                max-width: calc(100vw - 32px) !important;
                margin-inline: auto !important;
                justify-items: stretch !important;
                overflow-x: auto !important;
                overflow-y: hidden !important;
                overscroll-behavior-inline: contain !important;
                scroll-snap-type: x mandatory !important;
                touch-action: pan-x pan-y pinch-zoom !important;
                -webkit-overflow-scrolling: touch !important;
            }

            body.home .lunara-latest-reviews-section .lunara-review-grid-card,
            body.home .lunara-journal-home-card,
            body.home .lunara-oscar-pick-card,
            body.home .lunara-oscar-fact-card,
            body.home .lunara-ledger-story-card,
            body.home .lunara-lore-card {
                width: 100% !important;
                max-width: none !important;
                min-width: 0 !important;
                margin-inline: 0 !important;
                height: auto !important;
                min-height: 0 !important;
            }

            body.home .lunara-latest-reviews-section .lunara-review-grid-link,
            body.home .lunara-journal-home-card-link,
            body.home .lunara-oscar-pick-card-link,
            body.home .lunara-oscar-fact-card-link,
            body.home .lunara-ledger-story-link {
                height: auto !important;
                min-height: 0 !important;
                grid-template-rows: auto !important;
                touch-action: pan-y pinch-zoom !important;
            }

            body.home .lunara-oscar-pick-card-link {
                touch-action: pan-x pan-y pinch-zoom !important;
            }

            body.home .lunara-journal-home-card-media:has(.lunara-journal-home-card-placeholder),
            body.home .lunara-latest-reviews-section .lunara-review-grid-poster-wrap:has(.lunara-review-grid-poster-placeholder),
            body.home .lunara-oscar-pick-card-media:has(.lunara-oscar-pick-card-placeholder),
            body.home .lunara-oscar-fact-card:not(.has-poster) .lunara-oscar-fact-card-poster,
            body.home .lunara-ledger-story-poster:not(:has(img)):not(:has(.aat-entity-poster)),
            body.home .lunara-lore-card-poster:not(:has(img)):not(:has(.aat-entity-poster)) {
                display: none !important;
                aspect-ratio: auto !important;
                min-height: 0 !important;
                max-height: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            body.home .lunara-latest-reviews-section .lunara-review-grid-poster-wrap,
            body.home .lunara-journal-home-card-media,
            body.home .lunara-oscar-pick-card-media,
            body.home .lunara-oscar-fact-card-poster {
                max-height: 430px !important;
            }

            body.home .lunara-latest-reviews-section .lunara-review-grid-copy,
            body.home .lunara-journal-home-card-copy,
            body.home .lunara-oscar-pick-card-copy,
            body.home .lunara-oscar-fact-card-text,
            body.home .lunara-ledger-story-copy,
            body.home .lunara-lore-card .lunara-ledger-story-copy {
                display: grid !important;
                gap: 12px !important;
                grid-template-rows: none !important;
                min-height: 0 !important;
                height: auto !important;
                padding: 18px !important;
                align-content: start !important;
            }

            body.home .lunara-latest-reviews-section .lunara-review-grid-title,
            body.home .lunara-journal-home-card-title,
            body.home .lunara-oscar-pick-card-title,
            body.home .lunara-oscar-fact-card-title,
            body.home .lunara-ledger-story-title {
                display: block !important;
                min-height: 0 !important;
                max-height: none !important;
                -webkit-line-clamp: unset !important;
                -webkit-box-orient: initial !important;
                overflow: visible !important;
                text-overflow: clip !important;
                overflow-wrap: anywhere !important;
                word-break: normal !important;
            }

            body.home .lunara-latest-reviews-section .lunara-review-grid-excerpt,
            body.home .lunara-latest-reviews-section .lunara-review-grid-quote,
            body.home .lunara-journal-home-card-excerpt,
            body.home .lunara-oscar-pick-card-meta,
            body.home .lunara-oscar-fact-card-body,
            body.home .lunara-ledger-story-summary,
            body.home .lunara-ledger-story-categories {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                min-height: 0 !important;
                max-height: none !important;
                overflow: visible !important;
                white-space: normal !important;
                overflow-wrap: anywhere !important;
                word-break: normal !important;
                -webkit-line-clamp: unset !important;
                -webkit-box-orient: initial !important;
            }

            body.home .lunara-journal-home-card-meta,
            body.home .lunara-review-grid-footer,
            body.home .lunara-oscar-fact-card-foot {
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 8px !important;
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                overflow: visible !important;
            }

            body.home .lunara-journal-home-card-cta,
            body.home .lunara-review-grid-footer *,
            body.home .lunara-oscar-fact-card-foot * {
                max-width: 100% !important;
                white-space: normal !important;
                overflow-wrap: anywhere !important;
            }

            body.post-type-archive-review,
            body.post-type-archive-journal,
            body.lunara-oscars-portal-page,
            body.post-type-archive-review #main-container,
            body.post-type-archive-journal #main-container,
            body.lunara-oscars-portal-page #main-container,
            body.post-type-archive-review .ct-main,
            body.post-type-archive-journal .ct-main,
            body.lunara-oscars-portal-page .ct-main,
            body.post-type-archive-review .ct-content,
            body.post-type-archive-journal .ct-content,
            body.lunara-oscars-portal-page .ct-content {
                background-color: #07111d !important;
                overflow-x: clip !important;
            }

            body.post-type-archive-review .lunara-archive-page,
            body.post-type-archive-journal .lunara-archive-page,
            body.post-type-archive-review .lunara-review-archive-page,
            body.post-type-archive-journal .lunara-journal-archive-page,
            body.post-type-archive-review .lunara-editorial-archive-page,
            body.post-type-archive-journal .lunara-editorial-archive-page,
            body.lunara-oscars-portal-page .lunara-oscars-portal {
                background:
                    radial-gradient(circle at 10% 0%, rgba(224, 196, 129, 0.045), transparent 30%),
                    linear-gradient(180deg, #081421 0%, #07111d 54%, #050d18 100%) !important;
                background-color: #07111d !important;
                width: 100% !important;
                max-width: 100% !important;
                overflow-x: clip !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal {
                box-sizing: border-box !important;
                padding-left: 16px !important;
                padding-right: 16px !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal > .lunara-home-section,
            body.lunara-oscars-portal-page .lunara-oscars-portal-hero,
            body.lunara-oscars-portal-page .lunara-oscars-portal-links-section,
            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlights,
            body.lunara-oscars-portal-page .lunara-oscars-portal-titles,
            body.lunara-oscars-portal-page .lunara-oscars-portal-reviews,
            body.lunara-oscars-portal-page .lunara-oscars-portal-deep-cuts {
                width: 100% !important;
                max-width: 100% !important;
                margin-left: auto !important;
                margin-right: auto !important;
                box-sizing: border-box !important;
                overflow-x: clip !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-copy,
            body.lunara-oscars-portal-page .lunara-oscars-portal-actions,
            body.lunara-oscars-portal-page .lunara-oscars-portal-stat-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card,
            body.lunara-oscars-portal-page .lunara-home-section-header,
            body.lunara-oscars-portal-page .lunara-home-section-head {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                box-sizing: border-box !important;
                overflow-wrap: anywhere !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-copy,
            body.lunara-oscars-portal-page .lunara-oscars-portal-actions,
            body.lunara-oscars-portal-page .lunara-oscars-portal-stat-grid,
            body.lunara-oscars-portal-page .lunara-home-section-header,
            body.lunara-oscars-portal-page .lunara-home-section-head {
                width: min(100%, 320px) !important;
                max-width: 320px !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal h1,
            body.lunara-oscars-portal-page .lunara-oscars-portal h2,
            body.lunara-oscars-portal-page .lunara-oscars-portal h3,
            body.lunara-oscars-portal-page .lunara-oscars-portal p,
            body.lunara-oscars-portal-page .lunara-oscars-portal-link-card,
            body.lunara-oscars-portal-page .lunara-oscars-portal-link-card p,
            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-body,
            body.lunara-oscars-portal-page .lunara-home-section-summary {
                max-width: 100% !important;
                min-width: 0 !important;
                overflow: visible !important;
                overflow-wrap: anywhere !important;
                text-wrap: pretty !important;
                white-space: normal !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal h1 {
                font-size: clamp(1.72rem, 7.2vw, 2rem) !important;
                line-height: 1.05 !important;
                letter-spacing: 0 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal .lunara-home-hero-title {
                font-size: clamp(1.72rem, 7.2vw, 2rem) !important;
                line-height: 1.05 !important;
                letter-spacing: 0 !important;
                max-width: 100% !important;
                overflow-wrap: anywhere !important;
                text-wrap: balance !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal .lunara-home-hero-copy {
                font-size: .92rem !important;
                line-height: 1.58 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal h2 {
                font-size: clamp(1.65rem, 7.4vw, 2.08rem) !important;
                line-height: 1.08 !important;
                letter-spacing: 0 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy h2 {
                font-size: clamp(1.16rem, 5.6vw, 1.42rem) !important;
                line-height: 1.08 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-link-card h3 {
                font-size: clamp(1rem, 4.8vw, 1.18rem) !important;
                line-height: 1.12 !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-link-card p,
            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-body,
            body.lunara-oscars-portal-page .lunara-home-section-summary {
                font-size: .84rem !important;
                line-height: 1.5 !important;
            }

            body.post-type-archive-review .lunara-archive-hero,
            body.post-type-archive-journal .lunara-archive-hero {
                background:
                    radial-gradient(circle at 0% 0%, rgba(224, 196, 129, 0.04), transparent 42%),
                    linear-gradient(180deg, #081421 0%, #07111d 100%) !important;
                background-color: #07111d !important;
                border: 0 !important;
                color: #FAFBFC !important;
            }

            body.post-type-archive-review .lunara-review-archive-grid,
            body.post-type-archive-review .lunara-review-archive-uniform,
            body.post-type-archive-journal .lunara-journal-archive-grid,
            body.post-type-archive-journal .lunara-review-archive-uniform {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) !important;
                grid-auto-columns: minmax(0, 1fr) !important;
                gap: 16px !important;
                justify-items: stretch !important;
                width: min(100%, 320px) !important;
                max-width: 320px !important;
                margin-left: auto !important;
                margin-right: auto !important;
                overflow: visible !important;
                overflow-x: visible !important;
                box-sizing: border-box !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid,
            body.lunara-oscars-portal-page .lunara-oscars-research-card-grid {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) !important;
                grid-auto-columns: minmax(0, 1fr) !important;
                gap: 16px !important;
                justify-items: stretch !important;
                width: 100% !important;
                max-width: 100% !important;
                margin-left: auto !important;
                margin-right: auto !important;
                overflow: visible !important;
                overflow-x: visible !important;
                box-sizing: border-box !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid,
            body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid,
            body.lunara-oscars-portal-page .lunara-oscars-research-card-grid {
                width: min(100%, 320px) !important;
                max-width: 320px !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }

            body.post-type-archive-review .lunara-review-grid-card,
            body.post-type-archive-review .lunara-review-grid-link,
            body.post-type-archive-review .lunara-review-grid-copy,
            body.post-type-archive-journal .lunara-journal-archive-card,
            body.post-type-archive-journal .lunara-review-grid-card,
            body.post-type-archive-journal .lunara-review-grid-link,
            body.lunara-oscars-portal-page .lunara-oscars-portal-link-card,
            body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card,
            body.lunara-oscars-portal-page .lunara-oscars-portal-title-card,
            body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card,
            body.lunara-oscars-portal-page .lunara-oscars-research-card {
                width: 100% !important;
                max-width: none !important;
                min-width: 0 !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                box-sizing: border-box !important;
                overflow-wrap: anywhere !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) !important;
                gap: 14px !important;
                width: min(100%, 320px) !important;
                max-width: 320px !important;
                margin-left: auto !important;
                margin-right: auto !important;
                overflow: hidden !important;
            }

            body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
                width: 96px !important;
                max-width: 96px !important;
                justify-self: start !important;
            }

            body.post-type-archive-review .lunara-review-grid-card,
            body.post-type-archive-journal .lunara-journal-archive-card,
            body.post-type-archive-journal .lunara-review-grid-card {
                contain: none !important;
                transform: none !important;
                overflow: hidden !important;
                max-width: 100% !important;
            }

            body.post-type-archive-review .lunara-review-grid-link,
            body.post-type-archive-journal .lunara-review-grid-link {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) !important;
                grid-template-rows: auto !important;
                align-items: stretch !important;
                min-height: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                overflow: hidden !important;
            }

            body.post-type-archive-review .lunara-review-grid-copy,
            body.post-type-archive-journal .lunara-review-grid-copy {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                overflow: hidden !important;
                padding: 18px !important;
                box-sizing: border-box !important;
            }

            body.post-type-archive-review .lunara-review-grid-title,
            body.post-type-archive-review .lunara-review-grid-excerpt,
            body.post-type-archive-review .lunara-review-grid-quote,
            body.post-type-archive-review .lunara-review-grid-footer,
            body.post-type-archive-journal .lunara-review-grid-title,
            body.post-type-archive-journal .lunara-review-grid-excerpt,
            body.post-type-archive-journal .lunara-review-grid-footer,
            body.post-type-archive-journal .lunara-journal-archive-card-cta {
                max-width: 100% !important;
                min-width: 0 !important;
                overflow-wrap: anywhere !important;
                white-space: normal !important;
            }

            body.post-type-archive-review .lunara-review-grid-footer,
            body.post-type-archive-journal .lunara-review-grid-footer {
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 8px !important;
            }

            body.post-type-archive-review .lunara-review-grid-poster-wrap,
            body.post-type-archive-journal .lunara-review-grid-poster-wrap {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                max-height: 520px !important;
                margin: 0 !important;
            }

            body.post-type-archive-journal .lunara-review-grid-poster-wrap:has(.lunara-review-grid-poster-placeholder),
            body.post-type-archive-review .lunara-review-grid-poster-wrap:has(.lunara-review-grid-poster-placeholder) {
                display: none !important;
                aspect-ratio: auto !important;
                min-height: 0 !important;
                max-height: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                background: transparent !important;
            }
        }

    /* === Portal cinematic layer ===
       Emitted last inside this style block so it wins the cascade against the
       compact rules above at equal specificity. Turns the thumbnail-sized,
       text-first portal cards into poster-forward cards with real motion. */

    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
        align-content: start !important;
        gap: 12px !important;
        grid-template-columns: minmax(0, 1fr) !important;
        max-width: 344px !important;
        padding: 14px 14px 18px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
        aspect-ratio: 2 / 3 !important;
        border-radius: 14px !important;
        max-height: none !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 18px !important;
    }

    @media (min-width: 1121px) {
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }
    }

    @media (max-width: 640px) {
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid {
            grid-template-columns: minmax(0, 1fr) !important;
        }
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card {
        align-items: stretch !important;
        display: grid !important;
        gap: 0 !important;
        grid-template-columns: minmax(0, 1fr) !important;
        grid-template-rows: auto 1fr !important;
        overflow: hidden !important;
        padding: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-spotlight-media-link {
        display: block !important;
        max-width: none !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-spotlight-poster {
        aspect-ratio: 3 / 4 !important;
        background-position: center 22% !important;
        background-size: cover !important;
        border-radius: 0 !important;
        height: auto !important;
        max-height: none !important;
        max-width: none !important;
        min-height: 0 !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-spotlight-poster img,
    body.lunara-oscars-portal-page .lunara-oscars-spotlight-poster .aat-entity-poster {
        height: 100% !important;
        object-fit: cover !important;
        object-position: 50% 22% !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-spotlight-card-copy {
        padding: 13px 15px 16px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-title-card {
        align-items: stretch !important;
        gap: 0 !important;
        grid-template-columns: minmax(0, 1fr) !important;
        min-height: 0 !important;
        overflow: hidden !important;
        padding: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-title-media {
        aspect-ratio: 2 / 3 !important;
        border-radius: 0 !important;
        max-width: none !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-title-copy {
        padding: 12px 14px 15px !important;
    }

    /* Five title cards -> five columns on wide screens so the row never rags. */
    @media (min-width: 1121px) {
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
        }
    }

    @media (max-width: 1120px) and (min-width: 761px) {
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }
    }

    @media (max-width: 760px) {
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-card {
        align-items: stretch !important;
        gap: 0 !important;
        grid-template-columns: minmax(0, 1fr) !important;
        min-height: 0 !important;
        padding: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-media-link,
    body.lunara-oscars-portal-page .lunara-ceremony-winner-poster {
        aspect-ratio: 3 / 2 !important;
        border-radius: 0 !important;
        max-width: none !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-poster {
        background-position: center 30% !important;
        background-size: cover !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-poster img {
        object-position: 50% 30% !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-card:not(.has-poster) {
        padding: 14px !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-copy {
        padding: 13px 15px 16px !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-category {
        font-size: .62rem !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-name {
        font-size: 1.02rem !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-winner-carousel-track {
        grid-auto-columns: minmax(238px, 29%) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card.has-backdrop,
    body.lunara-oscars-portal-page .lunara-oscars-research-card.has-backdrop {
        align-content: end !important;
        background-position: center !important;
        background-size: cover !important;
        display: grid !important;
        isolation: isolate !important;
        min-height: 190px !important;
        position: relative !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card.has-backdrop::before,
    body.lunara-oscars-portal-page .lunara-oscars-research-card.has-backdrop::before {
        background: linear-gradient(180deg, rgba(7, 16, 27, .12), rgba(7, 16, 27, .5) 52%, rgba(4, 11, 19, .9)) !important;
        content: '' !important;
        inset: 0 !important;
        position: absolute !important;
        transition: background .3s ease !important;
        z-index: -1 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card {
        min-height: 128px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-value {
        font-size: clamp(1.45rem, 2.4vw, 1.85rem) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card,
    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-card,
    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card,
    body.lunara-oscars-portal-page .lunara-oscars-research-card,
    body.lunara-oscars-portal-page .lunara-ceremony-winner-card,
    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
        transition: transform .28s ease, border-color .28s ease, box-shadow .28s ease !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-spotlight-poster img,
    body.lunara-oscars-portal-page .lunara-oscars-spotlight-poster .aat-entity-poster,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-media img,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-media .aat-entity-poster,
    body.lunara-oscars-portal-page .lunara-ceremony-winner-poster img,
    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster img,
    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster .aat-entity-poster {
        transition: transform .65s cubic-bezier(.2, .7, .2, 1) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card:hover,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-card:hover,
    body.lunara-oscars-portal-page .lunara-ceremony-winner-card:hover,
    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card:hover,
    body.lunara-oscars-portal-page .lunara-oscars-research-card:hover,
    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card:hover,
    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card:hover {
        border-color: rgba(225, 197, 126, .55) !important;
        box-shadow: 0 18px 40px rgba(0, 0, 0, .35) !important;
        transform: translateY(-4px) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card:hover .lunara-oscars-spotlight-poster img,
    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card:hover .lunara-oscars-spotlight-poster .aat-entity-poster,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-card:hover .lunara-oscars-portal-title-media img,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-card:hover .lunara-oscars-portal-title-media .aat-entity-poster,
    body.lunara-oscars-portal-page .lunara-ceremony-winner-card:hover .lunara-ceremony-winner-poster img,
    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card:hover .lunara-oscars-portal-feature-poster img,
    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card:hover .lunara-oscars-portal-feature-poster .aat-entity-poster {
        transform: scale(1.055) !important;
    }

    @media (prefers-reduced-motion: no-preference) {
        body.lunara-oscars-portal-page .lunara-oscars-reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity .7s ease, transform .7s cubic-bezier(.2, .7, .2, 1);
        }

        body.lunara-oscars-portal-page .lunara-oscars-reveal.is-inview {
            opacity: 1;
            transform: none;
        }
    }

    /* === Reviews archive refit layer ===
       Kills the misshapen negative space on the Reviews page. Emitted at the
       end of this critical block so it is the final word in the cascade. */

    /* 1. Companion suite: the section header was a side panel stretched to the
          full height of the rail (a ~1100px mostly-empty box). Make it a slim
          band on top and give the rail the full width below, three cards up. */
    body .lunara-review-archive-page .lunara-review-archive-support-suite {
        grid-template-columns: minmax(0, 1fr) !important;
        gap: 14px !important;
    }

    body .lunara-review-archive-page .lunara-review-archive-support-head {
        align-content: start !important;
        background: transparent !important;
        border: 0 !important;
        border-bottom: 1px solid rgba(224, 196, 129, .16) !important;
        border-radius: 0 !important;
        gap: 4px !important;
        margin-top: 0 !important;
        min-height: 0 !important;
        padding: 0 0 12px !important;
    }

    body .lunara-review-archive-page .lunara-review-archive-support-head .lunara-section-title {
        max-width: 34ch !important;
    }

    body .lunara-review-archive-page .lunara-review-archive-rail-item {
        flex-basis: calc((100% - 2 * var(--lunara-reviews-archive-rail-gap, 16px)) / 3) !important;
    }

    @media (max-width: 980px) {
        body .lunara-review-archive-page .lunara-review-archive-rail-item {
            flex-basis: calc((100% - var(--lunara-reviews-archive-rail-gap, 16px)) / 2) !important;
        }
    }

    @media (max-width: 640px) {
        body .lunara-review-archive-page .lunara-review-archive-rail-item {
            flex-basis: 86% !important;
        }
    }

    /* 2. Uniform cards: pin the updated line to the bottom and clamp excerpts
          so short copy no longer leaves interior voids. */
    body .lunara-review-archive-page .lunara-review-grid-link {
        display: flex !important;
        flex-direction: column !important;
        height: 100% !important;
    }

    body .lunara-review-archive-page .lunara-review-grid-copy {
        display: flex !important;
        flex: 1 1 auto !important;
        flex-direction: column !important;
        min-height: 0 !important;
    }

    body .lunara-review-archive-page .lunara-review-grid-copy .lunara-review-grid-updated {
        margin-top: auto !important;
        padding-top: 12px !important;
    }

    body .lunara-review-archive-page .lunara-review-grid-excerpt {
        display: -webkit-box !important;
        -webkit-box-orient: vertical !important;
        -webkit-line-clamp: 5 !important;
        overflow: hidden !important;
    }

    /* 3. Hero: center the copy against the command panel instead of letting it
          float above a void. */
    body .lunara-review-archive-page .lunara-review-archive-hero-shell {
        align-items: center !important;
    }

    body .lunara-review-archive-page .lunara-review-archive-hero-copy-wrap {
        align-content: center !important;
    }

    /* 4. Sort toolbar: one slim row instead of a heavy boxed section. */
    body .lunara-review-archive-page .lunara-review-archive-toolbar {
        align-items: center !important;
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 14px !important;
        justify-content: space-between !important;
    }

    body .lunara-review-archive-page .lunara-review-archive-toolbar-head .lunara-section-title {
        font-size: clamp(1.05rem, 1.5vw, 1.3rem) !important;
        max-width: none !important;
        text-transform: none !important;
    }

    body .lunara-review-archive-page .lunara-review-archive-utility {
        padding-bottom: clamp(14px, 1.8vw, 20px) !important;
        padding-top: clamp(14px, 1.8vw, 20px) !important;
    }
    </style>
    <script id="lunara-card-image-hydrator">
        (function () {
            var observer = null;

            function shouldHydrateNow(img) {
                if (!img) return false;

                var loading = (img.getAttribute('loading') || '').toLowerCase();

                if (loading === 'eager' || img.getAttribute('fetchpriority') === 'high') {
                    return true;
                }

                if (!img.getBoundingClientRect) {
                    return false;
                }

                var rect = img.getBoundingClientRect();
                var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;

                return rect.top < viewportHeight * 1.35 && rect.bottom > -120;
            }

            function sanitizeSrcset(srcset) {
                srcset = (srcset || '').trim();
                if (!srcset) return '';

                return srcset.split(/,\s*(?=(?:https?:)?\/\/|\/)/).map(function (candidate) {
                    candidate = (candidate || '').trim();
                    if (!candidate) return '';

                    var decoded = candidate.replace(/&amp;/g, '&');
                    if (/(?:[?&;](?:resize|fit)=0(?:%2c|,)nan)/i.test(decoded) || /(?:[?&;](?:w|h)=0(?:&|$))/i.test(decoded)) {
                        return '';
                    }

                    return (/\s+\d+w$/.test(candidate) || /\s+\d+(?:\.\d+)?x$/.test(candidate)) ? candidate : '';
                }).filter(Boolean).join(', ');
            }

            function installSrcsetGuard() {
                if (window.lunaraSrcsetGuardInstalled || !window.Element) return;
                window.lunaraSrcsetGuardInstalled = true;

                var nativeSetAttribute = Element.prototype.setAttribute;
                Element.prototype.setAttribute = function (name, value) {
                    if (typeof name === 'string' && name.toLowerCase() === 'srcset') {
                        value = sanitizeSrcset(String(value || ''));
                    }

                    return nativeSetAttribute.call(this, name, value);
                };

                [window.HTMLImageElement, window.HTMLSourceElement].forEach(function (Constructor) {
                    if (!Constructor || !Constructor.prototype) return;

                    var descriptor = Object.getOwnPropertyDescriptor(Constructor.prototype, 'srcset');
                    if (!descriptor || !descriptor.set || !descriptor.get) return;

                    Object.defineProperty(Constructor.prototype, 'srcset', {
                        configurable: true,
                        enumerable: descriptor.enumerable,
                        get: function () {
                            return descriptor.get.call(this);
                        },
                        set: function (value) {
                            descriptor.set.call(this, sanitizeSrcset(String(value || '')));
                        }
                    });
                });
            }

            function sanitizeImageSrcset(node) {
                if (!node || !node.getAttribute || !node.setAttribute) return;

                var currentSrcset = node.getAttribute('srcset') || '';
                if (!currentSrcset) return;

                var sanitizedSrcset = sanitizeSrcset(currentSrcset);
                if (sanitizedSrcset && sanitizedSrcset !== currentSrcset) {
                    node.setAttribute('srcset', sanitizedSrcset);
                } else if (!sanitizedSrcset) {
                    node.removeAttribute('srcset');
                    node.removeAttribute('sizes');
                }
            }

            function sanitizeDocumentSrcsets(root) {
                root = root || document;

                if (root.matches && root.matches('img[srcset], source[srcset]')) {
                    sanitizeImageSrcset(root);
                }

                if (root.querySelectorAll) {
                    root.querySelectorAll('img[srcset], source[srcset]').forEach(sanitizeImageSrcset);
                }
            }

            installSrcsetGuard();

            function hydrateImage(img) {
                if (!img) return;
                sanitizeImageSrcset(img);

                var dataSrc = img.getAttribute('data-src') || img.getAttribute('data-lazy-src') || '';
                var dataSrcset = img.getAttribute('data-srcset') || img.getAttribute('data-lazy-srcset') || '';
                var currentSrc = img.getAttribute('src') || '';

                if (dataSrcset && !img.getAttribute('srcset')) {
                    var sanitizedSrcset = sanitizeSrcset(dataSrcset);
                    if (sanitizedSrcset) {
                        img.setAttribute('srcset', sanitizedSrcset);
                    } else {
                        img.removeAttribute('data-srcset');
                        img.removeAttribute('data-lazy-srcset');
                    }
                }

                if (dataSrc && (!currentSrc || currentSrc.indexOf('data:image/gif') === 0)) {
                    img.setAttribute('src', dataSrc);
                }

                if (img.complete && img.naturalWidth > 1) {
                    img.classList.add('lunara-img-loaded');
                } else {
                    img.addEventListener('load', function () {
                        img.classList.add('lunara-img-loaded');
                    }, { once: true });
                }
            }

            function observeImage(img) {
                if (!img || img.dataset.lunaraHydratorObserved === '1') return;

                if (shouldHydrateNow(img)) {
                    hydrateImage(img);
                    return;
                }

                if (!('IntersectionObserver' in window)) {
                    return;
                }

                if (!observer) {
                    observer = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (!entry.isIntersecting) return;
                            hydrateImage(entry.target);
                            observer.unobserve(entry.target);
                        });
                    }, { rootMargin: '640px 0px' });
                }

                img.dataset.lunaraHydratorObserved = '1';
                observer.observe(img);
            }

            function hydrateCards() {
                sanitizeDocumentSrcsets(document);

                document.querySelectorAll([
                    '.lunara-review-grid-poster',
                    '.lunara-review-feature-image',
                    '.lunara-poster-card-image',
                    '.lunara-journal-home-card-image',
                    '.lunara-dispatch-archive-thumb',
                    '.lunara-dispatch-lead-image',
                    '.lunara-oscar-pick-card-image',
                    '.lunara-oscar-fact-card-poster-image',
                    '.aat-entity-poster',
                    '.aat-filmography-poster',
                    '.aat-winner-circle-photo',
                    '.aat-winner-circle-media img',
                    '.aat-hub-spotlight-media img',
                    '.aat-related-review-image'
                ].join(',')).forEach(observeImage);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', hydrateCards);
            } else {
                hydrateCards();
            }

            if (window.MutationObserver) {
                new MutationObserver(function (mutations) {
                    var needsHydration = false;

                    mutations.forEach(function (mutation) {
                        if (mutation.type === 'attributes') {
                            sanitizeImageSrcset(mutation.target);
                            return;
                        }

                        needsHydration = true;
                        mutation.addedNodes.forEach(function (node) {
                            if (node.nodeType === 1) {
                                sanitizeDocumentSrcsets(node);
                            }
                        });
                    });

                    if (needsHydration) {
                        hydrateCards();
                    }
                }).observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['srcset', 'data-srcset', 'data-lazy-srcset'],
                    childList: true,
                    subtree: true
                });
            }
        }());
    </script>
    <style id="lunara-home-journal-sizing-polish">
        body.home .lunara-latest-reviews-section .lunara-review-grid {
            grid-template-columns: repeat(3, minmax(320px, 1fr)) !important;
            gap: clamp(20px, 2.4vw, 30px) !important;
        }

        body.home .lunara-latest-reviews-section .lunara-review-grid-card,
        body.home .lunara-latest-reviews-section .lunara-review-grid-link {
            min-width: 0 !important;
            width: 100% !important;
        }

        body.home .lunara-journal-home-grid {
            grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr) !important;
            gap: clamp(22px, 2.6vw, 34px) !important;
        }

        body.home .lunara-journal-home-card-media {
            aspect-ratio: 16 / 9 !important;
            max-height: clamp(280px, 30vw, 460px) !important;
        }

        body.home .lunara-journal-home-card-image,
        body.home .lunara-journal-home-card-media img,
        body.post-type-archive-journal .lunara-review-grid-poster,
        body.post-type-archive-journal .lunara-review-grid-poster-wrap img {
            image-rendering: auto !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-grid {
            width: min(100%, 1120px) !important;
            max-width: calc(100vw - 32px) !important;
            margin-inline: auto !important;
            display: grid !important;
            grid-template-columns: repeat(3, minmax(320px, 1fr)) !important;
            gap: clamp(22px, 2.4vw, 32px) !important;
            align-items: stretch !important;
            justify-items: stretch !important;
            overflow: visible !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card {
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card.is-lead {
            grid-column: span 2 !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card.is-lead .lunara-review-grid-link {
            display: grid !important;
            grid-template-columns: minmax(0, .98fr) minmax(0, 1.02fr) !important;
            grid-template-rows: minmax(0, 1fr) !important;
            min-height: 100% !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card.is-lead .lunara-review-grid-poster-wrap {
            height: 100% !important;
            min-height: clamp(320px, 36vw, 520px) !important;
            border-radius: 18px 0 0 18px !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card.is-lead .lunara-review-grid-copy {
            padding: clamp(22px, 3vw, 34px) !important;
        }

        body.post-type-archive-journal .lunara-review-grid-poster-wrap {
            aspect-ratio: 16 / 9 !important;
            width: 100% !important;
            max-height: none !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card.is-text-brief .lunara-review-grid-link {
            min-height: clamp(320px, 34vw, 430px) !important;
            background:
                radial-gradient(circle at 18% 18%, rgba(224,196,129,.14), transparent 34%),
                linear-gradient(135deg, rgba(13,29,48,.96), rgba(6,16,28,.98)) !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-card.is-text-brief .lunara-review-grid-copy {
            display: flex !important;
            min-height: 100% !important;
            flex-direction: column !important;
            justify-content: center !important;
            padding: clamp(24px, 3vw, 36px) !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-retention {
            order: 6 !important;
            width: min(100%, 1120px) !important;
            max-width: calc(100vw - 32px) !important;
            margin: clamp(26px, 4vw, 48px) auto 0 !important;
            padding: clamp(20px, 3vw, 32px) !important;
            display: grid !important;
            grid-template-columns: minmax(0, .78fr) minmax(0, 1.22fr) !important;
            gap: clamp(18px, 2.4vw, 30px) !important;
            align-items: stretch !important;
            border: 1px solid rgba(224,196,129,.24) !important;
            border-radius: 18px !important;
            background:
                linear-gradient(135deg, rgba(10,24,41,.92), rgba(18,36,55,.72)),
                radial-gradient(circle at 90% 10%, rgba(224,196,129,.14), transparent 34%) !important;
            box-shadow: 0 24px 70px rgba(0,0,0,.25) !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-slot-pagination {
            order: 7 !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-retention-head {
            display: flex !important;
            min-width: 0 !important;
            flex-direction: column !important;
            justify-content: center !important;
            gap: 8px !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-retention-grid {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 12px !important;
            min-width: 0 !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-retention-card {
            display: flex !important;
            min-width: 0 !important;
            min-height: 185px !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            gap: 12px !important;
            padding: 16px !important;
            border: 1px solid rgba(224,196,129,.18) !important;
            border-radius: 14px !important;
            background: rgba(6,17,30,.74) !important;
            color: rgba(233,240,247,.88) !important;
            text-decoration: none !important;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.04) !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-retention-card:hover,
        body.post-type-archive-journal .lunara-journal-archive-retention-card:focus-visible {
            border-color: rgba(224,196,129,.52) !important;
            transform: translateY(-2px) !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-retention-kicker {
            color: rgba(224,196,129,.9) !important;
            font-size: .68rem !important;
            font-weight: 800 !important;
            line-height: 1.1 !important;
            letter-spacing: 0 !important;
            text-transform: uppercase !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-retention-card strong {
            color: rgba(255,243,212,.98) !important;
            font-family: var(--theme-font-family, inherit) !important;
            font-size: clamp(1rem, 1.35vw, 1.18rem) !important;
            line-height: 1.1 !important;
        }

        body.post-type-archive-journal .lunara-journal-archive-retention-card span:last-child {
            color: rgba(226,235,243,.72) !important;
            font-size: .86rem !important;
            line-height: 1.45 !important;
        }

        @media (max-width: 1180px) {
            body.home .lunara-latest-reviews-section .lunara-review-grid,
            body.post-type-archive-journal .lunara-journal-archive-grid {
                grid-template-columns: repeat(2, minmax(300px, 1fr)) !important;
            }

            body.post-type-archive-journal .lunara-journal-archive-card.is-lead {
                grid-column: 1 / -1 !important;
            }
        }

        @media (max-width: 820px) {
            body.home .lunara-latest-reviews-section .lunara-review-grid,
            body.home .lunara-journal-home-grid,
            body.post-type-archive-journal .lunara-journal-archive-grid {
                width: min(100%, calc(100vw - 32px)) !important;
                max-width: calc(100vw - 32px) !important;
                margin-inline: auto !important;
                justify-items: stretch !important;
                overflow: visible !important;
            }

            body.home .lunara-latest-reviews-section .lunara-review-grid-card,
            body.home .lunara-journal-home-card,
            body.post-type-archive-journal .lunara-journal-archive-card,
            body.post-type-archive-journal .lunara-review-grid-card {
                width: 100% !important;
                max-width: none !important;
                margin-inline: 0 !important;
            }

            body.post-type-archive-journal .lunara-journal-archive-card.is-lead .lunara-review-grid-link {
                grid-template-columns: minmax(0, 1fr) !important;
                grid-template-rows: auto 1fr !important;
            }

            body.post-type-archive-journal .lunara-journal-archive-card.is-lead .lunara-review-grid-poster-wrap {
                min-height: 0 !important;
                border-radius: 18px 18px 0 0 !important;
            }

            body.post-type-archive-journal .lunara-journal-archive-retention {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }

        @media (max-width: 640px) {
            body.home .lunara-latest-reviews-section .lunara-review-grid,
            body.home .lunara-journal-home-grid,
            body.post-type-archive-journal .lunara-journal-archive-grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }

            body.post-type-archive-journal .lunara-journal-archive-retention {
                width: min(100%, calc(100vw - 32px)) !important;
                max-width: calc(100vw - 32px) !important;
                padding: 18px !important;
            }

            body.post-type-archive-journal .lunara-journal-archive-retention-grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }

            body.post-type-archive-journal .lunara-journal-archive-retention-card {
                min-height: 0 !important;
            }
        }
    </style>
    <?php do_action( 'blocksy:head:end' ); ?>
</head>

<?php
$show_lunara_header_search = is_front_page() || is_page_template( 'page-oscars.php' ) || is_page( 'oscars' );
$header_search_placeholder = __( 'Search reviews, titles, filmmakers, Oscar categories', 'lunara-film' );
$header_search_id          = 'lunara-header-search-input';
$lunara_strip_search_menu_item = null;
$lunara_strip_search_menu_markup = null;

if ( is_page_template( 'page-oscars.php' ) || is_page( 'oscars' ) ) {
    $header_search_placeholder = __( 'Search Oscar titles, people, ceremonies, categories', 'lunara-film' );
    $header_search_id          = 'lunara-header-oscars-search-input';
}

if ( $show_lunara_header_search ) {
    ob_start(
        static function ( $html ) {
            return preg_replace(
                array(
                    '#<li\b[^>]*menu-item-27569[^>]*>\s*<a\b[^>]*href="[^"]*/search/?(?:\?[^"]*)?"[^>]*>Search(?:<span class="ct-menu-badge">New</span>)?</a>\s*</li>#i',
                    '#<li\b[^>]*>\s*<a\b[^>]*href="[^"]*/search/?(?:\?[^"]*)?"[^>]*>Search(?:<span class="ct-menu-badge">New</span>)?</a>\s*</li>#i',
                ),
                '',
                $html
            );
        }
    );

    $lunara_strip_search_menu_item = static function ( $items ) {
        $search_url = untrailingslashit( home_url( '/search/' ) );

        foreach ( $items as $index => $item ) {
            $item_url   = isset( $item->url ) ? untrailingslashit( (string) $item->url ) : '';
            $item_title = isset( $item->title ) ? trim( wp_strip_all_tags( (string) $item->title ) ) : '';

            if ( $search_url === $item_url || 0 === strcasecmp( $item_title, 'Search' ) ) {
                unset( $items[ $index ] );
            }
        }

        return array_values( $items );
    };

    add_filter( 'wp_nav_menu_objects', $lunara_strip_search_menu_item, 20, 1 );

    $lunara_strip_search_menu_markup = static function ( $items_html ) {
        return preg_replace(
            '#<li\b[^>]*>\s*<a\b[^>]*href="[^"]*/search/?(?:\?[^"]*)?"[^>]*>[\s\S]*?</a>\s*</li>#i',
            '',
            $items_html
        );
    };

    add_filter( 'wp_nav_menu_items', $lunara_strip_search_menu_markup, 20, 1 );
}

ob_start();
if ( function_exists( 'blocksy_output_header' ) ) {
    blocksy_output_header();
}
$global_header = ob_get_clean();

if ( null !== $lunara_strip_search_menu_item ) {
    remove_filter( 'wp_nav_menu_objects', $lunara_strip_search_menu_item, 20 );
}

if ( null !== $lunara_strip_search_menu_markup ) {
    remove_filter( 'wp_nav_menu_items', $lunara_strip_search_menu_markup, 20 );
}

if ( $show_lunara_header_search ) {
    $search_menu_url = home_url( '/search/' );

    $global_header = str_replace(
        array(
            '<li id="menu-item-27569" class="menu-item menu-item-type-post_type menu-item-object_page menu-item-27569"><a href="' . esc_url( $search_menu_url ) . '" class="ct-menu-link">Search<span class="ct-menu-badge">New</span></a></li>',
            '<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-27569"><a href="' . esc_url( $search_menu_url ) . '" class="ct-menu-link">Search<span class="ct-menu-badge">New</span></a></li>',
            '<li id="menu-item-27569" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-27569"><a href="' . esc_url( $search_menu_url ) . '" class="ct-menu-link">Search<span class="ct-menu-badge">New</span></a></li>',
        ),
        '',
        $global_header
    );

    $global_header = preg_replace(
        '#<li\b[^>]*menu-item-27569[^>]*>[\s\S]*?</li>#i',
        '',
        $global_header
    );

    $global_header = preg_replace(
        '#<li\b[^>]*>\s*<a\b[^>]*href="[^"]*/search/?(?:\?[^"]*)?"[^>]*>[\s\S]*?</a>\s*</li>#i',
        '',
        $global_header
    );

    ob_start();
    ?>
    <form role="search" method="get" class="lunara-search-form lunara-inline-header-search" action="<?php echo esc_url( function_exists( 'lunara_search_command_url' ) ? lunara_search_command_url() : home_url( '/' ) ); ?>">
        <label class="screen-reader-text" for="<?php echo esc_attr( $header_search_id ); ?>"><?php esc_html_e( 'Search for:', 'lunara-film' ); ?></label>
        <input id="<?php echo esc_attr( $header_search_id ); ?>" type="search" class="lunara-search-input" placeholder="<?php echo esc_attr( $header_search_placeholder ); ?>" value="<?php echo esc_attr( isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : get_search_query() ); ?>" name="<?php echo esc_attr( function_exists( 'lunara_search_command_url' ) ? 'q' : 's' ); ?>" />
        <button type="submit" class="lunara-btn lunara-btn-primary"><?php esc_html_e( 'Search', 'lunara-film' ); ?></button>
    </form>
    <?php
    $inline_header_search = trim( ob_get_clean() );

    if ( '' !== $inline_header_search ) {
        $global_header = preg_replace(
            '/(<nav\s+id="header-menu-1"[\s\S]*?<\/nav>)/',
            '$1' . $inline_header_search,
            $global_header,
            1
        );
    }
}

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
