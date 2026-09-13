<?php

defined('_JEXEC') or die;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Plugin\Task\AcademyMailqueue\Extension\MailQueue;

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(PluginInterface::class, $container->lazy(MailQueue::class, function () {
            $plugin = new MailQueue((array)PluginHelper::getPlugin('task', 'academy_mailqueue'));
            $plugin->setApplication(Factory::getApplication());
            return $plugin;
        }));
    }
};
