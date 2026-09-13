<?php

namespace Joomla\Component\Codex\Administrator\View\Autopost;

defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public string $provider = 'facebook';
    public $params;
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.options', 'com_codex')) {
            throw new \RuntimeException('Not authorised.', 403);
        }$this->provider = $app->getInput()->getCmd('provider', 'facebook');
        if (!in_array($this->provider, ['facebook','twitter','linkedin'], true)) {
            $this->provider = 'facebook';
        }$this->params = ComponentHelper::getParams('com_codex');
        ToolbarHelper::title(ucfirst($this->provider).' Autoposting', 'share-alt');
        ToolbarHelper::apply('autopost.save');
        ToolbarHelper::save('autopost.save');
        parent::display($tpl);
    }
}
