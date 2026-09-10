<?php
defined('_JEXEC') or die;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\CodexLoader\Extension\FamilyLoader;
return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, static function (Container $container) {
            $plugin = new FamilyLoader($container->get(DispatcherInterface::class), (array) PluginHelper::getPlugin('system', 'codexloader'));
            $plugin->setApplication(Factory::getApplication());
            return $plugin;
        });
    }
};
