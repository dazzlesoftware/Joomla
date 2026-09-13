<?php
$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/academy', __DIR__ . '/blog', __DIR__ . '/codex', __DIR__ . '/plugins'])
    ->name('*.php')
    ->exclude(['dist', 'build']);
return (new PhpCsFixer\Config())
    ->setRules(['@PSR12' => true])
    ->setFinder($finder)
    ->setRiskyAllowed(false)
    ->setIndent('    ')
    ->setLineEnding("\n");
