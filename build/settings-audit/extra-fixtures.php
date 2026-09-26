<?php
// CLI-only additions to the explicitly named audit fixtures.
if (PHP_SAPI !== 'cli') { exit(1); }
require __DIR__ . '/setup.php';
foreach (['academy', 'blog', 'codex'] as $family) {
    $source = $db->setQuery('SELECT * FROM #__' . $family . ' WHERE alias=' . $db->quote('settings-audit-post-1'))->loadObject();
    foreach (['archived' => 2, 'unpublished' => 0, 'scheduled' => 1] as $kind => $state) {
        $alias = 'settings-audit-' . $kind;
        if ($db->setQuery('SELECT id FROM #__' . $family . ' WHERE alias=' . $db->quote($alias))->loadResult()) { continue; }
        $row = clone $source;
        unset($row->id);
        $row->asset_id = 0;
        $row->title = 'Audit ' . ucfirst($kind) . ' Post';
        $row->alias = $alias;
        $row->state = $state;
        $row->featured = 0;
        if ($kind === 'scheduled') { $row->publish_up = '2099-01-01 00:00:00'; }
        $db->insertObject('#__' . $family, $row, 'id');
    }
}
echo "Additional audit lifecycle fixtures ready.\n";
