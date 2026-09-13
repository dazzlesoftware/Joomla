<?php
namespace Joomla\Component\Academy\Administrator\Button;

defined('_JEXEC') or die;

use Joomla\CMS\Button\PublishedButton;
use Joomla\CMS\Language\Text;

/**
 * Posts support a "Pending Review" state (-3) in addition to the four core
 * Joomla knows about (published/unpublished/archived/trashed). Core's own
 * PublishedButton has no icon registered for it, so a pending post rendered
 * "Unknown state" in the Posts list. This adds that missing state - clicking
 * it publishes the post, the same way clicking a trashed item's icon
 * restores it.
 */
final class PostStateButton extends PublishedButton
{
    protected function preprocess()
    {
        parent::preprocess();
        $this->addState(-3, 'publish', 'pending', Text::_('JLIB_HTML_PUBLISH_ITEM'), ['tip_title' => Text::_('COM_ACADEMY_POST_PENDING')]);
    }
}
