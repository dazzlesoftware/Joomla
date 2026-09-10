<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_blog
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Blog\Site\Dispatcher;

use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * ComponentDispatcher class for com_blog
 *
 * @since  4.0.0
 */
class Dispatcher extends ComponentDispatcher
{
    /**
     * Dispatch a controller task. Redirecting the user if appropriate.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function dispatch()
    {
        $checkCreateEdit = ($this->input->get('view') === 'posts' && $this->input->get('layout') === 'modal')
            || ($this->input->get('view') === 'post' && $this->input->get('layout') === 'pagebreak');

        if ($checkCreateEdit) {
            // Can create in any category (component permission) or at least in one category
            $canCreateRecords = $this->app->getIdentity()->authorise('core.create', 'com_blog')
                || \count($this->app->getIdentity()->getAuthorisedCategories('com_blog', 'core.create')) > 0;

            // Instead of checking edit on all records, we can use **same** check as the form editing view
            $values           = (array) $this->app->getUserState('com_blog.edit.post.id');
            $isEditingRecords = \count($values);
            $hasAccess        = $canCreateRecords || $isEditingRecords;

            if (!$hasAccess) {
                $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

                return;
            }
        }

        parent::dispatch();
    }
}
