<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class FloatType extends ReflectionType
{
    public function getName(): string
    {
        return 'float';
    }
}
