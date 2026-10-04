<?php
if(PHP_SAPI!=='cli')exit(1);
$argv=['','C:/wamp64/www/Joomla'];require __DIR__.'/test-subcategory-styles.php';
use Joomla\CMS\Factory;
use Joomla\CMS\Event\Content\BeforeDisplayEvent;
use Joomla\Registry\Registry;
$db=Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
$menu=$app->getMenu();$active=$menu->getActive();$target=$menu->getItem(9967);$savedQuery=$target->query;$menu->setActive($target->id);
try{foreach(['academy','blog','codex'] as $family){
 $component='com_'.$family;$target->query=['option'=>$component,'view'=>'archive'];$input=$app->getInput();$input->set('option',$component);$input->set('view','post');$input->set('month',0);$input->set('year',0);$input->set('print',0);
 $page=$app->getParams($component);$saved=$page->toArray();$ids=[];
 try{
  $base=$db->setQuery('SELECT * FROM #__'.$family.' WHERE state=1 LIMIT 1')->loadObject();
  $other=(int)$db->setQuery('SELECT id FROM #__'.$family.'_categories WHERE published=1 AND access=1 AND id<>'.(int)$base->catid.' AND id>1 LIMIT 1')->loadResult();
  if(!$other)throw new RuntimeException('Second category required');
  foreach([$base->catid,$other] as $i=>$catid){$p=clone $base;unset($p->id);$p->catid=$catid;$p->state=2;$p->access=1;$p->language='*';$p->publish_up='2020-01-01 00:00:00';$p->publish_down=null;$p->title='Archive navigation fixture';$p->alias='archive-navigation-'.uniqid();$db->insertObject('#__'.$family,$p,'id');$ids[]=$p->id;}
  $page->set('listing_categories',[$base->catid,$other]);$page->set('listing_exclude_categories',[]);$page->set('listing_tags',[]);$page->set('listing_authors',[]);$page->set('listing_exclude_authors',[]);$page->set('archive_posts_orderby_pri','none');$page->set('archive_posts_orderby_sec','order');
  $exclude=$db->setQuery('SELECT id FROM #__'.$family.' WHERE id NOT IN ('.implode(',',$ids).')')->loadColumn();$page->set('listing_exclude_posts',$exclude);
  $plugin=$app->bootPlugin('pagenavigation',$family);
  $run=function($show=1)use($db,$family,$ids,$plugin,$component){$row=$db->setQuery('SELECT * FROM #__'.$family.' WHERE id='.$ids[0])->loadObject();$params=new Registry(['show_item_navigation'=>$show]);$event=new BeforeDisplayEvent('onContentBeforeDisplay',['context'=>$component.'.post','subject'=>$row,'params'=>$params,'page'=>0]);$plugin->onContentBeforeDisplay($event);return $row;};
  $row=$run();if(!str_contains(($row->prev??'').($row->next??''),'id='.$ids[1].':'))throw new RuntimeException($family.' cross-category neighbour missing');
  if(empty($row->pagination))throw new RuntimeException('No rendered navigation');
  if(!empty($run(0)->pagination))throw new RuntimeException('Hide ignored');
  $page->set('listing_exclude_posts',array_merge($exclude,[$ids[1]]));if(!empty($run()->pagination))throw new RuntimeException('Menu exclusion ignored');$page->set('listing_exclude_posts',$exclude);
  $db->setQuery('UPDATE #__'.$family.' SET access=999999 WHERE id='.$ids[1])->execute();if(!empty($run()->pagination))throw new RuntimeException('Restricted neighbour');
  echo "$family: cross-category archive navigation, rendered links, Hide, menu exclusion, singleton and access filtering passed.\n";
 }finally{$page->loadArray($saved);if($ids)$db->setQuery('DELETE FROM #__'.$family.' WHERE id IN ('.implode(',',$ids).')')->execute();}
}}finally{$target->query=$savedQuery;$menu->setActive($active?->id);}
