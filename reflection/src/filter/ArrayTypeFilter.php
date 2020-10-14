<?php

declare(strict_types=1);

namespace kuiper\reflection\filter;

use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\ArrayType;
use kuiper\reflection\TypeFilterInterface;

class ArrayTypeFilter implements TypeFilterInterface
{
    /**
     * @var ArrayType
     */
    private $type;

    /**
     * ArrayTypeFilter constructor.
     */
    public function __construct(ArrayType $type)
    {
        $this->type = $type;
    }

    /**
     * checks whether the value is valid.
     *
     * @param mixed $value
     */
    public function isValid($value): bool
    {
        return $this->validateArray($value, $this->type->getValueType(), $this->type->getDimension());
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
        return $this->sanitizeArray($value, $this->type->getValueType(), $this->type->getDimension());
    }

    /**
     * @param mixed $value
     */
    private function validateArray($value, ReflectionTypeInterface $valueType, int $dimension): bool
    {
        if (!is_array($value)) {
            return false;
        }
        if (1 === $dimension) {
            foreach ($value as $item) {
                if (!$valueType->isValid($item)) {
                    return false;
                }
            }
        } else {
            foreach ($value as $item) {
                if (!$this->validateArray($item, $valueType, $dimension - 1)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param mixed $value
     */
    private function sanitizeArray($value, ReflectionTypeInterface $valueType, int $dimension): array
    {
        $result = [];
        $value = (array) $value;
        if (1 === $dimension) {
            foreach ($value as $key => $item) {
                $result[$key] = $valueType->sanitize($item);
            }
        } else {
            foreach ($value as $key => $item) {
                $result[$key] = $this->sanitizeArray($item, $valueType, $dimension - 1);
            }
        }

        return $result;
    }
}
