<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.vote
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/**
 * @var Joomla\CMS\WebAsset\WebAssetManager $wa
 * @var \Joomla\Plugin\Codex\Vote\Extension\Vote $this
 */
$wa = $this->getApplication()->getDocument()->getWebAssetManager();
$wa->useStyle('fontawesome');

/**
 * Layout variables
 * -----------------
 * @var   string   $context  The context of the content being passed to the plugin
 * @var   object   &$row     The post object
 * @var   object   &$params  The post params
 * @var   integer  $page     The 'page' number
 * @var   array    $parts    The context segments
 * @var   string   $path     Path to this file
 */

if ($context === 'com_codex.categories') {
    return;
}

// Get rating
$rating = (float) $row->rating;
$rcount = (int) $row->rating_count;

// Round to 0.5
$rating = round($rating / 0.5) * 0.5;

// Render five Font Awesome stars, including a half star where appropriate.
$img = '';
for ($i = 1; $i <= 5; $i++) {
    $icon = $rating >= $i ? 'fa-solid fa-star' : ($rating >= $i - 0.5 ? 'fa-solid fa-star-half-stroke' : 'fa-regular fa-star');
    $img .= '<li><span class="' . $icon . ' text-warning" aria-hidden="true"></span></li>';
}

?>
<div class="content_rating" role="img" aria-label="<?php echo Text::sprintf('PLG_VOTE_STAR_RATING', $rating); ?>">
    <?php if ($rcount) : ?>
        <div class="visually-hidden">
            <p itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                <?php echo Text::sprintf('PLG_VOTE_USER_RATING', '<span itemprop="ratingValue">' . $rating . '</span>', '<span itemprop="bestRating">5</span>'); ?>
                <meta itemprop="ratingCount" content="<?php echo $rcount; ?>">
                <meta itemprop="worstRating" content="1">
            </p>
        </div>
        <?php if ($this->params->get('show_total_votes', 0)) : ?>
            <?php echo Text::sprintf('PLG_VOTE_TOTAL_VOTES', $rcount); ?>
        <?php endif; ?>
    <?php endif; ?>
    <ul class="list-unstyled d-inline-flex gap-1 mb-0">
        <?php echo $img; ?>
    </ul>
</div>
