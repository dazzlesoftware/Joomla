<?php

defined('_JEXEC') or die;

use Joomla\CMS\Button\PublishedButton;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\Academy\Administrator\View\Categories\HtmlView $this */

$user = $this->getCurrentUser();
$canOrder = $user->authorise('core.edit.state', 'com_academy');
?>
<form action="<?php echo Route::_('index.php?option=com_academy&view=categories'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="alert alert-light border mb-3">
        <h1 class="h4 mb-1"><?php echo Text::_('COM_ACADEMY_CATEGORIES_TITLE'); ?></h1>
        <p class="mb-0 small text-muted"><?php echo Text::_('COM_ACADEMY_CATEGORIES_DESC'); ?></p>
    </div>

    <div class="row g-2 align-items-center mb-3">
        <div class="col-md-4">
            <div class="input-group">
                <input
                    type="text"
                    name="filter_search"
                    id="filter_search"
                    class="form-control"
                    placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>"
                    value="<?php echo htmlspecialchars($this->search, ENT_QUOTES, 'UTF-8'); ?>"
                >
                <button type="submit" class="btn btn-primary" aria-label="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
                    <span class="icon-search" aria-hidden="true"></span>
                </button>
            </div>
        </div>
        <div class="col-md-3">
            <select name="filter_published" id="filter_published" class="form-select" onchange="this.form.submit()">
                <option value=""><?php echo Text::_('JOPTION_SELECT_PUBLISHED'); ?></option>
                <option value="1"<?php echo $this->filterPublished === '1' ? ' selected' : ''; ?>><?php echo Text::_('JPUBLISHED'); ?></option>
                <option value="0"<?php echo $this->filterPublished === '0' ? ' selected' : ''; ?>><?php echo Text::_('JUNPUBLISHED'); ?></option>
            </select>
        </div>
        <div class="col-md-5 text-md-end">
            <?php echo $this->pagination->getLimitBox(); ?>
        </div>
    </div>

    <?php if (empty($this->items)) : ?>
        <div class="alert alert-info">
            <span class="icon-info-circle" aria-hidden="true"></span>
            <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
        </div>
    <?php else : ?>
        <div class="table-responsive">
            <table class="table table-hover" id="categoryList">
                <caption class="visually-hidden"><?php echo Text::_('COM_ACADEMY_CATEGORIES_TABLE_CAPTION'); ?></caption>
                <thead>
                    <tr>
                        <td class="w-1 text-center">
                            <?php echo HTMLHelper::_('grid.checkall'); ?>
                        </td>
                        <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                            <?php echo Text::_('JGRID_HEADING_ORDERING'); ?>
                        </th>
                        <th scope="col">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'c.title', $this->listDirn, $this->listOrder); ?>
                        </th>
                        <th scope="col" class="w-5 text-center">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'c.published', $this->listDirn, $this->listOrder); ?>
                        </th>
                        <th scope="col" class="w-5 text-center d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_ACADEMY_HEADING_POSTS', 'post_count', $this->listDirn, $this->listOrder); ?>
                        </th>
                        <th scope="col" class="w-10 text-center d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'COM_ACADEMY_HEADING_SUBCATEGORIES', 'sub_count', $this->listDirn, $this->listOrder); ?>
                        </th>
                        <th scope="col" class="w-10 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'c.language', $this->listDirn, $this->listOrder); ?>
                        </th>
                        <th scope="col" class="w-10 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JAUTHOR', 'author_name', $this->listDirn, $this->listOrder); ?>
                        </th>
                        <th scope="col" class="w-5 d-none d-lg-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'c.id', $this->listDirn, $this->listOrder); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($this->items as $i => $item) : ?>
                    <tr class="row<?php echo $i % 2; ?>">
                        <td class="text-center">
                            <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                        </td>
                        <td class="text-center d-none d-md-table-cell">
                            <span class="sortable-handler<?php echo $canOrder ? '' : ' inactive'; ?>" title="<?php echo Text::_('JORDERINGDISABLED'); ?>">
                                <span class="icon-ellipsis-v" aria-hidden="true"></span>
                            </span>
                        </td>
                        <th scope="row">
                            <a href="<?php echo Route::_('index.php?option=com_academy&view=category&id=' . (int) $item->id); ?>">
                                <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </th>
                        <td class="text-center">
                            <?php
                            $options = ['task_prefix' => 'categories.', 'disabled' => !$canOrder, 'id' => 'cat-state-' . $item->id];
                    echo (new PublishedButton())->render((int) $item->published, $i, $options);
                    ?>
                        </td>
                        <td class="text-center d-none d-md-table-cell">
                            <span class="badge bg-secondary"><?php echo (int) $item->post_count; ?></span>
                        </td>
                        <td class="text-center d-none d-md-table-cell">
                            <span class="badge bg-secondary"><?php echo (int) $item->sub_count; ?></span>
                        </td>
                        <td class="small d-none d-md-table-cell">
                            <?php echo $item->language === '*' ? Text::_('JALL') : htmlspecialchars($item->language, ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td class="small d-none d-md-table-cell">
                            <?php echo htmlspecialchars($item->author_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <?php echo (int) $item->id; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php echo $this->pagination->getListFooter(); ?>
    <?php endif; ?>

    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <input type="hidden" name="filter_order" value="<?php echo htmlspecialchars($this->listOrder, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="filter_order_Dir" value="<?php echo htmlspecialchars($this->listDirn, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
