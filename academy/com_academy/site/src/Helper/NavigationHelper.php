<?php
namespace Joomla\Component\Academy\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

/** Resolve the toolbar home without changing the site's default menu item. */
final class NavigationHelper
{
    public static function home(?Registry $overrides = null): array
    {
        $app = Factory::getApplication();
        $global = ComponentHelper::getParams('com_academy');
        $setting = static function (string $key, $default) use ($overrides, $global) {
            $value = $overrides?->get($key);
            return $value !== null && $value !== '' ? $value : $global->get($key, $default);
        };
        $destination = (string) $setting('postnav_home', 'featured');
        $input = $app->getInput();
        if ($destination === 'menu') {
            $id = (int) $setting('postnav_home_menu', 0);
            $menu = $app->getMenu();
            $item = $menu->getItem($id);
            $seen = [];
            // Follow menu aliases, but never loop or use inaccessible targets.
            while ($item && !isset($seen[$item->id])) {
                $seen[$item->id] = true;
                if (!in_array((int) $item->access, $app->getIdentity()->getAuthorisedViewLevels(), true)
                    || !in_array($item->language, ['*', $app->getLanguage()->getTag()], true)) {
                    break;
                }
                if ($item->type === 'alias') {
                    $item = $menu->getItem((int) $item->getParams()->get('aliasoptions'));
                    continue;
                }
                if ($item->type === 'component') {
                    $active = in_array((int) ($menu->getActive()->id ?? 0), [$id, (int) $item->id], true);
                    foreach ($item->query as $key => $value) {
                        if ($key !== 'Itemid' && is_scalar($value) && $input->getString($key, '') !== (string) $value) {
                            $active = false;
                        }
                    }
                    return ['url' => $item->link . '&Itemid=' . (int) $item->id, 'active' => $active];
                }
                if ($item->type === 'url' && preg_match('~^https?://~i', $item->link)) {
                    return ['url' => $item->link, 'active' => false];
                }
                break;
            }
            // A deleted, unpublished, or unavailable menu target falls back to Featured.
            $destination = 'featured';
        }
        if (!in_array($destination, ['featured', 'categories', 'authors', 'archive'], true)) {
            $destination = 'featured';
        }
        return [
            'url' => 'index.php?option=com_academy&view=' . $destination,
            'active' => $input->getCmd('option') === 'com_academy' && $input->getCmd('view', 'featured') === $destination,
        ];
    }
}
