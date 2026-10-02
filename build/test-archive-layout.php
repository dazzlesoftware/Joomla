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
   $p=clone $base;unset($p->id);$p->title='Archive layout fixture '.$i;$p->alias='archive-layout-fixture-'.uniqid();$p->state=2;$p->featured=1;$p->media='{}';$p->created=$p->publish_up=$p->modified='2020-03-'.sprintf('%02d',$i+1).' 00:00:00';$p->publish_down=null;$p->access=1;$p->language='*';
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
  // Exercise the actual standalone module dispatcher and its inherited/explicit filters.
  require_once JPATH_ROOT.'/modules/mod_'.$family.'_archive/src/Dispatcher/Dispatcher.php';
  $dispatcherClass='Joomla\\Module\\'.ucfirst($family).'Archive\\Site\\Dispatcher\\Dispatcher';
  $moduleData=function(array $params)use($dispatcherClass,$app,$input,$family){
   $module=(object)['module'=>'mod_'.$family.'_archive','params'=>json_encode($params)];
   $dispatcher=new $dispatcherClass($module,$app,$input);
   return (new ReflectionMethod($dispatcher,'getLayoutData'))->invoke($dispatcher);
  };
  $moduleForm=new Joomla\CMS\Form\Form('archive.module.'.$family,['control'=>'jform']);
  $moduleForm->loadFile(JPATH_ROOT.'/modules/mod_'.$family.'_archive/mod_'.$family.'_archive.xml',true,'/extension/config');
  foreach(['listing_authors','listing_exclude_authors','listing_categories','listing_exclude_categories','listing_tags','listing_subcategories'] as $key){
   $field=$moduleForm->getField($key,'params');if(!$field || !$field->input)throw new RuntimeException('Module field '.$key);
  }
  $result=$moduleData(['count'=>100]);
  $march=array_values(array_filter($result['list'],fn($row)=>(int)$row->year===2020&&(int)$row->month===3));
  if(count($march)!==1 || (int)$march[0]->count!==6)throw new RuntimeException('Module archive count');
  $result=$moduleData(['count'=>100,'listing_exclude_authors'=>[$base->created_by]]);
  foreach($result['list'] as $row)if((int)$row->year===2020&&(int)$row->month===3)throw new RuntimeException('Module author exclusion');
  $result=$moduleData(['count'=>100,'listing_categories'=>[$base->catid]]);
  if(!$result['list'])throw new RuntimeException('Module category include');
  $result=$moduleData(['listing_tags'=>[2147483647]]);if($result['list'])throw new RuntimeException('Module tag filter');
  $input->set('archive_filters',['listing_exclude_authors'=>[$base->created_by],'order_date'=>'created']);
  $m=$factory->createModel('Archive','Site');if($m->getTotal()!=0)throw new RuntimeException('Module link filters');
  $input->set('archive_filters',[]);
  // Archive filters must restrict the query, total, slider and compact selections.
  $settings=$ns.'ArchivePostsHelper';
  foreach (['listing_authors'=>[$base->created_by], 'listing_categories'=>[$base->catid]] as $key=>$value) {
   $params=$settings::settings(new Joomla\Registry\Registry([$key=>$value]));
   if($params->get($key)!==$value)throw new RuntimeException('Lost filter '.$key);
   $m=$factory->createModel('Archive','Site');$m->getState();$m->setState('params',$params);
   if($m->getTotal()<6)throw new RuntimeException('Include filter '.$key);
  }
  foreach (['listing_exclude_authors'=>[$base->created_by], 'listing_exclude_categories'=>[$base->catid], 'listing_tags'=>[2147483647]] as $key=>$value) {
   $params=clone $v->params;$params->set($key,$value);
   $m=$factory->createModel('Archive','Site');$m->getState();$m->setState('params',$params);
   if($m->getTotal()!=0 || $m->getItems())throw new RuntimeException('Exclude/tag filter '.$key);
   if($slider::items($params))throw new RuntimeException('Slider filter '.$key);
   $params->set('compact_selection','latest');if($compact::select([],[],$params))throw new RuntimeException('Compact filter '.$key);
  }
  $parentId=(int)$db->setQuery('SELECT parent_id FROM #__'.$family.'_categories WHERE id='.(int)$base->catid)->loadResult();
  if($parentId>0) {
   $params=clone $v->params;$params->set('listing_categories',[$parentId]);$params->set('listing_subcategories',1);
   $m=$factory->createModel('Archive','Site');$m->getState();$m->setState('params',$params);
   if($m->getTotal()<6)throw new RuntimeException('Include subcategories');
  }
  $params=clone $v->params;$params->set('listing_canonical','https://example.org/archive');
  $filters=$ns.'ListingFilterHelper';$filters::canonical($app->getDocument(),$params);
  if(!isset($app->getDocument()->getHeadData()['links']['https://example.org/archive']))throw new RuntimeException('Canonical');
  // Check every slider control against real archived fixtures and all seven styles.
  $sliderParams=clone $v->params;$sliderParams->set('featured_slider_enabled',1);$sliderParams->set('featured_slider_count',2);
  $sliderParams->set('featured_slider_content_length',5);
  $slider::render($sliderParams,0,0,function($style,$data){
   if(count($data['items'])!==2)throw new RuntimeException('Slider count');
   foreach($data['items'] as $item)if(mb_strlen($item->sliderText)>6)throw new RuntimeException('Slider content limit');
   return '';
  });
  $sliderParams->set('featured_slider_content_length',0);
  $slider::render($sliderParams,0,0,function($style,$data){foreach($data['items'] as $item)if(mb_strlen($item->sliderText)<=5)throw new RuntimeException('Unlimited slider content');return '';});
  $sliderParams->set('featured_slider_enabled',0);if($slider::render($sliderParams)!=='')throw new RuntimeException('Slider disabled');
  $sliderParams->set('featured_slider_enabled',1);$sliderParams->set('featured_slider_all_pages',0);if($slider::render($sliderParams,0,2)!=='')throw new RuntimeException('Slider later pages');
  $sliderParams->set('featured_slider_all_pages',1);if(!$slider::render($sliderParams,0,2))throw new RuntimeException('Slider all pages');
  $markers=['avatar'=>'postmeta-avatar','image'=>'fa-image','title'=>'<h2','category'=>'fa-folder-open','author'=>'fa-user me-1','readmore'=>'Continue reading','navigation'=>'data-bs-slide=', 'content'=>'<p>', 'ratings'=>'fa-star','date'=>'<time'];
  foreach(['default','card','hero','magazine','side-navigation','slick','thumbnail'] as $style){
   $sliderParams->set('featured_slider_style',$style);
   foreach(['image','title','category','author','avatar','readmore','navigation','content','ratings','date','auto'] as $key)$sliderParams->set('featured_slider_'.$key,0);
   $hidden=$slider::render($sliderParams);
   foreach($markers as $key=>$marker) {
    if(str_contains($hidden,$marker))throw new RuntimeException('Hidden slider '.$style.' '.$key);
    $sliderParams->set('featured_slider_'.$key,1);$shown=$slider::render($sliderParams);
    if($key==='navigation'){$found=str_contains($shown,'data-bs-slide=')||str_contains($shown,'data-bs-slide-to=');}else{$found=str_contains($shown,$marker);}
    if(!$found)throw new RuntimeException('Shown slider '.$style.' '.$key);
    $sliderParams->set('featured_slider_'.$key,0);
   }
   $sliderParams->set('featured_slider_auto',1);$sliderParams->set('featured_slider_interval',3);
   if(!str_contains($slider::render($sliderParams),'data-featured-pause'))throw new RuntimeException('Autoplay control');
   $options=$app->getDocument()->getScriptOptions('bootstrap.carousel');$last=end($options);
   if($last->interval!==3000)throw new RuntimeException('Slider interval');
  }
  foreach(['created','modified','published'] as $source){
   $sliderParams->set('featured_slider_date_source',$source);$sliderParams->set('featured_slider_date',1);
   $slider::render($sliderParams,0,0,function($style,$data)use($source){foreach($data['items'] as $item)if($item->params->get('date_type')!==$source)throw new RuntimeException('Slider date source');return '';});
  }
  $input->set('month',4);[$empty,$html]=$render();if($empty->posts||$empty->compactItems||str_contains($html,'featured-showcase'))throw new RuntimeException('Date scope');
  $source=__DIR__.'/../'.$family.'/com_'.$family;$global=simplexml_load_file($source.'/admin/forms/settings.xml');$menu=simplexml_load_file($source.'/site/tmpl/archive/default.xml');
  $fields=$global->xpath('//fields[@name="params"]/fieldset[starts-with(@name,"archive_posts")]/field');if(count($fields)!==40)throw new RuntimeException('Global settings');$componentOnly=['archive_posts_columns_per_row', 'archive_posts_column_style', 'archive_posts_list_item_style', 'archive_posts_post_listing_layout', 'archive_posts_items_limit_source', 'archive_posts_posts_per_page', 'archive_posts_featured_slider_style', 'archive_posts_compact_layout', 'archive_posts_num_links', 'archive_posts_compact_columns'];foreach($fields as $field)if(count($menu->xpath('//field[@name="'.(string)$field['name'].'"]'))!==(in_array((string)$field['name'],$componentOnly,true)?0:1))throw new RuntimeException('Menu parity');
  $helper=$ns.'ArchivePostsHelper';$overrides=new Joomla\Registry\Registry();foreach($componentOnly as $key)$overrides->set($key,'stale-menu-value');$resolved=$helper::settings($overrides);foreach($componentOnly as $key){$short=substr($key,14);if($resolved->get($short)==='stale-menu-value')throw new RuntimeException('Stale menu override '.$key);}
  echo "$family: pagination, 15 layouts, 7 sliders, 5 compact modes, date scope and ".count($fields)." settings passed.\n";
 } finally {if($ids)$db->setQuery('DELETE FROM #__'.$family.' WHERE id IN ('.implode(',',array_map('intval',$ids)).')')->execute();}
}
