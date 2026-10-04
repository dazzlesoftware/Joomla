<?php
namespace Joomla\Component\Codex\Site\Service;
defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\Rules\MenuRules;

/** Preserve explicit post-listing context when routing its individual posts. */
final class FeaturedMenuRules extends MenuRules
{
    public function preprocess(&$query)
    {
        $item = isset($query['Itemid']) ? $this->router->menu->getItem((int) $query['Itemid']) : null;
        if (($query['view'] ?? '') === 'post' && $item
            && ($item->query['option'] ?? '') === 'com_codex'
            && in_array($item->query['view'] ?? '', ['featured', 'archive', 'author', 'tags'], true)) {
            return;
        }
        parent::preprocess($query);
    }
}
