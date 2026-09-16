<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Academy\Site\Helper\RouteHelper;
$item = $displayData['item'];
$params = $displayData['params'];
$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="post-details text-muted small mb-3" aria-label="Post details">
    <div class="post-details-row">
        <?php if ($params->get('show_category')) : ?>
            <span>Category: <?php if ($params->get('link_category')) : ?><a href="<?php echo Route::_(RouteHelper::getCategoryRoute($item->catid, $item->category_language)); ?>"><?php echo $escape($item->category_title); ?></a><?php else : ?><?php echo $escape($item->category_title); ?><?php endif; ?></span>
        <?php endif; ?>
        <?php if ($params->get('show_parent_category') && !empty($item->parent_id)) : ?>
            <span><?php if ($params->get('link_parent_category')) : ?><a href="<?php echo Route::_(RouteHelper::getCategoryRoute($item->parent_id, $item->parent_language)); ?>"><?php echo $escape($item->parent_title); ?></a><?php else : ?><?php echo $escape($item->parent_title); ?><?php endif; ?></span>
        <?php endif; ?>
        <?php if ($params->get('show_author') && !empty($item->author)) : ?>
            <span>Written by: <a href="<?php echo Route::_('index.php?option=com_academy&view=author&id=' . (int) $item->created_by); ?>"><?php echo $escape($item->created_by_alias ?: $item->author); ?></a></span>
        <?php endif; ?>
        <?php if ($params->get('show_publish_date')) : ?>
            <span>Published: <time datetime="<?php echo $escape(Factory::getDate($item->publish_up ?: $item->created)->toISO8601()); ?>"><?php echo HTMLHelper::_('date', $item->publish_up ?: $item->created, 'DATE_FORMAT_LC1'); ?></time></span>
        <?php endif; ?>
        <?php if ($params->get('show_hits')) : ?><span><?php echo (int) $item->hits; ?> Hits</span><?php endif; ?>
    </div>
    <?php if ($params->get('show_associations')) : ?>
        <dl class="mb-0"><?php echo \Joomla\CMS\Layout\LayoutHelper::render('academy.content.info_block.associations', $displayData, __DIR__); ?></dl>
    <?php endif; ?>
</div>
