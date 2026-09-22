<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_codex
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
\Joomla\Component\Codex\Administrator\Helper\NeuralNetworkEditorHelper::load();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\Codex\Site\View\Form\HtmlView $this */
/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate')
    ->useScript('com_codex.form-edit');

$this->tab_name = 'content-view-form';
$this->ignore_fieldsets = ['image-featured', 'jmetadata', 'item_associations'];
$this->useCoreUI = true;

// Create shortcut to parameters.
$params = $this->state->get('params');

// This checks if the editor config options have ever been saved. If they haven't they will fall back to the original settings
if (!$params->exists('show_publishing_options')) {
    $params->set('show_urls_images_frontend', '0');
}
?>
<div class="edit item-page">
    <?php if ($params->get('show_page_heading')) : ?>
    <div class="page-header">
        <h1>
            <?php echo $this->escape($params->get('page_heading')); ?>
        </h1>
    </div>
    <?php endif; ?>

    <form action="<?php echo Route::_('index.php'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate form-vertical">
        <fieldset>
            <?php echo HTMLHelper::_('uitab.startTabSet', $this->tab_name, ['active' => 'editor', 'recall' => true, 'breakpoint' => 768]); ?>

            <?php echo HTMLHelper::_('uitab.addTab', $this->tab_name, 'editor', Text::_('COM_CODEX_POST_CONTENT')); ?>
                <?php echo $this->form->renderField('title'); ?>

                <?php echo $this->form->renderField('alias'); ?>


                <?php echo $this->form->renderField('post_content'); ?>
<?php echo $this->form->renderField('excerpt'); ?>
                <?php if ($params->get('show_urls_images_frontend')) : ?>
                    <fieldset id="fieldset-image-featured" class="options-form mt-4">
                        <legend><?php echo Text::_('Featured Image'); ?></legend>
                        <?php echo $this->form->renderField('featured_image', 'media'); ?>
                        <?php echo $this->form->renderField('featured_image_alt', 'media'); ?>
                        <?php echo $this->form->renderField('featured_image_alt_empty', 'media'); ?>
                        <?php echo $this->form->renderField('featured_image_caption', 'media'); ?>
                        <?php echo $this->form->renderField('featured_image_class', 'media'); ?>
                    </fieldset>
                <?php endif; ?>

                <?php if ($this->captchaEnabled) : ?>
                    <?php echo $this->form->renderField('captcha'); ?>
                <?php endif; ?>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>

            <?php echo LayoutHelper::render('codex.edit.params', $this); ?>

            <?php echo HTMLHelper::_('uitab.addTab', $this->tab_name, 'options', Text::_('JOPTIONS')); ?>
                <?php echo $this->form->renderField('transition'); ?>
                    <?php echo $this->form->renderField('state'); ?>
                    <?php echo $this->form->renderField('catid'); ?>
                    <?php if ($this->item->params->get('access-change')) : ?>
                        <?php echo $this->form->renderField('featured'); ?>
                    <?php endif; ?>
                    <?php echo $this->form->renderField('access'); ?>
                    <?php echo $this->form->renderField('language'); ?>
                    <?php echo $this->form->renderField('tags'); ?>
                    <?php echo $this->form->renderField('note'); ?>
                    <?php if ($params->get('save_history', 0)) : ?>
                        <?php echo $this->form->renderField('version_note'); ?>
                    <?php endif; ?>
                    <?php if (is_null($this->item->id)) : ?>
                        <div class="control-group">
                            <div class="controls">
                                <?php echo Text::_('COM_CODEX_ORDERING'); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php echo HTMLHelper::_('uitab.endTab'); ?>

                <?php if ($params->get('show_publishing_options', 1) == 1) : ?>
                    <?php echo HTMLHelper::_('uitab.addTab', $this->tab_name, 'publishing', Text::_('COM_CODEX_PUBLISHING')); ?>
                        <?php if ($this->item->params->get('access-change')) : ?>
                            <?php echo $this->form->renderField('publish_up'); ?>
                            <?php echo $this->form->renderField('publish_down'); ?>
                            <?php echo $this->form->renderField('featured_up'); ?>
                            <?php echo $this->form->renderField('featured_down'); ?>
                        <?php endif; ?>
                        <?php echo $this->form->renderField('created_by_alias'); ?>

                        <fieldset id="fieldset-metadata" class="options-form">
                            <legend><?php echo Text::_('COM_CODEX_METADATA'); ?></legend>
                            <?php echo $this->form->renderField('metadesc'); ?>
                            <?php echo $this->form->renderField('metakey'); ?>
<?php foreach ($this->form->getFieldset('jmetadata') as $seoField) { echo $seoField->renderField(); } ?>
                        </fieldset>
                    <?php echo HTMLHelper::_('uitab.endTab'); ?>
                <?php endif; ?>

            <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

            <?php echo $this->form->renderControlFields(); ?>
        </fieldset>
        <div class="d-grid gap-2 d-sm-block mb-2">
            <button type="button" class="btn btn-primary" data-submit-task="post.apply">
                <span class="icon-check" aria-hidden="true"></span>
                <?php echo Text::_('JSAVE'); ?>
            </button>
            <button type="button" class="btn btn-primary" data-submit-task="post.save">
                <span class="icon-check" aria-hidden="true"></span>
                <?php echo Text::_('JSAVEANDCLOSE'); ?>
            </button>
            <?php if ($this->showSaveAsCopy) : ?>
                <button type="button" class="btn btn-primary" data-submit-task="post.save2copy">
                    <span class="icon-copy" aria-hidden="true"></span>
                    <?php echo Text::_('JSAVEASCOPY'); ?>
                </button>
            <?php endif; ?>
            <button type="submit" form="post-cancel-form" class="btn btn-danger">
                <span class="icon-times" aria-hidden="true"></span>
                <?php echo Text::_('JCANCEL'); ?>
            </button>
            <?php if ($params->get('save_history', 0) && $this->item->id && ComponentHelper::isEnabled('com_contenthistory')) : ?>
                <?php echo $this->form->getInput('contenthistory'); ?>
            <?php endif; ?>
        </div>
    </form>
    <?php // Cancel is deliberately its own isolated form - no content fields, no
    // required-field validation to fight past, no dependency on the shared
    // Joomla.submitform()/data-submit-task JS mechanism. "novalidate" is a
    // plain HTML attribute here, not something toggled by JS at click time. ?>
    <form id="post-cancel-form" action="<?php echo Route::_('index.php'); ?>" method="post" novalidate>
        <input type="hidden" name="option" value="com_codex">
        <input type="hidden" name="task" value="post.cancel">
        <input type="hidden" name="a_id" value="<?php echo (int) $this->item->id; ?>">
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
