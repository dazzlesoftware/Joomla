<?php
// Read-only form construction and field rendering. No saved settings or secrets are loaded.
if (PHP_SAPI !== 'cli') { exit(1); }
define('_JEXEC', 1);
define('JPATH_BASE', realpath('C:/wamp64/www/Joomla/administrator'));
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['HTTP_HOST']='localhost';
$_SERVER['REQUEST_URI']='/Joomla/administrator/index.php';
$_SERVER['SCRIPT_NAME']='/Joomla/administrator/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container=Joomla\CMS\Factory::getContainer();
$container->alias('session.web','session.web.administrator')->alias('session','session.web.administrator')->alias(Joomla\Session\SessionInterface::class,'session.web.administrator');
$app=$container->get(Joomla\CMS\Application\AdministratorApplication::class);
Joomla\CMS\Factory::$application=$app;
$app->createExtensionNamespaceMap();
(new ReflectionMethod($app,'initialiseApp'))->invoke($app);
$app->loadDocument();

$model=$app->bootComponent('com_menus')->getMVCFactory()->createModel('Menutypes','Administrator',['ignore_request'=>true]);
$model->setState('client_id',0);
$lookup=$model->getReverseLookup();
$db=Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
$results=[];
foreach (json_decode(file_get_contents(__DIR__.'/menus.json'),true) as $menu) {
 $link=$db->setQuery('SELECT link FROM #__menu WHERE id='.(int)$menu['id'])->loadResult();
 $key=Joomla\Component\Menus\Administrator\Helper\MenusHelper::getLinkKey($link);
 $title=$lookup[$key]??'';
 $results[]=['family'=>$menu['family'],'view'=>$menu['view'],'layout'=>$menu['layout'],'recognized'=>$title!=='','title'=>$title];
}
file_put_contents(__DIR__.'/menu-types.json',json_encode($results,JSON_PRETTY_PRINT));
echo count($results).' menu types; '.count(array_filter($results,fn($r)=>!$r['recognized']))." missing labels.\n";
