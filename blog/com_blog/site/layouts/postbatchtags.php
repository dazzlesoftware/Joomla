<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Component\Blog\Administrator\Helper\TagsHelper;
$options = TagsHelper::db()->setQuery('SELECT id AS value, title AS text FROM #__blog_tags ORDER BY title')->loadObjectList();
?>
<label for="batch-tag-id">Tag</label><select class="form-select" name="batch[tag]" id="batch-tag-id"><option value="">Keep current tags</option><?php echo HTMLHelper::_('select.options', $options, 'value', 'text'); ?></select>
<label for="batch-tag-action">Action</label><select class="form-select" name="batch[tag_addremove]" id="batch-tag-action"><option value="a">Add tag</option><option value="r">Remove tag</option></select>
