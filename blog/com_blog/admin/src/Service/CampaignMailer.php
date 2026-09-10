<?php
namespace Joomla\Component\Blog\Administrator\Service;
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Mail\MailerFactoryInterface;
class CampaignMailer
{
    public function send(string $email,string $name,string $subject,string $html):void
    {
        $params=ComponentHelper::getParams('com_blog');$service=(string)$params->get('campaign_mail_service','joomla');
        $fromEmail=trim((string)$params->get('campaign_from_email',Factory::getApplication()->get('mailfrom')));$fromName=trim((string)$params->get('campaign_from_name',Factory::getApplication()->get('fromname')));
        if(!filter_var($fromEmail,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('The campaign sender email is invalid.');
        if(in_array($service,['joomla','mail','smtp'],true)){$this->sendMailer($service,$params,$fromEmail,$fromName,$email,$name,$subject,$html);return;}
        if($service==='mailgun'){$this->mailgun($params,$fromEmail,$fromName,$email,$name,$subject,$html);return;}
        if($service==='sendgrid'){$this->sendgrid($params,$fromEmail,$fromName,$email,$name,$subject,$html);return;}
        if($service==='postmark'){$this->postmark($params,$fromEmail,$fromName,$email,$name,$subject,$html);return;}
        throw new \RuntimeException('Unknown campaign email service: '.$service);
    }
    private function sendMailer(string $service,$params,string $from,string $fromName,string $to,string $toName,string $subject,string $html):void
    {
        $mailer=Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
        if($service==='mail')$mailer->isMail();
        if($service==='smtp'){$host=trim((string)$params->get('campaign_smtp_host'));if($host==='')throw new \RuntimeException('SMTP host is required.');$user=(string)$params->get('campaign_smtp_user');$pass=(string)$params->get('campaign_smtp_password');$mailer->useSmtp($user!==''?true:null,$host,$user?:null,$pass?:null,(string)$params->get('campaign_smtp_security','tls'),(int)$params->get('campaign_smtp_port',587));}
        $mailer->setSender([$from,$fromName]);$reply=trim((string)$params->get('campaign_reply_to'));if($reply!=='')$mailer->addReplyTo($reply);$mailer->addRecipient($to,$toName);$mailer->setSubject($subject);$mailer->isHtml(true);$mailer->setBody($html);if(!$mailer->send())throw new \RuntimeException('The mail transport did not accept the message.');
    }
    private function mailgun($p,string $from,string $fromName,string $to,string $toName,string $subject,string $html):void
    {
        $key=trim((string)$p->get('campaign_mailgun_key'));$domain=trim((string)$p->get('campaign_mailgun_domain'));if($key===''||$domain==='')throw new \RuntimeException('Mailgun API key and domain are required.');$base=$p->get('campaign_mailgun_region','us')==='eu'?'https://api.eu.mailgun.net':'https://api.mailgun.net';$this->check(HttpFactory::getHttp()->post($base.'/v3/'.rawurlencode($domain).'/messages',['from'=>$this->address($from,$fromName),'to'=>$this->address($to,$toName),'subject'=>$subject,'html'=>$html,'text'=>trim(strip_tags($html))],['Authorization'=>'Basic '.base64_encode('api:'.$key)]),'Mailgun');
    }
    private function sendgrid($p,string $from,string $fromName,string $to,string $toName,string $subject,string $html):void
    {
        $key=trim((string)$p->get('campaign_sendgrid_key'));if($key==='')throw new \RuntimeException('SendGrid API key is required.');$data=['personalizations'=>[['to'=>[['email'=>$to,'name'=>$toName]]]],'from'=>['email'=>$from,'name'=>$fromName],'subject'=>$subject,'content'=>[['type'=>'text/html','value'=>$html]]];$this->check(HttpFactory::getHttp()->post('https://api.sendgrid.com/v3/mail/send',json_encode($data),['Authorization'=>'Bearer '.$key,'Content-Type'=>'application/json']),'SendGrid');
    }
    private function postmark($p,string $from,string $fromName,string $to,string $toName,string $subject,string $html):void
    {
        $key=trim((string)$p->get('campaign_postmark_key'));if($key==='')throw new \RuntimeException('Postmark server token is required.');$data=['From'=>$this->address($from,$fromName),'To'=>$this->address($to,$toName),'Subject'=>$subject,'HtmlBody'=>$html,'TextBody'=>trim(strip_tags($html)),'MessageStream'=>(string)$p->get('campaign_postmark_stream','outbound')];$this->check(HttpFactory::getHttp()->post('https://api.postmarkapp.com/email',json_encode($data),['X-Postmark-Server-Token'=>$key,'Content-Type'=>'application/json','Accept'=>'application/json']),'Postmark');
    }
    private function check($response,string $provider):void{$code=(int)($response->code??0);if($code<200||$code>=300)throw new \RuntimeException($provider.' returned HTTP '.$code.': '.mb_substr((string)($response->body??''),0,1000));}
    private function address(string $email,string $name):string{return $name!==''?$name.' <'.$email.'>':$email;}
}
