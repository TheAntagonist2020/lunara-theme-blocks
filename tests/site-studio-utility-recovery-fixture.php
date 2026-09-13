<?php
/** Shared WordPress seams; providers, transactions, templates and request resolver remain real. */
$bootstrap = file_get_contents( __DIR__ . '/site-studio-pilot-runtime.php' );
$bootstrap = str_replace( 'function add_filter() { return true; }', 'function add_filter($hook,$callback,$priority=10,$accepted=1){$GLOBALS["utility_filters"][$hook][]=$callback;return true;}', $bootstrap );
$bootstrap = str_replace( 'function apply_filters( $hook, $value ) { return $value; }', 'function apply_filters($hook,$value,...$args){foreach($GLOBALS["utility_filters"][$hook]??array() as $callback){$value=$callback($value,...$args);}return $value;}', $bootstrap );
$bootstrap = str_replace( 'return array_key_exists( $key, $lunara_pilot_theme_mods ) ? $lunara_pilot_theme_mods[ $key ] : $default;', 'return apply_filters("theme_mod_".$key,array_key_exists($key,$lunara_pilot_theme_mods)?$lunara_pilot_theme_mods[$key]:$default);', $bootstrap );
$bootstrap = str_replace( "return 'https://example.test/' . ltrim( (string) \$path, '/' );", 'return ($GLOBALS["utility_origin"]??"https://example.test") . "/" . ltrim((string)$path,"/");', $bootstrap );
$bootstrap = str_replace( 'function add_query_arg( $args, $url ) {', 'function add_query_arg( $args, $url, $third = null ) { if(null!==$third){$args=array($args=>$url);$url=$third;}', $bootstrap );
$bootstrap = str_replace( 'function get_current_user_id() { return 41; }', 'function get_current_user_id(){return $GLOBALS["utility_user"]??41;}', $bootstrap );
$bootstrap = str_replace( "return 'edit_theme_options' === \$capability || 'manage_options' === \$capability;", 'return ($GLOBALS["utility_authorized"]??true) && in_array($capability,array("edit_theme_options","manage_options"),true);', $bootstrap );
$bootstrap = str_replace( "require_once \$theme_root . '/inc/site-studio-utility-recovery.php';", "require_once (getenv('LUNARA_UTILITY_RECOVERY_SOURCE') ?: \$theme_root . '/inc/site-studio-utility-recovery.php');", $bootstrap );
ob_start(); eval( '?>' . $bootstrap ); ob_end_clean();

function utility_extract( $source, $name ) {
    $tokens = token_get_all( $source );
    foreach ( $tokens as $i => $token ) {
        if ( ! is_array( $token ) || T_FUNCTION !== $token[0] ) { continue; }
        $j = $i + 1; while ( isset( $tokens[$j] ) && ( ! is_array( $tokens[$j] ) || T_STRING !== $tokens[$j][0] ) ) { ++$j; }
        if ( ( $tokens[$j][1] ?? '' ) !== $name ) { continue; }
        $code = ''; $depth = 0; $opened = false;
        for ( $k = $i; $k < count( $tokens ); ++$k ) {
            $part = is_array( $tokens[$k] ) ? $tokens[$k][1] : $tokens[$k]; $code .= $part;
            if ( '{' === $part ) { ++$depth; $opened = true; }
            elseif ( '}' === $part && 0 === --$depth && $opened ) { return $code; }
        }
    }
    throw new RuntimeException( 'Missing function ' . $name );
}
function utility_load_functions( $file, $names ) { $source = file_get_contents($file); foreach($names as $name){if(!function_exists($name)){eval(utility_extract($source,$name));}} }
function utility_reset() { lunara_pilot_reset(); $GLOBALS['utility_filters']=array(); $GLOBALS['utility_user']=41; $GLOBALS['utility_authorized']=true; unset($GLOBALS['lunara_site_studio_preview_context']); }
function esc_html($text){return htmlspecialchars((string)$text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function esc_attr($text){return esc_html($text);}
function esc_url($text){return esc_html($text);}
function esc_html_e($text){echo esc_html($text);}
function esc_attr_e($text){echo esc_attr($text);}
function sanitize_html_class($text){return preg_replace('/[^a-zA-Z0-9_-]/','',(string)$text);}
function number_format_i18n($number){return number_format($number);}
function get_header(){echo '<!doctype html><html><head><meta charset="utf-8"></head><body>';if(!preg_match('/<main <\?php.*?\?>>/s',file_get_contents(dirname(__DIR__).'/header.php'),$match)){throw new RuntimeException('Actual header main shell missing');}eval('?>'.$match[0]);}
function get_footer(){if(!preg_match('/<\/main>/',file_get_contents(dirname(__DIR__).'/footer.php'),$match)){throw new RuntimeException('Actual footer main close missing');}echo $match[0].'</body></html>';}
function get_search_query(){return $_GET['s']??'';}
function the_posts_pagination(){}
function get_the_title($post){return 'Fixture story '.$post->ID;}
function get_the_excerpt($post){return implode(' ',array_map(static function($i){return 'word'.$i;},range(1,70)));}
function wp_strip_all_tags($text){return strip_tags($text);}
function wp_trim_words($text,$count,$more='…'){$words=preg_split('/\s+/',trim($text));return implode(' ',array_slice($words,0,$count)).(count($words)>$count?$more:'');}
function get_post_type_object(){return null;}
function get_the_post_thumbnail(){return '';}
function has_post_thumbnail(){return false;}
function is_admin(){return false;}
function wp_doing_ajax(){return false;}
function set_query_var($key,$value){$GLOBALS['utility_query_vars'][$key]=$value;}
function locate_template($name){return dirname(__DIR__).'/'.$name;}
function is_404(){return !empty($GLOBALS['wp_query']->is_404);}
function status_header($status){$GLOBALS['utility_status']=$status;if(PHP_SAPI==='cli-server'){http_response_code($status);}}
function nocache_headers(){$GLOBALS['utility_no_cache']=true;}
function show_admin_bar($show){$GLOBALS['utility_admin_bar']=$show;}
function wp_die($message,$title='',$args=array()){status_header($args['response']??403);if(PHP_SAPI==='cli-server'){echo esc_html($message);exit;}throw new RuntimeException('fixture wp_die');}
class WP_Query {
    public $posts=array(); public $found_posts=0; public $is_404=false; public $is_search=false; public $is_home=false; public $query_vars=array();
    public function __construct($args=array()){$this->query_vars=$args;$this->posts=empty($args['s'])?array():($GLOBALS['utility_results']??array());$this->found_posts=count($this->posts);}
    public function set($key,$value){$this->query_vars[$key]=$value;}
}
utility_load_functions(dirname(__DIR__).'/inc/live-search.php',array('lunara_search_command_url','lunara_is_search_command_request','lunara_search_command_template_redirect'));
utility_load_functions(dirname(__DIR__).'/inc/review-rendering.php',array('lunara_get_loop_posts'));
utility_load_functions(dirname(__DIR__).'/inc/site-studio-preview.php',array('lunara_site_studio_preview_instance_query_arg','lunara_site_studio_preview_pilots','lunara_site_studio_preview_uuid','lunara_site_studio_preview_instance','lunara_site_studio_preview_key_shape','lunara_site_studio_preview_exact_query','lunara_site_studio_preview_request_origin','lunara_site_studio_preview_request_path','lunara_site_studio_preview_state_safe','lunara_site_studio_preview_install_state','lunara_site_studio_resolve_private_preview','lunara_site_studio_handle_private_preview'));
utility_reset(); $wp_query=new WP_Query();
function utility_render($file){ob_start();include dirname(__DIR__).'/'.$file;return ob_get_clean();}
