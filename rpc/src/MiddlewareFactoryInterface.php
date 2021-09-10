<?php

declare(strict_types=1);

namespace kuiper\rpc;

use Psr\Container\ContainerInterface;

interface MiddlewareFactoryInterface
{
    public function getPriority(): int;

    public function create(ContainerInterface $container): MiddlewareInterface;
}
