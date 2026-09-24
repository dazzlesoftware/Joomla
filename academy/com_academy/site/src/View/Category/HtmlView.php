<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_academy
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Academy\Site\View\Category;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Academy\Administrator\Helper\CategoriesHelper;
use Joomla\Component\Academy\Administrator\Helper\TagsHelper;
use Joomla\Component\Academy\Site\Helper\ListExcerptHelper;
use Joomla\Registry\Registry;

final class HtmlView extends BaseHtmlView
{
    public $category;
    public array $items = [];
    public array $subcategories = [];
    public $pagination;
    public $params;

    /** @var \stdClass[] Leading posts, for the Blog layout. */
    public array $lead_items = [];
    /** @var \stdClass[] Intro (grid) posts, for the Blog layout. */
    public array $intro_items = [];
    /** @var \stdClass[] Title-only linked posts, for the Blog layout. */
    public array $link_items = [];

    public function display($tpl = null): void
    {
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        $db   = CategoriesHelper::db();

        $this->params = clone $app->getParams();
        $id = $app->getInput()->getInt('id', 0);
        if (!$this->params->get('listing_tags') && $app->getInput()->get('filter_tag', [], 'array')) {
            $this->params->set('listing_tags', $app->getInput()->get('filter_tag', [], 'array'));
        }
        $selected = \Joomla\Component\Academy\Site\Helper\ListingFilterHelper::ids($this->params->get('listing_categories', []));
        $headerId = $id ?: (count($selected) === 1 ? $selected[0] : 0);
        $this->category = (object) ['id' => 0, 'title' => $this->params->get('page_heading', 'Posts'), 'description' => '', 'default_image' => '', 'language' => '*'];
        if ($headerId) {
            $categoryQuery = $db->createQuery()->select('*')->from('#__academy_categories')
                ->where('id=' . $headerId)->where('published=1')->whereIn('access', $user->getAuthorisedViewLevels());
            if ($app->getLanguageFilter()) {
                $categoryQuery->where('language IN (' . $db->quote('*') . ',' . $db->quote($app->getLanguage()->getTag()) . ')');
            }
            $this->category = $db->setQuery($categoryQuery)->loadObject();
            if (!$this->category) { throw new \RuntimeException('Category not found', 404); }
        }
        $q = $db->createQuery()->select('p.*, u.name AS author, c.title AS category_title, c.language AS category_language, c.default_image AS category_default_image')
            ->from('#__academy AS p')->join('LEFT', '#__users AS u ON u.id=p.created_by')
            ->join('INNER', '#__academy_categories AS c ON c.id=p.catid')
            ->where('p.state=1 AND c.published=1')->whereIn('p.access', $user->getAuthorisedViewLevels())->whereIn('c.access', $user->getAuthorisedViewLevels())
            ->where('(p.publish_up IS NULL OR p.publish_up<=UTC_TIMESTAMP())')
            ->where('(p.publish_down IS NULL OR p.publish_down>=UTC_TIMESTAMP())');
        \Joomla\Component\Academy\Site\Helper\ListingFilterHelper::apply($q, $this->params, 'p', $id);
        $q->order('COALESCE(p.publish_up,p.created) DESC, p.id DESC');
        if ($app->getLanguageFilter()) {
            foreach (['p.language', 'c.language'] as $field) {
                $q->where($field . ' IN (' . $db->quote('*') . ',' . $db->quote($app->getLanguage()->getTag()) . ')');
            }
        }
        $all = $db->setQuery($q)->loadObjectList() ?: [];

        foreach ($all as $item) {
            $item->params = new Registry($item->options ?? '{}');
            $item->slug = $item->id . ':' . $item->alias;
            $item->parent_id = null;
            $item->readmore = 0;
        }

        $this->params = clone $app->getParams();
        $requestedLayout = $app->getInput()->getCmd('layout', '');
        $layout = (string) $this->params->get('category_layout', '');
        // Alternative URLs remain valid; the Category Posts menu uses its style setting.
        if (in_array($requestedLayout, ['standard', 'learning', 'simple', 'nickel'], true)) {
            $layout = $requestedLayout;
        } elseif ($layout === '') {
            $layout = $requestedLayout !== '' && $requestedLayout !== 'category_posts' ? $requestedLayout : 'card';
        }
        // Joomla componentlayout stores component selections as _:card, etc.
        $layoutName = str_contains($layout, ':') ? substr($layout, strrpos($layout, ':') + 1) : $layout;
        if ($layoutName === 'blog') { $layout = $layoutName = 'card'; } // Old bookmarks.
        if (in_array($layoutName, ['default', 'standard', 'card', 'learning', 'simple', 'nickel'], true)) {
            $this->setLayout($layout);
        }
        // Blog pages must count exactly the items their leading/intro/link groups render.
        $limit = \Joomla\Component\Academy\Site\Helper\ListingSettingsHelper::count($this->params);
        if ($layoutName !== 'default') {
            $posts = \Joomla\Component\Academy\Site\Helper\ListingSettingsHelper::count($this->params);
            $links = \Joomla\Component\Academy\Site\Helper\CompactPostsHelper::visible($this->params) && $this->params->get('compact_selection', 'next') === 'next' ? max(0, (int) $this->params->get('num_links', 4)) : 0;
            $limit = $posts + $links;
        }
        $start = $app->getInput()->getUint('limitstart', 0);
        $this->pagination = new Pagination(count($all), $start, $limit);
        $this->items = array_slice($all, $this->pagination->limitstart, $limit);

        foreach ($this->items as $item) {
            $merged = clone $this->params;
            $merged->merge($item->params);
            $item->params = $merged;
        }

        $this->subcategories = \Joomla\Component\Academy\Site\Helper\SubcategoriesHelper::load((int) $this->category->id, $this->params);

        $this->enrichItems();
        $this->splitBlogGroups();

        $this->getDocument()->setTitle($this->category->title);

        \Joomla\Component\Academy\Site\Helper\ListingFilterHelper::canonical($this->getDocument(), $this->params);
        parent::display($tpl);
    }

    /**
     * Adds everything the item templates (card_item.php, default.php's excerpt,
     * postlist tags, etc.) expect beyond the raw post row: view access, tags,
     * and the onContent* plugin event output.
     */
    private function enrichItems(): void
    {
        if (!$this->items) {
            return;
        }

        $user = $this->getCurrentUser();

        $ids = array_map(static fn ($item) => (int) $item->id, $this->items);
        $tagsByItem = (new TagsHelper())->getMultipleItemTags('com_academy.post', $ids);

        $app = Factory::getApplication();
        PluginHelper::importPlugin('content');
        PluginHelper::importPlugin('academy');

        foreach ($this->items as $item) {
            // Every item here already passed the access/state filters above.
            $item->params->set('access-view', true);
            $item->params->set('access-edit', $user->authorise('core.edit', 'com_academy.post.' . $item->id));

            $item->tags = (object) ['itemTags' => $tagsByItem[(int) $item->id] ?? []];
            $item->event = new \stdClass();

            // Decide the excerpt from the raw stored summary, before content
            // plugins expand any shortcodes (accordions, embeds, etc.) into
            // their full rendered markup - otherwise a short shortcode that
            // expands into a large widget fools the length check into
            // skipping truncation entirely, dumping the whole widget into
            // the listing.
            $item->text = ListExcerptHelper::render($item, $item->params);

            $app->triggerEvent('onContentPrepare', ['com_academy.category', &$item, &$item->params, 0]);
            $item->summary = $item->text;

            $results = $app->triggerEvent('onContentAfterTitle', ['com_academy.category', &$item, &$item->params, 0]);
            $item->event->afterDisplayTitle = trim(implode("\n", $results));

            $results = $app->triggerEvent('onContentBeforeDisplay', ['com_academy.category', &$item, &$item->params, 0]);
            $item->event->beforeDisplayContent = trim(implode("\n", $results));

            $results = $app->triggerEvent('onContentAfterDisplay', ['com_academy.category', &$item, &$item->params, 0]);
            $item->event->afterDisplayContent = trim(implode("\n", $results));
        }
    }

    /**
     * Buckets the current page's items into lead/intro/link groups for the
     * Blog layout, using the main post count and compact post count
     * menu params. Harmless no-op for the List layout, which ignores them.
     */
    private function splitBlogGroups(): void
    {
        $numLeading = 0;
        $numIntro = \Joomla\Component\Academy\Site\Helper\ListingSettingsHelper::count($this->params);
        $numLinks   = (int) $this->params->def('num_links', 4);
        $max        = count($this->items);

        for ($i = 0; $i < $numLeading && $i < $max; $i++) {
            $this->lead_items[] = $this->items[$i];
        }

        for ($i = $numLeading; $i < $numLeading + $numIntro && $i < $max; $i++) {
            $this->intro_items[] = $this->items[$i];
        }

        for ($i = $numLeading + $numIntro; $i < $numLeading + $numIntro + $numLinks && $i < $max; $i++) {
            $this->link_items[] = $this->items[$i];
        }
    }
}
