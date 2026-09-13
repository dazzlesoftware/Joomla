<?php

namespace Joomla\Component\Codex\Administrator\View\Autopostlogs;

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
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_codex')) {
            throw new \RuntimeException('Not authorised.', 403);
        }$db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->items = $db->setQuery($db->createQuery()->select('l.*,p.title')->from('#__codex_autopost_logs AS l')->join('LEFT', '#__codex AS p ON p.id=l.post_id')->order('l.id DESC'), 0, 200)->loadObjectList();
        ToolbarHelper::title('Autopost Logs', 'list');
        parent::display($tpl);
    }
}
