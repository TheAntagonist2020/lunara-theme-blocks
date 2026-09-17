<?php
/**
 * Isolated runtime regression for Reviews Archive 3.2.40 composition helpers.
 *
 * Run: php tests/reviews-archive-composition-runtime.php
 */

define( 'ABSPATH', __DIR__ . '/' );

function lunara_test_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "Assertion failed: {$message}\n" );
        exit( 1 );
    }
}

function add_action() {}
function add_filter() {}
function __( $value ) { return $value; }
function esc_html__( $value ) { return esc_html( $value ); }
function esc_attr__( $value ) { return esc_attr( $value ); }
function esc_html_e( $value ) { echo esc_html( $value ); }
function esc_attr_e( $value ) { echo esc_attr( $value ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function wp_unslash( $value ) { return $value; }
function absint( $value ) { return abs( (int) $value ); }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function esc_url_raw( $url ) { return filter_var( (string) $url, FILTER_SANITIZE_URL ); }
function esc_url( $url ) { return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function wp_kses_post( $value ) { return (string) $value; }
function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); }
function strip_shortcodes( $value ) { return (string) $value; }
function get_bloginfo() { return 'UTF-8'; }
function get_the_title() { return 'Runtime Review'; }
function get_permalink() { return 'https://example.com/reviews/runtime-review/'; }
function attachment_url_to_postid() { return 0; }
function has_post_thumbnail( $post_id ) { return 903 === $post_id; }
function get_the_post_thumbnail_url() { return 'https://example.com/posters/thumbnail.jpg'; }
function get_the_post_thumbnail( $post_id, $size, $attrs ) {
    $html = '<img src="' . esc_url( get_the_post_thumbnail_url() ) . '" alt="Thumbnail"';
    foreach ( $attrs as $name => $value ) {
        $html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
    }
    return $html . '>';
}
function lunara_get_title_poster_html( $title_id, $size, $class, $alt, $loading ) {
    return '<img src="https://example.com/posters/title.jpg" class="' . esc_attr( $class )
        . '" alt="' . esc_attr( $alt ) . '" loading="' . esc_attr( $loading )
        . '" decoding="async" data-no-lazy="1" data-skip-lazy="1">';
}
function get_post_meta( $post_id, $key ) {
    if ( 902 === $post_id && '_lunara_imdb_title_id' === $key ) {
        return 'tt1234567';
    }
    if ( '_lunara_review_card_image' === $key ) {
        return 'https://image.tmdb.org/t/p/original/runtime-review.jpg';
    }
    if ( '_lunara_pull_quote' === $key ) {
        return 'A precise runtime argument for the feature card.';
    }
    return '';
}
function lunara_get_pinned_review_id() { return 77; }

class WP_Query {
    private $values;

    public function __construct( $values = array() ) {
        $this->values = $values;
    }

    public function get( $key ) {
        return isset( $this->values[ $key ] ) ? $this->values[ $key ] : null;
    }
}

$wpdb = (object) array( 'posts' => 'wp_posts' );

require dirname( __DIR__ ) . '/inc/review-rendering.php';

$existing_tax_query = array(
    'relation' => 'AND',
    array(
        'taxonomy' => 'festival',
        'field'    => 'slug',
        'terms'    => array( 'cannes' ),
    ),
);
$filtered_args = lunara_get_review_archive_query_args(
    array( 'tax_query' => $existing_tax_query ),
    'modified_desc',
    '2026'
);

lunara_test_assert( 'modified' === $filtered_args['orderby'], 'modified_desc must sort by modified date.' );
lunara_test_assert( 'DESC' === $filtered_args['order'], 'modified_desc must remain descending.' );
lunara_test_assert( 'AND' === $filtered_args['tax_query']['relation'], 'Year composition must retain an existing tax_query relation.' );
lunara_test_assert( 'festival' === $filtered_args['tax_query'][0]['taxonomy'], 'Year composition must retain the existing taxonomy clause.' );
lunara_test_assert( 'lunara_review_year' === $filtered_args['tax_query'][1]['taxonomy'], 'Year composition must append the Review Year clause.' );
lunara_test_assert( ! isset( $filtered_args['lunara_reviews_archive_pinned_orderby'] ), 'Year/sort filters must not promote the pinned Review.' );

$default_args = lunara_get_review_archive_query_args( array(), 'release_desc', '' );
lunara_test_assert( 77 === $default_args['lunara_reviews_archive_pinned_orderby'], 'Default unfiltered ordering must carry the canonical pin into SQL.' );

$saved_lane_order = array(
    'hero'         => 4,
    'grid'         => 2,
    'pagination'   => 3,
    'pairing-desk' => 1,
);
lunara_test_assert( 4 === lunara_get_review_archive_lane_order( $saved_lane_order, 'hero' ), 'Rendered Hero order must match the saved order.' );
lunara_test_assert( 2 === lunara_get_review_archive_lane_order( $saved_lane_order, 'grid' ), 'Rendered Grid order must match the saved order.' );
lunara_test_assert( 2 === lunara_get_review_archive_lane_order( $saved_lane_order, 'utility' ), 'Utility must travel with the saved Grid order.' );
lunara_test_assert( 3 === lunara_get_review_archive_lane_order( $saved_lane_order, 'pagination' ), 'Rendered Pagination order must match the saved order.' );
lunara_test_assert( 1 === lunara_get_review_archive_lane_order( $saved_lane_order, 'pairing-desk' ), 'Rendered Pairing Desk order must match the saved order.' );

$ordered = lunara_reviews_archive_pinned_orderby(
    'wp_posts.post_date DESC',
    new WP_Query( array( 'lunara_reviews_archive_pinned_orderby' => 77 ) )
);
lunara_test_assert( 0 === strpos( $ordered, 'CASE WHEN wp_posts.ID = 77 THEN 0 ELSE 1 END ASC' ), 'Pinned SQL must be deterministic and scoped by query var.' );
lunara_test_assert( false !== strpos( $ordered, 'wp_posts.post_date DESC' ), 'Pinned SQL must preserve the requested secondary order.' );

$attrs = array(
    'class'   => 'poster',
    'loading' => 'lazy',
);
$profile = array(
    'width'  => 500,
    'height' => 750,
    'sizes'  => '(max-width: 700px) 45vw, 250px',
);

$tmdb = lunara_get_review_remote_image_markup(
    'https://image.tmdb.org/t/p/original/odyssey.jpg',
    $attrs,
    $profile
);
lunara_test_assert( 'https://image.tmdb.org/t/p/w500/odyssey.jpg' === $tmdb['url'], 'Canonical TMDB art must use the bounded w500 source.' );
lunara_test_assert( 1 === substr_count( $tmdb['html'], 'w342/odyssey.jpg 342w' ), 'TMDB w342 candidate must appear once.' );
lunara_test_assert( 1 === substr_count( $tmdb['html'], 'w500/odyssey.jpg 500w' ), 'TMDB w500 candidate must appear once.' );
lunara_test_assert( 1 === substr_count( $tmdb['html'], 'w780/odyssey.jpg 780w' ), 'TMDB w780 candidate must appear once.' );
lunara_test_assert( false === strpos( $tmdb['html'], '/original/' ), 'Archive TMDB markup must not request /original.' );

$noncanonical_tmdb_url = 'https://image.tmdb.org/custom/odyssey.jpg';
$noncanonical_tmdb = lunara_get_review_remote_image_markup( $noncanonical_tmdb_url, $attrs, $profile );
lunara_test_assert( $noncanonical_tmdb_url === $noncanonical_tmdb['url'], 'Noncanonical TMDB paths must preserve the exact source.' );
lunara_test_assert( false === strpos( $noncanonical_tmdb['html'], 'srcset=' ), 'Noncanonical TMDB paths must not receive fabricated width candidates.' );

$publisher_url = 'https://publisher.example/images/odyssey-master.jpg?crop=wide';
$publisher = lunara_get_review_remote_image_markup( $publisher_url, $attrs, $profile );
lunara_test_assert( $publisher_url === $publisher['url'], 'Unknown providers must preserve the exact stored source.' );
lunara_test_assert( false === strpos( $publisher['html'], 'srcset=' ), 'Unknown providers must not receive guessed derivatives.' );

$feature_html = lunara_render_review_feature_card(
    901,
    array(
        'variant'       => 'lead',
        'loading'       => 'lazy',
        'fetchpriority' => '',
    )
);
lunara_test_assert( false !== strpos( $feature_html, 'lunara-review-feature-media-link' ), 'Feature card must expose a dedicated media link.' );
lunara_test_assert( false !== strpos( $feature_html, 'lunara-review-feature-title-link' ), 'Feature card must expose a dedicated title link.' );
lunara_test_assert( false !== strpos( $feature_html, 'lunara-review-feature-cta' ), 'Feature card must expose a dedicated CTA link.' );
lunara_test_assert( false === strpos( $feature_html, '/original/' ), 'Feature card must not emit the TMDB original.' );

preg_match_all( '#</?a\b[^>]*>#i', $feature_html, $anchor_tags );
$anchor_depth = 0;
$max_anchor_depth = 0;
foreach ( $anchor_tags[0] as $anchor_tag ) {
    if ( 0 === stripos( $anchor_tag, '</a' ) ) {
        --$anchor_depth;
    } else {
        ++$anchor_depth;
        $max_anchor_depth = max( $max_anchor_depth, $anchor_depth );
    }
}
lunara_test_assert( 1 === $max_anchor_depth, 'Feature card must never nest one anchor inside another.' );
lunara_test_assert( 0 === $anchor_depth, 'Feature card anchors must close cleanly.' );

$debrief_sources = array(
    902 => 'https://example.com/posters/title.jpg',
    903 => 'https://example.com/posters/thumbnail.jpg',
);
foreach ( $debrief_sources as $post_id => $source ) {
    $debrief_html = lunara_get_review_debrief_signature_media_html( $post_id );
    lunara_test_assert( false !== strpos( $debrief_html, 'loading="lazy"' ), 'Below-article posters must defer loading in both source paths.' );
    lunara_test_assert( false === strpos( $debrief_html, 'data-no-lazy=' ) && false === strpos( $debrief_html, 'data-skip-lazy=' ), 'Lazy debrief posters must not retain optimizer bypass flags.' );
    lunara_test_assert( false !== strpos( $debrief_html, 'src="' . $source . '"' ), 'Deferring a debrief poster must preserve its source.' );
    lunara_test_assert( false !== strpos( $debrief_html, 'width="2000"' ) && false !== strpos( $debrief_html, 'height="3000"' ), 'Deferred debrief posters must retain reserved dimensions.' );
    lunara_test_assert( false !== strpos( $debrief_html, 'sizes="(max-width: 900px) 42vw, 320px"' ), 'Deferred debrief posters must retain responsive sizing.' );
    lunara_test_assert( false !== strpos( $debrief_html, 'alt="Runtime Review poster"' ) && false !== strpos( $debrief_html, 'lunara-review-single-debrief-poster-shell' ), 'Deferring a debrief poster must preserve its accessible label and shell.' );
}
lunara_test_assert( '' === lunara_get_review_debrief_signature_media_html( 904 ), 'Missing debrief media must not create an empty poster shell.' );

fwrite( STDOUT, "reviews-archive-composition-runtime: all assertions passed.\n" );
