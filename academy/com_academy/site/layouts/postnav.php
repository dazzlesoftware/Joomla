<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

$family = 'academy';
$app = Factory::getApplication();
$currentView = $app->getInput()->getCmd('view', 'featured');

// $displayData carries the merged (menu + global) params from the calling
// template, when one was passed in. Fall back to the global component
// params so the layout still works when called without display data.
$menuParams = null;
if (is_array($displayData) && isset($displayData['params']) && $displayData['params'] instanceof Registry) {
    $menuParams = $displayData['params'];
} elseif (is_object($displayData) && isset($displayData->params) && $displayData->params instanceof Registry) {
    $menuParams = $displayData->params;
}

$globalParams = ComponentHelper::getParams('com_' . $family);
$showPostnav = $menuParams
    ? (int) $menuParams->get('show_postnav', $globalParams->get('show_postnav', 1))
    : (int) $globalParams->get('show_postnav', 1);

if (!$showPostnav) {
    return;
}

$wa = $app->getDocument()->getWebAssetManager();
$wa->useStyle('fontawesome');

$links = [
    'categories' => 'Categories',
    'tags'       => 'Tags',
    'authors'    => 'Authors',
    'archive'    => 'Archives',
];

$navId = 'postnav-' . substr(md5(uniqid('postnav', true)), 0, 8);
$subscribeModalId = $navId . '-subscribe';
$subscribeFormId = $navId . '-subscribe-form';
$loginModalId = $navId . '-login';
$params = $globalParams;
$subscribeHeading = (string) $params->get('subscribe_heading', 'Stay Informed');
$subscribeText = (string) $params->get('subscribe_text', 'Subscribe for updates and new posts.');
$subscribeButton = (string) $params->get('subscribe_button', 'Subscribe');
$consentText = (string) $params->get('subscribe_consent', 'I agree to receive email updates and can unsubscribe at any time.');
$return = Uri::getInstance()->toString();

ob_start();
?>
<p><?php echo nl2br(htmlspecialchars($subscribeText, ENT_QUOTES, 'UTF-8')); ?></p>
<form id="<?php echo $subscribeFormId; ?>" method="post" action="<?php echo htmlspecialchars(Uri::base() . 'index.php?option=com_' . $family . '&task=engagement.subscribe', ENT_QUOTES, 'UTF-8'); ?>">
    <label class="form-label" for="<?php echo $subscribeFormId; ?>-name">Fullname</label>
    <input id="<?php echo $subscribeFormId; ?>-name" class="form-control mb-3" name="name" autocomplete="name" required>
    <label class="form-label" for="<?php echo $subscribeFormId; ?>-email">E-mail</label>
    <input id="<?php echo $subscribeFormId; ?>-email" class="form-control mb-3" name="email" type="email" autocomplete="email" inputmode="email" required>
    <label class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="consent" value="1" required>
        <span class="form-check-label"><?php echo htmlspecialchars($consentText, ENT_QUOTES, 'UTF-8'); ?></span>
    </label>
    <input type="hidden" name="return" value="<?php echo htmlspecialchars(base64_encode($return), ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
<?php
$subscribeModalBody = ob_get_clean();
$subscribeModalFooter = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>'
    . '<button type="submit" class="btn btn-primary" form="' . $subscribeFormId . '">'
    . htmlspecialchars($subscribeButton, ENT_QUOTES, 'UTF-8') . '</button>';

// Render Joomla's real mod_login output (guest login form, or the logged-in
// greeting/logout form when a user is already signed in). This gives us a
// fully functional login that honours whatever authentication plugins,
// two-factor buttons, and remember-me behaviour the site has configured.
// Note: core Joomla's login form has no built-in CAPTCHA field - CAPTCHA in
// core only appears on the registration/forgot-password forms - so none
// will appear here unless a plugin adds one to mod_login's layout.
$loginModule = clone ModuleHelper::getModule('mod_login');
// mod_login's own layout builds HTML ids from $module->id (e.g.
// "login-form-16"). If the site also has a Login module assigned to a
// template position, rendering the real module object a second time here
// would duplicate those ids on the page. Give this copy a distinct,
// non-persisted id so the modal's markup stays unique.
$loginModule->id = 'postnav-' . ($loginModule->id ?: 0);
ob_start();
echo ModuleHelper::renderModule($loginModule, ['style' => 'none']);
$loginModalBody = ob_get_clean();
$user = $app->getIdentity();
$isGuest = $user->guest;
$canCreatePost = !$isGuest && $user->authorise('core.create', 'com_' . $family);

// The subscribe/login modals work via HTMLHelper::_('bootstrap.renderModal', ...),
// which loads the "bootstrap.modal" script itself. The account dropdown uses
// plain data-bs-toggle="dropdown" markup instead, so its script has to be
// loaded explicitly here or the dropdown never opens.
if (!$isGuest) {
    HTMLHelper::_('bootstrap.dropdown');
}
?>
<nav class="postnav d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom" aria-label="Post navigation">
    <div class="postnav-links d-flex align-items-center flex-wrap gap-1">
        <a class="postnav-link postnav-icon-link<?php echo $currentView === 'featured' ? ' active' : ''; ?>" href="<?php echo Route::_('index.php?option=com_' . $family . '&view=featured'); ?>" aria-label="Home" title="Home">
            <i class="fa-solid fa-house" aria-hidden="true"></i>
        </a>
        <a class="postnav-link<?php echo $currentView === 'categories' ? ' active' : ''; ?>" href="<?php echo Route::_('index.php?option=com_' . $family . '&view=categories'); ?>"><?php echo $links['categories']; ?></a>
        <a class="postnav-link<?php echo $currentView === 'tags' ? ' active' : ''; ?>" href="<?php echo Route::_('index.php?option=com_' . $family . '&view=tags'); ?>"><?php echo $links['tags']; ?></a>
        <a class="postnav-link<?php echo $currentView === 'authors' ? ' active' : ''; ?>" href="<?php echo Route::_('index.php?option=com_' . $family . '&view=authors'); ?>"><?php echo $links['authors']; ?></a>
        <a class="postnav-link<?php echo $currentView === 'archive' ? ' active' : ''; ?>" href="<?php echo Route::_('index.php?option=com_' . $family . '&view=archive'); ?>"><?php echo $links['archive']; ?></a>
    </div>
    <div class="postnav-actions d-flex align-items-center gap-1">
        <button type="button" class="postnav-icon-btn" data-postnav-search-toggle="<?php echo $navId; ?>" aria-expanded="false" aria-controls="<?php echo $navId; ?>-search" title="Search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <span class="visually-hidden">Search</span>
        </button>
        <button type="button" class="postnav-icon-btn" data-bs-toggle="modal" data-bs-target="#<?php echo $subscribeModalId; ?>" title="Subscribe by email" aria-controls="<?php echo $subscribeModalId; ?>">
            <i class="fa-regular fa-envelope" aria-hidden="true"></i>
            <span class="visually-hidden">Subscribe by email</span>
        </button>
        <?php if ($canCreatePost) : ?>
            <a class="postnav-icon-btn postnav-create-btn" href="<?php echo Route::_('index.php?option=com_' . $family . '&task=post.add'); ?>" title="Create Post">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span class="visually-hidden">Create Post</span>
            </a>
        <?php endif; ?>
        <?php if ($isGuest) : ?>
            <button type="button" class="postnav-icon-btn" data-bs-toggle="modal" data-bs-target="#<?php echo $loginModalId; ?>" title="Sign in" aria-controls="<?php echo $loginModalId; ?>">
                <i class="fa-solid fa-user" aria-hidden="true"></i>
                <span class="visually-hidden">Sign in</span>
            </button>
        <?php else : ?>
            <div class="dropdown postnav-account">
                <button type="button" class="postnav-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Account">
                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                    <span class="visually-hidden">Account</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end postnav-account-menu">
                    <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_users&view=profile&layout=edit'); ?>">
                        <i class="fa-solid fa-id-badge me-2" aria-hidden="true"></i>Edit Profile
                    </a>
                    <a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_' . $family . '&view=myposts'); ?>">
                        <i class="fa-solid fa-file-lines me-2" aria-hidden="true"></i>My Posts
                    </a>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-item-text postnav-logout"><?php echo $loginModalBody; ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <form id="<?php echo $navId; ?>-search" class="postnav-search-box w-100 d-none mt-2" method="get" action="<?php echo Route::_('index.php?option=com_finder&view=search'); ?>">
        <div class="input-group">
            <input type="text" class="form-control" name="q" placeholder="Search&hellip;" aria-label="Search">
            <button class="btn btn-dark" type="submit">Search</button>
        </div>
    </form>
</nav>
<?php
echo HTMLHelper::_('bootstrap.renderModal', $subscribeModalId, [
    'title'       => htmlspecialchars($subscribeHeading, ENT_QUOTES, 'UTF-8'),
    'closeButton' => true,
    'modalCss'    => 'modal-dialog modal-dialog-centered',
    'footer'      => $subscribeModalFooter,
], $subscribeModalBody);

if ($isGuest) {
    echo HTMLHelper::_('bootstrap.renderModal', $loginModalId, [
        'title'       => 'Sign in to your account',
        'closeButton' => true,
        'modalCss'    => 'modal-dialog modal-dialog-centered',
    ], $loginModalBody);
}
?>
<style>
.postnav-link{display:inline-flex;align-items:center;padding:.4rem .65rem;border-radius:.375rem;color:var(--bs-body-color,#212529);text-decoration:none;font-weight:500;line-height:1}
.postnav-link:hover{background:rgba(0,0,0,.06);text-decoration:none}
.postnav-link.active{background:#e7e9fb;color:#2b2f77}
.postnav-icon-link{width:2.25rem;height:2.25rem;justify-content:center;padding:0}
.postnav-icon-btn{display:inline-flex;align-items:center;justify-content:center;width:2.25rem;height:2.25rem;border-radius:.375rem;border:0;background:transparent;color:inherit;text-decoration:none;cursor:pointer;transition:background-color .15s ease,transform .15s ease}
.postnav-icon-btn:hover{background:rgba(0,0,0,.08);transform:translateY(-1px)}
.postnav-icon-btn:focus-visible{outline:2px solid #2b2f77;outline-offset:2px}
.postnav-account-menu{min-width:14rem}
.postnav-logout{padding:.25rem 1rem}
.postnav-logout form{margin:0}
.postnav-logout .btn{width:100%}
</style>
<script>
(function () {
    var toggle = document.querySelector('[data-postnav-search-toggle="<?php echo $navId; ?>"]');
    var box = document.getElementById('<?php echo $navId; ?>-search');
    if (!toggle || !box) { return; }
    toggle.addEventListener('click', function () {
        var expanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        box.classList.toggle('d-none');
        if (!expanded) {
            var input = box.querySelector('input[name="q"]');
            if (input) { input.focus(); }
        }
    });
})();
</script>
