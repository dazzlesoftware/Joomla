<?php
defined('_JEXEC') or die;
// Alternative module layout uses the same query, permissions and profile integration.
$sliderParams = clone $params;
$sliderParams->set('listing_categories', (array) $params->get('catid', []));
echo \Joomla\Component\Codex\Site\Helper\FeaturedSliderHelper::render($sliderParams);
