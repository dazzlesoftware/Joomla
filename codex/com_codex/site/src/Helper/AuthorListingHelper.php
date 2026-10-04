<?php
namespace Joomla\Component\Codex\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

/** Independent author-page defaults, mapped to the common listing renderer. */
final class AuthorListingHelper
{
    private const DEFAULTS = [
        'compact_show' => 1,
        'num_links' => 4,
        'compact_layout' => 'columns',
        'compact_columns' => 2,
        'compact_show_title' => 1,
        'compact_selection' => 'next',
        'compact_heading_alignment' => 'left',
        'compact_show_image' => 1,
        'link_featured_image' => 0,
        'compact_show_rating' => 1,

        'list_item_style' => 'card',
        'post_listing_layout' => 'columns',
        'columns_per_row' => 2,
        'column_style' => 'grid',
        'items_limit_source' => 'custom',
        'posts_per_page' => 6,
        'orderby_pri' => 'none',
        'orderby_sec' => 'rdate',
        'order_date' => 'published',
        'show_pagination' => 2,
        'show_pagination_results' => 1,
        'featured_slider_enabled' => 0,
        'featured_slider_style' => 'default',
        'featured_slider_all_pages' => 0,
        'featured_slider_auto' => 0,
        'featured_slider_interval' => 8,
        'featured_slider_image' => 1,
        'featured_slider_title' => 1,
        'featured_slider_category' => 1,
        'featured_slider_author' => 1,
        'featured_slider_avatar' => 1,
        'featured_slider_readmore' => 1,
        'featured_slider_navigation' => 1,
        'featured_slider_content' => 1,
        'featured_slider_ratings' => 1,
        'featured_slider_date' => 1,
        'featured_slider_content_length' => 250,
        'featured_slider_count' => 5,
        'featured_slider_date_source' => 'created',
    ];

    public static function settings(Registry $overrides, int $authorId): Registry
    {
        $params = clone ComponentHelper::getParams('com_codex');
        foreach ($overrides->toArray() as $key => $value) {
            if ($value !== '' && $value !== null) {
                $params->set($key, $value);
            }
        }
        // Layout and size controls are component-only, including stale saved overrides.
        $global = ComponentHelper::getParams('com_codex');
        foreach (['list_item_style', 'post_listing_layout', 'columns_per_row', 'column_style', 'items_limit_source', 'posts_per_page', 'compact_layout', 'num_links', 'compact_columns'] as $key) {
            $params->set('author_' . $key, $global->get('author_' . $key, self::DEFAULTS[$key]));
        }
        foreach (self::DEFAULTS as $key => $default) {
            $params->set($key, $params->get('author_' . $key, $default));
        }
        $menu = \Joomla\CMS\Factory::getApplication()->getMenu()->getActive();
        $authorMenu = $menu && ($menu->query['option'] ?? '') === 'com_codex'
            && ($menu->query['view'] ?? '') === 'author';
        // Direct author links must not inherit unrelated listing filters.
        if (!$authorMenu) {
            foreach (['listing_categories', 'listing_exclude_categories', 'listing_tags', 'listing_authors', 'listing_exclude_authors', 'listing_canonical'] as $key) {
                $params->set($key, $key === 'listing_canonical' ? '' : []);
            }
        }
        $explicitAuthor = $authorId > 0 && (!$authorMenu || (int) ($menu->query['id'] ?? 0) !== $authorId);
        if ($explicitAuthor || ($authorMenu ? !$menu->getParams()->exists('listing_authors') : !$overrides->exists('listing_authors'))) {
            $params->set('listing_authors', $authorId > 0 ? [$authorId] : []);
        }
        // Preserve exclusions saved in the old Author Posts tab.
        if ($authorMenu ? !$menu->getParams()->exists('listing_exclude_authors') : !$overrides->exists('listing_exclude_authors')) {
            $params->set('listing_exclude_authors', $params->get('author_listing_exclude_authors', []));
        }
        $params->set('listing_include_featured', 1);
        $params->set('listing_pin_featured', 0);
        $params->set('layout_type', 'card');
        if (!in_array($params->get('list_item_style'), ['standard', 'card', 'learning', 'simple', 'nickel'], true)) {
            $params->set('list_item_style', 'card');
        }
        return $params;
    }
}
