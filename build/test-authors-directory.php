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
