<?php
if (PHP_SAPI !== 'cli') { exit(1); }
$argv=['','C:/wamp64/www/Joomla'];
require __DIR__.'/test-subcategory-styles.php';
define('JPATH_COMPONENT', JPATH_ROOT.'/components/com_academy');
$db=Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
foreach (['academy','blog','codex'] as $family) {
 $component='com_'.$family; $factory=$app->bootComponent($component)->getMVCFactory();
 $input=$app->getInput();$input->set('option',$component);$input->set('view','archive');$input->set('layout','default');$input->set('month',3);$input->set('year',2020);$input->set('limit',2);$input->set('catid',[]);
 $ids=[];
 try {
  $base=$db->setQuery('SELECT * FROM #__'.$family.' WHERE state=1 LIMIT 1')->loadObject();
  if(!$base)throw new RuntimeException('Missing base fixture');
  for($i=0;$i<6;$i++) {
   $p=clone $base;unset($p->id);$p->title='Archive layout fixture '.$i;$p->alias='archive-layout-fixture-'.uniqid();$p->state=2;$p->featured=1;$p->created=$p->publish_up=$p->modified='2020-03-'.sprintf('%02d',$i+1).' 00:00:00';$p->publish_down=null;$p->access=1;$p->language='*';
   $db->insertObject('#__'.$family,$p,'id');$ids[]=$p->id;
  }
  foreach(['archive_posts_order_date'=>'created','archive_posts_featured_slider_enabled'=>1,'archive_posts_compact_selection'=>'next','archive_posts_num_links'=>2,'archive_posts_show_pagination'=>1] as $key=>$value) { Joomla\CMS\Component\ComponentHelper::getParams($component)->set($key,$value);$app->getParams($component)->set($key,$value); }
  $render=function($start=0)use($input,$factory,$component,$app){$input->set('limitstart',$start);$m=$factory->createModel('Archive','Site');$v=$factory->createView('Archive','Site','html');$v->setModel($m,true);$v->addTemplatePath(JPATH_ROOT.'/components/'.$component.'/tmpl/archive');$v->setDocument($app->getDocument());ob_start();$v->display();$html=ob_get_clean();return [$v,$html];};
  [$v,$html]=$render();
  if(count($v->posts)!==2 || $v->pagination->total<6 || !str_contains($html,'pagination'))throw new RuntimeException('Pagination');
  [$v2]=$render(2);if(array_intersect(array_column($v->posts,'id'),array_column($v2->posts,'id')))throw new RuntimeException('Duplicate page');
  [$last]=$render(99999);if(!$last->posts)throw new RuntimeException('Last page');
  $input->set('limitstart',0);
  foreach(['standard','card','learning','simple','nickel'] as $style)foreach(['rows','grid','masonry'] as $layout){$v->params->set('list_item_style',$style);$v->params->set('post_listing_layout',$layout==='rows'?'rows':'columns');$v->params->set('column_style',$layout);$html=$v->loadTemplate();if(!str_contains($html,'post-style-'.$style))throw new RuntimeException('Style');}
  $ns='Joomla\\Component\\'.ucfirst($family).'\\Site\\Helper\\';$slider=$ns.'ArchiveSliderHelper';$compact=$ns.'ArchiveCompactHelper';
  foreach(['card','default','hero','magazine','side-navigation','slick','thumbnail'] as $style){$v->params->set('featured_slider_style',$style);$html=$v->loadTemplate();if(!str_contains($html,'featured-showcase'))throw new RuntimeException('Slider '.$style);}
  foreach(['latest','featured','random','related','next'] as $mode){$v->params->set('compact_selection',$mode);$items=$compact::select($v->compactItems,$v->posts,$v->params);if(!$items)throw new RuntimeException('Empty compact '.$mode);foreach($items as $p){if(!in_array($p->id,$ids)||in_array($p->id,array_column($v->posts,'id')))throw new RuntimeException('Compact scope '.$mode);}}
  foreach($slider::items($v->params) as $p)if(!in_array($p->id,$ids))throw new RuntimeException('Slider scope');
  $input->set('month',4);[$empty,$html]=$render();if($empty->posts||$empty->compactItems||str_contains($html,'featured-showcase'))throw new RuntimeException('Date scope');
  $source=__DIR__.'/../'.$family.'/com_'.$family;$global=simplexml_load_file($source.'/admin/forms/settings.xml');$menu=simplexml_load_file($source.'/site/tmpl/archive/default.xml');
  $fields=$global->xpath('//fields[@name="params"]/fieldset[starts-with(@name,"archive_posts")]/field');if(count($fields)!==40)throw new RuntimeException('Global settings');$componentOnly=['archive_posts_list_item_style', 'archive_posts_post_listing_layout', 'archive_posts_items_limit_source', 'archive_posts_posts_per_page', 'archive_posts_featured_slider_style', 'archive_posts_compact_layout', 'archive_posts_num_links', 'archive_posts_compact_columns'];foreach($fields as $field)if(count($menu->xpath('//field[@name="'.(string)$field['name'].'"]'))!==(in_array((string)$field['name'],$componentOnly,true)?0:1))throw new RuntimeException('Menu parity');
  $helper=$ns.'ArchivePostsHelper';$overrides=new Joomla\Registry\Registry();foreach($componentOnly as $key)$overrides->set($key,'stale-menu-value');$resolved=$helper::settings($overrides);foreach($componentOnly as $key){$short=substr($key,14);if($resolved->get($short)==='stale-menu-value')throw new RuntimeException('Stale menu override '.$key);}
  echo "$family: pagination, 15 layouts, 7 sliders, 5 compact modes, date scope and ".count($fields)." settings passed.\n";
 } finally {if($ids)$db->setQuery('DELETE FROM #__'.$family.' WHERE id IN ('.implode(',',array_map('intval',$ids)).')')->execute();}
}
