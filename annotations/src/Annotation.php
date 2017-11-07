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

    /**
     * Annotation constructor.
     *
     * @param string $name
     * @param array  $arguments
     */
    public function __construct(string $name, array $arguments)
    {
        $this->name = $name;
        $this->arguments = $arguments;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Annotation
     */
    public function withName(string $name)
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     *
     * @return Annotation
     */
    public function withArguments(array $arguments)
    {
        $clone = clone $this;
        $clone->arguments = $arguments;

        return $clone;
    }
}
