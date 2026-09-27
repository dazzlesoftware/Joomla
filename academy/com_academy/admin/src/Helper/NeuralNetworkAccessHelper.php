<?php

namespace Joomla\Component\Academy\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\User;
use Joomla\Plugin\User\GenesisProfile\Helper\AiProfileHelper;
use Joomla\Registry\Registry;

/** Resolves the same frontend AI policy for the editor and controller. */
final class NeuralNetworkAccessHelper
{
    public static function settings(Registry $params, User $user, bool $image = false): ?Registry
    {
        if ($user->guest || !$params->get('ai_enabled', 0) || !$user->authorise('ai.generate', 'com_academy')) {
            return null;
        }
        if ($image && (!$params->get('ai_images', 0) || !$user->authorise('core.create', 'com_media'))) {
            return null;
        }
        $settings = clone $params;
        if (Factory::getApplication()->isClient('administrator') || $user->authorise('core.admin')
            || ($user->authorise('core.login.admin') && $user->authorise('core.manage', 'com_academy'))) {
            return $settings;
        }
        if (!PluginHelper::isEnabled('user', 'genesisprofile') || !class_exists(AiProfileHelper::class)
            || !AiProfileHelper::eligible($user, $params)) {
            return null;
        }
        $personal = AiProfileHelper::load((int) $user->id);
        $provider = $image ? 'openai' : (string) $personal->get('ai_provider', 'openai');
        if (!in_array($provider, ['openai', 'claude'], true) || !$personal->get('ai_' . $provider . '_secret', '')) {
            return null;
        }
        // Do not inherit site credentials, including environment variables.
        $settings->set('ai_personal_credentials', true);
        foreach (['openai', 'claude'] as $name) {
            $settings->set('ai_' . $name . '_secret', (string) $personal->get('ai_' . $name . '_secret', ''));
        }
        $settings->set('ai_provider', $personal->get('ai_provider', 'openai'));
        foreach (['ai_openai_model', 'ai_claude_model', 'ai_image_model'] as $model) {
            if ($personal->get($model, '') !== '') {
                $settings->set($model, $personal->get($model));
            }
        }
        return $settings;
    }
}
