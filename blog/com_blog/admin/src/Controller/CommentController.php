<?php
namespace Joomla\Component\Blog\Administrator\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

final class CommentController extends BaseController
{
    public function add(): void
    {
        Factory::getApplication()->redirect(Route::_('index.php?option=com_blog&view=comment', false));
    }

    public function apply(): void
    {
        $id = $this->doSave();
        Factory::getApplication()->redirect(Route::_('index.php?option=com_blog&view=comment&id=' . $id, false));
    }

    public function save(): void
    {
        $this->doSave();
        Factory::getApplication()->redirect(Route::_('index.php?option=com_blog&view=comments', false));
    }

    public function save2new(): void
    {
        $this->doSave();
        Factory::getApplication()->redirect(Route::_('index.php?option=com_blog&view=comment', false));
    }

    public function cancel(): void
    {
        Factory::getApplication()->redirect(Route::_('index.php?option=com_blog&view=comments', false));
    }

    private function doSave(): int
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        $input = $app->getInput()->post;
        $data = $input->get('jform', [], 'array');
        $id = (int) ($data['id'] ?? 0);

        if (!$app->getIdentity()->authorise($id ? 'core.edit' : 'core.create', 'com_blog')) {
            throw new \RuntimeException('Not authorised', 403);
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $postId = (int) ($data['post_id'] ?? 0);
        $name = mb_substr(trim(strip_tags((string) ($data['name'] ?? ''))), 0, 255);
        $email = mb_substr(trim((string) ($data['email'] ?? '')), 0, 255);
        $body = mb_substr(strip_tags((string) ($data['body'] ?? '')), 0, 10000);
        $state = (int) ($data['state'] ?? 0);
        $created = trim((string) ($data['created'] ?? ''));

        if (!$postId || !(int) $db->setQuery('SELECT id FROM #__blog WHERE id=' . $postId)->loadResult()) {
            throw new \InvalidArgumentException('Select a valid post.', 400);
        }
        if ($name === '') {
            throw new \InvalidArgumentException('An author name is required.', 400);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('The email address is not valid.', 400);
        }
        if ($body === '') {
            throw new \InvalidArgumentException('A comment body is required.', 400);
        }

        $createdSql = $created !== '' ? str_replace('T', ' ', $created) . ':00' : Factory::getDate()->toSql();

        $row = (object) [
            'id' => $id ?: null,
            'post_id' => $postId,
            'parent_id' => (int) ($data['parent_id'] ?? 0),
            'user_id' => (int) ($data['user_id'] ?? 0),
            'name' => $name,
            'email' => $email,
            'body' => $body,
            'state' => $state,
            'created' => $createdSql,
            'ip_hash' => '',
        ];

        if ($id) {
            $old = $db->setQuery('SELECT ip_hash FROM #__blog_comments WHERE id=' . $id)->loadObject();
            if (!$old) {
                throw new \RuntimeException('Comment not found.', 404);
            }
            $row->ip_hash = $old->ip_hash;
            $db->updateObject('#__blog_comments', $row, 'id');
        } else {
            unset($row->ip_hash);
            $row->ip_hash = '';
            $db->insertObject('#__blog_comments', $row, 'id');
        }

        $app->enqueueMessage('Comment saved.');
        return (int) $row->id;
    }
}
