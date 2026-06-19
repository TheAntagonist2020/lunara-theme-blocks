<?php
/**
 * Customizer options and runtime CSS for Lunara Film.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Choices for homepage Journal media ratios.
 *
 * @return array<string,string>
 */
function lunara_get_dispatch_ratio_choices() {
    return array(
        'cinematic' => __( 'Cinematic (16:10)', 'lunara-film' ),
        'balanced'  => __( 'Balanced (3:2)', 'lunara-film' ),
        'classic'   => __( 'Classic (4:3)', 'lunara-film' ),
    );
}

/**
 * Resolve a homepage Journal media ratio preset to a CSS aspect-ratio value.
 *
 * @param string $preset Ratio preset slug from the Customizer.
 * @return string
 */
function lunara_get_dispatch_ratio_value( $preset ) {
    $ratios = array(
        'cinematic' => '16 / 10',
        'balanced'  => '3 / 2',
        'classic'   => '4 / 3',
    );

    $preset = sanitize_text_field( $preset );

    return isset( $ratios[ $preset ] ) ? $ratios[ $preset ] : $ratios['balanced'];
}

/**
 * Enqueue Lunara Customizer accessibility enhancements for range controls.
 *
 * Adds an exact numeric input and a live value summary beside range sliders so
 * voice-dictation workflows do not depend on visually estimating slider
 * positions.
 *
 * @return void
 */
function lunara_enqueue_customizer_control_assets() {
    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();
    $css_path  = $theme_dir . '/assets/css/lunara-customizer-controls.css';
    $js_path   = $theme_dir . '/assets/js/lunara-customizer-controls.js';

    if ( file_exists( $css_path ) ) {
        wp_enqueue_style(
            'lunara-customizer-controls',
            $theme_uri . '/assets/css/lunara-customizer-controls.css',
            array(),
            (string) filemtime( $css_path )
        );
    }

    if ( file_exists( $js_path ) ) {
        wp_enqueue_script(
            'lunara-customizer-controls',
            $theme_uri . '/assets/js/lunara-customizer-controls.js',
            array( 'jquery', 'customize-controls' ),
            (string) filemtime( $js_path ),
            true
        );

        wp_localize_script(
            'lunara-customizer-controls',
            'lunaraCustomizerA11y',
            array(
                'exactValueLabel' => __( 'Exact value', 'lunara-film' ),
                'currentValue'    => __( 'Current value', 'lunara-film' ),
                'allowedRange'    => __( 'Allowed range', 'lunara-film' ),
                'step'            => __( 'Step', 'lunara-film' ),
                'typingHint'      => __( 'You can type the exact number here instead of dragging the slider.', 'lunara-film' ),
            )
        );
    }
}
add_action( 'customize_controls_enqueue_scripts', 'lunara_enqueue_customizer_control_assets' );

/**
 * Customizer options
 */
function lunara_customize_register( $wp_customize ) {

    // ── PANELS ──────────────────────────────────────────────────────────────
    $wp_customize->add_panel( 'lunara_shell_panel', array(
        'title'    => __( 'Lunara Site Shell', 'lunara-film' ),
        'priority' => 30,
        'description' => __( 'Header, navigation, global colors, typography, and spacing.', 'lunara-film' ),
    ) );

    $wp_customize->add_panel( 'lunara_homepage_panel', array(
        'title'    => __( 'Lunara Homepage', 'lunara-film' ),
        'priority' => 31,
        'description' => __( 'Hero, section layout, curation, and content counts.', 'lunara-film' ),
    ) );

    $wp_customize->add_panel( 'lunara_reviews_panel', array(
        'title'    => __( 'Lunara Reviews', 'lunara-film' ),
        'priority' => 32,
        'description' => __( 'Review single layout, labels, debrief, and related section.', 'lunara-film' ),
    ) );

    $wp_customize->add_panel( 'lunara_editorial_panel', array(
        'title'    => __( 'Lunara Editorial', 'lunara-film' ),
        'priority' => 33,
        'description' => __( 'Standard posts, archives, and the debrief signature.', 'lunara-film' ),
    ) );

    $wp_customize->add_panel( 'lunara_utility_panel', array(
        'title'    => __( 'Lunara Utility Pages', 'lunara-film' ),
        'priority' => 34,
        'description' => __( '404, search, and generic archive pages.', 'lunara-film' ),
    ) );

    $wp_customize->add_panel( 'lunara_oscars_panel', array(
        'title'    => __( 'Lunara Oscars', 'lunara-film' ),
        'priority' => 35,
        'description' => __( 'Oscars portal headings and button labels.', 'lunara-film' ),
    ) );

    $wp_customize->add_panel( 'lunara_footer_panel', array(
        'title'    => __( 'Lunara Footer', 'lunara-film' ),
        'priority' => 36,
        'description' => __( 'Footer tagline, columns, and copyright.', 'lunara-film' ),
    ) );

    // ── PANEL 1: SITE SHELL ─────────────────────────────────────────────────

    // Lunara Header Section
    $wp_customize->add_section( 'lunara_header_options', array(
        'title'    => __( 'Header', 'lunara-film' ),
        'panel'    => 'lunara_shell_panel',
        'priority' => 10,
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
        'type'        => 'range',
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
        'type'        => 'range',
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
            'type'        => 'range',
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
        'type'        => 'range',
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
                'default' => '#A8A8B8',
            ),
            'lunara_header_link_color' => array(
                'label'   => __( 'Header Link Color', 'lunara-film' ),
                'default' => '#e0c481',
            ),
            'lunara_header_link_hover_color' => array(
                'label'   => __( 'Header Link Hover Color', 'lunara-film' ),
                'default' => '#FAFBFC',
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

    // Sticky Header toggle.
    $wp_customize->add_setting( 'lunara_sticky_header', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_sticky_header', array(
        'label'   => __( 'Enable Sticky Header', 'lunara-film' ),
        'section' => 'lunara_header_options',
        'type'    => 'checkbox',
    ) );

    // Transparent Header on Homepage toggle.
    $wp_customize->add_setting( 'lunara_transparent_header', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_transparent_header', array(
        'label'   => __( 'Transparent Header on Homepage', 'lunara-film' ),
        'section' => 'lunara_header_options',
        'type'    => 'checkbox',
    ) );

    // ── Dropdown Menus Section ──
    $wp_customize->add_section( 'lunara_dropdown_options', array(
        'title'    => __( 'Dropdown Menus', 'lunara-film' ),
        'panel'    => 'lunara_shell_panel',
        'priority' => 20,
    ) );

    $dropdown_color_controls = array(
        'lunara_dropdown_bg' => array(
            'label'   => __( 'Dropdown Background', 'lunara-film' ),
            'default' => '#0f1d2e',
        ),
        'lunara_dropdown_hover_bg' => array(
            'label'   => __( 'Dropdown Hover Background', 'lunara-film' ),
            'default' => '#0f1d2e',
        ),
        'lunara_dropdown_text_color' => array(
            'label'   => __( 'Dropdown Text Color', 'lunara-film' ),
            'default' => '#FAFBFC',
        ),
        'lunara_dropdown_hover_text' => array(
            'label'   => __( 'Dropdown Hover Text', 'lunara-film' ),
            'default' => '#e0c481',
        ),
    );

    foreach ( $dropdown_color_controls as $setting => $control ) {
        $wp_customize->add_setting( $setting, array(
            'default'           => $control['default'],
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ) );

        if ( class_exists( 'WP_Customize_Color_Control' ) ) {
            $wp_customize->add_control(
                new WP_Customize_Color_Control(
                    $wp_customize,
                    $setting,
                    array(
                        'label'   => $control['label'],
                        'section' => 'lunara_dropdown_options',
                    )
                )
            );
        }
    }

    // Dropdown border color (rgba value, uses text sanitizer).
    $wp_customize->add_setting( 'lunara_dropdown_border_color', array(
        'default'           => 'rgba(201,169,97,0.15)',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_dropdown_border_color', array(
        'label'       => __( 'Dropdown Border Color', 'lunara-film' ),
        'section'     => 'lunara_dropdown_options',
        'type'        => 'text',
        'description' => __( 'Accepts hex or rgba values, e.g. rgba(201,169,97,0.15).', 'lunara-film' ),
    ) );

    // Dropdown width.
    $wp_customize->add_setting( 'lunara_dropdown_width', array(
        'default'           => 220,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_dropdown_width', array(
        'label'       => __( 'Dropdown Width (px)', 'lunara-film' ),
        'section'     => 'lunara_dropdown_options',
        'type'        => 'range',
        'input_attrs' => array(
            'min'  => 160,
            'max'  => 320,
            'step' => 1,
        ),
    ) );

    // Dropdown padding.
    $wp_customize->add_setting( 'lunara_dropdown_padding', array(
        'default'           => 10,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_dropdown_padding', array(
        'label'       => __( 'Dropdown Padding (px)', 'lunara-film' ),
        'section'     => 'lunara_dropdown_options',
        'type'        => 'range',
        'input_attrs' => array(
            'min'  => 4,
            'max'  => 20,
            'step' => 1,
        ),
    ) );

    // ── Mobile Menu Section ──
    $wp_customize->add_section( 'lunara_mobile_menu_options', array(
        'title'    => __( 'Mobile Menu', 'lunara-film' ),
        'panel'    => 'lunara_shell_panel',
        'priority' => 30,
    ) );

    $wp_customize->add_setting( 'lunara_mobile_panel_width', array(
        'default'           => 320,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_mobile_panel_width', array(
        'label'       => __( 'Mobile Panel Width (px)', 'lunara-film' ),
        'section'     => 'lunara_mobile_menu_options',
        'type'        => 'range',
        'input_attrs' => array(
            'min'  => 260,
            'max'  => 480,
            'step' => 1,
        ),
    ) );

    $wp_customize->add_setting( 'lunara_mobile_panel_bg', array(
        'default'           => '#0a1520',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ) );

    if ( class_exists( 'WP_Customize_Color_Control' ) ) {
        $wp_customize->add_control(
            new WP_Customize_Color_Control(
                $wp_customize,
                'lunara_mobile_panel_bg',
                array(
                    'label'   => __( 'Mobile Panel Background', 'lunara-film' ),
                    'section' => 'lunara_mobile_menu_options',
                )
            )
        );
    }

    $wp_customize->add_setting( 'lunara_mobile_panel_direction', array(
        'default'           => 'right',
        'sanitize_callback' => 'lunara_sanitize_select',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_mobile_panel_direction', array(
        'label'   => __( 'Panel Slide Direction', 'lunara-film' ),
        'section' => 'lunara_mobile_menu_options',
        'type'    => 'select',
        'choices' => array(
            'left'  => __( 'Left', 'lunara-film' ),
            'right' => __( 'Right', 'lunara-film' ),
        ),
    ) );

    $wp_customize->add_setting( 'lunara_mobile_link_size', array(
        'default'           => 16,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_mobile_link_size', array(
        'label'       => __( 'Mobile Link Font Size (px)', 'lunara-film' ),
        'section'     => 'lunara_mobile_menu_options',
        'type'        => 'range',
        'input_attrs' => array(
            'min'  => 13,
            'max'  => 22,
            'step' => 1,
        ),
    ) );

    $mobile_link_colors = array(
        'lunara_mobile_link_color' => array(
            'label'   => __( 'Mobile Link Color', 'lunara-film' ),
            'default' => '#FAFBFC',
        ),
        'lunara_mobile_link_hover' => array(
            'label'   => __( 'Mobile Link Hover Color', 'lunara-film' ),
            'default' => '#e0c481',
        ),
    );

    foreach ( $mobile_link_colors as $setting => $control ) {
        $wp_customize->add_setting( $setting, array(
            'default'           => $control['default'],
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ) );

        if ( class_exists( 'WP_Customize_Color_Control' ) ) {
            $wp_customize->add_control(
                new WP_Customize_Color_Control(
                    $wp_customize,
                    $setting,
                    array(
                        'label'   => $control['label'],
                        'section' => 'lunara_mobile_menu_options',
                    )
                )
            );
        }
    }

    $wp_customize->add_section(
        'lunara_global_design_options',
        array(
            'title'       => __( 'Global Design', 'lunara-film' ),
            'panel'       => 'lunara_shell_panel',
            'priority'    => 40,
            'description' => __( 'Control the sitewide palette, typography, shell width, and card styling without editing CSS.', 'lunara-film' ),
        )
    );

    $global_design_controls = array(
        array(
            'setting'  => 'lunara_shell_content_width',
            'default'  => 1360,
            'label'    => __( 'Content Width (px)', 'lunara-film' ),
            'type'     => 'range',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 960, 'max' => 1800, 'step' => 10 ),
        ),
        array(
            'setting'  => 'lunara_shell_side_padding',
            'default'  => 28,
            'label'    => __( 'Content Side Padding (px)', 'lunara-film' ),
            'type'     => 'range',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 12, 'max' => 96, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_surface_radius',
            'default'  => 28,
            'label'    => __( 'Card Corner Radius (px)', 'lunara-film' ),
            'type'     => 'range',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 0, 'max' => 48, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_body_font_size',
            'default'  => 17,
            'label'    => __( 'Body Font Size (px)', 'lunara-film' ),
            'type'     => 'range',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 14, 'max' => 24, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_body_line_height',
            'default'  => 1.7,
            'label'    => __( 'Body Line Height', 'lunara-film' ),
            'type'     => 'range',
            'sanitize' => 'lunara_sanitize_decimal',
            'attrs'    => array( 'min' => 1.3, 'max' => 2.2, 'step' => 0.05 ),
        ),
        array(
            'setting'  => 'lunara_section_title_size',
            'default'  => 34,
            'label'    => __( 'Section Heading Size (px)', 'lunara-film' ),
            'type'     => 'range',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 24, 'max' => 64, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_hero_title_size',
            'default'  => 72,
            'label'    => __( 'Hero Title Size (px)', 'lunara-film' ),
            'type'     => 'range',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 40, 'max' => 120, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_hero_copy_size',
            'default'  => 19,
            'label'    => __( 'Hero Copy Size (px)', 'lunara-film' ),
            'type'     => 'range',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 15, 'max' => 30, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_kicker_size',
            'default'  => 12,
            'label'    => __( 'Kicker Size (px)', 'lunara-film' ),
            'type'     => 'range',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 10, 'max' => 18, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_kicker_tracking',
            'default'  => 0.16,
            'label'    => __( 'Kicker Letter Spacing (em)', 'lunara-film' ),
            'type'     => 'range',
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

    // ── PANEL 4: EDITORIAL ─────────────────────────────────────────────────

    // Lunara Debrief Section (signature controls)
    $wp_customize->add_section( 'lunara_debrief_options', array(
        'title'    => __( 'Debrief Signature', 'lunara-film' ),
        'panel'    => 'lunara_editorial_panel',
        'priority' => 10,
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

    // ── PANEL 2: HOMEPAGE ─────────────────────────────────────────────────

    $wp_customize->add_section( 'lunara_homepage_pulse_options', array(
        'title'    => __( 'Hero & Pulse Cards', 'lunara-film' ),
        'panel'    => 'lunara_homepage_panel',
        'priority' => 10,
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
        'lunara_homepage_sections_options',
        array(
            'title'       => __( 'Homepage Sections', 'lunara-film' ),
            'panel'       => 'lunara_homepage_panel',
            'priority'    => 19,
            'description' => __( 'Turn homepage sections on or off and control their order from one place.', 'lunara-film' ),
        )
    );

    $wp_customize->add_section(
        'lunara_homepage_layout_options',
        array(
            'title'       => __( 'Layout & Visibility', 'lunara-film' ),
            'panel'       => 'lunara_homepage_panel',
            'priority'    => 20,
            'description' => __( 'Control homepage shell width, spacing, and high-level layout without editing templates.', 'lunara-film' ),
        )
    );

    $wp_customize->add_section(
        'lunara_homepage_journal_options',
        array(
            'title'       => __( 'Homepage Journal', 'lunara-film' ),
            'panel'       => 'lunara_homepage_panel',
            'priority'    => 21,
            'description' => __( 'Tune the homepage Journal lane in one place: lead image sizing, split-card media, rail layout, stacked image crops, title scale, and mobile behavior.', 'lunara-film' ),
        )
    );

    $homepage_layout_controls = array(
        array(
            'setting'  => 'lunara_home_max_width',
            'default'  => 1400,
            'label'    => __( 'Homepage Max Width (px)', 'lunara-film' ),
            'type'     => 'range',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 960, 'max' => 1800, 'step' => 10 ),
        ),
        array(
            'setting'  => 'lunara_home_side_padding',
            'default'  => 40,
            'label'    => __( 'Homepage Side Padding (px)', 'lunara-film' ),
            'type'     => 'range',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 12, 'max' => 96, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_home_section_gap',
            'default'  => 72,
            'label'    => __( 'Homepage Section Gap (px)', 'lunara-film' ),
            'type'     => 'range',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 24, 'max' => 140, 'step' => 1 ),
        ),
        array(
            'setting'  => 'lunara_home_hero_top_padding',
            'default'  => 92,
            'label'    => __( 'Hero Top Padding (px)', 'lunara-film' ),
            'type'     => 'range',
            'sanitize' => 'absint',
            'attrs'    => array( 'min' => 24, 'max' => 180, 'step' => 1 ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_lead_media_min_width',
            'default'     => 180,
            'label'       => __( 'Journal Lead Media Width (px)', 'lunara-film' ),
            'type'        => 'range',
            'sanitize'    => 'absint',
            'section'     => 'lunara_homepage_journal_options',
            'attrs'       => array( 'min' => 180, 'max' => 360, 'step' => 10 ),
            'description' => __( 'Shrinks or enlarges the big homepage Journal image column on desktop.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_layout_split',
            'default'     => 44,
            'label'       => __( 'Journal Lead Column Share (%)', 'lunara-film' ),
            'type'        => 'range',
            'sanitize'    => 'absint',
            'section'     => 'lunara_homepage_journal_options',
            'attrs'       => array( 'min' => 36, 'max' => 62, 'step' => 1 ),
            'description' => __( 'Pushes the homepage Journal lead card narrower or wider relative to the supporting rail.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_lead_media_max_height',
            'default'     => 360,
            'label'       => __( 'Journal Lead Media Max Height (px)', 'lunara-film' ),
            'type'        => 'range',
            'sanitize'    => 'absint',
            'section'     => 'lunara_homepage_journal_options',
            'attrs'       => array( 'min' => 320, 'max' => 760, 'step' => 10 ),
            'description' => __( 'Caps how tall the homepage Journal lead image can grow before it starts feeling oversized.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_split_card_media_max_height',
            'default'     => 140,
            'label'       => __( 'Journal Split Lead Card Image Height (px)', 'lunara-film' ),
            'type'        => 'range',
            'sanitize'    => 'absint',
            'section'     => 'lunara_homepage_journal_options',
            'attrs'       => array( 'min' => 96, 'max' => 220, 'step' => 4 ),
            'description' => __( 'Controls the image height inside the smaller split cards that appear under the homepage Journal lead.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_lead_media_ratio',
            'default'     => 'cinematic',
            'label'       => __( 'Journal Lead Media Shape', 'lunara-film' ),
            'type'        => 'select',
            'sanitize'    => 'lunara_sanitize_select',
            'section'     => 'lunara_homepage_journal_options',
            'choices'     => lunara_get_dispatch_ratio_choices(),
            'description' => __( 'Choose the desktop crop shape for the big homepage Journal image.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_rail_thumb_width',
            'default'     => 92,
            'label'       => __( 'Journal Rail Thumb Width (px)', 'lunara-film' ),
            'type'        => 'range',
            'sanitize'    => 'absint',
            'section'     => 'lunara_homepage_journal_options',
            'attrs'       => array( 'min' => 88, 'max' => 220, 'step' => 4 ),
            'description' => __( 'Controls the size of the supporting Journal thumbnails beside each rail item in side-by-side mode, and gently reins in the image height in stacked mode.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_rail_stack_image_height',
            'default'     => 124,
            'label'       => __( 'Journal Rail Stacked Image Height (px)', 'lunara-film' ),
            'type'        => 'range',
            'sanitize'    => 'absint',
            'section'     => 'lunara_homepage_journal_options',
            'attrs'       => array( 'min' => 96, 'max' => 220, 'step' => 4 ),
            'description' => __( 'Directly controls the image height when the Journal rail cards are in stacked mode, so the cards stop feeling oversized on desktop.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_rail_card_layout',
            'default'     => 'split',
            'label'       => __( 'Journal Rail Card Layout', 'lunara-film' ),
            'type'        => 'radio',
            'sanitize'    => 'lunara_sanitize_rail_card_layout',
            'section'     => 'lunara_homepage_journal_options',
            'choices'     => array(
                'stack' => __( 'Stacked (image on top, title below)', 'lunara-film' ),
                'split' => __( 'Side-by-side (thumbnail beside copy)', 'lunara-film' ),
            ),
            'description' => __( 'Choose whether the supporting Journal cards stack the image above the text or use the tighter side-by-side layout. Side-by-side is the better fit if the stacked images feel oversized.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_rail_card_image_ratio',
            'default'     => '16/10',
            'label'       => __( 'Journal Rail Stacked Image Shape', 'lunara-film' ),
            'type'        => 'select',
            'sanitize'    => 'lunara_sanitize_rail_card_image_ratio',
            'section'     => 'lunara_homepage_journal_options',
            'choices'     => array(
                '4/3'   => __( '4 : 3 (taller)', 'lunara-film' ),
                '3/2'   => __( '3 : 2 (classic photo)', 'lunara-film' ),
                '16/10' => __( '16 : 10 (balanced)', 'lunara-film' ),
                '16/9'  => __( '16 : 9 (widescreen)', 'lunara-film' ),
                '2/1'   => __( '2 : 1 (panoramic)', 'lunara-film' ),
                '21/9'  => __( '21 : 9 (cinematic banner)', 'lunara-film' ),
            ),
            'description' => __( 'Sets the crop shape for the full-width image when the Journal rail cards are in stacked mode.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_rail_card_title_size',
            'default'     => 22,
            'label'       => __( 'Journal Rail Title Size (px)', 'lunara-film' ),
            'type'        => 'range',
            'sanitize'    => 'absint',
            'section'     => 'lunara_homepage_journal_options',
            'attrs'       => array( 'min' => 16, 'max' => 32, 'step' => 1 ),
            'description' => __( 'Controls the Journal rail headline size so the stacked and side-by-side cards can be tuned from the same homepage panel.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_mobile_lead_max_height',
            'default'     => 188,
            'label'       => __( 'Journal Mobile Lead Height (px)', 'lunara-film' ),
            'type'        => 'range',
            'sanitize'    => 'absint',
            'section'     => 'lunara_homepage_journal_options',
            'attrs'       => array( 'min' => 160, 'max' => 320, 'step' => 4 ),
            'description' => __( 'Caps how tall the homepage Journal lead image gets on phones.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_mobile_rail_thumb_width',
            'default'     => 68,
            'label'       => __( 'Journal Mobile Rail Thumb Width (px)', 'lunara-film' ),
            'type'        => 'range',
            'sanitize'    => 'absint',
            'section'     => 'lunara_homepage_journal_options',
            'attrs'       => array( 'min' => 60, 'max' => 120, 'step' => 2 ),
            'description' => __( 'Controls the supporting Journal thumbnail width on phones.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_dispatch_rail_thumb_ratio',
            'default'     => 'cinematic',
            'label'       => __( 'Journal Rail Thumb Shape', 'lunara-film' ),
            'type'        => 'select',
            'sanitize'    => 'lunara_sanitize_select',
            'section'     => 'lunara_homepage_journal_options',
            'choices'     => lunara_get_dispatch_ratio_choices(),
            'description' => __( 'Choose the crop shape for the supporting homepage Journal thumbnails.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_home_section_order',
            'default'     => implode( ',', lunara_get_home_section_slugs() ),
            'label'       => __( 'Section Order', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'lunara_sanitize_home_section_order',
            'section'     => 'lunara_homepage_sections_options',
            'description' => sprintf(
                /* translators: %s is the comma-separated list of recognized homepage section slugs. */
                __( 'Comma-separated slugs: %s', 'lunara-film' ),
                implode( ', ', lunara_get_home_section_slugs() )
            ),
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
            'section'     => ! empty( $control['section'] ) ? $control['section'] : 'lunara_homepage_layout_options',
            'type'        => $control['type'],
            'description' => ! empty( $control['description'] ) ? $control['description'] : '',
        );

        if ( ! empty( $control['attrs'] ) ) {
            $args['input_attrs'] = $control['attrs'];
        }

        if ( ! empty( $control['choices'] ) ) {
            $args['choices'] = $control['choices'];
        }

        $wp_customize->add_control( $control['setting'], $args );
    }

    foreach ( lunara_get_home_section_registry() as $section ) {
        if ( empty( $section['setting'] ) || empty( $section['toggle_label'] ) ) {
            continue;
        }

        $wp_customize->add_setting( $section['setting'], array(
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
            'transport'         => 'refresh',
        ) );

        $wp_customize->add_control( $section['setting'], array(
            'label'       => $section['toggle_label'],
            'section'     => 'lunara_homepage_sections_options',
            'type'        => 'checkbox',
            'description' => ! empty( $section['description'] ) ? $section['description'] : '',
        ) );
    }

    $wp_customize->add_section(
        'lunara_homepage_editorial_options',
        array(
            'title'    => __( 'Content Curation', 'lunara-film' ),
            'panel'    => 'lunara_homepage_panel',
            'priority' => 30,
        )
    );

    $wp_customize->add_section(
        'lunara_festival_qr_options',
        array(
            'title'       => __( 'Festival QR', 'lunara-film' ),
            'panel'       => 'lunara_utility_panel',
            'priority'    => 40,
            'description' => __( 'Configure the stable festival QR route. The QR code can point to https://lunarafilm.com/?lunara_qr=festival while this target URL stays editable.', 'lunara-film' ),
        )
    );

    $wp_customize->add_setting(
        'lunara_festival_qr_target_url',
        array(
            'default'           => home_url( '/' ),
            'sanitize_callback' => 'esc_url_raw',
            'transport'         => 'refresh',
        )
    );

    $wp_customize->add_control(
        'lunara_festival_qr_target_url',
        array(
            'label'       => __( 'Festival QR Destination URL', 'lunara-film' ),
            'section'     => 'lunara_festival_qr_options',
            'type'        => 'url',
            'description' => __( 'When someone scans your festival QR code, this is where they land. You can change it any time without replacing the QR image.', 'lunara-film' ),
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
            'setting'     => 'lunara_home_latest_review_ids',
            'default'     => '',
            'label'       => __( 'Current Release Review IDs or URLs', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
            'description' => __( 'Pin reviews to the Latest Reviews block when an older festival review becomes newly current. The remaining cards fill with newest reviews.', 'lunara-film' ),
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
            'setting'     => 'lunara_home_journal_lead_post_id',
            'default'     => '',
            'label'       => __( 'Featured Journal Lead ID or URL', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Paste one published Journal ID or URL. It becomes the first card on the homepage Journal lane and the default Journal archive.', 'lunara-film' ),
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
        'lunara_editorial_archive_sections_options',
        array(
            'title'       => __( 'Archive Sections', 'lunara-film' ),
            'panel'       => 'lunara_editorial_panel',
            'priority'    => 21,
            'description' => __( 'Control the section order and visibility for the Reviews archive and the Journal archive. The old /news/ route now follows the Journal editorial system.', 'lunara-film' ),
        )
    );

    $wp_customize->add_section(
        'lunara_journal_single_options',
        array(
            'title'       => __( 'Journal Single', 'lunara-film' ),
            'panel'       => 'lunara_editorial_panel',
            'priority'    => 19,
            'description' => __( 'Tune the Journal single hero without touching template code.', 'lunara-film' ),
        )
    );

    $wp_customize->add_section(
        'lunara_editorial_archive_options',
        array(
            'title'       => __( 'Editorial Archives', 'lunara-film' ),
            'panel'       => 'lunara_editorial_panel',
            'priority'    => 20,
            'description' => __( 'Shape the review archive and the journal/news archive without editing templates.', 'lunara-film' ),
        )
    );

    $editorial_archive_controls = array(
        array(
            'setting'  => 'lunara_reviews_archive_kicker',
            'default'  => 'Criticism Desk',
            'label'    => __( 'Reviews Archive Kicker', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'  => 'lunara_reviews_archive_title',
            'default'  => 'Lunara Reviews',
            'label'    => __( 'Reviews Archive Title', 'lunara-film' ),
            'type'     => 'text',
            'sanitize' => 'sanitize_text_field',
        ),
        array(
            'setting'     => 'lunara_reviews_archive_copy',
            'default'     => 'Spoiler-free criticism, full-spoiler companion files, festival finds, and the films that deserve a longer argument after the credits roll.',
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

    $wp_customize->add_setting(
        'lunara_journal_single_hero_title_size',
        array(
            'default'           => 84,
            'sanitize_callback' => 'absint',
            'transport'         => 'refresh',
        )
    );

    $wp_customize->add_control(
        'lunara_journal_single_hero_title_size',
        array(
            'label'       => __( 'Journal Hero Title Size (px)', 'lunara-film' ),
            'section'     => 'lunara_journal_single_options',
            'type'        => 'range',
            'description' => __( 'Shrink or enlarge the giant Journal single headline. Lower values make the hero feel more disciplined.', 'lunara-film' ),
            'input_attrs' => array(
                'min'  => 48,
                'max'  => 120,
                'step' => 1,
            ),
        )
    );

    $wp_customize->add_setting(
        'lunara_reviews_archive_section_order',
        array(
            'default'           => implode( ',', lunara_get_registry_slugs( lunara_get_reviews_archive_section_registry() ) ),
            'sanitize_callback' => 'lunara_sanitize_reviews_archive_section_order',
            'transport'         => 'refresh',
        )
    );

    $wp_customize->add_control(
        'lunara_reviews_archive_section_order',
        array(
            'label'       => __( 'Reviews Archive Section Order', 'lunara-film' ),
            'section'     => 'lunara_editorial_archive_sections_options',
            'type'        => 'text',
            'description' => __( 'Comma-separated slugs: hero, grid, pagination.', 'lunara-film' ),
        )
    );

    foreach ( lunara_get_reviews_archive_section_registry() as $section ) {
        if ( empty( $section['setting'] ) ) {
            continue;
        }

        $wp_customize->add_setting(
            $section['setting'],
            array(
                'default'           => true,
                'sanitize_callback' => 'wp_validate_boolean',
                'transport'         => 'refresh',
            )
        );

        $wp_customize->add_control(
            $section['setting'],
            array(
                'label'       => $section['toggle_label'],
                'section'     => 'lunara_editorial_archive_sections_options',
                'type'        => 'checkbox',
                'description' => $section['description'],
            )
        );
    }

    $wp_customize->add_setting(
        'lunara_journal_archive_section_order',
        array(
            'default'           => implode( ',', lunara_get_registry_slugs( lunara_get_journal_archive_section_registry() ) ),
            'sanitize_callback' => 'lunara_sanitize_journal_archive_section_order',
            'transport'         => 'refresh',
        )
    );

    $wp_customize->add_control(
        'lunara_journal_archive_section_order',
        array(
            'label'       => __( 'Journal Archive Section Order', 'lunara-film' ),
            'section'     => 'lunara_editorial_archive_sections_options',
            'type'        => 'text',
            'description' => __( 'Comma-separated slugs: hero, filters, grid, pagination.', 'lunara-film' ),
        )
    );

    foreach ( lunara_get_journal_archive_section_registry() as $section ) {
        if ( empty( $section['setting'] ) ) {
            continue;
        }

        $wp_customize->add_setting(
            $section['setting'],
            array(
                'default'           => true,
                'sanitize_callback' => 'wp_validate_boolean',
                'transport'         => 'refresh',
            )
        );

        $wp_customize->add_control(
            $section['setting'],
            array(
                'label'       => $section['toggle_label'],
                'section'     => 'lunara_editorial_archive_sections_options',
                'type'        => 'checkbox',
                'description' => $section['description'],
            )
        );
    }

    // ── Single Post Layout Section ──
    $wp_customize->add_section( 'lunara_single_post_layout_options', array(
        'title'       => __( 'Post & Review Layout', 'lunara-film' ),
        'panel'       => 'lunara_reviews_panel',
        'priority'    => 20,
        'description' => __( 'Control the layout of individual reviews and posts.', 'lunara-film' ),
    ) );

    $wp_customize->add_setting( 'lunara_single_review_layout', array(
        'default'           => 'standard',
        'sanitize_callback' => 'lunara_sanitize_select',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_single_review_layout', array(
        'label'   => __( 'Review Layout', 'lunara-film' ),
        'section' => 'lunara_single_post_layout_options',
        'type'    => 'select',
        'choices' => array(
            'standard'   => __( 'Standard', 'lunara-film' ),
            'narrow'     => __( 'Narrow', 'lunara-film' ),
            'full-width' => __( 'Full Width', 'lunara-film' ),
        ),
    ) );

    $wp_customize->add_setting( 'lunara_single_post_layout', array(
        'default'           => 'standard',
        'sanitize_callback' => 'lunara_sanitize_select',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_single_post_layout', array(
        'label'   => __( 'Post Layout', 'lunara-film' ),
        'section' => 'lunara_single_post_layout_options',
        'type'    => 'select',
        'choices' => array(
            'standard'   => __( 'Standard', 'lunara-film' ),
            'narrow'     => __( 'Narrow', 'lunara-film' ),
            'full-width' => __( 'Full Width', 'lunara-film' ),
        ),
    ) );

    $wp_customize->add_setting( 'lunara_single_content_max_width', array(
        'default'           => 780,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'lunara_single_content_max_width', array(
        'label'       => __( 'Content Max Width (px)', 'lunara-film' ),
        'section'     => 'lunara_single_post_layout_options',
        'type'        => 'range',
        'input_attrs' => array(
            'min'  => 600,
            'max'  => 1200,
            'step' => 10,
        ),
    ) );

    // ── PANEL 6: OSCARS ──────────────────────────────────────────────────

    $wp_customize->add_section(
        'lunara_oscars_portal_sections_options',
        array(
            'title'       => __( 'Oscars Sections', 'lunara-film' ),
            'panel'       => 'lunara_oscars_panel',
            'priority'    => 11,
            'description' => __( 'Control the order and visibility of the dedicated /oscars/ portal sections.', 'lunara-film' ),
        )
    );

    $wp_customize->add_section(
        'lunara_oscars_portal_options',
        array(
            'title'       => __( 'Oscars Portal', 'lunara-film' ),
            'panel'       => 'lunara_oscars_panel',
            'priority'    => 10,
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
        array(
            'setting'     => 'lunara_oscars_portal_explore_kicker',
            'default'     => 'Explore the Portal',
            'label'       => __( 'Explore Section Kicker', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Small label above the explore-links section.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_oscars_portal_spotlights_heading',
            'default'     => 'Latest Ceremony, category by category.',
            'label'       => __( 'Spotlights Heading', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Headline above the latest ceremony spotlight cards.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_oscars_portal_titles_kicker',
            'default'     => 'Poster-Led Entry Points',
            'label'       => __( 'Title Cards Kicker', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Small label above the title-card section.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_oscars_portal_titles_heading',
            'default'     => 'Open the ledger through the films themselves.',
            'label'       => __( 'Title Cards Heading', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Headline above the poster-led title cards.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_oscars_portal_research_kicker',
            'default'     => 'Research Mode',
            'label'       => __( 'Research Section Kicker', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Small label above the embedded ledger landing and data-explorer section.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_oscars_portal_research_heading',
            'default'     => 'Open the ledger without leaving the portal.',
            'label'       => __( 'Research Section Heading', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Headline above the embedded Oscars research layer.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_oscars_portal_research_copy',
            'default'     => 'This is where the public Oscars front door and the deeper research layer finally become one system: poster-led winner cards, direct category and ceremony navigation, and the full table when you want raw row-level work.',
            'label'       => __( 'Research Section Copy', 'lunara-film' ),
            'type'        => 'textarea',
            'sanitize'    => 'sanitize_textarea_field',
            'description' => __( 'Summary copy for the embedded ledger landing and table-explorer section.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_oscars_latest_winners_heading',
            'default'     => 'Latest Ceremony Winners',
            'label'       => __( 'Latest Winners Fallback Heading', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Fallback heading when the current ceremony label is unavailable.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_oscars_latest_winners_link_label',
            'default'     => 'Full Ceremony',
            'label'       => __( 'Latest Winners Link Label', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Button label for the latest ceremony winners section.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_oscars_rotating_winners_kicker',
            'default'     => 'Oscars Deep Dive',
            'label'       => __( 'Rotating Winners Kicker', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Small label above the daily rotating ceremony carousel.', 'lunara-film' ),
        ),
        array(
            'setting'     => 'lunara_oscars_rotating_winners_link_label',
            'default'     => 'Open This Ceremony',
            'label'       => __( 'Rotating Winners Link Label', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'Button label under the rotating ceremony winners carousel.', 'lunara-film' ),
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

    $wp_customize->add_setting(
        'lunara_oscars_portal_section_order',
        array(
            'default'           => implode( ',', lunara_get_registry_slugs( lunara_get_oscars_portal_section_registry() ) ),
            'sanitize_callback' => 'lunara_sanitize_oscars_portal_section_order',
            'transport'         => 'refresh',
        )
    );

    $wp_customize->add_control(
        'lunara_oscars_portal_section_order',
        array(
            'label'       => __( 'Oscars Section Order', 'lunara-film' ),
            'section'     => 'lunara_oscars_portal_sections_options',
            'type'        => 'text',
            'description' => __( 'Comma-separated slugs: hero, portal-links, spotlights, titles, research, latest-winners, deep-cuts, rotating-winners, linked-reviews.', 'lunara-film' ),
        )
    );

    foreach ( lunara_get_oscars_portal_section_registry() as $section ) {
        if ( empty( $section['setting'] ) ) {
            continue;
        }

        $default_enabled = 'linked-reviews' !== sanitize_title( (string) $section['label'] );
        if ( 'lunara_oscars_show_linked_reviews' === $section['setting'] ) {
            $default_enabled = false;
        }

        $wp_customize->add_setting(
            $section['setting'],
            array(
                'default'           => $default_enabled,
                'sanitize_callback' => 'wp_validate_boolean',
                'transport'         => 'refresh',
            )
        );

        $wp_customize->add_control(
            $section['setting'],
            array(
                'label'       => $section['toggle_label'],
                'section'     => 'lunara_oscars_portal_sections_options',
                'type'        => 'checkbox',
                'description' => $section['description'],
            )
        );
    }

    // Ledger Stories IMDb ID overrides.
    $wp_customize->add_section( 'lunara_homepage_ledger_options', array(
        'title'       => __( 'Ledger Stories', 'lunara-film' ),
        'panel'       => 'lunara_homepage_panel',
        'priority'    => 40,
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

    // ── PANEL 7: FOOTER ──────────────────────────────────────────────────

    $wp_customize->add_section( 'lunara_footer_options', array(
        'title'    => __( 'Footer', 'lunara-film' ),
        'panel'    => 'lunara_footer_panel',
        'priority' => 10,
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
    // ── PANEL 3: REVIEWS — Review Labels & Layout (ported from monolith + new) ──

    $wp_customize->add_section( 'lunara_review_layout_options', array(
        'title'       => __( 'Review Labels & Debrief', 'lunara-film' ),
        'panel'       => 'lunara_reviews_panel',
        'priority'    => 10,
        'description' => __( 'Control default review labels, sidebar card visibility, debrief sizing, and related section text.', 'lunara-film' ),
    ) );

    // Ported from monolith: review layout controls.
    $review_layout_controls = array(
        array(
            'setting'     => 'lunara_review_default_label',
            'default'     => 'Lunara Review',
            'label'       => __( 'Default Review Label', 'lunara-film' ),
            'type'        => 'text',
            'sanitize'    => 'sanitize_text_field',
            'description' => __( 'The lane label shown on review pages when no per-review override is set.', 'lunara-film' ),
        ),
        array(
            'setting'  => 'lunara_review_show_standfirst_default',
            'default'  => true,
            'label'    => __( 'Show Standfirst by Default', 'lunara-film' ),
            'type'     => 'checkbox',
            'sanitize' => 'wp_validate_boolean',
        ),
        array(
            'setting'  => 'lunara_review_show_where_card_default',
            'default'  => true,
            'label'    => __( 'Show "Where to Watch" Card', 'lunara-film' ),
            'type'     => 'checkbox',
            'sanitize' => 'wp_validate_boolean',
        ),
        array(
            'setting'  => 'lunara_review_show_details_card_default',
            'default'  => true,
            'label'    => __( 'Show "Review Details" Card', 'lunara-film' ),
            'type'     => 'checkbox',
            'sanitize' => 'wp_validate_boolean',
        ),
    );

    foreach ( $review_layout_controls as $rlc ) {
        $wp_customize->add_setting( $rlc['setting'], array(
            'default'           => $rlc['default'],
            'sanitize_callback' => $rlc['sanitize'],
            'transport'         => 'refresh',
        ) );
        $args = array(
            'label'       => $rlc['label'],
            'section'     => 'lunara_review_layout_options',
            'type'        => $rlc['type'],
            'description' => ! empty( $rlc['description'] ) ? $rlc['description'] : '',
        );
        $wp_customize->add_control( $rlc['setting'], $args );
    }

    // Debrief sizing (range sliders).
    $review_debrief_controls = array(
        'lunara_review_debrief_width' => array(
            'label'   => __( 'Debrief Max Width (px)', 'lunara-film' ),
            'default' => 1180,
            'min'     => 980,
            'max'     => 1600,
            'step'    => 10,
        ),
        'lunara_review_debrief_poster_max' => array(
            'label'   => __( 'Debrief Poster Max Width (px)', 'lunara-film' ),
            'default' => 400,
            'min'     => 260,
            'max'     => 520,
            'step'    => 10,
        ),
        'lunara_review_debrief_signature_offset' => array(
            'label'   => __( 'Signature Panel Top Offset (px)', 'lunara-film' ),
            'default' => 56,
            'min'     => 0,
            'max'     => 160,
            'step'    => 4,
        ),
    );

    foreach ( $review_debrief_controls as $setting => $control ) {
        $wp_customize->add_setting( $setting, array(
            'default'           => $control['default'],
            'sanitize_callback' => 'absint',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( $setting, array(
            'label'       => $control['label'],
            'section'     => 'lunara_review_layout_options',
            'type'        => 'range',
            'input_attrs' => array(
                'min'  => $control['min'],
                'max'  => $control['max'],
                'step' => $control['step'],
            ),
        ) );
    }

    // NEW: Review single label overrides.
    $review_label_controls = array(
        array(
            'setting'  => 'lunara_review_where_kicker',
            'default'  => 'Where to Watch',
            'label'    => __( '"Where to Watch" Kicker', 'lunara-film' ),
        ),
        array(
            'setting'  => 'lunara_review_details_kicker',
            'default'  => 'Review Details',
            'label'    => __( '"Review Details" Kicker', 'lunara-film' ),
        ),
        array(
            'setting'  => 'lunara_review_related_kicker',
            'default'  => 'Continue Watching',
            'label'    => __( 'Related Section Kicker', 'lunara-film' ),
        ),
        array(
            'setting'  => 'lunara_review_related_title',
            'default'  => 'More Lunara Criticism',
            'label'    => __( 'Related Section Title', 'lunara-film' ),
        ),
        array(
            'setting'  => 'lunara_review_archive_button',
            'default'  => 'Browse Reviews',
            'label'    => __( 'Archive Button Label', 'lunara-film' ),
        ),
        array(
            'setting'  => 'lunara_review_director_button',
            'default'  => 'Director Archive',
            'label'    => __( 'Director Button Label', 'lunara-film' ),
        ),
    );

    foreach ( $review_label_controls as $rlc ) {
        $wp_customize->add_setting( $rlc['setting'], array(
            'default'           => $rlc['default'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( $rlc['setting'], array(
            'label'   => $rlc['label'],
            'section' => 'lunara_review_layout_options',
            'type'    => 'text',
        ) );
    }

    // Review related posts count.
    $wp_customize->add_setting( 'lunara_review_related_count', array(
        'default'           => 4,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'lunara_review_related_count', array(
        'label'       => __( 'Related Reviews Count', 'lunara-film' ),
        'section'     => 'lunara_review_layout_options',
        'type'        => 'range',
        'input_attrs' => array( 'min' => 2, 'max' => 8, 'step' => 1 ),
    ) );

    // ── HOMEPAGE TIMING & COUNTS (NEW) ──────────────────────────────────

    $wp_customize->add_section( 'lunara_homepage_counts_options', array(
        'title'       => __( 'Counts & Timing', 'lunara-film' ),
        'panel'       => 'lunara_homepage_panel',
        'priority'    => 25,
        'description' => __( 'Control carousel speed and how many items each homepage section shows.', 'lunara-film' ),
    ) );

    $homepage_count_controls = array(
        'lunara_home_hero_autoplay' => array(
            'label'   => __( 'Hero Carousel Speed (ms)', 'lunara-film' ),
            'default' => 4000,
            'min'     => 2000,
            'max'     => 10000,
            'step'    => 500,
        ),
        'lunara_home_lore_autoplay' => array(
            'label'   => __( 'Oscar Lore Carousel Speed (ms)', 'lunara-film' ),
            'default' => 7600,
            'min'     => 3000,
            'max'     => 15000,
            'step'    => 500,
        ),
        'lunara_home_featured_count' => array(
            'label'   => __( 'Featured Reviews Count', 'lunara-film' ),
            'default' => 8,
            'min'     => 4,
            'max'     => 16,
            'step'    => 1,
        ),
        'lunara_home_latest_count' => array(
            'label'   => __( 'Latest Reviews Count', 'lunara-film' ),
            'default' => 18,
            'min'     => 6,
            'max'     => 30,
            'step'    => 1,
        ),
        'lunara_home_dispatch_count' => array(
            'label'   => __( 'Homepage Journal Count', 'lunara-film' ),
            'default' => 4,
            'min'     => 2,
            'max'     => 8,
            'step'    => 1,
        ),
        'lunara_home_hero_review_count' => array(
            'label'   => __( 'Hero Review Cards', 'lunara-film' ),
            'default' => 4,
            'min'     => 2,
            'max'     => 6,
            'step'    => 1,
        ),
    );

    foreach ( $homepage_count_controls as $setting => $control ) {
        $wp_customize->add_setting( $setting, array(
            'default'           => $control['default'],
            'sanitize_callback' => 'absint',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( $setting, array(
            'label'       => $control['label'],
            'section'     => 'lunara_homepage_counts_options',
            'type'        => 'range',
            'input_attrs' => array(
                'min'  => $control['min'],
                'max'  => $control['max'],
                'step' => $control['step'],
            ),
        ) );
    }

    // ── STANDARD POST DEFAULTS (NEW) ────────────────────────────────────

    $wp_customize->add_section( 'lunara_standard_post_options', array(
        'title'       => __( 'Journal and Standard Post Defaults', 'lunara-film' ),
        'panel'       => 'lunara_editorial_panel',
        'priority'    => 30,
        'description' => __( 'Default labels and counts for Journal entries and any legacy standard posts.', 'lunara-film' ),
    ) );

    $post_label_controls = array(
        array(
            'setting' => 'lunara_post_details_kicker_default',
            'default' => 'Article Details',
            'label'   => __( 'Details Card Kicker', 'lunara-film' ),
        ),
        array(
            'setting' => 'lunara_post_signal_kicker_default',
            'default' => 'Signal Context',
            'label'   => __( 'Signal Card Kicker', 'lunara-film' ),
        ),
        array(
            'setting' => 'lunara_post_related_kicker_default',
            'default' => 'Continue Reading',
            'label'   => __( 'Related Section Kicker', 'lunara-film' ),
        ),
        array(
            'setting' => 'lunara_post_related_heading_default',
            'default' => 'More from the Journal',
            'label'   => __( 'Related Section Heading', 'lunara-film' ),
        ),
        array(
            'setting' => 'lunara_post_related_button_default',
            'default' => 'Open Archive',
            'label'   => __( 'Related Section Button', 'lunara-film' ),
        ),
    );

    foreach ( $post_label_controls as $plc ) {
        $wp_customize->add_setting( $plc['setting'], array(
            'default'           => $plc['default'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( $plc['setting'], array(
            'label'   => $plc['label'],
            'section' => 'lunara_standard_post_options',
            'type'    => 'text',
        ) );
    }

    $wp_customize->add_setting( 'lunara_post_related_count', array(
        'default'           => 3,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'lunara_post_related_count', array(
        'label'       => __( 'Related Posts Count', 'lunara-film' ),
        'section'     => 'lunara_standard_post_options',
        'type'        => 'range',
        'input_attrs' => array( 'min' => 2, 'max' => 6, 'step' => 1 ),
    ) );

    $wp_customize->add_setting( 'lunara_post_reading_wpm', array(
        'default'           => 225,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'lunara_post_reading_wpm', array(
        'label'       => __( 'Reading Speed (words/min)', 'lunara-film' ),
        'section'     => 'lunara_standard_post_options',
        'type'        => 'range',
        'input_attrs' => array( 'min' => 150, 'max' => 350, 'step' => 25 ),
    ) );

    // Dispatch type signal notes.
    $signal_note_controls = array(
        'lunara_post_signal_news'     => array( 'label' => __( 'News Signal Note', 'lunara-film' ), 'default' => 'Industry movement, festival motion, and awards pressure — reported and contextualized for readers tracking cinema as a living system.' ),
        'lunara_post_signal_reaction' => array( 'label' => __( 'Reaction Signal Note', 'lunara-film' ), 'default' => 'A sharper immediate response filed while the impression is still live.' ),
        'lunara_post_signal_essay'    => array( 'label' => __( 'Essay Signal Note', 'lunara-film' ), 'default' => 'A longer editorial line built for readers who want cinema taken seriously as argument, not recap.' ),
        'lunara_post_signal_dispatch' => array( 'label' => __( 'Dispatch Signal Note', 'lunara-film' ), 'default' => 'A Lunara filing built to move quickly and stay useful.' ),
    );

    foreach ( $signal_note_controls as $setting => $control ) {
        $wp_customize->add_setting( $setting, array(
            'default'           => $control['default'],
            'sanitize_callback' => 'sanitize_textarea_field',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( $setting, array(
            'label'   => $control['label'],
            'section' => 'lunara_standard_post_options',
            'type'    => 'textarea',
        ) );
    }

    // ── PANEL 5: UTILITY PAGES ──────────────────────────────────────────

    // 404 Page
    $wp_customize->add_section( 'lunara_404_options', array(
        'title'       => __( '404 Page', 'lunara-film' ),
        'panel'       => 'lunara_utility_panel',
        'priority'    => 10,
        'description' => __( 'Customize the lost-page recovery surface.', 'lunara-film' ),
    ) );

    $four_oh_four_controls = array(
        array( 'setting' => 'lunara_404_kicker',       'default' => 'Lost Signal',                           'label' => __( 'Kicker', 'lunara-film' ) ),
        array( 'setting' => 'lunara_404_title',        'default' => 'This page is not on the record.',       'label' => __( 'Title', 'lunara-film' ) ),
        array( 'setting' => 'lunara_404_reset_label',  'default' => 'Best Reset',                            'label' => __( 'Reset Label', 'lunara-film' ) ),
        array( 'setting' => 'lunara_404_reset_desc',   'default' => 'Return to the homepage and start fresh.', 'label' => __( 'Reset Description', 'lunara-film' ) ),
        array( 'setting' => 'lunara_404_fastest_label','default' => 'Fastest Route',                        'label' => __( 'Fastest Route Label', 'lunara-film' ) ),
        array( 'setting' => 'lunara_404_fastest_desc', 'default' => 'Search a title, name, or keyword.',     'label' => __( 'Fastest Route Description', 'lunara-film' ) ),
        array( 'setting' => 'lunara_404_hubs_label',   'default' => 'Stable Hubs',                          'label' => __( 'Hubs Label', 'lunara-film' ) ),
        array( 'setting' => 'lunara_404_hubs_desc',    'default' => 'Reviews / Journal / Oscar Ledger',      'label' => __( 'Hubs Description', 'lunara-film' ) ),
        array( 'setting' => 'lunara_404_reentry_title','default' => 'Choose the cleanest way back in.',     'label' => __( 'Re-entry Title', 'lunara-film' ) ),
    );

    foreach ( $four_oh_four_controls as $foc ) {
        $wp_customize->add_setting( $foc['setting'], array(
            'default'           => $foc['default'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( $foc['setting'], array(
            'label'   => $foc['label'],
            'section' => 'lunara_404_options',
            'type'    => 'text',
        ) );
    }

    $wp_customize->add_setting( 'lunara_404_explanation', array(
        'default'           => 'The route you followed does not match any published page, review, or ledger entry. It may have been moved, renamed, or removed from the record.',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'lunara_404_explanation', array(
        'label'   => __( 'Explanation Text', 'lunara-film' ),
        'section' => 'lunara_404_options',
        'type'    => 'textarea',
    ) );

    // Search Page
    $wp_customize->add_section( 'lunara_search_options', array(
        'title'       => __( 'Search Page', 'lunara-film' ),
        'panel'       => 'lunara_utility_panel',
        'priority'    => 20,
        'description' => __( 'Customize search results labels and behavior.', 'lunara-film' ),
    ) );

    $search_controls = array(
        array( 'setting' => 'lunara_search_kicker',      'default' => 'Search Desk',                             'label' => __( 'Search Kicker', 'lunara-film' ) ),
        array( 'setting' => 'lunara_search_no_query_title', 'default' => 'Search Lunara Film',                   'label' => __( 'Empty Search Title', 'lunara-film' ) ),
        array( 'setting' => 'lunara_header_search_placeholder', 'default' => 'Search reviews, films, Oscar history\u2026', 'label' => __( 'Header Search Placeholder', 'lunara-film' ) ),
    );

    foreach ( $search_controls as $sc ) {
        $wp_customize->add_setting( $sc['setting'], array(
            'default'           => $sc['default'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( $sc['setting'], array(
            'label'   => $sc['label'],
            'section' => 'lunara_search_options',
            'type'    => 'text',
        ) );
    }

    $wp_customize->add_setting( 'lunara_search_excerpt_words', array(
        'default'           => 22,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'lunara_search_excerpt_words', array(
        'label'       => __( 'Excerpt Word Count', 'lunara-film' ),
        'section'     => 'lunara_search_options',
        'type'        => 'range',
        'input_attrs' => array( 'min' => 10, 'max' => 50, 'step' => 1 ),
    ) );

    // Generic Archives
    $wp_customize->add_section( 'lunara_generic_archive_options', array(
        'title'       => __( 'Generic Archives', 'lunara-film' ),
        'panel'       => 'lunara_utility_panel',
        'priority'    => 30,
        'description' => __( 'Default kickers for category, tag, author, and date archives.', 'lunara-film' ),
    ) );

    $archive_kicker_controls = array(
        array( 'setting' => 'lunara_archive_category_kicker', 'default' => 'Category Archive', 'label' => __( 'Category Archive Kicker', 'lunara-film' ) ),
        array( 'setting' => 'lunara_archive_tag_kicker',      'default' => 'Tagged Signal',     'label' => __( 'Tag Archive Kicker', 'lunara-film' ) ),
        array( 'setting' => 'lunara_archive_author_kicker',   'default' => 'Byline Archive',    'label' => __( 'Author Archive Kicker', 'lunara-film' ) ),
        array( 'setting' => 'lunara_archive_date_kicker',     'default' => 'Calendar File',     'label' => __( 'Date Archive Kicker', 'lunara-film' ) ),
    );

    foreach ( $archive_kicker_controls as $akc ) {
        $wp_customize->add_setting( $akc['setting'], array(
            'default'           => $akc['default'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( $akc['setting'], array(
            'label'   => $akc['label'],
            'section' => 'lunara_generic_archive_options',
            'type'    => 'text',
        ) );
    }

    $wp_customize->add_setting( 'lunara_archive_review_empty_text', array(
        'default'           => 'No reviews have been filed yet. When new criticism is published, it will appear here automatically.',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'lunara_archive_review_empty_text', array(
        'label'   => __( 'Review Archive Empty State', 'lunara-film' ),
        'section' => 'lunara_generic_archive_options',
        'type'    => 'textarea',
    ) );

    // ── OSCARS PORTAL EXTRAS ────────────────────────────────────────────

    $oscars_button_controls = array(
        array( 'setting' => 'lunara_oscars_ceremony_btn',   'default' => 'Latest Ceremony',  'label' => __( 'Ceremony Button', 'lunara-film' ) ),
        array( 'setting' => 'lunara_oscars_ledger_btn',     'default' => 'Open Full Ledger', 'label' => __( 'Ledger Button', 'lunara-film' ) ),
        array( 'setting' => 'lunara_oscars_categories_btn', 'default' => 'Browse Categories','label' => __( 'Categories Button', 'lunara-film' ) ),
    );

    foreach ( $oscars_button_controls as $obc ) {
        $wp_customize->add_setting( $obc['setting'], array(
            'default'           => $obc['default'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( $obc['setting'], array(
            'label'   => $obc['label'],
            'section' => 'lunara_oscars_portal_options',
            'type'    => 'text',
        ) );
    }

    $oscars_visibility_controls = array(
        'lunara_oscars_show_portal_links'   => __( 'Show Explore Portal Links', 'lunara-film' ),
        'lunara_oscars_show_spotlights'     => __( 'Show Latest Ceremony Spotlights', 'lunara-film' ),
        'lunara_oscars_show_title_cards'    => __( 'Show Poster-Led Title Cards', 'lunara-film' ),
        'lunara_oscars_show_research'       => __( 'Show Research Layer', 'lunara-film' ),
        'lunara_oscars_show_latest_winners' => __( 'Show Latest Ceremony Winners', 'lunara-film' ),
        'lunara_oscars_show_deep_cuts'      => __( 'Show Oscar Deep Cuts', 'lunara-film' ),
    );

    foreach ( $oscars_visibility_controls as $setting => $label ) {
        $wp_customize->add_setting(
            $setting,
            array(
                'default'           => true,
                'sanitize_callback' => 'wp_validate_boolean',
                'transport'         => 'refresh',
            )
        );
        $wp_customize->add_control(
            $setting,
            array(
                'label'   => $label,
                'section' => 'lunara_oscars_portal_options',
                'type'    => 'checkbox',
            )
        );
    }

    $portal_card_defaults = array(
        1 => array(
            'kicker' => 'Ceremonies',
            'title'  => 'Move through the awards one ceremony at a time.',
            'copy'   => 'Jump into the latest winners, older races, and the shape of each year.',
            'url'    => '/oscars/ceremonies/',
        ),
        2 => array(
            'kicker' => 'Categories',
            'title'  => 'Track the history of every Oscar category.',
            'copy'   => 'Go from Best Picture to supporting races, crafts, and international wins.',
            'url'    => '/oscars/categories/',
        ),
        3 => array(
            'kicker' => 'Ledger',
            'title'  => 'Search the full ledger directly.',
            'copy'   => 'Use the full ledger when you want filters, tables, and the entire record at once.',
            'url'    => '/oscars/',
        ),
        4 => array(
            'kicker' => 'About',
            'title'  => 'See how Lunara structures the archive.',
            'copy'   => 'Read the editorial and data philosophy behind the Oscar ledger.',
            'url'    => '/oscars/about/',
        ),
    );

    foreach ( $portal_card_defaults as $slot => $defaults ) {
        $enabled_setting = 'lunara_oscars_portal_card_' . $slot . '_enabled';
        $wp_customize->add_setting(
            $enabled_setting,
            array(
                'default'           => true,
                'sanitize_callback' => 'wp_validate_boolean',
                'transport'         => 'refresh',
            )
        );
        $wp_customize->add_control(
            $enabled_setting,
            array(
                'label'       => sprintf( __( 'Show Portal Card %d', 'lunara-film' ), $slot ),
                'section'     => 'lunara_oscars_portal_options',
                'type'        => 'checkbox',
                'description' => sprintf( __( 'Controls visibility for portal card slot %d.', 'lunara-film' ), $slot ),
            )
        );

        foreach ( array(
            'kicker' => array(
                'label'    => sprintf( __( 'Portal Card %d Kicker', 'lunara-film' ), $slot ),
                'sanitize' => 'sanitize_text_field',
                'type'     => 'text',
            ),
            'title' => array(
                'label'    => sprintf( __( 'Portal Card %d Title', 'lunara-film' ), $slot ),
                'sanitize' => 'sanitize_text_field',
                'type'     => 'text',
            ),
            'copy' => array(
                'label'    => sprintf( __( 'Portal Card %d Copy', 'lunara-film' ), $slot ),
                'sanitize' => 'sanitize_textarea_field',
                'type'     => 'textarea',
            ),
            'url' => array(
                'label'    => sprintf( __( 'Portal Card %d URL', 'lunara-film' ), $slot ),
                'sanitize' => 'esc_url_raw',
                'type'     => 'url',
            ),
        ) as $field => $config ) {
            $setting = 'lunara_oscars_portal_card_' . $slot . '_' . $field;
            $wp_customize->add_setting(
                $setting,
                array(
                    'default'           => $defaults[ $field ],
                    'sanitize_callback' => $config['sanitize'],
                    'transport'         => 'refresh',
                )
            );
            $wp_customize->add_control(
                $setting,
                array(
                    'label'   => $config['label'],
                    'section' => 'lunara_oscars_portal_options',
                    'type'    => $config['type'],
                )
            );
        }
    }

    $wp_customize->add_setting(
        'lunara_oscars_rotating_winners_enabled',
        array(
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
            'transport'         => 'refresh',
        )
    );
    $wp_customize->add_control(
        'lunara_oscars_rotating_winners_enabled',
        array(
            'label'       => __( 'Show Rotating Ceremony Winners', 'lunara-film' ),
            'section'     => 'lunara_oscars_portal_options',
            'type'        => 'checkbox',
            'description' => __( 'Adds a bottom-of-page Oscars deep-dive carousel that rotates to a different ceremony each day.', 'lunara-film' ),
        )
    );

    $wp_customize->add_setting(
        'lunara_oscars_rotating_winners_heading',
        array(
            'default'           => 'Ceremony Winners in Rotation',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        )
    );
    $wp_customize->add_control(
        'lunara_oscars_rotating_winners_heading',
        array(
            'label'       => __( 'Rotating Winners Heading', 'lunara-film' ),
            'section'     => 'lunara_oscars_portal_options',
            'type'        => 'text',
            'description' => __( 'Headline above the daily rotating ceremony-winners carousel.', 'lunara-film' ),
        )
    );

    $wp_customize->add_setting(
        'lunara_oscars_rotating_winners_count',
        array(
            'default'           => 10,
            'sanitize_callback' => 'absint',
            'transport'         => 'refresh',
        )
    );
    $wp_customize->add_control(
        'lunara_oscars_rotating_winners_count',
        array(
            'label'       => __( 'Rotating Winners Card Count', 'lunara-film' ),
            'section'     => 'lunara_oscars_portal_options',
            'type'        => 'range',
            'description' => __( 'How many winner cards appear in the rotating ceremony carousel.', 'lunara-film' ),
            'input_attrs' => array(
                'min'  => 4,
                'max'  => 16,
                'step' => 1,
            ),
        )
    );

    $wp_customize->add_setting(
        'lunara_oscars_rotating_winners_autoplay',
        array(
            'default'           => 0,
            'sanitize_callback' => 'absint',
            'transport'         => 'refresh',
        )
    );
    $wp_customize->add_control(
        'lunara_oscars_rotating_winners_autoplay',
        array(
            'label'       => __( 'Rotating Winners Autoplay (ms)', 'lunara-film' ),
            'section'     => 'lunara_oscars_portal_options',
            'type'        => 'range',
            'description' => __( 'Set to 0 to keep the carousel manual. Higher values auto-scroll the cards on desktop.', 'lunara-film' ),
            'input_attrs' => array(
                'min'  => 0,
                'max'  => 12000,
                'step' => 500,
            ),
        )
    );
}
add_action( 'customize_register', 'lunara_customize_register' );

/**
 * Print runtime CSS for Lunara design controls.
 */
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
    $dispatch_lead_media_min_width  = max( 180, min( 360, absint( get_theme_mod( 'lunara_home_dispatch_lead_media_min_width', 180 ) ) ) );
    $dispatch_layout_split          = max( 36, min( 62, absint( get_theme_mod( 'lunara_home_dispatch_layout_split', 44 ) ) ) );
    $dispatch_lead_media_max_height       = max( 320, min( 760, absint( get_theme_mod( 'lunara_home_dispatch_lead_media_max_height', 360 ) ) ) );
    $dispatch_split_card_media_max_height = max( 96, min( 220, absint( get_theme_mod( 'lunara_home_dispatch_split_card_media_max_height', 140 ) ) ) );
    $dispatch_lead_media_ratio            = lunara_get_dispatch_ratio_value( get_theme_mod( 'lunara_home_dispatch_lead_media_ratio', 'cinematic' ) );
    $dispatch_rail_thumb_width            = max( 88, min( 220, absint( get_theme_mod( 'lunara_home_dispatch_rail_thumb_width', 92 ) ) ) );
    $dispatch_rail_stack_image_height     = max( 96, min( 220, absint( get_theme_mod( 'lunara_home_dispatch_rail_stack_image_height', 124 ) ) ) );
    $dispatch_mobile_lead_max_height = max( 160, min( 320, absint( get_theme_mod( 'lunara_home_dispatch_mobile_lead_max_height', 188 ) ) ) );
    $dispatch_mobile_rail_thumb_width = max( 60, min( 120, absint( get_theme_mod( 'lunara_home_dispatch_mobile_rail_thumb_width', 68 ) ) ) );
    $dispatch_rail_thumb_ratio      = lunara_get_dispatch_ratio_value( get_theme_mod( 'lunara_home_dispatch_rail_thumb_ratio', 'cinematic' ) );
    $mobile_header_pad = min( $header_side_pad, 20 );
    $mobile_home_pad   = min( $home_side_pad, 24 );
    $mobile_shell_pad  = min( $shell_pad, 24 );
    $section_order     = lunara_get_home_section_order_map();
    $review_section_order = function_exists( 'lunara_get_reviews_archive_section_order_map' )
        ? lunara_get_reviews_archive_section_order_map()
        : array();
    $journal_section_order = function_exists( 'lunara_get_journal_archive_section_order_map' )
        ? lunara_get_journal_archive_section_order_map()
        : array();
    $journal_live_section_order = function_exists( 'lunara_get_news_archive_live_section_order_map' )
        ? lunara_get_news_archive_live_section_order_map()
        : array();
    $journal_empty_section_order = function_exists( 'lunara_get_news_archive_empty_section_order_map' )
        ? lunara_get_news_archive_empty_section_order_map()
        : array();
    $oscars_section_order = function_exists( 'lunara_get_oscars_portal_section_order_map' )
        ? lunara_get_oscars_portal_section_order_map()
        : array();
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
    $css .= '--lunara-section-gap:' . $home_gap . 'px;';
    $css .= '--lunara-home-dispatch-lead-media-min:' . $dispatch_lead_media_min_width . 'px;';
    $css .= '--lunara-home-dispatch-layout-split:' . $dispatch_layout_split . '%;';
    $css .= '--lunara-home-dispatch-lead-media-max:' . $dispatch_lead_media_max_height . 'px;';
    $css .= '--lunara-home-dispatch-split-card-media-max:' . $dispatch_split_card_media_max_height . 'px;';
    $css .= '--lunara-home-dispatch-lead-media-ratio:' . $dispatch_lead_media_ratio . ';';
    $css .= '--lunara-home-dispatch-rail-thumb-width:' . $dispatch_rail_thumb_width . 'px;';
    $css .= '--lunara-home-dispatch-rail-stack-max:' . $dispatch_rail_stack_image_height . 'px;';
    $css .= '--lunara-home-dispatch-mobile-lead-max:' . $dispatch_mobile_lead_max_height . 'px;';
    $css .= '--lunara-home-dispatch-mobile-rail-thumb-width:' . $dispatch_mobile_rail_thumb_width . 'px;';
    $css .= '--lunara-home-dispatch-rail-thumb-ratio:' . $dispatch_rail_thumb_ratio . ';';
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

    if ( '' !== $body_font ) {
        $css .= 'body,.lunara-front-page,.lunara-archive-page{font-family:' . $body_font . ';}';
    }

    if ( '' !== $heading_font ) {
        $css .= '.lunara-home-hero-title,.lunara-home-section-title,.lunara-section-title,.lunara-home-pulse-title,.lunara-home-pulse-feature-heading,.lunara-poster-card-title,.lunara-dispatch-lead-title,.lunara-dispatch-rail-title,.lunara-home-winner-title,.lunara-home-pulse-note-title,.lunara-review-grid-title,.lunara-oscar-spotlight-text-panel h3{font-family:' . $heading_font . ';}';
    }

    /* Header */
    $css .= '.lunara-header .lunara-header-row{background:' . $header_bg . ';border-bottom:1px solid ' . $header_border . ';}';
    $css .= '.lunara-header .lunara-header-row .lunara-container{max-width:var(--lunara-header-max);padding-left:var(--lunara-header-side-pad);padding-right:var(--lunara-header-side-pad);}';
    $css .= '.lunara-header .site-title,.lunara-header .site-title a{font-size:var(--lunara-header-title-size);}';
    $css .= '.lunara-nav .lunara-nav-list,.lunara-nav .menu{column-gap:var(--lunara-header-nav-gap);}';
    $css .= '.lunara-nav .lunara-nav-list > li > a,.lunara-nav .menu > li > a,.lunara-header button{color:' . $header_link . ';font-size:var(--lunara-header-nav-size);letter-spacing:var(--lunara-header-nav-track);text-transform:uppercase;}';
    $css .= '.lunara-nav .lunara-nav-list > li > a:hover,.lunara-nav .menu > li > a:hover,.lunara-header button:hover{color:' . $header_hover . ';}';
    $css .= '.ct-header [data-row="middle"]{background:radial-gradient(circle at top center,rgba(201,169,97,.12),transparent 42%),linear-gradient(180deg,rgba(10,22,36,.94),rgba(4,11,20,.98));border-bottom:1px solid rgba(201,169,97,.18);box-shadow:0 14px 34px rgba(0,0,0,.18);backdrop-filter:blur(18px);}';
    $css .= '.ct-header .site-branding,.ct-header .site-logo-container{background:transparent!important;border:0!important;box-shadow:none!important;}';
    $css .= '.ct-header .site-logo-container img{filter:drop-shadow(0 10px 20px rgba(0,0,0,.2));}';
    $css .= '.ct-header .ct-menu-link,.ct-header .ct-header-trigger,.ct-header .ct-toggle,.ct-header [data-id="search"] .ct-label,.ct-header [data-id="search"] .ct-icon{color:' . $accent_soft . '!important;}';
    $css .= '.ct-header .ct-menu-link:hover,.ct-header .ct-header-trigger:hover,.ct-header .ct-toggle:hover,.ct-header [data-id="search"]:hover .ct-label,.ct-header [data-id="search"]:hover .ct-icon{color:' . $text_color . '!important;}';
    $css .= '.lunara-footer,footer.site-footer{background:linear-gradient(180deg,' . lunara_hex_to_rgba( $bg_secondary, 0.92 ) . ',' . lunara_hex_to_rgba( $bg_primary, 0.98 ) . ');border-top:1px solid ' . $border_alpha . ';}';
    $css .= '.lunara-footer .lunara-container,footer.site-footer .lunara-container{max-width:var(--lunara-shell-max);padding-left:var(--lunara-shell-pad);padding-right:var(--lunara-shell-pad);}';
    $css .= '.lunara-footer a,footer.site-footer a{color:' . $text_color . ';}';
    $css .= '.lunara-footer a:hover,footer.site-footer a:hover{color:' . $accent_soft . ';}';
    $css .= '.lunara-front-page,.lunara-archive-page,.lunara-editorial-single-page,.lunara-oscars-portal{max-width:var(--lunara-home-max);padding-left:var(--lunara-home-pad);padding-right:var(--lunara-home-pad);}';
    $css .= '.lunara-front-page,.lunara-editorial-single-page,.lunara-oscars-portal{gap:var(--lunara-home-gap);}';
    $css .= '.lunara-home-hero.is-minimal,.lunara-archive-hero,.lunara-journal-single-hero,.lunara-oscars-portal-hero{padding-top:var(--lunara-home-hero-top);}';

    foreach ( lunara_get_home_section_slugs() as $slug ) {
        $order = isset( $section_order[ $slug ] ) ? intval( $section_order[ $slug ] ) : 99;
        $css  .= '.lunara-front-page > .lunara-home-slot-' . $slug . '{order:' . $order . ';}';
    }

    foreach ( lunara_get_registry_slugs( lunara_get_reviews_archive_section_registry() ) as $slug ) {
        $order = isset( $review_section_order[ $slug ] ) ? intval( $review_section_order[ $slug ] ) : 99;
        $css  .= '.lunara-review-archive-page > .lunara-review-archive-slot-' . $slug . '{order:' . $order . ';}';
    }

    foreach ( lunara_get_registry_slugs( lunara_get_journal_archive_section_registry() ) as $slug ) {
        $order = isset( $journal_section_order[ $slug ] ) ? intval( $journal_section_order[ $slug ] ) : 99;
        $css  .= '.lunara-journal-archive-page > .lunara-journal-archive-slot-' . $slug . '{order:' . $order . ';}';
    }

    foreach ( lunara_get_registry_slugs( lunara_get_news_archive_live_section_registry() ) as $slug ) {
        $order = isset( $journal_live_section_order[ $slug ] ) ? intval( $journal_live_section_order[ $slug ] ) : 99;
        $css  .= '.lunara-editorial-archive-page.lunara-editorial-archive-has-posts > .lunara-editorial-archive-slot-' . $slug . '{order:' . $order . ';}';
        $css  .= '.lunara-news-archive-page.lunara-news-archive-has-posts > .lunara-news-archive-slot-' . $slug . '{order:' . $order . ';}';
    }

    foreach ( lunara_get_registry_slugs( lunara_get_news_archive_empty_section_registry() ) as $slug ) {
        $order = isset( $journal_empty_section_order[ $slug ] ) ? intval( $journal_empty_section_order[ $slug ] ) : 99;
        $css  .= '.lunara-editorial-archive-page.lunara-editorial-archive-is-empty > .lunara-editorial-archive-slot-' . $slug . '{order:' . $order . ';}';
        $css  .= '.lunara-news-archive-page.lunara-news-archive-is-empty > .lunara-news-archive-slot-' . $slug . '{order:' . $order . ';}';
    }

    foreach ( lunara_get_registry_slugs( lunara_get_oscars_portal_section_registry() ) as $slug ) {
        $order = isset( $oscars_section_order[ $slug ] ) ? intval( $oscars_section_order[ $slug ] ) : 99;
        $css  .= '.lunara-oscars-portal > .lunara-oscars-portal-slot-' . $slug . '{order:' . $order . ';}';
    }

    if ( ! get_theme_mod( 'lunara_show_logo', true ) ) {
        $css .= '.lunara-header .site-logo-container{display:none !important;}';
    }

    if ( ! get_theme_mod( 'lunara_show_site_title', true ) ) {
        $css .= '.lunara-header .site-title{display:none !important;}';
    }

    /* Mobile responsive rules now handled entirely by style.css breakpoints (900px / 640px) */

    /* ── Sticky Header ── */
    if ( get_theme_mod( 'lunara_sticky_header', false ) ) {
        $css .= '.lunara-header{position:sticky;top:0;z-index:1000;transition:transform 0.3s ease,box-shadow 0.3s ease;}';
        $css .= '.lunara-header.is-scrolled{box-shadow:0 2px 20px rgba(0,0,0,0.4);}';
    }

    /* ── Transparent Header on Homepage ── */
    if ( get_theme_mod( 'lunara_transparent_header', false ) ) {
        $css .= '.home .lunara-header .lunara-header-row{background:transparent;border-bottom-color:transparent;}';
        $css .= '.home .lunara-header.is-scrolled .lunara-header-row{background:' . $header_bg . ';border-bottom:1px solid ' . $header_border . ';}';
    }

    /* ── Dropdown Menus ── */
    $dropdown_bg         = sanitize_hex_color( get_theme_mod( 'lunara_dropdown_bg', '#0f1d2e' ) ) ?: '#0f1d2e';
    $dropdown_hover_bg   = sanitize_hex_color( get_theme_mod( 'lunara_dropdown_hover_bg', '#0f1d2e' ) ) ?: '#0f1d2e';
    $dropdown_text       = sanitize_hex_color( get_theme_mod( 'lunara_dropdown_text_color', '#FAFBFC' ) ) ?: '#FAFBFC';
    $dropdown_hover_text = sanitize_hex_color( get_theme_mod( 'lunara_dropdown_hover_text', '#e0c481' ) ) ?: '#e0c481';
    $dropdown_border     = sanitize_text_field( get_theme_mod( 'lunara_dropdown_border_color', 'rgba(201,169,97,0.15)' ) );
    $dropdown_width      = absint( get_theme_mod( 'lunara_dropdown_width', 220 ) );
    $dropdown_pad        = absint( get_theme_mod( 'lunara_dropdown_padding', 10 ) );

    $css .= '.lunara-nav .sub-menu{background:' . $dropdown_bg . ';border:1px solid ' . $dropdown_border . ';border-radius:8px;min-width:' . $dropdown_width . 'px;padding:' . $dropdown_pad . 'px 0;box-shadow:0 8px 32px rgba(0,0,0,0.4);}';
    $css .= '.lunara-nav .sub-menu li a{color:' . $dropdown_text . ';padding:8px 18px;display:block;font-size:0.9em;}';
    $css .= '.lunara-nav .sub-menu li a:hover{color:' . $dropdown_hover_text . ';background:' . $dropdown_hover_bg . ';}';

    /* ── Mobile Menu ── */
    $mobile_width      = absint( get_theme_mod( 'lunara_mobile_panel_width', 320 ) );
    $mobile_bg         = sanitize_hex_color( get_theme_mod( 'lunara_mobile_panel_bg', '#0a1520' ) ) ?: '#0a1520';
    $mobile_dir        = get_theme_mod( 'lunara_mobile_panel_direction', 'right' );
    $mobile_link_size  = absint( get_theme_mod( 'lunara_mobile_link_size', 16 ) );
    $mobile_link_color = sanitize_hex_color( get_theme_mod( 'lunara_mobile_link_color', '#FAFBFC' ) ) ?: '#FAFBFC';
    $mobile_link_hover = sanitize_hex_color( get_theme_mod( 'lunara_mobile_link_hover', '#e0c481' ) ) ?: '#e0c481';

    $css .= '.lunara-mobile-panel .lunara-panel-inner{width:' . $mobile_width . 'px;background:' . $mobile_bg . ';}';
    if ( 'left' === $mobile_dir ) {
        $css .= '.lunara-mobile-panel .lunara-panel-inner{left:0;right:auto;}';
    }
    $css .= '.lunara-mobile-panel .lunara-mobile-menu li a{font-size:' . $mobile_link_size . 'px;color:' . $mobile_link_color . ';}';
    $css .= '.lunara-mobile-panel .lunara-mobile-menu li a:hover{color:' . $mobile_link_hover . ';}';

    /* ── Single Post Layout ── */
    $single_content_max = absint( get_theme_mod( 'lunara_single_content_max_width', 780 ) );
    $css .= ':root{--lunara-single-content-max:' . $single_content_max . 'px;}';

    /* ── Review Debrief Sizing ── */
    $review_debrief_width  = max( 980, min( 1600, absint( get_theme_mod( 'lunara_review_debrief_width', 1180 ) ) ) );
    $review_poster_max     = max( 260, min( 520, absint( get_theme_mod( 'lunara_review_debrief_poster_max', 400 ) ) ) );
    $review_signature_shift = max( 0, min( 160, absint( get_theme_mod( 'lunara_review_debrief_signature_offset', 56 ) ) ) );
    $css .= ':root{--lunara-review-debrief-width:' . $review_debrief_width . 'px;';
    $css .= '--lunara-review-debrief-poster-max:' . $review_poster_max . 'px;';
    $css .= '--lunara-review-debrief-signature-offset:' . $review_signature_shift . 'px;}';

    if ( '' === $css ) {
        return;
    }

    echo '<style id="lunara-runtime-customizer-css">' . $css . '</style>' . "\n";
}
add_action( 'wp_head', 'lunara_output_runtime_customizer_css', 99 );
