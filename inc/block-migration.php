<?php
/**
 * Admin tool to convert legacy Lunara shortcodes into real block markup.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lunara_register_block_migration_page() {
    add_management_page(
        __( 'Lunara Block Migration', 'lunara-film' ),
        __( 'Lunara Block Migration', 'lunara-film' ),
        'edit_pages',
        'lunara-block-migration',
        'lunara_render_block_migration_page'
    );
}
add_action( 'admin_menu', 'lunara_register_block_migration_page' );

function lunara_render_block_migration_page() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( esc_html__( 'You do not have permission to migrate page content.', 'lunara-film' ) );
    }

    $result = null;

    if ( isset( $_POST['lunara_block_migration_action'] ) ) {
        check_admin_referer( 'lunara_block_migration' );

        if ( 'convert' === sanitize_key( wp_unslash( $_POST['lunara_block_migration_action'] ) ) ) {
            $result = lunara_convert_shortcode_posts_to_blocks();
        }
    }

    $candidates = lunara_find_shortcode_migration_candidates();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Lunara Block Migration', 'lunara-film' ); ?></h1>
        <p><?php esc_html_e( 'Convert legacy Lunara shortcodes stored in content into real Gutenberg dynamic blocks. Old shortcode rendering remains available as a fallback.', 'lunara-film' ); ?></p>

        <?php if ( is_array( $result ) ) : ?>
            <div class="notice notice-success">
                <p>
                    <?php
                    printf(
                        esc_html__( 'Converted %1$d item(s). Skipped %2$d item(s).', 'lunara-film' ),
                        (int) $result['converted'],
                        (int) $result['skipped']
                    );
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <h2><?php esc_html_e( 'Detected Content', 'lunara-film' ); ?></h2>
        <?php if ( empty( $candidates ) ) : ?>
            <p><?php esc_html_e( 'No supported Lunara shortcodes were found in pages, posts, reviews, or journal entries.', 'lunara-film' ); ?></p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Title', 'lunara-film' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'lunara-film' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'lunara-film' ); ?></th>
                        <th><?php esc_html_e( 'Shortcodes', 'lunara-film' ); ?></th>
                        <th><?php esc_html_e( 'Edit', 'lunara-film' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $candidates as $candidate ) : ?>
                        <?php
                        $type_object = get_post_type_object( $candidate->post_type );
                        $type_label  = $type_object && isset( $type_object->labels->singular_name ) ? $type_object->labels->singular_name : $candidate->post_type;
                        ?>
                        <tr>
                            <td><?php echo esc_html( get_the_title( $candidate->ID ) ); ?></td>
                            <td><?php echo esc_html( $type_label ); ?></td>
                            <td><?php echo esc_html( $candidate->post_status ); ?></td>
                            <td><code><?php echo esc_html( implode( ', ', lunara_detect_supported_shortcodes( $candidate->post_content ) ) ); ?></code></td>
                            <td><a href="<?php echo esc_url( get_edit_post_link( $candidate->ID ) ); ?>"><?php esc_html_e( 'Open editor', 'lunara-film' ); ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <form method="post" style="margin-top: 18px;">
                <?php wp_nonce_field( 'lunara_block_migration' ); ?>
                <input type="hidden" name="lunara_block_migration_action" value="convert">
                <?php submit_button( __( 'Convert Supported Lunara Shortcodes to Blocks', 'lunara-film' ), 'primary' ); ?>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

function lunara_find_shortcode_migration_candidates() {
    $query = new WP_Query(
        array(
            'post_type'      => array( 'page', 'post', 'review', 'journal' ),
            'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
            'posts_per_page' => 500,
            'no_found_rows'  => true,
        )
    );

    $posts = array();

    foreach ( $query->posts as $post ) {
        if ( lunara_detect_supported_shortcodes( $post->post_content ) ) {
            $posts[] = $post;
        }
    }

    return $posts;
}

function lunara_detect_supported_shortcodes( $content ) {
    $supported = array(
        'lunara_home',
        'lunara_reviews',
        'lunara_posts',
        'lunara_carousel',
        'lunara_still',
        'lunara_debrief',
        'lunara_pair_it_with',
        'lunara_where_to_watch',
    );

    $found = array();

    foreach ( $supported as $tag ) {
        if ( has_shortcode( $content, $tag ) ) {
            $found[] = $tag;
        }
    }

    return $found;
}

function lunara_convert_shortcode_posts_to_blocks() {
    $converted = 0;
    $skipped   = 0;

    foreach ( lunara_find_shortcode_migration_candidates() as $post ) {
        $new_content = lunara_convert_shortcodes_to_blocks( $post->post_content );

        if ( $new_content === $post->post_content ) {
            $skipped++;
            continue;
        }

        wp_update_post(
            array(
                'ID'           => $post->ID,
                'post_content' => $new_content,
            )
        );

        $converted++;
    }

    return array(
        'converted' => $converted,
        'skipped'   => $skipped,
    );
}

function lunara_convert_shortcodes_to_blocks( $content ) {
    $tags  = array( 'lunara_home', 'lunara_reviews', 'lunara_posts', 'lunara_carousel', 'lunara_still', 'lunara_debrief', 'lunara_pair_it_with', 'lunara_where_to_watch' );
    $regex = get_shortcode_regex( $tags );

    return preg_replace_callback(
        '/' . $regex . '/s',
        static function( $matches ) {
            $tag  = $matches[2];
            $atts = shortcode_parse_atts( $matches[3] );
            $atts = is_array( $atts ) ? $atts : array();

            return lunara_shortcode_to_block_comment( $tag, $atts );
        },
        $content
    );
}

function lunara_shortcode_to_block_comment( $tag, $atts ) {
    $map = array(
        'lunara_home'           => 'lunara/home',
        'lunara_reviews'        => 'lunara/reviews',
        'lunara_posts'          => 'lunara/posts',
        'lunara_carousel'       => 'lunara/carousel',
        'lunara_still'          => 'lunara/still',
        'lunara_debrief'        => 'lunara/debrief',
        'lunara_pair_it_with'   => 'lunara/pair-it-with',
        'lunara_where_to_watch' => 'lunara/where-to-watch',
    );

    if ( ! isset( $map[ $tag ] ) ) {
        return '';
    }

    $attributes = array();

    switch ( $tag ) {
        case 'lunara_reviews':
            $attributes['count'] = isset( $atts['count'] ) ? (int) $atts['count'] : 6;
            break;
        case 'lunara_posts':
            $attributes['category'] = isset( $atts['category'] ) ? sanitize_title( $atts['category'] ) : '';
            $attributes['count']    = isset( $atts['count'] ) ? (int) $atts['count'] : 6;
            break;
        case 'lunara_carousel':
            $attributes['set']   = isset( $atts['set'] ) ? sanitize_title( $atts['set'] ) : 'homepage';
            $attributes['limit'] = isset( $atts['limit'] ) ? (int) $atts['limit'] : -1;
            break;
        case 'lunara_still':
            $attributes['url']     = isset( $atts['url'] ) ? esc_url_raw( $atts['url'] ) : '';
            $attributes['alt']     = isset( $atts['alt'] ) ? sanitize_text_field( $atts['alt'] ) : '';
            $attributes['caption'] = isset( $atts['caption'] ) ? sanitize_text_field( $atts['caption'] ) : '';
            $attributes['kicker']  = isset( $atts['kicker'] ) ? sanitize_text_field( $atts['kicker'] ) : '';
            $attributes['style']   = isset( $atts['style'] ) ? sanitize_key( $atts['style'] ) : 'default';
            $attributes['loading'] = isset( $atts['loading'] ) ? sanitize_key( $atts['loading'] ) : 'lazy';
            break;
        case 'lunara_where_to_watch':
            $attributes['imdb']   = isset( $atts['imdb'] ) ? sanitize_text_field( $atts['imdb'] ) : '';
            $attributes['region'] = isset( $atts['region'] ) ? sanitize_text_field( $atts['region'] ) : 'US';
            break;
    }

    $json = empty( $attributes ) ? '' : ' ' . wp_json_encode( $attributes );

    return '<!-- wp:' . $map[ $tag ] . $json . ' /-->';
}
