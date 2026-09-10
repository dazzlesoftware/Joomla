<?php
defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Table\Menu;
use Joomla\Database\DatabaseInterface;

class Com_AcademyInstallerScript
{
    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        // Joomla recreates manifest submenu rows after install/update handlers.
        // Apply the second-level hierarchy only once those rows exist.
        return $this->repairAdminMenu();
    }

    public function install(InstallerAdapter $adapter): bool
    {
        return $this->ensureNativeCategories() && $this->ensureNativeTags() && $this->ensureExcerptSchema() && $this->ensureSubscriberSchema() && $this->ensureCampaignSchema() && $this->ensureAutopostSchema() && $this->registerContentTypes() && $this->repairAdminMenu() && $this->seedComponentDefaults();
    }

    public function update(InstallerAdapter $adapter): bool
    {
        return $this->ensureNativeCategories() && $this->ensureNativeTags() && $this->ensureExcerptSchema() && $this->ensureSubscriberSchema() && $this->ensureCampaignSchema() && $this->ensureAutopostSchema() && $this->registerContentTypes() && $this->repairAdminMenu() && $this->seedComponentDefaults();
    }

    public function uninstall(InstallerAdapter $adapter): bool
    {
        $db = Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__content_types'))
            ->where($db->quoteName('type_alias') . ' IN (' . implode(',', $db->quote(['com_academy.post', 'com_academy.category'])) . ')');
        $db->setQuery($query)->execute();
        return true;
    }

    private function registerContentTypes(): bool
    {
        $db = Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        foreach (['post', 'category'] as $section) {
            $alias = 'com_academy.' . $section;
            $query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__content_types'))->where($db->quoteName('type_alias') . ' = ' . $db->quote($alias));
            if ((int) $db->setQuery($query)->loadResult() > 0) { continue; }

            // The source row is Joomla's built-in content type; the installed
            // family alias itself is always the native "post" alias.
            $sourceSection = $section === 'post' ? 'arti' . 'cle' : $section;
            $sourceAlias = 'com_content.' . $sourceSection;
            $query = $db->getQuery(true)->select('*')->from($db->quoteName('#__content_types'))->where($db->quoteName('type_alias') . ' = ' . $db->quote($sourceAlias));
            $row = $db->setQuery($query)->loadObject();
            if (!$row) { continue; }

            unset($row->type_id);
            foreach ($row as $field => $value) {
                if (is_string($value)) {
                    $value = str_replace(['com_content', '#__content', 'Joomla\\\\Component\\\\Content'], ['com_academy', '#__academy', 'Joomla\\\\Component\\\\Academy'], $value);
                    $row->$field = $value;
                }
            }
            if ($section === 'post') {
                $mappings = json_decode((string) $row->field_mappings);
                if (isset($mappings->common)) {
                    $mappings->common->core_body = 'summary';
                    $mappings->common->core_params = 'options';
                    $mappings->common->core_images = 'media';
                    unset($mappings->common->core_urls);
                    $mappings->special = (object) ['body' => 'body'];
                    $row->field_mappings = json_encode($mappings, JSON_UNESCAPED_SLASHES);
                }
            }
            $row->type_title = 'Academy ' . $row->type_title;
            $db->insertObject('#__content_types', $row);
        }
        return true;
    }

    private function ensureExcerptSchema(): bool
    {
        $db = Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        $columns = $db->getTableColumns('#__academy');
        if (!isset($columns['excerpt'])) {
            $db->setQuery('ALTER TABLE #__academy ADD excerpt MEDIUMTEXT NULL AFTER summary')->execute();
        }
        // Older family tables were created before Joomla's multilingual post field.
        // Views and routes use it, so add it during upgrades as well as new installs.
        if (!isset($columns['language'])) {
            $db->setQuery("ALTER TABLE #__academy ADD language char(7) NOT NULL DEFAULT '*' AFTER featured")->execute();
        }
        return true;
    }
    private function ensureSubscriberSchema(): bool
    {
        $db = Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery("CREATE TABLE IF NOT EXISTS #__academy_subscriber_suppressions (id int unsigned NOT NULL AUTO_INCREMENT,email varchar(320) NOT NULL,reason varchar(64) NOT NULL DEFAULT 'removed',created datetime NOT NULL,PRIMARY KEY (id),UNIQUE KEY idx_email (email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();
        $db->setQuery("CREATE TABLE IF NOT EXISTS #__academy_import_batches (id int unsigned NOT NULL AUTO_INCREMENT,source varchar(255) NOT NULL,created datetime NOT NULL,created_by int unsigned NOT NULL DEFAULT 0,imported_count int unsigned NOT NULL DEFAULT 0,PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();
        $db->setQuery("CREATE TABLE IF NOT EXISTS #__academy_import_items (batch_id int unsigned NOT NULL,post_id int unsigned NOT NULL,PRIMARY KEY (batch_id,post_id),KEY idx_post (post_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();
        $columns = $db->getTableColumns('#__academy_subscribers');
        $additions = [
            'confirm_token' => "ALTER TABLE #__academy_subscribers ADD confirm_token varchar(64) NOT NULL DEFAULT '' AFTER token",
            'confirmed' => "ALTER TABLE #__academy_subscribers ADD confirmed datetime NULL AFTER consented",
            'unsubscribed' => "ALTER TABLE #__academy_subscribers ADD unsubscribed datetime NULL AFTER confirmed",
            'consent_ip' => "ALTER TABLE #__academy_subscribers ADD consent_ip varchar(45) NOT NULL DEFAULT '' AFTER unsubscribed",
        ];
        foreach ($additions as $column => $sql) {
            if (!isset($columns[$column])) {
                $db->setQuery(str_replace('#__', $db->getPrefix(), $sql))->execute();
            }
        }
        $db->setQuery("UPDATE #__academy_subscribers SET confirmed=consented WHERE state=1 AND confirmed IS NULL")->execute();
        return true;
    }

    private function ensureCampaignSchema(): bool
    {
        $db = Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        $columns = $db->getTableColumns('#__academy_campaigns');
        if (!isset($columns['segment'])) {
            $db->setQuery('ALTER TABLE #__academy_campaigns ADD segment varchar(32) NOT NULL DEFAULT ' . $db->quote('all') . ' AFTER state')->execute();
        }
        if (!isset($columns['scheduled_at'])) {
            $db->setQuery('ALTER TABLE #__academy_campaigns ADD scheduled_at datetime NULL AFTER segment')->execute();
        }
        $queueColumns = $db->getTableColumns('#__academy_mail_queue');
        $queueAdditions = [
            'tracking_token' => "ALTER TABLE #__academy_mail_queue ADD tracking_token char(48) NOT NULL DEFAULT '' AFTER token",
            'opened_count' => "ALTER TABLE #__academy_mail_queue ADD opened_count int unsigned NOT NULL DEFAULT 0 AFTER error",
            'clicked_count' => "ALTER TABLE #__academy_mail_queue ADD clicked_count int unsigned NOT NULL DEFAULT 0 AFTER opened_count",
            'last_opened' => "ALTER TABLE #__academy_mail_queue ADD last_opened datetime NULL AFTER clicked_count",
            'last_clicked' => "ALTER TABLE #__academy_mail_queue ADD last_clicked datetime NULL AFTER last_opened",
        ];
        foreach ($queueAdditions as $column => $sql) {
            if (!isset($queueColumns[$column])) $db->setQuery($sql)->execute();
        }
        return true;
    }

    private function ensureAutopostSchema(): bool
    {
        $db = Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery("CREATE TABLE IF NOT EXISTS #__academy_autopost_logs (id bigint unsigned NOT NULL AUTO_INCREMENT,post_id int unsigned NOT NULL,provider varchar(24) NOT NULL,event varchar(12) NOT NULL,status varchar(12) NOT NULL,remote_id varchar(255) NOT NULL DEFAULT '',message text NOT NULL,response text NOT NULL,created datetime NOT NULL,PRIMARY KEY (id),KEY idx_post (post_id),KEY idx_provider_status (provider,status),KEY idx_created (created)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")->execute();
        return true;
    }

    private function repairAdminMenu(): bool
    {
        $db = Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__menu'))
            ->set($db->quoteName('link') . ' = REPLACE(' . $db->quoteName('link') . ', ' . $db->quote('index.php?index.php?') . ', ' . $db->quote('index.php?') . ')')
            ->where($db->quoteName('client_id') . ' = 1')
            ->where($db->quoteName('component_id') . ' = (SELECT ' . $db->quoteName('extension_id') . ' FROM ' . $db->quoteName('#__extensions') . ' WHERE ' . $db->quoteName('element') . ' = ' . $db->quote('com_academy') . ' AND ' . $db->quoteName('type') . ' = ' . $db->quote('component') . ')');
        $db->setQuery($query)->execute();
        $componentId = (int) $db->setQuery($db->getQuery(true)->select('extension_id')->from('#__extensions')->where('type=' . $db->quote('component'))->where('element=' . $db->quote('com_academy')))->loadResult();
        foreach ([
            'academy-posts-group' => ['view=posts'],
            'academy-autopost-group' => ['view=autopost&', 'view=autopostlogs'],
            'academy-marketing-group' => ['view=subscribers', 'view=newsletter', 'view=emailtemplates'],
            'academy-migration-group' => ['view=import', 'view=export'],
        ] as $alias => $needles) {
            $parentId = (int) $db->setQuery($db->getQuery(true)->select('id')->from('#__menu')->where('client_id=1')->where('component_id=' . $componentId)->where('alias=' . $db->quote($alias)))->loadResult();
            if (!$parentId) { continue; }
            foreach ($needles as $needle) {
                $ids = $db->setQuery($db->getQuery(true)->select('id')->from('#__menu')->where('client_id=1')->where('component_id=' . $componentId)->where('id<>' . $parentId)->where('link LIKE ' . $db->quote('%' . $needle . '%')))->loadColumn();
                foreach ($ids as $id) {
                    $table = new Menu($db);
                    if ($table->load((int) $id) && (int) $table->parent_id !== $parentId) {
                        $table->setLocation($parentId, 'last-child'); $table->parent_id = $parentId; $table->store();
                    }
                }
            }
        }
        return true;
    }

    private function seedComponentDefaults(): bool
    {
        $db = Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select([$db->quoteName('element'), $db->quoteName('params')])
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
            ->where($db->quoteName('element') . ' IN (' . implode(',', $db->quote(['com_content', 'com_academy'])) . ')');
        $rows = $db->setQuery($query)->loadAssocList('element', 'params');

        $current = trim((string) ($rows['com_academy'] ?? ''));
        $defaults = (string) ($rows['com_content'] ?? '');
        $params = new Joomla\Registry\Registry(
            (($current === '' || $current === '{}') && $defaults !== '') ? $defaults : $current
        );

        // These post components use their own direct status and featured fields.
        // com_content's workflow setting must not be inherited: without matching
        // workflow records Joomla replaces both controls with an unusable dash.
        $params->set('workflow_enabled', 0);

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('params') . ' = ' . $db->quote((string) $params))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_academy'));
        $db->setQuery($query)->execute();

        return true;
    }
    private function ensureNativeTags(): bool
    {
        require_once __DIR__ . '/admin/src/Helper/TagSchema.php';
        \Joomla\Component\Academy\Administrator\Helper\TagSchema::ensure(Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class));
        return true;
    }
    private function ensureNativeCategories(): bool
    {
        require_once __DIR__ . '/admin/src/Helper/CategorySchema.php';
        \Joomla\Component\Academy\Administrator\Helper\CategorySchema::ensure(Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class));
        return true;
    }
}