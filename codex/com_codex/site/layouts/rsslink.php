<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$params = $displayData;
if ($params->get('show_feed_link', 1)) :
    $feedUri = clone Uri::getInstance();
    $feedUri->setVar('format', 'feed');
    $feedUri->setVar('type', 'rss');
    $feedUri->delVar('limitstart');
    ?>
<div class="post-rss-link mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo Route::_($feedUri->toString()); ?>" type="application/rss+xml">
        <span class="icon-feed" aria-hidden="true"></span> RSS Feed
    </a>
</div>
<?php endif; ?>