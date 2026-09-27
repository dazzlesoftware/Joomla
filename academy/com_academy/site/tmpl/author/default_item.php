<?php
defined('_JEXEC') or die;
// Validated by AuthorListingHelper; each item style has its own override file.
echo $this->loadTemplate((string) $this->params->get('list_item_style', 'card'));
