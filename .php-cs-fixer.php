<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('storage')
    ->exclude('bootstrap/cache');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,   // Follow PSR-12 standard
        'array_syntax' => ['syntax' => 'short'], // Use short [] arrays
        'single_quote' => true, // Use single quotes where possible
        'no_unused_imports' => true, // Remove unused use statements
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'trailing_comma_in_multiline' => true,
        'no_extra_blank_lines' => true,
    ])
    ->setFinder($finder);
