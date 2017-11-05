<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class CallableType extends ReflectionType
{
    public function getName(): string
    {
        return 'callable';
    }
}
