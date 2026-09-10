<?php

namespace Joomla\Plugin\Codex\Blocks\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Editor\Button\Button;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Event\Editor\EditorButtonsSetupEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

final class Blocks extends CMSPlugin implements SubscriberInterface
{
    private const FAMILY = 'codex';
    private int $accordionSequence = 0;
    private int $tabSequence = 0;
    private int $columnSequence = 0;

    public static function getSubscribedEvents(): array
    {
        return ['onEditorButtonsSetup' => 'onEditorButtonsSetup', 'onContentPrepare' => 'onContentPrepare'];
    }

    public function onEditorButtonsSetup(EditorButtonsSetupEvent $event): void
    {
        if ($this->getApplication()->getInput()->getCmd('option') !== 'com_' . self::FAMILY
            || in_array($this->_name, $event->getDisabledButtons(), true)) {
            return;
        }

        $document = $this->getApplication()->getDocument();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        try {
            $items = $db->setQuery($db->createQuery()->select(['id', 'title'])->from('#__' . self::FAMILY . '_polls')->where('state=1')->order('title'))->loadObjectList();
            $polls = array_map(fn($item) => ['id' => (int) $item->id, 'title' => (string) $item->title], $items);
        } catch (\Throwable $error) {
            $polls = [];
        }
        $document->addScriptOptions(self::FAMILY . '.polls', $polls);
        $wa = $document->getWebAssetManager();
        $buttons = [
            'tabs' => ['Tabs', 'folder'], 'columns' => ['Columns', 'columns'],
            'section' => ['Section', 'square'], 'accordion' => ['Accordion', 'arrow-down-4'],
            'alert' => ['Alert', 'warning'], 'quote' => ['Quote', 'quote'], 'button' => ['Button', 'square'],
            'audio' => ['Audio', 'music'],
            'comparison' => ['Comparison', 'images'],
            'rule' => ['Rule', 'minus'], 'polls' => ['Poll', 'question'], 'embed' => ['Embed', 'share-alt'],
        ];
        foreach ($buttons as $type => [$label, $icon]) {
            $name = self::FAMILY . '_' . $type;
            $asset = 'editor-button.' . $name;
            if (!$wa->assetExists('script', $asset)) {
                $wa->registerScript($asset, 'plg_' . self::FAMILY . '_blocks/blocks-button.js', ['version' => '1.2.0'], ['type' => 'module'], ['editors']);
            }
            $event->getButtonsRegistry()->add(new Button($name, [
                'action' => 'insert-' . self::FAMILY . '-' . $type,
                'text' => $label, 'icon' => $icon, 'name' => $name,
            ]));
        }
    }

    public function onContentPrepare(ContentPrepareEvent $event): void
    {
        if (!str_starts_with($event->getContext(), 'com_' . self::FAMILY . '.')) return;
        $item = $event->getItem();
        if (!is_object($item) || !isset($item->text) || !is_string($item->text)) return;
        if ($event->getContext() === 'com_finder.indexer') {
            $item->text = preg_replace('/\{embed\s+[^}]+\}/i', '', $item->text);
            $item->text = preg_replace('~\{quote(?:\s+cite=(?:"|&quot;).*?(?:"|&quot;))?\s+template=(?:"|&quot;).*?(?:"|&quot;)\}(.*?)\{/quote\}~is', '$1', $item->text);
            $item->text = preg_replace('~\{section\s+title=(?:"|&quot;)(.*?)(?:"|&quot;)\}(.*?)\{/section\}~is', '$1 $2', $item->text);
            $item->text = preg_replace('~\{tabs\s+template=(?:"|&quot;).*?(?:"|&quot;)\}(.*?)\{/tabs\}~is', '$1', $item->text);
            $item->text = preg_replace('~\{tab\s+title=(?:"|&quot;)(.*?)(?:"|&quot;)(?:\s+icon=(?:"|&quot;).*?(?:"|&quot;))?\}(.*?)\{/tab\}~is', '$1 $2', $item->text);
            return;
        }
        if (str_contains($item->text, '{tabs')) {
            $item->text = preg_replace_callback(
                '~(?:<p>\s*)?\{tabs\s+template=(?:"|&quot;)(.*?)(?:"|&quot;)\}(.*?)\{/tabs\}(?:\s*</p>)?~is',
                fn($match) => $this->renderTabs($match[2], $match[1]),
                $item->text
            );
        }
        if (str_contains($item->text, '{section ')) {
            $item->text = preg_replace_callback(
                '~(?:<p>\s*)?\{section\s+title=(?:"|&quot;)(.*?)(?:"|&quot;)\}(.*?)\{/section\}(?:\s*</p>)?~is',
                fn($match) => $this->renderSection($match[1], $match[2]),
                $item->text
            );
        }
        if (str_contains($item->text, '{alert ')) {
            $item->text = preg_replace_callback(
                '~(?:<p>\s*)?\{alert\s+type=(?:"|&quot;)([a-z]+)(?:"|&quot;)\}(.*?)\{/alert\}(?:\s*</p>)?~is',
                fn($match) => $this->renderAlert($match[1], $match[2]),
                $item->text
            );
        }
        if (str_contains($item->text, '{quote')) {
            $item->text = preg_replace_callback(
                '~(?:<p>\s*)?\{quote(?:\s+cite=(?:"|&quot;)(.*?)(?:"|&quot;))?\s+template=(?:"|&quot;)(.*?)(?:"|&quot;)\}(.*?)\{/quote\}(?:\s*</p>)?~is',
                fn($match) => $this->renderQuote($match[1] ?? '', $match[3], $match[2]),
                $item->text
            );
        }
        if (str_contains($item->text, '{button ')) {
            $item->text = preg_replace_callback(
                '~(?:<p>\s*)?\{button\s+url=(?:"|&quot;)(.*?)(?:"|&quot;)\s+style=(?:"|&quot;)([a-z]+)(?:"|&quot;)\}(.*?)\{/button\}(?:\s*</p>)?~is',
                fn($match) => $this->renderButton($match[1], $match[2], $match[3]),
                $item->text
            );
        }
        if (str_contains($item->text, '{audio ')) {
            $item->text = preg_replace_callback(
                '~(?:<p>\s*)?\{audio\s+url=(?:"|&quot;)(.*?)(?:"|&quot;)\s+title=(?:"|&quot;)(.*?)(?:"|&quot;)\s+autoplay=(?:"|&quot;)([01])(?:"|&quot;)\}(?:\s*</p>)?~is',
                fn($match) => $this->renderAudio($match[1], $match[2], $match[3] === '1'),
                $item->text
            );
        }
        if (str_contains($item->text, '{columns')) {
            $item->text = preg_replace_callback(
                '~(?:<p>\s*)?\{columns\s+template=(?:"|&quot;)(.*?)(?:"|&quot;)\}(.*?)\{/columns\}(?:\s*</p>)?~is',
                fn($match) => $this->renderColumns($match[2], $match[1]),
                $item->text
            );
        }
        if (str_contains($item->text, '{comparison ')) {
            $item->text = preg_replace_callback(
                '~(?:<p>\s*)?\{comparison\s+before=(?:"|&quot;)(.*?)(?:"|&quot;)\s+after=(?:"|&quot;)(.*?)(?:"|&quot;)\s+beforealt=(?:"|&quot;)(.*?)(?:"|&quot;)\s+afteralt=(?:"|&quot;)(.*?)(?:"|&quot;)\s*\}(?:\s*</p>)?~is',
                fn($match) => $this->renderComparison($match[1], $match[2], $match[3], $match[4]),
                $item->text
            );
        }
        if (str_contains($item->text, '{accordion')) {
            $item->text = preg_replace_callback(
                '~(?:<p>\s*)?\{accordion\s+template=(?:"|&quot;)(.*?)(?:"|&quot;)\}(.*?)\{/accordion\}(?:\s*</p>)?~is',
                fn($match) => $this->renderAccordion($match[2], $match[1]),
                $item->text
            );
        }
        if (str_contains($item->text, 'post-accordion')) {
            $this->getApplication()->getDocument()->getWebAssetManager()->useScript('bootstrap.collapse');
        }
        if (str_contains($item->text, '{embed ')) {
            $item->text = preg_replace_callback('/\{embed\s+([^}]+)\}/i', fn($m) => $this->renderEmbed($this->attributes($m[1])), $item->text);
        }
    }

    private function renderAlert(string $style, string $content): string
    {
        $style = in_array(strtolower($style), ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'], true)
            ? strtolower($style) : 'info';

        return '<div class="alert alert-' . $style . '" role="alert">' . $this->paragraphs($content) . '</div>';
    }

    private function renderSection(string $title, string $content): string
    {
        $title = htmlspecialchars(
            html_entity_decode(trim(strip_tags($title)), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        return '<section class="post-section py-4 my-4 border-top border-bottom">'
            . ($title !== '' ? '<h2 class="post-section__title h3 mb-3">' . $title . '</h2>' : '')
            . '<div class="post-section__content">' . $this->paragraphs($content) . '</div>'
            . '</section>';
    }

    private function renderTabs(string $source, string $override): string
    {
        preg_match_all(
            '~\{tab\s+title=(?:"|&quot;)(.*?)(?:"|&quot;)(?:\s+icon=(?:"|&quot;)(.*?)(?:"|&quot;))?\}(.*?)\{/tab\}~is',
            $source,
            $matches,
            PREG_SET_ORDER
        );
        if (!$matches) return '';

        $params = ComponentHelper::getParams('com_' . self::FAMILY);
        $templates = ['classic', 'pills', 'underline', 'cards', 'colorbar', 'icons'];
        $globalTemplate = (string) $params->get('tab_template', 'classic');
        $template = $override !== 'global' && in_array($override, $templates, true) ? $override : $globalTemplate;
        $template = in_array($template, $templates, true) ? $template : 'classic';
        $vertical = $params->get('tab_mode', 'horizontal') === 'vertical';
        $colorMode = (string) $params->get('tab_color_mode', 'bootstrap');
        $bootstrapColor = (string) $params->get('tab_bootstrap_color', 'primary');
        $bootstrapColor = in_array($bootstrapColor, ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'], true) ? $bootstrapColor : 'primary';
        $customColor = (string) $params->get('tab_custom_color', '#0d6efd');
        $customColor = preg_match('/^#[0-9a-f]{6}$/i', $customColor) ? $customColor : '#0d6efd';
        $color = $colorMode === 'custom' ? $customColor : 'var(--bs-' . $bootstrapColor . ',#0d6efd)';
        $contrast = $colorMode === 'custom' ? $this->contrastColor($customColor) : (in_array($bootstrapColor, ['warning', 'info', 'light'], true) ? '#212529' : '#ffffff');
        $this->tabSequence++;
        $id = self::FAMILY . '-tabs-' . $this->tabSequence;
        $navType = in_array($template, ['pills', 'icons', 'colorbar'], true) || $vertical ? 'nav-pills' : 'nav-tabs';
        $navClasses = 'nav ' . $navType;
        if ($vertical) $navClasses .= ' flex-column flex-shrink-0 me-3';
        if ($template === 'icons' && !$vertical) $navClasses .= ' nav-fill gap-2';
        if ($template === 'underline') $navClasses .= ' border-0 gap-3';
        if ($template === 'colorbar') $navClasses .= ' rounded-top p-2 gap-1';
        $navigation = '<div class="' . $navClasses . '" id="' . $id . '-nav" role="tablist"' . ($vertical ? ' aria-orientation="vertical"' : '') . '>';
        $contentClasses = 'tab-content flex-grow-1 p-3';
        if ($template === 'cards') $contentClasses .= ' card-body';
        else $contentClasses .= ' border rounded-bottom';
        $panes = '<div class="' . $contentClasses . '" id="' . $id . '-content">';
        $allowedIcons = ['home','user','check','info','star','heart','music','camera','video','cog','envelope','search','question','bookmark'];
        foreach ($matches as $index => $match) {
            $number = $index + 1;
            $buttonId = $id . '-tab-' . $number;
            $paneId = $id . '-pane-' . $number;
            $active = $index === 0;
            $title = htmlspecialchars(
                html_entity_decode(trim(strip_tags($match[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
            $icon = strtolower(trim(strip_tags($match[2] ?? '')));
            $icon = in_array($icon, $allowedIcons, true) ? $icon : '';
            $iconHtml = $icon !== '' ? '<span class="icon-' . $icon . ($template === 'icons' ? ' fs-3 d-block mb-1' : ' me-2') . '" aria-hidden="true"></span>' : '';
            $navigation .= '<button class="nav-link' . ($active ? ' active' : '') . '" id="' . $buttonId . '" data-bs-toggle="tab" data-bs-target="#' . $paneId . '" type="button" role="tab" aria-controls="' . $paneId . '" aria-selected="' . ($active ? 'true' : 'false') . '">' . $iconHtml . $title . '</button>';
            $panes .= '<div class="tab-pane fade' . ($active ? ' show active' : '') . '" id="' . $paneId . '" role="tabpanel" aria-labelledby="' . $buttonId . '" tabindex="0">' . $this->paragraphs($match[3]) . '</div>';
        }
        $this->getApplication()->getDocument()->getWebAssetManager()->useScript('bootstrap.tab');

        $style = '<style>#' . $id . '{--post-tab-color:' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . ';--post-tab-contrast:' . $contrast . '}#' . $id . ' .nav-link.active{background:var(--post-tab-color);border-color:var(--post-tab-color);color:var(--post-tab-contrast)}#' . $id . '.post-tabs--underline .nav-link{border:0;border-bottom:.2rem solid transparent;border-radius:0;background:transparent;color:inherit}#' . $id . '.post-tabs--underline .nav-link.active{border-bottom-color:var(--post-tab-color);background:transparent;color:var(--post-tab-color)}#' . $id . '.post-tabs--colorbar>#' . $id . '-nav{background:var(--post-tab-color)}#' . $id . '.post-tabs--colorbar>#' . $id . '-nav .nav-link{color:var(--post-tab-contrast)}#' . $id . '.post-tabs--colorbar>#' . $id . '-nav .nav-link.active{background:var(--bs-body-bg,#fff);color:var(--post-tab-color)}</style>';
        $wrapperClasses = 'post-tabs post-tabs--' . $template . ' my-4' . ($vertical ? ' d-flex align-items-start' : '') . ($template === 'cards' ? ' card shadow-sm' : '');

        return $style . '<div class="' . $wrapperClasses . '" id="' . $id . '">' . $navigation . '</div>' . $panes . '</div></div>';
    }

    private function renderQuote(string $citation, string $content, string $override): string
    {
        $params = ComponentHelper::getParams('com_' . self::FAMILY);
        $templates = ['simple', 'color', 'framed', 'card', 'panel', 'minimal'];
        $globalTemplate = (string) $params->get('quote_template', 'simple');
        $template = $override !== 'global' && in_array($override, $templates, true) ? $override : $globalTemplate;
        $template = in_array($template, $templates, true) ? $template : 'simple';
        $colorMode = $params->get('quote_color_mode', 'bootstrap');
        $bootstrapColor = $params->get('quote_bootstrap_color', 'primary');
        $bootstrapColor = in_array($bootstrapColor, ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'], true) ? $bootstrapColor : 'primary';
        $customColor = (string) $params->get('quote_custom_color', '#0d6efd');
        $customColor = preg_match('/^#[0-9a-f]{6}$/i', $customColor) ? $customColor : '#0d6efd';
        $color = $colorMode === 'custom' ? $customColor : 'var(--bs-' . $bootstrapColor . ',#0d6efd)';
        $colorStyle = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');
        $quote = $this->paragraphs($content);
        $citation = htmlspecialchars(
            html_entity_decode(trim(strip_tags($citation)), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $caption = $citation !== '' ? '<figcaption class="post-quote__citation mt-3">&mdash; ' . $citation . '</figcaption>' : '';
        $mark = '<span class="post-quote__mark display-4 fw-bold lh-1 d-block mb-2" aria-hidden="true">&ldquo;</span>';

        if ($template === 'color') {
            $colorClass = $colorMode === 'custom' ? '' : ' text-bg-' . $bootstrapColor;
            $customStyle = $colorMode === 'custom' ? 'background:' . $colorStyle . ';color:' . $this->contrastColor($customColor) . ';' : '';
            return '<figure class="post-quote post-quote--color rounded-3 p-4 my-4 shadow-sm' . $colorClass . '" style="' . $customStyle . '">'
                . '<blockquote class="blockquote mb-0">' . $mark . $quote . '</blockquote>'
                . $caption . '</figure>';
        }

        if ($template === 'framed') {
            return '<figure class="post-quote post-quote--framed border rounded-1 p-4 my-4 position-relative" style="border-left:.5rem solid ' . $colorStyle . ' !important">'
                . '<blockquote class="blockquote mb-0"><span class="display-4 fw-bold lh-1 d-block mb-3" style="color:' . $colorStyle . '" aria-hidden="true">&ldquo;</span>' . $quote . '</blockquote>' . $caption . '</figure>';
        }
        if ($template === 'card') {
            return '<figure class="post-quote post-quote--card card border-0 shadow-sm my-4"><div class="card-body p-4">'
                . '<div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 text-white" style="width:3.5rem;height:3.5rem;background:' . $colorStyle . '"><span class="fs-2 fw-bold" aria-hidden="true">&ldquo;</span></div>'
                . '<blockquote class="blockquote mb-0">' . $quote . '</blockquote>' . $caption . '</div></figure>';
        }
        if ($template === 'panel') {
            return '<figure class="post-quote post-quote--panel bg-dark text-white rounded-2 p-4 my-4" style="border-left:.5rem solid ' . $colorStyle . '">'
                . '<blockquote class="blockquote mb-0" style="color:' . $colorStyle . '">' . $quote . '</blockquote>' . $caption . '</figure>';
        }
        if ($template === 'minimal') {
            return '<figure class="post-quote post-quote--minimal bg-body-tertiary rounded-2 p-4 my-4" style="border-left:.3rem solid ' . $colorStyle . '">'
                . '<blockquote class="blockquote mb-0">' . $quote . '</blockquote>' . $caption . '</figure>';
        }

        return '<figure class="post-quote post-quote--simple bg-body-tertiary rounded-end-3 p-4 my-4" style="border-left:.4rem solid ' . $colorStyle . '">'
            . '<blockquote class="blockquote mb-0"><span class="display-4 fw-bold lh-1 d-block mb-2" style="color:' . $colorStyle . '" aria-hidden="true">&ldquo;</span>' . $quote . '</blockquote>'
            . $caption . '</figure>';
    }

    private function contrastColor(string $hex): string
    {
        $red = hexdec(substr($hex, 1, 2));
        $green = hexdec(substr($hex, 3, 2));
        $blue = hexdec(substr($hex, 5, 2));

        return (($red * 299 + $green * 587 + $blue * 114) / 1000) > 160 ? '#212529' : '#ffffff';
    }

    private function renderButton(string $url, string $style, string $label): string
    {
        $style = in_array(strtolower($style), ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark', 'link'], true)
            ? strtolower($style) : 'primary';
        $url = html_entity_decode(trim(strip_tags($url)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!preg_match('~^(?:https?://|/|#)~i', $url)) $url = '#';

        return '<p><a class="btn btn-' . $style . '" href="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
            . htmlspecialchars(html_entity_decode(strip_tags($label), ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a></p>';
    }

    private function renderAudio(string $url, string $title, bool $autoplay): string
    {
        $url = html_entity_decode(strip_tags($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('~^(?:javascript|data):~i', trim($url))) return '';
        $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeTitle = htmlspecialchars(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<figure class="post-audio"><figcaption>' . $safeTitle . '</figcaption><audio controls preload="metadata"' . ($autoplay ? ' autoplay' : '') . ' src="' . $safeUrl . '">Your browser does not support audio playback.</audio></figure>';
    }

    private function renderComparison(string $before, string $after, string $beforeAlt, string $afterAlt): string
    {
        $cleanUrl = static function (string $url): string {
            $url = html_entity_decode(strip_tags($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return preg_match('~^(?:javascript|data):~i', trim($url))
                ? ''
                : htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };
        $cleanText = static fn(string $text): string => htmlspecialchars(
            html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $beforeUrl = $cleanUrl($before);
        $afterUrl = $cleanUrl($after);
        if ($beforeUrl === '' || $afterUrl === '') return '';

        return '<div class="row g-3 comparison">'
            . '<div class="col-md-6"><figure><img class="img-fluid w-100" src="' . $beforeUrl . '" alt="' . $cleanText($beforeAlt) . '"><figcaption class="text-muted mt-2">' . $cleanText($beforeAlt) . '</figcaption></figure></div>'
            . '<div class="col-md-6"><figure><img class="img-fluid w-100" src="' . $afterUrl . '" alt="' . $cleanText($afterAlt) . '"><figcaption class="text-muted mt-2">' . $cleanText($afterAlt) . '</figcaption></figure></div>'
            . '</div>';
    }

    private function renderColumns(string $source, string $override): string
    {
        preg_match_all('~\{column\}(.*?)\{/column\}~is', $source, $matches);
        if (empty($matches[1])) return '';
        $params = ComponentHelper::getParams('com_' . self::FAMILY);
        $templates = ['equal', 'sidebar-left', 'sidebar-right', 'cards', 'bordered', 'color', 'gapless', 'feature'];
        $globalTemplate = (string) $params->get('column_template', 'equal');
        $template = $override !== 'global' && in_array($override, $templates, true) ? $override : $globalTemplate;
        $template = in_array($template, $templates, true) ? $template : 'equal';
        $gap = (string) $params->get('column_gap', '3');
        $gap = in_array($gap, ['0', '1', '2', '3', '4', '5'], true) ? $gap : '3';
        if ($template === 'gapless') $gap = '0';
        $align = (string) $params->get('column_vertical_align', 'start');
        $align = in_array($align, ['start', 'center', 'end', 'stretch'], true) ? $align : 'start';
        $colorMode = (string) $params->get('column_color_mode', 'bootstrap');
        $bootstrapColor = (string) $params->get('column_bootstrap_color', 'primary');
        $bootstrapColor = in_array($bootstrapColor, ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'], true) ? $bootstrapColor : 'primary';
        $customColor = (string) $params->get('column_custom_color', '#0d6efd');
        $customColor = preg_match('/^#[0-9a-f]{6}$/i', $customColor) ? $customColor : '#0d6efd';
        $color = $colorMode === 'custom' ? $customColor : 'var(--bs-' . $bootstrapColor . ',#0d6efd)';
        $contrast = $colorMode === 'custom' ? $this->contrastColor($customColor) : (in_array($bootstrapColor, ['warning', 'info', 'light'], true) ? '#212529' : '#ffffff');
        $count = count($matches[1]);
        $equalWidth = match ($count) { 2 => 'col-md-6', 3 => 'col-md-4', 4 => 'col-md-3', default => 'col-md' };
        $this->columnSequence++;
        $id = self::FAMILY . '-columns-' . $this->columnSequence;
        $columns = [];
        foreach ($matches[1] as $index => $content) {
            $width = $equalWidth;
            if ($count === 2 && $template === 'sidebar-left') $width = $index === 0 ? 'col-md-4' : 'col-md-8';
            if ($count === 2 && in_array($template, ['sidebar-right', 'feature'], true)) $width = $index === 0 ? 'col-md-8' : 'col-md-4';
            $inner = $this->paragraphs($content);
            if ($template === 'cards') $inner = '<div class="card h-100 shadow-sm"><div class="card-body">' . $inner . '</div></div>';
            elseif ($template === 'bordered') $inner = '<div class="h-100 border rounded-3 p-4">' . $inner . '</div>';
            elseif ($template === 'color') $inner = '<div class="h-100 rounded-3 p-4' . ($index === 0 ? ' post-columns__featured' : ' bg-body-tertiary border') . '">' . $inner . '</div>';
            elseif ($template === 'feature') $inner = '<div class="h-100 rounded-3 p-4 ' . ($index === 0 ? 'post-columns__featured' : 'bg-body-tertiary border') . '">' . $inner . '</div>';
            elseif ($template === 'gapless') $inner = '<div class="h-100 p-4 ' . ($index % 2 === 0 ? 'post-columns__featured' : 'bg-body-tertiary') . '">' . $inner . '</div>';
            $columns[] = '<div class="' . $width . '">' . $inner . '</div>';
        }
        $style = '<style>#' . $id . '{--post-column-color:' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . ';--post-column-contrast:' . $contrast . '}#' . $id . ' .post-columns__featured{background:var(--post-column-color);color:var(--post-column-contrast)}#' . $id . '.post-columns--gapless{overflow:hidden;border-radius:.75rem}</style>';
        $rowAlign = $align === 'stretch' ? '' : ' align-items-' . $align;

        return $style . '<div id="' . $id . '" class="row g-' . $gap . $rowAlign . ' post-columns post-columns--' . $template . '">' . implode('', $columns) . '</div>';
    }

    private function renderAccordion(string $source, string $override): string
    {
        preg_match_all(
            '~\{item\s+title=(?:"|&quot;)(.*?)(?:"|&quot;)\}(.*?)\{/item\}~is',
            $source,
            $matches,
            PREG_SET_ORDER
        );
        if (!$matches) return '';

        $params = ComponentHelper::getParams('com_' . self::FAMILY);
        $templates = ['classic', 'separated', 'numbered', 'minimal', 'color-panel', 'gradient-card', 'compact', 'two-column'];
        $globalTemplate = (string) $params->get('accordion_template', 'classic');
        $template = $override !== 'global' && in_array($override, $templates, true) ? $override : $globalTemplate;
        $template = in_array($template, $templates, true) ? $template : 'classic';
        $colorMode = (string) $params->get('accordion_color_mode', 'bootstrap');
        $bootstrapColor = (string) $params->get('accordion_bootstrap_color', 'primary');
        $bootstrapColor = in_array($bootstrapColor, ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'], true) ? $bootstrapColor : 'primary';
        $customColor = (string) $params->get('accordion_custom_color', '#0d6efd');
        $customColor = preg_match('/^#[0-9a-f]{6}$/i', $customColor) ? $customColor : '#0d6efd';
        $color = $colorMode === 'custom' ? $customColor : 'var(--bs-' . $bootstrapColor . ',#0d6efd)';
        $contrast = $colorMode === 'custom' ? $this->contrastColor($customColor) : (in_array($bootstrapColor, ['warning', 'info', 'light'], true) ? '#212529' : '#ffffff');
        $this->accordionSequence++;
        $id = self::FAMILY . '-accordion-' . $this->accordionSequence;
        $rootClasses = 'accordion post-accordion post-accordion--' . $template;
        if ($template === 'minimal') $rootClasses .= ' accordion-flush';
        if (in_array($template, ['color-panel', 'gradient-card'], true)) $rootClasses .= ' p-4 rounded-4';
        if ($template === 'gradient-card') $rootClasses .= ' shadow-sm';
        $safeColor = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');
        $style = '<style>#' . $id . '{--post-accordion-color:' . $safeColor . ';--post-accordion-contrast:' . $contrast . ';--bs-accordion-active-bg:var(--post-accordion-color);--bs-accordion-active-color:var(--post-accordion-contrast);--bs-accordion-btn-focus-box-shadow:0 0 0 .25rem color-mix(in srgb,var(--post-accordion-color) 25%,transparent)}'
            . '#' . $id . '.post-accordion--separated .accordion-item,#' . $id . '.post-accordion--numbered .accordion-item{border:1px solid var(--bs-border-color);border-radius:1rem;overflow:hidden;margin-bottom:1rem;box-shadow:0 .2rem 0 rgba(0,0,0,.75)}'
            . '#' . $id . '.post-accordion--numbered .accordion-button{gap:1rem;border-radius:1rem;font-size:1.05rem}#' . $id . '.post-accordion--numbered .post-accordion__number{font-size:1.75rem;font-weight:300;min-width:2.5rem}'
            . '#' . $id . '.post-accordion--minimal .accordion-item{border-width:0 0 1px}#' . $id . '.post-accordion--minimal .accordion-button{background:transparent;box-shadow:none;padding-left:0;padding-right:0}'
            . '#' . $id . '.post-accordion--color-panel{background:var(--post-accordion-color)}#' . $id . '.post-accordion--color-panel .accordion-item{margin-bottom:.65rem;border:0}#' . $id . '.post-accordion--color-panel .accordion-button:not(.collapsed){background:var(--bs-body-bg);color:var(--post-accordion-color)}'
            . '#' . $id . '.post-accordion--gradient-card{background:linear-gradient(135deg,var(--post-accordion-color),#6f42c1)}#' . $id . '.post-accordion--gradient-card .accordion-item{border:0}#' . $id . '.post-accordion--gradient-card .accordion-button{font-weight:600}'
            . '#' . $id . '.post-accordion--compact .accordion-item{border:0;margin-bottom:.35rem}#' . $id . '.post-accordion--compact .accordion-button{padding:.65rem 1rem;background:var(--bs-dark,#212529);color:#fff;font-weight:600}#' . $id . '.post-accordion--compact .accordion-button:not(.collapsed){background:var(--post-accordion-color);color:var(--post-accordion-contrast)}'
            . '#' . $id . '.post-accordion--two-column{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;align-items:start}#' . $id . '.post-accordion--two-column .accordion-item{border-radius:.5rem;overflow:hidden}@media(max-width:767.98px){#' . $id . '.post-accordion--two-column{grid-template-columns:1fr}}'
            . '</style>';
        $html = $style . '<div class="' . $rootClasses . '" id="' . $id . '">';
        foreach ($matches as $index => $match) {
            $number = $index + 1;
            $heading = $id . '-heading-' . $number;
            $collapse = $id . '-collapse-' . $number;
            $open = $index === 0;
            $title = htmlspecialchars(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $titleHtml = $template === 'numbered' ? '<span class="post-accordion__number">' . str_pad((string) $number, 2, '0', STR_PAD_LEFT) . '</span><span>' . $title . '</span>' : $title;
            $html .= '<div class="accordion-item"><h2 class="accordion-header" id="' . $heading . '">'
                . '<button class="accordion-button' . ($open ? '' : ' collapsed') . '" type="button" data-bs-toggle="collapse" data-bs-target="#' . $collapse . '" aria-expanded="' . ($open ? 'true' : 'false') . '" aria-controls="' . $collapse . '">' . $titleHtml . '</button></h2>'
                . '<div id="' . $collapse . '" class="accordion-collapse collapse' . ($open ? ' show' : '') . '" aria-labelledby="' . $heading . '" data-bs-parent="#' . $id . '"><div class="accordion-body">' . $this->paragraphs($match[2]) . '</div></div></div>';
        }
        $this->getApplication()->getDocument()->getWebAssetManager()->useScript('bootstrap.collapse');

        return $html . '</div>';
    }

    private function paragraphs(string $content): string
    {
        $plain = html_entity_decode(strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], "\n", $content)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lines = preg_split('/\R/', trim($plain));
        $lines = $lines ?: [''];

        return implode('', array_map(
            fn($line) => '<p>' . (trim($line) === '' ? '<br>' : htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>',
            $lines
        ));
    }

    private function attributes(string $source): array
    {
        $out = [];
        preg_match_all('/([a-z][a-z0-9_-]*)\s*=\s*"([^"]*)"/i', $source, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) $out[strtolower($match[1])] = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $out;
    }

    private function renderEmbed(array $data): string
    {
        $provider = strtolower($data['provider'] ?? '');
        $url = trim($data['url'] ?? '');
        if ($provider === 'polls') return $this->renderPoll((int) $url, (string) ($data['template'] ?? 'global'));
        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) return '';
        $hosts = [
            'gist' => ['gist.github.com'], 'instagram' => ['instagram.com', 'www.instagram.com'],
            'spotify' => ['open.spotify.com'], 'behance' => ['behance.net', 'www.behance.net'],
            'soundcloud' => ['soundcloud.com', 'www.soundcloud.com', 'w.soundcloud.com'], 'slideshare' => ['slideshare.net', 'www.slideshare.net'],
            'codepen' => ['codepen.io'], 'tweet' => ['twitter.com', 'x.com', 'www.twitter.com', 'www.x.com'],
            'pinterest' => ['pinterest.com', 'www.pinterest.com', 'pin.it', 'assets.pinterest.com'], 'youtube' => ['youtube.com', 'www.youtube.com', 'youtu.be'],
            'vimeo' => ['vimeo.com', 'www.vimeo.com'], 'dailymotion' => ['dailymotion.com', 'www.dailymotion.com', 'dai.ly', 'geo.dailymotion.com'],
            'ted' => ['ted.com', 'www.ted.com', 'embed.ted.com'],
            'facebook' => ['facebook.com', 'www.facebook.com', 'm.facebook.com'], 'polls' => [],
        ];
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
        if (!isset($hosts[$provider]) || ($hosts[$provider] && !in_array($host, $hosts[$provider], true))) return '';
        if ($provider === 'tweet') return $this->renderTweet($url);
        if ($provider === 'gist') return $this->renderGist($url);
        if ($provider === 'instagram') return $this->renderInstagram($url);
        if ($provider === 'spotify') return $this->renderSpotify($url);
        if ($provider === 'behance') return $this->renderBehance($url);
        if ($provider === 'soundcloud') return $this->renderSoundCloud($url);
        if ($provider === 'slideshare') return $this->renderSlideShare($url, (int) ($data['width'] ?? 510), (int) ($data['height'] ?? 420));
        if ($provider === 'codepen') return $this->renderCodePen($url);
        if ($provider === 'pinterest') return $this->renderPinterest($url);
        if ($provider === 'youtube') return $this->renderYouTube($url, (int) ($data['width'] ?? 1024), (int) ($data['height'] ?? 576));
        if ($provider === 'vimeo') return $this->renderVimeo($url, (int) ($data['width'] ?? 1024), (int) ($data['height'] ?? 576));
        if ($provider === 'dailymotion') return $this->renderDailyMotion($url, (int) ($data['width'] ?? 1024), (int) ($data['height'] ?? 576));
        if ($provider === 'ted') return $this->renderTed($url, (int) ($data['width'] ?? 1024), (int) ($data['height'] ?? 576));
        if ($provider === 'facebook') return $this->renderFacebook($url, (int) ($data['width'] ?? 500), (int) ($data['height'] ?? 736));
        $embedUrl = $this->embedUrl($provider, $url);
        $safe = htmlspecialchars($embedUrl ?: $url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $label = htmlspecialchars(ucfirst($provider) . ' embed', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if ($embedUrl === null) {
            return '<p class="post-embed-link post-embed-link--' . htmlspecialchars($provider, ENT_QUOTES, 'UTF-8') . '"><a href="' . $safe . '" target="_blank" rel="noopener noreferrer">' . $label . '</a></p>';
        }
        return '<div class="post-embed post-embed--' . htmlspecialchars($provider, ENT_QUOTES, 'UTF-8') . '"><iframe src="' . $safe . '" title="' . $label . '" loading="lazy" allowfullscreen style="width:100%;min-height:420px;border:0"></iframe><noscript><a href="' . $safe . '">' . $label . '</a></noscript></div>';
    }

    private function renderTweet(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if (!preg_match('~^/[^/]+/status/[0-9]+/?$~', $path)) return '';
        $this->getApplication()->getDocument()->getWebAssetManager()->registerAndUseScript(
            self::FAMILY . '.x-widgets',
            'https://platform.twitter.com/widgets.js',
            ['version' => 'auto'],
            ['async' => true, 'charset' => 'utf-8']
        );
        $safe = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<blockquote class="twitter-tweet"><a href="' . $safe . '">View this post on X</a></blockquote>';
    }

    private function renderGist(string $url): string
    {
        $parts = parse_url($url);
        $path = rtrim($parts['path'] ?? '', '/');
        if (!preg_match('~^/([A-Za-z0-9_-]+)/([A-Fa-f0-9]+)(?:\.js)?$~', $path, $match)) return '';

        $gistUrl = 'https://gist.github.com/' . rawurlencode($match[1]) . '/' . strtolower($match[2]);
        $scriptUrl = $gistUrl . '.js';
        parse_str($parts['query'] ?? '', $query);
        if (!empty($query['file']) && is_string($query['file'])) {
            $scriptUrl .= '?file=' . rawurlencode(basename($query['file']));
        }

        $safeScript = htmlspecialchars($scriptUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeGist = htmlspecialchars($gistUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<div class="gist-embed"><script src="' . $safeScript . '"></script><noscript><a href="' . $safeGist . '">View this Gist on GitHub</a></noscript></div>';
    }

    private function renderInstagram(string $url): string
    {
        $path = rtrim(parse_url($url, PHP_URL_PATH) ?: '', '/') . '/';
        if (!preg_match('~^/(?:p|reel|tv)/[A-Za-z0-9_-]+/$~', $path)) return '';

        $permalink = 'https://www.instagram.com' . $path;
        $this->getApplication()->getDocument()->getWebAssetManager()->registerAndUseScript(
            self::FAMILY . '.instagram-embed',
            'https://www.instagram.com/embed.js',
            ['version' => 'auto'],
            ['async' => true]
        );
        $safe = htmlspecialchars($permalink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<blockquote class="instagram-media" data-instgrm-captioned data-instgrm-permalink="' . $safe . '" data-instgrm-version="14">'
            . '<p><a href="' . $safe . '" target="_blank" rel="noopener noreferrer">View this post on Instagram</a></p>'
            . '</blockquote>';
    }

    private function renderSpotify(string $url): string
    {
        $path = preg_replace('~^/embed~', '', parse_url($url, PHP_URL_PATH) ?: '');
        if (!preg_match('~^/(album|track|playlist|episode|show|artist)/([A-Za-z0-9]+)$~', $path, $match)) return '';

        $embedUrl = 'https://open.spotify.com/embed/' . $match[1] . '/' . $match[2] . '?utm_source=generator';
        $safe = htmlspecialchars($embedUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<iframe class="spotify-embed" data-testid="embed-iframe" style="border:0;border-radius:12px" src="' . $safe . '" width="100%" height="352" allowfullscreen allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy" title="Spotify player"></iframe>';
    }

    private function renderBehance(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if (!preg_match('~^/(?:embed/project|gallery)/([0-9]+)(?:/|$)~', $path, $match)) return '';

        $embedUrl = 'https://www.behance.net/embed/project/' . $match[1] . '?ilo0=1';
        $safe = htmlspecialchars($embedUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<iframe class="behance-embed" src="' . $safe . '" height="316" width="100%" allowfullscreen loading="lazy" scrolling="no" frameborder="0" allow="clipboard-write" referrerpolicy="strict-origin-when-cross-origin" title="Behance project" style="display:block;overflow:hidden;border:0"></iframe>';
    }

    private function renderSoundCloud(string $url): string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
        if ($host === 'w.soundcloud.com') {
            parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);
            $trackUrl = isset($query['url']) && is_string($query['url']) ? urldecode($query['url']) : '';
        } else {
            $trackUrl = $url;
        }
        if (!filter_var($trackUrl, FILTER_VALIDATE_URL)) return '';
        $trackHost = strtolower(parse_url($trackUrl, PHP_URL_HOST) ?: '');
        if (!in_array($trackHost, ['soundcloud.com', 'www.soundcloud.com', 'api.soundcloud.com'], true)) return '';

        $playerUrl = 'https://w.soundcloud.com/player/?' . http_build_query([
            'url' => $trackUrl,
            'color' => '#ff5500',
            'auto_play' => 'false',
            'hide_related' => 'false',
            'show_comments' => 'true',
            'show_user' => 'true',
            'show_reposts' => 'false',
            'show_teaser' => 'true',
            'visual' => 'true',
        ], '', '&', PHP_QUERY_RFC3986);
        $safePlayer = htmlspecialchars($playerUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeTrack = htmlspecialchars($trackUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<iframe class="soundcloud-embed" width="100%" height="300" scrolling="no" frameborder="0" allow="autoplay; encrypted-media" loading="lazy" src="' . $safePlayer . '" title="SoundCloud player" style="display:block;overflow:hidden;border:0"></iframe>'
            . '<div class="small text-muted text-truncate mt-1"><a href="' . $safeTrack . '" target="_blank" rel="noopener noreferrer">View this track on SoundCloud</a></div>';
    }

    private function renderSlideShare(string $url, int $width, int $height): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if (!preg_match('~^/slideshow/embed_code/key/([A-Za-z0-9_-]+)$~', $path, $match)) return '';
        $width = max(200, min(1920, $width ?: 510));
        $height = max(150, min(1200, $height ?: 420));
        $embedUrl = 'https://www.slideshare.net/slideshow/embed_code/key/' . $match[1];
        $safe = htmlspecialchars($embedUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<iframe class="slideshare-embed" src="' . $safe . '" width="' . $width . '" height="' . $height . '" frameborder="0" marginwidth="0" marginheight="0" scrolling="no" allowfullscreen loading="lazy" title="SlideShare presentation" style="border:1px solid #ccc;margin-bottom:5px;max-width:100%;overflow:hidden"></iframe>';
    }

    private function renderCodePen(string $url): string
    {
        $path = rtrim(parse_url($url, PHP_URL_PATH) ?: '', '/');
        if (!preg_match('~^/([A-Za-z0-9_-]+)/(?:pen|embed)/([A-Za-z0-9_-]+)$~', $path, $match)) return '';
        $user = $match[1];
        $slug = $match[2];
        $penUrl = 'https://codepen.io/' . rawurlencode($user) . '/pen/' . rawurlencode($slug);
        $profileUrl = 'https://codepen.io/' . rawurlencode($user);
        $this->getApplication()->getDocument()->getWebAssetManager()->registerAndUseScript(
            self::FAMILY . '.codepen-embed',
            'https://public.codepenassets.com/embed/index.js',
            ['version' => 'auto'],
            ['async' => true]
        );

        return '<p class="codepen" data-height="300" data-pen-title="CodePen example" data-default-tab="html,result" data-slug-hash="' . htmlspecialchars($slug, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" data-user="' . htmlspecialchars($user, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" style="height:300px;box-sizing:border-box;display:flex;align-items:center;justify-content:center;border:2px solid;margin:1em 0;padding:1em">'
            . '<span>See the Pen <a href="' . htmlspecialchars($penUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">CodePen example</a> by <a href="' . htmlspecialchars($profileUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">@' . htmlspecialchars($user, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a> on <a href="https://codepen.io">CodePen</a>.</span></p>';
    }

    private function renderPinterest(string $url): string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
        if ($host === 'assets.pinterest.com') {
            parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);
            $pinId = isset($query['id']) ? (string) $query['id'] : '';
        } else {
            $path = parse_url($url, PHP_URL_PATH) ?: '';
            $pinId = preg_match('~/(?:pin/)?([0-9]{5,})(?:/|$)~', $path, $match) ? $match[1] : '';
        }
        if (!preg_match('/^[0-9]{5,}$/', $pinId)) return '';

        $embedUrl = 'https://assets.pinterest.com/ext/embed.html?id=' . $pinId;
        $safe = htmlspecialchars($embedUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<iframe class="pinterest-embed" src="' . $safe . '" height="560" width="345" frameborder="0" scrolling="no" loading="lazy" title="Pinterest Pin" style="display:block;max-width:100%;overflow:hidden;border:0"></iframe>';
    }

    private function renderDailyMotion(string $url, int $width, int $height): string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if ($host === 'geo.dailymotion.com') {
            parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);
            $videoId = isset($query['video']) ? (string) $query['video'] : '';
        } elseif ($host === 'dai.ly') {
            $videoId = trim($path, '/');
        } else {
            $videoId = preg_match('~/video/([A-Za-z0-9]+)~', $path, $match) ? $match[1] : '';
        }
        if (!preg_match('/^[A-Za-z0-9]+$/', $videoId)) return '';

        $playerUrl = 'https://geo.dailymotion.com/player.html?video=' . rawurlencode($videoId);
        $safe = htmlspecialchars($playerUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        [$width, $height, $ratio] = $this->embedDimensions($width, $height);

        return '<div class="dailymotion-embed" style="max-width:' . $width . 'px"><div style="position:relative;height:0;padding-bottom:' . $ratio . '%"><iframe src="' . $safe . '" width="' . $width . '" height="' . $height . '" allowfullscreen loading="lazy" title="Dailymotion Video Player" allow="web-share; fullscreen" style="position:absolute;left:0;top:0;width:100%;height:100%;overflow:hidden;border:0"></iframe></div></div>';
    }

    private function renderYouTube(string $url, int $width, int $height): string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);
        $videoId = $host === 'youtu.be' ? trim($path, '/') : ($query['v'] ?? (preg_match('~/(?:shorts|embed)/([^/?]+)~', $path, $match) ? $match[1] : ''));
        if (!preg_match('/^[A-Za-z0-9_-]{6,20}$/', (string) $videoId)) return '';
        $safe = htmlspecialchars('https://www.youtube-nocookie.com/embed/' . $videoId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        [$width, $height, $ratio] = $this->embedDimensions($width, $height);

        return '<div class="youtube-embed" style="max-width:' . $width . 'px"><div style="position:relative;height:0;padding-bottom:' . $ratio . '%"><iframe src="' . $safe . '" width="' . $width . '" height="' . $height . '" allowfullscreen loading="lazy" title="YouTube video player" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" style="position:absolute;left:0;top:0;width:100%;height:100%;border:0"></iframe></div></div>';
    }

    private function renderVimeo(string $url, int $width, int $height): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if (!preg_match('~/(?:video/)?([0-9]+)(?:/|$)~', $path, $match)) return '';
        $safe = htmlspecialchars('https://player.vimeo.com/video/' . $match[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        [$width, $height, $ratio] = $this->embedDimensions($width, $height);

        return '<div class="vimeo-embed" style="display:block;width:100%;max-width:' . $width . 'px;background:#000;overflow:hidden"><div style="position:relative;width:100%;height:0;padding-bottom:' . $ratio . '%;background:#000;overflow:hidden"><iframe src="' . $safe . '" width="' . $width . '" height="' . $height . '" allowfullscreen loading="lazy" title="Vimeo video player" allow="autoplay; fullscreen; picture-in-picture" style="display:block;position:absolute;inset:0;width:100%;height:100%;border:0;background:#000"></iframe></div></div>';
    }

    private function embedDimensions(int $width, int $height): array
    {
        $width = max(320, min(1920, $width ?: 1024));
        $height = max(180, min(1080, $height ?: 576));
        return [$width, $height, round(($height / $width) * 100, 4)];
    }

    private function renderTed(string $url, int $width, int $height): string
    {
        $path = rtrim(parse_url($url, PHP_URL_PATH) ?: '', '/');
        if (!preg_match('~^/talks/([A-Za-z0-9_-]+)$~', $path, $match)) return '';
        $slug = $match[1];
        $embedUrl = 'https://embed.ted.com/talks/' . rawurlencode($slug);
        $title = ucwords(str_replace('_', ' ', $slug));
        $width = max(320, min(1920, $width ?: 1024));
        $height = max(180, min(1080, $height ?: 576));
        $safeUrl = htmlspecialchars($embedUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<div class="ted-embed" style="max-width:' . $width . 'px"><div style="position:relative;height:0;padding-bottom:' . round(($height / $width) * 100, 4) . '%"><iframe src="' . $safeUrl . '" width="' . $width . '" height="' . $height . '" title="' . $safeTitle . '" frameborder="0" scrolling="no" loading="lazy" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="position:absolute;left:0;top:0;width:100%;height:100%;overflow:hidden;border:0"></iframe></div></div>';
    }

    private function renderFacebook(string $url, int $width, int $height): string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '';
        if ($path === '/plugins/post.php') {
            parse_str($parts['query'] ?? '', $query);
            $postUrl = isset($query['href']) && is_string($query['href']) ? $query['href'] : '';
        } else {
            $postUrl = $url;
        }
        if (!filter_var($postUrl, FILTER_VALIDATE_URL)) return '';
        $postHost = strtolower(parse_url($postUrl, PHP_URL_HOST) ?: '');
        if (!in_array($postHost, ['facebook.com', 'www.facebook.com', 'm.facebook.com'], true)) return '';
        $width = max(350, min(750, $width ?: 500));
        $height = max(200, min(1600, $height ?: 736));
        $playerUrl = 'https://www.facebook.com/plugins/post.php?' . http_build_query([
            'href' => $postUrl,
            'show_text' => 'true',
            'width' => $width,
        ], '', '&', PHP_QUERY_RFC3986);
        $safe = htmlspecialchars($playerUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<iframe class="facebook-embed" src="' . $safe . '" width="' . $width . '" height="' . $height . '" scrolling="no" frameborder="0" allowfullscreen loading="lazy" title="Facebook post" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" style="display:block;max-width:100%;border:0;overflow:hidden"></iframe>';
    }

    private function renderPoll(int $pollId, string $override = 'global'): string
    {
        if ($pollId < 1) return '';
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $poll = $db->setQuery($db->createQuery()->select('*')->from('#__' . self::FAMILY . '_polls')->where('id=' . $pollId)->where('state=1'))->loadObject();
        if (!$poll) return '';
        $options = $db->setQuery($db->createQuery()->select('o.*,COUNT(v.id) AS votes')->from('#__' . self::FAMILY . '_poll_options AS o')->join('LEFT', '#__' . self::FAMILY . '_poll_votes AS v ON v.option_id=o.id')->where('o.poll_id=' . $pollId)->group('o.id')->order('o.ordering'))->loadObjectList();
        $total = array_sum(array_map(fn($option) => (int) $option->votes, $options));
        $params = ComponentHelper::getParams('com_' . self::FAMILY);
        $showVotes = (bool) $params->get('poll_show_votes', 1);
        $styles = ['progress', 'simple', 'badges'];
        $globalStyle = (string) $params->get('poll_results_style', 'progress');
        $style = $override !== 'global' && in_array($override, $styles, true) ? $override : $globalStyle;
        $style = in_array($style, $styles, true) ? $style : 'progress';
        $progressLabels = (bool) $params->get('poll_progress_labels', 1);
        $progressStriped = (bool) $params->get('poll_progress_striped', 1);
        $colorMode = in_array($params->get('poll_progress_color_mode', 'palette'), ['palette', 'primary', 'custom'], true) ? $params->get('poll_progress_color_mode', 'palette') : 'palette';
        $customColor = preg_match('/^#[0-9a-f]{6}$/i', (string) $params->get('poll_progress_custom_color', '#0d6efd')) ? (string) $params->get('poll_progress_custom_color', '#0d6efd') : '#0d6efd';
        $identity = Factory::getApplication()->getIdentity();
        $voterKey = $identity->guest ? hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')) : 'user:' . $identity->id;
        $hasVoted = (bool) $db->setQuery($db->createQuery()->select('COUNT(*)')->from('#__' . self::FAMILY . '_poll_votes')->where('poll_id=' . $pollId)->where('voter_key=' . $db->quote($voterKey)))->loadResult();
        $type = $poll->multiple ? 'checkbox' : 'radio';
        $html = '<section class="post-poll post-poll--'.$style.' card card-body my-4"><h3>' . htmlspecialchars($poll->title, ENT_QUOTES, 'UTF-8') . '</h3>';
        if (!$hasVoted) {
            $html .= '<form method="post" action="' . htmlspecialchars(Uri::base() . 'index.php?option=com_' . self::FAMILY . '&task=polls.vote', ENT_QUOTES, 'UTF-8') . '">';
            foreach ($options as $option) $html .= '<label class="d-block mb-2"><input type="'.$type.'" name="choice[]" value="'.(int)$option->id.'"> '.htmlspecialchars($option->title,ENT_QUOTES,'UTF-8').'</label>';
            return $html.'<input type="hidden" name="poll_id" value="'.$pollId.'"><input type="hidden" name="return" value="'.htmlspecialchars(base64_encode(Uri::getInstance()->toString()),ENT_QUOTES,'UTF-8').'">'.HTMLHelper::_('form.token').'<button class="btn btn-primary" type="submit">Vote</button></form></section>';
        }
        $html .= '<div class="post-poll-results" aria-label="Poll results">';
        $index = 0;
        foreach ($options as $option) {
            $percent=$total?(int)round(((int)$option->votes/$total)*100):0;$label=htmlspecialchars($option->title,ENT_QUOTES,'UTF-8');$count=$showVotes?' <small class="text-muted">'.(int)$option->votes.' vote'.((int)$option->votes===1?'':'s').'</small>':'';
            if ($style === 'progress') {$palette=['bg-primary','bg-success','bg-info','bg-warning','bg-danger'];$barClass='progress-bar'.($progressStriped?' progress-bar-striped':'').($colorMode==='palette'?' '.$palette[$index%count($palette)]:($colorMode==='primary'?' bg-primary':''));$barStyle='width:'.$percent.'%'.($colorMode==='custom'?';background-color:'.$customColor:'');$html.='<div class="mb-3"><div class="d-flex justify-content-between"><span>'.$label.$count.'</span>'.($progressLabels?'':'<strong>'.$percent.'%</strong>').'</div><div class="progress" role="progressbar" aria-label="'.$label.'" aria-valuenow="'.$percent.'" aria-valuemin="0" aria-valuemax="100"><div class="'.$barClass.'" style="'.$barStyle.'">'.($progressLabels?$percent.'%':'').'</div></div></div>';}
            elseif ($style === 'badges') $html.='<div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2"><span>'.$label.$count.'</span><span class="badge bg-primary rounded-pill">'.$percent.'%</span></div>';
            else $html.='<div class="border-bottom py-2"><strong>'.$label.'</strong> — '.$percent.'%'.$count.'</div>';
            $index++;
        }
        return $html.'</div>'.($showVotes?'<p class="mt-3 mb-0"><strong>'.$total.'</strong> total vote'.($total===1?'':'s').'</p>':'').'</section>';
    }

    private function embedUrl(string $provider, string $url): ?string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);
        if ($provider === 'youtube') {
            $id = $host === 'youtu.be' ? trim($path, '/') : ($query['v'] ?? (preg_match('~/(?:shorts|embed)/([^/?]+)~', $path, $m) ? $m[1] : ''));
            return preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id) ? 'https://www.youtube-nocookie.com/embed/' . $id : null;
        }
        if ($provider === 'vimeo' && preg_match('~/([0-9]+)~', $path, $m)) return 'https://player.vimeo.com/video/' . $m[1];
        if ($provider === 'dailymotion' && preg_match('~/(?:video/)?([A-Za-z0-9]+)~', $path, $m)) return 'https://www.dailymotion.com/embed/video/' . $m[1];
        if ($provider === 'spotify') return 'https://open.spotify.com/embed' . preg_replace('~^/embed~', '', $path);
        if ($provider === 'soundcloud') return 'https://w.soundcloud.com/player/?url=' . rawurlencode($url);
        if ($provider === 'instagram') return 'https://www.instagram.com' . rtrim($path, '/') . '/embed/';
        if ($provider === 'codepen' && preg_match('~/pen/[^/]+/([^/?]+)~', $path, $m)) return 'https://codepen.io/anon/embed/' . $m[1];
        return null;
    }
}
