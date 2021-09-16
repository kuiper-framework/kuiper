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
