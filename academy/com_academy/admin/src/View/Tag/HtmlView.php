<?php
namespace Joomla\Component\Academy\Administrator\View\Tag;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Academy\Administrator\Helper\TagsHelper;

final class HtmlView extends BaseHtmlView
{
    public $item;
    public array $languageOptions = [];

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id');
        if (!$app->getIdentity()->authorise($id ? 'core.edit' : 'core.create', 'com_academy')) {
            throw new \RuntimeException('Not authorised', 403);
        }

        $db = TagsHelper::db();
        $this->item = $id
            ? $db->setQuery('SELECT * FROM #__academy_tags WHERE id=' . $id)->loadObject()
            : (object) ['id' => 0, 'title' => '', 'alias' => '', 'description' => '', 'published' => 1, 'access' => 1, 'language' => '*', 'is_default' => 0];

        if ($id && !$this->item) {
            throw new \RuntimeException('Tag not found.', 404);
        }

        $this->languageOptions = LanguageHelper::getContentLanguages([0, 1], false);

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $isNew = empty($this->item->id);

        ToolbarHelper::title(Text::_($isNew ? 'COM_ACADEMY_ADD_TAG' : 'COM_ACADEMY_EDIT_TAG'), 'tag');

        $toolbar = $this->getDocument()->getToolbar();
        $toolbar->apply('tag.apply');
        $toolbar->save('tag.save');

        if ($isNew) {
            $toolbar->save2new('tag.save2new');
        }

        $toolbar->cancel('tag.cancel');
    }
}
