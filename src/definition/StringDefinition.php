<?php
namespace kuiper\di\definition;

class StringDefinition implements DefinitionInterface
{
    use ScopeTrait;
    
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
