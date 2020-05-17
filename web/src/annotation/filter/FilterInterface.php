<?php

declare(strict_types=1);

namespace kuiper\web\annotation\filter;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

interface FilterInterface
{
    /**
     * filter priority, the smaller one run first.
     */
    public function getPriority(): int;

    /**
     * Creates the middleware.
     */
    public function createMiddleware(ContainerInterface $container): MiddlewareInterface;
}
