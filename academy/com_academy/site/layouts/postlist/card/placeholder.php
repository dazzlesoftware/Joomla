<?php
defined('_JEXEC') or die;

$item  = $displayData;
$title = htmlspecialchars((string) ($item->title ?? 'Post image'), ENT_QUOTES, 'UTF-8');
?>
<figure class="item-image post-card-placeholder" role="img" aria-label="<?php echo $title; ?>">
    <svg viewBox="0 0 640 360" width="640" height="360" aria-hidden="true" focusable="false">
        <rect width="640" height="360" fill="currentColor" opacity=".06"/>
        <g transform="translate(220 95)" fill="none" stroke="currentColor" stroke-width="12" opacity=".38">
            <rect x="0" y="0" width="200" height="150" rx="5"/>
            <circle cx="48" cy="42" r="18" fill="currentColor" stroke="none"/>
            <path d="M15 132 72 72l42 43 30-31 42 48z" fill="currentColor" stroke="none"/>
        </g>
    </svg>
</figure>
