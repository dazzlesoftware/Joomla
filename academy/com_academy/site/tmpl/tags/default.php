<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Component\Academy\Site\Helper\RouteHelper;

$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<?php echo LayoutHelper::render('postnav', ['params' => $this->params], JPATH_COMPONENT . '/layouts'); ?>
<div class="com-academy-tags">
<h1><?php echo $escape($this->tag->title ?? 'Tags'); ?></h1>
<?php if ($this->tag) : ?>
<p><a href="<?php echo Route::_('index.php?option=com_academy&view=tags'); ?>">All tags</a></p>
<?php if ($this->tag->description !== '') : ?><p><?php echo nl2br($escape(strip_tags($this->tag->description))); ?></p><?php endif; ?>
<?php foreach ($this->items as $item) : $url = Route::_(RouteHelper::getPostRoute($item->id . ':' . $item->alias, $item->catid, $item->language ?? '*')); ?>
<article class="mb-4"><?php echo LayoutHelper::render('academy.content.featured_image', $item, JPATH_COMPONENT . '/layouts'); ?><h2><a href="<?php echo $url; ?>"><?php echo $escape($item->title); ?></a></h2>
<?php echo $item->summary; ?>
<a class="btn btn-outline-primary" href="<?php echo $url; ?>">Read more<span class="visually-hidden">: <?php echo $escape($item->title); ?></span></a></article>
<?php endforeach; ?>
<?php if (!$this->items) : ?><p>No published posts with this tag.</p><?php endif; ?>
<?php echo $this->pagination->getPagesLinks(); ?>
<?php else : ?>
<ul class="list-unstyled d-flex flex-wrap gap-2"><?php foreach ($this->tags as $tag) : ?><li><a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_academy&view=tags&tag_id=' . (int) $tag->id); ?>"><?php echo $escape($tag->title); ?></a></li><?php endforeach; ?></ul>
<?php if (!$this->tags) : ?><p>No tags yet.</p><?php endif; ?>
<?php endif; ?></div>
