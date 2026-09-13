<?php

namespace Joomla\Component\Academy\Administrator\Controller;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

final class EmailtemplatesController extends BaseController
{
    public function delete(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_academy')) {
            throw new \RuntimeException('Not authorised', 403);
        }$ids = array_map('intval', (array)$app->getInput()->post->get('cid', [], 'array'));
        if ($ids) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery($db->createQuery()->delete('#__academy_email_templates')->whereIn('id', $ids))->execute();
        }$app->redirect(Route::_('index.php?option=com_academy&view=emailtemplates', false));
    }
}
