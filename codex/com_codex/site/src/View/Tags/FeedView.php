<?php
namespace Joomla\Component\Codex\Site\View\Tags;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Document\Feed\FeedItem;
use Joomla\CMS\MVC\View\AbstractView;
use Joomla\CMS\Router\Route;
use Joomla\Component\Codex\Site\Helper\TagDirectoryHelper;
use Joomla\Component\Codex\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;

class FeedView extends AbstractView
{
    public function display($tpl = null)
    {
        $app = Factory::getApplication(); $id = $app->getInput()->getInt('tag_id');
        if (!$id || !TagDirectoryHelper::params()->get('tags_show_rss', 1)) {
            throw new \RuntimeException(\Joomla\CMS\Language\Text::_('JGLOBAL_RESOURCE_NOT_FOUND'), 404);
        }
        $tag = TagDirectoryHelper::tag($id);
        $doc = $this->getDocument(); $doc->title = $tag->title;
        $doc->setGenerator('Genesis Codex');
        $doc->link = Route::_('index.php?option=com_codex&view=tags&tag_id='.$id, false);
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $rows = $db->setQuery(TagDirectoryHelper::posts($id)->order('COALESCE(p.publish_up,p.created) DESC, p.id DESC'),0,max(1,min(100,(int)$app->get('feed_limit',10))))->loadObjectList();
        foreach ($rows as $row) {
            $item = new FeedItem(); $item->title = $row->title;
            $item->link = Route::_(RouteHelper::getPostRoute($row->id.':'.$row->alias,$row->catid,$row->language), false);
            $item->date = $row->publish_up ?: $row->created;
            $item->description = htmlspecialchars(strip_tags($row->summary),ENT_QUOTES,'UTF-8');
            $item->category = [$tag->title]; $doc->addItem($item);
        }
    }
}
