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
<footer class="post-card-footer">
    <div class="post-card-footer-details">
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
    <span class="post-card-footer-author">
        <?php echo LayoutHelper::render('postlist.card.avatar', $item, JPATH_COMPONENT . '/layouts'); ?>
    </span>
</footer>