<?php

namespace kuiper\di\event;

use kuiper\di\DefinitionEntry;
use Symfony\Component\EventDispatcher\Event;

class DefinitionEvent extends Event
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var DefinitionEntry
     */
    private $definition;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDefinition()
    {
        return $this->definition;
    }

    public function setDefinition(DefinitionEntry $definition)
    {
        $this->definition = $definition;

        return $this;
    }
}
