<?php

declare(strict_types=1);

namespace kuiper\di;

use Psr\Container\ContainerInterface;

trait ContainerAwareTrait
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }
}
