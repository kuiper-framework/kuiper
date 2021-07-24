<?php

declare(strict_types=1);

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class VoidType extends ReflectionType
{
    public function getName(): string
    {
        return 'void';
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
