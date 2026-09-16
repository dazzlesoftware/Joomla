<?php
defined('_JEXEC') or die;
use Joomla\CMS\Layout\LayoutHelper;
$item = $displayData;
?>
<div class="post-wiki" data-post-wiki>
    <div class="wiki-overview">
        <nav class="wiki-contents" aria-label="Contents" hidden>
            <details open>
                <summary>Contents <span class="wiki-toggle-hint">(show / hide)</span></summary>
                <ol data-wiki-contents></ol>
            </details>
        </nav>
        <aside class="wiki-details" aria-label="Page details">
            <h2 class="h5">Details</h2>
            <?php echo LayoutHelper::render('academy.content.info_block', ['item' => $item, 'params' => $item->params], __DIR__); ?>
        </aside>
    </div>
    <div class="wiki-content" data-wiki-content><?php echo $item->text; ?></div>
</div>
