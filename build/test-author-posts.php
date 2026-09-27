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
    $app->getInput()->set('view','author');
    $app->getInput()->set('layout','default');
    $authorId=(int)$db->setQuery('SELECT created_by FROM #__'.$family.' WHERE state=1 GROUP BY created_by ORDER BY COUNT(*) DESC')->loadResult();
    $app->getInput()->set('id',$authorId);
    $global=Joomla\CMS\Component\ComponentHelper::getParams($component);
    $set=function ($values) use ($global,$app,$component) {
        foreach ($values as $key=>$value) {
            $global->set($key,$value); $app->getParams($component)->set($key,$value);
            if ($app->getMenu()->getActive()) { $app->getMenu()->getActive()->getParams()->set($key,$value); }
        }
    };
    $set(['author_items_limit_source'=>'custom','author_posts_per_page'=>2,'author_show_pagination'=>1,'author_featured_slider_enabled'=>0,'author_listing_exclude_authors'=>[]]);
    $render=function ($start=0) use ($app,$factory,$component) {
        $app->getInput()->set('limitstart',$start);
        $model=$factory->createModel('Author','Site');
        $view=$factory->createView('Author','Site','html');
        $view->setModel($model,true);
        $view->addTemplatePath(JPATH_ROOT.'/components/'.$component.'/tmpl/author');
        $view->setDocument($app->getDocument());
        ob_start();$view->display();$html=ob_get_clean();
        return [$view,$html];
    };
    [$view,$html]=$render();
    if(count($view->posts)!==2 || $view->pagination->pagesTotal<2) { throw new RuntimeException('Author page size/total'); }
    $first=array_column($view->posts,'id');
    foreach($view->posts as $post) { if((int)$post->created_by!==$authorId) { throw new RuntimeException('Author scope'); } }
    [$second,$html]=$render(2);
    if(array_intersect($first,array_column($second->posts,'id'))) { throw new RuntimeException('Pagination duplicates'); }
    [$last]=$render(999999);
    if(!$last->posts) { throw new RuntimeException('Out of range page'); }
    foreach(['standard','card','learning','simple','nickel'] as $style) {
        foreach(['rows','grid','masonry'] as $layout) {
            $view->params->set('list_item_style',$style);
            $view->params->set('post_listing_layout',$layout==='rows'?'rows':'columns');
            $view->params->set('column_style',$layout);
            $view->params->set('columns_per_row',3);
            foreach($view->posts as $post) { $post->params->set('list_item_style',$style); }
            $html=$view->loadTemplate();
            if(!str_contains($html,'post-style-'.$style) || str_contains($html,'data-post-masonry')!==($layout==='masonry')) { throw new RuntimeException('Style '.$style.'/'.$layout); }
            if(!str_contains($html,'pagination')) { throw new RuntimeException('Pagination markup'); }
        }
    }
    $helper='Joomla\\Component\\'.ucfirst($family).'\\Site\\Helper\\FeaturedSliderHelper';
    $view->params->set('featured_slider_enabled',1);
    $view->params->set('featured_slider_all_pages',0);
    $slides=$helper::items($view->params);
    foreach($slides as $post) { if((int)$post->created_by!==$authorId) { throw new RuntimeException('Slider scope'); } }
    if(!$slides) { throw new RuntimeException('Featured fixture required'); }
    foreach(['card','default','hero','magazine','side-navigation','slick','thumbnail'] as $style) {
        $view->params->set('featured_slider_style',$style);
        $html=$view->loadTemplate();
        if(!str_contains($html,'featured-showcase') || str_contains($html,'&amp;amp;')) { throw new RuntimeException('Slider '.$style.' found='.(int)str_contains($html,'featured-showcase')); }
    }
    if($helper::render($view->params,0,2)!=='') { throw new RuntimeException('First-page slider gating'); }
    $set(['author_listing_exclude_authors'=>[$authorId],'author_featured_slider_enabled'=>1]);
    [$excluded,$html]=$render();
    if($excluded->posts || $excluded->pagination->total || str_contains($html,'featured-showcase')) { throw new RuntimeException('Excluded author posts='.count($excluded->posts).' total='.$excluded->pagination->total.' slider='.(int)str_contains($html,'featured-showcase').' exclusions='.json_encode($excluded->params->get('listing_exclude_authors'))); }
    $set(['author_listing_exclude_authors'=>[]]);
    // Both configuration surfaces must expose every author-specific control.
    $source=__DIR__.'/../'.$family.'/com_'.$family;
    $globalXml=simplexml_load_file($source.'/admin/forms/settings.xml');
    $menuXml=simplexml_load_file($source.'/site/tmpl/author/default.xml');
    $fields=$globalXml->xpath('//fieldset[@name="author_posts" or @name="author_slider"]/field');
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
    $authorHelper='Joomla\\Component\\'.ucfirst($family).'\\Site\\Helper\\AuthorListingHelper';
    $global->set('author_posts_per_page',7);
    $inherited=$authorHelper::settings(new Joomla\Registry\Registry(['author_posts_per_page'=>'']),$authorId);
    $overridden=$authorHelper::settings(new Joomla\Registry\Registry(['author_posts_per_page'=>3]),$authorId);
    if($inherited->get('posts_per_page')!==7 || $overridden->get('posts_per_page')!==3) { throw new RuntimeException('Menu inheritance'); }
    echo "$family: author scope, page boundaries, 15 listing combinations, seven slider styles and exclusions passed.\n";
}



