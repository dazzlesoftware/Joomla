<?php defined('_JEXEC') or die;
if (!$list) {
    return;
} ?>
<ul class="content-module-news list-unstyled">
<?php foreach ($list as $item):?><li class="mb-2"><a href="<?php echo $item->link;?>"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8');?></a><?php if (isset($item->count)):?> <span class="badge bg-secondary"><?php echo(int)$item->count;?></span><?php endif;?><?php if ($dateHtml = \Joomla\Component\Blog\Site\Helper\DateHelper::render($item, $dateParams, false)): ?><div class="small text-muted"><?php echo $dateHtml; ?></div><?php endif; ?><?php if ($mode === 'news' && !empty($item->summary)):?><div class="small mt-1"><?php echo $item->summary;?></div><?php endif;?><?php if (!empty($item->readmore)): ?><p><a class="btn btn-secondary btn-sm" href="<?php echo htmlspecialchars($item->link, ENT_QUOTES, 'UTF-8'); ?>"><?php echo \Joomla\CMS\Language\Text::_('JGLOBAL_READ_MORE'); ?></a></p><?php endif; ?></li><?php endforeach;?>
</ul>