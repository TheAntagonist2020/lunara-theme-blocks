<?php
/** Exercise actual templates inside the real header/footer main landmark. */
ob_start();
require __DIR__ . '/article-layout-runtime.php';
ob_end_clean();

function the_content() {
    if ( 'hub' === $GLOBALS['article_case']['type'] ) { echo '<h1>A curated film programme</h1>'; }
    echo get_the_content();
}
function wp_unslash( $value ) { return $value; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function sanitize_title( $value ) { return sanitize_key( $value ); }
function maybe_unserialize( $value ) { return $value; }
function get_permalink( $id = 0 ) { return 'https://example.test/film/fixture/'; }
function get_post_status( $id = 0 ) { return 'publish'; }
function wp_count_posts() { return (object) array( 'publish' => 1 ); }
function number_format_i18n( $value ) { return number_format( $value ); }
function add_query_arg( $key, $value, $url ) { return $url . '?' . rawurlencode( $key ) . '=' . rawurlencode( $value ); }
function paginate_links() { return '<a href="?paged=2">Next page</a>'; }
function wp_get_post_categories() { return array(); }
function get_category_by_slug() { return false; }
function get_page_by_path() { return false; }
function wp_trim_words( $value, $count = 55 ) { return implode( ' ', array_slice( preg_split( '/\s+/', strip_tags( $value ) ), 0, $count ) ); }
function _n( $single, $plural, $count ) { return 1 === $count ? $single : $plural; }
function the_post_thumbnail( $size, $attrs = array() ) { echo get_the_post_thumbnail( 7, $size, $attrs ); }
function lunara_entity_render_film_card( $id ) { return '<article class="fixture-entity-card">A film</article>'; }
function lunara_entity_render_person_card( $id ) { return '<article class="fixture-entity-card">A person</article>'; }

$header_source = file_get_contents( $root . '/header.php' );
$footer_source = file_get_contents( $root . '/footer.php' );
if ( ! preg_match( '/<main <\?php.*?\?>>/s', $header_source, $opening ) || ! preg_match( '/<\/main>/', $footer_source, $closing ) ) {
    throw new RuntimeException( 'The actual shared landmark boundaries are required.' );
}
// Execute the real opener without loading unrelated navigation services.
ob_start(); eval( '?>' . $opening[0] ); $main_open = ob_get_clean();
$checks = 0;
$assert = static function ( $passed, $message ) use ( &$checks ) {
    ++$checks;
    if ( ! $passed ) { throw new RuntimeException( $message ); }
};
$assert_landmark = static function ( $html, $label, $root_class, $has_title = true ) use ( $main_open, $closing, $assert ) {
    $document = '<!doctype html><html><body>' . $main_open . $html . $closing[0] . '</body></html>';
    $assert( 1 === preg_match_all( '/<main\b/i', $document ) && 1 === substr_count( $document, '</main>' ), $label . ': one server-emitted main pair.' );
    $dom = new DOMDocument();
    $previous = libxml_use_internal_errors( true ); $dom->loadHTML( $document ); libxml_clear_errors(); libxml_use_internal_errors( $previous );
    $xpath = new DOMXPath( $dom );
    $assert( 1 === $xpath->query( '//main' )->length, $label . ': one parsed main landmark.' );
    $assert( 1 === $xpath->query( '//main/div[contains(concat(" ",normalize-space(@class)," ")," ' . $root_class . ' ")]' )->length, $label . ': route styling stays on the direct neutral child.' );
    if ( $has_title ) { $assert( 1 === $xpath->query( '//main//h1' )->length, $label . ': the complete page heading remains inside main.' ); }
};

foreach ( $fixtures as $name => $fixture ) {
    $assert_landmark( $fixture['html'], $name, 'review' === $fixture['type'] ? 'lunara-review-single-page' : 'lunara-journal-single-page' );
}
$cases = array(
    array( 'index.php', 'post', 'lunara-archive-page', false ),
    array( 'index.php', 'post', 'lunara-archive-page', true ),
    array( 'template-lunara-hub.php', 'hub', 'lunara-hub-page', false ),
    array( 'single-movie.php', 'movie', 'lunara-film-dossier', false ),
    array( 'single-person.php', 'person', 'lunara-talent-page', false ),
    array( 'archive-movie.php', 'movie', 'lunara-entity-archive', false ),
    array( 'archive-movie.php', 'movie', 'lunara-entity-archive', true ),
    array( 'archive-person.php', 'person', 'lunara-entity-archive', false ),
    array( 'archive-person.php', 'person', 'lunara-entity-archive', true ),
    array( 'single.php', 'review', 'lunara-review-single-page', false ),
    array( 'single.php', 'post', 'lunara-editorial-single-page', false ),
);
$landmark_fixtures = array();
foreach ( $cases as list( $file, $type, $class, $empty ) ) {
    $article_case = array( 'type' => $type, 'scenario' => 'normal', 'title' => 'The films we carry out of the theater', 'art' => 'missing' );
    $article_mods = $article_filters = array(); $article_loop_done = $empty;
    ob_start(); include $root . '/' . $file; $html = ob_get_clean();
    $label = $file . ':' . $type . ( $empty ? ':empty' : ':populated' );
    $assert_landmark( $html, $label, $class, ! ( 'index.php' === $file && $empty ) );
    $landmark_fixtures[ $label ] = array( 'html' => $html, 'type' => $type, 'file' => $file );
}
echo 'Template main landmarks: ' . $checks . " checks passed.\n";
if ( in_array( '--fixtures', $argv, true ) ) { echo "LUNARA_LANDMARK_FIXTURES\n" . json_encode( $landmark_fixtures, JSON_THROW_ON_ERROR ); }
