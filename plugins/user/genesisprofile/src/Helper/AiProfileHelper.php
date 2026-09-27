<?php

namespace Joomla\Plugin\User\GenesisProfile\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/** Personal AI credentials shared by the Genesis component family. */
final class AiProfileHelper
{
    public static function isAdministrator(User $user, string $component): bool
    {
        return !$user->guest && ($user->authorise('core.admin')
            || ($user->authorise('core.login.admin') && $user->authorise('core.manage', $component)));
    }

    public static function eligible(User $user, Registry $params): bool
    {
        // Joomla's Editor group is the initial default; inherited groups are included.
        return !$user->guest && (bool) array_intersect(
            array_map('intval', (array) $params->get('ai_frontend_groups', [4])),
            $user->getAuthorisedGroups()
        );
    }

    public static function canConfigure(User $user): bool
    {
        foreach (['com_academy', 'com_blog', 'com_codex'] as $component) {
            if (!ComponentHelper::isEnabled($component)) {
                continue;
            }
            $params = ComponentHelper::getParams($component);
            if ($params->get('ai_enabled', 0) && (self::isAdministrator($user, $component)
                || (self::eligible($user, $params) && $user->authorise('ai.generate', $component)))) {
                return true;
            }
        }
        return false;
    }

    public static function load(int $userId): Registry
    {
        if ($userId <= 0) {
            return new Registry();
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $value = $db->setQuery($db->getQuery(true)->select($db->quoteName('profile_value'))
            ->from($db->quoteName('#__user_profiles'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('profile_key') . ' = ' . $db->quote('genesisprofile.ai')))->loadResult();
        return new Registry((string) $value);
    }

    public static function seal(string $key): string
    {
        $iv = random_bytes(12);
        $cipher = openssl_encrypt($key, 'aes-256-gcm', hash('sha256', Factory::getApplication()->get('secret'), true), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new \RuntimeException('Could not encrypt the API key.');
        }
        return base64_encode($iv . $tag . $cipher);
    }

    public static function save(int $userId, array $fields): void
    {
        if (!$fields) {
            return;
        }
        $user = Factory::getApplication()->getIdentity();
        // Credentials belong to the signed-in user, never a profile chosen in a request.
        if ((int) $user->id !== $userId || !self::canConfigure($user)) {
            return;
        }
        $settings = self::load($userId);
        foreach (['openai', 'claude'] as $provider) {
            $key = $fields[$provider . '_key'] ?? '';
            if (!empty($fields[$provider . '_clear'])) {
                $settings->set('ai_' . $provider . '_secret', '');
            } elseif (is_string($key) && trim($key) !== '' && strlen($key) <= 2048) {
                $settings->set('ai_' . $provider . '_secret', self::seal(trim($key)));
            }
        }
        if (in_array($fields['provider'] ?? '', ['openai', 'claude'], true)) {
            $settings->set('ai_provider', $fields['provider']);
        }
        foreach (['openai_model', 'claude_model', 'image_model'] as $model) {
            $value = $fields[$model] ?? null;
            if ($value === '__custom__') {
                $value = $fields[$model . '_custom'] ?? null;
                $value = is_string($value) ? trim($value) : null;
            }
            if (is_string($value) && ($value === '' || preg_match('~^[a-zA-Z0-9][a-zA-Z0-9._:/-]{0,255}$~D', $value))) {
                $settings->set('ai_' . $model, $value);
            }
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->transactionStart();
        try {
            $db->setQuery($db->getQuery(true)->delete($db->quoteName('#__user_profiles'))
                ->where($db->quoteName('user_id') . ' = ' . $userId)
                ->where($db->quoteName('profile_key') . ' = ' . $db->quote('genesisprofile.ai')))->execute();
            $row = (object) ['user_id' => $userId, 'profile_key' => 'genesisprofile.ai',
                'profile_value' => $settings->toString(), 'ordering' => 100];
            $db->insertObject('#__user_profiles', $row);
            $db->transactionCommit();
        } catch (\Throwable $error) {
            $db->transactionRollback();
            throw $error;
        }
    }
}
