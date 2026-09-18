<?php
namespace Joomla\Component\Academy\Site\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class CompactPostsHelper
{
    public static function visible($params): bool
    {
        return (bool) $params->get('compact_show', 1) && (
            $params->get('compact_show_title', 1) || $params->get('compact_show_image', 1) || $params->get('compact_show_rating', 1)
        );
    }

    public static function select(array $fallback, array $displayed, $params): array
    {
        if (!self::visible($params)) { return []; }
        $mode = $params->get('compact_selection', 'next');
        $limit = max(0, (int) $params->get('num_links', 4));
        if (!$limit) { return []; }
        if (!in_array($mode, ['featured', 'latest', 'random', 'related'], true)) {
            return array_slice($fallback, 0, $limit);
        }
        $app = Factory::getApplication();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $levels = $app->getIdentity()->getAuthorisedViewLevels();
        $q = $db->createQuery()->select('p.*, c.default_image AS category_default_image')
            ->from('#__academy AS p')->join('INNER', '#__academy_categories AS c ON c.id=p.catid')
            ->where('p.state=1 AND c.published=1')->whereIn('p.access', $levels)->whereIn('c.access', $levels)
            ->where('(p.publish_up IS NULL OR p.publish_up<=UTC_TIMESTAMP())')
            ->where('(p.publish_down IS NULL OR p.publish_down>=UTC_TIMESTAMP())');
        $ids = array_map(static fn($item) => (int) $item->id, $displayed);
        if ($ids) { $q->whereNotIn('p.id', $ids); }
        if ($app->getLanguageFilter()) {
            foreach (['p.language', 'c.language'] as $field) {
                $q->where($field . ' IN (' . $db->quote('*') . ',' . $db->quote($app->getLanguage()->getTag()) . ')');
            }
        }
        if ($mode === 'featured') { $q->where('p.featured=1'); }
        if ($mode === 'related') {
            $categories = array_values(array_unique(array_map(static fn($item) => (int) $item->catid, $displayed)));
            if (!$categories) { return []; }
            $q->whereIn('p.catid', $categories);
        }
        $q->order($mode === 'random' ? 'RAND()' : 'COALESCE(p.publish_up,p.created) DESC, p.id DESC');
        return $db->setQuery($q, 0, $limit)->loadObjectList() ?: [];
    }
}
