<?php
defined('_JEXEC') or die;
$items = \Joomla\Component\Codex\Site\Helper\CompactPostsHelper::select($this->link_items, array_merge($this->lead_items, $this->intro_items), $this->params);
echo \Joomla\CMS\Layout\LayoutHelper::render('compact-posts', ['items' => $items, 'params' => $this->params], JPATH_COMPONENT . '/layouts');
