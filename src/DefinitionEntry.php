<?php

namespace kuiper\di;

use kuiper\di\definition\DefinitionInterface;

class DefinitionEntry
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var DefinitionInterface
     */
    private $definition;

    public function __construct($name, DefinitionInterface $definition)
    {
        $this->name = $name;
        $this->definition = $definition;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDefinition()
    {
        return $this->definition;
    }

    public function getScope()
    {
        return $this->definition->getScope();
    }
}
