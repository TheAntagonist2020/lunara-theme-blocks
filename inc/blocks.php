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
            'align'  => array( 'wide', 'full' ),
            'anchor' => true,
            'html'   => false,
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
    return function_exists( 'lunara_debrief_shortcode' ) ? lunara_debrief_shortcode( array() ) : '';
}

function lunara_render_pair_it_with_block() {
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
