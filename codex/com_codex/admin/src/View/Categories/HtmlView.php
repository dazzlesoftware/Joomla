<?php
namespace Joomla\Component\Codex\Administrator\View\Categories;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;use Joomla\Component\Codex\Administrator\Helper\CategoriesHelper;
final class HtmlView extends BaseHtmlView {public array $items=[];public $item;public function display($tpl=null):void{$app=Factory::getApplication();if(!$app->getIdentity()->authorise('core.manage','com_codex'))throw new \RuntimeException('Not authorised',403);$db=CategoriesHelper::db();$this->items=$db->setQuery('SELECT c.*,COUNT(p.id) AS post_count FROM #__codex_categories c LEFT JOIN #__codex p ON p.catid=c.id GROUP BY c.id ORDER BY c.title')->loadObjectList()?:[];$id=$app->getInput()->getInt('category_id');$this->item=$id?$db->setQuery('SELECT * FROM #__codex_categories WHERE id='.$id)->loadObject():(object)['id'=>0,'title'=>'','alias'=>'','description'=>'','published'=>1,'access'=>1,'language'=>'*','parent_id'=>0];parent::display($tpl);}}
