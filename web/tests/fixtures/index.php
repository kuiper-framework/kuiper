<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\ContainerBuilder;
use kuiper\web\middleware\Session;
use kuiper\web\session\PhpSessionFactory;
use kuiper\web\SlimAppFactory;

$loader = require __DIR__.'/../../../vendor/autoload.php';

$builder = new ContainerBuilder();
$builder->setClassLoader($loader);
$builder->addDefinitions([
    AnnotationReaderInterface::class => AnnotationReader::getInstance(),
]);
$builder->componentScan(['kuiper\\web\\fixtures\\controllers']);

$app = SlimAppFactory::create($builder->build());
$app->add(new Session(new PhpSessionFactory(['auto_start' => true])));
$app->run();
