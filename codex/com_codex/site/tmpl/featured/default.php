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

/** @var \Joomla\Component\Codex\Site\View\Featured\HtmlView $this */
?>
<div class="blog-featured">
    <?php echo LayoutHelper::render('postnav', (object) [], JPATH_COMPONENT . '/layouts'); ?>
    <?php echo LayoutHelper::render('rsslink', $this->params, JPATH_COMPONENT . '/layouts'); ?>
    <?php if ($this->params->get('show_page_heading') != 0) : ?>
    <div class="page-header">
        <h1>
        <?php echo $this->escape($this->params->get('page_heading')); ?>
        </h1>
    </div>
    <?php endif; ?>

    <?php if (!empty($this->lead_items)) : ?>
        <div class="blog-items items-leading post-style-<?php echo $this->params->get('list_item_style', 'standard'); ?> <?php echo $this->params->get('blog_class_leading'); ?>">
            <?php foreach ($this->lead_items as &$item) : ?>
                <div class="blog-item">
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
        <?php $listingLayout = $this->params->get('post_listing_layout', 'rows'); $columnStyle = $this->params->get('column_style', 'grid'); $columnsPerRow = max(2, min(6, (int) $this->params->get('columns_per_row', 2))); $postStyle = $this->params->get('list_item_style', 'standard'); ?>
        <?php if ($listingLayout === 'rows') { $blogClass .= ' columns-1 post-listing-rows'; } else { $blogClass .= ' post-listing-columns post-listing-' . $columnStyle . ' columns-' . $columnsPerRow; } $blogClass .= ' post-style-' . $postStyle; ?>
        <?php if (false) : ?>
            <?php $blogClass .= (int) $this->params->get('multi_column_order', 0) === 0 ? ' masonry-' : ' columns-'; ?>
            <?php $blogClass .= (int) $this->params->get('num_columns'); ?>
        <?php endif; ?>
        <div class="blog-items <?php echo $blogClass; ?>" style="--post-listing-columns:<?php echo (int) $columnsPerRow; ?>">
        <?php foreach ($this->intro_items as $key => &$item) : ?>
            <div class="blog-item">
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

<style>
.post-listing-grid{display:grid;grid-template-columns:repeat(var(--post-listing-columns,2),minmax(0,1fr));gap:1.5rem}
.post-listing-masonry{column-count:var(--post-listing-columns,2);column-gap:1.5rem}
.post-listing-masonry>.blog-item{display:inline-block;width:100%;break-inside:avoid;margin:0 0 1.5rem}
.post-listing-columns{--post-listing-columns:2}
.post-listing-columns.columns-3{--post-listing-columns:3}.post-listing-columns.columns-4{--post-listing-columns:4}.post-listing-columns.columns-5{--post-listing-columns:5}.post-listing-columns.columns-6{--post-listing-columns:6}
.items-leading.post-style-card{display:block;width:100%;margin-bottom:1.5rem}
.items-leading.post-style-card>.blog-item{width:100%}
.post-style-card>.blog-item{display:flex;flex-direction:column;height:100%;padding:0;overflow:hidden;border:1px solid var(--border-color,#dee2e6);border-radius:.65rem;background:var(--card-bg,#fff);box-shadow:0 .25rem .9rem rgba(0,0,0,.09);transition:transform .18s ease,box-shadow .18s ease}
.post-style-card>.blog-item:hover{transform:translateY(-2px);box-shadow:0 .55rem 1.35rem rgba(0,0,0,.13)}
.post-style-card>.blog-item>.item-image{width:100%;aspect-ratio:16/9;margin:0;overflow:hidden;background:#f0f1f3}
.post-style-card>.blog-item>.item-image>a{display:block;width:100%;height:100%}
.post-style-card>.blog-item>.item-image img{display:block;width:100%;height:100%;object-fit:cover;transition:transform .25s ease}
.post-style-card>.blog-item>.post-card-placeholder{display:flex;align-items:center;justify-content:center;color:var(--secondary-color,#6c757d)}
.post-style-card>.blog-item>.post-card-placeholder svg{display:block;width:100%;height:100%}
.post-style-card>.blog-item:hover>.item-image img{transform:scale(1.025)}
.post-style-card>.blog-item>.item-content{display:flex;flex:1;flex-direction:column;padding:1.35rem}
.post-style-card>.blog-item>.item-content .item-title{margin-top:0;font-size:1.35rem;font-weight:700;line-height:1.25}
.post-style-card>.blog-item>.item-content .readmore{margin-top:1rem}
.post-style-card>.blog-item>.item-content .readmore .btn{font-weight:600}
.post-style-card>.blog-item>.item-content .postmeta-row{margin-top:auto!important;padding-top:1rem;border-top:1px solid var(--border-color,#dee2e6)}
.post-style-card>.blog-item>.item-content .postmeta-share{display:none!important}
.post-style-card>.blog-item>.item-content .article-info{display:none!important}
.post-style-card>.blog-item>.item-content .item-title .postmeta-avatar,.post-style-card>.blog-item>.item-content .item-title .postmeta-avatar-img{display:none!important}
.post-card-footer{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-top:.9rem;padding-top:1rem;border-top:1px solid var(--border-color,#dee2e6);color:var(--secondary-color,#6c757d)}
.post-card-footer-details{display:flex;min-width:0;flex-direction:column;gap:.2rem}
.post-card-footer-date{font-size:.9rem}
.post-card-footer-category{font-size:.9rem}
.post-card-footer-category a{color:inherit}
.post-card-footer-author{margin-left:auto;line-height:0}
.post-card-footer-author a{display:inline-flex;border-radius:.375rem}
.post-card-footer-author a:focus-visible{outline:3px solid currentColor;outline-offset:3px}
.post-style-learning>.blog-item{display:flex;flex-direction:column;height:100%;padding:0;overflow:hidden;border:1px solid var(--border-color,#dee2e6);border-radius:.65rem;background:var(--card-bg,#fff);box-shadow:0 .25rem .9rem rgba(0,0,0,.09);transition:transform .18s ease,box-shadow .18s ease}
.post-style-learning>.blog-item:hover{transform:translateY(-2px);box-shadow:0 .55rem 1.35rem rgba(0,0,0,.13)}
.post-style-learning>.blog-item>.item-image,.post-style-learning>.blog-item>.post-card-placeholder{display:flex;width:100%;aspect-ratio:16/9;margin:0;overflow:hidden;background:#f0f1f3;color:var(--secondary-color,#6c757d)}
.post-style-learning>.blog-item>.item-image>a{display:block;width:100%;height:100%}
.post-style-learning>.blog-item>.item-image img,.post-style-learning>.blog-item>.post-card-placeholder svg{display:block;width:100%;height:100%;object-fit:cover}
.post-style-learning>.blog-item>.learning-item-content{display:flex;flex:1;flex-direction:column;padding:1rem 1.1rem}
.post-style-learning>.blog-item>.learning-item-content .item-title{display:block;margin:0 0 .8rem;font-size:1.05rem;font-weight:700;line-height:1.3}
.post-style-learning>.blog-item>.learning-item-content .item-title span{display:block}
.learning-item-details{display:flex;flex-direction:column;gap:.65rem;color:var(--secondary-color,#6c757d);font-size:.9rem}
.learning-item-author{display:flex;align-items:center;gap:.55rem}
.learning-item-author .postmeta-avatar,.learning-item-author .postmeta-avatar-img{width:1.75rem;height:1.75rem}
.learning-item-category a{color:inherit}
.items-leading.post-style-learning{display:block;width:100%;margin-bottom:1.5rem}
.items-leading.post-style-learning>.blog-item{width:100%}
.post-style-simple>.blog-item{padding:.75rem 0;border-bottom:1px solid var(--border-color,#dee2e6)}
.post-style-nickel>.blog-item{padding:1.25rem;border-left:.35rem solid #6c757d;background:color-mix(in srgb,var(--card-bg,#fff) 94%,#6c757d)}
@media(max-width:991.98px){.post-listing-columns.columns-4,.post-listing-columns.columns-5,.post-listing-columns.columns-6{--post-listing-columns:3}}
@media(max-width:767.98px){.post-listing-grid{grid-template-columns:1fr}.post-listing-masonry{column-count:1}}
</style>