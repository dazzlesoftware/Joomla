<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;

$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="com-academy-categories">
    <h1>Categories</h1>
    <ul>
        <?php foreach ($this->items as $item) : ?>
            <li><a href="<?php echo Route::_('index.php?option=com_academy&view=category&id=' . (int) $item->id); ?>"><?php echo $escape($item->title); ?></a> <span><?php echo (int) $item->numitems; ?></span></li>
        <?php endforeach; ?>
    </ul>
    <?php if (!$this->items) : ?>
        <p>No categories yet.</p>
    <?php endif; ?>
</div>
