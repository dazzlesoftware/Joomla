<?php
namespace Joomla\Component\Academy\Administrator\Field;
defined('_JEXEC') or die;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
class PostauthorsField extends ListField
{
    protected $type = 'Postauthors';
    protected function getOptions()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()->select('DISTINCT u.id AS value, u.name AS text')->from('#__users AS u')
            ->join('INNER', '#__academy AS p ON p.created_by=u.id')->order('u.name');
        return array_merge(parent::getOptions(), $db->setQuery($query)->loadObjectList());
    }
}
