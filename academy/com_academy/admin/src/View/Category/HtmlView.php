<?php

namespace Joomla\Component\Academy\Administrator\View\Category;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Academy\Administrator\Helper\CategoriesHelper;

final class HtmlView extends BaseHtmlView
{
    public $item;
    public array $parentOptions = [];
    public array $languageOptions = [];

    /** @var object[] Other installed languages (excludes the item's own and "All"), for the Associations picker. */
    public array $associationLanguages = [];
    /** @var array<string, object[]> lang_code => candidate categories in that language */
    public array $associationOptions = [];
    /** @var array<string, int> lang_code => existing associated category id */
    public array $associations = [];

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_academy')) {
            throw new \RuntimeException('Not authorised', 403);
        }

        $id = $app->getInput()->getInt('id');
        $db = CategoriesHelper::db();
        $this->item = $id
            ? $db->setQuery('SELECT * FROM #__academy_categories WHERE id=' . $id)->loadObject()
            : (object) ['id' => 0, 'title' => '', 'alias' => '', 'description' => '', 'published' => 1, 'access' => 1, 'language' => '*', 'parent_id' => 0, 'is_default' => 0, 'allow_autoposting' => 1, 'default_image' => '', 'default_tags' => ''];

        if ($id && !$this->item) {
            throw new \RuntimeException('Category not found.', 404);
        }

        $this->parentOptions = CategoriesHelper::parentOptions((int) $this->item->id);
        $this->languageOptions = LanguageHelper::getContentLanguages([0, 1], false);

        if (Associations::isEnabled()) {
            $this->associationLanguages = array_values(array_filter(
                $this->languageOptions,
                fn ($lang) => $lang->lang_code !== $this->item->language
            ));

            if ($this->item->id) {
                $this->associations = CategoriesHelper::getAssociations((int) $this->item->id);
            }

            foreach ($this->associationLanguages as $lang) {
                $this->associationOptions[$lang->lang_code] = CategoriesHelper::optionsForLanguage($lang->lang_code, (int) $this->item->id);
            }
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $isNew = empty($this->item->id);

        ToolbarHelper::title(Text::_($isNew ? 'COM_ACADEMY_ADD_CATEGORY' : 'COM_ACADEMY_EDIT_CATEGORY'), 'copy');

        $toolbar = $this->getDocument()->getToolbar();
        $toolbar->apply('category.apply');
        $toolbar->save('category.save');

        if ($isNew) {
            $toolbar->save2new('category.save2new');
        }

        $toolbar->cancel('category.cancel');
    }
}
