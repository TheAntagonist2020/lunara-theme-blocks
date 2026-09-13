<?php
/** Local-only HTTP fixture exercising the actual pre-router guard and public templates. */
if(PHP_SAPI!=='cli-server'){fwrite(STDERR,"Run via the local HTTP runtime.\n");exit(1);}
$request_server=$_SERVER;$request_get=$_GET;
require __DIR__.'/site-studio-utility-recovery-fixture.php';
$_SERVER=$request_server;$_GET=$request_get;
$GLOBALS['utility_origin']='http://'.$_SERVER['HTTP_HOST'];
$GLOBALS['lunara_pilot_uuid']=0;
$scenario=$_SERVER['HTTP_X_LUNARA_FIXTURE']??'public';
$GLOBALS['lunara_pilot_theme_mods']=array('lunara_404_title'=>'Public recovery heading','lunara_search_kicker'=>'Public Search desk','lunara_search_no_query_title'=>'Public Search start','lunara_search_no_query_title_enabled'=>true,'lunara_search_excerpt_words'=>22);
$GLOBALS['utility_results']=array(new WP_Post(401,'review','publish'));
$wp_query=new WP_Query();$wp_query->is_404=true;status_header(404);
if($scenario==='existing-route'){$wp_query->is_404=false;status_header(200);}
if($scenario!=='public'){
    $is_search=str_starts_with($scenario,'search')||$scenario==='legacy';
    $surface=$is_search?'utility-search':'utility-404';
    $adapter=$is_search?lunara_site_studio_utility_search_adapter():lunara_site_studio_utility_404_adapter();
    $state=$adapter->read_state();
    if($is_search){$state['content']['kicker']='Private Search desk';$state['content']['no_query_title']='Private Search start';$state['content']['excerpt_words']=10;$state['content']['use_empty_title']=true;}
    else{$state['hero']['title']='Private recovery heading';$state['hero']['explanation']="Private first line\nPrivate second line";$state['recovery']['primary']='journal';}
    if($scenario==='legacy'){$state=lunara_site_studio_mod_surface_read_state(lunara_site_studio_utility_search_legacy_spec());$state['focus']['lead']='journal';$token=lunara_site_studio_store_private_preview($surface,'theme:'.$surface,'/search/',$state);}
    else{$created=$adapter->create_preview($state);if(is_wp_error($created)){throw new RuntimeException('Fixture token failed: '.$created->get_error_code());}$token=$created['token'];}
    if($token!=='10000000-0000-4000-8000-000000000001'){throw new RuntimeException('Unexpected fixture token');}
    if($scenario==='denied'){$GLOBALS['utility_authorized']=false;}
    if($scenario==='anonymous'){$GLOBALS['utility_user']=0;}
}
$stored=serialize(array($GLOBALS['lunara_pilot_theme_mods'],$GLOBALS['lunara_pilot_options']));
lunara_site_studio_handle_private_preview();
if($stored!==serialize(array($GLOBALS['lunara_pilot_theme_mods'],$GLOBALS['lunara_pilot_options']))){throw new RuntimeException('Private request changed persisted settings');}
header('X-Fixture-Public-State-Unchanged: 1');
// Same production order: authenticate first; the Search router may then send200.
lunara_search_command_template_redirect();
include dirname(__DIR__).'/404.php';
