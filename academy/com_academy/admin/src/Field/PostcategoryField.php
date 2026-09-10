<?php
namespace Joomla\Component\Academy\Administrator\Field;
defined('_JEXEC') or die;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Factory;
use Joomla\Component\Academy\Administrator\Helper\CategoriesHelper;
class PostcategoryField extends ListField { protected $type='Postcategory'; protected function getOptions(){return array_merge(parent::getOptions(),CategoriesHelper::options(!Factory::getApplication()->isClient('administrator')));}}
