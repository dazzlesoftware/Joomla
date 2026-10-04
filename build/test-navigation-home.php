<?php
if (PHP_SAPI !== 'cli') { exit(1); }
$argv=['','C:/wamp64/www/Joomla'];
require __DIR__.'/test-subcategory-styles.php';
use Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;
foreach (['academy','blog','codex'] as $family) {
    $component='com_'.$family;
    $app->bootComponent($component);
    $helper='Joomla\\Component\\'.ucfirst($family).'\\Site\\Helper\\NavigationHelper';
    $app->getInput()->set('option',$component);
    foreach (['featured','categories','authors','archive'] as $view) {
        ComponentHelper::getParams($component)->set('postnav_home',$view);
        $app->getInput()->set('view',$view);
        $home=$helper::home(new Registry(['postnav_home'=>'menu','postnav_home_menu'=>999999999]));
        if(!$home['active'] || $home['url']!=='index.php?option='.$component.'&view='.$view) { throw new RuntimeException('Destination '.$view); }
        $app->getInput()->set('view','post');
        if($helper::home(new Registry(['postnav_home'=>'menu','postnav_home_menu'=>999999999]))['active']) { throw new RuntimeException('Active state'); }
    }
    ComponentHelper::getParams($component)->set('postnav_home','authors');
    if(!str_ends_with($helper::home(new Registry(['postnav_home'=>'categories']))['url'],'view=authors')) { throw new RuntimeException('Global inheritance'); }
    ComponentHelper::getParams($component)->set('postnav_home','menu');
    ComponentHelper::getParams($component)->set('postnav_home_menu',999999999);
    if(!str_ends_with($helper::home(new Registry(['postnav_home'=>'menu','postnav_home_menu'=>999999999]))['url'],'view=featured')) { throw new RuntimeException('Missing menu fallback'); }
    $target=null;
    foreach($app->getMenu()->getItems('component_id',ComponentHelper::getComponent('com_academy')->id) as $item) {
        if($item->type==='component' && ($item->query['view']??'')==='featured' && in_array((int)$item->access,$app->getIdentity()->getAuthorisedViewLevels(),true) && in_array($item->language,['*',$app->getLanguage()->getTag()],true)) {$target=$item;break;}
    }
    if(!$target) { throw new RuntimeException('Featured menu fixture missing'); }
    $app->getMenu()->setActive($target->id);
    foreach($target->query as $key=>$value) {$app->getInput()->set($key,$value);}
    ComponentHelper::getParams($component)->set('postnav_home','menu');
    ComponentHelper::getParams($component)->set('postnav_home_menu',$target->id);
    $params=new Registry(['postnav_home'=>'menu','postnav_home_menu'=>999999999]);
    $home=$helper::home($params);
    if(!$home['active'] || !str_contains($home['url'],'Itemid='.$target->id)) { throw new RuntimeException('Selected menu'); }
    $app->getInput()->set('view','post');
    if($helper::home($params)['active']) { throw new RuntimeException('Child post must not highlight menu home'); }
    $form=Joomla\CMS\Factory::getContainer()->get(Joomla\CMS\Form\FormFactoryInterface::class)->createForm($component.'.nav.test',['control'=>'jform']);
    $form->loadFile(JPATH_ADMINISTRATOR.'/components/'.$component.'/forms/settings.xml');
    foreach(['postnav_home','postnav_home_menu'] as $name) {if(!$form->getInput($name,'params')) {throw new RuntimeException('Missing control');}}
    echo "$family: component-only destinations, stale override rejection, selected menu, active state, fallback and controls passed.\n";
}
