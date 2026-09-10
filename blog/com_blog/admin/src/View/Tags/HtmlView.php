<?php
namespace Joomla\Component\Blog\Administrator\View\Tags;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Blog\Administrator\Helper\TagsHelper;
class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public $tag;
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_blog')) throw new \RuntimeException('Not authorised', 403);
        $db = TagsHelper::db();
        $q = $db->createQuery()->select('t.*, (SELECT COUNT(*) FROM #__blog_tag_map m WHERE m.tag_id=t.id AND m.type_alias=' . $db->quote('com_blog.post') . ') AS post_count')->from('#__blog_tags t')->order('t.title');
        $search = trim($app->getInput()->getString('search'));
        if ($search !== '') $q->where('t.title LIKE ' . $db->quote('%' . $db->escape($search, true) . '%'));
        $this->items = $db->setQuery($q)->loadObjectList();
        $id = $app->getInput()->getInt('id');
        $this->tag = $id ? $db->setQuery('SELECT * FROM #__blog_tags WHERE id=' . $id)->loadObject() : null;
        if ($id && !$this->tag) throw new \RuntimeException('Tag not found', 404);
        ToolbarHelper::title('Blog Tags', 'tags');
        parent::display($tpl);
    }
}
