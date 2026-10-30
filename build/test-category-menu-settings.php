<?php
// Real Category views, existing content only; no saved settings or content changes.
if (PHP_SAPI !== 'cli') { exit(1); }
$argv = ['', 'C:/wamp64/www/Joomla'];
require __DIR__ . '/test-subcategory-styles.php';
define('JPATH_COMPONENT', JPATH_ROOT . '/components/com_academy');
$db = Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
$params = $app->getParams();
$original = clone $params;
try {
    foreach (['academy', 'blog', 'codex'] as $family) {
        $component = 'com_' . $family;
        $base = $db->setQuery('SELECT p.catid FROM #__' . $family . ' p INNER JOIN #__' . $family . '_categories c ON c.id=p.catid WHERE p.state=1 AND p.access=1 AND c.access=1 AND c.published=1 LIMIT 1')->loadObject();
        if (!$base) { throw new RuntimeException('Missing category fixture: ' . $family); }
        $factory = $app->bootComponent($component)->getMVCFactory();
        $input = $app->getInput();
        foreach (['option' => $component, 'view' => 'category', 'layout' => 'category_posts', 'id' => $base->catid, 'limitstart' => 0] as $key => $value) { $input->set($key, $value); }
        foreach (['listing_authors', 'listing_exclude_authors', 'listing_categories', 'listing_exclude_categories', 'listing_tags', 'listing_exclude_posts'] as $key) { $params->set($key, []); }
        foreach (['category_layout' => 'card', 'items_limit_source' => 'custom', 'posts_per_page' => 10, 'show_readmore' => 0, 'compact_show' => 0, 'featured_slider_enabled' => 0, 'show_postnav' => 0] as $key => $value) { $params->set($key, $value); }
        foreach ([0, 1] as $show) {
            foreach (['show_title', 'show_intro', 'show_hits'] as $key) { $params->set($key, $show); }
            $view = $factory->createView('Category', 'Site', 'html');
            $view->setDocument($app->getDocument());
            $view->addTemplatePath(JPATH_ROOT . '/components/' . $component . '/tmpl/category');
            ob_start();
            try { $view->display(); } finally { ob_end_clean(); }
            if (!$view->items) { throw new RuntimeException('Empty category fixture: ' . $family); }
            foreach ($view->items as $item) {
                foreach (['show_title', 'show_intro', 'show_hits'] as $key) {
                    if ((int) $item->params->get($key) !== $show) { throw new RuntimeException('Category menu precedence: ' . $family . ' ' . $key); }
                }
            }
            $view->item = $view->items[0];
            $view->item->summary = '<p>CATEGORY_MENU_INTRO_CHECK</p>';
            $html = $view->loadTemplate('item');
            if (str_contains($html, 'CATEGORY_MENU_INTRO_CHECK') !== (bool) $show) { throw new RuntimeException('Category intro visibility: ' . $family); }
        }
        echo "$family: Category explicit Hide/Show overrides per-post settings and controls rendered intro text.\n";
    }
} finally {
    foreach ($params->toArray() as $key => $value) { $params->set($key, $original->get($key)); }
}
