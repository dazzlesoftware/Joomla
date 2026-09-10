<?php
namespace Joomla\Component\Codex\Administrator\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\Database\DatabaseInterface;

final class TagsHelper
{
    public $tags = '';
    public array $itemTags = [];
    public string $typeAlias = 'com_codex.post';
    public static function db(): DatabaseInterface { return Factory::getContainer()->get(DatabaseInterface::class); }
    public function getTagIds($id, $typeAlias = 'com_codex.post'): string
    {
        $db = self::db();
        $this->tags = implode(',', $db->setQuery($db->createQuery()->select('tag_id')->from('#__codex_tag_map')->where('content_item_id=' . (int) $id)->where('type_alias=' . $db->quote($typeAlias))->order('tag_id'))->loadColumn());
        return $this->tags;
    }
    public function getItemTags($typeAlias, $id, $getTagData = true, $params = null): array
    {
        $all = $this->getMultipleItemTags($typeAlias, [(int) $id]);
        return $this->itemTags = $all[(int) $id] ?? [];
    }
    public function getMultipleItemTags($typeAlias, $ids, $getTagData = true, $params = null): array
    {
        $ids = array_values(array_filter(array_map('intval', (array) $ids)));
        if (!$ids) return [];
        $db = self::db();
        $q = $db->createQuery()->select('t.*, t.id AS tag_id, m.content_item_id')->from('#__codex_tags AS t')->join('INNER', '#__codex_tag_map AS m ON m.tag_id=t.id')->where('m.type_alias=' . $db->quote($typeAlias))->whereIn('m.content_item_id', $ids)->where('t.published=1')->order('t.title');
        if (Factory::getApplication()->isClient('site')) {
            $q->whereIn('t.access', Factory::getApplication()->getIdentity()->getAuthorisedViewLevels());
            $q->where('t.language IN (' . $db->quote('*') . ',' . $db->quote(Factory::getApplication()->getLanguage()->getTag()) . ')');
        }
        $result = [];
        foreach ($db->setQuery($q)->loadObjectList() as $tag) $result[(int) $tag->content_item_id][] = $tag;
        return $result;
    }
    public function getTags($ids): array
    {
        $ids = array_values(array_filter(array_map('intval', is_array($ids) ? $ids : explode(',', (string) $ids))));
        return $ids ? self::db()->setQuery(self::db()->createQuery()->select('*')->from('#__codex_tags')->whereIn('id', $ids))->loadObjectList() : [];
    }
    public static function resolve(array $values, bool $create = false): array
    {
        $db = self::db(); $ids = [];
        foreach ($values as $value) {
            if (!is_scalar($value)) continue;
            $value = trim((string) $value);
            if ($value === '') continue;
            if (ctype_digit($value)) {
                $id = (int) $db->setQuery('SELECT id FROM #__codex_tags WHERE id=' . (int) $value)->loadResult();
                if (!$id) throw new \InvalidArgumentException('Unknown tag ID: ' . $value);
            } else {
                if (!$create) throw new \InvalidArgumentException('Choose an existing tag.');
                $title = mb_substr(trim(strip_tags(preg_replace('/^#new#/', '', $value))), 0, 255);
                if ($title === '') continue;
                $id = (int) $db->setQuery('SELECT id FROM #__codex_tags WHERE title=' . $db->quote($title))->loadResult();
                if (!$id) {
                    $alias = ApplicationHelper::stringURLSafe($title) ?: bin2hex(random_bytes(6));
                    $base = mb_substr($alias, 0, 170); $alias = $base; $n = 2;
                    while ($db->setQuery('SELECT id FROM #__codex_tags WHERE alias=' . $db->quote($alias))->loadResult()) $alias = $base . '-' . $n++;
                    $row = (object) ['title' => $title, 'alias' => $alias, 'published' => 1, 'access' => 1, 'language' => '*', 'description' => '', 'params' => '{}'];
                    $db->insertObject('#__codex_tags', $row, 'id'); $id = (int) $row->id;
                }
            }
            $ids[] = $id;
        }
        return array_values(array_unique($ids));
    }
    public static function assign(int $postId, array $ids, string $type = 'com_codex.post'): void
    {
        $db = self::db();
        $ids = self::resolve($ids);
        $db->transactionStart(true);
        try {
            $db->setQuery('DELETE FROM #__codex_tag_map WHERE content_item_id=' . $postId . ' AND type_alias=' . $db->quote($type))->execute();
            foreach ($ids as $id) {
                $row = (object) ['content_item_id' => $postId, 'tag_id' => $id, 'type_alias' => $type];
                $db->insertObject('#__codex_tag_map', $row);
            }
            $db->transactionCommit(true);
        } catch (\Throwable $e) { $db->transactionRollback(true); throw $e; }
    }
}
