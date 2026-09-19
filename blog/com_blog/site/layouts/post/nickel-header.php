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
<header class="card-header bg-transparent d-flex align-items-center gap-2">
<?php if ($item->params->get('show_author', 1)) : ?>
<?php echo LayoutHelper::render('post.avatar', $item, JPATH_COMPONENT . '/layouts'); ?>
<?php endif; ?>
<div class="small d-flex flex-column gap-1">
        <?php if ($item->params->get('show_author', 1)) : ?>
            <span><span class="fa-solid fa-user me-1" aria-hidden="true"></span><?php
                $author = htmlspecialchars((string) ($item->created_by_alias ?: ($item->author ?? '')), ENT_QUOTES, 'UTF-8');
                if ($item->params->get('link_author', 1)) {
                    $author = HTMLHelper::_('link', Route::_('index.php?option=com_blog&view=author&id=' . (int) $item->created_by), $author);
                }
                echo \Joomla\CMS\Language\Text::sprintf('COM_BLOG_WRITTEN_BY', $author);
            ?></span>
        <?php endif; ?>
        <?php if ($date) : ?>
            <span class="post-card-footer-date"><span class="fa-solid fa-calendar me-1" aria-hidden="true"></span><?php echo \Joomla\Component\Blog\Site\Helper\DateHelper::render($item, $item->params); ?></span>
        <?php endif; ?>
</div>
</header>
