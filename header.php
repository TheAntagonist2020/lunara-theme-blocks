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
$header_search_placeholder = __( 'Search Lunara', 'lunara-film' );
$header_search_id          = 'lunara-header-search-input';
$lunara_strip_search_menu_item = null;
$lunara_strip_search_menu_markup = null;

if ( is_page_template( 'page-oscars.php' ) || is_page( 'oscars' ) ) {
    $header_search_placeholder = __( 'Search the Oscar Ledger', 'lunara-film' );
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
