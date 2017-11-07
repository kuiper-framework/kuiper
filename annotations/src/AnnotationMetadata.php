<?php

namespace kuiper\annotations;

use kuiper\annotations\annotation\Target;

class AnnotationMetadata
{
    /**
     * @var string
     */
    private $className;
    /**
     * @var bool
     */
    private $hasConstructor = false;

    /**
     * @var int
     */
    private $targets = Target::TARGET_ALL;

    /**
     * @var string
     */
    private $defaultProperty;

    /**
     * - required boolean
     * - type ReflectionTypeInterface
     * - enums array.
     *
     * @var array
     */
    private $propertyAttributes = [];

    /**
     * AnnotationMetadata constructor.
     *
     * @param string $className
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return bool
     */
    public function hasConstructor(): bool
    {
        return $this->hasConstructor;
    }

    /**
     * @param bool $hasConstructor
     */
    public function setHasConstructor(bool $hasConstructor)
    {
        $this->hasConstructor = $hasConstructor;
    }

    /**
     * @return int
     */
    public function getTargets(): int
    {
        return $this->targets;
    }

    /**
     * @param int $targets
     *
     * @return $this
     */
    public function setTargets(int $targets)
    {
        $this->targets = $targets;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultProperty()
    {
        return $this->defaultProperty;
    }

    /**
     * @param string $defaultProperty
     *
     * @return $this
     */
    public function setDefaultProperty(string $defaultProperty)
    {
        $this->defaultProperty = $defaultProperty;

        return $this;
    }

    /**
     * @param string $propertyName
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    public function setPropertyAttribute(string $propertyName, string $name, $value)
    {
        $this->propertyAttributes[$propertyName][$name] = $value;

        return $this;
    }

    /**
     * @param string $propertyName
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getPropertyAttribute(string $propertyName, string $name, $default = null)
    {
        return isset($this->propertyAttributes[$propertyName][$name]) ? $this->propertyAttributes[$propertyName][$name] : $default;
    }

    /**
     * @param string $propertyName
     *
     * @return bool
     */
    public function hasProperty(string $propertyName)
    {
        return isset($this->propertyAttributes[$propertyName]);
    }

    /**
     * @return string[]
     */
    public function getProperties()
    {
        return array_keys($this->propertyAttributes);
    }
}
