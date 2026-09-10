<?php
namespace Joomla\Plugin\System\CodexBranding\Extension;
defined('_JEXEC') or die;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
final class Branding extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['onBeforeCompileHead'=>'brandHtmlDocument','onAfterRender'=>'cleanGeneratorComment'];
    }
    public function brandHtmlDocument(): void
    {
        if (!$this->getApplication()->isClient('site') || $this->getApplication()->getInput()->getCmd('option') !== 'com_codex') return;
        $document=$this->getApplication()->getDocument();
        if ($document) $document->setGenerator('');
    }
    public function cleanGeneratorComment(): void
    {
        if (!$this->getApplication()->isClient('site') || $this->getApplication()->getInput()->getCmd('option') !== 'com_codex') return;
        $body=$this->getApplication()->getBody();
        $this->getApplication()->setBody((string) preg_replace('/\R?<!-- generator="[^"]*" -->\R?/', "\n", $body, 1));
    }
}
