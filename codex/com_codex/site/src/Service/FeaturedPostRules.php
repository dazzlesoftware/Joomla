<?php
namespace Joomla\Component\Codex\Site\Service;
defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\Rules\RulesInterface;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\Database\DatabaseInterface;

/** Route individual posts beneath their listing menu without losing its settings. */
final class FeaturedPostRules implements RulesInterface
{
    public function __construct(private RouterView $router, private DatabaseInterface $db, private bool $noIDs = false) {}

    public function preprocess(&$query) {}

    public function build(&$query, &$segments)
    {
        $menu = isset($query['Itemid']) ? $this->router->menu->getItem((int) $query['Itemid']) : null;
        if (($query['view'] ?? '') !== 'post' || !$menu
            || ($menu->query['option'] ?? '') !== 'com_codex'
            || !in_array($menu->query['view'] ?? '', ['featured', 'archive', 'author', 'tags'], true) || empty($query['id'])) {
            return;
        }
        $post = $this->post((int) $query['id']);
        if (!$post) { return; }
        $segments[] = 'post';
        // Numeric prefixes are reserved for existing ID-based URLs. Duplicate
        // aliases (including in other categories/languages) must retain IDs.
        $unique = $this->noIDs && $post->alias !== ''
            && !preg_match('/^[1-9][0-9]*-/', $post->alias)
            ? $this->postByAlias($post->alias) : null;
        $segments[] = $unique && (int) $unique->id === (int) $post->id
            ? $post->alias : $post->id . '-' . $post->alias;
        unset($query['view'], $query['id'], $query['catid']);
    }

    public function parse(&$segments, &$vars)
    {
        $menu = $this->router->menu->getActive();
        if (!$menu || ($menu->query['option'] ?? '') !== 'com_codex'
            || !in_array($menu->query['view'] ?? '', ['featured', 'archive', 'author', 'tags'], true)
            || count($segments) !== 2 || $segments[0] !== 'post') {
            return;
        }
        // Accept both URL forms after the setting changes. Never guess which
        // post an ambiguous alias belongs to, or reinterpret an old numeric URL.
        $post = preg_match('/^([1-9][0-9]*)-/', $segments[1], $match)
            ? $this->post((int) $match[1]) : $this->postByAlias($segments[1]);
        if (!$post) { return; }
        $vars['view'] = 'post';
        $vars['id'] = (int) $post->id;
        $vars['catid'] = (int) $post->catid;
        $segments = [];
    }

    private function postByAlias(string $alias): ?object
    {
        if ($alias === '') { return null; }
        $posts = $this->db->setQuery($this->db->createQuery()
            ->select(['id', 'alias', 'catid'])->from('#__codex')
            ->where($this->db->quoteName('alias') . '=' . $this->db->quote($alias)), 0, 2)
            ->loadObjectList();
        return count($posts) === 1 ? $posts[0] : null;
    }

    private function post(int $id): ?object
    {
        return $this->db->setQuery($this->db->createQuery()
            ->select(['id', 'alias', 'catid'])->from('#__codex')
            ->where('id=' . $id))->loadObject() ?: null;
    }
}
