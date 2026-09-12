<?php
namespace Joomla\Component\Blog\Administrator\View\Category;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Blog\Administrator\Helper\CategoriesHelper;

final class HtmlView extends BaseHtmlView
{
    public $item;
    public array $parentOptions = [];
    public array $languageOptions = [];

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_blog')) {
            throw new \RuntimeException('Not authorised', 403);
        }

        $id = $app->getInput()->getInt('id');
        $db = CategoriesHelper::db();
        $this->item = $id
            ? $db->setQuery('SELECT * FROM #__blog_categories WHERE id=' . $id)->loadObject()
            : (object) ['id' => 0, 'title' => '', 'alias' => '', 'description' => '', 'published' => 1, 'access' => 1, 'language' => '*', 'parent_id' => 0, 'allow_autoposting' => 1, 'default_image' => '', 'default_tags' => ''];

        if ($id && !$this->item) {
            throw new \RuntimeException('Category not found.', 404);
        }

        $this->parentOptions = CategoriesHelper::parentOptions((int) $this->item->id);
        $this->languageOptions = LanguageHelper::getContentLanguages([1], false);

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $isNew = empty($this->item->id);

        ToolbarHelper::title(Text::_($isNew ? 'COM_BLOG_ADD_CATEGORY' : 'COM_BLOG_EDIT_CATEGORY'), 'copy');

        $toolbar = $this->getDocument()->getToolbar();
        $toolbar->apply('category.apply');
        $toolbar->save('category.save');

        if ($isNew) {
            $toolbar->save2new('category.save2new');
        }

        $toolbar->cancel('category.cancel');
    }
}
