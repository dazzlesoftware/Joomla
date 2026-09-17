<?php
// Run with PHP CLI after deploying sources: php build/sync-votes-menu.php <site-root>
if (PHP_SAPI !== 'cli') { exit(1); }
define('_JEXEC', 1);
define('JPATH_BASE', rtrim($argv[1] ?? '', '/\\'));
if (!is_file(JPATH_BASE . '/includes/framework.php')) { throw new RuntimeException('Provide a Joomla site root.'); }
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container = Joomla\CMS\Factory::getContainer();
$container->alias(Joomla\Session\SessionInterface::class, 'session.web.site');
Joomla\CMS\Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
$db = $container->get(Joomla\Database\DatabaseInterface::class);
foreach (['academy', 'blog', 'codex'] as $family) {
    $link = 'index.php?option=com_' . $family . '&view=votes';
    $existing = $db->setQuery($db->createQuery()->select('id')->from('#__menu')->where('client_id=1')->where('link=' . $db->quote($link)))->loadResult();
    if ($existing) { echo "$family: Votes menu already registered\n"; continue; }
    $extension = (int) $db->setQuery($db->createQuery()->select('extension_id')->from('#__extensions')->where("type='component'")->where('element=' . $db->quote('com_' . $family)))->loadResult();
    $parent = (int) $db->setQuery($db->createQuery()->select('id')->from('#__menu')->where('client_id=1')->where('parent_id=1')->where('component_id=' . $extension))->loadResult();
    if (!$extension || !$parent) { throw new RuntimeException("$family component menu is not installed"); }
    $menu = new Joomla\CMS\Table\Menu($db);
    $menu->setLocation($parent, 'last-child');
    $data = ['menutype'=>'main','client_id'=>1,'title'=>'Votes','alias'=>$family.'-votes','type'=>'component','published'=>1,'parent_id'=>$parent,'component_id'=>$extension,'img'=>'class:star','home'=>0,'params'=>'','link'=>$link,'access'=>1,'language'=>'*'];
    if (!$menu->bind($data) || !$menu->check() || !$menu->store()) { throw new RuntimeException($menu->getError()); }
    $menu->rebuildPath($menu->id);
    echo "$family: Votes menu registered\n";
}
