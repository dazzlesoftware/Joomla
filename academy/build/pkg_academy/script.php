<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\Database\DatabaseInterface;

final class pkg_academyInstallerScript
{
    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $folders = [$db->quote('academy'), $db->quote('task')];
        $elements = [$db->quote('academy_mailqueue'), $db->quote('academybranding'), $db->quote('academyloader')];
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where('((' . $db->quoteName('folder') . ' = ' . $db->quote('academy') . ') OR ('
                . $db->quoteName('folder') . ' IN (' . implode(',', [$db->quote('task'), $db->quote('system')]) . ') AND '
                . $db->quoteName('element') . ' IN (' . implode(',', $elements) . ')))');
        $db->setQuery($query)->execute();
        return true;
    }
}