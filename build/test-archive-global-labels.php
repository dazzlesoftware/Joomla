<?php
if (PHP_SAPI !== 'cli') { exit(1); }
require __DIR__.'/settings-audit/forms.php';
$app->getInput()->set('option','com_menus');
foreach (['academy','blog','codex'] as $family) {
 $component='com_'.$family;
 $params=Joomla\CMS\Component\ComponentHelper::getParams($component);
 foreach ([null,0,1] as $value) {
  Joomla\CMS\Component\ComponentHelper::getParams($component)->set('archive_posts_featured_slider_enabled',$value);
  $form=new Joomla\CMS\Form\Form('test.'.$family.'.'.var_export($value,true),['control'=>'jform']);
  $form->loadFile(JPATH_ROOT.'/components/'.$component.'/tmpl/archive/default.xml',true,'/metadata');
  $form->bind(['link'=>'index.php?option='.$component.'&view=archive']);
  $field=$form->getField('archive_posts_featured_slider_enabled','params');
  $options=(new ReflectionMethod($field,'getOptions'))->invoke($field);
  $expected=Joomla\CMS\Language\Text::sprintf('JGLOBAL_USE_GLOBAL_VALUE',Joomla\CMS\Language\Text::_($value===0?'JHIDE':'JSHOW'));
  if($options[0]->text!==$expected)throw new RuntimeException($family.' value='.var_export($value,true).' expected='.$expected.' global label: '.$options[0]->text);
 }
 echo "$family: unsaved default, saved Hide and saved Show labels passed.\n";
}



