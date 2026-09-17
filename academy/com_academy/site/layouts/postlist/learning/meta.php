<?php
defined('_JEXEC') or die;
\Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('fontawesome');

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Academy\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;

Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('fontawesome');
Factory::getApplication()->getDocument()->getWebAssetManager()
    ->registerAndUseScript('com_academy.share-popup', 'com_academy/share-popup.js', ['version' => 'auto'], ['defer' => true]);

$family = 'academy';
$item   = $displayData;
$params = $item->params;
$postId = (int) $item->id;

$db = Factory::getContainer()->get(DatabaseInterface::class);

$rating        = $db->setQuery($db->createQuery()->select(['rating_sum', 'rating_count'])->from('#__' . $family . '_rating')->where('content_id=' . $postId))->loadObject();
$ratingCount   = (int) ($rating->rating_count ?? 0);
$ratingAverage = $ratingCount ? (int) round(((int) $rating->rating_sum) / $ratingCount) : 0;

$commentCount = (int) $db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__' . $family . '_comments')->where('post_id=' . $postId)->where('state=1'))->loadResult();

$postUrl = Uri::root() . Route::_(RouteHelper::getPostRoute($item->slug, $item->catid, $item->language), false);
$title   = (string) $item->title;
?>
<div class="postmeta-row d-flex flex-wrap align-items-center gap-3 mb-2 text-muted small">
    <span class="postmeta-rating" aria-label="Rating: <?php echo $ratingAverage; ?> out of 5, <?php echo $ratingCount; ?> vote<?php echo $ratingCount === 1 ? '' : 's'; ?>">
        <?php for ($star = 1; $star <= 5; $star++) : ?>
            <span class="<?php echo $star <= $ratingAverage ? 'fa-solid' : 'fa-regular'; ?> fa-star text-warning" aria-hidden="true"></span>
        <?php endfor; ?>
        <span class="badge bg-secondary ms-1"><?php echo $ratingCount; ?></span>
    </span>
    <span class="postmeta-hits">
        <span class="fa-solid fa-eye align-middle me-1" aria-hidden="true"></span>
        <?php echo (int) $item->hits; ?> Hits
    </span>
    <span class="postmeta-comments">
        <span class="fa-solid fa-comment align-middle me-1" aria-hidden="true"></span>
        <?php echo $commentCount; ?> Comment<?php echo $commentCount === 1 ? '' : 's'; ?>
    </span>
</div>
<nav class="postmeta-share d-flex flex-wrap gap-2 mb-3" aria-label="Share this post">
    <?php if ($params->get('share_facebook', 1)) : ?>
        <a class="postmeta-share-btn postmeta-share-facebook btn btn-primary btn-sm" data-post-share-popup target="_blank" rel="noopener noreferrer" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode($postUrl); ?>" aria-label="Share on Facebook" title="Share on Facebook">
            <span class="fa-brands fa-facebook-f" aria-hidden="true"></span>
        </a>
    <?php endif; ?>
    <?php if ($params->get('share_x', 1)) : ?>
        <a class="postmeta-share-btn postmeta-share-twitter btn btn-dark btn-sm" data-post-share-popup target="_blank" rel="noopener noreferrer" href="https://twitter.com/intent/tweet?url=<?php echo rawurlencode($postUrl); ?>&text=<?php echo rawurlencode($title); ?>" aria-label="Share on X" title="Share on X">
            <span class="fa-brands fa-x-twitter" aria-hidden="true"></span>
        </a>
    <?php endif; ?>
    <?php if ($params->get('share_linkedin', 1)) : ?>
        <a class="postmeta-share-btn postmeta-share-linkedin btn btn-primary btn-sm" data-post-share-popup target="_blank" rel="noopener noreferrer" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo rawurlencode($postUrl); ?>" aria-label="Share on LinkedIn" title="Share on LinkedIn">
            <span class="fa-brands fa-linkedin-in" aria-hidden="true"></span>
        </a>
    <?php endif; ?>
    <?php if ($params->get('share_pinterest', 1)) : ?>
        <a class="postmeta-share-btn postmeta-share-pinterest btn btn-danger btn-sm" data-post-share-popup target="_blank" rel="noopener noreferrer" href="https://pinterest.com/pin/create/button/?url=<?php echo rawurlencode($postUrl); ?>&description=<?php echo rawurlencode($title); ?>" aria-label="Share on Pinterest" title="Share on Pinterest">
            <span class="fa-brands fa-pinterest-p" aria-hidden="true"></span>
        </a>
    <?php endif; ?>
</nav>
