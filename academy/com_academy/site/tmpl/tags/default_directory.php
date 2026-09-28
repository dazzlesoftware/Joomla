<?php
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$columns = $this->params->get('tags_listing_layout','columns') === 'columns' ? max(2,min(6,(int)$this->params->get('tags_columns',3))) : 1;
$masonry = $columns > 1 && $this->params->get('tags_column_style','grid') === 'masonry';
$style = $this->params->get('tags_style','link_grid') === 'image_grid' ? 'directory_image' : 'link';
$wa = $this->getDocument()->getWebAssetManager(); $wa->useStyle('fontawesome');
$wa->registerAndUseStyle('com_academy.category-directory','com_academy/category-directory.css',['version'=>'auto']);
if ($masonry) { $wa->registerAndUseScript('com_academy.post-masonry','com_academy/post-masonry.js',['version'=>'auto'],['defer'=>true]); }
?>
<?php if ($this->params->get('tags_show_search',1) || $this->params->get('tags_show_sort',1)) : ?>
<form action="<?php echo $this->escape(Route::_('index.php?option=com_academy&view=tags',false)); ?>" method="get" class="row g-2 mb-4">
<input type="hidden" name="option" value="com_academy"><input type="hidden" name="view" value="tags">
<input type="hidden" name="Itemid" value="<?php echo \Joomla\CMS\Factory::getApplication()->getInput()->getInt('Itemid'); ?>">
<?php if ($this->params->get('tags_show_search',1)) : ?><div class="col"><label class="visually-hidden" for="tag-search"><?php echo Text::_('COM_ACADEMY_TAGS_SEARCH'); ?></label><input id="tag-search" class="form-control" name="tag_search" value="<?php echo $this->escape($this->search); ?>" placeholder="<?php echo Text::_('COM_ACADEMY_TAGS_SEARCH'); ?>"></div><?php endif; ?>
<?php if ($this->params->get('tags_show_sort',1)) : ?><div class="col-md-4"><label class="visually-hidden" for="tag-sort"><?php echo Text::_('COM_ACADEMY_TAGS_ORDER'); ?></label><select class="form-select" id="tag-sort" name="tag_sort"><?php foreach (['title','title_desc','count','newest'] as $sort) : ?><option value="<?php echo $sort; ?>" <?php echo $this->sort === $sort ? 'selected' : ''; ?>><?php echo Text::_('COM_ACADEMY_TAGS_ORDER_'.strtoupper($sort)); ?></option><?php endforeach; ?></select></div><?php endif; ?>
<div class="col-auto"><button class="btn btn-primary" type="submit"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button></div>
</form>
<?php endif; ?>
<ul class="category-directory list-unstyled row row-cols-1 row-cols-md-<?php echo $columns; ?> g-3" <?php echo $masonry ? 'data-post-masonry' : ''; ?>>
<?php foreach ($this->tags as $tag) : $this->directoryItem = $tag; ?><li class="col"><?php echo $this->loadTemplate($style); ?></li><?php endforeach; ?>
</ul>
<?php if (!$this->tags) : ?><p class="alert alert-info"><?php echo Text::_('COM_ACADEMY_TAGS_EMPTY'); ?></p><?php endif; ?>
<?php if ($this->params->get('tags_pagination',1) && $this->pagination->pagesTotal > 1) : ?>
<?php echo $this->pagination->getPagesLinks(); ?>
<?php if ($this->params->get('tags_pagination_summary',1)) : ?><p class="text-muted"><?php echo $this->pagination->getPagesCounter(); ?></p><?php endif; ?>
<?php endif; ?>
