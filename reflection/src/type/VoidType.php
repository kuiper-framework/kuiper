<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class VoidType extends ReflectionType
{
    public function getName(): string
    {
        return 'void';
    }
}
