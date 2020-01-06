<?php

declare(strict_types=1);

namespace kuiper\di;

trait ContainerBuilderAwareTrait
{
    /**
     * @var ContainerBuilderInterface
     */
    protected $containerBuilder;

    public function setContainerBuilder(ContainerBuilderInterface $containerBuilder): void
    {
        $this->containerBuilder = $containerBuilder;
    }
}
