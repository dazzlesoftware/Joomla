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
define('JPATH_COMPONENT',JPATH_BASE.'/components/com_'.$family);
$root=__DIR__.'/../'.$family.'/com_'.$family;
require $root.'/site/src/View/Categories/HtmlView.php';
$class='Joomla\\Component\\'.ucfirst($family).'\\Site\\View\\Categories\\HtmlView';
$view=new $class();$view->setDocument($app->getDocument());
$view->items=[(object)['id'=>0,'title'=>'Fixture <Title>','default_image'=>'images/cover.jpg','numitems'=>2],(object)['id'=>0,'title'=>'Empty','default_image'=>'','numitems'=>0]];
$render=function($path){include $path;};
foreach ([[],['categories_style'=>'image_grid'],['categories_style'=>'image_grid','categories_column_style'=>'masonry','categories_columns'=>4],['categories_style'=>'image_grid','categories_listing_layout'=>'rows']] as $values) {
    $view->params=new Registry($values);
    ob_start();$render->call($view,$root.'/site/tmpl/categories/default.php');$html=ob_get_clean();
    $grid=($values['categories_style']??'list')==='image_grid';
    if (str_contains($html,'category-directory-card')!==$grid) { throw new RuntimeException('Style selection failed'); }
    if (!str_contains($html,'Fixture &lt;Title&gt;')) { throw new RuntimeException('Escaping failed'); }
    if ($grid && (!str_contains($html,'images/cover.jpg') || !str_contains($html,'fa-solid fa-image'))) { throw new RuntimeException('Image/placeholder rendering failed'); }
    if (($values['categories_column_style']??'')==='masonry' && !str_contains($html,'data-post-masonry')) { throw new RuntimeException('Masonry missing'); }
    if (($values['categories_listing_layout']??'')==='rows' && !str_contains($html,'row-cols-md-1')) { throw new RuntimeException('Rows ignored'); }
}
foreach (['admin/forms/settings.xml','site/tmpl/categories/default.xml'] as $file) {
    $xml=simplexml_load_file($root.'/'.$file);
    foreach (['categories_style','categories_listing_layout','categories_column_style','categories_columns'] as $name) {
        $fields=$xml->xpath('//field[@name="'.$name.'"]');
        if (count($fields)!==1) { throw new RuntimeException('Missing/duplicate field '.$name); }
        if (str_starts_with($file,'site/') && (string)$fields[0]['useglobal']!=='true') { throw new RuntimeException('Inheritance missing'); }
    }
}
echo "$family: default list, grid, image fallback, rows, masonry and settings inheritance passed.\n";

