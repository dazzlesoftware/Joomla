<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_codex
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

// View-specific override point; retain the shared layout as the default.
echo LayoutHelper::render('codex.content.blog_style_default_item_title', $this->item, JPATH_COMPONENT . '/layouts');
