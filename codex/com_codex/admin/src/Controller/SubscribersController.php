<?php
namespace Joomla\Component\Codex\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

final class SubscribersController extends BaseController
{
    private function allowed(): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_codex')) {
            throw new \RuntimeException('Not authorised', 403);
        }
    }

    private function ids(): array
    {
        return array_filter(array_map('intval', (array) Factory::getApplication()->getInput()->post->get('cid', [], 'array')));
    }

    public function edit(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $this->allowed();
        $ids = array_values($this->ids());
        if (count($ids) !== 1) {
            Factory::getApplication()->enqueueMessage('Select exactly one subscriber to edit.', 'warning');
            $this->back();
            return;
        }
        Factory::getApplication()->redirect(Route::_('index.php?option=com_codex&view=subscriber&id=' . $ids[0], false));
    }

    public function state(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $this->allowed();
        $ids = $this->ids();
        if ($ids) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery($db->createQuery()->update('#__codex_subscribers')->set('state=' . Factory::getApplication()->getInput()->getInt('value'))->whereIn('id', $ids))->execute();
        }
        $this->back();
    }

    public function delete(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $this->allowed();
        $ids = $this->ids();
        if ($ids) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $emails = $db->setQuery($db->createQuery()->select('email')->from('#__codex_subscribers')->whereIn('id', $ids))->loadColumn();
            foreach ($emails as $email) {
                $existing = (int) $db->setQuery($db->createQuery()->select('id')->from('#__codex_subscriber_suppressions')->where('email=' . $db->quote(strtolower($email))))->loadResult();
                if (!$existing) {
                    $suppression = (object) ['email' => strtolower($email), 'reason' => 'removed', 'created' => Factory::getDate()->toSql()];
                    $db->insertObject('#__codex_subscriber_suppressions', $suppression);
                }
            }
            $db->setQuery($db->createQuery()->delete('#__codex_subscribers')->whereIn('id', $ids))->execute();
        }
        $this->back();
    }

    public function import(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $this->allowed();
        $app = Factory::getApplication();
        $file = $app->getInput()->files->get('csv_file', null, 'array');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Choose a valid CSV file.', 400);
        }
        $handle = fopen($file['tmp_name'], 'rb');
        if (!$handle) {
            throw new \RuntimeException('The CSV file could not be read.', 400);
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $added = 0;
        $skipped = 0;
        $first = true;
        while (($row = fgetcsv($handle)) !== false) {
            if ($first && isset($row[0]) && strtolower(trim($row[0])) === 'email') {
                $first = false;
                continue;
            }
            $first = false;
            $email = strtolower(trim((string) ($row[0] ?? '')));
            $name = trim((string) ($row[1] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }
            $blocked = (int) $db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__codex_subscriber_suppressions')->where('email=' . $db->quote($email)))->loadResult();
            $exists = (int) $db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__codex_subscribers')->where('email=' . $db->quote($email)))->loadResult();
            if ($blocked || $exists) {
                $skipped++;
                continue;
            }
            $now = Factory::getDate()->toSql();
            $subscriber = (object) ['name' => $name, 'email' => $email, 'state' => 1, 'token' => bin2hex(random_bytes(24)), 'confirm_token' => '', 'created' => $now, 'consented' => $now, 'confirmed' => $now, 'unsubscribed' => null, 'consent_ip' => 'csv-import'];
            $db->insertObject('#__codex_subscribers', $subscriber);
            $added++;
        }
        fclose($handle);
        $app->enqueueMessage("Imported $added subscriber(s); skipped $skipped invalid, duplicate, or suppressed row(s).");
        $this->back();
    }

    public function export(): void
    {
        $this->allowed();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $rows = $db->setQuery($db->createQuery()->select(['email', 'name', 'state', 'created', 'consented', 'confirmed', 'unsubscribed'])->from('#__codex_subscribers')->order('email'))->loadAssocList();
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'text/csv; charset=utf-8', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="codex-subscribers.csv"', true);
        $app->sendHeaders();
        $output = fopen('php://output', 'wb');
        fputcsv($output, ['email', 'name', 'state', 'created', 'consented', 'confirmed', 'unsubscribed']);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        $app->close();
    }

    private function back(): void
    {
        Factory::getApplication()->redirect(Route::_('index.php?option=com_codex&view=subscribers', false));
    }
}
