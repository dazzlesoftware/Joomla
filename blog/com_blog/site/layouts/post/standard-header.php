<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Blog\Site\Helper\RouteHelper;

$item = $displayData;
$date = \Joomla\Component\Blog\Site\Helper\DateHelper::value($item, $item->params ?? null);
$category = trim((string) ($item->category_title ?? ''));
?>
<header class="d-flex align-items-start gap-2 mb-3">
    <?php if ($item->params->get('show_author', 1)) : ?>
        <div class="flex-shrink-0"><?php echo LayoutHelper::render('post.avatar', $item, JPATH_COMPONENT . '/layouts'); ?></div>
    <?php endif; ?>
    <div class="flex-grow-1" style="min-width:0">
        <?php if ($item->params->get('show_title', 1)) : ?>
            <h2 class="h5 fw-bold mb-2 text-break">
                <?php if ($item->params->get('link_titles', 1) && ($item->params->get('access-view') || $item->params->get('show_noauth', 0))) : ?>
                    <a class="text-reset text-decoration-none" href="<?php echo Route::_(RouteHelper::getPostRoute($item->slug, $item->catid, $item->language)); ?>"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php else : ?>
                    <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
            </h2>
        <?php endif; ?>
    <div class="d-flex flex-wrap gap-2 small text-muted">
        <?php if ($item->params->get('show_author', 1)) : ?>
            <span><span class="fa-solid fa-user me-1" aria-hidden="true"></span><?php
                $author = htmlspecialchars((string) ($item->created_by_alias ?: ($item->author ?? '')), ENT_QUOTES, 'UTF-8');
                if ($item->params->get('link_author', 1)) {
                    $author = HTMLHelper::_('link', Route::_('index.php?option=com_blog&view=author&id=' . (int) $item->created_by), $author);
                }
                echo \Joomla\CMS\Language\Text::sprintf('COM_BLOG_WRITTEN_BY', $author);
            ?></span>
        <?php endif; ?>
        <?php if ($item->params->get('show_category', 1) && $category !== '') : ?>
            <span class="post-card-footer-category"><span class="fa-solid fa-folder-open me-1" aria-hidden="true"></span><?php
                $categoryText = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
                if ($item->params->get('link_category', 1)) {
                    $categoryText = HTMLHelper::_('link', Route::_(RouteHelper::getCategoryRoute((int) $item->catid, $item->language)), $categoryText);
                }
                echo \Joomla\CMS\Language\Text::sprintf('COM_BLOG_CATEGORY', $categoryText);
            ?></span>
        <?php endif; ?>
        <?php if ($date) : ?>
            <span class="post-card-footer-date"><span class="fa-solid fa-calendar me-1" aria-hidden="true"></span><?php echo \Joomla\Component\Blog\Site\Helper\DateHelper::render($item, $item->params); ?></span>
        <?php endif; ?>
    </div>
    </div>
</header>
