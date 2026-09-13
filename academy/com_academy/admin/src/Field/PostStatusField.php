<?php

namespace Joomla\Component\Academy\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\StatusField;

/**
 * Posts support a "Pending Review" state (-3) that core's own StatusField
 * doesn't know about, so the Posts list filter had no way to select it - the
 * list defaults to Published+Unpublished only, silently hiding pending posts
 * until this option existed.
 */
class PostStatusField extends StatusField
{
    public $type = 'Poststatus';

    protected $predefinedOptions = [
        -2  => 'JTRASHED',
        -3  => 'COM_ACADEMY_POST_PENDING',
        0   => 'JUNPUBLISHED',
        1   => 'JPUBLISHED',
        2   => 'JARCHIVED',
        '*' => 'JALL',
    ];
}
