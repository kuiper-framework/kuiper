<?php

declare(strict_types=1);

namespace kuiper\web;

use kuiper\annotations\AnnotationReaderInterface;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

class SlimAppFactory
{
    public static function create(ContainerInterface $container): App
    {
        $app = AppFactory::createFromContainer($container);
        $app->getRouteCollector()->setDefaultInvocationStrategy(new ControllerInvocationStrategy());
        if ($container->has(AnnotationReaderInterface::class)) {
            $annotationProcessor = new AnnotationProcessor($container, $container->get(AnnotationReaderInterface::class), $app);
            $annotationProcessor->process();
        }

        return $app;
    }
}
