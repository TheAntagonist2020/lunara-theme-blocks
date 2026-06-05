<?php
/**
 * Dedicated Oscars landing page.
 */

get_header();

the_post();

$aat                = function_exists( 'lunara_get_oscars_plugin' ) ? lunara_get_oscars_plugin() : null;
$snapshot           = function_exists( 'lunara_get_home_oscars_snapshot' ) ? lunara_get_home_oscars_snapshot() : array();
$database_spotlight = function_exists( 'lunara_get_home_database_spotlight' ) ? lunara_get_home_database_spotlight() : array();
$deep_cuts          = function_exists( 'lunara_get_home_deep_cuts' ) ? lunara_get_home_deep_cuts() : array();
$linked_reviews     = function_exists( 'lunara_oscars_linked_reviews_query' ) ? lunara_oscars_linked_reviews_query( 4 ) : new WP_Query();

$hero_kicker       = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_portal_kicker', 'The Lunara Oscar Ledger' ) : 'The Lunara Oscar Ledger';
$hero_title        = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_portal_title', 'Academy Awards history, treated like a living editorial system.' ) : 'Academy Awards history, treated like a living editorial system.';
$hero_copy         = '';
$explore_kicker    = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_portal_explore_kicker', 'Explore the Portal' ) : 'Explore the Portal';
$explore_heading   = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_portal_explore_heading', 'Start anywhere in the ledger.' ) : 'Start anywhere in the ledger.';
$reviews_heading   = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_portal_reviews_heading', 'Reviews Inside the Ledger' ) : 'Reviews Inside the Ledger';
$deep_cuts_heading = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_portal_deep_cuts_heading', 'Oscar Deep Cuts' ) : 'Oscar Deep Cuts';
$spotlights_heading = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_portal_spotlights_heading', 'Latest Ceremony, category by category.' ) : 'Latest Ceremony, category by category.';
$titles_kicker     = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_portal_titles_kicker', 'Poster-Led Entry Points' ) : 'Poster-Led Entry Points';
$titles_heading    = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_portal_titles_heading', 'Open the ledger through the films themselves.' ) : 'Open the ledger through the films themselves.';
$research_kicker   = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_portal_research_kicker', 'Research Mode' ) : 'Research Mode';
$research_heading  = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_portal_research_heading', 'Open the ledger without leaving the portal.' ) : 'Open the ledger without leaving the portal.';
$research_copy     = '';
$latest_winners_heading = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_latest_winners_heading', 'Latest Ceremony Winners' ) : 'Latest Ceremony Winners';
$latest_winners_link_label = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_latest_winners_link_label', 'Full Ceremony' ) : 'Full Ceremony';
$rotating_kicker   = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_rotating_winners_kicker', 'Oscars Deep Dive' ) : 'Oscars Deep Dive';
$rotating_heading  = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_rotating_winners_heading', 'Ceremony Winners in Rotation' ) : 'Ceremony Winners in Rotation';
$rotating_link_label = function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_rotating_winners_link_label', 'Open This Ceremony' ) : 'Open This Ceremony';

$rotating_enabled  = (bool) get_theme_mod( 'lunara_oscars_rotating_winners_enabled', true );
$rotating_count    = max( 4, min( 16, absint( get_theme_mod( 'lunara_oscars_rotating_winners_count', 10 ) ) ) );
$rotating_autoplay = max( 0, min( 12000, absint( get_theme_mod( 'lunara_oscars_rotating_winners_autoplay', 7200 ) ) ) );
$show_hero           = (bool) get_theme_mod( 'lunara_oscars_show_hero', true );
$show_portal_links   = (bool) get_theme_mod( 'lunara_oscars_show_portal_links', true );
$show_spotlights     = (bool) get_theme_mod( 'lunara_oscars_show_spotlights', true );
$show_title_cards    = (bool) get_theme_mod( 'lunara_oscars_show_title_cards', true );
$show_research       = (bool) get_theme_mod( 'lunara_oscars_show_research', true );
$show_latest_winners = (bool) get_theme_mod( 'lunara_oscars_show_latest_winners', true );
$show_deep_cuts      = (bool) get_theme_mod( 'lunara_oscars_show_deep_cuts', true );
$show_linked_reviews = (bool) get_theme_mod( 'lunara_oscars_show_linked_reviews', false );
$rotating_showcase = ( $rotating_enabled && function_exists( 'lunara_get_rotating_oscars_ceremony_showcase' ) )
    ? lunara_get_rotating_oscars_ceremony_showcase( $rotating_count )
    : array();
$rotating_cards    = is_array( $rotating_showcase['winner_cards'] ?? null ) ? $rotating_showcase['winner_cards'] : array();

$best_picture      = is_array( $snapshot['best_picture'] ?? null ) ? $snapshot['best_picture'] : array();
$best_visual       = is_array( $best_picture['visual'] ?? null ) ? $best_picture['visual'] : array();
$spotlights        = array_slice( (array) ( $snapshot['spotlights'] ?? array() ), 0, 6 );
$title_cards       = array_slice( (array) ( $database_spotlight['cards'] ?? array() ), 0, 5 );

if ( function_exists( 'lunara_normalize_visual_package_image_sizes' ) ) {
    $best_visual = lunara_normalize_visual_package_image_sizes( $best_visual, 'w500', 'w780' );

    foreach ( $spotlights as $spotlight_index => $spotlight ) {
        if ( ! empty( $spotlight['visual'] ) && is_array( $spotlight['visual'] ) ) {
            $spotlights[ $spotlight_index ]['visual'] = lunara_normalize_visual_package_image_sizes( $spotlight['visual'], 'w500', 'w780' );
        }
    }

    foreach ( $title_cards as $card_index => $card ) {
        if ( ! empty( $card['visual'] ) && is_array( $card['visual'] ) ) {
            $title_cards[ $card_index ]['visual'] = lunara_normalize_visual_package_image_sizes( $card['visual'], 'w500', 'w780' );
        }
    }

    foreach ( $rotating_cards as $card_index => $card ) {
        if ( ! empty( $card['_visual'] ) && is_array( $card['_visual'] ) ) {
            $rotating_cards[ $card_index ]['_visual'] = lunara_normalize_visual_package_image_sizes( $card['_visual'], 'w500', 'w780' );
        }
    }
}

$database_url      = trim( (string) ( $snapshot['database_url'] ?? ( $database_spotlight['database_url'] ?? home_url( '/oscars/' ) ) ) );
$categories_url    = trim( (string) ( $snapshot['categories_url'] ?? ( $database_spotlight['categories_url'] ?? home_url( '/oscars/categories/' ) ) ) );
$ceremony_url      = trim( (string) ( $snapshot['ceremony_url'] ?? home_url( '/oscars/ceremony/' ) ) );
$ceremony_label    = trim( (string) ( $snapshot['ceremony_label'] ?? 'Latest Ceremony' ) );
$year_label        = trim( (string) ( $snapshot['year_label'] ?? '' ) );
$research_anchor   = '#oscars-research';
$database_landing_url = remove_query_arg( 'view', $database_url );
$database_table_url   = add_query_arg( 'view', 'table', $database_url );
$database_landing_url = $database_landing_url . $research_anchor;
$database_table_url   = $database_table_url . $research_anchor;
$table_view_requested = isset( $_GET['view'] ) && 'table' === sanitize_key( wp_unslash( $_GET['view'] ) );
$about_url         = ( $aat && method_exists( $aat, 'get_about_url' ) ) ? $aat->get_about_url() : home_url( '/oscars/about/' );
$ceremonies_url    = ( $aat && method_exists( $aat, 'get_ceremonies_index_url' ) ) ? $aat->get_ceremonies_index_url() : home_url( '/oscars/ceremonies/' );
$hero_backdrop_url = trim( (string) ( $best_visual['backdrop_url'] ?? '' ) );
$hero_style        = '';

if ( '' !== $hero_backdrop_url ) {
    $hero_style = "background-image: linear-gradient(120deg, rgba(7,16,27,.92) 0%, rgba(7,16,27,.86) 48%, rgba(7,16,27,.97) 100%), url('" . esc_url( $hero_backdrop_url ) . "'); background-size: cover; background-position: center;";
}

$hero_title_card = array();
if ( ! empty( $best_picture ) ) {
    $hero_title_card = array(
        'title'     => trim( (string) ( $best_picture['film'] ?? '' ) ),
        'url'       => trim( (string) ( $best_picture['film_url'] ?? $ceremony_url ) ),
        'visual'    => $best_visual,
        'eyebrow'   => 'Latest Best Picture',
        'meta_line' => trim( (string) $ceremony_label . ( $year_label !== '' ? ' / ' . $year_label : '' ) ),
        'body'      => trim( (string) ( $snapshot['summary'] ?? $snapshot['winner_record'] ?? '' ) ),
    );
} elseif ( ! empty( $title_cards[0] ) ) {
    $hero_title_card = array(
        'title'     => trim( (string) ( $title_cards[0]['title'] ?? '' ) ),
        'url'       => trim( (string) ( $title_cards[0]['url'] ?? $database_url ) ),
        'visual'    => is_array( $title_cards[0]['visual'] ?? null ) ? $title_cards[0]['visual'] : array(),
        'eyebrow'   => 'Featured Portal Entry',
        'meta_line' => trim( (string) ( $title_cards[0]['categories_line'] ?? '' ) ),
        'body'      => '',
    );
}

$portal_stats = array(
    array(
        'label' => 'Ceremony',
        'value' => $ceremony_label,
    ),
    array(
        'label' => 'Year',
        'value' => $year_label !== '' ? $year_label : 'Live',
    ),
    array(
        'label' => 'Rows',
        'value' => number_format_i18n( intval( $database_spotlight['records_total'] ?? 0 ) ),
    ),
    array(
        'label' => 'Categories',
        'value' => number_format_i18n( intval( $database_spotlight['categories_total'] ?? 0 ) ),
    ),
);

// Backdrop images keyed by portal card to keep the top-level gateway visual.
$portal_backdrop_map = array(
    'Ceremonies' => 'tt7286456',
    'Categories' => 'tt1375666',
    'Ledger'     => 'tt0111161',
    'About'      => 'tt0068646',
);
$portal_backdrops = array();

if ( class_exists( 'Academy_Awards_Table' ) ) {
    $aat_instance = Academy_Awards_Table::get_instance();
    if ( $aat_instance && method_exists( $aat_instance, 'get_title_visual_package' ) ) {
        foreach ( $portal_backdrop_map as $key => $imdb_id ) {
            $visual   = $aat_instance->get_title_visual_package( $imdb_id, 'large' );
            $backdrop = trim( (string) ( $visual['backdrop_url'] ?? '' ) );

            $portal_backdrops[ $key ] = function_exists( 'lunara_resize_tmdb_image_url' ) ? lunara_resize_tmdb_image_url( $backdrop, 'w780' ) : $backdrop;
        }
    }
}

$portal_link_defaults = array(
    1 => array(
        'kicker'   => 'Ceremonies',
        'title'    => 'Ceremony Archive',
        'copy'     => '',
        'url'      => $ceremonies_url,
        'backdrop' => $portal_backdrops['Ceremonies'] ?? '',
    ),
    2 => array(
        'kicker'   => 'Categories',
        'title'    => 'Category History',
        'copy'     => '',
        'url'      => $categories_url,
        'backdrop' => $portal_backdrops['Categories'] ?? '',
    ),
    3 => array(
        'kicker'   => 'Ledger',
        'title'    => 'Full Ledger',
        'copy'     => '',
        'url'      => $database_table_url,
        'backdrop' => $portal_backdrops['Ledger'] ?? '',
    ),
    4 => array(
        'kicker'   => 'About',
        'title'    => 'Ledger Method',
        'copy'     => '',
        'url'      => $about_url,
        'backdrop' => $portal_backdrops['About'] ?? '',
    ),
);
$portal_links = array();

foreach ( $portal_link_defaults as $slot => $defaults ) {
    if ( ! get_theme_mod( 'lunara_oscars_portal_card_' . $slot . '_enabled', true ) ) {
        continue;
    }

    $card_url = trim( (string) get_theme_mod( 'lunara_oscars_portal_card_' . $slot . '_url', $defaults['url'] ) );
    if ( '' === $card_url ) {
        $card_url = $defaults['url'];
    }

    if ( 3 === $slot ) {
        $normalized_card_url = untrailingslashit( remove_query_arg( 'view', $card_url ) );
        $normalized_base_url = untrailingslashit( remove_query_arg( 'view', $database_url ) );

        if ( $normalized_card_url === $normalized_base_url ) {
            $card_url = $database_table_url;
        }
    }

    $portal_links[] = array(
        'kicker'   => function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_portal_card_' . $slot . '_kicker', $defaults['kicker'] ) : $defaults['kicker'],
        'title'    => function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_portal_card_' . $slot . '_title', $defaults['title'] ) : $defaults['title'],
        'copy'     => function_exists( 'lunara_theme_mod_text' ) ? lunara_theme_mod_text( 'lunara_oscars_portal_card_' . $slot . '_copy', $defaults['copy'] ) : $defaults['copy'],
        'url'      => $card_url,
        'backdrop' => $defaults['backdrop'],
    );
}

$research_cards = array(
    array(
        'kicker'   => 'Data Explorer',
        'title'    => 'Open the full research table.',
        'copy'     => '',
        'url'      => $database_table_url,
        'backdrop' => $portal_backdrops['Ledger'] ?? '',
    ),
    array(
        'kicker'   => 'Poster View',
        'title'    => 'Return to the faster poster-led front door.',
        'copy'     => '',
        'url'      => $database_landing_url,
        'backdrop' => $portal_backdrops['About'] ?? '',
    ),
    array(
        'kicker'   => 'Latest Ceremony',
        'title'    => 'Open the newest Oscar race as a full ceremony page.',
        'copy'     => '',
        'url'      => $ceremony_url,
        'backdrop' => $portal_backdrops['Ceremonies'] ?? '',
    ),
    array(
        'kicker'   => 'Categories',
        'title'    => 'Pivot into category history immediately.',
        'copy'     => '',
        'url'      => $categories_url,
        'backdrop' => $portal_backdrops['Categories'] ?? '',
    ),
);
?>
<main id="primary" class="site-main lunara-oscars-portal">
    <?php if ( empty( $snapshot ) && empty( $database_spotlight ) ) : ?>
        <section class="lunara-home-section lunara-archive-hero">
            <p class="lunara-archive-hero-kicker"><?php echo esc_html( $hero_kicker ); ?></p>
            <h1 class="lunara-archive-hero-title"><?php echo esc_html( get_the_title() ); ?></h1>
        </section>
        <section class="lunara-home-section">
            <div class="lunara-journal-single-content">
                <?php the_content(); ?>
            </div>
        </section>
    <?php else : ?>
        <?php if ( $show_hero ) : ?>
        <section class="lunara-home-section lunara-oscars-portal-hero lunara-oscars-portal-slot-hero"<?php if ( '' !== $hero_style ) : ?> style="<?php echo esc_attr( $hero_style ); ?>"<?php endif; ?>>
            <div class="lunara-oscars-portal-hero-grid">
                <div class="lunara-oscars-portal-copy">
                    <p class="lunara-home-section-kicker"><?php echo esc_html( $hero_kicker ); ?></p>
                    <h1 class="lunara-home-hero-title"><?php echo esc_html( $hero_title ); ?></h1>
                    <?php if ( '' !== trim( (string) $hero_copy ) ) : ?>
                        <p class="lunara-home-hero-copy"><?php echo esc_html( $hero_copy ); ?></p>
                    <?php endif; ?>

                    <div class="lunara-oscars-portal-actions">
                        <a class="lunara-button lunara-button-primary" href="<?php echo esc_url( $ceremony_url ); ?>"><?php echo esc_html( get_theme_mod( 'lunara_oscars_ceremony_btn', __( 'Latest Ceremony', 'lunara-film' ) ) ); ?></a>
                        <a class="lunara-button lunara-button-secondary" href="<?php echo esc_url( $database_table_url ); ?>"><?php echo esc_html( get_theme_mod( 'lunara_oscars_ledger_btn', __( 'Open Full Ledger', 'lunara-film' ) ) ); ?></a>
                        <a class="lunara-button-ghost" href="<?php echo esc_url( $categories_url ); ?>"><?php echo esc_html( get_theme_mod( 'lunara_oscars_categories_btn', __( 'Browse Categories', 'lunara-film' ) ) ); ?></a>
                    </div>

                    <div class="lunara-oscars-portal-stat-grid">
                        <?php foreach ( $portal_stats as $stat ) : ?>
                            <div class="lunara-oscars-portal-stat">
                                <span class="lunara-oscars-portal-stat-label"><?php echo esc_html( $stat['label'] ); ?></span>
                                <strong class="lunara-oscars-portal-stat-value"><?php echo esc_html( $stat['value'] ); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ( ! empty( $hero_title_card ) ) : ?>
                    <a class="lunara-oscars-portal-feature-card" href="<?php echo esc_url( $hero_title_card['url'] ?? $database_url ); ?>">
                        <?php
                        $hero_feature_poster_url  = trim( (string) ( $hero_title_card['visual']['poster_url'] ?? '' ) );
                        $hero_feature_poster_html = ! empty( $hero_title_card['visual']['poster_html'] )
                            ? (string) $hero_title_card['visual']['poster_html']
                            : '';

                        if ( '' !== $hero_feature_poster_html ) {
                            $hero_feature_poster_html = str_replace( 'loading="lazy"', 'loading="eager" fetchpriority="high"', $hero_feature_poster_html );
                        }
                        ?>
                        <div class="lunara-oscars-portal-feature-poster<?php echo '' !== $hero_feature_poster_url ? ' has-poster-bg' : ''; ?>"<?php if ( '' !== $hero_feature_poster_url ) : ?> style="background-image: url('<?php echo esc_url( $hero_feature_poster_url ); ?>');"<?php endif; ?>>
                            <?php if ( ! empty( $hero_title_card['visual']['poster_html'] ) ) : ?>
                                <?php echo $hero_feature_poster_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php elseif ( ! empty( $hero_title_card['visual']['poster_url'] ) ) : ?>
                                <img src="<?php echo esc_url( $hero_title_card['visual']['poster_url'] ); ?>" alt="<?php echo esc_attr( $hero_title_card['title'] ); ?>" loading="eager" fetchpriority="high" decoding="async" />
                            <?php elseif ( ! empty( $hero_title_card['visual']['card_fallback_html'] ) ) : ?>
                                <?php echo $hero_title_card['visual']['card_fallback_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php else : ?>
                                <div class="aat-filmography-poster-placeholder"><div class="aat-fallback-inner"><div class="aat-fallback-kicker">Oscar Portal</div><div class="aat-fallback-title small"><?php echo esc_html( $hero_title_card['title'] ); ?></div></div></div>
                            <?php endif; ?>
                        </div>
                        <div class="lunara-oscars-portal-feature-copy">
                            <p class="lunara-oscars-portal-feature-kicker"><?php echo esc_html( $hero_title_card['eyebrow'] ); ?></p>
                            <h2><?php echo esc_html( $hero_title_card['title'] ); ?></h2>
                            <?php if ( '' !== trim( (string) $hero_title_card['meta_line'] ) ) : ?>
                                <p class="lunara-oscars-portal-feature-meta"><?php echo esc_html( $hero_title_card['meta_line'] ); ?></p>
                            <?php endif; ?>
                            <?php if ( '' !== trim( (string) $hero_title_card['body'] ) ) : ?>
                                <p class="lunara-oscars-portal-feature-body"><?php echo esc_html( $hero_title_card['body'] ); ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ( $show_portal_links && ! empty( $portal_links ) ) : ?>
        <section class="lunara-home-section lunara-oscars-portal-links-section lunara-oscars-portal-slot-portal-links">
                <div class="lunara-home-section-header">
                    <div>
                    <p class="lunara-home-section-kicker"><?php echo esc_html( $explore_kicker ); ?></p>
                    <h2 class="lunara-home-section-title"><?php echo esc_html( $explore_heading ); ?></h2>
                </div>
            </div>

            <div class="lunara-oscars-portal-link-grid">
                <?php foreach ( $portal_links as $portal_link ) :
                    $backdrop_url = trim( (string) ( $portal_link['backdrop'] ?? '' ) );
                    $backdrop_css = '' !== $backdrop_url ? 'background-image:url(' . esc_url( $backdrop_url ) . ')' : '';
                    $backdrop_cls = '' !== $backdrop_url ? ' has-backdrop' : '';
                ?>
                    <a class="lunara-oscars-portal-link-card<?php echo esc_attr( $backdrop_cls ); ?>" href="<?php echo esc_url( $portal_link['url'] ); ?>"<?php if ( '' !== $backdrop_css ) : ?> style="<?php echo esc_attr( $backdrop_css ); ?>"<?php endif; ?>>
                        <p class="lunara-oscars-portal-link-kicker"><?php echo esc_html( $portal_link['kicker'] ); ?></p>
                        <h3><?php echo esc_html( $portal_link['title'] ); ?></h3>
                        <?php if ( '' !== trim( (string) $portal_link['copy'] ) ) : ?>
                            <p><?php echo esc_html( $portal_link['copy'] ); ?></p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ( $show_spotlights && ! empty( $spotlights ) ) : ?>
            <section class="lunara-home-section lunara-oscars-portal-spotlights lunara-oscars-portal-slot-spotlights">
                <div class="lunara-home-section-header">
                    <div>
                        <p class="lunara-home-section-kicker"><?php echo esc_html( $ceremony_label ); ?></p>
                        <h2 class="lunara-home-section-title"><?php echo esc_html( $spotlights_heading ); ?></h2>
                    </div>
                </div>

                <div class="lunara-oscars-portal-spotlight-grid">
                    <?php foreach ( $spotlights as $spotlight ) :
                        $sl_visual = is_array( $spotlight['visual'] ?? null ) ? $spotlight['visual'] : array();
                        $sl_winner = intval( $spotlight['winner'] ?? 0 );
                        $sl_poster_url = trim( (string) ( $sl_visual['poster_url'] ?? '' ) );
                    ?>
                        <article class="lunara-oscars-portal-spotlight-card<?php echo $sl_winner ? ' is-winner' : ''; ?>">
                            <?php $spotlight_primary_url = ! empty( $spotlight['primary_url'] ) ? $spotlight['primary_url'] : ( $spotlight['url'] ?? $database_url ); ?>
                            <?php if ( ! empty( $sl_visual['poster_html'] ) ) : ?>
                                <a class="lunara-oscars-spotlight-media-link" href="<?php echo esc_url( $spotlight_primary_url ); ?>">
                                    <div class="lunara-oscars-spotlight-poster<?php echo '' !== $sl_poster_url ? ' has-poster-bg' : ''; ?>"<?php if ( '' !== $sl_poster_url ) : ?> style="background-image: url('<?php echo esc_url( $sl_poster_url ); ?>');"<?php endif; ?>>
                                        <?php echo $sl_visual['poster_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    </div>
                                </a>
                            <?php elseif ( ! empty( $sl_visual['poster_url'] ) ) : ?>
                                <a class="lunara-oscars-spotlight-media-link" href="<?php echo esc_url( $spotlight_primary_url ); ?>">
                                    <div class="lunara-oscars-spotlight-poster has-poster-bg" style="background-image: url('<?php echo esc_url( $sl_visual['poster_url'] ); ?>');">
                                        <img src="<?php echo esc_url( $sl_visual['poster_url'] ); ?>" alt="<?php echo esc_attr( $spotlight['primary_label'] ?? '' ); ?>" loading="lazy" decoding="async" />
                                    </div>
                                </a>
                            <?php else : ?>
                                <a class="lunara-oscars-spotlight-media-link" href="<?php echo esc_url( $spotlight_primary_url ); ?>">
                                    <div class="lunara-oscars-spotlight-poster lunara-oscars-spotlight-poster--fallback">
                                        <span><?php echo esc_html( $spotlight['primary_label'] ?? '' ); ?></span>
                                    </div>
                                </a>
                            <?php endif; ?>
                            <div class="lunara-oscars-spotlight-card-copy">
                                <?php if ( ! empty( $spotlight['category_url'] ) ) : ?>
                                    <p class="lunara-oscars-portal-spotlight-category"><a class="lunara-oscars-text-link lunara-oscars-text-link--meta" href="<?php echo esc_url( $spotlight['category_url'] ); ?>"><?php echo esc_html( $spotlight['category_label'] ?? 'Category' ); ?></a></p>
                                <?php else : ?>
                                    <p class="lunara-oscars-portal-spotlight-category"><?php echo esc_html( $spotlight['category_label'] ?? 'Category' ); ?></p>
                                <?php endif; ?>
                                <h3>
                                    <?php if ( ! empty( $spotlight_primary_url ) ) : ?>
                                        <a class="lunara-oscars-text-link" href="<?php echo esc_url( $spotlight_primary_url ); ?>"><?php echo esc_html( $spotlight['primary_label'] ?? $spotlight['film'] ?? '' ); ?></a>
                                    <?php else : ?>
                                        <?php echo esc_html( $spotlight['primary_label'] ?? $spotlight['film'] ?? '' ); ?>
                                    <?php endif; ?>
                                </h3>
                                <?php if ( ! empty( $spotlight['secondary_label'] ) ) : ?>
                                    <p class="lunara-oscars-portal-spotlight-secondary">
                                        <?php if ( ! empty( $spotlight['secondary_url'] ) ) : ?>
                                            <a class="lunara-oscars-text-link lunara-oscars-text-link--secondary" href="<?php echo esc_url( $spotlight['secondary_url'] ); ?>"><?php echo esc_html( $spotlight['secondary_label'] ); ?></a>
                                        <?php else : ?>
                                            <?php echo esc_html( $spotlight['secondary_label'] ); ?>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ( ! empty( $spotlight['year'] ) ) : ?>
                                    <p class="lunara-oscars-portal-spotlight-meta"><?php echo esc_html( $spotlight['year'] ); ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ( $show_title_cards && ! empty( $title_cards ) ) : ?>
            <section class="lunara-home-section lunara-oscars-portal-titles lunara-oscars-portal-slot-titles">
                <div class="lunara-home-section-header">
                    <div>
                        <p class="lunara-home-section-kicker"><?php echo esc_html( $titles_kicker ); ?></p>
                        <h2 class="lunara-home-section-title"><?php echo esc_html( $titles_heading ); ?></h2>
                    </div>
                </div>

                <div class="lunara-oscars-portal-title-grid">
                    <?php foreach ( $title_cards as $card ) : ?>
                        <?php
                        $card_visual     = is_array( $card['visual'] ?? null ) ? $card['visual'] : array();
                        $card_poster_url = trim( (string) ( $card_visual['poster_url'] ?? '' ) );
                        ?>
                        <a class="lunara-oscars-portal-title-card" href="<?php echo esc_url( $card['url'] ?? $database_url ); ?>">
                            <div class="lunara-oscars-portal-title-media<?php echo '' !== $card_poster_url ? ' has-poster-bg' : ''; ?>"<?php if ( '' !== $card_poster_url ) : ?> style="background-image: url('<?php echo esc_url( $card_poster_url ); ?>');"<?php endif; ?>>
                                <?php if ( ! empty( $card_visual['poster_html'] ) ) : ?>
                                    <?php echo $card_visual['poster_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php elseif ( ! empty( $card_visual['poster_url'] ) ) : ?>
                                    <img src="<?php echo esc_url( $card_visual['poster_url'] ); ?>" alt="<?php echo esc_attr( $card['title'] ?? 'Oscar title' ); ?>" loading="lazy" decoding="async" />
                                <?php elseif ( ! empty( $card_visual['card_fallback_html'] ) ) : ?>
                                    <?php echo $card_visual['card_fallback_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php else : ?>
                                    <div class="aat-filmography-poster-placeholder"><div class="aat-fallback-inner"><div class="aat-fallback-kicker">Oscar Title</div><div class="aat-fallback-title small"><?php echo esc_html( $card['title'] ?? '' ); ?></div></div></div>
                                <?php endif; ?>
                            </div>
                            <div class="lunara-oscars-portal-title-copy">
                                <h3><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
                                <?php if ( ! empty( $card['year'] ) ) : ?>
                                    <p class="lunara-oscars-portal-title-year"><?php echo esc_html( $card['year'] ); ?></p>
                                <?php endif; ?>
                                <?php if ( ! empty( $card['categories_line'] ) ) : ?>
                                    <p class="lunara-oscars-portal-title-line"><?php echo esc_html( $card['categories_line'] ); ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ( $show_research && shortcode_exists( 'academy_awards' ) ) : ?>
            <section id="oscars-research" class="lunara-home-section lunara-oscars-portal-research lunara-oscars-portal-slot-research">
                <div class="lunara-home-section-header">
                    <div>
                        <p class="lunara-home-section-kicker"><?php echo esc_html( $research_kicker ); ?></p>
                        <h2 class="lunara-home-section-title"><?php echo esc_html( $research_heading ); ?></h2>
                    </div>
                    <?php if ( '' !== trim( (string) $research_copy ) ) : ?>
                        <p class="lunara-home-section-summary"><?php echo esc_html( $research_copy ); ?></p>
                    <?php endif; ?>
                </div>

                <div class="lunara-oscars-research-card-grid">
                    <?php foreach ( $research_cards as $research_card ) :
                        $backdrop_url = trim( (string) ( $research_card['backdrop'] ?? '' ) );
                        $backdrop_css = '' !== $backdrop_url ? 'background-image:url(' . esc_url( $backdrop_url ) . ')' : '';
                        $backdrop_cls = '' !== $backdrop_url ? ' has-backdrop' : '';
                    ?>
                        <a class="lunara-oscars-research-card<?php echo esc_attr( $backdrop_cls ); ?>" href="<?php echo esc_url( $research_card['url'] ); ?>"<?php if ( '' !== $backdrop_css ) : ?> style="<?php echo esc_attr( $backdrop_css ); ?>"<?php endif; ?>>
                            <p class="lunara-oscars-research-card-kicker"><?php echo esc_html( $research_card['kicker'] ); ?></p>
                            <h3><?php echo esc_html( $research_card['title'] ); ?></h3>
                            <?php if ( '' !== trim( (string) $research_card['copy'] ) ) : ?>
                                <p><?php echo esc_html( $research_card['copy'] ); ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="lunara-oscars-research-shell<?php echo $table_view_requested ? ' is-table-view' : ' is-landing-view'; ?>">
                    <?php echo do_shortcode( '[academy_awards]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </section>
        <?php endif; ?>

        <?php
        /**
         * "Reviews Inside the Ledger" section disabled 2026-04-20 per Dalton.
         * To be replaced by a more distinctive Oscars-native section (stats /
         * ceremony grid / deep-cuts visual). Re-enable by removing the `0 &&` guard.
         */
        ?>
        <?php if ( $show_linked_reviews && $linked_reviews instanceof WP_Query && $linked_reviews->have_posts() ) : ?>
            <section class="lunara-home-section lunara-oscars-portal-reviews lunara-oscars-portal-slot-linked-reviews">
                <div class="lunara-home-section-header">
                    <div>
                        <p class="lunara-home-section-kicker">Criticism Meets the Ledger</p>
                        <h2 class="lunara-home-section-title"><?php echo esc_html( $reviews_heading ); ?></h2>
                    </div>
                </div>

                <div class="lunara-review-grid lunara-review-archive-grid">
                    <?php while ( $linked_reviews->have_posts() ) : $linked_reviews->the_post(); ?>
                        <?php echo lunara_render_review_grid_card( get_the_ID() ); ?>
                    <?php endwhile; ?>
                </div>
            </section>
            <?php wp_reset_postdata(); ?>
        <?php endif; ?>

        <?php
        /**
         * Latest Ceremony Winners grid.
         * Uses the shared Oscars winner-card builder so the section
         * stays aligned with the Academy Awards plugin data layer.
         * @since 2026-04-20
         */
        $aat_instance       = function_exists( 'lunara_get_oscars_plugin' ) ? lunara_get_oscars_plugin() : null;
        $ceremony_label_str = ! empty( $snapshot['ceremony_label'] ) ? $snapshot['ceremony_label'] : '';
        $ceremony_url_str   = ! empty( $snapshot['ceremony_url'] ) ? $snapshot['ceremony_url'] : home_url( '/oscars/' );
        $winner_cards       = array();

        if ( function_exists( 'lunara_build_oscars_ceremony_winner_cards' ) ) {
            $winner_cards = lunara_build_oscars_ceremony_winner_cards(
                (array) ( $snapshot['winner_map'] ?? array() ),
                $aat_instance,
                12,
                array(
                    'use_curated_photos'   => false,
                    'prefer_backdrop'      => false,
                    'prefer_person_visuals' => true,
                    'title_visual_size'    => 'medium_large',
                    'person_visual_size'   => 'large',
                )
            );
        }

        if ( function_exists( 'lunara_normalize_visual_package_image_sizes' ) ) {
            foreach ( $winner_cards as $card_index => $winner_card ) {
                if ( ! empty( $winner_card['_visual'] ) && is_array( $winner_card['_visual'] ) ) {
                    $winner_cards[ $card_index ]['_visual'] = lunara_normalize_visual_package_image_sizes( $winner_card['_visual'], 'w500', 'w780' );
                }
            }
        }
        ?>
        <?php if ( $show_latest_winners && ! empty( $winner_cards ) ) : ?>
            <section class="lunara-home-section lunara-oscars-portal-winners lunara-ceremony-winners-section lunara-oscars-portal-slot-latest-winners" aria-label="Ceremony Winners">
                <div class="lunara-home-section-header">
                    <div>
                        <p class="lunara-home-section-kicker">Oscar Ledger</p>
                        <h2 class="lunara-home-section-title"><?php echo esc_html( $ceremony_label_str ?: $latest_winners_heading ); ?></h2>
                    </div>
                    <a class="lunara-section-link" href="<?php echo esc_url( $ceremony_url_str ); ?>"><?php echo esc_html( $latest_winners_link_label ); ?></a>
                </div>
                <div class="lunara-ceremony-winners-grid">
                    <?php foreach ( $winner_cards as $wcard ) :
                        $w_vis = $wcard['_visual'];
                        $winner_poster_url = trim( (string) ( $w_vis['poster_url'] ?? '' ) );
                    ?>
                    <article class="lunara-ceremony-winner-card<?php echo ! empty( $w_vis['poster_url'] ) || ! empty( $w_vis['poster_html'] ) ? ' has-poster' : ''; ?>">
                        <?php $winner_primary_url = ! empty( $wcard['primary_url'] ) ? $wcard['primary_url'] : ( ! empty( $wcard['film_url'] ) ? $wcard['film_url'] : $ceremony_url_str ); ?>
                        <?php $winner_media_url   = ! empty( $wcard['film_url'] ) ? $wcard['film_url'] : $winner_primary_url; ?>
                        <a class="lunara-ceremony-winner-media-link" href="<?php echo esc_url( $winner_media_url ); ?>">
                            <?php if ( ! empty( $w_vis['poster_html'] ) ) : ?>
                                <div class="lunara-ceremony-winner-poster<?php echo '' !== $winner_poster_url ? ' has-poster-bg' : ''; ?>"<?php if ( '' !== $winner_poster_url ) : ?> style="background-image: url('<?php echo esc_url( $winner_poster_url ); ?>');"<?php endif; ?>><?php echo $w_vis['poster_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                            <?php elseif ( ! empty( $w_vis['poster_url'] ) ) : ?>
                                <div class="lunara-ceremony-winner-poster has-poster-bg" style="background-image: url('<?php echo esc_url( $w_vis['poster_url'] ); ?>');"><img src="<?php echo esc_url( $w_vis['poster_url'] ); ?>" alt="<?php echo esc_attr( $wcard['film'] ?? '' ); ?> poster" loading="lazy" /></div>
                            <?php endif; ?>
                        </a>
                        <div class="lunara-ceremony-winner-copy">
                            <?php if ( ! empty( $wcard['category_url'] ) ) : ?>
                                <p class="lunara-ceremony-winner-category"><a class="lunara-oscars-text-link lunara-oscars-text-link--meta" href="<?php echo esc_url( $wcard['category_url'] ); ?>"><?php echo esc_html( $wcard['category_label'] ?? $wcard['canonical_category'] ?? '' ); ?></a></p>
                            <?php else : ?>
                                <p class="lunara-ceremony-winner-category"><?php echo esc_html( $wcard['category_label'] ?? $wcard['canonical_category'] ?? '' ); ?></p>
                            <?php endif; ?>
                            <h3 class="lunara-ceremony-winner-name">
                                <?php if ( ! empty( $winner_primary_url ) ) : ?>
                                    <a class="lunara-oscars-text-link" href="<?php echo esc_url( $winner_primary_url ); ?>"><?php echo esc_html( $wcard['primary_label'] ?? ( ! empty( $wcard['name'] ) ? $wcard['name'] : $wcard['film'] ) ); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html( $wcard['primary_label'] ?? ( ! empty( $wcard['name'] ) ? $wcard['name'] : $wcard['film'] ) ); ?>
                                <?php endif; ?>
                            </h3>
                            <?php if ( ! empty( $wcard['secondary_label'] ) ) : ?>
                                <p class="lunara-ceremony-winner-film">
                                    <?php if ( ! empty( $wcard['secondary_url'] ) ) : ?>
                                        <a class="lunara-oscars-text-link lunara-oscars-text-link--secondary" href="<?php echo esc_url( $wcard['secondary_url'] ); ?>"><?php echo esc_html( $wcard['secondary_label'] ); ?></a>
                                    <?php else : ?>
                                        <?php echo esc_html( $wcard['secondary_label'] ); ?>
                                    <?php endif; ?>
                                </p>
                            <?php elseif ( ! empty( $wcard['name'] ) && ! empty( $wcard['film'] ) ) : ?>
                                <p class="lunara-ceremony-winner-film"><?php echo esc_html( $wcard['film'] ); ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            </section>
        <?php endif; ?>

        <?php if ( $show_deep_cuts && ! empty( $deep_cuts ) ) : ?>
            <section class="lunara-home-section lunara-oscars-portal-deep-cuts lunara-oscars-portal-slot-deep-cuts">
                <div class="lunara-home-section-header">
                    <div>
                        <p class="lunara-home-section-kicker">Rotating Stats</p>
                        <h2 class="lunara-home-section-title"><?php echo esc_html( $deep_cuts_heading ); ?></h2>
                    </div>
                </div>

                <div class="lunara-oscars-portal-facts-grid">
                    <?php foreach ( $deep_cuts as $cut ) : ?>
                        <a class="lunara-oscars-portal-fact-card" href="<?php echo esc_url( $cut['url'] ?? $database_url ); ?>">
                            <p class="lunara-oscars-portal-fact-label"><?php echo esc_html( $cut['label'] ?? '' ); ?></p>
                            <strong class="lunara-oscars-portal-fact-value"><?php echo esc_html( $cut['value'] ?? '' ); ?></strong>
                            <?php if ( ! empty( $cut['context'] ) ) : ?>
                                <p class="lunara-oscars-portal-fact-context"><?php echo esc_html( $cut['context'] ); ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ( ! empty( $rotating_cards ) ) : ?>
            <?php
            $rotating_label = trim( (string) ( $rotating_showcase['ceremony_label'] ?? '' ) );
            $rotating_year  = trim( (string) ( $rotating_showcase['year_label'] ?? '' ) );
            $rotating_url   = trim( (string) ( $rotating_showcase['ceremony_url'] ?? $database_url ) );
            $rotating_copy  = trim( (string) ( $rotating_showcase['summary'] ?? '' ) );

            ?>
            <section class="lunara-home-section lunara-oscars-rotating-winners-section lunara-oscars-portal-slot-rotating-winners" aria-label="Oscars Deep Dive"<?php if ( $rotating_autoplay > 0 ) : ?> data-lunara-carousel data-lunara-carousel-autoplay="<?php echo absint( $rotating_autoplay ); ?>"<?php else : ?> data-lunara-carousel<?php endif; ?>>
                <div class="lunara-home-section-header">
                    <div>
                        <p class="lunara-home-section-kicker"><?php echo esc_html( $rotating_kicker ); ?></p>
                        <h2 class="lunara-home-section-title"><?php echo esc_html( $rotating_heading ); ?></h2>
                    </div>
                    <div class="lunara-oscars-rotating-winners-actions">
                        <p class="lunara-oscars-rotating-winners-note"><?php echo esc_html( $rotating_label . ( '' !== $rotating_year ? ' / ' . $rotating_year : '' ) ); ?></p>
                        <div class="lunara-poster-carousel-controls">
                            <button type="button" class="lunara-poster-carousel-btn lunara-poster-carousel-prev" data-lunara-carousel-prev aria-label="Previous rotating ceremony winners">&#8592;</button>
                            <button type="button" class="lunara-poster-carousel-btn lunara-poster-carousel-next" data-lunara-carousel-next aria-label="Next rotating ceremony winners">&#8594;</button>
                        </div>
                    </div>
                </div>
                <?php if ( '' !== $rotating_copy ) : ?>
                    <p class="lunara-home-section-summary"><?php echo esc_html( $rotating_copy ); ?></p>
                <?php endif; ?>
                <div class="lunara-ledger-carousel-wrap">
                    <div class="lunara-ledger-carousel-track lunara-oscars-winner-carousel-track" data-lunara-carousel-track>
                        <?php foreach ( $rotating_cards as $wcard ) :
                            $w_vis = is_array( $wcard['_visual'] ?? null ) ? $wcard['_visual'] : array();
                            $rotating_poster_url = trim( (string) ( $w_vis['poster_url'] ?? '' ) );
                        ?>
                        <article class="lunara-ceremony-winner-card lunara-oscars-winner-carousel-card<?php echo ! empty( $w_vis['poster_url'] ) || ! empty( $w_vis['poster_html'] ) ? ' has-poster' : ''; ?>">
                            <?php $rotating_primary_url = ! empty( $wcard['primary_url'] ) ? $wcard['primary_url'] : ( ! empty( $wcard['film_url'] ) ? $wcard['film_url'] : $rotating_url ); ?>
                            <?php $rotating_media_url   = ! empty( $wcard['film_url'] ) ? $wcard['film_url'] : $rotating_primary_url; ?>
                            <a class="lunara-ceremony-winner-media-link" href="<?php echo esc_url( $rotating_media_url ); ?>">
                                <?php if ( ! empty( $w_vis['poster_html'] ) ) : ?>
                                    <div class="lunara-ceremony-winner-poster<?php echo '' !== $rotating_poster_url ? ' has-poster-bg' : ''; ?>"<?php if ( '' !== $rotating_poster_url ) : ?> style="background-image: url('<?php echo esc_url( $rotating_poster_url ); ?>');"<?php endif; ?>><?php echo $w_vis['poster_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                                <?php elseif ( ! empty( $w_vis['poster_url'] ) ) : ?>
                                    <div class="lunara-ceremony-winner-poster has-poster-bg" style="background-image: url('<?php echo esc_url( $w_vis['poster_url'] ); ?>');"><img src="<?php echo esc_url( $w_vis['poster_url'] ); ?>" alt="<?php echo esc_attr( $wcard['film'] ?? '' ); ?> poster" loading="lazy" decoding="async" /></div>
                                <?php endif; ?>
                            </a>
                            <div class="lunara-ceremony-winner-copy">
                                <?php if ( ! empty( $wcard['category_url'] ) ) : ?>
                                    <p class="lunara-ceremony-winner-category"><a class="lunara-oscars-text-link lunara-oscars-text-link--meta" href="<?php echo esc_url( $wcard['category_url'] ); ?>"><?php echo esc_html( $wcard['category_label'] ?? $wcard['canonical_category'] ?? '' ); ?></a></p>
                                <?php else : ?>
                                    <p class="lunara-ceremony-winner-category"><?php echo esc_html( $wcard['category_label'] ?? $wcard['canonical_category'] ?? '' ); ?></p>
                                <?php endif; ?>
                                <h3 class="lunara-ceremony-winner-name">
                                    <?php if ( ! empty( $rotating_primary_url ) ) : ?>
                                        <a class="lunara-oscars-text-link" href="<?php echo esc_url( $rotating_primary_url ); ?>"><?php echo esc_html( $wcard['primary_label'] ?? ( ! empty( $wcard['name'] ) ? $wcard['name'] : $wcard['film'] ) ); ?></a>
                                    <?php else : ?>
                                        <?php echo esc_html( $wcard['primary_label'] ?? ( ! empty( $wcard['name'] ) ? $wcard['name'] : $wcard['film'] ) ); ?>
                                    <?php endif; ?>
                                </h3>
                                <?php if ( ! empty( $wcard['secondary_label'] ) ) : ?>
                                    <p class="lunara-ceremony-winner-film">
                                        <?php if ( ! empty( $wcard['secondary_url'] ) ) : ?>
                                            <a class="lunara-oscars-text-link lunara-oscars-text-link--secondary" href="<?php echo esc_url( $wcard['secondary_url'] ); ?>"><?php echo esc_html( $wcard['secondary_label'] ); ?></a>
                                        <?php else : ?>
                                            <?php echo esc_html( $wcard['secondary_label'] ); ?>
                                        <?php endif; ?>
                                    </p>
                                <?php elseif ( ! empty( $wcard['name'] ) && ! empty( $wcard['film'] ) ) : ?>
                                    <p class="lunara-ceremony-winner-film"><?php echo esc_html( $wcard['film'] ); ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </div>
                <a class="lunara-section-link" href="<?php echo esc_url( $rotating_url ); ?>"><?php echo esc_html( $rotating_link_label ); ?></a>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?php
wp_reset_postdata();
get_footer();
