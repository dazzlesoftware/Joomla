<?php

namespace Joomla\Component\Academy\Site\View\Tags;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Academy\Administrator\Helper\TagsHelper;
use Joomla\Component\Academy\Site\Helper\ListExcerptHelper;
use Joomla\Registry\Registry;

class HtmlView extends BaseHtmlView
{
    public array $tags = [];
    public array $items = [];
    public $tag = null;
    public string $search = '';
    public string $sort = 'title';
    public $directoryItem;
    public $pagination;
    public ?Registry $params = null;
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $this->params = \Joomla\Component\Academy\Site\Helper\TagDirectoryHelper::params();
        $db = TagsHelper::db();
        $q = \Joomla\Component\Academy\Site\Helper\TagDirectoryHelper::tags();
        $id = $app->getInput()->getInt('tag_id');
        if ($id) {
            $q->where('t.id=' . $id);
            $this->tag = $db->setQuery($q)->loadObject();
            if (!$this->tag) {
                throw new \RuntimeException('Tag not found', 404);
            }
            $groups = $app->getIdentity()->getAuthorisedViewLevels();
            $query = \Joomla\Component\Academy\Site\Helper\TagDirectoryHelper::posts($id)->select('c.default_image AS category_default_image');
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
            PluginHelper::importPlugin('content');
            PluginHelper::importPlugin('academy');
            foreach ($this->items as $item) {
                $item->readmore = !empty($item->body);
                $item->text = ListExcerptHelper::render($item, $this->params);
                $app->triggerEvent('onContentPrepare', ['com_academy.tags', &$item, &$item->params, 0]);
                $item->summary = $item->text;
            }
        } else {
            $helper = \Joomla\Component\Academy\Site\Helper\TagDirectoryHelper::class;
            $this->search = $this->params->get('tags_show_search', 1) ? trim($app->getInput()->getString('tag_search')) : '';
            $orders = ['title'=>'t.title ASC', 'title_desc'=>'t.title DESC', 'count'=>'post_count DESC', 'newest'=>'t.id DESC'];
            $requested = $this->params->get('tags_show_sort', 1) ? $app->getInput()->getCmd('tag_sort') : '';
            $this->sort = $requested ?: (string) $this->params->get('tags_order', 'title');
            if (!isset($orders[$this->sort])) { $this->sort = 'title'; }
            $posts = $helper::posts()->clear('select')->select('COUNT(DISTINCT p.id)')
                ->where('EXISTS (SELECT 1 FROM #__academy_tag_map m WHERE m.content_item_id=p.id AND m.type_alias='.$db->quote('com_academy.post').' AND m.tag_id=t.id)');
            $q = $helper::tags()->select('('.$posts.') AS post_count');
            if ($this->params->get('tags_style', 'link_grid') === 'image_grid') {
                $image = clone $posts;
                $image->clear('select')->select('p.media')->order('COALESCE(p.publish_up,p.created) DESC, p.id DESC')->setLimit(1);
                $q->select('('.$image.') AS post_media');
            }
            if ($this->search !== '') { $q->where('t.title LIKE '.$db->quote('%'.$db->escape($this->search,true).'%',false)); }
            if (!$this->params->get('tags_show_empty', 1)) { $q->where('('.$posts.') > 0'); }
            $countQuery = clone $q; $countQuery->clear('select')->select('COUNT(*)');
            $total = (int) $db->setQuery($countQuery)->loadResult();
            $limit = max(1,min(100,(int)$this->params->get('tags_per_page',12)));
            $start = $app->getInput()->getUint('limitstart',0);
            if ($start >= $total) { $start = $total ? (int)(floor(($total-1)/$limit)*$limit) : 0; }
            $this->tags = $db->setQuery($q->order($orders[$this->sort].', t.id ASC'),$start,$limit)->loadObjectList();
            $this->pagination = new Pagination($total,$start,$limit);
            foreach (['option'=>'com_academy','view'=>'tags','tag_id'=>0,'tag_search'=>$this->search,'tag_sort'=>$this->sort] as $key=>$value) { $this->pagination->setAdditionalUrlParam($key,$value); }
        }
        $this->getDocument()->setTitle($this->tag->title ?? 'Tags');
        parent::display($tpl);
    }
}
