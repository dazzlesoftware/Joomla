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
// Exercise the real save path; roll back all fixture rows.
$db = Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
foreach (['academy','blog','codex'] as $family) {
    require __DIR__.'/../'.$family.'/com_'.$family.'/admin/src/Helper/CategoriesHelper.php';
    $helper = 'Joomla\\Component\\'.ucfirst($family).'\\Administrator\\Helper\\CategoriesHelper';
    $db->transactionStart();
    try {
        $title = 'Alias Regression '.bin2hex(random_bytes(6));
        $base = Joomla\CMS\Application\ApplicationHelper::stringURLSafe($title);
        foreach ([''=>'', '   '=>'-2', 'Custom Alias '.$base=>'custom-alias-'.$base] as $input=>$suffix) {
            $id=$helper::save(['title'=>$title,'alias'=>$input]);
            $actual=$db->setQuery('SELECT alias FROM #__'.$family.'_categories WHERE id='.(int)$id)->loadResult();
            $expected=str_starts_with($input,'Custom') ? $suffix : $base.$suffix;
            if ($actual !== $expected) { throw new RuntimeException($family.': '.$actual.' != '.$expected); }
        }
        $id=$helper::save(['title'=>$title]);
        $actual=$db->setQuery('SELECT alias FROM #__'.$family.'_categories WHERE id='.(int)$id)->loadResult();
        if ($actual !== $base.'-3') { throw new RuntimeException('Missing alias fallback failed'); }
        $helper::save(['id'=>$id,'title'=>$title.' Updated','alias'=>'']);
        $actual=$db->setQuery('SELECT alias FROM #__'.$family.'_categories WHERE id='.(int)$id)->loadResult();
        if ($actual !== $base.'-updated') { throw new RuntimeException('Cleared alias on edit failed'); }
        echo "$family: blank, whitespace, omitted, custom, duplicate and edit aliases passed.\n";
    } finally { $db->transactionRollback(); }
}
