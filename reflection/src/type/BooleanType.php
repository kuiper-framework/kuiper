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
}
