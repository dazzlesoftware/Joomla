<?php
namespace Joomla\Component\Blog\Site\Helper;
defined('_JEXEC') or die;
final class ListingSettingsHelper
{
    public static function count($params): int
    {
        $count = $params->get('posts_per_page');
        // Compatibility for saved settings from versions with two post counts.
        if ($count === null || $count === '') {
            $count = max(0, (int) $params->get('num_leading_posts', 1)) + max(0, (int) $params->get('num_intro_posts', 4));
        }
        return max(1, (int) $count);
    }
}
