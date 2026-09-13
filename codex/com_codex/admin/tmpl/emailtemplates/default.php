<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<form action="<?php echo Route::_('index.php?option=com_codex&view=emailtemplates'); ?>" method="post" id="adminForm">
    <p>
        <a class="btn btn-success" href="<?php echo Route::_('index.php?option=com_codex&view=emailtemplate'); ?>">New Template</a>
        <button class="btn btn-danger" name="task" value="emailtemplates.delete">Delete</button>
    </p>
    <table class="table">
        <thead>
            <tr>
                <th></th>
                <th>Name</th>
                <th>Subject</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <tr>
                    <td><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                    <td><a href="<?php echo Route::_('index.php?option=com_codex&view=emailtemplate&id=' . (int) $item->id); ?>"><?php echo $escape($item->title); ?></a></td>
                    <td><?php echo $escape($item->subject); ?></td>
                    <td><?php echo $escape($item->created); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
