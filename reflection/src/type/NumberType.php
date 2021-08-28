<?php

declare(strict_types=1);

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

    public function isScalar(): bool
    {
        return true;
    }

    public function isPrimitive(): bool
    {
        return true;
    }
}
