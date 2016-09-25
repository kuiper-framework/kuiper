<?php
namespace kuiper\di\definition;

class ArrayDefinition implements DefinitionInterface
{
    use ScopeTrait;

    /**
     * @var array
     */
    private $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function getValues()
    {
        return $this->values;
    }
}
