<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_blog
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/** @var \Joomla\Component\Blog\Administrator\View\Post\HtmlView $this */

defined('_JEXEC') or die;
\Joomla\Component\Blog\Administrator\Helper\NeuralNetworkEditorHelper::load();

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_blog.post-editor', 'com_blog/admin-post-editor.css', ['version' => '1.0.3']);
$wa->registerAndUseScript('com_blog.post-editor', 'com_blog/admin-post-editor.js', ['version' => '1.0.2'], ['type' => 'module']);
$wa->getRegistry()->addExtensionRegistryFile('com_contenthistory');
$wa->registerAndUseScript('com_blog.block-editor', 'com_blog/admin-block-editor.js', ['version' => '1.4.1'], ['type' => 'module'])
    ->registerAndUseStyle('com_blog.block-editor', 'com_blog/admin-block-editor.css', ['version' => '1.4.1']);
$wa->useScript('keepalive')
    ->useScript('form.validate')
    ->useScript('com_contenthistory.admin-history-versions');

$this->configFieldsets  = ['editorConfig'];
$this->hiddenFieldsets  = ['basic-limited'];
$fieldsetsInImages = ['image-featured'];
$this->ignore_fieldsets = array_merge(['jmetadata', 'item_associations'], $fieldsetsInImages);
$this->useCoreUI = true;

// Create shortcut to parameters.
$params = clone $this->state->get('params');
$params->merge(new Registry($this->item->options));

$input = Factory::getApplication()->getInput();

$assoc              = Associations::isEnabled();
$showPostOptions = $params->get('show_post_options', 1);

if (!$assoc || !$showPostOptions) {
    $this->ignore_fieldsets[] = 'frontendassociations';
}

if (!$showPostOptions) {
    // Ignore fieldsets inside Options tab
    $this->ignore_fieldsets = array_merge($this->ignore_fieldsets, ['options', 'basic', 'category', 'author', 'date', 'other']);
}

// In case of modal
$isModal = $input->get('layout') === 'modal';
if (!$isModal && $input->getCmd('tmpl') !== 'component') {
    $input->set('hidemainmenu', false);
}
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $input->get('tmpl');
$tmpl    = $tmpl ? '&tmpl=' . $tmpl : '';
?>
<form action="<?php echo Route::_('index.php?option=com_blog&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="item-form" aria-label="<?php echo Text::_('COM_BLOG_FORM_TITLE_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>" class="form-validate">
    <div class="post-editor-workspace">
        <div class="post-editor-heading">
            <span class="post-editor-eyebrow"><?php echo Text::_('COM_BLOG_EDITOR_WRITING'); ?></span>
            <div class="post-editor-actions">
                <a href="#post-advanced" class="btn btn-link" data-post-advanced-link><?php echo Text::_('COM_BLOG_EDITOR_ADVANCED'); ?></a>
                <button type="button" class="btn btn-outline-secondary" data-post-focus aria-pressed="false" aria-controls="post-settings" hidden><?php echo Text::_('COM_BLOG_EDITOR_FOCUS'); ?></button>
            </div>
        </div>
        <div class="post-editor-grid">
            <div class="post-editor-writing" data-post-editor-column>
                <div class="post-editor-title form-vertical">
                    <?php $this->form->setFieldAttribute('title', 'hint', 'COM_BLOG_EDITOR_TITLE_HINT'); ?>
                    <?php echo $this->form->renderField('title'); ?>
                </div>
                <div>
                    <fieldset class="adminform">
                        <?php
                        $pollDb = Factory::getContainer()->get(DatabaseInterface::class);
                        $pollItems = $pollDb->setQuery(
                            $pollDb->createQuery()->select(['id', 'title'])->from('#__blog_polls')->where('state=1')->order('title')
                        )->loadObjectList();
                        ?>
                        <details class="post-editor-excerpt" <?php echo trim((string) $this->form->getValue('excerpt')) !== '' ? 'open' : ''; ?>>
                            <summary><?php echo Text::_('COM_BLOG_FIELD_EXCERPT_LABEL'); ?><span><?php echo Text::_('COM_BLOG_EDITOR_OPTIONAL'); ?></span></summary>
                            <div class="post-editor-panel-body form-vertical">
                                <?php echo $this->form->renderField('excerpt'); ?>
                                <p class="post-editor-hint"><?php echo Text::_('COM_BLOG_FIELD_EXCERPT_DESC'); ?></p>
                            </div>
                        </details>
                        <div data-post-block-editor data-editor-id="jform_post_content" data-default-mode="<?php echo htmlspecialchars(ComponentHelper::getParams('com_blog')->get('default_editor_mode', 'classic'), ENT_QUOTES, 'UTF-8'); ?>" data-polls="<?php echo htmlspecialchars(json_encode($pollItems), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo $this->form->getInput('block_data'); ?>
                            <div data-classic-editor><?php echo $this->form->getLabel('post_content'); ?><?php echo $this->form->getInput('post_content'); ?></div>
                            <div data-block-composer hidden>
                                <div class="post-composer-shell">
                                    <main class="post-composer-main"><div class="post-block-canvas" data-block-canvas></div></main>
                                    <aside class="post-block-palette">
                                        <h3>Insert Block</h3>
                                        <label class="visually-hidden" for="post-block-search">Search blocks</label>
                                        <input id="post-block-search" class="form-control mb-3" type="search" placeholder="Search blocks">
                                        <?php
                                        // Each group heading maps to the block types shown under it. Type
                                        // names are used as-is for the button label (Title Case via
                                        // ucfirst), except where overridden in $blockLabelOverrides.
                                        $blockPalette = [
                                            'Layout' => ['heading', 'text', 'tabs', 'columns', 'table', 'section', 'accordion'],
                                            'Elements' => ['alert', 'quote', 'button', 'link', 'code'],
                                            'Media' => ['image', 'video', 'audio', 'comparison'],
                                            'Joomla' => ['html', 'rule', 'readmore', 'pagebreak', 'module', 'polls'],
                                            'Embeddables' => ['gist', 'instagram', 'spotify', 'behance', 'soundcloud', 'slideshare', 'codepen', 'tweet', 'pinterest', 'youtube', 'vimeo', 'dailymotion', 'ted', 'facebook'],
                                        ];
                                        $blockLabelOverrides = ['polls' => 'Poll'];
                                        ?>
                                        <?php foreach ($blockPalette as $heading => $blockTypes) : ?>
                                            <h4><?php echo $heading; ?></h4>
                                            <div class="post-block-palette-grid">
                                                <?php foreach ($blockTypes as $blockType) : ?><button type="button" class="btn btn-outline-secondary" data-add-block="<?php echo $blockType; ?>"><?php echo $blockLabelOverrides[$blockType] ?? ucfirst($blockType); ?></button><?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </aside>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>
            <aside class="post-editor-settings" id="post-settings" aria-label="<?php echo Text::_('COM_BLOG_EDITOR_SETTINGS'); ?>" data-post-global-column>
                <h2><?php echo Text::_('COM_BLOG_EDITOR_SETTINGS'); ?></h2>
                <details class="post-editor-panel">
                    <summary><?php echo Text::_('COM_BLOG_EDITOR_VISIBILITY'); ?></summary>
                    <div class="post-editor-panel-body form-vertical">
                        <?php echo $this->form->renderField('alias'); ?>
                        <?php $this->fields = ['access', 'language']; ?>
                        <?php echo LayoutHelper::render('blog.edit.global', $this); ?>
                    </div>
                </details>
                <details class="post-editor-panel" open>
                    <summary><?php echo Text::_('COM_BLOG_EDITOR_PUBLISH'); ?></summary>
                    <div class="post-editor-panel-body">
                        <?php $this->fields = ['transition', ['published', 'state', 'enabled'], ['category', 'catid'], 'featured', 'tags']; ?>
                        <?php echo LayoutHelper::render('blog.edit.global', $this); ?>
                    </div>
                </details>
                <?php if ($params->get('show_urls_images_backend') == 1) : ?>
                    <details class="post-editor-panel" open>
                        <summary><?php echo Text::_($this->form->getFieldsets()['image-featured']->label); ?></summary>
                        <div id="fieldset-image-featured" class="post-editor-panel-body form-vertical">
                            <?php echo $this->form->renderField('featured_image', 'media'); ?>
                            <details class="post-editor-image-options">
                                <summary><?php echo Text::_('COM_BLOG_EDITOR_IMAGE_DETAILS'); ?></summary>
                                <?php foreach ($this->form->getFieldset('image-featured') as $imageField) : ?>
                                    <?php if ($imageField->fieldname !== 'featured_image') {
                                        echo $imageField->renderField();
                                    } ?>
                                <?php endforeach; ?>
                            </details>
                        </div>
                    </details>
                <?php endif; ?>

                <details class="post-editor-panel">
                    <summary><?php echo Text::_('COM_BLOG_EDITOR_NOTES'); ?></summary>
                    <div class="post-editor-panel-body">
                        <?php $this->fields = ['note', 'version_note']; ?>
                        <?php echo LayoutHelper::render('blog.edit.global', $this); ?>
                        <?php unset($this->fields); ?>
                    </div>
                </details>
            </aside>
        </div>
        <details class="post-editor-advanced" id="post-advanced">
        <summary><?php echo Text::_('COM_BLOG_EDITOR_ADVANCED'); ?><span><?php echo Text::_('COM_BLOG_EDITOR_ADVANCED_HINT'); ?></span></summary>
        <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['recall' => true, 'breakpoint' => 768]); ?>
        <?php echo LayoutHelper::render('blog.edit.params', $this); ?>

        <?php // Do not show the publishing options if the edit form is configured not to.?>
        <?php if ($params->get('show_publishing_options', 1) == 1) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('COM_BLOG_FIELDSET_PUBLISHING')); ?>
            <div class="row">
                <div class="col-12 col-lg-6">
                    <fieldset id="fieldset-publishingdata" class="options-form">
                        <legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
                        <div>
                        <?php echo LayoutHelper::render('blog.edit.publishingdata', $this); ?>
                        </div>
                    </fieldset>
                </div>
                <div class="col-12 col-lg-6">
                    <fieldset id="fieldset-metadata" class="options-form">
                        <legend><?php echo Text::_('SEO and Social Sharing'); ?></legend>
                        <div>
                        <?php echo LayoutHelper::render('blog.edit.metadata', $this); ?>
                        </div>
                    </fieldset>
                </div>
            </div>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php endif; ?>

        <?php if (!$isModal && $assoc && $params->get('show_associations_edit', 1) == 1) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'associations', Text::_('JGLOBAL_FIELDSET_ASSOCIATIONS')); ?>
            <fieldset id="fieldset-associations" class="options-form">
            <legend><?php echo Text::_('JGLOBAL_FIELDSET_ASSOCIATIONS'); ?></legend>
            <div>
            <?php echo LayoutHelper::render('blog.edit.associations', $this); ?>
            </div>
            </fieldset>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php elseif ($isModal && $assoc) : ?>
            <div class="hidden"><?php echo LayoutHelper::render('blog.edit.associations', $this); ?></div>
        <?php endif; ?>

        <?php if ($this->canDo->get('core.admin') && $params->get('show_configure_edit_options', 1) == 1) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'editor', Text::_('COM_BLOG_SLIDER_EDITOR_CONFIG')); ?>
            <fieldset id="fieldset-editor" class="options-form">
                <legend><?php echo Text::_('COM_BLOG_SLIDER_EDITOR_CONFIG'); ?></legend>
                <div class="form-grid">
                <?php echo $this->form->renderFieldset('editorConfig'); ?>
                </div>
            </fieldset>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php endif; ?>

        <?php if ($this->canDo->get('core.admin') && $params->get('show_permissions', 1) == 1) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('COM_BLOG_FIELDSET_RULES')); ?>
            <fieldset id="fieldset-rules" class="options-form">
                <legend><?php echo Text::_('COM_BLOG_FIELDSET_RULES'); ?></legend>
                <div>
                <?php echo $this->form->getInput('rules'); ?>
                </div>
            </fieldset>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php endif; ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
        </div>
        </details>

        <?php // Creating 'id' hiddenField to cope with com_associations sidebyside loop?>
        <?php if ($params->get('show_publishing_options', 1) == 0) : ?>
            <?php $hidden_fields = $this->form->getInput('id'); ?>
            <div class="hidden"><?php echo $hidden_fields; ?></div>
        <?php endif; ?>

        <?php echo $this->form->renderControlFields(); ?>
    </div>
</form>
