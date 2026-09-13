<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$options = $this->options ?: [(object) ['title' => ''], (object) ['title' => '']];
$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$toDatetimeLocal = static fn ($value) => $value ? $escape(str_replace(' ', 'T', substr($value, 0, 16))) : '';
?>
<form action="<?php echo Route::_('index.php?option=com_codex&view=poll'); ?>" method="post" id="adminForm" name="adminForm">
    <div class="card card-body">
        <label class="form-label">Question</label>
        <input class="form-control mb-3" name="title" required value="<?php echo $escape($this->item->title ?? ''); ?>">

        <label class="form-label">Choices (one per field)</label>
        <div id="poll-options">
            <?php foreach ($options as $option) : ?>
                <input class="form-control mb-2" name="options[]" required value="<?php echo $escape($option->title); ?>">
            <?php endforeach; ?>
        </div>
        <button
            type="button"
            class="btn btn-secondary align-self-start mb-3"
            onclick="document.getElementById('poll-options').insertAdjacentHTML('beforeend','<input class=&quot;form-control mb-2&quot; name=&quot;options[]&quot; required>')"
        >Add choice</button>

        <label><input type="checkbox" name="multiple" value="1" <?php echo !empty($this->item->multiple) ? 'checked' : ''; ?>> Allow multiple choices</label>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <label class="form-label">Voting opens</label>
                <input class="form-control" type="datetime-local" name="publish_up" value="<?php echo $toDatetimeLocal($this->item->publish_up ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Voting closes</label>
                <input class="form-control" type="datetime-local" name="publish_down" value="<?php echo $toDatetimeLocal($this->item->publish_down ?? ''); ?>">
            </div>
        </div>

        <label class="mt-3">Status <select class="form-select" name="state"><option value="1">Published</option><option value="0" <?php echo isset($this->item) && !$this->item->state ? 'selected' : ''; ?>>Unpublished</option></select></label>
    </div>
    <input type="hidden" name="id" value="<?php echo (int) ($this->item->id ?? 0); ?>">
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
