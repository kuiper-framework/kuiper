<?php // -*- php -*-

$finder = PhpCsFixer\Config::create()
        ->getFinder()
        ->notName('config.php');

return PhpCsFixer\Config::create()
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        "@Symfony" => true,
        'strict_param' => true,
        'declare_strict_types' => true,
        'no_superfluous_phpdoc_tags' => false,
        'phpdoc_to_comment' => false,
        'no_alias_functions' => false,
    ]);
