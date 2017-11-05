<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class ResourceType extends ReflectionType
{
    public function getName(): string
    {
        return 'resource';
    }
}
