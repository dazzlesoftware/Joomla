<?php
defined('_JEXEC') or die;
\Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('fontawesome');

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
    $avatarHtml = $avatarHelper::renderImgTag($authorId, $email, 40, ['class' => 'postmeta-avatar-img rounded object-fit-cover', 'alt' => '']);
} else {
    ob_start(); ?>
    <span class="postmeta-avatar d-inline-flex align-items-center justify-content-center rounded bg-body-secondary text-body-secondary p-2" aria-hidden="true">
        <span class="fa-solid fa-user fa-lg" aria-hidden="true"></span>
    </span>
    <?php $avatarHtml = (string) ob_get_clean();
}

$option = Factory::getApplication()->getInput()->getCmd('option');

if ($authorId > 0 && in_array($option, ['com_academy', 'com_blog', 'com_codex'], true)) : ?>
    <a class="postmeta-avatar-link d-inline-flex rounded" href="<?php echo Route::_('index.php?option=' . $option . '&view=author&id=' . $authorId); ?>" aria-label="View author profile"><?php echo $avatarHtml; ?></a>
<?php else :
    echo $avatarHtml;
endif; ?>
