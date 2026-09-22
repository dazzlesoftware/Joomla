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
class NeuralNetworkTestUser extends Joomla\CMS\User\User {
    public array $grants=[];
    public function authorise($action, $assetname = null) { return in_array($action,$this->grants,true); }
}
require __DIR__.'/../plugins/neuralnetwork/modelcatalog/src/Extension/Modelcatalog.php';
$catalog=new Joomla\Plugin\Neuralnetwork\Modelcatalog\Extension\Modelcatalog(['params'=>json_encode(['models'=>[
 ['provider'=>'openai','kind'=>'text','id'=>'fixture-text','label'=>'Fixture text'],
 ['provider'=>'openai','kind'=>'image','id'=>'fixture-image','label'=>'Fixture image'],
 ['provider'=>'claude','kind'=>'text','id'=>'fixture-claude','label'=>'Fixture Claude'],
 ['provider'=>'openai','kind'=>'text','id'=>'invalid id','label'=>'Invalid'],
 ['provider'=>'openai','kind'=>'text','id'=>'gpt-4.1-mini','label'=>'Duplicate']
]])]);
$app->getDispatcher()->addSubscriber($catalog);
$env=[];
foreach(['OPENAI_API_KEY','ANTHROPIC_API_KEY'] as $key){$env[$key]=getenv($key);putenv($key);}
try {
foreach(['academy','blog','codex'] as $f) {
    $base=__DIR__.'/../'.$f.'/com_'.$f;
    require $base.'/admin/src/Helper/NeuralNetworkModelRegistry.php';
    require $base.'/admin/src/Field/NeuralnetworkmodelField.php';
    require $base.'/admin/src/Helper/NeuralNetworkService.php';
    require $base.'/admin/src/Controller/NeuralNetworkController.php';
    require $base.'/site/src/Helper/SeoHelper.php';
    $ns='Joomla\\Component\\'.ucfirst($f);
    $service=$ns.'\\Administrator\\Helper\\NeuralNetworkService';
    $seo=$ns.'\\Site\\Helper\\SeoHelper';
    $registry=$ns.'\\Administrator\\Helper\\NeuralNetworkModelRegistry';
    $models=$registry::models('openai','text');
    check($models['fixture-text'],'Fixture text','plugin text catalog');
    check(isset($models['fixture-image']),false,'modality filtering');
    check(isset($models['fixture-claude']),false,'provider filtering');
    check(isset($models['invalid id']),false,'invalid model IDs excluded');
    check($models['gpt-4.1-mini'],'GPT-4.1 Mini','builtins cannot be replaced');
    check($registry::models('openai','image')['fixture-image'],'Fixture image','plugin images');
    check($registry::models('claude','text')['fixture-claude'],'Fixture Claude','plugin Claude');
    check($registry::selected(new Registry(['model'=>'legacy-model']),'model','fallback'),'legacy-model','legacy saved ID');
    check($registry::selected(new Registry(['model'=>'__custom__','model_custom'=>'my-model']),'model','fallback'),'my-model','custom model');
    fails(fn()=>$registry::selected(new Registry(['model'=>'__custom__']),'model','fallback'),'valid custom model');
    $sealed=$service::seal('fixture-secret');
    foreach (['credit_balance_exhausted'=>'credits are exhausted','project_spend_limit_exceeded'=>'project spend limit','organization_spend_limit_exceeded'=>'organization spend limit','organization_usage_limit_exceeded'=>'organization usage limit','insufficient_quota'=>'quota is unavailable','rate_limit_exceeded'=>'rate limit has been reached'] as $code=>$expected) {
        $message=$service::providerError('openai',429,json_encode(['error'=>['code'=>$code,'message'=>'PRIVATE fixture-secret source text']]));
        check(str_contains($message,$expected),true,'specific provider error: '.$code);
        check(str_contains($message,'fixture-secret'),false,'provider message never exposes secrets');
    }
    check(str_contains($service::providerError('claude',429,'{"error":{"type":"rate_limit_error"}}'),'rate limit has been reached'),true,'Claude rate limit');
    check(str_contains($service::providerError('openai',429,'not JSON'),'rate or quota limit'),true,'unknown 429 is not misclassified');
    check(str_contains($sealed,'fixture-secret'),false,'encrypted key');
    check($sealed!==$service::seal('fixture-secret'),true,'random IV');
    $settings=new Registry(['ai_openai_secret'=>$sealed,'ai_claude_secret'=>$sealed]);
    $settingsForm=new Joomla\CMS\Form\Form('ai-settings-'.$f);
    $settingsForm->loadFile($base.'/admin/forms/settings.xml');
    foreach(['openai','claude'] as $provider){
        $keyField=$settingsForm->getField('ai_'.$provider.'_key','params');
        check($keyField->maxLength,2048,'API key input must override Joomla 99-character password default');
    }
    $modelField=$settingsForm->getField('ai_openai_model','params');
    check(str_contains($modelField->input,'<select'),true,'model uses dropdown');
    check(str_contains($modelField->input,'fixture-text'),true,'dropdown includes plugin models');
    check(str_contains($modelField->input,'__custom__'),true,'dropdown includes Custom');
    check($settingsForm->getFieldAttribute('ai_openai_model_custom','showon','','params'),'ai_openai_model:__custom__','custom textbox is conditional');
    $reply=['status'=>'completed','output'=>[['content'=>[['type'=>'output_text','text'=>'<p>Rewritten safely.</p>']]]]];
    $capture=[];
    $http=static function($url,$payload,$headers)use(&$capture,&$reply){$capture=[$url,$payload,$headers];return(object)['code'=>200,'body'=>json_encode($reply)];};
    $ai=new $service($settings,$http);
    check($ai->generate('rewrite','<p>Original.</p>','Clearer')['text'],'<p>Rewritten safely.</p>','OpenAI rewrite');
    check($capture[0],'https://api.openai.com/v1/responses','OpenAI endpoint');
    check($capture[1]['store'],false,'no stored response');
    $settings->set('ai_openai_model','__custom__');$settings->set('ai_openai_model_custom','fixture-custom');
    $ai->generate('rewrite','Original','');
    check($capture[1]['model'],'fixture-custom','custom model sent to provider');
    $settings->set('ai_openai_model','fixture-text');$ai->generate('rewrite','Original','');
    check($capture[1]['model'],'fixture-text','plugin model sent to provider');
    check($capture[2]['Authorization'],'Bearer fixture-secret','key decryption');
    $longKey='sk-proj-'.str_repeat('x',180);
    $longSettings=new Registry(['ai_openai_secret'=>$service::seal($longKey)]);
    (new $service($longSettings,$http))->generate('excerpt','Original','');
    check($capture[2]['Authorization'],'Bearer '.$longKey,'long API key encryption and request header preserve every character');
    $reply['output'][0]['content'][0]['text']='<p onclick="evil()">Clean</p><script>alert(1)</script>';
    $clean=$ai->generate('rewrite','Original','')['text'];
    check(str_contains($clean,'onclick'),false,'event stripped');check(str_contains($clean,'<script'),false,'script stripped');
    $original='<p>Hello</p>{video url="movie.mp4"}<img src="cover.png" data-caption="Keep"><hr id="system-readmore">';
    $reply['output'][0]['content'][0]['text']=str_replace('Hello','Welcome',$original);
    check($ai->generate('rewrite',$original,'')['text'],str_replace('Hello','Welcome',$original),'media and shortcodes preserved exactly');
    $reply['output'][0]['content'][0]['text']='<p>Lost media</p>';
    fails(fn()=>$ai->generate('rewrite',$original,''),'changed embedded');
    $reply['status']='incomplete';fails(fn()=>$ai->generate('excerpt','Original',''),'incomplete');$reply['status']='completed';
    $reply['output'][0]['content'][0]['text']=json_encode(['seo_title'=>'<b>Title</b>','og_type'=>'bad','metadesc'=>str_repeat('x',400),'canonical'=>'https://unwanted.example','robots'=>'noindex']);
    $suggestions=$ai->generate('seo','Original','')['fields'];
    check($suggestions['seo_title'],'Title','plain SEO');check(strlen($suggestions['metadesc']),300,'description limit');check($suggestions['og_type'],'article','OG enum');check(isset($suggestions['robots']),false,'no indexing change');check(isset($suggestions['canonical']),false,'no canonical invention');
    $settings->set('ai_provider','claude');$reply=['content'=>[['type'=>'text','text'=>'Short excerpt.']],'stop_reason'=>'end_turn'];
    check($ai->generate('excerpt','Original','')['text'],'Short excerpt.','Claude excerpt');check($capture[0],'https://api.anthropic.com/v1/messages','Claude endpoint');check($capture[2]['x-api-key'],'fixture-secret','Claude key');check($capture[1]['messages'][0]['role'],'user','Claude payload');
    $reply['stop_reason']='max_tokens';fails(fn()=>$ai->generate('excerpt','Original',''),'incomplete');
    $png=base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6VioAAAAASUVORK5CYII='));
    $reply=['data'=>[['b64_json'=>$png]]];check($ai->generate('image','','A test image')['image'],$png,'image preview');check($capture[0],'https://api.openai.com/v1/images/generations','image always OpenAI');
    fails(fn()=>$service::imageBytes(base64_encode('<html>not an image</html>')),'supported PNG');
    fails(fn()=>$ai->generate('bad','Text',''),'Unknown');fails(fn()=>$ai->generate('excerpt',str_repeat('x',60001),''),'shorter');
    fails(fn()=>(new $service($settings,fn()=>throw new RuntimeException('PRIVATE KEY fixture-secret')))->generate('excerpt','Original',''),'could not be reached');
    fails(fn()=>(new $service(new Registry(),$http))->generate('excerpt','Original',''),'Configure');
    $doc=new Joomla\CMS\Document\HtmlDocument();
    $item=(object)['title'=>'Post','metadesc'=>'Description','metakey'=>'one, two','created'=>'2026-01-01 00:00:00','media'=>json_encode(['featured_image'=>'images/cover.png#joomlaImage://local-images/cover.png?width=1&height=1','featured_image_alt'=>'Cover']),'metadata'=>new Registry(['seo_title'=>'SEO title','robots'=>'noindex, follow','canonical'=>'https://example.com/post','og_title'=>'Social title'])];
    $seo::apply($doc,$item);
    check($doc->getTitle(),'SEO title','SEO title');check($doc->getMetaData('robots'),'noindex, follow','robots');check($doc->getMetaData('og:title','property'),'Social title','OG title');check($doc->getMetaData('og:image','property'),Joomla\CMS\Uri\Uri::root().'images/cover.png','OG featured fallback');check($doc->getMetaData('og:image:alt','property'),'Cover','alt fallback');check($doc->getMetaData('twitter:card'),'summary_large_image','social card');check($seo::url('javascript:alert(1)'),'','unsafe image');
    foreach(['admin','site'] as $side){$form=new Joomla\CMS\Form\Form('ai-'.$f.'-'.$side);$form->loadFile($base.'/'.$side.'/forms/post.xml');check(count($form->getFieldset('jmetadata')),11,'SEO form fields');}
    $controllerClass=$ns.'\\Administrator\\Controller\\NeuralNetworkController';
    $controller=new $controllerClass(['base_path'=>JPATH_ADMINISTRATOR.'/components/com_'.$f],null,$app,$app->getInput());
    $guard=new ReflectionMethod($controller,'guard');
    fails(fn()=>$guard->invoke($controller),'Invalid session token');
    $params=ComponentHelper::getParams('com_'.$f);$params->set('ai_enabled',1);$params->set('ai_images',1);
    $user=new NeuralNetworkTestUser();$user->guest=0;$user->id=999999;$user->grants=['ai.generate','core.create'];$app->loadIdentity($user);
    $app->getInput()->post->set(Joomla\CMS\Session\Session::getFormToken(),1);
    check($guard->invoke($controller)===$params,true,'authorized create');
    $user->grants=['core.create'];fails(fn()=>$guard->invoke($controller),'permission');
    $user->grants=['ai.generate','core.create'];$params->set('ai_images',0);fails(fn()=>$guard->invoke($controller,true),'permission');
    $params->set('ai_enabled',0);fails(fn()=>$guard->invoke($controller),'disabled');
    $app->getInput()->post->set(Joomla\CMS\Session\Session::getFormToken(),0);
    echo "$f: provider fixtures, secrets, HTML/media preservation, SEO, forms and access guards passed\n";
}
} finally {foreach($env as $key=>$value){putenv($value===false?$key:$key.'='.$value);}}

