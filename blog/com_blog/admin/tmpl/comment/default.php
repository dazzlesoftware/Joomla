<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\Blog\Administrator\View\Comment\HtmlView $this */

$item = $this->item;
$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$created = $item->created !== '' ? str_replace(' ', 'T', substr($item->created, 0, 16)) : Factory::getDate()->format('Y-m-d\TH:i');
?>
<form action="<?php echo Route::_('index.php?option=com_blog&task=comment.save'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="alert alert-light border mb-3">
        <h1 class="h4 mb-1"><?php echo Text::_($item->id ? 'COM_BLOG_EDIT_COMMENT' : 'COM_BLOG_ADD_COMMENT'); ?></h1>
        <p class="mb-0 small text-muted"><?php echo Text::_('COM_BLOG_COMMENT_EDIT_DESC'); ?></p>
    </div>

    <div class="card">
        <div class="card-header"><?php echo Text::_('JGLOBAL_FIELDSET_BASIC'); ?></div>
        <div class="card-body">
            <input type="hidden" name="jform[id]" value="<?php echo (int) $item->id; ?>">
            <input type="hidden" name="jform[parent_id]" value="<?php echo (int) $item->parent_id; ?>">
            <input type="hidden" name="jform[user_id]" value="<?php echo (int) $item->user_id; ?>">

            <div class="mb-3">
                <?php echo $this->postField->renderField('post_id'); ?>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="jform_name"><?php echo Text::_('COM_BLOG_FIELD_COMMENT_NAME_LABEL'); ?></label>
                    <input required maxlength="255" class="form-control" id="jform_name" name="jform[name]" value="<?php echo $escape($item->name); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="jform_email"><?php echo Text::_('COM_BLOG_FIELD_COMMENT_EMAIL_LABEL'); ?></label>
                    <input type="email" maxlength="255" class="form-control" id="jform_email" name="jform[email]" value="<?php echo $escape($item->email); ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="jform_body"><?php echo Text::_('COM_BLOG_FIELD_COMMENT_BODY_LABEL'); ?></label>
                <textarea required class="form-control" id="jform_body" name="jform[body]" rows="5"><?php echo $escape($item->body); ?></textarea>
            </div>

            <div class="row g-3 mb-0">
                <div class="col-md-6">
                    <label class="form-label" for="jform_state"><?php echo Text::_('JSTATUS'); ?></label>
                    <select class="form-select" id="jform_state" name="jform[state]">
                        <option value="1"<?php echo $item->state ? ' selected' : ''; ?>><?php echo Text::_('COM_BLOG_COMMENT_APPROVED'); ?></option>
                        <option value="0"<?php echo !$item->state ? ' selected' : ''; ?>><?php echo Text::_('COM_BLOG_COMMENT_PENDING'); ?></option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="jform_created"><?php echo Text::_('COM_BLOG_FIELD_COMMENT_CREATED_LABEL'); ?></label>
                    <input type="datetime-local" class="form-control" id="jform_created" name="jform[created]" value="<?php echo $escape($created); ?>">
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
