<?php

namespace kuiper\di\event;

use kuiper\di\ContainerInterface;
use kuiper\di\definition\DefinitionInterface;
use Symfony\Component\EventDispatcher\Event;

class DefinitionEvent extends Event
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $name;

    /**
     * @var DefinitionInterface
     */
    private $definition;

    public function __construct(ContainerInterface $container, $name)
    {
        $this->container = $container;
        $this->name = $name;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDefinition()
    {
        return $this->definition;
    }

    public function setDefinition(DefinitionInterface $definition)
    {
        $this->definition = $definition;

        return $this;
    }
}
