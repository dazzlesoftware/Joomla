<?php
namespace Joomla\Component\Blog\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Component\Blog\Site\Helper\TagPostsHelper;
use Joomla\Component\Blog\Site\Helper\ListingSettingsHelper;
use Joomla\Component\Blog\Site\Helper\QueryHelper;

/** Published, accessible posts belonging to one author, with database pagination. */
final class TagsModel extends PostsModel
{
    protected function populateState($ordering = 'ordering', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
        $app = Factory::getApplication();
        $tagId = $app->getInput()->getInt('tag_id');
        $params = TagPostsHelper::settings(\Joomla\Component\Blog\Site\Helper\TagDirectoryHelper::params(), $tagId);
        $this->setState('params', $params);
        $this->setState('filter.tag_id', $tagId);
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
        if (!(int) $this->getState('filter.tag_id')) { $query->where('1=0'); }
        if ($this->getState('filter.language')) {
            $query->whereIn('c.language', [Factory::getApplication()->getLanguage()->getTag(), '*'], \Joomla\Database\ParameterType::STRING);
        }
        return $query;
    }
}
