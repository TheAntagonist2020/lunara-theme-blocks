<?php
/** Actual header trigger and Search overlay; reuse the existing WordPress seams. */
ob_start();
require __DIR__ . '/header-navigation-runtime.php';
ob_end_clean();
require dirname(__DIR__) . '/inc/live-search.php';
ob_start();
lunara_render_header_command();
lunara_live_search_render_overlay();
$html = ob_get_clean();
ob_start();
lunara_header_command_css();
$css = ob_get_clean();
echo json_encode(array('html' => $html, 'header_css' => $css));
