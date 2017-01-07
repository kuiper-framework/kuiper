<?php

namespace kuiper\di\definition;

class StringDefinition extends AbstractDefinition
{
    /**
     * @var string
     */
    private $expression;

    public function __construct($expression)
    {
        $this->expression = $expression;
    }

    public function getExpression()
    {
        return $this->expression;
    }
}
