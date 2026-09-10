<?php
namespace Joomla\Plugin\System\BlogLoader\Extension;
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

        if ($option === 'com_blog') {
            PluginHelper::importPlugin('blog');
            return;
        }

        if ($this->getApplication()->isClient('api')) {
            PluginHelper::importPlugin('blog', 'webservices');
        } elseif ($option === 'com_finder') {
            PluginHelper::importPlugin('blog', 'finder');
        } elseif ($option === 'com_privacy') {
            PluginHelper::importPlugin('blog', 'privacy');
        }
    }
}
