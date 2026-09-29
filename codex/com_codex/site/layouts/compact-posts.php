<?php
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Layout\LayoutHelper;

// Compact styles are configured by the component, not by individual menu items.
$style = (string) ComponentHelper::getParams('com_codex')->get('compact_style', 'default');
// Explicitly allow supported styles; unknown or obsolete values use Default.
if (!in_array($style, ['default'], true)) {
    $style = 'default';
}
echo LayoutHelper::render('compact.' . $style, $displayData, __DIR__);
