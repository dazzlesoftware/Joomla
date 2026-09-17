<?php

namespace Joomla\Plugin\EditorsXtd\Video\Extension;

use Joomla\CMS\Editor\Button\Button;
use Joomla\CMS\Event\Editor\EditorButtonsSetupEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

defined('_JEXEC') or die;

final class Video extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return ['onEditorButtonsSetup' => 'onEditorButtonsSetup'];
    }

    public function onEditorButtonsSetup(EditorButtonsSetupEvent $event): void
    {
        if (in_array($this->_name, $event->getDisabledButtons(), true)) {
            return;
        }

        $event->getButtonsRegistry()->add($this->onDisplay($event->getEditorId()));
    }

    public function onDisplay(string $editorId): Button
    {
        $wa = $this->getApplication()->getDocument()->getWebAssetManager();
        $wa->useStyle('fontawesome');

        if (!$wa->assetExists('script', 'editor-button.video')) {
            $wa->registerScript(
                'editor-button.video',
                'plg_editors-xtd_video/video-button.js',
                ['version' => '1.0.1'],
                ['type' => 'module'],
                ['editors']
            );
        }

        $this->loadLanguage();

        return new Button(
            $this->_name,
            [
                'action'  => 'insert-video',
                'text'    => Text::_('PLG_EDITORSXTD_VIDEO_BUTTON'),
                'icon'    => 'play',
                'iconSVG' => '<svg viewBox="0 0 32 32" width="24" height="24"><path d="M4 2v28l24-14L4 2zm4 7l12 7-12 7V9z"></path></svg>', 
                'name'    => $this->_type . '_' . $this->_name,
            ]
        );
    }
}
