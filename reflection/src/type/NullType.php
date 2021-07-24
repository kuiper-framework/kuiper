<?php

declare(strict_types=1);

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class NullType extends ReflectionType
{
    public function getName(): string
    {
        return 'null';
    }

    public function isNull(): bool
    {
        return true;
    }

    public function isPrimitive(): bool
    {
        return true;
    }
}
