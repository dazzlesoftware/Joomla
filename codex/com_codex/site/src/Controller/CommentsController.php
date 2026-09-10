<?php
namespace Joomla\Component\Codex\Site\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

final class CommentsController extends BaseController
{
 public function submit(): void
 {
  Session::checkToken() or jexit('Invalid token'); $app=Factory::getApplication(); $input=$app->getInput();
  $postId=$input->post->getInt('post_id'); $body=trim($input->post->getString('body')); $user=$app->getIdentity();
  $started=$input->post->getInt('form_started');if($input->post->getString('website')!==''||!$started||$started>time()-2){$app->enqueueMessage('Comment could not be submitted.','warning');$app->redirect(base64_decode($input->post->getString('return'),true)?:'index.php');return;}
  $name=$user->guest?trim($input->post->getString('name')):$user->name; $email=$user->guest?trim($input->post->getString('email')):$user->email;
  $return=base64_decode($input->post->getString('return'), true) ?: Route::_('index.php?option=com_codex&view=post&id='.$postId, false);
  if(!$postId || $body==='' || $name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL)){ $app->enqueueMessage('Please provide your name, valid email, and comment.','error');$app->redirect($return);return; }
  $db=Factory::getContainer()->get(DatabaseInterface::class); $valid=(int)$db->setQuery($db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__codex'))->where('id='.(int)$postId))->loadResult();
  if(!$valid){throw new \RuntimeException('Post not found',404);}
  $params=ComponentHelper::getParams('com_codex');$state=$params->get('comments_moderation',1)?0:1;$ipHash=hash('sha256',($_SERVER['REMOTE_ADDR']??'').'codex');$cooldown=max(0,min(3600,(int)$params->get('comments_cooldown',60)));if($cooldown){$since=Factory::getDate('-'.$cooldown.' seconds')->toSql();$recent=(int)$db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__codex_comments')->where('ip_hash='.$db->quote($ipHash))->where('created>='.$db->quote($since)))->loadResult();if($recent){$app->enqueueMessage('Please wait before posting another comment.','warning');$app->redirect($return);return;}}
  $row=(object)['post_id'=>$postId,'parent_id'=>0,'user_id'=>(int)$user->id,'name'=>mb_substr($name,0,255),'email'=>mb_substr($email,0,255),'body'=>mb_substr(strip_tags($body),0,10000),'state'=>$state,'created'=>Factory::getDate()->toSql(),'ip_hash'=>$ipHash];
  $db->insertObject('#__codex_comments',$row);if($params->get('comments_notify',1)&&$app->get('mailfrom')){try{$mailer=Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();$mailer->addRecipient($app->get('mailfrom'));$mailer->setSubject('New '.ucfirst('codex').' comment awaiting review');$mailer->setBody("Post ID: $postId\nName: $name\nEmail: $email\n\n$body");$mailer->send();}catch(\Throwable $e){}}$app->enqueueMessage($state?'Comment published.':'Comment submitted for approval.');$app->redirect($return);
 }
}
