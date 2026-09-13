<?php
namespace Joomla\Component\Codex\Administrator\View\Tags;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Codex\Administrator\Helper\TagsHelper;

final class HtmlView extends BaseHtmlView
{
    private const SORT_COLUMNS = ['t.title', 't.published', 't.is_default', 'post_count', 't.language', 'author_name', 't.id'];

    public array $items = [];
    public ?Pagination $pagination = null;
    public string $search = '';
    public string $filterPublished = '';
    public string $listOrder = 't.title';
    public string $listDirn = 'ASC';
    public int $limit = 20;
    public int $limitstart = 0;
    public int $total = 0;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_codex')) {
            throw new \RuntimeException('Not authorised', 403);
        }

        $input = $app->getInput();
        $this->search = trim((string) $input->getString('filter_search', ''));
        $this->filterPublished = (string) $input->getCmd('filter_published', '');
        $this->listOrder = (string) $input->getCmd('filter_order', 't.title');
        if (!\in_array($this->listOrder, self::SORT_COLUMNS, true)) {
            $this->listOrder = 't.title';
        }
        $this->listDirn = strtoupper((string) $input->getCmd('filter_order_Dir', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $this->limit = (int) $input->getInt('list_limit', 20);
        if ($this->limit < 1) {
            $this->limit = 20;
        }
        $this->limitstart = (int) $input->getInt('limitstart', 0);

        $db = TagsHelper::db();
        $query = $db->createQuery()
            ->select([
                't.*',
                '(SELECT COUNT(*) FROM #__codex_tag_map m WHERE m.tag_id = t.id AND m.type_alias = ' . $db->quote('com_codex.post') . ') AS post_count',
                'u.name AS author_name',
            ])
            ->from('#__codex_tags AS t')
            ->leftJoin('#__users AS u ON u.id = t.created_by');

        if ($this->search !== '') {
            $needle = str_replace(['%', '_'], ['\%', '\_'], $this->search);
            $query->where('t.title LIKE ' . $db->quote('%' . $needle . '%'));
        }
        if (\in_array($this->filterPublished, ['0', '1'], true)) {
            $query->where('t.published = ' . (int) $this->filterPublished);
        }

        $countQuery = $db->createQuery()->select('COUNT(*)')->from('(' . (string) $query . ') AS filtered');
        $this->total = (int) $db->setQuery($countQuery)->loadResult();

        $query->order($this->listOrder . ' ' . $this->listDirn . ($this->listOrder !== 't.id' ? ', t.id ASC' : ''));
        $this->items = $db->setQuery($query, $this->limitstart, $this->limit)->loadObjectList() ?: [];
        $this->pagination = new Pagination($this->total, $this->limitstart, $this->limit);

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $user = $this->getCurrentUser();
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_CODEX_TAGS_TITLE'), 'tag');

        if ($user->authorise('core.create', 'com_codex')) {
            $toolbar->addNew('tags.add');
        }

        if ($user->authorise('core.edit.state', 'com_codex')) {
            $toolbar->publish('tags.publish')->listCheck(true);
            $toolbar->unpublish('tags.unpublish')->listCheck(true);
            $toolbar->standardButton('star', 'COM_CODEX_MAKE_DEFAULT', 'tags.makedefault')->listCheck(true);
            $toolbar->standardButton('star-empty', 'COM_CODEX_REMOVE_DEFAULT', 'tags.removedefault')->listCheck(true);
        }

        if ($user->authorise('core.delete', 'com_codex')) {
            $toolbar->delete('tags.delete')->message('JGLOBAL_CONFIRM_DELETE')->listCheck(true);
        }

        $toolbar->help('Tags');
    }
}
