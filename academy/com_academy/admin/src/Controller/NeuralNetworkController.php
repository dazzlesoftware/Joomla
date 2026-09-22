<?php
namespace Joomla\Component\Academy\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Component\Academy\Administrator\Helper\NeuralNetworkService;

class NeuralNetworkController extends BaseController
{
    private function guard(bool $image = false): object
    {
        $token = Session::getFormToken();
        if (!$this->input->post->get($token, '', 'alnum') && !hash_equals($token, $this->input->server->getString('HTTP_X_CSRF_TOKEN', ''))) { throw new \RuntimeException('Invalid session token.', 403); }
        $app = Factory::getApplication(); $user = $app->getIdentity();
        $params = ComponentHelper::getParams('com_academy');
        if ($user->guest || !$params->get('ai_enabled', 0) || !$user->authorise('ai.generate', 'com_academy')) { throw new \RuntimeException('AI tools are disabled or you do not have permission to use them.', 403); }
        $id = $this->input->post->getInt('post_id');
        $catid = $this->input->post->getInt('catid');
        if ($id) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $post = $db->setQuery($db->getQuery(true)->select(['created_by','catid'])->from('#__academy')->where('id=' . $id))->loadObject();
            $allowed = $post && ($user->authorise('core.edit', 'com_academy.post.' . $id) || ($user->authorise('core.edit.own', 'com_academy.post.' . $id) && (int) $post->created_by === (int) $user->id));
        } else {
            $allowed = $user->authorise('core.create', $catid ? 'com_academy.category.' . $catid : 'com_academy');
        }
        if (!$allowed || ($image && (!$params->get('ai_images', 0) || !$user->authorise('core.create', 'com_media')))) { throw new \RuntimeException('You do not have permission for this action.', 403); }
        return $params;
    }

    public function generate(): void
    {
        $this->respond(function () {
            $action = $this->input->post->getCmd('action');
            $params = $this->guard($action === 'image');
            $session = Factory::getApplication()->getSession();
            $usage = $session->get('com_academy.ai.usage', ['start' => time(), 'count' => 0]);
            if ($usage['start'] < time() - 3600) { $usage = ['start' => time(), 'count' => 0]; }
            if ($usage['count'] >= max(1, min(100, (int) $params->get('ai_hourly_limit', 20)))) { throw new \RuntimeException('This session has reached its hourly AI request limit.'); }
            $usage['count']++; $session->set('com_academy.ai.usage', $usage);
            $result = (new NeuralNetworkService($params))->generate($action, $this->input->post->get('content', '', 'raw'), $this->input->post->get('instruction', '', 'string'));
            if (isset($result['image'])) {
                $id = bin2hex(random_bytes(16));
                $session->set('com_academy.ai.image', ['id' => $id, 'created' => time(), 'image' => $result['image']]);
                return ['preview' => 'data:image/png;base64,' . $result['image'], 'image_id' => $id];
            }
            return $result;
        });
    }

    public function saveImage(): void
    {
        $this->respond(function () {
            $this->guard(true);
            $session = Factory::getApplication()->getSession();
            $pending = $session->get('com_academy.ai.image', []);
            if (!$pending || ($pending['created'] ?? 0) < time() - 1800 || !hash_equals($pending['id'], $this->input->post->getString('image_id'))) { throw new \RuntimeException('The image preview has expired. Generate it again.'); }
            $bytes = NeuralNetworkService::imageBytes($pending['image']);
            $root = realpath(JPATH_ROOT . '/images');
            if (!$root) { throw new \RuntimeException('The Joomla images directory is unavailable.'); }
            $directory = $root;
            foreach (['generated', 'academy'] as $segment) {
                $directory .= '/' . $segment;
                if (is_link($directory)) { throw new \RuntimeException('Invalid image directory.'); }
                if (!is_dir($directory) && !mkdir($directory, 0755) && !is_dir($directory)) { throw new \RuntimeException('The generated image directory is not writable.'); }
                $check = realpath($directory);
                if (!$check || !str_starts_with(str_replace('\\','/',$check) . '/', str_replace('\\','/',$root) . '/')) { throw new \RuntimeException('Invalid image directory.'); }
            }
            $resolved = realpath($directory);
            if (!$resolved || !str_starts_with(str_replace('\\','/',$resolved) . '/', str_replace('\\','/',$root) . '/')) { throw new \RuntimeException('Invalid image directory.'); }
            $name = 'ai-' . bin2hex(random_bytes(16)) . '.png';
            $handle = fopen($resolved . '/' . $name, 'xb');
            if (!$handle) { throw new \RuntimeException('Unable to save the generated image.'); }
            $written = fwrite($handle, $bytes); fclose($handle);
            if ($written !== strlen($bytes)) { unlink($resolved . '/' . $name); throw new \RuntimeException('Image write failed.'); }
            $session->remove('com_academy.ai.image');
            $path = 'images/generated/academy/' . $name;
            return ['path' => $path, 'url' => Uri::root() . $path];
        });
    }

    private function respond(\Closure $operation): void
    {
        $app = Factory::getApplication();
        $app->setHeader('Cache-Control', 'no-store', true);
        try { echo new JsonResponse($operation()); }
        catch (\InvalidArgumentException|\RuntimeException $error) { echo new JsonResponse(null, $error->getMessage(), true); }
        catch (\Throwable $error) { echo new JsonResponse(null, 'The AI response could not be processed. Please try again.', true); }
        $app->close();
    }
}
