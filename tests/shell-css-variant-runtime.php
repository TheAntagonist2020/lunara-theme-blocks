<?php
/** Focused shell selection check; no WordPress installation or network needed. */
$root = dirname( __DIR__ );
$state = array();
$enqueued = array();

function shell_test_load_function( $path, $name ) {
    $tokens = token_get_all( file_get_contents( $path ) );
    foreach ( $tokens as $index => $token ) {
        if ( ! is_array( $token ) || T_FUNCTION !== $token[0] ) { continue; }
        $next = $index + 1;
        while ( isset( $tokens[ $next ] ) && is_array( $tokens[ $next ] ) && T_WHITESPACE === $tokens[ $next ][0] ) { ++$next; }
        if ( ! isset( $tokens[ $next ][1] ) || $name !== $tokens[ $next ][1] ) { continue; }
        $code = ''; $depth = 0; $opened = false;
        for ( $cursor = $index; $cursor < count( $tokens ); ++$cursor ) {
            $part = $tokens[ $cursor ];
            $code .= is_array( $part ) ? $part[1] : $part;
            if ( '{' === $part ) { ++$depth; $opened = true; }
            if ( '}' === $part && --$depth === 0 && $opened ) { eval( $code ); return; }
        }
    }
    throw new RuntimeException( 'Missing function: ' . $name );
}

function is_admin() { global $state; return ! empty( $state['admin'] ); }
function is_feed() { global $state; return ! empty( $state['feed'] ); }
function is_preview() { global $state; return ! empty( $state['preview'] ); }
function is_customize_preview() { global $state; return ! empty( $state['customize'] ); }
function is_page( $slug ) { global $state; return 'oscars' === $slug && 'portal' === ( $state['route'] ?? '' ); }
function is_page_template( $file ) { global $state; return 'page-oscars.php' === $file && 'portal-template' === ( $state['route'] ?? '' ); }
function lunara_resolve_theme_asset( $relative ) {
    global $state;
    if ( ! empty( $state['missing_variant'] ) && 'assets/css/lunara-shell-non-portal.css' === $relative ) { return array( 'uri' => '', 'path' => '' ); }
    return array( 'uri' => $relative, 'path' => $relative );
}
function lunara_theme_asset_version( $path ) { return 'fixture'; }
function wp_enqueue_style( $handle, $uri, $deps, $version ) {
    global $enqueued;
    $enqueued[] = compact( 'handle', 'uri', 'deps', 'version' );
}

$missing_detector = in_array( '--missing-detector', $argv, true );
if ( ! $missing_detector ) { shell_test_load_function( $root . '/inc/oscars-family.php', 'lunara_is_oscars_portal_route' ); }
shell_test_load_function( $root . '/inc/setup.php', 'lunara_shell_uses_non_portal_variant' );
shell_test_load_function( $root . '/inc/setup.php', 'lunara_enqueue_shell_styles' );

$cases = $missing_detector ? array( 'unknown detector' => array() ) : array(
    'public home' => array( 'route' => 'home', 'slim' => true ),
    'public journal' => array( 'route' => 'journal', 'slim' => true ),
    'public reviews' => array( 'route' => 'reviews', 'slim' => true ),
    'ledger' => array( 'route' => 'ledger', 'slim' => true ),
    'portal slug' => array( 'route' => 'portal' ),
    'portal template on another page' => array( 'route' => 'portal-template' ),
    'WordPress preview' => array( 'preview' => true ),
    'Customizer' => array( 'customize' => true ),
    'Studio context' => array( 'context' => array( 'surface' => 'site-footer' ) ),
    'Studio query' => array( 'query' => array( 'lunara_site_studio_instance' => 'fixture' ) ),
    'legacy preview query' => array( 'query' => array( 'lunara_journal_preview' => 'fixture' ) ),
    'carousel preview query' => array( 'query' => array( 'lunara_hero_carousel_preview' => 'fixture' ) ),
    'ordinary query' => array( 'query' => array( 'sort' => 'newest' ), 'slim' => true ),
    'missing generated file' => array( 'missing_variant' => true ),
    'admin' => array( 'admin' => true ),
    'feed' => array( 'feed' => true ),
);
foreach ( $cases as $label => $case ) {
    $state = $case;
    $_GET = $case['query'] ?? array();
    $GLOBALS['lunara_site_studio_preview_context'] = $case['context'] ?? array();
    $enqueued = array();
    lunara_enqueue_shell_styles();
    $expected = array( array(
        'handle' => 'lunara-shell',
        'uri' => ! empty( $case['slim'] ) ? 'assets/css/lunara-shell-non-portal.css' : 'assets/css/lunara-shell.css',
        'deps' => array( 'lunara-style' ),
        'version' => 'fixture',
    ) );
    if ( $expected !== $enqueued ) { throw new RuntimeException( 'FAIL: ' . $label . ': ' . json_encode( $enqueued ) ); }
}
echo 'PASS shell route selection: ' . count( $cases ) . " cases\n";
