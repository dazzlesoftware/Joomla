<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_blog
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
\Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()->registerAndUseStyle('com_blog.post-list-styles', 'com_blog/post-list-styles.css', ['version' => hash_file('sha256', JPATH_ROOT . '/media/com_blog/css/post-list-styles.css')]);
\Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()->registerAndUseScript('com_blog.post-masonry', 'com_blog/post-masonry.js', ['version' => hash_file('sha256', JPATH_ROOT . '/media/com_blog/js/post-masonry.js')], ['defer' => true]);

/** @var \Joomla\Component\Blog\Site\View\Author\HtmlView $this */
?>
<div class="content-view-author">
    <?php echo $this->loadTemplate('navigation'); ?>
    <?php if ($this->author) : ?>
    <header class="author-profile-header d-flex align-items-center gap-3 mb-4">
        <?php echo LayoutHelper::render('post.avatar', (object) ['created_by' => $this->author->id], JPATH_COMPONENT . '/layouts'); ?>
        <div><h1 class="mb-1"><?php echo $this->escape($this->author->name); ?></h1>
        <div class="text-muted"><?php echo \Joomla\CMS\Language\Text::plural('COM_BLOG_AUTHORS_POST_COUNT', $this->pagination->total); ?></div></div>
    </header>
    <?php else : ?><h1><?php echo \Joomla\CMS\Language\Text::_('COM_BLOG_AUTHORS_HEADING'); ?></h1><?php endif; ?>
    <?php echo \Joomla\Component\Blog\Site\Helper\FeaturedSliderHelper::render($this->params, 0, $this->pagination->limitstart, function ($style, $data) {
        $this->sliderData = $data;
        return $this->loadTemplate('slider_' . $style);
    }); ?>
    <?php if (!$this->posts) : ?><p class="alert alert-info"><?php echo \Joomla\CMS\Language\Text::_('COM_BLOG_AUTHOR_POSTS_EMPTY'); ?></p><?php endif; ?>
    <?php
    $isColumns = $this->params->get('post_listing_layout', 'rows') === 'columns';
    $isMasonry = $isColumns && $this->params->get('column_style', 'grid') === 'masonry';
    $columns = max(2, min(6, (int) $this->params->get('columns_per_row', 2)));
    // Group the current page by user ID, preserving post order within each author.
    $groups = [];
    foreach ($this->posts as $post) {
        $groups[(int) $post->created_by][] = $post;
    }
    $authorNames = [];
    if (!$this->author && $groups) {
        $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $authorNames = $db->setQuery($db->createQuery()->select(['id', 'name'])->from('#__users')
            ->whereIn('id', array_keys($groups)))->loadObjectList('id');
    }
    $gridClass = 'row row-cols-1 g-4' . ($isColumns ? ' row-cols-md-' . $columns : '');
    ?>
    <?php foreach ($groups as $groupIndex => $group) : ?>
        <?php if (empty($group)) { continue; } ?>
        <section class="author-post-group" data-author-id="<?php echo (int) $groupIndex; ?>">
        <?php if (!$this->author) : ?>
        <header class="author-profile-header d-flex align-items-center gap-3 mb-4">
            <?php echo LayoutHelper::render('post.avatar', (object) ['created_by' => $groupIndex], JPATH_COMPONENT . '/layouts'); ?>
            <h2 class="mb-0"><?php echo $this->escape($authorNames[$groupIndex]->name ?? $group[0]->author); ?></h2>
        </header>
        <?php endif; ?>
        <div class="post-list-items mb-4 post-style-<?php echo $this->escape((string) $this->params->get('list_item_style', 'standard')); ?> <?php echo $gridClass; ?>" <?php echo $isMasonry ? 'data-post-masonry' : ''; ?>>
            <?php foreach ($group as $index => $item) : ?>
                <div class="post-list-item col <?php echo $this->escape((string) $this->params->get('blog_class', '')); ?>">
                    <?php
                    $this->item = $item;
                    echo $this->loadTemplate('item');
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
        </section>
    <?php endforeach; ?>

    <?php echo $this->loadTemplate('links'); ?>

    <?php if ($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2 && $this->pagination->pagesTotal > 1)) : ?>
        <div class="w-100">
            <?php if ($this->params->def('show_pagination_results', 1)) : ?>
                <p class="counter float-end pt-3 pe-2">
                    <?php echo $this->pagination->getPagesCounter(); ?>
                </p>
            <?php endif; ?>
            <?php echo $this->pagination->getPagesLinks(); ?>
        </div>
    <?php endif; ?>

</div>
