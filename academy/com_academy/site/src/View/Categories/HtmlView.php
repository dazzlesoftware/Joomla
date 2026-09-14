<?php

namespace Joomla\Component\Academy\Site\View\Categories;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Component\Academy\Administrator\Helper\CategoriesHelper;
use Joomla\Registry\Registry;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public ?Registry $params = null;
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $this->params = $app->getParams();
        $db = CategoriesHelper::db();
        $this->items = $db->setQuery($db->createQuery()->select('c.*,COUNT(p.id) AS numitems')->from('#__academy_categories AS c')->join('LEFT', '#__academy AS p ON p.catid=c.id AND p.state=1')->where('c.published=1')->whereIn('c.access', $app->getIdentity()->getAuthorisedViewLevels())->group('c.id')->order('c.title'))->loadObjectList() ?: [];
        $this->getDocument()->setTitle('Categories');
        parent::display($tpl);
    }
}
