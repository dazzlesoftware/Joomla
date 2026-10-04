<?php
defined('_JEXEC') or die;
echo \Joomla\CMS\Layout\LayoutHelper::render('compact-posts', ['items' => $this->compactItems, 'params' => $this->params], JPATH_COMPONENT . '/layouts');
