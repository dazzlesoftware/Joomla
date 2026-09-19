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
<div class="small text-muted mb-3">        <?php if ($item->params->get('show_category', 1) && $category !== '') : ?>
            <span class="post-card-footer-category"><span class="fa-solid fa-folder-open me-1" aria-hidden="true"></span><?php
                $categoryText = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
                if ($item->params->get('link_category', 1)) {
                    $categoryText = HTMLHelper::_('link', Route::_(RouteHelper::getCategoryRoute((int) $item->catid, $item->language)), $categoryText);
                }
                echo \Joomla\CMS\Language\Text::sprintf('COM_CODEX_CATEGORY', $categoryText);
            ?></span>
        <?php endif; ?>
</div>