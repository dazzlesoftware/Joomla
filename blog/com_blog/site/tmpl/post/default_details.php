<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_blog
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

// View-specific override point; retain the shared layout as the default.
$params = $this->item->params;
echo LayoutHelper::render('post-details', ['item' => $this->item, 'params' => $params, 'position' => 'above'], JPATH_COMPONENT . '/layouts');
