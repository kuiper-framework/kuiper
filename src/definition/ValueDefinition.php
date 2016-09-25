<?php
namespace kuiper\di\definition;

class ValueDefinition implements DefinitionInterface
{
    use ScopeTrait;
    
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
