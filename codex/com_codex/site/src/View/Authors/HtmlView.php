<?php

namespace Joomla\Component\Codex\Site\View\Authors;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public ?Registry $params = null;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $this->params = $app->getParams();

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $levels = array_map('intval', $app->getIdentity()->getAuthorisedViewLevels());
        $now = Factory::getDate()->toSql();

        $query = $db->createQuery()
            ->select(['u.id', 'u.name', 'COUNT(p.id) AS post_count'])
            ->from('#__users AS u')
            ->join('INNER', '#__codex AS p ON p.created_by = u.id')
            ->where('u.block = 0')
            ->where('p.state = 1')
            ->whereIn('p.access', $levels)
            ->where('(p.publish_up IS NULL OR p.publish_up <= ' . $db->quote($now) . ')')
            ->where('(p.publish_down IS NULL OR p.publish_down >= ' . $db->quote($now) . ')')
            ->group(['u.id', 'u.name'])
            ->order('u.name');
        $this->items = $db->setQuery($query)->loadObjectList() ?: [];

        $this->getDocument()->setTitle('Authors');
        parent::display($tpl);
    }
}
