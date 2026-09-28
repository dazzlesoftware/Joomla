<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
$tag = $this->directoryItem;
$media = json_decode($tag->post_media ?? '{}');
$image = (string)($media->featured_image ?? '');
$image = explode('#', $image)[0];
if ($image !== '' && (str_starts_with($image,'//') || preg_match('~^[a-z][a-z0-9+.-]*:~i',$image)) && !preg_match('~^https?://~i',$image)) { $image = ''; }
?>
<div class="card category-directory-card">
<a href="<?php echo $this->escape(Route::_('index.php?option=com_codex&view=tags&tag_id='.(int)$tag->id,false)); ?>" aria-label="<?php echo $this->escape($tag->title); ?>">
<span class="category-directory-image"><?php if ($image) : ?><img src="<?php echo $this->escape($image); ?>" alt="" loading="lazy"><?php else : ?><span class="fa-solid fa-image" aria-hidden="true"></span><?php endif; ?></span>
</a>
<?php echo $this->loadTemplate('link'); ?>
</div>
