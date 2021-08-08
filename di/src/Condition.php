<?php

declare(strict_types=1);

namespace kuiper\di;

use Psr\Container\ContainerInterface;

interface Condition
{
    /**
     * Checks the condition.
     *
     * @param ContainerInterface $container
     *
     * @return bool
     */
    public function matches(ContainerInterface $container): bool;
}
