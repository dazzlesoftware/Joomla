<?php
if (PHP_SAPI !== 'cli') { exit(1); }
$argv=['','C:/wamp64/www/Joomla'];
require __DIR__.'/test-subcategory-styles.php';
$db=Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
foreach (['academy','blog','codex'] as $family) {
 require_once __DIR__.'/../'.$family.'/plugins/blocks/src/Extension/Blocks.php';
 $class='Joomla\\Plugin\\'.ucfirst($family).'\\Blocks\\Extension\\Blocks';
 $dispatcher=new Joomla\Event\Dispatcher();
 $plugin=new $class($dispatcher,['name'=>'blocks','type'=>$family]);
 $plugin->setApplication($app);
 $render=function($text) use($plugin,$family) {
  $item=(object)['text'=>$text];
  $plugin->onContentPrepare(new Joomla\CMS\Event\Content\ContentPrepareEvent('onContentPrepare',['context'=>'com_'.$family.'.post','subject'=>$item,'params'=>new Joomla\Registry\Registry(),'page'=>0]));
  return $item->text;
 };
 $cases=[
  ['{quote template="framed" quote_color_mode="custom" quote_custom_color="#123456"}Text{/quote}','#123456'],
  ['{tabs template="pills" tab_mode="vertical" tab_bootstrap_color="danger"}{tab title="One"}Text{/tab}{/tabs}','flex-column'],
  ['{accordion template="color-panel" accordion_bootstrap_color="success"}{item title="One"}Text{/item}{/accordion}','var(--bs-success'],
  ['{columns template="equal" column_gap="5" column_vertical_align="end"}{column}A{/column}{column}B{/column}{/columns}','g-5'],
  ['{quote template="global"}Old text{/quote}','post-quote--simple'],
 ];
 foreach($cases as [$input,$expected]) {
  foreach([$input,htmlspecialchars($input,ENT_QUOTES,'UTF-8')] as $text) {
   if(!str_contains($render($text),$expected)) { throw new RuntimeException($family.': '.$expected); }
  }
 }
 $poll=(object)['title'=>'Temporary block regression','state'=>1,'multiple'=>0,'created'=>date('Y-m-d H:i:s')];
 $db->insertObject('#__'.$family.'_polls',$poll,'id');
 try {
  $option=(object)['poll_id'=>$poll->id,'title'=>'Yes','ordering'=>0];$db->insertObject('#__'.$family.'_poll_options',$option,'id');
  $vote=(object)['poll_id'=>$poll->id,'option_id'=>$option->id,'voter_key'=>hash('sha256',($_SERVER['REMOTE_ADDR']??'').'|'.($_SERVER['HTTP_USER_AGENT']??'')),'created'=>date('Y-m-d H:i:s')];$db->insertObject('#__'.$family.'_poll_votes',$vote);
  foreach([0,1] as $enabled) {
   $html=$render('{embed provider="polls" url="'.$poll->id.'" template="progress" poll_progress_labels="'.$enabled.'" poll_progress_striped="'.$enabled.'" poll_progress_color_mode="custom" poll_progress_custom_color="#123456"}');
   if(str_contains($html,'progress-bar-striped')!==(bool)$enabled || !str_contains($html,'background-color:#123456')) { throw new RuntimeException('Poll appearance'); }
   if(str_contains($html,'<strong>100%</strong>')===(bool)$enabled) { throw new RuntimeException('Poll percentage labels'); }
  }
 } finally {
  foreach(['poll_votes','poll_options'] as $table) {$db->setQuery('DELETE FROM #__'.$family.'_'.$table.' WHERE poll_id='.(int)$poll->id)->execute();}
  $db->setQuery('DELETE FROM #__'.$family.'_polls WHERE id='.(int)$poll->id)->execute();
 }
 echo "$family: plain/encoded blocks, legacy defaults and poll appearance passed.\n";
}
