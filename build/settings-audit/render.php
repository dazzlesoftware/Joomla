<?php
if (PHP_SAPI !== 'cli') exit(1);
$case=json_decode(file_get_contents($argv[1]),true);
define('_JEXEC',1);define('JPATH_BASE','C:/wamp64/www/Joomla');
$_SERVER['REQUEST_METHOD']='GET';$_SERVER['HTTP_HOST']='localhost';$_SERVER['REQUEST_URI']='/Joomla/index.php';$_SERVER['SCRIPT_NAME']='/Joomla/index.php';
require JPATH_BASE.'/includes/defines.php';require JPATH_BASE.'/includes/framework.php';
$c=Joomla\CMS\Factory::getContainer();$c->alias('session.web','session.web.site')->alias('session','session.web.site')->alias(Joomla\Session\SessionInterface::class,'session.web.site');
$app=$c->get(Joomla\CMS\Application\SiteApplication::class);Joomla\CMS\Factory::$application=$app;$app->createExtensionNamespaceMap();
(new ReflectionMethod($app,'initialiseApp'))->invoke($app);$app->loadDocument();
// CLI-only authenticated rendering; never creates a login session or changes permissions.
if (!empty($case['authenticated'])) {
 $db=$c->get(Joomla\Database\DatabaseInterface::class);
 $id=(int)$db->setQuery('SELECT MIN(id) FROM #__users WHERE block=0')->loadResult();
 $app->loadIdentity($c->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById($id));
}
$menu=$app->getMenu();$menu->setActive($case['menu']);$active=$menu->getActive();
foreach($active->query as $key=>$value)$app->getInput()->set($key,$value);
$app->getInput()->set('Itemid',$case['menu']);
foreach ($case['request'] ?? [] as $key => $value) $app->getInput()->set($key, $value);
$option='com_'.$case['family'];
$app->getDocument()->getWebAssetManager()->getRegistry()->addExtensionRegistryFile($option);
foreach ($case['global'] ?? [] as $key => $value) {
 Joomla\CMS\Component\ComponentHelper::getParams($option)->set($key,$value);
}
if(($case['scope']??'menu')==='component') {
 Joomla\CMS\Component\ComponentHelper::getParams($option)->set($case['name'],$case['value']);
 $active->getParams()->set($case['name'],'');
} else {$active->getParams()->set($case['name'],$case['value']);}
$app->getParams($option)->set($case['name'],$case['value']);
foreach ($case['params'] ?? [] as $key => $value) {
 $active->getParams()->set($key,$value);
 $app->getParams($option)->set($key,$value);
}
try {
 $warnings=[];
 set_error_handler(static function ($severity, $message, $file, $line) use (&$warnings) {
  if (!(error_reporting() & $severity)) { return true; }
  $warnings[]=['severity'=>$severity,'message'=>$message,'file'=>$file,'line'=>$line];
  return true;
 });
 $html=Joomla\CMS\Component\ComponentHelper::renderComponent($option);
 $html=preg_replace('/(?:academy|blog|codex)-featured-[a-f0-9]+/','slider-id',$html);
 echo json_encode(['status'=>'rendered','warnings'=>$warnings,'bytes'=>strlen($html),'hash'=>hash('sha256',$html),'text'=>substr(trim(strip_tags($html)),0,160)] + (!empty($case['html']) ? ['html'=>$html] : []));
} catch(Throwable $e) {echo json_encode(['status'=>'error','code'=>$e->getCode(),'message'=>$e->getMessage()]);}
