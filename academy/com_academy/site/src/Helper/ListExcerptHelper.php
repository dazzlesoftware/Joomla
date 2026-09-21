<?php

namespace Joomla\Component\Academy\Site\Helper;

use Joomla\CMS\Component\ComponentHelper;

defined('_JEXEC') or die;

final class ListExcerptHelper
{
    /** Menu/module blanks inherit component values; explicit zero remains meaningful. */
    public static function settings(object $params): object
    {
        $global = ComponentHelper::getParams('com_academy');
        $resolved = clone $global;
        foreach ($params->toArray() as $key => $value) {
            if ($value !== '' && $value !== null) {
                $resolved->set($key, $value);
            }
        }
        foreach (['content' => 'list_excerpt_length', 'paragraph' => 'truncation_paragraphs'] as $kind => $key) {
            if ((string) $params->get('truncation_' . $kind . '_override', '') === '0') {
                $resolved->set($key, $global->get($key, $kind === 'content' ? 400 : 1));
            }
        }
        return $resolved;
    }

    /** Only list bodies are shortened. Authored excerpts and Read More boundaries win. */
    public static function render(object $item, object $params): string
    {
        $params = self::settings($params);
        $summary = (string) ($item->summary ?? '');
        if (trim((string) ($item->excerpt ?? '')) !== '') {
            $item->readmore = 1;
            return '<p>' . nl2br(self::escape($item->excerpt)) . '</p>';
        }
        if (!empty($item->readmore) || !(int) $params->get('truncation_enabled', 1)) {
            return $summary;
        }
        $type = (string) $params->get('truncation_type', 'characters');
        $limit = (int) $params->get(in_array($type, ['paragraphs', 'breaks'], true) ? 'truncation_paragraphs' : 'list_excerpt_length', in_array($type, ['paragraphs', 'breaks'], true) ? 1 : 400);
        if ($limit <= 0) {
            return $summary;
        }

        // Protect whole plugin media tokens before parsing HTML. They are expanded by
        // the normal content-prepare event after the selected media has been positioned.
        $tokens = [];
        $paired = 'accordion|alert|button|columns|quote|section|tabs';
        preg_match_all('~\{(/?)(' . $paired . ')\b[^}]*\}~i', $summary, $matches, PREG_OFFSET_CAPTURE);
        $stack = $ranges = [];
        foreach ($matches[0] as $i => [$tag, $offset]) {
            $kind = strtolower($matches[2][$i][0]);
            if ($matches[1][$i][0] === '') {
                $stack[] = [$kind, $offset];
            } elseif ($stack && end($stack)[0] === $kind) {
                [$kind, $begin] = array_pop($stack);
                if (!$stack) { $ranges[] = [$begin, $offset + strlen($tag) - $begin, $kind]; }
            }
        }
        $html = $summary;
        foreach (array_reverse($ranges) as [$begin, $length, $kind]) {
            $index = count($tokens);
            $tokens[$index] = [$kind, substr($summary, $begin, $length)];
            $html = substr_replace($html, '<span data-excerpt-media="' . $index . '"></span>', $begin, $length);
        }
        $html = preg_replace_callback('~\{(video|audio|comparison|embed)\s+[^}]*\}~i', static function ($match) use (&$tokens) {
            $index = count($tokens);
            $kind = strtolower($match[1]);
            if ($kind === 'embed' && preg_match('~provider=(?:"|&quot;)polls(?:"|&quot;)~i', $match[0])) {
                $kind = 'poll';
            }
            $tokens[$index] = [$kind, $match[0]];
            return '<span data-excerpt-media="' . $index . '"></span>';
        }, $html);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new \DOMXPath($dom);
        foreach (iterator_to_array($xpath->query('//script|//style|//template')) as $node) {
            $node->parentNode?->removeChild($node);
        }
        $media = [];
        $walk = static function ($node) use (&$walk, &$media, $tokens, $dom) {
            foreach (iterator_to_array($node->childNodes) as $child) {
                if (!$child instanceof \DOMElement) { continue; }
                $tag = strtolower($child->tagName);
                $class = ' ' . $child->getAttribute('class') . ' ';
                $kind = null;
                $markup = null;
                if ($child->hasAttribute('data-excerpt-media')) {
                    [$kind, $markup] = $tokens[(int) $child->getAttribute('data-excerpt-media')];
                } elseif ($tag === 'hr' && !$child->hasAttribute('id') && !$child->hasAttribute('class')) {
                    $kind = 'rule';
                } elseif (str_contains($class, ' post-gallery ')) {
                    $kind = 'gallery';
                } elseif ($tag === 'figure' && $child->getElementsByTagName('img')->length) {
                    $kind = 'image';
                } elseif ($tag === 'figure' && $child->getElementsByTagName('audio')->length) {
                    $kind = 'audio';
                } elseif (in_array($tag, ['img', 'picture', 'video', 'audio', 'iframe'], true)) {
                    $kind = match ($tag) {
                        'img', 'picture' => 'image',
                        'audio' => 'audio',
                        'iframe' => preg_match('~(?:soundcloud|spotify)\.com~i', $child->getAttribute('src')) ? 'audio' : 'video',
                        default => 'video',
                    };
                }
                if ($kind !== null) {
                    $media[] = [$kind, $markup ?? $dom->saveHTML($child)];
                    $node->removeChild($child);
                } else {
                    $walk($child);
                }
            }
        };
        $body = $dom->getElementsByTagName('body')->item(0);
        $walk($body);
        // Removed widgets leave editor wrapper paragraphs and NBSP spacer rows.
        // Prune these only when extracting blocks, preserving normal authored content.
        if ($media) {
            do {
                $removed = false;
                foreach (array_reverse(iterator_to_array($xpath->query('//body//p|//body//div|//body//span'))) as $node) {
                    $visibleText = preg_replace('/[\s\x{00a0}]+/u', '', $node->textContent);
                    $hasContent = $xpath->query('.//*[not(self::br or self::span)]', $node)->length > 0;
                    if ($visibleText === '' && !$hasContent) {
                        $node->parentNode?->removeChild($node);
                        $removed = true;
                    }
                }
            } while ($removed);
        }
        $html = $dom->saveHTML($body);
        $retainedHtml = preg_replace('~^<body>|</body>$~', '', $html);
        // Do not expose or split remaining editor/plugin directives in a plain excerpt.
        $html = preg_replace('~\{/?[a-z][^{}]*\}~i', '', $html);
        $separator = $type === 'breaks' ? '~<br\b[^>]*>~i' : '~</p\s*>~i';
        $units = preg_split($separator, $html);
        $plain = static function ($text) {
            $text = preg_replace('~<br\b[^>]*>|</(?:p|div|li|h[1-6]|section|blockquote)>~i', ' ', $text);
            return trim(preg_replace('/[\s\x{00a0}]+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        };
        $source = preg_replace('~<(script|style|template)\b[^>]*>.*?</\1\s*>~is', '', $summary);
        $source = preg_replace('~\{/?[a-z][^{}]*\}~i', '', $source);
        $sourceText = $plain($source);
        $text = $plain($html);
        $needsTextTruncation = match ($type) {
            'paragraphs', 'breaks' => count(array_filter(array_map($plain, preg_split($separator, $source)), static fn ($unit) => $unit !== '')) > $limit,
            'words' => count(preg_split('/\s+/u', $sourceText, -1, PREG_SPLIT_NO_EMPTY)) > $limit,
            default => mb_strlen($sourceText) > $limit,
        };
        // Embeds can expand into large widgets despite having little or no text.
        // Apply their placement independently of whether the text exceeds its limit.
        if (!$needsTextTruncation) {
            if (!$media) { return $summary; }
            $result = $retainedHtml;
        } elseif (in_array($type, ['paragraphs', 'breaks'], true)) {
            $units = array_values(array_filter(array_map($plain, $units), static fn ($unit) => $unit !== ''));
            $result = implode('', array_map(static fn ($unit) => '<p>' . self::escape($unit) . '</p>', array_slice($units, 0, $limit)));
        } elseif ($type === 'words') {
            $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
            $result = '<p>' . self::escape(implode(' ', array_slice($words, 0, $limit))) . '&#8230;</p>';
        } else {
            $cut = mb_substr($text, 0, $limit);
            if (preg_match('/\s/u', $cut) && !preg_match('/\s/u', mb_substr($text, $limit, 1))) {
                $cut = preg_replace('/\s+\S*$/u', '', $cut);
            }
            $result = '<p>' . self::escape(rtrim($cut)) . '&#8230;</p>';
        }
        $top = $bottom = '';
        $removedContent = $needsTextTruncation;
        foreach ($media as [$kind, $markup]) {
            $position = $params->get('truncation_' . $kind . '_position', 'hide');
            $block = '<div class="excerpt-block my-3">' . $markup . '</div>';
            if ($position === 'top') { $top .= $block; }
            if ($position === 'bottom') { $bottom .= $block; }
            if (!in_array($position, ['top', 'bottom'], true)) { $removedContent = true; }
        }
        $item->readmore = $removedContent ? (int) $params->get('truncation_readmore', 1) : 0;
        return $top . $result . $bottom;
    }

    private static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
