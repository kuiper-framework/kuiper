<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class ResourceType extends ReflectionType
{
    public function getName(): string
    {
        return 'resource';
    }

    public function isResource(): bool
    {
        return true;
    }

    public function isPrimitive(): bool
    {
        return true;
    }
}
