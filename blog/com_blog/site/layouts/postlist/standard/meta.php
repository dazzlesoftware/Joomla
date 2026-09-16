<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Blog\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;

Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('fontawesome');
Factory::getApplication()->getDocument()->getWebAssetManager()
    ->registerAndUseScript('com_blog.share-popup', 'com_blog/share-popup.js', ['version' => 'auto'], ['defer' => true]);

$family = 'blog';
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
            <svg class="postmeta-star" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="<?php echo $star <= $ratingAverage ? '#f0ad4e' : 'none'; ?>" stroke="#f0ad4e" stroke-width="1.5" d="m12 3.5 2.6 5.27 5.82.85-4.21 4.1.99 5.8L12 16.9l-5.2 2.62.99-5.8-4.21-4.1 5.82-.85L12 3.5Z"/></svg>
        <?php endfor; ?>
        <span class="badge bg-secondary ms-1"><?php echo $ratingCount; ?></span>
    </span>
    <span class="postmeta-hits">
        <svg class="postmeta-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M12 5c-5 0-9.27 3.11-11 7 1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7Zm0 11.5A4.5 4.5 0 1 1 12 7.5a4.5 4.5 0 0 1 0 9Zm0-7A2.5 2.5 0 1 0 12 14a2.5 2.5 0 0 0 0-4.5Z"/></svg>
        <?php echo (int) $item->hits; ?> Hits
    </span>
    <span class="postmeta-comments">
        <svg class="postmeta-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M4 4h16a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H8l-4 4V6a1 1 0 0 1 1-1v-1Zm1 2v11.17L7.17 15H19V6H5Z"/></svg>
        <?php echo $commentCount; ?> Comment<?php echo $commentCount === 1 ? '' : 's'; ?>
    </span>
</div>
<nav class="postmeta-share d-flex flex-wrap gap-2 mb-3" aria-label="Share this post">
    <?php if ($params->get('share_facebook', 1)) : ?>
        <a class="postmeta-share-btn postmeta-share-facebook" data-post-share-popup target="_blank" rel="noopener noreferrer" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode($postUrl); ?>" aria-label="Share on Facebook" title="Share on Facebook">
            <span class="fa-brands fa-facebook-f" aria-hidden="true"></span>
        </a>
    <?php endif; ?>
    <?php if ($params->get('share_x', 1)) : ?>
        <a class="postmeta-share-btn postmeta-share-twitter" data-post-share-popup target="_blank" rel="noopener noreferrer" href="https://twitter.com/intent/tweet?url=<?php echo rawurlencode($postUrl); ?>&text=<?php echo rawurlencode($title); ?>" aria-label="Share on X" title="Share on X">
            <span class="fa-brands fa-x-twitter" aria-hidden="true"></span>
        </a>
    <?php endif; ?>
    <?php if ($params->get('share_linkedin', 1)) : ?>
        <a class="postmeta-share-btn postmeta-share-linkedin" data-post-share-popup target="_blank" rel="noopener noreferrer" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo rawurlencode($postUrl); ?>" aria-label="Share on LinkedIn" title="Share on LinkedIn">
            <span class="fa-brands fa-linkedin-in" aria-hidden="true"></span>
        </a>
    <?php endif; ?>
    <?php if ($params->get('share_pinterest', 1)) : ?>
        <a class="postmeta-share-btn postmeta-share-pinterest" data-post-share-popup target="_blank" rel="noopener noreferrer" href="https://pinterest.com/pin/create/button/?url=<?php echo rawurlencode($postUrl); ?>&description=<?php echo rawurlencode($title); ?>" aria-label="Share on Pinterest" title="Share on Pinterest">
            <span class="fa-brands fa-pinterest-p" aria-hidden="true"></span>
        </a>
    <?php endif; ?>
</nav>
<style>
.postmeta-star{display:inline-block;vertical-align:middle}
.postmeta-icon{display:inline-block;vertical-align:middle;color:#6c757d;margin-right:.15rem}
.postmeta-share-btn{display:inline-flex;align-items:center;justify-content:center;width:2.1rem;height:2.1rem;border-radius:.375rem;color:#fff;font-size:1rem;text-decoration:none;transition:filter .15s ease}
.postmeta-share-facebook{background:#3b5998}
.postmeta-share-twitter{background:#212529}
.postmeta-share-linkedin{background:#0a66c2}
.postmeta-share-pinterest{background:#e60023}
.postmeta-share-btn:hover{color:#fff;filter:brightness(1.1)}
</style>
