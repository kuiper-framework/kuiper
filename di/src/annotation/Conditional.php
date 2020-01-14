<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use Psr\Container\ContainerInterface;

interface Conditional
{
    public function match(ContainerInterface $container): bool;
}
