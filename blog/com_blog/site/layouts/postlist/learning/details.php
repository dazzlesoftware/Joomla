<?php
defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Blog\Site\Helper\RouteHelper;

$item = $displayData;
?>
<div class="learning-item-details d-flex flex-column gap-2 text-muted small">
    <div class="learning-item-author d-flex align-items-center gap-2">
        <?php echo LayoutHelper::render('postlist.learning.avatar', $item, JPATH_COMPONENT . '/layouts'); ?>
        <span><?php echo htmlspecialchars((string) ($item->author ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <?php if (!empty($item->category_title)) : ?>
        <div class="learning-item-category">
            <a href="<?php echo Route::_(RouteHelper::getCategoryRoute((int) $item->catid, $item->language)); ?>"><?php echo htmlspecialchars((string) $item->category_title, ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
    <?php endif; ?>
</div>