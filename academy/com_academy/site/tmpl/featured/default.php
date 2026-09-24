<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_academy
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
\Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()->registerAndUseStyle('com_academy.post-list-styles', 'com_academy/post-list-styles.css', ['version' => hash_file('sha256', JPATH_ROOT . '/media/com_academy/css/post-list-styles.css')]);
\Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()->registerAndUseScript('com_academy.post-masonry', 'com_academy/post-masonry.js', ['version' => hash_file('sha256', JPATH_ROOT . '/media/com_academy/js/post-masonry.js')], ['defer' => true]);

/** @var \Joomla\Component\Academy\Site\View\Featured\HtmlView $this */
?>
<div class="blog-featured">
    <?php echo $this->loadTemplate('navigation'); ?>
    <?php echo LayoutHelper::render('rsslink', $this->params, JPATH_COMPONENT . '/layouts'); ?>
    <?php if ($this->params->get('show_page_heading') != 0) : ?>
    <div class="page-header">
        <h1>
        <?php echo $this->escape($this->params->get('page_heading')); ?>
        </h1>
    </div>
    <?php endif; ?>

<?php echo \Joomla\Component\Academy\Site\Helper\FeaturedSliderHelper::render($this->params, 0, (int) ($this->pagination->limitstart ?? 0)); ?>
    <?php
    $isColumns = $this->params->get('post_listing_layout', 'rows') === 'columns';
    $isMasonry = $isColumns && $this->params->get('column_style', 'grid') === 'masonry';
    $columns = max(2, min(6, (int) $this->params->get('columns_per_row', 2)));
    // Keep leading and intro posts in one continuous grid, independent of compact posts.
    $groups = [array_merge($this->lead_items, $this->intro_items)];
    $gridClass = 'row row-cols-1 g-4' . ($isColumns ? ' row-cols-md-' . $columns : '');
    ?>
    <?php foreach ($groups as $groupIndex => $group) : ?>
        <?php if (empty($group)) { continue; } ?>
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
    <?php endforeach; ?>

    <?php if ((!empty($this->link_items) || ($this->params->get('compact_selection', 'next') !== 'next' && $this->params->get('num_links', 4) > 0))) : ?>
        <div class="items-more">
            <?php echo $this->loadTemplate('links'); ?>
        </div>
    <?php endif; ?>

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
