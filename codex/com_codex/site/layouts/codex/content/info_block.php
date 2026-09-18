<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_codex
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Local override of Joomla core's layouts/joomla/content/info_block.php.
 *
 * Core's version gates every field behind an "info_block_position"
 * (Above/Below/Split) check. We only ever render this block once, in the
 * "above" position (see the "Position of Post Info" removal), so that
 * check is dropped entirely here - otherwise a post/menu item/global
 * config that had a pre-existing stored value of "Below" or "Split" from
 * before that field was removed would render nothing at all, since we
 * always call this layout with position => 'above'.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<dl class="article-info text-muted">
    <dt class="article-info-term">
        <?php if (!$displayData['params']->get('info_block_show_title', 1)) : ?>
            <?php echo '<span class="visually-hidden">'; ?>
        <?php endif; ?>
        <?php echo Text::_('COM_CODEX_ARTICLE_INFO'); ?>
        <?php if (!$displayData['params']->get('info_block_show_title', 1)) : ?>
            <?php echo '</span>'; ?>
        <?php endif; ?>
    </dt>

    <?php if ($displayData['params']->get('show_author') && !empty($displayData['item']->author)) : ?>
        <?php echo $this->sublayout('author', $displayData); ?>
    <?php endif; ?>

    <?php if ($displayData['params']->get('show_parent_category') && !empty($displayData['item']->parent_id)) : ?>
        <?php echo $this->sublayout('parent_category', $displayData); ?>
    <?php endif; ?>

    <?php if ($displayData['params']->get('show_category')) : ?>
        <?php echo $this->sublayout('category', $displayData); ?>
    <?php endif; ?>

    <?php if ($displayData['params']->get('show_associations')) : ?>
        <?php echo $this->sublayout('associations', $displayData); ?>
    <?php endif; ?>

    <?php if ($dateHtml = \Joomla\Component\Codex\Site\Helper\DateHelper::render($displayData['item'], $displayData['params'])) : ?>
        <dd class="date"><span class="fa-solid fa-calendar me-1" aria-hidden="true"></span><?php echo $dateHtml; ?></dd>
    <?php endif; ?>

    <?php if ($displayData['params']->get('show_hits')) : ?>
        <?php echo $this->sublayout('hits', $displayData); ?>
    <?php endif; ?>
</dl>
