<?php
namespace Joomla\Component\Blog\Administrator\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class CategoriesHelper
{
    public static function db(): DatabaseInterface { return Factory::getContainer()->get(DatabaseInterface::class); }
    public static function options(bool $site = false): array
    {
        $db = self::db(); $q = $db->createQuery()->select('id AS value,title AS text')->from('#__blog_categories')->order('lft,title');
        if ($site) { $q->where('published=1')->whereIn('access', Factory::getApplication()->getIdentity()->getAuthorisedViewLevels())->where('language IN (' . $db->quote('*') . ',' . $db->quote(Factory::getApplication()->getLanguage()->getTag()) . ')'); }
        return $db->setQuery($q)->loadObjectList() ?: [];
    }
    public static function save(array $data): int
    {
        $db = self::db(); $id=(int)($data['id'] ?? 0); $title=trim(strip_tags((string)($data['title'] ?? ''))); if($title==='') throw new \InvalidArgumentException('A category title is required.');
        $alias=ApplicationHelper::stringURLSafe((string)($data['alias'] ?? $title)); if($alias==='')$alias='category-'.bin2hex(random_bytes(4)); $base=mb_substr($alias,0,185);$n=2;
        while((int)$db->setQuery('SELECT id FROM #__blog_categories WHERE alias='.$db->quote($alias).($id?' AND id<>'.$id:''))->loadResult()) $alias=$base.'-'.$n++;
        $identity = Factory::getApplication()->getIdentity(); $userId = (int) ($identity?->id ?? 0);
        $row=(object)['id'=>$id?:null,'title'=>$title,'alias'=>$alias,'description'=>(string)($data['description']??''),'published'=>(int)($data['published']??1),'access'=>(int)($data['access']??1),'language'=>(string)($data['language']??'*'),'parent_id'=>(int)($data['parent_id']??0),'created_time'=>Factory::getDate()->toSql(),'created_user_id'=>$userId,'modified_time'=>Factory::getDate()->toSql(),'modified_user_id'=>$userId,'metadata'=>'{}','params'=>'{}'];
        if($id){$old=$db->setQuery('SELECT * FROM #__blog_categories WHERE id='.$id)->loadObject();if(!$old)throw new \RuntimeException('Category not found.',404);foreach(['created_time','created_user_id','metadata','params','asset_id','lft','rgt','level','path'] as $field)if(isset($old->$field))$row->$field=$old->$field;$db->updateObject('#__blog_categories',$row,'id');}else{$row->lft=$row->rgt=$row->level=0;$row->path=$alias;$db->insertObject('#__blog_categories',$row,'id');}
        return (int)$row->id;
    }
}
