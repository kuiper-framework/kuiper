<?php

namespace kuiper\di\definition;

class ObjectDefinition extends AbstractDefinition
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var array
     */
    private $constructorParameters = [];

    /**
     * @var array
     */
    private $properties = [];

    /**
     * @var array
     */
    private $methods = [];

    /**
     * @var bool
     */
    private $isLazy = false;

    public function __construct($className = null)
    {
        $this->className = $className;
    }

    public function constructor($param)
    {
        if ($param instanceof NamedParameters) {
            $this->constructorParameters = $param;
        } else {
            $this->constructorParameters = func_get_args();
        }

        return $this;
    }

    public function property($property, $value)
    {
        $this->properties[$property] = $value;

        return $this;
    }

    public function method($method)
    {
        $args = func_get_args();
        array_shift($args);
        $this->methods[$method][] = $args;

        return $this;
    }

    public function lazy()
    {
        $this->isLazy = true;

        return $this;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getConstructorParameters()
    {
        return $this->constructorParameters;
    }

    public function setConstructorParameters(array $parameters)
    {
        $this->constructorParameters = $parameters;

        return $this;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function setProperties(array $properties)
    {
        $this->properties = $properties;

        return $this;
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function setMethods(array $methods)
    {
        $this->methods = $methods;

        return $this;
    }

    public function isLazy()
    {
        return $this->isLazy;
    }
}
