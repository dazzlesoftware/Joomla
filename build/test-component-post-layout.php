<?php
if(PHP_SAPI!=='cli')exit(1);
$argv=['','C:/wamp64/www/Joomla'];require __DIR__.'/test-subcategory-styles.php';
define('JPATH_COMPONENT',JPATH_ROOT.'/components/com_academy');
$db=Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
foreach(['academy','blog','codex'] as $family){
 $component='com_'.$family;$factory=$app->bootComponent($component)->getMVCFactory();
 $id=(int)$db->setQuery('SELECT id FROM #__'.$family.' WHERE state=1 AND access=1 LIMIT 1')->loadResult();
 foreach(['default','wiki'] as $layout){
  $global=Joomla\CMS\Component\ComponentHelper::getParams($component);$global->set('post_layout',$layout);$global->set('show_wiki_details',0);
  $input=$app->getInput();$input->set('option',$component);$input->set('view','post');$input->set('id',$id);$input->set('layout',$layout==='wiki'?'default':'wiki');
  $model=$factory->createModel('Post','Site');$item=$model->getItem($id);$item->params->set('post_layout',$layout==='wiki'?'default':'wiki');$item->params->set('show_wiki_details',1);
  $view=$factory->createView('Post','Site','html');$view->setModel($model,true);$view->addTemplatePath(JPATH_ROOT.'/components/'.$component.'/tmpl/post');$view->setDocument($app->getDocument());
  ob_start();$view->display();$html=ob_get_clean();
  if($view->getLayout()!==$layout || $item->params->get('show_wiki_details')!==0 || !$html)throw new RuntimeException('Component-only layout failed '.$family.'/'.$layout);
 }
 echo "$family: Default/Wiki render; component settings override saved post and requested layouts.\n";
}
