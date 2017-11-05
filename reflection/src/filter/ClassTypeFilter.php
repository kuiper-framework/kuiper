<?php

namespace kuiper\reflection\filter;

use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\ClassType;
use kuiper\reflection\TypeFilterInterface;
use kuiper\reflection\TypeUtils;

class ClassTypeFilter implements TypeFilterInterface
{
    /**
     * @var ClassType
     */
    private $type;

    /**
     * ClassTypeFilter constructor.
     */
    public function __construct(ClassType $type)
    {

        $this->type = $type;
    }

    /**
     * checks whether the value is valid.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function validate($value)
    {
        return $value instanceof $this->type->getName();
    }

    /**
     * Sanitizes input value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function sanitize($value)
    {
        return $value;
    }
}