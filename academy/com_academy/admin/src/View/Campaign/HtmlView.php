<?php

namespace Joomla\Component\Academy\Administrator\View\Campaign;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public ?object $item = null;
    public array $recipients = [];
    public function display($tpl = null): void
    {
        $id = Factory::getApplication()->getInput()->getInt('id');
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->item = $db->setQuery($db->createQuery()->select('*')->from('#__academy_campaigns')->where('id='.$id))->loadObject();
        if (!$this->item) {
            throw new \RuntimeException('Campaign not found.', 404);
        }$this->recipients = $db->setQuery($db->createQuery()->select('*')->from('#__academy_mail_queue')->where('campaign_id='.$id)->order('id'))->loadObjectList();
        ToolbarHelper::title('Campaign: '.$this->item->subject, 'envelope');
        ToolbarHelper::back('Email Campaigns', 'index.php?option=com_academy&view=newsletter');
        parent::display($tpl);
    }
}
