<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class IntegerType extends ReflectionType
{
    public function getName(): string
    {
        return 'int';
    }
}
