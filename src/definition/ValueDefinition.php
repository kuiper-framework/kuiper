<?php

namespace kuiper\di\definition;

class ValueDefinition extends AbstractDefinition
{
    /**
     * @var mixed
     */
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}
