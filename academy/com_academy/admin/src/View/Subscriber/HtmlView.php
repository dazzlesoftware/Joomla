<?php
namespace Joomla\Component\Academy\Administrator\View\Subscriber;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public object $item;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_academy')) {
            throw new \RuntimeException('Not authorised', 403);
        }

        $id = $app->getInput()->getInt('id');
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $item = $db->setQuery(
            $db->createQuery()->select('*')->from('#__academy_subscribers')->where('id=' . $id)
        )->loadObject();

        if (!$item) {
            throw new \RuntimeException('Subscriber not found.', 404);
        }

        $this->item = $item;

        ToolbarHelper::title('Academy Subscriber', 'user');

        parent::display($tpl);
    }
}
