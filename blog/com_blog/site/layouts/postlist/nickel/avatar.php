<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;

$item     = $displayData;
$authorId = (int) ($item->created_by ?? 0);
$email    = '';

if ($authorId > 0) {
    $db = Factory::getContainer()->get(DatabaseInterface::class);
    $email = (string) $db->setQuery(
        $db->createQuery()->select('email')->from('#__users')->where('id=' . $authorId)
    )->loadResult();
}

// Rendered by the plg_user_genesisprofile plugin when installed (uploaded
// picture, falling back to Gravatar, falling back to a placeholder).
// Degrades to a generic icon if that plugin isn't present.
$avatarHelper = '\\Joomla\\Plugin\\User\\GenesisProfile\\Helper\\GenesisProfileHelper';
$avatarHtml   = '';

if (class_exists($avatarHelper)) {
    $avatarHtml = $avatarHelper::renderImgTag($authorId, $email, 40, ['class' => 'postmeta-avatar-img', 'alt' => '']);
} else {
    ob_start(); ?>
    <span class="postmeta-avatar" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.42 0-8 2.24-8 5v1a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-1c0-2.76-3.58-5-8-5Z"/></svg>
    </span>
    <?php $avatarHtml = (string) ob_get_clean();
}

$option = Factory::getApplication()->getInput()->getCmd('option');

if ($authorId > 0 && in_array($option, ['com_academy', 'com_blog', 'com_codex'], true)) : ?>
    <a class="postmeta-avatar-link" href="<?php echo Route::_('index.php?option=' . $option . '&view=author&id=' . $authorId); ?>" aria-label="View author profile"><?php echo $avatarHtml; ?></a>
<?php else :
    echo $avatarHtml;
endif; ?>
<style>
.postmeta-avatar,.postmeta-avatar-img{display:inline-flex;align-items:center;justify-content:center;width:2.5rem;height:2.5rem;flex:0 0 auto;border-radius:.375rem;background:#e9ecef;color:#adb5bd;object-fit:cover}
.postmeta-avatar-link{display:inline-flex;border-radius:.375rem;line-height:0}
.postmeta-avatar-link:focus-visible{outline:3px solid currentColor;outline-offset:3px}
</style>
