<?php
namespace Joomla\Component\Codex\Administrator\Field;
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;

/** Show component XML defaults when a global setting has not been saved yet. */
class GlobaldefaultlistField extends ListField
{
    protected $type = 'Globaldefaultlist';

    protected function getOptions()
    {
        $options = parent::getOptions();
        if (!$this->element['useglobal']) { return $options; }
        $component = (string) $this->element['globalcomponent'];
        if (!in_array($component, ['com_academy', 'com_blog', 'com_codex'], true)) { return $options; }
        $value = ComponentHelper::getParams($component)->get($this->fieldname);
        if ($value === null) {
            static $cache = [];
            $defaults = $cache[$component] ?? null;
            if ($defaults === null) {
                $defaults = [];
                $xml = simplexml_load_file(JPATH_ADMINISTRATOR . '/components/' . $component . '/forms/settings.xml');
                if ($xml) {
                    foreach ($xml->xpath('//fields[@name="params"]//field[@default]') as $field) {
                        $defaults[(string) $field['name']] = (string) $field['default'];
                    }
                }
            }
            $cache[$component] = $defaults;
            $value = $defaults[$this->fieldname] ?? null;
        }
        if ($value !== null && $value !== '') {
            foreach ($options as $option) {
                if ((string) $option->value === (string) $value) {
                    foreach ($options as $global) {
                        if ($global->value === '') {
                            $global->text = Text::sprintf('JGLOBAL_USE_GLOBAL_VALUE', $option->text);
                            $global->optionattr = ['data-global-value' => (string) $value];
                            break;
                        }
                    }
                    break;
                }
            }
        }
        return $options;
    }
}
