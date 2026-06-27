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

function lunara_control_desk_brand_number_specs() {
    return array(
        'lunara_logo_max_height'               => array(
            'label'   => __( 'Header logo max height', 'lunara-film' ),
            'default' => 56,
            'min'     => 24,
            'max'     => 110,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Affects the public header mark across desktop and mobile.', 'lunara-film' ),
        ),
        'lunara_home_logo_desktop_max_width'   => array(
            'label'   => __( 'Homepage logo desktop max width', 'lunara-film' ),
            'default' => 1180,
            'min'     => 520,
            'max'     => 1600,
            'step'    => 10,
            'unit'    => 'px',
            'note'    => __( 'Caps the wide homepage identity mark on desktop viewports.', 'lunara-film' ),
        ),
        'lunara_home_logo_desktop_max_height'  => array(
            'label'   => __( 'Homepage logo desktop max height', 'lunara-film' ),
            'default' => 312,
            'min'     => 140,
            'max'     => 420,
            'step'    => 4,
            'unit'    => 'px',
            'note'    => __( 'Prevents the homepage logo from overwhelming the front door.', 'lunara-film' ),
        ),
        'lunara_home_logo_mobile_max_width'    => array(
            'label'   => __( 'Homepage logo mobile max width', 'lunara-film' ),
            'default' => 720,
            'min'     => 280,
            'max'     => 920,
            'step'    => 10,
            'unit'    => 'px',
            'note'    => __( 'Keeps the mark responsive without shrinking tablet layouts too far.', 'lunara-film' ),
        ),
        'lunara_home_logo_mobile_max_height'   => array(
            'label'   => __( 'Homepage logo mobile max height', 'lunara-film' ),
            'default' => 148,
            'min'     => 88,
            'max'     => 240,
            'step'    => 4,
            'unit'    => 'px',
            'note'    => __( 'Controls the 390px and small-screen logo chamber.', 'lunara-film' ),
        ),
        'lunara_home_logo_vertical_gap'        => array(
            'label'   => __( 'Homepage logo vertical spacing', 'lunara-film' ),
            'default' => 20,
            'min'     => 8,
            'max'     => 48,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Tunes the breathing room around the homepage identity stack.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_brand_clamp_number( $key, $value ) {
    $specs = lunara_control_desk_brand_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    $spec  = $specs[ $key ];
    if ( is_array( $value ) ) {
        $value = reset( $value );
    }
    $value = absint( $value );

    if ( $value < $spec['min'] ) {
        return absint( $spec['min'] );
    }

    if ( $value > $spec['max'] ) {
        return absint( $spec['max'] );
    }

    return $value;
}

function lunara_control_desk_brand_number_value( $key ) {
    $specs = lunara_control_desk_brand_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    return lunara_control_desk_brand_clamp_number(
        $key,
        get_theme_mod( $key, $specs[ $key ]['default'] )
    );
}

function lunara_control_desk_brand_image_is_valid( $attachment_id ) {
    $attachment_id = absint( $attachment_id );

    if ( ! $attachment_id ) {
        return true;
    }

    $mime = (string) get_post_mime_type( $attachment_id );

    return 0 === strpos( $mime, 'image/' );
}

function lunara_control_desk_save_brand_controls() {
    $redirect = lunara_control_desk_admin_url(
        array(
            'tab' => 'theme-studio',
        )
    ) . '#lunara-theme-studio-brand-console';

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'brand_controls_forbidden', $redirect ) );
        exit;
    }

    check_admin_referer( 'lunara_save_brand_controls', 'lunara_brand_nonce' );

    $header_logo_id = isset( $_POST['lunara_brand_header_logo_id'] ) ? absint( wp_unslash( $_POST['lunara_brand_header_logo_id'] ) ) : 0;
    $home_logo_id   = isset( $_POST['lunara_brand_home_logo_id'] ) ? absint( wp_unslash( $_POST['lunara_brand_home_logo_id'] ) ) : 0;
    $site_icon_id   = isset( $_POST['lunara_brand_site_icon_id'] ) ? absint( wp_unslash( $_POST['lunara_brand_site_icon_id'] ) ) : 0;
    $image_ids      = array( $header_logo_id, $home_logo_id, $site_icon_id );

    foreach ( $image_ids as $image_id ) {
        if ( ! lunara_control_desk_brand_image_is_valid( $image_id ) ) {
            wp_safe_redirect( add_query_arg( 'lunara_notice', 'brand_controls_invalid_image', $redirect ) );
            exit;
        }
    }

    $header_logo_id ? set_theme_mod( 'custom_logo', $header_logo_id ) : remove_theme_mod( 'custom_logo' );
    $home_logo_id ? update_option( 'lunara_home_identity_logo_id', $home_logo_id, false ) : delete_option( 'lunara_home_identity_logo_id' );
    $site_icon_id ? update_option( 'site_icon', $site_icon_id, false ) : delete_option( 'site_icon' );

    $raw_numbers = isset( $_POST['lunara_brand_number'] ) && is_array( $_POST['lunara_brand_number'] )
        ? wp_unslash( $_POST['lunara_brand_number'] )
        : array();
    $raw_resets  = isset( $_POST['lunara_brand_reset'] ) && is_array( $_POST['lunara_brand_reset'] )
        ? wp_unslash( $_POST['lunara_brand_reset'] )
        : array();
    $resets      = array_map( 'sanitize_key', array_keys( $raw_resets ) );

    foreach ( lunara_control_desk_brand_number_specs() as $key => $spec ) {
        if ( in_array( $key, $resets, true ) ) {
            remove_theme_mod( $key );
            continue;
        }

        if ( array_key_exists( $key, $raw_numbers ) ) {
            set_theme_mod( $key, (string) lunara_control_desk_brand_clamp_number( $key, $raw_numbers[ $key ] ) );
        }
    }

    wp_safe_redirect( add_query_arg( 'lunara_notice', 'brand_controls_saved', $redirect ) );
    exit;
}
add_action( 'admin_post_lunara_save_brand_controls', 'lunara_control_desk_save_brand_controls' );

function lunara_control_desk_homepage_select_specs() {
    return array(
        'lunara_home_front_door_density'      => array(
            'label'   => __( 'Front-door density', 'lunara-film' ),
            'default' => 'editorial',
            'note'    => __( 'Controls how tightly the identity, premise, and route cards travel together.', 'lunara-film' ),
            'options' => array(
                'compact'   => array(
                    'label' => __( 'Compact', 'lunara-film' ),
                    'copy'  => __( 'Sharper first-screen compression for days when the masthead feels too tall.', 'lunara-film' ),
                ),
                'editorial' => array(
                    'label' => __( 'Editorial', 'lunara-film' ),
                    'copy'  => __( 'The default publication rhythm: confident, dense, and still breathable.', 'lunara-film' ),
                ),
                'showcase'  => array(
                    'label' => __( 'Showcase', 'lunara-film' ),
                    'copy'  => __( 'A slightly roomier identity read for logo-forward presentation.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_home_route_card_prominence'   => array(
            'label'   => __( 'Route-card prominence', 'lunara-film' ),
            'default' => 'strong',
            'note'    => __( 'Tunes how much authority the Reviews, Journal, and Oscar Ledger doors carry.', 'lunara-film' ),
            'options' => array(
                'quiet'    => array(
                    'label' => __( 'Quiet', 'lunara-film' ),
                    'copy'  => __( 'Lower-contrast cards when the masthead art should dominate.', 'lunara-film' ),
                ),
                'standard' => array(
                    'label' => __( 'Standard', 'lunara-film' ),
                    'copy'  => __( 'Balanced cards with clear route access.', 'lunara-film' ),
                ),
                'strong'   => array(
                    'label' => __( 'Strong', 'lunara-film' ),
                    'copy'  => __( 'Premium front-door cards that read as core publication departments.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_home_first_section_rhythm'    => array(
            'label'   => __( 'First-section rhythm', 'lunara-film' ),
            'default' => 'tight',
            'note'    => __( 'Controls the vertical handoff from masthead into the first live content lane.', 'lunara-film' ),
            'options' => array(
                'tight'    => array(
                    'label' => __( 'Tight', 'lunara-film' ),
                    'copy'  => __( 'Gets readers from identity to active coverage faster.', 'lunara-film' ),
                ),
                'balanced' => array(
                    'label' => __( 'Balanced', 'lunara-film' ),
                    'copy'  => __( 'A moderate pause between brand and feed.', 'lunara-film' ),
                ),
                'spacious' => array(
                    'label' => __( 'Spacious', 'lunara-film' ),
                    'copy'  => __( 'More ceremony when the front door needs a calmer read.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_home_latest_reviews_density'  => array(
            'label'   => __( 'Latest Reviews density', 'lunara-film' ),
            'default' => 'editorial',
            'note'    => __( 'Tunes how much editorial pressure the homepage Reviews lane carries.', 'lunara-film' ),
            'options' => array(
                'compact'   => array(
                    'label' => __( 'Compact', 'lunara-film' ),
                    'copy'  => __( 'Tighter grid rhythm for a faster front-page scan.', 'lunara-film' ),
                ),
                'editorial' => array(
                    'label' => __( 'Editorial', 'lunara-film' ),
                    'copy'  => __( 'The default card rhythm: image-led, readable, and dense.', 'lunara-film' ),
                ),
                'showcase'  => array(
                    'label' => __( 'Showcase', 'lunara-film' ),
                    'copy'  => __( 'More room for pull quotes and larger criticism cards.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_home_journal_lane_density'    => array(
            'label'   => __( 'Journal lane density', 'lunara-film' ),
            'default' => 'editorial',
            'note'    => __( 'Controls how urgently the homepage Journal desk reads.', 'lunara-film' ),
            'options' => array(
                'compact'   => array(
                    'label' => __( 'Compact', 'lunara-film' ),
                    'copy'  => __( 'A tighter live-desk rail with shorter excerpts.', 'lunara-film' ),
                ),
                'editorial' => array(
                    'label' => __( 'Editorial', 'lunara-film' ),
                    'copy'  => __( 'Balanced movement for the default homepage desk lane.', 'lunara-film' ),
                ),
                'showcase'  => array(
                    'label' => __( 'Showcase', 'lunara-film' ),
                    'copy'  => __( 'Larger Journal cards for a more trade-front feel.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_home_oscar_facts_density'     => array(
            'label'   => __( 'Oscar Facts density', 'lunara-film' ),
            'default' => 'editorial',
            'note'    => __( 'Tunes the homepage Oscar Facts carousel as a signature retention lane.', 'lunara-film' ),
            'options' => array(
                'compact'   => array(
                    'label' => __( 'Compact', 'lunara-film' ),
                    'copy'  => __( 'A shorter carousel chamber that moves readers onward quickly.', 'lunara-film' ),
                ),
                'editorial' => array(
                    'label' => __( 'Editorial', 'lunara-film' ),
                    'copy'  => __( 'The default archival rhythm: premium but not overlong.', 'lunara-film' ),
                ),
                'showcase'  => array(
                    'label' => __( 'Showcase', 'lunara-film' ),
                    'copy'  => __( 'A bigger feature-lane read for the Oscar database flex.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_home_oscar_picks_density'     => array(
            'label'   => __( 'Oscar Picks density', 'lunara-film' ),
            'default' => 'editorial',
            'note'    => __( 'Tunes the homepage Oscar Picks rail without changing the curated pick posts.', 'lunara-film' ),
            'options' => array(
                'compact'   => array(
                    'label' => __( 'Compact', 'lunara-film' ),
                    'copy'  => __( 'A faster awards-season scan with tighter cards and less dwell time.', 'lunara-film' ),
                ),
                'editorial' => array(
                    'label' => __( 'Editorial', 'lunara-film' ),
                    'copy'  => __( 'The default rail rhythm: premium, readable, and active.', 'lunara-film' ),
                ),
                'showcase'  => array(
                    'label' => __( 'Showcase', 'lunara-film' ),
                    'copy'  => __( 'Larger cards when the picks lane should feel like a signature feature.', 'lunara-film' ),
                ),
            ),
        ),
    );
}

function lunara_control_desk_homepage_select_value( $key ) {
    $specs = lunara_control_desk_homepage_select_specs();

    if ( empty( $specs[ $key ] ) ) {
        return '';
    }

    $value = sanitize_key( (string) get_theme_mod( $key, $specs[ $key ]['default'] ) );

    if ( ! isset( $specs[ $key ]['options'][ $value ] ) ) {
        return (string) $specs[ $key ]['default'];
    }

    return $value;
}

function lunara_control_desk_homepage_order_preset_specs() {
    return array(
        'editorial-default' => array(
            'label'   => __( 'Editorial default', 'lunara-film' ),
            'copy'    => __( 'Reviews stay first on desktop, Journal stays early, and the Oscar lanes follow as signature retention.', 'lunara-film' ),
            'desktop_order' => array( 'hero', 'latest-reviews', 'dispatch', 'oscar-picks', 'oscar-facts', 'featured', 'oscar-spotlight', 'database', 'ledger', 'deep-cuts' ),
            'mobile_order'  => array( 'hero', 'dispatch', 'latest-reviews', 'oscar-picks', 'oscar-facts', 'featured', 'oscar-spotlight', 'database', 'ledger', 'deep-cuts' ),
            'desktop' => array(
                __( 'Identity masthead', 'lunara-film' ),
                __( 'Route cards', 'lunara-film' ),
                __( 'Latest Reviews', 'lunara-film' ),
                __( 'Journal lane', 'lunara-film' ),
                __( 'Oscar Facts', 'lunara-film' ),
            ),
            'mobile'  => array(
                __( 'Identity masthead', 'lunara-film' ),
                __( 'Route cards', 'lunara-film' ),
                __( 'Journal lane', 'lunara-film' ),
                __( 'Latest Reviews', 'lunara-film' ),
                __( 'Oscar Facts', 'lunara-film' ),
            ),
        ),
        'journal-first'     => array(
            'label'   => __( 'Journal first', 'lunara-film' ),
            'copy'    => __( 'Moves the live desk ahead of Reviews when the homepage should feel more immediate and trade-like.', 'lunara-film' ),
            'desktop_order' => array( 'hero', 'dispatch', 'latest-reviews', 'oscar-picks', 'oscar-facts', 'featured', 'oscar-spotlight', 'database', 'ledger', 'deep-cuts' ),
            'mobile_order'  => array( 'hero', 'dispatch', 'latest-reviews', 'oscar-picks', 'oscar-facts', 'featured', 'oscar-spotlight', 'database', 'ledger', 'deep-cuts' ),
            'desktop' => array(
                __( 'Identity masthead', 'lunara-film' ),
                __( 'Route cards', 'lunara-film' ),
                __( 'Journal lane', 'lunara-film' ),
                __( 'Latest Reviews', 'lunara-film' ),
                __( 'Oscar Facts', 'lunara-film' ),
            ),
            'mobile'  => array(
                __( 'Identity masthead', 'lunara-film' ),
                __( 'Route cards', 'lunara-film' ),
                __( 'Journal lane', 'lunara-film' ),
                __( 'Latest Reviews', 'lunara-film' ),
                __( 'Oscar Facts', 'lunara-film' ),
            ),
        ),
        'oscars-forward'    => array(
            'label'   => __( 'Oscars forward', 'lunara-film' ),
            'copy'    => __( 'Pushes Oscar Facts and ledger bridges higher when the Academy archive should become the front-door flex.', 'lunara-film' ),
            'desktop_order' => array( 'hero', 'oscar-facts', 'oscar-picks', 'oscar-spotlight', 'database', 'ledger', 'latest-reviews', 'dispatch', 'featured', 'deep-cuts' ),
            'mobile_order'  => array( 'hero', 'oscar-facts', 'oscar-picks', 'dispatch', 'latest-reviews', 'oscar-spotlight', 'database', 'ledger', 'featured', 'deep-cuts' ),
            'desktop' => array(
                __( 'Identity masthead', 'lunara-film' ),
                __( 'Route cards', 'lunara-film' ),
                __( 'Oscar Facts', 'lunara-film' ),
                __( 'Oscar Picks', 'lunara-film' ),
                __( 'Oscar Spotlight', 'lunara-film' ),
            ),
            'mobile'  => array(
                __( 'Identity masthead', 'lunara-film' ),
                __( 'Route cards', 'lunara-film' ),
                __( 'Oscar Facts', 'lunara-film' ),
                __( 'Oscar Picks', 'lunara-film' ),
                __( 'Journal lane', 'lunara-film' ),
            ),
        ),
    );
}

function lunara_control_desk_homepage_order_preset_value() {
    $specs = lunara_control_desk_homepage_order_preset_specs();
    $value = sanitize_key( (string) get_theme_mod( 'lunara_home_section_order_preset', 'editorial-default' ) );

    if ( ! isset( $specs[ $value ] ) ) {
        return 'editorial-default';
    }

    return $value;
}

function lunara_control_desk_homepage_order_for_preset( $preset, $context = 'desktop' ) {
    $specs  = lunara_control_desk_homepage_order_preset_specs();
    $preset = sanitize_key( (string) $preset );
    $context = 'mobile' === $context ? 'mobile' : 'desktop';

    if ( ! isset( $specs[ $preset ] ) ) {
        $preset = 'editorial-default';
    }

    $order_key = $context . '_order';
    $order     = isset( $specs[ $preset ][ $order_key ] ) ? $specs[ $preset ][ $order_key ] : array();

    if ( empty( $order ) && isset( $specs[ $preset ]['order'] ) ) {
        $order = $specs[ $preset ]['order'];
    }

    $order = implode( ',', $order );

    if ( function_exists( 'lunara_sanitize_home_section_order' ) ) {
        return lunara_sanitize_home_section_order( $order );
    }

    return $order;
}

function lunara_control_desk_homepage_comparison_specs() {
    return array(
        'lunara_home_front_door_density'    => array(
            'label' => __( 'Front-door density', 'lunara-film' ),
        ),
        'lunara_home_route_card_prominence' => array(
            'label' => __( 'Route-card prominence', 'lunara-film' ),
        ),
        'lunara_home_first_section_rhythm'  => array(
            'label' => __( 'First-section rhythm', 'lunara-film' ),
        ),
        'lunara_home_latest_reviews_density' => array(
            'label' => __( 'Latest Reviews density', 'lunara-film' ),
        ),
        'lunara_home_journal_lane_density'  => array(
            'label' => __( 'Journal lane density', 'lunara-film' ),
        ),
        'lunara_home_oscar_facts_density'   => array(
            'label' => __( 'Oscar Facts density', 'lunara-film' ),
        ),
        'lunara_home_oscar_picks_density'   => array(
            'label' => __( 'Oscar Picks density', 'lunara-film' ),
        ),
        'lunara_home_section_order_preset'  => array(
            'label' => __( 'Section order', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_homepage_preset_specs() {
    return array(
        'trade-front-door'   => array(
            'label'  => __( 'Trade Front Door', 'lunara-film' ),
            'copy'   => __( 'The balanced publication package: Reviews lead, Journal stays close, and Oscar Facts reads as a signature retention lane.', 'lunara-film' ),
            'values' => array(
                'lunara_home_front_door_density'     => 'editorial',
                'lunara_home_route_card_prominence'  => 'strong',
                'lunara_home_first_section_rhythm'   => 'tight',
                'lunara_home_latest_reviews_density' => 'editorial',
                'lunara_home_journal_lane_density'   => 'editorial',
                'lunara_home_oscar_facts_density'    => 'editorial',
                'lunara_home_oscar_picks_density'    => 'editorial',
                'lunara_home_section_order_preset'   => 'editorial-default',
            ),
        ),
        'journal-desk-day'   => array(
            'label'  => __( 'Journal Desk Day', 'lunara-film' ),
            'copy'   => __( 'For days when movement and industry coverage should feel urgent before the criticism grid settles in.', 'lunara-film' ),
            'values' => array(
                'lunara_home_front_door_density'     => 'compact',
                'lunara_home_route_card_prominence'  => 'standard',
                'lunara_home_first_section_rhythm'   => 'tight',
                'lunara_home_latest_reviews_density' => 'compact',
                'lunara_home_journal_lane_density'   => 'showcase',
                'lunara_home_oscar_facts_density'    => 'editorial',
                'lunara_home_oscar_picks_density'    => 'compact',
                'lunara_home_section_order_preset'   => 'journal-first',
            ),
        ),
        'oscars-signature'   => array(
            'label'  => __( 'Oscars Signature', 'lunara-film' ),
            'copy'   => __( 'Moves the Academy record forward so the homepage flexes the database without losing the live editorial desk.', 'lunara-film' ),
            'values' => array(
                'lunara_home_front_door_density'     => 'editorial',
                'lunara_home_route_card_prominence'  => 'strong',
                'lunara_home_first_section_rhythm'   => 'balanced',
                'lunara_home_latest_reviews_density' => 'compact',
                'lunara_home_journal_lane_density'   => 'editorial',
                'lunara_home_oscar_facts_density'    => 'showcase',
                'lunara_home_oscar_picks_density'    => 'showcase',
                'lunara_home_section_order_preset'   => 'oscars-forward',
            ),
        ),
        'criticism-showcase' => array(
            'label'  => __( 'Criticism Showcase', 'lunara-film' ),
            'copy'   => __( 'Gives the review lane extra room and weight when the front page should sell Lunara as a criticism destination.', 'lunara-film' ),
            'values' => array(
                'lunara_home_front_door_density'     => 'showcase',
                'lunara_home_route_card_prominence'  => 'strong',
                'lunara_home_first_section_rhythm'   => 'balanced',
                'lunara_home_latest_reviews_density' => 'showcase',
                'lunara_home_journal_lane_density'   => 'compact',
                'lunara_home_oscar_facts_density'    => 'compact',
                'lunara_home_oscar_picks_density'    => 'editorial',
                'lunara_home_section_order_preset'   => 'editorial-default',
            ),
        ),
    );
}

function lunara_control_desk_homepage_value_label( $key, $value ) {
    if ( null === $value || '' === (string) $value ) {
        return __( 'Default', 'lunara-film' );
    }

    $select_specs = lunara_control_desk_homepage_select_specs();
    if ( isset( $select_specs[ $key ]['options'][ $value ]['label'] ) ) {
        return $select_specs[ $key ]['options'][ $value ]['label'];
    }

    if ( 'lunara_home_section_order_preset' === $key ) {
        $order_specs = lunara_control_desk_homepage_order_preset_specs();
        if ( isset( $order_specs[ $value ]['label'] ) ) {
            return $order_specs[ $value ]['label'];
        }
    }

    return (string) $value;
}

function lunara_control_desk_homepage_active_preset_key() {
    $current = array(
        'lunara_home_section_order_preset' => lunara_control_desk_homepage_order_preset_value(),
    );

    foreach ( lunara_control_desk_homepage_select_specs() as $key => $spec ) {
        $current[ $key ] = lunara_control_desk_homepage_select_value( $key );
    }

    foreach ( lunara_control_desk_homepage_preset_specs() as $preset_key => $preset ) {
        $values = isset( $preset['values'] ) && is_array( $preset['values'] ) ? $preset['values'] : array();
        $match  = true;

        foreach ( lunara_control_desk_homepage_comparison_specs() as $key => $spec ) {
            if ( ! isset( $values[ $key ] ) || ! isset( $current[ $key ] ) || $values[ $key ] !== $current[ $key ] ) {
                $match = false;
                break;
            }
        }

        if ( $match ) {
            return $preset_key;
        }
    }

    return '';
}

function lunara_control_desk_homepage_number_specs() {
    return array(
        'lunara_home_masthead_top_padding'    => array(
            'label'   => __( 'Masthead top padding', 'lunara-film' ),
            'default' => 36,
            'min'     => 16,
            'max'     => 72,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Space above the homepage identity lockup.', 'lunara-film' ),
        ),
        'lunara_home_masthead_bottom_padding' => array(
            'label'   => __( 'Masthead bottom padding', 'lunara-film' ),
            'default' => 32,
            'min'     => 12,
            'max'     => 70,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Space below the route cards inside the masthead chamber.', 'lunara-film' ),
        ),
        'lunara_home_masthead_bottom_gap'     => array(
            'label'   => __( 'Masthead-to-content gap', 'lunara-film' ),
            'default' => 26,
            'min'     => 10,
            'max'     => 72,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'The public handoff from front-door identity into the first content lane.', 'lunara-film' ),
        ),
        'lunara_home_route_card_min_height'   => array(
            'label'   => __( 'Route-card minimum height', 'lunara-film' ),
            'default' => 126,
            'min'     => 88,
            'max'     => 190,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Keeps the three front-door departments visually even.', 'lunara-film' ),
        ),
        'lunara_home_section_gap'             => array(
            'label'   => __( 'Homepage section rhythm', 'lunara-film' ),
            'default' => 38,
            'min'     => 20,
            'max'     => 90,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Global spacing between major homepage modules after the masthead.', 'lunara-film' ),
        ),
        'lunara_home_latest_reviews_card_min_height' => array(
            'label'   => __( 'Latest Reviews card height', 'lunara-film' ),
            'default' => 430,
            'min'     => 340,
            'max'     => 560,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Minimum card height for the homepage Reviews lane.', 'lunara-film' ),
        ),
        'lunara_home_journal_card_min_height' => array(
            'label'   => __( 'Journal card height', 'lunara-film' ),
            'default' => 330,
            'min'     => 250,
            'max'     => 480,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Minimum card height for the homepage Journal lane.', 'lunara-film' ),
        ),
        'lunara_home_oscar_facts_card_min_height' => array(
            'label'   => __( 'Oscar Facts card height', 'lunara-film' ),
            'default' => 390,
            'min'     => 300,
            'max'     => 540,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Minimum feature height for the homepage Oscar Facts carousel.', 'lunara-film' ),
        ),
        'lunara_home_oscar_picks_count' => array(
            'label'   => __( 'Oscar Picks card count', 'lunara-film' ),
            'default' => 12,
            'min'     => 4,
            'max'     => 16,
            'step'    => 1,
            'unit'    => __( 'cards', 'lunara-film' ),
            'note'    => __( 'How many curated Oscar Pick cards render in the homepage rail.', 'lunara-film' ),
        ),
        'lunara_home_oscar_picks_card_min_height' => array(
            'label'   => __( 'Oscar Picks card height', 'lunara-film' ),
            'default' => 520,
            'min'     => 380,
            'max'     => 720,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Minimum card height for the homepage Oscar Picks rail.', 'lunara-film' ),
        ),
        'lunara_home_oscar_picks_autoplay_interval' => array(
            'label'   => __( 'Oscar Picks autoplay interval', 'lunara-film' ),
            'default' => 6500,
            'min'     => 0,
            'max'     => 12000,
            'step'    => 250,
            'unit'    => 'ms',
            'note'    => __( 'Set to 0 to stop automatic motion while keeping manual controls.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_homepage_clamp_number( $key, $value ) {
    $specs = lunara_control_desk_homepage_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    if ( is_array( $value ) ) {
        $value = reset( $value );
    }

    $spec  = $specs[ $key ];
    $value = absint( $value );

    if ( $value < $spec['min'] ) {
        return absint( $spec['min'] );
    }

    if ( $value > $spec['max'] ) {
        return absint( $spec['max'] );
    }

    return $value;
}

function lunara_control_desk_homepage_number_value( $key ) {
    $specs = lunara_control_desk_homepage_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    return lunara_control_desk_homepage_clamp_number(
        $key,
        get_theme_mod( $key, $specs[ $key ]['default'] )
    );
}

function lunara_control_desk_parse_oscar_pick_order( $raw ) {
    if ( is_array( $raw ) ) {
        $raw = implode( ',', $raw );
    }

    $ids = array();

    foreach ( preg_split( '/[\s,]+/', (string) $raw ) as $raw_id ) {
        $post_id = absint( $raw_id );

        if ( $post_id <= 0 || in_array( $post_id, $ids, true ) ) {
            continue;
        }

        if ( get_post_type( $post_id ) !== 'lunara_oscar_pick' ) {
            continue;
        }

        if ( get_post_status( $post_id ) !== 'publish' ) {
            continue;
        }

        $ids[] = $post_id;
    }

    return $ids;
}

function lunara_control_desk_get_homepage_oscar_pick_order_ids() {
    return lunara_control_desk_parse_oscar_pick_order( get_theme_mod( 'lunara_home_oscar_picks_manual_order', '' ) );
}

function lunara_control_desk_get_homepage_oscar_pick_posts_by_ids( $ids ) {
    $ids = lunara_control_desk_parse_oscar_pick_order( $ids );

    if ( empty( $ids ) ) {
        return array();
    }

    return get_posts(
        array(
            'post_type'           => 'lunara_oscar_pick',
            'post_status'         => 'publish',
            'post__in'            => $ids,
            'orderby'             => 'post__in',
            'posts_per_page'      => count( $ids ),
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        )
    );
}

function lunara_control_desk_get_homepage_oscar_pick_candidates( $exclude_ids = array(), $limit = 30 ) {
    $exclude_ids = array_map( 'absint', (array) $exclude_ids );

    return get_posts(
        array(
            'post_type'           => 'lunara_oscar_pick',
            'post_status'         => 'publish',
            'posts_per_page'      => absint( $limit ),
            'post__not_in'        => array_filter( $exclude_ids ),
            'meta_key'            => '_lunara_pick_ceremony_year',
            'orderby'             => array(
                'meta_value_num' => 'DESC',
                'date'           => 'DESC',
            ),
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        )
    );
}

function lunara_control_desk_homepage_visibility_specs() {
    return array(
        'lunara_home_show_latest_reviews'  => array(
            'label'   => __( 'Latest Reviews', 'lunara-film' ),
            'default' => true,
            'copy'    => __( 'The review-first desktop lane and criticism signal.', 'lunara-film' ),
        ),
        'lunara_home_show_dispatch'        => array(
            'label'   => __( 'Journal Lane', 'lunara-film' ),
            'default' => true,
            'copy'    => __( 'The mobile-first live desk and Journal signal.', 'lunara-film' ),
        ),
        'lunara_home_show_oscar_spotlight' => array(
            'label'   => __( 'Oscar Spotlight', 'lunara-film' ),
            'default' => true,
            'copy'    => __( 'The Oscar route-family bridge before deeper ledger modules.', 'lunara-film' ),
        ),
        'lunara_home_show_database'        => array(
            'label'   => __( 'Database Spotlight', 'lunara-film' ),
            'default' => true,
            'copy'    => __( 'The searchable archive/database invitation.', 'lunara-film' ),
        ),
        'lunara_home_show_ledger'          => array(
            'label'   => __( 'From the Ledger', 'lunara-film' ),
            'default' => true,
            'copy'    => __( 'The Oscar historical record lane.', 'lunara-film' ),
        ),
        'lunara_home_show_deep_cuts'       => array(
            'label'   => __( 'Deep Cut Stats', 'lunara-film' ),
            'default' => true,
            'copy'    => __( 'The statistics and discovery lane.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_save_homepage_studio() {
    $redirect = lunara_control_desk_admin_url(
        array(
            'tab' => 'theme-studio',
        )
    ) . '#lunara-theme-studio-homepage-studio';

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'homepage_studio_forbidden', $redirect ) );
        exit;
    }

    check_admin_referer( 'lunara_save_homepage_studio', 'lunara_homepage_nonce' );

    $homepage_presets = lunara_control_desk_homepage_preset_specs();
    $apply_preset_key = isset( $_POST['lunara_homepage_apply_preset'] )
        ? sanitize_key( wp_unslash( $_POST['lunara_homepage_apply_preset'] ) )
        : '';
    $apply_preset     = isset( $homepage_presets[ $apply_preset_key ] ) ? $homepage_presets[ $apply_preset_key ] : array();
    $apply_values     = isset( $apply_preset['values'] ) && is_array( $apply_preset['values'] ) ? $apply_preset['values'] : array();

    $raw_selects = isset( $_POST['lunara_homepage_select'] ) && is_array( $_POST['lunara_homepage_select'] )
        ? wp_unslash( $_POST['lunara_homepage_select'] )
        : array();

    foreach ( lunara_control_desk_homepage_select_specs() as $key => $spec ) {
        $value = isset( $apply_values[ $key ] ) ? sanitize_key( $apply_values[ $key ] ) : ( isset( $raw_selects[ $key ] ) ? sanitize_key( $raw_selects[ $key ] ) : (string) $spec['default'] );
        if ( ! isset( $spec['options'][ $value ] ) ) {
            $value = (string) $spec['default'];
        }
        set_theme_mod( $key, $value );
    }

    $order_specs  = lunara_control_desk_homepage_order_preset_specs();
    $order_preset = isset( $apply_values['lunara_home_section_order_preset'] )
        ? sanitize_key( $apply_values['lunara_home_section_order_preset'] )
        : ( isset( $_POST['lunara_homepage_order_preset'] ) ? sanitize_key( wp_unslash( $_POST['lunara_homepage_order_preset'] ) ) : 'editorial-default' );

    if ( ! isset( $order_specs[ $order_preset ] ) ) {
        $order_preset = 'editorial-default';
    }

    set_theme_mod( 'lunara_home_section_order_preset', $order_preset );
    set_theme_mod( 'lunara_home_section_order', lunara_control_desk_homepage_order_for_preset( $order_preset, 'desktop' ) );
    set_theme_mod( 'lunara_home_section_mobile_order', lunara_control_desk_homepage_order_for_preset( $order_preset, 'mobile' ) );

    $raw_numbers = isset( $_POST['lunara_homepage_number'] ) && is_array( $_POST['lunara_homepage_number'] )
        ? wp_unslash( $_POST['lunara_homepage_number'] )
        : array();
    $raw_resets  = isset( $_POST['lunara_homepage_reset'] ) && is_array( $_POST['lunara_homepage_reset'] )
        ? wp_unslash( $_POST['lunara_homepage_reset'] )
        : array();
    $resets      = array_map( 'sanitize_key', array_keys( $raw_resets ) );

    foreach ( lunara_control_desk_homepage_number_specs() as $key => $spec ) {
        if ( in_array( $key, $resets, true ) ) {
            remove_theme_mod( $key );
            continue;
        }

        if ( array_key_exists( $key, $raw_numbers ) ) {
            set_theme_mod( $key, (string) lunara_control_desk_homepage_clamp_number( $key, $raw_numbers[ $key ] ) );
        }
    }

    $reset_oscar_picks = ! empty( $_POST['lunara_home_oscar_picks_reset_order'] );
    $order_source       = isset( $_POST['lunara_home_oscar_picks_order_source'] )
        ? sanitize_key( wp_unslash( $_POST['lunara_home_oscar_picks_order_source'] ) )
        : 'fallback';
    $raw_oscar_order   = isset( $_POST['lunara_home_oscar_picks_manual_order'] )
        ? sanitize_textarea_field( wp_unslash( $_POST['lunara_home_oscar_picks_manual_order'] ) )
        : '';
    $oscar_pick_ids    = lunara_control_desk_parse_oscar_pick_order( $raw_oscar_order );
    $raw_oscar_adds    = isset( $_POST['lunara_home_oscar_picks_add'] ) && is_array( $_POST['lunara_home_oscar_picks_add'] )
        ? wp_unslash( $_POST['lunara_home_oscar_picks_add'] )
        : array();
    $add_oscar_pick_ids = lunara_control_desk_parse_oscar_pick_order( $raw_oscar_adds );

    foreach ( $add_oscar_pick_ids as $add_id ) {
        if ( ! in_array( $add_id, $oscar_pick_ids, true ) ) {
            $oscar_pick_ids[] = $add_id;
        }
    }

    $default_oscar_pick_ids       = lunara_control_desk_homepage_oscar_pick_default_order_ids();
    $oscar_pick_order_is_default = $oscar_pick_ids === array_values( array_map( 'absint', $default_oscar_pick_ids ) );
    $should_store_oscar_picks    = 'manual' === $order_source || ! empty( $add_oscar_pick_ids ) || ! $oscar_pick_order_is_default;

    if ( $reset_oscar_picks || empty( $oscar_pick_ids ) ) {
        remove_theme_mod( 'lunara_home_oscar_picks_manual_order' );
    } elseif ( $should_store_oscar_picks ) {
        set_theme_mod( 'lunara_home_oscar_picks_manual_order', implode( ',', $oscar_pick_ids ) );
    } else {
        remove_theme_mod( 'lunara_home_oscar_picks_manual_order' );
    }

    $raw_visible = isset( $_POST['lunara_homepage_visibility'] ) && is_array( $_POST['lunara_homepage_visibility'] )
        ? wp_unslash( $_POST['lunara_homepage_visibility'] )
        : array();
    $raw_known   = isset( $_POST['lunara_homepage_visibility_keys'] ) && is_array( $_POST['lunara_homepage_visibility_keys'] )
        ? wp_unslash( $_POST['lunara_homepage_visibility_keys'] )
        : array();
    $known       = array_map( 'sanitize_key', $raw_known );

    foreach ( lunara_control_desk_homepage_visibility_specs() as $key => $spec ) {
        if ( ! in_array( $key, $known, true ) ) {
            continue;
        }
        set_theme_mod( $key, isset( $raw_visible[ $key ] ) ? '1' : '0' );
    }

    $notice = $apply_values ? 'homepage_preset_applied' : 'homepage_studio_saved';
    wp_safe_redirect( add_query_arg( 'lunara_notice', $notice, $redirect ) );
    exit;
}
add_action( 'admin_post_lunara_save_homepage_studio', 'lunara_control_desk_save_homepage_studio' );

function lunara_control_desk_reviews_archive_select_specs() {
    return array(
        'lunara_reviews_archive_density'         => array(
            'label'   => __( 'Archive density', 'lunara-film' ),
            'default' => 'editorial',
            'note'    => __( 'Tunes the public Reviews desk from tighter scan to larger feature rhythm.', 'lunara-film' ),
            'options' => array(
                'compact'   => array(
                    'label' => __( 'Compact', 'lunara-film' ),
                    'copy'  => __( 'Shorter gaps and quicker card reads for a faster archive scan.', 'lunara-film' ),
                ),
                'editorial' => array(
                    'label' => __( 'Editorial', 'lunara-film' ),
                    'copy'  => __( 'The default Reviews desk rhythm: dense, legible, and publication-grade.', 'lunara-film' ),
                ),
                'showcase'  => array(
                    'label' => __( 'Showcase', 'lunara-film' ),
                    'copy'  => __( 'More room for the lead chamber and larger criticism cards.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_reviews_archive_lead_prominence' => array(
            'label'   => __( 'Lead review prominence', 'lunara-film' ),
            'default' => 'standard',
            'note'    => __( 'Controls how strongly the top review package anchors the archive.', 'lunara-film' ),
            'options' => array(
                'restrained' => array(
                    'label' => __( 'Restrained', 'lunara-film' ),
                    'copy'  => __( 'A slimmer lead chamber when the archive needs to move quickly.', 'lunara-film' ),
                ),
                'standard'   => array(
                    'label' => __( 'Standard', 'lunara-film' ),
                    'copy'  => __( 'Balanced lead emphasis with strong poster visibility.', 'lunara-film' ),
                ),
                'feature'    => array(
                    'label' => __( 'Feature', 'lunara-film' ),
                    'copy'  => __( 'A more commanding lead package for a premium front-of-archive read.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_reviews_archive_rail_density'    => array(
            'label'   => __( 'Companion rail density', 'lunara-film' ),
            'default' => 'editorial',
            'note'    => __( 'Tunes the native dynamic rail that carries current companion files.', 'lunara-film' ),
            'options' => array(
                'compact'   => array(
                    'label' => __( 'Compact', 'lunara-film' ),
                    'copy'  => __( 'Tighter rail cards and shorter excerpts.', 'lunara-film' ),
                ),
                'editorial' => array(
                    'label' => __( 'Editorial', 'lunara-film' ),
                    'copy'  => __( 'The default rail cadence with enough visual movement to keep readers moving.', 'lunara-film' ),
                ),
                'showcase'  => array(
                    'label' => __( 'Showcase', 'lunara-film' ),
                    'copy'  => __( 'Roomier companion cards for stronger poster and pull-quote presence.', 'lunara-film' ),
                ),
            ),
        ),
    );
}

function lunara_control_desk_reviews_archive_select_value( $key ) {
    $specs = lunara_control_desk_reviews_archive_select_specs();

    if ( empty( $specs[ $key ] ) ) {
        return '';
    }

    $value = sanitize_key( (string) get_theme_mod( $key, $specs[ $key ]['default'] ) );

    if ( ! isset( $specs[ $key ]['options'][ $value ] ) ) {
        return (string) $specs[ $key ]['default'];
    }

    return $value;
}

function lunara_control_desk_reviews_archive_number_specs() {
    return array(
        'lunara_reviews_archive_section_gap'         => array(
            'label'   => __( 'Section rhythm', 'lunara-film' ),
            'default' => 40,
            'min'     => 20,
            'max'     => 90,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Space between major Reviews archive modules.', 'lunara-film' ),
        ),
        'lunara_reviews_archive_lead_min_height'     => array(
            'label'   => __( 'Lead package height', 'lunara-film' ),
            'default' => 460,
            'min'     => 340,
            'max'     => 640,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Minimum height for the top lead review chamber.', 'lunara-film' ),
        ),
        'lunara_reviews_archive_card_min_height'     => array(
            'label'   => __( 'Archive card height', 'lunara-film' ),
            'default' => 360,
            'min'     => 260,
            'max'     => 540,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Minimum height for the review cards in the main archive run.', 'lunara-film' ),
        ),
        'lunara_reviews_archive_compact_media_width' => array(
            'label'   => __( 'Compact media width', 'lunara-film' ),
            'default' => 116,
            'min'     => 92,
            'max'     => 150,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Poster chamber width for compact companion cards.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_reviews_archive_clamp_number( $key, $value ) {
    $specs = lunara_control_desk_reviews_archive_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    if ( is_array( $value ) ) {
        $value = reset( $value );
    }

    $spec  = $specs[ $key ];
    $value = absint( $value );

    if ( $value < $spec['min'] ) {
        return absint( $spec['min'] );
    }

    if ( $value > $spec['max'] ) {
        return absint( $spec['max'] );
    }

    return $value;
}

function lunara_control_desk_reviews_archive_number_value( $key ) {
    $specs = lunara_control_desk_reviews_archive_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    return lunara_control_desk_reviews_archive_clamp_number(
        $key,
        get_theme_mod( $key, $specs[ $key ]['default'] )
    );
}

function lunara_control_desk_save_reviews_archive_studio() {
    $redirect = lunara_control_desk_admin_url(
        array(
            'tab' => 'theme-studio',
        )
    ) . '#lunara-theme-studio-reviews-archive-studio';

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'reviews_archive_studio_forbidden', $redirect ) );
        exit;
    }

    check_admin_referer( 'lunara_save_reviews_archive_studio', 'lunara_reviews_archive_nonce' );

    $raw_selects = isset( $_POST['lunara_reviews_archive_select'] ) && is_array( $_POST['lunara_reviews_archive_select'] )
        ? wp_unslash( $_POST['lunara_reviews_archive_select'] )
        : array();

    foreach ( lunara_control_desk_reviews_archive_select_specs() as $key => $spec ) {
        $value = isset( $raw_selects[ $key ] ) ? sanitize_key( $raw_selects[ $key ] ) : (string) $spec['default'];
        if ( ! isset( $spec['options'][ $value ] ) ) {
            $value = (string) $spec['default'];
        }
        set_theme_mod( $key, $value );
    }

    $raw_numbers = isset( $_POST['lunara_reviews_archive_number'] ) && is_array( $_POST['lunara_reviews_archive_number'] )
        ? wp_unslash( $_POST['lunara_reviews_archive_number'] )
        : array();
    $raw_resets  = isset( $_POST['lunara_reviews_archive_reset'] ) && is_array( $_POST['lunara_reviews_archive_reset'] )
        ? wp_unslash( $_POST['lunara_reviews_archive_reset'] )
        : array();
    $resets      = array_map( 'sanitize_key', array_keys( $raw_resets ) );

    foreach ( lunara_control_desk_reviews_archive_number_specs() as $key => $spec ) {
        if ( in_array( $key, $resets, true ) ) {
            remove_theme_mod( $key );
            continue;
        }

        if ( array_key_exists( $key, $raw_numbers ) ) {
            set_theme_mod( $key, (string) lunara_control_desk_reviews_archive_clamp_number( $key, $raw_numbers[ $key ] ) );
        }
    }

    wp_safe_redirect( add_query_arg( 'lunara_notice', 'reviews_archive_studio_saved', $redirect ) );
    exit;
}
add_action( 'admin_post_lunara_save_reviews_archive_studio', 'lunara_control_desk_save_reviews_archive_studio' );

function lunara_control_desk_review_card_image_focus_options() {
    return array(
        'center-center' => array(
            'label' => __( 'Center', 'lunara-film' ),
            'copy'  => __( 'Balanced crop focus for most review card art.', 'lunara-film' ),
        ),
        'center-top'    => array(
            'label' => __( 'Top', 'lunara-film' ),
            'copy'  => __( 'Protect faces, title text, and poster composition near the top.', 'lunara-film' ),
        ),
        'center-bottom' => array(
            'label' => __( 'Bottom', 'lunara-film' ),
            'copy'  => __( 'Favor lower poster or still composition when the top is empty.', 'lunara-film' ),
        ),
        'left-center'   => array(
            'label' => __( 'Left', 'lunara-film' ),
            'copy'  => __( 'Hold important figures or title art on the left side.', 'lunara-film' ),
        ),
        'right-center'  => array(
            'label' => __( 'Right', 'lunara-film' ),
            'copy'  => __( 'Hold important figures or title art on the right side.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_review_card_image_focus_specs() {
    $options = lunara_control_desk_review_card_image_focus_options();

    return array(
        'lunara_review_archive_image_focus' => array(
            'label'   => __( 'Archive card focus', 'lunara-film' ),
            'default' => 'center-center',
            'note'    => __( 'Tunes poster crops in the main Reviews archive run and shared review-card grids.', 'lunara-film' ),
            'options' => $options,
        ),
        'lunara_review_rail_image_focus'    => array(
            'label'   => __( 'Companion rail focus', 'lunara-film' ),
            'default' => 'center-center',
            'note'    => __( 'Tunes crops inside the moving companion rail on the Reviews archive.', 'lunara-film' ),
            'options' => $options,
        ),
        'lunara_review_related_image_focus' => array(
            'label'   => __( 'Related card focus', 'lunara-film' ),
            'default' => 'center-center',
            'note'    => __( 'Tunes related-review crops below single Review packages.', 'lunara-film' ),
            'options' => $options,
        ),
        'lunara_review_feature_image_focus' => array(
            'label'   => __( 'Feature image focus', 'lunara-film' ),
            'default' => 'center-center',
            'note'    => __( 'Tunes lead, feature, and hero-like Review image chambers without changing the source art.', 'lunara-film' ),
            'options' => $options,
        ),
    );
}

function lunara_control_desk_review_card_image_focus_value( $key ) {
    $specs = lunara_control_desk_review_card_image_focus_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 'center-center';
    }

    $value = sanitize_key( (string) get_theme_mod( $key, $specs[ $key ]['default'] ) );

    if ( ! isset( $specs[ $key ]['options'][ $value ] ) ) {
        return (string) $specs[ $key ]['default'];
    }

    return $value;
}

function lunara_control_desk_save_review_card_image_focus_controls() {
    $redirect = lunara_control_desk_admin_url(
        array(
            'tab' => 'theme-studio',
        )
    ) . '#lunara-theme-studio-review-card-image-focus';

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'review_card_image_focus_forbidden', $redirect ) );
        exit;
    }

    check_admin_referer( 'lunara_save_review_card_image_focus_controls', 'lunara_review_card_image_focus_nonce' );

    $raw_selects = isset( $_POST['lunara_review_card_image_focus_select'] ) && is_array( $_POST['lunara_review_card_image_focus_select'] )
        ? wp_unslash( $_POST['lunara_review_card_image_focus_select'] )
        : array();

    foreach ( lunara_control_desk_review_card_image_focus_specs() as $key => $spec ) {
        $value = isset( $raw_selects[ $key ] ) ? sanitize_key( $raw_selects[ $key ] ) : (string) $spec['default'];
        if ( ! isset( $spec['options'][ $value ] ) ) {
            $value = (string) $spec['default'];
        }
        set_theme_mod( $key, $value );
    }

    wp_safe_redirect( add_query_arg( 'lunara_notice', 'review_card_image_focus_saved', $redirect ) );
    exit;
}
add_action( 'admin_post_lunara_save_review_card_image_focus_controls', 'lunara_control_desk_save_review_card_image_focus_controls' );

function lunara_control_desk_review_single_select_specs() {
    return array(
        'lunara_review_single_density'             => array(
            'label'   => __( 'Review package density', 'lunara-film' ),
            'default' => 'editorial',
            'note'    => __( 'Controls the total single-review rhythm: body, rail, Debrief, and retention lanes.', 'lunara-film' ),
            'options' => array(
                'compact'   => array(
                    'label' => __( 'Compact', 'lunara-film' ),
                    'copy'  => __( 'A tighter criticism package when the review should move quickly.', 'lunara-film' ),
                ),
                'editorial' => array(
                    'label' => __( 'Editorial', 'lunara-film' ),
                    'copy'  => __( 'The default authority package: dense, readable, and visually held.', 'lunara-film' ),
                ),
                'feature'   => array(
                    'label' => __( 'Feature', 'lunara-film' ),
                    'copy'  => __( 'A roomier read for marquee reviews with stronger modules.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_review_single_hero_scale'          => array(
            'label'   => __( 'Hero image scale', 'lunara-film' ),
            'default' => 'standard',
            'note'    => __( 'Tunes how forcefully the visual lead enters before the criticism.', 'lunara-film' ),
            'options' => array(
                'standard'       => array(
                    'label' => __( 'Standard', 'lunara-film' ),
                    'copy'  => __( 'Balanced hero sizing for most reviews.', 'lunara-film' ),
                ),
                'poster-forward' => array(
                    'label' => __( 'Poster Forward', 'lunara-film' ),
                    'copy'  => __( 'Keeps poster-led reviews crisp without stretching them wide.', 'lunara-film' ),
                ),
                'wide-forward'   => array(
                    'label' => __( 'Wide Forward', 'lunara-film' ),
                    'copy'  => __( 'Gives backdrop and trailer-friendly reviews a stronger cinematic chamber.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_review_single_rail_mode'           => array(
            'label'   => __( 'Right rail mode', 'lunara-film' ),
            'default' => 'balanced',
            'note'    => __( 'Controls how much supporting metadata competes with the review body.', 'lunara-film' ),
            'options' => array(
                'balanced'         => array(
                    'label' => __( 'Balanced', 'lunara-film' ),
                    'copy'  => __( 'Ledger, watch, details, and archive actions share the rail evenly.', 'lunara-film' ),
                ),
                'minimal'          => array(
                    'label' => __( 'Minimal', 'lunara-film' ),
                    'copy'  => __( 'A calmer rail when the criticism should dominate.', 'lunara-film' ),
                ),
                'metadata-forward' => array(
                    'label' => __( 'Metadata Forward', 'lunara-film' ),
                    'copy'  => __( 'Stronger rail framing for Oscar and availability context.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_review_single_debrief_prominence'  => array(
            'label'   => __( 'Debrief prominence', 'lunara-film' ),
            'default' => 'standard',
            'note'    => __( 'Tunes the signature Debrief module without changing Debrief data.', 'lunara-film' ),
            'options' => array(
                'standard'        => array(
                    'label' => __( 'Standard', 'lunara-film' ),
                    'copy'  => __( 'Balanced Debrief presence below the main review.', 'lunara-film' ),
                ),
                'poster-forward'  => array(
                    'label' => __( 'Poster Forward', 'lunara-film' ),
                    'copy'  => __( 'Larger poster chamber with more visual confidence.', 'lunara-film' ),
                ),
                'signature-forward' => array(
                    'label' => __( 'Signature Forward', 'lunara-film' ),
                    'copy'  => __( 'Stronger text-side emphasis for the Lunara Debrief signature.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_review_single_pairing_density'     => array(
            'label'   => __( 'Pair It With density', 'lunara-film' ),
            'default' => 'editorial',
            'note'    => __( 'Tunes the companion pairing lane readers have been responding to.', 'lunara-film' ),
            'options' => array(
                'compact'   => array(
                    'label' => __( 'Compact', 'lunara-film' ),
                    'copy'  => __( 'Tighter pairing cards for a faster after-review scan.', 'lunara-film' ),
                ),
                'editorial' => array(
                    'label' => __( 'Editorial', 'lunara-film' ),
                    'copy'  => __( 'Default pairing rhythm with strong readability.', 'lunara-film' ),
                ),
                'showcase'  => array(
                    'label' => __( 'Showcase', 'lunara-film' ),
                    'copy'  => __( 'More room for each recommendation to feel premium.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_review_single_spoiler_treatment'   => array(
            'label'   => __( 'Spoiler shield treatment', 'lunara-film' ),
            'default' => 'standard',
            'note'    => __( 'Controls spoiler-warning emphasis without changing the reveal mechanism.', 'lunara-film' ),
            'options' => array(
                'standard'       => array(
                    'label' => __( 'Standard', 'lunara-film' ),
                    'copy'  => __( 'Clear spoiler guard with the current editorial styling.', 'lunara-film' ),
                ),
                'shield-forward' => array(
                    'label' => __( 'Shield Forward', 'lunara-film' ),
                    'copy'  => __( 'A more forceful protection chamber for full-spoiler reviews.', 'lunara-film' ),
                ),
                'high-contrast'  => array(
                    'label' => __( 'High Contrast', 'lunara-film' ),
                    'copy'  => __( 'Sharper warning treatment for readers who skim.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_review_single_trailer_prominence'  => array(
            'label'   => __( 'Trailer prominence', 'lunara-film' ),
            'default' => 'standard',
            'note'    => __( 'Tunes the embedded trailer chamber while preserving trailer placement rules.', 'lunara-film' ),
            'options' => array(
                'standard' => array(
                    'label' => __( 'Standard', 'lunara-film' ),
                    'copy'  => __( 'Current trailer treatment.', 'lunara-film' ),
                ),
                'centered' => array(
                    'label' => __( 'Centered', 'lunara-film' ),
                    'copy'  => __( 'Cleaner trade-publication centering for embedded trailers.', 'lunara-film' ),
                ),
                'feature'  => array(
                    'label' => __( 'Feature', 'lunara-film' ),
                    'copy'  => __( 'A larger trailer chamber when retention matters.', 'lunara-film' ),
                ),
            ),
        ),
    );
}

function lunara_control_desk_review_single_select_value( $key ) {
    $specs = lunara_control_desk_review_single_select_specs();

    if ( empty( $specs[ $key ] ) ) {
        return '';
    }

    $value = sanitize_key( (string) get_theme_mod( $key, $specs[ $key ]['default'] ) );

    if ( ! isset( $specs[ $key ]['options'][ $value ] ) ) {
        return (string) $specs[ $key ]['default'];
    }

    return $value;
}

function lunara_control_desk_review_single_number_specs() {
    return array(
        'lunara_review_single_section_gap'           => array(
            'label'   => __( 'Section rhythm', 'lunara-film' ),
            'default' => 48,
            'min'     => 24,
            'max'     => 96,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Space between the hero, body, Debrief, and related review package.', 'lunara-film' ),
        ),
        'lunara_review_single_debrief_poster_width'  => array(
            'label'   => __( 'Debrief poster width', 'lunara-film' ),
            'default' => 320,
            'min'     => 220,
            'max'     => 420,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Maximum poster chamber width inside the Debrief module.', 'lunara-film' ),
        ),
        'lunara_review_related_count'                => array(
            'label'   => __( 'Related review count', 'lunara-film' ),
            'default' => 4,
            'min'     => 2,
            'max'     => 6,
            'step'    => 1,
            'unit'    => __( 'cards', 'lunara-film' ),
            'note'    => __( 'How many related review cards appear after Debrief.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_review_pair_with_select_specs() {
    return array(
        'lunara_review_pair_with_layout'       => array(
            'label'   => __( 'Pair It With layout', 'lunara-film' ),
            'default' => 'wide',
            'note'    => __( 'Controls how much visual room the pairing module receives.', 'lunara-film' ),
            'options' => array(
                'contained' => array(
                    'label' => __( 'Contained', 'lunara-film' ),
                    'copy'  => __( 'Keeps the module aligned to the review column.', 'lunara-film' ),
                ),
                'wide'      => array(
                    'label' => __( 'Wide', 'lunara-film' ),
                    'copy'  => __( 'Lets pairings breathe as a stronger retention band.', 'lunara-film' ),
                ),
                'feature'   => array(
                    'label' => __( 'Feature', 'lunara-film' ),
                    'copy'  => __( 'A magazine-style pairing chamber for marquee reviews.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_review_pair_with_text_depth'   => array(
            'label'   => __( 'Pairing text depth', 'lunara-film' ),
            'default' => 'balanced',
            'note'    => __( 'Controls how much companion-note copy shows before clamping.', 'lunara-film' ),
            'options' => array(
                'tight'    => array(
                    'label' => __( 'Tight', 'lunara-film' ),
                    'copy'  => __( 'Keeps notes brisk for fast scanning.', 'lunara-film' ),
                ),
                'balanced' => array(
                    'label' => __( 'Balanced', 'lunara-film' ),
                    'copy'  => __( 'Default editorial note depth.', 'lunara-film' ),
                ),
                'full'     => array(
                    'label' => __( 'Full', 'lunara-film' ),
                    'copy'  => __( 'Gives recommendations more room to sell the connection.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_review_pair_with_mobile_stack' => array(
            'label'   => __( 'Mobile pairing stack', 'lunara-film' ),
            'default' => 'editorial',
            'note'    => __( 'Tunes the poster/text relationship on phones.', 'lunara-film' ),
            'options' => array(
                'compact'    => array(
                    'label' => __( 'Compact', 'lunara-film' ),
                    'copy'  => __( 'Small thumb, tight text, fastest mobile pass.', 'lunara-film' ),
                ),
                'editorial'  => array(
                    'label' => __( 'Editorial', 'lunara-film' ),
                    'copy'  => __( 'Balanced mobile layout for most reviews.', 'lunara-film' ),
                ),
                'poster-led' => array(
                    'label' => __( 'Poster-Led', 'lunara-film' ),
                    'copy'  => __( 'Lets poster art lead the pairing card on mobile.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_review_pair_with_image_focus'  => array(
            'label'   => __( 'Pairing image focus', 'lunara-film' ),
            'default' => 'center-center',
            'note'    => __( 'Adjusts existing pairing thumb crops without changing source images.', 'lunara-film' ),
            'options' => array(
                'center-center' => array(
                    'label' => __( 'Center', 'lunara-film' ),
                    'copy'  => __( 'Balanced default crop.', 'lunara-film' ),
                ),
                'center-top'    => array(
                    'label' => __( 'Top', 'lunara-film' ),
                    'copy'  => __( 'Protects faces or title art near the top.', 'lunara-film' ),
                ),
                'center-bottom' => array(
                    'label' => __( 'Bottom', 'lunara-film' ),
                    'copy'  => __( 'Protects lower-frame poster composition.', 'lunara-film' ),
                ),
                'left-center'   => array(
                    'label' => __( 'Left', 'lunara-film' ),
                    'copy'  => __( 'Favors left-weighted artwork.', 'lunara-film' ),
                ),
                'right-center'  => array(
                    'label' => __( 'Right', 'lunara-film' ),
                    'copy'  => __( 'Favors right-weighted artwork.', 'lunara-film' ),
                ),
            ),
        ),
    );
}

function lunara_control_desk_review_pair_with_number_specs() {
    return array(
        'lunara_review_pair_with_columns'     => array(
            'label'   => __( 'Pairing columns', 'lunara-film' ),
            'default' => 1,
            'min'     => 1,
            'max'     => 3,
            'step'    => 1,
            'unit'    => __( 'cols', 'lunara-film' ),
            'note'    => __( 'Desktop column count for the Pair It With lane.', 'lunara-film' ),
        ),
        'lunara_review_pair_with_thumb_width' => array(
            'label'   => __( 'Pairing thumb width', 'lunara-film' ),
            'default' => 96,
            'min'     => 64,
            'max'     => 140,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Poster/thumb width inside each pairing card.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_review_pair_with_select_value( $key ) {
    $specs = lunara_control_desk_review_pair_with_select_specs();

    if ( empty( $specs[ $key ] ) ) {
        return '';
    }

    $value = sanitize_key( (string) get_theme_mod( $key, $specs[ $key ]['default'] ) );

    if ( ! isset( $specs[ $key ]['options'][ $value ] ) ) {
        return (string) $specs[ $key ]['default'];
    }

    return $value;
}

function lunara_control_desk_review_pair_with_clamp_number( $key, $value ) {
    $specs = lunara_control_desk_review_pair_with_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    if ( is_array( $value ) ) {
        $value = reset( $value );
    }

    $spec  = $specs[ $key ];
    $value = absint( $value );

    if ( $value < $spec['min'] ) {
        return absint( $spec['min'] );
    }

    if ( $value > $spec['max'] ) {
        return absint( $spec['max'] );
    }

    return $value;
}

function lunara_control_desk_review_pair_with_number_value( $key ) {
    $specs = lunara_control_desk_review_pair_with_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    return lunara_control_desk_review_pair_with_clamp_number(
        $key,
        get_theme_mod( $key, $specs[ $key ]['default'] )
    );
}

function lunara_control_desk_review_single_preset_specs() {
    return array(
        'editorial-balance' => array(
            'label'  => __( 'Editorial Balance', 'lunara-film' ),
            'copy'   => __( 'The default premium criticism package: readable, steady, and publication-grade.', 'lunara-film' ),
            'values' => array(
                'lunara_review_single_density'            => 'editorial',
                'lunara_review_single_hero_scale'         => 'standard',
                'lunara_review_single_rail_mode'          => 'balanced',
                'lunara_review_single_debrief_prominence' => 'standard',
                'lunara_review_single_pairing_density'    => 'editorial',
                'lunara_review_pair_with_layout'          => 'wide',
                'lunara_review_pair_with_text_depth'      => 'balanced',
                'lunara_review_pair_with_mobile_stack'    => 'editorial',
                'lunara_review_pair_with_image_focus'     => 'center-center',
                'lunara_review_single_spoiler_treatment'  => 'standard',
                'lunara_review_single_trailer_prominence' => 'centered',
                'lunara_review_single_section_gap'        => 48,
                'lunara_review_single_debrief_poster_width' => 320,
                'lunara_review_related_count'             => 4,
                'lunara_review_pair_with_columns'         => 1,
                'lunara_review_pair_with_thumb_width'     => 96,
            ),
        ),
        'cinematic-feature' => array(
            'label'  => __( 'Cinematic Feature', 'lunara-film' ),
            'copy'   => __( 'A stronger magazine-style package for marquee reviews with bigger visual and retention beats.', 'lunara-film' ),
            'values' => array(
                'lunara_review_single_density'            => 'feature',
                'lunara_review_single_hero_scale'         => 'wide-forward',
                'lunara_review_single_rail_mode'          => 'metadata-forward',
                'lunara_review_single_debrief_prominence' => 'signature-forward',
                'lunara_review_single_pairing_density'    => 'showcase',
                'lunara_review_pair_with_layout'          => 'feature',
                'lunara_review_pair_with_text_depth'      => 'full',
                'lunara_review_pair_with_mobile_stack'    => 'poster-led',
                'lunara_review_pair_with_image_focus'     => 'center-center',
                'lunara_review_single_spoiler_treatment'  => 'shield-forward',
                'lunara_review_single_trailer_prominence' => 'feature',
                'lunara_review_single_section_gap'        => 64,
                'lunara_review_single_debrief_poster_width' => 360,
                'lunara_review_related_count'             => 5,
                'lunara_review_pair_with_columns'         => 3,
                'lunara_review_pair_with_thumb_width'     => 112,
            ),
        ),
        'compact-dispatch'  => array(
            'label'  => __( 'Compact Dispatch', 'lunara-film' ),
            'copy'   => __( 'A tighter trade-desk read when pace matters and the criticism should move fast.', 'lunara-film' ),
            'values' => array(
                'lunara_review_single_density'            => 'compact',
                'lunara_review_single_hero_scale'         => 'poster-forward',
                'lunara_review_single_rail_mode'          => 'minimal',
                'lunara_review_single_debrief_prominence' => 'standard',
                'lunara_review_single_pairing_density'    => 'compact',
                'lunara_review_pair_with_layout'          => 'contained',
                'lunara_review_pair_with_text_depth'      => 'tight',
                'lunara_review_pair_with_mobile_stack'    => 'compact',
                'lunara_review_pair_with_image_focus'     => 'center-top',
                'lunara_review_single_spoiler_treatment'  => 'standard',
                'lunara_review_single_trailer_prominence' => 'centered',
                'lunara_review_single_section_gap'        => 36,
                'lunara_review_single_debrief_poster_width' => 280,
                'lunara_review_related_count'             => 3,
                'lunara_review_pair_with_columns'         => 1,
                'lunara_review_pair_with_thumb_width'     => 76,
            ),
        ),
        'spoiler-shield'    => array(
            'label'  => __( 'Spoiler Shield', 'lunara-film' ),
            'copy'   => __( 'A protected full-spoiler package with a more unmistakable warning chamber.', 'lunara-film' ),
            'values' => array(
                'lunara_review_single_density'            => 'editorial',
                'lunara_review_single_hero_scale'         => 'wide-forward',
                'lunara_review_single_rail_mode'          => 'balanced',
                'lunara_review_single_debrief_prominence' => 'poster-forward',
                'lunara_review_single_pairing_density'    => 'editorial',
                'lunara_review_pair_with_layout'          => 'wide',
                'lunara_review_pair_with_text_depth'      => 'balanced',
                'lunara_review_pair_with_mobile_stack'    => 'editorial',
                'lunara_review_pair_with_image_focus'     => 'center-center',
                'lunara_review_single_spoiler_treatment'  => 'high-contrast',
                'lunara_review_single_trailer_prominence' => 'feature',
                'lunara_review_single_section_gap'        => 56,
                'lunara_review_single_debrief_poster_width' => 340,
                'lunara_review_related_count'             => 4,
                'lunara_review_pair_with_columns'         => 2,
                'lunara_review_pair_with_thumb_width'     => 104,
            ),
        ),
    );
}

function lunara_control_desk_review_single_clamp_number( $key, $value ) {
    $specs = lunara_control_desk_review_single_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    if ( is_array( $value ) ) {
        $value = reset( $value );
    }

    $spec  = $specs[ $key ];
    $value = absint( $value );

    if ( $value < $spec['min'] ) {
        return absint( $spec['min'] );
    }

    if ( $value > $spec['max'] ) {
        return absint( $spec['max'] );
    }

    return $value;
}

function lunara_control_desk_review_single_number_value( $key ) {
    $specs = lunara_control_desk_review_single_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    return lunara_control_desk_review_single_clamp_number(
        $key,
        get_theme_mod( $key, $specs[ $key ]['default'] )
    );
}

function lunara_control_desk_review_single_current_values() {
    $values = array();

    foreach ( lunara_control_desk_review_single_select_specs() as $key => $spec ) {
        $values[ $key ] = lunara_control_desk_review_single_select_value( $key );
    }

    foreach ( lunara_control_desk_review_pair_with_select_specs() as $key => $spec ) {
        $values[ $key ] = lunara_control_desk_review_pair_with_select_value( $key );
    }

    foreach ( lunara_control_desk_review_single_number_specs() as $key => $spec ) {
        $values[ $key ] = lunara_control_desk_review_single_number_value( $key );
    }

    foreach ( lunara_control_desk_review_pair_with_number_specs() as $key => $spec ) {
        $values[ $key ] = lunara_control_desk_review_pair_with_number_value( $key );
    }

    return $values;
}

function lunara_control_desk_review_single_active_preset_key() {
    $current = lunara_control_desk_review_single_current_values();

    foreach ( lunara_control_desk_review_single_preset_specs() as $preset_key => $preset ) {
        $values = isset( $preset['values'] ) && is_array( $preset['values'] ) ? $preset['values'] : array();
        $match  = true;

        foreach ( $values as $key => $value ) {
            if ( ! array_key_exists( $key, $current ) || (string) $current[ $key ] !== (string) $value ) {
                $match = false;
                break;
            }
        }

        if ( $match ) {
            return $preset_key;
        }
    }

    return '';
}

function lunara_control_desk_apply_review_single_values( $values ) {
    if ( ! is_array( $values ) ) {
        return;
    }

    foreach ( lunara_control_desk_review_single_select_specs() as $key => $spec ) {
        if ( ! array_key_exists( $key, $values ) ) {
            continue;
        }

        $value = sanitize_key( (string) $values[ $key ] );
        if ( isset( $spec['options'][ $value ] ) ) {
            set_theme_mod( $key, $value );
        }
    }

    foreach ( lunara_control_desk_review_pair_with_select_specs() as $key => $spec ) {
        if ( ! array_key_exists( $key, $values ) ) {
            continue;
        }

        $value = sanitize_key( (string) $values[ $key ] );
        if ( isset( $spec['options'][ $value ] ) ) {
            set_theme_mod( $key, $value );
        }
    }

    foreach ( lunara_control_desk_review_single_number_specs() as $key => $spec ) {
        if ( array_key_exists( $key, $values ) ) {
            set_theme_mod( $key, (string) lunara_control_desk_review_single_clamp_number( $key, $values[ $key ] ) );
        }
    }

    foreach ( lunara_control_desk_review_pair_with_number_specs() as $key => $spec ) {
        if ( array_key_exists( $key, $values ) ) {
            set_theme_mod( $key, (string) lunara_control_desk_review_pair_with_clamp_number( $key, $values[ $key ] ) );
        }
    }
}

function lunara_control_desk_save_review_single_studio() {
    $redirect = lunara_control_desk_admin_url(
        array(
            'tab' => 'theme-studio',
        )
    ) . '#lunara-theme-studio-review-single-studio';

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'review_single_studio_forbidden', $redirect ) );
        exit;
    }

    check_admin_referer( 'lunara_save_review_single_studio', 'lunara_review_single_nonce' );

    $presets    = lunara_control_desk_review_single_preset_specs();
    $preset_key = isset( $_POST['lunara_review_single_preset'] ) ? sanitize_key( wp_unslash( $_POST['lunara_review_single_preset'] ) ) : '';

    if ( '' !== $preset_key && isset( $presets[ $preset_key ] ) ) {
        lunara_control_desk_apply_review_single_values( $presets[ $preset_key ]['values'] );
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'review_single_preset_applied', $redirect ) );
        exit;
    }

    $raw_selects = isset( $_POST['lunara_review_single_select'] ) && is_array( $_POST['lunara_review_single_select'] )
        ? wp_unslash( $_POST['lunara_review_single_select'] )
        : array();

    foreach ( lunara_control_desk_review_single_select_specs() as $key => $spec ) {
        $value = isset( $raw_selects[ $key ] ) ? sanitize_key( $raw_selects[ $key ] ) : (string) $spec['default'];
        if ( ! isset( $spec['options'][ $value ] ) ) {
            $value = (string) $spec['default'];
        }
        set_theme_mod( $key, $value );
    }

    $raw_pair_selects = isset( $_POST['lunara_review_pair_with_select'] ) && is_array( $_POST['lunara_review_pair_with_select'] )
        ? wp_unslash( $_POST['lunara_review_pair_with_select'] )
        : array();

    foreach ( lunara_control_desk_review_pair_with_select_specs() as $key => $spec ) {
        $value = isset( $raw_pair_selects[ $key ] ) ? sanitize_key( $raw_pair_selects[ $key ] ) : (string) $spec['default'];
        if ( ! isset( $spec['options'][ $value ] ) ) {
            $value = (string) $spec['default'];
        }
        set_theme_mod( $key, $value );
    }

    $raw_numbers = isset( $_POST['lunara_review_single_number'] ) && is_array( $_POST['lunara_review_single_number'] )
        ? wp_unslash( $_POST['lunara_review_single_number'] )
        : array();
    $raw_resets  = isset( $_POST['lunara_review_single_reset'] ) && is_array( $_POST['lunara_review_single_reset'] )
        ? wp_unslash( $_POST['lunara_review_single_reset'] )
        : array();
    $resets      = array_map( 'sanitize_key', array_keys( $raw_resets ) );

    foreach ( lunara_control_desk_review_single_number_specs() as $key => $spec ) {
        if ( in_array( $key, $resets, true ) ) {
            remove_theme_mod( $key );
            continue;
        }

        if ( array_key_exists( $key, $raw_numbers ) ) {
            set_theme_mod( $key, (string) lunara_control_desk_review_single_clamp_number( $key, $raw_numbers[ $key ] ) );
        }
    }

    $raw_pair_numbers = isset( $_POST['lunara_review_pair_with_number'] ) && is_array( $_POST['lunara_review_pair_with_number'] )
        ? wp_unslash( $_POST['lunara_review_pair_with_number'] )
        : array();
    $raw_pair_resets  = isset( $_POST['lunara_review_pair_with_reset'] ) && is_array( $_POST['lunara_review_pair_with_reset'] )
        ? wp_unslash( $_POST['lunara_review_pair_with_reset'] )
        : array();
    $pair_resets      = array_map( 'sanitize_key', array_keys( $raw_pair_resets ) );

    foreach ( lunara_control_desk_review_pair_with_number_specs() as $key => $spec ) {
        if ( in_array( $key, $pair_resets, true ) ) {
            remove_theme_mod( $key );
            continue;
        }

        if ( array_key_exists( $key, $raw_pair_numbers ) ) {
            set_theme_mod( $key, (string) lunara_control_desk_review_pair_with_clamp_number( $key, $raw_pair_numbers[ $key ] ) );
        }
    }

    wp_safe_redirect( add_query_arg( 'lunara_notice', 'review_single_studio_saved', $redirect ) );
    exit;
}
add_action( 'admin_post_lunara_save_review_single_studio', 'lunara_control_desk_save_review_single_studio' );

function lunara_control_desk_oscars_dossier_select_specs() {
    return array(
        'lunara_oscars_dossier_preset'           => array(
            'label'   => __( 'Saved package marker', 'lunara-film' ),
            'default' => 'historical-dossier',
            'note'    => __( 'Records which complete Oscars route-family preset should be treated as the saved baseline.', 'lunara-film' ),
            'options' => array(
                'historical-dossier' => array(
                    'label' => __( 'Historical Dossier', 'lunara-film' ),
                    'copy'  => __( 'Default premium balance for ceremony and category pages.', 'lunara-film' ),
                ),
                'ceremony-feature'   => array(
                    'label' => __( 'Ceremony Feature', 'lunara-film' ),
                    'copy'  => __( 'A stronger editorial read for ceremony pages and approved write-ups.', 'lunara-film' ),
                ),
                'compact-ledger'     => array(
                    'label' => __( 'Compact Ledger', 'lunara-film' ),
                    'copy'  => __( 'Tighter information density for fast scanning.', 'lunara-film' ),
                ),
                'profile-spotlight'  => array(
                    'label' => __( 'Profile Spotlight', 'lunara-film' ),
                    'copy'  => __( 'A more cinematic title and person file treatment.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_oscars_dossier_density'          => array(
            'label'   => __( 'Dossier density', 'lunara-film' ),
            'default' => 'balanced',
            'note'    => __( 'Tunes how tightly Oscars dossier modules stack across ceremony, category, title, and person routes.', 'lunara-film' ),
            'options' => array(
                'balanced' => array(
                    'label' => __( 'Balanced', 'lunara-film' ),
                    'copy'  => __( 'Premium spacing without letting the ledger go slack.', 'lunara-film' ),
                ),
                'dense'    => array(
                    'label' => __( 'Dense', 'lunara-film' ),
                    'copy'  => __( 'More modules in view for a sharper research-desk rhythm.', 'lunara-film' ),
                ),
                'showcase' => array(
                    'label' => __( 'Showcase', 'lunara-film' ),
                    'copy'  => __( 'More room for hero, profile, and editorial modules.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_oscars_ceremony_rhythm'          => array(
            'label'   => __( 'Ceremony rhythm', 'lunara-film' ),
            'default' => 'balanced',
            'note'    => __( 'Controls the emphasis between ceremony context, editorial write-up, major races, and ledger scanning.', 'lunara-film' ),
            'options' => array(
                'balanced'  => array(
                    'label' => __( 'Balanced', 'lunara-film' ),
                    'copy'  => __( 'The default ceremony dossier mix.', 'lunara-film' ),
                ),
                'editorial' => array(
                    'label' => __( 'Editorial', 'lunara-film' ),
                    'copy'  => __( 'Gives the ceremony thesis and approved guide copy more authority.', 'lunara-film' ),
                ),
                'ledger'    => array(
                    'label' => __( 'Ledger', 'lunara-film' ),
                    'copy'  => __( 'Keeps ceremony pages tighter and more data-forward.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_oscars_major_race_prominence'    => array(
            'label'   => __( 'Major-race prominence', 'lunara-film' ),
            'default' => 'standard',
            'note'    => __( 'Tunes Picture, Director, Actor, and Actress race cards when the ceremony page has that package.', 'lunara-film' ),
            'options' => array(
                'standard' => array(
                    'label' => __( 'Standard', 'lunara-film' ),
                    'copy'  => __( 'A clear premium race module without overtaking the page.', 'lunara-film' ),
                ),
                'feature'  => array(
                    'label' => __( 'Feature', 'lunara-film' ),
                    'copy'  => __( 'Larger race cards for a stronger awards-season read.', 'lunara-film' ),
                ),
                'compact'  => array(
                    'label' => __( 'Compact', 'lunara-film' ),
                    'copy'  => __( 'Tighter cards when the page needs to move faster.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_oscars_profile_scale'            => array(
            'label'   => __( 'Profile scale', 'lunara-film' ),
            'default' => 'standard',
            'note'    => __( 'Controls the cinematic weight of title, person, and company profile files.', 'lunara-film' ),
            'options' => array(
                'standard'  => array(
                    'label' => __( 'Standard', 'lunara-film' ),
                    'copy'  => __( 'A balanced Oscar profile file.', 'lunara-film' ),
                ),
                'cinematic' => array(
                    'label' => __( 'Cinematic', 'lunara-film' ),
                    'copy'  => __( 'More visual authority for title and person hero chambers.', 'lunara-film' ),
                ),
                'compact'   => array(
                    'label' => __( 'Compact', 'lunara-film' ),
                    'copy'  => __( 'Tighter profile files for quick research movement.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_oscars_profile_media_treatment'  => array(
            'label'   => __( 'Profile image chamber', 'lunara-film' ),
            'default' => 'poster-frame',
            'note'    => __( 'Controls whether title and person file images read as poster art, cinematic crops, or protected archival images.', 'lunara-film' ),
            'options' => array(
                'poster-frame'   => array(
                    'label' => __( 'Poster Frame', 'lunara-film' ),
                    'copy'  => __( 'Premium poster and portrait framing for most title and person files.', 'lunara-film' ),
                ),
                'cinematic-crop' => array(
                    'label' => __( 'Cinematic Crop', 'lunara-film' ),
                    'copy'  => __( 'A stronger cropped chamber when the source image can carry more drama.', 'lunara-film' ),
                ),
                'archival-fit'   => array(
                    'label' => __( 'Archival Fit', 'lunara-film' ),
                    'copy'  => __( 'Protects unusual or older images from being cut off.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_oscars_writeup_prominence'       => array(
            'label'   => __( 'Write-up prominence', 'lunara-film' ),
            'default' => 'inline',
            'note'    => __( 'Tunes how approved Dalton-authored ceremony guide copy sits near the top of ceremony pages.', 'lunara-film' ),
            'options' => array(
                'inline'  => array(
                    'label' => __( 'Inline', 'lunara-film' ),
                    'copy'  => __( 'The guide reads as part of the dossier flow.', 'lunara-film' ),
                ),
                'feature' => array(
                    'label' => __( 'Feature', 'lunara-film' ),
                    'copy'  => __( 'The guide becomes a stronger editorial module.', 'lunara-film' ),
                ),
                'compact' => array(
                    'label' => __( 'Compact', 'lunara-film' ),
                    'copy'  => __( 'The guide remains visible but more compressed.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_oscars_related_reviews_treatment' => array(
            'label'   => __( 'Related-review treatment', 'lunara-film' ),
            'default' => 'standard-grid',
            'note'    => __( 'Controls how Lunara review cards behave inside ceremony, category, title, and person Oscar files.', 'lunara-film' ),
            'options' => array(
                'standard-grid' => array(
                    'label' => __( 'Standard Grid', 'lunara-film' ),
                    'copy'  => __( 'A balanced card grid with the current publication rhythm.', 'lunara-film' ),
                ),
                'compact-rail'  => array(
                    'label' => __( 'Compact Rail', 'lunara-film' ),
                    'copy'  => __( 'A tighter retention lane when the page needs to move faster.', 'lunara-film' ),
                ),
                'feature-strip' => array(
                    'label' => __( 'Feature Strip', 'lunara-film' ),
                    'copy'  => __( 'A stronger first-card treatment for pages with one obvious criticism lead.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_oscars_title_image_focus'          => array(
            'label'   => __( 'Title/person image focus', 'lunara-film' ),
            'default' => 'center-center',
            'note'    => __( 'Sets poster, portrait, and review-card crop focus without stretching the original art.', 'lunara-film' ),
            'options' => array(
                'center-center' => array(
                    'label' => __( 'Center', 'lunara-film' ),
                    'copy'  => __( 'Default balanced crop focus.', 'lunara-film' ),
                ),
                'center-top'    => array(
                    'label' => __( 'Top', 'lunara-film' ),
                    'copy'  => __( 'Protect faces and title art near the top of the frame.', 'lunara-film' ),
                ),
                'center-bottom' => array(
                    'label' => __( 'Bottom', 'lunara-film' ),
                    'copy'  => __( 'Favor lower poster composition when the top is empty.', 'lunara-film' ),
                ),
                'left-center'   => array(
                    'label' => __( 'Left', 'lunara-film' ),
                    'copy'  => __( 'Hold important figures or lettering on the left side.', 'lunara-film' ),
                ),
                'right-center'  => array(
                    'label' => __( 'Right', 'lunara-film' ),
                    'copy'  => __( 'Hold important figures or lettering on the right side.', 'lunara-film' ),
                ),
            ),
        ),
    );
}

function lunara_control_desk_oscars_dossier_select_value( $key ) {
    $specs = lunara_control_desk_oscars_dossier_select_specs();

    if ( empty( $specs[ $key ] ) ) {
        return '';
    }

    $value = sanitize_key( (string) get_theme_mod( $key, $specs[ $key ]['default'] ) );

    if ( ! isset( $specs[ $key ]['options'][ $value ] ) ) {
        return (string) $specs[ $key ]['default'];
    }

    return $value;
}

function lunara_control_desk_oscars_dossier_number_specs() {
    return array(
        'lunara_oscars_dossier_section_gap' => array(
            'label'   => __( 'Dossier section gap', 'lunara-film' ),
            'default' => 48,
            'min'     => 24,
            'max'     => 96,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Vertical rhythm between ceremony, category, title, and person dossier modules.', 'lunara-film' ),
        ),
        'lunara_oscars_dossier_card_min'    => array(
            'label'   => __( 'Card minimum width', 'lunara-film' ),
            'default' => 280,
            'min'     => 220,
            'max'     => 420,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Minimum card width for responsive Oscars dossier grids and major-race cards.', 'lunara-film' ),
        ),
        'lunara_oscars_profile_media_width' => array(
            'label'   => __( 'Profile image width', 'lunara-film' ),
            'default' => 340,
            'min'     => 220,
            'max'     => 520,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Maximum title/person image chamber width on Oscars profile files.', 'lunara-film' ),
        ),
        'lunara_oscars_profile_media_height' => array(
            'label'   => __( 'Profile image height', 'lunara-film' ),
            'default' => 500,
            'min'     => 320,
            'max'     => 700,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Maximum title/person image chamber height before the image crops or contains.', 'lunara-film' ),
        ),
        'lunara_oscars_related_reviews_count' => array(
            'label'   => __( 'Related reviews shown', 'lunara-film' ),
            'default' => 6,
            'min'     => 2,
            'max'     => 8,
            'step'    => 1,
            'unit'    => '',
            'note'    => __( 'Maximum Lunara review cards shown on Oscars ceremony, category, title, and person routes.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_oscars_dossier_clamp_number( $key, $value ) {
    $specs = lunara_control_desk_oscars_dossier_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    if ( is_array( $value ) ) {
        $value = reset( $value );
    }

    $spec  = $specs[ $key ];
    $value = absint( $value );

    if ( $value < $spec['min'] ) {
        return absint( $spec['min'] );
    }

    if ( $value > $spec['max'] ) {
        return absint( $spec['max'] );
    }

    return $value;
}

function lunara_control_desk_oscars_dossier_number_value( $key ) {
    $specs = lunara_control_desk_oscars_dossier_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    return lunara_control_desk_oscars_dossier_clamp_number(
        $key,
        get_theme_mod( $key, $specs[ $key ]['default'] )
    );
}

function lunara_control_desk_oscars_dossier_preset_specs() {
    return array(
        'historical-dossier' => array(
            'label'  => __( 'Historical Dossier', 'lunara-film' ),
            'copy'   => __( 'The default premium ledger balance for ceremony and category pages.', 'lunara-film' ),
            'values' => array(
                'lunara_oscars_dossier_preset'        => 'historical-dossier',
                'lunara_oscars_dossier_density'       => 'balanced',
                'lunara_oscars_ceremony_rhythm'       => 'balanced',
                'lunara_oscars_major_race_prominence' => 'standard',
                'lunara_oscars_profile_scale'         => 'standard',
                'lunara_oscars_profile_media_treatment' => 'poster-frame',
                'lunara_oscars_writeup_prominence'    => 'inline',
                'lunara_oscars_related_reviews_treatment' => 'standard-grid',
                'lunara_oscars_title_image_focus'      => 'center-center',
                'lunara_oscars_dossier_section_gap'   => 48,
                'lunara_oscars_dossier_card_min'      => 280,
                'lunara_oscars_profile_media_width'    => 340,
                'lunara_oscars_profile_media_height'   => 500,
                'lunara_oscars_related_reviews_count'  => 6,
            ),
        ),
        'ceremony-feature'   => array(
            'label'  => __( 'Ceremony Feature', 'lunara-film' ),
            'copy'   => __( 'Raises ceremony thesis, guide copy, and major-race modules for an editorial awards-file read.', 'lunara-film' ),
            'values' => array(
                'lunara_oscars_dossier_preset'        => 'ceremony-feature',
                'lunara_oscars_dossier_density'       => 'showcase',
                'lunara_oscars_ceremony_rhythm'       => 'editorial',
                'lunara_oscars_major_race_prominence' => 'feature',
                'lunara_oscars_profile_scale'         => 'standard',
                'lunara_oscars_profile_media_treatment' => 'poster-frame',
                'lunara_oscars_writeup_prominence'    => 'feature',
                'lunara_oscars_related_reviews_treatment' => 'feature-strip',
                'lunara_oscars_title_image_focus'      => 'center-top',
                'lunara_oscars_dossier_section_gap'   => 64,
                'lunara_oscars_dossier_card_min'      => 316,
                'lunara_oscars_profile_media_width'    => 340,
                'lunara_oscars_profile_media_height'   => 500,
                'lunara_oscars_related_reviews_count'  => 6,
            ),
        ),
        'compact-ledger'     => array(
            'label'  => __( 'Compact Ledger', 'lunara-film' ),
            'copy'   => __( 'Tightens the Oscars surface so the historical record moves quickly without feeling bare.', 'lunara-film' ),
            'values' => array(
                'lunara_oscars_dossier_preset'        => 'compact-ledger',
                'lunara_oscars_dossier_density'       => 'dense',
                'lunara_oscars_ceremony_rhythm'       => 'ledger',
                'lunara_oscars_major_race_prominence' => 'compact',
                'lunara_oscars_profile_scale'         => 'compact',
                'lunara_oscars_profile_media_treatment' => 'archival-fit',
                'lunara_oscars_writeup_prominence'    => 'compact',
                'lunara_oscars_related_reviews_treatment' => 'compact-rail',
                'lunara_oscars_title_image_focus'      => 'center-center',
                'lunara_oscars_dossier_section_gap'   => 34,
                'lunara_oscars_dossier_card_min'      => 238,
                'lunara_oscars_profile_media_width'    => 280,
                'lunara_oscars_profile_media_height'   => 420,
                'lunara_oscars_related_reviews_count'  => 4,
            ),
        ),
        'profile-spotlight'  => array(
            'label'  => __( 'Profile Spotlight', 'lunara-film' ),
            'copy'   => __( 'Lets title and person files breathe while keeping category and ceremony pages disciplined.', 'lunara-film' ),
            'values' => array(
                'lunara_oscars_dossier_preset'        => 'profile-spotlight',
                'lunara_oscars_dossier_density'       => 'balanced',
                'lunara_oscars_ceremony_rhythm'       => 'balanced',
                'lunara_oscars_major_race_prominence' => 'standard',
                'lunara_oscars_profile_scale'         => 'cinematic',
                'lunara_oscars_profile_media_treatment' => 'cinematic-crop',
                'lunara_oscars_writeup_prominence'    => 'inline',
                'lunara_oscars_related_reviews_treatment' => 'feature-strip',
                'lunara_oscars_title_image_focus'      => 'center-top',
                'lunara_oscars_dossier_section_gap'   => 56,
                'lunara_oscars_dossier_card_min'      => 304,
                'lunara_oscars_profile_media_width'    => 430,
                'lunara_oscars_profile_media_height'   => 560,
                'lunara_oscars_related_reviews_count'  => 8,
            ),
        ),
    );
}

function lunara_control_desk_oscars_dossier_key_label( $key ) {
    $select_specs = lunara_control_desk_oscars_dossier_select_specs();
    if ( isset( $select_specs[ $key ]['label'] ) ) {
        return $select_specs[ $key ]['label'];
    }

    $number_specs = lunara_control_desk_oscars_dossier_number_specs();
    if ( isset( $number_specs[ $key ]['label'] ) ) {
        return $number_specs[ $key ]['label'];
    }

    return $key;
}

function lunara_control_desk_oscars_dossier_value_label( $key, $value ) {
    if ( '' === (string) $value ) {
        return __( 'Default', 'lunara-film' );
    }

    $select_specs = lunara_control_desk_oscars_dossier_select_specs();
    if ( isset( $select_specs[ $key ]['options'][ $value ]['label'] ) ) {
        return $select_specs[ $key ]['options'][ $value ]['label'];
    }

    $number_specs = lunara_control_desk_oscars_dossier_number_specs();
    if ( isset( $number_specs[ $key ] ) ) {
        return absint( $value ) . $number_specs[ $key ]['unit'];
    }

    return (string) $value;
}

function lunara_control_desk_oscars_dossier_comparison_specs() {
    return array(
        'lunara_oscars_dossier_density',
        'lunara_oscars_ceremony_rhythm',
        'lunara_oscars_major_race_prominence',
        'lunara_oscars_profile_scale',
        'lunara_oscars_writeup_prominence',
        'lunara_oscars_related_reviews_count',
        'lunara_oscars_related_reviews_treatment',
        'lunara_oscars_profile_media_treatment',
        'lunara_oscars_title_image_focus',
        'lunara_oscars_dossier_section_gap',
        'lunara_oscars_dossier_card_min',
        'lunara_oscars_profile_media_width',
        'lunara_oscars_profile_media_height',
    );
}

function lunara_control_desk_oscars_dossier_current_values() {
    $values = array();

    foreach ( lunara_control_desk_oscars_dossier_select_specs() as $key => $spec ) {
        $values[ $key ] = lunara_control_desk_oscars_dossier_select_value( $key );
    }

    foreach ( lunara_control_desk_oscars_dossier_number_specs() as $key => $spec ) {
        $values[ $key ] = lunara_control_desk_oscars_dossier_number_value( $key );
    }

    return $values;
}

function lunara_control_desk_oscars_dossier_active_preset_key() {
    $current = lunara_control_desk_oscars_dossier_current_values();

    foreach ( lunara_control_desk_oscars_dossier_preset_specs() as $preset_key => $preset ) {
        $values = isset( $preset['values'] ) && is_array( $preset['values'] ) ? $preset['values'] : array();
        $match  = true;

        foreach ( $values as $key => $value ) {
            if ( ! array_key_exists( $key, $current ) || (string) $current[ $key ] !== (string) $value ) {
                $match = false;
                break;
            }
        }

        if ( $match ) {
            return $preset_key;
        }
    }

    return '';
}

function lunara_control_desk_apply_oscars_dossier_values( $values ) {
    if ( ! is_array( $values ) ) {
        return;
    }

    foreach ( lunara_control_desk_oscars_dossier_select_specs() as $key => $spec ) {
        if ( ! array_key_exists( $key, $values ) ) {
            continue;
        }

        $value = sanitize_key( (string) $values[ $key ] );
        if ( isset( $spec['options'][ $value ] ) ) {
            set_theme_mod( $key, $value );
        }
    }

    foreach ( lunara_control_desk_oscars_dossier_number_specs() as $key => $spec ) {
        if ( array_key_exists( $key, $values ) ) {
            set_theme_mod( $key, (string) lunara_control_desk_oscars_dossier_clamp_number( $key, $values[ $key ] ) );
        }
    }
}

function lunara_control_desk_save_oscars_dossier_studio() {
    $redirect = lunara_control_desk_admin_url(
        array(
            'tab' => 'theme-studio',
        )
    ) . '#lunara-theme-studio-oscars-dossier-studio';

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'oscars_dossier_studio_forbidden', $redirect ) );
        exit;
    }

    check_admin_referer( 'lunara_save_oscars_dossier_studio', 'lunara_oscars_dossier_nonce' );

    $presets    = lunara_control_desk_oscars_dossier_preset_specs();
    $preset_key = isset( $_POST['lunara_oscars_dossier_preset'] ) ? sanitize_key( wp_unslash( $_POST['lunara_oscars_dossier_preset'] ) ) : '';

    if ( '' !== $preset_key && isset( $presets[ $preset_key ] ) ) {
        lunara_control_desk_apply_oscars_dossier_values( $presets[ $preset_key ]['values'] );
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'oscars_dossier_preset_applied', $redirect ) );
        exit;
    }

    $raw_selects = isset( $_POST['lunara_oscars_dossier_select'] ) && is_array( $_POST['lunara_oscars_dossier_select'] )
        ? wp_unslash( $_POST['lunara_oscars_dossier_select'] )
        : array();

    foreach ( lunara_control_desk_oscars_dossier_select_specs() as $key => $spec ) {
        $value = isset( $raw_selects[ $key ] ) ? sanitize_key( $raw_selects[ $key ] ) : (string) $spec['default'];
        if ( ! isset( $spec['options'][ $value ] ) ) {
            $value = (string) $spec['default'];
        }
        set_theme_mod( $key, $value );
    }

    $raw_numbers = isset( $_POST['lunara_oscars_dossier_number'] ) && is_array( $_POST['lunara_oscars_dossier_number'] )
        ? wp_unslash( $_POST['lunara_oscars_dossier_number'] )
        : array();
    $raw_resets  = isset( $_POST['lunara_oscars_dossier_reset'] ) && is_array( $_POST['lunara_oscars_dossier_reset'] )
        ? wp_unslash( $_POST['lunara_oscars_dossier_reset'] )
        : array();
    $resets      = array_map( 'sanitize_key', array_keys( $raw_resets ) );

    foreach ( lunara_control_desk_oscars_dossier_number_specs() as $key => $spec ) {
        if ( in_array( $key, $resets, true ) ) {
            remove_theme_mod( $key );
            continue;
        }

        if ( array_key_exists( $key, $raw_numbers ) ) {
            set_theme_mod( $key, (string) lunara_control_desk_oscars_dossier_clamp_number( $key, $raw_numbers[ $key ] ) );
        }
    }

    wp_safe_redirect( add_query_arg( 'lunara_notice', 'oscars_dossier_studio_saved', $redirect ) );
    exit;
}
add_action( 'admin_post_lunara_save_oscars_dossier_studio', 'lunara_control_desk_save_oscars_dossier_studio' );

function lunara_control_desk_journal_archive_select_specs() {
    return array(
        'lunara_journal_archive_density'         => array(
            'label'   => __( 'Archive density', 'lunara-film' ),
            'default' => 'editorial',
            'note'    => __( 'Tunes the Journal archive from fast live-desk scan to roomier feature presentation.', 'lunara-film' ),
            'options' => array(
                'compact'   => array(
                    'label' => __( 'Compact', 'lunara-film' ),
                    'copy'  => __( 'Shorter gaps and quicker cards for a breaking-desk feel.', 'lunara-film' ),
                ),
                'editorial' => array(
                    'label' => __( 'Editorial', 'lunara-film' ),
                    'copy'  => __( 'The default Journal rhythm: live, dense, and still readable.', 'lunara-film' ),
                ),
                'showcase'  => array(
                    'label' => __( 'Showcase', 'lunara-film' ),
                    'copy'  => __( 'More room for the lead file and wider image-led cards.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_journal_archive_lead_prominence' => array(
            'label'   => __( 'Lead file prominence', 'lunara-film' ),
            'default' => 'standard',
            'note'    => __( 'Controls how forcefully the curated lead entry anchors the Journal archive.', 'lunara-film' ),
            'options' => array(
                'restrained' => array(
                    'label' => __( 'Restrained', 'lunara-film' ),
                    'copy'  => __( 'A lower lead chamber when the archive should move immediately.', 'lunara-film' ),
                ),
                'standard'   => array(
                    'label' => __( 'Standard', 'lunara-film' ),
                    'copy'  => __( 'Balanced lead emphasis with confident image presence.', 'lunara-film' ),
                ),
                'feature'    => array(
                    'label' => __( 'Feature', 'lunara-film' ),
                    'copy'  => __( 'A stronger lead file for a more publication-front read.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_journal_archive_desk_rhythm'     => array(
            'label'   => __( 'Desk rhythm', 'lunara-film' ),
            'default' => 'balanced',
            'note'    => __( 'Tunes the command band, filters, and retention cards as one live-desk system.', 'lunara-film' ),
            'options' => array(
                'quick'     => array(
                    'label' => __( 'Quick', 'lunara-film' ),
                    'copy'  => __( 'Tighter deskbar and filters for faster scanning.', 'lunara-film' ),
                ),
                'balanced'  => array(
                    'label' => __( 'Balanced', 'lunara-film' ),
                    'copy'  => __( 'The default rhythm for a trade-desk archive.', 'lunara-film' ),
                ),
                'immersive' => array(
                    'label' => __( 'Immersive', 'lunara-film' ),
                    'copy'  => __( 'More atmosphere and retention weight without becoming sparse.', 'lunara-film' ),
                ),
            ),
        ),
    );
}

function lunara_control_desk_journal_archive_select_value( $key ) {
    $specs = lunara_control_desk_journal_archive_select_specs();

    if ( empty( $specs[ $key ] ) ) {
        return '';
    }

    $value = sanitize_key( (string) get_theme_mod( $key, $specs[ $key ]['default'] ) );

    if ( ! isset( $specs[ $key ]['options'][ $value ] ) ) {
        return (string) $specs[ $key ]['default'];
    }

    return $value;
}

function lunara_control_desk_journal_archive_number_specs() {
    return array(
        'lunara_journal_archive_section_gap'      => array(
            'label'   => __( 'Section rhythm', 'lunara-film' ),
            'default' => 38,
            'min'     => 18,
            'max'     => 86,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Space between major Journal archive modules.', 'lunara-film' ),
        ),
        'lunara_journal_archive_hero_min_height'  => array(
            'label'   => __( 'Hero command height', 'lunara-film' ),
            'default' => 240,
            'min'     => 160,
            'max'     => 420,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Minimum height for the top Journal identity chamber.', 'lunara-film' ),
        ),
        'lunara_journal_archive_card_min_height'  => array(
            'label'   => __( 'Archive card height', 'lunara-film' ),
            'default' => 390,
            'min'     => 280,
            'max'     => 560,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Minimum height for Journal cards in the main archive run.', 'lunara-film' ),
        ),
        'lunara_journal_archive_media_min_height' => array(
            'label'   => __( 'Wide media height', 'lunara-film' ),
            'default' => 220,
            'min'     => 160,
            'max'     => 360,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Minimum 16:10 image chamber height for Journal archive cards.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_journal_archive_clamp_number( $key, $value ) {
    $specs = lunara_control_desk_journal_archive_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    if ( is_array( $value ) ) {
        $value = reset( $value );
    }

    $spec  = $specs[ $key ];
    $value = absint( $value );

    if ( $value < $spec['min'] ) {
        return absint( $spec['min'] );
    }

    if ( $value > $spec['max'] ) {
        return absint( $spec['max'] );
    }

    return $value;
}

function lunara_control_desk_journal_archive_number_value( $key ) {
    $specs = lunara_control_desk_journal_archive_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    return lunara_control_desk_journal_archive_clamp_number(
        $key,
        get_theme_mod( $key, $specs[ $key ]['default'] )
    );
}

function lunara_control_desk_save_journal_archive_studio() {
    $redirect = lunara_control_desk_admin_url(
        array(
            'tab' => 'theme-studio',
        )
    ) . '#lunara-theme-studio-journal-archive-studio';

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'journal_archive_studio_forbidden', $redirect ) );
        exit;
    }

    check_admin_referer( 'lunara_save_journal_archive_studio', 'lunara_journal_archive_nonce' );

    $raw_selects = isset( $_POST['lunara_journal_archive_select'] ) && is_array( $_POST['lunara_journal_archive_select'] )
        ? wp_unslash( $_POST['lunara_journal_archive_select'] )
        : array();

    foreach ( lunara_control_desk_journal_archive_select_specs() as $key => $spec ) {
        $value = isset( $raw_selects[ $key ] ) ? sanitize_key( $raw_selects[ $key ] ) : (string) $spec['default'];
        if ( ! isset( $spec['options'][ $value ] ) ) {
            $value = (string) $spec['default'];
        }
        set_theme_mod( $key, $value );
    }

    $raw_numbers = isset( $_POST['lunara_journal_archive_number'] ) && is_array( $_POST['lunara_journal_archive_number'] )
        ? wp_unslash( $_POST['lunara_journal_archive_number'] )
        : array();
    $raw_resets  = isset( $_POST['lunara_journal_archive_reset'] ) && is_array( $_POST['lunara_journal_archive_reset'] )
        ? wp_unslash( $_POST['lunara_journal_archive_reset'] )
        : array();
    $resets      = array_map( 'sanitize_key', array_keys( $raw_resets ) );

    foreach ( lunara_control_desk_journal_archive_number_specs() as $key => $spec ) {
        if ( in_array( $key, $resets, true ) ) {
            remove_theme_mod( $key );
            continue;
        }

        if ( array_key_exists( $key, $raw_numbers ) ) {
            set_theme_mod( $key, (string) lunara_control_desk_journal_archive_clamp_number( $key, $raw_numbers[ $key ] ) );
        }
    }

    wp_safe_redirect( add_query_arg( 'lunara_notice', 'journal_archive_studio_saved', $redirect ) );
    exit;
}
add_action( 'admin_post_lunara_save_journal_archive_studio', 'lunara_control_desk_save_journal_archive_studio' );

function lunara_control_desk_utility_search_select_specs() {
    return array(
        'lunara_utility_search_density'        => array(
            'label'   => __( 'Search density', 'lunara-film' ),
            'default' => 'editorial',
            'note'    => __( 'Tunes how compact or showcase-led Search and 404 utility surfaces feel.', 'lunara-film' ),
            'options' => array(
                'compact'   => array(
                    'label' => __( 'Compact', 'lunara-film' ),
                    'copy'  => __( 'Tighter command rhythm for fast utility scanning.', 'lunara-film' ),
                ),
                'editorial' => array(
                    'label' => __( 'Editorial', 'lunara-film' ),
                    'copy'  => __( 'Default Lunara utility rhythm: useful, dense, and still premium.', 'lunara-film' ),
                ),
                'showcase'  => array(
                    'label' => __( 'Showcase', 'lunara-film' ),
                    'copy'  => __( 'Larger recovery and result chambers when the route should feel more cinematic.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_utility_result_treatment'      => array(
            'label'   => __( 'Result treatment', 'lunara-film' ),
            'default' => 'cards',
            'note'    => __( 'Changes the Search results run without changing query behavior or result order.', 'lunara-film' ),
            'options' => array(
                'list'      => array(
                    'label' => __( 'List', 'lunara-film' ),
                    'copy'  => __( 'A tighter publication index for high-volume result sets.', 'lunara-film' ),
                ),
                'cards'     => array(
                    'label' => __( 'Cards', 'lunara-film' ),
                    'copy'  => __( 'Balanced cards with clean media and direct Oscar matches.', 'lunara-film' ),
                ),
                'spotlight' => array(
                    'label' => __( 'Spotlight', 'lunara-film' ),
                    'copy'  => __( 'The first result gets stronger visual weight for confidence and retention.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_utility_result_media'          => array(
            'label'   => __( 'Result media', 'lunara-film' ),
            'default' => 'guarded',
            'note'    => __( 'Controls how assertively Search uses posters and wide media chambers.', 'lunara-film' ),
            'options' => array(
                'guarded'    => array(
                    'label' => __( 'Guarded', 'lunara-film' ),
                    'copy'  => __( 'Use media when present while keeping text-only results intentional.', 'lunara-film' ),
                ),
                'poster-led' => array(
                    'label' => __( 'Poster-led', 'lunara-film' ),
                    'copy'  => __( 'Prioritize stronger poster-like card presence.', 'lunara-film' ),
                ),
                'text-led'   => array(
                    'label' => __( 'Text-led', 'lunara-film' ),
                    'copy'  => __( 'Suppress decorative media weight when results should read like a clean index.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_utility_recovery_prominence'   => array(
            'label'   => __( 'Recovery prominence', 'lunara-film' ),
            'default' => 'standard',
            'note'    => __( 'Adjusts no-results and 404 recovery cards so utility routes still keep readers moving.', 'lunara-film' ),
            'options' => array(
                'quiet'    => array(
                    'label' => __( 'Quiet', 'lunara-film' ),
                    'copy'  => __( 'Useful recovery without overpowering the page.', 'lunara-film' ),
                ),
                'standard' => array(
                    'label' => __( 'Standard', 'lunara-film' ),
                    'copy'  => __( 'A confident recovery block with clear next routes.', 'lunara-film' ),
                ),
                'strong'   => array(
                    'label' => __( 'Strong', 'lunara-film' ),
                    'copy'  => __( 'Bigger recovery cards when lost readers need obvious direction.', 'lunara-film' ),
                ),
            ),
        ),
    );
}

function lunara_control_desk_utility_search_select_value( $key ) {
    $specs = lunara_control_desk_utility_search_select_specs();

    if ( empty( $specs[ $key ] ) ) {
        return '';
    }

    $value = sanitize_key( (string) get_theme_mod( $key, $specs[ $key ]['default'] ) );

    if ( ! isset( $specs[ $key ]['options'][ $value ] ) ) {
        return (string) $specs[ $key ]['default'];
    }

    return $value;
}

function lunara_control_desk_utility_search_focus_select_specs() {
    return array(
        'lunara_utility_search_lead_focus'     => array(
            'label'   => __( 'Search lead focus', 'lunara-film' ),
            'default' => 'balanced',
            'note'    => __( 'Chooses whether Search leads with the ledger or the editorial result run.', 'lunara-film' ),
            'options' => array(
                'balanced' => array(
                    'label' => __( 'Balanced', 'lunara-film' ),
                    'copy'  => __( 'Keep the current Search mix: direct Oscar signal first, then editorial results.', 'lunara-film' ),
                ),
                'ledger'   => array(
                    'label' => __( 'Ledger', 'lunara-film' ),
                    'copy'  => __( 'Make Oscar matches the priority chamber when the query points into the historical record.', 'lunara-film' ),
                ),
                'reviews'  => array(
                    'label' => __( 'Reviews', 'lunara-film' ),
                    'copy'  => __( 'Lead with Review results when criticism should carry the Search page.', 'lunara-film' ),
                ),
                'journal'  => array(
                    'label' => __( 'Journal', 'lunara-film' ),
                    'copy'  => __( 'Lead with Journal/editorial results when the Search page should feel like the desk.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_utility_search_spotlight_type' => array(
            'label'   => __( 'Result spotlight type', 'lunara-film' ),
            'default' => 'automatic',
            'note'    => __( 'Chooses which result type is eligible to move into the first-card spotlight.', 'lunara-film' ),
            'options' => array(
                'automatic' => array(
                    'label' => __( 'Automatic', 'lunara-film' ),
                    'copy'  => __( 'Let the selected lead focus decide the first result treatment.', 'lunara-film' ),
                ),
                'review'    => array(
                    'label' => __( 'Review', 'lunara-film' ),
                    'copy'  => __( 'Prefer Review cards for the first result chamber.', 'lunara-film' ),
                ),
                'journal'   => array(
                    'label' => __( 'Journal', 'lunara-film' ),
                    'copy'  => __( 'Prefer Journal/editorial entries for the first result chamber.', 'lunara-film' ),
                ),
                'page'      => array(
                    'label' => __( 'Page', 'lunara-film' ),
                    'copy'  => __( 'Prefer static route pages when Search should function like site navigation.', 'lunara-film' ),
                ),
            ),
        ),
        'lunara_utility_reentry_primary'       => array(
            'label'   => __( 'Recovery primary route', 'lunara-film' ),
            'default' => 'home',
            'note'    => __( 'Moves the chosen 404/recovery path into the primary re-entry position.', 'lunara-film' ),
            'options' => array(
                'home'    => array(
                    'label' => __( 'Home', 'lunara-film' ),
                    'copy'  => __( 'Use the homepage as the safest recovery path.', 'lunara-film' ),
                ),
                'reviews' => array(
                    'label' => __( 'Reviews', 'lunara-film' ),
                    'copy'  => __( 'Push lost readers back toward criticism first.', 'lunara-film' ),
                ),
                'journal' => array(
                    'label' => __( 'Journal', 'lunara-film' ),
                    'copy'  => __( 'Push lost readers back toward the editorial desk first.', 'lunara-film' ),
                ),
                'oscars'  => array(
                    'label' => __( 'Oscars', 'lunara-film' ),
                    'copy'  => __( 'Push lost readers toward the Oscar Ledger first.', 'lunara-film' ),
                ),
                'search'  => array(
                    'label' => __( 'Search', 'lunara-film' ),
                    'copy'  => __( 'Make Search the primary recovery action.', 'lunara-film' ),
                ),
            ),
        ),
    );
}

function lunara_control_desk_utility_search_focus_select_value( $key ) {
    $specs = lunara_control_desk_utility_search_focus_select_specs();

    if ( empty( $specs[ $key ] ) ) {
        return '';
    }

    $value = sanitize_key( (string) get_theme_mod( $key, $specs[ $key ]['default'] ) );

    if ( ! isset( $specs[ $key ]['options'][ $value ] ) ) {
        return (string) $specs[ $key ]['default'];
    }

    return $value;
}

function lunara_control_desk_utility_search_preset_specs() {
    return array(
        'balanced-desk'    => array(
            'label'  => __( 'Balanced Desk', 'lunara-film' ),
            'copy'   => __( 'The default publication utility rhythm: direct ledger signal, editorial cards, and clean homepage recovery.', 'lunara-film' ),
            'values' => array(
                'lunara_utility_search_preset'         => 'balanced-desk',
                'lunara_utility_search_density'        => 'editorial',
                'lunara_utility_result_treatment'      => 'cards',
                'lunara_utility_result_media'          => 'guarded',
                'lunara_utility_recovery_prominence'   => 'standard',
                'lunara_utility_search_lead_focus'     => 'balanced',
                'lunara_utility_search_spotlight_type' => 'automatic',
                'lunara_utility_reentry_primary'       => 'home',
                'lunara_utility_section_gap'           => 42,
                'lunara_utility_result_min_height'     => 158,
                'lunara_utility_card_grid_min'         => 280,
            ),
        ),
        'ledger-signal'    => array(
            'label'  => __( 'Ledger Signal', 'lunara-film' ),
            'copy'   => __( 'Oscar-aware search leads the room, with stronger recovery toward the historical ledger.', 'lunara-film' ),
            'values' => array(
                'lunara_utility_search_preset'         => 'ledger-signal',
                'lunara_utility_search_density'        => 'showcase',
                'lunara_utility_result_treatment'      => 'spotlight',
                'lunara_utility_result_media'          => 'guarded',
                'lunara_utility_recovery_prominence'   => 'strong',
                'lunara_utility_search_lead_focus'     => 'ledger',
                'lunara_utility_search_spotlight_type' => 'automatic',
                'lunara_utility_reentry_primary'       => 'oscars',
                'lunara_utility_section_gap'           => 46,
                'lunara_utility_result_min_height'     => 176,
                'lunara_utility_card_grid_min'         => 300,
            ),
        ),
        'criticism-run'    => array(
            'label'  => __( 'Criticism Run', 'lunara-film' ),
            'copy'   => __( 'Search behaves like a Reviews desk: poster-led, review-first, and tuned for criticism discovery.', 'lunara-film' ),
            'values' => array(
                'lunara_utility_search_preset'         => 'criticism-run',
                'lunara_utility_search_density'        => 'editorial',
                'lunara_utility_result_treatment'      => 'spotlight',
                'lunara_utility_result_media'          => 'poster-led',
                'lunara_utility_recovery_prominence'   => 'standard',
                'lunara_utility_search_lead_focus'     => 'reviews',
                'lunara_utility_search_spotlight_type' => 'review',
                'lunara_utility_reentry_primary'       => 'reviews',
                'lunara_utility_section_gap'           => 38,
                'lunara_utility_result_min_height'     => 190,
                'lunara_utility_card_grid_min'         => 288,
            ),
        ),
        'journal-desk'     => array(
            'label'  => __( 'Journal Desk', 'lunara-film' ),
            'copy'   => __( 'Search feels more like the live editorial desk, prioritizing Journal entries and quick movement.', 'lunara-film' ),
            'values' => array(
                'lunara_utility_search_preset'         => 'journal-desk',
                'lunara_utility_search_density'        => 'editorial',
                'lunara_utility_result_treatment'      => 'cards',
                'lunara_utility_result_media'          => 'guarded',
                'lunara_utility_recovery_prominence'   => 'standard',
                'lunara_utility_search_lead_focus'     => 'journal',
                'lunara_utility_search_spotlight_type' => 'journal',
                'lunara_utility_reentry_primary'       => 'journal',
                'lunara_utility_section_gap'           => 34,
                'lunara_utility_result_min_height'     => 168,
                'lunara_utility_card_grid_min'         => 270,
            ),
        ),
        'navigation-clean' => array(
            'label'  => __( 'Navigation Clean', 'lunara-film' ),
            'copy'   => __( 'A compact utility index for fast navigation, static pages, and search-first recovery.', 'lunara-film' ),
            'values' => array(
                'lunara_utility_search_preset'         => 'navigation-clean',
                'lunara_utility_search_density'        => 'compact',
                'lunara_utility_result_treatment'      => 'list',
                'lunara_utility_result_media'          => 'text-led',
                'lunara_utility_recovery_prominence'   => 'strong',
                'lunara_utility_search_lead_focus'     => 'balanced',
                'lunara_utility_search_spotlight_type' => 'page',
                'lunara_utility_reentry_primary'       => 'search',
                'lunara_utility_section_gap'           => 28,
                'lunara_utility_result_min_height'     => 132,
                'lunara_utility_card_grid_min'         => 240,
            ),
        ),
    );
}

function lunara_control_desk_utility_search_current_values() {
    $values = array();

    foreach ( lunara_control_desk_utility_search_select_specs() as $key => $spec ) {
        $values[ $key ] = lunara_control_desk_utility_search_select_value( $key );
    }

    foreach ( lunara_control_desk_utility_search_focus_select_specs() as $key => $spec ) {
        $values[ $key ] = lunara_control_desk_utility_search_focus_select_value( $key );
    }

    foreach ( lunara_control_desk_utility_search_number_specs() as $key => $spec ) {
        $values[ $key ] = lunara_control_desk_utility_search_number_value( $key );
    }

    $preset_key = sanitize_key( (string) get_theme_mod( 'lunara_utility_search_preset', '' ) );
    $presets    = lunara_control_desk_utility_search_preset_specs();
    if ( isset( $presets[ $preset_key ] ) ) {
        $values['lunara_utility_search_preset'] = $preset_key;
    }

    return $values;
}

function lunara_control_desk_utility_search_active_preset_key() {
    $current = lunara_control_desk_utility_search_current_values();

    foreach ( lunara_control_desk_utility_search_preset_specs() as $preset_key => $preset ) {
        $values = isset( $preset['values'] ) && is_array( $preset['values'] ) ? $preset['values'] : array();
        $match  = true;

        foreach ( $values as $key => $value ) {
            if ( 'lunara_utility_search_preset' === $key ) {
                continue;
            }

            if ( ! array_key_exists( $key, $current ) || (string) $current[ $key ] !== (string) $value ) {
                $match = false;
                break;
            }
        }

        if ( $match ) {
            return $preset_key;
        }
    }

    return '';
}

function lunara_control_desk_apply_utility_search_values( $values ) {
    $values       = is_array( $values ) ? $values : array();
    $select_specs = lunara_control_desk_utility_search_select_specs();
    $focus_specs  = lunara_control_desk_utility_search_focus_select_specs();
    $number_specs = lunara_control_desk_utility_search_number_specs();
    $presets      = lunara_control_desk_utility_search_preset_specs();

    foreach ( $values as $key => $value ) {
        if ( 'lunara_utility_search_preset' === $key ) {
            $preset_key = sanitize_key( (string) $value );
            if ( isset( $presets[ $preset_key ] ) ) {
                set_theme_mod( $key, $preset_key );
            }
            continue;
        }

        if ( isset( $select_specs[ $key ] ) ) {
            $value = sanitize_key( (string) $value );
            if ( ! isset( $select_specs[ $key ]['options'][ $value ] ) ) {
                $value = (string) $select_specs[ $key ]['default'];
            }
            set_theme_mod( $key, $value );
            continue;
        }

        if ( isset( $focus_specs[ $key ] ) ) {
            $value = sanitize_key( (string) $value );
            if ( ! isset( $focus_specs[ $key ]['options'][ $value ] ) ) {
                $value = (string) $focus_specs[ $key ]['default'];
            }
            set_theme_mod( $key, $value );
            continue;
        }

        if ( isset( $number_specs[ $key ] ) ) {
            set_theme_mod( $key, (string) lunara_control_desk_utility_search_clamp_number( $key, $value ) );
        }
    }
}

function lunara_control_desk_utility_search_number_specs() {
    return array(
        'lunara_utility_section_gap'       => array(
            'label'   => __( 'Section rhythm', 'lunara-film' ),
            'default' => 42,
            'min'     => 20,
            'max'     => 84,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Space between Search/404 command, match, result, and recovery modules.', 'lunara-film' ),
        ),
        'lunara_utility_result_min_height' => array(
            'label'   => __( 'Result card height', 'lunara-film' ),
            'default' => 158,
            'min'     => 118,
            'max'     => 260,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Minimum chamber height for Search result cards and direct Oscar matches.', 'lunara-film' ),
        ),
        'lunara_utility_card_grid_min'     => array(
            'label'   => __( 'Card grid width', 'lunara-film' ),
            'default' => 280,
            'min'     => 220,
            'max'     => 360,
            'step'    => 1,
            'unit'    => 'px',
            'note'    => __( 'Responsive minimum column width for utility result grids.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_utility_search_clamp_number( $key, $value ) {
    $specs = lunara_control_desk_utility_search_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    if ( is_array( $value ) ) {
        $value = reset( $value );
    }

    $spec  = $specs[ $key ];
    $value = absint( $value );

    if ( $value < $spec['min'] ) {
        return absint( $spec['min'] );
    }

    if ( $value > $spec['max'] ) {
        return absint( $spec['max'] );
    }

    return $value;
}

function lunara_control_desk_utility_search_number_value( $key ) {
    $specs = lunara_control_desk_utility_search_number_specs();

    if ( empty( $specs[ $key ] ) ) {
        return 0;
    }

    return lunara_control_desk_utility_search_clamp_number(
        $key,
        get_theme_mod( $key, $specs[ $key ]['default'] )
    );
}

function lunara_control_desk_save_utility_search_studio() {
    $redirect = lunara_control_desk_admin_url(
        array(
            'tab' => 'theme-studio',
        )
    ) . '#lunara-theme-studio-utility-search-studio';

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'utility_search_studio_forbidden', $redirect ) );
        exit;
    }

    check_admin_referer( 'lunara_save_utility_search_studio', 'lunara_utility_search_nonce' );

    $presets    = lunara_control_desk_utility_search_preset_specs();
    $preset_key = isset( $_POST['lunara_utility_search_preset'] ) ? sanitize_key( wp_unslash( $_POST['lunara_utility_search_preset'] ) ) : '';

    if ( '' !== $preset_key && isset( $presets[ $preset_key ] ) ) {
        lunara_control_desk_apply_utility_search_values( $presets[ $preset_key ]['values'] );
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'utility_search_preset_applied', $redirect ) );
        exit;
    }

    $raw_selects = isset( $_POST['lunara_utility_search_select'] ) && is_array( $_POST['lunara_utility_search_select'] )
        ? wp_unslash( $_POST['lunara_utility_search_select'] )
        : array();

    foreach ( lunara_control_desk_utility_search_select_specs() as $key => $spec ) {
        $value = isset( $raw_selects[ $key ] ) ? sanitize_key( $raw_selects[ $key ] ) : (string) $spec['default'];
        if ( ! isset( $spec['options'][ $value ] ) ) {
            $value = (string) $spec['default'];
        }
        set_theme_mod( $key, $value );
    }

    $raw_focus_selects = isset( $_POST['lunara_utility_search_focus_select'] ) && is_array( $_POST['lunara_utility_search_focus_select'] )
        ? wp_unslash( $_POST['lunara_utility_search_focus_select'] )
        : array();

    foreach ( lunara_control_desk_utility_search_focus_select_specs() as $key => $spec ) {
        $value = isset( $raw_focus_selects[ $key ] ) ? sanitize_key( $raw_focus_selects[ $key ] ) : (string) $spec['default'];
        if ( ! isset( $spec['options'][ $value ] ) ) {
            $value = (string) $spec['default'];
        }
        set_theme_mod( $key, $value );
    }

    $raw_numbers = isset( $_POST['lunara_utility_search_number'] ) && is_array( $_POST['lunara_utility_search_number'] )
        ? wp_unslash( $_POST['lunara_utility_search_number'] )
        : array();
    $raw_resets  = isset( $_POST['lunara_utility_search_reset'] ) && is_array( $_POST['lunara_utility_search_reset'] )
        ? wp_unslash( $_POST['lunara_utility_search_reset'] )
        : array();
    $resets      = array_map( 'sanitize_key', array_keys( $raw_resets ) );

    foreach ( lunara_control_desk_utility_search_number_specs() as $key => $spec ) {
        if ( in_array( $key, $resets, true ) ) {
            remove_theme_mod( $key );
            continue;
        }

        if ( array_key_exists( $key, $raw_numbers ) ) {
            set_theme_mod( $key, (string) lunara_control_desk_utility_search_clamp_number( $key, $raw_numbers[ $key ] ) );
        }
    }

    wp_safe_redirect( add_query_arg( 'lunara_notice', 'utility_search_studio_saved', $redirect ) );
    exit;
}
add_action( 'admin_post_lunara_save_utility_search_studio', 'lunara_control_desk_save_utility_search_studio' );

function lunara_control_desk_image_source_surfaces() {
    return array(
        'review-card'  => array(
            'post_type' => 'review',
            'label'     => __( 'Review card image', 'lunara-film' ),
            'meta_key'  => '_lunara_review_card_image',
        ),
        'journal-hero' => array(
            'post_type' => 'journal',
            'label'     => __( 'Journal featured image', 'lunara-film' ),
            'meta_key'  => '_thumbnail_id',
        ),
        'oscar-fact'   => array(
            'post_type' => 'oscar_fact',
            'label'     => __( 'Oscar Fact visual', 'lunara-film' ),
            'meta_key'  => '_thumbnail_id',
        ),
    );
}

function lunara_control_desk_image_source_redirect_url( $post_id = 0, $surface = '' ) {
    $anchor = 'lunara-theme-studio-image-quality';

    if ( $post_id && $surface ) {
        $anchor = 'lunara-image-source-' . sanitize_html_class( $surface ) . '-' . absint( $post_id );
    }

    return lunara_control_desk_admin_url(
        array(
            'tab' => 'theme-studio',
        )
    ) . '#' . $anchor;
}

function lunara_control_desk_image_quality_accept_meta_key( $surface ) {
    $surface = sanitize_key( $surface );

    if ( ! in_array( $surface, array( 'review-card', 'journal-hero' ), true ) ) {
        return '';
    }

    return '_lunara_image_quality_accept_' . str_replace( '-', '_', $surface );
}

function lunara_control_desk_image_quality_target_for_surface( $surface ) {
    $targets = lunara_control_desk_image_quality_targets();
    $surface = sanitize_key( $surface );

    if ( 'review-card' === $surface ) {
        return $targets['review-card'];
    }

    if ( 'journal-hero' === $surface ) {
        return $targets['hero'];
    }

    return array();
}

function lunara_control_desk_image_quality_apply_accepted_state( $post_id, $surface, $status ) {
    $meta_key = lunara_control_desk_image_quality_accept_meta_key( $surface );

    if ( ! $meta_key || empty( $status['reason'] ) || 'near-target' !== $status['reason'] || ! get_post_meta( $post_id, $meta_key, true ) ) {
        return $status;
    }

    $status['state']    = 'ready';
    $status['label']    = __( 'Accepted near-target', 'lunara-film' );
    $status['note']     = __( 'Theme Studio accepts this exact source as visually faithful while no stronger replacement is available.', 'lunara-film' );
    $status['accepted'] = true;

    return $status;
}

function lunara_control_desk_save_image_quality_acceptance( $post_id, $surface, $attachment_id, $accept_requested ) {
    $meta_key = lunara_control_desk_image_quality_accept_meta_key( $surface );

    if ( ! $meta_key ) {
        return;
    }

    $target = lunara_control_desk_image_quality_target_for_surface( $surface );
    $status = $target ? lunara_control_desk_image_quality_state( $attachment_id, $target ) : array();

    if ( $accept_requested && ! empty( $status['reason'] ) && 'near-target' === $status['reason'] ) {
        update_post_meta( $post_id, $meta_key, '1' );
        return;
    }

    delete_post_meta( $post_id, $meta_key );
}

function lunara_control_desk_save_image_source() {
    $post_id       = isset( $_POST['lunara_image_source_post_id'] ) ? absint( wp_unslash( $_POST['lunara_image_source_post_id'] ) ) : 0;
    $surface       = isset( $_POST['lunara_image_source_surface'] ) ? sanitize_key( wp_unslash( $_POST['lunara_image_source_surface'] ) ) : '';
    $attachment_id = isset( $_POST['lunara_image_source_attachment_id'] ) ? absint( wp_unslash( $_POST['lunara_image_source_attachment_id'] ) ) : 0;
    $accept_near_target = ! empty( $_POST['lunara_image_source_accept_near_target'] );
    $visual_ok     = ! empty( $_POST['lunara_image_source_visual_verified'] );
    $visual_treatment = isset( $_POST['lunara_image_source_visual_treatment'] ) ? sanitize_key( wp_unslash( $_POST['lunara_image_source_visual_treatment'] ) ) : '';
    $visual_treatment = 'archival' === $visual_treatment ? 'archival' : '';
    $visual_focus  = isset( $_POST['lunara_image_source_visual_focus'] ) ? sanitize_key( wp_unslash( $_POST['lunara_image_source_visual_focus'] ) ) : 'center';
    $visual_focus  = function_exists( 'lunara_sanitize_oscar_fact_visual_focus' ) ? lunara_sanitize_oscar_fact_visual_focus( $visual_focus ) : 'center';
    $redirect      = lunara_control_desk_image_source_redirect_url( $post_id, $surface );
    $surfaces      = lunara_control_desk_image_source_surfaces();

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'image_source_forbidden', $redirect ) );
        exit;
    }

    check_admin_referer( 'lunara_save_image_source', 'lunara_image_source_nonce' );

    if ( ! $post_id || empty( $surfaces[ $surface ] ) ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'image_source_invalid', $redirect ) );
        exit;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'image_source_forbidden_post', $redirect ) );
        exit;
    }

    $surface_config = $surfaces[ $surface ];
    if ( get_post_type( $post_id ) !== $surface_config['post_type'] ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'image_source_wrong_surface', $redirect ) );
        exit;
    }

    if ( $attachment_id && ! lunara_control_desk_brand_image_is_valid( $attachment_id ) ) {
        wp_safe_redirect( add_query_arg( 'lunara_notice', 'image_source_invalid_image', $redirect ) );
        exit;
    }

    if ( 'review-card' === $surface ) {
        if ( $attachment_id ) {
            $attachment_url = wp_get_attachment_url( $attachment_id );

            if ( ! $attachment_url ) {
                wp_safe_redirect( add_query_arg( 'lunara_notice', 'image_source_invalid_image', $redirect ) );
                exit;
            }

            update_post_meta( $post_id, $surface_config['meta_key'], esc_url_raw( $attachment_url ) );
        } else {
            delete_post_meta( $post_id, $surface_config['meta_key'] );
        }
    } elseif ( 'journal-hero' === $surface ) {
        if ( $attachment_id ) {
            set_post_thumbnail( $post_id, $attachment_id );
        } else {
            delete_post_thumbnail( $post_id );
        }
    } elseif ( 'oscar-fact' === $surface ) {
        if ( $attachment_id ) {
            set_post_thumbnail( $post_id, $attachment_id );

            if ( $visual_ok ) {
                update_post_meta( $post_id, '_lunara_fact_visual_verified', '1' );
            } else {
                delete_post_meta( $post_id, '_lunara_fact_visual_verified' );
            }

            if ( 'archival' === $visual_treatment ) {
                update_post_meta( $post_id, '_lunara_fact_visual_treatment', 'archival' );
            } else {
                delete_post_meta( $post_id, '_lunara_fact_visual_treatment' );
            }

            if ( 'center' !== $visual_focus ) {
                update_post_meta( $post_id, '_lunara_fact_visual_focus', $visual_focus );
            } else {
                delete_post_meta( $post_id, '_lunara_fact_visual_focus' );
            }
        } else {
            delete_post_thumbnail( $post_id );
            delete_post_meta( $post_id, '_lunara_fact_visual_verified' );
            delete_post_meta( $post_id, '_lunara_fact_visual_treatment' );
            delete_post_meta( $post_id, '_lunara_fact_visual_focus' );
        }
    }

    lunara_control_desk_save_image_quality_acceptance( $post_id, $surface, $attachment_id, $accept_near_target );
    clean_post_cache( $post_id );

    wp_safe_redirect( add_query_arg( 'lunara_notice', 'image_source_saved', $redirect ) );
    exit;
}
add_action( 'admin_post_lunara_save_image_source', 'lunara_control_desk_save_image_source' );

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
            'state' => '2.7.83' === $aat_version ? 'ready' : ( $aat_version ? 'weak' : 'needs' ),
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

function lunara_control_desk_get_omdb_review_state_cards() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return array(
            array(
                'label' => __( 'OMDb review queue', 'lunara-film' ),
                'value' => __( 'Admin only', 'lunara-film' ),
                'state' => 'weak',
                'note'  => __( 'Review-state counts are restricted to administrators.', 'lunara-film' ),
            ),
        );
    }

    global $wpdb;

    $table  = $wpdb->prefix . 'aat_omdb_reviews';
    $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

    if ( $exists !== $table ) {
        return array(
            array(
                'label' => __( 'OMDb review queue', 'lunara-film' ),
                'value' => __( 'Not initialized', 'lunara-film' ),
                'state' => 'weak',
                'note'  => __( 'Open Academy Awards > OMDb Audit once to initialize the review table.', 'lunara-film' ),
            ),
        );
    }

    $rows = $wpdb->get_results( "SELECT review_state, COUNT(*) AS count FROM {$table} GROUP BY review_state", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $counts = array(
        'needs_review'     => 0,
        'verified_bad_id'  => 0,
        'omdb_source_gap'  => 0,
        'poster_gap_only'  => 0,
        'resolved'         => 0,
        'ignore_accept'    => 0,
    );

    foreach ( is_array( $rows ) ? $rows : array() as $row ) {
        $state = sanitize_key( (string) ( $row['review_state'] ?? '' ) );
        if ( isset( $counts[ $state ] ) ) {
            $counts[ $state ] = absint( $row['count'] ?? 0 );
        }
    }

    $total_reviewed = array_sum( $counts );
    $last_reviewed  = $wpdb->get_var( "SELECT MAX(reviewed_at) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $last_label     = $last_reviewed
        ? wp_date( 'M j, Y g:i a', strtotime( $last_reviewed ) )
        : __( 'No reviewed rows yet', 'lunara-film' );

    return array(
        array(
            'label' => __( 'OMDb reviewed', 'lunara-film' ),
            'value' => sprintf( __( '%d rows', 'lunara-film' ), $total_reviewed ),
            'state' => $total_reviewed > 0 ? 'ready' : 'weak',
            'note'  => __( 'Private annotations only; Oscar rows remain untouched.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Verified bad IDs', 'lunara-film' ),
            'value' => (string) $counts['verified_bad_id'],
            'state' => $counts['verified_bad_id'] > 0 ? 'needs' : 'ready',
            'note'  => __( 'Rows that need correct IMDb IDs before any dataset mutation.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Ignore / accept', 'lunara-film' ),
            'value' => (string) $counts['ignore_accept'],
            'state' => $counts['ignore_accept'] > 0 ? 'ready' : 'weak',
            'note'  => __( 'Reviewed rows accepted as source/year drift rather than bad IDs.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Resolved', 'lunara-film' ),
            'value' => (string) $counts['resolved'],
            'state' => $counts['resolved'] > 0 ? 'ready' : 'weak',
            'note'  => __( 'Rows confirmed handled after a future correction pass.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Last reviewed', 'lunara-film' ),
            'value' => $last_label,
            'state' => $last_reviewed ? 'ready' : 'weak',
            'note'  => __( 'Use Academy Awards > OMDb Audit for row-by-row notes.', 'lunara-film' ),
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
            'value' => 'G:\\lunara-backups\\work\\academy-awards-table-optimized-ceremony-depth',
            'state' => 'ready',
            'note'  => 'feat/ceremony-depth-thesis @ 9aebfcc; ceremony full-ledger research rhythm for Oscars 2.7.83.',
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
            'value' => 'main @ 0c93adb',
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
            'value' => 'main @ d2e55f6',
            'state' => 'ready',
            'note'  => 'github.com/TheAntagonist2020/lunara-plugin-oscars-ledger',
        ),
        array(
            'label' => __( 'Dispatch repo', 'lunara-film' ),
            'value' => 'main @ 2c2dbc7',
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
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'OMDb Review Queue', 'lunara-film' ); ?></p>
            <h2><?php esc_html_e( 'Reviewed-state counts without opening the audit table', 'lunara-film' ); ?></h2>
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'This reads the private OMDb review annotations only. It does not scan OMDb, expose keys, or mutate Oscar rows.', 'lunara-film' ); ?></p>
        </div>
        <?php lunara_control_desk_render_status_cards( lunara_control_desk_get_omdb_review_state_cards() ); ?>
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

function lunara_control_desk_brand_media_summary( $attachment_id ) {
    $attachment_id = absint( $attachment_id );

    if ( ! $attachment_id ) {
        return array(
            'id'     => 0,
            'title'  => __( 'Using fallback/default', 'lunara-film' ),
            'meta'   => __( 'No custom image selected here.', 'lunara-film' ),
            'thumb'  => '',
            'status' => 'empty',
        );
    }

    $title = get_the_title( $attachment_id );
    $image = wp_get_attachment_image_src( $attachment_id, 'medium' );
    $full  = wp_get_attachment_image_src( $attachment_id, 'full' );
    $meta  = '';

    if ( ! empty( $full[1] ) && ! empty( $full[2] ) ) {
        $meta = sprintf(
            /* translators: 1: attachment ID, 2: image dimensions. */
            __( 'Attachment #%1$d / %2$s', 'lunara-film' ),
            $attachment_id,
            absint( $full[1] ) . 'x' . absint( $full[2] )
        );
    } else {
        $meta = sprintf(
            /* translators: %d: attachment ID. */
            __( 'Attachment #%d', 'lunara-film' ),
            $attachment_id
        );
    }

    return array(
        'id'     => $attachment_id,
        'title'  => $title ? $title : sprintf( __( 'Attachment #%d', 'lunara-film' ), $attachment_id ),
        'meta'   => $meta,
        'thumb'  => ! empty( $image[0] ) ? $image[0] : '',
        'status' => 'ready',
    );
}

function lunara_control_desk_render_brand_media_control( $control ) {
    $summary = lunara_control_desk_brand_media_summary( $control['value'] );
    ?>
    <article class="lunara-control-desk-brand-card" data-lunara-brand-media-control>
        <div class="lunara-control-desk-card-head">
            <div>
                <p class="lunara-control-desk-kicker"><?php echo esc_html( $control['eyebrow'] ); ?></p>
                <h3><?php echo esc_html( $control['label'] ); ?></h3>
                <p class="lunara-control-desk-subtle"><?php echo esc_html( $control['note'] ); ?></p>
            </div>
        </div>
        <div class="lunara-control-desk-brand-preview is-<?php echo esc_attr( $summary['status'] ); ?>" data-lunara-brand-media-preview>
            <div class="lunara-control-desk-brand-thumb">
                <?php if ( $summary['thumb'] ) : ?>
                    <img src="<?php echo esc_url( $summary['thumb'] ); ?>" alt="" />
                <?php endif; ?>
            </div>
            <div>
                <strong data-lunara-brand-media-title><?php echo esc_html( $summary['title'] ); ?></strong>
                <span data-lunara-brand-media-meta><?php echo esc_html( $summary['meta'] ); ?></span>
            </div>
        </div>
        <input data-lunara-brand-media-input type="hidden" name="<?php echo esc_attr( $control['field'] ); ?>" value="<?php echo esc_attr( absint( $control['value'] ) ); ?>" />
        <div class="lunara-control-desk-brand-affects">
            <strong><?php esc_html_e( 'What this affects', 'lunara-film' ); ?></strong>
            <span><?php echo esc_html( $control['affects'] ); ?></span>
        </div>
        <div class="lunara-control-desk-actions">
            <button
                type="button"
                class="button button-secondary"
                data-lunara-brand-media-picker
                data-title="<?php echo esc_attr( $control['picker_title'] ); ?>"
                data-button="<?php echo esc_attr( $control['picker_button'] ); ?>"
            >
                <?php esc_html_e( 'Choose Image', 'lunara-film' ); ?>
            </button>
            <button type="button" class="button" data-lunara-brand-media-clear><?php esc_html_e( 'Clear Field', 'lunara-film' ); ?></button>
        </div>
    </article>
    <?php
}

function lunara_control_desk_render_brand_number_control( $key, $spec ) {
    $value     = lunara_control_desk_brand_number_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <label class="lunara-control-desk-brand-number" data-lunara-brand-number-control>
        <span>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
        </span>
        <input
            type="range"
            min="<?php echo esc_attr( $spec['min'] ); ?>"
            max="<?php echo esc_attr( $spec['max'] ); ?>"
            step="<?php echo esc_attr( $spec['step'] ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            data-lunara-brand-range
        />
        <span class="lunara-control-desk-brand-number-value">
            <input
                type="number"
                name="lunara_brand_number[<?php echo esc_attr( $key ); ?>]"
                min="<?php echo esc_attr( $spec['min'] ); ?>"
                max="<?php echo esc_attr( $spec['max'] ); ?>"
                step="<?php echo esc_attr( $spec['step'] ); ?>"
                value="<?php echo esc_attr( $value ); ?>"
                data-lunara-brand-number
            />
            <em><?php echo esc_html( $spec['unit'] ); ?></em>
        </span>
        <span class="lunara-control-desk-brand-reset">
            <label>
                <input type="checkbox" name="lunara_brand_reset[<?php echo esc_attr( $key ); ?>]" value="1" />
                <?php
                printf(
                    /* translators: %d: setting default value. */
                    esc_html__( 'Reset to %d', 'lunara-film' ),
                    absint( $spec['default'] )
                );
                ?>
            </label>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </span>
    </label>
    <?php
}

function lunara_control_desk_render_brand_console() {
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        ?>
        <section id="lunara-theme-studio-brand-console" class="lunara-control-desk-brand-console">
            <div class="lunara-control-desk-panel-header">
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Brand Console', 'lunara-film' ); ?></p>
                <h3><?php esc_html_e( 'Brand controls require theme editing permission', 'lunara-film' ); ?></h3>
                <p class="lunara-control-desk-subtle"><?php esc_html_e( 'The map below remains available, but direct logo and identity changes are limited to administrators.', 'lunara-film' ); ?></p>
            </div>
        </section>
        <?php
        return;
    }

    $custom_logo_id = absint( get_theme_mod( 'custom_logo' ) );
    $home_logo_id   = absint( get_option( 'lunara_home_identity_logo_id', 0 ) );
    $site_icon_id   = absint( get_option( 'site_icon', 0 ) );
    $controls       = array(
        array(
            'eyebrow'       => __( 'Header', 'lunara-film' ),
            'label'         => __( 'Header logo media', 'lunara-film' ),
            'field'         => 'lunara_brand_header_logo_id',
            'value'         => $custom_logo_id,
            'note'          => __( 'Uses the existing WordPress custom_logo setting.', 'lunara-film' ),
            'affects'       => __( 'The public site header, mobile header, and any Blocksy surface reading the WordPress custom logo.', 'lunara-film' ),
            'picker_title'  => __( 'Choose header logo', 'lunara-film' ),
            'picker_button' => __( 'Use as header logo', 'lunara-film' ),
        ),
        array(
            'eyebrow'       => __( 'Homepage', 'lunara-film' ),
            'label'         => __( 'Homepage identity logo', 'lunara-film' ),
            'field'         => 'lunara_brand_home_logo_id',
            'value'         => $home_logo_id,
            'note'          => __( 'Uses lunara_home_identity_logo_id; when empty, the homepage falls back to the header logo.', 'lunara-film' ),
            'affects'       => __( 'The large homepage masthead identity mark only.', 'lunara-film' ),
            'picker_title'  => __( 'Choose homepage identity logo', 'lunara-film' ),
            'picker_button' => __( 'Use on homepage', 'lunara-film' ),
        ),
        array(
            'eyebrow'       => __( 'Social Preview', 'lunara-film' ),
            'label'         => __( 'Site icon and social fallback', 'lunara-film' ),
            'field'         => 'lunara_brand_site_icon_id',
            'value'         => $site_icon_id,
            'note'          => __( 'Uses the existing site_icon option and the current brand fallback behavior.', 'lunara-film' ),
            'affects'       => __( 'Browser icon, WordPress site icon output, and social fallback identity when no stronger image is available.', 'lunara-film' ),
            'picker_title'  => __( 'Choose site icon', 'lunara-film' ),
            'picker_button' => __( 'Use as site icon', 'lunara-film' ),
        ),
    );
    ?>
    <section id="lunara-theme-studio-brand-console" class="lunara-control-desk-brand-console">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Brand Console', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Direct logo controls without a code edit', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'This is the first Theme Studio control surface: bounded, reversible, and wired to the same storage the public site already reads.', 'lunara-film' ); ?></p>
        </div>
        <form class="lunara-control-desk-brand-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="lunara_save_brand_controls" />
            <?php wp_nonce_field( 'lunara_save_brand_controls', 'lunara_brand_nonce' ); ?>

            <div class="lunara-control-desk-brand-grid">
                <?php foreach ( $controls as $control ) : ?>
                    <?php lunara_control_desk_render_brand_media_control( $control ); ?>
                <?php endforeach; ?>
            </div>

            <div class="lunara-control-desk-brand-tuning">
                <div class="lunara-control-desk-card-head">
                    <div>
                        <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Sizing', 'lunara-film' ); ?></p>
                        <h3><?php esc_html_e( 'Logo geometry and spacing', 'lunara-film' ); ?></h3>
                        <p class="lunara-control-desk-subtle"><?php esc_html_e( 'All values are clamped server-side so the logo can be tuned without creating crop, blur-stretch, or overflow risk.', 'lunara-film' ); ?></p>
                    </div>
                </div>
                <div class="lunara-control-desk-brand-number-grid">
                    <?php foreach ( lunara_control_desk_brand_number_specs() as $key => $spec ) : ?>
                        <?php lunara_control_desk_render_brand_number_control( $key, $spec ); ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="lunara-control-desk-brand-footer">
                <div>
                    <strong><?php esc_html_e( 'Preview after saving', 'lunara-film' ); ?></strong>
                    <span><?php esc_html_e( 'Desktop and 390px links stay close so logo changes can be judged immediately.', 'lunara-film' ); ?></span>
                </div>
                <div class="lunara-control-desk-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Brand Controls', 'lunara-film' ); ?></button>
                    <a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Homepage Desktop', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( add_query_arg( 'lunara-width', '390', home_url( '/' ) ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Homepage 390px', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Header Preview', 'lunara-film' ); ?></a>
                </div>
            </div>
        </form>
    </section>
    <?php
}

function lunara_control_desk_render_homepage_select_control( $key, $spec ) {
    $value     = lunara_control_desk_homepage_select_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <fieldset class="lunara-control-desk-homepage-choice">
        <legend>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </legend>
        <div class="lunara-control-desk-homepage-choice-options">
            <?php foreach ( $spec['options'] as $option_key => $option ) : ?>
                <label class="<?php echo $value === $option_key ? 'is-selected' : ''; ?>">
                    <input
                        type="radio"
                        name="lunara_homepage_select[<?php echo esc_attr( $key ); ?>]"
                        value="<?php echo esc_attr( $option_key ); ?>"
                        <?php checked( $value, $option_key ); ?>
                    />
                    <span>
                        <strong><?php echo esc_html( $option['label'] ); ?></strong>
                        <small><?php echo esc_html( $option['copy'] ); ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <?php
}

function lunara_control_desk_render_homepage_preset_comparison_item( $preset_key, $preset, $active_preset_key ) {
    $values    = isset( $preset['values'] ) && is_array( $preset['values'] ) ? $preset['values'] : array();
    $is_active = $preset_key === $active_preset_key;
    ?>
    <article class="lunara-control-desk-homepage-comparison-item <?php echo $is_active ? 'is-active' : ''; ?>">
        <header>
            <strong><?php echo esc_html( isset( $preset['label'] ) ? $preset['label'] : $preset_key ); ?></strong>
            <span><?php echo esc_html( $is_active ? __( 'active package', 'lunara-film' ) : __( 'available preset', 'lunara-film' ) ); ?></span>
        </header>
        <?php if ( ! empty( $preset['copy'] ) ) : ?>
            <p><?php echo esc_html( $preset['copy'] ); ?></p>
        <?php endif; ?>
        <dl>
            <?php foreach ( lunara_control_desk_homepage_comparison_specs() as $key => $spec ) : ?>
                <div>
                    <dt><?php echo esc_html( $spec['label'] ); ?></dt>
                    <dd><?php echo esc_html( lunara_control_desk_homepage_value_label( $key, isset( $values[ $key ] ) ? $values[ $key ] : '' ) ); ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
        <button
            type="submit"
            class="button button-secondary lunara-control-desk-homepage-apply-button"
            name="lunara_homepage_apply_preset"
            value="<?php echo esc_attr( $preset_key ); ?>"
            <?php disabled( $is_active ); ?>
        >
            <?php echo esc_html( $is_active ? __( 'Current Package', 'lunara-film' ) : __( 'Apply Package', 'lunara-film' ) ); ?>
        </button>
    </article>
    <?php
}

function lunara_control_desk_render_homepage_preset_comparison_strip( $presets, $active_preset_key ) {
    $presets = is_array( $presets ) ? $presets : lunara_control_desk_homepage_preset_specs();
    ?>
    <div class="lunara-control-desk-homepage-comparison-strip" aria-label="<?php esc_attr_e( 'Homepage preset comparison', 'lunara-film' ); ?>">
        <div class="lunara-control-desk-homepage-comparison-head">
            <strong><?php esc_html_e( 'Compare the homepage packages', 'lunara-film' ); ?></strong>
            <span><?php echo esc_html( $active_preset_key ? __( 'Saved controls match one of these publication packages.', 'lunara-film' ) : __( 'Current values are custom; use this strip to decide what the front door is trying to be.', 'lunara-film' ) ); ?></span>
        </div>
        <div class="lunara-control-desk-homepage-comparison-track">
            <?php foreach ( $presets as $preset_key => $preset ) : ?>
                <?php lunara_control_desk_render_homepage_preset_comparison_item( $preset_key, $preset, $active_preset_key ); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function lunara_control_desk_render_homepage_order_preset_control( $current, $specs ) {
    ?>
    <fieldset class="lunara-control-desk-homepage-order-choice">
        <legend>
            <strong><?php esc_html_e( 'Section-order preset', 'lunara-film' ); ?></strong>
            <small><?php esc_html_e( 'Choose the front-door flow without touching raw homepage slugs.', 'lunara-film' ); ?></small>
        </legend>
        <div class="lunara-control-desk-homepage-order-presets" role="radiogroup" aria-label="<?php esc_attr_e( 'Homepage section-order preset', 'lunara-film' ); ?>">
            <?php foreach ( $specs as $preset => $spec ) : ?>
                <?php $selected = $current === $preset; ?>
                <label class="lunara-control-desk-homepage-order-card <?php echo $selected ? 'is-selected' : ''; ?>">
                    <input
                        type="radio"
                        name="lunara_homepage_order_preset"
                        value="<?php echo esc_attr( $preset ); ?>"
                        <?php checked( $selected ); ?>
                    />
                    <span>
                        <strong><?php echo esc_html( $spec['label'] ); ?></strong>
                        <small><?php echo esc_html( $spec['copy'] ); ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <?php
}

function lunara_control_desk_render_homepage_number_control( $key, $spec ) {
    $value     = lunara_control_desk_homepage_number_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <label class="lunara-control-desk-homepage-number" data-lunara-brand-number-control>
        <span>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
        </span>
        <input
            type="range"
            min="<?php echo esc_attr( $spec['min'] ); ?>"
            max="<?php echo esc_attr( $spec['max'] ); ?>"
            step="<?php echo esc_attr( $spec['step'] ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            data-lunara-brand-range
        />
        <span class="lunara-control-desk-brand-number-value">
            <input
                type="number"
                name="lunara_homepage_number[<?php echo esc_attr( $key ); ?>]"
                min="<?php echo esc_attr( $spec['min'] ); ?>"
                max="<?php echo esc_attr( $spec['max'] ); ?>"
                step="<?php echo esc_attr( $spec['step'] ); ?>"
                value="<?php echo esc_attr( $value ); ?>"
                data-lunara-brand-number
            />
            <em><?php echo esc_html( $spec['unit'] ); ?></em>
        </span>
        <span class="lunara-control-desk-brand-reset">
            <label>
                <input type="checkbox" name="lunara_homepage_reset[<?php echo esc_attr( $key ); ?>]" value="1" />
                <?php
                printf(
                    /* translators: %d: setting default value. */
                    esc_html__( 'Reset to %d', 'lunara-film' ),
                    absint( $spec['default'] )
                );
                ?>
            </label>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </span>
    </label>
    <?php
}

function lunara_control_desk_homepage_oscar_pick_default_order_ids() {
    if ( function_exists( 'lunara_get_oscar_picks' ) ) {
        $query = lunara_get_oscar_picks( array( 'posts_per_page' => 16 ) );
        $ids   = wp_list_pluck( $query->posts, 'ID' );
        wp_reset_postdata();

        return array_map( 'absint', $ids );
    }

    return wp_list_pluck(
        get_posts(
            array(
                'post_type'           => 'lunara_oscar_pick',
                'post_status'         => 'publish',
                'posts_per_page'      => 16,
                'ignore_sticky_posts' => true,
                'no_found_rows'       => true,
            )
        ),
        'ID'
    );
}

function lunara_control_desk_render_homepage_oscar_pick_order_item( $post, $index = 0 ) {
    if ( ! ( $post instanceof WP_Post ) ) {
        return;
    }

    $post_id = absint( $post->ID );
    $film    = (string) get_post_meta( $post_id, '_lunara_pick_film', true );
    $year    = (int) get_post_meta( $post_id, '_lunara_pick_ceremony_year', true );
    $status  = (string) get_post_meta( $post_id, '_lunara_pick_status', true );
    $terms   = get_the_terms( $post_id, 'oscar_pick_category' );
    $term    = ! empty( $terms ) && ! is_wp_error( $terms ) ? $terms[0]->name : __( 'Oscar Pick', 'lunara-film' );
    ?>
    <article class="lunara-control-desk-carousel-item lunara-control-desk-oscar-pick-order-item" data-lunara-carousel-item data-lunara-carousel-id="<?php echo esc_attr( $post_id ); ?>">
        <div class="lunara-control-desk-carousel-thumb">
            <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                <?php
                echo get_the_post_thumbnail(
                    $post_id,
                    'thumbnail',
                    array(
                        'class'   => 'lunara-control-desk-carousel-image',
                        'loading' => 0 === (int) $index ? 'eager' : 'lazy',
                    )
                );
                ?>
            <?php else : ?>
                <span class="lunara-control-desk-oscar-pick-thumb-empty"><?php esc_html_e( 'No image', 'lunara-film' ); ?></span>
            <?php endif; ?>
        </div>
        <div class="lunara-control-desk-carousel-copy">
            <div class="lunara-control-desk-carousel-title-row">
                <div>
                    <strong><?php echo esc_html( get_the_title( $post ) ? get_the_title( $post ) : __( '(Untitled Oscar Pick)', 'lunara-film' ) ); ?></strong>
                    <span>
                        <?php
                        echo esc_html(
                            implode(
                                ' / ',
                                array_filter(
                                    array(
                                        '#' . $post_id,
                                        $term,
                                        $film,
                                        $year ? (string) $year : '',
                                        $status ? ucfirst( $status ) : __( 'Predicted', 'lunara-film' ),
                                    )
                                )
                            )
                        );
                        ?>
                    </span>
                </div>
                <div class="lunara-control-desk-carousel-controls">
                    <button type="button" class="button button-small" data-lunara-carousel-move="up"><?php esc_html_e( 'Up', 'lunara-film' ); ?></button>
                    <button type="button" class="button button-small" data-lunara-carousel-move="down"><?php esc_html_e( 'Down', 'lunara-film' ); ?></button>
                    <button type="button" class="button button-small" data-lunara-carousel-remove><?php esc_html_e( 'Hold', 'lunara-film' ); ?></button>
                </div>
            </div>
            <div class="lunara-control-desk-actions">
                <a class="button button-small" href="<?php echo esc_url( get_edit_post_link( $post_id, 'raw' ) ); ?>"><?php esc_html_e( 'Edit Pick', 'lunara-film' ); ?></a>
                <a class="button button-small" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Public Pick', 'lunara-film' ); ?></a>
            </div>
        </div>
    </article>
    <?php
}

function lunara_control_desk_render_homepage_oscar_pick_candidate( $post ) {
    if ( ! ( $post instanceof WP_Post ) ) {
        return;
    }

    $post_id = absint( $post->ID );
    $year    = (int) get_post_meta( $post_id, '_lunara_pick_ceremony_year', true );
    ?>
    <label class="lunara-control-desk-oscar-pick-candidate">
        <input type="checkbox" name="lunara_home_oscar_picks_add[]" value="<?php echo esc_attr( $post_id ); ?>" />
        <span>
            <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                <?php echo get_the_post_thumbnail( $post_id, 'thumbnail', array( 'loading' => 'lazy' ) ); ?>
            <?php endif; ?>
            <strong><?php echo esc_html( get_the_title( $post ) ? get_the_title( $post ) : __( '(Untitled Oscar Pick)', 'lunara-film' ) ); ?></strong>
            <small><?php echo esc_html( trim( sprintf( __( '#%1$d %2$s', 'lunara-film' ), $post_id, $year ? '/ ' . $year : '' ) ) ); ?></small>
        </span>
    </label>
    <?php
}

function lunara_control_desk_render_homepage_oscar_picks_curation() {
    $stored_ids      = lunara_control_desk_get_homepage_oscar_pick_order_ids();
    $order_is_custom = ! empty( $stored_ids );
    $ordered_ids     = $order_is_custom ? $stored_ids : lunara_control_desk_homepage_oscar_pick_default_order_ids();
    $ordered_posts   = lunara_control_desk_get_homepage_oscar_pick_posts_by_ids( $ordered_ids );
    $candidate_posts = lunara_control_desk_get_homepage_oscar_pick_candidates( $ordered_ids, 24 );
    ?>
    <div class="lunara-control-desk-homepage-card lunara-control-desk-oscar-picks-curation" data-lunara-oscar-picks-curation>
        <div class="lunara-control-desk-card-head">
            <div>
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Oscar Picks Curation', 'lunara-film' ); ?></p>
                <h3><?php esc_html_e( 'Order the homepage awards rail', 'lunara-film' ); ?></h3>
                <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Move cards up or down, hold weaker cards out of the public rail, and add recent published picks back on save. This only stores a homepage order list.', 'lunara-film' ); ?></p>
            </div>
            <div class="lunara-control-desk-status-pill">
                <strong><?php esc_html_e( 'Order source', 'lunara-film' ); ?></strong>
                <span><?php echo esc_html( $order_is_custom ? __( 'manual homepage order', 'lunara-film' ) : __( 'smart default fallback', 'lunara-film' ) ); ?></span>
            </div>
        </div>

        <div class="lunara-control-desk-carousel-toolbar">
            <input type="hidden" name="lunara_home_oscar_picks_order_source" value="<?php echo esc_attr( $order_is_custom ? 'manual' : 'fallback' ); ?>" />
            <label for="lunara-home-oscar-picks-manual-order">
                <span><?php esc_html_e( 'Active ordered pick IDs', 'lunara-film' ); ?></span>
                <textarea id="lunara-home-oscar-picks-manual-order" name="lunara_home_oscar_picks_manual_order" rows="2" data-lunara-carousel-ids><?php echo esc_textarea( implode( ',', $ordered_ids ) ); ?></textarea>
            </label>
            <label class="lunara-control-desk-oscar-pick-reset">
                <input type="checkbox" name="lunara_home_oscar_picks_reset_order" value="1" />
                <span><?php esc_html_e( 'Reset to smart default order', 'lunara-film' ); ?></span>
            </label>
        </div>

        <div class="lunara-control-desk-carousel-list" data-lunara-carousel-list>
            <?php if ( empty( $ordered_posts ) ) : ?>
                <div class="lunara-control-desk-empty" data-lunara-carousel-empty>
                    <p><?php esc_html_e( 'No published Oscar Picks are available for homepage curation yet.', 'lunara-film' ); ?></p>
                </div>
            <?php else : ?>
                <?php foreach ( $ordered_posts as $index => $post ) : ?>
                    <?php lunara_control_desk_render_homepage_oscar_pick_order_item( $post, $index ); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ( ! empty( $candidate_posts ) ) : ?>
            <div class="lunara-control-desk-oscar-picks-candidate-panel">
                <strong><?php esc_html_e( 'Add recent held picks on save', 'lunara-film' ); ?></strong>
                <div class="lunara-control-desk-oscar-picks-candidates">
                    <?php foreach ( $candidate_posts as $post ) : ?>
                        <?php lunara_control_desk_render_homepage_oscar_pick_candidate( $post ); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

function lunara_control_desk_render_homepage_studio() {
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        ?>
        <section id="lunara-theme-studio-homepage-studio" class="lunara-control-desk-homepage-studio">
            <div class="lunara-control-desk-panel-header">
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Homepage Studio', 'lunara-film' ); ?></p>
                <h3><?php esc_html_e( 'Homepage controls require theme editing permission', 'lunara-film' ); ?></h3>
                <p class="lunara-control-desk-subtle"><?php esc_html_e( 'The map below remains available, but direct homepage rhythm changes are limited to administrators.', 'lunara-film' ); ?></p>
            </div>
        </section>
        <?php
        return;
    }

    $order_specs   = lunara_control_desk_homepage_order_preset_specs();
    $order_preset  = lunara_control_desk_homepage_order_preset_value();
    $order_current = $order_specs[ $order_preset ];
    $desktop_order = $order_current['desktop'];
    $mobile_order  = $order_current['mobile'];
    $presets       = lunara_control_desk_homepage_preset_specs();
    $active_preset_key = lunara_control_desk_homepage_active_preset_key();
    $active_label  = $active_preset_key && isset( $presets[ $active_preset_key ] )
        ? $presets[ $active_preset_key ]['label']
        : __( 'Custom front door', 'lunara-film' );
    ?>
    <section id="lunara-theme-studio-homepage-studio" class="lunara-control-desk-homepage-studio">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Homepage Studio', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Front-door rhythm and signature lane controls', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Use these bounded controls to keep the homepage curated, dense, and alive without touching code or raw CSS.', 'lunara-film' ); ?></p>
        </div>
        <form class="lunara-control-desk-homepage-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-lunara-carousel-form>
            <input type="hidden" name="action" value="lunara_save_homepage_studio" />
            <input type="hidden" name="lunara_homepage_apply_preset" value="" />
            <?php wp_nonce_field( 'lunara_save_homepage_studio', 'lunara_homepage_nonce' ); ?>

            <div class="lunara-control-desk-homepage-card">
                <div class="lunara-control-desk-card-head">
                    <div>
                        <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Publication Packages', 'lunara-film' ); ?></p>
                        <h3><?php esc_html_e( 'Read the homepage as a front-door system', 'lunara-film' ); ?></h3>
                        <p class="lunara-control-desk-subtle"><?php esc_html_e( 'These are comparison packages built from the existing controls below. They do not save anything by themselves in this first pass.', 'lunara-film' ); ?></p>
                    </div>
                    <div class="lunara-control-desk-status-pill">
                        <strong><?php esc_html_e( 'Current package', 'lunara-film' ); ?></strong>
                        <span><?php echo esc_html( $active_label ); ?></span>
                    </div>
                </div>
                <?php lunara_control_desk_render_homepage_preset_comparison_strip( $presets, $active_preset_key ); ?>
            </div>

            <div class="lunara-control-desk-homepage-grid">
                <div class="lunara-control-desk-homepage-card">
                    <div class="lunara-control-desk-card-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Rhythm', 'lunara-film' ); ?></p>
                            <h3><?php esc_html_e( 'Editorial density and route emphasis', 'lunara-film' ); ?></h3>
                            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'These choices preserve the existing homepage architecture while tuning how confidently the front door reads.', 'lunara-film' ); ?></p>
                        </div>
                    </div>
                    <div class="lunara-control-desk-homepage-choice-grid">
                        <?php foreach ( lunara_control_desk_homepage_select_specs() as $key => $spec ) : ?>
                            <?php lunara_control_desk_render_homepage_select_control( $key, $spec ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="lunara-control-desk-homepage-card">
                    <div class="lunara-control-desk-card-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Spacing', 'lunara-film' ); ?></p>
                            <h3><?php esc_html_e( 'Masthead geometry and section handoff', 'lunara-film' ); ?></h3>
                            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Every number is clamped server-side so the homepage can be tightened without crop, overflow, or empty-space risk.', 'lunara-film' ); ?></p>
                        </div>
                    </div>
                    <div class="lunara-control-desk-homepage-number-grid">
                        <?php foreach ( lunara_control_desk_homepage_number_specs() as $key => $spec ) : ?>
                            <?php lunara_control_desk_render_homepage_number_control( $key, $spec ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php lunara_control_desk_render_homepage_oscar_picks_curation(); ?>

            <div class="lunara-control-desk-homepage-card">
                <div class="lunara-control-desk-card-head">
                    <div>
                        <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Shortcuts', 'lunara-film' ); ?></p>
                        <h3><?php esc_html_e( 'Section visibility', 'lunara-film' ); ?></h3>
                        <p class="lunara-control-desk-subtle"><?php esc_html_e( 'These mirror the existing homepage theme switches; they do not delete blocks or change URLs.', 'lunara-film' ); ?></p>
                    </div>
                </div>
                <div class="lunara-control-desk-homepage-visibility-grid">
                    <?php foreach ( lunara_control_desk_homepage_visibility_specs() as $key => $spec ) : ?>
                        <?php $enabled = (bool) get_theme_mod( $key, $spec['default'] ); ?>
                        <label class="<?php echo $enabled ? 'is-enabled' : 'is-disabled'; ?>">
                            <input type="hidden" name="lunara_homepage_visibility_keys[]" value="<?php echo esc_attr( $key ); ?>" />
                            <input type="checkbox" name="lunara_homepage_visibility[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $enabled ); ?> />
                            <span>
                                <strong><?php echo esc_html( $spec['label'] ); ?></strong>
                                <small><?php echo esc_html( $spec['copy'] ); ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="lunara-control-desk-homepage-card">
                <div class="lunara-control-desk-card-head">
                    <div>
                        <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Order Preview', 'lunara-film' ); ?></p>
                        <h3><?php esc_html_e( 'Responsive section rhythm', 'lunara-film' ); ?></h3>
                        <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Presets now write separate desktop and 390px mobile section orders, so the front door can stay Reviews-led on desktop while mobile can move with the live desk.', 'lunara-film' ); ?></p>
                    </div>
                </div>
                <?php lunara_control_desk_render_homepage_order_preset_control( $order_preset, $order_specs ); ?>
                <div class="lunara-control-desk-homepage-order-grid">
                    <article>
                        <strong><?php esc_html_e( 'Desktop', 'lunara-film' ); ?></strong>
                        <ol>
                            <?php foreach ( $desktop_order as $item ) : ?>
                                <li><?php echo esc_html( $item ); ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </article>
                    <article>
                        <strong><?php esc_html_e( '390px Mobile', 'lunara-film' ); ?></strong>
                        <ol>
                            <?php foreach ( $mobile_order as $item ) : ?>
                                <li><?php echo esc_html( $item ); ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </article>
                </div>
            </div>

            <div class="lunara-control-desk-homepage-footer">
                <div>
                    <strong><?php esc_html_e( 'Preview after saving', 'lunara-film' ); ?></strong>
                    <span><?php esc_html_e( 'The homepage should feel like one deliberate front door before the reader reaches the live lanes.', 'lunara-film' ); ?></span>
                </div>
                <div class="lunara-control-desk-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Homepage Studio', 'lunara-film' ); ?></button>
                    <a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Homepage Desktop', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( add_query_arg( 'lunara-width', '390', home_url( '/' ) ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Homepage 390px', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( home_url( '/oscars/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Oscar Portal', 'lunara-film' ); ?></a>
                </div>
            </div>
        </form>
    </section>
    <?php
}

function lunara_control_desk_render_reviews_archive_select_control( $key, $spec ) {
    $value     = lunara_control_desk_reviews_archive_select_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <fieldset class="lunara-control-desk-homepage-choice">
        <legend>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </legend>
        <div class="lunara-control-desk-homepage-choice-options">
            <?php foreach ( $spec['options'] as $option_key => $option ) : ?>
                <label class="<?php echo $value === $option_key ? 'is-selected' : ''; ?>">
                    <input
                        type="radio"
                        name="lunara_reviews_archive_select[<?php echo esc_attr( $key ); ?>]"
                        value="<?php echo esc_attr( $option_key ); ?>"
                        <?php checked( $value, $option_key ); ?>
                    />
                    <span>
                        <strong><?php echo esc_html( $option['label'] ); ?></strong>
                        <small><?php echo esc_html( $option['copy'] ); ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <?php
}

function lunara_control_desk_render_reviews_archive_number_control( $key, $spec ) {
    $value     = lunara_control_desk_reviews_archive_number_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <label class="lunara-control-desk-homepage-number" data-lunara-brand-number-control>
        <span>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
        </span>
        <input
            type="range"
            min="<?php echo esc_attr( $spec['min'] ); ?>"
            max="<?php echo esc_attr( $spec['max'] ); ?>"
            step="<?php echo esc_attr( $spec['step'] ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            data-lunara-brand-range
        />
        <span class="lunara-control-desk-brand-number-value">
            <input
                type="number"
                name="lunara_reviews_archive_number[<?php echo esc_attr( $key ); ?>]"
                min="<?php echo esc_attr( $spec['min'] ); ?>"
                max="<?php echo esc_attr( $spec['max'] ); ?>"
                step="<?php echo esc_attr( $spec['step'] ); ?>"
                value="<?php echo esc_attr( $value ); ?>"
                data-lunara-brand-number
            />
            <em><?php echo esc_html( $spec['unit'] ); ?></em>
        </span>
        <span class="lunara-control-desk-brand-reset">
            <label>
                <input type="checkbox" name="lunara_reviews_archive_reset[<?php echo esc_attr( $key ); ?>]" value="1" />
                <?php
                printf(
                    /* translators: %d: setting default value. */
                    esc_html__( 'Reset to %d', 'lunara-film' ),
                    absint( $spec['default'] )
                );
                ?>
            </label>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </span>
    </label>
    <?php
}

function lunara_control_desk_render_reviews_archive_studio() {
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        ?>
        <section id="lunara-theme-studio-reviews-archive-studio" class="lunara-control-desk-homepage-studio">
            <div class="lunara-control-desk-panel-header">
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Reviews Archive Studio', 'lunara-film' ); ?></p>
                <h3><?php esc_html_e( 'Reviews archive controls require theme editing permission', 'lunara-film' ); ?></h3>
                <p class="lunara-control-desk-subtle"><?php esc_html_e( 'The public archive remains visible, but direct Reviews rhythm changes are limited to administrators.', 'lunara-film' ); ?></p>
            </div>
        </section>
        <?php
        return;
    }
    ?>
    <section id="lunara-theme-studio-reviews-archive-studio" class="lunara-control-desk-homepage-studio">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Reviews Archive Studio', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Reviews desk density and companion rail controls', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Tune the Reviews archive like a route-family product: lead authority, companion movement, and archive card rhythm, all without raw CSS.', 'lunara-film' ); ?></p>
        </div>
        <form class="lunara-control-desk-homepage-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="lunara_save_reviews_archive_studio" />
            <?php wp_nonce_field( 'lunara_save_reviews_archive_studio', 'lunara_reviews_archive_nonce' ); ?>

            <div class="lunara-control-desk-homepage-grid">
                <div class="lunara-control-desk-homepage-card">
                    <div class="lunara-control-desk-card-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Editorial Rhythm', 'lunara-film' ); ?></p>
                            <h3><?php esc_html_e( 'Archive, lead, and rail emphasis', 'lunara-film' ); ?></h3>
                            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'These settings preserve the current archive shell while changing how forcefully each chamber reads.', 'lunara-film' ); ?></p>
                        </div>
                    </div>
                    <div class="lunara-control-desk-homepage-choice-grid">
                        <?php foreach ( lunara_control_desk_reviews_archive_select_specs() as $key => $spec ) : ?>
                            <?php lunara_control_desk_render_reviews_archive_select_control( $key, $spec ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="lunara-control-desk-homepage-card">
                    <div class="lunara-control-desk-card-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Geometry', 'lunara-film' ); ?></p>
                            <h3><?php esc_html_e( 'Spacing and card chambers', 'lunara-film' ); ?></h3>
                            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Every number is clamped server-side so the Reviews page can get tighter without overflow, crop, or empty-space drift.', 'lunara-film' ); ?></p>
                        </div>
                    </div>
                    <div class="lunara-control-desk-homepage-number-grid">
                        <?php foreach ( lunara_control_desk_reviews_archive_number_specs() as $key => $spec ) : ?>
                            <?php lunara_control_desk_render_reviews_archive_number_control( $key, $spec ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="lunara-control-desk-homepage-footer">
                <div>
                    <strong><?php esc_html_e( 'Preview after saving', 'lunara-film' ); ?></strong>
                    <span><?php esc_html_e( 'Use the archive links immediately after saving to judge the lead chamber, rail movement, and card density.', 'lunara-film' ); ?></span>
                </div>
                <div class="lunara-control-desk-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Reviews Archive Studio', 'lunara-film' ); ?></button>
                    <a class="button" href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Reviews Desktop', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( add_query_arg( 'lunara-width', '390', home_url( '/reviews/' ) ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Reviews 390px', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( home_url( '/reviews/sinners-2025/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Review Single', 'lunara-film' ); ?></a>
                </div>
            </div>
        </form>
    </section>
    <?php
}

function lunara_control_desk_render_review_card_image_focus_control( $key, $spec ) {
    $value     = lunara_control_desk_review_card_image_focus_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <fieldset class="lunara-control-desk-homepage-choice">
        <legend>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </legend>
        <div class="lunara-control-desk-homepage-choice-options">
            <?php foreach ( $spec['options'] as $option_key => $option ) : ?>
                <label class="<?php echo $value === $option_key ? 'is-selected' : ''; ?>">
                    <input
                        type="radio"
                        name="lunara_review_card_image_focus_select[<?php echo esc_attr( $key ); ?>]"
                        value="<?php echo esc_attr( $option_key ); ?>"
                        <?php checked( $value, $option_key ); ?>
                    />
                    <span>
                        <strong><?php echo esc_html( $option['label'] ); ?></strong>
                        <small><?php echo esc_html( $option['copy'] ); ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <?php
}

function lunara_control_desk_render_review_card_image_focus_controls() {
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        ?>
        <section id="lunara-theme-studio-review-card-image-focus" class="lunara-control-desk-homepage-studio">
            <div class="lunara-control-desk-panel-header">
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Review Card Image Focus', 'lunara-film' ); ?></p>
                <h3><?php esc_html_e( 'Review card focus controls require theme editing permission', 'lunara-film' ); ?></h3>
                <p class="lunara-control-desk-subtle"><?php esc_html_e( 'The public review cards remain visible, but crop-focus controls are limited to administrators.', 'lunara-film' ); ?></p>
            </div>
        </section>
        <?php
        return;
    }
    ?>
    <section id="lunara-theme-studio-review-card-image-focus" class="lunara-control-desk-homepage-studio">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Review Card Image Focus', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Crop focus for review cards and feature chambers', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Tune how existing Review art sits inside public card chambers. These controls never change source images, Review fields, trailers, spoilers, Debrief, or Pair It With data.', 'lunara-film' ); ?></p>
        </div>
        <form class="lunara-control-desk-homepage-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="lunara_save_review_card_image_focus_controls" />
            <?php wp_nonce_field( 'lunara_save_review_card_image_focus_controls', 'lunara_review_card_image_focus_nonce' ); ?>

            <div class="lunara-control-desk-homepage-card">
                <div class="lunara-control-desk-card-head">
                    <div>
                        <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Image Authority', 'lunara-film' ); ?></p>
                        <h3><?php esc_html_e( 'Keep the art sharp without stretching it', 'lunara-film' ); ?></h3>
                        <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Use these after selecting high-quality art: archive and homepage cards stay 3:4, related cards stay in their card chamber, and feature images keep their existing source priority.', 'lunara-film' ); ?></p>
                    </div>
                </div>
                <div class="lunara-control-desk-homepage-choice-grid">
                    <?php foreach ( lunara_control_desk_review_card_image_focus_specs() as $key => $spec ) : ?>
                        <?php lunara_control_desk_render_review_card_image_focus_control( $key, $spec ); ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="lunara-control-desk-homepage-footer">
                <div>
                    <strong><?php esc_html_e( 'Preview after saving', 'lunara-film' ); ?></strong>
                    <span><?php esc_html_e( 'Check archive cards, moving rail cards, homepage review cards, and related cards before calling the image pass clean.', 'lunara-film' ); ?></span>
                </div>
                <div class="lunara-control-desk-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Review Card Image Focus', 'lunara-film' ); ?></button>
                    <a class="button" href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Reviews Desktop', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( add_query_arg( 'lunara-width', '390', home_url( '/reviews/' ) ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Reviews 390px', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( home_url( '/reviews/sinners-2025/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Review Single', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( home_url( '/reviews/bugonia-the-full-spoiler/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Spoiler Review', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Homepage', 'lunara-film' ); ?></a>
                </div>
            </div>
        </form>
    </section>
    <?php
}

function lunara_control_desk_render_review_single_select_control( $key, $spec ) {
    $value     = lunara_control_desk_review_single_select_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <fieldset class="lunara-control-desk-homepage-choice">
        <legend>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </legend>
        <div class="lunara-control-desk-homepage-choice-options">
            <?php foreach ( $spec['options'] as $option_key => $option ) : ?>
                <label class="<?php echo $value === $option_key ? 'is-selected' : ''; ?>">
                    <input
                        type="radio"
                        name="lunara_review_single_select[<?php echo esc_attr( $key ); ?>]"
                        value="<?php echo esc_attr( $option_key ); ?>"
                        <?php checked( $value, $option_key ); ?>
                    />
                    <span>
                        <strong><?php echo esc_html( $option['label'] ); ?></strong>
                        <small><?php echo esc_html( $option['copy'] ); ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <?php
}

function lunara_control_desk_render_review_single_number_control( $key, $spec ) {
    $value     = lunara_control_desk_review_single_number_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <label class="lunara-control-desk-homepage-number" data-lunara-brand-number-control>
        <span>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
        </span>
        <input
            type="range"
            min="<?php echo esc_attr( $spec['min'] ); ?>"
            max="<?php echo esc_attr( $spec['max'] ); ?>"
            step="<?php echo esc_attr( $spec['step'] ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            data-lunara-brand-range
        />
        <span class="lunara-control-desk-brand-number-value">
            <input
                type="number"
                name="lunara_review_single_number[<?php echo esc_attr( $key ); ?>]"
                min="<?php echo esc_attr( $spec['min'] ); ?>"
                max="<?php echo esc_attr( $spec['max'] ); ?>"
                step="<?php echo esc_attr( $spec['step'] ); ?>"
                value="<?php echo esc_attr( $value ); ?>"
                data-lunara-brand-number
            />
            <em><?php echo esc_html( $spec['unit'] ); ?></em>
        </span>
        <span class="lunara-control-desk-brand-reset">
            <label>
                <input type="checkbox" name="lunara_review_single_reset[<?php echo esc_attr( $key ); ?>]" value="1" />
                <?php
                printf(
                    /* translators: %d: setting default value. */
                    esc_html__( 'Reset to %d', 'lunara-film' ),
                    absint( $spec['default'] )
                );
                ?>
            </label>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </span>
    </label>
    <?php
}

function lunara_control_desk_render_review_pair_with_select_control( $key, $spec ) {
    $value     = lunara_control_desk_review_pair_with_select_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <fieldset class="lunara-control-desk-homepage-choice">
        <legend>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </legend>
        <div class="lunara-control-desk-homepage-choice-options">
            <?php foreach ( $spec['options'] as $option_key => $option ) : ?>
                <label class="<?php echo $value === $option_key ? 'is-selected' : ''; ?>">
                    <input
                        type="radio"
                        name="lunara_review_pair_with_select[<?php echo esc_attr( $key ); ?>]"
                        value="<?php echo esc_attr( $option_key ); ?>"
                        <?php checked( $value, $option_key ); ?>
                    />
                    <span>
                        <strong><?php echo esc_html( $option['label'] ); ?></strong>
                        <small><?php echo esc_html( $option['copy'] ); ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <?php
}

function lunara_control_desk_render_review_pair_with_number_control( $key, $spec ) {
    $value     = lunara_control_desk_review_pair_with_number_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <label class="lunara-control-desk-homepage-number" data-lunara-brand-number-control>
        <span>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
        </span>
        <input
            type="range"
            min="<?php echo esc_attr( $spec['min'] ); ?>"
            max="<?php echo esc_attr( $spec['max'] ); ?>"
            step="<?php echo esc_attr( $spec['step'] ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            data-lunara-brand-range
        />
        <span class="lunara-control-desk-brand-number-value">
            <input
                type="number"
                name="lunara_review_pair_with_number[<?php echo esc_attr( $key ); ?>]"
                min="<?php echo esc_attr( $spec['min'] ); ?>"
                max="<?php echo esc_attr( $spec['max'] ); ?>"
                step="<?php echo esc_attr( $spec['step'] ); ?>"
                value="<?php echo esc_attr( $value ); ?>"
                data-lunara-brand-number
            />
            <em><?php echo esc_html( $spec['unit'] ); ?></em>
        </span>
        <span class="lunara-control-desk-brand-reset">
            <label>
                <input type="checkbox" name="lunara_review_pair_with_reset[<?php echo esc_attr( $key ); ?>]" value="1" />
                <?php
                printf(
                    /* translators: %d: setting default value. */
                    esc_html__( 'Reset to %d', 'lunara-film' ),
                    absint( $spec['default'] )
                );
                ?>
            </label>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </span>
    </label>
    <?php
}

function lunara_control_desk_render_review_pair_with_precision_controls() {
    ?>
    <div class="lunara-control-desk-homepage-card">
        <div class="lunara-control-desk-card-head">
            <div>
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Pair It With Precision', 'lunara-film' ); ?></p>
                <h3><?php esc_html_e( 'Width, poster balance, text depth, and mobile stack', 'lunara-film' ); ?></h3>
                <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Tune the retention module readers respond to without changing its titles, notes, links, Oscar matches, or Debrief source content.', 'lunara-film' ); ?></p>
            </div>
        </div>
        <div class="lunara-control-desk-homepage-choice-grid">
            <?php foreach ( lunara_control_desk_review_pair_with_select_specs() as $key => $spec ) : ?>
                <?php lunara_control_desk_render_review_pair_with_select_control( $key, $spec ); ?>
            <?php endforeach; ?>
        </div>
        <div class="lunara-control-desk-homepage-number-grid">
            <?php foreach ( lunara_control_desk_review_pair_with_number_specs() as $key => $spec ) : ?>
                <?php lunara_control_desk_render_review_pair_with_number_control( $key, $spec ); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function lunara_control_desk_review_single_preset_preview_url( $path, $preset_key, $mobile = false ) {
    $url = add_query_arg( 'lunara-review-preset', $preset_key, home_url( $path ) );

    if ( $mobile ) {
        $url = add_query_arg( 'lunara-width', '390', $url );
    }

    return $url;
}

function lunara_control_desk_review_single_value_label( $key, $value ) {
    if ( null === $value || '' === (string) $value ) {
        return __( 'Default', 'lunara-film' );
    }

    $select_specs = lunara_control_desk_review_single_select_specs();
    if ( isset( $select_specs[ $key ]['options'][ $value ]['label'] ) ) {
        return $select_specs[ $key ]['options'][ $value ]['label'];
    }

    $pair_select_specs = lunara_control_desk_review_pair_with_select_specs();
    if ( isset( $pair_select_specs[ $key ]['options'][ $value ]['label'] ) ) {
        return $pair_select_specs[ $key ]['options'][ $value ]['label'];
    }

    $number_specs = lunara_control_desk_review_single_number_specs();
    if ( isset( $number_specs[ $key ] ) ) {
        $unit = isset( $number_specs[ $key ]['unit'] ) ? $number_specs[ $key ]['unit'] : '';
        return trim( absint( $value ) . ' ' . $unit );
    }

    $pair_number_specs = lunara_control_desk_review_pair_with_number_specs();
    if ( isset( $pair_number_specs[ $key ] ) ) {
        $unit = isset( $pair_number_specs[ $key ]['unit'] ) ? $pair_number_specs[ $key ]['unit'] : '';
        return trim( absint( $value ) . ' ' . $unit );
    }

    return (string) $value;
}

function lunara_control_desk_review_single_key_label( $key ) {
    $select_specs = lunara_control_desk_review_single_select_specs();
    if ( isset( $select_specs[ $key ]['label'] ) ) {
        return $select_specs[ $key ]['label'];
    }

    $pair_select_specs = lunara_control_desk_review_pair_with_select_specs();
    if ( isset( $pair_select_specs[ $key ]['label'] ) ) {
        return $pair_select_specs[ $key ]['label'];
    }

    $number_specs = lunara_control_desk_review_single_number_specs();
    if ( isset( $number_specs[ $key ]['label'] ) ) {
        return $number_specs[ $key ]['label'];
    }

    $pair_number_specs = lunara_control_desk_review_pair_with_number_specs();
    if ( isset( $pair_number_specs[ $key ]['label'] ) ) {
        return $pair_number_specs[ $key ]['label'];
    }

    $label = str_replace( array( 'lunara_review_single_', 'lunara_review_' ), '', $key );
    return ucwords( str_replace( '_', ' ', $label ) );
}

function lunara_control_desk_review_single_comparison_specs() {
    return array(
        'lunara_review_single_density'              => array(
            'label' => __( 'Package density', 'lunara-film' ),
        ),
        'lunara_review_single_hero_scale'           => array(
            'label' => __( 'Hero scale', 'lunara-film' ),
        ),
        'lunara_review_single_rail_mode'            => array(
            'label' => __( 'Rail mode', 'lunara-film' ),
        ),
        'lunara_review_single_debrief_prominence'   => array(
            'label' => __( 'Debrief prominence', 'lunara-film' ),
        ),
        'lunara_review_single_pairing_density'      => array(
            'label' => __( 'Pair It With density', 'lunara-film' ),
        ),
        'lunara_review_pair_with_layout'            => array(
            'label' => __( 'Pair It With layout', 'lunara-film' ),
        ),
        'lunara_review_pair_with_columns'           => array(
            'label' => __( 'Pairing columns', 'lunara-film' ),
        ),
        'lunara_review_pair_with_thumb_width'       => array(
            'label' => __( 'Pairing thumb width', 'lunara-film' ),
        ),
        'lunara_review_pair_with_text_depth'        => array(
            'label' => __( 'Pairing text depth', 'lunara-film' ),
        ),
        'lunara_review_pair_with_mobile_stack'      => array(
            'label' => __( 'Mobile pairing stack', 'lunara-film' ),
        ),
        'lunara_review_pair_with_image_focus'       => array(
            'label' => __( 'Pairing image focus', 'lunara-film' ),
        ),
        'lunara_review_single_spoiler_treatment'    => array(
            'label' => __( 'Spoiler treatment', 'lunara-film' ),
        ),
        'lunara_review_single_trailer_prominence'   => array(
            'label' => __( 'Trailer prominence', 'lunara-film' ),
        ),
        'lunara_review_single_section_gap'          => array(
            'label' => __( 'Section rhythm', 'lunara-film' ),
        ),
        'lunara_review_single_debrief_poster_width' => array(
            'label' => __( 'Debrief poster width', 'lunara-film' ),
        ),
        'lunara_review_related_count'               => array(
            'label' => __( 'Related review count', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_render_review_single_preset_comparison_item( $preset_key, $preset, $active_preset_key ) {
    $values    = isset( $preset['values'] ) && is_array( $preset['values'] ) ? $preset['values'] : array();
    $is_active = $preset_key === $active_preset_key;
    ?>
    <article class="lunara-control-desk-review-comparison-item <?php echo $is_active ? 'is-active' : ''; ?>">
        <header>
            <strong><?php echo esc_html( isset( $preset['label'] ) ? $preset['label'] : $preset_key ); ?></strong>
            <span><?php echo esc_html( $is_active ? __( 'active package', 'lunara-film' ) : __( 'available preset', 'lunara-film' ) ); ?></span>
        </header>
        <dl>
            <?php foreach ( lunara_control_desk_review_single_comparison_specs() as $key => $spec ) : ?>
                <div>
                    <dt><?php echo esc_html( $spec['label'] ); ?></dt>
                    <dd><?php echo esc_html( lunara_control_desk_review_single_value_label( $key, isset( $values[ $key ] ) ? $values[ $key ] : '' ) ); ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </article>
    <?php
}

function lunara_control_desk_render_review_single_preset_comparison_strip( $presets, $active_preset_key ) {
    $presets = is_array( $presets ) ? $presets : lunara_control_desk_review_single_preset_specs();
    ?>
    <div class="lunara-control-desk-review-comparison-strip" aria-label="<?php esc_attr_e( 'Review Single preset comparison', 'lunara-film' ); ?>">
        <div class="lunara-control-desk-review-comparison-head">
            <strong><?php esc_html_e( 'Compare the Review packages', 'lunara-film' ); ?></strong>
            <span><?php echo esc_html( $active_preset_key ? __( 'Saved controls match one of these presets.', 'lunara-film' ) : __( 'Current values are custom; compare before saving a preset.', 'lunara-film' ) ); ?></span>
        </div>
        <div class="lunara-control-desk-review-comparison-track">
            <?php foreach ( $presets as $preset_key => $preset ) : ?>
                <?php lunara_control_desk_render_review_single_preset_comparison_item( $preset_key, $preset, $active_preset_key ); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function lunara_control_desk_render_review_single_preset_card( $preset_key, $preset, $active_preset_key ) {
    $is_active = $preset_key === $active_preset_key;
    $values    = isset( $preset['values'] ) && is_array( $preset['values'] ) ? $preset['values'] : array();
    ?>
    <fieldset class="lunara-control-desk-homepage-choice <?php echo $is_active ? 'is-selected' : ''; ?>">
        <legend>
            <strong><?php echo esc_html( $preset['label'] ); ?></strong>
            <small><?php echo esc_html( $preset['copy'] ); ?></small>
            <em><?php echo esc_html( $is_active ? __( 'active', 'lunara-film' ) : __( 'preset', 'lunara-film' ) ); ?></em>
        </legend>
        <div class="lunara-control-desk-source-grid">
            <?php foreach ( $values as $key => $value ) : ?>
                <span class="lunara-control-desk-source-pill">
                    <strong><?php echo esc_html( lunara_control_desk_review_single_key_label( $key ) ); ?></strong>
                    <?php echo esc_html( lunara_control_desk_review_single_value_label( $key, $value ) ); ?>
                </span>
            <?php endforeach; ?>
        </div>
        <div class="lunara-control-desk-actions">
            <button type="submit" class="button" name="lunara_review_single_preset" value="<?php echo esc_attr( $preset_key ); ?>">
                <?php echo esc_html( $is_active ? __( 'Reapply preset', 'lunara-film' ) : __( 'Apply preset', 'lunara-film' ) ); ?>
            </button>
        <a class="button" href="<?php echo esc_url( lunara_control_desk_review_single_preset_preview_url( '/reviews/sinners-2025/', $preset_key ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Sinners preview', 'lunara-film' ); ?></a>
        <a class="button" href="<?php echo esc_url( lunara_control_desk_review_single_preset_preview_url( '/reviews/sinners-2025/', $preset_key, true ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '390px', 'lunara-film' ); ?></a>
            <a class="button" href="<?php echo esc_url( lunara_control_desk_review_single_preset_preview_url( '/reviews/bugonia-the-full-spoiler/', $preset_key ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Spoiler preview', 'lunara-film' ); ?></a>
        </div>
    </fieldset>
    <?php
}

function lunara_control_desk_render_review_single_studio() {
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        ?>
        <section id="lunara-theme-studio-review-single-studio" class="lunara-control-desk-homepage-studio">
            <div class="lunara-control-desk-panel-header">
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Review Single Studio', 'lunara-film' ); ?></p>
                <h3><?php esc_html_e( 'Single Review controls require theme editing permission', 'lunara-film' ); ?></h3>
                <p class="lunara-control-desk-subtle"><?php esc_html_e( 'The public review package remains visible, but direct single-review rhythm changes are limited to administrators.', 'lunara-film' ); ?></p>
            </div>
        </section>
        <?php
        return;
    }
    $presets           = lunara_control_desk_review_single_preset_specs();
    $active_preset_key = lunara_control_desk_review_single_active_preset_key();
    $active_label      = $active_preset_key && isset( $presets[ $active_preset_key ] )
        ? $presets[ $active_preset_key ]['label']
        : __( 'Custom package', 'lunara-film' );
    ?>
    <section id="lunara-theme-studio-review-single-studio" class="lunara-control-desk-homepage-studio">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Review Single Studio', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Single-review authority package controls', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Tune the review page as a premium criticism product: hero image, right rail, Debrief, Pair It With, spoilers, trailers, and related reading, all without raw CSS.', 'lunara-film' ); ?></p>
        </div>
        <form class="lunara-control-desk-homepage-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="lunara_save_review_single_studio" />
            <?php wp_nonce_field( 'lunara_save_review_single_studio', 'lunara_review_single_nonce' ); ?>

            <div class="lunara-control-desk-homepage-card">
                <div class="lunara-control-desk-card-head">
                    <div>
                        <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Package Presets', 'lunara-film' ); ?></p>
                        <h3><?php esc_html_e( 'Apply or preview a complete single-review rhythm', 'lunara-film' ); ?></h3>
                        <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Presets save the same bounded controls below. Preview links are request-only and only affect admins with theme editing permission.', 'lunara-film' ); ?></p>
                    </div>
                    <div class="lunara-control-desk-status-pill">
                        <strong><?php esc_html_e( 'Current package', 'lunara-film' ); ?></strong>
                        <span><?php echo esc_html( $active_label ); ?></span>
                    </div>
                </div>
                <?php lunara_control_desk_render_review_single_preset_comparison_strip( $presets, $active_preset_key ); ?>
                <div class="lunara-control-desk-homepage-choice-grid">
                    <?php foreach ( $presets as $preset_key => $preset ) : ?>
                        <?php lunara_control_desk_render_review_single_preset_card( $preset_key, $preset, $active_preset_key ); ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="lunara-control-desk-homepage-grid">
                <div class="lunara-control-desk-homepage-card">
                    <div class="lunara-control-desk-card-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Editorial Package', 'lunara-film' ); ?></p>
                            <h3><?php esc_html_e( 'Hero, rail, spoilers, trailer, and module emphasis', 'lunara-film' ); ?></h3>
                            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'These controls change how the existing review surfaces read. They do not change Review fields, spoiler state, trailers, Debrief, or Pair It With content.', 'lunara-film' ); ?></p>
                        </div>
                    </div>
                    <div class="lunara-control-desk-homepage-choice-grid">
                        <?php foreach ( lunara_control_desk_review_single_select_specs() as $key => $spec ) : ?>
                            <?php lunara_control_desk_render_review_single_select_control( $key, $spec ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="lunara-control-desk-homepage-card">
                    <div class="lunara-control-desk-card-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Geometry', 'lunara-film' ); ?></p>
                            <h3><?php esc_html_e( 'Spacing, poster chamber, and retention count', 'lunara-film' ); ?></h3>
                            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Every value is clamped server-side so single reviews can get more dynamic without sidebar overlap, empty chambers, or mobile overflow.', 'lunara-film' ); ?></p>
                        </div>
                    </div>
                    <div class="lunara-control-desk-homepage-number-grid">
                        <?php foreach ( lunara_control_desk_review_single_number_specs() as $key => $spec ) : ?>
                            <?php lunara_control_desk_render_review_single_number_control( $key, $spec ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php lunara_control_desk_render_review_pair_with_precision_controls(); ?>

            <div class="lunara-control-desk-homepage-footer">
                <div>
                    <strong><?php esc_html_e( 'Preview after saving', 'lunara-film' ); ?></strong>
                    <span><?php esc_html_e( 'Check a standard review, a full-spoiler review, and the archive entry point after each change.', 'lunara-film' ); ?></span>
                </div>
                <div class="lunara-control-desk-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Review Single Studio', 'lunara-film' ); ?></button>
                    <a class="button" href="<?php echo esc_url( home_url( '/reviews/sinners-2025/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Sinners Desktop', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( add_query_arg( 'lunara-width', '390', home_url( '/reviews/sinners-2025/' ) ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Sinners 390px', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( home_url( '/reviews/bugonia-the-full-spoiler/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Spoiler Review', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Reviews Archive', 'lunara-film' ); ?></a>
                </div>
            </div>
        </form>
    </section>
    <?php
}

function lunara_control_desk_render_utility_search_select_control( $key, $spec ) {
    $value     = lunara_control_desk_utility_search_select_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <fieldset class="lunara-control-desk-homepage-choice">
        <legend>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </legend>
        <div class="lunara-control-desk-homepage-choice-options">
            <?php foreach ( $spec['options'] as $option_key => $option ) : ?>
                <label class="<?php echo $value === $option_key ? 'is-selected' : ''; ?>">
                    <input
                        type="radio"
                        name="lunara_utility_search_select[<?php echo esc_attr( $key ); ?>]"
                        value="<?php echo esc_attr( $option_key ); ?>"
                        <?php checked( $value, $option_key ); ?>
                    />
                    <span>
                        <strong><?php echo esc_html( $option['label'] ); ?></strong>
                        <small><?php echo esc_html( $option['copy'] ); ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <?php
}

function lunara_control_desk_render_utility_search_focus_select_control( $key, $spec ) {
    $value     = lunara_control_desk_utility_search_focus_select_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <div class="lunara-control-desk-homepage-choice">
        <div class="lunara-control-desk-choice-meta">
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <span><?php echo esc_html( $spec['note'] ); ?></span>
        </div>
        <div class="lunara-control-desk-radio-grid">
            <?php foreach ( $spec['options'] as $option_key => $option ) : ?>
                <label class="lunara-control-desk-radio-card<?php echo $value === $option_key ? ' is-selected' : ''; ?>">
                    <input
                        type="radio"
                        name="lunara_utility_search_focus_select[<?php echo esc_attr( $key ); ?>]"
                        value="<?php echo esc_attr( $option_key ); ?>"
                        <?php checked( $value, $option_key ); ?>
                    />
                    <strong><?php echo esc_html( $option['label'] ); ?></strong>
                    <span><?php echo esc_html( $option['copy'] ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <p class="lunara-control-desk-subtle"><?php echo $is_custom ? esc_html__( 'Custom value active.', 'lunara-film' ) : esc_html__( 'Default value active.', 'lunara-film' ); ?></p>
    </div>
    <?php
}

function lunara_control_desk_render_utility_search_number_control( $key, $spec ) {
    $value     = lunara_control_desk_utility_search_number_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <label class="lunara-control-desk-homepage-number" data-lunara-brand-number-control>
        <span>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
        </span>
        <input
            type="range"
            min="<?php echo esc_attr( $spec['min'] ); ?>"
            max="<?php echo esc_attr( $spec['max'] ); ?>"
            step="<?php echo esc_attr( $spec['step'] ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            data-lunara-brand-range
        />
        <span class="lunara-control-desk-brand-number-value">
            <input
                type="number"
                name="lunara_utility_search_number[<?php echo esc_attr( $key ); ?>]"
                min="<?php echo esc_attr( $spec['min'] ); ?>"
                max="<?php echo esc_attr( $spec['max'] ); ?>"
                step="<?php echo esc_attr( $spec['step'] ); ?>"
                value="<?php echo esc_attr( $value ); ?>"
                data-lunara-brand-number
            />
            <em><?php echo esc_html( $spec['unit'] ); ?></em>
        </span>
        <span class="lunara-control-desk-brand-reset">
            <label>
                <input type="checkbox" name="lunara_utility_search_reset[<?php echo esc_attr( $key ); ?>]" value="1" />
                <?php
                printf(
                    /* translators: %d: setting default value. */
                    esc_html__( 'Reset to %d', 'lunara-film' ),
                    absint( $spec['default'] )
                );
                ?>
            </label>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </span>
    </label>
    <?php
}

function lunara_control_desk_utility_search_key_label( $key ) {
    if ( 'lunara_utility_search_preset' === $key ) {
        return __( 'Preset', 'lunara-film' );
    }

    foreach ( array( lunara_control_desk_utility_search_select_specs(), lunara_control_desk_utility_search_focus_select_specs(), lunara_control_desk_utility_search_number_specs() ) as $spec_group ) {
        if ( isset( $spec_group[ $key ]['label'] ) ) {
            return $spec_group[ $key ]['label'];
        }
    }

    return $key;
}

function lunara_control_desk_utility_search_comparison_specs() {
    return array(
        'lunara_utility_search_density'        => array(
            'label' => __( 'Density', 'lunara-film' ),
        ),
        'lunara_utility_result_treatment'      => array(
            'label' => __( 'Result treatment', 'lunara-film' ),
        ),
        'lunara_utility_result_media'          => array(
            'label' => __( 'Result media', 'lunara-film' ),
        ),
        'lunara_utility_search_lead_focus'     => array(
            'label' => __( 'Search lead focus', 'lunara-film' ),
        ),
        'lunara_utility_search_spotlight_type' => array(
            'label' => __( 'Spotlight type', 'lunara-film' ),
        ),
        'lunara_utility_reentry_primary'       => array(
            'label' => __( '404 primary path', 'lunara-film' ),
        ),
        'lunara_utility_section_gap'           => array(
            'label' => __( 'Section gap', 'lunara-film' ),
        ),
        'lunara_utility_result_min_height'     => array(
            'label' => __( 'Result minimum height', 'lunara-film' ),
        ),
        'lunara_utility_card_grid_min'         => array(
            'label' => __( 'Card grid minimum', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_utility_search_comparison_value_label( $key, $value ) {
    if ( null === $value || '' === (string) $value ) {
        return __( 'Default', 'lunara-film' );
    }

    foreach ( array( lunara_control_desk_utility_search_select_specs(), lunara_control_desk_utility_search_focus_select_specs() ) as $spec_group ) {
        if ( isset( $spec_group[ $key ]['options'][ $value ]['label'] ) ) {
            return $spec_group[ $key ]['options'][ $value ]['label'];
        }
    }

    $number_specs = lunara_control_desk_utility_search_number_specs();
    if ( isset( $number_specs[ $key ] ) ) {
        $unit = isset( $number_specs[ $key ]['unit'] ) ? (string) $number_specs[ $key ]['unit'] : '';
        return trim( absint( $value ) . $unit );
    }

    return (string) $value;
}

function lunara_control_desk_utility_search_preset_preview_url( $url, $preset_key, $mobile = false ) {
    $url = add_query_arg( 'lunara-utility-preset', sanitize_key( (string) $preset_key ), $url );

    if ( $mobile ) {
        $url = add_query_arg( 'lunara-width', '390', $url );
    }

    return $url;
}

function lunara_control_desk_render_utility_search_preset_comparison_item( $preset_key, $preset, $active_preset_key ) {
    $values    = isset( $preset['values'] ) && is_array( $preset['values'] ) ? $preset['values'] : array();
    $is_active = $preset_key === $active_preset_key;
    ?>
    <article class="lunara-control-desk-utility-comparison-item <?php echo $is_active ? 'is-active' : ''; ?>">
        <header>
            <strong><?php echo esc_html( isset( $preset['label'] ) ? $preset['label'] : $preset_key ); ?></strong>
            <span><?php echo esc_html( $is_active ? __( 'active package', 'lunara-film' ) : __( 'available preset', 'lunara-film' ) ); ?></span>
        </header>
        <dl>
            <?php foreach ( lunara_control_desk_utility_search_comparison_specs() as $key => $spec ) : ?>
                <div>
                    <dt><?php echo esc_html( $spec['label'] ); ?></dt>
                    <dd><?php echo esc_html( lunara_control_desk_utility_search_comparison_value_label( $key, isset( $values[ $key ] ) ? $values[ $key ] : '' ) ); ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </article>
    <?php
}

function lunara_control_desk_render_utility_search_preset_comparison_strip( $presets, $active_preset_key ) {
    $presets = is_array( $presets ) ? $presets : lunara_control_desk_utility_search_preset_specs();
    ?>
    <div class="lunara-control-desk-utility-comparison-strip" aria-label="<?php esc_attr_e( 'Utility Search preset comparison', 'lunara-film' ); ?>">
        <div class="lunara-control-desk-utility-comparison-head">
            <strong><?php esc_html_e( 'Compare the packages', 'lunara-film' ); ?></strong>
            <span><?php echo esc_html( $active_preset_key ? __( 'Saved controls match one of these presets.', 'lunara-film' ) : __( 'Current values are custom; compare before saving a preset.', 'lunara-film' ) ); ?></span>
        </div>
        <div class="lunara-control-desk-utility-comparison-track">
            <?php foreach ( $presets as $preset_key => $preset ) : ?>
                <?php lunara_control_desk_render_utility_search_preset_comparison_item( $preset_key, $preset, $active_preset_key ); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function lunara_control_desk_render_utility_search_preset_card( $preset_key, $preset, $active_preset_key, $search_preview, $ledger_preview, $recovery_preview ) {
    $is_active = $preset_key === $active_preset_key;
    $values    = isset( $preset['values'] ) && is_array( $preset['values'] ) ? $preset['values'] : array();
    ?>
    <fieldset class="lunara-control-desk-homepage-choice <?php echo $is_active ? 'is-selected' : ''; ?>">
        <legend>
            <strong><?php echo esc_html( $preset['label'] ); ?></strong>
            <small><?php echo esc_html( $preset['copy'] ); ?></small>
            <em><?php echo esc_html( $is_active ? __( 'active', 'lunara-film' ) : __( 'preset', 'lunara-film' ) ); ?></em>
        </legend>
        <div class="lunara-control-desk-source-grid">
            <?php foreach ( $values as $key => $value ) : ?>
                <?php if ( 'lunara_utility_search_preset' === $key ) : ?>
                    <?php continue; ?>
                <?php endif; ?>
                <span class="lunara-control-desk-source-pill">
                    <strong><?php echo esc_html( lunara_control_desk_utility_search_key_label( $key ) ); ?></strong>
                    <small><?php echo esc_html( (string) $value ); ?></small>
                </span>
            <?php endforeach; ?>
        </div>
        <div class="lunara-control-desk-actions">
            <button type="submit" class="button" name="lunara_utility_search_preset" value="<?php echo esc_attr( $preset_key ); ?>">
                <?php echo esc_html( $is_active ? __( 'Reapply preset', 'lunara-film' ) : __( 'Apply preset', 'lunara-film' ) ); ?>
            </button>
            <a class="button" href="<?php echo esc_url( lunara_control_desk_utility_search_preset_preview_url( $search_preview, $preset_key ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Search', 'lunara-film' ); ?></a>
            <a class="button" href="<?php echo esc_url( lunara_control_desk_utility_search_preset_preview_url( $ledger_preview, $preset_key ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ledger Search', 'lunara-film' ); ?></a>
            <a class="button" href="<?php echo esc_url( lunara_control_desk_utility_search_preset_preview_url( $recovery_preview, $preset_key ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '404', 'lunara-film' ); ?></a>
            <a class="button" href="<?php echo esc_url( lunara_control_desk_utility_search_preset_preview_url( $search_preview, $preset_key, true ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '390px', 'lunara-film' ); ?></a>
        </div>
    </fieldset>
    <?php
}

function lunara_control_desk_render_utility_search_studio() {
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        ?>
        <section id="lunara-theme-studio-utility-search-studio" class="lunara-control-desk-homepage-studio">
            <div class="lunara-control-desk-panel-header">
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Utility Search Studio', 'lunara-film' ); ?></p>
                <h3><?php esc_html_e( 'Utility route controls require theme editing permission', 'lunara-film' ); ?></h3>
                <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Search and recovery routes remain visible, but direct utility rhythm changes are limited to administrators.', 'lunara-film' ); ?></p>
            </div>
        </section>
        <?php
        return;
    }

    $search_preview   = add_query_arg( 's', 'sinners', home_url( '/' ) );
    $ledger_preview   = add_query_arg( 's', 'oscars', home_url( '/' ) );
    $recovery_preview = home_url( '/definitely-not-a-real-lunara-route/' );
    $presets          = lunara_control_desk_utility_search_preset_specs();
    $active_preset_key = lunara_control_desk_utility_search_active_preset_key();
    $active_label     = $active_preset_key && isset( $presets[ $active_preset_key ] )
        ? $presets[ $active_preset_key ]['label']
        : __( 'Custom utility route', 'lunara-film' );
    ?>
    <section id="lunara-theme-studio-utility-search-studio" class="lunara-control-desk-homepage-studio">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Utility Search Studio', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Search and recovery routes without the dead utility-page feeling', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Tune Search results, Oscar direct matches, no-results recovery, and 404 routing as a small but real publication surface. No URL, query, or content behavior changes.', 'lunara-film' ); ?></p>
        </div>
        <form class="lunara-control-desk-homepage-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="lunara_save_utility_search_studio" />
            <?php wp_nonce_field( 'lunara_save_utility_search_studio', 'lunara_utility_search_nonce' ); ?>

            <div class="lunara-control-desk-homepage-grid">
                <div class="lunara-control-desk-homepage-card">
                    <div class="lunara-control-desk-card-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Utility Presets', 'lunara-film' ); ?></p>
                            <h3><?php esc_html_e( 'Apply or preview a complete Search/recovery package', 'lunara-film' ); ?></h3>
                            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Presets save the same bounded controls below. Preview links are request-only and only affect admins with theme editing permission.', 'lunara-film' ); ?></p>
                        </div>
                        <div class="lunara-control-desk-status-pill">
                            <strong><?php esc_html_e( 'Current package', 'lunara-film' ); ?></strong>
                            <span><?php echo esc_html( $active_label ); ?></span>
                        </div>
                    </div>
                    <?php lunara_control_desk_render_utility_search_preset_comparison_strip( $presets, $active_preset_key ); ?>
                    <div class="lunara-control-desk-homepage-choice-grid">
                        <?php foreach ( $presets as $preset_key => $preset ) : ?>
                            <?php lunara_control_desk_render_utility_search_preset_card( $preset_key, $preset, $active_preset_key, $search_preview, $ledger_preview, $recovery_preview ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="lunara-control-desk-homepage-card">
                    <div class="lunara-control-desk-card-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Utility Rhythm', 'lunara-film' ); ?></p>
                            <h3><?php esc_html_e( 'Search, direct matches, and recovery emphasis', 'lunara-film' ); ?></h3>
                            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'These controls keep the shared Lunara type system while giving utility pages their own route-family pulse.', 'lunara-film' ); ?></p>
                        </div>
                    </div>
                    <div class="lunara-control-desk-homepage-choice-grid">
                        <?php foreach ( lunara_control_desk_utility_search_select_specs() as $key => $spec ) : ?>
                            <?php lunara_control_desk_render_utility_search_select_control( $key, $spec ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="lunara-control-desk-homepage-card">
                    <div class="lunara-control-desk-card-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Geometry', 'lunara-film' ); ?></p>
                            <h3><?php esc_html_e( 'Spacing, result height, and grid width', 'lunara-film' ); ?></h3>
                            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Values clamp server-side so Search and 404 can get denser without mobile overflow, empty chambers, or cramped copy.', 'lunara-film' ); ?></p>
                        </div>
                    </div>
                    <div class="lunara-control-desk-homepage-number-grid">
                        <?php foreach ( lunara_control_desk_utility_search_number_specs() as $key => $spec ) : ?>
                            <?php lunara_control_desk_render_utility_search_number_control( $key, $spec ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="lunara-control-desk-homepage-card">
                    <div class="lunara-control-desk-card-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Search Focus', 'lunara-film' ); ?></p>
                            <h3><?php esc_html_e( 'Lead route, spotlight type, and recovery priority', 'lunara-film' ); ?></h3>
                            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'These controls change presentation priority only. Search queries, URLs, and result eligibility stay untouched.', 'lunara-film' ); ?></p>
                        </div>
                    </div>
                    <div class="lunara-control-desk-homepage-choice-grid">
                        <?php foreach ( lunara_control_desk_utility_search_focus_select_specs() as $key => $spec ) : ?>
                            <?php lunara_control_desk_render_utility_search_focus_select_control( $key, $spec ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="lunara-control-desk-homepage-footer">
                <div>
                    <strong><?php esc_html_e( 'Preview after saving', 'lunara-film' ); ?></strong>
                    <span><?php esc_html_e( 'Check a populated Search page, a 390px Search view, and the recovery route after each change.', 'lunara-film' ); ?></span>
                </div>
                <div class="lunara-control-desk-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Utility Search Studio', 'lunara-film' ); ?></button>
                    <a class="button" href="<?php echo esc_url( $search_preview ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Search Desktop', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( add_query_arg( 'lunara-width', '390', $search_preview ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Search 390px', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( $recovery_preview ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '404 Recovery', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( add_query_arg( 'lunara-width', '390', $recovery_preview ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '404 390px', 'lunara-film' ); ?></a>
                </div>
            </div>
        </form>
    </section>
    <?php
}

function lunara_control_desk_oscars_dossier_preset_preview_url( $path, $preset_key, $mobile = false ) {
    $url = add_query_arg( 'lunara-oscars-preset', $preset_key, home_url( $path ) );

    if ( $mobile ) {
        $url = add_query_arg( 'lunara-width', '390', $url );
    }

    return $url;
}

function lunara_control_desk_render_oscars_dossier_select_control( $key, $spec ) {
    $value     = lunara_control_desk_oscars_dossier_select_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <fieldset class="lunara-control-desk-homepage-choice">
        <legend>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </legend>
        <div class="lunara-control-desk-homepage-choice-options">
            <?php foreach ( $spec['options'] as $option_key => $option ) : ?>
                <label class="<?php echo $value === $option_key ? 'is-selected' : ''; ?>">
                    <input
                        type="radio"
                        name="lunara_oscars_dossier_select[<?php echo esc_attr( $key ); ?>]"
                        value="<?php echo esc_attr( $option_key ); ?>"
                        <?php checked( $value, $option_key ); ?>
                    />
                    <span>
                        <strong><?php echo esc_html( $option['label'] ); ?></strong>
                        <small><?php echo esc_html( $option['copy'] ); ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <?php
}

function lunara_control_desk_render_oscars_dossier_number_control( $key, $spec ) {
    $value     = lunara_control_desk_oscars_dossier_number_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <label class="lunara-control-desk-homepage-number" data-lunara-brand-number-control>
        <span>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
        </span>
        <input
            type="range"
            min="<?php echo esc_attr( $spec['min'] ); ?>"
            max="<?php echo esc_attr( $spec['max'] ); ?>"
            step="<?php echo esc_attr( $spec['step'] ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            data-lunara-brand-range
        />
        <span class="lunara-control-desk-brand-number-value">
            <input
                type="number"
                name="lunara_oscars_dossier_number[<?php echo esc_attr( $key ); ?>]"
                min="<?php echo esc_attr( $spec['min'] ); ?>"
                max="<?php echo esc_attr( $spec['max'] ); ?>"
                step="<?php echo esc_attr( $spec['step'] ); ?>"
                value="<?php echo esc_attr( $value ); ?>"
                data-lunara-brand-number
            />
            <em><?php echo esc_html( $spec['unit'] ); ?></em>
        </span>
        <span class="lunara-control-desk-brand-reset">
            <label>
                <input type="checkbox" name="lunara_oscars_dossier_reset[<?php echo esc_attr( $key ); ?>]" value="1" />
                <?php
                printf(
                    /* translators: %d: setting default value. */
                    esc_html__( 'Reset to %d', 'lunara-film' ),
                    absint( $spec['default'] )
                );
                ?>
            </label>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </span>
    </label>
    <?php
}

function lunara_control_desk_render_oscars_dossier_preset_comparison_item( $preset_key, $preset, $active_preset_key ) {
    $values    = isset( $preset['values'] ) && is_array( $preset['values'] ) ? $preset['values'] : array();
    $is_active = $preset_key === $active_preset_key;
    ?>
    <article class="lunara-control-desk-oscars-comparison-item <?php echo $is_active ? 'is-active' : ''; ?>">
        <header>
            <strong><?php echo esc_html( isset( $preset['label'] ) ? $preset['label'] : $preset_key ); ?></strong>
            <span><?php echo esc_html( $is_active ? __( 'active package', 'lunara-film' ) : __( 'available preset', 'lunara-film' ) ); ?></span>
        </header>
        <dl>
            <?php foreach ( lunara_control_desk_oscars_dossier_comparison_specs() as $key ) : ?>
                <div>
                    <dt><?php echo esc_html( lunara_control_desk_oscars_dossier_key_label( $key ) ); ?></dt>
                    <dd><?php echo esc_html( lunara_control_desk_oscars_dossier_value_label( $key, isset( $values[ $key ] ) ? $values[ $key ] : '' ) ); ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </article>
    <?php
}

function lunara_control_desk_render_oscars_dossier_preset_comparison_strip( $presets, $active_preset_key ) {
    $presets = is_array( $presets ) ? $presets : lunara_control_desk_oscars_dossier_preset_specs();
    ?>
    <div class="lunara-control-desk-oscars-comparison-strip" aria-label="<?php esc_attr_e( 'Oscars Dossier preset comparison', 'lunara-film' ); ?>">
        <div class="lunara-control-desk-oscars-comparison-head">
            <strong><?php esc_html_e( 'Compare the Oscars dossiers', 'lunara-film' ); ?></strong>
            <span><?php echo esc_html( $active_preset_key ? __( 'Saved controls match one of these presets.', 'lunara-film' ) : __( 'Current values are custom; compare before saving a preset.', 'lunara-film' ) ); ?></span>
        </div>
        <div class="lunara-control-desk-oscars-comparison-track">
            <?php foreach ( $presets as $preset_key => $preset ) : ?>
                <?php lunara_control_desk_render_oscars_dossier_preset_comparison_item( $preset_key, $preset, $active_preset_key ); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function lunara_control_desk_render_oscars_dossier_preset_card( $preset_key, $preset, $active_preset_key ) {
    $is_active = $preset_key === $active_preset_key;
    $values    = isset( $preset['values'] ) && is_array( $preset['values'] ) ? $preset['values'] : array();
    ?>
    <fieldset class="lunara-control-desk-homepage-choice <?php echo $is_active ? 'is-selected' : ''; ?>">
        <legend>
            <strong><?php echo esc_html( $preset['label'] ); ?></strong>
            <small><?php echo esc_html( $preset['copy'] ); ?></small>
            <em><?php echo esc_html( $is_active ? __( 'active', 'lunara-film' ) : __( 'preset', 'lunara-film' ) ); ?></em>
        </legend>
        <div class="lunara-control-desk-source-grid">
            <?php foreach ( $values as $key => $value ) : ?>
                <span class="lunara-control-desk-source-pill">
                    <strong><?php echo esc_html( lunara_control_desk_oscars_dossier_key_label( $key ) ); ?></strong>
                    <?php echo esc_html( lunara_control_desk_oscars_dossier_value_label( $key, $value ) ); ?>
                </span>
            <?php endforeach; ?>
        </div>
        <div class="lunara-control-desk-actions">
            <button type="submit" class="button" name="lunara_oscars_dossier_preset" value="<?php echo esc_attr( $preset_key ); ?>">
                <?php echo esc_html( $is_active ? __( 'Reapply preset', 'lunara-film' ) : __( 'Apply preset', 'lunara-film' ) ); ?>
            </button>
            <a class="button" href="<?php echo esc_url( lunara_control_desk_oscars_dossier_preset_preview_url( '/oscars/ceremony/98/', $preset_key ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ceremony', 'lunara-film' ); ?></a>
            <a class="button" href="<?php echo esc_url( lunara_control_desk_oscars_dossier_preset_preview_url( '/oscars/ceremony/98/', $preset_key, true ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '390px', 'lunara-film' ); ?></a>
            <a class="button" href="<?php echo esc_url( lunara_control_desk_oscars_dossier_preset_preview_url( '/oscars/category/best-picture/', $preset_key ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Best Picture', 'lunara-film' ); ?></a>
        </div>
    </fieldset>
    <?php
}

function lunara_control_desk_render_oscars_dossier_studio() {
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        ?>
        <section id="lunara-theme-studio-oscars-dossier-studio" class="lunara-control-desk-homepage-studio lunara-control-desk-oscars-dossier-studio">
            <div class="lunara-control-desk-panel-header">
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Oscars Dossier Studio', 'lunara-film' ); ?></p>
                <h3><?php esc_html_e( 'Oscars controls require theme editing permission', 'lunara-film' ); ?></h3>
                <p class="lunara-control-desk-subtle"><?php esc_html_e( 'The public Oscar Ledger remains visible, but direct dossier rhythm changes are limited to administrators.', 'lunara-film' ); ?></p>
            </div>
        </section>
        <?php
        return;
    }

    $presets           = lunara_control_desk_oscars_dossier_preset_specs();
    $active_preset_key = lunara_control_desk_oscars_dossier_active_preset_key();
    $active_label      = $active_preset_key && isset( $presets[ $active_preset_key ] )
        ? $presets[ $active_preset_key ]['label']
        : __( 'Custom dossier', 'lunara-film' );
    $preview_routes    = array(
        array(
            'label' => __( 'Oscars Portal', 'lunara-film' ),
            'url'   => home_url( '/oscars/' ),
        ),
        array(
            'label' => __( 'Ceremony Dossier', 'lunara-film' ),
            'url'   => home_url( '/oscars/ceremony/98/' ),
        ),
        array(
            'label' => __( 'Best Picture', 'lunara-film' ),
            'url'   => home_url( '/oscars/category/best-picture/' ),
        ),
        array(
            'label' => __( 'Title File', 'lunara-film' ),
            'url'   => home_url( '/oscars/title/tt0110912/' ),
        ),
        array(
            'label' => __( 'Person File', 'lunara-film' ),
            'url'   => home_url( '/oscars/name/nm0000233/' ),
        ),
    );
    ?>
    <section id="lunara-theme-studio-oscars-dossier-studio" class="lunara-control-desk-homepage-studio lunara-control-desk-oscars-dossier-studio">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Oscars Dossier Studio', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Route-family rhythm for the Oscar Ledger', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Tune ceremony, category, title, and person pages as a premium historical dossier system while the Academy plugin keeps owning the data.', 'lunara-film' ); ?></p>
        </div>
        <form class="lunara-control-desk-homepage-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="lunara_save_oscars_dossier_studio" />
            <?php wp_nonce_field( 'lunara_save_oscars_dossier_studio', 'lunara_oscars_dossier_nonce' ); ?>

            <div class="lunara-control-desk-homepage-card">
                <div class="lunara-control-desk-card-head">
                    <div>
                        <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Dossier Presets', 'lunara-film' ); ?></p>
                        <h3><?php esc_html_e( 'Apply or preview a complete Oscars route package', 'lunara-film' ); ?></h3>
                        <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Presets save the same bounded controls below. Preview links are request-only and only affect admins with theme editing permission.', 'lunara-film' ); ?></p>
                    </div>
                    <div class="lunara-control-desk-status-pill">
                        <strong><?php esc_html_e( 'Current dossier', 'lunara-film' ); ?></strong>
                        <span><?php echo esc_html( $active_label ); ?></span>
                    </div>
                </div>
                <?php lunara_control_desk_render_oscars_dossier_preset_comparison_strip( $presets, $active_preset_key ); ?>
                <div class="lunara-control-desk-oscars-preset-grid">
                    <?php foreach ( $presets as $preset_key => $preset ) : ?>
                        <?php lunara_control_desk_render_oscars_dossier_preset_card( $preset_key, $preset, $active_preset_key ); ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="lunara-control-desk-oscars-grid">
                <div class="lunara-control-desk-homepage-card">
                    <div class="lunara-control-desk-card-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Editorial Dossier', 'lunara-film' ); ?></p>
                            <h3><?php esc_html_e( 'Density, ceremony rhythm, profiles, and write-ups', 'lunara-film' ); ?></h3>
                            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'These controls change presentation only. They do not mutate Oscar result data, route slugs, write-up status, or source metadata.', 'lunara-film' ); ?></p>
                        </div>
                    </div>
                    <div class="lunara-control-desk-homepage-choice-grid">
                        <?php foreach ( lunara_control_desk_oscars_dossier_select_specs() as $key => $spec ) : ?>
                            <?php lunara_control_desk_render_oscars_dossier_select_control( $key, $spec ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="lunara-control-desk-homepage-card">
                    <div class="lunara-control-desk-card-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Geometry', 'lunara-film' ); ?></p>
                            <h3><?php esc_html_e( 'Spacing and responsive card width', 'lunara-film' ); ?></h3>
                            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Values clamp server-side so the ledger can become denser without causing mobile overflow or dead ledger space.', 'lunara-film' ); ?></p>
                        </div>
                    </div>
                    <div class="lunara-control-desk-homepage-number-grid">
                        <?php foreach ( lunara_control_desk_oscars_dossier_number_specs() as $key => $spec ) : ?>
                            <?php lunara_control_desk_render_oscars_dossier_number_control( $key, $spec ); ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="lunara-control-desk-oscars-preview-grid">
                        <?php foreach ( $preview_routes as $route ) : ?>
                            <?php $mobile_url = add_query_arg( 'lunara-width', '390', $route['url'] ); ?>
                            <span>
                                <strong><?php echo esc_html( $route['label'] ); ?></strong>
                                <a href="<?php echo esc_url( $route['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Desktop', 'lunara-film' ); ?></a>
                                <a href="<?php echo esc_url( $mobile_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '390px', 'lunara-film' ); ?></a>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="lunara-control-desk-homepage-footer">
                <div>
                    <strong><?php esc_html_e( 'Preview after saving', 'lunara-film' ); ?></strong>
                    <span><?php esc_html_e( 'Check the portal, a ceremony dossier, Best Picture, one title file, and one person file after each change.', 'lunara-film' ); ?></span>
                </div>
                <div class="lunara-control-desk-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Oscars Dossier Studio', 'lunara-film' ); ?></button>
                    <a class="button" href="<?php echo esc_url( home_url( '/oscars/ceremony/98/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ceremony', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( home_url( '/oscars/category/best-picture/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Best Picture', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( home_url( '/oscars/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Oscars Portal', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( add_query_arg( 'lunara-width', '390', home_url( '/oscars/ceremony/98/' ) ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '390px', 'lunara-film' ); ?></a>
                </div>
            </div>
        </form>
    </section>
    <?php
}

function lunara_control_desk_render_journal_archive_select_control( $key, $spec ) {
    $value     = lunara_control_desk_journal_archive_select_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <fieldset class="lunara-control-desk-homepage-choice">
        <legend>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </legend>
        <div class="lunara-control-desk-homepage-choice-options">
            <?php foreach ( $spec['options'] as $option_key => $option ) : ?>
                <label class="<?php echo $value === $option_key ? 'is-selected' : ''; ?>">
                    <input
                        type="radio"
                        name="lunara_journal_archive_select[<?php echo esc_attr( $key ); ?>]"
                        value="<?php echo esc_attr( $option_key ); ?>"
                        <?php checked( $value, $option_key ); ?>
                    />
                    <span>
                        <strong><?php echo esc_html( $option['label'] ); ?></strong>
                        <small><?php echo esc_html( $option['copy'] ); ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <?php
}

function lunara_control_desk_render_journal_archive_number_control( $key, $spec ) {
    $value     = lunara_control_desk_journal_archive_number_value( $key );
    $is_custom = lunara_control_desk_theme_mod_has_custom_value( $key );
    ?>
    <label class="lunara-control-desk-homepage-number" data-lunara-brand-number-control>
        <span>
            <strong><?php echo esc_html( $spec['label'] ); ?></strong>
            <small><?php echo esc_html( $spec['note'] ); ?></small>
        </span>
        <input
            type="range"
            min="<?php echo esc_attr( $spec['min'] ); ?>"
            max="<?php echo esc_attr( $spec['max'] ); ?>"
            step="<?php echo esc_attr( $spec['step'] ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            data-lunara-brand-range
        />
        <span class="lunara-control-desk-brand-number-value">
            <input
                type="number"
                name="lunara_journal_archive_number[<?php echo esc_attr( $key ); ?>]"
                min="<?php echo esc_attr( $spec['min'] ); ?>"
                max="<?php echo esc_attr( $spec['max'] ); ?>"
                step="<?php echo esc_attr( $spec['step'] ); ?>"
                value="<?php echo esc_attr( $value ); ?>"
                data-lunara-brand-number
            />
            <em><?php echo esc_html( $spec['unit'] ); ?></em>
        </span>
        <span class="lunara-control-desk-brand-reset">
            <label>
                <input type="checkbox" name="lunara_journal_archive_reset[<?php echo esc_attr( $key ); ?>]" value="1" />
                <?php
                printf(
                    /* translators: %d: setting default value. */
                    esc_html__( 'Reset to %d', 'lunara-film' ),
                    absint( $spec['default'] )
                );
                ?>
            </label>
            <em><?php echo esc_html( $is_custom ? __( 'custom', 'lunara-film' ) : __( 'default', 'lunara-film' ) ); ?></em>
        </span>
    </label>
    <?php
}

function lunara_control_desk_render_journal_archive_studio() {
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        ?>
        <section id="lunara-theme-studio-journal-archive-studio" class="lunara-control-desk-homepage-studio">
            <div class="lunara-control-desk-panel-header">
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Journal Archive Studio', 'lunara-film' ); ?></p>
                <h3><?php esc_html_e( 'Journal archive controls require theme editing permission', 'lunara-film' ); ?></h3>
                <p class="lunara-control-desk-subtle"><?php esc_html_e( 'The public Journal remains visible, but direct archive rhythm changes are limited to administrators.', 'lunara-film' ); ?></p>
            </div>
        </section>
        <?php
        return;
    }
    ?>
    <section id="lunara-theme-studio-journal-archive-studio" class="lunara-control-desk-homepage-studio">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Journal Archive Studio', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Live-desk density and archive rhythm controls', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Tune the Journal as a real trade desk: lead file force, command-band pace, card density, and mobile-safe image chambers, all without raw CSS.', 'lunara-film' ); ?></p>
        </div>
        <form class="lunara-control-desk-homepage-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="lunara_save_journal_archive_studio" />
            <?php wp_nonce_field( 'lunara_save_journal_archive_studio', 'lunara_journal_archive_nonce' ); ?>

            <div class="lunara-control-desk-homepage-grid">
                <div class="lunara-control-desk-homepage-card">
                    <div class="lunara-control-desk-card-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Editorial Rhythm', 'lunara-film' ); ?></p>
                            <h3><?php esc_html_e( 'Archive, lead, and desk emphasis', 'lunara-film' ); ?></h3>
                            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'These settings preserve the Journal query and section structure while changing how alive the desk feels.', 'lunara-film' ); ?></p>
                        </div>
                    </div>
                    <div class="lunara-control-desk-homepage-choice-grid">
                        <?php foreach ( lunara_control_desk_journal_archive_select_specs() as $key => $spec ) : ?>
                            <?php lunara_control_desk_render_journal_archive_select_control( $key, $spec ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="lunara-control-desk-homepage-card">
                    <div class="lunara-control-desk-card-head">
                        <div>
                            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Geometry', 'lunara-film' ); ?></p>
                            <h3><?php esc_html_e( 'Spacing and media chambers', 'lunara-film' ); ?></h3>
                            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Every number is clamped server-side so the Journal can get tighter without overflow, crop, or empty-space drift.', 'lunara-film' ); ?></p>
                        </div>
                    </div>
                    <div class="lunara-control-desk-homepage-number-grid">
                        <?php foreach ( lunara_control_desk_journal_archive_number_specs() as $key => $spec ) : ?>
                            <?php lunara_control_desk_render_journal_archive_number_control( $key, $spec ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="lunara-control-desk-homepage-footer">
                <div>
                    <strong><?php esc_html_e( 'Preview after saving', 'lunara-film' ); ?></strong>
                    <span><?php esc_html_e( 'Judge the hero, deskbar, filters, lead file, archive cards, and retention lane as one live editorial surface.', 'lunara-film' ); ?></span>
                </div>
                <div class="lunara-control-desk-actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Journal Archive Studio', 'lunara-film' ); ?></button>
                    <a class="button" href="<?php echo esc_url( home_url( '/journal/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Journal Desktop', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( add_query_arg( 'lunara-width', '390', home_url( '/journal/' ) ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Journal 390px', 'lunara-film' ); ?></a>
                    <a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Homepage', 'lunara-film' ); ?></a>
                </div>
            </div>
        </form>
    </section>
    <?php
}

function lunara_control_desk_image_quality_targets() {
    return array(
        'review-card' => array(
            'label'   => __( 'Review/home/archive cards', 'lunara-film' ),
            'size'    => 'lunara-review-card-retina',
            'width'   => 1500,
            'height'  => 2000,
            'ratio'   => '3:4',
            'surface' => __( 'Review archive cards, homepage review cards, and card-style editorial placements.', 'lunara-film' ),
        ),
        'poster'      => array(
            'label'   => __( 'Poster library modules', 'lunara-film' ),
            'size'    => 'lunara-poster-library',
            'width'   => 2000,
            'height'  => 3000,
            'ratio'   => '2:3',
            'surface' => __( 'Debrief posters, Oscar title/person modules, poster grids, and sidebar poster chambers.', 'lunara-film' ),
        ),
        'hero'        => array(
            'label'   => __( 'Hero, Journal, and spotlight images', 'lunara-film' ),
            'size'    => 'lunara-hero-spotlight',
            'width'   => 1920,
            'height'  => 1080,
            'ratio'   => '16:9',
            'surface' => __( 'Journal hero images, spotlight artwork, trailer backdrops, and wide editorial media.', 'lunara-film' ),
        ),
        'oscar-fact'  => array(
            'label'   => __( 'Oscar Facts visuals', 'lunara-film' ),
            'size'    => 'lunara-hero-spotlight',
            'width'   => 1920,
            'height'  => 1080,
            'ratio'   => '16:9',
            'surface' => __( 'Curated Oscar Fact stills, ceremony images, portraits, and wide homepage carousel visuals.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_image_quality_attachment_dimensions( $attachment_id ) {
    $attachment_id = absint( $attachment_id );

    if ( ! $attachment_id ) {
        return array(
            'width'  => 0,
            'height' => 0,
        );
    }

    $metadata = wp_get_attachment_metadata( $attachment_id );

    return array(
        'width'  => isset( $metadata['width'] ) ? absint( $metadata['width'] ) : 0,
        'height' => isset( $metadata['height'] ) ? absint( $metadata['height'] ) : 0,
    );
}

function lunara_control_desk_image_quality_state( $attachment_id, $target, $external_url = '' ) {
    $attachment_id = absint( $attachment_id );
    $external_url  = trim( (string) $external_url );
    $target_width  = isset( $target['width'] ) ? absint( $target['width'] ) : 0;
    $target_height = isset( $target['height'] ) ? absint( $target['height'] ) : 0;
    $dimensions    = lunara_control_desk_image_quality_attachment_dimensions( $attachment_id );

    if ( ! $attachment_id && '' !== $external_url ) {
        return array(
            'state'      => 'weak',
            'label'      => __( 'External URL', 'lunara-film' ),
            'note'       => __( 'The site can render it, but WordPress cannot report source dimensions. Prefer a Media Library image for quality control.', 'lunara-film' ),
            'reason'     => 'external',
            'dimensions' => $dimensions,
        );
    }

    if ( ! $attachment_id ) {
        return array(
            'state'      => 'weak',
            'label'      => __( 'Missing', 'lunara-film' ),
            'note'       => __( 'No inspectable source image is attached to this placement.', 'lunara-film' ),
            'reason'     => 'missing',
            'dimensions' => $dimensions,
        );
    }

    if ( $dimensions['width'] >= $target_width && $dimensions['height'] >= $target_height ) {
        return array(
            'state'      => 'ready',
            'label'      => __( 'Ready', 'lunara-film' ),
            'note'       => __( 'The source file meets the Lunara target for this surface.', 'lunara-film' ),
            'reason'     => 'at-target',
            'dimensions' => $dimensions,
        );
    }

    if ( $dimensions['width'] >= (int) round( $target_width * 0.85 ) && $dimensions['height'] >= (int) round( $target_height * 0.85 ) ) {
        return array(
            'state'      => 'weak',
            'label'      => __( 'Near target', 'lunara-film' ),
            'note'       => __( 'Usable in a pinch, but not the premium source standard.', 'lunara-film' ),
            'reason'     => 'near-target',
            'dimensions' => $dimensions,
        );
    }

    return array(
        'state'      => 'weak',
        'label'      => __( 'Replace source', 'lunara-film' ),
        'note'       => __( 'This source is below the target and can read soft or blurry in high-visibility placements.', 'lunara-film' ),
        'reason'     => 'below-target',
        'dimensions' => $dimensions,
    );
}

function lunara_control_desk_review_card_source( $post_id ) {
    $post_id       = absint( $post_id );
    $card_url      = trim( (string) get_post_meta( $post_id, '_lunara_review_card_image', true ) );
    $attachment_id = '' !== $card_url ? absint( attachment_url_to_postid( $card_url ) ) : 0;
    $source_label  = '' !== $card_url ? __( 'Card image override', 'lunara-film' ) : '';

    if ( ! $attachment_id && has_post_thumbnail( $post_id ) ) {
        $attachment_id = absint( get_post_thumbnail_id( $post_id ) );
        $source_label  = __( 'Featured image fallback', 'lunara-film' );
    }

    if ( '' === $source_label ) {
        $source_label = __( 'No source selected', 'lunara-film' );
    }

    return array(
        'attachment_id' => $attachment_id,
        'external_url'  => $attachment_id ? '' : $card_url,
        'source_label'  => $source_label,
    );
}

function lunara_control_desk_post_status_label( $post ) {
    $status = get_post_status( $post );
    $object = $status ? get_post_status_object( $status ) : null;

    if ( $object && ! empty( $object->label ) ) {
        return (string) $object->label;
    }

    return $status ? ucwords( str_replace( array( '-', '_' ), ' ', (string) $status ) ) : __( 'Unknown', 'lunara-film' );
}

function lunara_control_desk_oscar_fact_visual_state( $attachment_id, $target, $visual_verified, $visual_treatment = 'wide' ) {
    $attachment_id   = absint( $attachment_id );
    $visual_verified = (bool) $visual_verified;
    $visual_treatment = 'archival' === $visual_treatment ? 'archival' : 'wide';
    $dimensions      = lunara_control_desk_image_quality_attachment_dimensions( $attachment_id );

    if ( ! $attachment_id ) {
        return array(
            'state'      => 'weak',
            'label'      => __( 'No visual staged', 'lunara-film' ),
            'note'       => __( 'This fact is text-only until a verified wide visual is chosen for the homepage carousel.', 'lunara-film' ),
            'dimensions' => $dimensions,
        );
    }

    if ( ! $visual_verified ) {
        return array(
            'state'      => 'weak',
            'label'      => __( 'Needs visual verification', 'lunara-film' ),
            'note'       => __( 'A featured image is staged, but the public carousel will keep it hidden until it is marked as the verified public visual.', 'lunara-film' ),
            'dimensions' => $dimensions,
        );
    }

    if ( 'archival' === $visual_treatment ) {
        return array(
            'state'      => 'ready',
            'label'      => __( 'Archival fit', 'lunara-film' ),
            'note'       => __( 'This verified visual is preserved in a framed archival treatment instead of being cropped to the wide carousel target.', 'lunara-film' ),
            'dimensions' => $dimensions,
        );
    }

    return lunara_control_desk_image_quality_state( $attachment_id, $target );
}

function lunara_control_desk_review_pairing_source_status( $preview, $poster_required = true ) {
    $preview         = is_array( $preview ) ? $preview : array();
    $poster_required = (bool) $poster_required;
    $warnings        = isset( $preview['warnings'] ) && is_array( $preview['warnings'] ) ? $preview['warnings'] : array();
    $tt              = isset( $preview['tt'] ) ? trim( (string) $preview['tt'] ) : '';
    $poster          = isset( $preview['poster_html'] ) ? trim( (string) $preview['poster_html'] ) : '';
    $expected        = isset( $preview['title_base'] ) ? trim( (string) $preview['title_base'] ) : '';
    $resolved        = isset( $preview['resolved_title'] ) ? trim( (string) $preview['resolved_title'] ) : '';

    if ( '' === $tt ) {
        $warnings[] = __( 'Missing IMDb title ID. Lock the pairing with a tt-id before trusting poster or link accuracy.', 'lunara-film' );
    }

    if ( $poster_required && '' === $poster ) {
        $warnings[] = __( 'No poster resolved for this Pair It With source.', 'lunara-film' );
    }

    if ( '' !== $expected && '' !== $resolved && function_exists( 'lunara_normalize_title_key' ) && lunara_normalize_title_key( $expected ) !== lunara_normalize_title_key( $resolved ) ) {
        $warnings[] = __( 'Resolved title differs from the entered Pair It With title.', 'lunara-film' );
    }

    $warnings = array_values( array_unique( array_filter( array_map( 'trim', $warnings ) ) ) );

    if ( empty( $warnings ) ) {
        return array(
            'state'      => 'ready',
            'label'      => __( 'Pairing source locked', 'lunara-film' ),
            'note'       => $poster_required ? __( 'IMDb ID, poster, and resolved title agree enough for public review.', 'lunara-film' ) : __( 'IMDb ID and resolved title agree; open Pairing sources to resolve the poster preview.', 'lunara-film' ),
            'reason'     => 'pairing-locked',
            'dimensions' => array(
                'width'  => 0,
                'height' => 0,
            ),
            'warnings'   => array(),
        );
    }

    return array(
        'state'      => 'weak',
        'label'      => __( 'Needs source review', 'lunara-film' ),
        'note'       => __( 'Review the expected title, IMDb ID, resolved title, and poster before calling this pairing safe.', 'lunara-film' ),
        'reason'     => 'pairing-review',
        'dimensions' => array(
            'width'  => 0,
            'height' => 0,
        ),
        'warnings'   => $warnings,
    );
}

function lunara_control_desk_review_pairing_source_rows( $limit = 80, $resolve_posters = false ) {
    $limit           = max( 1, min( 160, absint( $limit ) ) );
    $resolve_posters = (bool) $resolve_posters;
    $rows            = array();

    if ( ! function_exists( 'lunara_parse_pair_it_with_value' ) ) {
        return $rows;
    }

    $posts = get_posts(
        array(
            'post_type'      => 'review',
            'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
            'posts_per_page' => $limit,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        )
    );

    foreach ( $posts as $post ) {
        $pairings = array(
            'theme_echo'      => array(
                'label' => __( 'Theme Echo', 'lunara-film' ),
                'value' => get_post_meta( $post->ID, '_lunara_theme_echo', true ),
            ),
            'counter_program' => array(
                'label' => __( 'Counter-Program', 'lunara-film' ),
                'value' => get_post_meta( $post->ID, '_lunara_counter_program', true ),
            ),
            'career_context'  => array(
                'label' => __( 'Career Context', 'lunara-film' ),
                'value' => function_exists( 'lunara_get_career_context_meta' ) ? lunara_get_career_context_meta( $post->ID ) : get_post_meta( $post->ID, '_lunara_career_context', true ),
            ),
        );

        foreach ( $pairings as $slot_key => $pairing ) {
            $raw = trim( (string) ( $pairing['value'] ?? '' ) );
            if ( '' === $raw ) {
                continue;
            }

            $preview = lunara_parse_pair_it_with_value( $raw, $post->ID, $resolve_posters );
            $status  = lunara_control_desk_review_pairing_source_status( $preview, $resolve_posters );

            $rows[] = array(
                'surface'        => __( 'Pair It With source', 'lunara-film' ),
                'surface_key'    => 'review-pairing',
                'title'          => get_the_title( $post ),
                'post_id'        => absint( $post->ID ),
                'post_status'    => (string) get_post_status( $post ),
                'status_label'   => lunara_control_desk_post_status_label( $post ),
                'edit_url'       => get_edit_post_link( $post->ID, '' ),
                'view_url'       => get_permalink( $post ),
                'media_url'      => '',
                'attachment_id'  => 0,
                'source_label'   => __( 'Debrief Pair It With field', 'lunara-film' ),
                'status'         => $status,
                'target'         => array(
                    'width'  => 2000,
                    'height' => 3000,
                ),
                'pairing_slot'   => $pairing['label'],
                'pairing_key'    => $slot_key,
                'expected_title' => isset( $preview['title'] ) && '' !== $preview['title'] ? $preview['title'] : $raw,
                'resolved_title' => isset( $preview['resolved_title'] ) ? (string) $preview['resolved_title'] : '',
                'imdb_title_id'  => isset( $preview['tt'] ) ? (string) $preview['tt'] : '',
                'poster_html'    => isset( $preview['poster_html'] ) ? (string) $preview['poster_html'] : '',
                'poster_deferred' => ! $resolve_posters,
                'warnings'       => isset( $status['warnings'] ) && is_array( $status['warnings'] ) ? $status['warnings'] : array(),
                'raw_value'      => $raw,
            );
        }
    }

    return $rows;
}

function lunara_control_desk_review_retention_signal( $state, $label, $note = '', $meta = array() ) {
    $allowed = array( 'ready', 'watch', 'needs-work', 'neutral' );
    $state   = sanitize_key( (string) $state );

    if ( ! in_array( $state, $allowed, true ) ) {
        $state = 'watch';
    }

    return array_merge(
        array(
            'state' => $state,
            'label' => (string) $label,
            'note'  => (string) $note,
        ),
        is_array( $meta ) ? $meta : array()
    );
}

function lunara_control_desk_review_retention_debrief_signal( $post_id ) {
    $post_id = absint( $post_id );
    if ( $post_id <= 0 ) {
        return lunara_control_desk_review_retention_signal(
            'needs-work',
            __( 'Debrief missing', 'lunara-film' ),
            __( 'No Review post was available for Debrief inspection.', 'lunara-film' )
        );
    }

    $career_context = function_exists( 'lunara_get_career_context_meta' )
        ? lunara_get_career_context_meta( $post_id )
        : get_post_meta( $post_id, '_lunara_career_context', true );

    $fields = array(
        'score'          => get_post_meta( $post_id, '_lunara_score', true ),
        'year'           => get_post_meta( $post_id, '_lunara_year', true ),
        'director'       => get_post_meta( $post_id, '_lunara_director', true ),
        'theme_echo'     => get_post_meta( $post_id, '_lunara_theme_echo', true ),
        'counter_program' => get_post_meta( $post_id, '_lunara_counter_program', true ),
        'career_context' => $career_context,
    );

    $present = array();
    foreach ( $fields as $key => $value ) {
        if ( '' !== trim( (string) $value ) ) {
            $present[] = $key;
        }
    }

    $count = count( $present );
    if ( $count >= 5 ) {
        return lunara_control_desk_review_retention_signal(
            'ready',
            __( 'Debrief ready', 'lunara-film' ),
            __( 'The signature module has enough score, context, and pairing material to feel intentional.', 'lunara-film' ),
            array( 'count' => $count )
        );
    }

    if ( $count > 0 ) {
        return lunara_control_desk_review_retention_signal(
            'watch',
            __( 'Debrief thin', 'lunara-film' ),
            __( 'Some Debrief material exists, but the module may read light against the review body.', 'lunara-film' ),
            array( 'count' => $count )
        );
    }

    return lunara_control_desk_review_retention_signal(
        'needs-work',
        __( 'Debrief missing', 'lunara-film' ),
        __( 'No Debrief source fields are filled for this Review.', 'lunara-film' ),
        array( 'count' => 0 )
    );
}

function lunara_control_desk_review_retention_pairing_signal( $post_id ) {
    $post_id = absint( $post_id );
    if ( $post_id <= 0 ) {
        return lunara_control_desk_review_retention_signal(
            'needs-work',
            __( 'Pair It With missing', 'lunara-film' ),
            __( 'No Review post was available for Pair It With inspection.', 'lunara-film' )
        );
    }

    $pairings = array(
        'theme_echo'      => get_post_meta( $post_id, '_lunara_theme_echo', true ),
        'counter_program' => get_post_meta( $post_id, '_lunara_counter_program', true ),
        'career_context'  => function_exists( 'lunara_get_career_context_meta' ) ? lunara_get_career_context_meta( $post_id ) : get_post_meta( $post_id, '_lunara_career_context', true ),
    );

    $present  = 0;
    $warnings = array();

    foreach ( $pairings as $raw ) {
        $raw = trim( (string) $raw );
        if ( '' === $raw ) {
            continue;
        }

        $present++;
        if ( function_exists( 'lunara_parse_pair_it_with_value' ) ) {
            $preview = lunara_parse_pair_it_with_value( $raw, $post_id, false );
            $status  = function_exists( 'lunara_control_desk_review_pairing_source_status' )
                ? lunara_control_desk_review_pairing_source_status( $preview, false )
                : array();

            if ( isset( $status['state'] ) && 'ready' !== $status['state'] ) {
                $warnings[] = isset( $status['label'] ) ? (string) $status['label'] : __( 'Needs source review', 'lunara-film' );
            }

            if ( isset( $status['warnings'] ) && is_array( $status['warnings'] ) ) {
                $warnings = array_merge( $warnings, $status['warnings'] );
            }
        } else {
            $warnings[] = __( 'Pair It With parser unavailable.', 'lunara-film' );
        }
    }

    $warnings = array_values( array_unique( array_filter( array_map( 'trim', $warnings ) ) ) );

    if ( ! empty( $warnings ) ) {
        return lunara_control_desk_review_retention_signal(
            'needs-work',
            __( 'Pairing needs source review', 'lunara-film' ),
            __( 'One or more Pair It With entries needs IMDb/source cleanup before the package is trusted.', 'lunara-film' ),
            array(
                'count'    => $present,
                'warnings' => $warnings,
            )
        );
    }

    if ( $present >= 3 ) {
        return lunara_control_desk_review_retention_signal(
            'ready',
            __( 'Pair It With ready', 'lunara-film' ),
            __( 'All three companion sources are present and parse cleanly.', 'lunara-film' ),
            array( 'count' => $present )
        );
    }

    if ( $present > 0 ) {
        return lunara_control_desk_review_retention_signal(
            'watch',
            __( 'Pair It With partial', 'lunara-film' ),
            __( 'At least one companion source exists, but the lane is not a complete three-part package.', 'lunara-film' ),
            array( 'count' => $present )
        );
    }

    return lunara_control_desk_review_retention_signal(
        'needs-work',
        __( 'Pair It With missing', 'lunara-film' ),
        __( 'No Theme Echo, Counter-Program, or Career Context companion is set.', 'lunara-film' ),
        array( 'count' => 0 )
    );
}

function lunara_control_desk_review_retention_overall_state( $signals ) {
    $signals = is_array( $signals ) ? $signals : array();
    $states  = array();

    foreach ( $signals as $signal ) {
        if ( ! is_array( $signal ) || empty( $signal['state'] ) || 'neutral' === $signal['state'] ) {
            continue;
        }

        $states[] = sanitize_key( (string) $signal['state'] );
    }

    if ( in_array( 'needs-work', $states, true ) ) {
        return lunara_control_desk_review_retention_signal(
            'needs-work',
            __( 'Needs Work', 'lunara-film' ),
            __( 'At least one retention module needs attention before this Review package is fully trusted.', 'lunara-film' )
        );
    }

    if ( in_array( 'watch', $states, true ) ) {
        return lunara_control_desk_review_retention_signal(
            'watch',
            __( 'Watch', 'lunara-film' ),
            __( 'The package is usable, but one or more retention signals should be reviewed.', 'lunara-film' )
        );
    }

    return lunara_control_desk_review_retention_signal(
        'ready',
        __( 'Ready', 'lunara-film' ),
        __( 'The visible retention package has the core signals expected of a polished Review.', 'lunara-film' )
    );
}

function lunara_control_desk_review_retention_row( $post ) {
    $post = get_post( $post );
    if ( ! $post || 'review' !== $post->post_type ) {
        return array();
    }

    $post_id        = absint( $post->ID );
    $related_target = max( 1, absint( get_theme_mod( 'lunara_review_related_count', 4 ) ) );
    $related_count  = 0;

    if ( function_exists( 'lunara_get_related_review_posts' ) ) {
        $related_query = lunara_get_related_review_posts( $post_id, $related_target );
        if ( $related_query instanceof WP_Query ) {
            $related_count = is_array( $related_query->posts ) ? count( $related_query->posts ) : 0;
        }
    }

    $review_tt = function_exists( 'lunara_get_review_imdb_title_id' ) ? lunara_get_review_imdb_title_id( $post_id ) : '';
    $ledger    = ( '' !== $review_tt && function_exists( 'lunara_get_oscar_ledger_counts' ) ) ? lunara_get_oscar_ledger_counts( $review_tt ) : array( 'noms' => 0, 'wins' => 0 );
    $noms      = isset( $ledger['noms'] ) ? absint( $ledger['noms'] ) : 0;
    $wins      = isset( $ledger['wins'] ) ? absint( $ledger['wins'] ) : 0;

    $signals = array(
        'trailer' => ( function_exists( 'lunara_post_has_trailer' ) && lunara_post_has_trailer( $post_id ) )
            ? lunara_control_desk_review_retention_signal( 'ready', __( 'Trailer ready', 'lunara-film' ), __( 'A supported trailer embed is attached.', 'lunara-film' ) )
            : lunara_control_desk_review_retention_signal( 'watch', __( 'No trailer', 'lunara-film' ), __( 'No supported trailer is attached to this Review.', 'lunara-film' ) ),
        'spoiler' => ( function_exists( 'lunara_is_full_spoiler_review' ) && lunara_is_full_spoiler_review( $post_id ) )
            ? lunara_control_desk_review_retention_signal( 'ready', __( 'Full spoiler review', 'lunara-film' ), __( 'This Review is explicitly marked as a full-spoiler package.', 'lunara-film' ) )
            : ( function_exists( 'lunara_get_linked_spoiler_review' ) && ! empty( lunara_get_linked_spoiler_review( $post_id ) )
                ? lunara_control_desk_review_retention_signal( 'ready', __( 'Spoiler bridge ready', 'lunara-film' ), __( 'This Review links readers to a full-spoiler companion.', 'lunara-film' ) )
                : lunara_control_desk_review_retention_signal( 'watch', __( 'No spoiler bridge', 'lunara-film' ), __( 'No full-spoiler companion is linked yet.', 'lunara-film' ) ) ),
        'debrief' => lunara_control_desk_review_retention_debrief_signal( $post_id ),
        'pairing' => lunara_control_desk_review_retention_pairing_signal( $post_id ),
        'related' => $related_count >= $related_target
            ? lunara_control_desk_review_retention_signal( 'ready', __( 'Related reviews ready', 'lunara-film' ), sprintf( __( '%1$d related Reviews available.', 'lunara-film' ), absint( $related_count ) ) )
            : ( $related_count > 0
                ? lunara_control_desk_review_retention_signal( 'watch', __( 'Related reviews light', 'lunara-film' ), sprintf( __( '%1$d of %2$d target related Reviews available.', 'lunara-film' ), absint( $related_count ), absint( $related_target ) ) )
                : lunara_control_desk_review_retention_signal( 'needs-work', __( 'Related reviews missing', 'lunara-film' ), __( 'No related Review fallback is available.', 'lunara-film' ) ) ),
        'oscar'   => ( $noms + $wins ) > 0
            ? lunara_control_desk_review_retention_signal( 'ready', __( 'Oscar Ledger linked', 'lunara-film' ), sprintf( __( '%1$d nominations and %2$d wins are available.', 'lunara-film' ), absint( $noms ), absint( $wins ) ) )
            : ( '' !== $review_tt
                ? lunara_control_desk_review_retention_signal( 'watch', __( 'No Oscar match', 'lunara-film' ), __( 'An IMDb title ID exists, but no Oscar Ledger nominations or wins are matched.', 'lunara-film' ) )
                : lunara_control_desk_review_retention_signal( 'neutral', __( 'No Oscar signal', 'lunara-film' ), __( 'No IMDb title ID is set for Oscar Ledger matching.', 'lunara-film' ) ) ),
    );

    $signals['overall'] = lunara_control_desk_review_retention_overall_state( $signals );

    return array(
        'post_id'      => $post_id,
        'title'        => get_the_title( $post ),
        'post_status'  => (string) get_post_status( $post ),
        'status_label' => function_exists( 'lunara_control_desk_post_status_label' ) ? lunara_control_desk_post_status_label( $post ) : get_post_status( $post ),
        'modified'     => get_the_modified_date( '', $post ),
        'edit_url'     => get_edit_post_link( $post_id, '' ),
        'view_url'     => get_permalink( $post_id ),
        'trailer'      => $signals['trailer'],
        'spoiler'      => $signals['spoiler'],
        'debrief'      => $signals['debrief'],
        'pairing'      => $signals['pairing'],
        'related'      => $signals['related'],
        'oscar'        => $signals['oscar'],
        'overall'      => $signals['overall'],
    );
}

function lunara_control_desk_review_retention_rows( $limit = 16 ) {
    $limit = max( 1, min( 48, absint( $limit ) ) );
    $rows  = array();

    $posts = get_posts(
        array(
            'post_type'      => 'review',
            'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
            'posts_per_page' => $limit,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        )
    );

    foreach ( $posts as $post ) {
        $row = lunara_control_desk_review_retention_row( $post );
        if ( ! empty( $row ) ) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function lunara_control_desk_render_review_retention_chip( $label, $signal ) {
    $signal = is_array( $signal ) ? $signal : array();
    $state  = isset( $signal['state'] ) ? sanitize_key( (string) $signal['state'] ) : 'watch';
    $state  = '' !== $state ? $state : 'watch';
    $note   = isset( $signal['note'] ) ? (string) $signal['note'] : '';
    ?>
    <span class="lunara-control-desk-retention-chip lunara-control-desk-retention-chip--<?php echo esc_attr( $state ); ?>">
        <strong><?php echo esc_html( $label ); ?></strong>
        <b><?php echo esc_html( isset( $signal['label'] ) ? $signal['label'] : $state ); ?></b>
        <?php if ( '' !== trim( $note ) ) : ?>
            <small><?php echo esc_html( $note ); ?></small>
        <?php endif; ?>
    </span>
    <?php
}

function lunara_control_desk_render_review_retention_console() {
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        ?>
        <section id="lunara-theme-studio-review-retention-health" class="lunara-control-desk-homepage-studio lunara-control-desk-retention-health">
            <div class="lunara-control-desk-panel-header">
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Review Retention Health', 'lunara-film' ); ?></p>
                <h3><?php esc_html_e( 'Retention package audit requires theme editing permission', 'lunara-film' ); ?></h3>
            </div>
        </section>
        <?php
        return;
    }

    $rows = lunara_control_desk_review_retention_rows( 16 );
    ?>
    <section id="lunara-theme-studio-review-retention-health" class="lunara-control-desk-homepage-studio lunara-control-desk-retention-health">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Review Retention Health', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Retention package audit', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Read-only scan of trailers, spoiler bridges, Debrief, Pair It With, related Reviews, and Oscar Ledger signals before public polish continues.', 'lunara-film' ); ?></p>
        </div>

        <?php if ( empty( $rows ) ) : ?>
            <div class="lunara-control-desk-empty">
                <strong><?php esc_html_e( 'No recent Review posts found', 'lunara-film' ); ?></strong>
                <span><?php esc_html_e( 'Create or import Reviews before running this retention package audit.', 'lunara-film' ); ?></span>
            </div>
        <?php else : ?>
            <div class="lunara-control-desk-retention-list">
                <?php foreach ( $rows as $row ) : ?>
                    <article class="lunara-control-desk-retention-row">
                        <div class="lunara-control-desk-retention-row-head">
                            <div>
                                <p class="lunara-control-desk-kicker"><?php echo esc_html( $row['status_label'] ); ?></p>
                                <h4><?php echo esc_html( $row['title'] ); ?></h4>
                                <span><?php echo esc_html( sprintf( __( 'Updated %s', 'lunara-film' ), $row['modified'] ) ); ?></span>
                            </div>
                            <div class="lunara-control-desk-actions">
                                <a class="button" href="<?php echo esc_url( get_edit_post_link( $row['post_id'], '' ) ); ?>"><?php esc_html_e( 'Edit Review', 'lunara-film' ); ?></a>
                                <a class="button" href="<?php echo esc_url( get_permalink( $row['post_id'] ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'lunara-film' ); ?></a>
                            </div>
                        </div>
                        <div class="lunara-control-desk-retention-overall">
                            <?php lunara_control_desk_render_review_retention_chip( __( 'Overall', 'lunara-film' ), $row['overall'] ); ?>
                        </div>
                        <div class="lunara-control-desk-retention-grid">
                            <?php lunara_control_desk_render_review_retention_chip( __( 'Trailer', 'lunara-film' ), $row['trailer'] ); ?>
                            <?php lunara_control_desk_render_review_retention_chip( __( 'Spoiler', 'lunara-film' ), $row['spoiler'] ); ?>
                            <?php lunara_control_desk_render_review_retention_chip( __( 'Debrief', 'lunara-film' ), $row['debrief'] ); ?>
                            <?php lunara_control_desk_render_review_retention_chip( __( 'Pair It With', 'lunara-film' ), $row['pairing'] ); ?>
                            <?php lunara_control_desk_render_review_retention_chip( __( 'Related reviews', 'lunara-film' ), $row['related'] ); ?>
                            <?php lunara_control_desk_render_review_retention_chip( __( 'Oscar Ledger', 'lunara-film' ), $row['oscar'] ); ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
}

function lunara_control_desk_image_quality_rows( $surface, $limit = 8, $options = array() ) {
    $targets = lunara_control_desk_image_quality_targets();
    $surface = sanitize_key( $surface );
    $options = is_array( $options ) ? $options : array();
    $limit   = max( 1, min( 48, absint( $limit ) ) );
    $rows    = array();

    if ( 'reviews' === $surface ) {
        $target = $targets['review-card'];
        $posts  = get_posts(
            array(
                'post_type'      => 'review',
                'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
                'posts_per_page' => $limit,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'no_found_rows'  => true,
            )
        );

        foreach ( $posts as $post ) {
            $source = lunara_control_desk_review_card_source( $post->ID );
            $state  = lunara_control_desk_image_quality_state( $source['attachment_id'], $target, $source['external_url'] );
            $state  = lunara_control_desk_image_quality_apply_accepted_state( $post->ID, 'review-card', $state );
            $rows[] = array(
                'surface'       => __( 'Review card', 'lunara-film' ),
                'surface_key'   => 'review-card',
                'title'         => get_the_title( $post ),
                'post_id'       => absint( $post->ID ),
                'post_status'   => (string) get_post_status( $post ),
                'status_label'  => lunara_control_desk_post_status_label( $post ),
                'edit_url'      => get_edit_post_link( $post->ID, '' ),
                'view_url'      => get_permalink( $post ),
                'media_url'     => $source['attachment_id'] ? get_edit_post_link( $source['attachment_id'], '' ) : '',
                'attachment_id' => $source['attachment_id'],
                'source_label'  => $source['source_label'],
                'status'        => $state,
                'target'        => $target,
            );
        }
    } elseif ( 'journal' === $surface ) {
        $target = $targets['hero'];
        $posts  = get_posts(
            array(
                'post_type'      => 'journal',
                'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
                'posts_per_page' => $limit,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'no_found_rows'  => true,
            )
        );

        foreach ( $posts as $post ) {
            $attachment_id = has_post_thumbnail( $post->ID ) ? absint( get_post_thumbnail_id( $post->ID ) ) : 0;
            $state         = lunara_control_desk_image_quality_state( $attachment_id, $target );
            $state         = lunara_control_desk_image_quality_apply_accepted_state( $post->ID, 'journal-hero', $state );
            $rows[]        = array(
                'surface'       => __( 'Journal hero', 'lunara-film' ),
                'surface_key'   => 'journal-hero',
                'title'         => get_the_title( $post ),
                'post_id'       => absint( $post->ID ),
                'post_status'   => (string) get_post_status( $post ),
                'status_label'  => lunara_control_desk_post_status_label( $post ),
                'edit_url'      => get_edit_post_link( $post->ID, '' ),
                'view_url'      => get_permalink( $post ),
                'media_url'     => $attachment_id ? get_edit_post_link( $attachment_id, '' ) : '',
                'attachment_id' => $attachment_id,
                'source_label'  => $attachment_id ? __( 'Featured image', 'lunara-film' ) : __( 'No featured image', 'lunara-film' ),
                'status'        => $state,
                'target'        => $target,
            );
        }
    } elseif ( 'oscar-facts' === $surface ) {
        $target = $targets['oscar-fact'];
        $posts  = get_posts(
            array(
                'post_type'      => 'oscar_fact',
                'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
                'posts_per_page' => $limit,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'no_found_rows'  => true,
            )
        );

        foreach ( $posts as $post ) {
            $attachment_id   = has_post_thumbnail( $post->ID ) ? absint( get_post_thumbnail_id( $post->ID ) ) : 0;
            $visual_verified = '1' === (string) get_post_meta( $post->ID, '_lunara_fact_visual_verified', true );
            $visual_treatment = 'archival' === (string) get_post_meta( $post->ID, '_lunara_fact_visual_treatment', true ) ? 'archival' : 'wide';
            $visual_focus    = function_exists( 'lunara_sanitize_oscar_fact_visual_focus' ) ? lunara_sanitize_oscar_fact_visual_focus( get_post_meta( $post->ID, '_lunara_fact_visual_focus', true ) ) : 'center';
            $state           = lunara_control_desk_oscar_fact_visual_state( $attachment_id, $target, $visual_verified, $visual_treatment );
            $rows[]          = array(
                'surface'         => __( 'Oscar Fact visual', 'lunara-film' ),
                'surface_key'     => 'oscar-fact',
                'title'           => get_the_title( $post ),
                'post_id'         => absint( $post->ID ),
                'post_status'     => (string) get_post_status( $post ),
                'status_label'    => lunara_control_desk_post_status_label( $post ),
                'edit_url'        => get_edit_post_link( $post->ID, '' ),
                'view_url'        => get_permalink( $post ),
                'media_url'       => $attachment_id ? get_edit_post_link( $attachment_id, '' ) : '',
                'attachment_id'   => $attachment_id,
                'source_label'    => $attachment_id ? ( $visual_verified ? ( 'archival' === $visual_treatment ? __( 'Verified archival image', 'lunara-film' ) : __( 'Verified featured image', 'lunara-film' ) ) : __( 'Featured image, hidden until verified', 'lunara-film' ) ) : __( 'No featured image', 'lunara-film' ),
                'status'          => $state,
                'target'          => $target,
                'visual_verified' => $visual_verified,
                'visual_treatment' => $visual_treatment,
                'visual_focus'    => $visual_focus,
            );
        }
    } elseif ( 'review-pairings' === $surface ) {
        $rows = lunara_control_desk_review_pairing_source_rows( $limit, ! empty( $options['resolve_posters'] ) );
    }

    return $rows;
}

function lunara_control_desk_review_archive_image_quality_rows( $limit = 200 ) {
    $targets = lunara_control_desk_image_quality_targets();
    $target  = $targets['review-card'];
    $limit   = max( 1, min( 300, absint( $limit ) ) );
    $rows    = array();
    $posts   = get_posts(
        array(
            'post_type'      => 'review',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        )
    );

    foreach ( $posts as $post ) {
        $source = lunara_control_desk_review_card_source( $post->ID );
        $state  = lunara_control_desk_image_quality_state( $source['attachment_id'], $target, $source['external_url'] );
        $state  = lunara_control_desk_image_quality_apply_accepted_state( $post->ID, 'review-card', $state );
        $rows[] = array(
            'surface'       => __( 'Review card', 'lunara-film' ),
            'surface_key'   => 'review-card',
            'title'         => get_the_title( $post ),
            'post_id'       => absint( $post->ID ),
            'post_status'   => (string) get_post_status( $post ),
            'status_label'  => lunara_control_desk_post_status_label( $post ),
            'edit_url'      => get_edit_post_link( $post->ID, '' ),
            'view_url'      => get_permalink( $post ),
            'media_url'     => $source['attachment_id'] ? get_edit_post_link( $source['attachment_id'], '' ) : '',
            'attachment_id' => $source['attachment_id'],
            'source_label'  => $source['source_label'],
            'status'        => $state,
            'target'        => $target,
        );
    }

    return $rows;
}

function lunara_control_desk_image_quality_summary_cards( $rows, $scope = 'recent' ) {
    $ready = 0;
    $weak  = 0;
    $scope = sanitize_key( $scope );

    foreach ( $rows as $row ) {
        $state = isset( $row['status']['state'] ) ? (string) $row['status']['state'] : 'weak';
        if ( 'ready' === $state ) {
            $ready++;
        } else {
            $weak++;
        }
    }

    return array(
        array(
            'label' => __( 'Sources checked', 'lunara-film' ),
            'value' => (string) count( $rows ),
            'state' => 'ready',
            'note'  => 'review-archive' === $scope ? __( 'All published Review archive card sources inspected from WordPress metadata.', 'lunara-film' ) : __( 'Recent Review, Journal, and Oscar Fact media inspected from WordPress metadata, including drafts.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'At target', 'lunara-film' ),
            'value' => (string) $ready,
            'state' => 'ready',
            'note'  => __( 'Meets the current Lunara source-size rule.', 'lunara-film' ),
        ),
        array(
            'label' => __( 'Needs attention', 'lunara-film' ),
            'value' => (string) $weak,
            'state' => $weak ? 'weak' : 'ready',
            'note'  => __( 'Missing, external, or below target source files.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_image_quality_filters() {
    $scope      = lunara_control_desk_get_request_key( 'lcd_iq_scope', 'recent' );
    $surface     = lunara_control_desk_get_request_key( 'lcd_iq_surface', 'all' );
    $post_status = lunara_control_desk_get_request_key( 'lcd_iq_status', 'all' );
    $state       = lunara_control_desk_get_request_key( 'lcd_iq_state', 'all' );
    $fact_state  = lunara_control_desk_get_request_key( 'lcd_iq_fact_state', 'all' );

    if ( ! in_array( $scope, array( 'recent', 'review-archive' ), true ) ) {
        $scope = 'recent';
    }

    if ( ! in_array( $surface, array( 'all', 'editorial', 'review-card', 'journal-hero', 'oscar-fact', 'review-pairing' ), true ) ) {
        $surface = 'all';
    }

    if ( ! in_array( $post_status, array( 'all', 'publish', 'drafts' ), true ) ) {
        $post_status = 'all';
    }

    if ( ! in_array( $state, array( 'all', 'needs', 'ready' ), true ) ) {
        $state = 'all';
    }

    if ( ! in_array( $fact_state, array( 'all', 'verified', 'unverified', 'needs-image' ), true ) ) {
        $fact_state = 'all';
    }

    return array(
        'scope'       => $scope,
        'surface'     => $surface,
        'post_status' => $post_status,
        'state'       => $state,
        'fact_state'  => $fact_state,
    );
}

function lunara_control_desk_image_quality_row_matches_filters( $row, $filters ) {
    $surface     = isset( $filters['surface'] ) ? sanitize_key( $filters['surface'] ) : 'all';
    $post_status = isset( $filters['post_status'] ) ? sanitize_key( $filters['post_status'] ) : 'all';
    $state       = isset( $filters['state'] ) ? sanitize_key( $filters['state'] ) : 'all';
    $fact_state  = isset( $filters['fact_state'] ) ? sanitize_key( $filters['fact_state'] ) : 'all';
    $row_surface = isset( $row['surface_key'] ) ? sanitize_key( $row['surface_key'] ) : '';
    $row_status  = isset( $row['post_status'] ) ? sanitize_key( $row['post_status'] ) : '';
    $row_state   = isset( $row['status']['state'] ) ? sanitize_key( $row['status']['state'] ) : 'weak';

    if ( 'editorial' === $surface && ! in_array( $row_surface, array( 'review-card', 'journal-hero', 'review-pairing' ), true ) ) {
        return false;
    }

    if ( 'all' !== $surface && 'editorial' !== $surface && $row_surface !== $surface ) {
        return false;
    }

    if ( 'publish' === $post_status && 'publish' !== $row_status ) {
        return false;
    }

    if ( 'drafts' === $post_status && ! in_array( $row_status, array( 'draft', 'pending', 'future' ), true ) ) {
        return false;
    }

    if ( 'ready' === $state && 'ready' !== $row_state ) {
        return false;
    }

    if ( 'needs' === $state && 'ready' === $row_state ) {
        return false;
    }

    if ( 'all' !== $fact_state ) {
        if ( 'oscar-fact' !== $row_surface ) {
            return false;
        }

        $has_fact_image    = ! empty( $row['attachment_id'] );
        $is_fact_verified  = ! empty( $row['visual_verified'] );
        $fact_state_match  = false;

        if ( 'verified' === $fact_state ) {
            $fact_state_match = $has_fact_image && $is_fact_verified;
        } elseif ( 'unverified' === $fact_state ) {
            $fact_state_match = $has_fact_image && ! $is_fact_verified;
        } elseif ( 'needs-image' === $fact_state ) {
            $fact_state_match = ! $has_fact_image;
        }

        if ( ! $fact_state_match ) {
            return false;
        }
    }

    return true;
}

function lunara_control_desk_filter_image_quality_rows( $rows, $filters ) {
    return array_values(
        array_filter(
            $rows,
            function ( $row ) use ( $filters ) {
                return lunara_control_desk_image_quality_row_matches_filters( $row, $filters );
            }
        )
    );
}

function lunara_control_desk_image_quality_filter_url( $filters, $overrides = array() ) {
    $filters = array_merge(
        array(
            'scope'       => 'recent',
            'surface'     => 'all',
            'post_status' => 'all',
            'state'       => 'all',
            'fact_state'  => 'all',
        ),
        is_array( $filters ) ? $filters : array(),
        is_array( $overrides ) ? $overrides : array()
    );

    $args = array(
        'tab' => 'theme-studio',
    );

    if ( 'recent' !== $filters['scope'] ) {
        $args['lcd_iq_scope'] = sanitize_key( $filters['scope'] );
    }

    if ( 'all' !== $filters['surface'] ) {
        $args['lcd_iq_surface'] = sanitize_key( $filters['surface'] );
    }

    if ( 'all' !== $filters['post_status'] ) {
        $args['lcd_iq_status'] = sanitize_key( $filters['post_status'] );
    }

    if ( 'all' !== $filters['state'] ) {
        $args['lcd_iq_state'] = sanitize_key( $filters['state'] );
    }

    if ( 'all' !== $filters['fact_state'] ) {
        $args['lcd_iq_fact_state'] = sanitize_key( $filters['fact_state'] );
    }

    return lunara_control_desk_url( $args ) . '#lunara-theme-studio-image-quality';
}

function lunara_control_desk_image_quality_filter_count( $rows, $filters, $overrides = array() ) {
    $candidate_filters = array_merge( $filters, $overrides );

    return count( lunara_control_desk_filter_image_quality_rows( $rows, $candidate_filters ) );
}

function lunara_control_desk_render_image_quality_filter_link( $rows, $filters, $group_key, $value, $label, $note ) {
    $overrides = array( $group_key => $value );

    if ( 'surface' === $group_key ) {
        $overrides['fact_state'] = 'all';
        if ( 'review-card' !== $value ) {
            $overrides['scope'] = 'recent';
        }
    }

    $active = isset( $filters[ $group_key ] ) && $filters[ $group_key ] === $value;
    $count  = lunara_control_desk_image_quality_filter_count( $rows, $filters, $overrides );
    ?>
    <a
        class="lunara-control-desk-image-filter<?php echo $active ? ' is-active' : ''; ?>"
        href="<?php echo esc_url( lunara_control_desk_image_quality_filter_url( $filters, $overrides ) ); ?>"
    >
        <strong><?php echo esc_html( $label ); ?></strong>
        <span><?php echo esc_html( $note ); ?></span>
        <em><?php echo esc_html( $count ); ?></em>
    </a>
    <?php
}

function lunara_control_desk_render_image_quality_filters( $rows, $filters ) {
    $visible_count           = count( lunara_control_desk_filter_image_quality_rows( $rows, $filters ) );
    $fact_lane_rows          = lunara_control_desk_image_quality_rows( 'oscar-facts', 48 );
    $resolve_pairing_posters = isset( $filters['surface'] ) && 'review-pairing' === sanitize_key( $filters['surface'] );
    $pairing_lane_rows       = lunara_control_desk_image_quality_rows(
        'review-pairings',
        $resolve_pairing_posters ? 48 : 16,
        array(
            'resolve_posters' => $resolve_pairing_posters,
        )
    );
    $fact_lane_reset = array(
        'scope'       => 'recent',
        'surface'     => 'oscar-fact',
        'post_status' => 'all',
        'state'       => 'all',
    );
    ?>
    <div class="lunara-control-desk-image-filter-panel" aria-label="<?php echo esc_attr__( 'Image quality filters', 'lunara-film' ); ?>">
        <div class="lunara-control-desk-card-head">
            <div>
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Filter Rail', 'lunara-film' ); ?></p>
                <h4><?php esc_html_e( 'Turn the audit into a cleanup queue', 'lunara-film' ); ?></h4>
                <p class="lunara-control-desk-subtle">
                    <?php
                    echo esc_html(
                        sprintf(
                            /* translators: %d: visible row count. */
                            __( '%d rows visible in the current queue.', 'lunara-film' ),
                            absint( $visible_count )
                        )
                    );
                    ?>
                </p>
            </div>
            <div class="lunara-control-desk-actions">
                <a class="button button-small" href="<?php echo esc_url( lunara_control_desk_image_quality_filter_url( array() ) ); ?>"><?php esc_html_e( 'Reset Filters', 'lunara-film' ); ?></a>
            </div>
        </div>
        <div class="lunara-control-desk-image-filter-grid">
            <section>
                <h5><?php esc_html_e( 'Publication state', 'lunara-film' ); ?></h5>
                <?php
                lunara_control_desk_render_image_quality_filter_link( $rows, $filters, 'post_status', 'all', __( 'All', 'lunara-film' ), __( 'Published and working queue.', 'lunara-film' ) );
                lunara_control_desk_render_image_quality_filter_link( $rows, $filters, 'post_status', 'publish', __( 'Published', 'lunara-film' ), __( 'Public-facing image gaps first.', 'lunara-film' ) );
                lunara_control_desk_render_image_quality_filter_link( $rows, $filters, 'post_status', 'drafts', __( 'Drafts', 'lunara-film' ), __( 'Draft, pending, and scheduled work.', 'lunara-film' ) );
                ?>
            </section>
            <section>
                <h5><?php esc_html_e( 'Readiness', 'lunara-film' ); ?></h5>
                <?php
                lunara_control_desk_render_image_quality_filter_link( $rows, $filters, 'state', 'all', __( 'All', 'lunara-film' ), __( 'Every inspected source.', 'lunara-film' ) );
                lunara_control_desk_render_image_quality_filter_link( $rows, $filters, 'state', 'needs', __( 'Needs attention', 'lunara-film' ), __( 'Missing, external, or below target.', 'lunara-film' ) );
                lunara_control_desk_render_image_quality_filter_link( $rows, $filters, 'state', 'ready', __( 'Ready', 'lunara-film' ), __( 'Meets the source rule.', 'lunara-film' ) );
                ?>
            </section>
            <section>
                <h5><?php esc_html_e( 'Surface', 'lunara-film' ); ?></h5>
                <?php
                lunara_control_desk_render_image_quality_filter_link( $rows, $filters, 'surface', 'all', __( 'All surfaces', 'lunara-film' ), __( 'Review cards, Pair It With sources, Journal heroes, and Oscar Facts.', 'lunara-film' ) );
                lunara_control_desk_render_image_quality_filter_link( $rows, $filters, 'surface', 'editorial', __( 'Editorial surfaces', 'lunara-film' ), __( 'Reviews, Pair It With, and Journal.', 'lunara-film' ) );
                lunara_control_desk_render_image_quality_filter_link( $rows, $filters, 'surface', 'review-card', __( 'Review cards', 'lunara-film' ), __( 'Archive, homepage, and card art.', 'lunara-film' ) );
                lunara_control_desk_render_image_quality_filter_link( $rows, $filters, 'surface', 'review-pairing', __( 'Pairing sources', 'lunara-film' ), __( 'Pair It With poster and IMDb locks.', 'lunara-film' ) );
                lunara_control_desk_render_image_quality_filter_link( $rows, $filters, 'surface', 'journal-hero', __( 'Journal heroes', 'lunara-film' ), __( 'Wide editorial image sources.', 'lunara-film' ) );
                lunara_control_desk_render_image_quality_filter_link( $rows, $filters, 'surface', 'oscar-fact', __( 'Oscar Facts', 'lunara-film' ), __( 'Homepage fact carousel visuals.', 'lunara-film' ) );
                ?>
            </section>
            <section>
                <h5><?php esc_html_e( 'Oscar Fact state', 'lunara-film' ); ?></h5>
                <a
                    class="lunara-control-desk-image-filter<?php echo ( 'recent' === $filters['scope'] && 'oscar-fact' === $filters['surface'] && 'all' === $filters['fact_state'] ) ? ' is-active' : ''; ?>"
                    href="<?php echo esc_url( lunara_control_desk_image_quality_filter_url( $filters, array_merge( $fact_lane_reset, array( 'fact_state' => 'all' ) ) ) ); ?>"
                >
                    <strong><?php esc_html_e( 'All facts', 'lunara-film' ); ?></strong>
                    <span><?php esc_html_e( 'Every inspected Oscar Fact visual.', 'lunara-film' ); ?></span>
                    <em><?php echo esc_html( lunara_control_desk_image_quality_filter_count( $fact_lane_rows, array(), array_merge( $fact_lane_reset, array( 'fact_state' => 'all' ) ) ) ); ?></em>
                </a>
                <?php
                $fact_filter_options = array(
                    'verified'    => array(
                        'label' => __( 'Verified', 'lunara-film' ),
                        'note'  => __( 'Approved to appear with image.', 'lunara-film' ),
                    ),
                    'unverified'  => array(
                        'label' => __( 'Unverified', 'lunara-film' ),
                        'note'  => __( 'Has image, held from public display.', 'lunara-film' ),
                    ),
                    'needs-image' => array(
                        'label' => __( 'Needs image', 'lunara-film' ),
                        'note'  => __( 'No featured image selected.', 'lunara-film' ),
                    ),
                );

                foreach ( $fact_filter_options as $fact_value => $fact_option ) :
                    $fact_overrides = array_merge(
                        $fact_lane_reset,
                        array(
                            'fact_state' => $fact_value,
                        )
                    );
                    ?>
                    <a
                        class="lunara-control-desk-image-filter<?php echo ( 'recent' === $filters['scope'] && 'oscar-fact' === $filters['surface'] && $fact_value === $filters['fact_state'] ) ? ' is-active' : ''; ?>"
                        href="<?php echo esc_url( lunara_control_desk_image_quality_filter_url( $filters, $fact_overrides ) ); ?>"
                    >
                        <strong><?php echo esc_html( $fact_option['label'] ); ?></strong>
                        <span><?php echo esc_html( $fact_option['note'] ); ?></span>
                        <em><?php echo esc_html( lunara_control_desk_image_quality_filter_count( $fact_lane_rows, array(), $fact_overrides ) ); ?></em>
                    </a>
                    <?php
                endforeach;
                ?>
            </section>
        </div>
        <div class="lunara-control-desk-image-priority-lanes">
            <a href="<?php echo esc_url( lunara_control_desk_image_quality_filter_url( $filters, array( 'surface' => 'editorial', 'post_status' => 'publish', 'state' => 'needs', 'fact_state' => 'all' ) ) ); ?>">
                <strong><?php esc_html_e( 'Editorial gaps', 'lunara-film' ); ?></strong>
                <span><?php echo esc_html( lunara_control_desk_image_quality_filter_count( $rows, $filters, array( 'surface' => 'editorial', 'post_status' => 'publish', 'state' => 'needs', 'fact_state' => 'all' ) ) ); ?></span>
            </a>
            <a href="<?php echo esc_url( lunara_control_desk_image_quality_filter_url( $filters, array( 'scope' => 'review-archive', 'surface' => 'review-card', 'post_status' => 'publish', 'state' => 'needs', 'fact_state' => 'all' ) ) ); ?>">
                <strong><?php esc_html_e( 'Review archive backlog', 'lunara-film' ); ?></strong>
                <span><?php echo esc_html( lunara_control_desk_image_quality_filter_count( lunara_control_desk_review_archive_image_quality_rows(), array(), array( 'surface' => 'review-card', 'post_status' => 'publish', 'state' => 'needs', 'fact_state' => 'all' ) ) ); ?></span>
            </a>
            <a href="<?php echo esc_url( lunara_control_desk_image_quality_filter_url( $filters, array( 'scope' => 'recent', 'surface' => 'review-pairing', 'post_status' => 'publish', 'state' => 'needs', 'fact_state' => 'all' ) ) ); ?>">
                <strong><?php esc_html_e( 'Pairing source backlog', 'lunara-film' ); ?></strong>
                <span><?php echo esc_html( lunara_control_desk_image_quality_filter_count( $pairing_lane_rows, array(), array( 'surface' => 'review-pairing', 'post_status' => 'publish', 'state' => 'needs', 'fact_state' => 'all' ) ) ); ?></span>
            </a>
            <a href="<?php echo esc_url( lunara_control_desk_image_quality_filter_url( $filters, array( 'post_status' => 'publish', 'state' => 'needs', 'fact_state' => 'all' ) ) ); ?>">
                <strong><?php esc_html_e( 'Published gaps', 'lunara-film' ); ?></strong>
                <span><?php echo esc_html( lunara_control_desk_image_quality_filter_count( $rows, $filters, array( 'post_status' => 'publish', 'state' => 'needs', 'fact_state' => 'all' ) ) ); ?></span>
            </a>
            <a href="<?php echo esc_url( lunara_control_desk_image_quality_filter_url( $filters, array( 'post_status' => 'drafts', 'state' => 'needs', 'fact_state' => 'all' ) ) ); ?>">
                <strong><?php esc_html_e( 'Draft cleanup', 'lunara-film' ); ?></strong>
                <span><?php echo esc_html( lunara_control_desk_image_quality_filter_count( $rows, $filters, array( 'post_status' => 'drafts', 'state' => 'needs', 'fact_state' => 'all' ) ) ); ?></span>
            </a>
            <a href="<?php echo esc_url( lunara_control_desk_image_quality_filter_url( $filters, array( 'post_status' => 'publish', 'state' => 'ready', 'fact_state' => 'all' ) ) ); ?>">
                <strong><?php esc_html_e( 'Published ready', 'lunara-film' ); ?></strong>
                <span><?php echo esc_html( lunara_control_desk_image_quality_filter_count( $rows, $filters, array( 'post_status' => 'publish', 'state' => 'ready', 'fact_state' => 'all' ) ) ); ?></span>
            </a>
            <a href="<?php echo esc_url( lunara_control_desk_image_quality_filter_url( $filters, array( 'scope' => 'recent', 'surface' => 'oscar-fact', 'post_status' => 'publish', 'state' => 'needs', 'fact_state' => 'all' ) ) ); ?>">
                <strong><?php esc_html_e( 'Fact visuals', 'lunara-film' ); ?></strong>
                <span><?php echo esc_html( lunara_control_desk_image_quality_filter_count( $fact_lane_rows, array(), array( 'surface' => 'oscar-fact', 'post_status' => 'publish', 'state' => 'needs', 'fact_state' => 'all' ) ) ); ?></span>
            </a>
            <a href="<?php echo esc_url( lunara_control_desk_image_quality_filter_url( $filters, array_merge( $fact_lane_reset, array( 'fact_state' => 'verified' ) ) ) ); ?>">
                <strong><?php esc_html_e( 'Verified facts', 'lunara-film' ); ?></strong>
                <span><?php echo esc_html( lunara_control_desk_image_quality_filter_count( $fact_lane_rows, array(), array_merge( $fact_lane_reset, array( 'fact_state' => 'verified' ) ) ) ); ?></span>
            </a>
            <a href="<?php echo esc_url( lunara_control_desk_image_quality_filter_url( $filters, array_merge( $fact_lane_reset, array( 'fact_state' => 'unverified' ) ) ) ); ?>">
                <strong><?php esc_html_e( 'Unverified facts', 'lunara-film' ); ?></strong>
                <span><?php echo esc_html( lunara_control_desk_image_quality_filter_count( $fact_lane_rows, array(), array_merge( $fact_lane_reset, array( 'fact_state' => 'unverified' ) ) ) ); ?></span>
            </a>
            <a href="<?php echo esc_url( lunara_control_desk_image_quality_filter_url( $filters, array_merge( $fact_lane_reset, array( 'fact_state' => 'needs-image' ) ) ) ); ?>">
                <strong><?php esc_html_e( 'Needs image', 'lunara-film' ); ?></strong>
                <span><?php echo esc_html( lunara_control_desk_image_quality_filter_count( $fact_lane_rows, array(), array_merge( $fact_lane_reset, array( 'fact_state' => 'needs-image' ) ) ) ); ?></span>
            </a>
        </div>
    </div>
    <?php
}

function lunara_control_desk_render_image_quality_targets() {
    ?>
    <div class="lunara-control-desk-image-targets" aria-label="<?php echo esc_attr__( 'Lunara image targets', 'lunara-film' ); ?>">
        <?php foreach ( lunara_control_desk_image_quality_targets() as $target ) : ?>
            <article>
                <p class="lunara-control-desk-kicker"><?php echo esc_html( $target['ratio'] ); ?></p>
                <h4><?php echo esc_html( $target['label'] ); ?></h4>
                <strong><?php echo esc_html( sprintf( '%1$d x %2$d', absint( $target['width'] ), absint( $target['height'] ) ) ); ?></strong>
                <code><?php echo esc_html( $target['size'] ); ?></code>
                <span><?php echo esc_html( $target['surface'] ); ?></span>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}

function lunara_control_desk_render_oscar_fact_visual_preview( $attachment_id, $visual_focus ) {
    $attachment_id = absint( $attachment_id );

    if ( ! $attachment_id ) {
        return;
    }

    $image_url = wp_get_attachment_image_url( $attachment_id, 'medium_large' );

    if ( ! $image_url ) {
        $image_url = wp_get_attachment_url( $attachment_id );
    }

    if ( ! $image_url ) {
        return;
    }

    $focus_css = function_exists( 'lunara_oscar_fact_visual_focus_css' ) ? lunara_oscar_fact_visual_focus_css( $visual_focus ) : 'center center';
    $wide_img  = wp_get_attachment_image(
        $attachment_id,
        'medium_large',
        false,
        array(
            'class'    => 'lunara-control-desk-oscar-fact-preview-img',
            'loading'  => 'lazy',
            'decoding' => 'async',
        )
    );
    $archival_img = wp_get_attachment_image(
        $attachment_id,
        'medium_large',
        false,
        array(
            'class'    => 'lunara-control-desk-oscar-fact-preview-img',
            'loading'  => 'lazy',
            'decoding' => 'async',
        )
    );

    if ( ! $wide_img || ! $archival_img ) {
        return;
    }

    $style = sprintf(
        '--lunara-admin-fact-image: url(%1$s); --lunara-admin-fact-focus: %2$s;',
        esc_url_raw( $image_url ),
        $focus_css
    );
    ?>
    <div class="lunara-control-desk-oscar-fact-preview" style="<?php echo esc_attr( $style ); ?>" aria-label="<?php echo esc_attr__( 'Oscar Fact visual preview', 'lunara-film' ); ?>">
        <strong><?php esc_html_e( 'Public framing preview', 'lunara-film' ); ?></strong>
        <div class="lunara-control-desk-oscar-fact-preview-grid">
            <figure class="lunara-control-desk-oscar-fact-preview-frame is-wide">
                <span><?php esc_html_e( 'Wide crop', 'lunara-film' ); ?></span>
                <?php echo $wide_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </figure>
            <figure class="lunara-control-desk-oscar-fact-preview-frame is-archival">
                <span><?php esc_html_e( 'Archival fit', 'lunara-film' ); ?></span>
                <?php echo $archival_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </figure>
        </div>
    </div>
    <?php
}

function lunara_control_desk_oscar_fact_focus_picker_order() {
    return array(
        'left-high',
        'center-high',
        'right-high',
        'left',
        'center',
        'right',
        'left-low',
        'center-low',
        'right-low',
    );
}

function lunara_control_desk_render_oscar_fact_focus_picker( $visual_focus, $visual_focus_options ) {
    $focus_order = lunara_control_desk_oscar_fact_focus_picker_order();
    ?>
    <div class="lunara-control-desk-oscar-fact-focus-picker" role="radiogroup" aria-label="<?php echo esc_attr__( 'Public crop focus', 'lunara-film' ); ?>">
        <?php foreach ( $focus_order as $focus_key ) : ?>
            <?php if ( empty( $visual_focus_options[ $focus_key ] ) ) : ?>
                <?php continue; ?>
            <?php endif; ?>
            <?php $focus_label = isset( $visual_focus_options[ $focus_key ]['label'] ) ? $visual_focus_options[ $focus_key ]['label'] : $focus_key; ?>
            <label class="lunara-control-desk-oscar-fact-focus-option is-<?php echo esc_attr( $focus_key ); ?>">
                <input type="radio" name="lunara_image_source_visual_focus" value="<?php echo esc_attr( $focus_key ); ?>" <?php checked( $visual_focus, $focus_key ); ?> />
                <span><?php echo esc_html( $focus_label ); ?></span>
            </label>
        <?php endforeach; ?>
    </div>
    <?php
}

function lunara_control_desk_render_image_source_control( $row ) {
    $post_id       = isset( $row['post_id'] ) ? absint( $row['post_id'] ) : 0;
    $surface       = isset( $row['surface_key'] ) ? sanitize_key( $row['surface_key'] ) : '';
    $attachment_id = isset( $row['attachment_id'] ) ? absint( $row['attachment_id'] ) : 0;
    $visual_ok     = ! empty( $row['visual_verified'] );
    $visual_treatment = isset( $row['visual_treatment'] ) && 'archival' === $row['visual_treatment'] ? 'archival' : 'wide';
    $visual_focus  = isset( $row['visual_focus'] ) && function_exists( 'lunara_sanitize_oscar_fact_visual_focus' ) ? lunara_sanitize_oscar_fact_visual_focus( $row['visual_focus'] ) : 'center';
    $visual_focus_options = function_exists( 'lunara_oscar_fact_visual_focus_options' ) ? lunara_oscar_fact_visual_focus_options() : array(
        'center' => array(
            'label' => __( 'Center', 'lunara-film' ),
            'css'   => 'center center',
        ),
    );

    if ( 'review-pairing' === $surface ) {
        return;
    }

    $surfaces      = lunara_control_desk_image_source_surfaces();
    $status        = isset( $row['status'] ) && is_array( $row['status'] ) ? $row['status'] : array();
    $accept_meta_key = lunara_control_desk_image_quality_accept_meta_key( $surface );
    $accept_available = $attachment_id && $accept_meta_key && ! empty( $status['reason'] ) && 'near-target' === $status['reason'];
    $accept_checked = $accept_available && ( ! empty( $status['accepted'] ) || get_post_meta( $post_id, $accept_meta_key, true ) );

    if ( ! $post_id || empty( $surfaces[ $surface ] ) || ! current_user_can( 'edit_theme_options' ) || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $dimensions = lunara_control_desk_image_quality_attachment_dimensions( $attachment_id );
    $thumb_html = $attachment_id ? wp_get_attachment_image(
        $attachment_id,
        'thumbnail',
        false,
        array(
            'class'    => 'lunara-control-desk-image-source-thumb-img',
            'loading'  => 'lazy',
            'decoding' => 'async',
        )
    ) : '';
    $title      = $attachment_id ? get_the_title( $attachment_id ) : __( 'No replacement selected', 'lunara-film' );
    $meta       = $attachment_id
        ? sprintf(
            /* translators: 1: attachment id, 2: width, 3: height. */
            __( 'Attachment #%1$d / %2$d x %3$d', 'lunara-film' ),
            $attachment_id,
            absint( $dimensions['width'] ),
            absint( $dimensions['height'] )
        )
        : __( 'Choose a Media Library image, then save this row.', 'lunara-film' );
    ?>
    <form
        id="<?php echo esc_attr( 'lunara-image-source-' . $surface . '-' . $post_id ); ?>"
        class="lunara-control-desk-image-source-form"
        method="post"
        action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
        data-lunara-image-source-control
    >
        <input type="hidden" name="action" value="lunara_save_image_source" />
        <input type="hidden" name="lunara_image_source_post_id" value="<?php echo esc_attr( $post_id ); ?>" />
        <input type="hidden" name="lunara_image_source_surface" value="<?php echo esc_attr( $surface ); ?>" />
        <input type="hidden" name="lunara_image_source_attachment_id" value="<?php echo esc_attr( $attachment_id ); ?>" data-lunara-image-source-input />
        <?php wp_nonce_field( 'lunara_save_image_source', 'lunara_image_source_nonce' ); ?>
        <div class="lunara-control-desk-image-source-preview<?php echo $attachment_id ? ' is-ready' : ' is-empty'; ?>" data-lunara-image-source-preview>
            <span class="lunara-control-desk-image-source-thumb">
                <?php if ( $thumb_html ) : ?>
                    <?php echo $thumb_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endif; ?>
            </span>
            <span>
                <strong data-lunara-image-source-title><?php echo esc_html( $title ); ?></strong>
                <em data-lunara-image-source-meta><?php echo esc_html( $meta ); ?></em>
            </span>
        </div>
        <div class="lunara-control-desk-image-source-actions">
            <button
                type="button"
                class="button button-small"
                data-lunara-image-source-picker
                data-title="<?php echo esc_attr( sprintf( __( 'Choose %s', 'lunara-film' ), $surfaces[ $surface ]['label'] ) ); ?>"
                data-button="<?php esc_attr_e( 'Use this image', 'lunara-film' ); ?>"
            ><?php echo esc_html( $attachment_id ? __( 'Replace Source', 'lunara-film' ) : __( 'Choose Source', 'lunara-film' ) ); ?></button>
            <button type="submit" class="button button-primary button-small"><?php esc_html_e( 'Save Source', 'lunara-film' ); ?></button>
            <button type="button" class="button button-small" data-lunara-image-source-clear><?php esc_html_e( 'Clear', 'lunara-film' ); ?></button>
        </div>
        <?php if ( $accept_available ) : ?>
            <label class="lunara-control-desk-image-source-verify">
                <input type="checkbox" name="lunara_image_source_accept_near_target" value="1" <?php checked( $accept_checked ); ?> />
                <span>
                    <strong><?php esc_html_e( 'Accept near-target source', 'lunara-film' ); ?></strong>
                    <em><?php esc_html_e( 'Use only when this exact image is visually faithful and no stronger source is available.', 'lunara-film' ); ?></em>
                </span>
            </label>
        <?php endif; ?>
        <?php if ( 'oscar-fact' === $surface ) : ?>
            <?php lunara_control_desk_render_oscar_fact_visual_preview( $attachment_id, $visual_focus ); ?>
            <div class="lunara-control-desk-image-source-framing" aria-label="<?php echo esc_attr__( 'Oscar Fact public framing controls', 'lunara-film' ); ?>">
                <label class="lunara-control-desk-image-source-verify">
                    <input type="checkbox" name="lunara_image_source_visual_verified" value="1" <?php checked( $visual_ok ); ?> />
                    <span>
                        <strong><?php esc_html_e( 'Verified public visual', 'lunara-film' ); ?></strong>
                        <em><?php esc_html_e( 'Only check this after the image truly matches the fact. The homepage carousel hides unverified fact images.', 'lunara-film' ); ?></em>
                    </span>
                </label>
                <label class="lunara-control-desk-image-source-verify">
                    <span>
                        <strong><?php esc_html_e( 'Public visual treatment', 'lunara-film' ); ?></strong>
                        <select name="lunara_image_source_visual_treatment">
                            <option value="wide" <?php selected( $visual_treatment, 'wide' ); ?>><?php esc_html_e( 'Wide carousel crop', 'lunara-film' ); ?></option>
                            <option value="archival" <?php selected( $visual_treatment, 'archival' ); ?>><?php esc_html_e( 'Archival fit', 'lunara-film' ); ?></option>
                        </select>
                        <em><?php esc_html_e( 'Use Archival fit for portraits, ceremony stills, and exact source images that should stay intact instead of being cropped.', 'lunara-film' ); ?></em>
                    </span>
                </label>
                <label class="lunara-control-desk-image-source-verify">
                    <span>
                        <strong><?php esc_html_e( 'Public crop focus', 'lunara-film' ); ?></strong>
                        <?php lunara_control_desk_render_oscar_fact_focus_picker( $visual_focus, $visual_focus_options ); ?>
                        <em><?php esc_html_e( 'Use this to keep faces, groups, or key image action in frame when the public card crops wide.', 'lunara-film' ); ?></em>
                    </span>
                </label>
            </div>
        <?php endif; ?>
    </form>
    <?php
}

function lunara_control_desk_render_pairing_source_row( $row ) {
    $status      = isset( $row['status'] ) && is_array( $row['status'] ) ? $row['status'] : array();
    $state       = isset( $status['state'] ) ? sanitize_html_class( $status['state'] ) : 'weak';
    $warnings    = isset( $row['warnings'] ) && is_array( $row['warnings'] ) ? $row['warnings'] : array();
    $poster_html = isset( $row['poster_html'] ) ? trim( (string) $row['poster_html'] ) : '';
    $deferred    = ! empty( $row['poster_deferred'] );
    ?>
    <article class="lunara-control-desk-image-row lunara-control-desk-pairing-source-row is-<?php echo esc_attr( $state ); ?>">
        <div class="lunara-control-desk-pairing-source-preview" aria-label="<?php echo esc_attr__( 'Pairing source audit', 'lunara-film' ); ?>">
            <?php if ( '' !== $poster_html ) : ?>
                <?php echo wp_kses_post( $poster_html ); ?>
            <?php elseif ( $deferred ) : ?>
                <span><?php esc_html_e( 'Poster preview deferred', 'lunara-film' ); ?></span>
            <?php else : ?>
                <span><?php esc_html_e( 'No poster', 'lunara-film' ); ?></span>
            <?php endif; ?>
        </div>
        <div class="lunara-control-desk-image-row-main">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Pairing source audit', 'lunara-film' ); ?></p>
            <h4><?php echo esc_html( isset( $row['title'] ) ? $row['title'] : __( 'Untitled Review', 'lunara-film' ) ); ?></h4>
            <div class="lunara-control-desk-pairing-source-meta">
                <span><strong><?php esc_html_e( 'Slot', 'lunara-film' ); ?></strong><?php echo esc_html( isset( $row['pairing_slot'] ) ? $row['pairing_slot'] : '' ); ?></span>
                <span><strong><?php esc_html_e( 'Expected', 'lunara-film' ); ?></strong><?php echo esc_html( isset( $row['expected_title'] ) ? $row['expected_title'] : '' ); ?></span>
                <span><strong><?php esc_html_e( 'IMDb', 'lunara-film' ); ?></strong><?php echo esc_html( ! empty( $row['imdb_title_id'] ) ? $row['imdb_title_id'] : __( 'Missing', 'lunara-film' ) ); ?></span>
                <span><strong><?php esc_html_e( 'Resolved', 'lunara-film' ); ?></strong><?php echo esc_html( ! empty( $row['resolved_title'] ) ? $row['resolved_title'] : __( 'Unknown', 'lunara-film' ) ); ?></span>
                <span><strong><?php esc_html_e( 'Post status', 'lunara-film' ); ?></strong><?php echo esc_html( isset( $row['status_label'] ) ? $row['status_label'] : __( 'Unknown', 'lunara-film' ) ); ?></span>
            </div>
            <p class="lunara-control-desk-image-note">
                <strong><?php echo esc_html( isset( $status['label'] ) ? $status['label'] : __( 'Needs source review', 'lunara-film' ) ); ?></strong>
                <?php echo esc_html( isset( $status['note'] ) ? $status['note'] : '' ); ?>
            </p>
            <?php if ( ! empty( $warnings ) ) : ?>
                <ul class="lunara-control-desk-pairing-source-warnings">
                    <?php foreach ( $warnings as $warning ) : ?>
                        <li><?php echo esc_html( $warning ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ( ! empty( $row['raw_value'] ) ) : ?>
                <p class="lunara-control-desk-pairing-source-raw">
                    <strong><?php esc_html_e( 'Raw field', 'lunara-film' ); ?></strong>
                    <span><?php echo esc_html( $row['raw_value'] ); ?></span>
                </p>
            <?php endif; ?>
            <div class="lunara-control-desk-actions">
                <?php if ( ! empty( $row['edit_url'] ) ) : ?>
                    <a class="button button-small button-primary" href="<?php echo esc_url( $row['edit_url'] ); ?>"><?php esc_html_e( 'Edit Review', 'lunara-film' ); ?></a>
                <?php endif; ?>
                <?php if ( ! empty( $row['view_url'] ) ) : ?>
                    <a class="button button-small" href="<?php echo esc_url( $row['view_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Review', 'lunara-film' ); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
}

function lunara_control_desk_render_image_quality_row( $row ) {
    if ( isset( $row['surface_key'] ) && 'review-pairing' === sanitize_key( $row['surface_key'] ) ) {
        lunara_control_desk_render_pairing_source_row( $row );
        return;
    }

    $status        = isset( $row['status'] ) && is_array( $row['status'] ) ? $row['status'] : array();
    $state         = isset( $status['state'] ) ? sanitize_html_class( $status['state'] ) : 'weak';
    $dimensions    = isset( $status['dimensions'] ) && is_array( $status['dimensions'] ) ? $status['dimensions'] : array();
    $width         = isset( $dimensions['width'] ) ? absint( $dimensions['width'] ) : 0;
    $height        = isset( $dimensions['height'] ) ? absint( $dimensions['height'] ) : 0;
    $target        = isset( $row['target'] ) && is_array( $row['target'] ) ? $row['target'] : array();
    $attachment_id = isset( $row['attachment_id'] ) ? absint( $row['attachment_id'] ) : 0;
    $thumb_html    = $attachment_id ? wp_get_attachment_image(
        $attachment_id,
        'thumbnail',
        false,
        array(
            'class'    => 'lunara-control-desk-image-thumb-img',
            'loading'  => 'lazy',
            'decoding' => 'async',
        )
    ) : '';
    ?>
    <article class="lunara-control-desk-image-row is-<?php echo esc_attr( $state ); ?>">
        <div class="lunara-control-desk-image-thumb">
            <?php if ( $thumb_html ) : ?>
                <?php echo $thumb_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php else : ?>
                <span><?php esc_html_e( 'No image', 'lunara-film' ); ?></span>
            <?php endif; ?>
        </div>
        <div class="lunara-control-desk-image-row-main">
            <p class="lunara-control-desk-kicker"><?php echo esc_html( isset( $row['surface'] ) ? $row['surface'] : '' ); ?></p>
            <h4><?php echo esc_html( isset( $row['title'] ) ? $row['title'] : __( 'Untitled', 'lunara-film' ) ); ?></h4>
            <div class="lunara-control-desk-image-row-meta">
                <span><strong><?php esc_html_e( 'Source', 'lunara-film' ); ?></strong><?php echo esc_html( isset( $row['source_label'] ) ? $row['source_label'] : '' ); ?></span>
                <span><strong><?php esc_html_e( 'Post status', 'lunara-film' ); ?></strong><?php echo esc_html( isset( $row['status_label'] ) ? $row['status_label'] : __( 'Unknown', 'lunara-film' ) ); ?></span>
                <span><strong><?php esc_html_e( 'Current', 'lunara-film' ); ?></strong><?php echo esc_html( $width && $height ? sprintf( '%1$d x %2$d', $width, $height ) : __( 'Unknown', 'lunara-film' ) ); ?></span>
                <span><strong><?php esc_html_e( 'Target', 'lunara-film' ); ?></strong><?php echo esc_html( sprintf( '%1$d x %2$d', absint( $target['width'] ?? 0 ), absint( $target['height'] ?? 0 ) ) ); ?></span>
            </div>
            <p class="lunara-control-desk-image-note">
                <strong><?php echo esc_html( isset( $status['label'] ) ? $status['label'] : __( 'Needs attention', 'lunara-film' ) ); ?></strong>
                <?php echo esc_html( isset( $status['note'] ) ? $status['note'] : '' ); ?>
            </p>
            <?php lunara_control_desk_render_image_source_control( $row ); ?>
            <div class="lunara-control-desk-actions">
                <?php if ( ! empty( $row['edit_url'] ) ) : ?>
                    <a class="button button-small button-primary" href="<?php echo esc_url( $row['edit_url'] ); ?>"><?php esc_html_e( 'Edit Post', 'lunara-film' ); ?></a>
                <?php endif; ?>
                <?php if ( ! empty( $row['view_url'] ) ) : ?>
                    <a class="button button-small" href="<?php echo esc_url( $row['view_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'lunara-film' ); ?></a>
                <?php endif; ?>
                <?php if ( ! empty( $row['media_url'] ) ) : ?>
                    <a class="button button-small" href="<?php echo esc_url( $row['media_url'] ); ?>"><?php esc_html_e( 'Open Media', 'lunara-film' ); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
}

function lunara_control_desk_render_image_quality_group( $label, $rows, $empty_message = '' ) {
    ?>
    <section class="lunara-control-desk-image-group">
        <div class="lunara-control-desk-card-head">
            <div>
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Source Audit', 'lunara-film' ); ?></p>
                <h3><?php echo esc_html( $label ); ?></h3>
            </div>
        </div>
        <div class="lunara-control-desk-image-row-list">
            <?php if ( empty( $rows ) ) : ?>
                <p class="lunara-control-desk-subtle"><?php echo esc_html( $empty_message ? $empty_message : __( 'No recent items found for this surface.', 'lunara-film' ) ); ?></p>
            <?php else : ?>
                <?php foreach ( $rows as $row ) : ?>
                    <?php lunara_control_desk_render_image_quality_row( $row ); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

function lunara_control_desk_render_image_quality_console() {
    $filters                 = lunara_control_desk_image_quality_filters();
    $scope                   = isset( $filters['scope'] ) ? sanitize_key( $filters['scope'] ) : 'recent';
    $resolve_pairing_posters = isset( $filters['surface'] ) && 'review-pairing' === sanitize_key( $filters['surface'] );
    $review_rows             = lunara_control_desk_image_quality_rows( 'reviews', 16 );
    $review_archive_rows     = lunara_control_desk_review_archive_image_quality_rows();
    $pairing_rows            = lunara_control_desk_image_quality_rows(
        'review-pairings',
        $resolve_pairing_posters ? 48 : 16,
        array(
            'resolve_posters' => $resolve_pairing_posters,
        )
    );
    $journal_rows            = lunara_control_desk_image_quality_rows( 'journal', 16 );
    $fact_rows               = lunara_control_desk_image_quality_rows( 'oscar-facts', 48 );

    if ( 'review-archive' === $scope ) {
        $all_rows     = $review_archive_rows;
        $filtered     = lunara_control_desk_filter_image_quality_rows( $all_rows, $filters );
        $review_rows  = $filtered;
        $pairing_rows = array();
        $journal_rows = array();
        $fact_rows    = array();
    } else {
        $all_rows     = array_merge( $review_rows, $pairing_rows, $journal_rows, $fact_rows );
        $filtered     = lunara_control_desk_filter_image_quality_rows( $all_rows, $filters );
        $review_rows  = lunara_control_desk_filter_image_quality_rows( $review_rows, $filters );
        $pairing_rows = lunara_control_desk_filter_image_quality_rows( $pairing_rows, $filters );
        $journal_rows = lunara_control_desk_filter_image_quality_rows( $journal_rows, $filters );
        $fact_rows    = lunara_control_desk_filter_image_quality_rows( $fact_rows, $filters );
    }
    ?>
    <section id="lunara-theme-studio-image-quality" class="lunara-control-desk-image-console">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Image Quality Console', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'Find the soft source before it reaches the public surface', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'The renderer already requests the large Lunara sizes; this console checks whether the selected source file is good enough to survive that treatment.', 'lunara-film' ); ?></p>
        </div>
        <?php lunara_control_desk_render_status_cards( lunara_control_desk_image_quality_summary_cards( $filtered, $scope ) ); ?>
        <?php lunara_control_desk_render_image_quality_filters( $all_rows, $filters ); ?>
        <?php lunara_control_desk_render_image_quality_targets(); ?>
        <div class="lunara-control-desk-image-groups">
            <?php if ( 'review-archive' === $scope ) : ?>
                <?php lunara_control_desk_render_image_quality_group( __( 'Published Review archive backlog', 'lunara-film' ), $review_rows, __( 'No published Review archive rows match the current filters.', 'lunara-film' ) ); ?>
            <?php else : ?>
                <?php lunara_control_desk_render_image_quality_group( __( 'Recent review card sources', 'lunara-film' ), $review_rows, __( 'No review card rows match the current filters.', 'lunara-film' ) ); ?>
                <?php lunara_control_desk_render_image_quality_group( __( 'Pair It With sources', 'lunara-film' ), $pairing_rows, __( 'No Pair It With source rows match the current filters.', 'lunara-film' ) ); ?>
                <?php lunara_control_desk_render_image_quality_group( __( 'Recent Journal hero sources', 'lunara-film' ), $journal_rows, __( 'No Journal hero rows match the current filters.', 'lunara-film' ) ); ?>
                <?php lunara_control_desk_render_image_quality_group( __( 'Oscar Facts carousel visuals', 'lunara-film' ), $fact_rows, __( 'No Oscar Fact rows match the current filters.', 'lunara-film' ) ); ?>
            <?php endif; ?>
        </div>
        <div class="lunara-control-desk-image-footer">
            <div>
                <strong><?php esc_html_e( 'Cleanup order', 'lunara-film' ); ?></strong>
                <span><?php esc_html_e( 'Start with Published gaps, curate Oscar Facts visuals for the homepage carousel, then extend this same source-control grammar into Oscars poster chambers.', 'lunara-film' ); ?></span>
            </div>
            <div class="lunara-control-desk-actions">
                <a class="button" href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Reviews Archive', 'lunara-film' ); ?></a>
                <a class="button" href="<?php echo esc_url( home_url( '/journal/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Journal Archive', 'lunara-film' ); ?></a>
                <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=oscar_fact' ) ); ?>"><?php esc_html_e( 'Oscar Facts', 'lunara-film' ); ?></a>
                <a class="button" href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>"><?php esc_html_e( 'Media Library', 'lunara-film' ); ?></a>
            </div>
        </div>
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
            'label'    => __( 'Utility Search', 'lunara-film' ),
            'section'  => 'lunara_utility_search_studio',
            'preview'  => home_url( '/?s=sinners' ),
            'owner'    => __( 'Search and recovery templates', 'lunara-film' ),
            'note'     => __( 'Search results, Oscar direct matches, no-results recovery, and 404 route rhythm.', 'lunara-film' ),
            'renders'  => array( __( 'Search results', 'lunara-film' ), __( 'Oscar direct matches', 'lunara-film' ), __( 'No-results recovery', 'lunara-film' ), __( '404 recovery', 'lunara-film' ) ),
            'settings' => array( 'lunara_utility_search_preset', 'lunara_utility_search_density', 'lunara_utility_result_treatment', 'lunara_utility_result_media', 'lunara_utility_recovery_prominence', 'lunara_utility_search_lead_focus', 'lunara_utility_search_spotlight_type', 'lunara_utility_reentry_primary', 'lunara_utility_section_gap', 'lunara_utility_result_min_height', 'lunara_utility_card_grid_min' ),
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
        'lunara_utility_search_preset'           => array(
            'label' => __( 'Utility preset', 'lunara-film' ),
            'hint'  => __( 'Records the last saved Utility Search package marker.', 'lunara-film' ),
        ),
        'lunara_utility_search_density'          => array(
            'label' => __( 'Utility density', 'lunara-film' ),
            'hint'  => __( 'Tunes the compact, editorial, or showcase rhythm for Search and 404.', 'lunara-film' ),
        ),
        'lunara_utility_result_treatment'        => array(
            'label' => __( 'Utility result treatment', 'lunara-film' ),
            'hint'  => __( 'Controls whether Search results read as list, cards, or a spotlight run.', 'lunara-film' ),
        ),
        'lunara_utility_result_media'            => array(
            'label' => __( 'Utility media treatment', 'lunara-film' ),
            'hint'  => __( 'Controls how assertively utility result cards use image chambers.', 'lunara-film' ),
        ),
        'lunara_utility_recovery_prominence'     => array(
            'label' => __( 'Recovery prominence', 'lunara-film' ),
            'hint'  => __( 'Tunes no-results and 404 recovery weight.', 'lunara-film' ),
        ),
        'lunara_utility_search_lead_focus'       => array(
            'label' => __( 'Search lead focus', 'lunara-film' ),
            'hint'  => __( 'Chooses whether Search leads with the ledger, Reviews, or Journal/editorial results.', 'lunara-film' ),
        ),
        'lunara_utility_search_spotlight_type'   => array(
            'label' => __( 'Search spotlight type', 'lunara-film' ),
            'hint'  => __( 'Chooses which result type gets first-card emphasis when available.', 'lunara-film' ),
        ),
        'lunara_utility_reentry_primary'         => array(
            'label' => __( 'Recovery primary route', 'lunara-film' ),
            'hint'  => __( 'Moves the chosen 404/recovery path into the primary re-entry position.', 'lunara-film' ),
        ),
        'lunara_utility_section_gap'             => array(
            'label' => __( 'Utility section rhythm', 'lunara-film' ),
            'hint'  => __( 'Controls spacing between Search/404 modules.', 'lunara-film' ),
        ),
        'lunara_utility_result_min_height'       => array(
            'label' => __( 'Utility result height', 'lunara-film' ),
            'hint'  => __( 'Sets the minimum card chamber height for Search result modules.', 'lunara-film' ),
        ),
        'lunara_utility_card_grid_min'           => array(
            'label' => __( 'Utility grid width', 'lunara-film' ),
            'hint'  => __( 'Sets responsive utility result grid width.', 'lunara-film' ),
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

function lunara_control_desk_theme_studio_command_index_items() {
    return array(
        array(
            'label'              => __( 'Brand Console', 'lunara-film' ),
            'status'             => __( 'Live controls', 'lunara-film' ),
            'surface'            => __( 'Identity', 'lunara-film' ),
            'affects'            => __( 'Header logo, homepage identity mark, site icon, social fallback, and logo geometry.', 'lunara-film' ),
            'anchor'             => '#lunara-theme-studio-brand-console',
            'preview_url'        => home_url( '/' ),
            'mobile_preview_url' => add_query_arg( 'lunara-width', '390', home_url( '/' ) ),
            'next'               => __( 'Next frontier: route-specific brand placement and social preview tuning.', 'lunara-film' ),
        ),
        array(
            'label'              => __( 'Homepage Studio', 'lunara-film' ),
            'status'             => __( 'Live controls', 'lunara-film' ),
            'surface'            => __( 'Curated front door', 'lunara-film' ),
            'affects'            => __( 'Front-door rhythm, route ordering, signature-lane density, visibility shortcuts, and mobile order.', 'lunara-film' ),
            'anchor'             => '#lunara-theme-studio-homepage-studio',
            'preview_url'        => home_url( '/' ),
            'mobile_preview_url' => add_query_arg( 'lunara-width', '390', home_url( '/' ) ),
            'next'               => __( 'Next frontier: carousel behavior and per-lane feature prominence presets.', 'lunara-film' ),
        ),
        array(
            'label'              => __( 'Journal Archive Studio', 'lunara-film' ),
            'status'             => __( 'Live controls', 'lunara-film' ),
            'surface'            => __( 'Live trade desk', 'lunara-film' ),
            'affects'            => __( 'Journal archive density, lead-file prominence, desk rhythm, card height, and wide media chambers.', 'lunara-film' ),
            'anchor'             => '#lunara-theme-studio-journal-archive-studio',
            'preview_url'        => home_url( '/journal/' ),
            'mobile_preview_url' => add_query_arg( 'lunara-width', '390', home_url( '/journal/' ) ),
            'next'               => __( 'Next frontier: Journal single-page media and dispatch-lane control.', 'lunara-film' ),
        ),
        array(
            'label'              => __( 'Reviews Archive Studio', 'lunara-film' ),
            'status'             => __( 'Live controls', 'lunara-film' ),
            'surface'            => __( 'Authority package archive', 'lunara-film' ),
            'affects'            => __( 'Reviews archive density, lead review force, companion rail density, and archive card rhythm.', 'lunara-film' ),
            'anchor'             => '#lunara-theme-studio-reviews-archive-studio',
            'preview_url'        => home_url( '/reviews/' ),
            'mobile_preview_url' => add_query_arg( 'lunara-width', '390', home_url( '/reviews/' ) ),
            'next'               => __( 'Next frontier: Review single-page Debrief, Pair It With, and spoiler bridge controls.', 'lunara-film' ),
        ),
        array(
            'label'              => __( 'Utility Search Studio', 'lunara-film' ),
            'status'             => __( 'Live controls', 'lunara-film' ),
            'surface'            => __( 'Search and recovery', 'lunara-film' ),
            'affects'            => __( 'Search density, result cards, Oscar direct matches, 404 recovery, and no-results routing.', 'lunara-film' ),
            'anchor'             => '#lunara-theme-studio-utility-search-studio',
            'preview_url'        => home_url( '/?s=sinners' ),
            'mobile_preview_url' => add_query_arg( 'lunara-width', '390', home_url( '/?s=sinners' ) ),
            'next'               => __( 'Next frontier: generic archives, contact, and utility retention modules.', 'lunara-film' ),
        ),
        array(
            'label'              => __( 'Image Authority', 'lunara-film' ),
            'status'             => __( 'Quality gate', 'lunara-film' ),
            'surface'            => __( 'Private visual QA', 'lunara-film' ),
            'affects'            => __( 'Review card sources, Journal hero sources, Oscar Fact visuals, near-target approvals, and visual readiness lanes.', 'lunara-film' ),
            'anchor'             => '#lunara-theme-studio-image-quality',
            'preview_url'        => home_url( '/reviews/sinners-2025/' ),
            'mobile_preview_url' => add_query_arg( 'lunara-width', '390', home_url( '/reviews/sinners-2025/' ) ),
            'next'               => __( 'Next frontier: older Oscars poster chambers and route-family image backlog triage.', 'lunara-film' ),
        ),
        array(
            'label'              => __( 'Oscars Dossier Studio', 'lunara-film' ),
            'status'             => __( 'Live controls', 'lunara-film' ),
            'surface'            => __( 'Premium historical ledger', 'lunara-film' ),
            'affects'            => __( 'Ceremony dossiers, Best Picture/category pages, title files, person files, write-up prominence, and major-race rhythm.', 'lunara-film' ),
            'anchor'             => '#lunara-theme-studio-oscars-dossier-studio',
            'preview_url'        => home_url( '/oscars/ceremony/98/' ),
            'mobile_preview_url' => add_query_arg( 'lunara-width', '390', home_url( '/oscars/ceremony/98/' ) ),
            'next'               => __( 'Next frontier: per-route poster and backdrop focus controls plus ceremony major-race editorial packets.', 'lunara-film' ),
        ),
        array(
            'label'              => __( 'Oscar Facts', 'lunara-film' ),
            'status'             => __( 'Signature lane', 'lunara-film' ),
            'surface'            => __( 'Homepage carousel', 'lunara-film' ),
            'affects'            => __( 'Verified public visuals, archival treatment, focus picker, working lanes, and homepage fact-card polish.', 'lunara-film' ),
            'anchor'             => '#lunara-theme-studio-image-quality',
            'preview_url'        => home_url( '/oscars/' ),
            'mobile_preview_url' => add_query_arg( 'lunara-width', '390', home_url( '/oscars/' ) ),
            'next'               => __( 'Next frontier: carousel behavior controls and ceremony-dossier visual treatment.', 'lunara-film' ),
        ),
    );
}

function lunara_control_desk_render_theme_studio_command_index() {
    $items    = lunara_control_desk_theme_studio_command_index_items();
    $base_url = lunara_control_desk_admin_url(
        array(
            'tab' => 'theme-studio',
        )
    );

    if ( ! current_user_can( 'edit_theme_options' ) ) {
        ?>
        <section id="lunara-theme-studio-command-index" class="lunara-control-desk-command-index">
            <div class="lunara-control-desk-panel-header">
                <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Theme Studio Command Index', 'lunara-film' ); ?></p>
                <h3><?php esc_html_e( 'Customization cockpit requires theme editing permission', 'lunara-film' ); ?></h3>
                <p class="lunara-control-desk-subtle"><?php esc_html_e( 'The public surfaces remain available, but direct control navigation is limited to administrators.', 'lunara-film' ); ?></p>
            </div>
        </section>
        <?php
        return;
    }
    ?>
    <section id="lunara-theme-studio-command-index" class="lunara-control-desk-command-index">
        <div class="lunara-control-desk-panel-header">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Theme Studio Command Index', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'The customization cockpit', 'lunara-film' ); ?></h3>
            <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Jump from surface to controls, preview the public route, and keep the next granular layer visible without hunting through panels.', 'lunara-film' ); ?></p>
        </div>
        <div class="lunara-control-desk-command-grid">
            <?php foreach ( $items as $item ) : ?>
                <?php
                $anchor      = isset( $item['anchor'] ) ? (string) $item['anchor'] : '';
                $control_url = $anchor ? $base_url . $anchor : $base_url;
                ?>
                <article class="lunara-control-desk-command-card">
                    <div class="lunara-control-desk-command-card-head">
                        <span><?php echo esc_html( $item['status'] ); ?></span>
                        <em><?php echo esc_html( $item['surface'] ); ?></em>
                    </div>
                    <h4><?php echo esc_html( $item['label'] ); ?></h4>
                    <p><?php echo esc_html( $item['affects'] ); ?></p>
                    <div class="lunara-control-desk-command-next">
                        <strong><?php esc_html_e( 'Next layer', 'lunara-film' ); ?></strong>
                        <span><?php echo esc_html( $item['next'] ); ?></span>
                    </div>
                    <div class="lunara-control-desk-command-actions">
                        <a class="button button-small button-primary" href="<?php echo esc_url( $control_url ); ?>"><?php esc_html_e( 'Open controls', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( $item['preview_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Desktop', 'lunara-film' ); ?></a>
                        <a class="button button-small" href="<?php echo esc_url( $item['mobile_preview_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '390px', 'lunara-film' ); ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
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
    $default_artifact_root = 'C:\\Users\\silve_i21do49\\OneDrive\\Desktop\\New folder\\10_VISUAL_EVIDENCE\\lunara-os-phase1-visual-qa-final';
    $artifact_root         = get_option( 'lunara_control_desk_last_visual_qa_path', $default_artifact_root );

    if ( false !== strpos( (string) $artifact_root, 'Documents\\New project' ) ) {
        $artifact_root = $default_artifact_root;
    }

    $theme_studio_capture = 'C:\\Users\\silve_i21do49\\OneDrive\\Desktop\\New folder\\10_VISUAL_EVIDENCE\\lunara-theme-studio-brand-controls-20260617\\theme-studio-admin-1280.png';
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
            <p class="lunara-control-desk-intro"><?php esc_html_e( 'Brand controls and image-quality checks now sit beside the existing ownership map, with direct storage only where the public site already reads it.', 'lunara-film' ); ?></p>
        </div>
        <?php lunara_control_desk_render_status_cards( lunara_control_desk_theme_studio_summary_cards( $groups ) ); ?>
        <?php lunara_control_desk_render_theme_studio_command_index(); ?>
        <?php lunara_control_desk_render_brand_console(); ?>
        <?php lunara_control_desk_render_homepage_studio(); ?>
        <?php lunara_control_desk_render_journal_archive_studio(); ?>
        <?php lunara_control_desk_render_reviews_archive_studio(); ?>
        <?php lunara_control_desk_render_review_card_image_focus_controls(); ?>
        <?php lunara_control_desk_render_review_single_studio(); ?>
        <?php lunara_control_desk_render_review_retention_console(); ?>
        <?php lunara_control_desk_render_utility_search_studio(); ?>
        <?php lunara_control_desk_render_oscars_dossier_studio(); ?>
        <?php lunara_control_desk_render_image_quality_console(); ?>
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
        'brand_controls_saved' => array(
            'class'   => 'notice-success',
            'message' => __( 'Brand controls saved. Header, homepage, and icon surfaces now read the updated values.', 'lunara-film' ),
        ),
        'brand_controls_invalid_image' => array(
            'class'   => 'notice-error',
            'message' => __( 'Brand controls were not saved because one selected media item was not an image attachment.', 'lunara-film' ),
        ),
        'brand_controls_forbidden' => array(
            'class'   => 'notice-error',
            'message' => __( 'You can view the Control Desk, but changing brand controls requires theme editing permission.', 'lunara-film' ),
        ),
        'homepage_studio_saved' => array(
            'class'   => 'notice-success',
            'message' => __( 'Homepage Studio saved. The front-door rhythm and section shortcuts now read the updated values.', 'lunara-film' ),
        ),
        'homepage_preset_applied' => array(
            'class'   => 'notice-success',
            'message' => __( 'Homepage package applied. The existing front-door controls now match that publication package.', 'lunara-film' ),
        ),
        'homepage_studio_forbidden' => array(
            'class'   => 'notice-error',
            'message' => __( 'You can view the Control Desk, but changing Homepage Studio controls requires theme editing permission.', 'lunara-film' ),
        ),
        'reviews_archive_studio_saved' => array(
            'class'   => 'notice-success',
            'message' => __( 'Reviews Archive Studio saved. The archive density and companion rail now read the updated values.', 'lunara-film' ),
        ),
        'reviews_archive_studio_forbidden' => array(
            'class'   => 'notice-error',
            'message' => __( 'You can view the Control Desk, but changing Reviews Archive Studio controls requires theme editing permission.', 'lunara-film' ),
        ),
        'review_card_image_focus_saved' => array(
            'class'   => 'notice-success',
            'message' => __( 'Review Card Image Focus saved. Review card crops now read the updated focus values.', 'lunara-film' ),
        ),
        'review_card_image_focus_forbidden' => array(
            'class'   => 'notice-error',
            'message' => __( 'You can view the Control Desk, but changing Review Card Image Focus controls requires theme editing permission.', 'lunara-film' ),
        ),
        'review_single_studio_saved' => array(
            'class'   => 'notice-success',
            'message' => __( 'Review Single Studio saved. Single-review package rhythm now reads the updated values.', 'lunara-film' ),
        ),
        'review_single_preset_applied' => array(
            'class'   => 'notice-success',
            'message' => __( 'Review Single Studio preset applied. The selected package now drives the saved single-review rhythm.', 'lunara-film' ),
        ),
        'review_single_studio_forbidden' => array(
            'class'   => 'notice-error',
            'message' => __( 'You can view the Control Desk, but changing Review Single Studio controls requires theme editing permission.', 'lunara-film' ),
        ),
        'utility_search_studio_saved' => array(
            'class'   => 'notice-success',
            'message' => __( 'Utility Search Studio saved. Search, Oscar direct matches, and recovery routes now read the updated values.', 'lunara-film' ),
        ),
        'utility_search_preset_applied' => array(
            'class'   => 'notice-success',
            'message' => __( 'Utility Search Studio preset applied. The selected utility-route package now drives the saved Search and recovery rhythm.', 'lunara-film' ),
        ),
        'utility_search_studio_forbidden' => array(
            'class'   => 'notice-error',
            'message' => __( 'You can view the Control Desk, but changing Utility Search Studio controls requires theme editing permission.', 'lunara-film' ),
        ),
        'oscars_dossier_studio_saved' => array(
            'class'   => 'notice-success',
            'message' => __( 'Oscars Dossier Studio saved. Ceremony, category, title, and person routes now read the updated route-family rhythm.', 'lunara-film' ),
        ),
        'oscars_dossier_preset_applied' => array(
            'class'   => 'notice-success',
            'message' => __( 'Oscars Dossier Studio preset applied. The selected historical-ledger package now drives the saved Oscars rhythm.', 'lunara-film' ),
        ),
        'oscars_dossier_studio_forbidden' => array(
            'class'   => 'notice-error',
            'message' => __( 'You can view the Control Desk, but changing Oscars Dossier Studio controls requires theme editing permission.', 'lunara-film' ),
        ),
        'journal_archive_studio_saved' => array(
            'class'   => 'notice-success',
            'message' => __( 'Journal Archive Studio saved. The live-desk rhythm and archive card geometry now read the updated values.', 'lunara-film' ),
        ),
        'journal_archive_studio_forbidden' => array(
            'class'   => 'notice-error',
            'message' => __( 'You can view the Control Desk, but changing Journal Archive Studio controls requires theme editing permission.', 'lunara-film' ),
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
