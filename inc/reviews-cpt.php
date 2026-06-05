<?php
/**
 * Reviews Custom Post Type Registration
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register Reviews Custom Post Type
 */
if ( ! defined( 'LUNARA_CORE_VERSION' ) ) {
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

    /**
     * Add a visible last-updated column to the review admin list.
     */
    function lunara_review_admin_columns( $columns ) {
        $updated_columns = array();

        foreach ( $columns as $key => $label ) {
            $updated_columns[ $key ] = $label;

            if ( 'date' === $key ) {
                $updated_columns['lunara_last_updated'] = __( 'Last Updated', 'lunara-film' );
            }
        }

        if ( ! isset( $updated_columns['lunara_last_updated'] ) ) {
            $updated_columns['lunara_last_updated'] = __( 'Last Updated', 'lunara-film' );
        }

        return $updated_columns;
    }
    add_filter( 'manage_review_posts_columns', 'lunara_review_admin_columns' );

    function lunara_render_review_admin_column( $column, $post_id ) {
        if ( 'lunara_last_updated' !== $column ) {
            return;
        }

        $modified = get_the_modified_date( 'M j, Y', $post_id );
        $time     = get_the_modified_date( 'g:i a', $post_id );

        if ( ! $modified ) {
            echo '&mdash;';
            return;
        }

        echo esc_html( $modified );

        if ( $time ) {
            echo '<br><small>' . esc_html( $time ) . '</small>';
        }
    }
    add_action( 'manage_review_posts_custom_column', 'lunara_render_review_admin_column', 10, 2 );

    function lunara_review_sortable_admin_columns( $columns ) {
        $columns['lunara_last_updated'] = 'modified';
        return $columns;
    }
    add_filter( 'manage_edit-review_sortable_columns', 'lunara_review_sortable_admin_columns' );
}
