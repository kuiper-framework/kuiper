<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class StringType extends ReflectionType
{
    public function getName(): string
    {
        return 'string';
    }

    public function isPrimitive(): bool
    {
        return true;
    }

    public function isScalar(): bool
    {
        return true;
    }
}
