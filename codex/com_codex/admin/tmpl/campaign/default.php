<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$sent = 0;
$pending = 0;
$failed = 0;
$skipped = 0;
$opens = 0;
$clicks = 0;
foreach ($this->recipients as $row) {
    $row->state == 1 ? $sent++ : ($row->state == -1 ? $failed++ : ($row->state == 2 ? $skipped++ : $pending++));
    $opens += (int) ($row->opened_count ?? 0);
    $clicks += (int) ($row->clicked_count ?? 0);
}

$campaignLabels = [-1 => 'Completed with failures', 0 => 'Queued', 1 => 'Completed', 2 => 'Scheduled', 3 => 'Paused', 4 => 'Canceled'];
$campaignLabel = $campaignLabels[(int) $this->item->state] ?? 'Unknown';

$recipientBadges = [1 => ['success', 'Sent'], -1 => ['danger', 'Failed'], 2 => ['warning', 'Skipped']];
$recipientBadge = static fn ($state) => $recipientBadges[$state] ?? ['secondary', 'Queued'];
?>
<div class="d-flex gap-2 mb-3">
    <form action="<?php echo Route::_('index.php?option=com_codex&task=newsletter.send'); ?>" method="post">
        <input type="hidden" name="campaign_id" value="<?php echo (int) $this->item->id; ?>">
        <button class="btn btn-success" type="submit" <?php echo $pending && (int) $this->item->state === 0 ? '' : 'disabled'; ?>>Send next batch (25)</button>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <form action="<?php echo Route::_('index.php?option=com_codex&task=newsletter.retry'); ?>" method="post">
        <input type="hidden" name="campaign_id" value="<?php echo (int) $this->item->id; ?>">
        <button class="btn btn-warning" type="submit" <?php echo $failed ? '' : 'disabled'; ?>>Retry this campaign’s failures</button>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>

<div class="d-flex gap-2 mb-3">
    <form action="<?php echo Route::_('index.php?option=com_codex&task=newsletter.state'); ?>" method="post">
        <input type="hidden" name="campaign_id" value="<?php echo (int) $this->item->id; ?>">
        <input type="hidden" name="campaign_state" value="<?php echo (int) $this->item->state === 3 ? 0 : 3; ?>">
        <button class="btn btn-secondary" type="submit" <?php echo in_array((int) $this->item->state, [1, 4], true) ? 'disabled' : ''; ?>><?php echo (int) $this->item->state === 3 ? 'Resume / Send now' : 'Pause'; ?></button>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <form action="<?php echo Route::_('index.php?option=com_codex&task=newsletter.state'); ?>" method="post">
        <input type="hidden" name="campaign_id" value="<?php echo (int) $this->item->id; ?>">
        <input type="hidden" name="campaign_state" value="4">
        <button class="btn btn-danger" type="submit" <?php echo in_array((int) $this->item->state, [1, 4], true) ? 'disabled' : ''; ?>>Cancel campaign</button>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>

<p><a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_codex&task=newsletter.export&campaign_id=' . (int) $this->item->id); ?>">Export recipient report (CSV)</a></p>

<div class="alert alert-info">
    <strong>Status:</strong> <?php echo $campaignLabel; ?>
    <span class="ms-3"><strong>Segment:</strong> <?php echo $escape($this->item->segment ?? 'all'); ?></span>
    <?php if (!empty($this->item->scheduled_at)) : ?>
        <span class="ms-3"><strong>Scheduled:</strong> <?php echo $escape($this->item->scheduled_at); ?></span>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card card-body"><strong><?php echo $opens; ?></strong><span>Tracked opens</span></div>
    </div>
    <div class="col-md-6">
        <div class="card card-body"><strong><?php echo $clicks; ?></strong><span>Tracked link clicks</span></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col">
        <div class="card card-body"><strong><?php echo count($this->recipients); ?></strong><span>Recipients</span></div>
    </div>
    <div class="col">
        <div class="card card-body"><strong><?php echo $sent; ?></strong><span>Sent</span></div>
    </div>
    <div class="col">
        <div class="card card-body"><strong><?php echo $pending; ?></strong><span>Queued</span></div>
    </div>
    <div class="col">
        <div class="card card-body"><strong><?php echo $failed; ?></strong><span>Failed</span></div>
    </div>
    <div class="col">
        <div class="card card-body"><strong><?php echo $skipped; ?></strong><span>Skipped</span></div>
    </div>
</div>

<div class="card card-body mb-4">
    <h2 class="h4"><?php echo $escape($this->item->subject); ?></h2>
    <p class="text-muted"><?php echo $escape($this->item->created); ?></p>
    <details>
        <summary>View email content</summary>
        <div class="border rounded p-3 mt-3"><?php echo $this->item->body; ?></div>
    </details>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Attempts</th>
            <th>Error</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($this->recipients as $row) : ?>
            <?php [$badgeClass, $badgeLabel] = $recipientBadge($row->state); ?>
            <tr>
                <td><?php echo $escape($row->name); ?></td>
                <td><?php echo $escape($row->email); ?></td>
                <td><span class="badge bg-<?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span></td>
                <td><?php echo (int) $row->attempts; ?></td>
                <td><?php echo $escape($row->error); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
