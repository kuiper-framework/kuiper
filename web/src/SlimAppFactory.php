<?php

declare(strict_types=1);

namespace kuiper\web;

use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

class SlimAppFactory
{
    public static function create(ContainerInterface $container): App
    {
        $app = AppFactory::createFromContainer($container);
        $app->getRouteCollector()->setDefaultInvocationStrategy(new ControllerInvocationStrategy());

        return $app;
    }
}
