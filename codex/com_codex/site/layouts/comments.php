<?php
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;

$family = 'codex';
$params = ComponentHelper::getParams('com_' . $family);
$provider = (string) $params->get('comments_provider', 'disabled');
$postId = (int) ($displayData->id ?? 0);
$postTitle = (string) ($displayData->title ?? 'Post');

if (!$postId || $provider === 'disabled') {
    return;
}
?>
<section class="post-comments post-comments--<?php echo htmlspecialchars($provider, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Comments">
<h2>Comments</h2>
<?php if ($provider === 'native') :
    $db = Factory::getContainer()->get(DatabaseInterface::class);
    $query = $db->createQuery()->select('*')->from($db->quoteName('#__'.$family.'_comments'))->where('post_id='.$postId)->where('state=1')->order('created ASC');
    $comments = $db->setQuery($query)->loadObjectList();
    foreach ($comments as $comment): ?><post class="card mb-3"><div class="card-body"><header><strong><?php echo htmlspecialchars($comment->name, ENT_QUOTES, 'UTF-8');?></strong> <small><?php echo htmlspecialchars($comment->created, ENT_QUOTES, 'UTF-8');?></small></header><p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($comment->body, ENT_QUOTES, 'UTF-8'));?></p></div></post><?php endforeach; ?>
    <form action="<?php echo htmlspecialchars(Uri::base().'index.php?option=com_'.$family.'&task=comments.submit', ENT_QUOTES, 'UTF-8');?>" method="post" class="card card-body"><h3>Leave a comment</h3><?php if (Factory::getApplication()->getIdentity()->guest):?><label class="form-label">Name</label><input class="form-control mb-3" name="name" autocomplete="name" required><label class="form-label">Email</label><input class="form-control mb-3" name="email" type="email" autocomplete="email" required><?php endif;?><label class="form-label">Comment</label><textarea class="form-control mb-3" name="body" rows="5" minlength="2" maxlength="10000" required></textarea><div class="visually-hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div><input type="hidden" name="form_started" value="<?php echo time();?>"><input type="hidden" name="post_id" value="<?php echo $postId;?>"><input type="hidden" name="return" value="<?php echo base64_encode(Uri::getInstance()->toString());?>"><?php echo HTMLHelper::_('form.token');?><button class="btn btn-primary" type="submit">Submit Comment</button></form>
<?php elseif ($provider === 'disqus') :
    $shortname = preg_replace('/[^a-z0-9-]/i', '', (string) $params->get('disqus_shortname', ''));
    if ($shortname) : ?>
    <div id="disqus_thread"></div><script>window.disqus_config=function(){this.page.url=<?php echo json_encode(Uri::current()); ?>;this.page.identifier=<?php echo json_encode('com_'.$family.'.post.'.$postId); ?>};(function(){const d=document,s=d.createElement('script');s.src=<?php echo json_encode('https://' . $shortname . '.disqus.com/embed.js'); ?>;s.setAttribute('data-timestamp',Date.now());(d.head||d.body).appendChild(s)})();</script><noscript>Enable JavaScript to view comments powered by Disqus.</noscript>
    <?php else : ?><p>Disqus is selected but its shortname has not been configured.</p><?php endif;
elseif ($provider === 'jcomments') :
    $file = JPATH_SITE . '/components/com_jcomments/jcomments.php';
    if (is_file($file)) {
        require_once $file;
        if (class_exists('JComments')) {
            echo JComments::showComments($postId, 'com_' . $family, $postTitle);
        }
    } else {
        echo '<p>JComments is selected but is not installed.</p>';
    } elseif ($provider === 'intensedebate') :
        $account = preg_replace('/[^a-z0-9_-]/i', '', (string) $params->get('intensedebate_account', ''));
        if ($account) : ?>
    <div class="comments-intensedebate"><script>var idcomments_acct=<?php echo json_encode($account); ?>;var idcomments_post_id=<?php echo json_encode('com_'.$family.'.post.'.$postId); ?>;var idcomments_post_url=<?php echo json_encode(Uri::current()); ?>;</script><span id="IDCommentsPostTitle" style="display:none"></span><script src="https://www.intensedebate.com/js/genericCommentWrapperV2.js" async></script></div>
    <?php else : ?><p>IntenseDebate is selected but its account ID has not been configured.</p><?php endif;
elseif ($provider === 'hypercomments') :
    $widgetId = preg_replace('/[^a-z0-9_-]/i', '', (string) $params->get('hypercomments_widget_id', ''));
    if ($widgetId) : ?>
    <div id="hypercomments_widget"></div><script>window._hcwp=window._hcwp||[];window._hcwp.push({widget:'Stream',widget_id:<?php echo json_encode($widgetId); ?>,xid:<?php echo json_encode('com_'.$family.'.post.'.$postId); ?>});(function(){if(window.HC_LOAD_INIT)return;window.HC_LOAD_INIT=true;const s=document.createElement('script');s.async=true;s.src='https://w.hypercomments.com/widget/hc/'+<?php echo json_encode($widgetId); ?>+'/'+((navigator.language||'en').slice(0,2).toLowerCase())+'/widget.js';document.head.appendChild(s)})();</script>
    <?php else : ?><p>HyperComments is selected but its widget ID has not been configured.</p><?php endif;
elseif ($provider === 'jlex') :
    $file = JPATH_SITE . '/components/com_jlexcomment/load.php';
    if (is_file($file)) {
        require_once $file;
        if (class_exists('JLexCommentLoader')) {
            echo JLexCommentLoader::init($family, $postId, $postTitle);
        }
    } else {
        echo '<p>JLex Comments is selected but is not installed.</p>';
    } elseif ($provider === 'compojoom') :
        $file = JPATH_SITE . '/components/com_comment/helpers/utils.php';
        if (is_file($file)) {
            require_once $file;
            if (class_exists('ccommentHelperUtils')) {
                echo ccommentHelperUtils::commentInit('com_' . $family, $displayData);
            }
        } else {
            echo '<p>CompoJoom Comments is selected but is not installed.</p>';
        } elseif ($provider === 'komento') :
            $file = JPATH_SITE . '/components/com_komento/bootstrap.php';
            if (is_file($file)) {
                require_once $file;
                if (class_exists('Komento')) {
                    echo Komento::commentify('com_' . $family, $displayData, ['trigger' => 'onDisplayComments']);
                }
            } else {
                echo '<p>Komento is selected but is not installed.</p>';
            }
endif; ?>
</section>
