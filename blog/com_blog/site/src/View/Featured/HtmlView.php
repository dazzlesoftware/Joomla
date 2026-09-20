<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_blog
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Blog\Site\View\Featured;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Blog\Site\Helper\ListExcerptHelper;
use Joomla\Component\Blog\Site\Model\FeaturedModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Frontpage View class
 *
 * @since  1.5
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The model state
     *
     * @var  \Joomla\Registry\Registry
     */
    protected $state = null;

    /**
     * The featured posts array
     *
     * @var  \stdClass[]
     */
    protected $items = null;

    /**
     * The pagination object.
     *
     * @var  \Joomla\CMS\Pagination\Pagination
     */
    protected $pagination = null;

    /**
     * The featured posts to be displayed as lead items.
     *
     * @var  \stdClass[]
     */
    protected $lead_items = [];

    /**
     * The featured posts to be displayed as intro items.
     *
     * @var  \stdClass[]
     */
    protected $intro_items = [];

    /**
     * The featured posts to be displayed as link items.
     *
     * @var  \stdClass[]
     */
    protected $link_items = [];

    /**
     * The user object
     *
     * @var \Joomla\CMS\User\User|null
     */
    protected $user = null;

    /**
     * The page class suffix
     *
     * @var    string
     *
     * @since  4.0.0
     */
    protected $pageclass_sfx = '';

    /**
     * The page parameters
     *
     * @var    \Joomla\Registry\Registry|null
     *
     * @since  4.0.0
     */
    protected $params = null;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        $user = $this->getCurrentUser();

        /** @var FeaturedModel $model */
        $model      = $this->getModel();
        $state      = $model->getState();
        $items      = $model->getItems();
        $pagination = $model->getPagination();

        // Flag indicates to not add limitstart=0 to URL
        $pagination->hideEmptyLimitstart = true;

        // Check for errors.
        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        /** @var \Joomla\Registry\Registry $params */
        $params = $state->get('params');

        // PREPARE THE DATA

        // Get the metrics for the structural page layout.
        $numLeading = 0;
        $numIntro = \Joomla\Component\Blog\Site\Helper\ListingSettingsHelper::count($params);

        PluginHelper::importPlugin('content');
        PluginHelper::importPlugin('blog');

        // Compute the post slugs and prepare summary (runs content plugins).
        foreach ($items as &$item) {
            $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;

            // No link for ROOT category
            if ($item->parent_alias === 'root') {
                $item->parent_id = null;
            }

            $item->event = new \stdClass();

            // Decide the excerpt from the raw stored summary, before content
            // plugins expand any shortcodes (accordions, embeds, etc.) into
            // their full rendered markup - otherwise a short shortcode that
            // expands into a large widget fools the length check into
            // skipping truncation entirely, dumping the whole widget into
            // the listing.
            $item->text = ListExcerptHelper::render($item, $item->params);

            Factory::getApplication()->triggerEvent('onContentPrepare', ['com_blog.featured', &$item, &$item->params, 0]);

            // Old plugins: Use processed text as summary
            $item->summary = $item->text;

            $results                        = Factory::getApplication()->triggerEvent('onContentAfterTitle', ['com_blog.featured', &$item, &$item->params, 0]);
            $item->event->afterDisplayTitle = trim(implode("\n", $results));

            $results                           = Factory::getApplication()->triggerEvent('onContentBeforeDisplay', ['com_blog.featured', &$item, &$item->params, 0]);
            $item->event->beforeDisplayContent = trim(implode("\n", $results));

            $results                          = Factory::getApplication()->triggerEvent('onContentAfterDisplay', ['com_blog.featured', &$item, &$item->params, 0]);
            $item->event->afterDisplayContent = trim(implode("\n", $results));
        }

        // Preprocess the breakdown of leading, intro and linked posts.
        // This makes it much easier for the designer to just integrate the arrays.
        $max = \count($items);

        // The first group is the leading posts.
        $limit = $numLeading;

        for ($i = 0; $i < $limit && $i < $max; $i++) {
            $this->lead_items[$i] = &$items[$i];
        }

        // The second group is the intro posts.
        $limit = $numLeading + $numIntro;

        // Order posts across, then down (or single column mode)
        for ($i = $numLeading; $i < $limit && $i < $max; $i++) {
            $this->intro_items[$i] = &$items[$i];
        }

        // The remainder are the links.
        for ($i = $numLeading + $numIntro; $i < $max; $i++) {
            $this->link_items[$i] = &$items[$i];
        }

        // Escape strings for HTML output
        $this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx', ''));

        $this->params     = &$params;
        $this->items      = &$items;
        $this->pagination = &$pagination;
        $this->user       = &$user;

        $this->_prepareDocument();

        \Joomla\Component\Blog\Site\Helper\ListingFilterHelper::canonical($this->getDocument(), $this->params);
        parent::display($tpl);
    }

    /**
     * Prepares the document.
     *
     * @return  void
     */
    protected function _prepareDocument()
    {
        // Because the application sets a default page title,
        // we need to get it from the menu item itself
        $menu = Factory::getApplication()->getMenu()->getActive();

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', Text::_('JGLOBAL_POSTS'));
        }

        $this->setDocumentTitle($this->params->get('page_title', ''));

        if ($this->params->get('menu-meta_description')) {
            $this->getDocument()->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('robots')) {
            $this->getDocument()->setMetaData('robots', $this->params->get('robots'));
        }


    }
}
