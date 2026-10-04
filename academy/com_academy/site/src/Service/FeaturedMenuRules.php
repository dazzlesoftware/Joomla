<?php
namespace Joomla\Component\Academy\Site\Service;
defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\Rules\MenuRules;

/** Preserve explicit Featured context when routing its individual posts. */
final class FeaturedMenuRules extends MenuRules
{
    public function preprocess(&$query)
    {
        $item = isset($query['Itemid']) ? $this->router->menu->getItem((int) $query['Itemid']) : null;
        if (($query['view'] ?? '') === 'post' && $item
            && ($item->query['option'] ?? '') === 'com_academy'
            && ($item->query['view'] ?? '') === 'featured') {
            return;
        }
        parent::preprocess($query);
    }
}
