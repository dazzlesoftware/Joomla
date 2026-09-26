<?php
if (PHP_SAPI !== 'cli') { exit(1); }
$argv = ['', 'C:/wamp64/www/Joomla'];
require __DIR__ . '/test-subcategory-styles.php';
$db = Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
foreach (['academy', 'blog', 'codex'] as $family) {
    $item = $db->setQuery('SELECT id,title,alias,catid,language,hits FROM #__' . $family . ' WHERE state=1 ORDER BY id')->loadObject();
    if (!$item) { throw new RuntimeException('No published fixture for ' . $family); }
    $item->slug = $item->id . ':' . $item->alias;
    foreach (['disabled', 'native', 'disqus'] as $provider) {
        foreach ([0, 1] as $ratings) {
            $item->params = new Joomla\Registry\Registry(['show_vote'=>0, 'engagement_ratings'=>$ratings, 'comments_provider'=>$provider]);
            $html = Joomla\CMS\Layout\LayoutHelper::render('post.meta', $item, JPATH_ROOT . '/components/com_' . $family . '/layouts');
            if (str_contains($html, 'class="postmeta-rating"') !== (bool) $ratings) {
                throw new RuntimeException($family . ': rating summary visibility');
            }
            if (!str_contains($html, 'class="postmeta-comments"')) {
                throw new RuntimeException($family . ': missing comment count for ' . $provider);
            }
        }
    }
    echo $family . ": six rating/comment summary cases passed.\n";
}
