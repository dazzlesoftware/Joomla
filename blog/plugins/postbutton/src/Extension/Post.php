<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Editors-xtd.post
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Blog\Postbutton\Extension;

use Joomla\CMS\Editor\Button\Button;
use Joomla\CMS\Event\Editor\EditorButtonsSetupEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Editor Post button
 *
 * @since  1.5
 */
final class Post extends CMSPlugin implements SubscriberInterface
{
    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return array
     *
     * @since   5.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return ['onEditorButtonsSetup' => ['onEditorButtonsSetup', -100]];
    }

    /**
     * @param  EditorButtonsSetupEvent $event
     * @return void
     *
     * @since   5.0.0
     */
    public function onEditorButtonsSetup(EditorButtonsSetupEvent $event): void
    {

        $subject  = $event->getButtonsRegistry();
        $disabled = $event->getDisabledButtons();

        if (\in_array($this->_name, $disabled)) {
            return;
        }

        $this->loadLanguage();

        $button = $this->onDisplay($event->getEditorId());

        if ($button) {
            $subject->add($button);
        }
    }

    /**
     * Display the button
     *
     * @param   string  $name  The name of the button to add
     *
     * @return  Button|void  The button options as Button object, void if ACL check fails.
     *
     * @since   1.5
     *
     * @deprecated  5.0 Use onEditorButtonsSetup event instead, will be removed in 7.0
     */
    public function onDisplay($name)
    {
        $user  = $this->getApplication()->getIdentity();

        // Can create in any category (component permission) or at least in one category
        $canCreateRecords = $user->authorise('core.create', 'com_blog')
            || \count($user->getAuthorisedCategories('com_blog', 'core.create')) > 0;

        // Instead of checking edit on all records, we can use **same** check as the form editing view
        $values           = (array) $this->getApplication()->getUserState('com_blog.edit.post.id');
        $isEditingRecords = \count($values);

        // This ACL check is probably a double-check (form view already performed checks)
        $hasAccess = $canCreateRecords || $isEditingRecords;
        if (!$hasAccess) {
            return;
        }

        $link = 'index.php?option=com_blog&view=posts&layout=modal&tmpl=component&'
            . Session::getFormToken() . '=1&editor=' . $name;

        $this->getApplication()->getDocument()->getWebAssetManager()->useStyle('fontawesome');

        $button = new Button(
            'post',
            [
                'action'  => 'modal',
                'link'    => $link,
                'text'    => Text::_('PLG_POST_BUTTON_POST'),
                'icon'    => 'file-add',
                'iconSVG' => '<svg viewBox="0 0 32 32" width="24" height="24"><path d="M28 24v-4h-4v4h-4v4h4v4h4v-4h4v-4zM2 2h18v6h6v10h2v-10l-8-'
                    . '8h-20v32h18v-2h-16z"></path></svg>',
                // This is whole Plugin name, it is needed for keeping backward compatibility
                'name' => $this->_type . '_' . $this->_name,
            ]
        );

        return $button;
    }
}
