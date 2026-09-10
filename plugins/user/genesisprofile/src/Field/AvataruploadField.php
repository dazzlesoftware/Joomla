<?php
namespace Joomla\Plugin\User\GenesisProfile\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Plugin\User\GenesisProfile\Helper\GenesisProfileHelper;

final class AvataruploadField extends FormField
{
    protected $type = 'Avatarupload';

    protected function getInput()
    {
        $userId = (int) ($this->form->getValue('id') ?: 0);
        $email  = (string) ($this->form->getValue('email') ?: '');

        $preview = GenesisProfileHelper::renderImgTag($userId, $email, 64, ['class' => 'plg-genesisprofile-avatar-preview']);

        $maxSize   = (int) ($this->element['max_size'] ?? 8);
        $dimension = (int) ($this->element['dimension'] ?? 200);

        $hint = htmlspecialchars(
            Text::sprintf('PLG_USER_GENESISPROFILE_FIELD_IMAGE_HINT', $maxSize, $dimension, $dimension),
            ENT_QUOTES,
            'UTF-8'
        );

        $html   = [];
        $html[] = '<div class="plg-genesisprofile-avatar-field d-flex align-items-center gap-3">';
        $html[] = $preview;
        $html[] = '<div>';
        $html[] = '<input type="file" name="' . $this->name . '" id="' . $this->id . '" class="form-control" accept="image/jpeg,image/png,image/gif">';
        $html[] = '<p class="text-muted small mb-0 mt-1">' . $hint . '</p>';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '<style>.plg-genesisprofile-avatar-preview{width:64px;height:64px;border-radius:.375rem;object-fit:cover;background:#e9ecef}</style>';

        return implode('', $html);
    }
}
