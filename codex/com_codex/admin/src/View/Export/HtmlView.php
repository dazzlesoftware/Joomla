<?php

namespace Joomla\Component\Codex\Administrator\View\Export;

defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public function display($tpl = null): void
    {
        ToolbarHelper::title('Export Posts', 'download');
        parent::display($tpl);
    }
}
