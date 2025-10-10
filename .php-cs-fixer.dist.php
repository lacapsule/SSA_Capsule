<?php

$rules = [
    '@PSR12' => true,
    'binary_operator_spaces' => ['default' => 'single_space'],
    'concat_space' => ['spacing' => 'one'], // <= espaces autour du '.'
    'array_syntax' => ['syntax' => 'short'],
    'no_unused_imports' => true,
    'single_quote' => true,
    'blank_line_before_statement' => ['statements' => ['return']],
];

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([__DIR__ . '/src', __DIR__ . '/tests'])
            ->name('*.php')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
    );
