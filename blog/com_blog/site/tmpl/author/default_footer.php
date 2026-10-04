<?php
defined('_JEXEC') or die;
use Joomla\CMS\Layout\LayoutHelper;
$params = clone $this->item->params;
// Hits are already rendered by the engagement row.
$params->set('show_hits', 0);
echo LayoutHelper::render('blog.content.info_block', ['item' => $this->item, 'params' => $params], dirname(__DIR__, 2) . '/layouts');
