<?php
/** Category actions shared by every category layout. */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$app = Factory::getApplication();
$document = $app->getDocument();
$document->getWebAssetManager()
    ->useStyle('fontawesome')
    ->registerAndUseStyle('com_academy.post-detail', 'com_academy/post-detail.css', ['version' => 'auto'])
    ->registerAndUseScript('com_academy.post-print', 'com_academy/post-print.js', ['version' => 'auto'], ['defer' => true]);
$showFeed = (bool) $displayData['params']->get('show_feed_link', 1);
$feedUri = clone Uri::getInstance();
$feedUri->setVar('format', 'feed');
$feedUri->setVar('type', 'rss');
$feedUri->delVar('limitstart');
$feedUri->delVar('start');
$feedUrl = $feedUri->toString();
if ($showFeed) {
    $document->addHeadLink($feedUrl, 'alternate', 'rel', ['type' => 'application/rss+xml', 'title' => Text::_('COM_ACADEMY_CATEGORY_RSS')]);
}
?>
<div class="post-print-toolbar d-flex flex-wrap align-items-center gap-3 mb-3">
    <button type="button" class="btn btn-link p-0" data-post-print><span class="fa-solid fa-print me-2" aria-hidden="true"></span><?php echo Text::_('JGLOBAL_PRINT'); ?></button>
    <?php if ($showFeed) : ?>
        <a href="<?php echo htmlspecialchars($feedUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-link p-0"><span class="fa-solid fa-rss me-2" aria-hidden="true"></span><?php echo Text::_('COM_ACADEMY_CATEGORY_RSS'); ?></a>
    <?php endif; ?>
</div>
