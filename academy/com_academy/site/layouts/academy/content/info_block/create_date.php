<?php
defined('_JEXEC') or die;
// Compatibility entry point for template overrides: only the selected date renders.
$params = $displayData['params'];
[$showDate, $dateType] = \Joomla\Component\Academy\Site\Helper\DateHelper::options($params);
if ($showDate && $dateType === 'created') : ?>
<dd class="date"><?php echo \Joomla\Component\Academy\Site\Helper\DateHelper::render($displayData['item'], $params); ?></dd>
<?php endif; ?>
