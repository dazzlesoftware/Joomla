<?php
namespace Joomla\Component\Academy\Site\View\Author;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public ?object $author = null;
    public array $posts = [];

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $authorId = $app->getInput()->getInt('id');

        if ($authorId < 1) {
            throw new \RuntimeException('Author not found.', 404);
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->author = $db->setQuery(
            $db->createQuery()->select(['id', 'name'])->from('#__users')->where('id=' . $authorId)->where('block=0')
        )->loadObject();

        if (!$this->author) {
            throw new \RuntimeException('Author not found.', 404);
        }

        $levels = array_map('intval', $app->getIdentity()->getAuthorisedViewLevels());
        $now = Factory::getDate()->toSql();
        $query = $db->createQuery()
            ->select(['id', 'title', 'alias', 'catid', 'summary', 'media', 'publish_up', 'created', 'language'])
            ->from('#__academy')
            ->where('created_by=' . $authorId)
            ->where('state=1')
            ->whereIn('access', $levels)
            ->where('(publish_up IS NULL OR publish_up <= ' . $db->quote($now) . ')')
            ->where('(publish_down IS NULL OR publish_down >= ' . $db->quote($now) . ')')
            ->order('publish_up DESC, created DESC');
        $this->posts = $db->setQuery($query)->loadObjectList();

        $this->document->setTitle($this->author->name);
        parent::display($tpl);
    }
}