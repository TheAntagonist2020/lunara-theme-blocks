<?php
/** Execute the real provider with existing transaction stubs and real legacy specs. */
$bootstrap=file_get_contents(__DIR__.'/site-studio-pilot-runtime.php');
$bootstrap=str_replace('function add_filter() { return true; }', 'function add_filter($hook,$callback,$priority=10,$accepted=1) { $GLOBALS["ledger_filters"][$hook][]=$callback; return true; }',$bootstrap);
$bootstrap=str_replace('function apply_filters( $hook, $value ) { return $value; }', 'function apply_filters($hook,$value,...$args) { foreach($GLOBALS["ledger_filters"][$hook]??array() as $callback){$value=$callback($value,...$args);} return $value; }',$bootstrap);
$bootstrap=str_replace('return array_key_exists( $key, $lunara_pilot_theme_mods ) ? $lunara_pilot_theme_mods[ $key ] : $default;', 'return apply_filters("theme_mod_".$key,array_key_exists($key,$lunara_pilot_theme_mods)?$lunara_pilot_theme_mods[$key]:$default);',$bootstrap);
$bootstrap=str_replace('function get_current_user_id() { return 41; }','function get_current_user_id() { return $GLOBALS["ledger_user"]??41; }',$bootstrap);
$bootstrap=str_replace("return 'edit_theme_options' === \$capability || 'manage_options' === \$capability;", 'return ($GLOBALS["ledger_authorized"]??true) && ("edit_theme_options" === $capability || "manage_options" === $capability);',$bootstrap);
ob_start();eval('?>'.$bootstrap);
function ledger_extract($source,$name) {
 $tokens=token_get_all($source);foreach($tokens as $i=>$token){if(!is_array($token)||$token[0]!==T_FUNCTION)continue;$j=$i+1;while(isset($tokens[$j])&&(!is_array($tokens[$j])||$tokens[$j][0]!==T_STRING))$j++;if(($tokens[$j][1]??'')!==$name)continue;$code='';$depth=0;$opened=false;for($k=$i;$k<count($tokens);$k++){$part=is_array($tokens[$k])?$tokens[$k][1]:$tokens[$k];$code.=$part;if($part==='{'){$depth++;$opened=true;}elseif($part==='}'&&--$depth===0&&$opened)return $code;}}throw new RuntimeException('Missing '.$name);
}
$control=file_get_contents(dirname(__DIR__).'/inc/control-desk.php');
foreach(array('lunara_control_desk_oscars_dossier_select_specs','lunara_control_desk_oscars_dossier_number_specs','lunara_control_desk_oscars_dossier_preset_specs','lunara_control_desk_render_oscars_dossier_studio','lunara_control_desk_apply_oscars_dossier_values','lunara_control_desk_save_oscars_dossier_studio') as $fn)eval(ledger_extract($control,$fn));
require dirname(__DIR__).'/inc/site-studio-oscars-ledger.php';
function nocache_headers(){$GLOBALS['ledger_no_cache']=true;}
$checks=0;function ledger_assert($condition,$message){global $checks;$checks++;if(!$condition)throw new RuntimeException($message);}
lunara_pilot_reset();$adapter=lunara_site_studio_oscars_ledger_adapter();$default=$adapter->read_state();
ledger_assert(count($default['presentation'])===14,'All14 canonical controls retained');
ledger_assert($lunara_pilot_theme_mods===array(),'Missing settings untouched by read');
$keys=lunara_site_studio_mod_surface_keys(lunara_site_studio_oscars_ledger_spec());
foreach(lunara_site_studio_oscars_ledger_packages() as $name=>$values){ledger_assert(count($values)===14,'Preset includes all14 values');ledger_assert(!is_wp_error($adapter->validate_state(array('presentation'=>$values))),'Complete preset validates');}
$before=serialize(array($lunara_pilot_theme_mods,$lunara_pilot_options));
foreach(array(-1,23,97,48.5,'48',false,array()) as $bad){$state=$default;$state['presentation']['dossier_section_gap']=$bad;$error=$adapter->save_state($state);ledger_assert(is_wp_error($error),'Reject invalid number '.json_encode($bad));ledger_assert($before===serialize(array($lunara_pilot_theme_mods,$lunara_pilot_options)),'Invalid candidate has zero writes');}
$bad=$default;$bad['presentation']['dossier_density']='arbitrary';ledger_assert(is_wp_error($adapter->validate_state($bad)),'Unknown enum rejected');
$bad=$default;unset($bad['presentation']['profile_scale']);ledger_assert(is_wp_error($adapter->validate_state($bad)),'Partial candidate rejected');
$bad=$default;$bad['presentation']['unrelated']='x';ledger_assert(is_wp_error($adapter->validate_state($bad)),'Unknown field rejected');
$lunara_pilot_theme_mods=array('lunara_oscars_profile_media_width'=>'430','unrelated'=>array('keep'=>true));$legacy=$lunara_pilot_theme_mods;$candidate=$adapter->read_state();ledger_assert($legacy===$lunara_pilot_theme_mods&&$candidate['presentation']['profile_media_width']===430,'Read keeps raw numeric strings and absence');
$candidate=array('presentation'=>lunara_site_studio_oscars_ledger_packages()['compact-ledger']);
$token=lunara_site_studio_store_private_preview('oscars-ledger','theme:oscars-ledger-presentation','/oscars/ceremony/98/',$candidate);
ledger_assert(is_string($token)&&$legacy===$lunara_pilot_theme_mods,'Private token does not change public mods');
ledger_assert(lunara_site_studio_get_private_preview('oscars-ledger','theme:oscars-ledger-presentation','/oscars/ceremony/98/',$token)===$candidate,'Canonical token returns candidate');
ledger_assert(false===lunara_site_studio_get_private_preview('oscars-ledger','wrong-owner','/oscars/ceremony/98/',$token),'Wrong owner rejected');
ledger_assert(false===lunara_site_studio_get_private_preview('oscars-ledger','theme:oscars-ledger-presentation','/arbitrary/',$token),'Unregistered token scope rejected');
$saved=$adapter->save_state($candidate);ledger_assert(!is_wp_error($saved)&&$saved['state']===$candidate,'Apply saves candidate');ledger_assert($lunara_pilot_theme_mods['unrelated']===array('keep'=>true),'Unrelated mods preserved');
$restore=$adapter->restore_revision($saved['revision_id']);ledger_assert(!is_wp_error($restore)&&$legacy===$lunara_pilot_theme_mods,'History restores raw strings and missing settings exactly');
$preview_source=file_get_contents(getenv('LUNARA_LEDGER_PREVIEW_SOURCE')?:dirname(__DIR__).'/inc/site-studio-preview.php');
eval(ledger_extract($preview_source,'lunara_site_studio_preview_request_path'));
$_SERVER['HTTP_HOST']='example.test';$_SERVER['HTTPS']='on';$_SERVER['QUERY_STRING']='anything=1';
foreach(lunara_site_studio_oscars_ledger_preview_routes() as $route){$_SERVER['REQUEST_URI']=$route.'?anything=1';ledger_assert(lunara_site_studio_oscars_ledger_preview_request_path(),'Allowed representative route '.$route);}
$_SERVER['QUERY_STRING']='';
foreach(array('/oscars/','/oscars/name/nm0000001/','/oscars/category/best-picture/extra/','//evil.test/oscars/ceremony/98/','/oscars/ceremony/%39%38/') as $route){$_SERVER['REQUEST_URI']=$route;ledger_assert(!lunara_site_studio_oscars_ledger_preview_request_path(),'Reject path '.$route);}
$frontend=file_get_contents(dirname(__DIR__).'/inc/frontend.php');
foreach(array('lunara_get_oscars_dossier_saved_control_values','lunara_get_oscars_dossier_studio_select_value','lunara_get_oscars_dossier_studio_number_value') as $fn)if(!function_exists($fn))eval(ledger_extract($frontend,$fn));
if(!function_exists('lunara_home_select_setting')){function lunara_home_select_setting($key,$default,$allowed){$value=get_theme_mod($key,$default);return in_array($value,$allowed,true)?$value:$default;}}
if(!function_exists('lunara_home_brand_number_setting')){function lunara_home_brand_number_setting($key,$default,$min,$max){return max($min,min($max,absint(get_theme_mod($key,$default))));}}
lunara_pilot_reset();$public=lunara_get_oscars_dossier_saved_control_values();foreach(array('dossier_density'=>'density','dossier_section_gap'=>'section_gap','dossier_card_min'=>'card_min') as $from=>$to){ledger_assert($default['presentation'][$from]===$public[$to],'Public missing default '.$from);}foreach($public as $key=>$value){if(isset($default['presentation'][$key]))ledger_assert($default['presentation'][$key]===$value,'Public missing default '.$key);}

// Exercise the real request resolver, retaining owner/route/hash and exact query guards.
foreach(array('lunara_site_studio_preview_instance_query_arg','lunara_site_studio_preview_pilots','lunara_site_studio_preview_uuid','lunara_site_studio_preview_instance','lunara_site_studio_preview_exact_query','lunara_site_studio_preview_request_origin','lunara_site_studio_preview_state_safe','lunara_site_studio_preview_install_state','lunara_site_studio_resolve_private_preview') as $fn)if(!function_exists($fn))eval(ledger_extract($preview_source,$fn));
add_filter('lunara_site_studio_surfaces',static function($surfaces){$surfaces['oscars-ledger']['dependency_callback']='__return_true';return $surfaces;});
function __return_true(){return true;}
$candidate=array('presentation'=>lunara_site_studio_oscars_ledger_packages()['profile-spotlight']);
$token=$adapter->create_preview($candidate)['token'];
$query=http_build_query(array('lunara_oscars_ledger_preview'=>$token,'lunara_site_studio_instance'=>'123e4567-e89b-42d3-a456-426614174111:1'));
$_SERVER['REQUEST_METHOD']='GET';$_SERVER['HTTPS']='on';$_SERVER['HTTP_HOST']='example.test';$_SERVER['QUERY_STRING']=$query;parse_str($query,$_GET);
$persisted=$lunara_pilot_theme_mods;
foreach(lunara_site_studio_oscars_ledger_preview_routes() as $route){$_SERVER['REQUEST_URI']=$route.'?'.$query;$context=lunara_site_studio_prepare_private_preview_response('lunara_site_studio_resolve_private_preview');ledger_assert(is_array($context)&&$context['surface']==='oscars-ledger','Real resolver accepts '.$route);$visible=lunara_get_oscars_dossier_saved_control_values();ledger_assert($visible['profile_media_width']===$candidate['presentation']['profile_media_width'],'Actual public reader sees private candidate');ledger_assert($persisted===$lunara_pilot_theme_mods,'Preview never changes public storage');}
ledger_assert(!empty($GLOBALS['ledger_no_cache'])&&in_array('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0',lunara_site_studio_private_preview_headers(),true),'Real preview response forbids page caching');
$_SERVER['HTTP_HOST']='evil.test';ledger_assert(false===lunara_site_studio_resolve_private_preview(),'Wrong host rejected');$_SERVER['HTTP_HOST']='example.test';
$GLOBALS['ledger_user']=42;ledger_assert(false===lunara_site_studio_resolve_private_preview(),'Wrong user rejected');$GLOBALS['ledger_user']=0;ledger_assert(false===lunara_site_studio_resolve_private_preview(),'Unauthenticated rejected');$GLOBALS['ledger_user']=41;
$GLOBALS['ledger_authorized']=false;ledger_assert(false===lunara_site_studio_resolve_private_preview(),'Missing capability rejected');$GLOBALS['ledger_authorized']=true;
$key='lunara_site_studio_preview_'.hash('sha256',$token);$record=get_transient($key);
foreach(array('owner'=>'wrong','surface'=>'review-single','expires'=>1,'token_hash'=>'bad') as $field=>$value){$bad=$record;$bad[$field]=$value;set_transient($key,$bad,900);ledger_assert(false===lunara_site_studio_resolve_private_preview(),'Reject token '.$field);}set_transient($key,$record,900);
$_SERVER['REQUEST_URI']='/oscars/ceremony/99/?'.$query;ledger_assert(false===lunara_site_studio_resolve_private_preview(),'Non-example ceremony rejects token');
$bad=$record;$bad['state']['presentation']['profile_scale']='unsafe';set_transient($key,$bad,900);$_SERVER['REQUEST_URI']='/oscars/ceremony/98/?'.$query;ledger_assert(false===lunara_site_studio_resolve_private_preview(),'Invalid private candidate rejected');set_transient($key,$record,900);
foreach($keys as $mod){$field=substr($mod,strlen('lunara_oscars_'));$bad=$default;$bad['presentation'][$field]=array('invalid');$error=$adapter->validate_state($bad);ledger_assert(array('presentation.'.$field)===array_keys(lunara_site_studio_safe_validation_fields($error)),'Focusable REST error for '.$field);}
$stored=$lunara_pilot_theme_mods;lunara_control_desk_apply_oscars_dossier_values(array('lunara_oscars_dossier_density'=>'dense'));ledger_assert($stored===$lunara_pilot_theme_mods,'Legacy direct writer is retired');
function lunara_control_desk_admin_url($args){return admin_url('admin.php?page=lunara-control-desk');}
function lunara_control_desk_bounded_return_url($key,$surface,$fallback){return $fallback;}
function check_admin_referer($action,$key){$GLOBALS['ledger_nonce_checked']=array($action,$key);}
function wp_safe_redirect($url){$GLOBALS['ledger_redirect']=$url;throw new RuntimeException('fixture redirect');}
$_POST=array('lunara_oscars_dossier_preset'=>'compact-ledger','lunara_oscars_profile_media_width'=>220);
try{lunara_control_desk_save_oscars_dossier_studio();}catch(RuntimeException $error){ledger_assert($error->getMessage()==='fixture redirect','Stale form redirects');}
ledger_assert($stored===$lunara_pilot_theme_mods&&$GLOBALS['ledger_nonce_checked']===array('lunara_save_oscars_dossier_studio','lunara_oscars_dossier_nonce'),'Stale form checks nonce and cannot write');
ledger_assert(str_contains($GLOBALS['ledger_redirect'],'surface=oscars-ledger'),'Stale form returns to owner');
ob_end_clean();
echo "Oscars Ledger provider runtime passed: $checks checks.\n";
