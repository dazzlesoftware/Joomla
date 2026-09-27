<?php
// Offline provider fixtures. No network requests, saved settings or content changes.
if (PHP_SAPI !== 'cli') { exit(1); }
define('_JEXEC', 1);
define('JPATH_BASE', rtrim($argv[1] ?? '', '/\\'));
$_SERVER['HTTP_HOST']='localhost';
$_SERVER['REQUEST_URI']='/Joomla/index.php';
$_SERVER['SCRIPT_NAME']='/Joomla/index.php';
require JPATH_BASE.'/includes/defines.php';
require JPATH_BASE.'/includes/framework.php';
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
$c=Factory::getContainer();
$c->alias('session.web','session.web.site')->alias('session','session.web.site')->alias(Joomla\Session\SessionInterface::class,'session.web.site');
$app=$c->get(Joomla\CMS\Application\SiteApplication::class);
Factory::$application=$app; $app->createExtensionNamespaceMap();
(new ReflectionMethod($app,'initialiseApp'))->invoke($app); $app->loadDocument();
function check($actual,$expected,$label) { if($actual!==$expected) throw new RuntimeException($label.': '.json_encode($actual)); }
function fails(Closure $call,string $message): void { try {$call();} catch(Throwable $e) {if(str_contains($e->getMessage(),$message))return;throw $e;} throw new RuntimeException('Expected failure: '.$message); }

use Joomla\Plugin\User\GenesisProfile\Helper\AiProfileHelper;
require __DIR__.'/../plugins/user/genesisprofile/src/Helper/AiProfileHelper.php';
class PersonalAiUser extends Joomla\CMS\User\User {
    public array $grants = ['ai.generate', 'core.create'];
    public array $testGroups = [2, 3, 4];
    public function authorise($action, $assetname = null) { return in_array($action, $this->grants, true); }
    public function getAuthorisedGroups() { return $this->testGroups; }
}
$db = $c->get(Joomla\Database\DatabaseInterface::class);
$id = 2147483000;
check((int) $db->setQuery('SELECT COUNT(*) FROM #__user_profiles WHERE user_id='.$id)->loadResult(), 0, 'unused fixture ID');
$user = new PersonalAiUser(); $user->id=$id; $user->guest=0; $app->loadIdentity($user);
$oldEnv=getenv('OPENAI_API_KEY');
try {
    foreach (['academy','blog','codex'] as $f) {
        $base=__DIR__.'/../'.$f.'/com_'.$f;
        $ns='Joomla\\Component\\'.ucfirst($f).'\\Administrator\\Helper\\';
        foreach (['NeuralNetworkService','NeuralNetworkAccessHelper','NeuralNetworkModelRegistry','NeuralNetworkEditorHelper'] as $class) { if (!class_exists($ns.$class, false)) require $base.'/admin/src/Helper/'.$class.'.php'; }
        $access=$ns.'NeuralNetworkAccessHelper';$service=$ns.'NeuralNetworkService';
        $params=ComponentHelper::getParams('com_'.$f);$params->set('ai_enabled',1);$params->set('ai_images',1);$params->set('ai_frontend_groups',[4]);
        $params->set('ai_openai_secret',$service::seal('site-key'));
        $user->grants=['ai.generate','core.create'];$user->testGroups=[2,3,4];
        check($access::settings($params,$user),null,'no personal key hides tools');
        AiProfileHelper::save($id,['provider'=>'openai','openai_key'=>'personal-fixture-key','openai_model'=>'gpt-4.1-mini']);
        $stored=AiProfileHelper::load($id);
        if ($f === 'academy') {
            require __DIR__.'/../plugins/user/genesisprofile/src/Extension/GenesisProfile.php';
            $plugin = new Joomla\Plugin\User\GenesisProfile\Extension\GenesisProfile(['name'=>'genesisprofile','type'=>'user','params'=>'{}']);
            $plugin->setApplication($app);
            foreach (['edit', 'save', 'display', 'registration', 'foreign'] as $mode) {
                $app->getInput()->set('layout', $mode === 'edit' || $mode === 'foreign' ? 'edit' : '');
                $app->getInput()->set('task', $mode === 'save' ? 'profile.save' : '');
                $form = new Joomla\CMS\Form\Form($mode === 'registration' ? 'com_users.registration' : 'com_users.profile');
                $event = new Joomla\CMS\Event\Model\PrepareFormEvent('onContentPrepareForm', ['subject'=>$form,'data'=>(object)['id'=>$mode === 'foreign' ? $id+1 : $id]]);
                $plugin->onContentPrepareForm($event);
                $keyField=$form->getField('openai_key','genesisai');
                check((bool)$keyField,in_array($mode,['edit','save'],true),'profile field visibility '.$mode);
                if ($keyField) {
                    $xml = simplexml_load_file(__DIR__.'/../plugins/user/genesisprofile/forms/genesisai.xml');
                    foreach ($xml->xpath('//@label | //@description') as $attribute) {
                        $key = (string) $attribute;
                        check(Joomla\CMS\Language\Text::_($key) !== $key, true, 'profile translation '.$key);
                    }
                    check(Joomla\CMS\Language\Text::_('PLG_USER_GENESISPROFILE_AI_KEY_SAVED') !== 'PLG_USER_GENESISPROFILE_AI_KEY_SAVED', true, 'saved-key translation');

                    check(str_contains($keyField->input,'personal-fixture-key'),false,'no plaintext in form');
                    check(str_contains($keyField->input,$stored->get('ai_openai_secret')),false,'no encrypted secret in form');
                    check($keyField->description,'PLG_USER_GENESISPROFILE_AI_KEY_SAVED','saved-key status');
                    foreach (['openai_model'=>'gpt-4.1-mini','claude_model'=>'claude-sonnet-4-6','image_model'=>'gpt-image-1.5'] as $model => $expected) {
                        $html=$form->getField($model,'genesisai')->input;
                        check(str_contains($html,'<select'),true,'model dropdown');
                        check(str_contains($html,$expected),true,'component model choice');
                        check(str_contains($html,'Use component model'),true,'inherit option');
                        check(str_contains($html,'__custom__'),true,'custom option');
                    }
                    $filtered=$form->filter(['genesisai'=>['provider'=>'openai','openai_key'=>'replacement','openai_model'=>'gpt-4.1-mini']]);
                    check($filtered['genesisai']['openai_key'],'replacement','profile submission preserves key input');
                }
            }
        }
        check(str_contains($stored->toString(),'personal-fixture-key'),false,'key encrypted');
        AiProfileHelper::save($id,['openai_key'=>'']);
        check(AiProfileHelper::load($id)->get('ai_openai_secret'),$stored->get('ai_openai_secret'),'blank retains key');
        AiProfileHelper::save($id,['openai_model'=>'__custom__','openai_model_custom'=>'custom-fixture-model']);
        check(AiProfileHelper::load($id)->get('ai_openai_model'),'custom-fixture-model','custom ID saved');
        AiProfileHelper::save($id,['openai_model'=>'gpt-4.1-mini']);
        $resolved=$access::settings($params,$user);
        check($resolved instanceof Registry,true,'eligible editor personal key accepted');
        check($resolved->get('ai_claude_secret',''),'','site credentials cleared');
        putenv('OPENAI_API_KEY=site-environment-fixture');
        $transport=function($url,$payload,$headers) {
            check($headers['Authorization'],'Bearer personal-fixture-key','personal key overrides site environment');
            return (object)['code'=>200,'body'=>'{"output":[{"content":[{"type":"output_text","text":"Fixture excerpt"}]}]}'];
        };
        check((new $service($resolved,$transport))->generate('excerpt','Test text','')['text'],'Fixture excerpt','offline generation');
        $missing=clone $resolved;$missing->set('ai_openai_secret','');
        fails(fn()=>(new $service($missing,$transport))->generate('excerpt','Text',''),'Configure');
        $user->testGroups=[2];check($access::settings($params,$user),null,'wrong group denied despite key');
        $user->testGroups=[2,3,4,5];check($access::settings($params,$user)!==null,true,'child group inherits');
        $params->set('ai_frontend_groups',[]);check($access::settings($params,$user),null,'empty group selection denies');$params->set('ai_frontend_groups',[4]);
        $user->grants=['core.create'];check($access::settings($params,$user),null,'AI ACL required');
        $user->grants=['ai.generate','core.create'];$user->guest=1;check($access::settings($params,$user),null,'guest denied');$user->guest=0;
        $params->set('ai_enabled',0);check($access::settings($params,$user),null,'master off');$params->set('ai_enabled',1);
        $user->grants=['ai.generate','core.create','core.login.admin','core.manage'];
        check($access::settings($params,$user)->get('ai_personal_credentials',false),false,'admin uses site settings');
        $user->grants=['ai.generate','core.create'];
        // Saving someone else's profile cannot replace their keys.
        AiProfileHelper::save($id+1,['openai_key'=>'injected']);
        check(AiProfileHelper::load($id+1)->toArray(),[],'foreign profile write refused');
        $controllerClass='Joomla\\Component\\'.ucfirst($f).'\\Administrator\\Controller\\NeuralNetworkController';
        require $base.'/admin/src/Controller/NeuralNetworkController.php';
        $controller=new $controllerClass(['base_path'=>JPATH_ADMINISTRATOR.'/components/com_'.$f],null,$app,$app->getInput());
        $guard=new ReflectionMethod($controller,'guard');
        $app->getInput()->post->set(Joomla\CMS\Session\Session::getFormToken(),1);
        check($guard->invoke($controller)->get('ai_personal_credentials'),true,'endpoint resolves personal settings');
        $user->testGroups=[2];fails(fn()=>$guard->invoke($controller),'permission');$user->testGroups=[4];
        AiProfileHelper::save($id,['openai_clear'=>1]);
        check($access::settings($params,$user),null,'removed key denies');
        $form=new Joomla\CMS\Form\Form('settings');$form->loadFile($base.'/admin/forms/settings.xml');
        check($form->getField('ai_frontend_groups','params')!==false,true,'group field renders');
        echo "$f: personal credentials, group inheritance, ACL and endpoint checks passed\n";
    }
} finally {
    $db->setQuery('DELETE FROM #__user_profiles WHERE user_id='.$id.' AND profile_key='.$db->quote('genesisprofile.ai'))->execute();
    putenv($oldEnv===false?'OPENAI_API_KEY':'OPENAI_API_KEY='.$oldEnv);
}
