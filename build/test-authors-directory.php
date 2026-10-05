<?php
if (PHP_SAPI !== 'cli') { exit(1); }
$argv=['','C:/wamp64/www/Joomla'];
require __DIR__.'/test-subcategory-styles.php';
define('JPATH_COMPONENT', JPATH_ROOT.'/components/com_academy');
foreach (['academy','blog','codex'] as $family) {
    $app->getInput()->set('option','com_'.$family);
    $app->getInput()->set('view','authors');
    $app->getInput()->set('limitstart',999999);
    Joomla\CMS\Component\ComponentHelper::getParams('com_'.$family)->set('authors_per_page',1);
    if ($app->getMenu()->getActive()) { $app->getMenu()->getActive()->getParams()->set('authors_per_page',1); }
    $app->getParams('com_'.$family)->set('authors_per_page',1);
    $view=$app->bootComponent('com_'.$family)->getMVCFactory()->createView('Authors','Site','html');
    $view->addTemplatePath(JPATH_ROOT.'/components/com_'.$family.'/tmpl/authors');
    $view->setDocument($app->getDocument());
    ob_start();$view->display();$html=ob_get_clean();
    if (count($view->items)>1 || !$view->pagination) { throw new RuntimeException('Page size'); }
    if ($view->pagination->total && !$view->items) { throw new RuntimeException('Out of range page'); }
    // Verify filters affect both directory rows and counts on the installed view.
    $page=$app->getParams('com_'.$family);
    $saved=$page->toArray();
    $db=Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
    $author=(int)$db->setQuery('SELECT created_by FROM #__'.$family.' WHERE state=1 LIMIT 1')->loadResult();
    foreach([
        ['listing_authors'=>[$author]],
        ['listing_exclude_authors'=>[$author]],
        ['listing_categories'=>[2147483647]],
        ['listing_tags'=>[2147483647]],
    ] as $filter) {
        foreach(['listing_authors','listing_exclude_authors','listing_categories','listing_exclude_categories','listing_tags'] as $key) $page->set($key,[]);
        foreach($filter as $key=>$value) $page->set($key,$value);
        ob_start();$view->display();ob_end_clean();
        foreach($view->items as $item) {
            if(isset($filter['listing_authors']) && (int)$item->id!==$author) throw new RuntimeException('Included author');
            if(isset($filter['listing_exclude_authors']) && (int)$item->id===$author) throw new RuntimeException('Excluded author');
        }
        if((isset($filter['listing_categories']) || isset($filter['listing_tags'])) && ($view->items || $view->pagination->total)) throw new RuntimeException('Filtered directory counts');
    }
    foreach(['listing_authors','listing_exclude_authors','listing_categories','listing_exclude_categories','listing_tags'] as $key) $page->set($key,$saved[$key]??[]);
    ob_start();$view->display();ob_end_clean();
    $page->set('compact_show',1);
    $page->set('num_links',2);
    foreach(['next','latest','featured','random','related'] as $selection) {
        $page->set('compact_selection',$selection);
        ob_start();$view->display();$compactHtml=ob_get_clean();
        if(count($view->compactItems)>2) throw new RuntimeException('Compact count');
        foreach($view->compactItems as $post) if(!in_array((int)$post->created_by,array_map('intval',array_column($view->items,'id')),true)) throw new RuntimeException('Directory compact author scope');
        if($view->compactItems && !str_contains($compactHtml,'class="compact-posts ')) throw new RuntimeException('Directory compact markup');
    }
    $page->set('compact_show',0);
    ob_start();$view->display();$compactHtml=ob_get_clean();
    if($view->compactItems || str_contains($compactHtml,'class="compact-posts ')) throw new RuntimeException('Directory compact Hide');
    $view->params->set('featured_slider_enabled',1);
    $view->params->set('featured_slider_all_pages',0);
    $view->pagination=new Joomla\CMS\Pagination\Pagination(20,0,1);
    $html=$view->loadTemplate();
    if(!str_contains($html,'featured-showcase') && !str_contains($html,'featured-slider')) throw new RuntimeException('Authors slider missing');
    $view->pagination=new Joomla\CMS\Pagination\Pagination(20,1,1);
    $html=$view->loadTemplate();
    if(str_contains($html,'featured-showcase')) throw new RuntimeException('Authors first-page gating');
    $view->params->set('featured_slider_all_pages',1);
    $html=$view->loadTemplate();
    if(!str_contains($html,'featured-showcase') && !str_contains($html,'featured-slider')) throw new RuntimeException('Authors all-pages slider');
    $view->params->set('featured_slider_enabled',0);
    $html=$view->loadTemplate();
    if(str_contains($html,'featured-showcase')) throw new RuntimeException('Authors slider Hide');
    foreach (['link_list','image_grid'] as $style) {
        foreach (['rows','columns'] as $layout) {
            foreach (['grid','masonry'] as $columnStyle) {
                $view->params->set('authors_style',$style);
                $view->params->set('authors_listing_layout',$layout);
                $view->params->set('authors_column_style',$columnStyle);
                $view->params->set('authors_columns',4);
                $view->pagination=new Joomla\CMS\Pagination\Pagination(13,0,1);
                $html=$view->loadTemplate();
                if (!str_contains($html,'row-cols-md-'.($layout==='rows'?1:4))) { throw new RuntimeException('Columns'); }
                if (str_contains($html,'data-post-masonry')!==($layout==='columns' && $columnStyle==='masonry')) { throw new RuntimeException('Masonry'); }
                if (!str_contains($html,'pagination')) { throw new RuntimeException('Pagination links'); }
            }
        }
    }
    $db=Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
    foreach (['genesis_test_01'=>true, 'genesis_test_no_image'=>false] as $username=>$hasImage) {
        $author=$db->setQuery('SELECT id,name,email FROM #__users WHERE username='.$db->quote($username))->loadObject();
        if (!$author) { continue; }
        $author->post_count=1;
        $view->items=[$author];
        $view->params->set('authors_style','image_grid');
        $html=$view->loadTemplate();
        if (str_contains($html, '&amp;amp;')) { throw new RuntimeException('Double-escaped author URL'); }
        if (str_contains($html,'author-directory-avatar-placeholder d-none')!==$hasImage) { throw new RuntimeException('Fallback visibility'); }
        if (!preg_match('~<a href="[^"]+">\s*<span class="d-block mb-3">.*?author-directory-avatar.*?</span>\s*'.preg_quote($author->name,'~').'\s*</a>~s',$html)) { throw new RuntimeException('Image and name must share a link'); }
    }
    echo "$family: eight layout combinations, pagination links, database limit and offset passed.\n";
}
