<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
$tag = $this->directoryItem;
$media = json_decode($tag->post_media ?? '{}');
$image = \Joomla\Component\Academy\Site\Helper\FeaturedSliderHelper::imageUrl((string)($media->featured_image ?? ''));
?>
<div class="card category-directory-card">
<a href="<?php echo $this->escape(Route::_('index.php?option=com_academy&view=tags&tag_id='.(int)$tag->id,false)); ?>" aria-label="<?php echo $this->escape($tag->title); ?>">
<span class="category-directory-image"><?php if ($image) : ?><img src="<?php echo $this->escape($image); ?>" alt="" loading="lazy"><?php else : ?><span class="fa-solid fa-image" aria-hidden="true"></span><?php endif; ?></span>
</a>
<?php echo $this->loadTemplate('link'); ?>
</div>
