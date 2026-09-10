<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\Component\Academy\Site\View\Subscribe\HtmlView $this */

$family = 'academy';
$subscribeHeading = (string) $this->params->get('subscribe_heading', 'Stay Informed');
$subscribeText = (string) $this->params->get('subscribe_text', 'Subscribe for updates and new posts.');
$subscribeButton = (string) $this->params->get('subscribe_button', 'Subscribe');
$consentText = (string) $this->params->get('subscribe_consent', 'I agree to receive email updates and can unsubscribe at any time.');
$return = Uri::root();
?>
<div class="post-subscribe-page card card-body bg-light text-dark p-3 p-md-5">
    <div class="mx-auto w-100" style="max-width:650px">
        <h1 class="text-center h2"><?php echo htmlspecialchars($subscribeHeading, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="text-center"><?php echo nl2br(htmlspecialchars($subscribeText, ENT_QUOTES, 'UTF-8')); ?></p>
        <form method="post" action="<?php echo htmlspecialchars(Uri::base() . 'index.php?option=com_' . $family . '&task=engagement.subscribe', ENT_QUOTES, 'UTF-8'); ?>">
            <label class="form-label" for="subscribe-page-name">Name</label>
            <input id="subscribe-page-name" class="form-control mb-3" name="name" autocomplete="name" required>
            <label class="form-label" for="subscribe-page-email">Email address</label>
            <input id="subscribe-page-email" class="form-control mb-3" name="email" type="email" autocomplete="email" inputmode="email" required>
            <label class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="consent" value="1" required>
                <span class="form-check-label"><?php echo htmlspecialchars($consentText, ENT_QUOTES, 'UTF-8'); ?></span>
            </label>
            <input type="hidden" name="return" value="<?php echo htmlspecialchars(base64_encode($return), ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo HTMLHelper::_('form.token'); ?>
            <button class="btn btn-primary w-100" type="submit"><?php echo htmlspecialchars($subscribeButton, ENT_QUOTES, 'UTF-8'); ?></button>
        </form>
    </div>
</div>
