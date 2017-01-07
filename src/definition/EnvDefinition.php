<?php

namespace kuiper\di\definition;

class EnvDefinition extends AbstractDefinition
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $defaultValue;

    public function __construct($envName, $default = null)
    {
        $this->name = $envName;
        $this->defaultValue = $default;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}
