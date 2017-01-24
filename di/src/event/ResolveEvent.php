<?php

namespace kuiper\di\event;

use kuiper\di\ContainerInterface;
use kuiper\di\DefinitionEntry;
use Symfony\Component\EventDispatcher\Event;

class ResolveEvent extends Event
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var DefinitionEntry
     */
    private $entry;

    /**
     * @var array
     */
    private $params;

    /**
     * @var mixed
     */
    private $value;

    public function __construct(ContainerInterface $container, DefinitionEntry $entry, array $params)
    {
        $this->container = $container;
        $this->entry = $entry;
        $this->params = $params;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getEntry()
    {
        return $this->entry;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}
