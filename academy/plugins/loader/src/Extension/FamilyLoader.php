<?php

namespace Joomla\Plugin\System\AcademyLoader\Extension;

defined('_JEXEC') or die;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\SubscriberInterface;

final class FamilyLoader extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['onAfterInitialise' => 'loadPluginGroup'];
    }

    public function loadPluginGroup(): void
    {
        $option = $this->getApplication()->getInput()->getCmd('option');

        if ($option === 'com_academy') {
            PluginHelper::importPlugin('academy');
            return;
        }

        if ($this->getApplication()->isClient('api')) {
            PluginHelper::importPlugin('academy', 'webservices');
        } elseif ($option === 'com_finder') {
            PluginHelper::importPlugin('academy', 'finder');
        } elseif ($option === 'com_privacy') {
            PluginHelper::importPlugin('academy', 'privacy');
        }
    }
}
