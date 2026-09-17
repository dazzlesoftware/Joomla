<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Codex\Site\Helper\RouteHelper;

$item = $displayData;
$date = $item->publish_up ?: $item->created;
$category = trim((string) ($item->category_title ?? ''));
?>
<footer class="post-card-footer d-flex align-items-end justify-content-between gap-3 border-top pt-3 mt-3 text-muted">
    <div class="post-card-footer-details d-flex flex-column gap-1 small">
        <?php if ($date) : ?>
            <time class="post-card-footer-date" datetime="<?php echo HTMLHelper::_('date', $date, 'c'); ?>">
                <?php echo HTMLHelper::_('date', $date, 'l, d F Y'); ?>
            </time>
        <?php endif; ?>
        <?php if ($category !== '') : ?>
            <span class="post-card-footer-category">
                <a href="<?php echo Route::_(RouteHelper::getCategoryRoute((int) $item->catid, $item->language)); ?>"><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></a>
            </span>
        <?php endif; ?>
    </div>
    <span class="post-card-footer-author ms-auto">
        <?php echo LayoutHelper::render('postlist.card.avatar', $item, JPATH_COMPONENT . '/layouts'); ?>
    </span>
</footer>