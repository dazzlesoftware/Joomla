<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_academy
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

// View-specific override point; retain the shared layout as the default.
echo LayoutHelper::render('comments', $this->item, JPATH_COMPONENT . '/layouts');
