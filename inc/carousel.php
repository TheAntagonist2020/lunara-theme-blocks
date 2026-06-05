<?php
/**
 * Slide Sets - Curated Carousels
 *
 * Carousel taxonomy, admin manager, drag-drop ordering,
 * [lunara_carousel] shortcode, and [lunara_still] shortcode.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ========================================
   SLIDE SETS - CURATED CAROUSELS
   ======================================== */

/**
 * Register Slide Sets taxonomy for Media
 * In WP Admin: Media Library -> click an image -> edit -> assign to a Slide Set (e.g., "homepage")
 * Then use: [lunara_carousel set="homepage"]
 */
if ( ! defined( 'LUNARA_CORE_VERSION' ) ) {
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
add_action( 'init', 'lunara_register_slide_set_taxonomy' );


/**
 * Attachment field: Carousel Link URL (stored as _lunara_slide_link).
 * Falls back to Alt Text for backward compatibility.
 */
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
add_filter( 'attachment_fields_to_edit', 'lunara_slide_link_edit_field', 10, 2 );

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
add_filter( 'attachment_fields_to_save', 'lunara_slide_link_save_field', 10, 2 );

/**
 * Admin: Carousel Manager (drag & drop ordering per Slide Set).
 */
function lunara_register_carousel_admin_page() {
    add_theme_page(
        'Lunara Carousel',
        'Lunara Carousel',
        'manage_options',
        'lunara-carousel-manager',
        'lunara_render_carousel_manager_page'
    );
}
add_action( 'admin_menu', 'lunara_register_carousel_admin_page' );

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
add_action( 'admin_enqueue_scripts', 'lunara_enqueue_carousel_admin_assets' );

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
    echo '<p><strong>How to update the carousel:</strong> Upload (or select) images in <em>Media → Library</em>, then assign them to a <em>Slide Set</em>. Use this page to drag & drop reorder slides. To add a link per slide, edit the media item and fill in <em>Carousel Link URL</em>.</p>';

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
            <p style="font-size:0.9em;">Go to Media Library → Edit an image → Assign to Slide Set</p>
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
add_shortcode( 'lunara_carousel', 'lunara_carousel_shortcode' );

/**
 * [lunara_still] — Full editorial control over cinematic stills in reviews.
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
