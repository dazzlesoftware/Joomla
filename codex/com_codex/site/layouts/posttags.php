<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
?>
<?php if ($displayData) : ?><ul class="tags list-inline">
<?php foreach ($displayData as $tag) : ?>
<li class="list-inline-item"><a class="btn btn-sm btn-info" href="<?php echo Route::_('index.php?option=com_codex&view=tags&tag_id=' . (int) $tag->id); ?>"><?php echo htmlspecialchars($tag->title, ENT_QUOTES, 'UTF-8'); ?></a></li>
<?php endforeach; ?></ul><?php endif; ?>
