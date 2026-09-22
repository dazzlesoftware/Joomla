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
$db=Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
foreach (['academy','blog','codex'] as $family) {
    require __DIR__.'/../'.$family.'/com_'.$family.'/site/src/Service/Router.php';
    require __DIR__.'/../'.$family.'/com_'.$family.'/admin/src/Helper/CategoriesHelper.php';
    $helper='Joomla\\Component\\'.ucfirst($family).'\\Administrator\\Helper\\CategoriesHelper';
    $router=$app->bootComponent('com_'.$family)->createRouter($app,$app->getMenu());
    $db->transactionStart();
    try {
        $title='Route fixture '.bin2hex(random_bytes(5));
        $parent=$helper::save(['title'=>$title]);
        $child=$helper::save(['title'=>$title.' empty','parent_id'=>$parent]);
        foreach ([false,true] as $noIds) {
            (new ReflectionProperty($router,'noIDs'))->setValue($router,$noIds);
            $path=$router->getCategorySegment($child,[]);
            if (array_keys($path)!==[$child,$parent,0]) { throw new RuntimeException('Native route ancestry lost'); }
            $query=['id'=>0];
            foreach (array_reverse($path,true) as $id=>$segment) {
                if (!$id) { continue; }
                $parsed=$router->getCategoryId(str_replace(':','-',$segment),$query);
                if ($parsed!==$id) { throw new RuntimeException('Category route round trip failed'); }
                $query['id']=$parsed;
            }
            $query=['option'=>'com_'.$family,'view'=>'category','id'=>$child];$segments=[];
            (new Joomla\CMS\Component\Router\Rules\NomenuRules($router))->build($query,$segments);
            if (count($segments)!==3 || $segments[0]!=='category') { throw new RuntimeException('Menu-less URL lost category'); }
        }
        if ($router->getCategoryId('does-not-exist',['id'=>0])!==false) { throw new RuntimeException('Unknown alias accepted'); }
        echo "$family: nested empty-category route retained, ID/alias round trips and menu-less URL passed.\n";
    } finally { $db->transactionRollback(); }
}
