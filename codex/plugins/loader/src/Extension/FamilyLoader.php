<?php

namespace Joomla\Plugin\System\CodexLoader\Extension;

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

        if ($option === 'com_codex') {
            PluginHelper::importPlugin('codex');
            return;
        }

        if ($this->getApplication()->isClient('api')) {
            PluginHelper::importPlugin('codex', 'webservices');
        } elseif ($option === 'com_finder') {
            PluginHelper::importPlugin('codex', 'finder');
        } elseif ($option === 'com_privacy') {
            PluginHelper::importPlugin('codex', 'privacy');
        }
    }
}
