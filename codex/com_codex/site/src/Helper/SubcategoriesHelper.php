<?php
namespace Joomla\Component\Codex\Site\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

final class SubcategoriesHelper
{
    public static function load(int $parentId, Registry $params, bool $allowRoot = false): array
    {
        $depth = (int) $params->get('maxLevel', 1);
        if (($parentId <= 0 && !$allowRoot) || $depth === 0) { return []; }
        $app = Factory::getApplication();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $access = $app->getIdentity()->getAuthorisedViewLevels();
        $posts = $db->createQuery()->select('p.catid, COUNT(*) AS numitems')->from('#__codex AS p')
            ->where('p.state=1')->whereIn('p.access', $access)
            ->where('(p.publish_up IS NULL OR p.publish_up<=UTC_TIMESTAMP())')
            ->where('(p.publish_down IS NULL OR p.publish_down>=UTC_TIMESTAMP())');
        $query = $db->createQuery()->select('c.*, COALESCE(counts.numitems, 0) AS numitems')->from('#__codex_categories AS c')
            ->where('c.published=1')->whereIn('c.access', $access)->order('c.lft, c.title, c.id');
        if ($app->getLanguageFilter()) {
            $languages = $db->quote('*') . ',' . $db->quote($app->getLanguage()->getTag());
            $posts->where('p.language IN (' . $languages . ')');
            $query->where('c.language IN (' . $languages . ')');
        }
        $posts->group('p.catid');
        $query->join('LEFT', '(' . $posts . ') AS counts ON counts.catid=c.id');
        return self::tree($db->setQuery($query)->loadObjectList() ?: [], $parentId, $depth, (bool) $params->get('show_empty_categories', 0));
    }

    /** Build only reachable, visible branches; protect malformed parent cycles. */
    public static function tree(array $rows, int $parentId, int $depth, bool $showEmpty): array
    {
        if ($depth === 0) { return []; }
        $byParent = [];
        foreach ($rows as $row) { $byParent[(int) $row->parent_id][] = $row; }
        $walk = static function (int $parent, array $seen) use (&$walk, $byParent, $showEmpty): array {
            $nodes = [];
            foreach ($byParent[$parent] ?? [] as $row) {
                $id = (int) $row->id;
                if (isset($seen[$id])) { continue; }
                $children = $walk($id, $seen + [$id => true]);
                if (!$showEmpty && !(int) $row->numitems && !$children) { continue; }
                $node = clone $row;
                $node->children = $children;
                $nodes[] = $node;
            }
            return $nodes;
        };
        $tree = $walk($parentId, [$parentId => true]);
        $limit = static function (array $nodes, int $remaining) use (&$limit): array {
            foreach ($nodes as $node) { $node->children = $remaining === 1 ? [] : $limit($node->children, $remaining - 1); }
            return $nodes;
        };
        return $depth < 0 ? $tree : $limit($tree, $depth);
    }
}
