<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Codex\Site\Helper\RouteHelper;
use Joomla\Component\Codex\Site\Helper\DateHelper;
$items = $displayData['items'];
$params = $displayData['params'];
$id = 'codex-featured-' . bin2hex(random_bytes(6));
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$show = static fn($key, $default = 1) => (bool) $params->get('featured_slider_' . $key, $default);
$auto = $show('auto', 0) && count($items) > 1;
HTMLHelper::_('bootstrap.carousel', '#' . $id, ['interval' => $auto ? max(1, (int) $params->get('featured_slider_interval', 8)) * 1000 : false, 'ride' => false, 'pause' => false]);
\Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('fontawesome');
?>
<section id="<?php echo $id; ?>" class="featured-showcase carousel slide mb-4" aria-label="Featured posts" aria-roledescription="carousel">
<div class="carousel-inner">
<?php foreach ($items as $index => $item) :
    $link = Route::_(RouteHelper::getPostRoute($item->id . ':' . $item->alias, $item->catid, $item->language));
    $media = json_decode((string) ($item->media ?? '{}'));
    $image = \Joomla\Component\Codex\Site\Helper\FeaturedSliderHelper::imageUrl((string) ($media->featured_image ?? ''));
?>
<div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>" role="group" aria-label="<?php echo ($index + 1) . ' of ' . count($items); ?>">
<article class="card overflow-hidden"><div class="row g-0">
<?php if ($show('image')) : ?><div class="col-md-5 bg-body-tertiary">
<?php if ($image !== '') : ?>
<img class="img-fluid w-100 h-100 object-fit-cover" src="<?php echo $escape($image); ?>" alt="<?php echo $escape($media->featured_image_alt ?? ''); ?>">
<?php else : ?><div class="ratio ratio-4x3 h-100"><div class="d-flex align-items-center justify-content-center"><span class="fa-regular fa-image fa-5x text-body-secondary" aria-hidden="true"></span><span class="visually-hidden">No post image</span></div></div><?php endif; ?>
</div><?php endif; ?>
<div class="<?php echo $show('image') ? 'col-md-7' : 'col-12'; ?>"><div class="card-body d-flex flex-column" style="min-height: 20rem">
<?php if ($show('title')) : ?><h2 class="h4"><a href="<?php echo $escape($link); ?>"><?php echo $escape($item->title); ?></a></h2><?php endif; ?>
<?php if ($show('content')) : ?><p><?php echo $escape($item->sliderText); ?></p><?php endif; ?>
<div class="mt-auto">
<?php if ($show('readmore')) : ?><a class="btn btn-outline-secondary btn-sm mb-3" href="<?php echo $escape($link); ?>">Continue reading<span class="visually-hidden">: <?php echo $escape($item->title); ?></span></a><?php endif; ?>
<?php if ($show('ratings', 0)) : $rating = $item->rating_count ? round($item->rating_sum / $item->rating_count) : 0; ?>
<div class="text-warning mb-2" aria-label="<?php echo $rating; ?> out of 5">
<?php for ($star=1; $star<=5; $star++) : ?><span class="<?php echo $star <= $rating ? 'fa-solid' : 'fa-regular'; ?> fa-star" aria-hidden="true"></span><?php endfor; ?> <span class="badge bg-secondary"><?php echo (int) $item->rating_count; ?></span></div><?php endif; ?>
<?php if ($show('author') || $show('category') || $show('date') || $show('avatar')) : ?>
<footer class="border-top pt-2 d-flex align-items-end gap-3 small text-muted"><div>
<?php if ($show('author')) : ?><div><span class="fa-solid fa-user me-1" aria-hidden="true"></span><a href="<?php echo $escape(Route::_('index.php?option=com_codex&view=author&id=' . (int) $item->created_by)); ?>"><?php echo $escape($item->created_by_alias ?: $item->author); ?></a></div><?php endif; ?>
<?php if ($show('category')) : ?><div><span class="fa-solid fa-folder-open me-1" aria-hidden="true"></span><a href="<?php echo $escape(Route::_(RouteHelper::getCategoryRoute($item->catid, $item->language))); ?>"><?php echo $escape($item->category_title); ?></a></div><?php endif; ?>
<?php if ($show('date')) : ?><div><span class="fa-solid fa-calendar me-1" aria-hidden="true"></span><?php echo DateHelper::render($item, $item->params); ?></div><?php endif; ?>
</div><?php if ($show('avatar')) : ?><div class="ms-auto"><?php echo LayoutHelper::render('post.avatar', $item, JPATH_ROOT . '/components/com_codex/layouts'); ?></div><?php endif; ?></footer><?php endif; ?>
</div></div></div></div></article></div>
<?php endforeach; ?>
</div>
<?php if (count($items) > 1) : ?>
<div class="d-flex justify-content-center gap-2 mt-2">
<?php if ($show('navigation')) : ?>
<button class="btn btn-outline-secondary btn-sm" type="button" data-bs-target="#<?php echo $id; ?>" data-bs-slide="prev" aria-label="Previous featured post"><span class="fa-solid fa-chevron-left" aria-hidden="true"></span></button>
<?php foreach ($items as $index => $item) : ?><button class="btn btn-outline-secondary btn-sm" type="button" data-bs-target="#<?php echo $id; ?>" data-bs-slide-to="<?php echo $index; ?>" aria-label="Featured post <?php echo $index + 1; ?>"><?php echo $index + 1; ?></button><?php endforeach; ?>
<button class="btn btn-outline-secondary btn-sm" type="button" data-bs-target="#<?php echo $id; ?>" data-bs-slide="next" aria-label="Next featured post"><span class="fa-solid fa-chevron-right" aria-hidden="true"></span></button>
<?php endif; ?>
<?php if ($auto) : ?><button class="btn btn-outline-secondary btn-sm" type="button" data-featured-pause aria-pressed="false">Pause slideshow</button><?php endif; ?>
</div><?php endif; ?>
</section>
<?php
\Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()->registerAndUseScript('com_codex.featured-slider', 'com_codex/featured-slider.js', ['version' => 'auto'], ['defer' => true], ['bootstrap.carousel']);
