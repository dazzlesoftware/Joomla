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
    public array $compactItems = [];
    public ?\Joomla\CMS\Pagination\Pagination $pagination = null;
    public ?Registry $params = null;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $this->params = clone \Joomla\CMS\Component\ComponentHelper::getParams('com_academy');
        foreach ($app->getParams('com_academy')->toArray() as $key => $value) {
            if ($value !== '' && $value !== null) { $this->params->set($key, $value); }
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $levels = array_map('intval', $app->getIdentity()->getAuthorisedViewLevels());
        $now = Factory::getDate()->toSql();

        $query = $db->createQuery()
            ->select(['u.id', 'u.name', 'u.email', 'COUNT(p.id) AS post_count'])
            ->from('#__users AS u')
            ->join('INNER', '#__academy AS p ON p.created_by = u.id')
            ->join('INNER', '#__academy_categories AS c ON c.id=p.catid')
            ->where('c.published = 1')->whereIn('c.access', $levels)
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
        $filters = clone $this->params;
        $filters->set('listing_include_featured', 1);
        $filters->set('listing_pin_featured', 0);
        \Joomla\Component\Academy\Site\Helper\ListingFilterHelper::apply($query, $filters);
        \Joomla\Component\Academy\Site\Helper\ListingFilterHelper::canonical($this->getDocument(), $this->params);
        $countQuery = clone $query;
        $countQuery->clear('order')->clear('group')->clear('select')->select('COUNT(DISTINCT u.id)');
        $total = (int) $db->setQuery($countQuery)->loadResult();
        $limit = max(1, min(100, (int) $this->params->get('authors_per_page', 12)));
        $start = max(0, $app->getInput()->getInt('limitstart', 0));
        $start = $total ? min(intdiv($start, $limit) * $limit, intdiv($total - 1, $limit) * $limit) : 0;
        $this->pagination = new \Joomla\CMS\Pagination\Pagination($total, $start, $limit);
        $this->items = $db->setQuery($query, $start, $limit)->loadObjectList() ?: [];

        // The directory contains authors rather than a primary post list.
        // Next/related therefore select recent posts by the authors on this page.
        $compactParams = clone $this->params;
        $compactParams->set('listing_authors', array_map(static fn($author) => (int) $author->id, $this->items));
        $compactParams->set('listing_include_featured', 1);
        $compactParams->set('listing_pin_featured', 0);
        if (in_array($compactParams->get('compact_selection', 'next'), ['next', 'related'], true)) {
            $compactParams->set('compact_selection', 'latest');
        }
        $this->compactItems = $this->items
            ? \Joomla\Component\Academy\Site\Helper\CompactPostsHelper::select([], [], $compactParams) : [];

        $this->getDocument()->setTitle(\Joomla\CMS\Language\Text::_('COM_ACADEMY_AUTHORS_HEADING'));
        parent::display($tpl);
    }
}
