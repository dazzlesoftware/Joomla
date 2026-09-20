<?php
// Read-only with respect to site data: all fixtures use connection-local temporary tables.
if (PHP_SAPI !== 'cli') { exit(1); }
define('_JEXEC', 1);
define('JPATH_BASE', rtrim($argv[1] ?? '', '/\\'));
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/Joomla/administrator/index.php';
$_SERVER['SCRIPT_NAME'] = '/Joomla/administrator/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\Database\DatabaseInterface;
$c = Factory::getContainer();
$c->alias('session.web', 'session.web.administrator')->alias('session', 'session.web.administrator')->alias(Joomla\Session\SessionInterface::class, 'session.web.administrator');
$app = $c->get(Joomla\CMS\Application\AdministratorApplication::class);
Factory::$application = $app;
(new ReflectionMethod($app, 'initialiseApp'))->invoke($app);
$config = Factory::getConfig();
$db = (new Joomla\Database\DatabaseFactory())->getDriver('mysqli', ['host'=>$config->get('host'), 'user'=>$config->get('user'), 'password'=>$config->get('password'), 'database'=>$config->get('db'), 'prefix'=>'listing_test_'.bin2hex(random_bytes(5)).'_']);

function verify($actual, $expected, $label): void {
    if ($actual !== $expected) { throw new RuntimeException($label . ': ' . json_encode($actual) . ' != ' . json_encode($expected)); }
}
foreach (['academy','blog','codex'] as $f) {
    $base = __DIR__ . '/../' . $f . '/com_' . $f;
    require $base . '/site/src/Helper/ListingFilterHelper.php';
    require $base . '/site/src/Helper/ListingSettingsHelper.php';
    $helper = 'Joomla\\Component\\'.ucfirst($f).'\\Site\\Helper\\ListingFilterHelper';
    $count = 'Joomla\\Component\\'.ucfirst($f).'\\Site\\Helper\\ListingSettingsHelper';
    $db->setQuery("CREATE TEMPORARY TABLE #__{$f}_categories (id INT,lft INT,rgt INT)")->execute();
    $db->setQuery("INSERT INTO #__{$f}_categories VALUES (1,1,6),(2,2,3),(3,4,5),(4,7,8)")->execute();
    $db->setQuery("CREATE TEMPORARY TABLE #__{$f} (id INT,catid INT,created_by INT,featured INT)")->execute();
    $db->setQuery("INSERT INTO #__{$f} VALUES (1,1,10,0),(2,2,20,1),(3,3,10,0),(4,4,30,1)")->execute();
    $db->setQuery("CREATE TEMPORARY TABLE #__{$f}_tags (id INT,published INT)")->execute();
    $db->setQuery("INSERT INTO #__{$f}_tags VALUES (7,1),(8,1),(9,0)")->execute();
    $db->setQuery("CREATE TEMPORARY TABLE #__{$f}_tag_map (content_item_id INT,tag_id INT,type_alias VARCHAR(80))")->execute();
    $db->setQuery("INSERT INTO #__{$f}_tag_map VALUES (1,7,'com_{$f}.post'),(1,8,'com_{$f}.post'),(2,7,'com_{$f}.post'),(3,9,'com_{$f}.post'),(4,7,'com_{$f}.category')")->execute();
    $select = static function($params, $category = 0) use ($db,$f,$helper) {
        $q=$db->createQuery()->select('p.id')->from('#__'.$f.' AS p');
        $helper::apply($q,new Registry($params),'p',$category,$db);$q->order('p.id');
        return array_map('intval',$db->setQuery($q)->loadColumn());
    };
    verify($select(['listing_categories'=>[1,4]]),[1,4],'multiple categories');
    verify($select(['listing_categories'=>[1], 'listing_subcategories'=>1]),[1,2,3],'descendants');
    verify($select(['listing_categories'=>[1], 'listing_subcategories'=>1,'listing_exclude_categories'=>[2]]),[1,3],'exclude wins');
    verify($select(['listing_authors'=>[10,20],'listing_exclude_authors'=>[20]]),[1,3],'author exclusion');
    verify($select(['listing_tags'=>[7,8]]),[1,2],'tags are OR, no duplicates or wrong type');
    verify($select(['listing_tags'=>[9]]),[],'unpublished tag');
    verify($select(['listing_include_featured'=>0]),[1,3],'exclude featured');
    verify($select(['listing_pin_featured'=>1]),[2,4,1,3],'pin before normal order');
    verify($select(['listing_categories'=>[1,4]],2),[2],'direct category route');
    verify($count::count(new Registry(['items_limit_source'=>'10','posts_per_page'=>6])),10,'preset');
    verify($count::count(new Registry(['items_limit_source'=>'custom','posts_per_page'=>6])),6,'custom');
    verify($count::count(new Registry(['items_limit_source'=>'custom','posts_per_page'=>0])),1,'positive limit');
    verify($count::count(new Registry(['items_limit_source'=>'joomla'])),max(1,(int)$app->get('list_limit',20)),'Joomla default');
    $form=new Joomla\CMS\Form\Form('listing-'.$f);
    $form->loadFile($base.'/site/tmpl/category/card.xml',true,'/metadata');
    $names=array_map(static fn($field)=>$field->fieldname,array_values($form->getFieldset('request')));
    verify(array_slice($names,0,6),['category_layout','post_listing_layout','column_style','columns_per_row','items_limit_source','posts_per_page'],'Details field order');
    verify($form->getFieldAttribute('posts_per_page','showon','','params'),'items_limit_source:custom','custom input visibility');
    echo "$f filters, ordering, limits and menu form passed\n";
}
