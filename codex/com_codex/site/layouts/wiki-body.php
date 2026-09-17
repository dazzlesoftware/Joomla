<?php
defined('_JEXEC') or die;
use Joomla\CMS\Layout\LayoutHelper;
$item = $displayData;
?>
<div class="post-wiki" data-post-wiki>
    <div class="wiki-overview row g-4 my-3">
        <nav class="wiki-contents col-12 col-md-6" aria-label="Contents" hidden>
            <details class="card card-body" open>
                <summary class="fw-bold mb-2">Contents <span class="wiki-toggle-hint small fw-normal">(show / hide)</span></summary>
                <ol data-wiki-contents></ol>
            </details>
        </nav>
        <?php if ((int) $item->params->get('show_wiki_details', 1)) : ?>
        <aside class="wiki-details col-12 col-md-5 ms-auto" aria-label="Page details">
            <div class="card card-body"><h2 class="h5">Details</h2>
            <?php echo LayoutHelper::render('codex.content.info_block', ['item' => $item, 'params' => $item->params], __DIR__); ?>
        </div></aside>
        <?php endif; ?>
    </div>
    <div class="wiki-content" data-wiki-content><?php echo $item->text; ?></div>
</div>
