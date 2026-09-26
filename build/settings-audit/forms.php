<?php
// Read-only form construction and field rendering. No saved settings or secrets are loaded.
if (PHP_SAPI !== 'cli') { exit(1); }
define('_JEXEC', 1);
define('JPATH_BASE', realpath('C:/wamp64/www/Joomla/administrator'));
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['HTTP_HOST']='localhost';
$_SERVER['REQUEST_URI']='/Joomla/administrator/index.php';
$_SERVER['SCRIPT_NAME']='/Joomla/administrator/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container=Joomla\CMS\Factory::getContainer();
$container->alias('session.web','session.web.administrator')->alias('session','session.web.administrator')->alias(Joomla\Session\SessionInterface::class,'session.web.administrator');
$app=$container->get(Joomla\CMS\Application\AdministratorApplication::class);
Joomla\CMS\Factory::$application=$app;
$app->createExtensionNamespaceMap();
(new ReflectionMethod($app,'initialiseApp'))->invoke($app);
$app->loadDocument();
$results=[];
foreach (['academy','blog','codex'] as $family) {
    $root=dirname(__DIR__,2).'/'.$family.'/com_'.$family;
    $paths=array_merge([$root.'/admin/forms/settings.xml'], glob($root.'/site/tmpl/*/*.xml'));
    foreach ($paths as $path) {
        $xml=simplexml_load_file($path);
        $form=new Joomla\CMS\Form\Form('com_'.$family.'.audit', ['control'=>'jform']);
        $form->loadFile($path, true, str_contains($path, '/site/') ? '/metadata' : false);
        foreach ($form->getFieldsets() as $fieldset) {
            foreach ($form->getFieldset($fieldset->name) as $field) {
                try {
                    $input=$field->input;
                    $results[]=['family'=>$family,'file'=>str_replace($root.'/','',$path),'name'=>$field->fieldname,
                        'class'=>get_class($field),'status'=>'rendered','bytes'=>strlen($input)];
                } catch (Throwable $e) {
                    $results[]=['family'=>$family,'file'=>str_replace($root.'/','',$path),'name'=>$field->fieldname,
                        'status'=>'error','message'=>$e->getMessage()];
                }
            }
        }
    }
}
file_put_contents(__DIR__.'/forms.json',json_encode($results,JSON_PRETTY_PRINT));
echo count($results)." form fields checked.\n";
