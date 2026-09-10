<?php

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Plugin\EditorsXtd\Video\Extension\Video;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(Video::class, function () {
                $plugin = new Video((array) PluginHelper::getPlugin('editors-xtd', 'video'));
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            })
        );
    }
};
