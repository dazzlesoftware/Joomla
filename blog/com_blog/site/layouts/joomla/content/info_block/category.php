<?php

/**
 * Category info-block sublayout for the post-family components.
 *
 * Overrides Joomla core's version, which links through com_content's own
 * RouteHelper (wrong component - our categories live in a native
 * #__<family>_categories table, not the shared #__categories table).
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Blog\Site\Helper\RouteHelper;

?>
<dd class="category-name">
    <?php echo LayoutHelper::render('joomla.icon.iconclass', ['icon' => 'icon-folder-open icon-fw']); ?>
    <?php $title = $this->escape($displayData['item']->category_title); ?>
    <?php if ($displayData['params']->get('link_category') && !empty($displayData['item']->catid)) : ?>
        <?php $url = '<a href="' . Route::_(
            RouteHelper::getCategoryRoute($displayData['item']->catid, $displayData['item']->category_language)
        )
            . '">' . $title . '</a>'; ?>
        <?php echo Text::sprintf('COM_BLOG_CATEGORY', $url); ?>
    <?php else : ?>
        <?php echo Text::sprintf('COM_BLOG_CATEGORY', '<span>' . $title . '</span>'); ?>
    <?php endif; ?>
</dd>
