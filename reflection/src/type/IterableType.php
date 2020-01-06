<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class IterableType extends ReflectionType
{
    public function getName(): string
    {
        return 'iterable';
    }
}
