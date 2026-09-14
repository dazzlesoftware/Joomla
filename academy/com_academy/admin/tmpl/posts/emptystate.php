<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_academy
 *
 * @copyright   (C) 2021 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

/** @var \Joomla\Component\Academy\Administrator\View\Posts\HtmlView $this */

$displayData = [
    'textPrefix' => 'COM_ACADEMY',
    'formURL'    => 'index.php?option=com_academy&view=posts',
    'helpURL'    => 'https://guide.joomla.org/user-manual/posts',
    'icon'       => 'icon-copy post',

    'controlFields' => $this->filterForm->renderControlFields(),
];

$user = $this->getCurrentUser();

if ($user->authorise('core.create', 'com_academy') || count($user->getAuthorisedCategories('com_academy', 'core.create')) > 0) {
    $displayData['createURL'] = 'index.php?option=com_academy&task=post.add';
}

echo LayoutHelper::render('academy.content.emptystate', $displayData);
