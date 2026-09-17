<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Codex\Site\Helper\RouteHelper;
$item = $displayData['item'];
$params = $displayData['params'];
$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="post-details text-muted small mb-3" aria-label="Post details">
    <div class="post-details-row d-flex flex-wrap align-items-baseline gap-2">
        <?php if ($params->get('show_category')) : ?>
            <span><span class="fa-solid fa-folder-open me-1" aria-hidden="true"></span>Category: <?php if ($params->get('link_category')) : ?><a href="<?php echo Route::_(RouteHelper::getCategoryRoute($item->catid, $item->category_language)); ?>"><?php echo $escape($item->category_title); ?></a><?php else : ?><?php echo $escape($item->category_title); ?><?php endif; ?></span>
        <?php endif; ?>
        <?php if ($params->get('show_parent_category') && !empty($item->parent_id)) : ?>
            <span><span class="fa-solid fa-folder-open me-1" aria-hidden="true"></span><?php if ($params->get('link_parent_category')) : ?><a href="<?php echo Route::_(RouteHelper::getCategoryRoute($item->parent_id, $item->parent_language)); ?>"><?php echo $escape($item->parent_title); ?></a><?php else : ?><?php echo $escape($item->parent_title); ?><?php endif; ?></span>
        <?php endif; ?>
        <?php if ($params->get('show_author') && !empty($item->author)) : ?>
            <span><span class="fa-solid fa-user me-1" aria-hidden="true"></span>Written by: <a href="<?php echo Route::_('index.php?option=com_codex&view=author&id=' . (int) $item->created_by); ?>"><?php echo $escape($item->created_by_alias ?: $item->author); ?></a></span>
        <?php endif; ?>
        <?php if ($params->get('show_hits')) : ?><span><span class="fa-solid fa-eye me-1" aria-hidden="true"></span><?php echo (int) $item->hits; ?> Hits</span><?php endif; ?>
    </div>
    <?php if ($params->get('show_associations')) : ?>
        <dl class="mb-0"><?php echo \Joomla\CMS\Layout\LayoutHelper::render('codex.content.info_block.associations', $displayData, __DIR__); ?></dl>
    <?php endif; ?>
</div>
