<?php

namespace Joomla\Component\Academy\Administrator\Controller;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Component\Academy\Administrator\Helper\CategoriesHelper;
use Joomla\Utilities\ArrayHelper;

final class CategoriesController extends BaseController
{
    public function add(): void
    {
        Factory::getApplication()->redirect(Route::_('index.php?option=com_academy&view=category', false));
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
        $n = CategoriesHelper::publish($ids, $state);
        $app->enqueueMessage($n ? 'Category state updated.' : 'No categories selected.', $n ? 'message' : 'warning');
        $app->redirect(Route::_('index.php?option=com_academy&view=categories', false));
    }

    public function makedefault(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.edit.state', 'com_academy')) {
            throw new \RuntimeException('Not authorised', 403);
        }
        $ids = ArrayHelper::toInteger((array) $app->getInput()->post->get('cid', [], 'array'));
        if (count($ids) !== 1) {
            $app->enqueueMessage('Select exactly one category to make the default.', 'warning');
        } else {
            CategoriesHelper::makeDefault((int) $ids[0]);
            $app->enqueueMessage('Default category updated.');
        }
        $app->redirect(Route::_('index.php?option=com_academy&view=categories', false));
    }

    public function removedefault(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.edit.state', 'com_academy')) {
            throw new \RuntimeException('Not authorised', 403);
        }
        $ids = ArrayHelper::toInteger((array) $app->getInput()->post->get('cid', [], 'array'));
        $n = CategoriesHelper::removeDefault($ids);
        $app->enqueueMessage($n ? 'Default category removed.' : 'No categories selected.', $n ? 'message' : 'warning');
        $app->redirect(Route::_('index.php?option=com_academy&view=categories', false));
    }

    public function copy(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.create', 'com_academy')) {
            throw new \RuntimeException('Not authorised', 403);
        }
        $ids = ArrayHelper::toInteger((array) $app->getInput()->post->get('cid', [], 'array'));
        $n = CategoriesHelper::copy($ids);
        $app->enqueueMessage($n ? 'Category copied.' : 'No categories selected.', $n ? 'message' : 'warning');
        $app->redirect(Route::_('index.php?option=com_academy&view=categories', false));
    }

    public function delete(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.delete', 'com_academy')) {
            throw new \RuntimeException('Not authorised', 403);
        }
        $ids = ArrayHelper::toInteger((array) $app->getInput()->post->get('cid', [], 'array'));
        if (!$ids) {
            $single = $app->getInput()->getInt('id');
            if ($single) {
                $ids = [$single];
            }
        }
        $n = CategoriesHelper::delete($ids);
        $app->enqueueMessage($n ? 'Category deleted. Posts were left uncategorised.' : 'No categories selected.', $n ? 'message' : 'warning');
        $app->redirect(Route::_('index.php?option=com_academy&view=categories', false));
    }
}
