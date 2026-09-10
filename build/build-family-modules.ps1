param([string]$SourceRoot = (Split-Path (Split-Path $PSScriptRoot -Parent) -Parent))
$ErrorActionPreference = 'Stop'
$dist = Join-Path $SourceRoot 'dist'
$modes = [ordered]@{
    posts='Posts'; archive='Post Archive'; categories='Post Categories'; category='Category Posts';
    latest='Latest Posts'; news='Post News'; popular='Popular Posts'; tagspopular='Popular Post Tags'; tagssimilar='Similar Tagged Posts'
}
$provider = @'
<?php
defined('_JEXEC') or die;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
return new class implements ServiceProviderInterface {public function register(Container $container):void{$container->registerServiceProvider(new ModuleDispatcherFactory('NAMESPACE'));$container->registerServiceProvider(new Module());}};
'@
$dispatcher = @'
<?php
namespace NAMESPACE\Site\Dispatcher;
defined('_JEXEC') or die;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
final class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData():array
    {
        $data=parent::getLayoutData();$db=Factory::getContainer()->get(DatabaseInterface::class);$params=$data['params'];$mode='MODE';$limit=max(1,(int)$params->get('count',5));$groups=Factory::getApplication()->getIdentity()->getAuthorisedViewLevels();$items=[];
        if($mode==='categories'){$q=$db->createQuery()->select(['id','title','alias','level'])->from('#__FAMILY_categories')->where('published=1')->whereIn('access',$groups)->where('level>0')->order('lft,title');$items=$db->setQuery($q,0,$limit)->loadObjectList();foreach($items as$item)$item->link=Route::_('index.php?option=com_FAMILY&view=category&id='.$item->id);}
        elseif($mode==='tagspopular'){$q=$db->createQuery()->select(['t.id','t.title','t.alias','COUNT(DISTINCT m.content_item_id) AS count'])->from('#__FAMILY_tag_map AS m')->join('INNER','#__FAMILY_tags AS t ON t.id=m.tag_id')->join('INNER','#__FAMILY AS p ON p.id=m.content_item_id')->where('m.type_alias='.$db->quote('com_FAMILY.post'))->where('t.published=1')->whereIn('t.access',$groups)->where('p.state=1')->whereIn('p.access',$groups)->where('(p.publish_up IS NULL OR p.publish_up <= UTC_TIMESTAMP())')->where('(p.publish_down IS NULL OR p.publish_down >= UTC_TIMESTAMP())')->where('EXISTS (SELECT 1 FROM #__categories c WHERE c.id=p.catid AND c.published=1 AND c.access IN ('.implode(',',$groups).'))')->group(['t.id','t.title','t.alias'])->order('count DESC');$items=$db->setQuery($q,0,$limit)->loadObjectList();foreach($items as$item)$item->link=Route::_('index.php?option=com_FAMILY&view=tags&tag_id='.$item->id);}
        elseif($mode==='tagssimilar'){$input=Factory::getApplication()->getInput();if($input->getCmd('option')==='com_FAMILY'&&$input->getCmd('view')==='post'&&($current=$input->getInt('id'))){$q=$db->createQuery()->select(['p.id','p.title','p.alias','p.catid','COUNT(DISTINCT other.tag_id) AS count'])->from('#__FAMILY_tag_map AS current')->join('INNER','#__FAMILY_tag_map AS other ON other.tag_id=current.tag_id AND other.type_alias='.$db->quote('com_FAMILY.post'))->join('INNER','#__FAMILY_tags AS t ON t.id=current.tag_id')->join('INNER','#__FAMILY AS p ON p.id=other.content_item_id')->where('current.type_alias='.$db->quote('com_FAMILY.post'))->where('current.content_item_id='.$current)->where('t.published=1')->whereIn('t.access',$groups)->where('t.language IN ('.$db->quote('*').','.$db->quote(Factory::getApplication()->getLanguage()->getTag()).')')->where('p.id<>'.$current)->where('p.state=1')->whereIn('p.access',$groups)->where('(p.publish_up IS NULL OR p.publish_up <= UTC_TIMESTAMP())')->where('(p.publish_down IS NULL OR p.publish_down >= UTC_TIMESTAMP())')->where('EXISTS (SELECT 1 FROM #__categories c WHERE c.id=p.catid AND c.published=1 AND c.access IN ('.implode(',',$groups).'))')->group(['p.id','p.title','p.alias','p.catid'])->order('count DESC');$items=$db->setQuery($q,0,$limit)->loadObjectList();foreach($items as$item)$item->link=Route::_('index.php?option=com_FAMILY&view=post&id='.$item->id.':'.$item->alias.'&catid='.$item->catid);}}
        elseif($mode==='archive'){$q=$db->createQuery()->select(['YEAR(created) AS year','MONTH(created) AS month','COUNT(*) AS count'])->from('#__FAMILY')->where('state=1')->whereIn('access',$groups)->group(['YEAR(created)','MONTH(created)'])->order('year DESC, month DESC');$items=$db->setQuery($q,0,$limit)->loadObjectList();foreach($items as$item){$item->title=date('F Y',mktime(0,0,0,(int)$item->month,1,(int)$item->year));$item->link=Route::_('index.php?option=com_FAMILY&view=archive&year='.$item->year.'&month='.$item->month);}}
        else{$q=$db->createQuery()->select(['p.id','p.title','p.alias','p.catid','p.summary','p.created','p.hits'])->from('#__FAMILY AS p')->where('p.state=1')->whereIn('p.access',$groups);$catids=array_values(array_filter(array_map('intval',(array)$params->get('catid',[]))));if($catids)$q->whereIn('p.catid',$catids);if($mode==='popular')$q->order('p.hits DESC');elseif($mode==='posts')$q->order('p.title ASC');else$q->order('p.created DESC');$items=$db->setQuery($q,0,$limit)->loadObjectList();foreach($items as$item)$item->link=Route::_('index.php?option=com_FAMILY&view=post&id='.$item->id.':'.$item->alias.'&catid='.$item->catid);}
        $data['list']=$items;$data['mode']=$mode;return$data;
    }
}
'@
$template = @'
<?php defined('_JEXEC') or die;if(!$list)return; ?>
<ul class="mod-FAMILY-MODE list-unstyled">
<?php foreach($list as$item):?><li class="mb-2"><a href="<?php echo $item->link;?>"><?php echo htmlspecialchars($item->title,ENT_QUOTES,'UTF-8');?></a><?php if(isset($item->count)):?> <span class="badge bg-secondary"><?php echo(int)$item->count;?></span><?php endif;?><?php if($mode==='news'&&!empty($item->summary)):?><div class="small mt-1"><?php echo strip_tags($item->summary,'<p><br><strong><em>');?></div><?php endif;?></li><?php endforeach;?>
</ul>
'@
$manifest = @'
<?xml version="1.0" encoding="UTF-8"?>
<extension type="module" client="site" method="upgrade"><name>MOD_UPPER_MODEUPPER</name><version>1.0.0</version><description>Family-specific MODETITLE module.</description><namespace path="src">NAMESPACE</namespace><files><folder module="mod_FAMILY_MODE">services</folder><folder>src</folder><folder>tmpl</folder></files><languages><language tag="en-GB">language/en-GB/mod_FAMILY_MODE.ini</language><language tag="en-GB">language/en-GB/mod_FAMILY_MODE.sys.ini</language></languages><config><fields name="params"><fieldset name="basic"><field name="catid" type="postcategory" addfieldprefix="Joomla\Component\FAMILYTITLE\Administrator\Field" multiple="true" layout="joomla.form.field.list-fancy-select" label="Categories"/><field name="count" type="number" default="5" min="1" label="Number of items"/></fieldset><fieldset name="advanced"><field name="layout" type="modulelayout" label="JFIELD_ALT_LAYOUT_LABEL"/><field name="moduleclass_sfx" type="textarea" label="COM_MODULES_FIELD_MODULECLASS_SFX_LABEL"/><field name="cache" type="list" default="1" label="COM_MODULES_FIELD_CACHING_LABEL"><option value="1">JGLOBAL_USE_GLOBAL</option><option value="0">COM_MODULES_FIELD_VALUE_NOCACHING</option></field><field name="cache_time" type="number" default="900" label="COM_MODULES_FIELD_CACHE_TIME_LABEL"/></fieldset></fields></config></extension>
'@
foreach($family in @('academy','blog','codex')){
    $familyTitle=(Get-Culture).TextInfo.ToTitleCase($family);$familyDist=Join-Path $dist $family
    $modulesDist=Join-Path $familyDist 'modules';$outDist=Join-Path $familyDist 'dist';New-Item -ItemType Directory -Force -Path $modulesDist,$outDist | Out-Null
    $packageRoot=Join-Path $familyDist 'build' 'modules';if(Test-Path $packageRoot){Remove-Item -LiteralPath $packageRoot -Recurse -Force}
    New-Item -ItemType Directory -Force -Path $packageRoot | Out-Null;$packageFiles=@()
    foreach($entry in $modes.GetEnumerator()){
        $mode=$entry.Key;$modeTitle=$entry.Value;$module="mod_${family}_${mode}";$namespace="Joomla\Module\${familyTitle}$((Get-Culture).TextInfo.ToTitleCase($mode))";$root=Join-Path $modulesDist $mode
        if(Test-Path $root){Remove-Item -LiteralPath $root -Recurse -Force};New-Item -ItemType Directory -Force -Path "$root/services","$root/src/Dispatcher","$root/tmpl","$root/language/en-GB"|Out-Null
        [IO.File]::WriteAllText("$root/services/provider.php",$provider.Replace('NAMESPACE',$namespace),[Text.UTF8Encoding]::new($false))
        [IO.File]::WriteAllText("$root/src/Dispatcher/Dispatcher.php",$dispatcher.Replace('NAMESPACE',$namespace).Replace('FAMILY',$family).Replace('MODE',$mode),[Text.UTF8Encoding]::new($false))
        [IO.File]::WriteAllText("$root/tmpl/default.php",$template.Replace('FAMILY',$family).Replace('MODE',$mode),[Text.UTF8Encoding]::new($false))
        $xml=$manifest.Replace('FAMILY',$family).Replace('FAMILYTITLE',$familyTitle).Replace('MODETITLE',$modeTitle).Replace('MODEUPPER',$mode.ToUpper()).Replace('MODE',$mode).Replace('UPPER',$family.ToUpper()).Replace('NAMESPACE',$namespace);[IO.File]::WriteAllText("$root/$module.xml",$xml,[Text.UTF8Encoding]::new($false))
        $language="MOD_$($family.ToUpper())_$($mode.ToUpper())=`"$familyTitle $modeTitle`"`r`n";[IO.File]::WriteAllText("$root/language/en-GB/$module.ini",$language,[Text.UTF8Encoding]::new($false));[IO.File]::WriteAllText("$root/language/en-GB/$module.sys.ini",$language,[Text.UTF8Encoding]::new($false))
        $zip=Join-Path $outDist "$module.zip";Compress-Archive -Path "$root/*" -DestinationPath $zip -Force;Copy-Item -LiteralPath $zip -Destination $packageRoot -Force;$packageFiles+="$module.zip"
    }
    $fileXml=($packageFiles|ForEach-Object{"<file type=`"module`" id=`"$($_ -replace '\.zip$','')`">$_</file>"})-join'';$pkg="<?xml version=`"1.0`" encoding=`"UTF-8`"?><extension type=`"package`" method=`"upgrade`"><name>PKG_$($family.ToUpper())_MODULES</name><packagename>${family}_modules</packagename><version>1.0.0</version><files>$fileXml</files></extension>";[IO.File]::WriteAllText("$packageRoot/pkg_${family}_modules.xml",$pkg,[Text.UTF8Encoding]::new($false));Compress-Archive -Path "$packageRoot/*" -DestinationPath (Join-Path $outDist "modules.zip") -Force
}
