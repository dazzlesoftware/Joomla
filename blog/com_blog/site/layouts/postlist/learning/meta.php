<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Blog\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;

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
        <a class="postmeta-share-btn postmeta-share-facebook" target="_blank" rel="noopener noreferrer" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode($postUrl); ?>" aria-label="Share on Facebook">
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="#fff" d="M13.5 21v-7.2h2.4l.36-2.8h-2.76V9.1c0-.81.22-1.36 1.39-1.36h1.48V5.22C15.9 5.15 15 5.07 13.95 5.07c-2.19 0-3.69 1.34-3.69 3.79v2.14H7.86v2.8h2.4V21h3.24Z"/></svg>
        </a>
    <?php endif; ?>
    <?php if ($params->get('share_x', 1)) : ?>
        <a class="postmeta-share-btn postmeta-share-twitter" target="_blank" rel="noopener noreferrer" href="https://twitter.com/intent/tweet?url=<?php echo rawurlencode($postUrl); ?>&text=<?php echo rawurlencode($title); ?>" aria-label="Share on Twitter">
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="#fff" d="M22 5.9c-.68.3-1.4.5-2.16.6a3.76 3.76 0 0 0 1.66-2.08 7.53 7.53 0 0 1-2.39.91 3.75 3.75 0 0 0-6.39 3.42A10.66 10.66 0 0 1 5.14 4.9a3.75 3.75 0 0 0 1.16 5 3.7 3.7 0 0 1-1.7-.47v.05a3.75 3.75 0 0 0 3.01 3.68 3.77 3.77 0 0 1-1.69.06 3.75 3.75 0 0 0 3.5 2.6A7.53 7.53 0 0 1 3 17.4a10.62 10.62 0 0 0 5.76 1.69c6.92 0 10.7-5.73 10.7-10.7l-.01-.49A7.65 7.65 0 0 0 22 5.9Z"/></svg>
        </a>
    <?php endif; ?>
    <?php if ($params->get('share_linkedin', 1)) : ?>
        <a class="postmeta-share-btn postmeta-share-linkedin" target="_blank" rel="noopener noreferrer" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo rawurlencode($postUrl); ?>" aria-label="Share on LinkedIn">
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="#fff" d="M6.94 8.5H4.1V19h2.84V8.5ZM5.52 4a1.65 1.65 0 1 0 0 3.3 1.65 1.65 0 0 0 0-3.3ZM19.9 19h-2.83v-5.6c0-1.34-.03-3.06-1.86-3.06-1.87 0-2.16 1.46-2.16 2.96V19H10.2V8.5h2.72v1.44h.04c.38-.71 1.3-1.46 2.68-1.46 2.86 0 3.39 1.88 3.39 4.33V19Z"/></svg>
        </a>
    <?php endif; ?>
    <?php if ($params->get('share_pinterest', 1)) : ?>
        <a class="postmeta-share-btn postmeta-share-pinterest" target="_blank" rel="noopener noreferrer" href="https://pinterest.com/pin/create/button/?url=<?php echo rawurlencode($postUrl); ?>&description=<?php echo rawurlencode($title); ?>" aria-label="Share on Pinterest">
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="#fff" d="M12 2a10 10 0 0 0-3.65 19.32c-.05-.82-.1-2.08.02-2.98.11-.8.7-3.16.7-3.16s-.18-.37-.18-.9c0-.85.49-1.48 1.1-1.48.52 0 .77.39.77.86 0 .53-.34 1.31-.51 2.04-.15.61.31 1.11.91 1.11 1.1 0 1.94-1.16 1.94-2.83 0-1.48-1.06-2.51-2.58-2.51-1.76 0-2.79 1.32-2.79 2.68 0 .53.2 1.1.46 1.41a.19.19 0 0 1 .04.18c-.05.19-.15.61-.17.7-.03.11-.09.14-.21.08-.78-.36-1.27-1.5-1.27-2.41 0-1.96 1.42-3.76 4.1-3.76 2.15 0 3.83 1.53 3.83 3.58 0 2.14-1.35 3.86-3.22 3.86-.63 0-1.22-.33-1.42-.72l-.39 1.47c-.14.54-.52 1.22-.77 1.63A10 10 0 1 0 12 2Z"/></svg>
        </a>
    <?php endif; ?>
</nav>
<style>
.postmeta-star{display:inline-block;vertical-align:middle}
.postmeta-icon{display:inline-block;vertical-align:middle;color:#6c757d;margin-right:.15rem}
.postmeta-share-btn{display:inline-flex;align-items:center;justify-content:center;width:2.1rem;height:2.1rem;border-radius:.375rem;text-decoration:none;transition:filter .15s ease}
.postmeta-share-facebook{background:#3b5998}
.postmeta-share-twitter{background:#1da1f2}
.postmeta-share-linkedin{background:#0a66c2}
.postmeta-share-pinterest{background:#e60023}
.postmeta-share-btn:hover{filter:brightness(1.1)}
</style>
