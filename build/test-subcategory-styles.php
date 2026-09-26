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
foreach (['academy','blog','codex'] as $family) {
    $root=__DIR__.'/../'.$family.'/com_'.$family;
    $child=(object)['id'=>0,'title'=>'Child <safe>','language'=>'*','numitems'=>0,'description'=>'Child description','default_image'=>'images/cover.jpg','children'=>[]];
    $parent=clone $child;$parent->title='Parent';$parent->default_image='';$parent->children=[$child];
    foreach ([[],['subcategories_style'=>'link_list','subcategories_listing_layout'=>'rows'],['subcategories_style'=>'link_list','subcategories_column_style'=>'masonry','subcategories_columns'=>4],['subcategories_style'=>'link_list','subcategories_columns'=>2],['subcategories_style'=>'image_grid'],['subcategories_style'=>'image_grid','subcategories_column_style'=>'masonry','subcategories_columns'=>4],['subcategories_style'=>'image_grid','subcategories_listing_layout'=>'rows']] as $values) {
        $displayData=['items'=>[$parent], 'params'=>new Registry($values)];
        ob_start();include $root.'/site/layouts/category-subcategories.php';$html=ob_get_clean();
        if (!str_contains($html, 'row-cols-md-' . (($values['subcategories_listing_layout'] ?? 'columns') === 'rows' ? 1 : ($values['subcategories_columns'] ?? 3)))) { throw new RuntimeException('Column count'); }
        $grid=($values['subcategories_style']??'link_list')==='image_grid';
        if (str_contains($html,'category-directory-card')!==$grid) { throw new RuntimeException('Wrong style'); }
        if (!str_contains($html,'Child &lt;safe&gt;')) { throw new RuntimeException('Child rendering/escaping'); }
        if ($grid && (!str_contains($html,'images/cover.jpg') || !str_contains($html,'fa-solid fa-image'))) { throw new RuntimeException('Image/placeholder'); }
        if (($values['subcategories_column_style']??'')==='masonry' && !str_contains($html,'data-post-masonry')) { throw new RuntimeException('Masonry'); }
        if (($values['subcategories_listing_layout']??'')==='rows' && !str_contains($html,'row-cols-md-1')) { throw new RuntimeException('Rows'); }
    }
    $displayData=['items'=>[], 'params'=>new Registry(['subcategories_style'=>'image_grid'])];
    ob_start();include $root.'/site/layouts/category-subcategories.php';$html=ob_get_clean();
    if (trim($html)!=='') { throw new RuntimeException('Empty tree must stay hidden'); }
    foreach (array_merge([$root.'/admin/forms/settings.xml'],glob($root.'/site/tmpl/category/*.xml')) as $file) {
        if (basename($file) === 'metadata.xml') { continue; }
        $xml=simplexml_load_file($file);
        foreach (['subcategories_style','subcategories_listing_layout','subcategories_column_style','subcategories_columns'] as $name) {
            $fields=$xml->xpath('//field[@name="'.$name.'"]');
            if (count($fields)!==1) { throw new RuntimeException('Missing/duplicate '.$name.' in '.$file); }
            if (str_contains($file,'/site/') && (string)$fields[0]['useglobal']!=='true') { throw new RuntimeException('Missing global inheritance'); }
        }
    }
    echo "$family: list, grid, rows, masonry, nested children, images, empty state and all menu settings passed.\n";
}
