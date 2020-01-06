<?php

declare(strict_types=1);

namespace kuiper\di;

interface ContainerBuilderAwareInterface
{
    public function setContainerBuilder(ContainerBuilderInterface $containerBuilder): void;
}
