<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\Component\Academy\Administrator\View\Category\HtmlView $this */
?>
<form
    action="<?php echo Route::_('index.php?option=com_academy&view=category&id=' . (int) $this->item->id); ?>"
    method="post"
    name="adminForm"
    id="adminForm"
    enctype="multipart/form-data"
>
    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="jform_title"><?php echo Text::_('JGLOBAL_TITLE'); ?></label>
                        <input required class="form-control" id="jform_title" name="jform[title]" value="<?php echo htmlspecialchars($this->item->title, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="jform_alias"><?php echo Text::_('JFIELD_ALIAS_LABEL'); ?></label>
                        <input
                            class="form-control"
                            id="jform_alias"
                            name="jform[alias]"
                            value="<?php echo htmlspecialchars($this->item->alias, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="<?php echo htmlspecialchars($this->item->title, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="jform_parent_id"><?php echo Text::_('COM_ACADEMY_PARENT_CATEGORY_LABEL'); ?></label>
                            <select class="form-select" id="jform_parent_id" name="jform[parent_id]">
                                <option value="0"><?php echo Text::_('COM_ACADEMY_PARENT_CATEGORY_ROOT'); ?></option>
                                <?php foreach ($this->parentOptions as $option) : ?>
                                    <option value="<?php echo (int) $option->value; ?>"<?php echo (int) $this->item->parent_id === (int) $option->value ? ' selected' : ''; ?>>
                                        <?php echo str_repeat('- ', $option->level) . htmlspecialchars($option->text, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="jform_language"><?php echo Text::_('JGRID_HEADING_LANGUAGE'); ?></label>
                            <select class="form-select" id="jform_language" name="jform[language]">
                                <option value="*"<?php echo $this->item->language === '*' ? ' selected' : ''; ?>><?php echo Text::_('JALL'); ?></option>
                                <?php foreach ($this->languageOptions as $option) : ?>
                                    <option value="<?php echo htmlspecialchars($option->lang_code, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $this->item->language === $option->lang_code ? ' selected' : ''; ?>>
                                        <?php echo htmlspecialchars($option->title, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="jform_description"><?php echo Text::_('JGLOBAL_DESCRIPTION'); ?></label>
                        <textarea class="form-control" rows="8" id="jform_description" name="jform[description]"><?php echo htmlspecialchars($this->item->description, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="jform_default_tags"><?php echo Text::_('COM_ACADEMY_DEFAULT_TAGS_LABEL'); ?></label>
                        <input
                            class="form-control"
                            id="jform_default_tags"
                            name="jform[default_tags]"
                            value="<?php echo htmlspecialchars($this->item->default_tags, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="<?php echo Text::_('COM_ACADEMY_DEFAULT_TAGS_PLACEHOLDER'); ?>"
                        >
                        <div class="form-text"><?php echo Text::_('COM_ACADEMY_DEFAULT_TAGS_DESC'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="jform_published"><?php echo Text::_('JSTATUS'); ?></label>
                        <select class="form-select" id="jform_published" name="jform[published]">
                            <option value="1"<?php echo $this->item->published ? ' selected' : ''; ?>><?php echo Text::_('JPUBLISHED'); ?></option>
                            <option value="0"<?php echo $this->item->published ? '' : ' selected'; ?>><?php echo Text::_('JUNPUBLISHED'); ?></option>
                        </select>
                    </div>
                    <div class="mb-0 form-check form-switch">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            role="switch"
                            id="jform_allow_autoposting"
                            name="jform[allow_autoposting]"
                            value="1"
                            <?php echo $this->item->allow_autoposting ? ' checked' : ''; ?>
                        >
                        <label class="form-check-label" for="jform_allow_autoposting"><?php echo Text::_('COM_ACADEMY_ALLOW_AUTOPOSTING_LABEL'); ?></label>
                        <div class="form-text"><?php echo Text::_('COM_ACADEMY_ALLOW_AUTOPOSTING_DESC'); ?></div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <label class="form-label" for="jform_default_image"><?php echo Text::_('COM_ACADEMY_DEFAULT_POST_COVER_LABEL'); ?></label>
                    <?php if ($this->item->default_image !== '') : ?>
                        <img
                            src="<?php echo htmlspecialchars(Uri::root() . $this->item->default_image, ENT_QUOTES, 'UTF-8'); ?>"
                            alt=""
                            class="img-fluid rounded mb-2"
                        >
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="jform_remove_image" name="jform_remove_image" value="1">
                            <label class="form-check-label" for="jform_remove_image"><?php echo Text::_('COM_ACADEMY_DEFAULT_POST_COVER_REMOVE'); ?></label>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="jform_default_image" name="jform_default_image" accept="image/*">
                    <div class="form-text"><?php echo Text::_('COM_ACADEMY_DEFAULT_POST_COVER_DESC'); ?></div>
                    <input type="hidden" name="jform_existing_image" value="<?php echo htmlspecialchars($this->item->default_image, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="jform[id]" value="<?php echo (int) $this->item->id; ?>">
    <input type="hidden" name="jform[access]" value="1">
    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
