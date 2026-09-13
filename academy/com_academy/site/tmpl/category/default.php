<?php defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use Joomla\Component\Academy\Site\Helper\RouteHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;

?>
<div class="com-academy-category"><h1><?php echo htmlspecialchars($this->category->title, ENT_QUOTES, 'UTF-8');?></h1><?php if ($this->category->default_image):?><figure class="category-image"><img src="<?php echo htmlspecialchars(Uri::root().$this->category->default_image, ENT_QUOTES, 'UTF-8');?>" alt=""></figure><?php endif;?><?php if ($this->category->description):?><div><?php echo $this->category->description;?></div><?php endif;?><?php foreach ($this->items as $item):$url = Route::_(RouteHelper::getPostRoute($item->id.':'.$item->alias, $item->catid, $item->language ?? '*'));?><article class="mb-4"><?php echo LayoutHelper::render('joomla.content.featured_image', $item, JPATH_COMPONENT . '/layouts');?><h2><a href="<?php echo $url;?>"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8');?></a></h2><?php echo $item->summary;?><a href="<?php echo $url;?>">Read more</a></article><?php endforeach;?><?php if (!$this->items):?><p>No posts in this category.</p><?php endif;?><?php echo $this->pagination->getPagesLinks();?></div>
