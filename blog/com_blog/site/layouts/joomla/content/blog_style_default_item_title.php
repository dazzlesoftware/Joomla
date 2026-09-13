<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Blog\Site\Helper\RouteHelper;

// Create a shortcut for params.
$params  = $displayData->params;
$listStyle = $params->get('list_item_style', 'standard');
$canEdit = $displayData->params->get('access-edit');

$currentDate = Factory::getDate()->format('Y-m-d H:i:s');
$link = RouteHelper::getPostRoute($displayData->slug, $displayData->catid, $displayData->language);
?>
<?php if ($displayData->state == 0 || $params->get('show_title', 1) || ($params->get('show_author') && !empty($displayData->author))) : ?>
    <div class="page-header">
        <?php if ($params->get('show_title', 1)) : ?>
            <h2 class="d-flex align-items-center gap-2"><?php if (!in_array($listStyle, ['card', 'learning'], true)) {
                echo LayoutHelper::render('postlist.' . $listStyle . '.avatar', $displayData, JPATH_COMPONENT . '/layouts');
            } ?><span>
                <?php if ($params->get('link_titles', 1) && ($params->get('access-view') || $params->get('show_noauth', '0') == '1')) : ?>
                    <a href="<?php echo Route::_($link); ?>">
                        <?php echo $this->escape($displayData->title); ?>
                    </a>
                <?php else : ?>
                    <?php echo $this->escape($displayData->title); ?>
                <?php endif; ?>
            </span></h2>
        <?php endif; ?>

        <?php if ($displayData->state == 0) : ?>
            <span class="badge bg-warning"><?php echo Text::_('JUNPUBLISHED'); ?></span>
        <?php endif; ?>

        <?php if ($displayData->publish_up > $currentDate) : ?>
            <span class="badge bg-warning"><?php echo Text::_('JNOTPUBLISHEDYET'); ?></span>
        <?php endif; ?>

        <?php if ($displayData->publish_down !== null && $displayData->publish_down < $currentDate) : ?>
            <span class="badge bg-warning"><?php echo Text::_('JEXPIRED'); ?></span>
        <?php endif; ?>
    </div>
<?php endif; ?>
