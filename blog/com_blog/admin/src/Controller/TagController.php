<?php
namespace Joomla\Component\Blog\Administrator\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\Factory; use Joomla\CMS\MVC\Controller\BaseController; use Joomla\CMS\Router\Route; use Joomla\CMS\Session\Session; use Joomla\Component\Blog\Administrator\Helper\TagsHelper;

final class TagController extends BaseController
{
    public function add(): void
    {
        Factory::getApplication()->redirect(Route::_('index.php?option=com_blog&view=tag', false));
    }

    public function apply(): void
    {
        $id = $this->doSave();
        Factory::getApplication()->redirect(Route::_('index.php?option=com_blog&view=tag&id=' . $id, false));
    }

    public function save(): void
    {
        $this->doSave();
        Factory::getApplication()->redirect(Route::_('index.php?option=com_blog&view=tags', false));
    }

    public function save2new(): void
    {
        $this->doSave();
        Factory::getApplication()->redirect(Route::_('index.php?option=com_blog&view=tag', false));
    }

    public function cancel(): void
    {
        Factory::getApplication()->redirect(Route::_('index.php?option=com_blog&view=tags', false));
    }

    private function doSave(): int
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        $data = $app->getInput()->post->get('jform', [], 'array');
        $id = (int) ($data['id'] ?? 0);
        if (!$app->getIdentity()->authorise($id ? 'core.edit' : 'core.create', 'com_blog')) {
            throw new \RuntimeException('Not authorised', 403);
        }

        $id = TagsHelper::save($data);

        $app->enqueueMessage('Tag saved.');
        return $id;
    }
}
