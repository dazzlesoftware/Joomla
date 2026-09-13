<?php

namespace Joomla\Component\Blog\Administrator\Controller;

defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

final class AutopostController extends BaseController
{
    private const PROVIDERS = ['facebook','twitter','linkedin'];
    public function save(): void
    {
        Session::checkToken('post') or jexit('Invalid token');
        $this->auth();
        $p = $this->provider();
        $app = Factory::getApplication();
        $params = clone ComponentHelper::getParams('com_blog');
        foreach ($app->getInput()->post->get('jform', [], 'array') as $k => $v) {
            if (str_starts_with((string)$k, 'autopost_'.$p.'_')) {
                $params->set((string)$k, $v);
            }
        }
        if ($p === 'facebook') {
            foreach ($this->list($params, 'facebook') as $a) {
                if ((string)($a['id'] ?? '') === (string)$params->get('autopost_facebook_page_id')) {
                    $params->set('autopost_facebook_token', (string)($a['token'] ?? ''));
                    $params->set('autopost_facebook_account_name', (string)($a['name'] ?? ''));
                    break;
                }
            }
        }
        $this->store($params);
        $app->enqueueMessage('Autopost settings saved.');
        $this->setRedirect($this->settings($p));
    }
    public function connect(): void
    {
        Session::checkToken('request') or jexit('Invalid token');
        $this->auth();
        $p = $this->provider();
        $params = ComponentHelper::getParams('com_blog');
        $state = bin2hex(random_bytes(24));
        $session = Factory::getApplication()->getSession();
        $session->set('blog.oauth.'.$p.'.state', $state);
        $redirect = $this->callback($p);
        if ($p === 'facebook') {
            $client = trim((string)$params->get('autopost_facebook_app_id'));
            $this->need($client, 'Save the Facebook Application ID and Secret first.');
            $version = (string)$params->get('autopost_facebook_version', 'v23.0');
            $url = 'https://www.facebook.com/'.rawurlencode($version).'/dialog/oauth?'.http_build_query(['client_id' => $client,'redirect_uri' => $redirect,'state' => $state,'scope' => 'pages_show_list,pages_read_engagement,pages_manage_posts','response_type' => 'code']);
        } elseif ($p === 'twitter') {
            $client = trim((string)$params->get('autopost_twitter_client_id'));
            $this->need($client, 'Save the X Client ID first.');
            $verifier = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
            $session->set('blog.oauth.twitter.verifier', $verifier);
            $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
            $url = 'https://x.com/i/oauth2/authorize?'.http_build_query(['response_type' => 'code','client_id' => $client,'redirect_uri' => $redirect,'scope' => 'tweet.read tweet.write users.read offline.access','state' => $state,'code_challenge' => $challenge,'code_challenge_method' => 'S256']);
        } else {
            $client = trim((string)$params->get('autopost_linkedin_client_id'));
            $this->need($client, 'Save the LinkedIn Client ID and Secret first.');
            $url = 'https://www.linkedin.com/oauth/v2/authorization?'.http_build_query(['response_type' => 'code','client_id' => $client,'redirect_uri' => $redirect,'state' => $state,'scope' => 'openid profile w_member_social']);
        }
        Factory::getApplication()->redirect($url);
    }
    public function oauthCallback(): void
    {
        $this->auth();
        $app = Factory::getApplication();
        $p = $this->provider();
        $session = $app->getSession();
        $expected = (string)$session->get('blog.oauth.'.$p.'.state', '');
        $session->clear('blog.oauth.'.$p.'.state');
        $state = $app->getInput()->getString('state');
        if ($expected === '' || !hash_equals($expected, $state)) {
            throw new \RuntimeException('The social login expired or was invalid. Please connect again.', 400);
        }
        if ($app->getInput()->getString('error') !== '') {
            throw new \RuntimeException('Access was not granted: '.$app->getInput()->getString('error_description', $app->getInput()->getString('error')), 400);
        }
        $code = $app->getInput()->getString('code');
        $this->need($code, 'No authorization code was returned.');
        $params = clone ComponentHelper::getParams('com_blog');
        if ($p === 'facebook') {
            $this->facebook($params, $code);
        } elseif ($p === 'twitter') {
            $this->twitter($params, $code);
        } else {
            $this->linkedin($params, $code);
        }
        $this->store($params);
        $app->enqueueMessage(($p === 'twitter' ? 'X' : ucfirst($p)).' account connected.');
        $app->redirect($this->settings($p));
    }
    public function disconnect(): void
    {
        Session::checkToken('request') or jexit('Invalid token');
        $this->auth();
        $p = $this->provider();
        $params = clone ComponentHelper::getParams('com_blog');
        foreach (['token','refresh_token','account_id','account_name','accounts','page_id','author'] as $k) {
            $params->set('autopost_'.$p.'_'.$k, '');
        }$params->set('autopost_'.$p.'_enabled', 0);
        $this->store($params);
        Factory::getApplication()->enqueueMessage('Connected account removed.');
        $this->setRedirect($this->settings($p));
    }
    public function purge(): void
    {
        Session::checkToken('request') or jexit('Invalid token');
        $this->auth('core.delete');
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery($db->createQuery()->delete('#__blog_autopost_logs'))->execute();
        Factory::getApplication()->enqueueMessage('Autopost logs purged.');
        $this->setRedirect(Route::_('index.php?option=com_blog&view=autopostlogs', false));
    }
    private function facebook($params, string $code): void
    {
        $v = (string)$params->get('autopost_facebook_version', 'v23.0');
        $id = trim((string)$params->get('autopost_facebook_app_id'));
        $secret = trim((string)$params->get('autopost_facebook_app_secret'));
        $this->need($id && $secret ? 'yes' : '', 'Facebook credentials are incomplete.');
        $token = (string)($this->json(HttpFactory::getHttp()->get('https://graph.facebook.com/'.rawurlencode($v).'/oauth/access_token?'.http_build_query(['client_id' => $id,'client_secret' => $secret,'redirect_uri' => $this->callback('facebook'),'code' => $code])))['access_token'] ?? '');
        $data = $this->json(HttpFactory::getHttp()->get('https://graph.facebook.com/'.rawurlencode($v).'/me/accounts?'.http_build_query(['fields' => 'id,name,access_token','limit' => 100,'access_token' => $token])))['data'] ?? [];
        $list = [];
        foreach ($data as $a) {
            if (!empty($a['id']) && !empty($a['access_token'])) {
                $list[] = ['id' => (string)$a['id'],'name' => (string)($a['name'] ?? $a['id']),'token' => (string)$a['access_token']];
            }
        }
        if (!$list) {
            throw new \RuntimeException('Facebook returned no manageable Pages. Check the app permissions and your Page role.');
        }$params->set('autopost_facebook_accounts', json_encode($list));
        $params->set('autopost_facebook_page_id', $list[0]['id']);
        $params->set('autopost_facebook_token', $list[0]['token']);
        $params->set('autopost_facebook_account_name', $list[0]['name']);
    }
    private function twitter($params, string $code): void
    {
        $id = trim((string)$params->get('autopost_twitter_client_id'));
        $secret = trim((string)$params->get('autopost_twitter_client_secret'));
        $session = Factory::getApplication()->getSession();
        $verifier = (string)$session->get('blog.oauth.twitter.verifier', '');
        $session->clear('blog.oauth.twitter.verifier');
        $this->need($verifier, 'The X login verifier expired. Please connect again.');
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        if ($secret !== '') {
            $headers['Authorization'] = 'Basic '.base64_encode($id.':'.$secret);
        }
        $result = $this->json(HttpFactory::getHttp()->post('https://api.x.com/2/oauth2/token', http_build_query(['code' => $code,'grant_type' => 'authorization_code','client_id' => $id,'redirect_uri' => $this->callback('twitter'),'code_verifier' => $verifier]), $headers));
        $token = (string)($result['access_token'] ?? '');
        $me = $this->json(HttpFactory::getHttp()->get('https://api.x.com/2/users/me', ['Authorization' => 'Bearer '.$token]))['data'] ?? [];
        $params->set('autopost_twitter_token', $token);
        $params->set('autopost_twitter_refresh_token', (string)($result['refresh_token'] ?? ''));
        $params->set('autopost_twitter_account_id', (string)($me['id'] ?? ''));
        $params->set('autopost_twitter_account_name', (string)($me['username'] ?? $me['name'] ?? 'X account'));
    }
    private function linkedin($params, string $code): void
    {
        $id = trim((string)$params->get('autopost_linkedin_client_id'));
        $secret = trim((string)$params->get('autopost_linkedin_client_secret'));
        $result = $this->json(HttpFactory::getHttp()->post('https://www.linkedin.com/oauth/v2/accessToken', http_build_query(['grant_type' => 'authorization_code','code' => $code,'redirect_uri' => $this->callback('linkedin'),'client_id' => $id,'client_secret' => $secret]), ['Content-Type' => 'application/x-www-form-urlencoded']));
        $token = (string)($result['access_token'] ?? '');
        $me = $this->json(HttpFactory::getHttp()->get('https://api.linkedin.com/v2/userinfo', ['Authorization' => 'Bearer '.$token]));
        $this->need((string)($me['sub'] ?? ''), 'LinkedIn did not return a member identity.');
        $author = 'urn:li:person:'.$me['sub'];
        $name = (string)($me['name'] ?? 'LinkedIn member');
        $params->set('autopost_linkedin_token', $token);
        $params->set('autopost_linkedin_author', $author);
        $params->set('autopost_linkedin_account_name', $name);
        $params->set('autopost_linkedin_accounts', json_encode([['id' => $author,'name' => $name]]));
    }
    private function json($response): array
    {
        $body = (string)($response->body ?? '');
        $code = (int)($response->code ?? 0);
        $data = json_decode($body, true);
        if ($code < 200 || $code >= 300 || !is_array($data)) {
            throw new \RuntimeException('Social API request failed (HTTP '.$code.'): '.mb_substr($body, 0, 1200));
        }return $data;
    }
    private function callback(string $p): string
    {
        return Uri::root().'administrator/index.php?option=com_blog&task=autopost.oauthCallback&provider='.rawurlencode($p);
    }
    private function settings(string $p): string
    {
        return Route::_('index.php?option=com_blog&view=autopost&provider='.$p, false);
    }
    private function provider(): string
    {
        $p = Factory::getApplication()->getInput()->getCmd('provider');
        if (!in_array($p, self::PROVIDERS, true)) {
            throw new \RuntimeException('Invalid provider.', 400);
        }return $p;
    }
    private function auth(string $action = 'core.options'): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise($action, 'com_blog')) {
            throw new \RuntimeException('Not authorised.', 403);
        }
    }
    private function need(string $value, string $message): void
    {
        if ($value === '') {
            throw new \RuntimeException($message, 400);
        }
    }
    private function store($params): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $json = $params->toString();
        $q = $db->createQuery()->update('#__extensions')->set('params=:params')->where("type='component'")->where("element='com_blog'")->bind(':params', $json);
        $db->setQuery($q)->execute();
    }
    private function list($params,string $p): array
    {
        $v = json_decode((string)$params->get('autopost_'.$p.'_accounts','[]'),true);
        return is_array($v) ? $v : [];
    }
}
