<?php

namespace Joomla\Component\Blog\Site\View\Tags;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Blog\Administrator\Helper\TagsHelper;
use Joomla\Component\Blog\Site\Helper\ListExcerptHelper;
use Joomla\Registry\Registry;

class HtmlView extends BaseHtmlView
{
    public array $tags = [];
    public array $items = [];
    public $tag;
    public $pagination;
    public ?Registry $params = null;
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $this->params = $app->getParams();
        $db = TagsHelper::db();
        $q = $db->createQuery()->select('*')->from('#__blog_tags')->where('published=1')->whereIn('access', $app->getIdentity()->getAuthorisedViewLevels())->where('language IN (' . $db->quote('*') . ',' . $db->quote($app->getLanguage()->getTag()) . ')')->order('title');
        $id = $app->getInput()->getInt('tag_id');
        if ($id) {
            $q->where('id=' . $id);
            $this->tag = $db->setQuery($q)->loadObject();
            if (!$this->tag) {
                throw new \RuntimeException('Tag not found', 404);
            }
            $groups = $app->getIdentity()->getAuthorisedViewLevels();
            $query = $db->createQuery()->select(['p.*', 'c.default_image AS category_default_image'])->from('#__blog AS p')
                ->join('INNER', '#__blog_tag_map AS m ON m.content_item_id=p.id')
                ->join('LEFT', '#__blog_categories AS c ON c.id=p.catid')
                ->where('m.type_alias=' . $db->quote('com_blog.post'))->where('m.tag_id=' . (int) $id)
                ->where('p.state=1')->whereIn('p.access', $groups)
                ->where('(p.publish_up IS NULL OR p.publish_up <= UTC_TIMESTAMP())')->where('(p.publish_down IS NULL OR p.publish_down >= UTC_TIMESTAMP())')
                ->where('EXISTS (SELECT 1 FROM #__blog_categories c2 WHERE c2.id=p.catid AND c2.published=1 AND c2.access IN (' . implode(',', $groups) . '))');
            if ($app->getLanguageFilter()) {
                $query->where('p.language IN (' . $db->quote('*') . ',' . $db->quote($app->getLanguage()->getTag()) . ')');
            }
            $query->order('CASE WHEN p.publish_up IS NULL THEN p.created ELSE p.publish_up END DESC');
            $limit = max(1, (int) $app->get('list_limit', 20));
            $start = $app->getInput()->getUint('limitstart', 0);
            $allItems = $db->setQuery($query)->loadObjectList() ?: [];
            foreach ($allItems as $item) {
                $item->params = new Registry($item->options ?? '{}');
                $item->slug = $item->id . ':' . $item->alias;
                $item->params->set('access-view', true);
            }
            $count = count($allItems);
            $this->items = array_slice($allItems, $start, $limit);
            $this->pagination = new Pagination($count, $start, $limit);

            // Decide the excerpt from the raw stored summary, then run
            // content plugins - before the shortcodes expand into large
            // widgets that would otherwise fool the length check into
            // skipping truncation entirely.
            PluginHelper::importPlugin('blog');
            foreach ($this->items as $item) {
                $item->text = ListExcerptHelper::render($item, $item->params);
                $app->triggerEvent('onContentPrepare', ['com_blog.tags', &$item, &$item->params, 0]);
                $item->summary = $item->text;
            }
        } else {
            $this->tags = $db->setQuery($q)->loadObjectList();
        }
        $this->getDocument()->setTitle($this->tag->title ?? 'Tags');
        parent::display($tpl);
    }
}
