<?php
namespace Joomla\Component\Academy\Administrator\View\Comments;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];

    public function display($tpl = null): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $q = $db->createQuery()
            ->select('c.*,p.title AS post_title')
            ->from('#__academy_comments AS c')
            ->join('LEFT', '#__academy AS p ON p.id=c.post_id')
            ->order('c.created DESC');
        $this->items = $db->setQuery($q)->loadObjectList();

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $user = $this->getCurrentUser();
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_ACADEMY_COMMENTS_TITLE'), 'comments');

        if ($user->authorise('core.create', 'com_academy')) {
            $toolbar->addNew('comments.add');
        }

        if ($user->authorise('core.edit.state', 'com_academy')) {
            $toolbar->standardButton('publish', 'COM_ACADEMY_COMMENT_APPROVE', 'comments.publish')->listCheck(true);
            $toolbar->standardButton('unpublish', 'COM_ACADEMY_COMMENT_UNPUBLISH', 'comments.unpublish')->listCheck(true);
        }

        if ($user->authorise('core.delete', 'com_academy')) {
            $toolbar->delete('comments.delete')->message('JGLOBAL_CONFIRM_DELETE')->listCheck(true);
        }

        $toolbar->help('Comments');
    }
}
