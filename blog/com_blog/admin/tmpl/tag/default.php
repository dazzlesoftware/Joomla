<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\Blog\Administrator\View\Tag\HtmlView $this */

$item = $this->item;
$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<form action="<?php echo Route::_('index.php?option=com_blog&task=tag.save'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="alert alert-light border mb-3">
        <h1 class="h4 mb-1"><?php echo Text::_($item->id ? 'COM_BLOG_EDIT_TAG' : 'COM_BLOG_ADD_TAG'); ?></h1>
        <p class="mb-0 small text-muted"><?php echo Text::_('COM_BLOG_TAG_EDIT_DESC'); ?></p>
    </div>

    <div class="card">
        <div class="card-header"><?php echo Text::_('JGLOBAL_FIELDSET_BASIC'); ?></div>
        <div class="card-body">
            <input type="hidden" name="jform[id]" value="<?php echo (int) $item->id; ?>">

            <div class="mb-3">
                <label class="form-label" for="jform_title"><?php echo Text::_('COM_BLOG_FIELD_TAG_TITLE_LABEL'); ?></label>
                <input required maxlength="255" class="form-control" id="jform_title" name="jform[title]" value="<?php echo $escape($item->title); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label" for="jform_alias"><?php echo Text::_('COM_BLOG_FIELD_TAG_ALIAS_LABEL'); ?></label>
                <input class="form-control" id="jform_alias" name="jform[alias]" maxlength="191" value="<?php echo $escape($item->alias); ?>" placeholder="<?php echo Text::_('COM_BLOG_FIELD_TAG_ALIAS_PLACEHOLDER'); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label" for="jform_description"><?php echo Text::_('COM_BLOG_FIELD_TAG_DESCRIPTION_LABEL'); ?></label>
                <textarea class="form-control" id="jform_description" name="jform[description]" rows="4"><?php echo $escape($item->description); ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label" for="jform_language"><?php echo Text::_('JFIELD_LANGUAGE_LABEL'); ?></label>
                <select class="form-select" id="jform_language" name="jform[language]">
                    <option value="*"<?php echo $item->language === '*' ? ' selected' : ''; ?>><?php echo Text::_('JALL'); ?></option>
                    <?php foreach ($this->languageOptions as $lang) : ?>
                        <option value="<?php echo $escape($lang->lang_code); ?>"<?php echo $item->language === $lang->lang_code ? ' selected' : ''; ?>><?php echo $escape($lang->title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3 form-check form-switch">
                <input type="hidden" name="jform[published]" value="0">
                <input
                    type="checkbox"
                    class="form-check-input"
                    role="switch"
                    id="jform_published"
                    name="jform[published]"
                    value="1"
                    <?php echo $item->published ? ' checked' : ''; ?>
                >
                <label class="form-check-label" for="jform_published"><?php echo Text::_('JPUBLISHED'); ?></label>
            </div>

            <div class="mb-0 form-check form-switch">
                <?php // A plain unchecked checkbox is simply omitted from the POST
                // body by the browser, so without this hidden fallback (submitted
                // first, then overridden by the checkbox's own value if it IS
                // checked) there is no way to ever save this toggle as "off". ?>
                <input type="hidden" name="jform[is_default]" value="0">
                <input
                    type="checkbox"
                    class="form-check-input"
                    role="switch"
                    id="jform_is_default"
                    name="jform[is_default]"
                    value="1"
                    <?php echo $item->is_default ? ' checked' : ''; ?>
                >
                <label class="form-check-label" for="jform_is_default"><?php echo Text::_('COM_BLOG_FIELD_TAG_DEFAULT_LABEL'); ?></label>
                <div class="form-text"><?php echo Text::_('COM_BLOG_FIELD_TAG_DEFAULT_DESC'); ?></div>
            </div>
        </div>
    </div>

    <?php echo HTMLHelper::_('form.token'); ?>
</form>
