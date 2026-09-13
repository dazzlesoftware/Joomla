<?php

namespace Joomla\Component\Codex\Administrator\View\Categories;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Codex\Administrator\Helper\CategoriesHelper;

final class HtmlView extends BaseHtmlView
{
    private const SORT_COLUMNS = ['c.title', 'c.is_default', 'c.published', 'sub_count', 'post_count', 'c.language', 'author_name', 'c.id'];

    public array $items = [];
    public ?Pagination $pagination = null;
    public string $search = '';
    public string $filterPublished = '';
    public string $listOrder = 'c.title';
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
        $this->listOrder = (string) $input->getCmd('filter_order', 'c.title');
        if (!\in_array($this->listOrder, self::SORT_COLUMNS, true)) {
            $this->listOrder = 'c.title';
        }
        $this->listDirn = strtoupper((string) $input->getCmd('filter_order_Dir', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $this->limit = (int) $input->getInt('list_limit', 20);
        if ($this->limit < 1) {
            $this->limit = 20;
        }
        $this->limitstart = (int) $input->getInt('limitstart', 0);

        $db = CategoriesHelper::db();
        $query = $db->createQuery()
            ->select([
                'c.*',
                'COUNT(DISTINCT p.id) AS post_count',
                '(SELECT COUNT(*) FROM #__codex_categories sc WHERE sc.parent_id = c.id) AS sub_count',
                'u.name AS author_name',
            ])
            ->from('#__codex_categories AS c')
            ->leftJoin('#__codex AS p ON p.catid = c.id')
            ->leftJoin('#__users AS u ON u.id = c.created_user_id')
            ->group('c.id');

        if ($this->search !== '') {
            $needle = str_replace(['%', '_'], ['\%', '\_'], $this->search);
            $query->where('c.title LIKE ' . $db->quote('%' . $needle . '%'));
        }
        if (\in_array($this->filterPublished, ['0', '1'], true)) {
            $query->where('c.published = ' . (int) $this->filterPublished);
        }

        $countQuery = $db->createQuery()->select('COUNT(*)')->from('(' . (string) $query . ') AS grouped');
        $this->total = (int) $db->setQuery($countQuery)->loadResult();

        $query->order($this->listOrder . ' ' . $this->listDirn . ($this->listOrder !== 'c.id' ? ', c.id ASC' : ''));
        $this->items = $db->setQuery($query, $this->limitstart, $this->limit)->loadObjectList() ?: [];
        $this->pagination = new Pagination($this->total, $this->limitstart, $this->limit);

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $user = $this->getCurrentUser();
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_CODEX_CATEGORIES_TITLE'), 'copy');

        if ($user->authorise('core.create', 'com_codex')) {
            $toolbar->addNew('categories.add');
        }

        if ($user->authorise('core.edit.state', 'com_codex')) {
            $toolbar->publish('categories.publish')->listCheck(true);
            $toolbar->unpublish('categories.unpublish')->listCheck(true);
            $toolbar->standardButton('star', 'COM_CODEX_MAKE_DEFAULT', 'categories.makedefault')->listCheck(true);
            $toolbar->standardButton('star-empty', 'COM_CODEX_REMOVE_DEFAULT', 'categories.removedefault')->listCheck(true);
        }

        if ($user->authorise('core.create', 'com_codex')) {
            $toolbar->standardButton('copy', 'JLIB_HTML_BATCH_COPY', 'categories.copy')->listCheck(true);
        }

        if ($user->authorise('core.delete', 'com_codex')) {
            $toolbar->delete('categories.delete')->message('JGLOBAL_CONFIRM_DELETE')->listCheck(true);
        }

        $toolbar->help('Categories');
    }
}
