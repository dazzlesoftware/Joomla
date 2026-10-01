<?php
namespace Joomla\Component\Codex\Site\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class ArchiveCompactHelper
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
        $model = $app->bootComponent('com_codex')->getMVCFactory()->createModel('Archive', 'Site');
        $model->getState();
        $model->setState('params', clone $params);
        $model->setState('filter.month', (int) $params->get('archive_month'));
        $model->setState('filter.year', (int) $params->get('archive_year'));
        $model->setState('list.start', 0);
        $model->setState('list.limit', min(100, $limit));
        if ($displayed) {
            $model->setState('filter.post_id', array_map(static fn($item) => (int) $item->id, $displayed));
            $model->setState('filter.post_id.include', false);
        }
        if ($mode === 'featured') { $model->setState('filter.featured', 'only'); }
        if ($mode === 'related') {
            $categories = array_values(array_unique(array_map(static fn($item) => (int) $item->catid, $displayed)));
            if (!$categories) { return []; }
            $model->setState('filter.category_id', $categories);
        }
        $model->setState('list.ordering', $mode === 'random' ? 'RAND()' : 'COALESCE(a.publish_up,a.created) DESC, a.id DESC');
        $items = $model->getItems();
        if ($errors = $model->getErrors()) { throw new \RuntimeException(implode('; ', $errors)); }
        return $items ?: [];
    }
}
