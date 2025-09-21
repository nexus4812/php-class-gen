<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/bin')
    ->name('*.php')
    ->exclude('vendor')
    ->exclude('generated')
;

// Add tests directory if it exists
if (is_dir(__DIR__ . '/tests')) {
    $finder->in(__DIR__ . '/tests');
}

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP82Migration' => true,
        '@PHP82Migration:risky' => true,
        '@PSR12' => true,
        'global_namespace_import' => true,
        'no_unused_imports' => true,
        'visibility_required' => true,
        'method_argument_space' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
