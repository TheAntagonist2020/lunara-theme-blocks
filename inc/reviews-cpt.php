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

/**
 * Reviews Archive lead pin.
 *
 * These helpers deliberately live outside the Core-registration fallback: the
 * Review post type may be owned by Lunara Core, but the theme owns how its
 * archive lead is curated. `_lunara_review_pinned` remains the sole storage
 * contract for the editor meta box and Reviews Archive Studio.
 */
if ( ! function_exists( 'lunara_add_review_pin_meta_box' ) ) {
    function lunara_add_review_pin_meta_box() {
        add_meta_box(
            'lunara_review_pin_meta',
            __( 'Reviews Page Lead', 'lunara-film' ),
            'lunara_review_pin_meta_callback',
            'review',
            'side',
            'high'
        );
    }
}
add_action( 'add_meta_boxes', 'lunara_add_review_pin_meta_box' );

if ( ! function_exists( 'lunara_review_pin_meta_callback' ) ) {
    function lunara_review_pin_meta_callback( $post ) {
        wp_nonce_field( 'lunara_review_pin_nonce', 'lunara_review_pin_nonce' );
        $pinned = (bool) get_post_meta( $post->ID, '_lunara_review_pinned', true );
        ?>
        <p>
            <label>
                <input type="checkbox" name="lunara_review_pinned" value="1" <?php checked( $pinned ); ?> />
                <strong><?php esc_html_e( 'Pin as the Reviews archive lead', 'lunara-film' ); ?></strong>
            </label>
        </p>
        <p class="description"><?php esc_html_e( 'One published Review can lead the default, unfiltered first page. Pinning this Review releases the previous lead automatically.', 'lunara-film' ); ?></p>
        <?php
    }
}

if ( ! function_exists( 'lunara_set_pinned_review_id' ) ) {
    /**
     * Replace the canonical Reviews lead, or restore automatic selection.
     *
     * @param int $post_id Published Review ID, or zero for automatic.
     * @return int Stored lead ID, or zero when automatic.
     */
    function lunara_set_pinned_review_id( $post_id = 0 ) {
        $post_id = absint( $post_id );

        // A nonzero request is a replacement, not an implicit clear. Validate
        // it before touching the current lead so saving a draft/private Review
        // can never silently release the published archive pin.
        if ( $post_id > 0 && ( 'review' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) ) {
            return 0;
        }

        $post_statuses = array_values( get_post_stati( array(), 'names' ) );
        $pinned_ids    = get_posts(
            array(
                'post_type'              => 'review',
                'post_status'            => $post_statuses,
                'posts_per_page'         => -1,
                'fields'                 => 'ids',
                'meta_key'               => '_lunara_review_pinned',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            )
        );

        foreach ( array_map( 'absint', is_array( $pinned_ids ) ? $pinned_ids : array() ) as $pinned_id ) {
            if ( $pinned_id > 0 ) {
                delete_post_meta( $pinned_id, '_lunara_review_pinned' );
            }
        }

        if ( $post_id <= 0 ) {
            return 0;
        }

        update_post_meta( $post_id, '_lunara_review_pinned', time() );
        return $post_id;
    }
}

if ( ! function_exists( 'lunara_save_review_pin_meta' ) ) {
    function lunara_save_review_pin_meta( $post_id ) {
        if ( ! isset( $_POST['lunara_review_pin_nonce'] ) ) {
            return;
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lunara_review_pin_nonce'] ) ), 'lunara_review_pin_nonce' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( ! empty( $_POST['lunara_review_pinned'] ) ) {
            lunara_set_pinned_review_id( $post_id );
        } elseif ( get_post_meta( $post_id, '_lunara_review_pinned', true ) ) {
            delete_post_meta( $post_id, '_lunara_review_pinned' );
        }
    }
}
add_action( 'save_post_review', 'lunara_save_review_pin_meta' );

if ( ! function_exists( 'lunara_get_pinned_review_id' ) ) {
    function lunara_get_pinned_review_id() {
        $ids = get_posts(
            array(
                'post_type'              => 'review',
                'post_status'            => 'publish',
                'posts_per_page'         => 1,
                'fields'                 => 'ids',
                'meta_key'               => '_lunara_review_pinned',
                'orderby'                => 'meta_value_num',
                'order'                  => 'DESC',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            )
        );

        return ! empty( $ids ) ? absint( $ids[0] ) : 0;
    }
}
