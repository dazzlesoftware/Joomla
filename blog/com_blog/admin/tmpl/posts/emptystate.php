<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_blog
 *
 * @copyright   (C) 2021 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

/** @var \Joomla\Component\Blog\Administrator\View\Posts\HtmlView $this */

$displayData = [
    'textPrefix' => 'COM_BLOG',
    'formURL'    => 'index.php?option=com_blog&view=posts',
    'helpURL'    => 'https://guide.joomla.org/user-manual/posts',
    'icon'       => 'icon-copy post',

    'controlFields' => $this->filterForm->renderControlFields(),
];

$user = $this->getCurrentUser();

if ($user->authorise('core.create', 'com_blog') || count($user->getAuthorisedCategories('com_blog', 'core.create')) > 0) {
    $displayData['createURL'] = 'index.php?option=com_blog&task=post.add';
}

echo LayoutHelper::render('joomla.content.emptystate', $displayData);
