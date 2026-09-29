<?php
if(PHP_SAPI!=='cli')exit(1);
$argv=['','C:/wamp64/www/Joomla'];require __DIR__.'/test-subcategory-styles.php';
foreach(['academy','blog','codex'] as $family){
 $xml=simplexml_load_file(__DIR__.'/../'.$family.'/com_'.$family.'/site/tmpl/featured/default.xml');
 foreach(['listing_authors','listing_exclude_authors','listing_categories','listing_exclude_categories','listing_tags','listing_canonical','listing_subcategories','listing_include_featured'] as $name) {
  if(count($xml->xpath('//fieldset[@name="request"]/field[@name="'.$name.'"]'))!==1)throw new RuntimeException('Missing menu control '.$name);
 }
 foreach(['featured_slider_style','list_item_style','post_listing_layout','column_style','columns_per_row','items_limit_source','posts_per_page'] as $name) {
  if($xml->xpath('//field[@name="'.$name.'"]'))throw new RuntimeException('Component-only control still in menu '.$name);
 }
 $component='com_'.$family;$factory=$app->bootComponent($component)->getMVCFactory();$app->getInput()->set('option',$component);$app->getInput()->set('view','featured');
 $expected=['featured_slider_style'=>'hero','list_item_style'=>'simple','post_listing_layout'=>'columns','column_style'=>'masonry','columns_per_row'=>4,'items_limit_source'=>'custom','posts_per_page'=>3];
 $stale=['featured_slider_style'=>'card','list_item_style'=>'card','post_listing_layout'=>'rows','column_style'=>'grid','columns_per_row'=>2,'items_limit_source'=>'50','posts_per_page'=>50];
 $global=Joomla\CMS\Component\ComponentHelper::getParams($component);$page=$app->getParams();
 foreach($expected as $key=>$value)$global->set($key,$value);
 foreach($stale as $key=>$value)$page->set($key,$value);
 $page->set('compact_show',0);$page->set('listing_canonical','https://example.com/featured');
 $model=$factory->createModel('Featured','Site');$params=$model->getState('params');
 foreach($expected as $key=>$value)if($params->get($key)!==$value)throw new RuntimeException('Stale menu override: '.$key);
 if((int)$model->getState('list.limit')!==3)throw new RuntimeException('Wrong page size');
 if($params->get('listing_canonical')!=='https://example.com/featured')throw new RuntimeException('Unrelated menu setting changed');
 $items=$model->getItems();if($items===false||count($items)>3)throw new RuntimeException('Query page limit');
 echo "$family: seven component controls override stale menu settings; page-size query and remaining menu options passed.\n";
}

