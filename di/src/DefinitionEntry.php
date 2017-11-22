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

    /**
     * DefinitionEntry constructor.
     *
     * @param string              $name
     * @param DefinitionInterface $definition
     */
    public function __construct($name, DefinitionInterface $definition)
    {
        $this->name = $name;
        $this->definition = $definition;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return DefinitionInterface
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->definition->getScope();
    }
}
