<?php

namespace Joomla\Component\Codex\Site\View\MyPosts;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public ?Registry $params = null;

    public function display($tpl = null): void
    {
        $app  = Factory::getApplication();
        $user = $app->getIdentity();

        if ($user->guest) {
            $return = base64_encode(Route::_('index.php?option=com_codex&view=myposts', false));
            $app->enqueueMessage(Text::_('COM_CODEX_MYPOSTS_LOGIN_REQUIRED'), 'warning');
            $app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $return, false));

            return;
        }

        $this->params = $app->getParams();

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
            ->select(['id', 'title', 'alias', 'catid', 'state', 'publish_up', 'created', 'modified', 'language'])
            ->from('#__codex')
            ->where('created_by=' . (int) $user->id)
            ->where('state != -2')
            ->order('modified DESC, created DESC');
        $this->items = $db->setQuery($query)->loadObjectList() ?: [];

        $this->getDocument()->setTitle(Text::_('COM_CODEX_MY_POSTS_LABEL'));
        parent::display($tpl);
    }
}
