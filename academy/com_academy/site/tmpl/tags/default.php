<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Component\Academy\Site\Helper\RouteHelper;

$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<?php echo LayoutHelper::render('postnav', ['params' => $this->params], JPATH_COMPONENT . '/layouts'); ?>
<div class="content-view-tags">
<h1><?php echo $escape($this->tag->title ?? 'Tags'); ?></h1>
<?php if ($this->tag) : ?>
<p><a href="<?php echo Route::_('index.php?option=com_academy&view=tags'); ?>">All tags</a></p>
<?php if ($this->tag->description !== '') : ?><p><?php echo nl2br($escape(strip_tags($this->tag->description))); ?></p><?php endif; ?>
<?php echo $this->loadTemplate('posts'); ?>
<?php else : ?>
<?php echo $this->loadTemplate('directory'); ?>
<?php endif; ?></div>
