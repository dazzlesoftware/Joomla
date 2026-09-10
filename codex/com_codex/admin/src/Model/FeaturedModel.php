<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_codex
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Codex\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of featured post records.
 *
 * @since  1.6
 *
 * @deprecated  6.0 will be removed in 8.0
 *              Use \Joomla\Component\Codex\Administrator\Model\PostsModel instead
 *              set the filter.featured state to 1 to get only featured posts
 */
class FeaturedModel extends PostsModel
{
}
