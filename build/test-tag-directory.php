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
function check($actual,$expected,$label) { if($actual!==$expected) throw new RuntimeException($label.': '.json_encode($actual)); }
function fails(Closure $call,string $message): void { try {$call();} catch(Throwable $e) {if(str_contains($e->getMessage(),$message))return;throw $e;} throw new RuntimeException('Expected failure: '.$message); }


foreach (['academy','blog','codex'] as $f) {
    $app->getInput()->set('option','com_'.$f);$app->getInput()->set('view','tags');$app->getInput()->set('tag_id',0);
    $base=__DIR__.'/../'.$f.'/com_'.$f;
    $class='Joomla\\Component\\'.ucfirst($f).'\\Site\\View\\Tags\\HtmlView';
    $helper='Joomla\\Component\\'.ucfirst($f).'\\Site\\Helper\\TagDirectoryHelper';
    $params=$app->getParams('com_'.$f);
    foreach (['link_grid','image_grid'] as $style) {
        foreach (['rows','columns'] as $layout) {
            foreach (['grid','masonry'] as $column) {
                $params->set('tags_style',$style);$params->set('tags_listing_layout',$layout);$params->set('tags_column_style',$column);$params->set('tags_per_page',2);
                $view=new $class(['base_path'=>$base.'/site','template_path'=>$base.'/site/tmpl/tags']);$view->setDocument($app->getDocument());
                ob_start();$view->display('directory');$html=ob_get_clean();
                check(str_contains($html,'data-post-masonry'),$layout==='columns' && $column==='masonry','masonry condition');
                check(str_contains($html,'category-directory-image'),$style==='image_grid' && count($view->tags)>0,'image layout');
                check(count($view->tags)<=2,true,'page limit');
                $db=$c->get(Joomla\Database\DatabaseInterface::class);
                foreach ($view->tags as $tag) {
                    $actual=count($db->setQuery($helper::posts((int)$tag->id))->loadObjectList());
                    check((int)$tag->post_count,$actual,'count matches accessible posts');
                }
            }
        }
    }
    $params->set('tags_show_search',1);$app->getInput()->set('tag_search','no-match-fixture-123456');
    $view=new $class(['base_path'=>$base.'/site','template_path'=>$base.'/site/tmpl/tags']);$view->setDocument($app->getDocument());ob_start();$view->display('directory');ob_end_clean();check($view->tags,[],'search filters tags');
    $app->getInput()->set('tag_search','');$app->getInput()->set('limitstart',999999);
    $view=new $class(['base_path'=>$base.'/site','template_path'=>$base.'/site/tmpl/tags']);$view->setDocument($app->getDocument());ob_start();$view->display('directory');ob_end_clean();check($view->pagination->limitstart < max(1,$view->pagination->total),true,'out-of-range pagination');
    $app->getInput()->set('limitstart',0);
    $global=new Joomla\CMS\Form\Form('global');$global->loadFile($base.'/admin/forms/settings.xml');
    $menu=new Joomla\CMS\Form\Form('menu');$menu->load('<form>'.simplexml_load_file($base.'/site/tmpl/tags/default.xml')->fields[1]->asXML().'</form>');
    foreach($global->getFieldset('tags_directory') as $field) {check((bool)$menu->getField($field->fieldname,'params'),true,'menu parity '.$field->fieldname);}
    $globalParams=ComponentHelper::getParams('com_'.$f);
    $db=$c->get(Joomla\Database\DatabaseInterface::class);
    $posts=$db->setQuery($helper::posts())->loadObjectList();
    if ($posts) {
        $post=$posts[0];
        $globalParams->set('tags_exclude_posts',[(int)$post->id]);
        check(in_array((int)$post->id,array_map('intval',$db->setQuery($helper::posts()->clear('select')->select('p.id'))->loadColumn())),false,'post exclusion');
        $globalParams->set('tags_exclude_posts',[]);
        $globalParams->set('tags_exclude_categories',[(int)$post->catid]);
        check(in_array((int)$post->catid,array_map('intval',$db->setQuery($helper::posts()->clear('select')->select('p.catid'))->loadColumn())),false,'category exclusion');
        $globalParams->set('tags_exclude_categories',[]);
    }
    $tags=$db->setQuery($helper::tags())->loadObjectList();
    if ($tags) {
        $globalParams->set('tags_exclude_tags',[(int)$tags[0]->id]);
        fails(fn()=>$helper::tag((int)$tags[0]->id),Joomla\CMS\Language\Text::_('JGLOBAL_RESOURCE_NOT_FOUND'));
        check(in_array((int)$tags[0]->id,array_map('intval',$db->setQuery($helper::tags()->clear('select')->select('t.id'))->loadColumn())),false,'tag exclusion');
        $globalParams->set('tags_exclude_tags',[]);
    }
    echo "$f: 8 layouts, accessible counts, search, pagination and settings parity passed\n";
}
