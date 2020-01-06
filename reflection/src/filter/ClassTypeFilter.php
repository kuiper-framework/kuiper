<?php

namespace kuiper\reflection\filter;

use kuiper\reflection\type\ClassType;
use kuiper\reflection\TypeFilterInterface;

class ClassTypeFilter implements TypeFilterInterface
{
    /**
     * @var ClassType
     */
    private $type;

    /**
     * ClassTypeFilter constructor.
     *
     * @param ClassType $type
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
        $className = $this->type->getName();

        return $value instanceof $className;
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
