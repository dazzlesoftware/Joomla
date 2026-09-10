<?php
namespace Joomla\Component\Academy\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

final class SubscriberController extends BaseController
{
    public function save(): void
    {
        Session::checkToken() or jexit('Invalid token');

        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_academy')) {
            throw new \RuntimeException('Not authorised', 403);
        }

        $input = $app->getInput();
        $id = $input->getInt('id');
        $name = trim($input->getString('name'));
        $email = strtolower(trim($input->getString('email')));
        $state = $input->getInt('state', 0);

        if (!$id) {
            throw new \RuntimeException('Invalid subscriber.', 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $app->enqueueMessage('Enter a valid email address.', 'warning');
            $app->redirect(Route::_('index.php?option=com_academy&view=subscriber&id=' . $id, false));
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $duplicate = (int) $db->setQuery(
            $db->createQuery()
                ->select('COUNT(*)')
                ->from('#__academy_subscribers')
                ->where('email=' . $db->quote($email))
                ->where('id!=' . $id)
        )->loadResult();

        if ($duplicate) {
            $app->enqueueMessage('Another subscriber already uses that email address.', 'warning');
            $app->redirect(Route::_('index.php?option=com_academy&view=subscriber&id=' . $id, false));
            return;
        }

        $db->setQuery(
            $db->createQuery()
                ->update('#__academy_subscribers')
                ->set('name=' . $db->quote($name))
                ->set('email=' . $db->quote($email))
                ->set('state=' . (int) $state)
                ->where('id=' . $id)
        )->execute();

        $app->enqueueMessage('Subscriber saved.');
        $app->redirect(Route::_('index.php?option=com_academy&view=subscribers', false));
    }

    public function cancel(): void
    {
        Factory::getApplication()->redirect(Route::_('index.php?option=com_academy&view=subscribers', false));
    }
}
