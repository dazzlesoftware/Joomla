<?php
defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Academy\Site\Helper\RouteHelper;

$avatarItem = (object) ['created_by' => $this->author->id];
?>
<?php echo LayoutHelper::render('postnav', ['params' => $this->params], JPATH_COMPONENT . '/layouts'); ?>
<div class="com-academy-author">
    <header class="author-profile-header d-flex align-items-center gap-3 mb-4">
        <?php echo LayoutHelper::render('postlist.card.avatar', $avatarItem, JPATH_COMPONENT . '/layouts'); ?>
        <div>
            <h1 class="mb-1"><?php echo $this->escape($this->author->name); ?></h1>
            <div class="text-muted"><?php echo count($this->posts); ?> published post<?php echo count($this->posts) === 1 ? '' : 's'; ?></div>
        </div>
    </header>

    <div class="author-profile-posts">
        <?php foreach ($this->posts as $post) : ?>
            <article class="author-profile-post py-3 border-top">
                <h2 class="h4"><a href="<?php echo Route::_(RouteHelper::getPostRoute($post->id . ':' . $post->alias, $post->catid, $post->language)); ?>"><?php echo $this->escape($post->title); ?></a></h2>
                <time class="text-muted small" datetime="<?php echo $this->escape($post->publish_up ?: $post->created); ?>"><?php echo $this->escape($post->publish_up ?: $post->created); ?></time>
                <?php if ($post->summary !== '') : ?><div class="mt-2"><?php echo $post->summary; ?></div><?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if (!$this->posts) : ?><p class="alert alert-info">This author has no published posts.</p><?php endif; ?>
    </div>
</div>