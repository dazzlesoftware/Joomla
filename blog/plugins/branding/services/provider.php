<?php
defined('_JEXEC') or die;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\BlogBranding\Extension\Branding;
return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, static function (Container $container) {
            $plugin = new Branding($container->get(DispatcherInterface::class), (array) PluginHelper::getPlugin('system', 'blogbranding'));
            $plugin->setApplication(Factory::getApplication());
            return $plugin;
        });
    }
};
