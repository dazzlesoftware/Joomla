<?php
// Integration test: real Joomla routers and database fixtures; settings change in memory only.
if (PHP_SAPI !== 'cli') { exit(1); }
$argv = ['', 'C:/wamp64/www/Joomla'];
require __DIR__ . '/test-subcategory-styles.php';
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
$db = Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
$menu = $app->getMenu();
$originalActive = $menu->getActive();
$target = null;
foreach ($menu->getItems('component', 'com_academy') ?: [] as $item) {
    if (($item->query['view'] ?? '') === 'featured') { $target = $item; break; }
}
if (!$target) { throw new RuntimeException('Featured menu fixture required'); }
$originalQuery = $target->query;
$check = static function ($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
};
try {
    $menu->setActive($target->id);
    foreach (['academy', 'blog', 'codex'] as $family) {
        require_once __DIR__ . '/../' . $family . '/com_' . $family . '/site/src/Service/FeaturedPostRules.php';
        require_once __DIR__ . '/../' . $family . '/com_' . $family . '/site/src/Service/Router.php';
        $component = 'com_' . $family;
        foreach (['featured', 'archive', 'author', 'tags'] as $listingView) {
        $target->query = ['option' => $component, 'view' => $listingView];
        $params = ComponentHelper::getParams($component);
        $saved = $params->get('sef_ids');
        $ids = [];
        try {
            $base = $db->setQuery('SELECT * FROM #__' . $family . ' LIMIT 1')->loadObject();
            $alias = 'sef-fixture-' . bin2hex(random_bytes(8));
            foreach ([$alias, $alias . '-other', '133-' . $alias] as $value) {
                $post = clone $base;
                unset($post->id);
                $post->alias = $value;
                $post->title = 'Temporary SEF route test';
                $post->state = -2;
                $db->insertObject('#__' . $family, $post, 'id');
                $ids[] = (int) $post->id;
            }
            $routers = [];
            foreach ([0, 1] as $mode) {
                $params->set('sef_ids', $mode);
                $router = $app->bootComponent($component)->createRouter($app, $menu);
                $routers[$mode] = $router;
                $query = ['option' => $component, 'view' => 'post', 'id' => $ids[0] . ':' . $alias, 'catid' => $base->catid, 'Itemid' => $target->id];
                $query = $router->preprocess($query);
                $check((int) $query['Itemid'] === (int) $target->id, "$family: Featured menu retained");
                $segments = $router->build($query);
                $check($segments === ['post', $mode ? $alias : $ids[0] . '-' . $alias], "$family: setting controls route");
                $check(!isset($query['id']) && !isset($query['catid']) && !isset($query['view']), "$family: query cleaned");
                foreach ([$alias, $ids[0] . '-' . $alias] as $segment) {
                    $parts = ['post', $segment];
                    $vars = $router->parse($parts);
                    $check(($vars['id'] ?? 0) === $ids[0] && ($vars['catid'] ?? 0) === (int) $base->catid && !$parts, "$family: both URL forms round trip");
                }
            }
            $query = ['view' => 'post', 'id' => $ids[2], 'Itemid' => $target->id];
            $check($routers[1]->build($query) === ['post', $ids[2] . '-133-' . $alias], "$family: numeric-prefix alias keeps ID");
            $db->setQuery('UPDATE #__' . $family . ' SET alias=' . $db->quote($alias) . ' WHERE id=' . $ids[1])->execute();
            foreach ([$ids[0], $ids[1]] as $id) {
                $query = ['view' => 'post', 'id' => $id, 'Itemid' => $target->id];
                $parts = $routers[1]->build($query);
                $check($parts === ['post', $id . '-' . $alias], "$family: duplicate alias keeps ID");
                $vars = $routers[1]->parse($parts);
                $check(($vars['id'] ?? 0) === $id, "$family: duplicate resolves correct post");
            }
            // Test the rule directly: unresolved segments remain for Joomla to reject.
            $class = 'Joomla\\Component\\' . ucfirst($family) . '\\Site\\Service\\FeaturedPostRules';
            $rule = new $class($routers[1], $db, true);
            foreach ([$alias, 'missing-' . $alias, '999999999-' . $alias] as $segment) {
                $parts = ['post', $segment]; $vars = [];
                $rule->parse($parts, $vars);
                $check(!$vars && count($parts) === 2, "$family: unknown or ambiguous URL rejected");
            }
            echo "$family/$listingView: setting on/off, Featured context, URL compatibility, duplicates, numeric aliases and unknown paths passed.\n";
        } finally {
            $params->set('sef_ids', $saved);
            if ($ids) { $db->setQuery('DELETE FROM #__' . $family . ' WHERE id IN (' . implode(',', $ids) . ')')->execute(); }
        }
        }
    }
} finally {
    $target->query = $originalQuery;
    if ($originalActive) { $menu->setActive($originalActive->id); }
}
