<?php

declare(strict_types=1);

namespace kuiper\di;

use Psr\Container\ContainerInterface;

interface Bootstrap
{
    /**
     * @param ContainerInterface $container
     */
    public function boot(ContainerInterface $container): void;
}
