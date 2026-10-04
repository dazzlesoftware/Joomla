<?php
if(PHP_SAPI!=='cli')exit(1);
$argv=['','C:/wamp64/www/Joomla'];require __DIR__.'/test-subcategory-styles.php';
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
$db=Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
$menu=$app->getMenu();$oldActive=$menu->getActive();$target=$menu->getItem(9967);$oldQuery=$target->query;$oldMenu=$target->getParams()->toArray();$menu->setActive($target->id);
try{foreach(['academy','blog','codex'] as $family){
 $component='com_'.$family;$factory=$app->bootComponent($component)->getMVCFactory();$ns='Joomla\\Component\\'.ucfirst($family).'\\Site\\Helper\\';$helper=$ns.'AuthorListingHelper';$slider=$ns.'FeaturedSliderHelper';$page=$app->getParams($component);$oldPage=$page->toArray();$ids=[];
 try{
  $base=$db->setQuery('SELECT * FROM #__'.$family.' WHERE state=1 LIMIT 1')->loadObject();
  $author2=(int)$db->setQuery('SELECT id FROM #__users WHERE block=0 AND id<>'.(int)$base->created_by.' LIMIT 1')->loadResult();
  $cat2=(int)$db->setQuery('SELECT id FROM #__'.$family.'_categories WHERE published=1 AND access=1 AND id>1 AND id<>'.(int)$base->catid.' LIMIT 1')->loadResult();
  foreach([[$base->created_by,$base->catid],[$author2,$cat2]] as [$author,$cat]){$p=clone $base;unset($p->id);$p->created_by=$author;$p->catid=$cat;$p->state=1;$p->featured=1;$p->access=1;$p->language='*';$p->publish_up='2020-01-01 00:00:00';$p->publish_down=null;$p->alias='author-filter-'.uniqid();$db->insertObject('#__'.$family,$p,'id');$ids[]=(int)$p->id;}
  $target->query=['option'=>$component,'view'=>'author','id'=>(int)$base->created_by];$target->setParams(new Registry());
  $legacy=$helper::settings(new Registry([]),(int)$base->created_by);
  if($legacy->get('listing_authors')!==[(int)$base->created_by])throw new RuntimeException('Legacy author lost');
  $form=new Joomla\CMS\Form\Form('author.filter.'.$family,['control'=>'jform']);$form->loadFile(__DIR__.'/../'.$family.'/com_'.$family.'/site/tmpl/author/default.xml',true,'/metadata');$form->load('<form><field name="link" type="text" /></form>');$form->bind(['link'=>'index.php?option='.$component.'&view=author&id='.$base->created_by,'params'=>[]]);
  $html=$form->getField('listing_authors','params')->input;
  if(!preg_match('/value="'.$base->created_by.'"[^>]*selected/', $html))throw new RuntimeException('Legacy author not selected in form');
  $input=$app->getInput();$input->set('option',$component);$input->set('view','author');$input->set('id',0);$input->set('limitstart',0);$target->query=['option'=>$component,'view'=>'author'];
  $excluded=$db->setQuery('SELECT id FROM #__'.$family.' WHERE id NOT IN ('.implode(',',$ids).')')->loadColumn();
  foreach([
   [[], $ids],
   [['listing_authors'=>[(int)$base->created_by]],[$ids[0]]],
   [['listing_authors'=>[(int)$base->created_by,$author2]],$ids],
   [['listing_exclude_authors'=>[$author2]],[$ids[0]]],
   [['listing_categories'=>[$cat2]],[$ids[1]]],
   [['listing_exclude_categories'=>[$cat2]],[$ids[0]]],
   [['listing_tags'=>[2147483647]],[]],
  ] as [$overrides,$expected]){
   $values=array_merge(['listing_authors'=>[],'listing_exclude_authors'=>[],'listing_categories'=>[],'listing_exclude_categories'=>[],'listing_tags'=>[],'listing_exclude_posts'=>$excluded,'listing_subcategories'=>0,'author_featured_slider_count'=>50],$overrides);
   $target->getParams()->loadArray($values);$page->loadArray(array_merge($oldPage,$values));
   $model=$factory->createModel('Author','Site');$got=array_map('intval',array_column($model->getItems(),'id'));sort($got);sort($expected);
   if($got!==$expected || $model->getTotal()!==count($expected))throw new RuntimeException('Author filter '.json_encode([$family,$overrides,$got,$expected]));
   $params=$model->getState('params');$slideIds=array_map('intval',array_column($slider::items($params),'id'));sort($slideIds);
   if($slideIds!==$expected)throw new RuntimeException('Slider filter '.json_encode([$overrides,$slideIds,$expected]));
  }
  $pair=$db->setQuery('SELECT c.id,c.parent_id FROM #__'.$family.'_categories c JOIN #__'.$family.'_categories p ON p.id=c.parent_id WHERE c.published=1 AND c.access=1 AND p.id>1 LIMIT 1')->loadObject();
  if(!$pair)throw new RuntimeException('Parent/child category fixture required');
  $db->setQuery('UPDATE #__'.$family.' SET catid='.(int)$pair->id.' WHERE id='.$ids[1])->execute();
  foreach([0,1] as $children){
   $values=['listing_authors'=>[],'listing_exclude_authors'=>[],'listing_categories'=>[(int)$pair->parent_id],'listing_exclude_categories'=>[],'listing_tags'=>[],'listing_subcategories'=>$children,'listing_exclude_posts'=>array_merge($excluded,[$ids[0]])];
   $target->getParams()->loadArray($values);$page->loadArray($values);
   $m=$factory->createModel('Author','Site');$got=array_map('intval',array_column($m->getItems(),'id'));
   if($got!==($children?[$ids[1]]:[]))throw new RuntimeException('Include subcategories');
  }
  $filter=$ns.'ListingFilterHelper';$params=new Registry(['listing_canonical'=>'https://example.org/authors']);$filter::canonical($app->getDocument(),$params);if(!isset($app->getDocument()->getHeadData()['links']['https://example.org/authors']))throw new RuntimeException('Canonical');
  echo "$family: legacy selection/form, multiple/all authors, exclusions, category/tag filters, pagination totals, slider and canonical passed.\n";
 }finally{$page->loadArray($oldPage);if($ids)$db->setQuery('DELETE FROM #__'.$family.' WHERE id IN ('.implode(',',$ids).')')->execute();}
}}finally{$target->query=$oldQuery;$target->getParams()->loadArray($oldMenu);$menu->setActive($oldActive?->id);}


