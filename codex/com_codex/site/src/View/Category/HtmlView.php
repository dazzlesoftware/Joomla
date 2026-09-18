<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_codex
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Codex\Site\View\Category;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Codex\Administrator\Helper\CategoriesHelper;
use Joomla\Component\Codex\Administrator\Helper\TagsHelper;
use Joomla\Component\Codex\Site\Helper\ListExcerptHelper;
use Joomla\Registry\Registry;

final class HtmlView extends BaseHtmlView
{
    public $category;
    public array $items = [];
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

        $id = $app->getInput()->getInt('id');
        $this->category = $db->setQuery(
            $db->createQuery()->select('*')->from('#__codex_categories')
                ->where('id=' . (int) $id)
                ->where('published=1')
                ->whereIn('access', $user->getAuthorisedViewLevels())
        )->loadObject();

        if (!$this->category) {
            throw new \RuntimeException('Category not found', 404);
        }

        $q = $db->createQuery()->select('p.*, u.name AS author')->from('#__codex AS p')
            ->join('LEFT', '#__users AS u ON u.id = p.created_by')
            ->where('p.catid=' . (int) $id)
            ->where('p.state=1')
            ->whereIn('p.access', $user->getAuthorisedViewLevels())
            ->where('(p.publish_up IS NULL OR p.publish_up<=UTC_TIMESTAMP())')
            ->where('(p.publish_down IS NULL OR p.publish_down>=UTC_TIMESTAMP())')
            ->order('CASE WHEN p.publish_up IS NULL THEN p.created ELSE p.publish_up END DESC');

        if ($app->getLanguageFilter()) {
            $q->where('p.language IN (' . $db->quote('*') . ',' . $db->quote($app->getLanguage()->getTag()) . ')');
        }

        $all = $db->setQuery($q)->loadObjectList() ?: [];

        foreach ($all as $item) {
            $item->params = new Registry($item->options ?? '{}');
            $item->slug = $item->id . ':' . $item->alias;
            // Lets the featured_image layout fall back to the category's own
            // "Default Post Cover" when a post has no image of its own.
            $item->category_default_image = $this->category->default_image ?? '';
            $item->category_title = $this->category->title;
            $item->category_language = $this->category->language;
            $item->parent_id = null;
            $item->readmore = 0;
        }

        $this->params = clone $app->getParams();
        $requestedLayout = $app->getInput()->getCmd('layout', '');
        $layout = (string) $this->params->get('category_layout', '');
        // Named style menu types are explicit; Blog/List can inherit the component setting.
        if (in_array($requestedLayout, ['standard', 'card', 'learning', 'simple', 'nickel'], true)) {
            $layout = $requestedLayout;
        } elseif ($layout === '') {
            $layout = $requestedLayout ?: 'default';
        }
        // Joomla componentlayout stores component selections as _:card, etc.
        $layoutName = str_contains($layout, ':') ? substr($layout, strrpos($layout, ':') + 1) : $layout;
        if (in_array($layoutName, ['default', 'blog', 'standard', 'card', 'learning', 'simple', 'nickel'], true)) {
            $this->setLayout($layout);
        }
        // Blog pages must count exactly the items their leading/intro/link groups render.
        $limit = max(1, (int) $app->get('list_limit', 20));
        if ($layoutName !== 'default') {
            $leading = max(0, (int) $this->params->get('num_leading_posts', 1));
            $intro = max(0, (int) $this->params->get('num_intro_posts', 4));
            $links = \Joomla\Component\Codex\Site\Helper\CompactPostsHelper::visible($this->params) && $this->params->get('compact_selection', 'next') === 'next' ? max(0, (int) $this->params->get('num_links', 4)) : 0;
            if ($leading + $intro + $links === 0) {
                $intro = 1;
            }
            $this->params->set('num_leading_posts', $leading);
            $this->params->set('num_intro_posts', $intro);
            $limit = $leading + $intro + $links;
        }
        $start = $app->getInput()->getUint('limitstart', 0);
        $this->pagination = new Pagination(count($all), $start, $limit);
        $this->items = array_slice($all, $this->pagination->limitstart, $limit);

        foreach ($this->items as $item) {
            $merged = clone $this->params;
            $merged->merge($item->params);
            $item->params = $merged;
        }

        $this->enrichItems();
        $this->splitBlogGroups();

        $this->getDocument()->setTitle($this->category->title);

        parent::display($tpl);
    }

    /**
     * Adds everything the item templates (blog_item.php, default.php's excerpt,
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
        $tagsByItem = (new TagsHelper())->getMultipleItemTags('com_codex.post', $ids);

        $app = Factory::getApplication();
        PluginHelper::importPlugin('codex');

        foreach ($this->items as $item) {
            // Every item here already passed the access/state filters above.
            $item->params->set('access-view', true);
            $item->params->set('access-edit', $user->authorise('core.edit', 'com_codex.post.' . $item->id));

            $item->tags = (object) ['itemTags' => $tagsByItem[(int) $item->id] ?? []];
            $item->event = new \stdClass();

            // Decide the excerpt from the raw stored summary, before content
            // plugins expand any shortcodes (accordions, embeds, etc.) into
            // their full rendered markup - otherwise a short shortcode that
            // expands into a large widget fools the length check into
            // skipping truncation entirely, dumping the whole widget into
            // the listing.
            $item->text = ListExcerptHelper::render($item, $item->params);

            $app->triggerEvent('onContentPrepare', ['com_codex.category', &$item, &$item->params, 0]);
            $item->summary = $item->text;

            $results = $app->triggerEvent('onContentAfterTitle', ['com_codex.category', &$item, &$item->params, 0]);
            $item->event->afterDisplayTitle = trim(implode("\n", $results));

            $results = $app->triggerEvent('onContentBeforeDisplay', ['com_codex.category', &$item, &$item->params, 0]);
            $item->event->beforeDisplayContent = trim(implode("\n", $results));

            $results = $app->triggerEvent('onContentAfterDisplay', ['com_codex.category', &$item, &$item->params, 0]);
            $item->event->afterDisplayContent = trim(implode("\n", $results));
        }
    }

    /**
     * Buckets the current page's items into lead/intro/link groups for the
     * Blog layout, per the num_leading_posts / num_intro_posts / num_links
     * menu params. Harmless no-op for the List layout, which ignores them.
     */
    private function splitBlogGroups(): void
    {
        $numLeading = (int) $this->params->def('num_leading_posts', 1);
        $numIntro   = (int) $this->params->def('num_intro_posts', 4);
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
