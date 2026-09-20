<?php
// Uses connection-local temporary tables; never changes site content.
if (PHP_SAPI !== 'cli') { exit(1); }
define('_JEXEC', 1);
define('JPATH_BASE', rtrim($argv[1] ?? '', '/\\'));
$_SERVER['HTTP_HOST']='localhost';
$_SERVER['REQUEST_URI']='/Joomla/administrator/index.php';
$_SERVER['SCRIPT_NAME']='/Joomla/administrator/index.php';
require JPATH_BASE.'/includes/defines.php';
require JPATH_BASE.'/includes/framework.php';
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
$c=Factory::getContainer();
$c->alias('session.web','session.web.site')->alias('session','session.web.site')->alias(Joomla\Session\SessionInterface::class,'session.web.site');
$app=$c->get(Joomla\CMS\Application\SiteApplication::class);
Factory::$application=$app; $app->createExtensionNamespaceMap();
(new ReflectionMethod($app,'initialiseApp'))->invoke($app); $app->loadDocument();
$config=Factory::getConfig();
$db=(new Joomla\Database\DatabaseFactory())->getDriver('mysqli',['host'=>$config->get('host'),'user'=>$config->get('user'),'password'=>$config->get('password'),'database'=>$config->get('db'),'prefix'=>'slider_test_'.bin2hex(random_bytes(5)).'_']);
$db->setQuery('CREATE TEMPORARY TABLE #__users (id INT,name VARCHAR(50))')->execute();
function check($value,$expected,$label) { if($value!==$expected) throw new RuntimeException($label.': '.json_encode($value)); }
foreach(['academy','blog','codex'] as $f) {
 $base=__DIR__.'/../'.$f.'/com_'.$f;
 require $base.'/site/src/Helper/ListingFilterHelper.php';
 require $base.'/site/src/Helper/FeaturedSliderHelper.php';
 $helper='Joomla\\Component\\'.ucfirst($f).'\\Site\\Helper\\FeaturedSliderHelper';
 $db->setQuery("CREATE TEMPORARY TABLE #__{$f}_categories (id INT,title VARCHAR(50),published INT,access INT,language VARCHAR(10),lft INT,rgt INT)")->execute();
 $db->setQuery("INSERT INTO #__{$f}_categories VALUES (1,'Public',1,1,'*',1,2),(2,'Hidden',0,1,'*',3,4)")->execute();
 $db->setQuery("CREATE TEMPORARY TABLE #__{$f} (id INT,catid INT,created_by INT,featured INT,state INT,access INT,language VARCHAR(10),publish_up DATETIME,publish_down DATETIME,created DATETIME)")->execute();
 $db->setQuery("INSERT INTO #__{$f} VALUES (1,1,10,1,1,1,'*',NULL,NULL,'2026-01-01'),(2,1,10,0,1,1,'*',NULL,NULL,'2026-01-02'),(3,1,10,1,0,1,'*',NULL,NULL,'2026-01-03'),(4,2,10,1,1,1,'*',NULL,NULL,'2026-01-04'),(5,1,10,1,1,999,'*',NULL,NULL,'2026-01-05'),(6,1,10,1,1,1,'*','2099-01-01',NULL,'2026-01-06'),(7,1,10,1,1,1,'*',NULL,'2000-01-01','2026-01-07'),(8,1,20,1,1,1,'*',NULL,NULL,'2026-01-08'),(9,1,10,1,1,1,'*',NULL,NULL,'2026-01-09')")->execute();
 $db->setQuery("CREATE TEMPORARY TABLE #__{$f}_rating (content_id INT,rating_sum INT,rating_count INT)")->execute();
 $db->setQuery("CREATE TEMPORARY TABLE #__{$f}_frontpage (content_id INT,featured_up DATETIME,featured_down DATETIME,ordering INT)")->execute();
 $db->setQuery("INSERT INTO #__{$f}_frontpage VALUES (9,'2099-01-01',NULL,0)")->execute();
 $select=static fn($options)=>array_map(static fn($p)=>(int)$p->id,$helper::items(new Registry($options),0,$db));
 check($helper::imageUrl('images/cover.jpg#joomlaImage://local-images/cover.jpg?width=800&height=600'), Joomla\CMS\Uri\Uri::root().'images/cover.jpg', 'Media Manager metadata');
 check($helper::imageUrl('/Joomla/images/cover.jpg'), '/Joomla/images/cover.jpg', 'root relative image');
 check($helper::imageUrl('https://example.com/cover.jpg'), 'https://example.com/cover.jpg', 'external image');
 check($helper::imageUrl('javascript:alert(1)'), '', 'unsafe scheme');
 check($select([]),[8,1],'visibility and publication schedules');
 check($select(['listing_include_featured'=>0]),[8,1],'showcase independent of ordinary list featured toggle');
 check($select(['listing_exclude_authors'=>[20]]),[1],'author exclusion');
 check($select(['listing_exclude_categories'=>[1]]),[],'category exclusion');
 check($select(['featured_slider_count'=>1]),[8],'independent count');
 check($helper::render(new Registry(['featured_slider_enabled'=>0])),'','disabled renderer');
 check($helper::render(new Registry(['featured_slider_enabled'=>1,'featured_slider_all_pages'=>0]),0,10),'','first page only');
 $settings=$helper::settings(new Registry(['featured_slider_image'=>0,'featured_slider_interval'=>'']));
 check($settings->get('featured_slider_image'),0,'explicit hide override');
 $form=new Joomla\CMS\Form\Form('slider-'.$f);
 $form->loadFile($base.'/site/tmpl/category/card.xml',true,'/metadata');
 check(count($form->getFieldset('featured_slider')),18,'featured menu controls');
 echo "$f featured filters, limits, inheritance and form passed\n";
}

// Exercise the real renderer with two fixture slides, including all display toggles.
$params = new Registry(['featured_slider_auto'=>1, 'featured_slider_interval'=>60, 'featured_slider_ratings'=>1]);
$item = (object) ['id'=>1,'alias'=>'fixture','catid'=>1,'language'=>'*','media'=>'{"featured_image":"images/cover.jpg#joomlaImage://local-images/cover.jpg?width=800&height=600"}','title'=>'Featured preview','sliderText'=>'An independent featured card above the regular post list.','rating_count'=>2,'rating_sum'=>8,'created_by'=>0,'created_by_alias'=>'','author'=>'Example author','category_title'=>'Example category','created'=>'2026-01-01 00:00:00','params'=>new Registry(['show_date'=>1,'date_type'=>'created'])];
$html=Joomla\CMS\Layout\LayoutHelper::render('featured.card',['items'=>[$item,clone $item],'params'=>$params],__DIR__.'/../academy/com_academy/site/layouts');
check(substr_count($html,'class="carousel-item '),2,'rendered slides');
check(str_contains($html,'data-featured-pause'),true,'pause control');
check(str_contains($html,'data-bs-slide="next"'),true,'navigation');
foreach(['image','title','category','author','avatar','readmore','navigation','content','ratings','date'] as $key) $params->set('featured_slider_'.$key,0);
$params->set('featured_slider_auto',0);
$hidden=Joomla\CMS\Layout\LayoutHelper::render('featured.card',['items'=>[$item],'params'=>$params],__DIR__.'/../academy/com_academy/site/layouts');
foreach(['Featured preview','Example author','Example category','Continue reading','postmeta-avatar','fa-star','data-featured-pause','<footer'] as $text) check(str_contains($hidden,$text),false,'hidden '.$text);

$gallery = '';
foreach (['default','hero','magazine','side-navigation','slick','thumbnail'] as $style) {
    $styleParams = new Registry(['featured_slider_auto'=>0, 'featured_slider_ratings'=>1]);
    $rendered = Joomla\CMS\Layout\LayoutHelper::render('featured.'.$style,['items'=>[$item,clone $item],'params'=>$styleParams],__DIR__.'/../academy/com_academy/site/layouts');
    check(substr_count($rendered,'class="carousel-item '),2,$style.' slides');
    check(str_contains($rendered,'joomlaImage://'),false,$style.' cleans image metadata');
    check(str_contains($rendered,'images/cover.jpg'),true,$style.' renders featured image');
    check(str_contains($rendered,'featured-style-'.$style),true,$style.' selected');
    if (in_array($style,['thumbnail','side-navigation'])) check(str_contains($rendered,'featured-choice'),true,$style.' selectors');
    foreach(['image','title','category','author','avatar','readmore','navigation','content','ratings','date'] as $key) $styleParams->set('featured_slider_'.$key,0);
    $off=Joomla\CMS\Layout\LayoutHelper::render('featured.'.$style,['items'=>[$item],'params'=>$styleParams],__DIR__.'/../academy/com_academy/site/layouts');
    foreach(['Featured preview','Example author','Example category','Continue reading','postmeta-avatar','fa-star','featured-choices'] as $text) check(str_contains($off,$text),false,$style.' hidden '.$text);
    $gallery .= '<h1>'.ucwords(str_replace('-',' ',$style)).'</h1>'.$rendered;
}
$html = $gallery;
if (!empty($argv[2])) {
 $options=$app->getDocument()->getScriptOptions('bootstrap.carousel');
 file_put_contents($argv[2], '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="/Joomla/media/templates/site/cassiopeia/css/template.min.css"><link rel="stylesheet" href="/Joomla/media/system/css/joomla-fontawesome.min.css"><link rel="stylesheet" href="/Joomla/media/com_academy/css/featured-styles.css"><script>window.Joomla={getOptions:()=>'.json_encode($options).'};</script><script type="module" src="/Joomla/media/vendor/bootstrap/js/carousel.js"></script><script defer src="/Joomla/media/com_academy/js/featured-slider.js"></script></head><body><main class="container my-4" style="max-width:900px">'.$html.'</main></body></html>');
}
echo "Renderer visibility and navigation passed\n";



