<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

// Compact list style: just hits and comment count, no rating stars and no share buttons.

$family = 'codex';
$item   = $displayData;
$postId = (int) $item->id;

$db = Factory::getContainer()->get(DatabaseInterface::class);
$commentCount = (int) $db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__' . $family . '_comments')->where('post_id=' . $postId)->where('state=1'))->loadResult();
?>
<div class="postmeta-row d-flex flex-wrap align-items-center gap-3 mb-2 text-muted small">
    <span class="postmeta-hits">
        <svg class="postmeta-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M12 5c-5 0-9.27 3.11-11 7 1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7Zm0 11.5A4.5 4.5 0 1 1 12 7.5a4.5 4.5 0 0 1 0 9Zm0-7A2.5 2.5 0 1 0 12 14a2.5 2.5 0 0 0 0-4.5Z"/></svg>
        <?php echo (int) $item->hits; ?> Hits
    </span>
    <span class="postmeta-comments">
        <svg class="postmeta-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M4 4h16a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H8l-4 4V6a1 1 0 0 1 1-1v-1Zm1 2v11.17L7.17 15H19V6H5Z"/></svg>
        <?php echo $commentCount; ?> Comment<?php echo $commentCount === 1 ? '' : 's'; ?>
    </span>
</div>
<style>
.postmeta-icon{display:inline-block;vertical-align:middle;color:#6c757d;margin-right:.15rem}
</style>
