<?php

namespace Joomla\Component\Blog\Administrator\Field;

defined('_JEXEC') or die;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Factory;
use Joomla\Component\Blog\Administrator\Helper\TagsHelper;

class PosttagsField extends ListField
{
    protected $type = 'Posttags';
    protected $layout = 'joomla.form.field.list-fancy-select';
    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        if ($value instanceof TagsHelper) {
            $value = $value->tags;
        }
        if (is_string($value) && $value !== '') {
            $value = explode(',', $value);
        }
        return parent::setup($element, $value, $group);
    }
    protected function getOptions()
    {
        $db = TagsHelper::db();
        $q = $db->createQuery()->select('id AS value, title AS text')->from('#__blog_tags')->order('title');
        if (!Factory::getApplication()->isClient('administrator')) {
            $q->where('published=1')->whereIn('access', Factory::getApplication()->getIdentity()->getAuthorisedViewLevels());
            $language = Factory::getApplication()->getLanguage();
            if ($language) {
                $q->where('language IN (' . $db->quote('*') . ',' . $db->quote($language->getTag()) . ')');
            }
        }
        return array_merge(parent::getOptions(), $db->setQuery($q)->loadObjectList());
    }
    protected function getInput()
    {
        $html = parent::getInput();
        if ($this->fieldname === 'tags' && $this->multiple && !$this->disabled && !$this->readonly) {
            // An empty selection must clear assignments rather than omit the field.
            $html = '<input type="hidden" name="' . htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8') . '" value="">' . $html;
            if (Factory::getApplication()->getIdentity()->authorise('core.create', 'com_blog')) {
                $name = preg_replace('/\[tags\]\[\]$/', '[new_tag_titles]', $this->name);
                $html .= '<label class="small mt-2" for="' . $this->id . '_new">New tags (comma separated)</label><input class="form-control" id="' . $this->id . '_new" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" type="text" placeholder="Add new tags">';
            }
        }
        return $html;
    }
}
