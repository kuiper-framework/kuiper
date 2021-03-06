<?php

declare(strict_types=1);

$config = new PhpCsFixer\Config();

$finder = PhpCsFixer\Finder::create()
        ->in('.')
        ->notName('config.php');

return $config
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'strict_param' => true,
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'no_superfluous_phpdoc_tags' => false,
        'no_unused_imports' => true,
        'phpdoc_to_comment' => false,
        'no_alias_functions' => false,
    ]);
