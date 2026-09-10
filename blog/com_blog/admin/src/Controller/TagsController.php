<?php
namespace Joomla\Component\Blog\Administrator\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\Component\Blog\Administrator\Helper\TagsHelper;

class TagsController extends BaseController
{
    public function save(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication(); $id = $app->getInput()->post->getInt('id');
        if (!$app->getIdentity()->authorise($id ? 'core.edit' : 'core.create', 'com_blog')) throw new \RuntimeException('Not authorised', 403);
        $db = TagsHelper::db(); $input = $app->getInput()->post;
        $title = mb_substr(trim($input->getString('title')), 0, 255);
        if ($title === '') { $app->enqueueMessage('A tag title is required.', 'error'); $this->back($id); return; }
        $alias = mb_substr(ApplicationHelper::stringURLSafe($input->getString('alias') ?: $title), 0, 191) ?: bin2hex(random_bytes(6));
        if ($db->setQuery('SELECT id FROM #__blog_tags WHERE alias=' . $db->quote($alias) . ' AND id<>' . $id)->loadResult()) { $app->enqueueMessage('This tag alias is already in use.', 'error'); $this->back($id); return; }
        $old = $id ? $db->setQuery('SELECT * FROM #__blog_tags WHERE id=' . $id)->loadObject() : null;
        if ($id && !$old) throw new \RuntimeException('Tag not found', 404);
        $canState = $app->getIdentity()->authorise('core.edit.state', 'com_blog');
        $row = (object) ['id' => $id ?: null, 'title' => $title, 'alias' => $alias, 'description' => $input->getString('description'), 'published' => $canState ? (int) ($input->getInt('published', 1) === 1) : (int) ($old->published ?? 0), 'access' => $input->getInt('access', 1), 'language' => $input->getCmd('language', '*'), 'params' => $old->params ?? '{}'];
        $id ? $db->updateObject('#__blog_tags', $row, 'id') : $db->insertObject('#__blog_tags', $row, 'id');
        $app->enqueueMessage('Tag saved.'); $this->back();
    }
    public function delete(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.delete', 'com_blog')) throw new \RuntimeException('Not authorised', 403);
        $id = $app->getInput()->post->getInt('id'); $db = TagsHelper::db();
        $db->transactionStart();
        try {
            $db->setQuery('DELETE FROM #__blog_tag_map WHERE tag_id=' . $id)->execute();
            $db->setQuery('DELETE FROM #__blog_tags WHERE id=' . $id)->execute();
            $db->transactionCommit();
        } catch (\Throwable $e) { $db->transactionRollback(); throw $e; }
        $app->enqueueMessage('Tag deleted. Posts were kept.'); $this->back();
    }
    private function back(int $id = 0): void { $this->setRedirect(Route::_('index.php?option=com_blog&view=tags' . ($id ? '&id=' . $id : ''), false)); }
}
