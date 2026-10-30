<?php
// Read-only ownership and menu binding checks against the real frontend MVC classes.
if (PHP_SAPI !== 'cli') { exit(1); }
$argv = ['', 'C:/wamp64/www/Joomla'];
require __DIR__ . '/test-subcategory-styles.php';
define('JPATH_COMPONENT', JPATH_ROOT . '/components/com_academy');
$db = Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
$originalIdentity = $app->getIdentity();
try {
    foreach (['academy', 'blog', 'codex'] as $family) {
        $component = 'com_' . $family;
        $input = $app->getInput();
        $input->set('option', $component);
        $factory = $app->bootComponent($component)->getMVCFactory();
        $owner = (int) $db->setQuery('SELECT created_by FROM #__' . $family . ' WHERE created_by > 0 LIMIT 1')->loadResult();
        $user = Joomla\CMS\Factory::getContainer()->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById($owner);
        if (!$user->id) { throw new RuntimeException('Missing user fixture: ' . $family); }
        $app->loadIdentity($user);
        $input->set('view', 'myposts');
        // Form and My Posts consume Joomla's cached active-menu registry.
        $params = $app->getParams();
        $old = clone $params;
        try {
            $params->set('show_postnav', 0);
            $view = $factory->createView('MyPosts', 'Site', 'html');
            $view->setDocument($app->getDocument());
            $view->addTemplatePath(JPATH_ROOT . '/components/' . $component . '/tmpl/myposts');
            ob_start();
            try { $view->display(); } finally { $html = ob_get_clean(); }
            $expected = $db->setQuery('SELECT id FROM #__' . $family . ' WHERE created_by=' . $owner . ' AND state != -2 ORDER BY modified DESC, created DESC')->loadColumn();
            if (array_map('intval', array_column($view->items, 'id')) !== array_map('intval', $expected)) {
                throw new RuntimeException('My Posts ownership/state filter: ' . $family);
            }
            if (str_contains($html, '<nav class="postnav ')) { throw new RuntimeException('My Posts Hide navigation: ' . $family); }
            $params->set('show_postnav', 1);
            ob_start();
            try { $view->display(); } finally { $html = ob_get_clean(); }
            if (!str_contains($html, '<nav class="postnav ')) { throw new RuntimeException('My Posts Show navigation: ' . $family); }
            $input->set('view', 'form');
            $input->set('a_id', 0);
            // A missing query key uses the menu default; an explicit zero overrides it.
            $inputData = (new ReflectionProperty($input, 'data'))->getValue($input);
            unset($inputData['catid']);
            (new ReflectionProperty($input, 'data'))->setValue($input, $inputData);
            foreach ([0, 1] as $enabled) {
                $params->set('enable_category', $enabled);
                $params->set('catid', 123456);
                $model = $factory->createModel('Form', 'Site');
                if ((int) $model->getState('post.catid') !== ($enabled ? 123456 : 0)) {
                    throw new RuntimeException('Form category binding: ' . $family . ' enabled=' . $enabled . ' actual=' . var_export($model->getState('post.catid'), true) . ' app=' . $app->getParams()->get('enable_category', 'missing'));
                }
            }
            echo "$family: My Posts ownership, non-trashed states, hidden navigation and Form category binding passed.\n";
        } finally {
            // Restore only the in-memory registry; this test never saves configuration.
            foreach ($params->toArray() as $key => $value) { $params->set($key, $old->get($key)); }
        }
    }
} finally { $app->loadIdentity($originalIdentity); }
