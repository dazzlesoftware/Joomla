<?php
// Reuse the Joomla CLI bootstrap and category rendering checks.
require __DIR__ . '/test-category-empty-render.php';
$root = __DIR__ . '/../' . $family . '/com_' . $family . '/site/tmpl';
$category = new Joomla\CMS\MVC\View\HtmlView(['name' => 'category', 'template_path' => $root . '/category']);
$featured = new Joomla\CMS\MVC\View\HtmlView(['name' => 'featured', 'template_path' => $root . '/featured']);
$category->setLayout('card');
$category->item = $featured->item = (object) ['title' => 'Fixture'];
$expected = Joomla\CMS\Layout\LayoutHelper::render('post.image-placeholder', $category->item, JPATH_COMPONENT . '/layouts');
if ($category->loadTemplate('placeholder') !== $expected || $featured->loadTemplate('placeholder') !== $expected) {
    throw new RuntimeException('Shared layout delegation changed output');
}
$temp = sys_get_temp_dir() . '/genesis-view-' . bin2hex(random_bytes(6));
mkdir($temp);
try {
    file_put_contents($temp . '/card_placeholder.php', '<?php defined("_JEXEC") or die; echo "category override";');
    $category->addTemplatePath($temp);
    if ($category->loadTemplate('placeholder') !== 'category override' || $featured->loadTemplate('placeholder') !== $expected) {
        throw new RuntimeException('View override isolation failed');
    }
    $post = new Joomla\CMS\MVC\View\HtmlView(['name' => 'post', 'template_path' => $root . '/post']);
    $post->setLayout('wiki');
    file_put_contents($temp . '/default_author.php', '<?php defined("_JEXEC") or die; echo "author fallback";');
    $post->addTemplatePath($temp);
    if ($post->loadTemplate('author') !== 'author fallback') {
        throw new RuntimeException('Wiki default subtemplate fallback failed');
    }
} finally {
    foreach (glob($temp . '/*.php') as $file) { unlink($file); }
    rmdir($temp);
}
echo "$family: shared output, per-view override isolation and wiki fallback passed.\n";
