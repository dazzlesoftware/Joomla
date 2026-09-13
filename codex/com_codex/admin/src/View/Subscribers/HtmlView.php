<?php

namespace Joomla\Component\Codex\Administrator\View\Subscribers;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public Pagination $pagination;
    public string $search = '';
    public string $status = '';

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->search = trim($app->getInput()->getString('filter_search'));
        $this->status = (string) $app->getInput()->getCmd('filter_status', '');
        $limit = max(5, min(100, $app->getInput()->getInt('limit', 25)));
        $start = max(0, $app->getInput()->getInt('limitstart'));
        $where = [];
        if ($this->search !== '') {
            $needle = $db->quote('%' . $db->escape($this->search, true) . '%', false);
            $where[] = '(name LIKE ' . $needle . ' OR email LIKE ' . $needle . ')';
        }
        if ($this->status === 'active') {
            $where[] = 'state=1';
        }
        if ($this->status === 'pending') {
            $where[] = 'state=0 AND confirmed IS NULL';
        }
        if ($this->status === 'disabled') {
            $where[] = 'state=0 AND confirmed IS NOT NULL';
        }
        $count = $db->createQuery()->select('COUNT(*)')->from('#__codex_subscribers');
        $query = $db->createQuery()->select('*')->from('#__codex_subscribers')->order('created DESC');
        foreach ($where as $condition) {
            $count->where($condition);
            $query->where($condition);
        }
        $total = (int) $db->setQuery($count)->loadResult();
        $this->items = $db->setQuery($query, $start, $limit)->loadObjectList();
        $this->pagination = new Pagination($total, $start, $limit);
        ToolbarHelper::title('Codex Subscribers', 'users');
        parent::display($tpl);
    }
}
