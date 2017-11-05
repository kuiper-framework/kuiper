<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class NumberType extends ReflectionType
{
    public function getName(): string
    {
        return 'number';
    }
}
