<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use kuiper\di\ContainerBuilderAwareInterface;
use kuiper\di\ContainerBuilderAwareTrait;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Configuration implements ComponentInterface, ContainerBuilderAwareInterface
{
    use ComponentTrait;
    use ContainerBuilderAwareTrait;

    public function handle(): void
    {
        $this->containerBuilder->addConfiguration($this->class->newInstance());
    }
}
