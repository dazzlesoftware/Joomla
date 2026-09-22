<?php
defined('_JEXEC') or die;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$grid = $this->params->get('categories_style', 'list') === 'image_grid';
$columns = $this->params->get('categories_listing_layout', 'columns') === 'columns' ? max(2, min(6, (int) $this->params->get('categories_columns', 3))) : 1;
$masonry = $grid && $columns > 1 && $this->params->get('categories_column_style', 'grid') === 'masonry';
if ($grid) {
    $wa = $this->getDocument()->getWebAssetManager();
    $wa->registerAndUseStyle('com_academy.category-directory', 'com_academy/category-directory.css', ['version'=>'1.0.0']);
    if ($masonry) { $wa->registerAndUseScript('com_academy.post-masonry', 'com_academy/post-masonry.js', ['version'=>'1.0.0'], ['defer'=>true]); }
}
?>
<?php echo LayoutHelper::render('postnav', ['params' => $this->params], JPATH_COMPONENT . '/layouts'); ?>
<div class="content-view-categories">
    <h1><?php echo $escape($this->params->get('page_heading', 'Categories')); ?></h1>
    <?php if ($grid) : ?>
    <div class="category-directory row row-cols-1 row-cols-md-<?php echo $columns; ?> g-4" <?php echo $masonry ? 'data-post-masonry' : ''; ?>>
    <?php else : ?>
    <ul>
    <?php endif; ?>
        <?php foreach ($this->items as $item) : ?>
            <?php $url = Route::_('index.php?option=com_academy&view=category&id=' . (int) $item->id); ?>
            <?php if ($grid) : ?>
            <div class="col">
                <article class="card category-directory-card<?php echo $masonry ? '' : ' h-100'; ?>">
                    <a class="category-directory-image" href="<?php echo $escape($url); ?>" aria-label="<?php echo $escape($item->title); ?>">
                    <?php $image = trim((string) ($item->default_image ?? ''));
                    $image = preg_replace('/#joomlaImage:.*$/', '', $image);
                    if ($image !== '' && !preg_match('~^(?:https?://|[^:/]+(?:/|$))~i', $image)) { $image = ''; }
                    ?>
                    <?php if ($image !== '') : ?>
                        <img src="<?php echo $escape(preg_match('~^https?://~i', $image) ? $image : Uri::root() . ltrim($image, '/')); ?>" alt="" loading="lazy">
                    <?php else : ?>
                        <span class="fa-solid fa-image" aria-hidden="true"></span>
                    <?php endif; ?>
                    </a>
                    <div class="card-body">
                        <h2 class="h5 card-title"><a href="<?php echo $escape($url); ?>"><?php echo $escape($item->title); ?></a></h2>
                        <?php if ($this->params->get('show_cat_num_posts_cat', 1)) : ?><span class="badge bg-secondary"><?php echo (int) $item->numitems; ?></span><?php endif; ?>
                    </div>
                </article>
            </div>
            <?php else : ?>
            <li><a href="<?php echo $escape($url); ?>"><?php echo $escape($item->title); ?></a> <?php if ($this->params->get('show_cat_num_posts_cat', 1)) : ?><span><?php echo (int) $item->numitems; ?></span><?php endif; ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php echo $grid ? '</div>' : '</ul>'; ?>
    <?php if (!$this->items) : ?><p>No categories yet.</p><?php endif; ?>
</div>
