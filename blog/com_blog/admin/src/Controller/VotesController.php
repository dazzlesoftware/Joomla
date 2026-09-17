<?php
namespace Joomla\Component\Blog\Administrator\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Component\Blog\Administrator\Service\VotesService;
final class VotesController extends BaseController
{
    public function add(): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_blog') || !$app->getIdentity()->authorise('core.create', 'com_blog')) {
            throw new \RuntimeException('Not authorised', 403);
        }
        $app->redirect(Route::_('index.php?option=com_blog&view=votes&layout=edit', false));
    }
    public function save(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        $input = $app->getInput()->post;
        $id = $input->getInt('id', 0);
        if (!$app->getIdentity()->authorise('core.manage', 'com_blog') || !$app->getIdentity()->authorise($id ? 'core.edit' : 'core.create', 'com_blog')) {
            throw new \RuntimeException('Not authorised', 403);
        }
        try {
            (new VotesService(Factory::getContainer()->get(DatabaseInterface::class)))->save($id, $input->getInt('post_id', 0), $input->getInt('rating', 0), $input->get('user_id', null, 'int'));
        } catch (\InvalidArgumentException $error) {
            $app->enqueueMessage($error->getMessage(), 'warning');
            $app->redirect(Route::_('index.php?option=com_blog&view=votes&layout=edit&id=' . max(0, $id), false));
            return;
        }
        $app->enqueueMessage('Vote saved. Post ratings updated.');
        $app->redirect(Route::_('index.php?option=com_blog&view=votes', false));
    }
    public function delete(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_blog') || !$app->getIdentity()->authorise('core.delete', 'com_blog')) {
            throw new \RuntimeException('Not authorised', 403);
        }
        $count = (new VotesService(Factory::getContainer()->get(DatabaseInterface::class)))->delete($app->getInput()->post->get('cid', [], 'array'));
        $app->enqueueMessage($count ? $count . ' vote(s) deleted. Post ratings updated.' : 'No votes selected.', $count ? 'message' : 'warning');
        $app->redirect(Route::_('index.php?option=com_blog&view=votes', false));
    }
}
