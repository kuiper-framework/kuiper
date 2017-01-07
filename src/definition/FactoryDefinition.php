<?php

namespace kuiper\di\definition;

class FactoryDefinition extends AbstractDefinition
{
    /**
     * @var callable
     */
    private $factory;

    /**
     * @var array
     */
    private $arguments = [];

    /**
     * @var bool
     */
    private $isLazy = false;

    /**
     * @var string
     */
    private $returnType;

    public function __construct($factory, $arguments = [])
    {
        $this->factory = $factory;
        $this->arguments = $arguments;
    }

    public function lazy()
    {
        $this->isLazy = true;

        return $this;
    }

    public function getFactory()
    {
        return $this->factory;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function isLazy()
    {
        return $this->isLazy;
    }

    public function willReturn($type)
    {
        $this->returnType = $type;

        return $this;
    }

    public function getReturnType()
    {
        return $this->returnType;
    }
}
