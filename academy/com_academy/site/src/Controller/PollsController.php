<?php
namespace Joomla\Component\Academy\Site\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

final class PollsController extends BaseController
{
    public function vote(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app=Factory::getApplication();$identity=$app->getIdentity();$return=base64_decode($app->getInput()->post->getString('return'),true)?:'index.php';
        if($identity->guest&&!ComponentHelper::getParams('com_academy')->get('poll_guest_voting',1)){$app->enqueueMessage('Please sign in to vote.','warning');$app->redirect($return);return;}
        $pollId=$app->getInput()->post->getInt('poll_id');$choices=array_unique(array_map('intval',(array)$app->getInput()->post->get('choice',[],'array')));$db=Factory::getContainer()->get(DatabaseInterface::class);
        $poll=$db->setQuery($db->createQuery()->select('*')->from('#__academy_polls')->where('id='.$pollId)->where('state=1')->where('(publish_up IS NULL OR publish_up<=NOW())')->where('(publish_down IS NULL OR publish_down>=NOW())'))->loadObject();
        if(!$poll||!$choices){$app->enqueueMessage($poll?'Choose an option.':'This poll is not currently open.','warning');$app->redirect($return);return;}
        $voter=$identity->guest?hash('sha256',($_SERVER['REMOTE_ADDR']??'').'|'.($_SERVER['HTTP_USER_AGENT']??'')):'user:'.$identity->id;$exists=(int)$db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__academy_poll_votes')->where('poll_id='.$pollId)->where('voter_key='.$db->quote($voter)))->loadResult();
        if($exists){$app->enqueueMessage('You have already voted.','warning');$app->redirect($return);return;}
        if(!$poll->multiple)$choices=[reset($choices)];$recorded=0;foreach($choices as $choice){$valid=(int)$db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__academy_poll_options')->where('id='.$choice)->where('poll_id='.$pollId))->loadResult();if($valid){$vote=(object)['poll_id'=>$pollId,'option_id'=>$choice,'voter_key'=>$voter,'created'=>Factory::getDate()->toSql()];$db->insertObject('#__academy_poll_votes',$vote);$recorded++;}}
        $app->enqueueMessage($recorded?'Your vote was recorded.':'No valid choice was selected.',$recorded?'message':'warning');$app->redirect($return);
    }
}
