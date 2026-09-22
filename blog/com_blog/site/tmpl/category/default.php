<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use Joomla\Component\Blog\Site\Helper\RouteHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;

$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<?php echo LayoutHelper::render('postnav', ['params' => $this->params], JPATH_COMPONENT . '/layouts'); ?>
<div class="content-view-category genesis-print-article">
<?php echo LayoutHelper::render('category-actions', ['params' => $this->params], JPATH_COMPONENT . '/layouts'); ?>
    <?php if ($this->params->get('show_page_heading')) : ?>
        <h1><?php echo $escape($this->params->get('page_heading', $this->category->title)); ?></h1>
    <?php endif; ?>
    <?php if ($this->params->get('show_category_title', 1)) : ?>
        <?php $htag = $this->params->get('show_page_heading') ? 'h2' : 'h1'; ?>
        <<?php echo $htag; ?>><?php echo $escape($this->category->title); ?></<?php echo $htag; ?>>
    <?php endif; ?>

    <?php if ($this->params->get('show_description_image', 0) && $this->category->default_image) : ?>
        <figure class="category-image">
            <img class="img-fluid" src="<?php echo $escape(Uri::root() . $this->category->default_image); ?>" alt="">
        </figure>
    <?php endif; ?>

    <?php if ($this->params->get('show_description', 1) && $this->category->description) : ?>
        <div class="category-desc clearfix mb-4"><?php echo \Joomla\CMS\HTML\HTMLHelper::_('content.prepare', $this->category->description, '', 'com_blog.category'); ?></div>
    <?php endif; ?>

<?php echo LayoutHelper::render('category-subcategories', ['items' => $this->subcategories, 'params' => $this->params], JPATH_COMPONENT . '/layouts'); ?>

    <?php foreach ($this->items as $item) : ?>
        <?php $url = Route::_(RouteHelper::getPostRoute($item->id . ':' . $item->alias, $item->catid, $item->language ?? '*')); ?>
        <article class="mb-4">
            <?php echo LayoutHelper::render('blog.content.featured_image', $item, JPATH_COMPONENT . '/layouts'); ?>
            <h2><a href="<?php echo $url; ?>"><?php echo $escape($item->title); ?></a></h2>
            <?php echo $item->summary; ?>
            <a href="<?php echo $url; ?>">Read more</a>
        </article>
    <?php endforeach; ?>

    <?php if (!$this->items && $this->params->get('show_no_posts', 1)) : ?>
        <p><?php echo \Joomla\CMS\Language\Text::_('COM_BLOG_NO_POSTS'); ?></p>
    <?php endif; ?>

    <?php echo $this->pagination->getPagesLinks(); ?>
</div>
