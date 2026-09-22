<?php
namespace Joomla\Component\Codex\Administrator\Field;
defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Component\Codex\Administrator\Helper\NeuralNetworkModelRegistry;

class NeuralnetworkmodelField extends ListField
{
    protected $type = 'Neuralnetworkmodel';
    protected function getOptions()
    {
        $models = NeuralNetworkModelRegistry::models((string) $this->element['provider'], (string) $this->element['kind']);
        // Preserve previously saved model IDs and models from a disabled plugin.
        if ($this->value !== '__custom__' && NeuralNetworkModelRegistry::validId($this->value) && !isset($models[$this->value])) {
            $models[$this->value] = $this->value . ' (saved model)';
        }
        $options = [];
        foreach ($models as $id=>$label) { $options[] = HTMLHelper::_('select.option', $id, $label); }
        $options[] = HTMLHelper::_('select.option', '__custom__', 'Custom');
        return array_merge(parent::getOptions(), $options);
    }
}
