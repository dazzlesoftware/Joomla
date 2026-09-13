<?php

namespace Joomla\Component\Academy\Administrator\Controller;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

final class PollsController extends BaseController
{
    public function delete(): void
    {
        Session::checkToken() or jexit('Invalid token');
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.delete', 'com_academy')) {
            throw new \RuntimeException('Not authorised', 403);
        }$ids = array_map('intval', (array)$app->getInput()->post->get('cid', [], 'array'));
        if ($ids) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            foreach (['#__academy_poll_votes','#__academy_poll_options','#__academy_polls'] as $table) {
                $db->setQuery($db->createQuery()->delete($table)->whereIn($table === '#__academy_polls' ? 'id' : 'poll_id', $ids))->execute();
            }
        }$app->redirect(Route::_('index.php?option=com_academy&view=polls', false));
    }
    public function export(): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_academy')) {
            throw new \RuntimeException('Not authorised', 403);
        }$pollId = $app->getInput()->getInt('poll_id');
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $poll = $db->setQuery($db->createQuery()->select('*')->from('#__academy_polls')->where('id='.$pollId))->loadObject();
        if (!$poll) {
            throw new \RuntimeException('Poll not found.', 404);
        }$rows = $db->setQuery($db->createQuery()->select(['o.title','COUNT(v.id) AS votes'])->from('#__academy_poll_options AS o')->join('LEFT', '#__academy_poll_votes AS v ON v.option_id=o.id')->where('o.poll_id='.$pollId)->group('o.id')->order('o.ordering'))->loadAssocList();
        $app->setHeader('Content-Type', 'text/csv; charset=utf-8', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="academy-poll-'.$pollId.'.csv"', true);
        $app->sendHeaders();
        $out = fopen('php://output', 'wb');
        fputcsv($out, ['poll_id','question','opens','closes']);
        fputcsv($out, [$poll->id,$poll->title,$poll->publish_up,$poll->publish_down]);
        fputcsv($out, []);
        fputcsv($out, ['choice','votes']);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }fclose($out);
        $app->close();
    }
}
