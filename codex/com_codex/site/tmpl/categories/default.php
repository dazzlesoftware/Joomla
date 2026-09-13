<?php defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;

?>
<div class="com-codex-categories"><h1>Categories</h1><ul><?php foreach ($this->items as $item):?><li><a href="<?php echo Route::_('index.php?option=com_codex&view=category&id='.(int)$item->id);?>"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8');?></a> <span><?php echo(int)$item->numitems;?></span></li><?php endforeach;?></ul><?php if (!$this->items):?><p>No categories yet.</p><?php endif;?></div>
