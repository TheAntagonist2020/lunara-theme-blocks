<?php
/**
 * Editorial publish safety guards.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lunara_featured_image_guard_post_types() {
    return apply_filters( 'lunara_featured_image_guard_post_types', array( 'post', 'journal', 'review' ) );
}

function lunara_featured_image_guard_filename_patterns() {
    return apply_filters(
        'lunara_featured_image_guard_filename_patterns',
        array(
            '/^favicon(?:-\d+)?\.(?:png|jpe?g|webp|gif)$/i',
            '/^clapper(?:[-_]\w+)?\.(?:png|jpe?g|webp|gif)$/i',
            '/^placeholder(?:[-_]\w+)?\.(?:png|jpe?g|webp|gif)$/i',
        )
    );
}

function lunara_featured_image_guard_blocked_source_patterns() {
    return apply_filters(
        'lunara_featured_image_guard_blocked_source_patterns',
        array(
            'World of Reel' => '/(?:worldofreel\.com|\bworld\s+of\s+reel\b)/i',
        )
    );
}

function lunara_featured_image_guard_dispatch_image_meta_keys() {
    return array(
        '_lunara_dispatch_image_url',
        '_lunara_dispatch_source_url',
        '_lunara_dispatch_source_label',
    );
}

function lunara_featured_image_guard_is_dispatch_image( $attachment_id ) {
    $attachment_id = absint( $attachment_id );
    if ( ! $attachment_id ) {
        return false;
    }

    foreach ( lunara_featured_image_guard_dispatch_image_meta_keys() as $meta_key ) {
        $value = get_post_meta( $attachment_id, $meta_key, true );
        if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
            return true;
        }
    }

    return false;
}

function lunara_featured_image_guard_visual_match_stopwords() {
    return array(
        'about', 'after', 'again', 'also', 'amid', 'among', 'and', 'are', 'article', 'attends', 'because', 'been',
        'before', 'being', 'black', 'but', 'california', 'can', 'credit', 'deadline', 'else', 'everyone', 'film',
        'films', 'from', 'getty', 'has', 'have', 'hollywood', 'image', 'into', 'its', 'journal', 'like', 'movie',
        'movies', 'news', 'october', 'photo', 'picture', 'pictures', 'post', 'presented', 'press', 'said', 'says',
        'source', 'that', 'the', 'their', 'them', 'this', 'through', 'universal', 'was', 'were', 'what', 'when',
        'where', 'which', 'while', 'who', 'will', 'with', 'would',
    );
}

function lunara_featured_image_guard_visual_match_tokens( $text ) {
    $text = strtolower( wp_strip_all_tags( (string) $text ) );
    $text = html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );
    $parts = preg_split( '/[^a-z0-9]+/', $text, -1, PREG_SPLIT_NO_EMPTY );
    if ( empty( $parts ) || ! is_array( $parts ) ) {
        return array();
    }

    $stopwords = lunara_featured_image_guard_visual_match_stopwords();
    $tokens    = array();

    foreach ( $parts as $part ) {
        $part = trim( (string) $part );
        if ( strlen( $part ) < 4 || in_array( $part, $stopwords, true ) ) {
            continue;
        }

        $tokens[ $part ] = true;
    }

    return array_keys( $tokens );
}

function lunara_featured_image_guard_post_visual_match_text( $post_id ) {
    $post_id = absint( $post_id );
    $post    = $post_id ? get_post( $post_id ) : null;
    if ( ! $post instanceof WP_Post ) {
        return '';
    }

    $strings = array(
        $post->post_title,
        $post->post_excerpt,
        wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 120, '' ),
    );

    if ( 'journal' === $post->post_type && function_exists( 'lunara_get_journal_source_items' ) ) {
        foreach ( lunara_get_journal_source_items( $post_id ) as $source_item ) {
            $strings[] = implode( ' ', array_filter( $source_item ) );
        }
    }

    if ( 'journal' === $post->post_type && function_exists( 'lunara_get_journal_field_value' ) ) {
        foreach ( array( 'journal_image_credit', 'journal_image_source_url', 'journal_image_alt' ) as $field_name ) {
            $value = lunara_get_journal_field_value( $post_id, $field_name );
            if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
                $strings[] = (string) $value;
            }
        }
    }

    foreach ( array( '_lunara_source_name', '_lunara_source_url', '_lunara_journal_source_name', '_lunara_journal_source_url', '_lunara_dispatch_source_label', '_lunara_dispatch_source_url' ) as $meta_key ) {
        $value = get_post_meta( $post_id, $meta_key, true );
        if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
            $strings[] = (string) $value;
        }
    }

    return implode( ' ', array_filter( $strings ) );
}

function lunara_featured_image_guard_attachment_visual_match_text( $attachment_id ) {
    $attachment_id = absint( $attachment_id );
    $attachment    = $attachment_id ? get_post( $attachment_id ) : null;
    if ( ! $attachment instanceof WP_Post ) {
        return '';
    }

    $strings = array(
        $attachment->post_title,
        $attachment->post_excerpt,
        $attachment->post_content,
        $attachment->guid,
        wp_basename( (string) get_attached_file( $attachment_id ) ),
    );

    foreach ( array( '_wp_attachment_image_alt', '_lunara_image_credit', '_lunara_image_source_name', '_lunara_image_source_url', '_lunara_dispatch_image_credit', '_lunara_dispatch_source_label', '_lunara_dispatch_source_url' ) as $meta_key ) {
        $value = get_post_meta( $attachment_id, $meta_key, true );
        if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
            $strings[] = (string) $value;
        }
    }

    $metadata = wp_get_attachment_metadata( $attachment_id );
    if ( is_array( $metadata ) && ! empty( $metadata['image_meta'] ) && is_array( $metadata['image_meta'] ) ) {
        foreach ( array( 'caption', 'title', 'credit', 'copyright' ) as $meta_key ) {
            if ( ! empty( $metadata['image_meta'][ $meta_key ] ) ) {
                $strings[] = (string) $metadata['image_meta'][ $meta_key ];
            }
        }
    }

    return implode( ' ', array_filter( $strings ) );
}

function lunara_featured_image_guard_visual_match_details( $post_id, $attachment_id ) {
    $post_tokens       = lunara_featured_image_guard_visual_match_tokens( lunara_featured_image_guard_post_visual_match_text( $post_id ) );
    $attachment_tokens = lunara_featured_image_guard_visual_match_tokens( lunara_featured_image_guard_attachment_visual_match_text( $attachment_id ) );
    $shared            = array_values( array_intersect( $post_tokens, $attachment_tokens ) );

    return array(
        'score'             => count( $shared ),
        'shared'            => $shared,
        'post_tokens'       => $post_tokens,
        'attachment_tokens' => $attachment_tokens,
    );
}

function lunara_featured_image_guard_subject_mismatch_reason( $post_id, $attachment_id ) {
    $post_id       = absint( $post_id );
    $attachment_id = absint( $attachment_id );

    if ( ! $post_id || ! $attachment_id || 'journal' !== get_post_type( $post_id ) || ! wp_attachment_is_image( $attachment_id ) ) {
        return '';
    }

    if ( ! lunara_featured_image_guard_is_dispatch_image( $attachment_id ) ) {
        return '';
    }

    $details = lunara_featured_image_guard_visual_match_details( $post_id, $attachment_id );
    if ( ! empty( $details['score'] ) && (int) $details['score'] >= 2 ) {
        return '';
    }

    return __( 'Possible subject mismatch: this Dispatch-imported image metadata has too little overlap with the Journal entry. Review the featured image before publishing.', 'lunara-film' );
}

function lunara_featured_image_guard_attachment_filenames( $attachment_id ) {
    $attachment_id = absint( $attachment_id );
    if ( ! $attachment_id ) {
        return array();
    }

    $filenames = array();
    $file      = (string) get_attached_file( $attachment_id );
    $url       = (string) wp_get_attachment_url( $attachment_id );

    if ( '' !== $file ) {
        $filenames[] = wp_basename( $file );
    }

    if ( '' !== $url ) {
        $path = (string) wp_parse_url( $url, PHP_URL_PATH );
        if ( '' !== $path ) {
            $filenames[] = wp_basename( $path );
        }
    }

    return array_values( array_unique( array_filter( $filenames ) ) );
}

function lunara_featured_image_guard_attachment_source_strings( $attachment_id ) {
    $attachment_id = absint( $attachment_id );
    if ( ! $attachment_id ) {
        return array();
    }

    $attachment = get_post( $attachment_id );
    $strings    = array();

    if ( $attachment instanceof WP_Post ) {
        $strings[] = $attachment->post_title;
        $strings[] = $attachment->post_excerpt;
        $strings[] = $attachment->post_content;
        $strings[] = $attachment->guid;
    }

    $meta_keys = array(
        '_wp_attachment_image_alt',
        '_lunara_dispatch_source_url',
        '_lunara_dispatch_source_label',
        '_lunara_dispatch_image_url',
        '_lunara_source_url',
        '_lunara_source_name',
        '_source_url',
        '_source_name',
    );

    foreach ( $meta_keys as $meta_key ) {
        $value = get_post_meta( $attachment_id, $meta_key, true );
        if ( is_scalar( $value ) && '' !== (string) $value ) {
            $strings[] = (string) $value;
        }
    }

    return array_values( array_filter( array_unique( array_map( 'strval', $strings ) ) ) );
}

function lunara_featured_image_guard_block_reason( $attachment_id ) {
    $attachment_id = absint( $attachment_id );
    if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
        return '';
    }

    $filenames = lunara_featured_image_guard_attachment_filenames( $attachment_id );
    foreach ( $filenames as $filename ) {
        foreach ( lunara_featured_image_guard_filename_patterns() as $pattern ) {
            if ( preg_match( $pattern, $filename ) ) {
                return sprintf(
                    /* translators: %s: attachment filename. */
                    __( 'Blocked placeholder filename: %s', 'lunara-film' ),
                    $filename
                );
            }
        }
    }

    $source_strings = lunara_featured_image_guard_attachment_source_strings( $attachment_id );
    foreach ( $source_strings as $source_string ) {
        foreach ( lunara_featured_image_guard_blocked_source_patterns() as $source_label => $pattern ) {
            if ( preg_match( $pattern, $source_string ) ) {
                return sprintf(
                    /* translators: %s: blocked source label. */
                    __( 'Blocked featured image source: %s images are not approved for Lunara featured images.', 'lunara-film' ),
                    $source_label
                );
            }
        }
    }

    return '';
}

function lunara_featured_image_guard_is_blocked_attachment( $attachment_id ) {
    return '' !== lunara_featured_image_guard_block_reason( $attachment_id );
}

function lunara_featured_image_guard_get_post_thumbnail_candidate( $postarr ) {
    if ( isset( $_POST['_thumbnail_id'] ) ) {
        $raw_thumbnail_id = sanitize_text_field( wp_unslash( $_POST['_thumbnail_id'] ) );
        if ( '-1' === $raw_thumbnail_id || '' === $raw_thumbnail_id ) {
            return 0;
        }

        return absint( $raw_thumbnail_id );
    }

    $post_id = isset( $postarr['ID'] ) ? absint( $postarr['ID'] ) : 0;
    return $post_id ? absint( get_post_thumbnail_id( $post_id ) ) : 0;
}

function lunara_featured_image_guard_notice_key( $user_id = 0 ) {
    $user_id = $user_id ? absint( $user_id ) : absint( get_current_user_id() );
    return 'lunara_featured_image_guard_notice_' . $user_id;
}

function lunara_featured_image_guard_set_notice( $post_id, $attachment_id, $reason ) {
    $user_id = absint( get_current_user_id() );
    if ( ! $user_id ) {
        return;
    }

    set_transient(
        lunara_featured_image_guard_notice_key( $user_id ),
        array(
            'post_id'       => absint( $post_id ),
            'attachment_id' => absint( $attachment_id ),
            'reason'        => (string) $reason,
        ),
        5 * MINUTE_IN_SECONDS
    );
}

function lunara_featured_image_guard_filter_post_data( $data, $postarr ) {
    $post_type   = isset( $data['post_type'] ) ? (string) $data['post_type'] : '';
    $post_status = isset( $data['post_status'] ) ? (string) $data['post_status'] : '';

    if ( ! in_array( $post_type, lunara_featured_image_guard_post_types(), true ) ) {
        return $data;
    }

    if ( ! in_array( $post_status, array( 'publish', 'future' ), true ) ) {
        return $data;
    }

    $thumbnail_id = lunara_featured_image_guard_get_post_thumbnail_candidate( $postarr );
    if ( ! $thumbnail_id ) {
        return $data;
    }

    $reason = lunara_featured_image_guard_block_reason( $thumbnail_id );
    if ( '' === $reason ) {
        $post_id = isset( $postarr['ID'] ) ? absint( $postarr['ID'] ) : 0;
        $reason  = $post_id ? lunara_featured_image_guard_subject_mismatch_reason( $post_id, $thumbnail_id ) : '';
    }
    if ( '' === $reason ) {
        return $data;
    }

    $data['post_status'] = 'draft';
    lunara_featured_image_guard_set_notice( isset( $postarr['ID'] ) ? absint( $postarr['ID'] ) : 0, $thumbnail_id, $reason );

    return $data;
}
add_filter( 'wp_insert_post_data', 'lunara_featured_image_guard_filter_post_data', 20, 2 );

function lunara_featured_image_guard_admin_notice() {
    if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
        return;
    }

    $notice = get_transient( lunara_featured_image_guard_notice_key() );
    if ( is_array( $notice ) && ! empty( $notice['attachment_id'] ) ) {
        delete_transient( lunara_featured_image_guard_notice_key() );
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'Lunara blocked publishing.', 'lunara-film' ); ?></strong>
                <?php
                printf(
                    /* translators: 1: attachment ID, 2: block reason. */
                    esc_html__( 'Attachment #%1$d is not allowed as a featured image. %2$s The post was kept as a draft.', 'lunara-film' ),
                    absint( $notice['attachment_id'] ),
                    esc_html( (string) $notice['reason'] )
                );
                ?>
            </p>
        </div>
        <?php
        return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen ) {
        return;
    }

    if ( 'upload' === $screen->base ) {
        $attachment_id = isset( $_GET['item'] ) ? absint( $_GET['item'] ) : 0;
        $reason        = $attachment_id ? lunara_featured_image_guard_block_reason( $attachment_id ) : '';
        if ( '' === $reason ) {
            return;
        }
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'Blocked for Lunara featured images.', 'lunara-film' ); ?></strong>
                <?php
                printf(
                    /* translators: 1: attachment ID, 2: block reason. */
                    esc_html__( 'Attachment #%1$d can stay in the library, but it cannot be used as a featured image on posts, Journal entries, or Reviews. %2$s', 'lunara-film' ),
                    absint( $attachment_id ),
                    esc_html( $reason )
                );
                ?>
            </p>
        </div>
        <?php
        return;
    }

    if ( 'post' !== $screen->base ) {
        return;
    }

    $post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
    if ( ! $post_id || ! in_array( get_post_type( $post_id ), lunara_featured_image_guard_post_types(), true ) ) {
        return;
    }

    $thumbnail_id = get_post_thumbnail_id( $post_id );
    $reason       = $thumbnail_id ? lunara_featured_image_guard_block_reason( $thumbnail_id ) : '';
    if ( '' === $reason && $thumbnail_id ) {
        $reason = lunara_featured_image_guard_subject_mismatch_reason( $post_id, $thumbnail_id );
    }
    if ( '' === $reason ) {
        return;
    }
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e( 'Blocked featured image.', 'lunara-film' ); ?></strong>
            <?php
            printf(
                /* translators: 1: attachment ID, 2: block reason. */
                esc_html__( 'Attachment #%1$d cannot be published on Lunara. Replace it before publishing. %2$s', 'lunara-film' ),
                absint( $thumbnail_id ),
                esc_html( $reason )
            );
            ?>
        </p>
    </div>
    <?php
}
add_action( 'admin_notices', 'lunara_featured_image_guard_admin_notice' );

function lunara_featured_image_guard_prepare_attachment_for_js( $response, $attachment, $meta ) {
    $reason = ! empty( $attachment->ID ) ? lunara_featured_image_guard_block_reason( $attachment->ID ) : '';

    $response['lunaraBlockedFeaturedImage']       = '' !== $reason;
    $response['lunaraBlockedFeaturedImageReason'] = $reason;

    return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'lunara_featured_image_guard_prepare_attachment_for_js', 10, 3 );

function lunara_featured_image_guard_admin_head() {
    if ( ! is_admin() ) {
        return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || ! in_array( $screen->base, array( 'post', 'upload' ), true ) ) {
        return;
    }
    ?>
    <style id="lunara-featured-image-guard-admin-css">
    .lunara-media-blocked-featured{box-shadow:inset 0 0 0 4px #d63638!important;}
    .lunara-media-blocked-badge{background:#d63638;color:#fff;font-size:11px;font-weight:700;left:0;line-height:1.2;padding:4px 6px;position:absolute;right:0;text-align:center;top:0;z-index:20;}
    .lunara-media-blocked-selection-note{background:#fff5f6;border-left:4px solid #d63638;color:#1d2327;font-size:12px;margin:0 0 8px;padding:8px 10px;}
    .lunara-media-blocked-panel-note{background:#fff5f6;border:1px solid #d63638;border-left-width:4px;color:#1d2327;font-size:12px;line-height:1.4;margin:0 0 12px;padding:10px 12px;}
    .lunara-featured-image-guard-fixed-notice{background:#fff5f6;border:1px solid #d63638;border-left-width:5px;box-shadow:0 10px 30px rgba(0,0,0,.18);box-sizing:border-box;color:#1d2327;font-size:13px;left:180px;line-height:1.45;padding:12px 14px;position:fixed;right:32px;top:48px;z-index:200000;}
    .lunara-featured-image-guard-fixed-notice strong{display:block;margin-bottom:3px;}
    @media (max-width:782px){.lunara-featured-image-guard-fixed-notice{left:12px;right:12px;top:58px;}}
    .media-button-select.lunara-media-button-blocked{cursor:not-allowed;opacity:.55;}
    </style>
    <?php
}
add_action( 'admin_head', 'lunara_featured_image_guard_admin_head' );

function lunara_featured_image_guard_admin_footer() {
    if ( ! is_admin() ) {
        return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || ! in_array( $screen->base, array( 'post', 'upload' ), true ) ) {
        return;
    }

    $upload_attachment_id = 'upload' === $screen->base && isset( $_GET['item'] ) ? absint( $_GET['item'] ) : 0;
    $upload_reason        = $upload_attachment_id ? lunara_featured_image_guard_block_reason( $upload_attachment_id ) : '';
    if ( '' !== $upload_reason ) {
        ?>
        <div class="lunara-featured-image-guard-fixed-notice">
            <strong><?php esc_html_e( 'Blocked featured image', 'lunara-film' ); ?></strong>
            <?php
            printf(
                /* translators: 1: attachment ID, 2: block reason. */
                esc_html__( 'Attachment #%1$d can stay in the Media Library, but it cannot be used as a Lunara featured image. %2$s', 'lunara-film' ),
                absint( $upload_attachment_id ),
                esc_html( $upload_reason )
            );
            ?>
        </div>
        <?php
    }
    ?>
    <script id="lunara-featured-image-guard-admin-js">
    (function($){
        if (!window.wp || !wp.media || !wp.media.view) {
            return;
        }

        function isBlocked(model) {
            return !!(model && model.get && model.get('lunaraBlockedFeaturedImage'));
        }

        function blockReason(model) {
            return model && model.get ? (model.get('lunaraBlockedFeaturedImageReason') || 'This image is blocked for Lunara featured images.') : 'This image is blocked for Lunara featured images.';
        }

        function decorate(view) {
            if (!view || !view.model || !view.$el) {
                return;
            }

            var blocked = isBlocked(view.model);
            view.$el.toggleClass('lunara-media-blocked-featured', blocked);
            view.$el.find('.lunara-media-blocked-badge').remove();

            if (blocked) {
                view.$el.append('<span class="lunara-media-blocked-badge">Blocked featured image</span>');
            }
        }

        function decorateFilenamePanels() {
            $('.attachment-details, .media-sidebar, .attachment-info').each(function(){
                var $panel = $(this);
                if ($panel.find('.lunara-media-blocked-panel-note').length) {
                    return;
                }

                if (!/File name:\s*favicon(?:-\d+)?\.(?:png|jpe?g|webp|gif)/i.test($panel.text())) {
                    return;
                }

                $('<div class="lunara-media-blocked-panel-note"><strong>Blocked featured image.</strong> This media item cannot be used as a featured image on Lunara posts, Journal entries, or Reviews.</div>').prependTo($panel);
            });
        }

        function patchView(View) {
            if (!View || !View.prototype || View.prototype.lunaraFeaturedGuardPatched) {
                return;
            }

            var render = View.prototype.render;
            View.prototype.render = function() {
                var out = render.apply(this, arguments);
                decorate(this);
                return out;
            };
            View.prototype.lunaraFeaturedGuardPatched = true;
        }

        function selectedAttachment() {
            var frame = wp.media.frame;
            var state = frame && frame.state ? frame.state() : null;
            var selection = state && state.get ? state.get('selection') : null;
            return selection && selection.first ? selection.first() : null;
        }

        function refreshToolbar() {
            window.setTimeout(function(){
                var model = selectedAttachment();
                var blocked = isBlocked(model);
                var $button = $('.media-button-select');
                var $toolbar = $('.media-frame-toolbar .media-toolbar-primary');

                $button.prop('disabled', blocked).toggleClass('disabled lunara-media-button-blocked', blocked);
                $toolbar.find('.lunara-media-blocked-selection-note').remove();

                if (blocked) {
                    $('<div class="lunara-media-blocked-selection-note"></div>').text(blockReason(model)).prependTo($toolbar);
                }
            }, 20);
        }

        patchView(wp.media.view.Attachment);
        patchView(wp.media.view.Attachment.Details);
        if (wp.media.view.Attachment.Details && wp.media.view.Attachment.Details.TwoColumn) {
            patchView(wp.media.view.Attachment.Details.TwoColumn);
        }

        $(document).on('click keyup', '.attachment, .media-modal, .media-menu-item', function(){
            refreshToolbar();
            decorateFilenamePanels();
        });
        $(document).on('click', '.media-button-select', function(event){
            var model = selectedAttachment();
            if (!isBlocked(model)) {
                return true;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            refreshToolbar();
            window.alert(blockReason(model));
            return false;
        });

        decorateFilenamePanels();
        var scanCount = 0;
        var scanTimer = window.setInterval(function(){
            decorateFilenamePanels();
            scanCount++;
            if (scanCount > 30) {
                window.clearInterval(scanTimer);
            }
        }, 500);
    })(jQuery);
    </script>
    <?php
}
add_action( 'admin_footer', 'lunara_featured_image_guard_admin_footer' );
