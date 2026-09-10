<?php
namespace Joomla\Component\Academy\Administrator\View\Comments;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;use Joomla\CMS\Toolbar\ToolbarHelper;use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView { public array $items=[]; public function display($tpl=null):void{$db=Factory::getContainer()->get(DatabaseInterface::class);$q=$db->createQuery()->select('c.*,p.title AS post_title')->from('#__academy_comments AS c')->join('LEFT','#__academy AS p ON p.id=c.post_id')->order('c.created DESC');$this->items=$db->setQuery($q)->loadObjectList();ToolbarHelper::title('Academy Comments','comments');parent::display($tpl);} }
