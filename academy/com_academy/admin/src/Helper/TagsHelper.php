<?php
namespace Joomla\Component\Academy\Administrator\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\Database\DatabaseInterface;

final class TagsHelper
{
    public $tags = '';
    public array $itemTags = [];
    public string $typeAlias = 'com_academy.post';
    public static function db(): DatabaseInterface { return Factory::getContainer()->get(DatabaseInterface::class); }

    /**
     * The site-wide default tag (if any), as a one-element array of string
     * ids ready to merge into a post's tag list - matching the shape
     * CategoriesHelper::defaultTagIds() returns for a category's own default
     * tags. An unpublished tag is never auto-applied even if flagged default.
     */
    public static function defaultTagIds(): array
    {
        $id = (int) self::db()->setQuery('SELECT id FROM #__academy_tags WHERE is_default=1 AND published=1')->loadResult();
        return $id ? [(string) $id] : [];
    }

    /**
     * Creates or updates a tag. Only one tag can be the default at a time -
     * setting this one as default clears the flag from every other tag,
     * mirroring how a single "default" item is enforced elsewhere in Joomla
     * (default access level, default language, etc.).
     */
    public static function save(array $data): int
    {
        $db = self::db();
        $id = (int) ($data['id'] ?? 0);
        $title = mb_substr(trim(strip_tags((string) ($data['title'] ?? ''))), 0, 255);
        if ($title === '') {
            throw new \InvalidArgumentException('A tag title is required.');
        }
        $alias = mb_substr(ApplicationHelper::stringURLSafe((string) ($data['alias'] ?? '') ?: $title), 0, 191) ?: bin2hex(random_bytes(6));
        $base = mb_substr($alias, 0, 170);
        $alias = $base;
        $n = 2;
        while ($db->setQuery('SELECT id FROM #__academy_tags WHERE alias=' . $db->quote($alias) . ($id ? ' AND id<>' . $id : ''))->loadResult()) {
            $alias = $base . '-' . $n++;
        }

        $old = $id ? $db->setQuery('SELECT * FROM #__academy_tags WHERE id=' . $id)->loadObject() : null;
        if ($id && !$old) {
            throw new \RuntimeException('Tag not found.', 404);
        }

        $identity = Factory::getApplication()->getIdentity();
        $isDefault = (int) ($data['is_default'] ?? 0);

        $row = (object) [
            'id' => $id ?: null,
            'title' => $title,
            'alias' => $alias,
            'description' => (string) ($data['description'] ?? ''),
            'published' => (int) ($data['published'] ?? 1),
            // 'access' is not exposed on the tag edit screen, so keep whatever
            // the tag already had rather than silently resetting it to Public.
            'access' => (int) ($data['access'] ?? $old->access ?? 1),
            'language' => (string) ($data['language'] ?? '*'),
            'is_default' => $isDefault,
            'created_by' => $old->created_by ?? (int) ($identity?->id ?? 0),
            'params' => $old->params ?? '{}',
        ];

        if ($id) {
            $db->updateObject('#__academy_tags', $row, 'id');
        } else {
            $db->insertObject('#__academy_tags', $row, 'id');
        }

        if ($isDefault) {
            $db->setQuery('UPDATE #__academy_tags SET is_default=0 WHERE id<>' . (int) $row->id)->execute();
        }

        return (int) $row->id;
    }

    /** Batch publish/unpublish. Returns the number of rows affected. */
    public static function publish(array $ids, int $state): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return 0;
        }
        $db = self::db();
        $db->setQuery(
            $db->createQuery()->update('#__academy_tags')->set('published=' . (int) $state)->whereIn('id', $ids)
        )->execute();
        return count($ids);
    }

    /** Sets exactly one tag as the default, clearing the flag from every other tag. */
    public static function makeDefault(int $id): bool
    {
        if (!$id) {
            return false;
        }
        $db = self::db();
        $db->transactionStart();
        try {
            $db->setQuery('UPDATE #__academy_tags SET is_default=0')->execute();
            $db->setQuery('UPDATE #__academy_tags SET is_default=1 WHERE id=' . $id)->execute();
            $db->transactionCommit();
        } catch (\Throwable $e) {
            $db->transactionRollback();
            throw $e;
        }
        return true;
    }

    /** Clears the default flag from the given tags. Returns the number of rows affected. */
    public static function removeDefault(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return 0;
        }
        $db = self::db();
        $db->setQuery(
            $db->createQuery()->update('#__academy_tags')->set('is_default=0')->whereIn('id', $ids)
        )->execute();
        return count($ids);
    }

    /** Batch delete. Removes the tags and their post assignments. Returns the number of tags deleted. */
    public static function delete(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return 0;
        }
        $db = self::db();
        $db->transactionStart();
        try {
            $db->setQuery($db->createQuery()->delete('#__academy_tag_map')->whereIn('tag_id', $ids))->execute();
            $db->setQuery($db->createQuery()->delete('#__academy_tags')->whereIn('id', $ids))->execute();
            $db->transactionCommit();
        } catch (\Throwable $e) {
            $db->transactionRollback();
            throw $e;
        }
        return count($ids);
    }
    public function getTagIds($id, $typeAlias = 'com_academy.post'): string
    {
        $db = self::db();
        $this->tags = implode(',', $db->setQuery($db->createQuery()->select('tag_id')->from('#__academy_tag_map')->where('content_item_id=' . (int) $id)->where('type_alias=' . $db->quote($typeAlias))->order('tag_id'))->loadColumn());
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
        $q = $db->createQuery()->select('t.*, t.id AS tag_id, m.content_item_id')->from('#__academy_tags AS t')->join('INNER', '#__academy_tag_map AS m ON m.tag_id=t.id')->where('m.type_alias=' . $db->quote($typeAlias))->whereIn('m.content_item_id', $ids)->where('t.published=1')->order('t.title');
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
        return $ids ? self::db()->setQuery(self::db()->createQuery()->select('*')->from('#__academy_tags')->whereIn('id', $ids))->loadObjectList() : [];
    }
    public static function resolve(array $values, bool $create = false): array
    {
        $db = self::db(); $ids = [];
        foreach ($values as $value) {
            if (!is_scalar($value)) continue;
            $value = trim((string) $value);
            if ($value === '') continue;
            if (ctype_digit($value)) {
                $id = (int) $db->setQuery('SELECT id FROM #__academy_tags WHERE id=' . (int) $value)->loadResult();
                if (!$id) throw new \InvalidArgumentException('Unknown tag ID: ' . $value);
            } else {
                if (!$create) throw new \InvalidArgumentException('Choose an existing tag.');
                $title = mb_substr(trim(strip_tags(preg_replace('/^#new#/', '', $value))), 0, 255);
                if ($title === '') continue;
                $id = (int) $db->setQuery('SELECT id FROM #__academy_tags WHERE title=' . $db->quote($title))->loadResult();
                if (!$id) {
                    $alias = ApplicationHelper::stringURLSafe($title) ?: bin2hex(random_bytes(6));
                    $base = mb_substr($alias, 0, 170); $alias = $base; $n = 2;
                    while ($db->setQuery('SELECT id FROM #__academy_tags WHERE alias=' . $db->quote($alias))->loadResult()) $alias = $base . '-' . $n++;
                    $row = (object) ['title' => $title, 'alias' => $alias, 'published' => 1, 'access' => 1, 'language' => '*', 'description' => '', 'params' => '{}'];
                    $db->insertObject('#__academy_tags', $row, 'id'); $id = (int) $row->id;
                }
            }
            $ids[] = $id;
        }
        return array_values(array_unique($ids));
    }
    public static function assign(int $postId, array $ids, string $type = 'com_academy.post'): void
    {
        $db = self::db();
        $ids = self::resolve($ids);
        $db->transactionStart(true);
        try {
            $db->setQuery('DELETE FROM #__academy_tag_map WHERE content_item_id=' . $postId . ' AND type_alias=' . $db->quote($type))->execute();
            foreach ($ids as $id) {
                $row = (object) ['content_item_id' => $postId, 'tag_id' => $id, 'type_alias' => $type];
                $db->insertObject('#__academy_tag_map', $row);
            }
            $db->transactionCommit(true);
        } catch (\Throwable $e) { $db->transactionRollback(true); throw $e; }
    }
}
