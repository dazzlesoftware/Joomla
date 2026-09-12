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

        $q = $db->createQuery()->select('p.*')->from('#__codex AS p')
            ->where('p.catid=' . (int) $id)
            ->where('p.state=1')
            ->whereIn('p.access', $user->getAuthorisedViewLevels())
            ->where('(p.publish_up IS NULL OR p.publish_up<=UTC_TIMESTAMP())')
            ->where('(p.publish_down IS NULL OR p.publish_down>=UTC_TIMESTAMP())')
            ->order('CASE WHEN p.publish_up IS NULL THEN p.created ELSE p.publish_up END DESC');
        $all = $db->setQuery($q)->loadObjectList() ?: [];

        foreach ($all as $item) {
            $item->params = new Registry($item->options ?? '{}');
            $item->slug = $item->id . ':' . $item->alias;
        }

        $limit = max(1, (int) $app->get('list_limit', 20));
        $start = $app->getInput()->getUint('limitstart', 0);
        $this->items = array_slice($all, $start, $limit);
        $this->pagination = new Pagination(count($all), $start, $limit);
        $this->params = $app->getParams();

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

            if (!isset($item->text)) {
                $item->text = $item->summary;
            }

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
