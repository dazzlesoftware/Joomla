<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_blog
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

// View-specific override point; retain the shared layout as the default.
$listStyle = $this->item->params->get('list_item_style', 'standard');
echo LayoutHelper::render('post.' . $listStyle . '-header', $this->item, JPATH_COMPONENT . '/layouts');
