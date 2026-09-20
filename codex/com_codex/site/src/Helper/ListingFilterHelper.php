<?php
namespace Joomla\Component\Codex\Site\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/** Shared listing restrictions; all identifiers come from trusted callers, all IDs are integers. */
final class ListingFilterHelper
{
    public static function ids($value): array
    {
        if (is_string($value)) { $value = explode(',', $value); }
        return array_values(array_unique(array_filter(array_map('intval', (array) $value), static fn($id) => $id > 0)));
    }

    private static function categories(array $ids, bool $children, $db): array
    {
        if (!$ids || !$children) { return $ids; }
        $parents = $db->setQuery($db->createQuery()->select('lft,rgt')->from('#__codex_categories')->whereIn('id', $ids))->loadObjectList();
        $ranges = [];
        foreach ($parents as $parent) { $ranges[] = '(lft >= ' . (int) $parent->lft . ' AND rgt <= ' . (int) $parent->rgt . ')'; }
        if (!$ranges) { return $ids; }
        $query = $db->createQuery()->select('id')->from('#__codex_categories')->where('(' . implode(' OR ', $ranges) . ')');
        return array_values(array_unique(array_merge($ids, array_map('intval', $db->setQuery($query)->loadColumn()))));
    }

    public static function apply($query, $params, string $alias = 'p', int $categoryId = 0, ?DatabaseInterface $db = null): void
    {
        $db ??= Factory::getContainer()->get(DatabaseInterface::class);
        $children = (bool) $params->get('listing_subcategories', (int) $params->get('show_subcategory_content', 0) !== 0);
        $include = $categoryId > 0 ? [$categoryId] : self::ids($params->get('listing_categories', []));
        $exclude = self::ids($params->get('listing_exclude_categories', []));
        $include = self::categories($include, $children, $db);
        $exclude = self::categories($exclude, $children, $db);
        if ($include) { $query->whereIn($alias . '.catid', $include); }
        if ($exclude) { $query->whereNotIn($alias . '.catid', $exclude); }
        foreach (['listing_authors' => true, 'listing_exclude_authors' => false] as $key => $included) {
            $ids = self::ids($params->get($key, []));
            if ($ids) {
                $included ? $query->whereIn($alias . '.created_by', $ids) : $query->whereNotIn($alias . '.created_by', $ids);
            }
        }
        $tags = self::ids($params->get('listing_tags', []));
        if ($tags) {
            $sub = $db->createQuery()->select('tm.content_item_id')->from('#__codex_tag_map AS tm')
                ->join('INNER', '#__codex_tags AS t ON t.id=tm.tag_id AND t.published=1')
                ->where('tm.type_alias=' . $db->quote('com_codex.post'))->where('tm.tag_id IN (' . implode(',', $tags) . ')');
            $query->where($alias . '.id IN (' . $sub . ')');
        }
        if (!(bool) $params->get('listing_include_featured', $params->get('show_featured', 'show') !== 'hide')) { $query->where($alias . '.featured=0'); }
        if ((bool) $params->get('listing_pin_featured', 0)) { $query->order($alias . '.featured DESC'); }
    }

    public static function canonical($document, $params): void
    {
        $url = trim((string) $params->get('listing_canonical', ''));
        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            $document->addHeadLink($url, 'canonical');
        }
    }
}
