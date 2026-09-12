<?php
namespace Joomla\Component\Blog\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

final class AutopostService
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Factory::getContainer()->get(DatabaseInterface::class);
    }

    public function publish(int $postId, string $event): void
    {
        if (!in_array($event, ['new', 'update'], true)) {
            return;
        }

        $post = $this->db->setQuery(
            $this->db->createQuery()
                ->select(['a.id', 'a.title', 'a.alias', 'a.catid', 'a.summary', 'a.state', 'a.language', 'c.title AS category_title', 'c.allow_autoposting'])
                ->from('#__blog AS a')
                ->join('LEFT', '#__blog_categories AS c ON c.id = a.catid')
                ->where('a.id=' . $postId)
        )->loadObject();

        if (!$post || (int) $post->state !== 1) {
            return;
        }

        // The post's category can opt out of autoposting entirely.
        if ($post->allow_autoposting !== null && (int) $post->allow_autoposting === 0) {
            return;
        }

        $params = ComponentHelper::getParams('com_blog');
        $link = Uri::root() . 'index.php?option=com_blog&view=post&id=' . $post->id . ':' . $post->alias;
        $tokens = [
            '{title}' => (string) $post->title,
            '{summary}' => trim(strip_tags((string) $post->summary)),
            '{category}' => (string) ($post->category_title ?? ''),
            '{link}' => $link,
        ];

        foreach (['facebook', 'twitter', 'linkedin'] as $provider) {
            if (!(int) $params->get('autopost_' . $provider . '_enabled', 0)
                || !(int) $params->get('autopost_' . $provider . '_' . $event, $event === 'new' ? 1 : 0)) {
                continue;
            }

            $template = (string) $params->get('autopost_' . $provider . '_message', '{title} {link}');
            $message = strtr($template, $tokens);

            try {
                $result = $this->{'send' . ucfirst($provider)}($params, $message, $link);
                $this->log($postId, $provider, $event, 'success', $result['id'], $message, $result['response']);
            } catch (\Throwable $error) {
                $this->log($postId, $provider, $event, 'failed', '', $message, $error->getMessage());
            }
        }
    }

    private function sendFacebook($params, string $message, string $link): array
    {
        $page = trim((string) $params->get('autopost_facebook_page_id'));
        $token = trim((string) $params->get('autopost_facebook_token'));
        $version = trim((string) $params->get('autopost_facebook_version', 'v23.0'));
        if ($page === '' || $token === '') {
            throw new \RuntimeException('Facebook Page ID or Page access token is missing.');
        }
        $response = HttpFactory::getHttp()->post('https://graph.facebook.com/' . rawurlencode($version) . '/' . rawurlencode($page) . '/feed', [
            'message' => $message, 'link' => $link, 'access_token' => $token,
        ], [], 15);
        return $this->decode($response, 'id');
    }

    private function sendTwitter($params, string $message): array
    {
        $token = trim((string) $params->get('autopost_twitter_token'));
        if ($token === '') {
            throw new \RuntimeException('X access token is missing.');
        }
        $response = HttpFactory::getHttp()->post('https://api.x.com/2/tweets', json_encode(['text' => $message], JSON_UNESCAPED_SLASHES), [
            'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json',
        ], 15);
        $result = $this->decode($response);
        $json = json_decode($result['response'], true);
        $result['id'] = (string) ($json['data']['id'] ?? '');
        return $result;
    }

    private function sendLinkedin($params, string $message): array
    {
        $token = trim((string) $params->get('autopost_linkedin_token'));
        $author = trim((string) $params->get('autopost_linkedin_author'));
        $version = trim((string) $params->get('autopost_linkedin_version', '202601'));
        if ($token === '' || $author === '') {
            throw new \RuntimeException('LinkedIn access token or author URN is missing.');
        }
        $payload = ['author' => $author, 'commentary' => $message, 'visibility' => 'PUBLIC', 'distribution' => ['feedDistribution' => 'MAIN_FEED', 'targetEntities' => [], 'thirdPartyDistributionChannels' => []], 'lifecycleState' => 'PUBLISHED', 'isReshareDisabledByAuthor' => false];
        $response = HttpFactory::getHttp()->post('https://api.linkedin.com/rest/posts', json_encode($payload, JSON_UNESCAPED_SLASHES), [
            'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json', 'X-Restli-Protocol-Version' => '2.0.0', 'Linkedin-Version' => $version,
        ], 15);
        $result = $this->decode($response);
        $result['id'] = (string) ($response->headers['x-restli-id'] ?? $response->headers['X-RestLi-Id'] ?? '');
        return $result;
    }

    private function decode($response, string $idKey = ''): array
    {
        $body = (string) ($response->body ?? '');
        $code = (int) ($response->code ?? 0);
        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException('HTTP ' . $code . ': ' . mb_substr($body, 0, 2000));
        }
        $json = json_decode($body, true);
        return ['id' => $idKey ? (string) ($json[$idKey] ?? '') : '', 'response' => mb_substr($body, 0, 4000)];
    }

    private function log(int $postId, string $provider, string $event, string $status, string $remoteId, string $message, string $response): void
    {
        $row = (object) ['post_id' => $postId, 'provider' => $provider, 'event' => $event, 'status' => $status, 'remote_id' => $remoteId, 'message' => $message, 'response' => mb_substr($response, 0, 8000), 'created' => Factory::getDate()->toSql()];
        $this->db->insertObject('#__blog_autopost_logs', $row);
    }
}
