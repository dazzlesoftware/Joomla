<?php

namespace Joomla\Plugin\Content\Video\Extension;

use Joomla\CMS\Editor\Button\Button;
use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Event\Editor\EditorButtonsSetupEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

defined('_JEXEC') or die;

final class Video extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepare'     => 'onContentPrepare',
            'onEditorButtonsSetup' => 'onEditorButtonsSetup',
        ];
    }

    public function onEditorButtonsSetup(EditorButtonsSetupEvent $event): void
    {
        $component = 'com_' . $this->_type;

        if ($this->getApplication()->getInput()->getCmd('option') !== $component
            || in_array($this->_name, $event->getDisabledButtons(), true)) {
            return;
        }

        $wa = $this->getApplication()->getDocument()->getWebAssetManager();
        $assetName = 'editor-button.' . $this->_type . '_' . $this->_name;

        if (!$wa->assetExists('script', $assetName)) {
            $wa->registerScript(
                $assetName,
                'plg_' . $this->_type . '_video/video-button.js',
                ['version' => '1.1.1'],
                ['type' => 'module'],
                ['editors']
            );
        }

        $this->loadLanguage();
        $event->getButtonsRegistry()->add(new Button(
            $this->_type . '_' . $this->_name,
            [
                'action'  => 'insert-' . $this->_type . '-video',
                'text'    => Text::_('PLG_' . strtoupper($this->_type) . '_VIDEO_BUTTON'),
                'icon'    => 'play',
                'iconSVG' => '<svg viewBox="0 0 32 32" width="24" height="24"><path d="M4 2v28l24-14L4 2zm4 7l12 7-12 7V9z"></path></svg>',
                'name'    => $this->_type . '_' . $this->_name,
            ]
        ));
    }

    public function onContentPrepare(ContentPrepareEvent $event): void
    {
        if (!str_starts_with($event->getContext(), 'com_' . $this->_type . '.')) {
            return;
        }

        $item = $event->getItem();

        if (!is_object($item) || !property_exists($item, 'text') || !is_string($item->text) || !str_contains($item->text, '{video ')) {
            return;
        }

        if ($event->getContext() === 'com_finder.indexer') {
            $item->text = preg_replace('/\{video\s+[^}]+\}/i', '', $item->text);
            return;
        }

        $item->text = preg_replace_callback('/\{video\s+([^}]+)\}/i', fn(array $match) => $this->render($this->attributes($match[1])), $item->text);
    }

    private function attributes(string $source): array
    {
        $attributes = [];
        preg_match_all('/([a-z][a-z0-9_-]*)\s*=\s*"([^"]*)"/i', $source, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $attributes[strtolower($match[1])] = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $attributes;
    }

    private function render(array $options): string
    {
        $url = trim($options['url'] ?? '');
        if ($url === '') {
            return '';
        }

        $type = ($options['type'] ?? 'embed') === 'local' ? 'local' : 'embed';
        $title = $this->escape($options['title'] ?? 'Video');
        $ratio = ['16by9' => '16 / 9', '9by16' => '9 / 16', '4by3' => '4 / 3', '1by1' => '1 / 1'][$options['ratio'] ?? '16by9'] ?? '16 / 9';

        if ($type === 'local') {
            return $this->renderLocal($url, $title, $ratio, $options);
        }

        $embed = $this->embedUrl($url, $options);
        if ($embed === null) {
            return '<p class="alert alert-warning">Unsupported video URL.</p>';
        }

        return '<div class="joomla-video" style="aspect-ratio:' . $ratio . ';width:100%;overflow:hidden">'
            . '<iframe src="' . $this->escape($embed) . '" title="' . $title . '" loading="lazy" '
            . 'style="width:100%;height:100%;border:0" allow="accelerometer; autoplay; encrypted-media; picture-in-picture" '
            . 'allowfullscreen></iframe></div>';
    }

    private function renderLocal(string $url, string $title, string $ratio, array $options): string
    {
        $url = $this->safeMediaUrl($url);
        if ($url === null) {
            return '<p class="alert alert-warning">Invalid video file URL.</p>';
        }

        $flags = [];
        foreach (['controls', 'autoplay', 'muted', 'loop'] as $flag) {
            if (($options[$flag] ?? '0') === '1') {
                $flags[] = $flag;
            }
        }

        $poster = isset($options['poster']) ? $this->safeMediaUrl($options['poster']) : null;
        $subtitle = isset($options['subtitle']) ? $this->safeMediaUrl($options['subtitle']) : null;
        $controlsList = ($options['nodownload'] ?? '0') === '1' ? ' controlslist="nodownload"' : '';
        $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $mime = ['mp4' => 'video/mp4', 'webm' => 'video/webm', 'ogv' => 'video/ogg', 'ogg' => 'video/ogg'][$extension] ?? 'video/mp4';

        $html = '<div class="joomla-video" style="aspect-ratio:' . $ratio . ';width:100%;overflow:hidden">';
        $html .= '<video aria-label="' . $title . '" ' . implode(' ', $flags) . $controlsList . ' playsinline preload="metadata" '
            . ($poster ? 'poster="' . $this->escape($poster) . '" ' : '') . 'style="width:100%;height:100%;object-fit:contain">';
        $html .= '<source src="' . $this->escape($url) . '" type="' . $mime . '">';
        if ($subtitle) {
            $html .= '<track src="' . $this->escape($subtitle) . '" kind="subtitles" srclang="'
                . $this->escape($options['srclang'] ?? 'en') . '" label="' . $this->escape($options['label'] ?? 'English') . '" default>';
        }
        $html .= '</video></div>';

        return $html;
    }

    private function embedUrl(string $url, array $options): ?string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);
        $id = null;

        if ($host === 'youtu.be') {
            $id = trim($path, '/');
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            $id = $query['v'] ?? (preg_match('~/(?:shorts|embed)/([^/?]+)~', $path, $match) ? $match[1] : null);
        }

        if ($id !== null && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id)) {
            $domain = ($options['nocookie'] ?? '0') === '1' ? 'www.youtube-nocookie.com' : 'www.youtube.com';
            $params = ['rel' => '0'];
            foreach (['start', 'end'] as $key) {
                if (isset($options[$key]) && ctype_digit($options[$key])) {
                    $params[$key] = $options[$key];
                }
            }
            if (($options['autoplay'] ?? '0') === '1') $params['autoplay'] = '1';
            if (($options['muted'] ?? '0') === '1') $params['mute'] = '1';
            return 'https://' . $domain . '/embed/' . $id . '?' . http_build_query($params);
        }

        if (in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true) && preg_match('~/(?:video/)?([0-9]+)~', $path, $match)) {
            $params = [];
            if (($options['autoplay'] ?? '0') === '1') $params['autoplay'] = '1';
            if (($options['muted'] ?? '0') === '1') $params['muted'] = '1';
            if (($options['loop'] ?? '0') === '1') $params['loop'] = '1';
            return 'https://player.vimeo.com/video/' . $match[1] . ($params ? '?' . http_build_query($params) : '');
        }

        return null;
    }

    private function safeMediaUrl(string $url): ?string
    {
        $url = trim($url);
        if (preg_match('~^https?://~i', $url) || preg_match('~^(?:/|images/|media/)~', $url)) {
            return $url;
        }
        return null;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
