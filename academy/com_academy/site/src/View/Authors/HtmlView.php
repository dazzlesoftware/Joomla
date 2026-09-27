<?php

namespace Joomla\Component\Academy\Site\View\Authors;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public ?\Joomla\CMS\Pagination\Pagination $pagination = null;
    public ?Registry $params = null;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $this->params = $app->getParams();

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $levels = array_map('intval', $app->getIdentity()->getAuthorisedViewLevels());
        $now = Factory::getDate()->toSql();

        $query = $db->createQuery()
            ->select(['u.id', 'u.name', 'u.email', 'COUNT(p.id) AS post_count'])
            ->from('#__users AS u')
            ->join('INNER', '#__academy AS p ON p.created_by = u.id')
            ->where('u.block = 0')
            ->where('p.state = 1')
            ->whereIn('p.access', $levels)
            ->where('(p.publish_up IS NULL OR p.publish_up <= ' . $db->quote($now) . ')')
            ->where('(p.publish_down IS NULL OR p.publish_down >= ' . $db->quote($now) . ')')
            ->group(['u.id', 'u.name', 'u.email'])
            ->order('u.name, u.id');
        if ($app->getLanguageFilter()) {
            $query->whereIn('p.language', ['*', $app->getLanguage()->getTag()], \Joomla\Database\ParameterType::STRING);
        }
        $countQuery = clone $query;
        $countQuery->clear('order')->clear('group')->clear('select')->select('COUNT(DISTINCT u.id)');
        $total = (int) $db->setQuery($countQuery)->loadResult();
        $limit = max(1, min(100, (int) $this->params->get('authors_per_page', 12)));
        $start = max(0, $app->getInput()->getInt('limitstart', 0));
        $start = $total ? min(intdiv($start, $limit) * $limit, intdiv($total - 1, $limit) * $limit) : 0;
        $this->pagination = new \Joomla\CMS\Pagination\Pagination($total, $start, $limit);
        $this->items = $db->setQuery($query, $start, $limit)->loadObjectList() ?: [];

        $this->getDocument()->setTitle(\Joomla\CMS\Language\Text::_('COM_ACADEMY_AUTHORS_HEADING'));
        parent::display($tpl);
    }
}
