<?php
if(PHP_SAPI!=='cli')exit(1);
$argv=['','C:/wamp64/www/Joomla'];require __DIR__.'/test-subcategory-styles.php';
use Joomla\CMS\Event\Content\BeforeDisplayEvent;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
$db=Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
foreach(['academy','blog','codex'] as $family){
 $component='com_'.$family;$factory=$app->bootComponent($component)->getMVCFactory();
 $input=$app->getInput();$input->set('option',$component);$input->set('view','post');$input->set('print',0);
 $plugin=$app->bootPlugin('pagenavigation',$family);
 echo $family.': installed plugin '.(Joomla\CMS\Plugin\PluginHelper::isEnabled($family,'pagenavigation')?'enabled':'DISABLED')."\n";$ids=[];$posts=[];
 $check=function($condition,$message)use($family){if(!$condition)throw new RuntimeException($family.': '.$message);};
 try{
  $base=$db->setQuery('SELECT * FROM #__'.$family.' WHERE state=1 LIMIT 1')->loadObject();
  for($i=0;$i<3;$i++){
   $post=clone $base;unset($post->id);$post->title='ZZ_NAV_TEST_'.uniqid().'_'.$i;$post->alias='nav-test-'.uniqid();$post->state=2;$post->ordering=900000+$i;$post->access=1;$post->language='*';$post->publish_up='2020-01-01 00:00:00';$post->publish_down=null;$post->options='{}';
   $db->insertObject('#__'.$family,$post,'id');$ids[]=$post->id;$posts[]=$post;
  }
  $run=function($row,$show=1,$context=null,$ordering='order')use($plugin,$component){
   $params=new Registry(['show_item_navigation'=>$show,'show_pagination'=>0,'orderby_sec'=>$ordering]);
   $event=new BeforeDisplayEvent('onContentBeforeDisplay',['context'=>$context??$component.'.post','subject'=>$row,'params'=>$params,'page'=>0]);
   $plugin->onContentBeforeDisplay($event);return $row;
  };
  $row=$run(clone $posts[1]);
  $check(str_contains($row->prev??'','id='.$ids[0].':')&&str_contains($row->next??'','id='.$ids[2].':'),'correct previous/next target');
  $check(str_contains($row->pagination??'','rel="prev"')&&str_contains($row->pagination??'','rel="next"'),'rendered links');
  $check(empty($run(clone $posts[2])->next),'last post has no next link');
  $db->setQuery('UPDATE #__'.$family.' SET access=999999 WHERE id='.$ids[2])->execute();
  $check(empty($run(clone $posts[1])->next),'restricted neighbour excluded');
  $db->setQuery("UPDATE #__".$family." SET access=1,publish_up='2099-01-01 00:00:00' WHERE id=".$ids[2])->execute();
  $check(empty($run(clone $posts[1])->next),'future neighbour excluded');
  $db->setQuery("UPDATE #__".$family." SET publish_up='2020-01-01 00:00:00' WHERE id=".$ids[2])->execute();
  $hidden=$run(clone $posts[1],0);$check(empty($hidden->pagination),'Hide');
  $listing=$run(clone $posts[1],1,$component.'.featured');$check(empty($listing->pagination),'listing context');
  $input->set('print',1);$check(empty($run(clone $posts[1])->pagination),'print');$input->set('print',0);
  $missing=clone $posts[1];$missing->id=2147483647;$check(empty($run($missing)->pagination),'missing current post must not select unrelated neighbour');
  $run($row,0);$check(empty($row->pagination),'Hide clears previously prepared navigation');
  $menu=$app->getMenu();$oldActive=$menu->getActive();
  $menus=$menu->getItems('component_id',Joomla\CMS\Component\ComponentHelper::getComponent('com_academy')->id);
  $target=null;foreach($menus as $candidate){if(($candidate->query['view']??'')==='featured'){$target=$candidate;break;}}
  $check((bool)$target,'Featured menu fixture');$saved=$target->getParams()->toArray();$savedQuery=$target->query;$target->query['option']=$component;$menu->setActive($target->id);
  $global=Joomla\CMS\Component\ComponentHelper::getParams($component);$oldGlobal=$global->get('show_item_navigation');
  try {
   foreach(['featured','archive','author','tags','category'] as $origin) {
   $target->query['view']=$origin;
   foreach([[0,1,1,0],[1,0,0,1],['use_post',0,1,0],['use_post','',1,1],['',1,0,1],['','',0,0],['','',1,1]] as [$menuValue,$postValue,$globalValue,$expected]){
    $target->getParams()->set('show_item_navigation',$menuValue);$global->set('show_item_navigation',$globalValue);
    $db->setQuery($db->createQuery()->update('#__'.$family)->set('options='.$db->quote(json_encode(['show_item_navigation'=>$postValue])))->where('id='.$ids[1]))->execute();
    $input->set('id',$ids[1]);$m=$factory->createModel('Post','Site');$m->getState();
    $params=clone $global;if($menuValue!=='')$params->set('show_item_navigation',$menuValue);$m->setState('params',$params);
    $item=$m->getItem($ids[1]);$check((int)$item->params->get('show_item_navigation')===$expected,'menu/post/global precedence '.json_encode([$menuValue,$postValue,$globalValue]));
   }
   }
  } finally {$target->getParams()->loadArray($saved);$target->query=$savedQuery;$global->set('show_item_navigation',$oldGlobal);$menu->setActive($oldActive?->id);}
  echo "$family: neighbours, rendered links, Hide, print, listing context, missing current post and menu/post/global inheritance passed.\n";
 }finally{if($ids)$db->setQuery($db->createQuery()->delete('#__'.$family)->whereIn('id',$ids))->execute();}
}
