<?php
/**
 * Gutenberg block bridge for Lunara dynamic editorial modules.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lunara_register_block_category( $categories ) {
    array_unshift(
        $categories,
        array(
            'slug'  => 'lunara',
            'title' => __( 'Lunara', 'lunara-film' ),
            'icon'  => 'format-gallery',
        )
    );

    return $categories;
}
add_filter( 'block_categories_all', 'lunara_register_block_category' );

function lunara_register_dynamic_blocks() {
    $asset = lunara_resolve_theme_asset( 'assets/js/lunara-blocks.js' );

    if ( ! empty( $asset['path'] ) ) {
        wp_register_script(
            'lunara-blocks',
            $asset['uri'],
            array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render' ),
            lunara_theme_asset_version( $asset['path'] ),
            true
        );
    }

    $common = array(
        'category'      => 'lunara',
        'editor_script' => 'lunara-blocks',
        'supports'      => array(
            'align'    => array( 'wide', 'full' ),
            'anchor'   => true,
            'html'     => false,
            // Legacy bridge blocks: kept registered so any existing content
            // still renders, but hidden from the inserter — the 2026-07 content
            // census found ZERO posts/pages using them, and they were flooding
            // the editor palette alongside the newer surfaces.
            'inserter' => false,
        ),
    );

    register_block_type( 'lunara/home', array_merge( $common, array( 'title' => __( 'Lunara Home', 'lunara-film' ), 'render_callback' => 'lunara_render_home_block' ) ) );

    register_block_type(
        'lunara/reviews',
        array_merge(
            $common,
            array(
                'title'           => __( 'Lunara Reviews Grid', 'lunara-film' ),
                'attributes'      => array( 'count' => array( 'type' => 'number', 'default' => 6 ) ),
                'render_callback' => 'lunara_render_reviews_block',
            )
        )
    );

    register_block_type(
        'lunara/posts',
        array_merge(
            $common,
            array(
                'title'           => __( 'Lunara Posts Grid', 'lunara-film' ),
                'attributes'      => array(
                    'category' => array( 'type' => 'string', 'default' => '' ),
                    'count'    => array( 'type' => 'number', 'default' => 6 ),
                ),
                'render_callback' => 'lunara_render_posts_block',
            )
        )
    );

    register_block_type(
        'lunara/carousel',
        array_merge(
            $common,
            array(
                'title'           => __( 'Lunara Carousel', 'lunara-film' ),
                'attributes'      => array(
                    'set'   => array( 'type' => 'string', 'default' => 'homepage' ),
                    'limit' => array( 'type' => 'number', 'default' => -1 ),
                ),
                'render_callback' => 'lunara_render_carousel_block',
            )
        )
    );

    register_block_type(
        'lunara/still',
        array_merge(
            $common,
            array(
                'title'           => __( 'Lunara Still', 'lunara-film' ),
                'attributes'      => array(
                    'url'     => array( 'type' => 'string', 'default' => '' ),
                    'alt'     => array( 'type' => 'string', 'default' => '' ),
                    'caption' => array( 'type' => 'string', 'default' => '' ),
                    'kicker'  => array( 'type' => 'string', 'default' => '' ),
                    'style'   => array( 'type' => 'string', 'default' => 'default' ),
                    'loading' => array( 'type' => 'string', 'default' => 'lazy' ),
                ),
                'render_callback' => 'lunara_render_still_block',
            )
        )
    );

    register_block_type( 'lunara/debrief', array_merge( $common, array( 'title' => __( 'Lunara Debrief', 'lunara-film' ), 'render_callback' => 'lunara_render_debrief_block' ) ) );
    register_block_type( 'lunara/pair-it-with', array_merge( $common, array( 'title' => __( 'Lunara Pair It With', 'lunara-film' ), 'render_callback' => 'lunara_render_pair_it_with_block' ) ) );

    register_block_type(
        'lunara/where-to-watch',
        array_merge(
            $common,
            array(
                'title'           => __( 'Lunara Where To Watch', 'lunara-film' ),
                'attributes'      => array(
                    'imdb'   => array( 'type' => 'string', 'default' => '' ),
                    'region' => array( 'type' => 'string', 'default' => 'US' ),
                ),
                'render_callback' => 'lunara_render_where_to_watch_block',
            )
        )
    );
}
add_action( 'init', 'lunara_register_dynamic_blocks' );

/**
 * Editor-only links and truthful ownership notes for the six homepage blocks.
 *
 * @return array<string,array<string,mixed>>
 */
function lunara_homepage_editor_section_config() {
    $map      = function_exists( 'lunara_home_section_block_map' ) ? lunara_home_section_block_map() : array();
    $registry = function_exists( 'lunara_get_home_section_registry' ) ? lunara_get_home_section_registry() : array();
    $can_edit = current_user_can( 'edit_theme_options' );
    $sections = array();

    foreach ( $map as $slug => $block_name ) {
        $is_hero_fixed = 'hero' === $slug
            && function_exists( 'lunara_home_cinematic_front_door_is_enabled' )
            && lunara_home_cinematic_front_door_is_enabled();
        $edit_surface  = 'pairing-desk' === $slug ? 'lunara-method' : 'homepage-structure';
        $edit_url      = $can_edit && function_exists( 'lunara_site_studio_admin_url' )
            ? lunara_site_studio_admin_url( $edit_surface )
            : '';

        if ( $can_edit && $is_hero_fixed && function_exists( 'lunara_control_desk_admin_url' ) ) {
            $edit_url = lunara_control_desk_admin_url( array( 'tab' => 'theme-studio' ) ) . '#lunara-theme-studio-hero-command';
        }

        if ( $is_hero_fixed ) {
            $edit_label = __( 'Edit in Hero Command', 'lunara-film' );
        } elseif ( 'pairing-desk' === $slug ) {
            $edit_label = __( 'Edit Lunara Method', 'lunara-film' );
        } else {
            $edit_label = __( 'Open Homepage Structure', 'lunara-film' );
        }

        $sections[ $block_name ] = array(
            'slug'        => $slug,
            'label'       => isset( $registry[ $slug ]['label'] ) ? (string) $registry[ $slug ]['label'] : $slug,
            'description' => isset( $registry[ $slug ]['description'] ) ? (string) $registry[ $slug ]['description'] : '',
            'editUrl'     => esc_url_raw( $edit_url ),
            'editLabel'   => $edit_label,
            'viewUrl'     => esc_url_raw( 'pairing-desk' === $slug ? home_url( '/#pairing-desk' ) : home_url( '/' ) ),
            'fixed'       => $is_hero_fixed,
            'status'      => $is_hero_fixed
                ? __( 'The public front-door hero is currently owned by Hero Command. This block remains stored, but its presence does not hide that live hero.', 'lunara-film' )
                : __( 'Public output renders only when WordPress displays the page; this compact card makes no content query.', 'lunara-film' ),
        );
    }

    return $sections;
}

/**
 * Attach compact homepage-card configuration only inside the block editor.
 *
 * @return void
 */
function lunara_enqueue_homepage_editor_card_assets() {
    if ( ! wp_script_is( 'lunara-blocks', 'registered' ) ) {
        return;
    }

    wp_localize_script(
        'lunara-blocks',
        'LunaraHomepageEditorConfig',
        array(
            'siteStudioUrl' => current_user_can( 'edit_theme_options' ) && function_exists( 'lunara_site_studio_admin_url' )
                ? esc_url_raw( lunara_site_studio_admin_url( 'lunara-method' ) )
                : '',
            'sections'      => lunara_homepage_editor_section_config(),
        )
    );
}
add_action( 'enqueue_block_editor_assets', 'lunara_enqueue_homepage_editor_card_assets', 20 );

/**
 * Load compact homepage-card styles through WordPress' iframe-aware path.
 *
 * enqueue_block_assets also fires for public rendering, so the explicit admin
 * guard is required to keep this stylesheet out of anonymous responses.
 *
 * @return void
 */
function lunara_enqueue_homepage_editor_card_style() {
    if ( ! is_admin() || ! function_exists( 'lunara_resolve_theme_asset' ) ) {
        return;
    }

    $style = lunara_resolve_theme_asset( 'assets/css/lunara-homepage-editor.css' );
    if ( ! empty( $style['path'] ) && ! empty( $style['uri'] ) ) {
        wp_enqueue_style(
            'lunara-homepage-editor',
            $style['uri'],
            array(),
            function_exists( 'lunara_theme_asset_version' ) ? lunara_theme_asset_version( $style['path'] ) : null
        );
    }
}
add_action( 'enqueue_block_assets', 'lunara_enqueue_homepage_editor_card_style', 20 );

function lunara_render_home_block() {
    return function_exists( 'lunara_home_shortcode' ) ? lunara_home_shortcode() : '';
}

function lunara_render_reviews_block( $attributes ) {
    return function_exists( 'lunara_reviews_shortcode' ) ? lunara_reviews_shortcode( array( 'count' => isset( $attributes['count'] ) ? (int) $attributes['count'] : 6 ) ) : '';
}

function lunara_render_posts_block( $attributes ) {
    return function_exists( 'lunara_posts_shortcode' ) ? lunara_posts_shortcode(
        array(
            'category' => isset( $attributes['category'] ) ? sanitize_title( $attributes['category'] ) : '',
            'count'    => isset( $attributes['count'] ) ? (int) $attributes['count'] : 6,
        )
    ) : '';
}

function lunara_render_carousel_block( $attributes ) {
    return function_exists( 'lunara_carousel_shortcode' ) ? lunara_carousel_shortcode(
        array(
            'set'   => isset( $attributes['set'] ) ? sanitize_title( $attributes['set'] ) : 'homepage',
            'limit' => isset( $attributes['limit'] ) ? (int) $attributes['limit'] : -1,
        )
    ) : '';
}

function lunara_render_still_block( $attributes ) {
    return function_exists( 'lunara_still_shortcode' ) ? lunara_still_shortcode(
        array(
            'url'     => isset( $attributes['url'] ) ? esc_url_raw( $attributes['url'] ) : '',
            'alt'     => isset( $attributes['alt'] ) ? sanitize_text_field( $attributes['alt'] ) : '',
            'caption' => isset( $attributes['caption'] ) ? sanitize_text_field( $attributes['caption'] ) : '',
            'kicker'  => isset( $attributes['kicker'] ) ? sanitize_text_field( $attributes['kicker'] ) : '',
            'style'   => isset( $attributes['style'] ) ? sanitize_key( $attributes['style'] ) : 'default',
            'loading' => isset( $attributes['loading'] ) ? sanitize_key( $attributes['loading'] ) : 'lazy',
        )
    ) : '';
}

function lunara_render_debrief_block() {
    if ( function_exists( 'lunara_debrief_public_renderer_enabled' ) && lunara_debrief_public_renderer_enabled() ) {
        return function_exists( 'lunara_render_review_debrief' ) ? lunara_render_review_debrief( get_the_ID() ) : '';
    }

    return function_exists( 'lunara_debrief_shortcode' ) ? lunara_debrief_shortcode( array() ) : '';
}

function lunara_render_pair_it_with_block() {
    if ( function_exists( 'lunara_debrief_public_renderer_enabled' ) && lunara_debrief_public_renderer_enabled() ) {
        $parts = function_exists( 'lunara_get_review_debrief_render_parts' )
            ? lunara_get_review_debrief_render_parts( get_the_ID() )
            : array();
        return ! empty( $parts['pairings_html'] ) ? (string) $parts['pairings_html'] : '';
    }

    return function_exists( 'lunara_pair_it_with_shortcode' ) ? lunara_pair_it_with_shortcode( array() ) : '';
}

function lunara_render_where_to_watch_block( $attributes ) {
    return function_exists( 'lunara_where_to_watch_shortcode' ) ? lunara_where_to_watch_shortcode(
        array(
            'imdb'   => isset( $attributes['imdb'] ) ? sanitize_text_field( $attributes['imdb'] ) : '',
            'region' => isset( $attributes['region'] ) ? sanitize_text_field( $attributes['region'] ) : 'US',
        )
    ) : '';
}
