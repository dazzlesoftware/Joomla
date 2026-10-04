<?php
if(PHP_SAPI!=='cli')exit(1);
$argv=['','C:/wamp64/www/Joomla'];require __DIR__.'/test-subcategory-styles.php';
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
$db=Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
foreach(['academy','blog','codex'] as $family){
 $component='com_'.$family;$factory=$app->bootComponent($component)->getMVCFactory();$input=$app->getInput();
 $input->set('option',$component);$input->set('layout','default');$input->set('month',0);$input->set('year',0);$input->set('limit',100);$input->set('limitstart',0);$input->set('tag_id',987654321);
 $global=ComponentHelper::getParams($component);$page=$app->getParams($component);$oldGlobal=$global->toArray();$oldPage=$page->toArray();$ids=[];
 try{
  $base=$db->setQuery('SELECT * FROM #__'.$family.' WHERE state=1 LIMIT 1')->loadObject();
  foreach([1,999999,999999] as $i=>$access){
   $p=clone $base;unset($p->id);$p->title='Menu control fixture';$p->alias='menu-control-'.uniqid();$p->state=2;$p->access=$access;$p->language='*';$p->publish_up='2020-01-01 00:00:00';$p->publish_down=null;$p->options=json_encode(['show_noauth'=>$i===1?1:0]);
   $db->insertObject('#__'.$family,$p,'id');$ids[]=(int)$p->id;
   $map=(object)['content_item_id'=>$p->id,'tag_id'=>987654321,'type_alias'=>$component.'.post'];$db->insertObject('#__'.$family.'_tag_map',$map);
  }
  foreach(['Archive','Author','Tags'] as $modelName){
   $input->set('view',strtolower($modelName));$input->set('id',$base->created_by);
   $db->setQuery('UPDATE #__'.$family.' SET state='.($modelName==='Archive'?2:1).' WHERE id IN ('.implode(',',$ids).')')->execute();
   foreach([0,1,'use_post'] as $noauth){
    $global->set('show_noauth',0);$page->set('show_noauth',$noauth);
    $m=$factory->createModel($modelName,'Site');$params=clone $m->getState('params');
    if($m->getState('filter.access')!==($noauth===0))throw new RuntimeException($family.' '.$modelName.' access setting');
    foreach(['listing_categories','listing_exclude_categories','listing_exclude_authors','listing_exclude_posts','listing_tags'] as $key)$params->set($key,[]);
    $m->setState('params',$params);$m->setState('filter.post_id',$ids);$m->setState('filter.post_id.include',true);$m->setState('list.limit',100);
    $items=$m->getItems();$got=array_map('intval',array_column($items,'id'));sort($got);
    $expected=$noauth===0?[$ids[0]]:($noauth===1?$ids:[$ids[0],$ids[1]]);sort($expected);
    if($got!==$expected || $m->getTotal()!==count($expected))throw new RuntimeException($family.' '.$modelName.' preview filtering '.json_encode([$noauth,$got,$expected]));
    foreach($items as $item)if((int)$item->access===999999 && $item->params->get('access-view'))throw new RuntimeException('Restricted full access granted');
   }
  }
  foreach(['archive','author','tags','featured','category','categories'] as $view){
   foreach(glob(__DIR__.'/../'.$family.'/com_'.$family.'/site/tmpl/'.$view.'/*.xml') as $file){
    if(basename($file)==='metadata.xml')continue;
    $xml=simplexml_load_file($file);
    foreach(['show_item_navigation','show_readmore','show_readmore_title','show_noauth'] as $field)if(count($xml->xpath('//field[@name="'.$field.'"]'))!==1)throw new RuntimeException('Missing/duplicate '.$field.' '.$file);
   }
  }
  echo "$family: menu controls, restricted previews, per-post inheritance, pagination totals and full-access flags passed.\n";
 }finally{
  $global->loadArray($oldGlobal);$page->loadArray($oldPage);
  if($ids){$db->setQuery('DELETE FROM #__'.$family.'_tag_map WHERE content_item_id IN ('.implode(',',$ids).')')->execute();$db->setQuery('DELETE FROM #__'.$family.' WHERE id IN ('.implode(',',$ids).')')->execute();}
 }
}

