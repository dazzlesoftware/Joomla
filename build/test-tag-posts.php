<?php
if (PHP_SAPI !== 'cli') { exit(1); }
$argv=['','C:/wamp64/www/Joomla'];
require __DIR__.'/test-subcategory-styles.php';
define('JPATH_COMPONENT', JPATH_ROOT.'/components/com_academy');
$db=Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
foreach (['academy','blog','codex'] as $family) {
    $component='com_'.$family;
    $factory=$app->bootComponent($component)->getMVCFactory();
    $app->getInput()->set('option',$component);
    $app->getInput()->set('view','tags');
    $app->getInput()->set('layout','default');
    $tagId=(int)$db->setQuery('SELECT tag_id FROM #__'.$family.'_tag_map GROUP BY tag_id ORDER BY COUNT(*) DESC')->loadResult();
    $fixtureTag = 0;
    if (!$tagId) {
        $tagHelper='Joomla\\Component\\'.ucfirst($family).'\\Administrator\\Helper\\TagsHelper';
        $fixtureTag=$tagId=$tagHelper::save(['title'=>'Temporary Tag Layout Test','alias'=>'temporary-tag-layout-test','published'=>1]);
        foreach ($db->setQuery('SELECT id FROM #__'.$family.' WHERE state=1')->loadColumn() as $postId) {
            $row=(object)['tag_id'=>$tagId,'content_item_id'=>(int)$postId,'type_alias'=>'com_'.$family.'.post'];
            $db->insertObject('#__'.$family.'_tag_map',$row);
        }
    }
    try {
    $app->getInput()->set('tag_id',$tagId);
    $global=Joomla\CMS\Component\ComponentHelper::getParams($component);
    $set=function ($values) use ($global,$app,$component) {
        foreach ($values as $key=>$value) {
            $global->set($key,$value); $app->getParams($component)->set($key,$value);
            if ($app->getMenu()->getActive()) { $app->getMenu()->getActive()->getParams()->set($key,$value); }
        }
    };
    $set(['tag_posts_items_limit_source'=>'custom','tag_posts_posts_per_page'=>2,'tag_posts_show_pagination'=>1,'tag_posts_featured_slider_enabled'=>0,'tags_exclusions_override'=>0,'tags_exclude_posts'=>[],'tags_exclude_categories'=>[],'tags_exclude_tags'=>[]]);
    $render=function ($start=0) use ($app,$factory,$component) {
        $app->getInput()->set('limitstart',$start);
        $model=$factory->createModel('Tags','Site');
        $view=$factory->createView('Tags','Site','html');
        $view->setModel($model,true);
        $view->addTemplatePath(JPATH_ROOT.'/components/'.$component.'/tmpl/tags');
        $view->setDocument($app->getDocument());
        ob_start();$view->display();$html=ob_get_clean();
        return [$view,$html];
    };
    [$view,$html]=$render();
    if(count($view->posts)!==min(2,(int)$view->pagination->total)) { throw new RuntimeException('Tag page size/total '.count($view->posts).'/'.$view->pagination->total.' limit '.$view->pagination->limit.' tag '.$tagId); }
    $first=array_column($view->posts,'id');
    foreach($view->posts as $post) { if(!(int)$db->setQuery('SELECT COUNT(*) FROM #__'.$family.'_tag_map WHERE tag_id='.$tagId.' AND content_item_id='.(int)$post->id)->loadResult()) { throw new RuntimeException('Author scope'); } }
    [$second,$html]=$render($view->pagination->pagesTotal>1 ? 2 : 0);
    if($view->pagination->pagesTotal>1 && array_intersect($first,array_column($second->posts,'id'))) { throw new RuntimeException('Pagination duplicates'); }
    [$last]=$render(999999);
    if($view->pagination->total && !$last->posts) { throw new RuntimeException('Out of range page'); }
    foreach(['standard','card','learning','simple','nickel'] as $style) {
        foreach(['rows','grid','masonry'] as $layout) {
            $view->params->set('list_item_style',$style);
            $view->params->set('post_listing_layout',$layout==='rows'?'rows':'columns');
            $view->params->set('column_style',$layout);
            $view->params->set('columns_per_row',3);
            foreach($view->posts as $post) { $post->params->set('list_item_style',$style); }
            $html=$view->loadTemplate();
            if(($view->posts && !str_contains($html,'post-style-'.$style)) || str_contains($html,'data-post-masonry')!==($layout==='masonry')) { throw new RuntimeException('Style '.$style.'/'.$layout); }
            if(!str_contains($html,'pagination')) { throw new RuntimeException('Pagination markup'); }
        }
    }
    $helper='Joomla\\Component\\'.ucfirst($family).'\\Site\\Helper\\FeaturedSliderHelper';
    $view->params->set('featured_slider_enabled',1);
    $view->params->set('featured_slider_all_pages',0);
    $slides=$helper::items($view->params);
    foreach($slides as $post) { if(!(int)$db->setQuery('SELECT COUNT(*) FROM #__'.$family.'_tag_map WHERE tag_id='.$tagId.' AND content_item_id='.(int)$post->id)->loadResult()) { throw new RuntimeException('Slider scope'); } }
    if (!$slides) { echo "$family: no matching featured fixture; slider empty-state checked.\n"; }
    foreach(['card','default','hero','magazine','side-navigation','slick','thumbnail'] as $style) {
        $view->params->set('featured_slider_style',$style);
        $html=$view->loadTemplate();
        if(($slides && !str_contains($html,'featured-showcase')) || str_contains($html,'&amp;amp;')) { throw new RuntimeException('Slider '.$style.' found='.(int)str_contains($html,'featured-showcase')); }
    }
    if($helper::render($view->params,0,2)!=='') { throw new RuntimeException('First-page slider gating'); }
    // Both configuration surfaces must expose every author-specific control.
    $source=__DIR__.'/../'.$family.'/com_'.$family;
    $globalXml=simplexml_load_file($source.'/admin/forms/settings.xml');
    $menuXml=simplexml_load_file($source.'/site/tmpl/tags/default.xml');
    $fields=$globalXml->xpath('//fieldset[@name="tag_posts" or @name="tag_posts_slider" or @name="tag_posts_compact"]/field');
    $form=Joomla\CMS\Factory::getContainer()->get(Joomla\CMS\Form\FormFactoryInterface::class)->createForm($component.'.author.test',['control'=>'jform']);
    $form->loadFile($source.'/admin/forms/settings.xml');
    $app->getLanguage()->load($component,JPATH_ADMINISTRATOR,'en-GB',true);
    foreach($fields as $field) {
        $name=(string)$field['name'];
        if(count($menuXml->xpath('//field[@name="'.$name.'"]'))!==1) { throw new RuntimeException('Missing menu setting '.$name); }
        if(!$form->getField($name,'params')) { throw new RuntimeException('Missing component field '.$name); }
        $control=$form->getInput($name,'params');
        if(!$control) { throw new RuntimeException('Empty control '.$name); }
        $label=(string)$field['label'];
        if(str_starts_with($label,'COM_') && Joomla\CMS\Language\Text::_($label)===$label) { throw new RuntimeException('Untranslated '.$label); }
    }
    // Menu overrides and blank/global inheritance resolve to author-specific defaults.
    $authorHelper='Joomla\\Component\\'.ucfirst($family).'\\Site\\Helper\\TagPostsHelper';
    $global->set('tag_posts_posts_per_page',7);
    $inherited=$authorHelper::settings(new Joomla\Registry\Registry(['tag_posts_posts_per_page'=>'']),$tagId);
    $overridden=$authorHelper::settings(new Joomla\Registry\Registry(['tag_posts_posts_per_page'=>3]),$tagId);
    if($inherited->get('posts_per_page')!==7 || $overridden->get('posts_per_page')!==3) { throw new RuntimeException('Menu inheritance'); }
    foreach (['next','latest','featured','random','related'] as $mode) {
        $set(['tag_posts_compact_selection'=>$mode,'tag_posts_num_links'=>3]);
        [$compactView,$html]=$render();
        foreach ($compactView->compactItems as $post) {
            if (!(int)$db->setQuery('SELECT COUNT(*) FROM #__'.$family.'_tag_map WHERE tag_id='.$tagId.' AND content_item_id='.(int)$post->id)->loadResult()) { throw new RuntimeException('Compact tag scope'); }
            if (in_array($post->id,array_column($compactView->posts,'id'))) { throw new RuntimeException('Compact duplicate'); }
        }
    }
    $excludedIds=array_map('intval',array_column($db->setQuery('SELECT id FROM #__'.$family.' WHERE state=1')->loadObjectList(),'id'));
    $set(['tags_exclude_posts'=>$excludedIds,'tag_posts_featured_slider_enabled'=>1]);
    [$excluded,$html]=$render();
    if ($excluded->posts || $excluded->compactItems || str_contains($html,'featured-showcase')) { throw new RuntimeException('Tag post exclusions'); }
    $set(['tags_exclude_posts'=>[]]);
    echo "$family: tag scope, page boundaries, 15 listing combinations, seven slider styles and exclusions passed.\n";
    } finally {
        if ($fixtureTag) {
            $db->setQuery('DELETE FROM #__'.$family.'_tag_map WHERE tag_id='.$fixtureTag)->execute();
            $db->setQuery('DELETE FROM #__'.$family.'_tags WHERE id='.$fixtureTag)->execute();
        }
    }

}



