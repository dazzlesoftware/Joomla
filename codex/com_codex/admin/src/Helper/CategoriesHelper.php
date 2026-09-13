<?php
namespace Joomla\Component\Codex\Administrator\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\Database\DatabaseInterface;
use Joomla\Component\Codex\Administrator\Helper\TagsHelper;

final class CategoriesHelper
{
    /** The context this component's categories are stored under in the shared
     * #__associations table - the same generic table and mechanism Joomla core
     * uses for com_content categories/articles, just under our own context
     * string and pointing at our own native categories table. */
    private const ASSOCIATIONS_CONTEXT = 'com_codex.category';

    public static function db(): DatabaseInterface { return Factory::getContainer()->get(DatabaseInterface::class); }

    /** Whether a category id exists in this component's own native categories table. */
    public static function exists(int $id): bool
    {
        if (!$id) { return false; }
        return (bool) self::db()->setQuery('SELECT 1 FROM #__codex_categories WHERE id=' . $id)->loadResult();
    }

    public static function options(bool $site = false): array
    {
        $db = self::db(); $q = $db->createQuery()->select('id AS value,title AS text')->from('#__codex_categories')->order('lft,title');
        if ($site) { $q->where('published=1')->whereIn('access', Factory::getApplication()->getIdentity()->getAuthorisedViewLevels())->where('language IN (' . $db->quote('*') . ',' . $db->quote(Factory::getApplication()->getLanguage()->getTag()) . ')'); }
        return $db->setQuery($q)->loadObjectList() ?: [];
    }
    public static function save(array $data): int
    {
        $db = self::db(); $id=(int)($data['id'] ?? 0); $title=trim(strip_tags((string)($data['title'] ?? ''))); if($title==='') throw new \InvalidArgumentException('A category title is required.');
        $alias=ApplicationHelper::stringURLSafe((string)($data['alias'] ?? $title)); if($alias==='')$alias='category-'.bin2hex(random_bytes(4)); $base=mb_substr($alias,0,185);$n=2;
        while((int)$db->setQuery('SELECT id FROM #__codex_categories WHERE alias='.$db->quote($alias).($id?' AND id<>'.$id:''))->loadResult()) $alias=$base.'-'.$n++;
        $parentId = (int) ($data['parent_id'] ?? 0);
        if ($parentId && ($parentId === $id || in_array($parentId, self::descendantIds($id), true))) {
            throw new \InvalidArgumentException('A category cannot be its own parent or descendant.');
        }
        $identity = Factory::getApplication()->getIdentity(); $userId = (int) ($identity?->id ?? 0);
        // 'extension' mirrors the column core's own shared #__categories table
        // carries: com_associations' generic category query hardcodes a WHERE
        // a.extension = <component name> filter, so every row needs it set.
        $row=(object)['id'=>$id?:null,'title'=>$title,'alias'=>$alias,'description'=>(string)($data['description']??''),'published'=>(int)($data['published']??1),'access'=>(int)($data['access']??1),'language'=>(string)($data['language']??'*'),'parent_id'=>$parentId,'extension'=>'com_codex','allow_autoposting'=>(int)($data['allow_autoposting']??1),'default_image'=>(string)($data['default_image']??''),'default_tags'=>(string)($data['default_tags']??''),'created_time'=>Factory::getDate()->toSql(),'created_user_id'=>$userId,'modified_time'=>Factory::getDate()->toSql(),'modified_user_id'=>$userId,'metadata'=>'{}','params'=>'{}'];
        if($id){$old=$db->setQuery('SELECT * FROM #__codex_categories WHERE id='.$id)->loadObject();if(!$old)throw new \RuntimeException('Category not found.',404);foreach(['created_time','created_user_id','metadata','params','asset_id','lft','rgt','level','path'] as $field)if(isset($old->$field))$row->$field=$old->$field;$db->updateObject('#__codex_categories',$row,'id');}else{$row->lft=$row->rgt=$row->level=0;$row->path=$alias;$db->insertObject('#__codex_categories',$row,'id');}
        return (int)$row->id;
    }

    /** Every descendant id of $id (direct and indirect children), used to keep the parent picker acyclic. */
    private static function descendantIds(int $id): array
    {
        if (!$id) { return []; }
        $db = self::db();
        $all = $db->setQuery('SELECT id, parent_id FROM #__codex_categories')->loadObjectList();
        $byParent = [];
        foreach ($all as $row) { $byParent[(int) $row->parent_id][] = (int) $row->id; }
        $descendants = []; $queue = $byParent[$id] ?? [];
        while ($queue) {
            $current = array_shift($queue);
            if (in_array($current, $descendants, true)) { continue; }
            $descendants[] = $current;
            foreach ($byParent[$current] ?? [] as $child) { $queue[] = $child; }
        }
        return $descendants;
    }

    /**
     * Options for the Parent Category picker: every category except $excludeId and its
     * descendants, indented by depth for display.
     *
     * @return array<int, object{value:int,text:string,level:int}>
     */
    public static function parentOptions(int $excludeId = 0): array
    {
        $db = self::db();
        $rows = $db->setQuery('SELECT id, parent_id, title FROM #__codex_categories ORDER BY title')->loadObjectList();
        $byId = [];
        foreach ($rows as $row) { $byId[(int) $row->id] = $row; }
        $exclude = $excludeId ? array_merge([$excludeId], self::descendantIds($excludeId)) : [];
        $options = [];
        foreach ($rows as $row) {
            $id = (int) $row->id;
            if (in_array($id, $exclude, true)) { continue; }
            $level = 0; $walk = (int) $row->parent_id; $guard = 0;
            while ($walk && isset($byId[$walk]) && $guard++ < 50) { $level++; $walk = (int) $byId[$walk]->parent_id; }
            $options[] = (object) ['value' => $id, 'text' => $row->title, 'level' => $level];
        }
        usort($options, fn ($a, $b) => strcasecmp($a->text, $b->text));
        return $options;
    }

    /**
     * Categories in a given language, for the per-language Associations picker.
     * Excludes $excludeId (the category being edited) since a category can't be
     * associated with itself.
     *
     * @return array<int, object{id:int,title:string}>
     */
    public static function optionsForLanguage(string $language, int $excludeId = 0): array
    {
        $db = self::db();
        $q = $db->createQuery()->select('id,title')->from('#__codex_categories')
            ->where('language=' . $db->quote($language))
            ->order('title');
        if ($excludeId) { $q->where('id != ' . $excludeId); }
        return $db->setQuery($q)->loadObjectList() ?: [];
    }

    /**
     * The existing per-language associations for a category, as [lang_code => id],
     * read via Joomla's shared #__associations table under our own context.
     *
     * @return array<string, int>
     */
    public static function getAssociations(int $id): array
    {
        if (!$id) { return []; }
        $rows = Associations::getAssociations('com_codex', '#__codex_categories', self::ASSOCIATIONS_CONTEXT, $id, 'id', 'alias', '');
        $out = [];
        foreach ($rows as $tag => $row) { $out[$tag] = (int) $row->id; }
        return $out;
    }

    /**
     * Links a category to its translated counterparts, mirroring exactly how
     * Joomla core's own AdminModel::save() maintains the shared #__associations
     * table - so this stays fully compatible with the core Associations admin
     * screen and the site-side Language Switcher module.
     *
     * @param array<string,int> $associations lang_code => associated category id (0/absent = no association for that language)
     */
    public static function saveAssociations(int $id, string $language, array $associations): void
    {
        $db = self::db();
        $context = self::ASSOCIATIONS_CONTEXT;

        $associations = array_filter(array_map('intval', $associations));

        if ($associations && $language === '*') {
            Factory::getApplication()->enqueueMessage(
                'A category set to All languages can\'t be associated. Associations have not been set.',
                'warning'
            );

            return;
        }

        $oldKey = $db->setQuery(
            $db->createQuery()->select($db->quoteName('key'))->from('#__associations')
                ->where('context=' . $db->quote($context))
                ->where('id=' . $id)
        )->loadResult();

        if ($associations || $oldKey !== null) {
            $delete = $db->createQuery()->delete('#__associations')->where('context=' . $db->quote($context));
            $where = [];
            if ($associations) { $where[] = 'id IN (' . implode(',', array_values($associations)) . ')'; }
            if ($oldKey !== null) { $where[] = $db->quoteName('key') . '=' . $db->quote($oldKey); }
            $delete->extendWhere('AND', $where, 'OR');
            $db->setQuery($delete)->execute();
        }

        if ($language !== '*') {
            $associations[$language] = $id;
        }

        if (count($associations) > 1) {
            $key = md5(json_encode($associations));
            $insert = $db->createQuery()->insert('#__associations')->columns(['id', 'context', $db->quoteName('key')]);
            foreach ($associations as $assocId) {
                $insert->values((int) $assocId . ',' . $db->quote($context) . ',' . $db->quote($key));
            }
            $db->setQuery($insert)->execute();
        }
    }

    /**
     * Resolves this category's comma-separated "Default Tags" title list into tag ids,
     * creating any tag that doesn't exist yet. Returns an empty array if the category has
     * no default tags configured.
     *
     * @return string[]
     */
    public static function defaultTagIds(int $categoryId): array
    {
        if (!$categoryId) { return []; }
        $raw = (string) self::db()->setQuery('SELECT default_tags FROM #__codex_categories WHERE id=' . $categoryId)->loadResult();
        $titles = array_filter(array_map('trim', explode(',', $raw)));
        if (!$titles) { return []; }
        return array_map('strval', TagsHelper::resolve(array_values($titles), true));
    }

    /** @param int[] $ids */
    public static function publish(array $ids, int $state): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) { return 0; }
        self::db()->setQuery('UPDATE #__codex_categories SET published=' . (int) $state . ' WHERE id IN (' . implode(',', $ids) . ')')->execute();
        return count($ids);
    }

    /** @param int[] $ids */
    public static function delete(array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) { return 0; }
        $db = self::db(); $list = implode(',', $ids);
        $db->setQuery('UPDATE #__codex SET catid=0 WHERE catid IN (' . $list . ')')->execute();
        $db->setQuery('UPDATE #__codex_categories SET parent_id=0 WHERE parent_id IN (' . $list . ')')->execute();
        $db->setQuery('DELETE FROM #__codex_categories WHERE id IN (' . $list . ')')->execute();
        $db->setQuery('DELETE FROM #__associations WHERE context=' . $db->quote(self::ASSOCIATIONS_CONTEXT) . ' AND id IN (' . $list . ')')->execute();
        return count($ids);
    }

    /**
     * Stores an uploaded "Default Post Cover" image under images/codex_categories/ and
     * returns its site-relative path (e.g. "images/codex_categories/xxxx.jpg"), or ''
     * if no file was uploaded.
     */
    public static function saveDefaultImage(array $file): string
    {
        if (empty($file['name']) || empty($file['tmp_name']) || (int) ($file['error'] ?? 1) !== 0) {
            return '';
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!\in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            throw new \InvalidArgumentException('The default post cover must be a JPG, PNG, GIF, or WEBP image.');
        }
        $dir = JPATH_ROOT . '/images/codex_categories';
        if (!is_dir($dir)) {
            \Joomla\Filesystem\Folder::create($dir);
        }
        $name = 'cat-' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!\Joomla\Filesystem\File::upload($file['tmp_name'], $dir . '/' . $name)) {
            throw new \RuntimeException('Could not save the uploaded image.');
        }
        return 'images/codex_categories/' . $name;
    }

    public static function deleteDefaultImage(string $path): void
    {
        if ($path === '') { return; }
        $full = JPATH_ROOT . '/' . ltrim($path, '/');
        if (is_file($full)) {
            \Joomla\Filesystem\File::delete($full);
        }
    }

    /** @param int[] $ids */
    public static function copy(array $ids): int
    {
        $db = self::db(); $done = 0;
        foreach (array_unique(array_filter(array_map('intval', $ids))) as $id) {
            $row = $db->setQuery('SELECT * FROM #__codex_categories WHERE id=' . $id)->loadObject();
            if (!$row) { continue; }
            self::save(['id' => 0, 'title' => $row->title . ' (2)', 'description' => $row->description, 'published' => $row->published, 'access' => $row->access, 'language' => $row->language, 'parent_id' => $row->parent_id, 'allow_autoposting' => $row->allow_autoposting ?? 1, 'default_image' => $row->default_image ?? '', 'default_tags' => $row->default_tags ?? '']);
            $done++;
        }
        return $done;
    }
}
