<?php
namespace Joomla\Component\Codex\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Component\Codex\Site\Helper\AuthorListingHelper;
use Joomla\Component\Codex\Site\Helper\ListingSettingsHelper;
use Joomla\Component\Codex\Site\Helper\QueryHelper;

/** Filtered author posts, with database pagination. */
final class AuthorModel extends PostsModel
{
    protected function populateState($ordering = 'ordering', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
        $app = Factory::getApplication();
        $authorId = $app->getInput()->getCmd('view') === 'post'
            ? (int) ($app->getMenu()->getActive()->query['id'] ?? 0)
            : $app->getInput()->getInt('id', 0);
        $params = AuthorListingHelper::settings($app->getParams('com_codex'), $authorId);
        $this->setState('params', $params);
        $this->setState('filter.author_id', null);
        $this->setState('filter.published', 1);
        $this->setState('filter.access', !in_array($params->get('show_noauth', 0), [1, '1', 'use_post'], true));
        $this->setState('list.limit', ListingSettingsHelper::count($params));
        $this->setState('list.links', 0);
        $this->setState('list.ordering', QueryHelper::orderbyPrimary($params->get('orderby_pri'))
            . QueryHelper::orderbySecondary($params->get('orderby_sec'), $params->get('order_date'), $this->getDatabase()) . ', a.id DESC');
        $this->setState('list.direction', '');
    }

    protected function getListQuery()
    {
        $query = parent::getListQuery();
        $query->where('ua.block = 0');
        if ($this->getState('filter.language')) {
            $query->whereIn('c.language', [Factory::getApplication()->getLanguage()->getTag(), '*'], \Joomla\Database\ParameterType::STRING);
        }
        return $query;
    }
}
