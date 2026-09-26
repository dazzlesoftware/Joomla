<?php

namespace Joomla\Component\Academy\Site\Helper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\User\User;

\defined('_JEXEC') or die;

/** Frontend submission access, additional to Joomla create permissions. */
final class SubmissionHelper
{
    public static function hasAccess(User $user): bool
    {
        $level = (int) ComponentHelper::getParams('com_academy')->get('frontend_create_access', 1);

        return !$user->guest && in_array($level, $user->getAuthorisedViewLevels(), true);
    }
}
