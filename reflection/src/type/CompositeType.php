<?php

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;
use kuiper\reflection\ReflectionTypeInterface;

class CompositeType extends ReflectionType
{
    /**
     * @var ReflectionTypeInterface[]
     */
    private $types;

    public function __construct(array $types)
    {
        parent::__construct(false);
        $this->types = $types;
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function getName(): string
    {
        return implode('|', array_map(function (ReflectionTypeInterface $type) {
            return $type->getName();
        }, $this->types));
    }

    public function allowsNull(): bool
    {
        foreach ($this->types as $type) {
            if ($type->allowsNull()) {
                return true;
            }
        }

        return false;
    }

    public function __toString(): string
    {
        return implode('|', $this->types);
    }

    public function isComposite(): bool
    {
        return true;
    }
}
