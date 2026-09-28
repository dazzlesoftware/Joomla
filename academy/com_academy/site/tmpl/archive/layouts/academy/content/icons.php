<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_academy
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

// Override of the core joomla.content.icons layout: calls our own
// 'academyicon' HTML service directly instead of the shared, unnamespaced
// 'icon' key. academy/blog/codex/content are siblings that can all be booted
// in the same request (e.g. the post edit screen's Associations tab boots
// every association-supporting extension at once), and each one registering
// itself under that same generic key crashes whichever one boots second. Our
// own templates render this layout with a $basePath of JPATH_COMPONENT, so
// this override is picked up instead of the core one, and never needs the
// shared key at all.

$canEdit   = $displayData['params']->get('access-edit');
$articleId = $displayData['item']->id;
?>

<?php if ($canEdit) : ?>
    <div class="icons">
        <div class="float-end">
            <div>
                <?php echo HTMLHelper::_('academyicon.edit', $displayData['item'], $displayData['params']); ?>
            </div>
        </div>
    </div>
<?php endif; ?>
