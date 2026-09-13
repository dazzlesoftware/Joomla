<?php

namespace Joomla\Module\CodexCategory\Site\Dispatcher;

defined('_JEXEC') or die;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;

final class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $params = $data['params'];
        $mode = 'category';
        $limit = max(1, (int)$params->get('count', 5));
        $groups = Factory::getApplication()->getIdentity()->getAuthorisedViewLevels();
        $items = [];
        if ($mode === 'categories') {
            $q = $db->createQuery()->select(['id','title','alias','level'])->from('#__codex_categories')->where('published=1')->whereIn('access', $groups)->where('level>0')->order('lft,title');
            $items = $db->setQuery($q, 0, $limit)->loadObjectList();
            foreach ($items as $item) {
                $item->link = Route::_('index.php?option=com_codex&view=category&id='.$item->id);
            }
        } elseif ($mode === 'tagspopular') {
            $q = $db->createQuery()->select(['t.id','t.title','t.alias','COUNT(DISTINCT m.content_item_id) AS count'])->from('#__codex_tag_map AS m')->join('INNER', '#__codex_tags AS t ON t.id=m.tag_id')->join('INNER', '#__codex AS p ON p.id=m.content_item_id')->where('m.type_alias='.$db->quote('com_codex.post'))->where('t.published=1')->whereIn('t.access', $groups)->where('p.state=1')->whereIn('p.access', $groups)->where('(p.publish_up IS NULL OR p.publish_up <= UTC_TIMESTAMP())')->where('(p.publish_down IS NULL OR p.publish_down >= UTC_TIMESTAMP())')->where('EXISTS (SELECT 1 FROM #__codex_categories c WHERE c.id=p.catid AND c.published=1 AND c.access IN ('.implode(',', $groups).'))')->group(['t.id','t.title','t.alias'])->order('count DESC');
            $items = $db->setQuery($q, 0, $limit)->loadObjectList();
            foreach ($items as $item) {
                $item->link = Route::_('index.php?option=com_codex&view=tags&tag_id='.$item->id);
            }
        } elseif ($mode === 'tagssimilar') {
            $input = Factory::getApplication()->getInput();
            if ($input->getCmd('option') === 'com_codex' && $input->getCmd('view') === 'post' && ($current = $input->getInt('id'))) {
                $q = $db->createQuery()->select(['p.id','p.title','p.alias','p.catid','COUNT(DISTINCT other.tag_id) AS count'])->from('#__codex_tag_map AS current')->join('INNER', '#__codex_tag_map AS other ON other.tag_id=current.tag_id AND other.type_alias='.$db->quote('com_codex.post'))->join('INNER', '#__codex_tags AS t ON t.id=current.tag_id')->join('INNER', '#__codex AS p ON p.id=other.content_item_id')->where('current.type_alias='.$db->quote('com_codex.post'))->where('current.content_item_id='.$current)->where('t.published=1')->whereIn('t.access', $groups)->where('t.language IN ('.$db->quote('*').','.$db->quote(Factory::getApplication()->getLanguage()->getTag()).')')->where('p.id<>'.$current)->where('p.state=1')->whereIn('p.access', $groups)->where('(p.publish_up IS NULL OR p.publish_up <= UTC_TIMESTAMP())')->where('(p.publish_down IS NULL OR p.publish_down >= UTC_TIMESTAMP())')->where('EXISTS (SELECT 1 FROM #__codex_categories c WHERE c.id=p.catid AND c.published=1 AND c.access IN ('.implode(',', $groups).'))')->group(['p.id','p.title','p.alias','p.catid'])->order('count DESC');
                $items = $db->setQuery($q, 0, $limit)->loadObjectList();
                foreach ($items as $item) {
                    $item->link = Route::_('index.php?option=com_codex&view=post&id='.$item->id.':'.$item->alias.'&catid='.$item->catid);
                }
            }
        } elseif ($mode === 'archive') {
            $q = $db->createQuery()->select(['YEAR(created) AS year','MONTH(created) AS month','COUNT(*) AS count'])->from('#__codex')->where('state=1')->whereIn('access', $groups)->group(['YEAR(created)','MONTH(created)'])->order('year DESC, month DESC');
            $items = $db->setQuery($q, 0, $limit)->loadObjectList();
            foreach ($items as $item) {
                $item->title = date('F Y', mktime(0, 0, 0, (int)$item->month, 1, (int)$item->year));
                $item->link = Route::_('index.php?option=com_codex&view=archive&year='.$item->year.'&month='.$item->month);
            }
        } else {
            $q = $db->createQuery()->select(['p.id','p.title','p.alias','p.catid','p.summary','p.created','p.hits'])->from('#__codex AS p')->where('p.state=1')->whereIn('p.access', $groups);
            $catids = array_values(array_filter(array_map('intval', (array)$params->get('catid', []))));
            if ($catids) {
                $q->whereIn('p.catid', $catids);
            }if ($mode === 'popular') {
                $q->order('p.hits DESC');
            } elseif ($mode === 'posts') {
                $q->order('p.title ASC');
            } else {
                $q->order('p.created DESC');
            }$items = $db->setQuery($q, 0, $limit)->loadObjectList();
            foreach ($items as $item) {
                $item->link = Route::_('index.php?option=com_codex&view=post&id='.$item->id.':'.$item->alias.'&catid='.$item->catid);
            }
        }
        $data['list'] = $items;
        $data['mode'] = $mode;
        return$data;
    }
}
