<?php
namespace Joomla\Component\Codex\Administrator\Helper;
defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;

final class TagSchema
{
    public static function ensure(DatabaseInterface $db): void
    {
        $db->setQuery("CREATE TABLE IF NOT EXISTS #__codex_tags (id INT UNSIGNED NOT NULL AUTO_INCREMENT, title VARCHAR(255) NOT NULL, alias VARCHAR(191) NOT NULL, description TEXT NOT NULL, published TINYINT NOT NULL DEFAULT 1, access INT UNSIGNED NOT NULL DEFAULT 1, language VARCHAR(7) NOT NULL DEFAULT '*', is_default TINYINT NOT NULL DEFAULT 0, created_by INT UNSIGNED NOT NULL DEFAULT 0, params TEXT NOT NULL, PRIMARY KEY(id), UNIQUE KEY idx_alias(alias), KEY idx_state(published)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();
        $db->setQuery("CREATE TABLE IF NOT EXISTS #__codex_tag_map (content_item_id INT UNSIGNED NOT NULL, tag_id INT UNSIGNED NOT NULL, type_alias VARCHAR(64) NOT NULL DEFAULT 'com_codex.post', PRIMARY KEY(content_item_id,tag_id,type_alias), KEY idx_tag(tag_id,type_alias)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();
        $db->setQuery("CREATE TABLE IF NOT EXISTS #__codex_tag_migrations (version INT NOT NULL PRIMARY KEY) ENGINE=InnoDB")->execute();

        // Additive columns for tags created before these settings existed.
        $columns = $db->getTableColumns('#__codex_tags');
        if (!isset($columns['is_default'])) {
            $db->setQuery('ALTER TABLE #__codex_tags ADD COLUMN is_default TINYINT NOT NULL DEFAULT 0 AFTER language')->execute();
        }
        if (!isset($columns['created_by'])) {
            $db->setQuery('ALTER TABLE #__codex_tags ADD COLUMN created_by INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_default')->execute();
        }

        if ($db->setQuery('SELECT version FROM #__codex_tag_migrations WHERE version=1')->loadResult()) return;
        $tables = $db->getTableList();
        $legacy = in_array($db->getPrefix() . 'tags', $tables, true) && in_array($db->getPrefix() . 'contentitem_tag_map', $tables, true);
        $db->transactionStart();
        try {
            if ($legacy) {
                $types = "('com_codex.post','com_codex.article','com_codex.category')";
                $tags = $db->setQuery('SELECT DISTINCT t.id,t.title,t.alias,t.description,t.published,t.access,t.language,t.params FROM #__tags t INNER JOIN #__contentitem_tag_map m ON m.tag_id=t.id WHERE m.type_alias IN ' . $types)->loadObjectList();
                foreach ($tags as $tag) {
                    if (!$db->setQuery('SELECT id FROM #__codex_tags WHERE id=' . (int) $tag->id)->loadResult()) {
                        $base = mb_substr($tag->alias ?: 'tag-' . $tag->id, 0, 170);
                        $tag->alias = $base;
                        if ($db->setQuery('SELECT id FROM #__codex_tags WHERE alias=' . $db->quote($base))->loadResult()) $tag->alias .= '-' . $tag->id;
                        $db->insertObject('#__codex_tags', $tag);
                    }
                }
                $db->setQuery("INSERT IGNORE INTO #__codex_tag_map (content_item_id,tag_id,type_alias) SELECT DISTINCT m.content_item_id,m.tag_id,'com_codex.post' FROM #__contentitem_tag_map m INNER JOIN #__codex p ON p.id=m.content_item_id INNER JOIN #__codex_tags t ON t.id=m.tag_id WHERE m.type_alias IN ('com_codex.post','com_codex.article')")->execute();
                $db->setQuery("INSERT IGNORE INTO #__codex_tag_map (content_item_id,tag_id,type_alias) SELECT DISTINCT m.content_item_id,m.tag_id,'com_codex.category' FROM #__contentitem_tag_map m INNER JOIN #__codex_categories c ON c.id=m.content_item_id INNER JOIN #__codex_tags t ON t.id=m.tag_id WHERE m.type_alias='com_codex.category'")->execute();
            }
            $db->setQuery('INSERT INTO #__codex_tag_migrations (version) VALUES (1)')->execute();
            $db->transactionCommit();
        } catch (\Throwable $e) { $db->transactionRollback(); throw $e; }
    }
}
