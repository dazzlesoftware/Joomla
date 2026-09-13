<?php

namespace Joomla\Component\Blog\Administrator\View\Emailtemplate;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public ?object $item = null;
    public function display($tpl = null): void
    {
        $id = Factory::getApplication()->getInput()->getInt('id');
        if ($id) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $this->item = $db->setQuery($db->createQuery()->select('*')->from('#__blog_email_templates')->where('id='.$id))->loadObject();
        }ToolbarHelper::title(($id ? 'Edit' : 'New').' Email Template', 'copy');
        ToolbarHelper::save('emailtemplate.save');
        ToolbarHelper::cancel('emailtemplate.cancel');
        parent::display($tpl);
    }
}
