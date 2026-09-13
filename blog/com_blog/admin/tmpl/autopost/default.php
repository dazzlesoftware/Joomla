<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

$p = $this->provider;
$prefix = 'autopost_' . $p . '_';
$value = fn ($key, $default = '') => $this->params->get($prefix . $key, $default);
$labels = ['facebook' => 'Facebook', 'twitter' => 'X / Twitter', 'linkedin' => 'LinkedIn'];
$connected = trim((string) $value('token')) !== '';
$accounts = json_decode((string) $value('accounts', '[]'), true);
$accounts = is_array($accounts) ? $accounts : [];
$token = Session::getFormToken();
$callback = Uri::root() . 'administrator/index.php?option=com_blog&task=autopost.oauthCallback&provider=' . $p;
$connect = Route::_('index.php?option=com_blog&task=autopost.connect&provider=' . $p . '&' . $token . '=1');
$disconnect = Route::_('index.php?option=com_blog&task=autopost.disconnect&provider=' . $p . '&' . $token . '=1');
$escape = static fn ($text) => htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');

$fields = $p === 'facebook'
    ? [['app_id', 'Application ID', 'text'], ['app_secret', 'Application Secret', 'password'], ['version', 'Graph API version', 'text']]
    : ($p === 'twitter'
        ? [['client_id', 'OAuth 2.0 Client ID', 'text'], ['client_secret', 'OAuth 2.0 Client Secret (optional for public clients)', 'password']]
        : [['client_id', 'Client ID', 'text'], ['client_secret', 'Client Secret', 'password'], ['version', 'LinkedIn API version (YYYYMM)', 'text']]);

$publishingRules = [
    ['enabled', 'Enable ' . $labels[$p] . ' autoposting', 0],
    ['new', 'Autopost new published posts', 1],
    ['update', 'Autopost updated published posts', 0],
];
?>
<nav class="nav nav-tabs mb-4">
    <?php foreach ($labels as $key => $label) : ?>
        <a class="nav-link <?php echo $key === $p ? 'active' : ''; ?>" href="<?php echo Route::_('index.php?option=com_blog&view=autopost&provider=' . $key); ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
    <a class="nav-link" href="<?php echo Route::_('index.php?option=com_blog&view=autopostlogs'); ?>">Logs</a>
</nav>

<form action="<?php echo Route::_('index.php?option=com_blog&task=autopost.save'); ?>" method="post" id="adminForm" name="adminForm">
    <input type="hidden" name="provider" value="<?php echo $p; ?>">

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header"><strong><?php echo $labels[$p]; ?> application and account</strong></div>
                <div class="card-body">
                    <?php if ($connected) : ?>
                        <div class="alert alert-success d-flex justify-content-between align-items-center">
                            <span>Connected as <strong><?php echo $escape($value('account_name', 'social account')); ?></strong></span>
                            <a class="btn btn-sm btn-outline-danger" href="<?php echo $disconnect; ?>">Disconnect</a>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-info">Enter the application credentials, save them, then click Connect to sign in and approve publishing access.</div>
                    <?php endif; ?>

                    <?php foreach ($fields as [$key, $label, $type]) : ?>
                        <div class="mb-3">
                            <label class="form-label" for="<?php echo $prefix . $key; ?>"><?php echo $label; ?></label>
                            <input
                                class="form-control"
                                id="<?php echo $prefix . $key; ?>"
                                type="<?php echo $type; ?>"
                                name="jform[<?php echo $prefix . $key; ?>]"
                                value="<?php echo $escape($value($key, $key === 'version' ? ($p === 'facebook' ? 'v23.0' : '202601') : '')); ?>"
                                autocomplete="off"
                            >
                        </div>
                    <?php endforeach; ?>

                    <div class="mb-3">
                        <label class="form-label">OAuth redirect URL</label>
                        <input class="form-control" readonly value="<?php echo $escape($callback); ?>">
                        <div class="form-text">Add this exact URL to the provider application's authorized redirect or callback URLs.</div>
                    </div>
                    <a class="btn btn-primary mb-3" href="<?php echo $connect; ?>">Connect <?php echo $labels[$p]; ?></a>

                    <?php if ($p === 'facebook' && $accounts) : ?>
                        <div class="mb-3">
                            <label class="form-label">Publish to Facebook Page</label>
                            <select class="form-select" name="jform[<?php echo $prefix; ?>page_id]">
                                <?php foreach ($accounts as $a) : ?>
                                    <option value="<?php echo $escape($a['id']); ?>"<?php echo (string) $value('page_id') === (string) $a['id'] ? ' selected' : ''; ?>><?php echo $escape($a['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">These are Pages the connected account can manage.</div>
                        </div>
                    <?php endif; ?>

                    <?php if ($p === 'linkedin' && $accounts) : ?>
                        <div class="mb-3">
                            <label class="form-label">Publish as</label>
                            <select class="form-select" name="jform[<?php echo $prefix; ?>author]">
                                <?php foreach ($accounts as $a) : ?>
                                    <option value="<?php echo $escape($a['id']); ?>"<?php echo (string) $value('author') === (string) $a['id'] ? ' selected' : ''; ?>><?php echo $escape($a['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <details class="mt-3">
                        <summary>Advanced manual access</summary>
                        <div class="mt-3">
                            <label class="form-label">Access token</label>
                            <input
                                class="form-control"
                                type="password"
                                name="jform[<?php echo $prefix; ?>token]"
                                value="<?php echo $escape($value('token')); ?>"
                                autocomplete="off"
                            >
                            <?php if ($p === 'facebook' && !$accounts) : ?>
                                <label class="form-label mt-3">Page ID</label>
                                <input class="form-control" name="jform[<?php echo $prefix; ?>page_id]" value="<?php echo $escape($value('page_id')); ?>">
                            <?php elseif ($p === 'linkedin' && !$accounts) : ?>
                                <label class="form-label mt-3">Author URN</label>
                                <input class="form-control" name="jform[<?php echo $prefix; ?>author]" value="<?php echo $escape($value('author')); ?>">
                            <?php endif; ?>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card">
                <div class="card-header"><strong>Publishing rules</strong></div>
                <div class="card-body">
                    <?php foreach ($publishingRules as [$key, $label, $default]) : ?>
                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="jform[<?php echo $prefix . $key; ?>]" value="0">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="jform[<?php echo $prefix . $key; ?>]"
                                value="1"
                                <?php echo (int) $value($key, $default) ? 'checked' : ''; ?>
                            >
                            <label class="form-check-label"><?php echo $label; ?></label>
                        </div>
                    <?php endforeach; ?>

                    <label class="form-label">Default message</label>
                    <textarea class="form-control" rows="5" name="jform[<?php echo $prefix; ?>message]"><?php echo $escape($value('message', '{title} in {category} {link}')); ?></textarea>
                    <div class="form-text">Available tags: {title}, {summary}, {category}, {link}</div>
                </div>
            </div>
        </div>
    </div>

    <?php echo HTMLHelper::_('form.token'); ?>
</form>
