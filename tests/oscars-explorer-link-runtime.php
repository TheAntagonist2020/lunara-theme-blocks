<?php
/**
 * Theme 3.2.93: "Full Ledger" opens the Oscar Ledger Explorer.
 *
 * Run: php tests/oscars-explorer-link-runtime.php
 *
 * lunara_oscars_explorer_url() asks the plugin (2.8.1+) for the Explorer's
 * address through AAT_Explorer::base_url(). The footer's Full Ledger
 * destination and the portal's Full Ledger links use it, and fall back to
 * the in-page research table when it is ''. The no-plugin half runs in a
 * child process (--no-explorer) in which AAT_Explorer is never declared.
 * The portal's own links are covered by tests/site-studio-oscars-runtime.php.
 */

define( 'ABSPATH', __DIR__ . '/' );

$root        = dirname( __DIR__ );
$no_explorer = in_array( '--no-explorer', $argv, true );
$checks      = 0;

function lunara_test_check( $value, $message ) {
	global $checks;
	++$checks;
	if ( ! $value ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function add_filter() {}
function __( $text ) { return $text; }
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function get_post_type_archive_link( $type ) { return 'https://example.test/' . $type . '/'; }
function get_bloginfo( $key ) { return 'https://example.test/feed/'; }
function get_privacy_policy_url() { return ''; }

if ( ! $no_explorer ) {
	class AAT_Explorer {
		public static function base_url() {
			return 'https://example.test/oscars/explore/';
		}
	}
}

require $root . '/inc/oscars-family.php';
require $root . '/inc/site-studio-footer-navigation.php';

$destinations = lunara_site_studio_footer_navigation_destinations();
$ledger       = $destinations['ledger'] ?? array();

if ( $no_explorer ) {
	lunara_test_check( '' === lunara_oscars_explorer_url(), 'Without the plugin Explorer the helper returns an empty string.' );
	lunara_test_check( 'https://example.test/oscars/?view=table#oscars-research' === ( $ledger['url'] ?? '' ), 'Without the plugin Explorer the footer Full Ledger keeps the research table.' );
	lunara_test_check( 'Full Ledger' === ( $ledger['label'] ?? '' ) && true === ( $ledger['available'] ?? false ), 'The fallback footer destination stays labelled and available.' );
	echo "no-explorer:{$checks}\n";
	exit( 0 );
}

lunara_test_check( 'https://example.test/oscars/explore/' === lunara_oscars_explorer_url(), 'The helper returns the plugin Explorer address.' );
lunara_test_check( 'https://example.test/oscars/explore/' === ( $ledger['url'] ?? '' ), 'The footer Full Ledger opens the Explorer.' );
lunara_test_check( 'Full Ledger' === ( $ledger['label'] ?? '' ) && true === ( $ledger['available'] ?? false ), 'The Explorer footer destination stays labelled and available.' );

$portal = file_get_contents( $root . '/page-oscars.php' );
lunara_test_check( 1 === substr_count( $portal, "\$ledger_url           = function_exists( 'lunara_oscars_explorer_url' )" ), 'The portal resolves one Full Ledger address.' );
lunara_test_check( 1 === substr_count( $portal, "'url'      => \$database_table_url," ), 'Only the Data Explorer research card keeps the in-page table.' );

$child = shell_exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' --no-explorer 2>&1' );
lunara_test_check( is_string( $child ) && 1 === preg_match( '/^no-explorer:(\d+)$/m', $child, $match ), 'The no-plugin half passes: ' . trim( (string) $child ) );

$total = $checks + (int) $match[1];
echo "Oscars Explorer link runtime passed: {$total} checks with and without the plugin Explorer.\n";
