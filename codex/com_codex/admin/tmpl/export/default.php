<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
?>
<form action="<?php echo Route::_('index.php?option=com_codex&task=import.export');?>" method="post" class="card card-body">
    <h2>Export posts</h2>
    <p>Create a portable XML backup that can be imported into Academy, Blog, or Codex. Referenced local images, audio, video, and documents are embedded in the file.</p>
    <label class="form-label" for="export-post-state">Posts to export</label>
    <select class="form-select mb-3" id="export-post-state" name="state">
        <option value="all">All posts</option>
        <option value="1">Published</option>
        <option value="0">Unpublished</option>
        <option value="2">Archived</option>
        <option value="-2">Trashed</option>
    </select>
    <div><button class="btn btn-success" type="submit"><span class="icon-download me-2" aria-hidden="true"></span>Download XML export</button></div>
    <?php echo HTMLHelper::_('form.token');?>
</form>
