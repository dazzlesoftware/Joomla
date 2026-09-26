<?php
if (PHP_SAPI !== 'cli') exit(1);
$argv = ['', 'C:/wamp64/www/Joomla'];
require dirname(__DIR__) . '/test-subcategory-styles.php';
$db = Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
function menu($title, $alias, $parent, $link, $component, $params = [], $type = 'component') {
 global $db;
 $id = (int) $db->setQuery('SELECT id FROM #__menu WHERE alias=' . $db->quote($alias) . ' AND menutype=\'mainmenu\'')->loadResult();
 if ($id) return $id;
 $table = new Joomla\CMS\Table\Menu($db);
 $table->setLocation($parent, 'last-child');
 $table->bind(['menutype'=>'mainmenu','title'=>$title,'alias'=>$alias,'link'=>$link,'type'=>$type,'published'=>1,'parent_id'=>$parent,'component_id'=>$component,'access'=>1,'language'=>'*','client_id'=>0,'params'=>json_encode($params)]);
 if (!$table->check() || !$table->store()) throw new RuntimeException($table->getError());
 return (int)$table->id;
}
$root=menu('Extension Tests','extension-tests',1,'#',0,[],'separator');
$manifest=[];
foreach (['academy','blog','codex'] as $f) {
 $component=(int)$db->setQuery('SELECT extension_id FROM #__extensions WHERE element='.$db->quote('com_'.$f).' AND type=\'component\'')->loadResult();
 $helper='Joomla\\Component\\'.ucfirst($f).'\\Administrator\\Helper\\CategoriesHelper';
 $cat=(int)$db->setQuery('SELECT id FROM #__'.$f.'_categories WHERE alias=\'settings-audit\'')->loadResult();
 if (!$cat) $cat=$helper::save(['title'=>'Settings Audit','alias'=>'settings-audit','description'=>'Audit base category description.','published'=>1,'access'=>1,'language'=>'*']);
 foreach (['Empty','Child','Grandchild'] as $label) {
  $alias='settings-audit-'.strtolower($label);
  $id=(int)$db->setQuery('SELECT id FROM #__'.$f.'_categories WHERE alias='.$db->quote($alias))->loadResult();
  if (!$id) $id=$helper::save(['title'=>'Audit '.$label,'alias'=>$alias,'description'=>'Audit '.$label.' description.','parent_id'=>$label==='Grandchild'?$child:$cat,'published'=>1,'access'=>1,'language'=>'*']);
  if ($label==='Child') $child=$id;
 }
 $author=(int)$db->setQuery('SELECT MIN(id) FROM #__users')->loadResult();$posts=[];
 for ($i=1;$i<=16;$i++) {
  $alias='settings-audit-post-'.$i;
  $id=(int)$db->setQuery('SELECT id FROM #__'.$f.' WHERE alias='.$db->quote($alias))->loadResult();
  if (!$id) {
   $row=(object)['title'=>'Audit Post '.sprintf('%02d',$i),'alias'=>$alias,'excerpt'=>'','summary'=>'<p>Audit paragraph one '.$i.'.</p><p>Audit paragraph two.</p><p>Audit paragraph three.</p>','body'=>'<p>Full audit content.</p>','catid'=>$i>12?$child:$cat,'state'=>1,'created'=>'2026-01-'.sprintf('%02d',$i).' 12:00:00','modified'=>'2026-02-'.sprintf('%02d',17-$i).' 12:00:00','publish_up'=>'2026-03-'.sprintf('%02d',$i).' 12:00:00','created_by'=>$author,'media'=>json_encode($i%2?['featured_image'=>'images/joomla_black.png','featured_image_alt'=>'Audit cover']:[]),'options'=>'{}','metadata'=>'{}','metadesc'=>'Audit description','access'=>1,'featured'=>$i%2,'language'=>'*','ordering'=>$i];
   $db->insertObject('#__'.$f,$row,'id');$id=$row->id;
  }
  $posts[]=$id;
 }
 $parent=menu(ucfirst($f).' Tests','extension-tests-'.$f,$root,'#',0,[],'separator');
 foreach (glob(dirname(__DIR__,2).'/'.$f.'/com_'.$f.'/site/tmpl/*/*.xml') as $file) {
  $xml=simplexml_load_file($file);if (!$xml->layout || (string)$xml->layout['hidden']==='true') continue;
  $view=basename(dirname($file));$layout=basename($file,'.xml');
  $query=['option'=>'com_'.$f,'view'=>$view];
  if ($layout !== 'default') $query['layout']=$layout;
  if (in_array($view,['category','categories'])) $query['id']=$cat;
  if ($view==='post') $query['id']=$posts[0];
  if ($view==='author') $query['id']=$author;
  $params=['listing_categories'=>[$cat],'listing_subcategories'=>1,'posts_per_page'=>3,'items_limit_source'=>'custom','num_links'=>2,'compact_show'=>1,'show_no_posts'=>1,'show_empty_categories'=>1,'show_empty_categories_cat'=>1,'featured_slider_enabled'=>1];
  $id=menu(ucfirst($view).' — '.$layout,'audit-'.$f.'-'.$view.'-'.$layout,$parent,'index.php?'.http_build_query($query),$component,$params);
  $manifest[]=['family'=>$f,'view'=>$view,'layout'=>$layout,'id'=>$id,'form'=>$file,'category'=>$cat,'post'=>$posts[0],'url'=>'http://localhost/Joomla/index.php?Itemid='.$id];
 }
}
file_put_contents(__DIR__.'/menus.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo count($manifest)." test menu types created or reused.\n";
