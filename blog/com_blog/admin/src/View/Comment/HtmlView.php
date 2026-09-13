<?php

namespace Joomla\Component\Blog\Administrator\View\Comment;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public $item;
    public ?Form $postField = null;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id');
        if (!$app->getIdentity()->authorise($id ? 'core.edit' : 'core.create', 'com_blog')) {
            throw new \RuntimeException('Not authorised', 403);
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->item = $id
            ? $db->setQuery('SELECT * FROM #__blog_comments WHERE id=' . $id)->loadObject()
            : (object) ['id' => 0, 'post_id' => 0, 'parent_id' => 0, 'user_id' => 0, 'name' => '', 'email' => '', 'body' => '', 'state' => 0, 'created' => ''];

        if ($id && !$this->item) {
            throw new \RuntimeException('Comment not found.', 404);
        }

        // The post picker reuses the same modal post field posts already use
        // elsewhere in this component (search/select/add-new/edit).
        $this->postField = new Form('blog.comment', ['control' => 'jform']);
        $this->postField->load(
            '<form><fieldset addfieldprefix="Joomla\Component\Blog\Administrator\Field">'
            . '<field name="post_id" type="modal_post" label="COM_BLOG_FIELD_COMMENT_POST_LABEL" required="true" select="true" new="false" edit="true" clear="true" />'
            . '</fieldset></form>'
        );
        $this->postField->bind(['post_id' => (int) $this->item->post_id]);

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $isNew = empty($this->item->id);

        ToolbarHelper::title(Text::_($isNew ? 'COM_BLOG_ADD_COMMENT' : 'COM_BLOG_EDIT_COMMENT'), 'comments');

        $toolbar = $this->getDocument()->getToolbar();
        $toolbar->apply('comment.apply');
        $toolbar->save('comment.save');

        if ($isNew) {
            $toolbar->save2new('comment.save2new');
        }

        $toolbar->cancel('comment.cancel');
    }
}
