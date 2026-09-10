<?php

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Academy\Blocks\Extension\Blocks;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, static function (Container $container) {
            $plugin = new Blocks((array) PluginHelper::getPlugin('academy', 'blocks'), $container->get(DispatcherInterface::class));
            $plugin->setApplication(Factory::getApplication());
            return $plugin;
        });
    }
};
