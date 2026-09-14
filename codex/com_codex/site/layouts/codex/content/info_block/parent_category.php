<?php

/**
 * Parent-category info-block sublayout for the post-family components.
 *
 * Overrides Joomla core's version, which links through com_content's own
 * RouteHelper (wrong component - our categories live in a native
 * #__<family>_categories table, not the shared #__categories table).
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Codex\Site\Helper\RouteHelper;

?>
<dd class="parent-category-name">
    <?php echo LayoutHelper::render('codex.icon.iconclass', ['icon' => 'icon-folder icon-fw']); ?>
    <?php $title = $this->escape($displayData['item']->parent_title); ?>
    <?php if ($displayData['params']->get('link_parent_category') && !empty($displayData['item']->parent_id)) : ?>
        <?php $url = '<a href="' . Route::_(
            RouteHelper::getCategoryRoute($displayData['item']->parent_id, $displayData['item']->parent_language)
        )
            . '">' . $title . '</a>'; ?>
        <?php echo Text::sprintf('COM_CODEX_PARENT', $url); ?>
    <?php else : ?>
        <?php echo Text::sprintf('COM_CODEX_PARENT', '<span>' . $title . '</span>'); ?>
    <?php endif; ?>
</dd>
