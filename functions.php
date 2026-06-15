<?php
/**
 * Lunara Film Child Theme Functions
 * 
 * @package Lunara_Film
 * @version 2.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Path B v2 (2026-04-19): wire the v3.0.0 split architecture.
 *
 * functions-loader.php loads all inc/ modules first. EVERY duplicate function
 * in this monolithic file below is wrapped in a `function_exists` guard so the
 * inc/ version wins and the monolith definitions silently skip.
 *
 * The earlier Path B attempt left 3 functions unwrapped (lunara_resolve_theme_asset,
 * lunara_get_home_pulse_editorial_cards, lunara_build_home_title_story) because
 * the wrap regex couldn't parse `= array()` defaults. Fixed in v2.
 *
 * To revert in one move: comment out the require_once below.
 */
require_once __DIR__ . '/functions-loader.php';

// The split inc/ modules have loaded. Treat the old monolithic fallback blocks
// below as inactive so they cannot redeclare CPT, debrief, carousel, or taxonomy
// functions already provided by the modular files.
if ( ! defined( 'LUNARA_CORE_VERSION' ) ) {
    define( 'LUNARA_CORE_VERSION', 'split-loader' );
}

/**
 * Enqueue parent and child theme styles
 */
if ( ! function_exists( 'lunara_enqueue_styles' ) ) {
function lunara_enqueue_styles() {
    // Parent styles (Blocksy)
    wp_enqueue_style(
        'blocksy-style',
        get_template_directory_uri() . '/style.css',
        array(),
        wp_get_theme( get_template() )->get( 'Version' )
    );
    
    wp_enqueue_style(
        'lunara-style',
        get_stylesheet_uri(),
        array( 'blocksy-style' ),
        filemtime( get_stylesheet_directory() . '/style.css' )
    );

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
}
add_action( 'wp_enqueue_scripts', 'lunara_enqueue_styles' );

/**
 * Theme setup
 */
if ( ! function_exists( 'lunara_theme_setup' ) ) {
function lunara_theme_setup() {
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
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
}
add_action( 'after_setup_theme', 'lunara_theme_setup' );

/**
 * Resolve a child-theme asset across the canonical assets tree and older flat-file layouts.
 */
if ( ! function_exists( 'lunara_resolve_theme_asset' ) ) {
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
}

/**
 * Use file modification time when possible so asset changes bust cache automatically.
 */
if ( ! function_exists( 'lunara_theme_asset_version' ) ) {
function lunara_theme_asset_version( $path ) {
    if ( $path && file_exists( $path ) ) {
        return (string) filemtime( $path );
    }

    return wp_get_theme()->get( 'Version' );
}
}

/**
 * Repair common UTF-8 mojibake sequences in stored editorial labels.
 */
if ( ! function_exists( 'lunara_repair_mojibake_text' ) ) {
    function lunara_repair_mojibake_text( $value ) {
        $value = (string) $value;

        if ( '' === $value ) {
            return $value;
        }

        $entities = array(
            'mdash'  => html_entity_decode( '&mdash;', ENT_QUOTES, 'UTF-8' ),
            'ndash'  => html_entity_decode( '&ndash;', ENT_QUOTES, 'UTF-8' ),
            'lsquo'  => html_entity_decode( '&lsquo;', ENT_QUOTES, 'UTF-8' ),
            'rsquo'  => html_entity_decode( '&rsquo;', ENT_QUOTES, 'UTF-8' ),
            'ldquo'  => html_entity_decode( '&ldquo;', ENT_QUOTES, 'UTF-8' ),
            'rdquo'  => html_entity_decode( '&rdquo;', ENT_QUOTES, 'UTF-8' ),
            'hellip' => html_entity_decode( '&hellip;', ENT_QUOTES, 'UTF-8' ),
        );

        return strtr(
            $value,
            array(
                "\xC3\xA2\xE2\x82\xAC\xE2\x80\x9D" => $entities['mdash'],
                "\xC3\xA2\xE2\x82\xAC\xE2\x80\x9C" => $entities['ndash'],
                "\xC3\xA2\xE2\x82\xAC\xCB\x9C"     => $entities['lsquo'],
                "\xC3\xA2\xE2\x82\xAC\xE2\x84\xA2" => $entities['rsquo'],
                "\xC3\xA2\xE2\x82\xAC\xC5\x93"     => $entities['ldquo'],
                "\xC3\xA2\xE2\x82\xAC\xC2\x9D"     => $entities['rdquo'],
                "\xC3\xA2\xE2\x82\xAC\xC2\xA6"     => $entities['hellip'],
                "\xC3\x82 "                         => ' ',
                "\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\xA2\xE2\x82\xAC\xC2\x9D" => $entities['mdash'],
                "\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\x82\xC2\xA6"             => $entities['hellip'],
            )
        );
    }
}

/**
 * Repair common mojibake sequences in selected string arguments.
 */
if ( ! function_exists( 'lunara_repair_mojibake_args' ) ) {
    function lunara_repair_mojibake_args( $args, $keys ) {
        foreach ( (array) $keys as $key ) {
            if ( isset( $args[ $key ] ) && is_string( $args[ $key ] ) ) {
                $args[ $key ] = lunara_repair_mojibake_text( $args[ $key ] );
            }
        }

        return $args;
    }
}

/**
 * Return a non-empty theme mod string or a fallback.
 */
if ( ! function_exists( 'lunara_theme_mod_text' ) ) {
    function lunara_theme_mod_text( $setting, $default = '' ) {
        $value = trim( (string) get_theme_mod( $setting, '' ) );

        return $value !== '' ? lunara_repair_mojibake_text( $value ) : lunara_repair_mojibake_text( $default );
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
if ( ! function_exists( 'lunara_sanitize_decimal' ) ) {
function lunara_sanitize_decimal( $value ) {
    return is_numeric( $value ) ? (float) $value : 0;
}
}

/**
 * Sanitize a CSS font stack entered in the Customizer.
 */
if ( ! function_exists( 'lunara_sanitize_font_stack' ) ) {
function lunara_sanitize_font_stack( $value ) {
    $value = wp_strip_all_tags( (string) $value );
    $value = preg_replace( '/[^A-Za-z0-9,\-_"\'\s]/', '', $value );
    $value = preg_replace( '/\s+/', ' ', (string) $value );

    return trim( (string) $value );
}
}

/**
 * Convert a hex color into rgba() for runtime CSS output.
 */
if ( ! function_exists( 'lunara_hex_to_rgba' ) ) {
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
}

/**
 * Canonical homepage section slugs used for ordering and visibility.
 */
if ( ! function_exists( 'lunara_get_home_section_slugs' ) ) {
function lunara_get_home_section_slugs() {
    return array(
        'hero',
        'latest-reviews',
        'dispatch',
        'oscar-picks',     // Added 2026-05-10 â€” Lunara Oscar Picks carousel
        'oscar-facts',     // Added 2026-05-10 â€” Lunara Oscar Facts carousel
        'featured',
        'oscar-spotlight',
        'database',
        'ledger',
        'deep-cuts',
    );
}
}

/**
 * Normalize a homepage section slug so editorial controls can use friendly aliases.
 */
if ( ! function_exists( 'lunara_normalize_home_section_slug' ) ) {
function lunara_normalize_home_section_slug( $slug ) {
    $slug    = sanitize_title( (string) $slug );
    $aliases = array(
        'featured-reviews'  => 'featured',
        'featured-review'   => 'featured',
        'dispatches'        => 'dispatch',
        'journal'           => 'dispatch',
        'oscar-spotlight'   => 'oscar-spotlight',
        'oscar-spotlights'  => 'oscar-spotlight',
        'spotlight'         => 'oscar-spotlight',
        'database-spotlight'=> 'database',
        'deep-cuts'         => 'deep-cuts',
        'deep-cut'          => 'deep-cuts',
        'deepcut'           => 'deep-cuts',
        'latest'            => 'latest-reviews',
        'latest-reviews'    => 'latest-reviews',
        'reviews'           => 'latest-reviews',
    );

    return isset( $aliases[ $slug ] ) ? $aliases[ $slug ] : $slug;
}
}

/**
 * Sanitize a comma-separated homepage section order list.
 */
if ( ! function_exists( 'lunara_sanitize_home_section_order' ) ) {
function lunara_sanitize_home_section_order( $value ) {
    $recognized = lunara_get_home_section_slugs();
    $tokens     = preg_split( '/[\s,\r\n]+/', strtolower( (string) $value ) );
    $ordered    = array();

    if ( is_array( $tokens ) ) {
        foreach ( $tokens as $token ) {
            $token = lunara_normalize_home_section_slug( $token );
            if ( '' === $token || ! in_array( $token, $recognized, true ) || in_array( $token, $ordered, true ) ) {
                continue;
            }

            $ordered[] = $token;
        }
    }

    foreach ( $recognized as $slug ) {
        if ( ! in_array( $slug, $ordered, true ) ) {
            $ordered[] = $slug;
        }
    }

    return implode( ',', $ordered );
}
}

/**
 * Resolve homepage section order into a slug => order map.
 */
if ( ! function_exists( 'lunara_get_home_section_order_map' ) ) {
function lunara_get_home_section_order_map() {
    $defaults = lunara_get_home_section_slugs();
    $raw      = (string) get_theme_mod( 'lunara_home_section_order', implode( ',', $defaults ) );
    $ordered  = explode( ',', lunara_sanitize_home_section_order( $raw ) );

    // BACKFILL 2026-05-10: append any recognized slug missing from the saved
    // customizer order. Without this, slugs added to lunara_get_home_section_slugs()
    // after the customizer was first saved (e.g. oscar-picks, oscar-facts) get NO
    // order rule and default to order:0 â€” rendering BEFORE all numbered sections.
    foreach ( $defaults as $default_slug ) {
        if ( '' !== $default_slug && ! in_array( $default_slug, $ordered, true ) ) {
            $ordered[] = $default_slug;
        }
    }

    $map = array();
    foreach ( $ordered as $index => $slug ) {
        if ( '' === $slug ) {
            continue;
        }
        $map[ $slug ] = $index + 1;
    }

    return $map;
}
}

/**
 * Sanitize a comma-separated order string against a known list of tokens.
 */
function lunara_sanitize_token_order( $value, $recognized ) {
    $recognized = array_values( array_filter( array_map( 'sanitize_key', (array) $recognized ) ) );
    $tokens     = preg_split( '/[\s,\r\n]+/', strtolower( (string) $value ) );
    $ordered    = array();

    if ( is_array( $tokens ) ) {
        foreach ( $tokens as $token ) {
            $token = sanitize_key( (string) $token );
            if ( '' === $token || ! in_array( $token, $recognized, true ) || in_array( $token, $ordered, true ) ) {
                continue;
            }

            $ordered[] = $token;
        }
    }

    foreach ( $recognized as $token ) {
        if ( ! in_array( $token, $ordered, true ) ) {
            $ordered[] = $token;
        }
    }

    return implode( ',', $ordered );
}

function lunara_sanitize_journal_live_section_order( $value ) {
    return lunara_sanitize_token_order( $value, array( 'spotlight', 'run', 'pagination' ) );
}

function lunara_sanitize_journal_empty_section_order( $value ) {
    return lunara_sanitize_token_order( $value, array( 'intro', 'standby' ) );
}

function lunara_sanitize_journal_standby_card_order( $value ) {
    return lunara_sanitize_token_order( $value, array( 'reviews', 'ledger', 'home' ) );
}

/**
 * Determine whether a homepage section should render.
 */
if ( ! function_exists( 'lunara_home_section_is_enabled' ) ) {
function lunara_home_section_is_enabled( $slug, $default = true ) {
    $settings = array(
        'hero'            => 'lunara_home_show_hero',
        'featured'        => 'lunara_home_show_featured',
        'dispatch'        => 'lunara_home_show_dispatch',
        'oscar-spotlight' => 'lunara_home_show_oscar_spotlight',
        'database'        => 'lunara_home_show_database',
        'ledger'          => 'lunara_home_show_ledger',
        'deep-cuts'       => 'lunara_home_show_deep_cuts',
        'latest-reviews'  => 'lunara_home_show_latest_reviews',
    );

    $slug = lunara_normalize_home_section_slug( $slug );

    if ( ! isset( $settings[ $slug ] ) ) {
        return (bool) $default;
    }

    return (bool) get_theme_mod( $settings[ $slug ], $default );
}
}

/**
 * Customizer options
 */
if ( ! function_exists( 'lunara_customize_register' ) ) {
function lunara_customize_register( $wp_customize ) {
    // Lunara Header Section
    $wp_customize->add_section( 'lunara_header_options', array(
        'title'    => __( 'Lunara Header', 'lunara-film' ),
        'priority' => 30,
    ) );
    
    // Site Title Text
    $wp_customize->add_setting( 'lunara_site_title', array(
        'default'           => 'LUNARA FILM',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    
    $wp_customize->add_control( 'lunara_site_title', array(
        'label'    => __( 'Site Title Text', 'lunara-film' ),
        'section'  => 'lunara_header_options',
        'type'     => 'text',
    ) );
    
    // Show/Hide Site Title
    $wp_customize->add_setting( 'lunara_show_site_title', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ) );
    
    $wp_customize->add_control( 'lunara_show_site_title', array(
        'label'    => __( 'Show Site Title', 'lunara-film' ),
        'section'  => 'lunara_header_options',
        'type'     => 'checkbox',
    ) );
    
    // Show/Hide Logo
    $wp_customize->add_setting( 'lunara_show_logo', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ) );
    
    $wp_customize->add_control( 'lunara_show_logo', array(
        'label'    => __( 'Show Logo (set in Site Identity)', 'lunara-film' ),
        'section'  => 'lunara_header_options',
        'type'     => 'checkbox',
    ) );

    // Header Spacing (fully editable without touching CSS)
    $wp_customize->add_setting( 'lunara_header_padding_y', array(
        'default'           => 24,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_header_padding_y', array(
        'label'       => __( 'Header Vertical Padding (px)', 'lunara-film' ),
        'section'     => 'lunara_header_options',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 8,
            'max'  => 48,
            'step' => 1,
        ),
    ) );

    $wp_customize->add_setting( 'lunara_logo_max_height', array(
        'default'           => 50,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_logo_max_height', array(
        'label'       => __( 'Logo Max Height (px)', 'lunara-film' ),
        'section'     => 'lunara_header_options',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 24,
            'max'  => 110,
            'step' => 1,
        ),
    ) );

    $header_number_controls = array(
        'lunara_header_max_width' => array(
            'label'   => __( 'Header Max Width (px)', 'lunara-film' ),
            'default' => 1480,
            'min'     => 960,
            'max'     => 1800,
            'step'    => 10,
        ),
        'lunara_header_side_padding' => array(
            'label'   => __( 'Header Side Padding (px)', 'lunara-film' ),
            'default' => 48,
            'min'     => 12,
            'max'     => 96,
            'step'    => 1,
        ),
        'lunara_header_nav_gap' => array(
            'label'   => __( 'Navigation Gap (px)', 'lunara-film' ),
            'default' => 26,
            'min'     => 8,
            'max'     => 56,
            'step'    => 1,
        ),
        'lunara_header_nav_size' => array(
            'label'   => __( 'Navigation Font Size (px)', 'lunara-film' ),
            'default' => 14,
            'min'     => 11,
            'max'     => 22,
            'step'    => 1,
        ),
        'lunara_header_title_size' => array(
            'label'   => __( 'Site Title Size (px)', 'lunara-film' ),
            'default' => 19,
            'min'     => 12,
            'max'     => 34,
            'step'    => 1,
        ),
    );

    foreach ( $header_number_controls as $setting => $control ) {
        $wp_customize->add_setting( $setting, array(
            'default'           => $control['default'],
            'sanitize_callback' => 'absint',
            'transport'         => 'refresh',
        ) );

        $wp_customize->add_control( $setting, array(
            'label'       => $control['label'],
            'section'     => 'lunara_header_options',
            'type'        => 'number',
            'input_attrs' => array(
                'min'  => $control['min'],
                'max'  => $control['max'],
                'step' => $control['step'],
            ),
        ) );
    }

    $wp_customize->add_setting( 'lunara_header_nav_tracking', array(
        'default'           => 0.12,
        'sanitize_callback' => 'lunara_sanitize_decimal',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_header_nav_tracking', array(
        'label'       => __( 'Navigation Letter Spacing (em)', 'lunara-film' ),
        'section'     => 'lunara_header_options',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 0,
            'max'  => 0.4,
            'step' => 0.01,
        ),
    ) );

    if ( class_exists( 'WP_Customize_Color_Control' ) ) {
        $header_color_controls = array(
            'lunara_header_background' => array(
                'label'   => __( 'Header Background', 'lunara-film' ),
                'default' => '#0a1520',
            ),
            'lunara_header_border_color' => array(
                'label'   => __( 'Header Border', 'lunara-film' ),
                'default' => '#223142',
            ),
            'lunara_header_link_color' => array(
                'label'   => __( 'Header Link Color', 'lunara-film' ),
                'default' => '#FAFBFC',
            ),
            'lunara_header_link_hover_color' => array(
                'label'   => __( 'Header Link Hover Color', 'lunara-film' ),
                'default' => '#e0c481',
            ),
        );

        foreach ( $header_color_controls as $setting => $control ) {
            $wp_customize->add_setting( $setting, array(
                'default'           => $control['default'],
                'sanitize_callback' => 'sanitize_hex_color',
                'transport'         => 'refresh',
            ) );

            $wp_customize->add_control(
                new WP_Customize_Color_Control(
                    $wp_customize,
                    $setting,
                    array(
                        'label'   => $control['label'],
                        'section' => 'lunara_header_options',
                    )
                )
            );
        }
    }

    $wp_customize->add_section(
        'lunara_global_design_options',
        array(
            'title'       => __( 'Lunara Global Design', 'lunara-film' ),
            'priority'    => 31,
            'description' => __( 'Control the sitewide palette, typography, shell width, and card styling without editing CSS.', 'lunara-film' ),
        )
    );

    $global_design_controls = array(
        array(
            'setting'  => 'lunara_shell_content_width',
            'default'  => 1360,
            'label'    => __( 'Content Width (px)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 960, 'max' => 1800, 'step' => 10 ),
        ),
        array(
            'setting'  => 'lunara_shell_side_padding',
            'default'  => 28,
            'label'    => __( 'Content Side Padding (px)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 12, 'max' => 96, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_surface_radius',
            'default'  => 28,
            'label'    => __( 'Card Corner Radius (px)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 0, 'max' => 48, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_body_font_size',
            'default'  => 17,
            'label'    => __( 'Body Font Size (px)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 14, 'max' => 24, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_body_line_height',
            'default'  => 1.7,
            'label'    => __( 'Body Line Height', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'lunara_sanitize_decimal',
            'attrs'    => array( 'min' => 1.3, 'max' => 2.2, 'step' => 0.05 ),
        ),
        array(
            'setting'  => 'lunara_section_title_size',
            'default'  => 34,
            'label'    => __( 'Section Heading Size (px)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 24, 'max' => 64, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_hero_title_size',
            'default'  => 72,
            'label'    => __( 'Hero Title Size (px)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 40, 'max' => 120, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_hero_copy_size',
            'default'  => 19,
            'label'    => __( 'Hero Copy Size (px)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 15, 'max' => 30, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_kicker_size',
            'default'  => 12,
            'label'    => __( 'Kicker Size (px)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 10, 'max' => 18, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_kicker_tracking',
            'default'  => 0.16,
            'label'    => __( 'Kicker Letter Spacing (em)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'lunara_sanitize_decimal',
            'attrs'    => array( 'min' => 0.02, 'max' => 0.4, 'step' => 0.01 ),
        ),
        array(
            'setting'     => 'lunara_heading_font_family',
            'default'     => '',
            'label'       => __( 'Heading Font Stack', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'lunara_sanitize_font_stack',
            'description' => __( 'Enter a loaded font stack or leave blank to keep the current theme font.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_body_font_family',
            'default'     => '',
            'label'       => __( 'Body Font Stack', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'lunara_sanitize_font_stack',
            'description' => __( 'Enter a loaded font stack or leave blank to keep the current theme font.', 'lunara-film' ),
        ),
    );

    foreach ( $global_design_controls as $control ) {
        $wp_customize->add_setting(
            $control['setting'],
            array(
                'default'           => $control['default'],
                'sanitize_callback' => $control['sanitize'],
                'transport'         => 'refresh',
            )
        );

        $args = array(
            'label'       => $control['label'],
            'section'     => 'lunara_global_design_options',
            'type'        => $control['type'],
            'description' => ! empty( $control['description'] ) ? $control['description'] : '',
        );

        if ( ! empty( $control['attrs'] ) ) {
            $args['input_attrs'] = $control['attrs'];
        }

        $wp_customize->add_control( $control['setting'], $args );
    }

    if ( class_exists( 'WP_Customize_Color_Control' ) ) {
        $global_color_controls = array(
            'lunara_bg_primary' => array(
                'label'   => __( 'Primary Background', 'lunara-film' ),
                'default' => '#0a1520',
            ),
            'lunara_bg_secondary' => array(
                'label'   => __( 'Secondary Background', 'lunara-film' ),
                'default' => '#0f1d2e',
            ),
            'lunara_bg_card' => array(
                'label'   => __( 'Card Background', 'lunara-film' ),
                'default' => '#0f1d2e',
            ),
            'lunara_accent_color' => array(
                'label'   => __( 'Accent Color', 'lunara-film' ),
                'default' => '#c9a961',
            ),
            'lunara_accent_soft_color' => array(
                'label'   => __( 'Accent Highlight', 'lunara-film' ),
                'default' => '#e0c481',
            ),
            'lunara_text_color' => array(
                'label'   => __( 'Body Text Color', 'lunara-film' ),
                'default' => '#FAFBFC',
            ),
            'lunara_muted_text_color' => array(
                'label'   => __( 'Muted Text Color', 'lunara-film' ),
                'default' => '#A8A8B8',
            ),
            'lunara_border_color' => array(
                'label'   => __( 'Border Color', 'lunara-film' ),
                'default' => '#c9a961',
            ),
        );

        foreach ( $global_color_controls as $setting => $control ) {
            $wp_customize->add_setting(
                $setting,
                array(
                    'default'           => $control['default'],
                    'sanitize_callback' => 'sanitize_hex_color',
                    'transport'         => 'refresh',
                )
            );

            $wp_customize->add_control(
                new WP_Customize_Color_Control(
                    $wp_customize,
                    $setting,
                    array(
                        'label'   => $control['label'],
                        'section' => 'lunara_global_design_options',
                    )
                )
            );
        }
    }

    // Lunara Debrief Section (signature controls)
    $wp_customize->add_section( 'lunara_debrief_options', array(
        'title'    => __( 'Lunara Debrief', 'lunara-film' ),
        'priority' => 32,
    ) );

        $wp_customize->add_setting( 'lunara_debrief_kicker_text', array(
        'default'           => 'A LUNARA FILM SIGNATURE',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_debrief_kicker_text', array(
        'label'   => __( 'Kicker Text', 'lunara-film' ),
        'section' => 'lunara_debrief_options',
        'type'    => 'text',
    ) );

    $wp_customize->add_section(
        'lunara_review_layout_options',
        array(
            'title'       => __( 'Lunara Review Layout', 'lunara-film' ),
            'priority'    => 32,
            'description' => __( 'Control the default feel of review singles the same way Blocksy lets you shape global templates.', 'lunara-film' ),
        )
    );

    $review_layout_controls = array(
        array(
            'setting'  => 'lunara_review_default_label',
            'default'  => 'Lunara Review',
            'label'    => __( 'Default Review Label', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'  => 'lunara_review_show_standfirst_default',
            'default'  => true,
            'label'    => __( 'Show Standfirst By Default', 'lunara-film' ),
            'type'     => 'checkbox',
            'sanitize' => 'wp_validate_boolean',
        ),
        array(
            'setting'  => 'lunara_review_show_where_card_default',
            'default'  => true,
            'label'    => __( 'Show Where To Watch Card By Default', 'lunara-film' ),
            'type'     => 'checkbox',
            'sanitize' => 'wp_validate_boolean',
        ),
        array(
            'setting'  => 'lunara_review_show_details_card_default',
            'default'  => true,
            'label'    => __( 'Show Review Details Card By Default', 'lunara-film' ),
            'type'     => 'checkbox',
            'sanitize' => 'wp_validate_boolean',
        ),
        array(
            'setting'  => 'lunara_review_debrief_width',
            'default'  => 1180,
            'label'    => __( 'Debrief Width (px)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 980, 'max' => 1600, 'step' => 10 ),
        ),
        array(
            'setting'  => 'lunara_review_debrief_poster_max',
            'default'  => 400,
            'label'    => __( 'Debrief Poster Max Width (px)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 260, 'max' => 520, 'step' => 10 ),
        ),
        array(
            'setting'  => 'lunara_review_debrief_signature_offset',
            'default'  => 56,
            'label'    => __( 'Signature Panel Alignment Offset (px)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 0, 'max' => 160, 'step' => 2 ),
        ),
    );

    foreach ( $review_layout_controls as $control ) {
        $wp_customize->add_setting(
            $control['setting'],
            array(
                'default'           => $control['default'],
                'sanitize_callback' => $control['sanitize'],
                'transport'         => 'refresh',
            )
        );

        $args = array(
            'label'   => $control['label'],
            'section' => 'lunara_review_layout_options',
            'type'    => $control['type'],
        );

        if ( ! empty( $control['attrs'] ) ) {
            $args['input_attrs'] = $control['attrs'];
        }

        $wp_customize->add_control( $control['setting'], $args );
    }

    $wp_customize->add_section( 'lunara_homepage_pulse_options', array(
        'title'    => __( 'Lunara Homepage Pulse', 'lunara-film' ),
        'priority' => 34,
    ) );

    $homepage_controls = array(
        array(
            'setting'  => 'lunara_home_hero_kicker',
            'default'  => 'LUNARA FILM',
            'label'    => __( 'Hero Kicker', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'  => 'lunara_home_hero_title',
            'default'  => get_bloginfo( 'name' ),
            'label'    => __( 'Hero Title', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'  => 'lunara_home_hero_copy',
            'default'  => 'Film criticism and a living Oscar ledger for readers who want cinema, and the record around it, taken seriously.',
            'label'    => __( 'Hero Sentence', 'lunara-film' ),
            'type'     => 'textarea',
            'sanitize' => 'sanitize_textarea_field',
        ),
        array(
            'setting'  => 'lunara_home_primary_cta_label',
            'default'  => 'Browse Reviews',
            'label'    => __( 'Primary Button Label', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'  => 'lunara_home_primary_cta_url',
            'default'  => home_url( '/reviews/' ),
            'label'    => __( 'Primary Button URL', 'lunara-film' ),
            'type'     => 'url',
            'sanitize' => 'esc_url_raw',
        ),
        array(
            'setting'  => 'lunara_home_secondary_cta_label',
            'default'  => 'Explore the Oscar Ledger',
            'label'    => __( 'Secondary Button Label', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'  => 'lunara_home_secondary_cta_url',
            'default'  => home_url( '/oscars/' ),
            'label'    => __( 'Secondary Button URL', 'lunara-film' ),
            'type'     => 'url',
            'sanitize' => 'esc_url_raw',
        ),
        array(
            'setting'  => 'lunara_home_database_heading',
            'default'  => 'The Lunara Oscar Ledger',
            'label'    => __( 'Oscar Spotlight Heading', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'  => 'lunara_home_database_copy',
            'default'  => 'This is not just a review blog. The Lunara Oscar Ledger is a research-driven archive of Academy Awards history, structured so readers can move from iconic films to categories, people, companies, and ceremony context without getting lost in a dead wall of data.',
            'label'    => __( 'Oscar Spotlight Copy', 'lunara-film' ),
            'type'     => 'textarea',
            'sanitize' => 'sanitize_textarea_field',
        ),
    );

    foreach ( $homepage_controls as $control ) {
        $wp_customize->add_setting(
            $control['setting'],
            array(
                'default'           => $control['default'],
                'sanitize_callback' => $control['sanitize'],
                'transport'         => 'refresh',
            )
        );

        $wp_customize->add_control(
            $control['setting'],
            array(
                'label'       => $control['label'],
                'section'     => 'lunara_homepage_pulse_options',
                'type'        => $control['type'],
                'description' => ! empty( $control['description'] ) ? $control['description'] : '',
            )
        );
    }

    for ( $index = 1; $index <= 3; $index++ ) {
        $fields = array(
            'kicker' => array(
                'label'       => sprintf( __( 'Pulse Card %d Kicker', 'lunara-film' ), $index ),
                'type'        => 'text',
                'sanitize'    => 'sanitize_text_field',
                'description' => __( 'Leave blank to use the live database-driven card.', 'lunara-film' ),
            ),
            'title' => array(
                'label'       => sprintf( __( 'Pulse Card %d Title', 'lunara-film' ), $index ),
                'type'        => 'text',
                'sanitize'    => 'sanitize_text_field',
                'description' => __( 'Leave blank to use the live database-driven card.', 'lunara-film' ),
            ),
            'copy' => array(
                'label'       => sprintf( __( 'Pulse Card %d Copy', 'lunara-film' ), $index ),
                'type'        => 'textarea',
                'sanitize'    => 'sanitize_textarea_field',
                'description' => __( 'Use this when you want to foreground a first, milestone, or sharper editorial angle.', 'lunara-film' ),
            ),
            'link_label' => array(
                'label'       => sprintf( __( 'Pulse Card %d Button Label', 'lunara-film' ), $index ),
                'type'        => 'text',
                'sanitize'    => 'sanitize_text_field',
                'description' => __( 'Optional override for the card button label.', 'lunara-film' ),
            ),
            'link_url' => array(
                'label'       => sprintf( __( 'Pulse Card %d Button URL', 'lunara-film' ), $index ),
                'type'        => 'url',
                'sanitize'    => 'esc_url_raw',
                'description' => __( 'Optional override for where the card should go.', 'lunara-film' ),
            ),
        );

        foreach ( $fields as $suffix => $field ) {
            $setting = sprintf( 'lunara_home_pulse_card_%d_%s', $index, $suffix );

            $wp_customize->add_setting(
                $setting,
                array(
                    'default'           => '',
                    'sanitize_callback' => $field['sanitize'],
                    'transport'         => 'refresh',
                )
            );

            $wp_customize->add_control(
                $setting,
                array(
                    'label'       => $field['label'],
                    'section'     => 'lunara_homepage_pulse_options',
                    'type'        => $field['type'],
                    'description' => $field['description'],
                )
            );
        }
    }

    $wp_customize->add_section(
        'lunara_homepage_layout_options',
        array(
            'title'       => __( 'Lunara Homepage Layout', 'lunara-film' ),
            'priority'    => 35,
            'description' => __( 'Control the homepage width, spacing, section visibility, and section order without editing templates.', 'lunara-film' ),
        )
    );

    $homepage_layout_controls = array(
        array(
            'setting'  => 'lunara_home_max_width',
            'default'  => 1400,
            'label'    => __( 'Homepage Max Width (px)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 960, 'max' => 1800, 'step' => 10 ),
        ),
        array(
            'setting'  => 'lunara_home_side_padding',
            'default'  => 40,
            'label'    => __( 'Homepage Side Padding (px)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 12, 'max' => 96, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_home_section_gap',
            'default'  => 72,
            'label'    => __( 'Homepage Section Gap (px)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 24, 'max' => 140, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_home_hero_top_padding',
            'default'  => 92,
            'label'    => __( 'Hero Top Padding (px)', 'lunara-film' ),
            'type'     => 'number',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 24, 'max' => 180, 'step' => 1 ),
        ),
        array(
            'setting'     => 'lunara_home_section_order',
            'default'     => implode( ',', lunara_get_home_section_slugs() ),
            'label'       => __( 'Section Order', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'lunara_sanitize_home_section_order',
            'description' => __( 'Comma-separated slugs: hero, featured, dispatch, oscar-spotlight, database, ledger, deep-cuts, latest-reviews.', 'lunara-film' ),
        ),
    );

    foreach ( $homepage_layout_controls as $control ) {
        $wp_customize->add_setting(
            $control['setting'],
            array(
                'default'           => $control['default'],
                'sanitize_callback' => $control['sanitize'],
                'transport'         => 'refresh',
            )
        );

        $args = array(
            'label'       => $control['label'],
            'section'     => 'lunara_homepage_layout_options',
            'type'        => $control['type'],
            'description' => ! empty( $control['description'] ) ? $control['description'] : '',
        );

        if ( ! empty( $control['attrs'] ) ) {
            $args['input_attrs'] = $control['attrs'];
        }

        $wp_customize->add_control( $control['setting'], $args );
    }

    $homepage_toggle_controls = array(
        'lunara_home_show_hero'            => __( 'Show Hero', 'lunara-film' ),
        'lunara_home_show_featured'        => __( 'Show Featured Reviews', 'lunara-film' ),
        'lunara_home_show_dispatch'        => __( 'Show Homepage Journal', 'lunara-film' ),
        'lunara_home_show_oscar_spotlight' => __( 'Show Oscar Spotlight', 'lunara-film' ),
        'lunara_home_show_database'        => __( 'Show Database Spotlight', 'lunara-film' ),
        'lunara_home_show_ledger'          => __( 'Show From the Ledger', 'lunara-film' ),
        'lunara_home_show_deep_cuts'       => __( 'Show Deep Cut Stats', 'lunara-film' ),
        'lunara_home_show_latest_reviews'  => __( 'Show Latest Reviews', 'lunara-film' ),
    );

    foreach ( $homepage_toggle_controls as $setting => $label ) {
        $wp_customize->add_setting( $setting, array(
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
            'transport'         => 'refresh',
        ) );

        $wp_customize->add_control( $setting, array(
            'label'   => $label,
            'section' => 'lunara_homepage_layout_options',
            'type'    => 'checkbox',
        ) );
    }

    $wp_customize->add_section(
        'lunara_homepage_editorial_options',
        array(
            'title'    => __( 'Lunara Homepage Curation', 'lunara-film' ),
            'priority' => 35,
        )
    );

    $homepage_editorial_controls = array(
        array(
            'setting'     => 'lunara_home_featured_reviews_kicker',
            'default'     => 'Featured Reviews',
            'label'       => __( 'Featured Reviews Kicker', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Controls the small label above the homepage review carousel.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_featured_reviews_heading',
            'default'     => 'Featured Criticism',
            'label'       => __( 'Featured Reviews Heading', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Use this to rename the homepage review showcase without editing templates.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_hero_review_ids',
            'default'     => '',
            'label'       => __( 'Top Homepage Review IDs or URLs', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
            'description' => __( 'Paste published review IDs or full URLs, one per line to control the very top homepage showcase. Leave blank to inherit the featured shelf list.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_featured_review_ids',
            'default'     => '',
            'label'       => __( 'Featured Shelf Review IDs or URLs', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
            'description' => __( 'Paste published review IDs or full URLs, one per line to control the lower Featured Criticism shelf. Leave blank to fall back to the featured tag.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_ledger_kicker',
            'default'     => 'From the Ledger',
            'label'       => __( 'Deep Cuts Kicker', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Controls the label above the Oscar history carousel.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_ledger_heading',
            'default'     => 'Oscar Ledger Highlights',
            'label'       => __( 'Deep Cuts Heading', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Rename the historical Oscar spotlight lane from the Customizer.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_kicker',
            'default'     => 'Journal',
            'label'       => __( 'Journal Kicker', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Use this for the mixed editorial lane for news, reactions, essays, and podcast posts.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_heading',
            'default'     => 'News, Reactions, and the Lunara Journal',
            'label'       => __( 'Journal Heading', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'This is the homepage headline for your non-review editorial stream.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_copy',
            'default'     => 'Use this lane for reported news, quick reactions, larger think pieces, and podcast episodes without flattening everything into review coverage.',
            'label'       => __( 'Journal Intro Copy', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
            'description' => __( 'A short statement that tells readers what kinds of writing and audio live here.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_category_slugs',
            'default'     => 'news,think-pieces,reactions,podcast',
            'label'       => __( 'Journal Category Slugs', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Comma-separated category slugs to pull into the homepage Journal lane.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_post_ids',
            'default'     => '',
            'label'       => __( 'Journal Post IDs or URLs', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
            'description' => __( 'Paste published post IDs or URLs, one per line, when you want full manual control over the homepage Journal lane.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_button_label',
            'default'     => 'Open the Journal',
            'label'       => __( 'Journal Button Label', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'The call-to-action beside the Journal section heading.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_button_url',
            'default'     => '',
            'label'       => __( 'Journal Button URL', 'lunara-film' ),
            'type'        => 'url',
            'sanitize'    => 'esc_url_raw',
            'description' => __( 'Optional override. Leave blank to link to the first matching category archive or the blog index.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_latest_reviews_heading',
            'default'     => 'New Writing',
            'label'       => __( 'Latest Reviews Heading', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Rename the lower review grid without touching the template.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_latest_reviews_button_label',
            'default'     => 'View All',
            'label'       => __( 'Latest Reviews Button Label', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Controls the archive button for the latest reviews section.', 'lunara-film' ),
        ),
    );

    foreach ( $homepage_editorial_controls as $control ) {
        $wp_customize->add_setting(
            $control['setting'],
            array(
                'default'           => $control['default'],
                'sanitize_callback' => $control['sanitize'],
                'transport'         => 'refresh',
            )
        );

        $wp_customize->add_control(
            $control['setting'],
            array(
                'label'       => $control['label'],
                'section'     => 'lunara_homepage_editorial_options',
                'type'        => $control['type'],
                'description' => $control['description'],
            )
        );
    }

    $wp_customize->add_section(
        'lunara_editorial_archive_options',
        array(
            'title'       => __( 'Lunara Editorial Archives', 'lunara-film' ),
            'priority'    => 36,
            'description' => __( 'Shape the review archive and the journal/news archive without editing templates.', 'lunara-film' ),
        )
    );

    $editorial_archive_controls = array(
        array(
            'setting'  => 'lunara_reviews_archive_kicker',
            'default'  => 'Review Archive',
            'label'    => __( 'Reviews Archive Kicker', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'  => 'lunara_reviews_archive_title',
            'default'  => 'The Review Archive',
            'label'    => __( 'Reviews Archive Title', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_reviews_archive_copy',
            'default'     => 'Poster-led criticism, cataloged so readers can move through the writing as an evolving record instead of a pile of disconnected posts.',
            'label'       => __( 'Reviews Archive Intro', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
            'description' => __( 'This appears on the main reviews archive page.', 'lunara-film' ),
        ),
        array(
            'setting'  => 'lunara_journal_archive_kicker',
            'default'  => 'The Journal',
            'label'    => __( 'Journal Archive Kicker', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'  => 'lunara_journal_archive_title',
            'default'  => 'News, Reactions, Essays, and Audio',
            'label'    => __( 'Journal Archive Title', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_archive_copy',
            'default'     => 'This is the live editorial lane for news, quick reactions, longer think pieces, interviews, and podcast writing that should stand beside the reviews without being mistaken for them.',
            'label'       => __( 'Journal Archive Intro', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
            'description' => __( 'This appears on the Journal archive and any legacy standard-post archive when no archive intro is available.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_journal_archive_source_label',
            'default'     => 'Breaking / Industry / Festival',
            'label'       => __( 'Journal Coverage Focus', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Short label in the Journal at-a-glance panel.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_journal_archive_lead_rail_kicker',
            'default'     => 'In Rotation',
            'label'       => __( 'Journal Support Kicker', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_archive_lead_rail_title',
            'default'     => 'What The Signal Is Holding Beside The Lead',
            'label'       => __( 'Journal Support Title', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_archive_lead_rail_copy',
            'default'     => 'A tighter support rail so the news archive feels like a live editorial desk, not a generic feed.',
            'label'       => __( 'Journal Support Copy', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
        ),
        array(
            'setting'     => 'lunara_journal_archive_run_kicker',
            'default'     => 'Archive Run',
            'label'       => __( 'Journal Run Kicker', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_archive_run_title',
            'default'     => 'More From The Journal',
            'label'       => __( 'Journal Run Title', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_archive_run_copy',
            'default'     => 'The broader run stays browseable and poster-led, but now lives inside the same deliberate editorial grammar as the rest of Lunara.',
            'label'       => __( 'Journal Run Copy', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
        ),
        array(
            'setting'     => 'lunara_journal_archive_empty_title',
            'default'     => 'The desk is on standby, not off.',
            'label'       => __( 'Journal Empty Title', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_archive_empty_copy',
            'default'     => 'When the next dispatch lands, it will appear here. Until then, the rest of Lunara is still moving.',
            'label'       => __( 'Journal Empty Copy', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
        ),
        array(
            'setting'     => 'lunara_journal_archive_empty_note_title',
            'default'     => 'Breaking items, industry shifts, and the stories worth moving on quickly.',
            'label'       => __( 'Journal Empty Note Title', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_archive_empty_note_copy',
            'default'     => 'This lane is for fresh movement across the film landscape: production turns, box office signals, festival currents, awards tremors, and the kinds of developments that keep Lunara alive between the longer critical pieces.',
            'label'       => __( 'Journal Empty Note Copy', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
        ),
        array(
            'setting'     => 'lunara_journal_archive_standby_kicker',
            'default'     => 'Stay On Signal',
            'label'       => __( 'Journal Standby Kicker', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_archive_standby_title',
            'default'     => 'The publication is still alive around the dispatch desk.',
            'label'       => __( 'Journal Standby Title', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_archive_standby_copy',
            'default'     => 'If the news lane is waiting on the next movement, the criticism, ledger, and front door are still fully in motion.',
            'label'       => __( 'Journal Standby Copy', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
        ),
        array(
            'setting'     => 'lunara_journal_archive_live_section_order',
            'default'     => 'spotlight,run,pagination',
            'label'       => __( 'Journal Live Section Order', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'lunara_sanitize_journal_live_section_order',
            'description' => __( 'Comma-separated: spotlight, run, pagination', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_journal_archive_empty_section_order',
            'default'     => 'intro,standby',
            'label'       => __( 'Journal Empty Section Order', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'lunara_sanitize_journal_empty_section_order',
            'description' => __( 'Comma-separated: intro, standby', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_journal_archive_standby_card_order',
            'default'     => 'reviews,ledger,home',
            'label'       => __( 'Journal Standby Card Order', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'lunara_sanitize_journal_standby_card_order',
            'description' => __( 'Comma-separated: reviews, ledger, home', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_journal_standby_reviews_kicker',
            'default'     => 'Criticism',
            'label'       => __( 'Standby Reviews Kicker', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_standby_reviews_title',
            'default'     => 'Browse The Review Archive',
            'label'       => __( 'Standby Reviews Title', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_standby_reviews_copy',
            'default'     => 'Move through the poster-led criticism system while the news desk waits for the next live item.',
            'label'       => __( 'Standby Reviews Copy', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
        ),
        array(
            'setting'     => 'lunara_journal_standby_reviews_button',
            'default'     => 'Enter The Reviews',
            'label'       => __( 'Standby Reviews Button', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_standby_reviews_url',
            'default'     => '',
            'label'       => __( 'Standby Reviews URL', 'lunara-film' ),
            'type'        => 'url',
            'sanitize'    => 'esc_url_raw',
        ),
        array(
            'setting'     => 'lunara_journal_standby_ledger_kicker',
            'default'     => 'Ledger',
            'label'       => __( 'Standby Ledger Kicker', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_standby_ledger_title',
            'default'     => 'Step Into The Oscar Ledger',
            'label'       => __( 'Standby Ledger Title', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_standby_ledger_copy',
            'default'     => 'Follow categories, ceremonies, records, and title profiles without leaving the Lunara world.',
            'label'       => __( 'Standby Ledger Copy', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
        ),
        array(
            'setting'     => 'lunara_journal_standby_ledger_button',
            'default'     => 'Open The Ledger',
            'label'       => __( 'Standby Ledger Button', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_standby_ledger_url',
            'default'     => '',
            'label'       => __( 'Standby Ledger URL', 'lunara-film' ),
            'type'        => 'url',
            'sanitize'    => 'esc_url_raw',
        ),
        array(
            'setting'     => 'lunara_journal_standby_home_kicker',
            'default'     => 'Front Door',
            'label'       => __( 'Standby Home Kicker', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_standby_home_title',
            'default'     => 'Return To The Live Homepage',
            'label'       => __( 'Standby Home Title', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_standby_home_copy',
            'default'     => 'Jump back into the main signal mix: featured criticism, the current pulse, and the latest Oscar movement.',
            'label'       => __( 'Standby Home Copy', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
        ),
        array(
            'setting'     => 'lunara_journal_standby_home_button',
            'default'     => 'Go To Lunara',
            'label'       => __( 'Standby Home Button', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_journal_standby_home_url',
            'default'     => '',
            'label'       => __( 'Standby Home URL', 'lunara-film' ),
            'type'        => 'url',
            'sanitize'    => 'esc_url_raw',
        ),
    );

    foreach ( $editorial_archive_controls as $control ) {
        $wp_customize->add_setting(
            $control['setting'],
            array(
                'default'           => $control['default'],
                'sanitize_callback' => $control['sanitize'],
                'transport'         => 'refresh',
            )
        );

        $wp_customize->add_control(
            $control['setting'],
            array(
                'label'       => $control['label'],
                'section'     => 'lunara_editorial_archive_options',
                'type'        => $control['type'],
                'description' => ! empty( $control['description'] ) ? $control['description'] : '',
            )
        );
    }

    $wp_customize->add_section(
        'lunara_oscars_portal_options',
        array(
            'title'       => __( 'Lunara Oscars Portal', 'lunara-film' ),
            'priority'    => 37,
            'description' => __( 'Control the dedicated /oscars/ landing page without editing templates.', 'lunara-film' ),
        )
    );

    $oscars_portal_controls = array(
        array(
            'setting'     => 'lunara_oscars_portal_kicker',
            'default'     => 'The Lunara Oscar Ledger',
            'label'       => __( 'Portal Kicker', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Small label above the Oscars portal headline.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_oscars_portal_title',
            'default'     => 'Academy Awards history, treated like a living editorial system.',
            'label'       => __( 'Portal Title', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Main headline for the /oscars/ front door.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_oscars_portal_copy',
            'default'     => 'Move from a winning film to the people behind it, from one category to the ceremony around it, and from the ledger straight into Lunara criticism without ever hitting a dead wall of data.',
            'label'       => __( 'Portal Intro Copy', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
            'description' => __( 'Intro paragraph for the Oscars portal hero.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_oscars_portal_explore_heading',
            'default'     => 'Start anywhere in the ledger.',
            'label'       => __( 'Explore Section Heading', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Headline for the section that links deeper into ceremonies, categories, and the database.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_oscars_portal_reviews_heading',
            'default'     => 'Reviews Inside the Ledger',
            'label'       => __( 'Linked Reviews Heading', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Headline above the latest reviews connected to Oscar film pages.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_oscars_portal_deep_cuts_heading',
            'default'     => 'Oscar Deep Cuts',
            'label'       => __( 'Deep Cuts Heading', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Headline above the rotating stats and historical facts grid.', 'lunara-film' ),
        ),
    );

    foreach ( $oscars_portal_controls as $control ) {
        $wp_customize->add_setting(
            $control['setting'],
            array(
                'default'           => $control['default'],
                'sanitize_callback' => $control['sanitize'],
                'transport'         => 'refresh',
            )
        );

        $wp_customize->add_control(
            $control['setting'],
            array(
                'label'       => $control['label'],
                'section'     => 'lunara_oscars_portal_options',
                'type'        => $control['type'],
                'description' => ! empty( $control['description'] ) ? $control['description'] : '',
            )
        );
    }

    // Ledger Stories IMDb ID overrides (Feature 3).
    $wp_customize->add_section( 'lunara_homepage_ledger_options', array(
        'title'       => __( 'Lunara Ledger Stories', 'lunara-film' ),
        'priority'    => 38,
        'description' => __( 'Override the "From the Ledger" homepage cards with specific IMDb title IDs. Leave blank to use automatic rotation from the database.', 'lunara-film' ),
    ) );

    for ( $slot = 1; $slot <= 4; $slot++ ) {
        $setting_key = 'lunara_home_ledger_card_' . $slot . '_imdb_id';

        $wp_customize->add_setting( $setting_key, array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ) );

        $wp_customize->add_control( $setting_key, array(
            'label'       => sprintf( __( 'Ledger Card %d IMDb ID', 'lunara-film' ), $slot ),
            'section'     => 'lunara_homepage_ledger_options',
            'type'        => 'text',
            'description' => __( 'e.g. tt0111161. Leave blank for automatic rotation.', 'lunara-film' ),
        ) );
    }

    // â”€â”€ Footer Design Controls â”€â”€
    $wp_customize->add_section( 'lunara_footer_options', array(
        'title'    => __( 'Lunara Footer', 'lunara-film' ),
        'priority' => 34,
    ) );

    $footer_controls = array(
        array(
            'setting'  => 'lunara_footer_tagline',
            'default'  => 'Film criticism and a living Oscar ledger.',
            'label'    => __( 'Footer Tagline', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'  => 'lunara_footer_show_logo',
            'default'  => true,
            'label'    => __( 'Show Logo in Footer', 'lunara-film' ),
            'type'     => 'checkbox',
            'sanitize' => 'absint',
        ),
        array(
            'setting'  => 'lunara_footer_col1_heading',
            'default'  => 'Editorial',
            'label'    => __( 'Column 1 Heading', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'  => 'lunara_footer_col2_heading',
            'default'  => 'Oscar Ledger',
            'label'    => __( 'Column 2 Heading', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'  => 'lunara_footer_col3_heading',
            'default'  => 'Utility',
            'label'    => __( 'Column 3 Heading', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'  => 'lunara_footer_copyright',
            'default'  => 'Lunara Film',
            'label'    => __( 'Copyright Name', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
    );

    foreach ( $footer_controls as $fc ) {
        $wp_customize->add_setting( $fc['setting'], array(
            'default'           => $fc['default'],
            'sanitize_callback' => $fc['sanitize'],
            'transport'         => 'refresh',
        ) );
        $args = array(
            'label'   => $fc['label'],
            'section' => 'lunara_footer_options',
            'type'    => $fc['type'],
        );
        $wp_customize->add_control( $fc['setting'], $args );
    }
}
}
add_action( 'customize_register', 'lunara_customize_register' );

/**
 * Print runtime CSS for Lunara design controls.
 */
if ( ! function_exists( 'lunara_output_runtime_customizer_css' ) ) {
function lunara_output_runtime_customizer_css() {
    $header_padding_y = max( 8, min( 48, absint( get_theme_mod( 'lunara_header_padding_y', 24 ) ) ) );
    $logo_max_height  = max( 24, min( 110, absint( get_theme_mod( 'lunara_logo_max_height', 56 ) ) ) );
    $header_max_width = max( 960, min( 1800, absint( get_theme_mod( 'lunara_header_max_width', 1480 ) ) ) );
    $header_side_pad  = max( 12, min( 96, absint( get_theme_mod( 'lunara_header_side_padding', 48 ) ) ) );
    $nav_gap          = max( 8, min( 56, absint( get_theme_mod( 'lunara_header_nav_gap', 26 ) ) ) );
    $nav_size         = max( 11, min( 22, absint( get_theme_mod( 'lunara_header_nav_size', 14 ) ) ) );
    $title_size       = max( 12, min( 34, absint( get_theme_mod( 'lunara_header_title_size', 19 ) ) ) );
    $nav_tracking     = max( 0, min( 0.4, (float) get_theme_mod( 'lunara_header_nav_tracking', 0.12 ) ) );
    $header_bg        = sanitize_hex_color( get_theme_mod( 'lunara_header_background', '#0a1520' ) ) ?: '#0a1520';
    $header_border    = sanitize_hex_color( get_theme_mod( 'lunara_header_border_color', '#A8A8B8' ) ) ?: '#A8A8B8';
    $header_link      = sanitize_hex_color( get_theme_mod( 'lunara_header_link_color', '#e0c481' ) ) ?: '#e0c481';
    $header_hover     = sanitize_hex_color( get_theme_mod( 'lunara_header_link_hover_color', '#FAFBFC' ) ) ?: '#FAFBFC';
    $bg_primary       = sanitize_hex_color( get_theme_mod( 'lunara_bg_primary', '#0a1520' ) ) ?: '#0a1520';
    $bg_secondary     = sanitize_hex_color( get_theme_mod( 'lunara_bg_secondary', '#0f1d2e' ) ) ?: '#0f1d2e';
    $bg_card          = sanitize_hex_color( get_theme_mod( 'lunara_bg_card', '#0f1d2e' ) ) ?: '#0f1d2e';
    $accent           = sanitize_hex_color( get_theme_mod( 'lunara_accent_color', '#c9a961' ) ) ?: '#c9a961';
    $accent_soft      = sanitize_hex_color( get_theme_mod( 'lunara_accent_soft_color', '#e0c481' ) ) ?: '#e0c481';
    $text_color       = sanitize_hex_color( get_theme_mod( 'lunara_text_color', '#FAFBFC' ) ) ?: '#FAFBFC';
    $muted_text       = sanitize_hex_color( get_theme_mod( 'lunara_muted_text_color', '#A8A8B8' ) ) ?: '#A8A8B8';
    $border_color     = sanitize_hex_color( get_theme_mod( 'lunara_border_color', '#c9a961' ) ) ?: '#c9a961';
    $shell_max        = max( 960, min( 1800, absint( get_theme_mod( 'lunara_shell_content_width', 1360 ) ) ) );
    $shell_pad        = max( 12, min( 96, absint( get_theme_mod( 'lunara_shell_side_padding', 28 ) ) ) );
    $surface_radius   = max( 0, min( 48, absint( get_theme_mod( 'lunara_surface_radius', 28 ) ) ) );
    $body_size        = max( 14, min( 24, absint( get_theme_mod( 'lunara_body_font_size', 17 ) ) ) );
    $body_line_height = max( 1.3, min( 2.2, (float) get_theme_mod( 'lunara_body_line_height', 1.7 ) ) );
    $section_title    = max( 24, min( 64, absint( get_theme_mod( 'lunara_section_title_size', 34 ) ) ) );
    $hero_title       = max( 40, min( 120, absint( get_theme_mod( 'lunara_hero_title_size', 72 ) ) ) );
    $hero_copy        = max( 15, min( 30, absint( get_theme_mod( 'lunara_hero_copy_size', 19 ) ) ) );
    $kicker_size      = max( 10, min( 18, absint( get_theme_mod( 'lunara_kicker_size', 12 ) ) ) );
    $kicker_track     = max( 0.02, min( 0.4, (float) get_theme_mod( 'lunara_kicker_tracking', 0.16 ) ) );
    $heading_font     = lunara_sanitize_font_stack( get_theme_mod( 'lunara_heading_font_family', '' ) );
    $body_font        = lunara_sanitize_font_stack( get_theme_mod( 'lunara_body_font_family', '' ) );
    $border_alpha     = lunara_hex_to_rgba( $border_color, 0.24 );
    $heading_font_var = '' !== $heading_font ? $heading_font : 'Georgia, "Times New Roman", "Iowan Old Style", "Palatino Linotype", serif';
    $body_font_var    = '' !== $body_font ? $body_font : 'Georgia, "Times New Roman", "Iowan Old Style", "Palatino Linotype", serif';

    $home_max_width   = max( 960, min( 1800, absint( get_theme_mod( 'lunara_home_max_width', 1400 ) ) ) );
    $home_side_pad    = max( 12, min( 96, absint( get_theme_mod( 'lunara_home_side_padding', 40 ) ) ) );
    $home_gap         = max( 24, min( 140, absint( get_theme_mod( 'lunara_home_section_gap', 72 ) ) ) );
    $hero_top_padding = max( 24, min( 180, absint( get_theme_mod( 'lunara_home_hero_top_padding', 92 ) ) ) );
    $review_debrief_width   = max( 980, min( 1600, absint( get_theme_mod( 'lunara_review_debrief_width', 1180 ) ) ) );
    $review_poster_max      = max( 260, min( 520, absint( get_theme_mod( 'lunara_review_debrief_poster_max', 400 ) ) ) );
    $review_signature_shift = max( 0, min( 160, absint( get_theme_mod( 'lunara_review_debrief_signature_offset', 56 ) ) ) );
    $journal_hero_title     = max( 48, min( 120, absint( get_theme_mod( 'lunara_journal_single_hero_title_size', 84 ) ) ) );
    $mobile_header_pad = min( $header_side_pad, 20 );
    $mobile_home_pad   = min( $home_side_pad, 24 );
    $mobile_shell_pad  = min( $shell_pad, 24 );
    $section_order     = lunara_get_home_section_order_map();
    $css               = '';

    $css .= ':root{';
    $css .= '--lunara-bg-primary:' . $bg_primary . ';';
    $css .= '--lunara-bg-deep:#0a1520;';
    $css .= '--lunara-bg-secondary:' . $bg_secondary . ';';
    $css .= '--lunara-bg-card:' . $bg_card . ';';
    $css .= '--lunara-gold:' . $accent . ';';
    $css .= '--lunara-gold-light:' . $accent_soft . ';';
    $css .= '--lunara-text:' . $text_color . ';';
    $css .= '--lunara-text-muted:' . $muted_text . ';';
    $css .= '--lunara-border:' . $border_alpha . ';';
    $css .= '--lunara-border-solid:' . $border_color . ';';
    $css .= '--lunara-glow-gold:rgba(201,169,97,0.16);';
    $css .= '--lunara-glow-blue:rgba(168,168,184,0.26);';
    $css .= '--lunara-glow-highlight:rgba(255,255,255,0.07);';
    $css .= '--lunara-shell-max:' . $shell_max . 'px;';
    $css .= '--lunara-shell-pad:' . $shell_pad . 'px;';
    $css .= '--lunara-surface-radius:' . $surface_radius . 'px;';
    $css .= '--lunara-body-size:' . $body_size . 'px;';
    $css .= '--lunara-body-line-height:' . $body_line_height . ';';
    $css .= '--lunara-section-title-size:' . $section_title . 'px;';
    $css .= '--lunara-hero-title-size:' . $hero_title . 'px;';
    $css .= '--lunara-hero-copy-size:' . $hero_copy . 'px;';
    $css .= '--lunara-kicker-size:' . $kicker_size . 'px;';
    $css .= '--lunara-kicker-track:' . $kicker_track . 'em;';
    $css .= '--lunara-header-pad:' . $header_padding_y . 'px;';
    $css .= '--lunara-logo-max:' . $logo_max_height . 'px;';
    $css .= '--lunara-header-max:' . $header_max_width . 'px;';
    $css .= '--lunara-header-side-pad:' . $header_side_pad . 'px;';
    $css .= '--lunara-header-nav-gap:' . $nav_gap . 'px;';
    $css .= '--lunara-header-nav-size:' . $nav_size . 'px;';
    $css .= '--lunara-header-title-size:' . $title_size . 'px;';
    $css .= '--lunara-header-nav-track:' . $nav_tracking . 'em;';
    $css .= '--lunara-home-max:' . $home_max_width . 'px;';
    $css .= '--lunara-home-pad:' . $home_side_pad . 'px;';
    $css .= '--lunara-home-gap:' . $home_gap . 'px;';
    $css .= '--lunara-home-hero-top:' . $hero_top_padding . 'px;';
    $css .= '--lunara-heading-font-stack:' . $heading_font_var . ';';
    $css .= '--lunara-body-font-stack:' . $body_font_var . ';';
    $css .= '--lunara-review-debrief-width:' . $review_debrief_width . 'px;';
    $css .= '--lunara-review-debrief-poster-max:' . $review_poster_max . 'px;';
    $css .= '--lunara-review-debrief-signature-offset:' . $review_signature_shift . 'px;';
    $css .= '--lunara-section-gap:' . $home_gap . 'px;';
    $css .= '}';

    $css .= 'body{background-color:var(--lunara-bg-primary)!important;background-image:radial-gradient(circle at top left,var(--lunara-glow-gold),transparent 28%),radial-gradient(circle at 80% 18%,var(--lunara-glow-blue),transparent 26%),linear-gradient(180deg,var(--lunara-bg-primary) 0%,var(--lunara-bg-deep) 100%)!important;background-attachment:fixed;color:var(--lunara-text)!important;font-size:var(--lunara-body-size);line-height:var(--lunara-body-line-height);}';
    $css .= '.site-content,.ct-content,.ct-container-full,.ct-page-title{background:transparent!important;}';
    $css .= '.lunara-section,.lunara-archive-page > .lunara-home-section,.lunara-editorial-single-page > .lunara-home-section,.lunara-oscars-portal > .lunara-home-section{max-width:var(--lunara-shell-max);margin-left:auto;margin-right:auto;padding-left:var(--lunara-shell-pad);padding-right:var(--lunara-shell-pad);}';
    $css .= '.lunara-home-section-title,.lunara-section-title,.lunara-home-pulse-title,.lunara-home-pulse-feature-heading,.lunara-poster-card-title,.lunara-dispatch-lead-title,.lunara-dispatch-rail-title,.lunara-home-winner-title,.lunara-home-pulse-note-title,.lunara-review-grid-title,.lunara-oscar-spotlight-text-panel h3{font-size:var(--lunara-section-title-size);}';
    $css .= '.lunara-home-hero-title{font-size:var(--lunara-hero-title-size);}';
    $css .= '.lunara-home-hero-copy{font-size:var(--lunara-hero-copy-size);line-height:var(--lunara-body-line-height);}';
    $css .= '.lunara-home-hero-kicker,.lunara-home-section-kicker,.lunara-poster-card-kicker,.lunara-home-pulse-kicker,.lunara-dispatch-type,.lunara-home-pulse-note-kicker{font-size:var(--lunara-kicker-size);letter-spacing:var(--lunara-kicker-track);}';
    $css .= '.lunara-home-section-summary,.lunara-poster-card-excerpt,.lunara-dispatch-lead-excerpt,.lunara-dispatch-rail-excerpt,.lunara-home-pulse-summary,.lunara-home-pulse-feature-copy,.lunara-home-pulse-note-copy,.lunara-oscar-spotlight-copy,.lunara-review-grid-meta,.lunara-poster-card-meta{font-size:var(--lunara-body-size);line-height:var(--lunara-body-line-height);}';
    $css .= '.lunara-home-pulse-card,.lunara-poster-card,.lunara-dispatch-lead,.lunara-dispatch-rail-card,.lunara-home-winner-card,.lunara-home-pulse-note,.lunara-review-grid-card,.lunara-oscar-spotlight-pill,.lunara-home-pulse-feature-card{border-color:var(--lunara-border);border-radius:var(--lunara-surface-radius);}';
    $css .= '.lunara-dispatch-lead,.lunara-dispatch-rail-card,.lunara-home-winner-card,.lunara-home-pulse-note,.lunara-review-grid-card,.lunara-home-pulse-feature-card{background:' . lunara_hex_to_rgba( $bg_card, 0.88 ) . ';}';
    $css .= '.lunara-home-pulse-card{background:linear-gradient(180deg,' . lunara_hex_to_rgba( $bg_card, 0.96 ) . ',' . lunara_hex_to_rgba( $bg_primary, 0.92 ) . ');}';
    $css .= '.lunara-home-pulse-card-top,.lunara-oscar-spotlight-layout,.lunara-home-pulse-feature-card{background:linear-gradient(135deg,' . lunara_hex_to_rgba( $bg_secondary, 0.96 ) . ',' . lunara_hex_to_rgba( $bg_primary, 0.98 ) . ');}';
    $css .= '.lunara-journal-cinematic-hero .lunara-review-single-title{font-size:clamp(2.6rem,5vw,' . $journal_hero_title . 'px);}';
    $css .= '@media (max-width:720px){.lunara-journal-cinematic-hero .lunara-review-single-title{font-size:clamp(2.2rem,8.6vw,' . max( 42, (int) round( $journal_hero_title * 0.62 ) ) . 'px);}}';

    if ( '' !== $body_font ) {
        $css .= 'body,.lunara-front-page,.lunara-archive-page{font-family:' . $body_font . ';}';
    }

    if ( '' !== $heading_font ) {
        $css .= '.lunara-home-hero-title,.lunara-home-section-title,.lunara-section-title,.lunara-home-pulse-title,.lunara-home-pulse-feature-heading,.lunara-poster-card-title,.lunara-dispatch-lead-title,.lunara-dispatch-rail-title,.lunara-home-winner-title,.lunara-home-pulse-note-title,.lunara-review-grid-title,.lunara-oscar-spotlight-text-panel h3{font-family:' . $heading_font . ';}';
    }

    /* Lunara header (header.php) â€” color + spacing from customizer controls */
    $css .= '.lunara-header{background:' . $header_bg . ';border-bottom:1px solid ' . $header_border . ';}';
    $css .= '.lunara-header .lunara-container{max-width:var(--lunara-header-max);padding-left:var(--lunara-header-side-pad);padding-right:var(--lunara-header-side-pad);}';
    $css .= '.lunara-header .site-title,.lunara-header .site-title a{font-size:var(--lunara-header-title-size);color:' . $header_link . ';}';
    $css .= '.lunara-header .lunara-nav-list{column-gap:var(--lunara-header-nav-gap);}';
    $css .= '.lunara-header .lunara-nav-list > li > a,.lunara-header .lunara-header-actions button{color:' . $header_link . ';font-size:var(--lunara-header-nav-size);letter-spacing:var(--lunara-header-nav-track);text-transform:uppercase;}';
    $css .= '.lunara-header .lunara-nav-list > li > a:hover,.lunara-header .lunara-header-actions button:hover{color:' . $header_hover . ';}';
    $css .= '.ct-header [data-row="middle"]{background:radial-gradient(circle at top center,rgba(201,169,97,.12),transparent 42%),linear-gradient(180deg,rgba(10,22,36,.94),rgba(4,11,20,.98));border-bottom:1px solid rgba(201,169,97,.18);box-shadow:0 14px 34px rgba(0,0,0,.18);backdrop-filter:blur(18px);}';
    $css .= '.ct-header .site-branding,.ct-header .site-logo-container{background:transparent!important;border:0!important;box-shadow:none!important;}';
    $css .= '.ct-header .site-logo-container img{filter:drop-shadow(0 10px 20px rgba(0,0,0,.2));}';
    $css .= '.ct-header .ct-menu-link,.ct-header .ct-header-trigger,.ct-header .ct-toggle,.ct-header [data-id="search"] .ct-label,.ct-header [data-id="search"] .ct-icon{color:' . $accent_soft . '!important;}';
    $css .= '.ct-header .ct-menu-link:hover,.ct-header .ct-header-trigger:hover,.ct-header .ct-toggle:hover,.ct-header [data-id="search"]:hover .ct-label,.ct-header [data-id="search"]:hover .ct-icon{color:' . $text_color . '!important;}';
    $css .= '.ct-footer,footer.site-footer{background:linear-gradient(180deg,' . lunara_hex_to_rgba( $bg_secondary, 0.92 ) . ',' . lunara_hex_to_rgba( $bg_primary, 0.98 ) . ');border-top:1px solid ' . $border_alpha . ';}';
    $css .= '.ct-footer .ct-container,footer.site-footer .ct-container{max-width:var(--lunara-shell-max);padding-left:var(--lunara-shell-pad);padding-right:var(--lunara-shell-pad);}';
    $css .= '.ct-footer a,footer.site-footer a{color:' . $text_color . ';}';
    $css .= '.ct-footer a:hover,footer.site-footer a:hover{color:' . $accent_soft . ';}';
    $css .= '.lunara-front-page,.lunara-archive-page,.lunara-editorial-single-page,.lunara-oscars-portal{max-width:var(--lunara-home-max);padding-left:var(--lunara-home-pad);padding-right:var(--lunara-home-pad);}';
    $css .= '.lunara-front-page,.lunara-editorial-single-page,.lunara-oscars-portal{gap:var(--lunara-home-gap);}';
    $css .= '.lunara-home-hero.is-minimal,.lunara-archive-hero,.lunara-journal-single-hero,.lunara-oscars-portal-hero{padding-top:var(--lunara-home-hero-top);}';

    foreach ( lunara_get_home_section_slugs() as $slug ) {
        $order = isset( $section_order[ $slug ] ) ? intval( $section_order[ $slug ] ) : 99;
        $css  .= '.lunara-front-page > .lunara-home-slot-' . $slug . '{order:' . $order . ';}';
    }

    if ( ! get_theme_mod( 'lunara_show_logo', true ) ) {
        $css .= '.lunara-header .site-logo-container{display:none !important;}';
    }

    if ( ! get_theme_mod( 'lunara_show_site_title', true ) ) {
        $css .= '.lunara-header .site-title{display:none !important;}';
    }

    /* Mobile responsive rules now handled entirely by style.css breakpoints (900px / 640px) */

    if ( '' === $css ) {
        return;
    }

    echo '<style id="lunara-runtime-customizer-css">' . $css . '</style>' . "\n";
}
}
if ( ! has_action( 'wp_head', 'lunara_output_runtime_customizer_css' ) ) {
    add_action( 'wp_head', 'lunara_output_runtime_customizer_css', 99 );
}

/**
 * Register Reviews Custom Post Type
 */
if ( ! defined( 'LUNARA_CORE_VERSION' ) && ! function_exists( 'lunara_register_reviews_cpt' ) ) {
    function lunara_register_reviews_cpt() {
        $args = array(
            'labels' => array(
                'name'          => 'Reviews',
                'singular_name' => 'Review',
                'add_new'       => 'Add New Review',
                'add_new_item'  => 'Add New Review',
                'edit_item'     => 'Edit Review',
                'menu_name'     => 'Reviews',
            ),
            'public'            => true,
            'has_archive'       => true,
            'rewrite'           => array( 'slug' => 'reviews' ),
            'menu_icon'         => 'dashicons-star-filled',
            'supports'          => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
            'taxonomies'        => array( 'category', 'post_tag' ),
            'show_in_rest'      => true,
        );
        register_post_type( 'review', $args );
    }
    add_action( 'init', 'lunara_register_reviews_cpt' );

    function lunara_register_review_single_rewrite() {
        add_rewrite_rule(
            '^reviews/([^/]+)/?$',
            'index.php?post_type=review&name=$matches[1]',
            'top'
        );
    }
    add_action( 'init', 'lunara_register_review_single_rewrite', 20 );

    function lunara_preserve_review_canonical( $redirect_url ) {
        if ( is_singular( 'review' ) ) {
            return false;
        }

        return $redirect_url;
    }
    add_filter( 'redirect_canonical', 'lunara_preserve_review_canonical', 10, 1 );

    /**
     * Flush rewrite rules on activation
     */
    function lunara_flush_rewrites() {
        lunara_register_reviews_cpt();
        flush_rewrite_rules();
    }
    add_action( 'after_switch_theme', 'lunara_flush_rewrites' );
}

/**
 * Blocksy controls the site header natively.
 */

/**
 * Shortcode: Homepage Content
 */
if ( ! function_exists( 'lunara_home_shortcode' ) ) {
function lunara_home_shortcode() {
    ob_start();
    ?>
    <?php echo do_shortcode('[lunara_carousel set="homepage"]'); ?>

    <div class="lunara-tagline">
        <p class="lunara-tagline-text">Film criticism and the living record of the Oscars.</p>
    </div>

    <section class="lunara-section">
        <div class="lunara-section-header">
            <h2 class="lunara-section-title">Latest Reviews</h2>
        </div>
        <?php echo do_shortcode('[lunara_reviews count="3"]'); ?>
        <div class="text-center" style="margin-top: 30px;">
            <a href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>" class="lunara-btn">View All Reviews</a>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
}
add_shortcode( 'lunara_home', 'lunara_home_shortcode' );

/**
 * Shortcode: Display Reviews
 */
if ( ! function_exists( 'lunara_reviews_shortcode' ) ) {
function lunara_reviews_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'count' => 6 ), $atts );
    $count = intval( $atts['count'] );
    if ( $count === 0 ) { $count = 6; }

    $query = new WP_Query( array(
        'post_type'      => 'review',
        'posts_per_page' => $count < 0 ? -1 : $count,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'ignore_sticky_posts' => true,
    ) );

    if ( ! $query->have_posts() ) {
        return '<p style="text-align:center;color:#888;">No reviews yet.</p>';
    }

    ob_start();
    echo '<div class="lunara-review-grid lunara-review-archive-grid">';
    while ( $query->have_posts() ) {
        $query->the_post();
        $review_id = get_the_ID();
        $score     = get_post_meta( $review_id, '_lunara_score', true );
        $quote     = function_exists( 'lunara_get_review_card_pull_quote' )
            ? lunara_get_review_card_pull_quote( $review_id, 46 )
            : wp_trim_words( wp_strip_all_tags( get_the_excerpt( $review_id ) ), 46, '...' );
        $thumb_attrs = array(
            'class'    => 'lunara-review-grid-poster',
            'loading'  => 'lazy',
            'decoding' => 'async',
            'sizes'    => '(max-width: 720px) 92vw, (max-width: 1180px) 42vw, 260px',
        );
        $image_data = function_exists( 'lunara_get_review_card_image_data' )
            ? lunara_get_review_card_image_data( $review_id, 'newspack-article-block-portrait-intermediate', $thumb_attrs )
            : array(
                'url'  => has_post_thumbnail() ? get_the_post_thumbnail_url( $review_id, 'medium_large' ) : '',
                'html' => has_post_thumbnail() ? get_the_post_thumbnail( $review_id, 'medium_large', $thumb_attrs ) : '',
            );
        $thumb_url       = isset( $image_data['url'] ) ? (string) $image_data['url'] : '';
        $has_thumb_html  = ! empty( $image_data['html'] );
        $use_fallback_bg = '' !== $thumb_url && ! $has_thumb_html;
        ?>
        <article class="lunara-review-grid-card lunara-review-archive-card">
            <a class="lunara-review-grid-link" href="<?php the_permalink(); ?>">
                <div class="lunara-review-grid-poster-wrap<?php echo $use_fallback_bg ? ' has-poster-bg has-fallback-bg' : ''; ?>"<?php if ( $use_fallback_bg ) : ?> style="background-image: url('<?php echo esc_url( $thumb_url ); ?>');"<?php endif; ?>>
                    <?php if ( $has_thumb_html ) : ?>
                        <?php echo $image_data['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php endif; ?>
                    <?php if ( $score ) : ?><span class="lunara-score-badge"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span><?php endif; ?>
                </div>
                <div class="lunara-review-grid-copy">
                    <h3 class="lunara-review-grid-title"><?php the_title(); ?></h3>
                    <?php if ( '' !== trim( $quote ) ) : ?>
                        <p class="lunara-review-grid-excerpt lunara-review-grid-quote"><?php echo esc_html( $quote ); ?></p>
                    <?php endif; ?>
                </div>
            </a>
        </article>
        <?php
    }
    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
}
}
add_shortcode( 'lunara_reviews', 'lunara_reviews_shortcode' );

/**
 * Shortcode: Display Posts by Category
 */
if ( ! function_exists( 'lunara_posts_shortcode' ) ) {
function lunara_posts_shortcode( $atts ) {
    $atts = shortcode_atts( array( 
        'category' => '', 
        'count'    => 6 
    ), $atts );
    
    $query = new WP_Query( array(
        'post_type'      => 'post',
        'category_name'  => sanitize_text_field( $atts['category'] ),
        'posts_per_page' => intval( $atts['count'] ),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
        'ignore_sticky_posts' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ) );
    
    if ( ! $query->have_posts() ) {
        return '<p style="text-align:center;color:#888;">No posts found.</p>';
    }
    
    ob_start();
    echo '<div class="lunara-grid">';
    while ( $query->have_posts() ) {
        $query->the_post();
        ?>
        <article class="lunara-card">
            <?php if ( has_post_thumbnail() ) : ?>
                <a href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail( 'medium', array( 'class' => 'lunara-card-thumb' ) ); ?>
                </a>
            <?php endif; ?>
            <h3 class="lunara-card-title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>
            <div class="lunara-card-meta"><?php echo get_the_date( 'F j, Y' ); ?></div>
            <div class="lunara-card-excerpt"><?php the_excerpt(); ?></div>
            <a href="<?php the_permalink(); ?>" class="lunara-btn">Read More</a>
        </article>
        <?php
    }
    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
}
}
add_shortcode( 'lunara_posts', 'lunara_posts_shortcode' );

/* ========================================
   LUNARA DEBRIEF - REVIEW METADATA
   ======================================== */

/**
 * Render star rating
 */
if ( ! function_exists( 'lunara_render_stars' ) ) {
    function lunara_render_stars( $score ) {
        if ( empty( $score ) ) {
            return '';
        }

        $score      = floatval( $score );
        $full_stars = floor( $score );
        $half_star  = ( $score - $full_stars ) >= 0.5;

        // Each star wrapped in its own span so CSS can stagger their
        // reveal when a review card scrolls into view (Bucket 4 sequence).
        $output = '<span class="lunara-stars">';
        for ( $i = 0; $i < $full_stars; $i++ ) {
            $output .= '<span class="lunara-star">' . '&#9733;' . '</span>';
        }
        if ( $half_star ) {
            $output .= '<span class="lunara-star lunara-star--half">' . '&frac12;' . '</span>';
        }
        $output .= '</span>';

        return $output;
    }
}

/**
 * Add Lunara Debrief meta box to Reviews
 */
if ( ! defined( 'LUNARA_CORE_VERSION' ) ) {
    function lunara_add_debrief_meta_box() {
        add_meta_box(
            'lunara_debrief_meta',
            'Lunara Debrief',
            'lunara_debrief_meta_callback',
            'review',
            'normal',
            'high'
        );
    }
    add_action( 'add_meta_boxes', 'lunara_add_debrief_meta_box' );

    /**
     * Debrief meta box callback
     */
    function lunara_debrief_meta_callback( $post ) {
        wp_nonce_field( 'lunara_debrief_nonce', 'lunara_debrief_nonce' );
        
        $score = get_post_meta( $post->ID, '_lunara_score', true );
        $year = get_post_meta( $post->ID, '_lunara_year', true );
        $imdb_review_id = get_post_meta( $post->ID, '_lunara_imdb_title_id', true );
        $where = get_post_meta( $post->ID, '_lunara_where', true );
        $theme_echo = get_post_meta( $post->ID, '_lunara_theme_echo', true );
        $counter = get_post_meta( $post->ID, '_lunara_counter_program', true );
        $craft = get_post_meta( $post->ID, '_lunara_craft_mirror', true );
        ?>
        <style>
            .lunara-meta-field { margin-bottom: 15px; }
            .lunara-meta-field label { display: block; font-weight: 600; margin-bottom: 5px; }
            .lunara-meta-field input, .lunara-meta-field select { width: 100%; }
            .lunara-meta-field .description { font-style: italic; color: #666; font-size: 12px; margin-top: 4px; }
            .lunara-meta-section { margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd; }
            .lunara-meta-section h4 { margin: 0 0 15px; color: #c9a961; }
            .lunara-meta-row { display: flex; gap: 20px; }
            .lunara-meta-row .lunara-meta-field { flex: 1; }
        </style>
        
        <div class="lunara-meta-row">
            <div class="lunara-meta-field">
                <label for="lunara_score">Score (0-5, use .5 for half stars)</label>
                <input type="text" id="lunara_score" name="lunara_score" value="<?php echo esc_attr( $score ); ?>" placeholder="4.5">
                <p class="description">Examples: 4, 4.5, 5 â†’ â˜…â˜…â˜…â˜…, â˜…â˜…â˜…â˜…Â½, â˜…â˜…â˜…â˜…â˜…</p>
            </div>
            
            <div class="lunara-meta-field">
                <label for="lunara_year">Year Released</label>
                <select id="lunara_year" name="lunara_year">
                    <option value="">â€” Select Year â€”</option>
                    <?php 
                    $current_year = (int) date('Y') + 2; // Allow 2 years ahead for upcoming films
                    for ( $y = $current_year; $y >= 1920; $y-- ) : 
                    ?>
                        <option value="<?php echo $y; ?>" <?php selected( $year, $y ); ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        
    </div>

        <div class="lunara-meta-field">
            <label for="lunara_imdb_title_id">IMDb Title ID (for this review)</label>
            <input type="text" id="lunara_imdb_title_id" name="lunara_imdb_title_id" value="<?php echo esc_attr( $imdb_review_id ); ?>" placeholder="tt1234567">
            <p class="description">Connects this review to the Oscars database film page (shows a â€œLunara Reviewâ€ module on /oscars/title/ttâ€¦/).</p>
        </div>

        <div class="lunara-meta-section">
            <h4>WHERE TO WATCH</h4>
            <div class="lunara-meta-field">
                <label for="lunara_where">Streaming + rental destinations</label>
                <textarea id="lunara_where" name="lunara_where" rows="5" placeholder="One per line. Any of these formats works:&#10;Netflix&#10;Max | https://play.max.com/video/watch/123&#10;https://tv.apple.com/us/movie/some-film/umc.cmc.xyz"><?php echo esc_textarea( $where ); ?></textarea>
                <p class="description">
                    Enter one destination per line. Three input formats supported:<br>
                    <strong>1. Service name alone</strong> (e.g. <code>Netflix</code>) â€” renders a branded chip that searches that service for the film title.<br>
                    <strong>2. Label | URL</strong> (e.g. <code>Max | https://play.max.com/video/watch/xyz</code>) â€” direct link with your chosen label.<br>
                    <strong>3. URL alone</strong> (e.g. <code>https://tv.apple.com/us/movie/xyz</code>) â€” direct link, label auto-inferred from the hostname.
                </p>
            </div>
        </div>

        <div class="lunara-meta-section">
            <h4>CINEMATIC IMAGE STRUCTURE</h4>

            <?php
            lunara_render_media_control(
                array(
                    'field_id'    => 'lunara_review_hero_banner',
                    'field_name'  => 'lunara_review_hero_banner',
                    'label'       => 'Hero Banner Image',
                    'value'       => get_post_meta( $post->ID, '_lunara_review_hero_banner', true ),
                    'description' => 'Wide, textless still that sits under the title and rating before the review begins.',
                )
            );
            ?>

            <div class="lunara-meta-field">
                <label for="lunara_review_hero_banner_caption">Hero Banner Caption (Optional)</label>
                <input type="text" id="lunara_review_hero_banner_caption" name="lunara_review_hero_banner_caption" value="<?php echo esc_attr( get_post_meta( $post->ID, '_lunara_review_hero_banner_caption', true ) ); ?>" placeholder="Optional context or source note">
            </div>

            <?php
            lunara_render_media_control(
                array(
                    'field_id'    => 'lunara_review_context_shot',
                    'field_name'  => 'lunara_review_context_shot',
                    'label'       => 'Context Shot Image',
                    'value'       => get_post_meta( $post->ID, '_lunara_review_context_shot', true ),
                    'description' => 'Usually lands after the introductory movement, near the first major subheading.',
                )
            );
            ?>

            <div class="lunara-meta-field">
                <label for="lunara_review_context_shot_caption">Context Shot Caption (Optional)</label>
                <input type="text" id="lunara_review_context_shot_caption" name="lunara_review_context_shot_caption" value="<?php echo esc_attr( get_post_meta( $post->ID, '_lunara_review_context_shot_caption', true ) ); ?>" placeholder="Optional context or source note">
            </div>

            <?php
            lunara_render_media_control(
                array(
                    'field_id'    => 'lunara_review_visual_evidence',
                    'field_name'  => 'lunara_review_visual_evidence',
                    'label'       => 'Visual Evidence Image',
                    'value'       => get_post_meta( $post->ID, '_lunara_review_visual_evidence', true ),
                    'description' => 'Use for the frame that proves a point about craft, performance, lighting, or composition.',
                )
            );
            ?>

            <div class="lunara-meta-field">
                <label for="lunara_review_visual_evidence_caption">Visual Evidence Caption (Optional)</label>
                <input type="text" id="lunara_review_visual_evidence_caption" name="lunara_review_visual_evidence_caption" value="<?php echo esc_attr( get_post_meta( $post->ID, '_lunara_review_visual_evidence_caption', true ) ); ?>" placeholder="Optional context or source note">
            </div>

            <?php
            lunara_render_media_control(
                array(
                    'field_id'    => 'lunara_review_thematic_echo',
                    'field_name'  => 'lunara_review_thematic_echo',
                    'label'       => 'Thematic Echo Image (Optional)',
                    'value'       => get_post_meta( $post->ID, '_lunara_review_thematic_echo', true ),
                    'description' => 'An evocative late still for longer essays, usually placed before the closing movement.',
                )
            );
            ?>

            <div class="lunara-meta-field">
                <label for="lunara_review_thematic_echo_caption">Thematic Echo Caption (Optional)</label>
                <input type="text" id="lunara_review_thematic_echo_caption" name="lunara_review_thematic_echo_caption" value="<?php echo esc_attr( get_post_meta( $post->ID, '_lunara_review_thematic_echo_caption', true ) ); ?>" placeholder="Optional context or source note">
            </div>
        </div>
        
        <div class="lunara-meta-section">
            <h4>PAIR IT WITH</h4>
            
            <div class="lunara-meta-field">
                <label for="lunara_theme_echo">Theme Echo</label>
                <input type="text" id="lunara_theme_echo" name="lunara_theme_echo" value="<?php echo esc_attr( $theme_echo ); ?>" placeholder="Film that shares thematic DNA">
                <p class="description">Tip: for clickable internal + IMDb links, you can append <code>| tt1234567</code> or paste a full IMDb URL anywhere in the line. No punctuation is required after the IMDb ID before your note.</p>
            </div>
            
            <div class="lunara-meta-field">
                <label for="lunara_counter_program">Counter-Program</label>
                <input type="text" id="lunara_counter_program" name="lunara_counter_program" value="<?php echo esc_attr( $counter ); ?>" placeholder="Film that offers opposing perspective">
                <p class="description">Tip: optionally add <code>| tt1234567</code> (or an IMDb URL) to enable direct links. No punctuation is required after the IMDb ID before your note.</p>
            </div>
            
            <div class="lunara-meta-field">
                <label for="lunara_craft_mirror">Career Context (Optional)</label>
                <input type="text" id="lunara_craft_mirror" name="lunara_craft_mirror" value="<?php echo esc_attr( $craft ); ?>" placeholder="Film with similar technical approach">
                <p class="description">Tip: optionally add <code>| tt1234567</code> (or an IMDb URL) to enable direct links. No punctuation is required after the IMDb ID before your note.</p>
            </div>
        </div>
        <?php
    }

    /**
     * Save Debrief meta
     */
    function lunara_save_debrief_meta( $post_id ) {
        if ( ! isset( $_POST['lunara_debrief_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['lunara_debrief_nonce'], 'lunara_debrief_nonce' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        
        $text_fields = array( 'lunara_score', 'lunara_year', 'lunara_imdb_title_id', 'lunara_theme_echo', 'lunara_counter_program', 'lunara_craft_mirror' );
        $textarea_fields = array( 'lunara_where' ); // Multi-line â€” preserves newlines for the new Where-to-Watch parser.
        $url_fields  = array( 'lunara_review_hero_banner', 'lunara_review_context_shot', 'lunara_review_visual_evidence', 'lunara_review_thematic_echo' );
        $caption_fields = array( 'lunara_review_hero_banner_caption', 'lunara_review_context_shot_caption', 'lunara_review_visual_evidence_caption', 'lunara_review_thematic_echo_caption' );

        foreach ( $text_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
            }
        }

        foreach ( $textarea_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) ) );
            }
        }

        foreach ( $url_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, esc_url_raw( wp_unslash( $_POST[ $field ] ) ) );
            }
        }

        foreach ( $caption_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
            }
        }
    }
    add_action( 'save_post_review', 'lunara_save_debrief_meta' );
}

/**
 * Auto-fill review meta from pasted HTML content.
 *
 * Dalton writes reviews externally and pastes HTML into the Code editor.
 * This parser reads the content on save and auto-populates any EMPTY meta fields
 * from patterns already present in the HTML:
 *
 *   <!-- "Title" (2026) â€” tt12345678 -->          â†’ IMDb ID, Year
 *   Score: â­â­â­                                   â†’ Score
 *   Where to Watch: Theatrical / Digital           â†’ Where to Watch
 *   Theme Echo: <em>Title</em> (YYYY) tt... â€” ...  â†’ Theme Echo pairing
 *   Counter-Program: <em>Title</em> ...            â†’ Counter-Program pairing
 *   Career Context: <em>Title</em> ...             â†’ Career Context pairing
 *   <!-- Director: Name / Runtime: 135 min / Studio: Name -->  â†’ Detail fields
 *
 * Only fills EMPTY fields â€” never overwrites manually entered data.
 */
if ( ! function_exists( 'lunara_parse_review_meta_comment_payload' ) ) {
function lunara_parse_review_meta_comment_payload( $content ) {
    $content = (string) $content;

    if ( '' === trim( $content ) ) {
        return array();
    }

    if ( ! preg_match( '/<!--\s*LUNARA_REVIEW_META\b(.*?)-->/is', $content, $match ) ) {
        return array();
    }

    $payload = trim( (string) $match[1] );
    if ( '' === $payload ) {
        return array();
    }

    $normalize_key = static function( $key ) {
        $key = strtolower( trim( (string) $key ) );
        $key = str_replace( array( '-', ' ' ), '_', $key );
        return preg_replace( '/[^a-z0-9_]/', '', $key );
    };

    $raw_pairs = array();

    if ( '{' === substr( ltrim( $payload ), 0, 1 ) ) {
        $decoded = json_decode( $payload, true );
        if ( is_array( $decoded ) ) {
            $raw_pairs = $decoded;
        }
    }

    if ( empty( $raw_pairs ) ) {
        $lines = preg_split( '/\r\n|\r|\n/', $payload );
        if ( is_array( $lines ) ) {
            foreach ( $lines as $line ) {
                $line = trim( (string) $line );
                if ( '' === $line || 0 === strpos( $line, '#' ) || 0 === strpos( $line, '//' ) ) {
                    continue;
                }

                if ( false === strpos( $line, ':' ) ) {
                    continue;
                }

                $parts = explode( ':', $line, 2 );
                $key   = isset( $parts[0] ) ? $normalize_key( $parts[0] ) : '';
                $value = isset( $parts[1] ) ? trim( $parts[1] ) : '';

                if ( '' === $key || '' === $value ) {
                    continue;
                }

                $raw_pairs[ $key ] = $value;
            }
        }
    }

    if ( empty( $raw_pairs ) ) {
        return array();
    }

    $map = array(
        'score'                => '_lunara_score',
        'year'                 => '_lunara_year',
        'imdb'                 => '_lunara_imdb_title_id',
        'imdb_id'              => '_lunara_imdb_title_id',
        'imdb_title_id'        => '_lunara_imdb_title_id',
        'where'                => '_lunara_where',
        'where_to_watch'       => '_lunara_where',
        'theme_echo'           => '_lunara_theme_echo',
        'counter_program'      => '_lunara_counter_program',
        'career_context'       => '_lunara_craft_mirror',
        'craft_mirror'         => '_lunara_craft_mirror',
        'director'             => '_lunara_director',
        'runtime'              => '_lunara_runtime',
        'studio'               => '_lunara_studio',
        'studio_distributor'   => '_lunara_studio',
        'lane_label'           => '_lunara_review_lane_label_override',
        'lane_label_override'  => '_lunara_review_lane_label_override',
        'standfirst'           => '_lunara_review_standfirst',
        'archive_label'        => '_lunara_review_archive_cta_label',
        'archive_cta_label'    => '_lunara_review_archive_cta_label',
        'archive_url'          => '_lunara_review_archive_url_override',
        'archive_url_override' => '_lunara_review_archive_url_override',
    );

    $normalized = array();

    foreach ( $raw_pairs as $raw_key => $raw_value ) {
        $key = $normalize_key( $raw_key );
        if ( '' === $key || ! isset( $map[ $key ] ) ) {
            continue;
        }

        $value = is_scalar( $raw_value ) ? trim( (string) $raw_value ) : '';
        if ( '' === $value ) {
            continue;
        }

        $normalized[ $map[ $key ] ] = $value;
    }

    return $normalized;
}
}

if ( ! function_exists( 'lunara_autofill_review_meta_from_content' ) ) {
function lunara_autofill_review_meta_from_content( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( 'review' !== get_post_type( $post_id ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $content = (string) get_post_field( 'post_content', $post_id );
    if ( '' === trim( $content ) ) {
        return;
    }

    // Helper: only set if the user didn't manually type something in the meta box.
    // We check $_POST to see if the form field was submitted empty â€” if so, the user
    // didn't fill it, and we should auto-fill from the content.
    $fill = static function( $post_id, $meta_key, $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return;
        }

        // Map meta keys to their form field names
        $form_field_map = array(
            '_lunara_score'            => 'lunara_score',
            '_lunara_year'             => 'lunara_year',
            '_lunara_imdb_title_id'    => 'lunara_imdb_title_id',
            '_lunara_where'            => 'lunara_where',
            '_lunara_theme_echo'       => 'lunara_theme_echo',
            '_lunara_counter_program'  => 'lunara_counter_program',
            '_lunara_craft_mirror'     => 'lunara_craft_mirror',
            '_lunara_director'         => 'lunara_director',
            '_lunara_runtime'          => 'lunara_runtime',
            '_lunara_studio'           => 'lunara_studio',
            '_lunara_review_lane_label_override' => 'lunara_review_lane_label_override',
            '_lunara_review_standfirst' => 'lunara_review_standfirst',
            '_lunara_review_archive_cta_label' => 'lunara_review_archive_cta_label',
            '_lunara_review_archive_url_override' => 'lunara_review_archive_url_override',
        );

        // If the form field was submitted with a value, the user typed something â€” don't overwrite.
        $form_field = isset( $form_field_map[ $meta_key ] ) ? $form_field_map[ $meta_key ] : '';
        if ( '' !== $form_field && isset( $_POST[ $form_field ] ) && '' !== trim( (string) $_POST[ $form_field ] ) ) {
            return;
        }

        if ( '_lunara_review_archive_url_override' === $meta_key ) {
            update_post_meta( $post_id, $meta_key, esc_url_raw( $value ) );
            return;
        }

        if ( '_lunara_review_standfirst' === $meta_key ) {
            update_post_meta( $post_id, $meta_key, sanitize_textarea_field( $value ) );
            return;
        }

        update_post_meta( $post_id, $meta_key, sanitize_text_field( $value ) );
    };

    $structured_meta = lunara_parse_review_meta_comment_payload( $content );
    if ( ! empty( $structured_meta ) ) {
        foreach ( $structured_meta as $meta_key => $value ) {
            $fill( $post_id, $meta_key, $value );
        }
    }

    // 1) IMDb title ID from header comment: <!-- "Title" (2026) â€” tt12345678 -->
    if ( ! isset( $structured_meta['_lunara_imdb_title_id'] ) && preg_match( '/<!--.*?(tt\d{7,8}).*?-->/', $content, $m ) ) {
        $fill( $post_id, '_lunara_imdb_title_id', $m[1] );
    }

    // 2) Year from header comment: <!-- "Title" (2026) -->
    if ( ! isset( $structured_meta['_lunara_year'] ) && preg_match( '/<!--.*?\((\d{4})\).*?-->/', $content, $m ) ) {
        $fill( $post_id, '_lunara_year', $m[1] );
    }

    // 3) Score â€” supports multiple formats:
    //    Score: â­â­â­        (star emojis)
    //    Score: â­â­â­Â½       (star emojis + half)
    //    Score: 3 out of 5    (text)
    //    Score: 3/5           (fraction)
    //    Score: 3.5           (decimal)
    //    Score: 3             (bare number)
    $score_found = '';
    // Try star emojis first
    if ( preg_match( '/Score:<\/strong>\s*([\x{2B50}\x{2605}\x{00BD}]+)/u', $content, $m ) ) {
        $stars_str = $m[1];
        $full = preg_match_all( '/[\x{2B50}\x{2605}]/u', $stars_str );
        $half = preg_match( '/\x{00BD}/u', $stars_str ) ? 0.5 : 0;
        $score_found = (string) ( $full + $half );
    }
    // Try "X out of 5" or "X/5"
    if ( '' === $score_found && preg_match( '/Score:<\/strong>\s*(\d+(?:\.\d+)?)\s*(?:out\s+of\s+5|\/\s*5)/i', $content, $m ) ) {
        $score_found = $m[1];
    }
    // Try bare number after Score:
    if ( '' === $score_found && preg_match( '/Score:<\/strong>\s*(\d+(?:\.\d+)?)\b/', $content, $m ) ) {
        $val = floatval( $m[1] );
        if ( $val > 0 && $val <= 5 ) {
            $score_found = $m[1];
        }
    }
    // Also try without <strong> tags (plain text)
    if ( '' === $score_found && preg_match( '/Score:\s*(\d+(?:\.\d+)?)\s*(?:out\s+of\s+5|\/\s*5)/i', $content, $m ) ) {
        $score_found = $m[1];
    }
    if ( '' === $score_found && preg_match( '/Score:\s*(\d+(?:\.\d+)?)\b/', $content, $m ) ) {
        $val = floatval( $m[1] );
        if ( $val > 0 && $val <= 5 ) {
            $score_found = $m[1];
        }
    }
    if ( ! isset( $structured_meta['_lunara_score'] ) && '' !== $score_found ) {
        $fill( $post_id, '_lunara_score', $score_found );
    }

    // 4) Where to Watch
    if ( ! isset( $structured_meta['_lunara_where'] ) && preg_match( '/Where\s+to\s+Watch:\s*(.+)/i', $content, $m ) ) {
        $value = wp_strip_all_tags( $m[1] );
        $value = preg_replace( '/\s*<.*$/', '', $value );
        $fill( $post_id, '_lunara_where', $value );
    }

    // 5) Pair It With â€” Theme Echo
    if ( ! isset( $structured_meta['_lunara_theme_echo'] ) && preg_match( '/Theme\s+Echo:\s*(.+)/i', $content, $m ) ) {
        $fill( $post_id, '_lunara_theme_echo', wp_strip_all_tags( html_entity_decode( $m[1] ) ) );
    }

    // 6) Pair It With â€” Counter-Program
    if ( ! isset( $structured_meta['_lunara_counter_program'] ) && preg_match( '/Counter[\-\s]Program:\s*(.+)/i', $content, $m ) ) {
        $fill( $post_id, '_lunara_counter_program', wp_strip_all_tags( html_entity_decode( $m[1] ) ) );
    }

    // 7) Pair It With â€” Career Context (or Craft Mirror)
    if ( ! isset( $structured_meta['_lunara_craft_mirror'] ) && preg_match( '/Career\s+Context:\s*(.+)/i', $content, $m ) ) {
        $fill( $post_id, '_lunara_craft_mirror', wp_strip_all_tags( html_entity_decode( $m[1] ) ) );
    }

    // 8) Director from comment: <!-- Director: Name -->
    if ( ! isset( $structured_meta['_lunara_director'] ) && preg_match( '/Director:\s*([^\/\n<]+)/i', $content, $m ) ) {
        $director = trim( $m[1] );
        // Skip if it's the meta box label or a URL
        if ( strlen( $director ) > 2 && strlen( $director ) < 100 && ! preg_match( '/^(Director|http)/i', $director ) ) {
            $fill( $post_id, '_lunara_director', $director );
        }
    }

    // 9) Runtime from comment: <!-- Runtime: 135 min -->
    if ( ! isset( $structured_meta['_lunara_runtime'] ) && preg_match( '/Runtime:\s*(\d+\s*min[^\/\n<]*)/i', $content, $m ) ) {
        $fill( $post_id, '_lunara_runtime', trim( $m[1] ) );
    }

    // 10) Studio from comment: <!-- Studio: Name --> or <!-- Studio / Distributor: Name -->
    if ( ! isset( $structured_meta['_lunara_studio'] ) && preg_match( '/Studio(?:\s*\/\s*Distributor)?:\s*([^\/\n<]+)/i', $content, $m ) ) {
        $studio = trim( $m[1] );
        if ( strlen( $studio ) > 1 && strlen( $studio ) < 100 ) {
            $fill( $post_id, '_lunara_studio', $studio );
        }
    }
}
}
add_action( 'save_post_review', 'lunara_autofill_review_meta_from_content', 50 );

/**
 * Load the bundled IMDb title map (title|year -> ttID).
 * This lets Debrief lines link directly to IMDb (and Lunara Oscars film pages)
 * without requiring you to paste a tt-id every time.
 *
 * File: /assets/data/imdb-title-map.json
 *
 * Key format: "<normalized_title>|<year>" => "tt1234567"
 */

/**
 * Resolve the active Academy Awards DB table name (supports multiple plugin variants).
 */
if ( ! function_exists( 'lunara_awards_table_name' ) ) {
function lunara_awards_table_name() {
    static $resolved_table = null;

    if ( $resolved_table !== null ) {
        return $resolved_table;
    }

    $cached_table = get_transient( 'lunara_awards_table_name_v1' );
    if ( is_string( $cached_table ) ) {
        $resolved_table = $cached_table;
        return $resolved_table;
    }

    global $wpdb;
    $candidates = array(
        $wpdb->prefix . 'academy_awards',
        $wpdb->prefix . 'academy_awards_table',
        $wpdb->prefix . 'aat_awards',
        $wpdb->prefix . 'lunara_academy_awards',
        $wpdb->prefix . 'lunara_awards',
    );
    foreach ( $candidates as $t ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $found = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $t ) );
        if ( $found ) {
            $resolved_table = $t;
            set_transient( 'lunara_awards_table_name_v1', $resolved_table, 12 * HOUR_IN_SECONDS );
            return $resolved_table;
        }
    }

    $resolved_table = '';
    set_transient( 'lunara_awards_table_name_v1', $resolved_table, HOUR_IN_SECONDS );
    return $resolved_table;
}
}

/**
 * Get Oscar nominations/wins counts for a film by IMDb title id (tt...).
 * Returns array( 'noms' => int, 'wins' => int ).
 */
if ( ! function_exists( 'lunara_get_oscar_ledger_counts' ) ) {
function lunara_get_oscar_ledger_counts( $tt ) {
    $tt = strtolower( trim( (string) $tt ) );
    if ( $tt === '' || ! preg_match( '/^tt\d{7,8}$/', $tt ) ) {
        return array( 'noms' => 0, 'wins' => 0 );
    }

    $cache_key = 'lunara_oscar_ledger_' . $tt;
    $cached = get_transient( $cache_key );
    if ( is_array( $cached ) && isset( $cached['noms'], $cached['wins'] ) ) {
        return $cached;
    }

    $table = lunara_awards_table_name();
    if ( $table === '' ) {
        return array( 'noms' => 0, 'wins' => 0 );
    }

    global $wpdb;

    // Fetch both totals in one pass so single-review pages do less database work.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT COUNT(*) AS noms, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS wins FROM {$table} WHERE film_id = %s",
            $tt
        ),
        ARRAY_A
    );

    $out = array(
        'noms' => isset( $row['noms'] ) ? (int) $row['noms'] : 0,
        'wins' => isset( $row['wins'] ) ? (int) $row['wins'] : 0,
    );
    set_transient( $cache_key, $out, 6 * HOUR_IN_SECONDS );
    return $out;
}
}

/**
 * Render the Oscar Ledger pill (clicks into Lunara's Oscars film page).
 */
if ( ! function_exists( 'lunara_render_oscar_ledger_pill' ) ) {
function lunara_render_oscar_ledger_pill( $tt, $counts = null ) {
    $tt = strtolower( trim( (string) $tt ) );
    if ( $tt === '' || ! preg_match( '/^tt\d{7,8}$/', $tt ) ) {
        return '';
    }

    if ( ! is_array( $counts ) ) {
        $counts = lunara_get_oscar_ledger_counts( $tt );
    }

    $noms = (int) ( $counts['noms'] ?? 0 );
    $wins = (int) ( $counts['wins'] ?? 0 );

    // Keep it classy: only show when the film actually has Oscar presence.
    if ( $noms <= 0 ) {
        return '';
    }

    $href = home_url( '/oscars/title/' . $tt . '/' );
    $label = sprintf( '%d nominations â€¢ %d wins', $noms, $wins );

    return '<a class="lunara-oscar-ledger" href="' . esc_url( $href ) . '">'
        . '<span class="lunara-oscar-ledger-pill">Oscar Ledger</span>'
        . '<span class="lunara-oscar-ledger-counts">' . esc_html( $label ) . '</span>'
        . '</a>';
}
}


if ( ! function_exists( 'lunara_imdb_title_map' ) ) {
function lunara_imdb_title_map() {
    static $map = null;
    if ( $map !== null ) {
        return $map;
    }

    $map = array();
    $asset = lunara_resolve_theme_asset(
        'assets/data/imdb-title-map.json',
        array( 'imdb-title-map.json' )
    );
    $file = $asset['path'];

    if ( $file && file_exists( $file ) ) {
        $json = file_get_contents( $file );
        $data = json_decode( $json, true );
        if ( is_array( $data ) ) {
            $map = $data;
        }
    }

    return $map;
}
}

/**
 * Normalize a title to a stable lookup key.
 */
if ( ! function_exists( 'lunara_normalize_title_key' ) ) {
function lunara_normalize_title_key( $title ) {
    $t = strtolower( remove_accents( (string) $title ) );
    $t = str_replace( '&', 'and', $t );
    $t = preg_replace( '/[^a-z0-9]+/', ' ', $t );
    $t = trim( preg_replace( '/\s+/', ' ', $t ) );
    return $t;
}
}



/**
 * Shortcode: The Lunara Debrief Block
 */
if ( ! function_exists( 'lunara_debrief_shortcode' ) ) {
function lunara_debrief_shortcode( $atts ) {
    $post_id = get_the_ID();
    
    $score = get_post_meta( $post_id, '_lunara_score', true );
    $year = get_post_meta( $post_id, '_lunara_year', true );
    $where = get_post_meta( $post_id, '_lunara_where', true );
    $theme_echo = get_post_meta( $post_id, '_lunara_theme_echo', true );
    $counter = get_post_meta( $post_id, '_lunara_counter_program', true );
    $craft = get_post_meta( $post_id, '_lunara_craft_mirror', true );
    $review_tt = get_post_meta( $post_id, '_lunara_imdb_title_id', true );
    if ( is_string( $review_tt ) && preg_match( '#imdb\.com/title/(tt\d{7,8})#i', $review_tt, $mtt ) ) { $review_tt = $mtt[1]; }
    $review_tt = strtolower( trim( (string) $review_tt ) );
    
    if ( empty( $score ) && empty( $where ) && empty( $theme_echo ) && empty( $year ) ) {
        return '';
    }

    // Local helper: render a "Pair It With" line.
    // Supports optional IMDb title ID / URL embedded anywhere in the field.
    // Examples you can paste into the meta field:
    //   "There Will Be Blood (2007) â€” ... | tt0469494"
    //   "There Will Be Blood (2007) â€” ... https://www.imdb.com/title/tt0469494/"
    // If a tt-id is present, the title links to the internal Oscars film page (/oscars/title/tt.../)
    // and an "IMDb" reference chip is shown.
    $format_pairing = function( $value ) {
    $raw = trim( (string) $value );
    if ( $raw === '' ) {
        return '';
    }

    // 1) Extract a tt-id if present anywhere (either bare tt123... or full IMDb URL).
    //    Also optionally extract a Letterboxd film URL (for clickable title).
    $tt = '';
    $lb = '';
    if ( preg_match( '/\btt\d{7,8}\b/i', $raw, $m ) ) {
        $tt = strtolower( $m[0] );
    } elseif ( preg_match( '#imdb\.com/title/(tt\d{7,8})#i', $raw, $m ) ) {
        $tt = strtolower( $m[1] );
    }

    // Letterboxd film URL (optional). Supports:
    //   - https://letterboxd.com/film/<slug>/
    //   - | lb:https://letterboxd.com/film/<slug>/
    //   - | https://letterboxd.com/film/<slug>/
    if ( preg_match( '#letterboxd\.com/film/[^\s\|\)\]]+/?#i', $raw, $m ) ) {
        $lb = $m[0];
        // Ensure scheme.
        if ( stripos( $lb, 'http' ) !== 0 ) {
            $lb = 'https://' . ltrim( $lb, '/' );
        }
    }

    // 2) Remove the tt-id / IMDb URL / Letterboxd URL from the display string so the line stays clean.
    $clean = $raw;
    if ( $tt !== '' ) {
        $clean = preg_replace( '/\[\s*' . preg_quote( $tt, '/' ) . '\s*\]/i', '', $clean );
        $clean = preg_replace( '/\(\s*' . preg_quote( $tt, '/' ) . '\s*\)/i', '', $clean );
        $clean = preg_replace( '/\s*\|\s*\b' . preg_quote( $tt, '/' ) . '\b\s*$/i', '', $clean );
        $clean = preg_replace( '#\s*\|\s*https?://(www\.)?imdb\.com/title/' . preg_quote( $tt, '#' ) . '/?\s*$#i', '', $clean );
        $clean = preg_replace( '#\s*https?://(www\.)?imdb\.com/title/' . preg_quote( $tt, '#' ) . '/?\s*#i', ' ', $clean );
        $clean = preg_replace( '/\s*\b' . preg_quote( $tt, '/' ) . '\b\s*/i', ' ', $clean );
        $clean = trim( preg_replace( '/\s{2,}/', ' ', $clean ) );
    }
    if ( $lb !== '' ) {
        $clean = preg_replace( '#\s*\|\s*lb:\s*' . preg_quote( $lb, '#' ) . '\s*$#i', '', $clean );
        $clean = preg_replace( '#\s*\|\s*' . preg_quote( $lb, '#' ) . '\s*$#i', '', $clean );
        $clean = preg_replace( '#\s*' . preg_quote( $lb, '#' ) . '\s*#i', ' ', $clean );
        $clean = trim( preg_replace( '/\s{2,}/', ' ', $clean ) );
    }

    // 3) Split into title + note (prefer em dash).
    $parts = preg_split( '/\s+â€”\s+/u', $clean, 2 );
    if ( count( $parts ) < 2 ) {
        $parts = preg_split( '/\s+-\s+/', $clean, 2 );
    }

    $title = trim( $parts[0] ?? '' );
    $note  = trim( $parts[1] ?? '' );

    // If the note starts immediately after the IMDb ID, don't require punctuation.
    if ( '' === $note && $tt !== '' ) {
        $tail_pattern = '/^(.*?)\b' . preg_quote( $tt, '/' ) . '\b(?:\s*[.:;\-\x{2013}\x{2014}]\s*|\s+)(.+)$/iu';
        if ( preg_match( $tail_pattern, $raw, $m3 ) ) {
            $raw_title = trim( wp_strip_all_tags( $m3[1] ) );
            $raw_title = preg_replace( '/\s*(?:\||:|-|\x{2013}|\x{2014})\s*$/u', '', $raw_title );
            $raw_title = preg_replace( '/\s{2,}/', ' ', (string) $raw_title );
            $title     = trim( (string) $raw_title );
            $note      = trim( wp_strip_all_tags( $m3[2] ) );
        }
    }

    // 4) Pull year out of "Title (YYYY)" for smarter lookups & cleaner IMDb search queries.
    $title_base = $title;
    $year = '';
    if ( preg_match( '/^(.*?)(?:\s*\((\d{4})\))\s*$/', $title, $m2 ) ) {
        $title_base = trim( $m2[1] );
        $year = trim( $m2[2] );
    }

    // 5) If no explicit tt-id was provided, try to resolve via the bundled IMDb title map.
    if ( $tt === '' && $title_base !== '' ) {
        $map = lunara_imdb_title_map();
        if ( $year !== '' ) {
            $key = lunara_normalize_title_key( $title_base ) . '|' . $year;
            if ( isset( $map[ $key ] ) ) {
                $tt = strtolower( $map[ $key ] );
            }
        } else {
            // Only use a title-only lookup if it's unambiguous.
            $prefix = lunara_normalize_title_key( $title_base ) . '|';
            $matches = array();
            foreach ( $map as $k => $val ) {
                if ( strpos( $k, $prefix ) === 0 ) {
                    $matches[] = $val;
                }
            }
            $matches = array_values( array_unique( $matches ) );
            if ( count( $matches ) === 1 ) {
                $tt = strtolower( $matches[0] );
            }
        }
    }

    // 6) Build title + links.
    //    Title click goes to Letterboxd (film URL if provided; otherwise Letterboxd search).
    $lb_href = '';
    if ( $lb !== '' ) {
        $lb_href = $lb;
    } else {
        $q = $title_base !== '' ? $title_base : $title;
        if ( $year !== '' ) {
            $q .= ' ' . $year;
        }
        $lb_href = 'https://letterboxd.com/search/' . rawurlencode( $q ) . '/';
    }
    $title_html = '<a class="lunara-pair-title" href="' . esc_url( $lb_href ) . '" target="_blank" rel="noopener noreferrer nofollow"><em>' . esc_html( $title ) . '</em></a>';
    $chips_html = '';

    if ( $tt !== '' ) {
            $imdb = 'https://www.imdb.com/title/' . $tt . '/';

            // IMDb is the external reference. Oscar Ledger pill drives visitors into Lunara's database.
            $chips_html = ' <a class="lunara-debrief-chip lunara-debrief-chip-imdb" href="' . esc_url( $imdb ) . '" target="_blank" rel="noopener noreferrer nofollow">IMDb</a>';
            $chips_html .= ' ' . lunara_render_oscar_ledger_pill( $tt );
        } else {
        // Fallback: IMDb search. Use "Title YYYY" (no parentheses) for better results.
        $q = $title_base !== '' ? $title_base : $title;
        if ( $year !== '' ) {
            $q .= ' ' . $year;
        }
        $imdb_search = 'https://www.imdb.com/find/?q=' . rawurlencode( $q ) . '&s=tt';
        $chips_html  = ' <a class="lunara-debrief-chip lunara-debrief-chip-imdb" href="' . esc_url( $imdb_search ) . '" target="_blank" rel="noopener noreferrer nofollow">IMDb</a>';
    }

    // Optional poster thumbnail.
    // Priority: 1) Oscar database poster library, 2) TMDB API lookup via IMDb ID.
    // Every paired film should show a poster regardless of Oscar history.
    $poster_html = '';
    if ( $tt !== '' && class_exists( 'Academy_Awards_Table' ) ) {
        $aat = Academy_Awards_Table::get_instance();
        if ( $aat && method_exists( $aat, 'get_poster_img_html_for_title' ) ) {
            $poster_html = (string) $aat->get_poster_img_html_for_title(
                $tt,
                'medium',
                array(
                    'class'    => 'lunara-debrief-thumb',
                    'loading'  => 'lazy',
                    'decoding' => 'async',
                )
            );
        }

        // Fallback: TMDB API lookup when no local Oscar poster exists.
        if ( '' === $poster_html && $aat && method_exists( $aat, 'get_tmdb_data_for_imdb_id' ) ) {
            $tmdb_data = $aat->get_tmdb_data_for_imdb_id( $tt );
            if ( ! empty( $tmdb_data['poster_path'] ) ) {
                $tmdb_poster_url = 'https://image.tmdb.org/t/p/w185' . $tmdb_data['poster_path'];
                $poster_html = sprintf(
                    '<img class="lunara-debrief-thumb" src="%s" alt="%s" loading="lazy" decoding="async">',
                    esc_url( $tmdb_poster_url ),
                    esc_attr( $title )
                );
            }
        }
    }

    // Last resort: try TMDB directly even without the Oscar plugin, as long as we have an IMDb ID.
    if ( '' === $poster_html && $tt !== '' && defined( 'AAT_TMDB_API_KEY' ) ) {
        $tmdb_cache_key = 'lunara_debrief_tmdb_' . $tt;
        $cached_url     = get_transient( $tmdb_cache_key );
        if ( false === $cached_url ) {
            $tmdb_response = wp_remote_get(
                'https://api.themoviedb.org/3/find/' . $tt . '?api_key=' . AAT_TMDB_API_KEY . '&external_source=imdb_id',
                array( 'timeout' => 5 )
            );
            if ( ! is_wp_error( $tmdb_response ) ) {
                $tmdb_body = json_decode( wp_remote_retrieve_body( $tmdb_response ), true );
                $results   = ! empty( $tmdb_body['movie_results'] ) ? $tmdb_body['movie_results'] : ( ! empty( $tmdb_body['tv_results'] ) ? $tmdb_body['tv_results'] : array() );
                if ( ! empty( $results[0]['poster_path'] ) ) {
                    $cached_url = 'https://image.tmdb.org/t/p/w185' . $results[0]['poster_path'];
                } else {
                    $cached_url = '';
                }
            } else {
                $cached_url = '';
            }
            set_transient( $tmdb_cache_key, $cached_url, 7 * DAY_IN_SECONDS );
        }
        if ( '' !== $cached_url ) {
            $poster_html = sprintf(
                '<img class="lunara-debrief-thumb" src="%s" alt="%s" loading="lazy" decoding="async">',
                esc_url( $cached_url ),
                esc_attr( $title )
            );
        }
    }

    // IMPORTANT UX FIX:
    // The descriptive sentence (note) must not trail AFTER the IMDb chip.
    // Render a clean first line (title + chips), then the note on its own line below.
    $line1 = '<span class="lunara-debrief-line1">' . $title_html . $chips_html . '</span>';
    $line2 = '';
    if ( $note !== '' ) {
        $line2 = '<span class="lunara-debrief-note">' . esc_html( $note ) . '</span>';
    }

    $text_html = '<span class="lunara-debrief-pairing-text">' . $line1 . $line2 . '</span>';

    if ( $poster_html === '' ) {
        return $text_html;
    }

    return '<span class="lunara-debrief-pairing">'
        . '<span class="lunara-debrief-thumb-wrap">' . $poster_html . '</span>'
        . $text_html
        . '</span>';
};
    
    ob_start();
    ?>
    <section class="lunara-debrief-block">
        <h3 class="lunara-debrief-heading">LUNARA DEBRIEF</h3>
        <?php $kicker = trim( (string) get_theme_mod( 'lunara_debrief_kicker_text', 'A LUNARA FILM SIGNATURE' ) ); ?>
        <?php if ( $kicker !== '' ) : ?>
            <div class="lunara-debrief-kicker"><?php echo esc_html( $kicker ); ?></div>
        <?php endif; ?>
<ul class="lunara-debrief-list">
            <?php if ( $score ) : ?>
                <li><strong>Score:</strong><span class="lunara-debrief-value"><?php echo lunara_render_stars( $score ); ?></span></li>
            <?php endif; ?>

            <?php if ( $review_tt ) : ?>
                <?php $ledger = lunara_render_oscar_ledger_pill( $review_tt ); ?>
                <?php if ( $ledger !== '' ) : ?>
                    <li class="lunara-debrief-ledger-row"><strong>&nbsp;</strong><span class="lunara-debrief-value"><?php echo $ledger; ?></span></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ( $year ) : ?>
                <li><strong>Year:</strong><span class="lunara-debrief-value"><?php echo esc_html( $year ); ?></span></li>
            <?php endif; ?>
            
            <?php if ( $where ) : ?>
                <li><strong>Where to Watch:</strong><span class="lunara-debrief-value"><?php echo esc_html( $where ); ?></span></li>
            <?php endif; ?>
            
            <?php if ( $theme_echo || $counter || $craft ) : ?>
                <li class="lunara-debrief-pair-header">Pair It With</li>
                
                <?php if ( $theme_echo ) : ?>
                    <li><strong>Theme Echo:</strong><span class="lunara-debrief-value"><?php echo $format_pairing( $theme_echo ); ?></span></li>
                <?php endif; ?>
                
                <?php if ( $counter ) : ?>
                    <li><strong>Counter-Program:</strong><span class="lunara-debrief-value"><?php echo $format_pairing( $counter ); ?></span></li>
                <?php endif; ?>
                
                <?php if ( $craft ) : ?>
                    <li><strong>Career Context:</strong><span class="lunara-debrief-value"><?php echo $format_pairing( $craft ); ?></span></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </section>
    <?php
    return ob_get_clean();
}
}
add_shortcode( 'lunara_debrief', 'lunara_debrief_shortcode' );

/**
 * Auto-append Lunara Debrief to single review content
 */
// Debrief is now rendered by the review single templates (single.php / single-review.php)
// directly as a dedicated closing section. No longer auto-appended to the_content.

/* ========================================
   SLIDE SETS - CURATED CAROUSELS
   ======================================== */

/**
 * Register Slide Sets taxonomy for Media
 * In WP Admin: Media Library â†’ click an image â†’ edit â†’ assign to a Slide Set (e.g., "homepage")
 * Then use: [lunara_carousel set="homepage"]
 */
if ( ! defined( 'LUNARA_CORE_VERSION' ) ) {
if ( ! function_exists( 'lunara_register_slide_set_taxonomy' ) ) {
function lunara_register_slide_set_taxonomy() {
    register_taxonomy( 'lunara_slide_set', array( 'attachment' ), array(
        'labels' => array(
            'name'          => __( 'Slide Sets', 'lunara-film' ),
            'singular_name' => __( 'Slide Set', 'lunara-film' ),
            'search_items'  => __( 'Search Slide Sets', 'lunara-film' ),
            'all_items'     => __( 'All Slide Sets', 'lunara-film' ),
            'edit_item'     => __( 'Edit Slide Set', 'lunara-film' ),
            'update_item'   => __( 'Update Slide Set', 'lunara-film' ),
            'add_new_item'  => __( 'Add New Slide Set', 'lunara-film' ),
            'new_item_name' => __( 'New Slide Set Name', 'lunara-film' ),
            'menu_name'     => __( 'Slide Sets', 'lunara-film' ),
        ),
        'public'             => false,
        'show_ui'            => true,
        'show_admin_column'  => true,
        'show_in_quick_edit' => true,
        'show_in_rest'       => true,
        'hierarchical'       => false,
        'rewrite'            => false,
        'query_var'          => false,
    ) );
}
}
add_action( 'init', 'lunara_register_slide_set_taxonomy' );


/**
 * Attachment field: Carousel Link URL (stored as _lunara_slide_link).
 * Falls back to Alt Text for backward compatibility.
 */
if ( ! function_exists( 'lunara_slide_link_edit_field' ) ) {
function lunara_slide_link_edit_field( $form_fields, $post ) {
    // Show for all media items; harmless if not used.
    $form_fields['lunara_slide_link'] = array(
        'label' => 'Carousel Link URL',
        'input' => 'text',
        'value' => get_post_meta($post->ID, '_lunara_slide_link', true),
        'helps' => 'Optional. If set, the carousel slide will link here. If empty, the theme falls back to using Alt Text as the link.',
    );
    return $form_fields;
}
}
add_filter( 'attachment_fields_to_edit', 'lunara_slide_link_edit_field', 10, 2 );

if ( ! function_exists( 'lunara_slide_link_save_field' ) ) {
function lunara_slide_link_save_field( $post, $attachment ) {
    if (isset($attachment['lunara_slide_link'])) {
        $url = trim((string) $attachment['lunara_slide_link']);
        if ($url === '') {
            delete_post_meta($post['ID'], '_lunara_slide_link');
        } else {
            update_post_meta($post['ID'], '_lunara_slide_link', esc_url_raw($url));
        }
    }
    return $post;
}
}
add_filter( 'attachment_fields_to_save', 'lunara_slide_link_save_field', 10, 2 );

/**
 * Admin: Carousel Manager (drag & drop ordering per Slide Set).
 */
if ( ! function_exists( 'lunara_register_carousel_admin_page' ) ) {
function lunara_register_carousel_admin_page() {
    add_theme_page(
        'Lunara Carousel',
        'Lunara Carousel',
        'manage_options',
        'lunara-carousel-manager',
        'lunara_render_carousel_manager_page'
    );
}
}
add_action( 'admin_menu', 'lunara_register_carousel_admin_page' );

if ( ! function_exists( 'lunara_enqueue_carousel_admin_assets' ) ) {
function lunara_enqueue_carousel_admin_assets( $hook ) {
    if ($hook !== 'appearance_page_lunara-carousel-manager') {
        return;
    }

    $admin_css = lunara_resolve_theme_asset(
        'assets/css/lunara-carousel-admin.css',
        array(
            'lunara-carousel-admin.css',
            'lunara-carousel-admis.css',
        )
    );
    $admin_js = lunara_resolve_theme_asset(
        'assets/js/lunara-carousel-admin.js',
        array(
            'lunara-carousel-admin.js',
            'lunara-carousel-admis.js',
        )
    );

    if ( $admin_css['uri'] ) {
        wp_enqueue_style(
            'lunara-carousel-admin',
            $admin_css['uri'],
            array(),
            lunara_theme_asset_version( $admin_css['path'] )
        );
    }

    wp_enqueue_script('jquery-ui-sortable');

    if ( $admin_js['uri'] ) {
        wp_enqueue_script(
            'lunara-carousel-admin',
            $admin_js['uri'],
            array('jquery', 'jquery-ui-sortable'),
            lunara_theme_asset_version( $admin_js['path'] ),
            true
        );
    }

    if ( $admin_js['uri'] ) {
        wp_localize_script('lunara-carousel-admin', 'LUNARA_CAROUSEL_ADMIN', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('lunara_carousel_admin'),
        ));
    }
}
}
add_action( 'admin_enqueue_scripts', 'lunara_enqueue_carousel_admin_assets' );

if ( ! function_exists( 'lunara_render_carousel_manager_page' ) ) {
function lunara_render_carousel_manager_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this page.');
    }

    $taxonomy = 'lunara_slide_set';
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
    ));

    $selected = isset($_GET['set']) ? sanitize_text_field(wp_unslash($_GET['set'])) : '';
    if ($selected === '' && !empty($terms) && !is_wp_error($terms)) {
        $selected = $terms[0]->slug;
    }

    echo '<div class="wrap">';
    echo '<h1>Lunara Carousel</h1>';
    echo '<p><strong>How to update the carousel:</strong> Upload (or select) images in <em>Media â†’ Library</em>, then assign them to a <em>Slide Set</em>. Use this page to drag & drop reorder slides. To add a link per slide, edit the media item and fill in <em>Carousel Link URL</em>.</p>';

    echo '<form method="get" action="">';
    echo '<input type="hidden" name="page" value="lunara-carousel-manager" />';
    echo '<label for="lunara-slide-set"><strong>Slide Set:</strong></label> ';
    echo '<select id="lunara-slide-set" name="set">';
    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $t) {
            $sel = selected($selected, $t->slug, false);
            echo '<option value="' . esc_attr($t->slug) . '" ' . $sel . '>' . esc_html($t->name) . '</option>';
        }
    }
    echo '</select> ';
    submit_button('Load', 'secondary', '', false);
    echo '</form>';

    if ($selected) {
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'),
            'tax_query' => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $selected,
                ),
            ),
        ));

        echo '<hr />';
        echo '<h2>Slides in: ' . esc_html($selected) . '</h2>';

        if (empty($attachments)) {
            echo '<p>No slides found in this set yet.</p>';
        } else {
            echo '<p class="description">Drag & drop to reorder. Then click <strong>Save Order</strong>.</p>';
            echo '<ul id="lunara-carousel-sortable" class="lunara-carousel-sortable" data-slide-set="' . esc_attr($selected) . '">';
            foreach ($attachments as $att) {
                $thumb = wp_get_attachment_image($att->ID, array(120, 120), true);
                $link = get_post_meta($att->ID, '_lunara_slide_link', true);
                echo '<li class="lunara-carousel-item" data-id="' . esc_attr($att->ID) . '">';
                echo '<div class="lunara-carousel-thumb">' . $thumb . '</div>';
                echo '<div class="lunara-carousel-meta">';
                echo '<div class="lunara-carousel-title"><strong>' . esc_html(get_the_title($att->ID)) . '</strong></div>';
                if ($link) {
                    echo '<div class="lunara-carousel-link"><code>' . esc_html($link) . '</code></div>';
                }
                echo '<div class="lunara-carousel-actions"><a href="' . esc_url(get_edit_post_link($att->ID)) . '">Edit</a></div>';
                echo '</div>';
                echo '</li>';
            }
            echo '</ul>';
            echo '<button type="button" class="button button-primary" id="lunara-carousel-save-order">Save Order</button> ';
            echo '<span id="lunara-carousel-save-status" style="margin-left:10px;"></span>';
        }
    }

    echo '</div>';
}
}

if ( ! function_exists( 'lunara_ajax_save_carousel_order' ) ) {
function lunara_ajax_save_carousel_order() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    check_ajax_referer('lunara_carousel_admin', 'nonce');

    $order = isset($_POST['order']) ? (array) $_POST['order'] : array();
    $order = array_values(array_filter(array_map('intval', $order)));

    if (empty($order)) {
        wp_send_json_error(array('message' => 'No order received.'));
    }

    $menu_order = 0;
    foreach ($order as $id) {
        wp_update_post(array(
            'ID' => $id,
            'menu_order' => $menu_order,
        ));
        $menu_order++;
    }

    wp_send_json_success(array(
        'message' => 'Order saved.',
        'count' => count($order),
    ));
}
}
add_action( 'wp_ajax_lunara_save_carousel_order', 'lunara_ajax_save_carousel_order' );
}


/**
 * Shortcode: Curated Carousel
 * Usage: [lunara_carousel set="homepage"]
 * 
 * Each image can have:
 * - Title: Used as slide title
 * - Caption: Used as slide subtitle
 * - Carousel Link URL: Used as link URL (optional)
 *   (set it on the Media item; Alt Text is left for actual alt text)
 */
if ( ! function_exists( 'lunara_carousel_shortcode' ) ) {
function lunara_carousel_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'set'   => 'homepage',
        'limit' => -1,  // -1 = unlimited
    ), $atts );

    // Enqueue carousel JS only when the shortcode is used.
    $carousel_js = lunara_resolve_theme_asset(
        'assets/js/lunara-carousel.js',
        array( 'lunara-carousel.js' )
    );
    if ( $carousel_js['path'] ) {
        wp_enqueue_script(
            'lunara-carousel',
            $carousel_js['uri'],
            array(),
            lunara_theme_asset_version( $carousel_js['path'] ),
            true
        );
    }

    $set_slug = sanitize_title( $atts['set'] );
    $limit    = (int) $atts['limit'];

    // Query slides for this set. (No object-cache here: we want updates to appear immediately after you assign images.)
    $images = get_posts( array(
        'post_type'              => 'attachment',
        'post_mime_type'         => 'image',
        'posts_per_page'         => $limit,
        'post_status'            => 'inherit',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'tax_query'              => array(
            array(
                'taxonomy' => 'lunara_slide_set',
                'field'    => 'slug',
                'terms'    => $set_slug,
            ),
        ),
        'orderby'                => 'menu_order',
        'order'                  => 'ASC',
    ) );
    // Fallback if no images in set
    if ( empty( $images ) ) {
        return '<div class="lunara-carousel-empty" style="background:#0f1d2e;padding:100px 40px;text-align:center;color:#888;">
            <p>No images in slide set "' . esc_html( $atts['set'] ) . '"</p>
            <p style="font-size:0.9em;">Go to Media Library â†’ Edit an image â†’ Assign to Slide Set</p>
        </div>';
    }
    
    ob_start();
    ?>
    <div class="lunara-carousel" id="lunara-carousel-<?php echo esc_attr( $set_slug ); ?>" data-autoplay="5000">
        <?php foreach ( $images as $index => $image ) : 
            $img_url = wp_get_attachment_image_url( $image->ID, 'full' );
            $title = $image->post_title;
            $caption = wp_get_attachment_caption( $image->ID );
            $link = (string) get_post_meta( $image->ID, '_lunara_slide_link', true );
            if ( empty( $link ) ) {
                // Back-compat: if Alt Text was previously used to store a URL, accept it only when it looks like a URL.
                $alt = (string) get_post_meta( $image->ID, '_wp_attachment_image_alt', true );
                if ( $alt && preg_match( '~^https?://~i', $alt ) ) {
                    $link = $alt;
                }
            }
            $link = ( $link && filter_var( $link, FILTER_VALIDATE_URL ) ) ? $link : '';
        ?>
            <div class="lunara-carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>" style="background-image: url('<?php echo esc_url( $img_url ); ?>');">
                <div class="lunara-carousel-overlay">
                    <?php if ( $link ) : ?>
                        <a href="<?php echo esc_url( $link ); ?>" class="lunara-carousel-link">
                    <?php endif; ?>
                    
                    <?php if ( $title ) : ?>
                        <h2 class="lunara-carousel-title"><?php echo esc_html( $title ); ?></h2>
                    <?php endif; ?>
                    
                    <?php if ( $caption ) : ?>
                        <p class="lunara-carousel-subtitle"><?php echo esc_html( $caption ); ?></p>
                    <?php endif; ?>
                    
                    <?php if ( $link ) : ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if ( count( $images ) > 1 ) : ?>
            <div class="lunara-carousel-dots">
                <?php foreach ( $images as $index => $image ) : ?>
                    <button class="lunara-carousel-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
}
add_shortcode( 'lunara_carousel', 'lunara_carousel_shortcode' );

/**
 * [lunara_still] â€” Full editorial control over cinematic stills in reviews.
 *
 * Usage:
 *   [lunara_still url="https://..." caption="..." style="full"]
 *   [lunara_still url="https://..." kicker="Context Shot" caption="..." style="inset"]
 *
 * Styles: default (inline), full (breaks out to viewport), hero (16:9 crop),
 *         inset (narrower centered), left (float left), right (float right),
 *         pair (half-width, use two in a row).
 */
if ( ! function_exists( 'lunara_still_shortcode' ) ) {
    function lunara_still_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'url'     => '',
                'alt'     => '',
                'caption' => '',
                'kicker'  => '',
                'style'   => 'default',
                'loading' => 'lazy',
            ),
            $atts,
            'lunara_still'
        );

        $url = trim( (string) $atts['url'] );
        if ( '' === $url ) {
            return '';
        }

        $valid_styles = array( 'default', 'full', 'hero', 'inset', 'left', 'right', 'pair' );
        $style        = in_array( $atts['style'], $valid_styles, true ) ? $atts['style'] : 'default';
        $classes      = 'lunara-still';
        if ( 'default' !== $style ) {
            $classes .= ' lunara-still--' . $style;
        }

        $alt     = trim( (string) $atts['alt'] );
        $caption = trim( (string) $atts['caption'] );
        $kicker  = trim( (string) $atts['kicker'] );

        $caption_html = '';
        if ( '' !== $caption || '' !== $kicker ) {
            $kicker_html  = '' !== $kicker ? sprintf( '<span class="lunara-still-kicker">%s</span>', esc_html( $kicker ) ) : '';
            $caption_text = '' !== $caption ? sprintf( '<p>%s</p>', esc_html( $caption ) ) : '';
            $caption_html = sprintf( '<figcaption class="lunara-still-caption">%s%s</figcaption>', $kicker_html, $caption_text );
        }

        return sprintf(
            '<figure class="%1$s"><div class="lunara-still-frame"><img class="lunara-still-image" src="%2$s" alt="%3$s" loading="%4$s" decoding="async"></div>%5$s</figure>',
            esc_attr( $classes ),
            esc_url( $url ),
            esc_attr( $alt ),
            esc_attr( $atts['loading'] ),
            $caption_html
        );
    }
}
add_shortcode( 'lunara_still', 'lunara_still_shortcode' );


/* ========================================
   WHERE TO WATCH â€” TMDB Watch Providers
   Renders real streaming/rental/buy availability for a film.
   Auto-detects IMDb ID from the current review, or accepts imdb="ttXXXXXXX".
   ======================================== */

function lunara_where_to_watch_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'imdb'    => '',
        'region'  => 'US',
    ), $atts, 'lunara_where_to_watch' );

    $imdb_id = trim( $atts['imdb'] );
    $region  = strtoupper( trim( $atts['region'] ) );

    // Auto-detect from current review post.
    if ( '' === $imdb_id ) {
        $post_id = get_the_ID();
        if ( $post_id ) {
            // Check both possible meta keys â€” reviews store the ID in _lunara_imdb_title_id.
            $imdb_id = trim( (string) get_post_meta( $post_id, '_lunara_imdb_title_id', true ) );
            if ( '' === $imdb_id ) {
                $imdb_id = trim( (string) get_post_meta( $post_id, '_lunara_imdb_id', true ) );
            }
        }
    }

    if ( '' === $imdb_id || ! preg_match( '/^tt\d+$/', $imdb_id ) ) {
        return '';
    }

    // Cache key â€” providers change rarely.
    $cache_key = 'lunara_wtw_v2_' . $imdb_id . '_' . $region;
    $cached    = get_transient( $cache_key );
    if ( is_string( $cached ) ) {
        return $cached;
    }

    // Step 1: Get the TMDB movie ID from IMDb ID.
    $aat = class_exists( 'Academy_Awards_Table' ) ? Academy_Awards_Table::get_instance() : null;
    $tmdb_id = 0;

    if ( $aat && method_exists( $aat, 'get_tmdb_data_for_imdb_id' ) ) {
        $tmdb_data = $aat->get_tmdb_data_for_imdb_id( $imdb_id );
        if ( ! empty( $tmdb_data['id'] ) ) {
            $tmdb_id = intval( $tmdb_data['id'] );
        }
    }

    if ( 0 === $tmdb_id ) {
        // Direct TMDB find as fallback.
        $key = defined( 'AAT_TMDB_API_KEY' ) ? AAT_TMDB_API_KEY : '';
        if ( '' !== $key ) {
            $find_url  = add_query_arg( array( 'api_key' => $key, 'external_source' => 'imdb_id' ), 'https://api.themoviedb.org/3/find/' . rawurlencode( $imdb_id ) );
            $find_resp = wp_remote_get( $find_url, array( 'timeout' => 10 ) );
            if ( ! is_wp_error( $find_resp ) ) {
                $find_data = json_decode( wp_remote_retrieve_body( $find_resp ), true );
                if ( ! empty( $find_data['movie_results'][0]['id'] ) ) {
                    $tmdb_id = intval( $find_data['movie_results'][0]['id'] );
                }
            }
        }
    }

    if ( 0 === $tmdb_id ) {
        set_transient( $cache_key, '', 2 * HOUR_IN_SECONDS );
        return '';
    }

    // Step 2: Fetch watch/providers for the region.
    $key = defined( 'AAT_TMDB_API_KEY' ) ? AAT_TMDB_API_KEY : '';
    if ( '' === $key ) {
        return '';
    }

    $providers_url  = add_query_arg( array( 'api_key' => $key ), 'https://api.themoviedb.org/3/movie/' . $tmdb_id . '/watch/providers' );
    $providers_resp = wp_remote_get( $providers_url, array( 'timeout' => 10 ) );
    if ( is_wp_error( $providers_resp ) ) {
        return '';
    }

    $providers_data = json_decode( wp_remote_retrieve_body( $providers_resp ), true );
    $region_data    = $providers_data['results'][ $region ] ?? array();

    if ( empty( $region_data ) ) {
        set_transient( $cache_key, '', 2 * HOUR_IN_SECONDS );
        return '';
    }

    $tmdb_link = ! empty( $region_data['link'] ) ? $region_data['link'] : '';

    // Build output â€” compact for sidebar context (max 4 logos per row).
    ob_start();
    ?>
    <div class="lunara-wtw-providers">
        <?php
        $sections = array(
            'flatrate' => 'Stream',
            'rent'     => 'Rent',
            'buy'      => 'Buy',
            'free'     => 'Free',
        );
        $has_any = false;
        foreach ( $sections as $section_key => $section_label ) :
            if ( empty( $region_data[ $section_key ] ) || ! is_array( $region_data[ $section_key ] ) ) {
                continue;
            }
            $has_any = true;
            $providers_list = array_slice( $region_data[ $section_key ], 0, 4 );
            ?>
            <div class="lunara-wtw-section">
                <span class="lunara-wtw-section-label"><?php echo esc_html( $section_label ); ?></span>
                <div class="lunara-wtw-logos">
                    <?php foreach ( $providers_list as $provider ) : ?>
                        <<?php echo $tmdb_link ? 'a' : 'span'; ?> class="lunara-wtw-logo" title="<?php echo esc_attr( $provider['provider_name'] ?? '' ); ?>"<?php echo $tmdb_link ? ' href="' . esc_url( $tmdb_link ) . '" target="_blank" rel="noopener noreferrer"' : ''; ?>>
                            <?php if ( ! empty( $provider['logo_path'] ) ) : ?>
                                <img src="<?php echo esc_url( 'https://image.tmdb.org/t/p/w92' . $provider['logo_path'] ); ?>" alt="<?php echo esc_attr( $provider['provider_name'] ?? '' ); ?>" width="28" height="28" loading="lazy" />
                            <?php else : ?>
                                <span class="lunara-wtw-logo-text"><?php echo esc_html( $provider['provider_name'] ?? '' ); ?></span>
                            <?php endif; ?>
                        </<?php echo $tmdb_link ? 'a' : 'span'; ?>>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ( ! $has_any ) : ?>
            <p class="lunara-wtw-empty">Availability data not yet available for this region.</p>
        <?php endif; ?>
        <?php if ( $tmdb_link ) : ?>
            <a class="lunara-wtw-tmdb-link" href="<?php echo esc_url( $tmdb_link ); ?>" target="_blank" rel="noopener noreferrer">Full availability on TMDB</a>
        <?php endif; ?>
    </div>
    <?php
    $html = ob_get_clean();
    set_transient( $cache_key, $html, 12 * HOUR_IN_SECONDS );
    return $html;
}
add_shortcode( 'lunara_where_to_watch', 'lunara_where_to_watch_shortcode' );

if ( ! function_exists( 'lunara_render_review_where_links' ) ) {
    function lunara_render_review_where_links( $where, $title = '', $watch_url = '' ) {
        $where = trim( (string) $where );
        if ( '' === $where ) {
            return '';
        }

        $title = trim( (string) $title );

        // Primary split: newlines (the new multi-line input format).
        // Legacy fallback: if a line has no URL + no pipe, also split it on commas
        // so old entries like "Netflix, Max, Hulu" still tokenize the same way.
        $lines = preg_split( '/\r?\n/', $where );
        $tokens = array();

        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( '' === $line ) {
                continue;
            }
            // If the line contains a URL or a pipe (Label | URL syntax), treat it as one token.
            if ( false !== stripos( $line, 'http://' )
                || false !== stripos( $line, 'https://' )
                || false !== strpos( $line, '|' ) ) {
                $tokens[] = $line;
                continue;
            }
            // Otherwise allow comma-separated names on a single line (legacy).
            $parts = preg_split( '/\s*,\s*/', $line );
            foreach ( (array) $parts as $part ) {
                $part = trim( $part );
                if ( '' !== $part ) {
                    $tokens[] = $part;
                }
            }
        }

        if ( empty( $tokens ) ) {
            $tokens = array( $where );
        }

        $provider_map = array(
            'netflix'                => 'https://www.netflix.com/search?q=%s',
            'max'                    => 'https://play.max.com/search?q=%s',
            'hbo max'                => 'https://play.max.com/search?q=%s',
            'hulu'                   => 'https://www.hulu.com/search?q=%s',
            'prime video'            => 'https://www.amazon.com/s?k=%s&i=instant-video',
            'amazon prime video'     => 'https://www.amazon.com/s?k=%s&i=instant-video',
            'amazon'                 => 'https://www.amazon.com/s?k=%s&i=instant-video',
            'apple tv+'              => 'https://tv.apple.com/search?term=%s',
            'apple tv plus'          => 'https://tv.apple.com/search?term=%s',
            'apple tv'               => 'https://tv.apple.com/search?term=%s',
            'paramount+'             => 'https://www.paramountplus.com/',
            'paramount plus'         => 'https://www.paramountplus.com/',
            'peacock'                => 'https://www.peacocktv.com/',
            'disney+'                => 'https://www.disneyplus.com/',
            'disney plus'            => 'https://www.disneyplus.com/',
            'criterion channel'      => 'https://www.criterionchannel.com/',
            'mubi'                   => 'https://mubi.com/',
            'theaters'               => '',
            'theatrical'             => '',
            'pvod'                   => '',
            'vod'                    => '',
            'digital'                => '',
        );

        $chip_html = array();
        foreach ( $tokens as $token ) {
            $label = $token;
            $token_lc = strtolower( $token );
            $url = '';

            // NEW: "Label | URL" format â€” use Dalton's label + exact URL
            if ( preg_match( '~^(.+?)\s*\|\s*(https?://\S+)\s*$~i', $token, $m ) ) {
                $label = trim( $m[1] );
                $url   = trim( $m[2] );
            } elseif ( filter_var( $token, FILTER_VALIDATE_URL ) ) {
                $url = $token;
                $host = wp_parse_url( $token, PHP_URL_HOST );
                if ( $host ) {
                    // Auto-label from hostname, prettier: netflix.com -> Netflix
                    $host = preg_replace( '#^www\.#', '', (string) $host );
                    $label_guess = preg_replace( '/\.(com|co|tv|net|org|io)(\.[a-z]{2})?$/i', '', $host );
                    $label = ucwords( str_replace( array( '-', '.' ), ' ', $label_guess ) );
                }
            } elseif ( isset( $provider_map[ $token_lc ] ) ) {
                $mapped = $provider_map[ $token_lc ];
                if ( '' !== $mapped ) {
                    $url = false !== strpos( $mapped, '%s' ) ? sprintf( $mapped, rawurlencode( $title ) ) : $mapped;
                } elseif ( '' !== $watch_url ) {
                    $url = $watch_url;
                }
            } elseif ( '' !== $watch_url ) {
                $url = $watch_url;
            }

            if ( '' !== $url ) {
                $chip_html[] = sprintf(
                    '<a class="lunara-review-single-where-chip" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
                    esc_url( $url ),
                    esc_html( $label )
                );
            } else {
                $chip_html[] = sprintf(
                    '<span class="lunara-review-single-where-chip is-static">%s</span>',
                    esc_html( $label )
                );
            }
        }

        return '<div class="lunara-review-single-where-links">' . implode( '', $chip_html ) . '</div>';
    }
}


/* ========================================
   LUNARA 2.0 - REVIEW ARCHIVES / LEDGER HIGHLIGHTS / METADATA
   ======================================== */

/**
 * Register archive taxonomies for director and review year.
 */
if ( ! defined( 'LUNARA_CORE_VERSION' ) ) {
    function lunara_register_review_taxonomies() {
        register_taxonomy( 'lunara_director', array( 'review' ), array(
            'labels' => array(
                'name'          => __( 'Directors', 'lunara-film' ),
                'singular_name' => __( 'Director', 'lunara-film' ),
            ),
            'public'       => true,
            'hierarchical' => false,
            'show_in_rest' => true,
            'rewrite'      => array( 'slug' => 'director' ),
        ) );

        register_taxonomy( 'lunara_review_year', array( 'review' ), array(
            'labels' => array(
                'name'          => __( 'Review Years', 'lunara-film' ),
                'singular_name' => __( 'Review Year', 'lunara-film' ),
            ),
            'public'       => true,
            'hierarchical' => false,
            'show_in_rest' => true,
            'rewrite'      => array( 'slug' => 'review-year' ),
        ) );
    }
    add_action( 'init', 'lunara_register_review_taxonomies', 20 );

    /**
     * Review detail meta box.
     */
    function lunara_add_review_details_meta_box() {
        add_meta_box(
            'lunara_review_details_meta',
            'Review Details',
            'lunara_review_details_meta_callback',
            'review',
            'side',
            'default'
        );
    }
    add_action( 'add_meta_boxes', 'lunara_add_review_details_meta_box' );

    function lunara_review_details_meta_callback( $post ) {
        wp_nonce_field( 'lunara_review_details_nonce', 'lunara_review_details_nonce' );
        $director = get_post_meta( $post->ID, '_lunara_director', true );
        $runtime  = get_post_meta( $post->ID, '_lunara_runtime', true );
        $studio   = get_post_meta( $post->ID, '_lunara_studio', true );
        ?>
        <p><label for="lunara_director"><strong>Director</strong></label><br>
        <input type="text" name="lunara_director" id="lunara_director" value="<?php echo esc_attr( $director ); ?>" style="width:100%;"></p>

        <p><label for="lunara_runtime"><strong>Runtime</strong></label><br>
        <input type="text" name="lunara_runtime" id="lunara_runtime" value="<?php echo esc_attr( $runtime ); ?>" placeholder="142 min" style="width:100%;"></p>

        <p><label for="lunara_studio"><strong>Studio / Distributor</strong></label><br>
        <input type="text" name="lunara_studio" id="lunara_studio" value="<?php echo esc_attr( $studio ); ?>" style="width:100%;"></p>
        <?php
    }

    function lunara_save_review_details_meta( $post_id ) {
        if ( ! isset( $_POST['lunara_review_details_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['lunara_review_details_nonce'], 'lunara_review_details_nonce' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        foreach ( array( 'lunara_director', 'lunara_runtime', 'lunara_studio' ) as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
            }
        }
    }
    add_action( 'save_post_review', 'lunara_save_review_details_meta' );

    /**
     * Keep archive taxonomies synchronized with review meta.
     */
    function lunara_sync_review_archive_terms( $post_id ) {
        if ( wp_is_post_revision( $post_id ) || 'review' !== get_post_type( $post_id ) ) {
            return;
        }

        $director = trim( (string) get_post_meta( $post_id, '_lunara_director', true ) );
        $year     = trim( (string) get_post_meta( $post_id, '_lunara_year', true ) );

        if ( $director !== '' ) {
            wp_set_object_terms( $post_id, array( $director ), 'lunara_director', false );
        }

        if ( $year !== '' ) {
            wp_set_object_terms( $post_id, array( $year ), 'lunara_review_year', false );
        }
    }
    add_action( 'save_post_review', 'lunara_sync_review_archive_terms', 30 );
}

/**
 * Visual Image Toolkit â€” helper panel in the review editor sidebar.
 * Placed outside LUNARA_CORE_VERSION guard so it always registers.
 */
if ( ! function_exists( 'lunara_add_image_toolkit_meta_box' ) ) {
    function lunara_add_image_toolkit_meta_box() {
        foreach ( array( 'review', 'journal' ) as $pt ) {
            add_meta_box(
                'lunara_image_toolkit',
                'Lunara Image Toolkit',
                'lunara_image_toolkit_callback',
                $pt,
                'side',
                'high'
            );
        }
    }
    add_action( 'add_meta_boxes', 'lunara_add_image_toolkit_meta_box' );

    function lunara_image_toolkit_callback( $post ) {
        ?>
        <style>
            .lunara-toolkit-section { margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #2c3e50; }
            .lunara-toolkit-section:last-child { border-bottom: none; margin-bottom: 0; }
            .lunara-toolkit-section h4 { margin: 0 0 6px; color: #c9a961; font-size: 12px; text-transform: uppercase; letter-spacing: .08em; }
            .lunara-toolkit-section p { margin: 0 0 6px; font-size: 12px; color: #8899aa; line-height: 1.5; }
            .lunara-toolkit-code { display: block; margin: 6px 0; padding: 8px 10px; background: #0d1923; border: 1px solid #1e3045; border-radius: 6px; font-family: monospace; font-size: 11px; color: #e0c481; white-space: pre-wrap; word-break: break-all; cursor: pointer; position: relative; }
            .lunara-toolkit-code:hover { border-color: #c9a961; }
            .lunara-toolkit-code::after { content: 'click to copy'; position: absolute; top: 4px; right: 6px; font-size: 9px; color: #556677; font-family: sans-serif; }
            .lunara-toolkit-styles { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; margin-top: 6px; }
            .lunara-toolkit-styles span { display: block; padding: 4px 6px; background: #0d1923; border: 1px solid #1e3045; border-radius: 4px; font-size: 10px; color: #8899aa; text-align: center; }
            .lunara-toolkit-styles span strong { color: #e0c481; display: block; font-size: 11px; }
        </style>

        <div class="lunara-toolkit-section">
            <h4>Quick Method â€” Media Insert</h4>
            <p>Just drop an image into the review body using the <strong>+</strong> button or <code>/image</code>. It auto-gets the Lunara cinematic frame treatment. Use <strong>Full Width</strong> alignment for edge-to-edge stills.</p>
        </div>

        <div class="lunara-toolkit-section">
            <h4>Power Method â€” Shortcode</h4>
            <p>Paste this in the review body wherever you want it:</p>
            <code class="lunara-toolkit-code" onclick="navigator.clipboard.writeText(this.innerText.replace('click to copy','').trim())">[lunara_still url="" caption="" kicker="" style="default"]</code>
        </div>

        <div class="lunara-toolkit-section">
            <h4>Available Styles</h4>
            <div class="lunara-toolkit-styles">
                <span><strong>default</strong>Inline frame</span>
                <span><strong>full</strong>Viewport-wide</span>
                <span><strong>hero</strong>16:9 crop</span>
                <span><strong>inset</strong>Centered narrow</span>
                <span><strong>left</strong>Float left</span>
                <span><strong>right</strong>Float right</span>
                <span><strong>pair</strong>Half-width</span>
            </div>
        </div>

        <div class="lunara-toolkit-section">
            <h4>Examples</h4>
            <code class="lunara-toolkit-code" onclick="navigator.clipboard.writeText(this.innerText.replace('click to copy','').trim())">[lunara_still url="https://..." style="full" caption="The frame that proves the point" kicker="Visual Evidence"]</code>
            <code class="lunara-toolkit-code" onclick="navigator.clipboard.writeText(this.innerText.replace('click to copy','').trim())">[lunara_still url="https://..." style="inset" caption="A quieter moment"]</code>
        </div>

        <div class="lunara-toolkit-section">
            <h4>Attributes</h4>
            <p><strong>url</strong> â€” image URL (required)<br>
            <strong>caption</strong> â€” italic text below<br>
            <strong>kicker</strong> â€” gold uppercase label<br>
            <strong>style</strong> â€” layout style<br>
            <strong>alt</strong> â€” accessibility text<br>
            <strong>loading</strong> â€” eager or lazy</p>
        </div>

        <script>
        document.querySelectorAll('.lunara-toolkit-code').forEach(function(el) {
            el.addEventListener('click', function() {
                var text = el.innerText.replace('click to copy', '').trim();
                navigator.clipboard.writeText(text).then(function() {
                    var orig = el.style.borderColor;
                    el.style.borderColor = '#c9a961';
                    setTimeout(function() { el.style.borderColor = orig; }, 800);
                });
            });
        });
        </script>
        <?php
    }
}

/**
 * Helper for card excerpt.
 */
if ( ! function_exists( 'lunara_card_excerpt' ) ) {
function lunara_card_excerpt( $post_id, $words = 22 ) {
    if ( has_excerpt( $post_id ) ) {
        return wp_trim_words( get_the_excerpt( $post_id ), $words );
    }
    return wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), $words );
}
}

/**
 * Cached home section review IDs.
 */
if ( ! function_exists( 'lunara_cached_review_ids' ) ) {
function lunara_cached_review_ids( $cache_group, $count, $query_args ) {
    $count = max( 1, (int) $count );
    $cache_key = sprintf( 'lunara_%s_%d_v1', sanitize_key( $cache_group ), $count );
    $post_ids = get_transient( $cache_key );

    if ( ! is_array( $post_ids ) ) {
        $query_args = wp_parse_args(
            $query_args,
            array(
                'post_type'              => 'review',
                'posts_per_page'         => $count,
                'post_status'            => 'publish',
                'ignore_sticky_posts'    => true,
                'no_found_rows'          => true,
                'fields'                 => 'ids',
                'orderby'                => 'date',
                'order'                  => 'DESC',
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            )
        );

        $query_args['posts_per_page'] = $count;
        $post_ids = get_posts( $query_args );
        $post_ids = array_values( array_map( 'intval', is_array( $post_ids ) ? $post_ids : array() ) );

        set_transient( $cache_key, $post_ids, 15 * MINUTE_IN_SECONDS );
    }

    return $post_ids;
}
}

if ( ! function_exists( 'lunara_curated_review_ids_by_meta' ) ) {
function lunara_curated_review_ids_by_meta( $cache_group, $flag_meta_key, $priority_meta_key, $count ) {
    $count = max( 1, (int) $count );
    $cache_key = sprintf( 'lunara_%s_%d_v1', sanitize_key( $cache_group ), $count );
    $post_ids = get_transient( $cache_key );

    if ( ! is_array( $post_ids ) ) {
        $post_ids = get_posts(
            array(
                'post_type'              => 'review',
                'posts_per_page'         => $count,
                'post_status'            => 'publish',
                'ignore_sticky_posts'    => true,
                'no_found_rows'          => true,
                'fields'                 => 'ids',
                'meta_key'               => $priority_meta_key,
                'orderby'                => array(
                    'meta_value_num' => 'ASC',
                    'date'           => 'DESC',
                ),
                'meta_query'             => array(
                    array(
                        'key'   => $flag_meta_key,
                        'value' => '1',
                    ),
                ),
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            )
        );

        $post_ids = array_values( array_map( 'intval', is_array( $post_ids ) ? $post_ids : array() ) );
        set_transient( $cache_key, $post_ids, 15 * MINUTE_IN_SECONDS );
    }

    return $post_ids;
}
}

/**
 * Prime post caches used by front-page cards before rendering.
 */
if ( ! function_exists( 'lunara_prime_review_card_caches' ) ) {
function lunara_prime_review_card_caches( $post_ids ) {
    $post_ids = array_values( array_filter( array_map( 'intval', (array) $post_ids ) ) );
    if ( empty( $post_ids ) ) {
        return;
    }

    update_meta_cache( 'post', $post_ids );
    update_object_term_cache( $post_ids, 'post' );
}
}

/**
 * Build a review query from cached IDs.
 */
if ( ! function_exists( 'lunara_reviews_query_from_ids' ) ) {
function lunara_reviews_query_from_ids( $post_ids ) {
    $post_ids = array_values( array_filter( array_map( 'intval', (array) $post_ids ) ) );

    if ( empty( $post_ids ) ) {
        return new WP_Query(
            array(
                'post_type'      => 'review',
                'post__in'       => array( 0 ),
                'posts_per_page' => 0,
                'no_found_rows'  => true,
            )
        );
    }

    lunara_prime_review_card_caches( $post_ids );

    return new WP_Query(
        array(
            'post_type'              => 'review',
            'post__in'               => $post_ids,
            'posts_per_page'         => count( $post_ids ),
            'orderby'                => 'post__in',
            'post_status'            => 'publish',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        )
    );
}
}

/**
 * Build a standard post query from a curated list of IDs.
 */
if ( ! function_exists( 'lunara_posts_query_from_ids' ) ) {
function lunara_posts_query_from_ids( $post_ids ) {
    $post_ids = array_values( array_filter( array_map( 'intval', (array) $post_ids ) ) );

    if ( empty( $post_ids ) ) {
        return new WP_Query(
            array(
                'post_type'      => 'post',
                'post__in'       => array( 0 ),
                'posts_per_page' => 0,
                'no_found_rows'  => true,
            )
        );
    }

    lunara_prime_review_card_caches( $post_ids );

    return new WP_Query(
        array(
            'post_type'              => 'post',
            'post__in'               => $post_ids,
            'posts_per_page'         => count( $post_ids ),
            'orderby'                => 'post__in',
            'post_status'            => 'publish',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        )
    );
}
}

/**
 * Resolve the editorial label for a standard post card.
 */
if ( ! function_exists( 'lunara_get_dispatch_type_label' ) ) {
function lunara_get_dispatch_type_label( $post_id ) {
    $priority_labels = array(
        'podcast'      => __( 'Podcast', 'lunara-film' ),
        'audio'        => __( 'Podcast', 'lunara-film' ),
        'news'         => __( 'News', 'lunara-film' ),
        'reaction'     => __( 'Reaction', 'lunara-film' ),
        'reactions'    => __( 'Reaction', 'lunara-film' ),
        'think-piece'  => __( 'Think Piece', 'lunara-film' ),
        'think-pieces' => __( 'Think Piece', 'lunara-film' ),
        'essay'        => __( 'Essay', 'lunara-film' ),
        'essays'       => __( 'Essay', 'lunara-film' ),
        'ink'          => __( 'Ink', 'lunara-film' ),
        'interview'    => __( 'Interview', 'lunara-film' ),
    );

    $normalize_label = static function( $label ) {
        $label = trim( (string) $label );
        if ( in_array( $label, array( 'Dispatch', 'Dispatches', 'Dispatches & Audio' ), true ) ) {
            return __( 'Journal', 'lunara-film' );
        }

        return $label;
    };

    // Journal CPT entries: prefer journal_type taxonomy first.
    if ( 'journal' === get_post_type( $post_id ) ) {
        $journal_terms = get_the_terms( $post_id, 'journal_type' );
        if ( is_array( $journal_terms ) && ! empty( $journal_terms ) ) {
            // Match against priority slug map first (canonical labels).
            foreach ( $priority_labels as $slug => $label ) {
                foreach ( $journal_terms as $term ) {
                    if ( $term instanceof WP_Term && $term->slug === $slug ) {
                        return $label;
                    }
                }
            }
            // Otherwise use the first term's display name.
            foreach ( $journal_terms as $term ) {
                if ( $term instanceof WP_Term && '' !== trim( (string) $term->name ) ) {
                    return $normalize_label( $term->name );
                }
            }
        }
        // Fall through to "Journal" rather than "Dispatch" for journal CPT.
        return __( 'Journal', 'lunara-film' );
    }

    // Standard posts: use category as before.
    $terms = get_the_terms( $post_id, 'category' );
    if ( ! is_array( $terms ) ) {
        return __( 'Journal', 'lunara-film' );
    }

    foreach ( $priority_labels as $slug => $label ) {
        foreach ( $terms as $term ) {
            if ( $term instanceof WP_Term && $term->slug === $slug ) {
                return $label;
            }
        }
    }

    foreach ( $terms as $term ) {
        if ( ! ( $term instanceof WP_Term ) ) {
            continue;
        }

        if ( 'uncategorized' === $term->slug ) {
            continue;
        }

        if ( '' !== trim( (string) $term->name ) ) {
            return $normalize_label( $term->name );
        }
    }

    return __( 'Journal', 'lunara-film' );
}
}

/**
 * Resolve a stable editorial type slug for styling and layout accents.
 */
if ( ! function_exists( 'lunara_get_dispatch_type_slug' ) ) {
function lunara_get_dispatch_type_slug( $post_id ) {
    $priority_slugs = array(
        'podcast'      => 'podcast',
        'audio'        => 'podcast',
        'news'         => 'news',
        'reaction'     => 'reaction',
        'reactions'    => 'reaction',
        'think-piece'  => 'essay',
        'think-pieces' => 'essay',
        'essay'        => 'essay',
        'essays'       => 'essay',
        'ink'          => 'ink',
        'interview'    => 'interview',
    );

    $terms = get_the_terms( $post_id, 'category' );
    if ( ! is_array( $terms ) ) {
        return 'dispatch';
    }

    foreach ( $priority_slugs as $slug => $resolved_slug ) {
        foreach ( $terms as $term ) {
            if ( $term instanceof WP_Term && $term->slug === $slug ) {
                return $resolved_slug;
            }
        }
    }

    foreach ( $terms as $term ) {
        if ( ! ( $term instanceof WP_Term ) || 'uncategorized' === $term->slug ) {
            continue;
        }

        $fallback_slug = sanitize_title( (string) $term->slug );
        if ( '' !== $fallback_slug ) {
            return $fallback_slug;
        }
    }

    return 'dispatch';
}
}

/**
 * Return the editorial category slugs configured for the journal lane.
 */
if ( ! function_exists( 'lunara_get_dispatch_category_slugs' ) ) {
function lunara_get_dispatch_category_slugs() {
    $raw_slugs = lunara_theme_mod_text( 'lunara_home_dispatch_category_slugs', 'news,think-pieces,reactions,podcast' );
    return array_values( array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', $raw_slugs ) ) ) ) );
}
}

/**
 * Determine whether a category term belongs to the editorial dispatch lane.
 */
if ( ! function_exists( 'lunara_is_editorial_category_term' ) ) {
function lunara_is_editorial_category_term( $term ) {
    if ( ! ( $term instanceof WP_Term ) || 'category' !== $term->taxonomy ) {
        return false;
    }

    return in_array( $term->slug, lunara_get_dispatch_category_slugs(), true );
}
}

/**
 * Resolve the fallback archive URL for the homepage dispatches section.
 */
if ( ! function_exists( 'lunara_home_dispatch_archive_url' ) ) {
function lunara_home_dispatch_archive_url() {
    // Priority 1: explicit customizer override (only if user set one).
    $custom_url = lunara_theme_mod_url( 'lunara_home_dispatch_button_url', '' );
    if ( '' !== $custom_url ) {
        return $custom_url;
    }

    // Priority 2: journal CPT archive â€” the canonical destination going forward.
    if ( post_type_exists( 'journal' ) ) {
        $journal_archive = get_post_type_archive_link( 'journal' );
        if ( $journal_archive ) {
            return $journal_archive;
        }
    }

    // Priority 3 (legacy fallback): category-based dispatch URLs.
    $slugs = lunara_get_dispatch_category_slugs();

    foreach ( $slugs as $slug ) {
        $term = get_category_by_slug( $slug );
        if ( $term instanceof WP_Term && intval( $term->count ) > 0 ) {
            $term_link = get_term_link( $term );
            if ( ! is_wp_error( $term_link ) ) {
                return $term_link;
            }
        }
    }

    $posts_page_id = absint( get_option( 'page_for_posts' ) );
    if ( $posts_page_id > 0 ) {
        $posts_page_url = get_permalink( $posts_page_id );
        if ( is_string( $posts_page_url ) && '' !== $posts_page_url ) {
            return $posts_page_url;
        }
    }

    foreach ( array( 'news', 'journal', 'blog' ) as $path ) {
        $page = get_page_by_path( $path );
        if ( $page instanceof WP_Post ) {
            $page_url = get_permalink( $page );
            if ( is_string( $page_url ) && '' !== $page_url ) {
                return $page_url;
            }
        }
    }

    $latest_post = get_posts(
        array(
            'post_type'              => 'post',
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'ignore_sticky_posts'    => true,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        )
    );

    if ( ! empty( $latest_post[0] ) ) {
        return get_permalink( intval( $latest_post[0] ) );
    }

    return home_url( '/news/' );
}
}

/**
 * Build the year/director line used on review cards.
 */
if ( ! function_exists( 'lunara_get_review_card_meta' ) ) {
function lunara_get_review_card_meta( $post_id ) {
    $post_id  = intval( $post_id );
    $year     = trim( (string) get_post_meta( $post_id, '_lunara_year', true ) );
    $director = trim( (string) get_post_meta( $post_id, '_lunara_director', true ) );
    $parts    = array();

    if ( '' !== $year ) {
        $parts[] = $year;
    }

    if ( '' !== $director ) {
        $parts[] = $director;
    }

    return implode( ' / ', $parts );
}
}

/**
 * Provide one uniform teaser line for review cards.
 */
if ( ! function_exists( 'lunara_get_review_card_teaser' ) ) {
function lunara_get_review_card_teaser() {
    return __( 'Open the review and enter the full argument.', 'lunara-film' );
}
}

/**
 * Pull the reader-facing quote/excerpt for review cards.
 */
if ( ! function_exists( 'lunara_get_review_card_pull_quote' ) ) {
function lunara_get_review_card_pull_quote( $post_id, $words = 46, $allow_fallback = false ) {
    $post_id = intval( $post_id );
    $words   = max( 12, intval( $words ) );

    if ( $post_id <= 0 ) {
        return '';
    }

    $clean_exact = static function( $value ) {
        $value = strip_shortcodes( (string) $value );
        $value = html_entity_decode( $value, ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
        $value = wp_strip_all_tags( $value );
        $value = preg_replace( '/\s+/', ' ', $value );
        return trim( (string) $value );
    };

    $clean_fallback = static function( $value ) use ( $words, $clean_exact ) {
        $value = $clean_exact( $value );

        return '' === $value ? '' : wp_trim_words( $value, $words, '...' );
    };

    $meta_keys = array(
        '_lunara_pull_quote',
        '_lunara_review_pull_quote',
        '_lunara_review_quote',
        '_lunara_card_quote',
        '_lunara_card_excerpt',
        '_lunara_deck',
        '_lunara_summary',
        'lunara_pull_quote',
        'lunara_review_pull_quote',
        'lunara_review_quote',
        'lunara_card_quote',
        'lunara_card_excerpt',
        'lunara_deck',
        'lunara_summary',
    );

    foreach ( $meta_keys as $meta_key ) {
        $quote = $clean_exact( get_post_meta( $post_id, $meta_key, true ) );
        if ( '' !== $quote ) {
            return $quote;
        }
    }

    if ( ! $allow_fallback ) {
        return '';
    }

    if ( has_excerpt( $post_id ) ) {
        $quote = $clean_fallback( get_the_excerpt( $post_id ) );
        if ( '' !== $quote ) {
            return $quote;
        }
    }

    if ( function_exists( 'lunara_card_excerpt' ) ) {
        $quote = $clean_fallback( lunara_card_excerpt( $post_id, $words ) );
        if ( '' !== $quote ) {
            return $quote;
        }
    }

    return $clean_fallback( get_post_field( 'post_content', $post_id ) );
}
}

/**
 * Resolve the preferred image for review cards without changing the single-review hero.
 */
if ( ! function_exists( 'lunara_get_review_card_image_data' ) ) {
function lunara_get_review_card_image_data( $post_id, $size = 'medium_large', $attrs = array() ) {
    $post_id = intval( $post_id );

    if ( $post_id <= 0 ) {
        return array(
            'url'       => '',
            'html'      => '',
            'has_image' => false,
        );
    }

    $title = trim( wp_strip_all_tags( get_the_title( $post_id ) ) );
    $attrs = wp_parse_args(
        is_array( $attrs ) ? $attrs : array(),
        array(
            'class'    => 'lunara-review-grid-poster',
            'loading'  => 'lazy',
            'decoding' => 'async',
            'sizes'    => '(max-width: 720px) 92vw, (max-width: 1180px) 42vw, 260px',
            'alt'      => '' !== $title ? sprintf( __( '%s artwork', 'lunara-film' ), $title ) : __( 'Review artwork', 'lunara-film' ),
        )
    );

    $url      = '';
    $html     = '';
    $card_url = trim( (string) get_post_meta( $post_id, '_lunara_review_card_image', true ) );

    if ( '' !== $card_url ) {
        $attachment_id = attachment_url_to_postid( $card_url );

        if ( $attachment_id > 0 ) {
            $url  = (string) wp_get_attachment_image_url( $attachment_id, $size );
            $html = (string) wp_get_attachment_image( $attachment_id, $size, false, $attrs );

            if ( '' === $url ) {
                $url = esc_url_raw( $card_url );
            }
        } else {
            $url = esc_url_raw( $card_url );

            if ( '' !== $url ) {
                $attr_html = '';

                foreach ( $attrs as $attr_name => $attr_value ) {
                    $attr_name = preg_replace( '/[^a-zA-Z0-9:_-]/', '', (string) $attr_name );

                    if ( '' === $attr_name || false === $attr_value || null === $attr_value ) {
                        continue;
                    }

                    if ( true === $attr_value ) {
                        $attr_html .= ' ' . esc_attr( $attr_name );
                        continue;
                    }

                    if ( is_scalar( $attr_value ) && '' !== (string) $attr_value ) {
                        $attr_html .= ' ' . esc_attr( $attr_name ) . '="' . esc_attr( (string) $attr_value ) . '"';
                    }
                }

                $html = sprintf( '<img src="%1$s"%2$s>', esc_url( $url ), $attr_html );
            }
        }
    }

    if ( '' === $html && has_post_thumbnail( $post_id ) ) {
        $thumbnail_id = get_post_thumbnail_id( $post_id );
        $url          = $thumbnail_id ? (string) wp_get_attachment_image_url( $thumbnail_id, $size ) : '';
        $html         = (string) get_the_post_thumbnail( $post_id, $size, $attrs );

        if ( '' === $url ) {
            $url = (string) get_the_post_thumbnail_url( $post_id, $size );
        }
    }

    return array(
        'url'       => (string) $url,
        'html'      => (string) $html,
        'has_image' => '' !== $html || '' !== $url,
    );
}
}

/**
 * Build a stable Oscars title URL from an IMDb title id.
 */
if ( ! function_exists( 'lunara_get_oscars_title_url' ) ) {
    function lunara_get_oscars_title_url( $imdb_title_id ) {
        $imdb_title_id = strtolower( trim( (string) $imdb_title_id ) );
        if ( ! preg_match( '/^tt\d{7,8}$/', $imdb_title_id ) ) {
            return '';
        }

        return home_url( '/oscars/title/' . rawurlencode( $imdb_title_id ) . '/' );
    }
}

/**
 * Render the filtered review content so it can be re-sectioned inside the template.
 */
if ( ! function_exists( 'lunara_get_review_rendered_content' ) ) {
    function lunara_get_review_rendered_content( $post_id ) {
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            return '';
        }

        $content = get_post_field( 'post_content', $post_id );
        if ( ! is_string( $content ) || '' === trim( $content ) ) {
            return '';
        }

        return (string) apply_filters( 'the_content', $content );
    }
}

/**
 * Add Lunara-owned links and Oscar pills to paired-film lines inside the debrief.
 */
if ( ! function_exists( 'lunara_enhance_review_debrief_html' ) ) {
    function lunara_enhance_review_debrief_html( $html ) {
        $html = trim( (string) $html );
        if ( '' === $html ) {
            return '';
        }

        return preg_replace_callback(
            '~(<strong>[^<]+:</strong>\s*)([^<]+?)\s*\|\s*IMDB:\s*(tt\d{7,8})(?:\s*(?:â€”|-)\s*([^<]+)|\s*<em>\s*(?:â€”|-)\s*([^<]+)\s*</em>)?~iu',
            static function( $matches ) {
                $label = $matches[1];
                $title = trim( wp_strip_all_tags( $matches[2] ) );
                $tt_id = strtolower( trim( $matches[3] ) );
                $note  = isset( $matches[4] ) && '' !== trim( (string) $matches[4] )
                    ? trim( wp_strip_all_tags( $matches[4] ) )
                    : ( isset( $matches[5] ) ? trim( wp_strip_all_tags( $matches[5] ) ) : '' );

                if ( '' !== $note ) {
                    $note = preg_replace( '/^\s*[â€”-]\s*/u', '', $note );
                }

                $letterbox_url = 'https://letterboxd.com/search/' . rawurlencode( $title ) . '/';
                $title_html    = sprintf(
                    '<a class="lunara-pair-title-link" href="%s" target="_blank" rel="noopener noreferrer nofollow"><em>%s</em></a>',
                    esc_url( $letterbox_url ),
                    esc_html( $title )
                );

                $imdb_chip = sprintf(
                    '<a class="lunara-debrief-chip lunara-debrief-chip-imdb" href="%s" target="_blank" rel="noopener noreferrer nofollow">IMDb</a>',
                    esc_url( 'https://www.imdb.com/title/' . rawurlencode( $tt_id ) . '/' )
                );

                $pill = '';
                if ( function_exists( 'lunara_get_oscar_ledger_counts' ) && function_exists( 'lunara_render_oscar_ledger_pill' ) ) {
                    $pill = lunara_render_oscar_ledger_pill( $tt_id, lunara_get_oscar_ledger_counts( $tt_id ) );
                }

                $poster_html = '';
                if ( class_exists( 'Academy_Awards_Table' ) ) {
                    $aat = Academy_Awards_Table::get_instance();
                    if ( $aat && method_exists( $aat, 'get_poster_img_html_for_title' ) ) {
                        $poster_html = (string) $aat->get_poster_img_html_for_title(
                            $tt_id,
                            'medium',
                            array(
                                'class'    => 'lunara-debrief-thumb',
                                'loading'  => 'lazy',
                                'decoding' => 'async',
                            )
                        );
                    }
                }

                $line_1    = '<span class="lunara-debrief-line1">' . $title_html . ' ' . $imdb_chip . ( '' !== $pill ? ' ' . $pill : '' ) . '</span>';
                $line_2    = '' !== $note ? '<span class="lunara-debrief-note">' . esc_html( $note ) . '</span>' : '';
                $text_html = '<span class="lunara-debrief-pairing-text">' . $line_1 . $line_2 . '</span>';

                if ( '' === $poster_html ) {
                    return $label . $text_html;
                }

                return $label
                    . '<span class="lunara-debrief-pairing">'
                    . '<span class="lunara-debrief-thumb-wrap">' . $poster_html . '</span>'
                    . $text_html
                    . '</span>';
            },
            $html
        );
    }
}

/**
 * Render the reviewed film poster for the debrief signature area.
 */
if ( ! function_exists( 'lunara_get_review_debrief_signature_media_html' ) ) {
    function lunara_get_review_debrief_signature_media_html( $post_id ) {
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            return '';
        }

        $poster_html = '';
        $title_id    = function_exists( 'lunara_get_review_imdb_title_id' )
            ? lunara_get_review_imdb_title_id( $post_id )
            : '';

        if ( '' !== $title_id && class_exists( 'Academy_Awards_Table' ) ) {
            $aat = Academy_Awards_Table::get_instance();
            if ( $aat && method_exists( $aat, 'get_poster_img_html_for_title' ) ) {
                $poster_html = (string) $aat->get_poster_img_html_for_title(
                    $title_id,
                    'large',
                    array(
                        'class'    => 'lunara-review-single-debrief-poster',
                        'loading'  => 'lazy',
                        'decoding' => 'async',
                    )
                );
            }
        }

        if ( '' === trim( $poster_html ) && has_post_thumbnail( $post_id ) ) {
            $poster_html = (string) get_the_post_thumbnail(
                $post_id,
                'medium_large',
                array(
                    'class'    => 'lunara-review-single-debrief-poster',
                    'loading'  => 'lazy',
                    'decoding' => 'async',
                )
            );
        }

        $title = trim( wp_strip_all_tags( get_the_title( $post_id ) ) );
        $year  = trim( (string) get_post_meta( $post_id, '_lunara_year', true ) );
        $meta  = '' !== $year ? $year : __( 'Review anchor', 'lunara-film' );

        if ( '' === trim( $poster_html ) ) {
            return '';
        }

        $poster_label = '' !== $title ? $title . ' poster' : __( 'Reviewed film poster', 'lunara-film' );
        $poster_html  = preg_replace(
            '/\salt="[^"]*"/i',
            ' alt="' . esc_attr( $poster_label ) . '"',
            $poster_html,
            1
        );
        $poster_html  = preg_replace(
            '/\sdata-image-title="[^"]*"/i',
            ' data-image-title="' . esc_attr( $title ) . '"',
            $poster_html,
            1
        );

        return sprintf(
            '<aside class="lunara-review-single-debrief-media" aria-label="%1$s"><p class="lunara-home-section-kicker">%2$s</p><div class="lunara-review-single-debrief-poster-shell">%3$s</div><div class="lunara-review-single-debrief-media-copy"><p class="lunara-review-single-debrief-media-title">%4$s</p><p class="lunara-review-single-debrief-media-meta">%5$s</p></div></aside>',
            esc_attr__( 'Reviewed film poster', 'lunara-film' ),
            esc_html__( 'Reviewed Film', 'lunara-film' ),
            $poster_html,
            esc_html( $title ),
            esc_html( $meta )
        );
    }
}

if ( ! function_exists( 'lunara_get_dom_node_outer_html' ) ) {
    function lunara_get_dom_node_outer_html( DOMNode $node ) {
        $document = $node->ownerDocument;
        return $document instanceof DOMDocument ? (string) $document->saveHTML( $node ) : '';
    }
}

if ( ! function_exists( 'lunara_split_review_debrief_block' ) ) {
    function lunara_split_review_debrief_block( $html ) {
        $html = trim( (string) $html );

        $fallback = array(
            'signature' => $html,
            'pairings'  => '',
        );

        if ( '' === $html || ! class_exists( 'DOMDocument' ) ) {
            return $fallback;
        }

        $previous = libxml_use_internal_errors( true );
        $document = new DOMDocument( '1.0', 'UTF-8' );
        $wrapper  = '<!DOCTYPE html><html><body><div id="lunara-debrief-root">' . $html . '</div></body></html>';

        if ( ! $document->loadHTML( mb_convert_encoding( $wrapper, 'HTML-ENTITIES', 'UTF-8' ) ) ) {
            libxml_clear_errors();
            libxml_use_internal_errors( $previous );
            return $fallback;
        }

        $root  = $document->getElementById( 'lunara-debrief-root' );
        $xpath = new DOMXPath( $document );

        if ( ! ( $root instanceof DOMElement ) ) {
            libxml_clear_errors();
            libxml_use_internal_errors( $previous );
            return $fallback;
        }

        $block = $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " lunara-debrief-block ")]', $root )->item( 0 );
        $list  = $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " lunara-debrief-list ")]', $root )->item( 0 );

        if ( ! ( $block instanceof DOMElement ) || ! ( $list instanceof DOMElement ) ) {
            libxml_clear_errors();
            libxml_use_internal_errors( $previous );
            return $fallback;
        }

        $heading_html = '';
        $kicker_html  = '';
        foreach ( $block->childNodes as $child ) {
            if ( ! ( $child instanceof DOMElement ) ) {
                continue;
            }

            $class_name = ' ' . trim( (string) $child->getAttribute( 'class' ) ) . ' ';
            if ( false !== strpos( $class_name, ' lunara-debrief-heading ' ) ) {
                $heading_html = lunara_get_dom_node_outer_html( $child );
            } elseif ( false !== strpos( $class_name, ' lunara-debrief-kicker ' ) ) {
                $kicker_html = lunara_get_dom_node_outer_html( $child );
            }
        }

        $signature_items = array();
        $pairing_items   = array();
        $pairing_title   = __( 'Pair It With', 'lunara-film' );
        $in_pairings     = false;

        foreach ( $list->childNodes as $child ) {
            if ( ! ( $child instanceof DOMElement ) || 'li' !== strtolower( $child->tagName ) ) {
                continue;
            }

            $class_name = ' ' . trim( (string) $child->getAttribute( 'class' ) ) . ' ';
            if ( false !== strpos( $class_name, ' lunara-debrief-pair-header ' ) ) {
                $in_pairings   = true;
                $pairing_title = trim( wp_strip_all_tags( $child->textContent ) ) ?: $pairing_title;
                continue;
            }

            if ( $in_pairings ) {
                $pairing_items[] = lunara_get_dom_node_outer_html( $child );
            } else {
                $signature_items[] = lunara_get_dom_node_outer_html( $child );
            }
        }

        $signature_html = '';
        if ( ! empty( $heading_html ) || ! empty( $kicker_html ) || ! empty( $signature_items ) ) {
            $signature_html .= '<section class="lunara-debrief-block lunara-debrief-block--signature">';
            $signature_html .= $heading_html;
            $signature_html .= $kicker_html;
            if ( ! empty( $signature_items ) ) {
                $signature_html .= '<ul class="lunara-debrief-list lunara-debrief-list--signature">' . implode( '', $signature_items ) . '</ul>';
            }
            $signature_html .= '</section>';
        }

        $pairings_html = '';
        if ( ! empty( $pairing_items ) ) {
            $pairings_html .= '<section class="lunara-debrief-block lunara-debrief-block--pairings">';
            $pairings_html .= '<div class="lunara-debrief-pairings-head">';
            $pairings_html .= '<p class="lunara-debrief-kicker lunara-debrief-kicker--pairings">' . esc_html( $pairing_title ) . '</p>';
            $pairings_html .= '</div>';
            $pairings_html .= '<ul class="lunara-debrief-list lunara-debrief-list--pairings">' . implode( '', $pairing_items ) . '</ul>';
            $pairings_html .= '</section>';
        }

        libxml_clear_errors();
        libxml_use_internal_errors( $previous );

        return array(
            'signature' => '' !== $signature_html ? $signature_html : $fallback['signature'],
            'pairings'  => $pairings_html,
        );
    }
}

/**
 * Return configured cinematic still slot data for a review.
 */
if ( ! function_exists( 'lunara_get_review_visual_slot_data' ) ) {
    function lunara_get_review_visual_slot_presets( $post_id ) {
        $post = get_post( $post_id );
        if ( ! ( $post instanceof WP_Post ) ) {
            return array();
        }

        $slug = sanitize_title( (string) $post->post_name );

        return array();
    }

    function lunara_get_review_visual_slot_data( $post_id, $slot ) {
        $configs = array(
            'hero_banner' => array(
                'url_key'     => '_lunara_review_hero_banner',
                'caption_key' => '_lunara_review_hero_banner_caption',
                'label'       => __( 'Hero Banner', 'lunara-film' ),
            ),
            'context_shot' => array(
                'url_key'     => '_lunara_review_context_shot',
                'caption_key' => '_lunara_review_context_shot_caption',
                'label'       => __( 'Context Shot', 'lunara-film' ),
            ),
            'visual_evidence' => array(
                'url_key'     => '_lunara_review_visual_evidence',
                'caption_key' => '_lunara_review_visual_evidence_caption',
                'label'       => __( 'Visual Evidence', 'lunara-film' ),
            ),
            'thematic_echo' => array(
                'url_key'     => '_lunara_review_thematic_echo',
                'caption_key' => '_lunara_review_thematic_echo_caption',
                'label'       => __( 'Thematic Echo', 'lunara-film' ),
            ),
        );

        if ( ! isset( $configs[ $slot ] ) ) {
            return array();
        }

        $config  = $configs[ $slot ];
        $url     = trim( (string) get_post_meta( $post_id, $config['url_key'], true ) );
        $caption = trim( (string) get_post_meta( $post_id, $config['caption_key'], true ) );

        if ( '' === $url ) {
            $presets = lunara_get_review_visual_slot_presets( $post_id );
            if ( isset( $presets[ $slot ] ) && ! empty( $presets[ $slot ]['url'] ) ) {
                $url     = trim( (string) $presets[ $slot ]['url'] );
                $caption = '' !== $caption ? $caption : trim( (string) ( $presets[ $slot ]['caption'] ?? '' ) );
            }
        }

        if ( '' === $url ) {
            return array();
        }

        $title = trim( wp_strip_all_tags( get_the_title( $post_id ) ) );
        $alt   = '' !== $caption ? wp_strip_all_tags( $caption ) : trim( $title . ' ' . $config['label'] );

        return array(
            'url'     => esc_url( $url ),
            'caption' => $caption,
            'alt'     => $alt,
            'label'   => $config['label'],
            'slot'    => $slot,
        );
    }
}

/**
 * Render a cinematic still slot for the review single.
 */
if ( ! function_exists( 'lunara_render_review_visual_slot' ) ) {
    function lunara_render_review_visual_slot( $post_id, $slot, $args = array() ) {
        $data = lunara_get_review_visual_slot_data( $post_id, $slot );
        if ( empty( $data ) ) {
            return '';
        }

        $args = wp_parse_args(
            $args,
            array(
                'loading' => 'lazy',
                'context' => 'body',
            )
        );

        $classes = array(
            'lunara-review-visual',
            'lunara-review-visual--' . sanitize_html_class( str_replace( '_', '-', $slot ) ),
            'lunara-review-visual--' . sanitize_html_class( $args['context'] ),
        );

        $caption_html = '';
        if ( '' !== $data['caption'] ) {
            $caption_html = sprintf(
                '<figcaption class="lunara-review-visual-caption"><span class="lunara-home-section-kicker">%1$s</span><p>%2$s</p></figcaption>',
                esc_html( $data['label'] ),
                esc_html( $data['caption'] )
            );
        }

        return sprintf(
            '<figure class="%1$s"><div class="lunara-review-visual-frame"><img class="lunara-review-visual-image" src="%2$s" alt="%3$s" loading="%4$s" decoding="async"></div>%5$s</figure>',
            esc_attr( implode( ' ', $classes ) ),
            esc_url( $data['url'] ),
            esc_attr( $data['alt'] ),
            esc_attr( $args['loading'] ),
            $caption_html
        );
    }
}

/**
 * Inject optional cinematic stills into the body of a review.
 */
if ( ! function_exists( 'lunara_insert_review_visuals_into_body_html' ) ) {
    function lunara_insert_review_visuals_into_body_html( $body_html, $post_id ) {
        $body_html = trim( (string) $body_html );
        if ( '' === $body_html || ! class_exists( 'DOMDocument' ) ) {
            return $body_html;
        }

        $slot_html = array(
            'context_shot'    => lunara_render_review_visual_slot( $post_id, 'context_shot' ),
            'visual_evidence' => lunara_render_review_visual_slot( $post_id, 'visual_evidence' ),
            'thematic_echo'   => lunara_render_review_visual_slot( $post_id, 'thematic_echo' ),
        );

        if ( ! array_filter( $slot_html ) ) {
            return $body_html;
        }

        $previous_state = libxml_use_internal_errors( true );
        $dom            = new DOMDocument( '1.0', 'UTF-8' );
        $loaded         = $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div id="lunara-review-body-root">' . $body_html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        if ( ! $loaded ) {
            libxml_clear_errors();
            libxml_use_internal_errors( $previous_state );
            return $body_html;
        }

        $root = $dom->getElementById( 'lunara-review-body-root' );
        if ( ! $root ) {
            libxml_clear_errors();
            libxml_use_internal_errors( $previous_state );
            return $body_html;
        }

        $get_children = static function( $container ) {
            $children = array();
            foreach ( $container->childNodes as $child ) {
                if ( XML_ELEMENT_NODE === $child->nodeType ) {
                    $children[] = $child;
                }
            }
            return $children;
        };

        $append_fragment_after = static function( $dom, $root, $target, $html ) {
            if ( '' === trim( (string) $html ) ) {
                return;
            }

            $fragment_doc = new DOMDocument( '1.0', 'UTF-8' );
            $loaded       = $fragment_doc->loadHTML(
                '<?xml encoding="utf-8" ?><div>' . $html . '</div>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );

            if ( ! $loaded ) {
                return;
            }

            $wrapper = $fragment_doc->getElementsByTagName( 'div' )->item( 0 );
            if ( ! $wrapper ) {
                return;
            }

            $reference = $target ? $target->nextSibling : $root->firstChild;
            foreach ( iterator_to_array( $wrapper->childNodes ) as $child ) {
                $imported = $dom->importNode( $child, true );
                if ( $reference ) {
                    $root->insertBefore( $imported, $reference );
                } else {
                    $root->appendChild( $imported );
                }
            }
        };

        $children = $get_children( $root );

        if ( '' !== $slot_html['context_shot'] && ! empty( $children ) ) {
            $target = null;
            foreach ( $children as $child ) {
                $tag = strtolower( $child->nodeName );
                if ( in_array( $tag, array( 'h2', 'h3' ), true ) ) {
                    $target = $child;
                    break;
                }
            }

            if ( ! $target ) {
                $paragraphs = array_values(
                    array_filter(
                        $children,
                        static function( $child ) {
                            return 'p' === strtolower( $child->nodeName );
                        }
                    )
                );
                $target = $paragraphs[ min( 1, max( 0, count( $paragraphs ) - 1 ) ) ] ?? $children[0];
            }

            $append_fragment_after( $dom, $root, $target, $slot_html['context_shot'] );
            $children = $get_children( $root );
        }

        if ( '' !== $slot_html['visual_evidence'] && ! empty( $children ) ) {
            $index  = max( 1, min( count( $children ) - 2, (int) floor( count( $children ) * 0.58 ) ) );
            $target = $children[ $index - 1 ] ?? end( $children );
            $append_fragment_after( $dom, $root, $target, $slot_html['visual_evidence'] );
            $children = $get_children( $root );
        }

        if ( '' !== $slot_html['thematic_echo'] && count( $children ) >= 2 ) {
            $index  = max( 1, min( count( $children ) - 2, (int) floor( count( $children ) * 0.82 ) ) );
            $target = $children[ $index - 1 ] ?? end( $children );
            $append_fragment_after( $dom, $root, $target, $slot_html['thematic_echo'] );
        }

        $output = '';
        foreach ( $root->childNodes as $child ) {
            $output .= $dom->saveHTML( $child );
        }

        libxml_clear_errors();
        libxml_use_internal_errors( $previous_state );

        return trim( $output );
    }
}

/**
 * Split a rendered review into the main essay, the Lunara Debrief, and any postscript/share blocks.
 */
if ( ! function_exists( 'lunara_extract_review_content_sections' ) ) {
    function lunara_extract_review_content_sections( $content_html ) {
        $content_html = trim( (string) $content_html );

        $sections = array(
            'body'     => $content_html,
            'debrief'  => '',
            'postscript' => '',
        );

        if ( '' === $content_html ) {
            return $sections;
        }

        $marker_pattern = '~<p>\s*(?:<strong>)?\s*LUNARA\s+DEBRIEF\s*(?:</strong>)?\s*</p>~i';

        if ( ! preg_match( $marker_pattern, $content_html, $marker_match, PREG_OFFSET_CAPTURE ) ) {
            return $sections;
        }

        $start_offset = intval( $marker_match[0][1] );
        $before       = trim( substr( $content_html, 0, $start_offset ) );
        $tail         = substr( $content_html, $start_offset );
        $debrief_end  = null;

        if ( preg_match( '~<p>\s*<strong>\s*Pair\s+It\s+With\s*</strong>\s*</p>.*?(</ul>)~is', $tail, $pair_match, PREG_OFFSET_CAPTURE ) ) {
            $debrief_end = intval( $pair_match[1][1] ) + strlen( $pair_match[1][0] );
        } elseif ( preg_match( '~</ul>~i', $tail, $list_end_match, PREG_OFFSET_CAPTURE ) ) {
            $debrief_end = intval( $list_end_match[0][1] ) + strlen( $list_end_match[0][0] );
        }

        if ( null === $debrief_end ) {
            return $sections;
        }

        $debrief_html   = trim( substr( $tail, 0, $debrief_end ) );
        $postscript_html = trim( substr( $tail, $debrief_end ) );

        $debrief_html = preg_replace( $marker_pattern, '', $debrief_html, 1 );
        $debrief_html = lunara_enhance_review_debrief_html( $debrief_html );

        $sections['body']       = '' !== $before ? $before : $content_html;
        $sections['debrief']    = trim( (string) $debrief_html );
        $sections['postscript'] = trim( (string) $postscript_html );

        return $sections;
    }
}

/**
 * Build a longer excerpt for hero/feature review cards.
 */
if ( ! function_exists( 'lunara_get_review_archive_excerpt' ) ) {
if ( ! function_exists( 'lunara_get_review_archive_excerpt' ) ) {
function lunara_get_review_archive_excerpt( $post_id, $words = 28 ) {
    $post_id = intval( $post_id );
    $words   = max( 12, intval( $words ) );

    if ( $post_id <= 0 ) {
        return '';
    }

    if ( function_exists( 'lunara_get_review_card_pull_quote' ) ) {
        return lunara_get_review_card_pull_quote( $post_id, $words );
    }

    return function_exists( 'lunara_card_excerpt' )
        ? lunara_card_excerpt( $post_id, $words )
        : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), $words, '...' );
}
}
}

/**
 * Render a lead or supporting review card for the archive shell.
 */
if ( ! function_exists( 'lunara_render_review_feature_card' ) ) {
    function lunara_render_review_feature_card( $post_id, $args = array() ) {
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            return '';
        }

        $args = wp_parse_args(
            $args,
            array(
                'variant' => 'lead',
                'excerpt_words' => 30,
            )
        );

        $variant      = 'compact' === $args['variant'] ? 'compact' : 'lead';
        $score        = trim( (string) get_post_meta( $post_id, '_lunara_score', true ) );
        $excerpt      = lunara_get_review_archive_excerpt( $post_id, intval( $args['excerpt_words'] ) );
        $review_tt    = function_exists( 'lunara_get_review_imdb_title_id' ) ? lunara_get_review_imdb_title_id( $post_id ) : '';
        $image_data   = lunara_get_review_card_image_data(
            $post_id,
            'large',
            array(
                'class'    => 'lunara-review-feature-image',
                'loading'  => 'lazy',
                'decoding' => 'async',
                'sizes'    => '(max-width: 900px) 100vw, 48vw',
            )
        );
        $ledger_pill  = '';

        if ( '' !== $review_tt && function_exists( 'lunara_get_oscar_ledger_counts' ) && function_exists( 'lunara_render_oscar_ledger_pill' ) ) {
            $ledger_pill = lunara_render_oscar_ledger_pill( $review_tt, lunara_get_oscar_ledger_counts( $review_tt ) );
        }

        ob_start();
        ?>
        <article class="lunara-review-feature-card is-<?php echo esc_attr( $variant ); ?>">
            <a class="lunara-review-feature-link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                <div class="lunara-review-feature-media">
                    <?php if ( ! empty( $image_data['html'] ) ) : ?>
                        <?php echo $image_data['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php else : ?>
                        <div class="lunara-review-feature-placeholder"><?php echo esc_html( get_the_title( $post_id ) ); ?></div>
                    <?php endif; ?>
                    <?php if ( '' !== $score ) : ?>
                        <span class="lunara-score-badge"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span>
                    <?php endif; ?>
                </div>
                <div class="lunara-review-feature-copy">
                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Lunara Review', 'lunara-film' ); ?></p>
                    <h2 class="lunara-review-feature-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h2>
                    <?php if ( '' !== $excerpt ) : ?>
                        <p class="lunara-review-feature-excerpt lunara-review-feature-quote"><?php echo esc_html( $excerpt ); ?></p>
                    <?php endif; ?>
                    <div class="lunara-review-feature-footer">
                        <?php if ( '' !== $ledger_pill ) : ?>
                            <div class="lunara-review-feature-ledger"><?php echo wp_kses_post( $ledger_pill ); ?></div>
                        <?php endif; ?>
                        <span class="lunara-section-link"><?php esc_html_e( 'Read Review', 'lunara-film' ); ?></span>
                    </div>
                </div>
            </a>
        </article>
        <?php

        return ob_get_clean();
    }
}

/**
 * Normalize the IMDb title id attached to a review.
 */
if ( ! function_exists( 'lunara_get_review_imdb_title_id' ) ) {
    function lunara_get_review_imdb_title_id( $post_id ) {
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            return '';
        }

        $raw = trim( (string) get_post_meta( $post_id, '_lunara_imdb_title_id', true ) );
        if ( '' === $raw ) {
            return '';
        }

        if ( preg_match( '/\btt\d{7,8}\b/i', $raw, $matches ) ) {
            return strtolower( $matches[0] );
        }

        if ( preg_match( '#imdb\.com/title/(tt\d{7,8})#i', $raw, $matches ) ) {
            return strtolower( $matches[1] );
        }

        return '';
    }
}

/**
 * Query related reviews using director/year affinity first, then recent fallback.
 */
if ( ! function_exists( 'lunara_get_related_review_posts' ) ) {
    function lunara_get_related_review_posts( $post_id, $count = 4 ) {
        $post_id = intval( $post_id );
        $count   = max( 1, intval( $count ) );

        if ( $post_id <= 0 ) {
            return lunara_reviews_query_from_ids( array() );
        }

        $director = trim( (string) get_post_meta( $post_id, '_lunara_director', true ) );
        $year     = trim( (string) get_post_meta( $post_id, '_lunara_year', true ) );
        $ids      = array();

        $collect_ids = static function( $query_args ) use ( &$ids, $count, $post_id ) {
            if ( count( $ids ) >= $count ) {
                return;
            }

            $query_args = wp_parse_args(
                $query_args,
                array(
                    'post_type'              => 'review',
                    'post_status'            => 'publish',
                    'posts_per_page'         => max( 1, $count - count( $ids ) ),
                    'post__not_in'           => array_merge( array( $post_id ), $ids ),
                    'ignore_sticky_posts'    => true,
                    'fields'                 => 'ids',
                    'orderby'                => 'date',
                    'order'                  => 'DESC',
                    'no_found_rows'          => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                )
            );

            $found_ids = get_posts( $query_args );
            foreach ( array_map( 'intval', is_array( $found_ids ) ? $found_ids : array() ) as $found_id ) {
                if ( $found_id > 0 && ! in_array( $found_id, $ids, true ) && $found_id !== $post_id ) {
                    $ids[] = $found_id;
                    if ( count( $ids ) >= $count ) {
                        break;
                    }
                }
            }
        };

        if ( '' !== $director ) {
            $collect_ids(
                array(
                    'meta_query' => array(
                        array(
                            'key'     => '_lunara_director',
                            'value'   => $director,
                            'compare' => '=',
                        ),
                    ),
                )
            );
        }

        if ( count( $ids ) < $count && '' !== $year ) {
            $collect_ids(
                array(
                    'meta_query' => array(
                        array(
                            'key'     => '_lunara_year',
                            'value'   => $year,
                            'compare' => '=',
                        ),
                    ),
                )
            );
        }

        if ( count( $ids ) < $count ) {
            $collect_ids( array() );
        }

        return lunara_reviews_query_from_ids( array_slice( $ids, 0, $count ) );
    }
}

/**
 * Render a review archive card.
 */
if ( ! function_exists( 'lunara_render_review_grid_card' ) ) {
function lunara_render_review_grid_card( $post_id, $card_index = null ) {
    $post_id = intval( $post_id );
    if ( $post_id <= 0 ) {
        return '';
    }

    $has_position = null !== $card_index;
    $card_index   = max( 1, intval( $card_index ) );
    $score        = get_post_meta( $post_id, '_lunara_score', true );
    $quote        = lunara_get_review_card_pull_quote( $post_id, 46 );
    $review_tt    = function_exists( 'lunara_get_review_imdb_title_id' ) ? lunara_get_review_imdb_title_id( $post_id ) : '';
    $thumb_attrs  = array(
        'class'    => 'lunara-review-grid-poster',
        'loading'  => $has_position && $card_index <= 8 ? 'eager' : 'lazy',
        'decoding' => 'async',
        'sizes'    => '(max-width: 720px) 92vw, (max-width: 1180px) 42vw, 260px',
    );
    $ledger_pill  = '';

    if ( $has_position && $card_index <= 4 ) {
        $thumb_attrs['fetchpriority'] = 'high';
    }

    $image_data = lunara_get_review_card_image_data( $post_id, 'medium_large', $thumb_attrs );
    $thumb_url  = isset( $image_data['url'] ) ? (string) $image_data['url'] : '';

    if ( '' !== $review_tt && function_exists( 'lunara_get_oscar_ledger_counts' ) && function_exists( 'lunara_render_oscar_ledger_pill' ) ) {
        $ledger_pill = lunara_render_oscar_ledger_pill( $review_tt, lunara_get_oscar_ledger_counts( $review_tt ) );
    }

    ob_start();
    ?>
    <article class="lunara-review-grid-card lunara-review-archive-card">
        <a class="lunara-review-grid-link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
            <div class="lunara-review-grid-poster-wrap<?php echo '' !== $thumb_url ? ' has-poster-bg' : ''; ?>"<?php if ( '' !== $thumb_url ) : ?> style="background-image: url('<?php echo esc_url( $thumb_url ); ?>');"<?php endif; ?>>
                <?php if ( ! empty( $image_data['html'] ) ) : ?>
                    <?php echo $image_data['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php else : ?>
                    <div class="lunara-review-grid-poster-placeholder"><?php echo esc_html( get_the_title( $post_id ) ); ?></div>
                <?php endif; ?>
                <?php if ( $score ) : ?>
                    <span class="lunara-score-badge"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span>
                <?php endif; ?>
            </div>
            <div class="lunara-review-grid-copy">
                <p class="lunara-review-grid-kicker"><?php esc_html_e( 'Lunara Review', 'lunara-film' ); ?></p>
                <h3 class="lunara-review-grid-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
                <?php if ( '' !== trim( $quote ) ) : ?>
                    <p class="lunara-review-grid-excerpt lunara-review-grid-quote"><?php echo esc_html( $quote ); ?></p>
                <?php endif; ?>
                <?php if ( '' !== $ledger_pill ) : ?>
                    <div class="lunara-review-grid-footer">
                        <div class="lunara-review-grid-ledger"><?php echo wp_kses_post( $ledger_pill ); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </a>
    </article>
    <?php

    return ob_get_clean();
}
}

/**
 * Build a short taxonomy line for standard post cards.
 */
if ( ! function_exists( 'lunara_get_dispatch_category_line' ) ) {
function lunara_get_dispatch_category_line( $post_id ) {
    $terms = get_the_terms( $post_id, 'category' );
    if ( ! is_array( $terms ) ) {
        return '';
    }

    $labels = array();

    foreach ( $terms as $term ) {
        if ( ! ( $term instanceof WP_Term ) || 'uncategorized' === $term->slug ) {
            continue;
        }

        $labels[] = trim( (string) $term->name );
    }

    $labels = array_values( array_filter( array_unique( $labels ) ) );

    return implode( ' / ', array_slice( $labels, 0, 2 ) );
}
}

/**
 * Estimate reading time for a standard editorial post.
 */
if ( ! function_exists( 'lunara_get_post_reading_time' ) ) {
function lunara_get_post_reading_time( $post_id ) {
    $post_id = intval( $post_id );
    if ( $post_id <= 0 ) {
        return '';
    }

    $content    = wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) );
    $word_count = str_word_count( $content );
    if ( $word_count <= 0 ) {
        return '';
    }

    $minutes = max( 1, (int) ceil( $word_count / 225 ) );

    /* translators: %d: Reading time in minutes. */
    return sprintf( _n( '%d min read', '%d mins read', $minutes, 'lunara-film' ), $minutes );
}
}

/**
 * Build a compact label list for a post's tags.
 */
if ( ! function_exists( 'lunara_get_post_tag_line' ) ) {
function lunara_get_post_tag_line( $post_id, $limit = 4 ) {
    $terms = get_the_terms( $post_id, 'post_tag' );
    if ( ! is_array( $terms ) ) {
        return '';
    }

    $labels = array();

    foreach ( $terms as $term ) {
        if ( ! ( $term instanceof WP_Term ) ) {
            continue;
        }

        $labels[] = trim( (string) $term->name );
    }

    $labels = array_values( array_filter( array_unique( $labels ) ) );

    return implode( ' / ', array_slice( $labels, 0, max( 1, intval( $limit ) ) ) );
}
}

/**
 * Query related editorial posts by shared categories.
 */
if ( ! function_exists( 'lunara_get_related_dispatch_posts' ) ) {
function lunara_get_related_dispatch_posts( $post_id, $count = 3 ) {
    $post_id = intval( $post_id );
    $count   = max( 1, intval( $count ) );
    $cat_ids = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );

    $query_args = array(
        'post_type'              => 'post',
        'post_status'            => 'publish',
        'posts_per_page'         => $count,
        'post__not_in'           => array( $post_id ),
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
        'orderby'                => 'date',
        'order'                  => 'DESC',
    );

    if ( ! empty( $cat_ids ) ) {
        $query_args['category__in'] = array_map( 'intval', $cat_ids );
    } else {
        $dispatch_slugs = lunara_get_dispatch_category_slugs();
        if ( ! empty( $dispatch_slugs ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'category',
                    'field'    => 'slug',
                    'terms'    => $dispatch_slugs,
                    'operator' => 'IN',
                ),
            );
        }
    }

    return new WP_Query( $query_args );
}
}

/**
 * Render a standard post card for the editorial archive.
 */
if ( ! function_exists( 'lunara_render_dispatch_archive_card' ) ) {
function lunara_render_dispatch_archive_card( $post_id, $featured = false ) {
    $post_id        = intval( $post_id );
    $featured       = (bool) $featured;
    $type_label     = lunara_get_dispatch_type_label( $post_id );
    $type_slug      = lunara_get_dispatch_type_slug( $post_id );
    $category_line  = lunara_get_dispatch_category_line( $post_id );
    $excerpt_length = $featured ? 40 : 22;
    $excerpt        = function_exists( 'lunara_card_excerpt' )
        ? lunara_card_excerpt( $post_id, $excerpt_length )
        : wp_trim_words( get_the_excerpt( $post_id ), $excerpt_length );

    ob_start();

    if ( $featured ) :
        ?>
        <article class="<?php echo esc_attr( 'lunara-dispatch-lead lunara-archive-lead-card lunara-dispatch-type-card is-' . sanitize_html_class( $type_slug ) ); ?>">
            <a class="lunara-dispatch-lead-link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                <div class="lunara-dispatch-lead-media">
                    <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                        <?php echo get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'lunara-dispatch-lead-image', 'loading' => 'lazy' ) ); ?>
                    <?php else : ?>
                        <div class="lunara-dispatch-lead-placeholder"><?php echo esc_html( $type_label ); ?></div>
                    <?php endif; ?>
                </div>
                <div class="lunara-dispatch-lead-copy">
                    <p class="lunara-dispatch-type"><?php echo esc_html( $type_label ); ?></p>
                    <h2 class="lunara-dispatch-lead-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h2>
                    <?php if ( '' !== $excerpt ) : ?>
                        <p class="lunara-dispatch-lead-excerpt"><?php echo esc_html( $excerpt ); ?></p>
                    <?php endif; ?>
                    <div class="lunara-dispatch-lead-meta">
                        <span><?php echo esc_html( get_the_date( 'F j, Y', $post_id ) ); ?></span>
                        <?php if ( '' !== $category_line ) : ?>
                            <span><?php echo esc_html( $category_line ); ?></span>
                        <?php endif; ?>
                        <span class="lunara-dispatch-meta-link"><?php esc_html_e( 'Read or listen', 'lunara-film' ); ?></span>
                    </div>
                </div>
            </a>
        </article>
        <?php
    else :
        ?>
        <article class="<?php echo esc_attr( 'lunara-dispatch-archive-card lunara-dispatch-type-card is-' . sanitize_html_class( $type_slug ) ); ?>">
            <a class="lunara-dispatch-archive-link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                <div class="lunara-dispatch-archive-thumb-wrap">
                    <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                        <?php echo get_the_post_thumbnail( $post_id, 'medium_large', array( 'class' => 'lunara-dispatch-archive-thumb', 'loading' => 'lazy' ) ); ?>
                    <?php else : ?>
                        <div class="lunara-dispatch-rail-thumb-placeholder"><?php echo esc_html( $type_label ); ?></div>
                    <?php endif; ?>
                </div>
                <div class="lunara-dispatch-archive-copy">
                    <p class="lunara-dispatch-type"><?php echo esc_html( $type_label ); ?></p>
                    <h3 class="lunara-dispatch-archive-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
                    <?php if ( '' !== $excerpt ) : ?>
                        <p class="lunara-dispatch-archive-excerpt"><?php echo esc_html( $excerpt ); ?></p>
                    <?php endif; ?>
                    <p class="lunara-dispatch-archive-meta">
                        <span><?php echo esc_html( get_the_date( 'F j, Y', $post_id ) ); ?></span>
                        <?php if ( '' !== $category_line ) : ?>
                            <span><?php echo esc_html( $category_line ); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </a>
        </article>
        <?php
    endif;

    return ob_get_clean();
}
}

/**
 * Normalize posts pulled from either the global loop or an explicit query.
 */
if ( ! function_exists( 'lunara_get_loop_posts' ) ) {
    function lunara_get_loop_posts( $query = null ) {
        if ( $query instanceof WP_Query ) {
            return is_array( $query->posts ) ? $query->posts : array();
        }

        global $wp_query;

        return ( isset( $wp_query ) && $wp_query instanceof WP_Query && is_array( $wp_query->posts ) )
            ? $wp_query->posts
            : array();
    }
}

/**
 * Shared editorial archive shell used by the posts index and editorial tax archives.
 */
if ( ! function_exists( 'lunara_render_editorial_archive_shell' ) ) {
    function lunara_render_editorial_archive_shell( $args = array() ) {
        $defaults = array(
            'classes'           => 'lunara-editorial-archive-page',
            'kicker'            => __( 'Archive', 'lunara-film' ),
            'title'             => __( 'Archive', 'lunara-film' ),
            'copy'              => '',
            'posts'             => array(),
            'empty_title'       => __( 'Nothing has been filed in this archive yet.', 'lunara-film' ),
            'empty_copy'        => '',
            'copy_words'        => 42,
            'pagination'        => paginate_links(),
            'overview_kicker'   => __( 'At A Glance', 'lunara-film' ),
            'overview_label'    => __( 'Coverage Focus', 'lunara-film' ),
            'source_label'      => __( 'Editorial lane', 'lunara-film' ),
            'overview_lines'    => array(),
            'lead_rail_kicker'  => __( 'In Rotation', 'lunara-film' ),
            'lead_rail_title'   => __( 'What The Archive Is Holding Beside The Lead', 'lunara-film' ),
            'lead_rail_copy'    => '',
            'run_kicker'        => __( 'Archive Run', 'lunara-film' ),
            'run_title'         => __( 'More From The Archive', 'lunara-film' ),
            'run_copy'          => '',
            'empty_note_kicker' => __( 'What Lives Here', 'lunara-film' ),
            'empty_note_title'  => __( 'Journal entries, reactions, essays, and signal worth following.', 'lunara-film' ),
            'empty_note_copy'   => '',
        );
        $args = wp_parse_args( $args, $defaults );

        $posts          = array_values( array_filter( (array) $args['posts'], static function ( $post_item ) {
            return $post_item instanceof WP_Post;
        } ) );
        $copy            = '';
        $classes         = trim( 'site-main lunara-archive-page ' . (string) $args['classes'] );
        $lead_post       = ! empty( $posts ) ? array_shift( $posts ) : null;
        $support_posts   = array_slice( $posts, 0, 2 );
        $remaining_posts = array_slice( $posts, 2 );
        $visible_count   = count( $posts ) + ( $lead_post instanceof WP_Post ? 1 : 0 );
        $archive_mode    = $lead_post instanceof WP_Post
            ? ( ! empty( $remaining_posts ) ? __( 'Spotlight / Supporting / Archive Run', 'lunara-film' ) : __( 'Spotlight / Supporting', 'lunara-film' ) )
            : __( 'Standby', 'lunara-film' );
        $total_count     = 0;

        global $wp_query;

        if ( isset( $wp_query ) && $wp_query instanceof WP_Query ) {
            $total_count = intval( $wp_query->found_posts );
        }

        if ( $total_count <= 0 ) {
            $total_count = $visible_count;
        }

        $overview_lines = array_values( array_filter( (array) $args['overview_lines'], static function ( $line ) {
            return is_array( $line ) && ! empty( $line['label'] ) && isset( $line['value'] );
        } ) );

        if ( empty( $overview_lines ) ) {
            $overview_lines = array(
                array(
                    'label' => __( 'Total Filed', 'lunara-film' ),
                    'value' => number_format_i18n( $total_count ),
                ),
                array(
                    'label' => __( 'Visible Now', 'lunara-film' ),
                    'value' => number_format_i18n( $visible_count ),
                ),
                array(
                    'label' => __( 'Page Shape', 'lunara-film' ),
                    'value' => $archive_mode,
                ),
                array(
                    'label' => (string) $args['overview_label'],
                    'value' => (string) $args['source_label'],
                ),
            );
        }

        ob_start();
        ?>
        <main id="primary" class="<?php echo esc_attr( $classes ); ?>">
            <section class="lunara-home-section lunara-archive-hero">
                <div class="lunara-editorial-archive-hero-shell">
                    <div class="lunara-editorial-archive-hero-copy-wrap">
                        <p class="lunara-archive-hero-kicker"><?php echo esc_html( $args['kicker'] ); ?></p>
                        <h1 class="lunara-archive-hero-title"><?php echo esc_html( $args['title'] ); ?></h1>
                        <?php if ( '' !== $copy ) : ?>
                            <p class="lunara-archive-hero-copy"><?php echo esc_html( wp_trim_words( $copy, max( 12, intval( $args['copy_words'] ) ) ) ); ?></p>
                        <?php endif; ?>
                    </div>
                    <aside class="lunara-editorial-archive-debrief" aria-label="<?php esc_attr_e( 'Editorial archive summary', 'lunara-film' ); ?>">
                        <p class="lunara-editorial-archive-debrief-kicker"><?php echo esc_html( $args['overview_kicker'] ); ?></p>
                        <ul class="lunara-editorial-archive-debrief-list">
                            <?php foreach ( $overview_lines as $line ) : ?>
                                <li>
                                    <strong><?php echo esc_html( (string) $line['label'] ); ?></strong>
                                    <span><?php echo esc_html( (string) $line['value'] ); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </aside>
                </div>
            </section>

            <section class="lunara-home-section lunara-editorial-archive-shell">
                <?php if ( $lead_post instanceof WP_Post ) : ?>
                    <div class="lunara-editorial-archive-spotlight">
                        <?php echo lunara_render_dispatch_archive_card( $lead_post->ID, true ); ?>

                        <?php if ( ! empty( $support_posts ) ) : ?>
                            <div class="lunara-editorial-archive-rail">
                                <div class="lunara-editorial-archive-rail-shell">
                                    <p class="lunara-home-section-kicker"><?php echo esc_html( $args['lead_rail_kicker'] ); ?></p>
                                    <h2 class="lunara-section-title"><?php echo esc_html( $args['lead_rail_title'] ); ?></h2>
                                    <?php if ( '' !== trim( (string) $args['lead_rail_copy'] ) ) : ?>
                                        <p class="lunara-editorial-archive-rail-copy"><?php echo esc_html( $args['lead_rail_copy'] ); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php foreach ( $support_posts as $post_item ) : ?>
                                    <?php echo lunara_render_dispatch_archive_card( $post_item->ID ); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ( ! empty( $remaining_posts ) ) : ?>
                        <div class="lunara-home-section-head lunara-editorial-archive-run-head">
                            <div>
                                <p class="lunara-home-section-kicker"><?php echo esc_html( $args['run_kicker'] ); ?></p>
                                <h2 class="lunara-section-title"><?php echo esc_html( $args['run_title'] ); ?></h2>
                                <?php if ( '' !== trim( (string) $args['run_copy'] ) ) : ?>
                                    <p class="lunara-editorial-archive-run-copy"><?php echo esc_html( $args['run_copy'] ); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="lunara-dispatch-archive-grid lunara-editorial-archive-grid">
                            <?php foreach ( $remaining_posts as $post_item ) : ?>
                                <?php echo lunara_render_dispatch_archive_card( $post_item->ID ); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $args['pagination'] ) ) : ?>
                        <div class="lunara-archive-pagination">
                            <?php echo wp_kses_post( $args['pagination'] ); ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="lunara-editorial-archive-empty-shell">
                        <div class="lunara-archive-empty lunara-editorial-archive-empty">
                            <h2><?php echo esc_html( $args['empty_title'] ); ?></h2>
                            <?php if ( '' !== trim( (string) $args['empty_copy'] ) ) : ?>
                                <p><?php echo esc_html( $args['empty_copy'] ); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="lunara-editorial-archive-empty-note">
                            <p class="lunara-home-section-kicker"><?php echo esc_html( $args['empty_note_kicker'] ); ?></p>
                            <h2 class="lunara-section-title"><?php echo esc_html( $args['empty_note_title'] ); ?></h2>
                            <?php if ( '' !== trim( (string) $args['empty_note_copy'] ) ) : ?>
                                <p class="lunara-editorial-archive-empty-copy"><?php echo esc_html( $args['empty_note_copy'] ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </main>
        <?php

        return ob_get_clean();
    }
}

/**
 * Shared review archive shell used by review and director archives.
 */
if ( ! function_exists( 'lunara_render_review_archive_shell' ) ) {
    function lunara_render_review_archive_shell( $args = array() ) {
        $defaults = array(
            'classes'      => 'lunara-review-archive-page',
            'kicker'       => __( 'Review Archive', 'lunara-film' ),
            'title'        => __( 'The Review Archive', 'lunara-film' ),
            'copy'         => '',
            'posts'        => array(),
            'empty_title'  => __( 'No reviews yet.', 'lunara-film' ),
            'empty_copy'   => '',
            'copy_words'   => 42,
            'pagination'   => paginate_links(),
        );
        $args = wp_parse_args( $args, $defaults );

        $posts = array_values( array_filter( (array) $args['posts'], static function ( $post_item ) {
            return $post_item instanceof WP_Post;
        } ) );
        $copy          = '';
        $classes       = trim( 'site-main lunara-archive-page ' . (string) $args['classes'] );
        $total_reviews = wp_count_posts( 'review' );
        $total_reviews = isset( $total_reviews->publish ) ? intval( $total_reviews->publish ) : 0;
        $visible_count = count( $posts );

        ob_start();
        ?>
        <main id="primary" class="<?php echo esc_attr( $classes ); ?>">
            <section class="lunara-home-section lunara-archive-hero lunara-review-archive-hero">
                <div class="lunara-review-archive-hero-shell">
                    <div class="lunara-review-archive-hero-copy-wrap">
                        <p class="lunara-archive-hero-kicker"><?php echo esc_html( $args['kicker'] ); ?></p>
                        <h1 class="lunara-archive-hero-title"><?php echo esc_html( $args['title'] ); ?></h1>
                        <?php if ( '' !== $copy ) : ?>
                            <p class="lunara-archive-hero-copy"><?php echo esc_html( wp_trim_words( $copy, max( 12, intval( $args['copy_words'] ) ) ) ); ?></p>
                        <?php endif; ?>
                    </div>
                    <aside class="lunara-review-archive-debrief" aria-label="<?php esc_attr_e( 'Review archive summary', 'lunara-film' ); ?>">
                        <p class="lunara-review-archive-debrief-kicker"><?php esc_html_e( 'At A Glance', 'lunara-film' ); ?></p>
                        <ul class="lunara-review-archive-debrief-list">
                            <li>
                                <strong><?php esc_html_e( 'Total Reviews', 'lunara-film' ); ?></strong>
                                <span><?php echo esc_html( number_format_i18n( $total_reviews ) ); ?></span>
                            </li>
                            <li>
                                <strong><?php esc_html_e( 'Visible Now', 'lunara-film' ); ?></strong>
                                <span><?php echo esc_html( number_format_i18n( $visible_count ) ); ?></span>
                            </li>
                        </ul>
                    </aside>
                </div>
            </section>

            <section class="lunara-home-section lunara-review-archive-shell">
                <?php if ( ! empty( $posts ) ) : ?>
                    <div class="lunara-review-grid lunara-review-archive-grid lunara-review-archive-uniform">
                        <?php foreach ( $posts as $review_index => $review_post ) : ?>
                            <?php echo lunara_render_review_grid_card( $review_post->ID, $review_index + 1 ); ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if ( ! empty( $args['pagination'] ) ) : ?>
                        <div class="lunara-archive-pagination">
                            <?php echo wp_kses_post( $args['pagination'] ); ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="lunara-archive-empty">
                        <h2><?php echo esc_html( $args['empty_title'] ); ?></h2>
                        <?php if ( '' !== trim( (string) $args['empty_copy'] ) ) : ?>
                            <p><?php echo esc_html( $args['empty_copy'] ); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
        <?php

        return ob_get_clean();
    }
}

/**
 * Dedicated news archive shell so the /news/ route feels authored, not generic.
 */
if ( ! function_exists( 'lunara_render_news_archive_shell' ) ) {
    function lunara_render_news_archive_shell( $args = array() ) {
        $defaults = array(
            'classes'      => 'lunara-editorial-archive-page lunara-news-archive-page',
            'kicker'       => __( 'Lunara Journal', 'lunara-film' ),
            'title'        => __( 'News', 'lunara-film' ),
            'copy'         => '',
            'posts'        => array(),
            'empty_title'  => __( 'No news posts have been filed yet.', 'lunara-film' ),
            'empty_copy'   => '',
            'copy_words'   => 42,
            'pagination'   => paginate_links(),
            'source_label' => __( 'Editorial lane', 'lunara-film' ),
            'lead_rail_kicker'  => __( 'In Rotation', 'lunara-film' ),
            'lead_rail_title'   => __( 'What The Signal Is Holding Beside The Lead', 'lunara-film' ),
            'lead_rail_copy'    => '',
            'run_kicker'        => __( 'Archive Run', 'lunara-film' ),
            'run_title'         => __( 'More From the Journal', 'lunara-film' ),
            'run_copy'          => '',
            'empty_note_kicker' => __( 'What Lives Here', 'lunara-film' ),
            'empty_note_title'  => __( 'Breaking items, industry shifts, and the stories worth moving on quickly.', 'lunara-film' ),
            'empty_note_copy'   => '',
            'standby_kicker'    => __( 'Stay On Signal', 'lunara-film' ),
            'standby_title'     => __( 'The publication is still alive around the dispatch desk.', 'lunara-film' ),
            'standby_copy'      => '',
            'live_section_order'   => 'spotlight,run,pagination',
            'empty_section_order'  => 'intro,standby',
            'standby_card_order'   => 'reviews,ledger,home',
            'standby_cards'        => array(),
        );
        $args = wp_parse_args( $args, $defaults );

        $posts = array_values( array_filter( (array) $args['posts'], static function ( $post_item ) {
            return $post_item instanceof WP_Post;
        } ) );

        $copy            = '';
        $classes         = trim( 'site-main lunara-archive-page ' . (string) $args['classes'] );
        $lead_post       = ! empty( $posts ) ? array_shift( $posts ) : null;
        $support_posts   = array_slice( $posts, 0, 2 );
        $remaining_posts = array_slice( $posts, 2 );
        $visible_count   = count( $posts ) + ( $lead_post instanceof WP_Post ? 1 : 0 );
        $archive_mode    = $lead_post instanceof WP_Post
            ? ( ! empty( $remaining_posts ) ? __( 'Spotlight / Supporting / News Run', 'lunara-film' ) : __( 'Spotlight / Supporting', 'lunara-film' ) )
            : __( 'Standby', 'lunara-film' );

        $news_total = 0;
        $news_term  = get_category_by_slug( 'news' );
        if ( $news_term instanceof WP_Term ) {
            $news_total = intval( $news_term->count );
        }

        $standby_cards_defaults = array(
            'reviews' => array(
                'kicker' => __( 'Criticism', 'lunara-film' ),
                'title'  => __( 'Browse The Review Archive', 'lunara-film' ),
                'copy'   => '',
                'button' => __( 'Enter The Reviews', 'lunara-film' ),
                'url'    => get_post_type_archive_link( 'review' ) ?: home_url( '/reviews/' ),
            ),
            'ledger' => array(
                'kicker' => __( 'Ledger', 'lunara-film' ),
                'title'  => __( 'Step Into The Oscar Ledger', 'lunara-film' ),
                'copy'   => '',
                'button' => __( 'Open The Ledger', 'lunara-film' ),
                'url'    => home_url( '/oscars/' ),
            ),
            'home' => array(
                'kicker' => __( 'Front Door', 'lunara-film' ),
                'title'  => __( 'Return To The Live Homepage', 'lunara-film' ),
                'copy'   => '',
                'button' => __( 'Go To Lunara', 'lunara-film' ),
                'url'    => home_url( '/' ),
            ),
        );

        $standby_cards = array();
        foreach ( $standby_cards_defaults as $card_slug => $card_defaults ) {
            $card_args                   = isset( $args['standby_cards'][ $card_slug ] ) && is_array( $args['standby_cards'][ $card_slug ] )
                ? $args['standby_cards'][ $card_slug ]
                : array();
            $standby_cards[ $card_slug ] = wp_parse_args( $card_args, $card_defaults );
        }

        $live_section_order  = array_filter( explode( ',', lunara_sanitize_journal_live_section_order( (string) $args['live_section_order'] ) ) );
        $empty_section_order = array_filter( explode( ',', lunara_sanitize_journal_empty_section_order( (string) $args['empty_section_order'] ) ) );
        $standby_card_order  = array_filter( explode( ',', lunara_sanitize_journal_standby_card_order( (string) $args['standby_card_order'] ) ) );
        $live_order_map      = array_flip( array_values( $live_section_order ) );
        $empty_order_map     = array_flip( array_values( $empty_section_order ) );
        $standby_card_map    = array_flip( array_values( $standby_card_order ) );

        ob_start();
        ?>
        <main id="primary" class="<?php echo esc_attr( $classes ); ?>">
            <section class="lunara-home-section lunara-archive-hero lunara-news-archive-hero">
                <div class="lunara-news-archive-hero-shell">
                    <div class="lunara-news-archive-hero-copy-wrap">
                        <p class="lunara-archive-hero-kicker"><?php echo esc_html( $args['kicker'] ); ?></p>
                        <h1 class="lunara-archive-hero-title"><?php echo esc_html( $args['title'] ); ?></h1>
                        <?php if ( '' !== $copy ) : ?>
                            <p class="lunara-archive-hero-copy"><?php echo esc_html( wp_trim_words( $copy, max( 12, intval( $args['copy_words'] ) ) ) ); ?></p>
                        <?php endif; ?>
                    </div>
                    <aside class="lunara-news-archive-debrief" aria-label="<?php esc_attr_e( 'News archive summary', 'lunara-film' ); ?>">
                        <p class="lunara-news-archive-debrief-kicker"><?php esc_html_e( 'At A Glance', 'lunara-film' ); ?></p>
                        <ul class="lunara-news-archive-debrief-list">
                            <li>
                                <strong><?php esc_html_e( 'Total Filed', 'lunara-film' ); ?></strong>
                                <span><?php echo esc_html( number_format_i18n( $news_total ) ); ?></span>
                            </li>
                            <li>
                                <strong><?php esc_html_e( 'Visible Now', 'lunara-film' ); ?></strong>
                                <span><?php echo esc_html( number_format_i18n( $visible_count ) ); ?></span>
                            </li>
                            <li>
                                <strong><?php esc_html_e( 'Desk State', 'lunara-film' ); ?></strong>
                                <span><?php echo esc_html( $archive_mode ); ?></span>
                            </li>
                            <li>
                                <strong><?php esc_html_e( 'Coverage Focus', 'lunara-film' ); ?></strong>
                                <span><?php echo esc_html( $args['source_label'] ); ?></span>
                            </li>
                        </ul>
                    </aside>
                </div>
            </section>

            <section class="lunara-home-section lunara-editorial-archive-shell lunara-news-archive-shell">
                <?php if ( $lead_post instanceof WP_Post ) : ?>
                    <div class="lunara-news-archive-slot lunara-news-archive-slot-spotlight" style="order:<?php echo esc_attr( isset( $live_order_map['spotlight'] ) ? $live_order_map['spotlight'] + 1 : 1 ); ?>;">
                    <div class="lunara-news-archive-spotlight">
                        <?php echo lunara_render_dispatch_archive_card( $lead_post->ID, true ); ?>

                        <?php if ( ! empty( $support_posts ) ) : ?>
                            <div class="lunara-news-archive-rail">
                                  <div class="lunara-news-archive-rail-shell">
                                      <p class="lunara-home-section-kicker"><?php echo esc_html( $args['lead_rail_kicker'] ); ?></p>
                                      <h2 class="lunara-section-title"><?php echo esc_html( $args['lead_rail_title'] ); ?></h2>
                                      <?php if ( '' !== trim( (string) $args['lead_rail_copy'] ) ) : ?>
                                          <p class="lunara-news-archive-rail-copy"><?php echo esc_html( $args['lead_rail_copy'] ); ?></p>
                                      <?php endif; ?>
                                  </div>
                                <?php foreach ( $support_posts as $post_item ) : ?>
                                    <?php echo lunara_render_dispatch_archive_card( $post_item->ID ); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    </div>

                    <?php if ( ! empty( $remaining_posts ) ) : ?>
                        <div class="lunara-news-archive-slot lunara-news-archive-slot-run" style="order:<?php echo esc_attr( isset( $live_order_map['run'] ) ? $live_order_map['run'] + 1 : 2 ); ?>;">
                          <div class="lunara-home-section-head lunara-news-archive-run-head">
                              <div>
                                  <p class="lunara-home-section-kicker"><?php echo esc_html( $args['run_kicker'] ); ?></p>
                                  <h2 class="lunara-section-title"><?php echo esc_html( $args['run_title'] ); ?></h2>
                                  <?php if ( '' !== trim( (string) $args['run_copy'] ) ) : ?>
                                      <p class="lunara-news-archive-run-copy"><?php echo esc_html( $args['run_copy'] ); ?></p>
                                  <?php endif; ?>
                              </div>
                          </div>

                        <div class="lunara-dispatch-archive-grid lunara-news-archive-grid">
                            <?php foreach ( $remaining_posts as $post_item ) : ?>
                                <?php echo lunara_render_dispatch_archive_card( $post_item->ID ); ?>
                            <?php endforeach; ?>
                        </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $args['pagination'] ) ) : ?>
                        <div class="lunara-archive-pagination lunara-news-archive-slot lunara-news-archive-slot-pagination" style="order:<?php echo esc_attr( isset( $live_order_map['pagination'] ) ? $live_order_map['pagination'] + 1 : 3 ); ?>;">
                            <?php echo wp_kses_post( $args['pagination'] ); ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="lunara-news-archive-empty-layout">
                    <div class="lunara-news-archive-empty-shell lunara-news-archive-slot lunara-news-archive-slot-intro" style="order:<?php echo esc_attr( isset( $empty_order_map['intro'] ) ? $empty_order_map['intro'] + 1 : 1 ); ?>;">
                        <div class="lunara-archive-empty lunara-news-archive-empty">
                            <p class="lunara-home-section-kicker"><?php esc_html_e( 'Desk Standby', 'lunara-film' ); ?></p>
                            <h2><?php echo esc_html( $args['empty_title'] ); ?></h2>
                            <?php if ( '' !== trim( (string) $args['empty_copy'] ) ) : ?>
                                <p><?php echo esc_html( $args['empty_copy'] ); ?></p>
                            <?php endif; ?>
                        </div>
                          <div class="lunara-news-archive-empty-note">
                              <p class="lunara-home-section-kicker"><?php echo esc_html( $args['empty_note_kicker'] ); ?></p>
                              <h2 class="lunara-section-title"><?php echo esc_html( $args['empty_note_title'] ); ?></h2>
                              <?php if ( '' !== trim( (string) $args['empty_note_copy'] ) ) : ?>
                                  <p class="lunara-news-archive-empty-copy"><?php echo esc_html( $args['empty_note_copy'] ); ?></p>
                              <?php endif; ?>
                          </div>
                      </div>
                      <div class="lunara-news-archive-standby-shell lunara-news-archive-slot lunara-news-archive-slot-standby" style="order:<?php echo esc_attr( isset( $empty_order_map['standby'] ) ? $empty_order_map['standby'] + 1 : 2 ); ?>;">
                          <div class="lunara-home-section-head lunara-news-archive-standby-head">
                              <div>
                                  <p class="lunara-home-section-kicker"><?php echo esc_html( $args['standby_kicker'] ); ?></p>
                                  <h2 class="lunara-section-title"><?php echo esc_html( $args['standby_title'] ); ?></h2>
                                  <?php if ( '' !== trim( (string) $args['standby_copy'] ) ) : ?>
                                      <p class="lunara-news-archive-empty-copy"><?php echo esc_html( $args['standby_copy'] ); ?></p>
                                  <?php endif; ?>
                              </div>
                          </div>
                        <div class="lunara-news-archive-standby-grid">
                            <?php foreach ( $standby_card_order as $card_slug ) : ?>
                                <?php if ( empty( $standby_cards[ $card_slug ] ) || ! is_array( $standby_cards[ $card_slug ] ) ) { continue; } ?>
                                <?php $card = $standby_cards[ $card_slug ]; ?>
                                <a class="lunara-news-archive-standby-card" href="<?php echo esc_url( $card['url'] ); ?>" style="order:<?php echo esc_attr( isset( $standby_card_map[ $card_slug ] ) ? $standby_card_map[ $card_slug ] + 1 : 99 ); ?>;">
                                    <p class="lunara-home-section-kicker"><?php echo esc_html( $card['kicker'] ); ?></p>
                                    <h3><?php echo esc_html( $card['title'] ); ?></h3>
                                    <?php if ( '' !== trim( (string) $card['copy'] ) ) : ?>
                                        <p><?php echo esc_html( $card['copy'] ); ?></p>
                                    <?php endif; ?>
                                    <span class="lunara-section-link"><?php echo esc_html( $card['button'] ); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    </div>
                <?php endif; ?>
            </section>
        </main>
        <?php

        return ob_get_clean();
    }
}

/**
 * Homepage featured reviews query with manual curation override.
 */
if ( ! function_exists( 'lunara_home_featured_reviews_query' ) ) {
function lunara_home_featured_reviews_query( $count = 8 ) {
    $curated_ids = lunara_curated_review_ids_by_meta(
        'home_featured_curated_reviews',
        '_lunara_review_home_featured_shelf',
        '_lunara_review_home_featured_priority',
        $count
    );

    if ( ! empty( $curated_ids ) ) {
        return lunara_reviews_query_from_ids( $curated_ids );
    }

    $manual_ids = lunara_parse_manual_post_ids(
        lunara_theme_mod_text( 'lunara_home_featured_review_ids', '' ),
        'review'
    );

    if ( ! empty( $manual_ids ) ) {
        return lunara_reviews_query_from_ids( array_slice( $manual_ids, 0, max( 1, intval( $count ) ) ) );
    }

    return lunara_featured_reviews_query( $count );
}
}

/**
 * Homepage top hero reviews query with dedicated manual curation override.
 */
if ( ! function_exists( 'lunara_home_hero_reviews_query' ) ) {
function lunara_home_hero_reviews_query( $count = 4 ) {
    $curated_ids = lunara_curated_review_ids_by_meta(
        'home_hero_curated_reviews',
        '_lunara_review_home_hero_featured',
        '_lunara_review_home_hero_priority',
        $count
    );

    if ( ! empty( $curated_ids ) ) {
        return lunara_reviews_query_from_ids( $curated_ids );
    }

    $manual_ids = lunara_parse_manual_post_ids(
        lunara_theme_mod_text( 'lunara_home_hero_review_ids', '' ),
        'review'
    );

    if ( empty( $manual_ids ) ) {
        $manual_ids = lunara_parse_manual_post_ids(
            lunara_theme_mod_text( 'lunara_home_featured_review_ids', '' ),
            'review'
        );
    }

    if ( ! empty( $manual_ids ) ) {
        return lunara_reviews_query_from_ids( array_slice( $manual_ids, 0, max( 1, intval( $count ) ) ) );
    }

    return lunara_home_featured_reviews_query( $count );
}
}

/**
 * Homepage dispatches query.
 *
 * Priority order:
 *   1. Manual curation (customizer post IDs â€” accepts both `post` and `journal`)
 *   2. `journal` CPT â€” the new dedicated post type (this is now the primary source)
 *   3. Legacy fallback â€” standard `post` filtered by category slugs
 *      (news / reactions / think-pieces / podcast). Keeps existing
 *      category-tagged posts visible during migration.
 */
if ( ! function_exists( 'lunara_home_dispatches_query' ) ) {
function lunara_home_dispatches_query( $count = 4 ) {
    $count = max( 1, intval( $count ) );

    // 1. Manual curation â€” accept both post types so curated lists can mix.
    $manual_ids = lunara_parse_manual_post_ids(
        lunara_theme_mod_text( 'lunara_home_dispatch_post_ids', '' ),
        ''
    );
    if ( ! empty( $manual_ids ) ) {
        return lunara_posts_query_from_ids( array_slice( $manual_ids, 0, $count ) );
    }

    // 2. Journal CPT â€” primary source going forward.
    // Exclude posts marked as legacy roundup archives (the originals after migration).
    $journal_query = new WP_Query( array(
        'post_type'              => 'journal',
        'posts_per_page'         => $count,
        'post_status'            => 'publish',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
        'orderby'                => 'date',
        'order'                  => 'DESC',
        'meta_query'             => array(
            'relation' => 'OR',
            array(
                'key'     => '_lunara_journal_is_archive',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => '_lunara_journal_is_archive',
                'value'   => '1',
                'compare' => '!=',
            ),
        ),
    ) );

    if ( $journal_query->have_posts() ) {
        return $journal_query;
    }

    // 3. Legacy fallback â€” standard posts filtered by category slugs.
    $slugs = lunara_get_dispatch_category_slugs();

    $query_args = array(
        'post_type'              => 'post',
        'posts_per_page'         => $count,
        'post_status'            => 'publish',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
    );

    if ( ! empty( $slugs ) ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => $slugs,
                'operator' => 'IN',
            ),
        );
    }

    $legacy_query = new WP_Query( $query_args );
    if ( $legacy_query->have_posts() ) {
        return $legacy_query;
    }

    return new WP_Query( array(
        'post_type'      => 'journal',
        'post__in'       => array( 0 ),
        'posts_per_page' => 0,
        'no_found_rows'  => true,
    ) );
}
}

/**
 * Clear short-lived front-page query caches whenever review content changes.
 */
if ( ! function_exists( 'lunara_get_oscars_plugin' ) ) {
function lunara_get_oscars_plugin() {
    if ( ! class_exists( 'Academy_Awards_Table' ) || ! method_exists( 'Academy_Awards_Table', 'get_instance' ) ) {
        return null;
    }

    return Academy_Awards_Table::get_instance();
}
}

/**
 * Resolve the most readable homepage label for an Oscar winner entry.
 */
if ( ! function_exists( 'lunara_home_winner_primary_label' ) ) {
function lunara_home_winner_primary_label( $entry ) {
    $category = trim( (string) ( $entry['canonical_category'] ?? '' ) );
    $film     = trim( (string) ( $entry['film'] ?? '' ) );
    $name     = trim( (string) ( $entry['name'] ?? '' ) );
    $detail   = trim( (string) ( $entry['detail'] ?? '' ) );
    $nominees = trim( (string) ( $entry['nominees'] ?? '' ) );

    $title_forward_categories = array(
        'BEST PICTURE',
        'ANIMATED FEATURE FILM',
        'DOCUMENTARY (Feature)',
        'DOCUMENTARY (Short Subject)',
        'INTERNATIONAL FEATURE FILM',
        'SHORT FILM (Animated)',
        'SHORT FILM (Live Action)',
    );

    if ( in_array( $category, $title_forward_categories, true ) && $film !== '' ) {
        return $film;
    }

    if ( $name !== '' ) {
        return $name;
    }

    if ( $film !== '' ) {
        return $film;
    }

    if ( $detail !== '' ) {
        return $detail;
    }

    return $nominees;
}
}

/**
 * Build a compact secondary line for homepage winner cards.
 */
if ( ! function_exists( 'lunara_home_winner_secondary_label' ) ) {
function lunara_home_winner_secondary_label( $entry ) {
    $primary = lunara_home_winner_primary_label( $entry );
    $bits    = array();

    foreach ( array( 'film', 'name', 'detail', 'note' ) as $key ) {
        $value = trim( (string) ( $entry[ $key ] ?? '' ) );
        if ( $value === '' ) {
            continue;
        }

        if ( strcasecmp( $value, $primary ) === 0 ) {
            continue;
        }

        if ( ! in_array( $value, $bits, true ) ) {
            $bits[] = $value;
        }
    }

    return implode( ' Â· ', array_slice( $bits, 0, 2 ) );
}
}

/**
 * Build a dynamic Oscars snapshot for the homepage from the active database plugin.
 */
if ( ! function_exists( 'lunara_get_home_oscars_snapshot' ) ) {
function lunara_get_home_oscars_snapshot() {
    $cache_key = 'lunara_home_oscars_snapshot_v2'; // v2: includes visual package on spotlight entries
    $cached    = get_transient( $cache_key );

    if ( is_array( $cached ) && ! empty( $cached ) ) {
        return $cached;
    }

    $aat = lunara_get_oscars_plugin();
    if ( ! $aat || ! method_exists( $aat, 'get_max_ceremony' ) || ! method_exists( $aat, 'get_ceremony_rollup' ) ) {
        return array();
    }

    $ceremony = intval( $aat->get_max_ceremony() );
    if ( $ceremony <= 0 ) {
        return array();
    }

    $rollup = $aat->get_ceremony_rollup( $ceremony );
    if ( empty( $rollup ) || ! is_array( $rollup ) ) {
        return array();
    }

    $winner_rows = ! empty( $rollup['winner_rows'] ) && is_array( $rollup['winner_rows'] ) ? $rollup['winner_rows'] : array();
    $winner_map  = array();
    foreach ( $winner_rows as $winner_entry ) {
        $canonical = trim( (string) ( $winner_entry['canonical_category'] ?? '' ) );
        if ( $canonical !== '' && ! isset( $winner_map[ $canonical ] ) ) {
            $winner_map[ $canonical ] = $winner_entry;
        }
    }

    $spotlight_categories = array(
        'BEST PICTURE',
        'DIRECTING',
        'ACTOR IN A LEADING ROLE',
        'ACTRESS IN A LEADING ROLE',
        'ACTOR IN A SUPPORTING ROLE',
        'ACTRESS IN A SUPPORTING ROLE',
        'CINEMATOGRAPHY',
        'WRITING (Original Screenplay)',
    );

    $spotlights = array();
    foreach ( $spotlight_categories as $canonical ) {
        if ( empty( $winner_map[ $canonical ] ) ) {
            continue;
        }

        $entry = $winner_map[ $canonical ];
        $entry['category_label'] = method_exists( $aat, 'format_category_display' ) ? $aat->format_category_display( $canonical ) : $canonical;
        $entry['category_url']   = method_exists( $aat, 'get_category_url' ) ? $aat->get_category_url( $canonical ) : home_url( '/oscars/category/' . sanitize_title( $canonical ) . '/' );
        $entry['primary_label']  = lunara_home_winner_primary_label( $entry );
        $entry['secondary_label'] = lunara_home_winner_secondary_label( $entry );
        $entry['url']            = ! empty( $entry['film_url'] ) ? $entry['film_url'] : $entry['category_url'];
        $spotlight_film_id       = trim( (string) ( $entry['film_id'] ?? '' ) );
        $entry['visual']         = ( $spotlight_film_id !== '' && method_exists( $aat, 'get_title_visual_package' ) )
            ? $aat->get_title_visual_package( $spotlight_film_id, 'medium_large' )
            : array();
        $spotlights[]            = $entry;
    }

    $top_titles = array();
    foreach ( array_slice( (array) ( $rollup['top_titles'] ?? array() ), 0, 4 ) as $title_entry ) {
        $film_id = trim( (string) ( $title_entry['film_id'] ?? '' ) );
        $visual  = ( $film_id !== '' && method_exists( $aat, 'get_title_visual_package' ) ) ? $aat->get_title_visual_package( $film_id, 'large' ) : array();

        $title_entry['visual'] = $visual;
        $title_entry['url']    = ! empty( $title_entry['film_url'] )
            ? $title_entry['film_url']
            : ( ( $film_id !== '' && method_exists( $aat, 'build_entity_url_from_id' ) ) ? $aat->build_entity_url_from_id( $film_id ) : home_url( '/oscars/title/' . $film_id . '/' ) );
        $title_entry['winning_categories_line'] = ! empty( $title_entry['winning_categories'] ) && is_array( $title_entry['winning_categories'] )
            ? implode( ' Â· ', array_slice( array_values( $title_entry['winning_categories'] ), 0, 2 ) )
            : '';

        $top_titles[] = $title_entry;
    }

    $best_picture = ! empty( $rollup['best_picture'] ) && is_array( $rollup['best_picture'] ) ? $rollup['best_picture'] : array();
    if ( ! empty( $best_picture['film_id'] ) && method_exists( $aat, 'get_title_visual_package' ) ) {
        $best_picture['visual'] = $aat->get_title_visual_package( $best_picture['film_id'], 'large' );
    } else {
        $best_picture['visual'] = array();
    }

    $winner_rows_total = count( $winner_rows );
    $categories_total  = intval( $rollup['categories_total'] ?? 0 );
    $summary_bits      = array();

    if ( ! empty( $best_picture['film'] ) ) {
        $summary_bits[] = sprintf( 'Best Picture went to %s', $best_picture['film'] );
    }

    if ( ! empty( $rollup['most_wins']['film'] ) && ! empty( $rollup['most_wins']['wins'] ) ) {
        $summary_bits[] = sprintf( '%1$s led the ceremony with %2$s win%3$s', $rollup['most_wins']['film'], number_format_i18n( intval( $rollup['most_wins']['wins'] ) ), intval( $rollup['most_wins']['wins'] ) === 1 ? '' : 's' );
    }

    $snapshot = array(
        'ceremony'          => $ceremony,
        'ceremony_label'    => method_exists( $aat, 'ordinal' ) ? $aat->ordinal( $ceremony ) . ' Academy Awards' : sprintf( 'Ceremony %d', $ceremony ),
        'year_label'        => method_exists( $aat, 'get_ceremony_year' ) ? $aat->get_ceremony_year( $ceremony ) : (string) ( $rollup['year'] ?? '' ),
        'ceremony_url'      => method_exists( $aat, 'get_ceremony_url' ) ? $aat->get_ceremony_url( $ceremony ) : home_url( '/oscars/ceremony/' . $ceremony . '/' ),
        'database_url'      => method_exists( $aat, 'get_database_url' ) ? $aat->get_database_url() : home_url( '/oscars/' ),
        'categories_url'    => method_exists( $aat, 'get_categories_index_url' ) ? $aat->get_categories_index_url() : home_url( '/oscars/categories/' ),
        'rollup'            => $rollup,
        'best_picture'      => $best_picture,
        'spotlights'        => $spotlights,
        'winner_map'        => $winner_map,
        'top_titles'        => $top_titles,
        'winner_rows_total' => $winner_rows_total,
        'categories_total'  => $categories_total,
        'summary'           => ! empty( $summary_bits ) ? implode( '. ', $summary_bits ) . '.' : '',
        'winner_record'     => sprintf(
            '%1$s winner row%2$s across %3$s categor%4$s%5$s',
            number_format_i18n( $winner_rows_total ),
            $winner_rows_total === 1 ? '' : 's',
            number_format_i18n( $categories_total ),
            $categories_total === 1 ? 'y' : 'ies',
            $winner_rows_total > $categories_total ? ', including ties' : ''
        ),
    );

    set_transient( $cache_key, $snapshot, 5 * MINUTE_IN_SECONDS );

    return $snapshot;
}
}

/**
 * Find a spotlight winner entry in the homepage snapshot by canonical category.
 */
if ( ! function_exists( 'lunara_get_home_snapshot_spotlight' ) ) {
function lunara_get_home_snapshot_spotlight( $snapshot, $canonical_category ) {
    foreach ( (array) ( $snapshot['spotlights'] ?? array() ) as $spotlight ) {
        if ( strtoupper( trim( (string) ( $spotlight['canonical_category'] ?? '' ) ) ) === strtoupper( trim( (string) $canonical_category ) ) ) {
            return $spotlight;
        }
    }

    return array();
}
}

/**
 * Determine whether a category debuts in the supplied ceremony.
 */
if ( ! function_exists( 'lunara_home_category_debuts_in_ceremony' ) ) {
function lunara_home_category_debuts_in_ceremony( $canonical_category, $ceremony ) {
    global $wpdb;

    $canonical_category = trim( (string) $canonical_category );
    $ceremony           = intval( $ceremony );

    if ( $canonical_category === '' || $ceremony <= 0 ) {
        return false;
    }

    $table_name = $wpdb->prefix . 'academy_awards';
    $first_seen = intval(
        $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MIN(ceremony) FROM $table_name WHERE canonical_category = %s",
                $canonical_category
            )
        )
    );

    return $first_seen > 0 && $first_seen === $ceremony;
}
}

/**
 * Build editable homepage Oscar pulse notes.
 */
if ( ! function_exists( 'lunara_get_home_pulse_editorial_cards' ) ) {
function lunara_get_home_pulse_editorial_cards( $snapshot = array() ) {
    if ( empty( $snapshot ) ) {
        $snapshot = lunara_get_home_oscars_snapshot();
    }

    $ceremony          = intval( $snapshot['ceremony'] ?? 0 );
    $ceremony_label    = trim( (string) ( $snapshot['ceremony_label'] ?? 'Latest Oscar Pulse' ) );
    $database_url      = trim( (string) ( $snapshot['database_url'] ?? home_url( '/oscars/' ) ) );
    $ceremony_url      = trim( (string) ( $snapshot['ceremony_url'] ?? $database_url ) );
    $best_picture      = (array) ( $snapshot['best_picture'] ?? array() );
    $most_wins         = (array) ( $snapshot['rollup']['most_wins'] ?? array() );
    $casting_spotlight = lunara_get_home_snapshot_spotlight( $snapshot, 'CASTING' );
    $cinema_spotlight  = lunara_get_home_snapshot_spotlight( $snapshot, 'CINEMATOGRAPHY' );

    $cards = array(
        array(
            'kicker'     => 'Latest Best Picture',
            'title'      => ! empty( $best_picture['film'] ) ? sprintf( '%s is now the front door into the latest ceremony', $best_picture['film'] ) : 'The latest Best Picture winner is now live',
            'copy'       => ! empty( $most_wins['film'] ) && ! empty( $most_wins['wins'] )
                ? sprintf( '%1$s now sits inside the latest Academy Awards hub alongside %2$s, which led the night with %3$s win%4$s.', $ceremony_label, $most_wins['film'], number_format_i18n( intval( $most_wins['wins'] ) ), intval( $most_wins['wins'] ) === 1 ? '' : 's' )
                : 'The newest winners, ceremony rollup, and film pages are already stitched into the Lunara Oscar Ledger.',
            'link_label' => 'Open Ceremony Hub',
            'url'        => $ceremony_url,
        ),
        array(
            'kicker'     => lunara_home_category_debuts_in_ceremony( 'CASTING', $ceremony ) ? 'New Oscar Category' : 'Category Spotlight',
            'title'      => ! empty( $casting_spotlight['primary_label'] )
                ? sprintf( '%s keeps the newest category from feeling abstract', $casting_spotlight['primary_label'] )
                : 'Every category in the Ledger leads somewhere real',
            'copy'       => ! empty( $casting_spotlight['film'] )
                ? sprintf( '%s gives you a live example of how the Ledger moves from a category win into a film page, people pages, and broader ceremony context.', $casting_spotlight['film'] )
                : 'Browse any category to see the full lineage of winners and nominees, then follow each name into the wider Lunara record.',
            'link_label' => ! empty( $casting_spotlight['category_url'] ) ? 'See the Category' : 'Explore the Ledger',
            'url'        => ! empty( $casting_spotlight['category_url'] ) ? $casting_spotlight['category_url'] : $database_url,
        ),
        array(
            'kicker'     => ! empty( $cinema_spotlight['category_label'] ) ? $cinema_spotlight['category_label'] : 'Editorial Spotlight',
            'title'      => ! empty( $cinema_spotlight['primary_label'] )
                ? sprintf( '%s won in a category that rewards the image itself', $cinema_spotlight['primary_label'] )
                : 'The craft categories reveal the ceremony from a different angle',
            'copy'       => ! empty( $cinema_spotlight['film'] )
                ? sprintf( 'Follow %s through the Ledger to see how the craft side of this ceremony connects to the broader competition.', $cinema_spotlight['film'] )
                : 'Cinematography, editing, sound, and design winners sit alongside every other category inside the same Lunara record.',
            'link_label' => ! empty( $cinema_spotlight['url'] ) ? 'Open the Winner Page' : 'Browse Categories',
            'url'        => ! empty( $cinema_spotlight['url'] ) ? $cinema_spotlight['url'] : $database_url,
        ),
    );

    foreach ( $cards as $index => $card ) {
        $card_number = $index + 1;
        foreach ( array( 'kicker', 'title', 'copy', 'link_label', 'url' ) as $field ) {
            $theme_mod_key = sprintf( 'lunara_home_pulse_card_%d_%s', $card_number, $field === 'url' ? 'link_url' : $field );
            $override      = 'url' === $field
                ? lunara_theme_mod_url( $theme_mod_key, '' )
                : lunara_theme_mod_text( $theme_mod_key, '' );

            if ( '' !== $override ) {
                $cards[ $index ][ $field ] = $override;
            }
        }
    }

    return $cards;
}
}

/**
 * Build a poster-first story card for a specific Oscar-recognized title.
 */
if ( ! function_exists( 'lunara_build_home_title_story' ) ) {
function lunara_build_home_title_story( $imdb_id, $args = array() ) {
    $aat = lunara_get_oscars_plugin();
    if ( ! $aat || ! method_exists( $aat, 'get_title_visual_package' ) ) {
        return array();
    }

    $defaults = array(
        'preferred_categories' => array(),
        'max_categories'       => 4,
    );
    $args = wp_parse_args( $args, $defaults );

    global $wpdb;
    $table_name = $wpdb->prefix . 'academy_awards';
    $imdb_id    = strtolower( trim( (string) $imdb_id ) );

    if ( $imdb_id === '' ) {
        return array();
    }

    $like = '%' . $wpdb->esc_like( $imdb_id ) . '%';
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ceremony, year, canonical_category, film, winner FROM $table_name WHERE (film_id = %s OR film_id LIKE %s) AND canonical_category != '' ORDER BY ceremony DESC, winner DESC, canonical_category ASC",
            $imdb_id,
            $like
        ),
        ARRAY_A
    );

    if ( ! is_array( $rows ) || empty( $rows ) ) {
        return array();
    }

    $winner_rows = array_values(
        array_filter(
            $rows,
            static function( $row ) {
                return ! empty( $row['winner'] );
            }
        )
    );

    $raw_categories = array();
    foreach ( $winner_rows as $row ) {
        $canonical = trim( (string) ( $row['canonical_category'] ?? '' ) );
        if ( $canonical !== '' && ! in_array( $canonical, $raw_categories, true ) ) {
            $raw_categories[] = $canonical;
        }
    }

    $ordered_categories = array();
    foreach ( (array) $args['preferred_categories'] as $preferred ) {
        if ( in_array( $preferred, $raw_categories, true ) ) {
            $ordered_categories[] = $preferred;
        }
    }
    foreach ( $raw_categories as $canonical ) {
        if ( ! in_array( $canonical, $ordered_categories, true ) ) {
            $ordered_categories[] = $canonical;
        }
    }

    $display_categories = array();
    foreach ( array_slice( $ordered_categories, 0, intval( $args['max_categories'] ) ) as $canonical ) {
        $display_categories[] = method_exists( $aat, 'format_category_display' ) ? $aat->format_category_display( $canonical ) : $canonical;
    }

    $first_row = $rows[0];
    $title     = trim( (string) ( $first_row['film'] ?? '' ) );
    if ( $title === '' && method_exists( $aat, 'lookup_title_label' ) ) {
        $title = $aat->lookup_title_label( $imdb_id );
    }

    return array(
        'imdb_id'         => $imdb_id,
        'title'           => $title,
        'year'            => trim( (string) ( $first_row['year'] ?? '' ) ),
        'url'             => method_exists( $aat, 'build_entity_url_from_id' ) ? $aat->build_entity_url_from_id( $imdb_id ) : home_url( '/oscars/title/' . $imdb_id . '/' ),
        'visual'          => $aat->get_title_visual_package( $imdb_id, 'medium_large' ),
        'wins'            => count( $winner_rows ),
        'nominations'     => count( $rows ),
        'categories'      => $display_categories,
        'categories_line' => implode( ' / ', $display_categories ),
    );
}
}

/**
 * Build the homepage Oscar Database spotlight section.
 */
if ( ! function_exists( 'lunara_get_home_database_spotlight' ) ) {
function lunara_get_home_database_spotlight() {
    $cache_key = 'lunara_home_database_spotlight_v1';
    $cached    = get_transient( $cache_key );

    if ( is_array( $cached ) && ! empty( $cached ) ) {
        return $cached;
    }

    $aat = lunara_get_oscars_plugin();
    if ( ! $aat ) {
        return array();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'academy_awards';
    $snapshot   = lunara_get_home_oscars_snapshot();

    $cards = array();
    foreach ( array_slice( (array) ( $snapshot['top_titles'] ?? array() ), 0, 5 ) as $title_entry ) {
        $card = array(
            'title'           => trim( (string) ( $title_entry['film'] ?? '' ) ),
            'year'            => trim( (string) ( $title_entry['year'] ?? '' ) ),
            'url'             => trim( (string) ( $title_entry['url'] ?? '' ) ),
            'visual'          => is_array( $title_entry['visual'] ?? null ) ? $title_entry['visual'] : array(),
            'categories_line' => trim( (string) ( $title_entry['winning_categories_line'] ?? '' ) ),
        );

        if ( '' !== $card['title'] && '' !== $card['url'] ) {
            $cards[] = $card;
        }
    }

    if ( count( $cards ) < 5 ) {
        // Fill remaining slots from the latest ceremony's most-nominated films
        $max_ceremony = intval( $wpdb->get_var( "SELECT MAX(ceremony) FROM $table_name" ) );
        if ( $max_ceremony > 0 ) {
            $existing_urls = array();
            foreach ( $cards as $c ) {
                $existing_urls[ strtolower( trim( (string) ( $c['url'] ?? '' ) ) ) ] = true;
            }

            $fill_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT film_id, film, COUNT(*) as nom_count FROM $table_name WHERE ceremony = %d AND film_id != '' AND canonical_category != '' GROUP BY film_id, film ORDER BY nom_count DESC LIMIT 10",
                    $max_ceremony
                ),
                ARRAY_A
            );

            foreach ( $fill_rows as $fill_row ) {
                if ( count( $cards ) >= 5 ) {
                    break;
                }

                $fill_card = lunara_build_home_title_story( $fill_row['film_id'], array( 'max_categories' => 3 ) );
                if ( ! empty( $fill_card ) && ! empty( $fill_card['url'] ) ) {
                    $fill_url = strtolower( trim( (string) $fill_card['url'] ) );
                    if ( ! isset( $existing_urls[ $fill_url ] ) ) {
                        $cards[]                    = $fill_card;
                        $existing_urls[ $fill_url ] = true;
                    }
                }
            }
        }
    }

    $spotlight = array(
        'database_url'      => method_exists( $aat, 'get_database_url' ) ? $aat->get_database_url() : home_url( '/oscars/' ),
        'categories_url'    => method_exists( $aat, 'get_categories_index_url' ) ? $aat->get_categories_index_url() : home_url( '/oscars/categories/' ),
        'records_total'     => intval( $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ) ),
        'winners_total'     => intval( $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE winner = 1" ) ),
        'categories_total'  => intval( $wpdb->get_var( "SELECT COUNT(DISTINCT canonical_category) FROM $table_name WHERE canonical_category != ''" ) ),
        'ceremonies_total'  => intval( $wpdb->get_var( "SELECT COUNT(DISTINCT ceremony) FROM $table_name" ) ),
        'cards'             => $cards,
    );

    set_transient( $cache_key, $spotlight, 15 * MINUTE_IN_SECONDS );

    return $spotlight;
}
}

/**
 * Build the homepage "From the Ledger" story cards.
 *
 * Feature 3: Dynamic + admin-controllable.
 * - If the Customizer override IDs are set, use those.
 * - Otherwise, query the database for a rotating mix of Best Picture winners
 *   and high-nomination films, refreshed every 6 hours.
 */
if ( ! function_exists( 'lunara_get_home_ledger_story_cards' ) ) {
function lunara_get_home_ledger_story_cards() {
    $cache_key = 'lunara_home_ledger_story_cards_v2';
    $cached    = get_transient( $cache_key );

    if ( is_array( $cached ) && ! empty( $cached ) ) {
        return $cached;
    }

    // Check for Customizer overrides first.
    $override_ids = array();
    for ( $i = 1; $i <= 4; $i++ ) {
        $id = trim( (string) get_theme_mod( 'lunara_home_ledger_card_' . $i . '_imdb_id', '' ) );
        if ( preg_match( '/^tt\d{7,8}$/i', $id ) ) {
            $override_ids[] = strtolower( $id );
        }
    }

    if ( count( $override_ids ) >= 4 ) {
        // Use the admin-selected IDs.
        $selected_ids = array_slice( $override_ids, 0, 4 );
    } else {
        // Dynamic rotation: query the database.
        $table = lunara_awards_table_name();
        if ( $table === '' ) {
            // Fallback to curated classics.
            $selected_ids = array( 'tt0070047', 'tt0074958', 'tt0477348', 'tt6751668' );
        } else {
            global $wpdb;
            $day_of_year = intval( date( 'z' ) );

            $bp_categories = "'BEST PICTURE','BEST MOTION PICTURE','OUTSTANDING PICTURE','OUTSTANDING PRODUCTION','OUTSTANDING MOTION PICTURE','UNIQUE AND ARTISTIC PICTURE'";

            // Pool 1: Random Best Picture winners (seeded by day).
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $bp_winners = $wpdb->get_col(
                "SELECT DISTINCT film_id FROM {$table} WHERE category IN ({$bp_categories}) AND winner = 1 AND film_id != '' ORDER BY RAND({$day_of_year}) LIMIT 8"
            );

            // Pool 2: High-nomination films (10+ noms).
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $high_nom = $wpdb->get_col(
                "SELECT film_id FROM {$table} WHERE film_id != '' GROUP BY film_id HAVING COUNT(*) >= 10 ORDER BY RAND({$day_of_year}) LIMIT 8"
            );

            // Pool 3: Recent ceremony highlights (latest 3 ceremonies).
            $aat = lunara_get_oscars_plugin();
            $max_ceremony = ( $aat && method_exists( $aat, 'get_max_ceremony' ) ) ? intval( $aat->get_max_ceremony() ) : 0;
            $recent_ids = array();
            if ( $max_ceremony > 0 ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $recent_ids = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT DISTINCT film_id FROM {$table} WHERE ceremony >= %d AND winner = 1 AND film_id != '' ORDER BY RAND({$day_of_year}) LIMIT 6",
                        max( 1, $max_ceremony - 2 )
                    )
                );
            }

            // Merge pools, deduplicate, pick 4.
            $all_pools = array_merge(
                array_slice( (array) $bp_winners, 0, 3 ),
                array_slice( (array) $high_nom, 0, 3 ),
                array_slice( (array) $recent_ids, 0, 3 )
            );
            $all_pools = array_values( array_unique( array_filter( $all_pools ) ) );

            // Fill in any Customizer overrides at the front.
            $selected_ids = array();
            foreach ( $override_ids as $oid ) {
                $selected_ids[] = $oid;
            }
            foreach ( $all_pools as $pool_id ) {
                if ( ! in_array( $pool_id, $selected_ids, true ) ) {
                    $selected_ids[] = $pool_id;
                }
                if ( count( $selected_ids ) >= 4 ) {
                    break;
                }
            }

            // Final fallback if database is sparse.
            $fallback_ids = array( 'tt0070047', 'tt0074958', 'tt0477348', 'tt6751668' );
            foreach ( $fallback_ids as $fid ) {
                if ( count( $selected_ids ) >= 4 ) {
                    break;
                }
                if ( ! in_array( $fid, $selected_ids, true ) ) {
                    $selected_ids[] = $fid;
                }
            }
        }
    }

    $cards = array();
    foreach ( array_slice( $selected_ids, 0, 4 ) as $imdb_id ) {
        $card = lunara_build_home_title_story(
            $imdb_id,
            array(
                'preferred_categories' => array( 'BEST PICTURE', 'DIRECTING', 'ACTOR IN A LEADING ROLE', 'ACTRESS IN A LEADING ROLE' ),
                'max_categories'       => 4,
            )
        );

        if ( ! empty( $card ) ) {
            $cards[] = $card;
        }
    }

    // Cache for 6 hours.
    set_transient( $cache_key, $cards, 6 * HOUR_IN_SECONDS );

    return $cards;
}
}

/* ========================================
   FEATURE 1: OSCAR SPOTLIGHT (date-based daily rotation)
   ======================================== */

/**
 * Build the homepage Oscar Spotlight section.
 * Uses date('z') (day of year 0-365) to rotate through different spotlight types.
 * Cached as a 12-hour transient.
 */
if ( ! function_exists( 'lunara_get_home_oscar_spotlight' ) ) {
function lunara_get_home_oscar_spotlight() {
    $cache_key = 'lunara_home_oscar_spotlight_v1';
    $cached    = get_transient( $cache_key );

    if ( is_array( $cached ) && ! empty( $cached ) ) {
        return $cached;
    }

    $table = lunara_awards_table_name();
    if ( $table === '' ) {
        return array();
    }

    global $wpdb;
    $aat       = lunara_get_oscars_plugin();
    $day       = intval( date( 'z' ) );
    $month     = intval( date( 'n' ) );
    $result    = array();

    $bp_categories = "'BEST PICTURE','BEST MOTION PICTURE','OUTSTANDING PICTURE','OUTSTANDING PRODUCTION','OUTSTANDING MOTION PICTURE','UNIQUE AND ARTISTIC PICTURE'";

    if ( $day <= 72 ) {
        // --- "On This Day" â€” ceremonies/wins from the current month across all years ---
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $ceremony_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ceremony, year, film, film_id FROM {$table} WHERE category IN ({$bp_categories}) AND winner = 1 ORDER BY RAND(%d) LIMIT 1",
                $day + $month * 100
            ),
            ARRAY_A
        );

        if ( empty( $ceremony_row ) ) {
            return array();
        }

        $ceremony_num  = intval( $ceremony_row['ceremony'] );
        $film          = trim( (string) ( $ceremony_row['film'] ?? '' ) );
        $film_id       = trim( (string) ( $ceremony_row['film_id'] ?? '' ) );

        // Get stats for this ceremony.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $ceremony_stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) AS total_noms, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS total_wins, COUNT(DISTINCT canonical_category) AS categories FROM {$table} WHERE ceremony = %d",
                $ceremony_num
            ),
            ARRAY_A
        );

        $ceremony_label = ( $aat && method_exists( $aat, 'ordinal' ) ) ? $aat->ordinal( $ceremony_num ) . ' Academy Awards' : sprintf( 'Ceremony %d', $ceremony_num );
        $ceremony_url   = ( $aat && method_exists( $aat, 'get_ceremony_url' ) ) ? $aat->get_ceremony_url( $ceremony_num ) : home_url( '/oscars/ceremony/' . $ceremony_num . '/' );

        $visual = array();
        if ( $film_id !== '' && $aat && method_exists( $aat, 'get_title_visual_package' ) ) {
            $visual = $aat->get_title_visual_package( $film_id, 'medium_large' );
        }

        $result = array(
            'kicker'        => 'On This Day in Oscar History',
            'title'         => sprintf( '%s won Best Picture at the %s', $film, $ceremony_label ),
            'copy'          => sprintf( 'The %s featured %s categories, %s nominees, and %s winners. Explore the full ceremony breakdown.',
                $ceremony_label,
                number_format_i18n( intval( $ceremony_stats['categories'] ?? 0 ) ),
                number_format_i18n( intval( $ceremony_stats['total_noms'] ?? 0 ) ),
                number_format_i18n( intval( $ceremony_stats['total_wins'] ?? 0 ) )
            ),
            'stats'         => array(
                array( 'label' => 'Ceremony', 'value' => $ceremony_label ),
                array( 'label' => 'Year', 'value' => trim( (string) ( $ceremony_row['year'] ?? '' ) ) ),
                array( 'label' => 'Categories', 'value' => number_format_i18n( intval( $ceremony_stats['categories'] ?? 0 ) ) ),
                array( 'label' => 'Winners', 'value' => number_format_i18n( intval( $ceremony_stats['total_wins'] ?? 0 ) ) ),
            ),
            'featured_film' => array(
                'title'   => $film,
                'film_id' => $film_id,
                'visual'  => $visual,
                'url'     => $film_id !== '' ? home_url( '/oscars/title/' . $film_id . '/' ) : $ceremony_url,
            ),
            'url'           => $ceremony_url,
        );

    } elseif ( $day <= 145 ) {
        // --- "Category Deep Dive" â€” spotlight a random category with its most decorated nominee ---
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $category_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT canonical_category, COUNT(*) AS total, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS wins FROM {$table} WHERE canonical_category != '' GROUP BY canonical_category HAVING total >= 20 ORDER BY RAND(%d) LIMIT 1",
                $day
            ),
            ARRAY_A
        );

        if ( empty( $category_row ) ) {
            return array();
        }

        $canonical = trim( (string) $category_row['canonical_category'] );
        $category_label = ( $aat && method_exists( $aat, 'format_category_display' ) ) ? $aat->format_category_display( $canonical ) : $canonical;
        $category_url   = ( $aat && method_exists( $aat, 'get_category_url' ) ) ? $aat->get_category_url( $canonical ) : home_url( '/oscars/category/' . sanitize_title( $canonical ) . '/' );

        // Most decorated person/film in this category.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $top_nominee = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT name, film, film_id, COUNT(*) AS noms, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS wins FROM {$table} WHERE canonical_category = %s AND name != '' GROUP BY name ORDER BY noms DESC, wins DESC LIMIT 1",
                $canonical
            ),
            ARRAY_A
        );

        $film_id = trim( (string) ( $top_nominee['film_id'] ?? '' ) );
        $visual  = array();
        if ( $film_id !== '' && $aat && method_exists( $aat, 'get_title_visual_package' ) ) {
            $visual = $aat->get_title_visual_package( $film_id, 'medium_large' );
        }

        $result = array(
            'kicker'        => 'Category Deep Dive',
            'title'         => $category_label,
            'copy'          => sprintf( '%s has %s total nomination rows and %s winners in the ledger. %s leads this category with %s nominations and %s wins.',
                $category_label,
                number_format_i18n( intval( $category_row['total'] ) ),
                number_format_i18n( intval( $category_row['wins'] ) ),
                ! empty( $top_nominee['name'] ) ? $top_nominee['name'] : 'The most decorated nominee',
                number_format_i18n( intval( $top_nominee['noms'] ?? 0 ) ),
                number_format_i18n( intval( $top_nominee['wins'] ?? 0 ) )
            ),
            'stats'         => array(
                array( 'label' => 'Nominations', 'value' => number_format_i18n( intval( $category_row['total'] ) ) ),
                array( 'label' => 'Winners', 'value' => number_format_i18n( intval( $category_row['wins'] ) ) ),
                array( 'label' => 'Top Nominee', 'value' => trim( (string) ( $top_nominee['name'] ?? 'N/A' ) ) ),
                array( 'label' => 'Their Noms', 'value' => number_format_i18n( intval( $top_nominee['noms'] ?? 0 ) ) ),
            ),
            'featured_film' => array(
                'title'   => trim( (string) ( $top_nominee['film'] ?? $category_label ) ),
                'film_id' => $film_id,
                'visual'  => $visual,
                'url'     => $category_url,
            ),
            'url'           => $category_url,
        );

    } elseif ( $day <= 218 ) {
        // --- "The Record Holders" â€” most wins/nominations for a rotating category ---
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $record_row = $wpdb->get_row(
            "SELECT film, film_id, COUNT(*) AS noms, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS wins FROM {$table} WHERE film_id != '' AND film != '' GROUP BY film_id ORDER BY wins DESC, noms DESC LIMIT 1",
            ARRAY_A
        );

        if ( empty( $record_row ) ) {
            return array();
        }

        $film    = trim( (string) $record_row['film'] );
        $film_id = trim( (string) $record_row['film_id'] );
        $visual  = array();
        if ( $film_id !== '' && $aat && method_exists( $aat, 'get_title_visual_package' ) ) {
            $visual = $aat->get_title_visual_package( $film_id, 'medium_large' );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total_unique_films = intval( $wpdb->get_var( "SELECT COUNT(DISTINCT film_id) FROM {$table} WHERE film_id != ''" ) );

        $result = array(
            'kicker'        => 'The Record Holders',
            'title'         => sprintf( '%s holds the most Oscar wins', $film ),
            'copy'          => sprintf( 'With %s wins from %s nominations, %s stands at the top of the all-time Oscar leaderboard across %s unique films in the database.',
                number_format_i18n( intval( $record_row['wins'] ) ),
                number_format_i18n( intval( $record_row['noms'] ) ),
                $film,
                number_format_i18n( $total_unique_films )
            ),
            'stats'         => array(
                array( 'label' => 'Wins', 'value' => number_format_i18n( intval( $record_row['wins'] ) ) ),
                array( 'label' => 'Nominations', 'value' => number_format_i18n( intval( $record_row['noms'] ) ) ),
                array( 'label' => 'Unique Films', 'value' => number_format_i18n( $total_unique_films ) ),
            ),
            'featured_film' => array(
                'title'   => $film,
                'film_id' => $film_id,
                'visual'  => $visual,
                'url'     => $film_id !== '' ? home_url( '/oscars/title/' . $film_id . '/' ) : home_url( '/oscars/' ),
            ),
            'url'           => $film_id !== '' ? home_url( '/oscars/title/' . $film_id . '/' ) : home_url( '/oscars/' ),
        );

    } elseif ( $day <= 291 ) {
        // --- "Oscar Rivalries" â€” ceremonies where multiple films had 10+ nominations ---
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rivalry_ceremony = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ceremony, year FROM (
                    SELECT ceremony, year, film_id, COUNT(*) AS noms
                    FROM {$table}
                    WHERE film_id != ''
                    GROUP BY ceremony, film_id
                    HAVING noms >= 10
                ) AS high_nom_films
                GROUP BY ceremony
                HAVING COUNT(*) >= 2
                ORDER BY RAND(%d) LIMIT 1",
                $day
            ),
            ARRAY_A
        );

        if ( empty( $rivalry_ceremony ) ) {
            // Fallback: just pick the ceremony with the most total nominations.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $rivalry_ceremony = $wpdb->get_row(
                "SELECT ceremony, year FROM {$table} GROUP BY ceremony ORDER BY COUNT(*) DESC LIMIT 1",
                ARRAY_A
            );
        }

        if ( empty( $rivalry_ceremony ) ) {
            return array();
        }

        $ceremony_num = intval( $rivalry_ceremony['ceremony'] );
        $ceremony_label = ( $aat && method_exists( $aat, 'ordinal' ) ) ? $aat->ordinal( $ceremony_num ) . ' Academy Awards' : sprintf( 'Ceremony %d', $ceremony_num );
        $ceremony_url   = ( $aat && method_exists( $aat, 'get_ceremony_url' ) ) ? $aat->get_ceremony_url( $ceremony_num ) : home_url( '/oscars/ceremony/' . $ceremony_num . '/' );

        // Top two films at this ceremony by nomination count.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rivals = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT film, film_id, COUNT(*) AS noms, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS wins FROM {$table} WHERE ceremony = %d AND film_id != '' GROUP BY film_id ORDER BY noms DESC LIMIT 2",
                $ceremony_num
            ),
            ARRAY_A
        );

        $first  = ! empty( $rivals[0] ) ? $rivals[0] : array();
        $second = ! empty( $rivals[1] ) ? $rivals[1] : array();
        $film_id = trim( (string) ( $first['film_id'] ?? '' ) );

        $visual = array();
        if ( $film_id !== '' && $aat && method_exists( $aat, 'get_title_visual_package' ) ) {
            $visual = $aat->get_title_visual_package( $film_id, 'medium_large' );
        }

        $result = array(
            'kicker'        => 'Oscar Rivalries',
            'title'         => sprintf( '%s vs. %s at the %s',
                trim( (string) ( $first['film'] ?? 'Film A' ) ),
                trim( (string) ( $second['film'] ?? 'Film B' ) ),
                $ceremony_label
            ),
            'copy'          => sprintf( '%s led with %s nominations (%s wins) while %s earned %s nominations (%s wins). A ceremony worth revisiting.',
                trim( (string) ( $first['film'] ?? 'The front-runner' ) ),
                number_format_i18n( intval( $first['noms'] ?? 0 ) ),
                number_format_i18n( intval( $first['wins'] ?? 0 ) ),
                trim( (string) ( $second['film'] ?? 'the challenger' ) ),
                number_format_i18n( intval( $second['noms'] ?? 0 ) ),
                number_format_i18n( intval( $second['wins'] ?? 0 ) )
            ),
            'stats'         => array(
                array( 'label' => trim( (string) ( $first['film'] ?? 'Film A' ) ), 'value' => sprintf( '%s noms / %s wins', number_format_i18n( intval( $first['noms'] ?? 0 ) ), number_format_i18n( intval( $first['wins'] ?? 0 ) ) ) ),
                array( 'label' => trim( (string) ( $second['film'] ?? 'Film B' ) ), 'value' => sprintf( '%s noms / %s wins', number_format_i18n( intval( $second['noms'] ?? 0 ) ), number_format_i18n( intval( $second['wins'] ?? 0 ) ) ) ),
                array( 'label' => 'Ceremony', 'value' => $ceremony_label ),
            ),
            'featured_film' => array(
                'title'   => trim( (string) ( $first['film'] ?? '' ) ),
                'film_id' => $film_id,
                'visual'  => $visual,
                'url'     => $ceremony_url,
            ),
            'url'           => $ceremony_url,
        );

    } else {
        // --- "Ceremony Spotlight" â€” random historical ceremony + BP winner + stats ---
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $random_ceremony = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT DISTINCT ceremony, year FROM {$table} ORDER BY RAND(%d) LIMIT 1",
                $day
            ),
            ARRAY_A
        );

        if ( empty( $random_ceremony ) ) {
            return array();
        }

        $ceremony_num   = intval( $random_ceremony['ceremony'] );
        $ceremony_label = ( $aat && method_exists( $aat, 'ordinal' ) ) ? $aat->ordinal( $ceremony_num ) . ' Academy Awards' : sprintf( 'Ceremony %d', $ceremony_num );
        $ceremony_url   = ( $aat && method_exists( $aat, 'get_ceremony_url' ) ) ? $aat->get_ceremony_url( $ceremony_num ) : home_url( '/oscars/ceremony/' . $ceremony_num . '/' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $bp_winner = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT film, film_id FROM {$table} WHERE ceremony = %d AND category IN ({$bp_categories}) AND winner = 1 LIMIT 1",
                $ceremony_num
            ),
            ARRAY_A
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $ceremony_stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) AS total_noms, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS total_wins, COUNT(DISTINCT canonical_category) AS categories FROM {$table} WHERE ceremony = %d",
                $ceremony_num
            ),
            ARRAY_A
        );

        $film    = trim( (string) ( $bp_winner['film'] ?? 'Unknown' ) );
        $film_id = trim( (string) ( $bp_winner['film_id'] ?? '' ) );
        $visual  = array();
        if ( $film_id !== '' && $aat && method_exists( $aat, 'get_title_visual_package' ) ) {
            $visual = $aat->get_title_visual_package( $film_id, 'medium_large' );
        }

        $result = array(
            'kicker'        => 'Ceremony Spotlight',
            'title'         => $ceremony_label,
            'copy'          => sprintf( '%s won Best Picture at the %s, a ceremony with %s categories, %s nominees, and %s winners.',
                $film,
                $ceremony_label,
                number_format_i18n( intval( $ceremony_stats['categories'] ?? 0 ) ),
                number_format_i18n( intval( $ceremony_stats['total_noms'] ?? 0 ) ),
                number_format_i18n( intval( $ceremony_stats['total_wins'] ?? 0 ) )
            ),
            'stats'         => array(
                array( 'label' => 'Best Picture', 'value' => $film ),
                array( 'label' => 'Year', 'value' => trim( (string) ( $random_ceremony['year'] ?? '' ) ) ),
                array( 'label' => 'Categories', 'value' => number_format_i18n( intval( $ceremony_stats['categories'] ?? 0 ) ) ),
                array( 'label' => 'Winners', 'value' => number_format_i18n( intval( $ceremony_stats['total_wins'] ?? 0 ) ) ),
            ),
            'featured_film' => array(
                'title'   => $film,
                'film_id' => $film_id,
                'visual'  => $visual,
                'url'     => $film_id !== '' ? home_url( '/oscars/title/' . $film_id . '/' ) : $ceremony_url,
            ),
            'url'           => $ceremony_url,
        );
    }

    if ( ! empty( $result ) ) {
        set_transient( $cache_key, $result, 12 * HOUR_IN_SECONDS );
    }

    return $result;
}
}

/* ========================================
   FEATURE 2: DEEP CUT STATS
   ======================================== */

/**
 * Build the homepage Deep Cut Stats section.
 * Queries the database for 4 interesting stats, rotating daily using date('z').
 * Cached as a 24-hour transient.
 */
if ( ! function_exists( 'lunara_get_home_deep_cuts' ) ) {
function lunara_get_home_deep_cuts() {
    $cache_key = 'lunara_home_deep_cuts_v1';
    $cached    = get_transient( $cache_key );

    if ( is_array( $cached ) && ! empty( $cached ) ) {
        return $cached;
    }

    $table = lunara_awards_table_name();
    if ( $table === '' ) {
        return array();
    }

    global $wpdb;
    $aat = lunara_get_oscars_plugin();
    $day = intval( date( 'z' ) );

    // Build a pool of stats, then pick 4 based on the day.
    $pool = array();

    // 1. Most wins by a single film.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $most_wins_film = $wpdb->get_row(
        "SELECT film, film_id, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS wins FROM {$table} WHERE film_id != '' GROUP BY film_id ORDER BY wins DESC LIMIT 1",
        ARRAY_A
    );
    if ( ! empty( $most_wins_film ) ) {
        $pool[] = array(
            'label'   => 'Most Wins by a Single Film',
            'value'   => number_format_i18n( intval( $most_wins_film['wins'] ) ),
            'context' => trim( (string) $most_wins_film['film'] ),
            'url'     => ! empty( $most_wins_film['film_id'] ) ? home_url( '/oscars/title/' . $most_wins_film['film_id'] . '/' ) : home_url( '/oscars/' ),
        );
    }

    // 2. Total unique films in database.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $unique_films = intval( $wpdb->get_var( "SELECT COUNT(DISTINCT film_id) FROM {$table} WHERE film_id != ''" ) );
    if ( $unique_films > 0 ) {
        $pool[] = array(
            'label'   => 'Unique Films in the Ledger',
            'value'   => number_format_i18n( $unique_films ),
            'context' => 'Every nominated and winning film, tracked',
            'url'     => home_url( '/oscars/' ),
        );
    }

    // 3. Total unique people in database.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $unique_people = intval( $wpdb->get_var( "SELECT COUNT(DISTINCT name) FROM {$table} WHERE name != ''" ) );
    if ( $unique_people > 0 ) {
        $pool[] = array(
            'label'   => 'Unique People Nominated',
            'value'   => number_format_i18n( $unique_people ),
            'context' => 'Actors, directors, writers, and craftspeople',
            'url'     => home_url( '/oscars/' ),
        );
    }

    // 4. Most nominated film without winning Best Picture.
    $bp_categories = "'BEST PICTURE','BEST MOTION PICTURE','OUTSTANDING PICTURE','OUTSTANDING PRODUCTION','OUTSTANDING MOTION PICTURE','UNIQUE AND ARTISTIC PICTURE'";
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $most_nom_no_bp = $wpdb->get_row(
        "SELECT film, film_id, COUNT(*) AS noms FROM {$table}
         WHERE film_id != '' AND film_id NOT IN (
             SELECT film_id FROM {$table} WHERE category IN ({$bp_categories}) AND winner = 1 AND film_id != ''
         )
         GROUP BY film_id ORDER BY noms DESC LIMIT 1",
        ARRAY_A
    );
    if ( ! empty( $most_nom_no_bp ) ) {
        $pool[] = array(
            'label'   => 'Most Nominated Without Best Picture',
            'value'   => number_format_i18n( intval( $most_nom_no_bp['noms'] ) ) . ' noms',
            'context' => trim( (string) $most_nom_no_bp['film'] ),
            'url'     => ! empty( $most_nom_no_bp['film_id'] ) ? home_url( '/oscars/title/' . $most_nom_no_bp['film_id'] . '/' ) : home_url( '/oscars/' ),
        );
    }

    // 5. Most nominated person without a win.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $most_nom_no_win = $wpdb->get_row(
        "SELECT name, COUNT(*) AS noms FROM {$table} WHERE name != '' GROUP BY name HAVING SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) = 0 ORDER BY noms DESC LIMIT 1",
        ARRAY_A
    );
    if ( ! empty( $most_nom_no_win ) ) {
        $pool[] = array(
            'label'   => 'Most Nominated Without a Win',
            'value'   => number_format_i18n( intval( $most_nom_no_win['noms'] ) ) . ' noms',
            'context' => trim( (string) $most_nom_no_win['name'] ),
            'url'     => home_url( '/oscars/' ),
        );
    }

    // 6. Director with most Best Picture wins.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $top_director = $wpdb->get_row(
        "SELECT name, COUNT(*) AS wins FROM {$table} WHERE canonical_category = 'DIRECTING' AND winner = 1 AND name != '' GROUP BY name ORDER BY wins DESC LIMIT 1",
        ARRAY_A
    );
    if ( ! empty( $top_director ) ) {
        $pool[] = array(
            'label'   => 'Most Best Director Wins',
            'value'   => number_format_i18n( intval( $top_director['wins'] ) ),
            'context' => trim( (string) $top_director['name'] ),
            'url'     => home_url( '/oscars/' ),
        );
    }

    // 7. Total ceremonies.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $total_ceremonies = intval( $wpdb->get_var( "SELECT COUNT(DISTINCT ceremony) FROM {$table}" ) );
    if ( $total_ceremonies > 0 ) {
        $pool[] = array(
            'label'   => 'Ceremonies in the Ledger',
            'value'   => number_format_i18n( $total_ceremonies ),
            'context' => 'From the 1st Academy Awards to the latest',
            'url'     => home_url( '/oscars/' ),
        );
    }

    // 8. Most competitive ceremony (highest ratio of nominees to winners).
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $competitive = $wpdb->get_row(
        "SELECT ceremony, COUNT(*) AS total, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS wins FROM {$table} GROUP BY ceremony HAVING wins > 0 ORDER BY (total / wins) DESC LIMIT 1",
        ARRAY_A
    );
    if ( ! empty( $competitive ) ) {
        $comp_ceremony = intval( $competitive['ceremony'] );
        $comp_label    = ( $aat && method_exists( $aat, 'ordinal' ) ) ? $aat->ordinal( $comp_ceremony ) : (string) $comp_ceremony;
        $pool[] = array(
            'label'   => 'Most Competitive Ceremony',
            'value'   => sprintf( '%s:%s', number_format_i18n( intval( $competitive['total'] ) ), number_format_i18n( intval( $competitive['wins'] ) ) ),
            'context' => sprintf( '%s ceremony (nominees to winners)', $comp_label ),
            'url'     => ( $aat && method_exists( $aat, 'get_ceremony_url' ) ) ? $aat->get_ceremony_url( $comp_ceremony ) : home_url( '/oscars/ceremony/' . $comp_ceremony . '/' ),
        );
    }

    // 9. Person with longest span between first and last nomination.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $longest_span = $wpdb->get_row(
        "SELECT name, MIN(ceremony) AS first_nom, MAX(ceremony) AS last_nom, (MAX(ceremony) - MIN(ceremony)) AS span FROM {$table} WHERE name != '' GROUP BY name HAVING COUNT(*) >= 2 ORDER BY span DESC LIMIT 1",
        ARRAY_A
    );
    if ( ! empty( $longest_span ) && intval( $longest_span['span'] ) > 0 ) {
        $pool[] = array(
            'label'   => 'Longest Career Span',
            'value'   => number_format_i18n( intval( $longest_span['span'] ) ) . ' ceremonies',
            'context' => trim( (string) $longest_span['name'] ),
            'url'     => home_url( '/oscars/' ),
        );
    }

    // 10. Category with most nominees per year (on average).
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $busiest_cat = $wpdb->get_row(
        "SELECT canonical_category, COUNT(*) AS total, COUNT(DISTINCT ceremony) AS ceremonies FROM {$table} WHERE canonical_category != '' GROUP BY canonical_category HAVING ceremonies >= 5 ORDER BY (total / ceremonies) DESC LIMIT 1",
        ARRAY_A
    );
    if ( ! empty( $busiest_cat ) ) {
        $cat_label = ( $aat && method_exists( $aat, 'format_category_display' ) ) ? $aat->format_category_display( $busiest_cat['canonical_category'] ) : $busiest_cat['canonical_category'];
        $avg = round( intval( $busiest_cat['total'] ) / max( 1, intval( $busiest_cat['ceremonies'] ) ), 1 );
        $pool[] = array(
            'label'   => 'Most Nominees Per Year (Avg)',
            'value'   => number_format( $avg, 1 ),
            'context' => $cat_label,
            'url'     => ( $aat && method_exists( $aat, 'get_category_url' ) ) ? $aat->get_category_url( $busiest_cat['canonical_category'] ) : home_url( '/oscars/' ),
        );
    }

    if ( count( $pool ) < 4 ) {
        return array();
    }

    // Deterministic daily rotation: pick 4 stats based on day of year.
    $pool_count = count( $pool );
    $start      = $day % $pool_count;
    $selected   = array();
    for ( $i = 0; $i < 4; $i++ ) {
        $selected[] = $pool[ ( $start + $i ) % $pool_count ];
    }

    set_transient( $cache_key, $selected, DAY_IN_SECONDS );

    return $selected;
}
}

if ( ! function_exists( 'lunara_invalidate_review_query_caches' ) ) {
function lunara_invalidate_review_query_caches( $post_id = 0 ) {
    if ( $post_id && get_post_type( $post_id ) !== 'review' ) {
        return;
    }

    delete_transient( 'lunara_home_oscars_snapshot_v1' );
    delete_transient( 'lunara_home_oscars_snapshot_v2' );
    delete_transient( 'lunara_home_database_spotlight_v1' );
    delete_transient( 'lunara_home_ledger_story_cards_v1' );
    delete_transient( 'lunara_home_ledger_story_cards_v2' );
    delete_transient( 'lunara_home_oscar_spotlight_v1' );
    delete_transient( 'lunara_home_deep_cuts_v1' );

    $counts = array( 2, 3, 4, 5, 6, 8, 9, 12 );
    $groups = array( 'featured_reviews', 'ledger_highlights', 'latest_reviews', 'home_hero_curated_reviews', 'home_featured_curated_reviews' );

    foreach ( $groups as $group ) {
        foreach ( $counts as $count ) {
            delete_transient( sprintf( 'lunara_%s_%d_v1', $group, $count ) );
        }
    }
}
}

/**
 * Featured review query.
 */
if ( ! function_exists( 'lunara_featured_reviews_query' ) ) {
function lunara_featured_reviews_query( $count = 8 ) {
    $post_ids = lunara_cached_review_ids(
        'featured_reviews',
        $count,
        array(
            'tag' => 'featured',
        )
    );

    return lunara_reviews_query_from_ids( $post_ids );
}
}

/**
 * Ledger highlights query.
 */
if ( ! function_exists( 'lunara_ledger_highlights_query' ) ) {
function lunara_ledger_highlights_query( $count = 6 ) {
    $post_ids = lunara_cached_review_ids(
        'ledger_highlights',
        $count,
        array(
            'tag' => 'oscar-ledger',
        )
    );

    return lunara_reviews_query_from_ids( $post_ids );
}
}

/**
 * Latest review query.
 */
if ( ! function_exists( 'lunara_latest_reviews_query' ) ) {
function lunara_latest_reviews_query( $count = 9 ) {
    $post_ids = lunara_cached_review_ids( 'latest_reviews', $count, array() );

    return lunara_reviews_query_from_ids( $post_ids );
}
}

/**
 * Latest reviews that are explicitly connected to an Oscars title page.
 */
if ( ! function_exists( 'lunara_oscars_linked_reviews_query' ) ) {
function lunara_oscars_linked_reviews_query( $count = 4 ) {
    $count = max( 1, intval( $count ) );

    // First priority: published reviews whose IMDb ID appears in the Academy Awards table.
    global $wpdb;
    $awards_table = $wpdb->prefix . 'academy_awards';
    $oscar_ids    = array();

    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$awards_table}'" ) === $awards_table ) {
        // Get IMDb IDs of reviewed Oscar-nominated films (rotate daily via OFFSET).
        $day_offset = intval( date( 'z' ) ) % 20; // rotate through the pool
        $oscar_imdb = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT pm.post_id
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_type = 'review' AND p.post_status = 'publish'
             INNER JOIN {$awards_table} aa ON aa.film_id = pm.meta_value AND aa.film_id != ''
             WHERE pm.meta_key = '_lunara_imdb_title_id' AND pm.meta_value != ''
             GROUP BY pm.post_id
             ORDER BY p.post_date DESC
             LIMIT %d OFFSET %d",
            $count * 3, // fetch extra so rotation works
            $day_offset
        ) );

        if ( ! empty( $oscar_imdb ) ) {
            $oscar_ids = array_map( 'intval', array_slice( $oscar_imdb, 0, $count ) );
        }
    }

    if ( ! empty( $oscar_ids ) ) {
        $query = new WP_Query(
            array(
                'post_type'              => 'review',
                'post_status'            => 'publish',
                'post__in'               => $oscar_ids,
                'posts_per_page'         => $count,
                'orderby'                => 'post__in',
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => true,
            )
        );
    } else {
        // Fallback: any published review with an IMDb ID.
        $query = new WP_Query(
            array(
                'post_type'              => 'review',
                'post_status'            => 'publish',
                'posts_per_page'         => $count,
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => true,
                'meta_query'             => array(
                    'relation' => 'AND',
                    array(
                        'key'     => '_lunara_imdb_title_id',
                        'compare' => 'EXISTS',
                    ),
                    array(
                        'key'     => '_lunara_imdb_title_id',
                        'value'   => '',
                        'compare' => '!=',
                    ),
                ),
            )
        );
    }

    if ( function_exists( 'lunara_prime_review_card_caches' ) && ! empty( $query->posts ) ) {
        lunara_prime_review_card_caches( wp_list_pluck( $query->posts, 'ID' ) );
    }

    return $query;
}
}

/**
 * True when rendering the dedicated Oscars portal front door.
 */
if ( ! function_exists( 'lunara_is_oscars_portal_page' ) ) {
function lunara_is_oscars_portal_page() {
    return ! is_admin() && is_page( 'oscars' );
}
}

/**
 * Build the dedicated Oscars portal markup for the /oscars/ page.
 */
if ( ! function_exists( 'lunara_render_oscars_portal_markup' ) ) {
function lunara_render_oscars_portal_markup() {
    $aat                = function_exists( 'lunara_get_oscars_plugin' ) ? lunara_get_oscars_plugin() : null;
    $snapshot           = function_exists( 'lunara_get_home_oscars_snapshot' ) ? lunara_get_home_oscars_snapshot() : array();
    $database_spotlight = function_exists( 'lunara_get_home_database_spotlight' ) ? lunara_get_home_database_spotlight() : array();
    $deep_cuts          = function_exists( 'lunara_get_home_deep_cuts' ) ? lunara_get_home_deep_cuts() : array();
    $linked_reviews     = function_exists( 'lunara_oscars_linked_reviews_query' ) ? lunara_oscars_linked_reviews_query( 4 ) : new WP_Query();

    $hero_kicker       = lunara_theme_mod_text( 'lunara_oscars_portal_kicker', 'The Lunara Oscar Ledger' );
    $hero_title        = lunara_theme_mod_text( 'lunara_oscars_portal_title', 'Academy Awards history, treated like a living editorial system.' );
    $hero_copy         = '';
    $explore_heading   = lunara_theme_mod_text( 'lunara_oscars_portal_explore_heading', 'Start anywhere in the ledger.' );
    $reviews_heading   = lunara_theme_mod_text( 'lunara_oscars_portal_reviews_heading', 'Reviews Inside the Ledger' );
    $deep_cuts_heading = lunara_theme_mod_text( 'lunara_oscars_portal_deep_cuts_heading', 'Oscar Deep Cuts' );

    $best_picture      = is_array( $snapshot['best_picture'] ?? null ) ? $snapshot['best_picture'] : array();
    $best_visual       = is_array( $best_picture['visual'] ?? null ) ? $best_picture['visual'] : array();
    $spotlights        = array_slice( (array) ( $snapshot['spotlights'] ?? array() ), 0, 6 );
    $title_cards       = array_slice( (array) ( $database_spotlight['cards'] ?? array() ), 0, 5 );

    $database_url      = ( $aat && method_exists( $aat, 'get_database_url' ) ) ? $aat->get_database_url() : trim( (string) ( $snapshot['database_url'] ?? ( $database_spotlight['database_url'] ?? home_url( '/oscars/' ) ) ) );
    $categories_url    = ( $aat && method_exists( $aat, 'get_categories_index_url' ) ) ? $aat->get_categories_index_url() : trim( (string) ( $snapshot['categories_url'] ?? ( $database_spotlight['categories_url'] ?? home_url( '/oscars/categories/' ) ) ) );
    $ceremony_url      = ( $aat && method_exists( $aat, 'get_ceremony_url' ) && ! empty( $snapshot['ceremony'] ) ) ? $aat->get_ceremony_url( intval( $snapshot['ceremony'] ) ) : trim( (string) ( $snapshot['ceremony_url'] ?? home_url( '/oscars/ceremony/' ) ) );
    $ceremony_label    = trim( (string) ( $snapshot['ceremony_label'] ?? 'Latest Ceremony' ) );
    $year_label        = trim( (string) ( $snapshot['year_label'] ?? '' ) );
    $about_url         = ( $aat && method_exists( $aat, 'get_about_url' ) ) ? $aat->get_about_url() : home_url( '/oscars/about/' );
    $ceremonies_url    = ( $aat && method_exists( $aat, 'get_ceremonies_index_url' ) ) ? $aat->get_ceremonies_index_url() : home_url( '/oscars/ceremonies/' );
    $hero_backdrop_url = trim( (string) ( $best_visual['backdrop_url'] ?? '' ) );
    $hero_style        = '';

    if ( '' !== $hero_backdrop_url ) {
        $hero_style = "background-image: linear-gradient(120deg, rgba(7,16,27,.92) 0%, rgba(7,16,27,.86) 48%, rgba(7,16,27,.97) 100%), url('" . esc_url( $hero_backdrop_url ) . "'); background-size: cover; background-position: center;";
    }

    $hero_title_card = array();
    if ( ! empty( $best_picture ) ) {
        $hero_title_card = array(
            'title'     => trim( (string) ( $best_picture['film'] ?? '' ) ),
            'url'       => trim( (string) ( $best_picture['film_url'] ?? $ceremony_url ) ),
            'visual'    => $best_visual,
            'eyebrow'   => 'Latest Best Picture',
            'meta_line' => trim( (string) $ceremony_label . ( $year_label !== '' ? ' / ' . $year_label : '' ) ),
            'body'      => trim( (string) ( $snapshot['summary'] ?? $snapshot['winner_record'] ?? '' ) ),
        );
    } elseif ( ! empty( $title_cards[0] ) ) {
        $hero_title_card = array(
            'title'     => trim( (string) ( $title_cards[0]['title'] ?? '' ) ),
            'url'       => trim( (string) ( $title_cards[0]['url'] ?? $database_url ) ),
            'visual'    => is_array( $title_cards[0]['visual'] ?? null ) ? $title_cards[0]['visual'] : array(),
            'eyebrow'   => 'Featured Portal Entry',
            'meta_line' => trim( (string) ( $title_cards[0]['categories_line'] ?? '' ) ),
            'body'      => '',
        );
    }

    $portal_stats = array(
        array(
            'label' => 'Ceremony',
            'value' => $ceremony_label,
        ),
        array(
            'label' => 'Year',
            'value' => $year_label !== '' ? $year_label : 'Live',
        ),
        array(
            'label' => 'Rows',
            'value' => number_format_i18n( intval( $database_spotlight['records_total'] ?? 0 ) ),
        ),
        array(
            'label' => 'Categories',
            'value' => number_format_i18n( intval( $database_spotlight['categories_total'] ?? 0 ) ),
        ),
    );

    // Backdrop images keyed by portal card â€” iconic Oscar titles.
    $portal_backdrop_map = array(
        'Ceremonies' => 'tt7286456',  // Joker
        'Categories' => 'tt1375666',  // Inception
        'Ledger'     => 'tt0111161',  // The Shawshank Redemption
        'About'      => 'tt0068646',  // The Godfather
    );
    $portal_backdrops = array();
    if ( class_exists( 'Academy_Awards_Table' ) ) {
        $aat_inst = Academy_Awards_Table::get_instance();
        if ( $aat_inst && method_exists( $aat_inst, 'get_title_visual_package' ) ) {
            foreach ( $portal_backdrop_map as $key => $imdb ) {
                $vis = $aat_inst->get_title_visual_package( $imdb, 'large' );
                $portal_backdrops[ $key ] = $vis['backdrop_url'] ?? '';
            }
        }
    }

    $portal_links = array(
        array(
            'kicker'   => 'Ceremonies',
            'title'    => 'Ceremony Archive',
            'copy'     => '',
            'url'      => $ceremonies_url,
            'backdrop' => $portal_backdrops['Ceremonies'] ?? '',
        ),
        array(
            'kicker'   => 'Categories',
            'title'    => 'Category History',
            'copy'     => '',
            'url'      => $categories_url,
            'backdrop' => $portal_backdrops['Categories'] ?? '',
        ),
        array(
            'kicker'   => 'Ledger',
            'title'    => 'Full Ledger',
            'copy'     => '',
            'url'      => $database_url,
            'backdrop' => $portal_backdrops['Ledger'] ?? '',
        ),
        array(
            'kicker'   => 'About',
            'title'    => 'Ledger Method',
            'copy'     => '',
            'url'      => $about_url,
            'backdrop' => $portal_backdrops['About'] ?? '',
        ),
    );

    ob_start();
    ?>
    <main id="primary" class="site-main lunara-oscars-portal">
        <?php if ( empty( $snapshot ) && empty( $database_spotlight ) ) : ?>
            <section class="lunara-home-section lunara-archive-hero">
                <p class="lunara-archive-hero-kicker"><?php echo esc_html( $hero_kicker ); ?></p>
                <h1 class="lunara-archive-hero-title"><?php echo esc_html( get_the_title() ); ?></h1>
            </section>
        <?php else : ?>
            <section class="lunara-home-section lunara-oscars-portal-hero"<?php if ( '' !== $hero_style ) : ?> style="<?php echo esc_attr( $hero_style ); ?>"<?php endif; ?>>
                <div class="lunara-oscars-portal-hero-grid">
                    <div class="lunara-oscars-portal-copy">
                        <p class="lunara-home-section-kicker"><?php echo esc_html( $hero_kicker ); ?></p>
                        <h1 class="lunara-home-hero-title"><?php echo esc_html( $hero_title ); ?></h1>
                        <?php if ( '' !== trim( (string) $hero_copy ) ) : ?>
                            <p class="lunara-home-hero-copy"><?php echo esc_html( $hero_copy ); ?></p>
                        <?php endif; ?>

                        <div class="lunara-oscars-portal-actions">
                            <a class="lunara-button lunara-button-primary" href="<?php echo esc_url( $ceremony_url ); ?>">Latest Ceremony</a>
                            <a class="lunara-button lunara-button-secondary" href="<?php echo esc_url( $database_url ); ?>">Open Full Ledger</a>
                            <a class="lunara-button-ghost" href="<?php echo esc_url( $categories_url ); ?>">Browse Categories</a>
                        </div>

                        <div class="lunara-oscars-portal-stat-grid">
                            <?php foreach ( $portal_stats as $stat ) : ?>
                                <div class="lunara-oscars-portal-stat">
                                    <span class="lunara-oscars-portal-stat-label"><?php echo esc_html( $stat['label'] ); ?></span>
                                    <strong class="lunara-oscars-portal-stat-value"><?php echo esc_html( $stat['value'] ); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ( ! empty( $hero_title_card ) ) : ?>
                        <a class="lunara-oscars-portal-feature-card" href="<?php echo esc_url( $hero_title_card['url'] ?? $database_url ); ?>">
                            <div class="lunara-oscars-portal-feature-poster">
                                <?php if ( ! empty( $hero_title_card['visual']['poster_html'] ) ) : ?>
                                    <?php echo $hero_title_card['visual']['poster_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php elseif ( ! empty( $hero_title_card['visual']['poster_url'] ) ) : ?>
                                    <img src="<?php echo esc_url( $hero_title_card['visual']['poster_url'] ); ?>" alt="<?php echo esc_attr( $hero_title_card['title'] ); ?>" loading="lazy" decoding="async" />
                                <?php elseif ( ! empty( $hero_title_card['visual']['card_fallback_html'] ) ) : ?>
                                    <?php echo $hero_title_card['visual']['card_fallback_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php else : ?>
                                    <div class="aat-filmography-poster-placeholder"><div class="aat-fallback-inner"><div class="aat-fallback-kicker">Oscar Portal</div><div class="aat-fallback-title small"><?php echo esc_html( $hero_title_card['title'] ); ?></div></div></div>
                                <?php endif; ?>
                            </div>
                            <div class="lunara-oscars-portal-feature-copy">
                                <p class="lunara-oscars-portal-feature-kicker"><?php echo esc_html( $hero_title_card['eyebrow'] ); ?></p>
                                <h2><?php echo esc_html( $hero_title_card['title'] ); ?></h2>
                                <?php if ( '' !== trim( (string) $hero_title_card['meta_line'] ) ) : ?>
                                    <p class="lunara-oscars-portal-feature-meta"><?php echo esc_html( $hero_title_card['meta_line'] ); ?></p>
                                <?php endif; ?>
                                <?php if ( '' !== trim( (string) $hero_title_card['body'] ) ) : ?>
                                    <p class="lunara-oscars-portal-feature-body"><?php echo esc_html( $hero_title_card['body'] ); ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
            </section>

            <section class="lunara-home-section lunara-oscars-portal-links-section">
                <div class="lunara-home-section-header">
                    <div>
                        <p class="lunara-home-section-kicker">Explore the Portal</p>
                        <h2 class="lunara-home-section-title"><?php echo esc_html( $explore_heading ); ?></h2>
                    </div>
                </div>

                <div class="lunara-oscars-portal-link-grid">
                    <?php foreach ( $portal_links as $portal_link ) :
                        $bd_url   = trim( (string) ( $portal_link['backdrop'] ?? '' ) );
                        $bd_style = '' !== $bd_url ? 'background-image:url(' . esc_url( $bd_url ) . ')' : '';
                        $bd_class = '' !== $bd_url ? ' has-backdrop' : '';
                    ?>
                        <a class="lunara-oscars-portal-link-card<?php echo $bd_class; ?>" href="<?php echo esc_url( $portal_link['url'] ); ?>"<?php if ( '' !== $bd_style ) : ?> style="<?php echo esc_attr( $bd_style ); ?>"<?php endif; ?>>
                            <p class="lunara-oscars-portal-link-kicker"><?php echo esc_html( $portal_link['kicker'] ); ?></p>
                            <h3><?php echo esc_html( $portal_link['title'] ); ?></h3>
                            <?php if ( '' !== trim( (string) $portal_link['copy'] ) ) : ?>
                                <p><?php echo esc_html( $portal_link['copy'] ); ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ( ! empty( $spotlights ) ) : ?>
                <section class="lunara-home-section lunara-oscars-portal-spotlights">
                    <div class="lunara-home-section-header">
                        <div>
                            <p class="lunara-home-section-kicker"><?php echo esc_html( $ceremony_label ); ?></p>
                            <h2 class="lunara-home-section-title">Latest Ceremony, category by category.</h2>
                        </div>
                    </div>

                    <div class="lunara-oscars-portal-spotlight-grid">
                        <?php foreach ( $spotlights as $spotlight ) :
                            $sl_visual = is_array( $spotlight['visual'] ?? null ) ? $spotlight['visual'] : array();
                            $sl_winner = intval( $spotlight['winner'] ?? 0 );
                        ?>
                            <a class="lunara-oscars-portal-spotlight-card<?php echo $sl_winner ? ' is-winner' : ''; ?>" href="<?php echo esc_url( $spotlight['url'] ?? $database_url ); ?>">
                                <?php if ( ! empty( $sl_visual['poster_html'] ) ) : ?>
                                    <div class="lunara-oscars-spotlight-poster">
                                        <?php echo $sl_visual['poster_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    </div>
                                <?php elseif ( ! empty( $sl_visual['poster_url'] ) ) : ?>
                                    <div class="lunara-oscars-spotlight-poster">
                                        <img src="<?php echo esc_url( $sl_visual['poster_url'] ); ?>" alt="<?php echo esc_attr( $spotlight['primary_label'] ?? '' ); ?>" loading="lazy" decoding="async" />
                                    </div>
                                <?php else : ?>
                                    <div class="lunara-oscars-spotlight-poster lunara-oscars-spotlight-poster--fallback">
                                        <span><?php echo esc_html( $spotlight['primary_label'] ?? '' ); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="lunara-oscars-spotlight-card-copy">
                                    <p class="lunara-oscars-portal-spotlight-category"><?php echo esc_html( $spotlight['category_label'] ?? 'Category' ); ?></p>
                                    <h3><?php echo esc_html( $spotlight['primary_label'] ?? $spotlight['film'] ?? '' ); ?></h3>
                                    <?php if ( ! empty( $spotlight['secondary_label'] ) ) : ?>
                                        <p class="lunara-oscars-portal-spotlight-secondary"><?php echo esc_html( $spotlight['secondary_label'] ); ?></p>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $spotlight['year'] ) ) : ?>
                                        <p class="lunara-oscars-portal-spotlight-meta"><?php echo esc_html( $spotlight['year'] ); ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ( ! empty( $title_cards ) ) : ?>
                <section class="lunara-home-section lunara-oscars-portal-titles">
                    <div class="lunara-home-section-header">
                        <div>
                            <p class="lunara-home-section-kicker">Poster-Led Entry Points</p>
                            <h2 class="lunara-home-section-title">Open the ledger through the films themselves.</h2>
                        </div>
                    </div>

                    <div class="lunara-oscars-portal-title-grid">
                        <?php foreach ( $title_cards as $card ) : ?>
                            <?php $card_visual = is_array( $card['visual'] ?? null ) ? $card['visual'] : array(); ?>
                            <a class="lunara-oscars-portal-title-card" href="<?php echo esc_url( $card['url'] ?? $database_url ); ?>">
                                <div class="lunara-oscars-portal-title-media">
                                    <?php if ( ! empty( $card_visual['poster_html'] ) ) : ?>
                                        <?php echo $card_visual['poster_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <?php elseif ( ! empty( $card_visual['poster_url'] ) ) : ?>
                                        <img src="<?php echo esc_url( $card_visual['poster_url'] ); ?>" alt="<?php echo esc_attr( $card['title'] ?? 'Oscar title' ); ?>" loading="lazy" decoding="async" />
                                    <?php elseif ( ! empty( $card_visual['card_fallback_html'] ) ) : ?>
                                        <?php echo $card_visual['card_fallback_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <?php else : ?>
                                        <div class="aat-filmography-poster-placeholder"><div class="aat-fallback-inner"><div class="aat-fallback-kicker">Oscar Title</div><div class="aat-fallback-title small"><?php echo esc_html( $card['title'] ?? '' ); ?></div></div></div>
                                    <?php endif; ?>
                                </div>
                                <div class="lunara-oscars-portal-title-copy">
                                    <h3><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
                                    <?php if ( ! empty( $card['year'] ) ) : ?>
                                        <p class="lunara-oscars-portal-title-year"><?php echo esc_html( $card['year'] ); ?></p>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $card['categories_line'] ) ) : ?>
                                        <p class="lunara-oscars-portal-title-line"><?php echo esc_html( $card['categories_line'] ); ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ( $linked_reviews instanceof WP_Query && $linked_reviews->have_posts() ) : ?>
                <section class="lunara-home-section lunara-oscars-portal-reviews">
                    <div class="lunara-home-section-header">
                        <div>
                            <p class="lunara-home-section-kicker">Criticism Meets the Ledger</p>
                            <h2 class="lunara-home-section-title"><?php echo esc_html( $reviews_heading ); ?></h2>
                        </div>
                    </div>

                    <div class="lunara-review-grid lunara-review-archive-grid">
                        <?php while ( $linked_reviews->have_posts() ) : $linked_reviews->the_post(); ?>
                            <?php echo lunara_render_review_grid_card( get_the_ID() ); ?>
                        <?php endwhile; ?>
                    </div>
                </section>
                <?php wp_reset_postdata(); ?>
            <?php endif; ?>

            <?php if ( ! empty( $deep_cuts ) ) : ?>
                <section class="lunara-home-section lunara-oscars-portal-deep-cuts">
                    <div class="lunara-home-section-header">
                        <div>
                            <p class="lunara-home-section-kicker">Rotating Stats</p>
                            <h2 class="lunara-home-section-title"><?php echo esc_html( $deep_cuts_heading ); ?></h2>
                        </div>
                    </div>

                    <div class="lunara-oscars-portal-facts-grid">
                        <?php foreach ( $deep_cuts as $cut ) : ?>
                            <a class="lunara-oscars-portal-fact-card" href="<?php echo esc_url( $cut['url'] ?? $database_url ); ?>">
                                <p class="lunara-oscars-portal-fact-label"><?php echo esc_html( $cut['label'] ?? '' ); ?></p>
                                <strong class="lunara-oscars-portal-fact-value"><?php echo esc_html( $cut['value'] ?? '' ); ?></strong>
                                <?php if ( ! empty( $cut['context'] ) ) : ?>
                                    <p class="lunara-oscars-portal-fact-context"><?php echo esc_html( $cut['context'] ); ?></p>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    <?php

    return trim( (string) ob_get_clean() );
}
}

/**
 * Replace the default page content with the custom Oscars portal.
 */
if ( ! function_exists( 'lunara_replace_oscars_portal_content' ) ) {
function lunara_replace_oscars_portal_content( $content ) {
    if ( ! lunara_is_oscars_portal_page() || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    return lunara_render_oscars_portal_markup();
}
}
// The dedicated page-oscars.php template is now the source of truth for /oscars/.
// Keep the helper available, but do not hijack the page content at runtime.

/**
 * Force the Oscars portal to render through a dedicated shell.
 *
 * Some theme/plugin combinations can bypass the normal singular content flow
 * and surface archive-style shells on the /oscars/ page. Rendering directly
 * here keeps the front door stable without touching the database layer.
 */
if ( ! function_exists( 'lunara_render_oscars_portal_direct' ) ) {
function lunara_render_oscars_portal_direct() {
    if ( ! lunara_is_oscars_portal_page() || is_admin() || is_feed() || is_embed() || is_preview() ) {
        return;
    }

    get_header();
    ?>
    <main id="main" class="site-main">
        <?php echo lunara_render_oscars_portal_markup(); ?>
    </main>
    <?php
    get_footer();
    exit;
}
}
// The dedicated page-oscars.php template is now the source of truth for /oscars/.
// Keep the helper available, but do not short-circuit normal template loading.

/**
 * Add a body class so the portal can be styled without relying on generic page shells.
 */
if ( ! function_exists( 'lunara_oscars_portal_body_class' ) ) {
function lunara_oscars_portal_body_class( $classes ) {
    if ( lunara_is_oscars_portal_page() ) {
        $classes[] = 'lunara-oscars-portal-page';
    }

    return $classes;
}
}
add_filter( 'body_class', 'lunara_oscars_portal_body_class' );
add_action( 'save_post_review', 'lunara_invalidate_review_query_caches', 50 );
add_action( 'deleted_post', 'lunara_invalidate_review_query_caches' );

/**
 * Footer fallback.
 */
if ( ! function_exists( 'lunara_footer_menu_fallback' ) ) {
function lunara_footer_menu_fallback() {
    echo '<ul class="lunara-footer-fallback">';
    echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/reviews/' ) ) . '">Reviews</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/oscars/' ) ) . '">Oscar Ledger</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">About</a></li>';
    echo '</ul>';
}
}

/**
 * â”€â”€ Lunara Custom Footer System â”€â”€
 * A three-zone branded footer that replaces Blocksy's native footer.
 */
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

if ( ! function_exists( 'lunara_render_custom_footer' ) ) {
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
}
if ( apply_filters( 'lunara_use_custom_footer', false ) ) {
add_action( 'wp_footer', 'lunara_render_custom_footer', 1 );

/* Suppress Blocksy's native footer only when the legacy Lunara footer is active. */
add_filter( 'blocksy:footer:has-widgets', '__return_false' );
add_filter( 'blocksy:builder:footer:enabled', '__return_false' );
}

if ( ! function_exists( 'lunara_hide_blocksy_footer_css' ) ) {
function lunara_hide_blocksy_footer_css() {
}
}
if ( apply_filters( 'lunara_use_custom_footer', false ) ) {
if ( ! has_action( 'wp_head', 'lunara_hide_blocksy_footer_css' ) ) {
    add_action( 'wp_head', 'lunara_hide_blocksy_footer_css', 100 );
}
}

/* Footer menu fallbacks */
if ( ! function_exists( 'lunara_footer_editorial_fallback' ) ) {
function lunara_footer_editorial_fallback() {
    // 2026-04-19: Hardcoded labels. Previously read from page_for_posts which
    // could produce "Reviews / Reviews / Journal / About" duplicates when the
    // posts page was titled "Reviews".
    $journal_url = function_exists( 'lunara_home_dispatch_archive_url' )
        ? lunara_home_dispatch_archive_url()
        : home_url( '/journal/' );

    echo '<ul class="menu">';
    echo '<li><a href="' . esc_url( home_url( '/reviews/' ) ) . '">Reviews</a></li>';
    echo '<li><a href="' . esc_url( $journal_url ) . '">Journal</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">About</a></li>';
    echo '</ul>';
}
}

/**
 * Dedupe ANY menu items in the footer-editorial / footer / etc. that share
 * the same URL â€” defensive against user-configured menus with accidental dupes.
 * Compares by trailing-slash-normalized URL.
 */
if ( ! function_exists( 'lunara_dedupe_footer_menu_items' ) ) {
function lunara_dedupe_footer_menu_items( $items, $args ) {
    if ( empty( $args ) || ! isset( $args->theme_location ) ) {
        return $items;
    }
    $footer_locations = array( 'footer', 'footer-editorial', 'footer-oscars', 'footer-utility' );
    if ( ! in_array( $args->theme_location, $footer_locations, true ) ) {
        return $items;
    }

    $seen = array();
    $out  = array();
    foreach ( $items as $item ) {
        $key = untrailingslashit( strtolower( (string) $item->url ) );
        if ( $key === '' || isset( $seen[ $key ] ) ) {
            continue;
        }
        $seen[ $key ] = true;
        $out[] = $item;
    }
    return $out;
}
add_filter( 'wp_nav_menu_objects', 'lunara_dedupe_footer_menu_items', 20, 2 );
}

/**
 * Inject "Journal" into the primary nav between Reviews and Oscars,
 * unless the user has already added it to their menu.
 *
 * Customizer/Menu admin can be flaky â€” this gives the user a code-side
 * guarantee that Journal appears in the top nav.
 */
if ( ! function_exists( 'lunara_inject_journal_into_primary_menu' ) ) {
function lunara_inject_journal_into_primary_menu( $items, $args ) {
    if ( empty( $args ) || ! isset( $args->theme_location ) ) {
        return $items;
    }
    if ( 'primary' !== $args->theme_location ) {
        return $items;
    }

    // If Journal is already present (any item URL contains /journal), bail.
    foreach ( $items as $item ) {
        if ( false !== strpos( (string) $item->url, '/journal' ) ) {
            return $items;
        }
    }

    $journal_url = home_url( '/journal/' );

    // Build a virtual menu item.
    $journal_item                         = new stdClass();
    $journal_item->ID                     = 99999991;
    $journal_item->db_id                  = 99999991;
    $journal_item->menu_item_parent       = 0;
    $journal_item->object_id              = 99999991;
    $journal_item->object                 = 'custom';
    $journal_item->type                   = 'custom';
    $journal_item->type_label             = 'Custom Link';
    $journal_item->title                  = __( 'Journal', 'lunara-film' );
    $journal_item->url                    = $journal_url;
    $journal_item->target                 = '';
    $journal_item->attr_title             = '';
    $journal_item->description            = '';
    $journal_item->classes                = array( 'menu-item', 'menu-item-type-custom', 'menu-item-injected-journal' );
    $journal_item->xfn                    = '';
    $journal_item->current                = is_post_type_archive( 'journal' ) || is_singular( 'journal' ) || is_tax( 'journal_type' );
    $journal_item->current_item_ancestor  = false;
    $journal_item->current_item_parent    = false;

    // Insert immediately after the first item whose URL contains /reviews.
    // If no Reviews item exists, append to the end.
    $insert_after = -1;
    foreach ( $items as $idx => $item ) {
        if ( false !== strpos( (string) $item->url, '/reviews' ) ) {
            $insert_after = $idx;
            break;
        }
    }

    if ( $insert_after >= 0 ) {
        array_splice( $items, $insert_after + 1, 0, array( $journal_item ) );
    } else {
        $items[] = $journal_item;
    }

    // Renumber menu_order so positioning is consistent.
    foreach ( $items as $idx => $i ) {
        if ( is_object( $i ) ) {
            $i->menu_order = $idx + 1;
        }
    }

    return $items;
}
add_filter( 'wp_nav_menu_objects', 'lunara_inject_journal_into_primary_menu', 10, 2 );
}

if ( ! function_exists( 'lunara_footer_oscars_fallback' ) ) {
function lunara_footer_oscars_fallback() {
    echo '<ul class="menu">';
    echo '<li><a href="' . esc_url( home_url( '/oscars/' ) ) . '">Ledger</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/oscars/categories/' ) ) . '">Categories</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/oscars/ceremonies/' ) ) . '">Ceremonies</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/oscars/about/' ) ) . '">About the Ledger</a></li>';
    echo '</ul>';
}
}

if ( ! function_exists( 'lunara_footer_utility_fallback' ) ) {
function lunara_footer_utility_fallback() {
    echo '<ul class="menu">';
    echo '<li><a href="' . esc_url( home_url( '/?s=' ) ) . '">Search</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/contact/' ) ) . '">Contact</a></li>';
    echo '<li><a href="' . esc_url( get_feed_link() ) . '">RSS</a></li>';
    echo '</ul>';
}
}

/**
 * Map primary-nav utility paths to reliable fallback labels.
 */
if ( ! function_exists( 'lunara_primary_menu_fallback_label_for_path' ) ) {
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
}

/**
 * Supply readable labels when a primary-nav item is configured as icon-only.
 */
if ( ! function_exists( 'lunara_primary_menu_item_title_fallback' ) ) {
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
}
add_filter( 'nav_menu_item_title', 'lunara_primary_menu_item_title_fallback', 10, 4 );

/**
 * Normalize icon-only primary-menu items before the walker renders them.
 */
if ( ! function_exists( 'lunara_primary_menu_object_title_fallback' ) ) {
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
}
add_filter( 'wp_nav_menu_objects', 'lunara_primary_menu_object_title_fallback', 10, 2 );

/**
 * Ensure icon-only primary menu items still output a visible text label.
 */
if ( ! function_exists( 'lunara_primary_menu_start_el_fallback' ) ) {
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
}
add_filter( 'walker_nav_menu_start_el', 'lunara_primary_menu_start_el_fallback', 10, 4 );

/**
 * Review metadata prepended above single review content.
 */
if ( ! function_exists( 'lunara_prepend_review_metadata' ) ) {
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
 * Keep Jetpack/CDN card artwork crisp without serving every thumbnail at
 * print-quality weight on archive and homepage surfaces.
 */
if ( ! function_exists( 'lunara_normalize_cdn_image_quality_value' ) ) {
	function lunara_normalize_cdn_image_quality_value( $value ) {
		if ( ! is_string( $value ) || '' === $value || false === strpos( $value, 'quality=' ) ) {
			return $value;
		}

		$target_quality = (int) apply_filters( 'lunara_frontend_cdn_image_quality', 86 );
		$target_quality = max( 70, min( 92, $target_quality ) );

		return preg_replace_callback(
			'/quality=(\d{2,3})/i',
			static function ( $matches ) use ( $target_quality ) {
				$current_quality = isset( $matches[1] ) ? (int) $matches[1] : $target_quality;

				return $current_quality > $target_quality
					? 'quality=' . $target_quality
					: $matches[0];
			},
			$value
		);
	}
}

if ( ! function_exists( 'lunara_normalize_cdn_image_quality_attributes' ) ) {
	function lunara_normalize_cdn_image_quality_attributes( $attr ) {
		if ( is_admin() || ! is_array( $attr ) ) {
			return $attr;
		}

		foreach ( $attr as $key => $value ) {
			if ( is_string( $value ) ) {
				$attr[ $key ] = lunara_normalize_cdn_image_quality_value( $value );
			}
		}

		return $attr;
	}
}
add_filter( 'wp_get_attachment_image_attributes', 'lunara_normalize_cdn_image_quality_attributes', 10000 );

if ( ! function_exists( 'lunara_normalize_cdn_image_quality_markup' ) ) {
	function lunara_normalize_cdn_image_quality_markup( $html ) {
		if ( is_admin() || ! is_string( $html ) ) {
			return $html;
		}

		return lunara_normalize_cdn_image_quality_value( $html );
	}
}
add_filter( 'wp_get_attachment_image', 'lunara_normalize_cdn_image_quality_markup', 10000 );
add_filter( 'post_thumbnail_html', 'lunara_normalize_cdn_image_quality_markup', 10000 );

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
 * Keep reviews inside the review lane instead of bleeding into standard post archives.
 */
if ( ! function_exists( 'lunara_separate_review_from_editorial_archives' ) ) {
    function lunara_separate_review_from_editorial_archives( $query ) {
        if ( is_admin() || ! ( $query instanceof WP_Query ) || ! $query->is_main_query() ) {
            return;
        }

        if ( $query->is_search() || $query->is_post_type_archive( 'review' ) || $query->is_singular( 'review' ) ) {
            return;
        }

        $requested_post_type = $query->get( 'post_type' );
        if ( 'review' === $requested_post_type || ( is_array( $requested_post_type ) && in_array( 'review', $requested_post_type, true ) ) ) {
            return;
        }

        if ( $query->is_home() || $query->is_category() || $query->is_tag() || $query->is_author() || $query->is_date() ) {
            $query->set( 'post_type', 'post' );
        }
    }
}

/**
 * Editorial controls for standard posts so the signal lane can be shaped per story.
 */
if ( ! function_exists( 'lunara_add_post_editorial_meta_box' ) ) {
    function lunara_enqueue_post_editorial_media_assets( $hook ) {
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || ! in_array( $screen->post_type, array( 'post', 'review', 'journal' ), true ) ) {
            return;
        }

        wp_enqueue_media();

        $css = '
        .lunara-media-control{margin:0 0 14px}
        .lunara-media-preview{margin:10px 0;padding:10px;border:1px solid rgba(0,0,0,.08);border-radius:8px;background:#fff}
        .lunara-media-preview img{display:block;max-width:100%;height:auto;border-radius:6px}
        .lunara-media-preview.is-empty{display:none}
        .lunara-media-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
        .lunara-media-url{margin-top:8px}
        .lunara-media-url input[readonly]{background:#f6f7f7}
        ';
        wp_add_inline_style( 'common', $css );

        $script = <<<'JS'
        (function($){
            function bindMediaControl($control){
                var frame = null;
                var $input = $control.find('.lunara-media-input');
                var $preview = $control.find('.lunara-media-preview');
                var $img = $control.find('.lunara-media-preview img');

                $control.on('click', '.lunara-media-select', function(e){
                    e.preventDefault();
                    if(frame){
                        frame.open();
                        return;
                    }
                    frame = wp.media({
                        title: 'Select image',
                        button: { text: 'Use this image' },
                        library: { type: 'image' },
                        multiple: false
                    });
                    frame.on('select', function(){
                        var attachment = frame.state().get('selection').first().toJSON();
                        $input.val(attachment.url).trigger('change');
                        $img.attr('src', attachment.url);
                        $preview.removeClass('is-empty').show();
                    });
                    frame.open();
                });

                $control.on('click', '.lunara-media-clear', function(e){
                    e.preventDefault();
                    $input.val('').trigger('change');
                    $img.attr('src', '');
                    $preview.addClass('is-empty').hide();
                });
            }

            $(function(){
                $('.lunara-media-control').each(function(){
                    bindMediaControl($(this));
                });
            });
        })(jQuery);
        JS;
        wp_add_inline_script( 'media-editor', $script, 'after' );
    }
    add_action( 'admin_enqueue_scripts', 'lunara_enqueue_post_editorial_media_assets' );

    function lunara_render_media_control( $args ) {
        $field_id    = isset( $args['field_id'] ) ? (string) $args['field_id'] : '';
        $field_name  = isset( $args['field_name'] ) ? (string) $args['field_name'] : $field_id;
        $label       = isset( $args['label'] ) ? (string) $args['label'] : '';
        $value       = isset( $args['value'] ) ? (string) $args['value'] : '';
        $description = isset( $args['description'] ) ? (string) $args['description'] : '';
        $preview_cls = '' === $value ? 'lunara-media-preview is-empty' : 'lunara-media-preview';
        ?>
        <div class="lunara-media-control">
            <label for="<?php echo esc_attr( $field_id ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label><br>
            <div class="<?php echo esc_attr( $preview_cls ); ?>">
                <img src="<?php echo esc_url( $value ); ?>" alt="">
            </div>
            <div class="lunara-media-actions">
                <button type="button" class="button button-secondary lunara-media-select">Select image</button>
                <button type="button" class="button button-link-delete lunara-media-clear">Clear</button>
            </div>
            <div class="lunara-media-url">
                <input type="url" class="lunara-media-input" name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $value ); ?>" style="width:100%;" readonly>
            </div>
            <?php if ( '' !== $description ) : ?>
                <small><?php echo esc_html( $description ); ?></small>
            <?php endif; ?>
        </div>
        <?php
    }

    function lunara_add_review_editorial_meta_box() {
        add_meta_box(
            'lunara_review_editorial_controls',
            'Lunara Review Controls',
            'lunara_review_editorial_meta_callback',
            'review',
            'side',
            'high'
        );
    }
    add_action( 'add_meta_boxes', 'lunara_add_review_editorial_meta_box' );

    function lunara_review_editorial_meta_callback( $post ) {
        wp_nonce_field( 'lunara_review_editorial_controls_nonce', 'lunara_review_editorial_controls_nonce' );

        $lane_label      = get_post_meta( $post->ID, '_lunara_review_lane_label_override', true );
        $standfirst      = get_post_meta( $post->ID, '_lunara_review_standfirst', true );
        $pull_quote      = get_post_meta( $post->ID, '_lunara_pull_quote', true );
        if ( '' === $pull_quote ) {
            $pull_quote = get_post_meta( $post->ID, '_lunara_review_pull_quote', true );
        }
        $card_image      = get_post_meta( $post->ID, '_lunara_review_card_image', true );
        $hide_standfirst = get_post_meta( $post->ID, '_lunara_review_hide_standfirst', true );
        $archive_label   = get_post_meta( $post->ID, '_lunara_review_archive_cta_label', true );
        $archive_url     = get_post_meta( $post->ID, '_lunara_review_archive_url_override', true );
        $hide_where      = get_post_meta( $post->ID, '_lunara_review_hide_where_card', true );
        $hide_details    = get_post_meta( $post->ID, '_lunara_review_hide_details_card', true );
        ?>
        <p>
            <label for="lunara_review_lane_label_override"><strong>Lane Label Override</strong></label><br>
            <input type="text" name="lunara_review_lane_label_override" id="lunara_review_lane_label_override" value="<?php echo esc_attr( $lane_label ); ?>" style="width:100%;">
            <small>Replaces the automatic `Lunara Review` label above the title.</small>
        </p>
        <p>
            <label for="lunara_review_standfirst"><strong>Standfirst Override</strong></label><br>
            <textarea name="lunara_review_standfirst" id="lunara_review_standfirst" rows="4" style="width:100%;"><?php echo esc_textarea( $standfirst ); ?></textarea>
            <small>Use this instead of the regular excerpt when you want exact top-of-page language.</small>
        </p>
        <p>
            <label for="lunara_pull_quote"><strong>Card Pull Quote</strong></label><br>
            <textarea name="lunara_pull_quote" id="lunara_pull_quote" rows="4" style="width:100%;"><?php echo esc_textarea( $pull_quote ); ?></textarea>
            <small>Exact front-facing hook for review cards on the homepage, Reviews archive, and featured review cards. Aim for 40-55 chosen words; this field is not auto-trimmed by PHP.</small>
        </p>
        <?php
        if ( function_exists( 'lunara_render_media_control' ) ) {
            lunara_render_media_control(
                array(
                    'field_id'    => 'lunara_review_card_image',
                    'field_name'  => 'lunara_review_card_image',
                    'label'       => 'Card Image Override',
                    'value'       => (string) $card_image,
                    'description' => 'Optional portrait/card crop for homepage and Reviews archive cards. Leaves the review hero and Featured image untouched.',
                )
            );
        } else {
            ?>
            <p>
                <label for="lunara_review_card_image"><strong>Card Image Override</strong></label><br>
                <input type="url" name="lunara_review_card_image" id="lunara_review_card_image" value="<?php echo esc_attr( $card_image ); ?>" style="width:100%;">
                <small>Optional portrait/card crop for homepage and Reviews archive cards.</small>
            </p>
            <?php
        }
        ?>
        <p>
            <label>
                <input type="checkbox" name="lunara_review_hide_standfirst" value="1" <?php checked( $hide_standfirst, '1' ); ?>>
                <strong>Hide standfirst entirely</strong>
            </label><br>
            <small>Turns off the paragraph under the title.</small>
        </p>
        <hr>
        <p>
            <label for="lunara_review_archive_cta_label"><strong>Browse Reviews CTA Label</strong></label><br>
            <input type="text" name="lunara_review_archive_cta_label" id="lunara_review_archive_cta_label" value="<?php echo esc_attr( $archive_label ); ?>" style="width:100%;">
        </p>
        <p>
            <label for="lunara_review_archive_url_override"><strong>Browse Reviews CTA URL Override</strong></label><br>
            <input type="url" name="lunara_review_archive_url_override" id="lunara_review_archive_url_override" value="<?php echo esc_attr( $archive_url ); ?>" style="width:100%;">
            <small>Send the rail button somewhere more specific than the main reviews archive.</small>
        </p>
        <p>
            <label>
                <input type="checkbox" name="lunara_review_hide_where_card" value="1" <?php checked( $hide_where, '1' ); ?>>
                <strong>Hide Where to Watch card</strong>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="lunara_review_hide_details_card" value="1" <?php checked( $hide_details, '1' ); ?>>
                <strong>Hide Review Details card</strong>
            </label>
        </p>
        <hr>
        <?php
        $home_hero_featured     = get_post_meta( $post->ID, '_lunara_review_home_hero_featured', true );
        $home_hero_priority     = get_post_meta( $post->ID, '_lunara_review_home_hero_priority', true );
        $featured_shelf_enabled = get_post_meta( $post->ID, '_lunara_review_home_featured_shelf', true );
        $featured_shelf_priority = get_post_meta( $post->ID, '_lunara_review_home_featured_priority', true );
        ?>
        <p>
            <strong>Homepage Placement</strong><br>
            <small>Use these controls instead of manually pasting review IDs when you want a review surfaced in specific homepage lanes.</small>
        </p>
        <p>
            <label>
                <input type="checkbox" name="lunara_review_home_hero_featured" value="1" <?php checked( $home_hero_featured, '1' ); ?>>
                <strong>Top Homepage Showcase</strong>
            </label><br>
            <small>If checked, this review becomes eligible for the top homepage hero lane.</small>
        </p>
        <p>
            <label for="lunara_review_home_hero_priority"><strong>Top Showcase Priority</strong></label><br>
            <input type="number" min="1" max="99" name="lunara_review_home_hero_priority" id="lunara_review_home_hero_priority" value="<?php echo esc_attr( '' !== $home_hero_priority ? $home_hero_priority : '10' ); ?>" style="width:100%;">
            <small>Lower numbers appear first. Reviews with the same priority fall back to the newest publish date.</small>
        </p>
        <p>
            <label>
                <input type="checkbox" name="lunara_review_home_featured_shelf" value="1" <?php checked( $featured_shelf_enabled, '1' ); ?>>
                <strong>Featured Criticism Shelf</strong>
            </label><br>
            <small>If that lower homepage shelf is enabled, this review can be pulled into it.</small>
        </p>
        <p>
            <label for="lunara_review_home_featured_priority"><strong>Featured Shelf Priority</strong></label><br>
            <input type="number" min="1" max="99" name="lunara_review_home_featured_priority" id="lunara_review_home_featured_priority" value="<?php echo esc_attr( '' !== $featured_shelf_priority ? $featured_shelf_priority : '10' ); ?>" style="width:100%;">
            <small>Lower numbers appear first. This gives you a predictable shelf order without editing IDs by hand.</small>
        </p>
        <?php
    }

    function lunara_save_review_editorial_meta( $post_id ) {
        if ( ! isset( $_POST['lunara_review_editorial_controls_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['lunara_review_editorial_controls_nonce'], 'lunara_review_editorial_controls_nonce' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['lunara_review_standfirst'] ) ) {
            update_post_meta( $post_id, '_lunara_review_standfirst', sanitize_textarea_field( wp_unslash( $_POST['lunara_review_standfirst'] ) ) );
        }

        if ( isset( $_POST['lunara_pull_quote'] ) ) {
            update_post_meta( $post_id, '_lunara_pull_quote', sanitize_textarea_field( wp_unslash( $_POST['lunara_pull_quote'] ) ) );
        }

        if ( isset( $_POST['lunara_review_card_image'] ) ) {
            update_post_meta( $post_id, '_lunara_review_card_image', esc_url_raw( wp_unslash( $_POST['lunara_review_card_image'] ) ) );
        }

        foreach ( array( 'lunara_review_lane_label_override', 'lunara_review_archive_cta_label' ) as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
            }
        }

        if ( isset( $_POST['lunara_review_archive_url_override'] ) ) {
            update_post_meta( $post_id, '_lunara_review_archive_url_override', esc_url_raw( wp_unslash( $_POST['lunara_review_archive_url_override'] ) ) );
        }

        update_post_meta( $post_id, '_lunara_review_hide_standfirst', isset( $_POST['lunara_review_hide_standfirst'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_lunara_review_hide_where_card', isset( $_POST['lunara_review_hide_where_card'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_lunara_review_hide_details_card', isset( $_POST['lunara_review_hide_details_card'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_lunara_review_home_hero_featured', isset( $_POST['lunara_review_home_hero_featured'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_lunara_review_home_featured_shelf', isset( $_POST['lunara_review_home_featured_shelf'] ) ? '1' : '0' );

        $hero_priority = isset( $_POST['lunara_review_home_hero_priority'] ) ? absint( wp_unslash( $_POST['lunara_review_home_hero_priority'] ) ) : 10;
        if ( $hero_priority < 1 ) {
            $hero_priority = 10;
        }
        update_post_meta( $post_id, '_lunara_review_home_hero_priority', (string) min( 99, $hero_priority ) );

        $featured_priority = isset( $_POST['lunara_review_home_featured_priority'] ) ? absint( wp_unslash( $_POST['lunara_review_home_featured_priority'] ) ) : 10;
        if ( $featured_priority < 1 ) {
            $featured_priority = 10;
        }
        update_post_meta( $post_id, '_lunara_review_home_featured_priority', (string) min( 99, $featured_priority ) );
    }
    add_action( 'save_post_review', 'lunara_save_review_editorial_meta' );

    function lunara_add_post_editorial_meta_box() {
        // Registers on post, journal, AND review. Same editorial controls apply everywhere.
        foreach ( array( 'post', 'journal' ) as $pt ) {
            add_meta_box(
                'lunara_post_editorial_controls',
                'Lunara Editorial Controls',
                'lunara_post_editorial_meta_callback',
                $pt,
                'side',
                'high'
            );
        }
    }
    add_action( 'add_meta_boxes', 'lunara_add_post_editorial_meta_box' );

    function lunara_post_editorial_meta_callback( $post ) {
        wp_nonce_field( 'lunara_post_editorial_controls_nonce', 'lunara_post_editorial_controls_nonce' );

        $type_label_override = get_post_meta( $post->ID, '_lunara_post_type_label_override', true );
        $hero_image_url      = get_post_meta( $post->ID, '_lunara_post_hero_image_url', true );
        $hero_secondary_url  = get_post_meta( $post->ID, '_lunara_post_hero_secondary_image_url', true );
        $hero_media_layout   = get_post_meta( $post->ID, '_lunara_post_hero_media_layout', true );
        $standfirst          = get_post_meta( $post->ID, '_lunara_post_standfirst', true );
        $hide_standfirst     = get_post_meta( $post->ID, '_lunara_post_hide_standfirst', true );
        $hide_hero_media     = get_post_meta( $post->ID, '_lunara_post_hide_hero_media', true );
        $signal_note         = get_post_meta( $post->ID, '_lunara_post_signal_note', true );
        $archive_cta         = get_post_meta( $post->ID, '_lunara_post_archive_cta_label', true );
        $archive_url         = get_post_meta( $post->ID, '_lunara_post_archive_url_override', true );
        $details_kicker      = get_post_meta( $post->ID, '_lunara_post_details_kicker', true );
        $signal_kicker       = get_post_meta( $post->ID, '_lunara_post_signal_kicker', true );
        $category_override   = get_post_meta( $post->ID, '_lunara_post_category_line_override', true );
        $tag_override        = get_post_meta( $post->ID, '_lunara_post_tag_line_override', true );
        $related_kicker      = get_post_meta( $post->ID, '_lunara_post_related_kicker', true );
        $related_heading     = get_post_meta( $post->ID, '_lunara_post_related_heading', true );
        $related_copy        = get_post_meta( $post->ID, '_lunara_post_related_copy', true );
        $related_button      = get_post_meta( $post->ID, '_lunara_post_related_button_label', true );
        $related_url         = get_post_meta( $post->ID, '_lunara_post_related_url_override', true );
        $hide_details        = get_post_meta( $post->ID, '_lunara_post_hide_details_card', true );
        $hide_signal         = get_post_meta( $post->ID, '_lunara_post_hide_signal_card', true );
        $hide_related        = get_post_meta( $post->ID, '_lunara_post_hide_related', true );
        ?>
        <p>
            <label for="lunara_post_type_label_override"><strong>Lane Label Override</strong></label><br>
            <input type="text" name="lunara_post_type_label_override" id="lunara_post_type_label_override" value="<?php echo esc_attr( $type_label_override ); ?>" style="width:100%;">
            <small>Replaces the automatic top label like News, Essay, or Journal.</small>
        </p>
        <?php
        lunara_render_media_control(
            array(
                'field_id'    => 'lunara_post_hero_image_url',
                'field_name'  => 'lunara_post_hero_image_url',
                'label'       => 'Hero Image Override',
                'value'       => $hero_image_url,
                'description' => 'Use a different image from the featured image when the page needs a more exact opener.',
            )
        );
        lunara_render_media_control(
            array(
                'field_id'    => 'lunara_post_hero_secondary_image_url',
                'field_name'  => 'lunara_post_hero_secondary_image_url',
                'label'       => 'Secondary Hero Image',
                'value'       => $hero_secondary_url,
                'description' => 'Optional second image for a stacked hero-media treatment.',
            )
        );
        ?>
        <p>
            <label for="lunara_post_hero_media_layout"><strong>Hero Media Layout</strong></label><br>
            <select name="lunara_post_hero_media_layout" id="lunara_post_hero_media_layout" style="width:100%;">
                <option value="" <?php selected( $hero_media_layout, '' ); ?>>Automatic</option>
                <option value="single" <?php selected( $hero_media_layout, 'single' ); ?>>Single image</option>
                <option value="stacked" <?php selected( $hero_media_layout, 'stacked' ); ?>>Stacked images</option>
            </select>
            <small>`Automatic` uses stacked mode when a secondary hero image is filled in.</small>
        </p>
        ?>
        <p>
            <label for="lunara_post_standfirst"><strong>Standfirst Override</strong></label><br>
            <textarea name="lunara_post_standfirst" id="lunara_post_standfirst" rows="4" style="width:100%;"><?php echo esc_textarea( $standfirst ); ?></textarea>
            <small>Use this instead of the regular excerpt when you want exact control.</small>
        </p>

        <p>
            <label>
                <input type="checkbox" name="lunara_post_hide_standfirst" value="1" <?php checked( $hide_standfirst, '1' ); ?>>
                <strong>Hide standfirst entirely</strong>
            </label><br>
            <small>If checked, the theme will not auto-show the excerpt at the top.</small>
        </p>
        <p>
            <label>
                <input type="checkbox" name="lunara_post_hide_hero_media" value="1" <?php checked( $hide_hero_media, '1' ); ?>>
                <strong>Hide hero media</strong>
            </label><br>
            <small>Useful when you want a text-led filing without the right-side image block.</small>
        </p>

        <p>
            <label for="lunara_post_signal_note"><strong>Signal Note Override</strong></label><br>
            <textarea name="lunara_post_signal_note" id="lunara_post_signal_note" rows="4" style="width:100%;"><?php echo esc_textarea( $signal_note ); ?></textarea>
            <small>This replaces the auto-generated note beneath the headline and inside the signal context rail.</small>
        </p>

        <p>
            <label for="lunara_post_archive_cta_label"><strong>Journal CTA Label</strong></label><br>
            <input type="text" name="lunara_post_archive_cta_label" id="lunara_post_archive_cta_label" value="<?php echo esc_attr( $archive_cta ); ?>" style="width:100%;">
            <small>Optional override for the archive-return link label.</small>
        </p>
        <p>
            <label for="lunara_post_archive_url_override"><strong>Journal CTA URL Override</strong></label><br>
            <input type="url" name="lunara_post_archive_url_override" id="lunara_post_archive_url_override" value="<?php echo esc_attr( $archive_url ); ?>" style="width:100%;">
            <small>Send the rail/archive CTA somewhere more specific than the main Journal route.</small>
        </p>

        <hr>
        <p>
            <label for="lunara_post_details_kicker"><strong>Article Details Kicker</strong></label><br>
            <input type="text" name="lunara_post_details_kicker" id="lunara_post_details_kicker" value="<?php echo esc_attr( $details_kicker ); ?>" style="width:100%;">
        </p>

        <p>
            <label for="lunara_post_signal_kicker"><strong>Signal Context Kicker</strong></label><br>
            <input type="text" name="lunara_post_signal_kicker" id="lunara_post_signal_kicker" value="<?php echo esc_attr( $signal_kicker ); ?>" style="width:100%;">
        </p>

        <p>
            <label for="lunara_post_category_line_override"><strong>Filed Under Override</strong></label><br>
            <input type="text" name="lunara_post_category_line_override" id="lunara_post_category_line_override" value="<?php echo esc_attr( $category_override ); ?>" style="width:100%;">
            <small>Replaces the automatic category line in the rail/meta.</small>
        </p>

        <p>
            <label for="lunara_post_tag_line_override"><strong>Threaded Through Override</strong></label><br>
            <input type="text" name="lunara_post_tag_line_override" id="lunara_post_tag_line_override" value="<?php echo esc_attr( $tag_override ); ?>" style="width:100%;">
        </p>
        <p>
            <label>
                <input type="checkbox" name="lunara_post_hide_details_card" value="1" <?php checked( $hide_details, '1' ); ?>>
                <strong>Hide Article Details card</strong>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="lunara_post_hide_signal_card" value="1" <?php checked( $hide_signal, '1' ); ?>>
                <strong>Hide Signal Context card</strong>
            </label>
        </p>

        <hr>
        <p>
            <label for="lunara_post_related_kicker"><strong>Related Section Kicker</strong></label><br>
            <input type="text" name="lunara_post_related_kicker" id="lunara_post_related_kicker" value="<?php echo esc_attr( $related_kicker ); ?>" style="width:100%;">
        </p>

        <p>
            <label for="lunara_post_related_heading"><strong>Related Section Heading</strong></label><br>
            <input type="text" name="lunara_post_related_heading" id="lunara_post_related_heading" value="<?php echo esc_attr( $related_heading ); ?>" style="width:100%;">
        </p>

        <p>
            <label for="lunara_post_related_copy"><strong>Related Section Copy</strong></label><br>
            <textarea name="lunara_post_related_copy" id="lunara_post_related_copy" rows="3" style="width:100%;"><?php echo esc_textarea( $related_copy ); ?></textarea>
        </p>

        <p>
            <label for="lunara_post_related_button_label"><strong>Related Button Label</strong></label><br>
            <input type="text" name="lunara_post_related_button_label" id="lunara_post_related_button_label" value="<?php echo esc_attr( $related_button ); ?>" style="width:100%;">
        </p>
        <p>
            <label for="lunara_post_related_url_override"><strong>Related Button URL Override</strong></label><br>
            <input type="url" name="lunara_post_related_url_override" id="lunara_post_related_url_override" value="<?php echo esc_attr( $related_url ); ?>" style="width:100%;">
            <small>Optional destination override for the lower section action.</small>
        </p>

        <p>
            <label>
                <input type="checkbox" name="lunara_post_hide_related" value="1" <?php checked( $hide_related, '1' ); ?>>
                <strong>Hide related section</strong>
            </label>
        </p>
        <?php
    }

    function lunara_save_post_editorial_meta( $post_id ) {
        if ( ! isset( $_POST['lunara_post_editorial_controls_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['lunara_post_editorial_controls_nonce'], 'lunara_post_editorial_controls_nonce' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $textareas = array(
            'lunara_post_standfirst',
            'lunara_post_signal_note',
            'lunara_post_related_copy',
        );

        foreach ( $textareas as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) ) );
            }
        }

        $text_fields = array(
            'lunara_post_type_label_override',
            'lunara_post_hero_media_layout',
            'lunara_post_archive_cta_label',
            'lunara_post_details_kicker',
            'lunara_post_signal_kicker',
            'lunara_post_category_line_override',
            'lunara_post_tag_line_override',
            'lunara_post_related_kicker',
            'lunara_post_related_heading',
            'lunara_post_related_button_label',
        );

        foreach ( $text_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta(
                    $post_id,
                    '_' . $field,
                    sanitize_text_field( wp_unslash( $_POST[ $field ] ) )
                );
            }
        }

        $url_fields = array(
            'lunara_post_hero_image_url',
            'lunara_post_hero_secondary_image_url',
            'lunara_post_archive_url_override',
            'lunara_post_related_url_override',
        );

        foreach ( $url_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta(
                    $post_id,
                    '_' . $field,
                    esc_url_raw( wp_unslash( $_POST[ $field ] ) )
                );
            }
        }

        update_post_meta(
            $post_id,
            '_lunara_post_hide_standfirst',
            isset( $_POST['lunara_post_hide_standfirst'] ) ? '1' : '0'
        );
        update_post_meta(
            $post_id,
            '_lunara_post_hide_hero_media',
            isset( $_POST['lunara_post_hide_hero_media'] ) ? '1' : '0'
        );
        update_post_meta(
            $post_id,
            '_lunara_post_hide_details_card',
            isset( $_POST['lunara_post_hide_details_card'] ) ? '1' : '0'
        );
        update_post_meta(
            $post_id,
            '_lunara_post_hide_signal_card',
            isset( $_POST['lunara_post_hide_signal_card'] ) ? '1' : '0'
        );
        update_post_meta(
            $post_id,
            '_lunara_post_hide_related',
            isset( $_POST['lunara_post_hide_related'] ) ? '1' : '0'
        );
    }
    add_action( 'save_post_post',    'lunara_save_post_editorial_meta' );
    add_action( 'save_post_journal', 'lunara_save_post_editorial_meta' );
}
add_action( 'pre_get_posts', 'lunara_separate_review_from_editorial_archives', 12 );

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
                    $kicker = __( 'Journal Route', 'lunara-film' );
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
if ( ! function_exists( 'lunara_output_carousel_controls_js' ) ) {
function lunara_output_carousel_controls_js() {
    if ( ! is_front_page() ) {
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
            if (!reduceMotion && autoplay > 0) {
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
                // Pause-on-hover ONLY for actual mouse pointers â€” never on touch,
                // because mobile taps fire synthetic pointerenter without a matching
                // pointerleave, which would freeze autoplay forever after the first tap.
                section.addEventListener('pointerenter', function (e) {
                    if (e.pointerType === 'mouse') stop();
                });
                section.addEventListener('pointerleave', function (e) {
                    if (e.pointerType === 'mouse') start();
                });
                section.addEventListener('focusin', stop);
                section.addEventListener('focusout', start);
                // Pause briefly while the user is actively swiping the track,
                // then resume after they let go.
                if (track) {
                    track.addEventListener('touchstart', stop, { passive: true });
                    track.addEventListener('touchend', start, { passive: true });
                }
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
}
add_action( 'wp_footer', 'lunara_output_carousel_controls_js', 99 );

/**
 * Hero cinema crossfade: auto-rotating poster hero.
 */
if ( ! function_exists( 'lunara_output_hero_cinema_js' ) ) {
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
}
add_action( 'wp_footer', 'lunara_output_hero_cinema_js', 100 );

/**
 * Review sidebar scroll-follow.
 * Blocksy's #main-container overflow:clip defeats CSS position:sticky.
 * This JS-based approach manually tracks scroll and fixes the sidebar.
 */
if ( ! function_exists( 'lunara_output_sidebar_scroll_follow_js' ) ) {
function lunara_output_sidebar_scroll_follow_js() {
    if ( ! is_singular( 'review' ) && ! ( is_single() && has_term( '', 'lunara_director' ) ) ) {
        return;
    }

    // CSS sticky/static rail behavior is safer than JS fixed-position following near the Debrief boundary.
    return;
    ?>
    <script>
    (function(){
        var sticky = document.querySelector('.lunara-review-single-rail-sticky');
        var rail   = document.querySelector('.lunara-review-single-rail');
        var grid   = document.querySelector('.lunara-review-single-body-grid');
        var stopAt = document.querySelector('.lunara-review-single-debrief-section');
        if (!sticky || !rail || !grid) return;

        var mq = window.matchMedia('(max-width: 900px)');
        if (mq.matches) return;

        var topGap   = 90;   /* px below viewport top */
        var ticking  = false;
        var lastY    = 0;

        /*
         * Use transform: translateY() instead of position:fixed.
         * This keeps the element in normal flow, avoiding Blocksy's
         * overflow:clip and ancestor-transform issues entirely.
         */
        function update() {
            ticking = false;

            /* Natural (un-translated) top of the sticky element */
            sticky.style.transform = '';               /* reset to measure natural position */
            var railRect   = rail.getBoundingClientRect();
            var stickyNat  = sticky.getBoundingClientRect();
            var gridRect   = grid.getBoundingClientRect();
            var stopRect   = stopAt ? stopAt.getBoundingClientRect() : null;
            var stickyH    = sticky.offsetHeight;
            var viewportSafeH = window.innerHeight - topGap - 32;
            var gridBottom = stopRect ? Math.min(gridRect.bottom, stopRect.top) : gridRect.bottom;

            if (stickyH >= viewportSafeH || gridRect.height <= stickyH + 32) {
                sticky.classList.remove('is-following', 'is-bottomed');
                sticky.style.transform = '';
                return;
            }

            /* 1. Sidebar top hasn't scrolled past the gap â€” stay put */
            if (stickyNat.top >= topGap) {
                sticky.classList.remove('is-following', 'is-bottomed');
                return;
            }

            /* How far we need to shift the element down */
            var shift = topGap - stickyNat.top;

            /* 2. Clamp so it doesn't overflow past the grid bottom */
            var maxShift = gridBottom - stickyNat.top - stickyH - 32;
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

        function onScroll() {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(update);
            }
        }

        window.addEventListener('scroll', onScroll, {passive: true});
        window.addEventListener('resize', function() {
            if (!mq.matches) update();
        });

        /* Initial call after layout settles */
        requestAnimationFrame(update);
    })();
    </script>
    <?php
}
}
add_action( 'wp_footer', 'lunara_output_sidebar_scroll_follow_js', 101 );

/**
 * Wave 2: Image fade-in on load.
 */
if ( ! function_exists( 'lunara_output_image_fadein_js' ) ) {
function lunara_output_image_fadein_js() {
    ?>
    <script>
    (function(){
        function markLoaded(img){img.classList.add('lunara-img-loaded');}
        function hydrateLazySource(img){
            var dataSrc=img.getAttribute('data-src')||img.getAttribute('data-lazy-src')||'';
            var dataSrcset=img.getAttribute('data-srcset')||img.getAttribute('data-lazy-srcset')||'';
            var currentSrc=img.getAttribute('src')||'';
            if(dataSrcset&&!img.getAttribute('srcset')){
                img.setAttribute('srcset',dataSrcset);
            }
            if(dataSrc&&(!currentSrc||currentSrc.indexOf('data:image/gif')===0)){
                img.setAttribute('src',dataSrc);
            }
        }
        function processImg(img){
            hydrateLazySource(img);
            if(img.complete&&img.naturalWidth>1){markLoaded(img);return;}
            img.addEventListener('load',function(){markLoaded(img);});
            img.addEventListener('error',function(){markLoaded(img);});
        }
        var sels='.lunara-review-grid-poster,.lunara-review-feature-image,.lunara-poster-card-image,.lunara-journal-home-card-image,.lunara-dispatch-archive-thumb,.lunara-dispatch-lead-image,.lunara-oscar-pick-card-image,.lunara-oscar-fact-card-poster-image,.lunara-home-pulse-poster,.aat-filmography-poster,.aat-entity-poster';
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
}
add_action( 'wp_footer', 'lunara_output_image_fadein_js', 100 );

/**
 * Wave 3: Scroll-triggered reveals.
 */
if ( ! function_exists( 'lunara_output_scroll_reveal_js' ) ) {
function lunara_output_scroll_reveal_js() {
    ?>
    <script>
    (function(){
        if(window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
        // Only run scroll reveals on the front page â€” skip portal, plugin, single review, and other pages
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
}
add_action( 'wp_footer', 'lunara_output_scroll_reveal_js', 101 );

// Sticky sidebar deferred to standalone theme (Tier 4).
// Blocksy's scroll container architecture defeats both CSS sticky and JS fixed positioning.
// The sidebar renders correctly in place; it just doesn't follow the reader yet.

/**
 * Wave 5: Oscar stats count-up animation.
 */
if ( ! function_exists( 'lunara_output_stats_countup_js' ) ) {
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
}
add_action( 'wp_footer', 'lunara_output_stats_countup_js', 102 );


/**
 * Oscar Lore â€” inline expansion interaction.
 *
 * Clicking a lore card on the homepage opens an expanded detail panel
 * in-place instead of navigating away. The panel injects dynamically
 * from the card's own DOM data, so no new markup in front-page.php.
 * Escape key, close button, and click-outside all dismiss.
 *
 * Front-page only.  Hooked to wp_footer at priority 100 so it runs
 * after the main carousel-controls script.
 *
 * @since Lunara 2026-04-20
 */
if ( ! function_exists( 'lunara_output_oscar_lore_interaction_js' ) ) {
function lunara_output_oscar_lore_interaction_js() {
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    <script>
    (function () {
        'use strict';

        var section = document.querySelector('.lunara-home-oscar-story-section');
        if (!section) return;
        var wrap  = section.querySelector('.lunara-ledger-carousel-wrap');
        var track = section.querySelector('[data-lunara-carousel-track]');
        if (!wrap || !track) return;

        var currentDetail = null;

        function getText(parent, selector) {
            var el = parent.querySelector(selector);
            return el ? (el.textContent || '').trim() : '';
        }

        function getHTML(parent, selector) {
            var el = parent.querySelector(selector);
            return el ? el.innerHTML : '';
        }

        function closeDetail() {
            if (!currentDetail) return;
            var detail = currentDetail;
            currentDetail = null;
            detail.classList.add('is-closing');
            section.classList.remove('has-expanded');
            setTimeout(function () {
                if (detail && detail.parentNode) {
                    detail.parentNode.removeChild(detail);
                }
            }, 280);
        }

        function openDetail(card) {
            if (currentDetail) closeDetail();

            var posterHTML = getHTML(card, '.lunara-lore-card-poster') || getHTML(card, '.lunara-ledger-story-poster');
            var eyebrow    = getText(card, '.lunara-ledger-story-year');
            var title      = getText(card, '.lunara-ledger-story-title');
            var meta       = getText(card, '.lunara-ledger-story-categories');
            var body       = getText(card, '.lunara-ledger-story-summary');
            var link       = card.querySelector('.lunara-ledger-story-link');
            var url        = link ? link.getAttribute('href') : '#';
            var backdrop   = (card.getAttribute('data-lunara-lore-backdrop') || '').trim();

            var detail = document.createElement('div');
            detail.className = 'lunara-lore-detail';
            detail.setAttribute('role', 'dialog');
            detail.setAttribute('aria-modal', 'true');
            detail.setAttribute('aria-label', title || 'Oscar lore detail');

            // Apply TMDB backdrop as cinematic background with strong overlay for legibility
            if (backdrop) {
                detail.classList.add('has-backdrop');
                detail.style.backgroundImage =
                    'linear-gradient(130deg, rgba(7,15,26,.86) 0%, rgba(7,15,26,.68) 45%, rgba(7,15,26,.94) 100%), ' +
                    'url("' + backdrop + '")';
                detail.style.backgroundSize = 'cover';
                detail.style.backgroundPosition = 'center';
            }

            var html = '';
            html += '<button type="button" class="lunara-lore-detail-close" aria-label="Close detail">\u2715</button>';
            html += '<div class="lunara-lore-detail-poster">' + posterHTML + '</div>';
            html += '<div class="lunara-lore-detail-copy">';
            if (eyebrow) html += '<p class="lunara-lore-detail-eyebrow">' + eyebrow + '</p>';
            if (title)   html += '<h3 class="lunara-lore-detail-title">' + title + '</h3>';
            if (meta)    html += '<p class="lunara-lore-detail-meta">' + meta + '</p>';
            if (body)    html += '<p class="lunara-lore-detail-body">' + body + '</p>';
            if (url && url !== '#') {
                html += '<a class="lunara-lore-detail-cta" href="' + url + '">Open in the Ledger \u2192</a>';
            }
            html += '</div>';
            detail.innerHTML = html;

            wrap.appendChild(detail);
            section.classList.add('has-expanded');
            currentDetail = detail;

            // Focus the close button for a11y
            var closeBtn = detail.querySelector('.lunara-lore-detail-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeDetail();
                });
                setTimeout(function () { closeBtn.focus(); }, 60);
            }
        }

        // Intercept lore card clicks
        track.addEventListener('click', function (e) {
            // Let modifier-key clicks open in new tab normally
            if (e.ctrlKey || e.metaKey || e.shiftKey) return;

            var link = e.target.closest('.lunara-lore-card .lunara-ledger-story-link');
            if (!link) return;

            e.preventDefault();
            var card = link.closest('.lunara-lore-card');
            if (card) openDetail(card);
        });

        // Escape to close
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && currentDetail) {
                closeDetail();
            }
        });

        // Click outside the detail to close
        document.addEventListener('click', function (e) {
            if (!currentDetail) return;
            if (currentDetail.contains(e.target)) return;
            // Ignore clicks that are already handled by track (new card click)
            if (track.contains(e.target)) return;
            closeDetail();
        });
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'lunara_output_oscar_lore_interaction_js', 100 );
}



/**
 * Register review post-type meta fields for the REST API.
 *
 * Without this, underscore-prefixed meta ("_lunara_score" etc.) is treated as
 * "protected" and hidden from /wp-json/wp/v2/review. With it, those fields
 * appear in the `meta` object of each response AND can be written via POST
 * using the same Application Password auth as the journal endpoint.
 *
 * @since Lunara 2026-04-20
 */
if ( ! function_exists( 'lunara_sanitize_review_score_meta' ) ) {
	/**
	 * Sanitize the review score meta when WordPress passes meta callback args.
	 *
	 * Core meta sanitizers may receive additional arguments beyond the raw value,
	 * so this wrapper keeps the callback compatible with register_post_meta().
	 *
	 * @param mixed $value Raw submitted score value.
	 * @return float|string
	 */
	function lunara_sanitize_review_score_meta( $value ) {
		if ( null === $value ) {
			return '';
		}

		if ( is_string( $value ) ) {
			$value = trim( $value );

			if ( '' === $value ) {
				return '';
			}
		}

		if ( '' === $value ) {
			return '';
		}

		$value = (float) $value;

		if ( $value < 0 ) {
			$value = 0.0;
		}

		if ( $value > 5 ) {
			$value = 5.0;
		}

		return $value;
	}
}

if ( ! function_exists( 'lunara_register_review_meta_for_rest' ) ) {
function lunara_register_review_meta_for_rest() {

	$core_fields = array(
		'_lunara_score'          => array( 'type' => 'number', 'sanitize' => 'lunara_sanitize_review_score_meta' ),
		'_lunara_year'           => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_director'       => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_imdb_id'        => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_imdb_title_id'  => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_where'          => array( 'type' => 'string', 'sanitize' => 'sanitize_textarea_field' ),
		'_lunara_theme_echo'     => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
	);

	$editorial_fields = array(
		'_lunara_pull_quote'                     => array( 'type' => 'string', 'sanitize' => 'sanitize_textarea_field' ),
		'_lunara_review_pull_quote'              => array( 'type' => 'string', 'sanitize' => 'sanitize_textarea_field' ),
		'_lunara_review_standfirst'              => array( 'type' => 'string', 'sanitize' => 'sanitize_textarea_field' ),
		'_lunara_review_card_image'              => array( 'type' => 'string', 'sanitize' => 'esc_url_raw' ),
		'_lunara_review_hero_banner'             => array( 'type' => 'string', 'sanitize' => 'esc_url_raw' ),
		'_lunara_review_hero_banner_caption'     => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_review_context_shot'            => array( 'type' => 'string', 'sanitize' => 'esc_url_raw' ),
		'_lunara_review_context_shot_caption'    => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_review_thematic_echo'           => array( 'type' => 'string', 'sanitize' => 'esc_url_raw' ),
		'_lunara_review_thematic_echo_caption'   => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_counter_program'                => array( 'type' => 'string', 'sanitize' => 'sanitize_textarea_field' ),
		'_lunara_craft_mirror'                   => array( 'type' => 'string', 'sanitize' => 'sanitize_textarea_field' ),
	);

	$override_fields = array(
		'_lunara_review_lane_label_override'     => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_review_archive_cta_label'       => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_review_archive_url_override'    => array( 'type' => 'string', 'sanitize' => 'esc_url_raw' ),
		'_lunara_review_hide_details_card'       => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_review_hide_standfirst'         => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_review_hide_where_card'         => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
	);

	$shared_fields = array(
		'_lunara_post_standfirst'                => array( 'type' => 'string', 'sanitize' => 'sanitize_textarea_field' ),
		'_lunara_post_type_label_override'       => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_post_archive_cta_label'         => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_post_archive_url_override'      => array( 'type' => 'string', 'sanitize' => 'esc_url_raw' ),
		'_lunara_post_category_line_override'    => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_post_details_kicker'            => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_post_hero_image_url'            => array( 'type' => 'string', 'sanitize' => 'esc_url_raw' ),
		'_lunara_post_hero_media_layout'         => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_post_hero_secondary_image_url'  => array( 'type' => 'string', 'sanitize' => 'esc_url_raw' ),
		'_lunara_post_hide_details_card'         => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_post_hide_hero_media'           => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_post_hide_related'              => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_post_hide_signal_card'          => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_post_hide_standfirst'           => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_post_related_button_label'      => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_post_related_copy'              => array( 'type' => 'string', 'sanitize' => 'sanitize_textarea_field' ),
		'_lunara_post_related_heading'           => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_post_related_kicker'            => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_post_related_url_override'      => array( 'type' => 'string', 'sanitize' => 'esc_url_raw' ),
		'_lunara_post_signal_kicker'             => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lunara_post_signal_note'               => array( 'type' => 'string', 'sanitize' => 'sanitize_textarea_field' ),
		'_lunara_post_tag_line_override'         => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
	);

	$all_fields = array_merge( $core_fields, $editorial_fields, $override_fields, $shared_fields );

	foreach ( $all_fields as $key => $config ) {
		register_post_meta( 'review', $key, array(
			'show_in_rest'      => true,
			'single'            => true,
			'type'              => $config['type'],
			'sanitize_callback' => $config['sanitize'],
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		) );
	}
}
add_action( 'init', 'lunara_register_review_meta_for_rest' );
}



/**
 * =========================================================================
 * JOURNAL H2 SPLITTER â€” turn one Journal post into N item-cards.
 *
 * The Lunara Dispatch plugin sends a roundup post to Claude, which returns
 * HTML with <h2> section headers + <p> body paragraphs. Each H2 is a
 * distinct news item. This helper parses that content into an array of
 * per-item card dicts for the homepage Journal lane to render individually.
 *
 * Each card:
 *   - title     string  H2 text
 *   - body      string  first ~35 words of paragraphs after the H2
 *   - anchor    string  slugified H2 text (for deep-link fragment)
 *   - url       string  parent permalink + #anchor
 *   - image_id  int     attachment ID for the card image
 *   - image_url string  medium_large URL (or empty string)
 *
 * @since Lunara 2026-04-21
 * =========================================================================
 */
if ( ! function_exists( 'lunara_split_journal_into_cards' ) ) {
	function lunara_split_journal_into_cards( $post_id, $max_cards = 6 ) {
		$post = get_post( $post_id );
		if ( ! ( $post instanceof WP_Post ) ) {
			return array();
		}
		if ( ! in_array( $post->post_type, array( 'journal', 'post' ), true ) ) {
			return array();
		}

		$content = (string) $post->post_content;
		if ( '' === trim( $content ) ) {
			return array();
		}

		if ( ! class_exists( 'DOMDocument' ) ) {
			return array();
		}

		// Run standard WP content filters so shortcodes / embeds are expanded to final HTML.
		$content = apply_filters( 'the_content', $content );

		// Parse with DOMDocument. Wrap in a div so we have a stable root.
		$dom = new DOMDocument( '1.0', 'UTF-8' );
		libxml_use_internal_errors( true );
		// Meta charset hint + wrapper div; suppress errors from HTML5 tags.
		$wrapped = '<?xml encoding="UTF-8"?><div id="lunara-journal-root">' . $content . '</div>';
		$dom->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$root = $dom->getElementById( 'lunara-journal-root' );
		if ( ! $root ) {
			return array();
		}

		// -------------------------------------------------------------
		// Pre-compute fallbacks.
		// -------------------------------------------------------------
		$permalink   = get_permalink( $post_id );
		$featured_id = (int) get_post_thumbnail_id( $post_id );

		// Editorial per-section image map (slug â†’ attachment ID) â€” set in the
		// "Section Images" meta box on the Journal edit screen.
		$section_image_map = get_post_meta( $post_id, '_lunara_journal_section_images', true );
		if ( ! is_array( $section_image_map ) ) {
			$section_image_map = array();
		}

		// URL fallback map (when wp.media wouldn't load and the user pasted a URL).
		$section_image_urls = get_post_meta( $post_id, '_lunara_journal_section_image_urls', true );
		if ( ! is_array( $section_image_urls ) ) {
			$section_image_urls = array();
		}

		// __card slug is the per-post override, used by the homepage card render
		// regardless of how many H2 sections the post has.
		$card_override_id  = isset( $section_image_map['__card'] ) ? (int) $section_image_map['__card'] : 0;
		$card_override_url = isset( $section_image_urls['__card'] ) ? (string) $section_image_urls['__card'] : '';
		if ( $card_override_id > 0 ) {
			$resolved_url = wp_get_attachment_image_url( $card_override_id, 'medium_large' );
			if ( $resolved_url ) {
				$featured_id = $card_override_id;
				$featured_url = $resolved_url;
			}
		} elseif ( '' !== $card_override_url ) {
			$featured_url = $card_override_url;
		} else {
			$featured_url = $featured_id > 0
				? (string) wp_get_attachment_image_url( $featured_id, 'medium_large' )
				: '';
		}

		// Optional per-item image IDs stored by the dispatch plugin (legacy fallback).
		$dispatch_item_images = get_post_meta( $post_id, '_lunara_dispatch_item_images', true );
		if ( ! is_array( $dispatch_item_images ) ) {
			$dispatch_item_images = array();
		}

		// -------------------------------------------------------------
		// Walk the DOM, split at every <h2>.
		// -------------------------------------------------------------
		$cards              = array();
		$current_title      = '';
		$current_body_html  = '';
		$section_index      = 0;

		$flush_section = function () use (
			&$cards,
			&$current_title,
			&$current_body_html,
			&$section_index,
			$permalink,
			$featured_id,
			$featured_url,
			$section_image_map,
			$dispatch_item_images
		) {
			$has_title = '' !== trim( $current_title );
			$body_plain = trim(
				preg_replace( '/\s+/', ' ',
					wp_strip_all_tags( (string) $current_body_html )
				)
			);

			// Skip empty sections (e.g. content before the first H2 with no real text).
			if ( ! $has_title && '' === $body_plain ) {
				return;
			}

			$body_trimmed = wp_trim_words( $body_plain, 35, 'â€¦' );
			$anchor       = sanitize_title(
				$has_title ? $current_title : 'item-' . ( $section_index + 1 )
			);

			// Per-section image priority (highest â†’ lowest):
			//   1. Editorial override (Section Images meta box, slug-keyed)
			//   2. Inline <img> in this section's body â€” "the image right under the H2"
			//   3. Dispatch plugin's auto-sideload array (index-keyed)
			//   4. Post featured image (always-available fallback)
			$card_image_id  = $featured_id;
			$card_image_url = $featured_url;
			$image_resolved = false;

			// 1. Editorial override.
			if ( isset( $section_image_map[ $anchor ] ) ) {
				$candidate_id = (int) $section_image_map[ $anchor ];
				if ( $candidate_id > 0 ) {
					$candidate_url = wp_get_attachment_image_url( $candidate_id, 'medium_large' );
					if ( $candidate_url ) {
						$card_image_id  = $candidate_id;
						$card_image_url = $candidate_url;
						$image_resolved = true;
					}
				}
			}

			// 2. First <img> embedded INSIDE this section's body â€” picks up
			//    the image you placed right under the H2 in the editor.
			if ( ! $image_resolved && '' !== trim( (string) $current_body_html ) ) {
				if ( preg_match( '/<img\b[^>]*\bsrc=["\']([^"\']+)["\']/i', $current_body_html, $img_match ) ) {
					$inline_url = esc_url_raw( $img_match[1] );
					if ( '' !== $inline_url ) {
						$card_image_url = $inline_url;
						// If the URL resolves to a media-library attachment, capture its ID
						// (gives us responsive srcset + lazy-load benefits). Else use raw URL.
						$candidate_id = function_exists( 'attachment_url_to_postid' )
							? (int) attachment_url_to_postid( $inline_url )
							: 0;
						if ( $candidate_id > 0 ) {
							$card_image_id = $candidate_id;
							$attached_url  = wp_get_attachment_image_url( $candidate_id, 'medium_large' );
							if ( $attached_url ) {
								$card_image_url = $attached_url;
							}
						}
						$image_resolved = true;
					}
				}
			}

			// 3. Plugin's per-item array.
			if ( ! $image_resolved && isset( $dispatch_item_images[ $section_index ] ) ) {
				$candidate_id = (int) $dispatch_item_images[ $section_index ];
				if ( $candidate_id > 0 ) {
					$candidate_url = wp_get_attachment_image_url( $candidate_id, 'medium_large' );
					if ( $candidate_url ) {
						$card_image_id  = $candidate_id;
						$card_image_url = $candidate_url;
						$image_resolved = true;
					}
				}
			}

			$cards[] = array(
				'title'     => $current_title,
				'body'      => $body_trimmed,
				'body_raw'  => $current_body_html,
				'anchor'    => $anchor,
				'url'       => $permalink . '#' . $anchor,
				'image_id'  => $card_image_id,
				'image_url' => $card_image_url,
			);

			$section_index++;
		};

		foreach ( $root->childNodes as $node ) {
			if ( $node->nodeType === XML_ELEMENT_NODE && strtolower( $node->nodeName ) === 'h2' ) {
				// Boundary: flush previous section, start a new one.
				$flush_section();
				$current_title     = trim( $node->textContent );
				$current_body_html = '';
			} else {
				// Accumulate this node into the current section's body.
				$current_body_html .= $dom->saveHTML( $node );
			}
		}
		// Last section.
		$flush_section();

		// Drop a leading no-title preamble if it has almost no substance.
		if ( ! empty( $cards ) && '' === trim( $cards[0]['title'] ) && strlen( trim( $cards[0]['body'] ) ) < 40 ) {
			array_shift( $cards );
		}

		// Cap.
		if ( count( $cards ) > $max_cards ) {
			$cards = array_slice( $cards, 0, $max_cards );
		}

		return $cards;
	}
}


/**
 * Inject id="..." attributes into <h2> tags on single-journal content so
 * that card deep-links (/journal/slug/#michael) actually scroll to the
 * correct section.  Auto-derives slug from the H2's text.
 */
if ( ! function_exists( 'lunara_inject_journal_h2_anchors' ) ) {
	function lunara_inject_journal_h2_anchors( $content ) {
		if ( ! is_singular( 'journal' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		if ( '' === trim( (string) $content ) ) {
			return $content;
		}

		$seen = array();
		$content = preg_replace_callback(
			'/<h2(?![^>]*\sid=)([^>]*)>(.*?)<\/h2>/is',
			function ( $m ) use ( &$seen ) {
				$inner_text = wp_strip_all_tags( $m[2] );
				$slug       = sanitize_title( $inner_text );
				if ( '' === $slug ) {
					return $m[0];
				}
				// Dedupe â€” if this slug already appeared, suffix -2, -3, etc.
				$base = $slug;
				$i    = 2;
				while ( isset( $seen[ $slug ] ) ) {
					$slug = $base . '-' . $i;
					$i++;
				}
				$seen[ $slug ] = true;

				return '<h2 id="' . esc_attr( $slug ) . '"' . $m[1] . '>' . $m[2] . '</h2>';
			},
			$content
		);

		return $content;
	}
	add_filter( 'the_content', 'lunara_inject_journal_h2_anchors', 15 );
}

/**
 * Add stable anchors to review subheads so long criticism can be deep-linked.
 */
if ( ! function_exists( 'lunara_inject_review_heading_anchors' ) ) {
	function lunara_inject_review_heading_anchors( $content ) {
		if ( ! is_singular( 'review' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		if ( '' === trim( (string) $content ) ) {
			return $content;
		}

		$seen = array();
		$content = preg_replace_callback(
			'/<h([23])(?![^>]*\sid=)([^>]*)>(.*?)<\/h\1>/is',
			function ( $m ) use ( &$seen ) {
				$inner_text = wp_strip_all_tags( $m[3] );
				$slug       = sanitize_title( $inner_text );
				if ( '' === $slug ) {
					return $m[0];
				}

				$base = $slug;
				$i    = 2;
				while ( isset( $seen[ $slug ] ) ) {
					$slug = $base . '-' . $i;
					$i++;
				}
				$seen[ $slug ] = true;

				return '<h' . $m[1] . ' id="' . esc_attr( $slug ) . '"' . $m[2] . '>' . $m[3] . '</h' . $m[1] . '>';
			},
			$content
		);

		return $content;
	}
	add_filter( 'the_content', 'lunara_inject_review_heading_anchors', 15 );
}

/**
 * Prepend compact article wayfinding when a review or journal entry has real sections.
 */
if ( ! function_exists( 'lunara_prepend_reader_toc' ) ) {
	function lunara_prepend_reader_toc( $content ) {
		if ( ! is_singular( array( 'journal', 'review' ) ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		if ( false !== strpos( $content, 'lunara-reader-toc' ) ) {
			return $content;
		}
		if ( ! preg_match_all( '/<h([23])\b[^>]*\bid=(["\'])(.*?)\2[^>]*>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER ) ) {
			return $content;
		}

		$items = array();
		$seen  = array();
		foreach ( $matches as $match ) {
			$id    = sanitize_title( wp_strip_all_tags( $match[3] ) );
			$title = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $match[4] ) ) );
			if ( '' === $id || '' === $title || isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;
			$items[] = array(
				'id'    => $id,
				'title' => $title,
			);
			if ( count( $items ) >= 6 ) {
				break;
			}
		}

		if ( count( $items ) < 2 ) {
			return $content;
		}

		$label = is_singular( 'review' ) ? __( 'In This Review', 'lunara-film' ) : __( 'In This Piece', 'lunara-film' );
		$links = '';
		foreach ( $items as $item ) {
			$links .= sprintf(
				'<a class="lunara-reader-toc-link" href="#%1$s">%2$s</a>',
				esc_attr( $item['id'] ),
				esc_html( $item['title'] )
			);
		}

		$toc = sprintf(
			'<nav class="lunara-reader-toc" aria-label="%1$s"><p class="lunara-reader-toc-kicker">%2$s</p><div class="lunara-reader-toc-links">%3$s</div></nav>',
			esc_attr__( 'Article sections', 'lunara-film' ),
			esc_html( $label ),
			$links
		);

		return $toc . $content;
	}
	add_filter( 'the_content', 'lunara_prepend_reader_toc', 18 );
}


/**
 * Render a single journal post as a grid of H2 item-cards.
 *
 * Output is a <div class="lunara-journal-split-grid">â€¦</div> with one
 * <article class="lunara-journal-split-card"> per H2 section. Returns
 * empty string if the post has no H2 sections (caller should fall back
 * to its normal single-card rendering).
 */
if ( ! function_exists( 'lunara_render_journal_split_cards' ) ) {
	function lunara_render_journal_split_cards( $post_id, $max_cards = 6 ) {
		$cards = lunara_split_journal_into_cards( $post_id, $max_cards );
		if ( empty( $cards ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="lunara-journal-split-grid" data-lunara-journal-split="<?php echo esc_attr( $post_id ); ?>">
			<?php foreach ( $cards as $card ) : ?>
				<article class="lunara-journal-split-card">
					<a class="lunara-journal-split-link" href="<?php echo esc_url( $card['url'] ); ?>">
						<?php if ( $card['image_url'] ) : ?>
							<div class="lunara-journal-split-media">
								<img
									src="<?php echo esc_url( $card['image_url'] ); ?>"
									alt="<?php echo esc_attr( $card['title'] ); ?>"
									loading="lazy"
									decoding="async"
								/>
							</div>
						<?php endif; ?>
						<div class="lunara-journal-split-copy">
							<?php if ( '' !== trim( $card['title'] ) ) : ?>
								<h3 class="lunara-journal-split-title"><?php echo esc_html( $card['title'] ); ?></h3>
							<?php endif; ?>
							<?php if ( '' !== trim( $card['body'] ) ) : ?>
								<p class="lunara-journal-split-body"><?php echo esc_html( $card['body'] ); ?></p>
							<?php endif; ?>
						</div>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}



/**
 * =========================================================================
 * JOURNAL SECTION IMAGES â€” per-H2 image picker meta box
 *
 * On a Journal post edit screen, this meta box scans the post content for
 * <h2> sections and renders one media-picker row per section. Selected
 * attachment IDs are stored in post meta as a slugâ†’ID associative array:
 *
 *     _lunara_journal_section_images = [
 *         'michael'      => 28765,
 *         'outcome'      => 28766,
 *         'the-drama'    => 28767,
 *     ]
 *
 * The H2-splitter (lunara_split_journal_into_cards) reads this map FIRST,
 * before falling back to the dispatch plugin's auto-sideloaded array, then
 * to the post's featured image.
 *
 * Slugs derive from H2 text via sanitize_title â€” exactly matching the
 * anchor logic in lunara_inject_journal_h2_anchors so deep-links and image
 * lookups stay aligned.
 *
 * @since Lunara 2026-04-21
 * =========================================================================
 */

if ( ! function_exists( 'lunara_extract_journal_h2_sections' ) ) {
	/**
	 * Returns [ slug => display_title ] for every <h2> in the post content.
	 * De-duplicates slugs the same way lunara_inject_journal_h2_anchors does.
	 */
	function lunara_extract_journal_h2_sections( $content ) {
		$sections = array();
		if ( '' === trim( (string) $content ) ) {
			return $sections;
		}

		if ( ! preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/is', $content, $matches ) ) {
			return $sections;
		}

		$seen = array();
		foreach ( $matches[1] as $title_html ) {
			$title = trim( wp_strip_all_tags( $title_html ) );
			if ( '' === $title ) {
				continue;
			}
			$slug = sanitize_title( $title );
			if ( '' === $slug ) {
				continue;
			}
			$base = $slug;
			$i    = 2;
			while ( isset( $seen[ $slug ] ) ) {
				$slug = $base . '-' . $i;
				$i++;
			}
			$seen[ $slug ]    = true;
			$sections[ $slug ] = $title;
		}

		return $sections;
	}
}

if ( ! function_exists( 'lunara_journal_section_images_add_meta_box' ) ) {
	function lunara_journal_section_images_add_meta_box() {
		add_meta_box(
			'lunara_journal_section_images',
			__( 'Section Images (Per-Subcard Imagery)', 'lunara-film' ),
			'lunara_journal_section_images_callback',
			'journal',
			'normal',
			'default'
		);
	}
	add_action( 'add_meta_boxes', 'lunara_journal_section_images_add_meta_box' );
}

if ( ! function_exists( 'lunara_journal_section_images_callback' ) ) {
	function lunara_journal_section_images_callback( $post ) {
		wp_nonce_field( 'lunara_journal_section_images_save', 'lunara_journal_section_images_nonce' );

		// Make sure media library is loaded for the picker.
		wp_enqueue_media();

		$sections        = lunara_extract_journal_h2_sections( $post->post_content );
		$section_images  = get_post_meta( $post->ID, '_lunara_journal_section_images', true );
		if ( ! is_array( $section_images ) ) {
			$section_images = array();
		}
		?>
		<style>
			.lunara-section-image-list { display: grid; gap: 14px; margin: 0; padding: 0; }
			.lunara-section-image-row { display: grid; grid-template-columns: 90px 1fr auto; gap: 14px; align-items: center; padding: 12px; border: 1px solid rgba(0,0,0,.08); border-radius: 8px; background: #fafafa; }
			.lunara-section-image-row .lunara-sir-preview { width: 90px; height: 60px; background: #e6e6e6; border-radius: 6px; overflow: hidden; display: grid; place-items: center; }
			.lunara-section-image-row .lunara-sir-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
			.lunara-section-image-row .lunara-sir-preview-empty { font-size: 11px; color: #999; text-align: center; padding: 4px; }
			.lunara-section-image-row .lunara-sir-info h4 { margin: 0 0 4px; font-size: 14px; color: #1e1e1e; font-weight: 600; }
			.lunara-section-image-row .lunara-sir-info code { font-size: 11px; color: #888; background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
			.lunara-section-image-row .lunara-sir-actions { display: flex; gap: 6px; flex-wrap: wrap; }
			.lunara-section-image-empty { padding: 16px; background: #fff8e1; border: 1px solid #f5d76e; border-radius: 6px; color: #735c00; font-size: 13px; }
			.lunara-section-image-empty code { background: #fff; padding: 2px 6px; border-radius: 3px; }
			.lunara-sir-help { margin: 0 0 14px; padding: 10px 12px; background: #f0f6ff; border-left: 3px solid #c9a961; color: #333; font-size: 13px; line-height: 1.5; }
		</style>

		<?php
		// ALWAYS show a primary "Card Image" picker, regardless of H2 count.
		// This is the override for THIS post's card image on the homepage Journal lane.
		// Stored slug: '__card' (reserved â€” won't collide with any real H2 slug).
		$card_override_id  = isset( $section_images['__card'] ) ? (int) $section_images['__card'] : 0;
		$card_override_url = $card_override_id > 0 ? wp_get_attachment_image_url( $card_override_id, 'thumbnail' ) : '';
		?>
		<p class="lunara-sir-help">
			<strong>Card Image Override</strong> â€” overrides the homepage card image for this entry. Falls back to Featured Image if blank.
		</p>
		<div class="lunara-section-image-list">
			<div class="lunara-section-image-row" data-slug="__card">
				<div class="lunara-sir-preview">
					<?php if ( $card_override_url ) : ?>
						<img src="<?php echo esc_url( $card_override_url ); ?>" alt="" />
					<?php else : ?>
						<span class="lunara-sir-preview-empty">Featured fallback</span>
					<?php endif; ?>
				</div>
				<div class="lunara-sir-info">
					<h4>Homepage Card Image</h4>
					<code>this post</code>
				</div>
				<div class="lunara-sir-actions">
					<button type="button" class="button button-primary lunara-sir-select">
						<?php echo $card_override_id ? esc_html__( 'Replace', 'lunara-film' ) : esc_html__( 'Select Image', 'lunara-film' ); ?>
					</button>
					<button type="button" class="button-link-delete lunara-sir-clear" <?php echo $card_override_id ? '' : 'style="display:none;"'; ?>>
						<?php esc_html_e( 'Clear', 'lunara-film' ); ?>
					</button>
				</div>
				<input
					type="hidden"
					name="lunara_section_images[__card]"
					value="<?php echo esc_attr( $card_override_id ); ?>"
				/>
				<div class="lunara-sir-url-fallback" style="grid-column: 1 / -1; display:none; margin-top: 8px;">
					<p style="margin: 0 0 4px; font-size: 12px; color: #666;">Media library couldn't load. Paste an image URL instead:</p>
					<input type="url" class="lunara-sir-url-input" placeholder="https://..." style="width: 100%; padding: 6px;" />
					<button type="button" class="button lunara-sir-url-apply" style="margin-top: 4px;">Apply URL</button>
				</div>
			</div>
		</div>

		<?php if ( ! empty( $sections ) ) : ?>
			<hr style="margin: 18px 0; border: 0; border-top: 1px solid #e0e0e0;" />
			<p class="lunara-sir-help">
				<strong>Per-Section Images</strong> â€” only used when this post has multiple <code>&lt;h2&gt;</code> sections (legacy roundup format). Each picks an image for that specific subcard.
			</p>
			<div class="lunara-section-image-list">
				<?php foreach ( $sections as $slug => $title ) :
					$current_id  = isset( $section_images[ $slug ] ) ? (int) $section_images[ $slug ] : 0;
					$current_url = $current_id > 0 ? wp_get_attachment_image_url( $current_id, 'thumbnail' ) : '';
					?>
					<div class="lunara-section-image-row" data-slug="<?php echo esc_attr( $slug ); ?>">
						<div class="lunara-sir-preview">
							<?php if ( $current_url ) : ?>
								<img src="<?php echo esc_url( $current_url ); ?>" alt="" />
							<?php else : ?>
								<span class="lunara-sir-preview-empty">No image</span>
							<?php endif; ?>
						</div>
						<div class="lunara-sir-info">
							<h4><?php echo esc_html( $title ); ?></h4>
							<code>#<?php echo esc_html( $slug ); ?></code>
						</div>
						<div class="lunara-sir-actions">
							<button type="button" class="button lunara-sir-select">
								<?php echo $current_id ? esc_html__( 'Replace', 'lunara-film' ) : esc_html__( 'Select Image', 'lunara-film' ); ?>
							</button>
							<button type="button" class="button-link-delete lunara-sir-clear" <?php echo $current_id ? '' : 'style="display:none;"'; ?>>
								<?php esc_html_e( 'Clear', 'lunara-film' ); ?>
							</button>
						</div>
						<input
							type="hidden"
							name="lunara_section_images[<?php echo esc_attr( $slug ); ?>]"
							value="<?php echo esc_attr( $current_id ); ?>"
						/>
						<div class="lunara-sir-url-fallback" style="grid-column: 1 / -1; display:none; margin-top: 8px;">
							<p style="margin: 0 0 4px; font-size: 12px; color: #666;">Media library couldn't load. Paste an image URL instead:</p>
							<input type="url" class="lunara-sir-url-input" placeholder="https://..." style="width: 100%; padding: 6px;" />
							<button type="button" class="button lunara-sir-url-apply" style="margin-top: 4px;">Apply URL</button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<script>
		(function ($) {
			'use strict';
			if ( typeof $ === 'undefined' ) {
				console.error('Lunara: jQuery missing â€” image picker disabled.');
				return;
			}

			// Defensive: try wp.media; if missing, surface the URL fallback inputs instead of failing silently.
			function ensureMediaOrFallback() {
				if ( window.wp && wp.media ) { return true; }
				$('.lunara-sir-url-fallback').show();
				$('.lunara-sir-select').each(function(){
					$(this).attr('disabled', true).attr('title', 'Media library failed to load â€” use the URL field below.');
				});
				return false;
			}

			$(document).on('click', '.lunara-section-image-row .lunara-sir-select', function (e) {
				e.preventDefault();
				if ( ! ensureMediaOrFallback() ) { return; }

				var $row     = $(this).closest('.lunara-section-image-row');
				var $input   = $row.find('input[type="hidden"]').first();
				var $preview = $row.find('.lunara-sir-preview');
				var $clear   = $row.find('.lunara-sir-clear');
				var $select  = $(this);

				try {
					var frame = wp.media({
						title:    'Select image',
						button:   { text: 'Use this image' },
						library:  { type: 'image' },
						multiple: false
					});

					frame.on('select', function () {
						var attachment = frame.state().get('selection').first().toJSON();
						var thumbUrl   = (attachment.sizes && attachment.sizes.thumbnail)
							? attachment.sizes.thumbnail.url
							: attachment.url;
						$input.val(attachment.id);
						$preview.html('<img src="' + thumbUrl + '" alt="" />');
						$clear.show();
						$select.text('Replace');
					});

					frame.open();
				} catch (err) {
					console.error('Lunara picker error:', err);
					alert('Image picker failed to open: ' + (err && err.message ? err.message : 'unknown error') + '. Use the URL field instead.');
					$row.find('.lunara-sir-url-fallback').show();
				}
			});

			$(document).on('click', '.lunara-section-image-row .lunara-sir-clear', function (e) {
				e.preventDefault();
				var $row = $(this).closest('.lunara-section-image-row');
				$row.find('input[type="hidden"]').first().val('');
				$row.find('.lunara-sir-preview').html('<span class="lunara-sir-preview-empty">No image</span>');
				$row.find('.lunara-sir-select').text('Select Image');
				$(this).hide();
			});

			// URL fallback: turn a pasted image URL into a saved attachment.
			$(document).on('click', '.lunara-section-image-row .lunara-sir-url-apply', function (e) {
				e.preventDefault();
				var $row = $(this).closest('.lunara-section-image-row');
				var url  = $.trim( $row.find('.lunara-sir-url-input').val() );
				if ( !url || ! /^https?:\/\//i.test(url) ) {
					alert('Enter a valid image URL starting with http:// or https://');
					return;
				}
				// We can't sideload-from-URL without an AJAX endpoint, so for now
				// just store the raw URL in a sibling hidden input with -url suffix.
				// The splitter already accepts external URLs from inline <img> tags,
				// so we'll create a hidden URL input the save handler can pick up.
				var slug = $row.attr('data-slug');
				if ( !$row.find('input[name="lunara_section_image_urls[' + slug + ']"]').length ) {
					$row.append('<input type="hidden" name="lunara_section_image_urls[' + slug + ']" value="" />');
				}
				$row.find('input[name="lunara_section_image_urls[' + slug + ']"]').val(url);
				$row.find('.lunara-sir-preview').html('<img src="' + url + '" alt="" />');
				$row.find('.lunara-sir-select').text('Replace');
				$row.find('.lunara-sir-clear').show();
			});

			// Auto-check on page load.
			$(function() {
				if ( ! window.wp || ! wp.media ) {
					console.warn('Lunara: wp.media not available at load. URL fallback inputs are visible.');
					$('.lunara-sir-url-fallback').show();
				}
			});
		})(jQuery);
		</script>
		<?php
	}
}

if ( ! function_exists( 'lunara_journal_section_images_save' ) ) {
	function lunara_journal_section_images_save( $post_id ) {
		if ( ! isset( $_POST['lunara_journal_section_images_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['lunara_journal_section_images_nonce'], 'lunara_journal_section_images_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$clean = array();
		// Preserve __card slug literally (don't run sanitize_title on it).
		if ( isset( $_POST['lunara_section_images'] ) && is_array( $_POST['lunara_section_images'] ) ) {
			foreach ( $_POST['lunara_section_images'] as $slug => $id ) {
				$slug = ( '__card' === $slug ) ? '__card' : sanitize_title( $slug );
				$id   = absint( $id );
				if ( '' !== $slug && $id > 0 ) {
					$clean[ $slug ] = $id;
				}
			}
		}
		if ( ! empty( $clean ) ) {
			update_post_meta( $post_id, '_lunara_journal_section_images', $clean );
		} else {
			delete_post_meta( $post_id, '_lunara_journal_section_images' );
		}

		// URL fallback: pasted image URLs (used when media library fails to load).
		$url_clean = array();
		if ( isset( $_POST['lunara_section_image_urls'] ) && is_array( $_POST['lunara_section_image_urls'] ) ) {
			foreach ( $_POST['lunara_section_image_urls'] as $slug => $url ) {
				$slug = ( '__card' === $slug ) ? '__card' : sanitize_title( $slug );
				$url  = esc_url_raw( $url );
				if ( '' !== $slug && '' !== $url ) {
					$url_clean[ $slug ] = $url;
				}
			}
		}
		if ( ! empty( $url_clean ) ) {
			update_post_meta( $post_id, '_lunara_journal_section_image_urls', $url_clean );
		} else {
			delete_post_meta( $post_id, '_lunara_journal_section_image_urls' );
		}
	}
	add_action( 'save_post_journal', 'lunara_journal_section_images_save' );
}



/**
 * Resolve the card-display image URL for a journal post.
 * Honors the __card override (set via the Section Images meta box) before
 * falling back to the post's featured image. Returns empty string if neither.
 *
 * @param  int   $post_id
 * @param  string $size  WP image size (default 'large').
 * @return string  URL, or '' if no image.
 */
if ( ! function_exists( 'lunara_get_journal_card_image_url' ) ) {
	function lunara_get_journal_card_image_url( $post_id, $size = 'large' ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return '';
		}

		// 1. __card override from Section Images meta box.
		$section_images = get_post_meta( $post_id, '_lunara_journal_section_images', true );
		if ( is_array( $section_images ) && isset( $section_images['__card'] ) ) {
			$override_id = (int) $section_images['__card'];
			if ( $override_id > 0 ) {
				$override_url = wp_get_attachment_image_url( $override_id, $size );
				if ( $override_url ) {
					return $override_url;
				}
			}
		}

		// 2. URL fallback (when wp.media wouldn't load and the user pasted a URL).
		$section_image_urls = get_post_meta( $post_id, '_lunara_journal_section_image_urls', true );
		if ( is_array( $section_image_urls ) && ! empty( $section_image_urls['__card'] ) ) {
			return (string) $section_image_urls['__card'];
		}

		// 3. Standard featured image.
		$thumb_id = (int) get_post_thumbnail_id( $post_id );
		if ( $thumb_id > 0 ) {
			$thumb_url = wp_get_attachment_image_url( $thumb_id, $size );
			if ( $thumb_url ) {
				return $thumb_url;
			}
		}

		return '';
	}
}

/**
 * Echoes a complete <img> tag for the journal card image.
 * Prefers the __card override; falls back to featured image.
 * If only a URL is available (no attachment), emits a plain <img>.
 */
if ( ! function_exists( 'lunara_journal_card_image_html' ) ) {
	function lunara_journal_card_image_html( $post_id, $size = 'large', $css_class = '' ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return '';
		}

		$attrs = array(
			'class'    => trim( 'lunara-journal-card-image ' . $css_class ),
			'loading'  => 'lazy',
			'decoding' => 'async',
		);

		// Prefer attachment-backed image (gives us responsive srcset).
		$section_images = get_post_meta( $post_id, '_lunara_journal_section_images', true );
		$override_id = ( is_array( $section_images ) && isset( $section_images['__card'] ) )
			? (int) $section_images['__card'] : 0;
		if ( $override_id > 0 ) {
			$html = wp_get_attachment_image( $override_id, $size, false, $attrs );
			if ( $html ) {
				return $html;
			}
		}

		$thumb_id = (int) get_post_thumbnail_id( $post_id );
		if ( $thumb_id > 0 ) {
			$html = wp_get_attachment_image( $thumb_id, $size, false, $attrs );
			if ( $html ) {
				return $html;
			}
		}

		// Fall back to a raw URL (from URL-fallback meta) if no attachment.
		$url = lunara_get_journal_card_image_url( $post_id, $size );
		if ( '' !== $url ) {
			return sprintf(
				'<img src="%1$s" alt="" class="%2$s" loading="lazy" decoding="async" />',
				esc_url( $url ),
				esc_attr( $attrs['class'] )
			);
		}

		return '';
	}
}



/**
 * =========================================================================
 * JOURNAL SINGLE PAGE â€” Customizer controls
 *
 * Appears in the Customizer under "Lunara Journal" section. Lets Dalton
 * toggle byline/date/reading-time visibility AND set the title color
 * without editing code.
 *
 * @since Lunara 2026-04-29
 * =========================================================================
 */
if ( ! function_exists( 'lunara_register_journal_single_customizer' ) ) {
	function lunara_register_journal_single_customizer( $wp_customize ) {

		// Section: Journal Single Page
		$wp_customize->add_section( 'lunara_journal_single_section', array(
			'title'       => __( 'Journal Single Page', 'lunara-film' ),
			'description' => __( 'Visibility toggles + title color for individual journal entry pages.', 'lunara-film' ),
			'priority'    => 35,
			'panel'       => 'lunara_homepage_panel', // Sits inside the Homepage panel for now
		) );

		// Show Byline
		$wp_customize->add_setting( 'lunara_journal_show_byline', array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_journal_show_byline', array(
			'label'   => __( 'Show "By [Author]" byline', 'lunara-film' ),
			'section' => 'lunara_journal_single_section',
			'type'    => 'checkbox',
		) );

		// Show Date
		$wp_customize->add_setting( 'lunara_journal_show_date', array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_journal_show_date', array(
			'label'   => __( 'Show publish date', 'lunara-film' ),
			'section' => 'lunara_journal_single_section',
			'type'    => 'checkbox',
		) );

		// Show Reading Time
		$wp_customize->add_setting( 'lunara_journal_show_reading_time', array(
			'default'           => false,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_journal_show_reading_time', array(
			'label'       => __( 'Show "X min read"', 'lunara-film' ),
			'description' => __( 'Off by default. Turn on if you want estimated reading time on each journal entry.', 'lunara-film' ),
			'section'     => 'lunara_journal_single_section',
			'type'        => 'checkbox',
		) );

		// Title Color (defaults to Lunara gold #c9a961)
		$wp_customize->add_setting( 'lunara_journal_title_color', array(
			'default'           => '#c9a961',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'lunara_journal_title_color',
				array(
					'label'       => __( 'Title color (Journal entry)', 'lunara-film' ),
					'description' => __( 'Default: #c9a961 (Lunara gold). Used for the big H1 on individual journal pages.', 'lunara-film' ),
					'section'     => 'lunara_journal_single_section',
				)
			)
		);
	}
	add_action( 'customize_register', 'lunara_register_journal_single_customizer' );
}

/**
 * Output the title color as a CSS variable in the head so the lock CSS can read it.
 */
if ( ! function_exists( 'lunara_journal_inline_title_color_css' ) ) {
	function lunara_journal_inline_title_color_css() {
		$color = get_theme_mod( 'lunara_journal_title_color', '#c9a961' );
		$color = sanitize_hex_color( $color );
		if ( ! $color ) {
			return;
		}
		echo '<style id="lunara-journal-title-color">:root{--lunara-journal-title-color:' . esc_attr( $color ) . ';}</style>' . "\n";
	}
	add_action( 'wp_head', 'lunara_journal_inline_title_color_css', 105 );
}
/**
 * =========================================================================
 * HOMEPAGE JOURNAL RAIL CARDS â€” Customizer controls (vertical stack)
 *
 * Adds three controls so Dalton can fine-tune the new stacked rail cards
 * without touching code:
 *
 *   1. Layout      â€” "Stacked (image on top)" or "Side-by-side (legacy)"
 *   2. Image ratio â€” aspect ratio for the stacked image (4/3 â†’ 21/9)
 *   3. Title size  â€” title font-size in pixels
 *
 * Lives in the existing "Lunara Homepage" panel (lunara_homepage_panel),
 * inside a new sub-section "Journal Rail Cards (Homepage)". Pairs with
 * rail_card_stack_append.css.
 *
 * @since Lunara 2026-04-29
 * =========================================================================
 */
if ( ! function_exists( 'lunara_register_rail_card_stack_customizer' ) ) {
	function lunara_register_rail_card_stack_customizer( $wp_customize ) {

		// Section: Journal Rail Cards
		$wp_customize->add_section( 'lunara_rail_card_section', array(
			'title'       => __( 'Journal Rail Cards (Homepage)', 'lunara-film' ),
			'description' => __( 'Controls the smaller Journal cards on the homepage Journal lane (right side / below the lead). Stacked layout keeps titles readable even with big images.', 'lunara-film' ),
			'priority'    => 36,
			'panel'       => 'lunara_homepage_panel',
		) );

		// 1) Layout choice
		$wp_customize->add_setting( 'lunara_rail_card_layout', array(
			'default'           => 'split',
			'sanitize_callback' => 'lunara_sanitize_rail_card_layout',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_rail_card_layout', array(
			'label'       => __( 'Card layout', 'lunara-film' ),
			'description' => __( 'Stacked = big image on top, title clearly below (recommended). Side-by-side = legacy small-thumbnail layout.', 'lunara-film' ),
			'section'     => 'lunara_rail_card_section',
			'type'        => 'radio',
			'choices'     => array(
				'stack' => __( 'Stacked (image on top â€” readable titles)', 'lunara-film' ),
				'split' => __( 'Side-by-side (small thumbnail left of title â€” legacy)', 'lunara-film' ),
			),
		) );

		// 2) Image aspect ratio (stacked only)
		$wp_customize->add_setting( 'lunara_rail_card_image_ratio', array(
			'default'           => '16/10',
			'sanitize_callback' => 'lunara_sanitize_rail_card_image_ratio',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_rail_card_image_ratio', array(
			'label'       => __( 'Stacked image shape', 'lunara-film' ),
			'description' => __( 'Aspect ratio for the full-width image when card layout is Stacked.', 'lunara-film' ),
			'section'     => 'lunara_rail_card_section',
			'type'        => 'select',
			'choices'     => array(
				'4/3'   => __( '4 : 3 (taller, more poster-like)', 'lunara-film' ),
				'3/2'   => __( '3 : 2 (classic photo)', 'lunara-film' ),
				'16/10' => __( '16 : 10 (default â€” balanced)', 'lunara-film' ),
				'16/9'  => __( '16 : 9 (widescreen)', 'lunara-film' ),
				'2/1'   => __( '2 : 1 (panoramic)', 'lunara-film' ),
				'21/9'  => __( '21 : 9 (cinematic banner)', 'lunara-film' ),
			),
		) );

		// 3) Title font size (px)
		$wp_customize->add_setting( 'lunara_rail_card_title_size', array(
			'default'           => 22,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_rail_card_title_size', array(
			'label'       => __( 'Title size (px)', 'lunara-film' ),
			'description' => __( 'Title font size in pixels. Default 22. Range 16â€“32.', 'lunara-film' ),
			'section'     => 'lunara_rail_card_section',
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 16,
				'max'  => 32,
				'step' => 1,
			),
		) );
	}
	// Controls now live in the main homepage Journal group in inc/customizer.php.
}

/**
 * Sanitize rail card layout choice.
 */
if ( ! function_exists( 'lunara_sanitize_rail_card_layout' ) ) {
	function lunara_sanitize_rail_card_layout( $value ) {
		return in_array( $value, array( 'stack', 'split' ), true ) ? $value : 'stack';
	}
}

/**
 * Sanitize stacked image aspect ratio.
 */
if ( ! function_exists( 'lunara_sanitize_rail_card_image_ratio' ) ) {
	function lunara_sanitize_rail_card_image_ratio( $value ) {
		$allowed = array( '4/3', '3/2', '16/10', '16/9', '2/1', '21/9' );
		return in_array( $value, $allowed, true ) ? $value : '16/10';
	}
}

/**
 * Emit CSS variables + a body-level data attribute so the stylesheet
 * (rail_card_stack_append.css) can react to the Customizer choices.
 */
if ( ! function_exists( 'lunara_rail_card_stack_inline_css' ) ) {
	function lunara_rail_card_stack_inline_css() {
		$layout = lunara_sanitize_rail_card_layout( get_theme_mod( 'lunara_rail_card_layout', 'split' ) );
		$ratio  = lunara_sanitize_rail_card_image_ratio( get_theme_mod( 'lunara_rail_card_image_ratio', '16/10' ) );
		$size   = absint( get_theme_mod( 'lunara_rail_card_title_size', 22 ) );
		if ( $size < 16 || $size > 32 ) {
			$size = 22;
		}

		$css = ':root{';
		$css .= '--lunara-rail-card-image-ratio:' . esc_attr( $ratio ) . ';';
		$css .= '--lunara-rail-card-title-size:' . (int) $size . 'px;';
		$css .= '}';

		echo '<style id="lunara-rail-card-stack">' . $css . '</style>' . "\n";

		// Tag <body> with the layout choice so CSS can flip stack â†” split.
		// We can't modify the body tag from wp_head, so emit a tiny inline
		// script that sets it as soon as <body> exists.
		echo '<script id="lunara-rail-card-layout-attr">document.documentElement.addEventListener("DOMContentLoaded",function(){document.body&&document.body.setAttribute("data-rail-card-layout","' . esc_js( $layout ) . '");});if(document.readyState!=="loading"&&document.body){document.body.setAttribute("data-rail-card-layout","' . esc_js( $layout ) . '");}</script>' . "\n";
	}
	add_action( 'wp_head', 'lunara_rail_card_stack_inline_css', 106 );
}


/* ============================================================================
 * PRESERVED FROM REGRESSED LIVE 20260510 â€” review layout guardrail CSS.
 * Wrapped in function_exists() guard for safety.
 * ============================================================================ */
if ( ! function_exists( 'lunara_output_review_layout_guardrail_css' ) ) {
function lunara_output_review_layout_guardrail_css() {
    if ( ! is_singular( 'review' ) ) {
        return;
    }
    ?>
    <style id="lunara-review-layout-guardrail">
    body.single-review[data-prefix="review_single"] {
        --theme-normal-container-max-width: min(1560px, calc(100vw - 32px)) !important;
    }

    body.single-review [class*="ct-container"] {
        width: min(1480px, calc(100vw - 48px)) !important;
        max-width: min(1480px, calc(100vw - 48px)) !important;
    }

    body.single-review [class*="ct-container"] > article[class*="post"] {
        --has-boxed: var(--false) !important;
        --has-wide: var(--true) !important;
        --theme-boxed-content-spacing: 0 !important;
        --theme-boxed-content-border: none !important;
        --theme-boxed-content-box-shadow: none !important;
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    body.single-review .lunara-review-single-page {
        width: 100% !important;
        max-width: min(1480px, calc(100vw - 48px)) !important;
        padding-left: clamp(22px, 3vw, 54px) !important;
        padding-right: clamp(22px, 3vw, 54px) !important;
    }

    @media (min-width: 760px) {
        body.single-review .lunara-review-single-body-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) minmax(220px, 290px) !important;
            gap: 34px !important;
            align-items: start !important;
        }

        body.single-review .lunara-review-single-content {
            max-width: none !important;
            min-width: 0 !important;
        }

        body.single-review .lunara-review-single-rail {
            display: block !important;
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            order: initial !important;
            margin-bottom: 0 !important;
            align-self: stretch !important;
            min-height: 100% !important;
            position: relative !important;
        }

        body.single-review .lunara-review-single-rail-sticky {
            position: sticky !important;
            top: 110px !important;
            transform: none !important;
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 16px !important;
            z-index: 1 !important;
        }

        body.single-review .lunara-review-single-details,
        body.single-review .lunara-review-single-where-card,
        body.single-review .lunara-review-single-ledger-card {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
        }

        body.single-review .lunara-review-single-rail-actions {
            grid-column: auto !important;
            display: grid !important;
            gap: 10px !important;
        }

        body.single-review .lunara-review-single-debrief-section {
            position: relative !important;
            z-index: 3 !important;
        }
    }

    @media (min-width: 760px) and (max-height: 820px) {
        body.single-review .lunara-review-single-rail-sticky {
            position: static !important;
            top: auto !important;
            transform: none !important;
        }
    }

    @media (max-width: 759px) {
        body.single-review .lunara-review-single-body-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 24px !important;
        }

        body.single-review .lunara-review-single-rail {
            order: initial !important;
            margin-bottom: 0 !important;
        }

        body.single-review .lunara-review-single-rail-sticky {
            position: static !important;
            top: auto !important;
            transform: none !important;
        }
    }
    </style>
    <?php
}
add_action( 'wp_head', 'lunara_output_review_layout_guardrail_css', 101 );
}

if ( ! function_exists( 'lunara_output_journal_desk_polish_css' ) ) {
	function lunara_output_journal_desk_polish_css() {
		if ( ! is_front_page() && ! is_post_type_archive( 'journal' ) && ! is_tax( 'journal_type' ) ) {
			return;
		}
		?>
		<style id="lunara-journal-desk-polish">
			body .lunara-journal-home-deskbar,
			body .lunara-journal-archive-deskbar {
				display: flex !important;
				flex-wrap: wrap !important;
				align-items: center !important;
				gap: 10px 18px !important;
				margin: 18px 0 22px !important;
				padding: 12px 14px !important;
				border: 1px solid rgba(224,196,129,.16) !important;
				border-radius: 8px !important;
				background: rgba(9,20,34,.72) !important;
				color: rgba(233,240,247,.86) !important;
			}

			body .lunara-journal-home-deskbar span,
			body .lunara-journal-archive-deskbar span {
				display: inline-flex !important;
				align-items: baseline !important;
				gap: 7px !important;
				min-width: 0 !important;
				font-size: .88rem !important;
				line-height: 1.35 !important;
			}

			body .lunara-journal-home-deskbar strong,
			body .lunara-journal-archive-deskbar strong {
				color: rgba(224,196,129,.95) !important;
				font-size: .68rem !important;
				letter-spacing: 0 !important;
				text-transform: uppercase !important;
			}

			body .lunara-journal-home-deskbar strong::after,
			body .lunara-journal-archive-deskbar strong::after {
				content: none !important;
			}

			body .lunara-journal-archive-filters,
			body .lunara-archive-sort {
				display: flex !important;
				flex-wrap: wrap !important;
				gap: 10px !important;
				align-items: center !important;
			}

			body .lunara-journal-filter-pill,
			body .lunara-journal-filter-pill:link,
			body .lunara-journal-filter-pill:visited,
			body .lunara-archive-sort-link,
			body .lunara-archive-sort-link:link,
			body .lunara-archive-sort-link:visited {
				display: inline-flex !important;
				align-items: center !important;
				justify-content: center !important;
				gap: 7px !important;
				min-height: 38px !important;
				padding: 9px 14px !important;
				border: 1px solid rgba(224,196,129,.24) !important;
				border-radius: 8px !important;
				background: rgba(12,27,45,.84) !important;
				color: rgba(233,240,247,.88) !important;
				font-size: .78rem !important;
				font-weight: 700 !important;
				line-height: 1.15 !important;
				letter-spacing: 0 !important;
				text-decoration: none !important;
			}

			body .lunara-journal-filter-pill:hover,
			body .lunara-archive-sort-link:hover,
			body .lunara-journal-filter-pill.is-active,
			body .lunara-archive-sort-link.is-active {
				border-color: rgba(224,196,129,.64) !important;
				background: rgba(201,169,97,.14) !important;
				color: rgba(255,245,216,.98) !important;
				text-decoration: none !important;
			}

			body .lunara-journal-filter-count {
				color: inherit !important;
				opacity: .72 !important;
			}

			body .lunara-journal-card-provenance {
				display: flex !important;
				flex-wrap: wrap !important;
				align-items: center !important;
				gap: 6px !important;
				margin: 8px 0 0 !important;
				max-width: 100% !important;
			}

			body .lunara-journal-card-provenance-pill {
				display: inline-flex !important;
				align-items: center !important;
				min-width: 0 !important;
				max-width: 100% !important;
				min-height: 28px !important;
				padding: 6px 9px !important;
				border: 1px solid rgba(224,196,129,.22) !important;
				border-radius: 8px !important;
				background: rgba(8,18,31,.66) !important;
				color: rgba(236,242,248,.84) !important;
				font-size: .67rem !important;
				font-weight: 800 !important;
				line-height: 1.12 !important;
				letter-spacing: 0 !important;
				text-transform: uppercase !important;
				overflow-wrap: anywhere !important;
			}

			body .lunara-journal-card-provenance-pill.is-source {
				color: rgba(255,244,213,.9) !important;
			}

			body .lunara-journal-card-provenance-pill.is-carousel {
				background: rgba(201,169,97,.14) !important;
				color: rgba(255,247,224,.96) !important;
			}

			@media (max-width: 720px) {
				body .lunara-journal-home-deskbar,
				body .lunara-journal-archive-deskbar {
					align-items: stretch !important;
					gap: 8px !important;
				}

				body .lunara-journal-home-deskbar span,
				body .lunara-journal-archive-deskbar span {
					width: 100% !important;
					justify-content: space-between !important;
				}
			}
		</style>
		<?php
	}
	add_action( 'wp_head', 'lunara_output_journal_desk_polish_css', 120 );
}


/* ============================================================================
 * LUNARA OSCAR PICKS â€” CPT, taxonomy, meta box, query helper.
 * Added 2026-05-10 for the new homepage Oscar Picks carousel.
 *
 * Editorial-content concept: Lunara's predicted/actual Oscar winners for each
 * ceremony, with UNIQUE behind-the-scenes / working images instead of generic
 * IMDb posters. The differentiator. NOT raw plugin data.
 * ============================================================================ */

if ( ! function_exists( 'lunara_register_oscar_pick_cpt' ) ) {
	function lunara_register_oscar_pick_cpt() {
		register_post_type( 'lunara_oscar_pick', array(
			'labels' => array(
				'name'          => __( 'Oscar Picks', 'lunara-film' ),
				'singular_name' => __( 'Oscar Pick', 'lunara-film' ),
				'add_new'       => __( 'Add New Pick', 'lunara-film' ),
				'add_new_item'  => __( 'Add New Oscar Pick', 'lunara-film' ),
				'edit_item'     => __( 'Edit Oscar Pick', 'lunara-film' ),
				'view_item'     => __( 'View Oscar Pick', 'lunara-film' ),
				'menu_name'     => __( 'Oscar Picks', 'lunara-film' ),
				'all_items'     => __( 'All Picks', 'lunara-film' ),
				'search_items'  => __( 'Search Picks', 'lunara-film' ),
				'not_found'     => __( 'No picks yet.', 'lunara-film' ),
			),
			'public'              => true,
			'has_archive'         => 'oscar-picks',
			'rewrite'             => array( 'slug' => 'oscar-picks', 'with_front' => false ),
			'menu_icon'           => 'dashicons-awards',
			'menu_position'       => 22,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'taxonomies'          => array( 'oscar_pick_category' ),
			'show_in_rest'        => true,
			'capability_type'     => 'post',
		) );

		register_taxonomy( 'oscar_pick_category', 'lunara_oscar_pick', array(
			'labels' => array(
				'name'          => __( 'Pick Categories', 'lunara-film' ),
				'singular_name' => __( 'Pick Category', 'lunara-film' ),
				'menu_name'     => __( 'Categories', 'lunara-film' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'oscar-picks/category', 'with_front' => false ),
		) );
	}
	add_action( 'init', 'lunara_register_oscar_pick_cpt' );
}

/**
 * Seed default categories on first activation. Idempotent.
 */
if ( ! function_exists( 'lunara_seed_oscar_pick_categories' ) ) {
	function lunara_seed_oscar_pick_categories() {
		if ( get_option( 'lunara_oscar_pick_categories_seeded' ) ) {
			return;
		}
		if ( ! taxonomy_exists( 'oscar_pick_category' ) ) {
			return;
		}
		$defaults = array(
			'Best Picture',
			'Best Director',
			'Best Actor',
			'Best Actress',
			'Best Supporting Actor',
			'Best Supporting Actress',
			'Best Original Screenplay',
			'Best Adapted Screenplay',
			'Best Cinematography',
			'Best Editing',
			'Best Original Score',
			'Best International Feature',
			'Best Documentary Feature',
			'Best Animated Feature',
		);
		foreach ( $defaults as $name ) {
			if ( ! term_exists( $name, 'oscar_pick_category' ) ) {
				wp_insert_term( $name, 'oscar_pick_category' );
			}
		}
		update_option( 'lunara_oscar_pick_categories_seeded', 1 );
	}
	add_action( 'init', 'lunara_seed_oscar_pick_categories', 20 );
}

/**
 * Meta box: film, person, ceremony year, status, ledger URL.
 */
if ( ! function_exists( 'lunara_oscar_pick_add_meta_box' ) ) {
	function lunara_oscar_pick_add_meta_box() {
		add_meta_box(
			'lunara_oscar_pick_details',
			__( 'Pick Details', 'lunara-film' ),
			'lunara_oscar_pick_meta_box_callback',
			'lunara_oscar_pick',
			'side',
			'high'
		);
	}
	add_action( 'add_meta_boxes', 'lunara_oscar_pick_add_meta_box' );
}

if ( ! function_exists( 'lunara_oscar_pick_meta_box_callback' ) ) {
	function lunara_oscar_pick_meta_box_callback( $post ) {
		wp_nonce_field( 'lunara_oscar_pick_save', 'lunara_oscar_pick_nonce' );
		$film          = (string) get_post_meta( $post->ID, '_lunara_pick_film', true );
		$person        = (string) get_post_meta( $post->ID, '_lunara_pick_person', true );
		$ceremony_year = (string) get_post_meta( $post->ID, '_lunara_pick_ceremony_year', true );
		$status        = (string) get_post_meta( $post->ID, '_lunara_pick_status', true );
		$ledger_url    = (string) get_post_meta( $post->ID, '_lunara_pick_oscar_entity_url', true );
		if ( '' === $status ) {
			$status = 'predicted';
		}
		?>
		<p>
			<label for="lunara_pick_film"><strong><?php esc_html_e( 'Film', 'lunara-film' ); ?></strong></label><br>
			<input type="text" id="lunara_pick_film" name="lunara_pick_film" value="<?php echo esc_attr( $film ); ?>" class="widefat" placeholder="One Battle After Another" />
		</p>
		<p>
			<label for="lunara_pick_person"><strong><?php esc_html_e( 'Person (optional)', 'lunara-film' ); ?></strong></label><br>
			<input type="text" id="lunara_pick_person" name="lunara_pick_person" value="<?php echo esc_attr( $person ); ?>" class="widefat" placeholder="Sean Penn" />
			<span class="description"><?php esc_html_e( 'Leave blank for film-level picks (Best Picture, Best International Feature).', 'lunara-film' ); ?></span>
		</p>
		<p>
			<label for="lunara_pick_ceremony_year"><strong><?php esc_html_e( 'Ceremony Year', 'lunara-film' ); ?></strong></label><br>
			<input type="number" id="lunara_pick_ceremony_year" name="lunara_pick_ceremony_year" value="<?php echo esc_attr( $ceremony_year ); ?>" class="widefat" placeholder="2026" min="1929" max="2100" step="1" />
			<span class="description"><?php esc_html_e( 'Year the ceremony airs (e.g. 2026 for the 98th Academy Awards).', 'lunara-film' ); ?></span>
		</p>
		<p>
			<label for="lunara_pick_status"><strong><?php esc_html_e( 'Status', 'lunara-film' ); ?></strong></label><br>
			<select id="lunara_pick_status" name="lunara_pick_status" class="widefat">
				<option value="predicted" <?php selected( $status, 'predicted' ); ?>><?php esc_html_e( 'Predicted (not yet awarded)', 'lunara-film' ); ?></option>
				<option value="won" <?php selected( $status, 'won' ); ?>><?php esc_html_e( 'Won', 'lunara-film' ); ?></option>
				<option value="lost" <?php selected( $status, 'lost' ); ?>><?php esc_html_e( 'Lost (we missed)', 'lunara-film' ); ?></option>
			</select>
		</p>
		<p>
			<label for="lunara_pick_oscar_entity_url"><strong><?php esc_html_e( 'Lunara Ledger URL (optional)', 'lunara-film' ); ?></strong></label><br>
			<input type="url" id="lunara_pick_oscar_entity_url" name="lunara_pick_oscar_entity_url" value="<?php echo esc_attr( $ledger_url ); ?>" class="widefat" placeholder="/oscars/title/tt12345/" />
			<span class="description"><?php esc_html_e( 'Link to the film/person page in the Lunara Oscar Ledger. The card image deep-links here.', 'lunara-film' ); ?></span>
		</p>
		<?php
	}
}

if ( ! function_exists( 'lunara_oscar_pick_save_meta' ) ) {
	function lunara_oscar_pick_save_meta( $post_id ) {
		if ( ! isset( $_POST['lunara_oscar_pick_nonce'] ) || ! wp_verify_nonce( $_POST['lunara_oscar_pick_nonce'], 'lunara_oscar_pick_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'_lunara_pick_film'             => isset( $_POST['lunara_pick_film'] ) ? sanitize_text_field( wp_unslash( $_POST['lunara_pick_film'] ) ) : '',
			'_lunara_pick_person'           => isset( $_POST['lunara_pick_person'] ) ? sanitize_text_field( wp_unslash( $_POST['lunara_pick_person'] ) ) : '',
			'_lunara_pick_ceremony_year'    => isset( $_POST['lunara_pick_ceremony_year'] ) ? absint( $_POST['lunara_pick_ceremony_year'] ) : 0,
			'_lunara_pick_oscar_entity_url' => isset( $_POST['lunara_pick_oscar_entity_url'] ) ? esc_url_raw( wp_unslash( $_POST['lunara_pick_oscar_entity_url'] ) ) : '',
		);

		$status = isset( $_POST['lunara_pick_status'] ) ? sanitize_key( $_POST['lunara_pick_status'] ) : 'predicted';
		if ( ! in_array( $status, array( 'predicted', 'won', 'lost' ), true ) ) {
			$status = 'predicted';
		}
		$fields['_lunara_pick_status'] = $status;

		foreach ( $fields as $key => $value ) {
			if ( '' === $value || 0 === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}
	add_action( 'save_post_lunara_oscar_pick', 'lunara_oscar_pick_save_meta' );
}

/**
 * Admin list: thumbnail, film, ceremony year, status as columns.
 */
if ( ! function_exists( 'lunara_oscar_pick_admin_columns' ) ) {
	function lunara_oscar_pick_admin_columns( $cols ) {
		$new = array();
		foreach ( $cols as $key => $label ) {
			if ( 'title' === $key ) {
				$new['lunara_pick_thumb'] = __( 'Image', 'lunara-film' );
			}
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['lunara_pick_film']     = __( 'Film', 'lunara-film' );
				$new['lunara_pick_ceremony'] = __( 'Ceremony', 'lunara-film' );
				$new['lunara_pick_status']   = __( 'Status', 'lunara-film' );
			}
		}
		return $new;
	}
	add_filter( 'manage_lunara_oscar_pick_posts_columns', 'lunara_oscar_pick_admin_columns' );

	function lunara_oscar_pick_admin_column_value( $column, $post_id ) {
		switch ( $column ) {
			case 'lunara_pick_thumb':
				if ( has_post_thumbnail( $post_id ) ) {
					echo get_the_post_thumbnail( $post_id, array( 80, 60 ), array( 'style' => 'border-radius:4px;object-fit:cover;' ) );
				} else {
					echo '<span style="color:#999;">&mdash;</span>';
				}
				break;
			case 'lunara_pick_film':
				echo esc_html( (string) get_post_meta( $post_id, '_lunara_pick_film', true ) ?: 'â€”' );
				break;
			case 'lunara_pick_ceremony':
				$year = (int) get_post_meta( $post_id, '_lunara_pick_ceremony_year', true );
				echo $year > 0 ? esc_html( (string) $year ) : 'â€”';
				break;
			case 'lunara_pick_status':
				$status = (string) get_post_meta( $post_id, '_lunara_pick_status', true );
				$colors = array( 'predicted' => '#f5a623', 'won' => '#27ae60', 'lost' => '#999' );
				$color  = isset( $colors[ $status ] ) ? $colors[ $status ] : '#999';
				echo '<span style="display:inline-block;padding:2px 8px;border-radius:10px;background:' . esc_attr( $color ) . ';color:#fff;font-size:11px;text-transform:uppercase;letter-spacing:.06em;">' . esc_html( $status ?: 'predicted' ) . '</span>';
				break;
		}
	}
	add_action( 'manage_lunara_oscar_pick_posts_custom_column', 'lunara_oscar_pick_admin_column_value', 10, 2 );
}

/**
 * Query helper. Returns a WP_Query of Oscar Picks.
 */
if ( ! function_exists( 'lunara_get_oscar_picks' ) ) {
	function lunara_get_oscar_picks( $args = array() ) {
		$defaults = array(
			'posts_per_page' => 12,
			'ceremony_year'  => 0,
			'status'         => '',
			'category'       => '',
		);
		$args = wp_parse_args( $args, $defaults );

		$query_args = array(
			'post_type'           => 'lunara_oscar_pick',
			'posts_per_page'      => (int) $args['posts_per_page'],
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'meta_query'          => array(),
			'tax_query'           => array(),
		);

		if ( ! empty( $args['ceremony_year'] ) ) {
			$query_args['meta_query'][] = array(
				'key'   => '_lunara_pick_ceremony_year',
				'value' => (int) $args['ceremony_year'],
			);
		}
		if ( ! empty( $args['status'] ) ) {
			$query_args['meta_query'][] = array(
				'key'   => '_lunara_pick_status',
				'value' => sanitize_key( $args['status'] ),
			);
		}
		if ( ! empty( $args['category'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'oscar_pick_category',
				'field'    => is_numeric( $args['category'] ) ? 'term_id' : 'slug',
				'terms'    => $args['category'],
			);
		}

		// Default sort: most recent ceremony first, then most recently published pick.
		$query_args['meta_key'] = '_lunara_pick_ceremony_year';
		$query_args['orderby']  = array(
			'meta_value_num' => 'DESC',
			'date'           => 'DESC',
		);

		return new WP_Query( $query_args );
	}
}

if ( ! function_exists( 'lunara_oscar_ledger_instance' ) ) {
	function lunara_oscar_ledger_instance() {
		if ( class_exists( 'Academy_Awards_Table' ) && method_exists( 'Academy_Awards_Table', 'get_instance' ) ) {
			return Academy_Awards_Table::get_instance();
		}

		return null;
	}
}

if ( ! function_exists( 'lunara_oscar_ledger_url_from_id' ) ) {
	function lunara_oscar_ledger_url_from_id( $entity_id ) {
		$entity_id = strtolower( trim( (string) $entity_id ) );
		if ( '' === $entity_id ) {
			return '';
		}

		$ledger = lunara_oscar_ledger_instance();
		if ( $ledger && method_exists( $ledger, 'get_entity_url' ) ) {
			$url = (string) $ledger->get_entity_url( $entity_id );
			if ( '' !== $url ) {
				return esc_url_raw( $url );
			}
		}

		$base = trailingslashit( home_url( '/oscars/' ) );
		if ( preg_match( '/^tt\d+$/', $entity_id ) ) {
			return esc_url_raw( $base . 'title/' . $entity_id . '/' );
		}
		if ( preg_match( '/^(nm\d+|lnm-[a-z0-9-]+)$/', $entity_id ) ) {
			return esc_url_raw( $base . 'name/' . $entity_id . '/' );
		}
		if ( preg_match( '/^co\d+$/', $entity_id ) ) {
			return esc_url_raw( $base . 'company/' . $entity_id . '/' );
		}

		return '';
	}
}

if ( ! function_exists( 'lunara_oscar_ledger_category_url' ) ) {
	function lunara_oscar_ledger_category_url( $category ) {
		$canonical = lunara_oscar_pick_canonical_category( $category );
		if ( '' === $canonical ) {
			$canonical = trim( (string) $category );
		}
		if ( '' === $canonical ) {
			return '';
		}

		$ledger = lunara_oscar_ledger_instance();
		if ( $ledger && method_exists( $ledger, 'get_category_url' ) ) {
			$url = (string) $ledger->get_category_url( $canonical );
			if ( '' !== $url ) {
				return esc_url_raw( $url );
			}
		}

		return esc_url_raw( trailingslashit( home_url( '/oscars/category/' . sanitize_title( $canonical ) ) ) );
	}
}

if ( ! function_exists( 'lunara_oscar_display_year_to_ceremony' ) ) {
	function lunara_oscar_display_year_to_ceremony( $year ) {
		$year = (int) $year;
		if ( $year >= 1 && $year <= 150 ) {
			return $year;
		}
		if ( $year >= 1929 && $year <= 2100 ) {
			return max( 1, $year - 1928 );
		}

		return 0;
	}
}

if ( ! function_exists( 'lunara_oscar_ledger_ceremony_url' ) ) {
	function lunara_oscar_ledger_ceremony_url( $year ) {
		$ceremony = lunara_oscar_display_year_to_ceremony( $year );
		if ( $ceremony <= 0 ) {
			return '';
		}

		$ledger = lunara_oscar_ledger_instance();
		if ( $ledger && method_exists( $ledger, 'get_ceremony_url' ) ) {
			$url = (string) $ledger->get_ceremony_url( $ceremony );
			if ( '' !== $url ) {
				return esc_url_raw( $url );
			}
		}

		return esc_url_raw( trailingslashit( home_url( '/oscars/ceremony/' . $ceremony ) ) );
	}
}

if ( ! function_exists( 'lunara_oscar_pick_canonical_category' ) ) {
	function lunara_oscar_pick_canonical_category( $category ) {
		$key = strtolower( trim( remove_accents( (string) $category ) ) );
		$key = preg_replace( '/[^a-z0-9]+/', ' ', $key );
		$key = trim( preg_replace( '/\s+/', ' ', $key ) );

		$map = array(
			'best picture'              => 'BEST PICTURE',
			'picture'                   => 'BEST PICTURE',
			'best director'             => 'DIRECTING',
			'directing'                 => 'DIRECTING',
			'best actor'                => 'ACTOR IN A LEADING ROLE',
			'actor'                     => 'ACTOR IN A LEADING ROLE',
			'best actress'              => 'ACTRESS IN A LEADING ROLE',
			'actress'                   => 'ACTRESS IN A LEADING ROLE',
			'best supporting actor'     => 'ACTOR IN A SUPPORTING ROLE',
			'supporting actor'          => 'ACTOR IN A SUPPORTING ROLE',
			'best supporting actress'   => 'ACTRESS IN A SUPPORTING ROLE',
			'supporting actress'        => 'ACTRESS IN A SUPPORTING ROLE',
			'best adapted screenplay'   => 'WRITING (Adapted Screenplay)',
			'adapted screenplay'        => 'WRITING (Adapted Screenplay)',
			'best original screenplay'  => 'WRITING (Original Screenplay)',
			'original screenplay'       => 'WRITING (Original Screenplay)',
			'best original score'       => 'MUSIC (Original Score)',
			'original score'            => 'MUSIC (Original Score)',
			'best original song'        => 'MUSIC (Original Song)',
			'original song'             => 'MUSIC (Original Song)',
			'best cinematography'       => 'CINEMATOGRAPHY',
			'cinematography'            => 'CINEMATOGRAPHY',
			'best editing'              => 'FILM EDITING',
			'best film editing'         => 'FILM EDITING',
			'film editing'              => 'FILM EDITING',
			'best production design'    => 'PRODUCTION DESIGN',
			'production design'         => 'PRODUCTION DESIGN',
			'best costume design'       => 'COSTUME DESIGN',
			'costume design'            => 'COSTUME DESIGN',
			'best makeup and hairstyling' => 'MAKEUP AND HAIRSTYLING',
			'makeup and hairstyling'    => 'MAKEUP AND HAIRSTYLING',
			'best visual effects'       => 'VISUAL EFFECTS',
			'visual effects'            => 'VISUAL EFFECTS',
			'best sound'                => 'SOUND',
			'sound'                     => 'SOUND',
			'best international feature film' => 'INTERNATIONAL FEATURE FILM',
			'international feature film' => 'INTERNATIONAL FEATURE FILM',
			'best documentary feature'  => 'DOCUMENTARY FEATURE',
			'documentary feature'       => 'DOCUMENTARY FEATURE',
			'best animated feature'     => 'ANIMATED FEATURE FILM',
			'animated feature'          => 'ANIMATED FEATURE FILM',
		);

		return isset( $map[ $key ] ) ? $map[ $key ] : '';
	}
}

if ( ! function_exists( 'lunara_oscar_match_text' ) ) {
	function lunara_oscar_match_text( $value ) {
		$value = remove_accents( wp_strip_all_tags( (string) $value ) );
		$value = strtolower( $value );
		$value = preg_replace( '/[^a-z0-9]+/', ' ', $value );
		return trim( preg_replace( '/\s+/', ' ', $value ) );
	}
}

if ( ! function_exists( 'lunara_oscar_text_matches' ) ) {
	function lunara_oscar_text_matches( $needle, $haystack ) {
		$needle   = lunara_oscar_match_text( $needle );
		$haystack = lunara_oscar_match_text( $haystack );

		if ( strlen( $needle ) < 3 || strlen( $haystack ) < 3 ) {
			return false;
		}

		return false !== strpos( $haystack, $needle ) || false !== strpos( $needle, $haystack );
	}
}

if ( ! function_exists( 'lunara_oscar_first_id_from_list' ) ) {
	function lunara_oscar_first_id_from_list( $ids_raw, $type = '' ) {
		$ids = preg_split( '/[|,;]/', (string) $ids_raw );
		foreach ( $ids as $id ) {
			$id = strtolower( trim( $id ) );
			if ( '' === $id ) {
				continue;
			}
			if ( 'title' === $type && preg_match( '/^tt\d+$/', $id ) ) {
				return $id;
			}
			if ( 'name' === $type && preg_match( '/^(nm\d+|lnm-[a-z0-9-]+)$/', $id ) ) {
				return $id;
			}
			if ( 'company' === $type && preg_match( '/^co\d+$/', $id ) ) {
				return $id;
			}
			if ( '' === $type && preg_match( '/^(tt\d+|nm\d+|lnm-[a-z0-9-]+|co\d+)$/', $id ) ) {
				return $id;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'lunara_oscar_nominee_id_for_label' ) ) {
	function lunara_oscar_nominee_id_for_label( $label, $names_raw, $ids_raw ) {
		$label = (string) $label;
		if ( '' === trim( $label ) ) {
			return '';
		}

		$names = array_map( 'trim', preg_split( '/\|/', (string) $names_raw ) );
		$ids   = array_map( 'trim', preg_split( '/\|/', (string) $ids_raw ) );

		foreach ( $names as $index => $name ) {
			if ( lunara_oscar_text_matches( $label, $name ) && ! empty( $ids[ $index ] ) ) {
				$id = lunara_oscar_first_id_from_list( $ids[ $index ], 'name' );
				if ( '' !== $id ) {
					return $id;
				}
			}
		}

		return lunara_oscar_first_id_from_list( $ids_raw, 'name' );
	}
}

if ( ! function_exists( 'lunara_oscar_awards_table_exists' ) ) {
	function lunara_oscar_awards_table_exists() {
		static $exists = null;

		if ( null !== $exists ) {
			return $exists;
		}

		global $wpdb;
		$table  = $wpdb->prefix . 'academy_awards';
		$exists = ( $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) );

		return $exists;
	}
}

if ( ! function_exists( 'lunara_resolve_oscar_pick_ledger_url' ) ) {
	function lunara_resolve_oscar_pick_ledger_url( $post_id, $film = '', $person = '', $year = 0, $category = '' ) {
		$manual_url = esc_url_raw( (string) get_post_meta( $post_id, '_lunara_pick_oscar_entity_url', true ) );
		if ( '' !== $manual_url && false !== strpos( $manual_url, '/oscars/' ) ) {
			return $manual_url;
		}

		if ( lunara_oscar_awards_table_exists() ) {
			global $wpdb;
			$table     = $wpdb->prefix . 'academy_awards';
			$canonical = lunara_oscar_pick_canonical_category( $category );
			$ceremony  = lunara_oscar_display_year_to_ceremony( $year );
			$where     = array( '1=1' );
			$values    = array();

			if ( $ceremony > 0 ) {
				$where[]  = 'ceremony = %d';
				$values[] = $ceremony;
			}
			if ( '' !== $canonical ) {
				$where[]  = 'canonical_category = %s';
				$values[] = $canonical;
			}
			if ( '' !== trim( (string) $film ) ) {
				$where[]  = 'film LIKE %s';
				$values[] = '%' . $wpdb->esc_like( trim( (string) $film ) ) . '%';
			}

			if ( count( $where ) > 1 || '' !== trim( (string) $person ) ) {
				$sql = "SELECT ceremony, canonical_category, category, film, film_id, name, nominees, nominee_ids, winner FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY ceremony DESC, winner DESC, id DESC LIMIT 40';
				if ( ! empty( $values ) ) {
					$sql = $wpdb->prepare( $sql, $values );
				}
				$rows = $wpdb->get_results( $sql, ARRAY_A );

				if ( is_array( $rows ) ) {
					foreach ( $rows as $row ) {
						$row_person = trim( (string) ( $row['name'] ?? '' ) . ' ' . ( $row['nominees'] ?? '' ) );
						$row_film   = (string) ( $row['film'] ?? '' );

						if ( '' !== trim( (string) $person ) && lunara_oscar_text_matches( $person, $row_person ) ) {
							$person_id = lunara_oscar_nominee_id_for_label( $person, $row['nominees'] ?: $row['name'], $row['nominee_ids'] ?? '' );
							if ( '' !== $person_id ) {
								$url = lunara_oscar_ledger_url_from_id( $person_id );
								if ( '' !== $url ) {
									return $url;
								}
							}
						}

						if ( '' !== trim( (string) $film ) && lunara_oscar_text_matches( $film, $row_film ) ) {
							$title_id = lunara_oscar_first_id_from_list( $row['film_id'] ?? '', 'title' );
							if ( '' !== $title_id ) {
								$url = lunara_oscar_ledger_url_from_id( $title_id );
								if ( '' !== $url ) {
									return $url;
								}
							}
						}
					}
				}
			}
		}

		if ( '' !== lunara_oscar_pick_canonical_category( $category ) ) {
			$category_url = lunara_oscar_ledger_category_url( $category );
			if ( '' !== $category_url ) {
				return $category_url;
			}
		}

		$ceremony_url = lunara_oscar_ledger_ceremony_url( $year );
		if ( '' !== $ceremony_url ) {
			return $ceremony_url;
		}

		return home_url( '/oscars/' );
	}
}

if ( ! function_exists( 'lunara_resolve_oscar_fact_ledger_url' ) ) {
	function lunara_resolve_oscar_fact_ledger_url( $post_id, $category = '', $year = 0 ) {
		$manual_keys = array(
			'_lunara_fact_oscar_entity_url',
			'_lunara_fact_ledger_url',
			'_lunara_fact_entity_url',
		);
		foreach ( $manual_keys as $meta_key ) {
			$manual_url = esc_url_raw( (string) get_post_meta( $post_id, $meta_key, true ) );
			if ( '' !== $manual_url && false !== strpos( $manual_url, '/oscars/' ) ) {
				return $manual_url;
			}
		}

		$id_keys = array(
			'_lunara_fact_entity_id',
			'_lunara_fact_imdb_id',
			'_lunara_fact_title_id',
			'_lunara_fact_person_id',
		);
		foreach ( $id_keys as $meta_key ) {
			$entity_url = lunara_oscar_ledger_url_from_id( get_post_meta( $post_id, $meta_key, true ) );
			if ( '' !== $entity_url ) {
				return $entity_url;
			}
		}

		$title = get_the_title( $post_id );
		$body  = wp_strip_all_tags( get_post_field( 'post_content', $post_id ) );
		$text  = trim( $title . ' ' . $body );

		if ( preg_match( '/\b(tt\d+|nm\d+|lnm-[a-z0-9-]+|co\d+)\b/i', $text, $id_match ) ) {
			$entity_url = lunara_oscar_ledger_url_from_id( $id_match[1] );
			if ( '' !== $entity_url ) {
				return $entity_url;
			}
		}

		if ( lunara_oscar_awards_table_exists() ) {
			global $wpdb;
			$table = $wpdb->prefix . 'academy_awards';

			if ( preg_match_all( '/\b([A-Z][A-Za-z0-9\'&,. -]{1,90}?)\s*\((18|19|20)\d{2}\)/u', (string) $text, $film_matches, PREG_SET_ORDER ) ) {
				foreach ( $film_matches as $film_match ) {
					if ( ! preg_match( '/\((\d{4})\)/', $film_match[0], $year_match ) ) {
						continue;
					}

					$film       = html_entity_decode( $film_match[1], ENT_QUOTES, get_bloginfo( 'charset' ) );
					$film       = trim( preg_replace( '/\s+/', ' ', $film ), " \t\n\r\0\x0B\"'*.,:;-" );
					$award_year = $year_match[1];
					if ( strlen( $film ) < 2 ) {
						continue;
					}

					$row = $wpdb->get_row(
						$wpdb->prepare(
							"SELECT film_id FROM {$table} WHERE film LIKE %s AND year = %s ORDER BY ceremony DESC, winner DESC, id DESC LIMIT 1",
							'%' . $wpdb->esc_like( $film ) . '%',
							$award_year
						),
						ARRAY_A
					);

					if ( ! is_array( $row ) ) {
						$row = $wpdb->get_row(
							$wpdb->prepare(
								"SELECT film_id FROM {$table} WHERE film LIKE %s ORDER BY ceremony DESC, winner DESC, id DESC LIMIT 1",
								'%' . $wpdb->esc_like( $film ) . '%'
							),
							ARRAY_A
						);
					}

					$title_id = is_array( $row ) ? lunara_oscar_first_id_from_list( $row['film_id'] ?? '', 'title' ) : '';
					if ( '' !== $title_id ) {
						$url = lunara_oscar_ledger_url_from_id( $title_id );
						if ( '' !== $url ) {
							return $url;
						}
					}
				}
			}

			if ( preg_match( '/^(?:Composer|Director|Actor|Actress|Filmmaker|Producer|Writer)?\s*([A-Z][A-Za-z\'â€™.-]+(?:\s+[A-Z][A-Za-z\'â€™.-]+){1,3})\b/u', (string) $title, $person_match ) ) {
				$person = trim( $person_match[1] );
				$row    = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT name, nominees, nominee_ids FROM {$table} WHERE name LIKE %s OR nominees LIKE %s ORDER BY ceremony DESC, winner DESC, id DESC LIMIT 1",
						'%' . $wpdb->esc_like( $person ) . '%',
						'%' . $wpdb->esc_like( $person ) . '%'
					),
					ARRAY_A
				);
				$person_id = is_array( $row ) ? lunara_oscar_nominee_id_for_label( $person, $row['nominees'] ?: $row['name'], $row['nominee_ids'] ?? '' ) : '';
				if ( '' !== $person_id ) {
					$url = lunara_oscar_ledger_url_from_id( $person_id );
					if ( '' !== $url ) {
						return $url;
					}
				}
			}

			$text_key       = lunara_oscar_match_text( $text );
			$category_hints = array(
				'best supporting actress',
				'best supporting actor',
				'best adapted screenplay',
				'best original screenplay',
				'international feature film',
				'best picture',
				'best director',
				'best actor',
				'best actress',
				'adapted screenplay',
				'original screenplay',
				'cinematography',
				'film editing',
				'original score',
				'original song',
				'documentary feature',
				'animated feature',
				'visual effects',
				'costume design',
				'production design',
				'sound',
			);
			foreach ( $category_hints as $hint ) {
				if ( false !== strpos( $text_key, $hint ) && '' !== lunara_oscar_pick_canonical_category( $hint ) ) {
					$category_url = lunara_oscar_ledger_category_url( $hint );
					if ( '' !== $category_url ) {
						return $category_url;
					}
				}
			}
		}

		if ( '' !== lunara_oscar_pick_canonical_category( $category ) ) {
			$category_url = lunara_oscar_ledger_category_url( $category );
			if ( '' !== $category_url ) {
				return $category_url;
			}
		}

		$ceremony_url = lunara_oscar_ledger_ceremony_url( $year );
		if ( '' !== $ceremony_url ) {
			return $ceremony_url;
		}

		return home_url( '/oscars/' );
	}
}

/**
 * Render the Oscar Picks homepage carousel section.
 * Returns markup; call from front-page.php with: echo lunara_render_oscar_picks_carousel();
 */
if ( ! function_exists( 'lunara_render_oscar_picks_carousel' ) ) {
	function lunara_render_oscar_picks_carousel( $args = array() ) {
		$defaults = array(
			'kicker'   => __( 'Lunara Picks', 'lunara-film' ),
			'heading'  => __( 'Predicted winners â€” 98th Academy Awards', 'lunara-film' ),
			'summary'  => __( 'Behind the work, behind the scenes. The images you will not find anywhere else.', 'lunara-film' ),
			'cta_text' => __( 'See the full Oscar Ledger', 'lunara-film' ),
			'cta_url'  => home_url( '/oscars/' ),
			'count'    => 12,
		);
		$args  = lunara_repair_mojibake_args( wp_parse_args( $args, $defaults ), array( 'kicker', 'heading', 'summary', 'cta_text' ) );
		$query = lunara_get_oscar_picks( array( 'posts_per_page' => (int) $args['count'] ) );

		if ( ! $query->have_posts() ) {
			return '';
		}

		ob_start();
		?>
		<section class="lunara-home-section lunara-home-slot-oscar-picks lunara-oscar-picks-section" aria-label="Lunara Oscar Picks" data-lunara-carousel data-lunara-carousel-autoplay="0">
			<div class="lunara-home-section-head is-with-summary">
				<div>
					<p class="lunara-home-section-kicker"><?php echo esc_html( $args['kicker'] ); ?></p>
					<h2 class="lunara-home-section-title"><?php echo esc_html( $args['heading'] ); ?></h2>
					<?php if ( ! empty( $args['summary'] ) ) : ?>
						<p class="lunara-home-section-summary"><?php echo esc_html( $args['summary'] ); ?></p>
					<?php endif; ?>
				</div>
				<a class="lunara-section-link" href="<?php echo esc_url( $args['cta_url'] ); ?>"><?php echo esc_html( $args['cta_text'] ); ?></a>
			</div>

			<div class="lunara-oscar-picks-track" role="list">
				<?php while ( $query->have_posts() ) :
					$query->the_post();
					$pick_index  = max( 0, (int) $query->current_post );
					$pid         = get_the_ID();
					$film        = (string) get_post_meta( $pid, '_lunara_pick_film', true );
					$person      = (string) get_post_meta( $pid, '_lunara_pick_person', true );
					$year        = (int) get_post_meta( $pid, '_lunara_pick_ceremony_year', true );
					$status      = (string) get_post_meta( $pid, '_lunara_pick_status', true ) ?: 'predicted';
					$ledger      = (string) get_post_meta( $pid, '_lunara_pick_oscar_entity_url', true );
					$cat_terms   = get_the_terms( $pid, 'oscar_pick_category' );
					$category    = ( $cat_terms && ! is_wp_error( $cat_terms ) ) ? $cat_terms[0]->name : '';
					$card_url    = lunara_resolve_oscar_pick_ledger_url( $pid, $film, $person, $year, $category );
					$thumb_url   = has_post_thumbnail( $pid ) ? get_the_post_thumbnail_url( $pid, 'newspack-article-block-landscape-intermediate' ) : '';
					$thumb_attrs = array(
						'class'    => 'lunara-oscar-pick-card-image',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '(max-width: 420px) 92vw, (max-width: 760px) 44vw, (max-width: 1180px) 42vw, 360px',
					);
					?>
					<article class="lunara-oscar-pick-card is-status-<?php echo esc_attr( $status ); ?>" role="listitem">
						<a class="lunara-oscar-pick-card-link" href="<?php echo esc_url( $card_url ); ?>">
							<div class="lunara-oscar-pick-card-media">
								<?php if ( has_post_thumbnail( $pid ) ) : ?>
									<?php echo get_the_post_thumbnail( $pid, 'newspack-article-block-landscape-intermediate', $thumb_attrs ); ?>
								<?php else : ?>
									<div class="lunara-oscar-pick-card-placeholder"><span><?php echo esc_html( $category ?: 'Pick' ); ?></span></div>
								<?php endif; ?>
								<span class="lunara-oscar-pick-card-status"><?php echo esc_html( strtoupper( $status ) ); ?></span>
							</div>
							<div class="lunara-oscar-pick-card-copy">
								<?php if ( '' !== $category ) : ?>
									<p class="lunara-oscar-pick-card-kicker"><?php echo esc_html( $category ); ?></p>
								<?php endif; ?>
								<h3 class="lunara-oscar-pick-card-title">
									<?php
									if ( '' !== $person && '' !== $film ) {
										echo esc_html( $person ) . ' &mdash; <em>' . esc_html( $film ) . '</em>';
									} elseif ( '' !== $film ) {
										echo '<em>' . esc_html( $film ) . '</em>';
									} elseif ( '' !== $person ) {
										echo esc_html( $person );
									} else {
										echo esc_html( get_the_title( $pid ) );
									}
									?>
								</h3>
								<?php if ( $year > 0 ) : ?>
									<p class="lunara-oscar-pick-card-meta"><?php echo esc_html( sprintf( __( '%d Academy Awards', 'lunara-film' ), $year ) ); ?></p>
								<?php endif; ?>
								<p class="lunara-oscar-pick-card-ledger"><?php esc_html_e( 'Open in Ledger', 'lunara-film' ); ?></p>
							</div>
						</a>
					</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}


/* ============================================================================
 * LUNARA OSCAR FACTS â€” CPT, taxonomy, meta box, importer, query, carousel.
 * Added 2026-05-10 (Sitting 2). Pairs with Oscar Picks but text-forward.
 *
 * Design contract:
 *   - Picks carousel = image-forward (the differentiator photo IS the card).
 *   - Facts carousel = text-forward (the language IS the card; image optional).
 *   - Together they create a two-tier Oscar storytelling rhythm on the homepage.
 *
 * Source content lives in OSCARS FACTS AND INTERESTING RESULTS.md and gets
 * imported via Tools â†’ Import Oscar Facts (or auto-seeded from a theme-shipped
 * copy at <theme>/_oscar-facts-source.md if present).
 * ============================================================================ */

if ( ! function_exists( 'lunara_register_oscar_fact_cpt' ) ) {
	function lunara_register_oscar_fact_cpt() {
		register_post_type( 'oscar_fact', array(
			'labels' => array(
				'name'          => __( 'Oscar Facts', 'lunara-film' ),
				'singular_name' => __( 'Oscar Fact', 'lunara-film' ),
				'add_new'       => __( 'Add New Fact', 'lunara-film' ),
				'add_new_item'  => __( 'Add New Oscar Fact', 'lunara-film' ),
				'edit_item'     => __( 'Edit Oscar Fact', 'lunara-film' ),
				'view_item'     => __( 'View Oscar Fact', 'lunara-film' ),
				'menu_name'     => __( 'Oscar Facts', 'lunara-film' ),
				'all_items'     => __( 'All Facts', 'lunara-film' ),
				'search_items'  => __( 'Search Facts', 'lunara-film' ),
				'not_found'     => __( 'No facts yet â€” try the importer.', 'lunara-film' ),
			),
			'public'              => true,
			'has_archive'         => 'oscar-facts',
			'rewrite'             => array( 'slug' => 'oscar-facts', 'with_front' => false ),
			'menu_icon'           => 'dashicons-lightbulb',
			'menu_position'       => 23,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'taxonomies'          => array( 'oscar_fact_category' ),
			'show_in_rest'        => true,
			'capability_type'     => 'post',
		) );

		register_taxonomy( 'oscar_fact_category', 'oscar_fact', array(
			'labels' => array(
				'name'          => __( 'Fact Categories', 'lunara-film' ),
				'singular_name' => __( 'Fact Category', 'lunara-film' ),
				'menu_name'     => __( 'Categories', 'lunara-film' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'oscar-facts/category', 'with_front' => false ),
		) );
	}
	add_action( 'init', 'lunara_register_oscar_fact_cpt' );
}

/**
 * Seed default fact categories. Idempotent.
 */
if ( ! function_exists( 'lunara_seed_oscar_fact_categories' ) ) {
	function lunara_seed_oscar_fact_categories() {
		if ( get_option( 'lunara_oscar_fact_categories_seeded' ) ) {
			return;
		}
		if ( ! taxonomy_exists( 'oscar_fact_category' ) ) {
			return;
		}
		$defaults = array(
			'Records'              => 'records',
			'Firsts'               => 'firsts',
			'Family Dynasties'     => 'family-dynasties',
			'Genre Breakthroughs'  => 'genre-breakthroughs',
			'Surprising Nominations' => 'surprising-nominations',
			'Best Picture History' => 'best-picture-history',
			'Acting Oscars'        => 'acting-oscars',
			'Behind the Scenes'    => 'behind-the-scenes',
			'Trivia'               => 'trivia',
		);
		foreach ( $defaults as $name => $slug ) {
			if ( ! term_exists( $slug, 'oscar_fact_category' ) ) {
				wp_insert_term( $name, 'oscar_fact_category', array( 'slug' => $slug ) );
			}
		}
		update_option( 'lunara_oscar_fact_categories_seeded', 1 );
	}
	add_action( 'init', 'lunara_seed_oscar_fact_categories', 20 );
}

/**
 * Meta box: featured film/person + citation source.
 */
if ( ! function_exists( 'lunara_oscar_fact_add_meta_box' ) ) {
	function lunara_oscar_fact_add_meta_box() {
		add_meta_box(
			'lunara_oscar_fact_details',
			__( 'Fact Details', 'lunara-film' ),
			'lunara_oscar_fact_meta_box_callback',
			'oscar_fact',
			'side',
			'high'
		);
	}
	add_action( 'add_meta_boxes', 'lunara_oscar_fact_add_meta_box' );
}

if ( ! function_exists( 'lunara_oscar_fact_meta_box_callback' ) ) {
	function lunara_oscar_fact_meta_box_callback( $post ) {
		wp_nonce_field( 'lunara_oscar_fact_save', 'lunara_oscar_fact_nonce' );
		$attribution = (string) get_post_meta( $post->ID, '_lunara_fact_attribution', true );
		$citation    = (string) get_post_meta( $post->ID, '_lunara_fact_citation', true );
		$year        = (string) get_post_meta( $post->ID, '_lunara_fact_year', true );
		$visual_ok   = '1' === (string) get_post_meta( $post->ID, '_lunara_fact_visual_verified', true );
		?>
		<p>
			<label for="lunara_fact_attribution"><strong><?php esc_html_e( 'Subject (film or person)', 'lunara-film' ); ?></strong></label><br>
			<input type="text" id="lunara_fact_attribution" name="lunara_fact_attribution" value="<?php echo esc_attr( $attribution ); ?>" class="widefat" placeholder="Network (1976) / Beatrice Straight" />
			<span class="description"><?php esc_html_e( 'Optional. Surfaces under the fact text on cards.', 'lunara-film' ); ?></span>
		</p>
		<p>
			<label for="lunara_fact_year"><strong><?php esc_html_e( 'Year (optional)', 'lunara-film' ); ?></strong></label><br>
			<input type="number" id="lunara_fact_year" name="lunara_fact_year" value="<?php echo esc_attr( $year ); ?>" class="widefat" min="1900" max="2100" step="1" placeholder="1976" />
		</p>
		<p>
			<label for="lunara_fact_citation"><strong><?php esc_html_e( 'Citation / Source URL (optional)', 'lunara-film' ); ?></strong></label><br>
			<input type="url" id="lunara_fact_citation" name="lunara_fact_citation" value="<?php echo esc_attr( $citation ); ?>" class="widefat" placeholder="https://" />
		</p>
		<p>
			<label>
				<input type="checkbox" name="lunara_fact_visual_verified" value="1" <?php checked( $visual_ok ); ?> />
				<strong><?php esc_html_e( 'Verified public visual', 'lunara-film' ); ?></strong>
			</label><br>
			<span class="description"><?php esc_html_e( 'Only check this when the featured image directly matches the fact. Unchecked facts render as text-led public cards.', 'lunara-film' ); ?></span>
		</p>
		<?php
	}
}

if ( ! function_exists( 'lunara_oscar_fact_save_meta' ) ) {
	function lunara_oscar_fact_save_meta( $post_id ) {
		if ( ! isset( $_POST['lunara_oscar_fact_nonce'] ) || ! wp_verify_nonce( $_POST['lunara_oscar_fact_nonce'], 'lunara_oscar_fact_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'_lunara_fact_attribution' => isset( $_POST['lunara_fact_attribution'] ) ? sanitize_text_field( wp_unslash( $_POST['lunara_fact_attribution'] ) ) : '',
			'_lunara_fact_citation'    => isset( $_POST['lunara_fact_citation'] ) ? esc_url_raw( wp_unslash( $_POST['lunara_fact_citation'] ) ) : '',
			'_lunara_fact_year'        => isset( $_POST['lunara_fact_year'] ) ? absint( $_POST['lunara_fact_year'] ) : 0,
			'_lunara_fact_visual_verified' => isset( $_POST['lunara_fact_visual_verified'] ) ? '1' : '',
		);

		foreach ( $fields as $key => $value ) {
			if ( '' === $value || 0 === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}
	add_action( 'save_post_oscar_fact', 'lunara_oscar_fact_save_meta' );
}

/**
 * Admin list columns: subject, year.
 */
if ( ! function_exists( 'lunara_oscar_fact_admin_columns' ) ) {
	function lunara_oscar_fact_admin_columns( $cols ) {
		$new = array();
		foreach ( $cols as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['lunara_fact_attribution'] = __( 'Subject', 'lunara-film' );
				$new['lunara_fact_year']        = __( 'Year', 'lunara-film' );
				$new['lunara_fact_visual']      = __( 'Public Visual', 'lunara-film' );
			}
		}
		return $new;
	}
	add_filter( 'manage_oscar_fact_posts_columns', 'lunara_oscar_fact_admin_columns' );

	function lunara_oscar_fact_admin_column_value( $column, $post_id ) {
		switch ( $column ) {
			case 'lunara_fact_attribution':
				echo esc_html( (string) get_post_meta( $post_id, '_lunara_fact_attribution', true ) ?: 'â€”' );
				break;
			case 'lunara_fact_year':
				$year = (int) get_post_meta( $post_id, '_lunara_fact_year', true );
				echo $year > 0 ? esc_html( (string) $year ) : 'â€”';
				break;
			case 'lunara_fact_visual':
				$has_image = has_post_thumbnail( $post_id );
				$verified  = '1' === (string) get_post_meta( $post_id, '_lunara_fact_visual_verified', true );
				if ( $verified && $has_image ) {
					esc_html_e( 'Verified', 'lunara-film' );
				} elseif ( $has_image ) {
					esc_html_e( 'Hidden until verified', 'lunara-film' );
				} else {
					echo '&mdash;';
				}
				break;
		}
	}
	add_action( 'manage_oscar_fact_posts_custom_column', 'lunara_oscar_fact_admin_column_value', 10, 2 );
}

/**
 * Query helper.
 */
if ( ! function_exists( 'lunara_get_oscar_facts' ) ) {
	function lunara_get_oscar_facts( $args = array() ) {
		$defaults = array(
			'posts_per_page' => 8,
			'category'       => '',
			'orderby'        => 'rand',
		);
		$args = wp_parse_args( $args, $defaults );

		$query_args = array(
			'post_type'           => 'oscar_fact',
			'posts_per_page'      => (int) $args['posts_per_page'],
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => 'rand' === $args['orderby'] ? 'rand' : 'date',
			'order'               => 'DESC',
		);

		if ( ! empty( $args['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'oscar_fact_category',
					'field'    => is_numeric( $args['category'] ) ? 'term_id' : 'slug',
					'terms'    => $args['category'],
				),
			);
		}

		return new WP_Query( $query_args );
	}
}

/**
 * Render the Oscar Facts homepage carousel â€” text-forward.
 * Picks lead with image; Facts lead with language.
 */
if ( ! function_exists( 'lunara_render_oscar_facts_carousel' ) ) {
	function lunara_render_oscar_facts_carousel( $args = array() ) {
		$defaults = array(
			'kicker'   => __( 'Did you know?', 'lunara-film' ),
			'heading'  => __( 'Records, firsts, and Oscar arguments that still live on', 'lunara-film' ),
			'summary'  => '',
			'cta_text' => __( 'Explore the full Lunara Oscar Ledger', 'lunara-film' ),
			'cta_url'  => home_url( '/oscars/' ),
			'count'    => 8,
		);
		$args  = lunara_repair_mojibake_args( wp_parse_args( $args, $defaults ), array( 'kicker', 'heading', 'summary', 'cta_text' ) );
		$query = lunara_get_oscar_facts( array( 'posts_per_page' => (int) $args['count'], 'orderby' => 'rand' ) );

		if ( ! $query->have_posts() ) {
			return '';
		}

		ob_start();
		?>
		<section class="lunara-home-section lunara-home-slot-oscar-facts lunara-oscar-facts-section" aria-label="Oscar Facts" data-lunara-carousel data-lunara-carousel-autoplay="0">
			<div class="lunara-home-section-head is-with-summary">
				<div>
					<p class="lunara-home-section-kicker"><?php echo esc_html( $args['kicker'] ); ?></p>
					<h2 class="lunara-home-section-title"><?php echo esc_html( $args['heading'] ); ?></h2>
					<?php if ( ! empty( $args['summary'] ) ) : ?>
						<p class="lunara-home-section-summary"><?php echo esc_html( $args['summary'] ); ?></p>
					<?php endif; ?>
				</div>
				<a class="lunara-section-link" href="<?php echo esc_url( $args['cta_url'] ); ?>"><?php echo esc_html( $args['cta_text'] ); ?></a>
			</div>

			<div class="lunara-oscar-facts-track" role="list">
				<?php while ( $query->have_posts() ) :
					$query->the_post();
					$fact_index  = max( 0, (int) $query->current_post );
					$pid         = get_the_ID();
					$attribution = (string) get_post_meta( $pid, '_lunara_fact_attribution', true );
					$year        = (int) get_post_meta( $pid, '_lunara_fact_year', true );
					$cat_terms   = get_the_terms( $pid, 'oscar_fact_category' );
					$category    = ( $cat_terms && ! is_wp_error( $cat_terms ) ) ? $cat_terms[0]->name : '';
					$body        = wp_strip_all_tags( get_the_content() );
					$excerpt_more = html_entity_decode( '&hellip;', ENT_QUOTES, 'UTF-8' );
					$body_short  = lunara_repair_mojibake_text( wp_trim_words( $body, 28, $excerpt_more ) );
					$visual_ok   = '1' === (string) get_post_meta( $pid, '_lunara_fact_visual_verified', true );
					$has_image   = $visual_ok && has_post_thumbnail( $pid );
					$card_class  = 'lunara-oscar-fact-card' . ( $has_image ? ' has-poster' : '' );
					$card_url    = lunara_resolve_oscar_fact_ledger_url( $pid, $category, $year );
					$thumb_url   = $has_image ? get_the_post_thumbnail_url( $pid, 'newspack-article-block-landscape-small' ) : '';
					$thumb_attrs = array(
						'class'    => 'lunara-oscar-fact-card-poster-image',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '(max-width: 420px) 92vw, (max-width: 760px) 44vw, (max-width: 1180px) 42vw, 360px',
					);
					?>
					<article class="<?php echo esc_attr( $card_class ); ?>" role="listitem">
						<a class="lunara-oscar-fact-card-link" href="<?php echo esc_url( $card_url ); ?>">
							<?php if ( $has_image ) : ?>
								<div class="lunara-oscar-fact-card-poster">
									<?php echo get_the_post_thumbnail( $pid, 'newspack-article-block-landscape-small', $thumb_attrs ); ?>
								</div>
							<?php endif; ?>
							<div class="lunara-oscar-fact-card-text">
								<?php if ( '' !== $category ) : ?>
									<p class="lunara-oscar-fact-card-kicker"><?php echo esc_html( $category ); ?></p>
								<?php endif; ?>
								<h3 class="lunara-oscar-fact-card-title"><?php echo esc_html( get_the_title( $pid ) ); ?></h3>
								<?php if ( '' !== $body_short ) : ?>
									<p class="lunara-oscar-fact-card-body"><?php echo esc_html( $body_short ); ?></p>
								<?php endif; ?>
								<div class="lunara-oscar-fact-card-foot">
									<?php if ( '' !== $attribution ) : ?>
										<span class="lunara-oscar-fact-card-attribution"><?php echo esc_html( $attribution ); ?><?php if ( $year > 0 ) : ?> &middot; <?php echo esc_html( (string) $year ); ?><?php endif; ?></span>
									<?php elseif ( $year > 0 ) : ?>
										<span class="lunara-oscar-fact-card-attribution"><?php echo esc_html( (string) $year ); ?></span>
									<?php endif; ?>
									<span class="lunara-oscar-fact-card-cta"><?php esc_html_e( 'Open in Ledger', 'lunara-film' ); ?> &rarr;</span>
								</div>
							</div>
						</a>
					</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

/* ----------------------------------------------------------------------------
 * IMPORTER â€” Tools â†’ Import Oscar Facts
 *
 * Parses the markdown source (theme-shipped at <theme>/_oscar-facts-source.md
 * OR pasted into the textarea) and creates one oscar_fact post per fact.
 * Idempotent: skips facts whose generated title already exists.
 * ---------------------------------------------------------------------------- */

if ( ! function_exists( 'lunara_oscar_facts_register_importer_page' ) ) {
	function lunara_oscar_facts_register_importer_page() {
		add_submenu_page(
			'edit.php?post_type=oscar_fact',
			__( 'Import Oscar Facts', 'lunara-film' ),
			__( 'Import from Markdown', 'lunara-film' ),
			'manage_options',
			'lunara-oscar-facts-import',
			'lunara_oscar_facts_render_importer_page'
		);
	}
	add_action( 'admin_menu', 'lunara_oscar_facts_register_importer_page' );
}

if ( ! function_exists( 'lunara_oscar_facts_render_importer_page' ) ) {
	function lunara_oscar_facts_render_importer_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'lunara-film' ) );
		}

		$result = null;
		if ( isset( $_POST['lunara_facts_importer_nonce'] ) && wp_verify_nonce( $_POST['lunara_facts_importer_nonce'], 'lunara_facts_import_run' ) ) {
			$source_choice = isset( $_POST['lunara_facts_source'] ) ? sanitize_key( $_POST['lunara_facts_source'] ) : 'file';
			$markdown      = '';
			if ( 'paste' === $source_choice && isset( $_POST['lunara_facts_paste'] ) ) {
				$markdown = wp_unslash( $_POST['lunara_facts_paste'] );
			} else {
				$path = trailingslashit( get_stylesheet_directory() ) . '_oscar-facts-source.md';
				if ( file_exists( $path ) ) {
					$markdown = (string) file_get_contents( $path );
				}
			}

			if ( '' === trim( $markdown ) ) {
				$result = array( 'error' => __( 'No markdown source found. Either upload _oscar-facts-source.md to the theme directory or paste markdown below.', 'lunara-film' ) );
			} else {
				$result = lunara_oscar_facts_run_import( $markdown );
			}
		}

		$source_path        = trailingslashit( get_stylesheet_directory() ) . '_oscar-facts-source.md';
		$source_path_exists = file_exists( $source_path );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import Oscar Facts from Markdown', 'lunara-film' ); ?></h1>

			<?php if ( is_array( $result ) ) : ?>
				<?php if ( ! empty( $result['error'] ) ) : ?>
					<div class="notice notice-error"><p><?php echo esc_html( $result['error'] ); ?></p></div>
				<?php else : ?>
					<div class="notice notice-success">
						<p>
							<strong><?php echo esc_html( sprintf( __( 'Imported %d new facts.', 'lunara-film' ), (int) $result['created'] ) ); ?></strong>
							<?php if ( ! empty( $result['skipped'] ) ) : ?>
								<?php echo esc_html( sprintf( __( 'Skipped %d facts (titles already exist).', 'lunara-film' ), (int) $result['skipped'] ) ); ?>
							<?php endif; ?>
						</p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<p><?php esc_html_e( 'The importer parses your OSCARS FACTS AND INTERESTING RESULTS markdown into individual Oscar Fact posts. Each ## section becomes a category assignment; each bullet or paragraph becomes one fact. Idempotent â€” re-running skips facts that already exist.', 'lunara-film' ); ?></p>

			<form method="post" action="">
				<?php wp_nonce_field( 'lunara_facts_import_run', 'lunara_facts_importer_nonce' ); ?>

				<h2 style="margin-top:24px;"><?php esc_html_e( 'Source', 'lunara-film' ); ?></h2>

				<p>
					<label>
						<input type="radio" name="lunara_facts_source" value="file" <?php checked( $source_path_exists ); ?> <?php disabled( ! $source_path_exists ); ?> />
						<strong><?php esc_html_e( 'Read from theme file:', 'lunara-film' ); ?></strong>
						<code><?php echo esc_html( $source_path ); ?></code>
						<?php if ( $source_path_exists ) : ?>
							<span style="color:#27ae60;"><?php esc_html_e( '(found)', 'lunara-film' ); ?></span>
						<?php else : ?>
							<span style="color:#dc3232;"><?php esc_html_e( '(not present â€” scp the markdown file there first)', 'lunara-film' ); ?></span>
						<?php endif; ?>
					</label>
				</p>

				<p>
					<label>
						<input type="radio" name="lunara_facts_source" value="paste" <?php checked( ! $source_path_exists ); ?> />
						<strong><?php esc_html_e( 'Paste markdown directly:', 'lunara-film' ); ?></strong>
					</label>
				</p>
				<textarea name="lunara_facts_paste" rows="14" class="widefat" placeholder="<?php esc_attr_e( 'Paste your OSCARS FACTS AND INTERESTING RESULTS markdown here...', 'lunara-film' ); ?>"></textarea>

				<p style="margin-top:18px;">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Run Import', 'lunara-film' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}
}

/**
 * Markdown parser. Returns ['created' => int, 'skipped' => int].
 *
 * Logic:
 *   - ## Header lines set the active category for the chunks that follow.
 *   - Bullet lines (- or *) become individual facts.
 *   - Paragraph lines (no header, no bullet, length > 40 chars) also become facts.
 *   - UPPERCASE LABEL\nValue pairs (the OSCAR FIRSTS section) become facts where
 *     title = sentence case of the label, body = the value line.
 *   - Title is generated from the first sentence (capped ~70 chars).
 *   - Idempotent: skip if a post with that title already exists.
 */
if ( ! function_exists( 'lunara_oscar_facts_run_import' ) ) {
	function lunara_oscar_facts_run_import( $markdown ) {
		$markdown = str_replace( "\r\n", "\n", (string) $markdown );
		$lines    = explode( "\n", $markdown );

		$created          = 0;
		$skipped          = 0;
		$active_category  = 'trivia';
		$active_cat_label = 'Trivia';
		$pending_label    = ''; // For UPPERCASE LABEL\nValue pattern.

		// Map header text to category slug. Fuzzy.
		$cat_map = array(
			'genre breakthroughs'      => array( 'genre-breakthroughs', 'Genre Breakthroughs' ),
			'surprising nominations'   => array( 'surprising-nominations', 'Surprising Nominations' ),
			'films that changed'       => array( 'best-picture-history', 'Best Picture History' ),
			'noteworthy performances'  => array( 'acting-oscars', 'Acting Oscars' ),
			'behind-the-scenes firsts' => array( 'behind-the-scenes', 'Behind the Scenes' ),
			'great movie factoids'     => array( 'trivia', 'Trivia' ),
			'more nuanced'             => array( 'trivia', 'Trivia' ),
			'extra firsts'             => array( 'firsts', 'Firsts' ),
			'interesting oscar facts'  => array( 'trivia', 'Trivia' ),
			'oscar firsts'             => array( 'firsts', 'Firsts' ),
		);

		foreach ( $lines as $raw_line ) {
			$line = trim( $raw_line );

			// Header? Update active category.
			if ( preg_match( '/^#+\s+(.+)$/', $line, $h ) ) {
				$header = strtolower( trim( $h[1] ) );
				foreach ( $cat_map as $needle => $pair ) {
					if ( false !== strpos( $header, $needle ) ) {
						$active_category  = $pair[0];
						$active_cat_label = $pair[1];
						break;
					}
				}
				$pending_label = '';
				continue;
			}

			// Skip junk.
			if ( '' === $line || 0 === strpos( $line, 'Â©' ) || 0 === strpos( $line, '1.18' ) ) {
				$pending_label = '';
				continue;
			}

			// UPPERCASE LABEL? (3+ chars, mostly uppercase, no period).
			$is_upper_label = (
				strlen( $line ) >= 3 &&
				$line === strtoupper( $line ) &&
				! preg_match( '/[.!?,]$/', $line ) &&
				! preg_match( '/^[-*]/', $line ) &&
				preg_match( '/[A-Z]/', $line )
			);
			if ( $is_upper_label ) {
				$pending_label = $line;
				continue;
			}

			// If we have a pending label, combine with this line as one fact.
			if ( '' !== $pending_label ) {
				$title = ucwords( strtolower( $pending_label ) );
				$body  = $line;
				$pending_label = '';
				$result = lunara_oscar_facts_create_fact( $title, $body, $active_category, $active_cat_label );
				if ( $result ) {
					$created++;
				} else {
					$skipped++;
				}
				continue;
			}

			// Bullet?
			$is_bullet = preg_match( '/^[-*]\s+(.+)$/', $line, $b );
			if ( $is_bullet ) {
				$body = trim( $b[1] );
				if ( strlen( $body ) < 25 ) {
					continue;
				}
				$title  = lunara_oscar_facts_derive_title( $body );
				$result = lunara_oscar_facts_create_fact( $title, $body, $active_category, $active_cat_label );
				if ( $result ) {
					$created++;
				} else {
					$skipped++;
				}
				continue;
			}

			// Plain paragraph (long enough to be substantive).
			if ( strlen( $line ) >= 60 ) {
				$title  = lunara_oscar_facts_derive_title( $line );
				$result = lunara_oscar_facts_create_fact( $title, $line, $active_category, $active_cat_label );
				if ( $result ) {
					$created++;
				} else {
					$skipped++;
				}
			}
		}

		return array( 'created' => $created, 'skipped' => $skipped );
	}
}

if ( ! function_exists( 'lunara_oscar_facts_derive_title' ) ) {
	function lunara_oscar_facts_derive_title( $body ) {
		// First sentence, capped at ~70 chars.
		$body = trim( wp_strip_all_tags( $body ) );
		$body = preg_replace( '/\s+/', ' ', $body );
		$first_sentence = preg_split( '/(?<=[.!?])\s/', $body, 2 )[0];
		if ( strlen( $first_sentence ) > 78 ) {
			$first_sentence = rtrim( substr( $first_sentence, 0, 75 ), ' ,;:' ) . 'â€¦';
		}
		return $first_sentence;
	}
}

if ( ! function_exists( 'lunara_oscar_facts_create_fact' ) ) {
	function lunara_oscar_facts_create_fact( $title, $body, $category_slug, $category_label ) {
		$title = trim( wp_strip_all_tags( $title ) );
		$body  = trim( wp_strip_all_tags( $body ) );
		if ( '' === $title || '' === $body ) {
			return false;
		}

		// Idempotency: skip if a fact with this exact title already exists.
		$existing = get_page_by_title( $title, OBJECT, 'oscar_fact' );
		if ( $existing instanceof WP_Post ) {
			return false;
		}

		$post_id = wp_insert_post( array(
			'post_type'    => 'oscar_fact',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $body,
		) );

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			return false;
		}

		// Ensure the category term exists, then assign.
		if ( ! term_exists( $category_slug, 'oscar_fact_category' ) ) {
			wp_insert_term( $category_label, 'oscar_fact_category', array( 'slug' => $category_slug ) );
		}
		wp_set_object_terms( $post_id, $category_slug, 'oscar_fact_category', false );

		return $post_id;
	}
}


/* ============================================================================
 * LUNARA OSCAR FACTS â€” markdown cleanup + image auto-fill (added 2026-05-10)
 *
 * Two related concerns:
 *   1. The original importer left literal markdown markers (*Film Title*,
 *      [link](url), etc.) inside post titles and bodies. Adds a clean
 *      strip + a one-click "Clean Existing Facts" admin action.
 *   2. Auto-fills featured images via Path 2 (TMDB film backdrops for
 *      film-related facts, Wikipedia article portraits for person-related
 *      facts). Idempotent â€” never overwrites a manually-set featured image.
 *
 * Both actions live on the existing Oscar Facts â†’ Import from Markdown
 * admin page as additional buttons.
 *
 * Reuses AAT_TMDB_API_KEY constant defined in the academy-awards plugin.
 * ============================================================================ */

/**
 * Strip markdown formatting from text. Returns clean plain text.
 * Preserves the inner text of italics/bold/links â€” drops only the markers.
 */
if ( ! function_exists( 'lunara_strip_oscar_fact_markdown' ) ) {
	function lunara_strip_oscar_fact_markdown( $text ) {
		$text = (string) $text;
		// Strip [text](url) links â†’ keep just the text.
		$text = preg_replace( '/\[([^\]]+)\]\([^\)]+\)/', '$1', $text );
		// Strip [reference] markers like [nytimes].
		$text = preg_replace( '/\[[^\]]+\]/', '', $text );
		// Strip bold markers (**text** or __text__) â†’ keep inner.
		$text = preg_replace( '/(\*\*|__)(.+?)\1/s', '$2', $text );
		// Strip italic markers (*text* or _text_) â†’ keep inner.
		$text = preg_replace( '/(\*|_)([^*_\s][^*_]*?[^*_\s]|\S)\1/s', '$2', $text );
		// Strip stray asterisks/underscores that survived above.
		$text = str_replace( array( '**', '__' ), '', $text );
		// Collapse whitespace.
		$text = preg_replace( '/\s+/', ' ', $text );
		return trim( $text );
	}
}

/**
 * Extract film titles from raw markdown source. Looks for *Title* patterns.
 * Returns array of unique cleaned titles.
 */
if ( ! function_exists( 'lunara_oscar_fact_extract_film_titles' ) ) {
	function lunara_oscar_fact_extract_film_titles( $raw_text ) {
		$titles = array();
		if ( preg_match_all( '/\*([^*\n]{2,80})\*/', (string) $raw_text, $matches ) ) {
			foreach ( $matches[1] as $m ) {
				$t = trim( $m );
				// Skip very short or obviously non-film matches.
				if ( strlen( $t ) < 2 || preg_match( '/^\d+$/', $t ) ) {
					continue;
				}
				// Title-cased? (Films usually start with capital.)
				if ( preg_match( '/^[A-Z]/', $t ) ) {
					$titles[ $t ] = $t;
				}
			}
		}
		return array_values( $titles );
	}
}

/**
 * Override: re-import (or clean existing) using markdown stripper.
 * Wraps the original create function to use cleaned text.
 */
if ( ! function_exists( 'lunara_oscar_facts_clean_existing_markdown' ) ) {
	function lunara_oscar_facts_clean_existing_markdown() {
		$query = new WP_Query( array(
			'post_type'      => 'oscar_fact',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'no_found_rows'  => true,
			'fields'         => 'ids',
		) );

		$cleaned = 0;
		foreach ( $query->posts as $pid ) {
			$post = get_post( $pid );
			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			$orig_title = (string) $post->post_title;
			$orig_body  = (string) $post->post_content;

			// Extract any film titles BEFORE stripping (for later auto-fill).
			$films_in_title = lunara_oscar_fact_extract_film_titles( $orig_title );
			$films_in_body  = lunara_oscar_fact_extract_film_titles( $orig_body );
			$detected       = array_unique( array_merge( $films_in_title, $films_in_body ) );
			if ( ! empty( $detected ) ) {
				update_post_meta( $pid, '_lunara_fact_detected_films', $detected );
			}

			$new_title = lunara_strip_oscar_fact_markdown( $orig_title );
			$new_body  = lunara_strip_oscar_fact_markdown( $orig_body );

			if ( $new_title !== $orig_title || $new_body !== $orig_body ) {
				wp_update_post( array(
					'ID'           => $pid,
					'post_title'   => $new_title,
					'post_content' => $new_body,
				) );
				$cleaned++;
			}
		}
		return $cleaned;
	}
}

/* ----------------------------------------------------------------------------
 * IMAGE AUTO-FILL â€” Path 2 (TMDB + Wikimedia)
 * ---------------------------------------------------------------------------- */

/**
 * Search TMDB for a film, return the URL of the best-quality backdrop.
 * Returns '' if no result, no API key, or no backdrop.
 */
if ( ! function_exists( 'lunara_tmdb_search_film_backdrop' ) ) {
	function lunara_tmdb_search_film_backdrop( $title ) {
		$title = trim( (string) $title );
		if ( '' === $title || ! defined( 'AAT_TMDB_API_KEY' ) || '' === AAT_TMDB_API_KEY ) {
			return '';
		}

		$search_url = add_query_arg(
			array(
				'api_key' => AAT_TMDB_API_KEY,
				'query'   => $title,
			),
			'https://api.themoviedb.org/3/search/movie'
		);

		$resp = wp_remote_get( $search_url, array( 'timeout' => 8 ) );
		if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
			return '';
		}
		$body = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( ! is_array( $body ) || empty( $body['results'] ) ) {
			return '';
		}

		// Take the first result with a backdrop_path.
		foreach ( $body['results'] as $movie ) {
			if ( ! empty( $movie['backdrop_path'] ) ) {
				return 'https://image.tmdb.org/t/p/original' . $movie['backdrop_path'];
			}
		}

		// Fallback: poster.
		foreach ( $body['results'] as $movie ) {
			if ( ! empty( $movie['poster_path'] ) ) {
				return 'https://image.tmdb.org/t/p/original' . $movie['poster_path'];
			}
		}

		return '';
	}
}

/**
 * Look up a Wikipedia article page image (typically the infobox portrait).
 * Returns image URL or ''.
 */
if ( ! function_exists( 'lunara_wikipedia_pageimage' ) ) {
	function lunara_wikipedia_pageimage( $name ) {
		$name = trim( (string) $name );
		if ( strlen( $name ) < 3 ) {
			return '';
		}

		$url = add_query_arg(
			array(
				'action'      => 'query',
				'prop'        => 'pageimages',
				'format'      => 'json',
				'pithumbsize' => 1200,
				'titles'      => $name,
				'redirects'   => 1,
			),
			'https://en.wikipedia.org/w/api.php'
		);

		$resp = wp_remote_get( $url, array(
			'timeout'    => 8,
			'user-agent' => 'LunaraFilm/1.0 (https://lunarafilm.com; daltino1@gmail.com)',
		) );
		if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
			return '';
		}
		$body = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( ! is_array( $body ) || empty( $body['query']['pages'] ) ) {
			return '';
		}

		foreach ( $body['query']['pages'] as $page ) {
			if ( ! empty( $page['thumbnail']['source'] ) ) {
				return (string) $page['thumbnail']['source'];
			}
		}

		return '';
	}
}

/**
 * Sideload an image URL into the media library and attach to a post as
 * its featured image. Returns attachment ID or false.
 */
if ( ! function_exists( 'lunara_sideload_image_to_post' ) ) {
	function lunara_sideload_image_to_post( $image_url, $post_id, $description = '' ) {
		if ( '' === $image_url || $post_id <= 0 ) {
			return false;
		}
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$attachment_id = media_sideload_image( $image_url, $post_id, $description, 'id' );
		if ( is_wp_error( $attachment_id ) || ! is_numeric( $attachment_id ) ) {
			return false;
		}

		set_post_thumbnail( $post_id, (int) $attachment_id );
		return (int) $attachment_id;
	}
}

/**
 * Try to extract a likely person name from a fact title or body.
 * Returns the most likely "First Last" name, or ''.
 *
 * Heuristic: looks for two consecutive Capitalized Words (with optional
 * apostrophe, hyphen, or middle initial).
 */
if ( ! function_exists( 'lunara_oscar_fact_extract_person_name' ) ) {
	function lunara_oscar_fact_extract_person_name( $text ) {
		$text = (string) $text;
		// Match: First Last, First Middle Last, First D. Last, McDaniel, O'Neal patterns.
		if ( preg_match_all( "/([A-Z][a-z\\\\'\\-]+(?:\\s+[A-Z]\\.?)?\\s+[A-Z][a-z\\\\'\\-]+)/u", $text, $m ) ) {
			$skips = array( 'Best Picture', 'Best Actor', 'Best Actress', 'Best Director', 'Academy Awards', 'Academy Award', 'Best Supporting', 'Original Screenplay', 'Adapted Screenplay', 'Foreign Language', 'Best Sound', 'Live Action', 'Hollywood Roosevelt', 'Roosevelt Hotel', 'Los Angeles', 'United States', 'Black History', 'New York' );
			foreach ( $m[1] as $candidate ) {
				$candidate = trim( $candidate );
				if ( in_array( $candidate, $skips, true ) ) {
					continue;
				}
				return $candidate;
			}
		}
		return '';
	}
}

/**
 * Auto-fill a single fact's featured image. Returns assoc array with
 * 'attachment_id', 'source' ('tmdb-film'|'wikipedia-person'|''), 'film', 'person'.
 */
if ( ! function_exists( 'lunara_oscar_fact_autofill_image' ) ) {
	function lunara_oscar_fact_autofill_image( $post_id ) {
		$post_id = (int) $post_id;
		$result  = array( 'attachment_id' => 0, 'source' => '', 'film' => '', 'person' => '' );
		if ( $post_id <= 0 ) {
			return $result;
		}
		// Idempotent: skip if already has a featured image.
		if ( has_post_thumbnail( $post_id ) ) {
			$result['source'] = 'skipped-has-image';
			return $result;
		}

		$post        = get_post( $post_id );
		$attribution = (string) get_post_meta( $post_id, '_lunara_fact_attribution', true );
		$detected    = (array) get_post_meta( $post_id, '_lunara_fact_detected_films', true );

		// Lookup priority 1: detected films (from markdown asterisks at import).
		foreach ( $detected as $film ) {
			$image_url = lunara_tmdb_search_film_backdrop( $film );
			if ( '' !== $image_url ) {
				$attachment_id = lunara_sideload_image_to_post( $image_url, $post_id, sprintf( __( 'TMDB backdrop for %s', 'lunara-film' ), $film ) );
				if ( $attachment_id ) {
					$result['attachment_id'] = $attachment_id;
					$result['source']        = 'tmdb-film';
					$result['film']          = $film;
					return $result;
				}
			}
			usleep( 250000 ); // be polite to TMDB
		}

		// Lookup priority 2: attribution field, treated as a film title.
		if ( '' !== $attribution ) {
			// Strip year parentheticals: "Network (1976)" â†’ "Network".
			$film_guess = trim( preg_replace( '/\(\d{4}\)/', '', $attribution ) );
			if ( '' !== $film_guess ) {
				$image_url = lunara_tmdb_search_film_backdrop( $film_guess );
				if ( '' !== $image_url ) {
					$attachment_id = lunara_sideload_image_to_post( $image_url, $post_id, sprintf( __( 'TMDB backdrop for %s', 'lunara-film' ), $film_guess ) );
					if ( $attachment_id ) {
						$result['attachment_id'] = $attachment_id;
						$result['source']        = 'tmdb-film';
						$result['film']          = $film_guess;
						return $result;
					}
				}
			}
		}

		// Lookup priority 3: extract a person name from title or attribution and try Wikipedia.
		$haystack = $post->post_title . ' ' . $attribution . ' ' . wp_strip_all_tags( $post->post_content );
		$person   = lunara_oscar_fact_extract_person_name( $haystack );
		if ( '' !== $person ) {
			$image_url = lunara_wikipedia_pageimage( $person );
			if ( '' !== $image_url ) {
				$attachment_id = lunara_sideload_image_to_post( $image_url, $post_id, sprintf( __( 'Wikipedia portrait for %s', 'lunara-film' ), $person ) );
				if ( $attachment_id ) {
					$result['attachment_id'] = $attachment_id;
					$result['source']        = 'wikipedia-person';
					$result['person']        = $person;
					return $result;
				}
			}
		}

		return $result;
	}
}

/**
 * Batch auto-fill all facts without featured images.
 */
if ( ! function_exists( 'lunara_oscar_facts_run_autofill' ) ) {
	function lunara_oscar_facts_run_autofill() {
		$query = new WP_Query( array(
			'post_type'      => 'oscar_fact',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'no_found_rows'  => true,
			'fields'         => 'ids',
		) );

		$counts = array(
			'tmdb_film'        => 0,
			'wikipedia_person' => 0,
			'skipped_existing' => 0,
			'no_match'         => 0,
		);

		foreach ( $query->posts as $pid ) {
			if ( has_post_thumbnail( $pid ) ) {
				$counts['skipped_existing']++;
				continue;
			}
			$result = lunara_oscar_fact_autofill_image( $pid );
			switch ( $result['source'] ) {
				case 'tmdb-film':        $counts['tmdb_film']++;        break;
				case 'wikipedia-person': $counts['wikipedia_person']++; break;
				default:                  $counts['no_match']++;
			}
			// Be polite to remote APIs.
			usleep( 350000 );
		}

		return $counts;
	}
}

/* ----------------------------------------------------------------------------
 * Hook the new actions into the existing importer admin page.
 * The importer page already exists (Oscar Facts â†’ Import from Markdown).
 * We add a second + third form below the import form.
 * ---------------------------------------------------------------------------- */

if ( ! function_exists( 'lunara_oscar_facts_handle_extra_actions' ) ) {
	function lunara_oscar_facts_handle_extra_actions() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Clean existing markdown.
		if ( isset( $_POST['lunara_facts_cleanup_nonce'] ) && wp_verify_nonce( $_POST['lunara_facts_cleanup_nonce'], 'lunara_facts_cleanup_run' ) ) {
			$cleaned = lunara_oscar_facts_clean_existing_markdown();
			set_transient( 'lunara_facts_cleanup_result', $cleaned, 60 );
			wp_safe_redirect( add_query_arg( 'lunara_action', 'cleanup_done', wp_get_referer() ?: admin_url( 'edit.php?post_type=oscar_fact&page=lunara-oscar-facts-import' ) ) );
			exit;
		}

		// Auto-fill images.
		if ( isset( $_POST['lunara_facts_autofill_nonce'] ) && wp_verify_nonce( $_POST['lunara_facts_autofill_nonce'], 'lunara_facts_autofill_run' ) ) {
			$counts = lunara_oscar_facts_run_autofill();
			set_transient( 'lunara_facts_autofill_result', $counts, 60 );
			wp_safe_redirect( add_query_arg( 'lunara_action', 'autofill_done', wp_get_referer() ?: admin_url( 'edit.php?post_type=oscar_fact&page=lunara-oscar-facts-import' ) ) );
			exit;
		}
	}
	add_action( 'admin_init', 'lunara_oscar_facts_handle_extra_actions' );
}

/**
 * Inject the two new buttons into the importer admin page (via filter pattern).
 * We just append our markup at the end of admin_notices when on that page.
 */
if ( ! function_exists( 'lunara_oscar_facts_render_extra_admin_ui' ) ) {
	function lunara_oscar_facts_render_extra_admin_ui() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || false === strpos( (string) $screen->id, 'oscar_fact_page_lunara-oscar-facts-import' ) ) {
			return;
		}

		$cleanup_result  = get_transient( 'lunara_facts_cleanup_result' );
		$autofill_result = get_transient( 'lunara_facts_autofill_result' );
		if ( false !== $cleanup_result ) {
			delete_transient( 'lunara_facts_cleanup_result' );
		}
		if ( false !== $autofill_result ) {
			delete_transient( 'lunara_facts_autofill_result' );
		}
		?>
		<style>
			.lunara-facts-extra-actions { margin-top: 32px; padding-top: 24px; border-top: 2px solid #ddd; }
			.lunara-facts-extra-actions h2 { margin-top: 0; }
			.lunara-facts-extra-action-block { background: #fff; padding: 18px 22px; border: 1px solid #c3c4c7; border-radius: 4px; margin-bottom: 18px; }
			.lunara-facts-extra-action-block h3 { margin-top: 0; }
			.lunara-facts-extra-action-block .description { margin: 8px 0 14px; color: #50575e; }
			.lunara-facts-result-box { padding: 12px 16px; background: #f0f6e6; border-left: 4px solid #46b450; margin: 14px 0; border-radius: 3px; }
			.lunara-facts-result-box ul { margin: 6px 0 0 22px; }
		</style>

		<div class="lunara-facts-extra-actions">
			<h2><?php esc_html_e( 'Cleanup & Image Auto-Fill', 'lunara-film' ); ?></h2>

			<div class="lunara-facts-extra-action-block">
				<h3><?php esc_html_e( '1. Clean Markdown in Existing Facts', 'lunara-film' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Strips literal markdown markers (*Title*, **bold**, [link](url)) from existing fact titles and bodies. Also extracts film titles from the asterisk patterns and stores them as detected films for the image auto-fill below. Idempotent.', 'lunara-film' ); ?></p>

				<?php if ( false !== $cleanup_result ) : ?>
					<div class="lunara-facts-result-box">
						<strong><?php echo esc_html( sprintf( __( 'Cleaned %d facts.', 'lunara-film' ), (int) $cleanup_result ) ); ?></strong>
					</div>
				<?php endif; ?>

				<form method="post" action="">
					<?php wp_nonce_field( 'lunara_facts_cleanup_run', 'lunara_facts_cleanup_nonce' ); ?>
					<button type="submit" class="button"><?php esc_html_e( 'Run Cleanup', 'lunara-film' ); ?></button>
				</form>
			</div>

			<div class="lunara-facts-extra-action-block">
				<h3><?php esc_html_e( '2. Auto-Fill Featured Images (TMDB + Wikipedia)', 'lunara-film' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'For every fact without a featured image: tries TMDB for film backdrops first (using detected films + the attribution field), then Wikipedia for portraits of detected people. Skips facts that already have an image. Will NOT overwrite manually-curated images. Run the cleanup above first so detected films are populated.', 'lunara-film' ); ?>
				</p>
				<p class="description">
					<strong><?php esc_html_e( 'Note:', 'lunara-film' ); ?></strong>
					<?php esc_html_e( 'TMDB images are a starting point â€” your editorial differentiator is curated imagery. Replace any auto-filled image you don\'t love by editing the fact and uploading your own.', 'lunara-film' ); ?>
				</p>

				<?php if ( is_array( $autofill_result ) ) : ?>
					<div class="lunara-facts-result-box">
						<strong><?php esc_html_e( 'Auto-fill complete.', 'lunara-film' ); ?></strong>
						<ul>
							<li><?php echo esc_html( sprintf( __( 'TMDB film backdrops: %d', 'lunara-film' ), (int) $autofill_result['tmdb_film'] ) ); ?></li>
							<li><?php echo esc_html( sprintf( __( 'Wikipedia portraits: %d', 'lunara-film' ), (int) $autofill_result['wikipedia_person'] ) ); ?></li>
							<li><?php echo esc_html( sprintf( __( 'Already had images (skipped): %d', 'lunara-film' ), (int) $autofill_result['skipped_existing'] ) ); ?></li>
							<li><?php echo esc_html( sprintf( __( 'No match found: %d', 'lunara-film' ), (int) $autofill_result['no_match'] ) ); ?></li>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( ! defined( 'AAT_TMDB_API_KEY' ) || '' === AAT_TMDB_API_KEY ) : ?>
					<div class="notice notice-warning inline" style="margin: 10px 0;">
						<p><?php esc_html_e( 'TMDB API key not configured (AAT_TMDB_API_KEY constant). Wikipedia portraits will still work; films will be skipped.', 'lunara-film' ); ?></p>
					</div>
				<?php endif; ?>

				<form method="post" action="">
					<?php wp_nonce_field( 'lunara_facts_autofill_run', 'lunara_facts_autofill_nonce' ); ?>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Run Auto-Fill', 'lunara-film' ); ?></button>
				</form>
			</div>
		</div>
		<?php
	}
	add_action( 'admin_notices', 'lunara_oscar_facts_render_extra_admin_ui' );
}

/* ----------------------------------------------------------------------------
 * Patch the original importer to clean markdown on creation going forward.
 * Wraps lunara_oscar_facts_create_fact via a small helper that the original
 * import flow doesn't call (we tell users to use the cleanup button after
 * import â€” simpler than rewiring the original create function).
 * ---------------------------------------------------------------------------- */


/* ============================================================================
 * AUTO-FLUSH REWRITE RULES â€” runs once after a new CPT is registered.
 * Added 2026-05-10 so Dalton never has to remember Settings â†’ Permalinks â†’ Save.
 *
 * Bump the option key (v1 â†’ v2 â†’ v3) any time a CPT slug changes.
 * ============================================================================ */
if ( ! function_exists( 'lunara_oscar_cpts_maybe_flush_rewrites' ) ) {
	function lunara_oscar_cpts_maybe_flush_rewrites() {
		if ( get_option( 'lunara_oscar_cpts_rewrite_v1' ) ) {
			return;
		}
		flush_rewrite_rules( false );
		update_option( 'lunara_oscar_cpts_rewrite_v1', '1' );
	}
	add_action( 'init', 'lunara_oscar_cpts_maybe_flush_rewrites', 99 );
}


/* ============================================================================
 * REVIEW CACHE INVALIDATION â€” added 2026-05-10
 *
 * lunara_cached_review_ids() caches review ID lists in 15-min transients
 * keyed by group + count (e.g. lunara_latest_reviews_18_v1). When a review
 * gets published, edited, deleted, or has its status changed, the cache
 * is now stale until the transient expires. This forces an immediate
 * invalidation so changes show up on the homepage right away.
 * ============================================================================ */
if ( ! function_exists( 'lunara_clear_review_caches' ) ) {
	function lunara_clear_review_caches() {
		global $wpdb;
		// Delete every transient that starts with our review-cache prefix.
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			 WHERE option_name LIKE '\_transient\_lunara\_latest\_reviews\_%'
			    OR option_name LIKE '\_transient\_timeout\_lunara\_latest\_reviews\_%'
			    OR option_name LIKE '\_transient\_lunara\_curated\_reviews\_%'
			    OR option_name LIKE '\_transient\_timeout\_lunara\_curated\_reviews\_%'
			    OR option_name LIKE '\_transient\_lunara\_hero\_reviews\_%'
			    OR option_name LIKE '\_transient\_timeout\_lunara\_hero\_reviews\_%'
			    OR option_name LIKE '\_transient\_lunara\_oscars\_linked\_reviews\_%'
			    OR option_name LIKE '\_transient\_timeout\_lunara\_oscars\_linked\_reviews\_%'"
		);
	}
	add_action( 'save_post_review',                'lunara_clear_review_caches' );
	add_action( 'deleted_post',                    'lunara_clear_review_caches' );
	add_action( 'trashed_post',                    'lunara_clear_review_caches' );
	add_action( 'untrashed_post',                  'lunara_clear_review_caches' );
}


/* ============================================================================
 * CINEMATIC HERO â€” full-viewport image-first homepage opener (added 2026-05-10)
 *
 * Replaces the structured grid hero with a 100vh cinematic statement.
 * Image source priority:
 *   1. Customizer override (Lunara Cinematic Hero â†’ Hero Image upload)
 *   2. Featured image of the most recently published review
 *   3. (no fallback â€” section hides if neither exists)
 *
 * Pairs with .lunara-cinematic-hero markup in front-page.php and CSS.
 * ============================================================================ */

if ( ! function_exists( 'lunara_register_cinematic_hero_customizer' ) ) {
	function lunara_register_cinematic_hero_customizer( $wp_customize ) {
		$wp_customize->add_section( 'lunara_cinematic_hero_section', array(
			'title'       => __( 'Lunara Cinematic Hero', 'lunara-film' ),
			'description' => __( 'Controls the full-viewport homepage hero. Image priority: this override (if set) â†’ featured image of the most recent review.', 'lunara-film' ),
			'priority'    => 30,
		) );

		$wp_customize->add_setting( 'lunara_hero_override_image', array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				'lunara_hero_override_image',
				array(
					'label'       => __( 'Hero Image (override)', 'lunara-film' ),
					'description' => __( 'Upload a curated cinematic backdrop. Falls back to the latest review\'s featured image if blank.', 'lunara-film' ),
					'section'     => 'lunara_cinematic_hero_section',
				)
			)
		);

		$wp_customize->add_setting( 'lunara_hero_override_kicker', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_hero_override_kicker', array(
			'label'       => __( 'Kicker text (override)', 'lunara-film' ),
			'description' => __( 'Optional. Default is "Latest Review". Leave blank to keep auto.', 'lunara-film' ),
			'section'     => 'lunara_cinematic_hero_section',
			'type'        => 'text',
		) );

		$wp_customize->add_setting( 'lunara_hero_override_title', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_hero_override_title', array(
			'label'       => __( 'Headline (override)', 'lunara-film' ),
			'description' => __( 'Optional. Default is the latest review\'s title. Leave blank to keep auto.', 'lunara-film' ),
			'section'     => 'lunara_cinematic_hero_section',
			'type'        => 'text',
		) );

		$wp_customize->add_setting( 'lunara_hero_override_excerpt', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_textarea_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_hero_override_excerpt', array(
			'label'       => __( 'Excerpt line (override)', 'lunara-film' ),
			'description' => __( 'Optional 1-2 sentence dek. Default is auto-generated from the latest review.', 'lunara-film' ),
			'section'     => 'lunara_cinematic_hero_section',
			'type'        => 'textarea',
		) );

		$wp_customize->add_setting( 'lunara_hero_override_url', array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_hero_override_url', array(
			'label'       => __( 'CTA URL (override)', 'lunara-film' ),
			'description' => __( 'Where the hero click-through goes. Default is the latest review permalink.', 'lunara-film' ),
			'section'     => 'lunara_cinematic_hero_section',
			'type'        => 'url',
		) );

		$wp_customize->add_setting( 'lunara_hero_override_cta', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'lunara_hero_override_cta', array(
			'label'       => __( 'CTA button text (override)', 'lunara-film' ),
			'description' => __( 'Default is "Read the review". Leave blank to keep auto.', 'lunara-film' ),
			'section'     => 'lunara_cinematic_hero_section',
			'type'        => 'text',
		) );
	}
	add_action( 'customize_register', 'lunara_register_cinematic_hero_customizer' );
}

/**
 * Fetch the most recently published review (lightweight â€” single post object).
 */
if ( ! function_exists( 'lunara_get_latest_review_post' ) ) {
	function lunara_get_latest_review_post() {
		$cache = wp_cache_get( 'lunara_latest_review_post' );
		if ( $cache instanceof WP_Post ) {
			return $cache;
		}
		$posts = get_posts( array(
			'post_type'           => 'review',
			'posts_per_page'      => 1,
			'post_status'         => 'publish',
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		) );
		if ( ! empty( $posts ) && $posts[0] instanceof WP_Post ) {
			wp_cache_set( 'lunara_latest_review_post', $posts[0], '', 5 * MINUTE_IN_SECONDS );
			return $posts[0];
		}
		return null;
	}
}

/**
 * Resolve the cinematic hero data with full fallback chain.
 * Returns assoc array: image_url, kicker, title, excerpt, url, cta.
 * Returns null if there's truly nothing to show (no override + no review).
 */
if ( ! function_exists( 'lunara_get_cinematic_hero_data' ) ) {
	function lunara_get_cinematic_hero_data( $attrs = array() ) {
		// Three-tier priority: per-instance block attribute â†’ Customizer override â†’ auto from latest review.
		$attrs = is_array( $attrs ) ? $attrs : array();
		$attr_image_id   = isset( $attrs['overrideImageId'] ) ? (int) $attrs['overrideImageId'] : 0;
		$attr_kicker     = isset( $attrs['overrideKicker'] )  ? trim( (string) $attrs['overrideKicker'] )  : '';
		$attr_title      = isset( $attrs['overrideTitle'] )   ? trim( (string) $attrs['overrideTitle'] )   : '';
		$attr_excerpt    = isset( $attrs['overrideExcerpt'] ) ? trim( (string) $attrs['overrideExcerpt'] ) : '';
		$attr_url        = isset( $attrs['overrideUrl'] )     ? trim( (string) $attrs['overrideUrl'] )     : '';
		$attr_cta        = isset( $attrs['overrideCta'] )     ? trim( (string) $attrs['overrideCta'] )     : '';

		$override_image = trim( (string) get_theme_mod( 'lunara_hero_override_image', '' ) );
		$latest_review  = lunara_get_latest_review_post();

		// Image priority: block attribute â†’ Customizer URL â†’ latest review featured â†’ null.
		$image_url     = '';
		$attachment_id = 0;
		if ( $attr_image_id > 0 ) {
			$attachment_id = $attr_image_id;
			$image_url     = (string) wp_get_attachment_image_url( $attr_image_id, 'full' );
		} elseif ( '' !== $override_image ) {
			$image_url     = $override_image;
			$attachment_id = (int) attachment_url_to_postid( $override_image );
		} elseif ( $latest_review && has_post_thumbnail( $latest_review->ID ) ) {
			$attachment_id = (int) get_post_thumbnail_id( $latest_review->ID );
			$image_url     = (string) wp_get_attachment_image_url( $attachment_id, 'full' );
		}

		if ( '' === $image_url ) {
			return null; // No image available â€” caller hides the section.
		}

		// Defaults from latest review, then override with Customizer if set.
		$kicker  = $latest_review ? __( 'Latest Review', 'lunara-film' ) : __( 'Lunara', 'lunara-film' );
		$title   = $latest_review ? get_the_title( $latest_review ) : get_bloginfo( 'name' );
		$excerpt = '';
		if ( $latest_review ) {
			$excerpt = trim( (string) get_the_excerpt( $latest_review ) );
			if ( '' === $excerpt ) {
				$excerpt = wp_trim_words( wp_strip_all_tags( $latest_review->post_content ), 24, 'â€¦' );
			}
		}
		$url = $latest_review ? get_permalink( $latest_review ) : home_url( '/' );
		$cta = __( 'Read the review', 'lunara-film' );

		// Customizer overrides win when set.
		$override_kicker  = trim( (string) get_theme_mod( 'lunara_hero_override_kicker', '' ) );
		$override_title   = trim( (string) get_theme_mod( 'lunara_hero_override_title', '' ) );
		$override_excerpt = trim( (string) get_theme_mod( 'lunara_hero_override_excerpt', '' ) );
		$override_url     = trim( (string) get_theme_mod( 'lunara_hero_override_url', '' ) );
		$override_cta     = trim( (string) get_theme_mod( 'lunara_hero_override_cta', '' ) );

		if ( '' !== $override_kicker )  { $kicker  = $override_kicker; }
		if ( '' !== $override_title )   { $title   = $override_title; }
		if ( '' !== $override_excerpt ) { $excerpt = $override_excerpt; }
		if ( '' !== $override_url )     { $url     = $override_url; }
		if ( '' !== $override_cta )     { $cta     = $override_cta; }

		// Per-instance block attributes win over everything (highest priority).
		if ( '' !== $attr_kicker )  { $kicker  = $attr_kicker; }
		if ( '' !== $attr_title )   { $title   = $attr_title; }
		if ( '' !== $attr_excerpt ) { $excerpt = $attr_excerpt; }
		if ( '' !== $attr_url )     { $url     = $attr_url; }
		if ( '' !== $attr_cta )     { $cta     = $attr_cta; }

		return array(
			'image_url'     => $image_url,
			'attachment_id' => $attachment_id, // 0 if URL-only (no library record)
			'kicker'        => $kicker,
			'title'         => $title,
			'excerpt'       => $excerpt,
			'url'           => $url,
			'cta'           => $cta,
		);
	}
}

/**
 * Render the cinematic hero markup. Returns string or '' if no data.
 */
if ( ! function_exists( 'lunara_render_cinematic_hero' ) ) {
	function lunara_render_cinematic_hero( $attrs = array() ) {
		$data = lunara_get_cinematic_hero_data( is_array( $attrs ) ? $attrs : array() );
		if ( null === $data ) {
			return '';
		}

		// Build the <img> tag â€” use wp_get_attachment_image() if we have the
		// attachment ID (gives us native srcset/sizes for responsive loading),
		// otherwise fall back to a raw <img> with the URL.
		// loading="eager" + fetchpriority="high" tells the browser this is the
		// LCP element and to fetch it immediately (huge mobile perf win).
		$img_attrs = array(
			'class'         => 'lunara-cinematic-hero-img',
			'alt'           => '',
			'loading'       => 'eager',
			'decoding'      => 'async',
			'fetchpriority' => 'high',
			'sizes'         => '100vw',
		);

		if ( $data['attachment_id'] > 0 ) {
			$img_html = wp_get_attachment_image( $data['attachment_id'], 'full', false, $img_attrs );
		} else {
			$attr_string = '';
			foreach ( $img_attrs as $k => $v ) {
				$attr_string .= ' ' . $k . '="' . esc_attr( $v ) . '"';
			}
			$img_html = '<img src="' . esc_url( $data['image_url'] ) . '"' . $attr_string . ' />';
		}

		ob_start();
		?>
		<section class="lunara-home-hero lunara-cinematic-hero lunara-home-slot-hero" aria-label="<?php esc_attr_e( 'Featured', 'lunara-film' ); ?>">
			<a class="lunara-cinematic-hero-link" href="<?php echo esc_url( $data['url'] ); ?>" aria-label="<?php echo esc_attr( $data['title'] ); ?>">
				<div class="lunara-cinematic-hero-bg" aria-hidden="true">
					<?php echo $img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="lunara-cinematic-hero-overlay" aria-hidden="true"></div>
				<div class="lunara-cinematic-hero-shell">
					<div class="lunara-cinematic-hero-content">
						<p class="lunara-cinematic-hero-kicker"><?php echo esc_html( $data['kicker'] ); ?></p>
						<h1 class="lunara-cinematic-hero-title"><?php echo esc_html( $data['title'] ); ?></h1>
						<?php if ( '' !== $data['excerpt'] ) : ?>
							<p class="lunara-cinematic-hero-excerpt"><?php echo esc_html( $data['excerpt'] ); ?></p>
						<?php endif; ?>
						<span class="lunara-cinematic-hero-cta"><?php echo esc_html( $data['cta'] ); ?> <span aria-hidden="true">â†’</span></span>
					</div>
				</div>
			</a>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

/**
 * Cache invalidation: when a review is published or the customizer is saved,
 * bust the latest-review object cache so the hero refreshes.
 */
if ( ! function_exists( 'lunara_cinematic_hero_bust_cache' ) ) {
	function lunara_cinematic_hero_bust_cache() {
		wp_cache_delete( 'lunara_latest_review_post' );
	}
	add_action( 'save_post_review',          'lunara_cinematic_hero_bust_cache' );
	add_action( 'customize_save_after',      'lunara_cinematic_hero_bust_cache' );
}


/* ============================================================================
 * PATH B â€” HOMEPAGE AS GUTENBERG BLOCKS (added 2026-05-10)
 *
 * Registers four custom dynamic blocks that wrap the existing render helpers.
 * Each block is just a comment marker in post content (<!-- wp:lunara/X /-->);
 * WordPress calls the render_callback at output time. Result: the homepage
 * becomes pure block-editor-composed content instead of hard-coded template
 * markup. Dalton (and the MCP) can drag/reorder/insert blocks freely.
 *
 * Blocks registered:
 *   - lunara/cinematic-hero  â†’ lunara_render_cinematic_hero()
 *   - lunara/journal-lane    â†’ lunara_render_homepage_journal_lane() (defined below)
 *   - lunara/oscar-picks     â†’ lunara_render_oscar_picks_carousel()
 *   - lunara/oscar-facts     â†’ lunara_render_oscar_facts_carousel()
 *
 * front-page.php gets stripped to ~15 lines (just calls the_content() in a loop).
 * ============================================================================ */

if ( ! function_exists( 'lunara_register_homepage_blocks' ) ) {
	function lunara_register_homepage_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$common = array(
			'api_version' => 3,
			'category'    => 'theme',
			'supports'    => array(
				'html'      => false,
				'reusable'  => false,
				'multiple'  => true,  // allow multiple instances per page (hub-friendly)
				'inserter'  => true,
			),
		);

		register_block_type( 'lunara/cinematic-hero', array_merge( $common, array(
			'title'           => __( 'Lunara Cinematic Hero', 'lunara-film' ),
			'icon'            => 'cover-image',
			'description'     => __( 'Full-viewport image-first opener. Per-instance overrides for image / kicker / title / excerpt / URL / CTA. Falls back to Customizer override â†’ latest review.', 'lunara-film' ),
			'attributes'      => array(
				'overrideImageId' => array( 'type' => 'number', 'default' => 0 ),
				'overrideKicker'  => array( 'type' => 'string', 'default' => '' ),
				'overrideTitle'   => array( 'type' => 'string', 'default' => '' ),
				'overrideExcerpt' => array( 'type' => 'string', 'default' => '' ),
				'overrideUrl'     => array( 'type' => 'string', 'default' => '' ),
				'overrideCta'     => array( 'type' => 'string', 'default' => '' ),
			),
			'render_callback' => function ( $attributes ) {
				return function_exists( 'lunara_render_cinematic_hero' ) ? lunara_render_cinematic_hero( $attributes ) : '';
			},
		) ) );

		register_block_type( 'lunara/journal-lane', array_merge( $common, array(
			'title'           => __( 'Lunara Journal Lane', 'lunara-film' ),
			'icon'            => 'editor-ul',
			'description'     => __( 'The Journal home grid: 1 lead card + 3 supporting cards from the most recent dispatch posts.', 'lunara-film' ),
			'render_callback' => function () {
				return function_exists( 'lunara_render_homepage_journal_lane' ) ? lunara_render_homepage_journal_lane() : '';
			},
		) ) );

		register_block_type( 'lunara/oscar-picks', array_merge( $common, array(
			'title'           => __( 'Lunara Oscar Picks', 'lunara-film' ),
			'icon'            => 'awards',
			'description'     => __( 'Horizontal carousel of curated behind-the-scenes Oscar pick cards.', 'lunara-film' ),
			'render_callback' => function () {
				return function_exists( 'lunara_render_oscar_picks_carousel' ) ? lunara_render_oscar_picks_carousel() : '';
			},
		) ) );

		register_block_type( 'lunara/oscar-facts', array_merge( $common, array(
			'title'           => __( 'Lunara Oscar Facts', 'lunara-film' ),
			'icon'            => 'lightbulb',
			'description'     => __( 'Text-forward Oscar fact cards in a horizontal carousel. Image-on-the-left when a featured image is set.', 'lunara-film' ),
			'render_callback' => function () {
				return function_exists( 'lunara_render_oscar_facts_carousel' ) ? lunara_render_oscar_facts_carousel() : '';
			},
		) ) );
	}
	add_action( 'init', 'lunara_register_homepage_blocks', 100 );
}

/**
 * Render the homepage Journal lane (extracted from front-page.php so it can be
 * called as a block render callback). 1 lead card + 3 supporting cards.
 *
 * Uses the same theme-mod text fields, query helper, and DOM as before â€” drop-in
 * compatible with the existing CSS (.lunara-journal-home-grid, .lunara-journal-home-card, etc.).
 */
if ( ! function_exists( 'lunara_get_journal_carousel_ids' ) ) {
	/**
	 * Resolve curated Journal carousel attachment IDs from explicit input or post meta.
	 *
	 * @param int          $post_id Journal post ID.
	 * @param string|array $ids     Optional comma-separated or array attachment IDs.
	 * @return array
	 */
	function lunara_get_journal_carousel_ids( $post_id, $ids = '' ) {
		$post_id = (int) $post_id;
		$raw_ids = $ids;

		if ( empty( $raw_ids ) && $post_id > 0 ) {
			$raw_ids = get_post_meta( $post_id, '_lunara_journal_carousel_ids', true );
		}

		if ( empty( $raw_ids ) && $post_id > 0 ) {
			$raw_ids = get_post_meta( $post_id, '_lunara_journal_gallery_ids', true );
		}

		if ( is_string( $raw_ids ) ) {
			$raw_ids = preg_split( '/[\s,;|]+/', $raw_ids );
		}

		if ( ! is_array( $raw_ids ) ) {
			return array();
		}

		$attachment_ids = array();
		foreach ( $raw_ids as $raw_id ) {
			$attachment_id = absint( $raw_id );
			if ( $attachment_id <= 0 || in_array( $attachment_id, $attachment_ids, true ) ) {
				continue;
			}

			if ( 'attachment' !== get_post_type( $attachment_id ) || ! wp_attachment_is_image( $attachment_id ) ) {
				continue;
			}

			$attachment_ids[] = $attachment_id;
		}

		return $attachment_ids;
	}
}

if ( ! function_exists( 'lunara_get_journal_first_meta_value' ) ) {
	function lunara_get_journal_first_meta_value( $object_id, $keys ) {
		foreach ( (array) $keys as $key ) {
			$value = trim( (string) get_post_meta( $object_id, $key, true ) );
			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'lunara_get_journal_visual_context' ) ) {
	/**
	 * Return the compact visual provenance state used by Journal public cards.
	 *
	 * @param int $post_id Journal post ID.
	 * @return array
	 */
	function lunara_get_journal_visual_context( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return array();
		}

		$carousel_ids  = function_exists( 'lunara_get_journal_carousel_ids' ) ? lunara_get_journal_carousel_ids( $post_id ) : array();
		$attachment_id = get_post_thumbnail_id( $post_id );
		$attachment_id = $attachment_id ? absint( $attachment_id ) : ( ! empty( $carousel_ids[0] ) ? absint( $carousel_ids[0] ) : 0 );
		$credit        = lunara_get_journal_first_meta_value(
			$post_id,
			array(
				'_lunara_featured_image_credit',
				'_lunara_image_credit',
				'_lunara_dispatch_image_credit',
			)
		);
		$source_name   = lunara_get_journal_first_meta_value(
			$post_id,
			array(
				'_lunara_featured_image_source_name',
				'_lunara_source_name',
				'_lunara_dispatch_source_label',
			)
		);
		$source_url    = lunara_get_journal_first_meta_value(
			$post_id,
			array(
				'_lunara_featured_image_source_url',
				'_lunara_source_url',
				'_lunara_dispatch_source_url',
			)
		);

		if ( $attachment_id ) {
			if ( '' === $credit ) {
				$credit = lunara_get_journal_first_meta_value(
					$attachment_id,
					array(
						'_lunara_image_credit',
						'_lunara_dispatch_image_credit',
					)
				);
			}

			if ( '' === $source_name ) {
				$source_name = lunara_get_journal_first_meta_value(
					$attachment_id,
					array(
						'_lunara_image_source_name',
						'_lunara_dispatch_source_label',
					)
				);
			}

			if ( '' === $source_url ) {
				$source_url = lunara_get_journal_first_meta_value(
					$attachment_id,
					array(
						'_lunara_image_source_url',
						'_lunara_dispatch_source_url',
					)
				);
			}
		}

		$source_label = $source_name ? $source_name : $credit;

		return array(
			'attachment_id'  => $attachment_id,
			'credit'         => sanitize_text_field( $credit ),
			'source_name'    => sanitize_text_field( $source_name ),
			'source_url'     => esc_url_raw( $source_url ),
			'source_label'   => sanitize_text_field( $source_label ),
			'has_provenance' => '' !== $source_label,
			'carousel_ids'   => $carousel_ids,
			'carousel_count' => count( $carousel_ids ),
			'has_carousel'   => count( $carousel_ids ) >= 2,
		);
	}
}

if ( ! function_exists( 'lunara_render_journal_card_provenance' ) ) {
	/**
	 * Render non-clickable Journal card provenance badges. Cards are usually anchors,
	 * so this intentionally avoids nested links while still surfacing source quality.
	 *
	 * @param int    $post_id Journal post ID.
	 * @param string $context Optional rendering context.
	 */
	function lunara_render_journal_card_provenance( $post_id, $context = 'card' ) {
		$visual = function_exists( 'lunara_get_journal_visual_context' )
			? lunara_get_journal_visual_context( $post_id )
			: array();

		if ( empty( $visual['has_provenance'] ) && empty( $visual['has_carousel'] ) ) {
			return;
		}

		$classes = array( 'lunara-journal-card-provenance' );
		if ( '' !== $context ) {
			$classes[] = 'is-' . sanitize_html_class( $context );
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" aria-label="<?php esc_attr_e( 'Journal visual provenance', 'lunara-film' ); ?>">
			<?php if ( ! empty( $visual['has_provenance'] ) ) : ?>
				<span class="lunara-journal-card-provenance-pill is-source">
					<?php
					printf(
						/* translators: %s: image source or credit. */
						esc_html__( 'Visual source: %s', 'lunara-film' ),
						esc_html( $visual['source_label'] )
					);
					?>
				</span>
			<?php endif; ?>
			<?php if ( ! empty( $visual['has_carousel'] ) ) : ?>
				<span class="lunara-journal-card-provenance-pill is-carousel">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: carousel image count. */
							_n( '%d still', '%d stills', absint( $visual['carousel_count'] ), 'lunara-film' ),
							absint( $visual['carousel_count'] )
						)
					);
					?>
				</span>
			<?php endif; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'lunara_render_homepage_journal_lane' ) ) {
	function lunara_render_homepage_journal_lane() {
		// Pull copy from theme mods (with defaults + legacy normalization).
		$kicker = function_exists( 'lunara_theme_mod_text' )
			? lunara_theme_mod_text( 'lunara_home_dispatch_kicker', 'Journal' )
			: 'Journal';
		$heading = function_exists( 'lunara_theme_mod_text' )
			? lunara_theme_mod_text( 'lunara_home_dispatch_heading', 'Fresh movement from the Lunara Journal' )
			: 'Fresh movement from the Lunara Journal';
		$copy = function_exists( 'lunara_theme_mod_text' )
			? lunara_theme_mod_text( 'lunara_home_dispatch_copy', '' )
			: '';
		$copy = '';
		$button_label = function_exists( 'lunara_theme_mod_text' )
			? lunara_theme_mod_text( 'lunara_home_dispatch_button_label', 'Open the Journal' )
			: 'Open the Journal';
		$button_url = function_exists( 'lunara_home_dispatch_archive_url' )
			? lunara_home_dispatch_archive_url()
			: home_url( '/journal/' );

		// Legacy text normalization (matches what front-page.php was doing).
		if ( 'Dispatches & Audio' === trim( (string) $kicker ) )                                 { $kicker = 'Journal'; }
		if ( 'News, Reactions, and the Lunara Journal' === trim( (string) $heading ) )          { $heading = 'Fresh movement from the Lunara Journal'; }

		// Query: 4 most recent dispatches.
		$dispatches = function_exists( 'lunara_home_dispatches_query' )
			? lunara_home_dispatches_query( 4 )
			: new WP_Query( array(
				'post_type'      => array( 'journal', 'post' ),
				'posts_per_page' => 4,
				'post_status'    => 'publish',
				'no_found_rows'  => true,
			) );

		if ( ! ( $dispatches instanceof WP_Query ) || ! $dispatches->have_posts() ) {
			return '';
		}

		$dispatch_posts = is_array( $dispatches->posts )
			? array_filter( $dispatches->posts, static function ( $p ) { return $p instanceof WP_Post; } )
			: array();
		if ( count( $dispatch_posts ) > 4 ) {
			$dispatch_posts = array_slice( $dispatch_posts, 0, 4 );
		}
		if ( empty( $dispatch_posts ) ) {
			return '';
		}

		$dispatch_display_type = static function ( $post_id ) {
			return function_exists( 'lunara_get_dispatch_type_label' )
				? lunara_get_dispatch_type_label( $post_id )
				: 'Dispatch';
		};
		$dispatch_meta_label = static function ( $post_id ) {
			$date = get_the_date( 'M j, Y', $post_id );
			return $date ? $date : '';
		};
		$latest_post_id     = (int) $dispatch_posts[0]->ID;
		$latest_update      = get_the_modified_date( 'M j, g:i A', $latest_post_id );
		$desk_types         = array();
		foreach ( $dispatch_posts as $desk_post ) {
			$type_label = $dispatch_display_type( (int) $desk_post->ID );
			if ( '' !== trim( (string) $type_label ) ) {
				$desk_types[] = trim( (string) $type_label );
			}
		}
		$desk_types      = array_slice( array_values( array_unique( $desk_types ) ), 0, 3 );
		$desk_type_label = implode( ' / ', $desk_types );

		ob_start();
		?>
		<section class="lunara-home-section lunara-home-slot-dispatch lunara-dispatches-section" aria-label="Journal">
			<div class="lunara-home-section-head is-with-summary">
				<div>
					<p class="lunara-home-section-kicker"><?php echo esc_html( $kicker ); ?></p>
					<h2 class="lunara-home-section-title"><?php echo esc_html( $heading ); ?></h2>
					<?php if ( ! empty( $copy ) ) : ?>
						<p class="lunara-home-section-summary"><?php echo esc_html( $copy ); ?></p>
					<?php endif; ?>
				</div>
				<a class="lunara-section-link" href="<?php echo esc_url( $button_url ); ?>"><?php echo esc_html( $button_label ); ?></a>
			</div>

			<div class="lunara-journal-home-deskbar" aria-label="<?php esc_attr_e( 'Journal desk status', 'lunara-film' ); ?>">
				<span><strong><?php esc_html_e( 'Latest file:', 'lunara-film' ); ?></strong> <?php echo esc_html( $latest_update ); ?></span>
				<span><strong><?php esc_html_e( 'On the board:', 'lunara-film' ); ?></strong> <?php echo esc_html( sprintf( _n( '%d file', '%d files', count( $dispatch_posts ), 'lunara-film' ), count( $dispatch_posts ) ) ); ?></span>
				<?php if ( '' !== $desk_type_label ) : ?>
					<span><strong><?php esc_html_e( 'Desk mix:', 'lunara-film' ); ?></strong> <?php echo esc_html( $desk_type_label ); ?></span>
				<?php endif; ?>
			</div>

			<div class="lunara-journal-home-grid" aria-label="Lunara Journal homepage stories">
				<?php foreach ( $dispatch_posts as $dispatch_index => $dispatch_post ) :
					setup_postdata( $dispatch_post );
					$pid         = (int) $dispatch_post->ID;
					$is_lead     = ( 0 === $dispatch_index );
					$card_url    = get_permalink( $pid );
					$thumb_size  = 'newspack-article-block-landscape-small';
					$thumb_url   = has_post_thumbnail( $pid ) ? get_the_post_thumbnail_url( $pid, $thumb_size ) : '';
					$thumb_attrs = array(
						'class'    => 'lunara-journal-home-card-image',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '(max-width: 420px) 92vw, (max-width: 760px) 44vw, (max-width: 1180px) 42vw, 360px',
					);
					?>
					<article class="lunara-journal-home-card<?php echo $is_lead ? ' is-lead' : ''; ?>">
						<a class="lunara-journal-home-card-link" href="<?php echo esc_url( $card_url ); ?>">
							<div class="lunara-journal-home-card-media">
								<?php if ( has_post_thumbnail( $pid ) ) : ?>
									<?php echo get_the_post_thumbnail( $pid, $thumb_size, $thumb_attrs ); ?>
								<?php else : ?>
									<div class="lunara-journal-home-card-placeholder">
										<span><?php echo esc_html( $dispatch_display_type( $pid ) ); ?></span>
									</div>
								<?php endif; ?>
							</div>
							<div class="lunara-journal-home-card-copy">
								<p class="lunara-journal-home-card-kicker"><?php echo esc_html( $is_lead ? __( 'Lead file', 'lunara-film' ) : __( 'From the desk', 'lunara-film' ) ); ?></p>
								<p class="lunara-dispatch-type"><?php echo esc_html( $dispatch_display_type( $pid ) ); ?></p>
								<?php lunara_render_journal_card_provenance( $pid, 'home' ); ?>
								<?php if ( function_exists( 'lunara_render_trailer_card_badge' ) ) : ?>
									<?php echo lunara_render_trailer_card_badge( $pid, 'journal-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php endif; ?>
								<h3 class="lunara-journal-home-card-title"><?php echo esc_html( get_the_title( $pid ) ); ?></h3>
								<p class="lunara-journal-home-card-excerpt"><?php
									echo esc_html(
										function_exists( 'lunara_card_excerpt' )
											? lunara_card_excerpt( $pid, $is_lead ? 30 : 18 )
											: wp_trim_words( get_the_excerpt( $pid ), $is_lead ? 30 : 18 )
									);
								?></p>
								<div class="lunara-journal-home-card-meta">
									<span><?php echo esc_html( $dispatch_meta_label( $pid ) ); ?></span>
									<span class="lunara-journal-home-card-cta"><?php esc_html_e( 'Read file', 'lunara-film' ); ?></span>
								</div>
							</div>
						</a>
					</article>
				<?php endforeach; ?>
				<?php wp_reset_postdata(); ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}


/* ============================================================================
 * PATH B â€” EDITOR-SIDE BLOCK REGISTRATION (added 2026-05-10)
 *
 * Server-side register_block_type() makes blocks render on the FRONT-END,
 * but the block editor (React) doesn't know about them and shows
 * "Your site doesn't include support for the X block" warnings.
 *
 * This enqueues a tiny inline JS that registers each block client-side
 * with a ServerSideRender component â€” so the editor shows a LIVE preview
 * of the rendered output as the block placeholder. Dalton can see what
 * the block will look like without leaving the editor.
 * ============================================================================ */
if ( ! function_exists( 'lunara_enqueue_homepage_block_editor_assets' ) ) {
	function lunara_enqueue_homepage_block_editor_assets() {
		wp_register_script(
			'lunara-homepage-blocks-editor',
			'',
			array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			'1.1.0',
			true
		);
		wp_enqueue_script( 'lunara-homepage-blocks-editor' );

		$js = <<<'JS'
(function (blocks, element, ssrPkg, blockEditor, components, i18n) {
	if (!blocks || !element) { return; }
	var el = element.createElement;
	var Fragment = element.Fragment;
	var ServerSideRender = (ssrPkg && (ssrPkg.default || ssrPkg)) || (window.wp && window.wp.serverSideRender);
	if (!ServerSideRender) { return; }

	var InspectorControls = blockEditor && blockEditor.InspectorControls;
	var MediaUpload = blockEditor && blockEditor.MediaUpload;
	var PanelBody = components && components.PanelBody;
	var SelectControl = components && components.SelectControl;
	var TextControl = components && components.TextControl;
	var TextareaControl = components && components.TextareaControl;
	var Button = components && components.Button;
	var __ = (i18n && i18n.__) || function (s) { return s; };

	// CINEMATIC HERO with rich override controls in the sidebar
	if (!blocks.getBlockType || !blocks.getBlockType('lunara/cinematic-hero')) {
		blocks.registerBlockType('lunara/cinematic-hero', {
			apiVersion: 3,
			title: __('Lunara Cinematic Hero'),
			icon: 'cover-image',
			category: 'theme',
			description: __('Full-viewport image-first opener. Per-instance overrides for image / kicker / title / excerpt / URL / CTA.'),
			supports: { html: false, reusable: false, multiple: true, inserter: true },
			attributes: {
				overrideImageId: { type: 'number', default: 0 },
				overrideKicker:  { type: 'string', default: '' },
				overrideTitle:   { type: 'string', default: '' },
				overrideExcerpt: { type: 'string', default: '' },
				overrideUrl:     { type: 'string', default: '' },
				overrideCta:     { type: 'string', default: '' }
			},
			edit: function (props) {
				var attrs = props.attributes;
				var setAttrs = props.setAttributes;

				var inspector = InspectorControls && PanelBody ? el(InspectorControls, {},
					el(PanelBody, { title: __('Hero Overrides'), initialOpen: true },
						el('p', { style: { fontSize: '12px', color: '#666', margin: '0 0 12px' } },
							__('Leave any field blank to use the auto value (latest review or Customizer override).')
						),
						MediaUpload ? el(MediaUpload, {
							onSelect: function (media) { setAttrs({ overrideImageId: media.id }); },
							allowedTypes: ['image'],
							value: attrs.overrideImageId,
							render: function (obj) {
								return el('div', { style: { marginBottom: '14px' } },
									el(Button, { onClick: obj.open, variant: 'secondary' },
										attrs.overrideImageId ? __('Replace hero image') : __('Select hero image')
									),
									attrs.overrideImageId ? el(Button, {
										onClick: function () { setAttrs({ overrideImageId: 0 }); },
										variant: 'tertiary',
										style: { marginLeft: '8px', color: '#cc1818' }
									}, __('Clear')) : null,
									attrs.overrideImageId ? el('div', { style: { fontSize: '11px', color: '#888', marginTop: '6px' } },
										__('Image ID: ') + attrs.overrideImageId
									) : null
								);
							}
						}) : null,
						TextControl ? el(TextControl, {
							label: __('Kicker (override)'),
							value: attrs.overrideKicker,
							onChange: function (v) { setAttrs({ overrideKicker: v }); },
							placeholder: __('e.g. LUNARA SPOTLIGHT')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('Title (override)'),
							value: attrs.overrideTitle,
							onChange: function (v) { setAttrs({ overrideTitle: v }); },
							placeholder: __('Custom headline')
						}) : null,
						TextareaControl ? el(TextareaControl, {
							label: __('Excerpt (override)'),
							value: attrs.overrideExcerpt,
							onChange: function (v) { setAttrs({ overrideExcerpt: v }); },
							placeholder: __('Optional 1-2 sentence dek')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('CTA URL (override)'),
							value: attrs.overrideUrl,
							onChange: function (v) { setAttrs({ overrideUrl: v }); },
							placeholder: __('https://')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('CTA button text (override)'),
							value: attrs.overrideCta,
							onChange: function (v) { setAttrs({ overrideCta: v }); },
							placeholder: __('Read the review')
						}) : null
					)
				) : null;

				var preview = el(
					'div',
					{ className: 'lunara-block-editor-preview', style: { border: '1px dashed #c9a961', padding: '12px', borderRadius: '8px', background: 'rgba(7, 15, 26, 0.04)' } },
					el('div', { style: { fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.2em', color: '#c9a961', marginBottom: '8px', fontFamily: 'sans-serif' } },
						__('Lunara Cinematic Hero')
					),
					el(ServerSideRender, {
						block: 'lunara/cinematic-hero',
						attributes: attrs,
						EmptyResponsePlaceholder: function () {
							return el('div', { style: { padding: '20px', color: '#888', fontStyle: 'italic' } }, __('(no hero image â€” set an override image or publish a review with a featured image)'));
						}
					})
				);

				return el(Fragment, {}, inspector, preview);
			},
			save: function () { return null; }
		});
	}

	// LATEST REVIEWS with override controls in the sidebar
	if (!blocks.getBlockType || !blocks.getBlockType('lunara/latest-reviews')) {
		blocks.registerBlockType('lunara/latest-reviews', {
			apiVersion: 3,
			title: __('Lunara Latest Reviews'),
			icon: 'star-filled',
			category: 'theme',
			description: __('Grid of curated homepage reviews or the most recent published reviews. Per-instance overrides for source / count / heading / kicker / CTA.'),
			supports: { html: false, reusable: false, multiple: true, inserter: true },
			attributes: {
				source:   { type: 'string', default: 'curated' },
				count:    { type: 'number', default: 8 },
				heading:  { type: 'string', default: '' },
				kicker:   { type: 'string', default: '' },
				ctaLabel: { type: 'string', default: '' },
				ctaUrl:   { type: 'string', default: '' }
			},
			edit: function (props) {
				var attrs = props.attributes;
				var setAttrs = props.setAttributes;

				var inspector = InspectorControls && PanelBody ? el(InspectorControls, {},
					el(PanelBody, { title: __('Latest Reviews Settings'), initialOpen: true },
						el('p', { style: { fontSize: '12px', color: '#666', margin: '0 0 12px' } },
							__('Default source uses the homepage curated review shelf, then falls back to featured/latest reviews.')
						),
						SelectControl ? el(SelectControl, {
							label: __('Source'),
							value: attrs.source || 'curated',
							options: [
								{ label: __('Homepage curated shelf'), value: 'curated' },
								{ label: __('Newest reviews'), value: 'latest' },
								{ label: __('Top homepage showcase'), value: 'hero' }
							],
							onChange: function (v) { setAttrs({ source: v || 'curated' }); },
							help: __('Use curated shelf when you want to feature an older review again.')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('Count'),
							type: 'number',
							value: attrs.count,
							onChange: function (v) { setAttrs({ count: Math.max(1, Math.min(24, parseInt(v, 10) || 8)) }); },
							help: __('Between 1 and 24.')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('Heading (override)'),
							value: attrs.heading,
							onChange: function (v) { setAttrs({ heading: v }); },
							placeholder: __('Latest Reviews')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('Kicker (override)'),
							value: attrs.kicker,
							onChange: function (v) { setAttrs({ kicker: v }); },
							placeholder: __('Lunara Reviews')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('CTA label (override)'),
							value: attrs.ctaLabel,
							onChange: function (v) { setAttrs({ ctaLabel: v }); },
							placeholder: __('All Reviews')
						}) : null,
						TextControl ? el(TextControl, {
							label: __('CTA URL (override)'),
							value: attrs.ctaUrl,
							onChange: function (v) { setAttrs({ ctaUrl: v }); },
							placeholder: __('https://lunarafilm.com/reviews/')
						}) : null
					)
				) : null;

				var preview = el(
					'div',
					{ className: 'lunara-block-editor-preview', style: { border: '1px dashed #c9a961', padding: '12px', borderRadius: '8px', background: 'rgba(7, 15, 26, 0.04)' } },
					el('div', { style: { fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.2em', color: '#c9a961', marginBottom: '8px', fontFamily: 'sans-serif' } },
						__('Lunara Latest Reviews')
					),
					el(ServerSideRender, {
						block: 'lunara/latest-reviews',
						attributes: attrs,
						EmptyResponsePlaceholder: function () {
							return el('div', { style: { padding: '20px', color: '#888', fontStyle: 'italic' } }, __('(no published reviews yet)'));
						}
					})
				);

				return el(Fragment, {}, inspector, preview);
			},
			save: function () { return null; }
		});
	}

	// Simpler blocks (no per-instance attributes yet) â€” journal/picks/facts
	var simpleDefs = [
		{ name: 'lunara/journal-lane', title: __('Lunara Journal Lane'), icon: 'editor-ul',  description: __('The Journal home grid: 1 lead + 3 supporting cards.') },
		{ name: 'lunara/oscar-picks',  title: __('Lunara Oscar Picks'),  icon: 'awards',     description: __('Behind-the-scenes editorial Oscar picks carousel.') },
		{ name: 'lunara/oscar-facts',  title: __('Lunara Oscar Facts'),  icon: 'lightbulb',  description: __('Text-forward Oscar fact carousel.') }
	];

	simpleDefs.forEach(function (def) {
		if (blocks.getBlockType && blocks.getBlockType(def.name)) { return; }

		blocks.registerBlockType(def.name, {
			apiVersion: 3,
			title: def.title,
			icon: def.icon,
			category: 'theme',
			description: def.description,
			supports: { html: false, reusable: false, multiple: true, inserter: true },
			edit: function () {
				return el(
					'div',
					{ className: 'lunara-block-editor-preview', style: { border: '1px dashed #c9a961', padding: '12px', borderRadius: '8px', background: 'rgba(7, 15, 26, 0.04)' } },
					el('div', { style: { fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.2em', color: '#c9a961', marginBottom: '8px', fontFamily: 'sans-serif' } },
						def.title
					),
					el(ServerSideRender, {
						block: def.name,
						EmptyResponsePlaceholder: function () {
							return el('div', { style: { padding: '20px', color: '#888', fontStyle: 'italic' } }, __('(empty â€” publish a record to see this block render)'));
						}
					})
				);
			},
			save: function () { return null; }
		});
	});
})(
	window.wp && window.wp.blocks,
	window.wp && window.wp.element,
	window.wp && window.wp.serverSideRender,
	window.wp && window.wp.blockEditor,
	window.wp && window.wp.components,
	window.wp && window.wp.i18n
);
JS;

		wp_add_inline_script( 'lunara-homepage-blocks-editor', $js );
	}
	add_action( 'enqueue_block_editor_assets', 'lunara_enqueue_homepage_block_editor_assets' );
}


/* ============================================================================
 * LUNARA HUB â€” Page Template + Block Patterns + Pattern Category (added 2026-05-10)
 *
 * Lets Dalton (or the MCP) compose any Page with the Lunara block grammar:
 *   - Cinematic Hero (per-instance overrides for hub feature pages)
 *   - Journal Lane
 *   - Oscar Picks
 *   - Oscar Facts
 *   - Plus any Gutenberg core blocks freely interspersed
 *
 * Three pre-built patterns ship: Full Hub, Awards Focus, Editorial Focus.
 * Page Template "Lunara Hub" gives full-width treatment without the default
 * Blocksy chrome (sidebar, default page header).
 * ============================================================================ */

if ( ! function_exists( 'lunara_register_hub_pattern_category' ) ) {
	function lunara_register_hub_pattern_category() {
		if ( ! function_exists( 'register_block_pattern_category' ) ) {
			return;
		}
		register_block_pattern_category( 'lunara', array(
			'label' => __( 'Lunara', 'lunara-film' ),
		) );
	}
	add_action( 'init', 'lunara_register_hub_pattern_category', 50 );
}

if ( ! function_exists( 'lunara_register_hub_block_patterns' ) ) {
	function lunara_register_hub_block_patterns() {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		// PATTERN 1 â€” Lunara Homepage (criticism-first, current default)
		register_block_pattern( 'lunara/hub-homepage', array(
			'title'       => __( 'Lunara Homepage â€” Reviews-First', 'lunara-film' ),
			'description' => __( 'Latest Reviews â†’ Oscar Picks â†’ Journal â†’ Oscar Facts. The default homepage layout, criticism-led.', 'lunara-film' ),
			'categories'  => array( 'lunara' ),
			'keywords'    => array( 'hub', 'homepage', 'lunara', 'reviews', 'critic' ),
			'content'     => "<!-- wp:lunara/latest-reviews /-->\n\n<!-- wp:lunara/oscar-picks /-->\n\n<!-- wp:lunara/journal-lane /-->\n\n<!-- wp:lunara/oscar-facts /-->",
		) );

		// PATTERN 1b â€” Lunara Hub with Cinematic Hero (for feature pages, NOT the homepage)
		register_block_pattern( 'lunara/hub-full', array(
			'title'       => __( 'Lunara Hub â€” With Cinematic Hero', 'lunara-film' ),
			'description' => __( 'Cinematic Hero â†’ Latest Reviews â†’ Journal Lane â†’ Oscar Picks â†’ Oscar Facts. For special feature pages, NOT the daily homepage.', 'lunara-film' ),
			'categories'  => array( 'lunara' ),
			'keywords'    => array( 'hub', 'cinematic', 'feature', 'landing' ),
			'content'     => "<!-- wp:lunara/cinematic-hero /-->\n\n<!-- wp:lunara/latest-reviews /-->\n\n<!-- wp:lunara/journal-lane /-->\n\n<!-- wp:lunara/oscar-picks /-->\n\n<!-- wp:lunara/oscar-facts /-->",
		) );

		// PATTERN 2 â€” Awards Focus (cinematic + picks + facts)
		register_block_pattern( 'lunara/hub-awards', array(
			'title'       => __( 'Lunara Hub â€” Awards Focus', 'lunara-film' ),
			'description' => __( 'Cinematic Hero â†’ Oscar Picks â†’ Oscar Facts. For Oscars-driven hub pages and awards-season campaigns.', 'lunara-film' ),
			'categories'  => array( 'lunara' ),
			'keywords'    => array( 'hub', 'oscars', 'awards', 'picks', 'facts' ),
			'content'     => "<!-- wp:lunara/cinematic-hero /-->\n\n<!-- wp:lunara/oscar-picks /-->\n\n<!-- wp:lunara/oscar-facts /-->",
		) );

		// PATTERN 3 â€” Editorial Focus (cinematic + journal)
		register_block_pattern( 'lunara/hub-editorial', array(
			'title'       => __( 'Lunara Hub â€” Editorial Focus', 'lunara-film' ),
			'description' => __( 'Cinematic Hero â†’ Journal Lane. For criticism-forward hub pages and editorial spotlights.', 'lunara-film' ),
			'categories'  => array( 'lunara' ),
			'keywords'    => array( 'hub', 'editorial', 'journal', 'criticism' ),
			'content'     => "<!-- wp:lunara/cinematic-hero /-->\n\n<!-- wp:lunara/journal-lane /-->",
		) );

		// PATTERN 4 â€” Picks Spotlight (cinematic with override + just picks below)
		register_block_pattern( 'lunara/hub-picks-spotlight', array(
			'title'       => __( 'Lunara Hub â€” Picks Spotlight', 'lunara-film' ),
			'description' => __( 'Cinematic Hero with custom kicker â†’ Oscar Picks. For ceremony-week pages and pick reveal posts.', 'lunara-film' ),
			'categories'  => array( 'lunara' ),
			'keywords'    => array( 'hub', 'picks', 'spotlight', 'ceremony' ),
			'content'     => "<!-- wp:lunara/cinematic-hero {\"overrideKicker\":\"Lunara Picks\",\"overrideTitle\":\"The 98th Academy Awards\",\"overrideCta\":\"Browse the picks\"} /-->\n\n<!-- wp:lunara/oscar-picks /-->",
		) );
	}
	add_action( 'init', 'lunara_register_hub_block_patterns', 100 );
}

/**
 * Register the Lunara Hub page template so any Page can opt into the
 * full-width canvas treatment from the editor's Template dropdown.
 */
if ( ! function_exists( 'lunara_register_hub_page_template' ) ) {
	function lunara_register_hub_page_template( $templates ) {
		$templates['template-lunara-hub.php'] = __( 'Lunara Hub', 'lunara-film' );
		return $templates;
	}
	add_filter( 'theme_page_templates', 'lunara_register_hub_page_template' );
}


/* ============================================================================
 * LATEST REVIEWS BLOCK â€” added 2026-05-10 (Reviews-first homepage)
 *
 * Extracts the Latest Reviews grid from the legacy front-page.php into a
 * standalone render function + Gutenberg block. Per-instance attributes:
 *   - count    (default 8)
 *   - heading  (default "Latest Reviews")
 *   - kicker   (default "Lunara Reviews")
 *   - ctaLabel (default "All Reviews â†’")
 *   - ctaUrl   (default /reviews/)
 *
 * Pairs with the existing .lunara-latest-reviews-section CSS so the visual
 * is consistent with what front-page.php produced â€” clean grid of poster-led
 * cards driving traffic into individual reviews.
 * ============================================================================ */

if ( ! function_exists( 'lunara_render_homepage_latest_reviews' ) ) {
	function lunara_render_homepage_latest_reviews( $attrs = array() ) {
		$attrs = is_array( $attrs ) ? $attrs : array();
		$count     = isset( $attrs['count'] ) ? max( 1, min( 24, (int) $attrs['count'] ) ) : 8;
		$source    = isset( $attrs['source'] ) ? sanitize_key( (string) $attrs['source'] ) : 'curated';
		$heading   = isset( $attrs['heading'] )   ? trim( (string) $attrs['heading'] )   : '';
		$kicker    = isset( $attrs['kicker'] )    ? trim( (string) $attrs['kicker'] )    : '';
		$cta_label = isset( $attrs['ctaLabel'] )  ? trim( (string) $attrs['ctaLabel'] )  : '';
		$cta_url   = isset( $attrs['ctaUrl'] )    ? trim( (string) $attrs['ctaUrl'] )    : '';
		$source    = in_array( $source, array( 'curated', 'latest', 'hero' ), true ) ? $source : 'curated';

		if ( '' === $heading )   { $heading   = __( 'Latest Reviews', 'lunara-film' ); }
		if ( '' === $kicker )    { $kicker    = __( 'Lunara Reviews', 'lunara-film' ); }
		if ( '' === $cta_label ) { $cta_label = __( 'All Reviews', 'lunara-film' ); }
		if ( '' === $cta_url )   { $cta_url   = home_url( '/reviews/' ); }

		if ( 'latest' === $source && function_exists( 'lunara_latest_reviews_query' ) ) {
			$latest = lunara_latest_reviews_query( $count );
		} elseif ( 'hero' === $source && function_exists( 'lunara_home_hero_reviews_query' ) ) {
			$latest = lunara_home_hero_reviews_query( $count );
		} elseif ( function_exists( 'lunara_home_featured_reviews_query' ) ) {
			$latest = lunara_home_featured_reviews_query( $count );
		} elseif ( function_exists( 'lunara_latest_reviews_query' ) ) {
			$latest = lunara_latest_reviews_query( $count );
		} else {
			$latest = new WP_Query( array(
				'post_type'      => 'review',
				'posts_per_page' => $count,
				'post_status'    => 'publish',
				'no_found_rows'  => true,
			) );
		}

		if ( ! ( $latest instanceof WP_Query ) || ! $latest->have_posts() ) {
			$latest = function_exists( 'lunara_latest_reviews_query' )
				? lunara_latest_reviews_query( $count )
				: new WP_Query( array(
					'post_type'      => 'review',
					'posts_per_page' => $count,
					'post_status'    => 'publish',
					'no_found_rows'  => true,
				) );
		}

		if ( ! ( $latest instanceof WP_Query ) || ! $latest->have_posts() ) {
			return '';
		}

		ob_start();
		?>
		<section class="lunara-home-section lunara-home-slot-latest-reviews lunara-latest-reviews-section" data-review-source="<?php echo esc_attr( $source ); ?>" aria-label="Latest Reviews">
			<div class="lunara-home-section-head">
				<div>
					<p class="lunara-home-section-kicker"><?php echo esc_html( $kicker ); ?></p>
					<h2 class="lunara-home-section-title"><?php echo esc_html( $heading ); ?></h2>
				</div>
				<a class="lunara-section-link" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
			</div>
			<div class="lunara-review-grid lunara-review-archive-uniform">
				<?php while ( $latest->have_posts() ) :
					$latest->the_post();
					$review_index = max( 0, (int) $latest->current_post );
					$rid          = get_the_ID();
					$score        = get_post_meta( $rid, '_lunara_score', true );
					$quote        = function_exists( 'lunara_get_review_card_pull_quote' )
						? lunara_get_review_card_pull_quote( $rid, 46 )
						: wp_trim_words( wp_strip_all_tags( get_the_excerpt( $rid ) ), 46, '...' );
					$thumb_attrs  = array(
						'class'    => 'lunara-review-grid-poster',
						'loading'  => 0 === $review_index ? 'eager' : 'lazy',
						'decoding' => 'async',
						'sizes'    => '(max-width: 420px) 92vw, (max-width: 760px) 44vw, (max-width: 1180px) 42vw, 260px',
					);
					if ( 0 === $review_index ) {
						$thumb_attrs['fetchpriority'] = 'high';
					}
					$image_data = function_exists( 'lunara_get_review_card_image_data' )
						? lunara_get_review_card_image_data( $rid, 'newspack-article-block-portrait-intermediate', $thumb_attrs )
						: array(
							'url'  => has_post_thumbnail( $rid ) ? get_the_post_thumbnail_url( $rid, 'medium_large' ) : '',
							'html' => has_post_thumbnail( $rid ) ? get_the_post_thumbnail( $rid, 'medium_large', $thumb_attrs ) : '',
						);
					$thumb_url       = isset( $image_data['url'] ) ? (string) $image_data['url'] : '';
					$has_thumb_html  = ! empty( $image_data['html'] );
					$use_fallback_bg = '' !== $thumb_url && ! $has_thumb_html;
					?>
					<article class="lunara-review-grid-card">
						<a class="lunara-review-grid-link" href="<?php the_permalink(); ?>">
							<div class="lunara-review-grid-poster-wrap<?php echo $use_fallback_bg ? ' has-poster-bg has-fallback-bg' : ''; ?>"<?php if ( $use_fallback_bg ) : ?> style="background-image: url('<?php echo esc_url( $thumb_url ); ?>');"<?php endif; ?>>
								<?php if ( $has_thumb_html ) : ?>
									<?php echo $image_data['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php endif; ?>
								<?php if ( $score && function_exists( 'lunara_render_stars' ) ) : ?>
									<span class="lunara-score-badge"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span>
								<?php endif; ?>
							</div>
							<div class="lunara-review-grid-copy">
								<p class="lunara-review-grid-kicker"><?php esc_html_e( 'Lunara Review', 'lunara-film' ); ?></p>
								<h3 class="lunara-review-grid-title"><?php the_title(); ?></h3>
								<?php if ( '' !== trim( $quote ) ) : ?>
									<p class="lunara-review-grid-excerpt lunara-review-grid-quote"><?php echo esc_html( $quote ); ?></p>
								<?php endif; ?>
							</div>
						</a>
					</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

/**
 * Register the lunara/latest-reviews block. Plugs into the same blocks
 * registration init hook so it lands alongside the others.
 */
if ( ! function_exists( 'lunara_register_latest_reviews_block' ) ) {
	function lunara_register_latest_reviews_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type( 'lunara/latest-reviews', array(
			'api_version' => 3,
			'category'    => 'theme',
			'title'       => __( 'Lunara Latest Reviews', 'lunara-film' ),
			'icon'        => 'star-filled',
			'description' => __( 'Grid of curated homepage reviews or newest published reviews. Per-instance source + count + heading + kicker + CTA overrides.', 'lunara-film' ),
			'supports'    => array(
				'html'     => false,
				'reusable' => false,
				'multiple' => true,
				'inserter' => true,
			),
			'attributes'  => array(
				'source'   => array( 'type' => 'string', 'default' => 'curated' ),
				'count'    => array( 'type' => 'number', 'default' => 8 ),
				'heading'  => array( 'type' => 'string', 'default' => '' ),
				'kicker'   => array( 'type' => 'string', 'default' => '' ),
				'ctaLabel' => array( 'type' => 'string', 'default' => '' ),
				'ctaUrl'   => array( 'type' => 'string', 'default' => '' ),
			),
			'render_callback' => function ( $attributes ) {
				return function_exists( 'lunara_render_homepage_latest_reviews' ) ? lunara_render_homepage_latest_reviews( $attributes ) : '';
			},
		) );
	}
	add_action( 'init', 'lunara_register_latest_reviews_block', 100 );
}
