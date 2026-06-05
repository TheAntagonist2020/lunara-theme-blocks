<?php
/**
 * Editorial Meta Boxes — per-post and per-review controls.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'lunara_add_post_editorial_meta_box' ) ) {

    /**
     * Enqueue the WordPress media library and inline CSS/JS for the
     * editorial meta-box media pickers on post / review screens.
     */
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

    /**
     * Render a reusable media-picker control inside a meta box.
     *
     * @param array $args {
     *     @type string $field_id    Input element ID.
     *     @type string $field_name  Input element name (defaults to $field_id).
     *     @type string $label       Visible label text.
     *     @type string $value       Current saved URL.
     *     @type string $description Small helper text beneath the control.
     * }
     */
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

    /* ------------------------------------------------------------------
     * Review meta box — register, render, save.
     * ---------------------------------------------------------------- */

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
        $hero_banner     = get_post_meta( $post->ID, '_lunara_review_hero_banner', true );
        $hide_standfirst = get_post_meta( $post->ID, '_lunara_review_hide_standfirst', true );
        $archive_label   = get_post_meta( $post->ID, '_lunara_review_archive_cta_label', true );
        $archive_url     = get_post_meta( $post->ID, '_lunara_review_archive_url_override', true );
        $hide_where             = get_post_meta( $post->ID, '_lunara_review_hide_where_card', true );
        $hide_details           = get_post_meta( $post->ID, '_lunara_review_hide_details_card', true );
        $home_hero_featured     = get_post_meta( $post->ID, '_lunara_review_home_hero_featured', true );
        $home_hero_priority     = get_post_meta( $post->ID, '_lunara_review_home_hero_priority', true );
        $featured_shelf_enabled = get_post_meta( $post->ID, '_lunara_review_home_featured_shelf', true );
        $featured_shelf_priority = get_post_meta( $post->ID, '_lunara_review_home_featured_priority', true );
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
            <small>Optional exact hook for gold review cards on the homepage, Reviews archive, and featured review cards. Aim for 18-32 words.</small>
        </p>
        <?php
        lunara_render_media_control(
            array(
                'field_id'    => 'lunara_review_card_image',
                'field_name'  => 'lunara_review_card_image',
                'label'       => 'Card Image Override',
                'value'       => (string) $card_image,
                'description' => 'Optional portrait/card crop for homepage and Reviews archive cards. Leaves the review hero and Featured image untouched.',
            )
        );

        lunara_render_media_control(
            array(
                'field_id'    => 'lunara_review_hero_banner',
                'field_name'  => 'lunara_review_hero_banner',
                'label'       => 'Hero Banner',
                'value'       => (string) $hero_banner,
                'description' => 'Optional widescreen hero image for the top of the review body. If left empty, the standard Featured image is used as the fallback hero.',
            )
        );
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

        if ( isset( $_POST['lunara_review_hero_banner'] ) ) {
            update_post_meta( $post_id, '_lunara_review_hero_banner', esc_url_raw( wp_unslash( $_POST['lunara_review_hero_banner'] ) ) );
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

    /* ------------------------------------------------------------------
     * Post meta box — register, render, save.
     * ---------------------------------------------------------------- */

    function lunara_add_post_editorial_meta_box() {
        // Registers on both `post` and `journal` — same editorial controls apply.
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
            <small>Replaces the automatic top label like News, Essay, or Dispatch.</small>
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
    add_action( 'save_post_post', 'lunara_save_post_editorial_meta' );
}
