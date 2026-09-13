<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<form action="<?php echo Route::_('index.php?option=com_codex&view=subscribers'); ?>" method="get" class="row g-2 mb-3">
    <input type="hidden" name="option" value="com_codex">
    <input type="hidden" name="view" value="subscribers">
    <div class="col-md-5">
        <input class="form-control" name="filter_search" value="<?php echo $escape($this->search); ?>" placeholder="Search name or email">
    </div>
    <div class="col-md-3">
        <select class="form-select" name="filter_status">
            <option value="">All statuses</option>
            <option value="active" <?php echo $this->status === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="pending" <?php echo $this->status === 'pending' ? 'selected' : ''; ?>>Awaiting confirmation</option>
            <option value="disabled" <?php echo $this->status === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-primary">Filter</button></div>
    <div class="col-auto"><a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_codex&view=subscribers'); ?>">Clear</a></div>
</form>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-success" href="<?php echo Route::_('index.php?option=com_codex&task=subscribers.export'); ?>">Export CSV</a>
    <form action="<?php echo Route::_('index.php?option=com_codex&task=subscribers.import'); ?>" method="post" enctype="multipart/form-data" class="d-flex gap-2">
        <input class="form-control" type="file" name="csv_file" accept=".csv,text/csv" required>
        <button class="btn btn-primary" type="submit">Import CSV</button>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_codex&view=newsletter'); ?>">Create email</a>
</div>
<p class="text-muted">CSV columns: email, name. Removed addresses are suppressed and skipped during future imports.</p>

<form action="<?php echo Route::_('index.php?option=com_codex&view=subscribers'); ?>" method="post" id="adminForm">
    <p>
        <button class="btn btn-secondary" name="task" value="subscribers.edit">Edit</button>
        <button class="btn btn-success" name="task" value="subscribers.state" onclick="this.form.value.value=1">Enable</button>
        <button class="btn btn-warning" name="task" value="subscribers.state" onclick="this.form.value.value=0">Disable</button>
        <button class="btn btn-danger" name="task" value="subscribers.delete">Delete and suppress</button>
    </p>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th></th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th>Confirmed</th>
                    <th>Unsubscribed</th>
                    <th>Consent IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->items as $i => $item) : ?>
                    <?php $status = $item->state ? 'Active' : ($item->confirmed ? 'Disabled' : 'Awaiting confirmation'); ?>
                    <tr>
                        <td><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                        <td><a href="<?php echo Route::_('index.php?option=com_codex&view=subscriber&id=' . (int) $item->id); ?>"><?php echo $escape($item->name !== '' ? $item->name : $item->email); ?></a></td>
                        <td><?php echo $escape($item->email); ?></td>
                        <td><span class="badge bg-<?php echo $item->state ? 'success' : ($item->confirmed ? 'secondary' : 'warning text-dark'); ?>"><?php echo $status; ?></span></td>
                        <td><?php echo $escape($item->consented ?: $item->created); ?></td>
                        <td><?php echo $escape($item->confirmed ?? ''); ?></td>
                        <td><?php echo $escape($item->unsubscribed ?? ''); ?></td>
                        <td><?php echo $escape($item->consent_ip ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php echo $this->pagination->getListFooter(); ?>
    <input type="hidden" name="value" value="1">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
