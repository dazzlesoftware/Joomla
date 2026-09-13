<?php
defined('_JEXEC') or die;

use Joomla\CMS\Button\PublishedButton;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\Codex\Administrator\View\Comments\HtmlView $this */

$user = $this->getCurrentUser();
$canChange = $user->authorise('core.edit.state', 'com_codex');
$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<form action="<?php echo Route::_('index.php?option=com_codex&view=comments'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="alert alert-light border mb-3">
        <h1 class="h4 mb-1"><?php echo Text::_('COM_CODEX_COMMENTS_TITLE'); ?></h1>
        <p class="mb-0 small text-muted"><?php echo Text::_('COM_CODEX_COMMENTS_DESC'); ?></p>
    </div>

    <?php if (empty($this->items)) : ?>
        <div class="alert alert-info">
            <span class="icon-info-circle" aria-hidden="true"></span>
            <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
        </div>
    <?php else : ?>
        <div class="table-responsive">
            <table class="table table-hover" id="commentList">
                <caption class="visually-hidden"><?php echo Text::_('COM_CODEX_COMMENTS_TABLE_CAPTION'); ?></caption>
                <thead>
                    <tr>
                        <td class="w-1 text-center">
                            <?php echo HTMLHelper::_('grid.checkall'); ?>
                        </td>
                        <th scope="col"><?php echo Text::_('COM_CODEX_HEADING_AUTHOR'); ?></th>
                        <th scope="col"><?php echo Text::_('COM_CODEX_HEADING_COMMENT'); ?></th>
                        <th scope="col" class="d-none d-md-table-cell"><?php echo Text::_('COM_CODEX_HEADING_POST'); ?></th>
                        <th scope="col" class="w-5 text-center"><?php echo Text::_('JSTATUS'); ?></th>
                        <th scope="col" class="w-10 d-none d-md-table-cell"><?php echo Text::_('COM_CODEX_HEADING_CREATED'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($this->items as $i => $item) : ?>
                    <tr class="row<?php echo $i % 2; ?>">
                        <td class="text-center">
                            <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                        </td>
                        <th scope="row">
                            <a href="<?php echo Route::_('index.php?option=com_codex&view=comment&id=' . (int) $item->id); ?>">
                                <?php echo $escape($item->name); ?>
                            </a>
                        </th>
                        <td class="break-word">
                            <?php echo nl2br($escape(mb_substr($item->body, 0, 200))); ?><?php echo mb_strlen($item->body) > 200 ? '&hellip;' : ''; ?>
                        </td>
                        <td class="small d-none d-md-table-cell">
                            <?php echo $escape($item->post_title ?? ''); ?>
                        </td>
                        <td class="text-center">
                            <?php
                            $options = ['task_prefix' => 'comments.', 'disabled' => !$canChange, 'id' => 'comment-state-' . $item->id];
                    echo (new PublishedButton())->render((int) $item->state, $i, $options);
                    ?>
                        </td>
                        <td class="small d-none d-md-table-cell">
                            <?php echo $escape($item->created); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
