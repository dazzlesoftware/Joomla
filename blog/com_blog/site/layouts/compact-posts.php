<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Component\Blog\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;
$items = $displayData['items'];
$params = $displayData['params'];
if (!$items || !\Joomla\Component\Blog\Site\Helper\CompactPostsHelper::visible($params)) { return; }
Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('fontawesome');
$columns = max(2, min(6, (int) $params->get('compact_columns', 2)));
$isColumns = $params->get('compact_layout', 'columns') === 'columns';
$listClass = 'row row-cols-1 g-3' . ($isColumns ? ' row-cols-md-' . $columns : '');
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$ratings = [];
if ($params->get('compact_show_rating', 1)) {
    $db = Factory::getContainer()->get(DatabaseInterface::class);
    $ratings = $db->setQuery($db->createQuery()->select('*')->from('#__blog_rating')
        ->whereIn('content_id', array_map(static fn($item) => (int) $item->id, $items)))->loadObjectList('content_id');
}
?>
<ul class="compact-posts list-unstyled my-4 <?php echo $listClass; ?>">
<?php foreach ($items as $item) :
    $url = Route::_(RouteHelper::getPostRoute($item->id . ':' . $item->alias, $item->catid, $item->language));
    $media = json_decode($item->media ?? '{}');
    $image = $media->featured_image ?? '';
    $rating = $ratings[$item->id] ?? null;
    $count = (int) ($rating->rating_count ?? 0);
    $average = $count ? round($rating->rating_sum / $count, 1) : 0;
?>
<li class="col">
    <div class="d-flex align-items-center gap-3 border rounded p-3 h-100">
    <?php if ($params->get('compact_show_image', 1)) : ?>
        <?php if ($params->get('link_featured_image', 0)) : ?>
        <a href="<?php echo $escape($url); ?>" class="flex-shrink-0" aria-label="<?php echo $escape($item->title); ?>">
        <?php else : ?><span class="flex-shrink-0"><?php endif; ?>
        <?php if ($image) : ?>
            <img src="<?php echo $escape($image); ?>" alt="" width="72" height="72" class="rounded object-fit-cover" loading="lazy">
        <?php else : ?>
            <span class="fa-regular fa-image fa-3x text-muted" aria-hidden="true"></span>
        <?php endif; ?>
        <?php if ($params->get('link_featured_image', 0)) : ?></a><?php else : ?></span><?php endif; ?>
    <?php endif; ?>
    <div class="text-break">
        <?php if ($params->get('compact_show_title', 1)) : ?>
        <a href="<?php echo $escape($url); ?>" class="fw-semibold"><?php echo $escape($item->title); ?></a>
        <?php endif; ?>
        <?php if ($params->get('compact_show_rating', 1)) : ?>
        <div class="small mt-1">
            <span class="text-warning" aria-hidden="true"><?php for ($star=1; $star<=5; $star++) : ?><span class="<?php echo $star <= round($average) ? 'fa-solid' : 'fa-regular'; ?> fa-star"></span><?php endfor; ?></span>
            <span class="text-muted"><?php echo $average; ?> / 5 (<?php echo $count; ?>)</span>
        </div>
        <?php endif; ?>
    </div>
    </div>
</li>
<?php endforeach; ?>
</ul>
