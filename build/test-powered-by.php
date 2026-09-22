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
$family=$argv[2] ?? 'academy';
if (!in_array($family,['academy','blog','codex'],true)) { exit(1); }
$option='com_'.$family;
$app->getInput()->set('option',$option);
$app->getInput()->set('view','categories');
$app->getInput()->set('task','');
$params=ComponentHelper::getParams($option);
foreach ([1,0] as $enabled) {
    $params->set('show_powered_by',$enabled);
    $html=ComponentHelper::renderComponent($option);
    if (substr_count($html,'class="dazzle-credit"')!==$enabled) { throw new RuntimeException('Show/Hide failed'); }
}
// This only changes in-memory parameters; saved settings remain untouched.
echo "$family: dispatcher credit Show/Hide passed without changing saved settings.\n";
