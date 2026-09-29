<?php
if(PHP_SAPI!=='cli')exit(1);
$argv=['','C:/wamp64/www/Joomla'];require __DIR__.'/test-subcategory-styles.php';
foreach(['academy','blog','codex'] as $family){
 $component='com_'.$family;$app->bootComponent($component);
 $base=JPATH_ROOT.'/components/'.$component.'/layouts';
 $params=new Joomla\Registry\Registry(['compact_show'=>1,'compact_show_rating'=>0,'compact_style'=>'unsupported-menu-override']);
 $data=['params'=>$params,'items'=>[(object)['id'=>1,'alias'=>'test','catid'=>0,'language'=>'*','title'=>'Compact style test','media'=>'{}']]];
 $expected=Joomla\CMS\Layout\LayoutHelper::render('compact.default',$data,$base);
 foreach(['default','invalid',''] as $style){
  Joomla\CMS\Component\ComponentHelper::getParams($component)->set('compact_style',$style);
  $actual=Joomla\CMS\Layout\LayoutHelper::render('compact-posts',$data,$base);
  if($actual!==$expected||!str_contains($actual,'Compact style test'))throw new RuntimeException('Compact dispatch failed '.$family.'/'.$style);
 }
 echo "$family: Default file rendering and unknown/empty style fallback passed.\n";
}
