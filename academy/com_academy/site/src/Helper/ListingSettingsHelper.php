<?php
namespace Joomla\Component\Academy\Site\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
final class ListingSettingsHelper
{
    public static function count($params): int
    {
        $source = (string) $params->get('items_limit_source', 'custom');
        if ($source === '' || $source === 'component') {
            $params = ComponentHelper::getParams('com_academy');
            $source = (string) $params->get('items_limit_source', 'custom');
        }
        if ($source === 'joomla') { return max(1, (int) Factory::getApplication()->get('list_limit', 20)); }
        if (ctype_digit($source) && (int) $source > 0) { return (int) $source; }
        $count = $params->get('posts_per_page');
        if ($count === null || $count === '') {
            $count = max(0, (int) $params->get('num_leading_posts', 1)) + max(0, (int) $params->get('num_intro_posts', 4));
        }
        return max(1, (int) $count);
    }
}
