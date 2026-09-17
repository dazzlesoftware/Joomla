<?php
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

$family = 'codex';
$postId = (int) ($displayData->id ?? 0);
if (!$postId) {
    return;
}
Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('fontawesome');
Factory::getApplication()->getDocument()->getWebAssetManager()
    ->registerAndUseScript('com_codex.share-popup', 'com_codex/share-popup.js', ['version' => 'auto'], ['defer' => true]);

$params = ComponentHelper::getParams('com_' . $family);
$url = Uri::getInstance()->toString();
$title = (string) ($displayData->title ?? '');
$db = Factory::getContainer()->get(DatabaseInterface::class);
$rating = $db->setQuery($db->createQuery()->select(['rating_sum', 'rating_count'])->from('#__' . $family . '_rating')->where('content_id=' . $postId))->loadObject();
$count = (int) ($rating->rating_count ?? 0);
$average = $count ? round((int) $rating->rating_sum / $count, 1) : 0;
$filledStars = $count ? (int) round((int) $rating->rating_sum / $count) : 0;
$ratingLabel = (string) $params->get('rating_label', 'Rate this post:');
$subscribeHeading = (string) $params->get('subscribe_heading', 'Stay Informed');
$subscribeText = (string) $params->get('subscribe_text', 'Subscribe for updates and new posts.');
$subscribeButton = (string) $params->get('subscribe_button', 'Subscribe');
$consentText = (string) $params->get('subscribe_consent', 'I agree to receive email updates and can unsubscribe at any time.');
?>
<section class="post-engagement my-4" aria-label="Post engagement">
<?php if ($params->get('engagement_ratings', 1)): ?>
<form method="post" action="<?php echo htmlspecialchars(Uri::base().'index.php?option=com_'.$family.'&task=engagement.rate', ENT_QUOTES, 'UTF-8'); ?>" class="post-rating d-flex flex-wrap align-items-center gap-1 mb-3">
    <span class="me-2"><?php echo htmlspecialchars($ratingLabel, ENT_QUOTES, 'UTF-8'); ?></span>
    <?php for ($star = 1; $star <= 5; $star++): ?><button class="btn btn-link text-warning text-decoration-none fs-4 lh-1 p-1" type="submit" name="rating" value="<?php echo $star; ?>" aria-label="Rate <?php echo $star; ?> out of 5 stars"><span class="<?php echo $star <= $filledStars ? 'fa-solid' : 'fa-regular'; ?> fa-star" aria-hidden="true"></span></button><?php endfor; ?>
    <span class="badge bg-secondary ms-2" aria-live="polite"><?php echo $average; ?> / 5 &middot; <?php echo $count; ?> vote<?php echo $count === 1 ? '' : 's'; ?></span>
    <input type="hidden" name="post_id" value="<?php echo $postId; ?>"><input type="hidden" name="return" value="<?php echo htmlspecialchars(base64_encode($url), ENT_QUOTES, 'UTF-8'); ?>"><?php echo HTMLHelper::_('form.token'); ?>
</form>
<?php endif; ?>
<?php if ($params->get('engagement_sharing', 1)): ?>
<nav class="post-sharing d-flex flex-wrap gap-2 mb-4" aria-label="Share this post">
    <?php if ($params->get('share_facebook', 1)):?><a class="btn btn-primary" data-post-share-popup target="_blank" rel="noopener noreferrer" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode($url); ?>" aria-label="Share on Facebook" title="Share on Facebook"><span class="fa-brands fa-facebook-f fa-fw" aria-hidden="true"></span></a><?php endif;?>
    <?php if ($params->get('share_x', 1)):?><a class="btn btn-dark" data-post-share-popup target="_blank" rel="noopener noreferrer" href="https://twitter.com/intent/tweet?url=<?php echo rawurlencode($url); ?>&text=<?php echo rawurlencode($title); ?>" aria-label="Share on X" title="Share on X"><span class="fa-brands fa-x-twitter fa-fw" aria-hidden="true"></span></a><?php endif;?>
    <?php if ($params->get('share_linkedin', 1)):?><a class="btn btn-primary" data-post-share-popup target="_blank" rel="noopener noreferrer" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo rawurlencode($url); ?>" aria-label="Share on LinkedIn" title="Share on LinkedIn"><span class="fa-brands fa-linkedin-in fa-fw" aria-hidden="true"></span></a><?php endif;?>
    <?php if ($params->get('share_pinterest', 1)):?><a class="btn btn-danger" data-post-share-popup target="_blank" rel="noopener noreferrer" href="https://pinterest.com/pin/create/button/?url=<?php echo rawurlencode($url); ?>&description=<?php echo rawurlencode($title); ?>" aria-label="Share on Pinterest" title="Share on Pinterest"><span class="fa-brands fa-pinterest-p fa-fw" aria-hidden="true"></span></a><?php endif;?>
    <?php if ($params->get('share_email', 1)):?><a class="btn btn-success" href="mailto:?subject=<?php echo rawurlencode($title); ?>&body=<?php echo rawurlencode($url); ?>" aria-label="Share by email" title="Share by email"><span class="fa-solid fa-envelope fa-fw" aria-hidden="true"></span></a><?php endif;?>
</nav>
<?php endif; ?>
<?php if ($params->get('engagement_subscribe', 1)): ?>
<div class="post-subscribe card card-body p-3 p-md-5"><div class="mx-auto col-12 col-lg-8"><h2 class="text-center"><?php echo htmlspecialchars($subscribeHeading, ENT_QUOTES, 'UTF-8'); ?></h2><p class="text-center"><?php echo nl2br(htmlspecialchars($subscribeText, ENT_QUOTES, 'UTF-8')); ?></p>
<form method="post" action="<?php echo htmlspecialchars(Uri::base().'index.php?option=com_'.$family.'&task=engagement.subscribe', ENT_QUOTES, 'UTF-8'); ?>">
    <label class="form-label" for="subscribe-name-<?php echo $postId; ?>">Name</label><input id="subscribe-name-<?php echo $postId; ?>" class="form-control mb-3" name="name" autocomplete="name" required>
    <label class="form-label" for="subscribe-email-<?php echo $postId; ?>">Email address</label><input id="subscribe-email-<?php echo $postId; ?>" class="form-control mb-3" name="email" type="email" autocomplete="email" inputmode="email" required>
    <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="consent" value="1" required><span class="form-check-label"><?php echo htmlspecialchars($consentText, ENT_QUOTES, 'UTF-8'); ?></span></label>
    <input type="hidden" name="return" value="<?php echo htmlspecialchars(base64_encode($url), ENT_QUOTES, 'UTF-8'); ?>"><?php echo HTMLHelper::_('form.token'); ?><button class="btn btn-primary w-100" type="submit"><?php echo htmlspecialchars($subscribeButton, ENT_QUOTES, 'UTF-8'); ?></button>
</form></div></div>
<?php endif; ?>
</section>
