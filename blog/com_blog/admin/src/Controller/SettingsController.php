<?php

namespace Joomla\Component\Blog\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

final class SettingsController extends BaseController
{
    public function cancel(): void
    {
        $this->setRedirect(Route::_('index.php?option=com_blog&view=dashboard', false));
    }

    public function save(): void
    {
        $this->saveSettings(false);
    }

    public function save2close(): void
    {
        $this->saveSettings(true);
    }

    private function saveSettings(bool $close): void
    {
        if (!Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $app = Factory::getApplication();

        if (!$app->getIdentity()->authorise('core.options', 'com_blog')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $submitted = $app->getInput()->post->get('jform', [], 'array');
        $values    = (array) ($submitted['params'] ?? []);
        $params    = clone ComponentHelper::getParams('com_blog');

        foreach (['openai', 'claude'] as $provider) {
            $key = trim((string) ($values['ai_' . $provider . '_key'] ?? ''));
            if (!empty($values['ai_' . $provider . '_clear'])) { $params->set('ai_' . $provider . '_secret', ''); }
            if ($key !== '') { $params->set('ai_' . $provider . '_secret', \Joomla\Component\Blog\Administrator\Helper\NeuralNetworkService::seal($key)); }
            unset($values['ai_' . $provider . '_key'], $values['ai_' . $provider . '_clear'], $values['ai_' . $provider . '_secret']);
        }
        foreach ($values as $name => $value) {
            $params->set((string) $name, $value);
        }

        // The Post components use direct publishing states, not Joomla workflows.
        $params->set('workflow_enabled', 0);

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $paramsJson = $params->toString();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('params') . ' = :params')
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_blog'))
            ->bind(':params', $paramsJson);
        $db->setQuery($query)->execute();

        $app->enqueueMessage(Text::_('COM_BLOG_SETTINGS_SAVED'));
        $view = $close ? 'dashboard' : 'settings';
        $this->setRedirect(Route::_('index.php?option=com_blog&view=' . $view, false));
    }
}
