<?php
namespace Joomla\Component\Blog\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

/** Independent author-page defaults, mapped to the common listing renderer. */
final class AuthorListingHelper
{
    private const DEFAULTS = [
        'list_item_style' => 'card',
        'post_listing_layout' => 'columns',
        'columns_per_row' => 2,
        'column_style' => 'grid',
        'items_limit_source' => 'custom',
        'posts_per_page' => 6,
        'listing_exclude_authors' => [],
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
        $params = clone ComponentHelper::getParams('com_blog');
        foreach ($overrides->toArray() as $key => $value) {
            if ($value !== '' && $value !== null) {
                $params->set($key, $value);
            }
        }
        foreach (self::DEFAULTS as $key => $default) {
            $params->set($key, $params->get('author_' . $key, $default));
        }
        // A toolbar link can inherit an unrelated active menu. Never inherit its filters.
        foreach (['listing_categories', 'listing_exclude_categories', 'listing_tags'] as $key) {
            $params->set($key, []);
        }
        $params->set('listing_authors', [$authorId]);
        $params->set('listing_include_featured', 1);
        $params->set('listing_pin_featured', 0);
        $params->set('layout_type', 'card');
        if (!in_array($params->get('list_item_style'), ['standard', 'card', 'learning', 'simple', 'nickel'], true)) {
            $params->set('list_item_style', 'card');
        }
        return $params;
    }
}
