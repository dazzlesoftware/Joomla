<?php
if(PHP_SAPI!=='cli')exit(1);
$argv=['','C:/wamp64/www/Joomla'];require __DIR__.'/test-subcategory-styles.php';
define('JPATH_COMPONENT',JPATH_ROOT.'/components/com_academy');
foreach(['academy','blog','codex'] as $family){
 $component='com_'.$family;$app->getInput()->set('option',$component);$app->getInput()->set('view','featured');
 $factory=$app->bootComponent($component)->getMVCFactory();$model=$factory->createModel('Featured','Site');$items=$model->getItems();if(!$items)throw new RuntimeException('No test post');
 $view=$factory->createView('Featured','Site','html');$view->addTemplatePath(JPATH_ROOT.'/components/'.$component.'/tmpl/featured');$view->setDocument($app->getDocument());
 $item=clone $items[0];$item->summary='<p>EXCERPT_VISIBILITY_TEST</p>';$item->event=(object)['afterDisplayTitle'=>'','beforeDisplayContent'=>'','afterDisplayContent'=>''];$item->slug=$item->id.':'.$item->alias;$view->item=$item;
 foreach(['standard','card','learning','simple','nickel'] as $style){
  $item->params->set('list_item_style',$style);
  foreach([0,1] as $show){$item->params->set('show_intro',$show);$html=$view->loadTemplate('item');if(str_contains($html,'EXCERPT_VISIBILITY_TEST')!==(bool)$show)throw new RuntimeException('Excerpt failed '.$family.'/'.$style.'/'.$show);}
 }
 echo "$family: Show and Hide passed in all five Featured styles.\n";
}
