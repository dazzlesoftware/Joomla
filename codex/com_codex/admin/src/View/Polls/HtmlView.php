<?php
namespace Joomla\Component\Codex\Administrator\View\Polls;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;use Joomla\CMS\Toolbar\ToolbarHelper;use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView{public array $items=[];public function display($tpl=null):void{$db=Factory::getContainer()->get(DatabaseInterface::class);$q=$db->createQuery()->select('p.*,COUNT(v.id) AS votes')->from('#__codex_polls AS p')->join('LEFT','#__codex_poll_votes AS v ON v.poll_id=p.id')->group('p.id')->order('p.created DESC');$this->items=$db->setQuery($q)->loadObjectList();ToolbarHelper::title('Codex Polls','question');parent::display($tpl);}}
