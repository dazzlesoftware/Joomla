<?php
namespace Joomla\Component\Blog\Administrator\Helper;
defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

final class CategorySchema
{
    public static function ensure(DatabaseInterface $db): void
    {
        $db->setQuery("CREATE TABLE IF NOT EXISTS #__blog_categories (id INT UNSIGNED NOT NULL AUTO_INCREMENT, asset_id INT UNSIGNED NOT NULL DEFAULT 0, parent_id INT UNSIGNED NOT NULL DEFAULT 0, lft INT NOT NULL DEFAULT 0, rgt INT NOT NULL DEFAULT 0, level INT NOT NULL DEFAULT 1, path VARCHAR(400) NOT NULL DEFAULT '', title VARCHAR(255) NOT NULL, alias VARCHAR(400) NOT NULL, description MEDIUMTEXT NOT NULL, published TINYINT NOT NULL DEFAULT 1, access INT UNSIGNED NOT NULL DEFAULT 1, language CHAR(7) NOT NULL DEFAULT '*', created_time DATETIME NULL, created_user_id INT UNSIGNED NOT NULL DEFAULT 0, modified_time DATETIME NULL, modified_user_id INT UNSIGNED NOT NULL DEFAULT 0, metadata TEXT NOT NULL, params TEXT NOT NULL, PRIMARY KEY(id), KEY idx_parent(parent_id), KEY idx_state(published), KEY idx_access(access), KEY idx_language(language), KEY idx_alias(alias(191))) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();
        $db->setQuery("CREATE TABLE IF NOT EXISTS #__blog_category_migrations (version INT NOT NULL PRIMARY KEY) ENGINE=InnoDB")->execute();
        if ($db->setQuery('SELECT version FROM #__blog_category_migrations WHERE version=1')->loadResult()) {
            if (!(int) $db->setQuery('SELECT COUNT(*) FROM #__blog_categories')->loadResult()) {
                $fallback = (object) ['title' => 'Uncategorised', 'alias' => 'uncategorised', 'description' => '', 'published' => 1, 'access' => 1, 'language' => '*', 'parent_id' => 0, 'lft' => 1, 'rgt' => 2, 'level' => 1, 'path' => 'uncategorised', 'created_time' => null, 'created_user_id' => 0, 'modified_time' => null, 'modified_user_id' => 0, 'metadata' => '{}', 'params' => '{}'];
                $db->insertObject('#__blog_categories', $fallback, 'id');
            }
            return;
        }
        $db->transactionStart();
        try {
            $columns = $db->getTableColumns('#__categories');
            if ($columns) {
                $sql = "INSERT IGNORE INTO #__blog_categories (id,asset_id,parent_id,lft,rgt,level,path,title,alias,description,published,access,language,created_time,created_user_id,modified_time,modified_user_id,metadata,params) SELECT id,asset_id,0,lft,rgt,level,path,title,alias,description,published,access,language,created_time,created_user_id,modified_time,modified_user_id,metadata,params FROM #__categories WHERE extension=" . $db->quote('com_blog') . ' AND level > 0';
                $db->setQuery($sql)->execute();
            }
            if (!(int) $db->setQuery('SELECT COUNT(*) FROM #__blog_categories')->loadResult()) {
                $row = (object) ['title' => 'Uncategorised', 'alias' => 'uncategorised', 'description' => '', 'published' => 1, 'access' => 1, 'language' => '*', 'parent_id' => 0, 'lft' => 1, 'rgt' => 2, 'level' => 1, 'path' => 'uncategorised', 'created_time' => null, 'created_user_id' => 0, 'modified_time' => null, 'modified_user_id' => 0, 'metadata' => '{}', 'params' => '{}'];
                $db->insertObject('#__blog_categories', $row, 'id');
            }
            $db->setQuery('INSERT INTO #__blog_category_migrations (version) VALUES (1)')->execute();
            $db->transactionCommit();
        } catch (\Throwable $e) { $db->transactionRollback(); throw $e; }
    }
}
