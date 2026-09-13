<?php
namespace Joomla\Component\Codex\Administrator\Button;

defined('_JEXEC') or die;

use Joomla\CMS\Button\ActionButton;
use Joomla\CMS\Language\Text;

/**
 * A clickable star toggle for the Tags list, reusing core's own
 * ActionButton/list-view JS mechanism (the same one PublishedButton uses) so
 * clicking the star submits the row's own task directly - no checkbox
 * selection or toolbar button needed first.
 */
final class DefaultButton extends ActionButton
{
    protected function preprocess()
    {
        // The extra "text-warning" class rides along as part of the icon
        // identifier itself: ActionButton's iconclass layout treats any
        // identifier already containing "icon-" as a literal class string
        // rather than prefixing it again, so this renders as
        // class="icon-star text-warning" - the filled-in yellow star that
        // shows a tag is the active default.
        $this->addState(1, 'removedefault', 'icon-star text-warning', Text::_('COM_CODEX_REMOVE_DEFAULT'), ['tip_title' => Text::_('COM_CODEX_DEFAULT_TAG')]);
        $this->addState(0, 'makedefault', 'icon-star-empty', Text::_('COM_CODEX_MAKE_DEFAULT'), ['tip_title' => Text::_('JNO')]);
    }
}
