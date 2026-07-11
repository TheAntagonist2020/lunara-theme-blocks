<?php
/**
 * Theme setup, enqueue, and utility helpers.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Drop malformed srcset candidates injected by CDN/image optimizers.
 */
if ( ! function_exists( 'lunara_sanitize_srcset_value' ) ) {
    function lunara_sanitize_srcset_value( $srcset ) {
        $srcset = is_string( $srcset ) ? trim( html_entity_decode( $srcset, ENT_QUOTES, 'UTF-8' ) ) : '';
        if ( '' === $srcset ) {
            return '';
        }

        $candidates = preg_split( '/,\s*(?=(?:https?:)?\/\/|\/)/', $srcset );
        if ( ! is_array( $candidates ) || empty( $candidates ) ) {
            $candidates = array( $srcset );
        }

        $valid = array();
        foreach ( $candidates as $candidate ) {
            $candidate = trim( (string) $candidate );
            if ( '' === $candidate ) {
                continue;
            }

            $decoded_candidate = html_entity_decode( $candidate, ENT_QUOTES, 'UTF-8' );
            if ( preg_match( '/(?:[?&;](?:resize|fit)=0(?:%2c|,)nan)/i', $decoded_candidate ) || preg_match( '/(?:[?&;](?:w|h)=0(?:&|$))/i', $decoded_candidate ) ) {
                continue;
            }

            if ( preg_match( '/\s+\d+w$/', $candidate ) || preg_match( '/\s+\d+(?:\.\d+)?x$/', $candidate ) ) {
                $valid[] = $candidate;
            }
        }

        return implode( ', ', $valid );
    }
}

/**
 * Enqueue theme styles.
 */
function lunara_enqueue_styles() {
    // Base reset (replaces Blocksy parent base styles).
    $base_css = lunara_resolve_theme_asset( 'assets/css/lunara-base.css' );
    if ( ! empty( $base_css['uri'] ) ) {
        wp_enqueue_style(
            'lunara-base',
            $base_css['uri'],
            array(),
            lunara_theme_asset_version( $base_css['path'] )
        );
    }

    wp_enqueue_style(
        'lunara-style',
        get_stylesheet_uri(),
        array( 'lunara-base' ),
        filemtime( get_stylesheet_directory() . '/style.css' )
    );

    if ( defined( 'STACKABLE_VERSION' ) || defined( 'STACKABLE_FILE' ) ) {
        $stackable_css = lunara_resolve_theme_asset( 'assets/css/lunara-stackable.css' );

        if ( ! empty( $stackable_css['uri'] ) ) {
            wp_enqueue_style(
                'lunara-stackable',
                $stackable_css['uri'],
                array( 'lunara-style' ),
                lunara_theme_asset_version( $stackable_css['path'] )
            );
        }
    }

    if ( is_page( 'oscars' ) || is_page_template( 'page-oscars.php' ) ) {
        $oscars_css = lunara_resolve_theme_asset(
            'assets/css/oscars.css',
            array( 'oscars/oscars.css' )
        );

        if ( ! empty( $oscars_css['uri'] ) ) {
            wp_enqueue_style(
                'lunara-oscars-shell',
                $oscars_css['uri'],
                array( 'lunara-style' ),
                lunara_theme_asset_version( $oscars_css['path'] )
            );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'lunara_enqueue_styles' );

/**
 * Preload only the two text faces that move Home's first-viewport geometry.
 *
 * The display faces remain demand-loaded so the LCP image does not compete
 * with the full publication font family on a cold visit.
 */
function lunara_preload_home_text_fonts() {
    if ( ! is_front_page() ) {
        return;
    }

    $font_base = content_url( '/uploads/lunara-fonts/v1/' );
    $fonts     = array(
        'TiemposText-Regular.woff2',
        'TiemposText-Bold.woff2',
    );

    foreach ( $fonts as $font ) {
        printf(
            '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
            esc_url( $font_base . $font )
        );
    }
}
add_action( 'wp_head', 'lunara_preload_home_text_fonts', 1 );

/**
 * Load the public shell repair layer after route-specific styles.
 *
 * This stylesheet used to live inline in header.php. Keeping it as the final
 * queued theme stylesheet preserves the existing cascade while allowing the
 * browser to cache and reuse it across page views.
 */
function lunara_enqueue_shell_styles() {
    $shell_css = lunara_resolve_theme_asset( 'assets/css/lunara-shell.css' );

    if ( empty( $shell_css['uri'] ) ) {
        return;
    }

    wp_enqueue_style(
        'lunara-shell',
        $shell_css['uri'],
        array( 'lunara-style' ),
        lunara_theme_asset_version( $shell_css['path'] )
    );
}
add_action( 'wp_enqueue_scripts', 'lunara_enqueue_shell_styles', 100 );

/**
 * Print a cacheable stylesheet at the same late cascade point previously used
 * by its inline PHP emitter.
 *
 * @param string $handle        WordPress style handle.
 * @param string $relative_path Theme-relative asset path.
 */
function lunara_print_cacheable_stylesheet( $handle, $relative_path ) {
    $asset = lunara_resolve_theme_asset( $relative_path );

    if ( empty( $asset['uri'] ) ) {
        return;
    }

    wp_enqueue_style(
        $handle,
        $asset['uri'],
        array(),
        lunara_theme_asset_version( $asset['path'] )
    );
    wp_print_styles( $handle );
}

/**
 * Shared route guardrails, formerly emitted as four large inline blocks.
 */
function lunara_print_public_guardrail_styles() {
    if ( is_admin() || is_feed() ) {
        return;
    }

    lunara_print_cacheable_stylesheet( 'lunara-public-guardrails', 'assets/css/lunara-public-guardrails.css' );
}
add_action( 'wp_head', 'lunara_print_public_guardrail_styles', 1005 );

/**
 * Homepage module rules that do not contain request-specific values.
 */
function lunara_print_home_module_styles() {
    if ( ! is_front_page() ) {
        return;
    }

    lunara_print_cacheable_stylesheet( 'lunara-home-modules', 'assets/css/lunara-home-modules.css' );
}
add_action( 'wp_head', 'lunara_print_home_module_styles', 44 );

/**
 * Static Customizer selectors that consume the request-specific values printed
 * at priority 99. Keeping this link at 98 preserves the former cascade.
 */
function lunara_print_runtime_customizer_static_styles() {
    if ( is_admin() || is_feed() ) {
        return;
    }

    lunara_print_cacheable_stylesheet( 'lunara-runtime-customizer-static', 'assets/css/lunara-runtime-customizer-static.css' );
}
add_action( 'wp_head', 'lunara_print_runtime_customizer_static_styles', 98 );

/**
 * Preserve the original final-word cascade position for Oscars safeguards.
 */
function lunara_print_late_oscars_guardrail_styles() {
    if ( is_admin() || is_feed() ) {
        return;
    }

    lunara_print_cacheable_stylesheet( 'lunara-oscars-late-guardrails', 'assets/css/lunara-oscars-late-guardrails.css' );
}
add_action( 'wp_footer', 'lunara_print_late_oscars_guardrail_styles', 999 );

/**
 * Add the cacheable Room Tone overlays without runtime canvas work.
 */
function lunara_inject_room_tone_markup() {
    echo '<div id="lunara-grain" class="is-live" aria-hidden="true"></div><div id="lunara-vignette" aria-hidden="true"></div>';
}
add_action( 'wp_body_open', 'lunara_inject_room_tone_markup', 1 );

/**
 * Theme setup
 */
function lunara_theme_setup() {
    add_theme_support( 'post-thumbnails' );
    add_image_size( 'lunara-editorial-card', 1500, 2000, true );
    add_image_size( 'lunara-poster-library', 2000, 3000, true );
    add_image_size( 'lunara-hero-spotlight', 1920, 1080, true );
    add_image_size( 'lunara-review-card', 750, 1000, true );
    add_image_size( 'lunara-review-card-retina', 1500, 2000, true );
    add_image_size( 'lunara-review-feature', 1500, 2000, true );
    add_image_size( 'lunara-review-hero', 1920, 1080, true );
    add_theme_support( 'title-tag' );
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    add_theme_support( 'html5', array(
        'comment-list',
        'comment-form',
        'search-form',
        'gallery',
        'caption',
        'script',
        'style',
    ) );

    // Register navigation menu
    register_nav_menus( array(
        'primary'          => __( 'Primary Menu', 'lunara-film' ),
        'footer'           => __( 'Footer Menu', 'lunara-film' ),
        'footer-editorial' => __( 'Footer Editorial', 'lunara-film' ),
        'footer-oscars'    => __( 'Footer Oscars', 'lunara-film' ),
        'footer-utility'   => __( 'Footer Utility', 'lunara-film' ),
    ) );
}
add_action( 'after_setup_theme', 'lunara_theme_setup' );

/**
 * Keep the public front door from shipping an empty browser title when the
 * static homepage/page title is intentionally quiet.
 *
 * @param array $parts WordPress document title parts.
 * @return array
 */
function lunara_filter_front_page_document_title_parts( $parts ) {
    if ( ! is_front_page() ) {
        return $parts;
    }

    $site_name = trim( (string) get_bloginfo( 'name' ) );
    if ( '' === $site_name ) {
        $site_name = __( 'Lunara Film', 'lunara-film' );
    }

    $parts['title'] = $site_name;

    return $parts;
}
add_filter( 'document_title_parts', 'lunara_filter_front_page_document_title_parts', 20 );

/**
 * Ensure the custom logo always ships with meaningful accessibility text.
 */
function lunara_filter_custom_logo_image_attributes( $attr, $custom_logo_id, $blog_id ) {
    unset( $custom_logo_id, $blog_id );

    $alt = isset( $attr['alt'] ) ? trim( (string) $attr['alt'] ) : '';
    $classes = isset( $attr['class'] ) ? (string) $attr['class'] : '';

    if ( '' === $alt ) {
        $site_name   = trim( (string) get_bloginfo( 'name' ) );
        $attr['alt'] = sprintf( __( '%s logo', 'lunara-film' ), '' !== $site_name ? $site_name : 'Lunara Film' );
    }

    $attr['width']    = 300;
    $attr['height']   = 79;
    $attr['sizes']    = '(max-width: 689px) 260px, 300px';
    $attr['decoding'] = 'async';

    if ( false !== strpos( $classes, 'default-logo' ) ) {
        $attr['loading']       = 'eager';
        $attr['fetchpriority'] = 'high';
    } elseif ( false !== strpos( $classes, 'dark-mode-logo' ) ) {
        $attr['loading'] = 'lazy';
        unset( $attr['fetchpriority'] );
    }

    return $attr;
}
add_filter( 'get_custom_logo_image_attributes', 'lunara_filter_custom_logo_image_attributes', 10, 3 );

function lunara_resize_wpcom_image_url( $url, $width, $height ) {
    $url    = html_entity_decode( (string) $url, ENT_QUOTES, 'UTF-8' );
    $width  = max( 1, absint( $width ) );
    $height = max( 1, absint( $height ) );

    if ( '' === trim( $url ) || 0 === $width || 0 === $height ) {
        return '';
    }

    $url = remove_query_arg( array( 'fit', 'resize', 'w', 'h', '_jb' ), $url );
    $url = add_query_arg(
        array(
            'resize'  => $width . ',' . $height,
            'quality' => '86',
            'ssl'     => '1',
        ),
        $url
    );

    return esc_url( str_replace( ',', '%2C', $url ) );
}

function lunara_normalize_custom_logo_markup( $html ) {
    if ( ! is_string( $html ) || '' === $html ) {
        return $html;
    }

    if ( false === stripos( $html, 'default-logo' ) && false === stripos( $html, 'dark-mode-logo' ) && false === stripos( $html, 'lunara-footer-logo' ) ) {
        return $html;
    }

    if ( ! preg_match( '/\ssrc=("|\')([^"\']+)\1/i', $html, $src_match ) ) {
        return $html;
    }

    $logo_300 = lunara_resize_wpcom_image_url( $src_match[2], 300, 79 );
    $logo_600 = lunara_resize_wpcom_image_url( $src_match[2], 600, 158 );

    if ( '' === $logo_300 ) {
        return $html;
    }

    $html = str_replace( $src_match[0], ' src="' . esc_url( $logo_300 ) . '"', $html );

    if ( '' !== $logo_600 ) {
        $srcset = esc_attr( $logo_300 . ' 300w, ' . $logo_600 . ' 600w' );
        if ( preg_match( '/\ssrcset=("|\')[^"\']+\1/i', $html, $srcset_match ) ) {
            $html = str_replace( $srcset_match[0], ' srcset="' . $srcset . '"', $html );
        } else {
            $html = preg_replace( '/<img\b/i', '<img srcset="' . $srcset . '"', $html, 1 );
        }
    }

    if ( preg_match( '/\ssizes=("|\')[^"\']+\1/i', $html, $sizes_match ) ) {
        $html = str_replace( $sizes_match[0], ' sizes="(max-width: 689px) 260px, 300px"', $html );
    }

    if ( preg_match( '/\salt=("|\')\s*(?:logo)?\s*\1/i', $html, $alt_match ) ) {
        $html = str_replace( $alt_match[0], ' alt="Lunara Film logo"', $html );
    }

    $html = preg_replace( '/\sdata-(?!(?:no-lazy|skip-lazy)\b)[a-z0-9_-]+=("|\')[^"\']*\1/i', '', $html );

    return $html;
}
add_filter( 'get_custom_logo', 'lunara_normalize_custom_logo_markup', PHP_INT_MAX );

/**
 * Keep above-the-fold images out of JS lazy placeholders.
 */
function lunara_is_priority_image_attr( $attr ) {
    if ( ! is_array( $attr ) ) {
        return false;
    }

    $loading       = isset( $attr['loading'] ) ? strtolower( (string) $attr['loading'] ) : '';
    $fetchpriority = isset( $attr['fetchpriority'] ) ? strtolower( (string) $attr['fetchpriority'] ) : '';

    return 'eager' === $loading || 'high' === $fetchpriority;
}

function lunara_protect_priority_image_attributes( $attr ) {
    if ( is_admin() ) {
        return $attr;
    }

    $class_string = isset( $attr['class'] ) ? (string) $attr['class'] : '';

    if ( false !== strpos( $class_string, 'default-logo' ) || false !== strpos( $class_string, 'dark-mode-logo' ) || false !== strpos( $class_string, 'lunara-footer-logo' ) ) {
        $attr['width']    = 300;
        $attr['height']   = 79;
        $attr['sizes']    = '(max-width: 689px) 260px, 300px';
        $attr['decoding'] = 'async';

        if ( empty( $attr['alt'] ) ) {
            $site_name   = trim( (string) get_bloginfo( 'name' ) );
            $attr['alt'] = sprintf( __( '%s logo', 'lunara-film' ), '' !== $site_name ? $site_name : 'Lunara Film' );
        }

        if ( false !== strpos( $class_string, 'default-logo' ) ) {
            $attr['loading']       = 'eager';
            $attr['fetchpriority'] = 'high';
        } else {
            $attr['loading'] = 'lazy';
            unset( $attr['fetchpriority'] );
        }
    }

    if ( ! lunara_is_priority_image_attr( $attr ) ) {
        return $attr;
    }

    $classes = isset( $attr['class'] ) ? preg_split( '/\s+/', (string) $attr['class'] ) : array();
    $classes = array_values( array_filter( array_map( 'sanitize_html_class', (array) $classes ) ) );
    $classes = array_diff( $classes, array( 'lazyload' ) );
    $classes = array_unique( array_merge( $classes, array( 'skip-lazy', 'no-lazy' ) ) );

    $attr['class']          = trim( implode( ' ', $classes ) );
    $attr['data-no-lazy']   = '1';
    $attr['data-skip-lazy'] = '1';
    $attr['decoding']       = 'async';

    return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'lunara_protect_priority_image_attributes', PHP_INT_MAX );

function lunara_restore_priority_image_markup( $html ) {
    if ( is_admin() || ! is_string( $html ) || '' === $html ) {
        return $html;
    }

    $is_logo_markup = false !== stripos( $html, 'default-logo' ) || false !== stripos( $html, 'dark-mode-logo' ) || false !== stripos( $html, 'lunara-footer-logo' );

    if ( false === stripos( $html, 'fetchpriority="high"' ) && false === stripos( $html, "fetchpriority='high'" ) && false === stripos( $html, 'loading="eager"' ) && false === stripos( $html, "loading='eager'" ) ) {
        return $is_logo_markup ? lunara_normalize_custom_logo_markup( $html ) : $html;
    }

    if ( preg_match( '/\sdata-(?:lazy-)?src=("|\')([^"\']+)\1/i', $html, $src_match ) ) {
        $src = esc_url( html_entity_decode( (string) $src_match[2], ENT_QUOTES, 'UTF-8' ) );
        if ( '' !== $src ) {
            $html = preg_replace( '/\ssrc=("|\')data:image\/gif[^"\']*\1/i', ' src="' . $src . '"', $html, 1 );
        }
    }

    if ( false === stripos( $html, ' srcset=' ) && preg_match( '/\sdata-(?:lazy-)?srcset=("|\')([^"\']+)\1/i', $html, $srcset_match ) ) {
        $srcset = lunara_sanitize_srcset_value( (string) $srcset_match[2] );
        if ( '' !== $srcset ) {
            $html = preg_replace( '/<img\b/i', '<img srcset="' . esc_attr( $srcset ) . '"', $html, 1 );
        }
    }

    if ( preg_match( '/\sclass=("|\')([^"\']*)\1/i', $html, $class_match ) ) {
        $classes = preg_split( '/\s+/', (string) $class_match[2] );
        $classes = array_values( array_filter( array_map( 'sanitize_html_class', (array) $classes ) ) );
        $classes = array_diff( $classes, array( 'lazyload' ) );
        $classes = array_unique( array_merge( $classes, array( 'skip-lazy', 'no-lazy' ) ) );
        $html    = str_replace( $class_match[0], ' class="' . esc_attr( trim( implode( ' ', $classes ) ) ) . '"', $html );
    }

    if ( false === stripos( $html, 'data-no-lazy=' ) ) {
        $html = preg_replace( '/<img\b/i', '<img data-no-lazy="1" data-skip-lazy="1"', $html, 1 );
    }

    $html = lunara_normalize_custom_logo_markup( $html );

    return $html;
}
add_filter( 'wp_get_attachment_image', 'lunara_restore_priority_image_markup', PHP_INT_MAX );
add_filter( 'post_thumbnail_html', 'lunara_restore_priority_image_markup', PHP_INT_MAX );

if ( ! function_exists( 'lunara_resize_tmdb_image_url' ) ) {
    function lunara_resize_tmdb_image_url( $url, $size = 'w780' ) {
        $url  = trim( (string) $url );
        $size = preg_match( '/^w\d+$/', (string) $size ) ? (string) $size : 'w780';

        if ( '' === $url ) {
            return '';
        }

        return preg_replace( '#https://image\.tmdb\.org/t/p/(?:w\d+|original)(?=/)#i', 'https://image.tmdb.org/t/p/' . $size, $url );
    }
}

if ( ! function_exists( 'lunara_resize_tmdb_image_markup' ) ) {
    function lunara_resize_tmdb_image_markup( $html, $size = 'w500' ) {
        if ( ! is_string( $html ) || '' === $html ) {
            return $html;
        }

        $size = preg_match( '/^w\d+$/', (string) $size ) ? (string) $size : 'w500';

        return preg_replace_callback(
            '#https://image\.tmdb\.org/t/p/(?:w\d+|original)(/[^\s"\'<)]+)#i',
            function ( $matches ) use ( $size ) {
                return 'https://image.tmdb.org/t/p/' . $size . $matches[1];
            },
            $html
        );
    }
}

if ( ! function_exists( 'lunara_normalize_visual_package_image_sizes' ) ) {
    function lunara_normalize_visual_package_image_sizes( $visual, $poster_size = 'w500', $backdrop_size = 'w780' ) {
        if ( ! is_array( $visual ) || empty( $visual ) ) {
            return array();
        }

        foreach ( array( 'poster_url' ) as $key ) {
            if ( ! empty( $visual[ $key ] ) ) {
                $visual[ $key ] = lunara_resize_tmdb_image_url( $visual[ $key ], $poster_size );
            }
        }

        foreach ( array( 'portrait_url' ) as $key ) {
            if ( ! empty( $visual[ $key ] ) ) {
                $visual[ $key ] = lunara_resize_tmdb_image_url( $visual[ $key ], 'w342' );
            }
        }

        if ( ! empty( $visual['backdrop_url'] ) ) {
            $visual['backdrop_url'] = lunara_resize_tmdb_image_url( $visual['backdrop_url'], $backdrop_size );
        }

        foreach ( array( 'poster_html', 'fallback_html', 'card_fallback_html' ) as $key ) {
            if ( ! empty( $visual[ $key ] ) ) {
                $visual[ $key ] = lunara_resize_tmdb_image_markup( $visual[ $key ], $poster_size );
            }
        }

        if ( ! empty( $visual['tmdb'] ) && is_array( $visual['tmdb'] ) ) {
            if ( ! empty( $visual['tmdb']['poster_full'] ) ) {
                $visual['tmdb']['poster_full'] = lunara_resize_tmdb_image_url( $visual['tmdb']['poster_full'], $poster_size );
            }
            if ( ! empty( $visual['tmdb']['profile_full'] ) ) {
                $visual['tmdb']['profile_full'] = lunara_resize_tmdb_image_url( $visual['tmdb']['profile_full'], 'w342' );
            }
            if ( ! empty( $visual['tmdb']['backdrop_full'] ) ) {
                $visual['tmdb']['backdrop_full'] = lunara_resize_tmdb_image_url( $visual['tmdb']['backdrop_full'], $backdrop_size );
            }
        }

        return $visual;
    }
}

/**
 * Optional escape hatch for forcing the legacy Lunara shell.
 *
 * By default we now allow Blocksy to render its native header builder again so
 * the parent theme regains control over the shell UI. A filter remains in place
 * in case we need to temporarily restore the old child-theme header path during
 * follow-up work.
 */
if ( apply_filters( 'lunara_disable_blocksy_header', false ) ) {
    add_filter( 'blocksy_has_header', '__return_false' );
}

/**
 * Resolve a child-theme asset across the canonical assets tree and older flat-file layouts.
 */
function lunara_resolve_theme_asset( $preferred, $fallbacks = array() ) {
    $candidates = array_merge( array( $preferred ), (array) $fallbacks );

    foreach ( $candidates as $relative ) {
        $relative = ltrim( str_replace( '\\', '/', (string) $relative ), '/' );
        if ( $relative === '' ) {
            continue;
        }

        $path = trailingslashit( get_stylesheet_directory() ) . $relative;
        if ( file_exists( $path ) ) {
            return array(
                'path' => $path,
                'uri'  => trailingslashit( get_stylesheet_directory_uri() ) . $relative,
            );
        }
    }

    return array(
        'path' => '',
        'uri'  => '',
    );
}

/**
 * Use file modification time when possible so asset changes bust cache automatically.
 */
function lunara_theme_asset_version( $path ) {
    if ( $path && file_exists( $path ) ) {
        return (string) filemtime( $path );
    }

    return wp_get_theme()->get( 'Version' );
}

/**
 * Return a non-empty theme mod string or a fallback.
 */
if ( ! function_exists( 'lunara_theme_mod_text' ) ) {
    function lunara_theme_mod_text( $setting, $default = '' ) {
        $value = trim( (string) get_theme_mod( $setting, '' ) );

        return $value !== '' ? $value : $default;
    }
}

/**
 * Return a non-empty theme mod URL or a fallback.
 */
if ( ! function_exists( 'lunara_theme_mod_url' ) ) {
    function lunara_theme_mod_url( $setting, $default = '' ) {
        $value = esc_url_raw( (string) get_theme_mod( $setting, '' ) );

        return $value !== '' ? $value : $default;
    }
}

/**
 * Keep a stable QR URL for festival outreach while allowing the destination to change.
 */
function lunara_handle_festival_qr_redirect() {
    $qr_key = isset( $_GET['lunara_qr'] ) ? sanitize_key( wp_unslash( $_GET['lunara_qr'] ) ) : '';

    if ( 'festival' !== $qr_key ) {
        return;
    }

    $target = lunara_theme_mod_url( 'lunara_festival_qr_target_url', home_url( '/' ) );
    if ( '' === $target ) {
        $target = home_url( '/' );
    }

    $target_host = wp_parse_url( $target, PHP_URL_HOST );
    $home_host   = wp_parse_url( home_url( '/' ), PHP_URL_HOST );

    if ( $target_host && $home_host && strtolower( $target_host ) === strtolower( $home_host ) ) {
        $target = add_query_arg(
            array(
                'utm_source'   => 'festival_qr',
                'utm_medium'   => 'qr',
                'utm_campaign' => 'festival_outreach',
            ),
            $target
        );
    }

    wp_safe_redirect( $target, 302, 'Lunara Festival QR' );
    exit;
}
add_action( 'template_redirect', 'lunara_handle_festival_qr_redirect', 0 );

/**
 * Treat /news/ as a legacy doorway into the Journal surface.
 */
if ( ! function_exists( 'lunara_handle_legacy_news_redirect' ) ) {
    function lunara_handle_legacy_news_redirect() {
        if ( is_admin() || wp_doing_ajax() || is_customize_preview() ) {
            return;
        }

        $request_path = wp_parse_url( home_url( add_query_arg( array() ) ), PHP_URL_PATH );
        $request_path = is_string( $request_path ) ? untrailingslashit( $request_path ) : '';

        if ( '/news' !== $request_path ) {
            return;
        }

        $target = function_exists( 'lunara_home_dispatch_archive_url' )
            ? lunara_home_dispatch_archive_url()
            : home_url( '/journal/' );

        if ( ! is_string( $target ) || '' === $target ) {
            $target = home_url( '/journal/' );
        }

        if ( untrailingslashit( $target ) === untrailingslashit( home_url( '/news/' ) ) ) {
            return;
        }

        wp_safe_redirect( $target, 301, 'Lunara Legacy News' );
        exit;
    }
}
add_action( 'template_redirect', 'lunara_handle_legacy_news_redirect', 1 );

/**
 * Normalize rich text into a clean one-line archive summary.
 */
if ( ! function_exists( 'lunara_clean_archive_summary_text' ) ) {
    function lunara_clean_archive_summary_text( $content ) {
        $content = strip_shortcodes( (string) $content );
        $content = wp_strip_all_tags( $content );
        $content = preg_replace( '/\s+/', ' ', $content );

        return trim( (string) $content );
    }
}

/**
 * Pull a safe archive intro from a supporting page without leaking shortcode scaffolding.
 */
if ( ! function_exists( 'lunara_get_archive_intro_from_post' ) ) {
    function lunara_get_archive_intro_from_post( $post ) {
        if ( ! ( $post instanceof WP_Post ) ) {
            return '';
        }

        $excerpt = lunara_clean_archive_summary_text( $post->post_excerpt );
        if ( '' !== $excerpt ) {
            return $excerpt;
        }

        return lunara_clean_archive_summary_text( $post->post_content );
    }
}

/**
 * Parse comma/newline-separated post IDs or URLs into published post IDs.
 */
if ( ! function_exists( 'lunara_parse_manual_post_ids' ) ) {
    function lunara_parse_manual_post_ids( $raw_value, $allowed_post_type = '' ) {
        $allowed_post_type = sanitize_key( (string) $allowed_post_type );
        $tokens            = preg_split( '/[\r\n,]+/', (string) $raw_value );
        $post_ids          = array();

        if ( ! is_array( $tokens ) ) {
            return array();
        }

        foreach ( $tokens as $token ) {
            $token   = trim( (string) $token );
            $post_id = 0;

            if ( '' === $token ) {
                continue;
            }

            if ( preg_match( '/^\d+$/', $token ) ) {
                $post_id = intval( $token );
            } elseif ( filter_var( $token, FILTER_VALIDATE_URL ) ) {
                $post_id = url_to_postid( $token );
            }

            if ( $post_id <= 0 ) {
                continue;
            }

            $post = get_post( $post_id );
            if ( ! ( $post instanceof WP_Post ) || 'publish' !== $post->post_status ) {
                continue;
            }

            if ( '' !== $allowed_post_type && $post->post_type !== $allowed_post_type ) {
                continue;
            }

            $post_ids[] = $post_id;
        }

        return array_values( array_unique( array_map( 'intval', $post_ids ) ) );
    }
}

/**
 * Sanitize decimal Customizer values.
 */
function lunara_sanitize_decimal( $value ) {
    return is_numeric( $value ) ? (float) $value : 0;
}

/**
 * Sanitize a CSS font stack entered in the Customizer.
 */
function lunara_sanitize_font_stack( $value ) {
    $value = wp_strip_all_tags( (string) $value );
    $value = preg_replace( '/[^A-Za-z0-9,\-_"\'\s]/', '', $value );
    $value = preg_replace( '/\s+/', ' ', (string) $value );

    return trim( (string) $value );
}

/**
 * Sanitize a select/radio Customizer value against its declared choices.
 *
 * @param string               $input   The value to sanitize.
 * @param WP_Customize_Setting $setting The setting instance.
 * @return string Sanitized value or the setting default.
 */
function lunara_sanitize_select( $input, $setting ) {
    $input   = sanitize_text_field( $input );
    $choices = $setting->manager->get_control( $setting->id )->choices;

    return array_key_exists( $input, $choices ) ? $input : $setting->default;
}

/**
 * Convert a hex color into rgba() for runtime CSS output.
 */
function lunara_hex_to_rgba( $value, $opacity = 1 ) {
    $hex = sanitize_hex_color( $value );

    if ( ! $hex ) {
        return 'rgba(201,169,97,0.24)';
    }

    $hex = ltrim( $hex, '#' );

    if ( 3 === strlen( $hex ) ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    $opacity = max( 0, min( 1, (float) $opacity ) );
    $red     = hexdec( substr( $hex, 0, 2 ) );
    $green   = hexdec( substr( $hex, 2, 2 ) );
    $blue    = hexdec( substr( $hex, 4, 2 ) );
    $alpha   = rtrim( rtrim( sprintf( '%.3F', $opacity ), '0' ), '.' );

    return sprintf( 'rgba(%d,%d,%d,%s)', $red, $green, $blue, $alpha );
}
