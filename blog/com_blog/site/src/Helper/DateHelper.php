<?php
namespace Joomla\Component\Blog\Site\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Registry\Registry;
final class DateHelper
{
    public static function options($params = null): array
    {
        $params ??= ComponentHelper::getParams('com_blog');
        $show = $params->get('show_date');
        $type = $params->get('date_type');
        if ($show === null || $show === '' || $show === 'use_post') {
            $legacy = array_map(fn ($key) => $params->get($key), ['show_publish_date', 'show_modify_date', 'show_create_date']);
            $show = count(array_filter($legacy, fn ($v) => $v !== null && $v !== '')) ? in_array(1, $legacy) : 1;
        }
        if (!in_array($type, ['created', 'modified', 'published'], true)) {
            $type = $params->get('show_publish_date', 1) ? 'published' : ($params->get('show_modify_date') ? 'modified' : 'created');
        }
        return [(bool) $show, $type];
    }
    public static function value(object $item, $params = null): ?string
    {
        [$show, $type] = self::options($params);
        if (!$show) { return null; }
        $value = $item->{['created' => 'created', 'modified' => 'modified', 'published' => 'publish_up'][$type]} ?? null;
        if (!$value || str_starts_with($value, '0000-')) { $value = $item->created ?? null; }
        return $value && !str_starts_with($value, '0000-') ? (string) $value : null;
    }
    public static function render(object $item, $params = null, bool $label = true): string
    {
        $value = self::value($item, $params);
        if (!$value) { return ''; }
        [, $type] = self::options($params);
        $prefix = ['created' => 'Created: ', 'modified' => 'Last Updated: ', 'published' => 'Published: '][$type];
        return ($label ? $prefix : '') . '<time datetime="' . htmlspecialchars(HTMLHelper::_('date', $value, 'c'), ENT_QUOTES, 'UTF-8') . '">' . HTMLHelper::_('date', $value, 'DATE_FORMAT_LC3') . '</time>';
    }
}
