<?php
/**
 * Homepage section utilities.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Canonical homepage section registry used for labels, ordering, and visibility.
 */
function lunara_get_home_section_registry() {
    return array(
        'hero' => array(
            'label'        => __( 'Hero', 'lunara-film' ),
            'toggle_label' => __( 'Show Hero', 'lunara-film' ),
            'setting'      => 'lunara_home_show_hero',
            'description'  => __( 'Lead atmosphere, title block, and top call-to-action.', 'lunara-film' ),
            'aliases'      => array(),
        ),
        'featured' => array(
            'label'        => __( 'Featured Reviews', 'lunara-film' ),
            'toggle_label' => __( 'Show Featured Reviews', 'lunara-film' ),
            'setting'      => 'lunara_home_show_featured',
            'description'  => __( 'The curated poster-led criticism shelf.', 'lunara-film' ),
            'aliases'      => array( 'featured-reviews', 'featured-review' ),
        ),
        'dispatch' => array(
            'label'        => __( 'Journal', 'lunara-film' ),
            'toggle_label' => __( 'Show Journal', 'lunara-film' ),
            'setting'      => 'lunara_home_show_dispatch',
            'description'  => __( 'The mixed editorial lane for journal entries, essays, reactions, and audio.', 'lunara-film' ),
            'aliases'      => array( 'dispatches', 'journal' ),
        ),
        'oscar-picks' => array(
            'label'        => __( 'Oscar Picks', 'lunara-film' ),
            'toggle_label' => __( 'Show Oscar Picks', 'lunara-film' ),
            'setting'      => 'lunara_home_show_oscar_picks',
            'description'  => __( 'The editorial Oscar picks carousel and awards-season prediction lane.', 'lunara-film' ),
            'aliases'      => array( 'oscar-pick', 'picks' ),
        ),
        'oscar-facts' => array(
            'label'        => __( 'Oscar Facts', 'lunara-film' ),
            'toggle_label' => __( 'Show Oscar Facts', 'lunara-film' ),
            'setting'      => 'lunara_home_show_oscar_facts',
            'description'  => __( 'The rotating Oscar facts and film-history signature lane.', 'lunara-film' ),
            'aliases'      => array( 'oscar-fact', 'facts' ),
        ),
        'oscar-spotlight' => array(
            'label'        => __( 'Oscar Spotlight', 'lunara-film' ),
            'toggle_label' => __( 'Show Oscar Spotlight', 'lunara-film' ),
            'setting'      => 'lunara_home_show_oscar_spotlight',
            'description'  => __( 'The rotating Oscars hero spotlight lane.', 'lunara-film' ),
            'aliases'      => array( 'oscar-spotlights', 'spotlight' ),
        ),
        'database' => array(
            'label'        => __( 'Oscar Ledger Intro', 'lunara-film' ),
            'toggle_label' => __( 'Show Oscar Ledger Intro', 'lunara-film' ),
            'setting'      => 'lunara_home_show_database',
            'description'  => __( 'The structural introduction to the Lunara Oscar Ledger.', 'lunara-film' ),
            'aliases'      => array( 'database-spotlight' ),
        ),
        'ledger' => array(
            'label'        => __( 'From the Ledger', 'lunara-film' ),
            'toggle_label' => __( 'Show From the Ledger', 'lunara-film' ),
            'setting'      => 'lunara_home_show_ledger',
            'description'  => __( 'Oscar-ledger story cards and research-driven history links.', 'lunara-film' ),
            'aliases'      => array(),
        ),
        'deep-cuts' => array(
            'label'        => __( 'Oscar Facts Carousel', 'lunara-film' ),
            'toggle_label' => __( 'Show Oscar Facts Carousel', 'lunara-film' ),
            'setting'      => 'lunara_home_show_deep_cuts',
            'description'  => __( 'The lore and trivia carousel seeded from Oscars data and curated title stories.', 'lunara-film' ),
            'aliases'      => array( 'deep-cut', 'deepcut' ),
        ),
        'latest-reviews' => array(
            'label'        => __( 'Latest Reviews Grid', 'lunara-film' ),
            'toggle_label' => __( 'Show Latest Reviews Grid', 'lunara-film' ),
            'setting'      => 'lunara_home_show_latest_reviews',
            'description'  => __( 'The broader review archive lane beneath the hero and featured areas.', 'lunara-film' ),
            'aliases'      => array( 'latest', 'reviews' ),
        ),
        'pairing-desk' => array(
            'label'        => __( 'Pairing Desk (Pair It With)', 'lunara-film' ),
            'toggle_label' => __( 'Show Pairing Desk', 'lunara-film' ),
            'setting'      => 'lunara_home_show_pairing_desk',
            'description'  => __( 'Showcases the signature Pair It With module: the three films paired with the latest reviewed title.', 'lunara-film' ),
            'aliases'      => array( 'pairing', 'pair-it-with', 'pairings' ),
        ),
    );
}

/**
 * Canonical homepage section slugs used for ordering and visibility.
 */
function lunara_get_home_section_slugs() {
    return array_keys( lunara_get_home_section_registry() );
}

/**
 * Normalize a homepage section slug so editorial controls can use friendly aliases.
 */
function lunara_normalize_home_section_slug( $slug ) {
    $slug     = sanitize_title( (string) $slug );
    $registry = lunara_get_home_section_registry();

    foreach ( $registry as $canonical => $section ) {
        if ( $slug === $canonical ) {
            return $canonical;
        }

        if ( ! empty( $section['aliases'] ) && in_array( $slug, (array) $section['aliases'], true ) ) {
            return $canonical;
        }
    }

    return $slug;
}

/**
 * Sanitize a comma-separated homepage section order list.
 */
function lunara_sanitize_home_section_order( $value ) {
    $recognized = lunara_get_home_section_slugs();
    $tokens     = preg_split( '/[\s,\r\n]+/', strtolower( (string) $value ) );
    $ordered    = array();

    if ( is_array( $tokens ) ) {
        foreach ( $tokens as $token ) {
            $token = lunara_normalize_home_section_slug( $token );
            if ( '' === $token || ! in_array( $token, $recognized, true ) || in_array( $token, $ordered, true ) ) {
                continue;
            }

            $ordered[] = $token;
        }
    }

    foreach ( $recognized as $slug ) {
        if ( ! in_array( $slug, $ordered, true ) ) {
            // The Pair It With showcase belongs right under Latest Reviews by
            // default (signature module, not a footer afterthought); every
            // other newly recognized slug keeps the safe append-to-end rule.
            if ( 'pairing-desk' === $slug ) {
                $latest_pos = array_search( 'latest-reviews', $ordered, true );
                if ( false !== $latest_pos ) {
                    array_splice( $ordered, $latest_pos + 1, 0, array( $slug ) );
                    continue;
                }
            }
            $ordered[] = $slug;
        }
    }

    return implode( ',', $ordered );
}

/**
 * Resolve homepage section order into a slug => order map.
 */
function lunara_get_home_section_order_map() {
    $defaults = lunara_get_home_section_slugs();
    $raw      = (string) get_theme_mod( 'lunara_home_section_order', implode( ',', $defaults ) );
    $ordered  = explode( ',', lunara_sanitize_home_section_order( $raw ) );
    $map      = array();

    foreach ( $ordered as $index => $slug ) {
        $map[ $slug ] = $index + 1;
    }

    return $map;
}

/**
 * Determine whether a homepage section should render.
 */
function lunara_home_section_is_enabled( $slug, $default = true ) {
    $slug     = lunara_normalize_home_section_slug( $slug );
    $registry = lunara_get_home_section_registry();

    if ( empty( $registry[ $slug ]['setting'] ) ) {
        return (bool) $default;
    }

    return (bool) get_theme_mod( $registry[ $slug ]['setting'], $default );
}

/**
 * Extract canonical slugs from a section registry.
 */
function lunara_get_registry_slugs( $registry ) {
    return array_keys( is_array( $registry ) ? $registry : array() );
}

/**
 * Normalize a section slug against a registry and its aliases.
 */
function lunara_normalize_registry_slug( $slug, $registry ) {
    $slug = sanitize_title( (string) $slug );

    foreach ( (array) $registry as $canonical => $section ) {
        if ( $slug === $canonical ) {
            return $canonical;
        }

        if ( ! empty( $section['aliases'] ) && in_array( $slug, (array) $section['aliases'], true ) ) {
            return $canonical;
        }
    }

    return $slug;
}

/**
 * Sanitize a comma-separated section order list from a specific registry.
 */
function lunara_sanitize_registry_section_order( $value, $registry ) {
    $recognized = lunara_get_registry_slugs( $registry );
    $tokens     = preg_split( '/[\s,\r\n]+/', strtolower( (string) $value ) );
    $ordered    = array();

    if ( is_array( $tokens ) ) {
        foreach ( $tokens as $token ) {
            $token = lunara_normalize_registry_slug( $token, $registry );
            if ( '' === $token || ! in_array( $token, $recognized, true ) || in_array( $token, $ordered, true ) ) {
                continue;
            }

            $ordered[] = $token;
        }
    }

    foreach ( $recognized as $slug ) {
        if ( ! in_array( $slug, $ordered, true ) ) {
            $ordered[] = $slug;
        }
    }

    return implode( ',', $ordered );
}

/**
 * Resolve a theme-mod section order setting into a slug => order map.
 */
function lunara_get_registry_section_order_map( $setting_name, $registry, $fallback = '' ) {
    $defaults = lunara_get_registry_slugs( $registry );
    $raw      = (string) get_theme_mod( $setting_name, '' !== $fallback ? $fallback : implode( ',', $defaults ) );
    $ordered  = explode( ',', lunara_sanitize_registry_section_order( $raw, $registry ) );
    $map      = array();

    foreach ( $ordered as $index => $slug ) {
        $map[ $slug ] = $index + 1;
    }

    return $map;
}

/**
 * Determine whether a registered section should render.
 */
function lunara_registry_section_is_enabled( $slug, $registry, $default = true ) {
    $slug = lunara_normalize_registry_slug( $slug, $registry );

    if ( empty( $registry[ $slug ]['setting'] ) ) {
        return (bool) $default;
    }

    return (bool) get_theme_mod( $registry[ $slug ]['setting'], $default );
}

/**
 * Canonical review archive section registry.
 */
function lunara_get_reviews_archive_section_registry() {
    return array(
        'hero' => array(
            'label'        => __( 'Hero', 'lunara-film' ),
            'toggle_label' => __( 'Show Review Archive Hero', 'lunara-film' ),
            'setting'      => 'lunara_reviews_archive_show_hero',
            'description'  => __( 'Lead the review archive with its introduction and summary block.', 'lunara-film' ),
            'aliases'      => array(),
        ),
        'grid' => array(
            'label'        => __( 'Review Grid', 'lunara-film' ),
            'toggle_label' => __( 'Show Review Grid', 'lunara-film' ),
            'setting'      => 'lunara_reviews_archive_show_grid',
            'description'  => __( 'Render the main poster-led review grid.', 'lunara-film' ),
            'aliases'      => array( 'reviews', 'cards' ),
        ),
        'pagination' => array(
            'label'        => __( 'Pagination', 'lunara-film' ),
            'toggle_label' => __( 'Show Review Pagination', 'lunara-film' ),
            'setting'      => 'lunara_reviews_archive_show_pagination',
            'description'  => __( 'Keep the review archive paginated instead of hard-stopping after the visible grid.', 'lunara-film' ),
            'aliases'      => array( 'pager' ),
        ),
    );
}

/**
 * Canonical journal archive section registry.
 */
function lunara_get_journal_archive_section_registry() {
    return array(
        'hero' => array(
            'label'        => __( 'Hero', 'lunara-film' ),
            'toggle_label' => __( 'Show Journal Archive Hero', 'lunara-film' ),
            'setting'      => 'lunara_journal_archive_show_hero',
            'description'  => __( 'Show the journal archive title block and intro copy.', 'lunara-film' ),
            'aliases'      => array(),
        ),
        'filters' => array(
            'label'        => __( 'Type Filters', 'lunara-film' ),
            'toggle_label' => __( 'Show Journal Type Filters', 'lunara-film' ),
            'setting'      => 'lunara_journal_archive_show_filters',
            'description'  => __( 'Render the journal-type filter pills at the top of the archive.', 'lunara-film' ),
            'aliases'      => array( 'types', 'taxonomy' ),
        ),
        'grid' => array(
            'label'        => __( 'Journal Grid', 'lunara-film' ),
            'toggle_label' => __( 'Show Journal Grid', 'lunara-film' ),
            'setting'      => 'lunara_journal_archive_show_grid',
            'description'  => __( 'Render the archive card grid for published journal entries.', 'lunara-film' ),
            'aliases'      => array( 'entries', 'cards' ),
        ),
        'pagination' => array(
            'label'        => __( 'Pagination', 'lunara-film' ),
            'toggle_label' => __( 'Show Journal Pagination', 'lunara-film' ),
            'setting'      => 'lunara_journal_archive_show_pagination',
            'description'  => __( 'Keep the journal archive paginated when there are more entries to browse.', 'lunara-film' ),
            'aliases'      => array( 'pager' ),
        ),
    );
}

/**
 * Canonical news archive live-state section registry.
 */
function lunara_get_news_archive_live_section_registry() {
    return array(
        'hero' => array(
            'label'        => __( 'Hero', 'lunara-film' ),
            'toggle_label' => __( 'Show News Archive Hero', 'lunara-film' ),
            'setting'      => 'lunara_news_archive_show_hero',
            'description'  => __( 'Show the news archive intro shell.', 'lunara-film' ),
            'aliases'      => array(),
        ),
        'spotlight' => array(
            'label'        => __( 'Spotlight', 'lunara-film' ),
            'toggle_label' => __( 'Show News Spotlight', 'lunara-film' ),
            'setting'      => 'lunara_news_archive_show_spotlight',
            'description'  => __( 'Show the lead dispatch with the supporting rail beside it.', 'lunara-film' ),
            'aliases'      => array( 'lead' ),
        ),
        'run' => array(
            'label'        => __( 'Archive Run', 'lunara-film' ),
            'toggle_label' => __( 'Show News Archive Run', 'lunara-film' ),
            'setting'      => 'lunara_news_archive_show_run',
            'description'  => __( 'Show the broader archive run beneath the live spotlight.', 'lunara-film' ),
            'aliases'      => array( 'grid', 'archive-run' ),
        ),
        'pagination' => array(
            'label'        => __( 'Pagination', 'lunara-film' ),
            'toggle_label' => __( 'Show News Pagination', 'lunara-film' ),
            'setting'      => 'lunara_news_archive_show_pagination',
            'description'  => __( 'Keep the news archive paginated when more posts exist.', 'lunara-film' ),
            'aliases'      => array( 'pager' ),
        ),
    );
}

/**
 * Canonical news archive empty-state section registry.
 */
function lunara_get_news_archive_empty_section_registry() {
    return array(
        'hero' => array(
            'label'        => __( 'Hero', 'lunara-film' ),
            'toggle_label' => __( 'Show News Archive Hero', 'lunara-film' ),
            'setting'      => 'lunara_news_archive_show_hero',
            'description'  => __( 'Keep the archive title block visible even when the desk is on standby.', 'lunara-film' ),
            'aliases'      => array(),
        ),
        'intro' => array(
            'label'        => __( 'Standby Intro', 'lunara-film' ),
            'toggle_label' => __( 'Show News Standby Intro', 'lunara-film' ),
            'setting'      => 'lunara_news_archive_show_intro',
            'description'  => __( 'Show the empty-state explanation when the live desk has no current items.', 'lunara-film' ),
            'aliases'      => array( 'empty', 'empty-note' ),
        ),
        'standby' => array(
            'label'        => __( 'Standby Cards', 'lunara-film' ),
            'toggle_label' => __( 'Show News Standby Cards', 'lunara-film' ),
            'setting'      => 'lunara_news_archive_show_standby',
            'description'  => __( 'Show the fallback route cards to reviews, the Oscars portal, and the homepage.', 'lunara-film' ),
            'aliases'      => array( 'routes', 'cards' ),
        ),
    );
}

/**
 * Canonical news archive standby-card registry.
 */
function lunara_get_news_archive_standby_card_registry() {
    return array(
        'reviews' => array(
            'label'   => __( 'Reviews', 'lunara-film' ),
            'aliases' => array( 'criticism' ),
        ),
        'ledger' => array(
            'label'   => __( 'Ledger', 'lunara-film' ),
            'aliases' => array( 'oscars' ),
        ),
        'home' => array(
            'label'   => __( 'Homepage', 'lunara-film' ),
            'aliases' => array( 'front-door' ),
        ),
    );
}

/**
 * Canonical Oscars portal section registry.
 */
function lunara_get_oscars_portal_section_registry() {
    return array(
        'hero' => array(
            'label'        => __( 'Hero', 'lunara-film' ),
            'toggle_label' => __( 'Show Oscars Hero', 'lunara-film' ),
            'setting'      => 'lunara_oscars_show_hero',
            'description'  => __( 'Lead the portal with the hero shell, current ceremony framing, and featured title card.', 'lunara-film' ),
            'aliases'      => array(),
        ),
        'portal-links' => array(
            'label'        => __( 'Portal Links', 'lunara-film' ),
            'toggle_label' => __( 'Show Portal Links', 'lunara-film' ),
            'setting'      => 'lunara_oscars_show_portal_links',
            'description'  => __( 'Show the gateway cards into ceremonies, categories, the ledger, and the about page.', 'lunara-film' ),
            'aliases'      => array( 'links', 'explore' ),
        ),
        'spotlights' => array(
            'label'        => __( 'Spotlights', 'lunara-film' ),
            'toggle_label' => __( 'Show Ceremony Spotlights', 'lunara-film' ),
            'setting'      => 'lunara_oscars_show_spotlights',
            'description'  => __( 'Show the latest ceremony winners category by category.', 'lunara-film' ),
            'aliases'      => array( 'spotlight', 'ceremony' ),
        ),
        'titles' => array(
            'label'        => __( 'Title Cards', 'lunara-film' ),
            'toggle_label' => __( 'Show Title Cards', 'lunara-film' ),
            'setting'      => 'lunara_oscars_show_title_cards',
            'description'  => __( 'Show the poster-led title entry points into the ledger.', 'lunara-film' ),
            'aliases'      => array( 'title-cards' ),
        ),
        'research' => array(
            'label'        => __( 'Research', 'lunara-film' ),
            'toggle_label' => __( 'Show Research Layer', 'lunara-film' ),
            'setting'      => 'lunara_oscars_show_research',
            'description'  => __( 'Embed the living ledger landing/table layer directly into the public Oscars portal.', 'lunara-film' ),
            'aliases'      => array( 'explorer', 'ledger', 'data-explorer' ),
        ),
        'latest-winners' => array(
            'label'        => __( 'Latest Winners', 'lunara-film' ),
            'toggle_label' => __( 'Show Latest Winners Grid', 'lunara-film' ),
            'setting'      => 'lunara_oscars_show_latest_winners',
            'description'  => __( 'Show the latest ceremony winner grid.', 'lunara-film' ),
            'aliases'      => array( 'winners' ),
        ),
        'deep-cuts' => array(
            'label'        => __( 'Deep Cuts', 'lunara-film' ),
            'toggle_label' => __( 'Show Deep Cuts', 'lunara-film' ),
            'setting'      => 'lunara_oscars_show_deep_cuts',
            'description'  => __( 'Show the Oscar facts and trivia lane.', 'lunara-film' ),
            'aliases'      => array( 'facts', 'trivia' ),
        ),
        'rotating-winners' => array(
            'label'        => __( 'Rotating Winners', 'lunara-film' ),
            'toggle_label' => __( 'Show Rotating Winners', 'lunara-film' ),
            'setting'      => 'lunara_oscars_rotating_winners_enabled',
            'description'  => __( 'Show the ceremony-winners carousel that rotates through different Oscar years.', 'lunara-film' ),
            'aliases'      => array( 'carousel', 'rotation' ),
        ),
        'linked-reviews' => array(
            'label'        => __( 'Linked Reviews', 'lunara-film' ),
            'toggle_label' => __( 'Show Linked Reviews', 'lunara-film' ),
            'setting'      => 'lunara_oscars_show_linked_reviews',
            'description'  => __( 'Show the review cards that connect Lunara criticism back into the Oscars title pages.', 'lunara-film' ),
            'aliases'      => array( 'reviews' ),
        ),
    );
}

function lunara_sanitize_reviews_archive_section_order( $value ) {
    return lunara_sanitize_registry_section_order( $value, lunara_get_reviews_archive_section_registry() );
}

function lunara_sanitize_journal_archive_section_order( $value ) {
    return lunara_sanitize_registry_section_order( $value, lunara_get_journal_archive_section_registry() );
}

function lunara_sanitize_news_archive_live_section_order( $value ) {
    return lunara_sanitize_registry_section_order( $value, lunara_get_news_archive_live_section_registry() );
}

function lunara_sanitize_news_archive_empty_section_order( $value ) {
    return lunara_sanitize_registry_section_order( $value, lunara_get_news_archive_empty_section_registry() );
}

function lunara_sanitize_news_archive_standby_card_order( $value ) {
    return lunara_sanitize_registry_section_order( $value, lunara_get_news_archive_standby_card_registry() );
}

function lunara_sanitize_oscars_portal_section_order( $value ) {
    return lunara_sanitize_registry_section_order( $value, lunara_get_oscars_portal_section_registry() );
}

function lunara_get_reviews_archive_section_order_map() {
    return lunara_get_registry_section_order_map(
        'lunara_reviews_archive_section_order',
        lunara_get_reviews_archive_section_registry()
    );
}

function lunara_get_journal_archive_section_order_map() {
    return lunara_get_registry_section_order_map(
        'lunara_journal_archive_section_order',
        lunara_get_journal_archive_section_registry()
    );
}

function lunara_get_news_archive_live_section_order_map() {
    return lunara_get_registry_section_order_map(
        'lunara_journal_archive_live_section_order',
        lunara_get_news_archive_live_section_registry()
    );
}

function lunara_get_news_archive_empty_section_order_map() {
    return lunara_get_registry_section_order_map(
        'lunara_journal_archive_empty_section_order',
        lunara_get_news_archive_empty_section_registry()
    );
}

function lunara_get_news_archive_standby_card_order_map() {
    return lunara_get_registry_section_order_map(
        'lunara_journal_archive_standby_card_order',
        lunara_get_news_archive_standby_card_registry()
    );
}

function lunara_get_oscars_portal_section_order_map() {
    return lunara_get_registry_section_order_map(
        'lunara_oscars_portal_section_order',
        lunara_get_oscars_portal_section_registry()
    );
}

function lunara_reviews_archive_section_is_enabled( $slug, $default = true ) {
    return lunara_registry_section_is_enabled( $slug, lunara_get_reviews_archive_section_registry(), $default );
}

function lunara_journal_archive_section_is_enabled( $slug, $default = true ) {
    return lunara_registry_section_is_enabled( $slug, lunara_get_journal_archive_section_registry(), $default );
}

function lunara_news_archive_live_section_is_enabled( $slug, $default = true ) {
    return lunara_registry_section_is_enabled( $slug, lunara_get_news_archive_live_section_registry(), $default );
}

function lunara_news_archive_empty_section_is_enabled( $slug, $default = true ) {
    return lunara_registry_section_is_enabled( $slug, lunara_get_news_archive_empty_section_registry(), $default );
}

function lunara_oscars_portal_section_is_enabled( $slug, $default = true ) {
    return lunara_registry_section_is_enabled( $slug, lunara_get_oscars_portal_section_registry(), $default );
}
