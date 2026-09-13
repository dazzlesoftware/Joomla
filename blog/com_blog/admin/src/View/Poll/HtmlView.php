<?php

namespace Joomla\Component\Blog\Administrator\View\Poll;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public ?object $item = null;
    public array $options = [];
    public function display($tpl = null): void
    {
        $id = Factory::getApplication()->getInput()->getInt('id');
        if ($id) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $this->item = $db->setQuery($db->createQuery()->select('*')->from('#__blog_polls')->where('id='.$id))->loadObject();
            $this->options = $db->setQuery($db->createQuery()->select('*')->from('#__blog_poll_options')->where('poll_id='.$id)->order('ordering'))->loadObjectList();
        }ToolbarHelper::title(($id ? 'Edit' : 'New').' Poll', 'question');
        ToolbarHelper::save('poll.save');
        ToolbarHelper::cancel('poll.cancel');
        parent::display($tpl);
    }
}
