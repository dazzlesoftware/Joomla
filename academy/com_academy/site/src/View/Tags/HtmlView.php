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
    public array $posts = [];
    public array $compactItems = [];
    public array $sliderData = [];
    public $item;
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
        $model = $this->getModel();
        $this->params = $model->getState('params');
        $total = (int) $model->getTotal();
        $limit = (int) $model->getState('list.limit');
        $start = (int) $model->getState('list.start');
        $start = $total ? min(intdiv($start, $limit), intdiv($total - 1, $limit)) * $limit : 0;
        $model->setState('list.start', $start);
        $this->posts = $model->getItems();
        $this->pagination = new Pagination($total, $start, $limit);
        $this->pagination->hideEmptyLimitstart = true;
        $this->pagination->setAdditionalUrlParam('option', 'com_academy');
        $this->pagination->setAdditionalUrlParam('view', 'tags');
        $this->pagination->setAdditionalUrlParam('tag_id', $id);
        PluginHelper::importPlugin('content');
        PluginHelper::importPlugin('academy');
        foreach ($this->posts as $post) {
            $post->slug = $post->id . ':' . $post->alias;
            if (($post->parent_alias ?? '') === 'root') {
                $post->parent_id = null;
            }
            $post->text = ListExcerptHelper::render($post, $post->params);
            $app->triggerEvent('onContentPrepare', ['com_academy.tags', &$post, &$post->params, 0]);
            $post->summary = $post->text;
            $post->event = new \stdClass();
            foreach (['onContentAfterTitle' => 'afterDisplayTitle', 'onContentBeforeDisplay' => 'beforeDisplayContent', 'onContentAfterDisplay' => 'afterDisplayContent'] as $event => $property) {
                $post->event->$property = trim(implode("\n", $app->triggerEvent($event, ['com_academy.tags', &$post, &$post->params, 0])));
            }
        }
        $fallbackModel = clone $model;
        $fallbackModel->setState('list.start', $start + $limit);
        $fallbackModel->setState('list.limit', max(1, min(100, (int)$this->params->get('num_links',4))));
        $fallback = $this->params->get('compact_selection','next') === 'next' ? $fallbackModel->getItems() : [];
        $this->compactItems = \Joomla\Component\Academy\Site\Helper\CompactPostsHelper::select($fallback, $this->posts, $this->params);
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
