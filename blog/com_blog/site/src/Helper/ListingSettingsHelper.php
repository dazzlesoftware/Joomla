<?php
namespace Joomla\Component\Blog\Site\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
final class ListingSettingsHelper
{
    /** Ordering for lightweight listings, using the same menu controls as Featured. */
    public static function order($query, $params): void
    {
        $primary = ['alpha' => 'c.title', 'ralpha' => 'c.title DESC', 'order' => 'c.lft'];
        $categoryOrder = (string) $params->get('orderby_pri', 'none');
        if (isset($primary[$categoryOrder])) {
            $query->order($primary[$categoryOrder]);
        }
        $date = match ((string) $params->get('order_date', 'published')) {
            'created' => 'p.created',
            'modified' => 'COALESCE(p.modified,p.created)',
            default => 'COALESCE(p.publish_up,p.created)',
        };
        $order = (string) $params->get('orderby_sec', 'rdate');
        if (in_array($order, ['vote', 'rvote', 'rank', 'rrank'], true)) {
            $query->join('LEFT', '#__blog_rating AS ordering_rating ON ordering_rating.content_id=p.id');
        }
        if ($order === 'front') {
            $query->join('LEFT', '#__blog_frontpage AS ordering_front ON ordering_front.content_id=p.id');
        }
        $query->order(match ($order) {
            'date' => $date,
            'alpha' => 'p.title', 'ralpha' => 'p.title DESC',
            'author' => 'u.name', 'rauthor' => 'u.name DESC',
            'hits' => 'p.hits DESC', 'rhits' => 'p.hits',
            'order' => 'p.ordering', 'rorder' => 'p.ordering DESC',
            'front' => 'p.featured DESC, ordering_front.ordering, ' . $date . ' DESC',
            'vote' => 'COALESCE(ordering_rating.rating_count,0) DESC',
            'rvote' => 'COALESCE(ordering_rating.rating_count,0)',
            'rank' => 'COALESCE(ordering_rating.rating_sum / NULLIF(ordering_rating.rating_count,0),0) DESC',
            'rrank' => 'COALESCE(ordering_rating.rating_sum / NULLIF(ordering_rating.rating_count,0),0)',
            default => $date . ' DESC',
        });
        $query->order('p.id DESC');
    }

    public static function count($params): int
    {
        $source = (string) $params->get('items_limit_source', 'custom');
        if ($source === '' || $source === 'component') {
            $params = ComponentHelper::getParams('com_blog');
            $source = (string) $params->get('items_limit_source', 'custom');
        }
        if ($source === 'joomla') { return max(1, (int) Factory::getApplication()->get('list_limit', 20)); }
        if (ctype_digit($source) && (int) $source > 0) { return (int) $source; }
        $count = $params->get('posts_per_page');
        if ($count === null || $count === '') {
            $count = max(0, (int) $params->get('num_leading_posts', 1)) + max(0, (int) $params->get('num_intro_posts', 4));
        }
        return max(1, (int) $count);
    }
}
