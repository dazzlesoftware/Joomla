<?php
namespace Joomla\Component\Academy\Administrator\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
final class NeuralNetworkEditorHelper
{
    public static function load(): void
    {
        $app = Factory::getApplication(); $params = ComponentHelper::getParams('com_academy');
        if (NeuralNetworkAccessHelper::settings($params, $app->getIdentity()) === null) { return; }
        $app->getDocument()->addScriptOptions('com_academy.ai', ['endpoint'=>Uri::base().'index.php?option=com_academy&format=json', 'token'=>Session::getFormToken(), 'images'=>NeuralNetworkAccessHelper::settings($params, $app->getIdentity(), true) !== null]);
        $app->getDocument()->getWebAssetManager()->registerAndUseScript('com_academy.ai', 'com_academy/neural-network-editor.js', ['version'=>'1.1.0'], ['type'=>'module'], ['editors']);
    }
}
