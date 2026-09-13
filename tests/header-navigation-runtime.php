<?php
/** Execute the actual shared header renderer with isolated WordPress menu dependencies. */
define('ABSPATH', __DIR__ . '/');
$filters = array();
function add_filter($hook, $callback, $priority = 10, $argc = 1) { $GLOBALS['filters'][$hook][] = $callback; }
function add_action(...$args) {}
function apply_filters($hook, $value, ...$args) { foreach ($GLOBALS['filters'][$hook] ?? array() as $callback) $value = $callback($value, ...$args); return $value; }
function home_url($path = '/') { return 'https://lunara.test' . $path; }
function untrailingslashit($value) { return rtrim($value, '/'); }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function wp_unslash($value) { return stripslashes($value); }
function __($value, $domain = '') { return $value; }
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES); }
function esc_attr($value) { return esc_html($value); }
function esc_url($value) { return esc_html($value); }
function esc_attr_e($value, $domain = '') { echo esc_attr($value); }
function esc_html_e($value, $domain = '') { echo esc_html($value); }
function get_theme_mod($key, $fallback = false) { return $fallback; }
function get_bloginfo($key) { return 'Lunara Film'; }
function is_admin() { return false; }
function wp_get_theme() { return new class { public function parent() { return false; } }; }
function post_type_exists($type) { return false; }
function has_nav_menu($location) { return $GLOBALS['use_saved_menu'] ?? true; }
function wp_nav_menu($args) {
    $args = (object) $args;
    $items = apply_filters('wp_nav_menu_objects', $GLOBALS['menu_items'], $args);
    $html = '';
    foreach ($items as $item) {
        $attrs = apply_filters('nav_menu_link_attributes', array('href' => $item->url), $item, $args);
        $html .= '<li><a';
        foreach ($attrs as $name => $value) $html .= ' ' . $name . '="' . esc_attr($value) . '"';
        $html .= '>' . esc_html($item->title) . '</a></li>';
    }
    return $html;
}
require getenv('LUNARA_HEADER_SOURCE') ?: dirname(__DIR__) . '/inc/header-command.php';
$GLOBALS['menu_items'] = array_map(static function ($row) { return (object) array('title'=>$row[0], 'url'=>home_url($row[1])); }, array(
    array('Search', '/search/'), array('Home', '/'), array('Journal', '/journal/'), array('Oscars', '/oscars/'), array('Reviews', '/reviews/')
));
$checks = 0;
function verify_header($ok, $message) { ++$GLOBALS['checks']; if (!$ok) throw new RuntimeException($message); }
$routes = array('/'=>'Home', '/journal/'=>'Journal', '/journal/long-article/'=>'Journal', '/reviews/'=>'Reviews', '/reviews/film/'=>'Reviews', '/oscars/'=>'Oscars', '/oscars/category/sound/'=>'Oscars');
foreach ($routes as $route => $label) {
    $_SERVER['REQUEST_URI'] = $route . '?tracking=1';
    foreach (array('header-nav', 'offcanvas') as $context) {
        $html = lunara_header_nav_markup($context);
        $dom = new DOMDocument(); @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        verify_header($xpath->query('//a')->length === 4, 'Four saved destinations on ' . $route);
        verify_header($xpath->query('//a[@aria-current]')->length === 1, 'One current destination on ' . $route);
        verify_header($xpath->query('//a[@aria-current]')->item(0)->textContent === $label, 'Current section on ' . $route);
        verify_header(!str_contains($html, '/search/'), 'One dedicated search destination on ' . $route);
    }
}
$other_args = (object) array('theme_location'=>'footer');
verify_header(lunara_header_filter_menu_items($GLOBALS['menu_items'], $other_args) === $GLOBALS['menu_items'], 'Preserve other menu owners');
$named_search = (object) array('title'=>'Search', 'url'=>'https://another.test/research/');
verify_header(count(lunara_header_filter_menu_items(array($named_search), (object) array('theme_location'=>'lunara-header'))) === 1, 'Preserve differently linked custom labels');
$_SERVER['REQUEST_URI'] = '/reviews-extra/';
verify_header(lunara_header_link_current(home_url('/reviews/')) === '', 'Do not mark sibling prefix');
$_SERVER['REQUEST_URI'] = '/reviews/film/';
verify_header(lunara_header_link_current('https://another.test/reviews/') === '', 'Do not mark external destination');
$GLOBALS['use_saved_menu'] = false;
$fallback = lunara_header_nav_markup('header-nav');
verify_header(str_contains($fallback, 'aria-current="location">Reviews</a>'), 'Fallback menu marks current section');
$GLOBALS['use_saved_menu'] = true;
ob_start(); lunara_render_header_command(); lunara_render_offcanvas_nav(); $markup = ob_get_clean();
verify_header(substr_count($markup, 'data-lunara-search-open') === 1, 'Single search control');
verify_header(str_contains($markup, '<a class="lunara-header-search" href="https://lunara.test/search/"'), 'Search works as a link without scripts');
verify_header(str_contains($markup, '<noscript>'), 'Mobile navigation has a no-script presentation');
ob_start(); lunara_header_command_css(); $css = ob_get_clean();
if (in_array('--fixture', $argv, true)) {
    echo json_encode(array('html'=>$markup,'css'=>$css,'checks'=>$checks));
} else {
    echo "Header navigation runtime passed: $checks checks.\n";
}
