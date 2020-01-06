<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class NumberType extends ReflectionType
{
    public function getName(): string
    {
        return 'number';
    }

    public function isPseudo(): bool
    {
        return true;
    }

    public function isPrimitive(): bool
    {
        return true;
    }
}
