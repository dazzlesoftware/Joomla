<?php

namespace Joomla\Component\Academy\Site\Helper;

defined('_JEXEC') or die;

final class ListExcerptHelper
{
    /**
     * Render a post's list-view body: the full stored summary when a manual
     * Read More split already exists, otherwise an automatically
     * truncated excerpt (forcing the Continue Reading link to show).
     */
    public static function render(object $item, object $params): string
    {
        $excerpt = (string) ($item->excerpt ?? '');
        if (trim($excerpt) !== '') {
            $item->readmore = 1;

            return '<p>' . nl2br(htmlspecialchars($excerpt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>';
        }

        if (!empty($item->readmore)) {
            return (string) $item->summary;
        }

        $limit = (int) $params->get('list_excerpt_length', 400);
        if ($limit <= 0) {
            return (string) $item->summary;
        }

        // Non-visible block contents must be removed before stripping tags,
        // otherwise embedded CSS and JavaScript become part of the excerpt.
        $html = preg_replace('~<(style|script|template)\b[^>]*>.*?</\1\s*>~is', '', (string) $item->summary);
        $html = preg_replace('~<(?:br\b[^>]*|/(?:p|div|h[1-6]|li|ul|ol|blockquote|section|tr|td))\s*>~i', ' ', $html);
        $plain = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = trim(preg_replace('/[\s\x{00a0}]+/u', ' ', $plain));
        if (mb_strlen($plain) <= $limit) {
            return (string) $item->summary;
        }

        $excerpt = mb_substr($plain, 0, $limit);
        $excerpt = preg_replace('/\s+\S*$/u', '', $excerpt);
        $item->readmore = 1;

        return '<p>' . htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') . '&#8230;</p>';
    }
}
