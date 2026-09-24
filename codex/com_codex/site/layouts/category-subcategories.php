<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Codex\Site\Helper\RouteHelper;
$nodes = $displayData['items'] ?? [];
$params = $displayData['params'];
if (!$nodes) { return; }
$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$grid = $params->get('subcategories_style', 'link_list') === 'image_grid';
$columns = $params->get('subcategories_listing_layout', 'columns') === 'columns' ? max(2, min(6, (int) $params->get('subcategories_columns', 3))) : 1;
$masonry = $columns > 1 && $params->get('subcategories_column_style', 'grid') === 'masonry';
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_codex.category-directory', 'com_codex/category-directory.css', ['version'=>'1.0.0']);
if ($masonry) { $wa->registerAndUseScript('com_codex.post-masonry', 'com_codex/post-masonry.js', ['version'=>'1.0.0'], ['defer'=>true]); }

$render = static function (array $items) use (&$render, $params, $escape, $grid, $columns, $masonry): void {
    echo '<ul class="category-subcategories category-directory list-unstyled row row-cols-1 row-cols-md-' . $columns . ($grid ? ' g-4 mb-4' : ' g-3 mb-3') . '"' . ($masonry ? ' data-post-masonry' : '') . '>';
    foreach ($items as $child) {
        $url = $escape(Route::_(RouteHelper::getCategoryRoute($child->id, $child->language)));
        echo $grid ? '<li class="col"><div class="card category-directory-card' . ($masonry ? '' : ' h-100') . '">' : '<li class="col">';
        if ($grid) {
            $image = preg_replace('/#joomlaImage:.*$/', '', trim((string) ($child->default_image ?? '')));
            if ($image !== '' && !preg_match('~^(?:https?://|[^:/]+(?:/|$))~i', $image)) { $image = ''; }
            echo '<a class="category-directory-image" href="' . $url . '" aria-label="' . $escape($child->title) . '">';
            if ($image !== '') {
                echo '<img src="' . $escape(preg_match('~^https?://~i', $image) ? $image : Uri::root() . ltrim($image, '/')) . '" alt="" loading="lazy">';
            } else { echo '<span class="fa-solid fa-image" aria-hidden="true"></span>'; }
            echo '</a><div class="card-body"><h3 class="h5 card-title">';
        }
        echo '<a href="' . $url . '">' . $escape($child->title) . '</a>';
        if ($grid) { echo '</h3>'; }
        if ($params->get('show_cat_num_posts', 1)) { echo ' <span class="badge bg-secondary">' . (int) $child->numitems . '</span>'; }
        if ($params->get('show_subcat_desc', 1) && $child->description !== '') {
            echo '<div class="category-desc">' . HTMLHelper::_('content.prepare', $child->description, '', 'com_codex.category') . '</div>';
        }
        if ($child->children) { $render($child->children); }
        if ($grid) { echo '</div></div>'; }
        echo '</li>';
    }
    echo '</ul>';
};
?>
<div class="category-children mt-4 mb-4">
    <?php if ($params->get('show_category_heading_title_text', 1)) : ?>
        <h2><?php echo Text::_('JGLOBAL_SUBCATEGORIES'); ?></h2>
    <?php endif; ?>
    <?php $render($nodes); ?>
</div>
