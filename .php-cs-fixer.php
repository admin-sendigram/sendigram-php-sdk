<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12'                  => true,
        '@Symfony'                => true,
        'declare_strict_types'    => true,
        'array_syntax'            => ['syntax' => 'short'],
        'ordered_imports'         => ['sort_algorithm' => 'alpha'],
        'no_unused_imports'       => true,
        'phpdoc_align'            => ['align' => 'left'],
        'single_quote'            => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
    ])
    ->setFinder($finder);
