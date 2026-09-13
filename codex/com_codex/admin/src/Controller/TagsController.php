<?php
namespace Joomla\Component\Codex\Administrator\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\Component\Codex\Administrator\Helper\TagsHelper;
use Joomla\Utilities\ArrayHelper;

class TagsController extends BaseController
{
    public function add(): void
    {
        Factory::getApplication()->redirect(Route::_('index.php?option=com_codex&view=tag', false));
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
        if (!$app->getIdentity()->authorise('core.edit.state', 'com_codex')) {
            throw new \RuntimeException('Not authorised', 403);
        }
        $ids = ArrayHelper::toInteger((array) $app->getInput()->post->get('cid', [], 'array'));
        $n = TagsHelper::publish($ids, $state);
        $app->enqueueMessage($n ? 'Tag state updated.' : 'No tags selected.', $n ? 'message' : 'warning');
        $app->redirect(Route::_('index.php?option=com_codex&view=tags', false));
    }

    public function makedefault(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.edit.state', 'com_codex')) {
            throw new \RuntimeException('Not authorised', 403);
        }
        $ids = ArrayHelper::toInteger((array) $app->getInput()->post->get('cid', [], 'array'));
        if (count($ids) !== 1) {
            $app->enqueueMessage('Select exactly one tag to make the default.', 'warning');
        } else {
            TagsHelper::makeDefault((int) $ids[0]);
            $app->enqueueMessage('Default tag updated.');
        }
        $app->redirect(Route::_('index.php?option=com_codex&view=tags', false));
    }

    public function removedefault(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.edit.state', 'com_codex')) {
            throw new \RuntimeException('Not authorised', 403);
        }
        $ids = ArrayHelper::toInteger((array) $app->getInput()->post->get('cid', [], 'array'));
        $n = TagsHelper::removeDefault($ids);
        $app->enqueueMessage($n ? 'Default tag removed.' : 'No tags selected.', $n ? 'message' : 'warning');
        $app->redirect(Route::_('index.php?option=com_codex&view=tags', false));
    }

    public function delete(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.delete', 'com_codex')) {
            throw new \RuntimeException('Not authorised', 403);
        }
        $ids = ArrayHelper::toInteger((array) $app->getInput()->post->get('cid', [], 'array'));
        if (!$ids) {
            $single = $app->getInput()->getInt('id');
            if ($single) {
                $ids = [$single];
            }
        }
        $n = TagsHelper::delete($ids);
        $app->enqueueMessage($n ? 'Tag(s) deleted. Posts were kept.' : 'No tags selected.', $n ? 'message' : 'warning');
        $app->redirect(Route::_('index.php?option=com_codex&view=tags', false));
    }
}
