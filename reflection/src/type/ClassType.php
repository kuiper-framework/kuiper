<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;

class ClassType extends ReflectionType
{
    /**
     * @var string
     */
    private $className;

    public function __construct($className, $allowsNull = false)
    {
        parent::__construct($allowsNull);
        $this->className = $className;
    }

    public function getName(): string
    {
        return $this->className;
    }

    public function isClass(): bool
    {
        return true;
    }
}
