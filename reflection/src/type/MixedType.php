<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class MixedType extends ReflectionType
{
    public function getName(): string
    {
        return 'mixed';
    }

    public function isPseudo(): bool
    {
        return true;
    }

    public function isUnknown(): bool
    {
        return true;
    }

    public function isPrimitive(): bool
    {
        return true;
    }
}
