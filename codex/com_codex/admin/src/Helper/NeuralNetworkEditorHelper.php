<?php
namespace Joomla\Component\Codex\Administrator\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
final class NeuralNetworkEditorHelper
{
    public static function load(): void
    {
        $app = Factory::getApplication(); $params = ComponentHelper::getParams('com_codex');
        if (!$params->get('ai_enabled', 0) || !$app->getIdentity()->authorise('ai.generate', 'com_codex')) { return; }
        $app->getDocument()->addScriptOptions('com_codex.ai', ['endpoint'=>Uri::base().'index.php?option=com_codex&format=json', 'token'=>Session::getFormToken(), 'images'=>(bool)$params->get('ai_images',0) && $app->getIdentity()->authorise('core.create','com_media')]);
        $app->getDocument()->getWebAssetManager()->registerAndUseScript('com_codex.ai', 'com_codex/neural-network-editor.js', ['version'=>'1.1.0'], ['type'=>'module'], ['editors']);
    }
}
