<?php

namespace Joomla\Component\Blog\Site\View\Categories;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Component\Blog\Administrator\Helper\CategoriesHelper;
use Joomla\Registry\Registry;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public string $baseDescription = "";
    public ?Registry $params = null;
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $this->params = \Joomla\Component\Blog\Site\Helper\ListExcerptHelper::settings($app->getParams());
        $db = CategoriesHelper::db();
        $parentId = $app->getInput()->getInt('id', 0);
        $treeParams = clone $this->params;
        $treeParams->set('maxLevel', (int) $this->params->get('maxLevelcat', -1));
        $treeParams->set('show_empty_categories', $this->params->get('show_empty_categories_cat', 0));
        $tree = \Joomla\Component\Blog\Site\Helper\SubcategoriesHelper::load($parentId, $treeParams, true);
        $this->items = [];
        $flatten = function (array $nodes, int $depth = 0) use (&$flatten): void {
            foreach ($nodes as $node) {
                $node->directoryDepth = $depth;
                $this->items[] = $node;
                $flatten($node->children, $depth + 1);
            }
        };
        $flatten($tree);
        $this->baseDescription = $parentId > 0 ? (string) $db->setQuery(
            $db->createQuery()->select('description')->from('#__blog_categories')->where('id=' . $parentId)
                ->where('published=1')->whereIn('access', $app->getIdentity()->getAuthorisedViewLevels())
        )->loadResult() : '';

        $this->getDocument()->setTitle('Categories');
        parent::display($tpl);
    }
}
