<?php
namespace Joomla\Component\Academy\Administrator\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Utilities\ArrayHelper;

final class CommentsController extends BaseController
{
    public function add(): void
    {
        Factory::getApplication()->redirect(Route::_('index.php?option=com_academy&view=comment', false));
    }

    public function publish(): void
    {
        $this->batchState(1);
    }

    public function unpublish(): void
    {
        $this->batchState(0);
    }

    private function batchState(int $state): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.edit.state', 'com_academy')) {
            throw new \RuntimeException('Not authorised', 403);
        }
        $ids = ArrayHelper::toInteger((array) $app->getInput()->post->get('cid', [], 'array'));
        if ($ids) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery(
                $db->createQuery()->update('#__academy_comments')->set('state=' . (int) $state)->whereIn('id', $ids)
            )->execute();
        }
        $app->enqueueMessage($ids ? 'Comment state updated.' : 'No comments selected.', $ids ? 'message' : 'warning');
        $app->redirect(Route::_('index.php?option=com_academy&view=comments', false));
    }

    public function delete(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.delete', 'com_academy')) {
            throw new \RuntimeException('Not authorised', 403);
        }
        $ids = ArrayHelper::toInteger((array) $app->getInput()->post->get('cid', [], 'array'));
        if ($ids) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery($db->createQuery()->delete('#__academy_comments')->whereIn('id', $ids))->execute();
        }
        $app->enqueueMessage($ids ? 'Comment(s) deleted.' : 'No comments selected.', $ids ? 'message' : 'warning');
        $app->redirect(Route::_('index.php?option=com_academy&view=comments', false));
    }
}
