<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
$voterKey = $this->vote->voter_key ?? '';
$userId = str_starts_with($voterKey, 'user:') ? (int) substr($voterKey, 5) : 0;
$userField = new \Joomla\CMS\Form\Field\UserField();
$userField->setup(new \SimpleXMLElement('<field name="user_id" type="user" label="Voter" />'), $userId);
$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<form action="<?php echo Route::_('index.php?option=com_blog'); ?>" method="post" id="vote-form">
    <p class="text-muted">Assign the vote to a user, or leave it unassigned. Saving recalculates the affected post ratings and preserves the original date.</p>
    <div class="mb-3"><label class="form-label" for="vote-post">Post / page</label>
        <select class="form-select" id="vote-post" name="post_id" required><option value="">Choose a post</option>
        <?php foreach ($this->posts as $post) : ?><option value="<?php echo (int) $post->id; ?>" <?php echo (int) $post->id === (int) $this->vote->post_id ? 'selected' : ''; ?>><?php echo $escape($post->title); ?> (#<?php echo (int) $post->id; ?>)</option><?php endforeach; ?></select>
    </div>
    <div class="mb-3">
        <?php echo $userField->label; ?>
        <?php echo $userField->input; ?>
        <p class="form-text">Unassigned votes show Guest. Existing guest votes keep their anonymous identity unless assigned to a user. Each user can have one vote per post.</p>
    </div>
    <div class="mb-3"><label class="form-label" for="vote-rating">Rating</label><select class="form-select" id="vote-rating" name="rating" required>
        <?php for ($score = 1; $score <= 5; $score++) : ?><option value="<?php echo $score; ?>" <?php echo $score === (int) $this->vote->rating ? 'selected' : ''; ?>><?php echo $score; ?> / 5</option><?php endfor; ?>
    </select></div>
    <button type="submit" class="btn btn-primary">Save &amp; Close</button>
    <a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_blog&view=votes'); ?>">Cancel</a>
    <input type="hidden" name="id" value="<?php echo (int) $this->vote->id; ?>"><input type="hidden" name="task" value="votes.save">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
