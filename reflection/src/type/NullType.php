<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class NullType extends ReflectionType
{
    public function getName(): string
    {
        return 'null';
    }
}
