<?php
namespace Joomla\Plugin\Neuralnetwork\Modelcatalog\Extension;
defined('_JEXEC') or die;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
final class Modelcatalog extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array { return ['onNeuralNetworkModels'=>'models']; }
    public function models(Event $event): void
    {
        $entries=[];
        foreach ((array) $this->params->get('models', []) as $row) {
            $row=(array)$row;
            if (($row['provider'] ?? '') !== $event->getArgument('provider') || ($row['kind'] ?? '') !== $event->getArgument('kind')) { continue; }
            $entries[]=['id'=>$row['id'] ?? '', 'label'=>$row['label'] ?? ''];
        }
        $result=(array)$event->getArgument('result', []);$result[]=$entries;$event->setArgument('result',$result);
    }
}
