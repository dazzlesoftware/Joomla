<?php
namespace Joomla\Module\CodexArchive\Site\Dispatcher;
defined('_JEXEC') or die;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Component\Codex\Site\Helper\ArchivePostsHelper;
use Joomla\Component\Codex\Site\Helper\ListingFilterHelper;

final class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();
        $app = Factory::getApplication();
        $app->bootComponent('com_codex');
        $input = $app->getInput();
        $onArchive = $input->getCmd('option') === 'com_codex' && $input->getCmd('view') === 'archive';
        $params = ArchivePostsHelper::settings($onArchive ? $app->getParams('com_codex') : ComponentHelper::getParams('com_codex'), $onArchive);
        foreach (['listing_authors', 'listing_exclude_authors', 'listing_categories', 'listing_exclude_categories', 'listing_tags'] as $key) {
            $ids = ListingFilterHelper::ids($data['params']->get($key, []));
            if ($key === 'listing_categories' && !$ids) { $ids = ListingFilterHelper::ids($data['params']->get('catid', [])); }
            if ($ids) { $params->set($key, $ids); }
        }
        $children = $data['params']->get('listing_subcategories', '');
        if ($children !== '' && $children !== null) { $params->set('listing_subcategories', (int) (bool) $children); }
        $model = $app->bootComponent('com_codex')->getMVCFactory()->createModel('Archive', 'Site');
        $model->getState();
        $model->setState('params', $params);
        $model->setState('filter.access', !in_array($params->get('show_noauth', 0), [1, '1', 'use_post'], true));
        $model->setState('filter.tag', null);
        $model->setState('list.filter', '');
        $items = $model->getArchiveMonths((int) $data['params']->get('count', 5));
        $filters = [];
        foreach (['listing_authors', 'listing_exclude_authors', 'listing_categories', 'listing_exclude_categories', 'listing_tags', 'listing_exclude_posts'] as $key) {
            $filters[$key] = ListingFilterHelper::ids($params->get($key, [])) ?: [0];
        }
        $filters['listing_subcategories'] = (int) $params->get('listing_subcategories', 0);
        $filters['order_date'] = $params->get('order_date', 'published');
        foreach ($items as $item) {
            $item->title = date('F Y', mktime(0, 0, 0, (int) $item->month, 1, (int) $item->year));
            $query = ['option'=>'com_codex', 'view'=>'archive', 'year'=>$item->year, 'month'=>$item->month, 'archive_filters'=>$filters];
            if ($onArchive) { $query['Itemid'] = $input->getInt('Itemid'); }
            $item->link = Route::_('index.php?' . http_build_query($query));
        }
        $data['list'] = $items;
        $data['mode'] = 'archive';
        return $data;
    }
}
