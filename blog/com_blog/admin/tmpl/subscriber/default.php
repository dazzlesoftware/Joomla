<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

?>
<form action="<?php echo Route::_('index.php?option=com_blog&view=subscriber&id=' . (int) $this->item->id); ?>" method="post" id="adminForm">
    <div class="row g-3" style="max-width:640px;">
        <div class="col-md-6">
            <label class="form-label" for="field-name">Name</label>
            <input class="form-control" type="text" id="field-name" name="name" value="<?php echo htmlspecialchars($this->item->name, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="field-email">Email</label>
            <input class="form-control" type="email" id="field-email" name="email" value="<?php echo htmlspecialchars($this->item->email, ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="field-state">Status</label>
            <select class="form-select" id="field-state" name="state">
                <option value="1" <?php echo $this->item->state ? 'selected' : ''; ?>>Active</option>
                <option value="0" <?php echo !$this->item->state ? 'selected' : ''; ?>>Disabled</option>
            </select>
        </div>
        <div class="col-12 text-muted small">
            Requested: <?php echo htmlspecialchars($this->item->consented ?: $this->item->created, ENT_QUOTES, 'UTF-8'); ?>
            &nbsp;&middot;&nbsp; Confirmed: <?php echo htmlspecialchars($this->item->confirmed ?? '—', ENT_QUOTES, 'UTF-8'); ?>
            &nbsp;&middot;&nbsp; Unsubscribed: <?php echo htmlspecialchars($this->item->unsubscribed ?? '—', ENT_QUOTES, 'UTF-8'); ?>
            &nbsp;&middot;&nbsp; Consent IP: <?php echo htmlspecialchars($this->item->consent_ip ?? '—', ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="col-12">
            <button class="btn btn-success" type="submit" name="task" value="subscriber.save">Save</button>
            <button class="btn btn-secondary" type="submit" name="task" value="subscriber.cancel" formnovalidate>Cancel</button>
        </div>
    </div>
    <input type="hidden" name="id" value="<?php echo (int) $this->item->id; ?>">
    <input type="hidden" name="option" value="com_blog">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
