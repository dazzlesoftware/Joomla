<?php
namespace Joomla\Component\Blog\Administrator\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Component\Blog\Administrator\Helper\TagsHelper;

final class CategoriesHelper
{
    public static function db(): DatabaseInterface { return Factory::getContainer()->get(DatabaseInterface::class); }

    /** Whether a category id exists in this component's own native categories table. */
    public static function exists(int $id): bool
    {
        if (!$id) { return false; }
        return (bool) self::db()->setQuery('SELECT 1 FROM #__blog_categories WHERE id=' . $id)->loadResult();
    }

    public static function options(bool $site = false): array
    {
        $db = self::db(); $q = $db->createQuery()->select('id AS value,title AS text')->from('#__blog_categories')->order('lft,title');
        if ($site) { $q->where('published=1')->whereIn('access', Factory::getApplication()->getIdentity()->getAuthorisedViewLevels())->where('language IN (' . $db->quote('*') . ',' . $db->quote(Factory::getApplication()->getLanguage()->getTag()) . ')'); }
        return $db->setQuery($q)->loadObjectList() ?: [];
    }
    public static function save(array $data): int
    {
        $db = self::db(); $id=(int)($data['id'] ?? 0); $title=trim(strip_tags((string)($data['title'] ?? ''))); if($title==='') throw new \InvalidArgumentException('A category title is required.');
        $alias=ApplicationHelper::stringURLSafe((string)($data['alias'] ?? $title)); if($alias==='')$alias='category-'.bin2hex(random_bytes(4)); $base=mb_substr($alias,0,185);$n=2;
        while((int)$db->setQuery('SELECT id FROM #__blog_categories WHERE alias='.$db->quote($alias).($id?' AND id<>'.$id:''))->loadResult()) $alias=$base.'-'.$n++;
        $parentId = (int) ($data['parent_id'] ?? 0);
        if ($parentId && ($parentId === $id || in_array($parentId, self::descendantIds($id), true))) {
            throw new \InvalidArgumentException('A category cannot be its own parent or descendant.');
        }
        $identity = Factory::getApplication()->getIdentity(); $userId = (int) ($identity?->id ?? 0);
        $row=(object)['id'=>$id?:null,'title'=>$title,'alias'=>$alias,'description'=>(string)($data['description']??''),'published'=>(int)($data['published']??1),'access'=>(int)($data['access']??1),'language'=>(string)($data['language']??'*'),'parent_id'=>$parentId,'allow_autoposting'=>(int)($data['allow_autoposting']??1),'default_image'=>(string)($data['default_image']??''),'default_tags'=>(string)($data['default_tags']??''),'created_time'=>Factory::getDate()->toSql(),'created_user_id'=>$userId,'modified_time'=>Factory::getDate()->toSql(),'modified_user_id'=>$userId,'metadata'=>'{}','params'=>'{}'];
        if($id){$old=$db->setQuery('SELECT * FROM #__blog_categories WHERE id='.$id)->loadObject();if(!$old)throw new \RuntimeException('Category not found.',404);foreach(['created_time','created_user_id','metadata','params','asset_id','lft','rgt','level','path'] as $field)if(isset($old->$field))$row->$field=$old->$field;$db->updateObject('#__blog_categories',$row,'id');}else{$row->lft=$row->rgt=$row->level=0;$row->path=$alias;$db->insertObject('#__blog_categories',$row,'id');}
        return (int)$row->id;
    }

    /** Every descendant id of $id (direct and indirect children), used to keep the parent picker acyclic. */
    private static function descendantIds(int $id): array
    {
        if (!$id) { return []; }
        $db = self::db();
        $all = $db->setQuery('SELECT id, parent_id FROM #__blog_categories')->loadObjectList();
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
        $rows = $db->setQuery('SELECT id, parent_id, title FROM #__blog_categories ORDER BY title')->loadObjectList();
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
     * Resolves this category's comma-separated "Default Tags" title list into tag ids,
     * creating any tag that doesn't exist yet. Returns an empty array if the category has
     * no default tags configured.
     *
     * @return string[]
     */
    public static function defaultTagIds(int $categoryId): array
    {
        if (!$categoryId) { return []; }
        $raw = (string) self::db()->setQuery('SELECT default_tags FROM #__blog_categories WHERE id=' . $categoryId)->loadResult();
        $titles = array_filter(array_map('trim', explode(',', $raw)));
        if (!$titles) { return []; }
        return array_map('strval', TagsHelper::resolve(array_values($titles), true));
    }

    /** @param int[] $ids */
    public static function publish(array $ids, int $state): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) { return 0; }
        self::db()->setQuery('UPDATE #__blog_categories SET published=' . (int) $state . ' WHERE id IN (' . implode(',', $ids) . ')')->execute();
        return count($ids);
    }

    /** @param int[] $ids */
    public static function delete(array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) { return 0; }
        $db = self::db(); $list = implode(',', $ids);
        $db->setQuery('UPDATE #__blog SET catid=0 WHERE catid IN (' . $list . ')')->execute();
        $db->setQuery('UPDATE #__blog_categories SET parent_id=0 WHERE parent_id IN (' . $list . ')')->execute();
        $db->setQuery('DELETE FROM #__blog_categories WHERE id IN (' . $list . ')')->execute();
        return count($ids);
    }

    /**
     * Stores an uploaded "Default Post Cover" image under images/blog_categories/ and
     * returns its site-relative path (e.g. "images/blog_categories/xxxx.jpg"), or ''
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
        $dir = JPATH_ROOT . '/images/blog_categories';
        if (!is_dir($dir)) {
            \Joomla\Filesystem\Folder::create($dir);
        }
        $name = 'cat-' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!\Joomla\Filesystem\File::upload($file['tmp_name'], $dir . '/' . $name)) {
            throw new \RuntimeException('Could not save the uploaded image.');
        }
        return 'images/blog_categories/' . $name;
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
            $row = $db->setQuery('SELECT * FROM #__blog_categories WHERE id=' . $id)->loadObject();
            if (!$row) { continue; }
            self::save(['id' => 0, 'title' => $row->title . ' (2)', 'description' => $row->description, 'published' => $row->published, 'access' => $row->access, 'language' => $row->language, 'parent_id' => $row->parent_id, 'allow_autoposting' => $row->allow_autoposting ?? 1, 'default_image' => $row->default_image ?? '', 'default_tags' => $row->default_tags ?? '']);
            $done++;
        }
        return $done;
    }
}
