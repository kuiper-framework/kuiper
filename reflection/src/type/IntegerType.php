<?php

declare(strict_types=1);

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class IntegerType extends ReflectionType
{
    public function getName(): string
    {
        return 'int';
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
