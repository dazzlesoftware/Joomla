<?php
defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
?>
<?php echo LayoutHelper::render('postnav', ['params' => $this->params], JPATH_COMPONENT . '/layouts'); ?>
<div class="content-view-authors">
    <h1 class="mb-4"><?php echo $this->escape($this->getDocument()->getTitle()); ?></h1>
    <ul class="list-unstyled">
        <?php foreach ($this->items as $author) : ?>
            <li class="d-flex justify-content-between align-items-center py-2 border-top">
                <a href="<?php echo Route::_('index.php?option=com_academy&view=author&id=' . (int) $author->id); ?>"><?php echo $this->escape($author->name); ?></a>
                <span class="text-muted small"><?php echo (int) $author->post_count; ?> post<?php echo (int) $author->post_count === 1 ? '' : 's'; ?></span>
            </li>
        <?php endforeach; ?>
        <?php if (!$this->items) : ?><p class="alert alert-info">No authors have published posts yet.</p><?php endif; ?>
    </ul>
</div>
