<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_blog
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Blog\Site\View\Post;

use Joomla\CMS\Event\Content;
use Joomla\CMS\Factory;
use Joomla\Component\Blog\Administrator\Helper\TagsHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Blog\Site\Helper\AssociationHelper;
use Joomla\Component\Blog\Site\Helper\RouteHelper;
use Joomla\Component\Blog\Site\Model\PostModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * HTML Post View class for the Content component
 *
 * @since  1.5
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The post object
     *
     * @var  \stdClass
     */
    protected $item;

    /**
     * The page parameters
     *
     * @var    \Joomla\Registry\Registry|null
     *
     * @since  4.0.0
     */
    protected $params = null;

    /**
     * Should the print button be displayed or not?
     *
     * @var   boolean
     */
    protected $print = false;

    /**
     * The model state
     *
     * @var   \Joomla\Registry\Registry
     */
    protected $state;

    /**
     * The user object
     *
     * @var   \Joomla\CMS\User\User|null
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
     * The flag to mark if the active menu item is linked to the being displayed post
     *
     * @var boolean
     */
    protected $menuItemMatchPost = false;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        if ($this->getLayout() == 'pagebreak') {
            parent::display($tpl);

            return;
        }

        $app  = Factory::getApplication();
        $user = $this->getCurrentUser();

        /** @var PostModel $model */
        $model       = $this->getModel();
        $this->item  = $model->getItem();
        $this->print = $app->getInput()->getBool('print', false);
        $this->state = $model->getState();
        $this->user  = $user;

        // Check for errors.
        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // Create a shortcut for $item.
        $item            = $this->item;
        $item->tagLayout = new FileLayout('posttags', JPATH_COMPONENT . '/layouts');

        // Add router helpers.
        $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;

        // No link for ROOT category
        if ($item->parent_alias === 'root') {
            $item->parent_id = null;
        }

        // @todo Change based on shownoauth
        $item->readmore_link = Route::_(RouteHelper::getPostRoute($item->slug, $item->catid, $item->language));

        // Merge post params. If this is single-post view, menu params override post params
        // Otherwise, post params override menu item params
        $this->params = $this->state->get('params');
        $active       = $app->getMenu()->getActive();
        $temp         = clone $this->params;

        // Check to see which parameters should take priority. If the active menu item link to the current post, then
        // the menu item params take priority
        if (
            $active
            && $active->component == 'com_blog'
            && isset($active->query['view'], $active->query['id'])
            && $active->query['view'] == 'post'
            && $active->query['id'] == $item->id
        ) {
            $this->menuItemMatchPost = true;

            // Menu overrides must be merged before resolving the selected post style.
            $item->params->merge($temp);

            // Load layout from active query (in case it is an alternative menu item)
            if (isset($active->query['layout'])) {
                $this->setLayout($active->query['layout']);
            } elseif ($layout = $item->params->get('post_layout')) {
                // Check for alternative layout of post
                $this->setLayout($layout);
            }

        } else {
            // The active menu item is not linked to this post, so the post params take priority here
            // Merge the menu item params with the post params so that the post params take priority
            $temp->merge($item->params);
            $item->params = $temp;

            // Check for alternative layouts (since we are not in a single-post menu item)
            // Single-post menu item layout takes priority over alt layout for an post
            if ($layout = $item->params->get('post_layout')) {
                $this->setLayout($layout);
            }
        }

        // Allow a Wiki preview without changing the saved post layout.
        if ($app->getInput()->getCmd('layout') === 'wiki') {
            $this->setLayout('wiki');
        }

        $offset = (int) $this->state->get('list.offset');

        // Check the view access to the post (the model has already computed the values).
        if (!$item->params->get('access-view') && ($item->params->get('show_noauth', '0') == '0')) {
            $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->setHeader('status', 403, true);

            return;
        }

        /**
         * Check for no 'access-view' and empty body,
         * - Redirect guest users to login
         * - Deny access to logged users with 403 code
         * NOTE: we do not recheck for no access-view + show_noauth disabled ... since it was checked above
         */
        if (!$item->params->get('access-view') && !\strlen($item->body)) {
            if ($this->user->guest) {
                $return                = base64_encode(Uri::getInstance());
                $login_url_with_return = Route::_('index.php?option=com_users&view=login&return=' . $return);
                $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
                $app->redirect($login_url_with_return, 403);
            } else {
                $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
                $app->setHeader('status', 403, true);

                return;
            }
        }

        /**
         * NOTE: The following code (usually) sets the text to contain the body, but it is the
         * responsibility of the layout to check 'access-view' and only use "summary" for guests
         */
        if ($item->params->get('show_intro', '1') == '1') {
            $item->text = $item->summary . ' ' . $item->body;
        } elseif ($item->body) {
            $item->text = $item->body;
        } else {
            $item->text = $item->summary;
        }

        $item->tags = new TagsHelper();
        $item->tags->getItemTags('com_blog.post', $this->item->id);

        if (Associations::isEnabled() && $item->params->get('show_associations')) {
            $item->associations = AssociationHelper::displayAssociations($item->id);
        }

        $dispatcher = $this->getDispatcher();

        // Process the content plugins.
        PluginHelper::importPlugin('blog', null, true, $dispatcher);

        $contentEventArguments = [
            'context' => 'com_blog.post',
            'subject' => $item,
            'params'  => $item->params,
            'page'    => $offset,
        ];

        $dispatcher->dispatch('onContentPrepare', new Content\ContentPrepareEvent('onContentPrepare', $contentEventArguments));

        // Extra content from events
        $item->event   = new \stdClass();
        $contentEvents = [
            'afterDisplayTitle'    => new Content\AfterTitleEvent('onContentAfterTitle', $contentEventArguments),
            'beforeDisplayContent' => new Content\BeforeDisplayEvent('onContentBeforeDisplay', $contentEventArguments),
            'afterDisplayContent'  => new Content\AfterDisplayEvent('onContentAfterDisplay', $contentEventArguments),
        ];

        foreach ($contentEvents as $resultKey => $event) {
            $results = $dispatcher->dispatch($event->getName(), $event)->getArgument('result', []);

            $item->event->{$resultKey} = $results ? trim(implode("\n", $results)) : '';
        }

        // Escape strings for HTML output
        $this->pageclass_sfx = htmlspecialchars($this->item->params->get('pageclass_sfx', ''));

        $this->_prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document.
     *
     * @return  void
     */
    protected function _prepareDocument()
    {
        $app     = Factory::getApplication();
        $pathway = $app->getPathway();

        /**
         * Because the application sets a default page title,
         * we need to get it from the menu item itself
         */
        $menu = $app->getMenu()->getActive();

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', Text::_('JGLOBAL_POSTS'));
        }

        // If the menu item is not linked to this post
        if (!$this->menuItemMatchPost) {
            // If a browser page title is defined, use that, then fall back to the post title if set, then fall back to the page_title option
            $title = $this->item->params->get('post_page_title', $this->item->title);

            // Get ID of the category from active menu item
            if (
                $menu && $menu->component == 'com_blog' && isset($menu->query['view'])
                && \in_array($menu->query['view'], ['categories', 'category'])
            ) {
                $id = $menu->query['id'];
            } else {
                $id = 0;
            }

            $path     = [['title' => $this->item->title, 'link' => '']];
            $category = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class)->setQuery('SELECT id,title,language FROM #__blog_categories WHERE id=' . (int) $this->item->catid)->loadObject();

            if ($category !== null && $category->id != $id) {
                $path[]   = ['title' => $category->title, 'link' => RouteHelper::getCategoryRoute($category->id, $category->language)];
            }

            $path = array_reverse($path);

            foreach ($path as $item) {
                $pathway->addItem($item['title'], $item['link']);
            }
        } else {
            /**
             * This case the menu item links directly to the post, browser will be determined by following
             * order:
             * 1. Browser page title set from menu item itself
             * 2. Browser page title set for the post
             * 3. Post title
             */
            $menuItemParams = $menu->getParams();
            $title          = $menuItemParams->get(
                'page_title',
                $this->item->params->get('post_page_title', $this->item->title)
            );
        }

        $this->setDocumentTitle($title);

        if ($this->item->metadesc) {
            $this->getDocument()->setDescription($this->item->metadesc);
        } elseif ($this->params->get('menu-meta_description')) {
            $this->getDocument()->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('robots')) {
            $this->getDocument()->setMetaData('robots', $this->params->get('robots'));
        }

        if ($app->get('MetaAuthor') == '1') {
            $author = $this->item->created_by_alias ?: $this->item->author;
            $this->getDocument()->setMetaData('author', $author);
        }

        $mdata = $this->item->metadata->toArray();

        foreach ($mdata as $k => $v) {
            if ($v) {
                $this->getDocument()->setMetaData($k, $v);
            }
        }

        // If there is a pagebreak heading or title, add it to the page title
        if (!empty($this->item->page_title)) {
            $this->item->title .= ' - ' . $this->item->page_title;
            $this->setDocumentTitle(
                $this->item->page_title . ' - ' . Text::sprintf('PLG_CONTENT_PAGEBREAK_PAGE_NUM', $this->state->get('list.offset') + 1)
            );
        }

        if ($this->print) {
            $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        }
    }
}
