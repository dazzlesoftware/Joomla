<?php
if (PHP_SAPI !== 'cli') { exit(1); }
$argv=['','C:/wamp64/www/Joomla'];
require __DIR__.'/test-subcategory-styles.php';
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\Registry\Registry;
foreach (['academy','blog','codex'] as $family) {
    $component='com_'.$family;
    $factory=$app->bootComponent($component)->getMVCFactory();
    $app->getInput()->set('option',$component);
    $app->getInput()->set('view','featured');
    $model=$factory->createModel('Posts','Site');
    $items=$model->getItems();
    if (!$items) { throw new RuntimeException('No fixture posts: '.$family); }
    $item=clone $items[0];
    $item->params=clone ComponentHelper::getParams($component);
    $item->params->set('engagement_ratings',1);
    foreach (['layouts','tmpl/tags/layouts','tmpl/archive/layouts'] as $path) {
        $layout=new FileLayout('post.meta',JPATH_SITE.'/components/'.$component.'/'.$path);
        foreach ([0,1] as $shown) {
            $item->params->set('show_rating',$shown);
            $html=$layout->render($item);
            if(str_contains($html,'postmeta-rating') !== (bool)$shown) {throw new RuntimeException($family.' '.$path.' rating visibility');}
        }
    }
    ComponentHelper::getParams($component)->set('engagement_ratings',1);
    $layout=new FileLayout('engagement',JPATH_SITE.'/components/'.$component.'/layouts');
    foreach([0,1] as $shown) {
        $item->params->set('show_rating',$shown);
        if(str_contains($layout->render($item),'class="post-rating ') !== (bool)$shown) {throw new RuntimeException('Post rating visibility');}
    }
    $controller='Joomla\\Component\\'.ucfirst($family).'\\Site\\Controller\\PostController';
    if(method_exists($controller,'vote')) {throw new RuntimeException('Legacy vote endpoint remains');}
    $postModel=$factory->createModel('Post','Site');
    if(method_exists($postModel,'storeVote')) {throw new RuntimeException('Legacy vote storage remains');}
    echo "$family: shared, tag, archive and single-post Rating visibility; legacy endpoint removal passed.\n";
}
