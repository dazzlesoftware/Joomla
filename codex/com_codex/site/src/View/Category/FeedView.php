<?php

namespace Joomla\Component\Codex\Site\View\Category;

defined('_JEXEC') or die;

use Joomla\CMS\Document\Feed\FeedItem;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\AbstractView;
use Joomla\CMS\Router\Route;
use Joomla\Component\Codex\Administrator\Helper\CategoriesHelper;
use Joomla\Component\Codex\Site\Helper\ListExcerptHelper;
use Joomla\Component\Codex\Site\Helper\RouteHelper;

/** Feeds use the same family tables and visibility rules as category pages. */
class FeedView extends AbstractView
{
    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $params = $app->getParams();
        if (!$params->get('show_feed_link', 1)) {
            throw new \RuntimeException(Text::_('JGLOBAL_RESOURCE_NOT_FOUND'), 404);
        }

        $db = CategoriesHelper::db();
        $levels = $app->getIdentity()->getAuthorisedViewLevels();
        $id = $app->getInput()->getInt('id');
        $category = $db->setQuery($db->createQuery()->select('*')->from('#__codex_categories')
            ->where('id=' . $id)->where('published=1')->whereIn('access', $levels))->loadObject();
        if (!$category) {
            throw new \RuntimeException(Text::_('JGLOBAL_CATEGORY_NOT_FOUND'), 404);
        }

        $query = $db->createQuery()->select('p.*, u.name AS author, u.email AS author_email')
            ->from('#__codex AS p')->join('LEFT', '#__users AS u ON u.id=p.created_by')
            ->where('p.catid=' . $id)->where('p.state=1')->whereIn('p.access', $levels)
            ->where('(p.publish_up IS NULL OR p.publish_up<=UTC_TIMESTAMP())')
            ->where('(p.publish_down IS NULL OR p.publish_down>=UTC_TIMESTAMP())')
            ->order('CASE WHEN p.publish_up IS NULL THEN p.created ELSE p.publish_up END DESC');
        if ($app->getLanguageFilter()) {
            $query->where('p.language IN (' . $db->quote('*') . ',' . $db->quote($app->getLanguage()->getTag()) . ')');
        }
        $items = $db->setQuery($query, 0, max(1, (int) $app->get('feed_limit', 10)))->loadObjectList();
        $document = $this->getDocument();
        $document->setTitle($category->title);
        $document->setDescription(strip_tags($category->description ?? ''));
        $document->setGenerator('Genesis Codex');
        $document->link = Route::_(RouteHelper::getCategoryRoute($id, $category->language), false, Route::TLS_IGNORE, true);
        $document->editor = $app->get('fromname');
        $feedEmail = $app->get('feed_email', 'none');
        if ($feedEmail !== 'none') {
            $document->editorEmail = $app->get('mailfrom');
        }

        foreach ($items as $item) {
            $feed = new FeedItem();
            $feed->title = $item->title;
            $feed->link = Route::_(RouteHelper::getPostRoute($item->id . ':' . $item->alias, $item->catid, $item->language), false, Route::TLS_IGNORE, true);
            $item->readmore = !empty($item->body);
            $feed->description = $params->get('feed_summary', 0)
                ? (string) $item->summary . (string) $item->body
                : ListExcerptHelper::render($item, $params);
            if (!$params->get('feed_summary', 0) && $params->get('feed_show_readmore', 1) && $item->readmore) {
                $feed->description .= '<p><a href="' . htmlspecialchars($feed->link, ENT_QUOTES, 'UTF-8') . '">'
                    . Text::_('COM_CODEX_FEED_READMORE') . '</a></p>';
            }
            $media = json_decode($item->media ?? '{}');
            if (!empty($media->featured_image)) {
                $feed->description = '<p>' . HTMLHelper::_('image', $media->featured_image, $media->featured_image_alt ?? '') . '</p>' . $feed->description;
            }
            $feed->author = $item->created_by_alias ?: ($item->author ?? '');
            $feed->category = $category->title;
            // Syndication timestamps describe publication, independently of display preferences.
            $feed->date = $item->publish_up ?: $item->created;
            if ($feedEmail === 'site') {
                $feed->authorEmail = $app->get('mailfrom');
            } elseif ($feedEmail === 'author') {
                $feed->authorEmail = $item->author_email ?? '';
            }
            $document->addItem($feed);
        }
    }
}
