<?php
defined('_JEXEC') or die;
\Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('fontawesome');

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

// Compact list style: just hits and comment count, no rating stars and no share buttons.

$family = 'academy';
$item   = $displayData;
$postId = (int) $item->id;

$db = Factory::getContainer()->get(DatabaseInterface::class);
$commentCount = (int) $db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__' . $family . '_comments')->where('post_id=' . $postId)->where('state=1'))->loadResult();
?>
<div class="postmeta-row d-flex flex-wrap align-items-center gap-3 mb-2 text-muted small">
    <span class="postmeta-hits">
        <span class="fa-solid fa-eye align-middle me-1" aria-hidden="true"></span>
        <?php echo (int) $item->hits; ?> Hits
    </span>
    <span class="postmeta-comments">
        <span class="fa-solid fa-comment align-middle me-1" aria-hidden="true"></span>
        <?php echo $commentCount; ?> Comment<?php echo $commentCount === 1 ? '' : 's'; ?>
    </span>
</div>
