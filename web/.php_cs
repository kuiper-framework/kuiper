<?php // -*- php -*-

$finder = PhpCsFixer\Config::create()
        ->getFinder()
        ->notName('config.php');

return PhpCsFixer\Config::create()
    ->setFinder($finder)
    ->setRules([
        "@PSR2" => true,
        // 'strict_param' => true,
        'array_syntax' => array('syntax' => 'short'),
        'ordered_imports' => true,
    ]);
