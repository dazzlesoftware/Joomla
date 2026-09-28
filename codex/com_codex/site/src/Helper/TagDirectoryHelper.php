<?php
namespace Joomla\Component\Codex\Site\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

final class TagDirectoryHelper
{
    public static function params(): Registry
    {
        $params = clone ComponentHelper::getParams('com_codex');
        foreach (Factory::getApplication()->getParams('com_codex')->toArray() as $key => $value) {
            if ($value !== '' && $value !== null) { $params->set($key, $value); }
        }
        $menu = Factory::getApplication()->getMenu()->getActive();
        if (!$menu || !$menu->getParams()->get('tags_exclusions_override', 0)) {
            foreach (['tags_exclude_tags', 'tags_exclude_categories', 'tags_exclude_posts'] as $key) {
                $params->set($key, ComponentHelper::getParams('com_codex')->get($key, []));
            }
        }
        return $params;
    }

    private static function excluded(string $name): array
    {
        return array_values(array_filter(array_map('intval', (array) self::params()->get($name, [])), static fn ($id) => $id > 0));
    }

    public static function posts(int $tagId = 0)
    {
        $app = Factory::getApplication();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $levels = array_map('intval', $app->getIdentity()->getAuthorisedViewLevels());
        $q = $db->getQuery(true)->select('p.*')->from('#__codex AS p')
            ->join('INNER', '#__codex_categories AS c ON c.id=p.catid')
            ->where('p.state=1')->where('c.published=1')->where('p.access IN ('.implode(',', $levels).')')->where('c.access IN ('.implode(',', $levels).')')
            ->where('(p.publish_up IS NULL OR p.publish_up <= UTC_TIMESTAMP())')
            ->where('(p.publish_down IS NULL OR p.publish_down >= UTC_TIMESTAMP())');
        if ($app->getLanguageFilter()) {
            foreach (['p','c'] as $alias) { $q->where($alias.'.language IN ('.$db->quote('*').','.$db->quote($app->getLanguage()->getTag()).')'); }
        }
        foreach (['tags_exclude_categories' => 'p.catid', 'tags_exclude_posts' => 'p.id'] as $key => $column) {
            $ids = self::excluded($key);
            if ($ids) { $q->where($column.' NOT IN ('.implode(',', $ids).')'); }
        }
        if ($tagId) {
            $q->where('EXISTS (SELECT 1 FROM #__codex_tag_map m WHERE m.content_item_id=p.id AND m.type_alias='.$db->quote('com_codex.post').' AND m.tag_id='.$tagId.')');
        }
        $ids = self::excluded('tags_exclude_tags');
        if ($ids) { $q->where('t.id NOT IN ('.implode(',', $ids).')'); }
        return $q;
    }

    public static function tags()
    {
        $app = Factory::getApplication(); $db = Factory::getContainer()->get(DatabaseInterface::class);
        $q = $db->getQuery(true)->select('t.*')->from('#__codex_tags AS t')->where('t.published=1')
            ->whereIn('t.access', $app->getIdentity()->getAuthorisedViewLevels());
        if ($app->getLanguageFilter()) { $q->where('t.language IN ('.$db->quote('*').','.$db->quote($app->getLanguage()->getTag()).')'); }
        $ids = self::excluded('tags_exclude_tags');
        if ($ids) { $q->where('t.id NOT IN ('.implode(',', $ids).')'); }
        return $q;
    }

    public static function tag(int $id): object
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $tag = $db->setQuery(self::tags()->where('t.id='.$id))->loadObject();
        if (!$tag) { throw new \RuntimeException(\Joomla\CMS\Language\Text::_('JGLOBAL_RESOURCE_NOT_FOUND'), 404); }
        return $tag;
    }
}
