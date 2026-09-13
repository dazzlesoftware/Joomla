<?php

namespace Joomla\Component\Academy\Administrator\View\Newsletter;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public array $templates = [];
    public array $campaigns = [];
    public int $pending = 0;
    public int $failed = 0;
    public function display($tpl = null): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->templates = $db->setQuery($db->createQuery()->select('*')->from('#__academy_email_templates')->order('title'))->loadObjectList();
        $q = $db->createQuery()->select(['c.id','c.subject','c.created','COUNT(q.id) AS recipients','COALESCE(SUM(q.state=1),0) AS sent','COALESCE(SUM(q.state=0),0) AS pending','COALESCE(SUM(q.state=-1),0) AS failed','COALESCE(SUM(q.state=2),0) AS skipped'])->from('#__academy_campaigns AS c')->join('LEFT', '#__academy_mail_queue AS q ON q.campaign_id=c.id')->group('c.id')->order('c.created DESC');
        $this->campaigns = $db->setQuery($q, 0, 25)->loadObjectList();
        $this->pending = (int)$db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__academy_mail_queue')->where('state=0'))->loadResult();
        $this->failed = (int)$db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__academy_mail_queue')->where('state=-1'))->loadResult();
        ToolbarHelper::title('Academy Email Campaigns', 'envelope');
        parent::display($tpl);
    }
}
