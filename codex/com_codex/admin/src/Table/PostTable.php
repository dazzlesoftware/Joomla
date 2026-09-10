<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_codex
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Codex\Administrator\Table;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Post table
 *
 * @since  1.5
 */
class PostTable extends NativePostTable
{
    public $excerpt = null;

    public function __construct(\Joomla\Database\DatabaseInterface $db, ?\Joomla\Event\DispatcherInterface $dispatcher = null)
    {
        parent::__construct($db, $dispatcher);
        $this->_tbl = '#__codex';
        $this->typeAlias = 'com_codex.post';
    }

    protected function _getAssetName()
    {
        return 'com_codex.post.' . (int) $this->{$this->_tbl_key};
    }
}
