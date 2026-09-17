<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Blog\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;

$item = $displayData;
$authorId = (int) ($item->created_by ?? 0);
if ($authorId < 1) {
    return;
}
$app = Factory::getApplication();
$db = Factory::getContainer()->get(DatabaseInterface::class);
$author = $db->setQuery($db->createQuery()->select(['id', 'name'])->from('#__users')
    ->where('id=' . $authorId)->where('block=0'))->loadObject();
if (!$author) {
    return;
}
$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$authorName = ($item->created_by_alias ?? '') ?: $author->name;
$profileUrl = Route::_('index.php?option=com_blog&view=author&id=' . $authorId);
$app->getDocument()->getWebAssetManager()->useStyle('fontawesome');
// Use the component model so publication windows, category access and language apply.
$model = $app->bootComponent('com_blog')->getMVCFactory()->createModel('Posts', 'Site', ['ignore_request' => true]);
$model->setState('params', clone $app->getParams());
$model->setState('filter.author_id', $authorId);
$model->setState('filter.post_id', (int) $item->id);
$model->setState('filter.post_id.include', false);
$model->setState('filter.published', 1);
$model->setState('filter.access', true);
$model->setState('filter.language', Multilanguage::isEnabled());
$model->setState('list.start', 0);
$model->setState('list.limit', 3);
$model->setState('list.ordering', 'a.publish_up DESC, a.created DESC, a.id');
$model->setState('list.direction', 'DESC');
$posts = $model->getItems();
?>
<section class="post-author my-5" aria-labelledby="post-author-heading-<?php echo (int) $item->id; ?>">
    <h2 id="post-author-heading-<?php echo (int) $item->id; ?>" class="h5 text-uppercase border-bottom pb-3 mb-4">About the author</h2>
    <div class="mb-4">
        <?php echo LayoutHelper::render('postlist.card.avatar', $item, __DIR__); ?>
        <h3 class="h5 mt-3 mb-2"><a class="text-reset text-decoration-none" href="<?php echo $profileUrl; ?>"><?php echo $escape($authorName); ?></a></h3>
        <div class="d-flex align-items-center gap-3">
            <?php if (!empty($item->contact_link)) : ?>
                <a href="<?php echo $escape($item->contact_link); ?>" class="text-reset" aria-label="Contact author" title="Contact author"><span class="fa-solid fa-envelope" aria-hidden="true"></span></a>
            <?php endif; ?>
            <a href="<?php echo $profileUrl; ?>" class="text-reset" aria-label="View author profile" title="View author profile"><span class="fa-solid fa-user" aria-hidden="true"></span></a>
        </div>
    </div>
    <div class="card card-body p-3 p-md-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h3 class="h4 mb-0">Author's recent posts</h3>
            <a href="<?php echo $profileUrl; ?>">More posts from author</a>
        </div>
        <?php if ($posts) : ?>
            <ul class="list-unstyled mb-0">
                <?php foreach ($posts as $post) : ?>
                    <li class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 border-top py-3">
                        <a href="<?php echo Route::_(RouteHelper::getPostRoute($post->id . ':' . $post->alias, $post->catid, $post->language)); ?>"><span class="fa-regular fa-file-lines text-muted me-2" aria-hidden="true"></span><?php echo $escape($post->title); ?></a>
                        <?php echo \Joomla\Component\Blog\Site\Helper\DateHelper::render($post, $post->params ?? null, false); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="mb-0 border-top pt-3">No other published posts yet.</p>
        <?php endif; ?>
    </div>
</section>
