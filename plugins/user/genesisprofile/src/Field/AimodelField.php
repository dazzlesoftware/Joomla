<?php

namespace Joomla\Plugin\User\GenesisProfile\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** Reuses the installed components' model catalogs, including catalog plugins. */
class AimodelField extends ListField
{
    protected $type = 'Aimodel';

    protected function getOptions()
    {
        $models = [];
        foreach (['Academy', 'Blog', 'Codex'] as $family) {
            $registry = 'Joomla\\Component\\' . $family . '\\Administrator\\Helper\\NeuralNetworkModelRegistry';
            if (ComponentHelper::isEnabled('com_' . strtolower($family)) && class_exists($registry)) {
                $models += $registry::models((string) $this->element['provider'], (string) $this->element['kind']);
            }
        }
        // Keep existing custom IDs selectable when reopening the profile.
        if (is_string($this->value) && preg_match('~^[a-zA-Z0-9][a-zA-Z0-9._:/-]{0,255}$~D', $this->value)
            && !isset($models[$this->value])) {
            $models[$this->value] = $this->value;
        }
        $options = parent::getOptions();
        foreach ($models as $id => $label) {
            $options[] = HTMLHelper::_('select.option', $id, $label);
        }
        $options[] = HTMLHelper::_('select.option', '__custom__', Text::_('PLG_USER_GENESISPROFILE_AI_CUSTOM'));
        return $options;
    }
}
