<?php
/**
 * Dependency-free fixture for the local-only Debrief film resolver.
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['lunara_resolver_test'] = array(
    'posts' => array(
        11 => array( 'post_type' => 'movie', 'post_status' => 'publish', 'title' => 'Theme Film' ),
        12 => array( 'post_type' => 'movie', 'post_status' => 'draft', 'title' => 'Draft Film' ),
        13 => array( 'post_type' => 'movie', 'post_status' => 'publish', 'title' => 'Local Poster Film' ),
        14 => array( 'post_type' => 'movie', 'post_status' => 'private', 'title' => 'Private Film' ),
        15 => array( 'post_type' => 'movie', 'post_status' => 'publish', 'title' => '' ),
        16 => array( 'post_type' => 'movie', 'post_status' => 'publish', 'title' => 'No IMDb Film' ),
        94 => array( 'post_type' => 'review', 'post_status' => 'publish', 'title' => 'Draft Fallback Review' ),
        95 => array( 'post_type' => 'review', 'post_status' => 'publish', 'title' => 'Private Fallback Review' ),
        98 => array( 'post_type' => 'review', 'post_status' => 'draft', 'title' => 'Draft Film Review' ),
        99 => array( 'post_type' => 'review', 'post_status' => 'publish', 'title' => 'Theme Film Review' ),
    ),
    'meta' => array(
        11 => array(
            'release_year'   => '2020',
            'imdb_title_id'  => 'TT0000011',
            'directors'      => array( 201, 201 ),
            'principal_cast' => array( 301, 302 ),
        ),
        12 => array( 'release_year' => '2021', 'imdb_title_id' => 'tt0000012' ),
        13 => array( 'release_year' => '2010', 'imdb_title_id' => 'tt0000013' ),
        14 => array( 'release_year' => '2014', 'imdb_title_id' => 'tt0000014' ),
        15 => array( 'release_year' => '2015', 'imdb_title_id' => 'tt0000015' ),
        16 => array( 'release_year' => '2016' ),
        94 => array( '_lunara_imdb_title_id' => 'tt0000012' ),
        95 => array( '_lunara_imdb_title_id' => 'tt0000014' ),
        99 => array( '_lunara_imdb_title_id' => 'tt0000011' ),
    ),
    'thumbnails'     => array( 11 => 501 ),
    'meta_reads'     => 0,
    'award_reads'    => 0,
    'aat_poster_ids' => array( 'tt0000013' => 777 ),
);

function absint( $value ) {
    return abs( (int) $value );
}

function get_post_type( $post_id ) {
    return $GLOBALS['lunara_resolver_test']['posts'][ $post_id ]['post_type'] ?? '';
}

function get_post_status( $post_id ) {
    return $GLOBALS['lunara_resolver_test']['posts'][ $post_id ]['post_status'] ?? '';
}

function get_the_title( $post_id ) {
    return $GLOBALS['lunara_resolver_test']['posts'][ $post_id ]['title'] ?? '';
}

function get_post_meta( $post_id, $key, $single = false ) {
    ++$GLOBALS['lunara_resolver_test']['meta_reads'];
    return $GLOBALS['lunara_resolver_test']['meta'][ $post_id ][ $key ] ?? '';
}

function get_permalink( $post_id ) {
    return isset( $GLOBALS['lunara_resolver_test']['posts'][ $post_id ] )
        ? 'https://example.test/film/' . $post_id . '/'
        : '';
}

function get_post_thumbnail_id( $post_id ) {
    return $GLOBALS['lunara_resolver_test']['thumbnails'][ $post_id ] ?? 0;
}

function maybe_unserialize( $value ) {
    return $value;
}

function apply_filters( $hook, $value, ...$args ) {
    return $value;
}

function get_posts( $args ) {
    $matches = array();
    foreach ( $GLOBALS['lunara_resolver_test']['posts'] as $post_id => $post ) {
        if ( $post['post_type'] !== ( $args['post_type'] ?? '' ) ) {
            continue;
        }

        $allowed_statuses = (array) ( $args['post_status'] ?? 'publish' );
        if ( ! in_array( $post['post_status'], $allowed_statuses, true ) ) {
            continue;
        }

        foreach ( $args['meta_query'] ?? array() as $condition ) {
            if ( ! is_array( $condition ) || empty( $condition['key'] ) ) {
                continue;
            }
            $actual = $GLOBALS['lunara_resolver_test']['meta'][ $post_id ][ $condition['key'] ] ?? '';
            if ( (string) $actual === (string) ( $condition['value'] ?? '' ) ) {
                $matches[] = $post_id;
                break;
            }
        }
    }
    return array_slice( $matches, 0, 1 );
}

function lunara_get_oscar_ledger_counts( $imdb_id = '' ) {
    ++$GLOBALS['lunara_resolver_test']['award_reads'];
    return array( 'noms' => 4, 'wins' => 1 );
}

function lunara_debrief_get_review_record( $review_id ) {
    $movie_ids = array(
        98 => 12,
        99 => 11,
    );

    return array(
        'review_id'     => $review_id,
        'reviewed_film' => array( 'movie_id' => $movie_ids[ $review_id ] ?? 0 ),
    );
}

final class Academy_Awards_Table {
    public static function get_instance() {
        return new self();
    }

    public function get_poster_attachment_id_for_title( $imdb_id ) {
        return $GLOBALS['lunara_resolver_test']['aat_poster_ids'][ $imdb_id ] ?? 0;
    }
}

function lunara_resolver_assert_same( $expected, $actual, $message ) {
    if ( $expected !== $actual ) {
        throw new RuntimeException(
            $message . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true )
        );
    }
}

function lunara_resolver_assert_true( $condition, $message ) {
    if ( ! $condition ) {
        throw new RuntimeException( $message );
    }
}

require dirname( __DIR__, 2 ) . '/inc/debrief-resolver.php';

$theme_film = lunara_debrief_resolve_movie( 11 );
lunara_resolver_assert_true( $theme_film['valid'], 'A published canonical movie must resolve.' );
lunara_resolver_assert_same( 'tt0000011', $theme_film['imdb_title_id'], 'IMDb IDs must normalize to lower case.' );
lunara_resolver_assert_same( 501, $theme_film['poster_attachment_id'], 'Featured images must be first poster authority.' );
lunara_resolver_assert_same( 'movie_featured', $theme_film['poster_source'], 'Featured poster source must be explicit.' );
lunara_resolver_assert_same( array( 201 ), $theme_film['directors'], 'Relationship IDs must be unique.' );
lunara_resolver_assert_true( ! $theme_film['awards_resolved'], 'Awards must remain opt-in.' );

$reads_after_first = $GLOBALS['lunara_resolver_test']['meta_reads'];
$cached_film       = lunara_debrief_resolve_movie( 11 );
lunara_resolver_assert_same( $theme_film, $cached_film, 'Resolver output must be deterministic.' );
lunara_resolver_assert_same( $reads_after_first, $GLOBALS['lunara_resolver_test']['meta_reads'], 'Repeat resolution must use the request cache.' );

$with_awards = lunara_debrief_resolve_movie( 11, array( 'resolve_awards' => true ) );
lunara_resolver_assert_true( $with_awards['awards_resolved'], 'Local Oscar counts must resolve only when requested.' );
lunara_resolver_assert_same( array( 'noms' => 4, 'wins' => 1 ), $with_awards['oscar_counts'], 'Oscar counts must keep a bounded shape.' );
lunara_resolver_assert_same( 1, $GLOBALS['lunara_resolver_test']['award_reads'], 'The opt-in awards lookup must run once.' );

$draft_default = lunara_debrief_resolve_movie( 12 );
lunara_resolver_assert_true( ! $draft_default['valid'], 'Draft movies must not become public companion records by default.' );
lunara_resolver_assert_true( in_array( 'movie_not_published', $draft_default['warnings'], true ), 'Draft rejection must expose a stable warning.' );

$draft_admin = lunara_debrief_resolve_movie( 12, array( 'require_published' => false ) );
lunara_resolver_assert_true( ! $draft_admin['valid'], 'Draft entities must remain invalid for public output during admin inspection.' );
lunara_resolver_assert_same( 'Draft Film', $draft_admin['title'], 'Admin callers may explicitly inspect a complete draft entity.' );

$aat_local = lunara_debrief_resolve_movie( 13 );
lunara_resolver_assert_same( 777, $aat_local['poster_attachment_id'], 'The resolver may use the Oscars plugin local attachment index.' );
lunara_resolver_assert_same( 'aat_local', $aat_local['poster_source'], 'Local Oscars poster authority must remain identifiable.' );

$reviewed = lunara_debrief_resolve_reviewed_movie( 99 );
lunara_resolver_assert_same( 11, $reviewed['movie_id'], 'A Review must resolve its Core-owned source movie.' );

$reviewed_bad_args = lunara_debrief_resolve_reviewed_movie( 99, 'not-an-options-array' );
lunara_resolver_assert_same( 11, $reviewed_bad_args['movie_id'], 'Non-array Review options must normalize without a PHP 8 TypeError.' );
lunara_resolver_assert_true( $reviewed_bad_args['valid'], 'Normalized Review options must retain the published default.' );

$reviewed_draft_default = lunara_debrief_resolve_reviewed_movie( 98 );
lunara_resolver_assert_true( ! $reviewed_draft_default['valid'], 'A Review must not expose its draft source movie by default.' );
lunara_resolver_assert_true( in_array( 'movie_not_published', $reviewed_draft_default['warnings'], true ), 'Default Review draft rejection must expose a stable warning.' );

$reviewed_draft_admin = lunara_debrief_resolve_reviewed_movie( 98, array( 'require_published' => false ) );
lunara_resolver_assert_true( ! $reviewed_draft_admin['valid'], 'A Review-owned draft must remain invalid for public output during admin inspection.' );
lunara_resolver_assert_same( 12, $reviewed_draft_admin['movie_id'], 'Explicit draft resolution must retain the Review-owned movie ID.' );
lunara_resolver_assert_same( 'Draft Film', $reviewed_draft_admin['title'], 'Admin callers may explicitly inspect a Review-owned draft movie.' );

$fallback_draft_default = lunara_debrief_resolve_reviewed_movie( 94 );
lunara_resolver_assert_same( 0, $fallback_draft_default['movie_id'], 'Default fallback lookup must not select a draft Movie.' );
lunara_resolver_assert_true( in_array( 'review_movie_not_found', $fallback_draft_default['warnings'], true ), 'Rejected draft fallback must expose the not-found warning.' );

$fallback_private_default = lunara_debrief_resolve_reviewed_movie( 95 );
lunara_resolver_assert_same( 0, $fallback_private_default['movie_id'], 'Default fallback lookup must not select a private Movie.' );
lunara_resolver_assert_true( in_array( 'review_movie_not_found', $fallback_private_default['warnings'], true ), 'Rejected private fallback must expose the not-found warning.' );

$fallback_draft_admin = lunara_debrief_resolve_reviewed_movie( 94, array( 'require_published' => false ) );
lunara_resolver_assert_same( 12, $fallback_draft_admin['movie_id'], 'Admin fallback lookup may explicitly inspect a draft Movie.' );
lunara_resolver_assert_same( 'Draft Film', $fallback_draft_admin['title'], 'Draft fallback inspection must return local Movie data.' );
lunara_resolver_assert_true( ! $fallback_draft_admin['valid'], 'An inspected draft fallback must remain invalid for public output.' );

$fallback_private_admin = lunara_debrief_resolve_reviewed_movie( 95, array( 'require_published' => false ) );
lunara_resolver_assert_same( 14, $fallback_private_admin['movie_id'], 'Admin fallback lookup may explicitly inspect a private Movie.' );
lunara_resolver_assert_same( 'Private Film', $fallback_private_admin['title'], 'Private fallback inspection must return local Movie data.' );
lunara_resolver_assert_true( ! $fallback_private_admin['valid'], 'An inspected private fallback must remain invalid for public output.' );

$titleless = lunara_debrief_resolve_movie( 15 );
lunara_resolver_assert_true( ! $titleless['valid'], 'A published Movie without a title must be invalid.' );
lunara_resolver_assert_true( in_array( 'missing_title', $titleless['warnings'], true ), 'A titleless Movie must expose a stable warning.' );

$without_imdb = lunara_debrief_resolve_movie( 16 );
lunara_resolver_assert_true( ! $without_imdb['valid'], 'A published Movie without an IMDb ID must be invalid.' );
lunara_resolver_assert_true( in_array( 'missing_imdb_title_id', $without_imdb['warnings'], true ), 'A Movie without an IMDb ID must expose a stable warning.' );

$invalid = lunara_debrief_resolve_movie( 99 );
lunara_resolver_assert_true( ! $invalid['valid'], 'Non-movie posts must be rejected.' );
lunara_resolver_assert_true( in_array( 'invalid_movie', $invalid['warnings'], true ), 'Invalid post types must expose a stable warning.' );

$resolver_source = file_get_contents( dirname( __DIR__, 2 ) . '/inc/debrief-resolver.php' );
foreach ( array( 'wp_remote_get', 'get_title_visual_package', 'get_tmdb_data_for_imdb_id', 'lunara_get_title_poster_html' ) as $forbidden ) {
    lunara_resolver_assert_true( false === strpos( $resolver_source, $forbidden ), 'Resolver contains forbidden remote-capable dependency: ' . $forbidden );
}

echo json_encode(
    array(
        'valid'          => $theme_film['valid'],
        'movie_id'       => $theme_film['movie_id'],
        'poster_source'  => $theme_film['poster_source'],
        'review_movie'   => $reviewed['movie_id'],
        'awards_opt_in'  => $with_awards['awards_resolved'],
        'remote_calls'   => 0,
    ),
    JSON_UNESCAPED_SLASHES
);
