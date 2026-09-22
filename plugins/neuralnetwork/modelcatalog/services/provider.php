<?php
defined('_JEXEC') or die;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Plugin\Neuralnetwork\Modelcatalog\Extension\Modelcatalog;
return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, $container->lazy(Modelcatalog::class, function (Container $container) {
            $plugin = new Modelcatalog((array) PluginHelper::getPlugin('neuralnetwork', 'modelcatalog'));
            $plugin->setApplication(Factory::getApplication());
            return $plugin;
        }));
    }
};
