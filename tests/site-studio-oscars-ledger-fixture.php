<?php
define('LUNARA_SITE_STUDIO_RUNTIME_BOOTSTRAP_ONLY',true);
require __DIR__.'/site-studio-runtime.php';
function ledger_extract($source,$name) {
 $tokens=token_get_all($source);foreach($tokens as $i=>$token){if(!is_array($token)||$token[0]!==T_FUNCTION)continue;$j=$i+1;while(isset($tokens[$j])&&(!is_array($tokens[$j])||$tokens[$j][0]!==T_STRING))$j++;if(($tokens[$j][1]??'')!==$name)continue;$code='';$depth=0;$opened=false;for($k=$i;$k<count($tokens);$k++){$part=is_array($tokens[$k])?$tokens[$k][1]:$tokens[$k];$code.=$part;if($part==='{'){$depth++;$opened=true;}elseif($part==='}'&&--$depth===0&&$opened)return $code;}}throw new RuntimeException('Missing '.$name);
}

$control=file_get_contents(dirname(__DIR__).'/inc/control-desk.php');
foreach(array('lunara_control_desk_oscars_dossier_select_specs','lunara_control_desk_oscars_dossier_number_specs','lunara_control_desk_oscars_dossier_preset_specs') as $fn)eval(ledger_extract($control,$fn));
if(!function_exists('get_theme_mod')){function get_theme_mod($key,$default=false){return $default;}}
require dirname(__DIR__).'/inc/site-studio-oscars-ledger.php';
require dirname(__DIR__).'/inc/site-studio-preview.php';
class LedgerFixtureAdapter extends Lunara_Site_Studio_Theme_Adapter {public function list_revisions(){return array();}}
function ledger_fixture_adapter(){return new LedgerFixtureAdapter('oscars-ledger','theme:oscars-ledger-presentation',array('read'=>'lunara_site_studio_oscars_ledger_read_state','validate'=>'lunara_site_studio_oscars_ledger_validate_state'));}
add_filter('lunara_site_studio_surfaces',static function($surfaces){$surfaces['oscars-ledger']['adapter_factory']='ledger_fixture_adapter';return $surfaces;},99);
$_GET['surface']='oscars-ledger';
lunara_enqueue_site_studio_assets('lunara_page_lunara-site-studio');
ob_start();lunara_render_site_studio_page();$html=ob_get_clean();
$config=$lunara_test_localized['LunaraSiteStudioWorkspaceConfig']??array();
echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><script>window.LunaraSiteStudioWorkspaceConfig='.wp_json_encode($config).';</script></head><body class="wp-admin">'.$html.'</body></html>';
