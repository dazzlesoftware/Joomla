<?php
namespace Joomla\Component\Academy\Site\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

final class SeoHelper
{
    public static function url(string $url): string
    {
        $url = HTMLHelper::cleanImageURL(trim($url))->url;
        if ($url === '') { return ''; }
        if (preg_match('~^https?://~i', $url)) { return filter_var($url, FILTER_VALIDATE_URL) ? $url : ''; }
        if (preg_match('~^[a-z][a-z0-9+.-]*:|^//~i', $url)) { return ''; }
        return str_starts_with($url, '/') ? Uri::getInstance()->toString(['scheme','host','port']) . $url : Uri::root() . $url;
    }
    public static function apply(object $doc, object $item): void
    {
        $meta = ($item->metadata ?? null) instanceof Registry ? $item->metadata : new Registry($item->metadata ?? '{}');
        $media = new Registry($item->media ?? '{}');
        $seoTitle = trim((string) $meta->get('seo_title', ''));
        if ($seoTitle !== '') { $doc->setTitle($seoTitle); }
        if (!empty($item->metakey)) { $doc->setMetaData('keywords', $item->metakey); }
        $robots = $meta->get('robots', '');
        if (in_array($robots, ['index, follow','noindex, follow','index, nofollow','noindex, nofollow'], true)) { $doc->setMetaData('robots', $robots); }
        $canonical = trim((string) $meta->get('canonical', ''));
        if (!preg_match('~^https?://~i', $canonical) || !filter_var($canonical, FILTER_VALIDATE_URL)) { $canonical = ''; }
        if ($canonical !== '') { $doc->addHeadLink($canonical, 'canonical'); }
        $type = $meta->get('og_type') ?: 'article';
        if (!in_array($type, ['article','website','video.movie','video.tv_show','music.album','book','profile'], true)) { $type = 'article'; }
        $title = $meta->get('og_title') ?: ($seoTitle ?: $item->title);
        $description = $meta->get('og_description') ?: (($item->metadesc ?? '') ?: ($item->excerpt ?? ''));
        $image = self::url($meta->get('og_image') ?: (string) $media->get('featured_image', ''));
        $alt = $meta->get('image_alt') ?: (string) $media->get('featured_image_alt', '');
        foreach (['og:type'=>$type, 'og:title'=>$title, 'og:description'=>$description, 'og:image'=>$image, 'og:image:alt'=>$alt, 'og:url'=>$canonical ?: Uri::getInstance()->toString(['scheme','host','port','path','query'])] as $key=>$value) {
            if ($value !== '') { $doc->setMetaData($key, strip_tags($value), 'property'); }
        }
        $card = $meta->get('twitter_card', 'summary_large_image');
        $doc->setMetaData('twitter:card', $image && $card === 'summary_large_image' ? 'summary_large_image' : 'summary');
        foreach (['twitter:title'=>$title,'twitter:description'=>$description,'twitter:image'=>$image,'twitter:image:alt'=>$alt] as $key=>$value) { if ($value !== '') { $doc->setMetaData($key, strip_tags($value)); } }
        if ($type === 'article') {
            foreach (['article:published_time'=>($item->publish_up ?? '') ?: ($item->created ?? ''),'article:modified_time'=>$item->modified ?? ''] as $key=>$value) {
                if ($value && $value !== '0000-00-00 00:00:00' && ($timestamp = strtotime($value)) !== false) { $doc->setMetaData($key, gmdate(DATE_ATOM, $timestamp), 'property'); }
            }
        }
    }
}
