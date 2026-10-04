<?php
namespace Joomla\Component\Blog\Site\View\Author;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Component\Blog\Site\Helper\ListExcerptHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

final class HtmlView extends BaseHtmlView
{
    public ?object $author = null;
    public array $posts = [];
    public array $compactItems = [];
    public ?Registry $params = null;
    public ?Pagination $pagination = null;
    public ?object $item = null;
    public array $sliderData = [];

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $authorId = $app->getInput()->getInt('id', 0);
        $model = $this->getModel();
        $this->params = $model->getState('params');
        $authors = \Joomla\Component\Blog\Site\Helper\ListingFilterHelper::ids($this->params->get('listing_authors', []));
        $profileId = count($authors) === 1 ? $authors[0] : 0;
        if ($profileId) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $this->author = $db->setQuery($db->createQuery()->select(['id', 'name'])->from('#__users')
                ->where('id=' . $profileId)->where('block=0'))->loadObject();
            if (!$this->author) { throw new \RuntimeException('Author not found.', 404); }
        }
        $total = (int) $model->getTotal();
        $limit = (int) $model->getState('list.limit');
        $start = (int) $model->getState('list.start');
        $start = $total ? min(intdiv($start, $limit), intdiv($total - 1, $limit)) * $limit : 0;
        $model->setState('list.start', $start);
        $this->posts = $model->getItems();
        $compact = '\\Joomla\\Component\\Blog\\Site\\Helper\\CompactPostsHelper';
        $fallback = [];
        if ($compact::visible($this->params) && $this->params->get('compact_selection', 'next') === 'next') {
            $next = $app->bootComponent('com_blog')->getMVCFactory()->createModel('Author', 'Site');
            $next->getState();
            $next->setState('params', clone $this->params);
            $next->setState('list.start', $start + count($this->posts));
            $next->setState('list.limit', max(0, (int) $this->params->get('num_links', 4)));
            $next->setState('filter.access', true);
            if ($next->getState('list.limit') > 0) { $fallback = $next->getItems() ?: []; }
        }
        $this->compactItems = $compact::select($fallback, $this->posts, $this->params);
        $this->pagination = new Pagination($total, $start, $limit);
        $this->pagination->hideEmptyLimitstart = true;
        $this->pagination->setAdditionalUrlParam('option', 'com_blog');
        $this->pagination->setAdditionalUrlParam('view', 'author');
        if ($authorId) { $this->pagination->setAdditionalUrlParam('id', $authorId); }
        PluginHelper::importPlugin('content');
        PluginHelper::importPlugin('blog');
        foreach ($this->posts as $post) {
            $post->slug = $post->id . ':' . $post->alias;
            if (($post->parent_alias ?? '') === 'root') {
                $post->parent_id = null;
            }
            $post->text = ListExcerptHelper::render($post, $post->params);
            $app->triggerEvent('onContentPrepare', ['com_blog.author', &$post, &$post->params, 0]);
            $post->summary = $post->text;
            $post->event = new \stdClass();
            foreach (['onContentAfterTitle' => 'afterDisplayTitle', 'onContentBeforeDisplay' => 'beforeDisplayContent', 'onContentAfterDisplay' => 'afterDisplayContent'] as $event => $property) {
                $post->event->$property = trim(implode("\n", $app->triggerEvent($event, ['com_blog.author', &$post, &$post->params, 0])));
            }
        }
        $this->document->setTitle($this->author->name ?? \Joomla\CMS\Language\Text::_('COM_BLOG_AUTHORS_HEADING'));
        \Joomla\Component\Blog\Site\Helper\ListingFilterHelper::canonical($this->document, $this->params);
        parent::display($tpl);
    }
}
