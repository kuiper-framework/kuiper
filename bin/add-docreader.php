<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

require __DIR__.'/../vendor/autoload.php';
$dirs = array_map(function ($dir) {
    return str_replace('/src', '', $dir);
}, array_values(json_decode(file_get_contents(__DIR__.'/../composer.json'), true)['autoload']['psr-4']));

chdir(__DIR__.'/..');

$finder = Finder::create()
    ->in($dirs)
     ->notPath(['tests'])
    ->name('*.php');
$docreader = file_get_contents('.docreader');
foreach ($finder->files() as $file) {
    echo $file, "\n";
    add_docreader($file->getRealPath(), $docreader);
}

function add_docreader(string $file, string $doc): void
{
    $code = file_get_contents($file);
    if (false !== strpos($code, $doc)) {
        return;
    }
    $lines = explode("\n", $code);
    array_splice($lines, 1, 0, ["\n".$doc."\n"]);
    file_put_contents($file, implode("\n", $lines));
}
