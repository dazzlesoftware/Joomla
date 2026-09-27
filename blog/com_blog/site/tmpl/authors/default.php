<?php
 defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$images = $this->params->get('authors_style', 'link_list') === 'image_grid';
$columns = $this->params->get('authors_listing_layout', 'rows') === 'columns'
    ? max(2, min(6, (int) $this->params->get('authors_columns', 3))) : 1;
$masonry = $columns > 1 && $this->params->get('authors_column_style', 'grid') === 'masonry';
$wa = $this->getDocument()->getWebAssetManager();
$wa->useStyle('fontawesome');
$wa->registerAndUseStyle('com_blog.category-directory', 'com_blog/category-directory.css', ['version' => 'authors-avatar-2']);
if ($masonry) {
    $wa->registerAndUseScript('com_blog.post-masonry', 'com_blog/post-masonry.js', ['version' => 'auto'], ['defer' => true]);
}
$avatarHelper = '\\Joomla\\Plugin\\User\\GenesisProfile\\Helper\\GenesisProfileHelper';
?>
<?php echo LayoutHelper::render('postnav', ['params' => $this->params], JPATH_COMPONENT . '/layouts'); ?>
<div class="content-view-authors">
    <?php if ($this->params->get('show_page_heading', 1)) : ?>
        <h1 class="mb-4"><?php echo $this->escape($this->params->get('page_heading') ?: Text::_('COM_BLOG_AUTHORS_HEADING')); ?></h1>
    <?php endif; ?>
    <ul class="list-unstyled row row-cols-1 row-cols-md-<?php echo $columns; ?> g-4" <?php echo $masonry ? 'data-post-masonry' : ''; ?>>
        <?php foreach ($this->items as $author) : ?>
            <?php $url = Route::_('index.php?option=com_blog&view=author&id=' . (int) $author->id); ?>
            <li class="col">
                <div class="<?php echo $images ? 'card card-body text-center' . ($masonry ? '' : ' h-100') : 'd-flex justify-content-between align-items-center py-2 border-top'; ?>">
                    <a href="<?php echo $this->escape($url); ?>">
                        <?php if ($images) : ?>
                            <span class="d-block mb-3">
                                <?php $avatar = class_exists($avatarHelper) ? $avatarHelper::getUploadedAvatarPath((int) $author->id) : null; ?>
                                <span class="author-directory-avatar">
                                    <span class="author-directory-avatar-placeholder<?php echo $avatar ? ' d-none' : ''; ?>" aria-hidden="true"><span class="fa-solid fa-user"></span></span>
                                    <?php if ($avatar) : ?>
                                        <img src="<?php echo $this->escape(\Joomla\CMS\Uri\Uri::root() . ltrim($avatar, '/')); ?>" width="160" height="160" alt="" loading="lazy" onerror="this.previousElementSibling.classList.remove('d-none');this.remove()">
                                    <?php endif; ?>
                                </span>
                            </span>
                        <?php endif; ?>
                        <?php echo $this->escape($author->name); ?>
                    </a>
                    <span class="text-muted small"><?php echo Text::plural('COM_BLOG_AUTHORS_POST_COUNT', (int) $author->post_count); ?></span>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if (!$this->items) : ?><p class="alert alert-info"><?php echo Text::_('COM_BLOG_AUTHORS_EMPTY'); ?></p><?php endif; ?>
    <?php if ($this->pagination && $this->pagination->pagesTotal > 1) : ?>
        <div class="com-content-category-blog__navigation">
            <?php echo $this->pagination->getPagesLinks(); ?>
            <?php if ($this->params->get('authors_pagination_summary', 1)) : ?>
                <p class="counter text-muted"><?php echo $this->pagination->getPagesCounter(); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
