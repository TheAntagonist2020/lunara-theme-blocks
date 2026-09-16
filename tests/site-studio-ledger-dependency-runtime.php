<?php
/** Real dependency and registry boundary, without replacing the dependency callback. */
define( 'ABSPATH', __DIR__ . '/' );
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function __( $text, $domain = '' ) { return $text; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function is_wp_error( $value ) { return false; }
require dirname( __DIR__ ) . '/inc/oscars-family.php';
require dirname( __DIR__ ) . '/inc/site-studio-adapters.php';
require dirname( __DIR__ ) . '/inc/site-studio-registry.php';
$surface = array( 'id' => 'oscars-ledger', 'available' => true, 'dependency_callback' => 'lunara_site_studio_oscars_ledger_dependency' );
$checks = 0;
function ledger_dependency_check( $condition, $message ) {
    ++$GLOBALS['checks'];
    if ( ! $condition ) { throw new RuntimeException( $message ); }
}
ledger_dependency_check( null === lunara_oscars_reader(), 'Missing Academy must return no reader.' );
ledger_dependency_check( false === lunara_site_studio_oscars_ledger_dependency(), 'Missing Academy must keep the editor unavailable.' );
ledger_dependency_check( 'missing_dependency' === lunara_site_studio_surface_availability( $surface )['reason'], 'Registry must explain a missing plugin.' );
// Runtime declaration allows the same process to test absent then present.
eval( 'class Academy_Awards_Table {
    public static $instance;
    public static function get_instance() { return self::$instance; }
}' );
Academy_Awards_Table::$instance = new Academy_Awards_Table();
ledger_dependency_check( Academy_Awards_Table::$instance === lunara_oscars_reader(), 'The real theme boundary must return the plugin singleton.' );
ledger_dependency_check( true === lunara_site_studio_oscars_ledger_dependency(), 'Active Academy must enable its presentation editor.' );
ledger_dependency_check( true === lunara_site_studio_surface_availability( $surface )['available'], 'The real registry must show Academy as available.' );
Academy_Awards_Table::$instance = null;
ledger_dependency_check( false === lunara_site_studio_surface_availability( $surface )['available'], 'A failed reader must remain unavailable.' );
echo "Academy editor dependency passed: $checks checks.\n";
