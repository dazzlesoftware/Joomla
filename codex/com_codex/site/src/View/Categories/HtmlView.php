<?php

namespace Joomla\Component\Codex\Site\View\Categories;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Component\Codex\Administrator\Helper\CategoriesHelper;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $db = CategoriesHelper::db();
        $this->items = $db->setQuery($db->createQuery()->select('c.*,COUNT(p.id) AS numitems')->from('#__codex_categories AS c')->join('LEFT', '#__codex AS p ON p.catid=c.id AND p.state=1')->where('c.published=1')->whereIn('c.access', $app->getIdentity()->getAuthorisedViewLevels())->group('c.id')->order('c.title'))->loadObjectList() ?: [];
        $this->getDocument()->setTitle('Categories');
        parent::display($tpl);
    }
}
