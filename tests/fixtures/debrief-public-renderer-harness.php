<?php
/**
 * Isolated contract harness for the atomic public Debrief renderer.
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['lunara_test'] = array();

function lunara_test_reset( $review_id ) {
    $GLOBALS['lunara_test'] = array(
        'review_id'          => (int) $review_id,
        'environment'        => 'staging',
        'home_url'           => 'https://example.wpcomstaging.com/',
        'switch'             => true,
        'filter_override'    => null,
        'filter_calls'       => 0,
        'meta'               => array(),
        'titles'             => array( (int) $review_id => 'Harness Review' ),
        'records'            => array(),
        'validations'        => array(),
        'movies'             => array(),
        'core_calls'         => 0,
        'validation_calls'   => 0,
        'validation_strict'  => array(),
        'resolver_calls'     => array(),
        'legacy_calls'       => 0,
        'split_calls'        => 0,
        'legacy_media_calls' => 0,
        'parser_calls'       => 0,
        'parser_inputs'      => array(),
        'watch_calls'        => 0,
        'image_calls'        => array(),
        'remote_calls'       => 0,
        'oscar_ledger_calls' => 0,
    );
}

function lunara_test_assert( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "Assertion failed: {$message}\n" );
        exit( 1 );
    }
}

function lunara_test_record( $review_id, $status = 'published', $movie_ids = array( 1, 2, 3, 4 ) ) {
    return array(
        'status'        => $status,
        'review_id'     => (int) $review_id,
        'reviewed_film' => array( 'movie_id' => (int) $movie_ids[0] ),
        'pairings'      => array(
            array(
                'role'             => 'theme_echo',
                'film'             => array( 'movie_id' => (int) $movie_ids[1] ),
                'editorial_reason' => 'Carries the central idea forward.',
            ),
            array(
                'role'             => 'counter_program',
                'film'             => array( 'movie_id' => (int) $movie_ids[2] ),
                'editorial_reason' => 'Changes the temperature <script>alert(1)</script>.',
            ),
            array(
                'role'             => 'career_context',
                'film'             => array( 'movie_id' => (int) $movie_ids[3] ),
                'editorial_reason' => 'Deepens the career context.',
            ),
        ),
        'editor_note'   => 'PRIVATE EDITOR NOTE',
    );
}

function lunara_test_movie( $movie_id, $poster_id = null ) {
    $movie_id = (int) $movie_id;
    if ( null === $poster_id ) {
        $poster_id = 1000 + $movie_id;
    }

    return array(
        'valid'                => true,
        'movie_id'             => $movie_id,
        'post_status'          => 'publish',
        'title'                => 'Movie ' . $movie_id,
        'year'                 => (string) ( 2000 + $movie_id ),
        'imdb_title_id'        => 'tt' . str_pad( (string) $movie_id, 7, '0', STR_PAD_LEFT ),
        'permalink'            => 'https://example.com/movies/movie-' . $movie_id . '/',
        'imdb_url'             => 'https://www.imdb.com/title/tt' . str_pad( (string) $movie_id, 7, '0', STR_PAD_LEFT ) . '/',
        'poster_attachment_id' => (int) $poster_id,
        'oscar_counts'         => array( 'noms' => 2, 'wins' => 1 ),
    );
}

function lunara_test_prime_legacy_meta( $review_id ) {
    $GLOBALS['lunara_test']['meta'][ $review_id ] = array(
        '_lunara_score'           => '4.5',
        '_lunara_year'            => '2026',
        '_lunara_where'           => 'Cinema',
        '_lunara_theme_echo'      => 'LEGACY THEME (2001) - Theme note | tt0000101',
        '_lunara_counter_program' => 'LEGACY COUNTER (2002) - Counter note | tt0000102',
        '_lunara_career_context'  => 'LEGACY CAREER (2003) - Career note | tt0000103',
        'theme_echo_movie'        => '9991',
        'counter_program_movie'   => '9992',
        'career_context_movie'    => '9993',
    );
}

function absint( $value ) {
    return abs( (int) $value );
}

function wp_get_environment_type() {
    return $GLOBALS['lunara_test']['environment'];
}

function home_url( $path = '/' ) {
    return rtrim( $GLOBALS['lunara_test']['home_url'], '/' ) . '/' . ltrim( (string) $path, '/' );
}

function get_theme_mod( $key, $default = false ) {
    if ( 'lunara_debrief_public_renderer_enabled' === $key ) {
        return $GLOBALS['lunara_test']['switch'];
    }
    if ( 'lunara_debrief_kicker_text' === $key ) {
        return 'A LUNARA FILM SIGNATURE';
    }
    return $default;
}

function apply_filters( $tag, $value ) {
    if ( 'lunara_debrief_public_renderer_enabled' === $tag ) {
        ++$GLOBALS['lunara_test']['filter_calls'];
        if ( null !== $GLOBALS['lunara_test']['filter_override'] ) {
            return (bool) $GLOBALS['lunara_test']['filter_override'];
        }
    }
    return $value;
}

function get_the_ID() {
    return (int) $GLOBALS['lunara_test']['review_id'];
}

function get_post_meta( $post_id, $key, $single = true ) {
    return $GLOBALS['lunara_test']['meta'][ (int) $post_id ][ $key ] ?? '';
}

function get_the_title( $post_id = 0 ) {
    return $GLOBALS['lunara_test']['titles'][ (int) $post_id ] ?? 'Harness Review';
}

function __( $text, $domain = null ) {
    return $text;
}

function esc_html( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

function esc_attr( $text ) {
    return esc_html( $text );
}

function esc_html__( $text, $domain = null ) {
    return esc_html( $text );
}

function esc_attr__( $text, $domain = null ) {
    return esc_attr( $text );
}

function esc_url( $url ) {
    $url = trim( (string) $url );
    if ( preg_match( '/^(?:javascript|data):/i', $url ) ) {
        return '';
    }
    return esc_attr( $url );
}

function wp_strip_all_tags( $html ) {
    return strip_tags( (string) $html );
}

function wp_kses_post( $html ) {
    return preg_replace( '#<script\b[^>]*>.*?</script>#is', '', (string) $html );
}

function wp_get_attachment_image( $attachment_id, $size, $icon = false, $attrs = array() ) {
    $GLOBALS['lunara_test']['image_calls'][] = array(
        'attachment_id' => (int) $attachment_id,
        'size'          => $size,
        'attrs'         => $attrs,
    );

    $html = '<img data-attachment="' . (int) $attachment_id . '" data-size="' . esc_attr( $size ) . '"';
    foreach ( $attrs as $key => $value ) {
        $html .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
    }
    return $html . '>';
}

function lunara_debrief_get_review_record( $review_id ) {
    ++$GLOBALS['lunara_test']['core_calls'];
    return $GLOBALS['lunara_test']['records'][ (int) $review_id ] ?? array( 'status' => 'incomplete' );
}

function lunara_debrief_validate_record( $record, $strict = null ) {
    ++$GLOBALS['lunara_test']['validation_calls'];
    $GLOBALS['lunara_test']['validation_strict'][] = $strict;
    $review_id = (int) ( $record['review_id'] ?? 0 );
    if ( isset( $GLOBALS['lunara_test']['validations'][ $review_id ] ) ) {
        return $GLOBALS['lunara_test']['validations'][ $review_id ];
    }
    return array(
        'valid'    => true,
        'complete' => true,
        'record'   => $record,
    );
}

function lunara_debrief_resolve_movie( $movie_id, $args = array() ) {
    $GLOBALS['lunara_test']['resolver_calls'][] = array(
        'movie_id' => (int) $movie_id,
        'args'     => $args,
    );
    return $GLOBALS['lunara_test']['movies'][ (int) $movie_id ] ?? array(
        'valid'       => false,
        'movie_id'    => (int) $movie_id,
        'post_status' => 'draft',
    );
}

function lunara_debrief_shortcode( $atts ) {
    ++$GLOBALS['lunara_test']['legacy_calls'];
    return '<section class="legacy-block">LEGACY BLOCK ' . get_the_ID() . '</section>';
}

function lunara_split_review_debrief_block( $html ) {
    ++$GLOBALS['lunara_test']['split_calls'];
    return array(
        'signature' => '<section class="legacy-signature">LEGACY SIGNATURE</section>',
        'pairings'  => '<section class="legacy-split-pairings">LEGACY SPLIT PAIRINGS</section>',
    );
}

function lunara_get_review_debrief_signature_media_html( $review_id ) {
    ++$GLOBALS['lunara_test']['legacy_media_calls'];
    return '<aside class="legacy-media">LEGACY MEDIA ' . (int) $review_id . '</aside>';
}

function lunara_parse_pair_it_with_value( $value, $review_id = 0 ) {
    ++$GLOBALS['lunara_test']['parser_calls'];
    $GLOBALS['lunara_test']['parser_inputs'][] = (string) $value;
    preg_match( '/\b(tt\d{7,8})\b/i', (string) $value, $matches );
    $tt    = strtolower( $matches[1] ?? '' );
    $title = trim( (string) preg_replace( '/\s*[|-].*$/', '', (string) $value ) );

    return array(
        'tt'              => $tt,
        'title'           => $title,
        'title_base'      => $title,
        'year'            => '2001',
        'note'            => 'Legacy note',
        'title_href'      => 'https://example.com/legacy/' . rawurlencode( $title ),
        'title_href_type' => 'review',
        'imdb_href'       => '' !== $tt ? 'https://www.imdb.com/title/' . $tt . '/' : '',
        'counts'          => array( 'noms' => 0, 'wins' => 0 ),
        'poster_html'     => '<img class="legacy-poster" alt="Legacy poster">',
    );
}

function lunara_pair_render_where_to_watch( $tt, $review_id = 0 ) {
    ++$GLOBALS['lunara_test']['watch_calls'];
    return '';
}

function lunara_render_oscar_ledger_pill( $tt, $counts = null ) {
    ++$GLOBALS['lunara_test']['oscar_ledger_calls'];
    $noms = (int) ( $counts['noms'] ?? 0 );
    if ( $noms <= 0 ) {
        return '';
    }
    return '<a class="lunara-oscar-ledger" href="/oscars/title/' . esc_attr( $tt ) . '/">Oscar Ledger</a>';
}

function lunara_render_stars( $score ) {
    return '<span class="lunara-stars">' . esc_html( $score ) . '</span>';
}

function lunara_render_review_where_links( $where, $title = '', $watch_url = '' ) {
    return '<span class="where-link">' . esc_html( $where ) . '</span>';
}

function wp_remote_get( $url, $args = array() ) {
    ++$GLOBALS['lunara_test']['remote_calls'];
    return array();
}

require dirname( __DIR__, 2 ) . '/inc/debrief-public.php';

// Production cannot be enabled, even by the filter.
lunara_test_reset( 101 );
lunara_test_prime_legacy_meta( 101 );
$GLOBALS['lunara_test']['environment']     = 'production';
$GLOBALS['lunara_test']['home_url']        = 'https://lunarafilm.com/';
$GLOBALS['lunara_test']['switch']          = true;
$GLOBALS['lunara_test']['filter_override'] = true;
$production = lunara_get_review_debrief_render_parts( 101 );
lunara_test_assert( 'legacy' === $production['source'], 'Production must stay on the legacy renderer.' );
lunara_test_assert( 0 === $GLOBALS['lunara_test']['filter_calls'], 'Production guard must return before the enable filter.' );
lunara_test_assert( 0 === $GLOBALS['lunara_test']['core_calls'], 'Production must not call the canonical Core adapter.' );
lunara_test_assert( 0 === count( $GLOBALS['lunara_test']['resolver_calls'] ), 'Production must not call the resolver.' );

// WordPress.com staging suffix is recognized, but the explicit switch remains off.
lunara_test_reset( 102 );
lunara_test_prime_legacy_meta( 102 );
$GLOBALS['lunara_test']['environment'] = 'production';
$GLOBALS['lunara_test']['switch']      = false;
lunara_test_assert( lunara_is_debrief_renderer_staging_environment(), 'WordPress.com staging host must be recognized.' );
$switch_off = lunara_get_review_debrief_render_parts( 102 );
lunara_test_assert( 'legacy' === $switch_off['source'], 'Switch-off staging request must use legacy.' );
lunara_test_assert( 0 === $GLOBALS['lunara_test']['core_calls'], 'Switch off must make zero canonical Core calls.' );
lunara_test_assert( 0 === count( $GLOBALS['lunara_test']['resolver_calls'] ), 'Switch off must make zero resolver calls.' );

// A complete, explicitly published record renders atomically from local data.
lunara_test_reset( 201 );
lunara_test_prime_legacy_meta( 201 );
$record = lunara_test_record( 201 );
$GLOBALS['lunara_test']['records'][201] = $record;
foreach ( array( 1, 2, 3, 4 ) as $movie_id ) {
    $GLOBALS['lunara_test']['movies'][ $movie_id ] = lunara_test_movie( $movie_id );
}
$canonical = lunara_get_review_debrief_render_parts( 201 );
$canonical_html = $canonical['signature_html'] . $canonical['media_html'] . $canonical['pairings_html'];
lunara_test_assert( 'canonical' === $canonical['source'], 'Valid published record must use canonical output.' );
lunara_test_assert( 4 === count( $GLOBALS['lunara_test']['resolver_calls'] ), 'Canonical output must resolve the source plus exactly three companions.' );
lunara_test_assert( array( 1, 2, 3, 4 ) === array_column( $GLOBALS['lunara_test']['resolver_calls'], 'movie_id' ), 'Resolver order must be source, Theme Echo, Counter-Program, Career Context.' );
foreach ( $GLOBALS['lunara_test']['resolver_calls'] as $call ) {
    lunara_test_assert( true === $call['args']['require_published'], 'Canonical resolver must require published Movies.' );
    lunara_test_assert( true === $call['args']['resolve_poster'], 'Canonical resolver must opt into local posters.' );
    lunara_test_assert( false === $call['args']['allow_aat_local_poster'], 'Canonical resolver must not depend on the Oscars plugin poster library.' );
    lunara_test_assert( false === $call['args']['resolve_awards'], 'Canonical resolver must not query Oscar data.' );
}
lunara_test_assert( array( true ) === $GLOBALS['lunara_test']['validation_strict'], 'Canonical validation must be strict.' );
lunara_test_assert( 0 === $GLOBALS['lunara_test']['legacy_calls'], 'Canonical output must not call the legacy shortcode.' );
lunara_test_assert( 0 === $GLOBALS['lunara_test']['parser_calls'], 'Canonical output must not call the legacy parser.' );
lunara_test_assert( 0 === $GLOBALS['lunara_test']['legacy_media_calls'], 'Canonical output must not call the legacy poster helper.' );
lunara_test_assert( 0 === $GLOBALS['lunara_test']['remote_calls'], 'Canonical output must make no remote request.' );
lunara_test_assert( 0 === $GLOBALS['lunara_test']['oscar_ledger_calls'], 'Canonical output must make no Oscar Ledger call.' );
lunara_test_assert( false === strpos( $canonical_html, 'lunara-oscar-ledger' ), 'Canonical output must remain complete without Oscar markup.' );
lunara_test_assert( 3 === substr_count( $canonical['pairings_html'], 'class="lunara-pair-card lunara-pair-card--' ), 'Canonical output must contain exactly three cards.' );
lunara_test_assert( false !== strpos( $canonical['pairings_html'], 'data-count="3"' ), 'Canonical output must declare three cards.' );
$theme_pos   = strpos( $canonical['pairings_html'], 'lunara-pair-card--theme' );
$counter_pos = strpos( $canonical['pairings_html'], 'lunara-pair-card--counter' );
$career_pos  = strpos( $canonical['pairings_html'], 'lunara-pair-card--career' );
lunara_test_assert( $theme_pos < $counter_pos && $counter_pos < $career_pos, 'Canonical role order must be fixed.' );
lunara_test_assert( false === strpos( $canonical_html, '<script>' ), 'Canonical editorial reasons must be escaped.' );
lunara_test_assert( false !== strpos( $canonical_html, '&lt;script&gt;' ), 'Escaped reason must remain visible as text.' );
lunara_test_assert( false === strpos( $canonical_html, 'PRIVATE EDITOR NOTE' ), 'Private editor note must never render.' );
lunara_test_assert( 4 === count( $GLOBALS['lunara_test']['image_calls'] ), 'Canonical output must render four local attachment images.' );
foreach ( $GLOBALS['lunara_test']['image_calls'] as $image_call ) {
    lunara_test_assert( 'lunara-poster-library' === $image_call['size'], 'Canonical images must use the poster library size.' );
}
foreach ( $GLOBALS['lunara_test']['image_calls'] as $image_call ) {
    lunara_test_assert( 'lazy' === $image_call['attrs']['loading'], 'Below-fold Debrief posters must lazy load.' );
}

// A second read reuses the complete render decision.
$calls_before_cache = array(
    $GLOBALS['lunara_test']['core_calls'],
    $GLOBALS['lunara_test']['validation_calls'],
    count( $GLOBALS['lunara_test']['resolver_calls'] ),
    count( $GLOBALS['lunara_test']['image_calls'] ),
);
$canonical_cached = lunara_get_review_debrief_render_parts( 201 );
lunara_test_assert( $canonical === $canonical_cached, 'Cached result must be stable.' );
lunara_test_assert(
    $calls_before_cache === array(
        $GLOBALS['lunara_test']['core_calls'],
        $GLOBALS['lunara_test']['validation_calls'],
        count( $GLOBALS['lunara_test']['resolver_calls'] ),
        count( $GLOBALS['lunara_test']['image_calls'] ),
    ),
    'Cached read must not repeat Core, validation, resolver, or image work.'
);

// The current empty census state, Ready records, and invalid records remain wholly legacy.
lunara_test_reset( 207 );
lunara_test_prime_legacy_meta( 207 );
$GLOBALS['lunara_test']['records'][207] = array( 'status' => '', 'review_id' => 207 );
$unpublished = lunara_get_review_debrief_render_parts( 207 );
lunara_test_assert( 'legacy' === $unpublished['source'], 'Empty canonical status must remain wholly legacy.' );
lunara_test_assert( 0 === $GLOBALS['lunara_test']['validation_calls'], 'Empty canonical status must not enter strict validation.' );
lunara_test_assert( 0 === count( $GLOBALS['lunara_test']['resolver_calls'] ), 'Empty canonical status must not resolve Movies.' );

lunara_test_reset( 202 );
lunara_test_prime_legacy_meta( 202 );
$GLOBALS['lunara_test']['records'][202] = lunara_test_record( 202, 'ready' );
$ready = lunara_get_review_debrief_render_parts( 202 );
lunara_test_assert( 'legacy' === $ready['source'], 'Ready is not public and must fall back.' );
lunara_test_assert( 0 === $GLOBALS['lunara_test']['validation_calls'], 'Non-published records must not enter strict validation.' );
lunara_test_assert( 0 === count( $GLOBALS['lunara_test']['resolver_calls'] ), 'Non-published records must not resolve Movies.' );

lunara_test_reset( 203 );
lunara_test_prime_legacy_meta( 203 );
$invalid_record = lunara_test_record( 203 );
$GLOBALS['lunara_test']['records'][203] = $invalid_record;
$GLOBALS['lunara_test']['validations'][203] = array( 'valid' => false, 'complete' => false, 'record' => $invalid_record );
$invalid = lunara_get_review_debrief_render_parts( 203 );
lunara_test_assert( 'legacy' === $invalid['source'], 'Strict-invalid published record must fall back wholly.' );
lunara_test_assert( 0 === count( $GLOBALS['lunara_test']['resolver_calls'] ), 'Strict-invalid record must not resolve Movies.' );
lunara_test_assert( false === strpos( $invalid['pairings_html'], 'Movie 2' ), 'Invalid result must leak no canonical markup.' );

// A failure on the final companion emits no earlier canonical fragment.
lunara_test_reset( 204 );
lunara_test_prime_legacy_meta( 204 );
$GLOBALS['lunara_test']['records'][204] = lunara_test_record( 204, 'published', array( 11, 12, 13, 14 ) );
foreach ( array( 11, 12, 13 ) as $movie_id ) {
    $GLOBALS['lunara_test']['movies'][ $movie_id ] = lunara_test_movie( $movie_id );
}
$GLOBALS['lunara_test']['movies'][14] = array( 'valid' => false, 'movie_id' => 14, 'post_status' => 'draft' );
$late_failure = lunara_get_review_debrief_render_parts( 204 );
lunara_test_assert( 'legacy' === $late_failure['source'], 'Final companion failure must select the whole legacy module.' );
lunara_test_assert( 4 === count( $GLOBALS['lunara_test']['resolver_calls'] ), 'Atomic preflight must reach and reject the final companion.' );
lunara_test_assert( false === strpos( $late_failure['pairings_html'], 'Movie 12' ), 'No canonical card may leak before final validation succeeds.' );
lunara_test_assert( false !== strpos( $late_failure['pairings_html'], 'LEGACY THEME' ), 'Whole legacy cards must replace failed canonical output.' );

// Partial relational values never replace or mix with legacy fallback rows.
lunara_test_reset( 205 );
lunara_test_prime_legacy_meta( 205 );
$partial_record = lunara_test_record( 205 );
$GLOBALS['lunara_test']['records'][205] = $partial_record;
$GLOBALS['lunara_test']['validations'][205] = array( 'valid' => false, 'complete' => false, 'record' => $partial_record );
$partial = lunara_get_review_debrief_render_parts( 205 );
lunara_test_assert( 'legacy' === $partial['source'], 'Partial canonical data must use whole legacy fallback.' );
lunara_test_assert( 3 === $GLOBALS['lunara_test']['parser_calls'], 'Legacy fallback must parse only three legacy fields.' );
foreach ( $GLOBALS['lunara_test']['parser_inputs'] as $legacy_input ) {
    lunara_test_assert( 0 === strpos( $legacy_input, 'LEGACY ' ), 'Fallback parser must receive only legacy text values.' );
    lunara_test_assert( false === strpos( $legacy_input, '999' ), 'Fallback must not mix relational IDs into legacy rows.' );
}

// Missing local posters use controlled server-rendered fallbacks, not remote IO.
lunara_test_reset( 206 );
lunara_test_prime_legacy_meta( 206 );
$GLOBALS['lunara_test']['records'][206] = lunara_test_record( 206, 'published', array( 21, 22, 23, 24 ) );
$GLOBALS['lunara_test']['movies'][21] = lunara_test_movie( 21, 0 );
$GLOBALS['lunara_test']['movies'][22] = lunara_test_movie( 22 );
$GLOBALS['lunara_test']['movies'][23] = lunara_test_movie( 23, 0 );
$GLOBALS['lunara_test']['movies'][24] = lunara_test_movie( 24 );
$poster_fallback = lunara_get_review_debrief_render_parts( 206 );
lunara_test_assert( 'canonical' === $poster_fallback['source'], 'Missing optional posters must not invalidate a public Movie.' );
lunara_test_assert( '' === $poster_fallback['media_html'], 'Missing reviewed-film poster must leave the media lane empty.' );
lunara_test_assert( false !== strpos( $poster_fallback['pairings_html'], 'lunara-pair-card-poster--plate' ), 'Missing companion poster must render the controlled title plate.' );
lunara_test_assert( 0 === $GLOBALS['lunara_test']['remote_calls'], 'Poster fallback must remain local-only.' );

echo json_encode(
    array(
        'production_locked'   => true,
        'switch_off_zero'     => true,
        'canonical_atomic'    => true,
        'roles_rendered'      => 3,
        'resolver_calls'      => 4,
        'cache_verified'      => true,
        'late_failure_legacy' => true,
        'partial_no_mix'      => true,
        'empty_status_legacy' => true,
        'remote_calls'        => 0,
        'poster_fallback'     => true,
        'oscar_independent'   => true,
    ),
    JSON_UNESCAPED_SLASHES
);
