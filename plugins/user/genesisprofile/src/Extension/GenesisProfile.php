<?php

namespace Joomla\Plugin\User\GenesisProfile\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Event\User\AfterSaveEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;
use Joomla\Utilities\ArrayHelper;

/**
 * Adds a Profile Picture upload to the user account form, and generically
 * persists any other simple fields later added to forms/genesisprofile.xml
 * under the same 'genesisprofile.<name>' key namespace.
 */
final class GenesisProfile extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepareForm' => 'onContentPrepareForm',
            'onUserAfterSave'      => 'onUserAfterSave',
        ];
    }

    public function onContentPrepareForm(PrepareFormEvent $event): void
    {
        $form = $event->getForm();
        $name = $form->getName();

        if (!\in_array($name, ['com_users.user', 'com_users.profile', 'com_users.registration'], true)) {
            return;
        }

        $this->loadLanguage();

        FormHelper::addFieldPrefix('Joomla\\Plugin\\User\\GenesisProfile\\Field');
        FormHelper::addFormPath(JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/forms');
        $form->loadFile('genesisprofile');

        $form->setFieldAttribute('image', 'max_size', (int) $this->params->get('max_size', 8), 'genesisprofile');
        $form->setFieldAttribute('image', 'dimension', (int) $this->params->get('dimension', 200), 'genesisprofile');
    }

    public function onUserAfterSave(AfterSaveEvent $event): void
    {
        $data   = $event->getUser();
        $result = $event->getSavingResult();
        $userId = ArrayHelper::getValue($data, 'id', 0, 'int');

        if (!$userId || !$result) {
            return;
        }

        $this->processAvatarUpload($userId);
        $this->processOtherFields($userId, (array) ($data['genesisprofile'] ?? []));
    }

    private function processAvatarUpload(int $userId): void
    {
        $app   = $this->getApplication();
        $files = $app->getInput()->files->get('jform', [], 'array');
        $file  = $files['genesisprofile']['image'] ?? null;

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            // Nothing uploaded this time - leave any existing avatar in place.
            return;
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $app->enqueueMessage(Text::_('PLG_USER_GENESISPROFILE_ERROR_UPLOAD_FAILED'), 'warning');
            return;
        }

        $maxBytes = (int) $this->params->get('max_size', 8) * 1024 * 1024;

        if ((int) $file['size'] <= 0 || (int) $file['size'] > $maxBytes) {
            $app->enqueueMessage(Text::_('PLG_USER_GENESISPROFILE_ERROR_TOO_LARGE'), 'warning');
            return;
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return;
        }

        $mime = @mime_content_type($file['tmp_name']) ?: '';

        $decoders = [
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png'  => 'imagecreatefrompng',
            'image/gif'  => 'imagecreatefromgif',
        ];

        if (!isset($decoders[$mime])) {
            $app->enqueueMessage(Text::_('PLG_USER_GENESISPROFILE_ERROR_TYPE'), 'warning');
            return;
        }

        $source = @\call_user_func($decoders[$mime], $file['tmp_name']);

        if (!$source) {
            $app->enqueueMessage(Text::_('PLG_USER_GENESISPROFILE_ERROR_TYPE'), 'warning');
            return;
        }

        $dimension = (int) $this->params->get('dimension', 200);
        $canvas    = imagecreatetruecolor($dimension, $dimension);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $dimension, $dimension, $transparent);

        // Crop to a centered square, then resize into the canvas.
        $sourceWidth  = imagesx($source);
        $sourceHeight = imagesy($source);
        $sourceSize   = min($sourceWidth, $sourceHeight);
        $sourceX      = (int) (($sourceWidth - $sourceSize) / 2);
        $sourceY      = (int) (($sourceHeight - $sourceSize) / 2);

        imagecopyresampled($canvas, $source, 0, 0, $sourceX, $sourceY, $dimension, $dimension, $sourceSize, $sourceSize);
        imagedestroy($source);

        $folder         = 'images/genesisprofile';
        $absoluteFolder = JPATH_ROOT . '/' . $folder;

        if (!is_dir($absoluteFolder) && !mkdir($absoluteFolder, 0755, true) && !is_dir($absoluteFolder)) {
            imagedestroy($canvas);
            $app->enqueueMessage(Text::_('PLG_USER_GENESISPROFILE_ERROR_UPLOAD_FAILED'), 'warning');
            return;
        }

        $filename     = 'user-' . $userId . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.png';
        $absolutePath = $absoluteFolder . '/' . $filename;

        imagepng($canvas, $absolutePath);
        imagedestroy($canvas);

        $relativePath = $folder . '/' . $filename;

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // Remove the previous avatar file, then store the new one under 'genesisprofile.avatar'.
        $previous = (string) $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName('profile_value'))
                ->from($db->quoteName('#__user_profiles'))
                ->where($db->quoteName('user_id') . ' = :userid')
                ->where($db->quoteName('profile_key') . ' = ' . $db->quote('genesisprofile.avatar'))
                ->bind(':userid', $userId, ParameterType::INTEGER)
        )->loadResult();

        if ($previous !== '') {
            $previousPath = json_decode($previous, true);
            $previousPath = is_string($previousPath) ? $previousPath : $previous;
            $previousAbsolute = JPATH_ROOT . '/' . ltrim($previousPath, '/');

            if ($previousPath !== '' && is_file($previousAbsolute)) {
                @unlink($previousAbsolute);
            }
        }

        $this->upsertProfileValue($db, $userId, 'genesisprofile.avatar', json_encode($relativePath));

        $app->enqueueMessage(Text::_('PLG_USER_GENESISPROFILE_UPLOAD_SUCCESS'));
    }

    /**
     * Persists any other simple fields defined later in forms/genesisprofile.xml.
     * File uploads (like the avatar) never arrive here - only normal field
     * values do - so this never touches the 'avatar' key.
     */
    private function processOtherFields(int $userId, array $fields): void
    {
        unset($fields['image']);

        if (!$fields) {
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        foreach ($fields as $name => $value) {
            $this->upsertProfileValue($db, $userId, 'genesisprofile.' . $name, json_encode($value));
        }
    }

    private function upsertProfileValue(DatabaseInterface $db, int $userId, string $key, string $jsonValue): void
    {
        $db->setQuery(
            $db->createQuery()
                ->delete($db->quoteName('#__user_profiles'))
                ->where($db->quoteName('user_id') . ' = :userid')
                ->where($db->quoteName('profile_key') . ' = :key')
                ->bind(':userid', $userId, ParameterType::INTEGER)
                ->bind(':key', $key, ParameterType::STRING)
        )->execute();

        $order = (int) $db->setQuery(
            $db->createQuery()
                ->select('MAX(' . $db->quoteName('ordering') . ')')
                ->from($db->quoteName('#__user_profiles'))
                ->where($db->quoteName('user_id') . ' = :userid')
                ->bind(':userid', $userId, ParameterType::INTEGER)
        )->loadResult();

        $row = (object) [
            'user_id'       => $userId,
            'profile_key'   => $key,
            'profile_value' => $jsonValue,
            'ordering'      => $order + 1,
        ];
        $db->insertObject('#__user_profiles', $row);
    }
}
