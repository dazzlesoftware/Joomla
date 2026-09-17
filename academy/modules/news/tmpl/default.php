<?php defined('_JEXEC') or die;
if (!$list) {
    return;
} ?>
<ul class="mod-academy-news list-unstyled">
<?php foreach ($list as $item):?><li class="mb-2"><a href="<?php echo $item->link;?>"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8');?></a><?php if (isset($item->count)):?> <span class="badge bg-secondary"><?php echo(int)$item->count;?></span><?php endif;?><?php if ($dateHtml = \Joomla\Component\Academy\Site\Helper\DateHelper::render($item, $dateParams, false)): ?><div class="small text-muted"><?php echo $dateHtml; ?></div><?php endif; ?><?php if ($mode === 'news' && !empty($item->summary)):?><div class="small mt-1"><?php echo strip_tags($item->summary, '<p><br><strong><em>');?></div><?php endif;?></li><?php endforeach;?>
</ul>