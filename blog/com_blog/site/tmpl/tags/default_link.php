<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
$tag = $this->directoryItem;
$url = Route::_('index.php?option=com_blog&view=tags&tag_id='.(int)$tag->id,false);
?>
<div class="border rounded p-3">
<div class="d-flex align-items-center gap-2">
<?php if ($this->params->get('tags_show_rss',1)) : ?><a href="<?php echo $this->escape(Route::_('index.php?option=com_blog&view=tags&tag_id='.(int)$tag->id.'&format=feed&type=rss',false)); ?>" aria-label="<?php echo $this->escape(Text::sprintf('COM_BLOG_TAGS_RSS',$tag->title)); ?>"><span class="fa-solid fa-rss" aria-hidden="true"></span></a><?php endif; ?>
<a href="<?php echo $this->escape($url); ?>"><?php echo $this->escape($tag->title); ?></a>
<?php if ($this->params->get('tags_show_count',1)) : ?><span class="badge bg-secondary ms-auto"><?php echo (int)$tag->post_count; ?><span class="visually-hidden"> <?php echo Text::_('COM_BLOG_TAGS_POSTS'); ?></span></span><?php endif; ?>
</div>
<?php if ($this->params->get('tags_show_description',1) && $tag->description) : ?><p class="mt-2 mb-0"><?php echo $this->escape(mb_substr(strip_tags($tag->description),0,max(1,(int)$this->params->get('tags_description_length',200)))); ?></p><?php endif; ?>
</div>
