<?php

defined('_JEXEC') or die;

// Neutral menu entry point; the view resolves the configured post style.
$this->setLayout('card');
echo $this->loadTemplate();
