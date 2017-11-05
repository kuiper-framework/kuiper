<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class MixedType extends ReflectionType
{
    public function getName(): string
    {
        return 'mixed';
    }
}
