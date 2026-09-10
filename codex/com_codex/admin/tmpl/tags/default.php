<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
$user = Factory::getApplication()->getIdentity();
$tag = $this->tag;
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="row g-4">
<div class="col-lg-8">
<form method="get" class="d-flex gap-2 mb-3"><input type="hidden" name="option" value="com_codex"><input type="hidden" name="view" value="tags"><label class="visually-hidden" for="tag-search">Search tags</label><input class="form-control" id="tag-search" name="search" value="<?php echo $escape(Factory::getApplication()->getInput()->getString('search')); ?>" placeholder="Search tags"><button class="btn btn-primary">Search</button></form>
<div class="card"><div class="card-header">Tags</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Title</th><th>Alias</th><th>Posts</th><th>Status</th></tr></thead><tbody>
<?php foreach ($this->items as $item) : ?><tr><td><a href="<?php echo Route::_('index.php?option=com_codex&view=tags&id=' . (int) $item->id); ?>"><?php echo $escape($item->title); ?></a></td><td><?php echo $escape($item->alias); ?></td><td><?php echo (int) $item->post_count; ?></td><td><?php echo $item->published == 1 ? 'Published' : 'Unpublished'; ?></td></tr><?php endforeach; ?>
<?php if (!$this->items) : ?><tr><td colspan="4">No tags yet. Create a tag or add one while editing a post.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<div class="col-lg-4"><div class="card"><div class="card-header"><?php echo $tag ? 'Edit tag' : 'New tag'; ?></div><div class="card-body">
<?php if ($user->authorise($tag ? 'core.edit' : 'core.create', 'com_codex')) : ?>
<form method="post" action="<?php echo Route::_('index.php?option=com_codex&task=tags.save'); ?>" class="form-vertical">
<input type="hidden" name="id" value="<?php echo (int) ($tag->id ?? 0); ?>">
<label for="tag-title">Title</label><input required maxlength="255" class="form-control mb-3" id="tag-title" name="title" value="<?php echo $escape($tag->title ?? ''); ?>">
<label for="tag-alias">Alias</label><input class="form-control mb-3" id="tag-alias" name="alias" maxlength="191" value="<?php echo $escape($tag->alias ?? ''); ?>" placeholder="Generated from title">
<label for="tag-description">Description</label><textarea class="form-control mb-3" id="tag-description" name="description" rows="3"><?php echo $escape($tag->description ?? ''); ?></textarea>
<label for="tag-published">Status</label><select class="form-select mb-3" id="tag-published" name="published" <?php echo !$user->authorise('core.edit.state', 'com_codex') ? 'disabled' : ''; ?>><option value="1">Published</option><option value="0" <?php echo $tag && !$tag->published ? 'selected' : ''; ?>>Unpublished</option></select>
<?php $form = new \Joomla\CMS\Form\Form('native-tag'); $form->load('<form><field name="access" type="accesslevel" label="JFIELD_ACCESS_LABEL"/><field name="language" type="contentlanguage" label="JFIELD_LANGUAGE_LABEL"><option value="*">JALL</option></field></form>'); $form->bind(['access' => $tag->access ?? 1, 'language' => $tag->language ?? '*']); echo $form->renderField('access'); echo $form->renderField('language'); ?>
<button class="btn btn-success" type="submit">Save tag</button> <a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_codex&view=tags'); ?>">New tag</a>
<?php echo HTMLHelper::_('form.token'); ?></form>
<?php endif; ?>
<?php if ($tag && $user->authorise('core.delete', 'com_codex')) : ?><form method="post" action="<?php echo Route::_('index.php?option=com_codex&task=tags.delete'); ?>" class="mt-3"><input type="hidden" name="id" value="<?php echo (int) $tag->id; ?>"><button class="btn btn-outline-danger" type="submit">Delete tag and its assignments</button><?php echo HTMLHelper::_('form.token'); ?></form><?php endif; ?>
</div></div></div></div>
