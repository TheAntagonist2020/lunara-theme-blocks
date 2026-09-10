<?php
define( 'LUNARA_SITE_STUDIO_RUNTIME_BOOTSTRAP_ONLY', true );
require __DIR__ . '/site-studio-runtime.php';
function get_theme_mod( $key, $default = false ) { return $default; }
function get_option( $key, $default = false ) { return $default; }
require dirname( __DIR__ ) . '/inc/site-studio-home-oscars.php';
require dirname( __DIR__ ) . '/inc/site-studio-preview.php';
$_GET['surface'] = isset( $argv[1] ) ? $argv[1] : 'home-oscar-picks';
lunara_enqueue_site_studio_assets( 'lunara_page_lunara-site-studio' );
ob_start(); lunara_render_site_studio_page(); $workspace = ob_get_clean();
$config = $lunara_test_localized['LunaraSiteStudioWorkspaceConfig'] ?? array();
echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><script>window.LunaraSiteStudioWorkspaceConfig=' . wp_json_encode( $config ) . ';</script></head><body class="wp-admin">' . $workspace . '</body></html>';
