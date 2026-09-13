<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;

$cards = [
    'posts' => ['All Posts', 'view=posts&filter[published]=*&filter[featured]=', 'file-alt'],
    'published' => ['Published', 'view=posts&filter[published]=1&filter[featured]=', 'check-circle'],
    'unpublished' => ['Unpublished', 'view=posts&filter[published]=0&filter[featured]=', 'edit'],
    'pending' => ['Pending Review', 'view=posts&filter[published]=-3&filter[featured]=', 'clock'],
    'archived' => ['Archived', 'view=posts&filter[published]=2&filter[featured]=', 'archive'],
    'trashed' => ['Trashed', 'view=posts&filter[published]=-2&filter[featured]=', 'trash'],
    'featured' => ['Featured', 'view=posts&filter[published]=*&filter[featured]=1', 'star'],
    'categories' => ['Categories', 'view=categories', 'folder'],
    'tags' => ['Tags', 'view=tags', 'tag'],
];

$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$task = $this->mailTask;
$taskEnabled = $task && (int) $task->state === 1;
$taskFailed = $task && (int) $task->last_exit_code < 0;
$queueStale = $this->stats['oldest_queued'] && strtotime($this->stats['oldest_queued']) < time() - 86400;
$needsAttention = $this->stats['mail_failed'] > 0 || ($this->stats['mail_queued'] > 0 && !$taskEnabled) || $queueStale || $taskFailed;

$stateLabels = [1 => 'Published', 0 => 'Draft', -3 => 'Pending', 2 => 'Archived', -2 => 'Trashed'];
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Academy Dashboard</h2>
            <p class="text-muted">Manage your posts and publishing workflow.</p>
        </div>
        <a class="btn btn-success" href="<?php echo Route::_('index.php?option=com_academy&task=post.add'); ?>">New Post</a>
    </div>

    <div class="row g-3 mb-4">
        <?php foreach ($cards as $key => $card) : ?>
            <div class="col-sm-6 col-xl-2">
                <a class="card text-decoration-none h-100" href="<?php echo Route::_('index.php?' . (str_starts_with($card[1], 'option=') ? $card[1] : 'option=com_academy&' . $card[1])); ?>">
                    <div class="card-body">
                        <span class="icon-<?php echo $card[2]; ?> me-2"></span><strong><?php echo $card[0]; ?></strong>
                        <div class="display-6 mt-2"><?php echo $this->stats[$key]; ?></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card mb-4 border-<?php echo $needsAttention ? 'warning' : 'success'; ?>">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Email Delivery</strong>
            <a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_scheduler&view=tasks'); ?>">Open Scheduled Tasks</a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><strong class="d-block fs-3"><?php echo $this->stats['subscribers']; ?></strong>Active subscribers</div>
                <div class="col-md-3"><strong class="d-block fs-3"><?php echo $this->stats['mail_queued']; ?></strong>Queued messages</div>
                <div class="col-md-3"><strong class="d-block fs-3"><?php echo $this->stats['mail_failed']; ?></strong>Failed messages</div>
                <div class="col-md-3">
                    <strong class="d-block"><?php echo !$task ? 'Not configured' : ($taskEnabled ? 'Enabled' : 'Disabled'); ?></strong>
                    <?php if ($task) : ?>
                        <small>Last run: <?php echo $escape($task->last_execution ?: 'Never'); ?><br>Next run: <?php echo $escape($task->next_execution ?: 'Not scheduled'); ?><br>Runs: <?php echo (int) $task->times_executed; ?> · Failures: <?php echo (int) $task->times_failed; ?></small>
                    <?php else : ?>
                        <a href="<?php echo Route::_('index.php?option=com_scheduler&view=select&layout=default'); ?>">Create the mail queue task</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($needsAttention) : ?>
                <div class="alert alert-warning mt-3 mb-0">
                    Email delivery needs attention.
                    <?php echo !$taskEnabled ? 'Create or enable the mail queue Scheduled Task. ' : ''; ?>
                    <?php echo $this->stats['mail_failed'] ? 'Review failed recipients and retry them. ' : ''; ?>
                    <?php echo $queueStale ? 'Messages have remained queued for more than 24 hours.' : ''; ?>
                </div>
            <?php elseif ($taskFailed) : ?>
                <div class="alert alert-warning mt-3 mb-0">The last Scheduled Task run reported a failure. Review its execution history.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Recent Posts</strong></div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->recent as $post) : ?>
                        <tr>
                            <td><a href="<?php echo Route::_('index.php?option=com_academy&task=post.edit&id=' . (int) $post->id); ?>"><?php echo $escape($post->title); ?></a></td>
                            <td><?php echo $stateLabels[$post->state] ?? 'Unknown'; ?></td>
                            <td><?php echo $escape($post->created); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
