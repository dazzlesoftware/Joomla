<?php
namespace Joomla\Component\Blog\Administrator\Helper;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\Event;
use Joomla\Registry\Registry;

final class NeuralNetworkModelRegistry
{
    public static function models(string $provider, string $kind): array
    {
        $models = match ($provider . ':' . $kind) {
            'openai:text' => ['gpt-4.1-mini'=>'GPT-4.1 Mini', 'gpt-4.1'=>'GPT-4.1', 'gpt-4.1-nano'=>'GPT-4.1 Nano'],
            'openai:image' => ['gpt-image-1.5'=>'GPT Image 1.5', 'gpt-image-1'=>'GPT Image 1', 'gpt-image-1-mini'=>'GPT Image 1 Mini'],
            'claude:text' => ['claude-sonnet-4-6'=>'Claude Sonnet 4.6'],
            default => [],
        };
        PluginHelper::importPlugin('neuralnetwork');
        $event = new Event('onNeuralNetworkModels', ['provider'=>$provider, 'kind'=>$kind, 'component'=>'com_blog', 'result'=>[]]);
        Factory::getApplication()->getDispatcher()->dispatch('onNeuralNetworkModels', $event);
        foreach ((array) $event->getArgument('result', []) as $entries) {
            foreach ((array) $entries as $entry) {
                if (!is_array($entry) || !self::validId($entry['id'] ?? null) || !is_string($entry['label'] ?? null)) { continue; }
                if (!isset($models[$entry['id']])) { $models[$entry['id']] = mb_substr(strip_tags($entry['label']), 0, 120); }
            }
        }
        return $models;
    }

    public static function validId(mixed $id): bool
    {
        return is_string($id) && (bool) preg_match('~^[a-zA-Z0-9][a-zA-Z0-9._:/-]{0,255}$~D', $id);
    }

    public static function selected(Registry $settings, string $field, string $default): string
    {
        $model = trim((string) $settings->get($field, $default));
        if ($model === '__custom__') { $model = trim((string) $settings->get($field . '_custom', '')); }
        if (!self::validId($model)) { throw new \InvalidArgumentException('Select a model or enter a valid custom model ID in AI settings.'); }
        return $model;
    }
}
