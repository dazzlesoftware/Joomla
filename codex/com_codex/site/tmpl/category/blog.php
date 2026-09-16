<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_codex
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\Component\Codex\Site\View\Category\HtmlView $this */

$app = Factory::getApplication();
$app->getDocument()->getWebAssetManager()->registerAndUseStyle('com_codex.post-list-styles', 'com_codex/post-list-styles.css', ['version' => 'auto']);
$listStyle = (string) $this->params->get('list_item_style', 'standard');
if (!in_array($listStyle, ['standard', 'card', 'learning', 'simple', 'nickel', 'compact'], true)) {
    $listStyle = 'standard';
}

$this->category->text = $this->category->description;
$app->triggerEvent('onContentPrepare', ['com_codex.categories', &$this->category, &$this->params, 0]);
$this->category->description = $this->category->text;

$results = $app->triggerEvent('onContentAfterTitle', ['com_codex.categories', &$this->category, &$this->params, 0]);
$afterDisplayTitle = trim(implode("\n", $results));

$results = $app->triggerEvent('onContentBeforeDisplay', ['com_codex.categories', &$this->category, &$this->params, 0]);
$beforeDisplayContent = trim(implode("\n", $results));

$results = $app->triggerEvent('onContentAfterDisplay', ['com_codex.categories', &$this->category, &$this->params, 0]);
$afterDisplayContent = trim(implode("\n", $results));

$htag = $this->params->get('show_page_heading') ? 'h2' : 'h1';
?>
<?php echo LayoutHelper::render('postnav', ['params' => $this->params], JPATH_COMPONENT . '/layouts'); ?>
<div class="com-codex-category-blog blog">
    <?php if ($this->params->get('show_page_heading')) : ?>
        <div class="page-header">
            <h1><?php echo $this->escape($this->params->get('page_heading', $this->category->title)); ?></h1>
        </div>
    <?php endif; ?>

    <?php if ($this->params->get('show_category_title', 1)) : ?>
    <<?php echo $htag; ?>>
        <?php echo htmlspecialchars($this->category->title, ENT_QUOTES, 'UTF-8'); ?>
    </<?php echo $htag; ?>>
    <?php endif; ?>
    <?php if ($this->category->default_image) : ?>
        <figure class="category-image">
            <img src="<?php echo htmlspecialchars(Uri::root() . $this->category->default_image, ENT_QUOTES, 'UTF-8'); ?>" alt="">
        </figure>
    <?php endif; ?>
    <?php echo $afterDisplayTitle; ?>

    <?php if ($beforeDisplayContent || $afterDisplayContent || $this->params->get('show_description', 1)) : ?>
        <div class="category-desc clearfix">
            <?php echo $beforeDisplayContent; ?>
            <?php if ($this->params->get('show_description') && $this->category->description) : ?>
                <?php echo HTMLHelper::_('content.prepare', $this->category->description, '', 'com_codex.category'); ?>
            <?php endif; ?>
            <?php echo $afterDisplayContent; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($this->lead_items) && empty($this->link_items) && empty($this->intro_items)) : ?>
        <?php if ($this->params->get('show_no_articles', 1)) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('COM_CODEX_NO_POSTS'); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($this->lead_items)) : ?>
        <div class="com-codex-category-blog__items blog-items items-leading post-style-<?php echo $listStyle; ?>">
            <?php foreach ($this->lead_items as &$item) : ?>
                <div class="com-codex-category-blog__item blog-item">
                    <?php
                    $this->item = &$item;
                echo $this->loadTemplate('item');
                ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($this->intro_items)) : ?>
        <?php $blogClass = ''; ?>
        <?php if ((int) $this->params->get('num_columns') > 1) : ?>
            <?php $blogClass = ' columns-' . (int) $this->params->get('num_columns'); ?>
        <?php endif; ?>
        <div class="com-codex-category-blog__items blog-items post-style-<?php echo $listStyle; ?><?php echo $blogClass; ?>">
        <?php foreach ($this->intro_items as &$item) : ?>
            <div class="com-codex-category-blog__item blog-item">
                <?php
                $this->item = &$item;
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

    <?php if (($this->params->def('show_pagination', 1) == 1 || $this->params->get('show_pagination') == 2) && $this->pagination->pagesTotal > 1) : ?>
        <div class="com-codex-category-blog__navigation w-100">
            <?php if ($this->params->def('show_pagination_results', 1)) : ?>
                <p class="com-codex-category-blog__counter counter float-md-end pt-3 pe-2">
                    <?php echo $this->pagination->getPagesCounter(); ?>
                </p>
            <?php endif; ?>
            <div class="com-codex-category-blog__pagination">
                <?php echo $this->pagination->getPagesLinks(); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
