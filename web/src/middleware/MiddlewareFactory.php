<?php

declare(strict_types=1);

namespace kuiper\web\middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareFactory
{
    /**
     * filter priority, the smaller one run first.
     */
    public function getPriority(): int;

    /**
     * Creates the middleware.
     */
    public function create(ContainerInterface $container): MiddlewareInterface;
}
