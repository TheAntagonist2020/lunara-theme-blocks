<?php
/**
 * wp-admin command surface for Lunara publishing work.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lunara_register_control_desk_page() {
    add_menu_page(
        __( 'Lunara Control Desk', 'lunara-film' ),
        __( 'Lunara', 'lunara-film' ),
        'edit_posts',
        'lunara-control-desk',
        'lunara_render_control_desk_page',
        'dashicons-welcome-view-site',
        3
    );

    add_theme_page(
        __( 'Lunara Control Desk', 'lunara-film' ),
        __( 'Lunara Control Desk', 'lunara-film' ),
        'edit_posts',
        'lunara-control-desk',
        'lunara_render_control_desk_page'
    );

    remove_submenu_page( 'themes.php', 'lunara-control-desk' );
}
add_action( 'admin_menu', 'lunara_register_control_desk_page' );

function lunara_enqueue_control_desk_assets( $hook ) {
    if ( ! in_array( $hook, array( 'toplevel_page_lunara-control-desk', 'appearance_page_lunara-control-desk' ), true ) ) {
        return;
    }

    if ( ! function_exists( 'lunara_resolve_theme_asset' ) ) {
        return;
    }

    wp_enqueue_media();

    $style = lunara_resolve_theme_asset( 'assets/css/lunara-control-desk.css' );
    if ( ! empty( $style['uri'] ) ) {
        wp_enqueue_style(
            'lunara-control-desk',
            $style['uri'],
            array(),
            function_exists( 'lunara_theme_asset_version' ) ? lunara_theme_asset_version( $style['path'] ) : wp_get_theme()->get( 'Version' )
        );
    }

    $script = lunara_resolve_theme_asset( 'assets/js/lunara-control-desk.js' );
    if ( ! empty( $script['uri'] ) ) {
        wp_enqueue_script(
            'lunara-control-desk',
            $script['uri'],
            array(),
            function_exists( 'lunara_theme_asset_version' ) ? lunara_theme_asset_version( $script['path'] ) : wp_get_theme()->get( 'Version' ),
            true
        );

        wp_localize_script(
            'lunara-control-desk',
            'LunaraControlDesk',
            array(
                'suggestUrl' => esc_url_raw( rest_url( 'lunara-ai-classic/v1/suggest' ) ),
                'nonce'      => wp_create_nonce( 'wp_rest' ),
                'i18n'       => array(
                    'working' => __( 'Asking the private AI desk...', 'lunara-film' ),
                    'ready'   => __( 'Suggestion saved privately.', 'lunara-film' ),
                    'failed'  => __( 'Suggestion request failed.', 'lunara-film' ),
                    'copied'  => __( 'Copied.', 'lunara-film' ),
                ),
            )
        );
    }
}
add_action( 'admin_enqueue_scripts', 'lunara_enqueue_control_desk_assets' );

function lunara_control_desk_save_journal_lead() {
    $redirect = lunara_control_desk_admin_url(
        array(
            'tab' => 'journal-growth',
        )
    );

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'journal_lead_forbidden', $redirect ) );
        exit;
    }

    check_admin_referer( 'lunara_save_journal_lead', 'lunara_journal_lead_nonce' );

    $raw_value = isset( $_POST['lunara_home_journal_lead_post_id'] )
        ? sanitize_text_field( wp_unslash( $_POST['lunara_home_journal_lead_post_id'] ) )
        : '';
    $lead_id   = 0;

    if ( '' !== $raw_value && '0' !== $raw_value ) {
        $lead_ids = function_exists( 'lunara_parse_manual_post_ids' )
            ? lunara_parse_manual_post_ids( $raw_value, 'journal' )
            : array();
        $lead_id  = ! empty( $lead_ids[0] ) ? absint( $lead_ids[0] ) : 0;

        if ( ! $lead_id ) {
            wp_safe_redirect( add_query_arg( 'lunara_notice', 'journal_lead_invalid', $redirect ) );
            exit;
        }
    }

    if ( $lead_id ) {
        set_theme_mod( 'lunara_home_journal_lead_post_id', (string) $lead_id );
    } else {
        remove_theme_mod( 'lunara_home_journal_lead_post_id' );
    }

    wp_safe_redirect( add_query_arg( 'lunara_notice', 'journal_lead_saved', $redirect ) );
    exit;
}
add_action( 'admin_post_lunara_save_journal_lead', 'lunara_control_desk_save_journal_lead' );

function lunara_control_desk_parse_journal_carousel_ids( $raw_ids ) {
    if ( is_array( $raw_ids ) ) {
        $raw_ids = implode( ',', $raw_ids );
    }

    $raw_ids = preg_replace( '/[\s;|]+/', ',', (string) $raw_ids );
    $parts   = array_filter( array_map( 'trim', explode( ',', $raw_ids ) ) );
    $ids     = array();

    foreach ( $parts as $part ) {
        $attachment_id = absint( $part );
        if ( $attachment_id <= 0 || in_array( $attachment_id, $ids, true ) ) {
            continue;
        }

        $mime = (string) get_post_mime_type( $attachment_id );
        if ( 0 !== strpos( $mime, 'image/' ) ) {
            continue;
        }

        $ids[] = $attachment_id;
    }

    return $ids;
}

function lunara_control_desk_get_journal_carousel_ids( $post_id ) {
    $post_id = absint( $post_id );
    if ( $post_id <= 0 ) {
        return array();
    }

    $raw_ids = get_post_meta( $post_id, '_lunara_journal_carousel_ids', true );
    if ( '' === (string) $raw_ids ) {
        $raw_ids = get_post_meta( $post_id, '_lunara_journal_gallery_ids', true );
    }

    return lunara_control_desk_parse_journal_carousel_ids( $raw_ids );
}

function lunara_control_desk_save_journal_carousel() {
    $post_id = isset( $_POST['lunara_journal_post_id'] ) ? absint( wp_unslash( $_POST['lunara_journal_post_id'] ) ) : 0;
    $base    = lunara_control_desk_admin_url( array( 'tab' => 'journal-growth' ) );

    if ( $post_id > 0 ) {
        $base = add_query_arg( 'lcd_journal_visual_post', $post_id, $base );
    }

    $fragment = '#lunara-journal-visual-file-manager';

    if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'journal_carousel_forbidden', $base ) . $fragment );
        exit;
    }

    $post = get_post( $post_id );
    if ( ! ( $post instanceof WP_Post ) || 'journal' !== $post->post_type ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'journal_carousel_invalid', $base ) . $fragment );
        exit;
    }

    check_admin_referer( 'lunara_save_journal_carousel_' . $post_id, 'lunara_journal_carousel_nonce' );

    $raw_ids = isset( $_POST['lunara_journal_carousel_ids'] )
        ? sanitize_text_field( wp_unslash( $_POST['lunara_journal_carousel_ids'] ) )
        : '';
    $ids     = lunara_control_desk_parse_journal_carousel_ids( $raw_ids );

    $credits      = isset( $_POST['lunara_journal_carousel_credit'] ) && is_array( $_POST['lunara_journal_carousel_credit'] )
        ? wp_unslash( $_POST['lunara_journal_carousel_credit'] )
        : array();
    $source_names = isset( $_POST['lunara_journal_carousel_source_name'] ) && is_array( $_POST['lunara_journal_carousel_source_name'] )
        ? wp_unslash( $_POST['lunara_journal_carousel_source_name'] )
        : array();
    $source_urls  = isset( $_POST['lunara_journal_carousel_source_url'] ) && is_array( $_POST['lunara_journal_carousel_source_url'] )
        ? wp_unslash( $_POST['lunara_journal_carousel_source_url'] )
        : array();

    foreach ( $ids as $attachment_id ) {
        if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
            continue;
        }

        $credit      = isset( $credits[ $attachment_id ] ) ? sanitize_text_field( $credits[ $attachment_id ] ) : '';
        $source_name = isset( $source_names[ $attachment_id ] ) ? sanitize_text_field( $source_names[ $attachment_id ] ) : '';
        $source_url  = isset( $source_urls[ $attachment_id ] ) ? esc_url_raw( $source_urls[ $attachment_id ] ) : '';

        '' !== $credit ? update_post_meta( $attachment_id, '_lunara_image_credit', $credit ) : delete_post_meta( $attachment_id, '_lunara_image_credit' );
        '' !== $source_name ? update_post_meta( $attachment_id, '_lunara_image_source_name', $source_name ) : delete_post_meta( $attachment_id, '_lunara_image_source_name' );
        '' !== $source_url ? update_post_meta( $attachment_id, '_lunara_image_source_url', $source_url ) : delete_post_meta( $attachment_id, '_lunara_image_source_url' );
    }

    if ( empty( $ids ) ) {
        delete_post_meta( $post_id, '_lunara_journal_carousel_ids' );
    } else {
        update_post_meta( $post_id, '_lunara_journal_carousel_ids', implode( ',', $ids ) );
    }

    wp_safe_redirect( add_query_arg( 'lunara_notice', 'journal_carousel_saved', $base ) . $fragment );
    exit;
}
add_action( 'admin_post_lunara_save_journal_carousel', 'lunara_control_desk_save_journal_carousel' );

function lunara_control_desk_post_types() {
    return array_values(
        array_filter(
            array( 'review', 'journal', 'post' ),
            static function ( $post_type ) {
                return post_type_exists( $post_type );
            }
        )
    );
}

function lunara_control_desk_statuses() {
    return array( 'draft', 'pending' );
}

function lunara_control_desk_admin_url( $args = array() ) {
    return add_query_arg(
        wp_parse_args(
            $args,
            array( 'page' => 'lunara-control-desk' )
        ),
        admin_url( 'admin.php' )
    );
}

function lunara_control_desk_plugin_file_version( $relative_file ) {
    $file = trailingslashit( WP_PLUGIN_DIR ) . ltrim( $relative_file, '/\\' );

    if ( ! file_exists( $file ) ) {
        return '';
    }

    if ( ! function_exists( 'get_file_data' ) ) {
        require_once ABSPATH . 'wp-includes/functions.php';
    }

    $data = get_file_data(
        $file,
        array(
            'Version' => 'Version',
            'Name'    => 'Plugin Name',
        ),
        'plugin'
    );

    return ! empty( $data['Version'] ) ? $data['Version'] : '';
}

function lunara_control_desk_is_plugin_active( $relative_file ) {
    if ( ! function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    return function_exists( 'is_plugin_active' ) && is_plugin_active( ltrim( $relative_file, '/\\' ) );
}

function lunara_control_desk_get_system_status() {
    $theme        = wp_get_theme();
    $parent       = $theme->parent();
    $core_version = lunara_control_desk_plugin_file_version( 'lunara-core/lunara-core.php' );
    $core_active  = lunara_control_desk_is_plugin_active( 'lunara-core/lunara-core.php' );
    $aat_version  = defined( 'AAT_VERSION' ) ? AAT_VERSION : lunara_control_desk_plugin_file_version( 'academy-awards-table-optimized/academy-awards-table.php' );
    $ai_version   = lunara_control_desk_plugin_file_version( 'lunara-ai-assistant-classic/lunara-ai-assistant-classic.php' );
    $active_theme = get_stylesheet();
    $parent_slug  = get_template();
    $review_model = post_type_exists( 'review' ) && taxonomy_exists( 'lunara_director' ) && taxonomy_exists( 'lunara_review_year' );
    $carousel_model = taxonomy_exists( 'lunara_slide_set' );

    return array(
        array(
            'label' => __( 'Active child theme', 'lunara-film' ),
            'value' => sprintf(
                '%1$s %2$s',
                $active_theme,
                $theme->get( 'Version' ) ? '(' . $theme->get( 'Version' ) . ')' : ''
            ),
            'state' => 'lunara-theme-blocks-20260513-2300' === $active_theme ? 'ready' : 'needs',
            'note'  => __( 'Expected stylesheet is lunara-theme-blocks-20260513-2300.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Parent theme', 'lunara-film' ),
            'value' => $parent ? $parent_slug . ' / ' . $parent->get( 'Name' ) : $parent_slug,
            'state' => 'blocksy' === $parent_slug ? 'ready' : 'weak',
            'note'  => __( 'Blocksy should remain the parent only.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'AI Classic plugin', 'lunara-film' ),
            'value' => $ai_version ? $ai_version : __( 'Not detected', 'lunara-film' ),
            'state' => $ai_version ? 'ready' : 'needs',
            'note'  => __( 'Owns provider calls and private suggestion snapshots.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Lunara Core plugin', 'lunara-film' ),
            'value' => $core_version ? $core_version : __( 'Not detected', 'lunara-film' ),
            'state' => $core_active ? 'ready' : 'weak',
            'note'  => $core_active ? __( 'Active load-bearing plugin; do not deactivate live without staging checks.', 'lunara-film' ) : __( 'Inactive: theme fallbacks should carry Review models, but this needs staging verification.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Review model layer', 'lunara-film' ),
            'value' => $review_model && $carousel_model ? __( 'Registered', 'lunara-film' ) : __( 'Incomplete', 'lunara-film' ),
            'state' => $review_model && $carousel_model ? 'ready' : 'needs',
            'note'  => __( 'Checks Review CPT, director/year taxonomies, and slide-set taxonomy after init.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Academy Awards plugin', 'lunara-film' ),
            'value' => $aat_version ? $aat_version : __( 'Not detected', 'lunara-film' ),
            'state' => '2.7.16' === $aat_version ? 'ready' : ( $aat_version ? 'weak' : 'needs' ),
            'note'  => __( 'The active source is the GitHub-backed Oscars Ledger work tree under G:\\lunara-backups\\work.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Object cache', 'lunara-film' ),
            'value' => wp_using_ext_object_cache() ? __( 'External cache active', 'lunara-film' ) : __( 'Default WordPress cache', 'lunara-film' ),
            'state' => 'ready',
            'note'  => __( 'Flush cache after every deployment.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Last deploy backup', 'lunara-film' ),
            'value' => get_option( 'lunara_control_desk_last_deploy_backup', __( 'Not recorded in WordPress yet', 'lunara-film' ) ),
            'state' => get_option( 'lunara_control_desk_last_deploy_backup', '' ) ? 'ready' : 'weak',
            'note'  => __( 'Current rollout backups are still tracked in the session log unless this option is set later.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_get_source_status() {
    return array(
        array(
            'label' => __( 'Theme source', 'lunara-film' ),
            'value' => 'G:\\lunara-backups\\work\\lunara-theme-blocks-20260513-2300',
            'state' => 'ready',
            'note'  => 'github.com/TheAntagonist2020/lunara-theme-blocks',
        ),
        array(
            'label' => __( 'Live theme', 'lunara-film' ),
            'value' => '/home/151589083/htdocs/wp-content/themes/lunara-theme-blocks-20260513-2300',
            'state' => 'ready',
        ),
        array(
            'label' => __( 'AI plugin source', 'lunara-film' ),
            'value' => 'G:\\lunara-backups\\work\\lunara-ai-assistant-classic',
            'state' => 'ready',
            'note'  => 'github.com/TheAntagonist2020/lunara-plugin-ai-assistant-classic',
        ),
        array(
            'label' => __( 'Core plugin source', 'lunara-film' ),
            'value' => 'G:\\lunara-backups\\work\\lunara-core',
            'state' => 'ready',
            'note'  => 'github.com/TheAntagonist2020/lunara-plugin-core',
        ),
        array(
            'label' => __( 'Oscars plugin source', 'lunara-film' ),
            'value' => 'G:\\lunara-backups\\work\\academy-awards-table-optimized',
            'state' => 'ready',
            'note'  => 'github.com/TheAntagonist2020/lunara-plugin-oscars-ledger',
        ),
        array(
            'label' => __( 'Dispatch plugin source', 'lunara-film' ),
            'value' => 'G:\\lunara-backups\\work\\lunara-dispatch',
            'state' => 'ready',
            'note'  => 'github.com/TheAntagonist2020/lunara-plugin-dispatch',
        ),
        array(
            'label' => __( 'IMDb Guard source', 'lunara-film' ),
            'value' => 'G:\\lunara-backups\\work\\lunara-imdb-guard',
            'state' => 'ready',
            'note'  => 'github.com/TheAntagonist2020/lunara-plugin-imdb-guard',
        ),
        array(
            'label' => __( 'Legacy AI source reference', 'lunara-film' ),
            'value' => 'C:\\Users\\silve_i21do49\\OneDrive\\Documents\\New project\\plugins\\lunara-ai-assistant-classic',
            'state' => 'weak',
            'note'  => __( 'Historical source location only; use the G: work tree for new edits.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Stale Oscars copy warning', 'lunara-film' ),
            'value' => 'C:\\Users\\silve_i21do49\\OneDrive\\Desktop\\New folder\\academy-awards-table-optimized',
            'state' => 'weak',
            'note'  => __( 'This root-level copy is stale; do not edit it for live work.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_get_source_control_status() {
    return array(
        array(
            'label' => __( 'Theme repo', 'lunara-film' ),
            'value' => 'main @ 6cdef4d',
            'state' => 'ready',
            'note'  => 'github.com/TheAntagonist2020/lunara-theme-blocks',
        ),
        array(
            'label' => __( 'Core repo', 'lunara-film' ),
            'value' => 'main @ d88777b',
            'state' => 'ready',
            'note'  => 'github.com/TheAntagonist2020/lunara-plugin-core',
        ),
        array(
            'label' => __( 'Oscars repo', 'lunara-film' ),
            'value' => 'main @ 96f9419',
            'state' => 'ready',
            'note'  => 'github.com/TheAntagonist2020/lunara-plugin-oscars-ledger',
        ),
        array(
            'label' => __( 'Dispatch repo', 'lunara-film' ),
            'value' => 'main @ 1e270d6',
            'state' => 'ready',
            'note'  => 'github.com/TheAntagonist2020/lunara-plugin-dispatch',
        ),
        array(
            'label' => __( 'IMDb Guard repo', 'lunara-film' ),
            'value' => 'main @ 75aae10',
            'state' => 'ready',
            'note'  => 'github.com/TheAntagonist2020/lunara-plugin-imdb-guard',
        ),
        array(
            'label' => __( 'AI Classic repo', 'lunara-film' ),
            'value' => 'main @ 1b041f3',
            'state' => 'ready',
            'note'  => 'github.com/TheAntagonist2020/lunara-plugin-ai-assistant-classic',
        ),
    );
}

function lunara_control_desk_get_request_key( $key, $default = '' ) {
    if ( ! isset( $_GET[ $key ] ) ) {
        return $default;
    }

    return sanitize_key( wp_unslash( $_GET[ $key ] ) );
}

function lunara_control_desk_get_request_absint( $key, $default = 0 ) {
    if ( ! isset( $_GET[ $key ] ) ) {
        return absint( $default );
    }

    $value = wp_unslash( $_GET[ $key ] );

    if ( is_array( $value ) ) {
        return absint( $default );
    }

    return absint( $value );
}

function lunara_control_desk_get_queue_posts( $limit = 80 ) {
    $post_types = lunara_control_desk_post_types();

    if ( empty( $post_types ) ) {
        return array();
    }

    $query = new WP_Query(
        array(
            'post_type'              => $post_types,
            'post_status'            => lunara_control_desk_statuses(),
            'posts_per_page'         => absint( $limit ),
            'orderby'                => 'modified',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        )
    );

    return $query->posts;
}

function lunara_control_desk_post_type_label( $post_type ) {
    $object = get_post_type_object( $post_type );

    if ( $object && isset( $object->labels->singular_name ) ) {
        return $object->labels->singular_name;
    }

    return ucfirst( (string) $post_type );
}

function lunara_control_desk_has_text( $value ) {
    return '' !== trim( wp_strip_all_tags( (string) $value ) );
}

function lunara_control_desk_has_excerpt_or_meta( $post_id, $meta_key ) {
    $post = get_post( $post_id );

    if ( $post instanceof WP_Post && lunara_control_desk_has_text( $post->post_excerpt ) ) {
        return true;
    }

    return lunara_control_desk_has_text( get_post_meta( $post_id, $meta_key, true ) );
}

function lunara_control_desk_has_terms( $post_id, $taxonomy ) {
    if ( ! taxonomy_exists( $taxonomy ) ) {
        return false;
    }

    $terms = get_the_terms( $post_id, $taxonomy );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return false;
    }

    if ( 'category' !== $taxonomy ) {
        return true;
    }

    foreach ( $terms as $term ) {
        if ( ! in_array( $term->slug, array( 'uncategorized' ), true ) ) {
            return true;
        }
    }

    return false;
}

function lunara_control_desk_make_signal( $label, $kind = 'needs' ) {
    return array(
        'label' => (string) $label,
        'kind'  => in_array( $kind, array( 'needs', 'weak', 'ready' ), true ) ? $kind : 'needs',
    );
}

function lunara_control_desk_make_review_check( $args ) {
    $defaults = array(
        'key'     => '',
        'label'   => '',
        'group'   => __( 'Packaging', 'lunara-film' ),
        'kind'    => 'needs',
        'value'   => '',
        'note'    => '',
        'owner'   => __( 'Review editor', 'lunara-film' ),
        'fix_url' => '',
    );

    $check         = wp_parse_args( $args, $defaults );
    $check['kind'] = in_array( $check['kind'], array( 'needs', 'weak', 'ready' ), true ) ? $check['kind'] : 'needs';

    return $check;
}

function lunara_control_desk_get_post_excerpt_label( $post_id ) {
    $post = get_post( $post_id );

    if ( $post instanceof WP_Post && lunara_control_desk_has_text( $post->post_excerpt ) ) {
        return __( 'Excerpt set', 'lunara-film' );
    }

    return __( 'No excerpt', 'lunara-film' );
}

function lunara_control_desk_attachment_ratio_summary( $attachment_id, $min = 0.58, $max = 0.74 ) {
    $attachment_id = absint( $attachment_id );

    if ( ! $attachment_id ) {
        return array(
            'kind'  => 'weak',
            'value' => __( 'No attachment ID', 'lunara-film' ),
            'note'  => __( 'Dimensions could not be checked.', 'lunara-film' ),
        );
    }

    $meta = wp_get_attachment_metadata( $attachment_id );
    if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) {
        return array(
            'kind'  => 'weak',
            'value' => __( 'Dimensions unknown', 'lunara-film' ),
            'note'  => __( 'Regenerate metadata or verify the crop in Media Library.', 'lunara-film' ),
        );
    }

    $width  = max( 1, intval( $meta['width'] ) );
    $height = max( 1, intval( $meta['height'] ) );
    $ratio  = $width / $height;
    $value  = sprintf(
        /* translators: 1: image width, 2: image height. */
        __( '%1$d x %2$d', 'lunara-film' ),
        $width,
        $height
    );

    if ( $ratio < $min || $ratio > $max ) {
        return array(
            'kind'  => 'weak',
            'value' => $value,
            'note'  => __( 'Crop is not close to the 2:3 card target.', 'lunara-film' ),
        );
    }

    return array(
        'kind'  => 'ready',
        'value' => $value,
        'note'  => __( 'Poster/card ratio looks safe.', 'lunara-film' ),
    );
}

function lunara_control_desk_url_attachment_id( $url ) {
    $url = trim( (string) $url );

    if ( '' === $url || ! function_exists( 'attachment_url_to_postid' ) ) {
        return 0;
    }

    return absint( attachment_url_to_postid( $url ) );
}

function lunara_control_desk_review_image_dimensions_label( $attachment_id ) {
    $attachment_id = absint( $attachment_id );

    if ( ! $attachment_id ) {
        return '';
    }

    $meta = wp_get_attachment_metadata( $attachment_id );
    if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) {
        return __( 'Dimensions unknown', 'lunara-film' );
    }

    return sprintf(
        /* translators: 1: image width, 2: image height. */
        __( '%1$d x %2$d', 'lunara-film' ),
        absint( $meta['width'] ),
        absint( $meta['height'] )
    );
}

function lunara_control_desk_review_image_thumb_from_attachment( $attachment_id, $label, $source, $state = 'ready' ) {
    $attachment_id = absint( $attachment_id );
    $src           = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';

    if ( ! $src && $attachment_id ) {
        $src = wp_get_attachment_image_url( $attachment_id, 'medium' );
    }

    return array(
        'label'      => $label,
        'source'     => $source,
        'state'      => in_array( $state, array( 'needs', 'weak', 'ready' ), true ) ? $state : 'ready',
        'src'        => $src ? $src : '',
        'value'      => $attachment_id ? sprintf( __( 'Attachment #%d', 'lunara-film' ), $attachment_id ) : __( 'Missing', 'lunara-film' ),
        'dimensions' => lunara_control_desk_review_image_dimensions_label( $attachment_id ),
    );
}

function lunara_control_desk_review_image_thumb_from_url( $url, $label, $source, $state = 'ready' ) {
    $url           = trim( (string) $url );
    $attachment_id = lunara_control_desk_url_attachment_id( $url );

    if ( $attachment_id ) {
        return lunara_control_desk_review_image_thumb_from_attachment( $attachment_id, $label, $source, $state );
    }

    return array(
        'label'      => $label,
        'source'     => $source,
        'state'      => in_array( $state, array( 'needs', 'weak', 'ready' ), true ) ? $state : 'ready',
        'src'        => $url,
        'value'      => $url ? __( 'External URL', 'lunara-film' ) : __( 'Missing', 'lunara-film' ),
        'dimensions' => $url ? __( 'External image', 'lunara-film' ) : '',
    );
}

function lunara_control_desk_get_review_thumbnail_items( $post_id ) {
    $featured_id = get_post_thumbnail_id( $post_id );
    $card_url    = trim( (string) get_post_meta( $post_id, '_lunara_review_card_image', true ) );
    $hero_url    = trim( (string) get_post_meta( $post_id, '_lunara_review_hero_banner', true ) );
    $items       = array();

    if ( '' !== $card_url ) {
        $items[] = lunara_control_desk_review_image_thumb_from_url( $card_url, __( 'Card', 'lunara-film' ), __( 'Override', 'lunara-film' ), 'ready' );
    } elseif ( $featured_id ) {
        $items[] = lunara_control_desk_review_image_thumb_from_attachment( $featured_id, __( 'Card', 'lunara-film' ), __( 'Featured fallback', 'lunara-film' ), 'weak' );
    } else {
        $items[] = array(
            'label'      => __( 'Card', 'lunara-film' ),
            'source'     => __( 'Missing', 'lunara-film' ),
            'state'      => 'needs',
            'src'        => '',
            'value'      => __( 'No image', 'lunara-film' ),
            'dimensions' => '',
        );
    }

    if ( $featured_id ) {
        $items[] = lunara_control_desk_review_image_thumb_from_attachment( $featured_id, __( 'Featured', 'lunara-film' ), __( 'WordPress featured image', 'lunara-film' ), 'ready' );
    } else {
        $items[] = array(
            'label'      => __( 'Featured', 'lunara-film' ),
            'source'     => __( 'Missing', 'lunara-film' ),
            'state'      => 'needs',
            'src'        => '',
            'value'      => __( 'No featured image', 'lunara-film' ),
            'dimensions' => '',
        );
    }

    if ( '' !== $hero_url ) {
        $items[] = lunara_control_desk_review_image_thumb_from_url( $hero_url, __( 'Hero', 'lunara-film' ), __( 'Hero banner', 'lunara-film' ), 'ready' );
    } elseif ( $featured_id ) {
        $items[] = lunara_control_desk_review_image_thumb_from_attachment( $featured_id, __( 'Hero', 'lunara-film' ), __( 'Featured fallback', 'lunara-film' ), 'weak' );
    } else {
        $items[] = array(
            'label'      => __( 'Hero', 'lunara-film' ),
            'source'     => __( 'Missing', 'lunara-film' ),
            'state'      => 'needs',
            'src'        => '',
            'value'      => __( 'No hero image', 'lunara-film' ),
            'dimensions' => '',
        );
    }

    return $items;
}

function lunara_control_desk_review_editor_link( $post_id, $meta_box = '' ) {
    $url = get_edit_post_link( $post_id, 'raw' );

    if ( $url && '' !== $meta_box ) {
        $url .= '#' . sanitize_html_class( $meta_box );
    }

    return $url;
}

function lunara_control_desk_get_review_pipeline_checks( $post_id ) {
    $post             = get_post( $post_id );
    $edit_editorial   = lunara_control_desk_review_editor_link( $post_id, 'lunara_review_editorial_controls' );
    $edit_details     = lunara_control_desk_review_editor_link( $post_id, 'lunara_review_details_meta' );
    $edit_featured    = lunara_control_desk_review_editor_link( $post_id, 'postimagediv' );
    $standfirst       = get_post_meta( $post_id, '_lunara_review_standfirst', true );
    $pull_quote       = get_post_meta( $post_id, '_lunara_pull_quote', true );
    $legacy_quote     = get_post_meta( $post_id, '_lunara_review_pull_quote', true );
    $score            = get_post_meta( $post_id, '_lunara_score', true );
    $card_image       = trim( (string) get_post_meta( $post_id, '_lunara_review_card_image', true ) );
    $hero_banner      = trim( (string) get_post_meta( $post_id, '_lunara_review_hero_banner', true ) );
    $featured_id      = get_post_thumbnail_id( $post_id );
    $featured_block   = $featured_id && function_exists( 'lunara_featured_image_guard_block_reason' ) ? lunara_featured_image_guard_block_reason( $featured_id ) : '';
    $featured_summary = $featured_id ? lunara_control_desk_attachment_ratio_summary( $featured_id ) : array();
    $home_hero        = '1' === get_post_meta( $post_id, '_lunara_review_home_hero_featured', true );
    $home_shelf       = '1' === get_post_meta( $post_id, '_lunara_review_home_featured_shelf', true );
    $raw_imdb         = trim( (string) get_post_meta( $post_id, '_lunara_imdb_title_id', true ) );
    $normalized_imdb  = function_exists( 'lunara_get_review_imdb_title_id' ) ? lunara_get_review_imdb_title_id( $post_id ) : $raw_imdb;
    $has_director     = lunara_control_desk_has_text( get_post_meta( $post_id, '_lunara_director', true ) ) || lunara_control_desk_has_terms( $post_id, 'lunara_director' );
    $has_year         = lunara_control_desk_has_text( get_post_meta( $post_id, '_lunara_year', true ) ) || lunara_control_desk_has_terms( $post_id, 'lunara_review_year' );
    $checks           = array();

    $has_excerpt_or_standfirst = $post instanceof WP_Post && (
        lunara_control_desk_has_text( $post->post_excerpt ) ||
        lunara_control_desk_has_text( $standfirst )
    );

    $checks[] = lunara_control_desk_make_review_check(
        array(
            'key'     => 'excerpt_standfirst',
            'label'   => __( 'Excerpt / standfirst', 'lunara-film' ),
            'group'   => __( 'Copy', 'lunara-film' ),
            'kind'    => $has_excerpt_or_standfirst ? ( '1' === get_post_meta( $post_id, '_lunara_review_hide_standfirst', true ) ? 'weak' : 'ready' ) : 'needs',
            'value'   => lunara_control_desk_has_text( $standfirst ) ? __( 'Standfirst override set', 'lunara-film' ) : lunara_control_desk_get_post_excerpt_label( $post_id ),
            'note'    => $has_excerpt_or_standfirst ? __( 'Card and hero copy have a controlled source.', 'lunara-film' ) : __( 'Add an excerpt or the Lunara standfirst override.', 'lunara-film' ),
            'owner'   => __( 'Excerpt box / Lunara Review Controls', 'lunara-film' ),
            'fix_url' => $edit_editorial,
        )
    );

    $pull_quote_text  = lunara_control_desk_has_text( $pull_quote ) ? $pull_quote : $legacy_quote;
    $pull_quote_words = 0;

    if ( lunara_control_desk_has_text( $pull_quote_text ) ) {
        $pull_quote_clean = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $pull_quote_text ) ) );
        $pull_quote_words = '' === $pull_quote_clean ? 0 : count( preg_split( '/\s+/', $pull_quote_clean ) );
    }

    $has_pull_quote = $pull_quote_words > 0;
    $pull_quote_kind = $has_pull_quote ? ( $pull_quote_words >= 38 ? 'ready' : 'weak' ) : 'needs';
    $checks[]       = lunara_control_desk_make_review_check(
        array(
            'key'     => 'pull_quote',
            'label'   => __( 'Card pull quote', 'lunara-film' ),
            'group'   => __( 'Copy', 'lunara-film' ),
            'kind'    => $pull_quote_kind,
            'value'   => $has_pull_quote ? sprintf( __( '%d chosen words', 'lunara-film' ), $pull_quote_words ) : __( 'Missing', 'lunara-film' ),
            'note'    => $has_pull_quote ? __( 'Archive/home cards use this exact chosen quote before any fallback copy.', 'lunara-film' ) : __( 'Add the exact gold-card hook. Aim for 40-55 words.', 'lunara-film' ),
            'owner'   => __( 'Lunara Review Controls', 'lunara-film' ),
            'fix_url' => $edit_editorial,
        )
    );

    $checks[] = lunara_control_desk_make_review_check(
        array(
            'key'     => 'score',
            'label'   => __( 'Review score', 'lunara-film' ),
            'group'   => __( 'Details', 'lunara-film' ),
            'kind'    => lunara_control_desk_has_text( $score ) ? 'ready' : 'needs',
            'value'   => lunara_control_desk_has_text( $score ) ? (string) $score : __( 'Missing', 'lunara-film' ),
            'note'    => lunara_control_desk_has_text( $score ) ? __( 'Score badge can render.', 'lunara-film' ) : __( 'Set the score used by review cards and schema.', 'lunara-film' ),
            'owner'   => __( 'Review Details', 'lunara-film' ),
            'fix_url' => $edit_details,
        )
    );

    $checks[] = lunara_control_desk_make_review_check(
        array(
            'key'     => 'featured_image',
            'label'   => __( 'Featured image', 'lunara-film' ),
            'group'   => __( 'Media', 'lunara-film' ),
            'kind'    => $featured_block ? 'needs' : ( $featured_id ? 'ready' : 'needs' ),
            'value'   => $featured_block ? sprintf( __( 'Blocked attachment #%d', 'lunara-film' ), $featured_id ) : ( $featured_id ? sprintf( __( 'Attachment #%d', 'lunara-film' ), $featured_id ) : __( 'Missing', 'lunara-film' ) ),
                'note'    => $featured_block ? sprintf( __( 'Replace this blocked image before publishing. The publish guard will keep the post in draft. %s', 'lunara-film' ), $featured_block ) : ( $featured_id ? __( 'Primary image source exists.', 'lunara-film' ) : __( 'Add the standard WordPress featured image.', 'lunara-film' ) ),
            'owner'   => __( 'Featured image box', 'lunara-film' ),
            'fix_url' => $edit_featured,
        )
    );

    if ( lunara_control_desk_has_text( $card_image ) ) {
        $card_attachment = lunara_control_desk_url_attachment_id( $card_image );
        $card_summary    = $card_attachment ? lunara_control_desk_attachment_ratio_summary( $card_attachment ) : array(
            'kind'  => 'weak',
            'value' => __( 'Override URL set', 'lunara-film' ),
            'note'  => __( 'Attachment dimensions were not available from the URL.', 'lunara-film' ),
        );
    } elseif ( $featured_id ) {
        $card_summary = array(
            'kind'  => isset( $featured_summary['kind'] ) ? $featured_summary['kind'] : 'weak',
            'value' => isset( $featured_summary['value'] ) ? $featured_summary['value'] : __( 'Featured fallback', 'lunara-film' ),
            'note'  => __( 'Using the featured image fallback; add a card override for exact 2:3 control.', 'lunara-film' ),
        );
    } else {
        $card_summary = array(
            'kind'  => 'needs',
            'value' => __( 'Missing', 'lunara-film' ),
            'note'  => __( 'Add a featured image or a card image override.', 'lunara-film' ),
        );
    }

    $checks[] = lunara_control_desk_make_review_check(
        array(
            'key'     => 'card_image',
            'label'   => __( '2:3 card image', 'lunara-film' ),
            'group'   => __( 'Media', 'lunara-film' ),
            'kind'    => $card_summary['kind'],
            'value'   => $card_summary['value'],
            'note'    => $card_summary['note'],
            'owner'   => __( 'Lunara Review Controls', 'lunara-film' ),
            'fix_url' => $edit_editorial,
        )
    );

    $checks[] = lunara_control_desk_make_review_check(
        array(
            'key'     => 'hero_visual',
            'label'   => __( 'Hero/banner visual', 'lunara-film' ),
            'group'   => __( 'Media', 'lunara-film' ),
            'kind'    => lunara_control_desk_has_text( $hero_banner ) ? 'ready' : ( $featured_id ? 'weak' : 'needs' ),
            'value'   => lunara_control_desk_has_text( $hero_banner ) ? __( 'Hero banner set', 'lunara-film' ) : ( $featured_id ? __( 'Featured fallback', 'lunara-film' ) : __( 'Missing', 'lunara-film' ) ),
            'note'    => lunara_control_desk_has_text( $hero_banner ) ? __( 'Single-review hero has a controlled source.', 'lunara-film' ) : ( $featured_id ? __( 'Single hero will use the featured image fallback.', 'lunara-film' ) : __( 'Add a hero banner or featured image.', 'lunara-film' ) ),
            'owner'   => __( 'Lunara Review Controls', 'lunara-film' ),
            'fix_url' => $edit_editorial,
        )
    );

    $checks[] = lunara_control_desk_make_review_check(
        array(
            'key'     => 'director',
            'label'   => __( 'Director', 'lunara-film' ),
            'group'   => __( 'Details', 'lunara-film' ),
            'kind'    => $has_director ? 'ready' : 'needs',
            'value'   => $has_director ? __( 'Set', 'lunara-film' ) : __( 'Missing', 'lunara-film' ),
            'note'    => $has_director ? __( 'Director taxonomy/meta can render.', 'lunara-film' ) : __( 'Add director for review detail and filtering.', 'lunara-film' ),
            'owner'   => __( 'Review Details', 'lunara-film' ),
            'fix_url' => $edit_details,
        )
    );

    $checks[] = lunara_control_desk_make_review_check(
        array(
            'key'     => 'year',
            'label'   => __( 'Year', 'lunara-film' ),
            'group'   => __( 'Details', 'lunara-film' ),
            'kind'    => $has_year ? 'ready' : 'needs',
            'value'   => $has_year ? __( 'Set', 'lunara-film' ) : __( 'Missing', 'lunara-film' ),
            'note'    => $has_year ? __( 'Year metadata can render.', 'lunara-film' ) : __( 'Add year for review detail and filtering.', 'lunara-film' ),
            'owner'   => __( 'Review Details', 'lunara-film' ),
            'fix_url' => $edit_details,
        )
    );

    $checks[] = lunara_control_desk_make_review_check(
        array(
            'key'     => 'tags',
            'label'   => __( 'Tags', 'lunara-film' ),
            'group'   => __( 'Details', 'lunara-film' ),
            'kind'    => lunara_control_desk_has_terms( $post_id, 'post_tag' ) ? 'ready' : 'weak',
            'value'   => lunara_control_desk_has_terms( $post_id, 'post_tag' ) ? __( 'Set', 'lunara-film' ) : __( 'Missing', 'lunara-film' ),
            'note'    => lunara_control_desk_has_terms( $post_id, 'post_tag' ) ? __( 'Tag basics are present.', 'lunara-film' ) : __( 'Add a few useful search/archive tags.', 'lunara-film' ),
            'owner'   => __( 'Tags box', 'lunara-film' ),
            'fix_url' => lunara_control_desk_review_editor_link( $post_id, 'tagsdiv-post_tag' ),
        )
    );

    $checks[] = lunara_control_desk_make_review_check(
        array(
            'key'     => 'imdb_title_id',
            'label'   => __( 'IMDb title ID', 'lunara-film' ),
            'group'   => __( 'Ledger', 'lunara-film' ),
            'kind'    => '' === $raw_imdb ? 'needs' : ( preg_match( '/^tt\d{7,9}$/', trim( (string) $normalized_imdb ) ) ? 'ready' : 'needs' ),
            'value'   => '' !== $raw_imdb ? $raw_imdb : __( 'Missing', 'lunara-film' ),
            'note'    => '' === $raw_imdb ? __( 'Add a tt1234567-style title ID for Ledger linking.', 'lunara-film' ) : __( 'Format should stay normalized for Oscars/entity links.', 'lunara-film' ),
            'owner'   => __( 'Review details / IMDb field', 'lunara-film' ),
            'fix_url' => $edit_details,
        )
    );

    $checks[] = lunara_control_desk_make_review_check(
        array(
            'key'     => 'homepage_flags',
            'label'   => __( 'Homepage flags', 'lunara-film' ),
            'group'   => __( 'Homepage', 'lunara-film' ),
            'kind'    => ( $home_hero || $home_shelf ) ? 'ready' : 'weak',
            'value'   => $home_hero && $home_shelf ? __( 'Hero + shelf', 'lunara-film' ) : ( $home_hero ? __( 'Hero', 'lunara-film' ) : ( $home_shelf ? __( 'Shelf', 'lunara-film' ) : __( 'Not flagged', 'lunara-film' ) ) ),
            'note'    => ( $home_hero || $home_shelf ) ? __( 'Review is eligible for a homepage lane.', 'lunara-film' ) : __( 'Leave unflagged if this should not be pushed to home.', 'lunara-film' ),
            'owner'   => __( 'Lunara Review Controls', 'lunara-film' ),
            'fix_url' => $edit_editorial,
        )
    );

    return $checks;
}

function lunara_control_desk_review_checks_to_signals( $checks ) {
    $signals = array();

    foreach ( $checks as $check ) {
        if ( ! isset( $check['kind'] ) || 'ready' === $check['kind'] ) {
            continue;
        }

        $signals[] = lunara_control_desk_make_signal(
            isset( $check['label'] ) ? $check['label'] : '',
            $check['kind']
        );
    }

    if ( empty( $signals ) ) {
        $signals[] = lunara_control_desk_make_signal( __( 'Review packaging ready', 'lunara-film' ), 'ready' );
    }

    return $signals;
}

function lunara_control_desk_get_review_signals( $post_id ) {
    return lunara_control_desk_review_checks_to_signals( lunara_control_desk_get_review_pipeline_checks( $post_id ) );
}

function lunara_control_desk_get_editorial_signals( $post_id, $post_type ) {
    $signals = array();
    $thumbnail_id = get_post_thumbnail_id( $post_id );
    $thumbnail_block = $thumbnail_id && function_exists( 'lunara_featured_image_guard_block_reason' ) ? lunara_featured_image_guard_block_reason( $thumbnail_id ) : '';
    $thumbnail_subject_mismatch = $thumbnail_id && function_exists( 'lunara_featured_image_guard_subject_mismatch_reason' )
        ? lunara_featured_image_guard_subject_mismatch_reason( $post_id, $thumbnail_id )
        : '';

    if ( ! lunara_control_desk_has_excerpt_or_meta( $post_id, '_lunara_post_standfirst' ) ) {
        $signals[] = lunara_control_desk_make_signal( __( 'Add excerpt or standfirst', 'lunara-film' ) );
    }

    if ( $thumbnail_block ) {
        $signals[] = lunara_control_desk_make_signal( __( 'Replace blocked featured image', 'lunara-film' ) );
    } elseif ( ! $thumbnail_id ) {
        $signals[] = lunara_control_desk_make_signal( __( 'Add featured image', 'lunara-film' ) );
    }

    if (
        '1' !== get_post_meta( $post_id, '_lunara_post_hide_hero_media', true ) &&
        ! $thumbnail_id &&
        ! lunara_control_desk_has_text( get_post_meta( $post_id, '_lunara_post_hero_image_url', true ) )
    ) {
        $signals[] = lunara_control_desk_make_signal( __( 'Add hero image or hide hero media', 'lunara-film' ) );
    }

    if ( 'journal' === $post_type ) {
        if ( ! lunara_control_desk_has_terms( $post_id, 'journal_type' ) ) {
            $signals[] = lunara_control_desk_make_signal( __( 'Choose journal type', 'lunara-film' ) );
        }
    } elseif ( ! lunara_control_desk_has_terms( $post_id, 'category' ) ) {
        $signals[] = lunara_control_desk_make_signal( __( 'Choose category', 'lunara-film' ) );
    }

    if ( ! lunara_control_desk_has_terms( $post_id, 'post_tag' ) ) {
        $signals[] = lunara_control_desk_make_signal( __( 'Add tags', 'lunara-film' ), 'weak' );
    }

    if ( 'journal' === $post_type && '1' !== get_post_meta( $post_id, '_lunara_journal_featured', true ) ) {
        $signals[] = lunara_control_desk_make_signal( __( 'Not journal-featured', 'lunara-film' ), 'weak' );
    }

    if ( 'journal' === $post_type ) {
        $journal_checks = lunara_control_desk_get_journal_growth_checks( $post_id );
        foreach ( $journal_checks as $check ) {
            if ( empty( $check['signal'] ) ) {
                continue;
            }

            $signals[] = lunara_control_desk_make_signal( $check['signal'], $check['state'] );
        }
    }

    return $signals;
}

function lunara_control_desk_journal_tokenize( $text ) {
    $text = strtolower( wp_strip_all_tags( (string) $text ) );
    $parts = preg_split( '/[^a-z0-9]+/', $text, -1, PREG_SPLIT_NO_EMPTY );

    if ( empty( $parts ) || ! is_array( $parts ) ) {
        return array();
    }

    $stopwords = array(
        'about', 'after', 'again', 'also', 'and', 'are', 'because', 'been', 'but', 'can',
        'film', 'films', 'from', 'has', 'have', 'into', 'its', 'journal', 'like', 'movie',
        'movies', 'new', 'news', 'not', 'now', 'one', 'only', 'over', 'post', 'press',
        'says', 'that', 'the', 'their', 'them', 'this', 'through', 'was', 'when', 'where',
        'which', 'while', 'who', 'will', 'with', 'would',
    );

    $tokens = array();
    foreach ( $parts as $part ) {
        $part = trim( (string) $part );
        if ( strlen( $part ) < 4 || in_array( $part, $stopwords, true ) ) {
            continue;
        }
        $tokens[ $part ] = true;
    }

    return array_keys( $tokens );
}

function lunara_control_desk_journal_similarity_score( $a, $b ) {
    $a = lunara_control_desk_journal_tokenize( $a );
    $b = lunara_control_desk_journal_tokenize( $b );

    if ( empty( $a ) || empty( $b ) ) {
        return 0;
    }

    $shared = count( array_intersect( $a, $b ) );
    $base   = max( 1, min( count( $a ), count( $b ) ) );

    return $shared / $base;
}

function lunara_control_desk_get_journal_duplicate_risk( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post instanceof WP_Post ) {
        return array();
    }

    $query = new WP_Query(
        array(
            'post_type'              => 'journal',
            'post_status'            => array( 'publish', 'draft', 'pending', 'future', 'private' ),
            'posts_per_page'         => 30,
            'post__not_in'           => array( absint( $post_id ) ),
            'orderby'                => 'modified',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        )
    );

    $candidate_text = $post->post_title . ' ' . wp_trim_words( wp_strip_all_tags( $post->post_content ), 70, '' );

    foreach ( $query->posts as $other ) {
        if ( ! $other instanceof WP_Post ) {
            continue;
        }

        $other_text = $other->post_title . ' ' . wp_trim_words( wp_strip_all_tags( $other->post_content ), 70, '' );
        $score      = lunara_control_desk_journal_similarity_score( $candidate_text, $other_text );

        if ( $score >= 0.58 ) {
            return array(
                'post_id' => $other->ID,
                'title'   => get_the_title( $other ),
                'score'   => $score,
                'url'     => get_edit_post_link( $other->ID, 'raw' ),
            );
        }
    }

    return array();
}

function lunara_control_desk_journal_has_source_signal( $post_id, $text ) {
    $source_meta_keys = array(
        '_lunara_source_url',
        '_lunara_source_name',
        '_lunara_journal_source_url',
        '_lunara_journal_source_name',
        '_lunara_dispatch_source_url',
        '_lunara_dispatch_source_label',
    );

    foreach ( $source_meta_keys as $meta_key ) {
        if ( lunara_control_desk_has_text( get_post_meta( $post_id, $meta_key, true ) ) ) {
            return true;
        }
    }

    $post = get_post( $post_id );
    if ( $post instanceof WP_Post && preg_match( '/href=["\']https?:\/\/(?!lunarafilm\.com)/i', (string) $post->post_content ) ) {
        return true;
    }

    return (bool) preg_match( '/\b(Deadline|Variety|Hollywood Reporter|IndieWire|Screen Daily|The Wrap|Collider|Empire|Vanity Fair|New York Times|Los Angeles Times|World of Reel)\b/i', $text );
}

function lunara_control_desk_journal_source_summary( $post_id, $text ) {
    $source_meta_keys = array(
        '_lunara_source_name',
        '_lunara_journal_source_name',
        '_lunara_dispatch_source_label',
        '_lunara_source_url',
        '_lunara_journal_source_url',
        '_lunara_dispatch_source_url',
    );

    foreach ( $source_meta_keys as $meta_key ) {
        $value = get_post_meta( $post_id, $meta_key, true );
        if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
            return esc_url_raw( (string) $value ) === (string) $value ? wp_parse_url( (string) $value, PHP_URL_HOST ) : sanitize_text_field( (string) $value );
        }
    }

    $post = get_post( $post_id );
    if ( $post instanceof WP_Post && preg_match( '/href=["\'](https?:\/\/([^\/"\']+))/i', (string) $post->post_content, $matches ) ) {
        return ! empty( $matches[2] ) ? sanitize_text_field( $matches[2] ) : __( 'Inline source link', 'lunara-film' );
    }

    if ( preg_match( '/\b(Deadline|Variety|Hollywood Reporter|IndieWire|Screen Daily|The Wrap|Collider|Empire|Vanity Fair|New York Times|Los Angeles Times|World of Reel|Entertainment Weekly)\b/i', $text, $matches ) ) {
        return sanitize_text_field( $matches[1] );
    }

    return '';
}

function lunara_control_desk_journal_source_risk_details( $post_id, $text ) {
    $source_meta_keys = array(
        '_lunara_source_url',
        '_lunara_source_name',
        '_lunara_journal_source_url',
        '_lunara_journal_source_name',
        '_lunara_dispatch_source_url',
        '_lunara_dispatch_source_label',
    );

    $haystack = (string) $text;
    foreach ( $source_meta_keys as $meta_key ) {
        $value = get_post_meta( $post_id, $meta_key, true );
        if ( is_scalar( $value ) && '' !== (string) $value ) {
            $haystack .= "\n" . (string) $value;
        }
    }

    $post = get_post( $post_id );
    if ( $post instanceof WP_Post ) {
        $haystack .= "\n" . (string) $post->post_content;
    }

    if ( preg_match( '/(?:worldofreel\.com|\bworld\s+of\s+reel\b)/i', $haystack ) ) {
        return array(
            'label' => __( 'World of Reel', 'lunara-film' ),
            'note'  => __( 'Use as a fast lead only: verify the facts, attribute when dependent, add a distinct Lunara angle, and do not use its imagery.', 'lunara-film' ),
        );
    }

    return array();
}

function lunara_control_desk_journal_has_originality_signal( $text ) {
    $phrases = array(
        'the point',
        'the signal',
        'the pattern',
        'the evidence',
        'the hook',
        'reads like',
        'looks like',
        'not just',
        'isn\'t just',
        'what separates',
        'what makes',
        'worth rooting for',
        'the risk',
        'the stake',
        'the tension',
        'hook',
        'gatekeeping',
        'race',
        'racism',
        'institutional',
        'strategy',
        'taste',
        'audience',
        'career',
        'trajectory',
    );

    $lower = strtolower( wp_strip_all_tags( (string) $text ) );
    foreach ( $phrases as $phrase ) {
        if ( false !== strpos( $lower, $phrase ) ) {
            return true;
        }
    }

    return false;
}

function lunara_control_desk_get_journal_dek_text( $post ) {
    if ( ! $post instanceof WP_Post ) {
        return '';
    }

    $standfirst = get_post_meta( $post->ID, '_lunara_post_standfirst', true );
    if ( is_scalar( $standfirst ) && '' !== trim( (string) $standfirst ) ) {
        return trim( wp_strip_all_tags( (string) $standfirst ) );
    }

    if ( '' !== trim( (string) $post->post_excerpt ) ) {
        return trim( wp_strip_all_tags( (string) $post->post_excerpt ) );
    }

    return '';
}

function lunara_control_desk_get_journal_card_label_details( $post_id ) {
    $override = trim( (string) get_post_meta( $post_id, '_lunara_journal_kicker', true ) );
    if ( '' !== $override ) {
        return array(
            'value'  => sprintf( __( 'Override: %s', 'lunara-film' ), $override ),
            'state'  => 'ready',
            'signal' => '',
        );
    }

    $terms = get_the_terms( $post_id, 'journal_type' );
    if ( is_array( $terms ) && ! empty( $terms ) ) {
        $names = array_filter( array_map( 'sanitize_text_field', wp_list_pluck( $terms, 'name' ) ) );

        return array(
            'value'  => implode( ', ', $names ),
            'state'  => 'ready',
            'signal' => '',
        );
    }

    $fallback_label = function_exists( 'lunara_get_dispatch_type_label' ) ? lunara_get_dispatch_type_label( $post_id ) : __( 'Journal', 'lunara-film' );

    return array(
        'value'  => sprintf( __( 'Fallback: %s', 'lunara-film' ), $fallback_label ),
        'state'  => 'needs',
        'signal' => __( 'Choose Journal type or kicker', 'lunara-film' ),
    );
}

function lunara_control_desk_get_journal_image_credit_summary( $post_id, $attachment_id ) {
    $post_keys = array(
        '_lunara_featured_image_credit',
        '_lunara_featured_image_source_name',
        '_lunara_source_name',
        '_lunara_image_source_name',
        '_lunara_dispatch_source_label',
    );

    foreach ( $post_keys as $meta_key ) {
        $value = get_post_meta( $post_id, $meta_key, true );
        if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
            return sanitize_text_field( (string) $value );
        }
    }

    if ( $attachment_id ) {
        $attachment_keys = array(
            '_lunara_image_credit',
            '_lunara_image_source_name',
            '_lunara_dispatch_source_label',
            '_wp_attachment_image_alt',
        );

        foreach ( $attachment_keys as $meta_key ) {
            $value = get_post_meta( $attachment_id, $meta_key, true );
            if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
                return sanitize_text_field( (string) $value );
            }
        }
    }

    return '';
}

function lunara_control_desk_get_journal_visual_provenance_details( $post_id, $attachment_id ) {
    $post_id       = absint( $post_id );
    $attachment_id = absint( $attachment_id );
    $read_meta     = static function ( $object_id, array $keys ) {
        foreach ( $keys as $key ) {
            $value = get_post_meta( $object_id, $key, true );
            if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
                return trim( (string) $value );
            }
        }

        return '';
    };

    $credit = $read_meta(
        $post_id,
        array(
            '_lunara_featured_image_credit',
            '_lunara_image_credit',
        )
    );
    if ( '' === $credit && $attachment_id ) {
        $credit = $read_meta(
            $attachment_id,
            array(
                '_lunara_image_credit',
                '_lunara_dispatch_image_credit',
            )
        );
    }

    $source_name = $read_meta(
        $post_id,
        array(
            '_lunara_featured_image_source_name',
            '_lunara_source_name',
            '_lunara_journal_source_name',
            '_lunara_dispatch_source_label',
        )
    );
    if ( '' === $source_name && $attachment_id ) {
        $source_name = $read_meta(
            $attachment_id,
            array(
                '_lunara_image_source_name',
                '_lunara_dispatch_source_label',
            )
        );
    }

    $source_url = $read_meta(
        $post_id,
        array(
            '_lunara_featured_image_source_url',
            '_lunara_source_url',
            '_lunara_journal_source_url',
            '_lunara_dispatch_source_url',
        )
    );
    if ( '' === $source_url && $attachment_id ) {
        $source_url = $read_meta(
            $attachment_id,
            array(
                '_lunara_image_source_url',
                '_lunara_dispatch_source_url',
            )
        );
    }

    $caption = $attachment_id ? trim( (string) wp_get_attachment_caption( $attachment_id ) ) : '';
    $alt     = $attachment_id ? trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) : '';

    return array(
        'credit'      => sanitize_text_field( $credit ),
        'source_name' => sanitize_text_field( $source_name ),
        'source_url'  => esc_url_raw( $source_url ),
        'caption'     => sanitize_text_field( $caption ),
        'alt'         => sanitize_text_field( $alt ),
    );
}

function lunara_control_desk_get_journal_growth_checks( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post instanceof WP_Post ) {
        return array();
    }

    $title = get_the_title( $post );
    $text  = trim( wp_strip_all_tags( $post->post_title . "\n" . $post->post_excerpt . "\n" . $post->post_content ) );
    $checks = array();

    $title_words = str_word_count( wp_strip_all_tags( $title ) );
    $checks[] = array(
        'label'  => __( 'Headline Pull', 'lunara-film' ),
        'value'  => $title_words >= 5 && $title_words <= 14 ? __( 'Sharp range', 'lunara-film' ) : __( 'Check headline shape', 'lunara-film' ),
        'state'  => $title_words >= 5 && $title_words <= 14 ? 'ready' : 'weak',
        'note'   => __( 'Journal headlines should feel editorial, not feed-parser plain.', 'lunara-film' ),
        'signal' => $title_words >= 5 && $title_words <= 14 ? '' : __( 'Headline needs stronger pull', 'lunara-film' ),
    );

    $dek_text = lunara_control_desk_get_journal_dek_text( $post );
    $dek_len  = strlen( $dek_text );
    $dek_state = $dek_len >= 80 ? 'ready' : ( $dek_len > 0 ? 'weak' : 'needs' );
    $checks[] = array(
        'label'  => __( 'Dek / Standfirst', 'lunara-film' ),
        'value'  => $dek_len ? sprintf( __( '%d characters', 'lunara-film' ), $dek_len ) : __( 'Missing', 'lunara-film' ),
        'state'  => $dek_state,
        'note'   => __( 'Traffic-facing Journal entries need a real reader promise, not just body-copy spillover.', 'lunara-film' ),
        'signal' => 'ready' === $dek_state ? '' : __( 'Strengthen Journal dek/standfirst', 'lunara-film' ),
    );

    $label_details = lunara_control_desk_get_journal_card_label_details( $post_id );
    $checks[] = array(
        'label'  => __( 'Card Label', 'lunara-film' ),
        'value'  => $label_details['value'],
        'state'  => $label_details['state'],
        'note'   => __( 'Archive and homepage cards should say what kind of file this is: News, Reaction, Essay, Trailer, Interview, or a deliberate kicker.', 'lunara-film' ),
        'signal' => $label_details['signal'],
    );

    $thumbnail_id = get_post_thumbnail_id( $post_id );
    $thumbnail_block = $thumbnail_id && function_exists( 'lunara_featured_image_guard_block_reason' ) ? lunara_featured_image_guard_block_reason( $thumbnail_id ) : '';
    $thumbnail_meta  = $thumbnail_id ? wp_get_attachment_metadata( $thumbnail_id ) : array();
    $thumbnail_size  = ! empty( $thumbnail_meta['width'] ) && ! empty( $thumbnail_meta['height'] )
        ? sprintf( '%1$dx%2$d', absint( $thumbnail_meta['width'] ), absint( $thumbnail_meta['height'] ) )
        : '';
    $image_credit    = $thumbnail_id ? lunara_control_desk_get_journal_image_credit_summary( $post_id, $thumbnail_id ) : '';
    $provenance      = $thumbnail_id ? lunara_control_desk_get_journal_visual_provenance_details( $post_id, $thumbnail_id ) : array();
    $has_credit      = ! empty( $provenance['credit'] );
    $has_source_name = ! empty( $provenance['source_name'] );
    $has_source_url  = ! empty( $provenance['source_url'] );
    $has_caption     = ! empty( $provenance['caption'] );
    $has_alt         = ! empty( $provenance['alt'] );
    $image_state     = $thumbnail_block || $thumbnail_subject_mismatch || ! $thumbnail_id ? 'needs' : ( $image_credit ? 'ready' : 'weak' );
    $checks[] = array(
        'label'  => __( 'Image Safety', 'lunara-film' ),
        'value'  => $thumbnail_block || $thumbnail_subject_mismatch ? __( 'Blocked image', 'lunara-film' ) : ( $thumbnail_id ? trim( sprintf( __( 'Attachment #%1$d %2$s', 'lunara-film' ), $thumbnail_id, $thumbnail_size ) ) : __( 'Missing featured image', 'lunara-film' ) ),
        'state'  => $image_state,
        'note'   => $thumbnail_block ? sprintf( __( 'Blocked by the featured-image safety guard: %s', 'lunara-film' ), $thumbnail_block ) : ( $thumbnail_subject_mismatch ? $thumbnail_subject_mismatch : ( $image_credit ? sprintf( __( 'Artwork has visible provenance/credit signal: %s', 'lunara-film' ), $image_credit ) : __( 'Journal traffic needs safe artwork with a clear credit/source signal.', 'lunara-film' ) ) ),
        'signal' => $thumbnail_block || $thumbnail_subject_mismatch ? __( 'Replace blocked featured image', 'lunara-film' ) : ( $thumbnail_id ? ( $image_credit ? '' : __( 'Add image credit/source', 'lunara-film' ) ) : __( 'Add safe featured image', 'lunara-film' ) ),
    );

    $credit_quality = array();
    if ( $has_credit ) {
        $credit_quality[] = __( 'credit', 'lunara-film' );
    }
    if ( $has_source_name ) {
        $credit_quality[] = __( 'source name', 'lunara-film' );
    }
    if ( $has_source_url ) {
        $credit_quality[] = __( 'source URL', 'lunara-film' );
    }
    if ( $has_caption ) {
        $credit_quality[] = __( 'caption', 'lunara-film' );
    }
    if ( $has_alt ) {
        $credit_quality[] = __( 'alt text', 'lunara-film' );
    }

    $visual_provenance_state = $thumbnail_block || ! $thumbnail_id ? 'needs' : ( $has_credit && $has_source_name ? 'ready' : ( $image_credit ? 'weak' : 'needs' ) );
    $checks[] = array(
        'label'  => __( 'Visual Provenance', 'lunara-film' ),
        'value'  => $thumbnail_id ? ( ! empty( $credit_quality ) ? implode( ', ', $credit_quality ) : __( 'Credit/source missing', 'lunara-film' ) ) : __( 'No visual file', 'lunara-film' ),
        'state'  => $visual_provenance_state,
        'note'   => $thumbnail_id && ( $has_credit || $has_source_name ) ? sprintf( __( 'Public credit should read cleanly on the Journal single. Current signal: %s', 'lunara-film' ), $image_credit ) : __( 'Clean Power means the image story must look intentional and credible, with visible provenance when outside artwork is used.', 'lunara-film' ),
        'signal' => 'ready' === $visual_provenance_state ? '' : __( 'Add credible visual provenance', 'lunara-film' ),
    );

    if ( $thumbnail_id && function_exists( 'lunara_featured_image_guard_visual_match_details' ) ) {
        $match_details = lunara_featured_image_guard_visual_match_details( $post_id, $thumbnail_id );
        $shared_terms  = ! empty( $match_details['shared'] ) ? implode( ', ', array_slice( $match_details['shared'], 0, 6 ) ) : '';
        $match_state   = $thumbnail_subject_mismatch ? 'needs' : 'ready';
        $checks[] = array(
            'label'  => __( 'Image Subject Match', 'lunara-film' ),
            'value'  => $thumbnail_subject_mismatch ? __( 'Review required', 'lunara-film' ) : ( $shared_terms ? sprintf( __( 'Shared: %s', 'lunara-film' ), $shared_terms ) : __( 'Manual/editorial image', 'lunara-film' ) ),
            'state'  => $match_state,
            'note'   => $thumbnail_subject_mismatch ? $thumbnail_subject_mismatch : __( 'Dispatch-imported images should share subject terms with the Journal entry; manual images remain an editor judgment call.', 'lunara-film' ),
            'signal' => $thumbnail_subject_mismatch ? __( 'Verify image subject match', 'lunara-film' ) : '',
        );
    }

    $has_original_angle = lunara_control_desk_journal_has_originality_signal( $text );
    $checks[] = array(
        'label'  => __( 'Lunara Angle', 'lunara-film' ),
        'value'  => $has_original_angle ? __( 'Signal found', 'lunara-film' ) : __( 'Needs human read', 'lunara-film' ),
        'state'  => $has_original_angle ? 'ready' : 'needs',
        'note'   => __( 'The entry should add judgment, taste, stakes, or context beyond source summary.', 'lunara-film' ),
        'signal' => $has_original_angle ? '' : __( 'Clarify original Lunara angle', 'lunara-film' ),
    );

    $has_source_signal = lunara_control_desk_journal_has_source_signal( $post_id, $text );
    $source_summary    = lunara_control_desk_journal_source_summary( $post_id, $text );
    $checks[] = array(
        'label'  => __( 'Attribution Pass', 'lunara-film' ),
        'value'  => $has_source_signal ? ( $source_summary ? $source_summary : __( 'Source signal found', 'lunara-film' ) ) : __( 'Check attribution', 'lunara-film' ),
        'state'  => $has_source_signal ? 'ready' : 'weak',
        'note'   => __( 'Avoid derivative feel by naming source reporting when the piece depends on it.', 'lunara-film' ),
        'signal' => $has_source_signal ? '' : __( 'Confirm source attribution', 'lunara-film' ),
    );

    $source_risk = lunara_control_desk_journal_source_risk_details( $post_id, $text );
    $checks[] = array(
        'label'  => __( 'Source Risk', 'lunara-film' ),
        'value'  => ! empty( $source_risk ) ? sprintf( __( '%s fast signal', 'lunara-film' ), $source_risk['label'] ) : __( 'No flagged source', 'lunara-film' ),
        'state'  => ! empty( $source_risk ) ? 'needs' : 'ready',
        'note'   => ! empty( $source_risk ) ? $source_risk['note'] : __( 'No source-risk outlet detected in the current draft metadata/content.', 'lunara-film' ),
        'signal' => ! empty( $source_risk ) ? __( 'Verify source-risk originality and image safety', 'lunara-film' ) : '',
    );

    $carousel_ids     = lunara_control_desk_get_journal_carousel_ids( $post_id );
    $carousel_count   = count( $carousel_ids );
    $visual_hint_text = strtolower( $source_summary . ' ' . $text );
    $visual_hint      = false !== strpos( $visual_hint_text, 'entertainment weekly' )
        || false !== strpos( $visual_hint_text, 'exclusive' )
        || false !== strpos( $visual_hint_text, 'first look' )
        || false !== strpos( $visual_hint_text, 'stills' );
    $carousel_state   = $carousel_count >= 2 ? 'ready' : ( $carousel_count > 0 || $visual_hint ? 'weak' : 'ready' );
    $carousel_value   = $carousel_count
        ? sprintf( _n( '%d image', '%d images', $carousel_count, 'lunara-film' ), $carousel_count )
        : ( $visual_hint ? __( 'Carousel candidate', 'lunara-film' ) : __( 'Single image enough', 'lunara-film' ) );
    $carousel_note    = __( 'No carousel needed unless the piece depends on multiple visual beats.', 'lunara-film' );
    if ( $carousel_count >= 2 ) {
        $carousel_note = __( 'Carousel is set. The single Journal template will render a click-through visual file.', 'lunara-film' );
    } elseif ( 1 === $carousel_count ) {
        $carousel_note = __( 'One carousel image is set. Add another image or clear the carousel if the hero is enough.', 'lunara-film' );
    } elseif ( $visual_hint ) {
        $carousel_note = __( 'This source or angle may have more than one legitimate credited still. Consider using the Visual File Manager.', 'lunara-film' );
    }
    $checks[] = array(
        'label'  => __( 'Visual File Carousel', 'lunara-film' ),
        'value'  => $carousel_value,
        'state'  => $carousel_state,
        'note'   => $carousel_note,
        'signal' => 'ready' === $carousel_state ? '' : __( 'Review Journal carousel fit', 'lunara-film' ),
        'url'    => lunara_control_desk_admin_url(
            array(
                'tab'                     => 'journal-growth',
                'lcd_journal_visual_post' => absint( $post_id ),
            )
        ) . '#lunara-journal-visual-file-manager',
    );

    $duplicate = lunara_control_desk_get_journal_duplicate_risk( $post_id );
    $checks[] = array(
        'label'  => __( 'Duplicate Topic Risk', 'lunara-film' ),
        'value'  => empty( $duplicate ) ? __( 'No close recent match', 'lunara-film' ) : sprintf( __( 'Near %s', 'lunara-film' ), $duplicate['title'] ),
        'state'  => empty( $duplicate ) ? 'ready' : 'weak',
        'note'   => empty( $duplicate ) ? __( 'Recent Journal stack does not show an obvious repeat.', 'lunara-film' ) : __( 'Review before publishing so Journal traffic does not recycle the same topic.', 'lunara-film' ),
        'signal' => empty( $duplicate ) ? '' : __( 'Duplicate-topic risk', 'lunara-film' ),
        'url'    => ! empty( $duplicate['url'] ) ? $duplicate['url'] : '',
    );

    $lead_blockers = array();
    if ( $thumbnail_block || $thumbnail_subject_mismatch || ! $thumbnail_id ) {
        $lead_blockers[] = __( 'safe hero image', 'lunara-film' );
    }
    if ( 'ready' !== $dek_state ) {
        $lead_blockers[] = __( 'dek', 'lunara-film' );
    }
    if ( ! $has_original_angle ) {
        $lead_blockers[] = __( 'angle', 'lunara-film' );
    }
    if ( ! $has_credit || ! $has_source_name ) {
        $lead_blockers[] = __( 'visual provenance', 'lunara-film' );
    }
    if ( ! empty( $source_risk ) ) {
        $lead_blockers[] = __( 'source-risk read', 'lunara-film' );
    }
    if ( ! empty( $duplicate ) ) {
        $lead_blockers[] = __( 'duplicate-topic read', 'lunara-film' );
    }
    if ( $visual_hint && $carousel_count < 2 ) {
        $lead_blockers[] = __( 'carousel decision', 'lunara-film' );
    }

    $lead_state = empty( $lead_blockers ) ? 'ready' : ( count( $lead_blockers ) > 2 ? 'needs' : 'weak' );
    $checks[]   = array(
        'label'  => __( 'Curated Lead Judgment', 'lunara-film' ),
        'value'  => empty( $lead_blockers ) ? __( 'Homepage lead-ready', 'lunara-film' ) : sprintf( __( 'Review %s', 'lunara-film' ), implode( ', ', array_slice( $lead_blockers, 0, 3 ) ) ),
        'state'  => $lead_state,
        'note'   => __( 'A Journal lead should be visually credible, attributed, distinctive, and strong enough to carry the homepage lane without feeling like source-copy with a new coat of paint.', 'lunara-film' ),
        'signal' => empty( $lead_blockers ) ? '' : __( 'Curate before homepage lead', 'lunara-film' ),
        'url'    => lunara_control_desk_admin_url(
            array(
                'tab'                     => 'journal-growth',
                'lcd_journal_visual_post' => absint( $post_id ),
            )
        ),
    );

    $current_lead_id = function_exists( 'lunara_control_desk_get_current_journal_lead_id' ) ? lunara_control_desk_get_current_journal_lead_id() : 0;
    $is_manual_lead  = $current_lead_id && absint( $post_id ) === absint( $current_lead_id );
    $has_home_flag   = '1' === get_post_meta( $post_id, '_lunara_journal_featured', true );
    $checks[] = array(
        'label'  => __( 'Homepage Candidate', 'lunara-film' ),
        'value'  => $is_manual_lead ? __( 'Curated lead', 'lunara-film' ) : ( $has_home_flag ? __( 'Featured', 'lunara-film' ) : __( 'Not flagged', 'lunara-film' ) ),
        'state'  => $is_manual_lead || $has_home_flag ? 'ready' : 'weak',
        'note'   => __( 'High-traffic Journal entries should be considered for the homepage lane.', 'lunara-film' ),
        'signal' => $is_manual_lead || $has_home_flag ? '' : __( 'Consider homepage fit', 'lunara-film' ),
    );

    $opportunities = lunara_control_desk_get_ledger_opportunities( $post );
    $checks[] = array(
        'label'  => __( 'Review / Ledger Links', 'lunara-film' ),
        'value'  => ! empty( $opportunities ) ? sprintf( __( '%d opportunity found', 'lunara-film' ), count( $opportunities ) ) : __( 'No obvious match', 'lunara-film' ),
        'state'  => ! empty( $opportunities ) ? 'ready' : 'weak',
        'note'   => __( 'Traffic should have deeper places to go when a film/person/Oscar hook exists.', 'lunara-film' ),
        'signal' => ! empty( $opportunities ) ? '' : __( 'Check internal link opportunity', 'lunara-film' ),
    );

    return $checks;
}

function lunara_control_desk_get_signals( $post_id, $post_type ) {
    $signals = 'review' === $post_type
        ? lunara_control_desk_get_review_signals( $post_id )
        : lunara_control_desk_get_editorial_signals( $post_id, $post_type );

    if ( empty( $signals ) ) {
        $signals[] = lunara_control_desk_make_signal( __( 'Ready for final read', 'lunara-film' ), 'ready' );
    }

    return $signals;
}

function lunara_control_desk_get_ai_mode_label( $post_type ) {
    if ( 'journal' === $post_type ) {
        return __( 'Journal Packaging', 'lunara-film' );
    }

    if ( 'review' === $post_type ) {
        return __( 'Full Editorial Package or Rewrite Referenced Text', 'lunara-film' );
    }

    return __( 'Full Editorial Package or Audit Current Packaging', 'lunara-film' );
}

function lunara_control_desk_get_default_intent( $post_type ) {
    if ( 'journal' === $post_type ) {
        return 'homepage_pitch';
    }

    if ( 'review' === $post_type ) {
        return 'package';
    }

    return 'readiness';
}

function lunara_control_desk_count_signal_kind( $signals, $kind ) {
    $count = 0;

    foreach ( $signals as $signal ) {
        if ( isset( $signal['kind'] ) && $kind === $signal['kind'] ) {
            $count++;
        }
    }

    return $count;
}

function lunara_control_desk_get_readiness( $signals ) {
    $blockers = lunara_control_desk_count_signal_kind( $signals, 'needs' );
    $warnings = lunara_control_desk_count_signal_kind( $signals, 'weak' );
    $score    = max( 0, 100 - ( $blockers * 22 ) - ( $warnings * 8 ) );

    if ( $blockers > 0 ) {
        $status = 'blocked';
        $label  = __( 'Blockers', 'lunara-film' );
    } elseif ( $warnings > 0 ) {
        $status = 'warnings';
        $label  = __( 'Warnings', 'lunara-film' );
    } else {
        $status = 'ready';
        $label  = __( 'Ready', 'lunara-film' );
    }

    return array(
        'score'    => $score,
        'status'   => $status,
        'label'    => $label,
        'blockers' => $blockers,
        'warnings' => $warnings,
    );
}

function lunara_control_desk_get_rows() {
    $rows = array();

    foreach ( lunara_control_desk_get_queue_posts() as $post ) {
        if ( ! $post instanceof WP_Post ) {
            continue;
        }

        $signals = lunara_control_desk_get_signals( $post->ID, $post->post_type );

        $rows[] = array(
            'post'      => $post,
            'signals'   => $signals,
            'readiness' => lunara_control_desk_get_readiness( $signals ),
        );
    }

    return $rows;
}

function lunara_control_desk_filter_rows( $rows ) {
    $type   = lunara_control_desk_get_request_key( 'lcd_type', 'all' );
    $status = lunara_control_desk_get_request_key( 'lcd_status', 'all' );
    $issue  = lunara_control_desk_get_request_key( 'lcd_issue', 'all' );

    return array_values(
        array_filter(
            $rows,
            static function ( $row ) use ( $type, $status, $issue ) {
                $post      = $row['post'];
                $readiness = $row['readiness'];

                if ( 'all' !== $type && $post->post_type !== $type ) {
                    return false;
                }

                if ( 'all' !== $status && $post->post_status !== $status ) {
                    return false;
                }

                if ( 'blockers' === $issue && intval( $readiness['blockers'] ) <= 0 ) {
                    return false;
                }

                if ( 'warnings' === $issue && ( intval( $readiness['blockers'] ) > 0 || intval( $readiness['warnings'] ) <= 0 ) ) {
                    return false;
                }

                if ( 'ready' === $issue && 'ready' !== $readiness['status'] ) {
                    return false;
                }

                return true;
            }
        )
    );
}

function lunara_control_desk_count_by_type( $rows, $post_type ) {
    $count = 0;

    foreach ( $rows as $row ) {
        if ( isset( $row['post'] ) && $row['post'] instanceof WP_Post && $post_type === $row['post']->post_type ) {
            $count++;
        }
    }

    return $count;
}

function lunara_control_desk_count_needing_attention( $rows ) {
    $count = 0;

    foreach ( $rows as $row ) {
        if ( isset( $row['readiness']['status'] ) && 'ready' !== $row['readiness']['status'] ) {
            $count++;
        }
    }

    return $count;
}

function lunara_control_desk_get_first_row_by_type( $rows, $post_type ) {
    foreach ( $rows as $row ) {
        if ( isset( $row['post'] ) && $row['post'] instanceof WP_Post && $post_type === $row['post']->post_type ) {
            return $row;
        }
    }

    return null;
}

function lunara_control_desk_render_signals( $signals ) {
    echo '<div class="lunara-control-desk-signals">';

    foreach ( $signals as $signal ) {
        $kind  = isset( $signal['kind'] ) ? $signal['kind'] : 'needs';
        $label = isset( $signal['label'] ) ? $signal['label'] : '';

        printf(
            '<span class="lunara-control-desk-signal is-%1$s">%2$s</span>',
            esc_attr( $kind ),
            esc_html( $label )
        );
    }

    echo '</div>';
}

function lunara_control_desk_render_readiness_badge( $readiness ) {
    printf(
        '<span class="lunara-control-desk-readiness is-%1$s"><strong>%2$s</strong><span>%3$s</span></span>',
        esc_attr( $readiness['status'] ),
        esc_html( $readiness['score'] ),
        esc_html( $readiness['label'] )
    );
}

function lunara_control_desk_customizer_url( $section ) {
    return add_query_arg(
        array(
            'autofocus[section]' => sanitize_key( $section ),
        ),
        admin_url( 'customize.php' )
    );
}

function lunara_control_desk_tab_definitions() {
    return array(
        'system-status'     => __( 'System', 'lunara-film' ),
        'operating-plan'    => __( 'Operating Plan', 'lunara-film' ),
        'publishing'        => __( 'Publishing', 'lunara-film' ),
        'journal-growth'    => __( 'Journal Growth', 'lunara-film' ),
        'reviews'           => __( 'Reviews', 'lunara-film' ),
        'theme-studio'      => __( 'Theme Studio', 'lunara-film' ),
        'oscars-integrity'  => __( 'Oscars Integrity', 'lunara-film' ),
        'speed-stability'   => __( 'Speed & Stability', 'lunara-film' ),
        'visual-qa'         => __( 'Visual QA', 'lunara-film' ),
        'ai-operator'       => __( 'AI Operator', 'lunara-film' ),
        'field-suggestions' => __( 'Field Suggestions', 'lunara-film' ),
        'homepage-board'    => __( 'Homepage Board', 'lunara-film' ),
        'readiness'         => __( 'Readiness', 'lunara-film' ),
        'ledger-assistant'  => __( 'Ledger Assistant', 'lunara-film' ),
    );
}

function lunara_control_desk_get_active_tab() {
    $tabs = lunara_control_desk_tab_definitions();
    $tab  = lunara_control_desk_get_request_key( 'tab', 'system-status' );

    if ( 'ai-console' === $tab ) {
        return 'ai-operator';
    }

    return isset( $tabs[ $tab ] ) ? $tab : 'system-status';
}

function lunara_control_desk_url( $args = array() ) {
    return lunara_control_desk_admin_url( $args );
}

function lunara_control_desk_render_tabs( $active_tab ) {
    echo '<nav class="nav-tab-wrapper lunara-control-desk-tabs" aria-label="' . esc_attr__( 'Lunara Control Desk tabs', 'lunara-film' ) . '">';

    foreach ( lunara_control_desk_tab_definitions() as $tab => $label ) {
        printf(
            '<a class="nav-tab %1$s" href="%2$s">%3$s</a>',
            $active_tab === $tab ? 'nav-tab-active' : '',
            esc_url( lunara_control_desk_url( array( 'tab' => $tab ) ) ),
            esc_html( $label )
        );
    }

    echo '</nav>';
}

function lunara_control_desk_get_latest_suggestion( $post_id, $intent = '' ) {
    $snapshots = lunara_control_desk_get_suggestions( $post_id );

    foreach ( $snapshots as $snapshot ) {
        if ( '' === $intent || ( isset( $snapshot['intent'] ) && $intent === $snapshot['intent'] ) ) {
            return $snapshot;
        }
    }

    return array();
}

function lunara_control_desk_get_suggestions( $post_id ) {
    $snapshots = get_post_meta( $post_id, '_lunara_ai_suggestion_snapshots', true );

    if ( ! is_array( $snapshots ) ) {
        return array();
    }

    $clean = array();

    foreach ( $snapshots as $snapshot ) {
        if ( ! is_array( $snapshot ) ) {
            continue;
        }

        $clean[] = $snapshot;
    }

    return array_slice( $clean, 0, 5 );
}

function lunara_control_desk_intent_label( $intent ) {
    $labels = array(
        'package'        => __( 'Packaging', 'lunara-film' ),
        'rewrite'        => __( 'Rewrite', 'lunara-film' ),
        'readiness'      => __( 'Readiness', 'lunara-film' ),
        'homepage_pitch' => __( 'Homepage Pitch', 'lunara-film' ),
        'ledger_links'   => __( 'Ledger', 'lunara-film' ),
    );

    return isset( $labels[ $intent ] ) ? $labels[ $intent ] : ucwords( str_replace( '_', ' ', (string) $intent ) );
}

function lunara_control_desk_get_intent_actions( $post_type ) {
    if ( 'journal' === $post_type ) {
        return array(
            array( 'homepage_pitch', __( 'Homepage Pitch', 'lunara-film' ) ),
            array( 'readiness', __( 'Readiness', 'lunara-film' ) ),
            array( 'ledger_links', __( 'Ledger', 'lunara-film' ) ),
        );
    }

    return array(
        array( 'package', __( 'Packaging', 'lunara-film' ) ),
        array( 'readiness', __( 'Readiness', 'lunara-film' ) ),
        array( 'ledger_links', __( 'Ledger', 'lunara-film' ) ),
    );
}

function lunara_control_desk_suggestion_field_definitions() {
    return array(
        'titles'              => __( 'Titles', 'lunara-film' ),
        'deks'                => __( 'Deks / Standfirsts', 'lunara-film' ),
        'h2s'                 => __( 'H2s', 'lunara-film' ),
        'pullQuotes'          => __( 'Pull Quotes', 'lunara-film' ),
        'socialHooks'         => __( 'Social Hooks', 'lunara-film' ),
        'homepagePitch'       => __( 'Homepage Pitch', 'lunara-film' ),
        'readinessNotes'      => __( 'Readiness Notes', 'lunara-film' ),
        'ledgerOpportunities' => __( 'Ledger Opportunities', 'lunara-film' ),
    );
}

function lunara_control_desk_text_lines( $text ) {
    $text = trim( wp_strip_all_tags( (string) $text ) );

    if ( '' === $text ) {
        return array();
    }

    $lines = preg_split( '/\r\n|\r|\n/', $text );

    return array_values(
        array_filter(
            array_map( 'trim', $lines ),
            'lunara_control_desk_has_text'
        )
    );
}

function lunara_control_desk_suggestion_item_to_lines( $item ) {
    if ( is_array( $item ) ) {
        $parts = array();

        foreach ( array( 'text', 'title', 'label', 'value', 'quote', 'note', 'person', 'film', 'category', 'ceremony', 'reason', 'url' ) as $key ) {
            if ( isset( $item[ $key ] ) && is_scalar( $item[ $key ] ) && lunara_control_desk_has_text( $item[ $key ] ) ) {
                $parts[] = trim( wp_strip_all_tags( (string) $item[ $key ] ) );
            }
        }

        if ( ! empty( $parts ) ) {
            return array( implode( ' - ', array_values( array_unique( $parts ) ) ) );
        }

        return array();
    }

    if ( is_scalar( $item ) ) {
        return lunara_control_desk_text_lines( $item );
    }

    return array();
}

function lunara_control_desk_normalize_suggestion_lines( $value ) {
    $lines = array();

    if ( is_array( $value ) ) {
        foreach ( $value as $item ) {
            $lines = array_merge( $lines, lunara_control_desk_suggestion_item_to_lines( $item ) );
        }
    } else {
        $lines = lunara_control_desk_suggestion_item_to_lines( $value );
    }

    return array_values(
        array_filter(
            $lines,
            'lunara_control_desk_has_text'
        )
    );
}

function lunara_control_desk_get_suggestion_field_groups( $snapshot ) {
    if ( empty( $snapshot['fields'] ) || ! is_array( $snapshot['fields'] ) ) {
        return array();
    }

    $groups = array();

    foreach ( lunara_control_desk_suggestion_field_definitions() as $key => $label ) {
        if ( ! array_key_exists( $key, $snapshot['fields'] ) ) {
            continue;
        }

        $lines = lunara_control_desk_normalize_suggestion_lines( $snapshot['fields'][ $key ] );

        if ( empty( $lines ) ) {
            continue;
        }

        $groups[ $key ] = array(
            'label' => $label,
            'lines' => $lines,
        );
    }

    return $groups;
}

function lunara_control_desk_format_snapshot_time( $created_at ) {
    if ( ! $created_at ) {
        return __( 'Saved snapshot', 'lunara-film' );
    }

    $timestamp = strtotime( (string) $created_at );

    if ( ! $timestamp ) {
        return (string) $created_at;
    }

    return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
}

function lunara_control_desk_render_suggestion_snapshot( $snapshot, $snapshots = array(), $recommended_label = '', $post_id = 0 ) {
    if ( empty( $snapshot ) || ! is_array( $snapshot ) ) {
        echo '<div class="lunara-control-desk-empty lunara-control-desk-suggestion-empty">';
        echo '<p>' . esc_html__( 'No private suggestion saved yet.', 'lunara-film' ) . '</p>';

        if ( $recommended_label ) {
            printf(
                '<p class="lunara-control-desk-subtle">%s</p>',
                esc_html(
                    sprintf(
                        /* translators: %s: recommended suggestion button label. */
                        __( 'Start with %s below, then copy only the fields you want.', 'lunara-film' ),
                        $recommended_label
                    )
                )
            );
        }

        echo '</div>';
        return;
    }

    if ( empty( $snapshots ) ) {
        $snapshots = array( $snapshot );
    }

    $snapshots = array_values(
        array_filter(
            $snapshots,
            static function ( $item ) {
                return is_array( $item );
            }
        )
    );

    $base_id = 'lunara-suggestion-' . absint( $post_id ) . '-';
    ?>
    <div class="lunara-control-desk-suggestion-shell" data-lunara-suggestion-shell>
        <?php if ( count( $snapshots ) > 1 ) : ?>
            <div class="lunara-control-desk-snapshot-history" aria-label="<?php esc_attr_e( 'Suggestion history', 'lunara-film' ); ?>">
                <span><?php esc_html_e( 'History', 'lunara-film' ); ?></span>
                <?php foreach ( $snapshots as $index => $history_snapshot ) : ?>
                    <?php
                    $history_id = $base_id . absint( $index );
                    $label      = sprintf(
                        /* translators: 1: snapshot number, 2: provider, 3: intent label. */
                        __( '#%1$d %2$s %3$s', 'lunara-film' ),
                        absint( $index + 1 ),
                        isset( $history_snapshot['provider'] ) ? strtoupper( (string) $history_snapshot['provider'] ) : '',
                        isset( $history_snapshot['intent'] ) ? lunara_control_desk_intent_label( $history_snapshot['intent'] ) : ''
                    );
                    ?>
                    <button
                        type="button"
                        class="button button-small <?php echo 0 === $index ? 'is-active' : ''; ?>"
                        data-lunara-snapshot-select="#<?php echo esc_attr( $history_id ); ?>"
                    >
                        <?php echo esc_html( $label ); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php foreach ( $snapshots as $index => $history_snapshot ) : ?>
            <?php
            $history_id = $base_id . absint( $index );
            $provider   = isset( $history_snapshot['provider'] ) ? strtoupper( (string) $history_snapshot['provider'] ) : '';
            $intent     = isset( $history_snapshot['intent'] ) ? (string) $history_snapshot['intent'] : '';
            $summary    = isset( $history_snapshot['summary'] ) ? (string) $history_snapshot['summary'] : '';
            $raw        = isset( $history_snapshot['rawText'] ) ? (string) $history_snapshot['rawText'] : '';
            $created    = isset( $history_snapshot['createdAt'] ) ? lunara_control_desk_format_snapshot_time( $history_snapshot['createdAt'] ) : '';
            $groups     = lunara_control_desk_get_suggestion_field_groups( $history_snapshot );
            ?>
            <div
                id="<?php echo esc_attr( $history_id ); ?>"
                class="lunara-control-desk-suggestion"
                data-lunara-snapshot-panel
                <?php echo 0 === $index ? '' : 'hidden'; ?>
            >
                <div class="lunara-control-desk-suggestion-head">
                    <div class="lunara-control-desk-suggestion-meta">
                        <?php if ( $provider ) : ?>
                            <span class="lunara-control-desk-chip"><?php echo esc_html( $provider ); ?></span>
                        <?php endif; ?>
                        <?php if ( $intent ) : ?>
                            <span class="lunara-control-desk-chip"><?php echo esc_html( lunara_control_desk_intent_label( $intent ) ); ?></span>
                        <?php endif; ?>
                        <?php if ( $created ) : ?>
                            <span><?php echo esc_html( $created ); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ( $summary ) : ?>
                        <p><?php echo esc_html( $summary ); ?></p>
                    <?php endif; ?>
                </div>

                <?php if ( ! empty( $groups ) ) : ?>
                    <div class="lunara-control-desk-field-groups">
                        <?php foreach ( $groups as $group ) : ?>
                            <?php $copy_all = implode( "\n", $group['lines'] ); ?>
                            <section class="lunara-control-desk-field-group">
                                <div class="lunara-control-desk-field-group-head">
                                    <h4><?php echo esc_html( $group['label'] ); ?></h4>
                                    <div>
                                        <span><?php echo esc_html( count( $group['lines'] ) ); ?></span>
                                        <button type="button" class="button button-small" data-lunara-copy data-lunara-copy-text="<?php echo esc_attr( $copy_all ); ?>"><?php esc_html_e( 'Copy all', 'lunara-film' ); ?></button>
                                    </div>
                                </div>
                                <ul class="lunara-control-desk-field-list">
                                    <?php foreach ( $group['lines'] as $line ) : ?>
                                        <li class="lunara-control-desk-field-line">
                                            <span data-lunara-copy-source><?php echo esc_html( $line ); ?></span>
                                            <button type="button" class="button button-small" data-lunara-copy><?php esc_html_e( 'Copy', 'lunara-film' ); ?></button>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="lunara-control-desk-empty lunara-control-desk-field-empty">
                        <p><?php esc_html_e( 'No structured fields in this snapshot yet.', 'lunara-film' ); ?></p>
                        <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Use the raw output below if this is an older provider response.', 'lunara-film' ); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ( $raw ) : ?>
                    <details class="lunara-control-desk-raw">
                        <summary><?php esc_html_e( 'Raw output', 'lunara-film' ); ?></summary>
                        <pre data-lunara-copy-source><?php echo esc_html( $raw ); ?></pre>
                        <button type="button" class="button button-small" data-lunara-copy><?php esc_html_e( 'Copy raw', 'lunara-film' ); ?></button>
                    </details>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function lunara_control_desk_render_suggestion_actions( $post ) {
    foreach ( lunara_control_desk_get_intent_actions( $post->post_type ) as $action ) {
        lunara_control_desk_render_suggest_button( $post->ID, $action[0], $action[1] );
    }
}

function lunara_control_desk_get_recommended_intent_label( $post_type ) {
    return lunara_control_desk_intent_label( lunara_control_desk_get_default_intent( $post_type ) );
}

function lunara_control_desk_provider_status( $provider ) {
    $settings = get_option( 'lunara_ai_assistant_classic_settings', array() );
    $settings = is_array( $settings ) ? $settings : array();

    if ( 'openai' === $provider ) {
        if ( ( defined( 'LUNARA_OPENAI_API_KEY' ) && LUNARA_OPENAI_API_KEY ) || getenv( 'OPENAI_API_KEY' ) ) {
            return __( 'Server key detected', 'lunara-film' );
        }

        return ! empty( $settings['api_key'] ) ? __( 'Fallback key saved', 'lunara-film' ) : __( 'Needs key', 'lunara-film' );
    }

    if ( 'anthropic' === $provider ) {
        if ( ( defined( 'LUNARA_ANTHROPIC_API_KEY' ) && LUNARA_ANTHROPIC_API_KEY ) || getenv( 'ANTHROPIC_API_KEY' ) ) {
            return __( 'Server key detected', 'lunara-film' );
        }

        return ! empty( $settings['anthropic_api_key'] ) ? __( 'Fallback key saved', 'lunara-film' ) : __( 'Needs key', 'lunara-film' );
    }

    if ( ( defined( 'LUNARA_GEMINI_API_KEY' ) && LUNARA_GEMINI_API_KEY ) || getenv( 'GEMINI_API_KEY' ) || getenv( 'GOOGLE_API_KEY' ) ) {
        return __( 'Server key detected', 'lunara-film' );
    }

    return ! empty( $settings['gemini_api_key'] ) ? __( 'Fallback key saved', 'lunara-film' ) : __( 'Needs key', 'lunara-film' );
}

function lunara_control_desk_render_suggest_button( $post_id, $intent, $label = '' ) {
    printf(
        '<button type="button" class="button lunara-control-desk-suggest" data-post-id="%1$d" data-intent="%2$s">%3$s</button>',
        absint( $post_id ),
        esc_attr( $intent ),
        esc_html( $label ? $label : __( 'Suggest', 'lunara-film' ) )
    );
}

function lunara_control_desk_render_filters( $active_tab ) {
    $type   = lunara_control_desk_get_request_key( 'lcd_type', 'all' );
    $status = lunara_control_desk_get_request_key( 'lcd_status', 'all' );
    $issue  = lunara_control_desk_get_request_key( 'lcd_issue', 'all' );
    ?>
    <form class="lunara-control-desk-filters" method="get">
        <input type="hidden" name="page" value="lunara-control-desk" />
        <input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>" />
        <label>
            <span><?php esc_html_e( 'Type', 'lunara-film' ); ?></span>
            <select name="lcd_type">
                <option value="all"><?php esc_html_e( 'All', 'lunara-film' ); ?></option>
                <?php foreach ( lunara_control_desk_post_types() as $post_type ) : ?>
                    <option value="<?php echo esc_attr( $post_type ); ?>" <?php selected( $type, $post_type ); ?>><?php echo esc_html( lunara_control_desk_post_type_label( $post_type ) ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span><?php esc_html_e( 'Status', 'lunara-film' ); ?></span>
            <select name="lcd_status">
                <option value="all"><?php esc_html_e( 'All', 'lunara-film' ); ?></option>
                <option value="draft" <?php selected( $status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'lunara-film' ); ?></option>
                <option value="pending" <?php selected( $status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'lunara-film' ); ?></option>
            </select>
        </label>
        <label>
            <span><?php esc_html_e( 'Issue', 'lunara-film' ); ?></span>
            <select name="lcd_issue">
                <option value="all"><?php esc_html_e( 'All', 'lunara-film' ); ?></option>
                <option value="blockers" <?php selected( $issue, 'blockers' ); ?>><?php esc_html_e( 'Blockers', 'lunara-film' ); ?></option>
                <option value="warnings" <?php selected( $issue, 'warnings' ); ?>><?php esc_html_e( 'Warnings only', 'lunara-film' ); ?></option>
                <option value="ready" <?php selected( $issue, 'ready' ); ?>><?php esc_html_e( 'Ready', 'lunara-film' ); ?></option>
            </select>
        </label>
        <button type="submit" class="button button-secondary"><?php esc_html_e( 'Filter', 'lunara-film' ); ?></button>
    </form>
    <div class="lunara-control-desk-saved-views">
        <a class="button" href="<?php echo esc_url( lunara_control_desk_url( array( 'tab' => $active_tab, 'lcd_issue' => 'blockers' ) ) ); ?>"><?php esc_html_e( 'Needs packaging', 'lunara-film' ); ?></a>
        <a class="button" href="<?php echo esc_url( lunara_control_desk_url( array( 'tab' => $active_tab, 'lcd_type' => 'review' ) ) ); ?>"><?php esc_html_e( 'Reviews only', 'lunara-film' ); ?></a>
        <a class="button" href="<?php echo esc_url( lunara_control_desk_url( array( 'tab' => $active_tab, 'lcd_type' => 'journal' ) ) ); ?>"><?php esc_html_e( 'Journal only', 'lunara-film' ); ?></a>
        <a class="button" href="<?php echo esc_url( lunara_control_desk_url( array( 'tab' => $active_tab, 'lcd_issue' => 'ready' ) ) ); ?>"><?php esc_html_e( 'Ready reads', 'lunara-film' ); ?></a>
    </div>
    <?php
}

function lunara_control_desk_render_queue_table( $rows, $args = array() ) {
    $args = wp_parse_args(
        $args,
        array(
            'show_ai'      => true,
            'empty'        => __( 'No matching draft or pending items right now.', 'lunara-film' ),
            'show_signals' => true,
        )
    );

    if ( empty( $rows ) ) {
        echo '<div class="lunara-control-desk-empty"><p>' . esc_html( $args['empty'] ) . '</p></div>';
        return;
    }
    ?>
    <table class="widefat striped lunara-control-desk-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Draft', 'lunara-film' ); ?></th>
                <th><?php esc_html_e( 'Type', 'lunara-film' ); ?></th>
                <th><?php esc_html_e( 'Status', 'lunara-film' ); ?></th>
                <th><?php esc_html_e( 'Readiness', 'lunara-film' ); ?></th>
                <th><?php esc_html_e( 'Modified', 'lunara-film' ); ?></th>
                <?php if ( $args['show_signals'] ) : ?>
                    <th><?php esc_html_e( 'Packaging signals', 'lunara-film' ); ?></th>
                <?php endif; ?>
                <th><?php esc_html_e( 'Action', 'lunara-film' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $rows as $row ) : ?>
                <?php
                $post          = $row['post'];
                $status_object = get_post_status_object( $post->post_status );
                $edit_url      = get_edit_post_link( $post->ID, 'raw' );
                $preview_url   = function_exists( 'get_preview_post_link' ) ? get_preview_post_link( $post ) : '';
                ?>
                <tr data-lunara-row-post="<?php echo esc_attr( $post->ID ); ?>">
                    <td>
                        <strong><?php echo esc_html( get_the_title( $post ) ? get_the_title( $post ) : __( '(Untitled)', 'lunara-film' ) ); ?></strong>
                        <div class="lunara-control-desk-subtle">ID <?php echo esc_html( $post->ID ); ?> - <?php echo esc_html( lunara_control_desk_get_ai_mode_label( $post->post_type ) ); ?></div>
                        <div class="lunara-control-desk-inline-result" data-lunara-result="<?php echo esc_attr( $post->ID ); ?>" hidden></div>
                    </td>
                    <td><?php echo esc_html( lunara_control_desk_post_type_label( $post->post_type ) ); ?></td>
                    <td><?php echo esc_html( $status_object ? $status_object->label : $post->post_status ); ?></td>
                    <td><?php lunara_control_desk_render_readiness_badge( $row['readiness'] ); ?></td>
                    <td><?php echo esc_html( get_the_modified_date( 'M j, Y g:i a', $post ) ); ?></td>
                    <?php if ( $args['show_signals'] ) : ?>
                        <td><?php lunara_control_desk_render_signals( $row['signals'] ); ?></td>
                    <?php endif; ?>
                    <td>
                        <div class="lunara-control-desk-actions">
                            <a class="button button-primary" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Open Editor', 'lunara-film' ); ?></a>
                            <?php if ( $preview_url ) : ?>
                                <a class="button" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Preview', 'lunara-film' ); ?></a>
                            <?php endif; ?>
                            <?php if ( $args['show_ai'] ) : ?>
                                <?php lunara_control_desk_render_suggest_button( $post->ID, lunara_control_desk_get_default_intent( $post->post_type ), __( 'AI Suggest', 'lunara-film' ) ); ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function lunara_control_desk_render_status_cards( $items ) {
    if ( empty( $items ) ) {
        return;
    }
    ?>
    <div class="lunara-control-desk-status-grid">
        <?php foreach ( $items as $item ) : ?>
            <?php
            $state = isset( $item['state'] ) ? sanitize_html_class( $item['state'] ) : 'ready';
            $label = isset( $item['label'] ) ? $item['label'] : '';
            $value = isset( $item['value'] ) ? $item['value'] : '';
            $note  = isset( $item['note'] ) ? $item['note'] : '';
            ?>
            <article class="lunara-control-desk-status-card is-<?php echo esc_attr( $state ); ?>">
                <p class="lunara-control-desk-kicker"><?php echo esc_html( $label ); ?></p>
                <strong><?php echo esc_html( $value ); ?></strong>
                <?php if ( $note ) : ?>
                    <span><?php echo esc_html( $note ); ?></span>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}

function lunara_control_desk_render_operating_plan_tab() {
    $lanes = array(
        array(
            'label' => __( 'Now', 'lunara-film' ),
            'title' => __( 'Quiet the Oscars Ledger surface', 'lunara-film' ),
            'body'  => __( 'Finish the compact poster rhythm on the Oscars portal, then confirm title and person routes feel lighter on mobile.', 'lunara-film' ),
            'links' => array(
                array( __( 'Oscars Portal', 'lunara-film' ), home_url( '/oscars/' ) ),
                array( __( 'Title Sample', 'lunara-film' ), home_url( '/oscars/title/tt0110912/' ) ),
                array( __( 'Person Sample', 'lunara-film' ), home_url( '/oscars/name/nm0000233/' ) ),
            ),
        ),
        array(
            'label' => __( 'Next', 'lunara-film' ),
            'title' => __( 'Make Journal the curated growth front door', 'lunara-film' ),
            'body'  => __( 'Use the Journal desk to protect originality, strengthen source attribution, tune visual files, and choose homepage-worthy leads.', 'lunara-film' ),
            'links' => array(
                array( __( 'Journal Growth', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'journal-growth' ) ) ),
                array( __( 'Journal Archive', 'lunara-film' ), home_url( '/journal/' ) ),
                array( __( 'New Journal', 'lunara-film' ), admin_url( 'post-new.php?post_type=journal' ) ),
            ),
        ),
        array(
            'label' => __( 'Then', 'lunara-film' ),
            'title' => __( 'Run reviews like authority packages', 'lunara-film' ),
            'body'  => __( 'Use the review work session for score, pull quote, card image, IMDb, homepage fit, and preview checks before publishing.', 'lunara-film' ),
            'links' => array(
                array( __( 'Reviews Desk', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'reviews' ) ) ),
                array( __( 'Reviews Archive', 'lunara-film' ), home_url( '/reviews/' ) ),
                array( __( 'New Review', 'lunara-film' ), admin_url( 'post-new.php?post_type=review' ) ),
            ),
        ),
        array(
            'label' => __( 'Always', 'lunara-film' ),
            'title' => __( 'Verify, cache, document, repeat', 'lunara-film' ),
            'body'  => __( 'Keep 390px and 768px first-class, flush cache after deploys, block unsafe source images, and update session notes after meaningful changes.', 'lunara-film' ),
            'links' => array(
                array( __( 'Speed & Stability', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'speed-stability' ) ) ),
                array( __( 'Visual QA', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'visual-qa' ) ) ),
                array( __( 'System', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'system-status' ) ) ),
            ),
        ),
    );

    $attack_cards = array(
        array(
            'kicker' => __( '1. Continuity', 'lunara-film' ),
            'title'  => __( 'One source of truth', 'lunara-film' ),
            'body'   => __( 'The Control Desk, session log, changelog, backups, and visual evidence keep every session from starting cold.', 'lunara-film' ),
            'items'  => array(
                __( 'Start from the active theme and live target paths.', 'lunara-film' ),
                __( 'Back up before deploys and record the deployed batch.', 'lunara-film' ),
                __( 'Use System and Visual QA tabs as the current map.', 'lunara-film' ),
            ),
            'links'  => array(
                array( __( 'System', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'system-status' ) ) ),
                array( __( 'Visual QA', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'visual-qa' ) ) ),
            ),
        ),
        array(
            'kicker' => __( '2. Public Product', 'lunara-film' ),
            'title'  => __( 'Home, Journal, Reviews, Oscars', 'lunara-film' ),
            'body'   => __( 'Treat Lunara as connected surfaces: Journal drives discovery, Reviews build authority, and Oscars gives the site its database spine.', 'lunara-film' ),
            'items'  => array(
                __( 'Home needs curated paths into the strongest current work.', 'lunara-film' ),
                __( 'Journal must feel original and worth returning to.', 'lunara-film' ),
                __( 'Oscars routes should feel coherent across portal, category, ceremony, title, and person pages.', 'lunara-film' ),
            ),
            'links'  => array(
                array( __( 'Home', 'lunara-film' ), home_url( '/' ) ),
                array( __( 'Journal', 'lunara-film' ), home_url( '/journal/' ) ),
                array( __( 'Reviews', 'lunara-film' ), home_url( '/reviews/' ) ),
                array( __( 'Oscars', 'lunara-film' ), home_url( '/oscars/' ) ),
            ),
        ),
        array(
            'kicker' => __( '3. Journal Growth', 'lunara-film' ),
            'title'  => __( 'Curated, fast, and not derivative', 'lunara-film' ),
            'body'   => __( 'The Journal pipeline should move quickly, but every kept draft needs a clear Lunara angle, visible attribution, and a useful reader promise.', 'lunara-film' ),
            'items'  => array(
                __( 'Reject filler and rewritten-source posts.', 'lunara-film' ),
                __( 'Use credited, provenance-safe imagery and carousel files where the story needs multiple visuals.', 'lunara-film' ),
                __( 'Promote strong entries into homepage lanes deliberately.', 'lunara-film' ),
            ),
            'links'  => array(
                array( __( 'Journal Growth', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'journal-growth' ) ) ),
                array( __( 'Homepage Board', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'homepage-board' ) ) ),
                array( __( 'Field Suggestions', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'field-suggestions', 'lcd_type' => 'journal' ) ) ),
            ),
        ),
        array(
            'kicker' => __( '4. Review Authority', 'lunara-film' ),
            'title'  => __( 'Package reviews before they go public', 'lunara-film' ),
            'body'   => __( 'Reviews should leave the desk with poster-safe images, strong excerpts, clear scores, pull quotes, and Ledger-aware internal paths.', 'lunara-film' ),
            'items'  => array(
                __( 'Featured/card image checks stay strict.', 'lunara-film' ),
                __( 'Work sessions surface the exact fields to fix first.', 'lunara-film' ),
                __( 'AI can suggest packaging, but editor judgment owns the publish decision.', 'lunara-film' ),
            ),
            'links'  => array(
                array( __( 'Reviews Desk', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'reviews' ) ) ),
                array( __( 'Readiness', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'readiness', 'lcd_type' => 'review' ) ) ),
            ),
        ),
        array(
            'kicker' => __( '5. Oscars Ledger', 'lunara-film' ),
            'title'  => __( 'Small, fast, accurate database pages', 'lunara-film' ),
            'body'   => __( 'The Ledger should keep its identity while shedding visual bulk: smaller posters, reliable IMDb IDs, correct routes, and cleaner mobile density.', 'lunara-film' ),
            'items'  => array(
                __( 'Audit title/person entity pages after portal changes.', 'lunara-film' ),
                __( 'Prefer safe caching and bounded image sizes over broad rewrites.', 'lunara-film' ),
                __( 'Repair only high-confidence poster or IMDb samples.', 'lunara-film' ),
            ),
            'links'  => array(
                array( __( 'Oscars Integrity', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'oscars-integrity' ) ) ),
                array( __( 'Ledger Assistant', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'ledger-assistant' ) ) ),
                array( __( 'Speed & Stability', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'speed-stability' ) ) ),
            ),
        ),
        array(
            'kicker' => __( '6. AI + QA Layer', 'lunara-film' ),
            'title'  => __( 'Use the tools without letting them drive blind', 'lunara-film' ),
            'body'   => __( 'OpenAI structures fields, Anthropic critiques taste and readiness, Gemini checks long context, and visual QA proves the public result.', 'lunara-film' ),
            'items'  => array(
                __( 'Suggestions stay private snapshots unless a later safe-apply action is approved.', 'lunara-film' ),
                __( 'Browser screenshots and route checks decide whether the change worked.', 'lunara-film' ),
                __( 'No Control Desk assets should leak onto public pages.', 'lunara-film' ),
            ),
            'links'  => array(
                array( __( 'AI Operator', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'ai-operator' ) ) ),
                array( __( 'Field Suggestions', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'field-suggestions' ) ) ),
                array( __( 'Visual QA', 'lunara-film' ), lunara_control_desk_url( array( 'tab' => 'visual-qa' ) ) ),
            ),
        ),
    );

    $queue = array(
        __( 'Fill BACKROOMS once the draft has body text; it was intentionally left without a Card Pull Quote because the draft is empty.', 'lunara-film' ),
        __( 'Add a Reviews work-session preview that mirrors the live public card and makes quote length/readability obvious before publish.', 'lunara-film' ),
        __( 'Use Journal Growth to pick the next strong lead or keep automatic latest while calibration continues.', 'lunara-film' ),
        __( 'Have Dalton visually judge the rotating Oscars winners carousel and Best Picture desktop rhythm.', 'lunara-film' ),
    );

    $rules = array(
        __( 'AI is suggest-first and cannot silently mutate public copy, metadata, homepage flags, or Ledger data.', 'lunara-film' ),
        __( 'Back up before deployment, lint changed PHP, flush cache, and verify public routes after deploy.', 'lunara-film' ),
        __( 'Mobile 390px and 768px are first-class acceptance lanes, not afterthoughts.', 'lunara-film' ),
        __( 'World of Reel images stay blocked for featured-image use; external exclusive images need visible provenance and credit.', 'lunara-film' ),
        __( 'Update session logs and the long-term changelog after meaningful product changes.', 'lunara-film' ),
    );

    $tools = array(
        array( __( 'Control Desk', 'lunara-film' ), __( 'Private operating surface for readiness, QA, curation, and next actions.', 'lunara-film' ) ),
        array( __( 'WordPress.com + SSH', 'lunara-film' ), __( 'Live checks, backups, deploys, cache flushes, and remote linting.', 'lunara-film' ) ),
        array( __( 'Browser QA', 'lunara-film' ), __( 'Screenshots and interaction checks at mobile, tablet, and desktop widths.', 'lunara-film' ) ),
        array( __( 'OpenAI / Anthropic / Gemini', 'lunara-film' ), __( 'Private snapshots for structure, taste, long context, and Ledger checks.', 'lunara-film' ) ),
        array( __( 'Dispatch', 'lunara-film' ), __( 'Draft-first Journal source pipeline with a hard originality gate.', 'lunara-film' ) ),
        array( __( 'Logs + Evidence', 'lunara-film' ), __( 'The continuity layer that protects against lost context.', 'lunara-film' ) ),
    );

    $continuity_cards = array(
        array(
            'label' => __( 'Website workspace', 'lunara-film' ),
            'value' => __( 'Desktop workspace is canonical', 'lunara-film' ),
            'state' => 'ready',
            'note'  => __( 'Use the Desktop Lunara folder for website logs, handoffs, changelog work, visual QA, and working artifacts.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Old workspace guardrail', 'lunara-film' ),
            'value' => __( 'Documents\\New project is blocked', 'lunara-film' ),
            'state' => 'ready',
            'note'  => __( 'Do not save new Lunara website evidence, screenshots, handoffs, changelogs, or working artifacts there.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Latest handoff', 'lunara-film' ),
            'value' => __( 'Review exact-quote workflow', 'lunara-film' ),
            'state' => 'ready',
            'note'  => __( 'Current boot opens on BACKROOMS quote follow-up, Reviews preview, Journal polish, and carousel/Best Picture visual judgment.', 'lunara-film' ),
        ),
    );

    $continuity_paths = array(
        array( __( 'Website handoff / logs', 'lunara-film' ), 'C:\\Users\\silve_i21do49\\OneDrive\\Desktop\\New folder\\09_DOCS_AND_NOTES' ),
        array( __( 'Website changelog', 'lunara-film' ), 'C:\\Users\\silve_i21do49\\OneDrive\\Desktop\\New folder\\LUNARA_WORLD_CHANGELOG.md' ),
        array( __( 'Visual QA / evidence', 'lunara-film' ), 'C:\\Users\\silve_i21do49\\OneDrive\\Desktop\\New folder\\10_VISUAL_EVIDENCE' ),
        array( __( 'Forbidden website artifact path', 'lunara-film' ), 'C:\\Users\\silve_i21do49\\OneDrive\\Documents\\New project' ),
        array( __( 'Active local theme source', 'lunara-film' ), 'G:\\lunara-backups\\work\\lunara-theme-blocks-20260513-2300' ),
        array( __( 'Live theme', 'lunara-film' ), '/home/151589083/htdocs/wp-content/themes/lunara-theme-blocks-20260513-2300' ),
        array( __( 'Latest remote backup', 'lunara-film' ), '/home/151589083/lunara-backups/lunara-review-card-exact-quote-20260601-0408' ),
    );
    ?>
    <section class="lunara-control-desk-panel lunara-control-desk-operating-plan">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Operating Plan', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Use every tool without scattering the work', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'This is the private plan of attack for building Lunara: protect continuity, keep the site fast and readable, make Journal a real growth product, and preserve the Oscars Ledger as the signature database experience.', 'lunara-film' ); ?></p>
        </div>

        <div class="lunara-control-desk-plan-lanes">
            <?php foreach ( $lanes as $lane ) : ?>
                <article class="lunara-control-desk-plan-lane">
                    <p class="lunara-control-desk-kicker"><?php echo esc_html( $lane['label'] ); ?></p>
                    <h3><?php echo esc_html( $lane['title'] ); ?></h3>
                    <p><?php echo esc_html( $lane['body'] ); ?></p>
                    <div class="lunara-control-desk-actions">
                        <?php foreach ( $lane['links'] as $link ) : ?>
                            <a class="button" href="<?php echo esc_url( $link[1] ); ?>"><?php echo esc_html( $link[0] ); ?></a>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="lunara-control-desk-panel lunara-control-desk-continuity-status">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Save / Continuity Status', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Where the website work must land', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'This panel is the in-admin reminder for the current handoff, evidence, changelog, and source paths. It is read-only so the saved route cannot drift from session notes by accident.', 'lunara-film' ); ?></p>
        </div>

        <?php lunara_control_desk_render_status_cards( $continuity_cards ); ?>

        <div class="lunara-control-desk-continuity-grid">
            <?php foreach ( $continuity_paths as $path ) : ?>
                <article class="lunara-control-desk-continuity-card">
                    <strong><?php echo esc_html( $path[0] ); ?></strong>
                    <code><?php echo esc_html( $path[1] ); ?></code>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Plan of Attack', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'The six operating pillars', 'lunara-film' ); ?></h2>
        </div>
        <div class="lunara-control-desk-plan-grid">
            <?php foreach ( $attack_cards as $card ) : ?>
                <article class="lunara-control-desk-plan-card">
                    <p class="lunara-control-desk-kicker"><?php echo esc_html( $card['kicker'] ); ?></p>
                    <h3><?php echo esc_html( $card['title'] ); ?></h3>
                    <p><?php echo esc_html( $card['body'] ); ?></p>
                    <ul>
                        <?php foreach ( $card['items'] as $item ) : ?>
                            <li><?php echo esc_html( $item ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="lunara-control-desk-actions">
                        <?php foreach ( $card['links'] as $link ) : ?>
                            <a class="button" href="<?php echo esc_url( $link[1] ); ?>"><?php echo esc_html( $link[0] ); ?></a>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-operating-split">
            <div>
                <div class="lunara-control-desk-panel-header">
                    <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Immediate Queue', 'lunara-film' ); ?></p>
                    <h2><?php esc_html_e( 'Recommended next moves', 'lunara-film' ); ?></h2>
                </div>
                <ol class="lunara-control-desk-plan-queue">
                    <?php foreach ( $queue as $item ) : ?>
                        <li><?php echo esc_html( $item ); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <div>
                <div class="lunara-control-desk-panel-header">
                    <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Guardrails', 'lunara-film' ); ?></p>
                    <h2><?php esc_html_e( 'Rules that keep the work safe', 'lunara-film' ); ?></h2>
                </div>
                <ul class="lunara-control-desk-plan-rules">
                    <?php foreach ( $rules as $rule ) : ?>
                        <li><?php echo esc_html( $rule ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </section>

    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Tool Stack', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'What each tool is for', 'lunara-film' ); ?></h2>
        </div>
        <div class="lunara-control-desk-tool-grid">
            <?php foreach ( $tools as $tool ) : ?>
                <article>
                    <strong><?php echo esc_html( $tool[0] ); ?></strong>
                    <span><?php echo esc_html( $tool[1] ); ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function lunara_control_desk_render_system_status_tab() {
    $phase_cards = array(
        array( 'label' => __( 'Phase 1', 'lunara-film' ), 'value' => __( 'Foundation active', 'lunara-film' ), 'state' => 'ready', 'note' => __( 'Top-level admin surface and source map.', 'lunara-film' ) ),
        array( 'label' => __( 'Phase 2', 'lunara-film' ), 'value' => __( 'Review pipeline active', 'lunara-film' ), 'state' => 'ready', 'note' => __( 'Grouped blockers, previews, and exact editor targets.', 'lunara-film' ) ),
        array( 'label' => __( 'Phase 3', 'lunara-film' ), 'value' => __( 'Theme Studio active', 'lunara-film' ), 'state' => 'ready', 'note' => __( 'Existing Customizer controls in one map.', 'lunara-film' ) ),
        array( 'label' => __( 'Phase 4', 'lunara-film' ), 'value' => __( 'Oscars Integrity active', 'lunara-film' ), 'state' => 'ready', 'note' => __( 'Poster, IMDb, route checks, and resolver suggestions.', 'lunara-film' ) ),
        array( 'label' => __( 'Phase 5', 'lunara-film' ), 'value' => __( 'Speed watch active', 'lunara-film' ), 'state' => 'ready', 'note' => __( 'Public status, payload, cache, and mobile-risk watchlist.', 'lunara-film' ) ),
        array( 'label' => __( 'Phase 6', 'lunara-film' ), 'value' => __( 'Visual QA active', 'lunara-film' ), 'state' => 'ready', 'note' => __( 'Canonical breakpoints with 390px and 768px prioritized.', 'lunara-film' ) ),
        array( 'label' => __( 'Phase 7', 'lunara-film' ), 'value' => __( 'AI Operator active', 'lunara-film' ), 'state' => 'ready', 'note' => __( 'Provider-routed private suggestions only.', 'lunara-film' ) ),
    );
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'System Status', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'The private operating surface for Lunara work', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'This is read-only in the first rollout: it tells you where things live, what is active, and which surface owns each job.', 'lunara-film' ); ?></p>
        </div>
        <?php lunara_control_desk_render_status_cards( lunara_control_desk_get_system_status() ); ?>
    </section>

    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Source Map', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Edit the right code, deploy the right target', 'lunara-film' ); ?></h2>
        </div>
        <?php lunara_control_desk_render_status_cards( lunara_control_desk_get_source_status() ); ?>
    </section>

    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Source Control', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Private GitHub baselines for the custom stack', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'These are the last recorded source-control anchors for the theme and load-bearing custom plugins. Update this panel after future commits that become the blessed working baseline.', 'lunara-film' ); ?></p>
        </div>
        <?php lunara_control_desk_render_status_cards( lunara_control_desk_get_source_control_status() ); ?>
    </section>

    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Rollout Map', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'One phase at a time, no silent public mutations', 'lunara-film' ); ?></h2>
        </div>
        <?php lunara_control_desk_render_status_cards( $phase_cards ); ?>
    </section>
    <?php
}

function lunara_control_desk_review_image_ratio_note( $post_id ) {
    $thumbnail_id = get_post_thumbnail_id( $post_id );

    if ( ! $thumbnail_id ) {
        return lunara_control_desk_make_signal( __( 'Featured image missing', 'lunara-film' ) );
    }

    $meta = wp_get_attachment_metadata( $thumbnail_id );
    if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) {
        return lunara_control_desk_make_signal( __( 'Featured image dimensions unknown', 'lunara-film' ), 'weak' );
    }

    $ratio = floatval( $meta['width'] ) / max( 1, floatval( $meta['height'] ) );
    if ( $ratio < 0.58 || $ratio > 0.74 ) {
        return lunara_control_desk_make_signal( __( 'Featured image is not close to 2:3', 'lunara-film' ), 'weak' );
    }

    return lunara_control_desk_make_signal( __( 'Featured image ratio looks poster-safe', 'lunara-film' ), 'ready' );
}

function lunara_control_desk_get_review_pipeline_signals( $post_id ) {
    return lunara_control_desk_review_checks_to_signals( lunara_control_desk_get_review_pipeline_checks( $post_id ) );
}

function lunara_control_desk_group_review_checks( $checks ) {
    $groups = array();

    foreach ( $checks as $check ) {
        $group = isset( $check['group'] ) && '' !== $check['group'] ? $check['group'] : __( 'Packaging', 'lunara-film' );

        if ( ! isset( $groups[ $group ] ) ) {
            $groups[ $group ] = array();
        }

        $groups[ $group ][] = $check;
    }

    return $groups;
}

function lunara_control_desk_get_review_pipeline_summary_cards( $review_rows ) {
    $total           = count( $review_rows );
    $blocked         = 0;
    $warnings        = 0;
    $ready           = 0;
    $homepage_ready  = 0;
    $missing_imdb    = 0;
    $card_watch      = 0;

    foreach ( $review_rows as $row ) {
        if ( empty( $row['post'] ) || ! $row['post'] instanceof WP_Post ) {
            continue;
        }

        $checks    = lunara_control_desk_get_review_pipeline_checks( $row['post']->ID );
        $readiness = lunara_control_desk_get_readiness( lunara_control_desk_review_checks_to_signals( $checks ) );

        if ( 'blocked' === $readiness['status'] ) {
            $blocked++;
        } elseif ( 'warnings' === $readiness['status'] ) {
            $warnings++;
        } else {
            $ready++;
        }

        foreach ( $checks as $check ) {
            if ( 'homepage_flags' === $check['key'] && 'ready' === $check['kind'] ) {
                $homepage_ready++;
            }

            if ( 'imdb_title_id' === $check['key'] && 'ready' !== $check['kind'] ) {
                $missing_imdb++;
            }

            if ( 'card_image' === $check['key'] && 'ready' !== $check['kind'] ) {
                $card_watch++;
            }
        }
    }

    return array(
        array(
            'label' => __( 'Draft reviews', 'lunara-film' ),
            'value' => (string) $total,
            'state' => $total ? 'ready' : 'weak',
            'note'  => __( 'Draft and pending review items in the queue.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Blocked', 'lunara-film' ),
            'value' => (string) $blocked,
            'state' => $blocked ? 'needs' : 'ready',
            'note'  => __( 'Has missing publish-critical fields.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Warnings', 'lunara-film' ),
            'value' => (string) $warnings,
            'state' => $warnings ? 'weak' : 'ready',
            'note'  => __( 'Can move, but needs polish.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Ready', 'lunara-film' ),
            'value' => (string) $ready,
            'state' => $ready ? 'ready' : 'weak',
            'note'  => __( 'No blocker or warning signals.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Homepage candidates', 'lunara-film' ),
            'value' => (string) $homepage_ready,
            'state' => $homepage_ready ? 'ready' : 'weak',
            'note'  => __( 'Reviews marked for a homepage lane.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'IMDb/card watch', 'lunara-film' ),
            'value' => sprintf( '%1$d / %2$d', $missing_imdb, $card_watch ),
            'state' => ( $missing_imdb || $card_watch ) ? 'weak' : 'ready',
            'note'  => __( 'Missing IMDb IDs / 2:3 card-image warnings.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_get_review_issue_groups( $review_rows ) {
    $issues = array();

    foreach ( $review_rows as $row ) {
        if ( empty( $row['post'] ) || ! $row['post'] instanceof WP_Post ) {
            continue;
        }

        foreach ( lunara_control_desk_get_review_pipeline_checks( $row['post']->ID ) as $check ) {
            if ( 'ready' === $check['kind'] ) {
                continue;
            }

            $key = $check['key'];
            if ( ! isset( $issues[ $key ] ) ) {
                $issues[ $key ] = array(
                    'label' => $check['label'],
                    'kind'  => $check['kind'],
                    'rows'  => array(),
                );
            }

            if ( 'needs' === $check['kind'] ) {
                $issues[ $key ]['kind'] = 'needs';
            }

            $issues[ $key ]['rows'][] = array(
                'id'    => $row['post']->ID,
                'title' => get_the_title( $row['post'] ) ? get_the_title( $row['post'] ) : __( '(Untitled)', 'lunara-film' ),
            );
        }
    }

    uasort(
        $issues,
        static function ( $a, $b ) {
            $kind_a = 'needs' === $a['kind'] ? 0 : 1;
            $kind_b = 'needs' === $b['kind'] ? 0 : 1;

            if ( $kind_a !== $kind_b ) {
                return $kind_a <=> $kind_b;
            }

            return count( $b['rows'] ) <=> count( $a['rows'] );
        }
    );

    return $issues;
}

function lunara_control_desk_get_review_pipeline_rows( $limit = 60 ) {
    if ( ! post_type_exists( 'review' ) ) {
        return array();
    }

    $query = new WP_Query(
        array(
            'post_type'              => 'review',
            'post_status'            => lunara_control_desk_statuses(),
            'posts_per_page'         => absint( $limit ),
            'orderby'                => 'modified',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        )
    );

    $rows = array();

    foreach ( $query->posts as $post ) {
        if ( ! $post instanceof WP_Post ) {
            continue;
        }

        $signals = lunara_control_desk_get_review_pipeline_signals( $post->ID );
        $rows[]  = array(
            'post'      => $post,
            'signals'   => $signals,
            'readiness' => lunara_control_desk_get_readiness( $signals ),
        );
    }

    return $rows;
}

function lunara_control_desk_review_filter_definitions() {
    return array(
        'all'          => array(
            'label' => __( 'All Reviews', 'lunara-film' ),
            'note'  => __( 'Every draft or pending review.', 'lunara-film' ),
        ),
        'blocked'      => array(
            'label' => __( 'Blocked', 'lunara-film' ),
            'note'  => __( 'Missing publish-critical fields.', 'lunara-film' ),
        ),
        'missing-imdb' => array(
            'label' => __( 'Missing IMDb', 'lunara-film' ),
            'note'  => __( 'Needs a normalized title ID.', 'lunara-film' ),
        ),
        'media-card'   => array(
            'label' => __( 'Media / Card', 'lunara-film' ),
            'note'  => __( 'Featured, card, or hero image needs attention.', 'lunara-film' ),
        ),
        'homepage'     => array(
            'label' => __( 'Homepage Candidates', 'lunara-film' ),
            'note'  => __( 'Flagged for a homepage lane.', 'lunara-film' ),
        ),
        'ready'        => array(
            'label' => __( 'Ready', 'lunara-film' ),
            'note'  => __( 'Clear of blockers and warnings.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_get_active_review_filter() {
    $filter  = lunara_control_desk_get_request_key( 'lcd_review_filter', 'all' );
    $filters = lunara_control_desk_review_filter_definitions();

    return isset( $filters[ $filter ] ) ? $filter : 'all';
}

function lunara_control_desk_review_row_matches_filter( $row, $filter ) {
    if ( 'all' === $filter ) {
        return true;
    }

    if ( empty( $row['post'] ) || ! $row['post'] instanceof WP_Post ) {
        return false;
    }

    $checks    = lunara_control_desk_get_review_pipeline_checks( $row['post']->ID );
    $readiness = isset( $row['readiness'] ) && is_array( $row['readiness'] )
        ? $row['readiness']
        : lunara_control_desk_get_readiness( lunara_control_desk_review_checks_to_signals( $checks ) );

    if ( 'blocked' === $filter ) {
        return 'blocked' === $readiness['status'];
    }

    if ( 'ready' === $filter ) {
        return 'ready' === $readiness['status'];
    }

    foreach ( $checks as $check ) {
        $key  = isset( $check['key'] ) ? $check['key'] : '';
        $kind = isset( $check['kind'] ) ? $check['kind'] : 'needs';

        if ( 'missing-imdb' === $filter && 'imdb_title_id' === $key && 'ready' !== $kind ) {
            return true;
        }

        if ( 'homepage' === $filter && 'homepage_flags' === $key && 'ready' === $kind ) {
            return true;
        }

        if ( 'media-card' === $filter && in_array( $key, array( 'featured_image', 'card_image', 'hero_visual' ), true ) && 'ready' !== $kind ) {
            return true;
        }
    }

    return false;
}

function lunara_control_desk_filter_review_rows( $review_rows, $filter ) {
    return array_values(
        array_filter(
            $review_rows,
            static function ( $row ) use ( $filter ) {
                return lunara_control_desk_review_row_matches_filter( $row, $filter );
            }
        )
    );
}

function lunara_control_desk_count_review_filter_rows( $review_rows, $filter ) {
    return count( lunara_control_desk_filter_review_rows( $review_rows, $filter ) );
}

function lunara_control_desk_render_review_filters( $review_rows, $active_filter ) {
    $filters = lunara_control_desk_review_filter_definitions();
    ?>
    <div class="lunara-control-desk-review-filters" aria-label="<?php echo esc_attr__( 'Review pipeline views', 'lunara-film' ); ?>">
        <div>
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Review Views', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Jump to the work that needs you next', 'lunara-film' ); ?></h3>
        </div>
        <div class="lunara-control-desk-review-filter-list">
            <?php foreach ( $filters as $filter => $definition ) : ?>
                <?php
                $count = lunara_control_desk_count_review_filter_rows( $review_rows, $filter );
                $url   = lunara_control_desk_url(
                    array(
                        'tab'               => 'reviews',
                        'lcd_review_filter' => $filter,
                    )
                );
                ?>
                <a class="lunara-control-desk-review-filter <?php echo $active_filter === $filter ? 'is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>" <?php echo $active_filter === $filter ? 'aria-current="page"' : ''; ?>>
                    <strong><?php echo esc_html( $definition['label'] ); ?></strong>
                    <span><?php echo esc_html( $definition['note'] ); ?></span>
                    <em><?php echo esc_html( (string) $count ); ?></em>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function lunara_control_desk_render_review_issue_groups( $issue_groups ) {
    if ( empty( $issue_groups ) ) {
        echo '<div class="lunara-control-desk-empty"><p>' . esc_html__( 'No review pipeline issues detected.', 'lunara-film' ) . '</p></div>';
        return;
    }
    ?>
    <div class="lunara-control-desk-review-issue-grid">
        <?php foreach ( array_slice( $issue_groups, 0, 8 ) as $issue ) : ?>
            <article class="lunara-control-desk-review-issue is-<?php echo esc_attr( $issue['kind'] ); ?>">
                <div>
                    <strong><?php echo esc_html( $issue['label'] ); ?></strong>
                    <span><?php echo esc_html( sprintf( _n( '%d review', '%d reviews', count( $issue['rows'] ), 'lunara-film' ), count( $issue['rows'] ) ) ); ?></span>
                </div>
                <div class="lunara-control-desk-review-issue-links">
                    <?php foreach ( array_slice( $issue['rows'], 0, 3 ) as $row ) : ?>
                        <a href="#lunara-review-<?php echo esc_attr( $row['id'] ); ?>"><?php echo esc_html( $row['title'] ); ?></a>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}

function lunara_control_desk_get_review_check_by_key( $checks, $key ) {
    foreach ( $checks as $check ) {
        if ( isset( $check['key'] ) && $key === $check['key'] ) {
            return $check;
        }
    }

    return array();
}

function lunara_control_desk_get_first_open_review_check( $checks ) {
    foreach ( array( 'needs', 'weak' ) as $kind ) {
        foreach ( $checks as $check ) {
            if ( isset( $check['kind'] ) && $kind === $check['kind'] ) {
                return $check;
            }
        }
    }

    return array();
}

function lunara_control_desk_review_check_anchor_id( $post_id, $check ) {
    $key = isset( $check['key'] ) && '' !== $check['key'] ? sanitize_key( $check['key'] ) : 'check';

    return 'lunara-review-' . absint( $post_id ) . '-check-' . $key;
}

function lunara_control_desk_get_first_open_review_target( $review_rows ) {
    foreach ( $review_rows as $row ) {
        if ( empty( $row['post'] ) || ! $row['post'] instanceof WP_Post ) {
            continue;
        }

        $checks = lunara_control_desk_get_review_pipeline_checks( $row['post']->ID );
        $check  = lunara_control_desk_get_first_open_review_check( $checks );

        if ( empty( $check ) ) {
            continue;
        }

        return array(
            'url'   => '#' . lunara_control_desk_review_check_anchor_id( $row['post']->ID, $check ),
            'label' => isset( $check['label'] ) ? $check['label'] : __( 'First blocker', 'lunara-film' ),
            'title' => get_the_title( $row['post'] ) ? get_the_title( $row['post'] ) : __( '(Untitled)', 'lunara-film' ),
        );
    }

    return array();
}

function lunara_control_desk_get_review_title( $post ) {
    if ( ! $post instanceof WP_Post ) {
        return __( '(Untitled)', 'lunara-film' );
    }

    $title = get_the_title( $post );

    return $title ? $title : __( '(Untitled)', 'lunara-film' );
}

function lunara_control_desk_review_check_kind_label( $kind ) {
    if ( 'needs' === $kind ) {
        return __( 'Blocker', 'lunara-film' );
    }

    if ( 'weak' === $kind ) {
        return __( 'Warning', 'lunara-film' );
    }

    return __( 'Ready', 'lunara-film' );
}

function lunara_control_desk_get_open_review_checks( $checks ) {
    $open = array();

    foreach ( array( 'needs', 'weak' ) as $kind ) {
        foreach ( $checks as $check ) {
            if ( isset( $check['kind'] ) && $kind === $check['kind'] ) {
                $open[] = $check;
            }
        }
    }

    return $open;
}

function lunara_control_desk_get_review_brief_group_counts( $checks ) {
    $groups = array();

    foreach ( $checks as $check ) {
        $group = isset( $check['group'] ) && '' !== $check['group'] ? $check['group'] : __( 'Packaging', 'lunara-film' );

        if ( ! isset( $groups[ $group ] ) ) {
            $groups[ $group ] = array(
                'open'  => 0,
                'total' => 0,
            );
        }

        $groups[ $group ]['total']++;

        if ( isset( $check['kind'] ) && 'ready' !== $check['kind'] ) {
            $groups[ $group ]['open']++;
        }
    }

    return $groups;
}

function lunara_control_desk_get_active_review_brief_row( $review_rows ) {
    $requested_id = lunara_control_desk_get_request_absint( 'lcd_review_brief' );

    if ( $requested_id ) {
        foreach ( $review_rows as $row ) {
            if ( ! empty( $row['post'] ) && $row['post'] instanceof WP_Post && $requested_id === absint( $row['post']->ID ) ) {
                return $row;
            }
        }
    }

    return ! empty( $review_rows[0] ) ? $review_rows[0] : array();
}

function lunara_control_desk_is_review_work_session() {
    return 1 === lunara_control_desk_get_request_absint( 'lcd_review_work' );
}

function lunara_control_desk_review_brief_url( $post_id, $active_filter, $work_session = null ) {
    if ( null === $work_session ) {
        $work_session = lunara_control_desk_is_review_work_session();
    }

    $args = array(
        'tab'               => 'reviews',
        'lcd_review_filter' => $active_filter,
        'lcd_review_brief'  => absint( $post_id ),
    );

    if ( $work_session ) {
        $args['lcd_review_work'] = 1;
    }

    return lunara_control_desk_url( $args ) . '#lunara-review-brief';
}

function lunara_control_desk_review_work_session_url( $post_id, $active_filter ) {
    return lunara_control_desk_review_brief_url( $post_id, $active_filter, true );
}

function lunara_control_desk_build_review_brief_text( $post, $checks, $readiness ) {
    $open_checks = lunara_control_desk_get_open_review_checks( $checks );
    $lines       = array(
        sprintf( __( 'Lunara Review Packaging Brief: %s', 'lunara-film' ), lunara_control_desk_get_review_title( $post ) ),
        sprintf( __( 'Post ID: %d', 'lunara-film' ), absint( $post->ID ) ),
        sprintf( __( 'Status: %s', 'lunara-film' ), get_post_status( $post ) ),
        sprintf( __( 'Modified: %s', 'lunara-film' ), get_the_modified_date( get_option( 'date_format' ), $post ) ),
        sprintf( __( 'Readiness: %1$s (%2$d)', 'lunara-film' ), isset( $readiness['label'] ) ? $readiness['label'] : '', isset( $readiness['score'] ) ? absint( $readiness['score'] ) : 0 ),
        '',
        __( 'Next fixes:', 'lunara-film' ),
    );

    if ( empty( $open_checks ) ) {
        $lines[] = __( '- No open blockers or warnings detected.', 'lunara-film' );
    } else {
        foreach ( $open_checks as $check ) {
            $lines[] = sprintf(
                '- [%1$s] %2$s: %3$s. %4$s Owner: %5$s.',
                lunara_control_desk_review_check_kind_label( isset( $check['kind'] ) ? $check['kind'] : 'ready' ),
                isset( $check['label'] ) ? $check['label'] : '',
                isset( $check['value'] ) ? $check['value'] : '',
                isset( $check['note'] ) ? $check['note'] : '',
                isset( $check['owner'] ) ? $check['owner'] : ''
            );
        }
    }

    $lines[] = '';
    $lines[] = __( 'Editor links:', 'lunara-film' );
    $lines[] = sprintf( __( '- Editor: %s', 'lunara-film' ), get_edit_post_link( $post->ID, 'raw' ) );
    $lines[] = sprintf( __( '- Review Controls: %s', 'lunara-film' ), lunara_control_desk_review_editor_link( $post->ID, 'lunara_review_editorial_controls' ) );
    $lines[] = sprintf( __( '- Details / IMDb: %s', 'lunara-film' ), lunara_control_desk_review_editor_link( $post->ID, 'lunara_review_details_meta' ) );

    return implode( "\n", $lines );
}

function lunara_control_desk_render_review_brief_panel( $review_rows, $active_filter ) {
    if ( empty( $review_rows ) ) {
        return;
    }

    $row = lunara_control_desk_get_active_review_brief_row( $review_rows );

    if ( empty( $row['post'] ) || ! $row['post'] instanceof WP_Post ) {
        return;
    }

    $post         = $row['post'];
    $checks       = lunara_control_desk_get_review_pipeline_checks( $post->ID );
    $signals      = lunara_control_desk_review_checks_to_signals( $checks );
    $readiness    = lunara_control_desk_get_readiness( $signals );
    $open_checks  = lunara_control_desk_get_open_review_checks( $checks );
    $group_counts = lunara_control_desk_get_review_brief_group_counts( $checks );
    $brief_text   = lunara_control_desk_build_review_brief_text( $post, $checks, $readiness );
    $work_session = lunara_control_desk_is_review_work_session();
    ?>
    <section id="lunara-review-brief" class="lunara-control-desk-review-brief <?php echo $work_session ? 'is-work-session' : ''; ?>">
        <div class="lunara-control-desk-review-brief-head">
            <div>
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Packaging Brief', 'lunara-film' ); ?></p>
                <h3><?php echo esc_html( lunara_control_desk_get_review_title( $post ) ); ?></h3>
                <p class="lunara-control-desk-subtle">
                    <?php
                    printf(
                        /* translators: 1: post ID, 2: status, 3: modified date. */
                        esc_html__( 'ID %1$d / %2$s / modified %3$s', 'lunara-film' ),
                        absint( $post->ID ),
                        esc_html( get_post_status( $post ) ),
                        esc_html( get_the_modified_date( get_option( 'date_format' ), $post ) )
                    );
                    ?>
                </p>
            </div>
            <div class="lunara-control-desk-actions">
                <button type="button" class="button button-secondary" data-lunara-copy data-lunara-copy-text="<?php echo esc_attr( $brief_text ); ?>"><?php esc_html_e( 'Copy Brief', 'lunara-film' ); ?></button>
                <button type="button" class="button" data-lunara-print><?php esc_html_e( 'Print', 'lunara-film' ); ?></button>
                <?php if ( $work_session ) : ?>
                    <a class="button" href="<?php echo esc_url( lunara_control_desk_review_brief_url( $post->ID, $active_filter, false ) ); ?>"><?php esc_html_e( 'Exit Work Session', 'lunara-film' ); ?></a>
                <?php else : ?>
                    <a class="button" href="<?php echo esc_url( lunara_control_desk_review_work_session_url( $post->ID, $active_filter ) ); ?>"><?php esc_html_e( 'Work Session', 'lunara-film' ); ?></a>
                <?php endif; ?>
                <a class="button button-primary" href="<?php echo esc_url( get_edit_post_link( $post->ID, 'raw' ) ); ?>"><?php esc_html_e( 'Open Editor', 'lunara-film' ); ?></a>
            </div>
        </div>

        <div class="lunara-control-desk-review-brief-picker" aria-label="<?php echo esc_attr__( 'Choose review brief', 'lunara-film' ); ?>">
            <?php foreach ( $review_rows as $pick_row ) : ?>
                <?php
                if ( empty( $pick_row['post'] ) || ! $pick_row['post'] instanceof WP_Post ) {
                    continue;
                }

                $pick_post = $pick_row['post'];
                $is_active = absint( $pick_post->ID ) === absint( $post->ID );
                ?>
                <a class="<?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( lunara_control_desk_review_brief_url( $pick_post->ID, $active_filter, $work_session ) ); ?>" <?php echo $is_active ? 'aria-current="true"' : ''; ?>>
                    <strong><?php echo esc_html( lunara_control_desk_get_review_title( $pick_post ) ); ?></strong>
                    <span>#<?php echo esc_html( (string) $pick_post->ID ); ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="lunara-control-desk-review-brief-layout">
            <aside class="lunara-control-desk-review-brief-summary">
                <?php lunara_control_desk_render_readiness_badge( $readiness ); ?>
                <dl>
                    <div>
                        <dt><?php esc_html_e( 'Open items', 'lunara-film' ); ?></dt>
                        <dd><?php echo esc_html( (string) count( $open_checks ) ); ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e( 'First action', 'lunara-film' ); ?></dt>
                        <dd>
                            <?php
                            echo esc_html(
                                ! empty( $open_checks[0]['label'] )
                                    ? $open_checks[0]['label']
                                    : __( 'Ready to review', 'lunara-film' )
                            );
                            ?>
                        </dd>
                    </div>
                </dl>
                <div class="lunara-control-desk-review-brief-groups">
                    <?php foreach ( $group_counts as $group => $counts ) : ?>
                        <div class="<?php echo $counts['open'] ? 'has-open' : 'is-clear'; ?>">
                            <strong><?php echo esc_html( $group ); ?></strong>
                            <span><?php echo esc_html( sprintf( __( '%1$d open / %2$d total', 'lunara-film' ), absint( $counts['open'] ), absint( $counts['total'] ) ) ); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </aside>

            <div class="lunara-control-desk-review-brief-next">
                <div class="lunara-control-desk-review-brief-section-head">
                    <h4><?php esc_html_e( 'Next fixes', 'lunara-film' ); ?></h4>
                    <span><?php esc_html_e( 'Read-only. Use the links to edit existing fields.', 'lunara-film' ); ?></span>
                </div>
                <?php if ( empty( $open_checks ) ) : ?>
                    <div class="lunara-control-desk-empty">
                        <p><?php esc_html_e( 'No open blockers or warnings detected for this review.', 'lunara-film' ); ?></p>
                    </div>
                <?php else : ?>
                    <ol>
                        <?php foreach ( $open_checks as $check ) : ?>
                            <li class="is-<?php echo esc_attr( $check['kind'] ); ?>">
                                <div>
                                    <span><?php echo esc_html( lunara_control_desk_review_check_kind_label( $check['kind'] ) ); ?></span>
                                    <strong><?php echo esc_html( $check['label'] ); ?></strong>
                                    <em><?php echo esc_html( $check['value'] ); ?></em>
                                </div>
                                <p><?php echo esc_html( $check['note'] ); ?></p>
                                <div class="lunara-control-desk-review-brief-fix-actions">
                                    <a href="#<?php echo esc_attr( lunara_control_desk_review_check_anchor_id( $post->ID, $check ) ); ?>"><?php esc_html_e( 'Jump to check', 'lunara-film' ); ?></a>
                                    <a href="<?php echo esc_url( $check['fix_url'] ); ?>"><?php echo esc_html( $check['owner'] ); ?></a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
                <details class="lunara-control-desk-review-brief-copy">
                    <summary><?php esc_html_e( 'Copyable brief text', 'lunara-film' ); ?></summary>
                    <pre data-lunara-copy-source><?php echo esc_html( $brief_text ); ?></pre>
                    <button type="button" class="button button-small" data-lunara-copy><?php esc_html_e( 'Copy text', 'lunara-film' ); ?></button>
                </details>
            </div>
        </div>
    </section>
    <?php
}

function lunara_control_desk_render_review_preview_matrix( $post, $checks, $preview, $mobile_url ) {
    $card_check     = lunara_control_desk_get_review_check_by_key( $checks, 'card_image' );
    $hero_check     = lunara_control_desk_get_review_check_by_key( $checks, 'hero_visual' );
    $homepage_check = lunara_control_desk_get_review_check_by_key( $checks, 'homepage_flags' );
    $imdb_check     = lunara_control_desk_get_review_check_by_key( $checks, 'imdb_title_id' );
    $items          = array(
        array(
            'label' => __( 'Review card', 'lunara-film' ),
            'value' => isset( $card_check['value'] ) ? $card_check['value'] : '',
            'note'  => isset( $card_check['note'] ) ? $card_check['note'] : '',
            'kind'  => isset( $card_check['kind'] ) ? $card_check['kind'] : 'weak',
            'url'   => lunara_control_desk_review_editor_link( $post->ID, 'lunara_review_editorial_controls' ),
        ),
        array(
            'label' => __( 'Archive card', 'lunara-film' ),
            'value' => get_post_status( $post ),
            'note'  => __( 'Checks card copy, image, score, and taxonomy readiness.', 'lunara-film' ),
            'kind'  => 'ready',
            'url'   => home_url( '/reviews/' ),
        ),
        array(
            'label' => __( 'Homepage placement', 'lunara-film' ),
            'value' => isset( $homepage_check['value'] ) ? $homepage_check['value'] : '',
            'note'  => isset( $homepage_check['note'] ) ? $homepage_check['note'] : '',
            'kind'  => isset( $homepage_check['kind'] ) ? $homepage_check['kind'] : 'weak',
            'url'   => lunara_control_desk_url( array( 'tab' => 'homepage-board' ) ),
        ),
        array(
            'label' => __( 'Single-review hero', 'lunara-film' ),
            'value' => isset( $hero_check['value'] ) ? $hero_check['value'] : '',
            'note'  => isset( $hero_check['note'] ) ? $hero_check['note'] : '',
            'kind'  => isset( $hero_check['kind'] ) ? $hero_check['kind'] : 'weak',
            'url'   => $preview ? $preview : get_permalink( $post ),
        ),
        array(
            'label' => __( 'Mobile view', 'lunara-film' ),
            'value' => __( '390px target', 'lunara-film' ),
            'note'  => __( 'Open the same draft target for phone-width visual QA.', 'lunara-film' ),
            'kind'  => 'ready',
            'url'   => $mobile_url,
        ),
        array(
            'label' => __( 'Ledger link', 'lunara-film' ),
            'value' => isset( $imdb_check['value'] ) ? $imdb_check['value'] : '',
            'note'  => isset( $imdb_check['note'] ) ? $imdb_check['note'] : '',
            'kind'  => isset( $imdb_check['kind'] ) ? $imdb_check['kind'] : 'weak',
            'url'   => ( ! empty( $imdb_check['value'] ) && preg_match( '/^tt\d{7,9}$/', (string) $imdb_check['value'] ) ) ? home_url( '/oscars/title/' . rawurlencode( strtolower( (string) $imdb_check['value'] ) ) . '/' ) : lunara_control_desk_review_editor_link( $post->ID, 'lunara_review_details_meta' ),
        ),
    );
    ?>
    <div class="lunara-control-desk-review-preview-grid">
        <?php foreach ( $items as $item ) : ?>
            <a class="lunara-control-desk-review-preview is-<?php echo esc_attr( $item['kind'] ); ?>" href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer">
                <strong><?php echo esc_html( $item['label'] ); ?></strong>
                <span><?php echo esc_html( $item['value'] ); ?></span>
                <em><?php echo esc_html( $item['note'] ); ?></em>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
}

function lunara_control_desk_render_review_public_card_preview( $post ) {
    if ( ! $post instanceof WP_Post ) {
        return;
    }

    $post_id     = absint( $post->ID );
    $quote       = function_exists( 'lunara_get_review_card_pull_quote' ) ? lunara_get_review_card_pull_quote( $post_id, 46 ) : '';
    $quote_clean = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $quote ) ) );
    $word_count  = '' === $quote_clean ? 0 : count( preg_split( '/\s+/', $quote_clean ) );
    $quote_kind  = $word_count > 0 ? ( $word_count >= 38 ? 'ready' : 'weak' ) : 'needs';
    $card_html   = function_exists( 'lunara_render_review_grid_card' ) ? lunara_render_review_grid_card( $post_id, 1 ) : '';
    ?>
    <section class="lunara-control-desk-public-card-preview is-<?php echo esc_attr( $quote_kind ); ?>">
        <div class="lunara-control-desk-review-brief-section-head">
            <div>
                <h4><?php esc_html_e( 'Public card preview', 'lunara-film' ); ?></h4>
                <span><?php esc_html_e( 'Read-only. Mirrors the archive/home card chamber that readers see.', 'lunara-film' ); ?></span>
            </div>
            <span class="lunara-control-desk-public-card-status">
                <?php
                if ( $word_count <= 0 ) {
                    esc_html_e( 'Missing quote', 'lunara-film' );
                } elseif ( $word_count < 38 ) {
                    echo esc_html( sprintf( __( 'Weak quote: %d words', 'lunara-film' ), $word_count ) );
                } else {
                    echo esc_html( sprintf( __( 'Ready quote: %d words', 'lunara-film' ), $word_count ) );
                }
                ?>
            </span>
        </div>

        <div class="lunara-control-desk-public-card-grid">
            <div class="lunara-control-desk-public-card-frame">
                <?php if ( '' !== $card_html ) : ?>
                    <div class="lunara-review-archive-shell">
                        <div class="lunara-review-grid lunara-review-archive-uniform">
                            <?php echo $card_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="lunara-control-desk-empty">
                        <p><?php esc_html_e( 'Public review card renderer is unavailable for this post.', 'lunara-film' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="lunara-control-desk-public-card-notes">
                <div>
                    <strong><?php esc_html_e( 'Quote chamber', 'lunara-film' ); ?></strong>
                    <span><?php esc_html_e( 'Seven public lines, aimed at roughly 40-55 chosen words.', 'lunara-film' ); ?></span>
                </div>
                <div>
                    <strong><?php esc_html_e( 'Source field', 'lunara-film' ); ?></strong>
                    <span><?php esc_html_e( 'Card Pull Quote only. Excerpt/body copy does not fill the public card.', 'lunara-film' ); ?></span>
                </div>
                <div>
                    <strong><?php esc_html_e( 'Next action', 'lunara-film' ); ?></strong>
                    <span>
                        <?php
                        echo esc_html(
                            $word_count >= 38
                                ? __( 'Judge the visible rhythm, then publish or continue polish.', 'lunara-film' )
                                : __( 'Open Review Controls and write the exact card pull quote.', 'lunara-film' )
                        );
                        ?>
                    </span>
                </div>
            </aside>
        </div>
    </section>
    <?php
}

function lunara_control_desk_render_review_thumbnail_strip( $post_id ) {
    $items = lunara_control_desk_get_review_thumbnail_items( $post_id );
    ?>
    <div class="lunara-control-desk-review-thumbs" aria-label="<?php echo esc_attr__( 'Review image preview thumbnails', 'lunara-film' ); ?>">
        <?php foreach ( $items as $item ) : ?>
            <?php
            $state      = isset( $item['state'] ) ? sanitize_html_class( $item['state'] ) : 'weak';
            $src        = isset( $item['src'] ) ? (string) $item['src'] : '';
            $label      = isset( $item['label'] ) ? (string) $item['label'] : '';
            $source     = isset( $item['source'] ) ? (string) $item['source'] : '';
            $value      = isset( $item['value'] ) ? (string) $item['value'] : '';
            $dimensions = isset( $item['dimensions'] ) ? (string) $item['dimensions'] : '';
            ?>
            <figure class="lunara-control-desk-review-thumb is-<?php echo esc_attr( $state ); ?>">
                <div class="lunara-control-desk-review-thumb-media">
                    <?php if ( '' !== $src ) : ?>
                        <img src="<?php echo esc_url( $src ); ?>" alt="<?php echo esc_attr( sprintf( __( '%s image preview', 'lunara-film' ), $label ) ); ?>" loading="lazy" decoding="async">
                    <?php else : ?>
                        <span><?php esc_html_e( 'No image', 'lunara-film' ); ?></span>
                    <?php endif; ?>
                </div>
                <figcaption>
                    <strong><?php echo esc_html( $label ); ?></strong>
                    <span><?php echo esc_html( $source ); ?></span>
                    <em><?php echo esc_html( $dimensions ? $dimensions : $value ); ?></em>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
    <?php
}

function lunara_control_desk_render_review_checklist( $checks, $post_id = 0 ) {
    $groups = lunara_control_desk_group_review_checks( $checks );
    ?>
    <div class="lunara-control-desk-review-checklist">
        <?php foreach ( $groups as $group => $group_checks ) : ?>
            <?php
            $open_count = 0;
            foreach ( $group_checks as $check ) {
                if ( 'ready' !== $check['kind'] ) {
                    $open_count++;
                }
            }
            ?>
            <section class="lunara-control-desk-review-check-group">
                <header>
                    <h4><?php echo esc_html( $group ); ?></h4>
                    <span><?php echo esc_html( $open_count ? sprintf( _n( '%d open', '%d open', $open_count, 'lunara-film' ), $open_count ) : __( 'Clear', 'lunara-film' ) ); ?></span>
                </header>
                <ul>
                    <?php foreach ( $group_checks as $check ) : ?>
                        <li id="<?php echo esc_attr( lunara_control_desk_review_check_anchor_id( $post_id, $check ) ); ?>" class="is-<?php echo esc_attr( $check['kind'] ); ?>">
                            <div>
                                <strong><?php echo esc_html( $check['label'] ); ?></strong>
                                <span><?php echo esc_html( $check['value'] ); ?></span>
                            </div>
                            <p><?php echo esc_html( $check['note'] ); ?></p>
                            <a href="<?php echo esc_url( $check['fix_url'] ); ?>"><?php echo esc_html( $check['owner'] ); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>
    <?php
}

function lunara_control_desk_render_review_work_session_panel( $row, $active_filter ) {
    if ( empty( $row['post'] ) || ! $row['post'] instanceof WP_Post ) {
        return;
    }

    $post       = $row['post'];
    $checks     = lunara_control_desk_get_review_pipeline_checks( $post->ID );
    $signals    = lunara_control_desk_review_checks_to_signals( $checks );
    $readiness  = lunara_control_desk_get_readiness( $signals );
    $first_open = lunara_control_desk_get_first_open_review_check( $checks );
    $preview    = function_exists( 'get_preview_post_link' ) ? get_preview_post_link( $post ) : '';
    $permalink  = get_permalink( $post );
    $mobile_url = add_query_arg( 'lunara-width', '390', $preview ? $preview : $permalink );
    ?>
    <section class="lunara-control-desk-review-work-session">
        <div class="lunara-control-desk-review-session-banner">
            <div>
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Work Session', 'lunara-film' ); ?></p>
                <h3><?php echo esc_html( sprintf( __( 'Finish %s', 'lunara-film' ), lunara_control_desk_get_review_title( $post ) ) ); ?></h3>
                <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Focused mode hides the wider queue so the selected review can move field by field.', 'lunara-film' ); ?></p>
            </div>
            <div class="lunara-control-desk-actions">
                <?php if ( ! empty( $first_open ) ) : ?>
                    <a class="button button-secondary" href="#<?php echo esc_attr( lunara_control_desk_review_check_anchor_id( $post->ID, $first_open ) ); ?>"><?php esc_html_e( 'First blocker', 'lunara-film' ); ?></a>
                <?php endif; ?>
                <a class="button" href="<?php echo esc_url( lunara_control_desk_review_brief_url( $post->ID, $active_filter, false ) ); ?>"><?php esc_html_e( 'Back to Pipeline', 'lunara-film' ); ?></a>
                <a class="button button-primary" href="<?php echo esc_url( get_edit_post_link( $post->ID, 'raw' ) ); ?>"><?php esc_html_e( 'Open Editor', 'lunara-film' ); ?></a>
            </div>
        </div>

        <article id="lunara-review-<?php echo esc_attr( $post->ID ); ?>" class="lunara-control-desk-card lunara-control-desk-review-card lunara-control-desk-review-work-card" data-lunara-row-post="<?php echo esc_attr( $post->ID ); ?>">
            <div class="lunara-control-desk-card-head">
                <div>
                    <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Selected Review', 'lunara-film' ); ?></p>
                    <h3><?php echo esc_html( lunara_control_desk_get_review_title( $post ) ); ?></h3>
                    <p class="lunara-control-desk-subtle">ID <?php echo esc_html( $post->ID ); ?> / <?php echo esc_html( get_post_status( $post ) ); ?> / <?php echo esc_html( get_the_modified_date( get_option( 'date_format' ), $post ) ); ?></p>
            </div>
            <?php lunara_control_desk_render_readiness_badge( $readiness ); ?>
        </div>
        <?php lunara_control_desk_render_review_thumbnail_strip( $post->ID ); ?>
        <?php lunara_control_desk_render_review_public_card_preview( $post ); ?>
        <?php lunara_control_desk_render_review_preview_matrix( $post, $checks, $preview, $mobile_url ); ?>
        <?php lunara_control_desk_render_review_checklist( $checks, $post->ID ); ?>
            <div class="lunara-control-desk-review-signal-strip">
                <?php lunara_control_desk_render_signals( $signals ); ?>
            </div>
            <div class="lunara-control-desk-actions">
                <a class="button" href="<?php echo esc_url( lunara_control_desk_review_editor_link( $post->ID, 'lunara_review_editorial_controls' ) ); ?>"><?php esc_html_e( 'Review Controls', 'lunara-film' ); ?></a>
                <a class="button" href="<?php echo esc_url( lunara_control_desk_review_editor_link( $post->ID, 'lunara_review_details_meta' ) ); ?>"><?php esc_html_e( 'Details/IMDb', 'lunara-film' ); ?></a>
                <a class="button" href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Review Archive', 'lunara-film' ); ?></a>
                <?php if ( $preview ) : ?>
                    <a class="button" href="<?php echo esc_url( $preview ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Single Preview', 'lunara-film' ); ?></a>
                <?php endif; ?>
                <a class="button" href="<?php echo esc_url( $mobile_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Mobile Target', 'lunara-film' ); ?></a>
                <?php lunara_control_desk_render_suggest_button( $post->ID, 'package', __( 'Packaging', 'lunara-film' ) ); ?>
                <?php lunara_control_desk_render_suggest_button( $post->ID, 'readiness', __( 'Readiness', 'lunara-film' ) ); ?>
                <?php lunara_control_desk_render_suggest_button( $post->ID, 'ledger_links', __( 'Ledger', 'lunara-film' ) ); ?>
            </div>
            <div class="lunara-control-desk-suggestion-shell" data-lunara-result="<?php echo esc_attr( $post->ID ); ?>" hidden></div>
        </article>
    </section>
    <?php
}

function lunara_control_desk_render_review_pipeline_tab( $rows ) {
    $review_rows   = lunara_control_desk_get_review_pipeline_rows();
    $active_filter = lunara_control_desk_get_active_review_filter();

    usort(
        $review_rows,
        static function ( $a, $b ) {
            $a_score = isset( $a['readiness']['score'] ) ? intval( $a['readiness']['score'] ) : 0;
            $b_score = isset( $b['readiness']['score'] ) ? intval( $b['readiness']['score'] ) : 0;

            return $a_score <=> $b_score;
        }
    );

    $filtered_review_rows = lunara_control_desk_filter_review_rows( $review_rows, $active_filter );
    $filter_definitions   = lunara_control_desk_review_filter_definitions();
    $active_filter_label  = isset( $filter_definitions[ $active_filter ] ) ? $filter_definitions[ $active_filter ]['label'] : __( 'All Reviews', 'lunara-film' );
    $first_open_target    = lunara_control_desk_get_first_open_review_target( $filtered_review_rows );
    $work_session         = lunara_control_desk_is_review_work_session();
    $work_session_row     = $work_session ? lunara_control_desk_get_active_review_brief_row( $filtered_review_rows ) : array();
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Review Pipeline', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Make every review publish-ready before it leaves draft', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'This tab checks the packaging fields that shape review cards, heroes, archives, homepage placement, and Oscar Ledger links.', 'lunara-film' ); ?></p>
        </div>

        <?php if ( empty( $review_rows ) ) : ?>
            <div class="lunara-control-desk-empty"><p><?php esc_html_e( 'No draft or pending reviews are in the queue.', 'lunara-film' ); ?></p></div>
        <?php else : ?>
            <?php if ( ! $work_session ) : ?>
                <?php lunara_control_desk_render_status_cards( lunara_control_desk_get_review_pipeline_summary_cards( $review_rows ) ); ?>
                <?php lunara_control_desk_render_review_filters( $review_rows, $active_filter ); ?>
            <?php endif; ?>
            <?php lunara_control_desk_render_review_brief_panel( $filtered_review_rows, $active_filter ); ?>

            <?php if ( $work_session ) : ?>
                <?php lunara_control_desk_render_review_work_session_panel( $work_session_row, $active_filter ); ?>
            <?php else : ?>
            <div class="lunara-control-desk-review-issues">
                <div class="lunara-control-desk-panel-header">
                    <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Issue Groups', 'lunara-film' ); ?></p>
                    <h3><?php esc_html_e( 'Fix the repeated review blockers first', 'lunara-film' ); ?></h3>
                </div>
                <?php lunara_control_desk_render_review_issue_groups( lunara_control_desk_get_review_issue_groups( $filtered_review_rows ) ); ?>
            </div>

            <div class="lunara-control-desk-filter-context">
                <p>
                    <?php
                    printf(
                        /* translators: 1: filtered row count, 2: active filter label. */
                        esc_html__( 'Showing %1$d reviews for %2$s.', 'lunara-film' ),
                        count( $filtered_review_rows ),
                        esc_html( $active_filter_label )
                    );
                    ?>
                </p>
                <?php if ( ! empty( $first_open_target['url'] ) ) : ?>
                    <a class="button button-secondary" href="<?php echo esc_url( $first_open_target['url'] ); ?>">
                        <?php esc_html_e( 'Start with first blocker', 'lunara-film' ); ?>
                    </a>
                    <span>
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: 1: check label, 2: review title. */
                                __( '%1$s in %2$s', 'lunara-film' ),
                                $first_open_target['label'],
                                $first_open_target['title']
                            )
                        );
                        ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ( empty( $filtered_review_rows ) ) : ?>
                <div class="lunara-control-desk-empty">
                    <p><?php echo esc_html( sprintf( __( 'No review drafts match the %s view right now.', 'lunara-film' ), $active_filter_label ) ); ?></p>
                </div>
            <?php else : ?>
            <div class="lunara-control-desk-card-grid lunara-control-desk-review-grid">
                <?php foreach ( $filtered_review_rows as $row ) : ?>
                    <?php
                    $post       = $row['post'];
                    $checks     = lunara_control_desk_get_review_pipeline_checks( $post->ID );
                    $signals    = lunara_control_desk_review_checks_to_signals( $checks );
                    $readiness  = lunara_control_desk_get_readiness( $signals );
                    $first_open = lunara_control_desk_get_first_open_review_check( $checks );
                    $preview    = function_exists( 'get_preview_post_link' ) ? get_preview_post_link( $post ) : '';
                    $permalink  = get_permalink( $post );
                    $mobile_url = add_query_arg( 'lunara-width', '390', $preview ? $preview : $permalink );
                    ?>
                    <article id="lunara-review-<?php echo esc_attr( $post->ID ); ?>" class="lunara-control-desk-card lunara-control-desk-review-card" data-lunara-row-post="<?php echo esc_attr( $post->ID ); ?>">
                        <div class="lunara-control-desk-card-head">
                            <div>
                                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Review Draft', 'lunara-film' ); ?></p>
                                <h3><?php echo esc_html( get_the_title( $post ) ? get_the_title( $post ) : __( '(Untitled)', 'lunara-film' ) ); ?></h3>
                                <p class="lunara-control-desk-subtle">ID <?php echo esc_html( $post->ID ); ?> / <?php echo esc_html( get_post_status( $post ) ); ?> / <?php echo esc_html( get_the_modified_date( get_option( 'date_format' ), $post ) ); ?></p>
                            </div>
                            <?php lunara_control_desk_render_readiness_badge( $readiness ); ?>
                        </div>
                        <?php lunara_control_desk_render_review_thumbnail_strip( $post->ID ); ?>
                        <?php lunara_control_desk_render_review_preview_matrix( $post, $checks, $preview, $mobile_url ); ?>
                        <?php lunara_control_desk_render_review_checklist( $checks, $post->ID ); ?>
                        <div class="lunara-control-desk-review-signal-strip">
                            <?php lunara_control_desk_render_signals( $signals ); ?>
                        </div>
                        <div class="lunara-control-desk-actions">
                            <?php if ( ! empty( $first_open ) ) : ?>
                                <a class="button button-secondary" href="#<?php echo esc_attr( lunara_control_desk_review_check_anchor_id( $post->ID, $first_open ) ); ?>"><?php esc_html_e( 'First blocker', 'lunara-film' ); ?></a>
                            <?php endif; ?>
                            <a class="button button-secondary" href="<?php echo esc_url( lunara_control_desk_review_brief_url( $post->ID, $active_filter ) ); ?>"><?php esc_html_e( 'Packaging Brief', 'lunara-film' ); ?></a>
                            <a class="button button-secondary" href="<?php echo esc_url( lunara_control_desk_review_work_session_url( $post->ID, $active_filter ) ); ?>"><?php esc_html_e( 'Work Session', 'lunara-film' ); ?></a>
                            <a class="button button-primary" href="<?php echo esc_url( get_edit_post_link( $post->ID, 'raw' ) ); ?>"><?php esc_html_e( 'Open Editor', 'lunara-film' ); ?></a>
                            <a class="button" href="<?php echo esc_url( lunara_control_desk_review_editor_link( $post->ID, 'lunara_review_editorial_controls' ) ); ?>"><?php esc_html_e( 'Review Controls', 'lunara-film' ); ?></a>
                            <a class="button" href="<?php echo esc_url( lunara_control_desk_review_editor_link( $post->ID, 'lunara_review_details_meta' ) ); ?>"><?php esc_html_e( 'Details/IMDb', 'lunara-film' ); ?></a>
                            <a class="button" href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Review Archive', 'lunara-film' ); ?></a>
                            <?php if ( $preview ) : ?>
                                <a class="button" href="<?php echo esc_url( $preview ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Single Preview', 'lunara-film' ); ?></a>
                            <?php endif; ?>
                            <a class="button" href="<?php echo esc_url( $mobile_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Mobile Target', 'lunara-film' ); ?></a>
                            <?php lunara_control_desk_render_suggest_button( $post->ID, 'package', __( 'Packaging', 'lunara-film' ) ); ?>
                            <?php lunara_control_desk_render_suggest_button( $post->ID, 'readiness', __( 'Readiness', 'lunara-film' ) ); ?>
                            <?php lunara_control_desk_render_suggest_button( $post->ID, 'ledger_links', __( 'Ledger', 'lunara-film' ) ); ?>
                        </div>
                        <div class="lunara-control-desk-suggestion-shell" data-lunara-result="<?php echo esc_attr( $post->ID ); ?>" hidden></div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </section>
    <?php
}

function lunara_control_desk_theme_studio_groups() {
    return array(
        array(
            'label'    => __( 'Header', 'lunara-film' ),
            'section'  => 'lunara_header_options',
            'preview'  => home_url( '/' ),
            'owner'    => __( 'Theme Customizer', 'lunara-film' ),
            'note'     => __( 'Logo, sticky shell, and top-level navigation behavior.', 'lunara-film' ),
            'renders'  => array( __( 'Every public page header', 'lunara-film' ), __( 'Desktop navigation', 'lunara-film' ), __( 'Mobile shell', 'lunara-film' ) ),
            'settings' => array( 'lunara_show_logo', 'lunara_logo_max_height', 'lunara_sticky_header' ),
        ),
        array(
            'label'    => __( 'Global Design', 'lunara-film' ),
            'section'  => 'lunara_global_design_options',
            'preview'  => home_url( '/' ),
            'owner'    => __( 'Theme runtime CSS', 'lunara-film' ),
            'note'     => __( 'Typography, accent color, and site-wide visual defaults.', 'lunara-film' ),
            'renders'  => array( __( 'Home', 'lunara-film' ), __( 'Reviews', 'lunara-film' ), __( 'Journal', 'lunara-film' ), __( 'Oscars', 'lunara-film' ), __( 'Singles', 'lunara-film' ) ),
            'settings' => array( 'lunara_body_font_size', 'lunara_heading_font_family', 'lunara_accent_color' ),
        ),
        array(
            'label'    => __( 'Home', 'lunara-film' ),
            'section'  => 'lunara_homepage_pulse_options',
            'preview'  => home_url( '/' ),
            'owner'    => __( 'Homepage section system', 'lunara-film' ),
            'note'     => __( 'Home hero, Latest Reviews, Journal, and Ledger lane labels.', 'lunara-film' ),
            'renders'  => array( __( 'Homepage hero', 'lunara-film' ), __( 'Latest Reviews lane', 'lunara-film' ), __( 'Journal lane', 'lunara-film' ), __( 'Ledger lane', 'lunara-film' ) ),
            'settings' => array( 'lunara_home_hero_title', 'lunara_home_reviews_heading', 'lunara_home_oscars_heading' ),
        ),
        array(
            'label'    => __( 'Reviews', 'lunara-film' ),
            'section'  => 'lunara_review_layout_options',
            'preview'  => home_url( '/reviews/' ),
            'owner'    => __( 'Review rendering module', 'lunara-film' ),
            'note'     => __( 'Archive density, single-review hero geometry, and related-review behavior.', 'lunara-film' ),
            'renders'  => array( __( 'Reviews archive', 'lunara-film' ), __( 'Review cards', 'lunara-film' ), __( 'Single-review hero', 'lunara-film' ), __( 'Related reviews', 'lunara-film' ), __( 'Home Latest Reviews', 'lunara-film' ) ),
            'settings' => array( 'lunara_review_layout_mode', 'lunara_review_related_count', 'lunara_review_hero_height' ),
        ),
        array(
            'label'    => __( 'Journal', 'lunara-film' ),
            'section'  => 'lunara_standard_post_options',
            'preview'  => home_url( '/journal/' ),
            'owner'    => __( 'Journal CPT and editorial archive', 'lunara-film' ),
            'note'     => __( 'Journal labeling, archive intro, and single-entry presentation.', 'lunara-film' ),
            'renders'  => array( __( 'Journal archive', 'lunara-film' ), __( 'Journal singles', 'lunara-film' ), __( 'Home Journal lane', 'lunara-film' ), __( 'Journal cards', 'lunara-film' ) ),
            'settings' => array( 'lunara_post_label', 'lunara_journal_single_hero_title_size', 'lunara_archive_intro' ),
        ),
        array(
            'label'    => __( 'Oscars', 'lunara-film' ),
            'section'  => 'lunara_oscars_portal_options',
            'preview'  => home_url( '/oscars/' ),
            'owner'    => __( 'Theme portal plus Academy plugin', 'lunara-film' ),
            'note'     => __( 'Portal framing while ledger data remains owned by the Academy plugin.', 'lunara-film' ),
            'renders'  => array( __( 'Oscars portal', 'lunara-film' ), __( 'Home Ledger lane', 'lunara-film' ), __( 'Ceremony pages', 'lunara-film' ), __( 'Category pages', 'lunara-film' ), __( 'Title/person pages', 'lunara-film' ) ),
            'settings' => array( 'lunara_oscars_portal_title', 'lunara_oscars_portal_intro', 'lunara_home_ledger_heading' ),
        ),
        array(
            'label'    => __( 'Footer', 'lunara-film' ),
            'section'  => 'lunara_footer_options',
            'preview'  => home_url( '/' ),
            'owner'    => __( 'Custom Lunara footer renderer', 'lunara-film' ),
            'note'     => __( 'Footer tagline, utility columns, copyright, and social display.', 'lunara-film' ),
            'renders'  => array( __( 'Every public footer', 'lunara-film' ), __( 'Editorial links', 'lunara-film' ), __( 'Ledger links', 'lunara-film' ), __( 'Utility links', 'lunara-film' ) ),
            'settings' => array( 'lunara_footer_tagline', 'lunara_footer_copyright', 'lunara_footer_show_social' ),
        ),
        array(
            'label'    => __( 'Mobile', 'lunara-film' ),
            'section'  => 'lunara_mobile_menu_options',
            'preview'  => home_url( '/' ),
            'owner'    => __( 'Header/mobile menu controls', 'lunara-film' ),
            'note'     => __( 'Mobile panel width, link scale, and menu direction.', 'lunara-film' ),
            'renders'  => array( __( 'Mobile header', 'lunara-film' ), __( 'Mobile menu panel', 'lunara-film' ), __( '390px QA targets', 'lunara-film' ) ),
            'settings' => array( 'lunara_mobile_panel_width', 'lunara_mobile_link_size', 'lunara_mobile_panel_direction' ),
        ),
    );
}

function lunara_control_desk_theme_mod_value_summary( $setting ) {
    $value = get_theme_mod( $setting, null );

    if ( null === $value || '' === $value ) {
        return __( 'default', 'lunara-film' );
    }

    if ( is_bool( $value ) ) {
        return $value ? __( 'on', 'lunara-film' ) : __( 'off', 'lunara-film' );
    }

    if ( is_array( $value ) ) {
        return sprintf( __( '%d values', 'lunara-film' ), count( $value ) );
    }

    $value = wp_strip_all_tags( (string) $value );
    return mb_strlen( $value ) > 42 ? mb_substr( $value, 0, 42 ) . '...' : $value;
}

function lunara_control_desk_theme_mod_has_custom_value( $setting ) {
    $value = get_theme_mod( $setting, null );

    return null !== $value && '' !== $value;
}

function lunara_control_desk_theme_studio_setting_copy( $setting ) {
    $copy = array(
        'lunara_show_logo'                       => array(
            'label' => __( 'Logo visibility', 'lunara-film' ),
            'hint'  => __( 'Turns the Lunara mark on or off in the header.', 'lunara-film' ),
        ),
        'lunara_logo_max_height'                 => array(
            'label' => __( 'Logo height', 'lunara-film' ),
            'hint'  => __( 'Controls how tall the header logo is allowed to render.', 'lunara-film' ),
        ),
        'lunara_sticky_header'                   => array(
            'label' => __( 'Sticky header', 'lunara-film' ),
            'hint'  => __( 'Keeps the top navigation available while scrolling.', 'lunara-film' ),
        ),
        'lunara_body_font_size'                  => array(
            'label' => __( 'Body text size', 'lunara-film' ),
            'hint'  => __( 'Changes the baseline reading size across public pages.', 'lunara-film' ),
        ),
        'lunara_heading_font_family'             => array(
            'label' => __( 'Heading typeface', 'lunara-film' ),
            'hint'  => __( 'Controls the display font used for major headings.', 'lunara-film' ),
        ),
        'lunara_accent_color'                    => array(
            'label' => __( 'Accent color', 'lunara-film' ),
            'hint'  => __( 'Feeds the main gold/accent treatment for buttons and highlights.', 'lunara-film' ),
        ),
        'lunara_home_hero_title'                 => array(
            'label' => __( 'Homepage hero title', 'lunara-film' ),
            'hint'  => __( 'Changes the first large editorial title on the homepage.', 'lunara-film' ),
        ),
        'lunara_home_reviews_heading'            => array(
            'label' => __( 'Home reviews heading', 'lunara-film' ),
            'hint'  => __( 'Labels the Latest Reviews lane on the homepage.', 'lunara-film' ),
        ),
        'lunara_home_oscars_heading'             => array(
            'label' => __( 'Home Oscars heading', 'lunara-film' ),
            'hint'  => __( 'Labels the homepage Ledger or Oscars lane.', 'lunara-film' ),
        ),
        'lunara_review_layout_mode'              => array(
            'label' => __( 'Review archive layout', 'lunara-film' ),
            'hint'  => __( 'Controls the density and presentation mode for review cards.', 'lunara-film' ),
        ),
        'lunara_review_related_count'            => array(
            'label' => __( 'Related review count', 'lunara-film' ),
            'hint'  => __( 'Sets how many related reviews appear on single review pages.', 'lunara-film' ),
        ),
        'lunara_review_hero_height'              => array(
            'label' => __( 'Review hero height', 'lunara-film' ),
            'hint'  => __( 'Controls the vertical size of single-review hero areas.', 'lunara-film' ),
        ),
        'lunara_post_label'                      => array(
            'label' => __( 'Journal label', 'lunara-film' ),
            'hint'  => __( 'Sets the public label for Journal/post surfaces.', 'lunara-film' ),
        ),
        'lunara_journal_single_hero_title_size'  => array(
            'label' => __( 'Journal title size', 'lunara-film' ),
            'hint'  => __( 'Controls the single Journal hero title scale.', 'lunara-film' ),
        ),
        'lunara_archive_intro'                   => array(
            'label' => __( 'Archive intro copy', 'lunara-film' ),
            'hint'  => __( 'Sets the short introduction for archive-style pages.', 'lunara-film' ),
        ),
        'lunara_oscars_portal_title'             => array(
            'label' => __( 'Oscars portal title', 'lunara-film' ),
            'hint'  => __( 'Changes the main heading on the Oscars portal.', 'lunara-film' ),
        ),
        'lunara_oscars_portal_intro'             => array(
            'label' => __( 'Oscars portal intro', 'lunara-film' ),
            'hint'  => __( 'Sets the intro copy that frames the Ledger.', 'lunara-film' ),
        ),
        'lunara_home_ledger_heading'             => array(
            'label' => __( 'Home Ledger heading', 'lunara-film' ),
            'hint'  => __( 'Labels the homepage Academy/Oscars Ledger lane.', 'lunara-film' ),
        ),
        'lunara_footer_tagline'                  => array(
            'label' => __( 'Footer tagline', 'lunara-film' ),
            'hint'  => __( 'Controls the short brand line in the footer.', 'lunara-film' ),
        ),
        'lunara_footer_copyright'                => array(
            'label' => __( 'Footer copyright', 'lunara-film' ),
            'hint'  => __( 'Sets the legal/copyright text in the footer.', 'lunara-film' ),
        ),
        'lunara_footer_show_social'              => array(
            'label' => __( 'Footer social links', 'lunara-film' ),
            'hint'  => __( 'Shows or hides social-link treatment in the footer.', 'lunara-film' ),
        ),
        'lunara_mobile_panel_width'              => array(
            'label' => __( 'Mobile menu width', 'lunara-film' ),
            'hint'  => __( 'Controls how wide the mobile navigation panel feels.', 'lunara-film' ),
        ),
        'lunara_mobile_link_size'                => array(
            'label' => __( 'Mobile link size', 'lunara-film' ),
            'hint'  => __( 'Sets tap-target text size inside the mobile menu.', 'lunara-film' ),
        ),
        'lunara_mobile_panel_direction'          => array(
            'label' => __( 'Mobile panel direction', 'lunara-film' ),
            'hint'  => __( 'Controls which direction the mobile menu enters from.', 'lunara-film' ),
        ),
    );

    if ( isset( $copy[ $setting ] ) ) {
        return $copy[ $setting ];
    }

    return array(
        'label' => ucwords( str_replace( '_', ' ', preg_replace( '/^lunara_/', '', $setting ) ) ),
        'hint'  => __( 'Existing Customizer setting.', 'lunara-film' ),
    );
}

function lunara_control_desk_theme_studio_group_anchor( $group ) {
    $label = isset( $group['label'] ) ? $group['label'] : 'theme';

    return 'lunara-theme-studio-' . sanitize_html_class( sanitize_title( $label ) );
}

function lunara_control_desk_theme_studio_group_counts( $group ) {
    $settings = isset( $group['settings'] ) && is_array( $group['settings'] ) ? $group['settings'] : array();
    $custom   = 0;

    foreach ( $settings as $setting ) {
        if ( lunara_control_desk_theme_mod_has_custom_value( $setting ) ) {
            $custom++;
        }
    }

    return array(
        'custom'  => $custom,
        'default' => max( 0, count( $settings ) - $custom ),
        'total'   => count( $settings ),
    );
}

function lunara_control_desk_theme_studio_summary_cards( $groups ) {
    $total_settings = 0;
    $custom_values  = 0;

    foreach ( $groups as $group ) {
        $counts          = lunara_control_desk_theme_studio_group_counts( $group );
        $total_settings += $counts['total'];
        $custom_values  += $counts['custom'];
    }

    return array(
        array(
            'label' => __( 'Control groups', 'lunara-film' ),
            'value' => (string) count( $groups ),
            'state' => 'ready',
            'note'  => __( 'Mapped to existing theme ownership areas.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Mapped controls', 'lunara-film' ),
            'value' => (string) $total_settings,
            'state' => 'ready',
            'note'  => __( 'Read-only view of current theme mod values.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Custom values', 'lunara-film' ),
            'value' => sprintf( '%1$d / %2$d', $custom_values, $total_settings ),
            'state' => $custom_values ? 'ready' : 'weak',
            'note'  => __( 'Values saved away from defaults.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Storage model', 'lunara-film' ),
            'value' => __( 'No duplicates', 'lunara-film' ),
            'state' => 'ready',
            'note'  => __( 'Theme Studio links to existing Customizer storage.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_theme_studio_layout_targets() {
    return array(
        array(
            'label'   => __( 'Home', 'lunara-film' ),
            'url'     => home_url( '/' ),
            'section' => 'lunara_homepage_pulse_options',
            'note'    => __( 'Hero, Latest Reviews, Journal, and Ledger lane decisions.', 'lunara-film' ),
        ),
        array(
            'label'   => __( 'Reviews', 'lunara-film' ),
            'url'     => home_url( '/reviews/' ),
            'section' => 'lunara_review_layout_options',
            'note'    => __( 'Archive grid, card rhythm, and review packaging behavior.', 'lunara-film' ),
        ),
        array(
            'label'   => __( 'Journal', 'lunara-film' ),
            'url'     => home_url( '/journal/' ),
            'section' => 'lunara_standard_post_options',
            'note'    => __( 'Magazine archive density, labels, and reader entry points.', 'lunara-film' ),
        ),
        array(
            'label'   => __( 'Oscars', 'lunara-film' ),
            'url'     => home_url( '/oscars/' ),
            'section' => 'lunara_oscars_portal_options',
            'note'    => __( 'Portal framing around the Academy Awards Ledger product.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_render_theme_studio_layout_targets() {
    ?>
    <section class="lunara-control-desk-studio-layout-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Layout Sync', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Public surfaces to check when a theme control changes', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'These are verification targets only. They do not change settings or public content.', 'lunara-film' ); ?></p>
        </div>
        <div class="lunara-control-desk-studio-layout-grid">
            <?php foreach ( lunara_control_desk_theme_studio_layout_targets() as $target ) : ?>
                <?php $mobile_url = add_query_arg( 'lunara-width', '390', $target['url'] ); ?>
                <article>
                    <div>
                        <strong><?php echo esc_html( $target['label'] ); ?></strong>
                        <span><?php echo esc_html( $target['note'] ); ?></span>
                    </div>
                    <div class="lunara-control-desk-actions">
                        <a class="button button-small" href="<?php echo esc_url( $target['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Desktop', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( $mobile_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Mobile', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( lunara_control_desk_customizer_url( $target['section'] ) ); ?>"><?php esc_html_e( 'Controls', 'lunara-film' ); ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function lunara_control_desk_theme_studio_mobile_checks() {
    return array(
        array(
            'label'   => __( 'Header + Menu', 'lunara-film' ),
            'url'     => home_url( '/' ),
            'section' => 'lunara_mobile_menu_options',
            'checks'  => array(
                __( 'Logo remains readable without stretching the header.', 'lunara-film' ),
                __( 'Menu trigger and panel links stay easy to tap.', 'lunara-film' ),
                __( 'Search, nav, and account chrome do not spill horizontally.', 'lunara-film' ),
            ),
        ),
        array(
            'label'   => __( 'Home Lanes', 'lunara-film' ),
            'url'     => home_url( '/' ),
            'section' => 'lunara_homepage_pulse_options',
            'checks'  => array(
                __( 'Hero copy wraps cleanly above the first content lane.', 'lunara-film' ),
                __( 'Review, Journal, and Ledger cards keep visible media.', 'lunara-film' ),
                __( 'Lane headings and action links stay inside the viewport.', 'lunara-film' ),
            ),
        ),
        array(
            'label'   => __( 'Review Cards', 'lunara-film' ),
            'url'     => home_url( '/reviews/' ),
            'section' => 'lunara_review_layout_options',
            'checks'  => array(
                __( '2:3 card images stay locked without original-size swaps.', 'lunara-film' ),
                __( 'Filters, sort links, and review titles wrap without clipping.', 'lunara-film' ),
                __( 'Single-review hero and debrief modules stack cleanly.', 'lunara-film' ),
            ),
        ),
        array(
            'label'   => __( 'Journal Flow', 'lunara-film' ),
            'url'     => home_url( '/journal/' ),
            'section' => 'lunara_standard_post_options',
            'checks'  => array(
                __( 'Archive cards preserve a magazine rhythm on one column.', 'lunara-film' ),
                __( 'Single-entry title scale does not crowd the hero image.', 'lunara-film' ),
                __( 'Related Journal cards and metadata stay readable.', 'lunara-film' ),
            ),
        ),
        array(
            'label'   => __( 'Oscars Ledger', 'lunara-film' ),
            'url'     => home_url( '/oscars/' ),
            'section' => 'lunara_oscars_portal_options',
            'checks'  => array(
                __( 'Ledger cards, tables, and navigation avoid horizontal overflow.', 'lunara-film' ),
                __( 'Poster ratios remain stable in portal and entity modules.', 'lunara-film' ),
                __( 'Ceremony/category/title/person paths keep usable tap targets.', 'lunara-film' ),
            ),
        ),
        array(
            'label'   => __( 'Footer', 'lunara-film' ),
            'url'     => home_url( '/' ),
            'section' => 'lunara_footer_options',
            'checks'  => array(
                __( 'Footer columns stack in a readable order.', 'lunara-film' ),
                __( 'Editorial, Ledger, and utility links retain Lunara styling.', 'lunara-film' ),
                __( 'Tagline, copyright, and social controls do not collide.', 'lunara-film' ),
            ),
        ),
    );
}

function lunara_control_desk_render_theme_studio_mobile_checks() {
    ?>
    <section class="lunara-control-desk-studio-mobile-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Mobile QA', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Checklist for 390px customization passes', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Use this after changing visual controls. It links to the public surface and the existing Customizer section only.', 'lunara-film' ); ?></p>
        </div>
        <div class="lunara-control-desk-studio-mobile-grid">
            <?php foreach ( lunara_control_desk_theme_studio_mobile_checks() as $item ) : ?>
                <?php $mobile_url = add_query_arg( 'lunara-width', '390', $item['url'] ); ?>
                <article>
                    <div class="lunara-control-desk-studio-mobile-head">
                        <strong><?php echo esc_html( $item['label'] ); ?></strong>
                        <span><?php esc_html_e( '390px QA lane', 'lunara-film' ); ?></span>
                    </div>
                    <ul>
                        <?php foreach ( $item['checks'] as $check ) : ?>
                            <li><?php echo esc_html( $check ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="lunara-control-desk-actions">
                        <a class="button button-small" href="<?php echo esc_url( $mobile_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '390px', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Desktop', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( lunara_control_desk_customizer_url( $item['section'] ) ); ?>"><?php esc_html_e( 'Controls', 'lunara-film' ); ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function lunara_control_desk_theme_studio_goal_guides() {
    return array(
        array(
            'goal'    => __( 'Make mobile cleaner', 'lunara-film' ),
            'section' => 'lunara_mobile_menu_options',
            'preview' => home_url( '/' ),
            'touch'   => array(
                __( 'Mobile menu width', 'lunara-film' ),
                __( 'Mobile link size', 'lunara-film' ),
                __( 'Logo height', 'lunara-film' ),
            ),
            'watch'   => __( 'Header height, menu tap targets, and any sideways overflow at 390px.', 'lunara-film' ),
        ),
        array(
            'goal'    => __( 'Make reviews feel more premium', 'lunara-film' ),
            'section' => 'lunara_review_layout_options',
            'preview' => home_url( '/reviews/' ),
            'touch'   => array(
                __( 'Review archive layout', 'lunara-film' ),
                __( 'Review hero height', 'lunara-film' ),
                __( 'Related review count', 'lunara-film' ),
            ),
            'watch'   => __( 'Poster rhythm, quote readability, card spacing, and single-review hero balance.', 'lunara-film' ),
        ),
        array(
            'goal'    => __( 'Make Journal easier to read', 'lunara-film' ),
            'section' => 'lunara_standard_post_options',
            'preview' => home_url( '/journal/' ),
            'touch'   => array(
                __( 'Journal label', 'lunara-film' ),
                __( 'Journal title size', 'lunara-film' ),
                __( 'Archive intro copy', 'lunara-film' ),
            ),
            'watch'   => __( 'Single-entry title scale, archive card rhythm, and whether the first paragraph arrives quickly.', 'lunara-film' ),
        ),
        array(
            'goal'    => __( 'Tune the homepage', 'lunara-film' ),
            'section' => 'lunara_homepage_pulse_options',
            'preview' => home_url( '/' ),
            'touch'   => array(
                __( 'Homepage hero title', 'lunara-film' ),
                __( 'Home reviews heading', 'lunara-film' ),
                __( 'Home Ledger heading', 'lunara-film' ),
            ),
            'watch'   => __( 'First viewport hierarchy, lane names, and whether the next section peeks in cleanly.', 'lunara-film' ),
        ),
        array(
            'goal'    => __( 'Clarify the Oscars Ledger', 'lunara-film' ),
            'section' => 'lunara_oscars_portal_options',
            'preview' => home_url( '/oscars/' ),
            'touch'   => array(
                __( 'Oscars portal title', 'lunara-film' ),
                __( 'Oscars portal intro', 'lunara-film' ),
                __( 'Home Ledger heading', 'lunara-film' ),
            ),
            'watch'   => __( 'Portal framing, database-module density, poster ratios, and category/entity navigation.', 'lunara-film' ),
        ),
        array(
            'goal'    => __( 'Polish site-wide tone', 'lunara-film' ),
            'section' => 'lunara_global_design_options',
            'preview' => home_url( '/' ),
            'touch'   => array(
                __( 'Body text size', 'lunara-film' ),
                __( 'Heading typeface', 'lunara-film' ),
                __( 'Accent color', 'lunara-film' ),
            ),
            'watch'   => __( 'Reading comfort, heading authority, button contrast, and consistency across Home, Reviews, Journal, and Oscars.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_render_theme_studio_goal_guide() {
    ?>
    <section class="lunara-control-desk-studio-goal-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'What Should I Touch?', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Plain-English starting points for customization', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Pick the goal that matches what feels off. Each card points to existing controls and the page to preview afterward.', 'lunara-film' ); ?></p>
        </div>
        <div class="lunara-control-desk-studio-goal-grid">
            <?php foreach ( lunara_control_desk_theme_studio_goal_guides() as $guide ) : ?>
                <?php $mobile_preview = add_query_arg( 'lunara-width', '390', $guide['preview'] ); ?>
                <article>
                    <div class="lunara-control-desk-studio-goal-head">
                        <strong><?php echo esc_html( $guide['goal'] ); ?></strong>
                        <span><?php esc_html_e( 'Start here', 'lunara-film' ); ?></span>
                    </div>
                    <div class="lunara-control-desk-studio-goal-touch">
                        <b><?php esc_html_e( 'Controls to check', 'lunara-film' ); ?></b>
                        <div>
                            <?php foreach ( $guide['touch'] as $control ) : ?>
                                <span><?php echo esc_html( $control ); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <p><?php echo esc_html( $guide['watch'] ); ?></p>
                    <div class="lunara-control-desk-actions">
                        <a class="button button-primary button-small" href="<?php echo esc_url( lunara_control_desk_customizer_url( $guide['section'] ) ); ?>"><?php esc_html_e( 'Open Controls', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( $guide['preview'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Preview', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( $mobile_preview ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '390px', 'lunara-film' ); ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function lunara_control_desk_theme_studio_visual_evidence() {
    $artifact_root        = get_option( 'lunara_control_desk_last_visual_qa_path', 'C:\\Users\\silve_i21do49\\OneDrive\\Documents\\New project\\output\\lunara-os-phase1-visual-qa-final' );
    $theme_studio_capture = 'C:\\Users\\silve_i21do49\\OneDrive\\Documents\\New project\\output\\lunara-os-phase3-5-goal-guide-browser.png';
    $checked             = get_option( 'lunara_control_desk_last_visual_qa_checked', '2026-05-28' );

    return array(
        array(
            'label'    => __( 'Home', 'lunara-film' ),
            'status'   => __( 'Responsive pass', 'lunara-film' ),
            'url'      => home_url( '/' ),
            'artifact' => $artifact_root,
            'checked'  => $checked,
            'note'     => __( 'Use this after Header, Global Design, Home, Footer, or Mobile changes.', 'lunara-film' ),
        ),
        array(
            'label'    => __( 'Reviews', 'lunara-film' ),
            'status'   => __( 'Responsive pass', 'lunara-film' ),
            'url'      => home_url( '/reviews/' ),
            'artifact' => $artifact_root,
            'checked'  => $checked,
            'note'     => __( 'Use this after Review layout, card-image, typography, or mobile spacing changes.', 'lunara-film' ),
        ),
        array(
            'label'    => __( 'Journal', 'lunara-film' ),
            'status'   => __( 'Responsive pass', 'lunara-film' ),
            'url'      => home_url( '/journal/' ),
            'artifact' => $artifact_root,
            'checked'  => $checked,
            'note'     => __( 'Use this after Journal label, archive intro, single-title scale, or homepage lane changes.', 'lunara-film' ),
        ),
        array(
            'label'    => __( 'Oscars', 'lunara-film' ),
            'status'   => __( 'Responsive pass', 'lunara-film' ),
            'url'      => home_url( '/oscars/' ),
            'artifact' => $artifact_root,
            'checked'  => $checked,
            'note'     => __( 'Use this after Oscars portal, Ledger lane, poster-ratio, or navigation changes.', 'lunara-film' ),
        ),
        array(
            'label'    => __( 'Review Single', 'lunara-film' ),
            'status'   => __( 'Sample checked', 'lunara-film' ),
            'url'      => home_url( '/reviews/passenger/' ),
            'artifact' => $artifact_root,
            'checked'  => $checked,
            'note'     => __( 'Use this after review hero, debrief, related-review, or poster sizing changes.', 'lunara-film' ),
        ),
        array(
            'label'    => __( 'Theme Studio Admin', 'lunara-film' ),
            'status'   => __( 'Admin capture', 'lunara-film' ),
            'url'      => admin_url( 'admin.php?page=lunara-control-desk&tab=theme-studio' ),
            'artifact' => $theme_studio_capture,
            'checked'  => $checked,
            'note'     => __( 'Use this after Control Desk layout, labels, or admin density changes.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_render_theme_studio_visual_evidence() {
    $visual_qa_url = admin_url( 'admin.php?page=lunara-control-desk&tab=visual-qa' );
    ?>
    <section class="lunara-control-desk-studio-evidence-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Visual Evidence', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Last checked surfaces before you customize', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'These are local browser-QA artifacts and exact preview targets. They do not change the site.', 'lunara-film' ); ?></p>
        </div>
        <div class="lunara-control-desk-studio-evidence-grid">
            <?php foreach ( lunara_control_desk_theme_studio_visual_evidence() as $item ) : ?>
                <?php $mobile_url = add_query_arg( 'lunara-width', '390', $item['url'] ); ?>
                <article>
                    <div class="lunara-control-desk-studio-evidence-head">
                        <strong><?php echo esc_html( $item['label'] ); ?></strong>
                        <span><?php echo esc_html( $item['status'] ); ?></span>
                    </div>
                    <div class="lunara-control-desk-studio-evidence-meta">
                        <div>
                            <b><?php esc_html_e( 'Last checked', 'lunara-film' ); ?></b>
                            <span><?php echo esc_html( $item['checked'] ); ?></span>
                        </div>
                        <div>
                            <b><?php esc_html_e( 'Artifact', 'lunara-film' ); ?></b>
                            <code><?php echo esc_html( $item['artifact'] ); ?></code>
                        </div>
                    </div>
                    <p><?php echo esc_html( $item['note'] ); ?></p>
                    <div class="lunara-control-desk-actions">
                        <a class="button button-small" href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Preview', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( $mobile_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '390px', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( $visual_qa_url ); ?>"><?php esc_html_e( 'Visual QA', 'lunara-film' ); ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function lunara_control_desk_theme_studio_glossary_terms() {
    return array(
        array(
            'term'    => __( 'Hero', 'lunara-film' ),
            'meaning' => __( 'The large first impression area at the top of a page or single entry.', 'lunara-film' ),
            'where'   => __( 'Home, review singles, Journal singles, Oscars portal.', 'lunara-film' ),
        ),
        array(
            'term'    => __( 'Lane', 'lunara-film' ),
            'meaning' => __( 'A homepage section that groups one kind of content, like Latest Reviews or the Ledger.', 'lunara-film' ),
            'where'   => __( 'Homepage only, especially Reviews, Journal, and Oscars rows.', 'lunara-film' ),
        ),
        array(
            'term'    => __( 'Card', 'lunara-film' ),
            'meaning' => __( 'A repeated preview tile with image, title, quote, metadata, or link behavior.', 'lunara-film' ),
            'where'   => __( 'Review archive, Journal archive, homepage lanes, related modules.', 'lunara-film' ),
        ),
        array(
            'term'    => __( 'Archive', 'lunara-film' ),
            'meaning' => __( 'A listing page for many entries of the same kind.', 'lunara-film' ),
            'where'   => __( 'Reviews, Journal, category-style pages, and some Ledger lists.', 'lunara-film' ),
        ),
        array(
            'term'    => __( 'Single', 'lunara-film' ),
            'meaning' => __( 'One individual entry page, like one review or one Journal piece.', 'lunara-film' ),
            'where'   => __( 'Review pages, Journal entries, title/person Ledger pages.', 'lunara-film' ),
        ),
        array(
            'term'    => __( 'Ledger', 'lunara-film' ),
            'meaning' => __( 'The Oscars database product: ceremonies, categories, titles, people, posters, and links.', 'lunara-film' ),
            'where'   => __( 'Oscars portal, ceremony pages, category pages, title pages, person pages.', 'lunara-film' ),
        ),
        array(
            'term'    => __( '390px', 'lunara-film' ),
            'meaning' => __( 'The phone-width QA target used to catch mobile layout problems fast.', 'lunara-film' ),
            'where'   => __( 'Mobile preview links throughout Theme Studio and Reviews.', 'lunara-film' ),
        ),
        array(
            'term'    => __( 'Featured image', 'lunara-film' ),
            'meaning' => __( 'The WordPress primary image for a post; Lunara may use it as a fallback.', 'lunara-film' ),
            'where'   => __( 'Review cards, Journal cards, single heroes, homepage lanes.', 'lunara-film' ),
        ),
        array(
            'term'    => __( 'Card image override', 'lunara-film' ),
            'meaning' => __( 'A Lunara review-specific image that can replace the featured image in card surfaces.', 'lunara-film' ),
            'where'   => __( 'Review archive cards, homepage review cards, related review cards.', 'lunara-film' ),
        ),
        array(
            'term'    => __( 'Theme mod', 'lunara-film' ),
            'meaning' => __( 'WordPress Customizer storage for a theme setting. Theme Studio shows it, but does not edit it directly.', 'lunara-film' ),
            'where'   => __( 'The small technical keys shown under friendly setting labels.', 'lunara-film' ),
        ),
        array(
            'term'    => __( 'Customizer section', 'lunara-film' ),
            'meaning' => __( 'The exact WordPress Customizer panel where the live setting is still owned.', 'lunara-film' ),
            'where'   => __( 'Open Controls buttons across Theme Studio.', 'lunara-film' ),
        ),
        array(
            'term'    => __( 'Preview surface', 'lunara-film' ),
            'meaning' => __( 'The public page most likely to show the effect of a control change.', 'lunara-film' ),
            'where'   => __( 'Preview and 390px buttons across Theme Studio.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_render_theme_studio_glossary() {
    ?>
    <section class="lunara-control-desk-studio-glossary-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Customizer Glossary', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Plain meanings for the words Theme Studio uses', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'A quick translation layer for the terms that tend to make customization feel more complicated than it is.', 'lunara-film' ); ?></p>
        </div>
        <div class="lunara-control-desk-studio-glossary-grid">
            <?php foreach ( lunara_control_desk_theme_studio_glossary_terms() as $term ) : ?>
                <article>
                    <strong><?php echo esc_html( $term['term'] ); ?></strong>
                    <p><?php echo esc_html( $term['meaning'] ); ?></p>
                    <span><?php echo esc_html( $term['where'] ); ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function lunara_control_desk_theme_studio_preview_packets() {
    return array(
        array(
            'label'   => __( 'Mobile Cleanliness Pass', 'lunara-film' ),
            'section' => 'lunara_mobile_menu_options',
            'url'     => home_url( '/' ),
            'watch'   => array(
                __( 'Header height and logo scale', 'lunara-film' ),
                __( 'Menu tap targets and search behavior', 'lunara-film' ),
                __( 'No sideways scroll at 390px', 'lunara-film' ),
            ),
        ),
        array(
            'label'   => __( 'Review Premium Pass', 'lunara-film' ),
            'section' => 'lunara_review_layout_options',
            'url'     => home_url( '/reviews/' ),
            'watch'   => array(
                __( 'Poster ratio and card rhythm', 'lunara-film' ),
                __( 'Pull quote and title readability', 'lunara-film' ),
                __( 'Single-review hero after archive changes', 'lunara-film' ),
            ),
            'extra'   => home_url( '/reviews/passenger/' ),
        ),
        array(
            'label'   => __( 'Journal Reading Pass', 'lunara-film' ),
            'section' => 'lunara_standard_post_options',
            'url'     => home_url( '/journal/' ),
            'watch'   => array(
                __( 'Archive card rhythm', 'lunara-film' ),
                __( 'Single-entry hero title scale', 'lunara-film' ),
                __( 'Related Journal module spacing', 'lunara-film' ),
            ),
        ),
        array(
            'label'   => __( 'Homepage Tuning Pass', 'lunara-film' ),
            'section' => 'lunara_homepage_pulse_options',
            'url'     => home_url( '/' ),
            'watch'   => array(
                __( 'First viewport hierarchy', 'lunara-film' ),
                __( 'Reviews, Journal, and Ledger lane labels', 'lunara-film' ),
                __( 'Visible media in first cards', 'lunara-film' ),
            ),
        ),
        array(
            'label'   => __( 'Oscars Ledger Framing Pass', 'lunara-film' ),
            'section' => 'lunara_oscars_portal_options',
            'url'     => home_url( '/oscars/' ),
            'watch'   => array(
                __( 'Portal intro and database-module density', 'lunara-film' ),
                __( 'Poster proportions', 'lunara-film' ),
                __( 'Ceremony/category/entity navigation', 'lunara-film' ),
            ),
        ),
        array(
            'label'   => __( 'Site Tone Pass', 'lunara-film' ),
            'section' => 'lunara_global_design_options',
            'url'     => home_url( '/' ),
            'watch'   => array(
                __( 'Body text comfort', 'lunara-film' ),
                __( 'Heading authority', 'lunara-film' ),
                __( 'Accent color consistency across public surfaces', 'lunara-film' ),
            ),
        ),
    );
}

function lunara_control_desk_render_theme_studio_preview_packets() {
    $visual_qa_url = admin_url( 'admin.php?page=lunara-control-desk&tab=visual-qa' );
    ?>
    <section class="lunara-control-desk-studio-packet-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Change Preview Packet', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'One bundle of links before a customization pass', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Use a packet when you know what you want to improve but do not want to remember every page to check.', 'lunara-film' ); ?></p>
        </div>
        <div class="lunara-control-desk-studio-packet-grid">
            <?php foreach ( lunara_control_desk_theme_studio_preview_packets() as $packet ) : ?>
                <?php $mobile_url = add_query_arg( 'lunara-width', '390', $packet['url'] ); ?>
                <article>
                    <div class="lunara-control-desk-studio-packet-head">
                        <strong><?php echo esc_html( $packet['label'] ); ?></strong>
                        <span><?php esc_html_e( 'Preview packet', 'lunara-film' ); ?></span>
                    </div>
                    <ol>
                        <li><?php esc_html_e( 'Open the existing controls.', 'lunara-film' ); ?></li>
                        <li><?php esc_html_e( 'Preview the desktop surface.', 'lunara-film' ); ?></li>
                        <li><?php esc_html_e( 'Preview the 390px mobile surface.', 'lunara-film' ); ?></li>
                        <li><?php esc_html_e( 'Check these visual risks.', 'lunara-film' ); ?></li>
                    </ol>
                    <div class="lunara-control-desk-studio-packet-watch">
                        <?php foreach ( $packet['watch'] as $watch ) : ?>
                            <span><?php echo esc_html( $watch ); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="lunara-control-desk-actions">
                        <a class="button button-primary button-small" href="<?php echo esc_url( lunara_control_desk_customizer_url( $packet['section'] ) ); ?>"><?php esc_html_e( 'Open Controls', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( $packet['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Desktop', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( $mobile_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '390px', 'lunara-film' ); ?></a>
                        <?php if ( ! empty( $packet['extra'] ) ) : ?>
                            <a class="button button-small" href="<?php echo esc_url( $packet['extra'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Sample Single', 'lunara-film' ); ?></a>
                        <?php endif; ?>
                        <a class="button button-small" href="<?php echo esc_url( $visual_qa_url ); ?>"><?php esc_html_e( 'Visual QA', 'lunara-film' ); ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function lunara_control_desk_render_theme_studio_tab() {
    $groups = lunara_control_desk_theme_studio_groups();
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Theme Studio', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'The important design controls without the hunting', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'This map keeps existing Customizer ownership intact. It does not create duplicate settings storage.', 'lunara-film' ); ?></p>
        </div>
        <?php lunara_control_desk_render_status_cards( lunara_control_desk_theme_studio_summary_cards( $groups ) ); ?>
        <div class="lunara-control-desk-studio-nav" aria-label="<?php echo esc_attr__( 'Theme Studio groups', 'lunara-film' ); ?>">
            <?php foreach ( $groups as $group ) : ?>
                <?php $counts = lunara_control_desk_theme_studio_group_counts( $group ); ?>
                <a href="#<?php echo esc_attr( lunara_control_desk_theme_studio_group_anchor( $group ) ); ?>">
                    <strong><?php echo esc_html( $group['label'] ); ?></strong>
                    <span><?php echo esc_html( sprintf( __( '%1$d custom / %2$d controls', 'lunara-film' ), absint( $counts['custom'] ), absint( $counts['total'] ) ) ); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="lunara-control-desk-card-grid lunara-control-desk-studio-grid">
            <?php foreach ( $groups as $group ) : ?>
                <?php $counts = lunara_control_desk_theme_studio_group_counts( $group ); ?>
                <article id="<?php echo esc_attr( lunara_control_desk_theme_studio_group_anchor( $group ) ); ?>" class="lunara-control-desk-card lunara-control-desk-studio-card">
                    <div class="lunara-control-desk-card-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php echo esc_html( $group['owner'] ); ?></p>
                            <h3><?php echo esc_html( $group['label'] ); ?></h3>
                            <p class="lunara-control-desk-subtle"><?php echo esc_html( isset( $group['note'] ) ? $group['note'] : '' ); ?></p>
                        </div>
                        <span class="lunara-control-desk-studio-count"><?php echo esc_html( sprintf( __( '%1$d/%2$d custom', 'lunara-film' ), absint( $counts['custom'] ), absint( $counts['total'] ) ) ); ?></span>
                    </div>
                    <div class="lunara-control-desk-studio-meta">
                        <div>
                            <strong><?php esc_html_e( 'Controlled by', 'lunara-film' ); ?></strong>
                            <span><?php echo esc_html( $group['owner'] ); ?></span>
                        </div>
                        <div>
                            <strong><?php esc_html_e( 'Customizer section', 'lunara-film' ); ?></strong>
                            <span><?php echo esc_html( $group['section'] ); ?></span>
                        </div>
                        <div>
                            <strong><?php esc_html_e( 'Preview surface', 'lunara-film' ); ?></strong>
                            <span><?php echo esc_html( $group['preview'] ); ?></span>
                        </div>
                    </div>
                    <?php if ( ! empty( $group['renders'] ) && is_array( $group['renders'] ) ) : ?>
                        <div class="lunara-control-desk-studio-renders" aria-label="<?php esc_attr_e( 'Where this renders', 'lunara-film' ); ?>">
                            <strong><?php esc_html_e( 'Where this renders', 'lunara-film' ); ?></strong>
                            <div>
                                <?php foreach ( $group['renders'] as $surface ) : ?>
                                    <span><?php echo esc_html( $surface ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="lunara-control-desk-studio-settings">
                        <?php foreach ( $group['settings'] as $setting ) : ?>
                            <?php $is_custom = lunara_control_desk_theme_mod_has_custom_value( $setting ); ?>
                            <?php $setting_copy = lunara_control_desk_theme_studio_setting_copy( $setting ); ?>
                            <div class="<?php echo $is_custom ? 'is-custom' : 'is-default'; ?>">
                                <strong>
                                    <?php echo esc_html( $setting_copy['label'] ); ?>
                                    <code><?php echo esc_html( $setting ); ?></code>
                                </strong>
                                <span>
                                    <b><?php esc_html_e( 'Current:', 'lunara-film' ); ?></b>
                                    <?php echo esc_html( lunara_control_desk_theme_mod_value_summary( $setting ) ); ?>
                                    <small><?php echo esc_html( $setting_copy['hint'] ); ?></small>
                                </span>
                                <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="lunara-control-desk-actions">
                        <a class="button button-primary" href="<?php echo esc_url( lunara_control_desk_customizer_url( $group['section'] ) ); ?>"><?php esc_html_e( 'Open Controls', 'lunara-film' ); ?></a>
                        <a class="button" href="<?php echo esc_url( $group['preview'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Preview', 'lunara-film' ); ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php lunara_control_desk_render_theme_studio_layout_targets(); ?>
        <?php lunara_control_desk_render_theme_studio_mobile_checks(); ?>
        <?php lunara_control_desk_render_theme_studio_goal_guide(); ?>
        <?php lunara_control_desk_render_theme_studio_glossary(); ?>
        <?php lunara_control_desk_render_theme_studio_preview_packets(); ?>
        <?php lunara_control_desk_render_theme_studio_visual_evidence(); ?>
    </section>
    <?php
}

function lunara_control_desk_get_oscars_integrity_summary() {
    if ( class_exists( 'Academy_Awards_Table' ) ) {
        $aat = Academy_Awards_Table::get_instance();
        if ( $aat && method_exists( $aat, 'get_lunara_integrity_summary' ) ) {
            $summary = $aat->get_lunara_integrity_summary();
            return is_array( $summary ) ? $summary : array();
        }
    }

    return array(
        'version'  => defined( 'AAT_VERSION' ) ? AAT_VERSION : '',
        'checks'   => array(
            array(
                'label' => __( 'Integrity helper', 'lunara-film' ),
                'value' => __( 'Not available in the active Academy plugin yet', 'lunara-film' ),
                'state' => 'weak',
            ),
        ),
        'samples'  => array(),
        'routes'   => array(),
    );
}

function lunara_control_desk_oscars_integrity_bucket_key( $check ) {
    $label = strtolower( wp_strip_all_tags( isset( $check['label'] ) ? (string) $check['label'] : '' ) );

    if ( false !== strpos( $label, 'poster' ) ) {
        return 'posters';
    }

    if ( false !== strpos( $label, 'review' ) ) {
        return 'reviews';
    }

    if ( false !== strpos( $label, 'route' ) || false !== strpos( $label, 'ceremony' ) || false !== strpos( $label, 'category' ) ) {
        return 'routes';
    }

    if ( false !== strpos( $label, 'imdb' ) || false !== strpos( $label, ' id' ) || false !== strpos( $label, 'ids' ) || false !== strpos( $label, 'nominee' ) || false !== strpos( $label, 'person' ) || false !== strpos( $label, 'company' ) ) {
        return 'ids';
    }

    return 'data';
}

function lunara_control_desk_oscars_integrity_buckets( $checks ) {
    $buckets = array(
        'data'    => array(
            'label'   => __( 'Core Ledger Data', 'lunara-film' ),
            'note'    => __( 'Ceremonies, categories, rows, and the database spine.', 'lunara-film' ),
            'primary' => array(
                'label' => __( 'Data Explorer', 'lunara-film' ),
                'url'   => home_url( '/oscars/?view=table#oscars-research' ),
            ),
            'checks'  => array(),
        ),
        'ids'     => array(
            'label'   => __( 'IMDb + Entity IDs', 'lunara-film' ),
            'note'    => __( 'Title, person, nominee, and company IDs that power accurate Ledger routing.', 'lunara-film' ),
            'primary' => array(
                'label' => __( 'Ledger Assistant', 'lunara-film' ),
                'url'   => admin_url( 'admin.php?page=lunara-control-desk&tab=ledger-assistant' ),
            ),
            'checks'  => array(),
        ),
        'posters' => array(
            'label'   => __( 'Posters + Image Shape', 'lunara-film' ),
            'note'    => __( 'Poster records, attachment existence, metadata, and ratio watch.', 'lunara-film' ),
            'primary' => array(
                'label' => __( 'Poster Library', 'lunara-film' ),
                'url'   => admin_url( 'admin.php?page=academy-awards-posters' ),
            ),
            'checks'  => array(),
        ),
        'reviews' => array(
            'label'   => __( 'Review Links', 'lunara-film' ),
            'note'    => __( 'Review-to-title IMDb mappings and editorial connections into the Ledger.', 'lunara-film' ),
            'primary' => array(
                'label' => __( 'Missing IMDb Reviews', 'lunara-film' ),
                'url'   => admin_url( 'admin.php?page=lunara-control-desk&tab=reviews&lcd_review_filter=missing-imdb' ),
            ),
            'checks'  => array(),
        ),
        'routes'  => array(
            'label'   => __( 'Routes + Public QA', 'lunara-film' ),
            'note'    => __( 'Portal, ceremony, category, title, and person surfaces that need to keep resolving.', 'lunara-film' ),
            'primary' => array(
                'label' => __( 'Oscars Portal', 'lunara-film' ),
                'url'   => home_url( '/oscars/' ),
            ),
            'checks'  => array(),
        ),
    );

    foreach ( $checks as $check ) {
        $key = lunara_control_desk_oscars_integrity_bucket_key( $check );

        if ( ! isset( $buckets[ $key ] ) ) {
            $key = 'data';
        }

        $buckets[ $key ]['checks'][] = $check;
    }

    return $buckets;
}

function lunara_control_desk_oscars_bucket_state( $checks ) {
    if ( empty( $checks ) ) {
        return 'weak';
    }

    foreach ( $checks as $check ) {
        $state = isset( $check['state'] ) ? (string) $check['state'] : 'weak';

        if ( 'ready' !== $state ) {
            return 'weak';
        }
    }

    return 'ready';
}

function lunara_control_desk_render_oscars_integrity_buckets( $checks, $routes ) {
    $buckets      = lunara_control_desk_oscars_integrity_buckets( $checks );
    $route_links  = array_slice( $routes, 0, 4 );
    $visual_qa    = admin_url( 'admin.php?page=lunara-control-desk&tab=visual-qa' );
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Issue Buckets', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Where each Oscars integrity signal belongs', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'Grouped read-only signals keep the Ledger work from feeling like one giant pile. Repairs still happen in the owning Academy/plugin/editor screens.', 'lunara-film' ); ?></p>
        </div>
        <div class="lunara-control-desk-oscars-bucket-grid">
            <?php foreach ( $buckets as $key => $bucket ) : ?>
                <?php
                $bucket_checks = isset( $bucket['checks'] ) && is_array( $bucket['checks'] ) ? $bucket['checks'] : array();
                $state         = lunara_control_desk_oscars_bucket_state( $bucket_checks );
                $weak_count    = 0;

                foreach ( $bucket_checks as $bucket_check ) {
                    if ( 'ready' !== ( isset( $bucket_check['state'] ) ? (string) $bucket_check['state'] : 'weak' ) ) {
                        $weak_count++;
                    }
                }
                ?>
                <article class="is-<?php echo esc_attr( sanitize_html_class( $state ) ); ?>">
                    <div class="lunara-control-desk-oscars-bucket-head">
                        <div>
                            <strong><?php echo esc_html( $bucket['label'] ); ?></strong>
                            <span><?php echo esc_html( $bucket['note'] ); ?></span>
                        </div>
                        <em><?php echo esc_html( sprintf( __( '%1$d checks / %2$d watch', 'lunara-film' ), count( $bucket_checks ), $weak_count ) ); ?></em>
                    </div>
                    <?php if ( empty( $bucket_checks ) ) : ?>
                        <p class="lunara-control-desk-subtle"><?php esc_html_e( 'No matching check returned yet.', 'lunara-film' ); ?></p>
                    <?php else : ?>
                        <ul>
                            <?php foreach ( $bucket_checks as $check ) : ?>
                                <?php $check_state = isset( $check['state'] ) ? (string) $check['state'] : 'weak'; ?>
                                <li class="is-<?php echo esc_attr( sanitize_html_class( $check_state ) ); ?>">
                                    <div>
                                        <strong><?php echo esc_html( isset( $check['label'] ) ? $check['label'] : __( 'Check', 'lunara-film' ) ); ?></strong>
                                        <span><?php echo esc_html( isset( $check['value'] ) ? $check['value'] : '' ); ?></span>
                                    </div>
                                    <?php if ( ! empty( $check['note'] ) ) : ?>
                                        <p><?php echo esc_html( $check['note'] ); ?></p>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="lunara-control-desk-actions">
                        <a class="button button-primary button-small" href="<?php echo esc_url( $bucket['primary']['url'] ); ?>" <?php echo 0 === strpos( $bucket['primary']['url'], home_url() ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $bucket['primary']['label'] ); ?></a>
                        <?php if ( 'routes' === $key ) : ?>
                            <?php foreach ( $route_links as $route ) : ?>
                                <?php if ( empty( $route['url'] ) || empty( $route['label'] ) ) : ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <a class="button button-small" href="<?php echo esc_url( $route['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $route['label'] ); ?></a>
                            <?php endforeach; ?>
                            <a class="button button-small" href="<?php echo esc_url( $visual_qa ); ?>"><?php esc_html_e( 'Visual QA', 'lunara-film' ); ?></a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function lunara_control_desk_oscars_resolver_groups( $samples, $checks ) {
    $groups = array(
        'imdb'         => array(
            'label'   => __( 'IMDb ID Lookup', 'lunara-film' ),
            'note'    => __( 'Use the sample row details to find the correct tt ID, then repair it in the owning Academy data workflow.', 'lunara-film' ),
            'primary' => array(
                'label' => __( 'Data Explorer', 'lunara-film' ),
                'url'   => home_url( '/oscars/?view=table#oscars-research' ),
            ),
            'actions' => array(
                array(
                    'label' => __( 'Ledger Assistant', 'lunara-film' ),
                    'url'   => admin_url( 'admin.php?page=lunara-control-desk&tab=ledger-assistant' ),
                ),
            ),
            'items'   => array(),
        ),
        'poster'       => array(
            'label'   => __( 'Poster Attachment Repair', 'lunara-film' ),
            'note'    => __( 'Use the IMDb ID and attachment detail to reconnect or replace the poster record in the Poster Library.', 'lunara-film' ),
            'primary' => array(
                'label' => __( 'Poster Library', 'lunara-film' ),
                'url'   => admin_url( 'admin.php?page=academy-awards-posters' ),
            ),
            'actions' => array(
                array(
                    'label' => __( 'Oscars Portal', 'lunara-film' ),
                    'url'   => home_url( '/oscars/' ),
                ),
            ),
            'items'   => array(),
        ),
        'poster_ratio' => array(
            'label'   => __( 'Poster Ratio Review', 'lunara-film' ),
            'note'    => __( 'Ratio flags usually mean an image is not behaving like a poster. Inspect before replacing anything.', 'lunara-film' ),
            'primary' => array(
                'label' => __( 'Poster Library', 'lunara-film' ),
                'url'   => admin_url( 'admin.php?page=academy-awards-posters' ),
            ),
            'actions' => array(
                array(
                    'label' => __( 'Visual QA', 'lunara-film' ),
                    'url'   => admin_url( 'admin.php?page=lunara-control-desk&tab=visual-qa' ),
                ),
            ),
            'items'   => array(),
        ),
        'review'       => array(
            'label'   => __( 'Review Mapping Check', 'lunara-film' ),
            'note'    => __( 'Review-to-title mapping flags should be resolved from the Review editor details first.', 'lunara-film' ),
            'primary' => array(
                'label' => __( 'Missing IMDb Reviews', 'lunara-film' ),
                'url'   => admin_url( 'admin.php?page=lunara-control-desk&tab=reviews&lcd_review_filter=missing-imdb' ),
            ),
            'actions' => array(),
            'items'   => array(),
        ),
    );

    foreach ( $samples as $sample ) {
        $label  = strtolower( wp_strip_all_tags( isset( $sample['label'] ) ? (string) $sample['label'] : '' ) );
        $detail = isset( $sample['detail'] ) ? (string) $sample['detail'] : '';
        $item   = array(
            'label'  => isset( $sample['label'] ) ? $sample['label'] : __( 'Sample', 'lunara-film' ),
            'detail' => $detail,
        );

        if ( false !== strpos( $label, 'poster ratio' ) || false !== strpos( $label, 'ratio flag' ) ) {
            $groups['poster_ratio']['items'][] = $item;
            continue;
        }

        if ( false !== strpos( $label, 'poster' ) ) {
            $groups['poster']['items'][] = $item;
            continue;
        }

        if ( false !== strpos( $label, 'review' ) ) {
            $groups['review']['items'][] = $item;
            continue;
        }

        if ( false !== strpos( $label, 'imdb' ) || false !== strpos( $label, 'id' ) ) {
            $groups['imdb']['items'][] = $item;
        }
    }

    foreach ( $checks as $check ) {
        $label = strtolower( wp_strip_all_tags( isset( $check['label'] ) ? (string) $check['label'] : '' ) );
        $state = isset( $check['state'] ) ? (string) $check['state'] : 'weak';

        if ( 'ready' === $state ) {
            continue;
        }

        $item = array(
            'label'  => isset( $check['label'] ) ? $check['label'] : __( 'Check', 'lunara-film' ),
            'detail' => trim( ( isset( $check['value'] ) ? (string) $check['value'] : '' ) . ( ! empty( $check['note'] ) ? ' - ' . (string) $check['note'] : '' ) ),
        );

        if ( false !== strpos( $label, 'poster ratio' ) ) {
            $groups['poster_ratio']['items'][] = $item;
        } elseif ( false !== strpos( $label, 'review' ) ) {
            $groups['review']['items'][] = $item;
        }
    }

    return array_filter(
        $groups,
        static function ( $group ) {
            return ! empty( $group['items'] );
        }
    );
}

function lunara_control_desk_render_oscars_resolver_suggestions( $samples, $checks ) {
    $groups = lunara_control_desk_oscars_resolver_groups( $samples, $checks );
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Resolver Suggestions', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Read-only next steps for sampled issues', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'These cards explain where to look next. They do not change IMDb IDs, poster records, attachments, reviews, or Ledger rows.', 'lunara-film' ); ?></p>
        </div>
        <?php if ( empty( $groups ) ) : ?>
            <div class="lunara-control-desk-empty"><p><?php esc_html_e( 'No resolver suggestions are needed from the current sampled issues.', 'lunara-film' ); ?></p></div>
        <?php else : ?>
            <div class="lunara-control-desk-oscars-resolver-grid">
                <?php foreach ( $groups as $group ) : ?>
                    <article>
                        <div class="lunara-control-desk-oscars-resolver-head">
                            <div>
                                <strong><?php echo esc_html( $group['label'] ); ?></strong>
                                <span><?php echo esc_html( $group['note'] ); ?></span>
                            </div>
                            <em><?php echo esc_html( sprintf( __( '%d signals', 'lunara-film' ), count( $group['items'] ) ) ); ?></em>
                        </div>
                        <ul>
                            <?php foreach ( array_slice( $group['items'], 0, 6 ) as $item ) : ?>
                                <li>
                                    <strong><?php echo esc_html( $item['label'] ); ?></strong>
                                    <span><?php echo esc_html( $item['detail'] ); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p><?php esc_html_e( 'Suggest-only: inspect first, then make any repair from the owning screen.', 'lunara-film' ); ?></p>
                        <div class="lunara-control-desk-actions">
                            <a class="button button-primary button-small" href="<?php echo esc_url( $group['primary']['url'] ); ?>" <?php echo 0 === strpos( $group['primary']['url'], home_url() ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $group['primary']['label'] ); ?></a>
                            <?php foreach ( $group['actions'] as $action ) : ?>
                                <a class="button button-small" href="<?php echo esc_url( $action['url'] ); ?>" <?php echo 0 === strpos( $action['url'], home_url() ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $action['label'] ); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
}

function lunara_control_desk_mobile_preview_url( $url, $width = 390 ) {
    if ( '' === trim( (string) $url ) ) {
        return '';
    }

    return add_query_arg( 'lunara-width', absint( $width ), $url );
}

function lunara_control_desk_public_link_attrs( $url ) {
    return 0 === strpos( (string) $url, home_url() ) ? ' target="_blank" rel="noopener noreferrer"' : '';
}

function lunara_control_desk_get_oscars_route_kind( $url, $label = '' ) {
    $needle = strtolower( (string) $url . ' ' . (string) $label );

    if ( false !== strpos( $needle, '/ceremony/' ) ) {
        return __( 'Ceremony', 'lunara-film' );
    }

    if ( false !== strpos( $needle, '/category/' ) ) {
        return __( 'Category', 'lunara-film' );
    }

    if ( false !== strpos( $needle, '/title/' ) ) {
        return __( 'Title', 'lunara-film' );
    }

    if ( false !== strpos( $needle, '/name/' ) || false !== strpos( $needle, '/person/' ) ) {
        return __( 'Person', 'lunara-film' );
    }

    return __( 'Portal', 'lunara-film' );
}

function lunara_control_desk_get_oscars_route_context( $kind ) {
    $contexts = array(
        __( 'Portal', 'lunara-film' )   => array(
            'risk' => __( 'Portal density, research module load, and first-screen clarity.', 'lunara-film' ),
            'next' => __( 'Check the database module, latest winners, and mobile stack after every Oscars deploy.', 'lunara-film' ),
        ),
        __( 'Ceremony', 'lunara-film' ) => array(
            'risk' => __( 'Winner Circle media, category jumps, and ceremony-year routing.', 'lunara-film' ),
            'next' => __( 'Confirm latest ceremony data, poster shapes, and card actions stay aligned.', 'lunara-film' ),
        ),
        __( 'Category', 'lunara-film' ) => array(
            'risk' => __( 'Long historical lists, repeated nominee cards, and category slug accuracy.', 'lunara-film' ),
            'next' => __( 'Check category labels, winner ordering, and deep mobile scrolling comfort.', 'lunara-film' ),
        ),
        __( 'Title', 'lunara-film' )    => array(
            'risk' => __( 'Poster correctness, title IMDb ID shape, and review-to-title context.', 'lunara-film' ),
            'next' => __( 'Confirm the title page finds its poster, review link, and nominations timeline.', 'lunara-film' ),
        ),
        __( 'Person', 'lunara-film' )   => array(
            'risk' => __( 'Person/entity ID correctness, timeline labels, and linked film context.', 'lunara-film' ),
            'next' => __( 'Confirm person names, ceremonies, and title chips route back into the Ledger.', 'lunara-film' ),
        ),
    );

    return isset( $contexts[ $kind ] ) ? $contexts[ $kind ] : $contexts[ __( 'Portal', 'lunara-film' ) ];
}

function lunara_control_desk_get_oscars_route_cards( $routes ) {
    $cards = array(
        array(
            'label' => __( 'Oscars Portal', 'lunara-film' ),
            'url'   => home_url( '/oscars/' ),
        ),
    );

    foreach ( $routes as $route ) {
        if ( empty( $route['url'] ) || empty( $route['label'] ) ) {
            continue;
        }

        $cards[] = array(
            'label' => $route['label'],
            'url'   => $route['url'],
        );
    }

    $fallbacks = array(
        array( 'label' => __( 'Ceremony 98', 'lunara-film' ), 'url' => home_url( '/oscars/ceremony/98/' ) ),
        array( 'label' => __( 'Best Picture', 'lunara-film' ), 'url' => home_url( '/oscars/category/best-picture/' ) ),
        array( 'label' => __( 'Sample Title', 'lunara-film' ), 'url' => home_url( '/oscars/title/tt0110912/' ) ),
        array( 'label' => __( 'Sample Person', 'lunara-film' ), 'url' => home_url( '/oscars/name/nm0000233/' ) ),
    );

    foreach ( $fallbacks as $fallback ) {
        $cards[] = $fallback;
    }

    $seen = array();
    $out  = array();

    foreach ( $cards as $card ) {
        $url = isset( $card['url'] ) ? esc_url_raw( $card['url'] ) : '';
        if ( '' === $url || isset( $seen[ $url ] ) ) {
            continue;
        }

        $seen[ $url ] = true;
        $kind         = lunara_control_desk_get_oscars_route_kind( $url, isset( $card['label'] ) ? $card['label'] : '' );
        $context      = lunara_control_desk_get_oscars_route_context( $kind );
        $out[]        = array(
            'label'  => isset( $card['label'] ) ? $card['label'] : __( 'Oscars Route', 'lunara-film' ),
            'url'    => $url,
            'kind'   => $kind,
            'risk'   => $context['risk'],
            'next'   => $context['next'],
            'mobile' => lunara_control_desk_mobile_preview_url( $url, 390 ),
        );
    }

    return array_slice( $out, 0, 8 );
}

function lunara_control_desk_render_oscars_route_qa_cards( $routes ) {
    $cards        = lunara_control_desk_get_oscars_route_cards( $routes );
    $page         = get_page_by_path( 'oscars' );
    $edit_page    = $page instanceof WP_Post ? get_edit_post_link( $page->ID, 'raw' ) : '';
    $visual_qa    = admin_url( 'admin.php?page=lunara-control-desk&tab=visual-qa' );
    $speed        = admin_url( 'admin.php?page=lunara-control-desk&tab=speed-stability' );
    $poster_admin = admin_url( 'admin.php?page=academy-awards-posters' );
    $data_table   = home_url( '/oscars/?view=table#oscars-research' );
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Route QA', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Every Oscars surface stays part of one Ledger', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'These cards are read-only route targets: portal, ceremony, category, title, and person pages with mobile checks and exact owning screens.', 'lunara-film' ); ?></p>
        </div>
        <div class="lunara-control-desk-oscars-route-grid">
            <?php foreach ( $cards as $card ) : ?>
                <article>
                    <div class="lunara-control-desk-oscars-route-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php echo esc_html( $card['kind'] ); ?></p>
                            <h3><?php echo esc_html( $card['label'] ); ?></h3>
                        </div>
                        <span><?php esc_html_e( 'Read-only', 'lunara-film' ); ?></span>
                    </div>
                    <dl>
                        <div>
                            <dt><?php esc_html_e( 'Public URL', 'lunara-film' ); ?></dt>
                            <dd><a href="<?php echo esc_url( $card['url'] ); ?>"<?php echo lunara_control_desk_public_link_attrs( $card['url'] ); ?>><?php echo esc_html( $card['url'] ); ?></a></dd>
                        </div>
                        <div>
                            <dt><?php esc_html_e( 'Mobile URL', 'lunara-film' ); ?></dt>
                            <dd><a href="<?php echo esc_url( $card['mobile'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $card['mobile'] ); ?></a></dd>
                        </div>
                        <div>
                            <dt><?php esc_html_e( 'Risk', 'lunara-film' ); ?></dt>
                            <dd><?php echo esc_html( $card['risk'] ); ?></dd>
                        </div>
                        <div>
                            <dt><?php esc_html_e( 'Next action', 'lunara-film' ); ?></dt>
                            <dd><?php echo esc_html( $card['next'] ); ?></dd>
                        </div>
                    </dl>
                    <div class="lunara-control-desk-actions">
                        <a class="button button-primary button-small" href="<?php echo esc_url( $card['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Route', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( $card['mobile'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '390px', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( $visual_qa ); ?>"><?php esc_html_e( 'Visual QA', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( $speed ); ?>"><?php esc_html_e( 'Speed', 'lunara-film' ); ?></a>
                        <?php if ( __( 'Portal', 'lunara-film' ) === $card['kind'] && $edit_page ) : ?>
                            <a class="button button-small" href="<?php echo esc_url( $edit_page ); ?>"><?php esc_html_e( 'Edit Page', 'lunara-film' ); ?></a>
                        <?php endif; ?>
                        <?php if ( in_array( $card['kind'], array( __( 'Title', 'lunara-film' ), __( 'Ceremony', 'lunara-film' ), __( 'Category', 'lunara-film' ) ), true ) ) : ?>
                            <a class="button button-small" href="<?php echo esc_url( $poster_admin ); ?>"><?php esc_html_e( 'Poster Library', 'lunara-film' ); ?></a>
                        <?php endif; ?>
                        <a class="button button-small" href="<?php echo esc_url( $data_table ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Data Explorer', 'lunara-film' ); ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function lunara_control_desk_render_oscars_integrity_tab() {
    $summary = lunara_control_desk_get_oscars_integrity_summary();
    $checks  = ! empty( $summary['checks'] ) && is_array( $summary['checks'] ) ? $summary['checks'] : array();
    $samples = ! empty( $summary['samples'] ) && is_array( $summary['samples'] ) ? $summary['samples'] : array();
    $routes  = ! empty( $summary['routes'] ) && is_array( $summary['routes'] ) ? $summary['routes'] : array();
    $resolver_samples = $samples;

    foreach ( array( 'poster_attachment_samples', 'poster_ratio_samples' ) as $sample_key ) {
        if ( ! empty( $summary[ $sample_key ] ) && is_array( $summary[ $sample_key ] ) ) {
            $resolver_samples = array_merge( $resolver_samples, $summary[ $sample_key ] );
        }
    }
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Oscars Integrity', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Poster, IMDb, route, and review-link truth checks', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'The Academy plugin owns this data. The Control Desk reads the summary and sends you to the exact repair surfaces.', 'lunara-film' ); ?></p>
        </div>
        <?php lunara_control_desk_render_status_cards( $checks ); ?>
    </section>

    <?php lunara_control_desk_render_oscars_integrity_buckets( $checks, $routes ); ?>
    <?php lunara_control_desk_render_oscars_resolver_suggestions( $resolver_samples, $checks ); ?>
    <?php lunara_control_desk_render_oscars_route_qa_cards( $routes ); ?>

    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Samples', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'First issues to inspect, without mutating ledger data', 'lunara-film' ); ?></h2>
        </div>
        <?php if ( empty( $samples ) ) : ?>
            <div class="lunara-control-desk-empty"><p><?php esc_html_e( 'No sample integrity issues returned by the active helper.', 'lunara-film' ); ?></p></div>
        <?php else : ?>
            <ul class="lunara-control-desk-audit-list">
                <?php foreach ( $samples as $sample ) : ?>
                    <li>
                        <strong><?php echo esc_html( isset( $sample['label'] ) ? $sample['label'] : __( 'Issue', 'lunara-film' ) ); ?></strong>
                        <span><?php echo esc_html( isset( $sample['detail'] ) ? $sample['detail'] : '' ); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="lunara-control-desk-actions">
            <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=academy-awards-posters' ) ); ?>"><?php esc_html_e( 'Poster Library', 'lunara-film' ); ?></a>
            <a class="button" href="<?php echo esc_url( home_url( '/oscars/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Oscars Portal', 'lunara-film' ); ?></a>
            <?php foreach ( $routes as $route ) : ?>
                <?php if ( empty( $route['url'] ) || empty( $route['label'] ) ) : ?>
                    <?php continue; ?>
                <?php endif; ?>
                <a class="button" href="<?php echo esc_url( $route['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $route['label'] ); ?></a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function lunara_control_desk_get_sample_permalink( $post_type, $fallback ) {
    if ( ! post_type_exists( $post_type ) ) {
        return $fallback;
    }

    $posts = get_posts(
        array(
            'post_type'              => $post_type,
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'orderby'                => 'modified',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        )
    );

    if ( empty( $posts ) || ! $posts[0] instanceof WP_Post ) {
        return $fallback;
    }

    $url = get_permalink( $posts[0] );

    return $url ? $url : $fallback;
}

function lunara_control_desk_surface_targets() {
    return array(
        array( 'key' => 'home', 'label' => __( 'Home', 'lunara-film' ), 'type' => __( 'Front door', 'lunara-film' ), 'url' => home_url( '/' ) ),
        array( 'key' => 'reviews', 'label' => __( 'Reviews', 'lunara-film' ), 'type' => __( 'Archive', 'lunara-film' ), 'url' => home_url( '/reviews/' ) ),
        array( 'key' => 'journal', 'label' => __( 'Journal', 'lunara-film' ), 'type' => __( 'Archive', 'lunara-film' ), 'url' => home_url( '/journal/' ) ),
        array( 'key' => 'oscars', 'label' => __( 'Oscars', 'lunara-film' ), 'type' => __( 'Ledger portal', 'lunara-film' ), 'url' => home_url( '/oscars/' ) ),
        array( 'key' => 'sample-review', 'label' => __( 'Sample Review', 'lunara-film' ), 'type' => __( 'Single', 'lunara-film' ), 'url' => lunara_control_desk_get_sample_permalink( 'review', home_url( '/reviews/passenger/' ) ) ),
        array( 'key' => 'sample-journal', 'label' => __( 'Sample Journal', 'lunara-film' ), 'type' => __( 'Single', 'lunara-film' ), 'url' => lunara_control_desk_get_sample_permalink( 'journal', home_url( '/journal/' ) ) ),
        array( 'key' => 'sample-ceremony', 'label' => __( 'Sample Ceremony', 'lunara-film' ), 'type' => __( 'Oscars route', 'lunara-film' ), 'url' => home_url( '/oscars/ceremony/98/' ) ),
        array( 'key' => 'sample-category', 'label' => __( 'Sample Category', 'lunara-film' ), 'type' => __( 'Oscars route', 'lunara-film' ), 'url' => home_url( '/oscars/category/best-picture/' ) ),
        array( 'key' => 'sample-title', 'label' => __( 'Sample Title', 'lunara-film' ), 'type' => __( 'Oscars route', 'lunara-film' ), 'url' => home_url( '/oscars/title/tt0110912/' ) ),
        array( 'key' => 'sample-person', 'label' => __( 'Sample Person', 'lunara-film' ), 'type' => __( 'Oscars route', 'lunara-film' ), 'url' => home_url( '/oscars/name/nm0000233/' ) ),
    );
}

function lunara_control_desk_speed_route_notes() {
    return array(
        'home'            => array(
            'image'  => __( 'First-lane media must avoid original-size requests.', 'lunara-film' ),
            'mobile' => __( '390px stack should keep the first viewport clean and readable.', 'lunara-film' ),
            'next'   => __( 'Check newest review, Journal, and Ledger lanes after cache flush.', 'lunara-film' ),
        ),
        'reviews'         => array(
            'image'  => __( 'Archive cards should use bounded card/featured images.', 'lunara-film' ),
            'mobile' => __( 'Cards need comfortable tap targets and no clipped titles.', 'lunara-film' ),
            'next'   => __( 'Spot-check card image sizes and recently updated ordering.', 'lunara-film' ),
        ),
        'journal'         => array(
            'image'  => __( 'Hero and archive images should not force huge downloads.', 'lunara-film' ),
            'mobile' => __( 'Long headlines need clean wrapping and readable leading.', 'lunara-film' ),
            'next'   => __( 'Check Journal archive stack and latest single article.', 'lunara-film' ),
        ),
        'oscars'          => array(
            'image'  => __( 'Poster grids must lazy-load and avoid original-size poster payloads.', 'lunara-film' ),
            'mobile' => __( 'Ledger modules must stack without horizontal overflow.', 'lunara-film' ),
            'next'   => __( 'Check portal research module, latest winners, and poster cards.', 'lunara-film' ),
        ),
        'sample-review'   => array(
            'image'  => __( 'Featured, hero, card override, and debrief poster images all need bounds.', 'lunara-film' ),
            'mobile' => __( 'Hero, score, and debrief modules should stack without squeezing.', 'lunara-film' ),
            'next'   => __( 'Check sticky rail leftovers, hero height, and related cards.', 'lunara-film' ),
        ),
        'sample-journal'  => array(
            'image'  => __( 'Journal single hero should use the intended responsive size.', 'lunara-film' ),
            'mobile' => __( 'Title block and hero image must read like one editorial cover.', 'lunara-film' ),
            'next'   => __( 'Check opening spacing, body width, and related-entry module.', 'lunara-film' ),
        ),
        'sample-ceremony' => array(
            'image'  => __( 'Winner Circle and nominee poster cards should use cached poster sizes.', 'lunara-film' ),
            'mobile' => __( 'Ceremony cards need stable ratios and readable category chips.', 'lunara-film' ),
            'next'   => __( 'Check latest ceremony modules and route chip density.', 'lunara-film' ),
        ),
        'sample-category' => array(
            'image'  => __( 'Historical category lists should not over-fetch posters at once.', 'lunara-film' ),
            'mobile' => __( 'Long category history must scroll without side drift.', 'lunara-film' ),
            'next'   => __( 'Check winner rows, card wrapping, and pagination/anchors.', 'lunara-film' ),
        ),
        'sample-title'    => array(
            'image'  => __( 'Title poster should resolve to the correct mapped attachment.', 'lunara-film' ),
            'mobile' => __( 'Timeline cards and poster media should not overlap.', 'lunara-film' ),
            'next'   => __( 'Check title poster, review mapping, and nominations timeline.', 'lunara-film' ),
        ),
        'sample-person'   => array(
            'image'  => __( 'Person pages should avoid oversized title/poster thumbnails.', 'lunara-film' ),
            'mobile' => __( 'Timeline chips and linked films must wrap cleanly.', 'lunara-film' ),
            'next'   => __( 'Check person label accuracy, film links, and ceremony links.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_render_mobile_priority_panel( $context = 'speed' ) {
    $items = 'visual' === $context ? array(
        __( '390px phone lane: no horizontal overflow, clipped text, or tiny card controls.', 'lunara-film' ),
        __( '768px tablet lane: two-column transitions must not squeeze cards or posters.', 'lunara-film' ),
        __( 'Header, footer, review hero, archive cards, and Oscars poster grids are the first checks.', 'lunara-film' ),
    ) : array(
        __( 'Flag oversized images before desktop polish, especially poster/card/hero media.', 'lunara-film' ),
        __( 'Treat mobile TTFB, lazy-loading, and layout shift as launch blockers.', 'lunara-film' ),
        __( 'Confirm no Control Desk assets leak into public mobile HTML.', 'lunara-film' ),
    );
    ?>
    <section class="lunara-control-desk-mobile-priority">
        <div>
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Mobile Priority', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( '390px and 768px get checked first', 'lunara-film' ); ?></h3>
        </div>
        <ul>
            <?php foreach ( $items as $item ) : ?>
                <li><?php echo esc_html( $item ); ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php
}

function lunara_control_desk_render_speed_stability_tab() {
    $cache_items = array(
        array( 'label' => __( 'WP_CACHE', 'lunara-film' ), 'value' => defined( 'WP_CACHE' ) && WP_CACHE ? __( 'enabled', 'lunara-film' ) : __( 'not enabled by constant', 'lunara-film' ), 'state' => 'ready', 'note' => __( 'WP.com and plugin caches still need public response checks.', 'lunara-film' ) ),
        array( 'label' => __( 'Desk assets', 'lunara-film' ), 'value' => __( 'admin-only enqueue path', 'lunara-film' ), 'state' => 'ready', 'note' => __( 'Verify public HTML after every deploy.', 'lunara-film' ) ),
        array( 'label' => __( 'Mobile priority', 'lunara-film' ), 'value' => __( '390px and 768px first', 'lunara-film' ), 'state' => 'ready', 'note' => __( 'Oversized images, lazy-loading, TTFB, layout shift, and asset leakage are first-class checks.', 'lunara-film' ) ),
        array( 'label' => __( 'Known watchlist', 'lunara-film' ), 'value' => __( 'Jetpack/Boost, Site Kit, Atomic TTFB', 'lunara-film' ), 'state' => 'weak', 'note' => __( 'These remain configuration/performance risks from the last speed pass.', 'lunara-film' ) ),
    );
    $notes = lunara_control_desk_speed_route_notes();
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Speed & Stability', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Public surfaces that must stay fast after each phase', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'These cards are the runbook for route checks. Live HTTP/cache confirmation still happens during deploy verification, not by mutating public content.', 'lunara-film' ); ?></p>
        </div>
        <?php lunara_control_desk_render_status_cards( $cache_items ); ?>
        <?php lunara_control_desk_render_mobile_priority_panel( 'speed' ); ?>
        <div class="lunara-control-desk-speed-grid">
            <?php foreach ( lunara_control_desk_surface_targets() as $target ) : ?>
                <?php $target_notes = isset( $notes[ $target['key'] ] ) ? $notes[ $target['key'] ] : array( 'image' => '', 'mobile' => '', 'next' => '' ); ?>
                <article>
                    <div class="lunara-control-desk-speed-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php echo esc_html( $target['type'] ); ?></p>
                            <h3><?php echo esc_html( $target['label'] ); ?></h3>
                        </div>
                        <span><?php esc_html_e( '200 OK target', 'lunara-film' ); ?></span>
                    </div>
                    <dl>
                        <div>
                            <dt><?php esc_html_e( 'Public route', 'lunara-film' ); ?></dt>
                            <dd><a href="<?php echo esc_url( $target['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $target['url'] ); ?></a></dd>
                        </div>
                        <div>
                            <dt><?php esc_html_e( 'Cache / assets', 'lunara-film' ); ?></dt>
                            <dd><?php esc_html_e( 'Check cache headers and confirm no lunara-control-desk asset marker appears in public HTML.', 'lunara-film' ); ?></dd>
                        </div>
                        <div>
                            <dt><?php esc_html_e( 'Image payload risk', 'lunara-film' ); ?></dt>
                            <dd><?php echo esc_html( $target_notes['image'] ); ?></dd>
                        </div>
                        <div>
                            <dt><?php esc_html_e( 'Mobile risk', 'lunara-film' ); ?></dt>
                            <dd><?php echo esc_html( $target_notes['mobile'] ); ?></dd>
                        </div>
                    </dl>
                    <p class="lunara-control-desk-subtle"><?php echo esc_html( $target_notes['next'] ); ?></p>
                    <div class="lunara-control-desk-actions">
                        <a class="button button-primary button-small" href="<?php echo esc_url( $target['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( lunara_control_desk_mobile_preview_url( $target['url'], 390 ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '390px', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=lunara-control-desk&tab=visual-qa' ) ); ?>"><?php esc_html_e( 'Visual QA', 'lunara-film' ); ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function lunara_control_desk_visual_breakpoints() {
    return array(
        array( 'width' => 390, 'label' => __( 'Phone', 'lunara-film' ), 'priority' => true ),
        array( 'width' => 768, 'label' => __( 'Tablet', 'lunara-film' ), 'priority' => true ),
        array( 'width' => 1024, 'label' => __( 'Small desktop', 'lunara-film' ), 'priority' => false ),
        array( 'width' => 1280, 'label' => __( 'Desktop', 'lunara-film' ), 'priority' => false ),
        array( 'width' => 1440, 'label' => __( 'Wide desktop', 'lunara-film' ), 'priority' => false ),
    );
}

function lunara_control_desk_visual_checks_for_target( $key ) {
    $common = array(
        __( 'No horizontal overflow', 'lunara-film' ),
        __( 'Header/footer guardrails hold', 'lunara-film' ),
        __( 'Default-link styling does not leak', 'lunara-film' ),
    );

    $specific = array(
        'home'            => array( __( 'First viewport hierarchy holds on mobile', 'lunara-film' ), __( 'Homepage lanes stack without card squeezing', 'lunara-film' ) ),
        'reviews'         => array( __( 'Review cards keep readable titles and tap targets', 'lunara-film' ), __( 'Card image ratios stay intentional', 'lunara-film' ) ),
        'journal'         => array( __( 'Archive cards keep readable excerpts', 'lunara-film' ), __( 'Filters/pagination do not drift sideways', 'lunara-film' ) ),
        'oscars'          => array( __( 'Poster grids keep correct ratios', 'lunara-film' ), __( 'Research module stays scannable on mobile', 'lunara-film' ) ),
        'sample-review'   => array( __( 'Hero, score, and debrief stack cleanly', 'lunara-film' ), __( 'Related poster cards stay in bounds', 'lunara-film' ) ),
        'sample-journal'  => array( __( 'Title block and hero media feel connected', 'lunara-film' ), __( 'Body copy width stays comfortable', 'lunara-film' ) ),
        'sample-ceremony' => array( __( 'Winner Circle and category chips wrap cleanly', 'lunara-film' ), __( 'Poster cards keep 2:3-ish behavior', 'lunara-film' ) ),
        'sample-category' => array( __( 'Long history cards do not overflow', 'lunara-film' ), __( 'Winner labels stay readable', 'lunara-film' ) ),
        'sample-title'    => array( __( 'Poster and timeline cards do not overlap', 'lunara-film' ), __( 'Review link context is visible', 'lunara-film' ) ),
        'sample-person'   => array( __( 'Person timeline chips wrap cleanly', 'lunara-film' ), __( 'Linked title cards stay readable', 'lunara-film' ) ),
    );

    return array_merge( isset( $specific[ $key ] ) ? $specific[ $key ] : array(), $common );
}

function lunara_control_desk_render_visual_qa_tab() {
    $breakpoints = lunara_control_desk_visual_breakpoints();
    $artifact    = get_option( 'lunara_control_desk_last_visual_qa_path', 'C:\\Users\\silve_i21do49\\OneDrive\\Documents\\New project\\output' );
    $checked     = get_option( 'lunara_control_desk_last_visual_qa_checked', __( 'Not recorded yet', 'lunara-film' ) );
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Visual QA', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Layout sync targets for desktop, tablet, and mobile', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'Screenshots are captured by the local browser QA lane; this tab keeps the canonical targets visible in wp-admin, with mobile treated as the first pass.', 'lunara-film' ); ?></p>
        </div>
        <div class="lunara-control-desk-breakpoints">
            <?php foreach ( $breakpoints as $breakpoint ) : ?>
                <span class="<?php echo ! empty( $breakpoint['priority'] ) ? 'is-priority' : ''; ?>">
                    <strong><?php echo esc_html( $breakpoint['width'] ); ?>px</strong>
                    <em><?php echo esc_html( $breakpoint['label'] ); ?></em>
                </span>
            <?php endforeach; ?>
        </div>
        <p class="lunara-control-desk-artifact-path">
            <strong><?php esc_html_e( 'Latest artifact root:', 'lunara-film' ); ?></strong>
            <?php echo esc_html( $artifact ); ?>
            <span><?php echo esc_html( sprintf( __( 'Last checked: %s', 'lunara-film' ), $checked ) ); ?></span>
        </p>
        <?php lunara_control_desk_render_mobile_priority_panel( 'visual' ); ?>
        <div class="lunara-control-desk-visual-grid">
            <?php foreach ( lunara_control_desk_surface_targets() as $target ) : ?>
                <?php $checks = lunara_control_desk_visual_checks_for_target( $target['key'] ); ?>
                <article>
                    <div class="lunara-control-desk-visual-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php echo esc_html( $target['type'] ); ?></p>
                            <h3><?php echo esc_html( $target['label'] ); ?></h3>
                        </div>
                        <span><?php esc_html_e( 'Mobile first', 'lunara-film' ); ?></span>
                    </div>
                    <div class="lunara-control-desk-visual-breakout">
                        <a href="<?php echo esc_url( lunara_control_desk_mobile_preview_url( $target['url'], 390 ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '390px', 'lunara-film' ); ?></a>
                        <a href="<?php echo esc_url( lunara_control_desk_mobile_preview_url( $target['url'], 768 ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '768px', 'lunara-film' ); ?></a>
                        <a href="<?php echo esc_url( $target['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Desktop', 'lunara-film' ); ?></a>
                    </div>
                    <ul>
                        <?php foreach ( $checks as $check ) : ?>
                            <li><?php echo esc_html( $check ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="lunara-control-desk-subtle"><?php echo esc_html( sprintf( __( 'Expected screenshots: %1$s-%2$s.png through %1$s-%3$s.png', 'lunara-film' ), sanitize_key( $target['key'] ), '390', '1440' ) ); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function lunara_control_desk_ai_operator_jobs() {
    return array(
        array(
            'provider' => 'OpenAI',
            'intent'   => 'package',
            'label'    => __( 'Packaging Fields', 'lunara-film' ),
            'job'      => __( 'Titles, deks, H2s, pull quotes, social hooks, and clean structured fields.', 'lunara-film' ),
            'surface'  => __( 'Reviews, posts, and any draft needing editorial packaging.', 'lunara-film' ),
        ),
        array(
            'provider' => 'Anthropic',
            'intent'   => 'readiness',
            'label'    => __( 'Taste + Readiness Critique', 'lunara-film' ),
            'job'      => __( 'Voice, judgment, reader pull, weak argument checks, and readiness notes.', 'lunara-film' ),
            'surface'  => __( 'Reviews and Journal drafts where quality matters more than field filling.', 'lunara-film' ),
        ),
        array(
            'provider' => 'Gemini',
            'intent'   => 'ledger_links',
            'label'    => __( 'Ledger + IMDb Context', 'lunara-film' ),
            'job'      => __( 'Long-context Oscars opportunities, title/person/category links, poster and IMDb checks.', 'lunara-film' ),
            'surface'  => __( 'Reviews, Oscars-heavy Journal pieces, and Ledger repair candidates.', 'lunara-film' ),
        ),
        array(
            'provider' => 'Gemini',
            'intent'   => 'homepage_pitch',
            'label'    => __( 'Layout + Homepage Fit', 'lunara-film' ),
            'job'      => __( 'Homepage pitch, media risk notes, and whether a piece belongs in a front-page lane.', 'lunara-film' ),
            'surface'  => __( 'Journal and review candidates before curation changes.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_render_ai_operator_tab( $rows ) {
    $jobs = lunara_control_desk_ai_operator_jobs();
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'AI Operator', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Provider-routed suggestions with no automatic public changes', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'OpenAI handles structured packaging, Anthropic handles taste/readiness, and Gemini handles long-context Oscars, media, IMDb, and layout checks.', 'lunara-film' ); ?></p>
        </div>
        <div class="lunara-control-desk-ai-operator-grid">
            <?php foreach ( $jobs as $job ) : ?>
                <article>
                    <div class="lunara-control-desk-ai-operator-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php echo esc_html( $job['provider'] ); ?></p>
                            <h3><?php echo esc_html( $job['label'] ); ?></h3>
                        </div>
                        <span><?php echo esc_html( $job['intent'] ); ?></span>
                    </div>
                    <p><?php echo esc_html( $job['job'] ); ?></p>
                    <em><?php echo esc_html( $job['surface'] ); ?></em>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="lunara-control-desk-ai-operator-rules">
            <strong><?php esc_html_e( 'Operator rules', 'lunara-film' ); ?></strong>
            <span><?php esc_html_e( 'Suggestions are private capped snapshots. No title, excerpt, content, homepage flag, poster, IMDb ID, or Ledger link changes happen here.', 'lunara-film' ); ?></span>
        </div>
        <div class="lunara-control-desk-actions lunara-control-desk-console-links">
            <a class="button button-primary" href="<?php echo esc_url( admin_url( 'options-general.php?page=lunara-ai-assistant-classic' ) ); ?>"><?php esc_html_e( 'AI Provider Settings', 'lunara-film' ); ?></a>
            <a class="button" href="<?php echo esc_url( lunara_control_desk_url( array( 'tab' => 'field-suggestions' ) ) ); ?>"><?php esc_html_e( 'View Suggestion Cards', 'lunara-film' ); ?></a>
            <a class="button" href="<?php echo esc_url( lunara_control_desk_url( array( 'tab' => 'ledger-assistant' ) ) ); ?>"><?php esc_html_e( 'Ledger Assistant', 'lunara-film' ); ?></a>
        </div>
    </section>
    <?php

    lunara_control_desk_render_ai_console_tab( $rows );
}

function lunara_control_desk_render_publishing_tab( $rows, $filtered_rows ) {
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Publishing Command Center', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Open the next draft and finish the missing pieces', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'Filter the queue, spot blockers fast, and jump straight into the editor that owns the work.', 'lunara-film' ); ?></p>
        </div>
        <?php lunara_control_desk_render_filters( 'publishing' ); ?>
        <?php lunara_control_desk_render_queue_table( $filtered_rows ); ?>
    </section>
    <?php
}

function lunara_control_desk_render_ai_console_tab( $rows ) {
    $providers = array(
        'openai'    => array(
            'label' => __( 'OpenAI', 'lunara-film' ),
            'role'  => __( 'Structured packaging: titles, deks, H2s, pull quotes, readiness fields.', 'lunara-film' ),
        ),
        'anthropic' => array(
            'label' => __( 'Anthropic', 'lunara-film' ),
            'role'  => __( 'Taste and voice: rewrite critique, editorial pressure, reader pull.', 'lunara-film' ),
        ),
        'gemini'    => array(
            'label' => __( 'Gemini', 'lunara-film' ),
            'role'  => __( 'Context checks: long-context review, media and ledger opportunities.', 'lunara-film' ),
        ),
    );
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'AI Console', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Private suggestions, routed by editorial job', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'This panel creates private suggestion snapshots only. It does not rewrite, publish, or change fields.', 'lunara-film' ); ?></p>
        </div>
        <div class="lunara-control-desk-ai-grid">
            <?php foreach ( $providers as $provider => $card ) : ?>
                <article class="lunara-control-desk-ai-card">
                    <p class="lunara-control-desk-kicker"><?php echo esc_html( lunara_control_desk_provider_status( $provider ) ); ?></p>
                    <h3><?php echo esc_html( $card['label'] ); ?></h3>
                    <p><?php echo esc_html( $card['role'] ); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="lunara-control-desk-actions lunara-control-desk-console-links">
            <a class="button" href="<?php echo esc_url( admin_url( 'options-general.php?page=lunara-ai-assistant-classic' ) ); ?>"><?php esc_html_e( 'AI Provider Settings', 'lunara-film' ); ?></a>
            <a class="button" href="<?php echo esc_url( lunara_control_desk_url( array( 'tab' => 'field-suggestions' ) ) ); ?>"><?php esc_html_e( 'View Suggestion Cards', 'lunara-film' ); ?></a>
        </div>
    </section>

    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Generate', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Ask for a suggestion snapshot on the next drafts', 'lunara-film' ); ?></h2>
        </div>
        <?php lunara_control_desk_render_queue_table( array_slice( $rows, 0, 12 ), array( 'show_signals' => false ) ); ?>
    </section>
    <?php
}

function lunara_control_desk_render_field_suggestions_tab( $rows ) {
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Field Suggestions', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Copy-ready ideas without automatic field changes', 'lunara-film' ); ?></h2>
        </div>
        <?php if ( empty( $rows ) ) : ?>
            <div class="lunara-control-desk-empty"><p><?php esc_html_e( 'No draft or pending items are available for suggestions.', 'lunara-film' ); ?></p></div>
        <?php else : ?>
            <div class="lunara-control-desk-card-grid lunara-control-desk-field-grid">
                <?php foreach ( array_slice( $rows, 0, 18 ) as $row ) : ?>
                    <?php
                    $post              = $row['post'];
                    $snapshots         = lunara_control_desk_get_suggestions( $post->ID );
                    $snapshot          = ! empty( $snapshots ) ? $snapshots[0] : array();
                    $recommended_label = lunara_control_desk_get_recommended_intent_label( $post->post_type );
                    ?>
                    <article class="lunara-control-desk-card" data-lunara-row-post="<?php echo esc_attr( $post->ID ); ?>">
                        <div class="lunara-control-desk-card-head">
                            <div>
                                <p class="lunara-control-desk-kicker"><?php echo esc_html( lunara_control_desk_post_type_label( $post->post_type ) ); ?></p>
                                <h3><?php echo esc_html( get_the_title( $post ) ? get_the_title( $post ) : __( '(Untitled)', 'lunara-film' ) ); ?></h3>
                                <p class="lunara-control-desk-subtle">ID <?php echo esc_html( $post->ID ); ?> / <?php echo esc_html( get_post_status( $post ) ); ?></p>
                            </div>
                            <?php lunara_control_desk_render_readiness_badge( $row['readiness'] ); ?>
                        </div>
                        <?php lunara_control_desk_render_suggestion_snapshot( $snapshot, $snapshots, $recommended_label, $post->ID ); ?>
                        <div class="lunara-control-desk-inline-result" data-lunara-result="<?php echo esc_attr( $post->ID ); ?>" hidden></div>
                        <div class="lunara-control-desk-actions">
                            <?php lunara_control_desk_render_suggestion_actions( $post ); ?>
                            <a class="button" href="<?php echo esc_url( get_edit_post_link( $post->ID, 'raw' ) ); ?>"><?php esc_html_e( 'Open Editor', 'lunara-film' ); ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
}

function lunara_control_desk_journal_checks_to_signals( $checks ) {
    $signals = array();

    foreach ( $checks as $check ) {
        if ( empty( $check['signal'] ) ) {
            continue;
        }

        $signals[] = lunara_control_desk_make_signal(
            $check['signal'],
            isset( $check['state'] ) ? $check['state'] : 'weak'
        );
    }

    if ( empty( $signals ) ) {
        $signals[] = lunara_control_desk_make_signal( __( 'Ready for Journal growth review', 'lunara-film' ), 'ready' );
    }

    return $signals;
}

function lunara_control_desk_get_journal_growth_items( $rows ) {
    $items = array();

    foreach ( $rows as $row ) {
        if ( empty( $row['post'] ) || ! $row['post'] instanceof WP_Post || 'journal' !== $row['post']->post_type ) {
            continue;
        }

        $post      = $row['post'];
        $checks    = lunara_control_desk_get_journal_growth_checks( $post->ID );
        $signals   = lunara_control_desk_journal_checks_to_signals( $checks );
        $readiness = lunara_control_desk_get_readiness( $signals );

        $items[] = array(
            'post'      => $post,
            'checks'    => $checks,
            'signals'   => $signals,
            'readiness' => $readiness,
        );
    }

    return $items;
}

function lunara_control_desk_build_journal_growth_brief( $post, $checks, $readiness ) {
    $lines = array(
        sprintf( __( 'Lunara Journal Growth Brief: %s', 'lunara-film' ), get_the_title( $post ) ? get_the_title( $post ) : __( '(Untitled)', 'lunara-film' ) ),
        sprintf( __( 'Post ID: %d', 'lunara-film' ), absint( $post->ID ) ),
        sprintf( __( 'Status: %s', 'lunara-film' ), get_post_status( $post ) ),
        sprintf( __( 'Modified: %s', 'lunara-film' ), get_the_modified_date( get_option( 'date_format' ), $post ) ),
        sprintf( __( 'Readiness: %1$s (%2$d)', 'lunara-film' ), isset( $readiness['label'] ) ? $readiness['label'] : '', isset( $readiness['score'] ) ? absint( $readiness['score'] ) : 0 ),
        '',
        __( 'Originality and traffic checks:', 'lunara-film' ),
    );

    foreach ( $checks as $check ) {
        $state = isset( $check['state'] ) ? $check['state'] : 'ready';
        if ( 'ready' === $state ) {
            continue;
        }

        $lines[] = sprintf(
            '- [%1$s] %2$s: %3$s. %4$s',
            strtoupper( $state ),
            isset( $check['label'] ) ? $check['label'] : '',
            isset( $check['value'] ) ? $check['value'] : '',
            isset( $check['note'] ) ? $check['note'] : ''
        );
    }

    $lines[] = '';
    $lines[] = __( 'Editor links:', 'lunara-film' );
    $lines[] = sprintf( __( '- Editor: %s', 'lunara-film' ), get_edit_post_link( $post->ID, 'raw' ) );
    $lines[] = sprintf( __( '- Preview: %s', 'lunara-film' ), get_preview_post_link( $post ) );
    $lines[] = sprintf( __( '- Journal archive: %s', 'lunara-film' ), get_post_type_archive_link( 'journal' ) );

    return implode( "\n", $lines );
}

function lunara_control_desk_render_journal_growth_checks( $checks ) {
    if ( empty( $checks ) ) {
        return;
    }
    ?>
    <div class="lunara-control-desk-journal-checks">
        <?php foreach ( $checks as $check ) : ?>
            <?php
            $state = isset( $check['state'] ) ? sanitize_html_class( $check['state'] ) : 'ready';
            $label = isset( $check['label'] ) ? $check['label'] : '';
            $value = isset( $check['value'] ) ? $check['value'] : '';
            $note  = isset( $check['note'] ) ? $check['note'] : '';
            $url   = isset( $check['url'] ) ? $check['url'] : '';
            ?>
            <article class="lunara-control-desk-journal-check is-<?php echo esc_attr( $state ); ?>">
                <div>
                    <strong><?php echo esc_html( $label ); ?></strong>
                    <span><?php echo esc_html( $value ); ?></span>
                </div>
                <?php if ( $note ) : ?>
                    <p><?php echo esc_html( $note ); ?></p>
                <?php endif; ?>
                <?php if ( $url ) : ?>
                    <a href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Open related item', 'lunara-film' ); ?></a>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}

function lunara_control_desk_get_current_journal_lead_id() {
    if ( function_exists( 'lunara_get_curated_journal_lead_id' ) ) {
        return absint( lunara_get_curated_journal_lead_id() );
    }

    if ( function_exists( 'lunara_parse_manual_post_ids' ) && function_exists( 'lunara_theme_mod_text' ) ) {
        $lead_ids = lunara_parse_manual_post_ids(
            lunara_theme_mod_text( 'lunara_home_journal_lead_post_id', '' ),
            'journal'
        );

        return ! empty( $lead_ids[0] ) ? absint( $lead_ids[0] ) : 0;
    }

    return 0;
}

function lunara_control_desk_get_recent_published_journals( $include_id = 0, $limit = 40 ) {
    $posts = get_posts(
        array(
            'post_type'              => 'journal',
            'post_status'            => 'publish',
            'posts_per_page'         => max( 10, absint( $limit ) ),
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        )
    );

    $seen = array();
    $out  = array();

    if ( $include_id ) {
        $included = get_post( $include_id );
        if ( $included instanceof WP_Post && 'journal' === $included->post_type && 'publish' === $included->post_status ) {
            $out[]              = $included;
            $seen[ $included->ID ] = true;
        }
    }

    foreach ( $posts as $post ) {
        if ( ! ( $post instanceof WP_Post ) || isset( $seen[ $post->ID ] ) ) {
            continue;
        }

        $out[]            = $post;
        $seen[ $post->ID ] = true;
    }

    return $out;
}

function lunara_control_desk_render_journal_lead_curator() {
    $current_lead_id = lunara_control_desk_get_current_journal_lead_id();
    $current_lead    = $current_lead_id ? get_post( $current_lead_id ) : null;
    $latest_posts    = lunara_control_desk_get_recent_published_journals( $current_lead_id, 40 );
    $automatic_post  = ! empty( $latest_posts[0] ) && $latest_posts[0] instanceof WP_Post ? $latest_posts[0] : null;
    $active_post     = $current_lead instanceof WP_Post ? $current_lead : $automatic_post;
    $can_save        = current_user_can( 'edit_theme_options' );
    ?>
    <section id="lunara-journal-lead-curator" class="lunara-control-desk-panel lunara-control-desk-journal-lead-curator">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Curated Lead', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Choose the Journal file that deserves the front door', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'This controls the first card in the homepage Journal lane and the default Journal archive. It does not edit the post itself.', 'lunara-film' ); ?></p>
        </div>

        <div class="lunara-control-desk-lead-grid">
            <article class="lunara-control-desk-card lunara-control-desk-lead-current">
                <p class="lunara-control-desk-kicker"><?php echo $current_lead_id ? esc_html__( 'Manual lead', 'lunara-film' ) : esc_html__( 'Automatic newest', 'lunara-film' ); ?></p>
                <?php if ( $active_post instanceof WP_Post ) : ?>
                    <h3><?php echo esc_html( get_the_title( $active_post ) ? get_the_title( $active_post ) : __( '(Untitled)', 'lunara-film' ) ); ?></h3>
                    <p class="lunara-control-desk-subtle">
                        <?php
                        printf(
                            /* translators: 1: post ID, 2: publication date. */
                            esc_html__( 'ID %1$d / published %2$s', 'lunara-film' ),
                            absint( $active_post->ID ),
                            esc_html( get_the_date( get_option( 'date_format' ), $active_post ) )
                        );
                        ?>
                    </p>
                <?php else : ?>
                    <h3><?php esc_html_e( 'No published Journal entries found', 'lunara-film' ); ?></h3>
                    <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Publish a Journal entry, then come back here to curate the lead.', 'lunara-film' ); ?></p>
                <?php endif; ?>

                <div class="lunara-control-desk-actions">
                    <a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Homepage', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( get_post_type_archive_link( 'journal' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Journal Archive', 'lunara-film' ); ?></a>
                    <?php if ( $active_post instanceof WP_Post ) : ?>
                        <a class="button" href="<?php echo esc_url( get_edit_post_link( $active_post->ID, 'raw' ) ); ?>"><?php esc_html_e( 'Edit File', 'lunara-film' ); ?></a>
                    <?php endif; ?>
                    <?php if ( current_user_can( 'customize' ) ) : ?>
                        <a class="button" href="<?php echo esc_url( lunara_control_desk_customizer_url( 'lunara_homepage_pulse_options' ) ); ?>"><?php esc_html_e( 'Customizer', 'lunara-film' ); ?></a>
                    <?php endif; ?>
                </div>

                <?php if ( $active_post instanceof WP_Post ) : ?>
                    <div class="lunara-control-desk-lead-checks">
                        <h4><?php esc_html_e( 'Lead readiness', 'lunara-film' ); ?></h4>
                        <?php lunara_control_desk_render_journal_growth_checks( lunara_control_desk_get_journal_growth_checks( $active_post->ID ) ); ?>
                    </div>
                <?php endif; ?>
            </article>

            <form class="lunara-control-desk-card lunara-control-desk-lead-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="lunara_save_journal_lead" />
                <?php wp_nonce_field( 'lunara_save_journal_lead', 'lunara_journal_lead_nonce' ); ?>

                <label for="lunara-home-journal-lead-post-id">
                    <span><?php esc_html_e( 'Homepage and archive lead', 'lunara-film' ); ?></span>
                    <select id="lunara-home-journal-lead-post-id" name="lunara_home_journal_lead_post_id" <?php disabled( ! $can_save ); ?>>
                        <option value="0"><?php esc_html_e( 'Automatic newest Journal entry', 'lunara-film' ); ?></option>
                        <?php foreach ( $latest_posts as $post ) : ?>
                            <?php
                            if ( ! ( $post instanceof WP_Post ) ) {
                                continue;
                            }

                            $option_label = sprintf(
                                /* translators: 1: post ID, 2: post title, 3: publication date. */
                                __( '#%1$d - %2$s (%3$s)', 'lunara-film' ),
                                absint( $post->ID ),
                                get_the_title( $post ) ? get_the_title( $post ) : __( '(Untitled)', 'lunara-film' ),
                                get_the_date( get_option( 'date_format' ), $post )
                            );
                            ?>
                            <option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( $current_lead_id, $post->ID ); ?>><?php echo esc_html( $option_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Leave this on automatic when freshness matters. Pick a specific file when one Journal piece should stay centered for traffic, identity, or importance.', 'lunara-film' ); ?></p>

                <div class="lunara-control-desk-actions">
                    <?php if ( $can_save ) : ?>
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Lead', 'lunara-film' ); ?></button>
                    <?php else : ?>
                        <span class="lunara-control-desk-subtle"><?php esc_html_e( 'Theme editing permission is required to change this selector.', 'lunara-film' ); ?></span>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>
    <?php
}

function lunara_control_desk_get_recent_journal_visual_posts( $include_id = 0, $limit = 60 ) {
    $posts = get_posts(
        array(
            'post_type'              => 'journal',
            'post_status'            => array( 'publish', 'draft', 'pending' ),
            'posts_per_page'         => max( 20, absint( $limit ) ),
            'orderby'                => 'modified',
            'order'                  => 'DESC',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        )
    );

    $seen = array();
    $out  = array();

    if ( $include_id ) {
        $included = get_post( $include_id );
        if ( $included instanceof WP_Post && 'journal' === $included->post_type && current_user_can( 'edit_post', $included->ID ) ) {
            $out[]                = $included;
            $seen[ $included->ID ] = true;
        }
    }

    foreach ( $posts as $post ) {
        if ( ! ( $post instanceof WP_Post ) || isset( $seen[ $post->ID ] ) || ! current_user_can( 'edit_post', $post->ID ) ) {
            continue;
        }

        $out[]             = $post;
        $seen[ $post->ID ] = true;
    }

    return $out;
}

function lunara_control_desk_get_attachment_dimensions_label( $attachment_id ) {
    $meta = wp_get_attachment_metadata( $attachment_id );
    if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) {
        return '';
    }

    return sprintf( '%1$dx%2$d', absint( $meta['width'] ), absint( $meta['height'] ) );
}

function lunara_control_desk_render_journal_carousel_attachment_card( $attachment_id, $index = 0 ) {
    $attachment_id = absint( $attachment_id );
    if ( $attachment_id <= 0 ) {
        return;
    }

    $title       = get_the_title( $attachment_id );
    $title       = $title ? $title : basename( (string) get_attached_file( $attachment_id ) );
    $dimensions  = lunara_control_desk_get_attachment_dimensions_label( $attachment_id );
    $credit      = get_post_meta( $attachment_id, '_lunara_image_credit', true );
    $source_name = get_post_meta( $attachment_id, '_lunara_image_source_name', true );
    $source_url  = get_post_meta( $attachment_id, '_lunara_image_source_url', true );
    ?>
    <article class="lunara-control-desk-carousel-item" data-lunara-carousel-item data-lunara-carousel-id="<?php echo esc_attr( $attachment_id ); ?>">
        <div class="lunara-control-desk-carousel-thumb">
            <?php
            echo wp_get_attachment_image(
                $attachment_id,
                'thumbnail',
                false,
                array(
                    'class'   => 'lunara-control-desk-carousel-image',
                    'loading' => 0 === (int) $index ? 'eager' : 'lazy',
                )
            );
            ?>
        </div>
        <div class="lunara-control-desk-carousel-copy">
            <div class="lunara-control-desk-carousel-title-row">
                <div>
                    <strong><?php echo esc_html( $title ); ?></strong>
                    <span><?php echo esc_html( trim( sprintf( __( 'Attachment #%1$d %2$s', 'lunara-film' ), $attachment_id, $dimensions ) ) ); ?></span>
                </div>
                <div class="lunara-control-desk-carousel-controls">
                    <button type="button" class="button button-small" data-lunara-carousel-move="up"><?php esc_html_e( 'Up', 'lunara-film' ); ?></button>
                    <button type="button" class="button button-small" data-lunara-carousel-move="down"><?php esc_html_e( 'Down', 'lunara-film' ); ?></button>
                    <button type="button" class="button button-small" data-lunara-carousel-remove><?php esc_html_e( 'Remove', 'lunara-film' ); ?></button>
                </div>
            </div>
            <div class="lunara-control-desk-carousel-fields">
                <label>
                    <span><?php esc_html_e( 'Credit', 'lunara-film' ); ?></span>
                    <input type="text" name="lunara_journal_carousel_credit[<?php echo esc_attr( $attachment_id ); ?>]" value="<?php echo esc_attr( $credit ); ?>" placeholder="<?php esc_attr_e( 'Warner Bros. Pictures', 'lunara-film' ); ?>" />
                </label>
                <label>
                    <span><?php esc_html_e( 'Source', 'lunara-film' ); ?></span>
                    <input type="text" name="lunara_journal_carousel_source_name[<?php echo esc_attr( $attachment_id ); ?>]" value="<?php echo esc_attr( $source_name ); ?>" placeholder="<?php esc_attr_e( 'Entertainment Weekly', 'lunara-film' ); ?>" />
                </label>
                <label class="lunara-control-desk-carousel-url-field">
                    <span><?php esc_html_e( 'Source URL', 'lunara-film' ); ?></span>
                    <input type="url" name="lunara_journal_carousel_source_url[<?php echo esc_attr( $attachment_id ); ?>]" value="<?php echo esc_url( $source_url ); ?>" placeholder="https://" />
                </label>
            </div>
        </div>
    </article>
    <?php
}

function lunara_control_desk_render_journal_visual_file_manager() {
    $requested_id    = absint( lunara_control_desk_get_request_key( 'lcd_journal_visual_post', 0 ) );
    $current_lead_id = lunara_control_desk_get_current_journal_lead_id();
    $include_id      = $requested_id ? $requested_id : $current_lead_id;
    $posts           = lunara_control_desk_get_recent_journal_visual_posts( $include_id, 70 );
    $selected_post   = null;

    foreach ( $posts as $post ) {
        if ( ! ( $post instanceof WP_Post ) ) {
            continue;
        }

        if ( $requested_id && absint( $post->ID ) === $requested_id ) {
            $selected_post = $post;
            break;
        }

        if ( ! $selected_post && $current_lead_id && absint( $post->ID ) === $current_lead_id ) {
            $selected_post = $post;
        }
    }

    if ( ! $selected_post && ! empty( $posts[0] ) && $posts[0] instanceof WP_Post ) {
        $selected_post = $posts[0];
    }

    $selected_id  = $selected_post instanceof WP_Post ? absint( $selected_post->ID ) : 0;
    $carousel_ids = $selected_id ? lunara_control_desk_get_journal_carousel_ids( $selected_id ) : array();
    $preview_url  = $selected_post instanceof WP_Post ? get_preview_post_link( $selected_post ) : '';
    $public_url   = $selected_post instanceof WP_Post && 'publish' === get_post_status( $selected_post ) ? get_permalink( $selected_post ) : '';
    ?>
    <section id="lunara-journal-visual-file-manager" class="lunara-control-desk-panel lunara-control-desk-visual-file-manager">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Visual File Manager', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Curate the stills that deserve a click-through rail', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'Pick safe Media Library images, order them, and keep visible credit/source metadata attached to each still. Saving here only updates private Journal carousel meta and image provenance fields.', 'lunara-film' ); ?></p>
        </div>

        <?php if ( empty( $posts ) || ! ( $selected_post instanceof WP_Post ) ) : ?>
            <div class="lunara-control-desk-empty">
                <p><?php esc_html_e( 'No editable Journal entries are available for carousel curation yet.', 'lunara-film' ); ?></p>
            </div>
        <?php else : ?>
            <div class="lunara-control-desk-visual-manager-grid">
                <article class="lunara-control-desk-card lunara-control-desk-visual-picker">
                    <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
                        <input type="hidden" name="page" value="lunara-control-desk" />
                        <input type="hidden" name="tab" value="journal-growth" />
                        <label for="lunara-journal-visual-post">
                            <span><?php esc_html_e( 'Journal file', 'lunara-film' ); ?></span>
                            <select id="lunara-journal-visual-post" name="lcd_journal_visual_post">
                                <?php foreach ( $posts as $post ) : ?>
                                    <?php
                                    if ( ! ( $post instanceof WP_Post ) ) {
                                        continue;
                                    }

                                    $label = sprintf(
                                        /* translators: 1: post ID, 2: post title, 3: post status. */
                                        __( '#%1$d - %2$s (%3$s)', 'lunara-film' ),
                                        absint( $post->ID ),
                                        get_the_title( $post ) ? get_the_title( $post ) : __( '(Untitled)', 'lunara-film' ),
                                        get_post_status( $post )
                                    );
                                    ?>
                                    <option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( $selected_id, $post->ID ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button type="submit" class="button"><?php esc_html_e( 'Open Visual File', 'lunara-film' ); ?></button>
                    </form>

                    <div class="lunara-control-desk-visual-current">
                        <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Current file', 'lunara-film' ); ?></p>
                        <h3><?php echo esc_html( get_the_title( $selected_post ) ? get_the_title( $selected_post ) : __( '(Untitled)', 'lunara-film' ) ); ?></h3>
                        <p class="lunara-control-desk-subtle">
                            <?php
                            printf(
                                /* translators: 1: post ID, 2: status, 3: carousel image count. */
                                esc_html__( 'ID %1$d / %2$s / %3$d carousel images', 'lunara-film' ),
                                absint( $selected_id ),
                                esc_html( get_post_status( $selected_post ) ),
                                count( $carousel_ids )
                            );
                            ?>
                        </p>
                        <div class="lunara-control-desk-actions">
                            <a class="button" href="<?php echo esc_url( get_edit_post_link( $selected_id, 'raw' ) ); ?>"><?php esc_html_e( 'Edit File', 'lunara-film' ); ?></a>
                            <?php if ( $preview_url ) : ?>
                                <a class="button" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Preview', 'lunara-film' ); ?></a>
                            <?php endif; ?>
                            <?php if ( $public_url ) : ?>
                                <a class="button" href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Public Page', 'lunara-film' ); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>

                <form class="lunara-control-desk-card lunara-control-desk-carousel-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-lunara-carousel-form>
                    <input type="hidden" name="action" value="lunara_save_journal_carousel" />
                    <input type="hidden" name="lunara_journal_post_id" value="<?php echo esc_attr( $selected_id ); ?>" />
                    <?php wp_nonce_field( 'lunara_save_journal_carousel_' . $selected_id, 'lunara_journal_carousel_nonce' ); ?>

                    <div class="lunara-control-desk-carousel-toolbar">
                        <label for="lunara-journal-carousel-ids">
                            <span><?php esc_html_e( 'Attachment order', 'lunara-film' ); ?></span>
                            <textarea id="lunara-journal-carousel-ids" name="lunara_journal_carousel_ids" rows="2" data-lunara-carousel-ids><?php echo esc_textarea( implode( ',', $carousel_ids ) ); ?></textarea>
                        </label>
                        <div class="lunara-control-desk-actions">
                            <button type="button" class="button" data-lunara-carousel-picker><?php esc_html_e( 'Choose Images', 'lunara-film' ); ?></button>
                            <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Visual File', 'lunara-film' ); ?></button>
                        </div>
                    </div>

                    <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Use two or more stills when the image story is part of the hook. Leave this empty when the hero image is enough.', 'lunara-film' ); ?></p>

                    <div class="lunara-control-desk-carousel-list" data-lunara-carousel-list>
                        <?php if ( empty( $carousel_ids ) ) : ?>
                            <div class="lunara-control-desk-empty" data-lunara-carousel-empty>
                                <p><?php esc_html_e( 'No carousel images selected yet.', 'lunara-film' ); ?></p>
                                <p><?php esc_html_e( 'Choose images from the Media Library, then save the visual file.', 'lunara-film' ); ?></p>
                            </div>
                        <?php else : ?>
                            <?php foreach ( $carousel_ids as $index => $attachment_id ) : ?>
                                <?php lunara_control_desk_render_journal_carousel_attachment_card( $attachment_id, $index ); ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </section>
    <?php
}

function lunara_control_desk_render_journal_growth_tab( $rows ) {
    $items = lunara_control_desk_get_journal_growth_items( $rows );

    $blocker_count  = 0;
    $warning_count  = 0;
    $homepage_count = 0;
    $duplicate_count = 0;
    $source_risk_count = 0;
    $provenance_count = 0;
    $carousel_count = 0;

    foreach ( $items as $item ) {
        $readiness = $item['readiness'];
        if ( ! empty( $readiness['blockers'] ) ) {
            $blocker_count++;
        } elseif ( ! empty( $readiness['warnings'] ) ) {
            $warning_count++;
        }

        foreach ( $item['checks'] as $check ) {
            if ( ! empty( $check['label'] ) && __( 'Homepage Candidate', 'lunara-film' ) === $check['label'] && isset( $check['state'] ) && 'ready' === $check['state'] ) {
                $homepage_count++;
            }

            if ( ! empty( $check['label'] ) && __( 'Duplicate Topic Risk', 'lunara-film' ) === $check['label'] && isset( $check['state'] ) && 'ready' !== $check['state'] ) {
                $duplicate_count++;
            }

            if ( ! empty( $check['label'] ) && __( 'Source Risk', 'lunara-film' ) === $check['label'] && isset( $check['state'] ) && 'ready' !== $check['state'] ) {
                $source_risk_count++;
            }

            if ( ! empty( $check['label'] ) && __( 'Visual Provenance', 'lunara-film' ) === $check['label'] && isset( $check['state'] ) && 'ready' !== $check['state'] ) {
                $provenance_count++;
            }

            if ( ! empty( $check['label'] ) && __( 'Visual File Carousel', 'lunara-film' ) === $check['label'] && isset( $check['state'] ) && 'ready' !== $check['state'] ) {
                $carousel_count++;
            }
        }
    }

    $status_cards = array(
        array(
            'label' => __( 'Journal Drafts', 'lunara-film' ),
            'value' => sprintf( __( '%d in queue', 'lunara-film' ), count( $items ) ),
            'state' => count( $items ) ? 'ready' : 'weak',
            'note'  => __( 'Draft and pending Journal entries currently visible to the desk.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Originality Blockers', 'lunara-film' ),
            'value' => sprintf( __( '%d need attention', 'lunara-film' ), $blocker_count ),
            'state' => $blocker_count ? 'needs' : 'ready',
            'note'  => __( 'Missing standfirsts or unclear Lunara angles should be fixed before publish.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Homepage Candidates', 'lunara-film' ),
            'value' => sprintf( __( '%d already flagged', 'lunara-film' ), $homepage_count ),
            'state' => $homepage_count ? 'ready' : 'weak',
            'note'  => __( 'Strong Journal pieces should be treated as a front-door traffic lane.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Duplicate Risk', 'lunara-film' ),
            'value' => sprintf( __( '%d draft warning', 'lunara-film' ), $duplicate_count ),
            'state' => $duplicate_count ? 'weak' : 'ready',
            'note'  => __( 'Flags likely topic repeats before another similar Journal post goes live.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Source Risk', 'lunara-film' ),
            'value' => sprintf( __( '%d need source pass', 'lunara-film' ), $source_risk_count ),
            'state' => $source_risk_count ? 'needs' : 'ready',
            'note'  => __( 'World of Reel can stay as a fast signal, but those drafts need independent Lunara framing and clean image sourcing.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Visual Provenance', 'lunara-film' ),
            'value' => sprintf( __( '%d need image credit pass', 'lunara-film' ), $provenance_count ),
            'state' => $provenance_count ? 'weak' : 'ready',
            'note'  => __( 'Traffic entries should show a clean credit/source story before they carry the homepage or archive lead.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Carousel Readiness', 'lunara-film' ),
            'value' => sprintf( __( '%d need visual decision', 'lunara-film' ), $carousel_count ),
            'state' => $carousel_count ? 'weak' : 'ready',
            'note'  => __( 'Visual-first entries should either use the carousel deliberately or stay with a single strong hero image.', 'lunara-film' ),
        ),
    );
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Journal Growth', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Traffic engine, originality guardrail, and homepage candidates', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'This tab mostly audits and recommends. The Visual File Manager below can also save private carousel image order and credit/source metadata for the selected Journal entry.', 'lunara-film' ); ?></p>
        </div>
        <?php lunara_control_desk_render_status_cards( $status_cards ); ?>
    </section>

    <?php lunara_control_desk_render_journal_lead_curator(); ?>

    <?php lunara_control_desk_render_journal_visual_file_manager(); ?>

    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Draft Review', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Journal entries that need an editorial pass', 'lunara-film' ); ?></h2>
        </div>

        <?php if ( empty( $items ) ) : ?>
            <div class="lunara-control-desk-empty">
                <p><?php esc_html_e( 'No draft or pending Journal entries are in the current queue.', 'lunara-film' ); ?></p>
                <p><?php esc_html_e( 'When dispatch creates new drafts, this is where originality, attribution, homepage fit, and link opportunities will appear.', 'lunara-film' ); ?></p>
            </div>
        <?php else : ?>
            <div class="lunara-control-desk-card-grid lunara-control-desk-journal-grid">
                <?php foreach ( $items as $item ) : ?>
                    <?php
                    $post       = $item['post'];
                    $checks     = $item['checks'];
                    $readiness  = $item['readiness'];
                    $brief_text = lunara_control_desk_build_journal_growth_brief( $post, $checks, $readiness );
                    $preview    = get_preview_post_link( $post );
                    $summary    = wp_trim_words( wp_strip_all_tags( $post->post_excerpt ? $post->post_excerpt : $post->post_content ), 28 );
                    ?>
                    <article class="lunara-control-desk-card lunara-control-desk-journal-card" data-lunara-row-post="<?php echo esc_attr( $post->ID ); ?>">
                        <div class="lunara-control-desk-card-head">
                            <div>
                                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Journal', 'lunara-film' ); ?></p>
                                <h3><?php echo esc_html( get_the_title( $post ) ? get_the_title( $post ) : __( '(Untitled)', 'lunara-film' ) ); ?></h3>
                                <p class="lunara-control-desk-subtle">
                                    <?php
                                    printf(
                                        /* translators: 1: post ID, 2: status, 3: modified date. */
                                        esc_html__( 'ID %1$d / %2$s / modified %3$s', 'lunara-film' ),
                                        absint( $post->ID ),
                                        esc_html( get_post_status( $post ) ),
                                        esc_html( get_the_modified_date( get_option( 'date_format' ), $post ) )
                                    );
                                    ?>
                                </p>
                            </div>
                            <?php lunara_control_desk_render_readiness_badge( $readiness ); ?>
                        </div>

                        <?php if ( $summary ) : ?>
                            <p class="lunara-control-desk-journal-summary"><?php echo esc_html( $summary ); ?></p>
                        <?php endif; ?>

                        <?php lunara_control_desk_render_journal_growth_checks( $checks ); ?>

                        <div class="lunara-control-desk-actions">
                            <?php lunara_control_desk_render_suggestion_actions( $post ); ?>
                            <button type="button" class="button button-secondary" data-lunara-copy data-lunara-copy-text="<?php echo esc_attr( $brief_text ); ?>"><?php esc_html_e( 'Copy Brief', 'lunara-film' ); ?></button>
                            <a class="button button-primary" href="<?php echo esc_url( get_edit_post_link( $post->ID, 'raw' ) ); ?>"><?php esc_html_e( 'Open Editor', 'lunara-film' ); ?></a>
                            <?php if ( $preview ) : ?>
                                <a class="button" href="<?php echo esc_url( $preview ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Preview', 'lunara-film' ); ?></a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
}

function lunara_control_desk_get_homepage_lane_posts( $post_type, $meta_key, $limit = 8 ) {
    if ( ! post_type_exists( $post_type ) ) {
        return array();
    }

    $query = new WP_Query(
        array(
            'post_type'              => $post_type,
            'post_status'            => array( 'publish', 'draft', 'pending' ),
            'posts_per_page'         => absint( $limit ),
            'orderby'                => 'modified',
            'order'                  => 'DESC',
            'meta_key'               => $meta_key,
            'meta_value'             => '1',
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        )
    );

    return $query->posts;
}

function lunara_control_desk_render_homepage_lane( $label, $description, $posts, $empty ) {
    ?>
    <section class="lunara-control-desk-lane">
        <div class="lunara-control-desk-lane-head">
            <h3><?php echo esc_html( $label ); ?></h3>
            <p><?php echo esc_html( $description ); ?></p>
        </div>
        <?php if ( empty( $posts ) ) : ?>
            <p class="lunara-control-desk-subtle"><?php echo esc_html( $empty ); ?></p>
        <?php else : ?>
            <div class="lunara-control-desk-lane-stack">
                <?php foreach ( $posts as $post ) : ?>
                    <?php if ( ! $post instanceof WP_Post ) : ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <article class="lunara-control-desk-mini-card">
                        <strong><?php echo esc_html( get_the_title( $post ) ? get_the_title( $post ) : __( '(Untitled)', 'lunara-film' ) ); ?></strong>
                        <span><?php echo esc_html( lunara_control_desk_post_type_label( $post->post_type ) ); ?> / <?php echo esc_html( get_post_status( $post ) ); ?></span>
                        <a href="<?php echo esc_url( get_edit_post_link( $post->ID, 'raw' ) ); ?>"><?php esc_html_e( 'Open editor', 'lunara-film' ); ?></a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
}

function lunara_control_desk_render_homepage_board_tab( $rows ) {
    $review_hero   = lunara_control_desk_get_homepage_lane_posts( 'review', '_lunara_review_home_hero_featured' );
    $review_shelf  = lunara_control_desk_get_homepage_lane_posts( 'review', '_lunara_review_home_featured_shelf' );
    $journal_lane  = lunara_control_desk_get_homepage_lane_posts( 'journal', '_lunara_journal_featured' );
    $draft_posts   = array_map(
        static function ( $row ) {
            return $row['post'];
        },
        array_slice( $rows, 0, 8 )
    );
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Homepage Curation Board', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'A read-only look at what can feed the front page', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'This board links to existing controls. It does not drag, pin, unpin, or save homepage flags.', 'lunara-film' ); ?></p>
        </div>
        <div class="lunara-control-desk-board">
            <?php lunara_control_desk_render_homepage_lane( __( 'Review Hero', 'lunara-film' ), __( 'Reviews marked for the home hero lane.', 'lunara-film' ), $review_hero, __( 'No review hero flags found.', 'lunara-film' ) ); ?>
            <?php lunara_control_desk_render_homepage_lane( __( 'Review Shelf', 'lunara-film' ), __( 'Reviews marked for the homepage shelf.', 'lunara-film' ), $review_shelf, __( 'No review shelf flags found.', 'lunara-film' ) ); ?>
            <?php lunara_control_desk_render_homepage_lane( __( 'Journal Feature', 'lunara-film' ), __( 'Journal entries marked as featured.', 'lunara-film' ), $journal_lane, __( 'No journal-featured entries found.', 'lunara-film' ) ); ?>
            <?php lunara_control_desk_render_homepage_lane( __( 'Draft Candidates', 'lunara-film' ), __( 'Recent draft/pending work that may need a homepage pitch.', 'lunara-film' ), $draft_posts, __( 'No draft candidates found.', 'lunara-film' ) ); ?>
        </div>
        <div class="lunara-control-desk-actions">
            <a class="button" href="<?php echo esc_url( lunara_control_desk_customizer_url( 'lunara_homepage_pulse_options' ) ); ?>"><?php esc_html_e( 'Homepage Customizer', 'lunara-film' ); ?></a>
            <a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Homepage', 'lunara-film' ); ?></a>
        </div>
    </section>
    <?php
}

function lunara_control_desk_render_readiness_tab( $rows ) {
    usort(
        $rows,
        static function ( $a, $b ) {
            return intval( $a['readiness']['score'] ) <=> intval( $b['readiness']['score'] );
        }
    );
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Publish Readiness', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Blockers first, warnings second, ready reads last', 'lunara-film' ); ?></h2>
        </div>
        <?php lunara_control_desk_render_queue_table( $rows, array( 'show_ai' => true ) ); ?>
    </section>
    <?php
}

function lunara_control_desk_get_ledger_opportunities( $post ) {
    $text = wp_strip_all_tags( $post->post_title . "\n" . $post->post_excerpt . "\n" . $post->post_content );
    $out  = array();

    if ( preg_match_all( '/\btt\d{7,8}\b/i', $text, $matches ) ) {
        foreach ( array_unique( array_map( 'strtolower', $matches[0] ) ) as $tt ) {
            $url   = function_exists( 'lunara_get_internal_title_reference_url' ) ? lunara_get_internal_title_reference_url( $tt, $post->ID ) : '';
            $url   = $url ? $url : ( function_exists( 'lunara_get_oscars_title_url' ) ? lunara_get_oscars_title_url( $tt ) : home_url( '/oscars/title/' . rawurlencode( $tt ) . '/' ) );
            $out[] = array(
                'label' => sprintf( __( 'Title ID %s', 'lunara-film' ), $tt ),
                'url'   => $url,
            );
        }
    }

    if ( preg_match_all( '/\bnm\d{7,8}\b/i', $text, $matches ) ) {
        $aat = function_exists( 'lunara_get_oscars_plugin' ) ? lunara_get_oscars_plugin() : null;
        foreach ( array_unique( array_map( 'strtolower', $matches[0] ) ) as $nm ) {
            $url   = ( $aat && method_exists( $aat, 'build_entity_url_from_id' ) ) ? $aat->build_entity_url_from_id( $nm ) : home_url( '/oscars/name/' . rawurlencode( $nm ) . '/' );
            $out[] = array(
                'label' => sprintf( __( 'Person ID %s', 'lunara-film' ), $nm ),
                'url'   => $url,
            );
        }
    }

    $categories = array( 'Best Picture', 'Directing', 'Actor in a Leading Role', 'Actress in a Leading Role', 'Writing', 'Cinematography', 'Film Editing', 'Visual Effects', 'International Feature Film', 'Documentary Feature' );
    $aat        = function_exists( 'lunara_get_oscars_plugin' ) ? lunara_get_oscars_plugin() : null;
    foreach ( $categories as $category ) {
        if ( false === stripos( $text, $category ) ) {
            continue;
        }

        $url   = ( $aat && method_exists( $aat, 'get_category_url' ) ) ? $aat->get_category_url( $category ) : home_url( '/oscars/category/' . sanitize_title( $category ) . '/' );
        $out[] = array(
            'label' => $category,
            'url'   => $url,
        );
    }

    if ( preg_match_all( '/\b(\d{1,3})(st|nd|rd|th)\s+Academy Awards\b/i', $text, $matches ) ) {
        $aat = function_exists( 'lunara_get_oscars_plugin' ) ? lunara_get_oscars_plugin() : null;
        foreach ( array_unique( array_map( 'intval', $matches[1] ) ) as $ceremony ) {
            if ( $ceremony <= 0 ) {
                continue;
            }

            $url   = ( $aat && method_exists( $aat, 'get_ceremony_url' ) ) ? $aat->get_ceremony_url( $ceremony ) : home_url( '/oscars/ceremony/' . $ceremony . '/' );
            $out[] = array(
                'label' => sprintf( __( 'Ceremony %d', 'lunara-film' ), $ceremony ),
                'url'   => $url,
            );
        }
    }

    return array_slice( $out, 0, 8 );
}

function lunara_control_desk_render_ledger_assistant_tab( $rows ) {
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Ledger-Aware Assistant', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Spot internal Oscar Ledger opportunities before publishing', 'lunara-film' ); ?></h2>
        </div>
        <div class="lunara-control-desk-card-grid">
            <?php foreach ( array_slice( $rows, 0, 18 ) as $row ) : ?>
                <?php
                $post          = $row['post'];
                $opportunities = lunara_control_desk_get_ledger_opportunities( $post );
                ?>
                <article class="lunara-control-desk-card" data-lunara-row-post="<?php echo esc_attr( $post->ID ); ?>">
                    <div class="lunara-control-desk-card-head">
                        <p class="lunara-control-desk-kicker"><?php echo esc_html( lunara_control_desk_post_type_label( $post->post_type ) ); ?></p>
                        <h3><?php echo esc_html( get_the_title( $post ) ? get_the_title( $post ) : __( '(Untitled)', 'lunara-film' ) ); ?></h3>
                    </div>
                    <?php if ( empty( $opportunities ) ) : ?>
                        <p class="lunara-control-desk-subtle"><?php esc_html_e( 'No obvious IMDb IDs, ceremony names, or major category phrases found yet.', 'lunara-film' ); ?></p>
                    <?php else : ?>
                        <ul class="lunara-control-desk-link-list">
                            <?php foreach ( $opportunities as $opportunity ) : ?>
                                <li><a href="<?php echo esc_url( $opportunity['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $opportunity['label'] ); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="lunara-control-desk-inline-result" data-lunara-result="<?php echo esc_attr( $post->ID ); ?>" hidden></div>
                    <div class="lunara-control-desk-actions">
                        <?php lunara_control_desk_render_suggest_button( $post->ID, 'ledger_links', __( 'Ask Gemini', 'lunara-film' ) ); ?>
                        <a class="button" href="<?php echo esc_url( get_edit_post_link( $post->ID, 'raw' ) ); ?>"><?php esc_html_e( 'Open Editor', 'lunara-film' ); ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function lunara_control_desk_render_useful_controls() {
    $controls = array(
        array( __( 'Homepage', 'lunara-film' ), home_url( '/' ), 'read' ),
        array( __( 'Reviews Archive', 'lunara-film' ), home_url( '/reviews/' ), 'read' ),
        array( __( 'Journal Archive', 'lunara-film' ), home_url( '/journal/' ), 'read' ),
        array( __( 'Oscars Portal', 'lunara-film' ), home_url( '/oscars/' ), 'read' ),
        array( __( 'Customizer: Home', 'lunara-film' ), lunara_control_desk_customizer_url( 'lunara_homepage_pulse_options' ), 'customize' ),
        array( __( 'Customizer: Reviews', 'lunara-film' ), lunara_control_desk_customizer_url( 'lunara_review_layout_options' ), 'customize' ),
        array( __( 'Customizer: Journal Defaults', 'lunara-film' ), lunara_control_desk_customizer_url( 'lunara_standard_post_options' ), 'customize' ),
        array( __( 'Customizer: Footer', 'lunara-film' ), lunara_control_desk_customizer_url( 'lunara_footer_options' ), 'customize' ),
        array( __( 'Carousel Manager', 'lunara-film' ), admin_url( 'themes.php?page=lunara-carousel-manager' ), 'manage_options' ),
        array( __( 'Block Migration', 'lunara-film' ), admin_url( 'tools.php?page=lunara-block-migration' ), 'edit_pages' ),
        array( __( 'AI Classic Settings', 'lunara-film' ), admin_url( 'options-general.php?page=lunara-ai-assistant-classic' ), 'manage_options' ),
    );
    ?>
    <section class="lunara-control-desk-panel">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Useful Controls', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Jump to the existing knobs without hunting', 'lunara-film' ); ?></h2>
        </div>
        <div class="lunara-control-desk-links">
            <?php foreach ( $controls as $control ) : ?>
                <?php if ( ! empty( $control[2] ) && ! current_user_can( $control[2] ) ) : ?>
                    <?php continue; ?>
                <?php endif; ?>
                <a class="button" href="<?php echo esc_url( $control[1] ); ?>"><?php echo esc_html( $control[0] ); ?></a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function lunara_control_desk_render_notice() {
    $notice = lunara_control_desk_get_request_key( 'lunara_notice', '' );

    if ( '' === $notice ) {
        return;
    }

    $messages = array(
        'journal_lead_saved'     => array(
            'class'   => 'notice-success',
            'message' => __( 'Journal lead updated. The homepage Journal lane and default Journal archive now share that lead.', 'lunara-film' ),
        ),
        'journal_lead_invalid'   => array(
            'class'   => 'notice-error',
            'message' => __( 'That Journal lead could not be saved. Pick a published Journal entry or leave the selector on automatic.', 'lunara-film' ),
        ),
        'journal_lead_forbidden' => array(
            'class'   => 'notice-error',
            'message' => __( 'You can view the Control Desk, but changing the Journal lead requires theme editing permission.', 'lunara-film' ),
        ),
        'journal_carousel_saved' => array(
            'class'   => 'notice-success',
            'message' => __( 'Journal visual file saved. The selected images now drive the single-entry carousel.', 'lunara-film' ),
        ),
        'journal_carousel_invalid' => array(
            'class'   => 'notice-error',
            'message' => __( 'That Journal visual file could not be saved. Pick an editable Journal entry and image attachments only.', 'lunara-film' ),
        ),
        'journal_carousel_forbidden' => array(
            'class'   => 'notice-error',
            'message' => __( 'You can view the Control Desk, but editing this Journal visual file requires permission to edit the selected entry.', 'lunara-film' ),
        ),
    );

    if ( empty( $messages[ $notice ] ) ) {
        return;
    }

    printf(
        '<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
        esc_attr( $messages[ $notice ]['class'] ),
        esc_html( $messages[ $notice ]['message'] )
    );
}

function lunara_render_control_desk_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( esc_html__( 'You do not have permission to access the Lunara Control Desk.', 'lunara-film' ) );
    }

    $rows            = lunara_control_desk_get_rows();
    $filtered_rows   = lunara_control_desk_filter_rows( $rows );
    $active_tab      = lunara_control_desk_get_active_tab();
    $review_count    = lunara_control_desk_count_by_type( $rows, 'review' );
    $journal_count   = lunara_control_desk_count_by_type( $rows, 'journal' );
    $standard_count  = lunara_control_desk_count_by_type( $rows, 'post' );
    $attention_count = lunara_control_desk_count_needing_attention( $rows );
    ?>
    <div class="wrap lunara-control-desk">
        <div class="lunara-control-desk-hero">
            <div>
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Lunara', 'lunara-film' ); ?></p>
                <h1><?php esc_html_e( 'Control Desk', 'lunara-film' ); ?></h1>
                <p class="lunara-control-desk-intro"><?php esc_html_e( 'Drafts, packaging gaps, private AI suggestions, homepage readiness, and Oscar Ledger opportunities in one place.', 'lunara-film' ); ?></p>
            </div>
            <div class="lunara-control-desk-actions">
                <a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=review' ) ); ?>"><?php esc_html_e( 'New Review', 'lunara-film' ); ?></a>
                <a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=journal' ) ); ?>"><?php esc_html_e( 'New Journal', 'lunara-film' ); ?></a>
            </div>
        </div>

        <?php lunara_control_desk_render_notice(); ?>

        <div class="lunara-control-desk-stats">
            <div><strong><?php echo esc_html( count( $rows ) ); ?></strong><span><?php esc_html_e( 'Draft or pending', 'lunara-film' ); ?></span></div>
            <div><strong><?php echo esc_html( $attention_count ); ?></strong><span><?php esc_html_e( 'Need attention', 'lunara-film' ); ?></span></div>
            <div><strong><?php echo esc_html( $review_count ); ?></strong><span><?php esc_html_e( 'Reviews', 'lunara-film' ); ?></span></div>
            <div><strong><?php echo esc_html( $journal_count ); ?></strong><span><?php esc_html_e( 'Journal', 'lunara-film' ); ?></span></div>
            <div><strong><?php echo esc_html( $standard_count ); ?></strong><span><?php esc_html_e( 'Posts', 'lunara-film' ); ?></span></div>
        </div>

        <?php lunara_control_desk_render_tabs( $active_tab ); ?>

        <?php
        switch ( $active_tab ) {
            case 'system-status':
                lunara_control_desk_render_system_status_tab();
                break;
            case 'operating-plan':
                lunara_control_desk_render_operating_plan_tab();
                break;
            case 'reviews':
                lunara_control_desk_render_review_pipeline_tab( $rows );
                break;
            case 'theme-studio':
                lunara_control_desk_render_theme_studio_tab();
                break;
            case 'oscars-integrity':
                lunara_control_desk_render_oscars_integrity_tab();
                break;
            case 'speed-stability':
                lunara_control_desk_render_speed_stability_tab();
                break;
            case 'visual-qa':
                lunara_control_desk_render_visual_qa_tab();
                break;
            case 'ai-operator':
            case 'ai-console':
                lunara_control_desk_render_ai_operator_tab( $rows );
                break;
            case 'field-suggestions':
                lunara_control_desk_render_field_suggestions_tab( $filtered_rows );
                break;
            case 'journal-growth':
                lunara_control_desk_render_journal_growth_tab( $rows );
                break;
            case 'homepage-board':
                lunara_control_desk_render_homepage_board_tab( $rows );
                break;
            case 'readiness':
                lunara_control_desk_render_filters( 'readiness' );
                lunara_control_desk_render_readiness_tab( $filtered_rows );
                break;
            case 'ledger-assistant':
                lunara_control_desk_render_ledger_assistant_tab( $filtered_rows );
                break;
            case 'publishing':
                lunara_control_desk_render_publishing_tab( $rows, $filtered_rows );
                break;
            default:
                lunara_control_desk_render_system_status_tab();
                break;
        }
        ?>

        <?php lunara_control_desk_render_useful_controls(); ?>
    </div>
    <?php
}
