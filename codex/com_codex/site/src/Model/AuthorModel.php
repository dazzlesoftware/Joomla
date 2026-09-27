<?php
namespace Joomla\Component\Codex\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Component\Codex\Site\Helper\AuthorListingHelper;
use Joomla\Component\Codex\Site\Helper\ListingSettingsHelper;
use Joomla\Component\Codex\Site\Helper\QueryHelper;

/** Published, accessible posts belonging to one author, with database pagination. */
final class AuthorModel extends PostsModel
{
    protected function populateState($ordering = 'ordering', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
        $app = Factory::getApplication();
        $authorId = $app->getInput()->getInt('id');
        $params = AuthorListingHelper::settings($app->getParams('com_codex'), $authorId);
        $this->setState('params', $params);
        $this->setState('filter.author_id', $authorId);
        $this->setState('filter.published', 1);
        $this->setState('filter.access', true);
        $this->setState('list.limit', ListingSettingsHelper::count($params));
        $this->setState('list.links', 0);
        $this->setState('list.ordering', QueryHelper::orderbyPrimary($params->get('orderby_pri'))
            . QueryHelper::orderbySecondary($params->get('orderby_sec'), $params->get('order_date'), $this->getDatabase()) . ', a.id DESC');
        $this->setState('list.direction', '');
    }

    protected function getListQuery()
    {
        $query = parent::getListQuery();
        // Do not allow a missing id (zero) to become an unrestricted list.
        $query->where('a.created_by = ' . (int) $this->getState('filter.author_id'));
        if ($this->getState('filter.language')) {
            $query->whereIn('c.language', [Factory::getApplication()->getLanguage()->getTag(), '*'], \Joomla\Database\ParameterType::STRING);
        }
        return $query;
    }
}
