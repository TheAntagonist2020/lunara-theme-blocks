<?php
/** Real Search/404 providers and templates, including raw rollback and retired writers. */
require __DIR__.'/site-studio-utility-recovery-fixture.php';
$checks=0;
function utility_assert($condition,$message){global $checks;++$checks;if(!$condition){throw new RuntimeException($message);}}
function utility_snapshot(){return serialize(array($GLOBALS['lunara_pilot_theme_mods'],$GLOBALS['lunara_pilot_options'],$GLOBALS['lunara_pilot_option_autoload']));}
$search=lunara_site_studio_utility_search_adapter();$missing=lunara_site_studio_utility_404_adapter();
$search_spec=lunara_site_studio_utility_search_spec();$missing_spec=lunara_site_studio_utility_404_spec();
utility_assert(count(lunara_site_studio_mod_surface_keys($search_spec))===13,'Search owns twelve existing fields and one explicit adoption flag');
utility_assert(count(lunara_site_studio_mod_surface_keys($missing_spec))===11,'404 owns ten copy fields and primary destination');
foreach(array('utility-search'=>array($search,$search_spec),'utility-404'=>array($missing,$missing_spec)) as $surface=>$pair){
    list($adapter,$spec)=$pair;utility_reset();$before=utility_snapshot();$default=$adapter->read_state();
    utility_assert($before===utility_snapshot(),'Read has no writes: '.$surface);
    utility_assert(!is_wp_error($adapter->validate_state($default)),'Current defaults validate: '.$surface);
    foreach($spec as $group=>$fields){foreach($fields as $field=>$definition){
        $path=$group.'.'.$field;$bad=$default;$bad[$group][$field]=array('wrong');$error=$adapter->save_state($bad);
        utility_assert(is_wp_error($error)&&array($path)===array_keys(lunara_site_studio_safe_validation_fields($error)),'Exact focused error for '.$surface.' '.$path);
        utility_assert($before===utility_snapshot(),'Invalid value has zero writes: '.$path);
        if(in_array($definition['type'],array('text','textarea'),true)){
            $bad=$default;$bad[$group][$field]=str_repeat('é',$definition['max_length']);utility_assert(!is_wp_error($adapter->validate_state($bad)),'Unicode BMP limit accepted '.$path);
            $bad[$group][$field].='é';utility_assert(is_wp_error($adapter->validate_state($bad)),'Unicode BMP overflow rejected '.$path);
            $bad[$group][$field]=str_repeat('🎬',intdiv($definition['max_length'],2));utility_assert(!is_wp_error($adapter->validate_state($bad)),'UTF16 emoji boundary accepted '.$path);
            $bad[$group][$field].='🎬';utility_assert(is_wp_error($adapter->validate_state($bad)),'UTF16 emoji overflow rejected '.$path);
        }elseif($definition['type']==='int'){
            foreach(array($definition['min'],$definition['max']) as $boundary){$bad=$default;$bad[$group][$field]=$boundary;utility_assert(!is_wp_error($adapter->validate_state($bad)),'Integer boundary accepted '.$path.' '.$boundary);}
            foreach(array($definition['min']-1,$definition['max']+1,22.5,'22',true) as $invalid){$bad=$default;$bad[$group][$field]=$invalid;utility_assert(is_wp_error($adapter->save_state($bad))&&$before===utility_snapshot(),'Invalid number rejected without writes '.$path.' '.json_encode($invalid));}
        }elseif($definition['type']==='select'){
            $bad=$default;$bad[$group][$field]='unsupported';utility_assert(is_wp_error($adapter->save_state($bad))&&$before===utility_snapshot(),'Unknown enum rejected without writes '.$path);
        }elseif($definition['type']==='bool'){
            foreach(array(0,1,'false','true') as $invalid){$bad=$default;$bad[$group][$field]=$invalid;utility_assert(is_wp_error($adapter->save_state($bad))&&$before===utility_snapshot(),'Adoption requires a real boolean');}
        }
    }}
    $partial=$default;unset($partial[array_key_first($partial)][array_key_first($partial[array_key_first($partial)])]);utility_assert(is_wp_error($adapter->save_state($partial))&&$before===utility_snapshot(),'Partial candidates cannot publish '.$surface);
    $extra=$default;$extra['unexpected']=array();utility_assert(is_wp_error($adapter->save_state($extra)),'Unknown candidate group rejected '.$surface);
    $GLOBALS['lunara_pilot_theme_mods']=array('unrelated'=>array('keep'=>true));
    foreach($spec as $group=>$fields){foreach($fields as $field=>$definition){if(!empty($definition['preserve_legacy_read'])){$GLOBALS['lunara_pilot_theme_mods'][$definition['mod']]='int'===$definition['type']?'73':"  Older\n".str_repeat('L',1200);}}}
    $raw=$GLOBALS['lunara_pilot_theme_mods'];$state=$adapter->read_state();utility_assert($raw===$GLOBALS['lunara_pilot_theme_mods'],'Raw read never rewrites '.$surface);
    foreach($spec as $group=>$fields){foreach($fields as $field=>$definition){if(!empty($definition['preserve_legacy_read'])){utility_assert($state[$group][$field]===('int'===$definition['type']?73:$raw[$definition['mod']]),'Long raw presentation retained '.$surface.' '.$field);}}}
    $saved=$adapter->save_state($default);utility_assert(!is_wp_error($saved),'Apply canonical candidate '.$surface);
    $restored=$adapter->restore_revision($saved['revision_id']);utility_assert(!is_wp_error($restored)&&$raw===$GLOBALS['lunara_pilot_theme_mods'],'History restores long raw values, types and absence '.$surface);
    foreach(array('fail','mismatch','throw_after','read_throw_after') as $mode){
        $before=utility_snapshot();$GLOBALS['lunara_pilot_mod_fault']=array('key'=>lunara_site_studio_mod_surface_keys($spec)[0],'mode'=>$mode,'remaining'=>1);
        utility_assert(is_wp_error($adapter->save_state($default)),'Injected write failure surfaces '.$surface.' '.$mode);
        utility_assert($before===utility_snapshot(),'Injected write failure rolls back exactly '.$surface.' '.$mode);
    }
    $before=utility_snapshot();$GLOBALS['lunara_pilot_option_fault']=array('key'=>lunara_site_studio_revision_option_name($surface),'mode'=>'mismatch','remaining'=>1);
    utility_assert(is_wp_error($adapter->save_state($default)),'Revision failure surfaces '.$surface);utility_assert($before===utility_snapshot(),'Revision failure rolls back raw settings/history '.$surface);
}
utility_reset();$old_keys=lunara_site_studio_mod_surface_keys(lunara_site_studio_utility_search_legacy_spec());
$old_revision=lunara_site_studio_private_revision('utility-search',array('mods'=>lunara_site_studio_raw_mod_snapshot($old_keys)),'save');
$state=$search->read_state();$state['content']=array('kicker'=>'New desk','no_query_title'=>'New heading','excerpt_words'=>31,'use_empty_title'=>true);$state['geometry']['section_gap']=60;$saved=$search->save_state($state);
utility_assert(!is_wp_error($saved),'New Search state saves');$current=$GLOBALS['lunara_pilot_theme_mods'];$restored=$search->restore_revision($old_revision);
utility_assert(!is_wp_error($restored),'Exact old nine-mod History remains available');
foreach(array('lunara_search_kicker','lunara_search_no_query_title','lunara_search_excerpt_words','lunara_search_no_query_title_enabled') as $key){utility_assert($GLOBALS['lunara_pilot_theme_mods'][$key]===$current[$key],'Old History preserves newer raw value '.$key);}
utility_assert(!array_key_exists('lunara_utility_section_gap',$GLOBALS['lunara_pilot_theme_mods']),'Old History restores original absence');
$safety=$search->restore_revision($restored['safety_revision_id']);$restored_mods=$GLOBALS['lunara_pilot_theme_mods'];ksort($restored_mods);ksort($current);utility_assert(!is_wp_error($safety)&&$current===$restored_mods,'Old History safety revision remains fully undoable');
$old_state=lunara_site_studio_mod_surface_read_state(lunara_site_studio_utility_search_legacy_spec());$before=utility_snapshot();utility_assert(is_wp_error($search->save_state($old_state))&&$before===utility_snapshot(),'Old nine-field POST cannot masquerade as current candidate');
$partial=lunara_site_studio_raw_mod_snapshot($old_keys);unset($partial[$old_keys[0]]);$partial_id=lunara_site_studio_private_revision('utility-search',array('mods'=>$partial),'save');$before=utility_snapshot();utility_assert(is_wp_error($search->restore_revision($partial_id))&&$before===utility_snapshot(),'Arbitrary partial History has zero writes');
$before=utility_snapshot();$old_state['geometry']['section_gap']=24;utility_assert(lunara_site_studio_utility_search_install_legacy_preview_state($old_state),'Exact old token state can install request-local filters');utility_assert(get_theme_mod('lunara_utility_section_gap')===24&&get_theme_mod('lunara_search_no_query_title')==='New heading','Old preview changes only old fields');utility_assert($before===utility_snapshot(),'Legacy preview has no writes');
utility_reset();$state=$missing->read_state();$state['hero']['explanation']="First line\nSecond line";$saved=$missing->save_state($state);utility_assert(!is_wp_error($saved)&&$saved['state']['hero']['explanation']==="First line\nSecond line",'404 Apply preserves multiline explanation');
$before=utility_snapshot();$GLOBALS['lunara_pilot_mod_fault']=array('key'=>'lunara_404_kicker','mode'=>'fail','remaining'=>1);$restore=$missing->restore_revision($saved['revision_id']);utility_assert(is_wp_error($restore)&&$before===utility_snapshot(),'History write failure restores both state and safety-history record');

// Render actual templates; saved dormant title is adopted only by the explicit flag.
utility_reset();$_SERVER['REQUEST_URI']='/search/';$_GET=array();$GLOBALS['lunara_pilot_theme_mods']['lunara_search_no_query_title']='Previously dormant title';
$html=utility_render('search.php');utility_assert(substr_count($html,'<main ')===1&&substr_count($html,'</main>')===1,'Search has one main landmark inside the actual header/footer shell');utility_assert(str_contains($html,'>Search Lunara Film</h1>')&&!str_contains($html,'Previously dormant title'),'Dormant title remains unused on public start');
$state=$search->read_state();$state['geometry']['section_gap']=52;$search->save_state($state);$html=utility_render('search.php');utility_assert(!str_contains($html,'Previously dormant title'),'Layout-only Apply does not adopt dormant title');
$state['content']['use_empty_title']=true;$search->save_state($state);utility_assert(str_contains(utility_render('search.php'),'>Previously dormant title</h1>'),'Explicit Apply adopts empty-search title');
$_GET=array('q'=>'Lunara');$GLOBALS['utility_results']=array(new WP_Post(401,'review','publish'));$state['content']['excerpt_words']=10;$search->save_state($state);$html=utility_render('search.php');utility_assert(str_contains($html,'>Search Results</h1>')&&!str_contains($html,'Previously dormant title'),'Results retain dedicated heading');utility_assert(str_contains($html,'word10')&&!str_contains($html,'word11'),'Actual result cards use candidate excerpt length');
$_GET=array();$GLOBALS['utility_results']=array();$GLOBALS['footer_archives']=array('journal'=>'https://example.test/editorial/','review'=>'https://example.test/criticism/');$html=utility_render('search.php');utility_assert(str_contains($html,'href="https://example.test/editorial/"'),'Search recovery uses actual Journal archive');
$state=$missing->read_state();foreach($missing_spec as $group=>$fields){foreach($fields as $field=>$definition){if($definition['type']!=='select'){$state[$group][$field]='Unique '.$group.' '.$field;}}}$state['recovery']['primary']='search';$missing->save_state($state);$html=utility_render('404.php');
utility_assert(substr_count($html,'<main ')===1&&substr_count($html,'</main>')===1,'404 has one main landmark inside the actual header/footer shell');
foreach($state as $group=>$fields){foreach($fields as $field=>$value){if($field!=='primary'){utility_assert(str_contains($html,esc_html($value)),'Actual404 renders '.$group.'.'.$field);}}}
preg_match('/class="lunara-404-actions">.*?href="([^"]+)"/s',$html,$first_action);utility_assert(($first_action[1]??'')==='https://example.test/search/','404 Search primary is the first canonical recovery link');utility_assert(str_contains($html,'action="https://example.test/search/"')&&str_contains($html,'name="q"'),'404 Search form uses canonical query');utility_assert(str_contains($html,'href="https://example.test/editorial/"')&&str_contains($html,'href="https://example.test/criticism/"'),'404 archive links use registered destinations');
foreach(array('home'=>'https://example.test/','reviews'=>'https://example.test/criticism/','journal'=>'https://example.test/editorial/','oscars'=>'https://example.test/oscars/','search'=>'https://example.test/search/') as $primary=>$destination){$state['recovery']['primary']=$primary;$missing->save_state($state);$html=utility_render('404.php');preg_match('/class="lunara-404-actions">.*?href="([^"]+)"/s',$html,$first_action);utility_assert(($first_action[1]??'')===$destination,'Each primary selection takes first position: '.$primary);}
$posts=array(new WP_Post(801,'review','publish'),new WP_Post(802,'journal','publish'),new WP_Post(803,'page','publish'),new WP_Post(804,'review','publish'));
foreach(array('review'=>array(801,804,802,803),'journal'=>array(802,801,803,804),'page'=>array(803,801,802,804),'automatic'=>array(801,802,803,804)) as $focus=>$ids){$sorted=lunara_search_focus_order_posts($posts,'balanced',$focus);utility_assert(array_map(static function($post){return $post->ID;},$sorted)===$ids,'Search spotlight preserves stable order for '.$focus);}

utility_load_functions(dirname(__DIR__).'/inc/control-desk.php',array('lunara_control_desk_apply_utility_search_values','lunara_control_desk_save_utility_search_studio','lunara_control_desk_render_utility_search_studio'));
function check_admin_referer($action,$field){$GLOBALS['utility_nonce']=array($action,$field);}
function wp_safe_redirect($url){$GLOBALS['utility_redirect']=$url;throw new RuntimeException('fixture redirect');}
$before=utility_snapshot();utility_assert(false===lunara_control_desk_apply_utility_search_values(array('lunara_search_kicker'=>'Stale'))&&$before===utility_snapshot(),'Old direct package writer is inert');
$_POST=array('lunara_search_kicker'=>'Stale');try{lunara_control_desk_save_utility_search_studio();}catch(RuntimeException $e){utility_assert($e->getMessage()==='fixture redirect','Stale authenticated form redirects');}utility_assert($before===utility_snapshot()&&$GLOBALS['utility_nonce']===array('lunara_save_utility_search_studio','lunara_utility_search_nonce'),'Stale form checks old nonce and cannot write');utility_assert(str_contains($GLOBALS['utility_redirect'],'surface=utility-search'),'Stale form returns to Search editor');
ob_start();lunara_control_desk_render_utility_search_studio();$panel=ob_get_clean();utility_assert(!str_contains($panel,'<form')&&str_contains($panel,'surface=utility-search')&&str_contains($panel,'surface=utility-404'),'Control Desk offers separate truthful handoffs');
utility_load_functions(dirname(__DIR__).'/inc/customizer.php',array('lunara_retire_search_recovery_customizer_controls'));
$customizer=new class{public $controls=array();public $settings=array();public function remove_control($key){$this->controls[]=$key;}public function remove_setting($key){$this->settings[]=$key;}};
lunara_retire_search_recovery_customizer_controls($customizer);utility_assert(count($customizer->controls)===13&&$customizer->controls===$customizer->settings,'All thirteen legacy registrations and controls retire together');utility_assert(!in_array('lunara_header_search_placeholder',$customizer->settings,true)&&$before===utility_snapshot(),'Header placeholder and persisted mods remain untouched');
echo "Search/404 provider and renderer runtime passed: $checks checks.\n";
