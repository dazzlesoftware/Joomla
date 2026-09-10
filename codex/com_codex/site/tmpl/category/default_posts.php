<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_codex
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Component\Codex\Administrator\Helper\TagsHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Codex\Administrator\Extension\CodexComponent;
use Joomla\Component\Codex\Site\Helper\AssociationHelper;
use Joomla\Component\Codex\Site\Helper\RouteHelper;

/** @var \Joomla\Component\Codex\Site\View\Category\HtmlView $this */
/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('com_codex.posts-list');

// Create some shortcuts.
$n          = count($this->items);
$listOrder  = $this->escape($this->state->get('list.ordering'));
$listDirn   = $this->escape($this->state->get('list.direction'));
$langFilter = false;

// Use component-owned tags and enforce their published, access and language visibility.
$tagDb = TagsHelper::db();
$tagQuery = $tagDb->createQuery()->select('id AS value, title AS text')->from('#__codex_tags')->where('published=1')->whereIn('access', Factory::getApplication()->getIdentity()->getAuthorisedViewLevels())->where('language IN (' . $tagDb->quote('*') . ',' . $tagDb->quote(Factory::getApplication()->getLanguage()->getTag()) . ')')->order('title');
$tagOptions = $tagDb->setQuery($tagQuery)->loadObjectList();
// Check for at least one editable post
$isEditable = false;

if (!empty($this->items)) {
    foreach ($this->items as $post) {
        if ($post->params->get('access-edit')) {
            $isEditable = true;
            break;
        }
    }
}

$currentDate = Factory::getDate()->format('Y-m-d H:i:s');
?>

<form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm" class="com-content-category__posts">
    <?php if ($this->params->get('filter_field') !== 'hide') : ?>
        <div class="com-content__filter btn-group">
            <?php if ($this->params->get('filter_field') === 'tag') : ?>
                <span class="visually-hidden">
                    <label class="filter-search-lbl" for="filter-search">
                        <?php echo Text::_('JOPTION_SELECT_TAG'); ?>
                    </label>
                </span>
                <select name="filter_tag" id="filter-search" class="form-select">
                    <option value=""><?php echo Text::_('JOPTION_SELECT_TAG'); ?></option>
                    <?php echo HTMLHelper::_('select.options', $tagOptions, 'value', 'text', $this->state->get('filter.tag')); ?>
                </select>
            <?php elseif ($this->params->get('filter_field') === 'month') : ?>
                <span class="visually-hidden">
                    <label class="filter-search-lbl" for="filter-search">
                        <?php echo Text::_('JOPTION_SELECT_MONTH'); ?>
                    </label>
                </span>
                <select name="filter-search" id="filter-search" class="form-select">
                    <option value=""><?php echo Text::_('JOPTION_SELECT_MONTH'); ?></option>
                    <?php echo HTMLHelper::_('select.options', HTMLHelper::_('content.months', $this->state), 'value', 'text', $this->state->get('list.filter')); ?>
                </select>
            <?php else : ?>
                <label class="filter-search-lbl visually-hidden" for="filter-search">
                    <?php echo Text::_('COM_CODEX_' . $this->params->get('filter_field') . '_FILTER_LABEL'); ?>
                </label>
                <input type="text" 
                    name="filter-search" 
                    id="filter-search" 
                    value="<?php echo $this->escape($this->state->get('list.filter')); ?>" 
                    class="inputbox" 
                    placeholder="<?php echo Text::_('COM_CODEX_' . $this->params->get('filter_field') . '_FILTER_LABEL'); ?>"
                >
            <?php endif; ?>

            <?php if ($this->params->get('filter_field') !== 'tag' && $this->params->get('filter_field') !== 'month') : ?>
                <button type="submit" name="filter_submit" class="btn btn-primary"><?php echo Text::_('JGLOBAL_FILTER_BUTTON'); ?></button>
            <?php endif; ?>
            <button type="reset" name="filter-clear-button" class="btn btn-secondary"><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></button>
        </div>
    <?php endif; ?>

    <?php if ($this->params->get('show_pagination_limit')) : ?>
        <div class="com-content-category__pagination btn-group float-end">
            <label for="limit" class="visually-hidden">
                <?php echo Text::_('JGLOBAL_DISPLAY_NUM'); ?>
            </label>
            <?php echo $this->pagination->getLimitBox(); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($this->items)) : ?>
        <?php if ($this->params->get('show_no_posts', 1)) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                    <?php echo Text::_('COM_CODEX_NO_POSTS'); ?>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <table class="com-content-category__table category table table-striped table-bordered table-hover">
            <caption class="visually-hidden">
                <?php echo Text::_('COM_CODEX_POSTS_TABLE_CAPTION'); ?>
            </caption>
            <thead<?php echo $this->params->get('show_headings', '1') ? '' : ' class="visually-hidden"'; ?>>
                <tr>
                    <th scope="col" id="categorylist_header_title">
                        <?php echo HTMLHelper::_('grid.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder, null, 'asc', '', 'adminForm'); ?>
                    </th>
                    <?php if ($date = $this->params->get('list_show_date')) : ?>
                        <th scope="col" id="categorylist_header_date">
                            <?php if ($date === 'created') : ?>
                                <?php echo HTMLHelper::_('grid.sort', 'COM_CODEX_' . $date . '_DATE', 'a.created', $listDirn, $listOrder); ?>
                            <?php elseif ($date === 'modified') : ?>
                                <?php echo HTMLHelper::_('grid.sort', 'COM_CODEX_' . $date . '_DATE', 'a.modified', $listDirn, $listOrder); ?>
                            <?php elseif ($date === 'published') : ?>
                                <?php echo HTMLHelper::_('grid.sort', 'COM_CODEX_' . $date . '_DATE', 'a.publish_up', $listDirn, $listOrder); ?>
                            <?php endif; ?>
                        </th>
                    <?php endif; ?>
                    <?php if ($this->params->get('list_show_author')) : ?>
                        <th scope="col" id="categorylist_header_author">
                            <?php echo HTMLHelper::_('grid.sort', 'JAUTHOR', 'author', $listDirn, $listOrder); ?>
                        </th>
                    <?php endif; ?>
                    <?php if ($this->params->get('list_show_hits')) : ?>
                        <th scope="col" id="categorylist_header_hits">
                            <?php echo HTMLHelper::_('grid.sort', 'JGLOBAL_HITS', 'a.hits', $listDirn, $listOrder); ?>
                        </th>
                    <?php endif; ?>
                    <?php if ($this->params->get('list_show_votes', 0) && $this->vote) : ?>
                        <th scope="col" id="categorylist_header_votes">
                            <?php echo HTMLHelper::_('grid.sort', 'COM_CODEX_VOTES', 'rating_count', $listDirn, $listOrder); ?>
                        </th>
                    <?php endif; ?>
                    <?php if ($this->params->get('list_show_ratings', 0) && $this->vote) : ?>
                        <th scope="col" id="categorylist_header_ratings">
                            <?php echo HTMLHelper::_('grid.sort', 'COM_CODEX_RATINGS', 'rating', $listDirn, $listOrder); ?>
                        </th>
                    <?php endif; ?>
                    <?php if ($isEditable) : ?>
                        <th scope="col" id="categorylist_header_edit"><?php echo Text::_('COM_CODEX_EDIT_ITEM'); ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($this->items as $i => $post) : ?>
                <?php if ($this->items[$i]->state == CodexComponent::CONDITION_UNPUBLISHED) : ?>
                    <tr class="system-unpublished cat-list-row<?php echo $i % 2; ?>">
                <?php else : ?>
                    <tr class="cat-list-row<?php echo $i % 2; ?>" >
                <?php endif; ?>
                <th class="list-title" scope="row">
                    <?php if (in_array($post->access, $this->user->getAuthorisedViewLevels())) : ?>
                        <a href="<?php echo Route::_(RouteHelper::getPostRoute($post->slug, $post->catid, $post->language)); ?>">
                            <?php echo $this->escape($post->title); ?>
                        </a>
                        <?php if (Associations::isEnabled() && $this->params->get('show_associations')) : ?>
                            <div class="cat-list-association">
                            <?php $associations = AssociationHelper::displayAssociations($post->id); ?>
                            <?php foreach ($associations as $association) : ?>
                                <?php if ($this->params->get('flags', 1) && $association['language']->image) : ?>
                                    <?php $flag = HTMLHelper::_('image', 'mod_languages/' . $association['language']->image . '.gif', $association['language']->title_native, ['title' => $association['language']->title_native], true); ?>
                                    <a href="<?php echo Route::_($association['item']); ?>"><?php echo $flag; ?></a>
                                <?php else : ?>
                                    <?php $class = 'btn btn-secondary btn-sm btn-' . strtolower($association['language']->lang_code); ?>
                                    <a class="<?php echo $class; ?>" title="<?php echo $association['language']->title_native; ?>" href="<?php echo Route::_($association['item']); ?>"><?php echo $association['language']->lang_code; ?>
                                        <span class="visually-hidden"><?php echo $association['language']->title_native; ?></span>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else : ?>
                        <?php
                        echo $this->escape($post->title) . ' : ';
                        $itemId = Factory::getApplication()->getMenu()->getActive()->id;
                        $link   = new Uri(Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId, false));
                        $link->setVar('return', base64_encode(RouteHelper::getPostRoute($post->slug, $post->catid, $post->language)));
                        ?>
                        <a href="<?php echo $link; ?>" class="register">
                            <?php echo Text::_('COM_CODEX_REGISTER_TO_READ_MORE'); ?>
                        </a>
                        <?php if (Associations::isEnabled() && $this->params->get('show_associations')) : ?>
                            <div class="cat-list-association">
                            <?php $associations = AssociationHelper::displayAssociations($post->id); ?>
                            <?php foreach ($associations as $association) : ?>
                                <?php if ($this->params->get('flags', 1)) : ?>
                                    <?php $flag = HTMLHelper::_('image', 'mod_languages/' . $association['language']->image . '.gif', $association['language']->title_native, ['title' => $association['language']->title_native], true); ?>
                                    <a href="<?php echo Route::_($association['item']); ?>"><?php echo $flag; ?></a>
                                <?php else : ?>
                                    <?php $class = 'btn btn-secondary btn-sm btn-' . strtolower($association['language']->lang_code); ?>
                                    <a class="<?php echo $class; ?>" title="<?php echo $association['language']->title_native; ?>" href="<?php echo Route::_($association['item']); ?>"><?php echo $association['language']->lang_code; ?>
                                        <span class="visually-hidden"><?php echo $association['language']->title_native; ?></span>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($post->state == CodexComponent::CONDITION_UNPUBLISHED) : ?>
                        <div>
                            <span class="list-published badge bg-warning text-light">
                                <?php echo Text::_('JUNPUBLISHED'); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if ($post->publish_up > $currentDate) : ?>
                        <div>
                            <span class="list-published badge bg-warning text-light">
                                <?php echo Text::_('JNOTPUBLISHEDYET'); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if (!is_null($post->publish_down) && $post->publish_down < $currentDate) : ?>
                        <div>
                            <span class="list-published badge bg-warning text-light">
                                <?php echo Text::_('JEXPIRED'); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </th>
                <?php if ($this->params->get('list_show_date')) : ?>
                    <td class="list-date small">
                        <?php
                        echo HTMLHelper::_(
                            'date',
                            $post->displayDate,
                            $this->escape($this->params->get('date_format', Text::_('DATE_FORMAT_LC3')))
                        ); ?>
                    </td>
                <?php endif; ?>
                <?php if ($this->params->get('list_show_author', 1)) : ?>
                    <td class="list-author">
                        <?php if (!empty($post->author) || !empty($post->created_by_alias)) : ?>
                            <?php $author = $post->author ?>
                            <?php $author = $post->created_by_alias ?: $author; ?>
                            <?php if (!empty($post->contact_link) && $this->params->get('link_author')) : ?>
                                <?php if ($this->params->get('show_headings')) : ?>
                                    <?php echo HTMLHelper::_('link', $post->contact_link, $author); ?>
                                <?php else : ?>
                                    <?php echo Text::sprintf('COM_CODEX_WRITTEN_BY', HTMLHelper::_('link', $post->contact_link, $author)); ?>
                                <?php endif; ?>
                            <?php else : ?>
                                <?php if ($this->params->get('show_headings')) : ?>
                                    <?php echo $author; ?>
                                <?php else : ?>
                                    <?php echo Text::sprintf('COM_CODEX_WRITTEN_BY', $author); ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
                <?php if ($this->params->get('list_show_hits', 1)) : ?>
                    <td class="list-hits">
                        <span class="badge bg-info">
                            <?php if ($this->params->get('show_headings')) : ?>
                                <?php echo $post->hits; ?>
                            <?php else : ?>
                                <?php echo Text::sprintf('JGLOBAL_HITS_COUNT', $post->hits); ?>
                            <?php endif; ?>
                        </span>
                    </td>
                <?php endif; ?>
                <?php if ($this->params->get('list_show_votes', 0) && $this->vote) : ?>
                    <td class="list-votes">
                        <span class="badge bg-success">
                            <?php if ($this->params->get('show_headings')) : ?>
                                <?php echo $post->rating_count; ?>
                            <?php else : ?>
                                <?php echo Text::sprintf('COM_CODEX_VOTES_COUNT', $post->rating_count); ?>
                            <?php endif; ?>
                        </span>
                    </td>
                <?php endif; ?>
                <?php if ($this->params->get('list_show_ratings', 0) && $this->vote) : ?>
                    <td class="list-ratings">
                        <span class="badge bg-warning text-light">
                            <?php if ($this->params->get('show_headings')) : ?>
                                <?php echo $post->rating; ?>
                            <?php else : ?>
                                <?php echo Text::sprintf('COM_CODEX_RATINGS_COUNT', $post->rating); ?>
                            <?php endif; ?>
                        </span>
                    </td>
                <?php endif; ?>
                <?php if ($isEditable) : ?>
                    <td class="list-edit">
                        <?php if ($post->params->get('access-edit')) : ?>
                            <?php echo HTMLHelper::_('codexicon.edit', $post, $post->params); ?>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php // Code to add a link to submit an post. ?>
    <?php if ($this->category->getParams()->get('access-create')) : ?>
        <?php echo HTMLHelper::_('codexicon.create', $this->category, $this->category->params); ?>
    <?php endif; ?>

    <?php // Add pagination links ?>
    <?php if (!empty($this->items)) : ?>
        <?php if (($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
            <div class="com-content-category__navigation w-100">
                <?php if ($this->params->def('show_pagination_results', 1)) : ?>
                    <p class="com-content-category__counter counter float-end pt-3 pe-2">
                        <?php echo $this->pagination->getPagesCounter(); ?>
                    </p>
                <?php endif; ?>
                <div class="com-content-category__pagination">
                    <?php echo $this->pagination->getPagesLinks(); ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    <div>
        <input type="hidden" name="filter_order" value="">
        <input type="hidden" name="filter_order_Dir" value="">
        <input type="hidden" name="limitstart" value="">
        <input type="hidden" name="task" value="">
    </div>
</form>
