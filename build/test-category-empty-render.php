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
$app->getLanguage()->load('com_'.$family,JPATH_BASE);
require __DIR__.'/../'.$family.'/com_'.$family.'/site/src/View/Category/HtmlView.php';
$class='Joomla\\Component\\'.ucfirst($family).'\\Site\\View\\Category\\HtmlView';
$view=new $class();
$view->addTemplatePath(__DIR__.'/../'.$family.'/com_'.$family.'/site/tmpl/category');
$view->category=(object)['id'=>0,'title'=>'Fixture','description'=>'','default_image'=>'','language'=>'*'];
$view->pagination=new Joomla\CMS\Pagination\Pagination(0,0,10);
$view->subcategories=[];
foreach (['default','card','standard','learning','simple','nickel'] as $layout) {
    foreach ([0,1] as $show) {
        $view->setLayout($layout);
        $view->params=new Registry(['show_no_posts'=>$show,'featured_slider_enabled'=>0,'num_links'=>0,'show_category_title'=>0]);
        $render=function($path){include $path;};
        ob_start();$render->call($view,__DIR__.'/../'.$family.'/com_'.$family.'/site/tmpl/category/'.$layout.'.php');$html=ob_get_clean();
        $message=Joomla\CMS\Language\Text::_('COM_'.strtoupper($family).'_NO_POSTS');
        if (str_contains($html,$message)!==(bool)$show) { throw new RuntimeException($layout.' no-post rendering failed'); }
    }
}
echo "$family: no-posts Show/Hide rendered correctly in all six category styles.\n";
