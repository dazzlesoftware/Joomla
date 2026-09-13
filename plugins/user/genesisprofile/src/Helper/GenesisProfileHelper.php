<?php

namespace Joomla\Plugin\User\GenesisProfile\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Resolves a user's avatar: an uploaded profile picture first, then Gravatar,
 * then a packaged placeholder image. Safe to call from any extension - this
 * class is autoloaded via the plugin's declared namespace regardless of
 * which extension is currently executing.
 */
final class GenesisProfileHelper
{
    public static function getUploadedAvatarPath(int $userId): ?string
    {
        if ($userId <= 0) {
            return null;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $value = (string) $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName('profile_value'))
                ->from($db->quoteName('#__user_profiles'))
                ->where($db->quoteName('user_id') . ' = :userid')
                ->where($db->quoteName('profile_key') . ' = ' . $db->quote('genesisprofile.avatar'))
                ->bind(':userid', $userId, ParameterType::INTEGER)
        )->loadResult();

        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        $path = is_string($decoded) ? $decoded : $value;

        if ($path === '' || !is_file(JPATH_ROOT . '/' . ltrim($path, '/'))) {
            return null;
        }

        return $path;
    }

    public static function getPlaceholderUrl(): string
    {
        return Uri::root() . 'media/plg_user_genesisprofile/images/placeholder.svg';
    }

    /**
     * Renders a complete <img> tag with the upload -> Gravatar -> placeholder
     * fallback chain. The Gravatar-to-placeholder step happens client-side
     * (onerror) so it works with no server-side network call and no caching.
     */
    public static function renderImgTag(int $userId, string $email, int $size = 200, array $attribs = []): string
    {
        $class = htmlspecialchars((string) ($attribs['class'] ?? ''), ENT_QUOTES, 'UTF-8');
        $alt   = htmlspecialchars((string) ($attribs['alt'] ?? ''), ENT_QUOTES, 'UTF-8');
        $size  = (int) $size;

        $uploaded = self::getUploadedAvatarPath($userId);

        if ($uploaded !== null) {
            $src = htmlspecialchars(Uri::root() . ltrim($uploaded, '/'), ENT_QUOTES, 'UTF-8');

            return '<img src="' . $src . '" width="' . $size . '" height="' . $size . '" class="' . $class . '" alt="' . $alt . '" loading="lazy">';
        }

        $hash        = md5(strtolower(trim($email)));
        $gravatar    = htmlspecialchars('https://www.gravatar.com/avatar/' . $hash . '?s=' . $size . '&d=404', ENT_QUOTES, 'UTF-8');
        $placeholder = htmlspecialchars(self::getPlaceholderUrl(), ENT_QUOTES, 'UTF-8');

        return '<img src="' . $gravatar . '" width="' . $size . '" height="' . $size . '" class="' . $class . '" alt="' . $alt . '" loading="lazy" '
            . 'onerror="this.onerror=null;this.src=\'' . $placeholder . '\';">';
    }

    /**
     * Reads back any 'genesisprofile.*' field for a user (added by future
     * fields in forms/genesisprofile.xml). Returns null if never set.
     */
    public static function getProfileValue(int $userId, string $field): ?string
    {
        if ($userId <= 0 || $field === '') {
            return null;
        }

        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $key = 'genesisprofile.' . $field;
        $value = $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName('profile_value'))
                ->from($db->quoteName('#__user_profiles'))
                ->where($db->quoteName('user_id') . ' = :userid')
                ->where($db->quoteName('profile_key') . ' = :key')
                ->bind(':userid', $userId, ParameterType::INTEGER)
                ->bind(':key', $key, ParameterType::STRING)
        )->loadResult();

        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode((string) $value, true);

        return is_string($decoded) ? $decoded : (string) $value;
    }
}
