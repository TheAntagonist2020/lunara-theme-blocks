<?php
/**
 * Frontend — footer, navigation, content filters, search, and animations.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lunara_front_page_document_title( $title ) {
    if ( is_front_page() ) {
        return __( 'Lunara Film - Film Criticism and a Living Oscar Ledger', 'lunara-film' );
    }

    return $title;
}
add_filter( 'pre_get_document_title', 'lunara_front_page_document_title', 99 );

function lunara_get_home_identity_logo_id() {
    $home_logo_id = absint( get_option( 'lunara_home_identity_logo_id', 0 ) );

    if ( $home_logo_id ) {
        return $home_logo_id;
    }

    return absint( get_theme_mod( 'custom_logo' ) );
}

function lunara_home_brand_number_setting( $key, $default, $min, $max ) {
    $value = absint( get_theme_mod( $key, $default ) );

    if ( $value < $min ) {
        return absint( $min );
    }

    if ( $value > $max ) {
        return absint( $max );
    }

    return $value;
}

function lunara_home_select_setting( $key, $default, $allowed ) {
    $allowed = array_map( 'sanitize_key', (array) $allowed );
    $value   = sanitize_key( (string) get_theme_mod( $key, $default ) );

    if ( ! in_array( $value, $allowed, true ) ) {
        return sanitize_key( (string) $default );
    }

    return $value;
}

function lunara_render_home_front_door() {
    $reviews_url = get_post_type_archive_link( 'review' ) ?: home_url( '/reviews/' );
    $journal_url = get_post_type_archive_link( 'journal' ) ?: home_url( '/journal/' );
    $logo_id     = lunara_get_home_identity_logo_id();
    $logo_html   = '';
    $density     = lunara_home_select_setting( 'lunara_home_front_door_density', 'editorial', array( 'compact', 'editorial', 'showcase' ) );
    $prominence  = lunara_home_select_setting( 'lunara_home_route_card_prominence', 'strong', array( 'quiet', 'standard', 'strong' ) );

    if ( $logo_id ) {
        $logo_html = wp_get_attachment_image(
            $logo_id,
            'full',
            false,
            array(
                'class'         => 'lunara-home-masthead-logo skip-lazy no-lazy',
                'loading'       => 'eager',
                'decoding'      => 'async',
                'fetchpriority' => 'high',
                'alt'           => '',
            )
        );
    }

    $routes = array(
        array(
            'label' => __( 'Criticism', 'lunara-film' ),
            'title' => __( 'Read Reviews', 'lunara-film' ),
            'copy'  => __( 'Arguments, scores, trailers, Debrief notes, and spoiler files when the film needs a second door.', 'lunara-film' ),
            'url'   => $reviews_url,
        ),
        array(
            'label' => __( 'Journal', 'lunara-film' ),
            'title' => __( 'Open Journal', 'lunara-film' ),
            'copy'  => __( 'Industry movement, trailer files, quick reactions, and larger work from the desk.', 'lunara-film' ),
            'url'   => $journal_url,
        ),
        array(
            'label' => __( 'Oscar Ledger', 'lunara-film' ),
            'title' => __( 'Explore Ledger', 'lunara-film' ),
            'copy'  => __( 'Films, people, categories, and ceremonies connected across the record.', 'lunara-film' ),
            'url'   => home_url( '/oscars/' ),
        ),
    );

    ob_start();
    ?>
    <section class="lunara-home-masthead is-density-<?php echo esc_attr( $density ); ?> is-route-<?php echo esc_attr( $prominence ); ?>" aria-labelledby="lunara-home-masthead-title">
        <div class="lunara-home-masthead-panel">
            <div class="lunara-home-masthead-identity">
                <p class="lunara-home-masthead-kicker"><?php esc_html_e( 'Film criticism and Oscar record', 'lunara-film' ); ?></p>
                <h1 id="lunara-home-masthead-title" class="screen-reader-text lunara-screen-reader-text"><?php esc_html_e( 'Lunara Film', 'lunara-film' ); ?></h1>
                <div class="lunara-home-masthead-logo-frame" aria-hidden="true">
                    <?php
                    if ( $logo_html ) {
                        echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    } else {
                        ?>
                        <span class="lunara-home-masthead-logo-fallback"><?php esc_html_e( 'Lunara Film', 'lunara-film' ); ?></span>
                        <?php
                    }
                    ?>
                </div>
                <p class="lunara-home-masthead-dek"><?php esc_html_e( 'Reviews, Journal files, and the Oscar Ledger, edited as one publication.', 'lunara-film' ); ?></p>
                <a class="lunara-home-masthead-standard" href="<?php echo esc_url( home_url( '/editorial-policy/' ) ); ?>"><?php esc_html_e( 'Editorial policy', 'lunara-film' ); ?></a>
            </div>
            <nav class="lunara-home-masthead-routes" aria-label="<?php esc_attr_e( 'Lunara front door', 'lunara-film' ); ?>">
                <?php foreach ( $routes as $route ) : ?>
                    <a class="lunara-home-masthead-route" href="<?php echo esc_url( $route['url'] ); ?>">
                        <span class="lunara-home-masthead-route-label"><?php echo esc_html( $route['label'] ); ?></span>
                        <strong><?php echo esc_html( $route['title'] ); ?></strong>
                        <span><?php echo esc_html( $route['copy'] ); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </section>
    <?php

    return (string) ob_get_clean();
}

function lunara_home_front_door_css() {
    if ( ! is_front_page() ) {
        return;
    }
    $desktop_logo_width   = lunara_home_brand_number_setting( 'lunara_home_logo_desktop_max_width', 1180, 520, 1600 );
    $desktop_logo_height  = lunara_home_brand_number_setting( 'lunara_home_logo_desktop_max_height', 312, 140, 420 );
    $tablet_logo_width    = min( $desktop_logo_width, 1040 );
    $mobile_logo_width    = lunara_home_brand_number_setting( 'lunara_home_logo_mobile_max_width', 720, 280, 920 );
    $mobile_logo_height   = lunara_home_brand_number_setting( 'lunara_home_logo_mobile_max_height', 148, 88, 240 );
    $identity_logo_gap    = lunara_home_brand_number_setting( 'lunara_home_logo_vertical_gap', 20, 8, 48 );
    $masthead_top_pad     = lunara_home_brand_number_setting( 'lunara_home_masthead_top_padding', 36, 16, 72 );
    $masthead_bottom_pad  = lunara_home_brand_number_setting( 'lunara_home_masthead_bottom_padding', 32, 12, 70 );
    $masthead_bottom_gap  = lunara_home_brand_number_setting( 'lunara_home_masthead_bottom_gap', 26, 10, 72 );
    $route_card_min       = lunara_home_brand_number_setting( 'lunara_home_route_card_min_height', 126, 88, 190 );
    $mobile_top_pad       = max( 18, min( 30, $masthead_top_pad ) );
    $mobile_bottom_pad    = max( 16, min( 28, $masthead_bottom_pad ) );
    ?>
    <style id="lunara-home-front-door-css">
    body.home .lunara-home-masthead{--lunara-home-masthead-top-pad:<?php echo esc_html( $masthead_top_pad ); ?>px;--lunara-home-masthead-bottom-pad:<?php echo esc_html( $masthead_bottom_pad ); ?>px;--lunara-home-masthead-gap:<?php echo esc_html( $masthead_bottom_gap ); ?>px;--lunara-home-route-card-min:<?php echo esc_html( $route_card_min ); ?>px;width:100%;margin:0 auto var(--lunara-home-masthead-gap);box-sizing:border-box;}
    body.home .lunara-home-masthead-panel{position:relative;display:grid;gap:clamp(16px,2.4vw,30px);align-items:center;overflow:hidden;padding:var(--lunara-home-masthead-top-pad) clamp(18px,5vw,76px) var(--lunara-home-masthead-bottom-pad);border-bottom:1px solid rgba(201,169,97,.24);background:radial-gradient(circle at 78% -20%,rgba(201,169,97,.18),transparent 32%),linear-gradient(180deg,rgba(5,12,21,.98),rgba(9,21,34,.98) 56%,rgba(6,14,24,.98));}
    body.home .lunara-home-masthead-panel::before{content:"";position:absolute;inset:0;pointer-events:none;background:linear-gradient(90deg,rgba(201,169,97,.14),transparent 19%,transparent 81%,rgba(244,239,227,.08));opacity:.74;}
    body.home .lunara-home-masthead-panel::after{content:"";position:absolute;left:clamp(18px,5vw,76px);right:clamp(18px,5vw,76px);bottom:clamp(82px,8vw,116px);height:1px;background:linear-gradient(90deg,transparent,rgba(224,196,129,.38),transparent);opacity:.9;}
    body.home .lunara-home-masthead-identity,body.home .lunara-home-masthead-routes{position:relative;z-index:1;}
    body.home .lunara-home-masthead-identity{display:grid;justify-items:center;gap:clamp(12px,1.8vw,<?php echo esc_html( $identity_logo_gap ); ?>px);text-align:center;}
    body.home .lunara-home-masthead-kicker{margin:0;color:var(--lunara-gold-light,#e0c481);font-size:clamp(.72rem,.82vw,.86rem);font-weight:800;letter-spacing:.13em;text-transform:uppercase;}
    body.home .lunara-home-masthead-logo-frame{display:grid;place-items:center;width:min(100%,1280px);margin-inline:auto;}
    body.home .lunara-home-masthead-logo{display:block;width:min(100%,<?php echo esc_html( $desktop_logo_width ); ?>px);height:auto;max-height:clamp(118px,22vw,<?php echo esc_html( $desktop_logo_height ); ?>px);object-fit:contain;filter:drop-shadow(0 18px 34px rgba(0,0,0,.36));}
    body.home .lunara-home-masthead-logo-fallback{color:var(--lunara-gold,#c9a961);font-family:var(--lunara-serif,Georgia,serif);font-size:clamp(3.1rem,9vw,6rem);line-height:.92;letter-spacing:0;}
    body.home .lunara-home-masthead-dek{max-width:58ch;margin:0 auto;color:rgba(250,251,252,.88);font-size:clamp(1rem,1.35vw,1.22rem);line-height:1.58;text-wrap:pretty;}
    body.home .lunara-home-masthead-standard{display:inline-flex;align-items:center;justify-content:center;margin:0;color:var(--lunara-gold-light,#e0c481)!important;font-size:.92rem;font-weight:700;letter-spacing:.02em;text-decoration:none!important;border-bottom:1px solid rgba(224,196,129,.38);}
    body.home .lunara-home-masthead-routes{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;width:min(100%,1120px);margin:clamp(8px,1.8vw,18px) auto 0;}
    body.home .lunara-home-masthead-route{display:grid;gap:6px;align-content:center;min-width:0;min-height:var(--lunara-home-route-card-min);padding:15px 16px;border:1px solid rgba(201,169,97,.2);border-radius:8px;background:rgba(6,14,24,.68);color:var(--lunara-text,#FAFBFC)!important;text-decoration:none!important;transition:border-color .18s ease,background .18s ease,transform .18s ease;}
    body.home .lunara-home-masthead.is-route-quiet .lunara-home-masthead-route{min-height:calc(var(--lunara-home-route-card-min) - 16px);background:rgba(6,14,24,.48);border-color:rgba(201,169,97,.14);}
    body.home .lunara-home-masthead.is-route-standard .lunara-home-masthead-route{background:rgba(6,14,24,.62);border-color:rgba(201,169,97,.22);}
    body.home .lunara-home-masthead.is-route-strong .lunara-home-masthead-route{background:linear-gradient(180deg,rgba(12,26,42,.86),rgba(6,14,24,.82));border-color:rgba(224,196,129,.34);box-shadow:0 18px 38px rgba(0,0,0,.16),inset 0 1px 0 rgba(255,255,255,.04);}
    body.home .lunara-home-masthead.is-density-compact .lunara-home-masthead-panel{gap:clamp(12px,2vw,22px);}
    body.home .lunara-home-masthead.is-density-compact .lunara-home-masthead-dek{max-width:52ch;}
    body.home .lunara-home-masthead.is-density-showcase .lunara-home-masthead-panel{gap:clamp(20px,3.2vw,38px);}
    body.home .lunara-home-masthead.is-density-showcase .lunara-home-masthead-route{min-height:calc(var(--lunara-home-route-card-min) + 12px);}
    body.home .lunara-home-masthead-route:hover,body.home .lunara-home-masthead-route:focus-visible{border-color:rgba(224,196,129,.56);background:rgba(201,169,97,.11);transform:translateY(-1px);}
    body.home .lunara-home-masthead-route-label{color:var(--lunara-gold-light,#e0c481);font-size:.7rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;}
    body.home .lunara-home-masthead-route strong{color:var(--lunara-text,#FAFBFC);font-size:clamp(1rem,1.15vw,1.16rem);line-height:1.18;}
    body.home .lunara-home-masthead-route span:last-child{color:rgba(244,239,227,.74);font-size:.88rem;line-height:1.42;}
    @media(max-width:820px){body.home .lunara-home-masthead{margin-bottom:<?php echo esc_html( max( 16, min( 34, $masthead_bottom_gap ) ) ); ?>px;}body.home .lunara-home-masthead-panel{padding:<?php echo esc_html( $mobile_top_pad ); ?>px 16px <?php echo esc_html( $mobile_bottom_pad ); ?>px;border-radius:0;}body.home .lunara-home-masthead-panel::after{left:16px;right:16px;bottom:auto;top:clamp(154px,42vw,218px);}body.home .lunara-home-masthead-logo-frame{width:100%;}body.home .lunara-home-masthead-logo{width:min(100%,<?php echo esc_html( $mobile_logo_width ); ?>px);max-width:100%;max-height:clamp(106px,31vw,<?php echo esc_html( $mobile_logo_height ); ?>px);object-fit:contain;}body.home .lunara-home-masthead-dek{font-size:1rem;line-height:1.54;}body.home .lunara-home-masthead-routes{grid-template-columns:minmax(0,1fr);gap:10px;margin-top:10px;}body.home .lunara-home-masthead-route{min-height:auto;padding:13px 14px;}}
    @media(min-width:821px) and (max-width:1100px){body.home .lunara-home-masthead-routes{grid-template-columns:repeat(3,minmax(0,1fr));}body.home .lunara-home-masthead-logo{width:min(100%,<?php echo esc_html( $tablet_logo_width ); ?>px);}}
    @media(prefers-reduced-motion:reduce){body.home .lunara-home-masthead-route{transition:none;}body.home .lunara-home-masthead-route:hover,body.home .lunara-home-masthead-route:focus-visible{transform:none;}}
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_home_front_door_css', 45 );

function lunara_home_card_media_hygiene_css() {
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    <style id="lunara-home-card-media-hygiene-css">
    body.home .lunara-review-grid-card.has-no-visual .lunara-review-grid-link,body.home .lunara-journal-home-card.has-no-visual .lunara-journal-home-card-link,body.home .lunara-oscar-pick-card.has-no-visual .lunara-oscar-pick-card-link{grid-template-rows:1fr!important;}
    body.home .lunara-review-grid-card.has-no-visual .lunara-review-grid-copy,body.home .lunara-journal-home-card.has-no-visual .lunara-journal-home-card-copy,body.home .lunara-oscar-pick-card.has-no-visual .lunara-oscar-pick-card-copy{align-content:start!important;min-height:clamp(210px,24vw,320px)!important;padding:clamp(18px,2.4vw,26px)!important;}
    body.home .lunara-journal-home-card.has-no-visual.is-lead .lunara-journal-home-card-copy{min-height:clamp(260px,28vw,380px)!important;}
    body.home .lunara-review-grid-card.has-no-visual,body.home .lunara-journal-home-card.has-no-visual,body.home .lunara-oscar-pick-card.has-no-visual{background:radial-gradient(circle at 92% 0%,rgba(201,169,97,.09),transparent 31%),linear-gradient(180deg,rgba(17,32,49,.96),rgba(7,15,26,.98))!important;}
    body.home .lunara-review-grid-card.has-no-visual .lunara-score-badge.is-inline-score,body.home .lunara-oscar-pick-card.has-no-visual .lunara-oscar-pick-card-status.is-inline-status{position:static!important;display:inline-flex!important;justify-self:start!important;width:auto!important;max-width:100%!important;margin:0 0 4px!important;}
    body.home .lunara-oscar-pick-card.has-no-visual .lunara-oscar-pick-card-status.is-inline-status{min-height:0!important;padding:6px 10px!important;border-radius:999px!important;background:rgba(6,14,24,.72)!important;}
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_home_card_media_hygiene_css', 47 );

/**
 * Keep homepage Journal card headlines on one shared type scale.
 */
function lunara_home_journal_title_rhythm_css() {
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    <style id="lunara-home-journal-title-rhythm-css">
    body.home .lunara-front-page .lunara-journal-home-grid .lunara-journal-home-card .lunara-journal-home-card-title,
    body.home .lunara-front-page .lunara-journal-home-grid .lunara-journal-home-card.is-lead .lunara-journal-home-card-title{font-size:clamp(1.25rem,1.9vw,1.62rem)!important;line-height:1.12!important;letter-spacing:0!important;min-height:calc(1.12em * 3)!important;}
    @media(max-width:760px){body.home .lunara-front-page .lunara-journal-home-grid .lunara-journal-home-card .lunara-journal-home-card-title,body.home .lunara-front-page .lunara-journal-home-grid .lunara-journal-home-card.is-lead .lunara-journal-home-card-title{font-size:clamp(1.25rem,5.2vw,1.36rem)!important;line-height:1.12!important;}}
    </style>
    <?php
}
add_action( 'wp_footer', 'lunara_home_journal_title_rhythm_css', 125 );

function lunara_homepage_studio_signature_css() {
    if ( ! is_front_page() ) {
        return;
    }

    $section_gap = lunara_home_brand_number_setting( 'lunara_home_section_gap', 38, 20, 90 );
    $rhythm      = lunara_home_select_setting( 'lunara_home_first_section_rhythm', 'tight', array( 'tight', 'balanced', 'spacious' ) );

    if ( 'balanced' === $rhythm ) {
        $section_gap = (int) round( $section_gap * 1.08 );
    } elseif ( 'spacious' === $rhythm ) {
        $section_gap = (int) round( $section_gap * 1.22 );
    } else {
        $section_gap = (int) round( $section_gap * 0.86 );
    }

    $section_gap = max( 18, min( 96, $section_gap ) );
    ?>
    <style id="lunara-homepage-studio-signature-css">
    body.home .lunara-front-page{--lunara-home-section-gap:<?php echo esc_html( $section_gap ); ?>px;}
    body.home .lunara-front-page > .lunara-home-section{margin-top:var(--lunara-home-section-gap)!important;margin-bottom:var(--lunara-home-section-gap)!important;}
    body.home .lunara-front-page > .lunara-home-masthead + .lunara-home-section{margin-top:max(16px,calc(var(--lunara-home-section-gap) * .55))!important;}
    body.home .lunara-front-page > .wp-block-group.alignfull{margin-top:var(--lunara-home-section-gap)!important;margin-bottom:var(--lunara-home-section-gap)!important;}
    body.home .lunara-front-page > :where(.lunara-home-section,.wp-block-group.alignfull) + :where(.lunara-home-section,.wp-block-group.alignfull){margin-top:calc(var(--lunara-home-section-gap) * .82)!important;}
    body.home .lunara-home-masthead + *{scroll-margin-top:96px;}
    body.home .lunara-home-masthead-route:focus-visible,body.home .lunara-oscar-facts-section .lunara-carousel-dot:focus-visible{outline:2px solid rgba(224,196,129,.92);outline-offset:3px;}
    body.home .lunara-oscar-facts-section{position:relative;overflow:clip;padding-inline:clamp(16px,4vw,30px);}
    body.home .lunara-oscar-facts-section .lunara-home-section-head{width:min(100%,1120px);margin:0 auto clamp(14px,2vw,22px)!important;padding:clamp(16px,2.2vw,24px) clamp(16px,2.4vw,28px);border:1px solid rgba(224,196,129,.2);border-radius:10px;background:linear-gradient(180deg,rgba(10,23,37,.78),rgba(7,16,27,.66));box-shadow:0 18px 48px rgba(0,0,0,.14);}
    body.home .lunara-oscar-facts-section .lunara-home-section-title{max-width:14em;text-wrap:balance;}
    body.home .lunara-oscar-facts-section .lunara-oscar-facts-carousel{width:min(100%,1160px)!important;padding:clamp(10px,1.5vw,18px);border:1px solid rgba(224,196,129,.26);border-radius:12px;background:linear-gradient(180deg,rgba(13,27,43,.88),rgba(6,14,24,.92));box-shadow:0 28px 70px rgba(0,0,0,.24),inset 0 1px 0 rgba(255,255,255,.04);}
    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-link{overflow:hidden;border:1px solid rgba(224,196,129,.22);border-radius:10px;box-shadow:inset 0 1px 0 rgba(255,255,255,.035);}
    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.has-poster .lunara-oscar-fact-card-poster{min-height:clamp(330px,36vw,500px)!important;}
    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.has-poster.has-archival-visual .lunara-oscar-fact-card-poster{min-height:clamp(260px,28vw,360px)!important;}
    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.has-archival-visual .lunara-oscar-fact-card-poster{background:linear-gradient(135deg,rgba(6,14,24,.98),rgba(16,29,43,.96))!important;}
    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-title{letter-spacing:0!important;}
    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-body{display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden;}
    body.home .lunara-oscar-facts-section .lunara-oscar-facts-dots{gap:12px;margin-top:20px;}
    body.home .lunara-oscar-facts-section .lunara-carousel-dot{width:11px;height:11px;background:rgba(244,239,227,.14);border-color:rgba(224,196,129,.62);transition:width .2s ease,background .2s ease,border-color .2s ease;}
    body.home .lunara-oscar-facts-section .lunara-carousel-dot.active{width:38px;background:#d7b66f;border-color:#d7b66f;}
    @media(max-width:820px){body.home .lunara-front-page > .lunara-home-section{margin-top:calc(var(--lunara-home-section-gap) * .76)!important;margin-bottom:calc(var(--lunara-home-section-gap) * .9)!important;}body.home .lunara-oscar-facts-section{padding-inline:14px;}body.home .lunara-oscar-facts-section .lunara-home-section-head{padding:16px;margin-bottom:14px!important;}body.home .lunara-oscar-facts-section .lunara-oscar-facts-carousel{padding:8px;border-radius:10px;}body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-link{border-radius:8px;}body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.has-poster .lunara-oscar-fact-card-poster{min-height:0!important;}}
    @media(prefers-reduced-motion:reduce){body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.lunara-carousel-slide,body.home .lunara-oscar-facts-section .lunara-carousel-dot{transition:none!important;}body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.lunara-carousel-slide{transform:none!important;}}
    </style>
    <?php
}
add_action( 'wp_footer', 'lunara_homepage_studio_signature_css', 130 );

/**
 * Footer fallback.
 */
function lunara_footer_menu_fallback() {
    echo '<ul class="lunara-footer-fallback">';
    echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/reviews/' ) ) . '">Reviews</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/oscars/' ) ) . '">Oscar Ledger</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">About</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/editorial-policy/' ) ) . '">Editorial Policy</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/contact/' ) ) . '">Contact</a></li>';
    echo '</ul>';
}

if ( ! function_exists( 'lunara_render_footer_link_list' ) ) {
    function lunara_render_footer_link_list( $items ) {
        $items = array_filter( (array) $items, static function ( $item ) {
            return ! empty( $item['label'] ) && ! empty( $item['url'] );
        } );

        if ( empty( $items ) ) {
            return;
        }

        echo '<ul class="lunara-footer-curated-list">';
        foreach ( $items as $item ) {
            printf(
                '<li><a href="%1$s">%2$s</a></li>',
                esc_url( $item['url'] ),
                esc_html( $item['label'] )
            );
        }
        echo '</ul>';
    }
}

/**
 * Optional legacy Lunara footer output.
 *
 * Blocksy should own the live footer shell by default. This renderer remains as
 * a fallback path that can be re-enabled through a filter if needed during the
 * transition.
 */
function lunara_render_custom_footer() {
    $show_logo  = get_theme_mod( 'lunara_footer_show_logo', true );
    $tagline    = get_theme_mod( 'lunara_footer_tagline', 'Film criticism and a living Oscar ledger.' );
    $col1_head  = get_theme_mod( 'lunara_footer_col1_heading', 'Editorial' );
    $col2_head  = get_theme_mod( 'lunara_footer_col2_heading', 'Oscar Ledger' );
    $col3_head  = get_theme_mod( 'lunara_footer_col3_heading', 'Utility' );
    $copyright  = get_theme_mod( 'lunara_footer_copyright', 'Lunara Film' );
    ?>
    <footer class="lunara-site-footer" role="contentinfo">
        <div class="lunara-footer-inner">
            <!-- Zone 1: Branded close -->
            <div class="lunara-footer-brand">
                <?php if ( $show_logo ) :
                    $custom_logo_id = get_theme_mod( 'custom_logo' );
                    if ( $custom_logo_id ) :
                        echo wp_get_attachment_image( $custom_logo_id, 'medium', false, array(
                            'class'   => 'lunara-footer-logo',
                            'loading' => 'lazy',
                            'alt'     => get_bloginfo( 'name' ) . ' logo',
                        ) );
                    else : ?>
                        <span class="lunara-footer-wordmark"><?php bloginfo( 'name' ); ?></span>
                    <?php endif;
                endif; ?>
                <?php if ( $tagline ) : ?>
                    <p class="lunara-footer-tagline"><?php echo esc_html( $tagline ); ?></p>
                <?php endif; ?>
            </div>

            <!-- Zone 2: Navigation columns -->
            <nav class="lunara-footer-nav-grid" aria-label="<?php esc_attr_e( 'Footer navigation', 'lunara-film' ); ?>">
                <div class="lunara-footer-nav-col">
                    <?php if ( $col1_head ) : ?>
                        <h4 class="lunara-footer-col-heading"><?php echo esc_html( $col1_head ); ?></h4>
                    <?php endif; ?>
                    <?php
                    lunara_render_footer_link_list( array(
                        array( 'label' => __( 'Home', 'lunara-film' ), 'url' => home_url( '/' ) ),
                        array( 'label' => __( 'Reviews', 'lunara-film' ), 'url' => get_post_type_archive_link( 'review' ) ?: home_url( '/reviews/' ) ),
                        array( 'label' => __( 'Journal', 'lunara-film' ), 'url' => get_post_type_archive_link( 'journal' ) ?: home_url( '/journal/' ) ),
                        array( 'label' => __( 'About', 'lunara-film' ), 'url' => home_url( '/about/' ) ),
                        array( 'label' => __( 'Editorial Policy', 'lunara-film' ), 'url' => home_url( '/editorial-policy/' ) ),
                    ) );
                    ?>
                </div>
                <div class="lunara-footer-nav-col">
                    <?php if ( $col2_head ) : ?>
                        <h4 class="lunara-footer-col-heading"><?php echo esc_html( $col2_head ); ?></h4>
                    <?php endif; ?>
                    <?php
                    lunara_render_footer_link_list( array(
                        array( 'label' => __( 'Oscars', 'lunara-film' ), 'url' => home_url( '/oscars/' ) ),
                        array( 'label' => __( 'Categories', 'lunara-film' ), 'url' => home_url( '/oscars/categories/' ) ),
                        array( 'label' => __( 'Ceremonies', 'lunara-film' ), 'url' => home_url( '/oscars/ceremonies/' ) ),
                        array( 'label' => __( 'Full Ledger', 'lunara-film' ), 'url' => home_url( '/oscars/?view=table#oscars-research' ) ),
                    ) );
                    ?>
                </div>
                <div class="lunara-footer-nav-col">
                    <?php if ( $col3_head ) : ?>
                        <h4 class="lunara-footer-col-heading"><?php echo esc_html( $col3_head ); ?></h4>
                    <?php endif; ?>
                    <?php
                    $utility_links = array(
                        array( 'label' => __( 'Search', 'lunara-film' ), 'url' => home_url( '/?s=' ) ),
                        array( 'label' => __( 'Contact', 'lunara-film' ), 'url' => home_url( '/contact/' ) ),
                        array( 'label' => __( 'RSS Feed', 'lunara-film' ), 'url' => get_bloginfo( 'rss2_url' ) ),
                    );
                    $privacy_url = get_privacy_policy_url();
                    if ( $privacy_url ) {
                        $utility_links[] = array( 'label' => __( 'Privacy', 'lunara-film' ), 'url' => $privacy_url );
                    }
                    lunara_render_footer_link_list( $utility_links );
                    ?>
                </div>
            </nav>

            <!-- Zone 3: Utility row -->
            <div class="lunara-footer-utility">
                <span class="lunara-footer-copyright">&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( $copyright ); ?></span>
                <?php $privacy_url = get_privacy_policy_url(); ?>
                <?php if ( $privacy_url ) : ?>
                    <span class="lunara-footer-legal">
                        <a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Privacy', 'lunara-film' ); ?></a>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </footer>
    <?php
}

if ( apply_filters( 'lunara_use_custom_footer', true ) ) {
    add_action( 'wp_footer', 'lunara_render_custom_footer', 1 );
    add_filter( 'blocksy:footer:has-widgets', '__return_false' );
    add_filter( 'blocksy:builder:footer:enabled', '__return_false' );
}

/* Footer menu fallbacks */
function lunara_footer_editorial_fallback() {
    $journal_url   = lunara_home_dispatch_archive_url();
    $journal_label = 'Journal';
    $posts_page_id = absint( get_option( 'page_for_posts' ) );
    $news_url      = home_url( '/news/' );

    if ( $posts_page_id > 0 ) {
        $posts_page_title = trim( wp_strip_all_tags( get_the_title( $posts_page_id ) ) );
        if ( '' !== $posts_page_title ) {
            $journal_label = $posts_page_title;
        }
    }

    echo '<ul class="menu">';
    echo '<li><a href="' . esc_url( home_url( '/reviews/' ) ) . '">Reviews</a></li>';
    echo '<li><a href="' . esc_url( $journal_url ) . '">' . esc_html( $journal_label ) . '</a></li>';
    if ( untrailingslashit( $journal_url ) !== untrailingslashit( $news_url ) && 'Journal' !== $journal_label ) {
        echo '<li><a href="' . esc_url( $news_url ) . '">Journal</a></li>';
    }
    echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">About</a></li>';
    echo '</ul>';
}

function lunara_footer_oscars_fallback() {
    echo '<ul class="menu">';
    echo '<li><a href="' . esc_url( home_url( '/oscars/' ) ) . '">Ledger</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/oscars/categories/' ) ) . '">Categories</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/oscars/ceremonies/' ) ) . '">Ceremonies</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/oscars/about/' ) ) . '">About the Ledger</a></li>';
    echo '</ul>';
}

function lunara_footer_utility_fallback() {
    echo '<ul class="menu">';
    echo '<li><a href="' . esc_url( home_url( '/?s=' ) ) . '">Search</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/contact/' ) ) . '">Contact</a></li>';
    echo '<li><a href="' . esc_url( get_feed_link() ) . '">RSS</a></li>';
    echo '</ul>';
}

/**
 * Map primary-nav utility paths to reliable fallback labels.
 */
function lunara_primary_menu_fallback_label_for_path( $path ) {
    $label_map = array(
        '/oscars/categories-page'         => 'Categories',
        '/oscars/categories'              => 'Categories',
        '/oscars/about-this-database-page' => 'About the Ledger',
        '/oscars/about'                   => 'About the Ledger',
        '/awards-tracker'                 => 'Awards Tracker',
        '/search'                         => 'Search',
    );

    if ( isset( $label_map[ $path ] ) ) {
        return $label_map[ $path ];
    }

    return '';
}

/**
 * Supply readable labels when a primary-nav item is configured as icon-only.
 */
function lunara_primary_menu_item_title_fallback( $title, $item, $args, $depth ) {
    if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
        return $title;
    }

    $plain_title = trim( wp_strip_all_tags( html_entity_decode( (string) $title, ENT_QUOTES, 'UTF-8' ) ) );
    if ( '' !== $plain_title ) {
        return $title;
    }

    $item_url = isset( $item->url ) ? (string) $item->url : '';
    if ( '' === $item_url ) {
        return $title;
    }

    $path  = wp_parse_url( $item_url, PHP_URL_PATH );
    $path  = is_string( $path ) ? untrailingslashit( $path ) : '';
    $label = lunara_primary_menu_fallback_label_for_path( $path );

    if ( '' !== $label ) {
        return esc_html( $label );
    }

    return $title;
}
add_filter( 'nav_menu_item_title', 'lunara_primary_menu_item_title_fallback', 10, 4 );

/**
 * Normalize icon-only primary-menu items before the walker renders them.
 */
function lunara_primary_menu_object_title_fallback( $sorted_menu_items, $args ) {
    if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location || ! is_array( $sorted_menu_items ) ) {
        return $sorted_menu_items;
    }

    foreach ( $sorted_menu_items as $item ) {
        if ( ! is_object( $item ) ) {
            continue;
        }

        $current_title = isset( $item->title ) ? trim( wp_strip_all_tags( html_entity_decode( (string) $item->title, ENT_QUOTES, 'UTF-8' ) ) ) : '';
        if ( '' !== $current_title ) {
            continue;
        }

        $item_url = isset( $item->url ) ? (string) $item->url : '';
        if ( '' === $item_url ) {
            continue;
        }

        $path  = wp_parse_url( $item_url, PHP_URL_PATH );
        $path  = is_string( $path ) ? untrailingslashit( $path ) : '';
        $label = lunara_primary_menu_fallback_label_for_path( $path );

        if ( '' === $label ) {
            continue;
        }

        $item->title = $label;

        if ( isset( $item->post_title ) && '' === trim( (string) $item->post_title ) ) {
            $item->post_title = $label;
        }
    }

    return $sorted_menu_items;
}
add_filter( 'wp_nav_menu_objects', 'lunara_primary_menu_object_title_fallback', 10, 2 );

/**
 * Ensure icon-only primary menu items still output a visible text label.
 */
function lunara_primary_menu_start_el_fallback( $item_output, $item, $depth, $args ) {
    if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
        return $item_output;
    }

    $item_url = isset( $item->url ) ? (string) $item->url : '';
    if ( '' === $item_url ) {
        return $item_output;
    }

    $path  = wp_parse_url( $item_url, PHP_URL_PATH );
    $path  = is_string( $path ) ? untrailingslashit( $path ) : '';
    $label = lunara_primary_menu_fallback_label_for_path( $path );
    if ( '' === $label || false !== strpos( $item_output, $label ) ) {
        return $item_output;
    }

    if ( ! preg_match( '/(<a\b[^>]*>)(.*?)(<\/a>)/is', $item_output, $matches ) ) {
        return $item_output;
    }

    $inner_html = preg_replace( '/<!--.*?-->/s', '', $matches[2] );
    $inner_html = preg_replace( '/<svg\b.*?<\/svg>/is', '', $inner_html );
    $plain_html = trim( wp_strip_all_tags( $inner_html ) );
    if ( '' !== $plain_html ) {
        return $item_output;
    }

    $fallback_markup = '<span class="lunara-menu-fallback-label">' . esc_html( $label ) . '</span>';
    return $matches[1] . $matches[2] . $fallback_markup . $matches[3];
}
add_filter( 'walker_nav_menu_start_el', 'lunara_primary_menu_start_el_fallback', 10, 4 );

/**
 * Review metadata prepended above single review content.
 */
function lunara_prepend_review_metadata( $content ) {
    if ( ! is_singular( 'review' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    $director = get_post_meta( get_the_ID(), '_lunara_director', true );
    $year     = get_post_meta( get_the_ID(), '_lunara_year', true );
    $runtime  = get_post_meta( get_the_ID(), '_lunara_runtime', true );
    $studio   = get_post_meta( get_the_ID(), '_lunara_studio', true );

    $items = array();
    if ( $director ) $items[] = '<span><strong>Director:</strong> ' . esc_html( $director ) . '</span>';
    if ( $year )     $items[] = '<span><strong>Year:</strong> ' . esc_html( $year ) . '</span>';
    if ( $runtime )  $items[] = '<span><strong>Runtime:</strong> ' . esc_html( $runtime ) . '</span>';
    if ( $studio )   $items[] = '<span><strong>Studio:</strong> ' . esc_html( $studio ) . '</span>';

    if ( empty( $items ) ) {
        return $content;
    }

    $bar = '<div class="lunara-review-metadata">' . implode( '', $items ) . '</div>';
    return $bar . $content;
}
add_filter( 'the_content', 'lunara_prepend_review_metadata', 5 );

/**
 * Drop malformed srcset candidates injected by CDN/image optimizers.
 *
 * Some homepage poster images receive an extra candidate like:
 *   "...&_jb=custom 1440.00"
 * which is missing a valid width or density descriptor. Browsers then emit
 * warnings and may ignore the whole srcset. We keep only candidates with a
 * standard trailing descriptor.
 */
if ( ! function_exists( 'lunara_sanitize_srcset_value' ) ) {
    function lunara_sanitize_srcset_value( $srcset ) {
        $srcset = is_string( $srcset ) ? trim( $srcset ) : '';
        if ( '' === $srcset || false === strpos( $srcset, ',' ) ) {
            return $srcset;
        }

        $candidates = preg_split( '/,\s*(?=(?:https?:)?\/\/|\/)/', $srcset );
        if ( ! is_array( $candidates ) || empty( $candidates ) ) {
            return $srcset;
        }

        $valid = array();
        foreach ( $candidates as $candidate ) {
            $candidate = trim( (string) $candidate );
            if ( '' === $candidate ) {
                continue;
            }

            if ( preg_match( '/\s+\d+w$/', $candidate ) || preg_match( '/\s+\d+(?:\.\d+)?x$/', $candidate ) ) {
                $valid[] = $candidate;
            }
        }

        if ( empty( $valid ) ) {
            return '';
        }

        return implode( ', ', $valid );
    }
}

/**
 * Sanitize attachment image attributes after WordPress/CDN filters run.
 */
if ( ! function_exists( 'lunara_sanitize_attachment_image_attributes' ) ) {
    function lunara_sanitize_attachment_image_attributes( $attr ) {
        if ( empty( $attr['srcset'] ) ) {
            return $attr;
        }

        $sanitized = lunara_sanitize_srcset_value( (string) $attr['srcset'] );
        if ( '' === $sanitized ) {
            unset( $attr['srcset'], $attr['sizes'] );
            return $attr;
        }

        $attr['srcset'] = $sanitized;
        if ( false === strpos( $sanitized, ',' ) ) {
            unset( $attr['sizes'] );
        }

        return $attr;
    }
}
add_filter( 'wp_get_attachment_image_attributes', 'lunara_sanitize_attachment_image_attributes', 999 );

/**
 * Sanitize content image tags that may bypass wp_get_attachment_image().
 */
if ( ! function_exists( 'lunara_sanitize_content_image_tag' ) ) {
    function lunara_sanitize_content_image_tag( $filtered_image ) {
        $filtered_image = is_string( $filtered_image ) ? $filtered_image : '';
        if ( '' === $filtered_image || false === strpos( $filtered_image, 'srcset=' ) ) {
            return $filtered_image;
        }

        return preg_replace_callback(
            '/\s(srcset)=("|\')(.*?)\2/i',
            static function ( $matches ) {
                $sanitized = lunara_sanitize_srcset_value( html_entity_decode( (string) $matches[3], ENT_QUOTES, 'UTF-8' ) );
                if ( '' === $sanitized ) {
                    return '';
                }

                return ' ' . $matches[1] . '=' . $matches[2] . esc_attr( $sanitized ) . $matches[2];
            },
            $filtered_image
        );
    }
}
add_filter( 'wp_content_img_tag', 'lunara_sanitize_content_image_tag', 999 );

/**
 * Make search reflect the real Lunara content universe.
 */
if ( ! function_exists( 'lunara_configure_main_search_query' ) ) {
    function lunara_configure_main_search_query( $query ) {
        if ( ! ( $query instanceof WP_Query ) || is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
            return;
        }

        $query->set( 'post_type', array( 'review', 'post', 'page' ) );
        $query->set( 'post_status', 'publish' );
        $query->set( 'ignore_sticky_posts', true );
        $query->set( 'posts_per_page', 12 );
    }
}
add_action( 'pre_get_posts', 'lunara_configure_main_search_query' );

/**
 * Push exact and title-based matches higher in Lunara search results.
 */
if ( ! function_exists( 'lunara_boost_search_orderby' ) ) {
    function lunara_boost_search_orderby( $orderby, $query ) {
        if ( ! ( $query instanceof WP_Query ) || is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
            return $orderby;
        }

        global $wpdb;

        $search = trim( (string) $query->get( 's' ) );
        if ( '' === $search || ! ( $wpdb instanceof wpdb ) ) {
            return $orderby;
        }

        $like_any   = '%' . $wpdb->esc_like( $search ) . '%';
        $like_start = $wpdb->esc_like( $search ) . '%';
        $quoted_any = "'" . esc_sql( $like_any ) . "'";
        $quoted_start = "'" . esc_sql( $like_start ) . "'";
        $quoted_exact = "'" . esc_sql( $search ) . "'";

        $posts_table = $wpdb->posts;

        return "
            CASE
                WHEN {$posts_table}.post_title = {$quoted_exact} THEN 0
                WHEN {$posts_table}.post_title LIKE {$quoted_start} THEN 1
                WHEN {$posts_table}.post_title LIKE {$quoted_any} THEN 2
                WHEN {$posts_table}.post_excerpt LIKE {$quoted_any} THEN 3
                WHEN {$posts_table}.post_content LIKE {$quoted_any} THEN 4
                ELSE 5
            END ASC,
            CASE
                WHEN {$posts_table}.post_type = 'review' THEN 0
                WHEN {$posts_table}.post_type = 'post' THEN 1
                WHEN {$posts_table}.post_type = 'page' THEN 2
                ELSE 3
            END ASC,
            {$posts_table}.post_date DESC
        ";
    }
}
add_filter( 'posts_orderby', 'lunara_boost_search_orderby', 20, 2 );

/**
 * Build fast front-end search suggestions from posts/pages/reviews.
 */
if ( ! function_exists( 'lunara_get_post_search_suggestions' ) ) {
    function lunara_get_post_search_suggestions( $query_text, $limit = 6 ) {
        global $wpdb;

        $query_text = trim( (string) $query_text );
        $limit      = max( 1, intval( $limit ) );

        if ( '' === $query_text || ! ( $wpdb instanceof wpdb ) ) {
            return array();
        }

        $posts_table  = $wpdb->posts;
        $like_any     = '%' . $wpdb->esc_like( $query_text ) . '%';
        $like_start   = $wpdb->esc_like( $query_text ) . '%';
        $quoted_any   = "'" . esc_sql( $like_any ) . "'";
        $quoted_start = "'" . esc_sql( $like_start ) . "'";
        $quoted_exact = "'" . esc_sql( $query_text ) . "'";

        $sql = $wpdb->prepare(
              "SELECT ID, post_title, post_type, post_date
               FROM {$posts_table}
               WHERE post_status = 'publish'
                 AND post_type IN ('review','post','page')
                 AND post_title LIKE %s
               ORDER BY
                  CASE
                      WHEN post_title = {$quoted_exact} THEN 0
                      WHEN post_title LIKE {$quoted_start} THEN 1
                      WHEN post_title LIKE {$quoted_any} THEN 2
                      ELSE 3
                  END ASC,
                  CASE
                      WHEN post_type = 'review' THEN 0
                      WHEN post_type = 'post' THEN 1
                      WHEN post_type = 'page' THEN 2
                    ELSE 3
                END ASC,
                post_date DESC
             LIMIT %d",
            $like_any,
            $limit
        );

        $rows = $wpdb->get_results( $sql, ARRAY_A );
        if ( ! is_array( $rows ) || empty( $rows ) ) {
            return array();
        }

        $results = array();
        foreach ( $rows as $row ) {
            $post_id   = intval( $row['ID'] ?? 0 );
            $post_type = (string) ( $row['post_type'] ?? '' );
            $title     = trim( (string) ( $row['post_title'] ?? '' ) );
            if ( $post_id <= 0 ) {
                continue;
            }

            $score = function_exists( 'lunara_search_text_match_score' )
                ? lunara_search_text_match_score( $title, $query_text )
                : 0;

            if ( $score <= 0 ) {
                continue;
            }

            if ( 'review' === $post_type ) {
                $kicker = __( 'Review', 'lunara-film' );
            } elseif ( 'page' === $post_type ) {
                $kicker = __( 'Page', 'lunara-film' );
            } else {
                $kicker = function_exists( 'lunara_get_dispatch_type_label' ) ? lunara_get_dispatch_type_label( $post_id ) : __( 'Dispatch', 'lunara-film' );
            }

            $results[] = array(
                'kicker' => $kicker,
                'title'  => $title,
                'url'    => get_permalink( $post_id ),
                'score'  => $score,
            );
        }

        usort(
            $results,
            static function ( $left, $right ) {
                return intval( $right['score'] ?? 0 ) <=> intval( $left['score'] ?? 0 );
            }
        );

        return $results;
    }
}

/**
 * Normalize a label for typo-tolerant search recovery checks.
 */
if ( ! function_exists( 'lunara_normalize_search_recovery_label' ) ) {
    function lunara_normalize_search_recovery_label( $label ) {
        $label = strtolower( trim( (string) $label ) );
        $label = preg_replace( '/\(\d{4}\)/', '', $label );
        $label = preg_replace( '/[^a-z0-9]+/i', ' ', $label );
        $label = trim( preg_replace( '/\s+/', ' ', $label ) );

        return is_string( $label ) ? $label : '';
    }
}

/**
 * Pull typo-tolerant fallback routes when a search is weak or empty.
 */
if ( ! function_exists( 'lunara_get_search_recovery_routes' ) ) {
    function lunara_get_search_recovery_routes( $query_text, $limit = 6 ) {
        global $wpdb;

        $query_text = trim( (string) $query_text );
        $limit      = max( 1, intval( $limit ) );

        if ( '' === $query_text || ! ( $wpdb instanceof wpdb ) ) {
            return array();
        }

        $normalized_query = lunara_normalize_search_recovery_label( $query_text );
        if ( '' === $normalized_query ) {
            return array();
        }

        $seed = substr( str_replace( ' ', '', $normalized_query ), 0, 3 );
        if ( '' === $seed ) {
            return array();
        }

        $seed_like = '%' . $wpdb->esc_like( $seed ) . '%';
        $matches   = array();

        $push_match = static function ( $key, $match ) use ( &$matches ) {
            if ( empty( $match['score'] ) ) {
                return;
            }

            if ( ! isset( $matches[ $key ] ) || intval( $match['score'] ) > intval( $matches[ $key ]['score'] ) ) {
                $matches[ $key ] = $match;
            }
        };

        $score_label = static function ( $label ) use ( $normalized_query ) {
            $normalized_label = lunara_normalize_search_recovery_label( $label );
            if ( '' === $normalized_label ) {
                return 0;
            }

            if ( $normalized_label === $normalized_query ) {
                return 100;
            }

            if ( str_starts_with( $normalized_label, $normalized_query ) ) {
                return 94;
            }

            if ( str_contains( $normalized_label, $normalized_query ) ) {
                return 88;
            }

            $distance = levenshtein( $normalized_query, $normalized_label );
            $length   = max( strlen( $normalized_query ), strlen( $normalized_label ) );

            if ( $length <= 0 ) {
                return 0;
            }

            if ( $distance <= 2 ) {
                return 82 - ( $distance * 6 );
            }

            similar_text( $normalized_query, $normalized_label, $percent );
            if ( $percent >= 72 ) {
                return intval( round( $percent ) );
            }

            $query_tokens = array_values( array_filter( explode( ' ', $normalized_query ) ) );
            if ( count( $query_tokens ) > 1 ) {
                $all_tokens_near = true;
                foreach ( $query_tokens as $token ) {
                    if ( ! str_contains( $normalized_label, $token ) ) {
                        $all_tokens_near = false;
                        break;
                    }
                }
                if ( $all_tokens_near ) {
                    return 74;
                }
            }

            return 0;
        };

        $posts_table = $wpdb->posts;
        $post_sql    = $wpdb->prepare(
            "SELECT ID, post_title, post_type
             FROM {$posts_table}
             WHERE post_status = 'publish'
               AND post_type IN ('review','post','page')
               AND post_title LIKE %s
             ORDER BY post_date DESC
             LIMIT 30",
            $seed_like
        );
        $post_rows   = $wpdb->get_results( $post_sql, ARRAY_A );

        if ( is_array( $post_rows ) ) {
            foreach ( $post_rows as $row ) {
                $post_id   = intval( $row['ID'] ?? 0 );
                $post_type = (string) ( $row['post_type'] ?? '' );
                $title     = trim( (string) ( $row['post_title'] ?? '' ) );
                $score     = $score_label( $title );

                if ( $post_id <= 0 || $score < 72 ) {
                    continue;
                }

                if ( 'review' === $post_type ) {
                    $kicker = __( 'Review Route', 'lunara-film' );
                    $score += 6;
                } elseif ( 'page' === $post_type ) {
                    $kicker = __( 'Page Route', 'lunara-film' );
                } else {
                    $kicker = __( 'Dispatch Route', 'lunara-film' );
                    $score += 2;
                }

                $push_match(
                    'post:' . $post_id,
                    array(
                        'kicker' => $kicker,
                        'title'  => $title,
                        'meta'   => __( 'Closest Lunara route', 'lunara-film' ),
                        'url'    => get_permalink( $post_id ),
                        'score'  => $score,
                    )
                );
            }
        }

        $table_name   = $wpdb->prefix . 'academy_awards';
        $table_like   = $wpdb->esc_like( $table_name );
        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_like ) );

        if ( $table_exists === $table_name ) {
            $oscars_sql = $wpdb->prepare(
                "SELECT film, film_id, nominees, nominee_ids, category, canonical_category, ceremony, year, winner
                 FROM {$table_name}
                 WHERE film LIKE %s
                    OR nominees LIKE %s
                 ORDER BY winner DESC, ceremony DESC, id DESC
                 LIMIT 60",
                $seed_like,
                $seed_like
            );
            $rows = $wpdb->get_results( $oscars_sql, ARRAY_A );

            if ( is_array( $rows ) ) {
                $base_url = home_url( '/oscars/' );
                if ( class_exists( 'Academy_Awards_Table' ) ) {
                    $aat = Academy_Awards_Table::get_instance();
                    if ( $aat && method_exists( $aat, 'get_entity_base_url' ) ) {
                        $base_url = $aat->get_entity_base_url();
                    }
                }
                $base_url = trailingslashit( $base_url );

                foreach ( $rows as $row ) {
                    $film    = trim( (string) ( $row['film'] ?? '' ) );
                    $film_id = strtolower( trim( (string) ( $row['film_id'] ?? '' ) ) );
                    $score   = $score_label( $film );

                    if ( '' !== $film && preg_match( '/^tt\d+$/', $film_id ) && $score >= 72 ) {
                        if ( intval( $row['winner'] ?? 0 ) > 0 ) {
                            $score += 2;
                        }

                        $push_match(
                            'title:' . $film_id,
                            array(
                                'kicker' => __( 'Closest Ledger Title', 'lunara-film' ),
                                'title'  => $film,
                                'meta'   => sprintf(
                                    /* translators: 1: ceremony number, 2: year */
                                    __( '%1$s Ceremony / %2$s', 'lunara-film' ),
                                    intval( $row['ceremony'] ?? 0 ),
                                    trim( (string) ( $row['year'] ?? '' ) )
                                ),
                                'url'    => $base_url . 'title/' . rawurlencode( $film_id ) . '/',
                                'score'  => $score,
                            )
                        );
                    }
                }
            }
        }

        uasort(
            $matches,
            static function ( $left, $right ) {
                return intval( $right['score'] ?? 0 ) <=> intval( $left['score'] ?? 0 );
            }
        );

        return array_slice( array_values( $matches ), 0, $limit );
    }
}

/**
 * AJAX suggestions endpoint for front-end search boxes.
 */
if ( ! function_exists( 'lunara_ajax_search_suggestions' ) ) {
    function lunara_ajax_search_suggestions() {
        $query_text = isset( $_REQUEST['q'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['q'] ) ) : '';
        $query_text = trim( $query_text );

        if ( '' === $query_text || strlen( $query_text ) < 2 ) {
            wp_send_json_success(
                array(
                    'items' => array(),
                )
            );
        }

        $items = array();
        $seen  = array();

        foreach ( lunara_get_post_search_suggestions( $query_text, 6 ) as $item ) {
            $url = isset( $item['url'] ) ? (string) $item['url'] : '';
            if ( '' === $url || isset( $seen[ $url ] ) ) {
                continue;
            }
            $seen[ $url ] = true;
            $score        = intval( $item['score'] ?? 0 );
            $kicker       = isset( $item['kicker'] ) ? (string) $item['kicker'] : '';
            if ( 'Review' === $kicker ) {
                $score += 8;
            } elseif ( 'Page' === $kicker ) {
                $score += 1;
            } else {
                $score += 4;
            }
            $items[]      = array(
                'kicker' => $kicker,
                'title'  => $item['title'] ?? '',
                'meta'   => $item['meta'] ?? '',
                'url'    => $url,
                'score'  => $score,
            );
        }

        foreach ( lunara_get_oscars_search_matches( $query_text, 4 ) as $item ) {
            $url = isset( $item['url'] ) ? (string) $item['url'] : '';
            if ( '' === $url || isset( $seen[ $url ] ) ) {
                continue;
            }
            $seen[ $url ] = true;
            $items[]      = array(
                'kicker' => $item['kicker'] ?? __( 'Oscar Match', 'lunara-film' ),
                'title'  => $item['title'] ?? '',
                'meta'   => $item['meta'] ?? '',
                'url'    => $url,
                'score'  => intval( $item['score'] ?? 0 ),
            );
        }

        usort(
            $items,
            static function ( $left, $right ) {
                return intval( $right['score'] ?? 0 ) <=> intval( $left['score'] ?? 0 );
            }
        );

        if ( empty( $items ) ) {
            foreach ( lunara_get_search_recovery_routes( $query_text, 6 ) as $item ) {
                $items[] = array(
                    'kicker' => $item['kicker'] ?? __( 'Closest Route', 'lunara-film' ),
                    'title'  => $item['title'] ?? '',
                    'meta'   => $item['meta'] ?? '',
                    'url'    => $item['url'] ?? '',
                    'score'  => intval( $item['score'] ?? 0 ),
                );
            }
        }

        wp_send_json_success(
            array(
                'items' => array_slice( $items, 0, 8 ),
            )
        );
    }
}
add_action( 'wp_ajax_lunara_search_suggestions', 'lunara_ajax_search_suggestions' );
add_action( 'wp_ajax_nopriv_lunara_search_suggestions', 'lunara_ajax_search_suggestions' );

/**
 * Lightweight live-search suggestions for front-end search inputs.
 */
if ( ! function_exists( 'lunara_render_live_search_script' ) ) {
    function lunara_render_live_search_script() {
        if ( is_admin() ) {
            return;
        }
        ?>
        <script id="lunara-live-search-script">
        document.addEventListener('DOMContentLoaded', function () {
            const forms = Array.from(document.querySelectorAll('form[role="search"], .search-form')).filter(function (form) {
                return form.querySelector('input[name="s"]');
            });
            if (!forms.length) return;

            const endpoint = <?php echo wp_json_encode( admin_url( 'admin-ajax.php?action=lunara_search_suggestions' ) ); ?>;

            forms.forEach(function (form) {
                const input = form.querySelector('input[name="s"]');
                if (!input || input.dataset.lunaraSuggestionsReady === '1') return;
                input.dataset.lunaraSuggestionsReady = '1';

                form.classList.add('lunara-live-search-form');
                let panel = form.querySelector('.lunara-live-search-panel');
                if (!panel) {
                    panel = document.createElement('div');
                    panel.className = 'lunara-live-search-panel';
                    panel.hidden = true;
                    form.appendChild(panel);
                }

                let controller = null;
                let activeIndex = -1;
                let currentItems = [];

                const closePanel = function () {
                    panel.hidden = true;
                    panel.innerHTML = '';
                    activeIndex = -1;
                    currentItems = [];
                };

                const renderPanel = function (items) {
                    currentItems = items.slice();
                    activeIndex = -1;

                    if (!items.length) {
                        closePanel();
                        return;
                    }

                    panel.innerHTML = items.map(function (item, index) {
                        const meta = item.meta ? '<span class="lunara-live-search-meta">' + item.meta + '</span>' : '';
                        return '<a class="lunara-live-search-item" href="' + item.url + '" data-index="' + index + '">' +
                            '<span class="lunara-live-search-kicker">' + item.kicker + '</span>' +
                            '<span class="lunara-live-search-title">' + item.title + '</span>' +
                            meta +
                        '</a>';
                    }).join('') +
                    '<a class="lunara-live-search-all-results" href="' + form.action + '?s=' + encodeURIComponent(input.value.trim()) + '">' +
                        '<span class="lunara-live-search-kicker"><?php echo esc_js( __( 'Search Desk', 'lunara-film' ) ); ?></span>' +
                        '<span class="lunara-live-search-title"><?php echo esc_js( __( 'See all results on the record', 'lunara-film' ) ); ?></span>' +
                    '</a>';
                    panel.hidden = false;
                };

                const updateActiveItem = function () {
                    const links = panel.querySelectorAll('.lunara-live-search-item');
                    links.forEach(function (link, index) {
                        link.classList.toggle('is-active', index === activeIndex);
                    });
                };

                const fetchSuggestions = function (value) {
                    if (controller) controller.abort();
                    controller = new AbortController();
                    const url = endpoint + '&q=' + encodeURIComponent(value);

                    fetch(url, {
                        credentials: 'same-origin',
                        signal: controller.signal
                    })
                    .then(function (response) { return response.json(); })
                    .then(function (payload) {
                        if (!payload || payload.success !== true || !payload.data || !Array.isArray(payload.data.items)) {
                            closePanel();
                            return;
                        }
                        renderPanel(payload.data.items);
                    })
                    .catch(function (error) {
                        if (error && error.name === 'AbortError') return;
                        closePanel();
                    });
                };

                let debounceTimer = null;
                input.addEventListener('input', function () {
                    const value = input.value.trim();
                    window.clearTimeout(debounceTimer);
                    if (value.length < 2) {
                        closePanel();
                        return;
                    }
                    debounceTimer = window.setTimeout(function () {
                        fetchSuggestions(value);
                    }, 140);
                });

                input.addEventListener('keydown', function (event) {
                    if (panel.hidden || !currentItems.length) return;

                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        activeIndex = Math.min(activeIndex + 1, currentItems.length - 1);
                        updateActiveItem();
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        activeIndex = Math.max(activeIndex - 1, 0);
                        updateActiveItem();
                    } else if (event.key === 'Enter' && activeIndex >= 0) {
                        const link = panel.querySelector('.lunara-live-search-item[data-index="' + activeIndex + '"]');
                        if (link) {
                            event.preventDefault();
                            window.location.href = link.href;
                        }
                    } else if (event.key === 'Escape') {
                        closePanel();
                    }
                });

                form.addEventListener('focusout', function () {
                    window.setTimeout(function () {
                        if (!form.contains(document.activeElement)) {
                            closePanel();
                        }
                    }, 120);
                });

                document.addEventListener('click', function (event) {
                    if (!form.contains(event.target)) {
                        closePanel();
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action( 'wp_footer', 'lunara_render_live_search_script', 120 );

/**
 * Pull direct Oscars entity matches for the front-end search desk.
 */
if ( ! function_exists( 'lunara_get_oscars_search_matches' ) ) {
    function lunara_get_oscars_search_matches( $query_text, $limit = 6 ) {
        global $wpdb;

        $query_text = trim( (string) $query_text );
        $limit      = max( 1, intval( $limit ) );

        if ( '' === $query_text || ! ( $wpdb instanceof wpdb ) ) {
            return array();
        }

        $table_name = $wpdb->prefix . 'academy_awards';
        $table_like = $wpdb->esc_like( $table_name );
        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_like ) );

        if ( $table_exists !== $table_name ) {
            return array();
        }

        $search_term = '%' . $wpdb->esc_like( $query_text ) . '%';
        $sql         = $wpdb->prepare(
            "SELECT film, film_id, name, nominees, nominee_ids, canonical_category, category, ceremony, year, winner
             FROM {$table_name}
             WHERE film LIKE %s
                OR name LIKE %s
                OR nominees LIKE %s
                OR canonical_category LIKE %s
                OR category LIKE %s
             ORDER BY winner DESC, ceremony DESC, id DESC
             LIMIT 80",
            $search_term,
            $search_term,
            $search_term,
            $search_term,
            $search_term
        );
        $rows        = $wpdb->get_results( $sql, ARRAY_A );

        if ( ! is_array( $rows ) || empty( $rows ) ) {
            return array();
        }

        $base_url = home_url( '/oscars/' );
        if ( class_exists( 'Academy_Awards_Table' ) ) {
            $aat = Academy_Awards_Table::get_instance();
            if ( $aat && method_exists( $aat, 'get_entity_base_url' ) ) {
                $base_url = $aat->get_entity_base_url();
            }
        }
        $base_url = trailingslashit( $base_url );

        $normalized_query = strtolower( $query_text );
        $matches          = array();

        $push_match = static function ( $key, $match ) use ( &$matches ) {
            if ( ! isset( $match['score'] ) ) {
                return;
            }

            if ( ! isset( $matches[ $key ] ) || intval( $match['score'] ) > intval( $matches[ $key ]['score'] ) ) {
                $matches[ $key ] = $match;
            }
        };

        $map_pipe_values = static function ( $values, $ids ) {
            $value_parts = array_values( array_filter( array_map( 'trim', explode( '|', (string) $values ) ), 'strlen' ) );
            $id_parts    = array_values( array_filter( array_map( 'trim', explode( '|', (string) $ids ) ), 'strlen' ) );

            if ( empty( $value_parts ) || count( $value_parts ) !== count( $id_parts ) ) {
                return array();
            }

            return array_combine( $id_parts, $value_parts );
        };

        foreach ( $rows as $row ) {
            $film    = trim( (string) ( $row['film'] ?? '' ) );
            $film_id = strtolower( trim( (string) ( $row['film_id'] ?? '' ) ) );

            if ( '' !== $film && preg_match( '/^tt\d+$/', $film_id ) ) {
                $film_score = function_exists( 'lunara_search_text_match_score' )
                    ? lunara_search_text_match_score( $film, $query_text )
                    : 0;
                if ( $film_score > 0 && intval( $row['winner'] ?? 0 ) > 0 ) {
                    $film_score += 4;
                }
            } else {
                $film_score = 0;
            }

            if ( $film_score > 0 ) {
                $push_match(
                    'title:' . $film_id,
                    array(
                        'kicker' => __( 'Oscar Title Match', 'lunara-film' ),
                        'title'  => $film,
                        'meta'   => sprintf(
                            /* translators: 1: ceremony number, 2: year */
                            __( '%1$s Ceremony / %2$s', 'lunara-film' ),
                            intval( $row['ceremony'] ?? 0 ),
                            trim( (string) ( $row['year'] ?? '' ) )
                        ),
                        'url'    => $base_url . 'title/' . rawurlencode( $film_id ) . '/',
                        'score'  => $film_score,
                    )
                );
            }

            $nominee_map = $map_pipe_values( $row['nominees'] ?? '', $row['nominee_ids'] ?? '' );
            foreach ( $nominee_map as $entity_id => $entity_label ) {
                $entity_id    = strtolower( trim( (string) $entity_id ) );
                $entity_label = trim( (string) $entity_label );
                $entity_score = function_exists( 'lunara_search_text_match_score' )
                    ? lunara_search_text_match_score( $entity_label, $query_text )
                    : 0;
                if ( $entity_score <= 0 ) {
                    continue;
                }

                if ( preg_match( '/^nm\d+$/', $entity_id ) ) {
                    $entity_type   = 'name';
                    $entity_kicker = __( 'Oscar Person Match', 'lunara-film' );
                } elseif ( preg_match( '/^co\d+$/', $entity_id ) ) {
                    $entity_type   = 'company';
                    $entity_kicker = __( 'Oscar Company Match', 'lunara-film' );
                } else {
                    continue;
                }

                $push_match(
                    $entity_type . ':' . $entity_id,
                    array(
                        'kicker' => $entity_kicker,
                        'title'  => $entity_label,
                        'meta'   => trim( (string) ( $row['category'] ?? $row['canonical_category'] ?? '' ) ),
                        'url'    => $base_url . $entity_type . '/' . rawurlencode( $entity_id ) . '/',
                        'score'  => $entity_score + ( intval( $row['winner'] ?? 0 ) > 0 ? 2 : 0 ),
                    )
                );
            }
        }

        uasort(
            $matches,
            static function ( $left, $right ) {
                return intval( $right['score'] ?? 0 ) <=> intval( $left['score'] ?? 0 );
            }
        );

        return array_slice( array_values( $matches ), 0, $limit );
    }
}

/**
 * Score a text label against a search query for title-first suggestion ranking.
 */
if ( ! function_exists( 'lunara_search_text_match_score' ) ) {
    function lunara_search_text_match_score( $label, $query_text ) {
        $label      = strtolower( trim( (string) $label ) );
        $query_text = strtolower( trim( (string) $query_text ) );

        if ( '' === $label || '' === $query_text ) {
            return 0;
        }

        if ( $label === $query_text ) {
            return 120;
        }

        if ( str_starts_with( $label, $query_text ) ) {
            return 102;
        }

        $query_length = function_exists( 'mb_strlen' ) ? mb_strlen( $query_text ) : strlen( $query_text );
        $label_words  = preg_split( '/\s+/', $label );
        $word_count   = is_array( $label_words ) ? count( array_filter( $label_words ) ) : 0;

        $tokens = preg_split( '/\s+/', $query_text );
        $tokens = is_array( $tokens ) ? array_values( array_filter( $tokens ) ) : array();

        if ( preg_match( '/(^|[^a-z0-9])' . preg_quote( $query_text, '/' ) . '([^a-z0-9]|$)/i', $label ) ) {
            if ( count( $tokens ) > 1 || $word_count <= 5 ) {
                return 88;
            }

            return 0;
        }

        if ( count( $tokens ) > 1 ) {
            $all_tokens_present = true;
            foreach ( $tokens as $token ) {
                if ( false === strpos( $label, $token ) ) {
                    $all_tokens_present = false;
                    break;
                }
            }

            if ( $all_tokens_present ) {
                return 82;
            }
        }

        if ( $query_length < 3 ) {
            return 0;
        }

        if ( false !== strpos( $label, $query_text ) && $word_count <= 5 ) {
            return 70;
        }

        return 0;
    }
}


/**
 * Poster carousel controls.
 */
function lunara_output_carousel_controls_js() {
    if ( ! is_front_page() && ! is_page( 'oscars' ) && ! is_page_template( 'page-oscars.php' ) ) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('[data-lunara-carousel]').forEach(function(section) {
            const track = section.querySelector('[data-lunara-carousel-track]');
            const prev = section.querySelector('[data-lunara-carousel-prev]');
            const next = section.querySelector('[data-lunara-carousel-next]');
            if (!track) return;
            function amount() {
                const card = track.children[0];
                const styles = window.getComputedStyle(track);
                const gap = parseInt(styles.columnGap || styles.gap || 24, 10);
                return card ? card.offsetWidth + gap : 360;
            }
            function step(direction) {
                const distance = amount() * direction;
                const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
                if (direction > 0 && track.scrollLeft + distance >= maxScroll - 6) {
                    track.scrollTo({ left: 0, behavior: 'smooth' });
                    return;
                }
                if (direction < 0 && track.scrollLeft <= 6) {
                    track.scrollTo({ left: maxScroll, behavior: 'smooth' });
                    return;
                }
                track.scrollBy({ left: distance, behavior: 'smooth' });
            }
            if (prev) {
                prev.addEventListener('click', function () {
                    step(-1);
                });
            }
            if (next) {
                next.addEventListener('click', function () {
                    step(1);
                });
            }

            const autoplay = parseInt(section.getAttribute('data-lunara-carousel-autoplay') || '0', 10);
            if (!reduceMotion && autoplay > 0 && window.innerWidth > 900) {
                let timer = null;
                const stop = function () {
                    if (timer) {
                        window.clearInterval(timer);
                        timer = null;
                    }
                };
                const start = function () {
                    stop();
                    timer = window.setInterval(function () {
                        step(1);
                    }, autoplay);
                };
                section.addEventListener('mouseenter', stop);
                section.addEventListener('mouseleave', start);
                section.addEventListener('focusin', stop);
                section.addEventListener('focusout', start);
                document.addEventListener('visibilitychange', function () {
                    if (document.hidden) {
                        stop();
                    } else {
                        start();
                    }
                });
                start();
            }
        });
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'lunara_output_carousel_controls_js', 99 );

/**
 * Wave 2: Image fade-in on load.
 */
function lunara_output_image_fadein_js() {
    ?>
    <script>
    (function(){
        function markLoaded(img){img.classList.add('lunara-img-loaded');}
        function processImg(img){
            if(img.complete&&img.naturalWidth>0){markLoaded(img);return;}
            img.addEventListener('load',function(){markLoaded(img);});
            img.addEventListener('error',function(){markLoaded(img);});
        }
        var sels='.lunara-review-grid-poster,.lunara-review-feature-image,.lunara-poster-card-image,.lunara-dispatch-archive-thumb,.lunara-dispatch-lead-image,.lunara-home-pulse-poster,.aat-filmography-poster,.aat-entity-poster';
        document.querySelectorAll(sels).forEach(processImg);
        if(window.MutationObserver){
            new MutationObserver(function(mutations){
                mutations.forEach(function(m){
                    m.addedNodes.forEach(function(n){
                        if(n.nodeType===1){
                            if(n.matches&&n.matches(sels))processImg(n);
                            n.querySelectorAll&&n.querySelectorAll(sels).forEach(processImg);
                        }
                    });
                });
            }).observe(document.body,{childList:true,subtree:true});
        }
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'lunara_output_image_fadein_js', 100 );

/**
 * Wave 3: Scroll-triggered reveals.
 */
function lunara_output_scroll_reveal_js() {
    ?>
    <script>
    (function(){
        if(window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
        // Only run scroll reveals on the front page — skip portal, plugin, single review, and other pages
        var isFrontPage=document.body.classList.contains('home')||document.querySelector('.lunara-front-page');
        var isPluginPage=document.querySelector('.aat-hub-page,.aat-entity-page');
        var revealSels=[];
        var staggerSels=[];
        if(isFrontPage){
            revealSels=[
                '.lunara-front-page>.lunara-home-section','.lunara-review-grid-card','.lunara-review-feature-card',
                '.lunara-poster-card','.lunara-ledger-card','.lunara-dispatch-archive-card'
            ];
            staggerSels=[
                '.lunara-review-grid','.lunara-review-related-grid'
            ];
        }
        // Entity pages get targeted reveals for stats/timeline only
        if(isPluginPage){
            revealSels=['.aat-entity-status-banner','.aat-stat','.aat-timeline-card'];
            staggerSels=['.aat-stats-bar','.aat-timeline-list'];
        }
        if(!revealSels.length)return;
        revealSels.forEach(function(s){
            document.querySelectorAll(s).forEach(function(el){el.classList.add('lunara-reveal');});
        });
        staggerSels.forEach(function(s){
            document.querySelectorAll(s).forEach(function(el){el.classList.add('lunara-reveal-stagger');});
        });
        var obs=new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if(entry.isIntersecting){
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        },{threshold:0.08,rootMargin:'0px 0px -40px 0px'});
        document.querySelectorAll('.lunara-reveal').forEach(function(el){obs.observe(el);});
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'lunara_output_scroll_reveal_js', 101 );

// Sticky sidebar deferred to standalone theme (Tier 4).
// Blocksy's scroll container architecture defeats both CSS sticky and JS fixed positioning.
// The sidebar renders correctly in place; it just doesn't follow the reader yet.

/**
 * Wave 5: Oscar stats count-up animation.
 */
function lunara_output_stats_countup_js() {
    if ( ! is_singular() ) {
        return;
    }
    ?>
    <script>
    (function(){
        var stats=document.querySelectorAll('.aat-stat-number');
        if(!stats.length||window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
        var obs=new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if(!entry.isIntersecting)return;
                obs.unobserve(entry.target);
                var el=entry.target,text=el.textContent.trim();
                var match=text.match(/^([\d,]+)(.*)/);
                if(!match)return;
                var target=parseInt(match[1].replace(/,/g,''),10);
                var suffix=match[2];
                if(isNaN(target)||target===0)return;
                var duration=Math.min(1600,Math.max(600,target*8));
                var start=performance.now();
                function tick(now){
                    var t=Math.min(1,(now-start)/duration);
                    var ease=1-Math.pow(1-t,3);
                    var current=Math.round(target*ease);
                    el.textContent=current.toLocaleString()+suffix;
                    if(t<1)requestAnimationFrame(tick);
                }
                el.textContent='0'+suffix;
                requestAnimationFrame(tick);
            });
        },{threshold:0.3});
        stats.forEach(function(el){obs.observe(el);});
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'lunara_output_stats_countup_js', 102 );

/**
 * Build review-specific SEO/social metadata from the same reader hook used by cards.
 */
if ( ! function_exists( 'lunara_get_review_seo_summary' ) ) {
    function lunara_get_review_seo_summary( $post_id, $words = 32 ) {
        $post_id = intval( $post_id );
        $words   = max( 18, intval( $words ) );

        if ( $post_id <= 0 ) {
            return '';
        }

        $summary = '';

        if ( function_exists( 'lunara_get_review_card_pull_quote' ) ) {
            $summary = lunara_get_review_card_pull_quote( $post_id, $words );
        }

        if ( '' === trim( $summary ) ) {
            $summary = has_excerpt( $post_id )
                ? get_the_excerpt( $post_id )
                : get_post_field( 'post_content', $post_id );
        }

        $summary = strip_shortcodes( (string) $summary );
        $summary = html_entity_decode( $summary, ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
        $summary = wp_strip_all_tags( $summary );
        $summary = preg_replace( '/\s+/', ' ', $summary );
        $summary = trim( (string) $summary );

        if ( '' === $summary ) {
            return '';
        }

        return wp_html_excerpt( $summary, 190, '...' );
    }
}

if ( ! function_exists( 'lunara_get_review_social_image_url' ) ) {
    function lunara_get_review_social_image_url( $post_id ) {
        $post_id = intval( $post_id );

        if ( $post_id <= 0 ) {
            return '';
        }

        $image_urls = array(
            get_post_meta( $post_id, '_lunara_review_hero_banner', true ),
            get_post_meta( $post_id, '_lunara_review_card_image', true ),
        );

        foreach ( $image_urls as $image_url ) {
            $image_url = trim( (string) $image_url );

            if ( '' === $image_url ) {
                continue;
            }

            $attachment_id = attachment_url_to_postid( $image_url );
            if ( $attachment_id > 0 ) {
                $large_url = wp_get_attachment_image_url( $attachment_id, 'large' );
                if ( $large_url ) {
                    return (string) $large_url;
                }
            }

            return esc_url_raw( $image_url );
        }

        if ( has_post_thumbnail( $post_id ) ) {
            $thumbnail_id  = get_post_thumbnail_id( $post_id );
            $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'large' ) : '';

            if ( $thumbnail_url ) {
                return (string) $thumbnail_url;
            }

            return (string) get_the_post_thumbnail_url( $post_id, 'large' );
        }

        return '';
    }
}

if ( ! function_exists( 'lunara_filter_review_jetpack_seo_meta_tags' ) ) {
    function lunara_filter_review_jetpack_seo_meta_tags( $meta ) {
        if ( ! is_singular( 'review' ) || ! is_array( $meta ) ) {
            return $meta;
        }

        $summary = lunara_get_review_seo_summary( get_queried_object_id() );

        if ( '' !== $summary ) {
            $meta['description'] = $summary;
        }

        return $meta;
    }
}
add_filter( 'jetpack_seo_meta_tags', 'lunara_filter_review_jetpack_seo_meta_tags', 20 );

if ( ! function_exists( 'lunara_get_brand_social_image_url' ) ) {
    function lunara_get_brand_social_image_url() {
        $candidate_ids = array(
            (int) get_option( 'site_icon' ),
            (int) get_option( 'lunara_home_identity_logo_id' ),
            (int) get_theme_mod( 'custom_logo' ),
        );

        foreach ( $candidate_ids as $attachment_id ) {
            if ( $attachment_id <= 0 ) {
                continue;
            }

            $image_url = wp_get_attachment_image_url( $attachment_id, 'full' );
            if ( $image_url ) {
                return (string) $image_url;
            }
        }

        return '';
    }
}

if ( ! function_exists( 'lunara_should_use_brand_social_image_fallback' ) ) {
    function lunara_should_use_brand_social_image_fallback() {
        if ( is_singular( 'review' ) ) {
            return false;
        }

        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            if ( $post_id > 0 && has_post_thumbnail( $post_id ) ) {
                return false;
            }
        }

        return true;
    }
}

if ( ! function_exists( 'lunara_filter_brand_jetpack_seo_meta_tags' ) ) {
    function lunara_filter_brand_jetpack_seo_meta_tags( $meta ) {
        if ( ! is_array( $meta ) || ! lunara_should_use_brand_social_image_fallback() ) {
            return $meta;
        }

        $image_url = lunara_get_brand_social_image_url();
        if ( '' === $image_url ) {
            return $meta;
        }

        if ( empty( $meta['twitter:image'] ) ) {
            $meta['twitter:image'] = $image_url;
        }

        if ( empty( $meta['twitter:card'] ) ) {
            $meta['twitter:card'] = 'summary_large_image';
        }

        return $meta;
    }
}
add_filter( 'jetpack_seo_meta_tags', 'lunara_filter_brand_jetpack_seo_meta_tags', 30 );

if ( ! function_exists( 'lunara_filter_brand_jetpack_open_graph_tags' ) ) {
    function lunara_filter_brand_jetpack_open_graph_tags( $tags ) {
        if ( ! is_array( $tags ) || ! lunara_should_use_brand_social_image_fallback() ) {
            return $tags;
        }

        $image_url = lunara_get_brand_social_image_url();
        if ( '' === $image_url ) {
            return $tags;
        }

        if ( empty( $tags['og:image'] ) ) {
            $tags['og:image'] = $image_url;
        }

        if ( empty( $tags['og:image:alt'] ) ) {
            $tags['og:image:alt'] = __( 'Lunara Film icon', 'lunara-film' );
        }

        return $tags;
    }
}
add_filter( 'jetpack_open_graph_tags', 'lunara_filter_brand_jetpack_open_graph_tags', 30 );

if ( ! function_exists( 'lunara_output_brand_social_image_fallback_meta' ) ) {
    function lunara_output_brand_social_image_fallback_meta() {
        if ( ! lunara_should_use_brand_social_image_fallback() ) {
            return;
        }

        $image_url = lunara_get_brand_social_image_url();
        if ( '' === $image_url ) {
            return;
        }

        echo "\n" . '<meta property="og:image" content="' . esc_url( $image_url ) . '">' . "\n";
        echo '<meta property="og:image:alt" content="' . esc_attr__( 'Lunara Film icon', 'lunara-film' ) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '">' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    }
}
add_action( 'wp_head', 'lunara_output_brand_social_image_fallback_meta', 99 );

if ( ! function_exists( 'lunara_filter_review_jetpack_open_graph_tags' ) ) {
    function lunara_filter_review_jetpack_open_graph_tags( $tags ) {
        if ( ! is_singular( 'review' ) || ! is_array( $tags ) ) {
            return $tags;
        }

        $post_id     = get_queried_object_id();
        $title       = trim( wp_strip_all_tags( get_the_title( $post_id ) ) );
        $description = lunara_get_review_seo_summary( $post_id );
        $image_url   = lunara_get_review_social_image_url( $post_id );

        if ( '' !== $title ) {
            $tags['og:title'] = $title;
        }

        if ( '' !== $description ) {
            $tags['og:description'] = $description;
        }

        if ( '' !== $image_url ) {
            $tags['og:image']     = $image_url;
            $tags['og:image:alt'] = '' !== $title ? sprintf( __( '%s artwork', 'lunara-film' ), $title ) : __( 'Review artwork', 'lunara-film' );
            unset( $tags['og:image:width'], $tags['og:image:height'] );
        }

        return $tags;
    }
}
add_filter( 'jetpack_open_graph_tags', 'lunara_filter_review_jetpack_open_graph_tags', 20 );

if ( ! function_exists( 'lunara_output_review_seo_meta' ) ) {
    function lunara_output_review_seo_meta() {
        if ( ! is_singular( 'review' ) ) {
            return;
        }

        $post_id = get_queried_object_id();

        if ( $post_id <= 0 ) {
            return;
        }

        $title       = trim( wp_strip_all_tags( get_the_title( $post_id ) ) );
        $description = lunara_get_review_seo_summary( $post_id );
        $url         = get_permalink( $post_id );
        $image_url   = lunara_get_review_social_image_url( $post_id );
        $site_name   = get_bloginfo( 'name' );
        $author_name = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) );
        $year        = trim( (string) get_post_meta( $post_id, '_lunara_year', true ) );
        $score       = trim( (string) get_post_meta( $post_id, '_lunara_score', true ) );
        $movie_name  = preg_replace( '/\s*\(\d{4}\)\s*$/', '', $title );
        $jetpack_meta_active = function_exists( 'jetpack_og_tags' ) || class_exists( 'Jetpack_SEO' );

        if ( '' === trim( (string) $movie_name ) ) {
            $movie_name = $title;
        }

        if ( '' === $description ) {
            $description = sprintf( __( 'A Lunara Film review of %s.', 'lunara-film' ), $title );
        }

        if ( ! $jetpack_meta_active ) {
            echo "\n" . '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
            echo '<meta property="og:type" content="article">' . "\n";
            echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
            echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
            echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
            echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";

            if ( '' !== $image_url ) {
                echo '<meta property="og:image" content="' . esc_url( $image_url ) . '">' . "\n";
            }

            echo '<meta name="twitter:card" content="' . esc_attr( '' !== $image_url ? 'summary_large_image' : 'summary' ) . '">' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
            echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";

            if ( '' !== $image_url ) {
                echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '">' . "\n";
            }
        }

        $schema = array(
            '@context'         => 'https://schema.org',
            '@type'            => 'Review',
            'headline'         => $title,
            'name'             => sprintf( __( '%s review', 'lunara-film' ), $title ),
            'description'      => $description,
            'url'              => $url,
            'mainEntityOfPage' => $url,
            'datePublished'    => get_the_date( DATE_W3C, $post_id ),
            'dateModified'     => get_the_modified_date( DATE_W3C, $post_id ),
            'inLanguage'       => get_bloginfo( 'language' ),
            'author'           => array(
                '@type' => 'Person',
                'name'  => '' !== $author_name ? $author_name : __( 'Lunara Film', 'lunara-film' ),
            ),
            'publisher'        => array(
                '@type' => 'Organization',
                'name'  => $site_name,
                'url'   => home_url( '/' ),
            ),
            'itemReviewed'     => array(
                '@type' => 'Movie',
                'name'  => $movie_name,
            ),
        );

        if ( '' !== $year ) {
            $schema['itemReviewed']['dateCreated'] = $year;
        }

        if ( '' !== $image_url ) {
            $schema['image'] = $image_url;
        }

        if ( is_numeric( $score ) ) {
            $schema['reviewRating'] = array(
                '@type'       => 'Rating',
                'ratingValue' => (float) $score,
                'bestRating'  => 5,
                'worstRating' => 0,
            );
        }

        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }
}
add_action( 'wp_head', 'lunara_output_review_seo_meta', 7 );

/**
 * Suppress Blocksy's native footer via CSS so ours is the only one.
 */
function lunara_hide_blocksy_footer_css() {
    if ( ! apply_filters( 'lunara_use_custom_footer', true ) ) {
        return;
    }

    echo '<style id="lunara-hide-blocksy-footer">.ct-footer,footer.site-footer:not(.lunara-site-footer){display:none!important;}#header .menu-item-27569 .ct-menu-badge{display:none!important;}.lunara-site-footer{position:relative;overflow:hidden;margin-top:clamp(76px,8vw,120px);padding:clamp(48px,6vw,86px) 0 54px;background:radial-gradient(circle at top center,rgba(201,169,97,.08),transparent 34%),linear-gradient(180deg,rgba(15,29,46,.95),rgba(10,21,32,.98));border-top:1px solid rgba(201,169,97,.24);color:var(--lunara-text,#FAFBFC);}.lunara-footer-inner{max-width:min(100%,1360px);margin:0 auto;padding-inline:var(--lunara-shell-pad,28px);display:grid;gap:48px;}.lunara-footer-brand{display:flex;flex-direction:column;align-items:center;gap:16px;text-align:center;}.lunara-footer-logo{max-height:var(--lunara-logo-max,64px);width:auto;}.lunara-footer-tagline{max-width:48ch;margin:0;color:var(--lunara-text-muted,#A8A8B8);font-size:.98rem;line-height:1.55;}.lunara-footer-nav-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:32px;}.lunara-footer-nav-col{padding:20px;border:1px solid rgba(201,169,97,.12);border-radius:22px;background:linear-gradient(180deg,rgba(18,31,47,.82),rgba(10,20,32,.56));}.lunara-footer-col-heading{margin:0 0 16px;color:var(--lunara-gold,#c9a961);font-size:.78rem;letter-spacing:.14em;text-transform:uppercase;}.lunara-footer-nav-col ul,.lunara-site-footer .menu{list-style:none!important;margin:0!important;padding:0!important;display:grid!important;gap:10px!important;}.lunara-footer-nav-col li,.lunara-site-footer .menu li{list-style:none!important;margin:0!important;padding:0!important;}.lunara-footer-nav-col a,.lunara-site-footer .menu a{display:inline-flex!important;align-items:center;min-height:38px;padding:8px 14px;border:1px solid rgba(201,169,97,.12);border-radius:999px;background:rgba(255,255,255,.02);color:var(--lunara-text,#FAFBFC)!important;font-size:.78rem;letter-spacing:.08em;text-transform:uppercase;text-decoration:none!important;}.lunara-footer-nav-col a:hover,.lunara-site-footer .menu a:hover{border-color:rgba(201,169,97,.32);color:var(--lunara-gold-light,#e0c481)!important;}.lunara-footer-utility{display:flex;justify-content:space-between;gap:14px 18px;flex-wrap:wrap;padding-top:24px;border-top:1px solid rgba(201,169,97,.24);color:var(--lunara-text-muted,#A8A8B8);font-size:.78rem;}.lunara-footer-utility a{color:var(--lunara-text-muted,#A8A8B8)!important;text-decoration:none!important;}@media(max-width:900px){.lunara-footer-nav-grid{grid-template-columns:repeat(2,minmax(0,1fr));}}@media(max-width:640px){.lunara-footer-nav-grid{grid-template-columns:1fr;}.lunara-footer-utility{justify-content:center;text-align:center;}}</style>' . "\n";
}
add_action( 'wp_head', 'lunara_hide_blocksy_footer_css', 100 );

/**
 * Critical Journal single-page guardrails.
 *
 * Jetpack Boost can inline only a subset of the main stylesheet on first paint,
 * so keep this small page-specific CSS in wp_head where it survives optimization.
 */
function lunara_output_journal_single_guardrail_css() {
    if ( ! is_singular( 'journal' ) ) {
        return;
    }
    ?>
    <style id="lunara-journal-single-guardrail-css">
    body.single-journal,body.single-journal #main-container{max-width:100%!important;overflow-x:hidden!important;}
    body.single-journal .lunara-journal-single-page{width:100%;max-width:min(100%,1440px)!important;margin-inline:auto!important;color:var(--lunara-text,#FAFBFC)!important;overflow-x:hidden!important;}
    body.single-journal .lunara-journal-cinematic-hero,body.single-journal .lunara-journal-cinematic-hero-header{max-width:100%!important;box-sizing:border-box!important;}
    body.single-journal .lunara-journal-cinematic-hero-header{padding-inline:clamp(18px,4vw,56px)!important;text-align:center!important;}
    body.single-journal .lunara-journal-cinematic-hero-inner{margin-inline:auto!important;justify-items:center!important;text-align:center!important;}
    body.single-journal .lunara-journal-cinematic-hero-frame{position:relative!important;display:block!important;width:min(calc(100% - clamp(36px,8vw,112px)),1080px)!important;max-width:100%!important;aspect-ratio:16/9!important;height:auto!important;min-height:0!important;margin-inline:auto!important;box-sizing:border-box!important;overflow:hidden!important;}
    body.single-journal .lunara-journal-cinematic-hero-media{position:absolute!important;inset:0!important;display:block!important;width:100%!important;height:100%!important;margin:0!important;}
    body.single-journal .lunara-journal-cinematic-hero-image{display:block!important;width:100%!important;height:100%!important;max-width:100%!important;object-fit:cover!important;object-position:center!important;}
    body.single-journal .lunara-journal-cinematic-hero-credit{position:absolute!important;left:clamp(14px,2vw,24px)!important;right:clamp(14px,2vw,24px)!important;bottom:clamp(12px,2vw,22px)!important;z-index:5!important;display:block!important;width:fit-content!important;max-width:min(92%,720px)!important;margin:0!important;padding:8px 11px!important;border:1px solid rgba(244,239,227,.2)!important;border-radius:999px!important;background:rgba(5,11,18,.76)!important;color:rgba(244,239,227,.88)!important;font-size:.78rem!important;line-height:1.35!important;backdrop-filter:blur(10px)!important;}
    body.single-journal .lunara-journal-cinematic-hero-credit a{color:var(--lunara-gold-light,#e0c481)!important;text-decoration:none!important;}
    body.single-journal .lunara-journal-cinematic-hero-credit a:hover{text-decoration:underline!important;}
    body.single-journal .lunara-journal-cinematic-hero .lunara-review-single-title{max-width:min(100%,980px)!important;margin-inline:auto!important;color:var(--lunara-gold-light,#e0c481)!important;text-align:center!important;text-wrap:balance;}
    body.single-journal .lunara-journal-cinematic-hero .lunara-review-single-meta,body.single-journal .lunara-journal-single-signal{justify-content:center!important;text-align:center!important;}
    body.single-journal .lunara-journal-cinematic-hero-inner{max-width:100%!important;min-width:0!important;overflow-wrap:anywhere!important;}
    body.single-journal .lunara-journal-cinematic-hero .lunara-review-single-title{min-width:0!important;overflow-wrap:anywhere!important;}
    body.single-journal .lunara-review-single-body{width:min(calc(100% - clamp(36px,8vw,112px)),920px)!important;max-width:920px!important;margin:clamp(20px,3vw,38px) auto 0!important;padding:clamp(20px,3vw,34px)!important;box-sizing:border-box!important;border:1px solid rgba(201,169,97,.16)!important;border-radius:22px!important;background:linear-gradient(180deg,rgba(15,29,46,.72),rgba(8,16,27,.54))!important;box-shadow:0 24px 58px rgba(0,0,0,.22)!important;}
    body.single-journal .lunara-review-single-body::before{display:none!important;}
    body.single-journal .lunara-review-single-body-grid{display:block!important;width:100%!important;max-width:100%!important;min-width:0!important;margin-inline:auto!important;}
    body.single-journal .lunara-review-single-content{width:100%!important;max-width:74ch!important;min-width:0!important;margin-inline:auto!important;overflow-wrap:break-word!important;}
    body.single-journal .lunara-review-single-content p{max-width:74ch!important;margin-inline:auto!important;font-size:clamp(1rem,1.05vw,1.12rem)!important;line-height:1.78!important;color:var(--lunara-text,#FAFBFC)!important;overflow-wrap:break-word!important;}
    body.single-journal .lunara-review-single-content a:not(.lunara-reader-toc-link){display:inline!important;max-width:100%!important;color:var(--lunara-gold-light,#e0c481)!important;text-decoration:underline!important;text-decoration-color:rgba(224,196,129,.58)!important;text-decoration-thickness:1px!important;text-underline-offset:.22em!important;white-space:normal!important;overflow-wrap:anywhere!important;word-break:break-word!important;}
    body.single-journal .lunara-review-single-content a:not(.lunara-reader-toc-link):hover,body.single-journal .lunara-review-single-content a:not(.lunara-reader-toc-link):focus-visible{color:#f4efe3!important;text-decoration-color:rgba(244,239,227,.82)!important;}
    body.single-journal .lunara-review-single-rail{width:100%!important;max-width:74ch!important;margin:clamp(22px,3vw,34px) auto 0!important;}
    body.single-journal .lunara-review-single-rail-sticky{position:static!important;display:grid!important;gap:16px!important;}
    body.single-journal .lunara-review-single-rail-actions .lunara-btn{display:inline-flex!important;align-items:center!important;justify-content:center!important;max-width:100%!important;min-height:42px!important;padding:10px 16px!important;box-sizing:border-box!important;border:1px solid rgba(201,169,97,.28)!important;border-radius:999px!important;background:rgba(201,169,97,.08)!important;color:var(--lunara-gold-light,#e0c481)!important;text-align:center!important;text-decoration:none!important;white-space:normal!important;}
    body.single-journal .lunara-journal-single-related{width:min(calc(100% - 80px),1160px)!important;margin:clamp(36px,5vw,72px) auto!important;padding-inline:0!important;}
    body.single-journal .lunara-journal-single-related .lunara-home-section-head{margin-bottom:22px!important;}
    body.single-journal .lunara-journal-single-related .lunara-home-section-kicker{color:var(--lunara-gold-light,#e0c481)!important;}
    body.single-journal .lunara-journal-single-related .lunara-home-section-title{color:var(--lunara-text,#FAFBFC)!important;font-size:clamp(1.65rem,2.6vw,2.35rem)!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-related-grid{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:24px!important;overflow:visible!important;padding:0!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-card{width:100%!important;min-width:0!important;max-width:none!important;min-height:0!important;overflow:hidden!important;border:1px solid rgba(201,169,97,.2)!important;border-radius:22px!important;background:linear-gradient(180deg,rgba(15,29,46,.94),rgba(10,21,32,.98))!important;box-shadow:0 24px 54px rgba(0,0,0,.28)!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-link{display:grid!important;grid-template-rows:auto 1fr!important;width:100%!important;height:100%!important;color:inherit!important;text-decoration:none!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-poster-wrap{aspect-ratio:16/10!important;width:100%!important;max-height:none!important;min-height:0!important;overflow:hidden!important;border-radius:22px 22px 0 0!important;background:rgba(255,255,255,.04)!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-poster-wrap img,body.single-journal .lunara-journal-single-related .lunara-review-grid-poster{display:block!important;width:100%!important;height:100%!important;object-fit:cover!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-copy{display:grid!important;gap:10px!important;align-content:start!important;padding:18px 20px 22px!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-kicker{margin:0!important;color:var(--lunara-gold-light,#e0c481)!important;font-size:.72rem!important;letter-spacing:.14em!important;text-transform:uppercase!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-title{margin:0!important;color:var(--lunara-gold,#c9a961)!important;font-size:clamp(1.05rem,1.4vw,1.28rem)!important;line-height:1.16!important;text-decoration:none!important;overflow-wrap:anywhere!important;}
    body.single-journal .lunara-journal-single-related .lunara-review-grid-meta{margin:0!important;color:var(--lunara-text-muted,#A8A8B8)!important;font-size:.88rem!important;}
    body.single-journal .lunara-journal-image-carousel{width:min(calc(100% - 36px),1080px)!important;max-width:min(calc(100% - 36px),1080px)!important;margin:clamp(18px,3vw,34px) auto 0!important;box-sizing:border-box!important;overflow:hidden!important;}
    body.single-journal .lunara-journal-image-carousel-head{display:flex!important;align-items:end!important;justify-content:space-between!important;gap:16px!important;}
    body.single-journal .lunara-journal-image-carousel-controls{display:inline-flex!important;align-items:center!important;gap:8px!important;flex:0 0 auto!important;}
    body.single-journal .lunara-journal-carousel-btn{display:inline-grid!important;place-items:center!important;width:36px!important;height:36px!important;min-width:36px!important;min-height:36px!important;margin:0!important;padding:0!important;border:1px solid rgba(201,169,97,.45)!important;border-radius:999px!important;background:rgba(5,11,18,.72)!important;color:var(--lunara-gold-light,#e0c481)!important;font-size:1.1rem!important;line-height:1!important;box-shadow:0 10px 24px rgba(0,0,0,.22)!important;cursor:pointer!important;}
    body.single-journal .lunara-journal-carousel-btn:hover,body.single-journal .lunara-journal-carousel-btn:focus-visible{background:rgba(201,169,97,.18)!important;color:#f4efe3!important;outline:2px solid rgba(224,196,129,.36)!important;outline-offset:2px!important;}
    body.single-journal .lunara-journal-image-carousel-track{display:grid!important;grid-auto-flow:column!important;grid-auto-columns:minmax(280px,74%)!important;gap:14px!important;max-width:100%!important;overflow-x:auto!important;scroll-snap-type:x mandatory!important;padding:0 2px 12px!important;}
    body.single-journal .lunara-journal-image-carousel-slide{min-width:0!important;max-width:100%!important;scroll-snap-align:start!important;}
    body.single-journal .lunara-journal-image-carousel-image{display:block!important;width:100%!important;height:clamp(190px,48vw,420px)!important;max-height:420px!important;aspect-ratio:16/9!important;object-fit:cover!important;}
    @media (max-width:980px){body.single-journal .lunara-journal-single-related .lunara-review-related-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important;}}
    @media (max-width:640px){body.single-journal{width:100vw!important;max-width:100vw!important;overflow-x:hidden!important;}body.single-journal .lunara-journal-single-page{width:100vw!important;max-width:100vw!important;padding-inline:0!important;overflow-x:hidden!important;}body.single-journal .lunara-journal-cinematic-hero-header{padding-inline:18px!important;text-align:center!important;}body.single-journal .lunara-journal-cinematic-hero .lunara-review-single-title{width:min(100%,282px)!important;max-width:282px!important;margin-inline:auto!important;font-size:clamp(1.55rem,7.1vw,1.82rem)!important;line-height:1.12!important;white-space:normal!important;overflow-wrap:normal!important;word-break:normal!important;text-wrap:balance!important;}body.single-journal .lunara-journal-cinematic-hero-frame{width:calc(100vw - 36px)!important;max-width:calc(100vw - 36px)!important;height:clamp(240px,74vw,360px)!important;}body.single-journal .lunara-journal-cinematic-hero-image{object-position:62% center!important;}body.single-journal .lunara-journal-cinematic-hero-credit{bottom:10px!important;border-radius:12px!important;font-size:.72rem!important;}body.single-journal .lunara-review-single-body{width:calc(100vw - 48px)!important;max-width:calc(100vw - 48px)!important;margin-top:18px!important;padding:18px!important;border-radius:18px!important;overflow-x:hidden!important;}body.single-journal .lunara-review-single-body-grid{display:block!important;width:100%!important;max-width:100%!important;overflow-x:hidden!important;}body.single-journal .lunara-review-single-content{display:block!important;width:min(100%,300px)!important;max-width:300px!important;margin-inline:auto!important;overflow-x:hidden!important;}body.single-journal .lunara-review-single-content p{max-width:100%!important;margin-inline:0!important;font-size:.98rem!important;line-height:1.72!important;}body.single-journal .lunara-review-single-content p,body.single-journal .lunara-review-single-content p *{white-space:normal!important;overflow-wrap:anywhere!important;word-break:break-word!important;}body.single-journal .lunara-review-single-content a:not(.lunara-reader-toc-link),body.single-journal .lunara-review-single-content em{display:inline!important;white-space:normal!important;overflow-wrap:anywhere!important;word-break:break-word!important;}body.single-journal .lunara-review-single-rail{max-width:100%!important;}body.single-journal .lunara-journal-image-carousel{width:calc(100vw - 36px)!important;max-width:calc(100vw - 36px)!important;}body.single-journal .lunara-journal-image-carousel-head{display:grid!important;align-items:start!important;}body.single-journal .lunara-journal-image-carousel-controls{justify-self:start!important;}body.single-journal .lunara-journal-image-carousel-track{grid-auto-columns:100%!important;}body.single-journal .lunara-journal-image-carousel-image{height:190px!important;max-height:190px!important;}body.single-journal .lunara-journal-single-related{width:calc(100vw - 48px)!important;max-width:calc(100vw - 48px)!important;padding-inline:0!important;}body.single-journal .lunara-journal-single-related .lunara-review-related-grid{grid-template-columns:1fr!important;}}
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_journal_single_guardrail_css', 101 );

/**
 * Late public guardrails for the Lunara OS layout QA lane.
 */
function lunara_output_os_responsive_guardrail_css() {
    if ( is_admin() || is_feed() ) {
        return;
    }
    ?>
    <style id="lunara-os-responsive-guardrails">
    body.home .lunara-front-page,
    body.home main.lunara-front-page,
    .lunara-front-page,
    .lunara-archive-page,
    .lunara-editorial-single-page,
    .lunara-oscars-portal,
    .lunara-home-section,
    .lunara-review-single-page,
    .lunara-review-single-page *,
    .lunara-review-single-body-grid,
    .lunara-review-single-content,
    .lunara-review-single-rail,
    .lunara-review-single-rail-sticky,
    .lunara-review-single-debrief-shell,
    .lunara-review-single-debrief-wrap,
    .aat-container,
    .aat-container * {
        box-sizing: border-box !important;
    }

    body.home .lunara-front-page,
    body.home main.lunara-front-page,
    .aat-container {
        max-width: 100% !important;
        overflow-x: clip !important;
        width: 100% !important;
    }

    body.home .lunara-front-page > .lunara-home-section,
    body.home main.lunara-front-page > .lunara-home-section,
    .lunara-review-single-page,
    .lunara-review-single-page article,
    .lunara-review-single-body,
    .lunara-review-single-body-grid,
    .lunara-review-single-content,
    .lunara-review-single-cinematic-hero,
    .lunara-review-visual,
    .lunara-review-visual-frame,
    .lunara-review-single-rail,
    .lunara-review-single-rail-sticky,
    .aat-entity-page,
    .aat-hub-page,
    .aat-hub-section,
    .aat-entity-hero,
    .aat-entity-main,
    .aat-filmography-grid,
    .aat-timeline,
    .aat-records-grid,
    .aat-table-wrapper {
        max-width: 100% !important;
        min-width: 0 !important;
    }

    .lunara-review-visual-frame {
        overflow: hidden !important;
    }

    .lunara-review-grid-poster-wrap,
    .lunara-review-single-debrief-poster-shell {
        overflow: hidden !important;
    }

    .lunara-review-visual-frame .lunara-review-visual-image {
        display: block !important;
        height: auto !important;
        max-width: 100% !important;
        width: 100% !important;
    }

    .lunara-review-visual--hero .lunara-review-visual-frame,
    .lunara-review-single-hero-media .lunara-review-visual-frame {
        aspect-ratio: 16 / 9 !important;
    }

    .lunara-review-visual--hero .lunara-review-visual-image,
    .lunara-review-single-hero-media .lunara-review-visual-image {
        height: 100% !important;
        object-fit: cover !important;
    }

    .lunara-review-grid-poster,
    .lunara-review-single-debrief-poster {
        display: block !important;
        height: auto !important;
        max-width: 100% !important;
        width: 100% !important;
    }

    @media (max-width: 820px) {
        html,
        body,
        #main-container,
        .ct-container,
        .ct-container-full,
        .site-main,
        .entry-content {
            max-width: 100% !important;
            min-width: 0 !important;
            overflow-x: clip !important;
        }

        body.home .lunara-front-page,
        body.home main.lunara-front-page,
        .lunara-front-page,
        .lunara-archive-page,
        .lunara-editorial-single-page,
        .lunara-oscars-portal {
            max-width: 100% !important;
            min-width: 0 !important;
            overflow-x: clip !important;
            padding-left: 18px !important;
            padding-right: 18px !important;
            width: 100% !important;
        }

        body.home .lunara-front-page > .lunara-home-section,
        body.home main.lunara-front-page > .lunara-home-section,
        .lunara-front-page > .lunara-home-section,
        .lunara-archive-page > .lunara-home-section,
        .lunara-editorial-single-page > .lunara-home-section,
        .lunara-oscars-portal > .lunara-home-section,
        .lunara-review-archive-hero-shell,
        .lunara-review-archive-spotlight,
        .lunara-review-feature-card,
        .lunara-review-grid-card,
        .lunara-journal-archive-card,
        .lunara-oscars-portal-feature-card,
        .lunara-oscars-portal-link-card,
        .lunara-oscars-portal-spotlight-card,
        .lunara-oscars-portal-title-card,
        .lunara-oscars-portal-fact-card,
        .aat-entity-page,
        .aat-hub-page,
        .aat-hub-section,
        .aat-card,
        .aat-table-wrapper {
            max-width: 100% !important;
            min-width: 0 !important;
            width: 100% !important;
        }

        .aat-container,
        .aat-entity-page,
        .aat-hub-page {
            margin-left: auto !important;
            margin-right: auto !important;
            max-width: calc(100vw - 20px) !important;
            width: calc(100vw - 20px) !important;
        }

        body.home .lunara-latest-reviews-section .lunara-review-grid,
        body.post-type-archive-review .lunara-review-archive-grid,
        body.post-type-archive-journal .lunara-journal-archive-grid,
        body.home .lunara-journal-home-grid,
        .lunara-review-grid,
        .lunara-ledger-grid,
        .lunara-dispatch-archive-grid,
        .lunara-review-archive-hero-shell,
        .lunara-review-archive-spotlight,
        .lunara-review-feature-card.is-lead .lunara-review-feature-link,
        .lunara-review-feature-card.is-compact .lunara-review-feature-link,
        .lunara-oscars-portal-feature-card,
        .lunara-oscars-portal-link-grid,
        .lunara-oscars-portal-spotlight-grid,
        .lunara-oscars-portal-title-grid,
        .lunara-oscars-portal-facts-grid,
        .lunara-oscars-research-card-grid,
        .aat-entity-hero,
        .aat-entity-main,
        .aat-hub-hero,
        .aat-hub-grid,
        .aat-filmography-grid,
        .aat-records-grid,
        .aat-timeline,
        .aat-winner-circle-grid,
        .aat-metrics-grid,
        .aat-highlight-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            grid-auto-columns: minmax(0, 1fr) !important;
        }

        .lunara-home-section-head,
        .lunara-review-grid-link,
        .lunara-review-grid-copy,
        .lunara-review-feature-copy,
        .lunara-oscars-portal-feature-copy,
        .aat-entity-copy,
        .aat-hub-copy {
            max-width: 100% !important;
            min-width: 0 !important;
            overflow-wrap: anywhere !important;
        }

        .lunara-home-section-title,
        .lunara-archive-hero-title,
        .lunara-review-grid-title,
        .lunara-review-feature-title,
        .lunara-oscars-portal h1,
        .lunara-oscars-portal h2,
        .lunara-oscars-portal h3,
        .aat-container h1,
        .aat-container h2,
        .aat-container h3,
        .aat-entity-title,
        .aat-hub-title {
            max-width: 100% !important;
            min-width: 0 !important;
            overflow-wrap: anywhere !important;
            text-wrap: balance;
        }

        .lunara-home-section-title,
        .lunara-archive-hero-title,
        .lunara-oscars-portal h1,
        .aat-container h1,
        .aat-entity-title,
        .aat-hub-title {
            font-size: clamp(2rem, 10.8vw, 3rem) !important;
            line-height: 1.06 !important;
        }

        .lunara-oscars-portal h2,
        .aat-container h2 {
            font-size: clamp(1.45rem, 7.4vw, 2.1rem) !important;
            line-height: 1.12 !important;
        }

        .lunara-section-link,
        .lunara-btn,
        .aat-button,
        .aat-ledger-pill,
        .aat-entity-actions,
        .aat-entity-chips,
        .aat-ledger-actions,
        .aat-category-chips {
            max-width: 100% !important;
            min-width: 0 !important;
            overflow-wrap: anywhere !important;
            white-space: normal !important;
        }

        .aat-entity-actions,
        .aat-entity-chips,
        .aat-ledger-actions,
        .aat-category-chips,
        .lunara-review-grid-footer {
            display: flex !important;
            flex-wrap: wrap !important;
        }

        .lunara-review-grid-poster-wrap,
        .lunara-oscars-portal-feature-poster,
        .lunara-oscars-portal-title-media,
        .aat-entity-poster-wrap,
        .aat-filmography-poster {
            max-width: 100% !important;
            min-width: 0 !important;
        }

        .lunara-review-grid-poster-wrap img,
        .lunara-review-grid-poster,
        .lunara-oscars-portal-feature-poster img,
        .lunara-oscars-portal-title-media img,
        .aat-container img {
            height: auto !important;
            max-width: 100% !important;
        }

        .aat-stats-bar.aat-entity-stats {
            display: grid !important;
            gap: 10px !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            justify-content: stretch !important;
            margin: 20px 0 30px !important;
        }

        .aat-stats-bar.aat-entity-stats .aat-stat {
            align-content: start !important;
            display: grid !important;
            gap: 4px !important;
            min-width: 0 !important;
            padding: 13px 14px !important;
            text-align: left !important;
        }

        .aat-stats-bar.aat-entity-stats .aat-stat-number,
        .aat-stats-bar.aat-entity-stats .aat-stat-label {
            display: block !important;
            min-width: 0 !important;
            overflow-wrap: anywhere !important;
        }

        .aat-stats-bar.aat-entity-stats .aat-stat-number {
            font-size: clamp(1.16rem, 6.2vw, 1.55rem) !important;
            line-height: 1.05 !important;
        }

        .aat-stats-bar.aat-entity-stats .aat-stat-label {
            font-size: .66rem !important;
            letter-spacing: .1em !important;
            line-height: 1.2 !important;
        }

        .aat-decade-nav,
        .aat-hub-chips,
        .aat-winner-circle-actions,
        .aat-category-history-actions,
        .aat-nominee-trail-actions,
        .aat-entity-status-tags {
            align-items: stretch !important;
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
            justify-content: flex-start !important;
            max-width: 100% !important;
            overflow: visible !important;
        }

        .aat-decade-pill,
        .aat-hub-chip,
        .aat-hub-card-action,
        .aat-winner-circle-action,
        .aat-entity-status-tag,
        .aat-nominee-trail-summary {
            align-items: center !important;
            display: inline-flex !important;
            justify-content: center !important;
            line-height: 1.16 !important;
            min-height: 40px !important;
            min-width: 0 !important;
            overflow-wrap: anywhere !important;
            text-align: center !important;
            white-space: normal !important;
        }

        .aat-decade-pill,
        .aat-hub-chip,
        .aat-hub-card-action,
        .aat-winner-circle-action,
        .aat-entity-status-tag {
            flex: 0 1 auto !important;
            max-width: 100% !important;
        }

        .aat-decade-pill {
            gap: 8px !important;
            justify-content: space-between !important;
            min-width: 92px !important;
            padding: 8px 11px !important;
        }

        .aat-decade-pill-label,
        .aat-decade-pill-count {
            display: inline-flex !important;
            min-width: 0 !important;
            white-space: nowrap !important;
        }

        .aat-decade-pill-count {
            align-items: center !important;
            background: rgba(201,169,97,.2) !important;
            border-radius: 999px !important;
            justify-content: center !important;
            margin-left: 2px !important;
            min-width: 24px !important;
            padding: 3px 7px !important;
        }

        .aat-hub-metric-card,
        .aat-category-latest-winner,
        .aat-ceremony-marquee,
        .aat-ceremony-marquee-copy,
        .aat-ceremony-marquee-stack,
        .aat-hub-spotlight-card,
        .aat-hub-spotlight-body,
        .aat-category-ceremony-row,
        .aat-category-history-winner,
        .aat-nominee-trail-item,
        .aat-history-item,
        .aat-entity-status-banner,
        .aat-crossroads-card,
        .aat-crossroads-list {
            min-width: 0 !important;
            overflow-wrap: anywhere !important;
        }

        .aat-category-latest-winner,
        .aat-ceremony-marquee {
            padding-left: clamp(18px, 6vw, 28px) !important;
            padding-right: clamp(18px, 6vw, 28px) !important;
        }

        .aat-category-latest-winner h2,
        .aat-ceremony-marquee h2,
        .aat-winner-circle-title,
        .aat-hub-spotlight-title,
        .aat-hub-metric-value,
        .aat-hub-card-title,
        .aat-entity-status-title {
            max-width: 100% !important;
            min-width: 0 !important;
            overflow-wrap: anywhere !important;
            white-space: normal !important;
            word-break: normal !important;
        }

        .aat-hub-inline-link,
        .aat-hub-inline-link-title,
        .aat-entity-link,
        .aat-winner-circle-title a,
        .aat-hub-spotlight-title a,
        .aat-hub-metric-value a {
            max-width: 100% !important;
            overflow-wrap: anywhere !important;
            white-space: normal !important;
            word-break: normal !important;
        }

        .aat-hub-spotlight-card {
            align-items: center !important;
            gap: 14px !important;
            grid-template-columns: minmax(82px, 112px) minmax(0, 1fr) !important;
        }

        .aat-hub-spotlight-media,
        .aat-hub-spotlight-media-link {
            max-width: 38vw !important;
            min-width: 0 !important;
        }

        .aat-crossroads-grid,
        .aat-crossroads-list {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
        }

        .aat-crossroads-grid {
            gap: 14px !important;
        }

        .aat-crossroads-list {
            gap: 8px !important;
        }

        .aat-crossroad-pill {
            align-items: start !important;
            border: 1px solid rgba(201,169,97,.18) !important;
            border-radius: 12px !important;
            display: grid !important;
            gap: 3px !important;
            grid-template-columns: minmax(0, 1fr) !important;
            line-height: 1.22 !important;
            min-height: 44px !important;
            min-width: 0 !important;
            padding: 10px 12px !important;
            text-decoration: none !important;
            white-space: normal !important;
        }

        .aat-crossroad-pill-title,
        .aat-crossroad-pill-meta {
            display: block !important;
            max-width: 100% !important;
            min-width: 0 !important;
            overflow-wrap: anywhere !important;
        }

        .aat-crossroad-pill-meta {
            opacity: .82 !important;
        }

        .aat-hub-metric-card.aat-card-has-backdrop {
            background-position: center !important;
            min-height: 0 !important;
        }

        .aat-hub-metric-card.aat-card-has-backdrop::before {
            opacity: .32 !important;
        }

        .aat-hub-metric-label,
        .aat-hub-metric-value,
        .aat-hub-metric-copy,
        .aat-category-history-title,
        .aat-category-history-meta,
        .aat-category-history-detail,
        .aat-nominee-primary,
        .aat-nominee-secondary,
        .aat-nominee-detail,
        .aat-history-line,
        .aat-section-description {
            max-width: 100% !important;
            min-width: 0 !important;
            overflow-wrap: anywhere !important;
            word-break: normal !important;
        }

        .aat-category-history-title {
            font-size: clamp(1.08rem, 5.8vw, 1.3rem) !important;
            line-height: 1.18 !important;
        }

        .aat-history-line .aat-entity-link,
        .aat-category-history-meta .aat-hub-inline-link,
        .aat-nominee-primary a,
        .aat-hub-inline-link-title {
            text-decoration-thickness: 1px !important;
            text-underline-offset: 3px !important;
        }

        .aat-nominee-trail {
            margin-top: 16px !important;
        }

        .aat-nominee-trail-summary {
            border: 1px solid rgba(201,169,97,.18) !important;
            border-radius: 999px !important;
            padding: 8px 12px !important;
            width: fit-content !important;
        }
    }

    @media (max-width: 520px) {
        .aat-hub-spotlight-card {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        .aat-hub-spotlight-media,
        .aat-hub-spotlight-media-link {
            max-width: 148px !important;
            width: 100% !important;
        }

        .aat-hub-spotlight-body {
            width: 100% !important;
        }
    }

    @media (max-width: 760px) {
        .lunara-review-single-body-grid {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        .lunara-review-single-rail,
        .lunara-review-single-rail-sticky,
        .lunara-review-single-rail-actions,
        .lunara-review-single-where-links,
        .lunara-review-single-debrief-wrap,
        .lunara-review-single-debrief-media,
        .lunara-review-single-debrief-poster-shell {
            max-width: 100% !important;
            min-width: 0 !important;
            width: 100% !important;
        }

        .lunara-review-single-rail-actions .lunara-btn,
        .lunara-review-single-where-links .lunara-btn,
        .lunara-btn {
            max-width: 100% !important;
            min-width: 0 !important;
            width: 100% !important;
        }

        .aat-container {
            padding-left: 12px !important;
            padding-right: 12px !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_os_responsive_guardrail_css', 999 );

/**
 * Scoped Splide pilot for the homepage Oscar Facts signature lane.
 */
function lunara_enqueue_home_splide_pilot_assets() {
	if ( ! is_front_page() ) {
		return;
	}

	$splide_css = lunara_resolve_theme_asset(
		'assets/vendor/splide/splide-core.min.css',
		array( 'assets/vendor/splide/splide-core.min.css' )
	);
	if ( $splide_css['uri'] ) {
		wp_enqueue_style(
			'lunara-splide-core',
			$splide_css['uri'],
			array(),
			lunara_theme_asset_version( $splide_css['path'] )
		);
	}

	$splide_js = lunara_resolve_theme_asset(
		'assets/vendor/splide/splide.min.js',
		array( 'assets/vendor/splide/splide.min.js' )
	);
	if ( $splide_js['uri'] ) {
		wp_enqueue_script(
			'lunara-splide',
			$splide_js['uri'],
			array(),
			lunara_theme_asset_version( $splide_js['path'] ),
			true
		);
	}

	$pilot_js = lunara_resolve_theme_asset(
		'assets/js/lunara-splide-pilot.js',
		array( 'lunara-splide-pilot.js' )
	);
	if ( $pilot_js['uri'] ) {
		wp_enqueue_script(
			'lunara-home-splide-pilot',
			$pilot_js['uri'],
			array( 'lunara-splide' ),
			lunara_theme_asset_version( $pilot_js['path'] ),
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'lunara_enqueue_home_splide_pilot_assets', 20 );

/**
 * Reviews archive authority package polish.
 */
function lunara_enqueue_review_archive_dynamic_rails() {
    if ( is_admin() || is_feed() ) {
        return;
    }

    $is_reviews_archive = is_post_type_archive( 'review' )
        || is_page_template( 'page-reviews.php' )
        || is_page( 'reviews' );

    if ( ! $is_reviews_archive ) {
        return;
    }

    $rail_js = lunara_resolve_theme_asset(
        'assets/js/lunara-dynamic-rails.js',
        array( 'lunara-dynamic-rails.js' )
    );

    if ( $rail_js['uri'] ) {
        wp_enqueue_script(
            'lunara-dynamic-rails',
            $rail_js['uri'],
            array(),
            lunara_theme_asset_version( $rail_js['path'] ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'lunara_enqueue_review_archive_dynamic_rails' );

function lunara_output_review_archive_authority_css() {
    if ( is_admin() || is_feed() ) {
        return;
    }

    $is_reviews_archive = is_post_type_archive( 'review' )
        || is_page_template( 'page-reviews.php' )
        || is_page( 'reviews' );

    if ( ! $is_reviews_archive ) {
        return;
    }
    ?>
    <style id="lunara-review-archive-authority-css">
    body.post-type-archive-review .lunara-review-archive-page,
    body.page-template-page-reviews .lunara-review-archive-page {
        gap: clamp(30px, 4vw, 52px) !important;
    }

    body.post-type-archive-review .lunara-review-archive-page > .lunara-home-section,
    body.page-template-page-reviews .lunara-review-archive-page > .lunara-home-section {
        margin-bottom: clamp(22px, 2.8vw, 36px) !important;
    }

    body.post-type-archive-review .lunara-review-archive-hero,
    body.page-template-page-reviews .lunara-review-archive-hero {
        padding-bottom: clamp(18px, 2.8vw, 34px) !important;
        padding-top: clamp(34px, 4.8vw, 58px) !important;
    }

    body.post-type-archive-review .lunara-review-archive-hero-shell,
    body.page-template-page-reviews .lunara-review-archive-hero-shell {
        padding: clamp(24px, 3vw, 34px) clamp(24px, 3.2vw, 38px) !important;
    }

    body.post-type-archive-review .lunara-review-archive-shell,
    body.page-template-page-reviews .lunara-review-archive-shell {
        display: grid !important;
        gap: clamp(28px, 4vw, 46px) !important;
    }

    body.post-type-archive-review .lunara-review-archive-spotlight,
    body.page-template-page-reviews .lunara-review-archive-spotlight {
        display: grid !important;
        gap: clamp(20px, 2.6vw, 32px) !important;
        grid-template-columns: minmax(0, 1fr) !important;
        margin-top: 0 !important;
    }

    body.post-type-archive-review .lunara-review-archive-run,
    body.page-template-page-reviews .lunara-review-archive-run {
        display: grid !important;
        gap: clamp(20px, 2.4vw, 30px) !important;
    }

    body.post-type-archive-review .lunara-review-archive-run-head,
    body.page-template-page-reviews .lunara-review-archive-run-head {
        margin: 0 !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-lead,
    body.page-template-page-reviews .lunara-review-feature-card.is-lead {
        min-height: 0 !important;
        width: 100% !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-link,
    body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-link {
        min-height: 100% !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-compact,
    body.page-template-page-reviews .lunara-review-feature-card.is-compact {
        min-height: 0 !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-link,
    body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-link {
        display: grid !important;
        grid-template-columns: 116px minmax(0, 1fr) !important;
        min-height: 154px !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-media,
    body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-media {
        aspect-ratio: auto !important;
        grid-column: 1 !important;
        grid-row: 1 !important;
        height: 154px !important;
        max-width: 116px !important;
        min-height: 154px !important;
        min-width: 0 !important;
        width: 116px !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-image,
    body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-image {
        display: block !important;
        height: 100% !important;
        object-fit: cover !important;
        width: 100% !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-copy,
    body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-copy {
        align-content: center !important;
        grid-column: 2 !important;
        grid-row: 1 !important;
        min-width: 0 !important;
    }

    body.post-type-archive-review .lunara-review-archive-run-grid,
    body.page-template-page-reviews .lunara-review-archive-run-grid {
        margin-top: 0 !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-link,
    body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-link {
        display: grid !important;
        grid-template-columns: minmax(270px, 0.4fr) minmax(0, 1fr) !important;
        grid-template-rows: minmax(0, 1fr) !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-media,
    body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-media {
        grid-column: 1 !important;
        grid-row: 1 !important;
        height: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        overflow: hidden !important;
        width: 100% !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-image,
    body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-image {
        display: block !important;
        height: 100% !important;
        max-width: 100% !important;
        object-fit: cover !important;
        width: 100% !important;
    }

    body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-copy,
    body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-copy {
        align-content: center !important;
        grid-column: 2 !important;
        grid-row: 1 !important;
        min-width: 0 !important;
        padding: clamp(24px, 3.6vw, 46px) !important;
    }

    body.post-type-archive-review .lunara-review-archive-rail,
    body.page-template-page-reviews .lunara-review-archive-rail {
        display: grid !important;
        gap: clamp(14px, 1.7vw, 22px) !important;
        grid-template-columns: minmax(230px, 0.56fr) repeat(2, minmax(0, 1fr)) !important;
    }

    body.post-type-archive-review .lunara-review-archive-rail-shell,
    body.page-template-page-reviews .lunara-review-archive-rail-shell {
        align-content: center !important;
        display: grid !important;
    }

    body.post-type-archive-review .lunara-review-archive-rail-shell .lunara-section-title,
    body.page-template-page-reviews .lunara-review-archive-rail-shell .lunara-section-title {
        font-size: clamp(1.18rem, 2vw, 1.66rem) !important;
        line-height: 1.12 !important;
        max-width: 17ch !important;
    }

    @media (max-width: 820px) {
        body.post-type-archive-review .lunara-review-archive-hero-shell,
        body.page-template-page-reviews .lunara-review-archive-hero-shell {
            gap: 16px !important;
        }

        body.post-type-archive-review .lunara-review-archive-debrief-list,
        body.page-template-page-reviews .lunara-review-archive-debrief-list {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
        }

        body.post-type-archive-review .lunara-review-archive-debrief-list li,
        body.page-template-page-reviews .lunara-review-archive-debrief-list li {
            border: 1px solid rgba(201, 169, 97, 0.13) !important;
            border-radius: 12px !important;
            padding: 10px 11px !important;
        }

        body.post-type-archive-review .lunara-review-archive-debrief-list span,
        body.page-template-page-reviews .lunara-review-archive-debrief-list span {
            text-align: left !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead {
            min-height: 0 !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-link,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-link {
            grid-template-columns: minmax(124px, 0.38fr) minmax(0, 1fr) !important;
            min-height: 0 !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-media,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-media {
            aspect-ratio: 3 / 4 !important;
            height: auto !important;
            min-height: 0 !important;
            width: 100% !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-copy,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-copy {
            align-content: center !important;
            gap: 10px !important;
            padding: 18px !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-excerpt,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-excerpt,
        body.post-type-archive-review .lunara-review-grid-excerpt,
        body.page-template-page-reviews .lunara-review-grid-excerpt {
            display: -webkit-box !important;
            -webkit-box-orient: vertical !important;
            overflow: hidden !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-excerpt,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-excerpt {
            -webkit-line-clamp: 3 !important;
        }

        body.post-type-archive-review .lunara-review-grid-excerpt,
        body.page-template-page-reviews .lunara-review-grid-excerpt {
            -webkit-line-clamp: 2 !important;
        }

        body.post-type-archive-review .lunara-review-archive-uniform.lunara-review-grid,
        body.page-template-page-reviews .lunara-review-archive-uniform.lunara-review-grid {
            display: grid !important;
            gap: 12px !important;
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.post-type-archive-review .lunara-review-archive-uniform .lunara-review-grid-link,
        body.page-template-page-reviews .lunara-review-archive-uniform .lunara-review-grid-link {
            display: grid !important;
            grid-template-columns: 104px minmax(0, 1fr) !important;
            min-height: 148px !important;
        }

        body.post-type-archive-review .lunara-review-archive-uniform .lunara-review-grid-poster-wrap,
        body.page-template-page-reviews .lunara-review-archive-uniform .lunara-review-grid-poster-wrap {
            aspect-ratio: auto !important;
            border-radius: 14px 0 0 14px !important;
            height: 148px !important;
            min-height: 148px !important;
            width: 104px !important;
        }

        body.post-type-archive-review .lunara-review-archive-uniform .lunara-review-grid-copy,
        body.page-template-page-reviews .lunara-review-archive-uniform .lunara-review-grid-copy {
            align-content: center !important;
            gap: 7px !important;
            min-width: 0 !important;
            padding: 12px 13px !important;
        }

        body.post-type-archive-review .lunara-review-archive-uniform .lunara-review-grid-title,
        body.page-template-page-reviews .lunara-review-archive-uniform .lunara-review-grid-title {
            font-size: clamp(0.96rem, 4.4vw, 1.12rem) !important;
            line-height: 1.16 !important;
        }

        body.post-type-archive-review .lunara-review-archive-rail,
        body.page-template-page-reviews .lunara-review-archive-rail {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.post-type-archive-review .lunara-review-archive-rail-shell .lunara-section-title,
        body.page-template-page-reviews .lunara-review-archive-rail-shell .lunara-section-title {
            font-size: clamp(1.28rem, 6.4vw, 1.72rem) !important;
            line-height: 1.08 !important;
            max-width: 12ch !important;
        }
    }

    @media (max-width: 540px) {
        body.post-type-archive-review .lunara-review-archive-page,
        body.page-template-page-reviews .lunara-review-archive-page {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        body.post-type-archive-review .lunara-review-archive-page > .lunara-home-section,
        body.page-template-page-reviews .lunara-review-archive-page > .lunara-home-section,
        body.post-type-archive-review .lunara-review-archive-hero,
        body.page-template-page-reviews .lunara-review-archive-hero,
        body.post-type-archive-review .lunara-review-archive-shell,
        body.page-template-page-reviews .lunara-review-archive-shell {
            margin-left: auto !important;
            margin-right: auto !important;
            max-width: calc(100vw - 24px) !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            width: calc(100vw - 24px) !important;
        }

        body.post-type-archive-review .lunara-review-archive-hero-shell,
        body.page-template-page-reviews .lunara-review-archive-hero-shell {
            max-width: 100% !important;
            padding: 18px !important;
            width: 100% !important;
        }

        body.post-type-archive-review .lunara-review-archive-spotlight,
        body.page-template-page-reviews .lunara-review-archive-spotlight,
        body.post-type-archive-review .lunara-review-archive-rail,
        body.page-template-page-reviews .lunara-review-archive-rail,
        body.post-type-archive-review .lunara-review-archive-uniform.lunara-review-grid,
        body.page-template-page-reviews .lunara-review-archive-uniform.lunara-review-grid,
        body.post-type-archive-review .lunara-review-archive-grid,
        body.page-template-page-reviews .lunara-review-archive-grid {
            max-width: 100% !important;
            width: 100% !important;
        }

        body.post-type-archive-review .lunara-review-archive-debrief-list,
        body.page-template-page-reviews .lunara-review-archive-debrief-list {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-link,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-link {
            grid-template-columns: 118px minmax(0, 1fr) !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-title,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-title {
            font-size: clamp(1.16rem, 6vw, 1.52rem) !important;
            line-height: 1.08 !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-excerpt,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-excerpt {
            -webkit-line-clamp: 2 !important;
            font-size: 0.9rem !important;
            line-height: 1.44 !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-lead .lunara-review-feature-footer,
        body.page-template-page-reviews .lunara-review-feature-card.is-lead .lunara-review-feature-footer,
        body.post-type-archive-review .lunara-review-archive-uniform .lunara-review-grid-footer,
        body.page-template-page-reviews .lunara-review-archive-uniform .lunara-review-grid-footer {
            display: none !important;
        }

        body.post-type-archive-review .lunara-review-archive-rail,
        body.page-template-page-reviews .lunara-review-archive-rail {
            gap: 10px !important;
        }

        body.post-type-archive-review .lunara-review-archive-rail-shell,
        body.page-template-page-reviews .lunara-review-archive-rail-shell,
        body.post-type-archive-review .lunara-review-archive-run-head,
        body.page-template-page-reviews .lunara-review-archive-run-head {
            padding: 16px !important;
            border-radius: 16px !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-link,
        body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-link {
            grid-template-columns: 88px minmax(0, 1fr) !important;
            min-height: 132px !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-media,
        body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-media {
            height: 132px !important;
            max-width: 88px !important;
            min-height: 132px !important;
            width: 88px !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-copy,
        body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-copy {
            align-content: center !important;
            gap: 6px !important;
            overflow: hidden !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-title,
        body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-title {
            display: -webkit-box !important;
            -webkit-box-orient: vertical !important;
            -webkit-line-clamp: 3 !important;
            font-size: clamp(0.86rem, 4.5vw, 1rem) !important;
            line-height: 1.1 !important;
            max-width: 100% !important;
            overflow: hidden !important;
            overflow-wrap: anywhere !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-excerpt,
        body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-excerpt {
            display: none !important;
        }

        body.post-type-archive-review .lunara-review-feature-card.is-compact .lunara-review-feature-footer,
        body.page-template-page-reviews .lunara-review-feature-card.is-compact .lunara-review-feature-footer {
            display: none !important;
        }
    }

    .lunara-review-archive-page .lunara-review-archive-hero-shell {
        align-items: stretch !important;
        border: 1px solid rgba(224, 196, 129, 0.18) !important;
        border-radius: 22px !important;
        box-shadow: 0 30px 72px rgba(0, 0, 0, 0.24), 0 0 0 1px rgba(255, 255, 255, 0.035) inset !important;
        display: grid !important;
        gap: clamp(18px, 2.4vw, 32px) !important;
        grid-template-columns: minmax(0, 1.1fr) minmax(280px, 0.64fr) !important;
    }

    .lunara-review-archive-page > .lunara-review-archive-slot-hero {
        order: 1 !important;
    }

    .lunara-review-archive-page > .lunara-review-archive-slot-utility {
        order: 2 !important;
    }

    .lunara-review-archive-page > .lunara-review-archive-slot-grid {
        order: 3 !important;
    }

    .lunara-review-archive-page > .lunara-review-archive-slot-pagination {
        order: 4 !important;
        width: 100% !important;
    }

    .lunara-review-archive-page .lunara-review-archive-hero-copy-wrap {
        align-content: center !important;
        display: grid !important;
        gap: clamp(14px, 1.8vw, 22px) !important;
        min-width: 0 !important;
    }

    .lunara-review-archive-page .lunara-archive-hero-title {
        max-width: 12ch !important;
    }

    .lunara-review-archive-page .lunara-archive-hero-copy {
        color: rgba(238, 242, 245, 0.82) !important;
        font-size: 1.12rem !important;
        line-height: 1.62 !important;
        margin: 0 !important;
        max-width: 58ch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-debrief {
        align-content: space-between !important;
        background: linear-gradient(145deg, rgba(8, 20, 33, 0.94), rgba(18, 31, 46, 0.9)) !important;
        border: 1px solid rgba(224, 196, 129, 0.19) !important;
        border-radius: 18px !important;
        box-shadow: 0 18px 42px rgba(0, 0, 0, 0.18) !important;
        display: grid !important;
        gap: 18px !important;
        margin: 0 !important;
        padding: clamp(18px, 2.2vw, 26px) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-debrief-kicker {
        color: var(--lunara-gold, #d8b665) !important;
        font-size: 0.76rem !important;
        letter-spacing: 0 !important;
        margin: 0 !important;
        text-transform: uppercase !important;
    }

    .lunara-review-archive-page .lunara-review-archive-debrief-list {
        display: grid !important;
        gap: 10px !important;
        list-style: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .lunara-review-archive-page .lunara-review-archive-debrief-list li {
        align-items: center !important;
        background: rgba(255, 255, 255, 0.035) !important;
        border: 1px solid rgba(224, 196, 129, 0.12) !important;
        border-radius: 12px !important;
        display: flex !important;
        gap: 12px !important;
        justify-content: space-between !important;
        padding: 10px 12px !important;
    }

    .lunara-review-archive-page .lunara-review-archive-debrief-list strong {
        color: rgba(224, 196, 129, 0.9) !important;
        font-size: 0.72rem !important;
        letter-spacing: 0 !important;
        text-transform: uppercase !important;
    }

    .lunara-review-archive-page .lunara-review-archive-debrief-list span {
        color: rgba(246, 248, 250, 0.9) !important;
        font-weight: 700 !important;
        text-align: right !important;
    }

    .lunara-review-archive-page .lunara-review-archive-hero-actions {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
    }

    .lunara-review-archive-page .lunara-review-archive-hero-actions a,
    .lunara-review-archive-page .lunara-review-archive-sort-link {
        align-items: center !important;
        border: 1px solid rgba(224, 196, 129, 0.28) !important;
        border-radius: 999px !important;
        color: rgba(239, 222, 173, 0.94) !important;
        display: inline-flex !important;
        font-size: 0.76rem !important;
        font-weight: 800 !important;
        justify-content: center !important;
        letter-spacing: 0 !important;
        min-height: 38px !important;
        padding: 9px 13px !important;
        text-decoration: none !important;
        text-transform: uppercase !important;
        transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease !important;
    }

    .lunara-review-archive-page .lunara-review-archive-hero-actions a:hover,
    .lunara-review-archive-page .lunara-review-archive-sort-link:hover,
    .lunara-review-archive-page .lunara-review-archive-sort-link.is-active {
        background: rgba(224, 196, 129, 0.14) !important;
        border-color: rgba(224, 196, 129, 0.52) !important;
        color: #fff4cf !important;
    }

    .lunara-review-archive-page .lunara-review-archive-utility {
        margin-bottom: clamp(18px, 2.6vw, 30px) !important;
        margin-top: clamp(-12px, -1vw, -4px) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-toolbar {
        align-items: center !important;
        background: rgba(7, 18, 30, 0.78) !important;
        border: 1px solid rgba(224, 196, 129, 0.16) !important;
        border-radius: 18px !important;
        display: grid !important;
        gap: clamp(14px, 2vw, 24px) !important;
        grid-template-columns: minmax(220px, 0.72fr) minmax(0, 1fr) !important;
        padding: clamp(16px, 2vw, 22px) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-toolbar-head {
        margin: 0 !important;
    }

    .lunara-review-archive-page .lunara-review-archive-toolbar-head .lunara-section-title {
        font-size: 1.24rem !important;
        line-height: 1.15 !important;
        max-width: 26ch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-sort {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
        justify-content: flex-end !important;
        align-items: center !important;
        background: linear-gradient(135deg, rgba(8, 20, 33, 0.86), rgba(18, 31, 46, 0.72)) !important;
        border: 1px solid rgba(224, 196, 129, 0.14) !important;
        border-radius: 16px !important;
        padding: 10px !important;
    }

    .lunara-review-archive-page .lunara-review-archive-sort-label {
        color: rgba(224, 196, 129, 0.78) !important;
        flex: 0 0 auto !important;
        font-size: 0.72rem !important;
        font-weight: 800 !important;
        letter-spacing: 0 !important;
        line-height: 1 !important;
        margin-right: 2px !important;
        text-transform: uppercase !important;
    }

    .lunara-review-archive-page .lunara-review-archive-support-head {
        align-content: center !important;
        background:
            radial-gradient(circle at 20% 0%, rgba(224, 196, 129, 0.12), transparent 38%),
            linear-gradient(145deg, rgba(8, 20, 33, 0.9), rgba(13, 29, 44, 0.76)) !important;
        border: 1px solid rgba(224, 196, 129, 0.15) !important;
        border-radius: 18px !important;
        display: grid !important;
        gap: 6px !important;
        margin-top: clamp(4px, 1vw, 10px) !important;
        min-height: 100% !important;
        padding: clamp(18px, 2.2vw, 24px) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-support-head .lunara-section-title {
        font-size: clamp(1.36rem, 2.05vw, 1.8rem) !important;
        line-height: 1.12 !important;
        max-width: 13ch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-support-suite {
        display: grid !important;
        gap: clamp(14px, 1.8vw, 22px) !important;
        grid-template-columns: minmax(210px, 0.42fr) minmax(0, 1fr) !important;
        align-items: stretch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: clamp(14px, 1.7vw, 22px) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail .lunara-review-grid-card {
        min-height: 0 !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail .lunara-review-grid-link {
        min-height: 100% !important;
    }

    .lunara-review-archive-page .lunara-review-feature-card,
    .lunara-review-archive-page .lunara-review-grid-card {
        border-color: rgba(224, 196, 129, 0.16) !important;
    }

    .lunara-review-archive-page .lunara-review-feature-card.is-text-led .lunara-review-feature-link,
    .lunara-review-archive-page .lunara-review-grid-card.is-text-led .lunara-review-grid-link {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) !important;
        min-height: 0 !important;
    }

    .lunara-review-archive-page .lunara-review-feature-card.is-text-led .lunara-review-feature-copy,
    .lunara-review-archive-page .lunara-review-grid-card.is-text-led .lunara-review-grid-copy {
        background: linear-gradient(145deg, rgba(9, 23, 37, 0.92), rgba(13, 29, 44, 0.82)) !important;
    }

    .lunara-review-archive-page .lunara-score-badge-inline {
        align-self: start !important;
        display: inline-flex !important;
        margin: 0 0 2px 0 !important;
        max-width: max-content !important;
        position: static !important;
    }

    .lunara-review-archive-page .lunara-review-grid-card.has-no-review-quote .lunara-review-grid-copy {
        gap: 10px !important;
    }

    .lunara-review-archive-page .lunara-review-grid-card.has-no-review-quote .lunara-review-grid-title {
        margin-bottom: 4px !important;
    }

    .lunara-review-archive-page .lunara-review-grid-card.is-text-led .lunara-review-grid-footer {
        padding-left: clamp(16px, 2vw, 22px) !important;
        padding-right: clamp(16px, 2vw, 22px) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-run-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        align-items: stretch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-card {
        align-content: space-between !important;
        background:
            radial-gradient(circle at 14% 0%, rgba(224, 196, 129, 0.16), transparent 38%),
            linear-gradient(145deg, rgba(9, 23, 37, 0.94), rgba(14, 31, 48, 0.86)) !important;
        border: 1px solid rgba(224, 196, 129, 0.2) !important;
        border-radius: 18px !important;
        box-shadow: 0 20px 42px rgba(0, 0, 0, 0.18) !important;
        display: grid !important;
        gap: 22px !important;
        min-height: 100% !important;
        padding: clamp(20px, 2.7vw, 32px) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-card.spans-2 {
        grid-column: span 2 !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-copy {
        display: grid !important;
        gap: 10px !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-copy h3 {
        color: #f2d589 !important;
        font-size: clamp(1.28rem, 2.4vw, 2rem) !important;
        line-height: 1.05 !important;
        margin: 0 !important;
        max-width: 15ch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-copy p:last-child {
        color: rgba(239, 242, 245, 0.78) !important;
        font-size: 0.98rem !important;
        line-height: 1.58 !important;
        margin: 0 !important;
        max-width: 48ch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-actions {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-actions a {
        align-items: center !important;
        border: 1px solid rgba(224, 196, 129, 0.26) !important;
        border-radius: 999px !important;
        color: rgba(239, 222, 173, 0.94) !important;
        display: inline-flex !important;
        font-size: 0.74rem !important;
        font-weight: 800 !important;
        justify-content: center !important;
        letter-spacing: 0 !important;
        min-height: 40px !important;
        padding: 9px 13px !important;
        text-decoration: none !important;
        text-transform: uppercase !important;
    }

    .lunara-review-archive-page .lunara-review-archive-retention-actions a:hover,
    .lunara-review-archive-page .lunara-review-archive-retention-actions a:focus-visible {
        background: rgba(224, 196, 129, 0.14) !important;
        border-color: rgba(224, 196, 129, 0.52) !important;
        color: #fff4cf !important;
    }

    .lunara-review-archive-page .lunara-review-archive-support-suite {
        align-items: stretch !important;
        display: grid !important;
        gap: clamp(16px, 2vw, 24px) !important;
        grid-template-columns: minmax(210px, 0.32fr) minmax(0, 1fr) !important;
        min-width: 0 !important;
        overflow: hidden !important;
        padding: clamp(16px, 2.1vw, 24px) !important;
        border: 1px solid rgba(224, 196, 129, 0.14) !important;
        border-radius: 20px !important;
        background: linear-gradient(135deg, rgba(7, 18, 30, 0.72), rgba(14, 29, 44, 0.64)) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-support-head {
        align-content: center !important;
        display: grid !important;
        min-width: 0 !important;
    }

    body.post-type-archive-review .lunara-review-archive-dynamic-rail,
    body.page-template-page-reviews .lunara-review-archive-dynamic-rail,
    .lunara-review-archive-page .lunara-review-archive-dynamic-rail {
        align-content: start !important;
        display: grid !important;
        gap: 12px !important;
        grid-template-columns: minmax(0, 1fr) !important;
        grid-template-rows: auto minmax(0, 1fr) auto !important;
        min-width: 0 !important;
        position: relative !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-controls {
        display: flex !important;
        gap: 8px !important;
        justify-content: flex-end !important;
        min-width: 0 !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-control {
        align-items: center !important;
        width: 36px !important;
        height: 36px !important;
        min-width: 36px !important;
        padding: 0 !important;
        border: 1px solid rgba(224, 196, 129, 0.32) !important;
        border-radius: 999px !important;
        background: rgba(6, 16, 27, 0.82) !important;
        color: rgba(239, 222, 173, 0.96) !important;
        cursor: pointer !important;
        display: inline-grid !important;
        font-size: 1.32rem !important;
        justify-content: center !important;
        line-height: 1 !important;
        transition: background-color 180ms ease, border-color 180ms ease, color 180ms ease, transform 180ms ease !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-control:hover,
    .lunara-review-archive-page .lunara-review-archive-rail-control:focus-visible {
        background: rgba(224, 196, 129, 0.16) !important;
        border-color: rgba(224, 196, 129, 0.58) !important;
        color: #fff4cf !important;
        outline: 2px solid rgba(224, 196, 129, 0.28) !important;
        outline-offset: 2px !important;
        transform: translateY(-1px) !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-track {
        display: flex !important;
        gap: clamp(12px, 1.6vw, 18px) !important;
        min-width: 0 !important;
        max-width: 100% !important;
        overflow-x: auto !important;
        overflow-y: hidden !important;
        overscroll-behavior-x: contain !important;
        padding: 1px 1px 8px !important;
        scroll-behavior: smooth !important;
        scroll-snap-type: x mandatory !important;
        scrollbar-width: none !important;
        -webkit-overflow-scrolling: touch !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-track::-webkit-scrollbar {
        display: none !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-track:focus-visible {
        outline: 2px solid rgba(224, 196, 129, 0.38) !important;
        outline-offset: 4px !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-item {
        display: grid !important;
        flex: 0 0 min(360px, calc((100% - 18px) / 2)) !important;
        min-width: 0 !important;
        scroll-snap-align: start !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-item > .lunara-review-grid-card {
        height: 100% !important;
        min-width: 0 !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-item .lunara-review-grid-link {
        height: 100% !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-dots {
        align-items: center !important;
        display: flex !important;
        gap: 8px !important;
        justify-content: center !important;
        min-width: 0 !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-dot {
        width: 9px !important;
        height: 9px !important;
        min-width: 9px !important;
        padding: 0 !important;
        border: 1px solid rgba(224, 196, 129, 0.6) !important;
        border-radius: 999px !important;
        background: rgba(244, 239, 227, 0.13) !important;
        cursor: pointer !important;
        transition: width 180ms ease, background-color 180ms ease, border-color 180ms ease !important;
    }

    .lunara-review-archive-page .lunara-review-archive-rail-dot.is-active {
        width: 30px !important;
        background: #d7b66f !important;
        border-color: #d7b66f !important;
    }

    .lunara-review-archive-page .lunara-review-archive-run {
        margin-top: clamp(4px, 1vw, 12px) !important;
    }

    @media (max-width: 900px) {
        .lunara-review-archive-page .lunara-review-archive-hero-shell,
        .lunara-review-archive-page .lunara-review-archive-toolbar {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        .lunara-review-archive-page .lunara-review-archive-sort {
            justify-content: flex-start !important;
        }

        .lunara-review-archive-page .lunara-review-archive-support-suite,
        .lunara-review-archive-page .lunara-review-archive-rail {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        .lunara-review-archive-page .lunara-review-archive-support-suite {
            overflow: hidden !important;
        }

        .lunara-review-archive-page .lunara-review-archive-rail-item {
            flex-basis: min(78vw, 340px) !important;
        }
    }

    @media (max-width: 820px) {
        .lunara-review-archive-page .lunara-review-feature-card.is-text-led .lunara-review-feature-link,
        .lunara-review-archive-page .lunara-review-grid-card.is-text-led .lunara-review-grid-link {
            grid-template-columns: minmax(0, 1fr) !important;
            min-height: 0 !important;
        }

        .lunara-review-archive-page .lunara-review-grid-card.is-text-led .lunara-review-grid-copy {
            padding: 16px !important;
        }

        .lunara-review-archive-page .lunara-review-archive-support-head {
            padding: 16px 0 12px !important;
        }

        .lunara-review-archive-page .lunara-review-archive-rail-controls {
            justify-content: flex-start !important;
        }

        .lunara-review-archive-page .lunara-review-archive-run-grid {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        .lunara-review-archive-page .lunara-review-archive-retention-card,
        .lunara-review-archive-page .lunara-review-archive-retention-card.spans-2 {
            grid-column: auto !important;
        }
    }

    @media (max-width: 540px) {
        .lunara-review-archive-page .lunara-review-archive-hero-shell,
        .lunara-review-archive-page .lunara-review-archive-toolbar,
        .lunara-review-archive-page .lunara-review-archive-debrief {
            border-radius: 16px !important;
        }

        .lunara-review-archive-page .lunara-archive-hero-title {
            max-width: 9ch !important;
        }

        .lunara-review-archive-page .lunara-review-archive-hero-actions a,
        .lunara-review-archive-page .lunara-review-archive-sort-link {
            flex: 1 1 auto !important;
            min-width: min(100%, 142px) !important;
        }

        .lunara-review-archive-page .lunara-review-archive-sort-label {
            flex-basis: 100% !important;
        }

        .lunara-review-archive-page .lunara-review-archive-debrief-list li {
            align-items: flex-start !important;
            flex-direction: column !important;
            gap: 4px !important;
        }

        .lunara-review-archive-page .lunara-review-archive-debrief-list span {
            text-align: left !important;
        }

        .lunara-review-archive-page .lunara-review-archive-retention-card {
            padding: 18px !important;
        }

        .lunara-review-archive-page .lunara-review-archive-rail-item {
            flex-basis: min(82vw, 300px) !important;
        }
    }

    @media (min-width: 681px) and (max-width: 900px) {
        body.post-type-archive-review .lunara-review-archive-page .lunara-review-archive-support-suite .lunara-review-archive-rail:not(.lunara-review-archive-dynamic-rail),
        body.page-template-page-reviews .lunara-review-archive-page .lunara-review-archive-support-suite .lunara-review-archive-rail:not(.lunara-review-archive-dynamic-rail) {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.post-type-archive-review .lunara-review-archive-page .lunara-review-archive-support-suite .lunara-review-archive-dynamic-rail,
        body.page-template-page-reviews .lunara-review-archive-page .lunara-review-archive-support-suite .lunara-review-archive-dynamic-rail {
            grid-template-columns: minmax(0, 1fr) !important;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .lunara-review-archive-page .lunara-review-archive-rail-track {
            scroll-behavior: auto !important;
        }

        .lunara-review-archive-page .lunara-review-archive-rail-control,
        .lunara-review-archive-page .lunara-review-archive-rail-dot {
            transition: none !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_footer', 'lunara_output_review_archive_authority_css', 5 );

/**
 * Public review Debrief polish.
 *
 * The Debrief is a retention module, not a raw metadata dump. Keep this layer
 * late so the live review page presents the poster, signature facts, watch
 * links, and pairings as one intentional editorial package.
 */
function lunara_output_review_debrief_polish_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-debrief-polish-css">
    body.single-review .lunara-review-single-debrief-section {
        margin: clamp(52px, 6.5vw, 92px) auto clamp(54px, 7vw, 96px) !important;
        width: min(1180px, calc(100vw - 108px)) !important;
    }

    body.single-review .sharedaddy.sd-sharing-enabled {
        display: none !important;
    }

    body.single-review .lunara-review-single-debrief-wrap {
        display: grid !important;
        isolation: isolate !important;
        padding: clamp(24px, 3.4vw, 42px) !important;
        border: 1px solid rgba(224, 196, 129, 0.22) !important;
        border-radius: 24px !important;
        background:
            radial-gradient(circle at 16% 0%, rgba(224, 196, 129, 0.18), transparent 30%),
            radial-gradient(circle at 92% 12%, rgba(112, 148, 185, 0.12), transparent 28%),
            linear-gradient(135deg, rgba(14, 28, 43, 0.94), rgba(5, 13, 22, 0.98)) !important;
        box-shadow:
            0 34px 76px rgba(0, 0, 0, 0.32),
            0 0 0 1px rgba(255, 255, 255, 0.035) inset !important;
    }

    body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
        align-items: stretch !important;
        grid-template-columns: minmax(210px, 300px) minmax(0, 1fr) !important;
        gap: clamp(22px, 3.2vw, 40px) !important;
    }

    body.single-review .lunara-review-single-debrief-media {
        align-content: start !important;
        display: grid !important;
        gap: 16px !important;
        grid-column: 1 !important;
        grid-row: 1 !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-debrief-poster-shell {
        aspect-ratio: 2 / 3 !important;
        max-width: 280px !important;
        overflow: hidden !important;
        border: 1px solid rgba(224, 196, 129, 0.2) !important;
        border-radius: 18px !important;
        background: rgba(255, 255, 255, 0.035) !important;
        box-shadow: 0 24px 48px rgba(0, 0, 0, 0.36) !important;
    }

    body.single-review .lunara-review-single-debrief-poster {
        display: block !important;
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    body.single-review .lunara-review-single-debrief-media-copy {
        padding: 14px 0 0 !important;
        border-top: 1px solid rgba(224, 196, 129, 0.16) !important;
    }

    body.single-review .lunara-review-single-debrief-media-kicker {
        margin-bottom: 10px !important;
        color: rgba(244, 210, 126, 0.9) !important;
        font-size: 0.78rem !important;
        letter-spacing: 0.18em !important;
        line-height: 1.2 !important;
        text-transform: uppercase !important;
    }

    body.single-review .lunara-review-single-debrief-media-title {
        color: #fafbfc !important;
        font-size: clamp(1.08rem, 1.6vw, 1.32rem) !important;
        line-height: 1.18 !important;
    }

    body.single-review .lunara-review-single-debrief-media-meta {
        margin-top: 8px !important;
        color: rgba(244, 239, 227, 0.74) !important;
        font-size: 0.9rem !important;
        letter-spacing: 0.08em !important;
    }

    body.single-review .lunara-review-single-debrief-wrap.has-signature-media > .lunara-review-single-debrief {
        align-self: center !important;
        grid-column: 2 !important;
        grid-row: 1 !important;
        margin-top: 0 !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-block--signature {
        display: grid !important;
        gap: 18px !important;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-block--signature .lunara-debrief-heading {
        margin: 0 !important;
        max-width: 13ch !important;
        color: #f4d27e !important;
        font-size: clamp(1.72rem, 2.9vw, 2.36rem) !important;
        line-height: 0.98 !important;
        letter-spacing: 0.06em !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-block--signature .lunara-debrief-kicker {
        order: -1 !important;
        margin: 0 !important;
        color: rgba(224, 196, 129, 0.74) !important;
        font-size: 0.74rem !important;
        letter-spacing: 0.22em !important;
        line-height: 1.2 !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-block--signature .lunara-debrief-list--signature {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 12px !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-list--signature li {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) !important;
        gap: 7px !important;
        min-width: 0 !important;
        padding: 14px 16px !important;
        border: 1px solid rgba(224, 196, 129, 0.18) !important;
        border-radius: 12px !important;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.018)) !important;
        box-shadow: 0 14px 30px rgba(0, 0, 0, 0.14) !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-list--signature li strong {
        color: rgba(244, 210, 126, 0.86) !important;
        font-size: 0.7rem !important;
        letter-spacing: 0.16em !important;
        line-height: 1.25 !important;
        text-transform: uppercase !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-list--signature .lunara-debrief-value,
    body.single-review .lunara-review-single-debrief .lunara-debrief-list--signature p {
        color: rgba(250, 251, 252, 0.92) !important;
        font-size: 0.96rem !important;
        line-height: 1.48 !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-ledger-row,
    body.single-review .lunara-review-single-debrief .lunara-debrief-where-row {
        grid-column: 1 / -1 !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-ledger-row strong {
        display: none !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-ledger-row .lunara-debrief-value {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 10px !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-oscar-ledger {
        display: inline-flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 10px !important;
        text-decoration: none !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-review-watch-links {
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)) !important;
        gap: 10px !important;
    }

    body.single-review .lunara-review-single-debrief--pairings {
        margin-top: 20px !important;
        padding: clamp(22px, 3vw, 34px) !important;
        border: 1px solid rgba(224, 196, 129, 0.18) !important;
        border-radius: 24px !important;
        background:
            radial-gradient(circle at 100% 0%, rgba(224, 196, 129, 0.12), transparent 30%),
            linear-gradient(180deg, rgba(12, 27, 43, 0.88), rgba(6, 15, 25, 0.94)) !important;
        box-shadow: 0 24px 58px rgba(0, 0, 0, 0.22) !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list {
        display: grid !important;
        gap: 14px !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list > li {
        padding: 16px !important;
        border: 1px solid rgba(224, 196, 129, 0.14) !important;
        border-radius: 16px !important;
        background: rgba(255, 255, 255, 0.032) !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-pairing {
        grid-template-columns: 96px minmax(0, 1fr) !important;
        gap: 18px !important;
    }

    body.single-review .lunara-review-single-debrief .lunara-debrief-thumb {
        width: 96px !important;
        aspect-ratio: 2 / 3 !important;
        object-fit: cover !important;
        border-radius: 12px !important;
    }

    @media (max-width: 900px) {
        body.single-review .lunara-review-single-debrief-section {
            left: auto !important;
            transform: none !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        body.single-review .lunara-review-single-debrief-wrap {
            padding: 20px !important;
            border-radius: 20px !important;
        }

        body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 22px !important;
        }

        body.single-review .lunara-review-single-debrief-media,
        body.single-review .lunara-review-single-debrief-wrap.has-signature-media > .lunara-review-single-debrief {
            grid-column: 1 !important;
            grid-row: auto !important;
        }

        body.single-review .lunara-review-single-debrief-poster-shell {
            max-width: 220px !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-block--signature .lunara-debrief-list--signature {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-pairing {
            grid-template-columns: 76px minmax(0, 1fr) !important;
            gap: 14px !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-thumb {
            width: 76px !important;
        }
    }

    @media (max-width: 520px) {
        body.single-review .lunara-review-single-debrief-section {
            margin: 42px auto 46px !important;
            width: min(100%, calc(100vw - 28px)) !important;
        }

        body.single-review .lunara-review-single-debrief-wrap {
            padding: 16px !important;
            border-radius: 16px !important;
        }

        body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
            gap: 16px !important;
        }

        body.single-review .lunara-review-single-debrief-media {
            justify-items: center !important;
            gap: 12px !important;
            text-align: center !important;
        }

        body.single-review .lunara-review-single-debrief-poster-shell {
            width: min(100%, 188px) !important;
            max-width: 188px !important;
            margin-inline: auto !important;
            border-radius: 14px !important;
        }

        body.single-review .lunara-review-single-debrief-media-copy {
            width: 100% !important;
            padding-top: 12px !important;
            text-align: center !important;
        }

        body.single-review .lunara-review-single-debrief-media-kicker {
            margin-bottom: 8px !important;
            letter-spacing: 0.14em !important;
        }

        body.single-review .lunara-review-single-debrief-media-title,
        body.single-review .lunara-review-single-debrief .lunara-debrief-block--signature .lunara-debrief-heading {
            max-width: none !important;
            text-align: center !important;
        }

        body.single-review .lunara-review-single-debrief-media-meta {
            letter-spacing: 0.04em !important;
            overflow-wrap: anywhere !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-list--signature li {
            padding: 12px 13px !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_debrief_polish_css', 1001 );

/**
 * Single Review reader-spine polish.
 *
 * Keep the criticism itself centered and authoritative while the utility rail
 * supports it. The Debrief already repeats the factual metadata, so the rail
 * details card is suppressed here to avoid duplicate "Review Details" moments.
 */
function lunara_output_review_reader_spine_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-reader-spine-css">
    body.single-review .lunara-review-single-body {
        margin-inline: auto !important;
    }

    body.single-review .lunara-review-single-details {
        display: none !important;
    }

    body.single-review .lunara-review-single-content {
        margin-inline: auto !important;
    }

    body.single-review .lunara-review-single-content p,
    body.single-review .lunara-review-single-content h2,
    body.single-review .lunara-review-single-content h3,
    body.single-review .lunara-review-single-content ul,
    body.single-review .lunara-review-single-content ol,
    body.single-review .lunara-review-single-content blockquote {
        margin-left: auto !important;
        margin-right: auto !important;
    }

    body.single-review .lunara-review-single-cinematic-hero {
        margin: 0 auto clamp(28px, 3vw, 42px) !important;
        max-width: min(100%, 820px) !important;
    }

    body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual--poster-hero {
        max-width: min(100%, 520px) !important;
        margin-inline: auto !important;
    }

    body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-frame {
        display: grid !important;
        place-items: center !important;
        min-height: 0 !important;
        aspect-ratio: auto !important;
        padding: clamp(8px, 1.2vw, 14px) !important;
        background:
            radial-gradient(circle at 50% 0%, rgba(224, 196, 129, 0.12), transparent 38%),
            linear-gradient(180deg, rgba(12, 26, 42, 0.96), rgba(5, 13, 22, 0.98)) !important;
    }

    body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-image {
        width: auto !important;
        height: auto !important;
        max-width: 100% !important;
        max-height: clamp(380px, 66vh, 680px) !important;
        object-fit: contain !important;
    }

    body.single-review .lunara-review-single-ledger-card .lunara-oscar-ledger {
        display: grid !important;
        gap: 8px !important;
        justify-items: start !important;
        text-decoration: none !important;
    }

    body.single-review .lunara-review-single-ledger-card .lunara-oscar-ledger-counts {
        display: block !important;
        color: rgba(244, 239, 227, 0.86) !important;
        font-size: 0.88rem !important;
        line-height: 1.4 !important;
    }

    @media (min-width: 1040px) {
        body.single-review .lunara-review-single-body {
            width: min(1280px, calc(100vw - 96px)) !important;
            max-width: 1280px !important;
        }

        body.single-review .lunara-review-single-body-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 820px) minmax(220px, 270px) !important;
            justify-content: center !important;
            gap: clamp(30px, 3.2vw, 48px) !important;
        }

        body.single-review .lunara-review-single-content {
            width: 100% !important;
            max-width: 820px !important;
        }

        body.single-review .lunara-review-single-content p,
        body.single-review .lunara-review-single-content h2,
        body.single-review .lunara-review-single-content h3,
        body.single-review .lunara-review-single-content ul,
        body.single-review .lunara-review-single-content ol,
        body.single-review .lunara-review-single-content blockquote {
            max-width: 72ch !important;
        }

        body.single-review .lunara-review-single-rail {
            max-width: 270px !important;
        }

        body.single-review .lunara-review-single-rail-sticky {
            gap: 12px !important;
        }
    }

    @media (min-width: 760px) and (max-width: 1039px) {
        body.single-review .lunara-review-single-body-grid {
            display: grid !important;
            grid-template-columns: minmax(0, min(100%, 820px)) !important;
            justify-content: center !important;
        }

        body.single-review .lunara-review-single-content,
        body.single-review .lunara-review-single-rail {
            width: min(100%, 820px) !important;
            max-width: 820px !important;
            margin-inline: auto !important;
        }

        body.single-review .lunara-review-single-rail-sticky {
            position: static !important;
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 12px !important;
        }

        body.single-review .lunara-review-single-rail-actions {
            grid-column: 1 / -1 !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_reader_spine_css', 1002 );

/**
 * Single Review desktop/tablet editorial repair.
 *
 * Large screens should feel like a trade feature package: strong poster,
 * compact navigation, readable criticism, and quiet utility support.
 */
function lunara_output_review_desktop_editorial_repair_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-desktop-editorial-repair-css">
    @media (min-width: 761px) {
        body.single-review .lunara-review-single-page {
            background:
                radial-gradient(circle at 20% 0%, rgba(224, 196, 129, 0.065), transparent 28%),
                linear-gradient(180deg, rgba(9, 20, 32, 0.98), rgba(7, 17, 28, 1)) !important;
            margin-left: calc(50% - 50vw) !important;
            margin-right: calc(50% - 50vw) !important;
            max-width: 100vw !important;
            overflow-x: hidden !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            width: 100vw !important;
        }

        body.single-review .lunara-review-single-hero {
            width: min(1180px, calc(100vw - 72px)) !important;
            margin: 0 auto !important;
            padding: clamp(24px, 3.2vw, 42px) 0 18px !important;
            border-bottom: 1px solid rgba(224, 196, 129, 0.14) !important;
        }

        body.single-review .lunara-review-single-hero-inner {
            max-width: 760px !important;
            gap: 14px !important;
        }

        body.single-review .lunara-review-single-title {
            color: rgba(250, 251, 252, 0.96) !important;
            font-size: clamp(2.35rem, 4.1vw, 4rem) !important;
            line-height: 0.98 !important;
            text-wrap: balance !important;
        }

        body.single-review .lunara-review-single-body {
            width: min(1180px, calc(100vw - 72px)) !important;
            max-width: 1180px !important;
            margin: 0 auto !important;
            padding: clamp(22px, 3vw, 34px) 0 0 !important;
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        body.single-review .lunara-review-single-body-grid {
            align-items: start !important;
        }

        body.single-review .lunara-review-single-cinematic-hero {
            margin-bottom: clamp(22px, 2.4vw, 34px) !important;
            max-width: min(100%, 500px) !important;
        }

        body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-frame {
            padding: clamp(8px, 1vw, 12px) !important;
            border: 1px solid rgba(224, 196, 129, 0.16) !important;
            border-radius: 8px !important;
            background:
                radial-gradient(circle at 50% 0%, rgba(224, 196, 129, 0.10), transparent 36%),
                linear-gradient(180deg, rgba(12, 27, 43, 0.98), rgba(5, 13, 22, 0.98)) !important;
            box-shadow: 0 22px 52px rgba(0, 0, 0, 0.28) !important;
        }

        body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-image {
            max-height: clamp(500px, 58vh, 620px) !important;
        }

        body.single-review .lunara-reader-toc {
            margin: 0 0 clamp(26px, 3vw, 40px) !important;
            padding: 16px 18px 18px !important;
            border: 1px solid rgba(224, 196, 129, 0.18) !important;
            border-radius: 10px !important;
            background: linear-gradient(180deg, rgba(15, 30, 47, 0.88), rgba(8, 18, 30, 0.94)) !important;
        }

        body.single-review .lunara-reader-toc-kicker,
        body.single-review .lunara-reader-toc-title {
            margin: 0 0 12px !important;
            color: #e0c481 !important;
            text-align: left !important;
        }

        body.single-review .lunara-reader-toc-links,
        body.single-review .lunara-reader-toc-list {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 10px !important;
        }

        body.single-review .lunara-reader-toc-link {
            display: flex !important;
            align-items: center !important;
            min-height: 42px !important;
            padding: 9px 12px !important;
            border-radius: 8px !important;
            color: #e0c481 !important;
            font-size: 0.88rem !important;
            line-height: 1.2 !important;
            text-decoration: none !important;
        }

        body.single-review .lunara-review-single-content > p,
        body.single-review .lunara-review-single-content > ul,
        body.single-review .lunara-review-single-content > ol,
        body.single-review .lunara-review-single-content > blockquote {
            color: rgba(250, 251, 252, 0.95) !important;
            font-size: clamp(1.02rem, 1.08vw, 1.12rem) !important;
            line-height: 1.76 !important;
        }

        body.single-review .lunara-review-single-content > p:first-of-type {
            color: rgba(250, 251, 252, 0.98) !important;
            font-size: clamp(1.08rem, 1.22vw, 1.22rem) !important;
            line-height: 1.66 !important;
        }

        body.single-review .lunara-review-single-content > h2 {
            margin-top: clamp(30px, 3.2vw, 46px) !important;
            color: #e0c481 !important;
            font-size: clamp(1.58rem, 2vw, 2.05rem) !important;
            line-height: 1.08 !important;
            text-wrap: balance !important;
        }

        body.single-review .lunara-review-single-rail-sticky {
            gap: 14px !important;
        }

        body.single-review .lunara-review-single-rail .lunara-journal-rail-card,
        body.single-review .lunara-review-single-where-card {
            padding: 16px !important;
            border-radius: 10px !important;
            background: linear-gradient(180deg, rgba(15, 30, 47, 0.88), rgba(8, 18, 30, 0.94)) !important;
        }

        body.single-review .lunara-review-single-where-card {
            display: none !important;
        }

        body.single-review .lunara-review-single-rail-actions {
            display: grid !important;
            gap: 10px !important;
        }

        body.single-review .lunara-review-single-rail-actions .lunara-btn {
            min-height: 42px !important;
            border-radius: 8px !important;
            font-size: 0.78rem !important;
            letter-spacing: 0.1em !important;
        }

        body.single-review .lunara-review-related {
            width: min(1180px, calc(100vw - 72px)) !important;
            margin: clamp(48px, 5.4vw, 78px) auto 0 !important;
            padding: 24px !important;
            border-radius: 10px !important;
        }

        body.single-review .lunara-review-related-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }

    @media (min-width: 1040px) {
        body.single-review .lunara-review-single-body-grid {
            grid-template-columns: minmax(0, 720px) minmax(220px, 270px) !important;
            justify-content: center !important;
            gap: clamp(34px, 4vw, 64px) !important;
        }

        body.single-review .lunara-review-single-content {
            max-width: 720px !important;
        }

        body.single-review .lunara-review-single-cinematic-hero {
            margin-left: auto !important;
            margin-right: auto !important;
            max-width: 480px !important;
        }

        body.single-review .lunara-review-single-rail {
            padding-top: 6px !important;
        }
    }

    @media (min-width: 761px) and (max-width: 1039px) {
        body.single-review .lunara-review-single-hero,
        body.single-review .lunara-review-single-body,
        body.single-review .lunara-review-related {
            width: min(100%, calc(100vw - 48px)) !important;
        }

        body.single-review .lunara-review-single-cinematic-hero {
            max-width: min(100%, 480px) !important;
        }

        body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-image {
            max-height: 620px !important;
        }

        body.single-review .lunara-reader-toc-links,
        body.single-review .lunara-reader-toc-list {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.single-review .lunara-review-single-rail {
            margin: 20px auto 0 !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_desktop_editorial_repair_css', 1003 );

/**
 * Single Review mobile editorial repair.
 *
 * Phone readers should get a confident article package, not a shrunken
 * desktop proof. Keep the poster strong, compress utility modules, and make
 * related criticism behave like a retention lane instead of full-page posters.
 */
function lunara_output_review_mobile_editorial_repair_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-mobile-editorial-repair-css">
    @media (max-width: 760px) {
        body.single-review,
        body.single-review #main-container {
            background: #07131f !important;
            overflow-x: hidden !important;
        }

        body.single-review main.lunara-review-single-page,
        body.single-review .lunara-review-single-page {
            max-width: 100vw !important;
            margin-left: calc(50% - 50vw) !important;
            margin-right: calc(50% - 50vw) !important;
            padding: 0 16px 52px !important;
            width: 100vw !important;
        }

        body.single-review .lunara-review-single-hero {
            margin: 0 -16px 0 !important;
            padding: 22px 22px 18px !important;
            width: calc(100% + 32px) !important;
            border-bottom: 1px solid rgba(224, 196, 129, 0.16) !important;
            background:
                radial-gradient(circle at 86% 0%, rgba(224, 196, 129, 0.12), transparent 34%),
                linear-gradient(180deg, rgba(17, 31, 46, 0.98), rgba(7, 19, 31, 0.98)) !important;
        }

        body.single-review .lunara-review-single-hero-inner {
            gap: 12px !important;
            max-width: 100% !important;
        }

        body.single-review .lunara-review-single-title {
            font-size: clamp(2.05rem, 10vw, 2.65rem) !important;
            line-height: 0.98 !important;
            max-width: 11ch !important;
            text-wrap: balance !important;
        }

        body.single-review .lunara-review-single-meta {
            gap: 9px !important;
            line-height: 1.35 !important;
        }

        body.single-review .lunara-review-single-body {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 auto !important;
            padding: 20px 0 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            overflow: visible !important;
        }

        body.single-review .lunara-review-single-body-grid {
            display: flex !important;
            flex-direction: column !important;
            gap: 22px !important;
            width: 100% !important;
        }

        body.single-review .lunara-review-single-content {
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            max-width: 100% !important;
            gap: 0 !important;
        }

        body.single-review .lunara-review-single-cinematic-hero {
            order: -3 !important;
            margin: 0 auto 24px !important;
            width: min(100%, 318px) !important;
            max-width: 318px !important;
        }

        body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual--poster-hero,
        body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-frame {
            width: 100% !important;
            max-width: 100% !important;
        }

        body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-frame {
            padding: 8px !important;
            border: 1px solid rgba(224, 196, 129, 0.2) !important;
            border-radius: 8px !important;
            background: linear-gradient(180deg, rgba(11, 24, 38, 0.98), rgba(4, 11, 19, 0.98)) !important;
            box-shadow: 0 18px 38px rgba(0, 0, 0, 0.32) !important;
        }

        body.single-review .lunara-review-single-cinematic-hero .lunara-review-visual-image {
            display: block !important;
            width: 100% !important;
            height: auto !important;
            max-height: none !important;
            object-fit: contain !important;
        }

        body.single-review .lunara-reader-toc {
            order: -2 !important;
            margin: 0 0 24px !important;
            padding: 14px 14px 16px !important;
            border-radius: 8px !important;
            background: linear-gradient(180deg, rgba(16, 31, 48, 0.92), rgba(8, 18, 30, 0.96)) !important;
        }

        body.single-review .lunara-reader-toc-title,
        body.single-review .lunara-reader-toc-kicker {
            margin-bottom: 10px !important;
            text-align: left !important;
        }

        body.single-review .lunara-reader-toc-links,
        body.single-review .lunara-reader-toc-list {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 8px !important;
            overflow: visible !important;
            padding: 0 0 4px !important;
            scroll-snap-type: none !important;
        }

        body.single-review .lunara-reader-toc-links .lunara-reader-toc-link,
        body.single-review .lunara-reader-toc-item {
            width: 100% !important;
            margin: 0 !important;
            scroll-snap-align: none !important;
        }

        body.single-review .lunara-reader-toc-link {
            display: flex !important;
            align-items: center !important;
            min-height: 42px !important;
            width: 100% !important;
            max-width: none !important;
            padding: 9px 12px !important;
            border-radius: 8px !important;
            font-size: 0.9rem !important;
            line-height: 1.22 !important;
            white-space: normal !important;
            text-wrap: balance !important;
        }

        body.single-review .lunara-review-single-content > p,
        body.single-review .lunara-review-single-content > ul,
        body.single-review .lunara-review-single-content > ol,
        body.single-review .lunara-review-single-content > blockquote {
            width: 100% !important;
            max-width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            color: rgba(250, 251, 252, 0.96) !important;
            font-size: 1.05rem !important;
            line-height: 1.76 !important;
        }

        body.single-review .lunara-review-single-content > h2,
        body.single-review .lunara-review-single-content > h3 {
            width: 100% !important;
            max-width: 100% !important;
            margin: 30px 0 14px !important;
            color: #e0c481 !important;
            text-wrap: balance !important;
        }

        body.single-review .lunara-review-single-content > h2 {
            font-size: clamp(1.36rem, 6.2vw, 1.58rem) !important;
            line-height: 1.12 !important;
        }

        body.single-review .lunara-review-single-content > h3 {
            font-size: clamp(1.24rem, 5.8vw, 1.5rem) !important;
            line-height: 1.18 !important;
        }

        body.single-review .lunara-review-single-rail {
            display: none !important;
        }

        body.single-review .lunara-review-single-rail-sticky {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 10px !important;
            position: static !important;
        }

        body.single-review .lunara-review-single-rail .lunara-journal-rail-card {
            padding: 13px 14px !important;
            border-radius: 8px !important;
            background: linear-gradient(180deg, rgba(13, 28, 44, 0.94), rgba(8, 18, 30, 0.96)) !important;
        }

        body.single-review .lunara-review-single-where-card {
            display: none !important;
        }

        body.single-review .lunara-review-single-rail-actions {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 10px !important;
        }

        body.single-review .lunara-review-single-rail-actions .lunara-btn {
            min-height: 42px !important;
            padding: 9px 11px !important;
            border-radius: 8px !important;
            font-size: 0.78rem !important;
            letter-spacing: 0.08em !important;
        }

        body.single-review .lunara-review-single-debrief-section {
            width: 100% !important;
            max-width: 100% !important;
            margin: 38px auto 42px !important;
        }

        body.single-review .lunara-review-single-debrief-wrap,
        body.single-review .lunara-review-single-debrief--pairings {
            padding: 16px !important;
            border-radius: 10px !important;
        }

        body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
            grid-template-columns: 112px minmax(0, 1fr) !important;
            align-items: start !important;
            gap: 14px !important;
        }

        body.single-review .lunara-review-single-debrief-media,
        body.single-review .lunara-review-single-debrief-wrap.has-signature-media > .lunara-review-single-debrief {
            grid-column: auto !important;
            grid-row: 1 !important;
        }

        body.single-review .lunara-review-single-debrief-poster-shell {
            max-width: 112px !important;
            justify-self: start !important;
        }

        body.single-review .lunara-review-single-debrief-media-copy {
            display: none !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-block--signature .lunara-debrief-heading {
            max-width: 100% !important;
            font-size: clamp(1.18rem, 5.8vw, 1.42rem) !important;
            line-height: 1.04 !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-list--signature li,
        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list > li {
            padding: 12px !important;
            border-radius: 8px !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-pairing {
            display: grid !important;
            grid-template-columns: 58px minmax(0, 1fr) !important;
            gap: 12px !important;
            align-items: start !important;
        }

        body.single-review .lunara-review-single-debrief .lunara-debrief-thumb {
            width: 58px !important;
            border-radius: 7px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list {
            gap: 10px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-note {
            font-size: 0.88rem !important;
            line-height: 1.48 !important;
        }

        body.single-review .lunara-review-related {
            display: grid !important;
            gap: 16px !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 34px 0 0 !important;
            padding: 18px 0 0 !important;
            border: 0 !important;
            border-top: 1px solid rgba(224, 196, 129, 0.18) !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            overflow: visible !important;
        }

        body.single-review .lunara-review-related::before {
            display: none !important;
        }

        body.single-review .lunara-review-related .lunara-home-section-head {
            display: flex !important;
            align-items: end !important;
            justify-content: space-between !important;
            gap: 14px !important;
            margin: 0 !important;
        }

        body.single-review .lunara-review-related .lunara-section-title {
            font-size: clamp(1.32rem, 7vw, 1.68rem) !important;
            line-height: 1.08 !important;
        }

        body.single-review .lunara-review-related .lunara-section-link {
            flex: 0 0 auto !important;
            font-size: 0.75rem !important;
            white-space: nowrap !important;
        }

        body.single-review .lunara-review-related-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 10px !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-card {
            width: 100% !important;
            min-height: 0 !important;
            border-radius: 8px !important;
            overflow: hidden !important;
            background: linear-gradient(180deg, rgba(14, 28, 43, 0.92), rgba(8, 18, 30, 0.96)) !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-link {
            display: grid !important;
            grid-template-columns: 96px minmax(0, 1fr) !important;
            align-items: stretch !important;
            min-height: 142px !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-poster-wrap {
            width: 96px !important;
            min-width: 96px !important;
            height: 142px !important;
            max-height: 142px !important;
            aspect-ratio: auto !important;
            border-radius: 0 !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-poster,
        body.single-review .lunara-review-related .lunara-review-grid-poster-wrap img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-copy {
            display: grid !important;
            align-content: center !important;
            gap: 7px !important;
            min-width: 0 !important;
            padding: 12px 14px !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-title {
            font-size: 1rem !important;
            line-height: 1.16 !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-excerpt {
            display: none !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-meta {
            font-size: 0.78rem !important;
            line-height: 1.35 !important;
        }
    }

    @media (max-width: 380px) {
        body.single-review main.lunara-review-single-page,
        body.single-review .lunara-review-single-page {
            padding-left: 14px !important;
            padding-right: 14px !important;
        }

        body.single-review .lunara-review-single-hero {
            margin-left: -14px !important;
            margin-right: -14px !important;
            width: calc(100% + 28px) !important;
        }

        body.single-review .lunara-review-single-cinematic-hero {
            width: min(100%, 294px) !important;
            max-width: 294px !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-link {
            grid-template-columns: 86px minmax(0, 1fr) !important;
            min-height: 128px !important;
        }

        body.single-review .lunara-review-related .lunara-review-grid-poster-wrap {
            width: 86px !important;
            min-width: 86px !important;
            height: 128px !important;
            max-height: 128px !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_mobile_editorial_repair_css', 1004 );

/**
 * Single Review Pair It With programming polish.
 */
function lunara_output_review_pair_it_with_polish_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-pair-it-with-polish-css">
    body.single-review .lunara-review-single-debrief--pairings {
        box-sizing: border-box !important;
        margin: clamp(24px, 4vw, 42px) auto 0 !important;
        max-width: min(100%, 980px) !important;
        overflow: hidden !important;
        padding: clamp(18px, 2.6vw, 28px) !important;
        border: 1px solid rgba(224, 196, 129, 0.26) !important;
        border-radius: 16px !important;
        background:
            radial-gradient(circle at 15% 0%, rgba(224, 196, 129, 0.13), transparent 34%),
            linear-gradient(135deg, rgba(15, 32, 49, 0.98), rgba(6, 16, 27, 0.98) 58%, rgba(13, 28, 44, 0.96)) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04) !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-block--pairings {
        display: grid !important;
        gap: clamp(14px, 2vw, 20px) !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairings-head {
        display: flex !important;
        align-items: end !important;
        justify-content: space-between !important;
        gap: 16px !important;
        margin: 0 !important;
        padding: 0 0 14px !important;
        border-bottom: 1px solid rgba(224, 196, 129, 0.2) !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-kicker--pairings {
        margin: 0 !important;
        color: rgba(244, 239, 227, 0.96) !important;
        font-family: var(--lunara-heading-font, inherit) !important;
        font-size: clamp(1.16rem, 2.1vw, 1.55rem) !important;
        font-weight: 800 !important;
        letter-spacing: 0.03em !important;
        line-height: 1.1 !important;
        text-transform: none !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list--pairings {
        display: grid !important;
        align-items: start !important;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 252px), 1fr)) !important;
        gap: clamp(12px, 1.7vw, 16px) !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pair-row {
        display: grid !important;
        align-content: start !important;
        gap: 12px !important;
        min-width: 0 !important;
        min-height: 0 !important;
        height: auto !important;
        padding: clamp(13px, 1.5vw, 16px) !important;
        border: 1px solid rgba(224, 196, 129, 0.2) !important;
        border-radius: 14px !important;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.055), rgba(255, 255, 255, 0.022)),
            rgba(7, 18, 30, 0.84) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.035) !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pair-type {
        display: inline-flex !important;
        width: fit-content !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 6px 9px 5px !important;
        border: 1px solid rgba(224, 196, 129, 0.22) !important;
        border-radius: 999px !important;
        color: rgba(224, 196, 129, 0.96) !important;
        font-family: var(--lunara-body-font, inherit) !important;
        font-size: 0.68rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.12em !important;
        line-height: 1 !important;
        text-transform: uppercase !important;
        white-space: normal !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-value {
        display: block !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairing {
        display: grid !important;
        grid-template-columns: minmax(72px, 96px) minmax(0, 1fr) !important;
        align-items: start !important;
        gap: clamp(11px, 1.4vw, 15px) !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-thumb-wrap {
        display: block !important;
        width: 100% !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-thumb {
        display: block !important;
        width: 100% !important;
        aspect-ratio: 2 / 3 !important;
        object-fit: cover !important;
        border: 1px solid rgba(224, 196, 129, 0.18) !important;
        border-radius: 10px !important;
        box-shadow: none !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairing-text {
        display: grid !important;
        align-content: start !important;
        gap: 8px !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-line1 {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 7px !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-pair-title {
        min-width: 0 !important;
        max-width: 100% !important;
        color: rgba(248, 244, 234, 0.98) !important;
        font-size: clamp(0.98rem, 1.1vw, 1.06rem) !important;
        font-weight: 800 !important;
        line-height: 1.16 !important;
        overflow-wrap: anywhere !important;
        text-decoration: none !important;
        text-wrap: pretty !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-note {
        color: rgba(244, 239, 227, 0.78) !important;
        font-size: 0.91rem !important;
        line-height: 1.5 !important;
        text-wrap: pretty !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-chip,
    body.single-review .lunara-review-single-debrief--pairings .lunara-oscar-ledger-pill {
        flex: 0 0 auto !important;
        max-width: 100% !important;
        white-space: normal !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-oscar-ledger {
        display: inline-flex !important;
        flex: 0 1 auto !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 6px !important;
        max-width: 100% !important;
        text-decoration: none !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-oscar-ledger-pill {
        min-height: 0 !important;
        padding: 5px 8px !important;
        border: 1px solid rgba(224, 196, 129, 0.3) !important;
        border-radius: 999px !important;
        background: rgba(224, 196, 129, 0.08) !important;
        color: rgba(224, 196, 129, 0.96) !important;
        font-size: 0.66rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.1em !important;
        line-height: 1 !important;
        text-transform: uppercase !important;
    }

    body.single-review .lunara-review-single-debrief--pairings .lunara-oscar-ledger-counts {
        display: inline-block !important;
        color: rgba(244, 239, 227, 0.66) !important;
        font-size: 0.72rem !important;
        line-height: 1.15 !important;
        white-space: normal !important;
    }

    @media (min-width: 1120px) {
        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list--pairings {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairing {
            grid-template-columns: 92px minmax(0, 1fr) !important;
        }
    }

    @media (max-width: 680px) {
        body.single-review .lunara-review-single-debrief--pairings {
            margin-top: 26px !important;
            padding: 14px !important;
            border-radius: 12px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairings-head {
            align-items: start !important;
            padding-bottom: 12px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list--pairings {
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 10px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pair-row {
            gap: 10px !important;
            padding: 12px !important;
            border-radius: 10px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairing {
            grid-template-columns: 66px minmax(0, 1fr) !important;
            gap: 11px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pair-type {
            font-size: 0.62rem !important;
            letter-spacing: 0.1em !important;
            padding: 5px 8px !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-pair-title {
            font-size: 0.96rem !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-note {
            font-size: 0.86rem !important;
            line-height: 1.44 !important;
        }

        body.single-review .lunara-review-single-debrief--pairings .lunara-oscar-ledger-counts {
            flex-basis: 100% !important;
            font-size: 0.68rem !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_pair_it_with_polish_css', 1005 );

/**
 * Full spoiler Review warning and archive labels.
 */
function lunara_output_full_spoiler_review_css() {
    if ( is_admin() || is_feed() || ( ! is_singular( 'review' ) && ! is_post_type_archive( 'review' ) && ! is_home() && ! is_front_page() && ! is_search() ) ) {
        return;
    }
    ?>
    <style id="lunara-full-spoiler-review-css">
    body.single-review .lunara-full-spoiler-warning {
        box-sizing: border-box !important;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        align-items: center !important;
        gap: clamp(16px, 2.5vw, 30px) !important;
        margin: 0 0 clamp(22px, 3vw, 34px) !important;
        padding: clamp(18px, 2.8vw, 30px) !important;
        border: 1px solid rgba(224, 196, 129, 0.48) !important;
        border-radius: 16px !important;
        background:
            linear-gradient(90deg, rgba(224, 196, 129, 0.2), transparent 36%),
            linear-gradient(135deg, rgba(18, 29, 42, 0.98), rgba(7, 17, 29, 0.98) 62%, rgba(24, 14, 14, 0.93)) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.055),
            0 22px 50px rgba(0, 0, 0, 0.22) !important;
    }

    body.single-review .lunara-full-spoiler-warning-copy {
        display: grid !important;
        gap: 8px !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-full-spoiler-warning-kicker {
        margin: 0 !important;
        color: rgba(244, 216, 143, 0.98) !important;
        font-family: var(--lunara-body-font, inherit) !important;
        font-size: 0.74rem !important;
        font-weight: 900 !important;
        letter-spacing: 0.18em !important;
        line-height: 1 !important;
        text-transform: uppercase !important;
    }

    body.single-review .lunara-full-spoiler-warning-title {
        margin: 0 !important;
        color: rgba(255, 250, 236, 0.98) !important;
        font-family: var(--lunara-heading-font, inherit) !important;
        font-size: clamp(1.25rem, 2.4vw, 1.78rem) !important;
        line-height: 1.08 !important;
        text-wrap: balance !important;
    }

    body.single-review .lunara-full-spoiler-warning-text {
        max-width: 70ch !important;
        margin: 0 !important;
        color: rgba(244, 239, 227, 0.76) !important;
        font-size: clamp(0.95rem, 1.15vw, 1.04rem) !important;
        line-height: 1.5 !important;
        text-wrap: pretty !important;
    }

    body.single-review .lunara-full-spoiler-warning-link {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 44px !important;
        max-width: 100% !important;
        padding: 11px 18px !important;
        border: 1px solid rgba(224, 196, 129, 0.58) !important;
        border-radius: 999px !important;
        background: rgba(224, 196, 129, 0.12) !important;
        color: rgba(244, 216, 143, 0.98) !important;
        font-family: var(--lunara-body-font, inherit) !important;
        font-size: 0.76rem !important;
        font-weight: 900 !important;
        letter-spacing: 0.1em !important;
        line-height: 1.1 !important;
        text-align: center !important;
        text-decoration: none !important;
        text-transform: uppercase !important;
        white-space: normal !important;
    }

    body.single-review .lunara-full-spoiler-warning-link:hover,
    body.single-review .lunara-full-spoiler-warning-link:focus-visible {
        border-color: rgba(244, 216, 143, 0.9) !important;
        background: rgba(224, 196, 129, 0.2) !important;
        color: #fff6dc !important;
    }

    body.single-review .lunara-full-spoiler-shield-actions {
        display: grid !important;
        gap: 10px !important;
        justify-items: stretch !important;
        min-width: min(285px, 100%) !important;
    }

    body.single-review .lunara-full-spoiler-shield-button {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 48px !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 12px 18px !important;
        border: 1px solid rgba(244, 216, 143, 0.82) !important;
        border-radius: 999px !important;
        background: linear-gradient(135deg, rgba(244, 216, 143, 0.98), rgba(171, 126, 47, 0.96)) !important;
        color: rgba(8, 17, 29, 0.98) !important;
        cursor: pointer !important;
        font-family: var(--lunara-body-font, inherit) !important;
        font-size: 0.76rem !important;
        font-weight: 950 !important;
        letter-spacing: 0.08em !important;
        line-height: 1.12 !important;
        text-align: center !important;
        text-transform: uppercase !important;
        white-space: normal !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.28),
            0 16px 32px rgba(0, 0, 0, 0.24) !important;
    }

    body.single-review .lunara-full-spoiler-shield-button:hover,
    body.single-review .lunara-full-spoiler-shield-button:focus-visible {
        background: linear-gradient(135deg, rgba(255, 231, 163, 1), rgba(193, 145, 52, 0.98)) !important;
        color: rgba(5, 14, 25, 1) !important;
        outline: 2px solid rgba(255, 246, 220, 0.62) !important;
        outline-offset: 3px !important;
    }

    body.has-lunara-spoiler-gate .lunara-spoiler-protected-content:not(.is-revealed) {
        display: none !important;
    }

    body.has-lunara-spoiler-gate .lunara-full-spoiler-shield.is-acknowledged {
        display: none !important;
    }

    body.has-lunara-spoiler-gate .lunara-spoiler-protected-content.is-revealed {
        scroll-margin-top: clamp(88px, 12vh, 148px) !important;
        animation: lunaraSpoilerReveal 220ms ease-out both;
    }

    body.single-review .lunara-spoiler-protected-content:focus {
        outline: none !important;
    }

    @keyframes lunaraSpoilerReveal {
        from {
            opacity: 0;
            transform: translateY(8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        body.has-lunara-spoiler-gate .lunara-spoiler-protected-content.is-revealed {
            animation: none !important;
        }
    }

    .lunara-review-grid-card.is-full-spoiler-review .lunara-review-grid-kicker {
        color: rgba(244, 216, 143, 0.98) !important;
    }

    .lunara-review-grid-card.is-full-spoiler-review .lunara-review-grid-poster-wrap::after {
        content: "Full Spoilers" !important;
        position: absolute !important;
        left: 12px !important;
        bottom: 12px !important;
        z-index: 4 !important;
        max-width: calc(100% - 24px) !important;
        padding: 5px 9px !important;
        border: 1px solid rgba(244, 216, 143, 0.58) !important;
        border-radius: 999px !important;
        background: rgba(5, 14, 25, 0.84) !important;
        color: rgba(244, 216, 143, 0.98) !important;
        font-family: var(--lunara-body-font, inherit) !important;
        font-size: 0.62rem !important;
        font-weight: 900 !important;
        letter-spacing: 0.1em !important;
        line-height: 1 !important;
        text-transform: uppercase !important;
        box-shadow: 0 10px 22px rgba(0, 0, 0, 0.24) !important;
    }

    @media (max-width: 760px) {
        body.single-review .lunara-full-spoiler-warning {
            grid-template-columns: minmax(0, 1fr) !important;
            align-items: stretch !important;
            padding: 16px !important;
            border-radius: 13px !important;
        }

        body.single-review .lunara-full-spoiler-warning-link {
            width: 100% !important;
        }

        body.single-review .lunara-full-spoiler-shield-actions {
            min-width: 0 !important;
        }
    }
    </style>
    <script id="lunara-full-spoiler-shield-js">
    (function() {
        function keyFor(postId) {
            return 'lunaraFullSpoilerAcknowledged:' + postId;
        }

        function getStored(postId) {
            try {
                return window.sessionStorage && window.sessionStorage.getItem(keyFor(postId)) === '1';
            } catch (error) {
                return false;
            }
        }

        function setStored(postId) {
            try {
                if (window.sessionStorage) {
                    window.sessionStorage.setItem(keyFor(postId), '1');
                }
            } catch (error) {
                return false;
            }

            return true;
        }

        function getProtected(postId) {
            var nodes = document.querySelectorAll('[data-lunara-spoiler-protected]');
            return Array.prototype.filter.call(nodes, function(node) {
                return node.getAttribute('data-lunara-spoiler-post') === String(postId);
            });
        }

        function applyState(shield, isRevealed) {
            var postId = shield.getAttribute('data-lunara-spoiler-post');
            var protectedNodes = getProtected(postId);

            shield.classList.toggle('is-acknowledged', isRevealed);
            shield.setAttribute('aria-hidden', isRevealed ? 'true' : 'false');

            protectedNodes.forEach(function(node) {
                node.classList.toggle('is-revealed', isRevealed);
                node.setAttribute('aria-hidden', isRevealed ? 'false' : 'true');
            });
        }

        function initSpoilerShields() {
            var shields = document.querySelectorAll('[data-lunara-spoiler-shield]');
            if (!shields.length || !document.body) {
                return;
            }

            document.body.classList.add('has-lunara-spoiler-gate');

            shields.forEach(function(shield) {
                var postId = shield.getAttribute('data-lunara-spoiler-post');
                var button = shield.querySelector('[data-lunara-spoiler-reveal]');

                applyState(shield, getStored(postId));

                if (!button) {
                    return;
                }

                button.addEventListener('click', function() {
                    setStored(postId);
                    applyState(shield, true);

                    var firstProtected = getProtected(postId)[0];
                    if (firstProtected) {
                        var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        firstProtected.setAttribute('tabindex', '-1');
                        firstProtected.focus({ preventScroll: true });
                        firstProtected.scrollIntoView({ block: 'start', behavior: reducedMotion ? 'auto' : 'smooth' });
                    }
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSpoilerShields);
        } else {
            initSpoilerShields();
        }
    })();
    </script>
    <?php
}
add_action( 'wp_head', 'lunara_output_full_spoiler_review_css', 1006 );

/**
 * Single Review spoiler companion bridge.
 */
function lunara_output_review_spoiler_bridge_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-spoiler-bridge-css">
    body.single-review .lunara-spoiler-review-bridge {
        box-sizing: border-box !important;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        align-items: center !important;
        gap: clamp(16px, 2.4vw, 28px) !important;
        margin: clamp(28px, 4vw, 48px) auto clamp(10px, 2vw, 18px) !important;
        padding: clamp(18px, 2.6vw, 28px) !important;
        border: 1px solid rgba(224, 196, 129, 0.3) !important;
        border-radius: 16px !important;
        background:
            linear-gradient(90deg, rgba(224, 196, 129, 0.12), transparent 32%),
            linear-gradient(135deg, rgba(11, 25, 40, 0.98), rgba(5, 14, 25, 0.98) 58%, rgba(15, 31, 47, 0.96)) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.045),
            0 18px 42px rgba(0, 0, 0, 0.18) !important;
    }

    body.single-review .lunara-spoiler-review-bridge-copy {
        display: grid !important;
        gap: 8px !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-spoiler-review-bridge-kicker {
        margin: 0 !important;
        color: rgba(224, 196, 129, 0.96) !important;
        font-family: var(--lunara-body-font, inherit) !important;
        font-size: 0.72rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.14em !important;
        line-height: 1 !important;
        text-transform: uppercase !important;
    }

    body.single-review .lunara-spoiler-review-bridge-title {
        margin: 0 !important;
        color: rgba(248, 244, 234, 0.98) !important;
        font-family: var(--lunara-heading-font, inherit) !important;
        font-size: clamp(1.18rem, 2.2vw, 1.62rem) !important;
        line-height: 1.08 !important;
        text-wrap: balance !important;
    }

    body.single-review .lunara-spoiler-review-bridge-text,
    body.single-review .lunara-spoiler-review-bridge-source {
        max-width: 64ch !important;
        margin: 0 !important;
        color: rgba(244, 239, 227, 0.74) !important;
        font-size: clamp(0.92rem, 1.2vw, 1rem) !important;
        line-height: 1.5 !important;
        text-wrap: pretty !important;
    }

    body.single-review .lunara-spoiler-review-bridge-source {
        color: rgba(224, 196, 129, 0.82) !important;
        font-size: 0.82rem !important;
        font-weight: 700 !important;
    }

    body.single-review .lunara-spoiler-review-bridge-link {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 44px !important;
        max-width: 100% !important;
        padding: 11px 18px !important;
        border: 1px solid rgba(224, 196, 129, 0.58) !important;
        border-radius: 999px !important;
        background: rgba(224, 196, 129, 0.12) !important;
        color: rgba(244, 216, 143, 0.98) !important;
        font-family: var(--lunara-body-font, inherit) !important;
        font-size: 0.78rem !important;
        font-weight: 900 !important;
        letter-spacing: 0.1em !important;
        line-height: 1.1 !important;
        text-align: center !important;
        text-decoration: none !important;
        text-transform: uppercase !important;
        white-space: normal !important;
    }

    body.single-review .lunara-spoiler-review-bridge-link:hover,
    body.single-review .lunara-spoiler-review-bridge-link:focus-visible {
        border-color: rgba(244, 216, 143, 0.9) !important;
        background: rgba(224, 196, 129, 0.2) !important;
        color: #fff6dc !important;
    }

    @media (max-width: 760px) {
        body.single-review .lunara-spoiler-review-bridge {
            grid-template-columns: minmax(0, 1fr) !important;
            align-items: stretch !important;
            margin-top: 30px !important;
            padding: 16px !important;
            border-radius: 13px !important;
        }

        body.single-review .lunara-spoiler-review-bridge-link {
            width: 100% !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_spoiler_bridge_css', 1007 );

/**
 * Single Review owned share strip.
 */
function lunara_output_review_share_strip_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-share-strip-css">
    body.single-review .lunara-review-share-strip {
        box-sizing: border-box !important;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        align-items: center !important;
        gap: clamp(14px, 2vw, 22px) !important;
        max-width: min(100%, 72ch) !important;
        margin: clamp(28px, 4vw, 46px) auto clamp(8px, 2vw, 16px) !important;
        padding: clamp(14px, 2vw, 20px) !important;
        border: 1px solid rgba(224, 196, 129, 0.24) !important;
        border-radius: 14px !important;
        background:
            linear-gradient(90deg, rgba(224, 196, 129, 0.1), transparent 38%),
            rgba(9, 22, 36, 0.92) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.04),
            0 16px 34px rgba(0, 0, 0, 0.16) !important;
    }

    body.single-review .lunara-review-share-strip-copy {
        display: grid !important;
        gap: 6px !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-share-strip-kicker,
    body.single-review .lunara-review-share-strip-title,
    body.single-review .lunara-review-share-status {
        margin: 0 !important;
    }

    body.single-review .lunara-review-share-strip-kicker {
        color: rgba(224, 196, 129, 0.88) !important;
        font-size: 0.68rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.16em !important;
        line-height: 1 !important;
        text-transform: uppercase !important;
    }

    body.single-review .lunara-review-share-strip-title {
        color: rgba(248, 244, 234, 0.96) !important;
        font-family: var(--lunara-heading-font, inherit) !important;
        font-size: clamp(1.04rem, 1.35vw, 1.18rem) !important;
        font-weight: 800 !important;
        line-height: 1.15 !important;
        text-wrap: pretty !important;
    }

    body.single-review .lunara-review-share-strip-actions {
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: flex-end !important;
        gap: 8px !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-share-link {
        appearance: none !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 34px !important;
        max-width: 100% !important;
        padding: 8px 11px !important;
        border: 1px solid rgba(224, 196, 129, 0.3) !important;
        border-radius: 999px !important;
        background: rgba(224, 196, 129, 0.075) !important;
        color: rgba(244, 216, 143, 0.98) !important;
        cursor: pointer !important;
        font-family: var(--lunara-body-font, inherit) !important;
        font-size: 0.68rem !important;
        font-weight: 900 !important;
        letter-spacing: 0.09em !important;
        line-height: 1 !important;
        text-align: center !important;
        text-decoration: none !important;
        text-transform: uppercase !important;
        white-space: normal !important;
    }

    body.single-review .lunara-review-share-link:hover,
    body.single-review .lunara-review-share-link:focus-visible {
        border-color: rgba(244, 216, 143, 0.85) !important;
        background: rgba(224, 196, 129, 0.16) !important;
        color: #fff6dc !important;
    }

    body.single-review .lunara-review-share-copy.is-copied {
        border-color: rgba(160, 210, 172, 0.8) !important;
        color: rgba(198, 238, 206, 0.98) !important;
    }

    body.single-review .lunara-review-share-status {
        grid-column: 1 / -1 !important;
        min-height: 1em !important;
        color: rgba(244, 239, 227, 0.66) !important;
        font-size: 0.72rem !important;
        line-height: 1.2 !important;
    }

    body.single-review .sharedaddy.sd-sharing-enabled {
        display: none !important;
    }

    @media (max-width: 760px) {
        body.single-review .lunara-review-share-strip {
            grid-template-columns: minmax(0, 1fr) !important;
            align-items: stretch !important;
            padding: 14px !important;
            border-radius: 12px !important;
        }

        body.single-review .lunara-review-share-strip-actions {
            justify-content: flex-start !important;
        }

        body.single-review .lunara-review-share-link {
            flex: 1 1 calc(50% - 8px) !important;
            min-width: 118px !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_share_strip_css', 1007 );

/**
 * Single Review owned share strip behavior.
 */
function lunara_output_review_share_strip_script() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <script id="lunara-review-share-strip-js">
    (function() {
        var buttons = document.querySelectorAll('[data-lunara-copy-share]');
        if (!buttons.length) {
            return;
        }

        buttons.forEach(function(button) {
            button.addEventListener('click', function() {
                var url = button.getAttribute('data-share-url') || window.location.href;
                var strip = button.closest('.lunara-review-share-strip');
                var status = strip ? strip.querySelector('.lunara-review-share-status') : null;
                var setStatus = function(message) {
                    if (status) {
                        status.textContent = message;
                    }
                };
                var markCopied = function() {
                    button.classList.add('is-copied');
                    button.textContent = 'Copied';
                    setStatus('Link copied.');
                    window.setTimeout(function() {
                        button.classList.remove('is-copied');
                        button.textContent = 'Copy Link';
                        setStatus('');
                    }, 1800);
                };
                var fallbackCopy = function() {
                    var input = document.createElement('textarea');
                    input.value = url;
                    input.setAttribute('readonly', 'readonly');
                    input.style.position = 'fixed';
                    input.style.left = '-9999px';
                    document.body.appendChild(input);
                    input.select();
                    try {
                        document.execCommand('copy');
                        markCopied();
                    } catch (error) {
                        setStatus('Copy failed.');
                    }
                    document.body.removeChild(input);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(markCopied).catch(fallbackCopy);
                } else {
                    fallbackCopy();
                }
            });
        });
    }());
    </script>
    <?php
}
add_action( 'wp_footer', 'lunara_output_review_share_strip_script', 100 );

/**
 * Single Review lower retention shelf polish.
 */
function lunara_output_review_related_retention_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-related-retention-css">
    body.single-review .lunara-review-related--retention {
        box-sizing: border-box !important;
        display: grid !important;
        gap: clamp(16px, 2.2vw, 24px) !important;
        width: min(1120px, calc(100vw - 64px)) !important;
        max-width: 100% !important;
        margin: clamp(34px, 5vw, 58px) auto 0 !important;
        padding: clamp(18px, 2.8vw, 28px) !important;
        border: 1px solid rgba(224, 196, 129, 0.22) !important;
        border-radius: 16px !important;
        background:
            radial-gradient(circle at 88% 10%, rgba(224, 196, 129, 0.12), transparent 31%),
            linear-gradient(135deg, rgba(11, 26, 42, 0.98), rgba(5, 15, 26, 0.99) 64%, rgba(13, 29, 45, 0.96)) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.035) !important;
        overflow: hidden !important;
    }

    body.single-review .lunara-review-related--retention::before {
        display: none !important;
    }

    body.single-review .lunara-review-related--retention .lunara-home-section-head {
        display: flex !important;
        align-items: end !important;
        justify-content: space-between !important;
        gap: 18px !important;
        margin: 0 !important;
        padding: 0 0 14px !important;
        border-bottom: 1px solid rgba(224, 196, 129, 0.18) !important;
    }

    body.single-review .lunara-review-related--retention .lunara-home-section-kicker {
        margin: 0 0 6px !important;
        color: rgba(224, 196, 129, 0.92) !important;
        font-size: 0.68rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.14em !important;
        line-height: 1.15 !important;
        text-transform: uppercase !important;
    }

    body.single-review .lunara-review-related--retention .lunara-section-title {
        margin: 0 !important;
        color: rgba(248, 244, 234, 0.98) !important;
        font-size: clamp(1.35rem, 2.4vw, 2rem) !important;
        line-height: 1.04 !important;
        text-wrap: balance !important;
    }

    body.single-review .lunara-review-related--retention .lunara-section-link {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 36px !important;
        padding: 9px 13px !important;
        border: 1px solid rgba(224, 196, 129, 0.26) !important;
        border-radius: 999px !important;
        color: rgba(224, 196, 129, 0.96) !important;
        font-size: 0.74rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.1em !important;
        line-height: 1.1 !important;
        text-decoration: none !important;
        text-transform: uppercase !important;
        white-space: nowrap !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-related-grid {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: clamp(12px, 1.7vw, 18px) !important;
        margin: 0 !important;
        min-width: 0 !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-card {
        width: 100% !important;
        min-width: 0 !important;
        min-height: 0 !important;
        border: 1px solid rgba(224, 196, 129, 0.18) !important;
        border-radius: 14px !important;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.052), rgba(255, 255, 255, 0.018)),
            rgba(7, 18, 30, 0.84) !important;
        box-shadow: none !important;
        overflow: hidden !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-link {
        display: grid !important;
        grid-template-columns: minmax(110px, 136px) minmax(0, 1fr) !important;
        align-items: stretch !important;
        min-height: 188px !important;
        color: inherit !important;
        text-decoration: none !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-poster-wrap {
        width: 100% !important;
        min-width: 0 !important;
        height: 100% !important;
        min-height: 188px !important;
        max-height: none !important;
        aspect-ratio: auto !important;
        border-radius: 0 !important;
        background: rgba(255, 255, 255, 0.035) !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-poster,
    body.single-review .lunara-review-related--retention .lunara-review-grid-poster-wrap img {
        display: block !important;
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-copy,
    body.single-review .lunara-review-related--retention .lunara-review-grid-card > .lunara-review-grid-copy {
        display: grid !important;
        align-content: center !important;
        gap: 8px !important;
        min-width: 0 !important;
        padding: clamp(14px, 1.8vw, 20px) !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-kicker {
        margin: 0 !important;
        color: rgba(224, 196, 129, 0.86) !important;
        font-size: 0.66rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.12em !important;
        line-height: 1.1 !important;
        text-transform: uppercase !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-title {
        margin: 0 !important;
        color: rgba(248, 244, 234, 0.98) !important;
        font-size: clamp(1.02rem, 1.35vw, 1.24rem) !important;
        line-height: 1.12 !important;
        text-wrap: balance !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-excerpt {
        display: -webkit-box !important;
        margin: 0 !important;
        max-width: 48ch !important;
        overflow: hidden !important;
        -webkit-box-orient: vertical !important;
        -webkit-line-clamp: 3 !important;
        color: rgba(244, 239, 227, 0.76) !important;
        font-size: 0.88rem !important;
        line-height: 1.45 !important;
        text-wrap: pretty !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-updated,
    body.single-review .lunara-review-related--retention .lunara-review-grid-meta {
        margin: 0 !important;
        color: rgba(244, 239, 227, 0.62) !important;
        font-size: 0.78rem !important;
        line-height: 1.35 !important;
    }

    body.single-review .lunara-review-related--retention .lunara-review-grid-footer,
    body.single-review .lunara-review-related--retention .lunara-review-grid-ledger {
        display: none !important;
    }

    @media (max-width: 820px) {
        body.single-review .lunara-review-related--retention {
            width: min(100%, calc(100vw - 36px)) !important;
            margin-top: 30px !important;
            padding: 15px !important;
            border-radius: 12px !important;
        }

        body.single-review .lunara-review-related--retention .lunara-home-section-head {
            align-items: start !important;
            gap: 12px !important;
            padding-bottom: 12px !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-related-grid {
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 10px !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-grid-link {
            grid-template-columns: 88px minmax(0, 1fr) !important;
            min-height: 128px !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-grid-poster-wrap {
            min-height: 128px !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-grid-copy,
        body.single-review .lunara-review-related--retention .lunara-review-grid-card > .lunara-review-grid-copy {
            align-content: center !important;
            gap: 6px !important;
            padding: 11px 12px !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-grid-title {
            font-size: 0.98rem !important;
            line-height: 1.15 !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-grid-excerpt {
            display: none !important;
        }
    }

    @media (max-width: 430px) {
        body.single-review .lunara-review-related--retention .lunara-home-section-head {
            display: grid !important;
        }

        body.single-review .lunara-review-related--retention .lunara-section-link {
            justify-self: start !important;
            min-height: 34px !important;
            padding: 8px 11px !important;
            font-size: 0.68rem !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-grid-link {
            grid-template-columns: 82px minmax(0, 1fr) !important;
            min-height: 122px !important;
        }

        body.single-review .lunara-review-related--retention .lunara-review-grid-poster-wrap {
            min-height: 122px !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_related_retention_css', 1006 );

/**
 * Single Review full-scroll rhythm guardrails.
 */
function lunara_output_review_full_scroll_rhythm_css() {
    if ( is_admin() || is_feed() || ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-full-scroll-rhythm-css">
    body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
        grid-template-columns: minmax(240px, 340px) minmax(0, 1fr) !important;
    }

    body.single-review .lunara-review-single-debrief-media {
        align-content: center !important;
        justify-items: center !important;
        text-align: center !important;
    }

    body.single-review .lunara-review-single-debrief-poster-shell {
        position: relative !important;
        width: min(100%, 320px) !important;
        max-width: 320px !important;
        margin-inline: auto !important;
        border-color: rgba(244, 210, 126, 0.34) !important;
        background:
            radial-gradient(circle at 50% 18%, rgba(244, 210, 126, 0.13), transparent 45%),
            rgba(255, 255, 255, 0.05) !important;
        box-shadow:
            0 28px 56px rgba(0, 0, 0, 0.42),
            0 0 0 1px rgba(255, 255, 255, 0.045) inset !important;
    }

    body.single-review .lunara-review-single-debrief-poster {
        filter: brightness(1.06) contrast(1.08) saturate(1.08) !important;
        object-position: center center !important;
    }

    body.single-review .lunara-review-single-debrief-media-copy {
        width: 100% !important;
        max-width: 30ch !important;
        margin-inline: auto !important;
        text-align: center !important;
    }

    @media (min-width: 1040px) {
        body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
            grid-template-columns: minmax(260px, 360px) minmax(0, 1fr) !important;
        }
    }

    @media (min-width: 521px) and (max-width: 900px) {
        body.single-review .lunara-review-single-debrief-poster-shell {
            width: min(42vw, 240px) !important;
            max-width: 240px !important;
        }
    }

    @media (max-width: 520px) {
        body.single-review .lunara-review-single-debrief-wrap.has-signature-media {
            grid-template-columns: minmax(0, 1fr) !important;
            align-items: start !important;
            gap: 16px !important;
        }

        body.single-review .lunara-review-single-debrief-media,
        body.single-review .lunara-review-single-debrief-wrap.has-signature-media > .lunara-review-single-debrief {
            grid-column: 1 / -1 !important;
            grid-row: auto !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }

        body.single-review .lunara-review-single-debrief-media {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            justify-items: center !important;
            text-align: center !important;
        }

        body.single-review .lunara-review-single-debrief-poster-shell {
            width: min(72vw, 210px) !important;
            max-width: 210px !important;
            min-width: 0 !important;
            margin-inline: auto !important;
        }

        body.single-review .lunara-review-single-debrief-media-copy {
            width: 100% !important;
            max-width: 28ch !important;
            margin-inline: auto !important;
            text-align: center !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_full_scroll_rhythm_css', 1007 );

/**
 * Compact Oscars portal guardrails.
 *
 * The /oscars/ front door inherits several homepage-scale components; keep this
 * page-specific layer late so the portal reads as an efficient ledger entry
 * point on mobile and desktop.
 */
function lunara_output_oscars_portal_compact_css() {
    if ( is_admin() || is_feed() || ! is_page( 'oscars' ) ) {
        return;
    }
    ?>
    <style id="lunara-oscars-portal-compact-css">
    body.lunara-oscars-portal-page .site-main {
        overflow-x: clip !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal {
        gap: clamp(34px, 4.8vw, 58px) !important;
        margin-inline: auto !important;
        max-width: min(100%, 1180px) !important;
        padding: clamp(14px, 2.4vw, 24px) clamp(18px, 3vw, 30px) clamp(48px, 6vw, 76px) !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal > .lunara-home-section {
        border-radius: clamp(18px, 2.4vw, 26px) !important;
        box-sizing: border-box !important;
        max-width: 100% !important;
        min-width: 0 !important;
        overflow: hidden !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-hero {
        box-shadow: 0 22px 46px rgba(0, 0, 0, .22) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid {
        align-items: center !important;
        gap: clamp(20px, 3vw, 34px) !important;
        grid-template-columns: minmax(0, 1.25fr) minmax(240px, 330px) !important;
        padding: clamp(16px, 2.2vw, 24px) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-copy {
        gap: clamp(13px, 1.6vw, 19px) !important;
        padding: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-section-kicker,
    body.lunara-oscars-portal-page .lunara-home-section-kicker {
        font-size: .7rem !important;
        letter-spacing: .13em !important;
        line-height: 1.2 !important;
        margin: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
        font-size: clamp(2.05rem, 4.4vw, 4rem) !important;
        letter-spacing: 0 !important;
        line-height: .98 !important;
        margin: 0 !important;
        max-width: 12.6ch !important;
        overflow-wrap: normal !important;
        text-wrap: balance !important;
        text-transform: none !important;
        word-break: normal !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-copy {
        color: rgba(244, 239, 227, .82) !important;
        font-size: clamp(.96rem, 1.14vw, 1.06rem) !important;
        line-height: 1.58 !important;
        margin: 0 !important;
        max-width: 66ch !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-actions {
        gap: 9px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-actions a {
        border-radius: 999px !important;
        font-size: .82rem !important;
        line-height: 1.15 !important;
        min-height: 38px !important;
        padding: 10px 13px !important;
        white-space: normal !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-stat-grid {
        gap: 10px !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-stat {
        border-radius: 15px !important;
        gap: 5px !important;
        min-width: 0 !important;
        padding: 12px 12px 11px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-stat-label {
        font-size: .62rem !important;
        letter-spacing: .11em !important;
        line-height: 1.15 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-stat-value {
        font-size: .9rem !important;
        line-height: 1.2 !important;
        overflow-wrap: anywhere !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-command-rail {
        align-items: stretch !important;
        display: grid !important;
        gap: 10px !important;
        grid-column: 1 / -1 !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        margin-top: clamp(4px, 1vw, 10px) !important;
        min-width: 0 !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-command-card {
        background:
            linear-gradient(145deg, rgba(201, 169, 97, .11), rgba(13, 27, 42, .9)),
            rgba(9, 20, 32, .9) !important;
        border: 1px solid rgba(201, 169, 97, .36) !important;
        border-radius: 16px !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .04) !important;
        color: rgba(248, 244, 234, .92) !important;
        display: grid !important;
        gap: 7px !important;
        min-height: 116px !important;
        min-width: 0 !important;
        padding: 15px !important;
        position: relative !important;
        text-decoration: none !important;
        transition: border-color .18s ease, transform .18s ease, background .18s ease !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-command-card:hover {
        background:
            linear-gradient(145deg, rgba(201, 169, 97, .18), rgba(13, 27, 42, .94)),
            rgba(9, 20, 32, .94) !important;
        border-color: rgba(225, 197, 126, .66) !important;
        transform: translateY(-2px) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-command-kicker,
    body.lunara-oscars-portal-page .lunara-oscars-command-meta {
        display: block !important;
        font-size: .62rem !important;
        letter-spacing: .11em !important;
        line-height: 1.2 !important;
        min-width: 0 !important;
        overflow-wrap: anywhere !important;
        text-transform: uppercase !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-command-kicker {
        color: var(--lunara-gold, #d4af66) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-command-card strong {
        color: #fffaf0 !important;
        display: block !important;
        font-family: var(--lunara-serif, Georgia, serif) !important;
        font-size: clamp(1rem, 1.35vw, 1.24rem) !important;
        letter-spacing: 0 !important;
        line-height: 1.08 !important;
        min-width: 0 !important;
        overflow-wrap: anywhere !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-command-meta {
        align-self: end !important;
        color: rgba(244, 239, 227, .64) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
        gap: 14px !important;
        justify-self: end !important;
        max-width: 330px !important;
        padding: 16px !important;
        width: 100% !important;
        border-radius: 22px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
        border-radius: 16px !important;
        max-height: 350px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy {
        gap: 7px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy h2 {
        font-size: clamp(1.05rem, 1.7vw, 1.38rem) !important;
        line-height: 1.08 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-body {
        font-size: .88rem !important;
        line-height: 1.45 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlights,
    body.lunara-oscars-portal-page .lunara-oscars-portal-titles,
    body.lunara-oscars-portal-page .lunara-oscars-portal-research,
    body.lunara-oscars-portal-page .lunara-oscars-portal-winners,
    body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section,
    body.lunara-oscars-portal-page .lunara-oscars-portal-deep-cuts {
        background:
            linear-gradient(135deg, rgba(201, 169, 97, .055), rgba(10, 22, 35, .96) 38%, rgba(7, 17, 29, .98)),
            rgba(8, 18, 30, .94) !important;
        border: 1px solid rgba(201, 169, 97, .16) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .035) !important;
        padding: clamp(20px, 3vw, 32px) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlights .lunara-home-section-header,
    body.lunara-oscars-portal-page .lunara-oscars-portal-titles .lunara-home-section-header,
    body.lunara-oscars-portal-page .lunara-oscars-portal-research .lunara-home-section-header,
    body.lunara-oscars-portal-page .lunara-oscars-portal-winners .lunara-home-section-header,
    body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section .lunara-home-section-header,
    body.lunara-oscars-portal-page .lunara-oscars-portal-deep-cuts .lunara-home-section-header {
        border-bottom: 1px solid rgba(201, 169, 97, .14) !important;
        margin-bottom: 18px !important;
        padding-bottom: 14px !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winners-grid {
        gap: 12px !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-card {
        align-items: center !important;
        background: rgba(9, 20, 32, .84) !important;
        border: 1px solid rgba(201, 169, 97, .18) !important;
        border-radius: 16px !important;
        box-shadow: none !important;
        display: grid !important;
        gap: 12px !important;
        grid-template-columns: minmax(58px, 76px) minmax(0, 1fr) !important;
        min-height: 96px !important;
        min-width: 0 !important;
        overflow: hidden !important;
        padding: 12px !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-card:hover {
        border-color: rgba(225, 197, 126, .42) !important;
        box-shadow: none !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-media-link,
    body.lunara-oscars-portal-page .lunara-ceremony-winner-poster {
        aspect-ratio: 1 / 1 !important;
        border-radius: 12px !important;
        display: block !important;
        max-width: 76px !important;
        min-width: 0 !important;
        overflow: hidden !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-poster img {
        height: 100% !important;
        object-fit: cover !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-copy {
        display: grid !important;
        gap: 5px !important;
        min-width: 0 !important;
        padding: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-category {
        font-size: .58rem !important;
        letter-spacing: .12em !important;
        line-height: 1.2 !important;
        margin: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-name {
        font-size: .94rem !important;
        line-height: 1.14 !important;
        margin: 0 !important;
        overflow-wrap: anywhere !important;
    }

    body.lunara-oscars-portal-page .lunara-ceremony-winner-film {
        font-size: .76rem !important;
        line-height: 1.25 !important;
        margin: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-title-card {
        align-items: center !important;
        background: rgba(9, 20, 32, .86) !important;
        display: grid !important;
        gap: 12px !important;
        grid-template-columns: minmax(52px, 72px) minmax(0, 1fr) !important;
        min-height: 100px !important;
        overflow: hidden !important;
        padding: 10px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-title-media {
        aspect-ratio: 2 / 3 !important;
        border-radius: 12px !important;
        min-height: 0 !important;
        overflow: hidden !important;
        width: 100% !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-title-copy {
        padding: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-research-card-grid {
        border: 1px solid rgba(201, 169, 97, .14) !important;
        border-radius: 18px 18px 0 0 !important;
        gap: 0 !important;
        overflow: hidden !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-research-card {
        border: 0 !important;
        border-radius: 0 !important;
        border-right: 1px solid rgba(201, 169, 97, .13) !important;
        min-height: 132px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-research-card:last-child {
        border-right: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-research-shell {
        border: 1px solid rgba(201, 169, 97, .24) !important;
        border-radius: 0 0 18px 18px !important;
        border-top: 0 !important;
        overflow: hidden !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-hub-header,
    body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-hub-section,
    body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-hub-metric-card,
    body.lunara-oscars-portal-page .lunara-oscars-research-shell .aat-hub-card {
        border-color: rgba(201, 169, 97, .18) !important;
    }

    body.lunara-oscars-portal-page .lunara-ledger-carousel-wrap {
        border: 1px solid rgba(201, 169, 97, .14) !important;
        border-radius: 18px !important;
        overflow: hidden !important;
        padding: 12px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-winner-carousel-track {
        gap: 12px !important;
        grid-auto-columns: minmax(260px, 31%) !important;
    }

    body.lunara-oscars-portal-page .lunara-home-section-header {
        align-items: end !important;
        gap: 18px !important;
        margin-bottom: 18px !important;
        padding-left: 4px !important;
    }

    body.lunara-oscars-portal-page .lunara-home-section-title {
        font-size: clamp(1.55rem, 2.5vw, 2.25rem) !important;
        line-height: 1.04 !important;
        margin: 0 !important;
        max-width: 16ch !important;
        padding-left: 2px !important;
        text-wrap: balance !important;
    }

    body.lunara-oscars-portal-page .lunara-home-section-summary {
        font-size: .96rem !important;
        line-height: 1.55 !important;
        margin: 0 !important;
        max-width: 58ch !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid,
    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
    body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid,
    body.lunara-oscars-portal-page .lunara-oscars-research-card-grid {
        gap: 16px !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid {
        gap: 16px !important;
        grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card,
    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-card,
    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card,
    body.lunara-oscars-portal-page .lunara-oscars-research-card {
        border-radius: 18px !important;
        gap: 10px !important;
        min-width: 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card,
    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card,
    body.lunara-oscars-portal-page .lunara-oscars-research-card {
        padding: 16px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card.has-backdrop {
        min-height: 150px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-spotlight-card-copy,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-copy {
        gap: 6px !important;
        padding: 12px 13px 14px !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card h3,
    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card h3,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-card h3 {
        font-size: clamp(.96rem, 1.25vw, 1.08rem) !important;
        line-height: 1.16 !important;
        overflow-wrap: anywhere !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card p,
    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-secondary,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-line,
    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-context {
        font-size: .86rem !important;
        line-height: 1.42 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-kicker,
    body.lunara-oscars-portal-page .lunara-oscars-portal-link-kicker,
    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-category,
    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-label,
    body.lunara-oscars-portal-page .lunara-oscars-research-card-kicker {
        font-size: .64rem !important;
        letter-spacing: .11em !important;
        line-height: 1.2 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-value {
        font-size: clamp(1.25rem, 2.1vw, 1.55rem) !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-spotlight-poster,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-media {
        border-radius: 14px 14px 0 0 !important;
    }

    body.lunara-oscars-portal-page .lunara-oscars-portal-link-card:hover,
    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card:hover,
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-card:hover,
    body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card:hover,
    body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card:hover {
        transform: translateY(-2px) !important;
    }

    @media (max-width: 1120px) {
        body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid {
            grid-template-columns: minmax(0, 1fr) minmax(220px, 280px) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
            max-width: 13.4ch !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid,
        body.lunara-oscars-portal-page .lunara-oscars-research-card-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-ceremony-winners-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-winner-carousel-track {
            grid-auto-columns: minmax(240px, 44%) !important;
        }
    }

    @media (max-width: 820px) {
        body.lunara-oscars-portal-page .lunara-oscars-portal {
            gap: 34px !important;
            padding: 12px 14px 52px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid {
            gap: 16px !important;
            grid-template-columns: minmax(0, 1fr) !important;
            padding: 14px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
            font-size: clamp(2rem, 7vw, 3rem) !important;
            line-height: 1.02 !important;
            max-width: 13ch !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-copy {
            font-size: .96rem !important;
            line-height: 1.5 !important;
            max-width: 64ch !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-actions {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-actions a {
            min-height: 42px !important;
            padding: 9px 10px !important;
            text-align: center !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-command-rail {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
            display: grid !important;
            gap: 12px !important;
            grid-template-columns: minmax(120px, 31%) minmax(0, 1fr) !important;
            justify-self: stretch !important;
            max-width: none !important;
            padding: 12px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
            align-self: start !important;
            max-height: none !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy {
            align-content: center !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-copy h2 {
            font-size: clamp(1.05rem, 3.6vw, 1.3rem) !important;
        }

        body.lunara-oscars-portal-page .lunara-home-section-header {
            align-items: start !important;
            display: grid !important;
            gap: 10px !important;
            margin-bottom: 14px !important;
            padding-left: 6px !important;
        }

        body.lunara-oscars-portal-page .lunara-home-section-title {
            font-size: clamp(1.45rem, 5.4vw, 2.05rem) !important;
            max-width: 18ch !important;
            padding-left: 2px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid,
        body.lunara-oscars-portal-page .lunara-oscars-portal-facts-grid,
        body.lunara-oscars-portal-page .lunara-oscars-research-card-grid {
            gap: 12px !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-card,
        body.lunara-oscars-portal-page .lunara-oscars-research-card {
            padding: 13px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-card.has-backdrop {
            min-height: 132px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlights,
        body.lunara-oscars-portal-page .lunara-oscars-portal-titles,
        body.lunara-oscars-portal-page .lunara-oscars-portal-research,
        body.lunara-oscars-portal-page .lunara-oscars-portal-winners,
        body.lunara-oscars-portal-page .lunara-oscars-rotating-winners-section,
        body.lunara-oscars-portal-page .lunara-oscars-portal-deep-cuts {
            padding: 16px 14px !important;
        }

        body.lunara-oscars-portal-page .lunara-ceremony-winners-grid {
            gap: 10px !important;
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-card-grid {
            border-radius: 16px !important;
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-card {
            border-right: 0 !important;
            border-bottom: 1px solid rgba(201, 169, 97, .13) !important;
            min-height: 98px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-card:last-child {
            border-bottom: 0 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-research-shell {
            border-radius: 16px !important;
            border-top: 1px solid rgba(201, 169, 97, .24) !important;
            margin-top: 12px !important;
        }
    }

    @media (max-width: 520px) {
        body.lunara-oscars-portal-page .lunara-oscars-portal {
            padding-inline: 12px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid {
            padding: 12px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-title {
            font-size: clamp(1.68rem, 8.1vw, 2.08rem) !important;
            line-height: 1.05 !important;
            max-width: 11.8ch !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy .lunara-home-hero-copy {
            font-size: .9rem !important;
            line-height: 1.44 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-actions {
            gap: 8px !important;
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-actions a {
            font-size: .78rem !important;
            min-height: 39px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-stat-grid {
            gap: 8px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-stat {
            border-radius: 12px !important;
            padding: 10px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-stat-value {
            font-size: .82rem !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-command-rail {
            gap: 8px !important;
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-command-card {
            align-items: center !important;
            gap: 5px !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            min-height: 74px !important;
            padding: 12px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-command-kicker,
        body.lunara-oscars-portal-page .lunara-oscars-command-meta {
            font-size: .58rem !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-command-card strong {
            font-size: 1rem !important;
            grid-column: 1 / -1 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
            grid-template-columns: minmax(104px, 36vw) minmax(0, 1fr) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-body {
            display: none !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-card h3,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card h3,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-card h3 {
            font-size: .9rem !important;
        }

        body.lunara-oscars-portal-page .lunara-home-section-title {
            max-width: 11.8ch !important;
        }

        body.lunara-oscars-portal-page .lunara-ceremony-winner-card {
            grid-template-columns: minmax(58px, 68px) minmax(0, 1fr) !important;
            min-height: 88px !important;
            padding: 10px !important;
        }

        body.lunara-oscars-portal-page .lunara-ceremony-winner-media-link,
        body.lunara-oscars-portal-page .lunara-ceremony-winner-poster {
            max-width: 68px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-title-grid {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-title-card {
            grid-template-columns: minmax(48px, 64px) minmax(0, 1fr) !important;
            min-height: 86px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-winner-carousel-track {
            grid-auto-columns: minmax(238px, 88%) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-link-card p,
        body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-secondary,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-line,
        body.lunara-oscars-portal-page .lunara-oscars-portal-fact-context {
            font-size: .78rem !important;
            line-height: 1.34 !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-spotlight-card-copy,
        body.lunara-oscars-portal-page .lunara-oscars-portal-title-copy {
            padding: 10px !important;
        }
    }

    @media (max-width: 900px) {
        body.lunara-oscars-portal-page,
        body.lunara-oscars-portal-page .site,
        body.lunara-oscars-portal-page .site-main {
            max-width: 100vw !important;
            overflow-x: clip !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal {
            box-sizing: border-box !important;
            margin-left: auto !important;
            margin-right: auto !important;
            max-width: 100vw !important;
            overflow-x: clip !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal > .lunara-home-section,
        body.lunara-oscars-portal-page .lunara-oscars-portal-hero {
            box-sizing: border-box !important;
            min-height: auto !important;
            max-width: 100% !important;
            overflow: hidden !important;
            padding: clamp(16px, 4vw, 24px) !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-hero-grid {
            box-sizing: border-box !important;
            display: grid !important;
            gap: 16px !important;
            grid-template-columns: minmax(0, 1fr) !important;
            max-width: 100% !important;
            min-height: 0 !important;
            min-width: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-copy,
        body.lunara-oscars-portal-page .lunara-oscars-command-rail,
        body.lunara-oscars-portal-page .lunara-oscars-command-card,
        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
            box-sizing: border-box !important;
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
        body.lunara-oscars-portal-page .lunara-oscars-portal {
            max-width: 100vw !important;
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal > .lunara-home-section,
        body.lunara-oscars-portal-page .lunara-oscars-portal-hero {
            border-radius: 18px !important;
            max-width: 100% !important;
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-card {
            grid-template-columns: minmax(84px, 102px) minmax(0, 1fr) !important;
        }

        body.lunara-oscars-portal-page .lunara-oscars-portal-feature-poster {
            max-height: 154px !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_oscars_portal_compact_css', 1001 );

/**
 * Final mobile-only Oscars guardrails after plugin-injected head styles.
 */
function lunara_output_os_late_oscars_mobile_guardrail_css() {
    if ( is_admin() || is_feed() ) {
        return;
    }
    ?>
    <style id="lunara-os-late-oscars-mobile-guardrails">
    @media (max-width: 820px) {
        body.aat-shell-page .aat-container,
        body.aat-shell-page .aat-hub-page,
        body.aat-shell-page .aat-entity-page {
            margin-left: auto !important;
            margin-right: auto !important;
            max-width: calc(100vw - 20px) !important;
            min-width: 0 !important;
            width: calc(100vw - 20px) !important;
        }

        body.aat-shell-page .aat-hub-header,
        body.aat-shell-page .aat-entity-header,
        body.aat-shell-page .aat-ceremony-marquee,
        body.aat-shell-page .aat-category-latest-winner,
        body.aat-shell-page .aat-hub-grid,
        body.aat-shell-page .aat-winner-circle-grid,
        body.aat-shell-page .aat-hub-metric-grid,
        body.aat-shell-page .aat-crossroads-grid,
        body.aat-shell-page .aat-crossroads-list {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }

        body.aat-shell-page .aat-hub-section h2,
        body.aat-shell-page .aat-section-title,
        body.aat-shell-page .aat-hub-spotlight-title,
        body.aat-shell-page .aat-winner-circle-title,
        body.aat-shell-page .aat-hub-metric-value,
        body.aat-shell-page .aat-hub-card-title,
        body.aat-shell-page .aat-hub-copy,
        body.aat-shell-page .aat-section-description,
        body.aat-shell-page .aat-hub-inline-link,
        body.aat-shell-page .aat-hub-inline-link-title,
        body.aat-shell-page .aat-entity-link {
            max-width: 100% !important;
            min-width: 0 !important;
            overflow-wrap: anywhere !important;
            white-space: normal !important;
            word-break: normal !important;
        }

        body.aat-shell-page .aat-category-latest-winner,
        body.aat-shell-page .aat-ceremony-marquee {
            padding-left: clamp(18px, 6vw, 28px) !important;
            padding-right: clamp(18px, 6vw, 28px) !important;
        }

        body.aat-shell-page .aat-category-latest-winner h2,
        body.aat-shell-page .aat-ceremony-marquee h2 {
            font-size: clamp(1.6rem, 8vw, 2.2rem) !important;
            line-height: 1.1 !important;
        }

        body.aat-shell-page .aat-hub-spotlight-card {
            align-items: center !important;
            display: grid !important;
            gap: 14px !important;
            grid-template-columns: minmax(88px, 110px) minmax(0, 1fr) !important;
            max-width: 100% !important;
            min-width: 0 !important;
            padding: 12px !important;
        }

        body.aat-shell-page .aat-hub-spotlight-body,
        body.aat-shell-page .aat-hub-spotlight-media-link,
        body.aat-shell-page .aat-hub-spotlight-media {
            max-width: 100% !important;
            min-width: 0 !important;
        }

        body.aat-shell-page .aat-stats-bar.aat-entity-stats {
            display: grid !important;
            gap: 10px !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.aat-shell-page .aat-stats-bar.aat-entity-stats .aat-stat {
            align-content: start !important;
            display: grid !important;
            gap: 4px !important;
            min-width: 0 !important;
            text-align: left !important;
        }

        body.aat-shell-page .aat-decade-nav {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 10px !important;
            overflow: visible !important;
        }

        body.aat-shell-page .aat-decade-pill {
            display: inline-flex !important;
            gap: 8px !important;
            justify-content: space-between !important;
            min-height: 40px !important;
            min-width: 92px !important;
            white-space: normal !important;
        }

        body.aat-shell-page .aat-decade-pill-count {
            align-items: center !important;
            background: rgba(201,169,97,.22) !important;
            border-radius: 999px !important;
            display: inline-flex !important;
            justify-content: center !important;
            min-width: 24px !important;
            padding: 3px 7px !important;
        }

        body.aat-shell-page .aat-crossroad-pill {
            align-items: start !important;
            display: grid !important;
            gap: 3px !important;
            grid-template-columns: minmax(0, 1fr) !important;
            line-height: 1.22 !important;
            min-height: 44px !important;
            min-width: 0 !important;
            padding: 10px 12px !important;
            white-space: normal !important;
        }

        body.aat-shell-page .aat-crossroad-pill-title,
        body.aat-shell-page .aat-crossroad-pill-meta {
            display: block !important;
            max-width: 100% !important;
            min-width: 0 !important;
            overflow-wrap: anywhere !important;
        }
    }

    @media (max-width: 520px) {
        body.aat-shell-page .aat-hub-spotlight-card {
            max-width: calc(100vw - 76px) !important;
            grid-template-columns: minmax(0, 1fr) !important;
        }

        body.aat-shell-page .aat-hub-chip-rich,
        body.aat-shell-page .aat-winner-circle-card,
        body.aat-shell-page .aat-hub-metric-card,
        body.aat-shell-page .aat-category-ceremony-row,
        body.aat-shell-page .aat-category-history-winner {
            max-width: calc(100vw - 76px) !important;
            min-width: 0 !important;
        }

        body.aat-shell-page .aat-ceremony-marquee-copy,
        body.aat-shell-page .aat-hub-copy,
        body.aat-shell-page .aat-section-description {
            max-width: calc(100vw - 76px) !important;
        }

        body.aat-shell-page .aat-hub-spotlight-media-link,
        body.aat-shell-page .aat-hub-spotlight-media {
            max-width: 148px !important;
            width: 100% !important;
        }

        body.aat-shell-page .aat-entity-poster-wrap {
            justify-self: start !important;
            max-width: 146px !important;
            width: 100% !important;
        }

        body.aat-shell-page .aat-entity-poster-wrap img,
        body.aat-shell-page .aat-entity-poster {
            max-height: 220px !important;
        }

        body.aat-shell-page .aat-winner-circle-media,
        body.aat-shell-page .aat-winner-circle-card.is-hero-latest .aat-winner-circle-media,
        body.aat-shell-page .aat-winner-circle-card.is-marquee-latest .aat-winner-circle-media {
            aspect-ratio: 2 / 3 !important;
            margin-left: auto !important;
            margin-right: auto !important;
            max-width: 132px !important;
            min-height: 0 !important;
            width: 132px !important;
        }

        body.aat-shell-page .aat-stats-bar.aat-entity-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        body.aat-shell-page .aat-hub-chip-rich,
        body.aat-shell-page .aat-winner-circle-action {
            justify-content: center !important;
            width: 100% !important;
        }

        body.aat-shell-page .aat-winner-circle-action {
            font-size: .72rem !important;
        }

        body.aat-shell-page .aat-category-latest-winner .aat-hub-spotlight-card,
        body.aat-shell-page .aat-ceremony-marquee .aat-hub-spotlight-card {
            align-items: center !important;
            display: grid !important;
            grid-template-columns: minmax(84px, 104px) minmax(0, 1fr) !important;
            max-width: 100% !important;
            padding: 10px !important;
        }

        body.aat-shell-page .aat-category-latest-winner .aat-hub-spotlight-media-link,
        body.aat-shell-page .aat-category-latest-winner .aat-hub-spotlight-media,
        body.aat-shell-page .aat-ceremony-marquee .aat-hub-spotlight-media-link,
        body.aat-shell-page .aat-ceremony-marquee .aat-hub-spotlight-media {
            max-width: 104px !important;
            min-height: 0 !important;
            width: 104px !important;
        }

        body.aat-shell-page .aat-category-latest-winner .aat-hub-spotlight-body,
        body.aat-shell-page .aat-ceremony-marquee .aat-hub-spotlight-body {
            gap: 6px !important;
            padding: 0 0 0 4px !important;
        }

        body.aat-shell-page .aat-category-latest-winner .aat-hub-spotlight-title,
        body.aat-shell-page .aat-ceremony-marquee .aat-hub-spotlight-title {
            font-size: .96rem !important;
            line-height: 1.16 !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_footer', 'lunara_output_os_late_oscars_mobile_guardrail_css', 999 );

/**
 * Hero cinema crossfade: auto-rotating poster hero.
 */
function lunara_output_hero_cinema_js() {
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    <script>
    (function(){
        var stage=document.querySelector('.lunara-hero-cinema-stage');
        if(!stage)return;
        var slides=stage.querySelectorAll('.lunara-hero-cinema-slide');
        var pips=stage.querySelectorAll('.lunara-hero-cinema-pip');
        if(slides.length<2)return;
        var current=0;
        var interval=5500;
        var timer=null;
        var paused=false;
        function goTo(idx){
            slides[current].classList.remove('is-active');
            slides[current].setAttribute('aria-hidden','true');
            if(pips[current])pips[current].classList.remove('is-active');
            current=idx%slides.length;
            slides[current].classList.add('is-active');
            slides[current].setAttribute('aria-hidden','false');
            if(pips[current])pips[current].classList.add('is-active');
        }
        function next(){goTo(current+1);}
        function startAuto(){
            if(timer||paused||window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
            timer=setInterval(next,interval);
        }
        function stopAuto(){if(timer){clearInterval(timer);timer=null;}}
        stage.addEventListener('mouseenter',function(){paused=true;stopAuto();});
        stage.addEventListener('mouseleave',function(){paused=false;startAuto();});
        stage.addEventListener('focusin',function(){paused=true;stopAuto();});
        stage.addEventListener('focusout',function(){paused=false;startAuto();});
        pips.forEach(function(pip){
            pip.addEventListener('click',function(){
                stopAuto();
                goTo(parseInt(pip.getAttribute('data-slide'),10));
                if(!paused)startAuto();
            });
        });
        startAuto();
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'lunara_output_hero_cinema_js', 100 );

/**
 * Lightweight Journal image carousel controls.
 */
function lunara_output_journal_image_carousel_js() {
    if ( ! is_singular( 'journal' ) ) {
        return;
    }
    ?>
    <script>
    (function(){
        var carousels = document.querySelectorAll('[data-lunara-journal-carousel]');
        if (!carousels.length) return;

        carousels.forEach(function(carousel){
            var track = carousel.querySelector('.lunara-journal-image-carousel-track');
            var previous = carousel.querySelector('[data-lunara-carousel-action="prev"]');
            var next = carousel.querySelector('[data-lunara-carousel-action="next"]');
            if (!track || (!previous && !next)) return;

            function slideWidth() {
                var slide = track.querySelector('.lunara-journal-image-carousel-slide');
                if (!slide) return Math.max(280, Math.round(track.clientWidth * 0.86));
                var rect = slide.getBoundingClientRect();
                return Math.max(240, Math.round(rect.width + 14));
            }

            function move(direction) {
                track.scrollBy({ left: slideWidth() * direction, behavior: 'smooth' });
            }

            if (previous) previous.addEventListener('click', function(){ move(-1); });
            if (next) next.addEventListener('click', function(){ move(1); });
        });
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'lunara_output_journal_image_carousel_js', 110 );

/**
 * Review sidebar scroll-follow.
 * Blocksy's #main-container overflow:clip defeats CSS position:sticky.
 * This JS-based approach manually tracks scroll and fixes the sidebar.
 */
function lunara_output_sidebar_scroll_follow_js() {
    if ( ! is_singular( 'review' ) && ! ( is_single() && has_term( '', 'lunara_director' ) ) ) {
        return;
    }

    // Keep review rail panels in normal sticky/static flow; JS fixed-follow can overlap the Debrief section.
    return;
    ?>
    <script>
    (function(){
        var sticky = document.querySelector('.lunara-review-single-rail-sticky');
        var rail   = document.querySelector('.lunara-review-single-rail');
        var bounds = document.querySelector('.lunara-review-single-page article') ||
            document.querySelector('.lunara-review-single-page') ||
            document.querySelector('.lunara-review-single-body-grid');
        if (!sticky || !rail || !bounds) return;

        var mq = window.matchMedia('(max-width: 900px)');
        var topGap = 90;
        var ticking = false;

        function pageTop(el) {
            return el.getBoundingClientRect().top + window.scrollY;
        }

        function resetRail() {
            sticky.style.position = '';
            sticky.style.top = '';
            sticky.style.left = '';
            sticky.style.width = '';
            sticky.style.transform = '';
            rail.style.minHeight = '';
            sticky.classList.remove('is-following', 'is-bottomed');
        }

        /*
         * Use transform: translateY() instead of position:fixed.
         * This keeps the element in normal flow, avoiding Blocksy's
         * overflow:clip and ancestor-transform issues entirely.
         */
        function update() {
            ticking = false;

            if (mq.matches) {
                resetRail();
                return;
            }

            /* Natural (un-translated) top of the sticky element */
            sticky.style.transform = '';               /* reset to measure natural position */
            var stickyNat  = sticky.getBoundingClientRect();
            var boundsRect = bounds.getBoundingClientRect();
            var stickyH    = sticky.offsetHeight;
            var boundsBottom = boundsRect.bottom;

            /* 1. Sidebar top hasn't scrolled past the gap — stay put */
            if (stickyNat.top >= topGap) {
                sticky.classList.remove('is-following', 'is-bottomed');
                return;
            }

            /* How far we need to shift the element down */
            var shift = topGap - stickyNat.top;

            /* 2. Clamp so it doesn't overflow past the review surface bottom */
            var maxShift = boundsBottom - stickyNat.top - stickyH - 24;
            if (maxShift < 0) maxShift = 0;
            if (shift > maxShift) {
                shift = maxShift;
                sticky.classList.remove('is-following');
                sticky.classList.add('is-bottomed');
            } else {
                sticky.classList.add('is-following');
                sticky.classList.remove('is-bottomed');
            }

            sticky.style.transform = 'translateY(' + Math.round(shift) + 'px)';
        }

        function onViewportChange() {
            resetRail();
            if (!mq.matches) {
                requestAnimationFrame(update);
            }
        }

        function onScroll() {
            if (mq.matches) {
                resetRail();
                return;
            }

            if (!ticking) {
                ticking = true;
                requestAnimationFrame(update);
            }
        }

        window.addEventListener('scroll', onScroll, {passive: true});
        window.addEventListener('resize', onViewportChange);

        if (typeof mq.addEventListener === 'function') {
            mq.addEventListener('change', onViewportChange);
        } else if (typeof mq.addListener === 'function') {
            mq.addListener(onViewportChange);
        }

        /* Initial call after layout settles */
        requestAnimationFrame(onViewportChange);
    })();
    </script>
    <script id="lunara-review-reader-rail-follow">
    (function(){
        var rail = document.querySelector('.lunara-review-single-rail');
        var sticky = document.querySelector('.lunara-review-single-rail-sticky');
        var bounds = document.querySelector('.lunara-review-single-page article') ||
            document.querySelector('.lunara-review-single-page') ||
            document.querySelector('.lunara-review-single-body-grid');

        if (!rail || !sticky || !bounds) return;

        var mq = window.matchMedia('(max-width: 900px)');
        var topGap = 90;
        var ticking = false;

        function pageTop(el) {
            return el.getBoundingClientRect().top + window.scrollY;
        }

        function reset() {
            sticky.style.position = '';
            sticky.style.top = '';
            sticky.style.left = '';
            sticky.style.width = '';
            sticky.style.transform = '';
            rail.style.minHeight = '';
            sticky.classList.remove('is-following', 'is-bottomed');
        }

        function update() {
            ticking = false;

            if (mq.matches) {
                reset();
                return;
            }

            reset();

            var railRect = rail.getBoundingClientRect();
            var stickyHeight = sticky.offsetHeight;
            var railStart = pageTop(rail);
            var boundsEnd = pageTop(bounds) + bounds.offsetHeight;
            var desiredTop = window.scrollY + topGap;
            var bottomTop = boundsEnd - stickyHeight - 24;

            rail.style.minHeight = stickyHeight + 'px';

            if (desiredTop <= railStart) {
                return;
            }

            if (desiredTop >= bottomTop) {
                sticky.style.setProperty('position', 'absolute', 'important');
                sticky.style.setProperty('top', Math.max(0, bottomTop - railStart) + 'px', 'important');
                sticky.style.setProperty('left', '0', 'important');
                sticky.style.setProperty('width', railRect.width + 'px', 'important');
                sticky.classList.add('is-bottomed');
                return;
            }

            sticky.style.setProperty('position', 'fixed', 'important');
            sticky.style.setProperty('top', topGap + 'px', 'important');
            sticky.style.setProperty('left', railRect.left + 'px', 'important');
            sticky.style.setProperty('width', railRect.width + 'px', 'important');
            sticky.classList.add('is-following');
        }

        function schedule() {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(update);
            }
        }

        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);

        if (typeof mq.addEventListener === 'function') {
            mq.addEventListener('change', schedule);
        } else if (typeof mq.addListener === 'function') {
            mq.addListener(schedule);
        }

        requestAnimationFrame(update);
        window.setTimeout(update, 500);
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'lunara_output_sidebar_scroll_follow_js', 101 );

/**
 * Keep reviews inside the review lane instead of bleeding into standard post archives.
 */
if ( ! function_exists( 'lunara_separate_review_from_editorial_archives' ) ) {
    function lunara_separate_review_from_editorial_archives( $query ) {
        if ( is_admin() || ! ( $query instanceof WP_Query ) || ! $query->is_main_query() ) {
            return;
        }

        if ( $query->is_search() || $query->is_singular( 'review' ) || $query->is_singular( 'journal' ) ) {
            return;
        }

        if ( $query->is_post_type_archive( 'review' ) ) {
            if ( function_exists( 'lunara_apply_review_archive_sort_args' ) ) {
                $query_vars = array(
                    'orderby' => $query->get( 'orderby' ),
                    'order'   => $query->get( 'order' ),
                );
                $query_vars = lunara_apply_review_archive_sort_args( $query_vars );
                $query->set( 'orderby', $query_vars['orderby'] );
                $query->set( 'order', $query_vars['order'] );
            }
            return;
        }

        if ( $query->is_post_type_archive( 'journal' ) || $query->is_tax( 'journal_type' ) ) {
            if ( function_exists( 'lunara_apply_editorial_archive_sort_args' ) ) {
                $query_vars = array(
                    'orderby' => $query->get( 'orderby' ),
                    'order'   => $query->get( 'order' ),
                );
                $query_vars = lunara_apply_editorial_archive_sort_args( $query_vars );
                $query->set( 'orderby', $query_vars['orderby'] );
                $query->set( 'order', $query_vars['order'] );
            }
            return;
        }

        $requested_post_type = $query->get( 'post_type' );
        if ( 'review' === $requested_post_type || ( is_array( $requested_post_type ) && in_array( 'review', $requested_post_type, true ) ) ) {
            return;
        }

        if ( $query->is_home() || $query->is_category() || $query->is_tag() || $query->is_author() || $query->is_date() ) {
            $query->set( 'post_type', 'post' );

            if ( function_exists( 'lunara_apply_editorial_archive_sort_args' ) ) {
                $query_vars = array(
                    'orderby' => $query->get( 'orderby' ),
                    'order'   => $query->get( 'order' ),
                );
                $query_vars = lunara_apply_editorial_archive_sort_args( $query_vars );
                $query->set( 'orderby', $query_vars['orderby'] );
                $query->set( 'order', $query_vars['order'] );
            }
        }
    }
}
add_action( 'pre_get_posts', 'lunara_separate_review_from_editorial_archives', 12 );

/* ========================================
   NEWS GRID — auto-splits multi-story posts into card grids
   Detects H2/H3 headings as story boundaries.
   ======================================== */

/**
 * Split a standard post's content into separate story cards when it has
 * multiple H2 or H3 headings (a "roundup" or "multi-story" post).
 *
 * Requires at least 2 headings to activate. Single-story posts pass through untouched.
 */
function lunara_news_grid_content_filter( $content ) {
    if ( ! is_singular( 'post' ) || is_admin() ) {
        return $content;
    }

    $post_id = get_the_ID();
    if ( ! $post_id ) {
        return $content;
    }

    // Only apply to news-type posts. Skip if post has the _lunara_disable_news_grid meta.
    if ( '1' === (string) get_post_meta( $post_id, '_lunara_disable_news_grid', true ) ) {
        return $content;
    }

    // Split content on H2 and H3 tags.
    $parts = preg_split( '/(<h[23][^>]*>.*?<\/h[23]>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
    if ( ! is_array( $parts ) || count( $parts ) < 4 ) {
        // Fewer than 2 heading+body pairs — not a multi-story post.
        return $content;
    }

    // Build story cards: each card starts with a heading and collects content until the next heading.
    $cards   = array();
    $current = array( 'heading' => '', 'body' => '' );
    $intro   = '';

    foreach ( $parts as $part ) {
        if ( preg_match( '/^<h[23][^>]*>(.*?)<\/h[23]>$/is', $part, $m ) ) {
            // Save previous card if it has a heading.
            if ( '' !== $current['heading'] ) {
                $cards[] = $current;
            } elseif ( '' !== trim( $current['body'] ) ) {
                // Content before the first heading is an intro.
                $intro = $current['body'];
            }
            $current = array( 'heading' => trim( $m[1] ), 'body' => '' );
        } else {
            $current['body'] .= $part;
        }
    }

    // Save the last card.
    if ( '' !== $current['heading'] ) {
        $cards[] = $current;
    }

    if ( count( $cards ) < 2 ) {
        return $content;
    }

    // Extract the first image from each card's body for the card visual.
    $grid_html = '';

    if ( '' !== trim( $intro ) ) {
        $grid_html .= '<div class="lunara-news-grid-intro">' . $intro . '</div>';
    }

    $grid_html .= '<div class="lunara-news-grid">';

    foreach ( $cards as $card ) {
        $image_html = '';
        $card_body  = $card['body'];

        // Pull the first <img> or <figure> from the card body.
        if ( preg_match( '/<figure[^>]*>.*?<\/figure>/is', $card_body, $fig_match ) ) {
            $image_html = $fig_match[0];
            $card_body  = str_replace( $fig_match[0], '', $card_body );
        } elseif ( preg_match( '/<img[^>]+>/is', $card_body, $img_match ) ) {
            $image_html = $img_match[0];
            $card_body  = str_replace( $img_match[0], '', $card_body );
        }

        // Clean up empty paragraphs left after image extraction.
        $card_body = preg_replace( '/<p>\s*<\/p>/is', '', $card_body );
        $card_body = trim( $card_body );

        $grid_html .= '<article class="lunara-news-grid-card">';

        if ( '' !== $image_html ) {
            $grid_html .= '<div class="lunara-news-grid-card-image">' . $image_html . '</div>';
        }

        $grid_html .= '<div class="lunara-news-grid-card-content">';
        $grid_html .= '<h3 class="lunara-news-grid-card-title">' . $card['heading'] . '</h3>';
        $grid_html .= '<div class="lunara-news-grid-card-body">' . $card_body . '</div>';
        $grid_html .= '</div>';
        $grid_html .= '</article>';
    }

    $grid_html .= '</div>';

    return $grid_html;
}
add_filter( 'the_content', 'lunara_news_grid_content_filter', 8 );

/**
 * Homepage Oscar Facts carousel polish.
 *
 * The homepage critical CSS still treats Oscar Facts as a static grid. This late,
 * scoped layer turns only the Facts lane into a premium rotating card surface.
 */
function lunara_output_home_oscar_facts_carousel_css() {
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    <style id="lunara-home-oscar-facts-carousel-css">
    body.home .lunara-oscar-facts-section {
        overflow: hidden;
    }

	body.home .lunara-oscar-facts-section .lunara-oscar-facts-carousel {
		position: relative;
		width: min(100%, 1120px);
		margin: 0 auto;
		outline: none;
	}

	body.home .lunara-oscar-facts-section .lunara-oscar-facts-carousel[data-lunara-splide-pilot] {
		isolation: isolate;
	}

    body.home .lunara-oscar-facts-section .lunara-oscar-facts-track {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) !important;
        gap: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        overflow: visible !important;
        scroll-snap-type: none !important;
    }

    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.lunara-carousel-slide {
        grid-area: 1 / 1;
        min-width: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateX(18px);
        transition: opacity 420ms ease, transform 420ms ease, visibility 420ms ease;
    }

	body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.lunara-carousel-slide.active {
		opacity: 1;
		visibility: visible;
		pointer-events: auto;
		transform: translateX(0);
	}

	body.home .lunara-oscar-facts-section .lunara-oscar-facts-carousel.lunara-splide-ready {
		height: auto !important;
		min-height: 0 !important;
		max-height: none !important;
		padding-bottom: 46px;
		overflow: visible;
	}

	body.home .lunara-oscar-facts-section .lunara-oscar-facts-carousel.lunara-splide-ready .lunara-oscar-facts-track {
		display: block !important;
		overflow: hidden !important;
	}

	body.home .lunara-oscar-facts-section .lunara-oscar-facts-carousel.lunara-splide-ready .lunara-oscar-facts-splide-list {
		display: flex !important;
		align-items: flex-start !important;
		gap: 0 !important;
		width: 100%;
	}

	body.home .lunara-oscar-facts-section .lunara-oscar-facts-carousel.lunara-splide-ready .lunara-oscar-fact-card.lunara-carousel-slide {
		grid-area: auto !important;
		width: 100% !important;
		max-width: 100% !important;
		flex: 0 0 100% !important;
		box-sizing: border-box;
		min-width: 0 !important;
		opacity: 1 !important;
		visibility: visible !important;
		pointer-events: auto !important;
		transform: none !important;
		transition: none !important;
	}

	body.home .lunara-oscar-facts-section .lunara-oscar-facts-carousel.lunara-splide-ready .splide__slide {
		display: flex !important;
		height: auto !important;
	}

	body.home .lunara-oscar-facts-section .lunara-oscar-facts-carousel.lunara-splide-ready .lunara-oscar-fact-card-link {
		grid-template-columns: minmax(0, 1fr) minmax(360px, 1fr);
		width: 100% !important;
		max-width: 100% !important;
		box-sizing: border-box;
	}

	body.home .lunara-oscar-facts-section .lunara-oscar-facts-carousel.lunara-splide-ready .lunara-oscar-fact-card-link > * {
		min-width: 0;
	}

	body.home .lunara-oscar-facts-section .lunara-oscar-facts-carousel.lunara-splide-ready .lunara-oscar-fact-card-text {
		min-width: 0;
		overflow: visible;
	}

    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-link {
        display: grid !important;
        grid-template-columns: minmax(280px, 0.9fr) minmax(0, 1.1fr);
        align-items: stretch;
        min-height: clamp(360px, 34vw, 470px);
        background:
            radial-gradient(circle at 14% 20%, rgba(219, 180, 103, 0.16), transparent 34%),
            linear-gradient(135deg, rgba(10, 25, 39, 0.98), rgba(15, 31, 48, 0.92));
        border: 1px solid rgba(219, 180, 103, 0.3);
        box-shadow: 0 18px 34px rgba(0, 0, 0, 0.18);
        overflow: hidden;
    }

    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card:not(.has-poster) .lunara-oscar-fact-card-link {
        grid-template-columns: minmax(0, 1fr);
        min-height: clamp(300px, 28vw, 400px);
    }

    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.has-archival-visual .lunara-oscar-fact-card-link {
        min-height: clamp(340px, 30vw, 430px);
    }

    @media (min-width: 781px) {
        body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.has-archival-visual .lunara-oscar-fact-card-poster {
            min-height: clamp(260px, 28vw, 360px) !important;
            align-self: center;
        }
    }

    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-poster {
        aspect-ratio: auto !important;
        min-height: 100% !important;
        border-radius: 0 !important;
        background: #081522;
    }

    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-poster-image,
    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-poster img {
        width: 100% !important;
		height: 100% !important;
		min-height: 100% !important;
		object-fit: cover !important;
		object-position: var(--lunara-fact-image-position, center center) !important;
		transform: scale(1.015);
	}

    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.has-archival-visual .lunara-oscar-fact-card-poster {
        position: relative;
        display: grid;
        place-items: center;
        padding: clamp(12px, 1.8vw, 24px);
        background:
            radial-gradient(circle at 50% 42%, rgba(215, 182, 111, 0.18), transparent 38%),
            linear-gradient(140deg, rgba(7, 18, 29, 0.98), rgba(18, 34, 50, 0.94));
        overflow: hidden;
    }

    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.has-archival-visual .lunara-oscar-fact-card-poster::before {
        content: "";
        position: absolute;
        inset: clamp(14px, 2vw, 26px);
        z-index: 3;
        border: 1px solid rgba(215, 182, 111, 0.28);
        box-shadow: inset 0 0 0 1px rgba(246, 239, 226, 0.05);
        pointer-events: none;
    }

    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.has-archival-visual .lunara-oscar-fact-card-poster::after {
        content: "";
        position: absolute;
        inset: 0;
        z-index: 0;
        background-image: var(--lunara-fact-image-url, none);
        background-position: var(--lunara-fact-image-position, center center);
        background-size: cover;
        filter: blur(18px) saturate(1.04);
        opacity: 0.24;
        transform: scale(1.08);
        pointer-events: none;
    }

    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.has-archival-visual .lunara-oscar-fact-card-poster-image,
    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.has-archival-visual .lunara-oscar-fact-card-poster img {
        position: relative;
        z-index: 2;
        width: 100% !important;
        height: 100% !important;
        min-height: 0 !important;
		max-width: 100% !important;
		max-height: 100% !important;
		object-fit: contain !important;
		object-position: var(--lunara-fact-image-position, center center) !important;
		transform: none !important;
		box-shadow: 0 18px 32px rgba(0, 0, 0, 0.32);
	}

    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-text {
        justify-content: center;
        padding: clamp(24px, 4vw, 56px) !important;
    }

    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-kicker {
        color: #d7b66f !important;
    }

	body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-title {
		max-width: min(100%, 13em);
		color: #f6efe2 !important;
		font-size: clamp(1.65rem, 2.75vw, 2.65rem) !important;
		line-height: 1.04 !important;
		overflow-wrap: anywhere;
		white-space: normal !important;
		word-break: normal;
		text-wrap: balance;
	}

    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-body {
        max-width: 58ch;
        color: rgba(246, 239, 226, 0.86) !important;
        font-size: clamp(0.98rem, 1.15vw, 1.08rem) !important;
        line-height: 1.7 !important;
        text-wrap: pretty;
    }

    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-foot {
        align-items: center;
        color: rgba(246, 239, 226, 0.72) !important;
    }

    body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-cta {
        color: #d7b66f !important;
    }

    body.home .lunara-oscar-facts-section .lunara-oscar-facts-dots {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 18px;
    }

    body.home .lunara-oscar-facts-section .lunara-carousel-dot {
        width: 9px;
        height: 9px;
        padding: 0;
        border-radius: 999px;
        border: 1px solid rgba(215, 182, 111, 0.65);
        background: transparent;
        cursor: pointer;
        transition: width 220ms ease, background-color 220ms ease, border-color 220ms ease;
    }

	body.home .lunara-oscar-facts-section .lunara-carousel-dot.active {
		width: 30px;
		background: #d7b66f;
		border-color: #d7b66f;
	}

	body.home .lunara-oscar-facts-section .lunara-splide-arrow {
		position: absolute;
		top: 50%;
		z-index: 4;
		display: grid;
		place-items: center;
		width: 44px;
		height: 44px;
		margin: 0;
		padding: 0;
		border: 1px solid rgba(215, 182, 111, 0.54);
		border-radius: 999px;
		background: rgba(6, 14, 24, 0.86);
		color: #f6efe2;
		box-shadow: 0 14px 30px rgba(0, 0, 0, 0.28);
		opacity: 1;
		transition: background-color 180ms ease, border-color 180ms ease, transform 180ms ease;
	}

	body.home .lunara-oscar-facts-section .lunara-splide-arrow:hover,
	body.home .lunara-oscar-facts-section .lunara-splide-arrow:focus-visible {
		background: rgba(215, 182, 111, 0.2);
		border-color: rgba(246, 239, 226, 0.82);
		outline: 2px solid rgba(215, 182, 111, 0.34);
		outline-offset: 3px;
	}

	body.home .lunara-oscar-facts-section .lunara-splide-arrow svg {
		width: 16px;
		height: 16px;
		fill: currentColor;
	}

	body.home .lunara-oscar-facts-section .lunara-splide-arrow-prev {
		left: 14px;
		transform: translateY(-50%);
	}

	body.home .lunara-oscar-facts-section .lunara-splide-arrow-next {
		right: 14px;
		transform: translateY(-50%);
	}

	@media (max-width: 780px) {
        body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-link {
            grid-template-columns: minmax(0, 1fr) !important;
            grid-template-rows: auto 1fr !important;
            min-height: 0 !important;
        }

		body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-poster {
			aspect-ratio: 16 / 10 !important;
			height: clamp(190px, 56vw, 240px) !important;
			min-height: 0 !important;
		}

		body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-poster-image,
		body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-poster img {
			height: 100% !important;
			min-height: 0 !important;
		}

        body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.has-archival-visual .lunara-oscar-fact-card-poster {
            padding: 12px;
        }

        body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.has-archival-visual .lunara-oscar-fact-card-poster-image,
        body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.has-archival-visual .lunara-oscar-fact-card-poster img {
            max-width: 100% !important;
            max-height: 100% !important;
        }

		body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-title {
			max-width: 100%;
			font-size: clamp(1.28rem, 6.4vw, 1.82rem) !important;
			line-height: 1.08 !important;
		}

		body.home .lunara-oscar-facts-section .lunara-oscar-fact-card-body {
			font-size: 0.98rem !important;
		}

		body.home .lunara-oscar-facts-section .lunara-oscar-facts-carousel.lunara-splide-ready .lunara-oscar-fact-card-text {
			padding-bottom: 92px !important;
		}

		body.home .lunara-oscar-facts-section .lunara-oscar-facts-carousel.lunara-splide-ready {
			padding-bottom: 12px;
		}

		body.home .lunara-oscar-facts-section .lunara-oscar-facts-dots {
			margin-bottom: 54px;
		}

		body.home .lunara-oscar-facts-section .lunara-splide-arrow {
			top: auto;
			bottom: 8px;
			width: 38px;
			height: 38px;
		}

		body.home .lunara-oscar-facts-section .lunara-splide-arrow-prev {
			left: calc(50% - 58px);
			transform: none;
		}

		body.home .lunara-oscar-facts-section .lunara-splide-arrow-next {
			right: calc(50% - 58px);
			transform: none;
		}
	}

	@media (prefers-reduced-motion: reduce) {
		body.home .lunara-oscar-facts-section .lunara-oscar-fact-card.lunara-carousel-slide,
		body.home .lunara-oscar-facts-section .lunara-carousel-dot,
		body.home .lunara-oscar-facts-section .lunara-splide-arrow {
			transition: none !important;
		}
	}
    </style>
    <?php
}
add_action( 'wp_footer', 'lunara_output_home_oscar_facts_carousel_css', 120 );
