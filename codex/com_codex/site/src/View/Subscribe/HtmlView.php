<?php

namespace Joomla\Component\Codex\Site\View\Subscribe;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public $params;

    public function display($tpl = null): void
    {
        $this->params = ComponentHelper::getParams('com_codex');

        parent::display($tpl);
    }
}
