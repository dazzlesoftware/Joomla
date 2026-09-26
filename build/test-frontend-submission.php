<?php
if (PHP_SAPI !== 'cli') { exit(1); }
$argv = ['', 'C:/wamp64/www/Joomla'];
require __DIR__ . '/test-subcategory-styles.php';
class SubmissionTestUser extends Joomla\CMS\User\User
{
    public function getAuthorisedViewLevels() { return [1, 2]; }
}
foreach (['academy', 'blog', 'codex'] as $family) {
    $helper = 'Joomla\\Component\\' . ucfirst($family) . '\\Site\\Helper\\SubmissionHelper';
    $params = Joomla\CMS\Component\ComponentHelper::getParams('com_' . $family);
    $original = $params->get('frontend_create_access');
    $user = new SubmissionTestUser();
    foreach ([[0,1,true],[0,2,true],[0,99,false],[1,1,false]] as [$guest,$level,$expected]) {
        $user->guest=$guest;
        $params->set('frontend_create_access',$level);
        if ($helper::hasAccess($user) !== $expected) { throw new RuntimeException($family . ': access check'); }
    }
    $params->set('frontend_create_access',$original);
    echo "$family: member, non-member and guest submission access passed.\n";
}
define('JPATH_COMPONENT', JPATH_ROOT . '/components/com_academy');
// Exercise real saves inside a rolled-back transaction; no test posts are retained.
$db = Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
$identity = $app->getIdentity();
$adminId = (int) $db->setQuery('SELECT MIN(id) FROM #__users WHERE block=0')->loadResult();
$admin = Joomla\CMS\Factory::getContainer()->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById($adminId);
$app->loadIdentity($admin);
foreach (['academy','blog','codex'] as $family) {
    $params = Joomla\CMS\Component\ComponentHelper::getParams('com_'.$family);
    $original = $params->get('frontend_create_access');
    $params->set('frontend_create_access',1);
    $db->transactionStart();
    try {
        $model = $app->bootComponent('com_'.$family)->getMVCFactory()->createModel('Form','Site',['ignore_request'=>true]);
        $model->setCurrentUser($admin);
        $catid=(int)$db->setQuery('SELECT MIN(id) FROM #__'.$family.'_categories')->loadResult();
        $ok=$model->save(['id'=>0,'title'=>'Submission regression fixture','alias'=>'submission-regression-'.uniqid(),'catid'=>$catid,'state'=>1,'access'=>1,'language'=>'*','post_content'=>'Review test.','created_by'=>$adminId]);
        if (!$ok) { throw new RuntimeException($family.': '.implode(';',$model->getErrors())); }
        $id=(int)$model->getState('post.id');
        if (!$id) { $id=(int)$model->getState($family.'.id'); }
        $state=$db->setQuery('SELECT state FROM #__'.$family.' WHERE title='.$db->quote('Submission regression fixture').' ORDER BY id DESC')->loadResult();
        if ((int)$state!==-3) { throw new RuntimeException($family.': expected pending review, got '.var_export($state,true)); }
        echo "$family: submitted Published saved as Pending Review.\n";
    } finally {
        $db->transactionRollback();
        $params->set('frontend_create_access',$original);
    }
}
$app->loadIdentity($identity);

