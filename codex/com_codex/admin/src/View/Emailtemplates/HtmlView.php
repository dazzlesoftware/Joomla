<?php

namespace Joomla\Component\Codex\Administrator\View\Emailtemplates;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public function display($tpl = null): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->items = $db->setQuery($db->createQuery()->select('*')->from('#__codex_email_templates')->order('title'))->loadObjectList();
        ToolbarHelper::title('Codex Email Templates', 'copy');
        parent::display($tpl);
    }
}
