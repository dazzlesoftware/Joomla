<?php
namespace Joomla\Component\Codex\Administrator\View\Votes;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;
final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public ?object $vote = null;
    public array $posts = [];
    public $pagination;
    public string $search = '';
    public int $postId = 0;
    public int $score = 0;
    public string $listOrder = 'v.created';
    public string $listDirn = 'DESC';
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_codex')) { throw new \RuntimeException('Not authorised', 403); }
        $input = $app->getInput();
        $this->search = trim($input->getString('search', ''));
        $this->postId = $input->getUint('post_id', 0);
        $this->score = $input->getUint('score', 0);
        $order = $input->getCmd('filter_order', 'v.created');
        $this->listOrder = in_array($order, ['p.title', 'v.rating', 'voter_name', 'v.created', 'v.id'], true) ? $order : 'v.created';
        $this->listDirn = strtoupper($input->getCmd('filter_order_Dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        if ($this->getLayout() === 'edit') {
            $id = $input->getUint('id', 0);
            if (!$app->getIdentity()->authorise($id ? 'core.edit' : 'core.create', 'com_codex')) { throw new \RuntimeException('Not authorised', 403); }
            $this->vote = $id ? $db->setQuery($db->createQuery()->select('*')->from('#__codex_rating_votes')->where('id=' . $id))->loadObject() : (object) ['id' => 0, 'post_id' => 0, 'rating' => 5];
            if (!$this->vote) { throw new \RuntimeException('Vote not found', 404); }
            $this->posts = $db->setQuery($db->createQuery()->select(['id', 'title'])->from('#__codex')->order('title ASC'))->loadObjectList();
            ToolbarHelper::title($id ? 'Edit vote' : 'New vote', 'star');
            parent::display($tpl);
            return;
        }
        $q = $db->createQuery()->from('#__codex_rating_votes AS v')->join('LEFT', '#__codex AS p ON p.id=v.post_id');
        if ($this->postId) { $q->where('v.post_id=' . $this->postId); }
        if ($this->score >= 1 && $this->score <= 5) { $q->where('v.rating=' . $this->score); }
        if ($this->search !== '') { $q->where('p.title LIKE ' . $db->quote('%' . $db->escape($this->search, true) . '%', false)); }
        $total = (int) $db->setQuery((clone $q)->select('COUNT(*)'))->loadResult();
        $limit = max(1, min(100, $input->getUint('limit', 20)));
        $start = min($input->getUint('limitstart', 0), max(0, (int) (ceil($total / $limit) - 1) * $limit));
        $this->pagination = new Pagination($total, $start, $limit);
        foreach (['search' => $this->search, 'post_id' => $this->postId, 'score' => $this->score, 'filter_order' => $this->listOrder, 'filter_order_Dir' => $this->listDirn] as $key => $value) { $this->pagination->setAdditionalUrlParam($key, $value); }
        $q->select('v.*, p.title AS post_title, u.name AS voter_name')->join('LEFT', "#__users AS u ON v.voter_key=CONCAT('user:',u.id)")->order($this->listOrder . ' ' . $this->listDirn . ', v.id DESC');
        $this->items = $db->setQuery($q, $start, $limit)->loadObjectList();
        $this->getDocument()->getWebAssetManager()->useScript('multiselect')->useStyle('fontawesome');
        ToolbarHelper::title('Votes', 'star');
        if ($app->getIdentity()->authorise('core.create', 'com_codex')) {
            $this->getDocument()->getToolbar()->addNew('votes.add');
        }
        if ($app->getIdentity()->authorise('core.delete', 'com_codex')) {
            $this->getDocument()->getToolbar()->delete('votes.delete')->message('Delete selected votes and recalculate their post ratings?')->listCheck(true);
        }
        parent::display($tpl);
    }
}
