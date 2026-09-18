<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_academy
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Academy\Site\Model;

use Joomla\CMS\Factory;
use Joomla\Component\Academy\Administrator\Extension\AcademyComponent;
use Joomla\Component\Academy\Site\Helper\QueryHelper;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Frontpage Component Model
 *
 * @since  1.5
 */
class FeaturedModel extends PostsModel
{
    /**
     * Model context string.
     *
     * @var     string
     */
    public $_context = 'com_academy.frontpage';

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   The field to order on.
     * @param   string  $direction  The direction to order on.
     *
     * @return  void
     *
     * @since   1.6
     */
    protected function populateState($ordering = null, $direction = null)
    {
        parent::populateState($ordering, $direction);

        $app   = Factory::getApplication();
        $input = $app->getInput();
        $user  = $app->getIdentity();

        // List state information
        $limitstart = $input->getUint('limitstart', 0);
        $this->setState('list.start', $limitstart);

        $params = $this->state->get('params');

        if ($menu = $app->getMenu()->getActive()) {
            $menuParams = $menu->getParams();
        } else {
            $menuParams = new Registry();
        }

        $mergedParams = clone $menuParams;
        $mergedParams->merge($params);

        $this->setState('params', $mergedParams);

        $limit = \Joomla\Component\Academy\Site\Helper\ListingSettingsHelper::count($params) + (\Joomla\Component\Academy\Site\Helper\CompactPostsHelper::visible($params) && $params->get('compact_selection', 'next') === 'next' ? $params->get('num_links') : 0);
        $this->setState('list.limit', $limit);
        $this->setState('list.links', $params->get('num_links'));

        $this->setState('filter.frontpage', true);

        if ((!$user->authorise('core.edit.state', 'com_academy')) && (!$user->authorise('core.edit', 'com_academy'))) {
            // Filter on published for those who do not have edit or edit.state rights.
            $this->setState('filter.published', AcademyComponent::CONDITION_PUBLISHED);
        } else {
            $this->setState('filter.published', [AcademyComponent::CONDITION_UNPUBLISHED, AcademyComponent::CONDITION_PUBLISHED]);
        }

        // Process show_noauth parameter
        if (!$params->get('show_noauth')) {
            $this->setState('filter.access', true);
        } else {
            $this->setState('filter.access', false);
        }

        // Check for category selection
        if ($params->get('featured_categories') && implode(',', $params->get('featured_categories'))) {
            $featuredCategories = $params->get('featured_categories');
            $this->setState('filter.frontpage.categories', $featuredCategories);
        }

        $postOrderby   = $params->get('orderby_sec', 'rdate');
        $postOrderDate = $params->get('order_date');
        $categoryOrderby  = $params->def('orderby_pri', '');

        $secondary = QueryHelper::orderbySecondary($postOrderby, $postOrderDate, $this->getDatabase());
        $primary   = QueryHelper::orderbyPrimary($categoryOrderby);

        $this->setState('list.ordering', $primary . $secondary . ', a.created DESC');
        $this->setState('list.direction', '');
    }

    /**
     * Method to get a list of posts.
     *
     * @return  mixed  An array of objects on success, false on failure.
     */
    public function getItems()
    {
        $params = clone $this->getState('params');
        $limit  = \Joomla\Component\Academy\Site\Helper\ListingSettingsHelper::count($params) + (\Joomla\Component\Academy\Site\Helper\CompactPostsHelper::visible($params) && $params->get('compact_selection', 'next') === 'next' ? $params->get('num_links') : 0);

        if ($limit > 0) {
            $this->setState('list.limit', $limit);

            return parent::getItems();
        }

        return [];
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= $this->getState('filter.frontpage');

        return parent::getStoreId($id);
    }

    /**
     * Get the list of items.
     *
     * @return  QueryInterface
     */
    protected function getListQuery()
    {
        // Create a new query object.
        $query = parent::getListQuery();

        // Filter by categories
        $featuredCategories = $this->getState('filter.frontpage.categories');

        if (\is_array($featuredCategories) && !\in_array('', $featuredCategories)) {
            $query->where('a.catid IN (' . implode(',', ArrayHelper::toInteger($featuredCategories)) . ')');
        }

        return $query;
    }
}
