<?php
defined('_JEXEC') or die;

// Reuse the category blog shell and its item/links subtemplates.
$this->params->set('list_item_style', 'simple');
foreach ($this->items as $item) {
    $item->params->set('list_item_style', 'simple');
}
$this->setLayout('blog');
require __DIR__ . '/blog.php';
