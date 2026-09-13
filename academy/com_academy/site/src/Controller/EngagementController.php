<?php

namespace Joomla\Component\Academy\Site\Controller;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

final class EngagementController extends BaseController
{
    private function back(): string
    {
        return base64_decode(Factory::getApplication()->getInput()->post->getString('return'), true) ?: 'index.php';
    }
    public function rate(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        $id = $app->getInput()->post->getInt('post_id');
        $value = max(1, min(5, $app->getInput()->post->getInt('rating')));
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $identity = $app->getIdentity();
        $key = $identity->guest ? hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '').'|'.($_SERVER['HTTP_USER_AGENT'] ?? '')) : 'user:'.$identity->id;
        $exists = (int)$db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__academy_rating_votes')->where('post_id='.$id)->where('voter_key='.$db->quote($key)))->loadResult();
        if (!$exists) {
            $vote = (object)['post_id' => $id,'rating' => $value,'voter_key' => $key,'created' => Factory::getDate()->toSql()];
            $db->insertObject('#__academy_rating_votes', $vote);
            $row = $db->setQuery($db->createQuery()->select(['SUM(rating) AS rating_sum','COUNT(*) AS rating_count'])->from('#__academy_rating_votes')->where('post_id='.$id))->loadObject();
            $aggregateExists = (int)$db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__academy_rating')->where('content_id='.$id))->loadResult();
            if ($aggregateExists) {
                $db->setQuery($db->createQuery()->update('#__academy_rating')->set(['rating_sum='.(int)$row->rating_sum,'rating_count='.(int)$row->rating_count])->where('content_id='.$id))->execute();
            } else {
                $aggregate = (object)['content_id' => $id,'rating_sum' => (int)$row->rating_sum,'rating_count' => (int)$row->rating_count,'lastip' => ''];
                $db->insertObject('#__academy_rating', $aggregate);
            }$app->enqueueMessage('Thank you for rating this post.');
        } else {
            $app->enqueueMessage('You have already rated this post.', 'warning');
        }$app->redirect($this->back());
    }
    public function subscribe(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        $name = trim($app->getInput()->post->getString('name'));
        $email = strtolower(trim($app->getInput()->post->getString('email')));
        if (!$app->getInput()->post->getInt('consent') || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('A valid email and consent are required.', 400);
        }$db = Factory::getContainer()->get(DatabaseInterface::class);
        $existing = $db->setQuery($db->createQuery()->select(['id','state'])->from('#__academy_subscribers')->where('email='.$db->quote($email)))->loadObject();
        if ($existing && (int)$existing->state === 1) {
            $app->enqueueMessage('This email address is already subscribed.', 'info');
            $app->redirect($this->back());
            return;
        }$now = Factory::getDate()->toSql();
        $confirmToken = bin2hex(random_bytes(24));
        $ip = substr((string)($app->input->server->getString('REMOTE_ADDR') ?: ''), 0, 45);
        if ($existing) {
            $db->setQuery($db->createQuery()->update('#__academy_subscribers')->set(['name='.$db->quote($name),'state=0','confirm_token='.$db->quote($confirmToken),'consented='.$db->quote($now),'confirmed=NULL','unsubscribed=NULL','consent_ip='.$db->quote($ip)])->where('id='.(int)$existing->id))->execute();
        } else {
            $subscriber = (object)['name' => $name,'email' => $email,'state' => 0,'token' => bin2hex(random_bytes(24)),'confirm_token' => $confirmToken,'created' => $now,'consented' => $now,'confirmed' => null,'unsubscribed' => null,'consent_ip' => $ip];
            $db->insertObject('#__academy_subscribers', $subscriber);
        }$confirmUrl = Uri::root().'index.php?option=com_academy&task=engagement.confirm&token='.rawurlencode($confirmToken);
        $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
        $mailer->addRecipient($email, $name);
        $mailer->setSubject('Confirm your '.ucfirst('academy').' subscription');
        $mailer->isHtml(true);
        $mailer->setBody('<p>Hello '.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').',</p><p>Please confirm that you want to receive '.htmlspecialchars(ucfirst('academy'), ENT_QUOTES, 'UTF-8').' updates.</p><p><a href="'.htmlspecialchars($confirmUrl, ENT_QUOTES, 'UTF-8').'">Confirm subscription</a></p><p>If you did not request this, you can ignore this email.</p>');
        $mailer->send();
        $app->enqueueMessage('Please check your email and confirm your subscription.');
        $app->redirect($this->back());
    }
    public function confirm(): void
    {
        $app = Factory::getApplication();
        $token = $app->getInput()->getString('token');
        $confirmed = false;
        if (preg_match('/^[a-f0-9]{48}$/', $token)) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $subscriber = $db->setQuery($db->createQuery()->select(['id','email'])->from('#__academy_subscribers')->where('confirm_token='.$db->quote($token)))->loadObject();
            if ($subscriber) {
                $db->setQuery($db->createQuery()->update('#__academy_subscribers')->set(['state=1','confirmed='.$db->quote(Factory::getDate()->toSql()),'unsubscribed=NULL','confirm_token='.$db->quote('')])->where('id='.(int)$subscriber->id))->execute();
                $db->setQuery($db->createQuery()->delete('#__academy_subscriber_suppressions')->where('email='.$db->quote($subscriber->email)))->execute();
                $confirmed = true;
            }
        }$app->enqueueMessage($confirmed ? 'Your subscription is confirmed.' : 'This confirmation link is invalid or has expired.', $confirmed ? 'message' : 'warning');
        $app->redirect('index.php');
    }
    public function unsubscribe(): void
    {
        $app = Factory::getApplication();
        $token = $app->getInput()->getString('token');
        if (preg_match('/^[a-f0-9]{48}$/', $token)) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $email = (string)$db->setQuery($db->createQuery()->select('email')->from('#__academy_subscribers')->where('token='.$db->quote($token)))->loadResult();
            if ($email !== '') {
                $db->setQuery($db->createQuery()->update('#__academy_subscribers')->set(['state=0','unsubscribed='.$db->quote(Factory::getDate()->toSql())])->where('token='.$db->quote($token)))->execute();
                $exists = (int)$db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__academy_subscriber_suppressions')->where('email='.$db->quote($email)))->loadResult();
                if (!$exists) {
                    $suppression = (object)['email' => $email,'reason' => 'unsubscribed','created' => Factory::getDate()->toSql()];
                    $db->insertObject('#__academy_subscriber_suppressions', $suppression);
                }
            }
        }$app->enqueueMessage('You have been unsubscribed.');
        $app->redirect('index.php');
    }
    public function open(): void
    {
        $app = Factory::getApplication();
        $token = $app->getInput()->getString('token');
        if (preg_match('/^[a-f0-9]{48}$/', $token)) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery($db->createQuery()->update('#__academy_mail_queue')->set(['opened_count=opened_count+1','last_opened='.$db->quote(Factory::getDate()->toSql())])->where('tracking_token='.$db->quote($token))->where('state=1'))->execute();
        }$app->setHeader('Content-Type', 'image/gif', true);
        $app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $app->sendHeaders();
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
        $app->close();
    }
    public function click(): void
    {
        $app = Factory::getApplication();
        $token = $app->getInput()->getString('token');
        $encoded = $app->getInput()->getString('url');
        $signature = $app->getInput()->getString('sig');
        $expected = hash_hmac('sha256', $encoded.'|'.$token, (string)$app->get('secret'));
        $url = base64_decode(strtr($encoded, '-_', '+/'), true);
        if (!preg_match('/^[a-f0-9]{48}$/', $token) || !hash_equals($expected, $signature) || !is_string($url) || !filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http','https'], true)) {
            throw new \RuntimeException('Invalid tracking link.', 400);
        }$db = Factory::getContainer()->get(DatabaseInterface::class);
        $exists = (int)$db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__academy_mail_queue')->where('tracking_token='.$db->quote($token))->where('state=1'))->loadResult();
        if (!$exists) {
            throw new \RuntimeException('Tracking link not found.', 404);
        }$db->setQuery($db->createQuery()->update('#__academy_mail_queue')->set(['clicked_count=clicked_count+1','last_clicked='.$db->quote(Factory::getDate()->toSql())])->where('tracking_token='.$db->quote($token)))->execute();
        $app->redirect($url);
    }
}
