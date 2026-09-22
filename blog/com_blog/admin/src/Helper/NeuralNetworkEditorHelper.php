<?php
namespace Joomla\Component\Blog\Administrator\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
final class NeuralNetworkEditorHelper
{
    public static function load(): void
    {
        $app = Factory::getApplication(); $params = ComponentHelper::getParams('com_blog');
        if (!$params->get('ai_enabled', 0) || !$app->getIdentity()->authorise('ai.generate', 'com_blog')) { return; }
        $app->getDocument()->addScriptOptions('com_blog.ai', ['endpoint'=>Uri::base().'index.php?option=com_blog&format=json', 'token'=>Session::getFormToken(), 'images'=>(bool)$params->get('ai_images',0) && $app->getIdentity()->authorise('core.create','com_media')]);
        $app->getDocument()->getWebAssetManager()->registerAndUseScript('com_blog.ai', 'com_blog/neural-network-editor.js', ['version'=>'1.1.0'], ['type'=>'module'], ['editors']);
    }
}
