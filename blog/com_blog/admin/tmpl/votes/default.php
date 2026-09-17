<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<form action="<?php echo Route::_('index.php?option=com_blog&view=votes'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="alert alert-light border mb-3">
        <h1 class="h4 mb-1">Votes</h1>
        <p class="mb-0 small text-muted">Manage post ratings. Create, edit or delete votes; each change updates the associated post rating.</p>
    </div>
    <div class="row g-2 align-items-center mb-3">
        <div class="col-md-4"><label class="visually-hidden" for="vote-search">Post title</label><input placeholder="Search post titles" class="form-control" id="vote-search" name="search" value="<?php echo $escape($this->search); ?>"></div>
        <div class="col-md-2"><label class="visually-hidden" for="vote-post">Post ID</label><input placeholder="Post ID" type="number" min="0" class="form-control" id="vote-post" name="post_id" value="<?php echo $this->postId ?: ''; ?>"></div>
        <div class="col-md-2"><label class="visually-hidden" for="vote-score">Rating</label><select class="form-select" id="vote-score" name="score"><option value="0">All ratings</option><?php for ($score = 1; $score <= 5; $score++) : ?><option value="<?php echo $score; ?>" <?php echo $score === $this->score ? 'selected' : ''; ?>><?php echo $score; ?> / 5</option><?php endfor; ?></select></div>
        <div class="col-md-2"><?php echo $this->pagination->getLimitBox(); ?></div>
        <div class="col-md-2"><button class="btn btn-primary" type="submit" onclick="this.form.task.value='';this.form.limitstart.value=0;">Filter</button> <a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_blog&view=votes'); ?>">Clear</a></div>
    </div>
    <div class="table-responsive"><table class="table table-hover">
        <caption class="visually-hidden"><?php echo (int) $this->pagination->total; ?> votes</caption>
        <thead><tr><td><?php echo HTMLHelper::_('grid.checkall'); ?></td><th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'Post / page', 'p.title', $this->listDirn, $this->listOrder); ?></th><th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'Rating', 'v.rating', $this->listDirn, $this->listOrder); ?></th><th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'Voter', 'voter_name', $this->listDirn, $this->listOrder); ?></th><th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'Date', 'v.created', $this->listDirn, $this->listOrder); ?></th><th scope="col"><?php echo HTMLHelper::_('searchtools.sort', 'Vote ID', 'v.id', $this->listDirn, $this->listOrder); ?></th></tr></thead>
        <tbody><?php foreach ($this->items as $i => $item) : ?><tr>
            <td><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
            <th scope="row"><?php if ($item->post_title !== null) : ?><a href="<?php echo Route::_('index.php?option=com_blog&task=post.edit&id=' . (int) $item->post_id); ?>"><?php echo $escape($item->post_title); ?></a> <a class="small" target="_blank" rel="noopener noreferrer" href="<?php echo $escape(Uri::root() . 'index.php?option=com_blog&view=post&id=' . (int) $item->post_id); ?>">View page</a><?php else : ?>Deleted post<?php endif; ?> <span class="text-muted">#<?php echo (int) $item->post_id; ?></span></th>
            <td><span class="text-warning" aria-hidden="true"><?php for ($star = 1; $star <= 5; $star++) : ?><span class="<?php echo $star <= (int) $item->rating ? 'fa-solid' : 'fa-regular'; ?> fa-star"></span><?php endfor; ?></span> <?php echo (int) $item->rating; ?> / 5</td>
            <td><?php echo $escape($item->voter_name ?: (str_starts_with($item->voter_key, 'user:') ? 'User #' . substr($item->voter_key, 5) : 'Guest')); ?></td>
            <td><?php echo HTMLHelper::_('date', $item->created, 'Y-m-d H:i'); ?></td><td><?php if (\Joomla\CMS\Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_blog')) : ?><a href="<?php echo Route::_('index.php?option=com_blog&view=votes&layout=edit&id=' . (int) $item->id); ?>">Edit vote #<?php echo (int) $item->id; ?></a><?php else : ?><?php echo (int) $item->id; ?><?php endif; ?></td>
        </tr><?php endforeach; ?>
        <?php if (!$this->items) : ?><tr><td colspan="6">No votes found.</td></tr><?php endif; ?></tbody>
    </table></div>
    <?php echo $this->pagination->getListFooter(); ?>
    <input type="hidden" name="task" value=""><input type="hidden" name="boxchecked" value="0">
    <input type="hidden" name="filter_order" value="<?php echo $escape($this->listOrder); ?>">
    <input type="hidden" name="filter_order_Dir" value="<?php echo $escape($this->listDirn); ?>">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
