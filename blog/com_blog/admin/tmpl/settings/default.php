<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('bootstrap.tab');

$active = array_key_first($this->sections);
?>
<form action="<?php echo Route::_('index.php?option=com_blog&view=settings'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row g-4 com-blog-settings">
        <div class="col-12 col-lg-3 col-xl-2">
            <div class="card position-sticky" style="top:1rem">
                <div class="card-header"><strong>Settings</strong></div>
                <div class="list-group list-group-flush" role="tablist">
                    <?php foreach ($this->sections as $key => $section) : ?>
                        <button class="list-group-item list-group-item-action<?php echo $key === $active ? ' active' : ''; ?>" data-bs-toggle="list" data-bs-target="#settings-<?php echo $key; ?>" type="button" role="tab">
                            <span class="<?php echo $section['icon_class'] ?? 'icon-' . $section['icon']; ?> me-2" aria-hidden="true"></span><?php echo $section['label']; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-9 col-xl-10">
            <div class="tab-content">
                <?php foreach ($this->sections as $key => $section) : ?>
                    <section class="tab-pane fade<?php echo $key === $active ? ' show active' : ''; ?>" id="settings-<?php echo $key; ?>" role="tabpanel">
                        <div class="mb-3"><h2><?php echo $section['label']; ?></h2><p class="text-muted">Configure <?php echo strtolower($section['label']); ?> for Blog Posts.</p></div>
                        <div class="row g-4">
                            <?php foreach ($section['fieldsets'] as $fieldsetName) : $fieldset = $this->form->getFieldset($fieldsetName);
                                if (!$fieldset) {
                                    continue;
                                } ?>
                                <?php $meta = $this->form->getFieldsets('params')[$fieldsetName] ?? null; ?>
                                <div class="col-12 col-xxl-6">
                                    <div class="card h-100">
                                        <div class="card-header"><strong><?php echo Text::_($meta->label ?? $fieldsetName); ?></strong></div>
                                        <div class="card-body"><?php foreach ($fieldset as $field) {
                                            echo $field->renderField();
                                        } ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <input type="hidden" name="task" value="settings.save">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
