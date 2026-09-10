<?php
namespace Joomla\Plugin\Task\AcademyMailqueue\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Task\AcademyMailqueue\Service\CampaignMailer;

final class MailQueue extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    protected const TASKS_MAP = ['academy.mailqueue' => ['langConstPrefix' => 'PLG_TASK_ACADEMY_MAILQUEUE', 'form' => 'mail_queue', 'method' => 'processQueue']];
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return ['onTaskOptionsList' => 'advertiseRoutines', 'onExecuteTask' => 'standardRoutineHandler', 'onContentPrepareForm' => 'enhanceTaskItemForm'];
    }

    protected function processQueue(ExecuteTaskEvent $event): int
    {
        $limit = max(1, min(200, (int) ($event->getArgument('params')->batch_size ?? 25)));
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery($db->createQuery()->update('#__academy_campaigns')->set('state=0')->where('state=2')->where('scheduled_at IS NOT NULL')->where('scheduled_at <= NOW()'))->execute();
        $db->setQuery($db->createQuery()->update('#__academy_mail_queue AS q')->set(['q.state=2', 'q.error=' . $db->quote('Subscriber is no longer active.')])->where('q.state=0')->where('NOT EXISTS (SELECT 1 FROM #__academy_subscribers AS s WHERE s.id=q.subscriber_id AND s.state=1)'))->execute();
        $rows = $db->setQuery($db->createQuery()->select(['q.*', 'c.subject', 'c.body'])->from('#__academy_mail_queue AS q')->join('INNER', '#__academy_campaigns AS c ON c.id=q.campaign_id')->join('INNER', '#__academy_subscribers AS s ON s.id=q.subscriber_id AND s.state=1')->where('q.state=0')->where('c.state=0')->order('q.id'), 0, $limit)->loadObjectList();
        $sent = 0;
        $failed = 0;
        $transport = new CampaignMailer();
        foreach ($rows as $row) {
            try {
                if ($row->tracking_token === '') {
                    $row->tracking_token = bin2hex(random_bytes(24));
                    $db->setQuery($db->createQuery()->update('#__academy_mail_queue')->set('tracking_token=' . $db->quote($row->tracking_token))->where('id=' . (int) $row->id))->execute();
                }
                $unsubscribe = Uri::root() . 'index.php?option=com_academy&task=engagement.unsubscribe&token=' . rawurlencode($row->token);
                $body = str_replace(['{name}', '{email}', '{unsubscribe_url}'], [$row->name, $row->email, $unsubscribe], $row->body);
                $body = $this->addTracking($body, $row->tracking_token);
                $transport->send($row->email, $row->name, $row->subject, $body . '<p><a href="' . htmlspecialchars($unsubscribe, ENT_QUOTES, 'UTF-8') . '">Unsubscribe</a></p>');
                $state = 1;
                $error = '';
                $sent++;
            } catch (\Throwable $e) {
                $state = -1;
                $error = substr($e->getMessage(), 0, 1000);
                $failed++;
            }
            $db->setQuery($db->createQuery()->update('#__academy_mail_queue')->set(['state=' . $state, 'attempts=attempts+1', 'error=' . $db->quote($error)])->where('id=' . (int) $row->id))->execute();
        }
        $campaignIds = $db->setQuery($db->createQuery()->select('id')->from('#__academy_campaigns')->where('state=0'))->loadColumn();
        foreach ($campaignIds as $campaignId) {
            $counts = $db->setQuery($db->createQuery()->select(['COALESCE(SUM(state=0),0) AS pending', 'COALESCE(SUM(state=-1),0) AS failed'])->from('#__academy_mail_queue')->where('campaign_id=' . (int) $campaignId))->loadObject();
            $campaignState = (int) $counts->pending ? 0 : ((int) $counts->failed ? -1 : 1);
            $db->setQuery($db->createQuery()->update('#__academy_campaigns')->set('state=' . $campaignState)->where('id=' . (int) $campaignId))->execute();
        }
        if (!$rows) {
            $this->logTask('No Academy messages are queued.');
            return TaskStatus::NO_RUN;
        }
        $this->logTask("Academy mail queue: sent $sent; failed $failed.", $failed ? 'warning' : 'info');
        return $failed && !$sent ? TaskStatus::KNOCKOUT : TaskStatus::OK;
    }

    private function addTracking(string $body, string $token): string
    {
        $params = ComponentHelper::getParams('com_academy');
        if ($params->get('email_link_tracking', 0)) {
            $secret = (string) Factory::getApplication()->get('secret');
            $body = preg_replace_callback('#href=(["\'])(https?://[^"\']+)\1#i', static function (array $match) use ($secret, $token): string {
                $encoded = rtrim(strtr(base64_encode(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')), '+/', '-_'), '=');
                $signature = hash_hmac('sha256', $encoded . '|' . $token, $secret);
                $url = Uri::root() . 'index.php?option=com_academy&task=engagement.click&token=' . rawurlencode($token) . '&url=' . rawurlencode($encoded) . '&sig=' . $signature;
                return 'href=' . $match[1] . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . $match[1];
            }, $body);
        }
        if ($params->get('email_open_tracking', 0)) {
            $pixel = Uri::root() . 'index.php?option=com_academy&task=engagement.open&token=' . rawurlencode($token);
            $body .= '<img src="' . htmlspecialchars($pixel, ENT_QUOTES, 'UTF-8') . '" alt="" width="1" height="1" style="display:none">';
        }
        return $body;
    }
}
