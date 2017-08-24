<?php

$loader = require __DIR__.'/vendor/autoload.php';

$app = new kuiper\boot\Application();
$app->setLoader($loader)
    ->useAnnotations()
    ->loadConfig(__DIR__.'/config')
    ->bootstrap()
    ->get(\kuiper\web\ApplicationInterface::class)
    ->run();
