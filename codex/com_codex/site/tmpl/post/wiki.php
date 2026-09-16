<?php
defined('_JEXEC') or die;

// Share the post shell so print, author, sharing and comment updates stay in sync.
$isWikiLayout = true;
\Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()
    ->registerAndUseStyle('com_codex.wiki', 'com_codex/wiki.css', ['version' => 'auto'])
    ->registerAndUseScript('com_codex.wiki', 'com_codex/wiki.js', ['version' => 'auto'], ['defer' => true]);
require __DIR__ . '/default.php';
