<?php
/** Existing transaction doubles, real Footer provider and real public renderer. */
define( 'LUNARA_METHOD_BOOTSTRAP_ONLY', true );
$bootstrap = file_get_contents( __DIR__ . '/site-studio-pilot-runtime.php' );
$bootstrap = str_replace( 'function add_filter() { return true; }', 'function add_filter($hook,$callback,$priority=10,$accepted=1){$GLOBALS["footer_filters"][$hook][]=$callback;return true;}', $bootstrap );
$bootstrap = str_replace( 'function apply_filters( $hook, $value ) { return $value; }', 'function apply_filters($hook,$value,...$args){foreach($GLOBALS["footer_filters"][$hook]??array() as $callback){$value=$callback($value,...$args);}return $value;}', $bootstrap );
$bootstrap = str_replace( 'return array_key_exists( $key, $lunara_pilot_theme_mods ) ? $lunara_pilot_theme_mods[ $key ] : $default;', 'return apply_filters("theme_mod_".$key,array_key_exists($key,$lunara_pilot_theme_mods)?$lunara_pilot_theme_mods[$key]:$default);', $bootstrap );
$bootstrap = str_replace( 'function get_current_user_id() { return 41; }', 'function get_current_user_id(){return $GLOBALS["footer_user"]??41;}', $bootstrap );
$bootstrap = str_replace( "return 'edit_theme_options' === \$capability || 'manage_options' === \$capability;", 'return ($GLOBALS["footer_authorized"]??true)&&("edit_theme_options"===$capability||"manage_options"===$capability);', $bootstrap );
$bootstrap = str_replace( 'function wp_json_encode( $value ) { return json_encode( $value ); }', 'function wp_json_encode($value,$options=0){return json_encode($value,$options);}', $bootstrap );
$bootstrap = str_replace( array( "require \$theme_root . '/inc/site-studio-footer-navigation.php';", "require_once \$theme_root . '/inc/site-studio-footer-navigation.php';" ), '', $bootstrap );
eval( '?>' . $bootstrap );
require_once getenv( 'LUNARA_FOOTER_PROVIDER_SOURCE' ) ?: dirname( __DIR__ ) . '/inc/site-studio-footer-navigation.php';

if ( ! function_exists( 'get_post_type_archive_link' ) ) { function get_post_type_archive_link( $type ) { return $GLOBALS['footer_archives'][ $type ] ?? home_url( '/' . ( 'review' === $type ? 'reviews' : $type ) . '/' ); } }
if ( ! function_exists( 'get_privacy_policy_url' ) ) { function get_privacy_policy_url() { return $GLOBALS['footer_privacy'] ?? home_url( '/privacy-policy/' ); } }
if ( ! function_exists( 'get_bloginfo' ) ) { function get_bloginfo( $field ) { return 'rss2_url' === $field ? ( $GLOBALS['footer_feed'] ?? home_url( '/feed/' ) ) : 'Lunara Film'; } }
function bloginfo( $field ) { echo esc_html( get_bloginfo( $field ) ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_html( $value ); }
function esc_attr_e( $value ) { echo esc_attr( $value ); }
function esc_html_e( $value ) { echo esc_html( $value ); }
function wp_get_attachment_image() { return '<img alt="Lunara logo">'; }
function has_action() { return false; }
function remove_action() { return true; }
function is_feed() { return false; }
function is_front_page() { return $GLOBALS['footer_front_page'] ?? true; }
function is_page() { return true; }
function get_queried_object_id() { return 101; }
function status_header( $status ) { $GLOBALS['footer_status'] = $status; }
function nocache_headers() { $GLOBALS['footer_no_store'] = true; }
function show_admin_bar() {}

function footer_extract_function( $source, $name ) {
	$tokens = token_get_all( $source );
	foreach ( $tokens as $i => $token ) {
		if ( ! is_array( $token ) || T_FUNCTION !== $token[0] ) { continue; }
		$j = $i + 1; while ( isset( $tokens[$j] ) && ( ! is_array( $tokens[$j] ) || T_STRING !== $tokens[$j][0] ) ) { $j++; }
		if ( ( $tokens[$j][1] ?? '' ) !== $name ) { continue; }
		$code = ''; $depth = 0; $opened = false;
		for ( $k = $i; $k < count( $tokens ); $k++ ) {
			$part = is_array( $tokens[$k] ) ? $tokens[$k][1] : $tokens[$k]; $code .= $part;
			if ( '{' === $part ) { $depth++; $opened = true; } elseif ( '}' === $part && 0 === --$depth && $opened ) { return $code; }
		}
	}
	throw new RuntimeException( 'Missing production function ' . $name );
}
$frontend = file_get_contents( getenv( 'LUNARA_FOOTER_FRONTEND_SOURCE' ) ?: dirname( __DIR__ ) . '/inc/frontend.php' );
foreach ( array( 'lunara_render_footer_link_list', 'lunara_render_custom_footer' ) as $fn ) { eval( footer_extract_function( $frontend, $fn ) ); }
require dirname( __DIR__ ) . '/inc/site-studio-preview.php';

function footer_fixture_reset() {
	lunara_pilot_reset();
	foreach ( array( 'footer_filters', 'footer_archives', 'footer_privacy', 'footer_feed', 'footer_user', 'footer_authorized', 'footer_front_page', 'footer_no_store' ) as $key ) { unset( $GLOBALS[$key] ); }
}
function footer_fixture_render() { ob_start(); lunara_render_custom_footer(); return ob_get_clean(); }
function footer_fixture_request( $token ) {
	$query = http_build_query( array( 'lunara_footer_preview' => $token, 'lunara_site_studio_instance' => '123e4567-e89b-42d3-a456-426614174000:1' ) );
	$_SERVER = array( 'REQUEST_METHOD' => 'GET', 'HTTP_HOST' => 'example.test', 'HTTPS' => 'on', 'QUERY_STRING' => $query, 'REQUEST_URI' => '/?' . $query );
	parse_str( $query, $_GET );
}
