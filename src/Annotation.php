<?php

namespace kuiper\annotations;

class Annotation
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $arguments;

    public function __construct($name, array $arguments)
    {
        $this->name = $name;
        $this->arguments = $arguments;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function setArguments($arguments)
    {
        $this->arguments = $arguments;

        return $this;
    }
}
