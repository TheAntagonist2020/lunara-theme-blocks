<?php
/** Render the real article templates with a small read-only WordPress fixture. */
define( 'ABSPATH', __DIR__ . '/' );
$article_case = array();
$article_loop_done = false;
$article_mods = array();
$article_filters = array();
function __( $text, $domain = '' ) { return $text; }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_attr( $value ); }
function esc_url_raw( $value ) { return (string) $value; }
function esc_html__( $value, $domain = '' ) { return esc_html( $value ); }
function esc_attr__( $value, $domain = '' ) { return esc_attr( $value ); }
function esc_html_e( $value, $domain = '' ) { echo esc_html( $value ); }
function esc_attr_e( $value, $domain = '' ) { echo esc_attr( $value ); }
function sanitize_text_field( $value ) { return strip_tags( (string) $value ); }
function sanitize_html_class( $value ) { return preg_replace( '/[^a-zA-Z0-9_-]/', '', $value ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function wp_kses_post( $value ) { return $value; }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function trailingslashit( $value ) { return rtrim( $value, '/' ) . '/'; }
function get_stylesheet_directory() { return dirname( __DIR__ ); }
function get_stylesheet_directory_uri() { return 'https://example.test/theme'; }
function wp_enqueue_style( $handle, $uri, $deps, $version, $media ) {
    $GLOBALS['article_styles'][ $handle ] = array( 'uri' => $uri, 'deps' => $deps, 'version' => $version, 'media' => $media );
}
function wp_print_styles( $handles ) {
    foreach ( $handles as $handle ) {
        if ( ! empty( $GLOBALS['article_printed'][ $handle ] ) ) { continue; }
        $style = $GLOBALS['article_styles'][ $handle ];
        echo '<link rel="stylesheet" id="' . esc_attr( $handle ) . '-css" href="' . esc_attr( $style['uri'] . '?ver=' . $style['version'] ) . '" media="' . esc_attr( $style['media'] ) . '" />';
        $GLOBALS['article_printed'][ $handle ] = true;
    }
}
function add_action() {}
function add_shortcode() {}
function remove_filter() {}
function add_filter( $hook, $callback ) { $GLOBALS['article_filters'][ $hook ][] = $callback; }
function apply_filters( $hook, $value ) { foreach ( $GLOBALS['article_filters'][ $hook ] ?? array() as $callback ) { $value = $callback( $value ); } return $value; }
function is_singular( $type ) { return $type === $GLOBALS['article_case']['type']; }
function is_wp_error() { return false; }
function get_theme_mod( $key, $default = false ) { return apply_filters( 'theme_mod_' . $key, $GLOBALS['article_mods'][ $key ] ?? $default ); }
function get_post_type( $id = 0 ) { return $id >= 100 ? 'attachment' : $GLOBALS['article_case']['type']; }
function get_the_ID() { return 7; }
function get_post_field( $key, $id = 0 ) { return 'post_author' === $key ? 1 : get_the_content(); }
function get_the_author_meta() { return 'Dalton Johnson'; }
function get_the_date() { return 'September 13, 2026'; }
function get_the_title( $id = 0 ) { if ( is_object( $id ) ) { return $id->post_title; } return $id >= 100 ? 'Production still' : $GLOBALS['article_case']['title']; }
function the_title() { echo esc_html( get_the_title() ); }
function get_post_meta( $id, $key, $single = true ) {
    if ( '_wp_attachment_image_alt' === $key ) { return 'A film scene with a bright window on the left'; }
    if ( '_lunara_review_standfirst' === $key ) { return 'A close reading of the film, its choices, and the details that stay with us.'; }
    if ( '_lunara_journal_carousel_ids' === $key && 'journal' === $GLOBALS['article_case']['type'] && 'normal' === $GLOBALS['article_case']['scenario'] ) { $gallery_count = getenv( 'LUNARA_TEST_GALLERY_COUNT' ); return '0' === $gallery_count ? array() : ( '1' === $gallery_count ? array( 102 ) : array( 102, 103 ) ); }
    return '';
}
function get_the_content() {
    return '<p>The camera stays with the room long enough for us to notice the details. Light at the window gives the scene its shape, while the conversation leaves room for a different reading.</p><h2>A scene worth returning to</h2><p>Good criticism makes an argument in ordinary language. It connects a film to the world around it without losing the images, performances, or surprises that first held our attention.</p><p>For further context, read <a href="https://example.test/context">https://example.test/' . str_repeat( 'cinema', 20 ) . '</a> and return to the film.</p><p>The ending rewards another look. Its last image asks us to reconsider what came before, making the familiar feel strange again.</p>';
}
function has_excerpt() { return true; }
function get_the_excerpt() { return 'A close reading of the film and its place in the current conversation.'; }
function lunara_get_post_reading_time() { return '6 min read'; }
function has_shortcode() { return false; }
function get_the_terms() { return false; }
function get_the_tags() { return false; }
function wp_link_pages() {}
function wp_reset_postdata() {}
function get_post_type_archive_link( $type ) { return 'https://example.test/' . ( 'review' === $type ? 'reviews' : 'journal' ) . '/'; }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function get_header() {}
function get_footer() {}
function have_posts() { return ! $GLOBALS['article_loop_done']; }
function the_post() { $GLOBALS['article_loop_done'] = true; }
function post_class( $class ) { echo 'class="' . esc_attr( $class ) . '"'; }
class WP_Query { public function __construct( $args = array() ) {} public function have_posts() { return false; } }
// Conditional: template-main-landmarks-runtime.php includes this fixture with its own seams.
if ( ! class_exists( 'WP_Post' ) ) { class WP_Post { public $ID; public $post_title; public function __construct( $id, $title ) { $this->ID = $id; $this->post_title = $title; } } }
// Journal neighbours: both directions normally, only an older entry for the long-title case.
if ( ! function_exists( 'get_adjacent_post' ) ) {
    function get_adjacent_post( $in_same_term = false, $excluded = '', $previous = true ) {
        if ( 'journal' !== $GLOBALS['article_case']['type'] || 'missing' === $GLOBALS['article_case']['scenario'] ) { return null; }
        if ( $previous ) { return new WP_Post( 201, 'An older dispatch about the festival circuit and the films that travel furthest' ); }
        return 'long-title' === $GLOBALS['article_case']['scenario'] ? null : new WP_Post( 202, 'Newer entry' );
    }
}
if ( ! function_exists( 'get_permalink' ) ) { function get_permalink( $post = 0 ) { return 'https://example.test/journal/entry-' . ( is_object( $post ) ? $post->ID : (int) $post ) . '/'; } }
function has_post_thumbnail( $id = 0 ) { return 'missing' !== $GLOBALS['article_case']['art']; }
function get_post_thumbnail_id( $id = 0 ) { return has_post_thumbnail() ? 101 : 0; }
function wp_get_attachment_caption( $id ) { return ''; }
function wp_attachment_is_image( $id ) { return $id >= 100; }
function wp_get_attachment_image( $id, $size, $icon = false, $attrs = array() ) {
    $cropped = 'lunara-hero-spotlight' === $size;
    $poster = ! $cropped && 'poster' === $GLOBALS['article_case']['art'] && 101 === $id;
    $source = 'https://example.test/' . ( $cropped ? 'hard-cropped' : ( $poster ? 'poster' : 'landscape' ) ) . '.svg';
    $attrs = array_merge( array( 'src' => $source, 'srcset' => $source . ' ' . ( $poster ? 1000 : 1920 ) . 'w', 'data-fixture-image-size' => $size, 'width' => $poster ? 1000 : 1920, 'height' => $poster ? 1500 : 1080 ), $attrs );
    $html = '<img'; foreach ( $attrs as $key => $value ) { $html .= ' ' . $key . '="' . esc_attr( $value ) . '"'; } return $html . ' />';
}
function get_the_post_thumbnail( $id, $size, $attrs = array() ) { return wp_get_attachment_image( 101, $size, false, $attrs ); }
function lunara_get_review_card_meta() { return '2026 · Film review'; }
function lunara_get_review_visual_slot_data( $id, $slot ) {
    if ( 'missing' === $GLOBALS['article_case']['art'] ) { return array(); }
    return array( 'url' => 'https://example.test/' . $GLOBALS['article_case']['art'] . '.svg', 'alt' => 'A film ' . $GLOBALS['article_case']['art'], 'caption' => '', 'label' => 'Film still' );
}
function lunara_get_review_image_profile( $classes ) {
    $poster = false !== strpos( $classes, 'poster-hero' );
    return array( 'width' => $poster ? 1000 : 1920, 'height' => $poster ? 1500 : 1080, 'sizes' => $poster ? '420px' : '100vw' );
}

// Extract a real named function, respecting braces inside quoted strings.
function article_fixture_function( $file, $name ) {
    $source = file_get_contents( $file );
    $start = strpos( $source, 'function ' . $name . '(' );
    if ( false === $start ) { throw new RuntimeException( 'Missing real renderer ' . $name ); }
    $tokens = token_get_all( '<?php ' . substr( $source, $start ) );
    $depth = 0; $opened = false; $code = '';
    foreach ( array_slice( $tokens, 1 ) as $token ) {
        $text = is_array( $token ) ? $token[1] : $token;
        $code .= $text;
        if ( ! is_array( $token ) && '{' === $token ) { ++$depth; $opened = true; }
        if ( ! is_array( $token ) && '}' === $token && 0 === --$depth && $opened ) { break; }
    }
    eval( $code );
}
$root = dirname( __DIR__ );
article_fixture_function( $root . '/inc/setup.php', 'lunara_resolve_theme_asset' );
article_fixture_function( $root . '/inc/setup.php', 'lunara_theme_asset_version' );
article_fixture_function( $root . '/inc/review-rendering.php', 'lunara_render_review_visual_slot' );
// The Journal lead-image alt resolves through the real Journal-family helpers.
if ( ! function_exists( 'sanitize_key' ) ) { function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); } }
foreach ( array( 'lunara_journal_field_has_value', 'lunara_get_journal_field_value', 'lunara_get_journal_hero_alt' ) as $journal_helper ) { article_fixture_function( $root . '/inc/journal-family.php', $journal_helper ); }
article_fixture_function( $root . '/inc/frontend.php', 'lunara_output_journal_single_guardrail_css' );
require_once $root . '/inc/site-studio-journal-single.php';
article_fixture_function( $root . '/inc/site-studio-adapters.php', 'lunara_site_studio_mod_surface_read_state' );
article_fixture_function( $root . '/inc/site-studio-adapters.php', 'lunara_site_studio_mod_surface_desired_snapshot' );
// Request authorization and schema rejection have their own provider runtime.
// This fixture starts at its accepted state and exercises the real read filters.
function lunara_site_studio_preview_state_safe( $surface, $state ) { return 'journal-single' === $surface && is_array( $state ); }
article_fixture_function( $root . '/inc/site-studio-preview.php', 'lunara_site_studio_preview_install_state' );
$fixtures = array();
foreach ( array( 'review', 'journal' ) as $type ) {
    $scenarios = 'journal' === $type ? array( 'normal', 'long-title', 'missing', 'framed-small', 'framed-large' ) : array( 'normal', 'long-title', 'missing' );
    foreach ( $scenarios as $scenario ) {
        $article_case = array( 'type' => $type, 'scenario' => $scenario, 'title' => 'The films we carry out of the theater', 'art' => 'review' === $type ? 'poster' : 'landscape' );
        $article_mods = array();
        $article_filters = array();
        if ( 'long-title' === $scenario ) { $article_case['title'] = 'A late summer conversation about the films that surprise us, the stories we revisit, and ' . str_repeat( 'Cinema', 14 ); $article_case['art'] = 'landscape'; }
        if ( 'missing' === $scenario ) { $article_case['art'] = 'missing'; }
        if ( 0 === strpos( $scenario, 'framed-' ) ) {
            $small = 'framed-small' === $scenario;
            $article_case['art'] = 'poster';
            $article_mods = array( 'lunara_journal_single_hero_title_size' => $small ? 48 : 120, 'lunara_journal_single_image_fit' => $small ? 'cover' : 'contain', 'lunara_journal_single_image_position_x' => $small ? 0 : 100, 'lunara_journal_single_image_position_y' => $small ? 100 : 0, 'lunara_journal_show_byline' => ! $small, 'lunara_journal_show_date' => false, 'lunara_journal_show_reading_time' => true );
        }
        $desired_mods = $article_mods;
        $is_private = 0 === strpos( $scenario, 'framed-' );
        if ( $is_private ) {
            $candidate = lunara_site_studio_journal_single_read_state();
            $article_mods = array();
            if ( ! lunara_site_studio_preview_install_state( 'journal-single', $candidate, 0 ) ) { throw new RuntimeException( 'Private Journal read filters failed.' ); }
        }
        $article_loop_done = false;
        ob_start(); include $root . '/single-' . $type . '.php'; $html = ob_get_clean();
        $GLOBALS['article_styles'] = array(); $GLOBALS['article_printed'] = array();
        ob_start(); lunara_output_journal_single_guardrail_css(); $authority = ob_get_clean();
        if ( $is_private && array() !== $article_mods ) { throw new RuntimeException( 'Private template read changed public settings.' ); }
        $fixtures[ $type . '-' . $scenario ] = array( 'type' => $type, 'scenario' => $scenario, 'title' => $article_case['title'], 'art' => $article_case['art'], 'html' => $html, 'authority' => $authority, 'mods' => $desired_mods, 'private' => $is_private, 'public_unchanged' => ! $is_private || array() === $article_mods );
    }
}
echo json_encode( $fixtures, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR );
