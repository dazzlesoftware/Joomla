<?php
defined('_JEXEC') or die;
\Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('fontawesome');

$item  = $displayData;
$title = htmlspecialchars((string) ($item->title ?? 'Post image'), ENT_QUOTES, 'UTF-8');
$linked = isset($item->params) && $item->params->get('link_featured_image')
    && ($item->params->get('access-view') || $item->params->get('show_noauth', 0));
?>
<?php if ($linked) : ?>
<a href="<?php echo htmlspecialchars(\Joomla\CMS\Router\Route::_(\Joomla\Component\Codex\Site\Helper\RouteHelper::getPostRoute($item->slug, $item->catid, $item->language)), ENT_QUOTES, 'UTF-8'); ?>" class="d-block">
<?php endif; ?>
<figure class="item-image post-card-placeholder m-0 text-body-secondary" role="img" aria-label="<?php echo $title; ?>">
    <div class="ratio ratio-16x9 bg-body-tertiary"><div class="d-flex align-items-center justify-content-center"><span class="fa-regular fa-image fa-5x opacity-50" aria-hidden="true"></span></div></div>
</figure>

<?php if ($linked) : ?></a><?php endif; ?>
