<?php

/**
 * Featured image layout for the post-family components.
 *
 * Uses the family fork's native featured_image storage contract.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Codex\Site\Helper\RouteHelper;

$params         = $displayData->params;
$featuredImages = json_decode($displayData->media);

if (empty($featuredImages->featured_image)) {
    return;
}

$imageClass = empty($featuredImages->featured_image_class) ? $params->get('featured_image_class') : $featuredImages->featured_image_class;
$layoutAttr = [
    'src' => $featuredImages->featured_image,
    'alt' => empty($featuredImages->featured_image_alt) && empty($featuredImages->featured_image_alt_empty) ? false : $featuredImages->featured_image_alt,
];
?>
<figure class="<?php echo $this->escape($imageClass); ?> item-image featured-image">
    <?php if ($params->get('link_featured_image') && ($params->get('access-view') || $params->get('show_noauth', '0') == '1')) : ?>
        <a href="<?php echo Route::_(RouteHelper::getPostRoute($displayData->slug, $displayData->catid, $displayData->language)); ?>" title="<?php echo $this->escape($displayData->title); ?>">
            <?php echo LayoutHelper::render('joomla.html.image', $layoutAttr); ?>
        </a>
    <?php else : ?>
        <?php echo LayoutHelper::render('joomla.html.image', $layoutAttr); ?>
    <?php endif; ?>
    <?php if (isset($featuredImages->featured_image_caption) && $featuredImages->featured_image_caption !== '') : ?>
        <figcaption class="caption"><?php echo $this->escape($featuredImages->featured_image_caption); ?></figcaption>
    <?php endif; ?>
</figure>
