<?php
namespace Joomla\Component\Codex\Site\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/** Independent featured showcase shared by component lists and post modules. */
final class ArchiveSliderHelper
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
        $params = clone ComponentHelper::getParams('com_codex');
        foreach ($overrides->toArray() as $key => $value) {
            if ($value !== '' && $value !== null) { $params->set($key, $value); }
        }
        return $params;
    }

    public static function items($params, int $categoryId = 0, ?DatabaseInterface $db = null): array
    {
        $app = Factory::getApplication();
        $model = $app->bootComponent('com_codex')->getMVCFactory()->createModel('Archive', 'Site');
        $model->getState();
        $model->setState('params', clone $params);
        $model->setState('filter.month', (int) $params->get('archive_month'));
        $model->setState('filter.year', (int) $params->get('archive_year'));
        $model->setState('list.start', 0);
        $model->setState('filter.featured', 'only');
        $model->setState('list.limit', max(1, min(50, (int) $params->get('featured_slider_count', 5))));
        return $model->getItems() ?: [];
    }

    public static function render($overrides, int $categoryId = 0, int $start = 0, ?callable $renderer = null): string
    {
        $params = self::settings($overrides);
        if (!$params->get('featured_slider_enabled', 0) || ($start > 0 && !$params->get('featured_slider_all_pages', 0))) { return ''; }
        $items = self::items($params, $categoryId);
        if (!$items) { return ''; }
        PluginHelper::importPlugin('content');
        PluginHelper::importPlugin('codex');
        foreach ($items as $item) {
            $item->params = clone $params;
            $item->params->set('show_date', $params->get('featured_slider_date', 1));
            $item->params->set('date_type', $params->get('featured_slider_date_source', 'created'));
            $item->text = (string) ($item->excerpt ?: $item->summary);
            Factory::getApplication()->triggerEvent('onContentPrepare', ['com_codex.archiveslider', &$item, &$item->params, 0]);
            $plain = preg_replace('~<(script|style|template)\b[^>]*>.*?</\1\s*>~is', '', $item->text);
            $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($plain), ENT_QUOTES, 'UTF-8')));
            $limit = max(0, (int) $params->get('featured_slider_content_length', 250));
            $item->sliderText = $limit && mb_strlen($plain) > $limit ? mb_substr($plain, 0, $limit) . '…' : $plain;
        }
        $style = (string) $params->get('featured_slider_style', 'default');
        if (!in_array($style, ['card', 'default', 'hero', 'magazine', 'side-navigation', 'slick', 'thumbnail'], true)) { $style = 'default'; }
        if ($renderer !== null) {
            return $renderer($style, ['items' => $items, 'params' => $params]);
        }
        return LayoutHelper::render('featured.' . $style, ['items' => $items, 'params' => $params], JPATH_ROOT . '/components/com_codex/tmpl/archive/layouts');
    }
}
