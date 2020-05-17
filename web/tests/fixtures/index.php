<?php

declare(strict_types=1);

use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\ContainerBuilder;
use kuiper\web\session\PhpSessionFactory;
use kuiper\web\session\SessionMiddleware;
use kuiper\web\SlimAppFactory;

$loader = require __DIR__.'/../../../vendor/autoload.php';

$builder = new ContainerBuilder();
$builder->setClassLoader($loader);
$builder->addDefinitions([
    AnnotationReaderInterface::class => AnnotationReader::getInstance(),
]);
$builder->componentScan(['kuiper\\web\\fixtures\\controllers']);

$app = SlimAppFactory::create($builder->build());
$app->add(new SessionMiddleware(new PhpSessionFactory(['auto_start' => true])));
$app->run();
