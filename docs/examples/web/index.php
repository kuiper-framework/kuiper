<?php

use kuiper\di\ContainerBuilder;
use kuiper\web\MicroApplication;

$loader = require __DIR__.'/vendor/autoload.php';

$builder = new ContainerBuilder();
$container = $builder->build();

$app = new MicroApplication($container);
$app->get('/', function ($req, $resp) {
    $resp->getBody()->write('<h1>It works!</h1>');
});
$app->get('/hello/{name}', 'IndexController:hello');

$app->run();
