<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$family = 'codex';
$app = Factory::getApplication();
$currentView = $app->getInput()->getCmd('view', 'featured');

$links = [
    'featured'   => 'Home',
    'categories' => 'Categories',
    'archive'    => 'Archives',
];

$navId = 'postnav-' . substr(md5(uniqid('postnav', true)), 0, 8);
$subscribeModalId = $navId . '-subscribe';
$subscribeFormId = $navId . '-subscribe-form';
$params = ComponentHelper::getParams('com_' . $family);
$subscribeHeading = (string) $params->get('subscribe_heading', 'Stay Informed');
$subscribeText = (string) $params->get('subscribe_text', 'Subscribe for updates and new posts.');
$subscribeButton = (string) $params->get('subscribe_button', 'Subscribe');
$consentText = (string) $params->get('subscribe_consent', 'I agree to receive email updates and can unsubscribe at any time.');
$return = Uri::getInstance()->toString();

ob_start();
?>
<p><?php echo nl2br(htmlspecialchars($subscribeText, ENT_QUOTES, 'UTF-8')); ?></p>
<form id="<?php echo $subscribeFormId; ?>" method="post" action="<?php echo htmlspecialchars(Uri::base() . 'index.php?option=com_' . $family . '&task=engagement.subscribe', ENT_QUOTES, 'UTF-8'); ?>">
    <label class="form-label" for="<?php echo $subscribeFormId; ?>-name">Name</label>
    <input id="<?php echo $subscribeFormId; ?>-name" class="form-control mb-3" name="name" autocomplete="name" required>
    <label class="form-label" for="<?php echo $subscribeFormId; ?>-email">Email address</label>
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
?>
<nav class="postnav d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom" aria-label="Post navigation">
    <div class="postnav-links d-flex align-items-center flex-wrap gap-1">
        <a class="postnav-link postnav-home<?php echo $currentView === 'featured' ? ' active' : ''; ?>" href="<?php echo Route::_('index.php?option=com_' . $family . '&view=featured'); ?>" aria-label="Home" title="Home">
            <svg class="postnav-svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 3.2 3 10.5V21h6v-6h6v6h6V10.5L12 3.2Z"/></svg>
        </a>
        <a class="postnav-link<?php echo $currentView === 'categories' ? ' active' : ''; ?>" href="<?php echo Route::_('index.php?option=com_' . $family . '&view=categories'); ?>"><?php echo $links['categories']; ?></a>
        <a class="postnav-link" href="<?php echo Route::_('index.php?option=com_codex&view=tags'); ?>">Tags</a>
        <a class="postnav-link<?php echo $currentView === 'archive' ? ' active' : ''; ?>" href="<?php echo Route::_('index.php?option=com_' . $family . '&view=archive'); ?>"><?php echo $links['archive']; ?></a>
    </div>
    <div class="postnav-actions d-flex align-items-center gap-1">
        <button type="button" class="postnav-icon-btn" data-postnav-search-toggle="<?php echo $navId; ?>" aria-expanded="false" aria-controls="<?php echo $navId; ?>-search" title="Search">
            <svg class="postnav-svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M10 4a6 6 0 1 0 3.75 10.66l4.3 4.29 1.4-1.41-4.29-4.3A6 6 0 0 0 10 4Zm0 2a4 4 0 1 1 0 8 4 4 0 0 1 0-8Z"/></svg>
            <span class="visually-hidden">Search</span>
        </button>
        <button type="button" class="postnav-icon-btn" data-bs-toggle="modal" data-bs-target="#<?php echo $subscribeModalId; ?>" title="Subscribe by email" aria-controls="<?php echo $subscribeModalId; ?>">
            <svg class="postnav-svg" viewBox="0 0 16 16" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105V5.383zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741zM1 11.105l4.708-2.897L1 5.383z"/></svg>
            <span class="visually-hidden">Subscribe by email</span>
        </button>
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
?>
<style>
.postnav-link{display:inline-flex;align-items:center;padding:.4rem .65rem;border-radius:.375rem;color:var(--bs-body-color,#212529);text-decoration:none;font-weight:500;line-height:1}
.postnav-link:hover{background:rgba(0,0,0,.06);text-decoration:none}
.postnav-link.active{background:#e7e9fb;color:#2b2f77}
.postnav-home svg{display:block}
.postnav-icon-btn{display:inline-flex;align-items:center;justify-content:center;width:2.25rem;height:2.25rem;border-radius:.375rem;border:0;background:transparent;color:inherit;text-decoration:none;cursor:pointer;transition:background-color .15s ease,transform .15s ease}
.postnav-icon-btn:hover{background:rgba(0,0,0,.08);transform:translateY(-1px)}
.postnav-icon-btn:focus-visible{outline:2px solid #2b2f77;outline-offset:2px}
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
