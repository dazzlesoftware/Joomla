<?php
namespace Joomla\Component\Blog\Site\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/** Independent featured showcase shared by component lists and post modules. */
final class FeaturedSliderHelper
{
    /** Resolve the Media Manager URL after removing Joomla image metadata. */
    public static function imageUrl(string $value): string
    {
        $image = trim((string) \Joomla\CMS\HTML\HTMLHelper::_('cleanImageURL', trim($value))->url);
        if ($image === '') { return ''; }
        if (preg_match('~^(?:https?:)?//~i', $image)) { return $image; }
        if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $image)) { return ''; }
        if (str_starts_with($image, '/')) { return $image; }
        return \Joomla\CMS\Uri\Uri::root() . $image;
    }

    public static function settings($overrides): Registry
    {
        $params = clone ComponentHelper::getParams('com_blog');
        foreach ($overrides->toArray() as $key => $value) {
            if ($value !== '' && $value !== null) { $params->set($key, $value); }
        }
        return $params;
    }

    public static function items($params, int $categoryId = 0, ?DatabaseInterface $db = null): array
    {
        $app = Factory::getApplication();
        $db ??= Factory::getContainer()->get(DatabaseInterface::class);
        $levels = $app->getIdentity()->getAuthorisedViewLevels();
        $q = $db->createQuery()->select('p.*, u.name AS author, c.title AS category_title, r.rating_sum, r.rating_count')
            ->from('#__blog AS p')->join('INNER', '#__blog_categories AS c ON c.id=p.catid')
            ->join('LEFT', '#__users AS u ON u.id=p.created_by')
            ->join('LEFT', '#__blog_rating AS r ON r.content_id=p.id')
            ->join('LEFT', '#__blog_frontpage AS fp ON fp.content_id=p.id')
            ->where('p.state=1 AND p.featured=1 AND c.published=1')
            ->whereIn('p.access', $levels)->whereIn('c.access', $levels)
            ->where('(p.publish_up IS NULL OR p.publish_up <= UTC_TIMESTAMP())')
            ->where('(p.publish_down IS NULL OR p.publish_down >= UTC_TIMESTAMP())')
            ->where('(fp.featured_up IS NULL OR fp.featured_up <= UTC_TIMESTAMP())')
            ->where('(fp.featured_down IS NULL OR fp.featured_down >= UTC_TIMESTAMP())');
        if (\Joomla\CMS\Language\Multilanguage::isEnabled()) {
            $languages = $db->quote('*') . ',' . $db->quote($app->getLanguage()->getTag());
            $q->where('p.language IN (' . $languages . ')')->where('c.language IN (' . $languages . ')');
        }
        // Include/pin controls concern the regular list, never the showcase query.
        $filters = clone $params;
        $filters->set('listing_include_featured', 1);
        $filters->set('listing_pin_featured', 0);
        ListingFilterHelper::apply($q, $filters, 'p', $categoryId, $db);
        $q->order('COALESCE(fp.ordering, 0), p.created DESC, p.id DESC');
        return $db->setQuery($q, 0, max(1, min(50, (int) $params->get('featured_slider_count', 5))))->loadObjectList();
    }

    public static function render($overrides, int $categoryId = 0, int $start = 0): string
    {
        $params = self::settings($overrides);
        if (!$params->get('featured_slider_enabled', 0) || ($start > 0 && !$params->get('featured_slider_all_pages', 0))) { return ''; }
        $items = self::items($params, $categoryId);
        if (!$items) { return ''; }
        PluginHelper::importPlugin('content');
        PluginHelper::importPlugin('blog');
        foreach ($items as $item) {
            $item->params = clone $params;
            $item->params->set('show_date', $params->get('featured_slider_date', 1));
            $item->params->set('date_type', $params->get('featured_slider_date_source', 'created'));
            $item->text = (string) ($item->excerpt ?: $item->summary);
            Factory::getApplication()->triggerEvent('onContentPrepare', ['com_blog.featuredslider', &$item, &$item->params, 0]);
            $plain = preg_replace('~<(script|style|template)\b[^>]*>.*?</\1\s*>~is', '', $item->text);
            $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($plain), ENT_QUOTES, 'UTF-8')));
            $limit = max(0, (int) $params->get('featured_slider_content_length', 250));
            $item->sliderText = $limit && mb_strlen($plain) > $limit ? mb_substr($plain, 0, $limit) . '…' : $plain;
        }
        $style = (string) $params->get('featured_slider_style', 'default');
        if (!in_array($style, ['card', 'default', 'hero', 'magazine', 'side-navigation', 'slick', 'thumbnail'], true)) { $style = 'default'; }
        return LayoutHelper::render('featured.' . $style, ['items' => $items, 'params' => $params], JPATH_ROOT . '/components/com_blog/layouts');
    }
}
