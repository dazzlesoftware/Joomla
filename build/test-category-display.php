<?php
// Offline provider fixtures. No network requests, saved settings or content changes.
if (PHP_SAPI !== 'cli') { exit(1); }
define('_JEXEC', 1);
define('JPATH_BASE', rtrim($argv[1] ?? '', '/\\'));
$_SERVER['HTTP_HOST']='localhost';
$_SERVER['REQUEST_URI']='/Joomla/index.php';
$_SERVER['SCRIPT_NAME']='/Joomla/index.php';
require JPATH_BASE.'/includes/defines.php';
require JPATH_BASE.'/includes/framework.php';
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
$c=Factory::getContainer();
$c->alias('session.web','session.web.site')->alias('session','session.web.site')->alias(Joomla\Session\SessionInterface::class,'session.web.site');
$app=$c->get(Joomla\CMS\Application\SiteApplication::class);
Factory::$application=$app; $app->createExtensionNamespaceMap();
(new ReflectionMethod($app,'initialiseApp'))->invoke($app); $app->loadDocument();
// Verify depth and visibility with fixture trees and database-backed loading.
function check($value, $expected, $label) { if ($value !== $expected) { throw new RuntimeException($label); } }
$db = Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
foreach (['academy','blog','codex'] as $family) {
    $root=__DIR__.'/../'.$family.'/com_'.$family;
    require $root.'/site/src/Helper/SubcategoriesHelper.php';
    require $root.'/admin/src/Helper/CategoriesHelper.php';
    $helper='Joomla\\Component\\'.ucfirst($family).'\\Site\\Helper\\SubcategoriesHelper';
    $categories='Joomla\\Component\\'.ucfirst($family).'\\Administrator\\Helper\\CategoriesHelper';
    $rows=[];
    foreach ([[2,1,0],[3,2,1],[4,1,0],[5,3,1],[6,99,1]] as [$id,$parent,$count]) {
        $rows[]=(object)['id'=>$id,'parent_id'=>$parent,'numitems'=>$count];
    }
    check($helper::tree($rows,1,0,true),[],'None');
    $one=$helper::tree($rows,1,1,false);
    check(count($one),1,'empty leaf hidden, populated branch retained');check($one[0]->children,[],'one level');
    $two=$helper::tree($rows,1,2,false);
    check($two[0]->children[0]->id,3,'second level');check($two[0]->children[0]->children,[],'third level hidden');
    check($helper::tree($rows,1,-1,false)[0]->children[0]->children[0]->id,5,'all levels');
    check(count($helper::tree($rows,1,1,true)),2,'show empty categories');
    check(count($helper::tree([(object)['id'=>1,'parent_id'=>2,'numitems'=>1],(object)['id'=>2,'parent_id'=>1,'numitems'=>1]],1,-1,true)),1,'cycle protection');
    $db->transactionStart();
    try {
        $title='Subcategories fixture '.bin2hex(random_bytes(5));
        $parent=$categories::save(['title'=>$title]);
        $child=$categories::save(['title'=>$title.' child','parent_id'=>$parent]);
        $grandchild=$categories::save(['title'=>$title.' grandchild','parent_id'=>$child]);
        $categories::save(['title'=>$title.' unpublished','parent_id'=>$parent,'published'=>0]);
        $categories::save(['title'=>$title.' restricted','parent_id'=>$parent,'access'=>999999]);
        $params=new Registry(['maxLevel'=>0,'show_empty_categories'=>1]);
        check($helper::load($parent,$params),[],'database None');
        $params->set('maxLevel',1);$items=$helper::load($parent,$params);
        check(count($items),1,'database publication/access filtering');check((int)$items[0]->id,$child,'correct direct child');check($items[0]->children,[],'database depth one');
        $params->set('maxLevel',2);check((int)$helper::load($parent,$params)[0]->children[0]->id,$grandchild,'database depth two');
        $params->set('show_empty_categories',0);check($helper::load($parent,$params),[],'database hide empty');
    } finally { $db->transactionRollback(); }
    foreach (['card.php','default.php'] as $name) {
        $template=file_get_contents($root.'/site/tmpl/category/'.$name);
        check(str_contains($template,"get('show_no_posts', 1)"),true,'correct no-post setting');
        check(str_contains($template,"loadTemplate('subcategories')"),true,'subcategory render connected');
    }
    echo "$family: subcategory depth, empty branches, access, publication and settings wiring passed.\n";
}
