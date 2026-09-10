<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\Database\DatabaseInterface;

final class pkg_codexInstallerScript
{
    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $folders = [$db->quote('codex'), $db->quote('task')];
        $elements = [$db->quote('codex_mailqueue'), $db->quote('codexbranding'), $db->quote('codexloader')];
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where('((' . $db->quoteName('folder') . ' = ' . $db->quote('codex') . ') OR ('
                . $db->quoteName('folder') . ' IN (' . implode(',', [$db->quote('task'), $db->quote('system')]) . ') AND '
                . $db->quoteName('element') . ' IN (' . implode(',', $elements) . ')))');
        $db->setQuery($query)->execute();
        return true;
    }
}