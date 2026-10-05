<?php
/**
 * 3.2.97: theme PHP source must not contain double-encoded UTF-8 ("â€”", "Ã©",
 * "â˜…"). 161 such lines were cleaned out of functions.php and inc/; this keeps
 * them out. Detection patterns must be written as escapes, not literal mojibake.
 *
 * Run: php tests/source-mojibake-guard.php
 */
$root = dirname(__DIR__);
$files = array_merge(glob($root . '/*.php'), glob($root . '/inc/*.php'), glob($root . '/inc/*/*.php'), glob($root . '/template-parts/*.php') ?: array());
$hits = array();
foreach ($files as $file) {
    foreach (file($file) as $n => $line) {
        if (preg_match('/â€|â˜|â†|â”|Ã[\x{80}-\x{BF}]|Â[\x{A0}-\x{BF}]|ðŸ/u', $line)) {
            $hits[] = str_replace($root . '/', '', $file) . ':' . ($n + 1);
        }
    }
}
$loader = file_get_contents($root . '/functions-loader.php');
$ga = file_get_contents($root . '/inc/analytics-off.php');
if (strpos($loader, "'analytics-off.php'") === false || strpos($ga, "add_filter( 'theme_mod_analytics_v4_id', '__return_empty_string', 99 );") === false) {
    $hits[] = 'inc/analytics-off.php must be loaded and must blank the Blocksy GA4 ID';
}
if ($hits) { fwrite(STDERR, "Mojibake or missing analytics guard:\n" . implode("\n", $hits) . "\n"); exit(1); }
echo "Source mojibake guard OK (" . count($files) . " files).\n";
