<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Academy\Site\Helper\RouteHelper;

$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$stateLabels = [
    1  => Text::_('JPUBLISHED'),
    0  => Text::_('JUNPUBLISHED'),
    2  => Text::_('JARCHIVED'),
    -3 => Text::_('COM_ACADEMY_POST_PENDING'),
];
?>
<?php echo LayoutHelper::render('postnav', ['params' => $this->params], JPATH_COMPONENT . '/layouts'); ?>
<div class="com-academy-myposts">
    <h1 class="mb-4"><?php echo $escape(Text::_('COM_ACADEMY_MY_POSTS_LABEL')); ?></h1>
    <table class="table">
        <thead>
            <tr>
                <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                <th><?php echo Text::_('JSTATUS'); ?></th>
                <th><?php echo Text::_('JGLOBAL_MODIFIED'); ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($this->items as $item) : ?>
                <tr>
                    <td>
                        <a href="<?php echo Route::_(RouteHelper::getPostRoute($item->id . ':' . $item->alias, $item->catid, $item->language ?? '*')); ?>">
                            <?php echo $escape($item->title); ?>
                        </a>
                    </td>
                    <td><?php echo $escape($stateLabels[(int) $item->state] ?? $item->state); ?></td>
                    <td><?php echo $escape($item->modified ?: $item->created); ?></td>
                    <td>
                        <a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_academy&task=post.edit&a_id=' . (int) $item->id); ?>">
                            <?php echo Text::_('JACTION_EDIT'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$this->items) : ?>
                <tr><td colspan="4"><?php echo Text::_('COM_ACADEMY_MYPOSTS_EMPTY'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
