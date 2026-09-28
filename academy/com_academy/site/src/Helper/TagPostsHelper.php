<?php
namespace Joomla\Component\Academy\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

/** Independent tag-page defaults, mapped to the common listing renderer. */
final class TagPostsHelper
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

    public static function settings(Registry $overrides, int $tagId): Registry
    {
        $params = clone ComponentHelper::getParams('com_academy');
        foreach ($overrides->toArray() as $key => $value) {
            if ($value !== '' && $value !== null) {
                $params->set($key, $value);
            }
        }
        foreach (self::DEFAULTS as $key => $default) {
            $params->set($key, $params->get('tag_posts_' . $key, $default));
        }
        // A toolbar link can inherit an unrelated active menu. Never inherit its filters.
        foreach (['listing_categories', 'listing_authors', 'listing_exclude_authors'] as $key) {
            $params->set($key, []);
        }
        $params->set('listing_tags', [$tagId]);
        $params->set('listing_exclude_categories', $overrides->get('tags_exclude_categories', []));
        $params->set('listing_exclude_posts', $overrides->get('tags_exclude_posts', []));
        $params->set('listing_subcategories', 0);
        $params->set('listing_include_featured', 1);
        $params->set('listing_pin_featured', 0);
        $params->set('layout_type', 'card');
        if (!in_array($params->get('list_item_style'), ['standard', 'card', 'learning', 'simple', 'nickel'], true)) {
            $params->set('list_item_style', 'card');
        }
        return $params;
    }
}
