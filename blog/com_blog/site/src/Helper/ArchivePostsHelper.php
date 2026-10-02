<?php
namespace Joomla\Component\Blog\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

/** Independent archive-page defaults, mapped to the common listing renderer. */
final class ArchivePostsHelper
{
    private const DEFAULTS = [
        'list_item_style' => 'card',
        'post_listing_layout' => 'columns',
        'columns_per_row' => 2,
        'column_style' => 'grid',
        'items_limit_source' => 'custom',
        'posts_per_page' => 6,
        'compact_show' => 1,
        'num_links' => 4,
        'compact_layout' => 'columns',
        'compact_columns' => 3,
        'compact_show_title' => 1,
        'compact_selection' => 'next',
        'compact_heading_alignment' => 'left',
        'compact_show_image' => 1,
        'link_featured_image' => 0,
        'compact_show_rating' => 1,
        'orderby_pri' => 'none',
        'orderby_sec' => 'rdate',
        'order_date' => 'published',
        'show_pagination' => 2,
        'show_pagination_results' => 1,
        'featured_slider_enabled' => 1,
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

    public static function settings(Registry $overrides, bool $readRequest = true): Registry
    {
        $global = ComponentHelper::getParams('com_blog');
        $params = clone ComponentHelper::getParams('com_blog');
        foreach ($overrides->toArray() as $key => $value) {
            if ($value !== '' && $value !== null) {
                $params->set($key, $value);
            }
        }
        // These Archive controls are managed only in component settings.
        foreach (['list_item_style', 'post_listing_layout', 'columns_per_row', 'column_style', 'items_limit_source', 'posts_per_page', 'featured_slider_style', 'compact_layout', 'num_links', 'compact_columns'] as $key) {
            $params->set('archive_posts_' . $key, $global->get('archive_posts_' . $key, self::DEFAULTS[$key]));
        }
        foreach (self::DEFAULTS as $key => $default) {
            $params->set($key, $params->get('archive_posts_' . $key, $default));
        }
        $input = \Joomla\CMS\Factory::getApplication()->getInput();
        $params->set('archive_month', $input->getInt('month'));
        $params->set('archive_year', $input->getInt('year'));
        // Preserve category selections from older Archive menu links.
        if ($readRequest && !ListingFilterHelper::ids($params->get('listing_categories', []))) {
            $legacyCategories = ListingFilterHelper::ids($input->get('catid', [], 'array'));
            if ($legacyCategories) { $params->set('listing_categories', $legacyCategories); }
        }
        // Carry module filters through month links and pagination. Accept only known filter values.
        $moduleFilters = $readRequest ? $input->get('archive_filters', [], 'array') : [];
        foreach (['listing_authors', 'listing_exclude_authors', 'listing_categories', 'listing_exclude_categories', 'listing_tags', 'listing_exclude_posts'] as $key) {
            if (array_key_exists($key, $moduleFilters)) { $params->set($key, ListingFilterHelper::ids($moduleFilters[$key])); }
        }
        if (isset($moduleFilters['listing_subcategories'])) { $params->set('listing_subcategories', (int) (bool) $moduleFilters['listing_subcategories']); }
        if (isset($moduleFilters['order_date']) && in_array($moduleFilters['order_date'], ['created', 'modified', 'published'], true)) { $params->set('order_date', $moduleFilters['order_date']); }
        $params->set('listing_include_featured', 1);
        $params->set('listing_pin_featured', 0);
        $params->set('layout_type', 'card');
        if (!in_array($params->get('list_item_style'), ['standard', 'card', 'learning', 'simple', 'nickel'], true)) {
            $params->set('list_item_style', 'card');
        }
        return $params;
    }
}
