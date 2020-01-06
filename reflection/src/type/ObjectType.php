<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class ObjectType extends ReflectionType
{
    public function getName(): string
    {
        return 'object';
    }
}
