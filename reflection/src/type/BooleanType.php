<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class BooleanType extends ReflectionType
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'bool';
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
