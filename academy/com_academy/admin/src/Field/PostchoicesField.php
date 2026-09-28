<?php
namespace Joomla\Component\Academy\Administrator\Field;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;
class PostchoicesField extends ListField
{
    protected $type = 'Postchoices';
    protected function getOptions()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $rows = $db->setQuery($db->getQuery(true)->select('id, title')->from('#__academy')->order('title, id'))->loadObjectList();
        $options = parent::getOptions();
        foreach ($rows as $row) { $options[] = HTMLHelper::_('select.option', $row->id, $row->title . ' (#' . $row->id . ')'); }
        return $options;
    }
}
