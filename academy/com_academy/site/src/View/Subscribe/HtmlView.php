<?php

namespace Joomla\Component\Academy\Site\View\Subscribe;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public $params;

    public function display($tpl = null): void
    {
        $this->params = ComponentHelper::getParams('com_academy');

        parent::display($tpl);
    }
}
