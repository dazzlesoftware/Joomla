<?php
// Uses connection-local temporary tables; never changes site content.
if (PHP_SAPI !== 'cli') { exit(1); }
define('_JEXEC', 1);
define('JPATH_BASE', rtrim($argv[1] ?? '', '/\\'));
$_SERVER['HTTP_HOST']='localhost';
$_SERVER['REQUEST_URI']='/Joomla/administrator/index.php';
$_SERVER['SCRIPT_NAME']='/Joomla/administrator/index.php';
require JPATH_BASE.'/includes/defines.php';
require JPATH_BASE.'/includes/framework.php';
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
$c=Factory::getContainer();
$c->alias('session.web','session.web.site')->alias('session','session.web.site')->alias(Joomla\Session\SessionInterface::class,'session.web.site');
$app=$c->get(Joomla\CMS\Application\SiteApplication::class);
Factory::$application=$app; $app->createExtensionNamespaceMap();
(new ReflectionMethod($app,'initialiseApp'))->invoke($app); $app->loadDocument();

function check($actual, $expected, $label) {
    if ($actual !== $expected) { throw new RuntimeException($label . ': ' . var_export($actual, true)); }
}
foreach (['academy', 'blog', 'codex'] as $family) {
    $base = __DIR__ . '/../' . $family . '/com_' . $family;
    require $base . '/site/src/Helper/ListExcerptHelper.php';
    $helper = 'Joomla\\Component\\' . ucfirst($family) . '\\Site\\Helper\\ListExcerptHelper';
    $global = Joomla\CMS\Component\ComponentHelper::getParams('com_' . $family);
    $global->set('list_excerpt_length', 12);
    $render = static function ($text, $options = [], $extra = []) use ($helper) {
        $item = (object) array_merge(['summary' => $text, 'readmore' => 0], $extra);
        return [$helper::render($item, new Registry($options)), $item->readmore];
    };
    check($render('<p>One two three four</p>')[0], '<p>One two&#8230;</p>', 'legacy/global characters');
    check($render('<p>One two three</p>', ['list_excerpt_length' => 3, 'truncation_content_override' => 0])[0], '<p>One two&#8230;</p>', 'inherit ignores saved custom');
    check($render('ééééé', ['list_excerpt_length' => 3])[0], '<p>ééé&#8230;</p>', 'unicode hard limit');
    check($render('One &amp; two three', ['truncation_type' => 'words', 'list_excerpt_length' => 3])[0], '<p>One &amp; two&#8230;</p>', 'words and entities');
    check($render('<p><b>First</b></p><p>Second</p>', ['truncation_type' => 'paragraphs', 'truncation_paragraphs' => 1])[0], '<p>First</p>', 'paragraph units');
    check($render('First<br>Second<br />Third', ['truncation_type' => 'breaks', 'truncation_paragraphs' => 2])[0], '<p>First</p><p>Second</p>', 'break units');
    check($render('Full long summary', ['truncation_enabled' => 0]), ['Full long summary',0], 'disabled');
    check($render('Full long summary', ['list_excerpt_length' => 0]), ['Full long summary',0], 'unlimited');
    check($render('Manual summary long', [], ['readmore' => 1]), ['Manual summary long',1], 'manual split');
    check($render('Full long summary', [], ['excerpt' => 'My excerpt'])[0], '<p>My excerpt</p>', 'manual excerpt');
    check($render('Full long summary', ['truncation_readmore' => 0])[1], 0, 'hide automated readmore');
    $media = '<div class="post-gallery"><img src="gallery.jpg"></div><img src="cover.jpg">{video url="video.mp4"}{audio url="song.mp3" title="Song" autoplay="0"}<p>One two three four five</p>';
    $html = $render($media, ['truncation_gallery_position' => 'top', 'truncation_video_position' => 'bottom', 'truncation_audio_position' => 'bottom'])[0];
    check(str_starts_with($html, '<div class="post-gallery">'), true, 'gallery retained as unit');
    check(str_contains($html, 'cover.jpg'), false, 'image hidden separately');
    check(str_ends_with($html, '{video url="video.mp4"}{audio url="song.mp3" title="Song" autoplay="0"}'), true, 'plugin media complete and ordered');
    check(str_contains($html, 'data-excerpt-media'), false, 'no internal tokens');
    check(str_contains($render('<script>Bad()</script><p>One two three four</p>')[0], 'Bad'), false, 'nonvisible content excluded');
    Joomla\CMS\Plugin\PluginHelper::importPlugin($family);
    $pluginItem = (object) ['text' => $html];
    $pluginParams = new Registry();
    $app->triggerEvent('onContentPrepare', ['com_' . $family . '.category', &$pluginItem, &$pluginParams, 0]);
    check(str_contains($pluginItem->text, '{video '), false, 'video plugin integration');
    check(str_contains($pluginItem->text, '{audio '), false, 'audio plugin integration');
    check(str_contains($pluginItem->text, '<audio'), true, 'audio rendered');
    foreach (['accordion', 'alert', 'button', 'columns', 'quote', 'section', 'tabs'] as $kind) {
        $block = '{' . $kind . ' template="global"}Long block content with several words{/'. $kind . '}';
        $result = $render($block . '<p>Outside text is long enough</p>', ['truncation_' . $kind . '_position' => 'top'])[0];
        check(str_starts_with($result, $block), true, $kind . ' retained whole');
        check(str_contains($render($block . '<p>Outside text is long enough</p>')[0], 'Long block'), false, $kind . ' hidden');
    }
    $nested = '{section title="Outer"}{quote template="global"}Nested content{/quote}{/section}';
    check(str_starts_with($render($nested . '<p>Outside text is long enough</p>', ['truncation_section_position' => 'top', 'truncation_quote_position' => 'hide'])[0], $nested), true, 'outer block owns nested content');
    foreach (['comparison' => '{comparison before="one.jpg" after="two.jpg"}', 'embed' => '{embed provider="youtube" url="https://example.com"}', 'poll' => '{embed provider="polls" url="1"}', 'rule' => '<hr>'] as $kind => $block) {
        check(str_ends_with($render($block . '<p>Outside text is long enough</p>', ['truncation_' . $kind . '_position' => 'bottom'])[0], $block), true, $kind . ' bottom');
    }
    // Retained content still passes through the real block plugin.
    $blockItem = (object) ['text' => $render('{alert type="info"}A complete alert{/alert}<p>Outside text is long enough</p>', ['truncation_alert_position' => 'top'])[0]];
    $app->triggerEvent('onContentPrepare', ['com_' . $family . '.category', &$blockItem, &$pluginParams, 0]);
    check(str_contains($blockItem->text, 'alert-info'), true, 'retained alert plugin rendering');
    check(str_contains($blockItem->text, '{alert'), false, 'no unexpanded alert');
    foreach (['characters', 'words', 'paragraphs', 'breaks'] as $mode) {
        [$short, $more] = $render('<h2>Hello World!</h2>{embed provider="youtube" url="https://example.com"}', ['truncation_type' => $mode, 'list_excerpt_length' => 400]);
        check(str_contains($short, '{embed'), false, 'short-text embed hidden: ' . $mode);
        check(str_contains($short, '<h2>Hello World!</h2>'), true, 'short text formatting preserved');
        check(str_contains($short, '&#8230;'), false, 'no false ellipsis');
        check($more, 1, 'hidden embed needs readmore');
    }
    check($render('{audio url="song.mp3" title="Song" autoplay="0"}', ['truncation_audio_position' => 'bottom'])[1], 0, 'reposition alone needs no readmore');
    $spacers = '<h2>Hello World!</h2>' . str_repeat('<p>&nbsp;</p><p>{embed provider="youtube" url="https://example.com"}</p><div><p><br></p></div>', 20);
    [$compact, $more] = $render($spacers, ['list_excerpt_length' => 400]);
    check(trim($compact), '<h2>Hello World!</h2>', 'removed widgets leave no empty editor spacer rows');
    check($more, 1, 'compact excerpt still offers Read More');
    // Exercise Joomla field loading and conditional override input rendering.
    $form = new Joomla\CMS\Form\Form('truncation-' . $family);
    $form->loadFile($base . '/site/tmpl/category/card.xml', true, '/metadata');
    check(count($form->getFieldset('automated_truncation')), 22, 'menu fields');
    $number = $form->getField('list_excerpt_length', 'params');
    check(str_contains($number->renderField(), 'truncation_content_override'), true, 'conditional number');
    echo "$family truncation, media, inheritance and form checks passed\n";
}
