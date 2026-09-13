<?php

namespace Joomla\Component\Blog\Administrator\Field;

defined('_JEXEC') or die;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Factory;
use Joomla\Component\Blog\Administrator\Helper\CategoriesHelper;

class PostcategoryField extends ListField
{
    protected $type = 'Postcategory';
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), CategoriesHelper::options(!Factory::getApplication()->isClient('administrator')));
    }
}
