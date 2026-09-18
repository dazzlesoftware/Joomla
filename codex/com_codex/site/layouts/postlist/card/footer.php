<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Codex\Site\Helper\RouteHelper;

$item = $displayData;
$date = \Joomla\Component\Codex\Site\Helper\DateHelper::value($item, $item->params ?? null);
$category = trim((string) ($item->category_title ?? ''));
?>
<footer class="post-card-footer d-flex align-items-end justify-content-between gap-3 border-top pt-3 mt-3 text-muted">
    <div class="post-card-footer-details d-flex flex-column gap-1 small">
        <?php if ($item->params->get('show_author', 1)) : ?>
            <span><span class="fa-solid fa-user me-1" aria-hidden="true"></span><?php
                $author = htmlspecialchars((string) ($item->created_by_alias ?: ($item->author ?? '')), ENT_QUOTES, 'UTF-8');
                if ($item->params->get('link_author', 1)) {
                    $author = HTMLHelper::_('link', Route::_('index.php?option=com_codex&view=author&id=' . (int) $item->created_by), $author);
                }
                echo \Joomla\CMS\Language\Text::sprintf('COM_CODEX_WRITTEN_BY', $author);
            ?></span>
        <?php endif; ?>
        <?php if ($item->params->get('show_category', 1) && $category !== '') : ?>
            <span class="post-card-footer-category"><span class="fa-solid fa-folder-open me-1" aria-hidden="true"></span><?php
                $categoryText = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
                if ($item->params->get('link_category', 1)) {
                    $categoryText = HTMLHelper::_('link', Route::_(RouteHelper::getCategoryRoute((int) $item->catid, $item->language)), $categoryText);
                }
                echo \Joomla\CMS\Language\Text::sprintf('COM_CODEX_CATEGORY', $categoryText);
            ?></span>
        <?php endif; ?>
        <?php if ($date) : ?>
            <span class="post-card-footer-date"><span class="fa-solid fa-calendar me-1" aria-hidden="true"></span><?php echo \Joomla\Component\Codex\Site\Helper\DateHelper::render($item, $item->params); ?></span>
        <?php endif; ?>
    </div>
    <span class="post-card-footer-author ms-auto">
        <?php echo LayoutHelper::render('postlist.card.avatar', $item, JPATH_COMPONENT . '/layouts'); ?>
    </span>
</footer>