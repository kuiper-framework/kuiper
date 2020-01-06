<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Service extends Component
{
    protected function getBeanNames(): array
    {
        return $this->class->getInterfaceNames() ?: parent::getBeanNames();
    }
}
