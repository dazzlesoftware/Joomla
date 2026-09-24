<?php

namespace Joomla\Component\Blog\Site\Service;

use Joomla\CMS\Component\Router\Rules\StandardRules;

\defined('_JEXEC') or die;

/** Handle posts directly beneath a category-directory menu's base category. */
class CategoryDirectoryRules extends StandardRules
{
    public function parse(&$segments, &$vars)
    {
        $active = $this->router->menu->getActive();
        $query = array_merge($active->query ?? [], $vars);

        if ($segments && ($query['view'] ?? '') === 'categories' && !empty($query['id'])
            && !$this->router->getCategoryId($segments[0], $query)
            && $this->router->getPostId($segments[0], $query)) {
            // URL building omits the base category segment. Parse its post child
            // from that same category, while giving real subcategories priority.
            $vars['view'] = 'category';
        }

        parent::parse($segments, $vars);
    }
}
