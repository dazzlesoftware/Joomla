<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_codex
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
\Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()->registerAndUseStyle('com_codex.post-list-styles', 'com_codex/post-list-styles.css', ['version' => 'auto']);

/** @var \Joomla\Component\Codex\Site\View\Featured\HtmlView $this */
?>
<div class="blog-featured">
    <?php echo LayoutHelper::render('postnav', ['params' => $this->params], JPATH_COMPONENT . '/layouts'); ?>
    <?php echo LayoutHelper::render('rsslink', $this->params, JPATH_COMPONENT . '/layouts'); ?>
    <?php if ($this->params->get('show_page_heading') != 0) : ?>
    <div class="page-header">
        <h1>
        <?php echo $this->escape($this->params->get('page_heading')); ?>
        </h1>
    </div>
    <?php endif; ?>

    <?php if (!empty($this->lead_items)) : ?>
        <div class="post-list-items items-leading row row-cols-1 g-4 mb-4 post-style-<?php echo $this->params->get('list_item_style', 'standard'); ?> <?php echo $this->params->get('blog_class_leading'); ?>">
            <?php foreach ($this->lead_items as &$item) : ?>
                <div class="post-list-item col">
                        <?php
                        $this->item = & $item;
                echo $this->loadTemplate('item');
                ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($this->intro_items)) : ?>
        <?php $blogClass = $this->params->get('blog_class', ''); ?>
        <?php $listingLayout = $this->params->get('post_listing_layout', 'rows');
        $columnStyle = $this->params->get('column_style', 'grid');
        $columnsPerRow = max(2, min(6, (int) $this->params->get('columns_per_row', 2)));
        $postStyle = $this->params->get('list_item_style', 'standard'); ?>
        <?php if ($listingLayout === 'rows') {
            $blogClass .= ' row row-cols-1 g-4';
        } else {
            $blogClass .= $columnStyle === 'masonry' ? ' post-listing-masonry' : ' row row-cols-1 row-cols-md-' . $columnsPerRow . ' g-4';
        } $blogClass .= ' post-style-' . $postStyle; ?>
        <div class="post-list-items <?php echo $blogClass; ?>" style="--post-listing-columns:<?php echo (int) $columnsPerRow; ?>">
        <?php foreach ($this->intro_items as $key => &$item) : ?>
            <div class="post-list-item col">
                    <?php
                    $this->item = & $item;
            echo $this->loadTemplate('item');
            ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($this->link_items)) : ?>
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
