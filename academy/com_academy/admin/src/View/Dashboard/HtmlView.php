<?php

namespace Joomla\Component\Academy\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public array $stats = [];
    public array $recent = [];
    public ?object $mailTask = null;

    public function display($tpl = null): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        foreach (['posts' => null, 'published' => 1, 'unpublished' => 0, 'pending' => -3, 'archived' => 2, 'trashed' => -2] as $key => $state) {
            $query = $db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__academy'));
            if ($state !== null) {
                $query->where($db->quoteName('state') . ' = ' . (int) $state);
            }
            $this->stats[$key] = (int) $db->setQuery($query)->loadResult();
        }
        $this->stats['featured'] = (int) $db->setQuery($db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__academy_frontpage')))->loadResult();
        $this->stats['categories'] = (int) $db->setQuery($db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__academy_categories')))->loadResult();
        $this->stats['tags'] = (int) $db->setQuery($db->createQuery()->select('COUNT(DISTINCT tag_id)')->from($db->quoteName('#__academy_tag_map'))->where($db->quoteName('type_alias') . " = 'com_academy.post'"))->loadResult();
        $this->stats['subscribers'] = (int) $db->setQuery($db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__academy_subscribers'))->where($db->quoteName('state') . ' = 1'))->loadResult();
        $this->stats['mail_queued'] = (int) $db->setQuery($db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__academy_mail_queue'))->where($db->quoteName('state') . ' = 0'))->loadResult();
        $this->stats['mail_failed'] = (int) $db->setQuery($db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__academy_mail_queue'))->where($db->quoteName('state') . ' = -1'))->loadResult();
        $this->stats['oldest_queued'] = $db->setQuery($db->createQuery()->select('MIN(c.created)')->from($db->quoteName('#__academy_mail_queue', 'q'))->join('INNER', $db->quoteName('#__academy_campaigns', 'c') . ' ON c.id=q.campaign_id')->where('q.state=0')->where('c.state=0'))->loadResult();
        $this->mailTask = $db->setQuery($db->createQuery()->select(['id','title','state','last_exit_code','last_execution','next_execution','times_executed','times_failed'])->from($db->quoteName('#__scheduler_tasks'))->where($db->quoteName('type') . " = 'academy.mailqueue'"))->loadObject();
        $this->recent = $db->setQuery($db->createQuery()->select($db->quoteName(['id','title','state','created']))->from($db->quoteName('#__academy'))->order($db->quoteName('created') . ' DESC'), 0, 8)->loadObjectList();
        ToolbarHelper::title('Academy Dashboard', 'home');
        if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_academy')) {
            ToolbarHelper::preferences('com_academy');
        }
        parent::display($tpl);
    }
}
